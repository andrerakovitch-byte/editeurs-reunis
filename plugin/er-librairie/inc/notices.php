<?php
/**
 * Les notices des livres, venues des catalogues des bibliothèques (outils/catalogue/notices.py, le workflow
 * « Déployer chez Kinsta », « notices ») : wp er notices importer [<fichier>] [appliquer].
 *
 * Ce qui est sûr se pose, sans jamais remplacer ce qu'on a saisi : l'ISBN (le champ GTIN de WooCommerce, ou la méta
 * er_isbn quand un autre livre a déjà ce numéro), le nombre de pages, l'année, l'éditeur. L'œuvre se garde à part :
 * l'année de première parution (er_premiere_parution), les sujets Rameau de la BnF (er_rameau), les sujets d'Open
 * Library (er_sujets_ol), qui servent aux étiquettes (inc/etiquettes.php). Chaque livre retient d'où vient sa notice
 * et ce qu'elle a posé (er_notice) : on peut la retirer.
 *
 * Ce qui est à revoir (plusieurs éditions possibles) est listé, avec les candidates.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pose la notice d'un livre.
 *
 * @param array $n         La notice (notices.py).
 * @param bool  $appliquer Poser, sinon seulement compter.
 * @return string[] Ce qui a été (ou serait) posé.
 */
