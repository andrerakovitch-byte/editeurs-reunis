<?php
/**
 * Remplit un WordPress neuf avec le contenu de l'ébauche : les réglages, les pages, les rayons, les livres de la
 * maquette Wix et leurs couvertures, le communiqué.
 *
 * À lancer une fois, sur un site vide : `wp eval-file wp-content/plugins/er-librairie/donnees/semer.php`, ou dans
 * WordPress Playground (étape runPHP du plan d'aperçu). Relancé, il ne crée rien en double : chaque page, chaque
 * livre est retrouvé par son slug.
 *
 * Les prix, les mentions et les statuts viennent de la maquette Wix. Les présentations des livres sont des
 * brouillons d'une phrase, à remplacer ; les couvertures « à venir » aussi.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

/* Les réglages ------------------------------------------------------------------------------------------------- */

$reglages = array(
	'blogname'                          => 'Les Éditeurs réunis',
	'blogdescription'                   => 'Librairie, maison d’édition YMCA-Press et Centre culturel Alexandre Soljenitsyne',
	'timezone_string'                   => 'Europe/Paris',
	'date_format'                       => 'j F Y',
	'time_format'                       => 'G\hi',
	'start_of_week'                     => 1,
	'permalink_structure'               => '/%postname%/',
	'woocommerce_currency'              => 'EUR',
	'woocommerce_currency_pos'          => 'right_space',
	'woocommerce_price_decimal_sep'     => ',',
	'woocommerce_price_thousand_sep'    => ' ',
	'woocommerce_price_num_decimals'    => 2,
	'woocommerce_default_country'       => 'FR',
	'woocommerce_store_address'         => '11, rue de la Montagne-Sainte-Geneviève',
	'woocommerce_store_city'            => 'Paris',
	'woocommerce_store_postcode'        => '75005',
	'woocommerce_coming_soon'           => 'no',
	'woocommerce_store_pages_only'      => 'no',
	'woocommerce_enable_reviews'        => 'no',
	'woocommerce_default_catalog_orderby' => 'date',
	'woocommerce_weight_unit'           => 'g',
	'woocommerce_dimension_unit'        => 'mm',
	'woocommerce_onboarding_profile'    => array( 'skipped' => true ),
	'woocommerce_task_list_hidden'      => 'yes',
	'woocommerce_show_marketplace_suggestions' => 'no',
	'woocommerce_permalinks'            => array(
		'product_base'           => '/livre',
		'category_base'          => 'rayon',
		'tag_base'               => 'mention',
		'attribute_base'         => '',
		'use_verbose_page_rules' => false,
	),
);
foreach ( $reglages as $nom => $valeur ) {
	update_option( $nom, $valeur );
}

/* Les pages ---------------------------------------------------------------------------------------------------- */

/**
 * Crée une page, ou la retrouve par son chemin.
 *
 * @param string $titre   Le titre.
 * @param string $slug    Le slug.
 * @param string $contenu Le contenu, en blocs.
 * @param int    $parent  La page parente.
 * @param int    $ordre   Sa place parmi ses sœurs.
 */
function er_semer_page( $titre, $slug, $contenu = '', $parent = 0, $ordre = 0 ) {
	$chemin = $slug;
	if ( $parent ) {
		$chemin = get_page_uri( $parent ) . '/' . $slug;
	}
	$existante = get_page_by_path( $chemin );
	$donnees   = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $titre,
		'post_name'    => $slug,
		'post_content' => $contenu,
		'post_parent'  => $parent,
		'menu_order'   => $ordre,
	);
	if ( $existante ) {
		$donnees['ID'] = $existante->ID;
		return wp_update_post( $donnees );
	}
	return wp_insert_post( $donnees );
}

/**
 * Un paragraphe en blocs.
 *
 * @param string $texte Le texte (HTML simple).
 */
