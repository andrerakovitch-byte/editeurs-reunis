/**
 * Le menu de la librairie : un grand rayon ouvre son tiroir au survol (sur ordinateur) ou d'une touche sur sa flèche
 * (partout). Échap le referme et rend la main à la flèche ; un clic ailleurs le referme aussi. Sans ce script, le
 * nom du rayon reste un lien vers sa page.
 */
( function () {
	'use strict';

	var nav = document.querySelector( '.er-rayons-nav' );
	if ( ! nav ) {
		return;
	}
	var survol = window.matchMedia( '(hover: hover) and (pointer: fine)' );
	var tiroirs = Array.prototype.slice.call( nav.querySelectorAll( '.er-a-tiroir' ) );

	function regler( li, ouvert ) {
		li.classList.toggle( 'ouvert', ouvert );
		li.querySelector( '.er-rayon-ouvrir' ).setAttribute( 'aria-expanded', ouvert ? 'true' : 'false' );
	}
	function fermer( sauf ) {
		tiroirs.forEach( function ( li ) {
			if ( li !== sauf ) {
				regler( li, false );
			}
		} );
	}

	tiroirs.forEach( function ( li ) {
		var bouton = li.querySelector( '.er-rayon-ouvrir' );
		var attente;
		bouton.addEventListener( 'click', function () {
			var ouvrir = ! li.classList.contains( 'ouvert' );
			fermer( li );
			regler( li, ouvrir );
		} );
		li.addEventListener( 'mouseenter', function () {
			if ( ! survol.matches ) {
				return;
			}
			clearTimeout( attente );
			attente = setTimeout( function () {
				fermer( li );
				regler( li, true );
			}, 90 );
		} );
		li.addEventListener( 'mouseleave', function () {
			if ( ! survol.matches ) {
				return;
			}
			clearTimeout( attente );
			attente = setTimeout( function () {
				regler( li, false );
			}, 220 );
		} );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) {
			return;
		}
		var ouvert = nav.querySelector( '.er-a-tiroir.ouvert' );
		if ( ouvert ) {
			fermer();
			ouvert.querySelector( '.er-rayon-ouvrir' ).focus();
		}
	} );
	document.addEventListener( 'click', function ( e ) {
		if ( ! nav.contains( e.target ) ) {
			fermer();
		}
	} );
} )();
