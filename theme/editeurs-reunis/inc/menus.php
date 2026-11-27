<?php
/**
 * L'en-tête retenu le 26 novembre 2026, sur tout le site (canevas « Les pages intérieures, dans la direction »,
 * planches H, I, J, N, P5 et R1) :
 *
 * - la barre du nom : le nom, puis Librairie, Maison d'édition, Centre culturel, Journal en 20 px, à 16 px l'un de
 *   l'autre, sans chevron ; leur menu s'ouvre au survol (et au clic, au doigt) ; les autres noms pâlissent ; puis la
 *   loupe et le sac, à 20 px dans des ronds de 40 px ; un filet sous la barre, toujours ;
 * - les menus, de 446 px : la Librairie en panneau (ses rayons, les sous-rayons du rayon survolé, et le dernier livre
 *   arrivé du rayon ou du sous-rayon survolé), les trois autres en grands mots, avec la photo de la page survolée ;
 * - le sac ouvre le panier et le compte, comme chez Apple ; la loupe, la recherche (inc/recherche.php) ;
 * - au téléphone et jusqu'à 1 360 px, le menu (≡) ouvre le plan du site en plein écran (er_em_plan()).
 *
 * Les données des menus viennent d'inc/essai-menus.php (er_em_donnees()), écrit pour les pages d'essai.
 *
 * @package editeurs-reunis
 */

/**
 * Le dernier livre arrivé d'un rayon : en stock, avec une couverture, sans ceux déjà montrés dans ce rayon ; en
 * Jeunesse, rien qui soit aussi rangé en Pensée et religion.
 *
 * @param string $slug     Le rayon ou le sous-rayon.
 * @param array  $exclus   Les livres déjà pris.
 * @param bool   $jeunesse Le rayon est la Jeunesse.
 */
function er_menu_dernier_livre( $slug, $exclus = array(), $jeunesse = false ) {
	$taxo = array(
		array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $slug,
		),
	);
	if ( $jeunesse ) {
		$taxo[]             = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => 'pensee-et-religion',
			'operator' => 'NOT IN',
		);
		$taxo['relation'] = 'AND';
	}
	$ids = get_posts(
		array(
			'post_type'     => 'product',
			'post_status'   => 'publish',
			'numberposts'   => 1,
			'fields'        => 'ids',
			'no_found_rows' => true,
			'post__not_in'  => array_map( 'intval', $exclus ),
			'tax_query'     => $taxo, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_query'    => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Le livre de chaque rayon et de chaque sous-rayon, gardé six heures : la clé du rayon (rayon-…) ou du sous-rayon
 * (sous-…) → l'identifiant du livre.
 */
function er_menu_livres() {
	$livres = get_transient( 'er_menu_livres_v1' );
	if ( is_array( $livres ) ) {
		return $livres;
	}
	$livres = array();
	$rayons = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
		)
	);
	foreach ( is_wp_error( $rayons ) ? array() : $rayons as $r ) {
		if ( in_array( $r->slug, array( 'uncategorized', 'sans-rayon' ), true ) ) {
			continue;
		}
		$jeunesse = false !== strpos( $r->slug, 'jeunesse' );
		$pris     = array();
		$id       = er_menu_dernier_livre( $r->slug, $pris, $jeunesse );
		if ( $id ) {
			$livres[ 'rayon-' . $r->slug ] = $id;
			$pris[]                         = $id;
		}
		$enfants = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $r->term_id,
				'hide_empty' => true,
			)
		);
		foreach ( is_wp_error( $enfants ) ? array() : $enfants as $e ) {
			$id = er_menu_dernier_livre( $e->slug, $pris, $jeunesse );
			if ( $id ) {
				$livres[ 'sous-' . $e->slug ] = $id;
				$pris[]                        = $id;
			}
		}
	}
	set_transient( 'er_menu_livres_v1', $livres, 6 * HOUR_IN_SECONDS );
	return $livres;
}

