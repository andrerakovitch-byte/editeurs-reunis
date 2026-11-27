<?php
/**
 * « Filtrer par » : la colonne de filtres des pages de la librairie (maquette du 24 novembre 2026, canevas
 * « Librairie — le module « Filtrer par » »).
 *
 * Sur toute la librairie, un rayon, un auteur, une langue, une maison, une collection, une mention ou une recherche :
 * la langue, l'époque (dont parle le livre, ou de son écriture), le genre (les sous-rayons d'un rayon) ou le rayon,
 * le sujet, l'auteur, la maison d'édition, l'année de publication (par décennie) et la disponibilité. Chaque option
 * dit combien de livres resteraient si on la cochait ; une option qui n'en laisserait aucun est grisée. Dans une
 * facette, les options s'additionnent (français OU bilingue) ; d'une facette à l'autre, elles se cumulent.
 *
 * Une facette qui ne partage pas la page n'y est pas : pas d'« Auteur » sur la page d'un auteur, pas de « Langue »
 * sur celle d'une langue, pas de « Maison d'édition » sur celle d'un éditeur. Moins de six livres : pas de colonne.
 *
 * Le filtre est dans l'adresse (?langues=francais,bilingue-russe-francais&epoques=annees-1920-et-1930), qui se partage.
 * Les paramètres sont au pluriel : « langue » et « auteur » sont déjà ceux des classements de WordPress.
 * Le calcul se fait ici, en PHP, sur les livres de la page (quelques centaines au plus) : les mêmes règles donnent les
 * comptes et les livres montrés (la requête principale reçoit leur liste, post__in), sans que les deux divergent.
 *
 * Deux blocs : er/facettes (la colonne) et er/facettes-puces (les filtres en cours, au-dessus des livres). Le thème
 * les place (patterns/librairie.php), les habille (19-facettes.css) et les fait vivre sans recharger la page
 * (assets/js/facettes.js) ; sans JavaScript, le formulaire s'envoie avec son bouton.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les facettes, dans l'ordre de la colonne : clé → paramètre de l'adresse, libellé, forme. L'époque et le sujet, les
 * plus fins, viennent à la fin (demandé le 24 nov. 2026).
 *
 * @return array
 */
function er_facettes_definitions() {
	return array(
		'rayon'    => array( 'rayons', __( 'Rayon', 'er-librairie' ), 'liste' ),
		'langue'   => array( 'langues', __( 'Langue', 'er-librairie' ), 'liste' ),
		'genre'    => array( 'genres', __( 'Genre', 'er-librairie' ), 'liste' ),
		'auteur'   => array( 'auteurs', __( 'Auteur', 'er-librairie' ), 'recherche' ),
		'maison'   => array( 'maisons', __( 'Maison d’édition', 'er-librairie' ), 'recherche' ),
		'decennie' => array( 'decennies', __( 'Année de publication', 'er-librairie' ), 'histo' ),
		'dispo'    => array( 'dispo', __( 'Disponibilité', 'er-librairie' ), 'liste' ),
		'epoque'   => array( 'epoques', __( 'Époque', 'er-librairie' ), 'frise' ),
		'ecrit'    => array( 'ecriture', __( 'Époque', 'er-librairie' ), 'frise' ),
		'sujet'    => array( 'sujets', __( 'Sujet', 'er-librairie' ), 'pilules' ),
	);
}

/**
 * Lit le filtre dans l'adresse : chaque facette, une liste de slugs (« a,b » ou « a[]=…&a[]=… ») ; sans_date, faux
 * seulement avec « sans-date=non ».
 *
 * @param array $get Les paramètres de l'adresse.
 * @return array
 */
function er_facettes_selection( $get ) {
	$sel = array();
	foreach ( er_facettes_definitions() as $cle => $def ) {
		$v = isset( $get[ $def[0] ] ) ? wp_unslash( $get[ $def[0] ] ) : array();
		$v = is_array( $v ) ? $v : explode( ',', (string) $v );
		$v = array_values( array_unique( array_filter( array_map( fn( $s ) => sanitize_title( (string) $s ), $v ), 'strlen' ) ) );
		$sel[ $cle ] = array_slice( $v, 0, 60 );
	}
	$sans             = isset( $get['sans-date'] ) ? wp_unslash( $get['sans-date'] ) : 'oui';
	$sel['sans_date'] = 'non' !== ( is_array( $sans ) ? end( $sans ) : $sans );
	return $sel;
}

/**
 * Un filtre est-il posé ?
 *
 * @param array $sel er_facettes_selection().
 */
function er_facettes_actives( $sel ) {
	foreach ( array_keys( er_facettes_definitions() ) as $cle ) {
		if ( ! empty( $sel[ $cle ] ) ) {
			return true;
		}
	}
	return empty( $sel['sans_date'] );
}

/**
 * Un livre passe-t-il le filtre, sans compter une facette (celle dont on fait les comptes) ?
 *
 * @param array  $livre Facette → clés du livre.
 * @param array  $sel   er_facettes_selection().
 * @param string $sauf  La facette à ne pas compter.
 */
