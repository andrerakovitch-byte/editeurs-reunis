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

require get_template_directory() . '/inc/recherche.php';

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'editeurs-reunis', get_template_directory() . '/languages' );
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
		wp_add_inline_script(
			'editeurs-reunis-diaporama',
			'window.erDiaporamaTextes = ' . wp_json_encode(
				array(
					'carrousel'   => __( 'carrousel', 'editeurs-reunis' ),
					'diapositive' => __( 'diapositive', 'editeurs-reunis' ),
					'alaune'      => __( 'À la une', 'editeurs-reunis' ),
					/* translators: 1: le rang de la diapositive, 2: le nombre de diapositives. */
					'position'    => __( '%1$s sur %2$s', 'editeurs-reunis' ),
					/* translators: %s: le rang de la diapositive. */
					'numero'      => __( 'Diapositive %s', 'editeurs-reunis' ),
					'precedente'  => __( 'Diapositive précédente', 'editeurs-reunis' ),
					'suivante'    => __( 'Diapositive suivante', 'editeurs-reunis' ),
				)
			) . ';',
			'before'
		);
	}
);

add_action(
	'init',
	function () {
		register_block_pattern_category( 'editeurs-reunis', array( 'label' => 'Les Éditeurs réunis' ) ); // Le nom de la maison ne se traduit pas.

		register_block_style(
			'core/button',
			array(
				'name'  => 'pilule-blanche',
				'label' => __( 'Pilule blanche', 'editeurs-reunis' ),
			)
		);
		register_block_style(
			'core/button',
			array(
				'name'  => 'fleche',
				'label' => __( 'Cerné, avec une flèche', 'editeurs-reunis' ),
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'carte-verre',
				'label' => __( 'Carte voilée', 'editeurs-reunis' ),
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'ambiance-papier',
				'label' => __( 'Ambiance papier (contenu patrimonial)', 'editeurs-reunis' ),
			)
		);
	}
);

/**
 * La maison de la page sur le corps de la page : er-maison-librairie, er-maison-ymca ou er-maison-centre (rien sur
 * l'accueil et les pages communes). L'en-tête en tire la maison soulignée et la couleur du menu du dessous.
 */
add_filter(
	'body_class',
	function ( $classes ) {
		$maison = er_maison();
		if ( $maison ) {
			$classes[] = 'er-maison-' . $maison;
		}
		if ( 'librairie' === $maison ) {
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
 * Une chaîne pour les attributs d'un bloc (le JSON de son commentaire) : guillemets et barres échappés, pour qu'une
 * traduction ne casse jamais la composition.
 *
 * @param string $texte Le texte, déjà traduit.
 */
function er_attr( $texte ) {
	return substr( wp_json_encode( (string) $texte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ), 1, -1 );
}

/* Les liens, dans la langue de la page -------------------------------------------------------------------------
 *
 * Les compositions désignent les pages, les livres et les rayons par leur chemin ou leur slug en français, la langue
 * de référence. Avec Polylang, ces fonctions rendent l'adresse de la traduction dans la langue de la page quand elle
 * existe, sinon celle de l'original ; sans Polylang, l'adresse française.
 */

/**
 * La traduction d'un contenu (page, article, livre) dans la langue de la page, ou le contenu lui-même.
 *
 * @param int $id L'identifiant du contenu en français.
 */
function er_traduction( $id ) {
	if ( function_exists( 'pll_get_post' ) ) {
		$traduit = pll_get_post( $id );
		if ( $traduit ) {
			return (int) $traduit;
		}
	}
	return (int) $id;
}

/**
 * La traduction d'un terme (rayon, mention, auteur) dans la langue de la page, ou le terme lui-même.
 *
 * @param WP_Term $terme Le terme en français.
 */
function er_traduction_terme( $terme ) {
	if ( function_exists( 'pll_get_term' ) ) {
		$traduit = pll_get_term( $terme->term_id );
		if ( $traduit && (int) $traduit !== (int) $terme->term_id ) {
			$autre = get_term( $traduit, $terme->taxonomy );
			if ( $autre && ! is_wp_error( $autre ) ) {
				return $autre;
			}
		}
	}
	return $terme;
}

/**
 * Le slug français d'un terme, quelle que soit la langue de la page : c'est lui que le thème connaît.
 *
 * @param WP_Term $terme Un terme, dans n'importe quelle langue.
 */
function er_slug_reference( $terme ) {
	if ( function_exists( 'pll_get_term' ) && function_exists( 'pll_default_language' ) ) {
		$original = pll_get_term( $terme->term_id, pll_default_language() );
		if ( $original && (int) $original !== (int) $terme->term_id ) {
			$autre = get_term( $original, $terme->taxonomy );
			if ( $autre && ! is_wp_error( $autre ) ) {
				return $autre->slug;
			}
		}
	}
	return $terme->slug;
}

/**
 * Le slug français de la page affichée, quelle que soit sa langue ; '' hors d'une page.
 */
function er_page_reference() {
	if ( ! is_page() ) {
		return '';
	}
	$id = get_queried_object_id();
	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) ) {
		$original = pll_get_post( $id, pll_default_language() );
		if ( $original ) {
			$id = $original;
		}
	}
	return (string) get_post_field( 'post_name', $id );
}

/**
 * Le chemin français de la page affichée (« ymca-press/histoire »), quelle que soit sa langue ; '' hors d'une page.
 */
function er_chemin_reference() {
	if ( ! is_page() ) {
		return '';
	}
	$id = get_queried_object_id();
	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) ) {
		$original = pll_get_post( $id, pll_default_language() );
		if ( $original ) {
			$id = $original;
		}
	}
	return trim( (string) get_page_uri( $id ), '/' );
}

