<?php
/**
 * Title: Accueil — communiqué
 * Slug: editeurs-reunis/accueil-communique
 * Categories: editeurs-reunis
 * Description: Comme une diapositive, sans image : la bande pervenche, l'aplat bleu nuit, le titre du communiqué en blanc.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-panneau er-panneau-sombre er-panneau-bas er-communique","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-panneau er-panneau-sombre er-panneau-bas er-communique">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"er-diapo-contenu"} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center er-diapo-contenu">
		<!-- wp:column {"verticalAlignment":"center","width":"72%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:72%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre"><?php esc_html_e( 'Communiqué de presse', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-diapo-titre er-communique-titre"} -->
			<h2 class="wp-block-heading er-diapo-titre er-communique-titre"><?php esc_html_e( 'À propos de l’invasion de l’Ukraine par la Fédération de Russie', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-diapo-texte"} -->
			<p class="er-diapo-texte"><?php echo wp_kses_post( __( 'Signé par Mélanie Rakovitch, le 28&nbsp;février 2022.', 'editeurs-reunis' ) ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-fleche"} -->
				<div class="wp-block-button is-style-fleche"><a class="wp-block-button__link wp-element-button" href="<?php echo er_article( 'a-propos-de-linvasion-de-lukraine-par-la-federation-de-russie' ); ?>"><?php esc_html_e( 'Lire le communiqué', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"28%"} -->
		<div class="wp-block-column" style="flex-basis:28%"></div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
