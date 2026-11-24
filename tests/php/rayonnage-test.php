<?php
/**
 * La grille des livres : les modules et les chapitres insérés dans la grille, le rangement par époque, le même livre
 * en deux langues (er-librairie/inc/rayonnage.php).
 *
 * @package er-librairie
 */

function er_test_grille( $ids ) {
	return '<ul class="er-livres">' . implode( '', array_map( fn( $i ) => '<li class="wc-block-product post-' . $i . ' product">' . $i . '</li>', $ids ) ) . '</ul>';
}

function test_rayonnage_modules_inseres_apres_le_bon_nombre_de_livres() {
	$html = er_rayonnage_inserer( er_test_grille( array( 1, 2, 3 ) ), array( 0 => '<li>A</li>', 2 => '<li>B</li>', 9 => '<li>C</li>' ) );
	egal( preg_replace( '/<li class="[^"]*">/', '<li>', $html ), '<ul class="er-livres"><li>A</li><li>1</li><li>2</li><li>B</li><li>3</li></ul>' );
}

function test_rayonnage_rien_apres_le_dernier_livre_ni_dans_une_grille_vide() {
	egal( er_rayonnage_inserer( er_test_grille( array( 1 ) ), array( 1 => '<li>B</li>' ) ), er_test_grille( array( 1 ) ) );
	egal( er_rayonnage_inserer( '<ul></ul>', array( 0 => '<li>A</li>' ) ), '<ul></ul>' );
}

function test_rayonnage_chapitres_avant_un_livre_selon_son_identifiant() {
	$html = er_rayonnage_inserer_avant( er_test_grille( array( 5, 6 ) ), fn( $id ) => 6 === $id ? '<li>X</li>' : '' );
	egal( substr_count( $html, '<li>X</li><li class="wc-block-product post-6' ), 1 );
}

function test_rayonnage_ordre_du_temps_par_la_plus_ancienne_epoque() {
	$livres = array(
		1 => array( 'epoque' => array( 'russie-de-poutine' ) ),
		2 => array( 'epoque' => array( 'la-guerre', 'age-d-argent' ) ),
		3 => array(),
		4 => array( 'epoque' => array( 'age-d-argent' ) ),
	);
	egal( er_rayonnage_ordonner( array( 1, 2, 3, 4 ), $livres, 'epoque' ), array( 2, 4, 1, 3 ) );
	egal( er_rayonnage_epoque_du_livre( $livres[2], 'epoque', er_rayonnage_rangs() ), 'age-d-argent' );
}

function test_rayonnage_cle_d_une_oeuvre_dans_deux_langues() {
	egal( er_rayonnage_cle_oeuvre( '« La Roue rouge. Avril 1917, t. 2 »' ), er_rayonnage_cle_oeuvre( 'La Roue rouge, chapitres choisis' ) );
	egal( er_rayonnage_cle_oeuvre( 'Anna Karénine' ), er_rayonnage_cle_oeuvre( '« Anna Karénine »' ) );
	egal( er_rayonnage_cle_oeuvre( 'Zoo' ), '' );
}

function test_rayonnage_paire_d_un_meme_auteur_seulement() {
	$livres = array(
		10 => array( 'langue' => array( 'francais' ), 'auteur' => array( 'soljenitsyne' ) ),
		11 => array( 'langue' => array( 'russe' ), 'auteur' => array( 'soljenitsyne' ) ),
		12 => array( 'langue' => array( 'russe' ), 'auteur' => array( 'autre' ) ),
	);
	$titres = array( 10 => 'La Roue rouge, chapitres choisis', 11 => 'Красное Колесо', 12 => 'Красное' );
	$gloses = array( 11 => '« La Roue rouge. Avril 1917 »', 12 => 'La Roue rouge' );
	egal( er_rayonnage_paire( $livres, fn( $i ) => $titres[ $i ], fn( $i ) => $gloses[ $i ] ?? '' ), array( 10, 11 ) );
	unset( $livres[11] );
	egal( er_rayonnage_paire( $livres, fn( $i ) => $titres[ $i ], fn( $i ) => $gloses[ $i ] ?? '' ), array() );
}

function test_carte_annee_d_une_parution_ecrite_a_la_main() {
	egal( er_carte_annee( 'Paris, 1972' ), '1972' );
	egal( er_carte_annee( '2024-2025' ), '2024' );
	egal( er_carte_annee( 's. d.' ), '' );
}
