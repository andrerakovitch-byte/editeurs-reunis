<?php
/**
 * Le relevé des rôles recoupé (24 nov. 2026) : qui a traduit, préfacé, établi, illustré, dirigé ou revu chaque livre,
 * d'après le texte des fiches, la BnF et les libraires, et les corrections des fiches qu'il a fait apparaître.
 *
 * wp er recoupement [appliquer] :
 * - donnees/roles-recoupes.tsv (identifiant, rôle, personne) : pour un livre, chaque rôle cité remplace les personnes
 *   posées dans ce rôle (er_poser_personnes : une personne inconnue devient un auteur) ; les rôles qui n'y sont pas ne
 *   bougent pas ;
 * - donnees/corrections-fiches.tsv (identifiant, champ, avant, après) : les noms mal écrits dans la présentation et le
 *   résumé (« Kchayan » pour Kehayan), les titres qui portent un nom de personne, les auteurs qui manquent (posés
 *   seulement sur un livre qui n'en a pas, plusieurs lignes pour plusieurs auteurs), les éditeurs mal écrits dans le
 *   classement (editeur-nom), l'éditeur, l'année et le nombre de pages que le texte donne et que la fiche n'a pas (ou
 *   qui sont faux : « avant » dit alors la valeur à remplacer, et rien d'autre n'est touché), la nature d'une œuvre
 *   collective (« revue », « collectif » : oeuvres-collectives.php, dans l'extension de la librairie), l'ISBN d'une
 *   fiche qui n'en a pas (isbn : treize chiffres à la clé juste, ou dix, convertis).
 * Sans « appliquer », la commande dit ce qu'elle ferait : les changements de rôles, les personnes nouvelles, les
 * corrections introuvables (déjà faites, ou un texte qui a changé).
 *
 * @package er-bascule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les rôles du relevé → leurs classements.
 */
function er_recoupement_roles() {
	return array(
		'Traduction'                    => 'er_traduction',
		'Révision de la traduction'     => 'er_revision',
		'Sous la direction de'          => 'er_direction',
		'Préface, présentation'         => 'er_preface',
		'Édition établie, notes, choix' => 'er_edition',
		'Illustrations'                 => 'er_illustration',
		'Contributions'                 => 'er_contribution',
	);
}

/**
 * Lit un fichier du relevé : ses lignes, en colonnes, sans les commentaires. Les colonnes ne sont pas rognées : un
 * morceau de titre à retirer commence par une espace.
 *
 * @param string $fichier Le fichier.
 * @param int    $n       Le nombre de colonnes.
 * @return array[]
 */
function er_recoupement_lire( $fichier, $n ) {
	$lignes = array();
	foreach ( file( $fichier, FILE_IGNORE_NEW_LINES ) as $ligne ) {
		$ligne = rtrim( $ligne, "\r" );
		if ( '' === trim( $ligne ) || '#' === $ligne[0] ) {
			continue;
		}
		$c = explode( "\t", $ligne );
		if ( count( $c ) >= $n && (int) $c[0] ) {
			$c[0]     = (int) $c[0];
			$lignes[] = array_slice( array_pad( $c, $n, '' ), 0, $n );
		}
	}
	return $lignes;
}

/**
 * Les rôles du relevé, livre par livre : identifiant → classement → personnes.
 *
 * @param string $fichier Le fichier.
 * @return array<int,array<string,string[]>>
 */
function er_recoupement_roles_par_livre( $fichier ) {
	$roles = er_recoupement_roles();
	$livres = array();
	foreach ( er_recoupement_lire( $fichier, 3 ) as list( $id, $role, $personne ) ) {
		$personne = trim( $personne );
		if ( isset( $roles[ $role ] ) && '' !== $personne ) {
			$livres[ $id ][ $roles[ $role ] ][] = $personne;
		}
	}
	return $livres;
}

/**
 * Remplace un morceau d'un texte, sans redoubler une correction déjà faite (« Zeytounian » → « Zeytounian-Beloüs »
 * ne touche pas un « Zeytounian-Beloüs » déjà là).
 *
 * @param string $texte Le texte.
 * @param string $avant Le morceau à remplacer.
 * @param string $apres Ce qui le remplace.
 * @param int    $n     Reçoit le nombre de remplacements.
 */
