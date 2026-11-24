<?php
/**
 * La tête de page : entre la ligne de la maison et la bande de page, la même sur tout le site (retenue le 24 nov. 2026,
 * le canevas « plein écran », page « La tête de page »).
 *
 * Sur la teinte pâle de la maison, comme la bande qui la ferme (la tête et la bande ne font qu'un bloc ; en défilant,
 * la tête s'en va et la bande s'accroche) :
 * - le fil, en haut à gauche : le point de la maison, puis le chemin, en petites capitales ; les faits en face, à
 *   droite (« 8 expositions depuis 2019 », « 312 livres ») ;
 * - le titre (titre-page) sur 7 colonnes, le chapô sur 5, alignés par le bas ; sans chapô, le titre prend la largeur ;
 * - une image, s'il y en a une (le portrait d'une personne), sur 2 colonnes à gauche du titre ;
 * - la page dit son nom en russe, en très grand, à 7 % de la couleur de la maison, coupé par le bas (la grande lettre
 *   du système) ; une page sans nom russe n'en a pas.
 * Une page sans maison (un sujet, les pages communes) : le fond doux, les trois points. Pas sur l'accueil, la fiche
 * d'un livre, un article, une rencontre, une exposition ni une vidéo, qui ont leur ouverture à eux.
 *
 * @package editeurs-reunis
 */

/**
 * Le nom de la maison et son adresse, pour le fil.
 *
 * @param string $maison librairie, ymca, centre.
 * @return array{texte:string,lien:string}|null
 */
function er_tete_maison( $maison ) {
	$maisons = array(
		'librairie' => array( _x( 'Librairie', 'menu du haut', 'editeurs-reunis' ), 'librairie/' ),
		'ymca'      => array( _x( 'Maison d’édition YMCA-Press', 'menu du haut', 'editeurs-reunis' ), 'ymca-press/' ),
		'centre'    => array( _x( 'Le centre culturel', 'menu du haut', 'editeurs-reunis' ), 'centre-culturel/' ),
	);
	if ( ! isset( $maisons[ $maison ] ) ) {
		return null;
	}
	return array(
		'texte' => $maisons[ $maison ][0],
		'lien'  => html_entity_decode( er_lien( $maisons[ $maison ][1] ), ENT_QUOTES, 'UTF-8' ),
	);
}

/**
 * La tête d'une page.
 *
 * @param array $a {
 *     @type string $maison     librairie, ymca, centre ; par défaut la maison de la page (aucune : le fond doux).
 *     @type array  $fil        Le chemin après la maison : array( array( 'texte', 'lien' ) ).
 *     @type string $faits      Ce qui se compte, en face du fil.
 *     @type string $titre      Le titre.
 *     @type bool   $h1         Le titre est le h1 de la page (sinon un paragraphe : le journal, hors de sa une).
 *     @type string $chapo      Le chapô, en texte.
 *     @type string $mot        Le nom russe de la page ; null pour le trouver (er_tete_mot_russe()), '' pour aucun.
 *     @type string $image      Une image (HTML), à gauche du titre.
 *     @type string $sous_titre Sous le titre : les graphies et les dates d'une personne.
 * }
 */
