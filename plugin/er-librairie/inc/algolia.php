<?php
/**
 * Algolia : l'index de la recherche (`inc/recherche.php`) recopié chez Algolia, et les recherches faites là-bas.
 *
 * L'index du site reste la référence : chaque ligne écrite ou ôtée de la table `er_index` l'est aussi chez Algolia, en
 * un seul envoi à la fin de la requête. La recherche (le panneau de la loupe, la recherche des livres) interroge
 * Algolia, avec la clé de ce qu'on tape (les trois écritures réunies, comme dans la table) : Algolia y ajoute la
 * tolérance aux fautes de frappe. Si Algolia ne répond pas, ou refuse (le quota gratuit épuisé), la recherche repasse
 * sur la table, sans rien dire au visiteur.
 *
 * Les réglages (Réglages → Recherche) : l'identifiant de l'application, la clé de recherche (publique) et la clé du
 * site, limitée aux index `er_*` (search, browse, addObject, deleteObject, settings, editSettings). Ils se saisissent
 * dans l'administration, jamais dans ce dépôt ; la clé du site ne se réaffiche pas.
 *
 * `wp er algolia` dit où en est l'index chez Algolia ; `wp er algolia indexer` refait l'index du site et y envoie tout.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les réglages d'Algolia : app, recherche, site, index, actif.
 */
function er_algolia_reglages() {
	return array(
		'app'       => trim( (string) get_option( 'er_algolia_app', '' ) ),
		'recherche' => trim( (string) get_option( 'er_algolia_cle_recherche', '' ) ),
		'site'      => trim( (string) get_option( 'er_algolia_cle_site', '' ) ),
		'index'     => sanitize_key( (string) get_option( 'er_algolia_index', 'er_site' ) ) ?: 'er_site',
		'actif'     => '1' === get_option( 'er_algolia_actif', '0' ),
	);
}

/**
 * Algolia est-il branché (les trois valeurs posées, et la recherche activée) ?
 *
 * @param bool $pour_ecrire Pour écrire (la clé du site suffit) plutôt que pour chercher.
 */
function er_algolia_branche( $pour_ecrire = false ) {
	$r = er_algolia_reglages();
	if ( '' === $r['app'] ) {
		return false;
	}
	return $pour_ecrire ? '' !== $r['site'] : ( $r['actif'] && '' !== $r['recherche'] );
}

/**
 * Une requête à l'API d'Algolia.
 *
 * @param string $methode GET, POST, PUT.
 * @param string $chemin  Le chemin, après /1/ (indexes/er_site/batch…).
 * @param array  $corps   Le corps, en JSON.
 * @param string $cle     « recherche » ou « site ».
 * @param int    $delai   Le délai, en secondes.
 * @return array|WP_Error La réponse décodée.
 */
function er_algolia_api( $methode, $chemin, $corps = null, $cle = 'site', $delai = 15 ) {
	$r = er_algolia_reglages();
	if ( '' === $r['app'] || '' === $r[ $cle ] ) {
		return new WP_Error( 'er_algolia_reglages', __( 'Algolia n’est pas réglé.', 'er-librairie' ) );
	}
	// Les hôtes : l'hôte de lecture pour chercher, celui d'écriture sinon, puis les hôtes de secours.
	$hotes = array(
		'recherche' === $cle ? $r['app'] . '-dsn.algolia.net' : $r['app'] . '.algolia.net',
		$r['app'] . '-1.algolianet.com',
		$r['app'] . '-2.algolianet.com',
	);
	$erreur = null;
	foreach ( $hotes as $hote ) {
		$reponse = wp_remote_request(
			'https://' . strtolower( $hote ) . '/1/' . ltrim( $chemin, '/' ),
			array(
				'method'  => $methode,
				'timeout' => $delai,
				'headers' => array(
					'X-Algolia-Application-Id' => $r['app'],
					'X-Algolia-API-Key'        => $r[ $cle ],
					'Content-Type'             => 'application/json; charset=UTF-8',
				),
				'body'    => null === $corps ? null : wp_json_encode( $corps ),
			)
		);
		if ( is_wp_error( $reponse ) ) {
			$erreur = $reponse; // Un hôte injoignable : on essaie le suivant.
			continue;
		}
		$code    = (int) wp_remote_retrieve_response_code( $reponse );
		$donnees = json_decode( wp_remote_retrieve_body( $reponse ), true );
		if ( $code >= 200 && $code < 300 ) {
			return is_array( $donnees ) ? $donnees : array();
		}
		// Une réponse d'Algolia (clé refusée, quota, index absent) : inutile d'essayer un autre hôte.
		return new WP_Error( 'er_algolia_' . $code, is_array( $donnees ) && isset( $donnees['message'] ) ? $donnees['message'] : 'HTTP ' . $code, array( 'status' => $code ) );
	}
	return $erreur ? $erreur : new WP_Error( 'er_algolia', 'Algolia injoignable' );
}

