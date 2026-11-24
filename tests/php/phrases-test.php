<?php
/**
 * Les phrases des suggestions (er-bascule/inc/phrases.php, er-bascule/donnees/phrases.tsv).
 *
 * @package er-bascule
 */

function test_phrases_une_par_livre_courtes_et_finies() {
	$phrases = er_phrases_lire( ER_RACINE . '/plugin/er-bascule/donnees/phrases.tsv' );
	egal( true, count( $phrases ) > 800, 'tout le catalogue' );
	$lignes = array_filter( file( ER_RACINE . '/plugin/er-bascule/donnees/phrases.tsv', FILE_IGNORE_NEW_LINES ), fn( $l ) => '' !== trim( $l ) && '#' !== $l[0] );
	egal( count( $lignes ), count( $phrases ), 'pas deux phrases pour le même livre' );
	foreach ( $phrases as $id => $p ) {
		vrai( mb_strlen( $p ) <= 160, $id . ' : phrase trop longue' );
		vrai( 1 === preg_match( '/[.?!…]$/u', $p ), $id . ' : la phrase finit par un point' );
		vrai( false === strpos( $p, "'" ), $id . ' : l’apostrophe typographique' );
		$sans_noms = preg_replace( '/Jean-Claude|Claude Ligny/u', '', $p ); // Les personnes qui s'appellent Claude.
		vrai( 0 === preg_match( '/\b(IA|Claude|intelligence artificielle)\b/u', $sans_noms ), $id . ' : aucune mention de l’outil' );
	}
}

function test_phrases_une_correction_a_la_main_reste() {
	egal( 'poser', er_phrases_decision( '', '', 'Nouvelle.' ), 'livre sans phrase' );
	egal( 'poser', er_phrases_decision( 'Ancienne.', 'Ancienne.', 'Nouvelle.' ), 'phrase encore celle de la commande' );
	egal( 'identique', er_phrases_decision( 'Nouvelle.', 'Ancienne.', 'Nouvelle.' ), 'déjà en place' );
	egal( 'corrigee', er_phrases_decision( 'Corrigée par l’équipe.', 'Ancienne.', 'Nouvelle.' ), 'corrigée à la main' );
	egal( 'corrigee', er_phrases_decision( 'Écrite à la main.', '', 'Nouvelle.' ), 'écrite avant toute pose' );
}
