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
- `tests/`, `outils/verifier.sh`, `phpcs.xml.dist`, `ruff.toml` : le contrôle qui précède chaque déploiement
  (« Vérifier le code »).

## La direction artistique est provisoire

Elle reprend la maquette Wix : Montserrat, la pervenche `#9DB5F5`, la barre noire, les boutons en pilule. Pour la
changer, on touche à `theme.json` et à `style.css`, et seulement à eux : les sections et les gabarits n'ont aucune
couleur ni taille écrite en dur.

## Le plein écran

Retenu le 24 novembre 2026 (« code tout sauf garde le vieux logo », André). Le site occupe toute la largeur de
l'écran : `theme.json` donne aux blocs larges (`alignwide`) 100 % de la page, et à la page des marges de
`clamp(1.25rem, 6vw, 5rem)` (80 px sur un grand écran). Les grilles de livres passent à six colonnes à partir de
1 280 px et à huit à partir de 1 600 px ; le texte courant garde sa largeur de lecture (`contentSize`). Tous les
gabarits en profitent sans rien y changer.

## L'en-tête : trois maisons, trois couleurs

Retenu le 21 novembre 2026, aéré le 24 (le « réglage 2 » du canevas « Les Éditeurs réunis — plein écran »). Trois
étages, du haut vers le bas :

1. **La barre d'informations** (40 px, noire, en petit gris) : si la librairie est ouverte en ce moment et jusqu'à
   quelle heure, sinon le jour et l'heure de sa réouverture ; l'exposition en cours ; la prochaine rencontre de la
   semaine (« Ce soir au centre », « Demain au centre »…) ; l'adresse et « Nous trouver ». Les horaires se règlent
   dans Réglages → Général, champ « Horaires de la librairie », un jour par ligne (`mardi 10:00-19:00`) ; l'exposition
   et les rencontres sont lues dans les contenus du centre. L'heure se calcule dans le navigateur, à l'heure de Paris
   (`assets/js/entete.js`), parce que Kinsta garde les pages en cache. En dessous de 1 100 px, l'adresse s'en va. Au
   téléphone, les informations passent l'une après l'autre en **relais** (24 nov., la façon 4 de la page « Les
   informations au téléphone » du canevas) : la suivante arrive par la droite et pousse l'autre, comme les sujets de
   la une, et un trait gris, au pied de la barre, avance en quatre secondes. Un titre trop long finit en points de
   suspension ; dans l'adresse, c'est le nom de la rue qui se raccourcit, « Nous trouver → » reste entier. Si le
   système demande moins d'animations, la barre garde l'ouverture seule.