function er_notice_poser( $n, $appliquer ) {
	$p = wc_get_product( (int) $n['id'] );
	if ( ! $p ) {
		return array();
	}
	$pose = array();
	$sur  = $n['sur'] ?? array();
	if ( ! empty( $sur['isbn'] ) && '' === er_isbn( $p ) ) {
		$pose[] = 'isbn';
		if ( $appliquer ) {
			try {
				$p->set_global_unique_id( $sur['isbn'] );
			} catch ( Exception $e ) {
				$p->update_meta_data( 'er_isbn', $sur['isbn'] ); // Un autre livre a ce numéro : même édition, deux fiches.
			}
		}
	}
	if ( ! empty( $sur['pages'] ) && ! (int) $p->get_meta( 'er_pages' ) ) {
		$pose[] = 'pages';
		if ( $appliquer ) {
			$p->update_meta_data( 'er_pages', (int) $sur['pages'] );
		}
	}
	if ( ! empty( $sur['annee'] ) && ! preg_match( '/\d{4}/', (string) $p->get_meta( 'er_annee' ) ) ) {
		$pose[] = 'année';
		if ( $appliquer ) {
			$p->update_meta_data( 'er_annee', sanitize_text_field( $sur['annee'] ) );
		}
	}
	if ( ! empty( $sur['editeur'] ) && '' === trim( (string) $p->get_meta( 'er_editeur' ) ) && ! er_editeur_du_livre( $p->get_id() )['termes'] ) {
		$pose[] = 'éditeur';
		if ( $appliquer ) {
			$p->update_meta_data( 'er_editeur', er_notice_editeur( sanitize_text_field( $sur['editeur'] ) ) );
		}
	}
	$oeuvre = $n['oeuvre'] ?? array();
	if ( ! empty( $oeuvre['premiere_parution'] ) ) {
		$pose[] = 'première parution';
		if ( $appliquer ) {
			$p->update_meta_data( 'er_premiere_parution', (int) $oeuvre['premiere_parution'] );
		}
	}
	foreach ( array( 'rameau' => 'er_rameau', 'sujets_ol' => 'er_sujets_ol' ) as $cle => $meta ) {
		if ( ! empty( $oeuvre[ $cle ] ) ) {
			$pose[] = $cle;
			if ( $appliquer ) {
				$p->update_meta_data( $meta, implode( "\n", array_map( 'sanitize_text_field', (array) $oeuvre[ $cle ] ) ) );
			}
		}
	}
	if ( ! empty( $sur['langues'] ) && $appliquer ) {
		$p->update_meta_data( 'er_notice_langues', implode( ',', array_map( 'sanitize_key', (array) $sur['langues'] ) ) );
	}
	if ( $pose && $appliquer ) {
		$p->update_meta_data(
			'er_notice',
			wp_json_encode(
				array(
					'date'    => wp_date( 'Y-m-d' ),
					'source'  => $sur['source'] ?? '',
					'lien'    => $sur['lien'] ?? '',
					'sources' => $n['sources'] ?? array(),
					'pose'    => $pose,
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
		);
		$p->save();
	}
	return $pose;
}

/**
 * L'éditeur comme l'écrit la BnF (« les Éditions Noir sur blanc (Lausanne (Suisse)) »), tel qu'une fiche le montre :
 * sans la ville entre parenthèses, avec une capitale (« Les Éditions Noir sur blanc »).
 *
 * @param string $texte L'éditeur du catalogue.
 */
function er_notice_editeur( $texte ) {
	$t = trim( preg_replace( '/\s*\(.*$/u', '', trim( wp_strip_all_tags( (string) $texte ) ) ) );
	return '' === $t ? '' : mb_strtoupper( mb_substr( $t, 0, 1 ) ) . mb_substr( $t, 1 );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Les notices : wp er notices importer [<fichier>] [appliquer].
	 *
	 * @param array $args Le fichier, et « appliquer ».
	 */
	WP_CLI::add_command(
		'er notices importer',
		function ( $args ) {
			// Sans fichier, celui que le workflow a déposé hors du site (private/notices.json) : la commande peut
			// alors se lancer depuis « Une commande de l'extension », qui refuse les chemins.
			$appliquer = in_array( 'appliquer', $args, true );
			$args      = array_values( array_diff( $args, array( 'appliquer' ) ) );
			$fichier   = $args[0] ?? dirname( untrailingslashit( ABSPATH ) ) . '/private/notices.json';
			$liste     = json_decode( (string) file_get_contents( $fichier ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! is_array( $liste ) ) {
				WP_CLI::error( 'Fichier illisible : ' . $fichier );
			}
			$comptes   = array();
			$revoir    = array();
			foreach ( $liste as $n ) {
				$pose = er_notice_poser( $n, $appliquer );
				foreach ( $pose as $champ ) {
					$comptes[ $champ ] = ( $comptes[ $champ ] ?? 0 ) + 1;
				}
				// Sans « appliquer », de quoi vérifier à la main : ce que la notice sûre poserait.
				if ( ! $appliquer && ! empty( $n['sur'] ) ) {
					$sur = $n['sur'];
					WP_CLI::log( sprintf( 'SUR #%d %s || %s %s %sp %s %s || %s', $n['id'], html_entity_decode( get_the_title( (int) $n['id'] ), ENT_QUOTES, 'UTF-8' ), $sur['isbn'] ?? '', $sur['annee'] ?? '', $sur['pages'] ?? '', $sur['editeur'] ?? '', implode( ',', (array) ( $sur['langues'] ?? array() ) ), $sur['lien'] ?? '' ) );
				}
				if ( ! empty( $n['a_revoir'] ) && empty( $n['sur'] ) ) {
					$revoir[] = $n;
				}
			}
			WP_CLI::log( sprintf( '%d notices lues ; %s :', count( $liste ), $appliquer ? 'posé' : 'à poser (« appliquer » pour poser)' ) );
			foreach ( $comptes as $champ => $nombre ) {
				WP_CLI::log( sprintf( '  %-24s %d', $champ, $nombre ) );
			}
			WP_CLI::log( sprintf( 'À revoir (plusieurs éditions possibles, ou l’éditeur ne concorde pas) : %d', count( $revoir ) ) );
			foreach ( $revoir as $n ) {
				$p = wc_get_product( (int) $n['id'] );
				if ( ! $p ) {
					continue;
				}
				$candidates = array();
				foreach ( $n['a_revoir'] as $c ) {
					$candidates[] = trim( sprintf( '%s %s %s %sp %s', $c['editeur'] ?? '', $c['annee'] ?? '', implode( ',', (array) ( $c['isbn'] ?? array() ) ), $c['pages'] ?? '', $c['lien'] ?? '' ) );
				}
				WP_CLI::log( sprintf( 'REVOIR #%d %s || %s', $p->get_id(), html_entity_decode( $p->get_name(), ENT_QUOTES, 'UTF-8' ), implode( ' || ', $candidates ) ) );
			}
		}
	);

	/**
	 * Les éditeurs posés par les notices, sans leur ville : wp er notices editeurs [appliquer].
	 */
	WP_CLI::add_command(
		'er notices editeurs',
		function ( $args ) {
			$appliquer = in_array( 'appliquer', $args, true );
			$n         = 0;
			foreach ( er_librairie_ids() as $id ) {
				$avant = (string) get_post_meta( $id, 'er_editeur', true );
				if ( '' === $avant || false === strpos( $avant, '(' ) || '' === (string) get_post_meta( $id, 'er_notice', true ) ) {
					continue;
				}
				$apres = er_notice_editeur( $avant );
				if ( $apres === $avant || '' === $apres ) {
					continue;
				}
				++$n;
				WP_CLI::log( '#' . $id . ' ' . $avant . ' → ' . $apres );
				if ( $appliquer ) {
					update_post_meta( $id, 'er_editeur', $apres );
				}
			}
			WP_CLI::success( sprintf( '%d éditeurs %s.', $n, $appliquer ? 'nettoyés' : 'à nettoyer (ajouter « appliquer »)' ) );
		}
	);
}
