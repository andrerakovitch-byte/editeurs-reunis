/**
 * L'accueil, les trois maisons (inc/trois-maisons.php) : au survol d'un lien, sa photo apparaît près de la souris,
 * grande et dans son format d'origine. Le point d'accroche suit le pointeur avec un peu de retard ; la photo se pose à
 * droite du curseur, à gauche près du bord droit, centrée sur lui en hauteur, sans sortir du module. Le texte qu'elle
 * couvre passe dessus, en blanc, à la manière de la Pinault Collection (27 nov.) : une copie du texte, au-dessus des
 * photos, découpée à la forme de celle qu'on voit, le lien visé souligné. La photo et la découpe avancent ensemble,
 * image par image. Seulement avec une souris ; ailleurs, rien ne change.
 */
( function () {
	'use strict';

	if ( ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
		return;
	}

	var ECART = 32;
	var MARGE = 16;
	var SUITE = 0.18; // la part du chemin faite à chaque image : le retard de la photo sur le pointeur
	var calme = window.matchMedia( '(prefers-reduced-motion: reduce)' );

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
		var blanc = module.querySelector( '.er-tm-blanc' );
		var photos = {};
		calque.querySelectorAll( '.er-tm-photo' ).forEach( function ( img ) {
			photos[ img.getAttribute( 'data-cle' ) ] = img;
		} );
		var copies = blanc ? Array.prototype.slice.call( blanc.querySelectorAll( '.er-tm-lien[data-photo]' ) ) : [];
		var visible = null;
		var cible = null; // où va le point d'accroche
		var point = null; // où il est
		var anime = 0;

		// Pose les photos autour du point d'accroche, et la découpe sur celle qu'on voit.
		function poser() {
			if ( ! point ) {
				return;
			}
			var x = point[ 0 ];
			var y = point[ 1 ];
			var w = module.offsetWidth;
			var h = module.offsetHeight;
			calque.style.transform = 'translate3d(' + x.toFixed( 1 ) + 'px, ' + y.toFixed( 1 ) + 'px, 0)';
			var cadre = null;
			Object.keys( photos ).forEach( function ( cle ) {
				var img = photos[ cle ];
				var t = taille( img );
				var gauche = Math.round( x + ECART + t[ 0 ] > w - MARGE ? -ECART - t[ 0 ] : ECART );
				var haut = Math.round( Math.max( MARGE - y, Math.min( -t[ 1 ] / 2, h - MARGE - t[ 1 ] - y ) ) );
				img.style.width = Math.round( t[ 0 ] ) + 'px';
				img.style.height = Math.round( t[ 1 ] ) + 'px';
				img.style.left = gauche + 'px';
				img.style.top = haut + 'px';
				if ( img === visible ) {
					cadre = [ x + gauche, y + haut, Math.round( t[ 0 ] ), Math.round( t[ 1 ] ) ];
				}
			} );
			if ( blanc && cadre ) {
				blanc.style.clipPath = 'inset(' + Math.max( 0, cadre[ 1 ] ).toFixed( 1 ) + 'px ' + Math.max( 0, w - cadre[ 0 ] - cadre[ 2 ] ).toFixed( 1 ) + 'px '
					+ Math.max( 0, h - cadre[ 1 ] - cadre[ 3 ] ).toFixed( 1 ) + 'px ' + Math.max( 0, cadre[ 0 ] ).toFixed( 1 ) + 'px)';
			}
		}

		function montre( cle ) {
			var img = cle ? photos[ cle ] || null : null;
			module.classList.toggle( 'est-survol', !! img );
			copies.forEach( function ( copie ) {
				copie.classList.toggle( 'est-vise', !! img && copie.getAttribute( 'data-photo' ) === cle );
			} );
			if ( img === visible ) {
				return;
			}
			if ( visible ) {
				visible.classList.remove( 'est-visible' );
			}
			visible = img;
			if ( visible ) {
				visible.classList.add( 'est-visible' );
			}
			poser();
		}

		// Le point d'accroche rejoint le pointeur, un peu en retard, image par image.
		function avancer() {
			anime = 0;
			if ( ! cible ) {
				return;
			}
			if ( ! point || calme.matches ) {
				point = cible.slice();
			} else {
				point[ 0 ] += ( cible[ 0 ] - point[ 0 ] ) * SUITE;
				point[ 1 ] += ( cible[ 1 ] - point[ 1 ] ) * SUITE;
			}
			poser();
			if ( Math.abs( cible[ 0 ] - point[ 0 ] ) > 0.3 || Math.abs( cible[ 1 ] - point[ 1 ] ) > 0.3 ) {
				anime = window.requestAnimationFrame( avancer );
			}
		}
		function viser( x, y, cle, demblee ) {
			cible = [ x, y ];
			if ( ! point || demblee ) {
				point = cible.slice();
			}
			montre( cle );
			if ( ! anime ) {
				anime = window.requestAnimationFrame( avancer );
			}
		}

		module.addEventListener( 'mousemove', function ( e ) {
			var r = module.getBoundingClientRect();
			var lien = e.target.closest ? e.target.closest( '.er-tm-corps a[data-photo]' ) : null;
			viser( e.clientX - r.left, e.clientY - r.top, lien && module.contains( lien ) ? lien.getAttribute( 'data-photo' ) : null, false );
		} );
		module.addEventListener( 'mouseleave', function () {
			cible = null;
			point = null; // en revenant, la photo se pose d'emblée près du pointeur
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
			viser( l.right - r.left, l.top + l.height / 2 - r.top, lien.getAttribute( 'data-photo' ), true );
		} );
		module.addEventListener( 'focusout', function () {
			montre( null );
		} );

		module.classList.add( 'est-pilote' );
	} );
} )();
