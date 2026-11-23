"""Les ISBN des livres : un tableau à remplir, puis le fichier qui les importe dans WooCommerce, par la référence.

    python3 outils/catalogue/isbn.py modele <catalogue>
        <catalogue> : import-woocommerce.csv (sorti de convertir.py) ou un export de la boutique (Produits →
        Exporter, avec au moins les colonnes UGS et Nom ; Auteurs, GTIN et les métadonnées s'il y en a).
        Écrit isbn-a-remplir.xlsx : Référence, Titre, Auteurs, Éditeur, Année, En stock, ISBN (en texte, pour que le
        tableur ne change pas 9782070368228 en 9,78207E+12). Les livres en stock d'abord, puis par titre.

    python3 outils/catalogue/isbn.py preparer <tableau rempli> [--catalogue <catalogue>]
        <tableau rempli> : le .xlsx ci-dessus, ou tout tableau (.xlsx, .csv) qui a une colonne d'ISBN et une colonne
        de référence (WIX-…) ; à défaut de référence, le titre (et l'auteur) sont cherchés dans --catalogue.
        Écrit complement-isbn.csv, à importer dans Produits → Importer en cochant « Mettre à jour les produits
        existants » : la référence, l'ISBN dans le champ « GTIN, UPC, EAN ou ISBN » de WooCommerce, et dans la méta
        er_isbn quand le même ISBN sert déjà à une autre fiche (WooCommerce refuse les doublons) ; et
        isbn-a-revoir.md : les ISBN faux, les doublons, les lignes sans livre.

Chaque ISBN est contrôlé (la clé de contrôle), écrit sans tirets ni espaces, et un ISBN à 10 chiffres est converti en
ISBN-13 (978…) : WooCommerce ne garde que les chiffres et les tirets, il perdrait le X final d'un ISBN-10.

Les fichiers lus et écrits restent hors du dépôt, qui est public (.gitignore).
"""
import csv, re, sys, unicodedata, collections

# --- les ISBN ------------------------------------------------------------------------------------------------------


def cle_isbn13(douze):
    return str((10 - sum(int(c) * (3 if i % 2 else 1) for i, c in enumerate(douze)) % 10) % 10)


def cle_isbn10(neuf):
    r = (11 - sum(int(c) * (10 - i) for i, c in enumerate(neuf)) % 11) % 11
    return 'X' if r == 10 else str(r)


def normaliser(valeur):
    """Une cellule → (ISBN-13 ou EAN-13, remarque) ; (None, raison) si ce n'est pas un ISBN valable."""
    if valeur is None:
        return None, ''
    if isinstance(valeur, float):  # un nombre lu dans un tableur
        valeur = '%d' % valeur
    brut = str(valeur).strip()
    if not brut or brut in {'-', '—', '?'}:
        return None, ''
    if not re.search(r'\d', brut):
        return None, f'pas de numéro (« {brut} »)'
    remarque = ''
    morceaux = [m for m in re.split(r'[;/,]|\s{2,}|\bou\b', brut) if re.search(r'\d', m)]
    if len(morceaux) > 1:
        remarque = f'plusieurs numéros dans la case (« {brut} ») : le premier est retenu'
        brut = morceaux[0]
    t = re.sub(r'^(?:ISBN(?:-1[03])?|EAN)\s*:?\s*', '', brut.strip(), flags=re.I)
    t = re.sub(r'[\s\-‐‑–—.]', '', t).upper()
    if re.fullmatch(r'\d{9}[\dX]', t):
        if cle_isbn10(t[:9]) != t[9]:
            return None, f'ISBN-10 dont la clé est fausse (« {brut} »)'
        douze = '978' + t[:9]
        return douze + cle_isbn13(douze), remarque or f'ISBN-10 converti ({t})'
    if re.fullmatch(r'\d{13}', t):
        if cle_isbn13(t[:12]) != t[12]:
            return None, f'clé de contrôle fausse (« {brut} »)'
        if not t.startswith(('978', '979')):
            return t, remarque or 'code à 13 chiffres qui n’est pas un ISBN (978 ou 979) : gardé comme EAN'
        return t, remarque
    if re.fullmatch(r'\d{8}', t):
        return None, f'ISSN ou numéro à 8 chiffres, pas un ISBN (« {brut} »)'
    return None, f'ni 10 ni 13 chiffres (« {brut} »)'


# --- les tableaux --------------------------------------------------------------------------------------------------


def cle_texte(t):
    t = unicodedata.normalize('NFKD', str(t or '').lower())
    t = ''.join(c for c in t if not unicodedata.combining(c)).replace('ё', 'е')
    return re.sub(r'\s+', ' ', re.sub(r'[^\w]+', ' ', t)).strip()


