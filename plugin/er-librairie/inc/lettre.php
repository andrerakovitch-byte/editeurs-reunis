<?php
/**
 * L'inscription à la lettre (la « newsletter » du pied de page, inc/pied.php du thème).
 *
 * En attendant de choisir le service qui enverra les lettres (Brevo, Mailchimp…), les adresses sont gardées sur le
 * site, dans l'option er_lettre_inscrits (adresse, date, langue), et la liste se lit et s'exporte en CSV dans
 * Outils → Lettre : inscrits. Le formulaire envoie à admin-post.php ; un champ piège écarte les robots. Pas de jeton
 * (nonce) : les pages sont en cache, un jeton périmé ferait échouer des inscriptions honnêtes.
 *
 * Audit du 27 nov. 2026 : cinq inscriptions par heure et par adresse IP, une liste plafonnée, un verrou contre deux
 * inscriptions simultanées, la langue de la page (un champ caché : admin-post.php ne connaît que celle du site), un
 * export CSV qui ne laisse passer aucune formule de tableur. Reste à faire avec le service d'envoi : la double
 * confirmation (seule preuve que la personne a consenti) et la désinscription.
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
	$langue   = isset( $_POST['langue'] ) ? sanitize_key( wp_unslash( $_POST['langue'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	if ( ! $courriel || ! is_email( $courriel ) ) {
		wp_safe_redirect( add_query_arg( 'lettre', 'erreur', $retour ) . '#er-lettre' );
		exit;
	}

	// Un robot qui omet le champ piège : au-delà de cinq inscriptions par heure depuis la même adresse, on remercie
	// sans rien garder.
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$garde = 'er_lettre_ip_' . md5( $ip );
	$fois  = (int) get_transient( $garde );
	set_transient( $garde, $fois + 1, HOUR_IN_SECONDS );
	if ( $fois < 5 ) {
		er_lettre_garder( $courriel, $langue );
	}
	wp_safe_redirect( add_query_arg( 'lettre', 'merci', $retour ) . '#er-lettre' );
	exit;
}

/**
 * Ajoute une adresse à la liste, sous un verrou (deux inscriptions simultanées ne s'effacent pas), dans la limite du
 * plafond (une liste qui gonfle au-delà tient du robot : on le note dans le journal).
 *
 * @param string $courriel L'adresse, déjà vérifiée.
 * @param string $langue   La langue de la page (fr, ru, en).
 */
function er_lettre_garder( $courriel, $langue ) {
	$langues = function_exists( 'er_langues_du_site' ) ? array_keys( (array) er_langues_du_site() ) : array( 'fr', 'ru', 'en' );
	$langue  = in_array( $langue, $langues, true ) ? $langue : substr( determine_locale(), 0, 2 );
	for ( $essai = 0; $essai < 20 && ! add_option( 'er_lettre_verrou', time(), '', false ); ++$essai ) {
		if ( (int) get_option( 'er_lettre_verrou' ) < time() - 10 ) {
			delete_option( 'er_lettre_verrou' ); // Un verrou resté d'une requête morte.
		}
		usleep( 50000 );
	}
	$inscrits = get_option( 'er_lettre_inscrits', array() );
	$inscrits = is_array( $inscrits ) ? $inscrits : array();
	$cle      = strtolower( $courriel );
	if ( ! isset( $inscrits[ $cle ] ) ) {
		if ( count( $inscrits ) >= (int) apply_filters( 'er_lettre_plafond', 20000 ) ) {
			error_log( 'er-librairie : la liste de la lettre a atteint son plafond ; inscription refusée.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		} else {
			$inscrits[ $cle ] = array(
				'courriel' => $courriel,
				'date'     => gmdate( 'c' ),
				'langue'   => $langue,
			);
			update_option( 'er_lettre_inscrits', $inscrits, false );
		}
	}
	delete_option( 'er_lettre_verrou' );
}

/**
 * Une cellule de CSV qu'un tableur ne prend pas pour une formule (« =1+1@exemple.fr »).
 *
 * @param string $valeur La valeur.
 */
function er_lettre_cellule( $valeur ) {
	$valeur = (string) $valeur;
	return preg_match( '/^[=+\-@\t\r]/', $valeur ) ? "'" . $valeur : $valeur;
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
			fputcsv( $sortie, array_map( 'er_lettre_cellule', array( $i['courriel'], $i['date'], $i['langue'] ) ) );
		}
		fclose( $sortie ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
);
