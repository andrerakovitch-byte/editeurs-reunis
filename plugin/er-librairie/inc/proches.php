<?php
/**
 * Les livres proches par le sens (25 nov. 2026) : pour chaque livre, ses voisins les plus proches d'après ce qu'il
 * raconte, calculés par un modèle de langue multilingue (bge-m3) hors du site, dans le workflow « Déployer chez
 * Kinsta » (outils/proches.py). Le site ne fait que lire le résultat : « Pour aller plus loin » s'en sert pour
 * choisir, à l'intérieur de chacun de ses modules, les livres les plus pertinents (inc/continuer-modules.php,
 * er_continuer_ranger).
 *
 * - wp er proches exporter : une ligne JSON par livre (identifiant et texte : titre, sorte, auteurs, époques, thèmes,
 *   phrase, début de la notice), sur la sortie, pour le workflow ; rien n'est écrit dans le dépôt.
 * - wp er proches importer <fichier> : pose les voisins calculés (option er_proches) et périme l'index de « Pour
 *   aller plus loin ».
 * - wp er proches voir <identifiant> : les voisins d'un livre, pour vérifier.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les voisins de tous les livres : identifiant → [ voisin → proximité (cosinus, de 0 à 1) ], les plus proches d'abord.
 *
 * @return array<int,array<int,float>>
 */
function er_proches_tous() {
	$v = get_option( 'er_proches', array() );
	return is_array( $v ) && isset( $v['voisins'] ) && is_array( $v['voisins'] ) ? $v['voisins'] : array();
}

/**
 * Le texte d'un livre pour le modèle : ce qui dit de quoi il parle, sans le prix ni la disponibilité.
 *
 * @param int   $id Le livre.
 * @param array $ix L'index de « Pour aller plus loin » (er_continuer_index).
 * @return string
 */
