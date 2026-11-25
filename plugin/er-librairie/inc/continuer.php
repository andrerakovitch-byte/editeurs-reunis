<?php
/**
 * « Pour aller plus loin » : sous la fiche d'un livre, des modules choisis pour ce livre-là (25 nov. 2026, la cinquième
 * page du canevas des fiches, « La fiche, modules compris »). Ils remplacent la bande « Autour de ce livre » et
 * l'étagère « Du même rayon, de la même époque ».
 *
 * Chaque module dit ce qui le relie au livre (l'autre langue, les autres volumes, le livre que cite la notice, ce
 * qu'on a écrit sur l'auteur, la personne dont parle le livre et son œuvre, le traducteur, les mêmes années) et ce
 * qu'il contient, en mots de libraire : « Deux romans, un journal et des poèmes d'Akhmatova ». Le choix et les mots
 * sont dans continuer-modules.php, des fonctions sans WordPress, qu'on peut faire tourner sur tout le catalogue ;
 * ce fichier-ci leur donne les données et montre le résultat.
 *
 * Les données : un index compact du catalogue (les livres, leurs personnes, rayons, époques, sujets, langues, le
 * stock ; les articles, rencontres et vidéos), refait en tâche de fond quand un livre change, gardé une semaine. Le
 * choix d'un livre est gardé un jour, et refait quand l'index l'est.
 *
 * `wp er continuer <slug>` montre les modules d'un livre ; `wp er continuer tous` ceux de tout le catalogue (des
 * titres, rien de privé) ; `wp er continuer exporter` l'index et les notices en JSON, pour relire le choix ailleurs.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/continuer-modules.php';

/* L'index du catalogue ------------------------------------------------------------------------------------------ */

/**
 * Les classements d'un livre que l'index retient : taxonomie → clé courte. Les rôles et « Autour de » sont des
 * classements miroirs des auteurs (les mêmes slugs) : l'index les ramène aux auteurs.
 */
function er_continuer_classements() {
	$c = array(
		'auteur'      => 'a',
		'er_autour'   => 'ab',
		'product_cat' => 'c',
		'langue'      => 'l',
		'er_periode'  => 'p',
		'er_ecriture' => 'e',
		'er_sujet'    => 's',
		'product_tag' => 'm',
		'er_editeur'  => 'ed',
	);
	foreach ( function_exists( 'er_roles_personnes' ) ? er_roles_personnes() : array() as $taxonomie => $role ) {
		$c[ $taxonomie ] = 'r_' . $role['cle'];
	}
	return array_filter( $c, 'taxonomy_exists', ARRAY_FILTER_USE_KEY );
}

/**
 * Construit l'index du catalogue.
 *
 * @return array quand, livres, personnes, rayons, sujets, contenus (voir continuer-modules.php).
 */
