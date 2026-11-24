<?php
/**
 * La page d'un auteur, comme une page d'encyclopédie : son portrait, ses dates, sa présentation, sa biographie, des
 * photos, des liens ; puis ses livres (la librairie), les livres autour de lui, les rencontres, les vidéos et les
 * podcasts, les articles du journal et les pages qui le concernent (le bloc er/autour, sujets.php).
 *
 * Tout se saisit dans la fiche de l'auteur (Produits → Auteurs → Modifier) : le portrait et les photos par la
 * médiathèque, la biographie dans un éditeur de texte (avec ses images), la présentation dans le champ
 * « Description » de WordPress. Une page de l'encyclopédie peut lui être liée : l'une mène à l'autre.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les champs de la fiche d'un auteur : clé → libellé, genre, aide.
 */
function er_auteur_champs() {
	return array(
		'er_portrait'   => array( __( 'Portrait', 'er-librairie' ), 'image', '' ),
		'er_naissance'  => array( __( 'Naissance', 'er-librairie' ), 'texte', __( 'Le lieu et la date : « Kislovodsk, 11 décembre 1918 ».', 'er-librairie' ) ),
		'er_deces'      => array( __( 'Mort', 'er-librairie' ), 'texte', __( '« Moscou, 3 août 2008 » ; vide s’il est vivant.', 'er-librairie' ) ),
		'er_biographie' => array( __( 'Biographie', 'er-librairie' ), 'riche', __( 'Le texte de la page, avec ses intertitres et ses images. La présentation courte est le champ « Description ».', 'er-librairie' ) ),
		'er_photos'     => array( __( 'Photos', 'er-librairie' ), 'images', __( 'Leur légende est celle de la médiathèque.', 'er-librairie' ) ),
		'er_liens'      => array( __( 'Pour aller plus loin', 'er-librairie' ), 'liens', __( 'Un lien par ligne : « Titre | lien ».', 'er-librairie' ) ),
		'er_page'       => array( __( 'Page de l’encyclopédie', 'er-librairie' ), 'page', __( 'La page qui lui est consacrée, s’il y en a une : elle mène à celle-ci, et celle-ci à elle.', 'er-librairie' ) ),
	);
}

/* La saisie -------------------------------------------------------------------------------------------------------- */

add_action(
	'auteur_edit_form_fields',
	function ( $terme ) {
		wp_nonce_field( 'er_auteur', 'er_auteur_jeton' );
		foreach ( er_auteur_champs() as $cle => list( $libelle, $genre, $aide ) ) {
			$valeur = (string) get_term_meta( $terme->term_id, $cle, true );
			echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $cle ) . '">' . esc_html( $libelle ) . '</label></th><td>';
			switch ( $genre ) {
				case 'image':
				case 'images':
					$ids = array_filter( array_map( 'absint', explode( ',', $valeur ) ) );
					echo '<div class="er-medias-choix" data-multiple="' . ( 'images' === $genre ? '1' : '' ) . '">'
						. '<input type="hidden" id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '">'
						. '<div class="er-medias-apercu" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px">';
					foreach ( $ids as $i ) {
						echo wp_get_attachment_image( $i, 'thumbnail', false, array( 'style' => 'width:80px;height:80px;object-fit:cover' ) );
					}
					echo '</div><button type="button" class="button er-medias-choisir">' . esc_html( 'images' === $genre ? __( 'Choisir les photos', 'er-librairie' ) : __( 'Choisir le portrait', 'er-librairie' ) ) . '</button> '
						. '<button type="button" class="button-link er-medias-oter">' . esc_html__( 'Ôter', 'er-librairie' ) . '</button></div>';
					break;
				case 'riche':
					wp_editor(
						$valeur,
						'er_biographie',
						array(
							'textarea_name' => 'er_biographie',
							'textarea_rows' => 18,
							'media_buttons' => true,
						)
					);
					break;
				case 'liens':
					echo '<textarea id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '" rows="4" class="large-text code">' . esc_textarea( $valeur ) . '</textarea>';
					break;
				case 'page':
					wp_dropdown_pages(
						array(
							'name'              => $cle,
							'id'                => $cle,
							'selected'          => (int) $valeur,
							'show_option_none'  => '—',
							'option_none_value' => '',
						)
					);
					break;
				default:
					echo '<input type="text" id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '" value="' . esc_attr( $valeur ) . '">';
			}
			if ( $aide ) {
				echo '<p class="description">' . esc_html( $aide ) . '</p>';
			}
			echo '</td></tr>';
		}
	},
	20
);

