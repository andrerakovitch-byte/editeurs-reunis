<?php
/**
 * Les titres liés : dans chaque texte du site, le titre d'un livre de la librairie devient un lien vers sa fiche, et sa
 * vignette paraît au survol (la couverture, la mention, le titre, l'auteur, le prix). Une règle de tout le site, décidée
 * le 24 nov. 2026 : les articles du journal, les pages, l'encyclopédie, les rencontres, les fiches des livres.
 *
 * - Le sommier : les titres des livres en vente (ni les billets des rencontres, ni les livres cachés), avec ceux de leur
 *   champ « Titres liés » (er_titres_lies, un par ligne : « Docteur Jivago », « Доктор Живаго »). Un livre peut être
 *   retiré de la règle (er_sans_lien). Un titre qui commence par un article (le, la, les, l’) se reconnaît aussi sans
 *   lui : « l’auteur du Docteur Jivago ».
 * - Un titre se reconnaît partout dans le texte quand il est assez distinctif : trois mots au moins hors article, ou
 *   deux mots qui portent chacun une majuscule (« Docteur Jivago »). Un titre qui est le nom d'une personne (la
 *   biographie « Ossip Mandelstam ») ne se lie que cité : sinon chaque mention du poète mènerait au livre. Les autres (« Tchevengour », « Les Douze »,
 *   « Tout passe ») seulement quand ils sont cités : seuls en italique, ou entre guillemets « ». Dans tous les cas, le
 *   premier mot garde sa majuscule.
 * - Un livre n'est lié qu'une fois par page, à sa première mention ; jamais dans un lien, un titre (h1 à h6), un bouton,
 *   du code, une légende de vignette, ni sur sa propre fiche.
 * - Deux livres au même titre (neuf et d'occasion, deux éditions) : le lien va à celui qui est en stock, sinon au plus
 *   récent.
 * - Les blocs de texte (paragraphe, élément de liste, citation en exergue, vers, tableau, extrait, contenu classique) y
 *   passent d'eux-mêmes ; un texte rendu hors des blocs (la biographie d'une personne, une description) y passe par
 *   le filtre « er_lier_titres » : apply_filters( 'er_lier_titres', $html ).
 * - wp er liens [<slug>] : le sommier (combien de titres, lesquels se disputent un même titre), ou les titres liés dans
 *   une page, un article, un livre.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les mots d'un titre ou d'un texte, avec leur place (en octets) : [mot, début, fin].
 *
 * @param string $texte Le texte.
 * @return array<int,array{0:string,1:int,2:int}>
 */
function er_liens_mots( $texte ) {
	$mots = array();
	if ( preg_match_all( '/[\p{L}\p{N}]+(?:[’\'\-][\p{L}\p{N}]+)*/u', $texte, $m, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m[0] as list( $mot, $debut ) ) {
			$mots[] = array( $mot, $debut, $debut + strlen( $mot ) );
		}
	}
	return $mots;
}

/**
 * La clé d'une suite de mots : en minuscules, l'apostrophe droite changée en apostrophe typographique.
 *
 * @param string[] $mots Les mots.
 */
function er_liens_cle( $mots ) {
	return mb_strtolower( str_replace( "'", '’', implode( ' ', $mots ) ), 'UTF-8' );
}

/**
 * Un mot est-il un article ? Le premier mot d'un titre peut tomber : « Le Docteur Jivago » se dit « du Docteur Jivago ».
 *
 * @param string $mot Le mot.
 */
function er_liens_article( $mot ) {
	return in_array( mb_strtolower( $mot, 'UTF-8' ), array( 'le', 'la', 'les', 'un', 'une', 'the' ), true );
}

/**
 * Le sommier des titres : clé → [livre, nombre de mots hors article], et le plus long titre en mots. Gardé un jour,
 * refait quand un livre change.
 *
 * @return array{titres: array<string,array{0:int,1:int}>, max: int}
 */
