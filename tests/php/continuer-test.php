<?php
/**
 * « Pour aller plus loin » : les mots et le choix des modules (er-librairie/inc/continuer-modules.php).
 *
 * @package er-librairie
 */

/**
 * Un petit catalogue pour les tests : des livres, des personnes, des rayons.
 */
function er_test_continuer_index() {
	$personnes = array(
		1 => array( 'n' => 'Vassili Grossman', 'nb' => 6 ),
		2 => array( 'n' => 'Anne Coldefy-Faucard', 'nb' => 0 ),
		3 => array( 'n' => 'Maria Stepanova', 'nb' => 2 ),
		4 => array( 'n' => 'Anne-Marie Pelletier', 'nb' => 1 ),
		5 => array( 'n' => 'Andreï Guelassimov', 'nb' => 2 ),
		6 => array( 'n' => 'Sacha Filipenko', 'nb' => 5 ),
		7 => array( 'n' => 'Iouri Riabinkine', 'nb' => 1 ),
		8 => array( 'n' => 'Anna Akhmatova', 'nb' => 9 ),
		9 => array( 'n' => 'Ал. Архангельский', 'nb' => 1, 'g' => array() ),
	);
	$rayons    = array(
		18 => array( 'sl' => 'romans', 'n' => 'Romans', 'pa' => 17, 'gr' => 'Par genre' ),
		20 => array( 'sl' => 'poesie', 'n' => 'Poésie', 'pa' => 17, 'gr' => 'Par genre' ),
		22 => array( 'sl' => 'memoires', 'n' => 'Mémoires et témoignages', 'pa' => 17, 'gr' => 'Par genre' ),
		24 => array( 'sl' => 'essais', 'n' => 'Essais', 'pa' => 17, 'gr' => 'Par genre' ),
	);
	$livres    = array(
		100 => array( 't' => 'Vie et Destin', 'k' => 'vi et destin', 'kt' => 'vi et destin', 'a' => array( 1 ), 'c' => array( 18 ), 'l' => array( 'francais' ), 'p' => array( 'la-guerre' ), 'st' => 1, 'r' => array( 'traduction' => array( 2 ) ) ),
		101 => array( 't' => 'Pour une juste cause', 'k' => 'pur un just caus', 'kt' => 'pur un just caus', 'a' => array( 1 ), 'c' => array( 18 ), 'l' => array( 'francais' ), 'p' => array( 'la-guerre' ), 'st' => 1, 'ph' => 1 ),
		102 => array( 't' => 'Résister avec Vassili Grossman', 'k' => 'resister avec vasili grosman', 'kt' => 'resister avec vasili grosman', 'a' => array( 4 ), 'ab' => array( 1 ), 'c' => array( 24 ), 'l' => array( 'francais' ), 'st' => 1 ),
		103 => array( 't' => 'En mémoire de la mémoire', 'k' => 'en memoir d la memoir', 'kt' => 'en memoir d la memoir', 'a' => array( 3 ), 'c' => array( 18 ), 'l' => array( 'francais' ), 'st' => 1, 'r' => array( 'traduction' => array( 2 ) ) ),
		104 => array( 't' => 'Les dieux de la steppe', 'k' => 'les dieu d la step', 'kt' => 'les dieu d la step', 'a' => array( 5 ), 'c' => array( 18 ), 'l' => array( 'francais' ), 'p' => array( 'la-guerre' ), 'st' => 1 ),
		105 => array( 't' => 'Красный крест', 'k' => 'krasni krest', 'kt' => 'krasni krest', 'a' => array( 6 ), 'c' => array( 18 ), 'l' => array( 'russe' ), 'p' => array( 'la-guerre' ), 'st' => 0, 'fr' => 'La Croix-Rouge' ),
		106 => array( 't' => 'Le Siège de Leningrad, Journal d’un adolescent', 'k' => 'l sieg d leningrad', 'kt' => 'l sieg d leningrad journal d un adolescent', 'a' => array( 7 ), 'c' => array( 22 ), 'l' => array( 'francais' ), 'p' => array( 'la-guerre' ), 'st' => 1 ),
		107 => array( 't' => 'La guerre', 'k' => 'la guer', 'kt' => 'la guer', 'a' => array( 8 ), 'c' => array( 20 ), 'l' => array( 'francais' ), 'p' => array( 'la-guerre' ), 'st' => 1 ),
		108 => array( 't' => 'Les Russes blancs – Volume 1', 'k' => 'les rus blancs', 'kt' => 'les rus blancs volum 1', 'a' => array( 9 ), 'c' => array( 24 ), 'l' => array( 'francais' ), 'st' => 1 ),
		109 => array( 't' => 'Les Russes blancs – Volume 2', 'k' => 'les rus blancs', 'kt' => 'les rus blancs volum 2', 'a' => array( 9 ), 'c' => array( 24 ), 'l' => array( 'francais' ), 'st' => 1 ),
	);
	return array(
		'quand'     => 1,
		'livres'    => $livres,
		'personnes' => $personnes,
		'rayons'    => $rayons,
		'sujets'    => array(),
		'contenus'  => array(
			500 => array( 'ty' => 'post', 't' => 'Résister avec Vassily Grossman', 'd' => 1, 'a' => array( 1, 4 ), 'lg' => 'fr' ),
		),
	);
}

