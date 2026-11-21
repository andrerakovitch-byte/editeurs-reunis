<?php
/**
 * Title: Accueil — les rayons
 * Slug: editeurs-reunis/accueil-rayons
 * Categories: editeurs-reunis
 * Description: Sur fond blanc : le titre dans la colonne pervenche ; à côté, les rayons et les langues de la librairie en pilules.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-panneau er-panneau-clair er-rayons-accueil","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-panneau er-panneau-clair er-rayons-accueil">
	<!-- wp:columns {"align":"wide","className":"er-panneau-colonnes","style":{"spacing":{"blockGap":{"left":"0"}}}} -->
	<div class="wp-block-columns alignwide er-panneau-colonnes">
		<!-- wp:column {"width":"22%","className":"er-colonne"} -->
		<div class="wp-block-column er-colonne" style="flex-basis:22%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre"><?php esc_html_e( 'La librairie', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-panneau-titre"} -->
			<h2 class="wp-block-heading er-panneau-titre"><?php esc_html_e( 'Les rayons', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"78%","className":"er-champ"} -->
		<div class="wp-block-column er-champ" style="flex-basis:78%">
			<!-- wp:paragraph {"className":"er-chapeau"} -->
			<p class="er-chapeau"><?php esc_html_e( 'Littérature classique et contemporaine, poésie, histoire, pensée et théologie orthodoxe, manuels et dictionnaires : en russe et en français, neufs et d’occasion.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:categories {"taxonomy":"product_cat","showPostCounts":true,"showEmpty":false,"className":"er-pilules er-pilules-grandes"} /-->

			<!-- wp:categories {"taxonomy":"langue","showPostCounts":true,"showEmpty":false,"className":"er-pilules er-pilules-grandes er-pilules-langues"} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
