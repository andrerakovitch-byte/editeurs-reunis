/**
 * « Filtrer par » sans recharger la page (er-librairie/inc/facettes.php, assets/css/19-facettes.css).
 *
 * Une case cochée : la page filtrée est demandée au site, et la colonne (ses comptes) et les livres sont remplacés
 * par les siens ; l'adresse suit, et se partage. La frise de l'époque bascule entre « Écrit pendant » et « Parle de » ;
 * le champ d'un auteur ou d'une maison trie la liste en tapant. Au téléphone, la colonne s'ouvre en panneau plein
 * écran, fermé par « Voir les N livres ». Sans JavaScript, le formulaire s'envoie par son bouton.
 */
( function () {
	'use strict';

	if ( ! document.querySelector( '[data-er-facettes]' ) ) {
		return;
	}

	var PARAMS = [ 'rayons', 'langues', 'epoques', 'ecriture', 'genres', 'sujets', 'auteurs', 'maisons', 'decennies', 'dispo' ];
	var GARDES = [ 'orderby', 's', 'post_type' ];
	var etroit = window.matchMedia( '(max-width: 781px)' );
	var mode = null;
	var requete = null;
	var recherche = window.location.search;

	function colonne() {
		return document.querySelector( '[data-er-facettes]' );
	}

	function sansAccents( texte ) {
		return texte.toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' );
	}

	function basculer( m ) {
		var c = colonne();
		mode = m;
		c.querySelectorAll( '.er-frise' ).forEach( function ( f ) {
			f.hidden = f.dataset.frise !== m;
		} );
		c.querySelectorAll( '.er-frise__bascule button' ).forEach( function ( b ) {
			b.setAttribute( 'aria-pressed', String( b.dataset.mode === m ) );
		} );
	}

	function preparer( c, garde ) {
		c.classList.add( 'is-js' );
		c.querySelectorAll( '.er-frise__bascule, .er-facette__chercher' ).forEach( function ( e ) {
			e.hidden = false;
		} );
		var b = c.querySelector( '.er-frise__bascule' );
		if ( b ) {
			basculer( mode || b.dataset.mode );
		}
		if ( garde ) {
			c.querySelectorAll( 'details[data-facette]' ).forEach( function ( d ) {
				if ( d.dataset.facette in garde.ouverts ) {
					d.open = garde.ouverts[ d.dataset.facette ];
				}
				var plus = d.querySelector( '.er-facette__plus' );
				if ( plus && garde.plus[ d.dataset.facette ] ) {
					plus.open = true;
				}
				var champ = d.querySelector( '.er-facette__chercher input' );
				if ( champ && garde.cherche[ d.dataset.facette ] ) {
					champ.value = garde.cherche[ d.dataset.facette ];
					chercher( champ );
				}
			} );
		} else if ( etroit.matches ) {
			// Au téléphone, seules les facettes où un choix est posé restent ouvertes, avec l'époque.
			c.querySelectorAll( 'details[data-facette]' ).forEach( function ( d ) {
				d.open = 'epoque' === d.dataset.facette || !! d.querySelector( '.er-facette__actifs' );
			} );
		}
	}

	function adresse( form ) {
		var url = new URL( form.getAttribute( 'action' ), window.location.href );
		var donnees = new FormData( form );
		PARAMS.concat( GARDES, [ 'sans-date' ] ).forEach( function ( p ) {
			url.searchParams.delete( p );
		} );
		PARAMS.forEach( function ( p ) {
			var v = donnees.getAll( p + '[]' );
			if ( v.length ) {
				url.searchParams.set( p, v.join( ',' ) );
			}
		} );
		if ( 'non' === donnees.getAll( 'sans-date' ).pop() ) {
			url.searchParams.set( 'sans-date', 'non' );
		}
		GARDES.forEach( function ( p ) {
			var v = donnees.get( p );
			if ( v ) {
				url.searchParams.set( p, v );
			}
		} );
		return url.toString().replace( /%2C/gi, ',' );
	}

	function charger( url, pousser ) {
		var c = colonne();
		var garde = { ouverts: {}, plus: {}, cherche: {} };
		c.querySelectorAll( 'details[data-facette]' ).forEach( function ( d ) {
			garde.ouverts[ d.dataset.facette ] = d.open;
			var plus = d.querySelector( '.er-facette__plus' );
			garde.plus[ d.dataset.facette ] = !! ( plus && plus.open );
			var champ = d.querySelector( '.er-facette__chercher input' );
			garde.cherche[ d.dataset.facette ] = champ ? champ.value : '';
		} );
		var actif = document.activeElement;
		var focus = actif && actif.name ? { name: actif.name, value: actif.value } : null;
		var ouvert = c.classList.contains( 'is-ouvert' );
		var panneau = c.querySelector( '.er-facettes__panneau' );
		var defile = panneau ? panneau.scrollTop : 0;

		if ( requete ) {
			requete.abort();
		}
		requete = new AbortController();
		document.documentElement.classList.add( 'er-facettes-charge' );
		fetch( url, { signal: requete.signal, credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.text();
			} )
			.then( function ( html ) {
				var doc = new DOMParser().parseFromString( html, 'text/html' );
				var neuve = doc.querySelector( '[data-er-facettes]' );
				var livres = doc.querySelector( '.er-rayonnage__livres' );
				var ici = document.querySelector( '.er-rayonnage__livres' );
				if ( ! neuve || ! livres || ! ici ) {
					window.location.href = url;
					return;
				}
				c.replaceWith( neuve );
				ici.replaceWith( livres );
				preparer( neuve, garde );
				if ( ouvert ) {
					neuve.classList.add( 'is-ouvert' );
					neuve.querySelector( '.er-facettes__ouvrir' ).setAttribute( 'aria-expanded', 'true' );
				}
				var p = neuve.querySelector( '.er-facettes__panneau' );
				if ( p ) {
					p.scrollTop = defile;
				}
				if ( focus ) {
					var meme = Array.prototype.find.call( neuve.querySelectorAll( '[name="' + focus.name + '"]' ), function ( e ) {
						return e.value === focus.value;
					} );
					if ( meme ) {
						meme.focus( { preventScroll: true } );
					}
				}
				document.title = doc.title;
				if ( pousser ) {
					window.history.pushState( { erFacettes: true }, '', url );
					recherche = window.location.search;
				}
			} )
			.catch( function ( e ) {
				if ( 'AbortError' !== e.name ) {
					window.location.href = url;
				}
			} )
			.finally( function () {
				document.documentElement.classList.remove( 'er-facettes-charge' );
			} );
	}

	function chercher( champ ) {
		var section = champ.closest( '[data-facette]' );
		var q = sansAccents( champ.value.trim() );
		var vus = 0;
		section.querySelectorAll( '.er-facette__option' ).forEach( function ( o ) {
			var garde = ! q || -1 !== o.dataset.nom.indexOf( q );
			o.hidden = ! garde;
			vus += garde ? 1 : 0;
		} );
		var plus = section.querySelector( '.er-facette__plus' );
		if ( plus && q ) {
			plus.open = true;
		}
		var rien = section.querySelector( '.er-facette__rien' );
		if ( rien ) {
			rien.hidden = vus > 0;
		}
	}

	function ouvrir( oui ) {
		var c = colonne();
		c.classList.toggle( 'is-ouvert', oui );
		document.documentElement.classList.toggle( 'er-facettes-ouvertes', oui );
		c.querySelector( '.er-facettes__ouvrir' ).setAttribute( 'aria-expanded', String( oui ) );
		if ( oui ) {
			var titre = c.querySelector( '.er-facettes__titre' );
			titre.setAttribute( 'tabindex', '-1' );
			titre.focus();
		} else {
			c.querySelector( '.er-facettes__ouvrir' ).focus();
		}
	}

	document.addEventListener( 'change', function ( e ) {
		var form = e.target.closest( '.er-facettes__panneau' );
		if ( form && 'checkbox' === e.target.type ) {
			charger( adresse( form ), true );
		}
	} );

	document.addEventListener( 'input', function ( e ) {
		if ( e.target.closest( '.er-facette__chercher' ) ) {
			chercher( e.target );
		}
	} );

	document.addEventListener( 'submit', function ( e ) {
		var form = e.target.closest( '.er-facettes__panneau' );
		if ( ! form ) {
			return;
		}
		e.preventDefault();
		if ( colonne().classList.contains( 'is-ouvert' ) ) {
			ouvrir( false );
		} else {
			charger( adresse( form ), true );
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		var bouton = e.target.closest( '.er-frise__bascule button' );
		if ( bouton ) {
			basculer( bouton.dataset.mode );
			return;
		}
		if ( e.target.closest( '.er-facettes__ouvrir' ) ) {
			ouvrir( true );
			return;
		}
		if ( e.target.closest( '.er-facettes__fermer' ) ) {
			ouvrir( false );
			return;
		}
		var lien = e.target.closest( '.er-puce, .er-facettes__effacer' );
		if ( lien && ! e.metaKey && ! e.ctrlKey && ! e.shiftKey ) {
			e.preventDefault();
			charger( lien.href, true );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && colonne().classList.contains( 'is-ouvert' ) ) {
			ouvrir( false );
		}
	} );

	// Revenir en arrière rend le filtre d'avant (les ancres d'une page, elles, ne changent pas l'adresse filtrée).
	window.addEventListener( 'popstate', function () {
		if ( window.location.search !== recherche ) {
			window.location.reload();
		}
	} );

	preparer( colonne(), null );
}() );