function er_facettes_passe( $livre, $sel, $sauf = '' ) {
	foreach ( array_keys( er_facettes_definitions() ) as $f ) {
		if ( $f === $sauf || 'decennie' === $f || empty( $sel[ $f ] ) ) {
			continue;
		}
		if ( ! array_intersect( $sel[ $f ], $livre[ $f ] ?? array() ) ) {
			return false;
		}
	}
	if ( 'decennie' !== $sauf ) {
		$d = $livre['decennie'] ?? array( 'sans' );
		if ( in_array( 'sans', $d, true ) ) {
			return ! empty( $sel['sans_date'] );
		}
		if ( ! empty( $sel['decennie'] ) && ! array_intersect( $sel['decennie'], $d ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Les comptes d'une facette : clé → nombre de livres qui resteraient si on la cochait.
 *
 * @param array  $livres Identifiant → facette → clés.
 * @param array  $sel    er_facettes_selection().
 * @param string $f      La facette.
 * @return int[]
 */
function er_facettes_compter( $livres, $sel, $f ) {
	$c = array();
	foreach ( $livres as $livre ) {
		if ( er_facettes_passe( $livre, $sel, $f ) ) {
			foreach ( $livre[ $f ] ?? array() as $k ) {
				$c[ $k ] = ( $c[ $k ] ?? 0 ) + 1;
			}
		}
	}
	return $c;
}

/**
 * Les livres qui passent le filtre.
 *
 * @param array $livres Identifiant → facette → clés.
 * @param array $sel    er_facettes_selection().
 * @return int[]
 */
function er_facettes_resultats( $livres, $sel ) {
	return array_keys( array_filter( $livres, fn( $l ) => er_facettes_passe( $l, $sel ) ) );
}

/**
 * La décennie d'une année d'édition écrite à la main (« 2-е изд., 1987 » → 1980), ou « sans ».
 *
 * @param string $annee Le champ er_annee.
 */
function er_facettes_decennie( $annee ) {
	return preg_match( '/\b(1[6-9]\d|20[0-4])\d\b/', (string) $annee, $m ) ? $m[1] . '0' : 'sans';
}

/**
 * Est-on sur une page de la librairie que la colonne filtre ?
 *
 * @param WP_Query|null $q La requête (la principale par défaut).
 */
function er_facettes_page( $q = null ) {
	$q = $q ? $q : $GLOBALS['wp_query'];
	if ( ! $q || is_admin() ) {
		return false;
	}
	if ( $q->is_tax( array( 'product_cat', 'product_tag', 'langue', 'auteur', 'er_editeur', 'er_collection' ) ) || $q->is_post_type_archive( 'product' ) ) {
		return true;
	}
	return $q->is_search() && in_array( 'product', (array) $q->get( 'post_type' ), true );
}

/**
 * Le contexte de la page : le terme qu'elle montre, les facettes qu'elle garde, et pour un rayon ses sous-rayons de
 * genre (sans ceux d'époque, que la frise dit mieux).
 *
 * @param WP_Query $q La requête principale.
 * @return array
 */
function er_facettes_contexte( $q ) {
	$terme   = $q->is_tax() || $q->is_category() ? $q->get_queried_object() : null;
	$terme   = $terme instanceof WP_Term ? $terme : null;
	$sans    = array( 'genre' );
	$enfants = array();
	$libelle = __( 'Genre', 'er-librairie' );
	if ( $terme && 'product_cat' === $terme->taxonomy ) {
		$sans = array( 'rayon' );
		if ( ! $terme->parent ) {
			$tous   = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $terme->term_id, 'hide_empty' => false ) );
			$tous   = is_wp_error( $tous ) ? array() : $tous;
			$genres = array();
			foreach ( $tous as $e ) {
				$g = (string) get_term_meta( $e->term_id, 'er_groupe', true );
				if ( preg_match( '/époque|période/iu', $g ) ) {
					continue;
				}
				$enfants[ $e->slug ] = $e;
				if ( preg_match( '/genre/iu', $g ) ) {
					$genres[ $e->slug ] = $e;
				}
			}
			if ( $genres ) {
				$enfants = $genres;
			} else {
				$libelle = __( 'Rubrique', 'er-librairie' );
			}
		}
		if ( ! $enfants ) {
			$sans[] = 'genre';
		}
	}
	$propres = array(
		'auteur'     => 'auteur',
		'langue'     => 'langue',
		'er_editeur' => 'maison',
	);
	if ( $terme && isset( $propres[ $terme->taxonomy ] ) ) {
		$sans[] = $propres[ $terme->taxonomy ];
	}
	return array(
		'terme'   => $terme,
		'sans'    => $sans,
		'enfants' => $enfants,
		'genre'   => $libelle,
	);
}

/**
 * Les livres de la page, sans le filtre : ceux du terme qu'elle montre (et de ses sous-termes) ou de la recherche,
 * publiés, visibles dans le catalogue, pas les billets des rencontres.
 *
 * @param WP_Query $q        La requête principale.
 * @param array    $contexte er_facettes_contexte().
 * @return int[]
 */
function er_facettes_ids( $q, $contexte ) {
	$taxes = array( 'relation' => 'AND' );
	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$vis = wc_get_product_visibility_term_ids();
		// Comme WooCommerce : le catalogue écarte ce qui est caché du catalogue, la recherche ce qui l'est de la recherche.
		$cache = $vis[ $q->is_search() ? 'exclude-from-search' : 'exclude-from-catalog' ] ?? 0;
		if ( $cache ) {
			$taxes[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( $cache ),
				'operator' => 'NOT IN',
			);
		}
	}
	if ( $contexte['terme'] ) {
		$taxes[] = array(
			'taxonomy'         => $contexte['terme']->taxonomy,
			'terms'            => array( $contexte['terme']->term_id ),
			'include_children' => true,
		);
	}
	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		'tax_query'              => $taxes, // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'relation' => 'OR',
			array(
				'key'     => 'er_evenement',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'   => 'er_evenement',
				'value' => '',
			),
		),
		'er_facettes_base'       => true,
		'suppress_filters'       => false,
	);
	if ( function_exists( 'pll_current_language' ) && pll_current_language() ) {
		$args['lang'] = pll_current_language();
	}
	if ( $q->is_search() ) {
		// La recherche du site passe par son index (recherche.php : les trois écritures, les auteurs), pas par celle
		// de WordPress : la colonne compte les mêmes livres, dans le même ordre.
		if ( function_exists( 'er_livres_trouves' ) ) {
			$trouves          = er_livres_trouves( $q->get( 's' ) );
			$args['post__in'] = $trouves ? $trouves : array( 0 );
			$args['orderby']  = 'post__in';
		} else {
			$args['s'] = $q->get( 's' );
		}
	}
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'relation' => 'AND',
			$args['meta_query'],
			array(
				'key'   => '_stock_status',
				'value' => 'instock',
			),
		);
	}
	return array_map( 'intval', get_posts( $args ) );
}

