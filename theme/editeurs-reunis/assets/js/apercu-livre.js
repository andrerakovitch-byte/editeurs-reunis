/**
 * L'aperçu d'un livre au survol de son lien dans un texte (inc/apercu-livre.php ; retenu le 28 nov. 2026) : la carte
 * du livre, le dessin de la recherche sans prix, posée au-dessus du lien, là où la souris l'a touché ; dessous s'il
 * n'y a pas la place. Le serveur a donné à chaque lien l'identifiant de son livre (data-apercu-livre) et posé les
 * cartes, cachées, à la fin du contenu (.er-apercus). Seulement avec une souris ; au doigt, le lien mène au livre.
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
	var actuel = null; // { lien, el }

	function fermer() {
		if ( ! actuel ) {
			return;
		}
		var el = actuel.el;
		el.classList.remove( 'est-vu' );
		window.setTimeout( function () { el.remove(); }, calme ? 0 : 260 );
		actuel = null;
	}

	function ouvrir( lien, e ) {
		// Un lien qui montre déjà une couverture n'a pas besoin d'aperçu.
		var source = lien.querySelector( 'img' ) ? null : modeles.querySelector( '.er-apercu-modele[data-modele="' + lien.getAttribute( 'data-apercu-livre' ) + '"]' );
		if ( ! source ) {
			return;
		}
		var el = document.createElement( 'div' );
		el.className = 'er-apercu';
		el.setAttribute( 'aria-hidden', 'true' );
		el.innerHTML = source.innerHTML;
		el.querySelectorAll( 'a' ).forEach( function ( a ) { a.removeAttribute( 'href' ); a.setAttribute( 'tabindex', '-1' ); } );
		document.body.appendChild( el );
		var r = lien.getBoundingClientRect();
		var largeur = el.offsetWidth, hauteur = el.offsetHeight;
		var x = Math.min( Math.max( 16, e.clientX - largeur / 2 ), document.documentElement.clientWidth - largeur - 16 );
		var y = r.top - hauteur - 12;
		if ( y < 72 ) {
			y = r.bottom + 12;
			el.classList.add( 'est-dessous' );
		}
		el.style.left = Math.round( x + window.scrollX ) + 'px';
		el.style.top = Math.round( y + window.scrollY ) + 'px';
		actuel = { lien: lien, el: el };
		// Une image plus tard, pour que la transition parte.
		window.requestAnimationFrame( function () { el.classList.add( 'est-vu' ); } );
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
		if ( e.relatedTarget && actuel.lien.contains( e.relatedTarget ) ) {
			return;
		}
		if ( e.target.closest && e.target.closest( 'a[data-apercu-livre]' ) === actuel.lien ) {
			fermer();
		}
	} );
	window.addEventListener( 'scroll', fermer, { passive: true } );
}() );
