/**
 * L'en-tête plein écran (inc/entete.php) :
 *
 * - un clic sur une maison de la barre noire (Librairie, Maison d'édition YMCA-Press, Le centre culturel) ne mène pas
 *   à sa page d'accueil : il montre sa ligne, sous la barre, sans quitter la page. Dans le menu (≡) du téléphone, les
 *   liens restent des liens ;
 * - l'en-tête accroché : la barre d'informations et la barre noire partent avec la page, la ligne de la maison reste en
 *   haut de l'écran (style.css). Ce script mesure ce qui part (--er-haut-cache) et ce qui reste (--er-ligne), et marque
 *   l'en-tête « est-accroche » quand la barre noire est sortie de l'écran : la loupe et le panier se montrent alors au
 *   bout de la ligne ;
 * - la barre d'informations : « Ouvert en ce moment » ou « Fermé en ce moment » d'après les horaires, et la prochaine
 *   rencontre de la semaine (« Ce soir au centre », « Demain au centre », « Jeudi au centre »), à l'heure de Paris.
 *
 * Sans ce script : les maisons mènent à leur page, l'en-tête s'accroche à la hauteur prévue, la barre d'informations
 * dit les jours d'ouverture.
 */
( function () {
	'use strict';

	var T = window.erEnteteTextes || {};
	var racine = document.documentElement;
	var barre = document.querySelector( '.er-barre' );
	var haut = barre ? barre.closest( 'header' ) : null;
	var infos = document.querySelector( '.er-infos' );
	var lignes = document.querySelector( '.er-lignes' );

	/* Les mesures : ce qui part en défilant, ce qui reste. ------------------------------------------------- */

	function mesurer() {
		if ( ! barre ) {
			return;
		}
		var cache = barre.offsetHeight + ( infos ? infos.offsetHeight : 0 );
		racine.style.setProperty( '--er-haut-cache', cache + 'px' );
		var visible = lignes ? lignes.querySelector( '.er-ligne:not([hidden])' ) : null;
		racine.style.setProperty( '--er-ligne', ( visible ? visible.offsetHeight : 0 ) + 'px' );
	}
	mesurer();
	window.addEventListener( 'resize', mesurer );
	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( mesurer );
	}

	/* L'en-tête accroché : la barre noire est sortie de l'écran. --------------------------------------------- */

	if ( haut && barre && 'IntersectionObserver' in window ) {
		// La barre noire accrochée a son bas tout contre le haut de l'écran (ou sous la barre d'administration) : on
		// rogne le haut de l'écran d'un pixel de plus, pour qu'elle compte comme sortie.
		var admin = parseInt( window.getComputedStyle( racine ).getPropertyValue( '--wp-admin--admin-bar--height' ), 10 ) || 0;
		if ( window.innerWidth <= 600 ) {
			admin = 0;
		}
		new IntersectionObserver( function ( entrees ) {
			var e = entrees[ 0 ];
			haut.classList.toggle( 'est-accroche', ! e.isIntersecting && e.boundingClientRect.top < admin );
		}, { rootMargin: '-' + ( admin + 1 ) + 'px 0px 0px 0px' } ).observe( barre );
	}

	/* Un clic sur une maison : sa ligne. --------------------------------------------------------------------- */

	function montrer( maison ) {
		lignes.querySelectorAll( '.er-ligne' ).forEach( function ( ligne ) {
			ligne.hidden = ligne.getAttribute( 'data-maison' ) !== maison;
		} );
		document.querySelectorAll( '.er-menu .er-onglet' ).forEach( function ( li ) {
			var ouverte = li.classList.contains( 'er-onglet-' + maison );
			li.classList.toggle( 'est-ouverte', ouverte );
			var lien = li.querySelector( '.wp-block-navigation-item__content' );
			if ( lien ) {
				lien.setAttribute( 'aria-expanded', ouverte ? 'true' : 'false' );
			}
		} );
		mesurer();
	}

	if ( lignes ) {
		var visible = lignes.querySelector( '.er-ligne:not([hidden])' );
		document.querySelectorAll( '.er-menu .er-onglet' ).forEach( function ( li ) {
			var m = ( li.className.match( /er-onglet-(librairie|ymca|centre)/ ) || [] )[ 1 ];
			var lien = li.querySelector( '.wp-block-navigation-item__content' );
			if ( ! m || ! lien || ! lignes.querySelector( '.er-ligne[data-maison="' + m + '"]' ) ) {
				return;
			}
			lien.setAttribute( 'aria-expanded', visible && visible.getAttribute( 'data-maison' ) === m ? 'true' : 'false' );
			if ( visible && visible.getAttribute( 'data-maison' ) === m ) {
				li.classList.add( 'est-ouverte' );
			}
			lien.addEventListener( 'click', function ( ev ) {
				if ( li.closest( '.is-menu-open' ) || ev.metaKey || ev.ctrlKey || ev.shiftKey ) {
					return; // Le menu (≡) du téléphone, ou un nouvel onglet : le lien reste un lien.
				}
				ev.preventDefault();
				montrer( m );
			} );
		} );
	}

	/* La barre d'informations. ------------------------------------------------------------------------------- */

	// L'heure de Paris, quel que soit le fuseau de l'ordinateur : [ jour (0 = dimanche), minutes depuis minuit, date ].
	function paris( date ) {
		var morceaux = {};
		new Intl.DateTimeFormat( 'en-GB', {
			timeZone: 'Europe/Paris',
			weekday: 'short',
			year: 'numeric',
			month: '2-digit',
			day: '2-digit',
			hour: '2-digit',
			minute: '2-digit',
			hourCycle: 'h23'
		} ).formatToParts( date ).forEach( function ( p ) {
			morceaux[ p.type ] = p.value;
		} );
		var jours = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };
		return {
			jour: jours[ morceaux.weekday ],
			minutes: parseInt( morceaux.hour, 10 ) * 60 + parseInt( morceaux.minute, 10 ),
			date: morceaux.year + '-' + morceaux.month + '-' + morceaux.day,
			heure: parseInt( morceaux.hour, 10 )
		};
	}

	// « 19 h », « 18 h 30 » en français ; « 19:00 » ailleurs.
	function heure( hhmm ) {
		var h = parseInt( hhmm.slice( 0, 2 ), 10 );
		var m = hhmm.slice( 3, 5 );
		return T.heureFr ? h + ' h' + ( '00' === m ? '' : ' ' + m ) : hhmm;
	}

	function minutes( hhmm ) {
		return parseInt( hhmm.slice( 0, 2 ), 10 ) * 60 + parseInt( hhmm.slice( 3, 5 ), 10 );
	}

	function format( modele ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var i = 0;
		return modele.replace( /%(\d)\$s|%s/g, function ( tout, n ) {
			return n ? args[ parseInt( n, 10 ) - 1 ] : args[ i++ ];
		} );
	}

	function majuscule( texte ) {
		return texte.charAt( 0 ).toUpperCase() + texte.slice( 1 );
	}

	var ouvert = document.querySelector( '.er-info-ouvert' );
	if ( ouvert && T.ouvert ) {
		var horaires = {};
		try {
			horaires = JSON.parse( ouvert.getAttribute( 'data-horaires' ) || '{}' );
		} catch ( e ) {
			horaires = {};
		}
		var maintenant = paris( new Date() );
		// Les horaires sont rangés de 1 (lundi) à 7 (dimanche) ; JavaScript compte de 0 (dimanche) à 6.
		var duJour = function ( j ) {
			return horaires[ 0 === j ? 7 : j ];
		};
		if ( Object.keys( horaires ).length ) {
			var aujourdhui = duJour( maintenant.jour );
			var texte = ouvert.querySelector( '.er-info-texte' );
			if ( aujourdhui && maintenant.minutes >= minutes( aujourdhui[ 0 ] ) && maintenant.minutes < minutes( aujourdhui[ 1 ] ) ) {
				ouvert.classList.add( 'est-ouvert' );
				texte.innerHTML = '';
				var fort = document.createElement( 'strong' );
				fort.textContent = T.ouvert;
				texte.appendChild( fort );
				texte.appendChild( document.createTextNode( ' · ' + format( T.jusqua, heure( aujourdhui[ 1 ] ) ) ) );
			} else {
				// La prochaine ouverture : plus tard aujourd'hui, demain, ou tel jour.
				for ( var d = 0; d < 8; d++ ) {
					var j = ( maintenant.jour + d ) % 7;
					var h = duJour( j );
					if ( ! h || ( 0 === d && maintenant.minutes >= minutes( h[ 0 ] ) ) ) {
						continue;
					}
					var quand = 0 === d ? '' : 1 === d ? T.demain : T.jours[ j ];
					ouvert.classList.add( 'est-ferme' );
					texte.innerHTML = '';
					var ferme = document.createElement( 'strong' );
					ferme.textContent = T.ferme;
					texte.appendChild( ferme );
					texte.appendChild( document.createTextNode( ' · ' + format( T.reouvre, quand, heure( h[ 0 ] ) ).replace( /\s+/g, ' ' ) ) );
					break;
				}
			}
		}
	}

	// La prochaine rencontre : la première qui n'est pas passée (deux heures après son début).
	var rencontres = document.querySelectorAll( '.er-info-rencontre' );
	if ( rencontres.length && T.cesoir ) {
		var ici = paris( new Date() );
		var demain = paris( new Date( Date.now() + 864e5 ) );
		for ( var r = 0; r < rencontres.length; r++ ) {
			var debut = new Date( rencontres[ r ].getAttribute( 'data-debut' ) );
			if ( isNaN( debut.getTime() ) || debut.getTime() + 2 * 36e5 < Date.now() ) {
				continue;
			}
			var p = paris( debut );
			var libelle = p.date === ici.date ? ( p.heure >= 17 ? T.cesoir : T.aujcentre ) : p.date === demain.date ? T.demaincentre : format( T.jourcentre, majuscule( T.jours[ p.jour ] ) );
			rencontres[ r ].querySelector( '.er-info-quand' ).textContent = libelle;
			if ( p.minutes ) {
				var hh = String( Math.floor( p.minutes / 60 ) ).padStart( 2, '0' ) + ':' + String( p.minutes % 60 ).padStart( 2, '0' );
				rencontres[ r ].querySelector( '.er-info-heure' ).textContent = ', ' + heure( hh );
			}
			rencontres[ r ].hidden = false;
			break;
		}
	}
	mesurer();
} )();
