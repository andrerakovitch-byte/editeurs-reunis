<?php
/**
 * L'audit des recoupements (24 nov. 2026 : « trouver les informations qui ont besoin d'être recoupées et que Wix ne
 * mettait pas à profit », André, après les traducteurs absents de leur page).
 *
 * wp er audit : en lecture seule, compte dans les données du site ce qui devrait se répondre et ne se répond pas — une
 * signature d'article qui n'est pas reliée à la personne, un intervenant qui a sa page d'auteur sous une autre
 * graphie, une personne nommée dans un texte sans y être rattachée, un préfacier que la fiche du livre ignore, une
 * collection qui n'a pas de page, une référence de livre qui ne mène nulle part. Rien n'est écrit ; le rapport dit
 * quoi faire, et les noms qu'il donne sont publics (ceux du catalogue et du journal).
 *
 * Les noms se rapprochent avec la clé de la recherche (er_cle_recherche : sans accents, transcriptions du russe
 * ramenées à une forme commune) et les « Autres graphies » des auteurs.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Les personnes du site : les auteurs, leurs clés (le nom et ses autres graphies) et un index par clé.
 *
 * @return array{termes: array<int,WP_Term>, cles: array<int,string[]>, index: array<string,int[]>}
 */
function er_audit_personnes() {
	$termes = array();
	$cles   = array();
	$index  = array();
	foreach ( get_terms( array( 'taxonomy' => 'auteur', 'hide_empty' => false ) ) as $t ) {
		$termes[ $t->term_id ] = $t;
		$noms                  = array_merge( array( $t->name ), er_autres_graphies( $t->term_id ) );
		foreach ( $noms as $nom ) {
			$cle = er_cle_recherche( $nom );
			if ( '' === $cle ) {
				continue;
			}
			$cles[ $t->term_id ][]       = $cle;
			$index[ $cle ][ $t->term_id ] = $t->term_id;
		}
	}
	return array(
		'termes' => $termes,
		'cles'   => $cles,
		'index'  => array_map( 'array_values', $index ),
	);
}

/**
 * Les contenus publiés d'un type, dans toutes les langues.
 *
 * @param string $type Le type.
 * @return int[]
 */
function er_audit_ids( $type ) {
	if ( ! post_type_exists( $type ) ) {
		return array();
	}
	$ids = array_map(
		'intval',
		get_posts(
			array(
				'post_type'        => $type,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'lang'             => '',
				'suppress_filters' => false,
			)
		)
	);
	// Les termes et les métas d'un coup, plutôt qu'une requête par contenu.
	update_object_term_cache( $ids, $type );
	update_postmeta_cache( $ids );
	return $ids;
}

/**
 * Le texte d'un contenu, réduit à sa clé : titre, chapô, texte et quelques métas, entre deux espaces.
 *
 * @param int      $id    Le contenu.
 * @param string[] $metas Des métas à y ajouter.
 */
function er_audit_texte( $id, $metas = array() ) {
	$p     = get_post( $id );
	$texte = $p->post_title . ' ' . $p->post_excerpt . ' ' . $p->post_content;
	foreach ( $metas as $m ) {
		$texte .= ' ' . get_post_meta( $id, $m, true );
	}
	return ' ' . er_cle_recherche( $texte ) . ' ';
}

/**
 * Les personnes nommées en toutes lettres dans un texte (prénom et nom, ou une autre graphie de deux mots au moins).
 *
 * @param string $texte   Le texte, en clé (er_audit_texte).
 * @param array  $par_mot Le dernier mot d'une clé → [clé, personne].
 * @return int[] Les personnes.
 */
function er_audit_nommes( $texte, $par_mot ) {
	$trouves = array();
	foreach ( array_unique( explode( ' ', trim( $texte ) ) ) as $mot ) {
		if ( ! isset( $par_mot[ $mot ] ) ) {
			continue;
		}
		foreach ( $par_mot[ $mot ] as list( $cle, $tid ) ) {
			$n = substr_count( $texte, ' ' . $cle . ' ' );
			if ( ! $n ) {
				continue;
			}
			// Le nom pris dans celui d'une institution, d'une rue ou d'un prix ne compte pas.
			static $institutions = null;
			if ( null === $institutions ) {
				$institutions = array_unique( array_map( 'er_cle_recherche', array( 'centre culturel', 'centre', 'fondation', 'prix', 'musée', 'bibliothèque', 'rue', 'avenue', 'boulevard', 'institut', 'maison', 'fonds', 'lycée', 'école', 'université', 'place', 'gymnase', 'центр', 'фонд', 'премия', 'улица', 'дом' ) ) );
			}
			foreach ( $institutions as $avant ) {
				$n -= substr_count( $texte, ' ' . $avant . ' ' . $cle . ' ' );
			}
			if ( $n > 0 ) {
				$trouves[ $tid ] = $tid;
			}
		}
	}
	return array_values( $trouves );
}

/**
 * Les premiers éléments d'une liste triée par nombre, en lignes « nom (n) ».
 *
 * @param array $comptes Nom → nombre.
 * @param int   $combien Combien.
 */
function er_audit_tete( $comptes, $combien = 12 ) {
	arsort( $comptes );
	$lignes = array();
	foreach ( array_slice( $comptes, 0, $combien, true ) as $nom => $n ) {
		$lignes[] = '    ' . $nom . ' (' . $n . ')';
	}
	if ( count( $comptes ) > $combien ) {
		$lignes[] = '    … et ' . ( count( $comptes ) - $combien ) . ' autres';
	}
	return implode( "\n", $lignes );
}

