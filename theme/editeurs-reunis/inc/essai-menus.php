<?php
/**
 * Les menus au clic de l'en-tête épuré (26 novembre 2026), sur les pages d'essai (inc/essai.php) : Librairie, Maison
 * d'édition, Centre culturel et Journal, d'après les planches E, F et G du canevas « Les pages intérieures, dans la
 * direction », avec les retouches d'André (plus de phrase dans les panneaux).
 *
 * - Le panneau (E, /centre-culturel/essai/) : sous la barre du nom, un panneau blanc entre deux filets, la page
 *   pâlit derrière. Les pages de la maison à gauche, ce qui est à la une à droite (la nouveauté, l'Archipel, la
 *   prochaine rencontre, le dernier texte). La Librairie sur deux niveaux : les rayons, puis les sous-rayons du rayon
 *   survolé, par groupes.
 * - Les grands mots (F, /centre-culturel/essai-mots/) : les pages en très grand ; au survol, les autres pâlissent et
 *   la photo de droite change (la couverture du dernier livre d'un rayon, le dernier texte d'un genre…).
 * - Le plein écran (G, /centre-culturel/essai-plein/) : tout le plan du site sur le blanc, en quatre colonnes ; la
 *   maison choisie en noir, les autres en gris.
 *
 * Le bouton d'une maison ouvre et referme son menu, à la même place (l'interrupteur du 24 novembre) ; un clic ailleurs
 * et Échap le referment aussi : plus de croix dans les panneaux (André, 26 novembre). Le plein écran garde « Fermer ».
 * Sous 1 320 px, un seul bouton (≡) ouvre le plein écran, quelle que soit la page. Le script : assets/js/essai-menus.js ;
 * les styles : assets/css/28-essai-menus.css.
 *
 * @package editeurs-reunis
 */

/**
 * Une image de la médiathèque, pour un menu.
 *
 * @param int    $id     La pièce jointe.
 * @param string $taille La taille.
 */
function er_em_image( $id, $taille = 'large' ) {
	return $id ? wp_get_attachment_image(
		(int) $id,
		$taille,
		false,
		array(
			'class'    => 'er-em-photo',
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	) : '';
}

/**
 * Une photo du texte des trois maisons de l'accueil (er_trois_maisons_photo), pour un menu.
 *
 * @param string $cle centre, rencontres, expositions, librairie, ymca-press, archipel.
 */
function er_em_photo_accueil( $cle ) {
	$photo = function_exists( 'er_trois_maisons_photo' ) ? er_trois_maisons_photo( $cle ) : null;
	return $photo ? str_replace( array( 'er-tm-photo', ' data-cle="' . esc_attr( $cle ) . '"' ), array( 'er-em-photo', '' ), $photo[0] ) : '';
}

/**
 * Le dernier livre en stock, avec une couverture (pas le billet d'une rencontre) ; d'un rayon, si on le donne.
 *
 * @param string $rayon Le slug du rayon.
 */
function er_em_dernier_livre( $rayon = '' ) {
	$args = array(
		'post_type'     => 'product',
		'post_status'   => 'publish',
		'numberposts'   => 1,
		'fields'        => 'ids',
		'no_found_rows' => true,
		'meta_query'    => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'   => '_stock_status',
				'value' => 'instock',
			),
			array(
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => 'er_evenement',
				'compare' => 'NOT EXISTS',
			),
		),
	);
	if ( $rayon ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $rayon,
			),
		);
	}
	$ids = get_posts( $args );
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Le dernier article, avec une image ; d'un genre, ou en russe, si on le dit.
 *
 * @param string $genre Le slug du genre.
 * @param bool   $russe En russe seulement.
 */
