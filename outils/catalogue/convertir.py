"""Le catalogue de la boutique Wix (export « catalog_products.csv ») converti pour l'importateur de WooCommerce.

    python3 outils/catalogue/convertir.py catalog_products.csv   (depuis le dossier où écrire les fichiers)

Écrit, à côté :
  import-woocommerce.csv   le fichier à téléverser dans Produits → Importer (sans le prix d'achat de Wix)
  echantillon.md           dix livres convertis, pour relire avant d'importer
  a-revoir.md              ce que la conversion n'a pas su trancher
  complement-pages-format.csv   les pages et le format seuls, à importer en « mettant à jour » un catalogue déjà là

Le titre et les auteurs viennent du nom Wix (« Titre - Auteur ») ; le traducteur, l'éditeur, la collection et l'année,
des premières lignes de la description, seulement quand elles sont nettes (la description reste entière) ; les
rayons, la langue et les mentions, des catégories et des rubans Wix. Chaque livre reçoit une référence stable,
WIX- et le début de son identifiant Wix : réimporter met à jour, sans doublon.
"""
import csv
import html
import re
import sys
import unicodedata
import collections
import random

SOURCE = sys.argv[1] if len(sys.argv) > 1 else 'wix.csv'
MEDIA = 'https://static.wixstatic.com/media/'

# --- la clé de comparaison, comme er_cle_recherche() de l'extension ----------------------------------------------

CYR = {'щ': 'shch', 'ж': 'zh', 'х': 'kh', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'ю': 'yu', 'я': 'ya', 'ё': 'e', 'й': 'i',
       'ы': 'y', 'э': 'e', 'ь': '', 'ъ': '', 'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'з': 'z',
       'и': 'i', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
       'ф': 'f', 'і': 'i', 'ї': 'i', 'є': 'e', 'ґ': 'g'}


def cle(t):
    t = (t or '').lower()
    t = ''.join(CYR.get(c, c) for c in t)
    t = unicodedata.normalize('NFKD', t).encode('ascii', 'ignore').decode()
    t = re.sub(r'[^a-z0-9]+', ' ', t)
    for a, b in (('shch', 'ch'), ('chtch', 'ch'), ('tch', 'ch'), ('sch', 'ch'), ('sh', 'ch'), ('zh', 'j'), ('kh', 'h'),
                 ('ph', 'f'), ('x', 'ks'), ('w', 'v'), ('qu', 'k'), ('ou', 'u'), ('y', 'i')):
        t = t.replace(a, b)
    t = re.sub(r'gu(?=[ei])', 'g', t).replace('ie', 'e').replace('ii', 'i')
    t = re.sub(r'([a-z])\1+', r'\1', t)
    t = re.sub(r'e\b', '', t)
    return re.sub(r'\s+', ' ', t).strip()


# --- les catégories Wix --------------------------------------------------------------------------------------------

LANGUES = {'français': ['francais'], 'russe': ['russe'], 'bilingue': ['francais', 'russe']}