/* Les trois maisons ---------------------------------------------------------------------------------------------
 *
 * La librairie, la maison d'édition YMCA-Press et le centre culturel. Chaque page appartient à l'une d'elles, ou à
 * aucune (l'accueil, le panier, le compte, les pages légales, la recherche) ; l'en-tête en tire sa couleur.
 */

/**
 * La maison de la page affichée : 'librairie', 'ymca', 'centre', ou ''.
 */
function er_maison() {
	if ( ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) || 'promos' === er_page_reference() ) {
		return 'librairie';
	}
	if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() ) {
		return 'centre'; // Le journal du centre.
	}
	$racine = strtok( er_chemin_reference(), '/' );
	if ( 'ymca-press' === $racine ) {
		return 'ymca';
	}
	if ( in_array( $racine, array( 'centre-culturel', 'journal', 'nous-trouver' ), true ) ) {
		return 'centre';
	}
	return '';
}

/**
 * Les pages d'YMCA-Press et du centre culturel, par leur chemin français : le menu du dessous à l'ordinateur, le menu
 * (≡) au téléphone. Les rayons de la librairie viennent, eux, des catégories de WooCommerce.
 */
function er_pages_des_maisons() {
	return array(
		'ymca'   => array(
			'ymca-press/'                 => _x( 'La maison d’édition', 'menu d’YMCA-Press', 'editeurs-reunis' ),
			'ymca-press/histoire/'        => _x( 'Un siècle d’histoire', 'menu d’YMCA-Press', 'editeurs-reunis' ),
			'ymca-press/catalogue/'       => _x( 'Le catalogue', 'menu d’YMCA-Press', 'editeurs-reunis' ),
			'ymca-press/amis-ymca-press/' => _x( 'Les Amis d’YMCA-Press', 'menu d’YMCA-Press', 'editeurs-reunis' ),
		),
		'centre' => array(
			'centre-culturel/'             => _x( 'Le centre', 'menu du centre culturel', 'editeurs-reunis' ),
			'centre-culturel/evenements/'  => _x( 'Nos événements', 'menu du centre culturel', 'editeurs-reunis' ),
			'centre-culturel/expositions/' => _x( 'Nos expositions', 'menu du centre culturel', 'editeurs-reunis' ),
			'centre-culturel/podcasts/'    => _x( 'Nos podcasts', 'menu du centre culturel', 'editeurs-reunis' ),
			'centre-culturel/videos/'      => _x( 'Nos vidéos', 'menu du centre culturel', 'editeurs-reunis' ),
			'journal/'                     => _x( 'Le journal du centre', 'menu du centre culturel', 'editeurs-reunis' ),
			'nous-trouver/'                => _x( 'Nous trouver', 'menu du centre culturel', 'editeurs-reunis' ),
		),
	);
}

/**
 * Le menu du dessous, sur la couleur de la maison, pour YMCA-Press et le centre culturel (la librairie a le sien,
 * avec ses rayons et leurs tiroirs). La page où l'on est est soulignée ; un article du journal souligne le journal.
 */
function er_sous_menu() {
	$maison = er_maison();
	$pages  = er_pages_des_maisons();
	if ( ! isset( $pages[ $maison ] ) ) {
		return '';
	}
	$ici = er_chemin_reference();
	if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() ) {
		$ici = 'journal';
	}
	$noms  = array(
		'ymca'   => _x( 'Maison d’édition YMCA-Press', 'menu du haut', 'editeurs-reunis' ),
		'centre' => _x( 'Le centre culturel', 'menu du haut', 'editeurs-reunis' ),
	);
	$liens = '';
	foreach ( $pages[ $maison ] as $chemin => $libelle ) {
		$courant = trim( $chemin, '/' ) === $ici ? ' aria-current="page"' : '';
		$liens  .= '<li><a href="' . er_lien( $chemin ) . '"' . $courant . '>' . esc_html( $libelle ) . '</a></li>';
	}
	return '<nav class="er-sous-menu" aria-label="' . esc_attr( $noms[ $maison ] ) . '"><ul>' . $liens . '</ul></nav>';
}

