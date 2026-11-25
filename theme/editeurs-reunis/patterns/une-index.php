<?php
/**
 * Title: À la une — l'index
 * Slug: editeurs-reunis/une-index
 * Categories: editeurs-reunis
 * Description: Le premier module de l'accueil : à gauche, les titres de ce que publient les trois maisons (la prochaine rencontre en grand, la parution d'YMCA-Press, le podcast, les nouveautés, puis mois par mois) ; à droite, le livre ou la photo de la ligne en cours, qui reste en place, avec l'essentiel et un bouton.
 *
 * Retenu le 25 novembre 2026 (planche D1 du canevas « Une nouvelle page d'accueil, aérée »), à la place de « À la une —
 * écran par écran » (patterns/une-ecrans.php, gardé). Tout est lu dans le site à chaque affichage : inc/index.php
 * (le code court [er_index], que le gabarit de l'accueil appelle aussi directement).
 * La colonne de droite suit la ligne en cours : assets/js/une-index.js. Sans JavaScript, elle montre la première
 * ligne ; au téléphone, elle disparaît et chaque ligne a sa vignette.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:html -->
[er_index]
<!-- /wp:html -->
