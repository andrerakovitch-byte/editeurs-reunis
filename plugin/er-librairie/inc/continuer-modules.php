<?php
/**
 * « Pour aller plus loin » : le choix des modules d'un livre et leurs mots, sans WordPress (continuer.php leur donne
 * les données et montre le résultat).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les phrases d'un texte : coupé aux fins de ligne, et après un point, un point d'exclamation ou d'interrogation,
 * des points de suspension, quand une majuscule suit.
 *
 * @param string $texte Le texte.
 * @return string[]
 */
function er_continuer_phrases_du_texte( $texte ) {
	$phrases = array();
	foreach ( preg_split( '/\R+/u', (string) $texte ) as $ligne ) {
		foreach ( preg_split( '/(?<=[.!?…])\s+(?=[«"“(\p{Lu}0-9])/u', trim( $ligne ) ) as $p ) {
			$p = trim( $p );
			if ( '' !== $p ) {
				$phrases[] = $p;
			}
		}
	}
	return $phrases;
}
