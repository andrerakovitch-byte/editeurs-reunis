<?php
/**
 * Les règles du livre : le prix unique, les soldes, les titres de la maison, les frais de port
 * (plugin/er-librairie/inc/prix-du-livre.php).
 *
 * @package editeurs-reunis
 */

function test_prix_unique_pas_de_remise_sur_un_livre_neuf_recent() {
	egal( array( false, 'édité il y a moins de deux ans' ), er_prix_regle( array( 'annee' => 2025 ), '2026-11-24' ) );
	egal( array( false, 'édité il y a moins de deux ans' ), er_prix_regle( array( 'annee' => 2024 ), '2026-11-24' ), 'deux ans ne sont sûrs qu’à trois ans d’écart' );
	egal( array( false, 'année d’édition inconnue' ), er_prix_regle( array(), '2026-11-24' ) );
}

function test_soldes_apres_deux_ans_et_six_mois_sans_approvisionnement() {
	egal( array( true, 'soldes' ), er_prix_regle( array( 'annee' => 2019, 'appro' => '2026-03-01' ), '2026-11-24' ) );
	egal( array( false, 'réapprovisionné il y a moins de six mois' ), er_prix_regle( array( 'annee' => 2019, 'appro' => '2026-06-01' ), '2026-11-24' ) );
	egal( array( true, 'soldes, dernier approvisionnement non noté' ), er_prix_regle( array( 'annee' => 2019 ), '2026-11-24' ) );
}

function test_jamais_de_soldes_sur_les_titres_de_la_maison() {
	egal( array( false, 'titre de la maison' ), er_prix_regle( array( 'annee' => 1975, 'maison' => true ), '2026-11-24' ) );
}

function test_occasion_et_billets_au_prix_libre() {
	egal( array( true, 'occasion' ), er_prix_regle( array( 'neuf' => false, 'annee' => 2026 ), '2026-11-24' ) );
	egal( array( true, 'pas un livre' ), er_prix_regle( array( 'livre' => false ), '2026-11-24' ) );
}

function test_ce_que_la_regle_sait_d_un_produit() {
	$GLOBALS['er_test_termes_de'][201] = array(
		'er_editeur'  => array( 'Éditions YMCA-Press' ),
		'product_tag' => array( 'nouveaute' ),
	);
	$l = er_prix_livre( new WC_Product( 201, array( 'er_annee' => 'Paris, 1973' ) ) );
	egal( true, $l['maison'], 'une variante du nom de la maison' );
	egal( 1973, $l['annee'] );
	egal( true, $l['neuf'] );
	$GLOBALS['er_test_termes_de'][202] = array( 'product_tag' => array( 'occasion' ) );
	$l = er_prix_livre( new WC_Product( 202, array( 'er_editeur' => 'Русский путь / YMCA-Press', 'er_dernier_appro' => 'hier' ) ) );
	egal( true, $l['maison'], 'une co-édition de la maison' );
	egal( false, $l['neuf'] );
	egal( '', $l['appro'], 'une date mal écrite ne compte pas' );
}

function test_le_prix_paye_est_celui_de_l_editeur_quand_la_remise_est_ignoree() {
	$GLOBALS['er_test_aujourdhui']     = '2026-11-24';
	$GLOBALS['er_test_termes_de'][203] = array();
	$recent                            = new WC_Product( 203, array( 'er_annee' => '2025' ), array( 'regular' => '22', 'sale' => '18' ) );
	egal( '22', $recent->get_price() );
	egal( '', $recent->get_sale_price() );
	egal( '18', $recent->get_sale_price( 'edit' ), 'la fiche garde la remise saisie' );
	$GLOBALS['er_test_termes_de'][204] = array();
	$solde                             = new WC_Product( 204, array( 'er_annee' => '2015' ), array( 'regular' => '22', 'sale' => '11' ) );
	egal( '11', $solde->get_price() );
	vrai( ! apply_filters( 'woocommerce_coupon_is_valid_for_product', true, $recent ), 'pas de code promo sur un livre neuf au prix unique' );
	vrai( apply_filters( 'woocommerce_coupon_is_valid_for_product', true, $solde ), 'un code promo sur un livre soldé' );
	unset( $GLOBALS['er_test_aujourdhui'] );
}

function test_frais_de_port_minimum_selon_les_livres_neufs() {
	egal( 3.0, er_prix_port_minimum( 34.99 ) );
	egal( 0.01, er_prix_port_minimum( 35 ) );
}

/**
 * Un colis : des lignes (produit, total TTC).
 *
 * @param array $lignes Les lignes : array( WC_Product, total ).
 */
function er_test_colis( $lignes ) {
	$contents = array();
	foreach ( $lignes as list( $produit, $total ) ) {
		$contents[] = array(
			'data'       => $produit,
			'line_total' => $total,
			'line_tax'   => 0,
		);
	}
	return array( 'contents' => $contents );
}

function test_livraison_relevee_au_minimum_legal_sauf_le_retrait() {
	$GLOBALS['er_test_termes_de'][301] = array();
	$GLOBALS['er_test_termes_de'][302] = array( 'product_tag' => array( 'occasion' ) );
	$neuf                              = new WC_Product( 301 );
	$occasion                          = new WC_Product( 302 );

	$tarifs = er_prix_frais_de_port(
		array( new WC_Shipping_Rate( 'free_shipping', 0, 'Livraison gratuite' ), new WC_Shipping_Rate( 'local_pickup', 0 ), new WC_Shipping_Rate( 'flat_rate', 6.5 ) ),
		er_test_colis( array( array( $neuf, 15 ), array( $occasion, 40 ) ) )
	);
	egal( '3', (string) $tarifs[0]->get_cost(), 'moins de 35 € de livres neufs : 3 €, l’occasion ne compte pas' );
	egal( 'Livraison', $tarifs[0]->libelle, 'plus « gratuite »' );
	egal( 0, $tarifs[1]->get_cost(), 'le retrait à la librairie reste gratuit' );
	egal( 6.5, $tarifs[2]->get_cost(), 'un tarif déjà au-dessus ne bouge pas' );

	$tarifs = er_prix_frais_de_port( array( new WC_Shipping_Rate( 'free_shipping', 0 ) ), er_test_colis( array( array( $neuf, 40 ) ) ) );
	egal( '0.01', (string) $tarifs[0]->get_cost(), '35 € et plus : un centime' );

	$tarifs = er_prix_frais_de_port( array( new WC_Shipping_Rate( 'free_shipping', 0 ) ), er_test_colis( array( array( $occasion, 10 ) ) ) );
	egal( 0, $tarifs[0]->get_cost(), 'que de l’occasion : rien à relever' );
}

function test_livraison_minimum_ttc_avec_la_tva() {
	$GLOBALS['er_test_taux_livraison'] = 5.5;
	$tarifs                            = er_prix_frais_de_port( array( new WC_Shipping_Rate( 'flat_rate', 1 ) ), er_test_colis( array( array( new WC_Product( 301 ), 12 ) ) ) );
	egal( 3.0, round( (float) $tarifs[0]->get_cost() + array_sum( $tarifs[0]->get_taxes() ), 2 ), '3 € toutes taxes comprises' );
	egal( 2.8436, (float) $tarifs[0]->get_cost() );
	unset( $GLOBALS['er_test_taux_livraison'] );
}
