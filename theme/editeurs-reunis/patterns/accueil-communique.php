<?php
/**
 * Title: Accueil — communiqué
 * Slug: editeurs-reunis/accueil-communique
 * Categories: editeurs-reunis
 * Description: Un encadré pour un communiqué de presse ou une annonce importante.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-section","style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-section" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"align":"wide","className":"er-communique","backgroundColor":"accent","textColor":"contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"820px"}} -->
	<div class="wp-block-group alignwide er-communique has-contrast-color has-accent-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
		<!-- wp:paragraph {"align":"center","className":"er-surtitre"} -->
		<p class="has-text-align-center er-surtitre">Communiqué de presse</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center"} -->
		<h2 class="wp-block-heading has-text-align-center">À propos de l’invasion de l’Ukraine par la Fédération de Russie</h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_article( 'a-propos-de-linvasion-de-lukraine-par-la-federation-de-russie' ); ?>">Lire le communiqué</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
