<?php
/**
 * La recherche : un index de tout ce qu'on peut chercher sur le site (les livres, les auteurs, les rayons, les pages,
 * les articles du journal), qui connaît les trois écritures des noms ; l'API que lit le panneau de recherche du
 * thème ; la recherche des livres de WordPress branchée sur l'index ; les critères de la recherche détaillée.
 *
 * Les trois écritures : « Dostoïevski », « Достоевский », « Dostoevsky » et « dostoievski » ont la même clé. La clé
 * passe le cyrillique en lettres latines, ôte les accents et ramène les transcriptions française et anglaise à une
 * forme commune (tch, ch, sh → ch ; j, zh → j ; ou → u ; y → i ; ie → e ; lettres doublées…). Les mêmes règles
 * s'appliquent à ce qu'on tape et à ce qu'on indexe : la clé n'a pas à être jolie, seulement la même des deux côtés.
 * Pour un nom qui y échappe, la fiche d'un auteur a un champ « Autres graphies ».
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

const ER_INDEX_VERSION = 3;

/* La clé --------------------------------------------------------------------------------------------------------- */

/**
 * La clé de recherche d'un texte.
 *
 * @param string $texte Le texte, dans n'importe quelle écriture.
 */
function er_cle_recherche( $texte ) {
	static $cyrillique = array(
		'щ' => 'shch', 'ж' => 'zh', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'ю' => 'yu', 'я' => 'ya',
		'ё' => 'e', 'й' => 'i', 'ы' => 'y', 'э' => 'e', 'ь' => '', 'ъ' => '', 'а' => 'a', 'б' => 'b', 'в' => 'v',
		'г' => 'g', 'д' => 'd', 'е' => 'e', 'з' => 'z', 'и' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
		'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'і' => 'i', 'ї' => 'i',
		'є' => 'e', 'ґ' => 'g',
	);
	$t = mb_strtolower( wp_strip_all_tags( html_entity_decode( (string) $texte, ENT_QUOTES, 'UTF-8' ) ), 'UTF-8' );
	$t = strtr( $t, $cyrillique );
	$t = remove_accents( $t );
	$t = strtolower( $t );
	$t = preg_replace( '/[^a-z0-9]+/', ' ', $t );
	// Les transcriptions française et anglaise, ramenées à une forme commune.
	$t = str_replace( array( 'shch', 'chtch', 'tch', 'sch', 'sh', 'zh', 'kh', 'ph', 'x', 'w', 'qu' ), array( 'ch', 'ch', 'ch', 'ch', 'ch', 'j', 'h', 'f', 'ks', 'v', 'k' ), $t );
	$t = str_replace( array( 'ou', 'y' ), array( 'u', 'i' ), $t );
	$t = preg_replace( '/gu(?=[ei])/', 'g', $t );
	$t = str_replace( array( 'ie', 'ii' ), array( 'e', 'i' ), $t );
	$t = preg_replace( '/([a-z])\1+/', '$1', $t );
	$t = preg_replace( '/e\b/', '', $t );
	return trim( preg_replace( '/\s+/', ' ', $t ) );
}

/* L'initiale en cyrillique ------------------------------------------------------------------------------------- */

/**
 * L'initiale d'un nom, toujours en cyrillique (décision du 23 nov. 2026) : les vignettes des auteurs, dans la
 * recherche et sur la fiche d'un livre, montrent une lettre russe sur un aplat. D'abord le nom écrit en cyrillique,
 * s'il est connu (le nom lui-même ou l'une de ses « Autres graphies ») ; sinon le début du nom latin, transcrit à la
 * française (Ch → Ш, Tch → Ч, J → Ж, Ou → У, Ia → Я) : Б pour Brodsky, Ш pour Chalamov, Ц pour Zweig.
 *
 * @param string $nom      Le nom.
 * @param array  $graphies Ses autres graphies.
 * @param bool   $famille  Le nom de famille (le dernier mot), sinon le premier mot (pour un titre).
 */
function er_initiale_cyrillique( $nom, $graphies = array(), $famille = true ) {
	$mot = function ( $texte ) use ( $famille ) {
		$mots = preg_split( '/[\s\x{00A0}]+/u', trim( (string) preg_replace( '/[«»"“”()\[\]]/u', ' ', $texte ) ), -1, PREG_SPLIT_NO_EMPTY );
		return $mots ? ( $famille ? end( $mots ) : reset( $mots ) ) : '';
	};
	foreach ( array_merge( array( $nom ), (array) $graphies ) as $n ) {
		$m = $mot( $n );
		if ( preg_match( '/\p{Cyrillic}/u', $m ) ) {
			return mb_strtoupper( mb_substr( (string) preg_replace( '/^[^\p{L}]+/u', '', $m ), 0, 1 ) );
		}
	}
	$m = (string) preg_replace( '/^[^a-z]+/', '', strtolower( remove_accents( (string) $mot( $nom ) ) ) );
	if ( '' === $m ) {
		return '';
	}
	// Les débuts de deux lettres ou plus d'abord, puis les lettres seules.
	$debuts = array(
		'chtch' => 'Щ', 'shch' => 'Щ', 'sch' => 'Щ', 'tch' => 'Ч', 'ch' => 'Ш', 'sh' => 'Ш', 'kh' => 'Х', 'zh' => 'Ж',
		'ts' => 'Ц', 'tz' => 'Ц', 'zw' => 'Ц', 'iou' => 'Ю', 'you' => 'Ю', 'yu' => 'Ю', 'iu' => 'Ю', 'ia' => 'Я', 'ya' => 'Я',
		'io' => 'Ё', 'yo' => 'Ё', 'ie' => 'Е', 'ye' => 'Е', 'eh' => 'Э', 'ou' => 'У', 'ph' => 'Ф', 'th' => 'Т',
		'ce' => 'С', 'ci' => 'С', 'cy' => 'С',
	);
	foreach ( $debuts as $debut => $lettre ) {
		if ( 0 === strpos( $m, $debut ) ) {
			return $lettre;
		}
	}
	$lettres = array(
		'a' => 'А', 'b' => 'Б', 'c' => 'К', 'd' => 'Д', 'e' => 'Е', 'f' => 'Ф', 'g' => 'Г', 'h' => 'Х', 'i' => 'И',
		'j' => 'Ж', 'k' => 'К', 'l' => 'Л', 'm' => 'М', 'n' => 'Н', 'o' => 'О', 'p' => 'П', 'q' => 'К', 'r' => 'Р',
		's' => 'С', 't' => 'Т', 'u' => 'У', 'v' => 'В', 'w' => 'В', 'x' => 'К', 'y' => 'И', 'z' => 'З',
	);
	return isset( $lettres[ $m[0] ] ) ? $lettres[ $m[0] ] : mb_strtoupper( mb_substr( $m, 0, 1 ) );
}

