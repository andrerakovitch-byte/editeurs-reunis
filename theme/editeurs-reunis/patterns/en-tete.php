<?php
/**
 * Title: En-tête
 * Slug: editeurs-reunis/en-tete
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * L'en-tête retenu le 21 novembre 2026 (B3 : « noir dessus, la couleur dessous »).
 *
 * - La barre noire, la même partout : le nom ; les trois maisons, chacune précédée d'un point de sa couleur (vert
 *   pour la librairie, brun pour YMCA-Press, bleu nuit pour le centre culturel), la maison où l'on est soulignée ;
 *   puis le coin droit, en toutes lettres (A, retenu le 21 novembre) : « Rechercher », qui ouvre le panneau de
 *   recherche (inc/recherche.php) et devient « Fermer » ; « Compte » ; « Panier », avec une pastille pervenche quand
 *   il n'est pas vide ; les trois langues dans un interrupteur, la langue de la page en blanc plein. Entre 1280 et
 *   1440 px, « Maison d'édition YMCA-Press » devient « YMCA-Press ».
 * - Au téléphone : la loupe et le panier dans la barre ; le compte et la langue au bas du menu (≡).
 * - Dessous, le menu de la maison où l'on est, sur sa couleur : les rayons dans la librairie (la composition
 *   « menu-librairie », appelée par les gabarits de la boutique), les pages d'YMCA-Press ou du centre ailleurs (le code
 *   court [er_sous_menu]). Rien dessous sur l'accueil et les pages communes (panier, compte, pages légales).
 * - Plus de menus déroulants à l'ordinateur ; au téléphone, le menu (≡) montre les trois maisons et leurs pages, le
 *   compte et la langue.
 *
 * La langue et le menu de la maison sont des codes courts, pour rester justes même si l'en-tête est retouché dans
 * l'éditeur du site.
 *
 * @package editeurs-reunis
 */

$er_lien_maison = function ( $libelle, $chemin, $cle, $pages = array(), $court = '' ) {
	if ( $court ) {
		// Le nom long, et le nom court qui le remplace quand la barre est étroite (style.css).
		$libelle = '<span class="er-nom-long">' . esc_html( $libelle ) . '</span><span class="er-nom-court">' . esc_html( $court ) . '</span>';
	}
	$classe = 'er-onglet er-onglet-' . $cle;
	if ( ! $pages ) {
		return '<!-- wp:navigation-link {"label":"' . er_attr( $libelle ) . '","url":"' . er_lien( $chemin ) . '","kind":"custom","className":"' . $classe . '"} /-->';
	}
	$html = '<!-- wp:navigation-submenu {"label":"' . er_attr( $libelle ) . '","url":"' . er_lien( $chemin ) . '","kind":"custom","className":"' . $classe . '"} -->';
	foreach ( $pages as $page_chemin => $page_libelle ) {
		$html .= '<!-- wp:navigation-link {"label":"' . er_attr( $page_libelle ) . '","url":"' . er_lien( $page_chemin ) . '","kind":"custom"} /-->';
	}
	return $html . '<!-- /wp:navigation-submenu -->';
};
$er_pages = er_pages_des_maisons();
?>
<!-- wp:group {"className":"er-barre","backgroundColor":"noir","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group er-barre has-base-color has-noir-background-color has-text-color has-background">
	<!-- wp:group {"align":"wide","className":"er-barre-dedans","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide er-barre-dedans">
		<!-- wp:paragraph {"className":"er-logo"} -->
		<p class="er-logo"><a href="<?php echo er_lien(); ?>" rel="home"><span><?php echo esc_html_x( 'Centre culturel', 'logo, première ligne', 'editeurs-reunis' ); ?></span> <span><?php echo esc_html_x( 'Alexandre Soljenitsyne', 'logo, seconde ligne', 'editeurs-reunis' ); ?></span></a></p>
		<!-- /wp:paragraph -->

		<!-- wp:navigation {"overlayMenu":"always","overlayBackgroundColor":"noir","overlayTextColor":"base","className":"er-menu","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"1.75rem"}}} -->
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput -- du balisage de blocs, échappé pièce à pièce.
			echo $er_lien_maison( _x( 'Librairie', 'menu du haut', 'editeurs-reunis' ), 'librairie/', 'librairie' );
			echo $er_lien_maison( _x( 'Maison d’édition YMCA-Press', 'menu du haut', 'editeurs-reunis' ), 'ymca-press/', 'ymca', $er_pages['ymca'], 'YMCA-Press' );
			echo $er_lien_maison( _x( 'Le centre culturel', 'menu du haut', 'editeurs-reunis' ), 'centre-culturel/', 'centre', $er_pages['centre'] );
			// phpcs:enable
			?>
			<!-- wp:navigation-link {"label":"<?php echo er_attr( __( 'Mon compte', 'editeurs-reunis' ) ); ?>","url":"<?php echo er_lien( 'mon-compte/' ); ?>","kind":"custom","className":"er-menu-telephone"} /-->
			<!-- wp:html -->[er_langues_menu]<!-- /wp:html -->
		<!-- /wp:navigation -->

		<!-- wp:html --><a class="er-loupe" href="<?php echo esc_url( er_lien_recherche() ); ?>" aria-controls="er-recherche" aria-expanded="false" aria-haspopup="dialog"><span class="er-loupe-icone" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M15.5 15.5L20 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="er-loupe-fermer" aria-hidden="true"><svg viewBox="0 0 24 24" width="14" height="14" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><?php esc_html_e( 'Fermer', 'editeurs-reunis' ); ?></span><span class="er-loupe-mot" aria-hidden="true"><?php esc_html_e( 'Rechercher', 'editeurs-reunis' ); ?></span><span class="screen-reader-text er-loupe-libelle"><?php esc_html_e( 'Rechercher', 'editeurs-reunis' ); ?></span></a><!-- /wp:html -->

		<!-- wp:html -->[er_compte_entete]<!-- /wp:html -->

		<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true,"productCountVisibility":"greater_than_zero","className":"er-panier"} /-->

		<!-- wp:html -->[er_langues_entete]<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:html -->[er_sous_menu]<!-- /wp:html -->