/* Recopier l'index ---------------------------------------------------------------------------------------------- */

/**
 * L'identifiant d'une ligne chez Algolia : « livre-123 ».
 *
 * @param string $genre Le genre.
 * @param int    $objet L'identifiant du contenu ou du terme.
 */
function er_algolia_id( $genre, $objet ) {
	return $genre . '-' . (int) $objet;
}

/**
 * Une ligne de la table `er_index` → la fiche Algolia. La langue vide (un contenu sans langue) devient « tout ».
 *
 * @param object $ligne La ligne.
 */
function er_algolia_fiche( $ligne ) {
	return array(
		'objectID'  => er_algolia_id( $ligne->genre, $ligne->objet ),
		'genre'     => $ligne->genre,
		'objet'     => (int) $ligne->objet,
		'langue'    => '' !== (string) $ligne->langue ? $ligne->langue : 'tout',
		'maison'    => (string) $ligne->maison,
		'titre'     => $ligne->titre,
		'cle_titre' => trim( $ligne->cle_titre ),
		'cle'       => trim( $ligne->cle ),
		'poids'     => (int) $ligne->poids,
	);
}

// Ce qui change pendant la requête, envoyé en une fois à la fin : array( objectID => true (à écrire) | false (à ôter) ).
$GLOBALS['er_algolia_file'] = array();
$GLOBALS['er_algolia_tout'] = false;

add_action(
	'er_index_ecrit',
	function ( $genre, $objet ) {
		$GLOBALS['er_algolia_file'][ er_algolia_id( $genre, $objet ) ] = array( $genre, (int) $objet, true );
	},
	10,
	2
);
add_action(
	'er_index_ote',
	function ( $genre, $objet ) {
		$GLOBALS['er_algolia_file'][ er_algolia_id( $genre, $objet ) ] = array( $genre, (int) $objet, false );
	},
	10,
	2
);
add_action(
	'er_index_vide',
	function () {
		$GLOBALS['er_algolia_tout'] = true; // L'index est refait : on renverra tout.
	}
);

add_action(
	'shutdown',
	function () {
		if ( ! er_algolia_branche( true ) ) {
			return;
		}
		if ( $GLOBALS['er_algolia_tout'] ) {
			er_algolia_tout_envoyer();
			return;
		}
		if ( $GLOBALS['er_algolia_file'] ) {
			er_algolia_envoyer( $GLOBALS['er_algolia_file'] );
		}
	},
	20
);

/**
 * Envoie les lignes changées : celles qui sont dans la table sont écrites, les autres ôtées.
 *
 * @param array $file objectID => array( genre, objet, écrite ).
 * @return int|WP_Error Le nombre de changements envoyés.
 */
function er_algolia_envoyer( $file ) {
	global $wpdb;
	$table   = er_index_table();
	$demande = array();
	foreach ( $file as $id => list( $genre, $objet, $ecrite ) ) {
		$ligne = $ecrite ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE genre = %s AND objet = %d", $genre, $objet ) ) : null; // phpcs:ignore WordPress.DB
		$demande[] = $ligne
			? array( 'action' => 'updateObject', 'body' => er_algolia_fiche( $ligne ) )
			: array( 'action' => 'deleteObject', 'body' => array( 'objectID' => $id ) );
	}
	$r = er_algolia_reglages();
	foreach ( array_chunk( $demande, 1000 ) as $lot ) {
		$reponse = er_algolia_api( 'POST', 'indexes/' . $r['index'] . '/batch', array( 'requests' => $lot ) );
		if ( is_wp_error( $reponse ) ) {
			return $reponse;
		}
	}
	return count( $demande );
}