/* L'index -------------------------------------------------------------------------------------------------------- */

/**
 * Le nom de la table de l'index.
 */
function er_index_table() {
	global $wpdb;
	return $wpdb->prefix . 'er_index';
}

/**
 * Crée la table de l'index si elle manque ou si sa version a changé (et la remplit alors).
 */
function er_index_installer() {
	global $wpdb;
	if ( (int) get_option( 'er_index_version' ) === ER_INDEX_VERSION ) {
		return;
	}
	$table = er_index_table();
	// Le nom de la table vient de $wpdb->prefix, pas d'une saisie : rien à préparer.
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	$wpdb->query(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			genre varchar(20) NOT NULL,
			objet bigint(20) unsigned NOT NULL,
			langue varchar(12) NOT NULL DEFAULT '',
			maison varchar(20) NOT NULL DEFAULT '',
			titre text NOT NULL,
			cle text NOT NULL,
			cle_titre text NOT NULL,
			poids int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY genre (genre),
			KEY objet (objet)
		) {$wpdb->get_charset_collate()}"
	);
	// phpcs:enable
	update_option( 'er_index_version', ER_INDEX_VERSION );
	er_index_reconstruire();
}
add_action( 'init', 'er_index_installer', 20 );

/**
 * La langue d'un contenu ou d'un terme selon Polylang, ou ''.
 *
 * @param string $genre « post » ou « term ».
 * @param int    $id    L'identifiant.
 */
function er_index_langue( $genre, $id ) {
	if ( 'term' === $genre && function_exists( 'pll_get_term_language' ) ) {
		return (string) pll_get_term_language( $id );
	}
	if ( 'post' === $genre && function_exists( 'pll_get_post_language' ) ) {
		return (string) pll_get_post_language( $id );
	}
	return '';
}

/**
 * Écrit (ou réécrit) une ligne de l'index.
 *
 * @param string $genre  livre, auteur, rayon, page, article.
 * @param int    $objet  L'identifiant du contenu ou du terme.
 * @param array  $ligne  titre, cle (le texte à chercher), maison, langue, poids.
 */
function er_index_ecrire( $genre, $objet, $ligne ) {
	global $wpdb;
	$table = er_index_table();
	$wpdb->delete( $table, array( 'genre' => $genre, 'objet' => $objet ) ); // phpcs:ignore WordPress.DB
	$wpdb->insert( // phpcs:ignore WordPress.DB
		$table,
		array(
			'genre'     => $genre,
			'objet'     => (int) $objet,
			'langue'    => $ligne['langue'] ?? '',
			'maison'    => $ligne['maison'] ?? '',
			'titre'     => $ligne['titre'],
			'cle'       => ' ' . er_cle_recherche( $ligne['cle'] ) . ' ',
			'cle_titre' => ' ' . er_cle_recherche( $ligne['titre'] ) . ' ',
			'poids'     => (int) ( $ligne['poids'] ?? 0 ),
		)
	);
	do_action( 'er_index_ecrit', $genre, (int) $objet ); // Algolia recopie la ligne (inc/algolia.php).
}

/**
 * Ôte un contenu ou un terme de l'index.
 *
 * @param string $genre Le genre.
 * @param int    $objet L'identifiant.
 */
function er_index_oter( $genre, $objet ) {
	global $wpdb;
	$wpdb->delete( er_index_table(), array( 'genre' => $genre, 'objet' => (int) $objet ) ); // phpcs:ignore WordPress.DB
	do_action( 'er_index_ote', $genre, (int) $objet );
}

/**
 * Les autres graphies d'un auteur (une par ligne dans sa fiche).
 *
 * @param int $terme L'identifiant de l'auteur.
 */
function er_autres_graphies( $terme ) {
	$graphies = (string) get_term_meta( $terme, 'er_graphies', true );
	return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,;]+/', $graphies ) ) ) );
}

/**
 * La maison d'une page d'après son chemin (comme l'en-tête du thème) : ymca, centre, encyclopedie ou ''.
 *
 * @param int $id La page.
 */
function er_index_maison_page( $id ) {
	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) ) {
		$id = pll_get_post( $id, pll_default_language() ) ?: $id;
	}
	$racine = strtok( trim( (string) get_page_uri( $id ), '/' ), '/' );
	if ( 'ymca-press' === $racine ) {
		return 'ymca';
	}
	if ( in_array( $racine, array( 'centre-culturel', 'nous-trouver' ), true ) ) {
		return 'centre';
	}
	if ( 'encyclopedie' === $racine ) {
		return 'encyclopedie';
	}
	return '';
}

/**
 * Indexe un contenu publié (livre, page, article), ou l'ôte s'il ne l'est plus.
 *
 * @param int $id Le contenu.
 */