function er_liens_sommier() {
	static $sommier = null;
	if ( null !== $sommier ) {
		return $sommier;
	}
	$garde = get_transient( 'er_liens_sommier' );
	if ( is_array( $garde ) && isset( $garde['titres'] ) ) {
		$sommier = $garde;
		return $sommier;
	}
	$args                 = function_exists( 'er_requete_livres_en_vente' ) ? er_requete_livres_en_vente() : array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);
	$args['meta_query'][] = array(
		'key'     => 'er_sans_lien',
		'compare' => 'NOT EXISTS',
	);
	// Les noms des personnes (et leurs autres graphies) : un titre qui en est un ne se lie que cité.
	$personnes = array();
	$termes    = get_terms(
		array(
			'taxonomy'   => 'auteur',
			'hide_empty' => false,
			'number'     => 5000,
		)
	);
	foreach ( is_wp_error( $termes ) ? array() : $termes as $t ) {
		$noms = array_merge( array( $t->name ), function_exists( 'er_autres_graphies' ) ? er_autres_graphies( $t->term_id ) : array() );
		foreach ( $noms as $nom ) {
			$personnes[ er_liens_cle( wp_list_pluck( er_liens_mots( $nom ), 0 ) ) ] = true;
		}
	}
	$candidats = array();
	foreach ( ( new WP_Query( $args ) )->posts as $id ) {
		$titres = array( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) );
		foreach ( preg_split( '/\R/u', (string) get_post_meta( $id, 'er_titres_lies', true ) ) as $autre ) {
			$titres[] = trim( $autre );
		}
		$en_stock = 'outofstock' !== get_post_meta( $id, '_stock_status', true );
		foreach ( array_filter( $titres ) as $titre ) {
			$formes = array( $titre );
			// Sans ce que Wix ajoutait en queue de titre : « (nouvelle traduction) », « – sous la direction de … ».
			$court = trim( preg_replace( '/\s*(?:\([^)]*\)|\[[^\]]*\]|\s[-–—]\s.*)$/u', '', $titre ) );
			if ( $court && $court !== $titre ) {
				$formes[] = $court;
			}
			foreach ( $formes as $forme ) {
				$mots = wp_list_pluck( er_liens_mots( $forme ), 0 );
				if ( ! $mots ) {
					continue;
				}
				$variantes = array( $mots );
				// Sans l'article : « Docteur Jivago » ; « L’Archipel » se lit aussi « Archipel ».
				if ( count( $mots ) > 1 && er_liens_article( $mots[0] ) ) {
					$variantes[] = array_slice( $mots, 1 );
				} elseif ( preg_match( '/^[ldLD][’\'](.+)$/u', $mots[0], $e ) ) {
					$variantes[] = array_merge( array( $e[1] ), array_slice( $mots, 1 ) );
				}
				foreach ( $variantes as $rang => $v ) {
					// Une forme raccourcie (sans l'article, sans la queue de titre) d'un seul mot est trop vague : « Journal »
					// pour « Journal (1867) », « Douze » pour « Les Douze ».
					if ( count( $v ) < 2 && ( $rang > 0 || $forme !== $titre ) ) {
						continue;
					}
					$cle    = er_liens_cle( $v );
					$pleins = isset( $personnes[ $cle ] ) ? 1 : count( array_filter( $v, fn( $m ) => ! er_liens_article( $m ) ) );
					// Deux livres au même titre : celui qui est en stock, sinon le plus récent.
					if ( isset( $candidats[ $cle ] ) ) {
						list( $autre, , $autre_stock ) = $candidats[ $cle ];
						if ( $autre_stock && ! $en_stock || ( $autre_stock === $en_stock && $autre > $id ) ) {
							continue;
						}
					}
					$candidats[ $cle ] = array( $id, $pleins, $en_stock );
				}
			}
		}
	}
	$sommier = array(
		'titres' => array(),
		'max'    => 1,
	);
	foreach ( $candidats as $cle => list( $id, $pleins ) ) {
		$sommier['titres'][ $cle ] = array( $id, $pleins );
		$sommier['max']            = max( $sommier['max'], substr_count( $cle, ' ' ) + 1 );
	}
	set_transient( 'er_liens_sommier', $sommier, DAY_IN_SECONDS );
	return $sommier;
}

// Un livre change (titre, stock, visibilité, mise en vente) : le sommier est à refaire.
foreach ( array( 'save_post_product', 'deleted_post', 'trashed_post', 'woocommerce_product_set_stock_status', 'woocommerce_product_set_visibility' ) as $crochet ) {
	add_action(
		$crochet,
		function () {
			delete_transient( 'er_liens_sommier' );
		}
	);
}

/**
 * Les livres déjà liés dans la page, et ceux qu'on n'y lie pas (le livre de la fiche).
 *
 * @param int|null $ajout    Un livre à marquer comme lié.
 * @param bool     $remettre Repartir de zéro (une autre page, en ligne de commande).
 * @return array<int,bool>
 */
