/**
 * La une de l'accueil, écran par écran (patterns/une-ecrans.php) : les sujets défilent sur un seul écran, de droite à
 * gauche. Le trait qui avance au-dessus du sujet en cours, dans le sommaire, donne la mesure : quand son animation
 * finit, le sujet suivant vient (style.css, « er-une-avance » ; sept secondes). Un clic sur un sujet du sommaire y
 * mène ; le bouton arrête et relance le défilement ; au téléphone, on fait glisser du doigt. Le défilement attend
 * pendant qu'on parcourt le module au clavier, et ne part pas de lui-même si le système demande moins d'animations.
 * Sans ce script, les sujets se suivent l'un sous l'autre.
 */
( function () {
	const module = document.querySelector( '.er-ecrans' );
	const sommaire = module && module.querySelector( '.er-ecrans-sommaire' );
	if ( ! sommaire ) {
		return;
	}
	const ecrans = Array.prototype.slice.call( module.querySelectorAll( ':scope > .er-ecran' ) );
	const liens = Array.prototype.slice.call( sommaire.querySelectorAll( 'a' ) );
	const pause = sommaire.querySelector( '.er-ecrans-pause' );
	const textes = window.erUneEcransTextes || { pause: 'Pause', reprendre: 'Reprendre' };
	if ( ecrans.length < 2 ) {
		return;
	}
	let ici = 0;

	// Au départ, chaque écran prend sa place sans glisser (sinon les suivants traverseraient le premier).
	ecrans.forEach( function ( e, rang ) {
		e.classList.add( 'sans-glisse' );
		e.classList.toggle( 'est-ici', 0 === rang );
	} );
	module.classList.add( 'est-defilant' );
	void module.offsetWidth;
	ecrans.forEach( function ( e ) {
		e.classList.remove( 'sans-glisse' );
	} );

	function marquer() {
		liens.forEach( function ( l, rang ) {
			l.removeAttribute( 'aria-current' );
			if ( rang === ici ) {
				void l.offsetWidth; // le trait repart de zéro
				l.setAttribute( 'aria-current', 'true' );
			}
		} );
	}

	// Aller à un sujet ; sens : 1, il vient de droite ; -1, de gauche.
	function aller( rang, sens ) {
		rang = ( rang + ecrans.length ) % ecrans.length;
		if ( rang === ici ) {
			marquer();
			return;
		}
		sens = sens || ( rang > ici ? 1 : -1 );
		const vient = ecrans[ rang ];
		const part = ecrans[ ici ];
		vient.classList.add( 'sans-glisse' );
		vient.classList.toggle( 'est-avant', sens < 0 );
		void vient.offsetWidth; // la place de départ, sans glisser
		vient.classList.remove( 'sans-glisse', 'est-avant' );
		vient.classList.add( 'est-ici' );
		part.classList.remove( 'est-ici' );
		part.classList.toggle( 'est-avant', sens > 0 );
		ici = rang;
		marquer();
	}

	// Le trait arrivé au bout : le sujet suivant.
	sommaire.addEventListener( 'animationend', function ( e ) {
		if ( 'er-une-avance' === e.animationName && e.target === liens[ ici ] ) {
			aller( ici + 1, 1 );
		}
	} );

	liens.forEach( function ( l, rang ) {
		l.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			aller( rang );
		} );
	} );

	function arreter( oui ) {
		module.classList.toggle( 'est-en-pause', oui );
		pause.setAttribute( 'aria-pressed', oui ? 'true' : 'false' );
		pause.querySelector( 'span' ).textContent = oui ? textes.reprendre : textes.pause;
	}
	pause.hidden = false;
	pause.addEventListener( 'click', function () {
		arreter( ! module.classList.contains( 'est-en-pause' ) );
	} );
	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		arreter( true );
	}

	// Au téléphone : glisser du doigt vers la gauche, le sujet suivant ; vers la droite, le précédent.
	let depart = null;
	module.addEventListener( 'touchstart', function ( e ) {
		depart = e.touches.length === 1 ? { x: e.touches[ 0 ].clientX, y: e.touches[ 0 ].clientY } : null;
	}, { passive: true } );
	module.addEventListener( 'touchend', function ( e ) {
		if ( ! depart ) {
			return;
		}
		const dx = e.changedTouches[ 0 ].clientX - depart.x;
		const dy = e.changedTouches[ 0 ].clientY - depart.y;
		depart = null;
		if ( Math.abs( dx ) > 48 && Math.abs( dx ) > Math.abs( dy ) * 1.5 ) {
			aller( ici + ( dx < 0 ? 1 : -1 ), dx < 0 ? 1 : -1 );
		}
	}, { passive: true } );
}() );
