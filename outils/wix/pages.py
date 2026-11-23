"""Lit des pages publiques de l'ancien site Wix (editeurs-reunis.fr) : son plan (sitemap), puis les pages d'un genre.

Tourne dans un workflow GitHub (`.github/workflows/wix-pages.yml`), le seul endroit d'où le site est joignable :
l'espace de travail de Claude ne l'est pas. Le résultat est déposé sur la branche à part « wix-pages », jamais sur
main.

    python3 outils/wix/pages.py plan                     → plan.json : toutes les adresses du plan, par sous-plan
    python3 outils/wix/pages.py evenements [--brut N]    → evenements.json : chaque page d'événement, ses données
                                                           structurées (JSON-LD, Open Graph) et les données de Wix ;
                                                           avec --brut, le HTML des N premières pages, pour lire leur forme
    python3 outils/wix/pages.py medias [--pages a,b]     → medias.json : les vidéos et les enregistrements que citent
                                                           les pages /videos et /podcast et l'accueil (liens YouTube,
                                                           Vimeo, SoundCloud…), les vidéos des chaînes YouTube citées
                                                           (yt-dlp, la liste seule, sans rien télécharger), le flux des
                                                           podcasts ; avec --pages, ces pages d'événement relues

Une demande par seconde, pas plus.
"""
import json
import os
import re
import sys
import time
import urllib.request
import xml.etree.ElementTree as ET

SITE = 'https://www.editeurs-reunis.fr'
SORTIE = 'sortie'
AGENT = 'Mozilla/5.0 (compatible; editeurs-reunis-migration/1.0; +https://github.com/andrerakovitch-byte/editeurs-reunis)'


def lire(url):
    time.sleep(1)
    req = urllib.request.Request(url, headers={'User-Agent': AGENT, 'Accept-Language': 'fr-FR,fr;q=0.9'})
    with urllib.request.urlopen(req, timeout=40) as r:
        return r.read().decode('utf-8', 'replace')


def plan():
    """Toutes les adresses du plan du site, rangées par sous-plan."""
    racine = ET.fromstring(lire(SITE + '/sitemap.xml'))
    ns = {'s': 'http://www.sitemaps.org/schemas/sitemap/0.9'}
    sous = [e.text for e in racine.findall('s:sitemap/s:loc', ns)] or [SITE + '/sitemap.xml']
    resultat = {}
    for s in sous:
        try:
            arbre = ET.fromstring(lire(s))
            resultat[s] = [e.text for e in arbre.findall('s:url/s:loc', ns)]
        except Exception as erreur:  # un sous-plan illisible n'arrête pas les autres
            resultat[s] = {'erreur': str(erreur)}
    return resultat


def json_ld(html):
    blocs = []
    for m in re.finditer(r'<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>', html, re.S):
        try:
            blocs.append(json.loads(m.group(1)))
        except ValueError:
            pass
    return blocs


def meta(html):
    d = {}
    for m in re.finditer(r'<meta\s+(?:property|name)="([^"]+)"\s+content="([^"]*)"', html):
        d.setdefault(m.group(1), m.group(2))
    t = re.search(r'<title>(.*?)</title>', html, re.S)
    d['title'] = t.group(1).strip() if t else ''
    return d


def donnees_wix(html):
    """Les données de la page que Wix glisse pour le premier affichage (« warmup data »), s'il y en a."""
    m = re.search(r'<script[^>]+id="wix-warmup-data"[^>]*>(.*?)</script>', html, re.S)
    if not m:
        return None
    try:
        return json.loads(m.group(1))
    except ValueError:
        return None


def chercher_evenement(objet, chemin=''):
    """Dans les données de Wix, le premier objet qui a l'air d'un événement (un titre, une date, une description)."""
    if isinstance(objet, dict):
        cles = set(objet)
        if {'title', 'scheduling'} <= cles or {'title', 'dateAndTimeSettings'} <= cles or ({'title', 'about'} <= cles):
            return objet
        for k, v in objet.items():
            r = chercher_evenement(v, chemin + '/' + k)
            if r is not None:
                return r
    elif isinstance(objet, list):
        for i, v in enumerate(objet):
            r = chercher_evenement(v, chemin + f'[{i}]')
            if r is not None:
                return r
    return None


def evenements(brut=0):
    adresses = []
    le_plan = plan()
    json.dump(le_plan, open(os.path.join(SORTIE, 'plan.json'), 'w'), ensure_ascii=False, indent=1)
    for sous, liste in le_plan.items():
        if isinstance(liste, list) and 'event' in sous:  # le sous-plan des événements (event-pages-sitemap.xml)
            adresses += liste
    adresses = list(dict.fromkeys(adresses))
    print(len(adresses), 'pages d’événement dans le plan')
    os.makedirs(os.path.join(SORTIE, 'brut'), exist_ok=True)
    resultat = []
    for i, url in enumerate(adresses):
        try:
            html = lire(url)
        except Exception as erreur:
            resultat.append({'url': url, 'erreur': str(erreur)})
            continue
        if i < brut:
            open(os.path.join(SORTIE, 'brut', f'{i:03d}.html'), 'w').write(html)
        wix = donnees_wix(html)
        resultat.append({'url': url, 'meta': meta(html), 'json_ld': json_ld(html),
                         'wix': chercher_evenement(wix) if wix else None, 'taille': len(html)})
    return resultat


MEDIAS = re.compile(
    r'https?://(?:www\.|m\.|music\.)?(?:youtube\.com|youtu\.be|youtube-nocookie\.com|vimeo\.com|player\.vimeo\.com|'
    r'soundcloud\.com|w\.soundcloud\.com|facebook\.com|fb\.watch|dailymotion\.com|dai\.ly|ausha\.co|podcast\.ausha\.co|'
    r'anchor\.fm|open\.spotify\.com|podcasts\.apple\.com|feeds\.[a-z0-9.-]+|[a-z0-9.-]*\.(?:rss|xml))[^\s"\'<>\\)]*', re.I)