function er_index_contenu( $id ) {
	$post = get_post( $id );
	if ( ! $post ) {
		return;
	}
	$genres = array(
		'product'      => 'livre',
		'page'         => 'page',
		'post'         => 'article',
		'er_evenement' => 'evenement',
		'er_exposition' => 'evenement',
	);
	if ( ! isset( $genres[ $post->post_type ] ) ) {
		return;
	}
	$genre = $genres[ $post->post_type ];
	if ( 'publish' !== $post->post_status || post_password_required( $post ) ) {
		er_index_oter( $genre, $id );
		return;
	}
	$cle    = $post->post_title;
	$maison = '';
	$poids  = 0;
	if ( 'livre' === $genre && get_post_meta( $id, 'er_evenement', true ) ) {
		er_index_oter( $genre, $id ); // Le billet d'une rencontre n'est pas un livre.
		return;
	}
	if ( 'livre' === $genre ) {
		$maison  = 'librairie';
		$auteurs = wp_get_post_terms( $id, 'auteur' );
		foreach ( is_wp_error( $auteurs ) ? array() : $auteurs as $auteur ) {
			$cle .= ' ' . $auteur->name . ' ' . implode( ' ', er_autres_graphies( $auteur->term_id ) );
		}
		foreach ( array( 'er_titre_fr', 'er_traducteur', 'er_editeur', 'er_collection', '_sku', '_global_unique_id' ) as $champ ) {
			$cle .= ' ' . get_post_meta( $id, $champ, true );
		}
		$poids = 'instock' === get_post_meta( $id, '_stock_status', true ) ? 1 : 0;
	} elseif ( 'page' === $genre ) {
		// Les pages techniques de WooCommerce (panier, commande, compte) ne se cherchent pas, ni une page marquée « hors
		// recherche » (la page d'essai du thème).
		if ( get_post_meta( $id, 'er_hors_recherche', true ) || ( function_exists( 'wc_get_page_id' ) && in_array( (int) $id, array_map( 'wc_get_page_id', array( 'cart', 'checkout', 'myaccount' ) ), true ) ) ) {
			er_index_oter( $genre, $id );
			return;
		}
		$maison = er_index_maison_page( $id );
		$cle   .= ' ' . wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 60, '' );
	} elseif ( 'evenement' === $genre ) {
		// Une rencontre ou une exposition : son titre, son chapô, ses intervenants ou ses artistes, son cycle ou ses
		// techniques, le début de son texte ; à venir (ou accrochée) d'abord.
		$maison = 'centre';
		foreach ( array( 'er_intervenant', 'er_cycle', 'auteur', 'er_sujet', 'er_technique' ) as $taxonomie ) {
			$noms = taxonomy_exists( $taxonomie ) ? wp_get_post_terms( $id, $taxonomie, array( 'fields' => 'names' ) ) : array();
			$cle .= ' ' . implode( ' ', is_wp_error( $noms ) ? array() : $noms );
		}
		$cle .= ' ' . $post->post_excerpt . ' ' . wp_trim_words( wp_strip_all_tags( $post->post_content ), 40, '' );
		if ( 'er_exposition' === $post->post_type ) {
			$cle  .= ' ' . __( 'exposition', 'er-librairie' ) . ' ' . get_post_meta( $id, 'er_artistes', true );
			$poids = function_exists( 'er_expo_etat' ) && 'passee' !== er_expo_etat( $id ) ? 2 : 0;
		} else {
			$poids = function_exists( 'er_evt_passee' ) && ! er_evt_passee( $id ) ? 2 : 0;
		}
	} else {
		$maison = 'centre';
		// Un article : ses mots-clés et sa signature comptent aussi.
		$cle .= ' ' . get_post_meta( $id, 'er_mots', true ) . ' ' . get_post_meta( $id, 'er_signature', true );
		foreach ( array( 'auteur', 'er_sujet', 'er_genre' ) as $taxonomie ) {
			$noms = wp_get_post_terms( $id, $taxonomie, array( 'fields' => 'names' ) );
			$cle .= ' ' . implode( ' ', is_wp_error( $noms ) ? array() : $noms );
		}
		$cle .= ' ' . wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 60, '' );
	}
	er_index_ecrire(
		$genre,
		$id,
		array(
			'titre'  => $post->post_title,
			'cle'    => $cle,
			'maison' => $maison,
			'langue' => er_index_langue( 'post', $id ),
			'poids'  => $poids,
		)
	);
}

/**
 * Indexe un auteur ou un rayon.
 *
 * @param int    $terme    L'identifiant du terme.
 * @param string $taxonomie auteur ou product_cat.
 */
function er_index_terme( $terme, $taxonomie ) {
	$t = get_term( $terme, $taxonomie );
	if ( ! $t || is_wp_error( $t ) || in_array( $t->slug, array( 'uncategorized', 'sans-rayon' ), true ) ) {
		return;
	}
	$genres = array(
		'auteur'   => 'auteur',
		'er_sujet' => 'sujet',
	);
	$genre  = isset( $genres[ $taxonomie ] ) ? $genres[ $taxonomie ] : 'rayon';
	$cle    = $t->name . ( 'auteur' === $genre ? ' ' . implode( ' ', er_autres_graphies( $t->term_id ) ) : '' );
	er_index_ecrire(
		$genre,
		$t->term_id,
		array(
			'titre'  => $t->name,
			'cle'    => $cle,
			'maison' => 'sujet' === $genre ? 'centre' : 'librairie',
			'langue' => er_index_langue( 'term', $t->term_id ),
			'poids'  => (int) $t->count,
		)
	);
}

/**
 * Reconstruit tout l'index.
 */
function er_index_reconstruire() {
	global $wpdb;
	$wpdb->query( 'DELETE FROM ' . er_index_table() ); // phpcs:ignore WordPress.DB
	do_action( 'er_index_vide' );
	$ids = get_posts(
		array(
			'post_type'        => array( 'product', 'page', 'post', 'er_evenement', 'er_exposition' ),
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'lang'             => '',
		)
	);
	foreach ( $ids as $id ) {
		er_index_contenu( $id );
	}
	foreach ( array( 'auteur', 'product_cat', 'er_sujet' ) as $taxonomie ) {
		$termes = get_terms(
			array(
				'taxonomy'   => $taxonomie,
				'hide_empty' => false,
				'fields'     => 'ids',
				'lang'       => '',
			)
		);
		foreach ( is_wp_error( $termes ) ? array() : $termes as $terme ) {
			er_index_terme( $terme, $taxonomie );
		}
	}
}

