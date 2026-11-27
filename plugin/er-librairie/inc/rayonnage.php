<?php
/**
 * La grille des livres (canevas « Librairie — la grille des livres », 24 novembre 2026) : une seule grille, trois
 * façons de la voir.
 *
 * - La table : chaque livre est une carte (le bloc er/carte-livre, que le thème place dans ses grilles) : la
 *   couverture entière, posée sur la même ligne que ses voisines ; les mentions en pastilles ; le titre et, sous un
 *   titre russe, sa traduction (la méta er_titre_fr) ; l'auteur et, sous un nom en cyrillique, sa transcription (ses
 *   autres graphies) ; la langue, l'année, la maison (le point brun d'YMCA-Press) ; qui en parle ; le prix et
 *   « Ajouter », ou « Me prévenir ».
 * - Le fil du libraire : sur la page d'un rayon, sans filtre ni tri, des modules s'intercalent entre les livres : le
 *   livre du moment (choisi dans la fiche du rayon), une époque, la bande d'YMCA-Press, le même livre en deux langues,
 *   un auteur. Tous viennent de ce que le site sait déjà des livres du rayon (er_facettes_etat()).
 * - Par époque : deux tris de plus, « L'époque dont parle le livre » et « L'époque d'écriture » ; la grille se range
 *   alors en chapitres, de l'Antiquité à aujourd'hui.
 * - Le catalogue : la vue en liste, une ligne par livre (titre, auteur, maison, année, pages, langue, prix). Les
 *   boutons « Couvertures » et « Liste » à côté du tri ; par défaut la liste sur la page d'une maison et sur
 *   « Très rare ». Le choix se garde dans le navigateur, et dans l'adresse (?vue=liste).
 *
 * Et en bas de la grille, « Voir 24 livres de plus » remplace les numéros de page (assets/js/rayonnage.js ; sans
 * JavaScript, la pagination reste).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/* La carte d'un livre ----------------------------------------------------------------------------------------- */

/**
 * Une chaîne en cyrillique ?
 *
 * @param string $s La chaîne.
 */
function er_carte_cyrillique( $s ) {
	return (bool) preg_match( '/\p{Cyrillic}/u', (string) $s );
}

/**
 * Le titre d'un livre, tel que le site l'affiche (les tirets et les guillemets typographiques), en texte, sans
 * balises ni entités.
 *
 * @param WC_Product $produit Le livre.
 */
function er_carte_titre( $produit ) {
	return html_entity_decode( wp_strip_all_tags( get_the_title( $produit->get_id() ) ), ENT_QUOTES, 'UTF-8' );
}

/**
 * L'année d'une parution écrite à la main (« 1980 », « Paris, 1972 », « 2024-2025 ») : la première année plausible.
 *
 * @param string $texte La parution.
 */
function er_carte_annee( $texte ) {
	return preg_match( '/\b(1[4-9]\d\d|20[0-4]\d)\b/', (string) $texte, $m ) ? $m[1] : '';
}

/**
 * La langue d'un livre, dite courte pour la ligne de faits.
 *
 * @param string $slug Le slug du terme de langue.
 * @param string $nom  Son nom.
 */
function er_carte_langue( $slug, $nom ) {
	$courtes = array(
		'francais'                => __( 'En français', 'er-librairie' ),
		'russe'                   => __( 'En russe', 'er-librairie' ),
		'bilingue-russe-francais' => __( 'Bilingue', 'er-librairie' ),
		'anglais'                 => __( 'En anglais', 'er-librairie' ),
		'slavon'                  => __( 'En slavon', 'er-librairie' ),
		'ukrainien'               => __( 'En ukrainien', 'er-librairie' ),
		'italien'                 => __( 'En italien', 'er-librairie' ),
		'allemand'                => __( 'En allemand', 'er-librairie' ),
	);
	return $courtes[ $slug ] ?? $nom;
}

/**
 * L'autre écriture d'une personne : sous un nom en cyrillique, sa transcription ; sous un nom latin, son nom russe.
 * Prise dans ses « Autres graphies ».
 *
 * @param WP_Term $terme L'auteur.
 */
function er_carte_autre_ecriture( $terme ) {
	if ( ! function_exists( 'er_autres_graphies' ) ) {
		return '';
	}
	$cyr = er_carte_cyrillique( $terme->name );
	foreach ( er_autres_graphies( $terme->term_id ) as $g ) {
		if ( er_carte_cyrillique( $g ) !== $cyr ) {
			return $g;
		}
	}
	return '';
}

/**
 * Le nom de la maison d'un livre, et si c'est YMCA-Press.
 *
 * @param int $id Le livre.
 * @return array [ nom, ymca ]
 */
function er_carte_maison( $id ) {
	$ed  = function_exists( 'er_editeur_du_livre' ) ? er_editeur_du_livre( $id ) : array( 'termes' => array(), 'texte' => (string) get_post_meta( $id, 'er_editeur', true ) );
	$nom = $ed['termes'] ? html_entity_decode( $ed['termes'][0]->name, ENT_QUOTES, 'UTF-8' ) : trim( wp_strip_all_tags( html_entity_decode( (string) $ed['texte'], ENT_QUOTES, 'UTF-8' ) ) );
	if ( '' !== $nom && function_exists( 'er_notice_editeur' ) ) {
		$nom = er_notice_editeur( $nom );
	}
	$ymca = '' !== $nom && function_exists( 'er_editeur_cle' ) && er_editeur_cle( $nom ) === er_editeur_cle( 'YMCA-Press' );
	return array( $nom, $ymca );
}

/**
 * Qui en parle : la première revue de presse, sinon le premier prix (la méta er_presse).
 *
 * @param int $id Le livre.
 * @return array [ 'presse' => [nom, lien] ou null, 'prix' => [nom, date] ou null ]
 */
function er_carte_presse( $id ) {
	$presse = get_post_meta( $id, 'er_presse', true );
	$out    = array(
		'presse' => null,
		'prix'   => null,
	);
	foreach ( is_array( $presse ) ? $presse : array() as $p ) {
		$genre = $p['genre'] ?? 'presse';
		if ( 'prix' === $genre && ! $out['prix'] ) {
			$out['prix'] = array( (string) ( $p['nom'] ?? '' ), (string) ( $p['date'] ?? '' ) );
		} elseif ( 'prix' !== $genre && ! $out['presse'] ) {
			$out['presse'] = array( (string) ( $p['nom'] ?? '' ), (string) ( $p['lien'] ?? '' ) );
		}
	}
	return $out;
}

/**
 * La couverture d'un livre, entière : la photo, ou la couverture typographique du thème quand la photo manque ou est
 * trop petite.
 *
 * @param WC_Product $produit Le livre.
 * @param string     $tailles L'attribut sizes de l'image.
 */