function er_em_dernier_texte( $genre = '', $russe = false ) {
	$meta = array(
		array(
			'key'     => '_thumbnail_id',
			'compare' => 'EXISTS',
		),
	);
	if ( $russe ) {
		$meta[] = array(
			'key'     => 'er_langues',
			'value'   => 'ru',
			'compare' => 'LIKE',
		);
	}
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'numberposts'         => 1,
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'meta_query'          => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	if ( $genre ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'er_genre',
				'field'    => 'slug',
				'terms'    => $genre,
			),
		);
	}
	$ids = get_posts( $args );
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Un lien du site, d'après son chemin, prêt pour esc_url.
 *
 * @param string $chemin Le chemin français.
 */
function er_em_lien( $chemin ) {
	return html_entity_decode( er_lien( $chemin ), ENT_QUOTES, 'UTF-8' );
}

/**
 * Les noms des auteurs d'un livre.
 *
 * @param int $id Le livre.
 */
function er_em_auteurs( $id ) {
	$noms = taxonomy_exists( 'auteur' ) ? wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) ) : array();
	return is_wp_error( $noms ) ? '' : implode( ', ', $noms );
}

/**
 * Les quatre menus : pour chacun, son nom, son point, sa page, ses pages (chacune avec sa photo pour les grands mots),
 * ce qui est à la une ; pour la Librairie, ses rayons et leurs sous-rayons, par groupes.
 */
