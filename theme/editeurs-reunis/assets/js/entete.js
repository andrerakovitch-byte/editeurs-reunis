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

	/* Au téléphone, le relais (24 nov., la façon 4 du canevas « Les informations au téléphone ») : la barre montre une
	   information à la fois (l'ouverture, l'exposition, la rencontre, l'adresse) ; la suivante arrive par la droite et
	   pousse l'autre, comme les sujets de la une. Un trait gris avance au pied de la barre et donne la mesure : quand il
	   arrive au bout (quatre secondes, style.css), la suivante vient. Il attend pendant qu'on parcourt la barre au
	   clavier ; si le système demande moins d'animations, la barre garde l'ouverture seule, comme avant. */
	var telephone = window.matchMedia( '(max-width: 781px)' );
	var calme = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var relaisCourant = null;
	var relaisDedans = infos && infos.querySelector( '.er-infos-dedans' );
	function relaisListe() {
		return Array.prototype.slice.call( infos.querySelectorAll( '.er-info' ) ).filter( function ( i ) {
			return ! i.hidden;
		} );
	}
	function relaisRepartir() {
		infos.classList.remove( 'est-en-route' );
		void infos.offsetWidth; // le trait repart de zéro
		infos.classList.add( 'est-en-route' );
	}
	function relaisSuivant() {
		var liste = relaisListe();
		if ( liste.length < 2 ) {
			return;
		}
		var vient = liste[ ( liste.indexOf( relaisCourant ) + 1 ) % liste.length ];
		vient.classList.add( 'sans-glisse' );
		vient.classList.remove( 'est-avant', 'est-ici' ); // à droite, prête à entrer
		void vient.offsetWidth;
		vient.classList.remove( 'sans-glisse' );
		vient.classList.add( 'est-ici' );
		if ( relaisCourant ) {
			relaisCourant.classList.remove( 'est-ici' );
			relaisCourant.classList.add( 'est-avant' );
		}
		relaisCourant = vient;
	}
	function relaisRegler() {
		var oui = telephone.matches && ! calme.matches && relaisListe().length > 1;
		var marche = infos.classList.contains( 'est-relais' );
		if ( oui && ! marche ) {
			var liste = relaisListe();
			liste.forEach( function ( i ) {
				i.classList.add( 'sans-glisse' );
				i.classList.remove( 'est-avant' );
				i.classList.toggle( 'est-ici', i === liste[ 0 ] );
			} );
			infos.classList.add( 'est-relais' );
			void infos.offsetWidth;
			liste.forEach( function ( i ) {
				i.classList.remove( 'sans-glisse' );
			} );
			relaisCourant = liste[ 0 ];
			relaisRepartir();
		} else if ( ! oui && marche ) {
			infos.classList.remove( 'est-relais', 'est-en-route' );
			infos.querySelectorAll( '.er-info' ).forEach( function ( i ) {
				i.classList.remove( 'est-ici', 'est-avant', 'sans-glisse' );
			} );
		}
	}
	if ( relaisDedans ) {
		relaisDedans.addEventListener( 'animationend', function ( e ) {
			if ( 'er-infos-trait' === e.animationName && infos.classList.contains( 'est-relais' ) ) {
				relaisSuivant();
				relaisRepartir();
			}
		} );
		relaisRegler();
		[ telephone, calme ].forEach( function ( m ) {
			if ( m.addEventListener ) {
				m.addEventListener( 'change', relaisRegler );
			}
		} );
	}

	/* Les interrupteurs : la loupe, le panier et le menu (≡) referment ce qu'ils ont ouvert, à la même place (24 nov.).
	   Leur panneau s'ouvre sous la barre ou la ligne où ils sont ; eux restent visibles et deviennent une croix
	   (style.css). La loupe est un interrupteur d'elle-même (recherche.js) ; le menu de WordPress et le volet du panier
	   de WooCommerce n'ouvrent qu'à leur clic : on retient, au moment où le doigt se pose, s'ils étaient ouverts, et
	   le clic referme alors au lieu de rouvrir (un panneau peut s'être refermé de lui-même entre-temps, quand la main
	   l'a quitté). */
	var racine = document.documentElement;
	var dessousDe = function ( el ) {
		var bande = el && el.closest( '.er-barre, .er-ligne' );
		return bande ? Math.max( 0, Math.round( bande.getBoundingClientRect().bottom ) ) : 0;
	};

	// Le menu (≡) jusqu'à 1 280 px.
	var menu = document.querySelector( '.er-barre .er-menu' );
	var menuOuvre = menu && menu.querySelector( '.wp-block-navigation__responsive-container-open' );
	var menuCalque = menu && menu.querySelector( '.wp-block-navigation__responsive-container' );
	var menuEtaitOuvert = false;
	if ( menuOuvre && menuCalque ) {
		window.addEventListener( 'pointerdown', function ( e ) {
			if ( menuOuvre.contains( e.target ) ) {
				menuEtaitOuvert = menuCalque.classList.contains( 'is-menu-open' );
			}
		}, true );
		window.addEventListener( 'click', function ( e ) {
			if ( ! menuOuvre.contains( e.target ) ) {
				return;
			}
			var ouvert = menuCalque.classList.contains( 'is-menu-open' );
			if ( ouvert || menuEtaitOuvert ) {
				e.preventDefault();
				e.stopPropagation();
				menuEtaitOuvert = false;
				var croix = menuCalque.querySelector( '.wp-block-navigation__responsive-container-close' );
				if ( ouvert && croix ) {
					croix.click();
				}
				return;
			}
			racine.style.setProperty( '--er-menu-haut', dessousDe( menuOuvre ) + 'px' );
		}, true );
	}

	// Le panier : celui de la barre noire (le bloc de WooCommerce) et celui de la ligne accrochée, qui ouvre le même
	// volet, sans quitter la page ; sa pastille recopie celle de la barre. Sans le volet, c'est un lien vers le panier.
	var lignesPanier = Array.prototype.slice.call( document.querySelectorAll( '.er-ligne-panier' ) ); // un par maison
	var miniPanier = function () {
		return document.querySelector( '.er-barre .wc-block-mini-cart__button' );
	};
	var panierOuvert = function () {
		return !! document.querySelector( '.wc-block-components-drawer__screen-overlay:not(.wc-block-components-drawer__screen-overlay--is-hidden)' ) || document.body.classList.contains( 'drawer-open' );
	};
	var fermerPanier = function () {
		var croix = document.querySelector( '.wc-block-components-drawer__close' );
		if ( croix ) {
			croix.click();
		}
	};
	var estPanier = function ( el ) {
		return el && el.closest ? el.closest( '.er-ligne-panier, .er-barre .wc-block-mini-cart__button' ) : null;
	};
	var panierEtaitOuvert = false;
	var relais = false; // le clic que la ligne passe au bouton de la barre
	window.addEventListener( 'pointerdown', function ( e ) {
		if ( estPanier( e.target ) ) {
			panierEtaitOuvert = panierOuvert();
			if ( panierEtaitOuvert ) {
				e.preventDefault(); // la main reste dans le volet : il ne se referme pas avant le clic
			}
		}
	}, true );
	window.addEventListener( 'click', function ( e ) {
		var ouvreur = estPanier( e.target );
		if ( ! ouvreur || relais ) {
			return;
		}
		if ( panierOuvert() || panierEtaitOuvert ) {
			e.preventDefault();
			e.stopPropagation();
			panierEtaitOuvert = false;
			if ( panierOuvert() ) {
				fermerPanier();
			}
			return;
		}
		racine.style.setProperty( '--er-dessous', dessousDe( ouvreur ) + 'px' );
		var bouton = miniPanier();
		if ( ouvreur.classList.contains( 'er-ligne-panier' ) && bouton && ! ( e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ) ) {
			e.preventDefault();
			e.stopPropagation();
			relais = true;
			bouton.click();
			relais = false;
		}
	}, true );
	// L'état du volet, sur <html> : les deux paniers passent à la croix, le volet se cale sous la barre.
	var suivrePanier = function () {
		racine.classList.toggle( 'er-panier-ouvert', panierOuvert() );
	};
	if ( window.MutationObserver ) {
		new MutationObserver( suivrePanier ).observe( document.body, { subtree: true, childList: true, attributes: true, attributeFilter: [ 'class' ] } );
	}
	if ( lignesPanier.length ) {
		lignesPanier.forEach( function ( l ) {
			if ( miniPanier() ) {
				l.setAttribute( 'aria-haspopup', 'dialog' );
			}
		} );
		var recopier = function () {
			var b = document.querySelector( '.er-barre .wc-block-mini-cart__badge' );
			var n = b && ! b.hidden && 'none' !== window.getComputedStyle( b ).display ? b.textContent.trim() : '';
			lignesPanier.forEach( function ( l ) {
				var pastille = l.querySelector( '.er-ligne-badge' );
				if ( pastille ) {
					pastille.textContent = n;
					pastille.hidden = ! n || '0' === n;
				}
			} );
		};
		recopier();
		var cadrePanier = document.querySelector( '.er-barre .er-panier' );
		if ( cadrePanier && window.MutationObserver ) {
			new MutationObserver( recopier ).observe( cadrePanier, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: [ 'hidden', 'class', 'style' ] } );
		}
	}

	mesurer();
} )();
