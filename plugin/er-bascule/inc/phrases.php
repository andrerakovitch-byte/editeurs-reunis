<?php
/**
 * Les phrases des suggestions (24 nov. 2026) : une phrase par livre, au ton du libraire, que la fiche d'un autre livre
 * montre sous sa couverture quand elle le propose (la méta er_phrase ; er-librairie/inc/phrases.php l'affiche et
 * permet de la relire).
 *
 * wp er phrases poser [appliquer] : pose les phrases de donnees/phrases.tsv (identifiant, phrase). Une phrase
 * corrigée à la main depuis la dernière pose n'est pas remplacée : la commande garde la phrase qu'elle a posée
 * (er_phrase_posee) et ne touche qu'aux livres dont la phrase est encore la sienne. Une phrase remplacée perd sa
 * marque « relue ». Sans « appliquer », la commande dit ce qu'elle poserait.
 *
 * @package er-bascule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lit le fichier des phrases : identifiant → phrase.
 *
 * @param string $fichier Le fichier.
 */
function er_phrases_lire( $fichier ) {
	$phrases = array();
	foreach ( file( $fichier, FILE_IGNORE_NEW_LINES ) as $ligne ) {
		if ( '' === trim( $ligne ) || '#' === $ligne[0] ) {
			continue;
		}
		$c = explode( "\t", $ligne );
		if ( count( $c ) >= 2 && (int) $c[0] && '' !== trim( $c[1] ) ) {
			$phrases[ (int) $c[0] ] = trim( $c[1] );
		}
	}
	return $phrases;
}

/**
 * Ce que la pose ferait d'un livre : « poser », « identique » (la phrase y est déjà) ou « corrigee » (changée à la
 * main depuis la dernière pose, laissée).
 *
 * @param string $actuelle La phrase du livre.
 * @param string $posee    La dernière phrase posée par la commande.
 * @param string $nouvelle La phrase du fichier.
 */
function er_phrases_decision( $actuelle, $posee, $nouvelle ) {
	if ( $actuelle === $nouvelle ) {
		return 'identique';
	}
	if ( '' !== $actuelle && $actuelle !== $posee ) {
		return 'corrigee';
	}
	return 'poser';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Les phrases des suggestions : wp er phrases poser [appliquer].
	 *
	 * @param array $args « poser », puis « appliquer ».
	 */
	WP_CLI::add_command(
		'er phrases',
		function ( $args ) {
			if ( 'poser' !== ( $args[0] ?? '' ) ) {
				WP_CLI::error( 'Usage : wp er phrases poser [appliquer]' );
			}
			$appliquer = isset( $args[1] ) && 'appliquer' === $args[1];
			$fichier   = dirname( __DIR__ ) . '/donnees/phrases.tsv';
			if ( ! is_readable( $fichier ) ) {
				WP_CLI::error( 'Fichier introuvable : ' . $fichier );
			}
			$compte   = array(
				'poser'     => 0,
				'identique' => 0,
				'corrigee'  => 0,
			);
			$absents  = array();
			foreach ( er_phrases_lire( $fichier ) as $id => $phrase ) {
				if ( 'product' !== get_post_type( $id ) ) {
					$absents[] = $id;
					continue;
				}
				$actuelle = trim( (string) get_post_meta( $id, 'er_phrase', true ) );
				$decision = er_phrases_decision( $actuelle, trim( (string) get_post_meta( $id, 'er_phrase_posee', true ) ), $phrase );
				++$compte[ $decision ];
				if ( 'poser' === $decision && $appliquer ) {
					update_post_meta( $id, 'er_phrase', $phrase );
					update_post_meta( $id, 'er_phrase_posee', $phrase );
					delete_post_meta( $id, 'er_phrase_relue' );
				}
			}
			WP_CLI::log(
				( $appliquer ? 'Posé' : 'À poser (« wp er phrases poser appliquer » pour poser)' ) . ' : ' . $compte['poser'] . ' phrases ; '
				. $compte['identique'] . ' déjà en place ; ' . $compte['corrigee'] . ' corrigées à la main, laissées.'
			);
			if ( $absents ) {
				WP_CLI::log( count( $absents ) . ' identifiants qui ne sont plus des livres : ' . implode( ', ', $absents ) );
			}
		}
	);
}
