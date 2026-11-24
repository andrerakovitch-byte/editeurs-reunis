<?php
/**
 * Les personnes : les réunir et les rattacher (24 nov. 2026, après l'audit des recoupements, inc/audit.php).
 *
 * Chez Wix, l'auteur d'un livre n'était qu'un texte : les graphies russes et les initiales y ont fait plusieurs personnes
 * d'une seule (« Alexandre Soljenitsyne », « А. Солженицын », « А.И Солженицын »), et deux personnes d'un seul nom
 * (« Anton Tchekhov et Lydia Mizinova »). Et les articles, les rencontres, les pages nomment des personnes sans y être
 * rattachés : leur page ne les montre pas.
 *
 * - wp er personnes fusionner [appliquer] : réunit les doublons de la liste er_personnes_fusions() (relue par André),
 *   sépare les noms à deux personnes ; sans « appliquer », dit seulement ce qui serait fait.
 * - wp er personnes rattacher [appliquer] : rattache à un contenu les personnes qu'il nomme dans son titre ou son chapô,
 *   ou au moins deux fois dans son texte ; liste celles qu'il ne nomme qu'une fois, à voir à la main.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les doublons à réunir : la personne gardée (et, au besoin, son nom corrigé), puis celles qui s'y fondent. Seulement
 * les cas sûrs ; les douteux attendent l'avis d'André (Anne et Anita Davidenkoff, « A. Tolstoï » et Alexis
 * Konstantinovitch, Nicolas et Nikita Struve).
 *
 * @return array[]
 */
function er_personnes_fusions() {
	return array(
		array( 'garder' => 'Alexandre Soljenitsyne', 'fondre' => array( 'А. Солженицын', 'А.И Солженицын' ) ),
		array( 'garder' => 'Fiodor Dostoïevski', 'fondre' => array( 'Fedor Dostoïevski' ) ),
		array( 'garder' => 'Léon Tolstoï', 'fondre' => array( 'Лев Толстой' ) ),
		array( 'garder' => 'Nicolas Berdiaev', 'fondre' => array( 'Николай Бердяев' ) ),
		array( 'garder' => 'Pavel Florenski', 'nom' => 'Pavel Florensky', 'fondre' => array( 'Paul Florensky' ) ),
		array( 'garder' => 'Père Serge Boulgakov', 'fondre' => array( 'прот. Сергей Булгаков', 'прот. Сергий Булгаков' ) ),
		array( 'garder' => 'Monseigneur Antoine Bloom', 'fondre' => array( 'Métropolite Antoine Bloom' ) ),
		array( 'garder' => 'Mikhaïl Ossorguine', 'fondre' => array( 'Michel Ossorguine' ) ),
		array( 'garder' => 'Nadejda Teffi', 'fondre' => array( 'Н.А. Тэффи' ) ),
		array( 'garder' => 'Nicolas Ross', 'fondre' => array( 'Николай Росс' ) ),
		array( 'garder' => 'Pierre Struve', 'fondre' => array( 'П.Б. Струве' ) ),
		array( 'garder' => 'Nicolas Zernov', 'fondre' => array( 'Николай Зернов' ) ),
		array( 'garder' => 'P. Jean Meyendorff', 'fondre' => array( 'Прот. Иоанн Мейендорф' ) ),
		array( 'garder' => 'Simon Frank', 'fondre' => array( 'Семен Франк' ) ),
		array( 'garder' => 'Yuri Felshtinsky', 'fondre' => array( 'Ю. Фельштинский' ) ),
		// « Георгий Фeдотов » porte un « e » latin : le nom est corrigé.
		array( 'garder' => 'Георгий Фeдотов', 'nom' => 'Георгий Федотов', 'fondre' => array( 'Г. Федотов' ) ),
		array( 'garder' => 'Mère Marie Skobtsova', 'fondre' => array( 'Мать Мария Скобцова' ) ),
		array( 'garder' => 'Christine Zeytounian-Beloüs', 'fondre' => array( 'Christine Zeytounian' ) ),
	);
}

/**
 * Les noms qui sont deux personnes : le nom, puis les personnes.
 *
 * @return array<string,string[]>
 */
function er_personnes_separations() {
	return array(
		'Anton Tchekhov et Lydia Mizinova'           => array( 'Anton Tchekhov', 'Lydia Mizinova' ),
		'Marina Tsvétaïeva et Maximilian Volochine' => array( 'Marina Tsvétaïéva', 'Maximilian Volochine' ),
	);
}