function er_tete_page( $a ) {
	$a      = wp_parse_args(
		$a,
		array(
			'maison'     => function_exists( 'er_maison' ) ? er_maison() : '',
			'fil'        => array(),
			'faits'      => '',
			'titre'      => '',
			'h1'         => true,
			'chapo'      => '',
			'mot'        => null,
			'image'      => '',
			'sous_titre' => '',
		)
	);
	$maison = in_array( $a['maison'], array( 'librairie', 'ymca', 'centre' ), true ) ? $a['maison'] : 'aucune';
	$mot    = null === $a['mot'] ? er_tete_mot_russe() : (string) $a['mot'];

	// Le fil : la maison (ou le nom du site), puis le chemin.
	$chemin = array();
	$racine = er_tete_maison( $maison );
	if ( $racine ) {
		$chemin[] = $racine;
	} elseif ( ! $a['fil'] ) {
		$chemin[] = array(
			'texte' => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
			'lien'  => html_entity_decode( er_lien(), ENT_QUOTES, 'UTF-8' ),
		);
	}
	$chemin = array_merge( $chemin, $a['fil'] );
	$morceaux = array();
	foreach ( $chemin as $c ) {
		$morceaux[] = empty( $c['lien'] ) ? '<span>' . esc_html( $c['texte'] ) . '</span>' : '<a href="' . esc_url( $c['lien'] ) . '">' . esc_html( $c['texte'] ) . '</a>';
	}
	$points = 'aucune' === $maison
		? '<span class="er-tete-point er-tete-point-librairie"></span><span class="er-tete-point er-tete-point-ymca"></span><span class="er-tete-point er-tete-point-centre"></span>'
		: '<span class="er-tete-point"></span>';
	$fil = '<div class="er-tete-haut"><nav class="er-tete-fil" aria-label="' . esc_attr__( 'Fil d’Ariane', 'editeurs-reunis' ) . '"><span class="er-tete-points" aria-hidden="true">' . $points . '</span>'
		. implode( '<span class="er-tete-sep" aria-hidden="true">/</span>', $morceaux ) . '</nav>'
		. ( '' !== $a['faits'] ? '<p class="er-tete-faits">' . esc_html( $a['faits'] ) . '</p>' : '' ) . '</div>';

	$balise = $a['h1'] ? 'h1' : 'p';
	$titre  = '<div class="er-tete-titres"><' . $balise . ' class="er-tete-titre">' . esc_html( $a['titre'] ) . '</' . $balise . '>'
		. ( '' !== $a['sous_titre'] ? '<p class="er-tete-sous-titre">' . esc_html( $a['sous_titre'] ) . '</p>' : '' ) . '</div>';
	$chapo  = '' !== trim( $a['chapo'] ) ? '<p class="er-tete-chapo">' . esc_html( $a['chapo'] ) . '</p>' : '';
	$image  = '' !== $a['image'] ? '<figure class="er-tete-image">' . $a['image'] . '</figure>' : '';
	$classes = 'er-tete er-tete-' . $maison . ( $image ? ' er-tete-avec-image' : '' ) . ( $chapo ? '' : ' er-tete-sans-chapo' );
	return '<section class="' . esc_attr( $classes ) . '">'
		. ( '' !== $mot ? '<span class="er-tete-lettre" lang="ru" aria-hidden="true">' . esc_html( $mot ) . '</span>' : '' )
		. $fil . '<div class="er-tete-corps">' . $image . $titre . $chapo . '</div></section>'
		. er_tete_fil_ld( $chemin, $a['titre'] );
}

/**
 * Le fil d'Ariane de la tête, pour les moteurs de recherche (BreadcrumbList, en JSON-LD) : le chemin, puis la page.
 *
 * @param array  $chemin Les étapes : array( 'texte', 'lien' ).
 * @param string $titre  Le titre de la page.
 */
function er_tete_fil_ld( $chemin, $titre ) {
	$elements = array();
	foreach ( array_merge( $chemin, array( array( 'texte' => $titre ) ) ) as $i => $c ) {
		$element = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => wp_strip_all_tags( (string) $c['texte'] ),
		);
		if ( ! empty( $c['lien'] ) ) {
			$element['item'] = html_entity_decode( (string) $c['lien'], ENT_QUOTES, 'UTF-8' );
		}
		$elements[] = $element;
	}
	if ( count( $elements ) < 2 ) {
		return '';
	}
	$ld = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $elements,
	);
	return '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>';
}

/**
 * Le nom russe de la page, pour le décor de sa tête : celui qu'on a saisi (la méta « er_nom_russe » d'une page ou
 * d'un terme), sinon la traduction russe de la page ou du terme (Polylang), sinon la graphie cyrillique d'une
 * personne, sinon celui des pages et des rayons de la maquette ; rien s'il n'y en a pas.
 */
