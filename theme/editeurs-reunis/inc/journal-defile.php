<?php
/**
 * Le journal, en grand format, qui défile de côté : le module de l'accueil qui suit l'exposition du moment.
 *
 * Retenu le 26 novembre 2026 (planche « J · Le grand format » du canevas « Une nouvelle page d'accueil, aérée » :
 * « ajoute J grand format après l'exposition », André). Les six derniers articles du journal, du plus récent au plus
 * ancien, sur une seule ligne qu'on fait défiler de côté : pour chacun, une grande image en 3:2 (une photo de bord à
 * bord ; une couverture posée au centre sur un fond clair, avec son ombre), la date et le genre, le titre en entier et
 * en grand, la signature et le temps de lecture. Deux flèches et un trait qui avance : assets/js/une-journal-defile.js.
 * Sans JavaScript, la ligne défile au doigt ou au pavé.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * Comment montrer l'image d'un article : « livre » pour une couverture (bien plus haute que large : posée au centre,
 * entière, avec son ombre), « photo » sinon (de bord à bord, recadrée). Le seuil est plus haut que celui de l'index
 * (er_index_vue) : une photo en hauteur, un portrait, se recadre ici en 3:2, comme sur la planche.
 *
 * @param int $image L'image (pièce jointe).
 */
function er_journal_defile_vue( $image ) {
	$meta = $image ? wp_get_attachment_metadata( $image ) : false;
	if ( ! is_array( $meta ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
		return $image ? 'photo' : 'aucune';
	}
	return (int) $meta['height'] > 1.4 * (int) $meta['width'] ? 'livre' : 'photo';
}

/**
 * Le module.
 */
function er_journal_defile_html() {
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	if ( ! $q->posts ) {
		return '';
	}
	update_post_thumbnail_cache( $q );
	$fuseau = new DateTimeZone( 'Europe/Paris' );
	$cartes = '';
	foreach ( $q->posts as $p ) {
		$id   = $p->ID;
		$ts   = (int) get_post_time( 'U', true, $id );
		// « 21 oct. 2026 » : le jour court de l'index, et l'année.
		$date = ( function_exists( 'er_index_jour' ) ? er_index_jour( $ts ) : wp_date( 'j M', $ts, $fuseau ) ) . "\u{00A0}" . wp_date( 'Y', $ts, $fuseau );

		$genre   = function_exists( 'er_journal_genre' ) ? er_journal_genre( $id ) : null;
		$genre   = $genre ? $genre->name : '';
		$langues = (string) get_post_meta( $id, 'er_langues', true );
		if ( false !== strpos( $langues, 'ru' ) && false === strpos( $langues, 'fr' ) ) {
			$genre .= ( '' !== $genre ? ' · ' : '' ) . __( 'en russe', 'editeurs-reunis' );
		}

		$signature = trim( (string) get_post_meta( $id, 'er_signature', true ) );
		$minutes   = function_exists( 'er_journal_lecture' ) ? er_journal_lecture( $id ) : 0;
		if ( $signature && $minutes ) {
			/* translators: 1: qui signe l'article, 2: des minutes */
			$sig = sprintf( __( 'Par %1$s · %2$d min', 'editeurs-reunis' ), $signature, $minutes );
		} elseif ( $signature ) {
			/* translators: %s: qui signe l'article */
			$sig = sprintf( __( 'Par %s', 'editeurs-reunis' ), $signature );
		} elseif ( $minutes ) {
			/* translators: %d: des minutes */
			$sig = sprintf( _n( '%d min de lecture', '%d min de lecture', $minutes, 'editeurs-reunis' ), $minutes );
		} else {
			$sig = '';
		}

		$image = (int) get_post_thumbnail_id( $id );
		$vue   = er_journal_defile_vue( $image );
		$img   = $image ? wp_get_attachment_image(
			$image,
			'livre' === $vue ? 'medium_large' : 'large',
			false,
			array(
				'alt'      => '',
				'sizes'    => 'livre' === $vue ? '(max-width: 781px) 45vw, 320px' : '(max-width: 781px) 84vw, 53vw',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		) : '';
		$titre = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
		$ru    = preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '';

		$cartes .= '<li class="er-jd-article"><a href="' . esc_url( get_permalink( $id ) ) . '">'
			. '<span class="er-jd-cadre est-' . esc_attr( $img ? $vue : 'aucune' ) . '">' . $img . '</span>'
			. '<span class="er-jd-meta"><span class="er-jd-point" aria-hidden="true"></span>' . esc_html( $date ) . ( '' !== $genre ? '<span class="er-jd-genre">' . esc_html( $genre ) . '</span>' : '' ) . '</span>'
			. '<span class="er-jd-titre"' . $ru . '>' . esc_html( $titre ) . '</span>'
			. ( '' !== $sig ? '<span class="er-jd-sig">' . esc_html( $sig ) . '</span>' : '' )
			. '</a></li>';
	}

	$fleche = function ( $sens, $etiquette, $chemin ) {
		return '<button type="button" class="er-jd-fleche" data-sens="' . esc_attr( $sens ) . '" aria-label="' . esc_attr( $etiquette ) . '" hidden><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . esc_attr( $chemin ) . '"/></svg></button>';
	};

	return '<section class="er-journal-defile" aria-labelledby="er-journal-defile-titre">'
		. '<div class="er-jd-tete">'
		. '<h2 id="er-journal-defile-titre" class="er-jd-sur">' . esc_html__( 'Le journal du centre', 'editeurs-reunis' ) . '</h2>'
		. '<div class="er-jd-outils">'
		. $fleche( '-1', __( 'Articles précédents', 'editeurs-reunis' ), 'M15 5l-7 7 7 7' )
		. $fleche( '1', __( 'Articles suivants', 'editeurs-reunis' ), 'M9 5l7 7-7 7' )
		. '<a class="er-jd-tout" href="' . er_lien( 'journal/' ) . '">' . esc_html__( 'Tout le journal', 'editeurs-reunis' ) . '</a>'
		. '</div></div>'
		. '<ol class="er-jd-piste" tabindex="0" aria-label="' . esc_attr__( 'Les derniers articles du journal', 'editeurs-reunis' ) . '">' . $cartes . '</ol>'
		. '<div class="er-jd-barre" aria-hidden="true"><span></span></div>'
		. '</section>';
}

// En code court : le gabarit de l'accueil l'appelle dans un bloc HTML (déplié par le filtre de render_block_core/html,
// functions.php).
add_shortcode( 'er_journal_defile', 'er_journal_defile_html' );
