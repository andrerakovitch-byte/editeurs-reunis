<?php
/**
 * Plugin Name: Les Éditeurs réunis — librairie
 * Description: Les données de la librairie : auteurs et langues des livres, les rencontres du centre et leur billetterie, et des traductions de secours pour WooCommerce tant que son paquet de langue français n'est pas installé. Séparée du thème : la direction artistique peut changer sans toucher aux livres.
 * Version: 0.6.6
 * Requires Plugins: woocommerce
 * Text Domain: er-librairie
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		load_plugin_textdomain( 'er-librairie', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	},
	1
);

require __DIR__ . '/inc/recherche.php';
require __DIR__ . '/inc/algolia.php';
require __DIR__ . '/inc/import.php';
require __DIR__ . '/inc/fiche.php';
require __DIR__ . '/inc/epoques.php';
require __DIR__ . '/inc/facettes.php';
require __DIR__ . '/inc/rayonnage.php';
require __DIR__ . '/inc/couvertures.php';
require __DIR__ . '/inc/catalogue.php';
require __DIR__ . '/inc/librairie.php';
require __DIR__ . '/inc/notices.php';
require __DIR__ . '/inc/recommandations.php';
require __DIR__ . '/inc/phrases.php';
require __DIR__ . '/inc/traducteurs-editeurs.php';
require __DIR__ . '/inc/prix-du-livre.php';
require __DIR__ . '/inc/bascule.php';
require __DIR__ . '/inc/donnees-structurees.php';
require __DIR__ . '/inc/liens-livres.php';
require __DIR__ . '/inc/reglages.php';
require __DIR__ . '/inc/langues.php';
require __DIR__ . '/inc/evenements.php';
require __DIR__ . '/inc/medias.php';
require __DIR__ . '/inc/sujets.php';
require __DIR__ . '/inc/auteurs.php';
require __DIR__ . '/inc/journal.php';
require __DIR__ . '/inc/journal-une.php';
require __DIR__ . '/inc/expositions.php';
require __DIR__ . '/inc/collections.php';
require __DIR__ . '/inc/personnes.php';
require __DIR__ . '/inc/audit.php';

/* Les langues --------------------------------------------------------------------------------------------------
 *
 * Le site est en trois langues : le français, le russe et l'anglais. Polylang (avec son extension pour WooCommerce)
 * fait le vrai travail : les adresses /ru/ et /en/, les traductions des pages, des livres et des rayons, les balises
 * hreflang. Tant qu'il n'est pas installé (en local, dans l'aperçu de WordPress Playground), « ?lang=ru » ou
 * « ?lang=en » change la langue de l'interface pour la visite, ce qui suffit à relire les traductions du thème.
 */

/**
 * Les trois langues, dans l'ordre du sélecteur, avec leur locale WordPress.
 */
function er_langues_du_site() {
	return array(
		'fr' => array(
			'locale' => 'fr_FR',
			'nom'    => 'Français',
		),
		'ru' => array(
			'locale' => 'ru_RU',
			'nom'    => 'Русский',
		),
		'en' => array(
			'locale' => 'en_GB',
			'nom'    => 'English',
		),
	);
}

/**
 * Polylang est-il là ?
 */
function er_polylang() {
	return defined( 'POLYLANG_VERSION' ) && function_exists( 'pll_current_language' );
}

/**
 * La langue de l'aperçu, sans Polylang : « ?lang= », sinon celle gardée pour la visite, sinon le français.
 */
function er_langue_apercu() {
	static $langue = null;
	if ( null === $langue ) {
		$langues = er_langues_du_site();
		$demande = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $demande && isset( $_COOKIE['er_lang'] ) ) {
			$demande = sanitize_key( wp_unslash( $_COOKIE['er_lang'] ) );
		}
		$langue = isset( $langues[ $demande ] ) ? $demande : 'fr';
	}
	return $langue;
}

/**
 * La langue de la page : fr, ru ou en.
 */
function er_langue() {
	if ( er_polylang() ) {
		$langue = pll_current_language( 'slug' );
		if ( $langue ) {
			return $langue;
		}
	}
	return er_langue_apercu();
}

// Sans Polylang, la langue de l'aperçu donne sa locale aux pages du site (jamais à l'administration).
foreach ( array( 'locale', 'determine_locale' ) as $er_filtre ) {
	add_filter(
		$er_filtre,
		function ( $locale ) {
			if ( er_polylang() || is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI )
				|| ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ) {
				return $locale;
			}
			$langues = er_langues_du_site();
			return $langues[ er_langue_apercu() ]['locale'];
		}
	);
}

