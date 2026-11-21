/**
 * Le panneau de recherche : la loupe de la barre noire l'ouvre (et devient « Fermer »), Échap ou un clic sur le voile
 * le referme. Avant de taper, des pistes ; en tapant, les résultats de l'API de la librairie, rangés par genre ; la
 * recherche détaillée compte les livres à chaque choix. Les flèches passent d'un résultat à l'autre.
 *
 * Sans ce script, la loupe mène à la recherche des livres, et la recherche détaillée reste un formulaire ordinaire.
 */
( function () {
	'use strict';

	var R = window.erRecherche;
	var panneau = document.getElementById( 'er-recherche' );
	if ( ! R || ! panneau ) {
		return;
	}
	var M = R.mots;
	var loupes = Array.prototype.slice.call( document.querySelectorAll( '.er-loupe' ) );
	var champ = panneau.querySelector( '#er-recherche-texte' );
	var formulaire = panneau.querySelector( '.er-recherche-champ' );
	var effacer = panneau.querySelector( '.er-recherche-effacer' );
	var bascule = panneau.querySelector( '.er-recherche-bascule' );
	var pour = panneau.querySelector( '.er-recherche-pour' );
	var portees = panneau.querySelector( '.er-recherche-portees' );
	var pistes = panneau.querySelector( '.er-recherche-pistes' );
	var resultats = panneau.querySelector( '.er-recherche-resultats' );
	var detaillee = panneau.querySelector( '.er-recherche-detaillee' );
	var recentes = panneau.querySelector( '.er-recherche-recentes' );
	var voir = detaillee.querySelector( '.er-rd-voir' );
	var criteres = detaillee.querySelector( '.er-rd-criteres' );
	var corps = panneau.querySelector( '.er-recherche-corps' );
	var cadreFiltres = panneau.querySelector( '.er-recherche-filtres' );
	var filtrer = panneau.querySelector( '.er-recherche-filtrer' );
	var nombreFiltres = panneau.querySelector( '.er-recherche-nombre' );
	var effacerFiltres = panneau.querySelector( '.er-filtres-effacer' );
	var filtresLivres = panneau.querySelector( '.er-filtres-livres' );
	var postType = formulaire.querySelector( 'input[name="post_type"]' );

	var ouvert = false;
	var dernier = null; // L'élément qui avait la main avant l'ouverture.
	var defilement = 0;
	var donnees = null;
	var portee = 'tout';
	// Les filtres de la colonne de gauche : où chercher (une maison), puis, pour les livres, rayons, langues, états.
	var filtres = { maison: 'tout', rayon: [], langue: [], etat: [] };
	var LIVRES = [ 'rayon', 'langue', 'etat' ];
	var minuterie;
	var compteur;
	var demande = 0;

	var FLECHE = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M5 12h13M13 7l5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	var CROIX = '<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
	var HORLOGE = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v4l3 2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';

	function echapper( t ) {
		return String( t == null ? '' : t ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}
	function format( modele ) {
		var valeurs = Array.prototype.slice.call( arguments, 1 );
		var rang = 0;
		return modele.replace( /%(?:(\d)\$)?s/g, function ( tout, n ) {
			return String( valeurs[ n ? n - 1 : rang++ ] );
		} );
	}
	function api( chemin, parametres ) {
		if ( R.langue ) {
			parametres.append( 'lang', R.langue );
		}
		return fetch( R.api + chemin + '?' + parametres.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } } ).then( function ( r ) {
			if ( ! r.ok ) {
				throw new Error( r.status );
			}
			return r.json();
		} );
	}

	/* Les dernières recherches, gardées dans le navigateur ------------------------------------------------------- */

	function lireRecentes() {
		try {
			return JSON.parse( window.localStorage.getItem( 'er-recherches' ) || '[]' ).slice( 0, 4 );
		} catch ( e ) {
			return [];
		}
	}
	function garderRecente( texte ) {
		texte = ( texte || '' ).trim();
		if ( texte.length < 2 ) {
			return;
		}
		try {
			var liste = lireRecentes().filter( function ( t ) {
				return t.toLowerCase() !== texte.toLowerCase();
			} );
			liste.unshift( texte );
			window.localStorage.setItem( 'er-recherches', JSON.stringify( liste.slice( 0, 4 ) ) );
		} catch ( e ) {}
	}
	function montrerRecentes() {
		var liste = lireRecentes();
		recentes.hidden = ! liste.length;
		recentes.querySelector( 'ul' ).innerHTML = liste.map( function ( t ) {
			return '<li><a class="er-r-lien er-recherche-proposition" href="#" data-er-texte="' + echapper( t ) + '">' + HORLOGE + echapper( t ) + '</a></li>';
		} ).join( '' );
	}

	/* Ouvrir, fermer --------------------------------------------------------------------------------------------- */

	function placer() {
		var barre = document.querySelector( '.er-barre' );
		var bas = barre ? Math.max( 0, barre.getBoundingClientRect().bottom ) : 0;
		panneau.style.setProperty( '--er-recherche-haut', bas + 'px' );
	}
	function ouvrir( detail ) {
		if ( ouvert ) {
			return;
		}
		ouvert = true;
		dernier = document.activeElement;
		defilement = window.scrollY;
		window.scrollTo( 0, 0 );
		placer();
		panneau.hidden = false;
		document.documentElement.classList.add( 'er-recherche-ouverte' );
		loupes.forEach( function ( l ) {
			l.setAttribute( 'aria-expanded', 'true' );
			var libelle = l.querySelector( '.er-loupe-libelle' );
			if ( libelle ) {
				libelle.textContent = M.fermer;
			}
		} );
		montrerRecentes();
		if ( detail ) {
			basculer( true );
		}
		window.setTimeout( function () {
			( detail ? detaillee.querySelector( 'input, select' ) : champ ).focus();
		}, 30 );
	}
	function fermer() {
		if ( ! ouvert ) {
			return;
		}
		ouvert = false;
		panneau.hidden = true;
		document.documentElement.classList.remove( 'er-recherche-ouverte' );
		loupes.forEach( function ( l ) {
			l.setAttribute( 'aria-expanded', 'false' );
			var libelle = l.querySelector( '.er-loupe-libelle' );
			if ( libelle ) {
				libelle.textContent = M.rechercher;
			}
		} );
		window.scrollTo( 0, defilement );
		if ( dernier && dernier.focus && document.contains( dernier ) ) {
			dernier.focus();
		}
	}

	loupes.forEach( function ( l ) {
		l.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( ouvert ) {
				fermer();
			} else {
				ouvrir();
			}
		} );
	} );
	panneau.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-er-fermer]' ) ) {
			fermer();
		}
	} );
	window.addEventListener( 'resize', function () {
		if ( ouvert ) {
			placer();
		}
	} );

	// Au téléphone, le champ du menu (≡) ouvre le panneau : on referme le menu d'abord.
	document.querySelectorAll( '.er-chercher-menu input[type="search"]' ).forEach( function ( entree ) {
		entree.setAttribute( 'readonly', 'readonly' );
		function depuisLeMenu( e ) {
			e.preventDefault();
			var fermerMenu = document.querySelector( '.er-menu .wp-block-navigation__responsive-container-close' );
			if ( fermerMenu ) {
				fermerMenu.click();
			}
			window.setTimeout( function () {
				ouvrir();
			}, 60 );
		}
		entree.addEventListener( 'focus', depuisLeMenu );
		entree.addEventListener( 'click', depuisLeMenu );
	} );

	/* En tapant -------------------------------------------------------------------------------------------------- */

	var ORDRE = [ 'auteurs', 'livres', 'rayons', 'encyclopedie', 'ymca', 'centre', 'journal', 'pages' ];
	var PORTEES = [ 'tout', 'livres', 'auteurs', 'encyclopedie', 'ymca', 'centre', 'journal' ];
	var MAISON = { auteurs: 'librairie', livres: 'librairie', rayons: 'librairie', encyclopedie: 'librairie', ymca: 'ymca', centre: 'centre', journal: 'centre', pages: '' };

	function point( maison ) {
		return maison ? '<span class="er-point er-point-' + maison + '" aria-hidden="true"></span>' : '';
	}
	function ligne( r, sous ) {
		return '<li><a class="er-r-lien er-r-ligne" href="' + echapper( r.url ) + '"><span class="er-r-texte"><span class="er-r-titre">' + echapper( r.titre ) + '</span>'
			+ ( sous ? '<span class="er-r-sous">' + echapper( sous ) + '</span>' : '' ) + '</span>' + FLECHE + '</a></li>';
	}
	function livre( l ) {
		var couverture = l.image ? '<img src="' + echapper( l.image ) + '" alt="" loading="lazy">' : '<span class="er-r-couverture-vide" aria-hidden="true"><span>' + echapper( l.titre ) + '</span></span>';
		return '<li><a class="er-r-lien er-r-livre" href="' + echapper( l.url ) + '">' + couverture
			+ ( l.mention ? '<span class="er-r-mention">' + echapper( l.mention ) + '</span>' : '' )
			+ '<span class="er-r-titre">' + echapper( l.titre ) + '</span>'
			+ ( l.auteurs ? '<span class="er-r-sous">' + echapper( l.auteurs ) + '</span>' : '' )
			+ '<span class="er-r-prix">' + ( l.prix ? echapper( l.prix ) + ' · ' : '' ) + '<span>' + echapper( l.stock ? M.en_stock : M.rupture ) + '</span></span></a></li>';
	}
	function auteur( a, premier ) {
		var nombre = a.livres === 1 ? M.un_livre : format( M.n_livres, a.livres );
		if ( ! premier ) {
			return ligne( a, nombre );
		}
		return '<li><a class="er-r-lien er-r-auteur" href="' + echapper( a.url ) + '"><span class="er-r-lettre" aria-hidden="true" lang="ru">' + echapper( a.lettre ) + '</span>'
			+ '<span class="er-r-texte"><span class="er-r-nom">' + echapper( a.titre ) + '</span>'
			+ ( a.graphies.length ? '<span class="er-r-sous">' + echapper( a.graphies.join( ' · ' ) ) + '</span>' : '' )
			+ '<span class="er-r-sous">' + echapper( nombre ) + '</span>'
			+ '<span class="er-r-plus">' + echapper( M.page_auteur ) + FLECHE + '</span></span></a></li>';
	}
	function groupe( nom, contenu, compte, plus ) {
		return '<section class="er-r-groupe er-r-groupe-' + nom + '"><div class="er-r-groupe-tete"><h2 class="er-recherche-surtitre">' + point( MAISON[ nom ] )
			+ echapper( M[ 'g_' + nom ] ) + ' <span class="er-r-compte">' + compte + '</span></h2>' + ( plus || '' ) + '</div>' + contenu + '</section>';
	}

	function dansLaMaison( nom ) {
		return 'tout' === filtres.maison || MAISON[ nom ] === filtres.maison;
	}
	function rendre() {
		var d = donnees;
		if ( ! d ) {
			return;
		}
		var c = Object.assign( {}, d.comptes || {} );
		var g = d.groupes || {};
		if ( d.filtre ) {
			rendreFiltre( d );
			return;
		}
		// La maison choisie : seulement ses genres, et « Tout » compte ce qu'elle a.
		if ( 'tout' !== filtres.maison ) {
			c.tout = ORDRE.reduce( function ( somme, nom ) {
				return somme + ( dansLaMaison( nom ) && 'rayons' !== nom ? ( c[ nom ] || 0 ) : 0 );
			}, 0 );
		}
		if ( 'tout' !== portee && ! dansLaMaison( portee ) ) {
			portee = 'tout';
		}
		// Le nom reconnu.
		if ( d.pour ) {
			pour.innerHTML = format( echapper( M.pour ), '<strong>' + echapper( d.pour ) + '</strong>' ) + ( d.aussi && d.aussi.length ? ' ' + echapper( format( M.aussi, d.aussi.join( ', ' ) ) ) : '' );
			pour.hidden = false;
		} else {
			pour.hidden = true;
		}
		// Les genres, avec leurs nombres.
		portees.innerHTML = PORTEES.filter( function ( p ) {
			return p === 'tout' || ( dansLaMaison( p ) && ( p === portee || c[ p ] ) );
		} ).map( function ( p ) {
			return '<button type="button" class="er-pilule' + ( p === portee ? ' est-active' : '' ) + '" aria-pressed="' + ( p === portee ) + '" data-er-portee="' + p + '">'
				+ echapper( M[ p ] ) + ' <span>' + ( c[ p ] || 0 ) + '</span></button>';
		} ).join( '' );
		portees.hidden = false;

		if ( ! c.tout ) {
			resultats.innerHTML = '<p class="er-r-aucun">' + echapper( format( M.aucun, d.requete ) ) + '</p>';
			return;
		}
		var montre = function ( nom ) {
			return ( g[ nom ] || [] ).length && dansLaMaison( nom ) && ( portee === 'tout' || portee === nom || ( portee === 'livres' && nom === 'rayons' ) );
		};
		var gauche = '';
		var droite = '';
		ORDRE.forEach( function ( nom ) {
			if ( ! montre( nom ) ) {
				return;
			}
			var liste = g[ nom ];
			var html;
			if ( 'livres' === nom ) {
				var plus = c.livres > 1 ? '<a class="er-r-tout" href="' + echapper( d.livres ) + '">' + echapper( format( M.tous_livres, c.livres ) ) + FLECHE + '</a>' : '';
				html = groupe( nom, '<ul class="er-r-livres">' + liste.slice( 0, portee === 'livres' ? 8 : 4 ).map( livre ).join( '' ) + '</ul>', c[ nom ], plus );
				droite += html;
				return;
			}
			if ( 'auteurs' === nom ) {
				html = groupe( nom, '<ul class="er-r-lignes">' + liste.map( function ( a, i ) {
					return auteur( a, i === 0 );
				} ).join( '' ) + '</ul>', c[ nom ] );
			} else if ( 'rayons' === nom ) {
				html = groupe( nom, '<ul class="er-r-rayons">' + liste.map( function ( r ) {
					return '<li><a class="er-r-lien" href="' + echapper( r.url ) + '">' + echapper( r.titre ) + '</a></li>';
				} ).join( '' ) + '</ul>', c[ nom ] );
			} else {
				html = groupe( nom, '<ul class="er-r-lignes">' + liste.map( function ( r ) {
					return ligne( r, r.extrait );
				} ).join( '' ) + '</ul>', c[ nom ] );
			}
			if ( ( 'rayons' === nom || 'ymca' === nom ) && droite ) {
				droite += html;
			} else {
				gauche += html;
			}
		} );
		var pied = c.livres > 0 && dansLaMaison( 'livres' )
			? '<div class="er-r-pied"><p>' + echapper( M.entree ) + '</p><a class="er-r-voir" href="' + echapper( d.livres ) + '">' + echapper( c.livres === 1 ? M.voir_livre : format( M.voir_livres, c.livres ) ) + FLECHE + '</a></div>'
			: '';
		resultats.innerHTML = '<div class="er-r-grille' + ( droite && gauche ? '' : ' er-r-grille-seule' ) + '"><div class="er-r-colonne">' + gauche + '</div><div class="er-r-colonne">' + droite + '</div></div>' + pied;
	}

	// Des filtres de livres choisis : rien que les livres qui y répondent (et au texte, s'il y en a un).
	function rendreFiltre( d ) {
		var n = ( d.comptes || {} ).livres || 0;
		var liste = ( d.groupes || {} ).livres || [];
		pour.hidden = true;
		portees.hidden = true;
		if ( ! n ) {
			resultats.innerHTML = '<p class="er-r-aucun">' + echapper( d.requete ? format( M.aucun_texte_filtre, d.requete ) : M.aucun_filtre ) + '</p>';
			return;
		}
		var plus = n > liste.length ? '<a class="er-r-tout" href="' + echapper( d.livres ) + '">' + echapper( format( M.tous_livres, n ) ) + FLECHE + '</a>' : '';
		resultats.innerHTML = '<div class="er-r-grille er-r-grille-seule"><div class="er-r-colonne">'
			+ groupe( 'livres', '<ul class="er-r-livres">' + liste.map( livre ).join( '' ) + '</ul>', n, plus ) + '</div></div>'
			+ '<div class="er-r-pied"><p>' + echapper( M.entree ) + '</p><a class="er-r-voir" href="' + echapper( d.livres ) + '">' + echapper( n === 1 ? M.voir_livre : format( M.voir_livres, n ) ) + FLECHE + '</a></div>';
	}

	function filtresDeLivres() {
		return LIVRES.some( function ( f ) {
			return filtres[ f ].length;
		} );
	}
	function parametresFiltres( p ) {
		LIVRES.forEach( function ( f ) {
			filtres[ f ].forEach( function ( v ) {
				p.append( 'er_' + f + '[]', v );
			} );
		} );
		return p;
	}

	function chercher() {
		var texte = champ.value.trim();
		effacer.hidden = ! champ.value;
		if ( ! texte && ! filtresDeLivres() ) {
			donnees = null;
			resultats.hidden = true;
			portees.hidden = true;
			pour.hidden = true;
			pistes.hidden = ! detaillee.hidden;
			return;
		}
		var numero = ++demande;
		api( 'chercher', parametresFiltres( new URLSearchParams( { q: texte } ) ) ).then( function ( d ) {
			if ( numero !== demande ) {
				return; // Une réponse plus ancienne que la dernière lettre tapée.
			}
			donnees = d;
			if ( ! detaillee.hidden ) {
				basculer( false );
			}
			pistes.hidden = true;
			resultats.hidden = false;
			rendre();
		} ).catch( function () {} );
	}
	champ.addEventListener( 'input', function () {
		window.clearTimeout( minuterie );
		minuterie = window.setTimeout( chercher, 160 );
	} );
	effacer.addEventListener( 'click', function () {
		champ.value = '';
		portee = 'tout';
		chercher();
		champ.focus();
	} );
	formulaire.addEventListener( 'submit', function ( e ) {
		var actif = resultats.querySelector( '.er-r-lien.est-choisi' );
		if ( actif ) {
			e.preventDefault();
			actif.click();
			return;
		}
		if ( ! champ.value.trim() ) {
			e.preventDefault();
			if ( donnees && donnees.filtre && donnees.livres ) {
				window.location.href = donnees.livres; // Des filtres sans texte : la page des livres filtrés.
			}
			return;
		}
		garderRecente( champ.value );
		// Les filtres partent avec le texte ; hors de la librairie, on cherche dans tout le site.
		formulaire.querySelectorAll( '.er-filtre-cache' ).forEach( function ( el ) {
			el.remove();
		} );
		postType.disabled = 'ymca' === filtres.maison || 'centre' === filtres.maison;
		parametresFiltres( new URLSearchParams() ).forEach( function ( v, k ) {
			var cache = document.createElement( 'input' );
			cache.type = 'hidden';
			cache.className = 'er-filtre-cache';
			cache.name = k;
			cache.value = v;
			formulaire.appendChild( cache );
		} );
	} );
	portees.addEventListener( 'click', function ( e ) {
		var bouton = e.target.closest( '[data-er-portee]' );
		if ( bouton ) {
			portee = bouton.getAttribute( 'data-er-portee' );
			rendre();
		}
	} );
	panneau.addEventListener( 'click', function ( e ) {
		var proposition = e.target.closest( '.er-recherche-proposition' );
		if ( proposition ) {
			e.preventDefault();
			champ.value = proposition.getAttribute( 'data-er-texte' );
			portee = 'tout';
			chercher();
			champ.focus();
			return;
		}
		var lien = e.target.closest( '.er-recherche-resultats a' );
		if ( lien ) {
			garderRecente( champ.value );
		}
	} );

	/* Les filtres ----------------------------------------------------------------------------------------------- */

	function montrerFiltres() {
		cadreFiltres.querySelectorAll( '.er-filtre' ).forEach( function ( groupeFiltre ) {
			var nom = groupeFiltre.getAttribute( 'data-er-filtre' );
			groupeFiltre.querySelectorAll( '.er-choix' ).forEach( function ( choix ) {
				var v = choix.getAttribute( 'data-er-valeur' );
				var presse = 'maison' === nom ? filtres.maison === v : filtres[ nom ].indexOf( v ) > -1;
				choix.setAttribute( 'aria-pressed', presse ? 'true' : 'false' );
				choix.classList.toggle( 'est-active', presse );
			} );
		} );
		// Les filtres des livres n'ont pas de sens chez YMCA-Press ni au centre.
		filtresLivres.hidden = 'ymca' === filtres.maison || 'centre' === filtres.maison;
		var n = ( 'tout' !== filtres.maison ? 1 : 0 ) + LIVRES.reduce( function ( somme, f ) {
			return somme + filtres[ f ].length;
		}, 0 );
		effacerFiltres.hidden = ! n;
		nombreFiltres.hidden = ! n;
		nombreFiltres.textContent = n;
		champ.placeholder = M[ 'champ_' + filtres.maison ] || M.champ_tout;
	}
	cadreFiltres.addEventListener( 'click', function ( e ) {
		var choix = e.target.closest( '.er-choix' );
		if ( ! choix ) {
			return;
		}
		var nom = choix.closest( '.er-filtre' ).getAttribute( 'data-er-filtre' );
		var v = choix.getAttribute( 'data-er-valeur' );
		if ( 'maison' === nom ) {
			filtres.maison = v;
			if ( 'ymca' === v || 'centre' === v ) {
				LIVRES.forEach( function ( f ) {
					filtres[ f ] = [];
				} );
			}
		} else {
			var i = filtres[ nom ].indexOf( v );
			if ( i > -1 ) {
				filtres[ nom ].splice( i, 1 );
			} else {
				filtres[ nom ].push( v );
				filtres.maison = 'librairie'; // Un rayon, une langue, un état : on cherche des livres, à la librairie.
			}
		}
		portee = 'tout';
		montrerFiltres();
		if ( ! detaillee.hidden ) {
			basculer( false );
		}
		chercher();
	} );
	effacerFiltres.addEventListener( 'click', function () {
		filtres = { maison: 'tout', rayon: [], langue: [], etat: [] };
		portee = 'tout';
		montrerFiltres();
		chercher();
		champ.focus();
	} );
	// Au téléphone, les filtres se replient sous un bouton.
	filtrer.addEventListener( 'click', function () {
		var deplie = ! cadreFiltres.classList.contains( 'est-deplie' );
		cadreFiltres.classList.toggle( 'est-deplie', deplie );
		filtrer.setAttribute( 'aria-expanded', deplie ? 'true' : 'false' );
	} );
	// La recherche détaillée reprend les filtres choisis, s'il y en a.
	function reprendreFiltres() {
		if ( ! filtresDeLivres() ) {
			return;
		}
		[ 'langue', 'etat' ].forEach( function ( f ) {
			detaillee.querySelectorAll( 'input[name="er_' + f + '[]"]' ).forEach( function ( caseACocher ) {
				caseACocher.checked = filtres[ f ].indexOf( caseACocher.value ) > -1;
			} );
		} );
		var choixRayon = detaillee.querySelector( 'select[name="er_rayon[]"]' );
		if ( choixRayon ) {
			choixRayon.value = filtres.rayon[ 0 ] || '';
		}
	}
	montrerFiltres();

	/* Le clavier ------------------------------------------------------------------------------------------------- */

	function liens() {
		var zone = ! resultats.hidden ? resultats : ( ! pistes.hidden ? pistes : null );
		return zone ? Array.prototype.slice.call( zone.querySelectorAll( '.er-r-lien' ) ) : [];
	}
	panneau.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			e.preventDefault();
			fermer();
			return;
		}
		if ( 'ArrowDown' !== e.key && 'ArrowUp' !== e.key ) {
			if ( 'Tab' === e.key ) {
				// La main reste dans le panneau.
				var focusables = Array.prototype.slice.call( panneau.querySelectorAll( 'a[href], button:not([hidden]), input:not([type="hidden"]), select' ) ).filter( function ( el ) {
					return el.offsetParent !== null;
				} );
				var premier = focusables[ 0 ];
				var dernierEl = focusables[ focusables.length - 1 ];
				if ( e.shiftKey && document.activeElement === premier ) {
					e.preventDefault();
					dernierEl.focus();
				} else if ( ! e.shiftKey && document.activeElement === dernierEl ) {
					e.preventDefault();
					premier.focus();
				}
			}
			return;
		}
		if ( e.target.closest( '.er-recherche-detaillee' ) ) {
			return;
		}
		var l = liens();
		if ( ! l.length ) {
			return;
		}
		e.preventDefault();
		var i = l.indexOf( document.activeElement );
		if ( 'ArrowDown' === e.key ) {
			i = i < 0 ? 0 : Math.min( l.length - 1, i + 1 );
		} else {
			i = i <= 0 ? -1 : i - 1;
		}
		if ( i < 0 ) {
			champ.focus();
		} else {
			l[ i ].focus();
		}
	} );

	/* La recherche détaillée ------------------------------------------------------------------------------------- */

	function basculer( montrer ) {
		detaillee.hidden = ! montrer;
		corps.hidden = montrer;
		bascule.setAttribute( 'aria-expanded', montrer ? 'true' : 'false' );
		bascule.classList.toggle( 'est-active', montrer );
		if ( montrer ) {
			reprendreFiltres();
			pistes.hidden = true;
			resultats.hidden = true;
			portees.hidden = true;
			pour.hidden = true;
			compter();
		} else if ( ! champ.value.trim() && ! filtresDeLivres() ) {
			pistes.hidden = false;
		} else if ( donnees ) {
			resultats.hidden = false;
			portees.hidden = false;
			pour.hidden = ! donnees.pour;
		}
	}
	bascule.addEventListener( 'click', function () {
		basculer( detaillee.hidden );
	} );

	function valeurs() {
		var p = new URLSearchParams();
		new FormData( detaillee ).forEach( function ( v, k ) {
			if ( String( v ).trim() !== '' ) {
				p.append( k, v );
			}
		} );
		return p;
	}
	function libelleChamp( el ) {
		var c = el.closest( '.er-rd-champ' );
		var l = c ? c.querySelector( '.er-rd-libelle' ) : null;
		return l ? l.textContent.trim() : '';
	}
	function montrerCriteres() {
		var pastilles = [];
		detaillee.querySelectorAll( 'input, select' ).forEach( function ( el ) {
			var texte = '';
			if ( 'checkbox' === el.type ) {
				if ( el.checked ) {
					var etiquette = el.parentNode.textContent.trim();
					texte = etiquette;
				}
			} else if ( 'SELECT' === el.tagName ) {
				if ( el.value ) {
					texte = el.options[ el.selectedIndex ].textContent.trim();
				}
			} else if ( el.value.trim() ) {
				texte = ( /_(de|a)$/.test( el.name ) ? libelleChamp( el ) + ( /_de$/.test( el.name ) ? ' ≥ ' : ' ≤ ' ) : '' ) + el.value.trim();
			}
			if ( texte ) {
				pastilles.push( '<button type="button" class="er-rd-pastille" data-er-champ="' + echapper( el.name ) + '" data-er-valeur="' + echapper( el.value ) + '" aria-label="' + echapper( format( M.retirer, texte ) ) + '">' + echapper( texte ) + CROIX + '</button>' );
			}
		} );
		criteres.innerHTML = pastilles.length ? '<span class="er-rd-criteres-titre">' + echapper( M.criteres ) + '</span>' + pastilles.join( '' ) : '';
	}
	function compter() {
		montrerCriteres();
		window.clearTimeout( compteur );
		compteur = window.setTimeout( function () {
			api( 'livres', valeurs() ).then( function ( d ) {
				voir.textContent = d.nombre === 0 ? M.voir_aucun : ( d.nombre === 1 ? M.voir_livre : format( M.voir_livres, d.nombre ) );
				voir.disabled = d.nombre === 0;
			} ).catch( function () {} );
		}, 180 );
	}
	detaillee.addEventListener( 'input', compter );
	detaillee.addEventListener( 'change', compter );
	detaillee.addEventListener( 'reset', function () {
		window.setTimeout( compter, 0 );
	} );
	criteres.addEventListener( 'click', function ( e ) {
		var pastille = e.target.closest( '.er-rd-pastille' );
		if ( ! pastille ) {
			return;
		}
		var nom = pastille.getAttribute( 'data-er-champ' );
		var valeur = pastille.getAttribute( 'data-er-valeur' );
		detaillee.querySelectorAll( '[name="' + nom.replace( /"/g, '' ) + '"]' ).forEach( function ( el ) {
			if ( 'checkbox' === el.type ) {
				if ( el.value === valeur ) {
					el.checked = false;
				}
			} else {
				el.value = '';
			}
		} );
		compter();
	} );
	detaillee.addEventListener( 'submit', function () {
		// Les champs vides ne vont pas dans l'adresse.
		detaillee.querySelectorAll( 'input, select' ).forEach( function ( el ) {
			if ( 'checkbox' !== el.type && ! String( el.value ).trim() ) {
				el.disabled = true;
			}
		} );
		window.setTimeout( function () {
			detaillee.querySelectorAll( ':disabled' ).forEach( function ( el ) {
				el.disabled = false;
			} );
		}, 0 );
	} );

}() );
