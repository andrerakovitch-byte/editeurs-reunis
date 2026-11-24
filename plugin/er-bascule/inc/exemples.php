<?php
/**
 * Les pages d'exemple de la bêta (donnees/semer.php) : leurs images sont dans cette extension depuis le 24 nov. 2026.
 * wp er exemples : les pages posées avant gardent l'adresse de l'extension de la librairie ; elles prennent celle-ci.
 *
 * @package er-bascule
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'er exemples',
		function () {
			global $wpdb;
			$avant = '/plugins/er-librairie/donnees/exemples/';
			$apres = '/plugins/er-bascule/donnees/exemples/';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- une fois, en ligne de commande.
			$n = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s ) WHERE post_type = 'page' AND post_content LIKE %s", $avant, $apres, '%' . $wpdb->esc_like( $avant ) . '%' ) );
			clean_post_cache( 0 );
			wp_cache_flush();
			WP_CLI::success( sprintf( '%d pages d’exemple prennent les images de l’extension de la bascule.', (int) $n ) );
		}
	);
}