function er_liens_deja( $ajout = null, $remettre = false ) {
	static $deja = null;
	if ( null === $deja || $remettre ) {
		$deja = array();
		if ( is_singular( 'product' ) ) {
			$deja[ (int) get_queried_object_id() ] = true;
		}
	}
	if ( null !== $ajout ) {
		$deja[ (int) $ajout ] = true;
	}
	return $deja;
}

/**
 * Le lien d'un livre autour d'un texte. Avec Polylang, la fiche dans la langue de la page quand elle existe.
 *
 * @param int    $id    Le livre.
 * @param string $texte Le texte, déjà en HTML.
 */
function er_liens_ancre( $id, $texte ) {
	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) && pll_current_language() ) {
		$id = (int) pll_get_post( $id ) ?: $id;
	}
	// Le thème peut montrer son propre aperçu à la place de la vignette (filtre er_vignettes_actives).
	if ( apply_filters( 'er_vignettes_actives', true ) ) {
		wp_enqueue_script( 'er-vignettes' );
	}
	return '<a class="er-lien-livre" href="' . esc_url( get_permalink( $id ) ) . '" data-livre="' . (int) $id . '">' . $texte . '</a>';
}

/**
 * Recompose un morceau de texte avec ses liens : le texte échappé, les liens posés à leur place.
 *
 * @param string $texte Le texte, décodé.
 * @param array  $poses Les liens : [début, fin, livre], dans l'ordre.
 */
function er_liens_recomposer( $texte, $poses ) {
	$html = '';
	$pos  = 0;
	foreach ( $poses as list( $debut, $fin, $id ) ) {
		$html .= htmlspecialchars( substr( $texte, $pos, $debut - $pos ), ENT_NOQUOTES, 'UTF-8' )
			. er_liens_ancre( $id, htmlspecialchars( substr( $texte, $debut, $fin - $debut ), ENT_NOQUOTES, 'UTF-8' ) );
		$pos   = $fin;
	}
	return $html . htmlspecialchars( substr( $texte, $pos ), ENT_NOQUOTES, 'UTF-8' );
}

/**
 * Cherche les titres dans un morceau de texte (sans balises) et les lie. Le morceau n'est réécrit (décodé puis
 * échappé de nouveau) que s'il reçoit un lien.
 *
 * @param string $brut Le texte, tel qu'il est dans le HTML (avec ses entités : &nbsp;, &rsquo;…).
 * @param bool   $cite Le morceau est-il tout entier cité (seul dans un italique) ?
 */
