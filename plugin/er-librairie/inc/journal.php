<?php
/**
 * Le journal du centre : la signature d'un article (« Par Yves Hamant »), les anciennes adresses du blog de Wix, et
 * l'import de ses articles (`wp er journal importer`, le fichier que fait outils/wix/journal.py).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		foreach ( array( 'er_livres', 'er_mots' ) as $cle ) {
			register_post_meta(
				'post',
				$cle,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
		register_block_type(
			'er/livres-lies',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID();
					return er_bloc_livres_article( $id );
				},
			)
		);
		register_post_meta(
			'post',
			'er_signature',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_block_type(
			'er/signature',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID();
					return er_bloc_signature( $id );
				},
			)
		);
	}
);

/**
 * « Par Yves Hamant · 29 septembre 2020 » : la signature mène à la page de l'auteur, s'il en a une.
 *
 * @param int $id L'article.
 */
function er_bloc_signature( $id ) {
	$signature = trim( (string) get_post_meta( $id, 'er_signature', true ) );
	$date      = get_the_date( __( 'j F Y', 'er-librairie' ), $id );
	if ( '' === $signature ) {
		return '<p class="er-article-date">' . esc_html( $date ) . '</p>';
	}
	$auteur = function_exists( 'er_personne_trouver' ) ? er_personne_trouver( $signature ) : get_term_by( 'name', $signature, 'auteur' );
	$lien   = $auteur ? get_term_link( $auteur ) : '';
	$nom    = $lien && ! is_wp_error( $lien ) ? '<a href="' . esc_url( $lien ) . '">' . esc_html( $signature ) . '</a>' : esc_html( $signature );
	/* translators: %s: le nom de celui qui signe l'article */
	return '<p class="er-article-date er-signature">' . sprintf( esc_html__( 'Par %s', 'er-librairie' ), $nom ) . ' · ' . esc_html( $date ) . '</p>';
}

/**
 * « À la librairie », sous un article : les livres dont il parle (er_livres, leurs références), puis ceux des personnes
 * qu'il concerne (en stock, pas ceux de celui qui signe), six au plus.
 *
 * @param int $id L'article.
 */
function er_bloc_livres_article( $id ) {
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! function_exists( 'er_carte_livre' ) ) {
		return '';
	}
	$ids = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'er_livres', true ) ) ) ) as $ref ) {
		$trouve = ctype_digit( $ref ) ? (int) $ref : wc_get_product_id_by_sku( $ref );
		if ( $trouve ) {
			$ids[] = $trouve;
		}
	}
	// Celui qui signe, sous son nom ou sous une autre graphie (« Irina Roskina », la page « Ирина Роскина »).
	$signature = trim( (string) get_post_meta( $id, 'er_signature', true ) );
	$signe     = '' !== $signature && function_exists( 'er_personne_trouver' ) ? er_personne_trouver( $signature ) : null;
	$personnes = array();
	foreach ( wp_get_post_terms( $id, 'auteur' ) as $t ) {
		if ( $t->name !== $signature && ! ( $signe && (int) $signe->term_id === (int) $t->term_id ) ) {
			$personnes[] = $t->term_id;
		}
	}
	if ( $personnes && count( $ids ) < 6 ) {
		$ids = array_merge(
			$ids,
			get_posts(
				array(
					'post_type'   => 'product',
					'numberposts' => 6,
					'fields'      => 'ids',
					'tax_query'   => array( array( 'taxonomy' => 'auteur', 'terms' => $personnes ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_query'  => array( array( 'key' => '_stock_status', 'value' => 'instock' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			)
		);
	}
	$cartes = implode( '', array_map( 'er_carte_livre', array_slice( array_unique( $ids ), 0, 6 ) ) );
	return $cartes ? '<section class="er-evt-livres er-article-livres"><h2 class="er-evt-section">' . esc_html__( 'À la librairie', 'er-librairie' ) . '</h2><ul>' . $cartes . '</ul></section>' : '';
}

// Les anciennes adresses du blog de Wix (/post/<slug>) mènent à l'article, une fois la bascule faite.
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;
		}
		$chemin = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! preg_match( '#^(?:[a-z]{2}/)?post/([^/]+)#', $chemin, $m ) ) {
			return;
		}
		$articles = get_posts(
			array(
				'post_type'   => 'post',
				'meta_key'    => 'er_wix_slug', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'  => urldecode( $m[1] ), // phpcs:ignore WordPress.DB.SlowDBQuery
				'numberposts' => 1,
				'lang'        => '',
			)
		);
		if ( $articles ) {
			wp_safe_redirect( get_permalink( $articles[0] ), 301 );
			exit;
		}
	}
);
