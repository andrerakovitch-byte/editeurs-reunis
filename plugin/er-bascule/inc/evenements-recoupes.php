<?php
/**
 * Les rencontres du centre recoupées (25 nov. 2026) : qui intervient et qui anime, les livres présentés, les auteurs
 * dont on parle et les sujets, d'après le texte de chaque rencontre et le catalogue de la librairie.
 *
 * - wp er evenements releve : ce que chaque rencontre porte aujourd'hui, une ligne JSON par rencontre (EVT), avec les
 *   sujets (SUJ) et les personnes de la librairie (PER), de quoi la relire.
 * - wp er evenements recouper [appliquer] : pose donnees/evenements-recoupes.tsv (identifiant, champ, valeur). Une
 *   rencontre qui y paraît est relue : ses intervenants, ses livres, ses auteurs et ses sujets sont remplacés par ceux
 *   du fichier, et ses étiquettes marquées relues (er_etiquettes_relues), que rien ne devine plus par-dessus.
 *   Champs : intervenant (une personne qui parle), animation (celle qui anime : sa ligne à part, ses livres ne
 *   s'ajoutent pas d'eux-mêmes), livre (l'identifiant d'une fiche de la librairie), auteur (une personne dont on parle,
 *   qui a sa page : son nom ou une de ses graphies), sujet (le nom d'un sujet du site).
 *
 * @package er-bascule
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le texte d'un contenu, sans balises, en une ligne.
 *
 * @param string $html Le contenu.
 */
function er_evt_recoupe_texte( $html ) {
	$texte = wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br />', '</li>', '</h2>', '</h3>' ), ' ¶ ', (string) $html ) );
	$texte = html_entity_decode( $texte, ENT_QUOTES, 'UTF-8' );
	return trim( preg_replace( '/\s+/u', ' ', $texte ) );
}

/**
 * Le fichier des rencontres recoupées : identifiant → [ champ → [ valeurs ] ].
 *
 * @param string $fichier Le chemin.
 */
function er_evt_recoupe_lire( $fichier ) {
	$par = array();
	foreach ( file( $fichier, FILE_IGNORE_NEW_LINES ) as $ligne ) {
		if ( '' === trim( $ligne ) || '#' === $ligne[0] ) {
			continue;
		}
		$c = array_map( 'trim', explode( "\t", $ligne ) );
		if ( count( $c ) < 3 || ! ctype_digit( $c[0] ) || '' === $c[2] ) {
			continue;
		}
		$par[ (int) $c[0] ][ $c[1] ][] = $c[2];
	}
	return $par;
}

/**
 * Le terme d'intervenant d'une personne : le miroir de sa page d'auteur, ou un intervenant seul, sans page.
 *
 * @param string $nom       Le nom, tel que la rencontre l'écrit.
 * @param bool   $appliquer Créer ce qui manque.
 * @return array [ le terme ou null, « page », « nouveau » ou « déjà » ].
 */
