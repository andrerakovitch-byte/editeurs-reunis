<?php
/**
 * Title: Le menu de la librairie
 * Slug: editeurs-reunis/menu-librairie
 * Categories: editeurs-reunis
 * Inserter: no
 *
 * La devise de la librairie, puis ses rayons : Accueil, les grands rayons, Nos meilleures ventes, Nouveautés, Promos.
 * Chaque entrée (sauf Accueil) ouvre un tiroir sur toute la largeur :
 * - un grand rayon : ses sous-rayons à gauche, en une ou deux listes (le groupe d'un sous-rayon est sa méta
 *   « er_groupe »), et deux tuiles ;
 * - Nos meilleures ventes, Nouveautés : quatre couvertures ;
 * - Promos : les bonnes affaires et les raretés (des mentions), et deux tuiles.
 *
 * Les tuiles ont toutes la même mise en page et trois fonds : une photo, un aplat et la couverture d'un livre (prise
 * dans le catalogue), un aplat et une grande lettre cyrillique. Une tuile peut avoir plusieurs vues : elle devient un
 * petit diaporama, avec ses flèches. Les tuiles de chaque rayon sont dans $tuiles, plus bas.
 *
 * Les rayons viennent des catégories de produits de WooCommerce : un rayon ajouté, renommé ou déplacé y apparaît de
 * lui-même, dans l'ordre des catégories ; un rayon sans tuiles ouvre un tiroir sans tuiles.
 *
 * @package editeurs-reunis
 */

/* Les rayons ------------------------------------------------------------------------------------------------- */

$er_rayons = function ( $parent ) {
	$termes = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent,
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $termes ) ) {
		return array();
	}
	$termes = array_filter(
		$termes,
		function ( $t ) {
			return ! in_array( $t->slug, array( 'uncategorized', 'sans-rayon' ), true );
		}
	);
	usort(
		$termes,
		function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true );
		}
	);
	return $termes;
};

// Où l'on est : le rayon ouvert (ou son parent), ou l'une des entrées fixes.
$actif = '';
if ( function_exists( 'is_shop' ) && is_shop() && empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	$actif = 'accueil';
} elseif ( isset( $_GET['orderby'] ) && 'popularity' === $_GET['orderby'] ) { // phpcs:ignore WordPress.Security.NonceVerification
	$actif = 'ventes';
} elseif ( is_tax( 'product_tag', 'nouveaute' ) ) {
	$actif = 'nouveautes';
} elseif ( is_page( 'promos' ) || is_tax( 'product_tag', array( 'occasion', 'fin-de-serie', 'tres-rare', 'edition-ancienne' ) ) ) {
	$actif = 'promos';
} elseif ( is_tax( 'product_cat' ) ) {
	$terme    = get_queried_object();
	$ancetres = get_ancestors( $terme->term_id, 'product_cat' );
	$haut     = $ancetres ? get_term( end( $ancetres ), 'product_cat' ) : $terme;
	$actif    = $haut->slug;
}

/* Les livres ------------------------------------------------------------------------------------------------- */

// La couverture d'un livre du catalogue, d'après son slug.
$couverture = function ( $slug ) {
	$livre = get_page_by_path( $slug, OBJECT, 'product' );
	if ( ! $livre ) {
		return '';
	}
	$image = get_post_thumbnail_id( $livre );
	return $image ? (string) wp_get_attachment_image_url( $image, 'woocommerce_thumbnail' ) : '';
};

$vitrine_livres = function ( $tri ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$args = array(
		'status'     => 'publish',
		'visibility' => 'catalog',
		'limit'      => 4,
	);
	if ( 'ventes' === $tri ) {
		$args += array(
			'orderby'  => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
			'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
		);
	} else {
		$args += array(
			'orderby' => 'date',
			'order'   => 'DESC',
		);
	}
	return wc_get_products( $args );
};

/* Les tuiles de chaque tiroir --------------------------------------------------------------------------------- */

