<?php
/**
 * L'en-tête retenu le 26 novembre 2026, sur tout le site (canevas « Les pages intérieures, dans la direction »,
 * planches H, I, J, N, P5 et R1) :
 *
 * - la barre du nom : le nom, puis Librairie, Maison d'édition, Centre culturel, Journal en 20 px, à 16 px l'un de
 *   l'autre, sans chevron ; leur menu s'ouvre au survol (et au clic, au doigt) ; les autres noms pâlissent ; puis la
 *   loupe et le sac, à 20 px dans des ronds de 40 px ; un filet sous la barre, toujours ;
 * - les menus, de 446 px : la Librairie en panneau (ses rayons, les sous-rayons du rayon survolé, et le dernier livre
 *   arrivé du rayon ou du sous-rayon survolé), les trois autres en grands mots, avec la photo de la page survolée ;
 * - le sac ouvre le panier et le compte, comme chez Apple ; la loupe, la recherche (inc/recherche.php) ;
 * - au téléphone et jusqu'à 1 320 px, le menu (≡) ouvre le plan du site en plein écran (er_em_plan()).
 *
 * Les données des menus viennent d'inc/essai-menus.php (er_em_donnees()), écrit pour les pages d'essai.
 *
 * @package editeurs-reunis
 */

/**
 * Le dernier livre arrivé d'un rayon : en stock, avec une couverture, sans ceux déjà montrés dans ce rayon ; en
 * Jeunesse, rien qui soit aussi rangé en Pensée et religion.
 *
 * @param string $slug     Le rayon ou le sous-rayon.
 * @param array  $exclus   Les livres déjà pris.
 * @param bool   $jeunesse Le rayon est la Jeunesse.
 */
function er_menu_dernier_livre( $slug, $exclus = array(), $jeunesse = false ) {
	$taxo = array(
		array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $slug,
		),
	);
	if ( $jeunesse ) {
		$taxo[]             = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => 'pensee-et-religion',
			'operator' => 'NOT IN',
		);
		$taxo['relation'] = 'AND';
	}
	$ids = get_posts(
		array(
			'post_type'     => 'product',
			'post_status'   => 'publish',
			'numberposts'   => 1,
			'fields'        => 'ids',
			'no_found_rows' => true,
			'post__not_in'  => array_map( 'intval', $exclus ),
			'tax_query'     => $taxo, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_query'    => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Le livre de chaque rayon et de chaque sous-rayon, gardé six heures : la clé du rayon (rayon-…) ou du sous-rayon
 * (sous-…) → l'identifiant du livre.
 */
function er_menu_livres() {
	$livres = get_transient( 'er_menu_livres_v1' );
	if ( is_array( $livres ) ) {
		return $livres;
	}
	$livres = array();
	$rayons = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
		)
	);
	foreach ( is_wp_error( $rayons ) ? array() : $rayons as $r ) {
		if ( in_array( $r->slug, array( 'uncategorized', 'sans-rayon' ), true ) ) {
			continue;
		}
		$jeunesse = false !== strpos( $r->slug, 'jeunesse' );
		$pris     = array();
		$id       = er_menu_dernier_livre( $r->slug, $pris, $jeunesse );
		if ( $id ) {
			$livres[ 'rayon-' . $r->slug ] = $id;
			$pris[]                         = $id;
		}
		$enfants = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $r->term_id,
				'hide_empty' => true,
			)
		);
		foreach ( is_wp_error( $enfants ) ? array() : $enfants as $e ) {
			$id = er_menu_dernier_livre( $e->slug, $pris, $jeunesse );
			if ( $id ) {
				$livres[ 'sous-' . $e->slug ] = $id;
				$pris[]                        = $id;
			}
		}
	}
	set_transient( 'er_menu_livres_v1', $livres, 6 * HOUR_IN_SECONDS );
	return $livres;
}
add_action( 'woocommerce_update_product', fn() => delete_transient( 'er_menu_livres_v1' ) );

/**
 * Un livre dans le panneau de la Librairie : « Au rayon … », la couverture, le titre, l'auteur et le prix.
 *
 * @param int    $id   Le livre.
 * @param string $cle  La clé du rayon ou du sous-rayon.
 * @param string $nom  Le nom du rayon ou du sous-rayon.
 * @param bool   $voir Montré d'emblée.
 */
