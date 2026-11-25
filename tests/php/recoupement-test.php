<?php
/**
 * Le relevé des rôles recoupé (er-bascule/inc/recoupement.php, donnees/roles-recoupes.tsv, corrections-fiches.tsv).
 *
 * @package er-bascule
 */

function test_recoupement_roles_tous_connus_et_par_livre() {
	$fichier = ER_RACINE . '/plugin/er-bascule/donnees/roles-recoupes.tsv';
	$roles   = er_recoupement_roles();
	foreach ( er_recoupement_lire( $fichier, 3 ) as list( $id, $role, $personne ) ) {
		vrai( isset( $roles[ $role ] ), $id . ' : rôle inconnu « ' . $role . ' »' );
		vrai( '' !== trim( $personne ) && '?' !== trim( $personne ), $id . ' : une personne' );
	}
	$livres = er_recoupement_roles_par_livre( $fichier );
	egal( true, count( $livres ) > 300, 'plus de trois cents livres' );
	egal( array( 'Hélène Henry' ), $livres[44]['er_traduction'], 'Le Docteur Jivago' );
	egal( array( 'Simone Sentz-Michel' ), $livres[98]['er_revision'], 'Un petit héros' );
	vrai( ! isset( $livres[1175] ), 'ce qui est à vérifier reste de côté' );
}

function test_recoupement_corrections_lisibles() {
	$champs = array( 'texte', 'titre', 'auteur', 'personne', 'editeur-nom', 'editeur', 'annee', 'pages', 'nature', 'isbn' );
	$poses  = array( 'auteur', 'editeur', 'annee', 'pages', 'nature', 'isbn' );
	foreach ( er_recoupement_lire( ER_RACINE . '/plugin/er-bascule/donnees/corrections-fiches.tsv', 4 ) as list( $id, $champ, $avant, $apres ) ) {
		vrai( in_array( $champ, $champs, true ), $id . ' : champ « ' . $champ . ' »' );
		vrai( in_array( $champ, $poses, true ) ? '' !== $apres : '' !== $avant, $id . ' : de quoi corriger' );
		if ( 'annee' === $champ ) {
			vrai( (bool) preg_match( '/^\d{4}(?:-\d{2,4})?$/', $apres ), $id . ' : une année, « ' . $apres . ' »' );
		}
		if ( 'nature' === $champ ) {
			vrai( isset( er_natures()[ $apres ] ), $id . ' : une nature connue, « ' . $apres . ' »' );
		}
		if ( 'pages' === $champ ) {
			vrai( (bool) preg_match( '/^[1-9]\d*$/', $apres ), $id . ' : un nombre de pages, « ' . $apres . ' »' );
		}
		if ( 'isbn' === $champ ) {
			vrai( '' !== er_recoupement_isbn13( $apres ), $id . ' : un ISBN à la clé juste, « ' . $apres . ' »' );
		}
	}
}

function test_recoupement_isbn() {
	egal( '9782846795159', er_recoupement_isbn13( '978-2-84679-515-9' ), 'treize chiffres, tirets ôtés' );
	egal( '9782850651595', er_recoupement_isbn13( '2-85065-159-1' ), 'dix chiffres, convertis' );
	egal( '', er_recoupement_isbn13( '9782846795158' ), 'clé fausse' );
	egal( '', er_recoupement_isbn13( '2-85065-159-5' ), 'clé fausse à dix chiffres' );
}

function test_recoupement_ne_pose_que_sur_du_vide_ou_le_faux() {
	vrai( er_recoupement_peut_poser( '', '', true ), 'rien sur la fiche : posé' );
	vrai( ! er_recoupement_peut_poser( '1998', '', false ), 'une année déjà là : laissée' );
	vrai( er_recoupement_peut_poser( '1939', '1939', false ), 'la valeur fausse : remplacée' );
	vrai( ! er_recoupement_peut_poser( '1940', '1939', false ), 'une autre valeur : laissée' );
}

function test_recoupement_remplace_sans_redoubler() {
	egal( 'Préface de Nina Kehayan', er_recoupement_remplacer( 'Préface de Nina Kchayan', 'Kchayan', 'Kehayan' ) );
	egal( 'par Christine Zeytounian-Beloüs et Christine Zeytounian-Beloüs', er_recoupement_remplacer( 'par Christine Zeytounian et Christine Zeytounian-Beloüs', 'Christine Zeytounian', 'Christine Zeytounian-Beloüs', $n ) );
	egal( 1, $n, 'un seul remplacement' );
	egal( 'Les sites de la mémoire russe, tome 2', er_recoupement_remplacer( 'Les sites de la mémoire russe, tome 2 – sous la direction de Georges Nivat', ' – sous la direction de Georges Nivat', '' ) );
	er_recoupement_remplacer( 'Jérôme Cler', 'Jérôme Clerc', 'Jérôme Cler', $n );
	egal( 0, $n, 'déjà corrigé' );
}
