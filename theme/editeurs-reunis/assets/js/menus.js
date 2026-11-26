/**
 * Les menus de l'en-tête (inc/menus.php ; retenus le 26 novembre 2026) :
 *
 * - un nom de la barre ouvre son menu au survol, après un court temps (on ne l'ouvre pas en passant) ; on passe d'un
 *   nom à l'autre sans attendre ; le menu se referme quand la souris quitte l'en-tête et ses menus, sur un clic
 *   ailleurs, sur Échap, ou sur un clic sur son nom. Au doigt et au clavier, le nom s'ouvre et se referme au clic ;
 * - la Librairie : les sous-rayons du rayon survolé, et le livre du rayon ou du sous-rayon survolé ;
 * - les grands mots : le mot survolé reste à l'encre, les autres pâlissent, la photo change ;
 * - le sac : le panier, lu à l'ouverture dans l'API du magasin, et le compte ; sa pastille suit les ajouts ;
 * - le plan du site (≡, jusqu'à 1 320 px) : Fermer et Échap rendent la main au bouton ; Tab ne le quitte pas ;
 * - au téléphone, dans ce plan, le menu P1 : les maisons, leurs pages en tiroir, les informations du haut en carrousel.
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
	var ouvertAuSurvol = 0; // quand le survol a ouvert le menu courant

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
		if ( menu && menu === plan ) {
			// Le plan se replie, puis se cache.
			plan.classList.remove( 'est-ouvert' );
			window.setTimeout( function () {
				if ( ! courant || cible( courant ) !== plan ) {
					plan.hidden = true;
					plan.dispatchEvent( new CustomEvent( 'er-plan-ferme' ) );
				}
			}, 420 );
		} else if ( menu ) {
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
		// Un menu qui s'ouvre referme la liste des langues, si elle était ouverte.
		document.querySelectorAll( '.er-choix-langue-liste:not([hidden])' ).forEach( function ( liste ) {
			var choix = liste.parentNode.querySelector( '.er-choix-langue-bouton' );
			liste.hidden = true;
			if ( choix ) {
				choix.setAttribute( 'aria-expanded', 'false' );
			}
		} );
		menu.hidden = false;
		racine.setAttribute( 'data-er-em', bouton.getAttribute( 'data-er-em' ) );
		menu.querySelectorAll( 'img[loading="lazy"]' ).forEach( function ( image ) {
			image.loading = 'eager';
		} );
		if ( 'panier' === bouton.getAttribute( 'data-er-em' ) ) {
			lirePanier();
		}
		if ( menu === plan ) {
			// Sous la barre du nom, qui reste : le bouton (≡, devenu ×) referme le plan à la même place. Si la barre
			// est sortie de l'écran, le plan prend tout l'écran, avec sa propre barre et « Fermer ».
			placerPlan();
			racine.classList.add( 'er-em-plan-ouvert' );
			void plan.offsetHeight; // le plan part replié, puis se déplie
			window.requestAnimationFrame( function () {
				plan.classList.add( 'est-ouvert' );
			} );
			if ( plan.classList.contains( 'avec-barre' ) ) {
				var croix = plan.querySelector( '.er-em-fermer-plan' );
				if ( croix ) {
					croix.focus();
				}
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

	// Les entrées du plan arrivent l'une après l'autre : chacune a son rang (le plan en colonnes et le menu du
	// téléphone ont chacun leur suite).
	if ( plan ) {
		[ '.er-em-colonne-titre, .er-em-colonne li, .er-em-plan-pied > *', '.er-mp-maisons > li, .er-mp-bas > *' ].forEach( function ( suite ) {
			plan.querySelectorAll( suite ).forEach( function ( el, i ) {
				el.classList.add( 'er-em-anim' );
				el.style.setProperty( '--er-i', String( Math.min( i, 24 ) ) );
			} );
		} );
	}

	// Le menu du téléphone (P1, inc/menus.php) : une maison touchée fait glisser ses pages depuis la droite ;
	// « ‹ Le menu » les ramène. Le panneau qu'on ne voit pas ne prend ni le doigt ni le clavier (inert). Le plan
	// refermé revient aux maisons.
	var telephone = plan && plan.querySelector( '.er-mp' );
	if ( telephone ) {
		var retour = telephone.querySelector( '.er-mp-retour' );
		var panneauMaisons = telephone.querySelector( '.er-mp-panneau-maisons' );
		var panneauPages = telephone.querySelector( '.er-mp-panneau-pages' );
		var maisons = Array.prototype.slice.call( telephone.querySelectorAll( '.er-mp-maison' ) );
		var maisonOuverte = null;
		var tiroir = function ( maison ) {
			var ouvert = !! maison;
			if ( maison ) {
				telephone.querySelectorAll( '.er-mp-tiroir' ).forEach( function ( t ) {
					t.hidden = t.id !== maison.getAttribute( 'aria-controls' );
				} );
				panneauPages.scrollTop = 0;
			}
			maisons.forEach( function ( m ) {
				m.setAttribute( 'aria-expanded', m === maison ? 'true' : 'false' );
			} );
			telephone.classList.toggle( 'est-pages', ouvert );
			panneauMaisons.toggleAttribute( 'inert', ouvert );
			panneauPages.toggleAttribute( 'inert', ! ouvert );
			retour.toggleAttribute( 'inert', ! ouvert );
			if ( ouvert ) {
				maisonOuverte = maison;
			}
		};
		maisons.forEach( function ( maison ) {
			maison.addEventListener( 'click', function () {
				tiroir( maison );
				retour.focus( { preventScroll: true } );
			} );
		} );
		retour.addEventListener( 'click', function () {
			var revenir = maisonOuverte;
			tiroir( null );
			if ( revenir ) {
				revenir.focus( { preventScroll: true } );
			}
		} );
		plan.addEventListener( 'er-plan-ferme', function () {
			var rail = telephone.querySelector( '.er-mp-rail' );
			rail.style.transition = 'none';
			tiroir( null );
			void rail.offsetWidth;
			rail.style.transition = '';
		} );

		// Le carrousel : les rencontres passées s'en vont (deux heures après leur début ; la page peut dater) ; un
		// trait par carte, qui y mène ; le trait noir suit la carte en vue.
		var cartes = telephone.querySelector( '.er-mp-cartes' );
		var traits = telephone.querySelector( '.er-mp-traits' );
		if ( cartes && traits ) {
			cartes.querySelectorAll( '[data-debut]' ).forEach( function ( carte ) {
				var debut = new Date( carte.getAttribute( 'data-debut' ) );
				if ( ! isNaN( debut.getTime() ) && debut.getTime() + 2 * 36e5 < Date.now() ) {
					carte.remove();
				}
			} );
			var lesCartes = Array.prototype.slice.call( cartes.children );
			var libelle = traits.getAttribute( 'data-libelle' ) || '%1$s / %2$s';
			var calme = window.matchMedia( '(prefers-reduced-motion: reduce)' );
			lesCartes.forEach( function ( carte, n ) {
				var trait = document.createElement( 'button' );
				trait.type = 'button';
				trait.setAttribute( 'aria-label', libelle.replace( '%1$s', String( n + 1 ) ).replace( '%2$s', String( lesCartes.length ) ) );
				trait.appendChild( document.createElement( 'span' ) );
				trait.addEventListener( 'click', function () {
					cartes.scrollTo( { left: carte.offsetLeft, behavior: calme.matches ? 'auto' : 'smooth' } );
				} );
				traits.appendChild( trait );
			} );
			traits.hidden = lesCartes.length < 2;
			var suivre = function () {
				var vue = 0;
				if ( cartes.scrollLeft >= cartes.scrollWidth - cartes.clientWidth - 2 ) {
					vue = lesCartes.length - 1;
				} else {
					lesCartes.forEach( function ( carte, n ) {
						if ( carte.offsetLeft <= cartes.scrollLeft + 10 ) {
							vue = n;
						}
					} );
				}
				Array.prototype.forEach.call( traits.children, function ( trait, n ) {
					trait.setAttribute( 'aria-current', n === vue ? 'true' : 'false' );
				} );
			};
			cartes.addEventListener( 'scroll', function () {
				window.requestAnimationFrame( suivre );
			}, { passive: true } );
			suivre();
		}
	}
	function placerPlan() {
		if ( ! plan ) {
			return;
		}
		var barre = document.querySelector( '.er-em-barre' );
		var bas = barre ? Math.round( barre.getBoundingClientRect().bottom ) : 0;
		plan.classList.toggle( 'avec-barre', bas <= 0 );
		racine.style.setProperty( '--er-plan-haut', Math.max( 0, bas ) + 'px' );
		// La hauteur du milieu du bouton (≡, devenu ×) : la première maison du plan s'aligne dessus.
		var menu = document.querySelector( '.er-em-menu' );
		if ( menu ) {
			var r = menu.getBoundingClientRect();
			racine.style.setProperty( '--er-plan-croix', Math.max( 0, Math.round( r.top + r.height / 2 ) ) + 'px' );
		}
	}
	window.addEventListener( 'resize', function () {
		if ( courant && cible( courant ) === plan ) {
			placerPlan();
		}
	} );

	boutons.forEach( function ( bouton ) {
		var duPlan = 'plan' === bouton.getAttribute( 'data-er-em' );
		bouton.addEventListener( 'click', function ( e ) {
			if ( courant === bouton ) {
				// Un clic sur le nom d'un menu ouvert le referme, à la souris aussi (André, 26 nov.) ; sauf si le
				// survol vient de l'ouvrir, à l'instant : ce clic-là voulait l'ouvrir, pas le refermer.
				if ( survol.matches && ! duPlan && 0 !== e.detail && Date.now() - ouvertAuSurvol < 400 ) {
					return;
				}
				fermer( false );
				return;
			}
			ouvertAuSurvol = 0;
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
				if ( courant !== bouton ) {
					ouvrir( bouton, false );
					ouvertAuSurvol = Date.now();
				}
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
			var liens = Array.prototype.slice.call( plan.querySelectorAll( 'a, button, input:not([type="hidden"])' ) ).filter( function ( el ) {
				return el.offsetParent !== null && ! el.closest( '[inert]' );
			} );
			if ( ! plan.classList.contains( 'avec-barre' ) ) {
				liens.unshift( courant ); // le bouton du menu, qui le referme
			}
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
				// Deux colonnes de deux : quatre livres au plus ; au-delà, trois, et la quatrième case dit combien d'autres.
				var html = '<ul class="er-mn-panier-articles">';
				var montres = articles.length > 4 ? articles.slice( 0, 3 ) : articles;
				montres.forEach( function ( a ) {
					var image = a.images && a.images[ 0 ] ? '<img src="' + texte( a.images[ 0 ].thumbnail ) + '" alt="">' : '<span class="er-mn-panier-sans"></span>';
					html += '<li><a href="' + texte( a.permalink ) + '">' + image + '<span class="er-mn-panier-texte"><span class="er-mn-panier-nom">' + a.name + '</span>'
						+ '<span class="er-mn-panier-prix">' + a.quantity + ' × ' + texte( prix( a.prices.price, a.prices ) ) + '</span></span></a></li>';
				} );
				if ( articles.length > 4 ) {
					var reste = articles.slice( 3 ).reduce( function ( somme, a ) {
						return somme + ( parseInt( a.quantity, 10 ) || 1 );
					}, 0 );
					var modele = reste > 1 ? liste.getAttribute( 'data-autres' ) : liste.getAttribute( 'data-autre' );
					var voir = colonne ? colonne.querySelector( '.er-mn-bouton' ) : null;
					var dire = texte( modele.replace( '%d', reste ) );
					html += '<li class="er-mn-panier-autres">' + ( voir ? '<a href="' + texte( voir.href ) + '">' + dire + '</a>' : dire ) + '</li>';
				}
				html += '</ul>';
				if ( panier.totals ) {
					html += '<p class="er-mn-panier-total">' + texte( liste.getAttribute( 'data-total' ).replace( '%s', prix( panier.totals.total_items, panier.totals ) ) ) + '</p>';
				}
				liste.innerHTML = html;
				liste.classList.remove( 'est-survol' );
			} )
			.catch( function () {} );
	}
	// Le survol dans le sac, comme dans les autres menus : le livre ou le lien survolé reste à l'encre, les autres
	// pâlissent, jusqu'à ce que la souris quitte la liste (pas d'éclipse en passant d'un livre à l'autre). Les livres
	// arrivent après coup, d'où l'écoute de la liste entière.
	document.querySelectorAll( '.er-mn-panier-liste, .er-mn-compte' ).forEach( function ( bloc ) {
		var vider = function () {
			bloc.classList.remove( 'est-survol' );
			bloc.querySelectorAll( '.est-survole' ).forEach( function ( a ) {
				a.classList.remove( 'est-survole' );
			} );
		};
		var viser = function ( e ) {
			var lien = e.target.closest ? e.target.closest( 'a' ) : null;
			if ( ! lien || ! bloc.contains( lien ) ) {
				return;
			}
			bloc.querySelectorAll( '.est-survole' ).forEach( function ( a ) {
				if ( a !== lien ) {
					a.classList.remove( 'est-survole' );
				}
			} );
			lien.classList.add( 'est-survole' );
			bloc.classList.add( 'est-survol' );
		};
		bloc.addEventListener( 'mouseover', viser );
		bloc.addEventListener( 'focusin', viser );
		bloc.addEventListener( 'mouseleave', vider );
		bloc.addEventListener( 'focusout', function ( e ) {
			if ( ! bloc.contains( e.relatedTarget ) ) {
				vider();
			}
		} );
	} );
	// Après un ajout au panier (les blocs de WooCommerce le disent sur la page), la pastille suit.
	document.body.addEventListener( 'wc-blocks_added_to_cart', lirePanier );
	document.body.addEventListener( 'wc-blocks_removed_from_cart', lirePanier );
}() );
