<?php
/**
 * Les traducteurs, les directeurs d'ouvrage et les éditeurs ont leur page (décidé le 24 nov. 2026).
 *
 * - Un traducteur, un directeur d'ouvrage est une personne : un auteur, avec la page de l'auteur (son article de
 *   l'encyclopédie). Un livre le porte dans « Traduction » ou « Sous la direction de » : er_traduction et er_direction,
 *   deux classements cachés tenus avec les mêmes noms que les auteurs, comme « Autour de » (er_autour). On y choisit une
 *   personne, rien d'autre ; un nom nouveau devient un auteur.
 * - Les livres d'une personne, ce sont ceux qu'elle a écrits, traduits ou dirigés : er_personne_livres() les donne, rôle
 *   par rôle, pour la recherche (« 3 livres en rayon ») et pour sa page. Le compte d'un auteur (son nombre de livres
 *   écrits) ne change pas.
 * - Un éditeur est un classement public des livres, er_editeur, avec sa page, /editeur/<slug>/ : sa présentation (la
 *   description du terme) et ses livres. La méta er_editeur, reprise de Wix, ne sert plus que de secours, comme la méta
 *   er_traducteur.
 * - wp er roles [appliquer] : relève dans le catalogue les traducteurs (méta er_traducteur, « traduit du russe par … »
 *   dans le titre ou la présentation), les directeurs (« sous la direction de … » dans le titre) et les éditeurs (méta
 *   er_editeur), et les pose avec « appliquer ». Sans « appliquer », il ne fait que les montrer.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les rôles d'une personne sur un livre, en plus de l'auteur : classement caché → libellés.
 */
function er_roles_personnes() {
	return array(
		'er_traduction' => array(
			'fiche' => _x( 'Traduction', 'fiche d’un livre', 'er-librairie' ),
			'boite' => __( 'Traduction', 'er-librairie' ),
			'aide'  => __( 'Les traducteurs, séparés par des virgules : des auteurs (un nom nouveau devient un auteur). Le livre paraît sur leur page, dans leurs traductions.', 'er-librairie' ),
			'meta'  => 'er_traducteur',
			'cle'   => 'traduction',
		),
		'er_direction'  => array(
			'fiche' => _x( 'Sous la direction de', 'fiche d’un livre', 'er-librairie' ),
			'boite' => __( 'Sous la direction de', 'er-librairie' ),
			'aide'  => __( 'Les directeurs de l’ouvrage, séparés par des virgules : des auteurs (un nom nouveau devient un auteur). Le livre paraît sur leur page.', 'er-librairie' ),
			'meta'  => '',
			'cle'   => 'direction',
		),
		// Les rôles que Wix ne connaissait pas, relevés dans les textes des livres (24 nov. 2026, l'audit : 45 mentions,
		// dont 17 de personnes qui ont leur page, comme Georges Nivat ou Sophie Benech).
		'er_preface'      => array(
			'fiche' => _x( 'Préface', 'fiche d’un livre', 'er-librairie' ),
			'boite' => __( 'Préface, présentation', 'er-librairie' ),
			'aide'  => __( 'Qui préface, présente, postface l’ouvrage, séparés par des virgules : des auteurs. Le livre paraît sur leur page.', 'er-librairie' ),
			'meta'  => '',
			'cle'   => 'preface',
		),
		'er_edition'      => array(
			'fiche' => _x( 'Édition établie par', 'fiche d’un livre', 'er-librairie' ),
			'boite' => __( 'Édition établie, notes', 'er-librairie' ),
			'aide'  => __( 'Qui établit le texte, l’annote, choisit les textes, séparés par des virgules : des auteurs. Le livre paraît sur leur page.', 'er-librairie' ),
			'meta'  => '',
			'cle'   => 'edition',
		),
		'er_illustration' => array(
			'fiche' => _x( 'Illustrations', 'fiche d’un livre', 'er-librairie' ),
			'boite' => __( 'Illustrations', 'er-librairie' ),
			'aide'  => __( 'Les illustrateurs, séparés par des virgules : des auteurs. Le livre paraît sur leur page.', 'er-librairie' ),
			'meta'  => '',
			'cle'   => 'illustration',
		),
	);
}

