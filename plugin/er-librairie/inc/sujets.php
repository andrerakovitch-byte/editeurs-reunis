<?php
/**
 * Les étiquettes des articles du journal et des rencontres : les auteurs et les sujets.
 *
 * - Les auteurs sont le classement des livres, étendu aux articles et aux rencontres : sur un livre, son auteur ; sur
 *   un article ou une rencontre, les auteurs de la librairie qui y sont liés (on en parle, ils l'ont écrit). Le compte
 *   d'un auteur reste son nombre de livres (« Gouzel Iakhina · 3 livres »).
 * - Les sujets (er_sujet) sont un classement libre des articles et des rencontres : l'émigration, la poésie, le
 *   Goulag… avec leur page, /sujet/<slug>/.
 *
 * Ils relient le site : la page d'un auteur montre, sous ses livres, les rencontres et les articles qui le concernent ;
 * la page d'un sujet, ses rencontres et ses articles ; un article ou une rencontre, ses étiquettes et ce qui lui est
 * proche (« À lire, à revoir »). Les étiquettes WordPress des articles (post_tag) laissent la place aux sujets.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_taxonomy(
			'er_sujet',
			array( 'post', ER_EVT ),
			array(
				'labels'            => array(
					'name'                       => __( 'Sujets', 'er-librairie' ),
					'singular_name'              => __( 'Sujet', 'er-librairie' ),
					'search_items'               => __( 'Chercher un sujet', 'er-librairie' ),
					'all_items'                  => __( 'Tous les sujets', 'er-librairie' ),
					'edit_item'                  => __( 'Modifier le sujet', 'er-librairie' ),
					'add_new_item'               => __( 'Ajouter un sujet', 'er-librairie' ),
					'separate_items_with_commas' => __( 'Séparer les sujets par des virgules', 'er-librairie' ),
					'choose_from_most_used'      => __( 'Choisir parmi les plus utilisés', 'er-librairie' ),
					'not_found'                  => __( 'Aucun sujet', 'er-librairie' ),
					'menu_name'                  => __( 'Sujets', 'er-librairie' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'sujet' ),
			)
		);
		register_taxonomy_for_object_type( 'auteur', 'post' );
		register_taxonomy_for_object_type( 'auteur', ER_EVT );
		unregister_taxonomy_for_object_type( 'post_tag', 'post' );
	},
	7
);

/**
 * Le compte d'un auteur : ses livres seulement, pas les articles ni les rencontres qui le concernent.
 *
 * @param array       $termes    Les term_taxonomy_id.
 * @param WP_Taxonomy $taxonomie Le classement.
 */
function er_compter_livres( $termes, $taxonomie ) {
	global $wpdb;
	foreach ( (array) $termes as $tt ) {
		$n = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr JOIN {$wpdb->posts} p ON p.ID = tr.object_id
				WHERE tr.term_taxonomy_id = %d AND p.post_type = 'product' AND p.post_status = 'publish'",
				$tt
			)
		);
		do_action( 'edit_term_taxonomy', $tt, $taxonomie->name );
		$wpdb->update( $wpdb->term_taxonomy, array( 'count' => $n ), array( 'term_taxonomy_id' => $tt ) ); // phpcs:ignore WordPress.DB
		do_action( 'edited_term_taxonomy', $tt, $taxonomie->name );
	}
}

// La page d'un auteur reste celle de ses livres : les articles et les rencontres viennent dessous (er/autour).
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_tax( 'auteur' ) ) {
			$q->set( 'post_type', 'product' );
		}
	},
	5
);

/* Les sujets devinés --------------------------------------------------------------------------------------------
 *
 * Pour l'import (et `wp er sujets deviner`) : des sujets de départ, reconnus par des mots du titre et du chapô, en
 * français et en russe. Une liste à reprendre à la main (Articles → Sujets) : on renomme, on fusionne, on ajoute ;
 * deviner n'ôte jamais un sujet posé à la main.
 */