function test_continuer_de_elide_et_contracte() {
	egal( 'de Grossman', er_continuer_de( 'Grossman' ), 'une consonne' );
	egal( 'd’Akhmatova', er_continuer_de( 'Akhmatova' ), 'une voyelle' );
	egal( 'de Iouri Riabinkine', er_continuer_de( 'Iouri Riabinkine' ), 'un i qui se dit comme une consonne' );
	egal( 'd’Ivan Bounine', er_continuer_de( 'Ivan Bounine' ), 'un i voyelle' );
	egal( 'd’Yves Gauthier', er_continuer_de( 'Yves Gauthier' ), 'un y voyelle' );
	egal( 'du {{Docteur Jivago}}', er_continuer_de( '{{Le Docteur Jivago}}' ), 'l’article d’un titre se contracte' );
	egal( 'des {{Frères Karamazov}}', er_continuer_de( '{{Les Frères Karamazov}}' ), 'au pluriel aussi' );
	egal( 'de {{La Garde blanche}}', er_continuer_de( '{{La Garde blanche}}' ), 'pas « la »' );
	egal( 'd’{{En mémoire de la mémoire}}', er_continuer_de( '{{En mémoire de la mémoire}}' ), 'un titre qui commence par une voyelle' );
	egal( 'd’Архангельский', er_continuer_de( 'Архангельский' ), 'le cyrillique' );
	egal( 'de Юрий', er_continuer_de( 'Юрий' ), 'ю se dit avec un i' );
}

function test_continuer_noms_courts() {
	egal( 'Pouchkine', er_continuer_nom_court( 'Alexandre Pouchkine' ) );
	egal( 'Coldefy-Faucard', er_continuer_nom_court( 'Anne Coldefy-Faucard' ) );
	egal( 'Meyendorff', er_continuer_nom_court( 'P. Jean Meyendorff' ), 'le titre tombe' );
	egal( 'Maurice de Gandillac', er_continuer_nom_court( 'Maurice de Gandillac' ), 'une particule garde le nom entier' );
	egal( 'saint Séraphin de Sarov', er_continuer_nom_court( 'Saint Séraphin de Sarov' ), 'un saint garde tout son nom' );
	egal( 'Dante', er_continuer_nom_court( 'Dante Alighieri' ) );
	egal( 'Ilf et Pétrov', er_continuer_nom_court( 'Ilf et Pétrov' ), 'un duo' );
	$ix = er_test_continuer_index();
	egal( array( 'Grossman', 'Akhmatova' ), er_continuer_noms_ligne( array( 1, 8 ), $ix ), 'toutes connues : tous courts' );
	egal( array( 'Maria Stepanova', 'Anna Akhmatova' ), er_continuer_noms_ligne( array( 3, 8 ), $ix ), 'une inconnue : tous entiers' );
}

