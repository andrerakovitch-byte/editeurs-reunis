<?php
/**
 * L'en-tête plein écran (retenu le 24 novembre 2026, le « réglage 2 » du canevas « Les Éditeurs réunis — plein
 * écran ») : trois bandes, de marge à marge.
 *
 * 1. La barre d'informations (40 px), noire et discrète : ouvert ou fermé en ce moment, l'exposition en cours, la
 *    prochaine rencontre de la semaine, l'adresse. Les horaires et les dates sont lus dans le navigateur
 *    (assets/js/entete.js) : la page peut venir d'un cache, l'heure, elle, est toujours la bonne.
 * 2. La barre noire (88 px) : le nom, les trois maisons, la loupe, le compte, le panier, les langues (patterns/en-tete.php).
 *    Un clic sur une maison ne mène pas à sa page d'accueil : il montre son menu sur la ligne du dessous, sans quitter la
 *    page (sans JavaScript, le lien mène à la maison).
 * 3. Les lignes des maisons (48 px), une par maison, une seule visible : la librairie (ses rayons et leurs tiroirs,
 *    patterns/menu-librairie.php), YMCA-Press, le centre culturel. Au bout de chacune, la loupe et le panier, qui ne
 *    se montrent que quand l'en-tête est accroché.
 *
 * En défilant, la barre d'informations et la barre noire partent avec la page ; la ligne de la maison reste accrochée
 * en haut de l'écran (style.css, « L'en-tête accroché »). Le sous-menu d'une rubrique (les genres du journal)
 * s'accroche dessous.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/* Les horaires ----------------------------------------------------------------------------------------------- */

/**
 * Les jours de la semaine, dans l'ordre de PHP (1 = lundi … 7 = dimanche), tels qu'on les écrit dans le réglage.
 */
function er_jours() {
	return array(
		1 => 'lundi',
		2 => 'mardi',
		3 => 'mercredi',
		4 => 'jeudi',
		5 => 'vendredi',
		6 => 'samedi',
		7 => 'dimanche',
	);
}

/**
 * Les horaires de la librairie, d'après le réglage « Horaires de la librairie » (Réglages → Général) : une ligne par
 * jour ouvert, « mardi 10:00-19:00 » (ou « mardi 10 h – 19 h ») ; un jour absent est un jour de fermeture.
 *
 * @return array<int, array{0: string, 1: string}> Le jour (1 à 7) => [ ouverture, fermeture ], en « HH:MM ».
 */
function er_horaires() {
	$texte    = (string) get_option( 'er_horaires', '' );
	$horaires = array();
	$jours    = array_flip( er_jours() );
	foreach ( preg_split( '/\R/', $texte ) as $ligne ) {
		if ( ! preg_match( '/^\s*(\p{L}+)\s*:?\s*(\d{1,2})\s*(?:h|:)\s*(\d{2})?\s*[-–à]+\s*(\d{1,2})\s*(?:h|:)\s*(\d{2})?/iu', $ligne, $m ) ) {
			continue;
		}
		$jour = mb_strtolower( $m[1] );
		if ( ! isset( $jours[ $jour ] ) ) {
			continue;
		}
		$horaires[ $jours[ $jour ] ] = array(
			sprintf( '%02d:%02d', (int) $m[2], (int) ( $m[3] ?? 0 ) ),
			sprintf( '%02d:%02d', (int) $m[4], (int) ( $m[5] ?? 0 ) ),
		);
	}
	ksort( $horaires );
	return $horaires;
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'general',
			'er_horaires',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => '',
			)
		);
		add_settings_field(
			'er_horaires',
			__( 'Horaires de la librairie', 'editeurs-reunis' ),
			function () {
				printf(
					'<textarea name="er_horaires" id="er_horaires" rows="7" cols="40" class="large-text code" placeholder="%s">%s</textarea><p class="description">%s</p>',
					esc_attr( "mardi 10:00-19:00\nmercredi 10:00-19:00" ),
					esc_textarea( (string) get_option( 'er_horaires', '' ) ),
					esc_html__( 'Une ligne par jour d’ouverture (« mardi 10:00-19:00 ») ; un jour absent est fermé. La barre d’informations, en haut du site, en tire « Ouvert en ce moment » ou « Fermé en ce moment ».', 'editeurs-reunis' )
				);
			},
			'general'
		);
	}
);

/* La barre d'informations ------------------------------------------------------------------------------------ */

/**
 * L'exposition en cours (la première à finir), s'il y en a une.
 *
 * @return WP_Post|null
 */
