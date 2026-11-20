<?php
/**
 * Title: Accueil — le livre du moment
 * Slug: editeurs-reunis/accueil-parution
 * Categories: editeurs-reunis
 * Description: Comme une diapositive du diaporama : la bande pervenche, un aplat sombre, le titre en blanc, la couverture à droite.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-panneau er-panneau-sombre er-parution","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-panneau er-panneau-sombre er-parution">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"er-diapo-contenu"} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center er-diapo-contenu">
		<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre">Le livre du moment</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-diapo-titre"} -->
			<h2 class="wp-block-heading er-diapo-titre">Notes d’un médecin</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-diapo-auteur"} -->
			<p class="er-diapo-auteur">Vikenti Veressaïev, traduit du russe par Julie Bouvard, préface de Dimitri Bortnikov</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"er-diapo-texte"} -->
			<p class="er-diapo-texte">Un jeune médecin raconte ses débuts à l’hôpital, ses doutes et ses erreurs. À sa parution, en 1901, le livre fit scandale ; il n’a rien perdu de sa franchise.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-fleche"} -->
				<div class="wp-block-button is-style-fleche"><a class="wp-block-button__link wp-element-button" href="<?php echo er_livre( 'notes-dun-medecin' ); ?>">Découvrir le livre</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
			<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-diapo-image er-diapo-image-livre"} -->
			<figure class="wp-block-image size-full er-diapo-image er-diapo-image-livre"><a href="<?php echo er_livre( 'notes-dun-medecin' ); ?>" tabindex="-1"><img src="<?php echo er_image( 'chef-doeuvre-veressaiev.jpg' ); ?>" alt="Couverture de Notes d’un médecin, de Vikenti Veressaïev"/></a></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
