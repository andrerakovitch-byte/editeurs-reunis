/**
 * La phrase en haut de l'accueil (inc/accueil-phrase.php, Y3b) : elle change d'elle-même toutes les six secondes ;
 * la nouvelle monte en paraissant, l'ancienne s'efface vers le haut. Elle tourne aussi sous la souris (André, 26 nov.) ;
 * seul le focus dans ses liens l'arrête, et un lien qui prend le focus paraît. Ni pendant que l'onglet est caché, ni si
 * l'on a demandé moins d'animations : la première phrase reste. Sans ce script, la première phrase reste aussi.
 *
 * Avec une souris, la photo de la phrase suit le pointeur quand il passe sur elle, comme dans les trois maisons
 * (une-trois-maisons.js), et aussi grande : elle se pose à droite du curseur (à gauche près du bord), centrée sur lui
 * en hauteur, sans sortir de l'écran ni passer sous l'en-tête (elle peut déborder sur le triptyque) ; quand la phrase
 * change, la photo change avec elle. Les mots qu'elle couvre passent dessus, en blanc, à la manière de la Pinault
 * Collection (Y6, 27 nov., à l'essai) : une copie blanche de la phrase, au-dessus de la photo, découpée à sa forme. La
 * photo et la découpe avancent ensemble, image par image, avec un peu de retard sur le pointeur.
 */
( function () {
	'use strict';
	var DUREE = 6000;
	var ECART = 32;
	var MARGE = 16;
	var SUITE = 0.18; // la part du chemin faite à chaque image : le retard de la photo sur le pointeur
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
		var phrases = Array.prototype.slice.call( ap.querySelectorAll( 'a.er-ap-reponse' ) );
		var blancs = Array.prototype.slice.call( ap.querySelectorAll( '.er-ap-blanc .er-ap-reponse' ) );
		var calque = ap.querySelector( '.er-ap-suit' );
		var blanc = ap.querySelector( '.er-ap-blanc' );
		var photos = {};
		if ( calque ) {
			calque.querySelectorAll( '.er-ap-suit-photo' ).forEach( function ( img ) {
				photos[ img.getAttribute( 'data-cle' ) ] = img;
			} );
		}
		var ici = 0;
		var arret = false;
		var survol = false; // La phrase ne change pas sous la souris : le lien qu'on vise ne change pas de page.
		var visible = null;
		var dernier = null; // le dernier mouvement de la souris sur la tête
		var cible = null; // où va le point d'accroche (le pointeur), dans la tête
		var point = null; // où il est
		var anime = 0;
		var entete = document.querySelector( 'header.er-haut' );

		// Pose les photos autour du point d'accroche, et la découpe blanche sur celle qu'on voit.
		function poser() {
			if ( ! point || ! calque ) {
				return;
			}
			var x = point[ 0 ];
			var y = point[ 1 ];
			var w = ap.offsetWidth;
			var h = ap.offsetHeight;
			// Les bornes en hauteur : le bas de l'en-tête et le bas de l'écran, ramenés à la tête de l'accueil.
			var dessus = ap.getBoundingClientRect().top;
			var plafond = ( entete ? Math.max( 0, entete.getBoundingClientRect().bottom ) : 0 ) - dessus + MARGE;
			var plancher = window.innerHeight - dessus - MARGE;
			calque.style.transform = 'translate3d(' + x.toFixed( 1 ) + 'px, ' + y.toFixed( 1 ) + 'px, 0)';
			var cadre = null;
			Object.keys( photos ).forEach( function ( cle ) {
				var img = photos[ cle ];
				var t = taille( img );
				var gauche = Math.round( x + ECART + t[ 0 ] > w - MARGE ? -ECART - t[ 0 ] : ECART );
				var haut = Math.round( Math.max( plafond - y, Math.min( -t[ 1 ] / 2, plancher - t[ 1 ] - y ) ) );
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

		// La photo qui suit la souris : celle de la phrase sous le pointeur, ou aucune.
		function montrePhoto( cle ) {
			var img = cle ? photos[ cle ] || null : null;
			ap.classList.toggle( 'est-survol', !! img );
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

		// La phrase sous le pointeur : tout son bloc, blanc du bout des lignes compris (sur les mots seuls, la photo
		// s'éteignait à chaque bout de ligne : « l'effet marche par intermittence », André, 27 nov.). Les autres
		// phrases ne prennent pas la souris.
		function sousLaSouris() {
			if ( ! dernier ) {
				return null;
			}
			var el = document.elementFromPoint( dernier.clientX, dernier.clientY );
			var phrase = el && el.closest ? el.closest( 'a.er-ap-reponse' ) : null;
			return phrase && ap.contains( phrase ) ? phrase.getAttribute( 'data-cle' ) : null;
		}

		function montrer( k ) {
			if ( k === ici ) {
				return;
			}
			[ phrases, blancs ].forEach( function ( liste ) {
				var avant = liste[ ici ];
				if ( ! avant || ! liste[ k ] ) {
					return;
				}
				avant.classList.remove( 'est-vue' );
				avant.classList.add( 'est-partie' );
				window.setTimeout( function () {
					avant.classList.remove( 'est-partie' );
				}, 500 );
				liste[ k ].classList.add( 'est-vue' );
			} );
			ici = k;
			if ( calque && dernier ) {
				montrePhoto( sousLaSouris() );
			}
		}

		if ( phrases.length > 1 ) {
			window.setInterval( function () {
				if ( ! arret && ! survol && ! document.hidden && ! calme.matches ) {
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
		// Sous la souris, la rotation s'arrête (audit du 27 nov. 2026 : en 6 s, le lien sous le pointeur menait ailleurs).
		ap.addEventListener( 'mouseenter', function () {
			survol = true;
		} );
		ap.addEventListener( 'mouseleave', function () {
			survol = false;
		} );

		if ( ! calque || ! souris.matches ) {
			return;
		}
		ap.addEventListener( 'mousemove', function ( e ) {
			dernier = e;
			var r = ap.getBoundingClientRect();
			cible = [ e.clientX - r.left, e.clientY - r.top ];
			if ( ! point ) {
				point = cible.slice(); // en entrant, la photo se pose d'emblée près du pointeur
			}
			montrePhoto( sousLaSouris() );
			if ( ! anime ) {
				anime = window.requestAnimationFrame( avancer );
			}
		} );
		ap.addEventListener( 'mouseleave', function () {
			dernier = null;
			cible = null;
			point = null;
			montrePhoto( null );
		} );
		// Le calque des photos se pose après le chargement de la page : posé tout de suite, il faisait télécharger les
		// photos du survol avant même le triptyque (audit du 27 nov. 2026).
		if ( 'complete' === document.readyState ) {
			ap.classList.add( 'est-pilote' );
		} else {
			window.addEventListener( 'load', function () {
				ap.classList.add( 'est-pilote' );
			} );
		}
	} );
} )();
