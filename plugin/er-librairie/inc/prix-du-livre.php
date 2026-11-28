<?php
/**
 * Les règles du livre en France, appliquées par la boutique elle-même : le prix unique, les soldes, les frais de port
 * minimaux, la TVA. Ce qu'elles disent (vérifié le 24 nov. 2026) :
 *
 * - Le prix unique (loi Lang du 10 août 1981, art. 1) : un livre neuf se vend au prix fixé par l'éditeur. Expédié à
 *   l'acheteur, il n'a droit à aucune remise (loi du 8 juillet 2014) ; les 5 % ne valent qu'au retrait en librairie,
 *   ce que la boutique en ligne ne propose pas. Une remise saisie sur un livre neuf est donc ignorée, et un code promo
 *   ne s'applique pas à un livre neuf, sauf aux soldes ci-dessous.
 * - Les soldes (art. 5) : seulement un livre édité depuis plus de deux ans, et dont le dernier approvisionnement
 *   remonte à plus de six mois. Nous ne connaissons que l'année d'édition : « plus de deux ans » n'est sûr qu'à trois
 *   ans d'écart (un livre de 2024 attend 2027). Le dernier approvisionnement se note tout seul quand on augmente le
 *   stock d'un livre dans sa fiche (le champ « Dernier approvisionnement », dans l'onglet Inventaire). Un livre dont
 *   la date n'est pas notée se vend au prix de l'éditeur (audit du 27 nov. 2026 : rien ne prouve alors les six mois,
 *   et l'import du catalogue ne la note pas) ; le relevé `wp er prix` dit lesquels dater. Pour un livre importé, les
 *   deux ans courent depuis l'importation : à vérifier avant de dater une remise.
 * - Les titres de la maison (loi Darcos du 30 décembre 2021) : un éditeur qui vend lui-même au détail ne solde pas ses
 *   propres titres, à aucun âge. Les livres d'YMCA-Press (ИМКА-Пресс) n'ont jamais de remise.
 * - Les livres d'occasion (la mention « Occasion ») ont un prix libre, et la mention les suit jusque dans le panier et
 *   la commande (décret du 22 juin 2023). Les billets des rencontres, les calendriers et les objets ne sont pas des
 *   livres.
 * - Les codes promo : WooCommerce applique un code « remise sur le panier » à toutes les lignes, sans demander au
 *   filtre des produits ; les lignes au prix unique sont donc retirées avant le calcul, et un code qui ne toucherait
 *   que des livres neufs au prix unique est refusé.
 * - Les frais de port (arrêté du 4 avril 2023) : une commande expédiée qui contient des livres neufs paie au moins
 *   3 € TTC de livraison quand ses livres neufs font moins de 35 € TTC, et au moins 0,01 € au-delà. Le retrait à la
 *   librairie peut rester gratuit.
 * - La TVA du livre : 5,5 %, sur les livres et sur leur livraison. `wp er tva appliquer` règle WooCommerce en ce sens
 *   (prix saisis TTC, un taux de 5,5 % pour la France, la livraison au taux des articles).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/* Les règles, sans WordPress --------------------------------------------------------------------------------- */

/**
 * Un livre peut-il se vendre sous le prix de l'éditeur ? La règle seule, d'après ce qu'on sait du livre.
 *
 * @param array  $livre {
 *     @type bool   $livre  Un livre (pas un billet).
 *     @type bool   $neuf   Neuf (pas d'occasion).
 *     @type bool   $maison Un titre de la maison (YMCA-Press).
 *     @type int    $annee  L'année d'édition, 0 si on ne la connaît pas.
 *     @type string $appro  Le dernier approvisionnement (AAAA-MM-JJ), vide s'il n'est pas noté.
 * }
 * @param string $aujourdhui La date du jour (AAAA-MM-JJ).
 * @return array{0: bool, 1: string} Peut-il être soldé, et pourquoi.
 */
