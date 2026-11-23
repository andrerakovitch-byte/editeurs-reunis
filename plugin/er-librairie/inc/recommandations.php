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
			// Le rendu des trois blocs, tel que la fiche le montrera : de quoi voir qu'il ne casse pas.
			$rendus = array();
			foreach ( array( 'auteur' => 'er_reco_bloc_auteur', 'autour' => 'er_reco_bloc_autour', 'rayon' => 'er_reco_bloc_rayon' ) as $cle => $f ) {
				$html            = $f( $livre->ID );
				$rendus[]        = $cle . ' ' . ( $html ? substr_count( $html, '<li' ) . ' cartes' : 'rien' );
			}
			WP_CLI::log( 'Rendu sur la fiche : ' . implode( ' ; ', $rendus ) );
		}
	);
}

/* L'affichage sur la fiche (la piste B, retenue le 23 nov. 2026) ------------------------------------------------
 *
 * Trois blocs, que la composition fiche-livre du thème place :
 * - er/fiche-auteur, dans la colonne de la fiche, sous les renseignements : l'auteur (son portrait, sinon l'initiale
 *   de son nom en cyrillique sur le vert de la librairie), son autre graphie, ses dates, quatre de ses livres ;
 * - er/fiche-autour, une bande pleine largeur sur la pervenche pâle : les personnes et les sujets en pilules, la page
 *   de l'encyclopédie, les livres autour, une rencontre (sur le bleu nuit du centre), un article ou une vidéo ;
 * - er/fiche-rayon, l'étagère : les livres du même rayon et de la même époque, debout sur un filet noir.
 * Un bloc sans rien à montrer ne paraît pas.
 */

/**
 * Les rangées d'un livre, calculées une fois par page.
 *
 * @param int $id Le livre.
 */
function er_reco_de( $id ) {
	static $cache = array();
	if ( ! isset( $cache[ $id ] ) ) {
		$cache[ $id ] = er_recommandations( $id, 8 );
	}
	return $cache[ $id ];
}

/**
 * La couverture d'un livre : sa photo, ou la couverture typographique du thème quand la photo manque ou est trop
 * petite.
 *
 * @param WC_Product $p Le livre.
 */
function er_reco_couverture( $p ) {
	if ( function_exists( 'er_couverture_a_remplacer' ) && function_exists( 'er_couverture_typo' ) && er_couverture_a_remplacer( $p ) ) {
		return er_couverture_typo( $p );
	}
	return $p->get_image( 'woocommerce_thumbnail', array( 'alt' => '' ) );
}

/**
 * Le prix d'un livre en texte, ou « épuisé pour l'instant ».
 *
 * @param WC_Product $p Le livre.
 */
function er_reco_prix( $p ) {
	$prix = '' !== $p->get_price() ? html_entity_decode( wp_strip_all_tags( wc_price( wc_get_price_to_display( $p ) ) ), ENT_QUOTES, 'UTF-8' ) : '';
	if ( $p->is_in_stock() ) {
		return '<span class="er-reco-prix">' . esc_html( $prix ) . '</span>';
	}
	return '<span class="er-reco-epuise">' . esc_html( trim( $prix . ( $prix ? ' · ' : '' ) . __( 'épuisé pour l’instant', 'er-librairie' ) ) ) . '</span>';
}

/**
 * Un livre en carte : la couverture, la mention, le titre, l'auteur, le prix.
 *
 * @param int    $pid    Le livre.
 * @param string $classe La classe de la carte.
 */
function er_reco_carte( $pid, $classe = 'er-reco-livre' ) {
	$p = wc_get_product( $pid );
	if ( ! $p ) {
		return '';
	}
	$auteurs  = wp_get_post_terms( $pid, 'auteur', array( 'fields' => 'names' ) );
	$mentions = wp_get_post_terms( $pid, 'product_tag' );
	$mention  = $mentions && ! is_wp_error( $mentions ) ? '<span class="er-reco-mention' . ( 'tres-rare' === $mentions[0]->slug ? ' er-reco-mention--rare' : '' ) . '">' . esc_html( $mentions[0]->name ) . '</span>' : '';
	$russe    = preg_match( '/\p{Cyrillic}/u', $p->get_name() ) ? ' lang="ru"' : '';
	return '<li class="' . esc_attr( $classe ) . '"><a href="' . esc_url( get_permalink( $pid ) ) . '">'
		. '<span class="er-reco-couverture">' . er_reco_couverture( $p ) . '</span>' . $mention
		. '<span class="er-reco-titre"' . $russe . '>' . esc_html( wptexturize( $p->get_name() ) ) . '</span>'
		. ( $auteurs && ! is_wp_error( $auteurs ) ? '<span class="er-reco-auteur">' . esc_html( implode( ', ', $auteurs ) ) . '</span>' : '' )
		. er_reco_prix( $p ) . '</a></li>';
}