// L'index suit le site : un contenu enregistré, supprimé ou dépublié ; un auteur ou un rayon modifié.
add_action(
	'save_post',
	function ( $id, $post ) {
		if ( wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || ! in_array( $post->post_type, array( 'product', 'page', 'post', 'er_evenement', 'er_exposition' ), true ) ) {
			return;
		}
		er_index_contenu( $id );
	},
	20,
	2
);
add_action(
	'woocommerce_after_product_object_save',
	function ( $produit ) {
		er_index_contenu( $produit->get_id() );
	}
);
add_action(
	'deleted_post',
	function ( $id ) {
		foreach ( array( 'livre', 'page', 'article', 'evenement' ) as $genre ) {
			er_index_oter( $genre, $id );
		}
	}
);
add_action(
	'set_object_terms',
	function ( $id, $termes, $tt_ids, $taxonomie ) {
		if ( in_array( $taxonomie, array( 'auteur', 'er_sujet', 'er_intervenant', 'er_cycle', 'er_genre', 'er_technique' ), true ) ) {
			er_index_contenu( $id );
		}
	},
	10,
	4
);
foreach ( array( 'created_term', 'edited_term' ) as $er_crochet ) {
	add_action(
		$er_crochet,
		function ( $terme, $tt_id, $taxonomie ) {
			if ( ! in_array( $taxonomie, array( 'auteur', 'product_cat', 'er_sujet' ), true ) ) {
				return;
			}
			er_index_terme( $terme, $taxonomie );
			if ( 'auteur' === $taxonomie ) {
				// Les livres de l'auteur portent son nom et ses graphies.
				foreach ( get_objects_in_term( $terme, 'auteur' ) as $livre ) {
					er_index_contenu( $livre );
				}
			}
		},
		20,
		3
	);
}
add_action(
	'delete_term',
	function ( $terme, $tt_id, $taxonomie ) {
		if ( 'auteur' === $taxonomie ) {
			er_index_oter( 'auteur', $terme );
		} elseif ( 'product_cat' === $taxonomie ) {
			er_index_oter( 'rayon', $terme );
		} elseif ( 'er_sujet' === $taxonomie ) {
			er_index_oter( 'sujet', $terme );
		}
	},
	10,
	3
);

/* Chercher ------------------------------------------------------------------------------------------------------- */

/**
 * Les lignes de l'index qui répondent à une recherche, les meilleures d'abord.
 *
 * @param string $cle    La clé de ce qu'on a tapé.
 * @param array  $genres Les genres voulus.
 * @param int    $limite Combien au plus.
 */
function er_index_chercher( $cle, $genres, $limite = 60 ) {
	global $wpdb;
	// Algolia d'abord, s'il est branché et répond (inc/algolia.php) ; la table sinon.
	$trouve = apply_filters( 'er_index_chercher_pre', null, $cle, $genres, $limite );
	if ( null !== $trouve ) {
		return $trouve;
	}
	$mots = array_filter( explode( ' ', $cle ) );
	if ( ! $mots ) {
		return array();
	}
	$table  = er_index_table();
	$where  = array();
	$params = array();
	foreach ( $mots as $mot ) {
		$where[]  = 'cle LIKE %s';
		$params[] = '%' . $wpdb->esc_like( $mot ) . '%';
	}
	$where[] = 'genre IN (' . implode( ',', array_fill( 0, count( $genres ), '%s' ) ) . ')';
	$params  = array_merge( $params, $genres );
	$langue  = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
	if ( $langue ) {
		$where[]  = "(langue = %s OR langue = '')";
		$params[] = $langue;
	}
	$params[] = (int) $limite * 4;
	$lignes   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY poids DESC LIMIT %d', $params ) ); // phpcs:ignore WordPress.DB

	// Le classement : le titre qui commence par ce qu'on tape, puis un mot du titre, puis le reste.
	$premier = reset( $mots );
	foreach ( $lignes as $l ) {
		$l->score = 0;
		if ( 0 === strpos( $l->cle_titre, ' ' . $cle ) ) {
			$l->score = 30;
		} elseif ( false !== strpos( $l->cle_titre, ' ' . $premier ) ) {
			$l->score = 20;
		} elseif ( false !== strpos( $l->cle_titre, $premier ) ) {
			$l->score = 10;
		} elseif ( false !== strpos( $l->cle, ' ' . $premier ) ) {
			$l->score = 5;
		}
		$l->score += min( (int) $l->poids, 9 );
	}
	usort(
		$lignes,
		function ( $a, $b ) {
			return $b->score <=> $a->score ?: strcmp( $a->titre, $b->titre );
		}
	);
	return array_slice( $lignes, 0, $limite );
}

/**
 * Les identifiants des livres qui répondent à une recherche : par leur titre, leurs auteurs, leurs références.
 *
 * @param string $texte Ce qu'on a tapé.
 */
function er_livres_trouves( $texte ) {
	$cle = er_cle_recherche( $texte );
	if ( '' === $cle ) {
		return array();
	}
	return array_map(
		function ( $l ) {
			return (int) $l->objet;
		},
		er_index_chercher( $cle, array( 'livre' ), 500 )
	);
}

/**
 * Ce qu'on montre d'un livre dans le panneau.
 *
 * @param int $id Le livre.
 */
