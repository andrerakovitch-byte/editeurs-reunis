<?php
/**
 * L'import des livres par l'importateur de WooCommerce (Produits → Importer).
 *
 * L'importateur connaît les champs de WooCommerce et les méta (« Meta: er_traducteur »…) ; il ne connaît pas les
 * classements de la librairie. Ces colonnes-ci les lui apprennent, plusieurs valeurs séparées par « | » :
 * - Auteurs : les noms, créés s'ils n'existent pas ;
 * - Langues : les slugs de la taxonomie langue (francais, russe) ;
 * - Rayons : les slugs des rayons (romans, age-d-argent, goulag…) ; ils remplacent les rayons du livre ;
 * - Mentions : les slugs des étiquettes (nouveaute, occasion, tres-rare…) ;
 * - Slug : l'adresse du livre ; un livre sans référence qui a déjà cette adresse est mis à jour au lieu d'être doublé
 *   (les livres d'exemple de semer.php, que le catalogue remplace).
 *
 * Le fichier du catalogue Wix est préparé hors du dépôt (il contient des données de la boutique).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les colonnes propres à la librairie : la clé de l'importateur et l'en-tête du fichier.
 */
function er_import_colonnes() {
	return array(
		'er_auteurs'  => __( 'Auteurs', 'er-librairie' ),
		'er_langues'  => __( 'Langues', 'er-librairie' ),
		'er_rayons'   => __( 'Rayons', 'er-librairie' ),
		'er_mentions' => __( 'Mentions', 'er-librairie' ),
		'er_slug'     => __( 'Slug', 'er-librairie' ),
	);
}

/**
 * Une liste « a | b | c » → ses valeurs.
 *
 * @param string $valeur La cellule.
 */
function er_import_liste( $valeur ) {
	return array_values( array_filter( array_map( 'trim', explode( '|', (string) $valeur ) ), 'strlen' ) );
}

// Les colonnes dans la liste de l'importateur, et reconnues d'elles-mêmes à leur en-tête (français ou non traduit).
add_filter(
	'woocommerce_csv_product_import_mapping_options',
	function ( $options ) {
		return $options + er_import_colonnes();
	}
);
add_filter(
	'woocommerce_csv_product_import_mapping_default_columns',
	function ( $colonnes ) {
		$sources = array(
			'er_auteurs'  => 'Auteurs',
			'er_langues'  => 'Langues',
			'er_rayons'   => 'Rayons',
			'er_mentions' => 'Mentions',
			'er_slug'     => 'Slug',
		);
		foreach ( er_import_colonnes() as $cle => $libelle ) {
			$colonnes[ $libelle ]         = $cle;
			$colonnes[ $sources[ $cle ] ] = $cle;
		}
		return $colonnes;
	}
);

// Un livre sans référence qui a déjà l'adresse demandée : on le met à jour (les livres d'exemple).
add_filter(
	'woocommerce_product_import_get_product_object',
	function ( $produit, $donnees ) {
		if ( is_wp_error( $produit ) || $produit->get_id() || empty( $donnees['er_slug'] ) ) {
			return $produit;
		}
		$existant = get_page_by_path( sanitize_title( $donnees['er_slug'] ), OBJECT, 'product' );
		if ( $existant ) {
			$deja = wc_get_product( $existant->ID );
			if ( $deja && '' === $deja->get_sku() ) {
				return $deja;
			}
		}
		return $produit;
	},
	10,
	2
);

add_filter(
	'woocommerce_product_import_pre_insert_product_object',
	function ( $produit, $donnees ) {
		if ( ! empty( $donnees['er_slug'] ) ) {
			$produit->set_slug( sanitize_title( $donnees['er_slug'] ) );
		}
		return $produit;
	},
	10,
	2
);

add_action(
	'woocommerce_product_import_inserted_product_object',
	function ( $produit, $donnees ) {
		$id = $produit->get_id();
		if ( ! $id ) {
			return;
		}
		if ( isset( $donnees['er_auteurs'] ) ) {
			wp_set_object_terms( $id, er_import_liste( $donnees['er_auteurs'] ), 'auteur' );
		}
		$par_slug = function ( $slugs, $taxonomie ) {
			$ids = array();
			foreach ( $slugs as $slug ) {
				$terme = get_term_by( 'slug', sanitize_title( $slug ), $taxonomie );
				if ( $terme ) {
					$ids[] = (int) $terme->term_id;
				}
			}
			return $ids;
		};
		if ( isset( $donnees['er_langues'] ) ) {
			$ids = $par_slug( er_import_liste( $donnees['er_langues'] ), 'langue' );
			if ( $ids ) {
				wp_set_object_terms( $id, $ids, 'langue' );
			}
		}
		if ( isset( $donnees['er_rayons'] ) ) {
			$ids = $par_slug( er_import_liste( $donnees['er_rayons'] ), 'product_cat' );
			if ( $ids ) {
				wp_set_object_terms( $id, $ids, 'product_cat' );
			}
		}
		if ( isset( $donnees['er_mentions'] ) ) {
			wp_set_object_terms( $id, $par_slug( er_import_liste( $donnees['er_mentions'] ), 'product_tag' ), 'product_tag' );
		}
		// L'index de la recherche, une fois la langue et les rayons posés.
		if ( function_exists( 'er_index_contenu' ) ) {
			er_index_contenu( $id );
		}
	},
	10,
	2
);

// La référence d'un livre venu de Wix (WIX-…) ne sert qu'à réimporter : elle ne paraît pas sur la fiche.
add_filter(
	'render_block_woocommerce/product-sku',
	function ( $html ) {
		return false !== strpos( $html, 'WIX-' ) ? '' : $html;
	}
);