ENTETES = {  # ce qu'on cherche → les en-têtes possibles (comparés par cle_texte)
    'ref': ['référence', 'reference', 'réf', 'ref', 'sku', 'ugs'],
    'titre': ['titre', 'name', 'nom', 'title'],
    'auteurs': ['auteurs', 'auteur', 'author', 'authors'],
    'editeur': ['éditeur', 'editeur', 'meta er_editeur', 'publisher'],
    'annee': ['année', 'annee', 'meta er_annee', 'parution', 'year'],
    'stock': ['en stock', 'in stock', 'stock'],
    'isbn': ['isbn', 'isbn 13', 'isbn13', 'ean', 'gtin', 'gtin upc ean or isbn', 'gtin upc ean ou isbn', 'gtin upc ean isbn'],
    'publie': ['published', 'publié', 'publie'],
}


def lire(chemin):
    """Un tableau .xlsx ou .csv → liste de dicts (les en-têtes tels quels)."""
    if chemin.lower().endswith(('.xlsx', '.xlsm')):
        import openpyxl
        feuille = openpyxl.load_workbook(chemin, read_only=True, data_only=True).worksheets[0]
        lignes = list(feuille.iter_rows(values_only=True))
        entetes = [str(e).strip() if e is not None else '' for e in lignes[0]]
        return entetes, [dict(zip(entetes, l)) for l in lignes[1:] if any(v not in (None, '') for v in l)]
    texte = open(chemin, encoding='utf-8-sig', newline='').read()
    separateur = ';' if texte.split('\n', 1)[0].count(';') > texte.split('\n', 1)[0].count(',') else ','
    lecteur = csv.DictReader(texte.splitlines(), delimiter=separateur)
    return lecteur.fieldnames, list(lecteur)


def colonnes(entetes):
    """Ce qu'on cherche → l'en-tête du fichier qui y répond."""
    trouve = {}
    for cle, possibles in ENTETES.items():
        for e in entetes:
            if cle_texte(e) in {cle_texte(p) for p in possibles} and cle not in trouve:
                trouve[cle] = e
    return trouve


def valeur(ligne, col, cle):
    v = ligne.get(col.get(cle, ''), '')
    return '' if v is None else str(v).strip()


def en_stock(v):
    return cle_texte(v) in {'1', 'oui', 'yes', 'en stock', 'instock', 'true'} or (v.isdigit() and int(v) > 0)


# --- modele --------------------------------------------------------------------------------------------------------


def modele(catalogue):
    import openpyxl
    from openpyxl.styles import Font, PatternFill, Alignment
    entetes, lignes = lire(catalogue)
    col = colonnes(entetes)
    if 'ref' not in col or 'titre' not in col:
        sys.exit(f'Le catalogue doit avoir une colonne de référence et une colonne de titre (trouvé : {", ".join(entetes)}).')
    livres = []
    for l in lignes:
        ref = valeur(l, col, 'ref')
        if not ref or valeur(l, col, 'publie') == '-1':
            continue
        isbn = valeur(l, col, 'isbn')
        livres.append([ref, valeur(l, col, 'titre'), valeur(l, col, 'auteurs').replace(' | ', ', '),
                       valeur(l, col, 'editeur'), valeur(l, col, 'annee'),
                       'oui' if en_stock(valeur(l, col, 'stock')) else ('non' if 'stock' in col else ''),
                       normaliser(isbn)[0] or isbn])
    livres.sort(key=lambda x: (x[5] != 'oui', cle_texte(x[1])))
    classeur = openpyxl.Workbook()
    f = classeur.active
    f.title = 'ISBN'
    f.append(['Référence', 'Titre', 'Auteurs', 'Éditeur', 'Année', 'En stock', 'ISBN'])
    for x in livres:
        f.append(x)
    for cellule in f[1]:
        cellule.font = Font(bold=True)
    jaune = PatternFill('solid', fgColor='FFF6CC')
    for (cellule,) in f.iter_rows(min_row=1, min_col=7, max_col=7):
        cellule.number_format = '@'  # du texte : le tableur ne touche pas aux chiffres
        if cellule.row > 1:
            cellule.fill = jaune
            if cellule.value is not None:
                cellule.value = str(cellule.value)
    for lettre, largeur in zip('ABCDEFG', (14, 48, 28, 22, 8, 9, 17)):
        f.column_dimensions[lettre].width = largeur
    for ligne in f.iter_rows(min_row=2, min_col=2, max_col=3):
        for cellule in ligne:
            cellule.alignment = Alignment(wrap_text=False)
    f.freeze_panes = 'B2'
    f.auto_filter.ref = f.dimensions
    classeur.save('isbn-a-remplir.xlsx')
    print(f'isbn-a-remplir.xlsx : {len(livres)} livres, dont {sum(x[5] == "oui" for x in livres)} en stock, '
          f'{sum(1 for x in livres if x[6])} avec déjà un ISBN.')


# --- preparer ------------------------------------------------------------------------------------------------------