/**
 * Les sujets de départ : nom → motif.
 */
function er_sujets_de_depart() {
	return array(
		'Poésie'              => '/po[ée]sie|po[èe]te|po[ée]tesse|po[ée]tique|поэ[тз]|стих/iu',
		'Émigration'          => '/[ée]migr|exil|диаспор|эмигра|изгнан/iu',
		'Goulag et dissidence' => '/goulag|gulag|гулаг|dissiden|диссидент|r[ée]pression|репресс|archipel du|архипелаг|samizdat|самиздат|terreur|kolyma|колым/iu',
		'Orthodoxie'          => '/(?i:orthodox|[ée]glise|th[ée]olog|liturg|православ|христиан|церк|богослов|ic[ôo]ne|икон|святы)|\b[Pp][èe]re [A-ZÀ-Ý]|[Оо]т(?:ец|цом) [А-Я]/u',
		'Histoire'            => '/histoire|historien|истори|r[ée]volution|революц|guerre|войн|m[ée]moire|память/iu',
		'Musique'             => '/musique|musical|compositeur|op[ée]ra|concert|музык|композитор|опер[аы]|\bchants?\b/iu',
		'Cinéma'              => '/cin[ée]ma|\bfilms?\b|r[ée]alisateur|кино|фильм|режисс/iu',
		'Arts'                => '/exposition|peint|gravure|\bart\b|artiste|sculpt|photograph|выставк|художник|живопис|графи/iu',
		'Traduction'          => '/traduction|traduct|traduire|перевод/iu',
		'Philosophie'         => '/philosoph|философ|pens[ée]e russe/iu',
		'Ukraine'             => '/ukrain|украин/iu',
		'Théâtre'             => '/th[ée][âa]tre|lecture[- ]spectacle|театр|спектакл/iu',
	);
}

/**
 * Les sujets qu'un texte évoque.
 *
 * @param string $texte Le titre et le chapô.
 */
function er_sujets_deviner( $texte ) {
	$sujets = array();
	foreach ( er_sujets_de_depart() as $nom => $motif ) {
		if ( preg_match( $motif, $texte ) ) {
			$sujets[] = $nom;
		}
	}
	return $sujets;
}

/**
 * Les auteurs de la librairie qu'un texte nomme, et s'ils y interviennent (« avec Georges Nivat », « par Hélène
 * Henry ») ou si l'on en parle (« le Docteur Jivago de Boris Pasternak »). Le nom du centre ne compte pas.
 *
 * @param string $texte   Le titre et le chapô.
 * @param array  $auteurs er_evt_auteurs_connus().
 * @return array [ [ le terme, ses noms, intervient ] ].
 */
function er_auteurs_cites( $texte, $auteurs ) {
	$cle = ' ' . er_cle_recherche( $texte ) . ' ';
	$cle = str_replace( ' ' . er_cle_recherche( 'Centre culturel Alexandre Soljenitsyne' ) . ' ', ' ', $cle );
	$cites = array();
	foreach ( $auteurs as $nom => list( $t, $noms ) ) {
		$position = strpos( $cle, ' ' . $nom . ' ' );
		if ( false === $position || isset( $cites[ $t->term_id ] ) ) {
			continue;
		}
		$avant                  = array_slice( explode( ' ', trim( substr( $cle, 0, $position ) ) ), -3 );
		$cites[ $t->term_id ] = array( $t, $noms, (bool) array_intersect( $avant, array( 'avec', 'par', 'presenc', 'recevons', 'invit', 'invite' ) ) );
	}
	return array_values( $cites );
}

/**
 * Pose sur un article ou une rencontre les sujets et les auteurs devinés (sans rien ôter).
 *
 * @param int    $id      Le contenu.
 * @param string $texte   Le titre et le chapô.
 * @param array  $auteurs er_evt_auteurs_connus().
 * @return array Les sujets et les auteurs posés.
 */
