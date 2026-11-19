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

## Installer sur l'hébergement définitif

1. Installer WordPress en français, puis WooCommerce.
2. Apparence → Thèmes → Ajouter → Téléverser `apercu/editeurs-reunis.zip`, puis l'activer.
3. Extensions → Ajouter → Téléverser `apercu/er-librairie.zip`, puis l'activer.
4. Pour partir du contenu de l'ébauche : `wp eval-file wp-content/plugins/er-librairie/donnees/semer.php`.
5. Réglages → Permaliens : « Titre de la publication ».
