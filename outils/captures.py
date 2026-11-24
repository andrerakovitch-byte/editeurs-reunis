"""Compare le rendu des pages de la bêta avec deux versions des feuilles de style du thème : avant et après un changement
de CSS, à plusieurs largeurs d'écran. Lancé par le workflow « Comparer les captures » (.github/workflows/captures.yml).

    python3 outils/captures.py <avant> <après> [--site https://…] [--sortie captures]

<avant> et <après> sont des révisions git (un commit, main~3…). Pour chacune, les feuilles du thème de cette révision
(style.css et assets/css/*.css, dans l'ordre) sont réunies et servies à la place de celles du site : le reste de la page
(le HTML, les images, les polices) vient de la bêta, le même pour les deux rendus. Chaque page est capturée à chaque
largeur, avant puis après, et les deux images comparées pixel par pixel.

Écrit dans <sortie>/ : rapport.md (les écarts, page par page et largeur par largeur) et, pour les plus grands écarts,
une image avant | après | différence (rouge), réduite.
"""
import argparse
import io
import os
import subprocess
import sys

from PIL import Image, ImageChops
from playwright.sync_api import sync_playwright

PAGES = [
    '/',
    '/librairie/',
    '/rayon/litterature/',
    '/livre/dieu-et-letat/',
    '/auteur/alexandre-soljenitsyne/',
    '/centre-culturel/',
    '/centre-culturel/evenements/',
    '/centre-culturel/expositions/',
    '/journal/',
    '/ymca-press/histoire/',
    '/librairie/encyclopedie/age-d-argent/',
    '/sujet/poesie/',
    '/nous-trouver/',
]
LARGEURS = [360, 420, 600, 640, 700, 720, 760, 781, 800, 860, 900, 960, 1024, 1099, 1100, 1180, 1240, 1279, 1280, 1440]
HAUTEUR_MAX = 6000  # Le haut de la page suffit à voir une mise en page ; au-delà, des listes longues.
CALME = '*, *::before, *::after { animation: none !important; transition: none !important; caret-color: transparent !important; }'


def feuilles(revision):
    """Les feuilles du thème à une révision, réunies dans l'ordre de chargement."""
    def montrer(chemin):
        return subprocess.run(['git', 'show', f'{revision}:{chemin}'], capture_output=True, text=True).stdout

    noms = subprocess.run(['git', 'ls-tree', '--name-only', f'{revision}:theme/editeurs-reunis/assets/css/'],
                          capture_output=True, text=True).stdout.split()
    css = montrer('theme/editeurs-reunis/style.css')
    for nom in sorted(n for n in noms if n.endswith('.css')):
        css += '\n' + montrer(f'theme/editeurs-reunis/assets/css/{nom}')
    return css


def cliche(page):
    """Le haut de la page, jusqu'à HAUTEUR_MAX."""
    hauteur = min(HAUTEUR_MAX, page.evaluate('document.documentElement.scrollHeight'))
    largeur = page.viewport_size['width']
    png = page.screenshot(full_page=True, clip={'x': 0, 'y': 0, 'width': largeur, 'height': hauteur})
    return Image.open(io.BytesIO(png)).convert('RGB')


def capturer(page, url, largeur, css_avant, css_apres):
    """La page à cette largeur, une fois chargée : avec les feuilles d'avant, puis celles d'après, glissées à la même
    place (le contenu ne bouge pas entre les deux images)."""
    servi = {'fait': False}

    def router(route):
        chemin = route.request.url.split('?')[0]
        if '/themes/editeurs-reunis/' in chemin and chemin.endswith('.css'):
            # La première feuille du thème reçoit tout ; les suivantes, rien : la place dans la cascade ne change pas.
            corps = '' if servi['fait'] else css_avant
            servi['fait'] = True
            route.fulfill(status=200, content_type='text/css', body=corps)
        else:
            route.continue_()

    page.unroute('**/*')
    page.route('**/*', router)
    page.set_viewport_size({'width': largeur, 'height': 900})
    page.goto(url, wait_until='networkidle', timeout=90000)
    page.add_style_tag(content=CALME)
    page.wait_for_timeout(400)
    avant = cliche(page)
    page.evaluate("""(css) => {
        const liens = [...document.querySelectorAll('link[rel="stylesheet"]')].filter(l => l.href.includes('/themes/editeurs-reunis/'));
        const style = document.createElement('style');
        style.textContent = css;
        liens[0].after(style);
        liens.forEach(l => { l.disabled = true; });
    }""", css_apres)
    page.wait_for_timeout(400)
    return avant, cliche(page)


