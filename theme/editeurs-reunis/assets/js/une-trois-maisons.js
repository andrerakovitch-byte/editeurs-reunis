/**
 * L'accueil, les trois maisons (inc/trois-maisons.php) : au survol d'un lien, sa photo apparaît près de la souris,
 * grande et dans son format d'origine. Le point d'accroche suit le pointeur avec un peu de retard (une transition CSS) ;
 * la photo se pose à droite du curseur, à gauche près du bord droit, centrée sur lui en hauteur, sans sortir du module.
 * Seulement avec une souris ; ailleurs, rien ne change.
 */
( function () {
	'use strict';

	if ( ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
		return;
	}

	var ECART = 32;
	var MARGE = 16;

	// La taille d'une photo à 1440 : un paysage en 540 de large, une photo presque carrée en 460, un portrait en 560 de
	// haut ; un peu plus petite sur un écran plus étroit.
	function taille( img ) {
		var w = Number( img.getAttribute( 'width' ) ) || img.naturalWidth || 4;
		var h = Number( img.getAttribute( 'height' ) ) || img.naturalHeight || 3;
		var r = w / h;
		var k = Math.min( 1, Math.max( 0.6, window.innerWidth / 1440 ) );
		if ( r >= 1.2 ) {
			return [ 540 * k, ( 540 * k ) / r ];
		}
		if ( r >= 0.85 ) {
			return [ 460 * k, ( 460 * k ) / r ];
		}
		return [ 560 * k * r, 560 * k ];
	}

	document.querySelectorAll( '.er-trois-maisons' ).forEach( function ( module ) {
		var calque = module.querySelector( '.er-tm-photos' );
		if ( ! calque ) {
			return;
		}
		var photos = {};
		calque.querySelectorAll( '.er-tm-photo' ).forEach( function ( img ) {
			photos[ img.getAttribute( 'data-cle' ) ] = img;
		} );
		var visible = null;

		function montre( cle ) {
			var img = cle ? photos[ cle ] : null;
			if ( img === visible ) {
				return;
			}
			if ( visible ) {
				visible.classList.remove( 'est-visible' );
			}
			visible = img || null;
			if ( visible ) {
				visible.classList.add( 'est-visible' );
			}
		}

		function place( x, y ) {
			var w = module.offsetWidth;
			var h = module.offsetHeight;
			calque.style.transform = 'translate3d(' + Math.round( x ) + 'px, ' + Math.round( y ) + 'px, 0)';
			Object.keys( photos ).forEach( function ( cle ) {
				var img = photos[ cle ];
				var t = taille( img );
				var gauche = x + ECART + t[ 0 ] > w - MARGE ? -ECART - t[ 0 ] : ECART;
				var haut = Math.max( MARGE - y, Math.min( -t[ 1 ] / 2, h - MARGE - t[ 1 ] - y ) );
				img.style.width = Math.round( t[ 0 ] ) + 'px';
				img.style.height = Math.round( t[ 1 ] ) + 'px';
				img.style.left = Math.round( gauche ) + 'px';
				img.style.top = Math.round( haut ) + 'px';
			} );
		}

		var dernier = null;
		var attente = 0;
		module.addEventListener( 'mousemove', function ( e ) {
			dernier = e;
			if ( attente ) {
				return;
			}
			attente = window.requestAnimationFrame( function () {
				attente = 0;
				var r = module.getBoundingClientRect();
				place( dernier.clientX - r.left, dernier.clientY - r.top );
				var lien = dernier.target.closest ? dernier.target.closest( 'a[data-photo]' ) : null;
				montre( lien && module.contains( lien ) ? lien.getAttribute( 'data-photo' ) : null );
			} );
		} );
		module.addEventListener( 'mouseleave', function () {
			montre( null );
		} );
		// Au clavier : la photo du lien qui a le focus, à côté de lui.
		module.addEventListener( 'focusin', function ( e ) {
			var lien = e.target.closest ? e.target.closest( 'a[data-photo]' ) : null;
			if ( ! lien || ! lien.matches( ':focus-visible' ) ) {
				return;
			}
			var r = module.getBoundingClientRect();
			var l = lien.getBoundingClientRect();
			place( l.right - r.left, l.top + l.height / 2 - r.top );
			montre( lien.getAttribute( 'data-photo' ) );
		} );
		module.addEventListener( 'focusout', function () {
			montre( null );
		} );

		module.classList.add( 'est-pilote' );
	} );
} )();
