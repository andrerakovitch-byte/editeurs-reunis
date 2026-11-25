<?php
/**
 * L'exposition du moment : le second module de l'accueil, en rupture, aux couleurs du centre culturel.
 *
 * Retenu le 26 novembre 2026 (planche « Aux couleurs du centre » du canevas « Une nouvelle page d'accueil, aérée » :
 * « garde comme ça, sans le saut de ligne et code », André). L'aplat bleu nuit du centre, de bord à bord ; à gauche,
 * en très grand et d'une traite, le nom de l'artiste en bleu pervenche puis le titre en blanc, de même taille et de
 * même graisse ; deux lignes (jusqu'à quand, l'entrée) et un bouton ; à droite, la photo de l'exposition posée dans le
 * bleu avec une marge, qui glisse un peu moins vite que la page (assets/css/23-expo-du-moment.css).
 *
 * Tout est lu dans l'exposition (er_exposition) : celle qui est en cours, celle qui finit le plus tôt d'abord ; à
 * défaut, la prochaine. Le nom vient de « La ligne des artistes » (er_expo_artistes), l'entrée du champ « Entrée »,
 * la photo de l'image mise en avant. Sans exposition en cours ni à venir, le module ne paraît pas ; sans photo, le
 * texte prend toute la largeur.
 *
 * La photo de « Je m'appelle la guerre » est provisoire (pas de droits) : elle se pose dans l'administration, jamais
 * dans ce dépôt public, et se retire avant la mise en ligne.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * L'exposition à montrer : en cours (celle qui finit le plus tôt), sinon la prochaine ; 0 s'il n'y en a pas.
 */
