"""Les rencontres de l'ancien site Wix, prêtes pour l'import dans le site (`wp er evenements importer`).

    python3 outils/wix/evenements.py <evenements.json> <sortie.json> [--medias medias.json] [--rapport rapport.md]

- evenements.json : les pages des événements lues sur l'ancien site (`outils/wix/pages.py evenements`) : le titre,
  les dates, la description (texte riche de Wix, ou HTML), l'image, le lieu, l'état (passé, annulé, à venir), le
  mode d'inscription ;
- medias.json (facultatif) : ce que lit `outils/wix/pages.py medias` : les vidéos de la chaîne YouTube du centre, les
  pistes de SoundCloud, et les pages d'événement relues (celles qui n'avaient pas répondu la première fois).

La sortie, une liste d'objets : wix, slug, autres_slugs, titre, debut et fin (heure de Paris, « AAAA-MM-JJ HH:MM »),
lieu, statut (passe, annule, a_venir), inscription (billetterie, gratuite), chapo, contenu (HTML), image (l'adresse
de l'original), langue (francais, russe), cycle, forme, videos ([{titre, url}]).

Les vidéos sont rattachées à leur rencontre d'après leur titre : les mots rares en commun (noms, titres d'œuvres,
dates), dans les trois écritures, sous la date approximative que donne YouTube. Seules les correspondances sûres sont
rattachées ; les autres vont au rapport, avec leurs deux meilleures candidates, pour être rattachées à la main
(champ « Vidéos et enregistrements » de la rencontre).

Les doublons de Wix (la même rencontre publiée deux fois, à la même heure) sont réunis : on garde le texte le plus
long, et l'autre adresse mène à la rencontre gardée.
"""
import argparse
import collections
import html
import json
import math
import re
import unicodedata
from datetime import datetime, timedelta, timezone
from zoneinfo import ZoneInfo

PARIS = ZoneInfo('Europe/Paris')
CENTRE = '11, rue de la Montagne-Sainte-Geneviève, 75005 Paris'
MOIS = {m: i + 1 for i, ms in enumerate([
    ('janvier', 'января', 'январь'), ('fevrier', 'février', 'февраля', 'февраль'), ('mars', 'марта', 'март'),
    ('avril', 'апреля', 'апрель'), ('mai', 'мая', 'май'), ('juin', 'июня', 'июнь'), ('juillet', 'июля', 'июль'),
    ('aout', 'août', 'августа', 'август'), ('septembre', 'сентября', 'сентябрь'), ('octobre', 'октября', 'октябрь'),
    ('novembre', 'ноября', 'ноябрь'), ('decembre', 'décembre', 'декабря', 'декабрь')]) for m in ms}


# --- le texte riche de Wix (Ricos) en HTML --------------------------------------------------------------------------

def texte(noeud):
    t = html.escape((noeud.get('textData') or {}).get('text', ''), quote=False).replace('\n', '<br>')
    for d in (noeud.get('textData') or {}).get('decorations', []):
        genre = d.get('type')
        if genre == 'BOLD':
            t = f'<strong>{t}</strong>'
        elif genre == 'ITALIC':
            t = f'<em>{t}</em>'
        elif genre == 'UNDERLINE':
            t = f'<span style="text-decoration: underline">{t}</span>'
        elif genre == 'LINK':
            url = ((d.get('linkData') or {}).get('link') or {}).get('url', '')
            if url:
                t = f'<a href="{html.escape(url)}">{t}</a>'
    return t


