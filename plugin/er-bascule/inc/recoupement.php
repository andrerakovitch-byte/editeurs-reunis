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
 *   seulement sur un livre qui n'en a pas).
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
			$noms_roles = array_flip( er_recoupement_roles() );

			// 1. Les rôles.
			$changes   = 0;
			$pareils   = 0;
			$nouvelles = array();
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

			// 2. Les fiches : textes, titres, auteurs.
			$faits       = 0;
			$introuvable = array();
			foreach ( er_recoupement_lire( $dossier . 'corrections-fiches.tsv', 4 ) as list( $id, $champ, $avant, $apres ) ) {
				$post = get_post( $id );
				if ( ! $post || 'product' !== $post->post_type ) {
					$absents[] = $id;
					continue;
				}
				if ( 'auteur' === $champ ) {
					$deja = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
					if ( $deja && ! is_wp_error( $deja ) ) {
						$introuvable[] = $id . ' auteur (déjà : ' . implode( ', ', $deja ) . ')';
						continue;
					}
					$t = er_personne_trouver( $apres );
					WP_CLI::log( '  ' . $id . ' auteur : ' . $apres . ( $t ? '' : ' (personne nouvelle)' ) );
					if ( $appliquer ) {
						$t = er_personne_par_nom( $apres, true );
						if ( $t ) {
							wp_set_object_terms( $id, array( (int) $t->term_id ), 'auteur' );
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
				foreach ( array_unique( array_column( er_recoupement_lire( $dossier . 'corrections-fiches.tsv', 4 ), 0 ) ) as $id ) {
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
