"""Les notices des livres : ce que les catalogues des bibliothèques savent de chaque livre de la librairie.

    python3 outils/catalogue/notices.py <livres.json> <notices.json> [--rapport rapport.md] [--limite N]

<livres.json> vient de `wp er librairie exporter` (le workflow « Déployer chez Kinsta », « notices ») ; il ne va
jamais dans le dépôt. Pour chaque livre, trois catalogues sont interrogés : la BnF (SRU, Dublin Core), Google Books et
Open Library. Chaque réponse est notée contre la fiche : le titre, l'auteur, l'éditeur, l'année, la langue.

Deux niveaux, parce qu'un classique a cent éditions :
- l'édition (ISBN, pages, année, éditeur) : posée seulement quand l'éditeur de la fiche concorde avec celui de la
  notice, ou quand la fiche n'en dit rien mais qu'une seule édition de ce titre, de cet auteur, dans cette langue, est
  connue ; sinon, la notice est « à revoir » ;
- l'œuvre (première parution, sujets Rameau de la BnF, sujets d'Open Library) : dès que le titre et l'auteur
  concordent.

Écrit <notices.json> (identifiant du livre → ce qui est sûr, ce qui est à revoir, les sources) et un rapport en comptes.
"""
import argparse
import concurrent.futures
import json
import os
import re
import sys
import time
import unicodedata
import urllib.parse
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET

AGENT = 'EditeursReunis-Notices/1.0 (librairie des Editeurs reunis, Paris ; contact via le site)'

CYR = {
    'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'z', 'з': 'z', 'и': 'i', 'й': 'i',
    'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f',
    'х': 'h', 'ц': 'c', 'ч': 'c', 'ш': 's', 'щ': 's', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'u', 'я': 'a',
    'і': 'i', 'ї': 'i', 'є': 'e', 'ґ': 'g',
}


# --- les clés ------------------------------------------------------------------------------------------------------


def sans_accents(t):
    t = unicodedata.normalize('NFKD', t)
    return ''.join(c for c in t if not unicodedata.combining(c))


def translit(t):
    """Le cyrillique en lettres latines sans signes (à peu près l'ISO 9 de la BnF, signes ôtés)."""
    sortie = []
    for c in t:
        if c in CYR:
            sortie.append(CYR[c])
        elif c.lower() in CYR:
            sortie.append(CYR[c.lower()].capitalize())
        else:
            sortie.append(c)
    return ''.join(sortie)


def cle(t):
    """Une clé de comparaison : minuscules, sans accents ni ponctuation, le cyrillique translittéré, les graphies
    française et anglaise rapprochées (tch, ch, sh → c ; ou → u ; y → i ; ie → e ; lettres doublées)."""
    t = sans_accents(translit((t or '').lower())).lower()
    t = re.sub(r'[^a-z0-9]+', ' ', t)
    for a, b in (('shch', 'c'), ('chtch', 'c'), ('tch', 'c'), ('sch', 'c'), ('sh', 'c'), ('ch', 'c'), ('zh', 'z'),
                 ('kh', 'h'), ('ou', 'u'), ('y', 'i'), ('ie', 'e'), ('ii', 'i'), ('w', 'v'), ('ph', 'f')):
        t = t.replace(a, b)
    # Le « j » français et le « ž » de la BnF disent le même ж ; « ts » et « c » le même ц.
    t = t.replace('j', 'z').replace('ts', 'c').replace('tz', 'c')
    t = re.sub(r'([a-z])\1+', r'\1', t)
    t = re.sub(r'e\b', '', t)
    return ' '.join(t.split())


VIDES = {'le', 'la', 'les', 'l', 'un', 'une', 'des', 'de', 'du', 'd', 'et', 'en', 'a', 'au', 'aux', 'the', 'of',
         'i', 'v', 'na', 'o', 's', 'k', 'tome', 'vol', 'volume', 't'}


def mots(t):
    return [m for m in cle(t).split() if m not in VIDES and len(m) > 1]


