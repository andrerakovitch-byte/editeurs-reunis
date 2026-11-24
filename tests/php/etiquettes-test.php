<?php
/**
 * Les époques, les langues des livres et le fichier des étiquettes (er-librairie/inc/epoques.php,
 * er-bascule/inc/etiquettes.php, er-bascule/donnees/etiquettes.tsv).
 *
 * @package er-librairie
 */

function test_epoques_quatorze_dans_l_ordre_aux_slugs_uniques() {
	$e = er_epoques();
	egal( 14, count( $e ) );
	egal( 'premiers-siecles-et-byzance', reset( $e )[0] );
	egal( 'russie-de-poutine', end( $e )[0] );
	egal( 14, count( array_unique( array_column( $e, 0 ) ) ) );
}

function test_langues_des_livres_gardent_francais_et_russe() {
	$l = er_langues_livres();
	egal( 'francais', $l['fr'][0] );
	egal( 'russe', $l['ru'][0] );
	egal( 'Bilingue russe-français', $l['bi'][1] );
}

function test_sujets_fermes_reprennent_les_noms_du_site() {
	$s = er_etiquettes_sujets();
	egal( 30, count( $s ) );
	egal( 30, count( array_unique( $s ) ) );
	foreach ( array( 'Poésie', 'Émigration', 'Goulag et dissidence', 'Orthodoxie', 'Histoire de la maison' ) as $nom ) {
		egal( true, in_array( $nom, $s, true ), $nom );
	}
}

function test_fichier_des_etiquettes_n_a_que_des_codes_connus() {
	$livres = er_etiquettes_lire( ER_RACINE . '/plugin/er-bascule/donnees/etiquettes.tsv' );
	egal( true, count( $livres ) > 800, 'tous les livres' );
	$epoques = array_map( 'strval', array_keys( er_epoques() ) );
	$inconnus = array();
	foreach ( $livres as $id => $l ) {
		$inconnus = array_merge(
			$inconnus,
			array_diff( $l['langues'], array_keys( er_langues_livres() ) ),
			array_diff( $l['sujets'], array_keys( er_etiquettes_sujets() ) ),
			array_diff( array_merge( $l['ecriture'], $l['periode'] ), $epoques )
		);
		egal( true, (bool) $l['rayons'] && (bool) $l['langues'], "#$id a un rayon et une langue" );
	}
	egal( array(), array_values( array_unique( $inconnus ) ) );
	egal( array( 'Boris Pasternak' ), $livres[541]['autour'] );
	egal( array( 'Jean-Paul Deschler', 'Pierre Gonneau' ), $livres[894]['auteurs'] );
	egal( array( 'fr', 'cu' ), $livres[894]['langues'] );
}
