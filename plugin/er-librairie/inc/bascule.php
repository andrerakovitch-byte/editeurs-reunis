<?php
/**
 * La bascule depuis Wix : les anciennes adresses mènent aux nouvelles, pour les liens qui circulent et pour les
 * moteurs de recherche. Les rencontres (/events-1/…) et le journal (/post/…) ont leurs redirections dans leurs
 * fichiers ; ici, les livres et les pages.
 *
 * - Un livre de Wix (/product-page/<nom>) : Wix nommait la page d'après « Titre - Auteur ». Le livre qui porte ce nom
 *   dans `er_wix_slug` (posé par `wp er bascule livres appliquer`, d'après le plan du site de Wix) ; sinon celui dont
 *   le titre ouvre le nom, par la clé de recherche (les trois écritures, sans accents) ; sinon la recherche de la
 *   librairie sur ces mots.
 * - Une page de Wix : sa nouvelle page, d'après la table ci-dessous, si elle existe.
 *
 * Avant la bascule : `wp er bascule livres` montre ce que chaque adresse de livre trouve, `… appliquer` le retient ;
 * `wp er bascule pages`, où mène chaque page.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les pages de Wix et leur nouvelle adresse (chemin de la page).
 */
function er_bascule_pages() {
	return array(
		'librairie'                => 'librairie',
		'evenements'               => 'centre-culturel/evenements',
		'conferences'              => 'centre-culturel/evenements',
		'expo'                     => 'centre-culturel/expositions',
		'podcast'                  => 'centre-culturel/podcasts',
		'audiotheque'              => 'centre-culturel/podcasts',
		'videos'                   => 'centre-culturel/videos',
		'notre-histoire'           => 'ymca-press/histoire',
		'les-editions-ymca-press'  => 'ymca-press',
		'nous-soutenir'            => 'ymca-press/amis-ymca-press',
		'store-policy'             => 'conditions-generales-de-vente',
		'politique-confidentialite' => 'politique-de-confidentialite',
	);
}

/**
 * Le livre que désigne le nom d'une page de livre de Wix, parmi des livres donnés par leurs clés. Dans l'ordre :
 * le titre qui ouvre le nom (« titre-auteur », le cas courant) ; le titre ailleurs dans le nom (« auteur-titre ») ;
 * un titre à une ou deux lettres près (les fautes de frappe de Wix : « la-sonate-à-kreutzner »). Le titre le plus
 * long l'emporte ; à titre égal (deux éditions, deux langues), celui dont les auteurs suivent, puis celui de la même
 * écriture que le nom.
 *
 * @param string $nom    Le nom de la page (« dieu-et-l-etat-bakounine »), décodé.
 * @param array  $livres Identifiant → array( 'titre' => clé du titre, 'tout' => clé du titre, des auteurs…,
 *                       'cyrillique' => le titre est-il en cyrillique ).
 * @return int Le livre, ou 0.
 */
function er_bascule_livre( $nom, $livres ) {
	$nom      = (string) preg_replace( '/^copie-de-/u', '', $nom );
	$cle      = ' ' . er_cle_recherche( str_replace( '-', ' ', $nom ) ) . ' ';
	$sans_num = (string) preg_replace( '/ \d+ $/', ' ', $cle ); // « …-1 » : un doublon de Wix.
	$cyr      = (bool) preg_match( '/\p{Cyrillic}/u', $nom );
	$retenu   = 0;
	$meilleur = 0;
	foreach ( $livres as $id => $l ) {
		$titre = ' ' . trim( $l['titre'] ) . ' ';
		$long  = strlen( $titre );
		if ( $long < 3 ) {
			continue;
		}
		$niveau = 0;
		if ( 0 === strpos( $cle, $titre ) || 0 === strpos( $sans_num, $titre ) ) {
			$niveau = 3;
		} elseif ( $long >= 10 && false !== strpos( $cle, $titre ) ) {
			$niveau = 2;
		} elseif ( $long >= 12 && er_bascule_presque( trim( $cle ), trim( $titre ) ) ) {
			$niveau = 1;
		}
		if ( ! $niveau ) {
			continue;
		}
		$reste   = array_filter( explode( ' ', trim( str_replace( $titre, ' ', $cle ) ) ) );
		$communs = count( array_intersect( $reste, explode( ' ', trim( $l['tout'] ?? '' ) ) ) );
		$score   = $niveau * 1000000 + $long * 1000 + $communs * 10 + ( ( $l['cyrillique'] ?? $cyr ) === $cyr ? 2 : 0 ) + ( 0 === strpos( $cle, $titre ) ? 1 : 0 );
		if ( $score > $meilleur ) {
			$meilleur = $score;
			$retenu   = (int) $id;
		}
	}
	return $retenu;
}