/**
 * Oublie les livres du menu et le panneau de la Librairie déjà rendu : un livre modifié, mis à la corbeille ou épuisé,
 * un rayon renommé.
 */
function er_menu_oublier() {
	delete_transient( 'er_menu_livres_v1' );
	foreach ( function_exists( 'er_langues_du_site' ) ? array_keys( (array) er_langues_du_site() ) : array( 'fr' ) as $langue ) {
		delete_transient( 'er_menu_librairie_' . $langue );
	}
}
add_action( 'woocommerce_update_product', 'er_menu_oublier' );
add_action( 'woocommerce_product_set_stock_status', 'er_menu_oublier' );
add_action(
	'transition_post_status',
	function ( $apres, $avant, $post ) {
		if ( $apres !== $avant && 'product' === $post->post_type ) {
			er_menu_oublier();
		}
	},
	10,
	3
);
foreach ( array( 'created_product_cat', 'edited_product_cat', 'delete_product_cat' ) as $er_crochet ) {
	add_action( $er_crochet, 'er_menu_oublier' );
}
unset( $er_crochet );

/**
 * Un livre dans le panneau de la Librairie (AA1) : « Au rayon … », la couverture au centre d'un cadre beige, le titre,
 * l'auteur et le prix dessous.
 *
 * @param int    $id   Le livre.
 * @param string $cle  La clé du rayon ou du sous-rayon.
 * @param string $nom  Le nom du rayon ou du sous-rayon.
 * @param bool   $voir Montré d'emblée.
 */
function er_menu_livre( $id, $cle, $nom, $voir ) {
	$produit = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
	// Un livre qui a quitté la vente depuis que la liste a été gardée ne s'y montre pas.
	if ( ! $produit || 'publish' !== $produit->get_status() ) {
		return '';
	}
	$titre  = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	$sous   = er_livre_sous( $produit, er_em_auteurs( $id ) );
	/* translators: %s: le nom d'un rayon (« Au rayon Poésie ») */
	return '<div class="er-mn-livre" data-livre="' . esc_attr( $cle ) . '"' . ( $voir ? '' : ' hidden' ) . '><p class="er-mn-livre-rayon">' . esc_html( sprintf( __( 'Au rayon %s', 'editeurs-reunis' ), $nom ) ) . '</p>'
		. '<a href="' . esc_url( get_permalink( $id ) ) . '"><span class="er-mn-livre-cadre">' . er_em_image( get_post_thumbnail_id( $id ), 'medium', '112px' ) . '</span>'
		. '<span class="er-mn-livre-texte"><span class="er-mn-livre-titre"' . er_em_lang( $titre ) . '>' . esc_html( $titre ) . '</span>'
		. ( $sous ? '<span class="er-mn-livre-sous">' . $sous . '</span>' : '' ) . '</span></a></div>'; // er_livre_sous() échappe.
}

/**
 * Le panneau de la Librairie (H) : les rayons ; les sous-rayons du rayon survolé ; le livre du rayon ou du sous-rayon.
 *
 * @param array $m Le menu de la Librairie (er_em_donnees()).
 */
function er_menu_panneau_librairie( $m ) {
	// Rendu une fois par heure et par langue (58 livres cachés, chacun avec sa couverture, son auteur, son prix : plus
	// de 400 requêtes à chaque page qui n'est pas en cache, un panier rempli par exemple ; audit du 27 nov. 2026).
	$cle_cache = 'er_menu_librairie_' . ( function_exists( 'er_langue' ) ? er_langue() : 'fr' );
	$garde     = get_transient( $cle_cache );
	if ( is_string( $garde ) && '' !== $garde ) {
		return $garde;
	}
	$html = er_menu_panneau_librairie_rendre( $m );
	set_transient( $cle_cache, $html, HOUR_IN_SECONDS );
	return $html;
}

