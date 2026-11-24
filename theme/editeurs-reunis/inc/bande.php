<?php
/**
 * La bande de page : le troisième niveau de la navigation, le même sur tout le site (retenue le 24 nov. 2026, le
 * canevas « plein écran », page « Le troisième niveau »).
 *
 * Sous le titre de la page, une bande de 48 px dans la teinte pâle de la maison (un ton plus bas que la ligne, qui est
 * un ton plus bas que la barre noire) : les parties de la page, ses filtres ou son sommaire. Quand on fait défiler,
 * elle monte avec la page et s'accroche sous la ligne de la maison ; le nom de la page s'y glisse alors à gauche
 * (assets/js/bande.js). Dans un sommaire, le trait suit la lecture.
 *
 * - Une entrée : un lien ; celle où l'on est, à l'encre, soulignée d'un trait de 2 px dans la couleur de la maison.
 * - Un groupe : un titre en petites capitales (« Passés »), après un filet ; un groupe de trop se replie en menu
 *   (« Par époque »), le seul menu déroulant du site.
 * - À droite, une action et une seule, facultative.
 * - Au téléphone : 44 px, sans le nom de la page ni l'action ; la bande défile de côté et garde l'entrée courante en vue.
 * - Une page sans maison (un sujet) : la bande sur le fond doux, avec les trois points, accrochée seule en haut.
 *
 * @package editeurs-reunis
 */

/**
 * La bande d'une page.
 *
 * @param array $a {
 *     @type string $maison   librairie, ymca, centre ; par défaut la maison de la page (aucune : le fond doux).
 *     @type string $nom      Le nom de la page, qui se glisse à gauche quand la bande est accrochée.
 *     @type bool   $sommaire Des ancres dans la page : le trait suit la lecture.
 *     @type array  $entrees  Les entrées, dans l'ordre : array( 'texte', 'lien', 'courant', 'avant' ) ; array( 'groupe' )
 *                            pour un titre de groupe ; array( 'separateur' => true ) ; array( 'menu', 'entrees' ) pour un
 *                            groupe replié.
 *     @type array  $action   array( 'texte', 'lien' ) : l'action à droite.
 *     @type string $libelle  Le nom de la navigation, pour les lecteurs d'écran.
 * }
 * @return string Le HTML, ou rien s'il y a moins de deux entrées.
 */
function er_bande_page( $a ) {
	$a = wp_parse_args(
		$a,
		array(
			'maison'   => function_exists( 'er_maison' ) ? er_maison() : '',
			'nom'      => '',
			'sommaire' => false,
			'entrees'  => array(),
			'action'   => null,
			'libelle'  => '',
		)
	);
	$liens = 0;
	foreach ( $a['entrees'] as $e ) {
		$liens += isset( $e['lien'] ) || isset( $e['menu'] ) ? 1 : 0;
	}
	if ( $liens < 2 ) {
		return '';
	}
	static $n = 0;
	++$n;
	$maison  = in_array( $a['maison'], array( 'librairie', 'ymca', 'centre' ), true ) ? $a['maison'] : 'aucune';
	$chevron = '<svg class="er-bande-chevron" viewBox="0 0 10 6" width="10" height="6" aria-hidden="true" focusable="false"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>';
	$lis     = '';
	$menus   = '';
	foreach ( array_values( $a['entrees'] ) as $i => $e ) {
		if ( ! empty( $e['separateur'] ) ) {
			$lis .= '<li class="er-bande-sep" aria-hidden="true"></li>';
		} elseif ( isset( $e['groupe'] ) ) {
			$lis .= '<li class="er-bande-groupe">' . esc_html( $e['groupe'] ) . '</li>';
		} elseif ( isset( $e['menu'] ) ) {
			if ( empty( $e['entrees'] ) ) {
				continue;
			}
			$id      = 'er-bande-' . $n . '-' . $i;
			$courant = null;
			$sous    = '';
			foreach ( $e['entrees'] as $s ) {
				if ( ! empty( $s['courant'] ) ) {
					$courant = $s;
				}
				$sous .= '<li><a href="' . esc_url( $s['lien'] ) . '"' . ( empty( $s['courant'] ) ? '' : ' aria-current="page"' ) . '>' . esc_html( $s['texte'] ) . '</a></li>';
			}
			/* translators: 1: le nom d'un groupe replié (« Par époque »), 2: l'entrée choisie dans ce groupe */
			$libelle = $courant ? sprintf( _x( '%1$s : %2$s', 'bande de page, un groupe replié et son entrée choisie', 'editeurs-reunis' ), $e['menu'], $courant['texte'] ) : $e['menu'];
			$lis    .= '<li class="er-bande-menu"><button type="button" class="er-bande-lien' . ( $courant ? ' est-courant' : '' ) . '" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '"><span>' . esc_html( $libelle ) . '</span>' . $chevron . '</button></li>';
			$menus  .= '<div class="er-bande-panneau" id="' . esc_attr( $id ) . '" hidden><ul>' . $sous . '</ul></div>';
		} elseif ( isset( $e['lien'] ) ) {
			$courant = empty( $e['courant'] ) ? '' : ' aria-current="' . ( $a['sommaire'] ? 'true' : 'page' ) . '"';
			$avant   = empty( $e['avant'] ) ? '' : '<span class="er-bande-avant">' . esc_html( $e['avant'] ) . '</span>';
			$lis    .= '<li><a class="er-bande-lien" href="' . esc_url( $e['lien'] ) . '"' . $courant . '>' . $avant . '<span>' . esc_html( $e['texte'] ) . '</span></a></li>';
		}
	}
	$points = 'aucune' === $maison
		? '<span class="er-bande-point er-bande-point-librairie"></span><span class="er-bande-point er-bande-point-ymca"></span><span class="er-bande-point er-bande-point-centre"></span>'
		: '<span class="er-bande-point"></span>';
	$nom    = '' !== $a['nom'] ? '<span class="er-bande-nom" aria-hidden="true"><span class="er-bande-points">' . $points . '</span><span class="er-bande-nom-texte">' . esc_html( $a['nom'] ) . '</span></span>' : '';
	$action = ! empty( $a['action']['lien'] ) ? '<a class="er-bande-action" href="' . esc_url( $a['action']['lien'] ) . '">' . esc_html( $a['action']['texte'] ) . '</a>' : '';
	$libelle = '' !== $a['libelle'] ? $a['libelle'] : ( '' !== $a['nom'] ? $a['nom'] : __( 'Dans cette page', 'editeurs-reunis' ) );
	return '<div class="er-bande-repere" aria-hidden="true"></div>'
		. '<nav class="er-bande er-bande-' . $maison . ( $a['sommaire'] ? ' er-bande-sommaire' : '' ) . '" aria-label="' . esc_attr( $libelle ) . '">'
		. '<div class="er-bande-dedans">' . $nom . '<ul class="er-bande-liste">' . $lis . '</ul>' . $action . '</div>' . $menus . '</nav>';
}