add_action(
	'init',
	function () {
		foreach ( er_roles_personnes() as $taxonomie => $role ) {
			register_taxonomy(
				$taxonomie,
				array( 'product' ),
				array(
					'labels'       => array( 'name' => $role['boite'] ),
					'public'       => false,
					'show_ui'      => false,
					'show_in_rest' => false,
					'hierarchical' => false,
					'rewrite'      => false,
					'query_var'    => false,
				)
			);
		}
		register_taxonomy(
			'er_editeur',
			array( 'product' ),
			array(
				'labels'            => array(
					'name'                       => __( 'Éditeurs', 'er-librairie' ),
					'singular_name'              => __( 'Éditeur', 'er-librairie' ),
					'search_items'               => __( 'Chercher un éditeur', 'er-librairie' ),
					'all_items'                  => __( 'Tous les éditeurs', 'er-librairie' ),
					'edit_item'                  => __( 'Modifier l’éditeur', 'er-librairie' ),
					'view_item'                  => __( 'Voir l’éditeur', 'er-librairie' ),
					'add_new_item'               => __( 'Ajouter un éditeur', 'er-librairie' ),
					'separate_items_with_commas' => __( 'Séparer les éditeurs par des virgules', 'er-librairie' ),
					'choose_from_most_used'      => __( 'Choisir parmi les plus utilisés', 'er-librairie' ),
					'not_found'                  => __( 'Aucun éditeur', 'er-librairie' ),
					'menu_name'                  => __( 'Éditeurs', 'er-librairie' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'editeur' ),
			)
		);
	},
	11
);

/**
 * Les personnes d'un livre dans un rôle, par leurs termes d'auteur.
 *
 * @param int    $id        Le livre.
 * @param string $taxonomie er_traduction ou er_direction.
 * @return WP_Term[]
 */
function er_personnes_du_livre( $id, $taxonomie ) {
	$personnes = array();
	$miroirs   = taxonomy_exists( $taxonomie ) ? wp_get_post_terms( $id, $taxonomie ) : array();
	foreach ( is_wp_error( $miroirs ) ? array() : $miroirs as $miroir ) {
		$auteur = get_term_by( 'slug', $miroir->slug, 'auteur' );
		if ( $auteur ) {
			$personnes[] = $auteur;
		}
	}
	return $personnes;
}

/**
 * Les clés de toutes les personnes : la clé de recherche de leur nom et de leurs « Autres graphies » → les auteurs qui
 * la portent. Gardée un jour, refaite dès qu'une personne change.
 *
 * @return array<string,int[]>
 */
function er_personnes_cles() {
	static $cles = null;
	if ( null !== $cles ) {
		return $cles;
	}
	$cles = get_transient( 'er_personnes_cles' );
	if ( is_array( $cles ) ) {
		return $cles;
	}
	$cles = array();
	foreach ( get_terms( array( 'taxonomy' => 'auteur', 'hide_empty' => false, 'update_term_meta_cache' => true ) ) as $t ) {
		$graphies = function_exists( 'er_autres_graphies' ) ? er_autres_graphies( $t->term_id ) : array();
		foreach ( array_merge( array( $t->name ), $graphies ) as $nom ) {
			$cle = er_cle_recherche( $nom );
			if ( '' !== $cle ) {
				$cles[ $cle ][] = (int) $t->term_id;
			}
		}
	}
	$cles = array_map( 'array_values', array_map( 'array_unique', $cles ) );
	set_transient( 'er_personnes_cles', $cles, DAY_IN_SECONDS );
	return $cles;
}
foreach ( array( 'created_auteur', 'edited_auteur', 'delete_auteur' ) as $er_crochet ) {
	add_action(
		$er_crochet,
		function () {
			delete_transient( 'er_personnes_cles' );
		}
	);
}
add_action(
	'updated_term_meta',
	function ( $meta_id, $terme, $cle ) {
		if ( in_array( $cle, array( 'er_graphies', 'er_anciens' ), true ) ) {
			delete_transient( 'er_personnes_cles' );
		}
	},
	10,
	3
);

/**
 * Retrouve une personne, quelle que soit la façon dont on l'écrit (24 nov. 2026, l'audit : « А. Солженицын » et
 * « Alexandre Soljenitsyne » étaient deux pages). Dans l'ordre : le nom exact ; l'identifiant (le nom passé par
 * sanitize_title) ; un ancien identifiant, laissé par une fusion (er_anciens) ; la clé de recherche du nom (sans
 * accents, transcriptions du russe ramenées à une forme commune) parmi les noms et les « Autres graphies » des
 * personnes, si elle n'en désigne qu'une. Rien, sinon : ni création, ni devinette.
 *
 * @param string $nom Le nom, ou l'identifiant.
 * @return WP_Term|null
 */
function er_personne_trouver( $nom ) {
	$nom = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $nom, ENT_QUOTES, 'UTF-8' ) ) ) );
	if ( '' === $nom ) {
		return null;
	}
	$t = get_term_by( 'name', $nom, 'auteur' );
	if ( ! $t ) {
		$t = get_term_by( 'slug', sanitize_title( $nom ), 'auteur' );
	}
	if ( ! $t ) {
		$anciens = get_terms(
			array(
				'taxonomy'   => 'auteur',
				'hide_empty' => false,
				'number'     => 1,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'     => 'er_anciens',
						'value'   => ',' . sanitize_title( $nom ) . ',',
						'compare' => 'LIKE',
					),
				),
			)
		);
		$t = $anciens && ! is_wp_error( $anciens ) ? $anciens[0] : null;
	}
	if ( ! $t && function_exists( 'er_cle_recherche' ) ) {
		$cles = er_personnes_cles();
		$cle  = er_cle_recherche( $nom );
		if ( isset( $cles[ $cle ] ) && 1 === count( $cles[ $cle ] ) ) {
			$t = get_term( $cles[ $cle ][0], 'auteur' );
		}
	}
	return $t && ! is_wp_error( $t ) ? $t : null;
}