/**
 * La clé d'une maison d'édition dans la colonne : les graphies d'un même éditeur n'en font qu'une (« Noir sur Blanc »
 * et « Les Éditions Noir sur blanc », er_editeur_cle).
 *
 * @param string $nom Le nom de l'éditeur.
 */
function er_facettes_cle_maison( $nom ) {
	$cle = function_exists( 'er_editeur_cle' ) ? er_editeur_cle( $nom ) : '';
	return '' !== $cle ? $cle : sanitize_title( $nom );
}

/**
 * Les facettes de chaque livre, et les libellés des clés : identifiant → facette → clés ; facette → clé → nom.
 *
 * @param int[] $ids      Les livres.
 * @param array $contexte er_facettes_contexte().
 * @return array [ livres, libellés, racines (slug → nom des rayons de premier niveau) ]
 */
function er_facettes_donnees( $ids, $contexte ) {
	global $wpdb;
	$livres  = array();
	$noms    = array();
	$racines = array();
	if ( ! $ids ) {
		return array( $livres, $noms, $racines );
	}
	foreach ( $ids as $id ) {
		$livres[ $id ] = array( 'decennie' => array( 'sans' ) );
	}
	$taxes = array(
		'langue'      => 'langue',
		'er_periode'  => 'epoque',
		'er_ecriture' => 'ecrit',
		'er_sujet'    => 'sujet',
		'auteur'      => 'auteur',
		'er_editeur'  => 'maison',
		'product_tag' => 'dispo',
		'product_cat' => 'cat',
	);
	$taxes = array_filter( $taxes, 'taxonomy_exists', ARRAY_FILTER_USE_KEY );
	$cat   = array();
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $t ) {
		$cat[ $t->term_id ] = $t;
	}
	$hauts = array_filter( $cat, fn( $t ) => ! $t->parent && 'sans-rayon' !== $t->slug && 0 !== strpos( $t->slug, 'uncategorized' ) );
	usort( $hauts, fn( $a, $b ) => (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true ) );
	foreach ( $hauts as $t ) {
		$racines[ $t->slug ] = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
	}
	$racine_de = function ( $t ) use ( $cat ) {
		while ( $t->parent && isset( $cat[ $t->parent ] ) ) {
			$t = $cat[ $t->parent ];
		}
		return $t->slug;
	};
	$mentions = array( 'nouveaute', 'occasion', 'tres-rare' );
	// Les maisons : clé (er_facettes_cle_maison) → [ slug, nom ], celui de l'éditeur du classement le plus fourni s'il y
	// en a un ; une graphie écrite à la main (la méta) rejoint l'éditeur de même clé.
	$maisons = array();
	if ( isset( $taxes['er_editeur'] ) ) {
		$editeurs = get_terms(
			array(
				'taxonomy'   => 'er_editeur',
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		foreach ( is_array( $editeurs ) ? $editeurs : array() as $t ) {
			$n = html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' );
			$c = er_facettes_cle_maison( $n );
			if ( ! isset( $maisons[ $c ] ) ) {
				$maisons[ $c ] = array( $t->slug, $n );
			}
		}
	}
	$maison = function ( $nom ) use ( &$maisons ) {
		$c = er_facettes_cle_maison( $nom );
		if ( ! isset( $maisons[ $c ] ) ) {
			$maisons[ $c ] = array( sanitize_title( $nom ), $nom );
		}
		return $maisons[ $c ];
	};
	foreach ( array_chunk( $ids, 400 ) as $lot ) {
		$trous = implode( ',', array_fill( 0, count( $lot ), '%d' ) );
		$tx    = implode( ',', array_fill( 0, count( $taxes ), '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders
		$lignes = $wpdb->get_results( $wpdb->prepare( "SELECT tr.object_id AS id, tt.taxonomy AS tax, t.term_id, t.slug, t.name FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE tr.object_id IN ($trous) AND tt.taxonomy IN ($tx)", array_merge( $lot, array_keys( $taxes ) ) ) );
		foreach ( $lignes as $l ) {
			$f = $taxes[ $l->tax ];
			$k = $l->slug;
			if ( 'cat' === $f ) {
				if ( isset( $cat[ $l->term_id ] ) ) {
					$r = $racine_de( $cat[ $l->term_id ] );
					if ( isset( $racines[ $r ] ) ) {
						$livres[ $l->id ]['rayon'][] = $r;
					}
					if ( isset( $contexte['enfants'][ $k ] ) ) {
						$livres[ $l->id ]['genre'][] = $k;
						$noms['genre'][ $k ]         = $contexte['enfants'][ $k ]->name;
					}
				}
				continue;
			}
			if ( 'dispo' === $f && ! in_array( $k, $mentions, true ) ) {
				continue;
			}
			if ( 'maison' === $f ) {
				list( $k, $n )            = $maison( html_entity_decode( $l->name, ENT_QUOTES, 'UTF-8' ) );
				$livres[ $l->id ][ $f ][] = $k;
				$noms[ $f ][ $k ]         = $n;
				continue;
			}
			$livres[ $l->id ][ $f ][] = $k;
			$noms[ $f ][ $k ]         = html_entity_decode( $l->name, ENT_QUOTES, 'UTF-8' );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders
		$metas = $wpdb->get_results( $wpdb->prepare( "SELECT post_id AS id, meta_key AS cle, meta_value AS val FROM {$wpdb->postmeta} WHERE post_id IN ($trous) AND meta_key IN ('er_annee', '_stock_status', 'er_editeur')", $lot ) );
		foreach ( $metas as $m ) {
			if ( 'er_annee' === $m->cle ) {
				$livres[ $m->id ]['decennie'] = array( er_facettes_decennie( $m->val ) );
			} elseif ( '_stock_status' === $m->cle && 'instock' === $m->val ) {
				$livres[ $m->id ]['dispo'][] = 'stock';
			} elseif ( 'er_editeur' === $m->cle && '' !== trim( wp_strip_all_tags( $m->val ) ) && empty( $livres[ $m->id ]['maison'] ) ) {
				// Un éditeur écrit à la main (l'import, les notices), quand le livre n'en a pas d'autre.
				// « les Belles lettres (Paris) », comme l'écrit la BnF : sans la ville, avec une capitale.
				list( $k, $nom )              = $maison( er_notice_editeur( html_entity_decode( $m->val, ENT_QUOTES, 'UTF-8' ) ) );
				$livres[ $m->id ]['maison'][] = $k;
				$noms['maison'][ $k ]         = $nom;
			}
		}
	}
	foreach ( $livres as $id => $l ) {
		foreach ( $l as $f => $v ) {
			$livres[ $id ][ $f ] = array_values( array_unique( $v ) );
		}
	}
	$noms['rayon'] = $racines;
	$noms['dispo'] = array_merge(
		array( 'stock' => __( 'En stock', 'er-librairie' ) ),
		array_intersect_key( $noms['dispo'] ?? array(), array_flip( $mentions ) )
	);
	return array( $livres, $noms, $racines );
}

/**
 * Tout ce que la page a besoin de savoir, calculé une fois par chargement : le contexte, les livres et leurs
 * facettes, le filtre, les livres qui le passent.
 *
 * @param WP_Query|null $q La requête principale.
 * @return array|null
 */
function er_facettes_etat( $q = null ) {
	static $etat = null;
	if ( null !== $etat ) {
		return $etat ? $etat : null;
	}
	$q = $q ? $q : $GLOBALS['wp_query'];
	if ( ! er_facettes_page( $q ) ) {
		$etat = false;
		return null;
	}
	$contexte                = er_facettes_contexte( $q );
	$ids                     = er_facettes_ids( $q, $contexte );
	list( $livres, $noms )   = er_facettes_donnees( $ids, $contexte );
	$sel                     = er_facettes_selection( $_GET ); // phpcs:ignore WordPress.Security
	foreach ( $contexte['sans'] as $f ) {
		$sel[ $f ] = array();
	}
	$etat = array(
		'contexte'  => $contexte,
		'livres'    => $livres,
		'noms'      => $noms,
		'sel'       => $sel,
		'resultats' => er_facettes_resultats( $livres, $sel ),
	);
	return $etat;
}

// La requête principale ne garde que les livres qui passent le filtre.
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! $q->is_main_query() || $q->get( 'er_facettes_base' ) || ! er_facettes_page( $q ) ) {
			return;
		}
		$sel = er_facettes_selection( $_GET ); // phpcs:ignore WordPress.Security
		if ( ! er_facettes_actives( $sel ) ) {
			return;
		}
		$etat = er_facettes_etat( $q );
		if ( $etat ) {
			// Après la recherche du site (qui pose déjà sa liste, dans son ordre) : on n'en garde que les retenus.
			$deja = array_map( 'intval', (array) $q->get( 'post__in' ) );
			$ids  = $deja ? array_values( array_intersect( $deja, $etat['resultats'] ) ) : $etat['resultats'];
			$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		}
	},
	30
);

/**
 * L'adresse de la page avec un autre filtre (sans la pagination ni les paramètres par défaut).
 *
 * @param array $sel Le filtre.
 */
function er_facettes_url( $sel ) {
	$params = array_merge( array_values( wp_list_pluck( er_facettes_definitions(), 0 ) ), array( 'sans-date', 'paged' ) );
	$url    = remove_query_arg( $params, get_pagenum_link( 1, false ) );
	$args   = array();
	foreach ( er_facettes_definitions() as $cle => $def ) {
		if ( ! empty( $sel[ $cle ] ) ) {
			$args[ $def[0] ] = implode( ',', $sel[ $cle ] );
		}
	}
	if ( empty( $sel['sans_date'] ) ) {
		$args['sans-date'] = 'non';
	}
	return add_query_arg( array_map( 'rawurlencode', $args ), $url );
}

/**
 * Deux noms dans l'ordre du dictionnaire : « Élisabeth » avec les E, le cyrillique après le latin. strcoll() suivait
 * l'ordre des octets (la locale de PHP reste « C » sous WordPress) : les É et le cyrillique passaient après Z (audit du
 * 27 nov. 2026).
 *
 * @param string $a Un nom.
 * @param string $b L'autre.
 */
function er_facettes_comparer_noms( $a, $b ) {
	static $collateur = null;
	if ( null === $collateur ) {
		$collateur = class_exists( 'Collator' ) ? new Collator( 'fr_FR' ) : false;
	}
	if ( $collateur ) {
		return $collateur->compare( (string) $a, (string) $b );
	}
	$cle = function ( $t ) {
		return mb_strtolower( remove_accents( (string) $t ), 'UTF-8' );
	};
	return strcmp( $cle( $a ), $cle( $b ) );
}

/**
 * Le libellé d'une clé d'une facette.
 *
 * @param array  $etat er_facettes_etat().
 * @param string $f    La facette.
 * @param string $k    La clé.
 */
function er_facettes_nom( $etat, $f, $k ) {
	if ( in_array( $f, array( 'epoque', 'ecrit' ), true ) ) {
		foreach ( er_epoques() as $e ) {
			if ( $e[0] === $k ) {
				return $e[1];
			}
		}
	}
	if ( 'genre' === $f && isset( $etat['contexte']['enfants'][ $k ] ) ) {
		return html_entity_decode( $etat['contexte']['enfants'][ $k ]->name, ENT_QUOTES, 'UTF-8' );
	}
	if ( 'decennie' === $f && 'sans' === $k ) {
		return __( 'Sans date', 'er-librairie' );
	}
	if ( 'decennie' === $f ) {
		/* translators: %s: une décennie, 1980 */
		return sprintf( __( 'Les années %s', 'er-librairie' ), $k );
	}
	return $etat['noms'][ $f ][ $k ] ?? $k;
}

/**
 * Une option cochable : une case, son nom, son compte.
 *
 * @param string $param   Le paramètre de l'adresse.
 * @param string $k       La clé.
 * @param string $nom     Le libellé.
 * @param int    $n       Le compte.
 * @param bool   $coche   Cochée.
 * @param string $classe  La classe de l'étiquette.
 * @param string $dedans  Ce qui va entre la case et le nom (un point, une barre).
 */
function er_facettes_option( $param, $k, $nom, $n, $coche, $classe = 'er-facette__option', $dedans = '' ) {
	$vide = ! $n && ! $coche;
	return '<label class="' . esc_attr( $classe . ( $vide ? ' is-vide' : '' ) . ( $coche ? ' is-coche' : '' ) ) . '" data-nom="' . esc_attr( remove_accents( mb_strtolower( $nom ) ) ) . '">'
		. '<input type="checkbox" name="' . esc_attr( $param ) . '[]" value="' . esc_attr( $k ) . '"' . ( $coche ? ' checked' : '' ) . ( $vide ? ' disabled' : '' ) . '>'
		. $dedans
		. '<span class="er-facette__nom">' . esc_html( $nom ) . '</span>'
		. '<span class="er-facette__n">' . esc_html( number_format_i18n( $n ) ) . '</span></label>';
}

/**
 * La colonne « Filtrer par ».
 *
 * @param array $etat er_facettes_etat().
 */
function er_facettes_colonne( $etat ) {
	$defs   = er_facettes_definitions();
	$sel    = $etat['sel'];
	$sans   = $etat['contexte']['sans'];
	$livres = $etat['livres'];
	$n      = count( $etat['resultats'] );
	$actifs = 0;
	foreach ( array_keys( $defs ) as $f ) {
		$actifs += count( $sel[ $f ] );
	}
	$actifs += $sel['sans_date'] ? 0 : 1;

	$sections = '';
	$section  = function ( $id, $libelle, $corps, $nb ) use ( &$sections ) {
		if ( '' === $corps ) {
			return;
		}
		$sections .= '<details class="er-facette" data-facette="' . esc_attr( $id ) . '" open><summary class="er-facette__tete"><span class="er-facette__libelle">' . esc_html( $libelle ) . '</span>'
			. ( $nb ? '<span class="er-facette__actifs">' . esc_html( $nb ) . '</span>' : '' ) . '</summary><div class="er-facette__corps">' . $corps . '</div></details>';
	};
	$cles_triees = function ( $f, $c, $ordre = null ) use ( $sel, $etat ) {
		$cles = array_unique( array_merge( array_keys( $c ), $sel[ $f ] ) );
		if ( $ordre ) {
			return array_values( array_intersect( $ordre, $cles ) );
		}
		usort(
			$cles,
			function ( $a, $b ) use ( $c, $sel, $f, $etat ) {
				return ( in_array( $b, $sel[ $f ], true ) <=> in_array( $a, $sel[ $f ], true ) ) ?: ( ( $c[ $b ] ?? 0 ) <=> ( $c[ $a ] ?? 0 ) ) ?: er_facettes_comparer_noms( er_facettes_nom( $etat, $f, $a ), er_facettes_nom( $etat, $f, $b ) );
			}
		);
		return $cles;
	};
	$liste = function ( $f, $ordre = null, $point = null ) use ( $defs, $sel, $livres, $etat, $cles_triees ) {
		$c    = er_facettes_compter( $livres, $sel, $f );
		$html = '';
		foreach ( $cles_triees( $f, $c, $ordre ) as $k ) {
			$dedans = $point ? $point( $k ) : '';
			$html  .= er_facettes_option( $defs[ $f ][0], $k, er_facettes_nom( $etat, $f, $k ), $c[ $k ] ?? 0, in_array( $k, $sel[ $f ], true ), 'er-facette__option', $dedans );
		}
		return $html;
	};

	foreach ( array_keys( $defs ) as $f ) {
		if ( in_array( $f, $sans, true ) || 'ecrit' === $f ) {
			continue;
		}
		$def = $defs[ $f ];
		if ( 'rayon' === $f ) {
			$section( $f, $def[1], $liste( $f, array_keys( $etat['noms']['rayon'] ) ), count( $sel[ $f ] ) );
		} elseif ( 'langue' === $f ) {
			$section( $f, $def[1], $liste( $f ), count( $sel[ $f ] ) );
		} elseif ( 'genre' === $f ) {
			$section( $f, $etat['contexte']['genre'], $liste( $f, array_keys( $etat['contexte']['enfants'] ) ), count( $sel[ $f ] ) );
		} elseif ( 'epoque' === $f ) {
			$frises = '';
			$total  = 0;
			foreach ( array( 'epoque', 'ecrit' ) as $mode ) {
				$c   = er_facettes_compter( $livres, $sel, $mode );
				$max = max( 1, max( array_merge( array( 0 ), array_values( $c ) ) ) );
				$total += array_sum( $c );
				$lignes = '';
				foreach ( er_epoques() as $e ) {
					$nb      = $c[ $e[0] ] ?? 0;
					$coche   = in_array( $e[0], $sel[ $mode ], true );
					$dedans  = '<span class="er-frise__point" aria-hidden="true"></span>';
					$lignes .= str_replace(
						'</span><span class="er-facette__n">',
						'<span class="er-frise__annees">' . esc_html( $e[2] ) . '</span><span class="er-frise__barre" aria-hidden="true"><span style="width:' . esc_attr( round( 100 * $nb / $max ) ) . '%"></span></span></span><span class="er-facette__n">',
						er_facettes_option( $defs[ $mode ][0], $e[0], $e[1], $nb, $coche, 'er-frise__ligne', $dedans )
					);
				}
				$titre   = 'epoque' === $mode ? __( 'Parle de', 'er-librairie' ) : __( 'Écrit durant', 'er-librairie' );
				$aide    = 'epoque' === $mode
					? __( 'L’époque dont parle le livre : Les Enfants de la Volga, écrit en 2017, se passe dans les années 1920 et 1930.', 'er-librairie' )
					: __( 'Quand le livre a été écrit : Boulgakov écrit Le Maître et Marguerite dans les années 1930.', 'er-librairie' );
				$frises .= '<div class="er-frise" data-frise="' . esc_attr( $mode ) . '"><p class="er-frise__titre">' . esc_html( $titre ) . '</p><p class="er-frise__aide">' . esc_html( $aide ) . '</p><div class="er-frise__lignes">' . $lignes . '</div></div>';
			}
			if ( $total ) {
				$mode   = $sel['ecrit'] && ! $sel['epoque'] ? 'ecrit' : 'epoque';
				$bascul = '<div class="er-frise__bascule" role="group" aria-label="' . esc_attr__( 'Quelle époque', 'er-librairie' ) . '" data-mode="' . esc_attr( $mode ) . '" hidden>'
					. '<button type="button" data-mode="ecrit" aria-pressed="' . ( 'ecrit' === $mode ? 'true' : 'false' ) . '">' . esc_html__( 'Écrit durant', 'er-librairie' ) . '</button>'
					. '<button type="button" data-mode="epoque" aria-pressed="' . ( 'epoque' === $mode ? 'true' : 'false' ) . '">' . esc_html__( 'Parle de', 'er-librairie' ) . '</button></div>';
				$section( 'epoque', $def[1], $bascul . $frises, count( $sel['epoque'] ) + count( $sel['ecrit'] ) );
			}
		} elseif ( 'sujet' === $f ) {
			$c    = er_facettes_compter( $livres, $sel, $f );
			$cles = $cles_triees( $f, $c );
			$html = '';
			foreach ( $cles as $i => $k ) {
				if ( 12 === $i ) {
					/* translators: %d: le nombre de sujets */
					$html .= '</div><details class="er-facette__plus"><summary>' . esc_html( sprintf( __( 'Voir les %d sujets', 'er-librairie' ), count( $cles ) ) ) . '</summary><div class="er-facette__pilules">';
				}
				$html .= er_facettes_option( $def[0], $k, er_facettes_nom( $etat, $f, $k ), $c[ $k ] ?? 0, in_array( $k, $sel[ $f ], true ), 'er-facette__pilule' );
			}
			$html .= count( $cles ) > 12 ? '</div></details>' : '';
			$section( $f, $def[1], $cles ? '<div class="er-facette__pilules">' . $html . ( count( $cles ) > 12 ? '' : '</div>' ) : '', count( $sel[ $f ] ) );
		} elseif ( 'auteur' === $f || 'maison' === $f ) {
			$c    = er_facettes_compter( $livres, $sel, $f );
			$cles = $cles_triees( $f, $c );
			if ( ! $cles ) {
				continue;
			}
			$aide = 'auteur' === $f ? __( 'Chercher un auteur', 'er-librairie' ) : __( 'Chercher une maison', 'er-librairie' );
			$html = count( $cles ) > 7 ? '<label class="er-facette__chercher" hidden><span class="screen-reader-text">' . esc_html( $aide ) . '</span><input type="search" placeholder="' . esc_attr( $aide ) . '" autocomplete="off"></label>' : '';
			foreach ( $cles as $i => $k ) {
				if ( 7 === $i ) {
					/* translators: %d: le nombre d'auteurs ou de maisons */
					$html .= '<details class="er-facette__plus"><summary>' . esc_html( sprintf( __( 'Voir les %d', 'er-librairie' ), count( $cles ) ) ) . '</summary>';
				}
				$nom    = er_facettes_nom( $etat, $f, $k );
				$point  = 'maison' === $f && 'ymca-press' === $k ? '<span class="er-facette__point er-facette__point--ymca" aria-hidden="true"></span>' : '';
				$html  .= er_facettes_option( $def[0], $k, $nom, $c[ $k ] ?? 0, in_array( $k, $sel[ $f ], true ), 'er-facette__option', $point );
			}
			$html .= count( $cles ) > 7 ? '</details>' : '';
			$html .= '<p class="er-facette__rien" hidden>' . esc_html__( 'Aucun nom ne correspond.', 'er-librairie' ) . '</p>';
			$section( $f, $def[1], $html, count( $sel[ $f ] ) );
		} elseif ( 'decennie' === $f ) {
			$c = er_facettes_compter( $livres, $sel, $f );
			$d = array_filter( array_keys( $c ), fn( $k ) => 'sans' !== $k );
			if ( ! $d && empty( $c['sans'] ) ) {
				continue;
			}
			$debut = $d ? min( $d ) : 1990;
			$fin   = max( $d ? max( $d ) : 2020, 2020 );
			$max   = max( 1, max( array_merge( array( 0 ), array_values( array_diff_key( $c, array( 'sans' => 0 ) ) ) ) ) );
			$barres = '';
			for ( $an = (int) $debut; $an <= $fin; $an += 10 ) {
				$k    = (string) $an;
				$nb   = $c[ $k ] ?? 0;
				$on   = in_array( $k, $sel[ $f ], true );
				/* translators: 1: une décennie, 2: un nombre de livres */
				$titre   = sprintf( _n( 'Les années %1$s : %2$s livre', 'Les années %1$s : %2$s livres', $nb, 'er-librairie' ), $k, number_format_i18n( $nb ) );
				$barres .= '<label class="er-histo__barre' . ( $on ? ' is-coche' : '' ) . ( $nb || $on ? '' : ' is-vide' ) . '" title="' . esc_attr( $titre ) . '">'
					. '<input type="checkbox" name="decennies[]" value="' . esc_attr( $k ) . '"' . ( $on ? ' checked' : '' ) . ( $nb || $on ? '' : ' disabled' ) . '>'
					. '<span class="screen-reader-text">' . esc_html( $titre ) . '</span>'
					. '<span class="er-histo__trait" aria-hidden="true" style="height:' . esc_attr( max( $nb ? 6 : 2, round( 100 * $nb / $max ) ) ) . '%"></span></label>';
			}
			$resume = $sel['decennie']
				/* translators: %s: des décennies, « 1980, 1990 » */
				? sprintf( __( 'Les années %s.', 'er-librairie' ), implode( ', ', $sel['decennie'] ) )
				: __( 'Toutes les années : touchez une décennie pour la choisir.', 'er-librairie' );
			$html   = '<div class="er-histo" role="group" aria-label="' . esc_attr__( 'Les décennies', 'er-librairie' ) . '">' . $barres . '</div>'
				. '<div class="er-histo__axe" aria-hidden="true"><span>' . esc_html( $debut ) . '</span><span>' . esc_html( min( $fin + 9, (int) gmdate( 'Y' ) ) ) . '</span></div>'
				. '<p class="er-facette__resume">' . esc_html( $resume ) . '</p>';
			if ( ! empty( $c['sans'] ) || ! $sel['sans_date'] ) {
				$html .= '<input type="hidden" name="sans-date" value="non">'
					. '<label class="er-facette__option"><input type="checkbox" name="sans-date" value="oui"' . ( $sel['sans_date'] ? ' checked' : '' ) . '><span class="er-facette__nom">' . esc_html__( 'Avec les livres sans date', 'er-librairie' ) . '</span><span class="er-facette__n">' . esc_html( number_format_i18n( $c['sans'] ?? 0 ) ) . '</span></label>';
			}
			$section( $f, $def[1], $html, count( $sel[ $f ] ) + ( $sel['sans_date'] ? 0 : 1 ) );
		} elseif ( 'dispo' === $f ) {
			$section( $f, $def[1], $liste( $f, array_keys( $etat['noms']['dispo'] ) ), count( $sel[ $f ] ) );
		}
	}

	// Les paramètres qui ne sont pas des filtres (le tri, la recherche, la vue) suivent le formulaire.
	$garder = '';
	foreach ( array( 'orderby', 's', 'post_type', 'vue' ) as $p ) {
		if ( isset( $_GET[ $p ] ) && is_string( $_GET[ $p ] ) ) { // phpcs:ignore WordPress.Security
			$garder .= '<input type="hidden" name="' . esc_attr( $p ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET[ $p ] ) ) ) . '">'; // phpcs:ignore WordPress.Security
		}
	}
	$vide = $etat['sel'];
	foreach ( array_keys( $defs ) as $f ) {
		$vide[ $f ] = array();
	}
	$vide['sans_date'] = true;
	/* translators: %s: un nombre de livres */
	$voir = sprintf( _n( 'Voir le livre', 'Voir les %s livres', $n, 'er-librairie' ), number_format_i18n( $n ) );

	return '<aside class="er-facettes" data-er-facettes aria-labelledby="er-facettes-titre">'
		. '<button type="button" class="er-facettes__ouvrir" aria-expanded="false" aria-controls="er-facettes-panneau">' . esc_html__( 'Filtrer', 'er-librairie' ) . ( $actifs ? ' <span class="er-facette__actifs">' . esc_html( $actifs ) . '</span>' : '' ) . '</button>'
		. '<form class="er-facettes__panneau" id="er-facettes-panneau" method="get" action="' . esc_url( er_facettes_url( $vide ) ) . '">'
		. '<div class="er-facettes__tete"><h2 class="er-facettes__titre" id="er-facettes-titre">' . esc_html__( 'Filtrer par', 'er-librairie' ) . '</h2>'
		. ( $actifs ? '<a class="er-facettes__effacer" href="' . esc_url( er_facettes_url( $vide ) ) . '">' . esc_html__( 'Tout effacer', 'er-librairie' ) . '</a>' : '' )
		. '<button type="button" class="er-facettes__fermer" aria-label="' . esc_attr__( 'Fermer les filtres', 'er-librairie' ) . '"></button></div>'
		. $garder . $sections
		. '<div class="er-facettes__pied"><button type="submit" class="er-facettes__voir">' . esc_html( $voir ) . '</button></div>'
		. '</form></aside>';
}