def titre_court(t):
    """Le titre sans sous-titre ni tome : ce qui précède « : », « . », « - », « ( »."""
    t = re.split(r'\s[:\-–—]\s|\.\s|\(|,\s*(?:tome|т\.|vol)', t)[0]
    return t.strip()


def ressemblance(a, b):
    """La part des mots de a qu'on retrouve dans b, et l'inverse : la plus petite des deux."""
    ma, mb = set(mots(a)), set(mots(b))
    if not ma or not mb:
        return 0.0
    commun = len(ma & mb)
    return min(commun / len(ma), commun / len(mb))


def nom_de_famille(nom):
    m = mots(nom)
    return m[-1] if m else ''


def isbn13(valeur):
    """Un ISBN valable, en 13 chiffres ; '' sinon."""
    t = re.sub(r'[^0-9Xx]', '', valeur or '').upper()
    if len(t) == 10 and re.fullmatch(r'\d{9}[\dX]', t):
        r = (11 - sum(int(c) * (10 - i) for i, c in enumerate(t[:9])) % 11) % 11
        if ('X' if r == 10 else str(r)) != t[9]:
            return ''
        t = '978' + t[:9]
        return t + str((10 - sum(int(c) * (3 if i % 2 else 1) for i, c in enumerate(t)) % 10) % 10)
    if len(t) == 13 and t.isdigit() and t.startswith(('978', '979')):
        if str((10 - sum(int(c) * (3 if i % 2 else 1) for i, c in enumerate(t[:12])) % 10) % 10) == t[12]:
            return t
    return ''


LANGUES = {'fre': 'fr', 'fra': 'fr', 'fr': 'fr', 'rus': 'ru', 'ru': 'ru', 'eng': 'en', 'en': 'en', 'ger': 'de',
           'deu': 'de', 'de': 'de', 'ukr': 'uk', 'uk': 'uk', 'ita': 'it', 'it': 'it', 'chu': 'cu'}


# --- les catalogues ------------------------------------------------------------------------------------------------


# Un catalogue qui refuse (quota dépassé, accès interdit) n'est pas relancé quatre fois par livre : après dix refus de
# suite, on le laisse de côté pour le reste du passage (le rapport le dit).
REFUS = {}
REFUS_MAX = 10


def lire(url, essais=3, delai=20):
    hote = urllib.parse.urlsplit(url).hostname or ''
    if REFUS.get(hote, 0) >= REFUS_MAX:
        return ''
    for essai in range(essais):
        try:
            req = urllib.request.Request(url, headers={'User-Agent': AGENT, 'Accept-Language': 'fr'})
            with urllib.request.urlopen(req, timeout=delai) as r:
                REFUS[hote] = 0
                return r.read().decode('utf-8', 'replace')
        except urllib.error.HTTPError as e:
            if 400 <= e.code < 500:
                REFUS[hote] = REFUS.get(hote, 0) + 1
                if e.code != 429 or essai:
                    return ''
            time.sleep(2 + 3 * essai)
        except Exception:
            time.sleep(2 + 3 * essai)
    return ''


def simples(t):
    """Les mots d'un texte comme le catalogue de la BnF les cherche : sans accents, le cyrillique translittéré."""
    return [m for m in re.sub(r'[^a-z0-9]+', ' ', sans_accents(translit(t or '')).lower()).split() if m not in VIDES]


def bnf_requete(requete, maximum=8):
    url = 'https://catalogue.bnf.fr/api/SRU?' + urllib.parse.urlencode({
        'version': '1.2', 'operation': 'searchRetrieve', 'query': requete,
        'recordSchema': 'dublincore', 'maximumRecords': str(maximum)})
    xml = lire(url)
    try:
        return ET.fromstring(xml) if xml else None
    except ET.ParseError:
        return None


