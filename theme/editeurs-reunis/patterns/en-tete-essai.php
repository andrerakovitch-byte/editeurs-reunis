<?php
/**
 * Title: En-tête des pages d'essai
 * Slug: editeurs-reunis/en-tete-essai
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * L'en-tête épuré, sur les pages d'essai (inc/essai.php, 26 novembre 2026) : la barre d'informations, puis la barre du
 * nom avec ses quatre menus au clic (inc/essai-menus.php), la loupe et le panier ; plus de ligne de la maison. Sous la
 * barre, les panneaux et le plein écran.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:html -->[er_infos]<!-- /wp:html -->

<!-- wp:group {"className":"er-barre er-em-barre","backgroundColor":"base","textColor":"contrast","layout":{"type":"constrained"}} -->
<div class="wp-block-group er-barre er-em-barre has-contrast-color has-base-background-color has-text-color has-background">
	<!-- wp:group {"align":"wide","className":"er-barre-dedans","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide er-barre-dedans">
		<!-- wp:paragraph {"className":"er-logo"} -->
		<p class="er-logo"><a href="<?php echo er_lien(); ?>" rel="home"><span><?php echo esc_html_x( 'Centre culturel', 'logo, première ligne', 'editeurs-reunis' ); ?></span> <span><?php echo esc_html_x( 'Alexandre Soljenitsyne', 'logo, seconde ligne', 'editeurs-reunis' ); ?></span></a></p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->[er_essai_menu]<!-- /wp:html -->

		<!-- wp:html --><a class="er-loupe er-picto" href="<?php echo esc_url( er_lien_recherche() ); ?>" aria-controls="er-recherche" aria-expanded="false" aria-haspopup="dialog"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M15.5 15.5L20 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><span class="screen-reader-text er-loupe-libelle"><?php esc_html_e( 'Rechercher', 'editeurs-reunis' ); ?></span></a><!-- /wp:html -->

		<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true,"productCountVisibility":"greater_than_zero","className":"er-panier"} /-->

		<!-- wp:html -->[er_essai_bouton_menu]<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:html -->[er_essai_panneaux]<!-- /wp:html -->