function er_tete_mot_russe() {
	$objet = get_queried_object();
	$cyr   = fn( $s ) => (bool) preg_match( '/\p{Cyrillic}/u', (string) $s );
	if ( $objet instanceof WP_Post ) {
		$saisi = (string) get_post_meta( $objet->ID, 'er_nom_russe', true );
		if ( '' !== $saisi ) {
			return $saisi;
		}
		if ( function_exists( 'pll_get_post' ) ) {
			$ru = pll_get_post( $objet->ID, 'ru' );
			if ( $ru && $cyr( get_the_title( $ru ) ) ) {
				return html_entity_decode( get_the_title( $ru ), ENT_QUOTES, 'UTF-8' );
			}
		}
	} elseif ( $objet instanceof WP_Term ) {
		$saisi = (string) get_term_meta( $objet->term_id, 'er_nom_russe', true );
		if ( '' !== $saisi ) {
			return $saisi;
		}
		if ( 'auteur' === $objet->taxonomy ) {
			// Une personne : son nom de famille en cyrillique (« Солженицын »), d'après son nom ou ses autres graphies.
			$nom = fn( $g ) => preg_replace( '/^.*\s/u', '', trim( $g ) );
			if ( $cyr( $objet->name ) ) {
				return $nom( $objet->name );
			}
			foreach ( function_exists( 'er_autres_graphies' ) ? er_autres_graphies( $objet->term_id ) : array() as $g ) {
				if ( $cyr( $g ) ) {
					return $nom( $g );
				}
			}
			return '';
		}
		if ( function_exists( 'pll_get_term' ) ) {
			$ru = pll_get_term( $objet->term_id, 'ru' );
			$t  = $ru ? get_term( $ru ) : null;
			if ( $t instanceof WP_Term && $cyr( $t->name ) ) {
				return $t->name;
			}
		}
		$rayons = array(
			'litterature'        => 'Литература',
			'histoire'           => 'История',
			'pensee-et-religion' => 'Мысль и вера',
			'apprendre-le-russe' => 'Русский язык',
			'arts'               => 'Искусство',
			'jeunesse'           => 'Детям',
		);
		return 'product_cat' === $objet->taxonomy && isset( $rayons[ $objet->slug ] ) ? $rayons[ $objet->slug ] : '';
	}
	$pages = array(
		'librairie'                   => 'Книги',
		'ymca-press'                  => 'ИМКА-Пресс',
		'ymca-press/histoire'         => 'История',
		'ymca-press/catalogue'        => 'Каталог',
		'ymca-press/amis-ymca-press'  => 'Друзья',
		'centre-culturel'             => 'Центр',
		'centre-culturel/evenements'  => 'Встречи',
		'centre-culturel/expositions' => 'Выставки',
		'centre-culturel/podcasts'    => 'Подкасты',
		'centre-culturel/videos'      => 'Видео',
		'nous-trouver'                => 'Адрес',
	);
	$chemin = function_exists( 'er_chemin_reference' ) ? er_chemin_reference() : '';
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$chemin = 'librairie';
	}
	return isset( $pages[ $chemin ] ) ? $pages[ $chemin ] : '';
}

/**
 * Le chapô d'un terme : le premier paragraphe de sa description, en texte.
 *
 * @param WP_Term $terme Le terme.
 */
function er_tete_chapo_terme( $terme ) {
	$texte = trim( (string) $terme->description );
	if ( '' === $texte ) {
		return '';
	}
	$premier = preg_split( '/\R\s*\R|<\/p>/u', $texte )[0];
	return trim( wp_strip_all_tags( html_entity_decode( $premier, ENT_QUOTES, 'UTF-8' ) ) );
}

/**
 * Un nombre de livres, en texte : « 312 livres », « un livre ».
 *
 * @param int $n Le nombre.
 */
function er_tete_livres( $n ) {
	/* translators: %d: un nombre de livres */
	return $n ? sprintf( _n( '%d livre', '%d livres', $n, 'editeurs-reunis' ), $n ) : '';
}

/**
 * La tête de la page qu'on regarde, d'après ce qu'elle est : une page, la librairie (la boutique, un rayon, une
 * langue, une mention, un éditeur, une collection, une recherche), une personne. Le bloc editeurs-reunis/tete.
 */
