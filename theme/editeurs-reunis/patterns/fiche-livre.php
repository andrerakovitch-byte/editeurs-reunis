<?php
/**
 * Title: La fiche d'un livre
 * Slug: editeurs-reunis/fiche-livre
 * Categories: editeurs-reunis
 * Inserter: no
 *
 * La couverture en grand ; à côté, les mentions, le titre, l'auteur, le prix, le panier, la présentation et la fiche
 * (rayon, langue, auteur, référence) ; dessous, les livres du même rayon.
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
				<!-- wp:post-terms {"term":"product_cat","prefix":"Rayon"} /-->

				<!-- wp:post-terms {"term":"langue","prefix":"Langue"} /-->

				<!-- wp:post-terms {"term":"auteur","prefix":"Auteur"} /-->

				<!-- wp:woocommerce/product-sku {"prefix":"Référence"} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"er-fiche-retrait","fontSize":"small"} -->
			<p class="er-fiche-retrait has-small-font-size">Vous pouvez aussi le demander à la librairie, 11, rue de la Montagne-Sainte-Geneviève, du mardi au samedi.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:woocommerce/product-collection {"align":"wide","queryId":41,"query":{"perPage":5,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":false}},"tagName":"div","displayLayout":{"type":"flex","columns":5,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/related","hideControls":["inherit"],"queryContextIncludes":["collection"],"className":"er-meme-rayon","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-woocommerce-product-collection alignwide er-meme-rayon" style="margin-top:var(--wp--preset--spacing--60)">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Du même rayon</h2>
		<!-- /wp:heading -->

		<?php echo er_fiche_livre(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<!-- /wp:woocommerce/product-collection -->
</main>
<!-- /wp:group -->
