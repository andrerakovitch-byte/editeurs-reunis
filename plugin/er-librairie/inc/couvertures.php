<?php
/**
 * Les couvertures des livres : leur qualité, d'après la taille de l'image d'origine, et le relevé qui la montre
 * (Produits → Couvertures, et `wp er couvertures`).
 *
 * - bonne : au moins 800 px de haut, nette en grand sur la fiche, écran Retina compris ;
 * - passable : de 450 à 800 px, nette dans les listes, montrée à sa taille sur la fiche, jamais agrandie ;
 * - trop petite : moins de 450 px, remplacée à l'affichage par la couverture typographique du thème ;
 * - absente.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

const ER_COUVERTURE_BONNE  = 800;
const ER_COUVERTURE_MINIMA = 450;

/**
 * La taille de l'image d'origine : array( largeur, hauteur ), ou null.
 *
 * @param int $image L'identifiant de l'image.
 */
function er_couverture_taille( $image ) {
	$meta = $image ? wp_get_attachment_metadata( $image ) : false;
	if ( ! is_array( $meta ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
		return null;
	}
	return array( (int) $meta['width'], (int) $meta['height'] );
}

/**
 * La qualité de la couverture d'un livre : bonne, passable, petite ou absente.
 *
 * @param WC_Product|int $produit Le livre.
 */
function er_couverture_qualite( $produit ) {
	$produit = is_numeric( $produit ) ? wc_get_product( $produit ) : $produit;
	$image   = $produit ? (int) $produit->get_image_id() : 0;
	if ( ! $image || ! get_post( $image ) ) {
		return 'absente';
	}
	$taille = er_couverture_taille( $image );
	if ( ! $taille ) {
		return 'passable'; // Une image sans dimensions connues (un SVG, une métadonnée perdue) : on la montre.
	}
	if ( $taille[1] >= ER_COUVERTURE_BONNE ) {
		return 'bonne';
	}
	return $taille[1] >= ER_COUVERTURE_MINIMA ? 'passable' : 'petite';
}

/**
 * Les qualités, dans l'ordre du relevé.
 */
function er_couverture_qualites() {
	return array(
		'petite'   => __( 'Trop petites', 'er-librairie' ),
		'absente'  => __( 'Absentes', 'er-librairie' ),
		'passable' => __( 'Passables', 'er-librairie' ),
		'bonne'    => __( 'Bonnes', 'er-librairie' ),
	);
}

/**
 * Tous les livres, rangés par qualité de couverture (les livres en stock d'abord, puis par titre).
 */
function er_couvertures_releve() {
	$ids    = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'meta_query'     => array( array( 'key' => 'er_evenement', 'compare' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery -- les billets des rencontres n'ont pas de couverture.
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$releve = array_fill_keys( array_keys( er_couverture_qualites() ), array() );
	foreach ( $ids as $id ) {
		$produit = wc_get_product( $id );
		if ( ! $produit ) {
			continue;
		}
		$releve[ er_couverture_qualite( $produit ) ][] = $produit;
	}
	foreach ( $releve as $qualite => $livres ) {
		usort(
			$releve[ $qualite ],
			function ( $a, $b ) {
				return ( 'instock' !== $a->get_stock_status() ) <=> ( 'instock' !== $b->get_stock_status() );
			}
		);
	}
	return $releve;
}

/* Produits → Couvertures ------------------------------------------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Les couvertures', 'er-librairie' ),
			__( 'Couvertures', 'er-librairie' ),
			'edit_products',
			'er-couvertures',
			'er_couvertures_page'
		);
	}
);

/**
 * La page du relevé : les comptes, puis les livres d'une qualité, à remplacer en priorité.
 */
function er_couvertures_page() {
	$releve   = er_couvertures_releve();
	$qualites = er_couverture_qualites();
	$choisie  = isset( $_GET['qualite'] ) ? sanitize_key( wp_unslash( $_GET['qualite'] ) ) : 'petite'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! isset( $qualites[ $choisie ] ) ) {
		$choisie = 'petite';
	}
	echo '<div class="wrap"><h1>' . esc_html__( 'Les couvertures', 'er-librairie' ) . '</h1>';
	echo '<p style="max-width: 46rem">' . esc_html__( 'Une couverture est bonne à partir de 800 pixels de haut : nette même en grand sur la fiche. Passable, de 450 à 800 pixels : nette dans les listes, montrée à sa taille sur la fiche. En dessous de 450 pixels, le site la remplace par une couverture typographique (le titre et l’auteur), jusqu’à ce qu’une meilleure image arrive. Les livres en stock viennent en tête.', 'er-librairie' ) . '</p>';
	echo '<ul class="subsubsub">';
	$liens = array();
	foreach ( $qualites as $cle => $nom ) {
		$url     = add_query_arg( array( 'post_type' => 'product', 'page' => 'er-couvertures', 'qualite' => $cle ), admin_url( 'edit.php' ) );
		$liens[] = '<li><a href="' . esc_url( $url ) . '"' . ( $cle === $choisie ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $nom ) . ' <span class="count">(' . count( $releve[ $cle ] ) . ')</span></a>';
	}
	echo implode( ' | </li>', $liens ) . '</li></ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<table class="widefat striped" style="clear: both; margin-top: 1rem"><thead><tr><th style="width: 56px"></th><th>' . esc_html__( 'Livre', 'er-librairie' ) . '</th><th>' . esc_html__( 'Taille de l’image', 'er-librairie' ) . '</th><th>' . esc_html__( 'Stock', 'er-librairie' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $releve[ $choisie ] as $produit ) {
		$image   = (int) $produit->get_image_id();
		$taille  = er_couverture_taille( $image );
		$auteurs = wp_get_post_terms( $produit->get_id(), 'auteur', array( 'fields' => 'names' ) );
		echo '<tr><td>' . ( $image ? wp_get_attachment_image( $image, array( 48, 72 ) ) : '' ) . '</td>';
		echo '<td><strong>' . esc_html( $produit->get_name() ) . '</strong>' . ( is_array( $auteurs ) && $auteurs ? '<br>' . esc_html( implode( ', ', $auteurs ) ) : '' ) . '</td>';
		echo '<td>' . ( $taille ? esc_html( $taille[0] . ' × ' . $taille[1] . ' px' ) : '—' ) . '</td>';
		echo '<td>' . esc_html( 'instock' === $produit->get_stock_status() ? __( 'En stock', 'er-librairie' ) : __( 'Épuisé', 'er-librairie' ) ) . '</td>';
		echo '<td><a href="' . esc_url( (string) get_edit_post_link( $produit->get_id() ) ) . '">' . esc_html__( 'Changer la couverture', 'er-librairie' ) . '</a></td></tr>';
	}
	if ( ! $releve[ $choisie ] ) {
		echo '<tr><td colspan="5">' . esc_html__( 'Aucun livre.', 'er-librairie' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/* wp er couvertures ------------------------------------------------------------------------------------------------ */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Le relevé des couvertures : le nombre de livres par qualité ; avec --liste=<qualité>, les livres eux-mêmes.
	 *
	 * @param array $args  Rien.
	 * @param array $assoc --liste=petite|absente|passable|bonne.
	 */
	WP_CLI::add_command(
		'er couvertures',
		function ( $args, $assoc ) {
			$releve = er_couvertures_releve();
			foreach ( er_couverture_qualites() as $cle => $nom ) {
				$en_stock = count( array_filter( $releve[ $cle ], function ( $p ) { return 'instock' === $p->get_stock_status(); } ) );
				WP_CLI::log( sprintf( '%-14s %4d livres, dont %d en stock', $nom, count( $releve[ $cle ] ), $en_stock ) );
			}
			if ( ! empty( $assoc['liste'] ) && isset( $releve[ $assoc['liste'] ] ) ) {
				foreach ( $releve[ $assoc['liste'] ] as $produit ) {
					$taille = er_couverture_taille( (int) $produit->get_image_id() );
					WP_CLI::log( $produit->get_sku() . "\t" . ( $taille ? $taille[0] . '×' . $taille[1] : '—' ) . "\t" . $produit->get_name() );
				}
			}
		}
	);
}