/**
 * Une personne par son nom exact ou son identifiant (pas de rapprochement : la liste dit exactement qui).
 *
 * @param string $nom Le nom.
 * @return WP_Term|null
 */
function er_personne_exacte( $nom ) {
	$t = get_term_by( 'name', $nom, 'auteur' );
	if ( ! $t ) {
		$t = get_term_by( 'slug', sanitize_title( $nom ), 'auteur' );
	}
	return $t && ! is_wp_error( $t ) ? $t : null;
}

/**
 * Les classements qui reprennent les personnes sous le même identifiant (traduction, direction, « Autour de »…).
 *
 * @return string[]
 */
function er_personnes_miroirs() {
	$miroirs = function_exists( 'er_roles_personnes' ) ? array_keys( er_roles_personnes() ) : array();
	$miroirs[] = 'er_autour';
	return array_values( array_filter( $miroirs, 'taxonomy_exists' ) );
}

/**
 * Remplace, sur tous les contenus, un terme par d'autres (dans le même classement).
 *
 * @param int    $ancien    Le terme remplacé.
 * @param int[]  $nouveaux  Les termes qui le remplacent.
 * @param string $taxonomie Le classement.
 * @return int Le nombre de contenus.
 */
function er_personnes_remplacer( $ancien, $nouveaux, $taxonomie ) {
	$objets = get_objects_in_term( $ancien, $taxonomie );
	foreach ( is_wp_error( $objets ) ? array() : $objets as $objet ) {
		wp_set_object_terms( (int) $objet, array_map( 'intval', $nouveaux ), $taxonomie, true );
		wp_remove_object_terms( (int) $objet, (int) $ancien, $taxonomie );
	}
	return is_wp_error( $objets ) ? 0 : count( $objets );
}

/**
 * Le terme miroir d'une personne dans un classement (même identifiant), créé au besoin.
 *
 * @param WP_Term $personne  La personne.
 * @param string  $taxonomie Le classement.
 */
function er_personnes_miroir( $personne, $taxonomie ) {
	$m = get_term_by( 'slug', $personne->slug, $taxonomie );
	if ( ! $m ) {
		$cree = wp_insert_term( $personne->name, $taxonomie, array( 'slug' => $personne->slug ) );
		$m    = is_wp_error( $cree ) ? null : get_term( (int) $cree['term_id'], $taxonomie );
	}
	return $m && ! is_wp_error( $m ) ? $m : null;
}

/**
 * Ajoute des noms aux « Autres graphies » d'une personne, et des identifiants à ses anciens (pour les adresses).
 *
 * @param WP_Term  $personne La personne.
 * @param string[] $noms     Les noms.
 * @param string[] $slugs    Les identifiants.
 */
function er_personnes_retenir( $personne, $noms, $slugs ) {
	$graphies = er_autres_graphies( $personne->term_id );
	foreach ( $noms as $nom ) {
		if ( $nom !== $personne->name && ! in_array( $nom, $graphies, true ) ) {
			$graphies[] = $nom;
		}
	}
	update_term_meta( $personne->term_id, 'er_graphies', implode( "\n", $graphies ) );
	$anciens = array_filter( explode( ',', (string) get_term_meta( $personne->term_id, 'er_anciens', true ) ) );
	$anciens = array_values( array_unique( array_merge( $anciens, $slugs ) ) );
	update_term_meta( $personne->term_id, 'er_anciens', ',' . implode( ',', $anciens ) . ',' );
}

/**
 * Fond une personne dans une autre : ses livres, ses rôles (traductions, directions, « Autour de »), ses contenus,
 * son intervenant, sa fiche (ce qui manque à la personne gardée), ses graphies et son adresse.
 *
 * @param WP_Term $garde La personne gardée.
 * @param WP_Term $fondu La personne fondue.
 * @return string Ce qui a été fait.
 */