2. **La barre noire** (88 px, 64 au téléphone), la même partout : le nom dans l'espacement du logo d'origine (0,95 em),
   les trois maisons (Librairie, Maison d'édition YMCA-Press, Le centre culturel), chacune précédée d'un point de sa
   couleur, puis le coin droit en pictos (disposition A′, retenue le 21 nov.) : la loupe, le compte, le panier, chacun
   dans un rond de 36 px (au survol, le rond grisé ; sélectionné, le rond blanc et le picto noir), le nombre de livres
   dans une pastille pervenche quand le panier n'est pas vide, et les trois langues dans un interrupteur. Entre 1 280
   et 1 440 px, « Maison d'édition YMCA-Press » devient « YMCA-Press » ; au téléphone, la barre garde la loupe et le
   panier, le compte et la langue vont au bas du menu (≡).
3. **La ligne de la maison** (48 px), sur sa couleur : vert rayonnage pour la librairie (ses rayons, avec leurs
   tiroirs), brun reliure pour YMCA-Press, bleu nuit pour le centre culturel. La page où l'on est s'écrit en blanc et
   en demi-gras ; l'accueil du site montre la ligne de la librairie, « Accueil » en blanc.

**Un clic sur une maison n'ouvre pas sa page d'accueil** (règle du 24 nov.) : il montre sa ligne à la place de celle
de la page, et l'onglet passe en blanc. L'accueil d'une maison est la première entrée de sa ligne. Le menu (≡) du
téléphone garde ses liens. Les trois lignes sont dans la page (`[er_lignes]`, `inc/entete.php`) ; celles des autres
maisons attendent, cachées.

**En défilant** (la manière 2 du canevas, choisie le 24 nov.) : la barre d'informations et la barre noire s'en vont,
la ligne de la maison reste en haut de l'écran, et prend à son bout la loupe et le panier. La loupe ouvre la recherche
sur place, sous la ligne, sans faire remonter la page ; le panier ouvre le volet du panier de WooCommerce, avec la même
pastille que celui de la barre noire. La bande de page (le troisième niveau, ci-dessous) s'accroche juste dessous.

**La tête de page** (retenue le 24 nov. 2026 ; le canevas, page « La tête de page ») : la même partout, entre la
ligne de la maison et la bande de page, sur la même teinte que la bande, qui la ferme ; un filet les sépare. En
haut, le fil (le point de la maison, le chemin en petites capitales : « Librairie / Littérature ») et, en face, les
faits (« 169 rencontres depuis 2018 », « 378 livres », « Depuis 2020 · 68 textes ») ; puis le titre sur 7 colonnes et le
chapô sur 5, alignés par le bas (sans chapô, le titre prend la largeur) ; pour une personne, son portrait sur 2
colonnes, ses graphies et ses dates sous son nom. La page dit son nom en russe, en très grand, à 7 % de la couleur de
la maison, coupé par le bas : la méta `er_nom_russe` d'une page ou d'un terme, sinon sa traduction russe (Polylang),
sinon le nom de famille cyrillique d'une personne, sinon celui des pages et des rayons de la maquette ; rien s'il n'y
en a pas. Une page sans maison (un sujet, les pages communes) : le fond doux, les trois points. En défilant, la tête
s'en va et la bande s'accroche.

- Le composant : `er_tete_page()` et le bloc `editeurs-reunis/tete` (`inc/tete.php`, la section « La tête de page »
  de `style.css`), dans les gabarits Page, Page large, Promos, les archives et la recherche, la composition de la
  librairie (boutique, rayons, personnes, éditeurs, collections, langues, mentions) et celle d'un sujet ; le journal
  (`er_journal_tete()`) et Nos expositions (`er_expo_bloc_liste()`) l'appellent eux-mêmes.
- Le chapô d'une page est son résumé (le champ « Extrait » de l'éditeur, ouvert aux pages). `wp er tetes [appliquer]`
  y déplace le premier paragraphe d'une page qui n'en a pas (s'il est court et n'est pas celui d'une ébauche) ; fait
  le 24 nov. sur la bêta pour Nos événements, Le centre culturel, YMCA-Press et Les Amis d'YMCA-Press. Les faits d'une
  page passent par le filtre `er_tete_faits` (Nos événements les compte).
- Pas de tête sur l'accueil, la fiche d'un livre, un article, une rencontre, une exposition, une vidéo ni les pages
  d'exemple, qui ont leur ouverture à elles.

**La bande de page, le troisième niveau** (retenue le 24 nov. 2026 ; le canevas, page « Le troisième niveau ») : la
même sur toutes les pages qui ont au moins trois parties. Sous le titre de la page, une bande de 48 px dans la teinte
pâle de la maison (`theme.json`, « Teinte · ») : un ton plus bas à chaque niveau, le noir de la barre, la couleur de la
ligne, sa teinte, le papier. Les entrées en 14 px comme la ligne ; celle où l'on est à l'encre, soulignée d'un trait de
2 px dans la couleur de la maison ; un groupe, après un filet, sous son titre en petites capitales (« Passées ») ; un
groupe de trop replié en menu (« Par époque », « Les cycles », « Plus anciennes » : le seul menu déroulant du site) ;
à droite, une action facultative. En défilant, elle monte avec la page, se pose sous la ligne avec une ombre d'un
pixel, et le nom de la page s'y glisse à gauche quand il y a la place. Dans un sommaire, le trait suit la lecture. Au
téléphone, 44 px, sans le nom ni l'action ; elle défile de côté et garde l'entrée courante en vue. Une page sans maison
(un sujet) l'a sur le fond doux, avec les trois points, accrochée seule en haut.

- Le composant : `er_bande_page()` (`inc/bande.php`), `assets/js/bande.js`, la section « La bande de page » de
  `style.css`. Les années d'une liste : `er_bande_annees()` (les quatre dernières dépliées).
- Les pages : un rayon (`er_bande_rayon()` : tout le rayon, le premier groupe de sous-rayons déplié, les autres
  repliés), la page d'une personne (sa biographie, ses livres, ses traductions…, ce qui l'entoure :
  `er_autour_sommaire()`), un sujet, Nos événements (`er_evt_bande()`), Nos expositions, Nos vidéos et Nos podcasts
  (le bloc `er/medias`), le journal, et toute page de texte qui a trois intertitres (un sommaire automatique, ce qui
  précède les deux-points ; « Chapitre 03 · 1940 – 1945 » donne son année : Un siècle d'histoire).
- Pour que la bande s'accroche tout le long, elle doit être l'enfant direct d'un contenant qui va jusqu'au bout de la
  page (le `main` du gabarit, le contenu de la page, la liste) : sur la page d'une personne, la fiche est coupée en
  deux (`er/auteur-fiche`, `{"partie":"tete"}` puis `{"partie":"suite"}`) et « Autour » est passé dans le `main`.
- `wp er bande [<chemin>…]` (le workflow, champ « Une commande de l'extension » : `bande`) montre la bande des pages
  telle que le site la sert.

**Les interrupteurs** (24 nov. : « *les boutons fermer se trouvent au même endroit exact que ceux qui ouvrent, comme un
interrupteur on off* ») : la loupe, le panier et le menu du téléphone (≡) ouvrent leur panneau sous la barre (ou sous
la ligne accrochée) d'où on les a touchés ; eux restent à leur place, passent au rond blanc, et leur picto devient une
croix : on referme en touchant au même endroit (`entete.js`, `recherche.js`). Un clic à côté et Échap referment aussi.
Les boutons « Fermer » des panneaux (la croix du menu de WordPress, celle du volet de WooCommerce, celle de la
recherche) ne servent plus qu'au clavier : on ne les voit que lorsqu'ils ont la main. Tout est en CSS
(`position: sticky`) ; `entete.js` ne fait que mesurer les hauteurs et dire quand l'en-tête est accroché.

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

**Algolia** (`plugin/…/inc/algolia.php`, sans extension de plus) : chaque ligne écrite ou ôtée de `er_index` l'est
aussi chez Algolia, en un envoi à la fin de la requête ; la recherche interroge Algolia avec la clé de ce qu'on tape
(les trois écritures réunies), et Algolia y ajoute la tolérance aux fautes de frappe et ses statistiques (ce que les
visiteurs cherchent, ce qui ne donne rien). Si Algolia ne répond pas ou refuse (le quota gratuit de 10 000 recherches
par mois épuisé), la recherche repasse sur la table, et laisse Algolia en paix cinq minutes. Les réglages sont dans
Réglages → Recherche : l'identifiant de l'application, la clé de recherche (publique), la clé d'écriture (la « Write
API Key » de l'application Algolia, qui ne sert qu'au site ; une clé limitée devrait avoir `addObject`, `deleteObject`,
`deleteIndex` pour vider l'index, `settings` et `editSettings` ; jamais la clé d'administration), l'index (`er_site`),
et la case « Chercher chez Algolia », à cocher une fois l'index
envoyé (le bouton de la même page, ou le workflow, champ « boutique » : `algolia indexer`, qui refait d'abord l'index du
site). `algolia` seul dit où on en est. Le panneau et l'API ne changent pas : les contenus ajoutés par le centre à
l'index (les fonctions `er_index_*`) vont chez Algolia d'eux-mêmes.

## L'accueil, à la une

L'accueil du site (`templates/front-page.html`) est fait des modules de la maquette « L'accueil, à la une » du canevas,
chacun une composition en blocs de WordPress, qu'on modifie dans l'éditeur de site (21 nov. 2026 : « garde ces
modules stp sauf le dernier [l'index], regarde les modifications et ajoute-les au site », André).

Le premier module, depuis le 24 nov., est **la une écran par écran** (`une-ecrans`) : L'Archipel du Goulag, Le Docteur
Jivago et Tchevengour, chacun un écran coupé en deux, à gauche sur l'aplat de sa maison la rubrique, le titre, la phrase
et deux boutons, à droite la photo des archives ou le dessin. Les trois **défilent** sur un seul écran, qui tient la
fenêtre sous l'en-tête : un sujet toutes les sept secondes, qui arrive par la droite (`assets/js/une-ecrans.js`). Le
sommaire, au pied, reste en place : un trait avance sur le filet au-dessus du sujet en cours, un clic mène à un sujet,
« Pause » arrête le défilement ; au téléphone, on fait glisser du doigt. Le défilement attend pendant qu'on parcourt le
module au clavier, et ne part pas de lui-même si le système demande moins d'animations ; sans JavaScript, les trois
sujets se suivent l'un sous l'autre. Suivent le journal, le poème, les vidéos et l'encyclopédie. Les trois affiches longues d'avant restent dans `patterns/`, pour l'éditeur :

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

La fiche d'un livre est coupée en deux moitiés (la version C du canevas, retenue le 24 nov. 2026) : à gauche, sur le
papier, la couverture, qui reste à l'écran pendant qu'on lit ; à droite, le fil d'Ariane, les mentions, le titre en
grand, l'auteur, le prix, le panier, la présentation et les renseignements. Au téléphone, la couverture passe au-dessus.

Elle montre, sous le rayon, la langue et l'auteur, ce que l'extension garde du livre : traduction,
éditeur, collection, parution, pages, format et ISBN (le champ « GTIN, UPC, EAN ou ISBN » de WooCommerce) ; une ligne
vide ne paraît pas (le bloc `er/fiche-champs`, `plugin/…/inc/fiche.php`). La référence `WIX-…` d'un livre venu de Wix
ne paraît pas.

Les couvertures : une image d'au moins 800 px de haut est bonne ; de 450 à 800 px, passable (montrée à sa taille sur
la fiche, jamais agrandie) ; en dessous, ou sans image, le thème montre une couverture typographique, l'auteur, le
titre et l'éditeur sur le papier du système (sur l'aplat brun pour YMCA-Press), dans les grilles et sur la fiche
(`er_couverture_typo()`, dans `functions.php`). Le relevé est dans Produits → Couvertures (les livres en stock d'abord,
avec un lien pour changer l'image), et en ligne de commande, `wp er couvertures` (le workflow « Déployer chez Kinsta »,
case « Relevé des couvertures », en donne les comptes).

Sous la fiche, trois rangées tirées des données du site (la piste B, retenue le 23 nov. 2026 ; les blocs `er/fiche-*`,
`plugin/…/inc/recommandations.php`, et leur section de `style.css`) : « Du même auteur » dans la colonne de la fiche
(l'auteur, son portrait ou l'initiale de son nom en cyrillique sur le vert de la librairie, quatre de ses livres) ;
« Autour de ce livre », une bande pervenche pâle (les personnes et les sujets en pilules, la page de l'encyclopédie,
les livres sur l'auteur, ceux des personnes dont parle le livre et ceux qui partagent ses sujets, une rencontre, un
article ou une vidéo qui les portent) ; « Du même rayon, de la même époque », l'étagère (le genre et l'époque
ensemble, à défaut l'un ou l'autre). Un livre ne paraît qu'une fois, les livres en stock d'abord ; un bloc vide ne
paraît pas. « Autour de ce livre » dépend des étiquettes : il se remplit à mesure qu'on pose les sujets et « Autour
de » sur les livres. `wp er voisins <slug>` (le workflow, champ « boutique ») montre les trois rangées d'un livre.

