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
 * - la barre d'informations : « Ouvert en ce moment » ou « Fermé en ce moment » d'après les horaires (quand ils sont
 *   posés), et le prochain événement : la première rencontre qui n'est pas passée.
 *
 * Sans ce script : les maisons mènent à leur page, l'en-tête s'accroche à la hauteur prévue, la barre d'informations
 * dit les jours et les heures d'ouverture.
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

	// Le prochain événement : la première rencontre qui n'est pas passée (deux heures après son début). Son jour et son
	// heure sont écrits par la page ; le navigateur choisit seulement laquelle montrer, la page pouvant dater un peu.
	var rencontres = document.querySelectorAll( '.er-info-rencontre' );
	for ( var r = 0; r < rencontres.length; r++ ) {
		var debut = new Date( rencontres[ r ].getAttribute( 'data-debut' ) );
		if ( isNaN( debut.getTime() ) || debut.getTime() + 2 * 36e5 < Date.now() ) {
			continue;
		}
		rencontres[ r ].hidden = false;
		break;
	}

	/* Le relais (24 nov. ; de bas en haut, à toutes les largeurs, le 25 nov. ; « Jusqu'au bout », la version 6 du
	   canevas « La barre d'informations, de bas en haut », le même soir) : la barre montre une information à la fois
	   (l'ouverture, l'exposition, la rencontre, l'adresse) ; la suivante monte par en dessous et pousse l'autre vers
	   le haut. Une ligne plus longue que la place (au téléphone, la rencontre et l'adresse) ne se coupe pas : après
	   un temps, elle glisse vers la gauche jusqu'à sa fin, s'y arrête, puis laisse la place. Chaque information reste
	   au moins quatre secondes et demie. Tout attend pendant que la souris est sur la barre ou qu'on la parcourt au
	   clavier ; si le système demande moins d'animations, la barre reste comme avant : tout en ligne sur ordinateur,
	   l'ouverture seule au téléphone. */
	var calme = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var relaisCourant = null;
	var relaisDedans = infos && infos.querySelector( '.er-infos-dedans' );
	var RELAIS_ATTENTE = 1800; // avant de glisser, puis une fois au bout (ms)
	var RELAIS_VITESSE = 45; // px par seconde
	var RELAIS_MINIMUM = 4500; // le temps d'une information qui tient en entier (ms)
	var relaisPlus = null; // ce qui vient ensuite : glisser, ou passer à la suivante
	var relaisFin = 0;
	var relaisReste = 0;
	var relaisMinuteur = null;
	var relaisArret = false;
	function relaisListe() {
		return Array.prototype.slice.call( infos.querySelectorAll( '.er-info' ) ).filter( function ( i ) {
			return ! i.hidden;
		} );
	}
	// Chaque information reçoit une ligne intérieure, celle qui glisse (display: contents tant que le relais dort).
	function relaisLigne( info ) {
		var ligne = info.querySelector( ':scope > .er-info-ligne' );
		if ( ! ligne ) {
			ligne = document.createElement( 'span' );
			ligne.className = 'er-info-ligne';
			while ( info.firstChild ) {
				ligne.appendChild( info.firstChild );
			}
			info.appendChild( ligne );
		}
		return ligne;
	}
	function relaisRemettre( info ) {
		var ligne = relaisLigne( info );
		ligne.style.transition = 'none';
		ligne.style.transform = '';
		info.classList.remove( 'est-trop' );
	}
	function relaisPlanifier( fn, ms ) {
		clearTimeout( relaisMinuteur );
		relaisPlus = fn;
		relaisReste = ms;
		if ( ! relaisArret ) {
			relaisFin = Date.now() + ms;
			relaisMinuteur = setTimeout( relaisSonner, ms );
		}
	}
	function relaisSonner() {
		var fn = relaisPlus;
		relaisPlus = null;
		if ( fn ) {
			fn();
		}
	}
	function relaisArreter() {
		if ( relaisArret ) {
			return;
		}
		relaisArret = true;
		clearTimeout( relaisMinuteur );
		if ( relaisPlus ) {
			relaisReste = Math.max( 0, relaisFin - Date.now() );
		}
	}
	function relaisReprendre() {
		if ( ! relaisArret ) {
			return;
		}
		relaisArret = false;
		if ( relaisPlus ) {
			relaisFin = Date.now() + relaisReste;
			relaisMinuteur = setTimeout( relaisSonner, relaisReste );
		}
	}
	// L'information du moment : tient-elle ? Sinon, elle glissera de ce qui dépasse (et un peu plus, pour sortir du
	// fondu de droite).
	function relaisMontrer( info ) {
		var ligne = relaisLigne( info );
		var style = getComputedStyle( info );
		var gauche = parseFloat( style.paddingLeft ) || 0;
		var droite = parseFloat( style.paddingRight ) || 0;
		var trop = Math.ceil( ligne.offsetWidth - ( info.clientWidth - gauche - droite ) );
		if ( trop <= 0 ) {
			relaisPlanifier( relaisSuivant, RELAIS_MINIMUM );
			return;
		}
		var decale = trop + Math.max( 0, 40 - droite );
		var glisse = Math.round( decale / RELAIS_VITESSE * 1000 );
		info.classList.add( 'est-trop' );
		relaisPlanifier( function () {
			ligne.style.transition = 'transform ' + glisse + 'ms linear';
			ligne.style.transform = 'translateX(' + -decale + 'px)';
			relaisPlanifier( relaisSuivant, Math.max( glisse + RELAIS_ATTENTE, RELAIS_MINIMUM - RELAIS_ATTENTE ) );
		}, RELAIS_ATTENTE );
	}
	function relaisSuivant() {
		var liste = relaisListe();
		if ( liste.length < 2 ) {
			return;
		}
		var vient = liste[ ( liste.indexOf( relaisCourant ) + 1 ) % liste.length ];
		vient.classList.add( 'sans-glisse' );
		vient.classList.remove( 'est-avant', 'est-ici' ); // en dessous, prête à monter
		relaisRemettre( vient ); // l'autre bout de la ligne, si elle avait glissé au tour d'avant
		void vient.offsetWidth;
		vient.classList.remove( 'sans-glisse' );
		vient.classList.add( 'est-ici' );
		if ( relaisCourant ) {
			relaisCourant.classList.remove( 'est-ici' );
			relaisCourant.classList.add( 'est-avant' ); // elle part telle quelle, glissée ou non
		}
		relaisCourant = vient;
		relaisMontrer( vient );
	}
	function relaisRegler() {
		var oui = ! calme.matches && relaisListe().length > 1;
		var marche = infos.classList.contains( 'est-relais' );
		if ( oui && ! marche ) {
			var liste = relaisListe();
			liste.forEach( function ( i ) {
				i.classList.add( 'sans-glisse' );
				i.classList.remove( 'est-avant' );
				i.classList.toggle( 'est-ici', i === liste[ 0 ] );
				relaisRemettre( i );
			} );
			infos.classList.add( 'est-relais' );
			void infos.offsetWidth;
			liste.forEach( function ( i ) {
				i.classList.remove( 'sans-glisse' );
			} );
			relaisCourant = liste[ 0 ];
			relaisMontrer( relaisCourant );
		} else if ( ! oui && marche ) {
			clearTimeout( relaisMinuteur );
			relaisPlus = null;
			infos.classList.remove( 'est-relais' );
			infos.querySelectorAll( '.er-info' ).forEach( function ( i ) {
				i.classList.remove( 'est-ici', 'est-avant', 'sans-glisse' );
				relaisRemettre( i );
			} );
		}
	}
	if ( relaisDedans ) {
		infos.addEventListener( 'mouseenter', relaisArreter );
		infos.addEventListener( 'mouseleave', function () {
			if ( ! infos.contains( document.activeElement ) ) {
				relaisReprendre();
			}
		} );
		infos.addEventListener( 'focusin', relaisArreter );
		infos.addEventListener( 'focusout', function ( e ) {
			if ( ! infos.contains( e.relatedTarget ) && ! infos.matches( ':hover' ) ) {
				relaisReprendre();
			}
		} );
		relaisRegler();
		if ( calme.addEventListener ) {
			calme.addEventListener( 'change', relaisRegler );
		}
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
	var dessousPose = false; // la place du volet, prise au clic sur l'un des paniers
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
		dessousPose = true;
		var bouton = miniPanier();
		if ( ouvreur.classList.contains( 'er-ligne-panier' ) && bouton && ! ( e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ) ) {
			e.preventDefault();
			e.stopPropagation();
			relais = true;
			bouton.click();
			relais = false;
		}
	}, true );
	// L'état du volet, sur <html> : les deux paniers passent à la croix, le volet se cale sous la barre. Ouvert sans
	// clic sur un panier (WooCommerce peut l'ouvrir après un ajout), il se cale sous la bande visible à ce moment-là :
	// la ligne accrochée si la barre est sortie de l'écran, sinon la barre.
	var voletEtaitOuvert = false;
	var panierVisible = function () {
		var ligne = lignesPanier.filter( function ( l ) {
			return l.getClientRects().length && 'visible' === window.getComputedStyle( l ).visibility && l.getBoundingClientRect().bottom > 0;
		} )[ 0 ];
		return ligne || miniPanier();
	};
	var suivrePanier = function () {
		var ouvert = panierOuvert();
		if ( ouvert && ! voletEtaitOuvert && ! dessousPose ) {
			racine.style.setProperty( '--er-dessous', dessousDe( panierVisible() ) + 'px' );
		}
		if ( ! ouvert ) {
			dessousPose = false;
		}
		voletEtaitOuvert = ouvert;
		racine.classList.toggle( 'er-panier-ouvert', ouvert );
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
			// Le panier en tête du menu du téléphone : « 2 articles ».
			document.querySelectorAll( '.er-menu-panier-nombre' ).forEach( function ( el ) {
				var k = parseInt( n, 10 ) || 0;
				var modele = el.getAttribute( k > 1 ? 'data-plusieurs' : 'data-un' );
				if ( modele && b ) {
					el.textContent = modele.replace( '%d', k );
				}
			} );
		};
		recopier();
		var cadrePanier = document.querySelector( '.er-barre .er-panier' );
		if ( cadrePanier && window.MutationObserver ) {
			new MutationObserver( recopier ).observe( cadrePanier, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: [ 'hidden', 'class', 'style' ] } );
		}
	}

	/* Le choix de la langue, au bout de la barre d'informations (B3, 25 nov. 2026) : le bouton ouvre et ferme la liste,
	   à la même place ; un clic ailleurs ou Échap la referme. */
	document.querySelectorAll( '.er-choix-langue' ).forEach( function ( bloc ) {
		var bouton = bloc.querySelector( '.er-choix-langue-bouton' );
		var liste = bloc.querySelector( '.er-choix-langue-liste' );
		if ( ! bouton || ! liste ) {
			return;
		}
		var ouvrir = function ( oui ) {
			liste.hidden = ! oui;
			bouton.setAttribute( 'aria-expanded', oui ? 'true' : 'false' );
		};
		bouton.addEventListener( 'click', function () {
			ouvrir( liste.hidden );
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! liste.hidden && ! bloc.contains( e.target ) ) {
				ouvrir( false );
			}
		} );
		bloc.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && ! liste.hidden ) {
				ouvrir( false );
				bouton.focus();
			}
		} );
	} );

	mesurer();
} )();
