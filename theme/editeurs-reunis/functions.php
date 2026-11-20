<?php
/**
 * Les Éditeurs réunis — thème.
 *
 * La direction artistique est provisoire (celle de la maquette Wix, en Montserrat) : toutes ses valeurs sont dans
 * theme.json (couleurs, police, tailles, espacements) et dans style.css (le reste). La changer, c'est changer ces deux
 * fichiers ; les gabarits et les compositions n'ont aucune couleur ni taille écrite en dur.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'style.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$version = wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'editeurs-reunis', get_stylesheet_uri(), array(), $version );
		wp_enqueue_script(
			'editeurs-reunis-menu-librairie',
			get_theme_file_uri( 'assets/js/menu-librairie.js' ),
			array(),
			$version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_enqueue_script(
			'editeurs-reunis-diaporama',
			get_theme_file_uri( 'assets/js/diaporama.js' ),
			array(),
			$version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
);

add_action(
	'init',
	function () {
		register_block_pattern_category( 'editeurs-reunis', array( 'label' => 'Les Éditeurs réunis' ) );

		register_block_style(
			'core/button',
			array(
				'name'  => 'pilule-blanche',
				'label' => 'Pilule blanche',
			)
		);
		register_block_style(
			'core/button',
			array(
				'name'  => 'fleche',
				'label' => 'Cerné, avec une flèche',
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'carte-verre',
				'label' => 'Carte voilée',
			)
		);
	}
);

/**
 * Dans la librairie (ses pages, un rayon, un livre, un auteur, une langue, les promotions), « Librairie » est allumée
 * dans le menu du haut.
 */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) || is_page( 'promos' ) ) {
			$classes[] = 'er-dans-la-librairie';
		}
		return $classes;
	}
);

/**
 * L'adresse d'une image du thème, pour les compositions.
 *
 * @param string $nom Le nom du fichier dans assets/images.
 */
function er_image( $nom ) {
	return esc_url( get_theme_file_uri( 'assets/images/' . $nom ) );
}

/**
 * L'adresse d'une page du site d'après son chemin. Calculée à chaque affichage : le même thème marche sous n'importe
 * quel nom de domaine, et dans l'aperçu de WordPress Playground.
 *
 * @param string $chemin Par exemple « librairie/ ».
 */
function er_lien( $chemin = '' ) {
	return esc_url( home_url( '/' . ltrim( $chemin, '/' ) ) );
}

/**
 * Le lien d'un rayon de la librairie, s'il existe ; sinon la librairie.
 *
 * @param string $slug Le slug du rayon.
 */
function er_rayon( $slug ) {
	$terme = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $terme && ! is_wp_error( $terme ) ) {
		$lien = get_term_link( $terme );
		if ( ! is_wp_error( $lien ) ) {
			return esc_url( $lien );
		}
	}
	return er_lien( 'librairie/' );
}

/**
 * L'adresse d'un article d'après son slug, ou le journal s'il n'existe pas.
 *
 * @param string $slug Le slug de l'article.
 */
function er_article( $slug ) {
	$article = get_page_by_path( $slug, OBJECT, 'post' );
	return $article ? esc_url( get_permalink( $article ) ) : er_lien( 'journal/' );
}

/**
 * L'adresse d'un livre d'après son slug, ou la librairie s'il n'existe pas.
 *
 * @param string $slug Le slug du livre.
 */
function er_livre( $slug ) {
	$livre = get_page_by_path( $slug, OBJECT, 'product' );
	return $livre ? esc_url( get_permalink( $livre ) ) : er_lien( 'librairie/' );
}

/**
 * La fiche d'un livre dans une grille : la couverture, ses mentions (Nouveauté, Très rare…), le titre, l'auteur, le
 * prix, et « Rupture de stock » quand il n'y en a plus. Une seule définition pour l'accueil, la librairie, les pages
 * d'un auteur ou d'une langue, et « Du même rayon ».
 */
function er_fiche_livre() {
	return '<!-- wp:woocommerce/product-template {"className":"er-livres"} -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","aspectRatio":"2/3","scale":"cover","className":"er-couverture"} /-->
<!-- wp:post-terms {"term":"product_tag","className":"er-mentions"} /-->
<!-- wp:post-title {"level":3,"isLink":true,"className":"er-livre-titre","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:post-terms {"term":"auteur","className":"er-livre-auteur"} /-->
<!-- wp:woocommerce/product-price {"className":"er-livre-prix"} /-->
<!-- wp:woocommerce/product-stock-indicator {"className":"er-livre-stock"} /-->
<!-- /wp:woocommerce/product-template -->';
}

/**
 * Un livre sans prix (épuisé, ou prix à confirmer) n'affiche rien plutôt qu'un prix vide.
 */
add_filter(
	'woocommerce_empty_price_html',
	function () {
		return '';
	}
);

/**
 * Les vignettes de la librairie au format d'une couverture (2:3), sans recadrage carré.
 */
add_filter(
	'woocommerce_get_image_size_thumbnail',
	function () {
		return array(
			'width'  => 420,
			'height' => 630,
			'crop'   => 0,
		);
	}
);
add_filter(
	'woocommerce_get_image_size_single',
	function () {
		return array(
			'width'  => 720,
			'height' => 0,
			'crop'   => 0,
		);
	}
);
