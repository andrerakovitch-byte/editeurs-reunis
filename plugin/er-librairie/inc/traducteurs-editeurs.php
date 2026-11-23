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
		),
		'er_direction'  => array(
			'fiche' => _x( 'Sous la direction de', 'fiche d’un livre', 'er-librairie' ),
			'boite' => __( 'Sous la direction de', 'er-librairie' ),
			'aide'  => __( 'Les directeurs de l’ouvrage, séparés par des virgules : des auteurs (un nom nouveau devient un auteur). Le livre paraît sur leur page.', 'er-librairie' ),
			'meta'  => '',
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
 * Une personne par son nom : l'auteur qui le porte (sans tenir compte des majuscules ni des accents), sinon un auteur
 * nouveau quand $creer est vrai.
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
	$t = get_term_by( 'name', $nom, 'auteur' );
	if ( ! $t ) {
		$t = get_term_by( 'slug', sanitize_title( $nom ), 'auteur' );
	}
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
		'auteur'     => array(),
		'traduction' => array(),
		'direction'  => array(),
		'autour'     => array(),
		'tous'       => array(),
	);
	if ( ! $auteur || is_wp_error( $auteur ) ) {
		return $vide;
	}
	$cache = wp_cache_get( 'er_personnes_livres', 'er' );
	$cache = is_array( $cache ) ? $cache : array();
	if ( isset( $cache[ $auteur->term_id ] ) ) {
		return $cache[ $auteur->term_id ];
	}
	$par_role = array(
		'auteur'     => array( 'auteur', 'term_id', $auteur->term_id ),
		'traduction' => array( 'er_traduction', 'slug', $auteur->slug ),
		'direction'  => array( 'er_direction', 'slug', $auteur->slug ),
		'autour'     => array( 'er_autour', 'slug', $auteur->slug ),
	);
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
	$livres['tous']               = array_values( array_unique( array_merge( $livres['auteur'], $livres['traduction'], $livres['direction'] ) ) );
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
 * Coupe une mention en noms : « Hélène Henry et Michel Aucouturier », « A, B & C ». Ce qui n'a pas l'air d'un nom
 * (des chiffres, un seul mot en minuscules) est écarté.
 *
 * @param string $texte La mention.
 * @return string[]
 */
function er_roles_noms( $texte ) {
	$texte = trim( wp_strip_all_tags( html_entity_decode( (string) $texte, ENT_QUOTES, 'UTF-8' ) ) );
	$texte = preg_replace( '/^(?:trad(?:uit|uction|\.)?)\s*(?:du|de l[’\']|de|depuis le)?\s*(?:\p{Ll}+\s+)?(?:par|:)?\s*/iu', '', $texte );
	$noms  = array();
	foreach ( preg_split( '/\s*(?:,|;|&|\bet\b|\band\b|\bи\b)\s*/u', $texte ) as $nom ) {
		$nom = trim( $nom, " \t\n\r\0\x0B.()[]" );
		if ( '' === $nom || preg_match( '/\d/', $nom ) || ! preg_match( '/^\p{Lu}/u', $nom ) || mb_strlen( $nom ) > 60 ) {
			continue;
		}
		$noms[] = $nom;
	}
	return $noms;
}

/**
 * Ce que le catalogue dit des rôles d'un livre : traducteurs, directeurs, éditeur, et où il le dit.
 *
 * @param int $id Le livre.
 * @return array{traduction: array, direction: array, editeur: string}
 */