function er_info_exposition() {
	if ( ! post_type_exists( 'er_exposition' ) ) {
		return null;
	}
	$maintenant = wp_date( 'Y-m-d H:i' );
	$expos      = get_posts(
		array(
			'post_type'      => 'er_exposition',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_key'       => 'er_fin', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => 'er_debut',
					'value'   => $maintenant,
					'compare' => '<=',
				),
				array(
					'key'     => 'er_fin',
					'value'   => $maintenant,
					'compare' => '>=',
				),
			),
		)
	);
	return $expos ? $expos[0] : null;
}

/**
 * Les rencontres des six semaines qui viennent (au plus trois) : le navigateur garde la première qui n'est pas passée,
 * « Prochain événement », avec son jour et son heure.
 *
 * @return WP_Post[]
 */
function er_info_rencontres() {
	if ( ! post_type_exists( 'er_evenement' ) ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'      => 'er_evenement',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
			'meta_key'       => 'er_debut', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'AND',
				array(
					'key'     => 'er_debut',
					'value'   => array( wp_date( 'Y-m-d 00:00' ), wp_date( 'Y-m-d 23:59', time() + 42 * DAY_IN_SECONDS ) ),
					'compare' => 'BETWEEN',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'er_etat',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'er_etat',
						'value'   => 'annule',
						'compare' => '!=',
					),
				),
			),
		)
	);
}

/**
 * Une date des champs d'une rencontre (« AAAA-MM-JJ HH:MM », heure de Paris), à la norme ISO, pour le navigateur.
 *
 * @param int    $id  La rencontre ou l'exposition.
 * @param string $cle er_debut ou er_fin.
 */
function er_info_iso( $id, $cle ) {
	$valeur = (string) get_post_meta( $id, $cle, true );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2})?$/', $valeur ) ) {
		return '';
	}
	$date = new DateTimeImmutable( $valeur, new DateTimeZone( 'Europe/Paris' ) );
	return $date->format( DATE_ATOM );
}

/**
 * Le jour et l'heure d'une rencontre, pour la barre d'informations : « lundi 30 novembre à 19 h », « mardi
 * 1er décembre à 18 h 30 » (le jour seul quand l'heure n'est pas dite).
 *
 * @param int $id La rencontre.
 * @return string
 */
function er_info_quand( $id ) {
	$valeur = (string) get_post_meta( $id, 'er_debut', true );
	if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2})(?: (\d{2}):(\d{2}))?$/', $valeur, $m ) ) {
		return '';
	}
	$fuseau = new DateTimeZone( 'Europe/Paris' );
	$date   = new DateTimeImmutable( $m[1], $fuseau );
	$fr     = 'fr' === substr( determine_locale(), 0, 2 );
	// « 1er décembre » : le premier du mois s'écrit en ordinal.
	$modele = $fr && '1' === $date->format( 'j' ) ? 'l \\1\\e\\r F' : _x( 'l j F', 'jour d’une rencontre dans la barre d’informations', 'editeurs-reunis' );
	$jour   = wp_date( $modele, $date->getTimestamp(), $fuseau );
	if ( empty( $m[2] ) || '00:00' === $m[2] . ':' . $m[3] ) {
		return $jour;
	}
	$heure = $fr ? (int) $m[2] . ' h' . ( '00' === $m[3] ? '' : ' ' . $m[3] ) : $m[2] . ':' . $m[3];
	/* translators: 1: le jour (« lundi 30 novembre »), 2: l'heure (« 19 h »). */
	return sprintf( __( '%1$s à %2$s', 'editeurs-reunis' ), $jour, $heure );
}

/**
 * La barre d'informations, en haut du site.
 */