function er_carte_couverture( $produit, $tailles = '(max-width: 600px) 46vw, 240px' ) {
	$titre = er_carte_titre( $produit );
	if ( function_exists( 'er_couverture_a_remplacer' ) && er_couverture_a_remplacer( $produit ) && function_exists( 'er_couverture_typo' ) ) {
		return er_couverture_typo( $produit );
	}
	$image = $produit->get_image_id();
	if ( ! $image ) {
		return function_exists( 'er_couverture_typo' ) ? er_couverture_typo( $produit ) : '';
	}
	return wp_get_attachment_image(
		$image,
		'woocommerce_single',
		false,
		array(
			'class'   => 'er-carte__image',
			'loading' => 'lazy',
			'sizes'   => $tailles,
			/* translators: %s: le titre du livre. */
			'alt'     => sprintf( __( 'Couverture : %s', 'er-librairie' ), $titre ),
		)
	);
}

/**
 * Le bouton d'achat : « Ajouter » (au panier, sans quitter la page), « Me prévenir » pour un livre épuisé.
 *
 * @param WC_Product $produit Le livre.
 * @param string     $texte   Le texte du bouton.
 */
function er_carte_bouton( $produit, $texte = '' ) {
	$titre = er_carte_titre( $produit );
	if ( $produit->is_type( 'simple' ) && $produit->is_purchasable() && $produit->is_in_stock() ) {
		// Sans script, le lien ajoute au panier en rechargeant la page ; avec, assets/js/rayonnage.js l'ajoute sur place
		// (wc-ajax=add_to_cart) et prévient le panier de l'en-tête.
		$ajax = class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'add_to_cart' ) : '';
		return '<a href="' . esc_url( $produit->add_to_cart_url() ) . '" data-product_id="' . esc_attr( $produit->get_id() ) . '" data-ajax="' . esc_url( $ajax ) . '" data-ajoute="' . esc_attr__( 'Ajouté', 'er-librairie' ) . '" class="er-carte__ajouter" rel="nofollow" aria-label="'
			/* translators: %s: le titre du livre. */
			. esc_attr( sprintf( __( 'Ajouter au panier : %s', 'er-librairie' ), $titre ) ) . '">' . esc_html( $texte ? $texte : __( 'Ajouter', 'er-librairie' ) ) . '</a>';
	}
	// « Me prévenir » promettait une alerte que le site n'envoie pas (audit du 27 nov. 2026) : la fiche, simplement.
	return '<a class="er-carte__prevenir" href="' . esc_url( $produit->get_permalink() ) . '">' . esc_html__( 'Voir le livre', 'er-librairie' ) . '</a>';
}

/**
 * Les pastilles d'un livre : ses mentions (Nouveauté, Très rare…), et « Épuisé ».
 *
 * @param WC_Product $produit Le livre.
 */
function er_carte_pastilles( $produit ) {
	$html   = '';
	$termes = wp_get_post_terms( $produit->get_id(), 'product_tag' );
	foreach ( is_array( $termes ) ? $termes : array() as $t ) {
		$html .= '<span class="er-pastille er-pastille--' . esc_attr( $t->slug ) . '">' . esc_html( html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) ) . '</span>';
	}
	if ( ! $produit->is_in_stock() ) {
		// « Épuisé » seul dirait que l'éditeur ne le réimprime plus : le même mot que partout ailleurs sur le site.
		$html .= '<span class="er-pastille er-pastille--epuise">' . esc_html__( 'Épuisé pour l’instant', 'er-librairie' ) . '</span>';
	}
	return $html;
}

/**
 * La carte d'un livre, telle que les grilles la montrent (en couvertures comme en liste : la feuille de style choisit).
 *
 * @param WC_Product $produit Le livre.
 */
function er_carte_html( $produit ) {
	$id     = $produit->get_id();
	$lien   = esc_url( $produit->get_permalink() );
	$titre  = er_carte_titre( $produit );
	$glose  = trim( (string) $produit->get_meta( 'er_titre_fr' ) );
	$langue = '';
	$sa     = wp_get_post_terms( $id, 'langue' );
	if ( $sa && ! is_wp_error( $sa ) ) {
		$langue = er_carte_langue( $sa[0]->slug, html_entity_decode( $sa[0]->name, ENT_QUOTES, 'UTF-8' ) );
	}
	$auteurs = array();
	foreach ( array_slice( (array) wp_get_post_terms( $id, 'auteur' ), 0, 2 ) as $t ) {
		if ( ! $t instanceof WP_Term ) {
			continue;
		}
		$autre     = er_carte_cyrillique( $t->name ) ? er_carte_autre_ecriture( $t ) : '';
		$auteurs[] = '<a href="' . esc_url( get_term_link( $t ) ) . '"' . ( er_carte_cyrillique( $t->name ) ? ' lang="ru"' : '' ) . '>' . esc_html( $t->name ) . '</a>'
			. ( '' !== $autre ? '<span class="er-carte__translit"> · ' . esc_html( $autre ) . '</span>' : '' );
	}
	// Une revue ou un ouvrage collectif sans auteur : sa nature et qui l'a faite (oeuvres-collectives.php).
	if ( ! $auteurs && function_exists( 'er_collectif_en_tete' ) ) {
		$auteurs[] = er_collectif_en_tete( $id, false );
	}
	list( $maison, $ymca ) = er_carte_maison( $id );
	$annee                 = er_carte_annee( $produit->get_meta( 'er_annee' ) );
	$pages                 = (int) $produit->get_meta( 'er_pages' );
	$parle                 = er_carte_presse( $id );
	$presse                = '';
	if ( $parle['presse'] && '' !== $parle['presse'][0] ) {
		$nom    = esc_html( $parle['presse'][0] );
		$presse = '<p class="er-carte__presse">' . esc_html__( 'Ils en parlent :', 'er-librairie' ) . ' ' . ( '' !== $parle['presse'][1] ? '<a href="' . esc_url( $parle['presse'][1] ) . '" rel="noopener">' . $nom . '</a>' : $nom ) . '</p>';
	} elseif ( $parle['prix'] && '' !== $parle['prix'][0] ) {
		$presse = '<p class="er-carte__presse">' . esc_html( $parle['prix'][0] ) . '</p>';
	}
	$faits  = '<span class="er-carte__langue">' . esc_html( $langue ) . '</span>';
	$faits .= '<span class="er-carte__annee">' . esc_html( $annee ) . '</span>';
	$faits .= '<span class="er-carte__maison' . ( $ymca ? ' er-carte__maison--ymca' : '' ) . '">' . esc_html( $maison ) . '</span>';
	/* translators: %s: le nombre de pages. */
	$faits .= '<span class="er-carte__pages">' . ( $pages ? esc_html( sprintf( __( '%s p.', 'er-librairie' ), number_format_i18n( $pages ) ) ) : '' ) . '</span>';

	return '<article class="er-carte">'
		. '<a class="er-carte__couv" href="' . $lien . '" tabindex="-1" aria-hidden="true">' . er_carte_couverture( $produit ) . '</a>'
		. '<div class="er-carte__corps">'
		. '<div class="er-carte__tete">'
		. '<div class="er-carte__mentions">' . er_carte_pastilles( $produit ) . '</div>'
		. '<h3 class="er-carte__titre"><a href="' . $lien . '"' . ( er_carte_cyrillique( $titre ) ? ' lang="ru"' : '' ) . '>' . esc_html( $titre ) . '</a></h3>'
		. ( '' !== $glose ? '<p class="er-carte__glose">« ' . esc_html( trim( $glose, ' «»"' ) ) . ' »</p>' : '' )
		. '</div>'
		. '<p class="er-carte__auteur">' . implode( ', ', $auteurs ) . '</p>'
		. '<p class="er-carte__faits">' . $faits . '</p>'
		. $presse
		. '</div>'
		. '<div class="er-carte__achat"><span class="er-carte__prix">' . $produit->get_price_html() . '</span>' . er_carte_bouton( $produit ) . '</div>'
		. '</article>';
}