Les vignettes des auteurs (la recherche, la fiche d'un livre, la lettrine d'un article dans « Autour de ce livre »)
montrent toujours une initiale en cyrillique (décision du 23 nov. 2026) : celle du nom écrit en cyrillique s'il est
connu (le nom ou ses « Autres graphies »), sinon le début du nom latin transcrit à la française — Б pour Brodsky,
Ш pour Chalamov, Ч pour Tchekhov, Ж pour Jivago. Une seule fonction pour tout le site, `er_initiale_cyrillique()`
(`plugin/…/inc/recherche.php`) : toute nouvelle vignette d'initiale s'en sert (les initiales des signatures du journal,
côté centre, aussi).

**Les titres liés** (décision du 24 nov. 2026, pour tout le site) : dans chaque texte, le titre d'un livre de la
librairie devient un lien vers sa fiche, et sa vignette paraît au survol (la couverture, la mention, le titre,
l'auteur, le prix ; au clavier aussi ; au toucher, le lien suffit). `plugin/…/inc/liens-livres.php`,
`assets/js/vignettes.js`, et leur section de `style.css`.

- Le sommier : les titres des livres en vente (ni les billets, ni les livres cachés), plus leur champ « Titres liés »
  (`er_titres_lies`, un par ligne : « Docteur Jivago », « Доктор Живаго ») ; `er_sans_lien` retire un livre de la
  règle. Un titre qui commence par un article se reconnaît aussi sans lui (« l'auteur du Docteur Jivago »).
- Un titre distinctif (trois mots pleins, ou deux mots à majuscule) se reconnaît partout ; les autres (« Tchevengour »,
  « Les Douze », « Tout passe ») seulement cités, seuls en italique ou entre « ». Le premier mot garde sa majuscule.
- Une fois par page, à la première mention ; jamais dans un lien, un titre h1 à h6, un bouton, du code, une légende,
  ni sur la fiche du livre lui-même. Deux livres au même titre : celui qui est en stock, sinon le plus récent.
- Les blocs de texte y passent d'eux-mêmes (paragraphe, élément de liste, vers, citation en exergue, tableau, extrait,
  contenu classique), dans les pages, les articles, les modèles et les compositions. **Un texte rendu hors des blocs
  y passe par le filtre** `apply_filters( 'er_lier_titres', $html )` : côté centre, la biographie d'une personne, une
  présentation, un chapô. Un endroit à laisser tel quel porte la classe `er-sans-liens`.
- `wp er liens` (le sommier, les titres qui se disputent) et `wp er liens <slug>` (les titres liés d'une page).

**Les traducteurs, les directeurs d'ouvrage et les éditeurs ont leur page** (décision du 24 nov. 2026 ;
`plugin/…/inc/traducteurs-editeurs.php`) :

- Un traducteur, un directeur d'ouvrage, un préfacier, celui qui établit une édition, un illustrateur est une
  personne : un auteur, avec la page d'une personne. Un livre le porte dans un classement caché tenu avec les noms des
  auteurs, comme « Autour de » : `er_traduction` (Traduction), `er_direction` (Sous la direction de), `er_preface`
  (Préface), `er_edition` (Édition établie par), `er_illustration` (Illustrations) ; une boîte chacun dans la fiche du
  produit. La fiche du livre en fait des liens. La liste des rôles est dans `er_roles_personnes()` : un rôle de plus
  s'y ajoute en une ligne.
- Les livres d'une personne sont ceux qu'elle a écrits ou auxquels elle a pris part : `er_personne_livres( $terme )`
  les donne rôle par rôle (`auteur`, `traduction`, `direction`, `preface`, `edition`, `illustration`, `autour`, et
  `tous`, qui laisse « Autour de » de côté). La recherche les compte : « 3 livres en rayon : 2 sous sa direction et une
  traduction » (Georges Nivat n'est l'auteur d'aucun livre du catalogue). **La page d'une personne**
  (`patterns/librairie.php`) montre, sous « Ses livres », « Ses traductions », « Sous sa direction », « Ses préfaces »,
  « Éditions établies » et « Ses illustrations », avec la même fonction ; qui n'a rien écrit qui soit en vente n'a pas
  de « Ses livres » vide (corrigé le 24 nov. : la page ne montrait que les livres écrits). Le compte d'un auteur, lui,
  reste son nombre de livres écrits.
- Un éditeur est un classement public, `er_editeur`, avec sa page, `/editeur/<slug>/` (sa présentation, ses livres ;
  le gabarit `taxonomy-er_editeur`). Les métas `er_traducteur` et `er_editeur` reprises de Wix ne servent plus que de
  secours.
- Une collection aussi : `er_collection`, à l'adresse `/collection/<slug>/` (le gabarit `taxonomy-er_collection` ;
  `plugin/…/inc/collections.php`). La fiche en fait un lien. `wp er collections` relève les collections du texte de
  Wix, nettoyées (sans numéro, sans parenthèses, sans la phrase qui suit) et regroupées par éditeur ;
  `wp er collections appliquer` les pose.
- Sous les renseignements, « Autres éditions » et « Les autres volumes » (`er_autres_editions()`) : les fiches du même
  auteur au même titre — même titre entier, une autre édition (la langue si elle diffère, l'éditeur, l'année) ; même
  titre avant le sous-titre, un autre volume.
- « Autour de ce livre » compte aussi les personnes des rôles, et les contenus qui citent le livre (un article, une
  rencontre, une page qui le porte dans ses livres, `er_reco_citants()`), qui passent devant.

**Retrouver une personne** (`er_personne_trouver()`, le 24 nov. 2026) : tout ce qui rattache un nom à une personne
(les rôles, les signatures du journal, les intervenants d'une rencontre, les expositions, l'import du catalogue, les
« Autour de ») passe par une seule fonction, qui essaie dans l'ordre le nom exact, le slug, les anciens slugs d'une
fiche fondue (`er_anciens`), puis la clé de recherche (sans accents, la transcription du russe ramenée à une forme,
« Autres graphies » comprises) quand elle ne désigne qu'une personne. Une adresse `/auteur/<ancien-slug>/` qui ne mène plus
nulle part renvoie (301) vers la personne qu'elle désigne.

- `wp er personnes fusionner` montre les doublons à fondre (la liste est dans `er_personnes_fusions()`,
  `plugin/…/inc/personnes.php` : Soljenitsyne et « А. Солженицын », Fiodor et Fedor Dostoïevski…) et les fiches qui
  mêlaient deux personnes (`er_personnes_separations()` : Tchekhov et Mizinova) ; `wp er personnes fusionner
  appliquer` les fond : les livres, les rôles, les étiquettes et les intervenants passent à la fiche gardée, l'autre
  graphie va dans ses « Autres graphies », l'ancien slug dans `er_anciens`. Un cas douteux ne s'ajoute à la liste
  qu'une fois tranché.
- `wp er personnes rattacher` relève les personnes nommées dans le titre ou le chapô d'un contenu, ou deux fois dans
  son texte, sans y être rattachées ; `appliquer` les rattache (« Autour de »). Les étiquettes relues à la main
  (`er_etiquettes_relues`) ne sont pas touchées ; une mention isolée est seulement listée, à trancher à la main.
- `wp er audit` (24 nov. 2026), en lecture seule : ce qui devrait se répondre et ne se répond pas, compté dans les
  données — personnes en double ou probablement en double, signatures et intervenants sous une autre graphie, personnes
  nommées dans un texte sans y être rattachées, rôles que la fiche ignore, collections sans page, même livre en
  plusieurs fiches, citations à sens unique, vidéos des rencontres absentes de la médiathèque, contenus isolés. Le
  lancer par le déploiement (champ « Une commande de l'extension » : `audit`) ; le rapport va sur la branche
  `wix-pages` (`commande.txt`). À relancer après chaque import.
- `wp er roles` relève dans le catalogue les traducteurs (la méta, « traduit du russe par … » dans le titre ou la
  présentation), les directeurs (« sous la direction de … » dans le titre), les préfaciers, les éditions établies, les
  illustrateurs et les éditeurs ; ce qui n'est pas un nom (un titre, une collection, un mot collé au nom par la perte
  des retours à la ligne de Wix) est écarté. `wp er roles appliquer` les pose (un nom inconnu devient un auteur).

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
  une vidéo ou par cycle, choisis dans la bande de page ; une vidéo, une annulation sont marquées sur l'affiche.
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
d'une rencontre reste sur la page de la rencontre, et la liste le reprend aussi, à la fin, sous « Les rencontres, à
revoir » (`er_media_rencontres()` : les rencontres dont un lien est une vidéo, ou un podcast pour « Nos podcasts ») ;
ainsi la médiathèque n'oublie plus les vidéos que Wix ne rangeait que sur la page de l'événement.

## Le journal

Un article garde sa signature (« Par Yves Hamant », qui mène à sa page d'auteur s'il en a une), ses rubriques (les
catégories : Les Billets d'Yves Hamant…), ses auteurs et ses sujets. Les articles du blog de Wix s'importent avec le
reste de l'ancien site (`outils/wix/journal.py`, `wp er journal importer`) : le texte tel que la page de Wix
l'affiche, remis en blocs de WordPress (paragraphes, intertitres, listes, citations, images et légendes, vidéos), les
images dans la médiathèque, la date et la signature. Les anciennes adresses `/post/<slug>` mènent à l'article.

Les mots-clés de Wix étaient trop bruyants (« Les Éditeurs Réunis » ou « Union Soviétique » pris pour des personnes,
la signature au nom du compte) : chaque article a été relu d'après son texte, dans
`outils/wix/journal-etiquettes.json` (une entrée par slug de Wix). On y trouve la vraie signature, les personnes dont
il parle (des auteurs du site, créés s'il le faut), ses sujets (le vocabulaire commun aux rencontres et aux livres),
les livres du catalogue qu'il évoque (par leur référence) et des mots-clés pour la recherche (graphies, noms propres,
termes en russe : « raskol », « vieux-croyants », « старообрядчество »). Ces mots ne s'affichent pas : ils sont dans
le champ `er_mots` et la recherche du site les lit, avec la signature. Un article relu porte `er_etiquettes_relues` :
ni l'import ni `wp er sujets deviner` n'y ajoutent d'étiquette devinée. Sous l'article, « À la librairie » montre les
livres qu'il cite, puis ceux des auteurs dont il parle (en stock, six au plus, ceux de la signature mis à part).

### La page du journal

Le journal se lit comme un journal en ligne (la piste D du canevas « Le journal du centre », d'après le système
« Les Éditeurs réunis — le système »). Chaque article a un **genre** (`er_genre` : chronique, critique, essai, récit,
entretien, hommage, archive, document), relu avec ses étiquettes (`genre` dans `journal-etiquettes.json`) ; le genre
et le sujet principal (le premier relu) font son surtitre, « Critique · Goulag et dissidence ».

- **L'en-tête du journal** (`er/journal-tete`) : la tête de page du site (le 24 nov., le bandeau bleu nuit du 23 a
  laissé la place à la teinte du centre), le nombre de textes, le titre et sa phrase, *Журнал* en grand et presque
  effacé, puis les genres dans la bande de page (et « En russe », les textes en russe ou bilingues, d'après `er_langues`), qui
  s'accroche sous la ligne du centre en défilant, comme sur toute page qui a ses parties.
- **La ligne de repères**, sous le bandeau, sur une seule ligne (ce qui dépasse défile de côté), selon l'onglet : à
  la une, le prochain rendez-vous au centre et le dernier épisode du podcast ; les plumes des chroniques, les
  témoins des récits, les auteurs des archives (avec leur nombre de textes) ; les livres des critiques, à la
  librairie ; les personnes dont parlent les essais, les entretiens et les hommages ; les sujets des documents ;
  pour un sujet, le sujet ailleurs sur le site et ses voisins ; en russe, le nombre de textes et le journal russe
  (avec Polylang) ; dans le fil, les années (`/journal/?annee=2023`).
- **La une** (`er/journal-corps`, à la première page) : le texte à la une, le dernier **épinglé** (« Mettre en
  avant » dans l'éditeur), sinon le plus récent qui a une image ; deux autres dessous ; à droite, le fil des six
  derniers, et sous le fil tous les sujets, avec leur nombre de textes. Puis, sur le fond doux, les chroniques (ceux qui signent au moins deux chroniques, leur portrait de
  fiche d'auteur ou leurs initiales, leur dernier texte) et le grand format (le plus récent des textes de vingt
  minutes ou plus) ; enfin les trois sujets les plus fournis, un texte mis en avant et trois titres chacun.
- **Les listes** : un genre (`/journal/genre/critique/`), un sujet (`/journal/?sujet=poesie`), les textes en russe
  (`/journal/?en=russe`), tout le fil (`/journal/?fil`), vingt par page.
- **Le titre** d'un article ne redit pas sa signature (« « Langue maternelle » par Georges Nivat » devient
  « « Langue maternelle » ») : elle est dessous, « Par Georges Nivat · 1er mai 2020 · 4 min ».

## Les expositions du centre

Un contenu à part, « Expositions » dans l'administration (`inc/expositions.php`), à l'adresse
`/centre-culturel/expositions/<slug>/`, d'après le canevas « Nos expositions » (24 nov. 2026). Une exposition a ses
dates (les mêmes champs que les rencontres, `er_debut` et `er_fin`), la ligne de ses artistes (« Madeleine Melot »,
« D’après leur correspondance » ; vide, ce sont ses auteurs), ses techniques (`er_technique` : peinture, gravure,
archives…), son lieu et son entrée, son image, son chapô, son texte, ses étiquettes (auteurs et sujets, comme le
reste), et ses **œuvres** : des images de la médiathèque, dont la légende fait le cartel (« Portrait n° 1, huile sur
toile, 2019 »).

**Les rattachements.** On y rattache des livres, des articles du journal, des vidéos et des podcasts, des rencontres
(le vernissage, la soirée de clôture) et des pages : depuis l'exposition (la boîte « Ce qui s'y rattache », qui
cherche dans tout le site), ou depuis le livre, l'article, la rencontre ou la vidéo (la boîte « Expositions », à
cocher). Les deux disent la même chose : une ligne `er_rattache` de l'exposition par contenu. Le lien se voit des
deux côtés : la fiche de l'exposition montre ce qui s'y rattache, chaque contenu montre l'exposition (sous l'article,
dans la page de la rencontre ou de la vidéo, dans la bande « Autour de ce livre » de la fiche d'un livre). Une
exposition paraît aussi sur la page de ses auteurs et de ses sujets, et dans la recherche (avec le centre).