function er_tete_de_la_page() {
	$objet = get_queried_object();
	if ( is_page() && $objet instanceof WP_Post ) {
		$fil    = array();
		$maison = function_exists( 'er_maison' ) ? er_maison() : '';
		// Les pages au-dessus, sous la page d'accueil de la maison (que le nom de la maison représente).
		$racines = array( 'centre-culturel', 'ymca-press' );
		foreach ( array_reverse( get_post_ancestors( $objet ) ) as $parent ) {
			$chemin = trim( (string) get_page_uri( $parent ), '/' );
			if ( in_array( $chemin, $racines, true ) || (int) $parent === (int) get_option( 'woocommerce_shop_page_id' ) ) {
				continue;
			}
			$fil[] = array(
				'texte' => html_entity_decode( get_the_title( $parent ), ENT_QUOTES, 'UTF-8' ),
				'lien'  => get_permalink( $parent ),
			);
		}
		return er_tete_page(
			array(
				'maison' => $maison,
				'fil'    => $fil,
				'faits'  => (string) apply_filters( 'er_tete_faits', '', $objet ),
				'titre'  => html_entity_decode( get_the_title( $objet ), ENT_QUOTES, 'UTF-8' ),
				'chapo'  => trim( wp_strip_all_tags( html_entity_decode( (string) $objet->post_excerpt, ENT_QUOTES, 'UTF-8' ) ) ),
			)
		);
	}
	if ( is_search() ) {
		global $wp_query;
		$n = (int) $wp_query->found_posts;
		return er_tete_page(
			array(
				'maison' => function_exists( 'is_woocommerce' ) && is_woocommerce() ? 'librairie' : '',
				/* translators: %s: ce qu'on a cherché */
				'titre'  => sprintf( __( 'Recherche : « %s »', 'editeurs-reunis' ), get_search_query( false ) ),
				/* translators: %d: un nombre de résultats */
				'faits'  => sprintf( _n( '%d résultat', '%d résultats', $n, 'editeurs-reunis' ), $n ),
				'mot'    => '',
			)
		);
	}
	if ( $objet instanceof WP_Term ) {
		$fil = array();
		foreach ( array_reverse( get_ancestors( $objet->term_id, $objet->taxonomy, 'taxonomy' ) ) as $parent ) {
			$p    = get_term( $parent, $objet->taxonomy );
			$lien = $p instanceof WP_Term ? get_term_link( $p ) : '';
			if ( $p instanceof WP_Term && ! is_wp_error( $lien ) ) {
				$fil[] = array(
					'texte' => $p->name,
					'lien'  => $lien,
				);
			}
		}
		if ( 'auteur' === $objet->taxonomy ) {
			return er_tete_personne( $objet );
		}
		if ( 'er_sujet' === $objet->taxonomy ) {
			return er_tete_sujet( $objet );
		}
		// Une catégorie, une étiquette du journal : sous le journal du centre.
		if ( ! in_array( $objet->taxonomy, get_object_taxonomies( 'product' ), true ) ) {
			$journal = (int) get_option( 'page_for_posts' );
			if ( $journal ) {
				array_unshift(
					$fil,
					array(
						'texte' => html_entity_decode( get_the_title( $journal ), ENT_QUOTES, 'UTF-8' ),
						'lien'  => get_permalink( $journal ),
					)
				);
			}
			return er_tete_page(
				array(
					'maison' => function_exists( 'er_maison' ) ? er_maison() : '',
					'fil'    => $fil,
					/* translators: %d: un nombre de textes */
					'faits'  => $objet->count ? sprintf( _n( '%d texte', '%d textes', (int) $objet->count, 'editeurs-reunis' ), (int) $objet->count ) : '',
					'titre'  => $objet->name,
					'chapo'  => er_tete_chapo_terme( $objet ),
				)
			);
		}
		$n = 'product_cat' === $objet->taxonomy ? (int) get_term_meta( $objet->term_id, 'product_count_product_cat', true ) : 0;
		return er_tete_page(
			array(
				'maison' => 'librairie',
				'fil'    => $fil,
				'faits'  => er_tete_livres( $n ? $n : (int) $objet->count ),
				'titre'  => $objet->name,
				'chapo'  => er_tete_chapo_terme( $objet ),
			)
		);
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$page = (int) get_option( 'woocommerce_shop_page_id' );
		$n    = (int) wp_count_posts( 'product' )->publish;
		return er_tete_page(
			array(
				'maison' => 'librairie',
				'faits'  => er_tete_livres( $n ),
				'titre'  => $page ? html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ) : __( 'Librairie', 'editeurs-reunis' ),
				'chapo'  => __( 'Les Éditeurs réunis, librairie de référence depuis 1925', 'editeurs-reunis' ),
			)
		);
	}
	return '';
}