function er_p( $texte ) {
	return "<!-- wp:paragraph -->\n<p>{$texte}</p>\n<!-- /wp:paragraph -->\n\n";
}

/**
 * Un intertitre en blocs.
 *
 * @param string $texte Le texte.
 */
function er_h2( $texte ) {
	return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$texte}</h2>\n<!-- /wp:heading -->\n\n";
}

$a_venir = er_p( '<em>Cette page est une ébauche : son texte est à écrire.</em>' );

// L'accueil est le gabarit front-page du thème ; la page « Accueil » ne sert qu'à le désigner.
$accueil = er_semer_page( 'Accueil', 'accueil' );
$journal = er_semer_page( 'Le journal du centre', 'journal' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $accueil );
update_option( 'page_for_posts', $journal );

// Les pages de WooCommerce (boutique, panier, commande, compte), si son installation ne les a pas encore faites.
if ( class_exists( 'WC_Install' ) ) {
	WC_Install::create_pages();
}

// La librairie : la page boutique de WooCommerce, renommée.
$boutique = (int) get_option( 'woocommerce_shop_page_id' );
if ( $boutique ) {
	wp_update_post(
		array(
			'ID'         => $boutique,
			'post_title' => 'Librairie',
			'post_name'  => 'librairie',
		)
	);
} else {
	$boutique = er_semer_page( 'Librairie', 'librairie' );
	update_option( 'woocommerce_shop_page_id', $boutique );
}
foreach ( array(
	'woocommerce_cart_page_id'      => array( 'Panier', 'panier' ),
	'woocommerce_checkout_page_id'  => array( 'Commande', 'commande' ),
	'woocommerce_myaccount_page_id' => array( 'Mon compte', 'mon-compte' ),
) as $option => $page ) {
	$id = (int) get_option( $option );
	if ( $id ) {
		wp_update_post(
			array(
				'ID'         => $id,
				'post_title' => $page[0],
				'post_name'  => $page[1],
			)
		);
	}
}

$ymca = er_semer_page(
	'Maison d’édition YMCA-Press',
	'ymca-press',
	er_p( 'Depuis un siècle, YMCA-Press publie la littérature et la pensée russes en exil.' ) . $a_venir
);
er_semer_page(
	'Un siècle d’histoire',
	'histoire',
	er_p( 'L’histoire centenaire des éditions YMCA-Press, cœur vibrant de la littérature russe du XX<sup>e</sup>&nbsp;siècle, entre Bounine, Akhmatova, Mandelstam, Boulgakov et Soljenitsyne.' ) . $a_venir,
	$ymca,
	1
);
er_semer_page( 'Le catalogue', 'catalogue', $a_venir, $ymca, 2 );
er_semer_page(
	'Les Amis d’YMCA-Press',
	'amis-ymca-press',
	er_p( 'Notre équipe est uniquement composée de bénévoles. Rejoignez l’Association des Amis d’YMCA-Press pour nous aider à faire vivre la librairie, la maison d’édition et le centre.' ) . $a_venir,
	$ymca,
	3
);

$centre = er_semer_page(
	'Le centre culturel',
	'centre-culturel',
	er_p( 'Le Centre culturel Alexandre Soljenitsyne organise des rencontres et des expositions, et publie des podcasts et des vidéos autour de la littérature et de l’histoire russes.' ) . $a_venir
);
er_semer_page( 'Nos événements', 'evenements', $a_venir, $centre, 1 );
er_semer_page( 'Nos expositions', 'expositions', $a_venir, $centre, 2 );
er_semer_page(
	'Nos podcasts',
	'podcasts',
	er_h2( 'Les secrets de <em>L’Archipel du Goulag</em>' ) . er_p( 'Un podcast inédit pour comprendre les mystères d’un livre hors du commun.' ) . $a_venir,
	$centre,
	3
);
er_semer_page( 'Nos vidéos', 'videos', $a_venir, $centre, 4 );