/**
 * Un livre par sa référence (SKU) ou son identifiant.
 *
 * @param string $ref La référence.
 */
function er_audit_livre( $ref ) {
	if ( ctype_digit( $ref ) ) {
		return 'product' === get_post_type( (int) $ref ) ? (int) $ref : 0;
	}
	return function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( $ref ) : 0;
}

/**
 * wp er audit : le rapport des recoupements.
 */
function er_audit() {
	$t0 = microtime( true );
	$p  = er_audit_personnes();
	$ln = function ( $texte = '' ) {
		WP_CLI::log( $texte );
	};
	$titre_de = function ( $id ) {
		return html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	};

	$livres    = er_audit_ids( 'product' );
	$articles  = er_audit_ids( 'post' );
	$rencontres = defined( 'ER_EVT' ) ? er_audit_ids( ER_EVT ) : array();
	$medias    = er_audit_ids( 'er_media' );
	$expos     = er_audit_ids( 'er_exposition' );
	$pages     = er_audit_ids( 'page' );

	$ln( 'Audit des recoupements — ' . wp_date( 'j F Y, H:i' ) );
	$ln( sprintf( '%d livres, %d articles, %d rencontres, %d vidéos et podcasts, %d expositions, %d pages, %d personnes.', count( $livres ), count( $articles ), count( $rencontres ), count( $medias ), count( $expos ), count( $pages ), count( $p['termes'] ) ) );

	/* 1. Les personnes en double -------------------------------------------------------------------------------- */
	$ln();
	$ln( '## 1. Les personnes en double (même nom à l’accent ou à la transcription près, ou par une autre graphie)' );
	$groupes = array();
	foreach ( $p['index'] as $cle => $tids ) {
		if ( count( $tids ) > 1 ) {
			sort( $tids );
			$groupes[ implode( ',', $tids ) ] = $tids;
		}
	}
	$ln( '  ' . count( $groupes ) . ' groupes.' );
	foreach ( array_slice( $groupes, 0, 20 ) as $tids ) {
		$ln( '    ' . implode( ' = ', array_map( fn( $t ) => $p['termes'][ $t ]->name . ' [' . $p['termes'][ $t ]->count . ']', $tids ) ) );
	}
	// Les doublons probables : même nom de famille et même initiale (« А. Солженицын » et « Alexandre Soljenitsyne »),
	// ou un nom tout entier au début d'un autre (« Christine Zeytounian » et « Christine Zeytounian-Beloüs »).
	$familles = array();
	$noms_cle = array();
	foreach ( $p['termes'] as $tid => $t ) {
		$cle  = er_cle_recherche( $t->name );
		$mots = explode( ' ', $cle );
		if ( count( $mots ) < 2 ) {
			continue;
		}
		$noms_cle[ $tid ]                                  = $cle;
		$familles[ end( $mots ) . '|' . $mots[0][0] ][ $tid ] = $tid;
	}
	$probables = array();
	foreach ( $familles as $tids ) {
		if ( count( $tids ) > 1 ) {
			$probables[ implode( ',', $tids ) ] = array_values( $tids );
		}
	}
	foreach ( $noms_cle as $a => $ca ) {
		foreach ( $noms_cle as $b => $cb ) {
			if ( $a !== $b && strlen( $cb ) > strlen( $ca ) && 0 === strpos( $cb, $ca . ' ' ) ) {
				$k               = min( $a, $b ) . ',' . max( $a, $b );
				$probables[ $k ] = array( $a, $b );
			}
		}
	}
	$ln( '  Doublons probables (même nom de famille et même initiale, ou un nom au début d’un autre) : ' . count( $probables ) . ' groupes, à vérifier un par un.' );
	foreach ( array_slice( $probables, 0, 25 ) as $tids ) {
		$ln( '    ' . implode( ' ~ ', array_map( fn( $t ) => $p['termes'][ $t ]->name . ' [' . $p['termes'][ $t ]->count . ']', $tids ) ) );
	}

	/* 2. Les signatures du journal ------------------------------------------------------------------------------ */
	$ln();
	$ln( '## 2. Les signatures du journal' );
	$signatures = array();
	$sans_sig   = 0;
	foreach ( $articles as $id ) {
		$sig = trim( (string) get_post_meta( $id, 'er_signature', true ) );
		if ( '' === $sig ) {
			++$sans_sig;
			continue;
		}
		$signatures[ $sig ][] = $id;
	}
	$liees      = array();
	$a_relier   = array();
	$sans_page  = array();
	$absents    = array(); // signés par la personne, mais pas rattachés à elle : absents de sa page.
	$par_graphie = array();
	foreach ( $signatures as $sig => $ids ) {
		// La signature mène à la page que trouve er_personne_trouver() (er_bloc_signature) : le nom, l'ancien slug, ou
		// une autre graphie qui ne désigne qu'une personne.
		$exact = function_exists( 'er_personne_trouver' ) ? er_personne_trouver( $sig ) : get_term_by( 'name', $sig, 'auteur' );
		$cand  = isset( $p['index'][ er_cle_recherche( $sig ) ] ) ? $p['index'][ er_cle_recherche( $sig ) ] : array();
		if ( $exact ) {
			$liees[ $sig ] = count( $ids );
			$tid           = $exact->term_id;
			if ( $exact->name !== $sig ) {
				$par_graphie[ $sig . ' → ' . $exact->name ] = count( $ids );
			}
		} elseif ( $cand ) {
			$a_relier[ $sig . ' → ' . $p['termes'][ $cand[0] ]->name ] = count( $ids );
			$tid = $cand[0];
		} else {
			$sans_page[ $sig ] = count( $ids );
			$tid               = 0;
		}
		if ( $tid ) {
			foreach ( $ids as $id ) {
				if ( ! has_term( $tid, 'auteur', $id ) ) {
					$nom             = $p['termes'][ $tid ]->name;
					$absents[ $nom ] = ( isset( $absents[ $nom ] ) ? $absents[ $nom ] : 0 ) + 1;
				}
			}
		}
	}
	$ln( sprintf( '  %d signatures (%d articles sans signature) : %d reliées à une page (dont %d par une autre graphie), %d qui ont une page sous une autre graphie sans que le lien se fasse (plusieurs personnes possibles), %d sans page.', count( $signatures ), $sans_sig, count( $liees ), count( $par_graphie ), count( $a_relier ), count( $sans_page ) ) );
	if ( $par_graphie ) {
		$ln( '  Reliées par une autre graphie (signature → page) :' );
		$ln( er_audit_tete( $par_graphie, 20 ) );
	}
	if ( $a_relier ) {
		$ln( '  Signatures à relier (graphie → page) :' );
		$ln( er_audit_tete( $a_relier, 20 ) );
	}
	if ( $sans_page ) {
		$ln( '  Signatures sans page de personne (articles) :' );
		$ln( er_audit_tete( $sans_page, 20 ) );
	}
	$ln( '  Articles signés par une personne qui a sa page, mais pas rattachés à elle (absents de sa page) : ' . array_sum( $absents ) );
	if ( $absents ) {
		$ln( er_audit_tete( $absents ) );
	}

	/* 3. Les intervenants des rencontres ------------------------------------------------------------------------ */
	$ln();
	$ln( '## 3. Les intervenants des rencontres' );
	$inter_lies = 0;
	$inter_relier = array();
	$inter_seuls  = array();
	if ( taxonomy_exists( 'er_intervenant' ) ) {
		foreach ( get_terms( array( 'taxonomy' => 'er_intervenant', 'hide_empty' => false ) ) as $t ) {
			if ( function_exists( 'er_evt_personne' ) ? er_evt_personne( $t ) : get_term_by( 'slug', $t->slug, 'auteur' ) ) {
				++$inter_lies;
				continue;
			}
			$cand = isset( $p['index'][ er_cle_recherche( $t->name ) ] ) ? $p['index'][ er_cle_recherche( $t->name ) ] : array();
			if ( $cand ) {
				$inter_relier[ $t->name . ' → ' . $p['termes'][ $cand[0] ]->name ] = $t->count;
			} else {
				$inter_seuls[ $t->name ] = $t->count;
			}
		}
	}
	$ln( sprintf( '  %d intervenants reliés à leur page, %d qui ont une page sous une autre graphie (le lien manque : ni lien, ni rencontre sur leur page), %d sans page.', $inter_lies, count( $inter_relier ), count( $inter_seuls ) ) );
	if ( $inter_relier ) {
		$ln( '  À relier (intervenant → page, rencontres) :' );
		$ln( er_audit_tete( $inter_relier, 20 ) );
	}
	if ( $inter_seuls ) {
		$ln( '  Les plus présents sans page (rencontres) :' );
		$ln( er_audit_tete( $inter_seuls, 15 ) );
	}

	/* 4. Les personnes nommées sans être rattachées ------------------------------------------------------------- */
	$ln();
	$ln( '## 4. Les personnes nommées dans un texte sans y être rattachées (prénom et nom en toutes lettres)' );
	$par_mot = array();
	foreach ( $p['cles'] as $tid => $cles ) {
		foreach ( array_unique( $cles ) as $cle ) {
			$mots = explode( ' ', $cle );
			if ( count( $mots ) < 2 || strlen( end( $mots ) ) < 4 ) {
				continue;
			}
			$par_mot[ end( $mots ) ][] = array( $cle, $tid );
		}
	}
	$sortes = array(
		'articles'          => array( $articles, array( 'er_signature' ) ),
		'rencontres'        => array( $rencontres, array() ),
		'vidéos et podcasts' => array( $medias, array() ),
		'expositions'       => array( $expos, array( 'er_artistes' ) ),
		'pages'             => array( $pages, array() ),
	);
	$par_personne = array();
	$relues_n     = 0;
	foreach ( $sortes as $sorte => list( $ids, $metas ) ) {
		$contenus = 0;
		$liens    = 0;
		foreach ( $ids as $id ) {
			$rattaches = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'ids' ) );
			$rattaches = is_wp_error( $rattaches ) ? array() : array_map( 'intval', $rattaches );
			if ( 'rencontres' === $sorte && taxonomy_exists( 'er_intervenant' ) ) {
				foreach ( wp_get_post_terms( $id, 'er_intervenant' ) as $it ) {
					$a = function_exists( 'er_evt_personne' ) ? er_evt_personne( $it ) : get_term_by( 'slug', $it->slug, 'auteur' );
					if ( $a ) {
						$rattaches[] = (int) $a->term_id;
					}
				}
			}
			$signature = 'articles' === $sorte ? trim( (string) get_post_meta( $id, 'er_signature', true ) ) : '';
			$sig       = '' !== $signature && function_exists( 'er_personne_trouver' ) ? er_personne_trouver( $signature ) : null;
			if ( $sig ) {
				$rattaches[] = (int) $sig->term_id;
			}
			$manque = array_diff( er_audit_nommes( er_audit_texte( $id, $metas ), $par_mot ), $rattaches );
			// Les étiquettes relues à la main (er_etiquettes_relues) : ce qui n'y est pas a été laissé exprès.
			if ( $manque && get_post_meta( $id, 'er_etiquettes_relues', true ) ) {
				$relues_n += count( $manque );
				continue;
			}
			if ( $manque ) {
				++$contenus;
				$liens += count( $manque );
				foreach ( $manque as $tid ) {
					$nom                  = $p['termes'][ $tid ]->name;
					$par_personne[ $nom ] = ( isset( $par_personne[ $nom ] ) ? $par_personne[ $nom ] : 0 ) + 1;
				}
			}
		}
		$ln( sprintf( '  %s : %d sur %d nomment au moins une personne sans y être rattachés (%d rattachements qui manquent).', $sorte, $contenus, count( $ids ), $liens ) );
	}
	$ln( '  Sans compter ' . $relues_n . ' noms laissés de côté dans des contenus aux étiquettes relues à la main.' );
	$ln( '  Les personnes les plus nommées sans rattachement :' );
	$ln( er_audit_tete( $par_personne, 25 ) );

	/* 5. Les livres -------------------------------------------------------------------------------------------- */
	$ln();
	$ln( '## 5. Les livres' );
	// Les rôles relevés dans les textes (er_roles_releves : la méta du traducteur, « préface de… », « illustrations
	// d'… ») et ceux qui sont posés : ce qui reste à poser par `wp er roles appliquer`.
	$roles_tax    = function_exists( 'er_roles_personnes' ) ? er_roles_personnes() : array();
	$roles_poses  = array(); // rôle → livres qui en ont au moins un posé.
	$roles_manque = array(); // rôle → noms relevés mais pas posés.
	$roles_noms   = array();
	$trad_sans    = array();
	$edit_sans    = 0;
	$col_posees   = 0;
	$col_a_poser  = array();
	$col_ecart    = array();
	$editions     = array();
	foreach ( $livres as $id ) {
		if ( get_post_meta( $id, 'er_evenement', true ) ) {
			continue; // un billet de rencontre
		}
		$post    = get_post( $id );
		$releves = function_exists( 'er_roles_releves' ) ? er_roles_releves( $id ) : array();
		foreach ( $roles_tax as $tax => $r ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$poses = wp_get_post_terms( $id, $tax );
			$poses = is_wp_error( $poses ) ? array() : wp_list_pluck( $poses, 'slug' );
			if ( $poses ) {
				$roles_poses[ $r['fiche'] ] = ( isset( $roles_poses[ $r['fiche'] ] ) ? $roles_poses[ $r['fiche'] ] : 0 ) + 1;
			}
			foreach ( array_keys( isset( $releves[ $r['cle'] ] ) ? $releves[ $r['cle'] ] : array() ) as $nom ) {
				$qui = er_personne_trouver( $nom );
				if ( $qui && in_array( $qui->slug, $poses, true ) ) {
					continue;
				}
				$roles_manque[ $r['fiche'] ] = ( isset( $roles_manque[ $r['fiche'] ] ) ? $roles_manque[ $r['fiche'] ] : 0 ) + 1;
				$roles_noms[ $nom ]          = ( isset( $roles_noms[ $nom ] ) ? $roles_noms[ $nom ] : 0 ) + 1;
			}
		}
		// La méta du traducteur, reprise de Wix, sans personne posée.
		$trad = trim( (string) get_post_meta( $id, 'er_traducteur', true ) );
		if ( '' !== $trad && taxonomy_exists( 'er_traduction' ) && ! wp_get_post_terms( $id, 'er_traduction', array( 'fields' => 'ids' ) ) ) {
			$trad_sans[ $titre_de( $id ) ] = $trad;
		}
		if ( '' !== trim( (string) get_post_meta( $id, 'er_editeur', true ) ) && taxonomy_exists( 'er_editeur' ) && ! wp_get_post_terms( $id, 'er_editeur', array( 'fields' => 'ids' ) ) ) {
			++$edit_sans;
		}
		// La collection : posée (une page), à poser (`wp er collections appliquer`), ou un texte de Wix qui n'en est pas une.
		$col = trim( (string) get_post_meta( $id, 'er_collection', true ) );
		if ( '' !== $col && function_exists( 'er_collection_du_livre' ) ) {
			$c = er_collection_du_livre( $id );
			if ( $c['termes'] ) {
				++$col_posees;
			} elseif ( '' !== $c['texte'] ) {
				$col_a_poser[ $c['texte'] ] = ( isset( $col_a_poser[ $c['texte'] ] ) ? $col_a_poser[ $c['texte'] ] : 0 ) + 1;
			} else {
				$col_ecart[ mb_substr( $col, 0, 50 ) ] = 1;
			}
		}
		// Le même livre en plusieurs fiches : mêmes auteurs, même titre (avant le sous-titre).
		$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'ids' ) );
		if ( $auteurs && ! is_wp_error( $auteurs ) ) {
			sort( $auteurs );
			$titre = er_cle_recherche( preg_split( '/\s[:—–-]\s|[:(]/u', html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ) )[0] );
			if ( strlen( $titre ) > 3 ) {
				$editions[ implode( ',', $auteurs ) . '|' . $titre ][] = $id;
			}
		}
	}
	$ln( '  Les rôles posés (livres) : ' . ( $roles_poses ? implode( ', ', array_map( fn( $r, $n ) => $r . ' ' . $n, array_keys( $roles_poses ), $roles_poses ) ) : 'aucun' ) . '.' );
	$ln( '  Relevés dans les textes mais pas posés : ' . array_sum( $roles_manque ) . ( $roles_manque ? ' (' . implode( ', ', array_map( fn( $r, $n ) => $r . ' ' . $n, array_keys( $roles_manque ), $roles_manque ) ) . ') — wp er roles appliquer' : '' ) . '.' );
	if ( $roles_noms ) {
		$ln( er_audit_tete( $roles_noms, 15 ) );
	}
	$ln( '  Livres avec un traducteur repris de Wix, mais personne posée (wp er roles l’a mis de côté) : ' . count( $trad_sans ) );
	foreach ( array_slice( $trad_sans, 0, 15, true ) as $t => $m ) {
		$ln( '    ' . $t . ' · « ' . mb_substr( $m, 0, 80 ) . ' »' );
	}
	$ln( '  Livres avec un éditeur repris de Wix mais sans éditeur posé : ' . $edit_sans );
	// Les collections : celles qui ont leur page, et deux pages pour une même collection (« Folio bilingue »,
	// « FolioBilingue »), à la casse, aux espaces et aux traits d'union près.
	$col_termes = taxonomy_exists( 'er_collection' ) ? get_terms( array( 'taxonomy' => 'er_collection', 'hide_empty' => false, 'orderby' => 'count', 'order' => 'DESC' ) ) : array();
	$col_termes = is_wp_error( $col_termes ) ? array() : $col_termes;
	$col_cles   = array();
	foreach ( $col_termes as $t ) {
		$col_cles[ str_replace( array( ' ', '-' ), '', er_cle_recherche( $t->name ) ) ][] = $t->name . ' (' . $t->count . ')';
	}
	$col_doubles = array_filter( $col_cles, fn( $n ) => count( $n ) > 1 );
	$ln( sprintf( '  Collections : %d pages, %d livres rangés ; %d livres dont la collection reste à poser (wp er collections appliquer) ; %d textes de Wix qui ne sont pas un nom de collection, écartés ; %d collections en deux pages.', count( $col_termes ), $col_posees, array_sum( $col_a_poser ), count( $col_ecart ), count( $col_doubles ) ) );
	$ln( '    Les plus grandes : ' . implode( ', ', array_map( fn( $t ) => $t->name . ' (' . $t->count . ')', array_slice( $col_termes, 0, 8 ) ) ) );
	foreach ( array_slice( $col_a_poser, 0, 10, true ) as $nom => $n ) {
		$ln( '    à poser : ' . $nom . ' (' . $n . ')' );
	}
	foreach ( $col_doubles as $noms ) {
		$ln( '    en double : ' . implode( ' / ', $noms ) );
	}
	// Le même livre en plusieurs fiches : relié par « Autres éditions » ou « Les autres volumes » (er_autres_editions),
	// ou non. Les fiches d'une même édition (même titre entier, même langue, même éditeur, même année) sont à trancher :
	// un doublon de Wix, ou le neuf et l'occasion.
	$multi    = array_filter( $editions, fn( $ids ) => count( $ids ) > 1 );
	$non_lies = array();
	$jumeaux  = array();
	foreach ( $multi as $ids ) {
		$autres = function_exists( 'er_autres_editions' ) ? er_autres_editions( $ids[0] ) : array( 'editions' => array(), 'volumes' => array() );
		if ( array_diff( array_slice( $ids, 1 ), array_merge( $autres['editions'], $autres['volumes'] ) ) ) {
			$non_lies[] = $ids;
		}
		$par_edition = array();
		foreach ( $ids as $i ) {
			$langue = wp_get_post_terms( $i, 'langue', array( 'fields' => 'ids' ) );
			$ed     = function_exists( 'er_editeur_du_livre' ) ? er_editeur_du_livre( $i ) : array( 'termes' => array(), 'texte' => '' );
			$cle    = er_cle_recherche( html_entity_decode( get_the_title( $i ), ENT_QUOTES, 'UTF-8' ) )
				. '|' . ( is_wp_error( $langue ) ? '' : implode( ',', $langue ) )
				. '|' . ( $ed['termes'] ? $ed['termes'][0]->term_id : er_cle_recherche( (string) $ed['texte'] ) )
				. '|' . trim( (string) get_post_meta( $i, 'er_annee', true ) );
			$par_edition[ $cle ][] = $i;
		}
		foreach ( $par_edition as $memes ) {
			if ( count( $memes ) > 1 ) {
				$jumeaux[] = $memes;
			}
		}
	}
	$ln( sprintf( '  Le même livre en plusieurs fiches (mêmes auteurs, même titre) : %d groupes, %d que « Autres éditions » ou « Les autres volumes » ne relient pas encore.', count( $multi ), count( $non_lies ) ) );
	foreach ( array_slice( $non_lies, 0, 12 ) as $ids ) {
		$ln( '    ' . implode( ' / ', array_map( fn( $i ) => $titre_de( $i ) . ' [' . get_post_meta( $i, '_sku', true ) . ']', $ids ) ) );
	}
	$ln( '  Dont la même édition en plusieurs fiches (même titre, langue, éditeur et année : un doublon, ou le neuf et l’occasion) : ' . count( $jumeaux ) );
	foreach ( array_slice( $jumeaux, 0, 15 ) as $ids ) {
		$ln(
			'    ' . implode(
				' / ',
				array_map(
					function ( $i ) use ( $titre_de ) {
						$prix  = get_post_meta( $i, '_price', true );
						$stock = get_post_meta( $i, '_stock_status', true );
						return $titre_de( $i ) . ' [' . get_post_meta( $i, '_sku', true ) . ( '' !== (string) $prix ? ', ' . $prix . ' €' : '' ) . ( 'instock' === $stock ? ', en stock' : ', épuisé' ) . ']';
					},
					$ids
				)
			)
		);
	}

	/* 6. Les références de livres ------------------------------------------------------------------------------- */
	$ln();
	$ln( '## 6. Les livres cités par référence (« Livres liés » des rencontres, des articles, des expositions)' );
	$cassees = array();
	$total   = 0;
	foreach ( array( 'rencontres' => $rencontres, 'articles' => $articles, 'expositions' => $expos, 'vidéos' => $medias ) as $sorte => $ids ) {
		foreach ( $ids as $id ) {
			foreach ( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'er_livres', true ) ) ) ) as $ref ) {
				++$total;
				if ( ! er_audit_livre( $ref ) ) {
					$cassees[] = '    ' . $sorte . ' · ' . $titre_de( $id ) . ' · ' . $ref;
				}
			}
		}
	}
	$ln( sprintf( '  %d références, %d qui ne mènent à aucun livre.', $total, count( $cassees ) ) );
	foreach ( array_slice( $cassees, 0, 15 ) as $l ) {
		$ln( $l );
	}
	// La fiche d'un livre montre deux contenus dans « Autour de ce livre » (er_reco_bloc_autour) : la meilleure rencontre
	// et le meilleur article ou vidéo, choisis par les citations d'abord (er_reco_citants), puis les auteurs, les
	// personnes et les sujets qu'ils partagent avec lui. Un contenu qui cite le livre n'y manque que si la place de sa
	// sorte est prise par un autre qui le cite aussi ; sinon, la citation est à sens unique.
	$montres_de  = function ( $livre ) {
		return function_exists( 'er_recommandations' ) ? array_slice( array_map( 'intval', er_recommandations( $livre )['contenus'] ), 0, 2 ) : array();
	};
	$place_prise = array();
	$termes_de = function ( $id, $tax ) {
		$t = taxonomy_exists( $tax ) ? wp_get_post_terms( $id, $tax, array( 'fields' => 'ids' ) ) : array();
		return is_wp_error( $t ) ? array() : array_map( 'intval', $t );
	};
	$sens_unique = array();
	$cites       = 0;
	foreach ( array( 'rencontres' => $rencontres, 'articles' => $articles, 'vidéos' => $medias ) as $sorte => $ids ) {
		foreach ( $ids as $id ) {
			$siens = $termes_de( $id, 'auteur' );
			$sujets_c = $termes_de( $id, 'er_sujet' );
			foreach ( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'er_livres', true ) ) ) ) as $ref ) {
				$livre = er_audit_livre( $ref );
				if ( ! $livre ) {
					continue;
				}
				++$cites;
				$montres = $montres_de( $livre );
				if ( ! in_array( (int) $id, $montres, true ) ) {
					$rencontre = defined( 'ER_EVT' ) && ER_EVT === get_post_type( $id );
					$sa_place  = array_filter( $montres, fn( $m ) => ( defined( 'ER_EVT' ) && ER_EVT === get_post_type( $m ) ) === $rencontre );
					$citants   = function_exists( 'er_reco_citants' ) ? array_map( 'intval', er_reco_citants( $livre ) ) : array();
					if ( array_intersect( $sa_place, $citants ) ) {
						$place_prise[ $titre_de( $livre ) ] = ( isset( $place_prise[ $titre_de( $livre ) ] ) ? $place_prise[ $titre_de( $livre ) ] : 0 ) + 1;
						continue;
					}
					$sens_unique[] = '    ' . $titre_de( $livre ) . ' ← ' . $sorte . ' · ' . $titre_de( $id );
				}
			}
		}
	}
	$ln( sprintf( '  Citations à sens unique : %d livres cités sur %d ne montrent pas, sur leur fiche, le contenu qui les cite ; %d n’y manquent que parce que la fiche en montre déjà un autre qui le cite.', count( $sens_unique ), $cites, array_sum( $place_prise ) ) );
	if ( $place_prise ) {
		$ln( er_audit_tete( $place_prise, 8 ) );
	}
	foreach ( array_slice( $sens_unique, 0, 15 ) as $l ) {
		$ln( $l );
	}
	// Les traducteurs, les préfaciers… : une rencontre avec le traducteur d'un livre n'apparaît pas sur la fiche du livre.
	$par_trad = 0;
	$exemples = array();
	foreach ( $livres as $id ) {
		$gens = array();
		foreach ( array_keys( function_exists( 'er_roles_personnes' ) ? er_roles_personnes() : array() ) as $tax ) {
			foreach ( taxonomy_exists( $tax ) ? wp_get_post_terms( $id, $tax ) : array() as $miroir ) {
				$a = get_term_by( 'slug', $miroir->slug, 'auteur' );
				if ( $a ) {
					$gens[] = (int) $a->term_id;
				}
			}
		}
		$gens = array_diff( $gens, $termes_de( $id, 'auteur' ) );
		if ( ! $gens ) {
			continue;
		}
		$leurs = get_posts(
			array(
				'post_type'   => array_values( array_filter( array( 'post', defined( 'ER_EVT' ) ? ER_EVT : '', 'er_media' ), 'post_type_exists' ) ),
				'post_status' => 'publish',
				'numberposts' => 3,
				'fields'      => 'ids',
				'lang'        => '',
				'tax_query'   => array( array( 'taxonomy' => 'auteur', 'terms' => $gens ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$montres = $montres_de( $id );
		if ( $leurs && ! array_intersect( array_map( 'intval', $leurs ), $montres ) ) {
			++$par_trad;
			if ( count( $exemples ) < 12 ) {
				$exemples[] = '    ' . $titre_de( $id ) . ' ← ' . $titre_de( $leurs[0] );
			}
		}
	}
	$ln( '  Livres dont le traducteur, le préfacier… a des rencontres, des articles ou des vidéos que la fiche ne montre pas : ' . $par_trad );
	foreach ( $exemples as $l ) {
		$ln( $l );
	}

	/* 7. Les vidéos des rencontres ----------------------------------------------------------------------------- */
	$ln();
	$ln( '## 7. Les vidéos des rencontres et la médiathèque' );
	$adresse = function ( $texte ) {
		preg_match_all( '#https?://[^\s|"<>]+#', (string) $texte, $m );
		return array_map( fn( $u ) => preg_replace( '#^https?://(www\.|m\.)?|[/?&]+$#', '', $u ), $m[0] );
	};
	$mediatheque = array();
	foreach ( $medias as $id ) {
		foreach ( $adresse( get_post_meta( $id, 'er_lien', true ) ) as $u ) {
			$mediatheque[ $u ] = $id;
		}
	}
	$hors = 0;
	$dans = 0;
	foreach ( $rencontres as $id ) {
		foreach ( $adresse( get_post_meta( $id, 'er_videos', true ) ) as $u ) {
			isset( $mediatheque[ $u ] ) ? ++$dans : ++$hors;
		}
	}
	$par_rencontre = function_exists( 'er_media_rencontres' ) ? count( er_media_rencontres( '' ) ) : 0;
	$ln( sprintf( '  %d vidéos de rencontres : %d sont aussi des vidéos de la médiathèque ; « Vidéos et podcasts » montre %d rencontres enregistrées (« Les rencontres, à revoir »).', $dans + $hors, $dans, $par_rencontre ) );

	/* 8. L'encyclopédie ------------------------------------------------------------------------------------------ */
	$ln();
	$ln( '## 8. L’encyclopédie et les pages des personnes' );
	$avec_page = array();
	foreach ( $p['termes'] as $t ) {
		$pg = (int) get_term_meta( $t->term_id, 'er_page', true );
		if ( $pg ) {
			$avec_page[ $pg ] = $t->term_id;
		}
	}
	$encyclo   = 0;
	$a_relier  = array();
	foreach ( $pages as $id ) {
		if ( 0 !== strpos( get_page_uri( $id ), 'encyclopedie/' ) ) {
			continue;
		}
		++$encyclo;
		if ( isset( $avec_page[ $id ] ) ) {
			continue;
		}
		$cle = er_cle_recherche( preg_split( '/[,(—–]/u', get_the_title( $id ) )[0] );
		if ( isset( $p['index'][ $cle ] ) ) {
			$a_relier[] = '    ' . $titre_de( $id ) . ' → ' . $p['termes'][ $p['index'][ $cle ][0] ]->name;
		}
	}
	$ln( sprintf( '  %d articles de l’encyclopédie, %d reliés à la page d’une personne ; %d portent le nom d’une personne qui a sa page, sans lien entre les deux.', $encyclo, count( array_intersect_key( $avec_page, array_flip( $pages ) ) ), count( $a_relier ) ) );
	$racines = array();
	foreach ( $pages as $id ) {
		$r             = strtok( (string) get_page_uri( $id ), '/' );
		$racines[ $r ] = ( isset( $racines[ $r ] ) ? $racines[ $r ] : 0 ) + 1;
	}
	$ln( '  Les pages du site, par rubrique : ' . implode( ', ', array_map( fn( $r, $n ) => $r . ' (' . $n . ')', array_keys( $racines ), $racines ) ) );
	foreach ( array_slice( $a_relier, 0, 15 ) as $l ) {
		$ln( $l );
	}

	/* 9. Les contenus isolés ------------------------------------------------------------------------------------- */
	$ln();
	$ln( '## 9. Les contenus isolés (ni personne, ni sujet : aucune page ne les montre en dehors de leur rubrique)' );
	foreach ( array( 'articles' => $articles, 'rencontres' => $rencontres, 'vidéos et podcasts' => $medias, 'expositions' => $expos ) as $sorte => $ids ) {
		$seuls = 0;
		foreach ( $ids as $id ) {
			$a = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'ids' ) );
			$s = taxonomy_exists( 'er_sujet' ) ? wp_get_post_terms( $id, 'er_sujet', array( 'fields' => 'ids' ) ) : array();
			$i = 'rencontres' === $sorte && taxonomy_exists( 'er_intervenant' ) ? wp_get_post_terms( $id, 'er_intervenant', array( 'fields' => 'ids' ) ) : array();
			if ( ! $a && ! $s && ! $i ) {
				++$seuls;
			}
		}
		$ln( sprintf( '  %s : %d sur %d.', $sorte, $seuls, count( $ids ) ) );
	}

	$ln();
	$ln( sprintf( 'Fait en %.1f s. Rien n’a été modifié.', microtime( true ) - $t0 ) );
}

/**
 * Le rapport des recoupements, en lecture seule : wp er audit.
 */
WP_CLI::add_command( 'er audit', 'er_audit' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Montre la bande de page de quelques pages, telle que le site la sert : wp er bande [<chemin>…]. Sans chemin, les
	 * pages qui doivent en avoir une (un rayon, une personne, un sujet, les rencontres, les expositions, le journal…).
	 *
	 * @param array $args Les chemins.
	 */
	WP_CLI::add_command(
		'er bande',
		function ( $args ) {
			$chemins = $args;
			if ( ! $chemins ) {
				$chemins = array( 'centre-culturel/evenements/', 'centre-culturel/expositions/', 'journal/', 'ymca-press/histoire/', 'librairie/encyclopedie/age-d-argent/', 'nous-trouver/', 'centre-culturel/videos/' );
				$rayon   = get_term_by( 'slug', 'litterature', 'product_cat' );
				if ( $rayon ) {
					$chemins[] = wp_make_link_relative( get_term_link( $rayon ) );
				}
				foreach ( array( 'georges-nivat', 'alexandre-soljenitsyne' ) as $slug ) {
					$t = get_term_by( 'slug', $slug, 'auteur' );
					if ( $t ) {
						$chemins[] = wp_make_link_relative( get_term_link( $t ) );
					}
				}
				$sujets = get_terms( array( 'taxonomy' => 'er_sujet', 'number' => 1, 'orderby' => 'count', 'order' => 'DESC' ) );
				if ( $sujets && ! is_wp_error( $sujets ) ) {
					$chemins[] = wp_make_link_relative( get_term_link( $sujets[0] ) );
				}
			}
			foreach ( $chemins as $chemin ) {
				$url = 0 === strpos( $chemin, 'http' ) ? $chemin : home_url( '/' . ltrim( $chemin, '/' ) );
				$r   = wp_remote_get( $url, array( 'timeout' => 30, 'sslverify' => false, 'redirection' => 3 ) );
				if ( is_wp_error( $r ) ) {
					WP_CLI::log( $chemin . ' : ' . $r->get_error_message() );
					continue;
				}
				$html = wp_remote_retrieve_body( $r );
				$n    = preg_match_all( '#<nav class="er-bande ([^"]*)"[^>]*>(.*?)</nav>#s', $html, $m, PREG_SET_ORDER );
				WP_CLI::log( $chemin . ' · ' . wp_remote_retrieve_response_code( $r ) . ' · ' . $n . ' bande' . ( $n > 1 ? 's' : '' ) );
				foreach ( $m as $x ) {
					preg_match( '#er-bande-nom-texte">([^<]*)<#', $x[2], $nom );
					preg_match_all( '#<li class="er-bande-(groupe|sep)"[^>]*>([^<]*)</li>|<a class="er-bande-lien"[^>]*?(aria-current="[^"]*")?>(?:<span class="er-bande-avant">([^<]*)</span>)?<span>([^<]*)</span>|<button[^>]*class="er-bande-lien[^"]*"[^>]*><span>([^<]*)</span>#', $x[2], $e, PREG_SET_ORDER );
					$entrees = array();
					foreach ( $e as $y ) {
						if ( ! empty( $y[1] ) ) {
							$entrees[] = 'sep' === $y[1] ? '|' : strtoupper( $y[2] );
						} elseif ( ! empty( $y[6] ) ) {
							$entrees[] = $y[6] . ' ▾';
						} else {
							$entrees[] = ( empty( $y[4] ) ? '' : $y[4] . ' ' ) . html_entity_decode( $y[5], ENT_QUOTES, 'UTF-8' ) . ( empty( $y[3] ) ? '' : ' •' );
						}
					}
					WP_CLI::log( '  [' . trim( $x[1] ) . '] ' . ( $nom ? html_entity_decode( $nom[1], ENT_QUOTES, 'UTF-8' ) . ' : ' : '' ) . implode( ' · ', $entrees ) );
				}
				// L'accroche : la bande doit être l'enfant d'un contenant qui va jusqu'au bout de la page.
				if ( $n && preg_match( '#<(\w+)([^>]*)>\s*(?:<!--[^>]*-->\s*)*<div class="er-bande-repere"#', $html, $p ) ) {
					WP_CLI::log( '  dans <' . $p[1] . ( preg_match( '#class="([^"]*)"#', $p[2], $c ) ? ' class="' . $c[1] . '"' : '' ) . '>' );
				}
			}
		}
	);
}