def bnf(titre, auteur, maximum=8):
    """Les notices de la BnF (Dublin Core) pour ce titre et cet auteur ; sans résultat, pour ce titre seul."""
    t = ' '.join(simples(titre_court(titre))[:6])
    a = (simples(auteur) or [''])[-1] if auteur else ''
    if not t:
        return []
    racine = bnf_requete(f'bib.title all "{t}"' + (f' and bib.author all "{a}"' if a else ''), maximum)
    if a and (racine is None or not list(racine.iter('{http://www.loc.gov/zing/srw/}recordData'))):
        racine = bnf_requete(f'bib.title all "{t}"', maximum)
    if racine is None:
        return []
    dc = '{http://purl.org/dc/elements/1.1/}'
    notices = []
    for rec in racine.iter('{http://www.loc.gov/zing/srw/}recordData'):
        champs = {}
        for e in rec.iter():
            if e.tag.startswith(dc) and e.text:
                champs.setdefault(e.tag[len(dc):], []).append(e.text.strip())
        if not champs.get('title'):
            continue
        ids = champs.get('identifier', [])
        isbns = [isbn13(re.sub(r'^ISBN\s*', '', i)) for i in ids if i.upper().startswith('ISBN')]
        ark = next((i for i in ids if 'ark:' in i), '')
        pages = 0
        for f in champs.get('format', []):
            m = re.search(r'(\d{2,4})\s*(?:p\.|с\.|pages)', f)
            if m:
                pages = int(m.group(1))
                break
        annee = next((m.group(0) for d in champs.get('date', []) for m in [re.search(r'1[4-9]\d\d|20[0-2]\d', d)] if m), '')
        notices.append({
            'source': 'bnf', 'lien': ark,
            'titre': champs['title'][0].split(' / ')[0],
            'auteurs': champs.get('creator', []),
            'editeur': (champs.get('publisher') or [''])[0],
            'annee': annee, 'pages': pages, 'isbn': [i for i in isbns if i],
            'langues': sorted({LANGUES.get(lang, lang) for lang in champs.get('language', [])}),
            'sujets': champs.get('subject', []),
        })
    return notices


def google(titre, auteur, max_google=8):
    q = f'intitle:{titre_court(titre)}' + (f' inauthor:{auteur.split()[-1]}' if auteur else '')
    url = 'https://www.googleapis.com/books/v1/volumes?' + urllib.parse.urlencode({'q': q, 'maxResults': str(max_google),
                                                                                   'country': 'FR'})
    texte = lire(url)
    try:
        items = json.loads(texte).get('items', []) if texte else []
    except json.JSONDecodeError:
        return []
    notices = []
    for it in items:
        v = it.get('volumeInfo', {})
        vente = it.get('saleInfo', {})
        prix = (vente.get('listPrice') or vente.get('retailPrice') or {}).get('amount', '')
        isbns = [isbn13(i.get('identifier', '')) for i in v.get('industryIdentifiers', [])]
        notices.append({
            'source': 'google', 'lien': v.get('infoLink', ''),
            'titre': v.get('title', '') + (' : ' + v['subtitle'] if v.get('subtitle') else ''),
            'auteurs': v.get('authors', []),
            'editeur': v.get('publisher', ''),
            'annee': (v.get('publishedDate') or '')[:4],
            'pages': int(v.get('pageCount') or 0),
            'isbn': sorted({i for i in isbns if i}),
            'langues': [LANGUES.get(v.get('language', ''), v.get('language', ''))] if v.get('language') else [],
            'sujets': v.get('categories', []),
            'prix': prix,
        })
    return notices