er_semer_page(
	'Nous trouver',
	'nous-trouver',
	er_h2( 'La librairie' )
	. er_p( '11, rue de la Montagne-Sainte-Geneviève, 75005 Paris<br>Métro Maubert–Mutualité (ligne 10)' )
	. er_p( 'Du mardi au samedi, de 10&nbsp;h&nbsp;30 à 18&nbsp;h&nbsp;30. Fermée le dimanche et le lundi.' )
	. er_p( '<a href="mailto:les-editeurs-reunis@orange.fr">les-editeurs-reunis@orange.fr</a>' )
);

foreach ( array(
	array( 'Conditions générales de vente', 'conditions-generales-de-vente' ),
	array( 'Livraison et retours', 'livraison-et-retours' ),
	array( 'Questions fréquentes', 'faq' ),
	array( 'Mentions légales', 'mentions-legales' ),
) as $page ) {
	er_semer_page( $page[0], $page[1], $a_venir );
}
$confidentialite = (int) get_option( 'wp_page_for_privacy_policy' );
if ( $confidentialite && get_post( $confidentialite ) ) {
	wp_update_post(
		array(
			'ID'          => $confidentialite,
			'post_title'  => 'Politique de confidentialité',
			'post_name'   => 'politique-de-confidentialite',
			'post_status' => 'publish',
		)
	);
} else {
	update_option( 'wp_page_for_privacy_policy', er_semer_page( 'Politique de confidentialité', 'politique-de-confidentialite', $a_venir ) );
}

// Les pages d'exemple de WordPress.
foreach ( array( 'sample-page', 'hello-world' ) as $slug ) {
	$exemple = get_page_by_path( $slug, OBJECT, array( 'page', 'post' ) );
	if ( $exemple ) {
		wp_delete_post( $exemple->ID, true );
	}
}

/* Le communiqué ------------------------------------------------------------------------------------------------ */

$communiques = term_exists( 'communiques', 'category' );
if ( ! $communiques ) {
	$communiques = wp_insert_term( 'Communiqués', 'category', array( 'slug' => 'communiques' ) );
}
$slug_communique = 'a-propos-de-linvasion-de-lukraine-par-la-federation-de-russie';
if ( ! get_page_by_path( $slug_communique, OBJECT, 'post' ) ) {
	wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => 'À propos de l’invasion de l’Ukraine par la Fédération de Russie',
			'post_name'     => $slug_communique,
			'post_date'     => '2022-03-01 10:00:00',
			'post_excerpt'  => 'Communiqué de presse du 28 février 2022.',
			'post_content'  => er_p( 'Communiqué de presse du 28&nbsp;février 2022, signé par Mélanie Rakovitch.' ) . er_p( '<em>Le texte du communiqué est à reprendre de l’ancien site.</em>' ),
			'post_category' => array( (int) $communiques['term_id'] ),
		)
	);
}
wp_update_term( 1, 'category', array( 'name' => 'Le journal', 'slug' => 'journal' ) );

/* Les rayons, les langues, les mentions ----------------------------------------------------------------------- */

/**
 * Crée un terme, ou le retrouve par son slug.
 *
 * @param string $nom      Le nom.
 * @param string $slug     Le slug.
 * @param string $taxonomie La taxonomie.
 * @param string $description La description.
 * @param int    $parent   Le terme parent (un rayon et ses sous-rayons).
 * @param int    $ordre    Sa place dans le menu de la librairie.
 */
function er_semer_terme( $nom, $slug, $taxonomie, $description = '', $parent = 0, $ordre = 0 ) {
	$donnees = array(
		'name'        => $nom,
		'description' => $description,
		'parent'      => $parent,
	);
	$terme   = get_term_by( 'slug', $slug, $taxonomie );
	if ( $terme ) {
		wp_update_term( $terme->term_id, $taxonomie, $donnees );
		$id = (int) $terme->term_id;
	} else {
		$terme = wp_insert_term( $nom, $taxonomie, $donnees + array( 'slug' => $slug ) );
		$id    = is_wp_error( $terme ) ? 0 : (int) $terme['term_id'];
	}
	if ( $id && 'product_cat' === $taxonomie ) {
		update_term_meta( $id, 'order', $ordre );
	}
	return $id;
}

