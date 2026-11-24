"""Les vidéos et les podcasts du centre qui ne sont pas l'enregistrement d'une rencontre, prêts pour l'import
(`wp er medias importer`).

    python3 outils/wix/medias.py <evenements.json> <medias.json> <sortie.json>

- evenements.json et medias.json : ce que lit `outils/wix/pages.py` (les pages des rencontres, les chaînes du centre) ;
  les vidéos rattachées à une rencontre (outils/wix/evenements.py) restent sur la page de la rencontre, et celles
  qu'il faut vérifier attendent qu'on les rattache : ne viennent ici que les vidéos sans rencontre ;
- les épisodes des podcasts : SoundCloud et le flux du podcast (Apple, Podcastics).

La sortie, une liste d'objets : ident, titre, url, genre (video, podcast), serie, date (« AAAA-MM-JJ », approchée pour
YouTube), duree (secondes), description, image, auteurs et sujets (posés en plus de ce que l'import devine).
"""
import json
import re
import sys
from datetime import datetime, timezone
from email.utils import parsedate_to_datetime
import html

sys.path.insert(0, __import__('os').path.dirname(__file__))
import evenements as ev  # noqa: E402

# Ce que l'on sait des séries : leurs étiquettes.
SERIES = {
    'Les podcasts à livre ouvert': {'auteurs': ['Alexandre Soljenitsyne'], 'sujets': ['Goulag et dissidence']},
    'Les Secrets de l’Archipel du Goulag': {'auteurs': ['Alexandre Soljenitsyne'], 'sujets': ['Goulag et dissidence']},
}


def episodes_du_flux(xml):
    titre_serie = html.unescape((re.search(r'<channel>.*?<title>(.*?)</title>', xml, re.S) or [None, ''])[1]).strip()
    titre_serie = ev.typo(re.sub(r'^<!\[CDATA\[(.*)\]\]>$', r'\1', titre_serie)).strip()
    for item in re.findall(r'<item>(.*?)</item>', xml, re.S):
        def champ(nom, item=item):
            m = re.search(rf'<{nom}[^>]*>(.*?)</{nom}>', item, re.S)
            v = m.group(1).strip() if m else ''
            return html.unescape(re.sub(r'^<!\[CDATA\[(.*)\]\]>$', r'\1', v, flags=re.S)).strip()
        son = re.search(r'<enclosure[^>]+url="([^"]+)"', item)
        image = re.search(r'<itunes:image[^>]+href="([^"]+)"', item)
        quand = champ('pubDate')
        duree = champ('itunes:duration')
        secondes = sum(int(x) * 60 ** i for i, x in enumerate(reversed(duree.split(':')))) if re.fullmatch(r'[\d:]+', duree) else None
        yield {
            'ident': 'flux:' + (champ('guid') or (son.group(1) if son else champ('title'))),
            'titre': ev.typo(champ('title')).strip(), 'url': son.group(1) if son else champ('link'), 'genre': 'podcast',
            'serie': titre_serie, 'date': parsedate_to_datetime(quand).strftime('%Y-%m-%d') if quand else '',
            'duree': secondes, 'description': re.sub(r'<[^>]+>', ' ', champ('description')).strip()[:1500],
            'image': image.group(1) if image else '',
        }


def convertir(pages, medias):
    rencontres, _ = ev.convertir(pages)
    _, a_voir, seules = ev.apparier(rencontres, ev.lire_videos(medias), datetime.now(timezone.utc))
    sortie = []
    for v in seules:
        if re.search(r'épisode|episode', v['titre'], re.I) or '/sets/' in v['url']:
            continue  # les épisodes du podcast viennent de SoundCloud ou du flux ; une liste n'est pas une vidéo
        ident = ev.ident_video(v['url'])
        youtube = re.fullmatch(r'[\w-]{11}', ident)
        sortie.append({
            'ident': ('youtube:' if youtube else '') + ident, 'titre': ev.typo(v['titre']).strip(), 'url': v['url'],
            'genre': 'podcast' if 'soundcloud' in v['url'] else 'video', 'serie': '',
            'date': datetime.fromtimestamp(v['date'], timezone.utc).strftime('%Y-%m-%d') if v.get('date') else '',
            'duree': v.get('duree'), 'description': '',
            'image': f'https://i.ytimg.com/vi/{ident}/hqdefault.jpg' if youtube else '',
        })
    # SoundCloud : les épisodes du podcast « à livre ouvert ».
    for source, liste in (medias.get('chaines') or {}).items():
        if 'soundcloud' not in source:
            continue
        for x in liste:
            if 'erreur' in x or not re.search(r'épisode|episode', x.get('title') or '', re.I) or '/sets/' in (x.get('url') or ''):
                continue
            sortie.append({
                'ident': 'soundcloud:' + x['url'].rsplit('/', 1)[-1], 'titre': ev.typo(x['title']).strip(), 'url': x['url'],
                'genre': 'podcast', 'serie': 'Les podcasts à livre ouvert', 'date': '', 'duree': x.get('duration'),
                'description': '', 'image': '',
            })
    for xml in (medias.get('flux') or {}).values():
        if isinstance(xml, str) and '<item>' in xml:
            sortie += list(episodes_du_flux(xml))
    # Les doublons (le même épisode publié deux fois) : le premier.
    vus, uniques = set(), []
    for m in sortie:
        cle = (m['serie'], ' '.join(ev.mots(m['titre'])))
        if cle not in vus:
            vus.add(cle)
            m.update(SERIES.get(m['serie'], {}))
            uniques.append(m)
    return uniques, a_voir


if __name__ == '__main__':
    pages = json.load(open(sys.argv[1]))
    medias = json.load(open(sys.argv[2]))
    relues = {p['url']: p for p in medias.get('evenements', []) if p.get('wix')}
    pages = [relues.get(p['url'], p) for p in pages]
    sortie, a_voir = convertir(pages, medias)
    json.dump(sortie, open(sys.argv[3], 'w'), ensure_ascii=False, indent=1)
    print(len(sortie), 'vidéos et épisodes ;', sum(1 for m in sortie if m['genre'] == 'podcast'), 'épisodes de podcast ;',
          len(a_voir), 'vidéos à rattacher à une rencontre (laissées de côté)')