/**
 * Le panneau de la Librairie, rendu (er_menu_panneau_librairie() le garde).
 *
 * @param array $m Le menu de la Librairie (er_em_donnees()).
 */
function er_menu_panneau_librairie_rendre( $m ) {
	$livres = er_menu_livres();
	$rayons = '';
	$sous   = '';
	$blocs  = '';
	$faits  = array(); // Un livre par clé : un même sous-rayon ne se montre jamais deux fois.
	foreach ( $m['rayons'] as $i => $r ) {
		$rayons .= '<li><a href="' . esc_url( $r['lien'] ) . '" data-rayon="' . esc_attr( $r['cle'] ) . '"' . ( 0 === $i ? ' class="est-actif"' : '' ) . '>' . esc_html( $r['nom'] ) . '</a></li>';
		if ( isset( $livres[ $r['cle'] ] ) && ! isset( $faits[ $r['cle'] ] ) ) {
			$blocs              .= er_menu_livre( $livres[ $r['cle'] ], $r['cle'], $r['nom'], 0 === $i );
			$faits[ $r['cle'] ] = true;
		}
		$groupes = '';
		foreach ( $r['groupes'] as $titre => $liens ) {
			$titre = substr( (string) $titre, 1 );
			$items = '';
			foreach ( $liens as $l ) {
				// Le sous-rayon par son identifiant (deux rayons peuvent avoir un sous-rayon du même nom, « Romans »).
				$slug  = isset( $l[2] ) ? $l[2] : '';
				if ( '' === $slug ) {
					$terme = get_term_by( 'name', $l[0], 'product_cat' );
					$slug  = $terme ? $terme->slug : '';
				}
				$cle    = '' !== $slug ? 'sous-' . $slug : '';
				$items .= '<li><a href="' . esc_url( $l[1] ) . '"' . ( $cle && isset( $livres[ $cle ] ) ? ' data-livre="' . esc_attr( $cle ) . '"' : '' ) . '>' . esc_html( $l[0] ) . '</a></li>';
				if ( $cle && isset( $livres[ $cle ] ) && ! isset( $faits[ $cle ] ) ) {
					$blocs        .= er_menu_livre( $livres[ $cle ], $cle, $l[0], false );
					$faits[ $cle ] = true;
				}
			}
			$groupes .= '<div class="er-mn-groupe">' . ( '' !== $titre ? '<p class="er-mn-groupe-titre">' . esc_html( $titre ) . '</p>' : '' ) . '<ul>' . $items . '</ul></div>';
		}
		/* translators: %s: le nom d'un rayon */
		$tout  = sprintf( __( 'Tout le rayon %s →', 'editeurs-reunis' ), $r['nom'] );
		$sous .= '<div class="er-mn-sous-rayon" data-rayon="' . esc_attr( $r['cle'] ) . '"' . ( 0 === $i ? '' : ' hidden' ) . '>'
			. '<a class="er-mn-tout-rayon" href="' . esc_url( $r['lien'] ) . '">' . esc_html( $tout ) . '</a>'
			. ( $groupes ? '<div class="er-mn-groupes">' . $groupes . '</div>' : '' ) . '</div>';
	}
	// Trois colonnes calées sur les trois photos du triptyque (.er-grille-tpq) : les rayons, les sous-rayons, le livre.
	return '<div class="er-em-panneau er-mn-librairie" id="er-em-librairie" hidden><div class="er-mn-dedans er-grille-tpq">'
		. '<div class="er-mn-rayons-col"><ul class="er-mn-rayons">' . $rayons . '</ul>'
		. '<a class="er-mn-toute" href="' . esc_url( $m['lien'] ) . '">' . esc_html__( 'Toute la librairie →', 'editeurs-reunis' ) . '</a></div>'
		. '<div class="er-mn-sous">' . $sous . '</div>'
		. '<div class="er-mn-livres">' . $blocs . '</div></div></div>';
}

/**
 * Le panneau du sac (J) : le panier (rempli par assets/js/menus.js, depuis l'API du magasin), et le compte.
 */