def sudoc(titre, auteur, maximum=15):
    """Les notices du Sudoc (UNIMARC), riche en livres russes et de l'émigration (la BULAC) : mots du titre, et nom
    de l'auteur s'il est donné."""
    t = [m for m in simples(titre_court(titre)) if len(m) > 2][:4]
    if not t:
        return []
    requete = ' and '.join(f'mti={m}' for m in t)
    a = (simples(auteur) or [''])[-1] if auteur else ''
    if a:
        requete += f' and aut={a}'
    url = 'https://www.sudoc.abes.fr/cbs/sru/?' + urllib.parse.urlencode({
        'operation': 'searchRetrieve', 'version': '1.1', 'query': requete, 'recordSchema': 'unimarc',
        'maximumRecords': str(maximum)})
    xml = lire(url, essais=1, delai=10)
    try:
        racine = ET.fromstring(xml) if xml else None
    except ET.ParseError:
        return []
    if racine is None:
        return []
    notices = []
    for rec in racine.iter():
        if not rec.tag.endswith('}record') and rec.tag != 'record':
            continue
        champs = {}
        ppn = ''
        for e in rec:
            nom = e.tag.split('}')[-1]
            if nom == 'controlfield' and e.get('tag') == '001':
                ppn = (e.text or '').strip()
            if nom != 'datafield':
                continue
            sous = {}
            for sf in e:
                if sf.text:
                    sous.setdefault(sf.get('code'), []).append(sf.text.strip())
            champs.setdefault(e.get('tag'), []).append(sous)
        if '200' not in champs:
            continue
        def premier(tag, code, champs=champs):
            return next((v[0] for f in champs.get(tag, []) for v in [f.get(code)] if v), '')
        isbns = [isbn13(v) for f in champs.get('010', []) for v in f.get('a', [])]
        pages = 0
        m = re.search(r'(\d{2,4})\s*(?:p\.|с\.|pages)', premier('215', 'a'))
        if m:
            pages = int(m.group(1))
        date = premier('210', 'd') or premier('214', 'd')
        annee = (re.search(r'1[4-9]\d\d|20[0-2]\d', date) or [''])[0]
        auteurs = [', '.join(f.get('a', []) + f.get('b', [])) for tag in ('700', '701') for f in champs.get(tag, [])]
        notices.append({
            'source': 'sudoc', 'lien': f'https://www.sudoc.fr/{ppn}' if ppn else '',
            'titre': premier('200', 'a') + (' : ' + premier('200', 'e') if premier('200', 'e') else ''),
            'auteurs': auteurs,
            'editeur': premier('210', 'c') or premier('214', 'c'),
            'annee': annee, 'pages': pages, 'isbn': [i for i in isbns if i],
            'langues': [], 'sujets': [],
        })
    if a and not notices:
        return sudoc(titre, '', maximum)
    return notices


def open_library(titre, auteur):
    params = {'title': titre_court(titre), 'limit': '5',
              'fields': 'key,title,author_name,first_publish_year,isbn,number_of_pages_median,language,subject,publisher,edition_count'}
    if auteur:
        params['author'] = auteur
    texte = lire('https://openlibrary.org/search.json?' + urllib.parse.urlencode(params))
    try:
        docs = json.loads(texte).get('docs', []) if texte else []
    except json.JSONDecodeError:
        return []
    return [{
        'source': 'openlibrary', 'lien': 'https://openlibrary.org' + d.get('key', ''),
        'titre': d.get('title', ''), 'auteurs': d.get('author_name', []),
        'premiere_parution': d.get('first_publish_year') or 0,
        'editions': d.get('edition_count') or 0,
        'editeurs': (d.get('publisher') or [])[:30],
        'pages': d.get('number_of_pages_median') or 0,
        'isbn': [i for i in (isbn13(x) for x in (d.get('isbn') or [])[:60]) if i],
        'langues': sorted({LANGUES.get(lang, lang) for lang in d.get('language', [])}),
        'sujets': (d.get('subject') or [])[:25],
    } for d in docs]


# --- la correspondance --------------------------------------------------------------------------------------------


def cle_editeur(e):
    k = cle(e)
    k = re.sub(r'\b(editions?|editeurs?|publishing|publications?|publisher|izdatelstvo|izd|press)\b', ' ', k)
    return ''.join(k.split())


def concorde_editeur(a, b):
    ka, kb = cle_editeur(a), cle_editeur(b)
    if not ka or not kb:
        return False
    return ka in kb or kb in ka or ('ymca' in ka and 'ymca' in kb)


