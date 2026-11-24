<?php
/**
 * Le journal du centre : la signature d'un article (« Par Yves Hamant »), les anciennes adresses du blog de Wix, et
 * l'import de ses articles (`wp er journal importer`, le fichier que fait outils/wix/journal.py).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		foreach ( array( 'er_livres', 'er_mots' ) as $cle ) {
			register_post_meta(
				'post',
				$cle,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
		register_block_type(
			'er/livres-lies',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID();
					return er_bloc_livres_article( $id );
				},
			)
		);
		register_post_meta(
			'post',
			'er_signature',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_block_type(
			'er/signature',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID();
					return er_bloc_signature( $id );
				},
			)
		);
	}
);

/**
 * « Par Yves Hamant · 29 septembre 2020 » : la signature mène à la page de l'auteur, s'il en a une.
 *
 * @param int $id L'article.
 */
function er_bloc_signature( $id ) {
	$signature = trim( (string) get_post_meta( $id, 'er_signature', true ) );
	$date      = get_the_date( __( 'j F Y', 'er-librairie' ), $id );
	if ( '' === $signature ) {
		return '<p class="er-article-date">' . esc_html( $date ) . '</p>';
	}
	$auteur = function_exists( 'er_personne_trouver' ) ? er_personne_trouver( $signature ) : get_term_by( 'name', $signature, 'auteur' );
	$lien   = $auteur ? get_term_link( $auteur ) : '';
	$nom    = $lien && ! is_wp_error( $lien ) ? '<a href="' . esc_url( $lien ) . '">' . esc_html( $signature ) . '</a>' : esc_html( $signature );
	/* translators: %s: le nom de celui qui signe l'article */
	return '<p class="er-article-date er-signature">' . sprintf( esc_html__( 'Par %s', 'er-librairie' ), $nom ) . ' · ' . esc_html( $date ) . '</p>';
}

/**
 * « À la librairie », sous un article : les livres dont il parle (er_livres, leurs références), puis ceux des personnes
 * qu'il concerne (en stock, pas ceux de celui qui signe), six au plus.
 *
 * @param int $id L'article.
 */
function er_bloc_livres_article( $id ) {
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) || ! function_exists( 'er_carte_livre' ) ) {
		return '';
	}
	$ids = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'er_livres', true ) ) ) ) as $ref ) {
		$trouve = ctype_digit( $ref ) ? (int) $ref : wc_get_product_id_by_sku( $ref );
		if ( $trouve ) {
			$ids[] = $trouve;
		}
	}
	$signature = (string) get_post_meta( $id, 'er_signature', true );
	$personnes = array();
	foreach ( wp_get_post_terms( $id, 'auteur' ) as $t ) {
		if ( $t->name !== $signature ) {
			$personnes[] = $t->term_id;
		}
	}
	if ( $personnes && count( $ids ) < 6 ) {
		$ids = array_merge(
			$ids,
			get_posts(
				array(
					'post_type'   => 'product',
					'numberposts' => 6,
					'fields'      => 'ids',
					'tax_query'   => array( array( 'taxonomy' => 'auteur', 'terms' => $personnes ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_query'  => array( array( 'key' => '_stock_status', 'value' => 'instock' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			)
		);
	}
	$cartes = implode( '', array_map( 'er_carte_livre', array_slice( array_unique( $ids ), 0, 6 ) ) );
	return $cartes ? '<section class="er-evt-livres er-article-livres"><h2 class="er-evt-section">' . esc_html__( 'À la librairie', 'er-librairie' ) . '</h2><ul>' . $cartes . '</ul></section>' : '';
}

// Les anciennes adresses du blog de Wix (/post/<slug>) mènent à l'article, une fois la bascule faite.
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;
		}
		$chemin = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! preg_match( '#^(?:[a-z]{2}/)?post/([^/]+)#', $chemin, $m ) ) {
			return;
		}
		$articles = get_posts(
			array(
				'post_type'   => 'post',
				'meta_key'    => 'er_wix_slug', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'  => urldecode( $m[1] ), // phpcs:ignore WordPress.DB.SlowDBQuery
				'numberposts' => 1,
				'lang'        => '',
			)
		);
		if ( $articles ) {
			wp_safe_redirect( get_permalink( $articles[0] ), 301 );
			exit;
		}
	}
);

/**
 * Une image de Wix dans la médiathèque (une seule fois : l'adresse d'origine est gardée).
 *
 * @param string $url    L'adresse chez Wix.
 * @param int    $parent L'article.
 * @return int L'identifiant, ou 0.
 */
