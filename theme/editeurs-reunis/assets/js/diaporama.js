/**
 * Le diaporama de l'accueil.
 *
 * Sans ce script, les diapositives se font déjà glisser de côté (défilement accroché, en CSS). Il ajoute les repères
 * (un trait par diapositive, qui se remplit pendant qu'elle reste à l'écran), les flèches, et le passage tout seul à
 * la suivante quand le trait est plein. Le défilement s'arrête quand on survole le diaporama, qu'on le touche ou
 * qu'on y entre au clavier, quand l'onglet est caché, et il n'a jamais lieu si l'appareil demande moins d'animations.
 */
( function () {
	'use strict';

	var moinsDAnimations = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function bouton( classe, libelle, dessin ) {
		var b = document.createElement( 'button' );
		b.type = 'button';
		b.className = classe;
		b.setAttribute( 'aria-label', libelle );
		b.innerHTML = dessin;
		return b;
	}

	var FLECHE = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

	document.querySelectorAll( '.er-diaporama' ).forEach( function ( racine ) {
		var piste = racine.querySelector( '.er-diapos' );
		if ( ! piste ) {
			return;
		}
		var diapos = Array.prototype.filter.call( piste.children, function ( el ) {
			return el.classList.contains( 'er-diapo' );
		} );
		var n = diapos.length;
		if ( n < 2 ) {
			return;
		}

		racine.setAttribute( 'role', 'region' );
		racine.setAttribute( 'aria-roledescription', 'carrousel' );
		racine.setAttribute( 'aria-label', racine.getAttribute( 'data-libelle' ) || 'À la une' );
		diapos.forEach( function ( d, i ) {
			d.setAttribute( 'role', 'group' );
			d.setAttribute( 'aria-roledescription', 'diapositive' );
			d.setAttribute( 'aria-label', ( i + 1 ) + ' sur ' + n );
		} );

		var commandes = document.createElement( 'div' );
		commandes.className = 'er-diaporama-commandes';
		var reperes = document.createElement( 'div' );
		reperes.className = 'er-diaporama-reperes';
		var traits = diapos.map( function ( d, i ) {
			var titre = d.querySelector( '.er-diapo-titre' );
			var t = bouton( 'er-diaporama-trait', ( titre ? titre.textContent : 'Diapositive ' + ( i + 1 ) ), '<span></span>' );
			t.addEventListener( 'click', function () {
				aller( i );
			} );
			reperes.appendChild( t );
			return t;
		} );
		var fleches = document.createElement( 'div' );
		fleches.className = 'er-diaporama-fleches';
		var precedente = bouton( 'er-diaporama-fleche er-diaporama-precedente', 'Diapositive précédente', FLECHE );
		var suivante = bouton( 'er-diaporama-fleche', 'Diapositive suivante', FLECHE );
		precedente.addEventListener( 'click', function () {
			aller( actuelle - 1 );
		} );
		suivante.addEventListener( 'click', function () {
			aller( actuelle + 1 );
		} );
		fleches.appendChild( precedente );
		fleches.appendChild( suivante );
		commandes.appendChild( reperes );
		commandes.appendChild( fleches );
		racine.appendChild( commandes );

		var actuelle = -1;

		function marquer( i ) {
			if ( i === actuelle ) {
				return;
			}
			actuelle = i;
			traits.forEach( function ( t, j ) {
				if ( j === i ) {
					t.setAttribute( 'aria-current', 'true' );
				} else {
					t.removeAttribute( 'aria-current' );
				}
			} );
			diapos.forEach( function ( d, j ) {
				d.toggleAttribute( 'inert', j !== i );
			} );
		}

		function aller( i ) {
			i = ( i + n ) % n;
			piste.scrollTo( { left: i * piste.clientWidth, behavior: moinsDAnimations ? 'auto' : 'smooth' } );
			marquer( i );
		}

		var attente;
		piste.addEventListener(
			'scroll',
			function () {
				clearTimeout( attente );
				attente = setTimeout( function () {
					marquer( Math.round( piste.scrollLeft / piste.clientWidth ) );
				}, 80 );
			},
			{ passive: true }
		);

		// Le trait de la diapositive en cours se remplit ; quand il est plein, on passe à la suivante.
		if ( ! moinsDAnimations ) {
			racine.classList.add( 'en-lecture' );
			traits.forEach( function ( t, j ) {
				t.firstChild.addEventListener( 'animationend', function () {
					if ( j === actuelle ) {
						aller( actuelle + 1 );
					}
				} );
			} );
			var pause = function () {
				racine.classList.add( 'en-pause' );
			};
			var reprise = function () {
				if ( ! racine.contains( document.activeElement ) ) {
					racine.classList.remove( 'en-pause' );
				}
			};
			racine.addEventListener( 'mouseenter', pause );
			racine.addEventListener( 'mouseleave', reprise );
			racine.addEventListener( 'focusin', pause );
			racine.addEventListener( 'focusout', function () {
				setTimeout( reprise, 0 );
			} );
			// Au toucher, on arrête pour de bon : la personne a pris la main.
			racine.addEventListener(
				'touchstart',
				function () {
					racine.classList.remove( 'en-lecture' );
				},
				{ passive: true }
			);
			document.addEventListener( 'visibilitychange', function () {
				racine.classList.toggle( 'en-cache', document.hidden );
			} );
		}

		marquer( 0 );
	} );
} )();
