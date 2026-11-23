<?php
/**
 * Les recommandations de la fiche d'un livre : trois rangées, tirées des données du site (pas d'Algolia, pas du
 * comportement des visiteurs) :
 * - « Du même auteur » : les livres qui partagent un auteur ;
 * - « Autour de ce livre » : les livres qui parlent de son auteur (« Autour de »), ceux des personnes dont il parle,
 *   ceux qui partagent ses sujets ; et les articles, rencontres, vidéos qui portent ses auteurs ou ses sujets ;
 * - « Du même rayon, de la même époque » : le même genre (romans, poésie…) et la même époque (l'Âge d'argent, la
 *   période soviétique…), à défaut l'un ou l'autre.
 * Un livre ne paraît qu'une fois, dans la première rangée qui le prend ; les livres en stock d'abord.
 *
 * `wp er voisins <slug>` montre les trois rangées d'un livre (des titres et des prix, rien de privé).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les rayons qui disent l'époque, et ceux qui disent le genre (les slugs des rayons de la librairie).
 */
function er_reco_epoques() {
	return array( 'classiques', 'age-d-argent', 'periode-sovietique', 'emigration', 'contemporains' );
}

/**
 * Des livres publiés (hors billets des rencontres) qui répondent à une requête de classements, les livres en stock
 * d'abord, puis les plus récents.
 *
 * @param array $tax    La tax_query.
 * @param array $exclus Les livres à laisser de côté.
 * @param int   $n      Combien au plus.
 * @return int[]
 */
function er_reco_livres( $tax, $exclus, $n ) {
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 60,
			'fields'         => 'ids',
			'post__not_in'   => array_values( array_filter( array_map( 'intval', $exclus ) ) ),
			'tax_query'      => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_query'     => array( array( 'key' => 'er_evenement', 'compare' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'date',
			'order'          => 'DESC',
			'lang'           => function_exists( 'pll_current_language' ) && pll_current_language() ? pll_current_language() : '',
		)
	);
	usort(
		$ids,
		function ( $a, $b ) {
			return ( 'instock' !== get_post_meta( $a, '_stock_status', true ) ) <=> ( 'instock' !== get_post_meta( $b, '_stock_status', true ) );
		}
	);
	return array_slice( $ids, 0, $n );
}

/**
 * Les identifiants des termes d'un livre dans un classement.
 *
 * @param int    $id        Le livre.
 * @param string $taxonomie Le classement.
 * @return int[]
 */
function er_reco_termes( $id, $taxonomie ) {
	$ids = wp_get_post_terms( $id, $taxonomie, array( 'fields' => 'ids' ) );
	return is_wp_error( $ids ) ? array() : array_map( 'intval', $ids );
}

/**
 * Les trois rangées d'un livre.
 *
 * @param int $id Le livre.
 * @param int $n  Combien de livres par rangée.
 * @return array auteur, autour, rayon (des identifiants de livres) ; contenus (des articles, rencontres, vidéos).
 */
