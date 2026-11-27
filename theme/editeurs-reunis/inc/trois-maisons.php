<?php
/**
 * Les trois maisons, en trois paragraphes : le module texte de l'accueil, après le journal.
 *
 * Retenu le 26 novembre 2026 (planche « T · Trois paragraphes » du canevas « Une nouvelle page d'accueil, aérée »),
 * dans l'esprit de la Fondation Cartier : un filet fin, puis trois paragraphes en grand, le centre culturel, la
 * librairie, la maison d'édition. Les noms sont des liens, soulignés ; au survol d'un lien, le reste passe en gris et
 * la photo du lien apparaît près de la souris, grande et dans son format d'origine (assets/js/une-trois-maisons.js).
 * Sans souris (téléphone, tablette), ni photo ni gris : le texte et ses liens.
 *
 * Les photos sont provisoires : un photographe fera un jeu de photos originales pour la mise en ligne. Pour en changer
 * une, il suffit de verser dans la médiathèque une image nommée « accueil-centre », « accueil-rencontres »,
 * « accueil-expositions », « accueil-librairie », « accueil-ymca-press » ou « accueil-archipel » (le nom du fichier,
 * avant l'extension) : elle remplace la photo d'attente du lien.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * La photo d'un lien : l'image « accueil-… » de la médiathèque, sinon la photo d'attente. Rend [ html, largeur, hauteur ]
 * ou null.
 *
 * @param string $cle centre, rencontres, expositions, librairie, ymca-press, archipel.
 */
function er_trois_maisons_photo( $cle ) {
	$attrs = array(
		'class'    => 'er-tm-photo',
		'alt'      => '',
		'loading'  => 'lazy',
		'decoding' => 'async',
		'sizes'    => '560px',
	);
	$image = get_page_by_path( 'accueil-' . $cle, OBJECT, 'attachment' );
	$id    = $image ? (int) $image->ID : 0;
	if ( ! $id ) {
		// Les photos d'attente : celles du thème, du journal, de l'exposition du moment, du livre.
		$article = function ( $chemin ) {
			$p = get_page_by_path( $chemin, OBJECT, 'post' );
			return $p ? (int) get_post_thumbnail_id( $p ) : 0;
		};
		switch ( $cle ) {
			case 'centre':
				return er_trois_maisons_photo_theme( 'branche-ymca-press.jpg', $cle );
			case 'ymca-press':
				return er_trois_maisons_photo_theme( 'une-imprimerie.jpg', $cle );
			case 'librairie':
				$id = $article( 'pour-le-10e-anniversaire-du-deces-de-nikita-struve-editeur-slavisant-professeur' );
				break;
			case 'rencontres':
				$id = $article( 'ignat-soljenitsyne-discours-pour-l-inauguration-de-la-plaque-commemorant-la-publication-le-28-dec' );
				break;
			case 'expositions':
				$expo = function_exists( 'er_expo_du_moment' ) ? er_expo_du_moment() : 0;
				$id   = $expo ? (int) get_post_thumbnail_id( $expo ) : 0;
				break;
			case 'archipel':
				$livre = er_trois_maisons_archipel();
				$id    = $livre ? (int) get_post_thumbnail_id( $livre ) : 0;
				break;
		}
	}
	if ( ! $id ) {
		return null;
	}
	$src = wp_get_attachment_image_src( $id, 'large' );
	if ( ! $src || ! $src[1] || ! $src[2] ) {
		return null;
	}
	return array( wp_get_attachment_image( $id, 'large', false, $attrs + array( 'data-cle' => $cle ) ), (int) $src[1], (int) $src[2] );
}

/**
 * Une photo d'attente prise dans le thème (assets/images).
 *
 * @param string $nom Le fichier.
 * @param string $cle Le lien.
 */
function er_trois_maisons_photo_theme( $nom, $cle ) {
	$chemin = get_theme_file_path( 'assets/images/' . $nom );
	$taille = is_readable( $chemin ) ? wp_getimagesize( $chemin ) : false;
	if ( ! $taille ) {
		return null;
	}
	return array(
		'<img class="er-tm-photo" data-cle="' . esc_attr( $cle ) . '" src="' . er_image( $nom ) . '" width="' . (int) $taille[0] . '" height="' . (int) $taille[1] . '" alt="" loading="lazy" decoding="async">',
		(int) $taille[0],
		(int) $taille[1],
	);
}

/**
 * Le livre de « L'Archipel du Goulag » : l'édition russe d'YMCA-Press au catalogue (« Архипелаг ГУЛаг »).
 */