function test_continuer_listes_phrases_et_titres() {
	egal( 'a, b et c', er_continuer_liste( array( 'a', 'b', 'c' ) ) );
	egal( 'a', er_continuer_liste( array( 'a', '' ) ) );
	egal( 'Deux romans.', er_continuer_phrase( 'deux romans' ) );
	egal( '{{Pour une juste cause}} ouvre le diptyque.', er_continuer_phrase( '{{pour une juste cause}} ouvre le diptyque' ), 'la capitale passe la marque' );
	egal( 'Crime et châtiment', er_continuer_titre_court( 'Crime et châtiment suivi de Journal de Raskolnikov' ) );
	egal( 'Anna Akhmatova, requiem pour l’Europe', er_continuer_titre_court( 'Anna Akhmatova, requiem pour l’Europe' ), 'pas de coupe avant une virgule après deux mots' );
	egal( 'Les songes de Tchang', er_continuer_titre_court( 'Les songes de Tchang. Poèmes' ) );
	egal( array( 2, 'tome' ), er_continuer_numero( 'Красное Колесо – Апрель Семнадцатого т. 2' ) );
	egal( array( 3, 'volume' ), er_continuer_numero( 'Œuvres, vol. III' ) );
	egal( array( 0, '' ), er_continuer_numero( 'Vie et Destin' ) );
	egal( 'Le Messager orthodoxe', er_continuer_revue( 'Le Messager orthodoxe, n° 168-169' ) );
}

function test_continuer_sortes() {
	$ix = er_test_continuer_index();
	egal( 'roman', er_continuer_sorte( $ix['livres'][100], $ix['rayons'] ) );
	egal( 'journal', er_continuer_sorte( $ix['livres'][106], $ix['rayons'] ), 'un témoignage dont le titre dit le journal' );
	egal( 'roman', er_continuer_sorte( array( 't' => 'Le journal d’un fou', 'c' => array( 18 ) ), $ix['rayons'] ), 'la fiction garde son rayon' );
	egal( 'poemes', er_continuer_sorte( array( 't' => 'Стихотворения', 'c' => array() ), $ix['rayons'] ) );
	egal( 'revue', er_continuer_sorte( array( 't' => 'Вестник Русского христианского движения, № 219', 'c' => array() ), $ix['rayons'] ) );
}

function test_continuer_inventaire() {
	$ix = er_test_continuer_index();
	egal( 'Deux romans, d’Andreï Guelassimov et de Sacha Filipenko, un journal de Iouri Riabinkine et des poèmes d’Akhmatova.', er_continuer_inventaire( array( 104, 105, 106, 107 ), $ix, array( 'langue' => 'fr', 'sans_langues' => true ) ), 'les auteurs entre virgules, sous la même forme ; le reste nommé' );
	egal( 'Un roman de Filipenko en russe et des poèmes d’Akhmatova.', er_continuer_inventaire( array( 105, 107 ), $ix, array( 'langue' => 'fr' ) ), 'le russe se dit ; un auteur connu, par son nom' );
	egal( '', er_continuer_inventaire( array( 104 ), array_replace_recursive( $ix, array( 'personnes' => array( 5 => array( 'n' => 'Г. Катков' ) ) ) ), array( 'langue' => 'fr' ) ), 'un nom en cyrillique ne se dit pas : la phrase nue reste vide' );
	egal( 'Un essai d’Anne-Marie Pelletier et un article du journal du centre.', er_continuer_inventaire( array( 102 ), $ix, array( 'personne' => 1, 'contenus' => array( 500 ) ) ), 'la personne du module ne se redit pas' );
}