add_action(
	'edited_auteur',
	function ( $terme ) {
		if ( ! isset( $_POST['er_auteur_jeton'] ) || ! wp_verify_nonce( sanitize_key( $_POST['er_auteur_jeton'] ), 'er_auteur' ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		foreach ( er_auteur_champs() as $cle => list( , $genre ) ) {
			if ( ! isset( $_POST[ $cle ] ) ) {
				continue;
			}
			$valeur = wp_unslash( $_POST[ $cle ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			switch ( $genre ) {
				case 'image':
				case 'images':
				case 'page':
					$valeur = implode( ',', array_filter( array_map( 'absint', explode( ',', $valeur ) ) ) );
					break;
				case 'riche':
					$valeur = wp_kses_post( $valeur );
					break;
				case 'liens':
					$valeur = sanitize_textarea_field( $valeur );
					break;
				default:
					$valeur = sanitize_text_field( $valeur );
			}
			update_term_meta( $terme, $cle, $valeur );
		}
	}
);

// Le choix des images, par la médiathèque de WordPress.
add_action(
	'admin_enqueue_scripts',
	function ( $ecran ) {
		if ( 'term.php' !== $ecran || ! isset( $_GET['taxonomy'] ) || 'auteur' !== $_GET['taxonomy'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script(
			'media-editor',
			"jQuery( function ( $ ) {
				$( '.er-medias-choix' ).each( function () {
					var bloc = $( this ), champ = bloc.find( 'input' ), apercu = bloc.find( '.er-medias-apercu' ), multiple = !! bloc.data( 'multiple' );
					bloc.on( 'click', '.er-medias-choisir', function () {
						var cadre = wp.media( { multiple: multiple ? 'add' : false, library: { type: 'image' } } );
						cadre.on( 'open', function () {
							var choix = cadre.state().get( 'selection' );
							( champ.val() ? champ.val().split( ',' ) : [] ).forEach( function ( id ) { choix.add( wp.media.attachment( id ) ); } );
						} );
						cadre.on( 'select', function () {
							var ids = [], html = '';
							cadre.state().get( 'selection' ).each( function ( a ) {
								var t = a.toJSON(), url = t.sizes && t.sizes.thumbnail ? t.sizes.thumbnail.url : t.url;
								ids.push( t.id );
								html += '<img src=\"' + url + '\" style=\"width:80px;height:80px;object-fit:cover\" alt=\"\">';
							} );
							champ.val( ids.join( ',' ) );
							apercu.html( html );
						} );
						cadre.open();
					} );
					bloc.on( 'click', '.er-medias-oter', function () { champ.val( '' ); apercu.empty(); } );
				} );
			} );"
		);
	}
);

/* L'affichage ------------------------------------------------------------------------------------------------------ */

add_action(
	'init',
	function () {
		register_block_type(
			'er/auteur-fiche',
			array(
				'api_version'     => 3,
				'attributes'      => array(
					'partie' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'render_callback' => 'er_auteur_bloc_fiche',
			)
		);
	},
	8
);

/**
 * La fiche de l'auteur, en tête de sa page : le portrait, le nom, ses autres graphies, ses dates, sa présentation ;
 * puis sa biographie (le début, et la suite à déplier), ses photos, ses liens. En deux parties (« tete », « suite »)
 * quand la bande de page passe entre les deux (patterns/librairie.php, dans le thème) ; les deux par défaut.
 *
 * @param array $attributs partie : tete, suite, ou rien pour les deux.
 */
function er_auteur_bloc_fiche( $attributs = array() ) {
	$partie = isset( $attributs['partie'] ) ? (string) $attributs['partie'] : '';
	$t      = get_queried_object();
	if ( ! $t instanceof WP_Term || 'auteur' !== $t->taxonomy ) {
		return '';
	}
	$meta     = function ( $cle ) use ( $t ) {
		return (string) get_term_meta( $t->term_id, $cle, true );
	};
	$portrait = (int) $meta( 'er_portrait' );
	$graphies = er_autres_graphies( $t->term_id );
	$dates    = array_filter( array( $meta( 'er_naissance' ), $meta( 'er_deces' ) ) );

	$tete  = $portrait ? '<figure class="er-auteur-portrait">' . wp_get_attachment_image( $portrait, 'large', false, array( 'alt' => $t->name ) ) . '</figure>' : '';
	$tete .= '<div class="er-auteur-identite"><h1 class="er-auteur-nom">' . esc_html( $t->name ) . '</h1>';
	if ( $graphies ) {
		$tete .= '<p class="er-auteur-graphies">' . esc_html( implode( ' · ', $graphies ) ) . '</p>';
	}
	if ( $dates ) {
		$tete .= '<p class="er-auteur-dates">' . esc_html( implode( ' – ', $dates ) ) . '</p>';
	}
	if ( '' !== trim( $t->description ) ) {
		$tete .= '<div class="er-auteur-presentation">' . wpautop( wp_kses_post( $t->description ) ) . '</div>';
	}
	$tete .= '</div>';
	$tete  = '<div class="er-auteur-tete' . ( $portrait ? ' er-auteur-avec-portrait' : '' ) . '">' . $tete . '</div>';
	if ( 'tete' === $partie ) {
		return '<div class="er-auteur-fiche er-auteur-fiche-tete">' . $tete . '</div>';
	}
	$html = 'suite' === $partie ? '' : $tete;
	// La tête de page (le thème) montre le premier paragraphe de la présentation, en chapô : la suite vient ici.
	if ( 'suite' === $partie && '' !== trim( $t->description ) ) {
		$paragraphes = preg_split( '/\R\s*\R|(?<=<\/p>)/u', trim( $t->description ), 2 );
		if ( isset( $paragraphes[1] ) && '' !== trim( wp_strip_all_tags( $paragraphes[1] ) ) ) {
			$html .= '<div class="er-auteur-presentation">' . wpautop( wp_kses_post( $paragraphes[1] ) ) . '</div>';
		}
	}

	// La biographie : les deux premiers paragraphes, la suite à déplier.
	$bio = trim( $meta( 'er_biographie' ) );
	if ( '' !== $bio ) {
		$bio      = wpautop( do_shortcode( $bio ) );
		$morceaux = preg_split( '#(?<=</p>)#', $bio, 3 );
		$html    .= '<section class="er-auteur-bio" id="biographie"><h2 class="er-evt-section">' . esc_html__( 'Biographie', 'er-librairie' ) . '</h2>';
		if ( count( $morceaux ) > 2 && strlen( wp_strip_all_tags( $morceaux[2] ) ) > 200 ) {
			$html .= '<div class="er-auteur-bio-texte">' . $morceaux[0] . $morceaux[1] . '</div>'
				. '<details class="er-auteur-bio-suite"><summary>' . esc_html__( 'Lire toute la biographie', 'er-librairie' ) . '</summary><div class="er-auteur-bio-texte">' . $morceaux[2] . '</div></details>';
		} else {
			$html .= '<div class="er-auteur-bio-texte">' . $bio . '</div>';
		}
		$html .= '</section>';
	}

	$photos = array_filter( array_map( 'absint', explode( ',', $meta( 'er_photos' ) ) ) );
	if ( $photos ) {
		$html .= '<section class="er-auteur-photos" id="photos"><h2 class="er-evt-section">' . esc_html__( 'Photos', 'er-librairie' ) . '</h2><ul>';
		foreach ( $photos as $p ) {
			$legende = wp_get_attachment_caption( $p );
			$html   .= '<li><figure><a href="' . esc_url( (string) wp_get_attachment_image_url( $p, 'full' ) ) . '">' . wp_get_attachment_image( $p, 'medium_large', false, array( 'loading' => 'lazy' ) ) . '</a>'
				. ( $legende ? '<figcaption>' . esc_html( $legende ) . '</figcaption>' : '' ) . '</figure></li>';
		}
		$html .= '</ul></section>';
	}

	$liens = array();
	$page  = (int) $meta( 'er_page' );
	if ( $page && 'publish' === get_post_status( $page ) ) {
		$liens[] = '<li><a href="' . esc_url( get_permalink( function_exists( 'pll_get_post' ) ? ( pll_get_post( $page ) ?: $page ) : $page ) ) . '">' . esc_html__( 'L’article de l’encyclopédie', 'er-librairie' ) . '</a></li>';
	}
	foreach ( preg_split( '/\R/', $meta( 'er_liens' ) ) as $ligne ) {
		if ( false === strpos( $ligne, '|' ) ) {
			$ligne = trim( $ligne ) ? trim( $ligne ) . ' | ' . trim( $ligne ) : '';
		}
		list( $titre, $url ) = array_map( 'trim', array_pad( explode( '|', $ligne, 2 ), 2, '' ) );
		if ( preg_match( '#^https?://#', $url ) ) {
			$liens[] = '<li><a href="' . esc_url( $url ) . '" rel="noopener">' . esc_html( $titre ) . '</a></li>';
		}
	}
	if ( $liens ) {
		$html .= '<section class="er-auteur-liens"><h2 class="er-evt-section">' . esc_html__( 'Pour aller plus loin', 'er-librairie' ) . '</h2><ul class="er-lien-fleche">' . implode( '', $liens ) . '</ul></section>';
	}
	if ( '' === $html ) {
		return '';
	}
	return '<div class="er-auteur-fiche' . ( 'suite' === $partie ? ' er-auteur-fiche-suite' : '' ) . '">' . $html . '</div>';
}

/**
 * L'auteur à qui une page de l'encyclopédie est liée, s'il y en a un.
 *
 * @param int $page La page.
 */
function er_auteur_de_la_page( $page ) {
	$termes = get_terms(
		array(
			'taxonomy'   => 'auteur',
			'hide_empty' => false,
			'number'     => 1,
			'meta_query' => array( array( 'key' => 'er_page', 'value' => (string) $page ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $termes && ! is_wp_error( $termes ) ? $termes[0] : null;
}

// La page de l'encyclopédie mène à celle de l'auteur : ses livres, les rencontres, les vidéos, les articles.
add_filter(
	'the_content',
	function ( $contenu ) {
		if ( ! is_page() || ! in_the_loop() || ! is_main_query() ) {
			return $contenu;
		}
		$id = get_the_ID();
		if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) ) {
			$id = pll_get_post( $id, pll_default_language() ) ?: $id;
		}
		$auteur = er_auteur_de_la_page( $id );
		if ( ! $auteur ) {
			return $contenu;
		}
		$lien = get_term_link( $auteur );
		/* translators: %s: le nom de l'auteur */
		return $contenu . '<p class="er-lien-fleche er-page-vers-auteur"><a href="' . esc_url( is_wp_error( $lien ) ? '' : $lien ) . '">' . esc_html( sprintf( __( 'Tout %s : ses livres, les rencontres, les vidéos, les articles', 'er-librairie' ), $auteur->name ) ) . '</a></p>';
	},
	20
);

/*
 * Une personne fusionnée dans une autre (wp er personnes fusionner) laisse son identifiant dans er_anciens : son
 * ancienne adresse (/auteur/a-solzhenitsyn/) mène, pour toujours, à la page gardée.
 */
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;
		}
		$chemin = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! preg_match( '#(?:^|/)auteur/([^/]+)$#', $chemin, $m ) ) {
			return;
		}
		$personne = function_exists( 'er_personne_trouver' ) ? er_personne_trouver( rawurldecode( $m[1] ) ) : null;
		$lien     = $personne ? get_term_link( $personne ) : '';
		if ( $lien && ! is_wp_error( $lien ) ) {
			wp_safe_redirect( $lien, 301 );
			exit;
		}
	}
);