$tuiles = array(
	'litterature'        => array(
		array(
			'vues' => array(
				array( 'fond' => 'photo', 'image' => er_image( 'branche-librairie.jpg' ), 'position' => '50% 28%', 'sur' => 'Classiques', 'nom' => 'Pouchkine', 'lien' => er_rayon( 'classiques' ) ),
				array( 'fond' => 'couverture', 'aplat' => 'nuit', 'livre' => 'convoi-pour-samarcande', 'sur' => 'Les contemporains', 'nom' => 'Gouzel Iakhina', 'lien' => er_rayon( 'contemporains' ) ),
			),
		),
		array( 'fond' => 'couverture', 'aplat' => 'vert', 'livre' => 'notes-dun-medecin', 'sur' => 'Le livre du moment', 'nom' => 'Notes d’un médecin', 'lien' => er_livre( 'notes-dun-medecin' ) ),
	),
	'histoire'           => array(
		array( 'fond' => 'photo', 'image' => er_image( 'branche-ymca-press.jpg' ), 'position' => '50% 40%', 'sur' => 'L’émigration', 'nom' => 'Un siècle d’YMCA-Press', 'lien' => er_lien( 'ymca-press/histoire/' ) ),
		array( 'fond' => 'couverture', 'aplat' => 'brun', 'livre' => 'larchipel-du-goulag-cinquante-ans-apres', 'sur' => 'Le Goulag', 'nom' => 'L’Archipel, cinquante ans après', 'lien' => er_livre( 'larchipel-du-goulag-cinquante-ans-apres' ) ),
	),
	'pensee-et-religion' => array(
		array( 'fond' => 'lettre', 'aplat' => 'nuit', 'lettre' => 'Ф', 'sur' => 'Philosophie', 'nom' => 'Berdiaev, Boulgakov, Florensky', 'lien' => er_rayon( 'philosophie-russe' ) ),
		array( 'fond' => 'lettre', 'aplat' => 'noir', 'lettre' => 'Б', 'sur' => 'Spiritualité', 'nom' => 'Théologie orthodoxe', 'lien' => er_rayon( 'theologie-orthodoxe' ) ),
	),
	'apprendre-le-russe' => array(
		array( 'fond' => 'lettre', 'aplat' => 'pervenche', 'lettre' => 'Аа', 'sur' => 'Débuter', 'nom' => 'Les manuels pour commencer', 'lien' => er_rayon( 'debutant' ) ),
		array( 'fond' => 'lettre', 'aplat' => 'nuit', 'lettre' => 'Я', 'sur' => 'Les dictionnaires', 'nom' => 'Français-russe, russe-français', 'lien' => er_rayon( 'dictionnaires' ) ),
	),
	'arts'               => array(
		array( 'fond' => 'couverture', 'aplat' => 'noir', 'livre' => 'scenarii-zviaguintsev', 'sur' => 'Cinéma', 'nom' => 'Les scénarios de Zviaguintsev', 'lien' => er_livre( 'scenarii-zviaguintsev' ) ),
		array( 'fond' => 'couverture', 'aplat' => 'nuit', 'livre' => 'moussorgski-calvocoressi', 'sur' => 'Musique', 'nom' => 'Moussorgski, par Calvocoressi', 'lien' => er_livre( 'moussorgski-calvocoressi' ) ),
	),
	'jeunesse'           => array(
		array( 'fond' => 'lettre', 'aplat' => 'pervenche', 'lettre' => 'Ж', 'sur' => 'Contes russes', 'nom' => 'Il était une fois', 'lien' => er_rayon( 'contes-russes' ) ),
		array( 'fond' => 'lettre', 'aplat' => 'vert', 'lettre' => 'Ё', 'sur' => 'Bilingue', 'nom' => 'Lire en deux langues', 'lien' => er_rayon( 'livres-bilingues' ) ),
	),
	'promos'             => array(
		array( 'fond' => 'couverture', 'aplat' => 'brun', 'livre' => 'russkaia-poezia', 'sur' => 'Très rare', 'nom' => 'Русская поэзия', 'lien' => er_livre( 'russkaia-poezia' ) ),
		array( 'fond' => 'lettre', 'aplat' => 'vert', 'lettre' => 'б/у', 'sur' => 'Occasions', 'nom' => 'Des livres d’occasion, en russe et en français', 'lien' => er_lien( 'mention/occasion/' ) ),
	),
);