function er_menu_panneau_panier() {
	$compte = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mon-compte/' );
	$panier = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' );
	$caisse = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/commande/' );
	if ( is_user_logged_in() ) {
		$liens = array(
			array( __( 'Mes commandes', 'editeurs-reunis' ), wc_get_account_endpoint_url( 'orders' ) ),
			array( __( 'Mes adresses', 'editeurs-reunis' ), wc_get_account_endpoint_url( 'edit-address' ) ),
			array( __( 'Mes informations', 'editeurs-reunis' ), wc_get_account_endpoint_url( 'edit-account' ) ),
			array( __( 'Me déconnecter', 'editeurs-reunis' ), wp_logout_url( home_url( '/' ) ) ),
		);
	} else {
		$liens = array(
			array( __( 'Me connecter', 'editeurs-reunis' ), $compte ),
			array( __( 'Créer un compte', 'editeurs-reunis' ), $compte ),
			array( __( 'Suivre une commande', 'editeurs-reunis' ), $compte ),
		);
	}
	$compte_html = '';
	foreach ( $liens as $l ) {
		$compte_html .= '<li><a href="' . esc_url( $l[1] ) . '">' . esc_html( $l[0] ) . '</a></li>';
	}
	// Deux colonnes sur le triptyque (.er-grille-tpq-2) : le panier sur les deux premières photos, le compte sur la dernière.
	return '<div class="er-em-panneau er-mn-panier" id="er-em-panier" hidden><div class="er-mn-dedans er-grille-tpq er-grille-tpq-2">'
		. '<div class="er-mn-panier-col"><p class="er-mn-titre">' . esc_html__( 'Votre panier', 'editeurs-reunis' ) . '</p>'
		. '<div class="er-mn-panier-liste" data-vide="' . esc_attr__( 'Votre panier est vide.', 'editeurs-reunis' ) . '" data-autre="' . esc_attr__( 'et un autre livre', 'editeurs-reunis' ) . '" data-autres="' . esc_attr__( 'et %d autres livres', 'editeurs-reunis' ) . '" data-total="' . esc_attr__( 'Sous-total : %s', 'editeurs-reunis' ) . '"><p class="er-mn-panier-attente">' . esc_html__( 'Votre panier…', 'editeurs-reunis' ) . '</p></div>'
		. '<p class="er-mn-panier-boutons"><a class="er-mn-bouton" href="' . esc_url( $panier ) . '">' . esc_html__( 'Voir le panier', 'editeurs-reunis' ) . '</a>'
		. '<a class="er-mn-bouton er-mn-bouton-plein" href="' . esc_url( $caisse ) . '">' . esc_html__( 'Commander', 'editeurs-reunis' ) . '</a></p></div>'
		. '<div class="er-mn-compte-col"><p class="er-mn-titre">' . esc_html__( 'Mon compte', 'editeurs-reunis' ) . '</p><ul class="er-mn-compte">' . $compte_html . '</ul></div>'
		. '</div></div>';
}

/**
 * Les quatre noms de la barre : chacun ouvre son menu au survol.
 */
function er_menu() {
	$html = '';
	foreach ( er_em_donnees() as $cle => $m ) {
		$html .= '<button type="button" class="er-em-bouton" data-er-em="' . esc_attr( $cle ) . '" aria-expanded="false" aria-controls="er-em-' . esc_attr( $cle ) . '">'
			. '<span>' . esc_html( $m['nom'] ) . '</span></button>';
	}
	return '<nav class="er-em er-mn" aria-label="' . esc_attr__( 'Le site', 'editeurs-reunis' ) . '">' . $html . '</nav>';
}
add_shortcode( 'er_menu', 'er_menu' );

/**
 * Le sac : il ouvre le panier et le compte ; la pastille dit combien de livres il y a.
 */
