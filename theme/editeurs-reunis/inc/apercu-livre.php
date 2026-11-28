<?php
/**
 * L'aperçu d'un livre au survol de son lien dans un texte (retenu le 28 nov. 2026 : B, la carte au-dessus du lien ;
 * le C, dans la marge, était préféré mais plus compliqué). Partout où un texte cite un livre par un lien vers sa
 * fiche : une page, un article du journal, une rencontre, une exposition.
 *
 * Le serveur reconnaît les liens du texte (sans classe : les cartes ont la leur) qui mènent à la fiche d'un livre,
 * leur donne l'identifiant du livre (data-apercu-livre ; data-livre est aux liens du menu Librairie) et pose leurs
 * cartes, cachées, à la fin du contenu : le dessin de la recherche (principe 13), sans prix hors de la librairie.
 * assets/js/apercu-livre.js les montre au survol, avec une souris seulement. Le dessin : assets/css/33-apercu-livre.css.
 *
 * @package editeurs-reunis
 */

/**
 * Les liens vers un livre d'un contenu : leur identifiant, et leurs cartes à la fin.
 *
 * @param string $html Le contenu rendu.
 * @return string
 */
function er_apercu_livres( $html ) {
	if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'er_recherche_vue' ) || false === strpos( $html, '/livre/' ) ) {
		return $html;
	}
	$cartes = array();
	$html   = preg_replace_callback(
		'#<a\s([^>]*?)href="([^"]+)"([^>]*)>#i',
		function ( $m ) use ( &$cartes ) {
			// Un lien du texte n'a pas de classe ; une carte, une ligne, un bouton en ont une.
			if ( preg_match( '/\sclass=|data-apercu-livre/i', ' ' . $m[1] . $m[3] ) ) {
				return $m[0];
			}
			$chemin = (string) wp_parse_url( html_entity_decode( $m[2] ), PHP_URL_PATH );
			if ( ! preg_match( '#/livre/([^/]+)/?$#', $chemin, $s ) ) {
				return $m[0];
			}
			$page  = get_page_by_path( rawurldecode( $s[1] ), OBJECT, 'product' );
			$livre = $page ? wc_get_product( $page->ID ) : null;
			if ( ! $livre || 'publish' !== $livre->get_status() ) {
				return $m[0];
			}
			$cartes[ $livre->get_id() ] = '<div class="er-apercu-modele" data-modele="' . (int) $livre->get_id() . '">' . er_recherche_vue( $livre, false ) . '</div>';
			return '<a ' . $m[1] . 'href="' . $m[2] . '"' . $m[3] . ' data-apercu-livre="' . (int) $livre->get_id() . '">';
		},
		$html
	);
	if ( ! $cartes ) {
		return $html;
	}
	wp_enqueue_script( 'er-apercu-livre', get_theme_file_uri( 'assets/js/apercu-livre.js' ), array(), wp_get_theme()->get( 'Version' ) . '.' . filemtime( get_theme_file_path( 'assets/js/apercu-livre.js' ) ), true );
	return $html . '<div class="er-apercus" hidden>' . implode( '', $cartes ) . '</div>';
}

add_filter(
	'render_block_core/post-content',
	function ( $html ) {
		return is_singular() && ! is_front_page() ? er_apercu_livres( $html ) : $html;
	},
	20
);
