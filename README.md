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
  - `patterns/` : les sections de l'accueil, l'en-tête, le pied, la librairie et la fiche d'un livre.
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
d'édition YMCA-Press, Le centre culturel), chacune précédée d'un point de sa couleur, puis le coin droit en toutes
lettres (disposition A, retenue le même jour) : « Rechercher », « Compte », « Panier » (avec une pastille pervenche
quand il n'est pas vide), et les trois langues dans un interrupteur, la langue de la page en blanc plein. « Rechercher » devient « Fermer »
dans la même case : rien ne bouge quand on ouvre ou qu'on ferme. Dans la barre, « Maison d'édition YMCA-Press »
devient « YMCA-Press » : à côté des gestes en toutes lettres, le nom long ne tient pas dans les 1280 px du site
(il reste entier dans le menu) ; sous 960 px, « Compte » passe dans le menu (≡) ;
au téléphone, la barre garde la loupe et le panier, le compte et la langue vont au bas du menu. Dessous, le menu de la maison où l'on est, sur sa couleur : vert rayonnage pour la librairie
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

## Les livres

Un livre est un produit WooCommerce (Produits → Ajouter) :

- son **rayon** est une catégorie de produit (Littérature russe, Poésie, Histoire, Essais et biographies, Cinéma) ;
- ses **mentions** sont des étiquettes (Nouveauté, Nouvelle édition, Nouvelle traduction, Très rare), montrées en
  pastilles sur la couverture ;
- son **auteur** et sa **langue** sont deux classements de l'extension, chacun avec sa page (« Tous les livres de
  Gouzel Iakhina ») ;
- un livre sans prix n'affiche rien et ne se commande pas ; un livre épuisé affiche « Rupture de stock ».

Les couvertures de l'ébauche sont reprises de la maquette (en petite définition) ou dessinées « couverture à
venir » ; les présentations sont des brouillons d'une phrase.

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