function er_trois_maisons_archipel() {
	$livre = get_page_by_path( 'архипелаг-гулаг', OBJECT, 'product' );
	if ( ! $livre ) {
		$q     = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'title'          => 'Архипелаг ГУЛаг',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			)
		);
		$livre = $q->posts ? get_post( $q->posts[0] ) : null;
	}
	return $livre && 'publish' === $livre->post_status ? (int) $livre->ID : 0;
}

/**
 * Le module.
 */
function er_trois_maisons_html() {
	$photos = '';
	$lien   = function ( $cle, $url, $texte ) use ( &$photos ) {
		$photo = er_trois_maisons_photo( $cle );
		if ( $photo ) {
			$photos .= $photo[0];
		}
		return '<a href="' . esc_url( $url ) . '"' . ( $photo ? ' data-photo="' . esc_attr( $cle ) . '"' : '' ) . '>' . $texte . '</a>';
	};

	$archipel = er_trois_maisons_archipel();

	$paragraphes = array(
		sprintf(
			/* translators: 1: « Centre culturel Alexandre Soljenitsyne », en lien, 2: « rencontres », en lien, 3: « expositions », en lien */
			esc_html__( 'Le %1$s œuvre pour la liberté de création littéraire et préserve la mémoire de la dissidence. Il accueille des %2$s avec des auteurs et des %3$s.', 'editeurs-reunis' ),
			$lien( 'centre', er_lien( 'centre-culturel/' ), esc_html__( 'Centre culturel Alexandre Soljenitsyne', 'editeurs-reunis' ) ),
			$lien( 'rencontres', er_lien( 'centre-culturel/evenements/' ), esc_html__( 'rencontres', 'editeurs-reunis' ) ),
			$lien( 'expositions', er_lien( 'centre-culturel/expositions/' ), esc_html__( 'expositions', 'editeurs-reunis' ) )
		),
		sprintf(
			/* translators: %s: « Les Éditeurs réunis », en lien */
			esc_html__( '%s, la plus ancienne librairie russe de Paris, proposent au cœur du Quartier latin des livres neufs et d’occasion, en russe et en français.', 'editeurs-reunis' ),
			$lien( 'librairie', er_lien( 'librairie/' ), esc_html__( 'Les Éditeurs réunis', 'editeurs-reunis' ) )
		),
		sprintf(
			/* translators: 1: « YMCA-Press », en lien, 2: « L'Archipel du Goulag », en italique et en lien */
			esc_html__( 'La maison d’édition %1$s, centenaire, a publié cinq prix Nobel ; c’est elle qui a fait paraître %2$s, en 1973.', 'editeurs-reunis' ),
			$lien( 'ymca-press', er_lien( 'ymca-press/' ), 'YMCA-Press' ),
			$archipel
				? $lien( 'archipel', get_permalink( $archipel ), '<em>' . esc_html__( 'L’Archipel du Goulag', 'editeurs-reunis' ) . '</em>' )
				: '<em>' . esc_html__( 'L’Archipel du Goulag', 'editeurs-reunis' ) . '</em>'
		),
	);

	// Les espaces insécables : avant « ; » (une insécable entière : Montserrat n'a pas d'espace fine), entre « prix » et
	// « Nobel ».
	$html = '';
	foreach ( $paragraphes as $p ) {
		$html .= '<p>' . str_replace( array( ' ;', 'prix Nobel' ), array( "\u{00A0};", "prix\u{00A0}Nobel" ), $p ) . '</p>';
	}

	// La copie pour l'effet de la Pinault Collection (27 nov.) : le même texte, au-dessus des photos, découpé à la
	// forme de celle qu'on voit ; tout le texte qu'elle couvre y paraît en blanc, le lien visé souligné. Ses liens
	// deviennent des span (rien à atteindre au clavier, rien d'annoncé).
	$copie = preg_replace( array( '#<a href="[^"]*"( data-photo="[^"]*")?>#', '#</a>#' ), array( '<span class="er-tm-lien"$1>', '</span>' ), $html );

	return '<section class="er-trois-maisons" aria-label="' . esc_attr__( 'Les trois maisons', 'editeurs-reunis' ) . '">'
		. '<div class="er-tm-corps">' . $html . '</div>'
		. ( '' !== $photos ? '<div class="er-tm-photos" aria-hidden="true">' . $photos . '</div>'
			. '<div class="er-tm-blanc" aria-hidden="true"><div class="er-tm-corps">' . $copie . '</div></div>' : '' )
		. '</section>';
}

// En code court : le gabarit de l'accueil l'appelle dans un bloc HTML (déplié par le filtre de render_block_core/html,
// functions.php).
add_shortcode( 'er_trois_maisons', 'er_trois_maisons_html' );
