/**
 * La phrase en haut de l'accueil (inc/accueil-phrase.php, Y3b) : elle change d'elle-même toutes les six secondes,
 * avec son image ; la nouvelle monte en paraissant, l'ancienne s'efface vers le haut. La souris sur la phrase, ou le
 * focus dans ses liens, l'arrête ; un lien qui prend le focus paraît. Ni pendant que l'onglet est caché, ni si l'on
 * a demandé moins d'animations : la première phrase reste. Sans ce script, la première phrase reste aussi.
 */
( function () {
	'use strict';
	var DUREE = 6000;
	var calme = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	document.querySelectorAll( '.er-ap' ).forEach( function ( ap ) {
		var phrases = Array.prototype.slice.call( ap.querySelectorAll( '.er-ap-reponse' ) );
		var images = Array.prototype.slice.call( ap.querySelectorAll( '.er-ap-image' ) );
		if ( phrases.length < 2 ) {
			return;
		}
		var ici = 0;
		var arret = false;

		function montrer( k ) {
			if ( k === ici ) {
				return;
			}
			var avant = phrases[ ici ];
			avant.classList.remove( 'est-vue' );
			avant.classList.add( 'est-partie' );
			window.setTimeout( function () {
				avant.classList.remove( 'est-partie' );
			}, 500 );
			ici = k;
			phrases[ ici ].classList.add( 'est-vue' );
			var cle = phrases[ ici ].getAttribute( 'data-cle' );
			images.forEach( function ( image ) {
				image.classList.toggle( 'est-vue', image.getAttribute( 'data-cle' ) === cle );
			} );
		}

		window.setInterval( function () {
			if ( ! arret && ! document.hidden && ! calme.matches ) {
				montrer( ( ici + 1 ) % phrases.length );
			}
		}, DUREE );

		ap.addEventListener( 'mouseenter', function () {
			arret = true;
		} );
		ap.addEventListener( 'mouseleave', function () {
			arret = ap.contains( document.activeElement );
		} );
		phrases.forEach( function ( phrase, k ) {
			phrase.addEventListener( 'focus', function () {
				arret = true;
				montrer( k );
			} );
		} );
		ap.addEventListener( 'focusout', function ( e ) {
			if ( ! ap.contains( e.relatedTarget ) ) {
				arret = ap.matches( ':hover' );
			}
		} );
	} );
} )();
