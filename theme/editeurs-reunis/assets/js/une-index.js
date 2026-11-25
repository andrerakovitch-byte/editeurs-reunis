/**
 * L'index de l'accueil (patterns/une-index.php, inc/index.php) : la colonne de droite montre la ligne en cours.
 *
 * La ligne en cours est celle qui passe sous le repère, aux trois dixièmes de l'écran sous l'en-tête, pendant qu'on
 * fait défiler la page ; la souris, ou le clavier, la choisit aussi. La colonne reste accrochée sous l'en-tête
 * (position: sticky) : ce script l'accroche au bas de l'en-tête tel qu'il est (sa ligne verte peut passer sur deux
 * lignes) et lui donne la hauteur qui reste jusqu'au bas de l'écran, pour que sa bande (le prix, le bouton) y soit
 * toujours. Au téléphone, la colonne est cachée : le script ne fait rien et les titres restent noirs.
 */
( function () {
	'use strict';

	var index = document.querySelector( '.er-index' );
	if ( ! index ) {
		return;
	}
	var colonne = index.querySelector( '.er-index-image' );
	var lignes = Array.prototype.slice.call( index.querySelectorAll( '.er-index-ligne' ) );
	var panneaux = Array.prototype.slice.call( index.querySelectorAll( '.er-index-panneau' ) );
	if ( ! colonne || ! lignes.length || lignes.length !== panneaux.length ) {
		return;
	}
	var entete = document.querySelector( 'header.er-haut' );
	var actif = 0;
	var attente = false;

	function enService() {
		return colonne.offsetWidth > 0;
	}

	function montrer( i ) {
		if ( i === actif ) {
			return;
		}
		lignes[ actif ].classList.remove( 'est-actif' );
		panneaux[ actif ].classList.remove( 'est-actif' );
		actif = i;
		lignes[ actif ].classList.add( 'est-actif' );
		panneaux[ actif ].classList.add( 'est-actif' );
	}

	// Le haut de la colonne quand elle est accrochée : le bas de l'en-tête, tel qu'il est à l'écran.
	function accroche() {
		if ( entete ) {
			return Math.max( 0, Math.round( entete.getBoundingClientRect().bottom ) );
		}
		return parseFloat( window.getComputedStyle( colonne ).top ) || 0;
	}

	function suivre() {
		attente = false;
		var pilote = enService();
		index.classList.toggle( 'est-pilote', pilote );
		if ( ! pilote ) {
			return;
		}
		var haut = accroche();
		colonne.style.top = haut + 'px';
		var dessus = Math.max( colonne.getBoundingClientRect().top, haut );
		colonne.style.setProperty( '--er-index-h', Math.max( 360, window.innerHeight - dessus ) + 'px' );

		var repere = haut + ( window.innerHeight - haut ) * 0.3;
		var choisie = 0;
		for ( var k = 0; k < lignes.length; k++ ) {
			if ( lignes[ k ].getBoundingClientRect().top <= repere ) {
				choisie = k;
			} else {
				break;
			}
		}
		montrer( choisie );
	}

	function planifier() {
		if ( ! attente ) {
			attente = true;
			window.requestAnimationFrame( suivre );
		}
	}

	lignes.forEach( function ( ligne, k ) {
		ligne.addEventListener( 'mousemove', function () {
			if ( enService() ) {
				montrer( k );
			}
		} );
		ligne.addEventListener( 'focusin', function () {
			if ( enService() ) {
				montrer( k );
			}
		} );
	} );
	window.addEventListener( 'scroll', planifier, { passive: true } );
	window.addEventListener( 'resize', planifier );
	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( planifier );
	}
	suivre();
}() );