function er_infos() {
	$nous_trouver = er_lien( 'nous-trouver/' );
	$horaires     = er_horaires();
	$items        = array();

	// Les jours et les heures d'ouverture (25 nov. 2026). Avec des horaires posés (réglage er_horaires), le navigateur
	// dit plutôt s'il est ouvert ou fermé en ce moment.
	$items[] = sprintf(
		'<a class="er-info er-info-ouvert" href="%1$s" data-horaires="%2$s"><span class="er-info-point" aria-hidden="true"></span><span class="er-info-texte">%3$s</span></a>',
		$nous_trouver,
		esc_attr( wp_json_encode( (object) $horaires ) ),
		esc_html__( 'Ouvert du mardi au samedi de 10 h à 18 h 30', 'editeurs-reunis' )
	);

	$expo = er_info_exposition();
	if ( $expo ) {
		$fin     = er_info_iso( $expo->ID, 'er_fin' );
		$items[] = sprintf(
			'<a class="er-info er-info-expo er-info-plus" href="%1$s">%2$s · <strong>%3$s</strong>%4$s</a>',
			esc_url( get_permalink( er_traduction( $expo->ID ) ) ),
			esc_html__( 'Exposition', 'editeurs-reunis' ),
			esc_html( get_the_title( er_traduction( $expo->ID ) ) ),
			/* translators: %s: la date de fin d'une exposition (« 28 février »). */
			$fin ? esc_html( sprintf( __( ', jusqu’au %s', 'editeurs-reunis' ), wp_date( _x( 'j F', 'date de fin d’une exposition', 'editeurs-reunis' ), strtotime( $fin ) ) ) ) : ''
		);
	}

	$rencontres = er_info_rencontres();
	if ( $rencontres ) {
		$liste = '';
		foreach ( $rencontres as $r ) {
			$debut = er_info_iso( $r->ID, 'er_debut' );
			if ( ! $debut ) {
				continue;
			}
			$quand  = er_info_quand( $r->ID );
			$liste .= sprintf(
				'<a class="er-info er-info-rencontre er-info-plus" href="%1$s" data-debut="%2$s" hidden>%3$s · <strong>%4$s</strong>%5$s</a>',
				esc_url( get_permalink( er_traduction( $r->ID ) ) ),
				esc_attr( $debut ),
				esc_html__( 'Prochain événement', 'editeurs-reunis' ),
				esc_html( get_the_title( er_traduction( $r->ID ) ) ),
				'' !== $quand ? ', ' . esc_html( $quand ) : ''
			);
		}
		$items[] = $liste;
	}

	$items[] = sprintf(
		'<a class="er-info er-info-adresse er-info-plus" href="%1$s"><span class="er-info-rue">%2$s</span> · <strong>%3$s</strong></a>',
		$nous_trouver,
		esc_html__( '11, rue de la Montagne-Sainte-Geneviève, 75005 Paris', 'editeurs-reunis' ),
		esc_html__( 'Nous trouver →', 'editeurs-reunis' )
	);

	return '<div class="er-infos" role="region" aria-label="' . esc_attr__( 'En ce moment', 'editeurs-reunis' ) . '"><div class="er-infos-dedans">' . implode( '', $items ) . '</div></div>';
}
add_shortcode( 'er_infos', 'er_infos' );

/* Les lignes des maisons ------------------------------------------------------------------------------------- */

/**
 * La loupe et le panier au bout d'une ligne : visibles seulement quand l'en-tête est accroché (la barre noire, qui les
 * porte, est alors sortie de l'écran). La loupe ouvre le même panneau que celle de la barre (assets/js/recherche.js).
 */
function er_ligne_pictos() {
	$panier = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : er_lien( 'panier/' );
	$loupe  = function_exists( 'er_lien_recherche' ) ? er_lien_recherche() : home_url( '/?s=' );
	return '<div class="er-ligne-pictos">'
		. '<a class="er-loupe er-picto er-ligne-loupe" href="' . esc_url( $loupe ) . '" aria-controls="er-recherche" aria-expanded="false" aria-haspopup="dialog"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M15.5 15.5L20 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><span class="screen-reader-text er-loupe-libelle">' . esc_html__( 'Rechercher', 'editeurs-reunis' ) . '</span></a>'
		. '<a class="er-picto er-ligne-panier" href="' . esc_url( $panier ) . '"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M5 8h14l-1.2 11.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8" fill="none" stroke="currentColor" stroke-width="1.7"/></svg><span class="screen-reader-text">' . esc_html__( 'Panier', 'editeurs-reunis' ) . '</span><span class="er-ligne-badge" aria-hidden="true" hidden></span></a>'
		. '</div>';
}

/**
 * Les trois lignes, sous la barre noire : celle de la maison de la page est visible (la librairie sur l'accueil),
 * les deux autres attendent un clic sur leur maison. Sur une page commune (le panier, le compte), aucune n'est visible.
 */
function er_lignes_maisons() {
	$maison = is_front_page() ? 'librairie' : er_maison();
	$pictos = er_ligne_pictos();
	$html   = '<div class="er-lignes">';
	$html  .= '<div class="er-ligne er-ligne-librairie" data-maison="librairie"' . ( 'librairie' === $maison ? '' : ' hidden' ) . '>'
		. do_blocks( '<!-- wp:pattern {"slug":"editeurs-reunis/menu-librairie"} /-->' ) . $pictos . '</div>';
	foreach ( array( 'ymca', 'centre' ) as $cle ) {
		$html .= '<div class="er-ligne er-ligne-' . $cle . '" data-maison="' . $cle . '"' . ( $cle === $maison ? '' : ' hidden' ) . '>'
			. er_sous_menu( $cle ) . $pictos . '</div>';
	}
	return $html . '</div>';
}
add_shortcode( 'er_lignes', 'er_lignes_maisons' );