RAYONS = {  # catégorie Wix → rayons (slugs de semer.php)
    'romans': ['romans'], 'nouvelles-récits': ['nouvelles-et-recits'], 'récits-nouvelles': ['nouvelles-et-recits'],
    'prose': ['nouvelles-et-recits'], 'poésie': ['poesie'], 'théâtre': ['theatre'], 'mémoires': ['memoires'],
    'souvenirs': ['memoires'], 'récit-autobiographique': ['memoires'], 'témoignage': ['memoires'],
    'correspondance': ['memoires'], 'journal': ['memoires'], 'récit-de-voyage': ['recit-de-voyage'],
    'essai': ['essais'], 'эссе-essais': ['essais'], 'critique-littéraire': ['essais'],
    'histoire': ['histoire'], 'goulag': ['goulag'], 'emigration-russe': ['emigration-russe'],
    'biographies': ['biographies'], 'ukraine': ['russie-aujourdhui'], 'actualité': ['russie-aujourdhui'],
    'géopolitique': ['russie-aujourdhui'], 'poutine': ['russie-aujourdhui'], 'cuisine-russe': ['civilisation'],
    'мемориал': ['urss'],
    'théologie-orthodoxe': ['theologie-orthodoxe'], 'orthodoxie': ['theologie-orthodoxe'],
    'catéchèse-orthodoxe': ['theologie-orthodoxe'], 'spiritualité': ['spiritualite-et-priere'], 'icônes': ['icones'],
    'revue-le-messager-orthodoxe': ['pensee-et-religion'], 'вестник-рхд': ['pensee-et-religion'],
    'philosophie': ['philosophie-russe'],
    'art': ['arts'], 'cinéma': ['cinema'], 'musique': ['musique'], 'danse': ['theatre-et-ballet'],
    'photographies': ['photographie'], 'bande-dessinée': ['arts'],
    'jeunesse-contes': ['jeunesse'], 'сказки-картинки': ['jeunesse'],
    'manuels': ['apprendre-le-russe'], 'dictionnaire': ['dictionnaires'],
    'étiquettes-clavier-cyrillique': ['apprendre-le-russe'], 'путеводитель': ['guides-de-voyage'],
}
GENRES_LITTERAIRES = {'romans', 'nouvelles-et-recits', 'poesie', 'theatre'}
EDITEURS_CAT = {'éditions-ymca-press': 'YMCA-Press', 'editions-ymca-press': 'YMCA-Press',
                'editions-des-syrtes': 'Éditions des Syrtes', 'ginkgoéditeur': 'Ginkgo éditeur',
                'sandermoen-publishing': 'Sandermoen Publishing', 'masha-publishing': 'Masha Publishing',
                'noir-sur-blanc-éditions': 'Noir sur Blanc'}
MENTIONS_CAT = {'bouquinisterie': 'occasion'}
MENTIONS_RUBAN = {'nouveauté': 'nouveaute', 'nouveauté ymca-press': 'nouveaute', 'новинка': 'nouveaute',
                  'nouvelle édition': 'nouvelle-edition', 'nouvelle traduction': 'nouvelle-traduction',
                  'rare': 'tres-rare', 'très rare': 'tres-rare'}

# Les époques de la littérature, d'après l'auteur (clé de comparaison du nom de famille) ; seulement pour les romans,
# les nouvelles, la poésie et le théâtre.
EPOQUES = {
    'classiques': 'puchkin pouchkin lermontov gogol turgenev tourguenev dostoevski dostoievski dostoievsk tolstoi '
                  'chekhov chehov tchehov goncharov gontcharov ostrovski leskov tiutchev tioutchev derjavin '
                  'lomonosov griboedov krilov saltikov kuprin kouprin karamzin',
    'age-d-argent': 'blok akhmatova ahmatova mandelstam mandelchtam tsvetaeva tsvetaieva cvetaeva beli biely bieli '
                    'gumilev goumilev andreev andreiev balmont hodasevich hodassevich kuzmin kouzmin volochin '
                    'voloshin esenin essenin maiakovski maiakovsk annenski sologub brusov brioussov ivanov '
                    'kliuev kliouiev hlebnikov gippius hippius merejkovski merejkowski rozanov',
    'periode-sovietique': 'bulgakov boulgakov platonov pasternak chalamov shalamov grossman zamiatin zamiatine '
                          'pilniak babel harms kharms ilf petrov zochenko zoshchenko solzhenitsin soljenitsin '
                          'soljenitsyn solgenitsin dovlatov brodski brodsky chklovski shklovski trifonov '
                          'bek kazakov tvardovski sinyavski siniavski',
    'emigration': 'bunin bounine zaitsev zaitsev teffi gazdanov poplavski nabokov berberova remizov chmeliov '
                  'shmelev osorgin ossorguine odarchenko',
    'contemporains': 'ulitskaia oulitskaia ulitskaya akunin akounine vodolazkin vodolazkine filipenko gelasimov '
                     'guelassimov iakhina iahina yakhina lebedev stepanova chichkin shishkin prilepin prilepine '
                     'sedakova kurkov kourkov alexievich alexeievitch limonov sorokin pelevin',
}
EPOQUE_PAR_NOM = {}
for epoque, noms in EPOQUES.items():
    for n in noms.split():
        EPOQUE_PAR_NOM[cle(n)] = epoque


