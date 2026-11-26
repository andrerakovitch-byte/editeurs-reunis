/**
 * Les menus de l'en-tête (inc/menus.php ; retenus le 26 novembre 2026) :
 *
 * - un nom de la barre ouvre son menu au survol, après un court temps (on ne l'ouvre pas en passant) ; on passe d'un
 *   nom à l'autre sans attendre ; le menu se referme quand la souris quitte l'en-tête et ses menus, sur un clic
 *   ailleurs, ou sur Échap. Au doigt et au clavier, le nom s'ouvre et se referme au clic ;
 * - la Librairie : les sous-rayons du rayon survolé, et le livre du rayon ou du sous-rayon survolé ;
 * - les grands mots : le mot survolé reste à l'encre, les autres pâlissent, la photo change ;
 * - le sac : le panier, lu à l'ouverture dans l'API du magasin, et le compte ; sa pastille suit les ajouts ;
 * - le plan du site (≡, jusqu'à 1 320 px) : Fermer et Échap rendent la main au bouton ; Tab ne le quitte pas.
 *
 * Sans ce script, les menus restent fermés : les pages des maisons sont aussi au pied de page.
 */
( function () {
	'use strict';
	var racine = document.documentElement;
	var boutons = Array.prototype.slice.call( document.querySelectorAll( '[data-er-em]' ) );
	if ( ! boutons.length ) {
		return;
	}
	var haut = boutons[ 0 ].closest( 'header' ) || document.body;
	var plan = document.getElementById( 'er-em-plan' );
	var voile = document.querySelector( '.er-em-voile' );
	var survol = window.matchMedia( '(hover: hover) and (pointer: fine)' );
	var courant = null;
	var minuteur = 0;

	function cible( bouton ) {
		return document.getElementById( bouton.getAttribute( 'aria-controls' ) );
	}
	function attendre( fn, ms ) {
		window.clearTimeout( minuteur );
		minuteur = window.setTimeout( fn, ms );
	}
	function fermer( rendre ) {
		window.clearTimeout( minuteur );
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
		racine.removeAttribute( 'data-er-em' );
		if ( rendre ) {
			bouton.focus();
		}
	}
	function ouvrir( bouton, clavier ) {
		if ( courant === bouton ) {
			return;
		}
		fermer( false );
		var menu = cible( bouton );
		if ( ! menu ) {
			return;
		}
		courant = bouton;
		bouton.setAttribute( 'aria-expanded', 'true' );
		menu.hidden = false;
		racine.setAttribute( 'data-er-em', bouton.getAttribute( 'data-er-em' ) );
		menu.querySelectorAll( 'img[loading="lazy"]' ).forEach( function ( image ) {
			image.loading = 'eager';
		} );
		if ( 'panier' === bouton.getAttribute( 'data-er-em' ) ) {
			lirePanier();
		}
		if ( menu === plan ) {
			racine.classList.add( 'er-em-plan-ouvert' );
			var croix = plan.querySelector( '.er-em-fermer-plan' );
			if ( croix ) {
				croix.focus();
			}
			return;
		}
		racine.classList.add( 'er-em-ouvert' );
		if ( clavier ) {
			var premier = menu.querySelector( 'a' );
			if ( premier ) {
				premier.focus();
			}
		}
	}

	boutons.forEach( function ( bouton ) {
		var duPlan = 'plan' === bouton.getAttribute( 'data-er-em' );
		bouton.addEventListener( 'click', function ( e ) {
			if ( courant === bouton ) {
				// Ouvert au survol : le clic ne le referme pas (la souris venait de l'ouvrir).
				if ( ! survol.matches || duPlan || 0 === e.detail ) {
					fermer( false );
				}
				return;
			}
			ouvrir( bouton, 0 === e.detail );
		} );
		if ( duPlan ) {
			return;
		}
		bouton.addEventListener( 'mouseenter', function () {
			if ( ! survol.matches ) {
				return;
			}
			// D'un menu ouvert à l'autre, sans attendre ; sinon, un temps, pour ne pas ouvrir en passant.
			attendre( function () {
				ouvrir( bouton, false );
			}, courant ? 0 : 150 );
		} );
		bouton.addEventListener( 'mouseleave', function () {
			if ( ! courant ) {
				window.clearTimeout( minuteur );
			}
		} );
	} );

	// La souris quitte l'en-tête et ses menus : le menu se referme, après un instant (le temps d'y revenir).
	haut.addEventListener( 'mouseleave', function () {
		if ( survol.matches && courant && cible( courant ) !== plan ) {
			attendre( function () {
				fermer( false );
			}, 250 );
		}
	} );
	haut.addEventListener( 'mouseenter', function () {
		if ( courant ) {
			window.clearTimeout( minuteur );
		}
	} );
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
	// La loupe ouvre la recherche : un menu ouvert se referme.
	document.querySelectorAll( '.er-loupe' ).forEach( function ( loupe ) {
		loupe.addEventListener( 'click', function () {
			fermer( false );
		} );
	} );
	if ( plan ) {
		var croix = plan.querySelector( '.er-em-fermer-plan' );
		if ( croix ) {
			croix.addEventListener( 'click', function () {
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

	// La Librairie : le rayon survolé montre ses sous-rayons et son livre ; un sous-rayon survolé, le sien.
	var librairie = document.getElementById( 'er-em-librairie' );
	if ( librairie ) {
		var livres = Array.prototype.slice.call( librairie.querySelectorAll( '.er-mn-livre' ) );
		var rayonActif = '';
		var montrerLivre = function ( cle ) {
			var trouve = livres.some( function ( l ) {
				return l.getAttribute( 'data-livre' ) === cle;
			} );
			if ( ! trouve ) {
				return;
			}
			livres.forEach( function ( l ) {
				l.hidden = l.getAttribute( 'data-livre' ) !== cle;
			} );
		};
		librairie.querySelectorAll( '.er-mn-rayons a[data-rayon]' ).forEach( function ( lien ) {
			var choisir = function () {
				var rayon = lien.getAttribute( 'data-rayon' );
				rayonActif = rayon;
				librairie.querySelectorAll( '.er-mn-rayons a' ).forEach( function ( autre ) {
					autre.classList.toggle( 'est-actif', autre === lien );
				} );
				librairie.querySelectorAll( '.er-mn-sous-rayon' ).forEach( function ( bloc ) {
					bloc.hidden = bloc.getAttribute( 'data-rayon' ) !== rayon;
				} );
				montrerLivre( rayon );
			};
			lien.addEventListener( 'mouseenter', choisir );
			lien.addEventListener( 'focus', choisir );
		} );
		librairie.querySelectorAll( '.er-mn-sous a[data-livre]' ).forEach( function ( lien ) {
			var liste = lien.closest( '.er-mn-sous-rayon' );
			var entrer = function () {
				montrerLivre( lien.getAttribute( 'data-livre' ) );
				liste.classList.add( 'est-survol' );
				liste.querySelectorAll( 'a[data-livre]' ).forEach( function ( a ) {
					a.classList.toggle( 'est-survole', a === lien );
				} );
			};
			lien.addEventListener( 'mouseenter', entrer );
			lien.addEventListener( 'focus', entrer );
		} );
		librairie.querySelectorAll( '.er-mn-sous-rayon' ).forEach( function ( liste ) {
			liste.addEventListener( 'mouseleave', function () {
				liste.classList.remove( 'est-survol' );
				liste.querySelectorAll( '.est-survole' ).forEach( function ( a ) {
					a.classList.remove( 'est-survole' );
				} );
				if ( rayonActif ) {
					montrerLivre( rayonActif );
				}
			} );
		} );
	}

	// Les grands mots.
	document.querySelectorAll( '.er-em-mots' ).forEach( function ( liste ) {
		var panneau = liste.closest( '.er-em-panneau' );
		var photos = panneau ? Array.prototype.slice.call( panneau.querySelectorAll( '.er-em-photos img' ) ) : [];
		var defaut = panneau ? panneau.getAttribute( 'data-photo' ) : '';
		var liens = Array.prototype.slice.call( liste.querySelectorAll( 'a' ) );
		var montrer = function ( cle ) {
			photos.forEach( function ( photo ) {
				photo.classList.toggle( 'est-vue', photo.getAttribute( 'data-photo' ) === cle );
			} );
		};
		var sortir = function () {
			liste.classList.remove( 'est-survol' );
			liens.forEach( function ( l ) {
				l.classList.remove( 'est-survole' );
			} );
			montrer( defaut );
		};
		liens.forEach( function ( lien ) {
			var entrer = function () {
				liste.classList.add( 'est-survol' );
				liens.forEach( function ( l ) {
					l.classList.toggle( 'est-survole', l === lien );
				} );
				if ( lien.getAttribute( 'data-photo' ) ) {
					montrer( lien.getAttribute( 'data-photo' ) );
				}
			};
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

	// Le sac : le panier, lu dans l'API du magasin ; la pastille.
	var conf = window.erMenus || {};
	var liste = document.querySelector( '.er-mn-panier-liste' );
	var pastilles = Array.prototype.slice.call( document.querySelectorAll( '.er-sac-nombre' ) );
	function texte( s ) {
		var d = document.createElement( 'div' );
		d.textContent = s;
		return d.innerHTML;
	}
	function prix( valeur, p ) {
		var n = parseInt( valeur, 10 ) / Math.pow( 10, p.currency_minor_unit || 0 );
		var chiffres = n.toFixed( p.currency_minor_unit || 0 ).replace( '.', p.currency_decimal_separator || ',' );
		return ( p.currency_prefix || '' ) + chiffres + ( p.currency_suffix || '' );
	}
	function pastille( n ) {
		pastilles.forEach( function ( el ) {
			el.textContent = String( n );
			el.hidden = ! n;
		} );
	}
	function lirePanier() {
		if ( ! conf.panier || ! window.fetch ) {
			return;
		}
		window.fetch( conf.panier, { credentials: 'same-origin', headers: { Accept: 'application/json' } } )
			.then( function ( r ) {
				return r.ok ? r.json() : null;
			} )
			.then( function ( panier ) {
				if ( ! panier ) {
					return;
				}
				pastille( panier.items_count || 0 );
				if ( ! liste ) {
					return;
				}
				var articles = panier.items || [];
				var colonne = liste.closest( '.er-mn-panier-col' );
				if ( colonne ) {
					colonne.classList.toggle( 'est-vide', ! articles.length );
				}
				if ( ! articles.length ) {
					liste.innerHTML = '<p class="er-mn-panier-vide">' + texte( liste.getAttribute( 'data-vide' ) ) + '</p>';
					return;
				}
				var html = '<ul class="er-mn-panier-articles">';
				articles.slice( 0, 3 ).forEach( function ( a ) {
					var image = a.images && a.images[ 0 ] ? '<img src="' + texte( a.images[ 0 ].thumbnail ) + '" alt="">' : '<span class="er-mn-panier-sans"></span>';
					html += '<li><a href="' + texte( a.permalink ) + '">' + image + '<span class="er-mn-panier-texte"><span class="er-mn-panier-nom">' + a.name + '</span>'
						+ '<span class="er-mn-panier-prix">' + a.quantity + ' × ' + texte( prix( a.prices.price, a.prices ) ) + '</span></span></a></li>';
				} );
				html += '</ul>';
				if ( articles.length > 3 ) {
					html += '<p class="er-mn-panier-autres">' + texte( liste.getAttribute( 'data-autres' ).replace( '%d', articles.length - 3 ) ) + '</p>';
				}
				if ( panier.totals ) {
					html += '<p class="er-mn-panier-total">' + texte( liste.getAttribute( 'data-total' ).replace( '%s', prix( panier.totals.total_items, panier.totals ) ) ) + '</p>';
				}
				liste.innerHTML = html;
			} )
			.catch( function () {} );
	}
	// Après un ajout au panier (les blocs de WooCommerce le disent sur la page), la pastille suit.
	document.body.addEventListener( 'wc-blocks_added_to_cart', lirePanier );
	document.body.addEventListener( 'wc-blocks_removed_from_cart', lirePanier );
}() );
