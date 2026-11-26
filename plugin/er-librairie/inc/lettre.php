<?php
/**
 * L'inscription à la lettre (la « newsletter » du pied de page, inc/pied.php du thème).
 *
 * En attendant de choisir le service qui enverra les lettres (Brevo, Mailchimp…), les adresses sont gardées sur le
 * site, dans l'option er_lettre_inscrits (adresse, date, langue), et la liste se lit et s'exporte en CSV dans
 * Outils → Lettre : inscrits. Le formulaire envoie à admin-post.php ; un champ piège écarte les robots. Pas de jeton
 * (nonce) : les pages sont en cache, un jeton périmé ferait échouer des inscriptions honnêtes, et s'inscrire à une
 * lettre ne change rien d'autre.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reçoit une inscription, puis revient à la page d'où elle part (?lettre=merci ou ?lettre=erreur, ancre #er-lettre).
 */
function er_lettre_recevoir() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- voir plus haut : pas de jeton sur un formulaire public mis en cache.
	$retour = wp_get_referer();
	$retour = $retour ? remove_query_arg( 'lettre', $retour ) : home_url( '/' );
	$retour = strtok( $retour, '#' );

	// Le piège : un robot remplit tout ; on fait comme si de rien n'était.
	if ( ! empty( $_POST['site'] ) ) {
		wp_safe_redirect( add_query_arg( 'lettre', 'merci', $retour ) . '#er-lettre' );
		exit;
	}

	$courriel = isset( $_POST['courriel'] ) ? sanitize_email( wp_unslash( $_POST['courriel'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	if ( ! $courriel || ! is_email( $courriel ) ) {
		wp_safe_redirect( add_query_arg( 'lettre', 'erreur', $retour ) . '#er-lettre' );
		exit;
	}

	$inscrits = get_option( 'er_lettre_inscrits', array() );
	$inscrits = is_array( $inscrits ) ? $inscrits : array();
	$cle      = strtolower( $courriel );
	if ( ! isset( $inscrits[ $cle ] ) ) {
		$inscrits[ $cle ] = array(
			'courriel' => $courriel,
			'date'     => gmdate( 'c' ),
			'langue'   => substr( determine_locale(), 0, 2 ),
		);
		update_option( 'er_lettre_inscrits', $inscrits, false );
	}
	wp_safe_redirect( add_query_arg( 'lettre', 'merci', $retour ) . '#er-lettre' );
	exit;
}
add_action( 'admin_post_nopriv_er_lettre', 'er_lettre_recevoir' );
add_action( 'admin_post_er_lettre', 'er_lettre_recevoir' );

/**
 * Outils → Lettre : inscrits. La liste, et son export en CSV.
 */
add_action(
	'admin_menu',
	function () {
		add_management_page(
			__( 'Lettre : inscrits', 'er-librairie' ),
			__( 'Lettre : inscrits', 'er-librairie' ),
			'manage_options',
			'er-lettre',
			function () {
				$inscrits = get_option( 'er_lettre_inscrits', array() );
				$inscrits = is_array( $inscrits ) ? $inscrits : array();
				$export   = wp_nonce_url( admin_url( 'admin-post.php?action=er_lettre_export' ), 'er_lettre_export' );
				echo '<div class="wrap"><h1>' . esc_html__( 'Lettre : inscrits', 'er-librairie' ) . '</h1>';
				/* translators: %d: le nombre d'inscrits */
				echo '<p>' . esc_html( sprintf( _n( '%d inscrit.', '%d inscrits.', count( $inscrits ), 'er-librairie' ), count( $inscrits ) ) ) . ' <a class="button" href="' . esc_url( $export ) . '">' . esc_html__( 'Exporter en CSV', 'er-librairie' ) . '</a></p>';
				echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Adresse', 'er-librairie' ) . '</th><th>' . esc_html__( 'Date', 'er-librairie' ) . '</th><th>' . esc_html__( 'Langue', 'er-librairie' ) . '</th></tr></thead><tbody>';
				foreach ( array_reverse( $inscrits ) as $i ) {
					echo '<tr><td>' . esc_html( $i['courriel'] ) . '</td><td>' . esc_html( wp_date( 'j M Y, H:i', strtotime( $i['date'] ) ) ) . '</td><td>' . esc_html( $i['langue'] ) . '</td></tr>';
				}
				echo '</tbody></table></div>';
			}
		);
	}
);

add_action(
	'admin_post_er_lettre_export',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'er-librairie' ) );
		}
		check_admin_referer( 'er_lettre_export' );
		$inscrits = get_option( 'er_lettre_inscrits', array() );
		$inscrits = is_array( $inscrits ) ? $inscrits : array();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="lettre-inscrits-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$sortie = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- la sortie du téléchargement.
		fputcsv( $sortie, array( 'courriel', 'date', 'langue' ) );
		foreach ( $inscrits as $i ) {
			fputcsv( $sortie, array( $i['courriel'], $i['date'], $i['langue'] ) );
		}
		fclose( $sortie ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
);