add_action(
	'init',
	function () {
		register_block_type(
			'er/carte-livre',
			array(
				'api_version'     => 3,
				'title'           => __( 'La carte d’un livre', 'er-librairie' ),
				'category'        => 'woocommerce',
				'uses_context'    => array( 'postId', 'postType', 'queryId' ),
				'render_callback' => function ( $attrs, $contenu, $bloc ) {
					$id      = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : get_the_ID();
					$produit = $id && function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
					return $produit ? er_carte_html( $produit ) : '';
				},
			)
		);
	}
);

/* Couvertures ou liste ---------------------------------------------------------------------------------------- */

/**
 * La vue de la grille : celle de l'adresse (?vue=), sinon la liste sur la page d'une maison et sur « Très rare », les
 * couvertures ailleurs. Le choix gardé dans le navigateur s'applique ensuite, par le script.
 */
function er_rayonnage_vue() {
	$vue = isset( $_GET['vue'] ) ? sanitize_key( wp_unslash( $_GET['vue'] ) ) : ''; // phpcs:ignore WordPress.Security
	if ( in_array( $vue, array( 'liste', 'couvertures' ), true ) ) {
		return $vue;
	}
	return ( is_tax( 'er_editeur' ) || is_tax( 'product_tag', 'tres-rare' ) ) ? 'liste' : 'couvertures';
}

/**
 * Les boutons « Couvertures » et « Liste ».
 */
function er_rayonnage_bascule() {
	$vue  = er_rayonnage_vue();
	$html = '<div class="er-vue" role="group" aria-label="' . esc_attr__( 'Affichage des livres', 'er-librairie' ) . '">';
	foreach ( array(
		'couvertures' => __( 'Couvertures', 'er-librairie' ),
		'liste'       => __( 'Liste', 'er-librairie' ),
	) as $cle => $libelle ) {
		$html .= '<a class="er-vue__choix" data-vue="' . esc_attr( $cle ) . '" href="' . esc_url( remove_query_arg( 'paged', add_query_arg( 'vue', $cle ) ) ) . '" aria-pressed="' . ( $cle === $vue ? 'true' : 'false' ) . '">' . esc_html( $libelle ) . '</a>';
	}
	return $html . '</div>';
}

/**
 * La ligne des intitulés de la liste, en tête de la grille (cachée en couvertures).
 */
function er_rayonnage_tete_liste() {
	$cols = array(
		'titre'  => __( 'Titre', 'er-librairie' ),
		'auteur' => __( 'Auteur', 'er-librairie' ),
		'maison' => __( 'Maison', 'er-librairie' ),
		'annee'  => __( 'Année', 'er-librairie' ),
		'pages'  => __( 'Pages', 'er-librairie' ),
		'langue' => __( 'Langue', 'er-librairie' ),
		'prix'   => __( 'Prix', 'er-librairie' ),
	);
	$html = '<li class="er-liste-tete" aria-hidden="true"><span class="er-liste-tete__couv"></span>';
	foreach ( $cols as $cle => $libelle ) {
		$html .= '<span class="er-liste-tete__' . esc_attr( $cle ) . '">' . esc_html( $libelle ) . '</span>';
	}
	return $html . '<span class="er-liste-tete__achat"></span></li>';
}

/* Les tris par époque ------------------------------------------------------------------------------------------ */

/**
 * Le tri demandé, s'il range par époque : 'epoque' (dont parle le livre) ou 'ecrit' (d'écriture), sinon ''.
 */
function er_rayonnage_tri_epoque() {
	$tri = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security
	return 'epoque' === $tri ? 'epoque' : ( 'ecriture' === $tri ? 'ecrit' : '' );
}

/**
 * Le rang de chaque époque, dans l'ordre du temps : slug → rang.
 */
function er_rayonnage_rangs() {
	$rangs = array();
	foreach ( array_values( er_epoques() ) as $i => $e ) {
		$rangs[ $e[0] ] = $i;
	}
	return $rangs;
}

/**
 * L'époque qui range un livre : la plus ancienne des siennes (pour la facette 'epoque' ou 'ecrit'), sinon ''.
 *
 * @param array  $livre Les facettes du livre (er_facettes_etat()['livres'][ id ]).
 * @param string $cle   'epoque' ou 'ecrit'.
 * @param array  $rangs er_rayonnage_rangs().
 */
function er_rayonnage_epoque_du_livre( $livre, $cle, $rangs ) {
	$meilleure = '';
	foreach ( (array) ( $livre[ $cle ] ?? array() ) as $slug ) {
		if ( isset( $rangs[ $slug ] ) && ( '' === $meilleure || $rangs[ $slug ] < $rangs[ $meilleure ] ) ) {
			$meilleure = $slug;
		}
	}
	return $meilleure;
}

/**
 * Range des livres dans l'ordre du temps, par la plus ancienne de leurs époques ; à époque égale, l'ordre donné ; les
 * livres sans époque à la fin.
 *
 * @param int[]  $ids    Les livres, dans leur ordre.
 * @param array  $livres id → facettes.
 * @param string $cle    'epoque' ou 'ecrit'.
 * @return int[]
 */
function er_rayonnage_ordonner( $ids, $livres, $cle ) {
	$rangs = er_rayonnage_rangs();
	$cles  = array();
	foreach ( array_values( $ids ) as $i => $id ) {
		$e           = er_rayonnage_epoque_du_livre( $livres[ $id ] ?? array(), $cle, $rangs );
		$cles[ $id ] = array( '' === $e ? 999 : $rangs[ $e ], $i );
	}
	$ids = array_values( $ids );
	usort( $ids, fn( $a, $b ) => $cles[ $a ] <=> $cles[ $b ] );
	return $ids;
}

add_filter(
	'woocommerce_catalog_orderby',
	function ( $tris ) {
		if ( function_exists( 'er_facettes_page' ) && er_facettes_page() && taxonomy_exists( 'er_periode' ) ) {
			$tris['epoque']   = __( 'L’époque dont parle le livre', 'er-librairie' );
			$tris['ecriture'] = __( 'L’époque d’écriture', 'er-librairie' );
		}
		return $tris;
	}
);

