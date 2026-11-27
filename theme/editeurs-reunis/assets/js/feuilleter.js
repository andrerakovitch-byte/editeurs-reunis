/**
 * « Feuilleter » sur la fiche d'un livre (er/fiche-feuilleter, inc/extraits.php de l'extension) : la fenêtre s'ouvre
 * sur la première page ; on passe d'une page à l'autre en glissant, avec les flèches ou au clavier ; un clic sur la
 * page l'agrandit (et la remet à sa taille) ; Échap, « Fermer » ou un clic à côté referment.
 */
( function () {
	document.querySelectorAll( '.er-feuilleter' ).forEach( function ( bloc ) {
		var ouvrir = bloc.querySelector( '.er-feuilleter__ouvrir' );
		var fenetre = bloc.querySelector( '.er-feuilleter__fenetre' );
		if ( ! ouvrir || ! fenetre || typeof fenetre.showModal !== 'function' ) {
			return;
		}
		var liste = fenetre.querySelector( '.er-feuilleter__pages' );
		var pages = Array.prototype.slice.call( liste.children );
		var compte = fenetre.querySelector( '.er-feuilleter__compte' );
		var legende = fenetre.querySelector( '.er-feuilleter__legende' );
		var avant = fenetre.querySelector( '.er-feuilleter__fleche--avant' );
		var apres = fenetre.querySelector( '.er-feuilleter__fleche--apres' );
		var courante = 0;

		function montrer( i ) {
			courante = Math.max( 0, Math.min( pages.length - 1, i ) );
			compte.textContent = ( courante + 1 ) + ' / ' + pages.length;
			legende.textContent = pages[ courante ].getAttribute( 'data-legende' ) || '';
			avant.disabled = courante === 0;
			apres.disabled = courante === pages.length - 1;
		}

		function aller( i ) {
			i = Math.max( 0, Math.min( pages.length - 1, i ) );
			pages[ i ].scrollIntoView( { behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth', inline: 'start', block: 'nearest' } );
			montrer( i );
		}

		ouvrir.addEventListener( 'click', function () {
			fenetre.showModal();
			liste.scrollLeft = 0;
			montrer( 0 );
			apres.focus();
		} );
		fenetre.querySelector( '.er-feuilleter__fermer' ).addEventListener( 'click', function () {
			fenetre.close();
		} );
		// Un clic sur le fond (à côté de la page) referme.
		fenetre.addEventListener( 'click', function ( e ) {
			if ( e.target === fenetre || e.target.classList.contains( 'er-feuilleter__page' ) ) {
				fenetre.close();
			}
		} );
		fenetre.addEventListener( 'close', function () {
			pages.forEach( function ( p ) {
				p.classList.remove( 'est-agrandie' );
			} );
			ouvrir.focus();
		} );
		avant.addEventListener( 'click', function () {
			aller( courante - 1 );
		} );
		apres.addEventListener( 'click', function () {
			aller( courante + 1 );
		} );
		fenetre.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowRight' ) {
				e.preventDefault();
				aller( courante + 1 );
			} else if ( e.key === 'ArrowLeft' ) {
				e.preventDefault();
				aller( courante - 1 );
			}
		} );
		// La page à l'écran quand on a glissé.
		var attente;
		liste.addEventListener( 'scroll', function () {
			clearTimeout( attente );
			attente = setTimeout( function () {
				// Fermée entre-temps, la fenêtre n'a plus de largeur : rien à relever (sinon « NaN / 3 », et une erreur).
				if ( ! fenetre.open || ! liste.clientWidth ) {
					return;
				}
				montrer( Math.round( liste.scrollLeft / liste.clientWidth ) );
			}, 80 );
		}, { passive: true } );
		pages.forEach( function ( p ) {
			var image = p.querySelector( 'img' );
			if ( image ) {
				image.addEventListener( 'click', function () {
					p.classList.toggle( 'est-agrandie' );
				} );
			}
		} );
	} );
}() );