function er_prix_regle( $livre, $aujourdhui ) {
	$livre = array_merge(
		array(
			'livre'  => true,
			'neuf'   => true,
			'maison' => false,
			'annee'  => 0,
			'appro'  => '',
		),
		$livre
	);
	if ( ! $livre['livre'] ) {
		return array( true, 'pas un livre' );
	}
	if ( ! $livre['neuf'] ) {
		return array( true, 'occasion' );
	}
	if ( $livre['maison'] ) {
		return array( false, 'titre de la maison' );
	}
	if ( $livre['annee'] < 1450 ) {
		return array( false, 'année d’édition inconnue' );
	}
	if ( (int) substr( $aujourdhui, 0, 4 ) - $livre['annee'] < 3 ) {
		return array( false, 'édité il y a moins de deux ans' );
	}
	if ( '' === $livre['appro'] ) {
		return array( false, 'dernier approvisionnement non noté' );
	}
	if ( $livre['appro'] > gmdate( 'Y-m-d', strtotime( $aujourdhui . ' -6 months' ) ) ) {
		return array( false, 'réapprovisionné il y a moins de six mois' );
	}
	return array( true, 'soldes' );
}

/**
 * Le prix minimal de la livraison d'une commande expédiée, d'après la valeur de ses livres neufs (TTC).
 *
 * @param float $neufs La valeur des livres neufs, TTC.
 * @return float Le minimum, TTC.
 */
function er_prix_port_minimum( $neufs ) {
	return $neufs < 35 ? 3.0 : 0.01;
}

/* Ce qu'on sait d'un livre ----------------------------------------------------------------------------------- */

/**
 * Les éditeurs dont les titres sont ceux de la maison, en clés (er_editeur_cle).
 */
function er_prix_maisons() {
	return array_map( 'er_editeur_cle', (array) apply_filters( 'er_prix_maisons', array( 'YMCA-Press', 'ИМКА-Пресс', 'IMKA-Press' ) ) );
}

/**
 * Ce que la règle a besoin de savoir d'un produit.
 *
 * @param WC_Product $produit Le produit.
 */
function er_prix_livre( $produit ) {
	$id      = $produit->get_id();
	$annee   = preg_match( '/\b(1[4-9]\d\d|20\d\d)\b/', (string) $produit->get_meta( 'er_annee' ), $m ) ? (int) $m[1] : 0;
	$appro   = (string) $produit->get_meta( 'er_dernier_appro' );
	$noms    = array( (string) $produit->get_meta( 'er_editeur' ) );
	$termes  = taxonomy_exists( 'er_editeur' ) ? wp_get_post_terms( $id, 'er_editeur', array( 'fields' => 'names' ) ) : array();
	$noms    = array_merge( $noms, is_wp_error( $termes ) ? array() : $termes );
	$editeur = array();
	foreach ( $noms as $nom ) {
		// Une co-édition (« Русский путь / YMCA-Press ») compte pour chacun.
		foreach ( preg_split( '/\s+\/\s+/u', $nom ) as $e ) {
			$editeur[] = er_editeur_cle( $e );
		}
	}
	// Un calendrier, un objet (oeuvres-collectives.php) : ni le prix unique, ni le seuil des 35 € de livres neufs.
	$hors = function_exists( 'er_nature_hors_livre' ) && er_nature_hors_livre( (string) $produit->get_meta( 'er_nature' ) );
	return array(
		'livre'  => ! $produit->get_meta( 'er_evenement' ) && ! $hors,
		'neuf'   => ! has_term( 'occasion', 'product_tag', $id ),
		'maison' => (bool) array_intersect( array_filter( $editeur ), er_prix_maisons() ),
		'annee'  => $annee,
		'appro'  => preg_match( '/^\d{4}-\d\d-\d\d$/', $appro ) ? $appro : '',
	);
}