/**
 * L'initiale du nom de famille d'un auteur, en cyrillique quand il a un nom russe (П pour Pasternak).
 *
 * @param WP_Term $auteur L'auteur.
 */
function er_reco_initiale( $auteur ) {
	$noms = array_merge( array( $auteur->name ), function_exists( 'er_autres_graphies' ) ? er_autres_graphies( $auteur->term_id ) : array() );
	$cyr  = array_values( array_filter( $noms, fn( $n ) => preg_match( '/\p{Cyrillic}/u', $n ) ) );
	$nom  = trim( $cyr ? $cyr[0] : $auteur->name );
	return mb_substr( (string) preg_replace( '/^.*\s/u', '', $nom ), 0, 1 );
}

/**
 * Le bloc « Du même auteur », dans la colonne de la fiche.
 *
 * @param int $id Le livre.
 */
function er_reco_bloc_auteur( $id ) {
	$r       = er_reco_de( $id );
	$auteurs = wp_get_post_terms( $id, 'auteur' );
	if ( ! $r['auteur'] || ! $auteurs || is_wp_error( $auteurs ) ) {
		return '';
	}
	$a        = $auteurs[0];
	$autre    = '';
	foreach ( function_exists( 'er_autres_graphies' ) ? er_autres_graphies( $a->term_id ) : array() as $g ) {
		if ( (bool) preg_match( '/\p{Cyrillic}/u', $g ) !== (bool) preg_match( '/\p{Cyrillic}/u', $a->name ) ) {
			$autre = $g; // L'autre écriture : le cyrillique d'un nom latin, ou l'inverse.
			break;
		}
	}
	preg_match_all( '/\b(1[5-9]\d\d|20\d\d)\b/', get_term_meta( $a->term_id, 'er_naissance', true ) . ' | ' . get_term_meta( $a->term_id, 'er_deces', true ), $annees );
	$dates    = $annees[1] ? implode( ' – ', array_slice( $annees[1], 0, 2 ) ) : '';
	$portrait = (int) get_term_meta( $a->term_id, 'er_portrait', true );
	$vignette = $portrait ? wp_get_attachment_image( $portrait, 'thumbnail', false, array( 'alt' => '' ) ) : '<span lang="ru">' . esc_html( er_reco_initiale( $a ) ) . '</span>';
	$lien     = get_term_link( $a );
	$livres   = '';
	foreach ( array_slice( $r['auteur'], 0, 4 ) as $pid ) {
		$livres .= er_reco_carte( $pid, 'er-reco-court' );
	}
	return '<section class="er-reco-auteur-bloc" aria-labelledby="er-reco-auteur-titre">'
		. '<div class="er-reco-auteur-tete"><span class="er-reco-initiale" aria-hidden="true">' . $vignette . '</span>'
		. '<div><h2 id="er-reco-auteur-titre" class="er-petit-titre">' . esc_html__( 'Du même auteur', 'er-librairie' ) . '</h2>'
		. '<p class="er-reco-auteur-nom">' . esc_html( $a->name ) . ( $autre ? ' <span lang="' . ( preg_match( '/\p{Cyrillic}/u', $autre ) ? 'ru' : 'fr' ) . '">· ' . esc_html( $autre ) . '</span>' : '' ) . ( $dates ? '<span class="er-reco-dates">' . esc_html( $dates ) . '</span>' : '' ) . '</p></div>'
		. ( is_wp_error( $lien ) ? '' : '<p class="er-lien-fleche"><a href="' . esc_url( $lien ) . '">' . esc_html__( 'Sa page', 'er-librairie' ) . '</a></p>' ) . '</div>'
		. '<ul class="er-reco-courts">' . $livres . '</ul></section>';
}

/**
 * Un article, une rencontre ou une vidéo, en carte dans la bande « Autour de ce livre ».
 *
 * @param int $cid Le contenu.
 */
