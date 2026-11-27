<?php
/**
 * La page d'essai (26 novembre 2026) : « Nos événements » dans la direction retenue pour les pages intérieures, la
 * « tête blanche » (canevas « Les pages intérieures, dans la direction », planche C, avec les retouches d'André), pour
 * la regarder avec les vraies rencontres avant de l'étendre à tout le site, et pour travailler l'en-tête épuré.
 *
 * - Les adresses : trois pages créées une fois par ce fichier (une option garde le numéro de chacune ; supprimée, une
 *   page ne revient pas), hors des moteurs de recherche et de la recherche du site. Chacune essaie un des menus au clic
 *   de l'en-tête épuré (planches E, F et G du même canevas ; inc/essai-menus.php) :
 *   /centre-culturel/essai/ le panneau, /centre-culturel/essai-mots/ les grands mots, /centre-culturel/essai-plein/
 *   le plein écran.
 * - L'en-tête : la barre d'informations, puis la barre du nom avec ses quatre menus au clic (parts/header-essai.html) ;
 *   plus de ligne de la maison.
 * - La tête : le titre seul, en 500, sur le blanc ; ni fil, ni chapô, ni compteur.
 * - La bande : la même que sur « Nos événements », mais sur le blanc, entre deux filets ; « Passées » en simple texte
 *   gris ; ses liens restent sur la page d'essai.
 * - Les rencontres à venir sans intertitre ni regroupement par semaine, sans surtitre ; les rencontres passées avec
 *   leur titre entier, sans mot russe en filigrane.
 *
 * Le gabarit : templates/page-essai.html ; les styles : assets/css/27-essai.css. À retirer quand la passe commune aura
 * repris tout cela sur les vraies pages ; mais pas inc/essai-menus.php ni 28-essai-menus.css, qui portent l'en-tête de
 * tout le site (voir l'avertissement en tête de inc/essai-menus.php).
 *
 * @package editeurs-reunis
 */

/**
 * Les pages d'essai : leur slug, l'option qui garde leur numéro, le menu qu'elles essaient.
 */
function er_essai_pages() {
	return array(
		'essai'       => array( 'er_essai_page', 'panneau' ),
		'essai-mots'  => array( 'er_essai_page_mots', 'mots' ),
		'essai-plein' => array( 'er_essai_page_plein', 'plein' ),
	);
}

/**
 * Le menu qu'essaie la page qu'on regarde (panneau, mots, plein), ou rien hors des pages d'essai.
 */
function er_essai_variante() {
	if ( ! is_page() ) {
		return '';
	}
	$id = get_queried_object_id();
	foreach ( er_essai_pages() as list( $option, $variante ) ) {
		if ( $id && (int) get_option( $option ) === $id ) {
			return $variante;
		}
	}
	return '';
}

/**
 * Est-on sur une page d'essai ?
 */
function er_essai() {
	return '' !== er_essai_variante();
}

/**
 * Les pages, créées une fois, sous « Le centre culturel ». L'option de chacune sert de verrou : deux visites en même
 * temps n'en créent pas deux.
 */
add_action(
	'init',
	function () {
		foreach ( er_essai_pages() as $slug => list( $option ) ) {
			if ( false !== get_option( $option ) || ! add_option( $option, 'en-cours' ) ) {
				continue;
			}
			$parent    = get_page_by_path( 'centre-culturel' );
			$existante = get_page_by_path( 'centre-culturel/' . $slug );
			$id        = $existante ? $existante->ID : wp_insert_post(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_title'     => 'Nos événements',
					'post_name'      => $slug,
					'post_parent'    => $parent ? $parent->ID : 0,
					'post_content'   => '',
					'comment_status' => 'closed',
					'meta_input'     => array(
						'er_hors_recherche' => '1',
						'_wp_page_template' => 'page-essai',
					),
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				delete_option( $option );
				continue;
			}
			if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_default_language' ) ) {
				pll_set_post_language( $id, pll_default_language() );
			}
			update_option( $option, (int) $id );
		}
	},
	30
);

// Ni moteurs de recherche, ni ligne de la maison ; la classe du corps pour les styles.
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( er_essai() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}
);
add_filter( 'er_sans_lignes', fn( $sans ) => $sans || er_essai() );
// Hors du plan du site aussi : WordPress y met toutes les pages publiées, noindex ou non (audit du 27 nov. 2026).
add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args, $type ) {
		if ( 'page' === $type ) {
			$essais                = array_filter( array_map( fn( $p ) => (int) get_option( $p[0] ), er_essai_pages() ) );
			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $essais );
		}
		return $args;
	},
	10,
	2
);
add_filter(
	'body_class',
	function ( $classes ) {
		if ( er_essai() ) {
			$classes[] = 'er-essai';
			$classes[] = 'er-essai-' . er_essai_variante();
		}
		return $classes;
	}
);