function er_etiqueter( $id, $texte, $auteurs ) {
	$sujets = er_sujets_deviner( $texte );
	if ( $sujets ) {
		wp_set_object_terms( $id, $sujets, 'er_sujet', true );
	}
	$poses = array();
	foreach ( er_auteurs_cites( $texte, $auteurs ) as list( $t, , $intervient ) ) {
		if ( $intervient && ER_EVT === get_post_type( $id ) ) {
			continue; // Un intervenant a sa ligne « Avec » (er_intervenant), posée par l'import des rencontres.
		}
		wp_set_object_terms( $id, $t->term_id, 'auteur', true );
		$poses[] = $t->name;
	}
	return array_merge( $sujets, $poses );
}

/* L'affichage ------------------------------------------------------------------------------------------------------
 *
 * Trois blocs dynamiques : er/etiquettes (les auteurs et les sujets d'un article ou d'une rencontre, en pilules),
 * er/proches (« À lire, à revoir » : ce qui partage le plus d'étiquettes) et er/autour (sur la page d'un auteur ou
 * d'un sujet : ses rencontres et ses articles).
 */

add_action(
	'init',
	function () {
		$blocs = array(
			'er/etiquettes' => 'er_bloc_etiquettes',
			'er/proches'    => 'er_bloc_proches',
			'er/autour'     => 'er_bloc_autour',
		);
		foreach ( $blocs as $nom => $rendu ) {
			register_block_type(
				$nom,
				array(
					'api_version'     => 3,
					'uses_context'    => array( 'postId' ),
					'render_callback' => function ( $attributs, $contenu, $bloc ) use ( $rendu ) {
						$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID();
						return call_user_func( $rendu, $id );
					},
				)
			);
		}
	},
	8
);

/**
 * Les auteurs et les sujets d'un article ou d'une rencontre, en pilules qui mènent à leur page.
 *
 * @param int $id Le contenu.
 */
function er_bloc_etiquettes( $id ) {
	$lignes = array(
		'auteur'   => __( 'Auteurs', 'er-librairie' ),
		'er_sujet' => __( 'Sujets', 'er-librairie' ),
	);
	$html   = '';
	foreach ( $lignes as $taxonomie => $libelle ) {
		$termes = wp_get_post_terms( $id, $taxonomie );
		if ( ! $termes || is_wp_error( $termes ) ) {
			continue;
		}
		$pilules = '';
		foreach ( $termes as $t ) {
			$lien     = get_term_link( $t );
			$pilules .= '<li><a href="' . esc_url( is_wp_error( $lien ) ? '' : $lien ) . '">' . esc_html( $t->name ) . '</a></li>';
		}
		$html .= '<div class="er-etiquettes-ligne"><span class="er-etiquettes-libelle">' . esc_html( $libelle ) . '</span><ul class="er-pilules">' . $pilules . '</ul></div>';
	}
	return $html ? '<div class="er-etiquettes">' . $html . '</div>' : '';
}

/**
 * Un article ou une rencontre, en carte : l'image, le genre et la date, le titre.
 *
 * @param WP_Post $p Le contenu.
 */
function er_carte_contenu( $p ) {
	if ( ER_EVT === $p->post_type ) {
		$genre = __( 'Rencontre', 'er-librairie' );
		$date  = er_evt_quand( $p->ID, false );
	} else {
		$genre = __( 'Journal', 'er-librairie' );
		$date  = get_the_date( __( 'j F Y', 'er-librairie' ), $p );
	}
	$image = get_the_post_thumbnail( $p, 'medium_large', array( 'alt' => '', 'loading' => 'lazy' ) );
	return '<li class="er-carte-contenu er-carte-' . ( ER_EVT === $p->post_type ? 'rencontre' : 'article' ) . '"><a href="' . esc_url( get_permalink( $p ) ) . '">'
		. '<span class="er-carte-image">' . $image . '</span>'
		. '<span class="er-carte-genre">' . esc_html( $genre ) . ' · ' . esc_html( $date ) . '</span>'
		. '<span class="er-carte-titre">' . esc_html( get_the_title( $p ) ) . '</span>'
		. ( ER_EVT === $p->post_type && er_evt_videos( $p->ID ) ? '<span class="er-evt-marques"><span class="er-evt-marque er-evt-marque-video">' . esc_html__( 'Vidéo', 'er-librairie' ) . '</span></span>' : '' )
		. '</a></li>';
}

