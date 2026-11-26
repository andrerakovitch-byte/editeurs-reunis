<?php
/**
 * La phrase à trous, en haut de l'accueil (Q5c du canevas « Les pages intérieures », retenue le 26 nov. 2026) :
 * « Vous cherchez un livre, une rencontre, une exposition ou un classique ? » Au survol, au toucher ou au clavier, le
 * mot passe à l'encre, la réponse se réécrit dessous et son image paraît à droite, comme dans l'index qui suit. Le
 * gabarit est celui de la tête des pages intérieures : 400 px, le texte calé en bas.
 *
 * Le livre (la librairie) et le classique (Le Maître et Marguerite) sont écrits ici. La rencontre et l'exposition se
 * lisent dans le site à chaque affichage : la prochaine rencontre et l'exposition du moment. Leur phrase est l'extrait
 * de leur fiche s'il est rempli (« l'historien Nicolas Werth présentera son livre Adieu ma Russie »), sinon elle se
 * compose de leur titre ; l'image de la rencontre est la couverture de son livre (les livres liés, puis ceux des
 * intervenants), à défaut sa propre image. Sans rencontre à venir ou sans exposition, le mot s'en va de la question.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le livre d'une rencontre : le premier des livres liés (er_livres), sinon le premier livre en stock d'un intervenant
 * qui est aussi un auteur (pas de qui anime). Avec « son » quand c'est le livre d'un intervenant.
 *
 * @param int $id La rencontre.
 * @return array{0:int,1:bool}|null L'identifiant du livre, et s'il est d'un intervenant.
 */
function er_ap_livre_de_la_rencontre( $id ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	$bon = function ( $pid ) {
		$p = wc_get_product( $pid );
		return $p && 'publish' === $p->get_status() && has_post_thumbnail( $pid ) && ! $p->get_meta( 'er_evenement' );
	};
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'er_livres', true ) ) ) ) as $ref ) {
		$pid = ctype_digit( $ref ) ? (int) $ref : (int) wc_get_product_id_by_sku( $ref );
		if ( $pid && $bon( $pid ) ) {
			return array( $pid, false );
		}
	}
	$auteurs = array();
	foreach ( (array) wp_get_post_terms( $id, 'er_intervenant' ) as $t ) {
		if ( ! $t instanceof WP_Term || ( function_exists( 'er_evt_anime' ) && er_evt_anime( $t, $id ) ) ) {
			continue;
		}
		$auteur = function_exists( 'er_evt_personne' ) ? er_evt_personne( $t ) : get_term_by( 'slug', $t->slug, 'auteur' );
		if ( $auteur ) {
			$auteurs[] = $auteur->term_id;
		}
	}
	if ( ! $auteurs ) {
		return null;
	}
	$livres = get_posts(
		array(
			'post_type'   => 'product',
			'numberposts' => 6,
			'fields'      => 'ids',
			'tax_query'   => array( array( 'taxonomy' => 'auteur', 'terms' => $auteurs ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_query'  => array( array( 'key' => '_stock_status', 'value' => 'instock' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $livres as $pid ) {
		if ( $bon( $pid ) ) {
			return array( (int) $pid, true );
		}
	}
	return null;
}

/**
 * Le début d'une phrase en minuscule quand c'est un nom commun (« Rencontre avec… » → « rencontre avec… ») ; les
 * noms propres gardent leur majuscule.
 *
 * @param string $texte Le texte.
 */
function er_ap_minuscule( $texte ) {
	$communs = array( 'rencontre', 'conférence', 'lecture', 'présentation', 'soirée', 'projection', 'table', 'concert', 'dialogue', 'discussion', 'débat', 'signature' );
	$premier = mb_strtolower( (string) strtok( $texte, ' ' ) );
	return in_array( $premier, $communs, true ) ? mb_strtolower( mb_substr( $texte, 0, 1 ) ) . mb_substr( $texte, 1 ) : $texte;
}

/**
 * L'extrait d'une fiche, s'il a été écrit (pas celui que WordPress tire du texte).
 *
 * @param int $id La fiche.
 */
function er_ap_extrait( $id ) {
	return trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $id ) ) );
}

/**
 * Une image du thème, cadrée comme dans les triptyques.
 *
 * @param string $nom Le fichier (assets/images/…).
 * @param array  $cadre Le zoom, X et Y.
 */
function er_ap_image_theme( $nom, $cadre = array( 1, 50, 50 ) ) {
	list( $z, $x, $y ) = $cadre;
	return '<span class="er-ap-cadre"><img src="' . esc_url( er_image( $nom ) ) . '" alt="" loading="lazy" decoding="async" style="'
		. esc_attr( sprintf( '--er-tpq-x:%1$s%%;--er-tpq-y:%2$s%%;--er-tpq-z:%3$s', $x, $y, $z ) ) . '"></span>';
}