/**
 * Les années d'une liste en entrées de bande : les quatre plus récentes, puis les autres repliées sous « Plus anciens ».
 *
 * @param array    $annees  Les années, de la plus récente à la plus ancienne.
 * @param callable $lien    L'adresse d'une année.
 * @param string   $courant L'année choisie.
 * @param string   $replie  Le nom du groupe replié.
 * @param int      $combien Combien restent dépliées.
 */
function er_bande_annees( $annees, $lien, $courant, $replie, $combien = 4 ) {
	$entrees = array();
	$autres  = array();
	foreach ( array_values( $annees ) as $i => $an ) {
		$e = array(
			'texte'   => (string) $an,
			'lien'    => $lien( $an ),
			'courant' => (string) $an === (string) $courant,
		);
		if ( $i < $combien ) {
			$entrees[] = $e;
		} else {
			$autres[] = $e;
		}
	}
	if ( $autres ) {
		$entrees[] = array(
			'menu'    => $replie,
			'entrees' => $autres,
		);
	}
	return $entrees;
}

/**
 * Un identifiant d'ancre pour un intertitre : son texte, sans accents, unique dans la page.
 *
 * @param string $texte Le texte.
 */
function er_bande_ancre( $texte ) {
	static $pris = array();
	$base = sanitize_title( remove_accents( wp_strip_all_tags( html_entity_decode( $texte, ENT_QUOTES, 'UTF-8' ) ) ) );
	$base = '' !== $base ? $base : 'partie';
	$id   = $base;
	$i    = 2;
	while ( isset( $pris[ $id ] ) ) {
		$id = $base . '-' . $i++;
	}
	$pris[ $id ] = true;
	return $id;
}

/**
 * Le sommaire d'un rayon de la librairie : tout le rayon, son premier groupe de sous-rayons déplié (« Par genre »),
 * les autres repliés (« Par époque »). Sur un sous-rayon, la bande de son rayon, l'entrée du sous-rayon marquée.
 *
 * @param WP_Term $terme Le rayon ou le sous-rayon.
 */
