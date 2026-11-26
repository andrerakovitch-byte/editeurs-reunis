/**
 * La phrase à trous, en haut de l'accueil (inc/accueil-phrase.php) : au survol, au toucher ou au clavier, le mot passe
 * à l'encre, sa réponse se réécrit dessous et son image paraît à droite. Les réponses qu'on ne voit pas ne prennent
 * ni le doigt ni le clavier (inert). Sans ce script, la première réponse reste, et ses mots ne font rien.
 */
( function () {
	'use strict';
	document.querySelectorAll( '.er-ap' ).forEach( function ( ap ) {
		var mots = Array.prototype.slice.call( ap.querySelectorAll( '.er-ap-mot' ) );
		var choisir = function ( cle ) {
			mots.forEach( function ( mot ) {
				mot.setAttribute( 'aria-pressed', mot.getAttribute( 'data-cle' ) === cle ? 'true' : 'false' );
			} );
			ap.querySelectorAll( '.er-ap-reponse' ).forEach( function ( reponse ) {
				var vue = reponse.getAttribute( 'data-cle' ) === cle;
				reponse.classList.toggle( 'est-vue', vue );
				reponse.toggleAttribute( 'inert', ! vue );
			} );
			ap.querySelectorAll( '.er-ap-image' ).forEach( function ( image ) {
				image.classList.toggle( 'est-vue', image.getAttribute( 'data-cle' ) === cle );
			} );
		};
		mots.forEach( function ( mot ) {
			var cle = mot.getAttribute( 'data-cle' );
			[ 'mouseenter', 'focus', 'click' ].forEach( function ( type ) {
				mot.addEventListener( type, function () {
					if ( 'true' !== mot.getAttribute( 'aria-pressed' ) ) {
						choisir( cle );
					}
				} );
			} );
		} );
	} );
} )();
