<?php
/**
 * Les Éditeurs réunis — thème.
 *
 * La direction artistique est provisoire (celle de la maquette Wix, en Montserrat) : toutes ses valeurs sont dans
 * theme.json (couleurs, police, tailles, espacements) et dans style.css (le reste). La changer, c'est changer ces deux
 * fichiers ; les gabarits et les compositions n'ont aucune couleur ni taille écrite en dur.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

require get_template_directory() . '/inc/recherche.php';
require get_template_directory() . '/inc/entete.php';
require get_template_directory() . '/inc/bande.php';
require get_template_directory() . '/inc/tete.php';
require get_template_directory() . '/inc/index.php';
require get_template_directory() . '/inc/expo-du-moment.php';
require get_template_directory() . '/inc/journal-defile.php';
require get_template_directory() . '/inc/trois-maisons.php';

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'editeurs-reunis', get_template_directory() . '/languages' );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_editor_style( er_feuilles_de_style() );
	}
);

/**
 * Les feuilles de style du thème, dans l'ordre où elles se chargent (assets/css/, numérotées).
 *
 * @return string[] Leurs chemins depuis le dossier du thème.
 */
function er_feuilles_de_style() {
	$feuilles = glob( get_template_directory() . '/assets/css/*.css' );
	sort( $feuilles );
	return array_map( fn( $f ) => 'assets/css/' . basename( $f ), $feuilles );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$version = wp_get_theme()->get( 'Version' );
		// Chaque feuille après la précédente, la date du fichier dans son adresse : un navigateur ne garde jamais
		// l'ancienne après un déploiement, même quand on oublie de changer le numéro de version.
		$avant = array();
		foreach ( er_feuilles_de_style() as $feuille ) {
			$nom = 'editeurs-reunis-' . basename( $feuille, '.css' );
			wp_enqueue_style( $nom, get_theme_file_uri( $feuille ), $avant, $version . '.' . filemtime( get_theme_file_path( $feuille ) ) );
			$avant = array( $nom );
		}
		wp_enqueue_script(
			'editeurs-reunis-menu-librairie',
			get_theme_file_uri( 'assets/js/menu-librairie.js' ),
			array(),
			$version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		// La bande de page (inc/bande.php) : accrochée, le nom de la page, le trait qui suit la lecture, les menus.
		wp_enqueue_script(
			'editeurs-reunis-bande',
			get_theme_file_uri( 'assets/js/bande.js' ),
			array(),
			$version . '.' . filemtime( get_theme_file_path( 'assets/js/bande.js' ) ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		// « Filtrer par » : la colonne de filtres des pages de la librairie (er-librairie/inc/facettes.php).
		if ( function_exists( 'er_facettes_page' ) && er_facettes_page() ) {
			wp_enqueue_script(
				'editeurs-reunis-facettes',
				get_theme_file_uri( 'assets/js/facettes.js' ),
				array(),
				$version . '.' . filemtime( get_theme_file_path( 'assets/js/facettes.js' ) ),
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
		// La grille des livres (er-librairie/inc/rayonnage.php) : « Ajouter » sans quitter la page, couvertures ou
		// liste, « Voir 24 livres de plus ».
		wp_enqueue_script(
			'editeurs-reunis-rayonnage',
			get_theme_file_uri( 'assets/js/rayonnage.js' ),
			array(),
			$version . '.' . filemtime( get_theme_file_path( 'assets/js/rayonnage.js' ) ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		// « Feuilleter » sur la fiche d'un livre (er/fiche-feuilleter) : la fenêtre des pages.
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_script(
				'editeurs-reunis-feuilleter',
				get_theme_file_uri( 'assets/js/feuilleter.js' ),
				array(),
				$version . '.' . filemtime( get_theme_file_path( 'assets/js/feuilleter.js' ) ),
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
		// L'en-tête plein écran : un clic sur une maison montre son menu, l'en-tête accroché, la barre d'informations.
		wp_enqueue_script(
			'editeurs-reunis-entete',
			get_theme_file_uri( 'assets/js/entete.js' ),
			array(),
			$version . '.' . filemtime( get_theme_file_path( 'assets/js/entete.js' ) ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_add_inline_script(
			'editeurs-reunis-entete',
			'window.erEnteteTextes = ' . wp_json_encode(
				array(
					'ouvert'  => __( 'Ouvert en ce moment', 'editeurs-reunis' ),
					/* translators: %s: l'heure de fermeture (« 19 h »). */
					'jusqua'  => __( 'la librairie, jusqu’à %s', 'editeurs-reunis' ),
					'ferme'   => __( 'Fermé en ce moment', 'editeurs-reunis' ),
					/* translators: 1: le jour de la réouverture (« mardi », « demain »), 2: l'heure (« 10 h »). */
					'reouvre' => __( 'réouverture %1$s à %2$s', 'editeurs-reunis' ),
					'demain'  => __( 'demain', 'editeurs-reunis' ),
					'jours'   => array( __( 'dimanche', 'editeurs-reunis' ), __( 'lundi', 'editeurs-reunis' ), __( 'mardi', 'editeurs-reunis' ), __( 'mercredi', 'editeurs-reunis' ), __( 'jeudi', 'editeurs-reunis' ), __( 'vendredi', 'editeurs-reunis' ), __( 'samedi', 'editeurs-reunis' ) ),
					'heureFr' => 'fr' === substr( determine_locale(), 0, 2 ),
				)
			) . ';',
			'before'
		);
		// Les pages d'exemple (les maquettes du canevas, dans la bêta) : réduites, sous 1 100 px.
		if ( is_page_template( array( 'page-exemple', 'page-exemple-librairie' ) ) ) {
			wp_enqueue_script(
				'editeurs-reunis-exemple',
				get_theme_file_uri( 'assets/js/exemple.js' ),
				array(),
				$version,
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
		// L'accueil, l'index : la colonne de droite montre la ligne en cours (patterns/une-index.php).
		if ( is_front_page() ) {
			wp_enqueue_script(
				'editeurs-reunis-une-index',
				get_theme_file_uri( 'assets/js/une-index.js' ),
				array(),
				$version . '.' . filemtime( get_theme_file_path( 'assets/js/une-index.js' ) ),
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
			// Le journal qui défile de côté : les deux flèches et le trait (inc/journal-defile.php).
			wp_enqueue_script(
				'editeurs-reunis-une-journal-defile',
				get_theme_file_uri( 'assets/js/une-journal-defile.js' ),
				array(),
				$version . '.' . filemtime( get_theme_file_path( 'assets/js/une-journal-defile.js' ) ),
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
			// Les trois maisons : la photo d'un lien, près de la souris (inc/trois-maisons.php).
			wp_enqueue_script(
				'editeurs-reunis-une-trois-maisons',
				get_theme_file_uri( 'assets/js/une-trois-maisons.js' ),
				array(),
				$version . '.' . filemtime( get_theme_file_path( 'assets/js/une-trois-maisons.js' ) ),
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
		// L'accueil, à la une : au téléphone, « Voir plus » sous l'essentiel de chaque module.
		wp_enqueue_script(
			'editeurs-reunis-une',
			get_theme_file_uri( 'assets/js/une.js' ),
			array(),
			$version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_add_inline_script(
			'editeurs-reunis-une',
			'window.erUneTextes = ' . wp_json_encode(
				array(
					'plus'  => __( 'Voir plus', 'editeurs-reunis' ),
					'moins' => __( 'Voir moins', 'editeurs-reunis' ),
				)
			) . ';',
			'before'
		);
		wp_enqueue_script(
			'editeurs-reunis-diaporama',
			get_theme_file_uri( 'assets/js/diaporama.js' ),
			array(),
			$version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_add_inline_script(
			'editeurs-reunis-diaporama',
			'window.erDiaporamaTextes = ' . wp_json_encode(
				array(
					'carrousel'   => __( 'carrousel', 'editeurs-reunis' ),
					'diapositive' => __( 'diapositive', 'editeurs-reunis' ),
					'alaune'      => __( 'À la une', 'editeurs-reunis' ),
					/* translators: 1: le rang de la diapositive, 2: le nombre de diapositives. */
					'position'    => __( '%1$s sur %2$s', 'editeurs-reunis' ),
					/* translators: %s: le rang de la diapositive. */
					'numero'      => __( 'Diapositive %s', 'editeurs-reunis' ),
					'precedente'  => __( 'Diapositive précédente', 'editeurs-reunis' ),
					'suivante'    => __( 'Diapositive suivante', 'editeurs-reunis' ),
				)
			) . ';',
			'before'
		);
	}
);

add_action(
	'init',
	function () {
		register_block_pattern_category( 'editeurs-reunis', array( 'label' => 'Les Éditeurs réunis' ) ); // Le nom de la maison ne se traduit pas.

		register_block_style(
			'core/button',
			array(
				'name'  => 'pilule-blanche',
				'label' => __( 'Pilule blanche', 'editeurs-reunis' ),
			)
		);
		register_block_style(
			'core/button',
			array(
				'name'  => 'fleche',
				'label' => __( 'Cerné, avec une flèche', 'editeurs-reunis' ),
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'carte-verre',
				'label' => __( 'Carte voilée', 'editeurs-reunis' ),
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'ambiance-papier',
				'label' => __( 'Ambiance papier (contenu patrimonial)', 'editeurs-reunis' ),
			)
		);
	}
);

/**
 * La maison de la page sur le corps de la page : er-maison-librairie, er-maison-ymca ou er-maison-centre (rien sur
 * l'accueil et les pages communes). L'en-tête en tire la maison soulignée et la couleur du menu du dessous.
 */
add_filter(
	'body_class',
	function ( $classes ) {
		$maison = er_maison();
		if ( $maison ) {
			$classes[] = 'er-maison-' . $maison;
		}
		if ( 'librairie' === $maison ) {
			$classes[] = 'er-dans-la-librairie';
		}
		return $classes;
	}
);

/**
 * L'adresse d'une image du thème, pour les compositions.
 *
 * @param string $nom Le nom du fichier dans assets/images.
 */
function er_image( $nom ) {
	return esc_url( get_theme_file_uri( 'assets/images/' . $nom ) );
}

/**
 * Une chaîne pour les attributs d'un bloc (le JSON de son commentaire) : guillemets et barres échappés, pour qu'une
 * traduction ne casse jamais la composition.
 *
 * @param string $texte Le texte, déjà traduit.
 */
function er_attr( $texte ) {
	return substr( wp_json_encode( (string) $texte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ), 1, -1 );
}

/* Les liens, dans la langue de la page -------------------------------------------------------------------------
 *
 * Les compositions désignent les pages, les livres et les rayons par leur chemin ou leur slug en français, la langue
 * de référence. Avec Polylang, ces fonctions rendent l'adresse de la traduction dans la langue de la page quand elle
 * existe, sinon celle de l'original ; sans Polylang, l'adresse française.
 */

/**
 * La traduction d'un contenu (page, article, livre) dans la langue de la page, ou le contenu lui-même.
 *
 * @param int $id L'identifiant du contenu en français.
 */
function er_traduction( $id ) {
	if ( function_exists( 'pll_get_post' ) ) {
		$traduit = pll_get_post( $id );
		if ( $traduit ) {
			return (int) $traduit;
		}
	}
	return (int) $id;
}

/**
 * La traduction d'un terme (rayon, mention, auteur) dans la langue de la page, ou le terme lui-même.
 *
 * @param WP_Term $terme Le terme en français.
 */
function er_traduction_terme( $terme ) {
	if ( function_exists( 'pll_get_term' ) ) {
		$traduit = pll_get_term( $terme->term_id );
		if ( $traduit && (int) $traduit !== (int) $terme->term_id ) {
			$autre = get_term( $traduit, $terme->taxonomy );
			if ( $autre && ! is_wp_error( $autre ) ) {
				return $autre;
			}
		}
	}
	return $terme;
}

/**
 * Le slug français d'un terme, quelle que soit la langue de la page : c'est lui que le thème connaît.
 *
 * @param WP_Term $terme Un terme, dans n'importe quelle langue.
 */
function er_slug_reference( $terme ) {
	if ( function_exists( 'pll_get_term' ) && function_exists( 'pll_default_language' ) ) {
		$original = pll_get_term( $terme->term_id, pll_default_language() );
		if ( $original && (int) $original !== (int) $terme->term_id ) {
			$autre = get_term( $original, $terme->taxonomy );
			if ( $autre && ! is_wp_error( $autre ) ) {
				return $autre->slug;
			}
		}
	}
	return $terme->slug;
}

/**
 * Le slug français de la page affichée, quelle que soit sa langue ; '' hors d'une page.
 */
function er_page_reference() {
	if ( ! is_page() ) {
		return '';
	}
	$id = get_queried_object_id();
	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) ) {
		$original = pll_get_post( $id, pll_default_language() );
		if ( $original ) {
			$id = $original;
		}
	}
	return (string) get_post_field( 'post_name', $id );
}

/**
 * Le chemin français de la page affichée (« ymca-press/histoire »), quelle que soit sa langue ; '' hors d'une page.
 */
function er_chemin_reference() {
	if ( ! is_page() ) {
		return '';
	}
	$id = get_queried_object_id();
	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) ) {
		$original = pll_get_post( $id, pll_default_language() );
		if ( $original ) {
			$id = $original;
		}
	}
	return trim( (string) get_page_uri( $id ), '/' );
}

/* Les trois maisons ---------------------------------------------------------------------------------------------
 *
 * La librairie, la maison d'édition YMCA-Press et le centre culturel. Chaque page appartient à l'une d'elles, ou à
 * aucune (l'accueil, le panier, le compte, les pages légales, la recherche) ; l'en-tête en tire sa couleur.
 */

/**
 * La maison de la page affichée : 'librairie', 'ymca', 'centre', ou ''.
 */
function er_maison() {
	if ( is_tax( 'er_sujet' ) ) {
		return ''; // Un sujet traverse les trois maisons : ses livres, ses rencontres, ses articles.
	}
	if ( ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) || 'promos' === er_page_reference() ) {
		return 'librairie';
	}
	if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_tax( 'er_genre' ) ) {
		return 'centre'; // Le journal du centre.
	}
	if ( is_singular( array( 'er_evenement', 'er_media', 'er_exposition' ) ) ) {
		return 'centre'; // Une rencontre, une exposition, une vidéo ou un podcast du centre.
	}
	$racine = strtok( er_chemin_reference(), '/' );
	if ( 'librairie' === $racine ) {
		return 'librairie'; // L'encyclopédie, sous la librairie.
	}
	if ( 'ymca-press' === $racine ) {
		return 'ymca';
	}
	if ( in_array( $racine, array( 'centre-culturel', 'journal', 'nous-trouver' ), true ) ) {
		return 'centre';
	}
	return '';
}

/**
 * Les pages d'YMCA-Press et du centre culturel, par leur chemin français : le menu du dessous à l'ordinateur, le menu
 * (≡) au téléphone. Les rayons de la librairie viennent, eux, des catégories de WooCommerce.
 */
function er_pages_des_maisons() {
	return array(
		'ymca'   => array(
			'ymca-press/'                 => _x( 'La maison d’édition', 'menu d’YMCA-Press', 'editeurs-reunis' ),
			'ymca-press/histoire/'        => _x( 'Un siècle d’histoire', 'menu d’YMCA-Press', 'editeurs-reunis' ),
			'ymca-press/catalogue/'       => _x( 'Le catalogue', 'menu d’YMCA-Press', 'editeurs-reunis' ),
			'ymca-press/amis-ymca-press/' => _x( 'Les Amis d’YMCA-Press', 'menu d’YMCA-Press', 'editeurs-reunis' ),
		),
		'centre' => array(
			'centre-culturel/'             => _x( 'Le centre', 'menu du centre culturel', 'editeurs-reunis' ),
			'centre-culturel/evenements/'  => _x( 'Nos événements', 'menu du centre culturel', 'editeurs-reunis' ),
			'centre-culturel/expositions/' => _x( 'Nos expositions', 'menu du centre culturel', 'editeurs-reunis' ),
			'centre-culturel/podcasts/'    => _x( 'Nos podcasts', 'menu du centre culturel', 'editeurs-reunis' ),
			'centre-culturel/videos/'      => _x( 'Nos vidéos', 'menu du centre culturel', 'editeurs-reunis' ),
			'journal/'                     => _x( 'Le journal du centre', 'menu du centre culturel', 'editeurs-reunis' ),
			'nous-trouver/'                => _x( 'Nous trouver', 'menu du centre culturel', 'editeurs-reunis' ),
		),
	);
}

/**
 * Le menu du dessous, sur la couleur de la maison, pour YMCA-Press et le centre culturel (la librairie a le sien,
 * avec ses rayons et leurs tiroirs). La page où l'on est est marquée ; un article du journal marque le journal.
 * L'en-tête rend le menu des deux maisons (inc/entete.php) : celui d'une autre maison que celle de la page ne marque
 * rien.
 *
 * @param string|null $maison 'ymca' ou 'centre' ; par défaut, la maison de la page.
 */
function er_sous_menu( $maison = null ) {
	$maison = is_string( $maison ) && '' !== $maison ? $maison : er_maison();
	$pages  = er_pages_des_maisons();
	if ( ! isset( $pages[ $maison ] ) ) {
		return '';
	}
	// Le menu d'une autre maison que celle de la page : rien n'y est marqué.
	$ici = '';
	if ( er_maison() === $maison ) {
		$ici = er_chemin_reference();
		if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_tax( 'er_genre' ) ) {
			$ici = 'journal';
		} elseif ( is_singular( 'er_evenement' ) ) {
			$ici = 'centre-culturel/evenements';
		} elseif ( is_singular( 'er_exposition' ) ) {
			$ici = 'centre-culturel/expositions';
		} elseif ( is_singular( 'er_media' ) ) {
			$ici = 'podcast' === get_post_meta( get_queried_object_id(), 'er_genre', true ) ? 'centre-culturel/podcasts' : 'centre-culturel/videos';
		}
	}
	$noms  = array(
		'ymca'   => _x( 'Maison d’édition YMCA-Press', 'menu du haut', 'editeurs-reunis' ),
		'centre' => _x( 'Le centre culturel', 'menu du haut', 'editeurs-reunis' ),
	);
	$liens = '';
	foreach ( $pages[ $maison ] as $chemin => $libelle ) {
		$courant = trim( $chemin, '/' ) === $ici ? ' aria-current="page"' : '';
		$liens  .= '<li><a href="' . er_lien( $chemin ) . '"' . $courant . '>' . esc_html( $libelle ) . '</a></li>';
	}
	return '<nav class="er-sous-menu er-sous-menu-' . esc_attr( $maison ) . '" aria-label="' . esc_attr( $noms[ $maison ] ) . '"><ul>' . $liens . '</ul></nav>';
}

/**
 * L'adresse d'une page du site d'après son chemin français. Calculée à chaque affichage : le même thème marche sous
 * n'importe quel nom de domaine, dans l'aperçu de WordPress Playground, et dans chaque langue.
 *
 * @param string $chemin Par exemple « librairie/ » ou « ymca-press/histoire/ ».
 */
function er_lien( $chemin = '' ) {
	$chemin = trim( $chemin, '/' );
	if ( '' === $chemin ) {
		return esc_url( function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ) );
	}
	$page = get_page_by_path( $chemin );
	if ( $page ) {
		return esc_url( get_permalink( er_traduction( $page->ID ) ) );
	}
	return esc_url( home_url( '/' . $chemin . '/' ) );
}

/**
 * Le lien d'un rayon de la librairie, s'il existe ; sinon la librairie.
 *
 * @param string $slug Le slug français du rayon.
 */
function er_rayon( $slug ) {
	$terme = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $terme && ! is_wp_error( $terme ) ) {
		$lien = get_term_link( er_traduction_terme( $terme ) );
		if ( ! is_wp_error( $lien ) ) {
			return esc_url( $lien );
		}
	}
	return er_lien( 'librairie/' );
}

/**
 * Le lien d'une mention (Nouveauté, Occasion, Très rare…), s'il existe ; sinon son adresse d'origine.
 *
 * @param string $slug Le slug français de la mention.
 */
function er_mention( $slug ) {
	$terme = get_term_by( 'slug', $slug, 'product_tag' );
	if ( $terme && ! is_wp_error( $terme ) ) {
		$lien = get_term_link( er_traduction_terme( $terme ) );
		if ( ! is_wp_error( $lien ) ) {
			return esc_url( $lien );
		}
	}
	return er_lien( 'mention/' . $slug . '/' );
}

/**
 * L'adresse d'un article d'après son slug français, ou le journal s'il n'existe pas.
 *
 * @param string $slug Le slug de l'article.
 */
function er_article( $slug ) {
	$article = get_page_by_path( $slug, OBJECT, 'post' );
	return $article ? esc_url( get_permalink( er_traduction( $article->ID ) ) ) : er_lien( 'journal/' );
}

/**
 * L'adresse d'un livre d'après son slug français, ou la librairie s'il n'existe pas.
 *
 * @param string $slug Le slug du livre.
 */
function er_livre( $slug ) {
	$livre = get_page_by_path( $slug, OBJECT, 'product' );
	return $livre ? esc_url( get_permalink( er_traduction( $livre->ID ) ) ) : er_lien( 'librairie/' );
}

/**
 * Le livre de la librairie (un produit WooCommerce) d'après son slug français, dans la langue de la page.
 *
 * @param string $slug Le slug du livre.
 * @return WC_Product|null
 */
function er_produit( $slug ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	$livre = get_page_by_path( $slug, OBJECT, 'product' );
	return $livre ? wc_get_product( er_traduction( $livre->ID ) ) : null;
}

/**
 * Le prix d'un livre, en texte (« 26,00 € »), pour le dire en petit à côté de « En librairie » ; « épuisé pour
 * l'instant » s'il n'est plus en stock. Lu dans la librairie à chaque affichage : la composition ne le recopie pas.
 *
 * @param string $slug Le slug du livre.
 */
function er_prix( $slug ) {
	$produit = er_produit( $slug );
	if ( ! $produit ) {
		return '';
	}
	if ( ! $produit->is_in_stock() ) {
		return __( 'épuisé pour l’instant', 'editeurs-reunis' );
	}
	if ( '' === $produit->get_price() ) {
		return '';
	}
	return html_entity_decode( wp_strip_all_tags( wc_price( wc_get_price_to_display( $produit ) ) ), ENT_QUOTES, 'UTF-8' );
}

/**
 * L'adresse qui met un livre au panier (sans JavaScript, celle de WooCommerce) ; sa fiche s'il n'est plus en stock ou
 * n'a pas de prix, ou la librairie s'il n'existe pas.
 *
 * @param string $slug Le slug du livre.
 */
function er_ajout_panier( $slug ) {
	$produit = er_produit( $slug );
	if ( ! $produit ) {
		return er_lien( 'librairie/' );
	}
	return esc_url( $produit->is_purchasable() && $produit->is_in_stock() ? $produit->add_to_cart_url() : get_permalink( $produit->get_id() ) );
}

/**
 * Le titre d'un groupe de sous-rayons dans le tiroir du menu (la méta « er_groupe », écrite en français), traduit.
 *
 * @param string $groupe Le titre en français.
 */
function er_groupe_libelle( $groupe ) {
	$libelles = array(
		'Par genre'   => __( 'Par genre', 'editeurs-reunis' ),
		'Par époque'  => __( 'Par époque', 'editeurs-reunis' ),
		'Par période' => __( 'Par période', 'editeurs-reunis' ),
		'Par thème'   => __( 'Par thème', 'editeurs-reunis' ),
		'Par âge'     => __( 'Par âge', 'editeurs-reunis' ),
		'Méthodes'    => __( 'Méthodes', 'editeurs-reunis' ),
		'Outils'      => __( 'Outils', 'editeurs-reunis' ),
		'Pensée'      => __( 'Pensée', 'editeurs-reunis' ),
		'Religion'    => __( 'Religion', 'editeurs-reunis' ),
		'Arts'        => __( 'Arts', 'editeurs-reunis' ),
	);
	return $libelles[ $groupe ] ?? $groupe;
}

/**
 * Le sélecteur de langue de la barre noire (l'extension de la librairie le fournit).
 */
add_shortcode(
	'er_langues_entete',
	function () {
		return function_exists( 'er_selecteur_langues' ) ? er_selecteur_langues() : '';
	}
);
add_shortcode( 'er_sous_menu', 'er_sous_menu' );

// Le compte, en picto, dans la barre noire (son nom pour les lecteurs d'écran).
add_shortcode(
	'er_compte_entete',
	function () {
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : er_lien( 'mon-compte/' );
		$picto = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="12" cy="8.5" r="3.6" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
		return '<a class="er-compte er-picto" href="' . esc_url( $url ) . '"' . ( function_exists( 'is_account_page' ) && is_account_page() ? ' aria-current="page"' : '' ) . '>' . $picto . '<span class="screen-reader-text">' . esc_html__( 'Compte', 'editeurs-reunis' ) . '</span></a>';
	}
);

// En tête du menu du téléphone (B3, 25 nov. 2026) : la recherche et le panier, qui ont quitté la barre ; la langue et
// le compte restent au bas du menu.
add_shortcode(
	'er_menu_outils',
	function () {
		$accueil = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
		$panier  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : er_lien( 'panier/' );
		$nombre  = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
		$loupe   = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M15.5 15.5L20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
		$sac     = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M5 8h14l-1.2 11.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>';
		return '<div class="er-menu-outils er-menu-telephone">'
			. '<form class="er-menu-recherche" role="search" method="get" action="' . esc_url( $accueil ) . '">' . $loupe
			. '<label class="screen-reader-text" for="er-menu-recherche">' . esc_html__( 'Rechercher', 'editeurs-reunis' ) . '</label>'
			. '<input type="search" id="er-menu-recherche" name="s" placeholder="' . esc_attr__( 'Un titre, un auteur, un ISBN…', 'editeurs-reunis' ) . '" autocomplete="off">'
			. '<input type="hidden" name="post_type" value="product"></form>'
			. '<a class="er-menu-panier" href="' . esc_url( $panier ) . '">' . $sac . '<span>' . esc_html__( 'Panier', 'editeurs-reunis' ) . '</span>'
			/* translators: %d: le nombre de livres au panier. */
			. '<span class="er-menu-panier-nombre" data-un="' . esc_attr( _n( '%d article', '%d articles', 1, 'editeurs-reunis' ) ) . '" data-plusieurs="' . esc_attr( _n( '%d article', '%d articles', 2, 'editeurs-reunis' ) ) . '">' . esc_html( sprintf( _n( '%d article', '%d articles', $nombre, 'editeurs-reunis' ), $nombre ) ) . '</span></a>'
			. '</div>';
	}
);

// Au bas du menu (≡) du téléphone, la langue : la barre n'a plus la place.
add_shortcode(
	'er_langues_menu',
	function () {
		if ( ! function_exists( 'er_selecteur_langues' ) ) {
			return '';
		}
		return '<div class="er-menu-langues er-menu-telephone"><span class="er-menu-langues-titre">' . esc_html__( 'Langue', 'editeurs-reunis' ) . '</span>' . er_selecteur_langues() . '</div>';
	}
);


// Un code court dans un gabarit n'est pas déplié quand il vient d'une composition (les compositions passent après) :
// on déplie les nôtres dans leur bloc HTML.
add_filter(
	'render_block_core/html',
	function ( $html ) {
		return preg_match( '/\[er_(langues_entete|langues_menu|menu_outils|sous_menu|compte_entete|infos|lignes|index|expo_du_moment|journal_defile|trois_maisons)\]/', $html ) ? do_shortcode( $html ) : $html;
	}
);

/**
 * WooCommerce accroche de lui-même un compte et un panier après le menu de l'en-tête : l'en-tête a déjà les siens.
 */
add_filter(
	'hooked_block_types',
	function ( $blocs, $position, $ancre ) {
		if ( 'core/navigation' === $ancre ) {
			$blocs = array_values( array_diff( $blocs, array( 'woocommerce/customer-account', 'woocommerce/mini-cart' ) ) );
		}
		return $blocs;
	},
	20,
	3
);

/**
 * La fiche d'un livre dans une grille : la couverture, ses mentions (Nouveauté, Très rare…), le titre, l'auteur, le
 * prix, et « Rupture de stock » quand il n'y en a plus. Une seule définition pour l'accueil, la librairie, les pages
 * d'un auteur ou d'une langue, et « Du même rayon ».
 */
function er_fiche_livre() {
	if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'er/carte-livre' ) ) {
		// Sans l'extension : les blocs de WooCommerce.
		return '<!-- wp:woocommerce/product-template {"className":"er-livres"} -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","aspectRatio":"2/3","scale":"cover","className":"er-couverture"} /-->
<!-- wp:post-title {"level":3,"isLink":true,"className":"er-livre-titre","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/product-price {"className":"er-livre-prix"} /-->
<!-- /wp:woocommerce/product-template -->';
	}
	// La carte d'un livre (er-librairie/inc/rayonnage.php) : la couverture entière, les mentions, le titre et sa
	// traduction, l'auteur, la langue, l'année, la maison, qui en parle, le prix et « Ajouter ».
	return '<!-- wp:woocommerce/product-template {"className":"er-livres"} -->
<!-- wp:er/carte-livre /-->
<!-- /wp:woocommerce/product-template -->';
}

/**
 * La couverture typographique : pour un livre sans photo, ou dont la photo est trop petite pour rester nette
 * (moins de 450 px de haut : er_couverture_qualite(), dans l'extension). L'auteur en haut, le titre, l'éditeur en bas
 * avec le point de sa maison ; un livre d'YMCA-Press sur l'aplat brun de la maison. Elle prend la place de la photo
 * dans les grilles et sur la fiche, jusqu'à ce qu'une meilleure image arrive.
 *
 * @param WC_Product $produit Le livre.
 */
function er_couverture_typo( $produit ) {
	$auteurs = wp_get_post_terms( $produit->get_id(), 'auteur', array( 'fields' => 'names' ) );
	$auteur  = is_array( $auteurs ) ? implode( ', ', array_slice( $auteurs, 0, 2 ) ) : '';
	$editeur = (string) $produit->get_meta( 'er_editeur' );
	$ymca    = (bool) preg_match( '/ymca|имка/iu', $editeur );
	$titre   = $produit->get_name();
	$russe   = (bool) preg_match( '/\p{Cyrillic}/u', $titre );
	return '<span class="er-couverture-typo' . ( $ymca ? ' er-couverture-typo--ymca' : '' ) . '"><span class="er-couverture-typo__page">'
		. ( $auteur ? '<span class="er-couverture-typo__auteur"' . ( preg_match( '/\p{Cyrillic}/u', $auteur ) ? ' lang="ru"' : '' ) . '>' . esc_html( $auteur ) . '</span>' : '' )
		. '<span class="er-couverture-typo__titre"' . ( $russe ? ' lang="ru"' : '' ) . '>' . esc_html( wptexturize( $titre ) ) . '</span>'
		. ( $editeur ? '<span class="er-couverture-typo__editeur">' . esc_html( $editeur ) . '</span>' : '' )
		. '</span></span>';
}

/**
 * Faut-il la couverture typographique pour ce livre ? Sans l'extension, jamais (la photo telle quelle).
 *
 * @param WC_Product $produit Le livre.
 */
function er_couverture_a_remplacer( $produit ) {
	return $produit && function_exists( 'er_couverture_qualite' ) && in_array( er_couverture_qualite( $produit ), array( 'petite', 'absente' ), true );
}

// Dans les grilles : le bloc de l'image du livre.
add_filter(
	'render_block_woocommerce/product-image',
	function ( $html, $bloc, $instance ) {
		$id      = isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : get_the_ID();
		$produit = $id ? wc_get_product( $id ) : null;
		if ( ! er_couverture_a_remplacer( $produit ) ) {
			return $html;
		}
		$classe = trim( 'wc-block-components-product-image wp-block-woocommerce-product-image ' . ( $bloc['attrs']['className'] ?? '' ) );
		return '<div class="' . esc_attr( $classe ) . '"><a href="' . esc_url( $produit->get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . er_couverture_typo( $produit ) . '</a></div>';
	},
	10,
	3
);

// Sur la fiche : l'image principale de la galerie (ou son espace réservé, quand il n'y a pas d'image).
add_filter(
	'woocommerce_single_product_image_thumbnail_html',
	function ( $html, $image ) {
		global $product;
		if ( ! $product || ( $image && (int) $image !== (int) $product->get_image_id() ) || ! er_couverture_a_remplacer( $product ) ) {
			return $html;
		}
		return '<div class="woocommerce-product-gallery__image er-fiche-typo">' . er_couverture_typo( $product ) . '</div>';
	},
	10,
	2
);

/**
 * Un livre sans prix (épuisé, ou prix à confirmer) n'affiche rien plutôt qu'un prix vide.
 */
add_filter(
	'woocommerce_empty_price_html',
	function () {
		return '';
	}
);

/**
 * Les vignettes de la librairie au format d'une couverture (2:3), sans recadrage carré.
 */
add_filter(
	'woocommerce_get_image_size_thumbnail',
	function () {
		return array(
			'width'  => 420,
			'height' => 630,
			'crop'   => 0,
		);
	}
);
add_filter(
	'woocommerce_get_image_size_single',
	function () {
		return array(
			'width'  => 720,
			'height' => 0,
			'crop'   => 0,
		);
	}
);
