<?php
/**
 * Les vidéos et les podcasts du centre : un contenu à part (er_media), à l'adresse
 * /centre-culturel/videos-et-podcasts/<slug>/, étiqueté comme le reste (auteurs, sujets) et rangé en séries.
 *
 * Ce qu'il garde : le lien (YouTube, SoundCloud, un fichier audio…), le genre (vidéo ou podcast), la durée.
 * L'enregistrement d'une rencontre reste sur la page de la rencontre (« Revoir, réécouter ») : ici, les vidéos et les
 * épisodes qui ne sont pas une rencontre (les podcasts, les séries des bénévoles, les entretiens). Mais la liste les
 * montre aussi, par leur rencontre, dans « Les rencontres, à revoir » (24 nov. 2026, l'audit : 44 enregistrements
 * qu'on ne trouvait que sur la page de leur rencontre) : la carte mène à la rencontre, rien n'est recopié.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'er_media',
			array(
				'labels'        => array(
					'name'          => __( 'Vidéos et podcasts', 'er-librairie' ),
					'singular_name' => __( 'Vidéo ou podcast', 'er-librairie' ),
					'add_new'       => __( 'Ajouter', 'er-librairie' ),
					'add_new_item'  => __( 'Ajouter une vidéo ou un podcast', 'er-librairie' ),
					'edit_item'     => __( 'Modifier', 'er-librairie' ),
					'all_items'     => __( 'Toutes les vidéos et les podcasts', 'er-librairie' ),
					'search_items'  => __( 'Chercher', 'er-librairie' ),
					'not_found'     => __( 'Rien pour l’instant', 'er-librairie' ),
					'menu_name'     => __( 'Vidéos et podcasts', 'er-librairie' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-format-video',
				'menu_position' => 27,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
				'has_archive'   => false,
				'rewrite'       => array(
					'slug'       => 'centre-culturel/videos-et-podcasts',
					'with_front' => false,
				),
			)
		);
		register_taxonomy(
			'er_serie',
			'er_media',
			array(
				'labels'             => array(
					'name'          => __( 'Séries', 'er-librairie' ),
					'singular_name' => __( 'Série', 'er-librairie' ),
					'all_items'     => __( 'Toutes les séries', 'er-librairie' ),
					'edit_item'     => __( 'Modifier la série', 'er-librairie' ),
					'add_new_item'  => __( 'Ajouter une série', 'er-librairie' ),
					'menu_name'     => __( 'Séries', 'er-librairie' ),
				),
				'public'             => true,
				'publicly_queryable' => false,
				'rewrite'            => false,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'hierarchical'       => false,
			)
		);
		foreach ( array( 'er_lien', 'er_genre', 'er_duree', 'er_ident' ) as $cle ) {
			register_post_meta(
				'er_media',
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
	},
	6
);

/**
 * Les liens d'une vidéo ou d'un podcast : [ titre, lien ] (le premier est le principal).
 *
 * @param int $id Le contenu.
 */
function er_media_liens( $id ) {
	$liens = array();
	foreach ( preg_split( '/\R/', (string) get_post_meta( $id, 'er_lien', true ) ) as $ligne ) {
		$ligne = trim( $ligne );
		$titre = '';
		if ( false !== strpos( $ligne, '|' ) ) {
			list( $titre, $ligne ) = array_map( 'trim', explode( '|', $ligne, 2 ) );
		}
		if ( preg_match( '#^https?://#', $ligne ) ) {
			$liens[] = array( $titre, esc_url_raw( $ligne ) );
		}
	}
	return $liens;
}

/**
 * Faute d'image : une vignette typographique sur le bleu du centre.
 *
 * @param int $id Le contenu.
 */
function er_media_vignette( $id ) {
	$podcast = 'podcast' === get_post_meta( $id, 'er_genre', true );
	return '<span class="er-media-vignette" aria-hidden="true" lang="ru">' . ( $podcast ? 'Подкаст' : 'Видео' ) . '</span>';
}

/* La saisie ------------------------------------------------------------------------------------------------------- */

