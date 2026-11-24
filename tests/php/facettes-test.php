<?php
/**
 * « Filtrer par » : le filtre lu dans l'adresse, les comptes et les livres retenus (er-librairie/inc/facettes.php).
 *
 * @package er-librairie
 */

/**
 * Quatre livres : deux romans en français, un bilingue, un russe sans date.
 */
function er_test_facettes_livres() {
	return array(
		1 => array( 'langue' => array( 'francais' ), 'epoque' => array( 'annees-1920-et-1930' ), 'genre' => array( 'romans' ), 'decennie' => array( '2010' ), 'dispo' => array( 'stock' ) ),
		2 => array( 'langue' => array( 'francais' ), 'epoque' => array( 'la-guerre' ), 'genre' => array( 'romans' ), 'decennie' => array( '2020' ), 'dispo' => array( 'stock', 'nouveaute' ) ),
		3 => array( 'langue' => array( 'bilingue-russe-francais' ), 'epoque' => array( 'age-d-argent' ), 'genre' => array( 'poesie' ), 'decennie' => array( '2020' ) ),
		4 => array( 'langue' => array( 'russe' ), 'epoque' => array( 'annees-1920-et-1930' ), 'genre' => array( 'memoires' ), 'decennie' => array( 'sans' ) ),
	);
}

function test_facettes_selection_lit_virgules_et_crochets() {
	$sel = er_facettes_selection(
		array(
			'langues'   => 'francais,bilingue-russe-francais',
			'genres'    => array( 'romans', 'Poésie' ),
			'sans-date' => 'non',
		)
	);
	egal( array( 'francais', 'bilingue-russe-francais' ), $sel['langue'] );
	egal( array( 'romans', 'poesie' ), $sel['genre'] );
	egal( false, $sel['sans_date'] );
	egal( true, er_facettes_selection( array() )['sans_date'], 'avec les livres sans date par défaut' );
	egal( true, er_facettes_selection( array( 'sans-date' => array( 'non', 'oui' ) ) )['sans_date'], 'la case cochée l’emporte sur le champ caché' );
	egal( false, er_facettes_actives( er_facettes_selection( array() ) ) );
}

function test_facettes_ou_dans_une_facette_et_d_une_facette_a_l_autre() {
	$livres = er_test_facettes_livres();
	$sel    = er_facettes_selection( array( 'langues' => 'francais,bilingue-russe-francais' ) );
	egal( array( 1, 2, 3 ), er_facettes_resultats( $livres, $sel ) );
	$sel = er_facettes_selection( array( 'langues' => 'francais,bilingue-russe-francais', 'genres' => 'romans' ) );
	egal( array( 1, 2 ), er_facettes_resultats( $livres, $sel ) );
}

function test_facettes_comptes_sans_la_facette_comptee() {
	$livres = er_test_facettes_livres();
	$sel    = er_facettes_selection( array( 'langues' => 'francais', 'genres' => 'romans' ) );
	// Les langues se comptent comme si aucune n'était cochée, mais avec le genre : deux romans en français.
	egal( array( 'francais' => 2 ), er_facettes_compter( $livres, $sel, 'langue' ) );
	egal( array( 'romans' => 2 ), er_facettes_compter( $livres, $sel, 'genre' ) );
	egal( 1, er_facettes_compter( $livres, $sel, 'dispo' )['nouveaute'] );
}

function test_facettes_livres_sans_date() {
	$livres = er_test_facettes_livres();
	egal( array( 1, 2, 3, 4 ), er_facettes_resultats( $livres, er_facettes_selection( array() ) ) );
	egal( array( 1, 2, 3 ), er_facettes_resultats( $livres, er_facettes_selection( array( 'sans-date' => 'non' ) ) ) );
	egal( array( 2, 3, 4 ), er_facettes_resultats( $livres, er_facettes_selection( array( 'decennies' => '2020' ) ) ), 'une décennie garde les livres sans date si on les veut' );
	egal( array( '2010' => 1, '2020' => 2, 'sans' => 1 ), er_facettes_compter( $livres, er_facettes_selection( array( 'decennies' => '2020' ) ), 'decennie' ) );
}

function test_facettes_decennie_d_une_annee_ecrite_a_la_main() {
	egal( '1980', er_facettes_decennie( '2-е изд., 1987' ) );
	egal( '2020', er_facettes_decennie( '2021' ) );
	egal( 'sans', er_facettes_decennie( '' ) );
	egal( 'sans', er_facettes_decennie( '128 стр.' ) );
}