def epoque_de(auteur):
    mots = cle(auteur).split()
    for m in reversed(mots):
        if m in EPOQUE_PAR_NOM:
            return EPOQUE_PAR_NOM[m]
    return None


# --- le nom : titre et auteurs -------------------------------------------------------------------------------------

def tout_en_capitales(t):
    lettres = [c for c in t if c.isalpha()]
    return len(lettres) > 6 and sum(c.isupper() for c in lettres) / len(lettres) > 0.8


def phrase(t):
    t = t.lower()
    return t[:1].upper() + t[1:]


def revue(nom):
    m = re.search(r'messager\s+orthodoxe\s*n\s*°\s*(.+)$', nom, re.I)
    if m:
        return 'Le Messager orthodoxe, n° ' + re.sub(r'\s*[-/]\s*', '-', m.group(1).strip()), 'Le Messager orthodoxe'
    m = re.search(r'вестник\s+русского\s+христианского\s+движения\s*№?\s*(\d+)', nom, re.I)
    if m:
        return 'Вестник Русского христианского движения, № ' + m.group(1), 'Вестник РХД'
    m = re.search(r'православная\s+мысль\s+выпуск\s+(\S+)', nom, re.I)
    if m:
        return 'Православная мысль, выпуск ' + m.group(1), 'Православная мысль'
    return None


PARTICULES = {'de', 'du', 'des', 'la', 'le', 'von', 'van', 'et', 'd’', "d'"}
SIGLES = {'urss', 'ymca', 'ymca-press', 'rsch', 'рхд', 'ссср', 'xxe', 'xixe', 'xxie'}


def ressemble_a_un_nom(t, mots_max=5):
    t = t.strip()
    mots = t.split()
    if not mots or len(mots) > mots_max or re.search(r'\d|\s[-–—]\s', t):
        return False
    if tout_en_capitales(t) and len(mots) > 3:
        return False
    if re.match(r'(père|mère|saint|sainte|monseigneur|mgr|prot\.|прот\.|мать|отец|свят)', t, re.I):
        return True
    return all(m[0].isupper() or m.lower() in PARTICULES for m in mots) and any(m[0].isupper() for m in mots)


def casse_nom(a):
    if not tout_en_capitales(a.replace('.', 'xxxxxxx')) and not a.isupper():
        return a
    return ' '.join(m[:1] + m[1:].lower() if not re.fullmatch(r'(?:\w\.)+', m) else m for m in a.split())


CORRECTIONS = {'Gouzel Lakhina': 'Gouzel Iakhina', 'Métopolite Antoine Bloom': 'Métropolite Antoine Bloom',
               'Schmemann Alexandre': 'Alexandre Schmemann', 'Nicolas ROSS': 'Nicolas Ross', 'Михаил Зыгарь.': 'Михаил Зыгарь'}


def auteurs_de(partie):
    return [CORRECTIONS.get(a.strip(), casse_nom(a.strip())) for a in re.split(r',\s+|\s+[–—-]\s+|\s*/\s*', partie) if a.strip()]


def decouper(nom):
    """Le nom Wix → (titre, [auteurs], douteux, collection d'une revue)."""
    nom = re.sub(r'\s+', ' ', nom.replace('\xa0', ' ')).strip()
    r = revue(nom)
    if r:
        return r[0], [], False, r[1]
    titre, auteurs, douteux = nom, [], True
    espaces = [m for m in re.finditer(r'\s[-–—]\s', nom)]
    for m in reversed(espaces):  # « Titre - Auteur » : le dernier tiret entre deux espaces
        if ressemble_a_un_nom(nom[m.end():]):
            titre, auteurs, douteux = nom[:m.start()].strip(), auteurs_de(nom[m.end():]), False
            break
    else:
        for m in re.finditer(r'(?<=\S)\s?[-–—]\s?(?=\S)', nom):  # « Titre -Auteur », « Titre-Auteur »
            gauche, droite = nom[:m.start()].strip(), nom[m.end():].strip()
            if len(gauche.split()) >= 2 and ressemble_a_un_nom(droite, 4) and len(droite.split()) >= 2:
                titre, auteurs = gauche, auteurs_de(droite)
                break
            if ressemble_a_un_nom(gauche, 3) and len(gauche.split()) >= 2 and not ressemble_a_un_nom(droite):
                titre, auteurs = droite, auteurs_de(gauche)  # « Auteur-Titre »
                break
        else:
            m = re.match(r'^(.*[?!"»])\s+((?:[A-ZÉА-ЯЁ][\w.’-]*\s?){2,3})$', nom)  # « Titre ? Auteur »
            if m and ressemble_a_un_nom(m.group(2)):
                titre, auteurs = m.group(1).strip(' "'), auteurs_de(m.group(2))
    if tout_en_capitales(titre):
        titre = ' '.join(w.upper() if w.lower().strip('.,') in SIGLES else w for w in phrase(titre).split())
        douteux = True
    titre = titre.replace("'", '’')
    return titre, auteurs, douteux, ''


