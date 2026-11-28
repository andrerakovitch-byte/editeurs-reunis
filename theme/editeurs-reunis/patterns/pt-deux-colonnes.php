<?php
/**
 * Title: La section sur deux colonnes
 * Slug: editeurs-reunis/pt-deux-colonnes
 * Categories: er-modules
 * Description: Un chapitre de la page : l'intertitre sur la première colonne du triptyque, le texte qui coule sur les deux autres.
 *
 * Son intertitre se choisit quand la page type l'inclut deux fois ($er_pt_intertitre). Les paragraphes vont dans le
 * groupe « er-pt-colonnes » : le texte passe tout seul d'une colonne à l'autre.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-texte er-pt-deux-colonnes er-pt-filet","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-texte er-pt-deux-colonnes er-pt-filet">
<!-- wp:heading {"className":"er-pt-intertitre"} -->
<h2 class="wp-block-heading er-pt-intertitre"><?php echo esc_html( $er_pt_intertitre ?? 'Des rencontres, plusieurs fois par semaine' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:group {"className":"er-pt-colonnes","layout":{"type":"default"}} -->
<div class="wp-block-group er-pt-colonnes">
<!-- wp:paragraph -->
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus, posuere velit aliquet. Curabitur blandit tempus porttitor, nullam quis risus eget urna mollis ornare vel eu leo. Vestibulum id ligula porta felis euismod semper.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Donec ullamcorper nulla non metus auctor fringilla. Maecenas faucibus mollis interdum. Cras mattis consectetur purus sit amet fermentum, vestibulum id ligula porta felis euismod semper. Aenean lacinia bibendum nulla sed consectetur. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Etiam porta sem malesuada magna mollis euismod, comme dans <a href="<?php echo esc_url( home_url( '/livre/la-roue-rouge-chapitres-choisis/' ) ); ?>"><em>La Roue rouge</em></a>, d’Alexandre Soljenitsyne. Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Sed posuere consectetur est at lobortis. Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Maecenas sed diam eget risus varius blandit sit amet non magna. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:buttons {"className":"er-pt-boutons"} -->
<div class="wp-block-buttons er-pt-boutons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/centre-culturel/evenements/' ) ); ?>">Le programme</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