function er_recoupement_remplacer( $texte, $avant, $apres, &$n = 0 ) {
	$n = 0;
	if ( '' === $avant ) {
		return $texte;
	}
	$garde = '' !== $apres && false !== strpos( $apres, $avant ) ? "\u{E000}" : '';
	if ( $garde ) {
		$texte = str_replace( $apres, $garde, $texte );
	}
	$texte = str_replace( $avant, $apres, $texte, $n );
	return $garde ? str_replace( $garde, $apres, $texte ) : $texte;
}

/**
 * L'éditeur du classement qui porte ce nom ou une de ses variantes (er_editeur_cle : « Ginkgo » pour « Ginkgo éditeur »,
 * « Cerf » pour « Éditions du Cerf ») : son identifiant, sinon le nom, que wp_set_object_terms() créera.
 *
 * @param string $nom Le nom.
 * @return int|string
 */
function er_recoupement_editeur( $nom ) {
	static $cles = null;
	if ( null === $cles ) {
		$cles   = array();
		$termes = get_terms(
			array(
				'taxonomy'   => 'er_editeur',
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		foreach ( is_array( $termes ) ? $termes : array() as $t ) {
			$cle = er_editeur_cle( html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) );
			if ( '' !== $cle && ! isset( $cles[ $cle ] ) ) {
				$cles[ $cle ] = (int) $t->term_id;
			}
		}
	}
	$cle = er_editeur_cle( $nom );
	return isset( $cles[ $cle ] ) ? $cles[ $cle ] : $nom;
}

/**
 * Une valeur de la fiche peut-elle recevoir celle du relevé ? Si « avant » est vide, seulement quand la fiche n'a rien ;
 * sinon, seulement quand elle porte cette valeur-là (une date ou un éditeur faux, et rien d'autre).
 *
 * @param string $actuel Ce que la fiche porte.
 * @param string $avant  La valeur fausse, ou rien.
 * @param bool   $vide   La fiche est-elle sans valeur (une année sans quatre chiffres, des pages à zéro) ?
 */
function er_recoupement_peut_poser( $actuel, $avant, $vide ) {
	return '' === $avant ? $vide : trim( (string) $actuel ) === trim( $avant );
}

/**
 * Un ISBN valable, en treize chiffres (un ISBN à dix chiffres est converti) ; '' si la clé est fausse.
 *
 * @param string $valeur L'ISBN, avec ou sans tirets.
 */
function er_recoupement_isbn13( $valeur ) {
	$t = strtoupper( preg_replace( '/[^0-9Xx]/', '', (string) $valeur ) );
	if ( preg_match( '/^\d{9}[\dX]$/', $t ) ) {
		$somme = 0;
		for ( $i = 0; $i < 9; $i++ ) {
			$somme += (int) $t[ $i ] * ( 10 - $i );
		}
		$r = ( 11 - $somme % 11 ) % 11;
		if ( ( 10 === $r ? 'X' : (string) $r ) !== $t[9] ) {
			return '';
		}
		$t = '978' . substr( $t, 0, 9 );
		$t = $t . er_recoupement_cle13( $t );
	}
	if ( ! preg_match( '/^97[89]\d{10}$/', $t ) || er_recoupement_cle13( $t ) !== $t[12] ) {
		return '';
	}
	return $t;
}

/**
 * La clé d'un EAN-13, d'après ses douze premiers chiffres.
 *
 * @param string $t Au moins douze chiffres.
 */
function er_recoupement_cle13( $t ) {
	$somme = 0;
	for ( $i = 0; $i < 12; $i++ ) {
		$somme += (int) $t[ $i ] * ( $i % 2 ? 3 : 1 );
	}
	return (string) ( ( 10 - $somme % 10 ) % 10 );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Le relevé des rôles recoupé : wp er recoupement [appliquer].
	 *
	 * @param array $args « appliquer », ou rien.
	 */
	WP_CLI::add_command(
		'er recoupement',
		function ( $args ) {
			$appliquer = isset( $args[0] ) && 'appliquer' === $args[0];
			$dossier   = dirname( __DIR__ ) . '/donnees/';
			foreach ( array( 'roles-recoupes.tsv', 'corrections-fiches.tsv' ) as $f ) {
				if ( ! is_readable( $dossier . $f ) ) {
					WP_CLI::error( 'Fichier introuvable : ' . $dossier . $f );
				}
			}
			if ( ! function_exists( 'er_poser_personnes' ) ) {
				WP_CLI::error( 'L’extension de la librairie n’est pas active (er_poser_personnes).' );
			}
			$noms_roles  = array_flip( er_recoupement_roles() );
			$corrections = er_recoupement_lire( $dossier . 'corrections-fiches.tsv', 4 );

			// 1. Les personnes déjà sur le site sous un nom fautif ou tronqué : renommées (l'identifiant ne change pas, les
			// rôles déjà posés suivent), avant les rôles, pour que le relevé les retrouve.
			foreach ( $corrections as list( $id, $champ, $avant, $apres ) ) {
				if ( 'personne' !== $champ ) {
					continue;
				}
				$t = get_term_by( 'name', $avant, 'auteur' );
				if ( ! $t ) {
					WP_CLI::log( '  personne : « ' . $avant . ' » introuvable (déjà renommée ?)' );
					continue;
				}
				if ( get_term_by( 'name', $apres, 'auteur' ) ) {
					WP_CLI::warning( '« ' . $apres . ' » existe déjà : « ' . $avant . ' » est laissé, à réunir à la main.' );
					continue;
				}
				WP_CLI::log( '  personne : « ' . $avant . ' » → « ' . $apres . ' »' );
				if ( $appliquer ) {
					wp_update_term( $t->term_id, 'auteur', array( 'name' => $apres ) );
					if ( function_exists( 'er_personnes_cles_perimer' ) ) {
						er_personnes_cles_perimer();
					}
				}
			}

			// Les éditeurs mal écrits dans le classement (« Masha Publishing », « Barthillat ») : renommés, ou réunis à
			// celui qui porte déjà le bon nom ; la méta des livres suit, pour qu'un relevé futur ne les recrée pas.
			foreach ( $corrections as list( $id, $champ, $avant, $apres ) ) {
				if ( 'editeur-nom' !== $champ ) {
					continue;
				}
				$t = get_term_by( 'name', $avant, 'er_editeur' );
				if ( ! $t ) {
					$t = get_term_by( 'name', esc_html( $avant ), 'er_editeur' );
				}
				if ( ! $t ) {
					WP_CLI::log( '  éditeur : « ' . $avant . ' » introuvable (déjà renommé ?)' );
					continue;
				}
				$chef = get_term_by( 'name', $apres, 'er_editeur' );
				WP_CLI::log( '  éditeur : « ' . $avant . ' » → « ' . $apres . ' »' . ( $chef ? ' (réuni à l’éditeur qui existe)' : '' ) );
				if ( ! $appliquer ) {
					continue;
				}
				$objets = get_objects_in_term( $t->term_id, 'er_editeur' );
				$objets = is_array( $objets ) ? array_map( 'intval', $objets ) : array();
				if ( $chef ) {
					foreach ( $objets as $o ) {
						wp_set_object_terms( $o, array( (int) $chef->term_id ), 'er_editeur', true );
					}
					wp_delete_term( $t->term_id, 'er_editeur' );
				} else {
					wp_update_term(
						$t->term_id,
						'er_editeur',
						array(
							'name' => $apres,
							'slug' => sanitize_title( $apres ),
						)
					);
				}
				foreach ( $objets as $o ) {
					$meta = (string) get_post_meta( $o, 'er_editeur', true );
					if ( '' !== $meta ) {
						update_post_meta( $o, 'er_editeur', er_recoupement_remplacer( $meta, $avant, $apres ) );
					}
				}
			}

			// 2. Les rôles.
			$changes   = 0;
			$pareils   = 0;
			$nouvelles = array();
			$otes      = array();
			$absents   = array();
			foreach ( er_recoupement_roles_par_livre( $dossier . 'roles-recoupes.tsv' ) as $id => $roles ) {
				if ( 'product' !== get_post_type( $id ) ) {
					$absents[] = $id;
					continue;
				}
				foreach ( $roles as $taxonomie => $personnes ) {
					if ( ! taxonomy_exists( $taxonomie ) ) {
						WP_CLI::warning( 'Classement inconnu : ' . $taxonomie );
						continue;
					}
					$avant   = wp_list_pluck( er_personnes_du_livre( $id, $taxonomie ), 'name' );
					$trouves = array();
					foreach ( $personnes as $p ) {
						$t = er_personne_trouver( $p );
						if ( ! $t ) {
							$nouvelles[ $p ] = true;
						}
						$trouves[] = $t ? $t->name : $p;
					}
					$a = $avant;
					$b = $trouves;
					sort( $a );
					sort( $b );
					if ( $a === $b ) {
						++$pareils;
						continue;
					}
					++$changes;
					foreach ( array_diff( $avant, $trouves ) as $ote ) {
						$otes[ $ote ] = true;
					}
					WP_CLI::log( '  ' . $id . ' « ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) . ' », ' . $noms_roles[ $taxonomie ] . ' : ' . ( $avant ? implode( ', ', $avant ) : '—' ) . ' → ' . implode( ', ', $trouves ) );
					if ( $appliquer ) {
						er_poser_personnes( $id, $taxonomie, $personnes );
					}
				}
			}
			WP_CLI::log( ( $appliquer ? 'Rôles posés' : 'Rôles à poser' ) . ' : ' . $changes . ' changements ; ' . $pareils . ' déjà justes.' );
			if ( $nouvelles ) {
				WP_CLI::log( count( $nouvelles ) . ' personnes nouvelles' . ( $appliquer ? ', créées' : ', à créer' ) . ' : ' . implode( ', ', array_keys( $nouvelles ) ) );
			}

			// Les personnes que le relevé retire d'un rôle : celles qui n'ont plus aucun livre sont signalées, pas effacées.
			if ( $otes ) {
				$seules = array();
				foreach ( array_keys( $otes ) as $nom ) {
					$t = get_term_by( 'name', $nom, 'auteur' );
					if ( $t && $appliquer ) {
						wp_cache_delete( 'er_personnes_livres', 'er' );
						$l = er_personne_livres( $t );
						if ( ! $l['tous'] && ! $l['autour'] ) {
							$seules[] = $nom;
						}
					}
				}
				WP_CLI::log( count( $otes ) . ' personnes retirées d’au moins un rôle : ' . implode( ', ', array_keys( $otes ) ) );
				if ( $seules ) {
					WP_CLI::log( count( $seules ) . ' n’ont plus aucun livre (leur page est vide, à supprimer à la main si elle ne sert à rien) : ' . implode( ', ', $seules ) );
				}
			}

			// 3. Les fiches : textes, titres, auteurs, éditeurs, années, pages.
			$faits       = 0;
			$introuvable = array();
			$auteurs     = array(); // Livre → les auteurs posés par ce passage : un deuxième auteur s'y ajoute.
			foreach ( $corrections as list( $id, $champ, $avant, $apres ) ) {
				if ( 'personne' === $champ || 'editeur-nom' === $champ ) {
					continue;
				}
				$post = get_post( $id );
				if ( ! $post || 'product' !== $post->post_type ) {
					$absents[] = $id;
					continue;
				}
				if ( 'auteur' === $champ ) {
					$deja = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
					$deja = is_wp_error( $deja ) ? array() : $deja;
					if ( $deja && ( ! isset( $auteurs[ $id ] ) || array_diff( $deja, $auteurs[ $id ] ) ) ) {
						$introuvable[] = $id . ' auteur (déjà : ' . implode( ', ', $deja ) . ')';
						continue;
					}
					$t = er_personne_trouver( $apres );
					WP_CLI::log( '  ' . $id . ' auteur : ' . $apres . ( $t ? '' : ' (personne nouvelle)' ) );
					if ( $appliquer ) {
						$t = er_personne_par_nom( $apres, true );
						if ( $t ) {
							wp_set_object_terms( $id, array( (int) $t->term_id ), 'auteur', (bool) $deja );
							$auteurs[ $id ][] = $t->name;
						}
					}
					++$faits;
					continue;
				}
				if ( 'editeur' === $champ ) {
					$ed     = er_editeur_du_livre( $id );
					$noms   = array_map( fn( $t ) => html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ), $ed['termes'] );
					$actuel = $noms ? implode( ' / ', $noms ) : trim( wp_strip_all_tags( html_entity_decode( $ed['texte'], ENT_QUOTES, 'UTF-8' ) ) );
					if ( ! er_recoupement_peut_poser( '' === $avant ? $actuel : er_editeur_cle( $actuel ), '' === $avant ? '' : er_editeur_cle( $avant ), '' === $actuel ) ) {
						$introuvable[] = $id . ' éditeur (déjà : ' . $actuel . ')';
						continue;
					}
					WP_CLI::log( '  ' . $id . ' éditeur : ' . ( '' === $actuel ? '—' : $actuel ) . ' → ' . $apres );
					if ( $appliquer ) {
						$termes = array_map( 'er_recoupement_editeur', preg_split( '/\s+\/\s+/u', $apres ) );
						wp_set_object_terms( $id, $termes, 'er_editeur', false );
						update_post_meta( $id, 'er_editeur', $apres );
					}
					++$faits;
					continue;
				}
				if ( 'nature' === $champ ) {
					$actuel = (string) get_post_meta( $id, 'er_nature', true );
					if ( ! er_recoupement_peut_poser( $actuel, $avant, '' === $actuel ) || $actuel === $apres ) {
						$introuvable[] = $id . ' nature (déjà : ' . $actuel . ')';
						continue;
					}
					WP_CLI::log( '  ' . $id . ' nature : ' . ( '' === $actuel ? '—' : $actuel ) . ' → ' . $apres );
					if ( $appliquer ) {
						update_post_meta( $id, 'er_nature', sanitize_key( $apres ) );
					}
					++$faits;
					continue;
				}
				if ( 'isbn' === $champ ) {
					$p      = wc_get_product( $id );
					$actuel = $p ? er_isbn( $p ) : '';
					$isbn   = er_recoupement_isbn13( $apres );
					if ( ! $p || '' === $isbn || ! er_recoupement_peut_poser( $actuel, '' === $avant ? '' : er_recoupement_isbn13( $avant ), '' === $actuel ) ) {
						$introuvable[] = $id . ' isbn (' . ( '' === $isbn ? 'clé fausse : ' . $apres : 'déjà : ' . $actuel ) . ')';
						continue;
					}
					WP_CLI::log( '  ' . $id . ' isbn : ' . ( '' === $actuel ? '—' : $actuel ) . ' → ' . $isbn );
					if ( $appliquer ) {
						try {
							$p->set_global_unique_id( $isbn );
						} catch ( Exception $e ) {
							$p->update_meta_data( 'er_isbn', $isbn ); // Un autre livre a ce numéro : même édition, deux fiches.
						}
						$p->save();
					}
					++$faits;
					continue;
				}
				if ( 'annee' === $champ || 'pages' === $champ ) {
					$meta   = 'annee' === $champ ? 'er_annee' : 'er_pages';
					$actuel = trim( (string) get_post_meta( $id, $meta, true ) );
					$vide   = 'annee' === $champ ? ! preg_match( '/\d{4}/', $actuel ) : ! (int) $actuel;
					if ( ! er_recoupement_peut_poser( $actuel, $avant, $vide ) ) {
						$introuvable[] = $id . ' ' . $champ . ' (déjà : ' . $actuel . ')';
						continue;
					}
					WP_CLI::log( '  ' . $id . ' ' . $champ . ' : ' . ( '' === $actuel ? '—' : $actuel ) . ' → ' . $apres );
					if ( $appliquer ) {
						update_post_meta( $id, $meta, 'pages' === $champ ? (int) $apres : sanitize_text_field( $apres ) );
						if ( function_exists( 'wc_delete_product_transients' ) ) {
							wc_delete_product_transients( $id );
						}
					}
					++$faits;
					continue;
				}
				$champs = 'titre' === $champ ? array( 'post_title' ) : array( 'post_content', 'post_excerpt' );
				$maj    = array();
				$total  = 0;
				foreach ( $champs as $c ) {
					$nouveau = er_recoupement_remplacer( $post->$c, $avant, $apres, $n );
					if ( ! $n ) {
						// Le texte enregistré peut porter des entités (&amp;, &#8211;) : on essaie aussi sous cette forme.
						$nouveau = er_recoupement_remplacer( $post->$c, esc_html( $avant ), esc_html( $apres ), $n );
					}
					if ( $n ) {
						$maj[ $c ] = $nouveau;
						$total    += $n;
					}
				}
				if ( ! $maj ) {
					$introuvable[] = $id . ' ' . $champ . ' « ' . $avant . ' »';
					continue;
				}
				WP_CLI::log( '  ' . $id . ' ' . $champ . ' : « ' . $avant . ' » → « ' . $apres . ' » (' . $total . ')' );
				if ( $appliquer ) {
					wp_update_post( array( 'ID' => $id ) + wp_slash( $maj ) );
					$post = get_post( $id );
				}
				++$faits;
			}
			if ( $appliquer && function_exists( 'er_index_contenu' ) ) {
				foreach ( array_unique( array_column( $corrections, 0 ) ) as $id ) {
					er_index_contenu( $id );
				}
			}
			WP_CLI::log( ( $appliquer ? 'Fiches corrigées' : 'Corrections à faire (« wp er recoupement appliquer » pour tout poser)' ) . ' : ' . $faits . '.' );
			if ( $introuvable ) {
				WP_CLI::log( count( $introuvable ) . ' laissées (introuvables ou déjà faites) : ' . implode( ' ; ', $introuvable ) );
			}
			if ( $absents ) {
				WP_CLI::log( 'Identifiants qui ne sont plus des livres : ' . implode( ', ', array_unique( $absents ) ) );
			}
		}
	);
}