/**
 * Les filtres en cours, au-dessus des livres : chacun se retire d'un clic.
 *
 * @param array $etat er_facettes_etat().
 */
function er_facettes_puces( $etat ) {
	$sel    = $etat['sel'];
	$puces  = '';
	$prefix = array(
		'epoque' => __( 'Parle de : %s', 'er-librairie' ),
		'ecrit'  => __( 'Écrit durant : %s', 'er-librairie' ),
	);
	foreach ( array_keys( er_facettes_definitions() ) as $f ) {
		foreach ( $sel[ $f ] as $k ) {
			$moins       = $sel;
			$moins[ $f ] = array_values( array_diff( $sel[ $f ], array( $k ) ) );
			$nom         = er_facettes_nom( $etat, $f, $k );
			if ( isset( $prefix[ $f ] ) ) {
				$nom = sprintf( $prefix[ $f ], preg_replace_callback( '/^(L[ae’\']\s?)/u', fn( $m ) => mb_strtolower( $m[1] ), $nom ) );
			}
			/* translators: %s: un filtre */
			$puces .= '<li><a class="er-puce" href="' . esc_url( er_facettes_url( $moins ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Retirer « %s »', 'er-librairie' ), $nom ) ) . '">' . esc_html( $nom ) . '</a></li>';
		}
	}
	if ( ! $sel['sans_date'] ) {
		$moins              = $sel;
		$moins['sans_date'] = true;
		$puces             .= '<li><a class="er-puce" href="' . esc_url( er_facettes_url( $moins ) ) . '">' . esc_html__( 'Sans les livres non datés', 'er-librairie' ) . '</a></li>';
	}
	if ( '' === $puces ) {
		return '';
	}
	$vide = $sel;
	foreach ( array_keys( er_facettes_definitions() ) as $f ) {
		$vide[ $f ] = array();
	}
	$vide['sans_date'] = true;
	return '<div class="er-facettes-puces" data-er-puces><ul>' . $puces . '</ul><a class="er-facettes__effacer" href="' . esc_url( er_facettes_url( $vide ) ) . '">' . esc_html__( 'Tout effacer', 'er-librairie' ) . '</a></div>';
}

add_action(
	'init',
	function () {
		register_block_type(
			'er/facettes',
			array(
				'api_version'     => 3,
				'attributes'      => array(
					'partie' => array(
						'type'    => 'string',
						'default' => 'colonne',
					),
				),
				'render_callback' => function ( $attributs ) {
					$etat = er_facettes_etat();
					if ( ! $etat || count( $etat['livres'] ) < 6 ) {
						return '';
					}
					return 'puces' === ( $attributs['partie'] ?? '' ) ? er_facettes_puces( $etat ) : er_facettes_colonne( $etat );
				},
			)
		);
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Essayer la colonne sur un rayon, sans navigateur : wp er facettes essayer <rayon> [langues=francais …]. Dit le
	 * nombre de livres retenus, les cinq premières options de chaque facette avec leur compte, et la taille du HTML.
	 */
	WP_CLI::add_command(
		'er facettes essayer',
		function ( $args ) {
			$rayon = array_shift( $args ) ?? 'litterature';
			$get   = array();
			foreach ( $args as $a ) {
				if ( false !== strpos( $a, '=' ) ) {
					list( $k, $v ) = explode( '=', $a, 2 );
					$get[ $k ][]   = $v;
				}
			}
			$q = new WP_Query(
				array(
					'post_type'   => 'product',
					'product_cat' => $rayon,
				)
			);
			$contexte              = er_facettes_contexte( $q );
			$ids                   = er_facettes_ids( $q, $contexte );
			list( $livres, $noms ) = er_facettes_donnees( $ids, $contexte );
			$sel                   = er_facettes_selection( $get );
			foreach ( $contexte['sans'] as $f ) {
				$sel[ $f ] = array();
			}
			$etat = array(
				'contexte'  => $contexte,
				'livres'    => $livres,
				'noms'      => $noms,
				'sel'       => $sel,
				'resultats' => er_facettes_resultats( $livres, $sel ),
			);
			WP_CLI::log( sprintf( '%s : %d livres, %d retenus par le filtre.', $rayon, count( $livres ), count( $etat['resultats'] ) ) );
			foreach ( array_keys( er_facettes_definitions() ) as $f ) {
				if ( in_array( $f, $contexte['sans'], true ) ) {
					continue;
				}
				$c = er_facettes_compter( $livres, $sel, $f );
				arsort( $c );
				$top = array();
				foreach ( array_slice( $c, 0, 5, true ) as $k => $n ) {
					$top[] = er_facettes_nom( $etat, $f, (string) $k ) . ' ' . $n;
				}
				WP_CLI::log( '  ' . $f . ' : ' . implode( ' · ', $top ) );
			}
			WP_CLI::log( sprintf( 'La colonne : %d caractères ; les filtres en cours : %d.', strlen( er_facettes_colonne( $etat ) ), strlen( er_facettes_puces( $etat ) ) ) );
		}
	);
}

/**
 * Une liste vue autrement (filtrée, triée, en liste, dans une autre langue sans Polylang) n'est pas une page de plus
 * pour les moteurs : noindex, mais ses liens se suivent. Sinon chaque combinaison de filtres devenait un doublon de
 * la page du rayon (audit du 27 nov. 2026).
 */
add_filter(
	'wp_robots',
	function ( $robots ) {
		$parametres = array_merge( array( 'vue', 'orderby', 'sans-date', 'lang', 'add-to-cart' ), wp_list_pluck( er_facettes_definitions(), 0 ) );
		if ( array_intersect( $parametres, array_keys( $_GET ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- on ne lit que les noms des paramètres.
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'], $robots['max-image-preview'] );
		}
		return $robots;
	}
);
