<?php
/**
 * Les pages à feuilleter d'un livre : quelques pages tirées de son PDF d'impression (outils/extraits.py), rangées dans
 * la médiathèque et notées dans la méta « er_extraits » ([ { id, legende, page } ]). Le bloc er/fiche-feuilleter les
 * montre sous la couverture de la fiche ; « wp er extraits importer <dossier> » les pose.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les pages à feuilleter d'un livre, dans l'ordre : celles dont l'image existe encore.
 *
 * @param int $id Le livre.
 * @return array [ { id, legende, page } ].
 */
function er_extraits( $id ) {
	$pages  = get_post_meta( (int) $id, 'er_extraits', true );
	$sortie = array();
	foreach ( is_array( $pages ) ? $pages : array() as $p ) {
		$image = (int) ( $p['id'] ?? 0 );
		if ( $image && wp_attachment_is_image( $image ) ) {
			$sortie[] = array(
				'id'      => $image,
				'legende' => (string) ( $p['legende'] ?? '' ),
				'page'    => (int) ( $p['page'] ?? 0 ),
			);
		}
	}
	return $sortie;
}

/**
 * Le bouton « Feuilleter » sous la couverture et la fenêtre qui montre les pages, une à la fois (on glisse, ou les
 * flèches, ou le clavier ; un clic sur la page l'agrandit). Rien quand le livre n'a pas de pages.
 *
 * @param int $id Le livre.
 * @return string Le HTML.
 */
function er_feuilleter_html( $id ) {
	$pages = er_extraits( $id );
	if ( ! $pages ) {
		return '';
	}
	$titre = wp_strip_all_tags( get_the_title( $id ) );
	$n     = count( $pages );
	$cible = 'er-feuilleter-' . (int) $id;

	$vignettes = '';
	foreach ( array_slice( $pages, 0, 3 ) as $p ) {
		$vignettes .= wp_get_attachment_image(
			$p['id'],
			'medium',
			false,
			array(
				'class'   => 'er-feuilleter__vignette',
				'alt'     => '',
				'loading' => 'lazy',
			)
		);
	}

	$liste = '';
	$suite = '';
	foreach ( $pages as $p ) {
		// Une légende vaut pour les pages qui la suivent sans en avoir (le sommaire sur deux pages).
		$suite  = '' !== $p['legende'] ? $p['legende'] : $suite;
		$alt    = $titre . ' — ' . ( '' !== $suite ? $suite : __( 'extrait', 'er-librairie' ) );
		$liste .= '<li class="er-feuilleter__page" data-legende="' . esc_attr( $suite ) . '">'
			. '<figure>' . wp_get_attachment_image(
				$p['id'],
				'full',
				false,
				array(
					'class'    => 'er-feuilleter__image',
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 900px) 100vw, 70vw',
				)
			)
			. '</figure></li>';
	}

	/* translators: %d: le nombre de pages à feuilleter. */
	$compte = sprintf( _n( '%d page', '%d pages', $n, 'er-librairie' ), $n );
	return '<div class="er-feuilleter">'
		. '<button type="button" class="er-feuilleter__ouvrir" aria-haspopup="dialog" aria-controls="' . esc_attr( $cible ) . '">'
		. '<span class="er-feuilleter__vignettes" aria-hidden="true">' . $vignettes . '</span>'
		. '<span class="er-feuilleter__libelle">' . esc_html__( 'Feuilleter', 'er-librairie' ) . ' <span class="er-feuilleter__nombre">' . esc_html( $compte ) . '</span></span>'
		. '</button>'
		/* translators: %s: le titre du livre. */
		. '<dialog class="er-feuilleter__fenetre" id="' . esc_attr( $cible ) . '" aria-label="' . esc_attr( sprintf( __( 'Feuilleter « %s »', 'er-librairie' ), $titre ) ) . '">'
		. '<div class="er-feuilleter__haut">'
		. '<p class="er-feuilleter__titre">' . esc_html( $titre ) . '</p>'
		. '<p class="er-feuilleter__ou" aria-live="polite"><span class="er-feuilleter__legende"></span> <span class="er-feuilleter__compte">1 / ' . (int) $n . '</span></p>'
		. '<button type="button" class="er-feuilleter__fermer">' . esc_html__( 'Fermer', 'er-librairie' ) . '</button>'
		. '</div>'
		. '<ol class="er-feuilleter__pages">' . $liste . '</ol>'
		. '<button type="button" class="er-feuilleter__fleche er-feuilleter__fleche--avant" aria-label="' . esc_attr__( 'Page précédente', 'er-librairie' ) . '">‹</button>'
		. '<button type="button" class="er-feuilleter__fleche er-feuilleter__fleche--apres" aria-label="' . esc_attr__( 'Page suivante', 'er-librairie' ) . '">›</button>'
		. '<p class="er-feuilleter__mention">' . esc_html__( 'Extrait. Un clic sur la page l’agrandit.', 'er-librairie' ) . '</p>'
		. '</dialog>'
		. '</div>';
}

