/**
 * La grille des livres (er-librairie/inc/rayonnage.php) :
 * - « Ajouter » met le livre au panier sans quitter la page (wc-ajax=add_to_cart), puis prévient le panier de
 *   l'en-tête (l'évènement wc-blocks_added_to_cart, que le bloc mini-cart écoute) ;
 * - « Couvertures » et « Liste » changent la vue sur place ; le choix se garde dans le navigateur et dans l'adresse ;
 * - « Voir 24 livres de plus » ajoute la page suivante sous la grille, et la pagination se cache.
 * Après un filtre (facettes.js remplace la grille), l'évènement er:livres fait réappliquer la vue.
 */
( function () {
	'use strict';

	var CLE = 'er-vue';
	document.documentElement.classList.add( 'er-rayonnage-js' );

	function vueDeLAdresse() {
		var v = new URLSearchParams( window.location.search ).get( 'vue' );
		return v === 'liste' || v === 'couvertures' ? v : null;
	}

	function vueGardee() {
		try {
			return window.localStorage.getItem( CLE );
		} catch ( e ) {
			return null;
		}
	}

	function appliquer( vue ) {
		document.querySelectorAll( '.er-livres' ).forEach( function ( ul ) {
			ul.classList.toggle( 'er-livres--liste', vue === 'liste' );
			ul.classList.toggle( 'er-livres--couvertures', vue !== 'liste' );
		} );
		document.querySelectorAll( '.er-vue__choix' ).forEach( function ( a ) {
			a.setAttribute( 'aria-pressed', a.dataset.vue === vue ? 'true' : 'false' );
		} );
		// Le formulaire des filtres la garde aussi.
		var form = document.querySelector( '[data-er-facettes] form' );
		if ( form ) {
			var champ = form.querySelector( 'input[name="vue"]' );
			if ( ! champ ) {
				champ = document.createElement( 'input' );
				champ.type = 'hidden';
				champ.name = 'vue';
				form.appendChild( champ );
			}
			champ.value = vue;
		}
	}

	function initialiser() {
		if ( ! document.querySelector( '.er-vue' ) ) {
			return;
		}
		var v = vueDeLAdresse() || vueGardee();
		if ( v === 'liste' || v === 'couvertures' ) {
			appliquer( v );
		}
	}

	function ajouter( lien ) {
		var url = lien.dataset.ajax;
		if ( ! url || lien.classList.contains( 'is-charge' ) ) {
			return false;
		}
		var corps = new FormData();
		corps.append( 'product_id', lien.dataset.product_id );
		corps.append( 'quantity', '1' );
		lien.classList.add( 'is-charge' );
		fetch( url, { method: 'POST', body: corps, credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( r ) {
				if ( ! r || r.error ) {
					window.location.href = ( r && r.product_url ) || lien.href;
					return;
				}
				lien.classList.remove( 'is-charge' );
				lien.classList.add( 'is-ajoute' );
				lien.textContent = lien.dataset.ajoute || lien.textContent;
				document.body.dispatchEvent( new CustomEvent( 'wc-blocks_added_to_cart', { bubbles: true, cancelable: true, detail: { preserveCartData: false } } ) );
			} )
			.catch( function () {
				window.location.href = lien.href;
			} );
		return true;
	}

	function suite( bouton ) {
		var zone = bouton.closest( '.er-rayonnage__livres' ) || document;
		var suivant = zone.querySelector( '.wp-block-query-pagination-next' );
		var ul = zone.querySelector( '.er-livres' );
		if ( ! suivant || ! ul ) {
			return;
		}
		bouton.disabled = true;
		bouton.classList.add( 'is-charge' );
		fetch( suivant.href, { credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.text();
			} )
			.then( function ( html ) {
				var doc = new DOMParser().parseFromString( html, 'text/html' );
				var neuf = doc.querySelector( '.er-rayonnage__livres .er-livres' ) || doc.querySelector( '.er-livres' );
				if ( ! neuf ) {
					window.location.href = suivant.href;
					return;
				}
				var chapitres = ul.querySelectorAll( '.er-chapitre' );
				var dernier = chapitres.length ? chapitres[ chapitres.length - 1 ].dataset.epoque : null;
				var premier = null;
				Array.prototype.slice.call( neuf.children ).forEach( function ( li ) {
					if ( li.classList.contains( 'er-liste-tete' ) || li.classList.contains( 'er-module' ) ) {
						return;
					}
					// Le chapitre en cours continue : son titre ne se répète pas.
					if ( li.classList.contains( 'er-chapitre' ) && li.dataset.epoque === dernier ) {
						dernier = null;
						return;
					}
					dernier = null;
					ul.appendChild( li );
					if ( ! premier && ! li.classList.contains( 'er-chapitre' ) ) {
						premier = li;
					}
				} );
				var pag = zone.querySelector( '.wp-block-query-pagination' );
				var pagNeuve = doc.querySelector( '.er-rayonnage__livres .wp-block-query-pagination' );
				var s = zone.querySelector( '.er-suite' );
				var sNeuve = doc.querySelector( '.er-rayonnage__livres .er-suite' );
				if ( pag ) {
					if ( pagNeuve ) {
						pag.replaceWith( pagNeuve );
					} else {
						pag.remove();
					}
				}
				if ( s ) {
					if ( sNeuve ) {
						s.replaceWith( sNeuve );
					} else {
						s.remove();
					}
				}
				// Le premier livre ajouté prend le relais du clavier.
				var lienPremier = premier && premier.querySelector( '.er-carte__titre a' );
				if ( lienPremier ) {
					lienPremier.focus( { preventScroll: true } );
				}
			} )
			.catch( function () {
				window.location.href = suivant.href;
			} );
	}

	document.addEventListener( 'click', function ( ev ) {
		var choix = ev.target.closest( '.er-vue__choix' );
		if ( choix ) {
			ev.preventDefault();
			var vue = choix.dataset.vue;
			appliquer( vue );
			try {
				window.localStorage.setItem( CLE, vue );
			} catch ( e ) {}
			var u = new URL( window.location.href );
			u.searchParams.set( 'vue', vue );
			window.history.replaceState( window.history.state, '', u.toString().replace( /%2C/gi, ',' ) );
			return;
		}
		var lien = ev.target.closest( '.er-carte__ajouter' );
		if ( lien && ajouter( lien ) ) {
			ev.preventDefault();
			return;
		}
		var plus = ev.target.closest( '.er-suite__plus' );
		if ( plus ) {
			ev.preventDefault();
			suite( plus );
		}
	} );

	document.addEventListener( 'er:livres', initialiser );
	initialiser();
}() );