// Une vue de tuile : son fond, le surtitre et le nom en haut, le bouton en bas.
$vue = function ( $t, $classes = '' ) use ( $couverture ) {
	$aplat = isset( $t['aplat'] ) ? ' er-aplat-' . $t['aplat'] : '';
	$fond  = '';
	if ( 'photo' === $t['fond'] ) {
		$fond = '<img class="er-tuile-fond" src="' . esc_url( $t['image'] ) . '" alt="" loading="lazy" style="object-position:' . esc_attr( $t['position'] ?? '50% 50%' ) . '"><span class="er-tuile-voile" aria-hidden="true"></span>';
	} elseif ( 'couverture' === $t['fond'] ) {
		$src  = $couverture( $t['livre'] );
		$fond = $src ? '<img class="er-tuile-livre" src="' . esc_url( $src ) . '" alt="" loading="lazy">' : '';
	} elseif ( 'lettre' === $t['fond'] ) {
		$long = mb_strlen( $t['lettre'] ) > 2 ? ' er-tuile-glyphe-long' : '';
		$fond = '<span class="er-tuile-glyphe' . $long . '" aria-hidden="true" lang="ru">' . esc_html( $t['lettre'] ) . '</span>';
	}
	return '<div class="er-tuile er-tuile-' . esc_attr( $t['fond'] ) . $aplat . ' ' . $classes . '">' . $fond
		. '<p class="er-tuile-sur">' . esc_html( $t['sur'] ) . '</p>'
		. '<p class="er-tuile-nom">' . esc_html( $t['nom'] ) . '</p>'
		. '<p class="er-tuile-bouton"><a href="' . esc_url( $t['lien'] ) . '">Découvrir<span class="screen-reader-text"> : ' . esc_html( $t['nom'] ) . '</span></a></p></div>';
};

// Une tuile : une vue, ou plusieurs en petit diaporama (le script de l'accueil).
$tuile = function ( $t, $rayon ) use ( $vue ) {
	if ( empty( $t['vues'] ) ) {
		return $vue( $t );
	}
	$vues = '';
	foreach ( $t['vues'] as $v ) {
		$vues .= $vue( $v, 'er-diapo' );
	}
	return '<div class="er-tuile-cadre er-diaporama er-diaporama-mini" data-libelle="' . esc_attr( 'À découvrir, ' . $rayon ) . '"><div class="er-diapos">' . $vues . '</div></div>';
};

/* Le tiroir --------------------------------------------------------------------------------------------------- */

$colonne = function ( $groupes, $tout_libelle, $tout_lien ) {
	$html = '<div class="er-tiroir-colonne"><div class="er-tiroir-groupes" style="--er-groupes:' . count( $groupes ) . '">';
	foreach ( $groupes as $titre => $liens ) {
		$html .= '<div class="er-tiroir-groupe">' . ( $titre ? '<p class="er-tiroir-groupe-titre">' . esc_html( $titre ) . '</p>' : '' ) . '<ul class="er-tiroir-liens">';
		foreach ( $liens as $libelle => $lien ) {
			$html .= '<li><a href="' . esc_url( $lien ) . '">' . esc_html( $libelle ) . '</a></li>';
		}
		$html .= '</ul></div>';
	}
	return $html . '</div><a class="er-tiroir-tout" href="' . esc_url( $tout_lien ) . '">' . esc_html( $tout_libelle ) . '</a></div>';
};

$colonne_texte = function ( $sur, $phrase, $tout_libelle, $tout_lien ) {
	return '<div class="er-tiroir-colonne er-tiroir-colonne-texte"><p class="er-tiroir-groupe-titre">' . esc_html( $sur ) . '</p><p class="er-tiroir-phrase">' . esc_html( $phrase ) . '</p><a class="er-tiroir-tout" href="' . esc_url( $tout_lien ) . '">' . esc_html( $tout_libelle ) . '</a></div>';
};

$vitrine = function ( $livres, $rangs ) {
	$html = '<div class="er-tiroir-vitrine">';
	foreach ( array_values( $livres ) as $i => $livre ) {
		$image   = $livre->get_image_id() ? wp_get_attachment_image_url( $livre->get_image_id(), 'woocommerce_thumbnail' ) : '';
		$auteurs = wp_get_post_terms( $livre->get_id(), 'auteur', array( 'fields' => 'names' ) );
		$html   .= '<a class="er-vitrine-livre" href="' . esc_url( get_permalink( $livre->get_id() ) ) . '">'
			. ( $rangs ? '<span class="er-vitrine-rang" aria-hidden="true">' . ( $i + 1 ) . '</span>' : '' )
			. '<span class="er-vitrine-fiche">' . ( $image ? '<img src="' . esc_url( $image ) . '" alt="" loading="lazy">' : '' )
			. '<span class="er-vitrine-titre">' . esc_html( $livre->get_name() ) . '</span>'
			. ( $auteurs && ! is_wp_error( $auteurs ) ? '<span class="er-vitrine-auteur">' . esc_html( implode( ', ', $auteurs ) ) . '</span>' : '' )
			. '</span></a>';
	}
	return $html . '</div>';
};

