<?php
/**
 * Les collections (24 nov. 2026, l'audit des recoupements : 42 collections reprises de Wix, dont dix d'au moins trois
 * livres, n'étaient qu'un mot sur la fiche).
 *
 * Une collection est un classement public des livres, er_collection, avec sa page, /collection/<slug>/, comme les
 * éditeurs : la fiche d'un livre y mène (« Collection : Le Messager orthodoxe »). La méta er_collection, reprise de
 * Wix, ne sert plus que de repli, tant que la collection n'est pas posée.
 *
 * wp er collections [appliquer] : relève les collections du catalogue, réunit leurs variantes (« Folio bilingue »,
 * « Folio-Bilingue », « FolioBilingue » ; avec ou sans guillemets) sous le nom le plus fréquent, et les pose avec
 * « appliquer ».
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_taxonomy(
			'er_collection',
			array( 'product' ),
			array(
				'labels'            => array(
					'name'                       => __( 'Collections', 'er-librairie' ),
					'singular_name'              => __( 'Collection', 'er-librairie' ),
					'search_items'               => __( 'Chercher une collection', 'er-librairie' ),
					'all_items'                  => __( 'Toutes les collections', 'er-librairie' ),
					'edit_item'                  => __( 'Modifier la collection', 'er-librairie' ),
					'view_item'                  => __( 'Voir la collection', 'er-librairie' ),
					'add_new_item'               => __( 'Ajouter une collection', 'er-librairie' ),
					'separate_items_with_commas' => __( 'Séparer les collections par des virgules', 'er-librairie' ),
					'choose_from_most_used'      => __( 'Choisir parmi les plus utilisées', 'er-librairie' ),
					'not_found'                  => __( 'Aucune collection', 'er-librairie' ),
					'menu_name'                  => __( 'Collections', 'er-librairie' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'collection' ),
			)
		);
	},
	11
);

/**
 * La collection d'un livre : ses termes, et la méta de Wix en repli.
 *
 * @param int $id Le livre.
 * @return array{termes: WP_Term[], texte: string}
 */
function er_collection_du_livre( $id ) {
	$termes = taxonomy_exists( 'er_collection' ) ? wp_get_post_terms( $id, 'er_collection' ) : array();
	return array(
		'termes' => is_wp_error( $termes ) ? array() : $termes,
		'texte'  => (string) get_post_meta( $id, 'er_collection', true ),
	);
}

/**
 * Le nom d'une collection tel que Wix l'a gardé, sans guillemets ni blancs en trop.
 *
 * @param string $nom Le nom.
 */
function er_collection_propre( $nom ) {
	$nom = trim( wp_strip_all_tags( html_entity_decode( (string) $nom, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	$nom = trim( $nom, " \t\n\r\0\x0B\"'«»“”„" );
	return trim( preg_replace( '/\s+/u', ' ', $nom ) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Relève les collections du catalogue et les pose avec « appliquer » : wp er collections [appliquer].
	 *
	 * @param array $args « appliquer », ou rien pour seulement montrer.
	 */
	WP_CLI::add_command(
		'er collections',
		function ( $args ) {
			$appliquer = isset( $args[0] ) && 'appliquer' === $args[0];
			$livres    = array();
			$comptes   = array();
			foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'lang' => '' ) ) as $id ) {
				$nom = er_collection_propre( get_post_meta( $id, 'er_collection', true ) );
				if ( '' === $nom ) {
					continue;
				}
				$livres[ $id ]   = $nom;
				$comptes[ $nom ] = ( isset( $comptes[ $nom ] ) ? $comptes[ $nom ] : 0 ) + 1;
			}
			$retenus = er_editeurs_regroupes( $comptes ); // Les variantes, réunies comme celles des éditeurs.
			$par     = array();
			foreach ( $retenus as $nom => $chef ) {
				$par[ $chef ][ $nom ] = $comptes[ $nom ];
			}
			uasort( $par, fn( $a, $b ) => array_sum( $b ) <=> array_sum( $a ) );
			foreach ( $par as $chef => $variantes ) {
				$autres = array_diff( array_keys( $variantes ), array( $chef ) );
				WP_CLI::log( '  ' . $chef . ' (' . array_sum( $variantes ) . ')' . ( $autres ? ' — réunit aussi : ' . implode( ' / ', $autres ) : '' ) );
			}
			if ( $appliquer ) {
				foreach ( $livres as $id => $nom ) {
					wp_set_object_terms( $id, $retenus[ $nom ], 'er_collection', false );
				}
			}
			WP_CLI::success( sprintf( $appliquer ? '%d collections posées sur %d livres.' : '%d collections pour %d livres. Avec « appliquer », elles sont posées.', count( $par ), count( $livres ) ) );
		}
	);
}
