<?php
/**
 * La page type d'une page de texte qui informe (28 nov. 2026, d'après « La Fondation » de la Fondation Cartier et les
 * pages thématiques de la National Academies) : la tête du gabarit « Page de texte » (le titre de la page en grand,
 * calé en bas d'un blanc de la taille d'un menu ouvert), le triptyque, un chapô en grand, puis des
 * modules qui racontent dans l'ordre où on les lit,
 * chacun réarrangeable dans l'éditeur (patterns/pt-*.php, catégorie « Les modules d'une page ») : le texte en colonne,
 * une section sur deux colonnes, les photos (triptyque, diptyque, photo en marge,
 * frise), le rendez-vous en aplat (la seule rupture de couleur), les livres, le journal, « Pour aller plus loin ».
 *
 * Les photos d'ambiance sont celles de la Comédie-Française, dans la médiathèque de la bêta (jamais dans le dépôt) :
 * elles donnent un climat, pas une illustration. Le texte de remplissage est du faux texte, en attendant le vrai.
 *
 * Ici : les photos (er_pt_photo), les trois modules qui lisent le site ([er_pt_rendez_vous], [er_pt_livres],
 * [er_pt_journal]) et la page « Page type » elle-même, créée et tenue à jour tant qu'on la travaille
 * (ER_PAGE_TYPE_VERSION). Le dessin : assets/css/32-page-type.css.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * La version du contenu de la page « Page type » : on l'augmente pour la récrire d'après les patterns, tant que la page
 * est un essai. Le jour où l'on y travaille dans l'éditeur, on cesse de l'augmenter.
 */
const ER_PAGE_TYPE_VERSION = 4;

/**
 * Une photo de la Comédie-Française, en bloc image (dans la médiathèque : er-cf-<nom>.jpg).
 *
 * @param string $nom     bleu-nuit, ciel-pale, creme, fenetre, or-brun, orange ou visage.
 * @param string $legende La légende, s'il y en a une.
 */
function er_pt_photo( $nom, $legende = '' ) {
	$url = content_url( 'uploads/2026/11/er-cf-' . sanitize_key( $nom ) . '.jpg' );
	$id  = function_exists( 'attachment_url_to_postid' ) ? (int) attachment_url_to_postid( $url ) : 0;
	$att = array(
		'sizeSlug'        => 'full',
		'linkDestination' => 'none',
	);
	if ( $id ) {
		$att = array( 'id' => $id ) + $att;
	}
	return '<!-- wp:image ' . wp_json_encode( $att ) . " -->\n"
		. '<figure class="wp-block-image size-full"><img src="' . esc_url( $url ) . '" alt=""' . ( $id ? ' class="wp-image-' . $id . '"' : '' ) . '/>'
		. ( '' !== $legende ? '<figcaption class="wp-element-caption">' . esc_html( $legende ) . '</figcaption>' : '' )
		. "</figure>\n<!-- /wp:image -->\n";
}

/**
 * Le rendez-vous en aplat : la prochaine rencontre, à défaut l'exposition du moment. Rien s'il n'y a ni l'une ni
 * l'autre (le module disparaît, 32-page-type.css).
 */
function er_pt_rendez_vous() {
	$maintenant = ( new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Paris' ) ) )->modify( '-2 hours' )->format( 'Y-m-d H:i' );
	$q          = new WP_Query(
		array(
			'post_type'      => 'er_evenement',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_key'       => 'er_debut', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $maintenant, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_compare'   => '>=',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		)
	);
	$id     = $q->posts ? (int) $q->posts[0]->ID : 0;
	$bouton = $id && function_exists( 'er_evt_billet' ) && er_evt_billet( $id ) ? __( 'Réserver ma place', 'editeurs-reunis' ) : __( 'Voir la rencontre', 'editeurs-reunis' );
	if ( ! $id && function_exists( 'er_expo_du_moment' ) ) {
		$id     = er_expo_du_moment();
		$bouton = __( 'Voir l’exposition', 'editeurs-reunis' );
	}
	if ( ! $id ) {
		return '';
	}
	$quand = function_exists( 'er_evt_quand' ) ? er_evt_quand( $id ) : '';
	$titre = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	$image = get_post_thumbnail_id( $id ) ? wp_get_attachment_image( get_post_thumbnail_id( $id ), 'large', false, array( 'loading' => 'lazy', 'sizes' => '(max-width: 781px) 100vw, 40vw' ) ) : '';
	return '<div class="er-pt-rdv-corps"><div class="er-pt-rdv-texte">'
		. ( $quand ? '<p class="er-pt-rdv-quand">' . esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $quand, 0, 1 ) ) . mb_substr( $quand, 1 ) : $quand ) . '</p>' : '' )
		. '<h3 class="er-pt-rdv-titre"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( $titre ) . '</a></h3>'
		. '<p class="er-pt-rdv-action"><a class="er-pt-pilule" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( $bouton ) . '</a></p></div>'
		. ( $image ? '<figure class="er-pt-rdv-image">' . $image . '</figure>' : '' ) . '</div>';
}
add_shortcode( 'er_pt_rendez_vous', 'er_pt_rendez_vous' );

/**
 * Les livres, dans le dessin de la recherche (principe 13) : six cadres, deux par colonne du triptyque.
 *
 * @param array $att ids (des identifiants), auteur ou rayon (un identifiant de terme), nombre (6).
 */