function er_personne_fondre( $garde, $fondu ) {
	$faits = array();
	$n     = er_personnes_remplacer( $fondu->term_id, array( $garde->term_id ), 'auteur' );
	$faits[] = $n . ' contenus';
	foreach ( er_personnes_miroirs() as $taxonomie ) {
		$m = get_term_by( 'slug', $fondu->slug, $taxonomie );
		if ( ! $m ) {
			continue;
		}
		$g = er_personnes_miroir( $garde, $taxonomie );
		if ( $g ) {
			$faits[] = er_personnes_remplacer( $m->term_id, array( $g->term_id ), $taxonomie ) . ' en ' . $taxonomie;
		}
		wp_delete_term( $m->term_id, $taxonomie );
	}
	// L'intervenant des rencontres, sous l'identifiant de la personne gardée.
	if ( taxonomy_exists( 'er_intervenant' ) ) {
		$i = get_term_by( 'slug', $fondu->slug, 'er_intervenant' );
		if ( $i ) {
			$deja = get_term_by( 'slug', $garde->slug, 'er_intervenant' );
			if ( $deja ) {
				er_personnes_remplacer( $i->term_id, array( $deja->term_id ), 'er_intervenant' );
				wp_delete_term( $i->term_id, 'er_intervenant' );
			} else {
				wp_update_term( $i->term_id, 'er_intervenant', array( 'slug' => $garde->slug ) );
			}
			$faits[] = 'intervenant';
		}
	}
	// La fiche : ce que la personne gardée n'a pas.
	foreach ( get_term_meta( $fondu->term_id ) as $cle => $valeurs ) {
		if ( in_array( $cle, array( 'er_graphies', 'er_anciens' ), true ) || '' === (string) $valeurs[0] ) {
			continue;
		}
		if ( '' === (string) get_term_meta( $garde->term_id, $cle, true ) ) {
			update_term_meta( $garde->term_id, $cle, maybe_unserialize( $valeurs[0] ) );
			$faits[] = $cle;
		}
	}
	if ( '' === trim( $garde->description ) && '' !== trim( $fondu->description ) ) {
		wp_update_term( $garde->term_id, 'auteur', array( 'description' => $fondu->description ) );
	}
	$anciens = array_filter( explode( ',', (string) get_term_meta( $fondu->term_id, 'er_anciens', true ) ) );
	er_personnes_retenir( $garde, array_merge( array( $fondu->name ), er_autres_graphies( $fondu->term_id ) ), array_merge( array( $fondu->slug ), $anciens ) );
	if ( function_exists( 'er_index_oter' ) ) {
		er_index_oter( 'auteur', $fondu->term_id );
	}
	wp_delete_term( $fondu->term_id, 'auteur' );
	return implode( ', ', $faits );
}

/**
 * Sépare un nom qui est deux personnes : chaque contenu reçoit les deux (retrouvées, ou créées), le nom disparaît ;
 * son adresse mène à la première.
 *
 * @param WP_Term  $double Le nom double.
 * @param string[] $noms   Les personnes.
 * @return string Ce qui a été fait.
 */
function er_personne_separer( $double, $noms ) {
	$personnes = array();
	foreach ( $noms as $nom ) {
		$t = er_personne_par_nom( $nom, true );
		if ( $t ) {
			$personnes[] = $t;
		}
	}
	if ( count( $personnes ) !== count( $noms ) ) {
		return 'personnes introuvables : rien fait';
	}
	$n = er_personnes_remplacer( $double->term_id, wp_list_pluck( $personnes, 'term_id' ), 'auteur' );
	foreach ( er_personnes_miroirs() as $taxonomie ) {
		$m = get_term_by( 'slug', $double->slug, $taxonomie );
		if ( ! $m ) {
			continue;
		}
		$gs = array_filter( array_map( fn( $p ) => er_personnes_miroir( $p, $taxonomie ), $personnes ) );
		er_personnes_remplacer( $m->term_id, wp_list_pluck( $gs, 'term_id' ), $taxonomie );
		wp_delete_term( $m->term_id, $taxonomie );
	}
	er_personnes_retenir( $personnes[0], array(), array( $double->slug ) );
	if ( function_exists( 'er_index_oter' ) ) {
		er_index_oter( 'auteur', $double->term_id );
	}
	wp_delete_term( $double->term_id, 'auteur' );
	return $n . ' contenus, à ' . implode( ' et ', wp_list_pluck( $personnes, 'name' ) );
}

/**
 * Les personnes qu'un texte nomme au moins $fois fois en toutes lettres (le nom pris dans celui d'une institution, d'une
 * rue ou d'un prix ne compte pas : « Centre culturel Alexandre Soljenitsyne »).
 *
 * @param string $texte   Le texte.
 * @param array  $auteurs er_evt_auteurs_connus() : clé → [terme, noms].
 * @param int    $fois    Combien de fois au moins.
 * @return array<int,WP_Term> Les personnes, par identifiant.
 */
