<?php
/**
 * Plugin Name: Les Éditeurs réunis — librairie
 * Description: Les données de la librairie : auteurs et langues des livres, et des traductions de secours pour WooCommerce tant que son paquet de langue français n'est pas installé. Séparée du thème : la direction artistique peut changer sans toucher aux livres.
 * Version: 0.1.0
 * Requires Plugins: woocommerce
 * Text Domain: er-librairie
 */

defined( 'ABSPATH' ) || exit;

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
					'name'          => 'Auteurs',
					'singular_name' => 'Auteur',
					'search_items'  => 'Chercher un auteur',
					'all_items'     => 'Tous les auteurs',
					'edit_item'     => 'Modifier l’auteur',
					'add_new_item'  => 'Ajouter un auteur',
					'menu_name'     => 'Auteurs',
				),
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'auteur' ),
			)
		);
		register_taxonomy(
			'langue',
			array( 'product' ),
			array(
				'labels'            => array(
					'name'          => 'Langues',
					'singular_name' => 'Langue',
					'all_items'     => 'Toutes les langues',
					'edit_item'     => 'Modifier la langue',
					'add_new_item'  => 'Ajouter une langue',
					'menu_name'     => 'Langues',
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
 * lignes ne servent plus : elles ne s'appliquent qu'à une phrase restée en anglais.
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
		if ( $traduction !== $texte ) {
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
		if ( $traduction !== $singulier && $traduction !== $pluriel ) {
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
		if ( 0 === strpos( $singulier, 'Showing %1$d' ) && ( $traduction === $singulier || $traduction === $pluriel ) ) {
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
		if ( 'Home' === $texte && 'breadcrumb' === $contexte && $traduction === $texte ) {
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
		if ( $traduction !== $texte ) {
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
		);
		return isset( $secours[ $texte ] ) ? $secours[ $texte ] : $traduction;
	},
	10,
	2
);