/**
 * Les réponses, dans l'ordre de la question : la clé, le mot, la réponse (HTML), le lien, l'image (HTML) et si l'image
 * est une couverture (montrée entière, avec son ombre).
 *
 * @return array<int,array<string,mixed>>
 */
function er_ap_reponses() {
	$reponses = array();
	$fleche   = ' <span aria-hidden="true">→</span>';

	// Un livre : la librairie.
	$reponses[] = array(
		'cle'    => 'livre',
		'mot'    => __( 'un livre', 'editeurs-reunis' ),
		'texte'  => esc_html__( 'La plus ancienne librairie russe de Paris : littérature, histoire, pensée, en russe et en français', 'editeurs-reunis' ),
		'lien'   => er_em_lien( 'librairie/' ),
		'image'  => er_ap_image_theme( 'triptyques/khokhloma.jpg', array( 1.5, 0, 0 ) ),
		'livre'  => false,
	);

	// Une rencontre : la prochaine (deux heures après son début, elle est passée).
	foreach ( function_exists( 'er_info_rencontres' ) ? er_info_rencontres() : array() as $r ) {
		$debut = function_exists( 'er_info_iso' ) ? er_info_iso( $r->ID, 'er_debut' ) : '';
		if ( ! $debut || strtotime( $debut ) + 2 * HOUR_IN_SECONDS < time() ) {
			continue;
		}
		$id    = function_exists( 'er_traduction' ) ? er_traduction( $r->ID ) : $r->ID;
		$quand = function_exists( 'er_info_quand' ) ? er_info_quand( $r->ID ) : '';
		$quand = '' !== $quand ? mb_strtoupper( mb_substr( $quand, 0, 1 ) ) . mb_substr( $quand, 1 ) . ', ' : '';
		$livre = er_ap_livre_de_la_rencontre( $r->ID );
		$texte = er_ap_extrait( $id );
		if ( '' !== $texte ) {
			$texte = esc_html( $quand . er_ap_minuscule( $texte ) );
		} else {
			$texte = esc_html( $quand . er_ap_minuscule( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) );
			if ( $livre ) {
				/* translators: %s: le titre du livre, en italique */
				$texte .= sprintf( $livre[1] ? esc_html__( ', autour de son livre %s', 'editeurs-reunis' ) : esc_html__( ', autour du livre %s', 'editeurs-reunis' ), '<em>' . esc_html( html_entity_decode( get_the_title( $livre[0] ), ENT_QUOTES, 'UTF-8' ) ) . '</em>' );
			}
		}
		$photo      = $livre ? (int) get_post_thumbnail_id( $livre[0] ) : (int) get_post_thumbnail_id( $id );
		$reponses[] = array(
			'cle'   => 'rencontre',
			'mot'   => __( 'une rencontre', 'editeurs-reunis' ),
			'texte' => $texte,
			'lien'  => get_permalink( $id ),
			'image' => $photo ? ( $livre ? '' : '<span class="er-ap-cadre">' ) . wp_get_attachment_image( $photo, 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) . ( $livre ? '' : '</span>' ) : '',
			'livre' => (bool) $livre,
		);
		break;
	}

	// Une exposition : celle du moment (en cours, sinon la prochaine).
	$expo = function_exists( 'er_expo_du_moment' ) ? er_expo_du_moment() : 0;
	if ( $expo ) {
		$titre = '<em>' . esc_html( html_entity_decode( get_the_title( $expo ), ENT_QUOTES, 'UTF-8' ) ) . '</em>';
		$texte = er_ap_extrait( $expo );
		if ( '' !== $texte ) {
			$texte = $titre . ' : ' . esc_html( er_ap_minuscule( $texte ) );
		} else {
			$artistes = function_exists( 'er_expo_artistes' ) ? er_expo_artistes( $expo ) : '';
			$fin      = function_exists( 'er_evt_date' ) ? er_evt_date( $expo, 'er_fin' ) : null;
			$texte    = $titre . ( '' !== $artistes ? esc_html( sprintf( /* translators: %s: les artistes */ __( ', de %s', 'editeurs-reunis' ), $artistes ) ) : '' )
				. ( $fin && function_exists( 'er_expo_du_moment_jour' ) ? esc_html( sprintf( /* translators: %s: le dernier jour */ __( ', jusqu’au %s', 'editeurs-reunis' ), er_expo_du_moment_jour( $fin ) ) ) : '' );
		}
		$photo      = (int) get_post_thumbnail_id( $expo );
		$reponses[] = array(
			'cle'   => 'expo',
			'mot'   => __( 'une exposition', 'editeurs-reunis' ),
			'texte' => $texte,
			'lien'  => get_permalink( function_exists( 'er_traduction' ) ? er_traduction( $expo ) : $expo ),
			'image' => $photo ? '<span class="er-ap-cadre">' . wp_get_attachment_image( $photo, 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) . '</span>' : '',
			'livre' => false,
		);
	}

	// Un classique : Le Maître et Marguerite, dont YMCA-Press a donné la première édition (la couverture de 1967).
	$classique  = get_transient( 'er_ap_classique' );
	if ( false === $classique ) {
		$trouves   = function_exists( 'wc_get_product' ) ? get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				's'           => 'Maître et Marguerite',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		) : array();
		$classique = $trouves ? (int) $trouves[0] : 0;
		set_transient( 'er_ap_classique', $classique, 12 * HOUR_IN_SECONDS );
	}
	$reponses[] = array(
		'cle'   => 'classique',
		'mot'   => __( 'un classique', 'editeurs-reunis' ),
		/* translators: %s: le titre, en italique */
		'texte' => sprintf( esc_html__( '%s, de Boulgakov, dont YMCA-Press a donné la première édition en 1967', 'editeurs-reunis' ), '<em>' . esc_html__( 'Le Maître et Marguerite', 'editeurs-reunis' ) . '</em>' ),
		'lien'  => $classique ? get_permalink( $classique ) : er_em_lien( 'ymca-press/catalogue/' ),
		'image' => '<img src="' . esc_url( er_image( 'couv-maitre-marguerite.jpg' ) ) . '" alt="" width="619" height="950" loading="lazy" decoding="async">',
		'livre' => true,
	);

	foreach ( $reponses as &$r ) {
		$r['texte'] .= $fleche;
	}
	return $reponses;
}

