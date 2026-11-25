<?php
/**
 * Title: La fiche d'un livre
 * Slug: editeurs-reunis/fiche-livre
 * Categories: editeurs-reunis
 * Inserter: no
 *
 * En deux moitiés (la page C du canevas « Les Éditeurs réunis — plein écran », retenue le 24 nov. 2026) : à gauche,
 * sur le fond doux et collée au bord de l'écran, la couverture, qui reste à l'écran pendant qu'on lit ; à droite,
 * tout le reste qui défile : le fil d'Ariane, les mentions, le titre, l'auteur, le prix, le panier, la présentation,
 * la fiche (rayon, langue, auteur, référence) et « Du même auteur » (er/fiche-auteur). Dessous, de marge à marge,
 * « Pour aller plus loin » : les modules choisis pour ce livre (er/fiche-continuer, inc/continuer.php de l'extension ;
 * la cinquième page du canevas des fiches, 25 nov. 2026).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"main","className":"er-fiche","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group er-fiche" style="padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"align":"full","className":"er-fiche-moities","layout":{"type":"default"}} -->
	<div class="wp-block-group alignfull er-fiche-moities">
		<!-- wp:group {"className":"er-fiche-couverture","layout":{"type":"default"}} -->
		<div class="wp-block-group er-fiche-couverture">
			<!-- wp:group {"className":"er-fiche-couverture-colle","layout":{"type":"default"}} -->
			<div class="wp-block-group er-fiche-couverture-colle">
				<!-- wp:woocommerce/product-image-gallery /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"er-fiche-texte","layout":{"type":"default"}} -->
		<div class="wp-block-group er-fiche-texte">
			<!-- wp:woocommerce/breadcrumbs {"fontSize":"x-small"} /-->

			<!-- wp:woocommerce/store-notices /-->

			<!-- wp:post-terms {"term":"product_tag","className":"er-mentions"} /-->

			<!-- wp:post-title {"level":1,"className":"er-fiche-titre","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

			<!-- wp:post-terms {"term":"auteur","className":"er-fiche-auteur"} /-->

			<!-- wp:woocommerce/product-price {"fontSize":"x-large","className":"er-fiche-prix"} /-->

			<!-- wp:woocommerce/product-stock-indicator {"className":"er-fiche-stock"} /-->

			<!-- wp:woocommerce/add-to-cart-form {"className":"er-fiche-panier"} /-->

			<!-- wp:post-content {"className":"er-fiche-resume","layout":{"type":"default"}} /-->

			<!-- wp:group {"className":"er-fiche-infos","layout":{"type":"default"}} -->
			<div class="wp-block-group er-fiche-infos">
				<!-- wp:post-terms {"term":"product_cat","prefix":"<?php echo er_attr( _x( 'Rayon', 'fiche d’un livre', 'editeurs-reunis' ) ); ?>"} /-->

				<!-- wp:post-terms {"term":"langue","prefix":"<?php echo er_attr( _x( 'Langue', 'fiche d’un livre', 'editeurs-reunis' ) ); ?>"} /-->

				<!-- wp:post-terms {"term":"auteur","prefix":"<?php echo er_attr( _x( 'Auteur', 'fiche d’un livre', 'editeurs-reunis' ) ); ?>"} /-->

				<!-- wp:er/fiche-champs /-->

				<!-- wp:woocommerce/product-sku {"prefix":"<?php echo er_attr( _x( 'Référence', 'fiche d’un livre', 'editeurs-reunis' ) ); ?>"} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"er-fiche-retrait","fontSize":"small"} -->
			<p class="er-fiche-retrait has-small-font-size"><?php esc_html_e( 'Vous pouvez aussi le demander à la librairie, 11, rue de la Montagne-Sainte-Geneviève, du mardi au samedi.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:er/fiche-auteur /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:er/fiche-continuer /-->
</main>
<!-- /wp:group -->
