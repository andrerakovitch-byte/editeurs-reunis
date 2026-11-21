<?php
/**
 * Title: En-tête
 * Slug: editeurs-reunis/en-tete
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * La barre noire (la langue, chercher un livre, le compte, le panier), puis le nom et le menu. Le sélecteur de langue
 * est un code court, pour rester juste même si l'en-tête est retouché dans l'éditeur du site.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"className":"er-barre","backgroundColor":"noir","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group er-barre has-base-color has-noir-background-color has-text-color has-background">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:html -->[er_langues_entete]<!-- /wp:html -->

		<!-- wp:search {"label":"<?php echo er_attr( __( 'Chercher dans la librairie', 'editeurs-reunis' ) ); ?>","showLabel":false,"placeholder":"<?php echo er_attr( __( 'Rechercher un titre, un auteur…', 'editeurs-reunis' ) ); ?>","buttonText":"<?php echo er_attr( __( 'Chercher', 'editeurs-reunis' ) ); ?>","buttonPosition":"button-inside","buttonUseIcon":true,"query":{"post_type":"product"},"className":"er-chercher"} /-->

		<!-- wp:woocommerce/customer-account {"displayStyle":"text_only","className":"er-compte"} /-->

		<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true,"productCountVisibility":"always","className":"er-panier"} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"er-entete","layout":{"type":"constrained"}} -->
<div class="wp-block-group er-entete">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"className":"er-logo"} -->
		<p class="er-logo"><a href="<?php echo er_lien(); ?>" rel="home"><span><?php echo esc_html_x( 'Centre culturel', 'logo, première ligne', 'editeurs-reunis' ); ?></span> <span><?php echo esc_html_x( 'Alexandre Soljenitsyne', 'logo, seconde ligne', 'editeurs-reunis' ); ?></span></a></p>
		<!-- /wp:paragraph -->

		<!-- wp:navigation {"overlayMenu":"mobile","overlayBackgroundColor":"base","overlayTextColor":"contrast","className":"er-menu","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"2.25rem"}}} -->
			<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Librairie', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'librairie/' ); ?>","kind":"custom","className":"er-lien-librairie"} /-->

			<!-- wp:navigation-submenu {"label":"<?php echo er_attr( _x( 'Maison d’édition YMCA-Press', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'ymca-press/' ); ?>","kind":"custom"} -->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'La maison d’édition', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'ymca-press/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Un siècle d’histoire', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'ymca-press/histoire/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Le catalogue', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'ymca-press/catalogue/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Les Amis d’YMCA-Press', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'ymca-press/amis-ymca-press/' ); ?>","kind":"custom"} /-->
			<!-- /wp:navigation-submenu -->

			<!-- wp:navigation-submenu {"label":"<?php echo er_attr( _x( 'Le centre culturel', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'centre-culturel/' ); ?>","kind":"custom"} -->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Le centre', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'centre-culturel/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Nos événements', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'centre-culturel/evenements/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Nos expositions', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'centre-culturel/expositions/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Nos podcasts', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'centre-culturel/podcasts/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Nos vidéos', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'centre-culturel/videos/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Le journal du centre', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'journal/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"<?php echo er_attr( _x( 'Nous trouver', 'menu du haut', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'nous-trouver/' ); ?>","kind":"custom"} /-->
			<!-- /wp:navigation-submenu -->
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
