<?php
/**
 * La bascule : le livre que désigne une ancienne adresse de Wix (plugin/er-librairie/inc/bascule.php). Les noms viennent
 * du plan du site de Wix (store-products-sitemap.xml, 847 adresses).
 *
 * @package editeurs-reunis
 */

/**
 * Des livres, par leurs clés, comme l'index de recherche les garde.
 *
 * @param array $livres Identifiant → array( titre, auteurs ).
 */
function er_test_livres_index( $livres ) {
	$cles = array();
	foreach ( $livres as $id => list( $titre, $auteurs ) ) {
		$cles[ $id ] = array(
			'titre' => er_cle_recherche( $titre ),
			'tout'  => er_cle_recherche( $titre . ' ' . $auteurs ),
		);
	}
	return $cles;
}

function test_bascule_le_titre_ouvre_le_nom_de_la_page() {
	$livres = er_test_livres_index(
		array(
			1 => array( 'Dieu et l’État', 'Michel Bakounine' ),
			2 => array( 'Dieu et l’homme, la foi et le doute', 'Antoine Bloom' ),
			3 => array( 'Dieu', 'Anonyme' ),
			4 => array( 'Смерть Андрея Белого (1880-1934)', '' ),
			5 => array( 'Святые Древней Руси', 'Георгий Федотов' ),
		)
	);
	egal( 1, er_bascule_livre( 'dieu-et-l-etat-bakounine', $livres ) );
	egal( 2, er_bascule_livre( 'dieu-et-l-homme-la-foi-et-le-doute-monseigneur-antoine-bloom', $livres ), 'le titre le plus long' );
	egal( 4, er_bascule_livre( 'смерть-андрея-белого-1880-1934', $livres ), 'en cyrillique' );
	egal( 5, er_bascule_livre( 'святые-древней-руси-георгий-фeдотов', $livres ), 'un « e » latin dans le nom russe' );
	egal( 0, er_bascule_livre( 'brisbane-evgueni-vodolazkine', $livres ) );
}

function test_bascule_deux_editions_et_les_doublons_de_wix() {
	$livres = er_test_livres_index(
		array(
			10 => array( 'Ce n’était que la peste', 'Ludmila Oulitskaïa' ),
			11 => array( 'L’Histoire de ma sœur', 'Michel Ossorguine' ),
			12 => array( 'Correspondance', 'Marina Tsvetaeva' ),
			13 => array( 'Correspondance', 'Anton Tchekhov' ),
		)
	);
	egal( 10, er_bascule_livre( 'ce-n-était-que-la-peste-ludmila-oulitskaïa-1', $livres ), '« -1 » : un doublon de Wix' );
	egal( 11, er_bascule_livre( 'l-histoire-de-ma-sœur-michel-ossorguine', $livres ) );
	egal( 13, er_bascule_livre( 'correspondance-anton-tchekhov', $livres ), 'à titre égal, l’auteur qui suit' );
}
