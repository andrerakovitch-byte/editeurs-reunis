<?php
/**
 * La phrase en haut de l'accueil (Y3b du canevas « Les pages intérieures », retenue le 26 nov. 2026, d'après Q5c) :
 * une seule phrase, sur trois lignes au plus, qui change d'elle-même toutes les six secondes ; sa photo suit la souris
 * (André : « c'est plus classe sur 3 lignes uniquement », la question « Vous cherchez… ? » enlevée). Dessous, le
 * triptyque de l'accueil, bord à bord, que le bas de l'écran coupe pour donner envie de descendre ; puis l'index.
 *
 * La librairie et le classique sont écrits ici, chacun une phrase qui se tient seule (« Notre librairie… », « Notre
 * maison d'édition a publié… »). La rencontre et l'exposition se lisent dans le site à chaque affichage : la prochaine
 * rencontre (son jour, son titre, son livre : « Lundi 30 novembre à 19 h, rencontre avec l'historien Nicolas Werth,
 * autour de son livre Adieu ma Russie ») et l'exposition du moment (son titre, ses artistes, jusqu'à quand). L'extrait
 * des fiches n'y entre pas : écrit pour être lu seul, il ne suit pas une date. L'image de la rencontre est la
 * couverture de son livre (les livres liés, puis ceux des intervenants), à défaut sa propre image. Sans rencontre à
 * venir ou sans exposition, la phrase saute son tour.
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
 * Une date d'un seul tenant : pas de saut de ligne entre un nombre et ce qui le suit (« 30 novembre », « 19 h »), ni
 * après « jusqu'au » (André, 26 nov. : « jusqu'au 30 / novembre » coupait la date).
 *
 * @param string $texte Le texte (déjà échappé, ou à échapper ensuite).
 */
function er_ap_insecable( $texte ) {
	$texte = preg_replace( '/(\d+(?:er)?) (?=\S)/u', "$1\u{00A0}", $texte );
	return preg_replace( '/(jusqu’au|jusqu\'au) /u', "$1\u{00A0}", $texte );
}

/**
 * La photo qui suit la souris sur la phrase, dans son format d'origine (comme celles des trois maisons) : une image
 * du thème, ou une pièce jointe ; avec sa largeur et sa hauteur, dont le script tire sa taille.
 *
 * @param string|int $source Le fichier du thème (assets/images/…) ou l'identifiant de la pièce jointe.
 * @param bool       $livre  Une couverture (avec son ombre).
 */
function er_ap_photo( $source, $livre = false ) {
	$classe = 'er-ap-suit-photo' . ( $livre ? ' est-livre' : '' );
	if ( is_int( $source ) ) {
		return $source ? wp_get_attachment_image(
			$source,
			'medium_large',
			false,
			array(
				'class'    => $classe,
				'alt'      => '',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'sizes'    => '460px',
			)
		) : '';
	}
	$chemin = get_theme_file_path( 'assets/images/' . $source );
	$taille = is_readable( $chemin ) ? wp_getimagesize( $chemin ) : false;
	return $taille ? '<img class="' . esc_attr( $classe ) . '" src="' . esc_url( er_image( $source ) ) . '" width="' . (int) $taille[0] . '" height="' . (int) $taille[1] . '" alt="" loading="lazy" decoding="async">' : '';
}

/**
 * Les phrases, dans l'ordre où elles passent : la clé, la phrase (HTML), le lien, et la photo qui suit la souris
 * (HTML ; une couverture a son ombre).
 *
 * @return array<int,array<string,mixed>>
 */