/**
 * La règle pour un produit, retenue le temps de la page.
 *
 * @param WC_Product $produit Le produit.
 * @return array{0: bool, 1: string}
 */
function er_prix_regle_du_produit( $produit ) {
	static $faites = array();
	$id = $produit->get_id();
	if ( ! isset( $faites[ $id ] ) ) {
		$faites[ $id ] = er_prix_regle( er_prix_livre( $produit ), wp_date( 'Y-m-d' ) );
	}
	return $faites[ $id ];
}

/**
 * Un livre neuf : la règle des frais de port le compte.
 *
 * @param WC_Product $produit Le produit.
 */
function er_prix_livre_neuf( $produit ) {
	$l = er_prix_livre( $produit );
	return $l['livre'] && $l['neuf'];
}

/* Le prix affiché et payé ------------------------------------------------------------------------------------ */

// Seulement dans le contexte « view » (la boutique, le panier, la commande) : la fiche dans l'administration garde la
// remise saisie, et dit pourquoi elle est ignorée.
add_filter(
	'woocommerce_product_get_sale_price',
	function ( $prix, $produit ) {
		return '' === $prix || er_prix_regle_du_produit( $produit )[0] ? $prix : '';
	},
	20,
	2
);
add_filter(
	'woocommerce_product_get_price',
	function ( $prix, $produit ) {
		if ( er_prix_regle_du_produit( $produit )[0] ) {
			return $prix;
		}
		$editeur = $produit->get_regular_price();
		return '' !== $editeur ? $editeur : $prix;
	},
	20,
	2
);
add_filter(
	'woocommerce_product_is_on_sale',
	function ( $en_solde, $produit ) {
		return $en_solde && er_prix_regle_du_produit( $produit )[0];
	},
	20,
	2
);

// Un code promo ne touche pas un livre neuf au prix unique : les codes « produit » le demandent à ce filtre…
add_filter(
	'woocommerce_coupon_is_valid_for_product',
	function ( $valide, $produit ) {
		return $valide && $produit instanceof WC_Product && er_prix_regle_du_produit( $produit )[0];
	},
	20,
	2
);

/**
 * Une ligne de remise (WC_Discounts) qui peut recevoir un code promo : tout ce qui n'est pas un livre neuf au prix
 * unique.
 *
 * @param object $ligne La ligne (son produit dans « product »).
 */
function er_prix_ligne_remisable( $ligne ) {
	return ! ( isset( $ligne->product ) && $ligne->product instanceof WC_Product ) || er_prix_regle_du_produit( $ligne->product )[0];
}

// … mais un code « remise sur le panier » touche toutes les lignes sans le demander (WC_Discounts::
// get_items_to_apply_coupon) : on retire les livres neufs au prix unique avant le calcul (WooCommerce 8.8 et plus).
add_filter(
	'woocommerce_coupon_get_items_to_apply',
	function ( $lignes ) {
		return array_values( array_filter( (array) $lignes, 'er_prix_ligne_remisable' ) );
	},
	20
);

// Et un code qui ne trouverait rien à remiser est refusé, avec la raison, plutôt qu'appliqué pour zéro.
add_filter(
	'woocommerce_coupon_is_valid',
	function ( $valide, $code, $remises = null ) {
		if ( ! $valide || ! is_object( $remises ) || ! method_exists( $remises, 'get_items' ) ) {
			return $valide;
		}
		$lignes = (array) $remises->get_items();
		if ( $lignes && ! array_filter( $lignes, 'er_prix_ligne_remisable' ) ) {
			throw new Exception( esc_html__( 'Ce code ne s’applique pas aux livres neufs, vendus au prix fixé par l’éditeur (prix unique du livre).', 'er-librairie' ) );
		}
		return $valide;
	},
	20,
	3
);