/**
 * Une personne par son nom, retrouvée sous toutes ses graphies (er_personne_trouver), sinon un auteur nouveau quand
 * $creer est vrai.
 *
 * @param string $nom   Le nom.
 * @param bool   $creer Créer l'auteur s'il n'existe pas.
 * @return WP_Term|null
 */
function er_personne_par_nom( $nom, $creer = false ) {
	$nom = trim( preg_replace( '/\s+/u', ' ', $nom ) );
	if ( '' === $nom ) {
		return null;
	}
	$t = er_personne_trouver( $nom );
	if ( ! $t && $creer ) {
		$cree = wp_insert_term( $nom, 'auteur' );
		$t    = is_wp_error( $cree ) ? null : get_term( $cree['term_id'], 'auteur' );
	}
	return $t && ! is_wp_error( $t ) ? $t : null;
}

/**
 * Pose les personnes d'un livre dans un rôle (par identifiant d'auteur ou par nom ; un nom inconnu devient un auteur).
 *
 * @param int    $id        Le livre.
 * @param string $taxonomie er_traduction ou er_direction.
 * @param array  $noms      Les personnes.
 * @param bool   $ajouter   Ajouter aux personnes déjà posées plutôt que les remplacer.
 */
function er_poser_personnes( $id, $taxonomie, $noms, $ajouter = false ) {
	$slugs = array();
	foreach ( $noms as $n ) {
		$t = is_numeric( $n ) ? get_term( (int) $n, 'auteur' ) : er_personne_par_nom( (string) $n, true );
		if ( ! $t || is_wp_error( $t ) ) {
			continue;
		}
		if ( ! term_exists( $t->slug, $taxonomie ) ) {
			wp_insert_term( $t->name, $taxonomie, array( 'slug' => $t->slug ) );
		}
		$slugs[] = $t->slug;
	}
	wp_set_object_terms( $id, $slugs, $taxonomie, $ajouter );
	wp_cache_delete( 'er_personnes_livres', 'er' );
}

/**
 * Les livres en vente : publiés, visibles au catalogue, sans les billets des rencontres. Des arguments de WP_Query.
 */
function er_requete_livres_en_vente() {
	return array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'lang'           => '',
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'slug',
				'terms'    => array( 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		),
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => 'er_evenement',
				'compare' => 'NOT EXISTS',
			),
		),
	);
}

/**
 * Les livres d'une personne, rôle par rôle : écrits, traduits, dirigés, et ceux qui parlent d'elle (« Autour de »).
 * « tous » réunit les trois premiers, sans doublon : ce sont « ses livres en rayon ».
 *
 * @param WP_Term|int $auteur La personne.
 * @return array<string,int[]>
 */
function er_personne_livres( $auteur ) {
	$auteur = is_object( $auteur ) ? $auteur : get_term( (int) $auteur, 'auteur' );
	$vide   = array(
		'auteur'       => array(),
		'traduction'   => array(),
		'direction'    => array(),
		'preface'      => array(),
		'edition'      => array(),
		'illustration' => array(),
		'autour'       => array(),
		'tous'         => array(),
	);
	if ( ! $auteur || is_wp_error( $auteur ) ) {
		return $vide;
	}
	$cache = wp_cache_get( 'er_personnes_livres', 'er' );
	$cache = is_array( $cache ) ? $cache : array();
	if ( isset( $cache[ $auteur->term_id ] ) ) {
		return $cache[ $auteur->term_id ];
	}
	$par_role = array( 'auteur' => array( 'auteur', 'term_id', $auteur->term_id ) );
	foreach ( er_roles_personnes() as $taxonomie => $role ) {
		$par_role[ $role['cle'] ] = array( $taxonomie, 'slug', $auteur->slug );
	}
	$par_role['autour'] = array( 'er_autour', 'slug', $auteur->slug );
	$livres = $vide;
	foreach ( $par_role as $role => list( $taxonomie, $champ, $valeur ) ) {
		if ( ! taxonomy_exists( $taxonomie ) ) {
			continue;
		}
		$args                = er_requete_livres_en_vente();
		$args['tax_query'][] = array(
			'taxonomy' => $taxonomie,
			'field'    => $champ,
			'terms'    => array( $valeur ),
		);
		$livres[ $role ]     = array_map( 'intval', ( new WP_Query( $args ) )->posts );
	}
	$livres['tous']               = array_values( array_unique( array_merge( ...array_values( array_diff_key( $livres, array( 'autour' => 1, 'tous' => 1 ) ) ) ) ) );
	$cache[ $auteur->term_id ] = $livres;
	wp_cache_set( 'er_personnes_livres', $cache, 'er', HOUR_IN_SECONDS );
	return $livres;
}