function er_em_donnees() {
	static $menus = null;
	if ( null !== $menus ) {
		return $menus;
	}
	$pages = er_pages_des_maisons();

	// La Librairie : les rayons, dans l'ordre de la librairie, et leurs sous-rayons par groupe (« Par genre »…).
	$ordre  = fn( $a, $b ) => (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true );
	$termes = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
		)
	);
	$termes = is_wp_error( $termes ) ? array() : array_values( array_filter( $termes, fn( $t ) => ! in_array( $t->slug, array( 'uncategorized', 'sans-rayon' ), true ) ) );
	usort( $termes, $ordre );
	$rayons = array();
	foreach ( $termes as $t ) {
		$enfants = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $t->term_id,
				'hide_empty' => false,
			)
		);
		$enfants = is_wp_error( $enfants ) ? array() : $enfants;
		usort( $enfants, $ordre );
		$groupes = array();
		foreach ( $enfants as $e ) {
			$lien = get_term_link( $e );
			if ( ! is_wp_error( $lien ) ) {
				$groupes[ 'g' . get_term_meta( $e->term_id, 'er_groupe', true ) ][] = array( $e->name, $lien );
			}
		}
		$lien     = get_term_link( $t );
		$livre    = er_em_dernier_livre( $t->slug );
		$rayons[] = array(
			'cle'     => 'rayon-' . $t->slug,
			'nom'     => $t->name,
			'lien'    => is_wp_error( $lien ) ? er_em_lien( 'librairie/' ) : $lien,
			'groupes' => $groupes,
			'photo'   => $livre ? er_em_image( get_post_thumbnail_id( $livre ), 'medium_large' ) : '',
		);
	}
	$livre = er_em_dernier_livre();
	$prix  = $livre && function_exists( 'wc_get_product' ) && wc_get_product( $livre ) ? trim( wp_strip_all_tags( html_entity_decode( wc_get_product( $livre )->get_price_html(), ENT_QUOTES, 'UTF-8' ) ) ) : '';
	$menus = array(
		'librairie' => array(
			'nom'    => _x( 'Librairie', 'menu du haut', 'editeurs-reunis' ),
			'point'  => 'librairie',
			'lien'   => er_em_lien( 'librairie/' ),
			'pages'  => $rayons,
			'rayons' => $rayons,
			'une'    => $livre ? array(
				'label' => __( 'Nouveauté', 'editeurs-reunis' ),
				'image' => er_em_image( get_post_thumbnail_id( $livre ), 'medium' ),
				'titre' => html_entity_decode( get_the_title( $livre ), ENT_QUOTES, 'UTF-8' ),
				'sous'  => er_em_auteurs( $livre ),
				'prix'  => $prix,
				'lien'  => get_permalink( $livre ),
			) : null,
		),
	);

	// YMCA-Press : ses pages ; à la une, l'Archipel paru chez elle.
	$photos   = array(
		'ymca-press/'                  => er_em_photo_accueil( 'ymca-press' ),
		'ymca-press/histoire/'         => er_em_photo_accueil( 'librairie' ),
		'ymca-press/catalogue/'        => er_em_photo_accueil( 'archipel' ),
		'centre-culturel/'             => er_em_photo_accueil( 'centre' ),
		'centre-culturel/expositions/' => er_em_photo_accueil( 'expositions' ),
	);
	$archipel = function_exists( 'er_trois_maisons_archipel' ) ? (int) er_trois_maisons_archipel() : 0;
	$liste    = array();
	foreach ( $pages['ymca'] as $chemin => $libelle ) {
		$liste[] = array(
			'cle'   => 'page-' . sanitize_title( $chemin ),
			'nom'   => $libelle,
			'lien'  => er_em_lien( $chemin ),
			'photo' => isset( $photos[ $chemin ] ) ? $photos[ $chemin ] : '',
		);
	}
	$menus['ymca'] = array(
		'nom'   => _x( 'Maison d’édition', 'menu du haut', 'editeurs-reunis' ),
		'point' => 'ymca',
		'lien'  => er_em_lien( 'ymca-press/' ),
		'pages' => $liste,
		'une'   => $archipel ? array(
			'label' => __( 'Paru chez YMCA-Press en 1973', 'editeurs-reunis' ),
			'image' => er_em_image( get_post_thumbnail_id( $archipel ), 'medium' ),
			'titre' => html_entity_decode( get_the_title( $archipel ), ENT_QUOTES, 'UTF-8' ),
			'sous'  => er_em_auteurs( $archipel ),
			'prix'  => '',
			'lien'  => get_permalink( $archipel ),
		) : null,
	);

	// Le centre culturel : ses pages, sans le journal (qui a son menu) ; à la une, la prochaine rencontre.
	$rencontre = function_exists( 'er_evt_requete' ) ? er_evt_requete( true ) : array();
	$rencontre = $rencontre ? $rencontre[0] : null;
	if ( $rencontre && has_post_thumbnail( $rencontre ) ) {
		$photos['centre-culturel/evenements/'] = er_em_image( get_post_thumbnail_id( $rencontre ), 'medium_large' );
	} else {
		$photos['centre-culturel/evenements/'] = er_em_photo_accueil( 'rencontres' );
	}
	$liste = array();
	foreach ( $pages['centre'] as $chemin => $libelle ) {
		if ( 'journal/' === $chemin ) {
			continue;
		}
		$liste[] = array(
			'cle'   => 'page-' . sanitize_title( $chemin ),
			'nom'   => $libelle,
			'lien'  => er_em_lien( $chemin ),
			'photo' => isset( $photos[ $chemin ] ) ? $photos[ $chemin ] : '',
		);
	}
	$quand = '';
	if ( $rencontre && function_exists( 'er_evt_date' ) && er_evt_date( $rencontre->ID ) ) {
		$debut = er_evt_date( $rencontre->ID );
		$quand = wp_date( 'l j F', $debut->getTimestamp(), new DateTimeZone( 'Europe/Paris' ) )
			. ( '00:00' === $debut->format( 'H:i' ) ? '' : ', ' . str_replace( ' ', "\u{00A0}", er_evt_heure( $debut ) ) );
	}
	$menus['centre'] = array(
		'nom'   => _x( 'Centre culturel', 'menu du haut', 'editeurs-reunis' ),
		'point' => 'centre',
		'lien'  => er_em_lien( 'centre-culturel/' ),
		'pages' => $liste,
		'une'   => $rencontre ? array(
			/* translators: %s: le jour et l'heure (« lundi 30 novembre, 19 h ») */
			'label' => $quand ? sprintf( __( 'Prochaine rencontre · %s', 'editeurs-reunis' ), $quand ) : __( 'Prochaine rencontre', 'editeurs-reunis' ),
			'image' => er_em_image( get_post_thumbnail_id( $rencontre ), 'medium' ),
			'titre' => html_entity_decode( get_the_title( $rencontre ), ENT_QUOTES, 'UTF-8' ),
			'sous'  => '',
			'prix'  => '',
			'lien'  => get_permalink( $rencontre ),
		) : null,
	);

	// Le journal : la une, ses genres, les textes en russe ; à la une, le dernier texte.
	$journal = function_exists( 'er_journal_lien' ) ? er_journal_lien() : er_em_lien( 'journal/' );
	$dernier = er_em_dernier_texte();
	$liste   = array(
		array(
			'cle'   => 'journal-une',
			'nom'   => __( 'À la une', 'editeurs-reunis' ),
			'lien'  => $journal,
			'photo' => $dernier ? er_em_image( get_post_thumbnail_id( $dernier ), 'medium_large' ) : '',
		),
	);
	$genres  = taxonomy_exists( 'er_genre' ) ? get_terms(
		array(
			'taxonomy'   => 'er_genre',
			'hide_empty' => true,
		)
	) : array();
	$genres  = is_wp_error( $genres ) ? array() : $genres;
	$suite   = function_exists( 'er_genres' ) ? array_keys( er_genres() ) : array();
	usort(
		$genres,
		function ( $a, $b ) use ( $suite ) {
			$ia = array_search( $a->slug, $suite, true );
			$ib = array_search( $b->slug, $suite, true );
			return ( false === $ia ? 99 : $ia ) <=> ( false === $ib ? 99 : $ib );
		}
	);
	foreach ( $genres as $g ) {
		$lien  = get_term_link( $g );
		$texte = er_em_dernier_texte( $g->slug );
		if ( ! is_wp_error( $lien ) ) {
			$liste[] = array(
				'cle'   => 'genre-' . $g->slug,
				'nom'   => function_exists( 'er_journal_genre_pluriel' ) ? er_journal_genre_pluriel( $g ) : $g->name,
				'lien'  => $lien,
				'photo' => $texte ? er_em_image( get_post_thumbnail_id( $texte ), 'medium_large' ) : '',
			);
		}
	}
	$russe = er_em_dernier_texte( '', true );
	if ( $russe && function_exists( 'er_journal_lien' ) ) {
		$liste[] = array(
			'cle'   => 'journal-russe',
			'nom'   => __( 'En russe', 'editeurs-reunis' ),
			'lien'  => er_journal_lien( array( 'en' => 'russe' ) ),
			'photo' => er_em_image( get_post_thumbnail_id( $russe ), 'medium_large' ),
		);
	}
	$genre = $dernier ? wp_get_post_terms( $dernier, 'er_genre', array( 'fields' => 'names' ) ) : array();
	$genre = ! is_wp_error( $genre ) && $genre ? mb_strtolower( $genre[0] ) . ', ' : '';
	$menus['journal'] = array(
		'nom'   => _x( 'Journal', 'menu du haut', 'editeurs-reunis' ),
		'point' => '',
		'lien'  => $journal,
		'pages' => $liste,
		'une'   => $dernier ? array(
			/* translators: %s: le genre et la date du texte (« critique, 21 octobre 2026 ») */
			'label' => sprintf( __( 'Dernier texte · %s', 'editeurs-reunis' ), $genre . get_the_date( 'j F Y', $dernier ) ),
			'image' => er_em_image( get_post_thumbnail_id( $dernier ), 'medium' ),
			'titre' => html_entity_decode( get_the_title( $dernier ), ENT_QUOTES, 'UTF-8' ),
			'sous'  => '',
			'prix'  => '',
			'lien'  => get_permalink( $dernier ),
		) : null,
	);
	return $menus;
}

