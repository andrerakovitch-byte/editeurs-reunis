"""Les articles du journal de l'ancien site Wix, prêts pour l'import dans le site (`wp er journal importer`).

    python3 outils/wix/journal.py <journal.json> <sortie.json> [--rapport rapport.md]

- journal.json : les pages des articles lues sur l'ancien site (`outils/wix/pages.py journal`) : le texte tel que la
  page l'affiche, les catégories, les mots-clés, les données structurées (titre, date, auteur, image).

La sortie, une liste d'objets : wix_slug, slug, titre, date (heure de Paris, « AAAA-MM-JJ HH:MM:SS »), signature (le
nom de celui qui a écrit l'article), extrait, image (l'adresse de l'original), contenu (en blocs de WordPress : les
paragraphes, les intertitres, les listes, les citations, les images avec leur légende, les vidéos, les filets ; les
images gardent leur adresse chez Wix, que l'import remplace par celle de la médiathèque), categories, personnes et
sujets, livres (les références des livres dont il parle), mots (des mots-clés pour la recherche) : d'après les
étiquettes relues de journal-etiquettes.json, sinon d'après les mots-clés de Wix (une personne devient un auteur, le
reste un sujet).
"""
import argparse
import html
import json
import re
import sys
import urllib.parse
from datetime import datetime
from zoneinfo import ZoneInfo

from bs4 import BeautifulSoup, NavigableString, Tag

sys.path.insert(0, __import__('os').path.dirname(__file__))
from evenements import typo  # noqa: E402

PARIS = ZoneInfo('Europe/Paris')
WIX = 'https://static.wixstatic.com/media/'

# Les étiquettes relues article par article, d'après leur contenu (journal-etiquettes.json, à côté) : la signature
# (celui qui a écrit, que Wix confond souvent avec le compte qui a publié), les personnes, les sujets (une liste
# commune à tout le site), les livres de la librairie dont parle l'article (leurs références) et des mots-clés pour
# la recherche (les œuvres, les lieux, les notions). Elles remplacent les mots-clés de Wix, trop inégaux.
try:
    ETIQUETTES = json.load(open(__import__('os').path.join(__import__('os').path.dirname(__file__), 'journal-etiquettes.json')))
except FileNotFoundError:
    ETIQUETTES = {}


def chapo(extrait, contenu, relu=''):
    """Le chapô : celui qu'on a relu, sinon celui de Wix, sauf s'il commence au milieu d'une phrase ou s'arrête en
    route ; alors les premières phrases des paragraphes du texte (ni les légendes, ni les intertitres)."""
    if relu:
        return relu
    extrait = re.sub(r'\s+', ' ', extrait or '').strip()
    if len(extrait) >= 40 and extrait[:1].isupper() and re.search(r'[.!?»)]$', extrait) and not extrait.endswith('...'):
        return extrait
    # Un article de Wix commence souvent par un intertitre qui le résume : on le prend, s'il est assez long.
    tete = re.match(r'\s*<!-- wp:heading[^>]*-->\s*<h\d[^>]*>(.*?)</h\d>', contenu, re.S)
    if tete and len(re.sub(r'<[^>]+>', '', tete.group(1))) >= 60:
        return html.unescape(re.sub(r'<[^>]+>', '', tete.group(1))).strip()
    paragraphes = [html.unescape(re.sub(r'<[^>]+>', '', p)).strip() for p in re.findall(r'<!-- wp:paragraph -->\s*<p>(.*?)</p>', contenu, re.S)]
    paragraphes = [re.sub(r'^par [^.]{3,60}?(?=[A-ZÉÀ])', '', p).strip() for p in paragraphes if len(p) >= 60]
    sortie = ''
    for ph in re.split(r'(?<=[.!?»])\s+', ' '.join(paragraphes[:3])):
        if len(sortie) + len(ph) > 280 and sortie:
            break
        sortie = (sortie + ' ' + ph).strip()
    return sortie[:320] or extrait