# --- la description ------------------------------------------------------------------------------------------------

def nettoyer(h):
    h = h.replace('\xa0', ' ').replace('&nbsp;', ' ')
    h = re.sub(r'<span style="[^"]*font-weight:\s*(?:700|bold)[^"]*">(.*?)</span>', r'<strong>\1</strong>', h, flags=re.S)
    h = re.sub(r'<span style="[^"]*font-style:\s*italic[^"]*">(.*?)</span>', r'<em>\1</em>', h, flags=re.S)
    h = re.sub(r'</?span[^>]*>', '', h)
    h = re.sub(r'\s(?:style|class|dir)="[^"]*"', '', h)
    h = re.sub(r' {2,}', ' ', h)
    h = re.sub(r'\s+([;:!?»])', ' \\1', h)
    h = re.sub(r'«\s+', '« ', h)
    h = re.sub(r'<(p|strong|em)>\s*</\1>', '', h)
    h = re.sub(r'(<br\s*/?>\s*)+</p>', '</p>', h)
    h = re.sub(r'<p>\s*(<br\s*/?>\s*)*</p>', '', h)
    return h.strip()


def lignes(h):
    h = re.sub(r'<br\s*/?>', '\n', h)
    h = re.sub(r'</p>', '\n', h)
    t = html.unescape(re.sub(r'<[^>]+>', '', h)).replace('\xa0', ' ')
    return [re.sub(r'\s+', ' ', l).strip() for l in t.split('\n') if l.strip()]


VILLES = {'paris', 'париж', 'москва', 'нью-йорк', 'new york', 'moscou', 'париж – нью-йорк – москва',
          'paris – new york – moscou', 'париж - нью-йорк - москва'}
