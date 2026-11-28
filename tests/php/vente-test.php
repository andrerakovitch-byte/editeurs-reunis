<?php
/**
 * La vente à distance : le bouton de commande, la rétractation en ligne (plugin/er-librairie/inc/vente-a-distance.php).
 *
 * @package editeurs-reunis
 */

function test_le_bouton_de_commande_dit_qu_on_paie() {
	egal( 'Commander et payer', er_vente_libelle_commande() );
	egal(
		'<div data-place-order-button-label="Commander et payer" data-block-name="woocommerce/checkout-actions-block" class="x"></div>',
		er_vente_poser_libelle( '<div data-block-name="woocommerce/checkout-actions-block" class="x"></div>', 'Commander et payer' ),
		'le libellé par défaut : on l’ajoute'
	);
	egal(
		'<div data-block-name="a" data-place-order-button-label="Commander et payer"><div></div></div>',
		er_vente_poser_libelle( '<div data-block-name="a" data-place-order-button-label="Place Order"><div></div></div>', 'Commander et payer' ),
		'un libellé saisi : on le remplace, une seule fois'
	);
	egal( '<div data-place-order-button-label="A &amp; B"></div>', er_vente_poser_libelle( '<div></div>', 'A & B' ), 'échappé' );
}

function test_une_declaration_de_retractation() {
	list( $d, $erreurs ) = er_retractation_lire(
		array(
			'nom'      => '  Anna <b>Akhmatova</b> ',
			'commande' => '1234',
			'courriel' => 'anna@exemple.fr',
		)
	);
	egal( array(), $erreurs );
	egal( 'Anna Akhmatova', $d['nom'] );
	egal( '', $d['precision'] );

	list( , $erreurs ) = er_retractation_lire( array( 'courriel' => 'pas une adresse' ) );
	egal( array( 'nom', 'courriel', 'commande' ), array_keys( $erreurs ), 'nom, adresse et commande manquent' );

	list( , $erreurs ) = er_retractation_lire(
		array(
			'nom'       => 'Anna',
			'courriel'  => 'anna@exemple.fr',
			'precision' => 'Commandée en mars sur l’ancien site : deux livres de Tsvetaïeva.',
		)
	);
	egal( array(), $erreurs, 'sans numéro, une description suffit : jamais de refus faute de numéro' );
}

function test_l_accuse_de_reception_porte_la_declaration_sa_date_et_son_heure() {
	$d     = array(
		'nom'       => 'Anna Akhmatova',
		'commande'  => '1234',
		'courriel'  => 'anna@exemple.fr',
		'precision' => 'Seulement le premier livre.',
	);
	$texte = er_retractation_texte( $d, '28 novembre 2026 à 14 h 05' );
	vrai( str_contains( $texte, 'envoyée le 28 novembre 2026 à 14 h 05' ), 'la date et l’heure' );
	vrai( str_contains( $texte, 'je renonce au contrat de vente de la commande n° 1234' ), 'la déclaration' );
	vrai( str_contains( $texte, 'Précision : Seulement le premier livre.' ), 'la précision' );
	vrai( str_contains( er_retractation_texte( array_merge( $d, array( 'commande' => '' ) ), 'hier' ), 'commande n° non précisée' ), 'sans numéro' );
}

function test_la_confirmation_porte_l_empreinte_de_la_declaration() {
	$d = array(
		'nom'       => 'Anna',
		'commande'  => '1234',
		'courriel'  => 'anna@exemple.fr',
		'precision' => '',
	);
	egal( er_retractation_empreinte( $d ), er_retractation_empreinte( $d ) );
	vrai( er_retractation_empreinte( $d ) !== er_retractation_empreinte( array_merge( $d, array( 'commande' => '1235' ) ) ), 'une déclaration changée ne se confirme pas' );
}