// Les rayons et leurs sous-rayons, dans l'ordre du menu de la librairie (la maquette Wix : Littérature, Apprendre le
// russe, Arts, Jeunesse). Les sous-rayons d'Apprendre le russe et d'Arts sont une proposition, Jeunesse n'en a pas.
$rayons = array(
	'litterature'        => array(
		'Littérature',
		'Les classiques et les contemporains, en français et en russe.',
		array(
			'memoires'               => array( 'Mémoires', 'Témoignages, souvenirs, le Goulag et l’exil.' ),
			'nouvelles-et-recits'    => array( 'Nouvelles et récits', '' ),
			'poesie'                 => array( 'Poésie', 'De Blok à Goumilev, et les anthologies bilingues d’YMCA-Press.' ),
			'recit-autobiographique' => array( 'Récit autobiographique', '' ),
			'recit-de-voyage'        => array( 'Récit de voyage', '' ),
			'romans'                 => array( 'Romans', '' ),
			'theatre'                => array( 'Théâtre', '' ),
			'essais'                 => array( 'Essais', '' ),
		),
	),
	'apprendre-le-russe' => array(
		'Apprendre le russe',
		'Des manuels, des dictionnaires et des guides pour vos voyages.',
		array(
			'manuels'       => array( 'Manuels', '' ),
			'dictionnaires' => array( 'Dictionnaires', '' ),
		),
	),
	'arts'               => array(
		'Arts',
		'Le cinéma, la musique, la peinture russes.',
		array(
			'cinema'  => array( 'Cinéma', 'Des scénarios et des livres sur le cinéma russe.' ),
			'musique' => array( 'Musique', '' ),
		),
	),
	'jeunesse'           => array( 'Jeunesse', '', array() ),
);
$id_rayons = array();
$ordre     = 0;
foreach ( $rayons as $slug => $rayon ) {
	$id_rayons[ $slug ] = er_semer_terme( $rayon[0], $slug, 'product_cat', $rayon[1], 0, ++$ordre );
	foreach ( $rayon[2] as $sous_slug => $sous ) {
		$id_rayons[ $sous_slug ] = er_semer_terme( $sous[0], $sous_slug, 'product_cat', $sous[1], $id_rayons[ $slug ], ++$ordre );
	}
}
// Le rayon par défaut de WooCommerce, vide, ne doit pas paraître.
$non_classe = get_term_by( 'slug', 'uncategorized', 'product_cat' );
if ( $non_classe ) {
	wp_update_term( $non_classe->term_id, 'product_cat', array( 'name' => 'Sans rayon', 'slug' => 'sans-rayon' ) );
}

$langues = array(
	'francais' => er_semer_terme( 'En français', 'francais', 'langue' ),
	'russe'    => er_semer_terme( 'En russe', 'russe', 'langue' ),
);

$mentions = array(
	'nouveaute'           => 'Nouveauté',
	'nouvelle-edition'    => 'Nouvelle édition',
	'nouvelle-traduction' => 'Nouvelle traduction',
	'tres-rare'           => 'Très rare',
);
foreach ( $mentions as $slug => $nom ) {
	er_semer_terme( $nom, $slug, 'product_tag' );
}

/* Les livres --------------------------------------------------------------------------------------------------- */

$dossier = __DIR__ . '/couvertures/';