def note(livre, n):
    """La note d'une notice contre la fiche : titre (0 à 1), auteur (0 ou 1), éditeur, année."""
    titre = ressemblance(titre_court(livre['titre']), titre_court(n['titre']))
    titre = max(titre, ressemblance(livre['titre'], n['titre']))
    noms = {nom_de_famille(a) for a in livre['auteurs']} - {''}
    leurs = ' '.join(cle(a) for a in n.get('auteurs', []))
    auteur = 1.0 if noms and any(nm in leurs.split() for nm in noms) else (0.5 if not noms else 0.0)
    editeur = concorde_editeur(livre.get('editeur', ''), n.get('editeur', '')) if n.get('editeur') else False
    la_sienne = re.search(r'\d{4}', livre.get('annee', '') or '')
    la_leur = re.search(r'\d{4}', str(n.get('annee', '') or ''))
    annee = bool(la_sienne and la_leur and abs(int(la_sienne.group(0)) - int(la_leur.group(0))) <= 1)
    return {'titre': round(titre, 2), 'auteur': auteur, 'editeur': editeur, 'annee': annee}


def langues_du_livre(livre):
    codes = {'francais': 'fr', 'russe': 'ru', 'anglais': 'en', 'bilingue-russe-francais': None}
    out = set()
    for s in livre.get('langues', []):
        if s.startswith('bilingue'):
            out |= {'fr', 'ru'}
        elif codes.get(s):
            out.add(codes[s])
    return out


def chercher(livre):
    auteur = livre['auteurs'][0] if livre['auteurs'] else ''
    resultat = {'id': livre['id'], 'sur': {}, 'oeuvre': {}, 'a_revoir': [], 'sources': []}
    trouvees = []
    for fn in (bnf, google, open_library):
        try:
            trouvees += fn(livre['titre'], auteur)
        except Exception as erreur:  # un catalogue qui flanche n'arrête pas les autres
            resultat.setdefault('erreurs', []).append(f'{fn.__name__} : {erreur}')
    notees = []
    for n in trouvees:
        s = note(livre, n)
        if s['titre'] >= 0.8 and s['auteur'] >= 0.5:
            notees.append((s, n))
    if not notees:
        return resultat
    # L'œuvre : la première parution (Open Library), les sujets (BnF d'abord).
    ol = [n for s, n in notees if n['source'] == 'openlibrary' and n.get('premiere_parution')]
    if ol:
        resultat['oeuvre']['premiere_parution'] = min(n['premiere_parution'] for n in ol)
        resultat['oeuvre']['sujets_ol'] = ol[0]['sujets'][:15]
    rameau = []
    for _s, n in notees:
        if n['source'] == 'bnf':
            rameau += [x for x in n['sujets'] if x not in rameau]
    if rameau:
        resultat['oeuvre']['rameau'] = rameau[:20]
    resultat['sources'] = sorted({n['lien'] for s, n in notees if n.get('lien')})[:6]
    # L'édition.
    editions = [(s, n) for s, n in notees if n['source'] in ('bnf', 'google')]
    langues = langues_du_livre(livre)
    if livre.get('editeur'):
        sures = [(s, n) for s, n in editions if s['editeur'] and s['titre'] >= 0.85 and s['auteur'] >= 0.5]
    else:
        # Sans éditeur sur la fiche : une seule édition de ce titre, de cet auteur, dans cette langue.
        dans_la_langue = [(s, n) for s, n in editions if s['titre'] >= 0.9 and s['auteur'] == 1.0
                          and (not langues or not n['langues'] or set(n['langues']) & langues)]
        distinctes = {(cle_editeur(n['editeur']), n['annee']) for s, n in dans_la_langue}
        sures = dans_la_langue if len(distinctes) == 1 else []
        if len(distinctes) > 1:
            resultat['a_revoir'] = [{'source': n['source'], 'titre': n['titre'], 'editeur': n['editeur'],
                                     'annee': n['annee'], 'pages': n['pages'], 'isbn': n['isbn'][:1], 'lien': n['lien']}
                                    for s, n in dans_la_langue[:5]]
    if sures:
        # La BnF d'abord, puis Google ; une année qui concorde d'abord.
        sures.sort(key=lambda x: (x[1]['source'] != 'bnf', not x[0]['annee']))
        s, n = sures[0]
        sur = {'source': n['source'], 'lien': n['lien']}
        if n['isbn']:
            sur['isbn'] = n['isbn'][0]
        pages = next((m['pages'] for _, m in sures if m['pages']), 0)
        if pages:
            sur['pages'] = pages
        if n['annee'] and not re.search(r'\d{4}', livre.get('annee', '')):
            sur['annee'] = n['annee']
        if not livre.get('editeur') and n.get('editeur'):
            sur['editeur'] = n['editeur']
        if n['langues']:
            sur['langues'] = n['langues']
        resultat['sur'] = sur
    elif not resultat['a_revoir'] and livre.get('editeur'):
        autres = [(s, n) for s, n in editions if s['titre'] >= 0.9 and s['auteur'] == 1.0]
        resultat['a_revoir'] = [{'source': n['source'], 'titre': n['titre'], 'editeur': n['editeur'], 'annee': n['annee'],
                                 'pages': n['pages'], 'isbn': n['isbn'][:1], 'lien': n['lien']} for s, n in autres[:5]]
    return resultat