def ecart(a, b):
    """La part des pixels qui diffèrent, et l'image de la différence."""
    largeur, hauteur = max(a.width, b.width), max(a.height, b.height)
    fond = Image.new('RGB', (largeur, hauteur), 'white')
    a2, b2 = fond.copy(), fond.copy()
    a2.paste(a)
    b2.paste(b)
    diff = ImageChops.difference(a2, b2).convert('L').point(lambda v: 255 if v > 24 else 0)
    n = diff.histogram()[255]
    return n / (largeur * hauteur), a2, b2, diff


def planche(a, b, diff, chemin):
    """Avant | après | différence en rouge, réduits."""
    rouge = Image.new('RGB', diff.size, (220, 0, 0))
    calque = b.copy()
    calque.paste(rouge, mask=diff)
    l, h = a.width, a.height
    p = Image.new('RGB', (l * 3 + 40, h), 'white')
    for i, im in enumerate((a, b, calque)):
        p.paste(im, (i * (l + 20), 0))
    p.thumbnail((2400, 2400))
    p.save(chemin, optimize=True)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('avant')
    ap.add_argument('apres')
    ap.add_argument('--site', default='https://editeursreunis.kinsta.cloud')
    ap.add_argument('--sortie', default='captures')
    ap.add_argument('--seuil', type=float, default=0.001, help='la part de pixels à partir de laquelle un écart compte')
    args = ap.parse_args()
    os.makedirs(args.sortie, exist_ok=True)
    css_avant, css_apres = feuilles(args.avant), feuilles(args.apres)
    if not css_avant.strip() or not css_apres.strip():
        sys.exit('Une des révisions n’a pas de feuille de style.')
    lignes, planches = [], []
    with sync_playwright() as p:
        nav = p.chromium.launch()
        page = nav.new_page(device_scale_factor=1)
        for chemin in PAGES:
            url = args.site + chemin
            for largeur in LARGEURS:
                try:
                    a, b = capturer(page, url, largeur, css_avant, css_apres)
                except Exception as erreur:  # une page qui ne répond pas n'arrête pas les autres
                    lignes.append((chemin, largeur, None, str(erreur)[:120]))
                    continue
                part, a2, b2, diff = ecart(a, b)
                lignes.append((chemin, largeur, part, ''))
                if part >= args.seuil:
                    planches.append((part, chemin, largeur, a2, b2, diff))
        nav.close()
    planches.sort(key=lambda x: -x[0])
    noms = {}
    for _part, chemin, largeur, a2, b2, diff in planches[:24]:
        nom = (chemin.strip('/').replace('/', '_') or 'accueil') + f'-{largeur}.png'
        planche(a2, b2, diff, os.path.join(args.sortie, nom))
        noms[(chemin, largeur)] = nom
    with open(os.path.join(args.sortie, 'rapport.md'), 'w', encoding='utf-8') as f:
        f.write(f'# Captures : {args.avant} → {args.apres}\n\n')
        ecarts = [x for x in lignes if x[2] is not None and x[2] >= args.seuil]
        f.write(f'{len(PAGES)} pages, {len(LARGEURS)} largeurs : {len(ecarts)} écarts sur {len(lignes)} rendus.\n\n')
        f.write('| Page | Largeur | Pixels changés | Planche |\n| --- | --- | --- | --- |\n')
        for chemin, largeur, part, erreur in lignes:
            if erreur:
                f.write(f'| {chemin} | {largeur} | erreur : {erreur} | |\n')
            elif part >= args.seuil:
                f.write(f'| {chemin} | {largeur} | {part:.2%} | {noms.get((chemin, largeur), "")} |\n')
    print(open(os.path.join(args.sortie, 'rapport.md'), encoding='utf-8').read())


if __name__ == '__main__':
    main()
