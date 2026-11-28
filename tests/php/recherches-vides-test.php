<?php
/**
 * Les recherches restées sans résultat (plugin/er-librairie/inc/recherches-vides.php).
 *
 * @package editeurs-reunis
 */

function test_ce_qu_on_garde_d_une_recherche_vide() {
	vrai( er_recherches_vides_gardable( 'Zamiatine' ), 'un nom' );
	vrai( er_recherches_vides_gardable( 'Цветаева' ), 'en cyrillique' );
	vrai( er_recherches_vides_gardable( '978-2-07-036024-6' ), 'un ISBN-13' );
	vrai( er_recherches_vides_gardable( '2-07-036024-5' ), 'un ISBN-10' );
	egal( false, er_recherches_vides_gardable( 'ab' ), 'trop court' );
	egal( false, er_recherches_vides_gardable( 'anna@exemple.fr' ), 'une adresse électronique' );
	egal( false, er_recherches_vides_gardable( 'https://exemple.fr' ), 'un lien' );
	egal( false, er_recherches_vides_gardable( '06 12 34 56 78' ), 'un numéro de téléphone' );
	egal( false, er_recherches_vides_gardable( '???' ), 'rien à lire' );
}

function test_une_recherche_vide_s_ajoute_et_se_compte() {
	$t     = 2000000000;
	$liste = er_recherches_vides_ajouter( array(), 'zamiatine', 'Zamiatine', 'panneau', 'fr', $t );
	$liste = er_recherches_vides_ajouter( $liste, 'zamiatine', 'zamiatine', 'page', 'fr', $t + 60 );
	egal( 2, $liste['zamiatine']['fois'] );
	egal( 'page', $liste['zamiatine']['source'], 'cherchée une fois sur la page des résultats' );
	$liste = er_recherches_vides_ajouter( $liste, 'zamiatine', 'Zamiatine', 'panneau', 'fr', $t + 120 );
	egal( 'page', $liste['zamiatine']['source'], 'elle le reste' );

	$vieille = array(
		'ancien' => array( 'texte' => 'ancien', 'fois' => 4, 'derniere' => $t - YEAR_IN_SECONDS - 1, 'source' => 'panneau', 'langue' => 'fr' ),
	);
	egal( array( 'neuf' ), array_keys( er_recherches_vides_ajouter( $vieille, 'neuf', 'neuf', 'panneau', 'fr', $t ) ), 'plus d’un an : oubliée' );

	$pleine = array();
	for ( $i = 0; $i < 500; $i++ ) {
		$pleine[ 'r' . $i ] = array( 'texte' => 'r' . $i, 'fois' => 1, 'derniere' => $t - 1000 + $i, 'source' => 'panneau', 'langue' => 'fr' );
	}
	$pleine = er_recherches_vides_ajouter( $pleine, 'nouvelle', 'nouvelle', 'panneau', 'fr', $t );
	egal( 500, count( $pleine ), '500 au plus' );
	egal( false, isset( $pleine['r0'] ), 'la plus ancienne s’en va' );
	vrai( isset( $pleine['nouvelle'] ), 'la nouvelle reste' );
}

function test_les_debuts_d_une_frappe_sous_la_recherche_la_plus_longue() {
	$r     = fn( $texte, $fois, $quand = 1 ) => array( 'texte' => $texte, 'fois' => $fois, 'derniere' => $quand, 'source' => 'panneau', 'langue' => 'fr' );
	$liste = array(
		'zam'       => $r( 'zam', 1 ),
		'zamia'     => $r( 'zamia', 1 ),
		'zamiatine' => $r( 'zamiatine', 1 ),
		'tsvet'     => $r( 'tsvet', 5 ),
		'tsvetaeva' => $r( 'tsvetaeva', 2 ),
		'brodski'   => $r( 'brodski', 3, 9 ),
	);
	$vue   = er_recherches_vides_regrouper( $liste );
	egal( array( 'brodski', 'tsvet', 'tsvetaeva', 'zamiatine' ), array_keys( $vue ), 'la frappe de « zamiatine » en une ligne ; de la plus fréquente à la plus rare, la plus récente d’abord à égalité' );
	egal( 3, $vue['tsvet']['fois'], 'cherché plus souvent que sa suite : il reste, pour l’écart' );
}