def nettoyer_titre(titre, auteurs):
    """Le titre sans le nom de l'auteur collé à la fin (« Smolensk - François Malye ») ni les mentions du libraire."""
    t = titre
    for a in auteurs:
        nom = nom_de_famille(a)
        if nom:
            m = re.search(r'\s*[-–—,/]\s*[^-–—,/]*$', t)
            if m and nom in cle(m.group(0)).split():
                t = t[:m.start()]
    return t.strip(' -–—,/') or titre


FIN_CANDIDATES = float('inf')


def noyau(livre):
    """Pour une fiche sans éditeur, ni année, ni pages : toutes les éditions que la BnF et Google connaissent de ce
    titre (et de cet auteur), avec le prix que Google donne, pour reconnaître l'édition vendue à la main."""
    titre = nettoyer_titre(livre['titre'], livre['auteurs'])
    auteur = livre['auteurs'][0] if livre['auteurs'] else ''
    trouvees = []
    if time.monotonic() > FIN_CANDIDATES:
        return livre['id'], None
    for fn, kw in ((sudoc, {'maximum': 15}),):
        try:
            trouvees += fn(titre, auteur, **kw)
            if auteur:
                trouvees += fn(titre, '', **kw)
        except Exception:
            pass
    lignes, vues = [], set()
    fiche = dict(livre, titre=titre)
    for n in trouvees:
        s = note(fiche, n)
        if s['titre'] < 0.6 or (auteur and s['auteur'] == 0.0 and s['titre'] < 0.9):
            continue
        cle_n = (n['source'], cle_editeur(n['editeur']), n['annee'], tuple(n['isbn'][:1]))
        if cle_n in vues:
            continue
        vues.add(cle_n)
        lignes.append(f"{n['source']}|{n['editeur']}|{n['annee']}|{n['pages']}p|{','.join(n['isbn'][:1])}|"
                      f"{n.get('prix', '')}€|{','.join(n['langues'])}|{n['titre'][:70]}|{', '.join(n.get('auteurs', []))[:60]}|{n['lien']}")
    return livre['id'], lignes


