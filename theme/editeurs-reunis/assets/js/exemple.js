/**
 * Les pages d'exemple de la bêta sont les maquettes du canevas, dessinées à 1 440 px. Au-dessus de 1 100 px, elles
 * s'étirent d'elles-mêmes ; au-dessous, on garde la maquette entière, réduite à la largeur de l'écran, plutôt qu'une
 * mise en page qui casse. Elles seront refaites en blocs, et alors pour le téléphone.
 */
( function () {
	'use strict';
	var cadres = Array.prototype.slice.call( document.querySelectorAll( '.er-exemple-cadre' ) );
	if ( ! cadres.length ) {
		return;
	}
	var LARGEUR = 1440;
	var SEUIL = 1100;
	function ajuster() {
		cadres.forEach( function ( cadre ) {
			var dedans = cadre.firstElementChild;
			var largeur = cadre.clientWidth;
			if ( largeur >= SEUIL ) {
				dedans.style.width = '';
				dedans.style.transform = '';
				cadre.style.height = '';
				return;
			}
			var echelle = largeur / LARGEUR;
			dedans.style.width = LARGEUR + 'px';
			dedans.style.transformOrigin = '0 0';
			dedans.style.transform = 'scale(' + echelle + ')';
			cadre.style.height = Math.ceil( dedans.offsetHeight * echelle ) + 'px';
		} );
	}
	ajuster();
	window.addEventListener( 'resize', ajuster );
	window.addEventListener( 'load', ajuster );
}() );
