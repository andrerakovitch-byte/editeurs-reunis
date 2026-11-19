<?php
/**
 * Title: Accueil — les rayons
 * Slug: editeurs-reunis/accueil-rayons
 * Categories: editeurs-reunis
 * Description: Les rayons de la librairie et les langues, en pilules, avec le nombre de livres.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-section er-rayons-accueil","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-section er-rayons-accueil" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"34%"} -->
		<div class="wp-block-column" style="flex-basis:34%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Les rayons de la librairie</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>Littérature classique et contemporaine, poésie, histoire, pensée et théologie orthodoxe, manuels et dictionnaires : en russe et en français, neufs et d’occasion.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"66%"} -->
		<div class="wp-block-column" style="flex-basis:66%">
			<!-- wp:heading {"level":3,"className":"er-petit-titre"} -->
			<h3 class="wp-block-heading er-petit-titre">Par rayon</h3>
			<!-- /wp:heading -->

			<!-- wp:categories {"taxonomy":"product_cat","showPostCounts":true,"showEmpty":false,"className":"er-pilules"} /-->

			<!-- wp:heading {"level":3,"className":"er-petit-titre"} -->
			<h3 class="wp-block-heading er-petit-titre">Par langue</h3>
			<!-- /wp:heading -->

			<!-- wp:categories {"taxonomy":"langue","showPostCounts":true,"showEmpty":false,"className":"er-pilules"} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
