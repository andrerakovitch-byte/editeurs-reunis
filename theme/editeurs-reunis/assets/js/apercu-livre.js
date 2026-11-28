/**
 * L'aperçu d'un livre au survol de son lien, dans le texte d'une page (28 nov. 2026, à l'essai sur la page type).
 * Le serveur a donné à chaque lien l'identifiant de son livre (data-apercu-livre) et posé les cartes, cachées, à la fin du
 * contenu (.er-apercus) : le dessin de la recherche. Trois dessins, selon html[data-er-survol] (inc/page-type.php) :
 * - a, la couverture qui suit : la couverture seule, qui suit la souris avec un peu de retard ;
 * - b, la carte au-dessus : la carte du livre, posée au-dessus du lien, là où la souris l'a touché ;
 * - c, dans la marge : la carte dans la colonne de gauche du triptyque, à la hauteur du lien.
 * Seulement avec une souris ; au doigt, le lien mène au livre, sans aperçu.
 */
( function () {
	'use strict';
	if ( ! window.matchMedia || ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
		return;
	}
	var modeles = document.querySelector( '.er-apercus' );
	if ( ! modeles ) {
		return;
	}
	var calme = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var actuel = null; // { lien, el, mode }
	var souris = { x: 0, y: 0 }, suivie = { x: 0, y: 0 }, image = 0;

	function dessin() {
		return document.documentElement.getAttribute( 'data-er-survol' ) || '';
	}

	function modele( id ) {
		return modeles.querySelector( '.er-apercu-modele[data-livre="' + id + '"]' );
	}

	function fermer() {
		if ( ! actuel ) {
			return;
		}
		var el = actuel.el;
		el.classList.remove( 'est-vu' );
		window.setTimeout( function () { el.remove(); }, calme ? 0 : 260 );
		if ( image ) {
			window.cancelAnimationFrame( image );
			image = 0;
		}
		actuel = null;
	}

	// a : la couverture suit la souris, le point d'accroche avance d'un cinquième par image.
	function suivre() {
		if ( ! actuel || 'a' !== actuel.mode ) {
			return;
		}
		suivie.x += ( souris.x - suivie.x ) * ( calme ? 1 : 0.2 );
		suivie.y += ( souris.y - suivie.y ) * ( calme ? 1 : 0.2 );
		actuel.el.style.transform = 'translate(' + Math.round( suivie.x + 18 ) + 'px,' + Math.round( suivie.y + 18 ) + 'px)';
		image = window.requestAnimationFrame( suivre );
	}

	function ouvrir( lien, e ) {
		var mode = dessin();
		var source = modele( lien.getAttribute( 'data-apercu-livre' ) );
		if ( ! mode || ! source ) {
			return;
		}
		var el = document.createElement( 'div' );
		el.setAttribute( 'aria-hidden', 'true' );
		if ( 'a' === mode ) {
			var couv = source.querySelector( '.er-r-vue-cadre img' );
			if ( ! couv ) {
				return;
			}
			el.className = 'er-apercu er-apercu-suit';
			el.appendChild( couv.cloneNode( false ) );
			document.body.appendChild( el );
			souris.x = suivie.x = e.clientX;
			souris.y = suivie.y = e.clientY;
		} else {
			el.className = 'er-apercu er-apercu-carte er-apercu-' + mode;
			el.innerHTML = source.innerHTML;
			el.querySelectorAll( 'a' ).forEach( function ( a ) { a.removeAttribute( 'href' ); a.setAttribute( 'tabindex', '-1' ); } );
			document.body.appendChild( el );
			var r = lien.getBoundingClientRect();
			var largeur = el.offsetWidth, hauteur = el.offsetHeight;
			var x, y;
			if ( 'c' === mode ) {
				// La colonne de gauche du triptyque : le bord droit de la carte à 40 px du texte.
				var module = lien.closest( '.er-pt' ) || document.body;
				var mr = module.getBoundingClientRect(), cs = window.getComputedStyle( module );
				var gauche = mr.left + parseFloat( cs.paddingLeft ), droite = mr.right - parseFloat( cs.paddingRight );
				var colonne = ( droite - gauche - 24 ) / 3;
				x = gauche + colonne - largeur - 40;
				y = r.top - 8;
				if ( x < gauche ) {
					x = gauche;
				}
			} else {
				// Au-dessus du lien, centrée sur l'endroit où la souris l'a touché, sans sortir de l'écran.
				x = Math.min( Math.max( 16, e.clientX - largeur / 2 ), document.documentElement.clientWidth - largeur - 16 );
				y = r.top - hauteur - 12;
				if ( y < 72 ) {
					y = r.bottom + 12;
				}
			}
			el.style.left = Math.round( x + window.scrollX ) + 'px';
			el.style.top = Math.round( y + window.scrollY ) + 'px';
		}
		actuel = { lien: lien, el: el, mode: mode };
		// Une image plus tard, pour que la transition parte.
		window.requestAnimationFrame( function () { el.classList.add( 'est-vu' ); } );
		if ( 'a' === mode ) {
			suivre();
		}
	}

	document.addEventListener( 'mouseover', function ( e ) {
		var lien = e.target.closest && e.target.closest( 'a[data-apercu-livre]' );
		if ( ! lien || ( actuel && actuel.lien === lien ) ) {
			return;
		}
		fermer();
		ouvrir( lien, e );
	} );
	document.addEventListener( 'mouseout', function ( e ) {
		if ( ! actuel ) {
			return;
		}
		var vers = e.relatedTarget;
		if ( vers && actuel.lien.contains( vers ) ) {
			return;
		}
		if ( e.target.closest && e.target.closest( 'a[data-apercu-livre]' ) === actuel.lien ) {
			fermer();
		}
	} );
	document.addEventListener( 'mousemove', function ( e ) {
		souris.x = e.clientX;
		souris.y = e.clientY;
	}, { passive: true } );
	window.addEventListener( 'scroll', function () {
		if ( actuel && 'a' !== actuel.mode ) {
			fermer();
		}
	}, { passive: true } );
}() );
