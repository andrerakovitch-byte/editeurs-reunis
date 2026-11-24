<?php
/**
 * Les étiquettes des livres, relues fiche par fiche pour l'audit de la librairie (24 nov. 2026).
 *
 * donnees/etiquettes.tsv donne, pour chaque livre (par son identifiant), sa langue, ses rayons, ses sujets, l'époque
 * de son écriture, les époques dont il parle, les personnes dont il parle (« Autour de ») et l'auteur qui manque.
 * Les codes sont ci-dessous et dans inc/epoques.php de er-librairie.
 *
 * wp er etiquettes poser [appliquer] [--fichier=<chemin>]
 * Le fichier est donnees/etiquettes.tsv, sauf --fichier. Sans « appliquer », la commande dit ce qu'elle changerait. Avec, elle pose :
 * - la langue, les sujets et les époques : ceux du fichier remplacent ceux du livre ;
 * - les rayons : la liste du fichier (et leurs rayons parents) remplace les rayons du livre ; un rayon hors de
 *   l'arbre de la librairie reste ;
 * - « Autour de » : les personnes du fichier s'ajoutent à celles qui y sont déjà ;
 * - l'auteur : seulement si le livre n'en a aucun.
 * Une personne inconnue devient un auteur (comme dans l'administration) ; la commande les nomme avant de les créer.
 *
 * @package er-bascule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les sujets, vocabulaire fermé : code → nom (le nom d'un sujet qui existe déjà sur le site est repris tel quel).
 */
function er_etiquettes_sujets() {
	return array(
		'poe' => 'Poésie',
		'emi' => 'Émigration',
		'gou' => 'Goulag et dissidence',
		'ort' => 'Orthodoxie',
		'mus' => 'Musique',
		'cin' => 'Cinéma',
		'art' => 'Arts',
		'the' => 'Théâtre',
		'phi' => 'Philosophie',
		'ukr' => 'Ukraine',
		'tra' => 'Traduction',
		'mai' => 'Histoire de la maison',
		'gue' => 'Guerre',
		'pol' => 'Pouvoir et politique',
		'fem' => 'Femmes',
		'enf' => 'Enfance et famille',
		'amo' => 'Amour',
		'sai' => 'Saints et monastères',
		'jui' => 'Juifs de Russie',
		'spb' => 'Saint-Pétersbourg',
		'mos' => 'Moscou',
		'sib' => 'Sibérie et Grand Nord',
		'cau' => 'Caucase et Asie centrale',
		'eur' => 'Russie et Europe',
		'lan' => 'Langue russe',
		'con' => 'Contes et légendes',
		'hum' => 'Humour et satire',
		'fan' => 'Fantastique et anticipation',
		'pay' => 'Paysans et campagne',
		'cri' => 'Critique et histoire littéraire',
	);
}

/**
 * Lit le fichier : identifiant → [ langues, rayons, sujets, écriture, périodes, autour, auteurs ].
 *
 * @param string $fichier Le chemin du fichier.
 * @return array
 */