EDITEURS_CANON = {
    cle('Editions des Syrtes'): 'Éditions des Syrtes', cle('Éditions des Syrtes'): 'Éditions des Syrtes',
    cle('Ginkgoéditions'): 'Ginkgo éditeur', cle('Ginkgo éditions'): 'Ginkgo éditeur', cle('Ginkgo éditeur'): 'Ginkgo éditeur',
    cle('YMCA-Press'): 'YMCA-Press', cle('YMCA Press'): 'YMCA-Press', cle('ИМКА-Пресс'): 'YMCA-Press',
    cle('Русский Путь / YMCA-Press'): 'Русский путь / YMCA-Press', cle('YMCA-Press / Русский Путь'): 'Русский путь / YMCA-Press',
    cle('Русский путь'): 'Русский путь', cle('Gallimard'): 'Gallimard', cle('Gallimard NRF'): 'Gallimard',
    cle('Editions du Cerf'): 'Éditions du Cerf', cle('Cerf'): 'Éditions du Cerf',
    cle('Editions Mesures'): 'Éditions Mesures', cle('Robert Laffont'): 'Robert Laffont',
    cle('Editions du Panthéon'): 'Éditions du Panthéon', cle('Noir sur Blanc'): 'Noir sur Blanc',
    cle('Actes Sud'): 'Actes Sud', cle('Verdier'): 'Verdier', cle('Flammarion'): 'Flammarion',
    cle('"Thésaurus" Actes Sud'): 'Actes Sud', cle('édition YMCA-PRESS'): 'YMCA-Press',
    cle("avec la collaboration d'YMCA-Press"): 'YMCA-Press', cle('Oeil/Ymca-Press'): 'L’Œil / YMCA-Press',
    cle('Gingko éditeurs'): 'Ginkgo éditeur', cle('Macha publishing'): 'Masha Publishing',
    cle('Editions Sillage'): 'Éditions Sillage', cle('Editions Siilage'): 'Éditions Sillage',
    cle('Edtions ROD'): 'Éditions ROD', cle('Mame'): 'Éditions Mame', cle('Éditions Mame'): 'Éditions Mame',
    cle('«Захаров»'): 'Захаров', cle(': Эксмо'): 'Эксмо', cle('réedition Apostolia'): 'Apostolia',
    cle('Fraternité Orthodoxe en Europe ccidentale'): 'Fraternité orthodoxe en Europe occidentale',
    cle('Librarie des Cinq Continents'): 'Librairie des Cinq Continents',
    cle("Institut d'Etudes Slaves"): 'Institut d’études slaves', cle("L'école des Loisirs"): 'L’École des loisirs',
    cle('Editions de la Présentation'): 'Éditions de la Présentation', cle('Editions Vétché'): 'Éditions Vétché',
    cle('Editions Infoli'): 'Éditions Infoli', cle('Philippe Rey éditions'): 'Philippe Rey',
    cle('Harpo & éditions'): 'Harpo &', cle('Harpo &'): 'Harpo &', cle('La Barque éditions'): 'La Barque',
    cle('Éditions La Barque'): 'La Barque', cle('Les Éditions du Cerf'): 'Éditions du Cerf', cle('DDB'): 'Desclée de Brouwer',
    cle('Robert Laffont Pavillons Poche'): 'Robert Laffont',
}
EDITEURS_RUSSES = {cle(e) for e in ('Захаров', 'Эксмо', 'Нигма', 'АСТ', 'Азбука', 'Русский путь', 'Посев', 'ИМКА-Пресс')}


def canon_editeur(e):
    e = re.sub(r'^[ÉEé]diteur\s*:?\s*', '', e.strip(' .,;:'))  # « Éditeur Natania » → « Natania »
    e = EDITEURS_CANON.get(cle(e), e)
    return re.sub(r'^Editions\b', 'Éditions', e)


NOM = r"(?<![\w-])(?:[A-ZÉÈÂÎ]\.(?:-[A-ZÉÈÂÎ]\.)?|[A-ZÉÈÂÎ][\w’'-]+)"
PAS_UN_NOM = re.compile(r"(Collection|Éditions?|Editions?|Gallimard|Folio|Préface|Édition|Le|La|Les|L['’]|Ces|Pour|Vie|Extraits|Éloge|"
                        r"France|Russie|Russe|Autriche|LOCAL|Frères)\b")


def traducteur(tete):
    """Le traducteur (ou, à défaut, le préfacier) nommé dans une phrase « Traduit du russe par… »."""
    for clause in re.split(r'¶|;', tete):
        m = re.search(r'\b(?:tradu\w*|trad\.)', clause, re.I)
        if not m or re.search(r'traduit en |se traduire|traduites? en |nouvelle traduction du', clause, re.I):
            continue
        n = re.search(NOM + r'(?:\s+(?:et\s+|de\s+|von\s+)?' + NOM + r'){1,5}', clause[m.end():])
        if n and n.start() <= 45 and len(n.group(0)) < 60 and not PAS_UN_NOM.match(n.group(0)):
            return n.group(0).strip(' .,')
    return ''