function er_liens_morceau( $brut, $cite ) {
	$sommier = er_liens_sommier();
	$texte   = false !== strpos( $brut, '&' ) ? html_entity_decode( $brut, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : $brut;
	$mots    = er_liens_mots( $texte );
	if ( ! $mots || ! $sommier['titres'] ) {
		return $brut;
	}
	$poses = array();
	$deja  = er_liens_deja();
	// Cité tout entier : le morceau est-il un titre, même peu distinctif ?
	if ( $cite ) {
		$cle = er_liens_cle( wp_list_pluck( $mots, 0 ) );
		if ( isset( $sommier['titres'][ $cle ] ) && ! isset( $deja[ $sommier['titres'][ $cle ][0] ] ) && preg_match( '/^\p{Lu}|^\p{N}/u', $mots[0][0] ) ) {
			$id = $sommier['titres'][ $cle ][0];
			er_liens_deja( $id );
			return er_liens_recomposer( $texte, array( array( $mots[0][1], $mots[ count( $mots ) - 1 ][2], $id ) ) );
		}
	}
	// Entre guillemets « » : un titre cité.
	$guillemets = array();
	if ( preg_match_all( '/«[\s\x{00A0}\x{202F}]*([^«»]+?)[\s\x{00A0}\x{202F}]*»/u', $texte, $g, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $g[1] as list( $dedans, $pos ) ) {
			$guillemets[ $pos ] = $pos + strlen( $dedans );
		}
	}
	$n = count( $mots );
	for ( $i = 0; $i < $n; $i++ ) {
		// Le premier mot, ou ce qui suit son article élidé (« l’Archipel »).
		$departs = array( array( $mots[ $i ][0], $mots[ $i ][1] ) );
		if ( preg_match( '/^([ldLD][’\'])(.+)$/u', $mots[ $i ][0], $e ) ) {
			$departs[] = array( $e[2], $mots[ $i ][1] + strlen( $e[1] ) );
		}
		$trouve = false;
		foreach ( $departs as list( $premier, $debut ) ) {
			if ( ! preg_match( '/^\p{Lu}|^\p{N}/u', $premier ) ) {
				continue;
			}
			for ( $longueur = min( $sommier['max'], $n - $i ); $longueur >= 1; $longueur-- ) {
				$suite = array( $premier );
				$ok    = true;
				for ( $k = 1; $k < $longueur; $k++ ) {
					$entre = substr( $texte, $mots[ $i + $k - 1 ][2], $mots[ $i + $k ][1] - $mots[ $i + $k - 1 ][2] );
					// Entre deux mots d'un titre : des espaces, une virgule, deux-points, un tiret ; pas la fin d'une phrase.
					if ( ! preg_match( '/^[\s\x{00A0}\x{202F},:;–—\-]*$/u', $entre ) ) {
						$ok = false;
						break;
					}
					$suite[] = $mots[ $i + $k ][0];
				}
				if ( ! $ok ) {
					continue;
				}
				$cle = er_liens_cle( $suite );
				if ( ! isset( $sommier['titres'][ $cle ] ) ) {
					continue;
				}
				list( $id, $pleins ) = $sommier['titres'][ $cle ];
				if ( isset( $deja[ $id ] ) ) {
					continue;
				}
				$fin = $mots[ $i + $longueur - 1 ][2];
				// Distinctif (trois mots pleins, ou deux mots à majuscule) : partout ; sinon, seulement entre guillemets.
				$majuscules = count( array_filter( $suite, fn( $m ) => ! er_liens_article( $m ) && preg_match( '/^\p{Lu}/u', $m ) ) );
				$distinctif = $pleins >= 3 || ( $pleins >= 2 && $majuscules >= 2 );
				$entre_guil = isset( $guillemets[ $debut ] ) && $guillemets[ $debut ] === $fin;
				if ( ! $distinctif && ! $entre_guil ) {
					continue;
				}
				$poses[] = array( $debut, $fin, $id );
				$deja    = er_liens_deja( $id );
				$i      += $longueur - 1;
				$trouve  = true;
				break;
			}
			if ( $trouve ) {
				break;
			}
		}
	}
	return $poses ? er_liens_recomposer( $texte, $poses ) : $brut;
}

/**
 * Lie les titres des livres dans un morceau de HTML. On ne touche qu'au texte, hors des liens, des titres, des boutons,
 * du code et des endroits marqués « er-sans-liens ».
 *
 * @param string $html Le HTML.
 */
function er_lier_titres( $html ) {
	if ( ! is_string( $html ) || '' === trim( $html ) || ! preg_match( '/\p{Lu}/u', $html ) ) {
		return $html;
	}
	$interdits = array( 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'button', 'label', 'script', 'style', 'code', 'pre', 'textarea', 'select', 'option', 'svg', 'math', 'figcaption', 'title', 'noscript', 'iframe' );
	$vides     = array( 'br', 'img', 'hr', 'input', 'meta', 'link', 'source', 'wbr', 'col', 'area', 'base', 'embed', 'track', 'param' );
	$citants   = array( 'em', 'i', 'cite' );
	$morceaux  = preg_split( '/(<!--.*?-->|<[^>]*>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$pile      = array();
	$bloque    = 0;
	$sortie    = '';
	$n         = count( $morceaux );
	for ( $k = 0; $k < $n; $k++ ) {
		$m = $morceaux[ $k ];
		if ( 1 === $k % 2 ) {
			$sortie .= $m;
			if ( preg_match( '#^<(/?)([a-zA-Z][a-zA-Z0-9-]*)([^>]*?)(/?)>$#s', $m, $b ) ) {
				$nom = strtolower( $b[2] );
				if ( '' === $b[1] ) {
					if ( '/' === $b[4] || in_array( $nom, $vides, true ) ) {
						continue;
					}
					$interdit = in_array( $nom, $interdits, true ) || false !== strpos( $b[3], 'er-sans-liens' );
					$pile[]   = array( $nom, $interdit );
					$bloque  += $interdit ? 1 : 0;
				} else {
					for ( $p = count( $pile ) - 1; $p >= 0; $p-- ) {
						if ( $pile[ $p ][0] === $nom ) {
							foreach ( array_splice( $pile, $p ) as $ferme ) {
								$bloque -= $ferme[1] ? 1 : 0;
							}
							break;
						}
					}
				}
			}
			continue;
		}
		if ( $bloque > 0 || '' === trim( $m ) ) {
			$sortie .= $m;
			continue;
		}
		// Cité tout entier : le morceau est seul entre <em> et </em> (ou <i>, <cite>).
		$cite = false;
		if ( $k > 0 && $k + 1 < $n && preg_match( '#^<(em|i|cite)\b[^>]*>$#i', $morceaux[ $k - 1 ], $o ) && preg_match( '#^</' . preg_quote( $o[1], '#' ) . '>$#i', $morceaux[ $k + 1 ] ) ) {
			$cite = in_array( strtolower( $o[1] ), $citants, true );
		}
		$sortie .= er_liens_morceau( $m, $cite );
	}
	return $sortie;
}

/**
 * Lier ici ? Sur le site seulement : ni dans l'administration, ni dans l'éditeur, ni pour l'API.
 */
function er_liens_actifs() {
	if ( is_admin() || wp_doing_ajax() || is_feed() ) {
		return false;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}
	return (bool) apply_filters( 'er_liens_actifs', true );
}

// Les blocs de texte, partout : le contenu des pages et des articles, les modèles, les compositions.
add_filter(
	'render_block',
	function ( $html, $bloc ) {
		$textes = array( 'core/paragraph', 'core/list-item', 'core/verse', 'core/pullquote', 'core/table', 'core/post-excerpt', 'core/term-description', 'core/freeform', null );
		if ( ! in_array( isset( $bloc['blockName'] ) ? $bloc['blockName'] : null, $textes, true ) || ! er_liens_actifs() ) {
			return $html;
		}
		return er_lier_titres( $html );
	},
	20,
	2
);

// Hors des blocs : la présentation courte d'un livre, la description d'un terme, et tout texte qu'on fait passer par
// le filtre « er_lier_titres ».
foreach ( array( 'woocommerce_short_description', 'term_description', 'er_lier_titres' ) as $filtre ) {
	add_filter(
		$filtre,
		function ( $html ) {
			return er_liens_actifs() ? er_lier_titres( $html ) : $html;
		},
		20
	);
}

/**
 * La vignette d'un livre, celle qui paraît au survol de son titre.
 *
 * @param WC_Product $p Le livre.
 */
function er_vignette_html( $p ) {
	$id       = $p->get_id();
	$auteurs  = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
	$mentions = wp_get_post_terms( $id, 'product_tag', array( 'fields' => 'names' ) );
	$russe    = preg_match( '/\p{Cyrillic}/u', $p->get_name() ) ? ' lang="ru"' : '';
	$couv     = function_exists( 'er_reco_couverture' ) ? er_reco_couverture( $p ) : $p->get_image( 'woocommerce_thumbnail', array( 'alt' => '' ) );
	$prix     = function_exists( 'er_reco_prix' ) ? er_reco_prix( $p ) : '';
	return '<a class="er-vignette__lien" href="' . esc_url( get_permalink( $id ) ) . '">'
		. '<span class="er-vignette__couverture">' . $couv . '</span>'
		. '<span class="er-vignette__texte">'
		. ( $mentions && ! is_wp_error( $mentions ) ? '<span class="er-vignette__mention">' . esc_html( $mentions[0] ) . '</span>' : '' )
		. '<span class="er-vignette__titre"' . $russe . '>' . esc_html( wptexturize( $p->get_name() ) ) . '</span>'
		. ( $auteurs && ! is_wp_error( $auteurs ) ? '<span class="er-vignette__auteur">' . esc_html( implode( ', ', $auteurs ) ) . '</span>' : '' )
		. $prix
		. '<span class="er-vignette__voir">' . esc_html__( 'Voir à la librairie', 'er-librairie' ) . ' <span aria-hidden="true">→</span></span>'
		. '</span></a>';
}

// La vignette, à la demande du script : /wp-json/er/v1/vignette/<livre>.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'er/v1',
			'/vignette/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $r ) {
					$p = wc_get_product( (int) $r['id'] );
					if ( ! $p || 'publish' !== $p->get_status() || 'hidden' === $p->get_catalog_visibility() || $p->get_meta( 'er_evenement' ) ) {
						return new WP_Error( 'er_vignette', __( 'Livre introuvable.', 'er-librairie' ), array( 'status' => 404 ) );
					}
					$reponse = new WP_REST_Response( array( 'html' => er_vignette_html( $p ) ) );
					$reponse->header( 'Cache-Control', 'public, max-age=600' );
					return $reponse;
				},
			)
		);
	}
);