/**
 * « À lire, à revoir » : jusqu'à quatre articles ou rencontres qui partagent le plus d'auteurs et de sujets.
 *
 * @param int $id Le contenu.
 */
function er_bloc_proches( $id ) {
	$requete = array( 'relation' => 'OR' );
	$miens   = array();
	foreach ( array( 'auteur', 'er_sujet', 'er_intervenant', 'er_cycle' ) as $taxonomie ) {
		$ids = wp_get_post_terms( $id, $taxonomie, array( 'fields' => 'ids' ) );
		if ( $ids && ! is_wp_error( $ids ) ) {
			$requete[]            = array( 'taxonomy' => $taxonomie, 'terms' => $ids );
			$miens[ $taxonomie ] = $ids;
		}
	}
	if ( count( $requete ) < 2 ) {
		return '';
	}
	$candidats = get_posts(
		array(
			'post_type'      => array( 'post', ER_EVT ),
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'post__not_in'   => array( $id ),
			'tax_query'      => $requete, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	// Les plus proches d'abord : un auteur en commun compte plus qu'un sujet, un sujet plus qu'un cycle.
	$poids  = array(
		'auteur'         => 3,
		'er_intervenant' => 3,
		'er_sujet'       => 2,
		'er_cycle'       => 1,
	);
	$scores = array();
	foreach ( $candidats as $c ) {
		$s = 0;
		foreach ( $miens as $taxonomie => $ids ) {
			$leurs = wp_get_post_terms( $c->ID, $taxonomie, array( 'fields' => 'ids' ) );
			$s    += $poids[ $taxonomie ] * count( array_intersect( $ids, is_wp_error( $leurs ) ? array() : $leurs ) );
		}
		$scores[ $c->ID ] = $s * 100000000 + (int) get_post_timestamp( $c ) / 100;
	}
	arsort( $scores );
	$choisis = array_slice( array_keys( $scores ), 0, 4 );
	if ( ! $choisis ) {
		return '';
	}
	update_post_thumbnail_cache( new WP_Query( array( 'post__in' => $choisis, 'post_type' => array( 'post', ER_EVT ), 'posts_per_page' => 4 ) ) );
	return '<section class="er-proches"><h2 class="er-evt-section">' . esc_html__( 'À lire, à revoir', 'er-librairie' ) . '</h2><ul class="er-cartes">'
		. implode( '', array_map( 'er_carte_contenu', array_map( 'get_post', $choisis ) ) ) . '</ul></section>';
}

/**
 * Sur la page d'un auteur ou d'un sujet : ses rencontres (à venir, puis passées) et ses articles.
 */
function er_bloc_autour() {
	$terme = get_queried_object();
	if ( ! $terme instanceof WP_Term || ! in_array( $terme->taxonomy, array( 'auteur', 'er_sujet' ), true ) ) {
		return '';
	}
	$sujet = 'er_sujet' === $terme->taxonomy;
	$tax   = array( array( 'taxonomy' => $terme->taxonomy, 'terms' => $terme->term_id ) );
	if ( ! $sujet ) {
		$intervenant = get_term_by( 'slug', $terme->slug, 'er_intervenant' );
		if ( $intervenant ) {
			$tax = array( 'relation' => 'OR', $tax[0], array( 'taxonomy' => 'er_intervenant', 'terms' => $intervenant->term_id ) );
		}
	}
	$rencontres = get_posts(
		array(
			'post_type'      => ER_EVT,
			'post_status'    => 'publish',
			'posts_per_page' => $sujet ? 60 : 12,
			'tax_query'      => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_key'       => 'er_debut', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);
	$a_venir    = array_reverse( array_filter( $rencontres, fn( $p ) => ! er_evt_passee( $p->ID ) ) );
	$passees    = array_filter( $rencontres, fn( $p ) => er_evt_passee( $p->ID ) );
	$articles   = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $sujet ? 30 : 6,
			'tax_query'      => array( array( 'taxonomy' => $terme->taxonomy, 'terms' => $terme->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	update_post_thumbnail_cache( new WP_Query( array( 'post__in' => array_merge( array( 0 ), wp_list_pluck( $rencontres, 'ID' ), wp_list_pluck( $articles, 'ID' ) ), 'post_type' => array( 'post', ER_EVT ), 'posts_per_page' => -1 ) ) );
	$html = '';
	if ( $a_venir ) {
		$html .= '<section class="er-autour-section"><h2 class="er-evts-titre">' . esc_html__( 'Prochainement au centre', 'er-librairie' ) . '</h2>'
			. '<ol class="er-evts-lignes">' . implode( '', array_map( 'er_evt_ligne', $a_venir ) ) . '</ol></section>';
	}
	if ( $passees ) {
		$html .= '<section class="er-autour-section"><h2 class="er-evts-titre">' . esc_html( $sujet ? __( 'Les rencontres', 'er-librairie' ) : __( 'Au centre', 'er-librairie' ) ) . '</h2>'
			. '<ul class="er-evts-grille">' . implode( '', array_map( 'er_evt_carte', $passees ) ) . '</ul></section>';
	}
	if ( $articles ) {
		$html .= '<section class="er-autour-section"><h2 class="er-evts-titre">' . esc_html__( 'Dans le journal', 'er-librairie' ) . '</h2>'
			. '<ul class="er-cartes">' . implode( '', array_map( 'er_carte_contenu', $articles ) ) . '</ul></section>';
	}
	if ( '' === $html && $sujet ) {
		$html = '<p class="er-evts-vide">' . esc_html__( 'Rien encore sur ce sujet.', 'er-librairie' ) . '</p>';
	}
	return $html ? '<div class="er-autour alignwide">' . $html . '</div>' : '';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Devine les sujets et les auteurs des articles et des rencontres déjà en place, d'après leur titre et leur chapô
	 * (sans rien ôter).
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : post, er_evenement, ou les deux (par défaut).
	 *
	 * [--simulation]
	 * : Dit ce qui serait posé, sans rien poser.
	 *
	 * @param array $args  Rien.
	 * @param array $assoc Les options.
	 */
	WP_CLI::add_command(
		'er sujets deviner',
		function ( $args, $assoc ) {
			$types   = empty( $assoc['type'] ) ? array( 'post', ER_EVT ) : array( $assoc['type'] );
			$auteurs = er_evt_auteurs_connus();
			$n       = 0;
			foreach ( get_posts( array( 'post_type' => $types, 'post_status' => 'publish', 'numberposts' => -1, 'lang' => '' ) ) as $p ) {
				$texte = $p->post_title . ' ' . $p->post_excerpt;
				if ( ! empty( $assoc['simulation'] ) ) {
					$poses = array_merge( er_sujets_deviner( $texte ), array_map( fn( $c ) => $c[0]->name . ( $c[2] ? ' (intervient)' : '' ), er_auteurs_cites( $texte, $auteurs ) ) );
				} else {
					$poses = er_etiqueter( $p->ID, $texte, $auteurs );
				}
				if ( $poses ) {
					++$n;
					WP_CLI::log( $p->post_title . ' → ' . implode( ', ', $poses ) );
				}
			}
			WP_CLI::success( $n . ' contenus étiquetés.' );
		}
	);
}
