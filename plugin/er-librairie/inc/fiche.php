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
 * Les lignes de la fiche, dans l'ordre : libellé → valeur, en HTML (déjà échappé). La traduction, la direction et
 * l'éditeur sont des liens vers leur page quand le livre les porte (traducteurs-editeurs.php), sinon le texte repris
 * de Wix.
 *
 * @param WC_Product $produit Le livre.
 */
function er_fiche_champs( $produit ) {
	$pages = (int) $produit->get_meta( 'er_pages' );
	$id    = $produit->get_id();
	$liens = function ( $termes ) {
		$html = array();
		foreach ( $termes as $t ) {
			$lien   = get_term_link( $t );
			$html[] = '<a href="' . esc_url( is_wp_error( $lien ) ? '' : $lien ) . '">' . esc_html( $t->name ) . '</a>';
		}
		return implode( ', ', $html );
	};
	$traduction = function_exists( 'er_personnes_du_livre' ) ? er_personnes_du_livre( $id, 'er_traduction' ) : array();
	$direction  = function_exists( 'er_personnes_du_livre' ) ? er_personnes_du_livre( $id, 'er_direction' ) : array();
	$editeur    = function_exists( 'er_editeur_du_livre' ) ? er_editeur_du_livre( $id ) : array( 'termes' => array(), 'texte' => (string) $produit->get_meta( 'er_editeur' ) );
	$texte      = function ( $valeur ) {
		return esc_html( (string) $valeur );
	};
	return array(
		_x( 'Traduction', 'fiche d’un livre', 'er-librairie' )           => $traduction ? $liens( $traduction ) : $texte( $produit->get_meta( 'er_traducteur' ) ),
		_x( 'Sous la direction de', 'fiche d’un livre', 'er-librairie' ) => $liens( $direction ),
		_x( 'Éditeur', 'fiche d’un livre', 'er-librairie' )              => $editeur['termes'] ? $liens( $editeur['termes'] ) : $texte( $editeur['texte'] ),
		_x( 'Collection', 'fiche d’un livre', 'er-librairie' )           => $texte( $produit->get_meta( 'er_collection' ) ),
		_x( 'Parution', 'fiche d’un livre', 'er-librairie' )             => $texte( $produit->get_meta( 'er_annee' ) ),
		_x( 'Pages', 'fiche d’un livre', 'er-librairie' )                => $pages ? $texte( number_format_i18n( $pages ) ) : '',
		_x( 'Format', 'fiche d’un livre', 'er-librairie' )               => $texte( $produit->get_meta( 'er_format' ) ),
		_x( 'ISBN', 'fiche d’un livre', 'er-librairie' )                 => $texte( er_isbn( $produit ) ),
	);
}

/**
 * L'ISBN d'un livre : le champ « GTIN, UPC, EAN ou ISBN » de WooCommerce, sinon la méta `er_isbn`. WooCommerce
 * refuse qu'un même GTIN serve à deux produits ; quand la librairie a deux fiches pour un même livre (neuf et
 * d'occasion), la seconde le garde dans `er_isbn` (outils/catalogue/isbn.py).
 *
 * @param WC_Product $produit Le livre.
 */
function er_isbn( $produit ) {
	$isbn = method_exists( $produit, 'get_global_unique_id' ) ? (string) $produit->get_global_unique_id() : '';
	return '' !== $isbn ? $isbn : (string) $produit->get_meta( 'er_isbn' );
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
						$lignes .= '<p class="er-fiche-champ"><span class="er-fiche-champ__libelle">' . esc_html( $libelle ) . '</span>' . wp_kses_post( $valeur ) . '</p>';
					}
					// Les personnes dont parle le livre, et ses sujets : des liens vers leur page.
					$liees = array(
						_x( 'Autour de', 'fiche d’un livre', 'er-librairie' ) => function_exists( 'er_autour_de' ) ? er_autour_de( $id ) : array(),
						_x( 'Sujets', 'fiche d’un livre', 'er-librairie' )    => wp_get_post_terms( $id, 'er_sujet' ),
					);
					foreach ( $liees as $libelle => $termes ) {
						if ( ! $termes || is_wp_error( $termes ) ) {
							continue;
						}
						$liens = array();
						foreach ( $termes as $t ) {
							$lien    = get_term_link( $t );
							$liens[] = '<a href="' . esc_url( is_wp_error( $lien ) ? '' : $lien ) . '">' . esc_html( $t->name ) . '</a>';
						}
						$lignes .= '<p class="er-fiche-champ"><span class="er-fiche-champ__libelle">' . esc_html( $libelle ) . '</span>' . implode( ', ', $liens ) . '</p>';
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
