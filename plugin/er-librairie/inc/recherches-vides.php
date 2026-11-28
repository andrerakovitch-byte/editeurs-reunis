<?php
/**
 * Les recherches restées sans résultat : ce que les lecteurs cherchent sur le site et n'y trouvent pas. Un livre
 * demandé et absent (une commande à passer), un nom tapé autrement que l'index ne le connaît (une graphie à
 * ajouter), une rubrique qui manque. Relevées par la recherche du panneau (l'API, recherche.php) et par la page des
 * résultats ; la liste se lit dans WooCommerce → Recherches sans résultat.
 *
 * Rien qui identifie personne : le texte cherché, combien de fois, la dernière fois, d'où (le panneau ou la page), la
 * langue de la page. Ni adresse IP, ni compte. Un texte qui ressemble à une adresse électronique, à un lien ou à un
 * numéro de téléphone n'est pas gardé. 500 recherches au plus (la plus ancienne s'en va), rien de plus d'un an.
 * Au-delà de 60 recherches vides par heure depuis la même adresse IP (un robot), on ne note plus rien ; l'adresse
 * ne sert qu'à ce compte, sous une empreinte, et n'est pas gardée.
 *
 * Le panneau cherche pendant la frappe : « zamia », « zamiat », « zamiatine » arrivent l'un après l'autre. Tout est
 * noté, et la liste regroupe les débuts d'une même frappe sous la recherche la plus longue
 * (er_recherches_vides_regrouper). L'API se garde cinq minutes en cache : une recherche répétée dans ce délai ne compte
 * qu'une fois. Les nombres sont des ordres de grandeur, pas une comptabilité.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Un texte qu'on peut garder : ni trop court ni trop long, et rien qui ressemble à une donnée personnelle.
 *
 * @param string $texte Ce qu'on a cherché.
 */
function er_recherches_vides_gardable( $texte ) {
	$texte = trim( (string) $texte );
	$long  = mb_strlen( $texte );
	if ( $long < 3 || $long > 80 ) {
		return false;
	}
	// Une adresse électronique, un lien, un numéro de téléphone (neuf chiffres ou plus, sans être un ISBN de 10 ou 13).
	if ( preg_match( '/@|https?:|www\./i', $texte ) ) {
		return false;
	}
	$chiffres = preg_replace( '/\D/', '', $texte );
	if ( strlen( $chiffres ) >= 9 && ! er_recherches_vides_isbn( preg_replace( '/[\s\-]/', '', $texte ) ) ) {
		return false;
	}
	return (bool) preg_match( '/[\p{L}\p{N}]/u', $texte );
}

/**
 * Un ISBN, à sa clé de contrôle près (un numéro de téléphone de dix chiffres a la forme d'un ISBN-10, rarement sa clé).
 *
 * @param string $s Les chiffres, sans espaces ni tirets.
 */
function er_recherches_vides_isbn( $s ) {
	$s = strtoupper( (string) $s );
	if ( preg_match( '/^\d{9}[\dX]$/', $s ) ) {
		$somme = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$somme += ( 10 - $i ) * ( 'X' === $s[ $i ] ? 10 : (int) $s[ $i ] );
		}
		return 0 === $somme % 11;
	}
	if ( preg_match( '/^97[89]\d{10}$/', $s ) ) {
		$somme = 0;
		for ( $i = 0; $i < 13; $i++ ) {
			$somme += ( $i % 2 ? 3 : 1 ) * (int) $s[ $i ];
		}
		return 0 === $somme % 10;
	}
	return false;
}

/**
 * La liste mise à jour par une recherche vide.
 *
 * @param array  $liste      La liste (clé de recherche => texte, fois, derniere, source, langue).
 * @param string $cle        La clé de la recherche (er_cle_recherche).
 * @param string $texte      Le texte tel qu'il a été tapé.
 * @param string $source     panneau ou page.
 * @param string $langue     La langue de la page.
 * @param int    $maintenant L'instant.
 * @return array
 */