def ricos(noeud):
    genre = noeud.get('type')
    dedans = ''.join(ricos(n) for n in noeud.get('nodes', []))
    if genre == 'TEXT':
        return texte(noeud)
    if genre == 'PARAGRAPH':
        return f'<p>{dedans}</p>' if dedans.strip() else ''
    if genre == 'HEADING':
        niveau = min(max(int((noeud.get('headingData') or {}).get('level', 3)), 2), 4)
        return f'<h{niveau}>{dedans}</h{niveau}>'
    if genre == 'BULLETED_LIST':
        return f'<ul>{dedans}</ul>'
    if genre == 'ORDERED_LIST':
        return f'<ol>{dedans}</ol>'
    if genre == 'LIST_ITEM':
        return '<li>' + re.sub(r'^<p>(.*)</p>$', r'\1', dedans, flags=re.S) + '</li>'
    if genre == 'BLOCKQUOTE':
        return f'<blockquote>{dedans}</blockquote>'
    if genre == 'DIVIDER':
        return '<hr>'
    return dedans


def typo(t):
    """La typographie française d'un texte sans balises : apostrophe courbe, guillemets, espaces insécables."""
    t = t.replace('\xa0', ' ').replace('&#x27;', "'").replace('&#39;', "'")
    t = re.sub(r"(\w)'(\w)", '\\1’\\2', t)
    t = re.sub(r'"([^"<>]{1,200})"', '« \\1 »', t)
    t = re.sub(r'«\s*', '« ', t)
    t = re.sub(r'\s*»', ' »', t)
    t = re.sub(r'\s+([;:!?])', ' \\1', t)
    t = re.sub(r'(^|[.!?]\s+)A (?=[A-Za-zÀ-ÖØ-öø-ÿ’]{2})', '\\1À ', t)  # « A l’occasion » : « À l’occasion »
    return re.sub(r' {2,}', ' ', t)


def nettoyer(h):
    """Le HTML de la description : paragraphes vides ôtés, typographie française dans le texte (pas dans les balises)."""
    h = h.replace('&nbsp;', ' ')
    h = re.sub(r'<p>\s*(?:<br\s*/?>\s*)*</p>', '', h)
    h = re.sub(r'(?:<br\s*/?>\s*){2,}', '</p><p>', h)
    morceaux = re.split(r'(<[^>]+>)', h)
    return ''.join(m if m.startswith('<') else typo(m) for m in morceaux).strip()


def titre_propre(t):
    t = re.sub(r'\s+', ' ', t or '').strip().rstrip('.').strip()
    t = re.sub(r'^[\s–—-]+', '', t)
    if t.count('"') % 2:  # « Rencontre avec Georges Nivat "Ma Russie de 17 à 90 ans » : le guillemet fermant manque
        t += '"'
    return typo(t).strip()


# --- ce qui se lit dans le titre ----------------------------------------------------------------------------------

CYCLES = [
    (r'поэт\s+и\s+время', 'Поэт и время'),
    (r'mercredis?\s+d[eu]s?\s+[ée]diteurs', 'Les Mercredis des Éditeurs'),
    (r'христианство\s+и\s+культура', 'Христианство и культура'),
    (r'si[èe]cle\s+d.[ée]migrations', 'Un siècle d’émigrations'),
    (r'pens[ée]e\s+russe\s+face', 'La pensée russe face à l’oppression'),
    (r'разговор\s+в\s+париже', 'Разговор в Париже'),
]
FORMES = [
    (r'vernissage|exposition|inauguration|выставк', 'Exposition'),
    (r'lecture[- ]spectacle|lecture sc[ée]nique|читка', 'Lecture'),
    (r'\bfilm|кино|cin[ée]ma', 'Cinéma'),
    (r'conf[ée]rence|лекци|cycle de', 'Conférence'),
    (r'pr[ée]sentation|презентац|lancement|представит', 'Présentation'),
    (r'po[ée]sie|po[èe]te|поэт|стих', 'Poésie'),
    (r'rencontre|встреча|разговор|вспоминает', 'Rencontre'),
    (r'soir[ée]e|hommage|anniversaire|юбилей|вечер', 'Soirée'),
]


def lire(motifs, t, defaut=''):
    for motif, nom in motifs:
        if re.search(motif, t, re.I):
            return nom
    return defaut