/**
 * Le point de couleur d'une maison.
 *
 * @param string $maison librairie, ymca, centre, ou rien.
 */
function er_em_point( $maison ) {
	return $maison ? '<span class="er-em-point er-em-point-' . esc_attr( $maison ) . '" aria-hidden="true"></span>' : '';
}

/**
 * Le chevron d'un menu, qui se retourne quand il est ouvert.
 */
function er_em_chevron() {
	return '<svg class="er-em-chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6"/></svg>';
}

/**
 * Une croix.
 */
function er_em_croix() {
	return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>';
}

/**
 * Un titre en russe ? L'attribut de langue.
 *
 * @param string $texte Le texte.
 */
function er_em_lang( $texte ) {
	return preg_match( '/\p{Cyrillic}/u', (string) $texte ) ? ' lang="ru"' : '';
}

/**
 * Les quatre boutons de la barre du nom : chacun ouvre son menu (le plein écran, sur la page qui l'essaie).
 */
function er_essai_menu() {
	$variante = er_essai_variante();
	$html     = '';
	foreach ( er_em_donnees() as $cle => $m ) {
		$cible = 'plein' === $variante ? 'er-em-plan' : 'er-em-' . $cle;
		$html .= '<button type="button" class="er-em-bouton" data-er-em="' . esc_attr( $cle ) . '" aria-expanded="false" aria-controls="' . esc_attr( $cible ) . '">'
			. er_em_point( $m['point'] ) . '<span>' . esc_html( $m['nom'] ) . '</span>' . er_em_chevron() . '</button>';
	}
	return '<nav class="er-em" aria-label="' . esc_attr__( 'Le site', 'editeurs-reunis' ) . '">' . $html . '</nav>';
}
add_shortcode( 'er_essai_menu', 'er_essai_menu' );