$chevron = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

// Une entrée du menu, avec ou sans tiroir.
$entree = function ( $cle, $libelle, $lien, $tiroir = '' ) use ( $actif, $chevron ) {
	$courant = $cle === $actif ? ' aria-current="page"' : '';
	if ( ! $tiroir ) {
		return '<li class="er-rayon"><a class="er-rayon-lien" href="' . esc_url( $lien ) . '"' . $courant . '>' . esc_html( $libelle ) . '</a></li>';
	}
	$id = 'er-tiroir-' . $cle;
	return '<li class="er-rayon er-a-tiroir"><a class="er-rayon-lien" href="' . esc_url( $lien ) . '"' . $courant . '>' . esc_html( $libelle ) . '</a>'
		. '<button type="button" class="er-rayon-ouvrir" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '" aria-label="' . esc_attr( 'Ouvrir ' . $libelle ) . '">' . $chevron . '</button>'
		. '<div class="er-tiroir" id="' . esc_attr( $id ) . '"><div class="er-tiroir-dedans">' . $tiroir . '</div></div></li>';
};

/* Les entrées ------------------------------------------------------------------------------------------------- */

$entrees = array( $entree( 'accueil', 'Accueil', er_lien( 'librairie/' ) ) );

foreach ( $er_rayons( 0 ) as $rayon ) {
	$groupes = array();
	foreach ( $er_rayons( $rayon->term_id ) as $sous ) {
		$groupe                            = (string) get_term_meta( $sous->term_id, 'er_groupe', true );
		$groupes[ $groupe ][ $sous->name ] = get_term_link( $sous );
	}
	$contenu = $colonne( $groupes, 'Tout le rayon ' . $rayon->name, get_term_link( $rayon ) );
	foreach ( $tuiles[ $rayon->slug ] ?? array() as $t ) {
		$contenu .= $tuile( $t, $rayon->name );
	}
	$entrees[] = $entree( $rayon->slug, $rayon->name, get_term_link( $rayon ), $contenu );
}

$lien_ventes = add_query_arg( 'orderby', 'popularity', er_lien( 'librairie/' ) );
$entrees[]   = $entree(
	'ventes',
	'Nos meilleures ventes',
	$lien_ventes,
	$colonne_texte( 'Nos meilleures ventes', 'Ce que nos lecteurs achètent le plus.', 'Toutes nos meilleures ventes', $lien_ventes ) . $vitrine( $vitrine_livres( 'ventes' ), true )
);
$entrees[] = $entree(
	'nouveautes',
	'Nouveautés',
	er_lien( 'mention/nouveaute/' ),
	$colonne_texte( 'Nouveautés', 'Les derniers livres arrivés en rayon, en français et en russe.', 'Toutes les nouveautés', er_lien( 'mention/nouveaute/' ) ) . $vitrine( $vitrine_livres( 'date' ), false )
);
$promos = $colonne(
	array(
		'Bonnes affaires' => array(
			'Livres d’occasion' => er_lien( 'mention/occasion/' ),
			'Fins de série'     => er_lien( 'mention/fin-de-serie/' ),
			'Livres soldés'     => er_lien( 'promos/' ),
		),
		'Raretés'         => array(
			'Exemplaires rares'  => er_lien( 'mention/tres-rare/' ),
			'Éditions anciennes' => er_lien( 'mention/edition-ancienne/' ),
		),
	),
	'Toutes les promos',
	er_lien( 'promos/' )
);
foreach ( $tuiles['promos'] as $t ) {
	$promos .= $tuile( $t, 'Promos' );
}
$entrees[] = $entree( 'promos', 'Promos', er_lien( 'promos/' ), $promos );
?>
<!-- wp:group {"align":"full","className":"er-librairie-entete","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-librairie-entete">
	<!-- wp:html -->
	<p class="er-devise">Les Éditeurs réunis, librairie de référence depuis 1925</p>
	<nav class="er-rayons-nav" aria-label="Les rayons de la librairie">
		<ul class="er-rayons-liste">
			<?php echo implode( '', $entrees ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</ul>
	</nav>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