function er_recherches_vides_ajouter( $liste, $cle, $texte, $source, $langue, $maintenant ) {
	$liste = is_array( $liste ) ? $liste : array();
	$avant = $liste[ $cle ] ?? array(
		'fois'   => 0,
		'source' => $source,
	);

	$liste[ $cle ] = array(
		'texte'    => $texte,
		'fois'     => (int) $avant['fois'] + 1,
		'derniere' => $maintenant,
		// Une recherche faite au moins une fois sur la page des résultats compte comme telle : on l'a voulue entière.
		'source'   => 'page' === $source || 'page' === ( $avant['source'] ?? '' ) ? 'page' : 'panneau',
		'langue'   => $langue,
	);

	// Rien de plus d'un an, et 500 au plus : les plus anciennes s'en vont.
	$liste = array_filter( $liste, fn( $r ) => (int) $r['derniere'] > $maintenant - YEAR_IN_SECONDS );
	if ( count( $liste ) > 500 ) {
		uasort( $liste, fn( $a, $b ) => (int) $b['derniere'] <=> (int) $a['derniere'] );
		$liste = array_slice( $liste, 0, 500, true );
	}
	return $liste;
}

/**
 * La liste à lire : les débuts d'une même frappe regroupés sous la recherche la plus longue, puis de la plus
 * fréquente à la plus rare. Un début est retiré quand une recherche plus longue qui commence par lui a été faite au
 * moins autant de fois (ses passages s'expliquent par la frappe) ; cherché plus souvent, il reste, pour l'écart.
 *
 * @param array $liste La liste gardée.
 * @return array
 */
function er_recherches_vides_regrouper( $liste ) {
	$cles   = array_keys( (array) $liste );
	$sortie = array();
	foreach ( (array) $liste as $cle => $r ) {
		$fois = (int) $r['fois'];
		foreach ( $cles as $autre ) {
			$autre = (string) $autre;
			if ( strlen( $autre ) > strlen( (string) $cle ) && 0 === strpos( $autre, (string) $cle ) ) {
				$fois -= (int) $liste[ $autre ]['fois'];
			}
		}
		if ( $fois > 0 ) {
			$sortie[ $cle ] = array_merge( $r, array( 'fois' => $fois ) );
		}
	}
	uasort( $sortie, fn( $a, $b ) => array( $b['fois'], $b['derniere'] ) <=> array( $a['fois'], $a['derniere'] ) );
	return $sortie;
}

/**
 * Note une recherche vide.
 *
 * @param string $texte  Ce qu'on a cherché.
 * @param string $source panneau ou page.
 */
function er_recherches_vides_noter( $texte, $source ) {
	$texte = trim( (string) $texte );
	$cle   = function_exists( 'er_cle_recherche' ) ? er_cle_recherche( $texte ) : '';
	if ( '' === $cle || ! er_recherches_vides_gardable( $texte ) ) {
		return;
	}
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$garde = 'er_rv_ip_' . md5( $ip );
	$fois  = (int) get_transient( $garde );
	if ( $fois >= 60 ) {
		return;
	}
	set_transient( $garde, $fois + 1, HOUR_IN_SECONDS );

	// Un verrou court : deux recherches simultanées ne s'effacent pas ; s'il tient trop, on renonce (une recherche ne
	// doit pas attendre son relevé).
	for ( $essai = 0; $essai < 5 && ! add_option( 'er_recherches_vides_verrou', time(), '', false ); ++$essai ) {
		if ( (int) get_option( 'er_recherches_vides_verrou' ) < time() - 10 ) {
			delete_option( 'er_recherches_vides_verrou' );
		}
		usleep( 20000 );
	}
	if ( 5 === $essai ) {
		return;
	}
	$langue = function_exists( 'pll_current_language' ) && pll_current_language( 'slug' ) ? (string) pll_current_language( 'slug' ) : substr( determine_locale(), 0, 2 );
	$liste  = er_recherches_vides_ajouter( get_option( 'er_recherches_vides', array() ), $cle, $texte, $source, $langue, time() );
	update_option( 'er_recherches_vides', $liste, false );
	delete_option( 'er_recherches_vides_verrou' );
}

