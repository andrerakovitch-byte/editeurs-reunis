<?php
/**
 * Title: Accueil — nouveautés de la librairie
 * Slug: editeurs-reunis/accueil-nouveautes
 * Categories: editeurs-reunis
 * Description: Sur fond blanc : le titre dans la colonne pervenche, les huit derniers livres arrivés à côté.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-panneau er-panneau-clair er-nouveautes","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-panneau er-panneau-clair er-nouveautes">
	<!-- wp:columns {"align":"wide","className":"er-panneau-colonnes","style":{"spacing":{"blockGap":{"left":"0"}}}} -->
	<div class="wp-block-columns alignwide er-panneau-colonnes">
		<!-- wp:column {"width":"22%","className":"er-colonne"} -->
		<div class="wp-block-column er-colonne" style="flex-basis:22%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre"><?php esc_html_e( 'La librairie', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-panneau-titre"} -->
			<h2 class="wp-block-heading er-panneau-titre"><?php esc_html_e( 'Nouveautés', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-colonne-texte"} -->
			<p class="er-colonne-texte"><?php esc_html_e( 'Les derniers livres arrivés en rayon, en français et en russe.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-fleche"} -->
				<div class="wp-block-button is-style-fleche"><a class="wp-block-button__link wp-element-button" href="<?php echo er_lien( 'librairie/' ); ?>"><?php esc_html_e( 'Toute la librairie', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"78%","className":"er-champ"} -->
		<div class="wp-block-column er-champ" style="flex-basis:78%">
			<!-- wp:woocommerce/product-collection {"queryId":21,"query":{"perPage":8,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"]} -->
			<div class="wp-block-woocommerce-product-collection">
				<?php echo er_fiche_livre(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<!-- /wp:woocommerce/product-collection -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