add_action(
	'init',
	function () {
		if ( er_polylang() || ! isset( $_GET['lang'] ) || headers_sent() ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		setcookie( 'er_lang', er_langue_apercu(), 0, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
);

/**
 * Les liens du sélecteur : pour chaque langue, son code, son nom, l'adresse de la page dans cette langue (l'accueil de
 * la langue quand la page n'est pas traduite), et si c'est la langue de la page.
 */
function er_liens_langues() {
	$liens = array();
	if ( er_polylang() && function_exists( 'pll_the_languages' ) ) {
		$langues = pll_the_languages(
			array(
				'raw'                    => 1,
				'hide_if_empty'          => 0,
				'hide_if_no_translation' => 0,
			)
		);
		foreach ( (array) $langues as $l ) {
			$liens[] = array(
				'code'     => $l['slug'],
				'nom'      => $l['name'],
				'url'      => $l['url'],
				'actuelle' => ! empty( $l['current_lang'] ),
				'traduite' => empty( $l['no_translation'] ),
			);
		}
		return $liens;
	}
	$ici = er_langue_apercu();
	foreach ( er_langues_du_site() as $code => $l ) {
		$liens[] = array(
			'code'     => $code,
			'nom'      => $l['nom'],
			'url'      => add_query_arg( 'lang', $code ),
			'actuelle' => $code === $ici,
			'traduite' => true,
		);
	}
	return $liens;
}

/**
 * Le sélecteur de langue, « FR · RU · EN », pour la barre noire de l'en-tête. Un code court qu'on voit, le nom de la
 * langue, dans sa langue, pour les lecteurs d'écran.
 */
function er_selecteur_langues() {
	$html = '';
	foreach ( er_liens_langues() as $l ) {
		$classes = 'er-langue' . ( $l['actuelle'] ? ' er-langue-actuelle' : '' ) . ( $l['traduite'] ? '' : ' er-langue-absente' );
		$html   .= '<li><a class="' . esc_attr( $classes ) . '" href="' . esc_url( $l['url'] ) . '" hreflang="' . esc_attr( $l['code'] ) . '" lang="' . esc_attr( $l['code'] ) . '"'
			. ( $l['actuelle'] ? ' aria-current="true"' : '' ) . '>'
			. '<span aria-hidden="true">' . esc_html( strtoupper( $l['code'] ) ) . '</span>'
			. '<span class="screen-reader-text">' . esc_html( $l['nom'] ) . '</span></a></li>';
	}
	return '<nav class="er-langues" aria-label="' . esc_attr__( 'Langue du site', 'er-librairie' ) . '"><ul>' . $html . '</ul></nav>';
}

/**
 * Auteurs et langues : deux classements des livres, avec leur page (« Tous les livres de Gouzel Iakhina »).
 */
add_action(
	'init',
	function () {
		register_taxonomy(
			'auteur',
			array( 'product' ),
			array(
				'labels'            => array(
					'name'          => __( 'Auteurs', 'er-librairie' ),
					'singular_name' => __( 'Auteur', 'er-librairie' ),
					'search_items'  => __( 'Chercher un auteur', 'er-librairie' ),
					'all_items'     => __( 'Tous les auteurs', 'er-librairie' ),
					'edit_item'     => __( 'Modifier l’auteur', 'er-librairie' ),
					'add_new_item'  => __( 'Ajouter un auteur', 'er-librairie' ),
					'menu_name'     => __( 'Auteurs', 'er-librairie' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'auteur' ),
				// Le compte d'un auteur, ce sont ses livres : pas les articles ni les rencontres liés (sujets.php).
				'update_count_callback' => 'er_compter_livres',
			)
		);
		register_taxonomy(
			'langue',
			array( 'product' ),
			array(
				'labels'            => array(
					'name'          => __( 'Langues', 'er-librairie' ),
					'singular_name' => __( 'Langue', 'er-librairie' ),
					'all_items'     => __( 'Toutes les langues', 'er-librairie' ),
					'edit_item'     => __( 'Modifier la langue', 'er-librairie' ),
					'add_new_item'  => __( 'Ajouter une langue', 'er-librairie' ),
					'menu_name'     => __( 'Langues', 'er-librairie' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => array( 'slug' => 'langue' ),
			)
		);
	},
	5
);

/**
 * Traductions de secours. Sur l'hébergement définitif, installer le français dans Réglages → Général suffit, et ces
 * lignes ne servent plus : elles ne s'appliquent qu'à une phrase restée en anglais, et seulement sur les pages en
 * français (en anglais, une phrase de WooCommerce restée en anglais est juste).
 */
function er_librairie_secours() {
	return array(
		'Add to cart'                             => 'Ajouter au panier',
		'Read more'                               => 'Voir le livre',
		'Out of stock'                            => 'Rupture de stock',
		'In stock'                                => 'En stock',
		'%s in stock'                             => '%s en stock',
		'Sale!'                                   => 'Promo',
		'Sale'                                    => 'Promo',
		'Related products'                        => 'Du même rayon',
		'You may also like&hellip;'               => 'Vous aimerez aussi',
		'Description'                             => 'Présentation',
		'Additional information'                  => 'Informations',
		'Reviews (%d)'                            => 'Avis (%d)',
		'Search products&hellip;'                 => 'Rechercher un titre, un auteur…',
		'Search products…'                        => 'Rechercher un titre, un auteur…',
		'Search'                                  => 'Rechercher',
		'Default sorting'                         => 'Tri par défaut',
		'Sort by popularity'                      => 'Les plus demandés',
		'Sort by average rating'                  => 'Les mieux notés',
		'Sort by latest'                          => 'Les plus récents',
		'Sort by price: low to high'              => 'Prix croissant',
		'Sort by price: high to low'              => 'Prix décroissant',
		'Showing the single result'               => 'Un livre',
		'Home'                                    => 'Accueil',
		'Shop'                                    => 'Librairie',
		'Cart'                                    => 'Panier',
		'View cart'                               => 'Voir le panier',
		'Checkout'                                => 'Commander',
		'Proceed to Checkout'                     => 'Commander',
		'Go to checkout'                          => 'Commander',
		'Log in'                                  => 'Se connecter',
		'Login'                                   => 'Se connecter',
		'My Account'                              => 'Mon compte',
		'My account'                              => 'Mon compte',
		'Quantity'                                => 'Quantité',
		'Category:'                               => 'Rayon :',
		'Categories:'                             => 'Rayons :',
		'Tag:'                                    => 'Mention :',
		'Tags:'                                   => 'Mentions :',
		'SKU:'                                    => 'Réf. :',
		'Your cart is currently empty!'           => 'Votre panier est vide.',
		'Return to shop'                          => 'Retour à la librairie',
		'No products were found matching your selection.' => 'Aucun livre ne correspond à votre recherche.',
		'Previous'                                => 'Précédent',
		'Next'                                    => 'Suivant',
		'Price'                                   => 'Prix',
		'Product'                                 => 'Livre',
		'Subtotal'                                => 'Sous-total',
		'Total'                                   => 'Total',
		'Remove item'                             => 'Retirer',
		'Search results: &ldquo;%s&rdquo;'        => 'Résultats pour « %s »',
		'Search results: “%s”'                    => 'Résultats pour « %s »',
	);
}

add_filter(
	'gettext_woocommerce',
	function ( $traduction, $texte ) {
		if ( $traduction !== $texte || 'fr' !== er_langue() ) {
			return $traduction;
		}
		$secours = er_librairie_secours();
		return isset( $secours[ $texte ] ) ? $secours[ $texte ] : $traduction;
	},
	10,
	2
);

add_filter(
	'ngettext_woocommerce',
	function ( $traduction, $singulier, $pluriel, $nombre ) {
		if ( ( $traduction !== $singulier && $traduction !== $pluriel ) || 'fr' !== er_langue() ) {
			return $traduction;
		}
		$formes = array(
			'Showing all %1$d result'         => array( 'Le livre', 'Les %1$d livres' ),
			'Showing all %d result'           => array( 'Le livre', 'Les %d livres' ),
			'Showing %1$d&ndash;%2$d of %3$d result' => array( 'Livres %1$d à %2$d sur %3$d', 'Livres %1$d à %2$d sur %3$d' ),
			'Showing %1$d–%2$d of %3$d result'       => array( 'Livres %1$d à %2$d sur %3$d', 'Livres %1$d à %2$d sur %3$d' ),
		);
		if ( isset( $formes[ $singulier ] ) ) {
			return 1 === (int) $nombre ? $formes[ $singulier ][0] : $formes[ $singulier ][1];
		}
		return $traduction;
	},
	10,
	4
);

add_filter(
	'ngettext_with_context_woocommerce',
	function ( $traduction, $singulier, $pluriel, $nombre ) {
		if ( 'fr' === er_langue() && 0 === strpos( $singulier, 'Showing %1$d' ) && ( $traduction === $singulier || $traduction === $pluriel ) ) {
			return 'Livres %1$d à %2$d sur %3$d';
		}
		return $traduction;
	},
	10,
	4
);

add_filter(
	'gettext_with_context_woocommerce',
	function ( $traduction, $texte, $contexte ) {
		if ( 'fr' === er_langue() && 'Home' === $texte && 'breadcrumb' === $contexte && $traduction === $texte ) {
			return 'Accueil';
		}
		return $traduction;
	},
	10,
	3
);

/**
 * Les quelques phrases de WordPress lui-même que le site montre, tant que le français n'est pas installé.
 */
add_filter(
	'gettext_default',
	function ( $traduction, $texte ) {
		if ( $traduction !== $texte || 'fr' !== er_langue() ) {
			return $traduction;
		}
		$secours = array(
			'Page not found'                         => 'Page introuvable',
			'Search Results for &#8220;%s&#8221;'    => 'Résultats pour «&nbsp;%s&nbsp;»',
			'Search results for: &#8220;%s&#8221;'   => 'Résultats pour «&nbsp;%s&nbsp;»',
			'Search'                                 => 'Chercher',
			'Search results for: %s'                 => 'Résultats pour «&nbsp;%s&nbsp;»',
			'Open menu'                              => 'Ouvrir le menu',
			'Close menu'                             => 'Fermer le menu',
			'Menu'                                   => 'Menu',
			'Submenu'                                => 'Sous-menu',
			'Skip to content'                        => 'Aller au contenu',
			'Expand search field'                    => 'Ouvrir la recherche',
			'Submit Search'                          => 'Lancer la recherche',
		);
		return isset( $secours[ $texte ] ) ? $secours[ $texte ] : $traduction;
	},
	10,
	2
);
