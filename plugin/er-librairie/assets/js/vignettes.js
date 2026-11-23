/**
 * Les titres liés : au survol d'un titre de livre (ou quand le clavier s'y pose), sa vignette, demandée une fois à
 * /wp-json/er/v1/vignette/<livre>. Au toucher, rien de plus : le lien mène à la fiche.
 */
( function () {
	var api = window.erVignettes && window.erVignettes.api;
	if ( ! api || ! window.fetch ) {
		return;
	}
	var cache = {};
	var carte = null;
	var courant = null;
	var minuteurOuvrir = 0;
	var minuteurFermer = 0;

	function lienDe( cible ) {
		return cible && cible.closest ? cible.closest( 'a.er-lien-livre' ) : null;
	}

	function laCarte() {
		if ( ! carte ) {
			carte = document.createElement( 'div' );
			carte.className = 'er-vignette';
			carte.id = 'er-vignette';
			carte.setAttribute( 'role', 'tooltip' );
			carte.hidden = true;
			carte.addEventListener( 'pointerenter', function () {
				clearTimeout( minuteurFermer );
			} );
			carte.addEventListener( 'pointerleave', fermerBientot );
			document.body.appendChild( carte );
		}
		return carte;
	}

	function charger( id ) {
		if ( ! cache[ id ] ) {
			cache[ id ] = fetch( api + 'vignette/' + encodeURIComponent( id ), { credentials: 'same-origin' } )
				.then( function ( r ) {
					if ( ! r.ok ) {
						throw new Error( r.status );
					}
					return r.json();
				} )
				.then( function ( d ) {
					return d.html;
				} );
			cache[ id ].catch( function () {
				delete cache[ id ];
			} );
		}
		return cache[ id ];
	}

	// Sous le titre, centrée ; au-dessus quand la place manque en bas ; jamais hors de l'écran.
	function placer( lien ) {
		var c = laCarte();
		var r = lien.getClientRects()[ 0 ] || lien.getBoundingClientRect();
		var largeur = c.offsetWidth;
		var hauteur = c.offsetHeight;
		var marge = 12;
		var x = r.left + r.width / 2 - largeur / 2;
		x = Math.max( marge, Math.min( x, document.documentElement.clientWidth - largeur - marge ) );
		var y = r.bottom + 10;
		var dessus = y + hauteur > window.innerHeight - marge && r.top - 10 - hauteur > marge;
		if ( dessus ) {
			y = r.top - 10 - hauteur;
		}
		c.style.left = x + window.scrollX + 'px';
		c.style.top = y + window.scrollY + 'px';
		c.classList.toggle( 'er-vignette--dessus', dessus );
	}

	function ouvrir( lien ) {
		var id = lien.getAttribute( 'data-livre' );
		if ( ! id ) {
			return;
		}
		courant = lien;
		charger( id ).then( function ( html ) {
			if ( courant !== lien ) {
				return;
			}
			var c = laCarte();
			c.innerHTML = html;
			c.hidden = false;
			placer( lien );
			lien.setAttribute( 'aria-describedby', 'er-vignette' );
		}, function () {} );
	}

	function fermer() {
		clearTimeout( minuteurOuvrir );
		if ( carte ) {
			carte.hidden = true;
		}
		if ( courant ) {
			courant.removeAttribute( 'aria-describedby' );
		}
		courant = null;
	}

	function fermerBientot() {
		clearTimeout( minuteurFermer );
		minuteurFermer = setTimeout( fermer, 200 );
	}

	document.addEventListener( 'pointerover', function ( e ) {
		var lien = lienDe( e.target );
		if ( ! lien || ( e.pointerType && 'mouse' !== e.pointerType ) ) {
			return;
		}
		clearTimeout( minuteurFermer );
		clearTimeout( minuteurOuvrir );
		minuteurOuvrir = setTimeout( function () {
			ouvrir( lien );
		}, 250 );
	} );
	document.addEventListener( 'pointerout', function ( e ) {
		var lien = lienDe( e.target );
		if ( ! lien || lien.contains( e.relatedTarget ) ) {
			return;
		}
		clearTimeout( minuteurOuvrir );
		fermerBientot();
	} );
	document.addEventListener( 'focusin', function ( e ) {
		var lien = lienDe( e.target );
		if ( lien ) {
			clearTimeout( minuteurFermer );
			ouvrir( lien );
		}
	} );
	document.addEventListener( 'focusout', function ( e ) {
		if ( lienDe( e.target ) ) {
			fermerBientot();
		}
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			fermer();
		}
	} );
	window.addEventListener( 'resize', fermer );
}() );