def heure_paris(iso):
    if not iso:
        return ''
    return datetime.fromisoformat(iso.replace('Z', '+00:00')).astimezone(PARIS).strftime('%Y-%m-%d %H:%M')


# --- la clé des mots, dans les trois écritures (comme er_cle_recherche de l'extension) ----------------------------

CYR = {'щ': 'shch', 'ж': 'zh', 'х': 'kh', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'ю': 'yu', 'я': 'ya', 'ё': 'e', 'й': 'i',
       'ы': 'y', 'э': 'e', 'ь': '', 'ъ': '', 'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'з': 'z',
       'и': 'i', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
       'ф': 'f', 'і': 'i', 'ї': 'i', 'є': 'e', 'ґ': 'g'}


def mots(t):
    t = re.sub(r"[’'ʼ]", ' ', (t or '').lower())  # l’Archipel : deux mots
    t = ''.join(CYR.get(c, c) for c in t)
    t = unicodedata.normalize('NFKD', t).encode('ascii', 'ignore').decode()
    t = re.sub(r'[^a-z0-9]+', ' ', t)
    for a, b in [('shch', 'ch'), ('chtch', 'ch'), ('tch', 'ch'), ('sch', 'ch'), ('sh', 'ch'), ('zh', 'j'), ('kh', 'h'),
                 ('ph', 'f'), ('x', 'ks'), ('w', 'v'), ('qu', 'k')]:
        t = t.replace(a, b)
    t = t.replace('ou', 'u').replace('y', 'i')
    t = re.sub(r'gu(?=[ei])', 'g', t)
    t = t.replace('ie', 'e').replace('ii', 'i')
    t = re.sub(r'([a-z])\1+', r'\1', t)
    t = re.sub(r'e\b', '', t)
    return t.split()


VIDES = set(mots('''rencontre presentation livre livres soiree autour avec pour dans leur les des une sur par cette notre
nouveau nouvelle occasion parution conference conferences cycle editions edition traduction roman poete poetesse poesie
russe russes russie centre culturel alexandre soljenitsyne auteur auteure historien historienne paris france premier
premiere annee siecle mercredis editeurs vie consacree consacre direct entretien dialogue introduction episode podcasts
ouvert lancement
встреча презентация книга книги книгой книгу вечер лекция лекций цикл поэт поэзия автор автором новой новая новый
издательство стихи своей россии русской русская русский выхода случаю беседу ведет'''))


def jetons(t):
    return {j for j in mots(t) if (len(j) >= 4 or (j.isdigit() and len(j) >= 2)) and j not in VIDES}


def dates_dans(t):
    """Les dates écrites en toutes lettres : « 14 novembre 2022 », « 12 ноября 2025 »."""
    trouve = []
    for j, m, a in re.findall(r'(\d{1,2})(?:er)?\s+([a-zéûа-яё]+)\s+(\d{4})', (t or '').lower()):
        if m in MOIS:
            try:
                trouve.append(datetime(int(a), MOIS[m], int(j)).strftime('%Y-%m-%d'))
            except ValueError:
                pass
    return trouve


# --- les rencontres ---------------------------------------------------------------------------------------------