// La page des résultats : une recherche simple (sans filtres) qui ne trouve rien.
add_action(
	'wp',
	function () {
		global $wp_query;
		if ( is_admin() || ! is_search() || is_paged() || ! $wp_query || (int) $wp_query->found_posts > 0 ) {
			return;
		}
		$autres = array_diff( array_keys( $_GET ), array( 's', 'post_type', 'lang' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- on regarde seulement quels paramètres sont là.
		if ( ! $autres ) {
			er_recherches_vides_noter( get_search_query( false ), 'page' );
		}
	}
);

/* La liste, dans l'administration ------------------------------------------------------------------------------ */

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'woocommerce',
			__( 'Recherches sans résultat', 'er-librairie' ),
			__( 'Recherches sans résultat', 'er-librairie' ),
			'manage_woocommerce',
			'er-recherches-vides',
			'er_recherches_vides_page'
		);
	},
	60
);

/**
 * La page : ce qu'on a cherché, combien de fois, la dernière fois, d'où ; et un lien pour chercher dans le catalogue
 * (le livre y est peut-être, sous une autre graphie).
 */
function er_recherches_vides_page() {
	$liste = er_recherches_vides_regrouper( get_option( 'er_recherches_vides', array() ) );
	echo '<div class="wrap"><h1>' . esc_html__( 'Recherches sans résultat', 'er-librairie' ) . '</h1>';
	echo '<p>' . esc_html__( 'Ce que les lecteurs ont cherché sur le site sans rien trouver, du plus fréquent au plus rare. Un livre demandé et absent, c’est peut-être une commande à passer ; un nom écrit autrement, une graphie à ajouter à la fiche de l’auteur. Les nombres sont des ordres de grandeur.', 'er-librairie' ) . '</p>';
	if ( ! $liste ) {
		echo '<p><em>' . esc_html__( 'Rien pour l’instant.', 'er-librairie' ) . '</em></p></div>';
		return;
	}
	$vider = wp_nonce_url( admin_url( 'admin-post.php?action=er_recherches_vides_vider' ), 'er_recherches_vides_vider' );
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Recherche', 'er-librairie' ) . '</th><th>' . esc_html__( 'Fois', 'er-librairie' ) . '</th><th>' . esc_html__( 'Dernière fois', 'er-librairie' ) . '</th><th>' . esc_html__( 'Où', 'er-librairie' ) . '</th><th>' . esc_html__( 'Langue', 'er-librairie' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $liste as $r ) {
		$catalogue = add_query_arg(
			array(
				'post_type' => 'product',
				's'         => rawurlencode( $r['texte'] ),
			),
			admin_url( 'edit.php' )
		);
		echo '<tr><td>' . esc_html( $r['texte'] ) . '</td><td>' . (int) $r['fois'] . '</td><td>' . esc_html( wp_date( 'j M Y, H:i', (int) $r['derniere'] ) ) . '</td><td>'
			. esc_html( 'page' === $r['source'] ? __( 'Page des résultats', 'er-librairie' ) : __( 'Panneau de recherche', 'er-librairie' ) ) . '</td><td>' . esc_html( $r['langue'] ) . '</td>'
			. '<td><a href="' . esc_url( $catalogue ) . '">' . esc_html__( 'Chercher dans le catalogue', 'er-librairie' ) . '</a></td></tr>';
	}
	echo '</tbody></table>';
	echo '<p><a class="button" href="' . esc_url( $vider ) . '">' . esc_html__( 'Vider la liste', 'er-librairie' ) . '</a></p></div>';
}

add_action(
	'admin_post_er_recherches_vides_vider',
	function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'er-librairie' ) );
		}
		check_admin_referer( 'er_recherches_vides_vider' );
		delete_option( 'er_recherches_vides' );
		wp_safe_redirect( admin_url( 'admin.php?page=er-recherches-vides' ) );
		exit;
	}
);
