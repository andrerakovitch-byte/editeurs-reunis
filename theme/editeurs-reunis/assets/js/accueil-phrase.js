/**
 * La phrase en haut de l'accueil (inc/accueil-phrase.php, Y3b) : elle change d'elle-même toutes les six secondes ;
 * la nouvelle monte en paraissant, l'ancienne s'efface vers le haut. Elle tourne aussi sous la
 * souris (André, 26 nov.) ; seul le focus dans ses liens l'arrête, et un lien qui prend le focus paraît. Ni pendant
 * que l'onglet est caché, ni si l'on a demandé moins d'animations : la première phrase reste. Sans ce script, la
 * première phrase reste aussi.
 *
 * Avec une souris, la photo de la phrase suit le pointeur quand il passe sur ses mots, comme dans les trois maisons
 * (une-trois-maisons.js), et aussi grande : elle se pose à droite du curseur (à gauche près du bord), centrée sur lui
 * en hauteur, sans sortir de l'écran ni passer sous l'en-tête (elle peut déborder sur le triptyque) ; quand la phrase
 * change, la photo change avec elle.
 */
( function () {
	'use strict';
	var DUREE = 6000;
	var ECART = 32;
	var MARGE = 16;
	var calme = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var souris = window.matchMedia( '(hover: hover) and (pointer: fine)' );

	// La taille d'une photo, celle des trois maisons : à 1440, un paysage en 540 de large, presque carrée en 460, un
	// portrait en 560 de haut ; un peu plus petite sur un écran plus étroit.
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

	document.querySelectorAll( '.er-ap' ).forEach( function ( ap ) {
		var phrases = Array.prototype.slice.call( ap.querySelectorAll( '.er-ap-reponse' ) );
		var calque = ap.querySelector( '.er-ap-suit' );
		var photos = {};
		if ( calque ) {
			calque.querySelectorAll( '.er-ap-suit-photo' ).forEach( function ( img ) {
				photos[ img.getAttribute( 'data-cle' ) ] = img;
			} );
		}
		var ici = 0;
		var arret = false;
		var visible = null;
		var dernier = null; // le dernier mouvement de la souris sur la tête

		// La photo qui suit la souris : celle de la phrase sous le pointeur, ou aucune.
		function montrePhoto( cle ) {
			var img = cle ? photos[ cle ] || null : null;
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
		}
		var entete = document.querySelector( 'header.er-haut' );
		function place( x, y ) {
			var w = ap.offsetWidth;
			// Les bornes en hauteur : le bas de l'en-tête et le bas de l'écran, ramenés à la tête de l'accueil.
			var dessus = ap.getBoundingClientRect().top;
			var plafond = ( entete ? Math.max( 0, entete.getBoundingClientRect().bottom ) : 0 ) - dessus + MARGE;
			var plancher = window.innerHeight - dessus - MARGE;
			calque.style.transform = 'translate3d(' + Math.round( x ) + 'px, ' + Math.round( y ) + 'px, 0)';
			Object.keys( photos ).forEach( function ( cle ) {
				var img = photos[ cle ];
				var t = taille( img );
				var gauche = x + ECART + t[ 0 ] > w - MARGE ? -ECART - t[ 0 ] : ECART;
				var haut = Math.max( plafond - y, Math.min( -t[ 1 ] / 2, plancher - t[ 1 ] - y ) );
				img.style.width = Math.round( t[ 0 ] ) + 'px';
				img.style.height = Math.round( t[ 1 ] ) + 'px';
				img.style.left = Math.round( gauche ) + 'px';
				img.style.top = Math.round( haut ) + 'px';
			} );
		}
		// La phrase sous le pointeur (les autres ne prennent pas la souris).
		function sousLaSouris() {
			if ( ! dernier ) {
				return null;
			}
			var el = document.elementFromPoint( dernier.clientX, dernier.clientY );
			var mots = el && el.closest ? el.closest( '.er-ap-mots' ) : null;
			var phrase = mots ? mots.closest( '.er-ap-reponse' ) : null;
			return phrase && ap.contains( phrase ) ? phrase.getAttribute( 'data-cle' ) : null;
		}

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
			if ( calque && dernier ) {
				montrePhoto( sousLaSouris() );
			}
		}

		if ( phrases.length > 1 ) {
			window.setInterval( function () {
				if ( ! arret && ! document.hidden && ! calme.matches ) {
					montrer( ( ici + 1 ) % phrases.length );
				}
			}, DUREE );
		}
		phrases.forEach( function ( phrase, k ) {
			phrase.addEventListener( 'focus', function () {
				arret = true;
				montrer( k );
			} );
		} );
		ap.addEventListener( 'focusout', function ( e ) {
			if ( ! ap.contains( e.relatedTarget ) ) {
				arret = false;
			}
		} );

		if ( ! calque || ! souris.matches ) {
			return;
		}
		var attente = 0;
		var posee = false;
		ap.addEventListener( 'mousemove', function ( e ) {
			dernier = e;
			if ( attente ) {
				return;
			}
			attente = window.requestAnimationFrame( function () {
				attente = 0;
				if ( ! dernier ) {
					return;
				}
				var r = ap.getBoundingClientRect();
				// En entrant, la photo se pose d'emblée près du pointeur (elle ne glisse pas depuis le coin).
				if ( ! posee ) {
					calque.style.transition = 'none';
				}
				place( dernier.clientX - r.left, dernier.clientY - r.top );
				if ( ! posee ) {
					void calque.offsetWidth;
					calque.style.transition = '';
					posee = true;
				}
				montrePhoto( sousLaSouris() );
			} );
		} );
		ap.addEventListener( 'mouseleave', function () {
			dernier = null;
			posee = false;
			montrePhoto( null );
		} );
		ap.classList.add( 'est-pilote' );
	} );
} )();