function test_continuer_vie_et_destin() {
	$ix = er_test_continuer_index();
	$r  = er_continuer_choisir(
		100,
		$ix,
		array(
			'notice' => array(
				array( 'Deuxième partie d’un diptyque consacré, avec Pour une juste cause, à la Seconde Guerre mondiale.', 'deuxiem parti d un diptiqu consacr avec pur un just caus a la second guer mondial' ),
			),
		)
	);
	$types = array_column( $r['modules'], 'type' );
	egal( 'cite', $types[0], 'le livre que cite la notice d’abord' );
	egal( 'Le diptyque', $r['modules'][0]['sur'] );
	egal( 'Commencer par le premier volume', $r['modules'][0]['titre'] );
	egal( '{{Pour une juste cause}} ouvre le diptyque ; {{Vie et Destin}} en est la suite.', $r['modules'][0]['ligne'] );
	egal( true, $r['modules'][0]['fond'], 'le premier sur le fond doux' );
	vrai( in_array( 'sur-auteur', $types, true ), 'ce qu’on a écrit sur Grossman' );
	vrai( in_array( 'traduction', $types, true ), 'la traductrice' );
	vrai( in_array( 'epoque', $types, true ), 'les mêmes années' );
	$tous = array();
	foreach ( $r['modules'] as $m ) {
		$tous = array_merge( $tous, $m['livres'] );
	}
	egal( count( $tous ), count( array_unique( $tous ) ), 'un livre ne paraît qu’une fois' );
	vrai( ! in_array( 100, $tous, true ), 'pas le livre lui-même' );
	vrai( 0 === strpos( $r['ouverture'], 'Après {{Vie et Destin}} : le premier volume du diptyque' ), 'l’ouverture : ' . $r['ouverture'] );
	foreach ( $r['modules'] as $m ) {
		if ( 'traduction' === $m['type'] ) {
			egal( 'On lui doit aussi la traduction d’{{En mémoire de la mémoire}}, de Maria Stepanova.', $m['ligne'] );
		}
		if ( 'epoque' === $m['type'] ) {
			egal( 'La guerre, 1941-1945', $m['titre'] );
			egal( 'Les mêmes années', $m['sur'] );
			vrai( ! in_array( 101, $m['livres'], true ), 'pas les livres de l’auteur, que la colonne montre' );
		}
	}
}

function test_continuer_trois_editions_d_un_livre_cite() {
	$ix                         = er_test_continuer_index();
	$ix['personnes'][10]        = array( 'n' => 'Mikhaïl Boulgakov', 'nb' => 15 );
	$maitre                     = array( 't' => 'Le Maître et Marguerite', 'k' => 'l metr et margerit', 'kt' => 'l metr et margerit', 'a' => array( 10 ), 'c' => array( 18 ), 'l' => array( 'francais' ), 'st' => 1 );
	$ix['livres'][110]          = $maitre + array( 'ed' => 'Barnum', 'an' => '2020' );
	$ix['livres'][111]          = $maitre + array( 'ed' => 'Inculte', 'an' => '2012' );
	$ix['livres'][112]          = $maitre + array( 'ed' => 'Robert Laffont', 'an' => '1997' );
	$ix['livres'][113]          = array( 't' => 'Le Roman de monsieur de Molière', 'k' => 'l roman d monseur d moler', 'kt' => 'l roman d monseur d moler', 'a' => array( 10 ), 'c' => array( 18 ), 'l' => array( 'francais' ), 'st' => 1 );
	$r                          = er_continuer_choisir( 113, $ix, array( 'notice' => array( array( 'Écrit en même temps que Le Maître et Marguerite.', 'ecrit en mem temp que l metr et margerit' ) ) ) );
	$cite                       = array_values( array_filter( $r['modules'], fn( $m ) => 'cite' === $m['type'] ) );
	egal( 1, count( $cite ), 'un module des livres cités' );
	egal( '{{Le Maître et Marguerite}}', $cite[0]['titre'], 'le titre une seule fois' );
	egal( 'En trois éditions : chez Barnum en 2020, chez Inculte en 2012 et chez Robert Laffont en 1997.', $cite[0]['ligne'] );
}

function test_continuer_volumes_et_nom_en_cyrillique() {
	$ix = er_test_continuer_index();
	$r  = er_continuer_choisir( 108, $ix );
	egal( 'volumes', $r['modules'][0]['type'] );
	egal( 'Le deuxième volume', $r['modules'][0]['titre'] );
	egal( 'La suite.', $r['modules'][0]['ligne'] );
	egal( array( 109 ), $r['modules'][0]['livres'] );
}

function test_continuer_tout_le_catalogue_sans_erreur() {
	$ix = er_test_continuer_index();
	foreach ( array_keys( $ix['livres'] ) as $id ) {
		$r = er_continuer_choisir( $id, $ix );
		foreach ( $r['modules'] as $m ) {
			vrai( '' !== $m['sur'] && '' !== $m['titre'], $id . ' : un module a son surtitre et son titre' );
			vrai( '' === $m['ligne'] || 1 === preg_match( '/[.!?…»]$/u', $m['ligne'] ), $id . ' : la ligne finit par un point' );
		}
	}
	egal( array( 'ouverture' => '', 'modules' => array() ), er_continuer_choisir( 999, $ix ), 'un livre absent' );
}