function er_reco_contenu( $cid ) {
	$type  = get_post_type( $cid );
	$titre = html_entity_decode( get_the_title( $cid ), ENT_QUOTES, 'UTF-8' );
	$point = '<span class="er-reco-point" aria-hidden="true"></span>';
	if ( 'er_evenement' === $type ) {
		$passee = function_exists( 'er_evt_passee' ) && er_evt_passee( $cid );
		$quand  = function_exists( 'er_evt_quand' ) ? er_evt_quand( $cid, false ) : get_the_date( 'j F Y', $cid );
		$suite  = $passee ? ( get_post_meta( $cid, 'er_videos', true ) ? __( 'Revoir la rencontre', 'er-librairie' ) : __( 'La rencontre', 'er-librairie' ) ) : __( 'Venir à la rencontre', 'er-librairie' );
		return '<li class="er-reco-rencontre"><a href="' . esc_url( get_permalink( $cid ) ) . '"><span class="er-reco-genre">' . $point . esc_html__( 'Rencontre au centre', 'er-librairie' ) . '</span>'
			. '<span class="er-reco-contenu-titre">' . esc_html( $titre ) . '</span><span class="er-reco-meta">' . esc_html( trim( $quand ) ) . ' · ' . esc_html( $suite ) . ' →</span></a></li>';
	}
	if ( 'er_media' === $type ) {
		$genre = 'podcast' === get_post_meta( $cid, 'er_genre', true ) ? __( 'Podcast', 'er-librairie' ) : __( 'Vidéo', 'er-librairie' );
		$lettre = '▶';
	} else {
		$genre = __( 'Le journal', 'er-librairie' );
		// La lettrine : l'initiale cyrillique de la première personne dont parle l'article, sinon celle du titre.
		$gens   = wp_get_post_terms( $cid, 'auteur' );
		$lettre = $gens && ! is_wp_error( $gens ) ? er_reco_initiale( $gens[0] ) : mb_substr( trim( $titre, ' «»"“”' ), 0, 1 );
	}
	return '<li class="er-reco-article"><a href="' . esc_url( get_permalink( $cid ) ) . '"><span class="er-reco-lettrine" lang="ru" aria-hidden="true">' . esc_html( $lettre ) . '</span>'
		. '<span class="er-reco-article-texte"><span class="er-reco-genre">' . $point . esc_html( $genre ) . '</span><span class="er-reco-contenu-titre">' . esc_html( $titre ) . '</span>'
		. '<span class="er-reco-meta">' . esc_html( get_the_date( 'j F Y', $cid ) ) . '</span></span></a></li>';
}

/**
 * La bande « Autour de ce livre ».
 *
 * @param int $id Le livre.
 */
function er_reco_bloc_autour( $id ) {
	$r = er_reco_de( $id );
	if ( ! $r['autour'] && ! $r['contenus'] ) {
		return '';
	}
	// Les pilules : les auteurs, les personnes dont parle le livre, ses sujets.
	$termes = array_merge(
		(array) wp_get_post_terms( $id, 'auteur' ),
		function_exists( 'er_autour_de' ) ? er_autour_de( $id ) : array(),
		taxonomy_exists( 'er_sujet' ) ? (array) wp_get_post_terms( $id, 'er_sujet' ) : array()
	);
	$pilules = '';
	$vus     = array();
	foreach ( $termes as $t ) {
		if ( ! $t instanceof WP_Term || isset( $vus[ $t->term_taxonomy_id ] ) ) {
			continue;
		}
		$vus[ $t->term_taxonomy_id ] = true;
		$lien                         = get_term_link( $t );
		$pilules                     .= is_wp_error( $lien ) ? '' : '<a class="er-reco-pilule" href="' . esc_url( $lien ) . '">' . esc_html( $t->name ) . '</a>';
	}
	// La page de l'encyclopédie du premier auteur, s'il en a une.
	$encyclo = '';
	$auteurs = wp_get_post_terms( $id, 'auteur' );
	if ( $auteurs && ! is_wp_error( $auteurs ) ) {
		$page = (int) get_term_meta( $auteurs[0]->term_id, 'er_page', true );
		if ( $page && 'publish' === get_post_status( $page ) ) {
			$nom     = preg_replace( '/^.*\s/u', '', trim( $auteurs[0]->name ) );
			/* translators: %s: le nom de famille de l'auteur */
			$encyclo = '<p class="er-lien-fleche"><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( sprintf( __( '%s, dans l’encyclopédie', 'er-librairie' ), $nom ) ) . '</a></p>';
		}
	}
	$livres = '';
	foreach ( array_slice( $r['autour'], 0, 4 ) as $pid ) {
		$livres .= er_reco_carte( $pid );
	}
	$contenus = '';
	foreach ( array_slice( $r['contenus'], 0, 2 ) as $cid ) {
		$contenus .= er_reco_contenu( $cid );
	}
	return '<section class="er-reco-autour alignfull" aria-labelledby="er-reco-autour-titre"><div class="er-reco-autour-g">'
		. '<div class="er-reco-autour-tete"><p class="er-petit-titre">' . esc_html__( 'Autour de ce livre', 'er-librairie' ) . '</p>'
		. '<h2 id="er-reco-autour-titre">' . esc_html__( 'Les personnes et les sujets qu’il touche', 'er-librairie' ) . '</h2>'
		. ( $pilules ? '<div class="er-reco-pilules">' . $pilules . '</div>' : '' ) . $encyclo . '</div>'
		. '<div class="er-reco-autour-corps' . ( $livres ? '' : ' er-reco-autour-corps--sans-livres' ) . '">'
		. ( $livres ? '<ul class="er-reco-livres">' . $livres . '</ul>' : '' )
		. ( $contenus ? '<ul class="er-reco-contenus">' . $contenus . '</ul>' : '' )
		. '</div></div></section>';
}