function er_menu_sac() {
	$n = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
	return '<button type="button" class="er-sac er-picto" data-er-em="panier" aria-expanded="false" aria-controls="er-em-panier" aria-label="' . esc_attr__( 'Le panier et mon compte', 'editeurs-reunis' ) . '">'
		. '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 8h14l-1.2 11.2a1.5 1.5 0 0 1-1.5 1.3H7.7a1.5 1.5 0 0 1-1.5-1.3L5 8z"/><path d="M9 10V6.5a3 3 0 0 1 6 0V10"/></svg>'
		. '<span class="er-sac-nombre"' . ( $n ? '' : ' hidden' ) . '>' . esc_html( (string) $n ) . '</span></button>';
}
add_shortcode( 'er_menu_sac', 'er_menu_sac' );

/**
 * Le bouton du menu (≡), jusqu'à 1 360 px : il ouvre le plan du site.
 */
add_shortcode( 'er_menu_bouton', 'er_essai_bouton_menu' );

/**
 * Le chevron d'une maison du menu du téléphone, au trait fin (AK1, 27 nov. ; gras jusque-là).
 */
function er_menu_chevron() {
	return '<svg class="er-mp-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7"/></svg>';
}

/**
 * Une image du thème pour une carte du carrousel, cadrée comme dans les triptyques.
 *
 * @param string $nom La clé de l'image (er_triptyque_images).
 */
function er_menu_carte_image( $nom ) {
	$images = er_triptyque_images();
	if ( ! isset( $images[ $nom ]['theme'] ) ) {
		return '';
	}
	list( $z, $x, $y ) = $images[ $nom ]['cadre'];
	return '<img src="' . esc_url( er_image( $images[ $nom ]['theme'] ) ) . '" alt="" loading="lazy" decoding="async" style="'
		. esc_attr( sprintf( '--er-tpq-x:%1$s%%;--er-tpq-y:%2$s%%;--er-tpq-z:%3$s', $x, $y, $z ) ) . '">';
}

/**
 * Ce que dit la barre d'informations (inc/entete.php, er_infos), carte par carte : les prochaines rencontres,
 * l'exposition en cours, les horaires, l'adresse.
 */
function er_menu_cartes() {
	$trouver = er_em_lien( 'nous-trouver/' );
	$cartes  = array();
	foreach ( function_exists( 'er_info_rencontres' ) ? er_info_rencontres() : array() as $r ) {
		$debut = er_info_iso( $r->ID, 'er_debut' );
		if ( ! $debut ) {
			continue;
		}
		$id       = er_traduction( $r->ID );
		$quand    = er_info_quand( $r->ID );
		$photo    = get_post_thumbnail_id( $id ) ? get_post_thumbnail_id( $id ) : get_post_thumbnail_id( $r->ID );
		$cartes[] = array(
			'lien'  => get_permalink( $id ),
			'image' => $photo ? er_em_image( $photo, 'medium', '64px' ) : er_menu_carte_image( 'signature' ),
			'label' => __( 'Prochain événement', 'editeurs-reunis' ) . ( '' !== $quand ? ' · ' . $quand : '' ),
			'titre' => get_the_title( $id ),
			'debut' => $debut,
		);
	}
	$expo = function_exists( 'er_info_exposition' ) ? er_info_exposition() : null;
	if ( $expo ) {
		$id       = er_traduction( $expo->ID );
		$fin      = er_info_iso( $expo->ID, 'er_fin' );
		$photo    = get_post_thumbnail_id( $id ) ? get_post_thumbnail_id( $id ) : get_post_thumbnail_id( $expo->ID );
		$cartes[] = array(
			'lien'  => get_permalink( $id ),
			'image' => $photo ? er_em_image( $photo, 'medium', '64px' ) : er_menu_carte_image( 'photographe' ),
			/* translators: %s: la date de fin d'une exposition (« 28 février »). */
			'label' => __( 'Exposition', 'editeurs-reunis' ) . ( $fin ? ' · ' . sprintf( __( 'jusqu’au %s', 'editeurs-reunis' ), er_jour_fin_expo( $fin ) ) : '' ),
			'titre' => get_the_title( $id ),
		);
	}
	$cartes[] = array(
		'lien'  => $trouver,
		'image' => er_menu_carte_image( 'facade' ),
		'label' => __( 'Horaires', 'editeurs-reunis' ),
		'titre' => __( 'Ouvert du mardi au samedi de 10 h à 18 h 30', 'editeurs-reunis' ),
	);
	$cartes[] = array(
		'lien'  => $trouver,
		'image' => er_menu_carte_image( 'khokhloma' ),
		'label' => __( 'Nous trouver', 'editeurs-reunis' ),
		'titre' => __( '11, rue de la Montagne-Sainte-Geneviève, 75005 Paris', 'editeurs-reunis' ) . ' →',
	);
	return $cartes;
}

