<?php
/**
 * Title: La librairie
 * Slug: editeurs-reunis/librairie
 * Categories: editeurs-reunis
 * Inserter: no
 *
 * Toute la librairie, un rayon, un auteur, une langue ou une recherche : le titre, les rayons et les langues en
 * pilules, puis les livres en grand, sur toute la largeur.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"main","className":"er-librairie","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group er-librairie" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"align":"wide","className":"er-librairie-tete","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide er-librairie-tete">
		<!-- wp:woocommerce/breadcrumbs {"fontSize":"x-small"} /-->

		<!-- wp:query-title {"type":"archive","showPrefix":false} /-->

		<!-- wp:query-title {"type":"search"} /-->

		<!-- wp:term-description /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"wide","className":"er-filtres","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide er-filtres">
		<!-- wp:categories {"taxonomy":"product_cat","showPostCounts":false,"showEmpty":false,"className":"er-pilules er-pilules-rayons"} /-->

		<!-- wp:categories {"taxonomy":"langue","showPostCounts":false,"showEmpty":false,"className":"er-pilules er-pilules-langues"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:woocommerce/store-notices /-->

	<!-- wp:group {"align":"wide","className":"er-tri","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide er-tri">
		<!-- wp:woocommerce/product-results-count /-->

		<!-- wp:woocommerce/catalog-sorting /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:woocommerce/product-collection {"queryId":31,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":20,"pages":0,"offset":0,"postType":"product","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","dimensions":{"widthType":"fill"},"displayLayout":{"type":"flex","columns":5,"shrinkColumns":true},"queryContextIncludes":["collection"],"align":"wide"} -->
	<div class="wp-block-woocommerce-product-collection alignwide">
		<?php echo er_fiche_livre(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
			<!-- wp:query-pagination-previous {"label":"Précédents"} /-->
			<!-- wp:query-pagination-numbers /-->
			<!-- wp:query-pagination-next {"label":"Suivants"} /-->
		<!-- /wp:query-pagination -->

		<!-- wp:woocommerce/product-collection-no-results -->
			<!-- wp:paragraph -->
			<p>Aucun livre ne correspond. <a href="<?php echo er_lien( 'librairie/' ); ?>">Voir toute la librairie</a>, ou écrivez-nous : nous avons bien plus de livres en rayon qu’en ligne.</p>
			<!-- /wp:paragraph -->
		<!-- /wp:woocommerce/product-collection-no-results -->
	</div>
	<!-- /wp:woocommerce/product-collection -->
</main>
<!-- /wp:group -->
