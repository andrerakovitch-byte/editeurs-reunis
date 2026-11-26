<?php
/**
 * Title: En-tête
 * Slug: editeurs-reunis/en-tete
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * L'en-tête retenu le 26 novembre 2026, sur tout le site (canevas « Les pages intérieures, dans la direction »,
 * planches H, I, J, N, P5 et R1 ; le détail est dans inc/menus.php) :
 *
 * - la barre d'informations : les horaires, l'exposition, le prochain événement, l'adresse ; à son bout, la langue
 *   (la bulle « Aя » et le nom de la langue) ;
 * - la barre du nom, blanche, fermée d'un filet : le nom ; Librairie, Maison d'édition, Centre culturel, Journal, dont
 *   le menu s'ouvre au survol ; la loupe (la recherche) et le sac (le panier et le compte) ;
 * - dessous, les menus de 446 px, qui couvrent la tête de la page et sa bande ; le plan du site au téléphone (≡).
 *
 * Il remplace l'en-tête des trois maisons (la barre et les lignes de couleur, 21-25 nov. 2026).
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

		<!-- wp:html --><div class="er-mn-droite">[er_menu]<span class="er-mn-outils"><a class="er-loupe er-picto" href="<?php echo esc_url( er_lien_recherche() ); ?>" aria-controls="er-recherche" aria-expanded="false" aria-haspopup="dialog"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="11.5" cy="11.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M16.4 16.4L20.5 20.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><span class="screen-reader-text er-loupe-libelle"><?php esc_html_e( 'Rechercher', 'editeurs-reunis' ); ?></span></a>[er_menu_sac][er_menu_bouton]</span></div><!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:html -->[er_menu_panneaux]<!-- /wp:html -->