def convertir(pages):
    sortie = []
    for p in pages:
        w = p.get('wix')
        if not w:
            continue
        config = (w.get('scheduling') or {}).get('config') or {}
        statut = {3: 'annule', 2: 'passe', 1: 'a_venir', 0: 'a_venir'}.get(w.get('status'), '')
        if not statut:
            fin = config.get('endDate') or config.get('startDate')
            statut = 'passe' if fin and fin < datetime.now(timezone.utc).isoformat() else 'a_venir'
        inscription = {2: 'billetterie', 1: 'gratuite'}.get((w.get('registration') or {}).get('type'), '')
        long = w.get('longDescription')
        contenu = ''.join(ricos(n) for n in long.get('nodes', [])) if isinstance(long, dict) else ''
        if not contenu.strip():
            contenu = w.get('about') or ''
        chapo = re.sub(r'^[\s,.;:–-]+', '', (w.get('description') or '').strip())
        if not contenu.strip() and chapo:
            contenu, chapo = f'<p>{html.escape(chapo)}</p>', ''
        titre = titre_propre(w.get('title'))
        lieu = (w.get('location') or {}).get('address') or ''
        if re.search(r'facebook|en ligne|online|zoom', titre + ' ' + chapo, re.I) and not re.search(r'montagne', lieu, re.I):
            lieu = 'En ligne'
        elif re.search(r'montagne', lieu, re.I) or not lieu:
            lieu = CENTRE
        image = w.get('mainImage') or {}
        debut, fin = heure_paris(config.get('startDate')), heure_paris(config.get('endDate'))
        if debut and fin and timedelta(hours=6) < (datetime.fromisoformat(fin) - datetime.fromisoformat(debut)) < timedelta(days=3):
            fin = ''  # Wix met souvent la fin au lendemain : on ne garde une fin que pour ce qui dure (une exposition)
        sortie.append({
            'wix': w.get('id'), 'slug': w.get('slug'), 'autres_slugs': [], 'titre': titre,
            'debut': debut, 'fin': fin,
            'lieu': lieu, 'statut': statut, 'inscription': inscription, 'chapo': typo(chapo).strip(),
            'contenu': nettoyer(contenu), 'image': image.get('url', ''),
            'langue': 'russe' if len(re.findall('[а-яА-ЯёЁ]', titre)) > len(re.findall('[a-zA-Zéèàç]', titre)) else 'francais',
            'cycle': lire(CYCLES, titre), 'forme': lire(FORMES, titre, 'Rencontre'), 'videos': [],
        })
    # Les doublons de Wix : même heure, même titre.
    gardes, doublons = {}, 0
    for e in sortie:
        cle = (e['debut'], ' '.join(mots(e['titre'])))
        if cle in gardes and e['debut']:
            g = gardes[cle]
            if len(e['contenu']) > len(g['contenu']):
                e['autres_slugs'] = g['autres_slugs'] + [g['slug']]
                gardes[cle] = e
            else:
                g['autres_slugs'].append(e['slug'])
            doublons += 1
        else:
            gardes[cle] = e
    sortie = sorted(gardes.values(), key=lambda e: e['debut'], reverse=True)
    return sortie, doublons


# --- les vidéos et les enregistrements --------------------------------------------------------------------------

# Les vidéos que le calcul laisse « à vérifier » mais dont la rencontre ne fait pas de doute à la lecture (la vidéo
# nomme le même livre, la même personne, la séance du programme) : l'identifiant YouTube → le début de la rencontre.
RELUES = {
    'cap6vmRB3Y4': '2023-12-11 18:30',  # Hélène Henry, sa traduction du Docteur Jivago
    'VN-55noZgP4': '2023-11-13 18:30',  # Michel Niqueux, le Dictionnaire Gogol
    'e2bcPvy635U': '2025-11-17 19:00',  # Riva Evstifeeva (la vidéo est parue après la rencontre de 2025)
    '5FA7R8q3y6c': '2024-01-29 18:30',  # L'Archipel du Goulag, 50 ans après
    'cjGfVT6vubk': '2021-12-06 18:30',  # Michel Niqueux, le Dictionnaire Dostoïevski
    'Ao5Z89dXp5k': '2020-04-10 19:00',  # l'Anthologie de la poésie russe du début du XXIe siècle (Michel Finck)
    '-kFCoUHwwFo': '2020-04-10 19:00',  # la même anthologie, la présentation en russe
    'Pn4Ix4G_EaE': '2025-02-24 18:30',  # Philippe Sers, Kandinsky : « Beauté et Vérité »
    'wX9tOhAWZpc': '2021-11-10 19:00',  # Musique et folklore : la séance du 10 novembre 2021, d'après le programme
}


