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
	foreach ( $signatures as $sig => $ids ) {
		$exact = get_term_by( 'name', $sig, 'auteur' );
		$cand  = isset( $p['index'][ er_cle_recherche( $sig ) ] ) ? $p['index'][ er_cle_recherche( $sig ) ] : array();
		if ( $exact ) {
			$liees[ $sig ] = count( $ids );
			$tid           = $exact->term_id;
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
	$ln( sprintf( '  %d signatures (%d articles sans signature) : %d reliées à une page, %d qui ont une page sous une autre graphie (le lien manque), %d sans page.', count( $signatures ), $sans_sig, count( $liees ), count( $a_relier ), count( $sans_page ) ) );
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
			if ( get_term_by( 'slug', $t->slug, 'auteur' ) ) {
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
	foreach ( $sortes as $sorte => list( $ids, $metas ) ) {
		$contenus = 0;
		$liens    = 0;
		foreach ( $ids as $id ) {
			$rattaches = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'ids' ) );
			$rattaches = is_wp_error( $rattaches ) ? array() : array_map( 'intval', $rattaches );
			if ( 'rencontres' === $sorte && taxonomy_exists( 'er_intervenant' ) ) {
				foreach ( wp_get_post_terms( $id, 'er_intervenant' ) as $it ) {
					$a = get_term_by( 'slug', $it->slug, 'auteur' );
					if ( $a ) {
						$rattaches[] = (int) $a->term_id;
					}
				}
			}
			$sig = 'articles' === $sorte ? get_term_by( 'name', trim( (string) get_post_meta( $id, 'er_signature', true ) ), 'auteur' ) : null;
			if ( $sig ) {
				$rattaches[] = (int) $sig->term_id;
			}
			$manque = array_diff( er_audit_nommes( er_audit_texte( $id, $metas ), $par_mot ), $rattaches );
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
	$ln( '  Les personnes les plus nommées sans rattachement :' );
	$ln( er_audit_tete( $par_personne, 25 ) );

	/* 5. Les livres -------------------------------------------------------------------------------------------- */
	$ln();
	$ln( '## 5. Les livres' );
	// Les rôles que la fiche ne connaît pas : préface, postface, illustrations, présentation, édition établie par…
	$motif = '/\b(pr[ée]fac[ée]e?|pr[ée]face|postface|illustr[ée]e?s?|illustrations?|dessins|pr[ée]sentation|pr[ée]sent[ée]e?|introduction|avant-propos|notes|[ée]dition [ée]tablie|[ée]tablie?|choix|annot[ée]e?s?|commentaires?)\s+(?:et\s+\p{L}+\s+)?(?:de|par|d[’\'])\s*((?:\p{Lu}[\p{L}’\'\-]+)(?:\s+(?:de\s+|von\s+|van\s+)?\p{Lu}[\p{L}’\'\-]+){1,3})/u';
	$roles_livres = array();
	$roles_noms   = array();
	$roles_connus = 0;
	$roles_n      = 0;
	$trad_sans    = array();
	$edit_sans    = 0;
	$collections  = array();
	$editions     = array();
	foreach ( $livres as $id ) {
		if ( get_post_meta( $id, 'er_evenement', true ) ) {
			continue; // un billet de rencontre
		}
		$post  = get_post( $id );
		$texte = wp_strip_all_tags( html_entity_decode( $post->post_title . "\n" . $post->post_excerpt . "\n" . $post->post_content . "\n" . get_post_meta( $id, 'er_traducteur', true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( preg_match_all( $motif, $texte, $m, PREG_SET_ORDER ) ) {
			$vus = array();
			foreach ( $m as $x ) {
				$role = mb_strtolower( preg_replace( '/^(pr[ée]fac|illustr|pr[ée]sent|annot|[ée]tabli).*/u', '$1', $x[1] ), 'UTF-8' );
				$nom  = trim( $x[2] );
				if ( isset( $vus[ $role . $nom ] ) ) {
					continue;
				}
				$vus[ $role . $nom ]   = true;
				$roles_livres[ $role ] = ( isset( $roles_livres[ $role ] ) ? $roles_livres[ $role ] : 0 ) + 1;
				$roles_noms[ $nom ]    = ( isset( $roles_noms[ $nom ] ) ? $roles_noms[ $nom ] : 0 ) + 1;
				++$roles_n;
				if ( isset( $p['index'][ er_cle_recherche( $nom ) ] ) ) {
					++$roles_connus;
				}
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
		$col = trim( (string) get_post_meta( $id, 'er_collection', true ) );
		if ( '' !== $col ) {
			$cle                 = er_cle_recherche( $col );
			$collections[ $cle ] = array(
				'nom' => isset( $collections[ $cle ] ) ? $collections[ $cle ]['nom'] : $col,
				'n'   => ( isset( $collections[ $cle ] ) ? $collections[ $cle ]['n'] : 0 ) + 1,
				'graphies' => array_unique( array_merge( isset( $collections[ $cle ] ) ? $collections[ $cle ]['graphies'] : array(), array( $col ) ) ),
			);
		}
		// Le même livre en plusieurs éditions : mêmes auteurs, même titre (avant le sous-titre).
		$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'ids' ) );
		if ( $auteurs && ! is_wp_error( $auteurs ) ) {
			sort( $auteurs );
			$titre = er_cle_recherche( preg_split( '/\s[:—–-]\s|[:(]/u', $post->post_title )[0] );
			if ( strlen( $titre ) > 3 ) {
				$editions[ implode( ',', $auteurs ) . '|' . $titre ][] = $id;
			}
		}
	}
	$ln( sprintf( '  Rôles que la fiche ne connaît pas (préface, postface, illustrations, présentation, édition établie…) : %d mentions, dans les textes de livres ; %d désignent une personne qui a déjà sa page.', $roles_n, $roles_connus ) );
	$ln( er_audit_tete( $roles_livres, 10 ) );
	$ln( '  Les noms les plus cités dans ces rôles :' );
	$ln( er_audit_tete( $roles_noms, 15 ) );
	$ln( '  Livres avec un traducteur repris de Wix, mais personne posée (wp er roles l’a mis de côté) : ' . count( $trad_sans ) );
	foreach ( array_slice( $trad_sans, 0, 15, true ) as $t => $m ) {
		$ln( '    ' . $t . ' · « ' . mb_substr( $m, 0, 80 ) . ' »' );
	}
	$ln( '  Livres avec un éditeur repris de Wix mais sans éditeur posé : ' . $edit_sans );
	$grosses = array_filter( $collections, fn( $c ) => $c['n'] >= 3 );
	uasort( $grosses, fn( $a, $b ) => $b['n'] <=> $a['n'] );
	$ln( sprintf( '  Collections (une méta, sans page ni « Dans la même collection ») : %d noms, %d qui réunissent au moins trois livres.', count( $collections ), count( $grosses ) ) );
	foreach ( array_slice( $grosses, 0, 15 ) as $c ) {
		$ln( '    ' . $c['nom'] . ' (' . $c['n'] . ')' . ( count( $c['graphies'] ) > 1 ? ' — écrite aussi : ' . implode( ' / ', array_slice( $c['graphies'], 1, 3 ) ) : '' ) );
	}
	$multi = array_filter( $editions, fn( $ids ) => count( $ids ) > 1 );
	$ln( '  Le même livre en plusieurs fiches (mêmes auteurs, même titre) : ' . count( $multi ) . ' groupes, qu’aucune fiche ne relie aux autres.' );
	foreach ( array_slice( $multi, 0, 12 ) as $ids ) {
		$ln( '    ' . implode( ' / ', array_map( fn( $i ) => $titre_de( $i ) . ' [' . get_post_meta( $i, '_sku', true ) . ']', $ids ) ) );
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
	// La fiche d'un livre choisit ses rencontres, articles et vidéos par les auteurs, les personnes « Autour de » et les
	// sujets qu'ils partagent avec lui (er_recommandations) : la citation elle-même n'y compte pas.
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
				$autour = array_map( fn( $t ) => $t->term_id, function_exists( 'er_autour_de' ) ? er_autour_de( $livre ) : array() );
				if ( ! array_intersect( $siens, array_merge( $termes_de( $livre, 'auteur' ), $autour ) ) && ! array_intersect( $sujets_c, $termes_de( $livre, 'er_sujet' ) ) ) {
					$sens_unique[] = '    ' . $titre_de( $livre ) . ' ← ' . $sorte . ' · ' . $titre_de( $id );
				}
			}
		}
	}
	$ln( sprintf( '  Citations à sens unique : %d livres cités sur %d ne montrent pas, sur leur fiche, le contenu qui les cite (rien en commun pour la fiche : ni auteur, ni personne, ni sujet).', count( $sens_unique ), $cites ) );
	foreach ( array_slice( $sens_unique, 0, 15 ) as $l ) {
		$ln( $l );
	}
	// Les traducteurs et les directeurs : une rencontre avec le traducteur d'un livre n'apparaît pas sur la fiche du livre.
	$par_trad = 0;
	$exemples = array();
	foreach ( $livres as $id ) {
		$gens = array();
		foreach ( array( 'er_traduction', 'er_direction' ) as $tax ) {
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
		if ( $leurs ) {
			++$par_trad;
			if ( count( $exemples ) < 12 ) {
				$exemples[] = '    ' . $titre_de( $id ) . ' ← ' . $titre_de( $leurs[0] );
			}
		}
	}
	$ln( '  Livres dont le traducteur ou le directeur a des rencontres, des articles ou des vidéos que la fiche ne montre pas : ' . $par_trad );
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
	$ln( sprintf( '  %d vidéos de rencontres : %d sont aussi dans « Vidéos et podcasts », %d n’y sont pas (on ne les trouve que sur la page de la rencontre).', $dans + $hors, $dans, $hors ) );

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
