# Les Éditeurs réunis — le site

L'ébauche du site de la librairie des Éditeurs réunis, de la maison d'édition YMCA-Press et du Centre culturel
Alexandre Soljenitsyne : un WordPress avec WooCommerce, pour vendre les livres en ligne.

## Voir l'ébauche

[Ouvrir l'aperçu dans WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/andrerakovitch-byte/editeurs-reunis/main/blueprint.json)

L'aperçu tourne entièrement dans le navigateur : WordPress, WooCommerce, le thème, l'extension et le contenu
s'installent à l'ouverture (une minute environ). On y est connecté en administrateur, on peut tout modifier ; rien
n'est gardé quand on ferme l'onglet.

## Ce qu'il y a dedans

- `theme/editeurs-reunis/` : le thème, un thème « blocs » qu'on modifie dans l'éditeur de site de WordPress
  (Apparence → Éditeur).
  - `theme.json` : la direction artistique, toute entière (les couleurs, la police Montserrat, les tailles, les
    espacements, les boutons en pilule).
  - `style.css` : le reste du dessin (l'en-tête, les fiches des livres, le pied).
  - `patterns/` : les modules de l'accueil (`une-*.php`), l'en-tête, le pied, la librairie et la fiche d'un livre.
  - `templates/` et `parts/` : les gabarits, qui ne font qu'appeler les sections.
- `plugin/er-librairie/` : l'extension de la librairie, séparée du thème pour que les livres survivent à un
  changement de dessin : les auteurs et les langues des livres, et des traductions de secours pour WooCommerce.
  - `donnees/semer.php` : remplit un site vide avec le contenu de l'ébauche (les réglages, les pages, les rayons,
    les quinze livres et leurs couvertures, le communiqué). Relancé, il ne crée rien en double.
- `blueprint.json` : le plan de l'aperçu Playground.
- `apercu/` : les deux archives que l'aperçu installe. `outils/construire.sh` les refait.

## La direction artistique est provisoire

Elle reprend la maquette Wix : Montserrat, la pervenche `#9DB5F5`, la barre noire, les boutons en pilule. Pour la
changer, on touche à `theme.json` et à `style.css`, et seulement à eux : les sections et les gabarits n'ont aucune
couleur ni taille écrite en dur.

## L'en-tête : trois maisons, trois couleurs

Retenu le 21 novembre 2026. Une barre noire, la même partout : le nom, les trois maisons (Librairie, Maison
d'édition YMCA-Press, Le centre culturel), chacune précédée d'un point de sa couleur, puis le coin droit en pictos (disposition A′, retenue le même jour) : la loupe, le compte, le panier, chacun dans un
rond de 36 px (au survol, le rond grisé ; sélectionné, le rond blanc et le picto noir, à la même place), le nombre de
livres dans une pastille pervenche quand le panier n'est pas vide, et les trois langues dans un interrupteur, la
langue de la page en blanc plein. Le nom garde l'espacement du logo d'origine (0,95 em) ; entre 1280 et 1440 px,
« Maison d'édition YMCA-Press » devient « YMCA-Press » ; au téléphone, la barre garde la loupe et le panier, le compte
et la langue vont au bas du menu (≡). Dessous, le menu de la maison où l'on est, sur sa couleur : vert rayonnage pour la librairie
(ses rayons, avec leurs tiroirs), brun reliure pour YMCA-Press, bleu nuit pour le centre culturel. L'accueil du
site montre la ligne de la librairie, « Accueil » souligné (son entrée « Accueil » y mène) ; l'onglet « Librairie »
n'y est pas souligné : l'accueil n'est pas toute la librairie. Rien dessous sur les pages communes. Plus de menus déroulants ; en dessous de 1280 px, le menu (≡) montre les trois
maisons et leurs pages.

La maison d'une page se déduit d'elle-même (`er_maison()` dans `functions.php`) : la boutique, les rayons, les livres,
les auteurs et les promotions pour la librairie ; les pages sous `ymca-press/` pour YMCA-Press ; les pages sous
`centre-culturel/`, le journal et « Nous trouver » pour le centre. Les couleurs sont dans `theme.json` (« Maison · »
et « Point · »), les pages des menus dans `er_pages_des_maisons()`.

## La recherche

La loupe de la barre noire ouvre un panneau (`theme/…/inc/recherche.php`, `assets/js/recherche.js`). À gauche, les
filtres : où chercher (partout, la librairie, YMCA-Press, le centre culturel), puis, pour les livres, le rayon, la
langue et l'état. Ce sont des boutons qu'on presse, jamais des liens (décision du 21 nov. 2026) : ils filtrent ce
qu'on tape, et seuls, sans texte, montrent les livres qui y répondent ; au téléphone, ils se replient sous « Filtrer ».
À droite, avant de taper, des pistes (les dernières recherches, les recherches fréquentes, les derniers arrivés) ; en
tapant, les résultats rangés par genre (l'auteur, les livres, les rayons, YMCA-Press, le centre, le journal), chacun
avec le point de sa maison ; Entrée emporte le texte et les filtres. « Recherche détaillée » reprend les filtres et
déplie des champs qui se combinent (titre, auteur, traducteur, ISBN, rayon, langue, éditeur, collection, année
d'édition, prix, état, en stock) ; elle compte les livres à chaque choix.

L'extension tient l'index (`plugin/…/inc/recherche.php`, une table `er_index` refaite à chaque enregistrement) et
l'API que lit le panneau : `/wp-json/er/v1/chercher?q=` (avec `er_rayon[]`, `er_langue[]`, `er_etat[]`, les livres
seulement) et `/wp-json/er/v1/livres?er_auteur=…`. L'index connaît les
trois écritures : Dostoïevski, Достоевский, Dostoevsky et « dostoievski » ont la même clé ; pour un nom qui y
échappe, la fiche d'un auteur a un champ « Autres graphies ». La recherche des livres de WordPress
(`?s=…&post_type=product`) passe par le même index, et la librairie lit les critères de la recherche détaillée dans
son adresse (`/librairie/?er_auteur=iakhina&er_stock=1`). La fiche d'un livre a quatre champs de plus : traducteur,
éditeur, collection, année d'édition.

## L'accueil, à la une

L'accueil du site (`templates/front-page.html`) est fait des modules de la maquette « L'accueil, à la une » du canevas,
chacun une composition en blocs de WordPress, qu'on modifie dans l'éditeur de site (21 nov. 2026 : « garde ces
modules stp sauf le dernier [l'index], regarde les modifications et ajoute-les au site », André) :

- `une-archipel`, `une-jivago`, `une-tchevengour` : des affiches pleine largeur sur l'aplat d'une maison (brun
  d'YMCA-Press, vert de la librairie, noir). À gauche la rubrique et le point de chaque maison, le titre, les faits en
  petit (le prix compris, en petit, pour informer plutôt que vendre), les boutons, et « Pour aller plus loin », où
  chaque lien dit ce qu'il est (En librairie, L'encyclopédie, Journal du centre, Vidéo, Podcast, Le fonds d'archives)
  et, en une phrase, pourquoi il est là. À droite la phrase en demi-gras, une photo des archives ou un dessin
  (`assets/images/une-*.svg`), et le texte sur deux colonnes ;
- `une-journal` : l'article du mois et sa photo ; à côté, les billets, chacun avec sa lettrine (une lettre russe sur
  un aplat) ;
- `une-poeme` : une page, le poème de Blok en entier, la rue dessinée, trois paragraphes et le livre à la librairie ;
- `une-videos` : une vidéo à voir et les six séries des bénévoles ;
- `une-encyclopedie` : l'article du jour, sa fiche, son portrait et les livres qu'il cite ; « Le saviez-vous ? » et
  les dates du mois.

Les prix et les boutons « Ajouter au panier » sont lus dans la librairie à chaque affichage (`er_prix()`,
`er_ajout_panier()`, `functions.php`) : « épuisé pour l'instant » quand un livre n'est plus en stock. Les grands mots
russes ton sur ton et les lettrines sont des blocs HTML, cachés aux lecteurs d'écran. Les textes entre crochets sont à
écrire ; ceux des compositions se traduisent comme le reste du thème (`outils/traductions.py`).

Au téléphone, chaque module garde l'essentiel : le titre sur son aplat, la photo ou le dessin, la phrase, le bouton
principal ; le texte, les faits et « Pour aller plus loin » se déplient sous « Voir plus » (`assets/js/une.js` ; sans
JavaScript, tout est montré). Les billets et les séries y deviennent une liste courte, la lettrine et le titre
(« sur mobile trouve un affichage adapté : soit tu utilises le Voir plus, garde l'essentiel, et plutôt photo et
titres avec aplat de couleurs », André, 21 nov.).

Les compositions sont écrites d'après la maquette par un script (`proposition/blocs_une.py`, hors du dépôt) ; on peut
aussi les modifier à la main. Les anciennes sections de l'accueil (le diaporama, les nouveautés, les branches…)
restent dans `patterns/`, pour l'éditeur.

## Une sélection, sous un titre

Deux compositions à insérer où l'on veut (Compositions → Les Éditeurs réunis), pour mettre en valeur plusieurs livres
sous un même titre éditorialisé, avec un texte qui explique quelque chose, comme un article, et « À voir, à écouter »
(une vidéo, un épisode du podcast, un article de l'encyclopédie) : `selection-table` (la table, sur un aplat : le
texte à gauche, les livres en liste à droite) et `selection-etagere` (l'étagère, sur le blanc : l'article sur trois
colonnes, les livres debout sur un filet, la vidéo sur une bande pervenche). « Je garde A et B, les deux comme bloc
WordPress que l'on peut charger » (André, 21 nov. 2026). Le contenu inséré est l'exemple du canevas, « Avant l'orage,
1901 – 1918 » : on le remplace dans l'éditeur ; le prix et « Ajouter au panier » de chaque livre se lisent dans la
librairie. L'image de la vidéo se remplace par une image de la vidéo. Au téléphone, la table montre les livres
d'abord et replie l'article sous « Voir plus » ; l'étagère se fait glisser de côté.

Pour une page faite de modules pleine largeur, le gabarit « Page en modules, pleine largeur » (`page-modules`). La
bêta en montre une : `/librairie/selections/`.

## Les pages d'exemple de la bêta

Pour voir la bêta en entier, les maquettes du canevas sont posées en pages, telles quelles (`semer.php`, avec le HTML
et les images de `plugin/…/donnees/exemples/`), sous un bandeau pervenche pâle « Page d'exemple » : elles seront
refaites en blocs. Deux gabarits les accueillent : `page-exemple`, et `page-exemple-librairie` (avec les rayons de la
librairie). Au-dessus de 1 100 px elles s'étirent ; au-dessous, la maquette entière est réduite à l'écran
(`assets/js/exemple.js`).

- `/librairie/encyclopedie/`, et dessous `age-d-argent/`, `soljenitsyne/`, `platonov/` : l'encyclopédie, un chapitre,
  deux pages d'auteur ;
- `/ymca-press/histoire/` : Un siècle d'histoire, dans la version retenue : « en pages » (dix chapitres en quatorze
  pages de même hauteur), dans la variante chaude, celle du patrimoine (décidé le 21 nov.). La barre noire de chaque
  page reste en bas de l'écran pendant qu'on la lit, ses flèches mènent à la page d'avant et à celle d'après, et la
  fiche d'un auteur souligné s'ouvre au survol ou au clavier (`style.css`, « Notre histoire, en pages ») ;
- `/centre-culturel/videos/`, et dessous `une-video/` ; `/centre-culturel/paris-emigration/`.

## Les livres

La fiche d'un livre montre, sous le rayon, la langue et l'auteur, ce que l'extension garde du livre : traduction,
éditeur, collection, parution, pages, format et ISBN (le champ « GTIN, UPC, EAN ou ISBN » de WooCommerce) ; une ligne
vide ne paraît pas (le bloc `er/fiche-champs`, `plugin/…/inc/fiche.php`). La référence `WIX-…` d'un livre venu de Wix
ne paraît pas.

Les couvertures : une image d'au moins 800 px de haut est bonne ; de 450 à 800 px, passable (montrée à sa taille sur
la fiche, jamais agrandie) ; en dessous, ou sans image, le thème montre une couverture typographique, l'auteur, le
titre et l'éditeur sur le papier du système (sur l'aplat brun pour YMCA-Press), dans les grilles et sur la fiche
(`er_couverture_typo()`, dans `functions.php`). Le relevé est dans Produits → Couvertures (les livres en stock d'abord,
avec un lien pour changer l'image), et en ligne de commande, `wp er couvertures` (le workflow « Déployer chez Kinsta »,
case « Relevé des couvertures », en donne les comptes).

Un livre est un produit WooCommerce (Produits → Ajouter) :

- son **rayon** est une catégorie de produit (Littérature russe, Poésie, Histoire, Essais et biographies, Cinéma) ;
- ses **mentions** sont des étiquettes (Nouveauté, Nouvelle édition, Nouvelle traduction, Très rare), montrées en
  pastilles sur la couverture ;
- son **auteur** et sa **langue** sont deux classements de l'extension, chacun avec sa page (« Tous les livres de
  Gouzel Iakhina ») ;
- un livre sans prix n'affiche rien et ne se commande pas ; un livre épuisé affiche « Rupture de stock ».

Les couvertures de l'ébauche sont reprises de la maquette (en petite définition) ou dessinées « couverture à
venir » ; les présentations sont des brouillons d'une phrase.

## Les rencontres du centre

Une rencontre (conférence, présentation, lecture, exposition…) est un contenu à part, « Événements » dans
l'administration (`plugin/…/inc/evenements.php`), à l'adresse `/centre-culturel/evenements/<slug>/`. On y saisit, dans
la boîte « La rencontre » : le début (et la fin, pour ce qui dure plusieurs jours), le lieu (vide : au centre), la
forme, la langue, l'entrée (libre, gratuite sur inscription, payante), le prix et le nombre de places, l'état (prévue,
complète, annulée), les **vidéos et enregistrements** (un lien par ligne : YouTube, Vimeo, SoundCloud, Dailymotion,
Spotify, Facebook ou un fichier de la médiathèque ; « Titre | lien » pour leur donner un titre) et les livres liés
(leurs références). Le cycle (Les Mercredis des Éditeurs, Поэт и время…) et les intervenants sont deux classements ;
un intervenant qui est aussi un auteur de la librairie renvoie à sa page, et ses livres en stock s'ajoutent à « À la
librairie ». L'affiche est l'image mise en avant ; le chapô, l'extrait.

- **La page d'une rencontre** (le gabarit `single-er_evenement` du thème) : l'affiche à gauche ; à droite la forme et
  le cycle, le titre, le chapô, les lignes Quand, Où, Entrée, Avec, Langue, puis « Réserver » (ou « S'inscrire »,
  « Complet », « Annulée », « Cette rencontre a eu lieu le… ») ; le texte ; « Revoir, réécouter », les vidéos en
  lecteur (YouTube sans témoins tant qu'on ne lance pas la vidéo ; un lien quand le site ne se lit pas, Facebook par
  exemple) ; les livres.
- **La liste**, sur la page « Nos événements » (le bloc `er/evenements`, dans le gabarit « Page large ») : à venir,
  en lignes (cette semaine, la semaine prochaine, plus tard), puis les rencontres passées en affiches, par année, avec
  « Avec une vidéo » et les cycles en pilules ; une vidéo, une annulation sont marquées sur l'affiche. Au téléphone,
  les pilules glissent de côté.
- **La billetterie** est dans WooCommerce : une rencontre payante (avec un prix) ou sur inscription a son billet, un
  produit virtuel caché de la librairie, tenu à jour à chaque enregistrement (le nom, le prix, les places : changer
  le nombre de places décale le stock d'autant). On le prend depuis la page de la rencontre ; sa page à lui y
  renvoie. Une rencontre passée, annulée ou complète ne se réserve plus. Les commandes sont dans WooCommerce →
  Commandes, comme les livres.
- **La recherche** trouve les rencontres (le groupe du centre), par leur titre, leur chapô, leurs intervenants et leur
  cycle.
- **Les anciennes adresses** de Wix (`/events-1/<slug>`) mènent à la rencontre, une fois la bascule faite.

Les rencontres de l'ancien site s'importent par le workflow « Déployer chez Kinsta », case « ancien_site » (avec le
journal, les vidéos et les podcasts) : il lit
les pages des événements de Wix et la chaîne YouTube du centre (`outils/wix/pages.py`), les convertit
(`outils/wix/evenements.py` : le texte riche de Wix en HTML, la typographie française, les doublons de Wix réunis,
les fins fausses ôtées) et les importe (`wp er evenements importer`). Les vidéos sont rattachées à leur rencontre
d'après leur titre (les noms, les œuvres, les dates en commun, et le programme d'un cycle pour ses séances) : seules
les correspondances sûres le sont, les autres vont au rapport (`rapport.md`, sur la branche `wix-pages`) pour être
rattachées à la main. Relancé, l'import met à jour sans doublon, sans toucher à une rencontre modifiée à la main
depuis, et ajoute les vidéos sans en retirer : on le relance avant la bascule pour les dernières rencontres. Les
chiffres de vente de Wix ne sont jamais lus.

## Les étiquettes : les personnes et les sujets

Tout le site s'étiquette de la même façon : les livres, les articles du journal, les rencontres, les vidéos et les
podcasts, les pages (`plugin/…/inc/sujets.php`).

- **Les personnes** n'ont qu'une étiquette, leur nom d'auteur (le classement des auteurs des livres), et une seule
  page, `/auteur/<slug>/`. Sur un livre, c'est celui qui l'a écrit ; sur un article, une rencontre, une vidéo, une
  page, ce sont les personnes qui y sont liées (on en parle, elles l'ont écrit ou y interviennent). Un livre qui
  parle de quelqu'un le porte dans « Autour de » (une boîte de la fiche du livre, où l'on choisit des auteurs). Le
  compte d'un auteur reste son nombre de livres.
- **Les sujets** (l'émigration, la poésie, le Goulag…) ont leur page, `/sujet/<slug>/`. Un sujet n'est jamais une
  personne : celui qui porterait le nom d'un auteur est refusé (« choisissez-le dans Auteurs »). Les étiquettes
  WordPress des articles laissent la place aux sujets.
- **La page d'une personne** se lit comme une page d'encyclopédie (`inc/auteurs.php`) : le portrait, le nom et ses
  autres graphies, les dates (naissance, mort), la présentation (le champ « Description »), la biographie (un éditeur
  de texte, avec ses intertitres et ses images ; la suite se déplie), des photos avec leur légende, « Pour aller plus
  loin » (des liens, et la page de l'encyclopédie qui lui est liée, qui mène à son tour vers elle). Tout se saisit
  dans Produits → Auteurs → Modifier. Dessous : ses livres, les livres autour de lui, les rencontres (à venir, puis
  passées), les vidéos et les podcasts, les articles du journal, les pages qui le concernent. La page de Soljenitsyne
  donne ainsi ses livres, les podcasts sur L'Archipel, les billets, les rencontres.
- **La page d'un sujet** donne tout ce qui le porte, de la même façon.
- **Sous un article, une rencontre, une vidéo** : ses auteurs et ses sujets en pilules, puis « À lire, à revoir »,
  les contenus qui partagent le plus d'étiquettes (un auteur en commun compte plus qu'un sujet). La fiche d'un livre
  a les lignes « Autour de » et « Sujets ».
- **La recherche** trouve les sujets, et les contenus par leurs étiquettes.

Les imports posent des étiquettes d'eux-mêmes, sans jamais en ôter : les auteurs de la librairie que nomme un titre
(« avec Georges Nivat » : un intervenant ; « le Docteur Jivago de Boris Pasternak » : un auteur lié ; les noms russes
se reconnaissent déclinés, « с Андреем Звягинцевым »), et des sujets de départ reconnus par des mots du titre et du
chapô (Poésie, Émigration, Goulag et dissidence, Orthodoxie, Histoire, Musique, Cinéma, Arts, Traduction,
Philosophie, Ukraine, Théâtre) : une liste à reprendre à la main (Articles → Sujets). `wp er sujets deviner` (ou le
workflow, case « er » : `sujets deviner`) les repose sur ce qui est déjà en place ; `--simulation` dit ce qu'il
poserait.

## Les vidéos et les podcasts

Un contenu à part, « Vidéos et podcasts » dans l'administration (`inc/medias.php`), à l'adresse
`/centre-culturel/videos-et-podcasts/<slug>/` : le lien (YouTube, SoundCloud, un fichier audio… ; le premier est
le principal), le genre (vidéo ou podcast), la durée, la série (Les podcasts à livre ouvert, Les Secrets de
l'Archipel du Goulag…), les étiquettes. Sa page : le titre, le lecteur, le texte, les étiquettes, « À lire, à
revoir ». Le bloc `er/medias` donne la liste, par série (les épisodes dans l'ordre) : à poser sur « Nos vidéos »
(`{"genre":"video"}`) ou « Nos podcasts » (`{"genre":"podcast"}`), qui sont encore les maquettes. L'enregistrement
d'une rencontre reste sur la page de la rencontre.

## Le journal

Un article garde sa signature (« Par Yves Hamant », qui mène à sa page d'auteur s'il en a une), ses rubriques (les
catégories : Les Billets d'Yves Hamant…), ses auteurs et ses sujets. Les articles du blog de Wix s'importent avec le
reste de l'ancien site (`outils/wix/journal.py`, `wp er journal importer`) : le texte tel que la page de Wix
l'affiche, remis en blocs de WordPress (paragraphes, intertitres, listes, citations, images et légendes, vidéos), les
images dans la médiathèque, la date et la signature ; les mots-clés de Wix deviennent des auteurs (un nom de
personne) ou des sujets. Les anciennes adresses `/post/<slug>` mènent à l'article.

## Trois langues

Le site est en français, en russe et en anglais. Le français est la langue de référence : tout le texte du code est
en français.

- **L'interface** (l'en-tête, le pied, les menus, les boutons, les étiquettes, et pour l'instant les textes de
  l'accueil) passe par les fonctions de traduction de WordPress. Les traductions sont dans
  `theme/editeurs-reunis/languages/` et `plugin/er-librairie/languages/`, tirées de `outils/traductions.py` :
  l'anglais est prêt, le russe est un premier jet à faire relire par un russophone de la maison.
- **Le contenu** (les pages, les articles, les livres, les rayons, les auteurs) se traduit dans WordPress avec
  Polylang Pro et son extension pour WooCommerce (le Business Pack) : les adresses `/ru/` et `/en/`, une page par
  langue, le stock d'un livre partagé entre ses trois versions, l'anglais tiré par DeepL. `wpml-config.xml` dit à
  Polylang que les auteurs et les langues des livres se traduisent (Солженицын, Soljenitsyne, Solzhenitsyn).
- **Le sélecteur** « FR · RU · EN » est au bout gauche de la barre noire. Sans Polylang (en local, dans l'aperçu),
  il ajoute `?lang=ru` ou `?lang=en` à l'adresse : la langue de l'interface change pour toute la visite, le contenu
  reste en français.
- Les compositions désignent les pages, les livres, les rayons et les mentions par leur chemin ou leur slug
  français (`er_lien( 'ymca-press/histoire/' )`, `er_livre(…)`, `er_rayon(…)`, `er_mention(…)`) : avec Polylang,
  ces fonctions rendent l'adresse de la traduction dans la langue de la page.

Après un changement de texte dans le thème ou l'extension : refaire les deux `.pot` (la commande est en tête de
`outils/traductions.py`), ajouter les traductions au script, le relancer.

## Le catalogue de la boutique Wix

`outils/catalogue/convertir.py` convertit l'export des produits de Wix (Catalogue → Produits → Exporter,
`catalog_products.csv`) en un fichier pour l'importateur de WooCommerce (Produits → Importer) : le titre et les
auteurs tirés du nom (« Titre - Auteur »), le traducteur, l'éditeur, la collection et l'année tirés des premières
lignes de la description quand elles sont nettes, les rayons, la langue et les mentions tirés des catégories et des
rubans, les couvertures par leur adresse chez Wix, le poids en grammes ; jamais le prix d'achat. Chaque livre a pour
référence `WIX-` et le début de son identifiant Wix : réimporter avec « Mettre à jour les produits existants » met à
jour le prix et le stock sans doublon. Les pages et le format, tirés aussi des descriptions, sont repris à part dans
`complement-pages-format.csv` (la référence et ces deux champs), à importer en « mettant à jour » un catalogue déjà
en place. Les colonnes Auteurs, Langues, Rayons, Mentions et Slug sont lues par
l'extension (`inc/import.php`) ; un livre d'exemple de `semer.php` est remplacé par son équivalent Wix. La conversion
écrit aussi `a-revoir.md` (titres douteux, livres sans rayon, graphies réunies) et `echantillon.md`.

Le fichier de Wix et le fichier converti ne vont jamais dans ce dépôt, qui est public.

## Déployer chez Kinsta

Le site est hébergé chez Kinsta (formule Single 20GB, centre de données de Paris). Chaque poussée sur `main` qui
touche `theme/` ou `plugin/` met le thème et l'extension en ligne par SSH (`.github/workflows/kinsta.yml`), puis vide
le cache. Chaque passage installe et active aussi les extensions dont le site a besoin : WooCommerce, Stripe
(WooCommerce Stripe Payment Gateway) et la nôtre ; une extension à ajouter au site s'ajoute dans le workflow. Le
contenu (pages, livres, réglages) se modifie dans l'administration de WordPress, jamais par ce dépôt.

Pour brancher le dépôt sur Kinsta, une fois :

1. Sur le Mac, créer une clé réservée au déploiement, sans phrase de passe :
   `ssh-keygen -t ed25519 -N "" -C "github-editeurs-reunis" -f ~/.ssh/kinsta-editeurs`
2. MyKinsta → Paramètres de l'utilisateur → Clés SSH : ajouter la clé publique (`pbcopy < ~/.ssh/kinsta-editeurs.pub`).
3. GitHub → le dépôt → Settings → Secrets and variables → Actions :
   - le secret `KINSTA_CLE` : la clé privée (`pbcopy < ~/.ssh/kinsta-editeurs`) ;
   - les variables `KINSTA_HOTE`, `KINSTA_PORT` et `KINSTA_UTILISATEUR` : dans MyKinsta → le site → Infos → Accès
     SFTP/SSH.
4. Actions → « Déployer chez Kinsta » → Run workflow, « installer » coché : active WooCommerce, l'extension et le
   thème, télécharge le français, le russe et l'anglais, ferme le site aux moteurs de recherche et sème le contenu
   de l'ébauche. Relancé, `semer.php` ne crée rien en double.

Tant que les variables ne sont pas posées, le déploiement ne fait rien. La bêta est fermée aux moteurs de recherche
(`blog_public` à 0) : le jour de la mise en ligne, Réglages → Lecture, décocher « Demander aux moteurs de recherche
de ne pas indexer ce site ».

## Installer sur l'hébergement définitif

1. Installer WordPress en français, puis WooCommerce ; ajouter le russe et l'anglais (Royaume-Uni) dans Réglages →
   Général, pour que WordPress et WooCommerce téléchargent leurs traductions.
2. Apparence → Thèmes → Ajouter → Téléverser `apercu/editeurs-reunis.zip`, puis l'activer.
3. Extensions → Ajouter → Téléverser `apercu/er-librairie.zip`, puis l'activer.
4. Pour partir du contenu de l'ébauche : `wp eval-file wp-content/plugins/er-librairie/donnees/semer.php`.
5. Réglages → Permaliens : « Titre de la publication ».
6. Installer Polylang Pro et Polylang for WooCommerce ; créer les langues français (par défaut), русский et English
   (en_GB), l'adresse de la langue dans le chemin (`/ru/`, `/en/`), le français sans préfixe ; brancher la clé
   DeepL dans Langues → Réglages → Traduction automatique.
