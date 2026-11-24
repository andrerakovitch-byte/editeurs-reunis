/**
 * La bande de page (inc/bande.php) :
 * - accrochée sous la ligne de la maison, elle prend la classe « est-accrochee » : une ombre d'un pixel, et le nom de la
 *   page se glisse à gauche (style.css), s'il y a la place (sinon « est-sans-nom ») ;
 * - dans un sommaire (er-bande-sommaire), le trait suit la lecture : l'entrée de la partie qu'on lit est marquée ;
 * - l'entrée courante reste en vue quand la bande défile de côté (au téléphone), avec un fondu du côté où il reste à
 *   voir ;
 * - un groupe replié ouvre son menu sous la bande, sous son entrée ; Échap ou un clic ailleurs le referme.
 * Sans ce script, la bande s'accroche quand même (position: sticky) et ses liens mènent où ils doivent.
 */
( function () {
	'use strict';

	var bandes = Array.prototype.slice.call( document.querySelectorAll( '.er-bande' ) );
	if ( ! bandes.length ) {
		return;
	}
	var calme = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	function hautAccroche( bande ) {
		var top = parseFloat( window.getComputedStyle( bande ).top );
		return isNaN( top ) ? 0 : top;
	}

	bandes.forEach( function ( bande ) {
		var repere = bande.previousElementSibling && bande.previousElementSibling.classList.contains( 'er-bande-repere' ) ? bande.previousElementSibling : null;
		var liste = bande.querySelector( '.er-bande-liste' );
		var nom = bande.querySelector( '.er-bande-nom' );
		var sommaire = bande.classList.contains( 'er-bande-sommaire' );
		var liens = Array.prototype.slice.call( bande.querySelectorAll( '.er-bande-liste a.er-bande-lien' ) );
		var cibles = [];
		if ( sommaire ) {
			liens.forEach( function ( lien ) {
				var id = decodeURIComponent( ( lien.getAttribute( 'href' ) || '' ).replace( /^#/, '' ) );
				var cible = id ? document.getElementById( id ) : null;
				if ( cible ) {
					cibles.push( { lien: lien, cible: cible } );
				}
			} );
		}

		/* L'entrée courante en vue, et les fondus aux bords. */
		function fondus() {
			if ( ! liste ) {
				return;
			}
			var reste = liste.scrollWidth - liste.clientWidth - liste.scrollLeft;
			liste.classList.toggle( 'est-a-droite', reste > 2 );
			liste.classList.toggle( 'est-a-gauche', liste.scrollLeft > 2 );
		}
		function montrer( el, doux ) {
			if ( ! liste || ! el || liste.scrollWidth <= liste.clientWidth ) {
				return;
			}
			var li = el.closest( 'li' ) || el;
			var gauche = li.offsetLeft - liste.offsetLeft;
			var droite = gauche + li.offsetWidth;
			var cible = null;
			if ( gauche < liste.scrollLeft + 24 ) {
				cible = Math.max( 0, gauche - 24 );
			} else if ( droite > liste.scrollLeft + liste.clientWidth - 56 ) {
				cible = droite - liste.clientWidth + 56;
			}
			if ( null !== cible ) {
				liste.scrollTo( { left: cible, behavior: doux && ! calme.matches ? 'smooth' : 'auto' } );
			}
		}
		if ( liste ) {
			liste.addEventListener( 'scroll', fondus, { passive: true } );
			montrer( bande.querySelector( '[aria-current], .est-courant' ), false );
			fondus();
		}

		/* Le sommaire : la partie qu'on lit. */
		var courant = null;
		function marquer( lien ) {
			if ( lien === courant ) {
				return;
			}
			cibles.forEach( function ( c ) {
				if ( c.lien === lien ) {
					c.lien.setAttribute( 'aria-current', 'true' );
				} else {
					c.lien.removeAttribute( 'aria-current' );
				}
			} );
			courant = lien;
			montrer( lien, true );
		}
		function lecture( haut ) {
			if ( ! cibles.length ) {
				return;
			}
			var seuil = haut + bande.offsetHeight + 24;
			var choisi = cibles[ 0 ].lien;
			for ( var i = 0; i < cibles.length; i++ ) {
				if ( cibles[ i ].cible.getBoundingClientRect().top <= seuil ) {
					choisi = cibles[ i ].lien;
				}
			}
			if ( window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2 ) {
				choisi = cibles[ cibles.length - 1 ].lien;
			}
			marquer( choisi );
		}
		/* Une ancre ne se cache pas sous l'en-tête et la bande. */
		function marges( haut ) {
			cibles.forEach( function ( c ) {
				c.cible.style.scrollMarginTop = ( haut + bande.offsetHeight + 16 ) + 'px';
			} );
		}

		/* Accrochée ou non. */
		var attente = false;
		function regler() {
			attente = false;
			var haut = hautAccroche( bande );
			var accrochee = repere ? repere.getBoundingClientRect().top < haut - 0.5 : bande.getBoundingClientRect().top <= haut + 0.5;
			if ( accrochee !== bande.classList.contains( 'est-accrochee' ) ) {
				// Le nom ne se glisse que s'il y a la place : les entrées passent avant.
				if ( accrochee && nom && liste && liste.firstElementChild ) {
					var dernier = liste.lastElementChild;
					var contenu = dernier.offsetLeft + dernier.offsetWidth - liste.firstElementChild.offsetLeft;
					bande.classList.toggle( 'est-sans-nom', contenu + nom.scrollWidth + 49 > liste.clientWidth + 1 );
				}
				bande.classList.toggle( 'est-accrochee', accrochee );
				window.setTimeout( fondus, 320 ); // le nom qui entre prend de la place
			}
			lecture( haut );
		}
		function demander() {
			if ( ! attente ) {
				attente = true;
				window.requestAnimationFrame( regler );
			}
		}
		window.addEventListener( 'scroll', demander, { passive: true } );
		window.addEventListener( 'resize', function () {
			marges( hautAccroche( bande ) );
			fondus();
			demander();
		} );
		marges( hautAccroche( bande ) );
		regler();
		/* La hauteur de la ligne se mesure après ce script (entete.js) : on règle encore une fois. */
		window.addEventListener( 'load', function () {
			marges( hautAccroche( bande ) );
			regler();
		} );

		/* Les groupes repliés. */
		var boutons = Array.prototype.slice.call( bande.querySelectorAll( '.er-bande-menu > button' ) );
		function fermer( bouton, rendre ) {
			var panneau = document.getElementById( bouton.getAttribute( 'aria-controls' ) );
			bouton.setAttribute( 'aria-expanded', 'false' );
			if ( panneau ) {
				panneau.hidden = true;
			}
			if ( rendre ) {
				bouton.focus();
			}
		}
		boutons.forEach( function ( bouton ) {
			var panneau = document.getElementById( bouton.getAttribute( 'aria-controls' ) );
			if ( ! panneau ) {
				return;
			}
			bouton.addEventListener( 'click', function () {
				var ouvert = 'true' === bouton.getAttribute( 'aria-expanded' );
				boutons.forEach( function ( b ) {
					fermer( b, false );
				} );
				if ( ouvert ) {
					return;
				}
				var cadre = bande.getBoundingClientRect();
				var rect = bouton.getBoundingClientRect();
				panneau.hidden = false;
				var gauche = Math.min( rect.left - cadre.left - 20, cadre.width - panneau.offsetWidth - 8 );
				panneau.style.setProperty( '--er-panneau-gauche', Math.max( 8, gauche ) + 'px' );
				bouton.setAttribute( 'aria-expanded', 'true' );
			} );
			panneau.addEventListener( 'keydown', function ( e ) {
				if ( 'Escape' === e.key ) {
					fermer( bouton, true );
				}
			} );
		} );
		if ( boutons.length ) {
			document.addEventListener( 'click', function ( e ) {
				if ( ! bande.contains( e.target ) ) {
					boutons.forEach( function ( b ) {
						fermer( b, false );
					} );
				}
			} );
			bande.addEventListener( 'keydown', function ( e ) {
				if ( 'Escape' === e.key ) {
					boutons.forEach( function ( b ) {
						if ( 'true' === b.getAttribute( 'aria-expanded' ) ) {
							fermer( b, true );
						}
					} );
				}
			} );
			/* La bande défile de côté : un menu ouvert ne reste pas en l'air. */
			if ( liste ) {
				liste.addEventListener( 'scroll', function () {
					boutons.forEach( function ( b ) {
						fermer( b, false );
					} );
				}, { passive: true } );
			}
		}
	} );
}() );
