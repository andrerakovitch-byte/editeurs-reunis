<?php
/**
 * Title: La fiche d'un livre
 * Slug: editeurs-reunis/fiche-livre
 * Categories: editeurs-reunis
 * Inserter: no
 *
 * La couverture en grand ; à côté, les mentions, le titre, l'auteur, le prix, le panier, la présentation, la fiche
 * (rayon, langue, auteur, référence) et « Du même auteur » ; dessous, la bande « Autour de ce livre » et l'étagère
 * « Du même rayon, de la même époque » (la piste B, retenue le 23 nov. 2026 ; les blocs er/fiche-* de l'extension,
 * inc/recommandations.php).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"main","className":"er-fiche","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group er-fiche" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:woocommerce/breadcrumbs {"align":"wide","fontSize":"x-small"} /-->

	<!-- wp:woocommerce/store-notices /-->

	<!-- wp:columns {"align":"wide","className":"er-fiche-haut","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"},"margin":{"top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns alignwide er-fiche-haut" style="margin-top:var(--wp--preset--spacing--40)">
		<!-- wp:column {"width":"40%","className":"er-fiche-couverture"} -->
		<div class="wp-block-column er-fiche-couverture" style="flex-basis:40%">
			<!-- wp:woocommerce/product-image-gallery /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"60%","className":"er-fiche-texte"} -->
		<div class="wp-block-column er-fiche-texte" style="flex-basis:60%">
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
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:er/fiche-autour /-->

	<!-- wp:er/fiche-rayon /-->
</main>
<!-- /wp:group -->