/**
 * Le bouton du menu, sous 1 320 px : il ouvre le plein écran.
 */
function er_essai_bouton_menu() {
	return '<button type="button" class="er-em-menu er-picto" data-er-em="plan" aria-expanded="false" aria-controls="er-em-plan" aria-label="' . esc_attr__( 'Menu', 'editeurs-reunis' ) . '">'
		. '<svg class="er-em-menu-trois" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg>'
		. '<svg class="er-em-menu-croix" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg></button>';
}
add_shortcode( 'er_essai_bouton_menu', 'er_essai_bouton_menu' );

/**
 * Ce qui est à la une dans un panneau : une image, un titre, une ligne dessous.
 *
 * @param array|null $u La une.
 * @param string     $classe Une classe en plus.
 */
function er_em_une( $u, $classe = '' ) {
	if ( ! $u ) {
		return '';
	}
	$sous = array_filter( array( $u['sous'], $u['prix'] ) );
	return '<div class="er-em-une' . ( $classe ? ' ' . esc_attr( $classe ) : '' ) . '"><p class="er-em-une-label">' . esc_html( $u['label'] ) . '</p>'
		. '<a class="er-em-une-lien" href="' . esc_url( $u['lien'] ) . '">' . $u['image']
		. '<span class="er-em-une-texte"><span class="er-em-une-titre"' . er_em_lang( $u['titre'] ) . '>' . esc_html( $u['titre'] ) . '</span>'
		. ( $sous ? '<span class="er-em-une-sous"' . er_em_lang( $u['sous'] ) . '>' . esc_html( implode( ' · ', $sous ) ) . '</span>' : '' )
		. '</span></a></div>';
}

/**
 * E · Le panneau d'une maison : ses pages à gauche, ce qui est à la une à droite.
 *
 * @param string $cle La maison.
 * @param array  $m   Son menu.
 */