def releve_candidates(livres, seulement=None):
    """Les livres sans ISBN parus depuis 1970 (ou sans année), hors revues : leurs éditions candidates au Sudoc, dans
    le journal seulement, celles du même éditeur d'abord, pour retrouver l'ISBN à la main (un quart d'heure au plus)."""
    global FIN_CANDIDATES

    def sans_isbn(x):
        annee = re.search(r'\d{4}', str(x.get('annee') or ''))
        return not x.get('isbn') and x.get('nature') != 'revue' and (not annee or int(annee.group(0)) >= 1970)

    durs = [x for x in livres if sans_isbn(x) and (not seulement or x['id'] in seulement)]
    print(f'CANDIDATES : {len(durs)} livres sans ISBN depuis 1970', flush=True)
    FIN_CANDIDATES = time.monotonic() + (25 if seulement else 15) * 60
    par_id = {x['id']: x for x in durs}
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as ex:
        for ident, candidates in ex.map(noyau, durs):
            if candidates is None:
                print(f'CAND #{ident} : temps écoulé', flush=True)
                continue
            ed = par_id[ident].get('editeur') or ''
            candidates.sort(key=lambda c: not (ed and concorde_editeur(ed, c.split('|')[1])))
            print(f'CAND #{ident} : {len(candidates)} | {ed} | {par_id[ident].get("annee") or ""}', flush=True)
            for c in candidates[:14]:
                print(f'CAND #{ident} || {c}', flush=True)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('livres')
    ap.add_argument('sortie')
    ap.add_argument('--rapport', default='')
    ap.add_argument('--limite', type=int, default=0)
    args = ap.parse_args()
    livres = [x for x in json.load(open(args.livres, encoding='utf-8')) if x.get('titre')]
    temoin = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'candidates.seules')
    if os.path.exists(temoin):
        # Le fichier témoin candidates.seules : le relevé des candidates seul (des livres qu'il nomme par leur
        # identifiant, s'il en nomme). Rien n'est écrit, et la sortie en erreur arrête l'étape avant l'envoi : les
        # notices déjà sur le serveur restent telles quelles.
        releve_candidates(livres, {int(n) for n in re.findall(r'\b\d+\b', open(temoin, encoding='utf-8').read())})
        sys.exit('Relevé des candidates seul (candidates.seules) : aucune notice écrite.')
    if args.limite:
        livres = livres[:args.limite]
    resultats = []
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as ex:
        for i, r in enumerate(ex.map(chercher, livres)):
            resultats.append(r)
            if i % 50 == 0:
                print(f'{i} / {len(livres)}', file=sys.stderr, flush=True)
    json.dump(resultats, open(args.sortie, 'w', encoding='utf-8'), ensure_ascii=False)
    n = len(resultats)
    sur = sum(1 for r in resultats if r['sur'])
    lignes = [
        f'# Les notices : {n} livres', '',
        f'- une édition sûre : {sur} (ISBN : {sum(1 for r in resultats if r["sur"].get("isbn"))}, '
        f'pages : {sum(1 for r in resultats if r["sur"].get("pages"))}, année : {sum(1 for r in resultats if r["sur"].get("annee"))}, '
        f'éditeur : {sum(1 for r in resultats if r["sur"].get("editeur"))})',
        f'- à revoir (plusieurs éditions possibles) : {sum(1 for r in resultats if r["a_revoir"])}',
        f'- l\'œuvre retrouvée : {sum(1 for r in resultats if r["oeuvre"])} (première parution : '
        f'{sum(1 for r in resultats if r["oeuvre"].get("premiere_parution"))}, sujets Rameau : '
        f'{sum(1 for r in resultats if r["oeuvre"].get("rameau"))})',
        f'- rien trouvé : {sum(1 for r in resultats if not r["sur"] and not r["oeuvre"] and not r["a_revoir"])}',
        f'- erreurs de catalogue : {sum(1 for r in resultats if r.get("erreurs"))}',
    ]
    for hote, refus in sorted(REFUS.items()):
        if refus >= REFUS_MAX:
            lignes.append(f'- {hote} a refusé les demandes (quota ?) : laissé de côté en cours de route')
    texte = '\n'.join(lignes) + '\n'
    print(texte)
    releve_candidates(livres)
    if args.rapport:
        open(args.rapport, 'w', encoding='utf-8').write(texte)


if __name__ == '__main__':
    main()