def ident_video(url):
    m = re.search(r'(?:v=|youtu\.be/|embed/)([\w-]{11})', url)
    return m.group(1) if m else url


def apparier(rencontres, videos, maintenant):
    """Rattache chaque vidéo à sa rencontre quand c'est sûr ; rend les autres, avec leurs candidates."""
    docs = []
    for e in rencontres:
        corps = re.sub(r'<[^>]+>', ' ', e['contenu'])
        docs.append((jetons(e['titre'] + ' ' + e['chapo']), jetons(corps)))
    df = collections.Counter(j for t, c in docs for j in t | c)
    n = len(rencontres)
    idf = lambda j: math.log((n + 1) / (1 + df.get(j, 0)))  # noqa: E731

    # Les programmes écrits dans les textes : « 12 ноября 2025 – Лесков и русское духовенство ». Le programme que
    # donne la rencontre elle-même compte plus que celui d'une autre séance (il a pu changer entre-temps).
    programmes = []
    for i, e in enumerate(rencontres):
        texte_brut = re.sub(r'<[^>]+>', '\n', e['chapo'] + '\n' + e['contenu'])
        for ligne in texte_brut.split('\n'):
            d = dates_dans(ligne)
            if d:
                sujet = jetons(re.sub(r'^.*?\d{4}\s*[–—:-]?\s*', '', ligne))
                if sujet:
                    programmes.append((d[0], sujet, ' '.join(mots(e['titre'])), i))

    # Un titre que portent plusieurs rencontres (les séances d'un cycle) ne suffit pas : il faut la date ou le sujet.
    familles = collections.Counter(' '.join(mots(e['titre'])) for e in rencontres)

    sures, a_voir, seules = [], [], []
    par_debut = {e['debut']: e for e in rencontres if e['statut'] == 'passe'}
    for v in videos:
        relue = par_debut.get(RELUES.get(ident_video(v['url']), ''))
        if relue:
            relue['videos'].append({'titre': v['titre'], 'url': v['url']})
            sures.append((v, relue))
            continue
        tv = jetons(v['titre'])
        poids_video = sum(idf(j) for j in tv) or 1
        quand = datetime.fromtimestamp(v['date'], timezone.utc) if v.get('date') else None
        tolerance = (maintenant - quand) * 0.5 + timedelta(days=21) if quand else None
        candidats = []
        for i, (e, (t, c)) in enumerate(zip(rencontres, docs)):
            if e['statut'] != 'passe' or not e['debut']:
                continue
            debut = datetime.strptime(e['debut'], '%Y-%m-%d %H:%M').replace(tzinfo=PARIS)
            if quand and debut > quand + tolerance:
                continue  # la vidéo est parue avant la rencontre
            commun = tv & (t | c)
            score = sum(idf(j) * (1 if j in t else 0.5) for j in commun)
            if e['debut'][:10] in dates_dans(v['titre']):
                score += 50  # la vidéo dit la date de la rencontre
            bonus = 0
            for d, sujet, famille, source in programmes:
                part = sum(idf(j) for j in tv & sujet) / (sum(idf(j) for j in sujet) or 1)
                if d == e['debut'][:10] and famille == ' '.join(mots(e['titre'])) and part >= 0.5:
                    # le sujet du jour, dans le programme d'un cycle (celui de la séance elle-même compte double)
                    bonus = max(bonus, 40 * part * (1 if source == i else 0.5))
            score += bonus
            fort = bonus >= 20 or e['debut'][:10] in dates_dans(v['titre'])
            candidats.append((score, sum(idf(j) for j in commun) / poids_video, e, fort))
        candidats.sort(key=lambda x: -x[0])
        if not candidats or candidats[0][0] < 6:
            seules.append(v)
            continue
        meilleur, second = candidats[0], candidats[1] if len(candidats) > 1 else (0, 0, None, False)
        seule_du_titre = familles[' '.join(mots(meilleur[2]['titre']))] == 1
        if (meilleur[3] and (not second[3] or meilleur[0] >= second[0] + 10)) or (
                seule_du_titre and meilleur[0] >= 7 and meilleur[1] >= 0.6 and meilleur[0] >= 1.5 * second[0]):
            meilleur[2]['videos'].append({'titre': v['titre'], 'url': v['url']})
            sures.append((v, meilleur[2]))
        else:
            a_voir.append((v, [c[2] for c in candidats[:2]]))
    return sures, a_voir, seules


