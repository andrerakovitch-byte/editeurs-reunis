<?php
/**
 * Le journal du centre mis en page comme un journal en ligne (la piste D du canevas « Le journal du centre ») :
 *
 * - les genres d'un article (er_genre : chronique, critique, essai…), qui font le surtitre « Critique · Poésie » ;
 * - l'en-tête du journal (er/journal-tete) : le titre, les genres en pilules, la ligne des sujets ;
 * - le corps (er/journal-corps) : à la première page, la une (le texte choisi, deux autres, le fil), les chroniques,
 *   le grand format et trois sujets ; filtré par genre, par sujet ou en russe, et dans le fil, la liste des textes ;
 * - le surtitre d'un article (er/journal-surtitre) : « Le journal du centre · Critique ».
 *
 * Le texte à la une est le dernier article épinglé (« Mettre en avant » dans l'éditeur), sinon le plus récent.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les genres, par leur slug : le nom (au singulier, celui du terme) et le pluriel des pilules.
 */
function er_genres() {
	return array(
		'chronique' => array( _x( 'Chronique', 'genre d’un article', 'er-librairie' ), _x( 'Chroniques', 'genre d’un article', 'er-librairie' ) ),
		'critique'  => array( _x( 'Critique', 'genre d’un article', 'er-librairie' ), _x( 'Critiques', 'genre d’un article', 'er-librairie' ) ),
		'essai'     => array( _x( 'Essai', 'genre d’un article', 'er-librairie' ), _x( 'Essais', 'genre d’un article', 'er-librairie' ) ),
		'recit'     => array( _x( 'Récit', 'genre d’un article', 'er-librairie' ), _x( 'Récits', 'genre d’un article', 'er-librairie' ) ),
		'entretien' => array( _x( 'Entretien', 'genre d’un article', 'er-librairie' ), _x( 'Entretiens', 'genre d’un article', 'er-librairie' ) ),
		'hommage'   => array( _x( 'Hommage', 'genre d’un article', 'er-librairie' ), _x( 'Hommages', 'genre d’un article', 'er-librairie' ) ),
		'archive'   => array( _x( 'Archive', 'genre d’un article', 'er-librairie' ), _x( 'Archives', 'genre d’un article', 'er-librairie' ) ),
		'document'  => array( _x( 'Document', 'genre d’un article', 'er-librairie' ), _x( 'Documents', 'genre d’un article', 'er-librairie' ) ),
	);
}

