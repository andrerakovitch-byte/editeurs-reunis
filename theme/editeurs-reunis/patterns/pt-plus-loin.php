<?php
/**
 * Title: Pour aller plus loin
 * Slug: editeurs-reunis/pt-plus-loin
 * Categories: er-modules
 * Description: Le module qui ferme la page : les pages voisines, en grand ; au survol, les autres s’effacent.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-plus-loin er-pt-filet","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-plus-loin er-pt-filet">
<!-- wp:paragraph {"className":"er-pt-plus-loin-titre"} -->
<p class="er-pt-plus-loin-titre">Pour aller plus loin</p>
<!-- /wp:paragraph -->
<!-- wp:list {"className":"er-pt-liens"} -->
<ul class="wp-block-list er-pt-liens"><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/centre-culturel/evenements/' ) ); ?>">Nos rencontres</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/centre-culturel/expositions/' ) ); ?>">Nos expositions</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/journal/' ) ); ?>">Le journal du centre</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/librairie/' ) ); ?>">La librairie</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/ymca-press/' ) ); ?>">La maison d’édition YMCA-Press</a></li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/nous-trouver/' ) ); ?>">Nous trouver</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