function er_em_panneau( $cle, $m ) {
	if ( 'librairie' === $cle ) {
		return er_em_panneau_librairie( $m );
	}
	$pages = '';
	foreach ( $m['pages'] as $p ) {
		$pages .= '<li><a href="' . esc_url( $p['lien'] ) . '">' . esc_html( $p['nom'] ) . '</a></li>';
	}
	return '<div class="er-em-panneau" id="er-em-' . esc_attr( $cle ) . '" hidden><div class="er-em-dedans">'
		. '<ul class="er-em-pages' . ( count( $m['pages'] ) > 6 ? ' est-en-deux' : '' ) . '">' . $pages . '</ul>'
		. er_em_une( $m['une'] ) . '</div></div>';
}

/**
 * E · Le panneau de la Librairie : les rayons ; les sous-rayons du rayon survolé, par groupes ; la nouveauté.
 *
 * @param array $m Le menu de la Librairie.
 */
function er_em_panneau_librairie( $m ) {
	$rayons = '';
	$sous   = '';
	foreach ( $m['rayons'] as $i => $r ) {
		$rayons .= '<li><a href="' . esc_url( $r['lien'] ) . '" data-rayon="' . esc_attr( $r['cle'] ) . '"' . ( 0 === $i ? ' class="est-actif"' : '' ) . '>' . esc_html( $r['nom'] ) . '</a></li>';
		$groupes = '';
		foreach ( $r['groupes'] as $titre => $liens ) {
			$titre  = substr( (string) $titre, 1 );
			$items  = '';
			foreach ( $liens as $l ) {
				$items .= '<li><a href="' . esc_url( $l[1] ) . '">' . esc_html( $l[0] ) . '</a></li>';
			}
			$groupes .= '<div class="er-em-groupe">' . ( '' !== $titre ? '<p class="er-em-groupe-titre">' . esc_html( $titre ) . '</p>' : '' ) . '<ul>' . $items . '</ul></div>';
		}
		/* translators: %s: le nom d'un rayon */
		$tout  = sprintf( __( 'Tout le rayon %s →', 'editeurs-reunis' ), $r['nom'] );
		$sous .= '<div class="er-em-sous-rayon" data-rayon="' . esc_attr( $r['cle'] ) . '"' . ( 0 === $i ? '' : ' hidden' ) . '>'
			. '<a class="er-em-tout-rayon" href="' . esc_url( $r['lien'] ) . '">' . esc_html( $tout ) . '</a>'
			. ( $groupes ? '<div class="er-em-groupes">' . $groupes . '</div>' : '' ) . '</div>';
	}
	return '<div class="er-em-panneau er-em-panneau-librairie" id="er-em-librairie" hidden><div class="er-em-dedans">'
		. '<div class="er-em-rayons-col"><ul class="er-em-rayons">' . $rayons . '</ul>'
		. '<a class="er-em-toute" href="' . esc_url( $m['lien'] ) . '">' . esc_html__( 'Toute la librairie →', 'editeurs-reunis' ) . '</a></div>'
		. '<div class="er-em-sous">' . $sous . '</div>'
		. er_em_une( $m['une'] ) . '</div></div>';
}

/**
 * F · Les grands mots d'une maison : ses pages en très grand, et la photo de celle qu'on survole.
 *
 * @param string $cle La maison.
 * @param array  $m   Son menu.
 */
function er_em_panneau_mots( $cle, $m ) {
	$mots   = '';
	$photos = '';
	$defaut = '';
	foreach ( $m['pages'] as $p ) {
		$avec  = '' !== $p['photo'];
		$mots .= '<li><a href="' . esc_url( $p['lien'] ) . '"' . ( $avec ? ' data-photo="' . esc_attr( $p['cle'] ) . '"' : '' ) . '>' . esc_html( $p['nom'] ) . '</a></li>';
		if ( $avec ) {
			$classe  = '' === $defaut ? 'er-em-photo est-vue' : 'er-em-photo';
			$defaut  = '' === $defaut ? $p['cle'] : $defaut;
			$photos .= str_replace( array( 'class="er-em-photo"', '<img ' ), array( 'class="' . $classe . '"', '<img data-photo="' . esc_attr( $p['cle'] ) . '" ' ), $p['photo'] );
		}
	}
	return '<div class="er-em-panneau er-em-panneau-mots" id="er-em-' . esc_attr( $cle ) . '" data-photo="' . esc_attr( $defaut ) . '" hidden><div class="er-em-dedans">'
		. '<div class="er-em-mots-col"><ul class="er-em-mots">' . $mots . '</ul></div>'
		. '<div class="er-em-photos" aria-hidden="true">' . $photos . '</div></div></div>';
}