function er_expo_du_moment() {
	if ( ! defined( 'ER_EXPO' ) || ! function_exists( 'er_expo_etat' ) ) {
		return 0;
	}
	$aujourdhui = ( new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Paris' ) ) )->format( 'Y-m-d' );
	$q          = new WP_Query(
		array(
			'post_type'      => ER_EXPO,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				'fin'      => array(
					'key'     => 'er_fin',
					'value'   => $aujourdhui,
					'compare' => '>=',
				),
				'debut'    => array(
					'key'     => 'er_debut',
					'value'   => ( new DateTimeImmutable( $aujourdhui ) )->modify( '-1 month' )->format( 'Y-m-d' ),
					'compare' => '>=',
				),
			),
		)
	);
	$en_cours = array();
	$a_venir  = array();
	foreach ( $q->posts as $p ) {
		$etat = er_expo_etat( $p->ID );
		$fin  = (string) get_post_meta( $p->ID, 'er_fin', true );
		if ( 'en_cours' === $etat ) {
			$en_cours[ $p->ID ] = '' !== $fin ? $fin : '9999';
		} elseif ( 'a_venir' === $etat ) {
			$a_venir[ $p->ID ] = (string) get_post_meta( $p->ID, 'er_debut', true );
		}
	}
	asort( $en_cours );
	asort( $a_venir );
	$ids = array_keys( $en_cours ? $en_cours : $a_venir );
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Une date sans l'année quand c'est celle-ci : « 30 novembre », « 10 janvier 2027 ».
 *
 * @param DateTimeImmutable $d La date.
 */
function er_expo_du_moment_jour( $d ) {
	$fuseau = new DateTimeZone( 'Europe/Paris' );
	$annee  = wp_date( 'Y', null, $fuseau ) === $d->format( 'Y' );
	$jour   = wp_date( $annee ? _x( 'j F', 'date de l’exposition du moment, sur l’accueil', 'editeurs-reunis' ) : _x( 'j F Y', 'date de l’exposition du moment, sur l’accueil', 'editeurs-reunis' ), $d->getTimestamp(), $fuseau );
	return '1' === $d->format( 'j' ) && 'fr' === substr( determine_locale(), 0, 2 ) ? preg_replace( '/^1 /', '1er ', $jour ) : $jour;
}

/**
 * Le module.
 */
function er_expo_du_moment_html() {
	$id = er_expo_du_moment();
	if ( ! $id ) {
		return '';
	}
	$titre    = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	$artistes = function_exists( 'er_expo_artistes' ) ? er_expo_artistes( $id ) : '';
	$debut    = function_exists( 'er_evt_date' ) ? er_evt_date( $id ) : null;
	$fin      = function_exists( 'er_evt_date' ) ? er_evt_date( $id, 'er_fin' ) : null;
	$etat     = er_expo_etat( $id );

	// Les deux lignes : jusqu'à quand (ou à partir de quand), puis l'entrée.
	$quand = '';
	if ( 'a_venir' === $etat && $debut ) {
		/* translators: %s: le premier jour (« 10 janvier ») */
		$quand = sprintf( __( 'À partir du %s', 'editeurs-reunis' ), er_expo_du_moment_jour( $debut ) );
	} elseif ( $fin && wp_date( 'Y-m-d', null, new DateTimeZone( 'Europe/Paris' ) ) === $fin->format( 'Y-m-d' ) ) {
		$quand = __( 'Dernier jour aujourd’hui', 'editeurs-reunis' );
	} elseif ( $fin ) {
		/* translators: %s: le dernier jour (« 30 novembre ») */
		$quand = sprintf( __( 'Jusqu’au %s', 'editeurs-reunis' ), er_expo_du_moment_jour( $fin ) );
	}
	$entree = trim( (string) get_post_meta( $id, 'er_entree', true ) );
	if ( '' !== $entree ) {
		// En bas de casse, comme la suite de la première ligne (« Jusqu'au 30 novembre / entrée libre », la planche).
		/* translators: %s: l'entrée, telle qu'on l'a saisie (« libre », « libre, aux heures de la librairie ») */
		$entree = sprintf( __( 'entrée %s', 'editeurs-reunis' ), mb_strtolower( mb_substr( $entree, 0, 1 ) ) . mb_substr( $entree, 1 ) );
	}
	$lignes = array_filter( array( $quand, $entree ) );

	$image = (int) get_post_thumbnail_id( $id );
	$photo = $image ? wp_get_attachment_image(
		$image,
		'large',
		false,
		array(
			'class'    => 'er-expo-moment-image',
			'sizes'    => '(max-width: 781px) 100vw, 40vw',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	) : '';
	$legende = $image ? wp_get_attachment_caption( $image ) : '';
	$ru      = fn( $t ) => preg_match( '/\p{Cyrillic}/u', $t ) ? ' lang="ru"' : '';
	// Les coupures de la planche : le nom se lie au premier mot du titre (« Victoria / Ivleva Je »), les deux derniers
	// mots du titre restent ensemble (« m’appelle / la guerre ») ; la largeur du titre, en em, fait le reste (CSS).

	return '<section class="er-expo-moment' . ( $photo ? '' : ' est-sans-photo' ) . '" aria-labelledby="er-expo-moment-titre">'
		. '<div class="er-expo-moment-texte">'
		. '<h2 id="er-expo-moment-titre" class="er-expo-moment-titre">'
		. ( $artistes ? '<span class="er-expo-moment-artiste"' . $ru( $artistes ) . '>' . esc_html( $artistes ) . '</span>&nbsp;' : '' )
		. '<span' . $ru( $titre ) . '>' . preg_replace( '/ (\S+)$/u', '&nbsp;$1', esc_html( $titre ) ) . '</span></h2>'
		. ( $lignes ? '<p class="er-expo-moment-quand">' . implode( '<br>', array_map( 'esc_html', $lignes ) ) . '</p>' : '' )
		. '<p class="er-expo-moment-action"><a class="er-expo-moment-bouton" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'Voir l’exposition', 'editeurs-reunis' ) . '</a></p>'
		. '</div>'
		. ( $photo ? '<figure class="er-expo-moment-photo"><div class="er-expo-moment-cadre">' . $photo . '</div>' . ( $legende ? '<figcaption>' . esc_html( $legende ) . '</figcaption>' : '' ) . '</figure>' : '' )
		. '</section>';
}

// En code court : le gabarit de l'accueil l'appelle dans un bloc HTML (déplié par le filtre de render_block_core/html,
// functions.php).
add_shortcode( 'er_expo_du_moment', 'er_expo_du_moment_html' );
