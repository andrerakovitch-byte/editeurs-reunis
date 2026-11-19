<?php
/**
 * Title: Accueil — nouveautés de la librairie
 * Slug: editeurs-reunis/accueil-nouveautes
 * Categories: editeurs-reunis
 * Description: Les dix derniers livres arrivés, en grand, et le lien vers toute la librairie.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-section er-nouveautes","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-section er-nouveautes" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"align":"wide","className":"er-section-tete","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group alignwide er-section-tete">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Nouveautés de la librairie</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"er-lien-fleche"} -->
		<p class="er-lien-fleche"><a href="<?php echo er_lien( 'librairie/' ); ?>">Toute la librairie</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:woocommerce/product-collection {"queryId":21,"query":{"perPage":10,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":5,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"align":"wide"} -->
	<div class="wp-block-woocommerce-product-collection alignwide">
		<?php echo er_fiche_livre(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<!-- /wp:woocommerce/product-collection -->
</div>
<!-- /wp:group -->