/**
 * Le début d'un nom diffère-t-il d'un titre d'une lettre ou deux au plus (deux à partir de 24 lettres) ? Une lettre en
 * trop, en moins ou changée.
 *
 * @param string $cle   La clé du nom.
 * @param string $titre La clé du titre.
 */
function er_bascule_presque( $cle, $titre ) {
	$n     = strlen( $titre );
	$seuil = $n >= 24 ? 2 : 1;
	for ( $k = $n - $seuil; $k <= $n + $seuil; $k++ ) {
		if ( $k <= strlen( $cle ) && levenshtein( substr( $cle, 0, $k ), $titre ) <= $seuil && ( strlen( $cle ) === $k || ' ' === $cle[ $k ] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Les livres de l'index de recherche, par leurs clés.
 */
function er_bascule_livres_de_l_index() {
	global $wpdb;
	$livres = array();
	$table  = er_index_table();
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- le nom de la table vient de $wpdb->prefix.
	foreach ( (array) $wpdb->get_results( "SELECT objet, titre, cle_titre, cle FROM {$table} WHERE genre = 'livre'" ) as $l ) {
		$livres[ (int) $l->objet ] = array(
			'titre'      => trim( $l->cle_titre ),
			'tout'       => trim( $l->cle ),
			'cyrillique' => (bool) preg_match( '/\p{Cyrillic}/u', $l->titre ),
		);
	}
	return $livres;
}

/**
 * Le livre d'une ancienne adresse de Wix : celui qui la retient, sinon celui que son nom désigne.
 *
 * @param string $nom Le nom de la page, décodé.
 */
function er_bascule_livre_du_site( $nom ) {
	$ids = get_posts(
		array(
			'post_type'   => 'product',
			'meta_key'    => 'er_wix_slug', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $nom, // phpcs:ignore WordPress.DB.SlowDBQuery
			'numberposts' => 1,
			'fields'      => 'ids',
			'lang'        => '',
		)
	);
	return $ids ? (int) $ids[0] : er_bascule_livre( $nom, er_bascule_livres_de_l_index() );
}

/**
 * Où mène une ancienne adresse de Wix : l'adresse, et le code (301 pour la même chose, 302 pour une recherche), ou
 * rien.
 *
 * @param string $chemin Le chemin demandé, sans les barres du bout.
 * @return array{0: string, 1: int}|null
 */
function er_bascule_destination( $chemin ) {
	$chemin = preg_replace( '#^(?:ru|en)/#', '', $chemin );
	if ( preg_match( '#^product-page/([^/]+)#', $chemin, $m ) ) {
		$nom = urldecode( $m[1] );
		$id  = er_bascule_livre_du_site( $nom );
		if ( $id && 'publish' === get_post_status( $id ) ) {
			return array( get_permalink( $id ), 301 );
		}
		$mots = trim( (string) preg_replace( '/-\d+$/', '', str_replace( '-', ' ', $nom ) ) );
		return array( add_query_arg( array( 's' => rawurlencode( $mots ), 'post_type' => 'product' ), home_url( '/' ) ), 302 );
	}
	$pages = er_bascule_pages();
	if ( isset( $pages[ $chemin ] ) ) {
		$page = get_page_by_path( $pages[ $chemin ] );
		if ( $page ) {
			return array( get_permalink( $page ), 301 );
		}
	}
	return null;
}

add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;
		}
		$chemin = trim( (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), PHP_URL_PATH ), '/' );
		$aller  = er_bascule_destination( $chemin );
		if ( $aller ) {
			wp_safe_redirect( $aller[0], $aller[1] );
			exit;
		}
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * La bascule : wp er bascule livres [appliquer] | pages.
	 *
	 * « livres » lit le plan des livres du site de Wix et montre le livre que chaque adresse trouve ; avec
	 * « appliquer », le retient dans la fiche du livre (er_wix_slug). « pages » montre où mène chaque page de Wix.
	 *
	 * @param array $args livres [appliquer], ou pages.
	 */
	WP_CLI::add_command(
		'er bascule',
		function ( $args ) {
			$quoi = $args[0] ?? '';
			if ( 'pages' === $quoi ) {
				foreach ( er_bascule_pages() as $ancien => $nouveau ) {
					$page = get_page_by_path( $nouveau );
					WP_CLI::log( sprintf( '  /%s → %s', $ancien, $page ? wp_make_link_relative( get_permalink( $page ) ) : '(la page ' . $nouveau . ' manque)' ) );
				}
				return;
			}
			if ( 'livres' !== $quoi ) {
				WP_CLI::error( 'wp er bascule livres [appliquer] | pages' );
			}
			$appliquer = isset( $args[1] ) && 'appliquer' === $args[1];
			$reponse   = wp_remote_get( 'https://www.editeurs-reunis.fr/store-products-sitemap.xml', array( 'timeout' => 30 ) );
			if ( is_wp_error( $reponse ) || 200 !== wp_remote_retrieve_response_code( $reponse ) ) {
				WP_CLI::error( 'Le plan des livres de Wix ne se lit pas.' );
			}
			preg_match_all( '#/product-page/([^<\s]+)</loc>#', wp_remote_retrieve_body( $reponse ), $m );
			$livres  = er_bascule_livres_de_l_index();
			$trouves = 0;
			$manques = array();
			$par     = array();
			foreach ( array_unique( $m[1] ) as $code ) {
				$nom = urldecode( html_entity_decode( $code ) );
				$id  = er_bascule_livre( $nom, $livres );
				if ( ! $id ) {
					$manques[] = $nom;
					continue;
				}
				++$trouves;
				$par[ $id ][] = $nom;
				if ( $appliquer && ! get_post_meta( $id, 'er_wix_slug', true ) ) {
					update_post_meta( $id, 'er_wix_slug', $nom );
				}
			}
			WP_CLI::log( sprintf( '%d adresses de livres sur Wix : %d trouvent leur livre, %d mènent à la recherche.', count( array_unique( $m[1] ) ), $trouves, count( $manques ) ) );
			$doubles = array_filter( $par, fn( $noms ) => count( $noms ) > 1 );
			if ( $doubles ) {
				WP_CLI::log( sprintf( '%d livres reçoivent plusieurs adresses (des doublons de Wix) :', count( $doubles ) ) );
				foreach ( array_slice( $doubles, 0, 15, true ) as $id => $noms ) {
					WP_CLI::log( '  ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) . ' ← ' . implode( ', ', $noms ) );
				}
			}
			if ( $manques ) {
				WP_CLI::log( 'Sans livre (elles mènent à la recherche de la librairie) :' );
				foreach ( $manques as $nom ) {
					WP_CLI::log( '  ' . $nom );
				}
			}
			if ( ! $appliquer ) {
				WP_CLI::log( '« wp er bascule livres appliquer » retient ces adresses dans les fiches.' );
			}
		}
	);
}