function er_pt_livres( $att = array() ) {
	if ( ! function_exists( 'wc_get_products' ) || ! function_exists( 'er_recherche_vue' ) ) {
		return '';
	}
	$att  = shortcode_atts(
		array(
			'ids'    => '',
			'auteur' => '',
			'rayon'  => '',
			'nombre' => 6,
		),
		$att,
		'er_pt_livres'
	);
	$args = array(
		'status'     => 'publish',
		'limit'      => max( 1, min( 12, (int) $att['nombre'] ) ),
		'visibility' => 'catalog',
		'orderby'    => 'date',
		'order'      => 'DESC',
	);
	if ( '' !== $att['ids'] ) {
		$args['include'] = array_map( 'absint', explode( ',', $att['ids'] ) );
		$args['orderby'] = 'post__in';
	} elseif ( '' !== $att['auteur'] || '' !== $att['rayon'] ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => '' !== $att['auteur'] ? 'auteur' : 'product_cat',
				'field'    => is_numeric( '' !== $att['auteur'] ? $att['auteur'] : $att['rayon'] ) ? 'term_id' : 'slug',
				'terms'    => '' !== $att['auteur'] ? $att['auteur'] : $att['rayon'],
			),
		);
	} else {
		$args['stock_status'] = 'instock';
	}
	$html = '';
	foreach ( wc_get_products( $args ) as $livre ) {
		$html .= '<div class="er-pt-livre">' . er_recherche_vue( $livre ) . '</div>'; // er_recherche_vue() échappe.
	}
	return $html ? '<div class="er-pt-livres-grille">' . $html . '</div>' : '';
}
add_shortcode( 'er_pt_livres', 'er_pt_livres' );

/**
 * Les derniers articles du journal, en lignes : la date, le genre, le titre (la liste des pages de la National
 * Academies).
 *
 * @param array $att nombre (3), categorie (un identifiant ou un slug).
 */
function er_pt_journal( $att = array() ) {
	$att      = shortcode_atts(
		array(
			'nombre'    => 3,
			'categorie' => '',
		),
		$att,
		'er_pt_journal'
	);
	$articles = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'numberposts'      => max( 1, min( 10, (int) $att['nombre'] ) ),
			'category_name'    => is_numeric( $att['categorie'] ) ? '' : sanitize_title( $att['categorie'] ),
			'cat'              => is_numeric( $att['categorie'] ) ? (int) $att['categorie'] : 0,
			'suppress_filters' => false,
		)
	);
	$html     = '';
	foreach ( $articles as $p ) {
		$genres = array_filter( get_the_category( $p->ID ), fn( $c ) => 'non-classe' !== $c->slug && 'uncategorized' !== $c->slug );
		$genre  = $genres ? reset( $genres )->name : '';
		$titre  = html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' );
		$html  .= '<li><a class="er-pt-ligne" href="' . esc_url( get_permalink( $p ) ) . '">'
			. '<span class="er-pt-ligne-date">' . esc_html( wp_date( 'j M Y', get_post_timestamp( $p ) ) ) . '</span>'
			. '<span class="er-pt-ligne-genre">' . esc_html( $genre ) . '</span>'
			. '<span class="er-pt-ligne-titre"' . ( preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '' ) . '>' . esc_html( $titre ) . '</span>'
			. '<span class="er-pt-ligne-fleche" aria-hidden="true">→</span></a></li>';
	}
	return $html ? '<ul class="er-pt-lignes">' . $html . '</ul>' : '';
}
add_shortcode( 'er_pt_journal', 'er_pt_journal' );

/**
 * Le contenu de la page type : le pattern patterns/page-type.php, rendu.
 */
function er_page_type_contenu() {
	ob_start();
	include get_template_directory() . '/patterns/page-type.php';
	return (string) ob_get_clean();
}

// La page « Page type » (/page-type/), dans le gabarit « Page de texte » : créée au premier passage,
// récrite quand ER_PAGE_TYPE_VERSION augmente, sauf si quelqu'un l'a retouchée dans l'éditeur depuis (son contenu ne
// répond plus à l'empreinte de la dernière écriture) : la page de l'équipe passe avant. Hors des moteurs, comme toute
// la bêta.
add_action(
	'init',
	function () {
		if ( (int) get_option( 'er_page_type_version' ) >= ER_PAGE_TYPE_VERSION || wp_installing() ) {
			return;
		}
		if ( (int) get_option( 'er_page_type_verrou' ) < time() - 60 ) {
			delete_option( 'er_page_type_verrou' );
		}
		if ( ! add_option( 'er_page_type_verrou', time(), '', false ) ) {
			return;
		}
		$page      = get_page_by_path( 'page-type' );
		$empreinte = (string) get_option( 'er_page_type_empreinte' );
		if ( $page && '' !== $empreinte && md5( $page->post_content ) !== $empreinte ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-texte' );
			update_option( 'er_page_type_version', ER_PAGE_TYPE_VERSION, false );
			delete_option( 'er_page_type_verrou' );
			return;
		}
		$donnees = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => 'page-type',
			'post_title'   => 'Page type',
			'post_content' => er_page_type_contenu(),
		);
		$id      = $page ? wp_update_post( array( 'ID' => $page->ID ) + $donnees, true ) : wp_insert_post( $donnees, true );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', 'page-texte' );
			if ( ! $page && function_exists( 'pll_set_post_language' ) && function_exists( 'pll_default_language' ) ) {
				pll_set_post_language( $id, pll_default_language() );
			}
			update_option( 'er_page_type_version', ER_PAGE_TYPE_VERSION, false );
			update_option( 'er_page_type_empreinte', md5( (string) get_post_field( 'post_content', $id, 'raw' ) ), false );
		}
		delete_option( 'er_page_type_verrou' );
	},
	40
);
