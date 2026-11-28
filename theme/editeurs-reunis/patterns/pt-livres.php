<?php
/**
 * Title: Les livres
 * Slug: editeurs-reunis/pt-livres
 * Categories: er-modules
 * Description: Six livres dans le dessin de la recherche, deux par colonne du triptyque ; [er_pt_livres auteur="…"] ou ids="…" pour choisir.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-livres er-pt-filet","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-livres er-pt-filet">
<!-- wp:heading {"className":"er-pt-module-titre"} -->
<h2 class="wp-block-heading er-pt-module-titre">Les livres</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"er-pt-lien-fleche"} -->
<p class="er-pt-lien-fleche"><a href="<?php echo esc_url( home_url( '/librairie/' ) ); ?>">Toute la librairie&nbsp;→</a></p>
<!-- /wp:paragraph -->
<!-- wp:shortcode -->
[er_pt_livres]
<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