/**
 * Les traductions d'une personne (pour sa page) : ses livres traduits, en vente.
 *
 * @param WP_Term|int $auteur La personne.
 * @return int[]
 */
function er_traductions_de( $auteur ) {
	$l = er_personne_livres( $auteur );
	return $l['traduction'];
}

// Un livre change : les comptes des personnes sont à refaire.
foreach ( array( 'save_post_product', 'deleted_post', 'trashed_post', 'set_object_terms' ) as $crochet ) {
	add_action(
		$crochet,
		function () {
			wp_cache_delete( 'er_personnes_livres', 'er' );
		}
	);
}

/**
 * L'éditeur d'un livre : ses termes, sinon la méta reprise de Wix.
 *
 * @param int $id Le livre.
 * @return array{termes: WP_Term[], texte: string}
 */
function er_editeur_du_livre( $id ) {
	$termes = taxonomy_exists( 'er_editeur' ) ? wp_get_post_terms( $id, 'er_editeur' ) : array();
	return array(
		'termes' => is_wp_error( $termes ) ? array() : $termes,
		'texte'  => (string) get_post_meta( $id, 'er_editeur', true ),
	);
}

// La fiche d'un livre, dans l'administration : la traduction et la direction, des personnes, comme « Autour de ».
add_action(
	'add_meta_boxes_product',
	function () {
		foreach ( er_roles_personnes() as $taxonomie => $role ) {
			add_meta_box(
				'er-' . $taxonomie,
				$role['boite'],
				function ( $post ) use ( $taxonomie, $role ) {
					wp_nonce_field( $taxonomie, $taxonomie . '_jeton' );
					$noms = implode( ', ', wp_list_pluck( er_personnes_du_livre( $post->ID, $taxonomie ), 'name' ) );
					echo '<p><input type="text" class="widefat" name="' . esc_attr( $taxonomie ) . '" list="er-auteurs" value="' . esc_attr( $noms ) . '"></p>';
					echo '<p class="description">' . esc_html( $role['aide'] ) . '</p>';
				},
				'product',
				'side'
			);
		}
	}
);
add_action(
	'save_post_product',
	function ( $id ) {
		foreach ( array_keys( er_roles_personnes() ) as $taxonomie ) {
			$jeton = $taxonomie . '_jeton';
			if ( ! isset( $_POST[ $jeton ], $_POST[ $taxonomie ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ $jeton ] ), $taxonomie ) || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}
			er_poser_personnes( $id, $taxonomie, array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST[ $taxonomie ] ) ) ) ) ) );
		}
	}
);

/**
 * Coupe une mention en noms : « Hélène Henry et Michel Aucouturier », « A, B & C ». Le texte repris de Wix a perdu ses
 * retours à la ligne (« Françoise LhoestEnfant… ») : une minuscule collée à une majuscule coupe aussi. Ce qui n'a pas
 * l'air d'un traducteur est mis de côté, avec sa raison : un seul mot (« Rilke », « Goethe »), un autre rôle
 * (« Illustrations de… », « Préface de… »), un nom qui est dans le titre du livre (« Djann », « Martin de Tours »).
 *
 * @param string $texte La mention.
 * @param string $titre Le titre du livre.
 * @param array  $ecart Reçoit les noms mis de côté : nom → raison.
 * @return string[]
 */