/**
 * Ce qui reste d'une liste « en promotion » une fois la règle appliquée : seulement les produits dont la remise est
 * vraiment faite. WooCommerce les choisit sur la remise saisie (wc_get_product_ids_on_sale), que la règle peut
 * ignorer : la page Promos montrait 55 livres au prix plein (passage sur la bêta, 28 nov. 2026).
 *
 * @param int[]    $ids        Les produits retenus par WooCommerce.
 * @param callable $produit_de De l'identifiant au produit.
 * @return int[] Jamais vide : array( 0 ) quand rien ne reste, car un post__in vide voudrait dire « tous ».
 */
function er_prix_vraies_promos( $ids, $produit_de = 'wc_get_product' ) {
	$garder = array();
	foreach ( (array) $ids as $id ) {
		$produit = (int) $id > 0 ? $produit_de( (int) $id ) : null;
		if ( $produit && $produit->is_on_sale() ) {
			$garder[] = (int) $id;
		}
	}
	return $garder ? $garder : array( 0 );
}

// Le bloc « Collection de produits » réglé sur les promotions (la page Promos) : WooCommerce pose sa liste à la
// priorité 10 ; on la trie ensuite.
add_filter(
	'query_loop_block_query_vars',
	function ( $requete, $bloc = null ) {
		$q = is_object( $bloc ) && isset( $bloc->context['query'] ) ? (array) $bloc->context['query'] : array();
		if ( empty( $q['isProductCollectionBlock'] ) || empty( $q['woocommerceOnSale'] ) || empty( $requete['post__in'] ) ) {
			return $requete;
		}
		$requete['post__in'] = er_prix_vraies_promos( $requete['post__in'] );
		return $requete;
	},
	99,
	2
);

/* Les frais de port ------------------------------------------------------------------------------------------ */

/**
 * Relève au minimum légal les tarifs de livraison d'un colis qui contient des livres neufs ; le retrait à la librairie
 * n'est pas touché.
 *
 * @param WC_Shipping_Rate[] $tarifs Les tarifs proposés.
 * @param array              $colis  Le colis (ses lignes dans « contents »).
 */
function er_prix_frais_de_port( $tarifs, $colis ) {
	$neufs = null;
	foreach ( (array) ( $colis['contents'] ?? array() ) as $ligne ) {
		if ( isset( $ligne['data'] ) && $ligne['data'] instanceof WC_Product && er_prix_livre_neuf( $ligne['data'] ) ) {
			$neufs = (float) $neufs + (float) ( $ligne['line_total'] ?? 0 ) + (float) ( $ligne['line_tax'] ?? 0 );
		}
	}
	if ( null === $neufs ) {
		return $tarifs;
	}
	$minimum = er_prix_port_minimum( $neufs );
	foreach ( $tarifs as $tarif ) {
		if ( in_array( $tarif->get_method_id(), array( 'local_pickup', 'pickup_location' ), true ) ) {
			continue;
		}
		if ( (float) $tarif->get_cost() + array_sum( (array) $tarif->get_taxes() ) >= $minimum - 0.001 ) {
			continue;
		}
		$taux  = wc_tax_enabled() ? WC_Tax::get_shipping_tax_rates() : array();
		$taxes = $taux ? WC_Tax::calc_tax( $minimum, $taux, true ) : array();
		$tarif->set_cost( (string) round( $minimum - array_sum( $taxes ), 4 ) );
		$tarif->set_taxes( $taxes );
		if ( 'free_shipping' === $tarif->get_method_id() ) {
			// « Livraison gratuite » ne l'est plus : le prix affiché dit le reste.
			$tarif->set_label( __( 'Livraison', 'er-librairie' ) );
		}
	}
	return $tarifs;
}
add_filter( 'woocommerce_package_rates', 'er_prix_frais_de_port', 20, 2 );

/* La fiche du livre, dans l'administration ------------------------------------------------------------------- */