function er_roles_releves( $id ) {
	$releve = array(
		'traduction' => array(),
		'direction'  => array(),
		'editeur'    => trim( (string) get_post_meta( $id, 'er_editeur', true ) ),
	);
	foreach ( er_roles_noms( (string) get_post_meta( $id, 'er_traducteur', true ) ) as $nom ) {
		$releve['traduction'][ $nom ] = 'méta';
	}
	$titre = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	if ( preg_match( '/sous la direction d(?:e\s+|[’\'])(.+?)\)?\s*$/iu', $titre, $m ) ) {
		foreach ( er_roles_noms( $m[1] ) as $nom ) {
			$releve['direction'][ $nom ] = 'titre';
		}
	}
	$post  = get_post( $id );
	$texte = $titre . "\n" . wp_strip_all_tags( $post ? $post->post_excerpt . "\n" . $post->post_content : '' );
	$nom   = '\p{Lu}[\p{L}’\'\-]+(?:\s+(?:de\s+|von\s+|van\s+)?\p{Lu}[\p{L}’\'\-]+){1,3}';
	if ( preg_match_all( '/tradu(?:it|ite|its|ction)\s+(?:(?:du|de l[’\']|de)\s+\p{Ll}+\s+)?(?:par|de)\s+(' . $nom . '(?:\s*(?:,|et)\s*' . $nom . ')*)/u', $texte, $mm ) ) {
		foreach ( $mm[1] as $trouve ) {
			foreach ( er_roles_noms( $trouve ) as $n ) {
				if ( ! isset( $releve['traduction'][ $n ] ) ) {
					$releve['traduction'][ $n ] = 'texte';
				}
			}
		}
	}
	return $releve;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Relève les traducteurs, les directeurs et les éditeurs du catalogue, et les pose avec « appliquer » :
	 * wp er roles [appliquer].
	 *
	 * @param array $args « appliquer », ou rien pour seulement montrer.
	 */
	WP_CLI::add_command(
		'er roles',
		function ( $args ) {
			$appliquer = isset( $args[0] ) && 'appliquer' === $args[0];
			$ids       = ( new WP_Query( er_requete_livres_en_vente() ) )->posts;
			$comptes   = array(
				'traduction' => 0,
				'direction'  => 0,
				'editeur'    => 0,
			);
			$personnes = array();
			$editeurs  = array();
			$nouveaux  = array();
			$exemples  = array();
			foreach ( $ids as $id ) {
				$r = er_roles_releves( $id );
				foreach ( array(
					'traduction' => 'er_traduction',
					'direction'  => 'er_direction',
				) as $role => $taxonomie ) {
					if ( ! $r[ $role ] ) {
						continue;
					}
					++$comptes[ $role ];
					foreach ( $r[ $role ] as $nom => $source ) {
						$personnes[ $nom ] = ( isset( $personnes[ $nom ] ) ? $personnes[ $nom ] : 0 ) + 1;
						if ( ! er_personne_par_nom( $nom ) ) {
							$nouveaux[ $nom ] = true;
						}
						if ( count( $exemples ) < 40 ) {
							$exemples[] = '  ' . $role . ' | ' . $nom . ' | ' . $source . ' | ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
						}
					}
					if ( $appliquer ) {
						er_poser_personnes( $id, $taxonomie, array_keys( $r[ $role ] ), true );
					}
				}
				if ( '' !== $r['editeur'] ) {
					++$comptes['editeur'];
					$editeurs[ $r['editeur'] ] = ( isset( $editeurs[ $r['editeur'] ] ) ? $editeurs[ $r['editeur'] ] : 0 ) + 1;
					if ( $appliquer ) {
						wp_set_object_terms( $id, array( $r['editeur'] ), 'er_editeur', false );
					}
				}
			}
			WP_CLI::log( ( $appliquer ? 'Posé' : 'Relevé (rien n’est posé ; « wp er roles appliquer » pour poser)' ) . ' sur ' . count( $ids ) . ' livres en vente :' );
			WP_CLI::log( '  ' . $comptes['traduction'] . ' livres avec une traduction, ' . $comptes['direction'] . ' sous la direction de quelqu’un, ' . $comptes['editeur'] . ' avec un éditeur.' );
			WP_CLI::log( count( $personnes ) . ' personnes, dont ' . count( $nouveaux ) . ' qui ne sont pas encore des auteurs' . ( $appliquer ? ' (créées)' : '' ) . ' :' );
			arsort( $personnes );
			foreach ( array_slice( $personnes, 0, 60, true ) as $nom => $n ) {
				WP_CLI::log( '  ' . $nom . ' (' . $n . ')' . ( isset( $nouveaux[ $nom ] ) ? ' · nouveau' : '' ) );
			}
			WP_CLI::log( count( $editeurs ) . ' éditeurs :' );
			arsort( $editeurs );
			foreach ( array_slice( $editeurs, 0, 60, true ) as $nom => $n ) {
				WP_CLI::log( '  ' . $nom . ' (' . $n . ')' );
			}
			WP_CLI::log( 'Exemples :' );
			foreach ( $exemples as $e ) {
				WP_CLI::log( $e );
			}
			wp_cache_delete( 'er_personnes_livres', 'er' );
		}
	);
}