function er_recommandations( $id, $n = 8 ) {
	$deja    = array( $id );
	$auteurs = er_reco_termes( $id, 'auteur' );

	// Du même auteur.
	$auteur = $auteurs ? er_reco_livres( array( array( 'taxonomy' => 'auteur', 'terms' => $auteurs ) ), $deja, $n ) : array();
	$deja   = array_merge( $deja, $auteur );

	// Autour de ce livre : les livres sur ses auteurs (3), ceux des personnes dont il parle (2), ses sujets (2 chacun).
	$sujets  = er_reco_termes( $id, 'er_sujet' );
	$autour  = function_exists( 'er_autour_de' ) ? wp_list_pluck( er_autour_de( $id ), 'term_id' ) : array();
	$miroirs = array();
	foreach ( $auteurs as $a ) {
		$t = get_term( $a, 'auteur' );
		$m = $t && ! is_wp_error( $t ) && taxonomy_exists( 'er_autour' ) ? get_term_by( 'slug', $t->slug, 'er_autour' ) : null;
		if ( $m ) {
			$miroirs[] = (int) $m->term_id;
		}
	}
	$scores = array();
	foreach ( array(
		array( 'er_autour', $miroirs, 3 ),
		array( 'auteur', $autour, 2 ),
		array( 'er_sujet', $sujets, 2 ),
	) as list( $taxonomie, $termes, $poids ) ) {
		if ( ! $termes || ! taxonomy_exists( $taxonomie ) ) {
			continue;
		}
		foreach ( er_reco_livres( array( array( 'taxonomy' => $taxonomie, 'terms' => $termes ) ), $deja, 30 ) as $livre ) {
			$communs           = array_intersect( $termes, er_reco_termes( $livre, $taxonomie ) );
			$scores[ $livre ]  = ( isset( $scores[ $livre ] ) ? $scores[ $livre ] : 0 ) + $poids * max( 1, count( $communs ) );
		}
	}
	arsort( $scores );
	$autour_livres = array_slice( array_keys( $scores ), 0, $n );
	$deja          = array_merge( $deja, $autour_livres );

	// Les articles, rencontres, vidéos qui portent ses auteurs, ceux dont il parle, ou ses sujets.
	$tax = array( 'relation' => 'OR' );
	if ( $auteurs || $autour ) {
		$tax[] = array( 'taxonomy' => 'auteur', 'terms' => array_merge( $auteurs, $autour ) );
	}
	if ( $sujets ) {
		$tax[] = array( 'taxonomy' => 'er_sujet', 'terms' => $sujets );
	}
	$contenus = count( $tax ) > 1 ? get_posts(
		array(
			'post_type'      => array_values( array_filter( array( 'post', 'er_evenement', 'er_media' ), 'post_type_exists' ) ),
			'post_status'    => 'publish',
			'posts_per_page' => 4,
			'fields'         => 'ids',
			'tax_query'      => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
			'lang'           => function_exists( 'pll_current_language' ) && pll_current_language() ? pll_current_language() : '',
		)
	) : array();

	// Du même rayon, de la même époque : le genre et l'époque ensemble, à défaut l'époque, à défaut le rayon.
	$rayons   = er_reco_termes( $id, 'product_cat' );
	$epoques  = array();
	$genres   = array();
	foreach ( $rayons as $r ) {
		$t = get_term( $r, 'product_cat' );
		if ( $t && ! is_wp_error( $t ) ) {
			if ( in_array( $t->slug, er_reco_epoques(), true ) ) {
				$epoques[] = $r;
			} elseif ( 'sans-rayon' !== $t->slug ) {
				$genres[] = $r;
			}
		}
	}
	$essais = array();
	if ( $genres && $epoques ) {
		$essais[] = array(
			'relation' => 'AND',
			array( 'taxonomy' => 'product_cat', 'terms' => $genres, 'include_children' => false ),
			array( 'taxonomy' => 'product_cat', 'terms' => $epoques, 'include_children' => false ),
		);
	}
	if ( $epoques ) {
		$essais[] = array( array( 'taxonomy' => 'product_cat', 'terms' => $epoques, 'include_children' => false ) );
	}
	if ( $genres ) {
		$essais[] = array( array( 'taxonomy' => 'product_cat', 'terms' => $genres, 'include_children' => false ) );
	}
	$rayon = array();
	foreach ( $essais as $tax_rayon ) {
		$rayon = array_merge( $rayon, er_reco_livres( $tax_rayon, array_merge( $deja, $rayon ), $n - count( $rayon ) ) );
		if ( count( $rayon ) >= $n ) {
			break;
		}
	}

	return array(
		'auteur'   => $auteur,
		'autour'   => $autour_livres,
		'rayon'    => $rayon,
		'contenus' => $contenus,
		'epoques'  => $epoques,
		'genres'   => $genres,
	);
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Les trois rangées de recommandations d'un livre : wp er voisins <slug>.
	 *
	 * @param array $args Le slug du livre.
	 */
	WP_CLI::add_command(
		'er voisins',
		function ( $args ) {
			$livre = isset( $args[0] ) ? get_page_by_path( sanitize_title( $args[0] ), OBJECT, 'product' ) : null;
			if ( ! $livre ) {
				WP_CLI::error( 'Livre introuvable : ' . ( isset( $args[0] ) ? $args[0] : '' ) );
			}
			$ligne = function ( $pid ) {
				$p       = wc_get_product( $pid );
				$auteurs = wp_get_post_terms( $pid, 'auteur', array( 'fields' => 'names' ) );
				$mentions = wp_get_post_terms( $pid, 'product_tag', array( 'fields' => 'names' ) );
				$rayons  = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'names' ) );
				return '  ' . $p->get_name() . ' | ' . implode( ', ', is_wp_error( $auteurs ) ? array() : $auteurs ) . ' | '
					. ( '' !== $p->get_price() ? number_format_i18n( (float) $p->get_price(), 2 ) . ' €' : 'sans prix' ) . ' | '
					. ( $p->is_in_stock() ? 'en stock' : 'épuisé' ) . ' | couverture ' . ( function_exists( 'er_couverture_qualite' ) ? er_couverture_qualite( $p ) : '?' )
					. ( $mentions && ! is_wp_error( $mentions ) ? ' | ' . implode( ', ', $mentions ) : '' )
					. ' | ' . implode( ', ', is_wp_error( $rayons ) ? array() : $rayons ) . ' | ' . $p->get_slug();
			};
			WP_CLI::log( 'Le livre' );
			WP_CLI::log( $ligne( $livre->ID ) );
			$r = er_recommandations( $livre->ID );
			foreach ( array(
				'auteur' => 'Du même auteur',
				'autour' => 'Autour de ce livre',
				'rayon'  => 'Du même rayon, de la même époque',
			) as $cle => $titre ) {
				WP_CLI::log( $titre . ' (' . count( $r[ $cle ] ) . ')' );
				foreach ( $r[ $cle ] as $pid ) {
					WP_CLI::log( $ligne( $pid ) );
				}
			}
			WP_CLI::log( 'Au centre et au journal (' . count( $r['contenus'] ) . ')' );
			foreach ( $r['contenus'] as $cid ) {
				WP_CLI::log( '  ' . get_post_type( $cid ) . ' | ' . html_entity_decode( get_the_title( $cid ), ENT_QUOTES, 'UTF-8' ) . ' | ' . get_the_date( 'j F Y', $cid ) );
			}
			$noms = function ( $ids ) {
				return implode( ', ', array_map( fn( $t ) => get_term( $t, 'product_cat' )->name, $ids ) );
			};
			WP_CLI::log( 'Genre : ' . $noms( $r['genres'] ) . ' ; époque : ' . $noms( $r['epoques'] ) );
		}
	);
}