function er_continuer_construire() {
	global $wpdb;
	// Un seul index pour tout le site : les livres n'ont qu'une fiche, quelle que soit la langue de la page ; les
	// articles, rencontres et vidéos, traduits, gardent leur langue (lg), et la fiche prend ceux de la sienne.
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => 'er_evenement', 'compare' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'date',
			'order'          => 'DESC',
			'lang'           => '',
		)
	);
	$ids    = array_map( 'intval', $ids );
	$index  = array(
		'quand'     => time(),
		'livres'    => array(),
		'personnes' => array(),
		'rayons'    => array(),
		'sujets'    => array(),
		'contenus'  => array(),
	);

	// Les auteurs, par slug (les classements miroirs s'y ramènent) et par identifiant.
	$auteurs = get_terms(
		array(
			'taxonomy'   => 'auteur',
			'hide_empty' => false,
			'lang'       => '',
		)
	);
	$par_slug = array();
	foreach ( is_wp_error( $auteurs ) ? array() : $auteurs as $t ) {
		$par_slug[ $t->slug ] = (int) $t->term_id;
		$nom                  = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
		$graphies             = function_exists( 'er_autres_graphies' ) ? er_autres_graphies( $t->term_id ) : array();
		$index['personnes'][ (int) $t->term_id ] = array(
			'n'  => $nom,
			'g'  => $graphies,
			'nb' => (int) $t->count,
			'sl' => $t->slug,
		);
	}

	$classements = er_continuer_classements();
	$titres      = array();
	foreach ( $ids as $id ) {
		$titre           = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
		$titres[ $id ]   = $titre;
		$index['livres'][ $id ] = array(
			't'  => $titre,
			'k'  => function_exists( 'er_cle_recherche' ) ? er_cle_recherche( preg_split( '/\s[:—–-]\s|[:(]/u', $titre )[0] ) : '',
			'kt' => function_exists( 'er_cle_recherche' ) ? er_cle_recherche( $titre ) : '',
			'd'  => (int) get_post_timestamp( $id ),
		);
	}
	$editeurs = array();
	foreach ( array_chunk( $ids, 400 ) as $lot ) {
		$trous = implode( ',', array_fill( 0, count( $lot ), '%d' ) );
		$tx    = implode( ',', array_fill( 0, count( $classements ), '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders
		$lignes = $wpdb->get_results( $wpdb->prepare( "SELECT tr.object_id AS id, tt.taxonomy AS tax, t.term_id, t.slug, t.name FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE tr.object_id IN ($trous) AND tt.taxonomy IN ($tx) ORDER BY tr.term_order, t.term_id", array_merge( $lot, array_keys( $classements ) ) ) );
		foreach ( $lignes as $l ) {
			$cle = $classements[ $l->tax ];
			$id  = (int) $l->id;
			if ( 'auteur' === $l->tax ) {
				$index['livres'][ $id ]['a'][] = (int) $l->term_id;
			} elseif ( 'er_autour' === $l->tax || 0 === strpos( $cle, 'r_' ) ) {
				if ( isset( $par_slug[ $l->slug ] ) ) {
					if ( 'ab' === $cle ) {
						$index['livres'][ $id ]['ab'][] = $par_slug[ $l->slug ];
					} else {
						$index['livres'][ $id ]['r'][ substr( $cle, 2 ) ][] = $par_slug[ $l->slug ];
					}
				}
			} elseif ( in_array( $cle, array( 'c', 's' ), true ) ) {
				$index['livres'][ $id ][ $cle ][] = (int) $l->term_id;
			} elseif ( 'ed' === $cle ) {
				$editeurs[ $id ] = html_entity_decode( $l->name, ENT_QUOTES, 'UTF-8' );
			} else {
				$index['livres'][ $id ][ $cle ][] = $l->slug;
			}
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders
		$metas = $wpdb->get_results( $wpdb->prepare( "SELECT post_id AS id, meta_key AS cle, meta_value AS val FROM {$wpdb->postmeta} WHERE post_id IN ($trous) AND meta_key IN ('_stock_status', '_sku', 'er_titre_fr', 'er_phrase', 'er_annee', 'er_editeur', '_thumbnail_id', 'er_nature')", $lot ) );
		foreach ( $metas as $m ) {
			$id  = (int) $m->id;
			$val = trim( (string) $m->val );
			if ( '_stock_status' === $m->cle ) {
				$index['livres'][ $id ]['st'] = 'instock' === $val ? 1 : 0;
			} elseif ( '_sku' === $m->cle && '' !== $val ) {
				$index['livres'][ $id ]['sku'] = $val;
			} elseif ( 'er_titre_fr' === $m->cle && '' !== $val ) {
				$index['livres'][ $id ]['fr'] = trim( html_entity_decode( $val, ENT_QUOTES, 'UTF-8' ), ' «»"' );
				$index['livres'][ $id ]['kf'] = function_exists( 'er_cle_recherche' ) ? er_cle_recherche( preg_split( '/\s[:—–-]\s|[:(]/u', $index['livres'][ $id ]['fr'] )[0] ) : '';
			} elseif ( 'er_nature' === $m->cle && '' !== $val ) {
				$index['livres'][ $id ]['na'] = $val;
			} elseif ( 'er_phrase' === $m->cle && '' !== $val ) {
				$index['livres'][ $id ]['ph'] = 1;
			} elseif ( 'er_annee' === $m->cle && '' !== $val ) {
				$index['livres'][ $id ]['an'] = $val;
			} elseif ( 'er_editeur' === $m->cle && '' !== $val && ! isset( $editeurs[ $id ] ) ) {
				$editeurs[ $id ] = function_exists( 'er_notice_editeur' ) ? er_notice_editeur( html_entity_decode( wp_strip_all_tags( $val ), ENT_QUOTES, 'UTF-8' ) ) : $val;
			} elseif ( '_thumbnail_id' === $m->cle && (int) $val ) {
				$index['livres'][ $id ]['img'] = 1;
			}
		}
	}
	foreach ( $editeurs as $id => $nom ) {
		$index['livres'][ $id ]['ed'] = $nom;
	}
	// Une revue se reconnaît aussi à son titre (oeuvres-collectives.php), pour les numéros pas encore rangés.
	foreach ( $index['livres'] as $id => $l ) {
		if ( ! isset( $l['na'] ) && function_exists( 'er_titre_de_revue' ) && er_titre_de_revue( $l['t'] ) ) {
			$index['livres'][ $id ]['na'] = 'revue';
		}
	}

	// Les rayons et les sujets.
	$rayons = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'lang'       => '',
		)
	);
	foreach ( is_wp_error( $rayons ) ? array() : $rayons as $t ) {
		$index['rayons'][ (int) $t->term_id ] = array(
			'sl' => $t->slug,
			'n'  => html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ),
			'pa' => (int) $t->parent,
			'gr' => (string) get_term_meta( $t->term_id, 'er_groupe', true ),
		);
	}
	if ( taxonomy_exists( 'er_sujet' ) ) {
		$sujets = get_terms(
			array(
				'taxonomy'   => 'er_sujet',
				'hide_empty' => false,
				'lang'       => '',
			)
		);
		foreach ( is_wp_error( $sujets ) ? array() : $sujets as $t ) {
			$index['sujets'][ (int) $t->term_id ] = array(
				'sl' => $t->slug,
				'n'  => html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ),
			);
		}
	}

	// Les articles, les rencontres, les vidéos et les podcasts : leurs personnes, leurs sujets, les livres qu'ils citent.
	$par_ref = array();
	foreach ( $index['livres'] as $id => $l ) {
		$par_ref[ (string) $id ] = $id;
		if ( isset( $l['sku'] ) ) {
			$par_ref[ $l['sku'] ] = $id;
		}
	}
	$types    = array_values( array_filter( array( 'post', 'er_evenement', 'er_media' ), 'post_type_exists' ) );
	$contenus = $types ? array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => $types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'lang'           => '',
			)
		)
	) : array();
	foreach ( $contenus as $cid ) {
		$index['contenus'][ $cid ] = array(
			'ty' => get_post_type( $cid ),
			't'  => html_entity_decode( get_the_title( $cid ), ENT_QUOTES, 'UTF-8' ),
			'd'  => (int) get_post_timestamp( $cid ),
			'lg' => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $cid ) : '',
		);
	}
	$tx_contenus = array_values( array_filter( array( 'auteur', 'er_sujet' ), 'taxonomy_exists' ) );
	foreach ( array_chunk( $contenus, 400 ) as $lot ) {
		$trous = implode( ',', array_fill( 0, count( $lot ), '%d' ) );
		$tx    = implode( ',', array_fill( 0, count( $tx_contenus ), '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders
		$lignes = $wpdb->get_results( $wpdb->prepare( "SELECT tr.object_id AS id, tt.taxonomy AS tax, t.term_id FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE tr.object_id IN ($trous) AND tt.taxonomy IN ($tx)", array_merge( $lot, $tx_contenus ) ) );
		foreach ( $lignes as $l ) {
			$index['contenus'][ (int) $l->id ][ 'auteur' === $l->tax ? 'a' : 's' ][] = (int) $l->term_id;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders
		$metas = $wpdb->get_results( $wpdb->prepare( "SELECT post_id AS id, meta_key AS cle, meta_value AS val FROM {$wpdb->postmeta} WHERE post_id IN ($trous) AND meta_key IN ('er_livres', 'er_debut', 'er_fin', 'er_videos', 'er_genre')", $lot ) );
		foreach ( $metas as $m ) {
			$cid = (int) $m->id;
			$val = trim( (string) $m->val );
			if ( '' === $val ) {
				continue;
			}
			if ( 'er_livres' === $m->cle ) {
				foreach ( array_filter( array_map( 'trim', explode( ',', $val ) ) ) as $ref ) {
					if ( isset( $par_ref[ $ref ] ) ) {
						$index['contenus'][ $cid ]['lv'][] = $par_ref[ $ref ];
					}
				}
			} elseif ( 'er_videos' === $m->cle ) {
				$index['contenus'][ $cid ]['vi'] = 1;
			} elseif ( 'er_genre' === $m->cle ) {
				$index['contenus'][ $cid ]['ge'] = $val;
			} else {
				$index['contenus'][ $cid ][ 'er_debut' === $m->cle ? 'deb' : 'fin' ] = $val;
			}
		}
	}
	return $index;
}

/**
 * Le nom de la clé où l'index est gardé.
 */
function er_continuer_cle_index() {
	return 'er_continuer_index';
}

/**
 * L'index du catalogue : celui qui est gardé, même un peu ancien (la tâche de fond le refait) ; construit sur-le-champ
 * s'il n'y en a pas.
 *
 * @return array
 */
function er_continuer_index() {
	static $index = null;
	if ( null !== $index ) {
		return $index;
	}
	$garde = get_transient( er_continuer_cle_index() );
	if ( is_array( $garde ) && isset( $garde['livres'] ) ) {
		$index = $garde;
		return $index;
	}
	$index = er_continuer_construire();
	set_transient( er_continuer_cle_index(), $index, WEEK_IN_SECONDS );
	return $index;
}

/**
 * Un livre, une personne, un rayon a changé : l'index sera refait dans deux minutes, en tâche de fond (une seule fois
 * pour une série de changements).
 */
function er_continuer_perimer() {
	if ( ! wp_next_scheduled( 'er_continuer_refaire' ) ) {
		wp_schedule_single_event( time() + 2 * MINUTE_IN_SECONDS, 'er_continuer_refaire' );
	}
}

add_action(
	'er_continuer_refaire',
	function () {
		set_transient( er_continuer_cle_index(), er_continuer_construire(), WEEK_IN_SECONDS );
	}
);
add_action(
	'save_post_product',
	function ( $id, $post, $mise_a_jour ) {
		unset( $id, $post, $mise_a_jour );
		er_continuer_perimer();
	},
	10,
	3
);
add_action(
	'set_object_terms',
	function ( $objet ) {
		if ( in_array( get_post_type( $objet ), array( 'product', 'post', 'er_evenement', 'er_media' ), true ) ) {
			er_continuer_perimer();
		}
	}
);
add_action(
	'deleted_post',
	function ( $id, $post = null ) {
		if ( $post && in_array( $post->post_type, array( 'product', 'post', 'er_evenement', 'er_media' ), true ) ) {
			er_continuer_perimer();
		}
	},
	10,
	2
);
add_action( 'woocommerce_product_set_stock_status', 'er_continuer_perimer' );
foreach ( array( 'edited_auteur', 'edited_product_cat', 'edited_er_sujet', 'save_post_post', 'save_post_er_evenement', 'save_post_er_media' ) as $er_continuer_crochet ) {
	add_action(
		$er_continuer_crochet,
		function () {
			er_continuer_perimer();
		}
	);
}
unset( $er_continuer_crochet );

/* Le texte de la notice ----------------------------------------------------------------------------------------- */

/**
 * Les phrases de la notice d'un livre, chacune avec sa clé de recherche : [ [ phrase, clé ] ].
 *
 * @param int $id Le livre.
 */
function er_continuer_notice( $id ) {
	$post  = get_post( $id );
	$texte = $post ? $post->post_content . "\n" . $post->post_excerpt : '';
	$texte = html_entity_decode( wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br />' ), "\n", $texte ) ), ENT_QUOTES, 'UTF-8' );
	$texte = trim( (string) preg_replace( '/[ \t\x{00A0}]+/u', ' ', $texte ) );
	$sorties = array();
	foreach ( er_continuer_phrases_du_texte( $texte ) as $phrase ) {
		$sorties[] = array( $phrase, function_exists( 'er_cle_recherche' ) ? er_cle_recherche( $phrase ) : '' );
	}
	return $sorties;
}

/**
 * Les personnes que nomme la notice d'un livre : [ [ identifiant, intervient ] ] (er_auteurs_cites ; intervient : le
 * nom suit « avec », « par »…, c'est un traducteur, un préfacier plutôt qu'une personne dont on parle).
 *
 * @param int $id Le livre.
 */
function er_continuer_cites( $id ) {
	if ( ! function_exists( 'er_auteurs_cites' ) || ! function_exists( 'er_evt_auteurs_connus' ) ) {
		return array();
	}
	static $connus = null;
	if ( null === $connus ) {
		$connus = er_evt_auteurs_connus();
	}
	$post = get_post( $id );
	$cites = array();
	foreach ( $post ? er_auteurs_cites( $post->post_content . ' ' . $post->post_excerpt, $connus ) : array() as list( $t, $noms, $intervient ) ) {
		unset( $noms );
		$cites[] = array( (int) $t->term_id, $intervient ? 1 : 0 );
	}
	return $cites;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * L'index et les notices, sur la sortie : wp er continuer exporter. Rien que ce que le site montre déjà (des titres,
	 * des noms, des rayons, en stock ou non, les notices). Le JSON est compressé puis écrit en base64, en lignes
	 * courtes qui commencent par « J » : le journal du workflow ne coupe pas une ligne longue de cette façon.
	 */
	WP_CLI::add_command(
		'er continuer voir',
		/**
		 * Les modules de livres, en texte : wp er continuer voir <slug ou identifiant>… [--refaire] (--refaire :
		 * l'index et le choix refaits).
		 *
		 * @param array $args  Les slugs ou les identifiants.
		 * @param array $assoc refaire.
		 */
		function ( $args, $assoc ) {
			if ( isset( $assoc['refaire'] ) ) {
				delete_transient( er_continuer_cle_index() );
			}
			$index = er_continuer_index();
			foreach ( $args as $arg ) {
				$livre = ctype_digit( (string) $arg ) ? get_post( (int) $arg ) : get_page_by_path( sanitize_title( $arg ), OBJECT, 'product' );
				if ( ! $livre || 'product' !== $livre->post_type ) {
					WP_CLI::warning( 'Livre introuvable : ' . $arg );
					continue;
				}
				delete_transient( 'er_continuer_' . $livre->ID . '_' . ( function_exists( 'er_langue' ) ? er_langue() : 'fr' ) . '_' . (int) $index['quand'] );
				$choix = er_continuer( $livre->ID );
				WP_CLI::log( '' );
				WP_CLI::log( html_entity_decode( get_the_title( $livre ), ENT_QUOTES, 'UTF-8' ) . ' (' . $livre->ID . ')' );
				WP_CLI::log( '  ' . er_continuer_nu( $choix['ouverture'] ) );
				foreach ( $choix['modules'] as $m ) {
					WP_CLI::log( '  [' . $m['type'] . '] ' . er_continuer_nu( $m['sur'] ) . ' / ' . er_continuer_nu( $m['titre'] ) );
					if ( '' !== $m['ligne'] ) {
						WP_CLI::log( '      ' . er_continuer_nu( $m['ligne'] ) );
					}
					foreach ( $m['livres'] as $pid ) {
						WP_CLI::log( '      · ' . html_entity_decode( get_the_title( $pid ), ENT_QUOTES, 'UTF-8' ) );
					}
					foreach ( $m['contenus'] as $cid ) {
						WP_CLI::log( '      ◦ ' . html_entity_decode( get_the_title( $cid ), ENT_QUOTES, 'UTF-8' ) );
					}
				}
				$html = er_continuer_bloc( $livre->ID );
				WP_CLI::log( 'Rendu : ' . strlen( $html ) . ' octets, ' . substr_count( $html, 'class="er-carte__ajouter"' ) . ' « Ajouter » ; colonne « Du même auteur » : ' . substr_count( er_reco_bloc_auteur( $livre->ID ), '<li' ) . ' livres.' );
			}
		}
	);
	WP_CLI::add_command(
		'er continuer exporter',
		function () {
			// Une erreur fatale (la mémoire, le temps) se dit aussi sur la sortie, que le workflow garde.
			register_shutdown_function(
				function () {
					$erreur = error_get_last();
					if ( $erreur && in_array( $erreur['type'], array( E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
						WP_CLI::line( '# Erreur : ' . $erreur['message'] . ' (' . basename( $erreur['file'] ) . ':' . $erreur['line'] . ')' );
					}
				}
			);
			$index   = er_continuer_construire();
			$notices = array();
			$cites   = array();
			foreach ( array_keys( $index['livres'] ) as $id ) {
				$notices[ $id ] = er_continuer_notice( $id );
				$c              = er_continuer_cites( $id );
				if ( $c ) {
					$cites[ $id ] = $c;
				}
				wp_cache_delete( $id, 'posts' );
			}
			$json = wp_json_encode(
				array(
					'index'   => $index,
					'notices' => $notices,
					'cites'   => $cites,
				),
				JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
			);
			WP_CLI::line( '# ' . count( $index['livres'] ) . ' livres, ' . count( $index['contenus'] ) . ' contenus, ' . strlen( (string) $json ) . ' octets, ' . round( memory_get_peak_usage() / 1048576 ) . ' Mo au plus' );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- des données publiques, pour le journal.
			foreach ( str_split( base64_encode( (string) gzcompress( (string) $json, 9 ) ), 2000 ) as $morceau ) {
				WP_CLI::line( 'J' . $morceau );
			}
		}
	);
}

/* Le choix d'un livre ------------------------------------------------------------------------------------------- */

/**
 * Les modules d'un livre (er_continuer_choisir), gardés un jour, refaits quand l'index l'est. Sur une page qui
 * n'est pas en français, les lignes (des phrases françaises) se taisent.
 *
 * @param int $id Le livre.
 * @return array ouverture, modules.
 */
function er_continuer( $id ) {
	static $faits = array();
	$id = (int) $id;
	if ( isset( $faits[ $id ] ) ) {
		return $faits[ $id ];
	}
	$index  = er_continuer_index();
	$langue = function_exists( 'er_langue' ) ? er_langue() : 'fr';
	// La clé suit l'index et le moteur : un déploiement qui change les mots ne laisse pas les anciens en place un jour.
	$cle    = 'er_continuer_' . $id . '_' . $langue . '_' . ( isset( $index['quand'] ) ? (int) $index['quand'] : 0 ) . '_' . (int) filemtime( __DIR__ . '/continuer-modules.php' );
	$garde  = get_transient( $cle );
	if ( is_array( $garde ) && isset( $garde['modules'] ) ) {
		$faits[ $id ] = $garde;
		return $garde;
	}
	$choix = er_continuer_choisir(
		$id,
		$index,
		array(
			'notice'     => er_continuer_notice( $id ),
			'cites'      => er_continuer_cites( $id ),
			'langue'     => $langue,
			'maintenant' => time(),
		)
	);
	if ( 'fr' !== $langue ) {
		$choix['ouverture'] = '';
		foreach ( $choix['modules'] as $k => $m ) {
			$choix['modules'][ $k ]['ligne'] = '';
		}
	}
	set_transient( $cle, $choix, DAY_IN_SECONDS );
	$faits[ $id ] = $choix;
	return $choix;
}

/**
 * Les livres que montrent les modules d'un livre (« Du même auteur », dans la colonne, ne les redit pas).
 *
 * @param int $id Le livre.
 * @return int[]
 */
function er_continuer_livres_montres( $id ) {
	$ids = array();
	foreach ( er_continuer( $id )['modules'] as $m ) {
		$ids = array_merge( $ids, array_map( 'intval', $m['livres'] ) );
	}
	return array_values( array_unique( $ids ) );
}

/* L'affichage --------------------------------------------------------------------------------------------------- */

/**
 * Un texte du choix en HTML : échappé, les titres d'œuvre ({{…}}) et les citations ([[…]]) en italique, le
 * cyrillique dans sa langue.
 *
 * @param string $texte Le texte.
 */
function er_continuer_html( $texte ) {
	$html = str_replace( array( '{{', '}}', '[[', ']]' ), array( '<em>', '</em>', '<em>', '</em>' ), esc_html( (string) $texte ) );
	return er_continuer_cyrillique( $html );
}

/**
 * Le cyrillique d'un texte déjà échappé, dans une balise de langue (pour la lecture à voix haute et la césure).
 *
 * @param string $html Le texte.
 */
function er_continuer_cyrillique( $html ) {
	return (string) preg_replace( '/(\p{Cyrillic}(?:[\p{Cyrillic}0-9 .,:;!?«»()\x{2013}\x{2014}\x{2019}-]*\p{Cyrillic})?)/u', '<span lang="ru">$1</span>', (string) $html );
}

/**
 * Le prix d'un livre et, s'il est en stock, « Ajouter » (au panier sans quitter la page, assets/js/rayonnage.js) ;
 * rien pour un livre épuisé, que son prix dit déjà.
 *
 * @param WC_Product $p     Le livre.
 * @param string     $texte Le texte du bouton.
 */
function er_continuer_achat( $p, $texte = '' ) {
	$bouton = $p->is_in_stock() && $p->is_purchasable() && $p->is_type( 'simple' ) && function_exists( 'er_carte_bouton' ) ? er_carte_bouton( $p, $texte ) : '';
	return er_reco_prix( $p ) . $bouton;
}

/**
 * Les mots d'un livre dans une carte : la mention, le titre (et sa traduction sous un titre russe), l'auteur, la
 * phrase.
 *
 * @param WC_Product $p     Le livre.
 * @param string     $bloc  La classe de base (er-pl-vignette, er-pl-vedette, er-pl-ligne).
 * @param bool       $titre Montrer le titre (la vedette d'un module dont le titre est déjà celui du livre ne le
 *                          redit pas).
 */
function er_continuer_mots_livre( $p, $bloc, $titre = true ) {
	$id       = $p->get_id();
	$lien     = get_permalink( $id );
	$mentions = wp_get_post_terms( $id, 'product_tag' );
	$mention  = $mentions && ! is_wp_error( $mentions ) ? '<span class="er-reco-mention' . ( 'tres-rare' === $mentions[0]->slug ? ' er-reco-mention--rare' : '' ) . '">' . esc_html( html_entity_decode( $mentions[0]->name, ENT_QUOTES, 'UTF-8' ) ) . '</span>' : '';
	$nom      = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	$glose    = preg_match( '/\p{Cyrillic}/u', $nom ) && ( ! function_exists( 'er_langue' ) || 'ru' !== er_langue() ) ? trim( (string) $p->get_meta( 'er_titre_fr' ), ' «»"' ) : '';
	$auteurs  = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
	$auteurs  = $auteurs && ! is_wp_error( $auteurs ) ? implode( ', ', array_map( fn( $a ) => html_entity_decode( $a, ENT_QUOTES, 'UTF-8' ), $auteurs ) ) : '';
	$phrase   = function_exists( 'er_reco_phrase' ) ? er_reco_phrase( $id ) : '';
	return $mention
		. ( $titre ? '<a class="' . $bloc . '__titre" href="' . esc_url( $lien ) . '">' . er_continuer_cyrillique( esc_html( wptexturize( $nom ) ) ) . '</a>' : '' )
		. ( '' !== $glose ? '<span class="' . $bloc . '__glose">«' . "\u{00A0}" . esc_html( $glose ) . "\u{00A0}" . '»</span>' : '' )
		. ( '' !== $auteurs ? '<span class="' . $bloc . '__auteur">' . er_continuer_cyrillique( esc_html( $auteurs ) ) . '</span>' : '' )
		. ( '' !== $phrase ? '<span class="' . $bloc . '__phrase">' . esc_html( $phrase ) . '</span>' : '' );
}

/**
 * La case d'un livre : sa couverture entière (jamais recadrée), posée sur le fond doux (ou blanc sur un module en fond
 * doux). Le lien de la couverture double celui du titre : on ne le lit qu'une fois, sauf quand le titre n'est pas
 * montré.
 *
 * @param WC_Product $p       Le livre.
 * @param string     $classe  La classe.
 * @param bool       $seul    Le seul lien vers le livre.
 * @param string     $tailles L'attribut sizes de l'image.
 */
function er_continuer_case( $p, $classe, $seul = false, $tailles = '(max-width: 781px) 40vw, 220px' ) {
	$titre = html_entity_decode( get_the_title( $p->get_id() ), ENT_QUOTES, 'UTF-8' );
	$image = function_exists( 'er_carte_couverture' ) ? er_carte_couverture( $p, $tailles ) : er_reco_couverture( $p );
	return '<a class="' . $classe . '" href="' . esc_url( get_permalink( $p->get_id() ) ) . '"' . ( $seul ? ' aria-label="' . esc_attr( $titre ) . '"' : ' tabindex="-1" aria-hidden="true"' ) . '>' . $image . '</a>';
}

/**
 * Un livre en vignette posée : la case, puis les mots, le prix et « Ajouter ».
 *
 * @param int $pid Le livre.
 */
function er_continuer_vignette( $pid ) {
	$p = wc_get_product( $pid );
	if ( ! $p ) {
		return '';
	}
	return '<li class="er-pl-vignette">' . er_continuer_case( $p, 'er-pl-vignette__case' )
		. '<div class="er-pl-vignette__texte">' . er_continuer_mots_livre( $p, 'er-pl-vignette' ) . '<span class="er-pl-vignette__achat">' . er_continuer_achat( $p ) . '</span></div></li>';
}

/**
 * Un seul livre en grand : la case à gauche, la phrase en grand à droite, le prix et « Ajouter au panier ».
 *
 * @param int  $pid   Le livre.
 * @param bool $titre Montrer le titre.
 */
function er_continuer_vedette( $pid, $titre = true ) {
	$p = wc_get_product( $pid );
	if ( ! $p ) {
		return '';
	}
	return '<div class="er-pl-vedette">' . er_continuer_case( $p, 'er-pl-vedette__case', ! $titre, '(max-width: 781px) 60vw, 380px' )
		. '<div class="er-pl-vedette__texte">' . er_continuer_mots_livre( $p, 'er-pl-vedette', $titre )
		. '<div class="er-pl-vedette__achat">' . er_continuer_achat( $p, __( 'Ajouter au panier', 'er-librairie' ) ) . '</div></div></div>';
}

/**
 * Un livre en ligne : la couverture, les mots, le prix et « Ajouter » à droite (les modules discrets).
 *
 * @param int $pid Le livre.
 */
function er_continuer_ligne_livre( $pid ) {
	$p = wc_get_product( $pid );
	if ( ! $p ) {
		return '';
	}
	return '<li class="er-pl-ligne">' . er_continuer_case( $p, 'er-pl-ligne__case', false, '90px' )
		. '<div class="er-pl-ligne__texte">' . er_continuer_mots_livre( $p, 'er-pl-ligne' ) . '</div>'
		. '<div class="er-pl-ligne__achat">' . er_continuer_achat( $p ) . '</div></li>';
}

/**
 * Un article, une rencontre, une vidéo ou une exposition du centre, en carte sur le bleu nuit du centre : la sorte, la
 * grande initiale en cyrillique, le titre, la date et où aller.
 *
 * @param int $cid Le contenu.
 */
function er_continuer_carte_centre( $cid ) {
	$type  = (string) get_post_type( $cid );
	$titre = html_entity_decode( get_the_title( $cid ), ENT_QUOTES, 'UTF-8' );
	if ( 'er_evenement' === $type ) {
		$passee = function_exists( 'er_evt_passee' ) && er_evt_passee( $cid );
		$genre  = __( 'Rencontre au centre', 'er-librairie' );
		$quand  = function_exists( 'er_evt_quand' ) ? er_evt_quand( $cid, false ) : get_the_date( 'j F Y', $cid );
		$suite  = $passee ? ( get_post_meta( $cid, 'er_videos', true ) ? __( 'Revoir la rencontre', 'er-librairie' ) : __( 'La rencontre', 'er-librairie' ) ) : __( 'Venir à la rencontre', 'er-librairie' );
	} elseif ( 'er_media' === $type ) {
		$podcast = 'podcast' === get_post_meta( $cid, 'er_genre', true );
		$genre   = $podcast ? __( 'Podcast du centre', 'er-librairie' ) : __( 'Vidéo du centre', 'er-librairie' );
		$quand   = get_the_date( 'j F Y', $cid );
		$suite   = $podcast ? __( 'Écouter', 'er-librairie' ) : __( 'Voir la vidéo', 'er-librairie' );
	} elseif ( defined( 'ER_EXPO' ) && ER_EXPO === $type ) {
		$genre = __( 'Exposition au centre', 'er-librairie' );
		$quand = function_exists( 'er_expo_quand' ) ? er_expo_quand( $cid ) : '';
		$suite = function_exists( 'er_expo_etat' ) && 'passee' === er_expo_etat( $cid ) ? __( 'Revoir l’exposition', 'er-librairie' ) : __( 'Voir l’exposition', 'er-librairie' );
	} else {
		$genre = __( 'Le journal du centre', 'er-librairie' );
		$quand = get_the_date( 'j F Y', $cid );
		$suite = __( 'Lire l’article', 'er-librairie' );
	}
	$gens   = wp_get_post_terms( $cid, 'auteur' );
	$lettre = $gens && ! is_wp_error( $gens ) && function_exists( 'er_reco_initiale' ) ? er_reco_initiale( $gens[0] ) : ( function_exists( 'er_initiale_cyrillique' ) ? er_initiale_cyrillique( $titre, array(), false ) : '' );
	$quand  = trim( (string) $quand );
	return '<li class="er-pl-carte er-pl-carte--' . esc_attr( $type ) . '"><a href="' . esc_url( get_permalink( $cid ) ) . '">'
		. '<span class="er-pl-carte__genre"><span class="er-reco-point" aria-hidden="true"></span>' . esc_html( $genre ) . '</span>'
		. ( '' !== $lettre ? '<span class="er-pl-carte__lettre" lang="ru" aria-hidden="true">' . esc_html( $lettre ) . '</span>' : '' )
		. '<span class="er-pl-carte__titre">' . er_continuer_cyrillique( esc_html( $titre ) ) . '</span>'
		. '<span class="er-pl-carte__meta">' . esc_html( ( '' !== $quand ? $quand . ' · ' : '' ) . $suite ) . ' →</span></a></li>';
}

/**
 * Le lien d'un module vers la page où aller plus loin encore : la personne, l'époque, le sujet, le rayon.
 *
 * @param array $m Le module.
 */
function er_continuer_lien( $m ) {
	if ( empty( $m['lien'] ) || count( $m['lien'] ) < 2 ) {
		return '';
	}
	list( $taxonomie, $terme ) = $m['lien'];
	if ( ! taxonomy_exists( $taxonomie ) ) {
		return '';
	}
	$t = is_numeric( $terme ) ? get_term( (int) $terme, $taxonomie ) : get_term_by( 'slug', (string) $terme, $taxonomie );
	if ( ! $t || is_wp_error( $t ) ) {
		return '';
	}
	$url = get_term_link( $t );
	if ( is_wp_error( $url ) ) {
		return '';
	}
	$mots = array(
		'auteur'      => __( 'Sa page', 'er-librairie' ),
		'er_periode'  => __( 'Toute l’époque', 'er-librairie' ),
		'er_ecriture' => __( 'Toute l’époque', 'er-librairie' ),
		'er_sujet'    => __( 'Tout le thème', 'er-librairie' ),
		'product_cat' => __( 'Tout le rayon', 'er-librairie' ),
	);
	return '<p class="er-lien-fleche er-pl-module__lien"><a href="' . esc_url( $url ) . '">' . esc_html( isset( $mots[ $taxonomie ] ) ? $mots[ $taxonomie ] : __( 'Voir tout', 'er-librairie' ) ) . '</a></p>';
}

/**
 * Le corps d'un module, selon sa forme.
 *
 * @param array $m Le module.
 */
function er_continuer_corps( $m ) {
	$livres = array_map( 'intval', $m['livres'] );
	$cartes = '';
	foreach ( array_map( 'intval', $m['contenus'] ) as $cid ) {
		$cartes .= er_continuer_carte_centre( $cid );
	}
	switch ( $m['forme'] ) {
		case 'vedette':
			return ( $livres ? er_continuer_vedette( $livres[0], empty( $m['sans_titre'] ) ) : '' ) . ( $cartes ? '<ul class="er-pl-cartes">' . $cartes . '</ul>' : '' );
		case 'deux-langues':
			$fr = '';
			$ru = '';
			$n  = array( 0, 0 );
			foreach ( $livres as $pid ) {
				if ( has_term( 'russe', 'langue', $pid ) ) {
					$ru .= er_continuer_vignette( $pid );
					++$n[1];
				} else {
					$fr .= er_continuer_vignette( $pid );
					++$n[0];
				}
			}
			// Les deux colonnes se partagent la même grille : un livre russe a la largeur d'un livre français.
			return '<div class="er-pl-langues" style="--fr:' . $n[0] . ';--ru:' . $n[1] . '"><div class="er-pl-langues__fr"><h4 class="er-pl-langues__titre">' . esc_html__( 'En français', 'er-librairie' ) . '</h4><ul class="er-pl-vignettes">' . $fr . '</ul></div>'
				. '<div class="er-pl-langues__ru"><h4 class="er-pl-langues__titre" lang="ru">По-русски</h4><ul class="er-pl-vignettes">' . $ru . '</ul></div></div>'
				. ( $cartes ? '<ul class="er-pl-cartes">' . $cartes . '</ul>' : '' );
		case 'ligne':
			$lignes = '';
			foreach ( $livres as $pid ) {
				$lignes .= er_continuer_ligne_livre( $pid );
			}
			return ( $lignes ? '<ul class="er-pl-lignes">' . $lignes . '</ul>' : '' ) . ( $cartes ? '<ul class="er-pl-cartes">' . $cartes . '</ul>' : '' );
		case 'contenus':
			return '<ul class="er-pl-cartes">' . $cartes . '</ul>';
		default:
			$vignettes = '';
			foreach ( $livres as $pid ) {
				$vignettes .= er_continuer_vignette( $pid );
			}
			return '<ul class="er-pl-vignettes">' . $vignettes . $cartes . '</ul>';
	}
}

/**
 * « Pour aller plus loin » sous la fiche d'un livre : l'ouverture, puis les modules, puis la mention des phrases.
 * Un livre sans module n'a pas de section.
 *
 * @param int $id Le livre.
 */
function er_continuer_bloc( $id ) {
	$choix   = er_continuer( $id );
	$modules = $choix['modules'];
	// L'exposition du centre à laquelle le livre est rattaché (expositions.php), en module à part, après le premier.
	if ( function_exists( 'er_expos_de' ) ) {
		$expos = er_expos_de( $id );
		if ( $expos ) {
			$expo = array(
				'type'       => 'exposition',
				'sur'        => __( 'Au centre culturel', 'er-librairie' ),
				'titre'      => _n( 'L’exposition', 'Les expositions', count( $expos ), 'er-librairie' ),
				'ligne'      => '',
				'livres'     => array(),
				'contenus'   => array_map( fn( $e ) => (int) $e->ID, $expos ),
				'forme'      => 'contenus',
				'secondaire' => true,
				'fond'       => ! $modules,
				'lien'       => array(),
				'sans_titre' => false,
			);
			array_splice( $modules, $modules ? 1 : 0, 0, array( $expo ) );
		}
	}
	if ( ! $modules ) {
		return '';
	}
	$html    = '';
	$phrases = array();
	foreach ( $modules as $k => $m ) {
		$classes = array( 'er-pl-module', 'er-pl-module--' . $m['forme'], 'er-pl-module--' . $m['type'] );
		if ( ! empty( $m['fond'] ) ) {
			$classes[] = 'er-pl-module--fond';
		}
		if ( ! empty( $m['secondaire'] ) ) {
			$classes[] = 'er-pl-module--secondaire';
		}
		$titre_id = 'er-module-' . ( $k + 1 );
		$html    .= '<section class="' . esc_attr( implode( ' ', $classes ) ) . '" aria-labelledby="' . esc_attr( $titre_id ) . '"><div class="er-pl-module__g">'
			. '<div class="er-pl-module__tete"><p class="er-pl-module__sur">' . er_continuer_html( er_continuer_maj( $m['sur'] ) ) . '</p>'
			. '<h3 class="er-pl-module__titre" id="' . esc_attr( $titre_id ) . '">' . er_continuer_html( er_continuer_maj( $m['titre'] ) ) . '</h3>'
			. ( '' !== $m['ligne'] ? '<p class="er-pl-module__ligne">' . er_continuer_html( $m['ligne'] ) . '</p>' : '' )
			. er_continuer_lien( $m ) . '</div>'
			. '<div class="er-pl-module__corps">' . er_continuer_corps( $m ) . '</div></div></section>';
		$phrases = array_merge( $phrases, array_map( 'intval', $m['livres'] ) );
	}
	$mention = function_exists( 'er_reco_mention_phrases' ) ? er_reco_mention_phrases( $phrases ) : '';
	return '<section class="er-plus-loin alignfull" aria-labelledby="er-plus-loin-titre">'
		. '<div class="er-plus-loin__ouverture"><p class="er-plus-loin__sur">' . esc_html__( 'Pour aller plus loin', 'er-librairie' ) . '</p>'
		. '<h2 class="er-plus-loin__titre" id="er-plus-loin-titre">' . esc_html__( 'D’un livre à l’autre', 'er-librairie' ) . '</h2>'
		. ( '' !== $choix['ouverture'] ? '<p class="er-plus-loin__resume">' . er_continuer_html( $choix['ouverture'] ) . '</p>' : '' ) . '</div>'
		. $html
		. ( '' !== $mention ? '<div class="er-plus-loin__mention">' . $mention . '</div>' : '' )
		. '</section>';
}

add_action(
	'init',
	function () {
		register_block_type(
			'er/fiche-continuer',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					unset( $attributs, $contenu );
					$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : get_the_ID();
					return $id && 'product' === get_post_type( $id ) && function_exists( 'wc_get_product' ) ? er_continuer_bloc( $id ) : '';
				},
			)
		);
	}
);
