/**
 * Les menus au clic de l'en-tête épuré, sur les pages d'essai (inc/essai-menus.php) :
 *
 * - le bouton d'une maison ouvre son menu et le referme, à la même place ; un clic ailleurs, ou Échap, le referme
 *   aussi (plus de croix : André, 26 novembre 2026) ;
 * - le panneau (E) : la Librairie montre les sous-rayons du rayon qu'on survole ;
 * - les grands mots (F) : au survol d'un mot, les autres pâlissent et la photo change ;
 * - le plein écran (G, et le menu ≡ sous 1 320 px) : la maison choisie à l'encre ; « Fermer » et Échap rendent la
 *   main au bouton qui l'a ouvert, et Tab ne quitte pas le plein écran tant qu'il est ouvert.
 *
 * Sans ce script, les panneaux restent fermés : on va aux pages des maisons par le pied de page.
 */
( function () {
	'use strict';

	var racine = document.documentElement;
	var boutons = Array.prototype.slice.call( document.querySelectorAll( '[data-er-em]' ) );
	if ( ! boutons.length ) {
		return;
	}
	var plan = document.getElementById( 'er-em-plan' );
	var voile = document.querySelector( '.er-em-voile' );
	var courant = null; // Le bouton dont le menu est ouvert.

	function cible( bouton ) {
		return document.getElementById( bouton.getAttribute( 'aria-controls' ) );
	}

	function fermer( rendre ) {
		if ( ! courant ) {
			return;
		}
		var bouton = courant;
		var menu = cible( bouton );
		courant = null;
		bouton.setAttribute( 'aria-expanded', 'false' );
		if ( menu ) {
			menu.hidden = true;
		}
		racine.classList.remove( 'er-em-ouvert', 'er-em-plan-ouvert' );
		if ( rendre ) {
			bouton.focus();
		}
	}

	function ouvrir( bouton, clavier ) {
		fermer( false );
		var menu = cible( bouton );
		if ( ! menu ) {
			return;
		}
		courant = bouton;
		bouton.setAttribute( 'aria-expanded', 'true' );
		menu.hidden = false;
		// Les photos du menu arrivent tout de suite, sans attendre qu'on survole un mot.
		menu.querySelectorAll( 'img[loading="lazy"]' ).forEach( function ( image ) {
			image.loading = 'eager';
		} );
		if ( menu === plan ) {
			var maison = bouton.getAttribute( 'data-er-em' );
			plan.querySelectorAll( '.er-em-colonne' ).forEach( function ( colonne ) {
				colonne.classList.toggle( 'est-active', colonne.getAttribute( 'data-colonne' ) === maison );
			} );
			if ( 'plan' === maison ) {
				plan.removeAttribute( 'data-actif' );
			} else {
				plan.setAttribute( 'data-actif', maison );
			}
			racine.classList.add( 'er-em-plan-ouvert' );
			var fermerPlan = plan.querySelector( '.er-em-fermer-plan' );
			if ( fermerPlan ) {
				fermerPlan.focus();
			}
		} else {
			racine.classList.add( 'er-em-ouvert' );
			if ( clavier ) {
				var premier = menu.querySelector( 'a' );
				if ( premier ) {
					premier.focus();
				}
			}
		}
	}

	boutons.forEach( function ( bouton ) {
		bouton.addEventListener( 'click', function ( e ) {
			if ( courant === bouton ) {
				fermer( false );
				return;
			}
			ouvrir( bouton, 0 === e.detail ); // detail 0 : la touche Entrée ou Espace, pas la souris.
		} );
	} );

	// Un clic ailleurs (sur le voile, ou sur la page hors du menu et de ses boutons) referme le menu.
	if ( voile ) {
		voile.addEventListener( 'click', function () {
			fermer( false );
		} );
	}
	document.addEventListener( 'click', function ( e ) {
		if ( ! courant || cible( courant ) === plan ) {
			return;
		}
		var menu = cible( courant );
		if ( ( menu && menu.contains( e.target ) ) || e.target.closest( '[data-er-em]' ) ) {
			return;
		}
		fermer( false );
	} );
	if ( plan ) {
		var fermerPlan = plan.querySelector( '.er-em-fermer-plan' );
		if ( fermerPlan ) {
			fermerPlan.addEventListener( 'click', function () {
				fermer( true );
			} );
		}
	}

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! courant ) {
			return;
		}
		if ( 'Escape' === e.key ) {
			fermer( true );
			return;
		}
		// Le plein écran garde la main : Tab tourne entre ses liens.
		if ( 'Tab' === e.key && plan && ! plan.hidden ) {
			var liens = Array.prototype.slice.call( plan.querySelectorAll( 'a, button' ) );
			if ( ! liens.length ) {
				return;
			}
			var premier = liens[ 0 ];
			var dernier = liens[ liens.length - 1 ];
			if ( e.shiftKey && document.activeElement === premier ) {
				e.preventDefault();
				dernier.focus();
			} else if ( ! e.shiftKey && document.activeElement === dernier ) {
				e.preventDefault();
				premier.focus();
			}
		}
	} );

	// Le panneau de la Librairie : les sous-rayons du rayon qu'on survole (ou qui a la main).
	document.querySelectorAll( '.er-em-rayons a[data-rayon]' ).forEach( function ( lien ) {
		function choisir() {
			var panneau = lien.closest( '.er-em-panneau' );
			var rayon = lien.getAttribute( 'data-rayon' );
			panneau.querySelectorAll( '.er-em-rayons a' ).forEach( function ( autre ) {
				autre.classList.toggle( 'est-actif', autre === lien );
			} );
			panneau.querySelectorAll( '.er-em-sous-rayon' ).forEach( function ( bloc ) {
				bloc.hidden = bloc.getAttribute( 'data-rayon' ) !== rayon;
			} );
		}
		lien.addEventListener( 'mouseenter', choisir );
		lien.addEventListener( 'focus', choisir );
	} );

	// Les grands mots : le mot survolé reste à l'encre, les autres pâlissent, la photo change.
	document.querySelectorAll( '.er-em-mots' ).forEach( function ( liste ) {
		var panneau = liste.closest( '.er-em-panneau' );
		var photos = panneau ? Array.prototype.slice.call( panneau.querySelectorAll( '.er-em-photos img' ) ) : [];
		var defaut = panneau ? panneau.getAttribute( 'data-photo' ) : '';
		var liens = Array.prototype.slice.call( liste.querySelectorAll( 'a' ) );

		function montrer( cle ) {
			photos.forEach( function ( photo ) {
				photo.classList.toggle( 'est-vue', photo.getAttribute( 'data-photo' ) === cle );
			} );
		}
		function sortir() {
			liste.classList.remove( 'est-survol' );
			liens.forEach( function ( l ) {
				l.classList.remove( 'est-survole' );
			} );
			montrer( defaut );
		}
		liens.forEach( function ( lien ) {
			function entrer() {
				liste.classList.add( 'est-survol' );
				liens.forEach( function ( l ) {
					l.classList.toggle( 'est-survole', l === lien );
				} );
				if ( lien.getAttribute( 'data-photo' ) ) {
					montrer( lien.getAttribute( 'data-photo' ) );
				}
			}
			lien.addEventListener( 'mouseenter', entrer );
			lien.addEventListener( 'focus', entrer );
		} );
		liste.addEventListener( 'mouseleave', sortir );
		liste.addEventListener( 'focusout', function ( e ) {
			if ( ! liste.contains( e.relatedTarget ) ) {
				sortir();
			}
		} );
	} );
}() );