function er_bande_rayon( $terme ) {
	if ( ! $terme instanceof WP_Term || 'product_cat' !== $terme->taxonomy ) {
		return '';
	}
	$rayon = $terme->parent ? get_term( $terme->parent, 'product_cat' ) : $terme;
	if ( ! $rayon instanceof WP_Term ) {
		return '';
	}
	$enfants = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $rayon->term_id,
			'hide_empty' => false,
		)
	);
	if ( ! $enfants || is_wp_error( $enfants ) ) {
		return '';
	}
	usort( $enfants, fn( $x, $y ) => (int) get_term_meta( $x->term_id, 'order', true ) <=> (int) get_term_meta( $y->term_id, 'order', true ) );
	$groupes = array();
	foreach ( $enfants as $e ) {
		$g               = (string) get_term_meta( $e->term_id, 'er_groupe', true );
		$lien            = get_term_link( $e );
		$groupes[ $g ][] = array(
			'texte'   => $e->name,
			'lien'    => is_wp_error( $lien ) ? '' : $lien,
			'courant' => (int) $e->term_id === (int) $terme->term_id,
		);
	}
	$entrees = array(
		array(
			/* translators: dans la bande d'un rayon, la première entrée */
			'texte'   => __( 'Tout le rayon', 'editeurs-reunis' ),
			'lien'    => is_wp_error( get_term_link( $rayon ) ) ? '' : get_term_link( $rayon ),
			'courant' => (int) $rayon->term_id === (int) $terme->term_id,
		),
	);
	$premier = true;
	foreach ( $groupes as $nom => $liste ) {
		$entrees[] = array( 'separateur' => true );
		if ( $premier ) {
			if ( '' !== $nom ) {
				$entrees[] = array( 'groupe' => $nom );
			}
			$entrees = array_merge( $entrees, $liste );
			$premier = false;
		} else {
			$entrees[] = array(
				'menu'    => '' !== $nom ? $nom : __( 'Et aussi', 'editeurs-reunis' ),
				'entrees' => $liste,
			);
		}
	}
	return er_bande_page(
		array(
			'maison'  => 'librairie',
			'nom'     => $rayon->name,
			'entrees' => $entrees,
		)
	);
}

/**
 * Le sommaire automatique d'une page de texte : dès qu'elle a trois intertitres (h2), la bande les reprend en ancres,
 * en tête du contenu. Une page qui porte déjà sa bande (la liste des rencontres, des expositions…) garde la sienne.
 * « Chapitre 03 · 1940 – 1945 » juste avant un intertitre donne son année (Un siècle d'histoire).
 */
add_filter(
	'render_block_core/post-content',
	function ( $html, $bloc, $instance = null ) {
		if ( ! is_page() || is_front_page() || false !== strpos( $html, 'er-bande' ) ) {
			return $html;
		}
		if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'is_account_page' ) && is_account_page() ) ) {
			return $html;
		}
		if ( $instance && isset( $instance->context['postId'] ) && (int) $instance->context['postId'] !== (int) get_queried_object_id() ) {
			return $html;
		}
		if ( ! preg_match_all( '#<h2\b([^>]*)>(.*?)</h2>#is', $html, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) || count( $m ) < 3 ) {
			return $html;
		}
		$entrees = array();
		$decalage = 0;
		foreach ( $m as $x ) {
			$attributs = $x[1][0];
			$texte     = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( $x[2][0], ENT_QUOTES, 'UTF-8' ) ) ) );
			if ( '' === $texte ) {
				continue;
			}
			if ( preg_match( '/\bid=["\']([^"\']+)["\']/i', $attributs, $id ) ) {
				$id = $id[1];
			} else {
				$id      = er_bande_ancre( $texte );
				$nouveau = '<h2 id="' . esc_attr( $id ) . '"' . $attributs . '>';
				$avant   = '<h2' . $attributs . '>';
				$pos     = $x[0][1] + $decalage;
				$html    = substr_replace( $html, $nouveau, $pos, strlen( $avant ) );
				$decalage += strlen( $nouveau ) - strlen( $avant );
			}
			// L'année d'un chapitre : le surtitre qui le précède (« Chapitre 03 · 1940 – 1945 »).
			$an  = '';
			$pre = trim( wp_strip_all_tags( substr( $html, max( 0, $x[0][1] + $decalage - 400 ), min( 400, $x[0][1] + $decalage ) ) ) );
			if ( preg_match( '/Chapitre\s+\d+\s*·\s*([^·\n]*)$/u', $pre, $c ) && preg_match( '/\b(1[89]\d\d|20\d\d)\b/', $c[1], $y ) ) {
				$an = $y[1];
			}
			$entrees[] = array(
				'texte'   => $texte,
				'lien'    => '#' . $id,
				'courant' => ! $entrees,
				'avant'   => $an,
			);
		}
		if ( count( $entrees ) < 3 ) {
			return $html;
		}
		$bande = er_bande_page(
			array(
				'nom'      => html_entity_decode( get_the_title( get_queried_object_id() ), ENT_QUOTES, 'UTF-8' ),
				'sommaire' => true,
				'entrees'  => $entrees,
			)
		);
		// La bande en tête du contenu : à l'intérieur de son enveloppe, pour s'accrocher tout le long de la page.
		return preg_replace( '#^(\s*<div\b[^>]*>)#', '$1' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $bande ), $html, 1 );
	},
	10,
	3
);