function er_etiquettes_lire( $fichier ) {
	$liste  = fn( $s, $sep = ',' ) => '-' === trim( $s ) ? array() : array_values( array_filter( array_map( 'trim', explode( $sep, $s ) ), 'strlen' ) );
	$livres = array();
	foreach ( file( $fichier, FILE_IGNORE_NEW_LINES ) as $ligne ) {
		if ( '' === trim( $ligne ) || '#' === $ligne[0] ) {
			continue;
		}
		$c = explode( "\t", $ligne );
		if ( count( $c ) < 8 ) {
			WP_CLI::warning( 'Ligne incomplète : ' . $ligne );
			continue;
		}
		$livres[ (int) $c[0] ] = array(
			'langues'  => $liste( $c[1] ),
			'rayons'   => $liste( $c[2] ),
			'sujets'   => $liste( $c[3] ),
			'ecriture' => $liste( $c[4] ),
			'periode'  => $liste( $c[5] ),
			'autour'   => $liste( $c[6], ';' ),
			'auteurs'  => $liste( $c[7], ';' ),
		);
	}
	return $livres;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'er etiquettes poser',
		function ( $args, $assoc ) {
			$fichier   = $assoc['fichier'] ?? dirname( __DIR__ ) . '/donnees/etiquettes.tsv';
			$appliquer = in_array( 'appliquer', $args, true );
			if ( ! is_readable( $fichier ) ) {
				WP_CLI::error( 'Le fichier est introuvable : ' . $fichier );
			}
			$livres = er_etiquettes_lire( $fichier );
			if ( $appliquer ) {
				er_epoques_semer();
				wp_defer_term_counting( true );
			}

			// Les codes et les slugs, vérifiés une fois : une erreur arrête tout avant d'écrire.
			$epoques = er_epoques();
			$langues = er_langues_livres();
			$sujets  = er_etiquettes_sujets();
			$rayons  = array();
			$erreurs = array();
			foreach ( $livres as $id => $l ) {
				$inconnus = array_merge(
					array_map( fn( $c ) => "langue $c", array_diff( $l['langues'], array_keys( $langues ) ) ),
					array_map( fn( $c ) => "sujet $c", array_diff( $l['sujets'], array_keys( $sujets ) ) ),
					array_map( fn( $c ) => "époque $c", array_diff( array_merge( $l['ecriture'], $l['periode'] ), array_map( 'strval', array_keys( $epoques ) ) ) )
				);
				foreach ( $l['rayons'] as $s ) {
					if ( ! isset( $rayons[ $s ] ) ) {
						$t            = get_term_by( 'slug', $s, 'product_cat' );
						$rayons[ $s ] = $t ? (int) $t->term_id : 0;
					}
					if ( ! $rayons[ $s ] ) {
						$inconnus[] = "rayon $s";
					}
				}
				foreach ( $inconnus as $i ) {
					$erreurs[] = "#$id $i";
				}
			}
			if ( $erreurs ) {
				WP_CLI::error( 'Codes inconnus : ' . implode( ', ', array_unique( $erreurs ) ) );
			}
			// L'arbre de la librairie : les rayons de premier niveau des rayons du fichier.
			$racines = array();
			foreach ( array_filter( $rayons ) as $tid ) {
				$haut = get_ancestors( $tid, 'product_cat', 'taxonomy' );
				$racines[ $haut ? end( $haut ) : $tid ] = true;
			}

			$compte    = array_fill_keys( array( 'livres', 'absents', 'langue', 'rayons', 'sujets', 'ecriture', 'periode', 'autour', 'auteur' ), 0 );
			$nouvelles = array();
			$exemples  = array();
			$sujet_id  = array();
			foreach ( $livres as $id => $l ) {
				if ( 'product' !== get_post_type( $id ) ) {
					++$compte['absents'];
					continue;
				}
				++$compte['livres'];
				$change = array();

				// La langue.
				$avant = er_librairie_slugs( $id, 'langue' );
				$apres = array_map( fn( $c ) => $langues[ $c ][0], $l['langues'] );
				if ( array_diff( $avant, $apres ) || array_diff( $apres, $avant ) ) {
					$change['langue'] = implode( ',', $avant ) . ' → ' . implode( ',', $apres );
					if ( $appliquer ) {
						$ids = array_map( fn( $c ) => er_terme_assurer( 'langue', $langues[ $c ][0], $langues[ $c ][1] ), $l['langues'] );
						wp_set_object_terms( $id, array_filter( $ids ), 'langue' );
					}
				}

				// Les rayons, avec leurs parents ; ceux qui sont hors de l'arbre restent.
				$avant_ids = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'ids' ) );
				$garder    = array();
				foreach ( $avant_ids as $tid ) {
					$haut = get_ancestors( $tid, 'product_cat', 'taxonomy' );
					$t    = get_term( $tid, 'product_cat' );
					if ( ! isset( $racines[ $haut ? end( $haut ) : $tid ] ) && ! in_array( $t->slug, array( 'sans-rayon', 'uncategorized' ), true ) ) {
						$garder[] = $tid;
					}
				}
				$apres_ids = $garder;
				foreach ( $l['rayons'] as $s ) {
					$apres_ids = array_merge( $apres_ids, array( $rayons[ $s ] ), get_ancestors( $rayons[ $s ], 'product_cat', 'taxonomy' ) );
				}
				$apres_ids = array_values( array_unique( array_map( 'intval', $apres_ids ) ) );
				if ( array_diff( $avant_ids, $apres_ids ) || array_diff( $apres_ids, $avant_ids ) ) {
					$change['rayons'] = implode( ',', er_librairie_slugs( $id, 'product_cat' ) ) . ' → ' . implode( ',', $l['rayons'] );
					if ( $appliquer ) {
						wp_set_object_terms( $id, $apres_ids, 'product_cat' );
					}
				}

				// Les sujets.
				$noms  = array_map( fn( $c ) => $sujets[ $c ], $l['sujets'] );
				$avant = wp_get_post_terms( $id, 'er_sujet', array( 'fields' => 'names' ) );
				$avant = array_map( fn( $n ) => html_entity_decode( $n, ENT_QUOTES, 'UTF-8' ), is_wp_error( $avant ) ? array() : $avant );
				if ( array_diff( $avant, $noms ) || array_diff( $noms, $avant ) ) {
					$change['sujets'] = implode( ', ', $noms );
					if ( $appliquer ) {
						$ids = array();
						foreach ( $l['sujets'] as $c ) {
							if ( ! isset( $sujet_id[ $c ] ) ) {
								$t              = get_term_by( 'name', $sujets[ $c ], 'er_sujet' );
								$sujet_id[ $c ] = $t ? (int) $t->term_id : er_terme_assurer( 'er_sujet', sanitize_title( $sujets[ $c ] ), $sujets[ $c ] );
							}
							$ids[] = $sujet_id[ $c ];
						}
						wp_set_object_terms( $id, array_filter( $ids ), 'er_sujet' );
					}
				}

				// Les époques.
				foreach ( array( 'ecriture' => 'er_ecriture', 'periode' => 'er_periode' ) as $cle => $taxonomie ) {
					$avant = er_librairie_slugs( $id, $taxonomie );
					$apres = array_map( fn( $c ) => $epoques[ $c ][0], $l[ $cle ] );
					if ( array_diff( $avant, $apres ) || array_diff( $apres, $avant ) ) {
						$change[ $cle ] = implode( ',', $l[ $cle ] );
						if ( $appliquer ) {
							$ids = array_map( fn( $s ) => (int) get_term_by( 'slug', $s, $taxonomie )->term_id, $apres );
							wp_set_object_terms( $id, $ids, $taxonomie );
						}
					}
				}

				// « Autour de » : on ajoute.
				if ( $l['autour'] ) {
					$deja   = array_map( 'intval', wp_list_pluck( er_autour_de( $id ), 'term_id' ) );
					$ajouts = array();
					foreach ( $l['autour'] as $nom ) {
						$t = er_personne_trouver( $nom );
						if ( ! $t ) {
							$nouvelles[ $nom ] = true;
						}
						if ( ! $t || ! in_array( (int) $t->term_id, $deja, true ) ) {
							$ajouts[] = $t ? (int) $t->term_id : $nom;
						}
					}
					if ( $ajouts ) {
						$change['autour'] = implode( ' ; ', $l['autour'] );
						if ( $appliquer ) {
							er_poser_autour_de( $id, array_merge( $deja, $ajouts ) );
						}
					}
				}

				// L'auteur, s'il manque.
				if ( $l['auteurs'] && ! wp_get_post_terms( $id, 'auteur', array( 'fields' => 'ids' ) ) ) {
					$change['auteur'] = implode( ' ; ', $l['auteurs'] );
					foreach ( $l['auteurs'] as $nom ) {
						if ( ! er_personne_trouver( $nom ) ) {
							$nouvelles[ $nom ] = true;
						}
					}
					if ( $appliquer ) {
						$ids = array_filter( array_map( fn( $n ) => er_personne_par_nom( $n, true ), $l['auteurs'] ) );
						wp_set_object_terms( $id, array_map( fn( $t ) => (int) $t->term_id, $ids ), 'auteur', true );
					}
				}

				foreach ( array_keys( $change ) as $cle ) {
					++$compte[ $cle ];
				}
				if ( $change && count( $exemples ) < 12 ) {
					$exemples[] = '#' . $id . ' ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) . ' | ' . implode( ' | ', array_map( fn( $k, $v ) => "$k: $v", array_keys( $change ), $change ) );
				}
			}
			if ( $appliquer ) {
				wp_defer_term_counting( false );
			}

			foreach ( $exemples as $e ) {
				WP_CLI::log( $e );
			}
			WP_CLI::log( sprintf( '%d livres lus, %d absents du site.', $compte['livres'], $compte['absents'] ) );
			foreach ( array( 'langue', 'rayons', 'sujets', 'ecriture', 'periode', 'autour', 'auteur' ) as $cle ) {
				WP_CLI::log( sprintf( '  %-10s %d livres', $cle, $compte[ $cle ] ) );
			}
			if ( $nouvelles ) {
				WP_CLI::log( sprintf( 'Personnes inconnues (%d), %s : %s', count( $nouvelles ), $appliquer ? 'créées comme auteurs' : 'elles deviendraient des auteurs', implode( ' ; ', array_keys( $nouvelles ) ) ) );
			}
			WP_CLI::success( $appliquer ? 'Étiquettes posées.' : 'Rien n’est écrit : ajouter « appliquer » pour poser.' );
		}
	);

	/**
	 * La revue de presse des livres récents : wp er presse poser [appliquer] [--fichier=<chemin>]. Le fichier
	 * (donnees/presse.tsv) donne, par livre, les articles et les prix ; ils remplacent ceux du livre (er_presse).
	 */
	WP_CLI::add_command(
		'er presse poser',
		function ( $args, $assoc ) {
			$fichier   = $assoc['fichier'] ?? dirname( __DIR__ ) . '/donnees/presse.tsv';
			$appliquer = in_array( 'appliquer', $args, true );
			$par_livre = array();
			foreach ( file( $fichier, FILE_IGNORE_NEW_LINES ) as $ligne ) {
				$c = explode( "\t", $ligne );
				if ( '' === trim( $ligne ) || '#' === $ligne[0] || count( $c ) < 5 ) {
					continue;
				}
				$par_livre[ (int) $c[0] ][] = array(
					'genre' => 'prix' === trim( $c[1] ) ? 'prix' : 'presse',
					'nom'   => trim( $c[2] ),
					'date'  => trim( $c[3] ),
					'lien'  => '-' === trim( $c[4] ) ? '' : esc_url_raw( trim( $c[4] ) ),
				);
			}
			$poses = 0;
			foreach ( $par_livre as $id => $entrees ) {
				if ( 'product' !== get_post_type( $id ) ) {
					WP_CLI::warning( "#$id n’est pas un livre." );
					continue;
				}
				WP_CLI::log( '#' . $id . ' ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) . ' : ' . implode( ' ; ', wp_list_pluck( $entrees, 'nom' ) ) );
				if ( $appliquer ) {
					update_post_meta( $id, 'er_presse', $entrees );
					++$poses;
				}
			}
			WP_CLI::success( sprintf( '%d livres dans le fichier, %d posés.', count( $par_livre ), $poses ) );
		}
	);
}