def preparer(rempli, catalogue=None):
    entetes, lignes = lire(rempli)
    col = colonnes(entetes)
    if 'isbn' not in col:
        sys.exit(f'Pas de colonne d’ISBN dans {rempli} (trouvé : {", ".join(entetes)}).')
    par_titre = collections.defaultdict(list)
    refs_connues = set()
    if catalogue:
        e2, l2 = lire(catalogue)
        c2 = colonnes(e2)
        for l in l2:
            ref = valeur(l, c2, 'ref')
            if ref:
                refs_connues.add(ref)
                par_titre[cle_texte(valeur(l, c2, 'titre'))].append((ref, cle_texte(valeur(l, c2, 'auteurs'))))
    revoir = collections.defaultdict(list)
    retenus = []  # (ref, isbn, libellé, en stock)
    for n, l in enumerate(lignes, start=2):
        isbn, remarque = normaliser(l.get(col['isbn']))
        titre = valeur(l, col, 'titre')
        libelle = f'ligne {n} : {titre or "?"}'
        if not isbn:
            if remarque.startswith('pas de numéro'):
                revoir['Cases écrites sans numéro'].append(f'{libelle} — {remarque}')
            elif remarque:
                revoir['ISBN refusés'].append(f'{libelle} — {remarque}')
            continue
        ref = valeur(l, col, 'ref').upper()
        if ref and refs_connues and ref not in refs_connues:
            revoir['Références inconnues du catalogue'].append(f'{libelle} — {ref}')
        if not ref:
            candidats = par_titre.get(cle_texte(titre), [])
            auteur = cle_texte(valeur(l, col, 'auteurs'))
            if len(candidats) > 1 and auteur:
                candidats = [c for c in candidats if auteur.split()[-1] in c[1]] or candidats
            if len(candidats) == 1:
                ref = candidats[0][0]
                revoir['Retrouvés par le titre (à vérifier)'].append(f'{libelle} → {ref}')
            else:
                revoir['Sans référence ni titre reconnu' if not candidats else 'Titre ambigu'].append(
                    libelle + (f' — {", ".join(c[0] for c in candidats)}' if candidats else ''))
                continue
        if remarque:
            revoir['Remarques'].append(f'{libelle} — {remarque}')
        retenus.append((ref, isbn, libelle, en_stock(valeur(l, col, 'stock'))))

    # Une référence, un ISBN : la dernière ligne l'emporte si la même référence revient.
    par_ref = {}
    for r in retenus:
        if r[0] in par_ref and par_ref[r[0]][1] != r[1]:
            revoir['Deux ISBN pour une même référence (le dernier est retenu)'].append(
                f'{r[0]} : {par_ref[r[0]][1]} puis {r[1]}')
        par_ref[r[0]] = r
    # Un ISBN, une fiche dans le GTIN de WooCommerce (le livre en stock d'abord) ; les autres le gardent dans er_isbn.
    par_isbn = collections.defaultdict(list)
    for r in par_ref.values():
        par_isbn[r[1]].append(r)
    sortie = []
    for isbn, fiches in par_isbn.items():
        fiches.sort(key=lambda r: not r[3])
        sortie.append((fiches[0][0], isbn, ''))
        for autre in fiches[1:]:
            sortie.append((autre[0], '', isbn))
        if len(fiches) > 1:
            revoir['Un même ISBN pour plusieurs fiches (neuf et occasion ?)'].append(
                f'{isbn} : {fiches[0][0]} (dans le GTIN) ; ' + ', '.join(a[0] for a in fiches[1:]) + ' (dans er_isbn)')
    sortie.sort()
    with open('complement-isbn.csv', 'w', newline='', encoding='utf-8') as f:
        w = csv.writer(f)
        w.writerow(['SKU', 'GTIN, UPC, EAN, or ISBN', 'Meta: er_isbn'])
        w.writerows(sortie)
    with open('isbn-a-revoir.md', 'w', encoding='utf-8') as f:
        f.write(f'# Les ISBN : ce qui est à revoir\n\n{len(lignes)} lignes lues, {len(sortie)} livres à mettre à jour '
                f'({sum(1 for s in sortie if s[1])} dans le GTIN, {sum(1 for s in sortie if s[2])} dans er_isbn).\n\n')
        for titre, items in revoir.items():
            f.write(f'## {titre} ({len(items)})\n\n' + ''.join(f'- {i}\n' for i in items) + '\n')
    print(f'complement-isbn.csv : {len(sortie)} livres ; à revoir :', {k: len(v) for k, v in revoir.items()} or 'rien')


if __name__ == '__main__':
    args = sys.argv[1:]
    if len(args) >= 2 and args[0] == 'modele':
        modele(args[1])
    elif len(args) >= 2 and args[0] == 'preparer':
        cat = args[args.index('--catalogue') + 1] if '--catalogue' in args else None
        preparer(args[1], cat)
    else:
        sys.exit(__doc__)
