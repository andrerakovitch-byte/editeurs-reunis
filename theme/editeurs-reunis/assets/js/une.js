/**
 * L'accueil, à la une, au téléphone : chaque module montre l'essentiel (le titre sur son aplat, la photo, la phrase,
 * le bouton principal), et le reste se déplie sous « Voir plus ». Sur ordinateur, ou sans JavaScript, tout est montré.
 */
( function () {
	const modules = document.querySelectorAll( '.er-une' );
	if ( ! modules.length ) {
		return;
	}
	const textes = window.erUneTextes || { plus: 'Voir plus', moins: 'Voir moins' };
	const doux = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	modules.forEach( function ( module, rang ) {
		if ( ! module.id ) {
			module.id = 'er-une-' + ( rang + 1 );
		}
		const bouton = document.createElement( 'button' );
		bouton.type = 'button';
		bouton.className = 'er-une-voir-plus';
		bouton.setAttribute( 'aria-expanded', 'false' );
		bouton.setAttribute( 'aria-controls', module.id );
		bouton.textContent = textes.plus;
		bouton.addEventListener( 'click', function () {
			const ouvert = module.classList.toggle( 'er-une-ouvert' );
			bouton.setAttribute( 'aria-expanded', ouvert ? 'true' : 'false' );
			bouton.textContent = ouvert ? textes.moins : textes.plus;
			// Replié, le module raccourcit : on revient à son début, pour ne pas se retrouver plus bas.
			if ( ! ouvert && module.getBoundingClientRect().top < 0 ) {
				module.scrollIntoView( { block: 'start', behavior: doux.matches ? 'auto' : 'smooth' } );
			}
		} );
		module.appendChild( bouton );
		module.classList.add( 'er-une-repliable' );
	} );
}() );
