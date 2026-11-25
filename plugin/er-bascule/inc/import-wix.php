<?php
/**
 * Les imports de l'ancien site Wix : les rencontres, le journal, les vidéos et les podcasts, les expositions. Ils ont
 * servi à remplir le site et se relancent jusqu'à la bascule (le workflow « Déployer chez Kinsta », « ancien site ») ;
 * ils sortiront avec cette extension. Repris tels quels de l'extension de la librairie, dont ils appellent les fonctions
 * (les champs des rencontres, les étiquettes, les auteurs connus…).
 *
 * @package er-bascule
 */

defined( 'ABSPATH' ) || exit;

/* == Les rencontres (venu de er-librairie/inc/evenements.php) ======================================================================== */

/**
 * Importe une rencontre de l'ancien site.
 *
 * @param array $e       La rencontre (outils/wix/evenements.py).
 * @param array $auteurs er_evt_auteurs_connus().
 * @param bool  $images  Télécharger l'affiche.
 * @return string créée, mise à jour, inchangée.
 */
function er_evt_importer_un( $e, $auteurs, $images = true ) {
	$existants = get_posts(
		array(
			'post_type'   => ER_EVT,
			'post_status' => 'any',
			'meta_key'    => 'er_wix', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $e['wix'], // phpcs:ignore WordPress.DB.SlowDBQuery
			'numberposts' => 1,
			'lang'        => '',
		)
	);
	$texte  = array(
		'post_title'   => $e['titre'],
		'post_content' => $e['contenu'],
		'post_excerpt' => $e['chapo'],
	);
	$source = md5( wp_json_encode( $texte ) );
	// L'empreinte de ce qui est en base après l'import (WordPress filtre le HTML) : si elle a changé, c'est qu'on
	// a modifié la rencontre à la main, et l'import n'y touche plus.
	$en_base = function ( $id ) {
		$p = get_post( $id );
		return md5( wp_json_encode( array( $p->post_title, $p->post_content, $p->post_excerpt ) ) );
	};
	if ( $existants ) {
		$id   = $existants[0]->ID;
		$etat = 'inchangée';
		if ( $en_base( $id ) === get_post_meta( $id, 'er_import_empreinte', true ) && get_post_meta( $id, 'er_import_source', true ) !== $source ) {
			wp_update_post( array( 'ID' => $id ) + wp_slash( $texte ) );
			$etat = 'mise à jour';
		}
	} else {
		$id = wp_insert_post(
			wp_slash(
				$texte + array(
					'post_type'   => ER_EVT,
					'post_status' => 'publish',
					'post_name'   => $e['slug'],
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 'erreur : ' . $id->get_error_message();
		}
		$etat = 'créée';
	}
	if ( 'inchangée' !== $etat ) {
		update_post_meta( $id, 'er_import_source', $source );
		update_post_meta( $id, 'er_import_empreinte', $en_base( $id ) );
	}
	$champs = array(
		'er_wix'         => $e['wix'],
		'er_debut'       => $e['debut'],
		'er_fin'         => $e['fin'],
		'er_lieu'        => $e['lieu'],
		'er_forme'       => $e['forme'],
		'er_langue'      => $e['langue'],
		'er_inscription' => 'gratuite' === $e['inscription'] ? 'gratuite' : ( 'billetterie' === $e['inscription'] ? 'billetterie' : '' ),
		'er_etat'        => 'annule' === $e['statut'] ? 'annule' : '',
	);
	foreach ( $champs as $cle => $valeur ) {
		if ( '' === (string) get_post_meta( $id, $cle, true ) || in_array( $cle, array( 'er_wix', 'er_debut', 'er_fin', 'er_etat' ), true ) ) {
			update_post_meta( $id, $cle, (string) $valeur );
		}
	}
	// Les vidéos : celles de l'import s'ajoutent, aucune ne se retire.
	$deja   = (string) get_post_meta( $id, 'er_videos', true );
	$lignes = array_filter( preg_split( '/\R/', $deja ) );
	foreach ( isset( $e['videos'] ) ? $e['videos'] : array() as $v ) {
		$url = is_array( $v ) ? $v['url'] : $v;
		if ( false === strpos( $deja, $url ) ) {
			$lignes[] = is_array( $v ) && ! empty( $v['titre'] ) ? $v['titre'] . ' | ' . $url : $url;
		}
	}
	if ( implode( "\n", $lignes ) !== $deja ) {
		update_post_meta( $id, 'er_videos', implode( "\n", $lignes ) );
	}
	if ( ! empty( $e['cycle'] ) ) {
		wp_set_object_terms( $id, $e['cycle'], 'er_cycle', true );
	}
	// Les intervenants : les auteurs de la librairie que le titre ou le chapô présentent comme tels (« avec Georges
	// Nivat », « par Hélène Henry »). Un auteur dont on parle (« le Docteur Jivago de Boris Pasternak ») est un auteur
	// lié (le classement des auteurs) ; les sujets se devinent aussi (sujets.php).
	$cyrillique = (bool) preg_match( '/\p{Cyrillic}/u', $e['titre'] );
	$relues     = (bool) get_post_meta( $id, 'er_etiquettes_relues', true ); // Recoupées à la main (evenements-recoupes.php).
	foreach ( $relues ? array() : er_auteurs_cites( $e['titre'] . ' ' . $e['chapo'], $auteurs ) as list( $t, $noms, $intervient ) ) {
		if ( ! $intervient ) {
			continue;
		}
		// Le nom dans l'écriture du titre : « Andreï Zviaguintsev » dans un titre en français.
		$affiche = $t->name;
		foreach ( $noms as $n ) {
			if ( (bool) preg_match( '/\p{Cyrillic}/u', $n ) === $cyrillique ) {
				$affiche = $n;
				break;
			}
		}
		er_personnes_miroir( $t, 'er_intervenant', $affiche );
		wp_set_object_terms( $id, $t->slug, 'er_intervenant', true );
	}
	er_etiqueter( $id, $e['titre'] . ' ' . $e['chapo'], $auteurs );
	if ( $images && ! empty( $e['image'] ) && ! has_post_thumbnail( $id ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$image = media_sideload_image( $e['image'], $id, $e['titre'], 'id' );
		if ( ! is_wp_error( $image ) ) {
			set_post_thumbnail( $id, $image );
		}
	}
	er_evt_billet_synchroniser( $id );
	return $etat;
}

/**
 * La page « Nos événements » montre la liste (le bloc er/evenements), si elle ne l'a pas déjà.
 */
function er_evt_page_avec_liste() {
	$page = get_page_by_path( 'centre-culturel/evenements' );
	if ( ! $page ) {
		return;
	}
	if ( ! get_post_meta( $page->ID, '_wp_page_template', true ) || 'default' === get_post_meta( $page->ID, '_wp_page_template', true ) ) {
		update_post_meta( $page->ID, '_wp_page_template', 'page-large' ); // La liste prend la largeur du site.
	}
	if ( has_block( 'er/evenements', $page ) ) {
		return;
	}
	$contenu = preg_replace( '#<!-- wp:paragraph -->\s*<p><em>Cette page est une ébauche[^<]*</em></p>\s*<!-- /wp:paragraph -->#u', '', $page->post_content );
	if ( '' === trim( $contenu ) ) {
		$contenu = "<!-- wp:paragraph -->\n<p>" . esc_html__( 'Les rencontres, les conférences, les lectures et les expositions du centre, au 11, rue de la Montagne-Sainte-Geneviève.', 'er-librairie' ) . "</p>\n<!-- /wp:paragraph -->";
	}
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => trim( $contenu ) . "\n\n<!-- wp:er/evenements /-->",
		)
	);
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Importe les rencontres de l'ancien site.
	 *
	 * ## OPTIONS
	 *
	 * <fichier>
	 * : Le fichier JSON fait par outils/wix/evenements.py.
	 *
	 * [--sans-images]
	 * : Ne télécharge pas les affiches.
	 *
	 * @param array $args  Le fichier.
	 * @param array $assoc Les options.
	 */
	WP_CLI::add_command(
		'er evenements importer',
		function ( $args, $assoc ) {
			$liste = json_decode( (string) file_get_contents( $args[0] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! is_array( $liste ) ) {
				WP_CLI::error( 'Fichier illisible : ' . $args[0] );
			}
			$auteurs = er_evt_auteurs_connus();
			$bilan   = array();
			foreach ( $liste as $e ) {
				$etat = er_evt_importer_un( $e, $auteurs, empty( $assoc['sans-images'] ) );
				$bilan[ $etat ] = ( isset( $bilan[ $etat ] ) ? $bilan[ $etat ] : 0 ) + 1;
				if ( 0 === strpos( $etat, 'erreur' ) ) {
					WP_CLI::warning( $e['titre'] . ' : ' . $etat );
				}
			}
			er_evt_page_avec_liste();
			delete_transient( 'er_evt_annees' );
			foreach ( $bilan as $etat => $n ) {
				WP_CLI::log( sprintf( '%4d %s', $n, $etat ) );
			}
			WP_CLI::success( count( $liste ) . ' rencontres lues.' );
		}
	);
}

/* == Le journal (venu de er-librairie/inc/journal.php) =============================================================================== */

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

/* == Les vidéos et les podcasts (venu de er-librairie/inc/medias.php) ================================================================ */

/* L'import -------------------------------------------------------------------------------------------------------
 *
 * wp er medias importer <fichier.json> : le fichier que fait outils/wix/medias.py (les vidéos de la chaîne du centre
 * qui ne sont pas une rencontre, les épisodes des podcasts). Une vidéo se reconnaît à son identifiant (er_ident) :
 * relancé, l'import ne crée rien en double et ne touche pas à ce qu'on a modifié.
 */

/**
 * Importe une vidéo ou un épisode.
 *
 * @param array $m       La vidéo (ident, titre, url, genre, serie, date, duree, description, image, auteurs, sujets).
 * @param array $auteurs er_evt_auteurs_connus().
 * @param bool  $images  Télécharger l'image.
 */
function er_media_importer_un( $m, $auteurs, $images = true ) {
	$existants = get_posts(
		array(
			'post_type'   => 'er_media',
			'post_status' => 'any',
			'meta_key'    => 'er_ident', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $m['ident'], // phpcs:ignore WordPress.DB.SlowDBQuery
			'numberposts' => 1,
			'lang'        => '',
		)
	);
	if ( $existants ) {
		$id   = $existants[0]->ID;
		$etat = 'inchangée';
	} else {
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'er_media',
					'post_status'  => 'publish',
					'post_title'   => $m['titre'],
					'post_content' => $m['description'] ? '<!-- wp:paragraph --><p>' . esc_html( $m['description'] ) . '</p><!-- /wp:paragraph -->' : '',
					'post_date'    => $m['date'] ? $m['date'] . ' 12:00:00' : current_time( 'mysql' ),
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 'erreur : ' . $id->get_error_message();
		}
		update_post_meta( $id, 'er_ident', $m['ident'] );
		update_post_meta( $id, 'er_lien', $m['url'] );
		update_post_meta( $id, 'er_genre', 'podcast' === $m['genre'] ? 'podcast' : 'video' );
		update_post_meta( $id, 'er_duree', $m['duree'] ? (string) (int) round( $m['duree'] / 60 ) : '' );
		$etat = 'créée';
	}
	if ( ! empty( $m['serie'] ) ) {
		wp_set_object_terms( $id, $m['serie'], 'er_serie', true );
	}
	// Sans date (les pistes de SoundCloud), la date de publication est celle de l'import : elle ne dit rien.
	update_post_meta( $id, 'er_date_connue', $m['date'] ? '1' : '0' );
	er_etiqueter( $id, $m['titre'] . ' ' . $m['description'], $auteurs );
	foreach ( isset( $m['auteurs'] ) ? $m['auteurs'] : array() as $nom ) {
		$cle = er_cle_recherche( $nom );
		if ( isset( $auteurs[ $cle ] ) ) {
			wp_set_object_terms( $id, $auteurs[ $cle ][0]->term_id, 'auteur', true );
		}
	}
	if ( ! empty( $m['sujets'] ) ) {
		wp_set_object_terms( $id, $m['sujets'], 'er_sujet', true );
	}
	if ( $images && ! empty( $m['image'] ) && ! has_post_thumbnail( $id ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$fichier = download_url( $m['image'] );
		if ( ! is_wp_error( $fichier ) ) {
			$image = media_handle_sideload( array( 'name' => sanitize_file_name( $m['ident'] ) . '.jpg', 'tmp_name' => $fichier ), $id, $m['titre'] );
			if ( is_wp_error( $image ) ) {
				wp_delete_file( $fichier );
			} else {
				set_post_thumbnail( $id, $image );
			}
		}
	}
	return $etat;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Importe les vidéos et les podcasts de l'ancien site.
	 *
	 * ## OPTIONS
	 *
	 * <fichier>
	 * : Le fichier JSON fait par outils/wix/medias.py.
	 *
	 * [--sans-images]
	 * : Ne télécharge pas les images.
	 *
	 * @param array $args  Le fichier.
	 * @param array $assoc Les options.
	 */
	WP_CLI::add_command(
		'er medias importer',
		function ( $args, $assoc ) {
			$liste = json_decode( (string) file_get_contents( $args[0] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! is_array( $liste ) ) {
				WP_CLI::error( 'Fichier illisible : ' . $args[0] );
			}
			$auteurs = er_evt_auteurs_connus();
			$bilan   = array();
			foreach ( $liste as $m ) {
				$etat           = er_media_importer_un( $m, $auteurs, empty( $assoc['sans-images'] ) );
				$bilan[ $etat ] = ( isset( $bilan[ $etat ] ) ? $bilan[ $etat ] : 0 ) + 1;
			}
			foreach ( $bilan as $etat => $n ) {
				WP_CLI::log( sprintf( '%4d %s', $n, $etat ) );
			}
			WP_CLI::success( count( $liste ) . ' vidéos et podcasts lus.' );
		}
	);
}

/* == Les expositions (venu de er-librairie/inc/expositions.php) ====================================================================== */

/**
 * La page « Nos expositions » porte la liste (le bloc er/expositions), qui a sa propre tête : le gabarit en modules,
 * pleine largeur, sans titre.
 */
function er_expo_page_avec_liste() {
	$page = get_page_by_path( 'centre-culturel/expositions' );
	if ( ! $page ) {
		return;
	}
	$gabarit = (string) get_post_meta( $page->ID, '_wp_page_template', true );
	if ( in_array( $gabarit, array( '', 'default', 'page-large', 'page-sans-titre' ), true ) ) {
		update_post_meta( $page->ID, '_wp_page_template', 'page-modules' ); // Pleine largeur ; la liste a sa tête (le titre, la phrase).
	}
	if ( has_block( 'er/expositions', $page ) ) {
		return;
	}
	// L'ébauche s'en va ; un texte déjà écrit reste, sous la liste.
	$contenu = trim( preg_replace( '#<!-- wp:paragraph -->\s*<p><em>Cette page est une ébauche[^<]*</em></p>\s*<!-- /wp:paragraph -->#u', '', $page->post_content ) );
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => "<!-- wp:er/expositions /-->" . ( $contenu ? "\n\n" . $contenu : '' ),
		)
	);
}

/* L'import des expositions de l'ancien site ------------------------------------------------------------------------ */

/**
 * Crée ou complète une exposition d'après son entrée (donnees/expositions.json) : ce qu'on a saisi à la main l'emporte,
 * les rattachements s'ajoutent.
 *
 * @param array $x L'entrée.
 */
function er_expo_importer_un( $x ) {
	$existantes = get_posts( array( 'post_type' => ER_EXPO, 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => 'er_expo_cle', 'meta_value' => $x['cle'], 'lang' => '' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	// Les rencontres de l'exposition (le vernissage, la soirée de clôture) : la première donne le texte et l'image.
	$rencontres = array();
	foreach ( isset( $x['evenements'] ) ? $x['evenements'] : array() as $wix ) {
		$r = get_posts( array( 'post_type' => ER_EVT, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'er_wix', 'meta_value' => $wix, 'lang' => '' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $r ) {
			$rencontres[] = $r[0];
		}
	}
	$premiere = $rencontres ? get_post( $rencontres[0] ) : null;
	if ( $existantes ) {
		$id   = $existantes[0]->ID;
		$etat = 'complétée';
	} else {
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => ER_EXPO,
					'post_status'  => 'publish',
					'post_title'   => $x['titre'],
					'post_name'    => sanitize_title( $x['cle'] ),
					'post_excerpt' => isset( $x['chapo'] ) ? $x['chapo'] : ( $premiere ? $premiere->post_excerpt : '' ),
					'post_content' => $premiere ? $premiere->post_content : '',
					'post_date'    => $x['debut'] ? $x['debut'] . ' 12:00:00' : current_time( 'mysql' ),
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 'erreur : ' . $id->get_error_message();
		}
		update_post_meta( $id, 'er_expo_cle', $x['cle'] );
		if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_default_language' ) ) {
			pll_set_post_language( $id, pll_default_language() ); // En français, comme les rencontres.
		}
		$etat = 'créée';
	}
	// La ligne des artistes : la ligne donnée, sinon les artistes dans leur ordre (les personnes dont il est question,
	// Pasternak pour les gravures du Docteur Jivago, sont des auteurs de l'exposition, pas ses artistes).
	if ( empty( $x['ligne'] ) && ! empty( $x['artistes'] ) ) {
		$x['ligne'] = implode( ', ', $x['artistes'] );
	}
	foreach ( array( 'er_debut' => 'debut', 'er_fin' => 'fin', 'er_artistes' => 'ligne', 'er_entree' => 'entree', 'er_lieu' => 'lieu' ) as $cle => $champ ) {
		if ( ! empty( $x[ $champ ] ) && '' === (string) get_post_meta( $id, $cle, true ) ) {
			update_post_meta( $id, $cle, $x[ $champ ] );
		}
	}
	// Le chapô relu remplace celui qu'on avait pris à la rencontre (souvent une invitation), jamais un chapô écrit à la
	// main.
	$chapo = (string) get_post_field( 'post_excerpt', $id );
	if ( ! empty( $x['chapo'] ) && $x['chapo'] !== $chapo && ( '' === trim( $chapo ) || ( $premiere && $chapo === $premiere->post_excerpt ) ) ) {
		wp_update_post( wp_slash( array( 'ID' => $id, 'post_excerpt' => $x['chapo'] ) ) );
	}
	if ( $premiere && ! has_post_thumbnail( $id ) && has_post_thumbnail( $premiere ) ) {
		set_post_thumbnail( $id, get_post_thumbnail_id( $premiere ) );
	}
	foreach ( array_merge( isset( $x['artistes'] ) ? $x['artistes'] : array(), isset( $x['personnes'] ) ? $x['personnes'] : array() ) as $nom ) {
		$t = er_personne_par_nom( $nom, true );
		if ( $t ) {
			wp_set_object_terms( $id, (int) $t->term_id, 'auteur', true );
		}
	}
	if ( ! empty( $x['techniques'] ) ) {
		$poses = wp_set_object_terms( $id, $x['techniques'], 'er_technique', true );
		if ( ! is_wp_error( $poses ) && function_exists( 'pll_set_term_language' ) && function_exists( 'pll_get_term_language' ) ) {
			foreach ( $poses as $tt ) {
				$t = get_term_by( 'term_taxonomy_id', $tt, 'er_technique' );
				if ( $t && ! pll_get_term_language( $t->term_id ) ) {
					pll_set_term_language( $t->term_id, pll_default_language() );
				}
			}
		}
	}
	foreach ( isset( $x['sujets'] ) ? $x['sujets'] : array() as $nom ) {
		$t = term_exists( $nom, 'er_sujet' );
		if ( $t ) {
			wp_set_object_terms( $id, (int) $t['term_id'], 'er_sujet', true );
		}
	}
	foreach ( $rencontres as $r ) {
		er_expo_rattacher( $id, $r );
	}
	foreach ( isset( $x['articles'] ) ? $x['articles'] : array() as $wix ) {
		$a = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'er_wix_slug', 'meta_value' => $wix, 'lang' => '' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $a ) {
			er_expo_rattacher( $id, $a[0] );
		}
	}
	foreach ( isset( $x['livres'] ) ? $x['livres'] : array() as $ref ) {
		$l = function_exists( 'wc_get_product_id_by_sku' ) ? wc_get_product_id_by_sku( $ref ) : 0;
		if ( $l ) {
			er_expo_rattacher( $id, $l );
		}
	}
	return $etat;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Crée les expositions de l'ancien site (donnees/expositions.json), ou les complète ; met la liste sur la page
	 * « Nos expositions ».
	 *
	 * ## OPTIONS
	 *
	 * [<fichier>]
	 * : Un autre fichier que donnees/expositions.json.
	 */
	WP_CLI::add_command(
		'er expositions importer',
		function ( $args ) {
			$fichier = isset( $args[0] ) ? $args[0] : dirname( __DIR__ ) . '/donnees/expositions.json';
			$liste   = json_decode( (string) file_get_contents( $fichier ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! is_array( $liste ) ) {
				WP_CLI::error( 'Fichier illisible : ' . $fichier );
			}
			$comptes = array();
			foreach ( $liste as $x ) {
				$etat             = er_expo_importer_un( $x );
				$comptes[ $etat ] = isset( $comptes[ $etat ] ) ? $comptes[ $etat ] + 1 : 1;
				WP_CLI::log( $x['titre'] . ' : ' . $etat );
			}
			er_expo_page_avec_liste();
			$dits = array();
			foreach ( $comptes as $etat => $n ) {
				$dits[] = $n . ' ' . $etat . ( $n > 1 && preg_match( '/ée$/u', $etat ) ? 's' : '' ); // 8 créées, 1 complétée.
			}
			WP_CLI::success( count( $liste ) . ' expositions lues (' . implode( ', ', $dits ) . ').' );
		}
	);
}