/**
 * L'adresse d'une page du site d'après son chemin français. Calculée à chaque affichage : le même thème marche sous
 * n'importe quel nom de domaine, dans l'aperçu de WordPress Playground, et dans chaque langue.
 *
 * @param string $chemin Par exemple « librairie/ » ou « ymca-press/histoire/ ».
 */
function er_lien( $chemin = '' ) {
	$chemin = trim( $chemin, '/' );
	if ( '' === $chemin ) {
		return esc_url( function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ) );
	}
	$page = get_page_by_path( $chemin );
	if ( $page ) {
		return esc_url( get_permalink( er_traduction( $page->ID ) ) );
	}
	return esc_url( home_url( '/' . $chemin . '/' ) );
}

/**
 * Le lien d'un rayon de la librairie, s'il existe ; sinon la librairie.
 *
 * @param string $slug Le slug français du rayon.
 */
function er_rayon( $slug ) {
	$terme = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $terme && ! is_wp_error( $terme ) ) {
		$lien = get_term_link( er_traduction_terme( $terme ) );
		if ( ! is_wp_error( $lien ) ) {
			return esc_url( $lien );
		}
	}
	return er_lien( 'librairie/' );
}

/**
 * Le lien d'une mention (Nouveauté, Occasion, Très rare…), s'il existe ; sinon son adresse d'origine.
 *
 * @param string $slug Le slug français de la mention.
 */
function er_mention( $slug ) {
	$terme = get_term_by( 'slug', $slug, 'product_tag' );
	if ( $terme && ! is_wp_error( $terme ) ) {
		$lien = get_term_link( er_traduction_terme( $terme ) );
		if ( ! is_wp_error( $lien ) ) {
			return esc_url( $lien );
		}
	}
	return er_lien( 'mention/' . $slug . '/' );
}

/**
 * L'adresse d'un article d'après son slug français, ou le journal s'il n'existe pas.
 *
 * @param string $slug Le slug de l'article.
 */
function er_article( $slug ) {
	$article = get_page_by_path( $slug, OBJECT, 'post' );
	return $article ? esc_url( get_permalink( er_traduction( $article->ID ) ) ) : er_lien( 'journal/' );
}

/**
 * L'adresse d'un livre d'après son slug français, ou la librairie s'il n'existe pas.
 *
 * @param string $slug Le slug du livre.
 */
function er_livre( $slug ) {
	$livre = get_page_by_path( $slug, OBJECT, 'product' );
	return $livre ? esc_url( get_permalink( er_traduction( $livre->ID ) ) ) : er_lien( 'librairie/' );
}

/**
 * Le titre d'un groupe de sous-rayons dans le tiroir du menu (la méta « er_groupe », écrite en français), traduit.
 *
 * @param string $groupe Le titre en français.
 */
function er_groupe_libelle( $groupe ) {
	$libelles = array(
		'Par genre'   => __( 'Par genre', 'editeurs-reunis' ),
		'Par époque'  => __( 'Par époque', 'editeurs-reunis' ),
		'Par période' => __( 'Par période', 'editeurs-reunis' ),
		'Par thème'   => __( 'Par thème', 'editeurs-reunis' ),
		'Par âge'     => __( 'Par âge', 'editeurs-reunis' ),
		'Méthodes'    => __( 'Méthodes', 'editeurs-reunis' ),
		'Outils'      => __( 'Outils', 'editeurs-reunis' ),
		'Pensée'      => __( 'Pensée', 'editeurs-reunis' ),
		'Religion'    => __( 'Religion', 'editeurs-reunis' ),
		'Arts'        => __( 'Arts', 'editeurs-reunis' ),
	);
	return $libelles[ $groupe ] ?? $groupe;
}

/**
 * Le sélecteur de langue de la barre noire (l'extension de la librairie le fournit).
 */
add_shortcode(
	'er_langues_entete',
	function () {
		return function_exists( 'er_selecteur_langues' ) ? er_selecteur_langues() : '';
	}
);
add_shortcode( 'er_sous_menu', 'er_sous_menu' );

// Un code court dans un gabarit n'est pas déplié quand il vient d'une composition (les compositions passent après) :
// on déplie les nôtres dans leur bloc HTML.
add_filter(
	'render_block_core/html',
	function ( $html ) {
		return ( false !== strpos( $html, '[er_langues_entete]' ) || false !== strpos( $html, '[er_sous_menu]' ) ) ? do_shortcode( $html ) : $html;
	}
);

/**
 * WooCommerce accroche de lui-même un compte et un panier après le menu de l'en-tête : l'en-tête a déjà les siens.
 */
add_filter(
	'hooked_block_types',
	function ( $blocs, $position, $ancre ) {
		if ( 'core/navigation' === $ancre ) {
			$blocs = array_values( array_diff( $blocs, array( 'woocommerce/customer-account', 'woocommerce/mini-cart' ) ) );
		}
		return $blocs;
	},
	20,
	3
);

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