function er_ap_reponses() {
	$reponses = array();
	$fleche   = "\u{00A0}" . '<span aria-hidden="true">→</span>'; // La flèche ne reste jamais seule sur sa ligne.

	// Un livre : la librairie.
	$reponses[] = array(
		'cle'   => 'livre',
		'texte' => esc_html__( 'Notre librairie, rue de la Montagne-Sainte-Geneviève, est la plus ancienne librairie russe de Paris', 'editeurs-reunis' ),
		'lien'  => er_em_lien( 'librairie/' ),
		'photo' => er_ap_photo( 'triptyques/khokhloma.jpg' ),
	);

	// Une rencontre : la prochaine (deux heures après son début, elle est passée).
	foreach ( function_exists( 'er_info_rencontres' ) ? er_info_rencontres() : array() as $r ) {
		$debut = function_exists( 'er_info_iso' ) ? er_info_iso( $r->ID, 'er_debut' ) : '';
		if ( ! $debut || strtotime( $debut ) + 2 * HOUR_IN_SECONDS < time() ) {
			continue;
		}
		$id    = function_exists( 'er_traduction' ) ? er_traduction( $r->ID ) : $r->ID;
		$quand = function_exists( 'er_info_quand' ) ? er_info_quand( $r->ID ) : '';
		$quand = '' !== $quand ? er_ap_insecable( mb_strtoupper( mb_substr( $quand, 0, 1 ) ) . mb_substr( $quand, 1 ) ) . ', ' : '';
		$livre = er_ap_livre_de_la_rencontre( $r->ID );
		$texte = esc_html( $quand . er_ap_minuscule( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) );
		if ( $livre ) {
			/* translators: %s: le titre du livre, en italique */
			$texte .= sprintf( $livre[1] ? esc_html__( ', autour de son livre %s', 'editeurs-reunis' ) : esc_html__( ', autour du livre %s', 'editeurs-reunis' ), '<em>' . esc_html( html_entity_decode( get_the_title( $livre[0] ), ENT_QUOTES, 'UTF-8' ) ) . '</em>' );
		}
		$photo      = $livre ? (int) get_post_thumbnail_id( $livre[0] ) : (int) get_post_thumbnail_id( $id );
		$reponses[] = array(
			'cle'   => 'rencontre',
			'texte' => $texte,
			'lien'  => get_permalink( $id ),
			'photo' => er_ap_photo( $photo, (bool) $livre ),
		);
		break;
	}

	// Une exposition : celle du moment (en cours, sinon la prochaine).
	$expo = function_exists( 'er_expo_du_moment' ) ? er_expo_du_moment() : 0;
	if ( $expo ) {
		$titre = '<em>' . esc_html( html_entity_decode( get_the_title( $expo ), ENT_QUOTES, 'UTF-8' ) ) . '</em>';
		$artistes = function_exists( 'er_expo_artistes' ) ? er_expo_artistes( $expo ) : '';
		$fin      = function_exists( 'er_evt_date' ) ? er_evt_date( $expo, 'er_fin' ) : null;
		$texte    = $titre . ( '' !== $artistes ? esc_html( sprintf( /* translators: %s: les artistes */ __( ', de %s', 'editeurs-reunis' ), $artistes ) ) : '' )
			. ( $fin && function_exists( 'er_expo_du_moment_jour' ) ? esc_html( er_ap_insecable( sprintf( /* translators: %s: le dernier jour */ __( ', jusqu’au %s', 'editeurs-reunis' ), er_expo_du_moment_jour( $fin ) ) ) ) : '' );
		$photo      = (int) get_post_thumbnail_id( $expo );
		$reponses[] = array(
			'cle'   => 'expo',
			'texte' => $texte,
			'lien'  => get_permalink( function_exists( 'er_traduction' ) ? er_traduction( $expo ) : $expo ),
			'photo' => er_ap_photo( $photo ),
		);
	}

	// Un classique : Le Maître et Marguerite, dont YMCA-Press a publié la première édition en volume (la couverture de
	// 1967) ; le roman avait d'abord paru, coupé, dans une revue de Moscou.
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
		/* translators: %s: le titre, en italique (« Maître et Marguerite », après « du ») */
		'texte' => sprintf( esc_html__( 'Notre maison d’édition a publié en 1967 la première édition en volume du %s, de Boulgakov', 'editeurs-reunis' ), '<em>' . esc_html__( 'Maître et Marguerite', 'editeurs-reunis' ) . '</em>' ),
		'lien'  => $classique ? get_permalink( $classique ) : er_em_lien( 'ymca-press/catalogue/' ),
		'photo' => er_ap_photo( 'couv-maitre-marguerite.jpg', true ),
	);

	foreach ( $reponses as &$r ) {
		$r['texte'] .= $fleche;
	}
	return $reponses;
}

/**
 * Le module : la phrase, puis le triptyque. Avec une souris, la photo de la phrase suit le pointeur quand il passe
 * sur elle, comme dans les trois maisons ; plus d'image à droite (André, 26 nov.). Les mots que la photo couvre
 * passent dessus, en blanc, à la manière de la Pinault Collection (Y6, 27 nov., à l'essai) : une copie blanche de la
 * phrase, au-dessus de la photo, découpée à sa forme.
 *
 * Les phrases qu'on ne voit pas restent des liens qu'on atteint au clavier : celle qui prend le focus paraît (le script
 * la montre et s'arrête, comme au survol). Rien n'est annoncé quand la phrase change seule.
 */
function er_accueil_phrase() {
	$textes = '';
	$blancs = '';
	$photos = '';
	foreach ( er_ap_reponses() as $i => $r ) {
		$vue     = 0 === $i ? ' est-vue' : '';
		// Les mots dans un span : la photo ne vient que sur eux, pas sur le blanc au bout des lignes.
		$textes .= '<a class="er-ap-reponse' . $vue . '" data-cle="' . esc_attr( $r['cle'] ) . '" href="' . esc_url( $r['lien'] ) . '"><span class="er-ap-mots">' . $r['texte'] . '</span></a>';
		// La même phrase, en blanc, que le script découpe à la forme de la photo (Y6, 27 nov., à l'essai).
		$blancs .= '<span class="er-ap-reponse' . $vue . '" data-cle="' . esc_attr( $r['cle'] ) . '"><span class="er-ap-mots">' . $r['texte'] . '</span></span>';
		if ( $r['photo'] ) {
			$photos .= str_replace( '<img ', '<img data-cle="' . esc_attr( $r['cle'] ) . '" ', $r['photo'] );
		}
	}
	$triptyque = function_exists( 'er_triptyque_html' ) ? er_triptyque_html( 'accueil' ) : '';
	return '<section class="er-ap" aria-label="' . esc_attr__( 'À la une', 'editeurs-reunis' ) . '"><div class="er-ap-corps">'
		. '<div class="er-ap-reponses">' . $textes . '</div></div>'
		. ( '' !== $photos ? '<div class="er-ap-suit" aria-hidden="true">' . $photos . '</div>'
			. '<div class="er-ap-blanc" aria-hidden="true"><div class="er-ap-corps"><div class="er-ap-reponses">' . $blancs . '</div></div></div>' : '' )
		. '</section>'
		. ( $triptyque ? '<div class="er-ap-triptyque">' . $triptyque . '</div>' : '' );
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
