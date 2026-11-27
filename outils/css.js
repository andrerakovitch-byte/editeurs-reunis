#!/usr/bin/env node
// Vérifie que chaque feuille de style est entière : accolades équilibrées, aucun commentaire ni chaîne laissés
// ouverts, et la feuille finit sur une règle refermée. Une feuille tronquée (une session coupée en pleine écriture,
// comme le bloc du téléphone de 05-recherche.css le 26 novembre) passait jusque-là le contrôle et partait en ligne.
// Sans dépendance : node outils/css.js fichier.css…

'use strict';

const fs = require( 'fs' );

let fautes = 0;

for ( const fichier of process.argv.slice( 2 ) ) {
	const texte = fs.readFileSync( fichier, 'utf8' );
	let profondeur = 0;
	let ligne = 1;
	let dernier = '';
	let faute = '';
	for ( let i = 0; i < texte.length && ! faute; i++ ) {
		const c = texte[ i ];
		if ( '\n' === c ) {
			ligne++;
			continue;
		}
		if ( '/' === c && '*' === texte[ i + 1 ] ) {
			const fin = texte.indexOf( '*/', i + 2 );
			if ( -1 === fin ) {
				faute = 'commentaire jamais refermé (ligne ' + ligne + ')';
				break;
			}
			ligne += ( texte.slice( i, fin ).match( /\n/g ) || [] ).length;
			i = fin + 1;
			continue;
		}
		if ( '"' === c || "'" === c ) {
			let j = i + 1;
			while ( j < texte.length && texte[ j ] !== c && '\n' !== texte[ j ] ) {
				j += '\\' === texte[ j ] ? 2 : 1;
			}
			if ( texte[ j ] !== c ) {
				faute = 'chaîne jamais refermée (ligne ' + ligne + ')';
				break;
			}
			i = j;
			dernier = c;
			continue;
		}
		if ( '{' === c ) {
			profondeur++;
		} else if ( '}' === c ) {
			profondeur--;
			if ( profondeur < 0 ) {
				faute = 'accolade fermante en trop (ligne ' + ligne + ')';
			}
		}
		if ( ! /\s/.test( c ) ) {
			dernier = c;
		}
	}
	if ( ! faute && 0 !== profondeur ) {
		faute = profondeur + ' accolade(s) jamais refermée(s) : la feuille est-elle tronquée ?';
	}
	if ( ! faute && '' !== dernier && '}' !== dernier && ';' !== dernier ) {
		faute = 'la feuille ne finit pas sur une règle refermée : tronquée ?';
	}
	if ( faute ) {
		fautes++;
		console.log( '  ' + fichier + ' : ' + faute );
	}
}

if ( fautes ) {
	process.exit( 1 );
}
console.log( '  ok' );