// Dans l'ordre de la maquette : le premier est le plus récent.
$livres = array(
	array( 'convoi-pour-samarcande', 'Convoi pour Samarcande', array( 'Gouzel Iakhina' ), '25', 'romans', 'francais', array( 'nouveaute' ), true, 'Le roman de Gouzel Iakhina sur la famine de 1921 : un train d’enfants affamés traverse la Russie jusqu’à Samarcande.' ),
	array( 'scenarii-zviaguintsev', 'Сценарии кинофильмов Андрея Звягинцева', array( 'Андрей Звягинцев' ), '', 'cinema', 'russe', array(), false, 'Les scénarios des films d’Andreï Zviaguintsev, en russe.' ),
	array( 'larchipel-du-goulag-cinquante-ans-apres', 'L’Archipel du Goulag, cinquante ans après', array( 'Alexandre Soljenitsyne', 'Georges Nivat' ), '', 'memoires', 'francais', array( 'nouveaute' ), false, 'Morceaux choisis de <em>L’Archipel du Goulag</em>, 1973-2023, sous la direction de Georges Nivat.' ),
	array( 'put-filma', 'Путь фильма', array( 'Семён Ляшенко' ), '15', 'cinema', 'russe', array(), true, 'Un livre sur le cinéma, en russe.' ),
	array( 'tchevengour', 'Tchevengour', array( 'Andreï Platonov' ), '24', 'romans', 'francais', array( 'nouvelle-edition' ), true, 'Le grand roman d’Andreï Platonov, écrit à la fin des années 1920 et publié en URSS seulement en 1988.' ),
	array( 'le-docteur-jivago', 'Le Docteur Jivago', array( 'Boris Pasternak' ), '26', 'romans', 'francais', array( 'nouvelle-traduction' ), true, 'Le roman de Boris Pasternak, publié pour la première fois en Italie en 1957, dans une nouvelle traduction.' ),
	array( 'voix-et-visages-du-goulag', 'Voix et visages du Goulag', array(), '35', 'memoires', 'francais', array(), true, '' ),
	array( 'les-douze', 'Les Douze', array( 'Alexandre Blok' ), '20', 'poesie', 'francais', array(), true, 'Le poème d’Alexandre Blok sur la révolution, écrit en janvier 1918.' ),
	array( 'ekaterina-ivanovna', 'Ekatérina Ivanovna, suivi de Requiem', array( 'Léonid Andréïev' ), '20', 'theatre', 'francais', array(), true, 'Deux pièces de Léonid Andréïev.' ),
	array( 'les-enfants-de-la-volga', 'Les Enfants de la Volga', array( 'Gouzel Iakhina' ), '24', 'romans', 'francais', array(), true, 'Le deuxième roman de Gouzel Iakhina, chez les Allemands de la Volga.' ),
	array( 'poesies-choisies-goumilev', 'Poésies choisies', array( 'Nikolaï Goumilev' ), '19', 'poesie', 'francais', array(), true, 'Un choix de poèmes de Nikolaï Goumilev.' ),
	array( 'russkaia-poezia', 'Русская поэзия', array(), '200', 'poesie', 'russe', array( 'tres-rare' ), true, 'Un exemplaire rare, à voir à la librairie.' ),
	array( 'dostoievski-zweig', 'Dostoïevski', array( 'Stefan Zweig' ), '7', 'essais', 'francais', array(), true, 'L’essai de Stefan Zweig sur Dostoïevski, tiré de <em>Trois Maîtres</em>.' ),
	array( 'moussorgski-calvocoressi', 'Modeste Moussorgski', array( 'Michel D. Calvocoressi' ), '20', 'musique', 'francais', array(), true, 'La vie du compositeur, par Michel D. Calvocoressi.' ),
	array( 'notes-dun-medecin', 'Notes d’un médecin', array( 'Vikenti Veressaïev' ), '', 'recit-autobiographique', 'francais', array(), true, 'Un jeune médecin raconte ses débuts à l’hôpital, ses doutes et ses erreurs. À sa parution, en 1901, le livre fit scandale ; il n’a rien perdu de sa franchise. Traduit du russe par Julie Bouvard, préface de Dimitri Bortnikov.' ),
);