function er_evt_recoupe_intervenant( $nom, $appliquer ) {
	$auteur = function_exists( 'er_personne_trouver' ) ? er_personne_trouver( $nom ) : null;
	if ( $auteur ) {
		return array( $appliquer ? er_personnes_miroir( $auteur, 'er_intervenant', $nom ) : $auteur, 'page' );
	}
	foreach ( get_terms( array( 'taxonomy' => 'er_intervenant', 'hide_empty' => false, 'name' => $nom ) ) as $t ) {
		return array( $t, 'déjà' );
	}
	if ( ! $appliquer ) {
		return array( null, 'nouveau' );
	}
	$cree = wp_insert_term( $nom, 'er_intervenant' );
	return array( is_wp_error( $cree ) ? null : get_term( (int) $cree['term_id'], 'er_intervenant' ), 'nouveau' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'er evenements releve',
		function () {
			foreach ( get_terms( array( 'taxonomy' => 'er_sujet', 'hide_empty' => false, 'lang' => '' ) ) as $s ) {
				WP_CLI::log( 'SUJ ' . $s->slug . ' | ' . $s->name . ' | ' . $s->count );
			}
			foreach ( get_terms( array( 'taxonomy' => 'auteur', 'hide_empty' => false, 'lang' => '' ) ) as $a ) {
				WP_CLI::log( 'PER ' . $a->term_id . ' | ' . $a->name . ' | ' . implode( ' ; ', er_autres_graphies( $a->term_id ) ) . ' | ' . $a->count );
			}
			$noms = fn( $id, $tax ) => array_values( array_map( fn( $t ) => $t->name, wp_get_post_terms( $id, $tax ) ) );
			foreach ( get_posts( array( 'post_type' => ER_EVT, 'post_status' => 'publish', 'numberposts' => -1, 'lang' => '', 'orderby' => 'ID', 'order' => 'ASC' ) ) as $p ) {
				$inter = array();
				foreach ( wp_get_post_terms( $p->ID, 'er_intervenant' ) as $t ) {
					$page    = er_evt_personne( $t );
					$inter[] = $t->name . ( $page ? '' : ' (sans page)' );
				}
				$ligne = array(
					'id'        => $p->ID,
					'titre'     => html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' ),
					'debut'     => (string) get_post_meta( $p->ID, 'er_debut', true ),
					'forme'     => (string) get_post_meta( $p->ID, 'er_forme', true ),
					'langue'    => (string) get_post_meta( $p->ID, 'er_langue', true ),
					'cycle'     => $noms( $p->ID, 'er_cycle' ),
					'chapo'     => er_evt_recoupe_texte( $p->post_excerpt ),
					'texte'     => er_evt_recoupe_texte( $p->post_content ),
					'inter'     => $inter,
					'animation' => (string) get_post_meta( $p->ID, 'er_animation', true ),
					'auteurs'   => $noms( $p->ID, 'auteur' ),
					'sujets'    => $noms( $p->ID, 'er_sujet' ),
					'livres'    => (string) get_post_meta( $p->ID, 'er_livres', true ),
					'videos'    => count( array_filter( preg_split( '/\R/', (string) get_post_meta( $p->ID, 'er_videos', true ) ) ) ),
					'relues'    => (bool) get_post_meta( $p->ID, 'er_etiquettes_relues', true ),
				);
				WP_CLI::log( 'EVT ' . wp_json_encode( $ligne, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
			}
		}
	);

	WP_CLI::add_command(
		'er evenements recouper',
		function ( $args ) {
			$appliquer = in_array( 'appliquer', $args, true );
			$fichier   = dirname( __DIR__ ) . '/donnees/evenements-recoupes.tsv';
			if ( ! is_readable( $fichier ) ) {
				WP_CLI::error( 'Le fichier est introuvable : ' . $fichier );
			}
			$faites   = 0;
			$manques  = array();
			$nouveaux = array();
			foreach ( er_evt_recoupe_lire( $fichier ) as $id => $champs ) {
				if ( ER_EVT !== get_post_type( $id ) ) {
					$manques[] = '#' . $id . ' : pas une rencontre';
					continue;
				}
				$titre = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );

				// Les intervenants, puis ceux qui animent : un seul classement, la ligne « Animation » les distingue.
				$intervenants = array();
				$animation    = array();
				foreach ( array( 'intervenant', 'animation' ) as $role ) {
					foreach ( $champs[ $role ] ?? array() as $nom ) {
						list( $t, $etat ) = er_evt_recoupe_intervenant( $nom, $appliquer );
						if ( 'nouveau' === $etat ) {
							$nouveaux[ $nom ] = true;
						}
						if ( $t ) {
							$intervenants[] = $t->slug;
							if ( 'animation' === $role ) {
								$animation[] = $t->name;
							}
						}
					}
				}

				// Les livres : des fiches publiées de la librairie, rien d'autre.
				$livres = array();
				foreach ( $champs['livre'] ?? array() as $ref ) {
					$p = function_exists( 'wc_get_product' ) ? wc_get_product( (int) $ref ) : null;
					if ( $p && 'publish' === $p->get_status() ) {
						$livres[] = (string) $p->get_id();
					} else {
						$manques[] = '#' . $id . ' ' . $titre . ' : livre ' . $ref . ' introuvable';
					}
				}

				// Les auteurs dont on parle : des personnes qui ont leur page.
				$auteurs = array();
				foreach ( $champs['auteur'] ?? array() as $nom ) {
					$a = function_exists( 'er_personne_trouver' ) ? er_personne_trouver( $nom ) : null;
					if ( $a ) {
						$auteurs[] = (int) $a->term_id;
					} else {
						$manques[] = '#' . $id . ' ' . $titre . ' : auteur « ' . $nom . ' » sans page';
					}
				}

				// Les sujets : ceux du site.
				$sujets = array();
				foreach ( $champs['sujet'] ?? array() as $nom ) {
					$s = get_term_by( 'name', $nom, 'er_sujet' );
					if ( $s ) {
						$sujets[] = (int) $s->term_id;
					} else {
						$manques[] = '#' . $id . ' ' . $titre . ' : sujet « ' . $nom . ' » inconnu';
					}
				}

				WP_CLI::log(
					sprintf(
						'#%d %s · avec %s%s · livres %s · auteurs %d · sujets %d',
						$id,
						$titre,
						implode( ', ', $champs['intervenant'] ?? array( '—' ) ),
						isset( $champs['animation'] ) ? ' · animation ' . implode( ', ', $champs['animation'] ) : '',
						$livres ? implode( ',', $livres ) : '—',
						count( $auteurs ),
						count( $sujets )
					)
				);
				if ( $appliquer ) {
					wp_set_object_terms( $id, $intervenants, 'er_intervenant' );
					update_post_meta( $id, 'er_animation', implode( ',', $animation ) );
					update_post_meta( $id, 'er_livres', implode( ',', $livres ) );
					wp_set_object_terms( $id, array_values( array_unique( $auteurs ) ), 'auteur' );
					wp_set_object_terms( $id, array_values( array_unique( $sujets ) ), 'er_sujet' );
					update_post_meta( $id, 'er_etiquettes_relues', 1 );
				}
				++$faites;
			}
			if ( $nouveaux ) {
				WP_CLI::log( sprintf( 'Intervenants sans page (%d) : %s', count( $nouveaux ), implode( ' ; ', array_keys( $nouveaux ) ) ) );
			}
			if ( $manques ) {
				WP_CLI::log( 'À voir : ' . implode( ' ; ', $manques ) );
			}
			WP_CLI::success( sprintf( '%d rencontres %s.', $faites, $appliquer ? 'recoupées' : 'à recouper (rien n’est écrit : ajouter « appliquer »)' ) );
		}
	);
}