/**
 * Le menu du téléphone (P1 du canevas « Les pages intérieures », retenu le 26 nov. 2026), dans le plein écran du ≡ :
 * les quatre maisons en gras, sans photo, un chevron au bout ; on touche une maison, ses pages glissent depuis la
 * droite, et « ‹ Le menu » ramène, comme sur apple.com. La liste est collée en bas et défile. En bas, accrochés (N2,
 * retenus) : ce que dit la barre d'informations, en carrousel, puis la recherche (qui s'ouvre en plein écran, AF1),
 * puis le panier, le compte et la langue. Au-dessus de 781 px, le plan en colonnes reste.
 */
function er_menu_telephone() {
	$chemin = function ( $lien ) {
		return trailingslashit( (string) wp_parse_url( (string) $lien, PHP_URL_PATH ) );
	};
	$ici = $chemin( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/' );

	$maisons = '';
	$tiroirs = '';
	foreach ( er_em_donnees() as $cle => $m ) {
		$id    = 'er-mp-pages-' . $cle;
		$liste = '';
		$deja  = false;
		foreach ( $m['pages'] as $p ) {
			$deja   = $deja || $chemin( $p['lien'] ) === $chemin( $m['lien'] );
			$liste .= '<li><a href="' . esc_url( $p['lien'] ) . '"' . ( $chemin( $p['lien'] ) === $ici ? ' aria-current="page"' : '' ) . '>' . esc_html( $p['nom'] ) . '</a></li>';
		}
		// La page de la maison, quand elle n'est pas déjà dans la liste (la Librairie, dont la liste a les rayons).
		$tout     = $deja ? '' : '<a class="er-mp-tout" href="' . esc_url( $m['lien'] ) . '">'
			. esc_html( 'librairie' === $cle ? __( 'Toute la librairie', 'editeurs-reunis' ) : $m['nom'] ) . ' <span aria-hidden="true">→</span></a>';
		$maisons .= '<li><button type="button" class="er-mp-maison" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '">'
			. '<span>' . esc_html( $m['nom'] ) . '</span>' . er_menu_chevron() . '</button></li>';
		$tiroirs .= '<div class="er-mp-tiroir" id="' . esc_attr( $id ) . '" hidden><p class="er-mp-surtitre">' . esc_html( $m['nom'] ) . '</p>'
			. '<ul class="er-mp-pages">' . $liste . '</ul>' . $tout . '</div>';
	}

	$cartes = '';
	foreach ( er_menu_cartes() as $c ) {
		$cartes .= '<a class="er-mp-carte" href="' . esc_url( $c['lien'] ) . '"' . ( isset( $c['debut'] ) ? ' data-debut="' . esc_attr( $c['debut'] ) . '"' : '' ) . '>'
			. '<span class="er-mp-carte-image">' . $c['image'] . '</span>'
			. '<span class="er-mp-carte-texte"><span class="er-mp-carte-label">' . esc_html( $c['label'] ) . '</span>'
			. '<span class="er-mp-carte-titre">' . esc_html( html_entity_decode( $c['titre'], ENT_QUOTES, 'UTF-8' ) ) . '</span></span></a>';
	}

	$accueil = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
	$compte  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mon-compte/' );
	$panier  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/panier/' );
	$langue  = function_exists( 'er_choix_langue' ) ? er_choix_langue() : '';

	return '<div class="er-mp">'
		. '<button type="button" class="er-mp-retour" inert><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7"/></svg>'
		. esc_html__( 'Le menu', 'editeurs-reunis' ) . '</button>'
		. '<div class="er-mp-fenetre"><div class="er-mp-rail">'
		. '<div class="er-mp-panneau er-mp-panneau-maisons"><div class="er-mp-colle"><ul class="er-mp-maisons">' . $maisons . '</ul></div></div>'
		. '<div class="er-mp-panneau er-mp-panneau-pages" inert><div class="er-mp-colle">' . $tiroirs . '</div></div>'
		. '</div></div>'
		. '<div class="er-mp-bas">'
		// Le carrousel collé au bas, au-dessus de la recherche (N2) : il reste en vue quand on parcourt les pages.
		. '<div class="er-mp-carrousel"><div class="er-mp-cartes">' . $cartes . '</div>'
		/* translators: 1: le rang d'une carte, 2: le nombre de cartes (le carrousel du menu du téléphone). */
		. '<div class="er-mp-traits" data-libelle="' . esc_attr__( 'Information %1$s sur %2$s', 'editeurs-reunis' ) . '"></div></div>'
		// La recherche (AF1, 27 nov.) : ce n'est plus un champ dans le menu (le clavier faisait glisser la page, et le
		// site se voyait derrière) ; on le touche, la recherche prend tout l'écran, le champ en haut, le clavier ouvert.
		// Sans script, le lien mène à la recherche des livres.
		. '<a class="er-mp-recherche" href="' . esc_url( add_query_arg( array( 's' => '', 'post_type' => 'product' ), $accueil ) ) . '" data-er-recherche-ouvrir aria-label="' . esc_attr__( 'Rechercher un livre, un auteur, une rencontre', 'editeurs-reunis' ) . '">'
		. '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="11.5" cy="11.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M16.4 16.4L20.5 20.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>'
		. '<span class="er-mp-recherche-texte">' . esc_html__( 'Un livre, un auteur, une rencontre', 'editeurs-reunis' ) . '</span></a>'
		. '<div class="er-mp-outils"><a href="' . esc_url( $panier ) . '">' . esc_html__( 'Panier', 'editeurs-reunis' ) . '</a>'
		. '<a href="' . esc_url( $compte ) . '">' . esc_html__( 'Mon compte', 'editeurs-reunis' ) . '</a>'
		. ( $langue ? '<span class="er-mp-langue">' . $langue . '</span>' : '' ) . '</div></div></div>';
}

/**
 * Sous la barre du nom : le voile, les quatre menus, le panier, puis le plan du site.
 */
function er_menu_panneaux() {
	$html = '<div class="er-em-voile" aria-hidden="true"></div>';
	foreach ( er_em_donnees() as $cle => $m ) {
		$html .= 'librairie' === $cle ? er_menu_panneau_librairie( $m ) : er_em_panneau_mots( $cle, $m );
	}
	return $html . er_menu_panneau_panier() . er_em_plan( er_menu_telephone() );
}
add_shortcode( 'er_menu_panneaux', 'er_menu_panneaux' );

// Le script des menus, partout.
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script(
			'editeurs-reunis-menus',
			get_theme_file_uri( 'assets/js/menus.js' ),
			array(),
			wp_get_theme()->get( 'Version' ) . '.' . filemtime( get_theme_file_path( 'assets/js/menus.js' ) ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_localize_script(
			'editeurs-reunis-menus',
			'erMenus',
			array( 'panier' => esc_url_raw( rest_url( 'wc/store/v1/cart' ) ) )
		);
	}
);
