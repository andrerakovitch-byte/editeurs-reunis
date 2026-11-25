<?php
/**
 * Les rencontres : qui anime n'est pas un intervenant comme les autres (evenements.php).
 *
 * @package er-tests
 */

function test_qui_anime_une_rencontre() {
	er_test_article( 9101, array( 'post_title' => 'Présentation des Cahiers de Voronej', 'metas' => array( 'er_animation' => 'Tatiana Victoroff, Дмитрий Макаров' ) ) );
	$victoroff = new WP_Term( array( 'name' => 'Tatiana Victoroff', 'slug' => 'tatiana-victoroff' ) );
	$schneider = new WP_Term( array( 'name' => 'Jean-Claude Schneider', 'slug' => 'jean-claude-schneider' ) );
	$makarov   = new WP_Term( array( 'name' => 'Дмитрий Макаров', 'slug' => 'dmitrii-makarov' ) );
	vrai( er_evt_anime( $victoroff, 9101 ), 'Tatiana Victoroff anime' );
	vrai( er_evt_anime( $makarov, 9101 ), 'un nom en cyrillique aussi' );
	vrai( ! er_evt_anime( $schneider, 9101 ), 'Jean-Claude Schneider intervient' );
	er_test_article( 9102, array( 'post_title' => 'Rencontre sans animation' ) );
	vrai( ! er_evt_anime( $victoroff, 9102 ), 'sans le champ, personne n’anime' );
}