/**
 * Règle l'index chez Algolia : ce qu'on cherche, les filtres, le classement.
 *
 * @return array|WP_Error
 */
function er_algolia_regler() {
	$r = er_algolia_reglages();
	return er_algolia_api(
		'PUT',
		'indexes/' . $r['index'] . '/settings',
		array(
			// La clé du titre d'abord, puis le reste (auteurs, graphies, références, mots-clés, début du texte).
			'searchableAttributes'  => array( 'cle_titre', 'cle' ),
			'attributesForFaceting' => array( 'filterOnly(genre)', 'filterOnly(langue)', 'filterOnly(maison)' ),
			'customRanking'         => array( 'desc(poids)' ),
			'attributesToRetrieve'  => array( 'genre', 'objet', 'langue', 'maison', 'titre', 'poids' ),
			'attributesToHighlight' => array(),
			'attributesToSnippet'   => array(),
			// Les clés sont déjà ramenées à l'alphabet latin, sans accents : pas de règles de langue en plus.
			'ignorePlurals'         => false,
			'removeStopWords'       => false,
			'queryType'             => 'prefixLast',
			'typoTolerance'         => true,
			'minWordSizefor1Typo'   => 4,
			'minWordSizefor2Typos'  => 8,
			'paginationLimitedTo'   => 1000,
		)
	);
}

/**
 * Envoie tout l'index : les réglages, l'index vidé, puis toutes les lignes de la table.
 *
 * @return int|WP_Error Le nombre de fiches envoyées.
 */
function er_algolia_tout_envoyer() {
	global $wpdb;
	$r       = er_algolia_reglages();
	$reponse = er_algolia_regler();
	if ( is_wp_error( $reponse ) ) {
		return $reponse;
	}
	$reponse = er_algolia_api( 'POST', 'indexes/' . $r['index'] . '/clear', new stdClass() );
	if ( is_wp_error( $reponse ) ) {
		return $reponse;
	}
	$lignes = $wpdb->get_results( 'SELECT * FROM ' . er_index_table() ); // phpcs:ignore WordPress.DB
	foreach ( array_chunk( $lignes, 1000 ) as $lot ) {
		$demande = array();
		foreach ( $lot as $ligne ) {
			$demande[] = array( 'action' => 'addObject', 'body' => er_algolia_fiche( $ligne ) );
		}
		$reponse = er_algolia_api( 'POST', 'indexes/' . $r['index'] . '/batch', array( 'requests' => $demande ) );
		if ( is_wp_error( $reponse ) ) {
			return $reponse;
		}
	}
	return count( $lignes );
}

/**
 * Refait l'index du site (la langue de chaque contenu comprise, depuis Polylang), puis l'envoie tout entier.
 *
 * @return int|WP_Error Le nombre de fiches envoyées.
 */
function er_algolia_tout_refaire() {
	er_index_reconstruire();
	// Ce que la reconstruction a mis en file est envoyé ici, pas une seconde fois à la fin de la requête.
	$GLOBALS['er_algolia_file'] = array();
	$GLOBALS['er_algolia_tout'] = false;
	return er_algolia_tout_envoyer();
}

/* Chercher chez Algolia ----------------------------------------------------------------------------------------- */

/**
 * Une recherche chez Algolia, sous la forme que rend la table : array d'objets (genre, objet, langue, maison, titre,
 * poids, score), ou WP_Error.
 *
 * @param string $cle    La clé de ce qu'on a tapé.
 * @param array  $genres Les genres voulus.
 * @param int    $limite Combien au plus.
 * @param string $langue La langue de la page (fr, ru, en), ou '' pour toutes.
 */