add_filter(
	'woocommerce_get_catalog_ordering_args',
	function ( $args, $tri = '' ) {
		if ( in_array( $tri, array( 'epoque', 'ecriture' ), true ) ) {
			return array(
				'orderby'  => 'post__in',
				'order'    => 'ASC',
				'meta_key' => '', // phpcs:ignore WordPress.DB.SlowDBQuery
			);
		}
		return $args;
	},
	10,
	2
);

// Après le filtre (30) : la liste des livres retenus, rangée dans l'ordre du temps.
add_action(
	'pre_get_posts',
	function ( $q ) {
		$cle = er_rayonnage_tri_epoque();
		if ( '' === $cle || ! $q->is_main_query() || $q->get( 'er_facettes_base' ) || ! function_exists( 'er_facettes_page' ) || ! er_facettes_page( $q ) ) {
			return;
		}
		$etat = er_facettes_etat( $q );
		if ( ! $etat ) {
			return;
		}
		$deja = array_values( array_filter( array_map( 'intval', (array) $q->get( 'post__in' ) ) ) );
		$ids  = $deja ? array_values( array_intersect( $deja, $etat['resultats'] ) ) : $etat['resultats'];
		$ids  = er_rayonnage_ordonner( $ids, $etat['livres'], $cle );
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		$q->set( 'orderby', 'post__in' );
		$q->set( 'order', 'ASC' );
	},
	31
);

/* Insérer dans la grille ---------------------------------------------------------------------------------------- */

/**
 * Coupe la liste rendue d'une grille en morceaux : l'ouverture, puis un morceau par <li>.
 *
 * @param string $html La grille.
 * @return string[]
 */
function er_rayonnage_morceaux( $html ) {
	return preg_split( '/(?=<li\b)/', $html );
}

/**
 * Insère des modules dans une grille, après un nombre donné de livres.
 *
 * @param string $html    La grille (un <ul> de <li>).
 * @param array  $modules nombre de livres avant → HTML d'un <li>.
 */
function er_rayonnage_inserer( $html, $modules ) {
	$morceaux = er_rayonnage_morceaux( $html );
	if ( count( $morceaux ) < 2 ) {
		return $html;
	}
	$n   = count( $morceaux ) - 1;
	$out = $morceaux[0] . ( $modules[0] ?? '' );
	for ( $i = 1; $i <= $n; $i++ ) {
		$out .= $morceaux[ $i ];
		if ( $i < $n && isset( $modules[ $i ] ) ) {
			$out .= $modules[ $i ];
		}
	}
	return $out;
}

/**
 * Insère avant chaque livre ce que demande le rappel (le titre d'un chapitre), d'après l'identifiant du livre.
 *
 * @param string   $html  La grille.
 * @param callable $avant id → HTML à insérer avant, ou ''.
 */
function er_rayonnage_inserer_avant( $html, $avant ) {
	$morceaux = er_rayonnage_morceaux( $html );
	$out      = array_shift( $morceaux );
	foreach ( $morceaux as $m ) {
		if ( preg_match( '/^<li\b[^>]*\bpost-(\d+)\b/', $m, $p ) ) {
			$out .= $avant( (int) $p[1] );
		}
		$out .= $m;
	}
	return $out;
}

/* Le fil du libraire ------------------------------------------------------------------------------------------- */

/**
 * Le fil passe-t-il sur cette page ? Sur la page d'un rayon, à sa première page, sans filtre, sans tri ni recherche,
 * en couvertures.
 */
function er_rayonnage_fil_actif() {
	if ( ! is_tax( 'product_cat' ) || is_paged() || is_search() || ! function_exists( 'er_facettes_selection' ) ) {
		return false;
	}
	if ( er_facettes_actives( er_facettes_selection( $_GET ) ) ) { // phpcs:ignore WordPress.Security
		return false;
	}
	$tri = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security
	return '' === $tri && 'couvertures' === er_rayonnage_vue();
}

/**
 * La clé d'une œuvre d'après un titre, pour reconnaître le même livre dans deux langues : la traduction d'un titre
 * russe (« La Roue rouge. Avril 1917 ») et le titre d'une édition française (« La Roue rouge, chapitres choisis »)
 * donnent la même clé, « laroue rouge » sans espaces ni accents.
 *
 * @param string $titre Le titre.
 */
function er_rayonnage_cle_oeuvre( $titre ) {
	$t = html_entity_decode( (string) $titre, ENT_QUOTES, 'UTF-8' );
	$t = trim( preg_replace( '/[«»"“”]/u', '', $t ) );
	$t = preg_split( '/\s*[.,:;(\/—–]\s*|\s-\s/u', $t )[0];
	$t = mb_strtolower( remove_accents( $t ), 'UTF-8' );
	$t = preg_replace( '/[^\p{L}\p{N}]+/u', '', $t );
	return mb_strlen( $t ) >= 4 ? $t : '';
}

/**
 * Le même livre dans deux langues, parmi des livres : une édition russe, dont la traduction du titre (er_titre_fr)
 * a la clé du titre d'une édition française, d'un même auteur.
 *
 * @param array    $livres id → facettes (au moins 'langue' et 'auteur').
 * @param callable $titre  id → titre.
 * @param callable $glose  id → traduction du titre.
 * @return array [ id français, id russe ] ou array()
 */
function er_rayonnage_paire( $livres, $titre, $glose ) {
	$russes = array();
	foreach ( $livres as $id => $l ) {
		if ( in_array( 'russe', (array) ( $l['langue'] ?? array() ), true ) ) {
			$cle = er_rayonnage_cle_oeuvre( $glose( $id ) );
			if ( '' !== $cle ) {
				$russes[ $cle ][] = $id;
			}
		}
	}
	if ( ! $russes ) {
		return array();
	}
	foreach ( $livres as $id => $l ) {
		if ( ! in_array( 'francais', (array) ( $l['langue'] ?? array() ), true ) ) {
			continue;
		}
		$cle = er_rayonnage_cle_oeuvre( $titre( $id ) );
		foreach ( $russes[ $cle ] ?? array() as $ru ) {
			if ( array_intersect( (array) ( $l['auteur'] ?? array() ), (array) ( $livres[ $ru ]['auteur'] ?? array() ) ) ) {
				return array( $id, $ru );
			}
		}
	}
	return array();
}

/**
 * L'édition d'un livre dans l'autre langue (russe pour un livre français, et l'inverse), parmi tous les livres du site.
 *
 * @param int $id Le livre.
 */