/**
 * G · Le plein écran : tout le plan du site, en quatre colonnes. Il sert aussi de menu sous 1 320 px.
 */
function er_em_plan() {
	$colonnes = '';
	foreach ( er_em_donnees() as $cle => $m ) {
		$pages = '';
		foreach ( $m['pages'] as $p ) {
			$pages .= '<li><a href="' . esc_url( $p['lien'] ) . '">' . esc_html( $p['nom'] ) . '</a></li>';
		}
		$colonnes .= '<div class="er-em-colonne" data-colonne="' . esc_attr( $cle ) . '">'
			. '<a class="er-em-colonne-titre" href="' . esc_url( $m['lien'] ) . '">' . er_em_point( $m['point'] ) . '<span>' . esc_html( $m['nom'] ) . '</span></a>'
			. '<ul>' . $pages . '</ul></div>';
	}
	$langue = function_exists( 'er_choix_langue' ) ? er_choix_langue() : '';
	return '<div class="er-em-plan" id="er-em-plan" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Le plan du site', 'editeurs-reunis' ) . '" hidden>'
		. '<div class="er-em-plan-barre"><p class="er-logo"><a href="' . er_lien() . '" rel="home"><span>' . esc_html_x( 'Centre culturel', 'logo, première ligne', 'editeurs-reunis' ) . '</span> <span>' . esc_html_x( 'Alexandre Soljenitsyne', 'logo, seconde ligne', 'editeurs-reunis' ) . '</span></a></p>'
		. '<button type="button" class="er-em-fermer-plan"><span>' . esc_html__( 'Fermer', 'editeurs-reunis' ) . '</span>' . er_em_croix() . '</button></div>'
		. '<div class="er-em-colonnes">' . $colonnes . '</div>'
		. '<div class="er-em-plan-pied"><span>' . esc_html__( '11, rue de la Montagne-Sainte-Geneviève, 75005 Paris', 'editeurs-reunis' ) . '</span>'
		. '<span class="er-em-plan-outils"><a href="' . esc_url( function_exists( 'er_lien_recherche' ) ? er_lien_recherche() : home_url( '/?s=' ) ) . '">' . esc_html__( 'Rechercher', 'editeurs-reunis' ) . '</a>'
		. '<a href="' . esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' ) ) . '">' . esc_html__( 'Panier', 'editeurs-reunis' ) . '</a></span>'
		. ( $langue ? '<span class="er-em-plan-langue">' . $langue . '</span>' : '' )
		. '<a href="' . er_lien( 'nous-trouver/' ) . '">' . esc_html__( 'Nous trouver →', 'editeurs-reunis' ) . '</a></div></div>';
}

/**
 * Sous la barre du nom : le voile et les panneaux (le panneau ou les grands mots), puis le plein écran.
 */
function er_essai_panneaux() {
	$variante = er_essai_variante();
	$html     = '';
	if ( 'plein' !== $variante ) {
		$html .= '<div class="er-em-voile" aria-hidden="true"></div>';
		foreach ( er_em_donnees() as $cle => $m ) {
			$html .= 'mots' === $variante ? er_em_panneau_mots( $cle, $m ) : er_em_panneau( $cle, $m );
		}
	}
	return $html . er_em_plan();
}
add_shortcode( 'er_essai_panneaux', 'er_essai_panneaux' );
