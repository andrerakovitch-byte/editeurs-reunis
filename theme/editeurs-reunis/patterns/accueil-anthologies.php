<?php
/**
 * Title: Accueil — les anthologies
 * Slug: editeurs-reunis/accueil-anthologies
 * Categories: editeurs-reunis
 * Description: Une collection mise en avant : quelques lignes et ses couvertures en éventail.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-section er-anthologies","backgroundColor":"pierre","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-section er-anthologies has-pierre-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"34%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:34%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre"><?php esc_html_e( 'Nos meilleures ventes YMCA-Press', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Anthologies de la poésie russe', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-parution-auteur"} -->
			<p class="er-parution-auteur"><?php esc_html_e( 'Bilingues russe-français, en quatre volumes', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Un panorama saisissant de la poésie russe à travers son histoire, de ses origines jusqu’à son expression la plus contemporaine.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_rayon( 'poesie' ); ?>"><?php esc_html_e( 'Le rayon poésie', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"66%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66%">
			<!-- wp:gallery {"columns":4,"linkTo":"none","sizeSlug":"full","className":"er-eventail","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
			<figure class="wp-block-gallery has-nested-images columns-4 is-cropped er-eventail">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full"><img src="<?php echo er_image( 'anthologie-1.jpg' ); ?>" alt="<?php esc_attr_e( 'Anthologie de la poésie russe du XVIIIᵉ siècle', 'editeurs-reunis' ); ?>"/></figure>
				<!-- /wp:image -->

				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full"><img src="<?php echo er_image( 'anthologie-2.jpg' ); ?>" alt="<?php esc_attr_e( 'Anthologie de la poésie russe du XIXᵉ siècle', 'editeurs-reunis' ); ?>"/></figure>
				<!-- /wp:image -->

				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full"><img src="<?php echo er_image( 'anthologie-3.jpg' ); ?>" alt="<?php esc_attr_e( 'Anthologie de la poésie russe : la renaissance du XXᵉ siècle', 'editeurs-reunis' ); ?>"/></figure>
				<!-- /wp:image -->

				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full"><img src="<?php echo er_image( 'anthologie-4.jpg' ); ?>" alt="<?php esc_attr_e( 'Anthologie de la poésie russe du début du XXIᵉ siècle', 'editeurs-reunis' ); ?>"/></figure>
				<!-- /wp:image -->
			</figure>
			<!-- /wp:gallery -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