function er_roles_noms( $texte, $titre = '', &$ecart = array() ) {
	$texte = trim( wp_strip_all_tags( html_entity_decode( (string) $texte, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	$texte = preg_replace( '/(?<=\p{Ll})(?=\p{Lu})/u', "\n", $texte );
	$noms  = array();
	foreach ( preg_split( '/\s*(?:\R|,|;|&|\/|\bet\b|\band\b|\bи\b)\s*/u', $texte ) as $nom ) {
		$nom = preg_replace( '/^(?:trad(?:uit|uite|uction|\.)?)\s*(?:(?:du|de l[’\']|de|depuis le)\s+\p{Ll}+\s*)?(?:par|:)?\s*/iu', '', trim( $nom ) );
		$nom = trim( $nom, " \t\n\r\0\x0B.()[]:" );
		if ( '' === $nom ) {
			continue;
		}
		if ( preg_match( '/^(?:pr[ée]face|postface|illustr|dessin|photo|pr[ée]sent|introduction|notes?\b|avant-propos|[ée]dition|choix|r[ée]vis|relu|enfant|\p{Ll})/iu', $nom ) ) {
			$ecart[ $nom ] = 'autre rôle';
			continue;
		}
		if ( preg_match( '/\d/', $nom ) || mb_strlen( $nom ) > 50 ) {
			$ecart[ $nom ] = 'pas un nom';
			continue;
		}
		if ( ! preg_match( '/\s/u', $nom ) ) {
			$ecart[ $nom ] = 'un seul mot';
			continue;
		}
		// Un bout de phrase pris pour un nom : « Si ceux… », « Dans la… ».
		if ( in_array( mb_strtolower( strtok( $nom, ' ' ), 'UTF-8' ), array( 'si', 'ce', 'ceux', 'celui', 'dans', 'tandis', 'le', 'la', 'les', 'un', 'une', 'par', 'de', 'du', 'des', 'en', 'pour', 'avec' ), true ) ) {
			$ecart[ $nom ] = 'pas un nom';
			continue;
		}
		if ( '' !== $titre && false !== mb_stripos( $titre, $nom ) ) {
			$ecart[ $nom ] = 'dans le titre';
			continue;
		}
		$noms[] = $nom;
	}
	return array_values( array_unique( $noms ) );
}

/**
 * Ce que le catalogue dit des rôles d'un livre : traducteurs, directeurs, éditeurs, ce qui est mis de côté.
 *
 * @param int $id Le livre.
 * @return array{traduction: array, direction: array, editeurs: string[], ecart: array}
 */
function er_roles_releves( $id ) {
	$titre  = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	$releve = array(
		'traduction'   => array(),
		'direction'    => array(),
		'preface'      => array(),
		'edition'      => array(),
		'illustration' => array(),
		'editeurs'     => array(),
		'ecart'        => array(),
	);
	$titre_seul = preg_replace( '/\s*[-–—]?\s*sous la direction d.*$/iu', '', $titre );
	foreach ( er_roles_noms( (string) get_post_meta( $id, 'er_traducteur', true ), $titre_seul, $releve['ecart'] ) as $nom ) {
		$releve['traduction'][ $nom ] = 'méta';
	}
	if ( preg_match( '/sous la direction d(?:e\s+|[’\'])(.+?)\)?\s*$/iu', $titre, $m ) ) {
		foreach ( er_roles_noms( $m[1], '', $releve['ecart'] ) as $nom ) {
			$releve['direction'][ $nom ] = 'titre';
		}
	}
	$post  = get_post( $id );
	$texte = $titre . "\n" . wp_strip_all_tags( $post ? $post->post_excerpt . "\n" . $post->post_content : '' );
	$nom   = '\p{Lu}[\p{L}’\'\-]+(?:\s+(?:de\s+|du\s+|d[’\']|von\s+|van\s+)?\p{Lu}[\p{L}’\'\-]+){1,3}';
	if ( preg_match_all( '/tradu(?:it|ite|its|ction)\s+(?:(?:du|de l[’\']|de)\s+\p{Ll}+\s+)?(?:par|de)\s+(' . $nom . '(?:\s*(?:,|et)\s*' . $nom . ')*)/u', $texte, $mm ) ) {
		foreach ( $mm[1] as $trouve ) {
			foreach ( er_roles_noms( $trouve, $titre_seul, $releve['ecart'] ) as $n ) {
				if ( ! isset( $releve['traduction'][ $n ] ) ) {
					$releve['traduction'][ $n ] = 'texte';
				}
			}
		}
	}
	// Les autres rôles : « Préface de Georges Nivat », « édition établie et annotée par… », « illustrations d'Ivan
	// Bilibine ». Dans les textes du livre et dans la méta « Traducteur, préfacier » de Wix, qui les mêlait.
	$tout   = $texte . "\n" . wp_strip_all_tags( html_entity_decode( (string) get_post_meta( $id, 'er_traducteur', true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$motifs = array(
		'preface'      => '(?:pr[ée]fac(?:e|[ée]e?s?)|postface|avant-propos|introduction|pr[ée]sentation|pr[ée]sent[ée]e?s?)',
		'edition'      => '(?:[ée]dition\s+[ée]tablie|[ée]tablie?s?|annot[ée]e?s?|notes|choix(?:\s+de\s+textes)?|commentaires?)',
		'illustration' => '(?:illustr(?:ations?|[ée]e?s?)|dessins)',
	);
	foreach ( $motifs as $role => $motif ) {
		if ( ! preg_match_all( '/\b' . $motif . '\s+(?:et\s+\p{Ll}+\s+)?(?:de\s+|par\s+|d[’\'])\s*(' . $nom . '(?:\s*(?:,|et)\s*' . $nom . ')*)/iu', $tout, $mm ) ) {
			continue;
		}
		foreach ( $mm[1] as $trouve ) {
			$ecart = array();
			// Le texte de Wix a perdu ses retours à la ligne : « Actes Sud BDMisha Zaslavsky » se coupe aussi entre deux
			// majuscules dont la seconde ouvre un mot.
			$trouve = preg_replace( '/(?<=\p{Lu})(?=\p{Lu}\p{Ll})/u', "\n", $trouve );
			foreach ( er_roles_noms( $trouve, $titre_seul, $ecart ) as $n ) {
				// Ce qui n'est pas une personne : un mot du texte collé au nom (« Collection Folio », « UN RÔLE »,
				// « Bibliothèque Rivages »), un mot trop long pour un nom (« Pascalbibliographie »), un mot qui mêle
				// l'alphabet latin et le cyrillique (« Pasquierь »).
				// Un titre ou une collection pris pour un nom aussi : « Autobiographie d'Anna Akhmatova », « Albums du
				// Père Castor ».
				if ( preg_match( '/^(?:collection|biblioth\p{L}*|aujourd\S*|le|la|les|un|une|des|du|de|[ée]ditions?|traduction|tome|volume|livre|roman|albums?|autobiographie|biographie|m[ée]moires|journal|lettres|correspondance|contes?|po[èe]mes?|po[ée]sies?|[œo]uvres?|r[ée]cits?|nouvelles|histoire|souvenirs|essais?|textes?)\b/iu', $n )
					|| preg_match( '/\b\p{Lu}{2,}\b/u', $n )
					|| preg_match( '/[\p{L}’\']{15,}/u', $n )
					|| preg_match( '/(?=[\p{L}]*\p{Latin})(?=[\p{L}]*\p{Cyrillic})[\p{L}]+/u', $n ) ) {
					continue;
				}
				$releve[ $role ][ $n ] = 'texte';
			}
		}
	}
	// L'éditeur : une co-édition (« Русский путь / YMCA-Press ») en donne deux ; ce qui n'est pas un nom est écarté.
	$editeur = trim( wp_strip_all_tags( html_entity_decode( (string) get_post_meta( $id, 'er_editeur', true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	foreach ( preg_split( '/\s+\/\s+/u', $editeur ) as $e ) {
		$e = trim( $e, " \t\n\r\0\x0B&,;" );
		if ( '' === $e ) {
			continue;
		}
		if ( mb_strlen( $e ) < 3 || ! preg_match( '/^\p{L}/u', $e ) || false !== strpos( $e, '@' ) ) {
			$releve['ecart'][ $e ] = 'éditeur douteux';
			continue;
		}
		$releve['editeurs'][] = $e;
	}
	return $releve;
}

/**
 * La clé d'un nom d'éditeur, pour reconnaître ses variantes : « Éditions Fayard » et « Fayard », « Éditions de
 * l’EHESS » et « Éditions EHESS », « Institut d’études slaves » et « Institut dEtudes Slaves ».
 *
 * @param string $nom Le nom.
 */
function er_editeur_cle( $nom ) {
	$cle = mb_strtolower( remove_accents( $nom ), 'UTF-8' );
	$cle = preg_replace( '/\b(?:editions?|editeurs?|publishing|publications?|publisher)\b/u', ' ', $cle );
	$cle = preg_replace( '/^\s*(?:de la|de l|du|des|de|d|l)[\s’\']+/u', '', trim( $cle ) );
	return trim( preg_replace( '/[^\p{L}\p{N}]+/u', '', $cle ) );
}

/**
 * Regroupe les éditeurs relevés par variantes : chaque nom → le nom retenu (la variante la plus fréquente). Deux clés
 * à une lettre près (« Barthillat », « Bartillat » ; « Ginkgo », « Gingko ») vont ensemble.
 *
 * @param array<string,int> $comptes Nom → nombre de livres.
 * @return array<string,string>
 */
function er_editeurs_regroupes( $comptes ) {
	arsort( $comptes );
	$groupes = array(); // clé → nom retenu.
	$retenu  = array();
	foreach ( $comptes as $nom => $n ) {
		$cle = er_editeur_cle( $nom );
		$va  = null;
		foreach ( $groupes as $autre => $chef ) {
			if ( $autre === $cle || ( mb_strlen( $cle ) >= 5 && levenshtein( $autre, $cle ) <= 1 ) || ( mb_strlen( $cle ) >= 6 && levenshtein( $autre, $cle ) <= 2 && similar_text( $autre, $cle ) >= mb_strlen( $cle ) - 2 ) ) {
				$va = $chef;
				break;
			}
		}
		if ( null === $va ) {
			$groupes[ $cle ] = $nom;
			$va              = $nom;
		}
		$retenu[ $nom ] = $va;
	}
	return $retenu;
}

/**
 * Réunit les graphies d'une même personne : avec un auteur qui existe déjà (« Natalia Soljénistyne » → « Natalia
 * Soljénitsyne »), ou entre elles (« Paul Lesquesne » et « Paul Lequesne », « G. Arout » et « Gabriel Arout »,
 * « Boris de Schloezer » et « Boris de Schlœzer »). Chaque nom → le nom retenu : l'auteur qui existe, sinon la graphie
 * la plus fréquente.
 *
 * @param array<string,int> $comptes Nom → nombre de livres.
 * @return array<string,string>
 */
function er_personnes_regroupees( $comptes ) {
	$cle = function ( $nom ) {
		return preg_replace( '/[^a-z]+/', '', strtolower( str_replace( array( 'œ', 'Œ' ), 'oe', remove_accents( $nom ) ) ) );
	};
	$proche = function ( $a, $b ) use ( $cle ) {
		$ka = $cle( $a );
		$kb = $cle( $b );
		if ( $ka === $kb ) {
			return true;
		}
		if ( min( strlen( $ka ), strlen( $kb ) ) >= 9 && levenshtein( $ka, $kb ) <= 2 ) {
			return true;
		}
		// « G. Arout » et « Gabriel Arout » : même nom de famille, même initiale.
		foreach ( array( array( $a, $b ), array( $b, $a ) ) as list( $court, $long ) ) {
			if ( preg_match( '/^(\p{Lu})\.\s*(.+)$/u', $court, $m ) && preg_match( '/^(\p{Lu})\p{L}*\s+(.+)$/u', $long, $n ) && $m[1] === $n[1] && $cle( $m[2] ) === $cle( $n[2] ) ) {
				return true;
			}
		}
		return false;
	};
	$existants = get_terms(
		array(
			'taxonomy'   => 'auteur',
			'hide_empty' => false,
			'fields'     => 'names',
			'number'     => 5000,
		)
	);
	$existants = is_wp_error( $existants ) ? array() : $existants;
	arsort( $comptes );
	$retenus = array();
	$chefs   = array();
	foreach ( array_keys( $comptes ) as $nom ) {
		$va = null;
		foreach ( $existants as $e ) {
			if ( $proche( $nom, $e ) ) {
				$va = $e;
				break;
			}
		}
		if ( null === $va ) {
			foreach ( $chefs as $c ) {
				if ( $proche( $nom, $c ) ) {
					$va = $c;
					break;
				}
			}
		}
		if ( null === $va ) {
			// La forme complète l'emporte sur l'initiale.
			$va      = $nom;
			$chefs[] = $nom;
		}
		$retenus[ $nom ] = $va;
	}
	return $retenus;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Relève les traducteurs, les directeurs et les éditeurs du catalogue, et les pose avec « appliquer » :
	 * wp er roles [appliquer]. Ce qui est mis de côté est listé, à corriger à la main dans la fiche du livre.
	 *
	 * @param array $args « appliquer », ou rien pour seulement montrer.
	 */
	WP_CLI::add_command(
		'er roles',
		function ( $args ) {
			$appliquer = isset( $args[0] ) && 'appliquer' === $args[0];
			$ids       = ( new WP_Query( er_requete_livres_en_vente() ) )->posts;
			$releves   = array();
			$personnes = array();
			$nouveaux  = array();
			$editeurs  = array();
			$ecarts    = array();
			$roles     = array_column( er_roles_personnes(), 'cle' );
			$comptes   = array_fill_keys( $roles, 0 ) + array( 'editeur' => 0 );
			foreach ( $ids as $id ) {
				$r              = er_roles_releves( $id );
				$releves[ $id ] = $r;
				foreach ( $roles as $role ) {
					$comptes[ $role ] += $r[ $role ] ? 1 : 0;
					foreach ( array_keys( $r[ $role ] ) as $nom ) {
						$personnes[ $nom ] = ( isset( $personnes[ $nom ] ) ? $personnes[ $nom ] : 0 ) + 1;
						if ( ! er_personne_par_nom( $nom ) ) {
							$nouveaux[ $nom ] = true;
						}
					}
				}
				$comptes['editeur'] += $r['editeurs'] ? 1 : 0;
				foreach ( $r['editeurs'] as $e ) {
					$editeurs[ $e ] = ( isset( $editeurs[ $e ] ) ? $editeurs[ $e ] : 0 ) + 1;
				}
				foreach ( $r['ecart'] as $nom => $raison ) {
					$ecarts[] = '  ' . $nom . ' · ' . $raison . ' · ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) . ' (' . get_post_field( 'post_name', $id ) . ')';
				}
			}
			$regroupes = er_editeurs_regroupes( $editeurs );
			$memes     = er_personnes_regroupees( $personnes );
			foreach ( $memes as $nom => $chef ) {
				if ( $nom !== $chef ) {
					unset( $nouveaux[ $nom ] );
					if ( ! er_personne_par_nom( $chef ) ) {
						$nouveaux[ $chef ] = true;
					}
				} elseif ( ! er_personne_par_nom( $nom ) ) {
					$nouveaux[ $nom ] = true;
				}
			}
			if ( $appliquer ) {
				foreach ( $releves as $id => $r ) {
					foreach ( er_roles_personnes() as $taxonomie => $def ) {
						$role = $def['cle'];
						if ( ! empty( $r[ $role ] ) ) {
							er_poser_personnes( $id, $taxonomie, array_values( array_unique( array_map( fn( $n ) => $memes[ $n ], array_keys( $r[ $role ] ) ) ) ), true );
						}
					}
					if ( $r['editeurs'] ) {
						wp_set_object_terms( $id, array_values( array_unique( array_map( fn( $e ) => $regroupes[ $e ], $r['editeurs'] ) ) ), 'er_editeur', false );
					}
				}
			}
			WP_CLI::log( ( $appliquer ? 'Posé' : 'Relevé (rien n’est posé ; « wp er roles appliquer » pour poser)' ) . ' sur ' . count( $ids ) . ' livres en vente :' );
			WP_CLI::log( '  ' . $comptes['traduction'] . ' livres avec une traduction, ' . $comptes['direction'] . ' sous la direction de quelqu’un, ' . $comptes['preface'] . ' avec une préface ou une présentation, ' . $comptes['edition'] . ' avec une édition établie ou des notes, ' . $comptes['illustration'] . ' illustrés, ' . $comptes['editeur'] . ' avec un éditeur.' );
			$par_chef = array();
			foreach ( $memes as $nom => $chef ) {
				$par_chef[ $chef ][] = $nom;
			}
			WP_CLI::log( count( $par_chef ) . ' personnes (' . count( $personnes ) . ' graphies), dont ' . count( $nouveaux ) . ' qui ne sont pas encore des auteurs' . ( $appliquer ? ' (créées)' : '' ) . ' :' );
			foreach ( $par_chef as $chef => $noms ) {
				$autres = array_diff( $noms, array( $chef ) );
				$total  = array_sum( array_intersect_key( $personnes, array_flip( $noms ) ) );
				WP_CLI::log( '  ' . $chef . ' (' . $total . ')' . ( isset( $nouveaux[ $chef ] ) ? ' · nouveau' : '' ) . ( $autres ? ' ← ' . implode( ', ', $autres ) : '' ) );
			}
			$retenus = array_count_values( $regroupes );
			WP_CLI::log( count( $retenus ) . ' éditeurs (' . count( $editeurs ) . ' graphies) :' );
			arsort( $editeurs );
			foreach ( array_unique( $regroupes ) as $chef ) {
				$variantes = array_keys( array_filter( $regroupes, fn( $c, $nom ) => $c === $chef && $nom !== $chef, ARRAY_FILTER_USE_BOTH ) );
				$total     = array_sum( array_intersect_key( $editeurs, array_flip( array_merge( array( $chef ), $variantes ) ) ) );
				WP_CLI::log( '  ' . $chef . ' (' . $total . ')' . ( $variantes ? ' ← ' . implode( ', ', $variantes ) : '' ) );
			}
			WP_CLI::log( count( $ecarts ) . ' mentions mises de côté, à revoir dans la fiche du livre :' );
			foreach ( $ecarts as $e ) {
				WP_CLI::log( $e );
			}
			wp_cache_delete( 'er_personnes_livres', 'er' );
		}
	);
}
