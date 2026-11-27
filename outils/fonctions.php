<?php
/**
 * Chaque fonction er_… appelée par le thème ou les extensions est-elle définie quelque part ? Un fichier retiré dont
 * d'autres se servaient encore (les fonctions de l'en-tête vivent dans inc/essai-menus.php, au nom trompeur) faisait
 * une erreur fatale sur chaque page, et le contrôle passait (audit du 27 nov. 2026).
 *
 *     php outils/fonctions.php
 *
 * Lit les jetons de PHP : un appel est un nom er_… suivi d'une parenthèse, qui n'est ni une méthode (->, ::) ni une
 * définition ; les noms passés en chaîne (crochets, codes courts) comptent aussi, quand la chaîne est exactement un
 * nom er_….
 *
 * @package editeurs-reunis
 */

$racine   = dirname( __DIR__ );
$dossiers = array( 'theme/editeurs-reunis', 'plugin/er-librairie', 'plugin/er-bascule' );
$definies = array();
$appels   = array(); // nom → premier endroit.

foreach ( $dossiers as $dossier ) {
	$fichiers = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $racine . '/' . $dossier, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $fichiers as $f ) {
		if ( 'php' !== $f->getExtension() || false !== strpos( $f->getPathname(), '/languages/' ) ) {
			continue;
		}
		$jetons = token_get_all( (string) file_get_contents( $f->getPathname() ) );
		$n      = count( $jetons );
		for ( $i = 0; $i < $n; $i++ ) {
			$j = $jetons[ $i ];
			if ( ! is_array( $j ) ) {
				continue;
			}
			// Le jeton significatif d'avant et d'après.
			$avant = null;
			for ( $k = $i - 1; $k >= 0; $k-- ) {
				if ( ! is_array( $jetons[ $k ] ) || ! in_array( $jetons[ $k ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$avant = $jetons[ $k ];
					break;
				}
			}
			$apres = null;
			for ( $k = $i + 1; $k < $n; $k++ ) {
				if ( ! is_array( $jetons[ $k ] ) || ! in_array( $jetons[ $k ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					$apres = $jetons[ $k ];
					break;
				}
			}
			if ( T_STRING === $j[0] && 0 === strpos( $j[1], 'er_' ) ) {
				if ( is_array( $avant ) && T_FUNCTION === $avant[0] ) {
					$definies[ $j[1] ] = true;
				} elseif ( '(' === $apres && ! ( is_array( $avant ) && in_array( $avant[0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_NULLSAFE_OBJECT_OPERATOR ), true ) ) ) {
					$appels[ $j[1] ] = $appels[ $j[1] ] ?? substr( $f->getPathname(), strlen( $racine ) + 1 ) . ':' . $j[2];
				}
			}
		}
	}
}

$manquent = array_diff_key( $appels, $definies );
if ( $manquent ) {
	foreach ( $manquent as $nom => $ou ) {
		echo "  $nom() appelée ($ou) mais définie nulle part\n";
	}
	exit( 1 );
}
echo '  ok (' . count( $appels ) . " fonctions er_… appelées, toutes définies)\n";
