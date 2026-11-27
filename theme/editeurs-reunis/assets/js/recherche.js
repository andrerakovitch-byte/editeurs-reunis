/**
 * Le panneau de recherche : la loupe de l'en-tête l'ouvre (et devient « Fermer »), Échap ou un clic sur le voile le
 * referme. Avant de taper, des pistes ; en tapant, les résultats de l'API de la librairie, en trois colonnes calées sur
 * le triptyque (AB1a) : l'auteur et les livres, le reste du site, la couverture du livre survolé ; les genres et leurs
 * nombres prennent la place des maisons. La recherche détaillée compte les livres à chaque choix. Les flèches passent
 * d'un résultat à l'autre.
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
	var portees = panneau.querySelector( '.er-recherche-portees' );
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

	var ouvert = false;
	var dernier = null; // L'élément qui avait la main avant l'ouverture.
	var donnees = null;
	var portee = 'tout';
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
	function ouvrir( detail ) {
		if ( ouvert ) {
			return;
		}
		ouvert = true;
		dernier = document.activeElement;
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
		if ( ouvert && e.target.closest( '[data-er-em]' ) ) {
			fermer( true );
		}
	}, true );

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

	var ORDRE = [ 'auteurs', 'livres', 'rayons', 'encyclopedie', 'ymca', 'centre', 'journal', 'pages' ];
	var PORTEES = [ 'tout', 'livres', 'auteurs', 'encyclopedie', 'ymca', 'centre', 'journal' ];
	var MAISON = { auteurs: 'librairie', livres: 'librairie', rayons: 'librairie', encyclopedie: 'librairie', ymca: 'ymca', centre: 'centre', journal: 'centre', pages: '' };

	function langue( t ) {
		return /[\u0400-\u04FF]/.test( String( t || '' ) ) ? ' lang="ru"' : '';
	}
	function libelle( t ) {
		return '<p class="er-recherche-libelle">' + echapper( t ) + '</p>';
	}
	// Une ligne : le titre, et dessous ce qui le situe ; un livre garde son rang pour montrer sa couverture à droite.
	function ligne( r, sous, rang ) {
		return '<li><a class="er-r-lien er-r-ligne" href="' + echapper( r.url ) + '"' + ( rang > -1 ? ' data-er-livre="' + rang + '"' : '' ) + '>'
			+ '<span class="er-r-titre"' + langue( r.titre ) + '>' + echapper( r.titre ) + '</span>'
			+ ( sous ? '<span class="er-r-sous">' + echapper( sous ) + '</span>' : '' ) + '</a></li>';
	}
	function sousLivre( l ) {
		return [ l.mention, l.auteurs, l.prix, l.stock ? '' : M.rupture ].filter( Boolean ).join( ' · ' );
	}
	// La couverture au centre du cadre beige, le titre, l'auteur et le prix dessous (AA1).
	function vue( l ) {
		var couverture = l.image ? '<img src="' + echapper( l.image ) + '" alt="" loading="lazy">' : '<span class="er-r-couverture-vide" aria-hidden="true"><span>' + echapper( l.titre ) + '</span></span>';
		var sous = [ l.auteurs, l.prix ].filter( Boolean ).join( ' · ' );
		return '<a class="er-r-vue" href="' + echapper( l.url ) + '" tabindex="-1"><span class="er-r-vue-cadre">' + couverture + '</span>'
			+ '<span class="er-r-vue-texte"><span class="er-r-vue-titre"' + langue( l.titre ) + '>' + echapper( l.titre ) + '</span>'
			+ ( sous ? '<span class="er-r-vue-sous">' + echapper( sous ) + '</span>' : '' ) + '</span></a>';
	}
	function nombreAuteur( a ) {
		var nombre = a.livres === 1 ? M.un_livre : format( M.n_livres, a.livres );
		// Ses livres, ce sont aussi ceux qu'il a traduits ou dirigés : « 3 livres en rayon : 2 sous sa direction et une traduction ».
		var r = a.roles || {}, dont = [];
		if ( r.direction ) {
			dont.push( r.direction === 1 ? M.une_direction : format( M.n_direction, r.direction ) );
		}
		if ( r.traduction ) {
			dont.push( r.traduction === 1 ? M.une_traduction : format( M.n_traductions, r.traduction ) );
		}
		if ( dont.length ) {
			nombre += r.auteur ? ', ' + format( M.dont, dont.join( ' ' + M.et + ' ' ) ) : ' : ' + dont.join( ' ' + M.et + ' ' );
		}
		if ( ! a.livres && r.autour ) {
			nombre = r.autour === 1 ? M.un_sujet : format( M.n_sujet, r.autour );
		}
		return nombre;
	}
	function auteur( a, premier ) {
		var nombre = nombreAuteur( a );
		if ( ! premier ) {
			return ligne( a, nombre, -1 );
		}
		var page = M.page_auteur.charAt( 0 ).toLowerCase() + M.page_auteur.slice( 1 );
		return ligne( a, ( a.graphies || [] ).concat( [ nombre, page + '\u00a0→' ] ).join( ' · ' ), -1 );
	}
	function groupe( nom, contenu ) {
		return '<section class="er-r-groupe er-r-groupe-' + nom + '">' + libelle( M[ 'g_' + nom ] ) + contenu + '</section>';
	}

	var livresVus = []; // Les livres des résultats, pour la couverture de droite.
	var vueResultats = null;
	function viser( rang ) {
		var l = livresVus[ rang ];
		if ( ! l || ! vueResultats || vueResultats.getAttribute( 'data-er-livre' ) === String( rang ) ) {
			return;
		}
		vueResultats.setAttribute( 'data-er-livre', String( rang ) );
		vueResultats.querySelector( '.er-r-vue-place' ).innerHTML = vue( l );
	}
	resultats.addEventListener( 'mouseover', function ( e ) {
		var l = e.target.closest( '[data-er-livre]' );
		if ( l ) {
			viser( Number( l.getAttribute( 'data-er-livre' ) ) );
		}
	} );
	resultats.addEventListener( 'focusin', function ( e ) {
		var l = e.target.closest( '[data-er-livre]' );
		if ( l ) {
			viser( Number( l.getAttribute( 'data-er-livre' ) ) );
		}
	} );

	function dansLaMaison( nom ) {
		return 'tout' === filtres.maison || MAISON[ nom ] === filtres.maison;
	}
	// Les genres en capitales, à la place des maisons ; l'inverse quand il n'y a plus rien à montrer.
	function montrerGenres( oui ) {
		portees.hidden = ! oui;
		cadreFiltres.hidden = oui || ! detaillee.hidden;
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
		// Le nom reconnu et ses autres écritures ne s'affichent plus (« Résultats pour… Cherché aussi… », retiré le 27 nov.).
		// Les genres, avec leurs nombres, en capitales comme les noms de la barre.
		portees.innerHTML = PORTEES.filter( function ( p ) {
			return p === 'tout' || ( dansLaMaison( p ) && ( p === portee || c[ p ] ) );
		} ).map( function ( p ) {
			return '<button type="button" class="er-portee' + ( p === portee ? ' est-active' : '' ) + '" aria-pressed="' + ( p === portee ) + '" data-er-portee="' + p + '">'
				+ '<span>' + echapper( M[ p ] ) + ' <span class="er-portee-nombre">' + ( c[ p ] || 0 ) + '</span></span></button>';
		} ).join( '' );
		montrerGenres( true );

		livresVus = [];
		vueResultats = null;
		if ( ! c.tout ) {
			resultats.innerHTML = '<div class="er-grille-tpq"><p class="er-r-aucun">' + echapper( format( M.aucun, d.requete ) ) + '</p></div>';
			return;
		}
		var montre = function ( nom ) {
			return ( g[ nom ] || [] ).length && dansLaMaison( nom ) && ( portee === 'tout' || portee === nom || ( portee === 'livres' && nom === 'rayons' ) );
		};
		// À gauche, l'auteur et les livres ; au milieu, le reste du site ; à droite, la couverture du livre visé.
		var gauche = [];
		var milieu = [];
		ORDRE.forEach( function ( nom ) {
			if ( ! montre( nom ) ) {
				return;
			}
			var liste = g[ nom ];
			if ( 'livres' === nom ) {
				livresVus = liste.slice( 0, portee === 'livres' ? 8 : 4 );
				var tous = c.livres > 0 ? '<a class="er-r-lien er-r-tout" href="' + echapper( d.livres ) + '">' + echapper( c.livres === 1 ? M.voir_livre : format( M.voir_livres, c.livres ) ) + '\u00a0→</a>' : '';
				gauche.push( groupe( nom, '<ul class="er-r-lignes">' + livresVus.map( function ( l, i ) {
					return ligne( l, sousLivre( l ), i );
				} ).join( '' ) + '</ul>' ) + tous );
				return;
			}
			var html;
			if ( 'auteurs' === nom ) {
				html = groupe( nom, '<ul class="er-r-lignes">' + liste.map( function ( a, i ) {
					return auteur( a, i === 0 );
				} ).join( '' ) + '</ul>' );
				gauche.unshift( html );
				return;
			}
			html = groupe( nom, '<ul class="er-r-lignes">' + liste.map( function ( r ) {
				return ligne( r, 'rayons' === nom ? '' : r.extrait, -1 );
			} ).join( '' ) + '</ul>' );
			milieu.push( html );
		} );
		// Sans auteur ni livre, le reste du site se partage les deux premières colonnes.
		if ( ! gauche.length && milieu.length > 1 ) {
			gauche = milieu.splice( 0, Math.ceil( milieu.length / 2 ) );
		}
		var droite = livresVus.length
			? libelle( M.le_livre ) + '<div class="er-r-vue-place">' + vue( livresVus[ 0 ] ) + '</div>'
			: '';
		resultats.innerHTML = '<div class="er-grille-tpq er-r-grille"><div class="er-r-colonne">' + gauche.join( '' ) + '</div><div class="er-r-colonne">' + milieu.join( '' )
			+ '</div><div class="er-r-colonne er-r-colonne-vue"' + ( droite ? ' data-er-livre="0"' : '' ) + '>' + droite + '</div></div>';
		vueResultats = droite ? resultats.querySelector( '.er-r-colonne-vue' ) : null;
	}

	// Des filtres de livres choisis : rien que les livres qui y répondent (et au texte, s'il y en a un).
	function rendreFiltre( d ) {
		var n = ( d.comptes || {} ).livres || 0;
		var liste = ( d.groupes || {} ).livres || [];
		montrerGenres( false );
		if ( ! n ) {
			resultats.innerHTML = '<div class="er-grille-tpq"><p class="er-r-aucun">' + echapper( d.requete ? format( M.aucun_texte_filtre, d.requete ) : M.aucun_filtre ) + '</p></div>';
			return;
		}
		livresVus = liste;
		var tous = '<a class="er-r-lien er-r-tout" href="' + echapper( d.livres ) + '">' + echapper( n === 1 ? M.voir_livre : format( M.voir_livres, n ) ) + '\u00a0→</a>';
		resultats.innerHTML = '<div class="er-grille-tpq er-r-grille er-grille-tpq-2"><div class="er-r-colonne">'
			+ groupe( 'livres', '<ul class="er-r-lignes">' + liste.map( function ( l, i ) {
				return ligne( l, sousLivre( l ), i );
			} ).join( '' ) + '</ul>' ) + tous + '</div><div class="er-r-colonne er-r-colonne-vue" data-er-livre="0">'
			+ libelle( M.le_livre ) + '<div class="er-r-vue-place">' + vue( liste[ 0 ] ) + '</div></div></div>';
		vueResultats = resultats.querySelector( '.er-r-colonne-vue' );
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
			montrerGenres( false );
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
		portee = 'tout';
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
			portees.hidden = true;
			cadreFiltres.hidden = true;
			compter();
		} else if ( ! champ.value.trim() && ! filtresDeLivres() ) {
			pistes.hidden = false;
			montrerGenres( false );
		} else if ( donnees ) {
			resultats.hidden = false;
			montrerGenres( ! donnees.filtre );
		} else {
			montrerGenres( false );
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