function er_algolia_requete( $cle, $genres, $limite, $langue = '' ) {
	$filtres = array( '(' . implode( ' OR ', array_map( fn( $g ) => 'genre:' . $g, $genres ) ) . ')' );
	if ( $langue ) {
		$filtres[] = '(langue:' . $langue . ' OR langue:tout)';
	}
	$r       = er_algolia_reglages();
	$reponse = er_algolia_api(
		'POST',
		'indexes/' . $r['index'] . '/query',
		array(
			'params' => http_build_query(
				array(
					'query'          => trim( $cle ),
					'filters'        => implode( ' AND ', $filtres ),
					'hitsPerPage'    => max( 1, min( 1000, (int) $limite ) ),
					'analyticsTags'  => '["' . ( $langue ? $langue : 'fr' ) . '"]',
					'clickAnalytics' => 'false',
				),
				'',
				'&',
				PHP_QUERY_RFC3986
			),
		),
		'recherche',
		2
	);
	if ( is_wp_error( $reponse ) ) {
		return $reponse;
	}
	$lignes = array();
	foreach ( isset( $reponse['hits'] ) ? $reponse['hits'] : array() as $rang => $hit ) {
		$lignes[] = (object) array(
			'genre'  => $hit['genre'],
			'objet'  => (int) $hit['objet'],
			'langue' => 'tout' === $hit['langue'] ? '' : $hit['langue'],
			'maison' => $hit['maison'],
			'titre'  => $hit['titre'],
			'poids'  => (int) $hit['poids'],
			'score'  => 1000 - $rang, // L'ordre d'Algolia.
		);
	}
	return $lignes;
}

/**
 * Le filtre de la recherche du site : les lignes trouvées chez Algolia, ou null si Algolia n'est pas branché ou ne
 * répond pas (la recherche repasse alors sur la table).
 *
 * @param array|null $deja   Ce qu'un autre filtre a déjà trouvé.
 * @param string     $cle    La clé de ce qu'on a tapé.
 * @param array      $genres Les genres voulus.
 * @param int        $limite Combien au plus.
 */
function er_algolia_chercher( $deja, $cle, $genres, $limite ) {
	if ( null !== $deja || ! er_algolia_branche() || '' === trim( $cle ) || get_transient( 'er_algolia_pause' ) ) {
		return $deja;
	}
	$lignes = er_algolia_requete( $cle, $genres, $limite, function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '' );
	if ( is_wp_error( $lignes ) ) {
		// Après un refus (quota épuisé, clé retirée), on laisse Algolia tranquille cinq minutes.
		$donnees = $lignes->get_error_data();
		if ( is_array( $donnees ) && isset( $donnees['status'] ) && in_array( (int) $donnees['status'], array( 401, 403, 429 ), true ) ) {
			set_transient( 'er_algolia_pause', 1, 5 * MINUTE_IN_SECONDS );
		}
		return null;
	}
	return $lignes;
}
add_filter( 'er_index_chercher_pre', 'er_algolia_chercher', 10, 4 );

/* Réglages → Recherche ------------------------------------------------------------------------------------------ */

add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'La recherche', 'er-librairie' ),
			__( 'Recherche', 'er-librairie' ),
			'manage_options',
			'er-recherche',
			'er_algolia_page'
		);
	}
);

