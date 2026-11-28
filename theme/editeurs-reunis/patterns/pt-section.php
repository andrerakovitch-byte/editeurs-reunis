<?php
/**
 * Title: La section : un intertitre et son texte
 * Slug: editeurs-reunis/pt-section
 * Categories: er-modules
 * Description: Un chapitre de la page : un intertitre, un texte, un bouton.
 *
 * Son intertitre se choisit quand la page type l'inclut deux fois ($er_pt_intertitre).
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-texte er-pt-section er-pt-filet","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-texte er-pt-section er-pt-filet">
<!-- wp:heading {"className":"er-pt-intertitre"} -->
<h2 class="wp-block-heading er-pt-intertitre"><?php echo esc_html( $er_pt_intertitre ?? 'Des rencontres, plusieurs fois par semaine' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus, posuere velit aliquet. Curabitur blandit tempus porttitor, nullam quis risus eget urna mollis ornare vel eu leo.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Donec ullamcorper nulla non metus auctor fringilla. Maecenas faucibus mollis interdum. Cras mattis consectetur purus sit amet fermentum, vestibulum id ligula porta felis euismod semper. Aenean lacinia bibendum nulla sed consectetur.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"className":"er-pt-boutons"} -->
<div class="wp-block-buttons er-pt-boutons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/centre-culturel/evenements/' ) ); ?>">Le programme</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