// Le script de la vignette : déclaré dès le départ (un thème en blocs rend la page avant l'en-tête), chargé seulement
// quand un titre a été lié.
add_action(
	'init',
	function () {
		if ( is_admin() ) {
			return;
		}
		$donnees = get_file_data( dirname( __DIR__ ) . '/er-librairie.php', array( 'version' => 'Version' ) );
		wp_register_script(
			'er-vignettes',
			plugins_url( 'assets/js/vignettes.js', __DIR__ ),
			array(),
			$donnees['version'],
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_add_inline_script( 'er-vignettes', 'window.erVignettes = ' . wp_json_encode( array( 'api' => esc_url_raw( rest_url( 'er/v1/' ) ) ) ) . ';', 'before' );
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Le sommier des titres liés, ou les titres liés dans une page : wp er liens [<slug>].
	 *
	 * @param array $args Le slug d'une page, d'un article ou d'un livre.
	 */
	WP_CLI::add_command(
		'er liens',
		function ( $args ) {
			delete_transient( 'er_liens_sommier' );
			$sommier = er_liens_sommier();
			if ( empty( $args[0] ) ) {
				$livres = array();
				foreach ( $sommier['titres'] as $cle => list( $id ) ) {
					$livres[ $id ][] = $cle;
				}
				WP_CLI::log( count( $livres ) . ' livres dans le sommier, ' . count( $sommier['titres'] ) . ' façons de les citer (le plus long : ' . $sommier['max'] . ' mots).' );
				$distinctifs = 0;
				foreach ( $sommier['titres'] as list( , $pleins ) ) {
					$distinctifs += $pleins >= 2 ? 1 : 0;
				}
				WP_CLI::log( $distinctifs . ' de deux mots pleins ou plus (liés partout s’ils sont assez distinctifs), les autres seulement cités, en italique ou entre « ».' );
				foreach ( array_slice( $sommier['titres'], 0, 25, true ) as $cle => list( $id, $pleins ) ) {
					WP_CLI::log( '  ' . $cle . ' → ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) . ' (' . $pleins . ')' );
				}
				return;
			}
			// « articles » : les douze derniers articles du journal ; sinon une adresse, ou à défaut les mots de l'adresse.
			$types = array( 'page', 'post', 'product', 'er_evenement', 'er_exposition', 'er_media' );
			if ( 'articles' === $args[0] ) {
				$objets = get_posts(
					array(
						'post_type'   => 'post',
						'numberposts' => 12,
					)
				);
			} else {
				$objet = null;
				foreach ( $types as $type ) {
					$objet = get_page_by_path( sanitize_title( $args[0] ), OBJECT, $type );
					if ( $objet ) {
						break;
					}
				}
				$objets = $objet ? array( $objet ) : get_posts(
					array(
						'post_type'   => $types,
						's'           => str_replace( '-', ' ', $args[0] ),
						'numberposts' => 3,
					)
				);
			}
			if ( ! $objets ) {
				WP_CLI::error( 'Rien à cette adresse : ' . $args[0] );
			}
			foreach ( $objets as $objet ) {
				er_liens_deja( null, true );
				$GLOBALS['post'] = $objet; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $objet );
				$html = apply_filters( 'the_content', $objet->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
				preg_match_all( '#<a class="er-lien-livre" href="([^"]*)" data-livre="(\d+)">(.*?)</a>#s', $html, $m, PREG_SET_ORDER );
				WP_CLI::log( count( $m ) . ' titres liés dans « ' . html_entity_decode( get_the_title( $objet ), ENT_QUOTES, 'UTF-8' ) . ' » (' . $objet->post_name . ') :' );
				foreach ( $m as $lien ) {
					WP_CLI::log( '  ' . html_entity_decode( wp_strip_all_tags( $lien[3] ), ENT_QUOTES, 'UTF-8' ) . ' → ' . html_entity_decode( get_the_title( (int) $lien[2] ), ENT_QUOTES, 'UTF-8' ) );
				}
			}
		}
	);
}