function er_rayonnage_autre_langue( $id ) {
	$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'ids' ) );
	if ( ! $auteurs || is_wp_error( $auteurs ) ) {
		return 0;
	}
	$autres = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 80,
			'fields'         => 'ids',
			'lang'           => '',
			'post__not_in'   => array( $id ),
			'tax_query'      => array( array( 'taxonomy' => 'auteur', 'terms' => $auteurs ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$livres = array();
	foreach ( array_merge( array( $id ), $autres ) as $i ) {
		$livres[ $i ] = array(
			'langue' => wp_get_post_terms( $i, 'langue', array( 'fields' => 'slugs' ) ),
			'auteur' => wp_get_post_terms( $i, 'auteur', array( 'fields' => 'slugs' ) ),
		);
	}
	$paire = er_rayonnage_paire(
		$livres,
		fn( $i ) => get_the_title( $i ),
		fn( $i ) => (string) get_post_meta( $i, 'er_titre_fr', true )
	);
	if ( $paire && in_array( $id, $paire, true ) ) {
		return $paire[0] === $id ? $paire[1] : $paire[0];
	}
	return 0;
}

/**
 * Le surtitre d'un module : le point de la maison, en capitales.
 *
 * @param string $texte Le surtitre.
 * @param string $point 'librairie', 'ymca' ou ''.
 */
function er_rayonnage_surtitre( $texte, $point = 'librairie' ) {
	return '<p class="er-module__surtitre' . ( $point ? ' er-module__surtitre--' . esc_attr( $point ) : '' ) . '">' . esc_html( $texte ) . '</p>';
}

/**
 * La ligne d'un livre dans un module : son titre lié, son auteur, sa maison, son année.
 *
 * @param WC_Product $p Le livre.
 */
function er_rayonnage_faits( $p ) {
	list( $maison, $ymca ) = er_carte_maison( $p->get_id() );
	$annee                 = er_carte_annee( $p->get_meta( 'er_annee' ) );
	$pages                 = (int) $p->get_meta( 'er_pages' );
	$faits                 = array();
	if ( '' !== $maison ) {
		$faits[] = ( $ymca ? '<span class="er-point er-point--ymca" aria-hidden="true"></span>' : '' ) . esc_html( $maison );
	}
	if ( '' !== $annee ) {
		$faits[] = esc_html( $annee );
	}
	if ( $pages ) {
		/* translators: %s: le nombre de pages. */
		$faits[] = esc_html( sprintf( __( '%s pages', 'er-librairie' ), number_format_i18n( $pages ) ) );
	}
	return implode( ' · ', $faits );
}

/**
 * Le livre du moment d'un rayon : choisi dans la fiche du rayon (ou de son rayon parent), avec le mot du libraire.
 *
 * @param WP_Term $rayon Le rayon.
 */
function er_rayonnage_module_moment( $rayon ) {
	$id  = (int) get_term_meta( $rayon->term_id, 'er_livre_du_moment', true );
	$mot = (string) get_term_meta( $rayon->term_id, 'er_mot_du_libraire', true );
	if ( ! $id && $rayon->parent ) {
		$id  = (int) get_term_meta( $rayon->parent, 'er_livre_du_moment', true );
		$mot = (string) get_term_meta( $rayon->parent, 'er_mot_du_libraire', true );
	}
	$p = $id ? wc_get_product( $id ) : null;
	if ( ! $p || 'publish' !== $p->get_status() ) {
		return '';
	}
	$lien    = esc_url( $p->get_permalink() );
	$titre   = er_carte_titre( $p );
	$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
	$trad    = function_exists( 'er_personnes_du_livre' ) ? wp_list_pluck( er_personnes_du_livre( $id, 'er_traduction' ), 'name' ) : array();
	if ( ! $trad && '' !== trim( (string) $p->get_meta( 'er_traducteur' ) ) ) {
		$trad = array( trim( (string) $p->get_meta( 'er_traducteur' ) ) );
	}
	$qui = esc_html( implode( ', ', is_array( $auteurs ) ? $auteurs : array() ) );
	if ( $trad ) {
		/* translators: %s: le ou les traducteurs. */
		$qui .= '<br>' . esc_html( sprintf( __( 'traduit par %s', 'er-librairie' ), implode( ', ', $trad ) ) );
	}
	$parle   = er_carte_presse( $id );
	$prix    = $parle['prix'] && '' !== $parle['prix'][0] ? '<p class="er-moment__prix-litteraire"><span class="er-pastille">' . esc_html( trim( $parle['prix'][0] . ' ' . $parle['prix'][1] ) ) . '</span></p>' : '';
	$autres  = array();
	if ( $parle['presse'] && '' !== $parle['presse'][0] ) {
		$autres[] = esc_html__( 'Ils en parlent :', 'er-librairie' ) . ' ' . ( '' !== $parle['presse'][1] ? '<a href="' . esc_url( $parle['presse'][1] ) . '" rel="noopener">' . esc_html( $parle['presse'][0] ) . '</a>' : esc_html( $parle['presse'][0] ) );
	}
	$autre  = er_rayonnage_autre_langue( $id );
	$decor  = '';
	if ( $autre ) {
		$ap       = wc_get_product( $autre );
		$langue   = er_carte_cyrillique( $ap->get_name() ) ? __( 'Aussi en russe :', 'er-librairie' ) : __( 'Aussi en français :', 'er-librairie' );
		$autres[] = esc_html( $langue ) . ' <a href="' . esc_url( $ap->get_permalink() ) . '">' . esc_html( er_carte_titre( $ap ) ) . '</a>' . ( $ap->is_in_stock() ? '' : esc_html__( ', épuisé', 'er-librairie' ) );
		if ( er_carte_cyrillique( $ap->get_name() ) ) {
			$decor = '<span class="er-module__lettre" aria-hidden="true">' . esc_html( preg_split( '/\s*[.,:(]/u', er_carte_titre( $ap ) )[0] ) . '</span>';
		}
	}
	return '<li class="er-module er-module--large er-moment">'
		. '<div class="er-moment__haut"><a class="er-moment__couv" href="' . $lien . '" tabindex="-1" aria-hidden="true">' . er_carte_couverture( $p, '160px' ) . '</a><div>'
		. er_rayonnage_surtitre( __( 'Le livre du moment', 'er-librairie' ) )
		. '<h2 class="er-moment__titre"><a href="' . $lien . '">' . esc_html( $titre ) . '</a></h2>'
		. '<p class="er-moment__qui">' . $qui . '</p>'
		. '<p class="er-moment__faits">' . er_rayonnage_faits( $p ) . '</p>'
		. '</div></div>'
		. $prix
		. ( '' !== trim( $mot ) ? '<p class="er-moment__mot">' . esc_html( $mot ) . '</p>' : '' )
		. ( $autres ? '<p class="er-moment__autres">' . implode( ' · ', $autres ) . '</p>' : '' )
		. '<div class="er-moment__achat"><span class="er-moment__prix">' . $p->get_price_html() . '</span>' . er_carte_bouton( $p, __( 'Ajouter au panier', 'er-librairie' ) ) . '</div>'
		. $decor
		. '</li>';
}

/**
 * Le choix de la semaine parmi des candidats : le même toute la semaine, un autre la suivante.
 *
 * @param array $candidats Les candidats.
 */
function er_rayonnage_de_la_semaine( $candidats ) {
	$candidats = array_values( $candidats );
	return $candidats ? $candidats[ ( (int) gmdate( 'W' ) + (int) gmdate( 'o' ) ) % count( $candidats ) ] : null;
}

/**
 * Une époque, avec le nombre de livres du rayon qui en parlent et sa petite frise.
 *
 * @param array $etat er_facettes_etat().
 */
function er_rayonnage_module_epoque( $etat ) {
	$compte = array();
	foreach ( $etat['livres'] as $l ) {
		foreach ( (array) ( $l['epoque'] ?? array() ) as $slug ) {
			$compte[ $slug ] = ( $compte[ $slug ] ?? 0 ) + 1;
		}
	}
	$epoques = array_values( er_epoques() );
	$cand    = array_values( array_filter( $epoques, fn( $e ) => ( $compte[ $e[0] ] ?? 0 ) >= 8 ) );
	$e       = er_rayonnage_de_la_semaine( $cand );
	if ( ! $e ) {
		return '';
	}
	list( $slug, $nom, $annees ) = $e;
	$n     = $compte[ $slug ];
	$sel   = er_facettes_selection( array() );
	$sel['epoque'] = array( $slug );
	$terme = get_term_by( 'slug', $slug, 'er_periode' );
	$desc  = $terme ? trim( wp_strip_all_tags( $terme->description ) ) : '';
	if ( mb_strlen( $desc ) < 24 || false !== strpos( $desc, $annees ) ) {
		$desc = ''; // Une description qui ne redit que les années.
	}
	$frise = '';
	foreach ( $epoques as $x ) {
		$frise .= '<span class="er-mini-frise__point' . ( $x[0] === $slug ? ' is-actif' : '' ) . '"></span>';
	}
	$lien_terme = $terme ? get_term_link( $terme ) : '';
	return '<li class="er-module er-module--epoque">'
		. er_rayonnage_surtitre( __( 'Une époque', 'er-librairie' ), '' )
		. '<p class="er-module__annees">' . esc_html( $annees ) . '</p>'
		. '<h2 class="er-module__titre">' . esc_html( $nom ) . '</h2>'
		. ( '' !== $desc ? '<p class="er-module__texte">' . esc_html( wp_trim_words( $desc, 22 ) ) . '</p>' : '' )
		/* translators: %s: le nombre de livres. */
		. '<p class="er-module__texte">' . esc_html( sprintf( _n( '%s livre du rayon en parle.', '%s livres du rayon en parlent.', $n, 'er-librairie' ), number_format_i18n( $n ) ) ) . '</p>'
		. '<div class="er-mini-frise" aria-hidden="true">' . $frise . '</div>'
		. '<div class="er-module__liens">'
		/* translators: %s: le nombre de livres. */
		. '<a class="er-module__pilule" href="' . esc_url( er_facettes_url( $sel ) ) . '">' . esc_html( sprintf( __( 'Voir les %s livres', 'er-librairie' ), number_format_i18n( $n ) ) ) . '</a>'
		. ( $lien_terme && ! is_wp_error( $lien_terme ) ? '<a class="er-module__lien" href="' . esc_url( $lien_terme ) . '">' . esc_html__( 'Toute l’époque, dans la librairie', 'er-librairie' ) . '</a>' : '' )
		. '</div>'
		. '</li>';
}

/**
 * La bande d'YMCA-Press : cinq de ses livres du rayon, sur l'aplat brun de la maison.
 *
 * @param array $etat er_facettes_etat().
 */
function er_rayonnage_module_ymca( $etat ) {
	$terme = get_term_by( 'name', 'YMCA-Press', 'er_editeur' );
	if ( ! $terme ) {
		return '';
	}
	$siens = array();
	foreach ( $etat['livres'] as $id => $l ) {
		if ( in_array( $terme->slug, (array) ( $l['maison'] ?? array() ), true ) ) {
			$siens[ $id ] = in_array( 'stock', (array) ( $l['dispo'] ?? array() ), true );
		}
	}
	$en_stock = array_keys( array_filter( $siens ) );
	if ( count( $en_stock ) < 5 ) {
		return '';
	}
	$items = '';
	foreach ( array_slice( $en_stock, 0, 5 ) as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			continue;
		}
		$glose  = trim( (string) $p->get_meta( 'er_titre_fr' ), ' «»"' );
		$auteur = wp_get_post_terms( $id, 'auteur' );
		$auteur = $auteur && ! is_wp_error( $auteur ) ? ( er_carte_cyrillique( $auteur[0]->name ) && er_carte_autre_ecriture( $auteur[0] ) ? er_carte_autre_ecriture( $auteur[0] ) : $auteur[0]->name ) : '';
		$items .= '<div class="er-ymca__livre"><a class="er-ymca__couv" href="' . esc_url( $p->get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . er_carte_couverture( $p, '160px' ) . '</a>'
			. '<h3 class="er-ymca__titre"><a href="' . esc_url( $p->get_permalink() ) . '" lang="ru">' . esc_html( er_carte_titre( $p ) ) . '</a></h3>'
			. ( '' !== $glose ? '<p class="er-ymca__glose">« ' . esc_html( $glose ) . ' »</p>' : '' )
			. ( '' !== $auteur ? '<p class="er-ymca__auteur">' . esc_html( $auteur ) . '</p>' : '' )
			. '<p class="er-ymca__prix">' . $p->get_price_html() . '</p></div>';
	}
	$n             = count( $siens );
	$sel           = er_facettes_selection( array() );
	$sel['maison'] = array( $terme->slug );
	return '<li class="er-module er-module--bande er-ymca">'
		. '<div class="er-ymca__tete"><div>'
		. er_rayonnage_surtitre( __( 'YMCA-Press, la maison', 'er-librairie' ), 'ymca' )
		. '<h2 class="er-module__titre">' . esc_html__( 'Publié à Paris, en russe', 'er-librairie' ) . '</h2>'
		/* translators: %s: le nombre de livres. */
		. '<p class="er-module__texte">' . esc_html( sprintf( _n( 'La maison de l’émigration russe, depuis 1921 : %s de ses livres est dans ce rayon.', 'La maison de l’émigration russe, depuis 1921 : %s de ses livres sont dans ce rayon.', $n, 'er-librairie' ), number_format_i18n( $n ) ) ) . '</p>'
		/* translators: %s: le nombre de livres. */
		. '</div><a class="er-module__pilule" href="' . esc_url( er_facettes_url( $sel ) ) . '">' . esc_html( sprintf( __( 'Voir les %s livres', 'er-librairie' ), number_format_i18n( $n ) ) ) . '</a></div>'
		. '<div class="er-ymca__livres">' . $items . '</div>'
		. '<span class="er-module__lettre" aria-hidden="true">ИМКА-Пресс</span>'
		. '</li>';
}

/**
 * Le même livre en deux langues, quand le rayon a les deux éditions.
 *
 * @param array $etat er_facettes_etat().
 */
function er_rayonnage_module_langues( $etat ) {
	$paire = er_rayonnage_paire(
		$etat['livres'],
		fn( $i ) => get_the_title( $i ),
		fn( $i ) => (string) get_post_meta( $i, 'er_titre_fr', true )
	);
	if ( ! $paire ) {
		return '';
	}
	$cotes  = '';
	$titres = array();
	foreach ( $paire as $k => $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			return '';
		}
		$titres[] = er_carte_titre( $p );
		$cotes   .= '<div class="er-deux-langues__cote"><a class="er-deux-langues__couv" href="' . esc_url( $p->get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . er_carte_couverture( $p, '200px' ) . '</a>'
			. '<p class="er-deux-langues__langue">' . esc_html( 0 === $k ? __( 'En français', 'er-librairie' ) : __( 'En russe', 'er-librairie' ) ) . '</p>'
			. '<h3 class="er-deux-langues__titre"><a href="' . esc_url( $p->get_permalink() ) . '"' . ( 1 === $k ? ' lang="ru"' : '' ) . '>' . esc_html( er_carte_titre( $p ) ) . '</a></h3>'
			. '<p class="er-deux-langues__faits">' . er_rayonnage_faits( $p ) . '</p>'
			. '<div class="er-carte__achat"><span class="er-carte__prix">' . $p->get_price_html() . '</span>' . er_carte_bouton( $p ) . '</div></div>';
	}
	$auteurs = wp_get_post_terms( $paire[0], 'auteur', array( 'fields' => 'names' ) );
	return '<li class="er-module er-module--large er-deux-langues">'
		. er_rayonnage_surtitre( __( 'Lire en deux langues', 'er-librairie' ) )
		. '<h2 class="er-module__titre">' . esc_html( preg_split( '/\s*[,:(—–]|\s-\s/u', $titres[0] )[0] . ' · ' . preg_split( '/\s*[.,:(—–]|\s-\s/u', $titres[1] )[0] ) . '</h2>'
		/* translators: %s: l'auteur. */
		. '<p class="er-module__texte">' . esc_html( sprintf( __( '%s : le même livre, en français et en russe.', 'er-librairie' ), $auteurs && ! is_wp_error( $auteurs ) ? $auteurs[0] : '' ) ) . '</p>'
		. '<div class="er-deux-langues__paire">' . $cotes . '</div>'
		. '</li>';
}

/**
 * Un auteur du rayon, de ceux qui y ont le plus de livres.
 *
 * @param array $etat er_facettes_etat().
 */
function er_rayonnage_module_auteur( $etat ) {
	$compte = array();
	$russes = array();
	foreach ( $etat['livres'] as $l ) {
		foreach ( (array) ( $l['auteur'] ?? array() ) as $slug ) {
			$compte[ $slug ] = ( $compte[ $slug ] ?? 0 ) + 1;
			if ( in_array( 'russe', (array) ( $l['langue'] ?? array() ), true ) ) {
				$russes[ $slug ] = ( $russes[ $slug ] ?? 0 ) + 1;
			}
		}
	}
	arsort( $compte );
	$cand  = array_slice( array_keys( array_filter( $compte, fn( $n ) => $n >= 4 ) ), 0, 8 );
	$slug  = er_rayonnage_de_la_semaine( $cand );
	$terme = $slug ? get_term_by( 'slug', $slug, 'auteur' ) : null;
	if ( ! $terme ) {
		return '';
	}
	$autre = er_carte_autre_ecriture( $terme );
	$n     = $compte[ $slug ];
	$r     = $russes[ $slug ] ?? 0;
	/* translators: %s: le nombre de livres. */
	$texte = sprintf( _n( '%s livre au rayon', '%s livres au rayon', $n, 'er-librairie' ), number_format_i18n( $n ) );
	if ( $r && $r < $n ) {
		/* translators: %s: le nombre de livres en russe. */
		$texte .= sprintf( __( ', dont %s en russe', 'er-librairie' ), number_format_i18n( $r ) );
	} elseif ( $r === $n ) {
		$texte .= __( ', tous en russe', 'er-librairie' );
	}
	return '<li class="er-module er-module--auteur">'
		. er_rayonnage_surtitre( __( 'Un auteur', 'er-librairie' ) )
		. '<h2 class="er-module__titre er-module__titre--auteur"><a href="' . esc_url( get_term_link( $terme ) ) . '"' . ( er_carte_cyrillique( $terme->name ) ? ' lang="ru"' : '' ) . '>' . esc_html( $terme->name ) . '</a></h2>'
		. ( '' !== $autre ? '<p class="er-module__autre"' . ( er_carte_cyrillique( $autre ) ? ' lang="ru"' : '' ) . '>' . esc_html( $autre ) . '</p>' : '' )
		. '<p class="er-module__texte">' . esc_html( $texte . '.' ) . '</p>'
		. '<div class="er-module__liens"><a class="er-module__pilule er-module__pilule--contour" href="' . esc_url( get_term_link( $terme ) ) . '">' . esc_html__( 'Sa page', 'er-librairie' ) . '</a></div>'
		. '</li>';
}

/**
 * Les modules du fil, à leur place : nombre de livres avant → module (une page en montre 16).
 */
function er_rayonnage_modules() {
	$etat  = er_facettes_etat();
	$rayon = get_queried_object();
	if ( ! $etat || ! $rayon instanceof WP_Term ) {
		return array();
	}
	$modules = array(
		0  => er_rayonnage_module_moment( $rayon ),
		5  => er_rayonnage_module_epoque( $etat ),
		8  => er_rayonnage_module_ymca( $etat ),
		11 => er_rayonnage_module_langues( $etat ),
		14 => er_rayonnage_module_auteur( $etat ),
	);
	return array_filter( $modules );
}

/**
 * Le titre d'un chapitre, quand la grille est rangée par époque.
 *
 * @param string $slug   L'époque.
 * @param int    $nombre Ses livres dans la liste.
 */
function er_rayonnage_chapitre( $slug, $nombre ) {
	$e = null;
	foreach ( er_epoques() as $x ) {
		if ( $x[0] === $slug ) {
			$e = $x;
		}
	}
	/* translators: %s: le nombre de livres. */
	$compte = esc_html( sprintf( _n( '%s livre', '%s livres', $nombre, 'er-librairie' ), number_format_i18n( $nombre ) ) );
	if ( ! $e ) {
		return '<li class="er-chapitre" data-epoque="sans"><h2 class="er-chapitre__nom">' . esc_html__( 'Sans époque', 'er-librairie' ) . '</h2><p class="er-chapitre__compte">' . $compte . '</p></li>';
	}
	return '<li class="er-chapitre" id="epoque-' . esc_attr( $slug ) . '" data-epoque="' . esc_attr( $slug ) . '">'
		. '<p class="er-chapitre__annees">' . esc_html( $e[2] ) . '</p>'
		. '<h2 class="er-chapitre__nom">' . esc_html( $e[1] ) . '</h2>'
		. '<p class="er-chapitre__compte">' . $compte . '</p></li>';
}

// La grille des pages de la librairie : la vue, la ligne des intitulés, les modules du fil, les chapitres.
add_filter(
	'render_block_woocommerce/product-template',
	function ( $html, $bloc, $instance = null ) {
		if ( ! function_exists( 'er_facettes_page' ) || ! er_facettes_page() || false === strpos( $html, 'er-livres' ) ) {
			return $html;
		}
		$query = $instance && isset( $instance->context['query'] ) ? $instance->context['query'] : array();
		if ( empty( $query['inherit'] ) ) {
			return $html;
		}
		$vue  = er_rayonnage_vue();
		$html = preg_replace( '/(<ul\b[^>]*class=")/', '$1er-livres--' . $vue . ' ', $html, 1 );
		$cle  = er_rayonnage_tri_epoque();
		if ( '' !== $cle ) {
			$etat  = er_facettes_etat();
			$rangs = er_rayonnage_rangs();
			$ids   = array_map( 'intval', (array) $GLOBALS['wp_query']->get( 'post__in' ) );
			$par   = array();
			foreach ( $ids as $id ) {
				$e         = $etat ? er_rayonnage_epoque_du_livre( $etat['livres'][ $id ] ?? array(), $cle, $rangs ) : '';
				$par[ $e ] = ( $par[ $e ] ?? 0 ) + 1;
			}
			$courante = null;
			$html     = er_rayonnage_inserer_avant(
				$html,
				function ( $id ) use ( $etat, $cle, $rangs, $par, &$courante ) {
					$e = $etat ? er_rayonnage_epoque_du_livre( $etat['livres'][ $id ] ?? array(), $cle, $rangs ) : '';
					if ( $e === $courante ) {
						return '';
					}
					$courante = $e;
					return er_rayonnage_chapitre( $e, $par[ $e ] ?? 0 );
				}
			);
		} elseif ( er_rayonnage_fil_actif() ) {
			$html = er_rayonnage_inserer( $html, er_rayonnage_modules() );
		}
		return er_rayonnage_inserer( $html, array( 0 => er_rayonnage_tete_liste() ) );
	},
	10,
	3
);

// À côté du tri : « Couvertures » et « Liste ».
add_filter(
	'render_block_woocommerce/catalog-sorting',
	function ( $html ) {
		return function_exists( 'er_facettes_page' ) && er_facettes_page() ? $html . er_rayonnage_bascule() : $html;
	}
);

// Sous la grille : « Voir 24 livres de plus » (le script la montre et cache les numéros de page).
add_filter(
	'render_block_core/query-pagination',
	function ( $html ) {
		if ( ! function_exists( 'er_facettes_page' ) || ! er_facettes_page() || false === strpos( $html, 'query-pagination-next' ) ) {
			return $html;
		}
		$q     = $GLOBALS['wp_query'];
		$total = (int) $q->found_posts;
		$par   = max( 1, (int) $q->get( 'posts_per_page' ) );
		$vus   = min( $total, max( 1, (int) $q->get( 'paged' ) ) * $par );
		$reste = min( $par, $total - $vus );
		if ( $reste <= 0 ) {
			return $html;
		}
		return $html . '<div class="er-suite">'
			/* translators: 1: les livres vus, 2: le total. */
			. '<p class="er-suite__compte">' . esc_html( sprintf( __( '%1$s livres sur %2$s', 'er-librairie' ), number_format_i18n( $vus ), number_format_i18n( $total ) ) ) . '</p>'
			. '<span class="er-suite__barre" aria-hidden="true"><span style="width: ' . esc_attr( (string) round( 100 * $vus / max( 1, $total ) ) ) . '%"></span></span>'
			/* translators: %s: le nombre de livres. */
			. '<button type="button" class="er-suite__plus">' . esc_html( sprintf( _n( 'Voir %s livre de plus', 'Voir %s livres de plus', $reste, 'er-librairie' ), number_format_i18n( $reste ) ) ) . '</button>'
			. '</div>';
	}
);

/* La fiche d'un rayon : le livre du moment -------------------------------------------------------------------- */

add_action(
	'product_cat_edit_form_fields',
	function ( $terme ) {
		$id  = (int) get_term_meta( $terme->term_id, 'er_livre_du_moment', true );
		$mot = (string) get_term_meta( $terme->term_id, 'er_mot_du_libraire', true );
		echo '<tr class="form-field"><th scope="row"><label for="er_livre_du_moment">' . esc_html__( 'Le livre du moment', 'er-librairie' ) . '</label></th><td>'
			. '<input type="number" min="0" name="er_livre_du_moment" id="er_livre_du_moment" value="' . esc_attr( $id ? (string) $id : '' ) . '" style="width: 8em">'
			. ( $id ? ' <a href="' . esc_url( (string) get_edit_post_link( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>' : '' )
			. '<p class="description">' . esc_html__( 'Le numéro du livre (dans l’adresse de sa fiche, post=…). Il ouvre la page du rayon, et de ses sous-rayons qui n’en ont pas.', 'er-librairie' ) . '</p></td></tr>'
			. '<tr class="form-field"><th scope="row"><label for="er_mot_du_libraire">' . esc_html__( 'Le mot du libraire', 'er-librairie' ) . '</label></th><td>'
			. '<textarea name="er_mot_du_libraire" id="er_mot_du_libraire" rows="3">' . esc_textarea( $mot ) . '</textarea>'
			. '<p class="description">' . esc_html__( 'Deux ou trois lignes sur ce livre, sous son titre.', 'er-librairie' ) . '</p></td></tr>';
	}
);

add_action(
	'edited_product_cat',
	function ( $terme_id ) {
		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		// Le formulaire du terme est déjà vérifié par WordPress (update-tag_…) avant edited_product_cat.
		if ( isset( $_POST['er_livre_du_moment'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_term_meta( $terme_id, 'er_livre_du_moment', absint( $_POST['er_livre_du_moment'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		if ( isset( $_POST['er_mot_du_libraire'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_term_meta( $terme_id, 'er_mot_du_libraire', sanitize_textarea_field( wp_unslash( $_POST['er_mot_du_libraire'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Le livre du moment d'un rayon : wp er moment <rayon> <livre>, le rayon par son slug, le livre par son numéro
	 * (0 pour n'en plus avoir).
	 *
	 * @param array $args Le rayon, le livre.
	 */
	WP_CLI::add_command(
		'er moment',
		function ( $args ) {
			if ( count( $args ) < 2 ) {
				WP_CLI::error( 'Usage : wp er moment <rayon> <livre>' );
			}
			$rayon = get_term_by( 'slug', sanitize_title( $args[0] ), 'product_cat' );
			if ( ! $rayon ) {
				WP_CLI::error( 'Rayon inconnu : ' . $args[0] );
			}
			$id = absint( $args[1] );
			if ( $id && 'product' !== get_post_type( $id ) ) {
				WP_CLI::error( 'Ce numéro n’est pas un livre : ' . $id );
			}
			update_term_meta( $rayon->term_id, 'er_livre_du_moment', $id );
			WP_CLI::success( $id ? 'Le livre du moment de « ' . $rayon->name . ' » : ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) : 'Plus de livre du moment pour « ' . $rayon->name . ' ».' );
		}
	);
}
