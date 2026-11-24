<?php
/**
 * Lance les tests PHP : php tests/php/lancer.php
 *
 * Chaque fichier *-test.php de ce dossier définit des fonctions test_… ; chacune vérifie ce qu'elle veut avec egal()
 * ou vrai(). Pas de PHPUnit : rien à installer, le même lancement ici, sur un poste et dans GitHub. Sort avec le code
 * 1 au premier test qui échoue, pour que le déploiement s'arrête.
 *
 * @package editeurs-reunis
 */

error_reporting( E_ALL );
set_error_handler(
	function ( $niveau, $message, $fichier, $ligne ) {
		throw new ErrorException( $message, 0, $niveau, $fichier, $ligne );
	}
);

require __DIR__ . '/amorce.php';

/**
 * Un test qui échoue.
 */
class Echec extends Exception {}

/**
 * Vérifie que deux valeurs sont égales.
 *
 * @param mixed  $attendu Ce qu'on attend.
 * @param mixed  $obtenu  Ce qu'on obtient.
 * @param string $quoi    Ce qu'on vérifie, pour le message.
 */
function egal( $attendu, $obtenu, $quoi = '' ) {
	if ( $attendu !== $obtenu ) {
		throw new Echec( ( $quoi ? $quoi . "\n" : '' ) . '    attendu : ' . var_export( $attendu, true ) . "\n    obtenu  : " . var_export( $obtenu, true ) );
	}
}

/**
 * Vérifie qu'une condition est vraie.
 *
 * @param bool   $condition La condition.
 * @param string $quoi      Ce qu'on vérifie, pour le message.
 */
function vrai( $condition, $quoi ) {
	if ( true !== $condition ) {
		throw new Echec( $quoi );
	}
}

foreach ( glob( __DIR__ . '/*-test.php' ) as $fichier ) {
	require $fichier;
}

$tests  = array_values( array_filter( get_defined_functions()['user'], fn( $f ) => 0 === strpos( $f, 'test_' ) ) );
$echecs = 0;
foreach ( $tests as $test ) {
	try {
		$test();
		echo '  ok   ', $test, "\n";
	} catch ( Throwable $e ) {
		++$echecs;
		echo '  ÉCHEC ', $test, "\n    ", str_replace( "\n", "\n    ", $e->getMessage() ), "\n";
		if ( ! $e instanceof Echec ) {
			echo '    (', basename( $e->getFile() ), ', ligne ', $e->getLine(), ")\n";
		}
	}
}
printf( "\n%d tests, %d en échec.\n", count( $tests ), $echecs );
exit( $echecs ? 1 : 0 );