add_action(
	'admin_init',
	function () {
		foreach ( array( 'er_algolia_app', 'er_algolia_cle_recherche', 'er_algolia_index', 'er_algolia_actif' ) as $nom ) {
			register_setting( 'er_recherche', $nom, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		}
		// La clé du site : un champ vide garde la clé déjà posée ; elle ne se réaffiche jamais.
		register_setting(
			'er_recherche',
			'er_algolia_cle_site',
			array(
				'sanitize_callback' => function ( $valeur ) {
					$valeur = sanitize_text_field( (string) $valeur );
					return '' === $valeur ? (string) get_option( 'er_algolia_cle_site', '' ) : $valeur;
				},
			)
		);
	}
);

// La clé du site ne se charge pas à chaque page.
add_action(
	'add_option_er_algolia_cle_site',
	function () {
		global $wpdb;
		$wpdb->update( $wpdb->options, array( 'autoload' => 'off' ), array( 'option_name' => 'er_algolia_cle_site' ) ); // phpcs:ignore WordPress.DB
	}
);

/**
 * La page Réglages → Recherche : les clés d'Algolia, l'état de l'index, l'envoi de tout l'index.
 */
function er_algolia_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$message = '';
	if ( isset( $_POST['er_algolia_envoyer'] ) && check_admin_referer( 'er_algolia_envoyer' ) ) {
		$n       = er_algolia_tout_refaire();
		$message = is_wp_error( $n )
			/* translators: %s: le message d'erreur d'Algolia */
			? sprintf( __( 'L’envoi a échoué : %s', 'er-librairie' ), $n->get_error_message() )
			/* translators: %d: le nombre de fiches */
			: sprintf( _n( '%d fiche envoyée à Algolia.', '%d fiches envoyées à Algolia.', $n, 'er-librairie' ), $n );
		delete_transient( 'er_algolia_pause' );
	}
	$r = er_algolia_reglages();
	echo '<div class="wrap"><h1>' . esc_html__( 'La recherche', 'er-librairie' ) . '</h1>';
	if ( $message ) {
		echo '<div class="notice notice-info"><p>' . esc_html( $message ) . '</p></div>';
	}
	echo '<p style="max-width: 46rem">' . esc_html__( 'La loupe cherche dans l’index du site, qui réunit les trois écritures des noms. Avec Algolia, la même recherche tolère les fautes de frappe, et le tableau de bord d’Algolia montre ce que les visiteurs cherchent. Si Algolia ne répond pas, la recherche repasse sur l’index du site.', 'er-librairie' ) . '</p>';

	// L'état.
	if ( '' !== $r['app'] && '' !== $r['recherche'] ) {
		$etat = er_algolia_api( 'POST', 'indexes/' . $r['index'] . '/query', array( 'params' => 'query=&hitsPerPage=0' ), 'recherche', 5 );
		echo '<p><strong>' . esc_html__( 'Algolia :', 'er-librairie' ) . '</strong> ';
		if ( is_wp_error( $etat ) ) {
			/* translators: %s: le message d'erreur d'Algolia */
			echo esc_html( sprintf( __( 'pas de réponse (%s).', 'er-librairie' ), $etat->get_error_message() ) );
		} else {
			/* translators: %d: le nombre de fiches */
			echo esc_html( sprintf( _n( '%d fiche dans l’index.', '%d fiches dans l’index.', (int) $etat['nbHits'], 'er-librairie' ), (int) $etat['nbHits'] ) );
		}
		echo ' ' . esc_html( $r['actif'] ? __( 'La loupe cherche chez Algolia.', 'er-librairie' ) : __( 'La loupe cherche encore dans l’index du site.', 'er-librairie' ) ) . '</p>';
	}

	echo '<form method="post" action="options.php">';
	settings_fields( 'er_recherche' );
	echo '<table class="form-table" role="presentation">';
	$champs = array(
		'er_algolia_app'           => array( __( 'Application ID', 'er-librairie' ), $r['app'], 'text' ),
		'er_algolia_cle_recherche' => array( __( 'Search API Key (la clé de recherche, publique)', 'er-librairie' ), $r['recherche'], 'text' ),
		'er_algolia_cle_site'      => array( __( 'La clé du site (limitée aux index er_*)', 'er-librairie' ), '', 'password' ),
		'er_algolia_index'         => array( __( 'L’index', 'er-librairie' ), $r['index'], 'text' ),
	);
	foreach ( $champs as $nom => list( $libelle, $valeur, $type ) ) {
		$aide = 'er_algolia_cle_site' === $nom ? ( '' !== $r['site'] ? __( 'Posée. Laisser vide pour la garder.', 'er-librairie' ) : __( 'Pas encore posée.', 'er-librairie' ) ) : '';
		echo '<tr><th scope="row"><label for="' . esc_attr( $nom ) . '">' . esc_html( $libelle ) . '</label></th><td>';
		echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="' . esc_attr( $nom ) . '" name="' . esc_attr( $nom ) . '" value="' . esc_attr( $valeur ) . '" autocomplete="off" spellcheck="false">';
		echo $aide ? '<p class="description">' . esc_html( $aide ) . '</p>' : '';
		echo '</td></tr>';
	}
	echo '<tr><th scope="row">' . esc_html__( 'Chercher chez Algolia', 'er-librairie' ) . '</th><td><label><input type="hidden" name="er_algolia_actif" value="0"><input type="checkbox" name="er_algolia_actif" value="1"' . checked( $r['actif'], true, false ) . '> ' . esc_html__( 'La loupe et la recherche des livres interrogent Algolia (à cocher une fois l’index envoyé).', 'er-librairie' ) . '</label></td></tr>';
	echo '</table>';
	submit_button( __( 'Enregistrer', 'er-librairie' ) );
	echo '</form>';

	if ( er_algolia_branche( true ) ) {
		echo '<h2>' . esc_html__( 'Envoyer tout l’index', 'er-librairie' ) . '</h2>';
		echo '<p>' . esc_html__( 'Refait l’index du site, règle celui d’Algolia, le vide, puis y envoie tout ce qu’on peut chercher sur le site. Ensuite, chaque contenu enregistré s’y recopie de lui-même.', 'er-librairie' ) . '</p>';
		echo '<form method="post">';
		wp_nonce_field( 'er_algolia_envoyer' );
		submit_button( __( 'Envoyer tout l’index à Algolia', 'er-librairie' ), 'secondary', 'er_algolia_envoyer', false );
		echo '</form>';
	}
	echo '</div>';
}

