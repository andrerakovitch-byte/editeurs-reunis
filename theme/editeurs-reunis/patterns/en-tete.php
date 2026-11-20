<?php
/**
 * Title: En-tête
 * Slug: editeurs-reunis/en-tete
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * La barre noire (chercher un livre, le compte, le panier), puis le nom et le menu.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"className":"er-barre","backgroundColor":"noir","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group er-barre has-base-color has-noir-background-color has-text-color has-background">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:search {"label":"Chercher dans la librairie","showLabel":false,"placeholder":"Rechercher un titre, un auteur…","buttonText":"Chercher","buttonPosition":"button-inside","buttonUseIcon":true,"query":{"post_type":"product"},"className":"er-chercher"} /-->

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
		<p class="er-logo"><a href="<?php echo er_lien(); ?>" rel="home"><span>Centre culturel</span> <span>Alexandre Soljenitsyne</span></a></p>
		<!-- /wp:paragraph -->

		<!-- wp:navigation {"overlayMenu":"mobile","overlayBackgroundColor":"base","overlayTextColor":"contrast","className":"er-menu","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"2.25rem"}}} -->
			<!-- wp:navigation-link {"label":"Librairie","url":"<?php echo er_lien( 'librairie/' ); ?>","kind":"custom","className":"er-lien-librairie"} /-->

			<!-- wp:navigation-submenu {"label":"Maison d’édition YMCA-Press","url":"<?php echo er_lien( 'ymca-press/' ); ?>","kind":"custom"} -->
				<!-- wp:navigation-link {"label":"La maison d’édition","url":"<?php echo er_lien( 'ymca-press/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Un siècle d’histoire","url":"<?php echo er_lien( 'ymca-press/histoire/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Le catalogue","url":"<?php echo er_lien( 'ymca-press/catalogue/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Les Amis d’YMCA-Press","url":"<?php echo er_lien( 'ymca-press/amis-ymca-press/' ); ?>","kind":"custom"} /-->
			<!-- /wp:navigation-submenu -->

			<!-- wp:navigation-submenu {"label":"Le centre culturel","url":"<?php echo er_lien( 'centre-culturel/' ); ?>","kind":"custom"} -->
				<!-- wp:navigation-link {"label":"Le centre","url":"<?php echo er_lien( 'centre-culturel/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Nos événements","url":"<?php echo er_lien( 'centre-culturel/evenements/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Nos expositions","url":"<?php echo er_lien( 'centre-culturel/expositions/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Nos podcasts","url":"<?php echo er_lien( 'centre-culturel/podcasts/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Nos vidéos","url":"<?php echo er_lien( 'centre-culturel/videos/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Le journal du centre","url":"<?php echo er_lien( 'journal/' ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Nous trouver","url":"<?php echo er_lien( 'nous-trouver/' ); ?>","kind":"custom"} /-->
			<!-- /wp:navigation-submenu -->
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
