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
	$champs = array( 'texte', 'titre', 'auteur', 'personne' );
	foreach ( er_recoupement_lire( ER_RACINE . '/plugin/er-bascule/donnees/corrections-fiches.tsv', 4 ) as list( $id, $champ, $avant, $apres ) ) {
		vrai( in_array( $champ, $champs, true ), $id . ' : champ « ' . $champ . ' »' );
		vrai( 'auteur' === $champ ? '' !== $apres : '' !== $avant, $id . ' : de quoi corriger' );
	}
}

function test_recoupement_remplace_sans_redoubler() {
	egal( 'Préface de Nina Kehayan', er_recoupement_remplacer( 'Préface de Nina Kchayan', 'Kchayan', 'Kehayan' ) );
	egal( 'par Christine Zeytounian-Beloüs et Christine Zeytounian-Beloüs', er_recoupement_remplacer( 'par Christine Zeytounian et Christine Zeytounian-Beloüs', 'Christine Zeytounian', 'Christine Zeytounian-Beloüs', $n ) );
	egal( 1, $n, 'un seul remplacement' );
	egal( 'Les sites de la mémoire russe, tome 2', er_recoupement_remplacer( 'Les sites de la mémoire russe, tome 2 – sous la direction de Georges Nivat', ' – sous la direction de Georges Nivat', '' ) );
	er_recoupement_remplacer( 'Jérôme Cler', 'Jérôme Clerc', 'Jérôme Cler', $n );
	egal( 0, $n, 'déjà corrigé' );
}