function er_menu_livre( $id, $cle, $nom, $voir ) {
	$produit = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
	if ( ! $produit ) {
		return '';
	}
	$titre  = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	$auteur = er_em_auteurs( $id );
	$prix   = trim( wp_strip_all_tags( html_entity_decode( $produit->get_price_html(), ENT_QUOTES, 'UTF-8' ) ) );
	$sous   = array_filter( array( $auteur, $prix ) );
	/* translators: %s: le nom d'un rayon (« Au rayon Poésie ») */
	return '<div class="er-mn-livre" data-livre="' . esc_attr( $cle ) . '"' . ( $voir ? '' : ' hidden' ) . '><p class="er-mn-livre-rayon">' . esc_html( sprintf( __( 'Au rayon %s', 'editeurs-reunis' ), $nom ) ) . '</p>'
		. '<a href="' . esc_url( get_permalink( $id ) ) . '">' . er_em_image( get_post_thumbnail_id( $id ), 'medium' )
		. '<span class="er-mn-livre-texte"><span class="er-mn-livre-titre"' . er_em_lang( $titre ) . '>' . esc_html( $titre ) . '</span>'
		. ( $sous ? '<span class="er-mn-livre-sous">' . esc_html( implode( ' · ', $sous ) ) . '</span>' : '' ) . '</span></a></div>';
}

/**
 * Le panneau de la Librairie (H) : les rayons ; les sous-rayons du rayon survolé ; le livre du rayon ou du sous-rayon.
 *
 * @param array $m Le menu de la Librairie (er_em_donnees()).
 */
function er_menu_panneau_librairie( $m ) {
	$livres = er_menu_livres();
	$rayons = '';
	$sous   = '';
	$blocs  = '';
	foreach ( $m['rayons'] as $i => $r ) {
		$rayons .= '<li><a href="' . esc_url( $r['lien'] ) . '" data-rayon="' . esc_attr( $r['cle'] ) . '"' . ( 0 === $i ? ' class="est-actif"' : '' ) . '>' . esc_html( $r['nom'] ) . '</a></li>';
		if ( isset( $livres[ $r['cle'] ] ) ) {
			$blocs .= er_menu_livre( $livres[ $r['cle'] ], $r['cle'], $r['nom'], 0 === $i );
		}
		$groupes = '';
		foreach ( $r['groupes'] as $titre => $liens ) {
			$titre = substr( (string) $titre, 1 );
			$items = '';
			foreach ( $liens as $l ) {
				$terme = get_term_by( 'name', $l[0], 'product_cat' );
				$cle   = $terme ? 'sous-' . $terme->slug : '';
				$items .= '<li><a href="' . esc_url( $l[1] ) . '"' . ( $cle && isset( $livres[ $cle ] ) ? ' data-livre="' . esc_attr( $cle ) . '"' : '' ) . '>' . esc_html( $l[0] ) . '</a></li>';
				if ( $cle && isset( $livres[ $cle ] ) ) {
					$blocs .= er_menu_livre( $livres[ $cle ], $cle, $l[0], false );
				}
			}
			$groupes .= '<div class="er-mn-groupe">' . ( '' !== $titre ? '<p class="er-mn-groupe-titre">' . esc_html( $titre ) . '</p>' : '' ) . '<ul>' . $items . '</ul></div>';
		}
		/* translators: %s: le nom d'un rayon */
		$tout  = sprintf( __( 'Tout le rayon %s →', 'editeurs-reunis' ), $r['nom'] );
		$sous .= '<div class="er-mn-sous-rayon" data-rayon="' . esc_attr( $r['cle'] ) . '"' . ( 0 === $i ? '' : ' hidden' ) . '>'
			. '<a class="er-mn-tout-rayon" href="' . esc_url( $r['lien'] ) . '">' . esc_html( $tout ) . '</a>'
			. ( $groupes ? '<div class="er-mn-groupes">' . $groupes . '</div>' : '' ) . '</div>';
	}
	return '<div class="er-em-panneau er-mn-librairie" id="er-em-librairie" hidden><div class="er-mn-dedans">'
		. '<div class="er-mn-rayons-col"><ul class="er-mn-rayons">' . $rayons . '</ul>'
		. '<a class="er-mn-toute" href="' . esc_url( $m['lien'] ) . '">' . esc_html__( 'Toute la librairie →', 'editeurs-reunis' ) . '</a></div>'
		. '<div class="er-mn-sous">' . $sous . '</div>'
		. '<div class="er-mn-livres" aria-live="polite">' . $blocs . '</div></div></div>';
}

/**
 * Le panneau du sac (J) : le panier (rempli par assets/js/menus.js, depuis l'API du magasin), et le compte.
 */
