<?php
/**
 * Title: Accueil — le livre du moment
 * Slug: editeurs-reunis/accueil-parution
 * Categories: editeurs-reunis
 * Description: Un livre mis en avant : sa couverture en grand, quelques lignes et le lien vers sa fiche.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-section er-parution","backgroundColor":"doux","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-section er-parution has-doux-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre">Le livre du moment</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading -->
			<h2 class="wp-block-heading">Notes d’un médecin</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-parution-auteur"} -->
			<p class="er-parution-auteur">Vikenti Veressaïev, traduit du russe par Julie Bouvard, préface de Dimitri Bortnikov</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>Un jeune médecin raconte ses débuts à l’hôpital, ses doutes et ses erreurs. À sa parution, en 1901, le livre fit scandale ; il n’a rien perdu de sa franchise.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_livre( 'notes-dun-medecin' ); ?>">Découvrir le livre</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
			<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","align":"center","className":"er-couverture-seule"} -->
			<figure class="wp-block-image aligncenter size-full er-couverture-seule"><a href="<?php echo er_livre( 'notes-dun-medecin' ); ?>"><img src="<?php echo er_image( 'chef-doeuvre-veressaiev.jpg' ); ?>" alt="Couverture de Notes d’un médecin, de Vikenti Veressaïev"/></a></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