function er_journal_image( $url, $parent ) {
	$deja = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'meta_key'    => 'er_source', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $url, // phpcs:ignore WordPress.DB.SlowDBQuery
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	if ( $deja ) {
		return (int) $deja[0];
	}
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$fichier = download_url( $url );
	if ( is_wp_error( $fichier ) ) {
		return 0;
	}
	$nom = sanitize_file_name( preg_replace( '/~mv2/', '', basename( wp_parse_url( $url, PHP_URL_PATH ) ) ) );
	$id  = media_handle_sideload( array( 'name' => $nom, 'tmp_name' => $fichier ), $parent );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $fichier );
		return 0;
	}
	update_post_meta( $id, 'er_source', $url );
	return (int) $id;
}

/**
 * Importe un article de l'ancien site.
 *
 * @param array $a       L'article (outils/wix/journal.py).
 * @param array $auteurs er_evt_auteurs_connus().
 * @param bool  $images  Télécharger les images.
 */
function er_journal_importer_un( $a, $auteurs, $images = true ) {
	$existants = get_posts(
		array(
			'post_type'   => 'post',
			'post_status' => 'any',
			'meta_key'    => 'er_wix_slug', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $a['wix_slug'], // phpcs:ignore WordPress.DB.SlowDBQuery
			'numberposts' => 1,
			'lang'        => '',
		)
	);
	$source = md5( wp_json_encode( array( $a['titre'], $a['contenu'], $a['extrait'] ) ) );
	$id     = $existants ? $existants[0]->ID : 0;
	$etat   = 'inchangé';
	$a_jour = ! $id || ( md5( $existants[0]->post_content ) === get_post_meta( $id, 'er_import_empreinte', true ) && get_post_meta( $id, 'er_import_source', true ) !== $source );
	if ( ! $id ) {
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'post',
					'post_status'  => 'publish',
					'post_title'   => $a['titre'],
					'post_name'    => sanitize_title( $a['slug'] ),
					'post_excerpt' => $a['extrait'],
					'post_date'    => $a['date'] ? $a['date'] : current_time( 'mysql' ),
					'post_content' => '',
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 'erreur : ' . $id->get_error_message();
		}
		update_post_meta( $id, 'er_wix_slug', $a['wix_slug'] );
		$etat = 'créé';
	} elseif ( $a_jour ) {
		$etat = 'mis à jour';
	}
	if ( $a_jour ) {
		// Les images du texte, dans la médiathèque ; leur adresse chez Wix remplacée.
		$contenu = $a['contenu'];
		if ( $images ) {
			foreach ( array_unique( $a['images'] ) as $url ) {
				$image = er_journal_image( $url, $id );
				if ( $image ) {
					$contenu = preg_replace(
						'#<!-- wp:image \{"sizeSlug":"large"\} -->(\s*<figure class="wp-block-image size-large"><img) src="' . preg_quote( $url, '#' ) . '" alt=""#',
						'<!-- wp:image {"id":' . $image . ',"sizeSlug":"large"} -->$1 src="' . esc_url( (string) wp_get_attachment_image_url( $image, 'large' ) ) . '" alt="" class="wp-image-' . $image . '"',
						$contenu
					);
				}
			}
		}
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $id,
					'post_title'   => $a['titre'],
					'post_content' => $contenu,
					'post_excerpt' => $a['extrait'],
				)
			)
		);
		update_post_meta( $id, 'er_import_source', $source );
		update_post_meta( $id, 'er_import_empreinte', md5( get_post( $id )->post_content ) );
	}
	if ( $a['signature'] && '' === (string) get_post_meta( $id, 'er_signature', true ) ) {
		update_post_meta( $id, 'er_signature', $a['signature'] );
	}
	// Les livres dont parle l'article et les mots-clés de la recherche : ajoutés à ceux qui y sont déjà.
	foreach ( array( 'er_livres' => 'livres', 'er_mots' => 'mots' ) as $cle => $champ ) {
		$deja    = array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, $cle, true ) ) ) );
		$nouveau = array_values( array_unique( array_merge( $deja, isset( $a[ $champ ] ) ? (array) $a[ $champ ] : array() ) ) );
		if ( $nouveau !== $deja ) {
			update_post_meta( $id, $cle, implode( ', ', $nouveau ) );
		}
	}
	if ( $a['categories'] ) {
		$rubriques = array();
		foreach ( $a['categories'] as $nom ) {
			$t           = term_exists( $nom, 'category' );
			$t           = $t ? $t : wp_insert_term( $nom, 'category' );
			$rubriques[] = is_wp_error( $t ) ? 0 : (int) $t['term_id'];
		}
		wp_set_post_categories( $id, array_filter( $rubriques ), true );
		wp_remove_object_terms( $id, (int) get_option( 'default_category' ), 'category' );
	}
	// Les personnes : un auteur (celui de la librairie s'il existe, sinon un nouveau) ; les sujets.
	foreach ( $a['personnes'] as $nom ) {
		$trouve = null;
		foreach ( er_auteurs_cites( $nom, $auteurs ) as list( $t ) ) {
			$trouve = $t;
			break;
		}
		if ( ! $trouve ) {
			$trouve = er_personne_par_nom( $nom, true );
		}
		if ( $trouve ) {
			wp_set_object_terms( $id, $trouve->term_id, 'auteur', true );
		}
	}
	foreach ( $a['sujets'] as $nom ) {
		$t = term_exists( $nom, 'er_sujet' ) ? term_exists( $nom, 'er_sujet' ) : wp_insert_term( $nom, 'er_sujet' );
		if ( is_wp_error( $t ) ) {
			// Un sujet qui porte le nom d'un auteur : c'est l'auteur.
			foreach ( er_auteurs_cites( $nom, $auteurs ) as list( $auteur ) ) {
				wp_set_object_terms( $id, $auteur->term_id, 'auteur', true );
			}
			continue;
		}
		wp_set_object_terms( $id, (int) $t['term_id'], 'er_sujet', true );
	}
	// Le genre (chronique, critique…), le sujet principal (le premier relu) et les langues du texte : la mise en page du journal.
	if ( ! empty( $a['genre'] ) && function_exists( 'er_genres_installer' ) ) {
		if ( ! term_exists( $a['genre'], 'er_genre' ) ) {
			er_genres_installer();
		}
		wp_set_object_terms( $id, $a['genre'], 'er_genre' );
	}
	if ( $a['sujets'] ) {
		update_post_meta( $id, 'er_sujet_principal', $a['sujets'][0] );
	}
	if ( ! empty( $a['langues'] ) ) {
		update_post_meta( $id, 'er_langues', $a['langues'] );
	}
	if ( ! empty( $a['relu'] ) ) {
		update_post_meta( $id, 'er_etiquettes_relues', 1 ); // Les étiquettes relues à la main font foi : rien n'est deviné en plus.
	}
	er_etiqueter( $id, $a['titre'] . ' ' . $a['extrait'], $auteurs );
	if ( $images && $a['image'] && ! has_post_thumbnail( $id ) ) {
		$image = er_journal_image( $a['image'], $id );
		if ( $image ) {
			set_post_thumbnail( $id, $image );
		}
	}
	return $etat;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Importe les articles du journal de l'ancien site.
	 *
	 * ## OPTIONS
	 *
	 * <fichier>
	 * : Le fichier JSON fait par outils/wix/journal.py.
	 *
	 * [--sans-images]
	 * : Ne télécharge pas les images.
	 *
	 * @param array $args  Le fichier.
	 * @param array $assoc Les options.
	 */
	WP_CLI::add_command(
		'er journal importer',
		function ( $args, $assoc ) {
			$liste = json_decode( (string) file_get_contents( $args[0] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! is_array( $liste ) ) {
				WP_CLI::error( 'Fichier illisible : ' . $args[0] );
			}
			$auteurs = er_evt_auteurs_connus();
			$bilan   = array();
			foreach ( $liste as $a ) {
				$etat = er_journal_importer_un( $a, $auteurs, empty( $assoc['sans-images'] ) );
				if ( 'créé' === $etat ) {
					$auteurs = er_evt_auteurs_connus(); // Les auteurs créés en route comptent pour les articles suivants.
				}
				$bilan[ $etat ] = ( isset( $bilan[ $etat ] ) ? $bilan[ $etat ] : 0 ) + 1;
				if ( 0 === strpos( $etat, 'erreur' ) ) {
					WP_CLI::warning( $a['titre'] . ' : ' . $etat );
				}
			}
			foreach ( $bilan as $etat => $n ) {
				WP_CLI::log( sprintf( '%4d %s', $n, $etat ) );
			}
			WP_CLI::success( count( $liste ) . ' articles lus.' );
		}
	);
}
