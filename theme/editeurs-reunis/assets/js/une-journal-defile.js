/**
 * L'accueil, le journal qui défile de côté (inc/journal-defile.php) : les deux flèches avancent ou reculent d'un
 * article, et le trait sous la ligne dit la part qu'on voit et où l'on en est. Sans ce script, la ligne défile au doigt,
 * au pavé ou au clavier, sans flèches ni trait.
 */
( function () {
	'use strict';

	var reduit = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	document.querySelectorAll( '.er-journal-defile' ).forEach( function ( module ) {
		var piste = module.querySelector( '.er-jd-piste' );
		var fleches = module.querySelectorAll( '.er-jd-fleche' );
		if ( ! piste || 2 !== fleches.length ) {
			return;
		}

		// D'un article (sa largeur et l'écart).
		function pas() {
			var article = piste.querySelector( '.er-jd-article' );
			var ecart = parseFloat( window.getComputedStyle( piste ).columnGap ) || 0;
			return article ? article.getBoundingClientRect().width + ecart : piste.clientWidth * 0.8;
		}

		var attente = 0;
		function maj() {
			attente = 0;
			var max = piste.scrollWidth - piste.clientWidth;
			var x = Math.max( 0, Math.min( piste.scrollLeft, max ) );
			var vu = piste.scrollWidth > 0 ? Math.min( 1, piste.clientWidth / piste.scrollWidth ) : 1;
			var ou = max > 0 ? x / max : 0;
			module.style.setProperty( '--er-jd-w', ( vu * 100 ).toFixed( 3 ) + '%' );
			module.style.setProperty( '--er-jd-x', ( ou * ( 1 - vu ) * 100 ).toFixed( 3 ) + '%' );
			fleches[ 0 ].disabled = max <= 0 || x <= 2;
			fleches[ 1 ].disabled = max <= 0 || x >= max - 2;
			module.classList.toggle( 'est-court', max <= 0 );
		}
		function plusTard() {
			if ( ! attente ) {
				attente = window.requestAnimationFrame( maj );
			}
		}

		fleches.forEach( function ( fleche ) {
			fleche.hidden = false;
			fleche.addEventListener( 'click', function () {
				piste.scrollBy( {
					left: Number( fleche.getAttribute( 'data-sens' ) ) * pas(),
					behavior: reduit.matches ? 'auto' : 'smooth',
				} );
			} );
		} );
		piste.addEventListener( 'scroll', plusTard, { passive: true } );
		window.addEventListener( 'resize', plusTard );
		// Les images arrivent après coup (chargement paresseux) : la longueur de la ligne peut changer.
		piste.querySelectorAll( 'img' ).forEach( function ( img ) {
			if ( ! img.complete ) {
				img.addEventListener( 'load', plusTard, { once: true } );
			}
		} );

		module.classList.add( 'est-pilote' );
		maj();
	} );
} )();
