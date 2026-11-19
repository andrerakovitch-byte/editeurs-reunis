<?php
/**
 * Title: Accueil — notre histoire
 * Slug: editeurs-reunis/accueil-histoire
 * Categories: editeurs-reunis
 * Description: Un bandeau d'image sur toute la largeur, puis l'histoire de la maison en quelques lignes.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-histoire","style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|60"},"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-histoire" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:image {"align":"full","sizeSlug":"full","linkDestination":"none","className":"er-bande"} -->
	<figure class="wp-block-image alignfull size-full er-bande"><img src="<?php echo er_image( 'bande-histoire.jpg' ); ?>" alt=""/></figure>
	<!-- /wp:image -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"},"margin":{"top":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--60)">
		<!-- wp:column {"width":"45%"} -->
		<div class="wp-block-column" style="flex-basis:45%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Découvrez notre histoire centenaire</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"55%"} -->
		<div class="wp-block-column" style="flex-basis:55%">
			<!-- wp:paragraph {"fontSize":"large"} -->
			<p class="has-large-font-size">L’histoire centenaire des éditions YMCA-Press, cœur vibrant de la littérature russe du XX<sup>e</sup>&nbsp;siècle, entre Bounine, Akhmatova, Mandelstam, Boulgakov et Soljenitsyne.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"er-lien-fleche"} -->
			<p class="er-lien-fleche"><a href="<?php echo er_lien( 'ymca-press/histoire/' ); ?>">Un siècle d’histoire</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
