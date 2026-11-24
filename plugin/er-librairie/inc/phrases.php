<?php
/**
 * Les phrases des suggestions (24 nov. 2026) : sous la couverture d'un livre que la fiche d'un autre livre propose
 * (la bande « Autour de ce livre », l'étagère du rayon), une phrase au ton du libraire dit ce qu'il est (« Le roman de
 * Grossman sur Stalingrad, la guerre et les camps. »). Une phrase par livre, la même partout où il est proposé : la
 * méta er_phrase, posée par « wp er phrases poser appliquer » (er-bascule) et corrigeable ici.
 *
 * Écrites automatiquement d'après les notices, elles sont annoncées comme relues par l'équipe : la page Produits >
 * Phrases des suggestions les montre toutes, avec leur livre, pour les relire et les corriger d'un coup, et compte
 * celles qui sont marquées « relue ».
 *
 * En français seulement : sur les pages russes et anglaises, ni phrase ni mention.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * La mention qui accompagne les phrases.
 */
function er_phrases_mention() {
	return __( 'Suggestion automatique d’après les notices de la librairie et relue par l’équipe.', 'er-librairie' );
}

/**
 * La phrase d'un livre, si la page est en français.
 *
 * @param int $pid Le livre.
 */
function er_reco_phrase( $pid ) {
	if ( function_exists( 'er_langue' ) && 'fr' !== er_langue() ) {
		return '';
	}
	return trim( (string) get_post_meta( $pid, 'er_phrase', true ) );
}

/**
 * La mention sous une rangée, si l'un de ses livres a sa phrase.
 *
 * @param int[] $pids Les livres de la rangée.
 */
function er_reco_mention_phrases( $pids ) {
	foreach ( $pids as $pid ) {
		if ( '' !== er_reco_phrase( (int) $pid ) ) {
			return '<p class="er-reco-note-phrases">' . esc_html( er_phrases_mention() ) . '</p>';
		}
	}
	return '';
}

/* La relecture : Produits > Phrases des suggestions ------------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Les phrases des suggestions', 'er-librairie' ),
			__( 'Phrases des suggestions', 'er-librairie' ),
			'edit_products',
			'er-phrases',
			'er_phrases_page'
		);
	}
);

/**
 * Enregistre les phrases et les marques « relue » envoyées par la page.
 *
 * @return int Le nombre de livres changés.
 */