# Les mots-clés de Wix qui sont des sujets bien qu'ils aient l'air de noms propres, et l'inverse.
SUJETS_FORCES = {'Archipel du Goulag', 'Goulag', 'YMCA-Press', 'Russie', 'Ukraine', 'France', 'Paris', 'URSS',
                 'Révolution russe', 'Église orthodoxe', 'Seconde Guerre mondiale', 'Première Guerre mondiale'}
PARTICULES = {'de', 'du', 'des', 'd', 'von', 'van', 'le', 'la', 'di', 'da', 'del', 'saint', 'sainte'}
TITRES = {'mère', 'père', 'saint', 'sainte', 'mgr', 'monseigneur', 'sœur', 'frère', 'отец', 'мать'}


def personne(nom):
    """Un mot-clé qui a l'air d'un nom de personne : deux mots ou plus, chacun avec une capitale (les particules et
    les titres « mère », « père » mis à part), sans article ni mot commun en tête."""
    if nom in SUJETS_FORCES:
        return False
    parties = [p for p in re.split(r'[\s’\']+', nom) if p]
    noms = [p for p in parties if p.lower() not in PARTICULES and p.lower() not in TITRES]
    return len(noms) >= 2 and all(p[:1].isupper() for p in noms) and not re.search(r'\d', nom)


# --- le texte de l'article, en blocs ------------------------------------------------------------------------------

def en_ligne(noeud):
    """Le contenu d'un paragraphe : le texte, l'italique, le gras, le souligné, les liens, les retours à la ligne."""
    if isinstance(noeud, NavigableString):
        return html.escape(typo(str(noeud)), quote=False)
    if not isinstance(noeud, Tag) or noeud.name in ('svg', 'button', 'script', 'style'):
        return ''
    dedans = ''.join(en_ligne(e) for e in noeud.children)
    style = (noeud.get('style') or '').replace(' ', '')
    if noeud.name == 'br':
        return '<br>'
    if noeud.name in ('em', 'i') or 'font-style:italic' in style:
        return f'<em>{dedans}</em>' if dedans.strip() else dedans
    if noeud.name in ('strong', 'b') or 'font-weight:700' in style or 'font-weight:bold' in style:
        return f'<strong>{dedans}</strong>' if dedans.strip() else dedans
    if noeud.name == 'u' or 'text-decoration:underline' in style:
        return f'<span style="text-decoration: underline">{dedans}</span>' if dedans.strip() else dedans
    if noeud.name == 'a' and noeud.get('href'):
        return f'<a href="{html.escape(noeud["href"])}">{dedans}</a>'
    return dedans


def propre(h):
    h = re.sub(r'(<br>\s*)+$', '', h.strip())
    h = re.sub(r'^(\s*<br>)+', '', h)
    return h.replace('</em><em>', '').replace('</strong><strong>', '').strip()


def bloc(nom, attrs, html_bloc):
    a = (' ' + json.dumps(attrs, ensure_ascii=False, separators=(',', ':'))) if attrs else ''
    return f'<!-- wp:{nom}{a} -->\n{html_bloc}\n<!-- /wp:{nom} -->'


def image_de(figure):
    """L'image d'une figure de Wix : l'original (sans les retouches de taille), sa légende."""
    wow = figure.find('wow-image')
    uri = wow.get('id') if wow and wow.get('id') else ''
    if not uri:
        img = figure.find('img')
        m = re.search(r'/media/([^/]+?)(?:/v1/|$)', img.get('src', '') if img else '')
        uri = m.group(1) if m else ''
    legende = figure.find('figcaption')
    return (WIX + uri if uri else ''), (propre(en_ligne(legende)) if legende else '')