function er_personnes_nommees( $texte, $auteurs, $fois = 1 ) {
	static $institutions = null;
	if ( null === $institutions ) {
		$institutions = array_unique( array_map( 'er_cle_recherche', array( 'centre culturel', 'centre', 'fondation', 'prix', 'musée', 'bibliothèque', 'rue', 'avenue', 'boulevard', 'institut', 'maison', 'fonds', 'lycée', 'école', 'université', 'place', 'центр', 'фонд', 'премия', 'улица', 'дом' ) ) );
	}
	$cle     = ' ' . er_cle_recherche( $texte ) . ' ';
	$trouves = array();
	foreach ( $auteurs as $nom => list( $t ) ) {
		$n = substr_count( $cle, ' ' . $nom . ' ' );
		if ( ! $n ) {
			continue;
		}
		foreach ( $institutions as $avant ) {
			$n -= substr_count( $cle, ' ' . $avant . ' ' . $nom . ' ' );
		}
		if ( $n > 0 ) {
			$trouves[ $t->term_id ] = array( $t, ( isset( $trouves[ $t->term_id ] ) ? $trouves[ $t->term_id ][1] : 0 ) + $n );
		}
	}
	$retenus = array();
	foreach ( $trouves as $tid => list( $t, $n ) ) {
		if ( $n >= $fois ) {
			$retenus[ $tid ] = $t;
		}
	}
	return $retenus;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Réunit les doublons de er_personnes_fusions() et sépare les noms de er_personnes_separations() :
	 * wp er personnes fusionner [appliquer].
	 *
	 * @param array $args « appliquer », ou rien pour seulement montrer.
	 */
	WP_CLI::add_command(
		'er personnes fusionner',
		function ( $args ) {
			$appliquer = isset( $args[0] ) && 'appliquer' === $args[0];
			$n         = 0;
			foreach ( er_personnes_fusions() as $f ) {
				$garde = er_personne_exacte( $f['garder'] );
				if ( ! $garde ) {
					WP_CLI::log( '  ' . $f['garder'] . ' : introuvable, rien fait' );
					continue;
				}
				foreach ( $f['fondre'] as $nom ) {
					$fondu = er_personne_exacte( $nom );
					if ( ! $fondu || (int) $fondu->term_id === (int) $garde->term_id ) {
						WP_CLI::log( '  ' . $nom . ' : introuvable (déjà réuni ?)' );
						continue;
					}
					$fait = $appliquer ? er_personne_fondre( $garde, $fondu ) : $fondu->count . ' livres';
					WP_CLI::log( '  ' . $nom . ' → ' . $garde->name . ' : ' . $fait );
					++$n;
				}
				if ( ! empty( $f['nom'] ) && $f['nom'] !== $garde->name ) {
					WP_CLI::log( '  ' . $garde->name . ' s’écrit désormais ' . $f['nom'] );
					if ( $appliquer ) {
						$ancien = $garde->name;
						wp_update_term( $garde->term_id, 'auteur', array( 'name' => $f['nom'] ) );
						$garde = get_term( $garde->term_id, 'auteur' );
						er_personnes_retenir( $garde, array( $ancien ), array() );
					}
				}
				if ( $appliquer && function_exists( 'er_index_terme' ) ) {
					er_index_terme( $garde->term_id, 'auteur' );
				}
			}
			foreach ( er_personnes_separations() as $double => $noms ) {
				$t = er_personne_exacte( $double );
				if ( ! $t ) {
					WP_CLI::log( '  ' . $double . ' : introuvable (déjà séparé ?)' );
					continue;
				}
				WP_CLI::log( '  ' . $double . ' → ' . implode( ' + ', $noms ) . ' : ' . ( $appliquer ? er_personne_separer( $t, $noms ) : $t->count . ' livres' ) );
				++$n;
			}
			delete_transient( 'er_personnes_cles' );
			if ( $appliquer && $n && function_exists( 'er_index_reconstruire' ) ) {
				er_index_reconstruire();
				WP_CLI::log( '  L’index de la recherche est refait.' );
			}
			WP_CLI::success( $appliquer ? $n . ' personnes réunies ou séparées.' : $n . ' à réunir ou séparer. Avec « appliquer », c’est fait.' );
		}
	);

	/**
	 * Rattache à un contenu (article, rencontre, vidéo, exposition, page) les personnes qu'il nomme dans son titre ou
	 * son chapô (la règle de l'étiquetage, er_etiqueter), ou au moins deux fois dans son texte ; liste celles qu'il ne
	 * nomme qu'une fois, à voir. Les contenus aux étiquettes relues à la main (er_etiquettes_relues) ne bougent pas.
	 * Une rencontre ne reçoit pas comme personne son intervenant : il a sa ligne « Avec ».
	 *
	 * wp er personnes rattacher [appliquer]
	 *
	 * @param array $args « appliquer », ou rien pour seulement montrer.
	 */
	WP_CLI::add_command(
		'er personnes rattacher',
		function ( $args ) {
			$appliquer = isset( $args[0] ) && 'appliquer' === $args[0];
			$auteurs   = er_evt_auteurs_connus();
			$types     = array_values( array_filter( array( 'post', defined( 'ER_EVT' ) ? ER_EVT : '', 'er_media', 'er_exposition', 'page' ), 'post_type_exists' ) );
			$poses     = 0;
			$contenus  = 0;
			$a_voir    = array();
			foreach ( get_posts( array( 'post_type' => $types, 'post_status' => 'publish', 'numberposts' => -1, 'lang' => '' ) ) as $p ) {
				if ( get_post_meta( $p->ID, 'er_etiquettes_relues', true ) ) {
					continue;
				}
				$deja = wp_get_post_terms( $p->ID, 'auteur', array( 'fields' => 'ids' ) );
				$deja = is_wp_error( $deja ) ? array() : array_map( 'intval', $deja );
				if ( taxonomy_exists( 'er_intervenant' ) ) {
					foreach ( wp_get_post_terms( $p->ID, 'er_intervenant' ) as $it ) {
						$a = er_evt_personne( $it );
						if ( $a ) {
							$deja[] = (int) $a->term_id;
						}
					}
				}
				$signature = trim( (string) get_post_meta( $p->ID, 'er_signature', true ) );
				$signe     = '' !== $signature ? er_personne_trouver( $signature ) : null;
				if ( $signe ) {
					$deja[] = (int) $signe->term_id;
				}
				$tete  = $p->post_title . ' ' . $p->post_excerpt;
				$corps = $p->post_content . ' ' . get_post_meta( $p->ID, 'er_artistes', true );
				$nouveaux = array();
				foreach ( er_auteurs_cites( $tete, $auteurs ) as list( $t, , $intervient ) ) {
					if ( ! ( $intervient && ER_EVT === $p->post_type ) ) {
						$nouveaux[ $t->term_id ] = $t;
					}
				}
				$nouveaux += er_personnes_nommees( $corps, $auteurs, 2 );
				foreach ( array_keys( $nouveaux ) as $tid ) {
					if ( in_array( (int) $tid, $deja, true ) ) {
						unset( $nouveaux[ $tid ] );
					}
				}
				foreach ( er_personnes_nommees( $corps, $auteurs, 1 ) as $tid => $t ) {
					if ( ! isset( $nouveaux[ $tid ] ) && ! in_array( (int) $tid, $deja, true ) ) {
						$a_voir[] = '    ' . html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' ) . ' · ' . $t->name;
					}
				}
				if ( ! $nouveaux ) {
					continue;
				}
				++$contenus;
				$poses += count( $nouveaux );
				if ( $appliquer ) {
					wp_set_object_terms( $p->ID, array_map( 'intval', array_keys( $nouveaux ) ), 'auteur', true );
				}
				WP_CLI::log( '  ' . html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' ) . ' (' . $p->post_type . ') ← ' . implode( ', ', wp_list_pluck( $nouveaux, 'name' ) ) );
			}
			WP_CLI::log( '' );
			WP_CLI::log( 'Nommées une seule fois dans le texte, à voir à la main (' . count( $a_voir ) . ') :' );
			foreach ( $a_voir as $l ) {
				WP_CLI::log( $l );
			}
			WP_CLI::success( sprintf( $appliquer ? '%d personnes rattachées à %d contenus.' : '%d personnes à rattacher à %d contenus. Avec « appliquer », c’est fait.', $poses, $contenus ) );
		}
	);
}
