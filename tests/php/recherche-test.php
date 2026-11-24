<?php
/**
 * La clé de recherche et l'initiale en cyrillique (plugin/er-librairie/inc/recherche.php).
 *
 * @package editeurs-reunis
 */

function test_cle_les_trois_ecritures_d_un_nom() {
	$groupes = array(
		array( 'Dostoïevski', 'Достоевский', 'Dostoevsky', 'dostoievski' ),
		array( 'Tchekhov', 'Чехов', 'Chekhov' ),
		array( 'Soljénitsyne', 'Солженицын', 'Solzhenitsyn' ),
		array( 'Akhmatova', 'Ахматова' ),
		array( 'Pasternak', 'Пастернак' ),
	);
	foreach ( $groupes as $noms ) {
		$cle = er_cle_recherche( $noms[0] );
		foreach ( $noms as $nom ) {
			egal( $cle, er_cle_recherche( $nom ), "« $nom » n'a pas la clé de « {$noms[0]} »" );
		}
	}
}

function test_cle_ote_balises_entites_et_ponctuation() {
	egal( er_cle_recherche( 'L’Archipel du Goulag' ), er_cle_recherche( '<em>l&rsquo;archipel</em>  du goulag !' ) );
	egal( '', er_cle_recherche( ' — ' ) );
}

function test_initiale_toujours_en_cyrillique() {
	$cas = array(
		'Joseph Brodsky'   => 'Б',
		'Varlam Chalamov'  => 'Ш',
		'Stefan Zweig'     => 'Ц',
		'Anton Tchekhov'   => 'Ч',
		'Iouri Mamleïev'   => 'М',
		'Marina Tsvetaeva' => 'Ц',
		'Ivan Bounine'     => 'Б',
	);
	foreach ( $cas as $nom => $lettre ) {
		egal( $lettre, er_initiale_cyrillique( $nom ), $nom );
	}
}

function test_initiale_prend_d_abord_le_nom_ecrit_en_cyrillique() {
	egal( 'Д', er_initiale_cyrillique( 'Fiodor Dostoïevski', array( 'Фёдор Достоевский' ) ) );
	egal( 'Ф', er_initiale_cyrillique( 'Фёдор Достоевский', array(), false ), 'le premier mot, pour un titre' );
	egal( '', er_initiale_cyrillique( '« »' ) );
}