/**
 * Le module.
 */
function er_accueil_phrase() {
	$reponses = er_ap_reponses();
	$n        = count( $reponses );
	$mots     = '';
	$textes   = '';
	$images   = '';
	foreach ( $reponses as $i => $r ) {
		$premier = 0 === $i;
		$id      = 'er-ap-' . $r['cle'];
		$sep     = $i < $n - 2 ? ', ' : ( $i === $n - 2 ? ' ' . __( 'ou', 'editeurs-reunis' ) . ' ' : '' );
		$mots   .= '<button type="button" class="er-ap-mot" data-cle="' . esc_attr( $r['cle'] ) . '" aria-controls="' . esc_attr( $id ) . '" aria-pressed="' . ( $premier ? 'true' : 'false' ) . '">'
			. esc_html( $r['mot'] ) . '</button>' . esc_html( $sep );
		$textes .= '<a class="er-ap-reponse' . ( $premier ? ' est-vue' : '' ) . '" id="' . esc_attr( $id ) . '" data-cle="' . esc_attr( $r['cle'] ) . '" href="' . esc_url( $r['lien'] ) . '"' . ( $premier ? '' : ' inert' ) . '>'
			. $r['texte'] . '</a>';
		if ( $r['image'] ) {
			$images .= '<span class="er-ap-image' . ( $r['livre'] ? ' est-livre' : '' ) . ( $premier ? ' est-vue' : '' ) . '" data-cle="' . esc_attr( $r['cle'] ) . '">' . $r['image'] . '</span>';
		}
	}
	/* translators: la question en haut de l'accueil, devant les mots à choisir (« un livre, une rencontre… ») */
	$question = esc_html__( 'Vous cherchez', 'editeurs-reunis' );
	return '<section class="er-ap" aria-label="' . esc_attr__( 'À la une', 'editeurs-reunis' ) . '"><div class="er-ap-corps">'
		. '<div class="er-ap-texte"><p class="er-ap-question">' . $question . ' ' . $mots . "\u{00A0}?</p>"
		. '<div class="er-ap-reponses" aria-live="polite">' . $textes . '</div></div>'
		. '<div class="er-ap-images" aria-hidden="true">' . $images . '</div></div></section>';
}
add_shortcode( 'er_accueil_phrase', 'er_accueil_phrase' );

// Son script, sur l'accueil seulement.
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_front_page() ) {
			return;
		}
		wp_enqueue_script(
			'editeurs-reunis-accueil-phrase',
			get_theme_file_uri( 'assets/js/accueil-phrase.js' ),
			array(),
			wp_get_theme()->get( 'Version' ) . '.' . filemtime( get_theme_file_path( 'assets/js/accueil-phrase.js' ) ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
);
