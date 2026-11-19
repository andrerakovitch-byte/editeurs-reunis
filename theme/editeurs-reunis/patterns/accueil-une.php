<?php
/**
 * Title: Accueil — à la une
 * Slug: editeurs-reunis/accueil-une
 * Categories: editeurs-reunis
 * Description: Le grand bandeau d'ouverture : un titre, une phrase, deux boutons et une image carrée.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-une","backgroundColor":"accent","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-une has-contrast-color has-accent-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre">Podcast du centre culturel</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"className":"er-une-titre"} -->
			<h1 class="wp-block-heading er-une-titre">Les secrets de <em>L’Archipel du Goulag</em></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"large"} -->
			<p class="has-large-font-size">Un podcast inédit pour comprendre les mystères d’un livre hors du commun.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_lien( 'centre-culturel/podcasts/' ); ?>">Écouter le podcast</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_livre( 'larchipel-du-goulag-cinquante-ans-apres' ); ?>">Le livre à la librairie</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
			<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-image"} -->
			<figure class="wp-block-image size-full er-une-image"><a href="<?php echo er_lien( 'centre-culturel/podcasts/' ); ?>"><img src="<?php echo er_image( 'podcast-archipel.jpg' ); ?>" alt="Pochette du podcast « Les secrets de L’Archipel du Goulag » : une mer grise sous un ciel bas"/></a></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
