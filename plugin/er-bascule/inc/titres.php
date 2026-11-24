<?php
/**
 * Les titres russes traduits (24 nov. 2026) : sous un titre en cyrillique, les grilles de la librairie montrent sa
 * traduction (la méta er_titre_fr, que la fiche du livre permet ensuite de corriger).
 *
 * wp er titres lister : les livres au titre en cyrillique qui n'ont pas encore de traduction (identifiant, titre,
 * auteur), pour écrire donnees/titres-fr.tsv.
 * wp er titres poser [appliquer] : pose les traductions du fichier (identifiant, titre russe, traduction) ; une
 * traduction déjà saisie n'est pas remplacée, et un livre dont le titre a changé depuis est laissé de côté. Sans
 * « appliquer », la commande dit ce qu'elle poserait.
 *
 * @package er-bascule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lit le fichier des traductions : identifiant → [ titre russe, traduction ].
 *
 * @param string $fichier Le fichier.
 */
function er_titres_lire( $fichier ) {
	$titres = array();
	foreach ( file( $fichier, FILE_IGNORE_NEW_LINES ) as $ligne ) {
		if ( '' === trim( $ligne ) || '#' === $ligne[0] ) {
			continue;
		}
		$c = explode( "\t", $ligne );
		if ( count( $c ) >= 3 && (int) $c[0] && '' !== trim( $c[2] ) ) {
			$titres[ (int) $c[0] ] = array( trim( $c[1] ), trim( $c[2] ) );
		}
	}
	return $titres;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Les titres russes : wp er titres lister, wp er titres poser [appliquer].
	 *
	 * @param array $args « lister », ou « poser » puis « appliquer ».
	 */
	WP_CLI::add_command(
		'er titres',
		function ( $args ) {
			$quoi = $args[0] ?? '';
			if ( 'lister' === $quoi ) {
				$ids = get_posts(
					array(
						'post_type'      => 'product',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'lang'           => '',
						'orderby'        => 'ID',
						'order'          => 'ASC',
					)
				);
				$n   = 0;
				foreach ( $ids as $id ) {
					$titre = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
					if ( ! preg_match( '/\p{Cyrillic}/u', $titre ) || '' !== trim( (string) get_post_meta( $id, 'er_titre_fr', true ) ) || get_post_meta( $id, 'er_evenement', true ) ) {
						continue;
					}
					$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
					WP_CLI::log( $id . "\t" . $titre . "\t" . implode( ', ', is_array( $auteurs ) ? $auteurs : array() ) );
					++$n;
				}
				WP_CLI::log( $n . ' livres au titre en cyrillique, sans traduction.' );
				return;
			}
			if ( 'poser' !== $quoi ) {
				WP_CLI::error( 'Usage : wp er titres lister | wp er titres poser [appliquer]' );
			}
			$appliquer = isset( $args[1] ) && 'appliquer' === $args[1];
			$fichier   = dirname( __DIR__ ) . '/donnees/titres-fr.tsv';
			if ( ! is_readable( $fichier ) ) {
				WP_CLI::error( 'Fichier introuvable : ' . $fichier );
			}
			$poses   = 0;
			$deja    = 0;
			$changes = array();
			foreach ( er_titres_lire( $fichier ) as $id => list( $russe, $fr ) ) {
				if ( 'product' !== get_post_type( $id ) ) {
					$changes[] = $id . ' (plus de livre)';
					continue;
				}
				$titre = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
				if ( '' !== $russe && $titre !== $russe ) {
					$changes[] = $id . ' (« ' . $titre . ' »)';
					continue;
				}
				if ( '' !== trim( (string) get_post_meta( $id, 'er_titre_fr', true ) ) ) {
					++$deja;
					continue;
				}
				if ( $appliquer ) {
					update_post_meta( $id, 'er_titre_fr', sanitize_text_field( $fr ) );
					if ( function_exists( 'er_index_contenu' ) ) {
						er_index_contenu( $id );
					}
				}
				++$poses;
			}
			WP_CLI::log( ( $appliquer ? 'Posé' : 'À poser (« wp er titres poser appliquer » pour poser)' ) . ' : ' . $poses . ' traductions ; ' . $deja . ' déjà saisies, laissées.' );
			if ( $changes ) {
				WP_CLI::log( count( $changes ) . ' livres laissés de côté (titre changé ou livre disparu) : ' . implode( ', ', $changes ) );
			}
		}
	);
}