def liens_medias(texte):
    texte = texte.replace('\\/', '/').replace('\\u002F', '/').replace('&amp;', '&')
    return sorted({m.group(0).rstrip('.,;') for m in MEDIAS.finditer(texte)})


def chaine_youtube(url):
    """La liste des vidéos d'une chaîne ou d'une liste de lecture YouTube, par yt-dlp, sans rien télécharger."""
    import subprocess
    cibles = [url]
    if re.search(r'youtube\.com/(?:@|c/|channel/|user/)', url) and '/videos' not in url:
        cibles = [url.rstrip('/') + '/videos', url.rstrip('/') + '/streams']
    elif re.fullmatch(r'https://soundcloud\.com/[^/?#]+/?', url):
        cibles = [url.rstrip('/') + '/tracks', url.rstrip('/') + '/sets']
    videos = []
    for cible in cibles:
        r = subprocess.run(['yt-dlp', '--flat-playlist', '-J', '--extractor-args', 'youtubetab:approximate_date', cible],
                           capture_output=True, text=True, timeout=300)
        if r.returncode != 0:
            videos.append({'erreur': r.stderr[-500:], 'cible': cible})
            continue
        donnees = json.loads(r.stdout)
        for e in donnees.get('entries') or []:
            videos.append({k: e.get(k) for k in ('id', 'title', 'url', 'upload_date', 'timestamp', 'duration', 'description')})
    return videos


def medias(pages=()):
    resultat = {'pages': {}, 'liens': {}, 'chaines': {}, 'flux': {}, 'evenements': []}
    os.makedirs(os.path.join(SORTIE, 'brut'), exist_ok=True)
    for nom in ('videos', 'podcast', ''):
        try:
            html = lire(f'{SITE}/{nom}')
        except Exception as erreur:
            resultat['pages'][nom or 'accueil'] = {'erreur': str(erreur)}
            continue
        open(os.path.join(SORTIE, 'brut', f'{nom or "accueil"}.html'), 'w').write(html)
        wix = donnees_wix(html)
        if wix:
            json.dump(wix, open(os.path.join(SORTIE, 'brut', f'{nom or "accueil"}-wix.json'), 'w'), ensure_ascii=False)
        resultat['pages'][nom or 'accueil'] = {'meta': meta(html), 'taille': len(html)}
        resultat['liens'][nom or 'accueil'] = liens_medias(html)
    tous = sorted({l for ls in resultat['liens'].values() for l in ls})
    for url in tous:
        apple = re.search(r'podcasts\.apple\.com/.*/id(\d+)', url)
        if apple:
            try:  # le flux du podcast, que donne l'annuaire d'Apple
                flux = json.loads(lire('https://itunes.apple.com/lookup?id=' + apple.group(1)))['results'][0]['feedUrl']
                resultat['flux'][flux] = lire(flux)
            except Exception as erreur:
                resultat['flux'][url] = str(erreur)
        elif (re.search(r'youtube\.com/(?:@|c/|channel/|user/|playlist)', url)
                or re.fullmatch(r'https://soundcloud\.com/[^/?#]+/?', url)) and url not in resultat['chaines']:
            try:
                resultat['chaines'][url] = chaine_youtube(url)
            except Exception as erreur:
                resultat['chaines'][url] = [{'erreur': str(erreur)}]
        elif re.search(r'\.(?:rss|xml)(?:$|\?)|feeds\.|/rss', url):
            try:
                resultat['flux'][url] = lire(url)
            except Exception as erreur:
                resultat['flux'][url] = str(erreur)
    for url in pages:
        try:
            html = lire(url)
        except Exception as erreur:
            resultat['evenements'].append({'url': url, 'erreur': str(erreur)})
            continue
        wix = donnees_wix(html)
        resultat['evenements'].append({'url': url, 'meta': meta(html), 'json_ld': json_ld(html),
                                       'wix': chercher_evenement(wix) if wix else None, 'taille': len(html)})
    return resultat


if __name__ == '__main__':
    os.makedirs(SORTIE, exist_ok=True)
    quoi = sys.argv[1] if len(sys.argv) > 1 else 'plan'
    if quoi == 'plan':
        r = plan()
        json.dump(r, open(os.path.join(SORTIE, 'plan.json'), 'w'), ensure_ascii=False, indent=1)
        for s, l in r.items():
            print(s, len(l) if isinstance(l, list) else l)
    elif quoi == 'evenements':
        brut = int(sys.argv[sys.argv.index('--brut') + 1]) if '--brut' in sys.argv else 0
        r = evenements(brut)
        json.dump(r, open(os.path.join(SORTIE, 'evenements.json'), 'w'), ensure_ascii=False, indent=1)
        print(len(r), 'pages lues ;', sum(1 for x in r if x.get('json_ld')), 'avec JSON-LD ;', sum(1 for x in r if x.get('wix')), 'avec les données de Wix')
    elif quoi == 'medias':
        pages = sys.argv[sys.argv.index('--pages') + 1].split(',') if '--pages' in sys.argv else []
        r = medias([p for p in pages if p.strip()])
        json.dump(r, open(os.path.join(SORTIE, 'medias.json'), 'w'), ensure_ascii=False, indent=1)
        for nom, liens in r['liens'].items():
            print(nom, len(liens), 'liens')
        for url, videos in r['chaines'].items():
            print(url, len(videos), 'vidéos')