def blocs(contenu):
    soupe = BeautifulSoup(contenu, 'html.parser')
    sortie, images = [], []

    def visiter(n):
        if not isinstance(n, Tag):
            return
        hook = n.get('data-hook') or ''
        if n.name == 'figure' or hook.startswith('figure-'):
            iframe = n.find('iframe')
            if iframe and iframe.get('src'):
                url = iframe['src']
                ident = re.search(r'(?:embed/|v=)([\w-]{11})', url)
                if ident:
                    url = 'https://www.youtube.com/watch?v=' + ident.group(1)
                sortie.append(bloc('embed', {'url': url, 'type': 'video', 'responsive': True},
                                   f'<figure class="wp-block-embed is-type-video"><div class="wp-block-embed__wrapper">\n{url}\n</div></figure>'))
                return
            for fig in ([n] if n.find('wow-image') or n.find('img') else []):
                src, legende = image_de(fig)
                if src:
                    images.append(src)
                    cap = f'<figcaption class="wp-element-caption">{legende}</figcaption>' if legende else ''
                    sortie.append(bloc('image', {'sizeSlug': 'large'},
                                       f'<figure class="wp-block-image size-large"><img src="{src}" alt=""/>{cap}</figure>'))
            return
        if n.name in ('p',) or (n.name == 'div' and n.get('data-ricos-paragraph') is not None):
            texte = propre(en_ligne(n))
            if re.sub(r'<br>|\s', '', texte):
                sortie.append(bloc('paragraph', None, f'<p>{texte}</p>'))
            return
        if n.name in ('h1', 'h2', 'h3', 'h4', 'h5', 'h6'):
            texte = propre(en_ligne(n))
            if texte:
                niveau = max(2, min(4, int(n.name[1])))
                sortie.append(bloc('heading', {'level': niveau} if niveau != 2 else None,
                                   f'<h{niveau} class="wp-block-heading">{texte}</h{niveau}>'))
            return
        if n.name in ('ul', 'ol'):
            items = []
            for li in n.find_all('li', recursive=False):
                texte = propre(en_ligne(li))
                if texte:
                    items.append(bloc('list-item', None, f'<li>{texte}</li>'))
            if items:
                sortie.append(bloc('list', {'ordered': True} if n.name == 'ol' else None,
                                   f'<{n.name} class="wp-block-list">' + '\n'.join(items) + f'</{n.name}>'))
            return
        if n.name == 'blockquote':
            texte = propre(en_ligne(n))
            if texte:
                sortie.append(bloc('quote', None, '<blockquote class="wp-block-quote">'
                                   + bloc('paragraph', None, f'<p>{texte}</p>') + '</blockquote>'))
            return
        if 'divider' in hook or n.name == 'hr':
            sortie.append(bloc('separator', None, '<hr class="wp-block-separator has-alpha-channel-opacity"/>'))
            return
        for e in n.children:
            visiter(e)

    visiter(soupe)
    return '\n\n'.join(sortie), images


# --- les articles ---------------------------------------------------------------------------------------------

def original(url):
    m = re.search(r'/media/([^/]+?)(?:/v1/|$)', url or '')
    return WIX + m.group(1) if m else (url or '')


def sans_signature(contenu, signature):
    """Ôte le premier paragraphe quand il ne fait que redire la signature (« par X ») : la page la donne déjà, en tête."""
    m = re.match(r'\s*<!-- wp:paragraph -->\s*<p>(.*?)</p>\s*<!-- /wp:paragraph -->\s*', contenu, re.S)
    if not m or not signature:
        return contenu
    texte = re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', '', m.group(1)))).strip(' .').lower()
    if texte in (signature.lower(), 'par ' + signature.lower()):
        return contenu[m.end():]
    return contenu