function er_proches_texte( $id, $ix ) {
	$l      = isset( $ix['livres'][ $id ] ) ? $ix['livres'][ $id ] : array();
	$lignes = array();
	$titre  = isset( $l['t'] ) ? (string) $l['t'] : get_the_title( $id );
	if ( ! empty( $l['fr'] ) && $l['fr'] !== $titre ) {
		$titre .= ' (' . $l['fr'] . ')';
	}
	$lignes[] = $titre;
	if ( function_exists( 'er_continuer_sorte' ) && $l ) {
		$sorte = er_continuer_sorte( $l, $ix['rayons'] );
		if ( isset( er_continuer_sortes()[ $sorte ] ) ) {
			$lignes[] = er_continuer_sortes()[ $sorte ][0];
		}
	}
	$noms = array();
	foreach ( isset( $l['a'] ) ? (array) $l['a'] : array() as $p ) {
		if ( isset( $ix['personnes'][ $p ] ) ) {
			$noms[] = $ix['personnes'][ $p ]['n'];
			foreach ( (array) ( isset( $ix['personnes'][ $p ]['g'] ) ? $ix['personnes'][ $p ]['g'] : array() ) as $g ) {
				$noms[] = $g;
			}
		}
	}
	if ( $noms ) {
		$lignes[] = implode( ', ', array_unique( $noms ) );
	}
	$mots = array();
	if ( function_exists( 'er_continuer_epoques_mots' ) ) {
		$epoques = er_continuer_epoques_mots();
		foreach ( array_merge( isset( $l['p'] ) ? (array) $l['p'] : array(), isset( $l['e'] ) ? (array) $l['e'] : array() ) as $e ) {
			if ( isset( $epoques[ $e ] ) ) {
				$mots[] = $epoques[ $e ][0];
			}
		}
	}
	foreach ( isset( $l['s'] ) ? (array) $l['s'] : array() as $s ) {
		if ( isset( $ix['sujets'][ $s ]['n'] ) ) {
			$mots[] = $ix['sujets'][ $s ]['n'];
		}
	}
	if ( $mots ) {
		$lignes[] = implode( ', ', array_unique( $mots ) );
	}
	$phrase = (string) get_post_meta( $id, 'er_phrase', true );
	if ( '' !== trim( $phrase ) ) {
		$lignes[] = trim( $phrase );
	}
	// La notice, sans ses balises, jusqu'à 2 000 signes : le modèle lit le début, qui dit de quoi parle le livre.
	$post = get_post( $id );
	if ( $post ) {
		$notice = html_entity_decode( wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br />' ), "\n", $post->post_content . "\n" . $post->post_excerpt ) ), ENT_QUOTES, 'UTF-8' );
		$notice = trim( (string) preg_replace( '/\s+/u', ' ', $notice ) );
		if ( '' !== $notice ) {
			$lignes[] = mb_substr( $notice, 0, 2000 );
		}
	}
	return implode( "\n", $lignes );
}

/**
 * Pose les voisins calculés par le workflow : { modele, k, quand, voisins: { id: [ [ voisin, proximité ], … ] } }.
 * Les identifiants qui ne sont plus des livres tombent.
 *
 * @param array $donnees Le contenu du fichier.
 * @return int Le nombre de livres qui ont des voisins.
 */
function er_proches_poser( $donnees ) {
	$ix      = function_exists( 'er_continuer_index' ) ? er_continuer_index() : array( 'livres' => array() );
	$voisins = array();
	foreach ( isset( $donnees['voisins'] ) ? (array) $donnees['voisins'] : array() as $id => $liste ) {
		$id = (int) $id;
		if ( ! isset( $ix['livres'][ $id ] ) ) {
			continue;
		}
		foreach ( (array) $liste as $paire ) {
			$v = isset( $paire[0] ) ? (int) $paire[0] : 0;
			if ( $v && $v !== $id && isset( $ix['livres'][ $v ] ) ) {
				$voisins[ $id ][ $v ] = round( (float) $paire[1], 3 );
			}
		}
	}
	update_option(
		'er_proches',
		array(
			'modele'  => isset( $donnees['modele'] ) ? sanitize_text_field( (string) $donnees['modele'] ) : '',
			'quand'   => time(),
			'voisins' => $voisins,
		),
		false
	);
	if ( function_exists( 'er_continuer_perimer' ) ) {
		er_continuer_perimer();
	}
	return count( $voisins );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Une ligne JSON par livre, sur la sortie : wp er proches exporter.
	 */
	WP_CLI::add_command(
		'er proches exporter',
		function () {
			$ix = er_continuer_index();
			foreach ( array_keys( $ix['livres'] ) as $id ) {
				WP_CLI::line(
					wp_json_encode(
						array(
							'id'    => (int) $id,
							'texte' => er_proches_texte( (int) $id, $ix ),
						),
						JSON_UNESCAPED_UNICODE
					)
				);
			}
		}
	);

	/**
	 * Pose les voisins d'un fichier : wp er proches importer <fichier>.
	 *
	 * @param array $args Le fichier.
	 */
	WP_CLI::add_command(
		'er proches importer',
		function ( $args ) {
			$fichier = isset( $args[0] ) ? (string) $args[0] : '';
			if ( '' === $fichier || ! is_readable( $fichier ) ) {
				WP_CLI::error( 'Fichier introuvable : ' . $fichier );
			}
			$donnees = json_decode( (string) file_get_contents( $fichier ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- un fichier local du workflow.
			if ( ! is_array( $donnees ) ) {
				WP_CLI::error( 'Fichier illisible : ' . $fichier );
			}
			$n = er_proches_poser( $donnees );
			WP_CLI::success( $n . ' livres ont leurs voisins (' . ( isset( $donnees['modele'] ) ? $donnees['modele'] : '?' ) . ').' );
		}
	);

	/**
	 * Les voisins d'un livre : wp er proches voir <identifiant>.
	 *
	 * @param array $args L'identifiant.
	 */
	WP_CLI::add_command(
		'er proches voir',
		function ( $args ) {
			$tous = er_proches_tous();
			foreach ( $args as $id ) {
				$id = (int) $id;
				WP_CLI::log( '— ' . $id . ' ' . get_the_title( $id ) );
				foreach ( array_slice( isset( $tous[ $id ] ) ? $tous[ $id ] : array(), 0, 15, true ) as $v => $s ) {
					WP_CLI::log( '  ' . number_format( $s, 3 ) . '  ' . $v . ' ' . get_the_title( $v ) );
				}
			}
		}
	);
}