function er_resultat_livre( $id ) {
	$produit = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
	if ( ! $produit ) {
		return null;
	}
	$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
	$mention = '';
	foreach ( array( 'tres-rare', 'edition-ancienne', 'occasion', 'nouveaute' ) as $slug ) {
		if ( has_term( $slug, 'product_tag', $id ) ) {
			$terme   = get_term_by( 'slug', $slug, 'product_tag' );
			$mention = $terme ? $terme->name : '';
			break;
		}
	}
	$image = $produit->get_image_id() ? wp_get_attachment_image_url( $produit->get_image_id(), 'woocommerce_thumbnail' ) : '';
	$prix  = $produit->get_price();
	return array(
		'titre'   => $produit->get_name(),
		'url'     => get_permalink( $id ),
		'image'   => $image ? $image : '',
		'auteurs' => is_wp_error( $auteurs ) ? '' : implode( ', ', $auteurs ),
		'prix'    => '' !== $prix ? wp_strip_all_tags( html_entity_decode( wc_price( $prix ), ENT_QUOTES, 'UTF-8' ) ) : '',
		'stock'   => $produit->is_in_stock(),
		'mention' => $mention,
	);
}

/**
 * La réponse de l'API : les résultats rangés par genre, leurs nombres, et le nom reconnu.
 *
 * @param WP_REST_Request $requete La requête (q, portee).
 */
function er_api_chercher( $requete ) {
	$texte = trim( (string) $requete->get_param( 'q' ) );
	$cle   = er_cle_recherche( $texte );
	$vide  = array(
		'requete' => $texte,
		'pour'    => '',
		'aussi'   => array(),
		'comptes' => new stdClass(),
		'groupes' => new stdClass(),
		'livres'  => '',
	);
	$criteres = er_criteres( $requete->get_query_params() );
	if ( $criteres ) {
		return rest_ensure_response( er_api_chercher_filtre( $texte, $cle, $criteres ) );
	}
	if ( '' === $cle ) {
		return rest_ensure_response( $vide );
	}
	$lignes = er_index_chercher( $cle, array( 'auteur', 'livre', 'rayon', 'page', 'article', 'evenement', 'sujet' ), 200 );

	$groupes = array(
		'auteurs'      => array(),
		'livres'       => array(),
		'rayons'       => array(),
		'encyclopedie' => array(),
		'ymca'         => array(),
		'centre'       => array(),
		'journal'      => array(),
		'pages'        => array(),
	);
	$limites = array(
		'auteurs'      => 3,
		'livres'       => 8,
		'rayons'       => 4,
		'encyclopedie' => 4,
		'ymca'         => 3,
		'centre'       => 3,
		'journal'      => 3,
		'pages'        => 3,
	);
	$comptes = array_fill_keys( array_keys( $groupes ), 0 );
	foreach ( $lignes as $l ) {
		switch ( $l->genre ) {
			case 'auteur':
				$groupe = 'auteurs';
				break;
			case 'livre':
				$groupe = 'livres';
				break;
			case 'rayon':
				$groupe = 'rayons';
				break;
			case 'article':
				$groupe = 'journal';
				break;
			case 'evenement':
			case 'sujet':
				$groupe = 'centre';
				break;
			default:
				$groupe = in_array( $l->maison, array( 'ymca', 'centre', 'encyclopedie' ), true ) ? $l->maison : 'pages';
		}
		++$comptes[ $groupe ];
		if ( count( $groupes[ $groupe ] ) >= $limites[ $groupe ] ) {
			continue;
		}
		if ( 'livres' === $groupe ) {
			$livre = er_resultat_livre( (int) $l->objet );
			if ( $livre ) {
				$groupes['livres'][] = $livre;
			}
		} elseif ( 'auteurs' === $groupe || 'rayons' === $groupe ) {
			$taxonomie = 'auteurs' === $groupe ? 'auteur' : 'product_cat';
			$terme     = get_term( (int) $l->objet, $taxonomie );
			if ( ! $terme || is_wp_error( $terme ) ) {
				continue;
			}
			$graphies = 'auteurs' === $groupe ? er_autres_graphies( $terme->term_id ) : array();
			$lien     = get_term_link( $terme );
			// Les livres d'une personne : ceux qu'elle a écrits, traduits ou dirigés (Georges Nivat n'en a écrit aucun
			// ici, mais en a traduit et dirigé) ; ceux qui parlent d'elle à part.
			$roles                = 'auteurs' === $groupe && function_exists( 'er_personne_livres' ) ? er_personne_livres( $terme ) : null;
			$groupes[ $groupe ][] = array(
				'titre'    => $terme->name,
				'url'      => is_wp_error( $lien ) ? '' : $lien,
				'livres'   => $roles ? count( $roles['tous'] ) : (int) $terme->count,
				'roles'    => $roles ? array(
					'auteur'     => count( $roles['auteur'] ),
					'traduction' => count( $roles['traduction'] ),
					'direction'  => count( $roles['direction'] ),
					'preface'    => count( $roles['preface'] ),
					'edition'    => count( $roles['edition'] ),
					'illustration' => count( $roles['illustration'] ),
					'revision'   => count( $roles['revision'] ),
					'contribution' => count( $roles['contribution'] ),
					'autour'     => count( $roles['autour'] ),
				) : null,
				'graphies' => $graphies,
				// L'initiale du nom de famille, toujours en cyrillique : Д pour Dostoïevski, Б pour Brodsky.
				'lettre'   => er_initiale_cyrillique( $terme->name, $graphies ),
			);
		} elseif ( 'sujet' === $l->genre ) {
			$terme = get_term( (int) $l->objet, 'er_sujet' );
			if ( ! $terme || is_wp_error( $terme ) ) {
				continue;
			}
			$lien                 = get_term_link( $terme );
			$groupes[ $groupe ][] = array(
				'titre'   => $terme->name,
				'url'     => is_wp_error( $lien ) ? '' : $lien,
				/* translators: %d: le nombre de rencontres et d'articles */
				'extrait' => sprintf( _n( 'Sujet · %d rencontre ou article', 'Sujet · %d rencontres et articles', (int) $terme->count, 'er-librairie' ), (int) $terme->count ),
			);
		} else {
			$post                 = get_post( (int) $l->objet );
			$groupes[ $groupe ][] = array(
				'titre'  => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ), // Le tiret et les guillemets typographiques, pas leurs entités.
				'url'    => get_permalink( $post ),
				'extrait' => 'er_exposition' === $post->post_type && function_exists( 'er_expo_quand' ) ? __( 'Exposition', 'er-librairie' ) . ' · ' . er_expo_quand( $post->ID ) : ( 'evenement' === $l->genre && function_exists( 'er_evt_quand' ) ? er_evt_quand( $post->ID, false ) : ( 'journal' === $groupe ? date_i18n( 'j F Y', get_post_timestamp( $post ) ) : wp_trim_words( html_entity_decode( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), ENT_QUOTES, 'UTF-8' ), 14, '…' ) ) ),
			);
		}
	}
	$comptes['tout'] = array_sum( $comptes );

	// Le nom reconnu : l'auteur trouvé le premier, si ce qu'on a tapé est l'un de ses noms, nom de famille compris
	// (« boris » seul ne désigne pas Boris Akounine plutôt que Boris Pasternak ; « akounine » ou « pasternak » oui).
	$pour  = '';
	$aussi = array();
	if ( $groupes['auteurs'] ) {
		$auteur = $groupes['auteurs'][0];
		$noms   = array_merge( array( $auteur['titre'] ), $auteur['graphies'] );
		$tapes  = array_filter( explode( ' ', $cle ) );
		foreach ( $noms as $nom ) {
			$mots    = array_values( array_filter( explode( ' ', er_cle_recherche( $nom ) ) ) );
			$famille = $mots ? end( $mots ) : '';
			$couvre  = $tapes && $famille;
			foreach ( $tapes as $t ) {
				$couvre = $couvre && (bool) array_filter( $mots, fn( $m ) => 0 === strpos( $m, $t ) );
			}
			if ( $couvre && array_filter( $tapes, fn( $t ) => strlen( $t ) >= 3 && 0 === strpos( $famille, $t ) ) ) {
				$pour  = $auteur['titre'];
				$aussi = array_values( array_diff( $auteur['graphies'], array( $texte ) ) );
				break;
			}
		}
	}

	return rest_ensure_response(
		array(
			'requete' => $texte,
			'pour'    => $pour,
			'aussi'   => $aussi,
			'comptes' => $comptes,
			'groupes' => $groupes,
			'livres'  => add_query_arg(
				array(
					's'         => rawurlencode( $texte ),
					'post_type' => 'product',
				),
				function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' )
			),
		)
	);
}

