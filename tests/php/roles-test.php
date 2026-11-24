<?php
/**
 * Les rôles d'un livre, les éditeurs et les personnes (plugin/er-librairie/inc/traducteurs-editeurs.php et
 * collections.php). Les cas viennent du catalogue de Wix : chaque mauvais nom trouvé un jour devient un cas ici.
 *
 * @package editeurs-reunis
 */

// Les collections du catalogue, lues une fois par er_roles_maisons() : posées avant le premier test.
er_test_termes( 'er_collection', array( 'Pavillons Poche' ) );

function test_noms_coupes_sur_et_virgules_et_esperluettes() {
	egal( array( 'Hélène Henry', 'Michel Aucouturier' ), er_roles_noms( 'Hélène Henry et Michel Aucouturier' ) );
	egal( array( 'Georges Nivat', 'Anne Coldefy-Faucard' ), er_roles_noms( 'Traduit du russe par Georges Nivat & Anne Coldefy-Faucard' ) );
	egal( array( 'Pierre Léon' ), er_roles_noms( 'Traduit de l’anglais par Pierre Léon' ) );
}

function test_noms_ecartes_avec_leur_raison() {
	$ecart = array();
	$noms  = er_roles_noms( 'Françoise LhoestEnfant de Paris, Rilke, Illustrations de Bilibine ; Martin de Tours', 'Vie de Martin de Tours', $ecart );
	egal( array( 'Françoise Lhoest' ), $noms, 'le texte de Wix a perdu ses retours à la ligne' );
	egal(
		array(
			'Enfant de Paris'           => 'autre rôle',
			'Rilke'                     => 'un seul mot',
			'Illustrations de Bilibine' => 'autre rôle',
			'Martin de Tours'           => 'dans le titre',
		),
		$ecart
	);
}

function test_releve_des_roles_d_un_livre() {
	er_test_article(
		101,
		array(
			'post_title'   => 'Le Pavillon des cancéreux',
			'post_content' => 'Traduit du russe par Alfreda Aucouturier et Lucile Nivat. Préface de Georges Nivat. Illustrations de Misha Zaslavsky.',
			'metas'        => array( 'er_editeur' => 'Русский путь / YMCA-Press' ),
		)
	);
	$r = er_roles_releves( 101 );
	egal( array( 'Alfreda Aucouturier', 'Lucile Nivat' ), array_keys( $r['traduction'] ) );
	egal( array( 'Georges Nivat' ), array_keys( $r['preface'] ) );
	egal( array( 'Misha Zaslavsky' ), array_keys( $r['illustration'] ) );
	egal( array( 'Русский путь', 'YMCA-Press' ), $r['editeurs'], 'une co-édition donne deux éditeurs' );
}

function test_releve_sans_les_faux_noms_du_24_novembre() {
	er_test_article(
		102,
		array(
			'post_title'   => 'Requiem',
			'post_content' => 'Illustrations de Actes Sud BDMisha Zaslavsky. Préface de Autobiographie d’Anna Akhmatova. Notes de Pasquierь Durand.',
		)
	);
	$r = er_roles_releves( 102 );
	egal( array( 'Misha Zaslavsky' ), array_keys( $r['illustration'] ), '« Actes Sud BDMisha » : deux majuscules collées coupent le nom' );
	egal( array(), $r['preface'], '« Autobiographie d’Anna Akhmatova » est un titre' );
	egal( array(), $r['edition'], '« Pasquierь » mêle le latin et le cyrillique' );
}

function test_traducteurs_sans_les_maisons_d_edition_du_24_novembre() {
	er_test_article(
		104,
		array(
			'post_title'   => 'Vie et destin',
			'post_content' => 'Traduit du russe par Michèle KahnRobert Laffont, Pavillons Poche. Traduit de l’anglais par Pierre Léon et Michel Lafon.',
		)
	);
	$r = er_roles_releves( 104 );
	egal( array( 'Michèle Kahn', 'Pierre Léon' ), array_keys( $r['traduction'] ), '« Traduit » avec une majuscule, sans les éditeurs collés au nom' );
	egal( 'pas une personne', $r['ecart']['Robert Laffont'] );
	egal( 'pas une personne', $r['ecart']['Michel Lafon'] );
	vrai( er_roles_pas_une_personne( 'Pavillons Poche' ), 'une collection du catalogue' );
	vrai( er_roles_pas_une_personne( 'Suivi de Le' ), 'un bout de phrase' );
	vrai( ! er_roles_pas_une_personne( 'Hélène Henry' ), 'une personne' );
}