def details(L):
    """Les premières lignes de la description → traducteur, éditeur, collection, année (vides si rien de net)."""
    d = {'traducteur': '', 'editeur': '', 'collection': '', 'annee': '', 'pages': '', 'format': ''}
    tete = ' ¶ '.join(L[:4])
    d['traducteur'] = traducteur(tete)
    m = re.search(r'[Cc]ollection\s+(?:«\s*)?([^,;¶»]+?)(?:\s*»)?(?=\s*[,;¶]|\s*\(n|$)', tete) or re.search(r'Серия\s+«?([^,;¶»]+)»?', tete)
    if m:
        d['collection'] = m.group(1).strip(' .')
    for l in L[:4]:
        for seg in re.finditer(r'(?:^|[.;,¶(])\s*([^.;,¶()]{2,60}?)\s*,\s*((?:19|20)\d\d)\b', l):
            editeur, annee = seg.group(1).strip(), seg.group(2)
            if cle(editeur) in {cle(v) for v in VILLES} or re.match(r'(tradu|trad\b|préface|collection|серия|том|t\d)', editeur, re.I):
                d['annee'] = d['annee'] or annee
                continue
            if re.search(r'\d', editeur) or len(editeur.split()) > 6 or editeur[:1].islower() \
                    or re.search(r'tradu|introduction|фотограф|histoire du|^orthodoxie$|^spiritualité$', editeur, re.I) \
                    or (re.search('[а-яА-Я]', editeur) and cle(canon_editeur(editeur)) not in EDITEURS_RUSSES
                        and canon_editeur(editeur) not in EDITEURS_CANON.values()):
                continue
            d['editeur'], d['annee'] = canon_editeur(editeur), annee
            break
        if d['editeur']:
            break
    m = re.search(r'(?<![\d-])(\d{2,4})\s*(?:p\.|pages\b|pp\.|стр\b\.?|с\.)', tete)  # « 228 p. », « 420 стр. »
    if m and 8 <= int(m.group(1)) <= 3000:
        d['pages'] = m.group(1)
    for motif, nom in ((r'\bpoche\b', 'Poche'), (r'\bbroché\b', 'Broché'), (r'\brelié\b|переплет|переплёт', 'Relié')):
        if re.search(motif, tete, re.I):
            d['format'] = nom
            break
    if not d['annee']:
        m = re.search(r'(?:^|[\s,;(])((?:18|19|20)\d\d)\s*(?:,|г\.)?\s*\d+\s*(?:p\.|pages|стр|с\.)', tete)
        if m:
            d['annee'] = m.group(1)
    return d


def grammes(kg):
    """Le poids Wix, en kilos → en grammes, l'unité de la boutique (semer.php)."""
    try:
        return str(round(float(kg.strip().replace(',', '.')) * 1000)) if kg.strip() else ''
    except ValueError:
        return ''


# --- la conversion -------------------------------------------------------------------------------------------------

lignes_wix = list(csv.DictReader(open(SOURCE, newline='', encoding='utf-8-sig')))
images = collections.defaultdict(list)
for l in lignes_wix:
    if l['fieldType'] == 'MEDIA' and l['media'].strip():
        images[l['handle']].append(MEDIA + l['media'].strip())

SEMES = {  # les livres d'exemple de semer.php, retrouvés par la clé de leur titre
    'convoi-pour-samarcande': 'Convoi pour Samarcande', 'scenarii-zviaguintsev': 'Сценарии кинофильмов Андрея Звягинцева',
    'larchipel-du-goulag-cinquante-ans-apres': 'L’Archipel du Goulag, cinquante ans après', 'put-filma': 'Путь фильма',
    'tchevengour': 'Tchevengour', 'le-docteur-jivago': 'Le Docteur Jivago',
    'voix-et-visages-du-goulag': 'Voix et visages du Goulag', 'les-douze': 'Les Douze',
    'ekaterina-ivanovna': 'Ekatérina Ivanovna, suivi de Requiem', 'les-enfants-de-la-volga': 'Les Enfants de la Volga',
    'poesies-choisies-goumilev': 'Poésies choisies', 'russkaia-poezia': 'Русская поэзия',
    'dostoievski-zweig': 'Dostoïevski', 'moussorgski-calvocoressi': 'Modeste Moussorgski',
    'notes-dun-medecin': 'Notes d’un médecin',
}
SEMES_PAR_CLE = {cle(t): s for s, t in SEMES.items()}
AUTEUR_SEME = {'convoi-pour-samarcande': 'iakhina', 'tchevengour': 'platonov', 'le-docteur-jivago': 'pasternak',
               'les-douze': 'blok', 'ekaterina-ivanovna': 'andreev', 'les-enfants-de-la-volga': 'iakhina',
               'poesies-choisies-goumilev': 'gumilev', 'dostoievski-zweig': 'zveig', 'moussorgski-calvocoressi': 'calvocoressi',
               'notes-dun-medecin': 'veressaev', 'larchipel-du-goulag-cinquante-ans-apres': '', 'put-filma': '',
               'scenarii-zviaguintsev': '', 'voix-et-visages-du-goulag': '', 'russkaia-poezia': ''}
