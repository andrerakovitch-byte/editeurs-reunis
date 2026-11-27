/**
 * Les menus de l'en-tête (inc/menus.php ; retenus le 26 novembre 2026) :
 *
 * - un nom de la barre ouvre son menu au clic, plus au survol (André, 26 nov. : en allant vers « Centre culturel » ou
 *   vers les langues, on passait sur « Maison d'édition », qui s'ouvrait) ; un clic sur un autre nom ouvre le sien ; le
 *   menu se referme sur un clic sur son nom, sur un clic ailleurs ou sur Échap ;
 * - la Librairie : les sous-rayons du rayon survolé, et le livre du rayon ou du sous-rayon survolé ;
 * - les grands mots : le mot survolé reste à l'encre, les autres pâlissent, la photo change ;
 * - le sac : le panier, lu à l'ouverture dans l'API du magasin, et le compte ; sa pastille suit les ajouts ;
 * - le plan du site (≡, jusqu'à 1 360 px) : Fermer et Échap rendent la main au bouton ; Tab ne le quitte pas ;
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
	var plan = document.getElementById( 'er-em-plan' );
	var voile = document.querySelector( '.er-em-voile' );
	var courant = null;

	function cible( bouton ) {
		return document.getElementById( bouton.getAttribute( 'aria-controls' ) );
	}
	function fermer( rendre ) {
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
		racine.removeAttribute( 'data-er-em-ouvert' );
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
		// Le menu ouvert, sur <html> : pas sous « data-er-em », le nom des boutons (closest( '[data-er-em]' ) trouvait
		// <html> à chaque clic ; audit du 27 nov. 2026).
		racine.setAttribute( 'data-er-em-ouvert', bouton.getAttribute( 'data-er-em' ) );
		// Les images qu'on voit tout de suite, sans attendre : seulement celles-là (le panneau de la Librairie cache un
		// livre par rayon et sous-rayon, 58 couvertures), et sans le « sizes="auto" » de WordPress, que le navigateur
		// ne lit que pour une image paresseuse (sinon la plus grande taille du srcset).
		menu.querySelectorAll( 'img[loading="lazy"]' ).forEach( function ( image ) {
			if ( image.getClientRects().length ) {
				if ( image.sizes ) {
					image.sizes = image.sizes.replace( /^\s*auto\s*,\s*/, '' );
				}
				image.loading = 'eager';
			}
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
				// Le menu fermé, le carrousel n'a pas de largeur : on resterait sur la dernière carte (« au bout »).
				var largeur = cartes.scrollWidth - cartes.clientWidth;
				if ( cartes.clientWidth > 0 && largeur > 2 && cartes.scrollLeft >= largeur - 2 ) {
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
			// À l'ouverture du menu, le carrousel prend sa largeur : on recale le trait sur la carte en vue.
			if ( 'ResizeObserver' in window ) {
				new window.ResizeObserver( suivre ).observe( cartes );
			}
			suivre();
		}
	}
	function placerPlan() {
		if ( ! plan ) {
			return;
		}
		var barre = document.querySelector( '.er-em-barre' );
		var bas = barre ? Math.round( barre.getBoundingClientRect().bottom ) : 0;
		var menu = document.querySelector( '.er-em-menu' );
		// Le plan n'a sa propre barre (et sa croix) que si le bouton du menu est sorti de l'écran avec la barre du nom.
		// Au téléphone, le bouton flotte et reste en vue (entete.js) : sinon, deux croix l'une sur l'autre (André, 27 nov.).
		var boutonEnVue = menu && menu.getBoundingClientRect().bottom > 0;
		plan.classList.toggle( 'avec-barre', bas <= 0 && ! boutonEnVue );
		racine.style.setProperty( '--er-plan-haut', Math.max( 0, bas ) + 'px' );
		// La hauteur du milieu du bouton (≡, devenu ×) : la première maison du plan s'aligne dessus.
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
		bouton.addEventListener( 'click', function ( e ) {
			if ( courant === bouton ) {
				fermer( false );
				return;
			}
			ouvrir( bouton, 0 === e.detail );
		} );
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
		// Un clic dans le menu, ou sur un nom de la barre (pas sur la page elle-même, qui porte aussi data-er-em quand un
		// menu est ouvert), ne le referme pas.
		var menu = cible( courant );
		var nom = e.target.closest( '[data-er-em]' );
		if ( ( menu && menu.contains( e.target ) ) || nom ) {
			return;
		}
		fermer( false );
	} );
	// Au clavier, la main qui sort d'un menu le referme : elle allait sinon sur des liens cachés sous le panneau ou
	// sous le voile, menu toujours ouvert (audit du 27 nov. 2026). Le plan garde la main (son tour de Tab, plus bas).
	document.addEventListener( 'focusin', function ( e ) {
		if ( ! courant || cible( courant ) === plan ) {
			return;
		}
		var menu = cible( courant );
		if ( courant === e.target || ( menu && menu.contains( e.target ) ) ) {
			return;
		}
		fermer( false );
	} );
	// Passer d'un côté à l'autre de 1 360 px referme ce qui est ouvert : au-dessus, le bouton du plan disparaît (le plan
	// restait plein écran, sans croix) ; au-dessous, les noms disparaissent (leur menu restait ouvert, écrasé). Un iPad
	// qu'on tourne, une fenêtre qu'on agrandit.
	var large = window.matchMedia( '(min-width: 1360px)' );
	var auPassage = function () {
		if ( courant ) {
			var etaitLePlan = cible( courant ) === plan;
			fermer( false );
			if ( etaitLePlan && plan ) {
				plan.hidden = true;
			}
		}
	};
	if ( large.addEventListener ) {
		large.addEventListener( 'change', auPassage );
	} else if ( large.addListener ) {
		large.addListener( auPassage ); // Safari antérieur à 14.
	}
	// Revenir en arrière (le cache de navigation de Safari remet la page telle qu'on l'a laissée) : le menu se referme,
	// sans attendre le repli.
	window.addEventListener( 'pageshow', function ( e ) {
		if ( ! e.persisted || ! courant ) {
			return;
		}
		var etaitLePlan = cible( courant ) === plan;
		fermer( false );
		if ( etaitLePlan && plan ) {
			plan.hidden = true;
			plan.dispatchEvent( new CustomEvent( 'er-plan-ferme' ) );
		}
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
			// Déjà traité plus près (la recherche ouverte depuis le menu du téléphone se referme seule) : le menu reste.
			if ( ! e.defaultPrevented ) {
				fermer( true );
			}
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
			// Au clavier, Tab depuis un rayon entre dans ses sous-rayons (ils viennent après tous les rayons dans la page,
			// et le rayon suivant les remplaçait : seuls ceux du dernier étaient atteignables ; audit du 27 nov. 2026).
			lien.addEventListener( 'keydown', function ( e ) {
				if ( 'Tab' !== e.key || e.shiftKey ) {
					return;
				}
				var bloc = librairie.querySelector( '.er-mn-sous-rayon[data-rayon="' + lien.getAttribute( 'data-rayon' ) + '"]' );
				var premier = bloc ? bloc.querySelector( 'a' ) : null;
				if ( premier ) {
					e.preventDefault();
					premier.focus();
				}
			} );
		} );
		// Et du dernier sous-rayon, au rayon suivant ; du premier, Maj+Tab revient à son rayon.
		librairie.querySelectorAll( '.er-mn-sous-rayon' ).forEach( function ( bloc ) {
			var leurs = bloc.querySelectorAll( 'a' );
			var rayon = librairie.querySelector( '.er-mn-rayons a[data-rayon="' + bloc.getAttribute( 'data-rayon' ) + '"]' );
			if ( ! leurs.length || ! rayon ) {
				return;
			}
			leurs[ leurs.length - 1 ].addEventListener( 'keydown', function ( e ) {
				if ( 'Tab' !== e.key || e.shiftKey ) {
					return;
				}
				var li = rayon.closest( 'li' );
				var suivant = li && li.nextElementSibling ? li.nextElementSibling.querySelector( 'a' ) : null;
				if ( suivant ) {
					e.preventDefault();
					suivant.focus();
				}
			} );
			leurs[ 0 ].addEventListener( 'keydown', function ( e ) {
				if ( 'Tab' === e.key && e.shiftKey ) {
					e.preventDefault();
					rayon.focus();
				}
			} );
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
	// Pour une valeur d'attribut : les guillemets aussi.
	function attribut( s ) {
		return texte( s ).replace( /"/g, '&quot;' ).replace( /'/g, '&#39;' );
	}
	// Un nom du magasin, qui peut contenir du HTML : son texte seul (un <template> reste inerte, rien ne s'y charge).
	function enTexte( html ) {
		var t = document.createElement( 'template' );
		t.innerHTML = String( html == null ? '' : html );
		return t.content.textContent;
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
					// Ouvert au clavier, la main était sur « Voir le panier », qui vient de disparaître : au premier lien
					// qu'on voit.
					var sac = courant && 'panier' === courant.getAttribute( 'data-er-em' ) ? cible( courant ) : null;
					if ( document.activeElement === document.body && sac && ! sac.hidden ) {
						var visible = Array.prototype.slice.call( sac.querySelectorAll( 'a' ) ).filter( function ( a ) {
							return a.getClientRects().length > 0;
						} )[ 0 ];
						if ( visible ) {
							visible.focus();
						}
					}
					return;
				}
				// Deux colonnes de deux : quatre livres au plus ; au-delà, trois, et la quatrième case dit combien d'autres.
				var html = '<ul class="er-mn-panier-articles">';
				var montres = articles.length > 4 ? articles.slice( 0, 3 ) : articles;
				montres.forEach( function ( a ) {
					// Tout est échappé ici, sans compter sur le nettoyage fait par WooCommerce : le nom réduit à son texte,
					// les adresses avec leurs guillemets (audit du 27 nov. 2026). La petite image, prise dans le srcset.
					var im = a.images && a.images[ 0 ];
					var image = im ? '<img src="' + attribut( im.thumbnail ) + '"' + ( im.srcset ? ' srcset="' + attribut( im.srcset ) + '" sizes="44px"' : '' ) + ' alt="">' : '<span class="er-mn-panier-sans"></span>';
					html += '<li><a href="' + attribut( a.permalink ) + '">' + image + '<span class="er-mn-panier-texte"><span class="er-mn-panier-nom">' + texte( enTexte( a.name ) ) + '</span>'
						+ '<span class="er-mn-panier-prix">' + texte( String( a.quantity ) ) + ' × ' + texte( prix( a.prices.price, a.prices ) ) + '</span></span></a></li>';
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
