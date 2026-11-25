<?php
/**
 * Title: En-tête
 * Slug: editeurs-reunis/en-tete
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * L'en-tête plein écran, de marge à marge, en blanc depuis le 25 novembre 2026 (« B3 blanc », canevas « La barre
 * d'informations, de bas en haut — versions ») : la barre d'informations (48 px), qui porte à son bout la langue et le
 * compte ; la barre du nom (120 px), blanche, le nom espacé comme sur l'ancien site ; la ligne de la maison, sur sa
 * couleur. Au téléphone, la barre du nom n'a que le nom et le menu (deux barres), qui contient en tête la recherche et
 * le panier, au bas la langue et le compte. Le détail est dans inc/entete.php ; en défilant, seule la ligne de la
 * maison reste en haut de l'écran. Ce qui suit décrit l'en-tête noir d'avant ; les couleurs ont changé, pas l'ordre.
 *
 * Il prolonge l'en-tête du 21 novembre 2026 (B3 : « noir dessus, la couleur dessous »), dont le nom en capitales
 * espacées est gardé :
 *
 * - La barre noire, la même partout : le nom ; les trois maisons, chacune précédée d'un point de sa couleur (vert
 *   pour la librairie, brun pour YMCA-Press, bleu nuit pour le centre culturel), la maison où l'on est soulignée ;
 *   puis le coin droit, en pictos (A′, retenue le 21 novembre) : la loupe, qui ouvre le panneau de recherche
 *   (inc/recherche.php), le compte, le panier, chacun dans un rond de 36 px ; au survol, le rond grisé ; sélectionné
 *   (la recherche ouverte, la page du compte), le rond blanc et le picto noir, à la même place. Le nombre de livres
 *   au panier dans une pastille pervenche, seulement s'il y en a. Les trois langues dans un interrupteur, la langue
 *   de la page en blanc plein. Le nom garde l'espacement du logo d'origine ; entre 1280 et 1440 px, « Maison
 *   d'édition YMCA-Press » devient « YMCA-Press ».
 * - Au téléphone : la loupe et le panier dans la barre ; le compte et la langue au bas du menu (≡).
 * - Dessous, les lignes des trois maisons, sur leur couleur (le code court [er_lignes]) : les rayons de la librairie
 *   (la composition « menu-librairie »), les pages d'YMCA-Press, celles du centre. Seule celle de la maison de la page
 *   est visible (la librairie sur l'accueil, aucune sur les pages communes) ; un clic sur une maison de la barre montre
 *   la sienne, sans quitter la page (assets/js/entete.js).
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
<!-- wp:html -->[er_infos]<!-- /wp:html -->

<!-- wp:group {"className":"er-barre","backgroundColor":"base","textColor":"contrast","layout":{"type":"constrained"}} -->
<div class="wp-block-group er-barre has-contrast-color has-base-background-color has-text-color has-background">
	<!-- wp:group {"align":"wide","className":"er-barre-dedans","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide er-barre-dedans">
		<!-- wp:paragraph {"className":"er-logo"} -->
		<p class="er-logo"><a href="<?php echo er_lien(); ?>" rel="home"><span><?php echo esc_html_x( 'Centre culturel', 'logo, première ligne', 'editeurs-reunis' ); ?></span> <span><?php echo esc_html_x( 'Alexandre Soljenitsyne', 'logo, seconde ligne', 'editeurs-reunis' ); ?></span></a></p>
		<!-- /wp:paragraph -->

		<!-- wp:navigation {"overlayMenu":"always","overlayBackgroundColor":"base","overlayTextColor":"contrast","icon":"handle","className":"er-menu","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"1.75rem"}}} -->
			<!-- wp:html -->[er_menu_outils]<!-- /wp:html -->
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

		<!-- wp:html --><a class="er-loupe er-picto" href="<?php echo esc_url( er_lien_recherche() ); ?>" aria-controls="er-recherche" aria-expanded="false" aria-haspopup="dialog"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M15.5 15.5L20 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><span class="screen-reader-text er-loupe-libelle"><?php esc_html_e( 'Rechercher', 'editeurs-reunis' ); ?></span></a><!-- /wp:html -->

		<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true,"productCountVisibility":"greater_than_zero","className":"er-panier"} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:html -->[er_lignes]<!-- /wp:html -->