/**
 * Le titre de l'étagère : « Romans de la période soviétique », « Poésie de l'Âge d'argent » ; en russe et en anglais,
 * les deux noms côte à côte.
 *
 * @param int[] $genres  Les rayons du genre.
 * @param int[] $epoques Les rayons de l'époque.
 */
function er_reco_titre_rayon( $genres, $epoques ) {
	$genre  = $genres ? get_term( $genres[0], 'product_cat' ) : null;
	$epoque = $epoques ? get_term( $epoques[0], 'product_cat' ) : null;
	$genre  = $genre && ! is_wp_error( $genre ) ? $genre->name : '';
	$epoque = $epoque && ! is_wp_error( $epoque ) ? $epoque->name : '';
	if ( ! $genre || ! $epoque ) {
		return $genre . $epoque;
	}
	if ( function_exists( 'er_langue' ) && 'fr' !== er_langue() ) {
		return $genre . ' · ' . $epoque;
	}
	// Le français : « de la », « du », « des », « de l' ».
	$de = preg_replace( array( '/^La /u', '/^Le /u', '/^Les /u', '/^L[’\']/u' ), array( 'de la ', 'du ', 'des ', 'de l’' ), $epoque, 1, $fait );
	return $genre . ' ' . ( $fait ? $de : 'de ' . $epoque );
}

/**
 * L'étagère « Du même rayon, de la même époque ».
 *
 * @param int $id Le livre.
 */
function er_reco_bloc_rayon( $id ) {
	$r = er_reco_de( $id );
	if ( ! $r['rayon'] ) {
		return '';
	}
	$cible = $r['epoques'] ? $r['epoques'][0] : ( $r['genres'] ? $r['genres'][0] : 0 );
	$lien  = $cible ? get_term_link( (int) $cible, 'product_cat' ) : '';
	$titre = er_reco_titre_rayon( $r['genres'], $r['epoques'] );
	$livres = '';
	foreach ( array_slice( $r['rayon'], 0, 6 ) as $pid ) {
		$livres .= er_reco_carte( $pid, 'er-reco-livre er-reco-debout' );
	}
	return '<section class="er-reco-etagere alignwide" aria-labelledby="er-reco-etagere-titre"><div class="er-reco-etagere-tete"><div>'
		. '<p class="er-petit-titre">' . esc_html__( 'Du même rayon, de la même époque', 'er-librairie' ) . '</p>'
		. '<h2 id="er-reco-etagere-titre">' . esc_html( $titre ? $titre : __( 'Du même rayon', 'er-librairie' ) ) . '</h2></div>'
		. ( $lien && ! is_wp_error( $lien ) ? '<p class="er-lien-fleche"><a href="' . esc_url( $lien ) . '">' . esc_html__( 'Tout le rayon', 'er-librairie' ) . '</a></p>' : '' ) . '</div>'
		. '<ul class="er-reco-debouts">' . $livres . '</ul></section>';
}

add_action(
	'init',
	function () {
		foreach ( array(
			'er/fiche-auteur' => 'er_reco_bloc_auteur',
			'er/fiche-autour' => 'er_reco_bloc_autour',
			'er/fiche-rayon'  => 'er_reco_bloc_rayon',
		) as $nom => $rendu ) {
			register_block_type(
				$nom,
				array(
					'api_version'     => 3,
					'uses_context'    => array( 'postId' ),
					'render_callback' => function ( $attributs, $contenu, $bloc ) use ( $rendu ) {
						$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : get_the_ID();
						return $id && 'product' === get_post_type( $id ) && function_exists( 'wc_get_product' ) ? $rendu( $id ) : '';
					},
				)
			);
		}
	}
);