/* wp er algolia ------------------------------------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Où en est l'index chez Algolia (sans argument) ; y envoie tout (indexer).
	 *
	 * @param array $args indexer, ou rien.
	 */
	WP_CLI::add_command(
		'er algolia',
		function ( $args ) {
			$r = er_algolia_reglages();
			WP_CLI::log( 'Application : ' . ( '' !== $r['app'] ? 'posée' : 'vide' ) . ' ; clé de recherche : ' . ( '' !== $r['recherche'] ? 'posée' : 'vide' ) . ' ; clé du site : ' . ( '' !== $r['site'] ? 'posée' : 'vide' ) . ' ; index : ' . $r['index'] . ' ; la loupe cherche chez Algolia : ' . ( $r['actif'] ? 'oui' : 'non' ) );
			if ( isset( $args[0] ) && 'indexer' === $args[0] ) {
				if ( ! er_algolia_branche( true ) ) {
					WP_CLI::error( 'Poser l’identifiant de l’application et la clé du site dans Réglages → Recherche.' );
				}
				$n = er_algolia_tout_refaire();
				if ( is_wp_error( $n ) ) {
					WP_CLI::error( 'L’envoi a échoué : ' . $n->get_error_message() );
				}
				delete_transient( 'er_algolia_pause' );
				WP_CLI::success( $n . ' fiches envoyées à Algolia.' );
			}
			if ( '' !== $r['app'] && '' !== $r['recherche'] ) {
				$etat = er_algolia_api( 'POST', 'indexes/' . $r['index'] . '/query', array( 'params' => 'query=&hitsPerPage=0' ), 'recherche', 10 );
				WP_CLI::log( is_wp_error( $etat ) ? 'Algolia ne répond pas : ' . $etat->get_error_message() : 'Fiches chez Algolia : ' . (int) $etat['nbHits'] );
				foreach ( array( 'dostoevsky', 'достоевский', 'soljenitsine', 'pasternac' ) as $essai ) {
					$lignes = er_algolia_requete( er_cle_recherche( $essai ), array( 'auteur', 'livre' ), 3, 'fr' );
					WP_CLI::log( '  « ' . $essai . ' » : ' . ( is_wp_error( $lignes ) ? $lignes->get_error_message() : implode( ' ; ', array_map( fn( $l ) => $l->titre, $lignes ) ) ) );
				}
			}
			global $wpdb;
			WP_CLI::log( 'Lignes dans l’index du site : ' . (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . er_index_table() ) ); // phpcs:ignore WordPress.DB
		}
	);
}