// Les liens de la bande (les années, les vidéos, les cycles) restent sur la page d'essai.
add_filter( 'er_evt_page_liste', fn( $url ) => er_essai() ? get_permalink( get_queried_object_id() ) : $url );

// Les menus : leur script est celui de tout le site (inc/menus.php, assets/js/menus.js).

/**
 * La tête blanche : le titre de la page, seul.
 */
function er_essai_tete() {
	$titre = html_entity_decode( get_the_title( get_queried_object_id() ), ENT_QUOTES, 'UTF-8' );
	return '<section class="er-tb"><h1 class="er-tb-titre">' . esc_html( $titre ) . '</h1></section>';
}
add_shortcode( 'er_essai_tete', 'er_essai_tete' );

/**
 * Un titre en russe ? L'attribut de langue, pour la césure et la lecture à voix haute.
 *
 * @param string $texte Le titre.
 */
function er_essai_lang( $texte ) {
	return preg_match( '/\p{Cyrillic}/u', (string) $texte ) ? ' lang="ru"' : '';
}

/**
 * Une abréviation à la française : en minuscules, avec son point (« nov. », « lun. ») ; « mai » reste « mai ».
 *
 * @param string $court La forme courte de WordPress (« Nov », « lun »).
 * @param string $long  La forme longue (« novembre », « lundi »).
 */
function er_essai_abrege( $court, $long ) {
	$court = mb_strtolower( $court );
	return mb_strtolower( $long ) === $court || '.' === mb_substr( $court, -1 ) ? $court : $court . '.';
}

/**
 * Une rencontre à venir, en ligne : la date (le jour en grand, le mois, le jour de la semaine et l'heure), l'affiche,
 * le titre, puis « Réserver » ou ce qu'il faut savoir de l'entrée.
 *
 * @param WP_Post $p La rencontre.
 */
function er_essai_ligne( $p ) {
	$debut = er_evt_date( $p->ID );
	$etat  = (string) get_post_meta( $p->ID, 'er_etat', true );
	$lien  = esc_url( get_permalink( $p ) );
	$titre = get_the_title( $p );

	if ( $debut ) {
		$ts    = $debut->getTimestamp();
		$tz    = er_evt_fuseau();
		$heure = '00:00' === $debut->format( 'H:i' ) ? '' : str_replace( ' ', "\u{00A0}", er_evt_heure( $debut ) );
		$long  = wp_date( 'l', $ts, $tz ) . ( $heure ? ', ' . $heure : '' );
		$court = er_essai_abrege( wp_date( 'D', $ts, $tz ), wp_date( 'l', $ts, $tz ) ) . ( $heure ? ' ' . $heure : '' );
		$date  = '<p class="er-es-date"><span class="er-es-jour">' . esc_html( $debut->format( 'j' ) ) . '</span>'
			. '<span class="er-es-mois"><span class="er-es-long">' . esc_html( wp_date( 'F', $ts, $tz ) ) . '</span><span class="er-es-court">' . esc_html( er_essai_abrege( wp_date( 'M', $ts, $tz ), wp_date( 'F', $ts, $tz ) ) ) . '</span></span>'
			. '<span class="er-es-heure"><span class="er-es-long">' . esc_html( $long ) . '</span><span class="er-es-court">' . esc_html( $court ) . '</span></span></p>';
	} else {
		$date = '<p class="er-es-date"><span class="er-es-heure">' . esc_html__( 'Date à venir', 'er-librairie' ) . '</span></p>';
	}

	$image   = get_the_post_thumbnail( $p, 'medium', array( 'alt' => '' ) );
	$affiche = '<a class="er-es-affiche" href="' . $lien . '" tabindex="-1" aria-hidden="true">' . ( $image ? $image : '<span class="er-es-affiche-vide"></span>' ) . '</a>';

	$action = '';
	if ( 'annule' === $etat ) {
		$action = '<span class="er-es-etat">' . esc_html__( 'Annulée', 'er-librairie' ) . '</span>';
	} elseif ( 'complet' === $etat ) {
		$action = '<span class="er-es-etat">' . esc_html__( 'Complet', 'er-librairie' ) . '</span>';
	} else {
		$inscription = (string) get_post_meta( $p->ID, 'er_inscription', true );
		if ( 'billetterie' === $inscription ) {
			$action = '<a class="er-es-bouton" href="' . $lien . '">' . esc_html__( 'Réserver', 'er-librairie' ) . '</a>';
		} elseif ( 'gratuite' === $inscription ) {
			$action = '<a class="er-es-bouton" href="' . $lien . '">' . esc_html__( 'S’inscrire', 'er-librairie' ) . '</a>';
		} elseif ( 'libre' === $inscription ) {
			$action = '<span class="er-es-etat">' . esc_html__( 'Entrée libre', 'er-librairie' ) . '</span>';
		}
	}

	return '<li class="er-es-ligne' . ( 'annule' === $etat ? ' est-annulee' : '' ) . '">' . $date . $affiche
		. '<a class="er-es-ligne-titre" href="' . $lien . '"' . er_essai_lang( $titre ) . '>' . esc_html( $titre ) . '</a>'
		. ( $action ? '<div class="er-es-action">' . $action . '</div>' : '' ) . '</li>';
}