def convertir(pages):
    sortie = []
    for p in pages:
        if not p.get('contenu'):
            continue
        ld = next((b for b in p.get('json_ld', []) if isinstance(b, dict) and b.get('@type') == 'BlogPosting'), {})
        meta = p.get('meta', {})
        # Wix coupe le titre des données structurées à 110 signes : le titre de la page (og:title) est entier.
        titre = max((html.unescape(t or '').strip() for t in (ld.get('headline'), meta.get('og:title'))), key=len)
        titre = re.sub(r'\s+-\s+', ' – ', titre)  # « Anne-Marie Pelletier - Résister… » : le tiret demi-cadratin
        titre = re.sub(r'(?<=[^\s\d:]):(?=\s)', ' :', titre)  # « Boris Akounine: » : l'espace avant le deux-points
        titre = typo(re.sub(r'^"(.*)"', r'« \1 »', titre)).strip().rstrip('.').strip()
        quand = ld.get('datePublished') or meta.get('article:published_time') or ''
        date = datetime.fromisoformat(quand.replace('Z', '+00:00')).astimezone(PARIS).strftime('%Y-%m-%d %H:%M:%S') if quand else ''
        signature = ((ld.get('author') or {}).get('name') or '').strip()
        contenu, images = blocs(p['contenu'])
        wix_slug = urllib.parse.unquote(p['url'].rstrip('/').rsplit('/', 1)[-1])
        etiquettes = [html.unescape(e[1]).strip() for e in p.get('etiquettes', [])]
        relues = ETIQUETTES.get(wix_slug)
        if relues:
            titre = relues.get('titre') or titre  # un titre mal formé chez Wix, refait à la main
            signature = relues['signature']
            personnes, sujets = list(relues['personnes']), list(relues['sujets'])
        else:
            personnes = [e for e in etiquettes if personne(e)]
            sujets = [e for e in etiquettes if e not in personnes]
        if signature and signature not in personnes:
            personnes.append(signature)
        contenu = sans_signature(contenu, signature)
        if signature:
            # « Langue maternelle » par Georges Nivat : la signature est sous le titre, le titre n'a pas à la redire.
            titre = re.sub(r',?\s+(?:par|de)\s+' + re.escape(signature) + r'(?:,[^()]*)?(?=\s*\(|$)', '', titre).strip()
        texte = re.sub(r'<[^>]+>', ' ', contenu)
        cyr, lat = len(re.findall('[а-яёА-ЯЁ]', texte)), len(re.findall('[a-zA-Zà-ÿÀ-Ÿ]', texte))
        langues = ' '.join(l for l, n, m in (('fr', lat, cyr), ('ru', cyr, lat)) if n >= 400 and n >= 0.25 * m) or 'fr'
        sortie.append({
            'wix_slug': wix_slug, 'slug': wix_slug, 'titre': titre, 'date': date, 'signature': signature,
            'extrait': chapo(typo(html.unescape(ld.get('description') or meta.get('og:description') or '')), contenu,
                             relues.get('chapo', '') if relues else ''),
            'image': original((ld.get('image') or {}).get('url') if isinstance(ld.get('image'), dict) else meta.get('og:image')),
            'contenu': contenu, 'images': images,
            'categories': [typo(html.unescape(c[1])).strip() for c in p.get('categories', [])],
            'personnes': personnes, 'sujets': sujets,
            'livres': relues['livres'] if relues else [], 'mots': relues['mots'] if relues else [],
            'relu': bool(relues),
            'genre': relues.get('genre', '') if relues else '',
            'langues': langues,
        })
    sortie.sort(key=lambda a: a['date'], reverse=True)
    return sortie


def rapport(articles, pages):
    import collections
    l = ['# Le journal de l’ancien site', '',
         f'{len(articles)} articles convertis sur {len(pages)} pages lues ; '
         f'{sum(1 for p in pages if p.get("erreur"))} pages en erreur.', '',
         '## Les rubriques (catégories de Wix)', '']
    for c, n in collections.Counter(c for a in articles for c in a['categories']).most_common():
        l.append(f'- {c} ({n})')
    l += ['', '## Les mots-clés devenus des personnes (des auteurs)', '']
    for c, n in collections.Counter(c for a in articles for c in a['personnes']).most_common():
        l.append(f'- {c} ({n})')
    l += ['', '## Les mots-clés devenus des sujets', '']
    for c, n in collections.Counter(c for a in articles for c in a['sujets']).most_common():
        l.append(f'- {c} ({n})')
    return '\n'.join(l) + '\n'


if __name__ == '__main__':
    arg = argparse.ArgumentParser()
    arg.add_argument('journal')
    arg.add_argument('sortie')
    arg.add_argument('--rapport')
    a = arg.parse_args()
    pages = json.load(open(a.journal))
    articles = convertir(pages)
    json.dump(articles, open(a.sortie, 'w'), ensure_ascii=False, indent=1)
    if a.rapport:
        open(a.rapport, 'w').write(rapport(articles, pages))
    print(len(articles), 'articles ;', sum(len(x['images']) for x in articles), 'images dans les textes ;',
          sum(1 for x in articles if x['image']), 'avec une image à la une')