add_action(
	'init',
	function () {
		register_taxonomy(
			'er_genre',
			array( 'post' ),
			array(
				'labels'            => array(
					'name'          => __( 'Genres', 'er-librairie' ),
					'singular_name' => __( 'Genre', 'er-librairie' ),
					'all_items'     => __( 'Tous les genres', 'er-librairie' ),
					'edit_item'     => __( 'Modifier le genre', 'er-librairie' ),
					'add_new_item'  => __( 'Ajouter un genre', 'er-librairie' ),
					'not_found'     => __( 'Aucun genre', 'er-librairie' ),
					'menu_name'     => __( 'Genres', 'er-librairie' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true, // Des cases à cocher dans l'éditeur, comme les rubriques.
				'rewrite'           => array( 'slug' => 'journal/genre' ),
			)
		);
		foreach ( array( 'er_langues', 'er_sujet_principal', 'er_lecture' ) as $cle ) {
			register_post_meta(
				'post',
				$cle,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
		if ( '1' !== get_option( 'er_genres_installes' ) ) {
			er_genres_installer();
		}
		foreach (
			array(
				'er/journal-tete'     => 'er_journal_tete',
				'er/journal-corps'    => 'er_journal_corps',
				'er/journal-surtitre' => 'er_journal_surtitre_bloc',
			) as $nom => $rendu
		) {
			register_block_type(
				$nom,
				array(
					'api_version'     => 3,
					'uses_context'    => array( 'postId' ),
					'render_callback' => function ( $attributs, $contenu, $bloc ) use ( $rendu ) {
						return call_user_func( $rendu, isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID() );
					},
				)
			);
		}
	}
);

/**
 * Crée les genres qui manquent. Relancé sans dommage.
 */
function er_genres_installer() {
	foreach ( er_genres() as $slug => list( $nom ) ) {
		if ( ! term_exists( $slug, 'er_genre' ) ) {
			wp_insert_term( $nom, 'er_genre', array( 'slug' => $slug ) );
		}
	}
	update_option( 'er_genres_installes', '1' );
}

// Le journal filtré : par sujet (/journal/?sujet=poesie), en russe (?en=russe), ou tout le fil (?fil).
add_filter(
	'query_vars',
	function ( $vars ) {
		return array_merge( $vars, array( 'sujet', 'en', 'fil' ) );
	}
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( $q->is_home() ) {
			$sujet = sanitize_title( (string) $q->get( 'sujet' ) );
			if ( $sujet ) {
				$q->set( 'tax_query', array( array( 'taxonomy' => 'er_sujet', 'field' => 'slug', 'terms' => $sujet ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			}
			if ( 'russe' === $q->get( 'en' ) ) {
				$q->set( 'meta_query', array( array( 'key' => 'er_langues', 'value' => 'ru', 'compare' => 'LIKE' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			}
			$q->set( 'posts_per_page', 20 );
			$q->set( 'ignore_sticky_posts', true );
		} elseif ( $q->is_tax( 'er_genre' ) ) {
			$q->set( 'posts_per_page', 20 );
		}
	}
);

/**
 * Où l'on est dans le journal : la une, ou une liste (un genre, un sujet, les textes en russe, tout le fil).
 */
function er_journal_vue() {
	if ( is_tax( 'er_genre' ) ) {
		return array( 'liste', 'genre', get_queried_object() );
	}
	$sujet = sanitize_title( (string) get_query_var( 'sujet' ) );
	if ( $sujet ) {
		return array( 'liste', 'sujet', get_term_by( 'slug', $sujet, 'er_sujet' ) );
	}
	if ( 'russe' === get_query_var( 'en' ) ) {
		return array( 'liste', 'russe', null );
	}
	if ( isset( $_GET['fil'] ) || is_paged() ) { // phpcs:ignore WordPress.Security.NonceVerification
		return array( 'liste', 'fil', null );
	}
	return array( 'une', '', null );
}

/**
 * L'adresse du journal, avec un filtre.
 *
 * @param array $args Les paramètres (sujet, en, fil).
 */
function er_journal_lien( $args = array() ) {
	$page = (int) get_option( 'page_for_posts' );
	$base = $page ? get_permalink( function_exists( 'er_traduction' ) ? er_traduction( $page ) : $page ) : home_url( '/' );
	return $args ? add_query_arg( $args, $base ) : $base;
}

/* Les petits morceaux d'un article ---------------------------------------------------------------------------------- */

/**
 * Le temps de lecture, en minutes (230 mots par minute), gardé dans er_lecture.
 *
 * @param int $id L'article.
 */
function er_journal_lecture( $id ) {
	$minutes = (int) get_post_meta( $id, 'er_lecture', true );
	if ( ! $minutes ) {
		$texte   = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $id ) ) );
		$mots    = preg_split( '/\s+/u', trim( $texte ), -1, PREG_SPLIT_NO_EMPTY );
		$minutes = max( 1, (int) round( count( $mots ) / 230 ) );
		update_post_meta( $id, 'er_lecture', (string) $minutes );
	}
	return $minutes;
}
add_action(
	'save_post_post',
	function ( $id ) {
		delete_post_meta( $id, 'er_lecture' );
		delete_transient( 'er_journal_sujets' );
		delete_transient( 'er_journal_chroniqueurs' );
	}
);
add_action(
	'set_object_terms',
	function ( $id, $termes, $tt_ids, $taxonomie ) {
		if ( in_array( $taxonomie, array( 'er_sujet', 'er_genre' ), true ) ) {
			delete_transient( 'er_journal_sujets' );
			delete_transient( 'er_journal_chroniqueurs' );
		}
	},
	10,
	4
);

/**
 * Le genre d'un article (un terme), ou null.
 *
 * @param int $id L'article.
 */
function er_journal_genre( $id ) {
	$genres = wp_get_post_terms( $id, 'er_genre' );
	return $genres && ! is_wp_error( $genres ) ? $genres[0] : null;
}

/**
 * Le pluriel d'un genre : « Critiques ».
 *
 * @param WP_Term $genre Le genre.
 */
function er_journal_genre_pluriel( $genre ) {
	$genres = er_genres();
	return isset( $genres[ $genre->slug ] ) ? $genres[ $genre->slug ][1] : $genre->name;
}

/**
 * Le sujet principal d'un article : le premier des sujets relus (er_sujet_principal), sinon le premier venu.
 *
 * @param int $id L'article.
 */
function er_journal_sujet( $id ) {
	$nom    = (string) get_post_meta( $id, 'er_sujet_principal', true );
	$sujets = wp_get_post_terms( $id, 'er_sujet' );
	if ( ! $sujets || is_wp_error( $sujets ) ) {
		return null;
	}
	foreach ( $sujets as $s ) {
		if ( $s->name === $nom ) {
			return $s;
		}
	}
	return $sujets[0];
}

/**
 * Le surtitre d'un texte dans le journal : « Critique · Goulag et dissidence ».
 *
 * @param int  $id       L'article.
 * @param bool $sujet      Avec le sujet.
 * @param bool $avec_genre Avec le genre.
 */
function er_journal_kicker( $id, $sujet = true, $avec_genre = true ) {
	$genre  = $avec_genre ? er_journal_genre( $id ) : null;
	$parts  = $genre ? '<span class="er-j-genre">' . esc_html( $genre->name ) . '</span>' : '';
	$s      = $sujet ? er_journal_sujet( $id ) : null;
	$parts .= $s ? ( $parts ? '<span aria-hidden="true"> · </span>' : '' ) . esc_html( $s->name ) : '';
	if ( false !== strpos( (string) get_post_meta( $id, 'er_langues', true ), 'ru' ) && false === strpos( (string) get_post_meta( $id, 'er_langues', true ), 'fr' ) ) {
		$parts .= '<span aria-hidden="true"> · </span>' . esc_html__( 'En russe', 'er-librairie' );
	}
	return $parts ? '<p class="er-j-sur">' . $parts . '</p>' : '';
}

/**
 * La ligne d'auteur : « Par Yves Hamant · 27 janvier 2026 · 12 min de lecture ».
 *
 * @param int  $id    L'article.
 * @param bool $date  Avec la date.
 * @param bool $long  « 12 min de lecture » plutôt que « 12 min ».
 */
function er_journal_auteur( $id, $date = true, $long = false ) {
	$parts     = array();
	$signature = trim( (string) get_post_meta( $id, 'er_signature', true ) );
	if ( $signature ) {
		/* translators: %s: le nom de celui qui signe */
		$parts[] = sprintf( esc_html__( 'Par %s', 'er-librairie' ), '<span class="er-j-nom">' . esc_html( $signature ) . '</span>' );
	}
	if ( $date ) {
		$parts[] = esc_html( get_the_date( __( 'j F Y', 'er-librairie' ), $id ) );
	}
	$min = er_journal_lecture( $id );
	/* translators: %d: des minutes */
	$parts[] = esc_html( sprintf( $long ? _n( '%d min de lecture', '%d min de lecture', $min, 'er-librairie' ) : __( '%d min', 'er-librairie' ), $min ) );
	return '<p class="er-j-auteur">' . implode( ' · ', $parts ) . '</p>';
}

/**
 * L'image d'un article, ou l'aplat d'attente (pierre).
 *
 * @param int    $id     L'article.
 * @param string $taille La taille de WordPress.
 */
function er_journal_vignette( $id, $taille = 'large' ) {
	$image = get_the_post_thumbnail( $id, $taille, array( 'alt' => '', 'loading' => 'lazy' ) );
	return '<span class="er-j-image">' . ( $image ? $image : '<span class="er-j-attente"></span>' ) . '</span>';
}

/**
 * Le titre d'un article, avec la langue du texte s'il est en russe seulement.
 *
 * @param int    $id     L'article.
 * @param string $balise h2, h3, span…
 * @param string $classe La classe.
 */
function er_journal_titre( $id, $balise, $classe ) {
	$langues = (string) get_post_meta( $id, 'er_langues', true );
	$lang    = 'ru' === trim( $langues ) ? ' lang="ru"' : '';
	return '<' . $balise . ' class="' . esc_attr( $classe ) . '"' . $lang . '>' . esc_html( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) . '</' . $balise . '>';
}

/* L'en-tête du journal -------------------------------------------------------------------------------------------- */

/**
 * Les sujets du journal, du plus fourni au moins fourni : [ terme, nombre d'articles ].
 */
function er_journal_sujets() {
	$sujets = get_transient( 'er_journal_sujets' );
	if ( false !== $sujets ) {
		return $sujets;
	}
	$ids     = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'lang' => '' ) );
	$comptes = array();
	$termes  = array();
	if ( $ids ) {
		foreach ( wp_get_object_terms( $ids, 'er_sujet', array( 'fields' => 'all_with_object_id' ) ) as $t ) {
			$comptes[ $t->term_id ] = isset( $comptes[ $t->term_id ] ) ? $comptes[ $t->term_id ] + 1 : 1;
			$termes[ $t->term_id ]  = $t;
		}
	}
	arsort( $comptes );
	$sujets = array();
	foreach ( $comptes as $tid => $n ) {
		$sujets[] = array( $termes[ $tid ], $n );
	}
	set_transient( 'er_journal_sujets', $sujets, DAY_IN_SECONDS );
	return $sujets;
}

/**
 * Le titre du journal, ses genres en pilules, ses sujets en mots.
 */
function er_journal_tete() {
	list( $vue, $quoi, $objet ) = er_journal_vue();
	$page   = (int) get_option( 'page_for_posts' );
	$titre  = $page ? get_the_title( $page ) : __( 'Le journal du centre', 'er-librairie' );
	$titre  = '<a href="' . esc_url( er_journal_lien() ) . '">' . esc_html( html_entity_decode( $titre, ENT_QUOTES, 'UTF-8' ) ) . '</a>';
	$pilule = function ( $lien, $texte, $actif ) {
		return '<a class="er-j-pilule' . ( $actif ? ' est-actif' : '' ) . '" href="' . esc_url( $lien ) . '"' . ( $actif ? ' aria-current="page"' : '' ) . '>' . esc_html( $texte ) . '</a>';
	};

	$pilules = $pilule( er_journal_lien(), __( 'À la une', 'er-librairie' ), 'une' === $vue );
	$genres  = get_terms( array( 'taxonomy' => 'er_genre', 'hide_empty' => true ) );
	$ordre   = array_keys( er_genres() );
	usort(
		$genres,
		function ( $a, $b ) use ( $ordre ) {
			$ia = array_search( $a->slug, $ordre, true );
			$ib = array_search( $b->slug, $ordre, true );
			return ( false === $ia ? 99 : $ia ) <=> ( false === $ib ? 99 : $ib );
		}
	);
	foreach ( is_wp_error( $genres ) ? array() : $genres as $g ) {
		$lien = get_term_link( $g );
		if ( ! is_wp_error( $lien ) ) {
			$pilules .= $pilule( $lien, er_journal_genre_pluriel( $g ), 'genre' === $quoi && $objet && $objet->term_id === $g->term_id );
		}
	}
	$russes = get_posts( array( 'post_type' => 'post', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'er_langues', 'value' => 'ru', 'compare' => 'LIKE' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $russes ) {
		$pilules .= $pilule( er_journal_lien( array( 'en' => 'russe' ) ), __( 'En russe', 'er-librairie' ), 'russe' === $quoi );
	}

	$sujets = er_journal_sujets();
	$mots   = '';
	$autres = '';
	foreach ( $sujets as $i => list( $t, $n ) ) {
		$actif = 'sujet' === $quoi && $objet && $objet->term_id === $t->term_id;
		$lien  = '<a href="' . esc_url( er_journal_lien( array( 'sujet' => $t->slug ) ) ) . '"' . ( $actif ? ' aria-current="page" class="est-actif"' : '' ) . '>' . esc_html( $t->name ) . '</a>';
		if ( $i < 9 ) {
			$mots .= $lien;
		} else {
			$autres .= $lien;
		}
	}
	if ( $autres ) {
		$mots .= '<details class="er-j-autres"><summary>' . esc_html__( 'Tous les sujets', 'er-librairie' ) . '</summary><div>' . $autres . '</div></details>';
	}

	return '<section class="er-j-tete alignfull"><div class="er-j-g">'
		. ( 'une' === $vue ? '<h1 class="er-j-titre">' . $titre . '</h1>' : '<p class="er-j-titre">' . $titre . '</p>' )
		. '<p class="er-j-chapo">' . esc_html__( 'Chroniques, critiques, essais et archives : la littérature russe et son histoire, par ceux qui la lisent et la traduisent.', 'er-librairie' ) . '</p>'
		. '<nav class="er-j-genres" aria-label="' . esc_attr__( 'Les genres du journal', 'er-librairie' ) . '">' . $pilules . '</nav>'
		. ( $mots ? '<nav class="er-j-sujets" aria-label="' . esc_attr__( 'Les sujets du journal', 'er-librairie' ) . '"><span class="er-j-sujets-titre">' . esc_html__( 'Sujets', 'er-librairie' ) . '</span>' . $mots . '</nav>' : '' )
		. '</div></section>';
}

/* Le corps : la une, ou une liste ------------------------------------------------------------------------------- */

/**
 * Le corps du journal.
 */
function er_journal_corps() {
	list( $vue ) = er_journal_vue();
	return 'une' === $vue ? er_journal_une() : er_journal_liste();
}

/**
 * Les articles publiés, du plus récent au plus ancien, sauf ceux déjà montrés.
 *
 * @param array $sauf   Les identifiants déjà montrés.
 * @param int   $nombre Combien.
 * @param array $plus   D'autres critères.
 */
function er_journal_articles( $sauf, $nombre, $plus = array() ) {
	return get_posts(
		array_merge(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'numberposts'         => $nombre,
				'post__not_in'        => $sauf, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
				'ignore_sticky_posts' => true,
				'fields'              => 'ids',
			),
			$plus
		)
	);
}

/**
 * La première page : la une et le fil, les chroniques et le grand format, trois sujets.
 */
function er_journal_une() {
	$vus = array();

	// Le texte à la une : le dernier épinglé, sinon le plus récent qui a une image, sinon le plus récent.
	$epingles = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) );
	$une      = $epingles ? er_journal_articles( array(), 1, array( 'post__in' => $epingles ) ) : array();
	if ( ! $une ) {
		$une = er_journal_articles( array(), 1, array( 'meta_key' => '_thumbnail_id' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( ! $une ) {
		$une = er_journal_articles( array(), 1 );
	}
	if ( ! $une ) {
		return '<section class="er-j-module alignfull"><div class="er-j-g"><p>' . esc_html__( 'Rien pour l’instant.', 'er-librairie' ) . '</p></div></section>';
	}
	$une   = $une[0];
	$vus[] = $une;
	$deux  = er_journal_articles( $vus, 2 );
	$vus   = array_merge( $vus, $deux );

	$html  = '<section class="er-j-module er-j-une alignfull"><div class="er-j-g">';
	$html .= '<div class="er-j-une-gauche">';
	$html .= '<a class="er-j-histoire er-j-principale" href="' . esc_url( get_permalink( $une ) ) . '">' . er_journal_vignette( $une, 'large' ) . er_journal_kicker( $une ) . er_journal_titre( $une, 'h2', 'er-j-t-module' )
		. ( has_excerpt( $une ) ? '<p class="er-j-chapo-texte">' . esc_html( wp_trim_words( get_the_excerpt( $une ), 45, '…' ) ) . '</p>' : '' )
		. er_journal_auteur( $une, true, true ) . '</a>';
	foreach ( $deux as $id ) {
		$html .= '<a class="er-j-histoire er-j-seconde" href="' . esc_url( get_permalink( $id ) ) . '">' . er_journal_vignette( $id, 'medium_large' ) . er_journal_kicker( $id ) . er_journal_titre( $id, 'h3', 'er-j-t-bloc' ) . er_journal_auteur( $id ) . '</a>';
	}
	$html .= '</div>';

	// Le fil : les six derniers textes, du plus récent au plus ancien.
	$html .= '<aside class="er-j-fil"><div class="er-j-titre-sec"><h2>' . esc_html__( 'Le fil', 'er-librairie' ) . '</h2><span>' . esc_html__( 'Du plus récent au plus ancien', 'er-librairie' ) . '</span></div><ul>';
	foreach ( er_journal_articles( array(), 6 ) as $id ) {
		$genre = er_journal_genre( $id );
		$html .= '<li><a href="' . esc_url( get_permalink( $id ) ) . '"><span class="er-j-fil-date">' . esc_html( get_the_date( _x( 'j M Y', 'date courte du fil', 'er-librairie' ), $id ) ) . '</span><span class="er-j-fil-texte">'
			. ( $genre ? '<span class="er-j-sur"><span class="er-j-genre">' . esc_html( $genre->name ) . '</span></span>' : '' )
			. er_journal_titre( $id, 'span', 'er-j-t-fil' ) . er_journal_auteur( $id, false ) . '</span></a></li>';
	}
	$html .= '</ul><a class="er-j-sortie" href="' . esc_url( er_journal_lien( array( 'fil' => '' ) ) ) . '">' . esc_html__( 'Tout le fil', 'er-librairie' ) . '</a></aside>';
	$html .= '</div></section>';

	$html .= er_journal_chroniques( $vus );
	$html .= er_journal_rangees_sujets( $vus );
	return $html;
}

/**
 * Les chroniqueurs : ceux qui signent au moins deux chroniques, des plus fidèles aux autres, quatre au plus.
 */
function er_journal_chroniqueurs() {
	$noms = get_transient( 'er_journal_chroniqueurs' );
	if ( false !== $noms ) {
		return $noms;
	}
	$comptes = array();
	$ids     = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'er_genre', 'field' => 'slug', 'terms' => 'chronique' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $ids as $id ) {
		$nom = trim( (string) get_post_meta( $id, 'er_signature', true ) );
		if ( $nom ) {
			$comptes[ $nom ] = isset( $comptes[ $nom ] ) ? $comptes[ $nom ] + 1 : 1;
		}
	}
	arsort( $comptes );
	$noms = array_slice( array_keys( array_filter( $comptes, fn( $n ) => $n >= 2 ) ), 0, 4 );
	set_transient( 'er_journal_chroniqueurs', $noms, DAY_IN_SECONDS );
	return $noms;
}

/**
 * Les chroniques, puis le grand format, sur le fond doux.
 *
 * @param array $vus Les articles déjà montrés (le grand format n'en est pas).
 */
function er_journal_chroniques( &$vus ) {
	$cartes = '';
	foreach ( er_journal_chroniqueurs() as $nom ) {
		$siens = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'er_signature', 'meta_value' => $nom, 'tax_query' => array( array( 'taxonomy' => 'er_genre', 'field' => 'slug', 'terms' => 'chronique' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$dernier = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'er_signature', 'meta_value' => $nom ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( ! $dernier ) {
			continue;
		}
		$premier  = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'order' => 'ASC', 'post__in' => $siens ) );
		$auteur   = get_term_by( 'name', $nom, 'auteur' );
		$portrait = $auteur ? (int) get_term_meta( $auteur->term_id, 'er_portrait', true ) : 0;
		$portrait = $portrait ? wp_get_attachment_image( $portrait, 'thumbnail', false, array( 'alt' => '' ) ) : '';
		if ( ! $portrait ) {
			$initiales = implode( '', array_map( fn( $m ) => mb_substr( $m, 0, 1 ), array_slice( preg_split( '/[\s-]+/u', $nom ), 0, 2 ) ) );
			$portrait  = '<span class="er-j-initiales" aria-hidden="true">' . esc_html( $initiales ) . '</span>';
		}
		$lien = $auteur ? get_term_link( $auteur ) : '';
		$n    = count( $siens );
		$an   = $premier ? get_the_date( 'Y', $premier[0] ) : '';
		/* translators: 1: le nombre de chroniques, 2: l'année de la première */
		$compte  = sprintf( _n( '%1$d chronique depuis %2$s', '%1$d chroniques depuis %2$s', $n, 'er-librairie' ), $n, $an );
		$cartes .= '<li class="er-j-chroniqueur">'
			. '<a class="er-j-qui" href="' . esc_url( $lien && ! is_wp_error( $lien ) ? $lien : get_permalink( $dernier[0] ) ) . '"><span class="er-j-portrait">' . $portrait . '</span><span><span class="er-j-qui-nom">' . esc_html( $nom ) . '</span><span class="er-j-qui-compte">' . esc_html( $compte ) . '</span></span></a>'
			. '<a class="er-j-histoire" href="' . esc_url( get_permalink( $dernier[0] ) ) . '">' . er_journal_titre( $dernier[0], 'span', 'er-j-t-bloc er-j-trois' ) . '<span class="er-j-auteur">' . esc_html( get_the_date( __( 'j F Y', 'er-librairie' ), $dernier[0] ) ) . '</span></a>'
			. '</li>';
	}

	// Le grand format : le plus récent des textes longs (vingt minutes de lecture ou plus) pas encore montré.
	$grand = 0;
	foreach ( er_journal_articles( $vus, 40 ) as $id ) {
		if ( er_journal_lecture( $id ) >= 20 ) {
			$grand = $id;
			break;
		}
	}

	if ( ! $cartes && ! $grand ) {
		return '';
	}
	$html = '<section class="er-j-module er-j-doux alignfull"><div class="er-j-g">';
	if ( $cartes ) {
		$html .= '<div class="er-j-titre-sec er-j-plein"><h2>' . esc_html__( 'Les chroniques', 'er-librairie' ) . '</h2>';
		$chroniques = get_term_by( 'slug', 'chronique', 'er_genre' );
		$lien       = $chroniques ? get_term_link( $chroniques ) : '';
		$html      .= $lien && ! is_wp_error( $lien ) ? '<a class="er-j-sortie" href="' . esc_url( $lien ) . '">' . esc_html__( 'Toutes les chroniques', 'er-librairie' ) . '</a>' : '';
		$html      .= '</div><ul class="er-j-chroniques er-j-plein">' . $cartes . '</ul>';
	}
	if ( $grand ) {
		$vus[] = $grand;
		$genre = er_journal_genre( $grand );
		$html .= '<a class="er-j-histoire er-j-grand er-j-plein" href="' . esc_url( get_permalink( $grand ) ) . '"><span class="er-j-grand-texte">'
			. '<span class="er-j-sur"><span class="er-j-genre">' . esc_html__( 'Grand format', 'er-librairie' ) . '</span>' . ( $genre ? '<span aria-hidden="true"> · </span>' . esc_html( $genre->name ) : '' ) . '</span>'
			. er_journal_titre( $grand, 'span', 'er-j-t-module' )
			. ( has_excerpt( $grand ) ? '<span class="er-j-chapo-texte">' . esc_html( wp_trim_words( get_the_excerpt( $grand ), 45, '…' ) ) . '</span>' : '' )
			. er_journal_auteur( $grand, true, true ) . '</span>' . er_journal_vignette( $grand, 'large' ) . '</a>';
	}
	return $html . '</div></section>';
}

/**
 * Trois sujets, les plus fournis : un texte mis en avant, puis trois titres.
 *
 * @param array $vus Les articles déjà montrés.
 */
function er_journal_rangees_sujets( &$vus ) {
	$colonnes = '';
	foreach ( array_slice( er_journal_sujets(), 0, 3 ) as list( $t, $n ) ) {
		$ids = er_journal_articles( $vus, 4, array( 'tax_query' => array( array( 'taxonomy' => 'er_sujet', 'terms' => $t->term_id ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( ! $ids ) {
			continue;
		}
		$vus   = array_merge( $vus, $ids );
		$tete  = array_shift( $ids );
		/* translators: %d: un nombre de textes */
		$compte = sprintf( _n( '%d texte', '%d textes', $n, 'er-librairie' ), $n );
		$col    = '<div class="er-j-sujet"><div class="er-j-titre-sec"><h2><a href="' . esc_url( er_journal_lien( array( 'sujet' => $t->slug ) ) ) . '">' . esc_html( $t->name ) . '</a></h2><span>' . esc_html( $compte ) . '</span></div>';
		$col   .= '<a class="er-j-histoire" href="' . esc_url( get_permalink( $tete ) ) . '">' . er_journal_vignette( $tete, 'medium_large' ) . er_journal_kicker( $tete, false ) . er_journal_titre( $tete, 'h3', 'er-j-t-bloc' ) . er_journal_auteur( $tete ) . '</a><ul>';
		foreach ( $ids as $id ) {
			$col .= '<li><a href="' . esc_url( get_permalink( $id ) ) . '">' . er_journal_titre( $id, 'span', 'er-j-t-fil' ) . er_journal_auteur( $id ) . '</a></li>';
		}
		$colonnes .= $col . '</ul></div>';
	}
	return $colonnes ? '<section class="er-j-module er-j-par-sujet alignfull"><div class="er-j-g">' . $colonnes . '</div></section>' : '';
}

/**
 * Une liste de textes (un genre, un sujet, les textes en russe, tout le fil), page par page.
 */
function er_journal_liste() {
	global $wp_query;
	list( , $quoi, $objet ) = er_journal_vue();
	if ( 'genre' === $quoi && $objet ) {
		$titre = er_journal_genre_pluriel( $objet );
	} elseif ( 'sujet' === $quoi && $objet ) {
		$titre = $objet->name;
	} elseif ( 'russe' === $quoi ) {
		$titre = __( 'En russe', 'er-librairie' );
	} else {
		$titre = __( 'Tous les textes', 'er-librairie' );
	}
	$n = (int) $wp_query->found_posts;
	/* translators: %d: un nombre de textes */
	$html  = '<section class="er-j-module er-j-liste alignfull"><div class="er-j-g"><div class="er-j-titre-sec er-j-plein"><h1 class="er-j-liste-titre">' . esc_html( $titre ) . '</h1><span>' . esc_html( sprintf( _n( '%d texte', '%d textes', $n, 'er-librairie' ), $n ) ) . '</span></div>';
	$html .= '<ul class="er-j-plein">';
	foreach ( $wp_query->posts as $p ) {
		$html .= '<li><a class="er-j-ligne" href="' . esc_url( get_permalink( $p ) ) . '"><span class="er-j-ligne-date">' . esc_html( get_the_date( __( 'j F Y', 'er-librairie' ), $p ) ) . '</span><span class="er-j-ligne-texte">'
			. er_journal_kicker( $p->ID, 'sujet' !== $quoi, 'genre' !== $quoi ) . er_journal_titre( $p->ID, 'h2', 'er-j-t-bloc' )
			. ( has_excerpt( $p ) ? '<span class="er-j-ligne-chapo">' . esc_html( wp_trim_words( get_the_excerpt( $p ), 30, '…' ) ) . '</span>' : '' )
			. er_journal_auteur( $p->ID, false ) . '</span>' . er_journal_vignette( $p->ID, 'medium' ) . '</a></li>';
	}
	if ( ! $wp_query->posts ) {
		$html .= '<li><p>' . esc_html__( 'Rien pour l’instant.', 'er-librairie' ) . '</p></li>';
	}
	$html .= '</ul>';
	$pages = paginate_links(
		array(
			'total'     => (int) $wp_query->max_num_pages,
			'current'   => max( 1, (int) get_query_var( 'paged' ) ),
			'prev_text' => _x( 'Plus récents', 'pagination du journal', 'er-librairie' ),
			'next_text' => _x( 'Plus anciens', 'pagination du journal', 'er-librairie' ),
		)
	);
	$html .= $pages ? '<nav class="er-j-pages er-j-plein" aria-label="' . esc_attr__( 'Les pages du journal', 'er-librairie' ) . '">' . $pages . '</nav>' : '';
	return $html . '</div></section>';
}

/* Le surtitre d'un article ---------------------------------------------------------------------------------------- */

/**
 * « Le journal du centre · Critique », au-dessus du titre d'un article.
 *
 * @param int $id L'article.
 */
function er_journal_surtitre_bloc( $id ) {
	$page  = (int) get_option( 'page_for_posts' );
	$nom   = $page ? html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ) : __( 'Le journal du centre', 'er-librairie' );
	$html  = '<p class="er-surtitre er-j-surtitre"><a href="' . esc_url( er_journal_lien() ) . '">' . esc_html( $nom ) . '</a>';
	$genre = er_journal_genre( $id );
	$lien  = $genre ? get_term_link( $genre ) : '';
	if ( $genre && ! is_wp_error( $lien ) ) {
		$html .= ' · <a href="' . esc_url( $lien ) . '">' . esc_html( $genre->name ) . '</a>';
	}
	return $html . '</p>';
}