pris = set()


def seme(titre, auteurs):
    slug = SEMES_PAR_CLE.get(cle(titre), '')
    if not slug or slug in pris:
        return ''
    nom = cle(AUTEUR_SEME.get(slug, ''))
    if nom and not any(nom[:5] in cle(a) for a in auteurs):
        return ''
    pris.add(slug)
    return slug

produits, revoir = [], collections.defaultdict(list)
for l in lignes_wix:
    if l['fieldType'] != 'PRODUCT':
        continue
    titre, auteurs, douteux, collection_revue = decouper(l['name'])
    L = lignes(l['plainDescription'])
    det = details(L)
    if collection_revue:
        det['collection'] = collection_revue
    cats = [c.strip() for c in l['categorySlugs'].split(';') if c.strip()]
    langues = sorted({x for c in cats for x in LANGUES.get(c, [])})
    if not langues:
        langues = ['russe'] if re.search('[а-яА-Я]', titre) else ['francais']
        revoir['langue devinée'].append(f'{titre} → {langues[0]}')
    rayons = []
    for c in cats:
        for r in RAYONS.get(c, []):
            if r not in rayons:
                rayons.append(r)
    if set(rayons) & GENRES_LITTERAIRES and auteurs:
        ep = epoque_de(auteurs[0])
        if ep:
            rayons.append(ep)
    if not rayons:
        revoir['sans rayon'].append(f'{titre} ({", ".join(cats) or "aucune catégorie"})')
    if not det['editeur']:
        for c in cats:
            if c in EDITEURS_CAT:
                det['editeur'] = EDITEURS_CAT[c]
                break
    mentions = sorted({MENTIONS_CAT[c] for c in cats if c in MENTIONS_CAT}
                      | ({MENTIONS_RUBAN[l['ribbon'].strip().lower()]} if l['ribbon'].strip().lower() in MENTIONS_RUBAN else set()))
    if l['ribbon'].strip() and l['ribbon'].strip().lower() not in MENTIONS_RUBAN:
        revoir['ruban non repris'].append(f'{titre} : « {l["ribbon"].strip()} »')
    prix, barre = l['price'].strip(), l['strikethroughPrice'].strip()
    stock = l['inventory'].strip()
    sku = 'WIX-' + l['handle'].replace('Product_', '')[:8].upper()
    slug = seme(titre, auteurs)
    if douteux:
        revoir['titre à vérifier'].append(f'{l["name"]!s} → {titre}' + (f' / {", ".join(auteurs)}' if auteurs else ' / sans auteur'))
    produits.append({
        'SKU': sku, 'Type': 'simple', 'Name': titre, 'Slug': slug,
        'Published': '1' if l['visible'] == 'true' else '-1', 'Visibility in catalog': 'visible',
        'Short description': '', 'Description': nettoyer(l['plainDescription']),
        'Regular price': barre or prix, 'Sale price': prix if barre else '',
        'In stock?': '0' if stock == 'OUT_OF_STOCK' or stock == '0' else '1',
        'Stock': stock if stock.isdigit() else '',  # une quantité fait suivre le stock
        'Weight (g)': grammes(l['weight']), 'Images': ', '.join(images.get(l['handle'], [])),
        'Auteurs': ' | '.join(auteurs), 'Langues': ' | '.join(langues), 'Rayons': ' | '.join(rayons),
        'Mentions': ' | '.join(mentions),
        'Meta: er_traducteur': det['traducteur'], 'Meta: er_editeur': det['editeur'],
        'Meta: er_collection': det['collection'], 'Meta: er_annee': det['annee'],
        'Meta: er_pages': det['pages'], 'Meta: er_format': det['format'],
    })

# Les graphies d'un même auteur : la plus fréquente l'emporte.
compte = collections.Counter(a for p in produits for a in p['Auteurs'].split(' | ') if a)
par_cle = collections.defaultdict(list)
for a in compte:
    par_cle[cle(a)].append(a)