def lire_videos(medias):
    videos = []
    for source, liste in (medias.get('chaines') or {}).items():
        for x in liste:
            if 'erreur' in x or not x.get('url'):
                continue
            if 'soundcloud' in source and re.search(r'épisode|episode', x.get('title') or '', re.I):
                continue  # les épisodes du podcast ne sont pas des rencontres
            videos.append({'titre': re.sub(r'\s+', ' ', x.get('title') or '').strip(), 'url': x['url'],
                           'date': x.get('timestamp'), 'duree': x.get('duration'), 'source': source})
    # La même vidéo citée deux fois (dans la page et dans la chaîne) : une seule.
    vues = set()
    uniques = []
    for v in videos:
        ident = ident_video(v['url'])
        if ident not in vues:
            vues.add(ident)
            uniques.append(v)
    return uniques


def rapport(sures, a_voir, seules, doublons, rencontres):
    l = ['# Les rencontres de l’ancien site', '',
         f'{len(rencontres)} rencontres ({doublons} doublons de Wix réunis) ; '
         f'{sum(1 for e in rencontres if e["contenu"])} avec un texte, {sum(1 for e in rencontres if e["image"])} avec une affiche.', '',
         f'## Vidéos rattachées d’office ({len(sures)})', '']
    for v, e in sorted(sures, key=lambda x: x[1]['debut'], reverse=True):
        l.append(f'- {e["debut"][:10]} · {e["titre"]} ← [{v["titre"]}]({v["url"]})')
    l += ['', f'## À vérifier ({len(a_voir)}) : la vidéo, puis les deux rencontres les plus proches', '']
    for v, cands in a_voir:
        l.append(f'- [{v["titre"]}]({v["url"]})')
        for e in cands:
            l.append(f'  - {e["debut"][:10]} · {e["titre"]}')
    l += ['', f'## Sans rencontre trouvée ({len(seules)}) : pour la page « Nos vidéos », ou à rattacher à la main', '']
    for v in seules:
        l.append(f'- [{v["titre"]}]({v["url"]})')
    return '\n'.join(l) + '\n'


if __name__ == '__main__':
    arg = argparse.ArgumentParser()
    arg.add_argument('evenements')
    arg.add_argument('sortie')
    arg.add_argument('--medias')
    arg.add_argument('--rapport')
    a = arg.parse_args()
    pages = json.load(open(a.evenements))
    medias = json.load(open(a.medias)) if a.medias else {}
    relues = {p['url']: p for p in medias.get('evenements', []) if p.get('wix')}
    pages = [relues.get(p['url'], p) for p in pages] + [p for u, p in relues.items() if u not in {q['url'] for q in pages}]
    rencontres, doublons = convertir(pages)
    sures, a_voir, seules = apparier(rencontres, lire_videos(medias), datetime.now(timezone.utc))
    json.dump(rencontres, open(a.sortie, 'w'), ensure_ascii=False, indent=1)
    if a.rapport:
        open(a.rapport, 'w').write(rapport(sures, a_voir, seules, doublons, rencontres))
    print(len(rencontres), 'rencontres ;', doublons, 'doublons réunis ;', sum(1 for e in rencontres if e['contenu']), 'avec un texte ;',
          sum(1 for e in rencontres if e['image']), 'avec une affiche ;', len(sures), 'vidéos rattachées ;', len(a_voir), 'à vérifier ;',
          len(seules), 'sans rencontre')