/**
 * La tête d'une personne : son portrait, son nom, ses graphies et ses dates, sa présentation ; ses livres en face du
 * fil.
 *
 * @param WP_Term $t La personne.
 */
function er_tete_personne( $t ) {
	$portrait = (int) get_term_meta( $t->term_id, 'er_portrait', true );
	$graphies = function_exists( 'er_autres_graphies' ) ? er_autres_graphies( $t->term_id ) : array();
	$dates    = array_filter( array( (string) get_term_meta( $t->term_id, 'er_naissance', true ), (string) get_term_meta( $t->term_id, 'er_deces', true ) ) );
	$livres   = function_exists( 'er_personne_livres' ) ? er_personne_livres( $t ) : array();
	$n        = count( array_unique( array_merge( isset( $livres['auteur'] ) ? $livres['auteur'] : array(), isset( $livres['tous'] ) ? $livres['tous'] : array() ) ) );
	$sous     = array_filter( array( implode( ' · ', array_slice( $graphies, 0, 2 ) ), implode( ' – ', $dates ) ) );
	return er_tete_page(
		array(
			'maison'     => 'librairie',
			/* translators: %s: un nombre de livres (« 5 livres ») */
			'faits'      => $n ? sprintf( __( '%s en rayon', 'editeurs-reunis' ), er_tete_livres( $n ) ) : '',
			'titre'      => $t->name,
			'chapo'      => er_tete_chapo_terme( $t ),
			'image'      => $portrait ? wp_get_attachment_image( $portrait, 'medium_large', false, array( 'alt' => $t->name ) ) : '',
			'sous_titre' => implode( ' · ', $sous ),
		)
	);
}

/**
 * La tête d'un sujet : il traverse les trois maisons (le fond doux, les trois points) ; en face du fil, ce qui le
 * porte : « 12 livres · 8 rencontres · 5 textes ».
 *
 * @param WP_Term $t Le sujet.
 */
function er_tete_sujet( $t ) {
	$ids    = get_objects_in_term( $t->term_id, 'er_sujet' );
	$sortes = array();
	foreach ( is_wp_error( $ids ) ? array() : $ids as $id ) {
		if ( 'publish' === get_post_status( $id ) ) {
			$type            = get_post_type( $id );
			$sortes[ $type ] = ( isset( $sortes[ $type ] ) ? $sortes[ $type ] : 0 ) + 1;
		}
	}
	$faits = array();
	if ( ! empty( $sortes['product'] ) ) {
		$faits[] = er_tete_livres( $sortes['product'] );
	}
	if ( ! empty( $sortes['er_evenement'] ) ) {
		/* translators: %d: un nombre de rencontres */
		$faits[] = sprintf( _n( '%d rencontre', '%d rencontres', $sortes['er_evenement'], 'editeurs-reunis' ), $sortes['er_evenement'] );
	}
	if ( ! empty( $sortes['post'] ) ) {
		/* translators: %d: un nombre de textes */
		$faits[] = sprintf( _n( '%d texte', '%d textes', $sortes['post'], 'editeurs-reunis' ), $sortes['post'] );
	}
	return er_tete_page(
		array(
			'maison' => '',
			'fil'    => array( array( 'texte' => __( 'Sujet', 'editeurs-reunis' ) ) ),
			'faits'  => implode( ' · ', $faits ),
			'titre'  => $t->name,
			'chapo'  => er_tete_chapo_terme( $t ),
		)
	);
}

add_action(
	'init',
	function () {
		// Le chapô d'une page est son résumé.
		add_post_type_support( 'page', 'excerpt' );
		register_block_type(
			'editeurs-reunis/tete',
			array(
				'api_version'     => 3,
				'title'           => __( 'La tête de page', 'editeurs-reunis' ),
				'render_callback' => 'er_tete_de_la_page',
			)
		);
	}
);