// Sous les prix : pourquoi la remise saisie est ignorée.
add_action(
	'woocommerce_product_options_pricing',
	function () {
		global $product_object;
		if ( ! $product_object instanceof WC_Product || '' === $product_object->get_sale_price( 'edit' ) ) {
			return;
		}
		list( $permis, $raison ) = er_prix_regle_du_produit( $product_object );
		if ( ! $permis ) {
			echo '<p class="form-field"><strong>' . esc_html__( 'Prix unique du livre :', 'er-librairie' ) . '</strong> '
				/* translators: %s: la raison (« titre de la maison », « édité il y a moins de deux ans »…) */
				. esc_html( sprintf( __( 'la remise est ignorée (%s) ; le livre se vend au prix de l’éditeur.', 'er-librairie' ), $raison ) ) . '</p>';
		}
	}
);

// Dans l'inventaire : le dernier approvisionnement.
add_action(
	'woocommerce_product_options_inventory_product_data',
	function () {
		woocommerce_wp_text_input(
			array(
				'id'          => 'er_dernier_appro',
				'label'       => __( 'Dernier approvisionnement', 'er-librairie' ),
				'type'        => 'date',
				'desc_tip'    => true,
				'description' => __( 'Noté tout seul quand le stock augmente. Un livre neuf ne se solde que six mois après.', 'er-librairie' ),
			)
		);
	}
);

add_action(
	'woocommerce_admin_process_product_object',
	function ( $produit ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}
		$saisi  = sanitize_text_field( wp_unslash( $_POST['er_dernier_appro'] ?? '' ) );
		$avant  = (string) $produit->get_meta( 'er_dernier_appro' );
		$id     = $produit->get_id();
		$plus   = $produit->managing_stock() && (float) $produit->get_stock_quantity( 'edit' ) > (float) get_post_meta( $id, '_stock', true );
		$revenu = 'instock' === $produit->get_stock_status( 'edit' ) && 'instock' !== get_post_meta( $id, '_stock_status', true ) && '' !== (string) get_post_meta( $id, '_stock_status', true );
		if ( $saisi !== $avant && preg_match( '/^\d{4}-\d\d-\d\d$|^$/', $saisi ) ) {
			$produit->update_meta_data( 'er_dernier_appro', $saisi );
		} elseif ( $plus || $revenu ) {
			$produit->update_meta_data( 'er_dernier_appro', wp_date( 'Y-m-d' ) );
		}
	}
);

/* La mention « occasion », jusqu'au bout ---------------------------------------------------------------------- */

/**
 * Un livre d'occasion : la mention « Occasion » sur le produit (ou sur son parent).
 *
 * @param int $id Le produit.
 */
function er_prix_occasion( $id ) {
	return $id > 0 && has_term( 'occasion', 'product_tag', $id );
}

// Le panier (classique et en blocs, par l'API du magasin) : « État : occasion » sous le titre.
add_filter(
	'woocommerce_get_item_data',
	function ( $donnees, $ligne ) {
		if ( er_prix_occasion( (int) ( $ligne['product_id'] ?? 0 ) ) ) {
			$donnees[] = array(
				'key'   => __( 'État', 'er-librairie' ),
				'value' => __( 'occasion', 'er-librairie' ),
			);
		}
		return $donnees;
	},
	10,
	2
);

// La commande, et donc ses courriels et sa facture.
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $ligne, $cle, $valeurs ) {
		if ( er_prix_occasion( (int) ( $valeurs['product_id'] ?? 0 ) ) ) {
			$ligne->add_meta_data( __( 'État', 'er-librairie' ), __( 'occasion', 'er-librairie' ), true );
		}
	},
	10,
	3
);

/* Les commandes ---------------------------------------------------------------------------------------------- */

/**
 * Un libellé de relevé, complété d'espaces jusqu'à la colonne des valeurs (compte les lettres, pas les octets).
 *
 * @param string $texte Le libellé.
 */
