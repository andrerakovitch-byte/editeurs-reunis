<?php
/**
 * Les recommandations de la fiche d'un livre, tirées des données du site (pas d'Algolia, pas du comportement des
 * visiteurs) : « Du même auteur », dans la colonne de la fiche (le bloc er/fiche-auteur). Sous la fiche, « Pour aller
 * plus loin » (continuer.php, le bloc er/fiche-continuer) a remplacé, le 25 nov. 2026, la bande « Autour de ce
 * livre » et l'étagère « Du même rayon, de la même époque » : la colonne ne redit pas les livres qu'il montre.
 *
 * Restent ici les outils que d'autres pages prennent : les livres d'un classement, en stock d'abord
 * (er_reco_livres), les contenus qui citent un livre (er_reco_citants), la couverture, le prix, l'initiale.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

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
 * Les articles, rencontres et vidéos qui citent un livre dans leurs « Livres liés » (er_livres : des références ou des
 * identifiants, séparés par des virgules).
 *
 * @param int $id Le livre.
 * @return int[]
 */
function er_reco_citants( $id ) {
	$refs = array_values( array_filter( array( trim( (string) get_post_meta( $id, '_sku', true ) ), (string) $id ) ) );
	$ou   = array( 'relation' => 'OR' );
	foreach ( $refs as $ref ) {
		$ou[] = array(
			'key'     => 'er_livres',
			'value'   => $ref,
			'compare' => 'LIKE',
		);
	}
	$trouves = get_posts(
		array(
			'post_type'   => array_values( array_filter( array( 'post', 'er_evenement', 'er_media' ), 'post_type_exists' ) ),
			'post_status' => 'publish',
			'numberposts' => 20,
			'fields'      => 'ids',
			'meta_query'  => $ou, // phpcs:ignore WordPress.DB.SlowDBQuery
			'lang'        => function_exists( 'pll_current_language' ) && pll_current_language() ? pll_current_language() : '',
		)
	);
	// « LIKE » trouve aussi 123 dans 1234 : on garde ceux dont la liste porte vraiment la référence.
	return array_values(
		array_filter(
			array_map( 'intval', $trouves ),
			function ( $c ) use ( $refs ) {
				return (bool) array_intersect( $refs, array_map( 'trim', explode( ',', (string) get_post_meta( $c, 'er_livres', true ) ) ) );
			}
		)
	);
}

/**
 * Les livres du même auteur : ceux qui partagent un auteur avec le livre, en stock d'abord.
 *
 * @param int $id Le livre.
 * @param int $n  Combien au plus.
 * @return array auteur (des identifiants de livres).
 */
function er_recommandations( $id, $n = 8 ) {
	$auteurs = er_reco_termes( $id, 'auteur' );
	return array(
		'auteur' => $auteurs ? er_reco_livres( array( array( 'taxonomy' => 'auteur', 'terms' => $auteurs ) ), array( $id ), $n ) : array(),
	);
}

/* L'affichage sur la fiche --------------------------------------------------------------------------------------- */

/**
 * Les livres du même auteur, calculés une fois par page.
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
 * Un livre en carte : la couverture, la mention, le titre, l'auteur, sa phrase (les cartes de la bande et de
 * l'étagère, pas les courtes de la colonne), le prix.
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
	$phrase   = false !== strpos( $classe, 'er-reco-livre' ) && function_exists( 'er_reco_phrase' ) ? er_reco_phrase( $pid ) : '';
	return '<li class="' . esc_attr( $classe ) . '"><a href="' . esc_url( get_permalink( $pid ) ) . '">'
		. '<span class="er-reco-couverture">' . er_reco_couverture( $p ) . '</span>' . $mention
		. '<span class="er-reco-titre"' . $russe . '>' . esc_html( wptexturize( $p->get_name() ) ) . '</span>'
		. ( $auteurs && ! is_wp_error( $auteurs ) ? '<span class="er-reco-auteur">' . esc_html( implode( ', ', $auteurs ) ) . '</span>' : '' )
		. ( '' !== $phrase ? '<span class="er-reco-phrase">' . esc_html( $phrase ) . '</span>' : '' )
		. er_reco_prix( $p ) . '</a></li>';
}

/**
 * L'initiale du nom de famille d'un auteur, toujours en cyrillique (П pour Pasternak, Б pour Brodsky).
 *
 * @param WP_Term $auteur L'auteur.
 */
function er_reco_initiale( $auteur ) {
	return er_initiale_cyrillique( $auteur->name, function_exists( 'er_autres_graphies' ) ? er_autres_graphies( $auteur->term_id ) : array() );
}

/**
 * Le bloc « Du même auteur », dans la colonne de la fiche.
 *
 * @param int $id Le livre.
 */
function er_reco_bloc_auteur( $id ) {
	$r       = er_reco_de( $id );
	$auteurs = wp_get_post_terms( $id, 'auteur' );
	$montres = function_exists( 'er_continuer_livres_montres' ) ? er_continuer_livres_montres( $id ) : array();
	if ( ! array_diff( $r['auteur'], $montres ) || ! $auteurs || is_wp_error( $auteurs ) ) {
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
	// Les livres que « Pour aller plus loin » montre déjà (l'autre volume, le livre que cite la notice) n'y sont pas.
	foreach ( array_slice( array_values( array_diff( $r['auteur'], $montres ) ), 0, 4 ) as $pid ) {
		$livres .= er_reco_carte( $pid, 'er-reco-court' );
	}
	return '<section class="er-reco-auteur-bloc" aria-labelledby="er-reco-auteur-titre">'
		. '<div class="er-reco-auteur-tete"><span class="er-reco-initiale" aria-hidden="true">' . $vignette . '</span>'
		. '<div><h2 id="er-reco-auteur-titre" class="er-petit-titre">' . esc_html__( 'Du même auteur', 'er-librairie' ) . '</h2>'
		. '<p class="er-reco-auteur-nom">' . esc_html( $a->name ) . ( $autre ? ' <span lang="' . ( preg_match( '/\p{Cyrillic}/u', $autre ) ? 'ru' : 'fr' ) . '">· ' . esc_html( $autre ) . '</span>' : '' ) . ( $dates ? '<span class="er-reco-dates">' . esc_html( $dates ) . '</span>' : '' ) . '</p></div>'
		. ( is_wp_error( $lien ) ? '' : '<p class="er-lien-fleche"><a href="' . esc_url( $lien ) . '">' . esc_html__( 'Sa page', 'er-librairie' ) . '</a></p>' ) . '</div>'
		. '<ul class="er-reco-courts">' . $livres . '</ul></section>';
}

add_action(
	'init',
	function () {
		register_block_type(
			'er/fiche-auteur',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					unset( $attributs, $contenu );
					$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : get_the_ID();
					return $id && 'product' === get_post_type( $id ) && function_exists( 'wc_get_product' ) ? er_reco_bloc_auteur( $id ) : '';
				},
			)
		);
		// La bande « Autour de ce livre » et l'étagère, retirées le 25 nov. 2026 : leurs blocs restent connus, vides,
		// pour qu'un modèle enregistré qui les porterait encore n'affiche rien plutôt qu'une erreur.
		foreach ( array( 'er/fiche-autour', 'er/fiche-rayon' ) as $nom ) {
			register_block_type(
				$nom,
				array(
					'api_version'     => 3,
					'render_callback' => '__return_empty_string',
				)
			);
		}
	}
);