/**
 * La réponse de l'API quand des filtres sont choisis (un rayon, une langue, un état) : les livres seulement, ceux qui
 * répondent au texte (s'il y en a un) et aux filtres.
 *
 * @param string $texte    Ce qu'on a tapé (peut être vide).
 * @param string $cle      Sa clé.
 * @param array  $criteres Les filtres (er_criteres).
 */
function er_api_chercher_filtre( $texte, $cle, $criteres ) {
	$args = array_merge(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		),
		er_criteres_requete( $criteres )
	);
	if ( '' !== $cle ) {
		$trouves          = er_livres_trouves( $texte );
		$args['post__in'] = isset( $args['post__in'] ) ? array_values( array_intersect( $trouves, $args['post__in'] ) ) : $trouves;
		$args['post__in'] = $args['post__in'] ? $args['post__in'] : array( 0 );
		$args['orderby']  = 'post__in';
	}
	$q      = new WP_Query( $args );
	$livres = array_values( array_filter( array_map( 'er_resultat_livre', $q->posts ) ) );

	// La page des livres trouvés garde le texte et les filtres.
	$parametres = array();
	foreach ( $criteres as $champ => $valeur ) {
		$parametres[ 'er_' . $champ ] = true === $valeur ? '1' : $valeur;
	}
	if ( '' !== $cle ) {
		$parametres['s']         = $texte;
		$parametres['post_type'] = 'product';
		$base                    = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
	} else {
		$base = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}
	return array(
		'requete' => $texte,
		'pour'    => '',
		'aussi'   => array(),
		'filtre'  => true,
		'comptes' => array(
			'livres' => (int) $q->found_posts,
			'tout'   => (int) $q->found_posts,
		),
		'groupes' => array( 'livres' => $livres ),
		'livres'  => add_query_arg( urlencode_deep( $parametres ), $base ),
	);
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'er/v1',
			'/chercher',
			array(
				'methods'             => 'GET',
				'callback'            => 'er_api_chercher',
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
		register_rest_route(
			'er/v1',
			'/livres',
			array(
				'methods'             => 'GET',
				'callback'            => 'er_api_livres',
				'permission_callback' => '__return_true',
			)
		);
	}
);

/* La recherche des livres de WordPress, branchée sur l'index ----------------------------------------------------- */

/**
 * « ?s=dostoevsky&post_type=product » : les livres trouvés par l'index, dans l'ordre de l'index, au lieu de la
 * recherche de WordPress (qui ne connaît ni les trois écritures, ni les auteurs).
 */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() || 'product' !== $q->get( 'post_type' ) ) {
			return;
		}
		$ids = er_livres_trouves( $q->get( 's' ) );
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		if ( ! $q->get( 'orderby' ) || 'relevance' === $q->get( 'orderby' ) ) {
			$q->set( 'orderby', 'post__in' );
		}
		$q->set( 'er_index', true );
	},
	20
);
add_filter(
	'posts_search',
	function ( $sql, $q ) {
		return $q->get( 'er_index' ) ? '' : $sql;
	},
	10,
	2
);

/* La recherche détaillée ----------------------------------------------------------------------------------------- */

