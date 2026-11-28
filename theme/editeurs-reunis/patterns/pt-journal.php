<?php
/**
 * Title: Du journal
 * Slug: editeurs-reunis/pt-journal
 * Categories: er-modules
 * Description: Les trois derniers articles du journal, en lignes : la date, le genre, le titre.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-journal er-pt-filet","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-journal er-pt-filet">
<!-- wp:heading {"className":"er-pt-module-titre"} -->
<h2 class="wp-block-heading er-pt-module-titre">Du journal</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"er-pt-lien-fleche"} -->
<p class="er-pt-lien-fleche"><a href="<?php echo esc_url( home_url( '/journal/' ) ); ?>">Tout le journal&nbsp;→</a></p>
<!-- /wp:paragraph -->
<!-- wp:shortcode -->
[er_pt_journal]
<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