function er_menu_panneau_panier() {
	$compte = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mon-compte/' );
	$panier = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' );
	$caisse = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/commande/' );
	if ( is_user_logged_in() ) {
		$liens = array(
			array( __( 'Mes commandes', 'editeurs-reunis' ), wc_get_account_endpoint_url( 'orders' ) ),
			array( __( 'Mes adresses', 'editeurs-reunis' ), wc_get_account_endpoint_url( 'edit-address' ) ),
			array( __( 'Mes informations', 'editeurs-reunis' ), wc_get_account_endpoint_url( 'edit-account' ) ),
			array( __( 'Me déconnecter', 'editeurs-reunis' ), wp_logout_url( home_url( '/' ) ) ),
		);
	} else {
		$liens = array(
			array( __( 'Me connecter', 'editeurs-reunis' ), $compte ),
			array( __( 'Créer un compte', 'editeurs-reunis' ), $compte ),
			array( __( 'Suivre une commande', 'editeurs-reunis' ), $compte ),
		);
	}
	$compte_html = '';
	foreach ( $liens as $l ) {
		$compte_html .= '<li><a href="' . esc_url( $l[1] ) . '">' . esc_html( $l[0] ) . '</a></li>';
	}
	return '<div class="er-em-panneau er-mn-panier" id="er-em-panier" hidden><div class="er-mn-dedans">'
		. '<div class="er-mn-panier-col"><p class="er-mn-titre">' . esc_html__( 'Votre panier', 'editeurs-reunis' ) . '</p>'
		. '<div class="er-mn-panier-liste" data-vide="' . esc_attr__( 'Votre panier est vide.', 'editeurs-reunis' ) . '" data-autres="' . esc_attr__( 'et %d autres livres', 'editeurs-reunis' ) . '" data-total="' . esc_attr__( 'Sous-total : %s', 'editeurs-reunis' ) . '"><p class="er-mn-panier-attente">' . esc_html__( 'Votre panier…', 'editeurs-reunis' ) . '</p></div>'
		. '<p class="er-mn-panier-boutons"><a class="er-mn-bouton" href="' . esc_url( $panier ) . '">' . esc_html__( 'Voir le panier', 'editeurs-reunis' ) . '</a>'
		. '<a class="er-mn-bouton er-mn-bouton-plein" href="' . esc_url( $caisse ) . '">' . esc_html__( 'Commander', 'editeurs-reunis' ) . '</a></p></div>'
		. '<div class="er-mn-compte-col"><p class="er-mn-titre">' . esc_html__( 'Mon compte', 'editeurs-reunis' ) . '</p><ul class="er-mn-compte">' . $compte_html . '</ul></div>'
		. '</div></div>';
}

/**
 * Les quatre noms de la barre : chacun ouvre son menu au survol.
 */
function er_menu() {
	$html = '';
	foreach ( er_em_donnees() as $cle => $m ) {
		$html .= '<button type="button" class="er-em-bouton" data-er-em="' . esc_attr( $cle ) . '" aria-expanded="false" aria-controls="er-em-' . esc_attr( $cle ) . '">'
			. '<span>' . esc_html( $m['nom'] ) . '</span></button>';
	}
	return '<nav class="er-em er-mn" aria-label="' . esc_attr__( 'Le site', 'editeurs-reunis' ) . '">' . $html . '</nav>';
}
add_shortcode( 'er_menu', 'er_menu' );

/**
 * Le sac : il ouvre le panier et le compte ; la pastille dit combien de livres il y a.
 */
function er_menu_sac() {
	$n = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
	return '<button type="button" class="er-sac er-picto" data-er-em="panier" aria-expanded="false" aria-controls="er-em-panier" aria-label="' . esc_attr__( 'Le panier et mon compte', 'editeurs-reunis' ) . '">'
		. '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 8h14l-1.2 11.2a1.5 1.5 0 0 1-1.5 1.3H7.7a1.5 1.5 0 0 1-1.5-1.3L5 8z"/><path d="M9 10V6.5a3 3 0 0 1 6 0V10"/></svg>'
		. '<span class="er-sac-nombre"' . ( $n ? '' : ' hidden' ) . '>' . esc_html( (string) $n ) . '</span></button>';
}
add_shortcode( 'er_menu_sac', 'er_menu_sac' );

/**
 * Le bouton du menu (≡), jusqu'à 1 320 px : il ouvre le plan du site.
 */
add_shortcode( 'er_menu_bouton', 'er_essai_bouton_menu' );

/**
 * Sous la barre du nom : le voile, les quatre menus, le panier, puis le plan du site.
 */
function er_menu_panneaux() {
	$html = '<div class="er-em-voile" aria-hidden="true"></div>';
	foreach ( er_em_donnees() as $cle => $m ) {
		$html .= 'librairie' === $cle ? er_menu_panneau_librairie( $m ) : er_em_panneau_mots( $cle, $m );
	}
	return $html . er_menu_panneau_panier() . er_em_plan();
}
add_shortcode( 'er_menu_panneaux', 'er_menu_panneaux' );

// Le script des menus, partout.
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script(
			'editeurs-reunis-menus',
			get_theme_file_uri( 'assets/js/menus.js' ),
			array(),
			wp_get_theme()->get( 'Version' ) . '.' . filemtime( get_theme_file_path( 'assets/js/menus.js' ) ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_localize_script(
			'editeurs-reunis-menus',
			'erMenus',
			array( 'panier' => esc_url_raw( rest_url( 'wc/store/v1/cart' ) ) )
		);
	}
);