/**
 * Les critères de la recherche détaillée, lus dans une requête (l'adresse de la librairie, ou l'API).
 *
 * @param array $source $_GET, ou les paramètres de l'API.
 */
function er_criteres( $source ) {
	$texte    = array( 'titre', 'auteur', 'traducteur', 'isbn', 'editeur', 'collection' );
	$nombres  = array( 'annee_de', 'annee_a', 'prix_de', 'prix_a' );
	$criteres = array();
	foreach ( $texte as $champ ) {
		$v = isset( $source[ 'er_' . $champ ] ) ? sanitize_text_field( wp_unslash( $source[ 'er_' . $champ ] ) ) : '';
		if ( '' !== $v ) {
			$criteres[ $champ ] = $v;
		}
	}
	foreach ( $nombres as $champ ) {
		$v = isset( $source[ 'er_' . $champ ] ) ? str_replace( ',', '.', sanitize_text_field( wp_unslash( $source[ 'er_' . $champ ] ) ) ) : '';
		if ( '' !== $v && is_numeric( $v ) ) {
			$criteres[ $champ ] = (float) $v;
		}
	}
	foreach ( array( 'rayon', 'langue' ) as $champ ) {
		$v = isset( $source[ 'er_' . $champ ] ) ? array_filter( array_map( 'sanitize_title', (array) wp_unslash( $source[ 'er_' . $champ ] ) ) ) : array();
		if ( $v ) {
			$criteres[ $champ ] = array_values( $v );
		}
	}
	$etats = isset( $source['er_etat'] ) ? array_intersect( (array) wp_unslash( $source['er_etat'] ), array( 'neuf', 'occasion', 'edition-ancienne', 'tres-rare' ) ) : array();
	if ( $etats ) {
		$criteres['etat'] = array_values( $etats );
	}
	if ( ! empty( $source['er_stock'] ) ) {
		$criteres['stock'] = true;
	}
	return $criteres;
}

/**
 * Les arguments de WP_Query qui appliquent des critères.
 *
 * @param array $criteres Les critères (er_criteres).
 */
function er_criteres_requete( $criteres ) {
	$args = array(
		'tax_query'  => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_query' => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	$ids  = null;
	$garder = function ( $trouves ) use ( &$ids ) {
		$ids = null === $ids ? $trouves : array_values( array_intersect( $ids, $trouves ) );
	};
	if ( isset( $criteres['titre'] ) ) {
		$cle   = er_cle_recherche( $criteres['titre'] );
		$garder(
			array_map(
				fn( $l ) => (int) $l->objet,
				array_filter( er_index_chercher( $cle, array( 'livre' ), 1000 ), fn( $l ) => false !== strpos( $l->cle_titre, explode( ' ', $cle )[0] ) )
			)
		);
	}
	if ( isset( $criteres['auteur'] ) ) {
		$auteurs = array_map( fn( $l ) => (int) $l->objet, er_index_chercher( er_cle_recherche( $criteres['auteur'] ), array( 'auteur' ), 200 ) );
		$args['tax_query'][] = array(
			'taxonomy' => 'auteur',
			'field'    => 'term_id',
			'terms'    => $auteurs ? $auteurs : array( 0 ),
		);
	}
	foreach ( array( 'traducteur' => 'er_traducteur', 'editeur' => 'er_editeur', 'collection' => 'er_collection' ) as $champ => $meta ) {
		if ( isset( $criteres[ $champ ] ) ) {
			$args['meta_query'][] = array(
				'key'     => $meta,
				'value'   => $criteres[ $champ ],
				'compare' => 'LIKE',
			);
		}
	}
	if ( isset( $criteres['isbn'] ) ) {
		$isbn                 = preg_replace( '/[^0-9xX]/', '', $criteres['isbn'] );
		$args['meta_query'][] = array(
			'relation' => 'OR',
			array(
				'key'     => '_global_unique_id',
				'value'   => $isbn,
				'compare' => 'LIKE',
			),
			array(
				'key'     => '_sku',
				'value'   => $isbn,
				'compare' => 'LIKE',
			),
		);
	}
	foreach ( array( 'annee' => array( 'er_annee', 'NUMERIC' ), 'prix' => array( '_price', 'DECIMAL(10,2)' ) ) as $champ => list( $meta, $type ) ) {
		if ( isset( $criteres[ $champ . '_de' ] ) ) {
			$args['meta_query'][] = array(
				'key'     => $meta,
				'value'   => $criteres[ $champ . '_de' ],
				'compare' => '>=',
				'type'    => $type,
			);
		}
		if ( isset( $criteres[ $champ . '_a' ] ) ) {
			$args['meta_query'][] = array(
				'key'     => $meta,
				'value'   => $criteres[ $champ . '_a' ],
				'compare' => '<=',
				'type'    => $type,
			);
		}
	}
	if ( isset( $criteres['rayon'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'slug',
			'terms'            => $criteres['rayon'],
			'include_children' => true,
		);
	}
	if ( isset( $criteres['langue'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'langue',
			'field'    => 'slug',
			'terms'    => $criteres['langue'],
		);
	}
	if ( isset( $criteres['etat'] ) ) {
		$mentions = array_values( array_diff( $criteres['etat'], array( 'neuf' ) ) );
		$neuf     = in_array( 'neuf', $criteres['etat'], true );
		$etat     = array( 'relation' => 'OR' );
		if ( $mentions ) {
			$etat[] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => $mentions,
			);
		}
		if ( $neuf ) {
			$etat[] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => array( 'occasion', 'edition-ancienne', 'tres-rare' ),
				'operator' => 'NOT IN',
			);
		}
		$args['tax_query'][] = $etat;
	}
	if ( isset( $criteres['stock'] ) ) {
		$args['meta_query'][] = array(
			'key'   => '_stock_status',
			'value' => 'instock',
		);
	}
	if ( null !== $ids ) {
		$args['post__in'] = $ids ? $ids : array( 0 );
	}
	return $args;
}

/**
 * Applique les critères de la recherche détaillée à la librairie : « /librairie/?er_auteur=…&er_langue=russe ».
 */
add_action(
	'pre_get_posts',
	function ( $q ) {
		$livres = $q->is_post_type_archive( 'product' ) || $q->is_tax( get_object_taxonomies( 'product' ) ) || ( $q->is_search() && 'product' === $q->get( 'post_type' ) );
		if ( is_admin() || ! $q->is_main_query() || ! $livres ) {
			return;
		}
		$criteres = er_criteres( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $criteres ) {
			return;
		}
		foreach ( er_criteres_requete( $criteres ) as $cle => $valeur ) {
			if ( in_array( $cle, array( 'tax_query', 'meta_query' ), true ) ) {
				$valeur = array_merge( (array) $q->get( $cle ), $valeur );
			} elseif ( 'post__in' === $cle && $q->get( 'post__in' ) ) {
				// La recherche a déjà choisi ses livres : on garde ceux qui répondent aussi aux critères.
				$valeur = array_values( array_intersect( (array) $q->get( 'post__in' ), $valeur ) );
				$valeur = $valeur ? $valeur : array( 0 );
			}
			$q->set( $cle, $valeur );
		}
	},
	21
);

/**
 * L'API de la recherche détaillée : combien de livres répondent, et l'adresse de la librairie qui les montre.
 *
 * @param WP_REST_Request $requete Les critères (les mêmes noms que dans l'adresse : er_auteur, er_langue[]…).
 */
function er_api_livres( $requete ) {
	$parametres = $requete->get_query_params();
	$criteres   = er_criteres( $parametres );
	$args       = array_merge(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		),
		er_criteres_requete( $criteres )
	);
	$q          = new WP_Query( $args );
	$garder     = array_filter(
		$parametres,
		fn( $v, $k ) => 0 === strpos( $k, 'er_' ) && '' !== $v && array() !== $v,
		ARRAY_FILTER_USE_BOTH
	);
	$boutique   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	return rest_ensure_response(
		array(
			'nombre' => (int) $q->found_posts,
			'url'    => add_query_arg( $garder, $boutique ),
		)
	);
}

