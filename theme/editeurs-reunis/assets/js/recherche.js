/**
 * Le panneau de recherche : la loupe de l'en-tête l'ouvre (et devient « Fermer »), Échap ou un clic sur le voile le
 * referme. Avant de taper, des pistes ; en tapant, les résultats de l'API de la librairie, en trois colonnes calées sur
 * le triptyque (AE2a) : l'auteur et les rayons, puis quatre livres. La recherche détaillée compte les livres à chaque
 * choix. Les flèches passent d'un résultat à l'autre.
 *
 * Au téléphone (AF1) : la recherche du menu l'ouvre en plein écran, le champ en haut, le clavier ouvert tout de suite ;
 * le panneau suit ce qu'on voit au-dessus du clavier (visualViewport) ; faire défiler les résultats range le clavier.
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
	var pistes = panneau.querySelector( '.er-recherche-pistes' );
	var resultats = panneau.querySelector( '.er-recherche-resultats' );
	var detaillee = panneau.querySelector( '.er-recherche-detaillee' );
	var recentes = panneau.querySelector( '.er-recherche-recentes' );
	var voir = detaillee.querySelector( '.er-rd-voir' );
	var criteres = detaillee.querySelector( '.er-rd-criteres' );
	var corps = panneau.querySelector( '.er-recherche-corps' );
	var rayonsPistes = panneau.querySelector( '.er-recherche-rayons' );
	// Où chercher : les maisons en capitales, au-dessus du champ (le rayon, la langue et l'état sont dans la recherche
	// détaillée ; les filtres de livres restent vides ici).
	var cadreFiltres = panneau.querySelector( '.er-recherche-maisons' );
	var postType = formulaire.querySelector( 'input[name="post_type"]' );

	var telephone = window.matchMedia( '(max-width: 781px)' );
	var ouvert = false;
	var dernier = null; // L'élément qui avait la main avant l'ouverture.
	var donnees = null;
	// Les filtres de la colonne de gauche : où chercher (une maison), puis, pour les livres, rayons, langues, états.
	var filtres = { maison: 'tout', rayon: [], langue: [], etat: [] };
	var LIVRES = [ 'rayon', 'langue', 'etat' ];
	var minuterie;
	var compteur;
	var demande = 0;

	var CROIX = '<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';

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
		// Sans recherche gardée, les rayons à la place.
		if ( rayonsPistes ) {
			rayonsPistes.hidden = !! liste.length;
		}
		recentes.querySelector( 'ul' ).innerHTML = liste.map( function ( t ) {
			return '<li><a class="er-r-lien er-recherche-proposition" href="#" data-er-texte="' + echapper( t ) + '">' + echapper( t ) + '</a></li>';
		} ).join( '' );
	}

	/* Ouvrir, fermer --------------------------------------------------------------------------------------------- */

	// Le panneau s'ouvre là où l'on est, sous ce qu'on voit de l'en-tête : la barre noire en haut de la page ; la
	// ligne de la maison quand l'en-tête est accroché (sa loupe reste là, pour refermer).
	function placer() {
		var haut = document.querySelector( 'header.er-haut' );
		var repere = haut && haut.classList.contains( 'est-accroche' ) ? haut.querySelector( '.er-ligne:not([hidden])' ) : null;
		repere = repere || document.querySelector( '.er-barre' );
		var bas = repere ? Math.max( 0, repere.getBoundingClientRect().bottom ) : 0;
		panneau.style.setProperty( '--er-recherche-haut', bas + 'px' );
	}
	// Au téléphone, le panneau prend exactement ce qu'on voit : le clavier de l'iPhone ne rétrécit pas la page, il la
	// cache, et Safari la fait glisser pour montrer le champ ; sans cela, la page apparaissait sous le panneau.
	function caler() {
		var vv = window.visualViewport;
		if ( ! vv || ! telephone.matches ) {
			panneau.style.removeProperty( '--er-vv-haut' );
			panneau.style.removeProperty( '--er-vv-dy' );
			return;
		}
		panneau.style.setProperty( '--er-vv-haut', Math.round( vv.height ) + 'px' );
		panneau.style.setProperty( '--er-vv-dy', Math.round( vv.offsetTop ) + 'px' );
	}
	function ouvrir( detail ) {
		if ( ouvert ) {
			return;
		}
		ouvert = true;
		dernier = document.activeElement;
		placer();
		caler();
		var court = champ.getAttribute( 'data-er-court' );
		if ( court ) {
			if ( ! champ.hasAttribute( 'data-er-long' ) ) {
				champ.setAttribute( 'data-er-long', champ.placeholder );
			}
			champ.placeholder = telephone.matches ? court : champ.getAttribute( 'data-er-long' );
		}
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
			window.setTimeout( function () {
				detaillee.querySelector( 'input, select' ).focus();
			}, 30 );
			return;
		}
		// Tout de suite, dans le geste même : sinon l'iPhone n'ouvre pas le clavier.
		champ.focus( { preventScroll: true } );
	}
	function fermer( sansRendre ) {
		if ( ! ouvert ) {
			return;
		}
		if ( ! detaillee.hidden ) {
			basculer( false );
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
		if ( ! sansRendre && dernier && dernier.focus && document.contains( dernier ) ) {
			dernier.focus();
		}
	}
	// Un nom du menu cliqué pendant la recherche ouvre son menu tout de suite : la recherche se retire (elle le
	// couvrirait sinon) ; menus.js ouvre le menu (André, 27 nov.).
	document.addEventListener( 'click', function ( e ) {
		var nom = ouvert ? e.target.closest( '[data-er-em]' ) : null;
		// Le menu ouvert pose aussi data-er-em sur <html> (menus.js) : ce n'est pas un nom (sinon, au téléphone, tout
		// clic dans la recherche ouverte depuis le menu la refermait).
		if ( nom && nom !== document.documentElement && ! panneau.contains( e.target ) ) {
			fermer( true );
		}
	}, true );

	// La recherche du menu du téléphone (un lien qui a l'air d'un champ) ouvre le panneau en plein écran (AF1).
	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-er-recherche-ouvrir]' ) ) {
			e.preventDefault();
			ouvrir();
		}
	} );
	if ( window.visualViewport ) {
		[ 'resize', 'scroll' ].forEach( function ( type ) {
			window.visualViewport.addEventListener( type, function () {
				if ( ouvert ) {
					caler();
				}
			} );
		} );
	}
	// Au téléphone, faire défiler les résultats range le clavier : on voit tout.
	corps.addEventListener( 'touchmove', function () {
		if ( telephone.matches && document.activeElement === champ ) {
			champ.blur();
		}
	}, { passive: true } );

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
	// Échap referme aussi quand la main n'est plus dans le panneau (le panneau a son Échap plus bas).
	document.addEventListener( 'keydown', function ( e ) {
		if ( ouvert && 'Escape' === e.key && ! e.defaultPrevented ) {
			e.preventDefault();
			if ( ! detaillee.hidden ) {
				basculer( false );
				bascule.focus();
				return;
			}
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

	var NOMS = [ 'auteurs', 'rayons', 'encyclopedie', 'ymca', 'centre', 'journal', 'pages' ];
	var MAISON = { auteurs: 'librairie', livres: 'librairie', rayons: 'librairie', encyclopedie: 'librairie', ymca: 'ymca', centre: 'centre', journal: 'centre', pages: '' };

	function langue( t ) {
		return /[Ѐ-ӿ]/.test( String( t || '' ) ) ? ' lang="ru"' : '';
	}
	function libelle( t ) {
		return '<p class="er-recherche-libelle">' + echapper( t ) + '</p>';
	}
	// Une ligne : le nom, et dessous, en gris, ce qui le situe s'il le faut.
	function ligne( r, sous ) {
		return '<li><a class="er-r-lien er-r-ligne" href="' + echapper( r.url ) + '">'
			+ '<span class="er-r-titre"' + langue( r.titre ) + '>' + echapper( r.titre ) + '</span>'
			+ ( sous ? '<span class="er-r-sous"' + langue( sous ) + '>' + echapper( sous ) + '</span>' : '' ) + '</a></li>';
	}
	// Un livre a toujours le même dessin (principe 13, AE2) : la couverture au centre du cadre beige, le titre, et en
	// gris le prix, précédé de l'auteur quand la recherche ne l'a pas reconnu (sinon, il serait sous chaque livre).
	function livre( l, avecAuteur ) {
		var couverture = l.image ? '<img src="' + echapper( l.image ) + '" alt="" loading="lazy">' : '<span class="er-r-couverture-vide" aria-hidden="true"><span>' + echapper( l.titre ) + '</span></span>';
		// Le prix, ou « épuisé » à sa place, ne se coupe jamais ; l'auteur se coupe s'il le faut, sur un cadre étroit.
		var qui = avecAuteur ? l.auteurs : '';
		var prix = l.stock ? l.prix : ( qui ? M.rupture : M.rupture.charAt( 0 ).toUpperCase() + M.rupture.slice( 1 ) );
		var sous = ( qui ? '<span class="er-r-vue-coupe"' + langue( qui ) + '>' + echapper( qui ) + '</span>' : '' )
			+ ( prix ? '<span class="er-r-vue-prix">' + ( qui ? '\u00a0· ' : '' ) + echapper( prix ) + '</span>' : '' );
		return '<li><a class="er-r-lien er-r-vue" href="' + echapper( l.url ) + '"><span class="er-r-vue-cadre">' + couverture + '</span>'
			+ '<span class="er-r-vue-texte"><span class="er-r-vue-titre"' + langue( l.titre ) + '>' + echapper( l.titre ) + '</span>'
			+ ( sous ? '<span class="er-r-vue-sous">' + sous + '</span>' : '' ) + '</span></a></li>';
	}
	// Quatre livres sur les deux dernières colonnes du triptyque, deux par colonne (AE2a) : chaque cadre commence ou
	// finit avec sa colonne, 12 px entre deux.
	function livres( liste, avecAuteur ) {
		return [ liste.slice( 0, 2 ), liste.slice( 2, 4 ) ].map( function ( deux ) {
			return '<div class="er-r-colonne"><ul class="er-r-livres">' + deux.map( function ( l ) {
				return livre( l, avecAuteur );
			} ).join( '' ) + '</ul></div>';
		} ).join( '' );
	}
	function groupe( nom, contenu, titre ) {
		return '<section class="er-r-groupe er-r-groupe-' + nom + '">' + libelle( titre || M[ 'g_' + nom ] ) + contenu + '</section>';
	}
	// Des noms, rien d'autre (d'après la recherche des Harvard University Press) ; le premier auteur garde, en gris, son
	// nom russe et ses années. Sans livres, le reste du site dit aussi ce qui le situe (une date, un extrait).
	function noms( nom, liste, avecSous ) {
		var vus = liste.slice( 0, 'auteurs' === nom ? 2 : 3 );
		return groupe( nom, '<ul class="er-r-lignes">' + vus.map( function ( r, i ) {
			var sous = '';
			if ( 'auteurs' === nom ) {
				sous = 0 === i ? [ r.russe, r.annees ].filter( Boolean ).join( ' · ' ) : '';
			} else if ( avecSous && 'rayons' !== nom ) {
				sous = r.extrait || '';
			}
			return ligne( r, sous );
		} ).join( '' ) + '</ul>', 'auteurs' === nom && vus.length > 1 ? M.auteurs : '' );
	}
	// « Tous les résultats » : la page où mène Entrée (les livres trouvés ; hors de la librairie, tout le site).
	function tousLesResultats( d ) {
		var url = d.livres;
		if ( 'ymca' === filtres.maison || 'centre' === filtres.maison ) {
			url = formulaire.action + ( formulaire.action.indexOf( '?' ) > -1 ? '&' : '?' ) + 's=' + encodeURIComponent( d.requete );
		}
		return '<a class="er-r-lien er-r-tous" href="' + echapper( url ) + '">' + echapper( M.tous_resultats ) + ' →</a>';
	}

	function dansLaMaison( nom ) {
		return 'tout' === filtres.maison || MAISON[ nom ] === filtres.maison;
	}
	// Les maisons restent au-dessus du champ quand on tape ; seule la recherche détaillée les remplace.
	function montrerMaisons() {
		cadreFiltres.hidden = ! detaillee.hidden;
	}
	function aucun( texte ) {
		resultats.innerHTML = '<div class="er-grille-tpq"><p class="er-r-aucun">' + echapper( texte ) + '</p></div>';
	}
	// En tapant (AE2a, retenu le 27 nov.) : sur la première colonne, l'auteur et les rayons, des noms seulement, et
	// « Tous les résultats » ; sur les deux autres, quatre livres. Tout tient dans le panneau, sans défiler.
	function rendre() {
		var d = donnees;
		if ( ! d ) {
			return;
		}
		montrerMaisons();
		if ( d.filtre ) {
			rendreFiltre( d );
			return;
		}
		var g = d.groupes || {};
		var montre = function ( nom ) {
			return ( g[ nom ] || [] ).length && dansLaMaison( nom );
		};
		var aLivres = montre( 'livres' );
		var groupes = NOMS.filter( montre );
		if ( ! aLivres && ! groupes.length ) {
			aucun( format( M.aucun, d.requete ) );
			return;
		}
		var gauche = groupes.slice( 0, 2 ).map( function ( nom ) {
			return noms( nom, g[ nom ], false );
		} ).join( '' ) + tousLesResultats( d );
		var droite;
		if ( aLivres ) {
			droite = livres( g.livres.slice( 0, 4 ), ! d.pour );
		} else {
			// Sans livre, le reste du site prend les deux dernières colonnes.
			var reste = groupes.slice( 2 );
			droite = [ 0, 1 ].map( function ( k ) {
				return '<div class="er-r-colonne">' + reste.filter( function ( nom, i ) {
					return i % 2 === k;
				} ).map( function ( nom ) {
					return noms( nom, g[ nom ], true );
				} ).join( '' ) + '</div>';
			} ).join( '' );
		}
		resultats.innerHTML = '<div class="er-grille-tpq er-r-grille"><div class="er-r-colonne">' + gauche + '</div>' + droite + '</div>';
	}

	// Des filtres de livres choisis : rien que les livres qui y répondent (et au texte, s'il y en a un), du même dessin.
	function rendreFiltre( d ) {
		var n = ( d.comptes || {} ).livres || 0;
		var liste = ( d.groupes || {} ).livres || [];
		if ( ! n ) {
			aucun( d.requete ? format( M.aucun_texte_filtre, d.requete ) : M.aucun_filtre );
			return;
		}
		var tous = '<a class="er-r-lien er-r-tous" href="' + echapper( d.livres ) + '">' + echapper( n === 1 ? M.voir_livre : format( M.voir_livres, n ) ) + ' →</a>';
		resultats.innerHTML = '<div class="er-grille-tpq er-r-grille"><div class="er-r-colonne">' + libelle( M.g_livres ) + tous + '</div>'
			+ livres( liste.slice( 0, 4 ), true ) + '</div>';
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
			montrerMaisons();
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
	panneau.addEventListener( 'click', function ( e ) {
		var proposition = e.target.closest( '.er-recherche-proposition' );
		if ( proposition ) {
			e.preventDefault();
			champ.value = proposition.getAttribute( 'data-er-texte' );
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
		[ cadreFiltres ].forEach( function ( groupeFiltre ) {
			var nom = groupeFiltre.getAttribute( 'data-er-filtre' );
			groupeFiltre.querySelectorAll( '.er-choix' ).forEach( function ( choix ) {
				var v = choix.getAttribute( 'data-er-valeur' );
				var presse = 'maison' === nom ? filtres.maison === v : filtres[ nom ].indexOf( v ) > -1;
				choix.setAttribute( 'aria-pressed', presse ? 'true' : 'false' );
				choix.classList.toggle( 'est-active', presse );
			} );
		} );
		champ.placeholder = M[ 'champ_' + filtres.maison ] || M.champ_tout;
	}
	cadreFiltres.addEventListener( 'click', function ( e ) {
		var choix = e.target.closest( '.er-choix' );
		if ( ! choix ) {
			return;
		}
		var nom = cadreFiltres.getAttribute( 'data-er-filtre' );
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
		montrerFiltres();
		if ( ! detaillee.hidden ) {
			basculer( false );
		}
		chercher();
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
			if ( ! detaillee.hidden ) {
				basculer( false );
				bascule.focus();
				return;
			}
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

	// La recherche détaillée : le panneau s'agrandit jusqu'au bas de l'écran, sous l'en-tête. Le même bouton, au même
	// endroit, l'ouvre et la referme (un principe : on et off au même endroit) ; Échap ramène aussi au panneau.
	function basculer( montrer ) {
		detaillee.hidden = ! montrer;
		document.documentElement.classList.toggle( 'er-rd-ouverte', montrer );
		bascule.innerHTML = montrer
			? '<span aria-hidden="true">←</span>\u00a0' + echapper( M.simple )
			: echapper( M.detaillee ) + '\u00a0<span aria-hidden="true">→</span>';
		corps.hidden = montrer;
		bascule.setAttribute( 'aria-expanded', montrer ? 'true' : 'false' );
		bascule.classList.toggle( 'est-active', montrer );
		if ( montrer ) {
			reprendreFiltres();
			pistes.hidden = true;
			resultats.hidden = true;
			cadreFiltres.hidden = true;
			compter();
		} else if ( ! champ.value.trim() && ! filtresDeLivres() ) {
			pistes.hidden = false;
			montrerMaisons();
		} else if ( donnees ) {
			resultats.hidden = false;
			montrerMaisons();
		} else {
			montrerMaisons();
		}
	}
	bascule.addEventListener( 'click', function () {
		var montrer = detaillee.hidden;
		basculer( montrer );
		if ( montrer ) {
			window.setTimeout( function () {
				detaillee.querySelector( 'input, select' ).focus();
			}, 30 );
		}
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