- **La page « Nos expositions »** (`er/expositions`, posé sur la page par l'import, gabarit en modules) : la tête de
  page (le titre, sa phrase, combien d'expositions depuis quand) ; la bande de page (en ce moment, à venir, les expositions passées par année, les techniques repliées :
  `?annee=2023`, `?technique=peinture` ; à droite, les horaires) ; ce qui est accroché, ou annoncé, sur l'aplat du centre, *Выставка* en décor ; les
  expositions passées, une par une, comme des cartels (l'année, la technique, le titre, les artistes, les dates, ce
  qui s'y rattache en pastilles) ; trois entrées pour finir (la prochaine rencontre, le journal, venir).
- **La fiche** (`single-er_exposition.html`) : l'image et le cartel (les dates, le vernissage et la clôture, le lieu,
  l'entrée, le nombre d'œuvres), le texte et sa marge (le journal d'abord, puis le centre, la librairie, écouter et
  regarder), l'accrochage (six œuvres par rangée), les autres expositions.
- **Les titres** des expositions sont des titres d'œuvres : en italique.

L'ancien site n'avait que des événements : les huit expositions sont relues dans `donnees/expositions.json` (le
titre, les artistes et les personnes dont il est question, les techniques, les dates, les rencontres de l'ancien site
par leur identifiant de Wix, les articles par leur slug, les livres par leur référence). `wp er expositions importer`
les crée ou les complète (ce qu'on a saisi à la main l'emporte ; les rattachements s'ajoutent) : le texte, le chapô
et l'image viennent de la première rencontre. Une rencontre de l'ancien site qui dure des semaines, c'est l'exposition
telle qu'il l'annonçait : elle reste rattachée, mais la fiche ne la redit pas.

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

**Mettre Polylang en place** (la bêta, puis l'hébergement définitif) :

1. Extensions → Ajouter → Téléverser : Polylang Pro, puis Polylang for WooCommerce (les archives achetées chez WP
   Syntex ; payantes, elles ne vont jamais dans ce dépôt public). Ignorer l'assistant de Polylang. Le workflow
   « Déployer chez Kinsta » les active à chaque passage s'ils sont installés.
2. Le workflow, champ « boutique » : `langues installer` (`wp er langues installer`, `inc/langues.php`). Il crée le
   français (fr_FR, par défaut), le russe (ru_RU) et l'anglais (en_GB) ; met la langue dans le chemin, le français
   sans préfixe, l'accueil de chaque langue à `/ru/` et `/en/`, sans redirection d'après le navigateur ; recopie d'une
   traduction à l'autre les classements, les champs, l'image mise en avant, la date, l'ordre, la page parente et le
   gabarit ; passe en français tout le contenu qui n'a pas de langue. Il se relance sans dommage ; `langues` seul dit
   où on en est.
3. Langues → Réglages : la licence, et la clé DeepL (Traduction automatique). Tout le reste s'y modifie aussi.

Ce qui se traduit : les pages, les articles, les livres, les rayons et les mentions (Polylang pour WooCommerce), et ce
que dit `wpml-config.xml` (les rencontres, les auteurs, les langues des livres, les cycles, les intervenants, les
sujets). Les vidéos et les podcasts n'y sont pas : ils paraissent dans les trois langues. Les images non plus : une
couverture sert aux trois.

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

L'export de la boutique (Produits → Exporter) a les mêmes colonnes Auteurs, Langues, Rayons et Mentions que l'import :
un fichier exporté, corrigé puis réimporté en « mettant à jour » ne perd rien. Les billets des rencontres n'y sont pas.

**Les ISBN** manquent dans l'export de Wix. `outils/catalogue/isbn.py` les fait entrer par la référence `WIX-…` :

1. `python3 outils/catalogue/isbn.py modele import-woocommerce.csv` (ou un export de la boutique, avec au moins les
   colonnes UGS et Nom) écrit `isbn-a-remplir.xlsx` : Référence, Titre, Auteurs, Éditeur, Année, En stock, et la
   colonne ISBN à remplir, en texte (le tableur ne change pas 9782070368228 en 9,78207E+12). Les livres en stock
   d'abord.
2. Une fois rempli : `python3 outils/catalogue/isbn.py preparer isbn-a-remplir.xlsx --catalogue
   import-woocommerce.csv` écrit `complement-isbn.csv` et `isbn-a-revoir.md`. Chaque ISBN est contrôlé (la clé),
   écrit sans tirets ; un ISBN à 10 chiffres devient un ISBN-13. Un tableau sans référence se rapproche du catalogue
   par le titre et l'auteur (à vérifier dans le rapport).
3. Produits → Importer, `complement-isbn.csv`, « Mettre à jour les produits existants » coché : l'ISBN va dans le
   champ « GTIN, UPC, EAN ou ISBN » de WooCommerce, que la fiche et la recherche lisent. WooCommerce refuse qu'un même
   numéro serve à deux produits : quand la librairie a deux fiches pour un même livre (neuf et d'occasion), la seconde
   le garde dans la méta `er_isbn`, que la fiche lit aussi (`er_isbn()`, `inc/fiche.php`). Une ligne refusée à
   l'import (« GTIN en double ») a un numéro déjà pris par une autre fiche.

**Le point sur le catalogue** : `wp er catalogue` (le workflow « Déployer chez Kinsta », champ « boutique » :
`catalogue`) compte les livres (hors billets des rencontres), ceux qui ont un prix, du stock, un poids, un ISBN, un
rayon, et leurs couvertures ; des comptes seulement, jamais une liste ni une quantité en stock, jamais les ventes. Le
rapport va sur la branche `boutique` (`catalogue.md`). `wp er extensions` (champ « boutique » : `extensions`) fait le
relevé des extensions installées et des réglages du paiement, de la livraison et des langues, sans jamais montrer une
clé ni un secret (« posée » ou « vide »).

## La bascule depuis Wix

Les anciennes adresses mènent aux nouvelles (301), pour les liens qui circulent et pour les moteurs de recherche :
les rencontres (`/events-1/…`), le journal (`/post/…`), les livres (`/product-page/…`) et les pages (`/expo`,
`/notre-histoire`…, la table de `plugin/er-librairie/inc/bascule.php`). Un livre de Wix est retrouvé par le nom de sa
page, que `wp er bascule livres appliquer` a lu dans le plan du site de Wix et retenu dans la fiche (831 sur 847) ;
une adresse sans livre mène à la recherche de la librairie. `wp er bascule pages` montre où mène chaque page.

Les données structurées (`inc/donnees-structurees.php`) disent aux moteurs de recherche ce qu'est chaque page : un
livre (son prix, sa disponibilité, son ISBN, ses auteurs et traducteurs), une rencontre ou une exposition (sa date,
son lieu, son entrée), une personne (ses dates, son nom russe), la librairie à l'accueil, et le fil d'Ariane de chaque
tête de page. `wp er ld` les relève sur quelques pages et essaie les anciennes adresses.

## Le paiement

Par l'extension WooCommerce Stripe Payment Gateway, reliée au compte Stripe de la maison par son bouton de connexion
(WooCommerce → Réglages → Paiements → Stripe) : aucune clé ne se copie à la main, aucune ne passe par ce dépôt ni par
Claude. Mode test sur la bêta. La carte, Apple Pay et Google Pay (qui s'activent ensemble) ; les boutons express sur
la fiche du livre, le panier et la page Commande. WooPayments, qui ferait doublon (Apple Pay et Google Pay deux fois),
est retiré par le workflow. Les billets des rencontres se paient comme les livres ; virtuels, ils n'ont jamais de
livraison.

## Les règles du livre

La boutique applique elle-même les lois du livre (`plugin/er-librairie/inc/prix-du-livre.php`, qui les cite) :

- **Le prix unique.** Un livre neuf expédié se vend au prix de l'éditeur : une remise saisie est ignorée (la fiche du
  livre dit pourquoi, sous les prix), un code promo ne le touche pas. Les 5 % de la loi Lang ne valent qu'au retrait
  en librairie, que la boutique en ligne ne remise pas.
- **Les soldes.** Permises sur un livre édité depuis plus de deux ans (l'année d'édition à trois ans d'écart, faute de
  mois) et dont le dernier approvisionnement remonte à plus de six mois. Ce dernier approvisionnement se note tout seul
  quand on augmente le stock dans la fiche (onglet Inventaire), et se corrige à la main.
- **Les titres de la maison.** Jamais soldés (loi Darcos, 2021). Si YMCA-Press veut vendre un titre moins cher, elle
  baisse son prix d'éditeur : `wp er prix maison appliquer` fait du prix remisé le nouveau prix.
- **L'occasion** (la mention « Occasion ») garde un prix libre.
- **La livraison** d'une commande qui contient des livres neufs coûte au moins 3 € TTC sous 35 € de livres neufs,
  0,01 € au-delà ; le retrait à la librairie reste gratuit.
- **La TVA** à 5,5 %, livraison comprise, prix saisis TTC : `wp er tva appliquer`.

`wp er prix` relève les remises : permises, ignorées, et pourquoi.

## Déployer chez Kinsta

Le site est hébergé chez Kinsta (formule Single 20GB, centre de données de Paris). Chaque poussée sur `main` qui
touche `theme/` ou `plugin/` met le thème et l'extension en ligne par SSH (`.github/workflows/kinsta.yml`), puis vide
le cache. Chaque passage installe et active aussi les extensions dont le site a besoin : WooCommerce, Stripe
(WooCommerce Stripe Payment Gateway) et la nôtre ; une extension à ajouter au site s'ajoute dans le workflow. Polylang
Pro et Polylang for WooCommerce, payants, se téléversent à la main ; le workflow les active s'ils sont là. Le
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

## Vérifier le code

Rien ne part chez Kinsta sans être vérifié : le déploiement attend le contrôle (`.github/workflows/verifier.yml`), et
s'arrête s'il échoue. Le même contrôle se lance sur un poste avec `bash outils/verifier.sh` :

1. la syntaxe de chaque fichier PHP et JavaScript ;
2. les tests PHP (`tests/php`, `php tests/php/lancer.php`), sans WordPress ni rien à installer : les fonctions de
   WordPress dont le code a besoin sont doublées dans `tests/php/amorce.php` ;
3. les outils Python : ruff (`ruff.toml`) et les tests de `tests/python` (pytest) ;
4. PHPCS, avec les règles de sécurité de WordPress et une hygiène de base des fichiers, pas toute la mise en forme
   (`phpcs.xml.dist`) ; sur un poste, après `composer install`.

Une règle : chaque bogue corrigé arrive avec le test qui l'aurait vu ; chaque mauvais nom trouvé dans le catalogue
devient un cas dans `tests/php/roles-test.php`. Une fonction qui rend du HTML ou une adresse déjà échappés se déclare
dans `phpcs.xml.dist` plutôt qu'avec un « phpcs:ignore » à chaque `echo`.

## Travailler à deux

Depuis le 23 nov. 2026, deux conversations de Claude développent le site en même temps, chacune dans son propre
espace de travail : elles ne se voient pas et se retrouvent sur `main`.

- **Le centre culturel** : les rencontres et leur billetterie, le journal, les vidéos et les podcasts, les fiches
  d'auteurs, les sujets et les étiquettes (`inc/evenements.php`, `medias.php`, `sujets.php`, `auteurs.php`,
  `journal.php`, leurs gabarits, `outils/wix/`, la branche `wix-pages`).
- **La boutique** : le catalogue et son import (`outils/catalogue/`, `inc/import.php`, `fiche.php`,
  `couvertures.php`, `catalogue.php`, `liens-livres.php`, `traducteurs-editeurs.php`, la branche `boutique`), le paiement (Stripe, Apple Pay, Google Pay), la livraison, Polylang, puis Algolia
  (`inc/recherche.php` passe alors de son côté : le centre lui signale ce qu'il ajoute à l'index).

Les fichiers communs, que presque chaque changement touche :

- **Toujours** `git pull --rebase` avant de pousser ; des commits petits et fréquents ; jamais de force-push sur
  `main`.
- **Les archives** `apercu/*.zip` ne se fusionnent pas : en cas de conflit, `sh outils/construire.sh` après le
  rebase, puis `git add apercu && git rebase --continue`. Les refaire à chaque changement du thème ou de
  l'extension.
- **Les traductions** : chacun ajoute ses textes dans `outils/traductions.py` (un conflit s'y règle ligne à ligne) ;
  les `.pot`, `.po` et `.l10n.php` ne se fusionnent pas non plus, on relance le script.
- **`style.css`** : chacun écrit dans ses propres sections ; pour le numéro de version, en cas de conflit, le plus
  grand des deux plus un. De même pour la version de l'extension (`er-librairie.php`), où chacun ajoute ses
  `require` sans retirer ceux de l'autre.
- **`functions.php`** et **ce README** : une section par sujet, sans réécrire celles de l'autre.
- **Kinsta** : les passages du workflow « Déployer chez Kinsta » se mettent en file (`concurrency: kinsta`), deux
  poussées rapprochées ne se gênent pas. Un passage à la main se lance par l'API REST de GitHub
  (`gh api -X POST repos/andrerakovitch-byte/editeurs-reunis/actions/workflows/kinsta.yml/dispatches -f ref=main
  -f "inputs[er]=…"`) : `gh workflow run` passe par GraphQL, qui n'est pas ouvert à ces conversations.

La copie locale de WordPress (avec SQLite) n'est pas dans le dépôt : chacune monte la sienne. La bêta, Kinsta et Wix
ne sont pas joignables depuis l'espace de travail de Claude : on y agit par le workflow, et on voit ce que sert le
site dans le rapport qu'il dépose.

## Installer sur l'hébergement définitif

1. Installer WordPress en français, puis WooCommerce ; ajouter le russe et l'anglais (Royaume-Uni) dans Réglages →
   Général, pour que WordPress et WooCommerce téléchargent leurs traductions.
2. Apparence → Thèmes → Ajouter → Téléverser `apercu/editeurs-reunis.zip`, puis l'activer.
3. Extensions → Ajouter → Téléverser `apercu/er-librairie.zip`, puis l'activer.
4. Pour partir du contenu de l'ébauche : `wp eval-file wp-content/plugins/er-librairie/donnees/semer.php`.
5. Réglages → Permaliens : « Titre de la publication ».
6. Polylang Pro et Polylang for WooCommerce : comme sur la bêta (« Trois langues », « Mettre Polylang en place »),
   puis `wp er langues installer`.
