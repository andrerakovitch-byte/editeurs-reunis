<?php
/**
 * Les personnes et leurs miroirs (plugin/er-librairie/inc/personnes.php).
 *
 * @package editeurs-reunis
 */

function test_miroirs_sans_personne_et_noms_qui_divergent() {
	$ecarts = er_personnes_ecarts(
		array(
			'georges-nivat' => 'Georges Nivat',
			'sophie-benech' => 'Sophie Benech',
		),
		array(
			'er_traduction' => array(
				'georges-nivat'  => 'Georges Nivat',
				'sophie-benech'  => 'Sophie Bénech',
				'paul-lesquesne' => 'Paul Lesquesne',
			),
			'er_autour'     => array( 'georges-nivat' => 'Georges Nivat' ),
		)
	);
	egal( 2, count( $ecarts ) );
	egal( array( 'er_traduction', 'sophie-benech', 'nom différent', 'Sophie Benech' ), array( $ecarts[0]['taxonomie'], $ecarts[0]['slug'], $ecarts[0]['probleme'], $ecarts[0]['attendu'] ) );
	egal( array( 'paul-lesquesne', 'sans personne' ), array( $ecarts[1]['slug'], $ecarts[1]['probleme'] ) );
	egal( array(), er_personnes_ecarts( array( 'a-b' => 'A B' ), array( 'er_direction' => array( 'a-b' => 'A B' ) ) ), 'tout en ordre' );
}

function test_identifiant_d_une_personne_retenu_avant_sa_modification() {
	er_personnes_avant( 42, 'ancien-nom' );
	egal( 'ancien-nom', er_personnes_avant( 42 ) );
	egal( '', er_personnes_avant( 43 ) );
}

function test_renommer_une_personne_renomme_ses_miroirs() {
	$personne = er_test_terme( 1, 'auteur', 'paul-lesquesne', 'Paul Lesquesne' );
	er_test_terme( 11, 'er_traduction', 'paul-lesquesne', 'Paul Lesquesne' );
	er_test_terme( 12, 'er_autour', 'paul-lesquesne', 'Paul Lesquesne' );
	er_test_terme( 21, 'er_intervenant', 'paul-lesquesne', 'Павел Лекен' );
	er_test_action( 'edit_terms', 1, 'auteur' );
	$personne->slug = 'paul-lequesne';
	$personne->name = 'Paul Lequesne';
	er_test_action( 'edited_auteur', 1 );
	egal( array( 'paul-lequesne', 'Paul Lequesne' ), array( get_term( 11, 'er_traduction' )->slug, get_term( 11, 'er_traduction' )->name ) );
	egal( 'Paul Lequesne', get_term( 12, 'er_autour' )->name );
	egal( array( 'paul-lequesne', 'Павел Лекен' ), array( get_term( 21, 'er_intervenant' )->slug, get_term( 21, 'er_intervenant' )->name ), 'l’intervenant garde son nom affiché' );
	egal( ',paul-lesquesne,', get_term_meta( 1, 'er_anciens' ), 'l’ancienne adresse retenue' );
}

function test_supprimer_une_personne_supprime_ses_miroirs() {
	$personne = er_test_terme( 2, 'auteur', 'jean-laury', 'Jean Laury' );
	er_test_terme( 31, 'er_preface', 'jean-laury', 'Jean Laury' );
	er_test_terme( 32, 'er_direction', 'georges-nivat', 'Georges Nivat' );
	// Le dernier rappel de « delete_term » : celui de personnes.php (celui de l'index, plus tôt, demande la base).
	$rappels = $GLOBALS['er_test_crochets']['delete_term'];
	end( $rappels )[0]( 2, 0, 'auteur', $personne );
	egal( null, get_term( 31, 'er_preface' ) );
	vrai( null !== get_term( 32, 'er_direction' ), 'les autres restent' );
}