/**
 * Une rencontre passée, en carte : l'affiche, la date, le titre entier.
 *
 * @param WP_Post $p La rencontre.
 */
function er_essai_carte( $p ) {
	$annulee = 'annule' === get_post_meta( $p->ID, 'er_etat', true );
	$titre   = get_the_title( $p );
	$image   = get_the_post_thumbnail(
		$p,
		'medium_large',
		array(
			'alt'     => '',
			'loading' => 'lazy',
		)
	);
	$date    = er_evt_quand( $p->ID, false ) . ( $annulee ? ' · ' . __( 'annulée', 'er-librairie' ) : '' );
	return '<li class="er-es-carte"><a href="' . esc_url( get_permalink( $p ) ) . '">'
		. '<span class="er-es-carte-affiche">' . ( $image ? $image : '<span class="er-es-affiche-vide"></span>' ) . '</span>'
		. '<span class="er-es-carte-date">' . esc_html( $date ) . '</span>'
		. '<span class="er-es-carte-titre"' . er_essai_lang( $titre ) . '>' . esc_html( $titre ) . '</span>'
		. '</a></li>';
}

/**
 * La bande, les rencontres à venir, puis les rencontres passées (une année, un cycle, les vidéos).
 */
function er_essai_evenements() {
	if ( ! function_exists( 'er_evt_requete' ) || ! function_exists( 'er_evt_bande' ) ) {
		return '';
	}
	// phpcs:disable WordPress.Security.NonceVerification
	$criteres = array(
		'annee' => isset( $_GET['annee'] ) ? preg_replace( '/\D/', '', wp_unslash( $_GET['annee'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		'cycle' => isset( $_GET['cycle'] ) ? sanitize_title( wp_unslash( $_GET['cycle'] ) ) : '',
		'video' => ! empty( $_GET['video'] ) ? '1' : '',
	);
	// phpcs:enable
	$html = er_evt_bande( $criteres ) . '<div class="er-es">';

	// À venir : l'intertitre n'est plus là que pour les lecteurs d'écran.
	$a_venir = er_evt_requete( true );
	$html   .= '<section class="er-es-a-venir" id="a-venir"><h2 class="screen-reader-text">' . esc_html__( 'À venir', 'er-librairie' ) . '</h2>';
	$html   .= $a_venir
		? '<ol class="er-es-lignes">' . implode( '', array_map( 'er_essai_ligne', $a_venir ) ) . '</ol>'
		: '<p class="er-es-vide">' . esc_html__( 'Pas de rencontre annoncée pour l’instant : les prochaines paraîtront ici.', 'er-librairie' ) . '</p>';
	$html   .= '</section>';

	// Les rencontres passées.
	$annees = er_evt_annees();
	if ( ! $annees ) {
		return $html . '</div>';
	}
	if ( ! $criteres['annee'] && ! $criteres['cycle'] && ! $criteres['video'] ) {
		$criteres['annee'] = $annees[0];
	}
	$passees = er_evt_requete( false, $criteres );
	$quoi    = $criteres['annee'];
	if ( $criteres['cycle'] ) {
		$cycle = get_term_by( 'slug', $criteres['cycle'], 'er_cycle' );
		$quoi  = $cycle ? $cycle->name : '';
	} elseif ( $criteres['video'] ) {
		$quoi = __( 'avec une vidéo', 'er-librairie' );
	}
	$titre = __( 'Les rencontres passées', 'er-librairie' ) . ( $quoi ? ', ' . $quoi : '' );
	/* translators: %d: le nombre de rencontres */
	$compte = sprintf( _n( '%d rencontre', '%d rencontres', count( $passees ), 'er-librairie' ), count( $passees ) );
	$html  .= '<section class="er-es-archive" id="archive"><div class="er-es-entete"><h2 class="er-es-titre">' . esc_html( $titre ) . '</h2><p class="er-es-compte">' . esc_html( $compte ) . '</p></div>';
	$html  .= $passees ? '<ul class="er-es-grille">' . implode( '', array_map( 'er_essai_carte', $passees ) ) . '</ul>' : '';
	return $html . '</section></div>';
}
add_shortcode( 'er_essai_evenements', 'er_essai_evenements' );