add_action(
	'add_meta_boxes_er_media',
	function () {
		add_meta_box(
			'er-media',
			__( 'La vidéo ou le podcast', 'er-librairie' ),
			function ( $post ) {
				wp_nonce_field( 'er_media', 'er_media_jeton' );
				$genre = (string) get_post_meta( $post->ID, 'er_genre', true );
				echo '<table class="form-table" role="presentation"><tbody>';
				echo '<tr><th scope="row"><label for="er_lien">' . esc_html__( 'Lien', 'er-librairie' ) . '</label></th><td><textarea class="large-text code" rows="3" id="er_lien" name="er_lien">' . esc_textarea( (string) get_post_meta( $post->ID, 'er_lien', true ) ) . '</textarea>'
					. '<p class="description">' . esc_html__( 'YouTube, Vimeo, SoundCloud, Spotify, un fichier audio ou vidéo ; un lien par ligne, le premier est le principal. Pour un titre : « Titre | lien ».', 'er-librairie' ) . '</p></td></tr>';
				echo '<tr><th scope="row"><label for="er_genre">' . esc_html__( 'Genre', 'er-librairie' ) . '</label></th><td><select id="er_genre" name="er_genre">'
					. '<option value="video"' . selected( $genre, 'video', false ) . '>' . esc_html__( 'Vidéo', 'er-librairie' ) . '</option>'
					. '<option value="podcast"' . selected( $genre, 'podcast', false ) . '>' . esc_html__( 'Podcast', 'er-librairie' ) . '</option></select></td></tr>';
				echo '<tr><th scope="row"><label for="er_duree">' . esc_html__( 'Durée (minutes)', 'er-librairie' ) . '</label></th><td><input type="number" min="0" class="small-text" id="er_duree" name="er_duree" value="' . esc_attr( (string) get_post_meta( $post->ID, 'er_duree', true ) ) . '"></td></tr>';
				echo '</tbody></table>';
			},
			'er_media',
			'normal',
			'high'
		);
	}
);
add_action(
	'save_post_er_media',
	function ( $id ) {
		if ( ! isset( $_POST['er_media_jeton'] ) || ! wp_verify_nonce( sanitize_key( $_POST['er_media_jeton'] ), 'er_media' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		update_post_meta( $id, 'er_lien', sanitize_textarea_field( wp_unslash( $_POST['er_lien'] ?? '' ) ) );
		update_post_meta( $id, 'er_genre', 'podcast' === ( $_POST['er_genre'] ?? '' ) ? 'podcast' : 'video' );
		update_post_meta( $id, 'er_duree', '' === trim( (string) ( $_POST['er_duree'] ?? '' ) ) ? '' : (string) absint( $_POST['er_duree'] ) );
	}
);

/* L'affichage ------------------------------------------------------------------------------------------------------
 *
 * er/media-lecteur (le lecteur, le genre, la série, la date et la durée, sur la page d'une vidéo ou d'un podcast) et
 * er/medias (la liste, à poser sur « Nos vidéos » ou « Nos podcasts » : l'attribut genre, video ou podcast).
 */

add_action(
	'init',
	function () {
		register_block_type(
			'er/media-lecteur',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID();
					return er_media_bloc_lecteur( $id );
				},
			)
		);
		register_block_type(
			'er/medias',
			array(
				'api_version'     => 3,
				'attributes'      => array(
					'genre' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'render_callback' => function ( $attributs ) {
					return er_media_bloc_liste( isset( $attributs['genre'] ) ? $attributs['genre'] : '' );
				},
			)
		);
	}
);

/**
 * Le lecteur et la ligne de la vidéo ou du podcast.
 *
 * @param int $id Le contenu.
 */
function er_media_bloc_lecteur( $id ) {
	if ( 'er_media' !== get_post_type( $id ) ) {
		return '';
	}
	$liens   = er_media_liens( $id );
	$parties = array( 'podcast' === get_post_meta( $id, 'er_genre', true ) ? __( 'Podcast', 'er-librairie' ) : __( 'Vidéo', 'er-librairie' ) );
	foreach ( wp_get_post_terms( $id, 'er_serie' ) as $serie ) {
		$parties[] = $serie->name;
	}
	$parties[] = get_the_date( __( 'j F Y', 'er-librairie' ), $id );
	$duree     = (int) get_post_meta( $id, 'er_duree', true );
	if ( $duree ) {
		/* translators: %d: la durée en minutes */
		$parties[] = sprintf( __( '%d min', 'er-librairie' ), $duree );
	}
	$html = '<p class="er-evt-surtitre er-points-centre">' . esc_html( implode( ' · ', $parties ) ) . '</p>';
	foreach ( $liens as $i => list( $titre, $url ) ) {
		$html .= '<figure class="er-evt-media' . ( 0 === $i ? ' er-media-principal' : '' ) . '">' . er_evt_lecteur( $url ) . ( $titre ? '<figcaption>' . esc_html( $titre ) . '</figcaption>' : '' ) . '</figure>';
	}
	return '<div class="er-media-tete">' . $html . '</div>';
}

/**
 * Le genre d'un lien : podcast pour un son (SoundCloud, Spotify, un fichier audio), vidéo sinon.
 *
 * @param string $url Le lien.
 */
function er_media_genre_du_lien( $url ) {
	return preg_match( '#soundcloud\.com|spotify\.com|anchor\.fm|podcasts?\.|\.(mp3|m4a|ogg|wav)(\?|$)#i', $url ) ? 'podcast' : 'video';
}

/**
 * Les rencontres passées qui ont un enregistrement du genre voulu, les plus récentes d'abord.
 *
 * @param string $genre video, podcast, ou les deux.
 * @return WP_Post[]
 */
function er_media_rencontres( $genre ) {
	if ( ! defined( 'ER_EVT' ) || ! function_exists( 'er_evt_videos' ) ) {
		return array();
	}
	$rencontres = get_posts(
		array(
			'post_type'      => ER_EVT,
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'meta_query'     => array( array( 'key' => 'er_videos', 'value' => 'http', 'compare' => 'LIKE' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_key'       => 'er_debut', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);
	return array_values(
		array_filter(
			$rencontres,
			function ( $r ) use ( $genre ) {
				foreach ( er_evt_videos( $r->ID ) as list( , $url ) ) {
					if ( ! in_array( $genre, array( 'video', 'podcast' ), true ) || er_media_genre_du_lien( $url ) === $genre ) {
						return true;
					}
				}
				return false;
			}
		)
	);
}

/**
 * La liste des vidéos ou des podcasts, par série puis les autres, les plus récents d'abord.
 *
 * @param string $genre video, podcast, ou les deux.
 */
function er_media_bloc_liste( $genre ) {
	$args = array(
		'post_type'      => 'er_media',
		'post_status'    => 'publish',
		'posts_per_page' => 200,
	);
	if ( in_array( $genre, array( 'video', 'podcast' ), true ) ) {
		$args['meta_query'] = array( array( 'key' => 'er_genre', 'value' => $genre ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$medias     = get_posts( $args );
	$rencontres = er_media_rencontres( $genre );
	if ( ! $medias && ! $rencontres ) {
		return '<p class="er-evts-vide">' . esc_html__( 'Rien pour l’instant.', 'er-librairie' ) . '</p>';
	}
	update_post_thumbnail_cache( new WP_Query( $args ) );
	$groupes = array();
	foreach ( $medias as $m ) {
		$series = wp_get_post_terms( $m->ID, 'er_serie', array( 'fields' => 'names' ) );
		$nom    = $series && ! is_wp_error( $series ) ? $series[0] : __( 'Et aussi', 'er-librairie' );
		$groupes[ $nom ][] = $m;
	}
	$autres = __( 'Et aussi', 'er-librairie' );
	if ( $rencontres ) { // Les enregistrements des rencontres, après les séries, par leur rencontre.
		$groupes[ __( 'Les rencontres, à revoir', 'er-librairie' ) ] = $rencontres;
	}
	if ( isset( $groupes[ $autres ] ) ) { // Les vidéos sans série, à la fin.
		$fin = $groupes[ $autres ];
		unset( $groupes[ $autres ] );
		$groupes[ $autres ] = $fin;
	}
	$html = '';
	foreach ( $groupes as $nom => $liste ) {
		if ( $autres !== $nom && $liste !== $rencontres ) {
			usort( $liste, fn( $x, $y ) => strnatcasecmp( $x->post_title, $y->post_title ) ); // Épisode 1, 2… 10.
		}
		$html .= '<section class="er-autour-section"><h2 class="er-evts-titre">' . esc_html( $nom ) . '</h2><ul class="er-cartes">' . implode( '', array_map( 'er_carte_contenu', $liste ) ) . '</ul></section>';
	}
	return '<div class="er-autour alignwide">' . $html . '</div>';
}

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
