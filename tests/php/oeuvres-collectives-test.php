<?php
/**
 * Les œuvres collectives (er-librairie/inc/oeuvres-collectives.php) : les revues et les ouvrages collectifs, sans
 * auteur seul.
 *
 * @package er-librairie
 */

function test_une_revue_se_reconnait_a_son_titre() {
	vrai( er_titre_de_revue( 'Le Messager orthodoxe, n° 160' ), 'Le Messager orthodoxe' );
	vrai( er_titre_de_revue( 'Вестник Русского христианского движения, № 219' ), 'le Вестник' );
	vrai( er_titre_de_revue( 'Православная мысль, выпуск 5' ), 'Православная мысль' );
	vrai( ! er_titre_de_revue( 'Les sites de la mémoire russe, tome 2' ), 'un ouvrage collectif n’est pas une revue' );
	vrai( ! er_titre_de_revue( 'Le Messager de l’Archange' ), 'un autre messager' );
}

function test_la_nature_d_un_livre() {
	er_test_article( 9001, array( 'post_title' => 'Le Messager orthodoxe, n° 101' ) );
	er_test_article( 9002, array( 'post_title' => 'Hommage à l’Ukraine', 'metas' => array( 'er_nature' => 'collectif' ) ) );
	er_test_article( 9003, array( 'post_title' => 'Djann' ) );
	er_test_article( 9004, array( 'post_title' => 'Un livre', 'metas' => array( 'er_nature' => 'n’importe quoi' ) ) );
	egal( 'revue', er_nature_du_livre( 9001 ), 'une revue, à son titre' );
	egal( 'collectif', er_nature_du_livre( 9002 ), 'un ouvrage collectif, à sa méta' );
	egal( '', er_nature_du_livre( 9003 ), 'un livre d’auteur' );
	egal( '', er_nature_du_livre( 9004 ), 'une nature inconnue ne compte pas' );
	egal( '', er_collectif_en_tete( 9003 ), 'rien sous le titre d’un livre d’auteur' );
}

function test_un_calendrier_un_objet_ne_sont_pas_des_livres() {
	er_test_article( 9005, array( 'post_title' => 'Calendrier orthodoxe des scouts russes 2024', 'metas' => array( 'er_nature' => 'calendrier' ) ) );
	er_test_article( 9006, array( 'post_title' => 'Étiquettes clavier cyrillique', 'metas' => array( 'er_nature' => 'objet' ) ) );
	egal( 'calendrier', er_nature_du_livre( 9005 ), 'un calendrier, à sa méta' );
	egal( 'objet', er_nature_du_livre( 9006 ), 'un objet, à sa méta' );
	vrai( er_nature_hors_livre( 'calendrier' ) && er_nature_hors_livre( 'objet' ), 'ni l’un ni l’autre n’est un livre' );
	vrai( ! er_nature_hors_livre( 'revue' ) && ! er_nature_hors_livre( 'collectif' ) && ! er_nature_hors_livre( '' ), 'une revue, un ouvrage collectif, un livre d’auteur sont des livres' );
	egal( 'Calendrier', er_collectif_en_tete( 9005 ), 'sous le titre : « Calendrier », sans personne' );
	egal( 'Objet', er_collectif_en_tete( 9006, false ), 'sur la carte : « Objet »' );
}

function test_le_role_des_contributions() {
	$roles = er_roles_personnes();
	vrai( isset( $roles['er_contribution'] ), 'le rôle existe' );
	egal( 'contribution', $roles['er_contribution']['cle'], 'sa clé, pour la page d’une personne' );
	egal( 'er_contribution', er_recoupement_roles()['Contributions'], 'le relevé le connaît' );
}