function er_phrases_enregistrer() {
	// phpcs:disable WordPress.Security.NonceVerification -- vérifié par er_phrases_page().
	$phrases = isset( $_POST['er_phrase'] ) && is_array( $_POST['er_phrase'] ) ? wp_unslash( $_POST['er_phrase'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$relues  = isset( $_POST['er_relue'] ) && is_array( $_POST['er_relue'] ) ? array_map( 'intval', wp_unslash( $_POST['er_relue'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	// phpcs:enable
	$changes = 0;
	foreach ( $phrases as $id => $texte ) {
		$id = (int) $id;
		if ( ! $id || 'product' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		$texte  = sanitize_text_field( (string) $texte );
		$avant  = trim( (string) get_post_meta( $id, 'er_phrase', true ) );
		$relue  = in_array( $id, $relues, true );
		$change = false;
		if ( $texte !== $avant ) {
			if ( '' === $texte ) {
				delete_post_meta( $id, 'er_phrase' );
			} else {
				update_post_meta( $id, 'er_phrase', $texte );
			}
			$change = true;
		}
		if ( $relue !== (bool) get_post_meta( $id, 'er_phrase_relue', true ) ) {
			if ( $relue ) {
				update_post_meta( $id, 'er_phrase_relue', '1' );
			} else {
				delete_post_meta( $id, 'er_phrase_relue' );
			}
			$change = true;
		}
		$changes += $change ? 1 : 0;
	}
	return $changes;
}

/**
 * Le nombre de livres qui ont une phrase, et de phrases relues.
 */
function er_phrases_compte() {
	global $wpdb;
	$avec   = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = 'er_phrase' AND meta_value <> ''" ); // phpcs:ignore WordPress.DB
	$relues = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT m.post_id) FROM {$wpdb->postmeta} m JOIN {$wpdb->postmeta} r ON r.post_id = m.post_id AND r.meta_key = 'er_phrase_relue' WHERE m.meta_key = 'er_phrase' AND m.meta_value <> ''" ); // phpcs:ignore WordPress.DB
	return array( $avec, $relues );
}

/**
 * La page de relecture : les livres par cent, leur phrase dans un champ, une case « relue ».
 */
function er_phrases_page() {
	if ( ! current_user_can( 'edit_products' ) ) {
		return;
	}
	$message = '';
	if ( isset( $_POST['er_phrases_envoi'] ) ) {
		check_admin_referer( 'er_phrases' );
		$n = er_phrases_enregistrer();
		/* translators: %d: le nombre de livres */
		$message = sprintf( _n( '%d livre mis à jour.', '%d livres mis à jour.', $n, 'er-librairie' ), $n );
	}
	$filtres = array(
		'a-relire' => __( 'À relire', 'er-librairie' ),
		'relues'   => __( 'Relues', 'er-librairie' ),
		'sans'     => __( 'Sans phrase', 'er-librairie' ),
		'toutes'   => __( 'Tous les livres', 'er-librairie' ),
	);
	$filtre  = isset( $_GET['filtre'] ) ? sanitize_key( wp_unslash( $_GET['filtre'] ) ) : 'a-relire'; // phpcs:ignore WordPress.Security.NonceVerification
	$filtre  = isset( $filtres[ $filtre ] ) ? $filtre : 'a-relire';
	$page    = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 ); // phpcs:ignore WordPress.Security.NonceVerification
	$metas   = array(
		'a-relire' => array(
			array(
				'key'     => 'er_phrase',
				'value'   => '',
				'compare' => '!=',
			),
			array(
				'key'     => 'er_phrase_relue',
				'compare' => 'NOT EXISTS',
			),
		),
		'relues'   => array(
			array(
				'key'     => 'er_phrase',
				'value'   => '',
				'compare' => '!=',
			),
			array(
				'key'     => 'er_phrase_relue',
				'compare' => 'EXISTS',
			),
		),
		'sans'     => array(
			'relation' => 'OR',
			array(
				'key'     => 'er_phrase',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'   => 'er_phrase',
				'value' => '',
			),
		),
		'toutes'   => array(),
	);
	$requete = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'posts_per_page' => 100,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'lang'           => '',
			'meta_query'     => $metas[ $filtre ], // phpcs:ignore WordPress.DB.SlowDBQuery
			'no_found_rows'  => false,
		)
	);
	list( $avec, $relues ) = er_phrases_compte();
	$adresse               = admin_url( 'edit.php?post_type=product&page=er-phrases' );

	echo '<div class="wrap"><h1>' . esc_html__( 'Les phrases des suggestions', 'er-librairie' ) . '</h1>';
	echo '<p style="max-width: 46rem">' . esc_html__( 'Une phrase par livre, sous sa couverture quand la fiche d’un autre livre le propose. Elles ont été écrites automatiquement d’après les notices de la librairie, et le site annonce qu’elles sont relues par l’équipe : corrigez ce qui ne va pas, cochez « relue », enregistrez. Une phrase vidée n’est plus montrée.', 'er-librairie' ) . '</p>';
	/* translators: 1: le nombre de phrases relues, 2: le nombre de livres qui ont une phrase */
	echo '<p><strong>' . esc_html( sprintf( __( '%1$d phrases relues sur %2$d.', 'er-librairie' ), $relues, $avec ) ) . '</strong></p>';
	if ( $message ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
	echo '<ul class="subsubsub">';
	$liens = array();
	foreach ( $filtres as $cle => $nom ) {
		$liens[] = '<li><a href="' . esc_url( add_query_arg( 'filtre', $cle, $adresse ) ) . '"' . ( $cle === $filtre ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $nom ) . '</a></li>';
	}
	echo implode( ' | ', $liens ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<form method="post" style="clear: both">';
	wp_nonce_field( 'er_phrases' );
	echo '<table class="widefat striped" style="margin-top: 1rem"><thead><tr><th style="width: 32%">' . esc_html__( 'Livre', 'er-librairie' ) . '</th><th>' . esc_html__( 'Phrase', 'er-librairie' ) . '</th><th style="width: 5rem">' . esc_html__( 'Relue', 'er-librairie' ) . '</th></tr></thead><tbody>';
	if ( ! $requete->posts ) {
		echo '<tr><td colspan="3">' . esc_html__( 'Aucun livre ici.', 'er-librairie' ) . '</td></tr>';
	}
	foreach ( $requete->posts as $livre ) {
		$auteurs = wp_get_post_terms( $livre->ID, 'auteur', array( 'fields' => 'names' ) );
		$phrase  = (string) get_post_meta( $livre->ID, 'er_phrase', true );
		echo '<tr><td><a href="' . esc_url( (string) get_edit_post_link( $livre->ID ) ) . '"' . ( preg_match( '/\p{Cyrillic}/u', $livre->post_title ) ? ' lang="ru"' : '' ) . '><strong>' . esc_html( $livre->post_title ) . '</strong></a>'
			. ( $auteurs && ! is_wp_error( $auteurs ) ? '<br><span style="color: #646970">' . esc_html( implode( ', ', $auteurs ) ) . '</span>' : '' ) . '</td>'
			. '<td><input type="text" class="large-text" name="er_phrase[' . (int) $livre->ID . ']" value="' . esc_attr( $phrase ) . '" maxlength="220" aria-label="' . esc_attr( $livre->post_title ) . '"></td>'
			. '<td><input type="checkbox" name="er_relue[]" value="' . (int) $livre->ID . '"' . ( get_post_meta( $livre->ID, 'er_phrase_relue', true ) ? ' checked' : '' ) . ' aria-label="' . esc_attr__( 'Relue', 'er-librairie' ) . '"></td></tr>';
	}
	echo '</tbody></table>';
	submit_button( __( 'Enregistrer', 'er-librairie' ), 'primary', 'er_phrases_envoi' );
	echo '</form>';
	$pages = (int) $requete->max_num_pages;
	if ( $pages > 1 ) {
		echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'base'    => add_query_arg( 'paged', '%#%', add_query_arg( 'filtre', $filtre, $adresse ) ),
				'format'  => '',
				'current' => $page,
				'total'   => $pages,
			)
		) . '</div></div>';
	}
	echo '</div>';
}
