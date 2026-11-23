<?php
/**
 * La fiche d'un livre : les renseignements que l'extension garde (traduction, éditeur, collection, parution, pages,
 * format, ISBN), en lignes comme le rayon et la langue. Un bloc dynamique, « er/fiche-champs », que la composition
 * fiche-livre du thème place sous l'auteur ; une ligne vide ne paraît pas.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les lignes de la fiche, dans l'ordre : libellé → valeur.
 *
 * @param WC_Product $produit Le livre.
 */
function er_fiche_champs( $produit ) {
	$pages = (int) $produit->get_meta( 'er_pages' );
	return array(
		_x( 'Traduction', 'fiche d’un livre', 'er-librairie' ) => $produit->get_meta( 'er_traducteur' ),
		_x( 'Éditeur', 'fiche d’un livre', 'er-librairie' )    => $produit->get_meta( 'er_editeur' ),
		_x( 'Collection', 'fiche d’un livre', 'er-librairie' ) => $produit->get_meta( 'er_collection' ),
		_x( 'Parution', 'fiche d’un livre', 'er-librairie' )   => $produit->get_meta( 'er_annee' ),
		_x( 'Pages', 'fiche d’un livre', 'er-librairie' )      => $pages ? number_format_i18n( $pages ) : '',
		_x( 'Format', 'fiche d’un livre', 'er-librairie' )     => $produit->get_meta( 'er_format' ),
		_x( 'ISBN', 'fiche d’un livre', 'er-librairie' )       => method_exists( $produit, 'get_global_unique_id' ) ? $produit->get_global_unique_id() : '',
	);
}

add_action(
	'init',
	function () {
		register_block_type(
			'er/fiche-champs',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					$id      = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : get_the_ID();
					$produit = $id ? wc_get_product( $id ) : null;
					if ( ! $produit ) {
						return '';
					}
					$lignes = '';
					foreach ( er_fiche_champs( $produit ) as $libelle => $valeur ) {
						if ( '' === trim( (string) $valeur ) ) {
							continue;
						}
						$lignes .= '<p class="er-fiche-champ"><span class="er-fiche-champ__libelle">' . esc_html( $libelle ) . '</span>' . esc_html( $valeur ) . '</p>';
					}
					return $lignes ? '<div class="wp-block-er-fiche-champs">' . $lignes . '</div>' : '';
				},
			)
		);
	}
);

/**
 * Les corrections faites une fois dans le contenu déjà en place (les pages semées avant la correction du texte).
 */
add_action(
	'init',
	function () {
		$corrections = array(
			1 => array( 'Taganstsev', 'Tagantsev' ),
		);
		$faite = (int) get_option( 'er_corrections', 0 );
		if ( $faite >= max( array_keys( $corrections ) ) ) {
			return;
		}
		global $wpdb;
		foreach ( $corrections as $numero => $paire ) {
			if ( $numero <= $faite ) {
				continue;
			}
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s", $paire[0], $paire[1], '%' . $wpdb->esc_like( $paire[0] ) . '%' ) ); // phpcs:ignore WordPress.DB
			$faite = $numero;
		}
		update_option( 'er_corrections', $faite );
	},
	20
);
