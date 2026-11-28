<?php
/**
 * Title: Le texte, ses liens dans la marge
 * Slug: editeurs-reunis/pt-texte
 * Categories: er-modules
 * Description: Le texte sur la colonne du milieu (une amorce, des paragraphes) ; dans la marge de droite, ses liens : une rencontre, une exposition, un article, des pages, un livre.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-texte er-pt-filet","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-texte er-pt-filet">
<!-- wp:group {"className":"er-pt-corps","layout":{"type":"default"}} -->
<div class="wp-block-group er-pt-corps">
<!-- wp:paragraph {"className":"er-pt-amorce"} -->
<p class="er-pt-amorce">Depuis 2016, le centre réunit autour d’un livre, d’une œuvre ou d’un témoin ceux qui lisent, écrivent et traduisent. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus, posuere velit aliquet. Curabitur blandit tempus porttitor, nullam quis risus eget urna mollis ornare vel eu leo.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Donec ullamcorper nulla non metus auctor fringilla. Maecenas faucibus mollis interdum. Cras mattis consectetur purus sit amet fermentum, vestibulum id ligula porta felis euismod semper. Aenean lacinia bibendum nulla sed consectetur.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Etiam porta sem malesuada magna mollis euismod. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Sed posuere consectetur est at lobortis.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"er-pt-marge","layout":{"type":"default"}} -->
<div class="wp-block-group er-pt-marge">
<!-- wp:shortcode -->
[er_pt_lien type="evenement"]
<!-- /wp:shortcode -->
<!-- wp:shortcode -->
[er_pt_lien type="exposition"]
<!-- /wp:shortcode -->
<!-- wp:shortcode -->
[er_pt_lien type="article"]
<!-- /wp:shortcode -->
<!-- wp:buttons {"className":"er-pt-boutons"} -->
<div class="wp-block-buttons er-pt-boutons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/centre-culturel/evenements/' ) ); ?>">Nos rencontres</a></div>
<!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/centre-culturel/expositions/' ) ); ?>">Nos expositions</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- wp:shortcode -->
[er_pt_lien type="livre"]
<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
