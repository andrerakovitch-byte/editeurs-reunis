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
		$genre = sanitize_key( wp_unslash( $_POST['er_genre'] ?? '' ) );
		$duree = trim( sanitize_text_field( wp_unslash( $_POST['er_duree'] ?? '' ) ) );
		update_post_meta( $id, 'er_genre', 'podcast' === $genre ? 'podcast' : 'video' );
		update_post_meta( $id, 'er_duree', '' === $duree ? '' : (string) absint( $duree ) );
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
	$html    = '';
	$entrees = array();
	foreach ( $groupes as $nom => $liste ) {
		if ( $autres !== $nom && $liste !== $rencontres ) {
			usort( $liste, fn( $x, $y ) => strnatcasecmp( $x->post_title, $y->post_title ) ); // Épisode 1, 2… 10.
		}
		$ancre     = 'er-serie-' . sanitize_title( remove_accents( $nom ) );
		$entrees[] = array(
			'texte'   => $nom,
			'lien'    => '#' . $ancre,
			'courant' => ! $entrees,
		);
		$html .= '<section class="er-autour-section" id="' . esc_attr( $ancre ) . '"><h2 class="er-evts-titre">' . esc_html( $nom ) . '</h2><ul class="er-cartes">' . implode( '', array_map( 'er_carte_contenu', $liste ) ) . '</ul></section>';
	}
	// La bande de la page (la bande de page du thème) : les séries, en sommaire.
	$page  = get_post();
	$bande = function_exists( 'er_bande_page' ) && count( $entrees ) > 1
		? er_bande_page(
			array(
				'maison'   => 'centre',
				'nom'      => $page && 'page' === $page->post_type ? html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ) : ( 'podcast' === $genre ? __( 'Nos podcasts', 'er-librairie' ) : __( 'Nos vidéos', 'er-librairie' ) ),
				'sommaire' => true,
				'entrees'  => $entrees,
			)
		)
		: '';
	return $bande . '<div class="er-autour alignwide">' . $html . '</div>';
}
