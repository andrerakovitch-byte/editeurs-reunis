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
		$metas = $wpdb->get_results( $wpdb->prepare( "SELECT post_id AS id, meta_key AS cle, meta_value AS val FROM {$wpdb->postmeta} WHERE post_id IN ($trous) AND meta_key IN ('_stock_status', '_sku', 'er_titre_fr', 'er_phrase', 'er_annee', 'er_editeur', '_thumbnail_id')", $lot ) );
		foreach ( $metas as $m ) {
			$id  = (int) $m->id;
			$val = trim( (string) $m->val );
			if ( '_stock_status' === $m->cle ) {
				$index['livres'][ $id ]['st'] = 'instock' === $val ? 1 : 0;
			} elseif ( '_sku' === $m->cle && '' !== $val ) {
				$index['livres'][ $id ]['sku'] = $val;
			} elseif ( 'er_titre_fr' === $m->cle && '' !== $val ) {
				$index['livres'][ $id ]['fr'] = trim( html_entity_decode( $val, ENT_QUOTES, 'UTF-8' ), ' «»"' );
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
	 * L'index et les notices en JSON, sur la sortie : wp er continuer exporter [--partie=index|notices] [--debut=0]
	 * [--n=400]. Rien que ce que le site montre déjà (des titres, des noms, des rayons, en stock ou non, les notices).
	 * Une ligne par morceau : l'index d'abord, puis les notices et les personnes qu'elles nomment, livre par livre.
	 *
	 * @param array $args  Rien.
	 * @param array $assoc partie, debut, n.
	 */
	WP_CLI::add_command(
		'er continuer exporter',
		function ( $args, $assoc ) {
			unset( $args );
			// Une erreur fatale (la mémoire, le temps) se dit aussi sur la sortie, que le workflow garde.
			register_shutdown_function(
				function () {
					$erreur = error_get_last();
					if ( $erreur && in_array( $erreur['type'], array( E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
						WP_CLI::line( '# Erreur : ' . $erreur['message'] . ' (' . basename( $erreur['file'] ) . ':' . $erreur['line'] . ')' );
					}
				}
			);
			WP_CLI::line( '# Mémoire : ' . ini_get( 'memory_limit' ) );
			$partie = isset( $assoc['partie'] ) ? (string) $assoc['partie'] : 'index';
			$index  = er_continuer_construire();
			WP_CLI::line( '# ' . count( $index['livres'] ) . ' livres, ' . count( $index['contenus'] ) . ' contenus, ' . round( memory_get_peak_usage() / 1048576 ) . ' Mo au plus' );
			if ( 'index' === $partie ) {
				WP_CLI::line( wp_json_encode( array( 'index' => $index ), JSON_UNESCAPED_UNICODE ) );
				return;
			}
			$ids   = array_slice( array_keys( $index['livres'] ), isset( $assoc['debut'] ) ? (int) $assoc['debut'] : 0, isset( $assoc['n'] ) ? (int) $assoc['n'] : 400 );
			$index = null;
			foreach ( $ids as $id ) {
				WP_CLI::line(
					wp_json_encode(
						array(
							'id'     => $id,
							'notice' => er_continuer_notice( $id ),
							'cites'  => er_continuer_cites( $id ),
						),
						JSON_UNESCAPED_UNICODE
					)
				);
				wp_cache_delete( $id, 'posts' );
				wp_cache_delete( $id, 'post_meta' );
			}
		}
	);
}
