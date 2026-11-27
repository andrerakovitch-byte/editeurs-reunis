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

function test_le_nom_dans_l_autre_ecriture() {
	egal( 'Александр Солженицын', er_nom_russe( array( 'А. Солженицын', 'Alexandre Soljénitsyne', 'Александр Солженицын' ), 'Alexandre Soljenitsyne' ), 'la forme entière plutôt que l’initiale' );
	egal( 'Georgi Fedotov', er_nom_russe( array( 'Г. Федотов', 'Georgi Fedotov', 'G. Fedotov' ), 'Георгий Федотов' ), 'en latin sous un nom cyrillique' );
	egal( 'Борис Пастернак', er_nom_russe( array( 'Б. Пастернак' ), 'Boris Pasternak', 'Борис Пастернак' ), 'le nom saisi d’abord' );
	egal( 'П.Б. Струве', er_nom_russe( array( 'П.Б. Струве' ), 'Pierre Struve' ), 'une initiale, faute de mieux' );
	egal( '', er_nom_russe( array( 'Solzhenitsyn' ), 'Soljenitsyne' ) );
	egal( '', er_nom_russe( array() ) );
}

function test_les_annees_d_un_auteur() {
	$GLOBALS['er_test_metas_termes'][801] = array( 'er_naissance' => 'Kislovodsk, 11 décembre 1918', 'er_deces' => 'Moscou, 3 août 2008' );
	$GLOBALS['er_test_metas_termes'][802] = array( 'er_naissance' => 'Moscou, 1950' );
	$GLOBALS['er_test_metas_termes'][803] = array( 'er_deces' => '1937' );
	egal( '1918–2008', er_annees_auteur( 801 ) );
	egal( '1950–', er_annees_auteur( 802 ), 'vivant' );
	egal( '', er_annees_auteur( 803 ), 'sans naissance, rien' );
}