/* Les fiches : les graphies d'un auteur, les références d'un livre ------------------------------------------------ */

add_action(
	'auteur_add_form_fields',
	function () {
		echo '<div class="form-field"><label for="er_graphies">' . esc_html__( 'Autres graphies', 'er-librairie' ) . '</label>'
			. '<textarea name="er_graphies" id="er_graphies" rows="3"></textarea>'
			. '<p>' . esc_html__( 'Une par ligne : le nom en russe, en anglais, une transcription ancienne. La recherche les trouve toutes.', 'er-librairie' ) . '</p></div>';
	}
);
add_action(
	'auteur_edit_form_fields',
	function ( $terme ) {
		echo '<tr class="form-field"><th scope="row"><label for="er_graphies">' . esc_html__( 'Autres graphies', 'er-librairie' ) . '</label></th><td>'
			. '<textarea name="er_graphies" id="er_graphies" rows="3">' . esc_textarea( (string) get_term_meta( $terme->term_id, 'er_graphies', true ) ) . '</textarea>'
			. '<p class="description">' . esc_html__( 'Une par ligne : le nom en russe, en anglais, une transcription ancienne. La recherche les trouve toutes.', 'er-librairie' ) . '</p></td></tr>';
	}
);
foreach ( array( 'created_auteur', 'edited_auteur' ) as $er_crochet ) {
	add_action(
		$er_crochet,
		function ( $terme ) {
			if ( isset( $_POST['er_graphies'] ) && current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				update_term_meta( $terme, 'er_graphies', sanitize_textarea_field( wp_unslash( $_POST['er_graphies'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
		},
		5
	);
}

/**
 * Les champs propres à un livre, dans l'onglet « Général » de sa fiche : traducteur, éditeur, collection, année.
 */
function er_champs_livre() {
	return array(
		'er_titre_fr'   => __( 'Le titre en français (sous un titre russe, dans les grilles)', 'er-librairie' ),
		'er_traducteur' => __( 'Traducteur, préfacier', 'er-librairie' ),
		'er_editeur'    => __( 'Éditeur', 'er-librairie' ),
		'er_collection' => __( 'Collection', 'er-librairie' ),
		'er_annee'      => __( 'Année d’édition', 'er-librairie' ),
		'er_pages'      => __( 'Nombre de pages', 'er-librairie' ),
		'er_format'     => __( 'Format (poche, broché, relié…)', 'er-librairie' ),
		'er_phrase'     => __( 'Sa phrase, sous sa couverture quand la fiche d’un autre livre le propose', 'er-librairie' ),
	);
}
add_action(
	'woocommerce_product_options_general_product_data',
	function () {
		echo '<div class="options_group">';
		foreach ( er_champs_livre() as $cle => $libelle ) {
			woocommerce_wp_text_input(
				array(
					'id'    => $cle,
					'label' => $libelle,
					'type'  => in_array( $cle, array( 'er_annee', 'er_pages' ), true ) ? 'number' : 'text',
				)
			);
		}
		echo '</div>';
	}
);
add_action(
	'woocommerce_admin_process_product_object',
	function ( $produit ) {
		foreach ( array_keys( er_champs_livre() ) as $cle ) {
			if ( isset( $_POST[ $cle ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$produit->update_meta_data( $cle, sanitize_text_field( wp_unslash( $_POST[ $cle ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
		}
	}
);

/**
 * Les valeurs déjà saisies d'un champ (les éditeurs, les collections), pour les suggestions de la recherche détaillée.
 *
 * @param string $meta La clé du champ.
 */
function er_valeurs_champ( $meta ) {
	global $wpdb;
	return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> '' ORDER BY meta_value LIMIT 200", $meta ) ); // phpcs:ignore WordPress.DB
}