function er_prix_colonne( $texte ) {
	return $texte . str_repeat( ' ', max( 1, 46 - mb_strlen( $texte ) ) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Le relevé des prix : les remises des livres neufs, permises ou ignorées, et pourquoi. wp er prix [tout]
	 *
	 * wp er prix maison [appliquer] : les titres de la maison qui portent une remise, que la loi ignore. L'éditeur fixe
	 * le prix de ses livres : s'il veut ce prix-là, c'est un nouveau prix de l'éditeur. Avec « appliquer », le prix
	 * remisé devient le prix du livre, sans remise affichée.
	 *
	 * @param array $args « tout » : la liste entière des remises ignorées, sinon les quarante premières ; « maison ».
	 */
	WP_CLI::add_command(
		'er prix',
		function ( $args ) {
			if ( isset( $args[0] ) && 'maison' === $args[0] ) {
				er_prix_maison( isset( $args[1] ) && 'appliquer' === $args[1] );
				return;
			}
			$ids      = get_posts(
				array(
					'post_type'   => 'product',
					'post_status' => array( 'publish', 'draft', 'private' ),
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);
			$raisons  = array();
			$ignorees = array();
			$soldes   = 0;
			$sans_ap  = 0;
			foreach ( $ids as $id ) {
				$p = wc_get_product( $id );
				if ( ! $p || '' === $p->get_sale_price( 'edit' ) ) {
					continue;
				}
				list( $permis, $raison ) = er_prix_regle_du_produit( $p );
				$raisons[ $raison ]      = ( $raisons[ $raison ] ?? 0 ) + 1;
				$sans_ap += 'dernier approvisionnement non noté' === $raison ? 1 : 0;
				if ( $permis ) {
					$soldes += 'soldes' === $raison ? 1 : 0;
				} else {
					$ignorees[] = sprintf( '  %s · %s · %s € au lieu de %s €', html_entity_decode( $p->get_name(), ENT_QUOTES, 'UTF-8' ), $raison, $p->get_sale_price( 'edit' ), $p->get_regular_price( 'edit' ) );
				}
			}
			WP_CLI::log( sprintf( 'Les remises saisies : %d livres.', array_sum( $raisons ) ) );
			arsort( $raisons );
			foreach ( $raisons as $raison => $n ) {
				WP_CLI::log( '  ' . er_prix_colonne( $raison ) . $n );
			}
			WP_CLI::log( sprintf( 'Soldes permises : %d. Remises ignorées faute de date de dernier approvisionnement : %d (à noter dans la fiche, onglet Inventaire, si le livre est en librairie depuis plus de six mois).', $soldes, $sans_ap ) );
			WP_CLI::log( sprintf( 'Remises ignorées (le livre se vend au prix de l’éditeur) : %d', count( $ignorees ) ) );
			foreach ( isset( $args[0] ) && 'tout' === $args[0] ? $ignorees : array_slice( $ignorees, 0, 40 ) as $ligne ) {
				WP_CLI::log( $ligne );
			}
			if ( count( $ignorees ) > 40 && ! ( isset( $args[0] ) && 'tout' === $args[0] ) ) {
				WP_CLI::log( '  … « wp er prix tout » pour la liste entière.' );
			}
		}
	);

	/**
	 * Les titres de la maison remisés : leur prix remisé comme nouveau prix de l'éditeur (wp er prix maison).
	 *
	 * @param bool $appliquer Poser les nouveaux prix, sinon seulement les montrer.
	 */
	function er_prix_maison( $appliquer ) {
		$n = 0;
		foreach ( get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => array( 'publish', 'draft', 'private' ),
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		) as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p || '' === $p->get_sale_price( 'edit' ) || 'titre de la maison' !== er_prix_regle_du_produit( $p )[1] ) {
				continue;
			}
			++$n;
			WP_CLI::log( sprintf( '  %s · %s € → %s €', html_entity_decode( $p->get_name(), ENT_QUOTES, 'UTF-8' ), $p->get_regular_price( 'edit' ), $p->get_sale_price( 'edit' ) ) );
			if ( $appliquer ) {
				$p->set_regular_price( $p->get_sale_price( 'edit' ) );
				$p->set_sale_price( '' );
				$p->save();
			}
		}
		WP_CLI::log( sprintf( $appliquer ? '%d titres de la maison à leur nouveau prix.' : '%d titres de la maison remisés ; « wp er prix maison appliquer » fait du prix remisé leur nouveau prix.', $n ) );
	}

	/**
	 * La TVA du livre : wp er tva [appliquer]. Montre les réglages ; avec « appliquer », les pose : taxes calculées,
	 * prix saisis TTC, calcul sur l'adresse de la boutique, un taux de 5,5 % pour la France (livraison comprise), la
	 * livraison au taux des articles, les prix affichés TTC.
	 *
	 * @param array $args « appliquer », ou rien pour seulement montrer.
	 */
	WP_CLI::add_command(
		'er tva',
		function ( $args ) {
			$appliquer = isset( $args[0] ) && 'appliquer' === $args[0];
			$reglages  = array(
				'woocommerce_calc_taxes'         => array( 'yes', 'Taxes calculées' ),
				'woocommerce_prices_include_tax' => array( 'yes', 'Prix saisis TTC' ),
				'woocommerce_tax_based_on'       => array( 'base', 'Calcul d’après l’adresse de la boutique' ),
				'woocommerce_shipping_tax_class' => array( 'inherit', 'Livraison au taux des articles' ),
				'woocommerce_tax_display_shop'   => array( 'incl', 'Prix affichés TTC dans la boutique' ),
				'woocommerce_tax_display_cart'   => array( 'incl', 'Prix affichés TTC dans le panier' ),
			);
			foreach ( $reglages as $option => list( $voulu, $libelle ) ) {
				$avant = (string) get_option( $option );
				if ( $appliquer && $avant !== $voulu ) {
					update_option( $option, $voulu );
				}
				WP_CLI::log( '  ' . er_prix_colonne( $libelle ) . ( $appliquer ? $voulu : $avant ) . ( $avant === $voulu ? '' : ( $appliquer ? ' (était : ' . $avant . ')' : ' (attendu : ' . $voulu . ')' ) ) );
			}
			$taux = null;
			foreach ( WC_Tax::get_rates_for_tax_class( '' ) as $t ) {
				if ( 'FR' === $t->tax_rate_country ) {
					$taux = $t;
					break;
				}
			}
			$voulu = array(
				'tax_rate_country'  => 'FR',
				'tax_rate'          => '5.5000',
				'tax_rate_name'     => 'TVA',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			);
			if ( $taux && 5.5 === (float) $taux->tax_rate && (int) $taux->tax_rate_shipping ) {
				WP_CLI::log( '  ' . er_prix_colonne( 'Taux pour la France' ) . '5,5 %, livraison comprise' );
			} elseif ( $appliquer ) {
				if ( $taux ) {
					WC_Tax::_update_tax_rate( (int) $taux->tax_rate_id, $voulu );
				} else {
					WC_Tax::_insert_tax_rate( $voulu );
				}
				WP_CLI::log( '  ' . er_prix_colonne( 'Taux pour la France' ) . '5,5 %, livraison comprise (posé)' );
			} else {
				WP_CLI::log( '  ' . er_prix_colonne( 'Taux pour la France' ) . ( $taux ? $taux->tax_rate . ' % (attendu : 5,5 %, livraison comprise)' : 'aucun (attendu : 5,5 %, livraison comprise)' ) );
			}
			if ( ! $appliquer ) {
				WP_CLI::log( '« wp er tva appliquer » pour poser ces réglages.' );
			}
			WP_CLI::log( 'Les billets des rencontres suivent le même taux : à confirmer avec le comptable.' );
		}
	);
}