add_action(
	'init',
	function () {
		register_block_type(
			'er/fiche-feuilleter',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : get_the_ID();
					return $id ? er_feuilleter_html( $id ) : '';
				},
			)
		);
	}
);

/**
 * Pose les pages d'un livre : les images du dossier entrent dans la médiathèque (rattachées au livre), dans l'ordre du
 * manifeste ; les anciennes pages du livre sont effacées.
 *
 * @param int    $id      Le livre.
 * @param array  $pages   [ { fichier, legende, page } ].
 * @param string $dossier Le dossier des images.
 * @return int|WP_Error Le nombre de pages posées.
 */
function er_extraits_poser( $id, $pages, $dossier ) {
	if ( 'product' !== get_post_type( $id ) ) {
		return new WP_Error( 'er_extraits', 'Pas un livre : ' . $id );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$titre     = wp_strip_all_tags( get_the_title( $id ) );
	$nouvelles = array();
	foreach ( $pages as $n => $p ) {
		$source = trailingslashit( $dossier ) . basename( (string) $p['fichier'] );
		if ( ! is_readable( $source ) ) {
			return new WP_Error( 'er_extraits', 'Image introuvable : ' . $source );
		}
		// media_handle_sideload déplace le fichier : on lui en donne une copie.
		$copie = wp_tempnam( $source );
		copy( $source, $copie );
		$image = media_handle_sideload(
			array(
				'name'     => sanitize_title( $titre ) . '-extrait-' . ( $n + 1 ) . '.' . pathinfo( $source, PATHINFO_EXTENSION ),
				'tmp_name' => $copie,
			),
			$id,
			/* translators: 1: le titre du livre, 2: le numéro de l'extrait. */
			sprintf( __( '%1$s — extrait %2$d', 'er-librairie' ), $titre, $n + 1 )
		);
		if ( is_wp_error( $image ) ) {
			foreach ( $nouvelles as $deja ) {
				wp_delete_attachment( $deja['id'], true );
			}
			return $image;
		}
		$nouvelles[] = array(
			'id'      => (int) $image,
			'legende' => sanitize_text_field( (string) ( $p['legende'] ?? '' ) ),
			'page'    => (int) ( $p['page'] ?? 0 ),
		);
	}
	foreach ( er_extraits( $id ) as $ancienne ) {
		wp_delete_attachment( $ancienne['id'], true );
	}
	update_post_meta( $id, 'er_extraits', $nouvelles );
	return count( $nouvelles );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Pose les pages de tous les livres d'un dossier : wp er extraits importer <dossier> (son manifeste.json et un
	 * sous-dossier d'images par livre, tels que les écrit outils/extraits.py).
	 *
	 * @param array $args Le dossier.
	 */
	WP_CLI::add_command(
		'er extraits importer',
		function ( $args ) {
			$dossier = untrailingslashit( isset( $args[0] ) ? (string) $args[0] : '' );
			$fichier = $dossier . '/manifeste.json';
			if ( ! is_readable( $fichier ) ) {
				WP_CLI::error( 'Manifeste introuvable : ' . $fichier );
			}
			$manifeste = json_decode( (string) file_get_contents( $fichier ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- un fichier local du workflow.
			if ( ! is_array( $manifeste ) ) {
				WP_CLI::error( 'Manifeste illisible : ' . $fichier );
			}
			foreach ( $manifeste as $id => $livre ) {
				$n = er_extraits_poser( (int) $id, (array) ( $livre['pages'] ?? array() ), $dossier . '/' . (int) $id );
				if ( is_wp_error( $n ) ) {
					WP_CLI::warning( $id . ' : ' . $n->get_error_message() );
					continue;
				}
				WP_CLI::log( $id . ' ' . get_the_title( (int) $id ) . ' : ' . $n . ' pages' );
			}
			WP_CLI::success( 'Pages posées.' );
		}
	);

	/**
	 * Efface les pages d'un livre : wp er extraits effacer <identifiant>…
	 *
	 * @param array $args Les livres.
	 */
	WP_CLI::add_command(
		'er extraits effacer',
		function ( $args ) {
			foreach ( $args as $id ) {
				foreach ( er_extraits( (int) $id ) as $p ) {
					wp_delete_attachment( $p['id'], true );
				}
				delete_post_meta( (int) $id, 'er_extraits' );
				WP_CLI::log( $id . ' : pages effacées' );
			}
		}
	);
}
