"""Les pages à feuilleter d'un livre, tirées de son PDF d'impression.

Pour chaque livre du plan, choisit quelques pages (le début du texte, une double page en regard pour un bilingue, le
sommaire) ou prend celles que le plan désigne, les rend en JPEG légers (en gris quand la page n'a pas de couleur) et
écrit le manifeste que lit « wp er extraits importer ».

    python outils/extraits.py plan.json sortie/

plan.json :
    {"1288": {"pdf": "chaliapine.pdf"},                                 choix automatique
     "1170": {"pdf": "boulgakov.pdf", "pages": [{"p": 4, "legende": "La note de l'éditeur"}, …]},
     "739": {"pdf": "debut.pdf", "pages": [{"p": 7}, {"p": 2, "pdf": "fin.pdf", "legende": "Le sommaire"}]}}

Les numéros de page sont ceux du PDF (1 = la première page du fichier). Un PDF en doubles pages (largeur > hauteur)
donne des doubles pages. Les PDF et les images ne vont jamais dans le dépôt : le dépôt est public.
"""

import json
import re
import sys
from pathlib import Path

HAUTEUR = 1400  # pixels : de quoi lire une page en plein écran, et zoomer un peu.
QUALITE = 76
TITRES_SOMMAIRE = re.compile(r"(table des mati[eè]res|sommaire|содержание|оглавление)", re.I)


def ouvrir(chemin):
    import pymupdf  # noqa: PLC0415 - seulement pour s'en servir

    return pymupdf.open(chemin)


def texte(page):
    return page.get_text() or ""


def langue(t):
    """« ru », « fr » ou « » selon l'alphabet qui domine la page."""
    cyr = len(re.findall("[а-яА-ЯёЁ]", t))
    lat = len(re.findall("[a-zA-Zéèàçù]", t))
    if cyr + lat < 120:
        return ""
    return "ru" if cyr > 2 * lat else ("fr" if lat > 2 * cyr else "")


def titre_de(t):
    """La première ligne qui ressemble à un intertitre (ni numéro, ni titre courant), pour la légende."""
    t = re.sub(r"[\x00-\x08\x0b-\x1f]", "’", t)  # L'apostrophe de certaines polices sort en caractère de contrôle.
    for ligne in t.splitlines()[:6]:
        ligne = ligne.strip()
        if not ligne or re.fullmatch(r"[\d\s]+", ligne) or len(ligne) > 60:
            continue
        if ligne.isupper() or len(ligne.split()) <= 5:
            return ligne[:1] + ligne[1:].lower() if ligne.isupper() else ligne
    return ""


def sommaire(doc):
    """Les pages du sommaire : celle qui porte le titre, et les suivantes qui ont l'air d'en être (trois au plus)."""
    for i in range(doc.page_count):
        if TITRES_SOMMAIRE.search(texte(doc[i])[:300]):
            pages = [i]
            for j in range(i + 1, min(i + 3, doc.page_count)):
                lignes = [x for x in texte(doc[j]).splitlines() if x.strip()]
                if sum(bool(re.search(r"\d+\s*$", x)) for x in lignes) >= 5:
                    pages.append(j)
                else:
                    break
            return pages
    return []


def choisir(doc):
    """Le choix automatique : le début du texte (trois pages), une double page en regard, le sommaire."""
    choix = []
    pleines = [i for i in range(3, doc.page_count) if len(texte(doc[i]).strip()) > 900]
    if pleines:
        debut = pleines[0]
        legende = titre_de(texte(doc[debut])) or "Le début du texte"
        for k, i in enumerate(range(debut, min(debut + 3, doc.page_count))):
            choix.append({"p": i + 1, "legende": legende if k == 0 else ""})
    # Un bilingue : la première double page où le russe et le français se répondent.
    for i in range(3, doc.page_count - 1):
        a, b = langue(texte(doc[i])), langue(texte(doc[i + 1]))
        if {a, b} == {"ru", "fr"}:
            choix.append({"p": i + 1, "double": True, "legende": "Le texte et sa traduction, en regard"})
            break
    toc = sommaire(doc)
    for k, i in enumerate(toc):
        choix.append({"p": i + 1, "legende": "Le sommaire" if k == 0 else ""})
    return choix


def en_couleur(image):
    from PIL import ImageStat  # noqa: PLC0415

    hsv = image.convert("HSV")
    return ImageStat.Stat(hsv.split()[1]).mean[0] > 12


def sans_moitie_blanche(image):
    """Une double page du PDF dont une moitié est vide (le verso blanc d'une page de titre) : l'autre seule."""
    if image.width <= image.height:
        return image
    from PIL import ImageStat  # noqa: PLC0415

    milieu = image.width // 2
    gauche, droite = image.crop((0, 0, milieu, image.height)), image.crop((milieu, 0, image.width, image.height))
    clair = [ImageStat.Stat(m.convert("L")).mean[0] for m in (gauche, droite)]
    if clair[0] > 253 and clair[1] <= 253:
        return droite
    if clair[1] > 253 and clair[0] <= 253:
        return gauche
    return image


def rendre(doc, numero, double=False):
    """L'image d'une page (ou de deux pages côte à côte), à la hauteur voulue."""
    from PIL import Image  # noqa: PLC0415

    pages = [doc[numero - 1]] + ([doc[numero]] if double and numero < doc.page_count else [])
    images = []
    for page in pages:
        # La page coupée (TrimBox) : sans les traits de coupe ni le fond perdu du fichier d'impression.
        cadre = page.trimbox if page.trimbox.height > 0 else page.rect
        zoom = HAUTEUR / cadre.height
        pix = page.get_pixmap(matrix=__import__("pymupdf").Matrix(zoom, zoom), clip=cadre, alpha=False)
        images.append(Image.frombytes("RGB", (pix.width, pix.height), pix.samples))
    if len(images) == 1:
        return sans_moitie_blanche(images[0])
    largeur = sum(i.width for i in images)
    planche = Image.new("RGB", (largeur, HAUTEUR), "white")
    x = 0
    for i in images:
        planche.paste(i, (x, 0))
        x += i.width
    return planche


def extraire(plan, dossier_pdf, sortie):
    manifeste = {}
    for livre, reglage in plan.items():
        doc = ouvrir(dossier_pdf / reglage["pdf"])
        autres = {}
        choix = reglage.get("pages") or choisir(doc)
        rep = sortie / str(livre)
        rep.mkdir(parents=True, exist_ok=True)
        pages = []
        for n, c in enumerate(choix, 1):
            source = doc
            if c.get("pdf"):  # Une page d'un autre fichier du même livre (le sommaire d'un PDF découpé).
                source = autres.setdefault(c["pdf"], ouvrir(dossier_pdf / c["pdf"]))
            image = rendre(source, int(c["p"]), bool(c.get("double")))
            if not en_couleur(image):
                image = image.convert("L")
            nom = f"{n:02d}.jpg"
            image.save(rep / nom, "JPEG", quality=QUALITE, optimize=True, progressive=True)
            pages.append({"fichier": nom, "legende": c.get("legende", ""), "page": int(c["p"])})
        manifeste[str(livre)] = {"pages": pages}
        print(livre, reglage["pdf"], [p["page"] for p in pages])
    (sortie / "manifeste.json").write_text(json.dumps(manifeste, ensure_ascii=False, indent=1), encoding="utf-8")
    return manifeste


if __name__ == "__main__":
    plan_json = Path(sys.argv[1])
    extraire(json.loads(plan_json.read_text(encoding="utf-8")), plan_json.parent, Path(sys.argv[2]))