retenu = {}
for formes in par_cle.values():
    # La graphie latine d'abord (le français est la langue du site), puis la plus fréquente, puis la translittération
    # française (ï, é, ou, -ski plutôt que w ou y).
    meilleure = max(formes, key=lambda a: (not re.search('[а-яА-Я]', a), compte[a],
                                           sum(c in 'ïëéèêàâôûç' for c in a) - a.count('w') - a.count('y')))
    for f in formes:
        retenu[f] = meilleure
    if len(formes) > 1:
        revoir['graphies d’un même auteur réunies'].append(' = '.join(sorted(formes)) + f' → {meilleure}')
for p in produits:
    p['Auteurs'] = ' | '.join(dict.fromkeys(retenu.get(a, a) for a in p['Auteurs'].split(' | ') if a))
# Les mêmes noms de famille sous deux prénoms ou initiales : à vérifier à la main.
par_nom = collections.defaultdict(set)
for a in set(retenu.values()):
    par_nom[cle(a).split()[-1] if cle(a) else ''].add(a)
for nom, formes in sorted(par_nom.items()):
    if nom and len(formes) > 1:
        revoir['même nom de famille, deux graphies'].append(' / '.join(sorted(formes)))

for slug, t in SEMES.items():
    if not any(p['Slug'] == slug for p in produits):
        revoir['livre d’exemple sans équivalent Wix'].append(f'{t} ({slug})')

COLONNES = list(produits[0].keys())
with open('import-woocommerce.csv', 'w', newline='', encoding='utf-8') as f:
    w = csv.DictWriter(f, fieldnames=COLONNES)
    w.writeheader()
    w.writerows(produits)

with open('complement-pages-format.csv', 'w', newline='', encoding='utf-8') as f:
    w = csv.writer(f)
    w.writerow(['SKU', 'Meta: er_pages', 'Meta: er_format'])
    for p in produits:
        if p['Meta: er_pages'] or p['Meta: er_format']:
            w.writerow([p['SKU'], p['Meta: er_pages'], p['Meta: er_format']])

# Les éditeurs trouvés, pour relecture.
editeurs = collections.Counter(p['Meta: er_editeur'] for p in produits if p['Meta: er_editeur'])

with open('a-revoir.md', 'w', encoding='utf-8') as f:
    f.write(f'# Le catalogue Wix : ce qui est à revoir\n\n{len(produits)} livres convertis.\n\n')
    f.write('## Les champs remplis\n\n')
    for k in ('Auteurs', 'Meta: er_traducteur', 'Meta: er_editeur', 'Meta: er_collection', 'Meta: er_annee', 'Meta: er_pages', 'Meta: er_format', 'Rayons', 'Images', 'Slug'):
        f.write(f'- {k} : {sum(1 for p in produits if p[k])}\n')
    f.write('\n## Les éditeurs trouvés\n\n' + ''.join(f'- {e} ({n})\n' for e, n in editeurs.most_common()) + '\n')
    for titre, items in revoir.items():
        f.write(f'## {titre[:1].upper() + titre[1:]} ({len(items)})\n\n' + ''.join(f'- {i}\n' for i in items) + '\n')

random.seed(11)
with open('echantillon.md', 'w', encoding='utf-8') as f:
    for p in random.sample(produits, 10):
        f.write(f"### {p['Name']}\n- Auteurs : {p['Auteurs'] or '—'}\n- Langue : {p['Langues']} · Rayons : {p['Rayons'] or '—'} · Mentions : {p['Mentions'] or '—'}\n"
                f"- Traducteur : {p['Meta: er_traducteur'] or '—'} · Éditeur : {p['Meta: er_editeur'] or '—'} · Collection : {p['Meta: er_collection'] or '—'} · Année : {p['Meta: er_annee'] or '—'}\n"
                f"- Prix : {p['Regular price']} €" + (f" (soldé {p['Sale price']} €)" if p['Sale price'] else '') + f" · {'en stock' if p['In stock?'] == '1' else 'épuisé'} · {len(p['Images'].split(', ')) if p['Images'] else 0} image(s)\n\n")
print(len(produits), 'livres ;', {k: len(v) for k, v in revoir.items()})