function test_direction_lue_dans_le_titre() {
	er_test_article( 103, array( 'post_title' => 'Le Messager — sous la direction de Nikita Struve' ) );
	egal( array( 'Nikita Struve' ), array_keys( er_roles_releves( 103 )['direction'] ) );
}

function test_cle_d_un_editeur_et_ses_variantes() {
	egal( er_editeur_cle( 'Fayard' ), er_editeur_cle( 'Éditions Fayard' ) );
	egal( er_editeur_cle( 'Éditions EHESS' ), er_editeur_cle( 'Éditions de l’EHESS' ) );
	egal( er_editeur_cle( 'Institut dEtudes Slaves' ), er_editeur_cle( 'Institut d’études slaves' ) );
	egal( er_editeur_cle( 'Noir sur Blanc' ), er_editeur_cle( 'Les Éditions Noir sur blanc' ) );
	egal( er_editeur_cle( 'Le Seuil' ), er_editeur_cle( 'Éditions du Seuil' ) );
	egal( er_editeur_cle( 'Les Éditeurs réunis' ) === er_editeur_cle( 'YMCA-Press' ), false );
}

function test_editeurs_regroupes_a_une_lettre_pres() {
	$r = er_editeurs_regroupes(
		array(
			'Barthillat'      => 3,
			'Bartillat'       => 1,
			'Ginkgo'          => 2,
			'Gingko'          => 1,
			'Fayard'          => 5,
			'Éditions Fayard' => 1,
		)
	);
	egal( 'Barthillat', $r['Bartillat'] );
	egal( 'Ginkgo', $r['Gingko'] );
	egal( 'Fayard', $r['Éditions Fayard'] );
}

function test_personnes_regroupees() {
	er_test_termes( 'auteur', array( 'Natalia Soljénitsyne' ) );
	$r = er_personnes_regroupees(
		array(
			'Natalia Soljénistyne' => 1,
			'Paul Lesquesne'       => 1,
			'Paul Lequesne'        => 3,
			'G. Arout'             => 1,
			'Gabriel Arout'        => 2,
			'Boris de Schloezer'   => 1,
			'Boris de Schlœzer'    => 2,
			'Hélène Henry'         => 4,
		)
	);
	egal( 'Natalia Soljénitsyne', $r['Natalia Soljénistyne'], 'un auteur qui existe déjà l’emporte' );
	egal( 'Paul Lequesne', $r['Paul Lesquesne'], 'la graphie la plus fréquente' );
	egal( 'Gabriel Arout', $r['G. Arout'], 'la forme complète l’emporte sur l’initiale' );
	egal( 'Boris de Schlœzer', $r['Boris de Schloezer'] );
	egal( 'Hélène Henry', $r['Hélène Henry'] );
}

function test_collection_remise_au_propre() {
	$cas = array(
		'GF n° 1466'                                          => 'GF',
		'ВМБ № 12'                                            => 'ВМБ',
		'Le sel de la terre. Spiritualité'                    => 'Le sel de la terre',
		'Kuro Savoir : Une collection inédite pour expliquer' => 'Kuro Savoir',
		'Père Castor (Flammarion)'                            => 'Père Castor',
		'« Folio »'                                           => 'Folio',
		'Вестник РХД'                                         => 'Вестник РХД',
		'de la Pléiade. Il s’appuie sur'                      => '',
		' ) '                                                 => '',
	);
	foreach ( $cas as $brut => $propre ) {
		egal( $propre, er_collection_propre( $brut ), $brut );
	}
}