$fichiers_couvertures = array(
	'larchipel-du-goulag-cinquante-ans-apres' => 'archipel-cinquante-ans.jpg',
	'ekaterina-ivanovna'                      => 'ekaterina-ivanovna.jpg',
);

$maintenant = time();
foreach ( $livres as $rang => $livre ) {
	list( $slug, $titre, $auteurs, $prix, $rayon, $langue, $mentions_livre, $en_stock, $presentation ) = $livre;

	$existant = get_page_by_path( $slug, OBJECT, 'product' );
	$produit  = $existant ? wc_get_product( $existant->ID ) : new WC_Product_Simple();

	$produit->set_name( $titre );
	$produit->set_slug( $slug );
	$produit->set_status( 'publish' );
	$produit->set_catalog_visibility( 'visible' );
	$produit->set_regular_price( $prix );
	$produit->set_description( $presentation ? er_p( $presentation ) : '' );
	$produit->set_short_description( '' );
	$produit->set_stock_status( $en_stock ? 'instock' : 'outofstock' );
	$produit->set_category_ids( array( $id_rayons[ $rayon ] ) );
	$produit->set_tag_ids(
		array_values(
			array_filter(
				array_map(
					function ( $mention ) {
						$terme = get_term_by( 'slug', $mention, 'product_tag' );
						return $terme ? (int) $terme->term_id : 0;
					},
					$mentions_livre
				)
			)
		)
	);
	$produit->set_date_created( $maintenant - $rang * DAY_IN_SECONDS );
	$id = $produit->save();

	wp_set_object_terms( $id, $auteurs, 'auteur' );
	wp_set_object_terms( $id, array( $langues[ $langue ] ), 'langue' );

	// La couverture.
	if ( ! $produit->get_image_id() ) {
		$fichier = $dossier . ( $fichiers_couvertures[ $slug ] ?? $slug . '.jpg' );
		if ( file_exists( $fichier ) ) {
			$copie = wp_tempnam( basename( $fichier ) );
			copy( $fichier, $copie );
			$image = media_handle_sideload(
				array(
					'name'     => 'couverture-' . $slug . '.jpg',
					'tmp_name' => $copie,
				),
				$id,
				'Couverture de ' . $titre
			);
			if ( ! is_wp_error( $image ) ) {
				update_post_meta( $image, '_wp_attachment_image_alt', 'Couverture de ' . wp_strip_all_tags( $titre ) );
				$produit = wc_get_product( $id );
				$produit->set_image_id( $image );
				$produit->save();
			}
		}
	}
}

// Les rayons de la première ébauche, remplacés par l'arborescence de la maquette.
foreach ( array( 'litterature-russe', 'histoire', 'essais-et-biographies' ) as $ancien ) {
	$terme = get_term_by( 'slug', $ancien, 'product_cat' );
	if ( $terme && 0 === (int) $terme->count ) {
		wp_delete_term( $terme->term_id, 'product_cat' );
	}
}

// Les promotions : les livres soldés, ou une phrase quand il n'y en a pas.
er_semer_page(
	'Promos',
	'promos',
	'<!-- wp:woocommerce/product-collection {"queryId":61,"query":{"perPage":20,"pages":0,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":true,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":5,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide">' . ( function_exists( 'er_fiche_livre' ) ? er_fiche_livre() : '' ) . '
<!-- wp:woocommerce/product-collection-no-results -->
<!-- wp:paragraph -->
<p>Pas de promotion en ce moment.</p>
<!-- /wp:paragraph -->
<!-- /wp:woocommerce/product-collection-no-results -->
</div>
<!-- /wp:woocommerce/product-collection -->'
);

flush_rewrite_rules();
wc_delete_product_transients();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::success( count( $livres ) . ' livres, les pages et les réglages sont en place.' );
}
