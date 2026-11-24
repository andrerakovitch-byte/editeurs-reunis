<?php
/**
 * Les expositions du centre (er_exposition) : leurs dates, leurs artistes, leurs techniques, leurs œuvres (chacune
 * avec son cartel, la légende de la médiathèque), et ce qu'on y rattache : des livres, des articles du journal, des
 * vidéos et des podcasts, des rencontres (le vernissage, la soirée de clôture). Le lien se voit des deux côtés : la
 * fiche de l'exposition montre ce qui s'y rattache, et chaque livre, article, rencontre ou vidéo montre l'exposition.
 *
 * Les rattachements sont des lignes er_rattache de l'exposition (une par contenu) : un seul endroit les garde, qu'on
 * les pose depuis l'exposition ou depuis l'autre contenu (la boîte « Expositions »).
 *
 * La page « Nos expositions » porte le bloc er/expositions ; la fiche, les blocs er/expo-*. Les dates sont dans les
 * mêmes champs que celles des rencontres (er_debut, er_fin), si bien que er_evt_quand() les dit aussi.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

const ER_EXPO = 'er_exposition';

/**
 * Les contenus qu'on peut rattacher à une exposition, et leur nom.
 */
function er_expo_types() {
	return array(
		'product'    => __( 'Livre', 'er-librairie' ),
		'post'       => __( 'Article', 'er-librairie' ),
		'er_media'   => __( 'Vidéo ou podcast', 'er-librairie' ),
		'er_evenement' => __( 'Rencontre', 'er-librairie' ),
		'page'       => __( 'Page', 'er-librairie' ),
	);
}

add_action(
	'init',
	function () {
		register_post_type(
			ER_EXPO,
			array(
				'labels'        => array(
					'name'               => __( 'Expositions', 'er-librairie' ),
					'singular_name'      => __( 'Exposition', 'er-librairie' ),
					'add_new'            => __( 'Ajouter', 'er-librairie' ),
					'add_new_item'       => __( 'Ajouter une exposition', 'er-librairie' ),
					'edit_item'          => __( 'Modifier l’exposition', 'er-librairie' ),
					'view_item'          => __( 'Voir l’exposition', 'er-librairie' ),
					'all_items'          => __( 'Toutes les expositions', 'er-librairie' ),
					'search_items'       => __( 'Chercher une exposition', 'er-librairie' ),
					'not_found'          => __( 'Aucune exposition', 'er-librairie' ),
					'not_found_in_trash' => __( 'Aucune exposition dans la corbeille', 'er-librairie' ),
					'menu_name'          => __( 'Expositions', 'er-librairie' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-format-gallery',
				'menu_position' => 27,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'has_archive'   => false, // La liste est la page « Nos expositions » (le bloc er/expositions).
				'rewrite'       => array(
					'slug'       => 'centre-culturel/expositions',
					'with_front' => false,
				),
			)
		);
		register_taxonomy(
			'er_technique',
			ER_EXPO,
			array(
				'labels'            => array(
					'name'          => __( 'Techniques', 'er-librairie' ),
					'singular_name' => __( 'Technique', 'er-librairie' ),
					'all_items'     => __( 'Toutes les techniques', 'er-librairie' ),
					'edit_item'     => __( 'Modifier la technique', 'er-librairie' ),
					'add_new_item'  => __( 'Ajouter une technique', 'er-librairie' ),
					'not_found'     => __( 'Aucune technique', 'er-librairie' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
			)
		);
		foreach ( array( 'er_debut', 'er_fin', 'er_lieu', 'er_entree', 'er_artistes', 'er_oeuvres', 'er_expo_cle' ) as $cle ) {
			register_post_meta(
				ER_EXPO,
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
		foreach (
			array(
				'er/expositions'  => 'er_expo_bloc_liste',
				'er/expo-entete'  => 'er_expo_bloc_entete',
				'er/expo-corps'   => 'er_expo_bloc_corps',
				'er/expo-oeuvres' => 'er_expo_bloc_oeuvres',
				'er/expo-autres'  => 'er_expo_bloc_autres',
				'er/expo-liee'    => 'er_expo_bloc_liee',
			) as $nom => $rendu
		) {
			register_block_type(
				$nom,
				array(
					'api_version'     => 3,
					'uses_context'    => array( 'postId' ),
					'render_callback' => function ( $attributs, $contenu, $bloc ) use ( $rendu ) {
						return call_user_func( $rendu, isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID() );
					},
				)
			);
		}
	}
);

/* Les rattachements ------------------------------------------------------------------------------------------------ */

/**
 * Ce qui est rattaché à une exposition : les identifiants, dans l'ordre où on les a posés.
 *
 * @param int $expo L'exposition.
 */
function er_expo_rattaches( $expo ) {
	return array_values( array_unique( array_filter( array_map( 'intval', (array) get_post_meta( $expo, 'er_rattache', false ) ) ) ) );
}

/**
 * Rattache un contenu à une exposition (sans doublon).
 *
 * @param int $expo L'exposition.
 * @param int $id   Le contenu.
 */
function er_expo_rattacher( $expo, $id ) {
	if ( $id && $id !== $expo && ! in_array( (int) $id, er_expo_rattaches( $expo ), true ) ) {
		add_post_meta( $expo, 'er_rattache', (int) $id );
	}
}

/**
 * Les expositions auxquelles un contenu est rattaché.
 *
 * @param int $id Le contenu.
 */
function er_expos_de( $id ) {
	return get_posts(
		array(
			'post_type'   => ER_EXPO,
			'post_status' => 'publish',
			'numberposts' => 10,
			'meta_key'    => 'er_rattache', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => (string) (int) $id, // phpcs:ignore WordPress.DB.SlowDBQuery
			'lang'        => '',
		)
	);
}

/**
 * Où en est une exposition : en_cours, a_venir ou passee (sans date de fin, elle est passée un mois après son début).
 *
 * @param int $id L'exposition.
 */
function er_expo_etat( $id ) {
	$debut = function_exists( 'er_evt_date' ) ? er_evt_date( $id ) : null;
	$fin   = function_exists( 'er_evt_date' ) ? er_evt_date( $id, 'er_fin' ) : null;
	$now   = new DateTimeImmutable( 'now', function_exists( 'er_evt_fuseau' ) ? er_evt_fuseau() : wp_timezone() );
	if ( $debut && $debut > $now ) {
		return 'a_venir';
	}
	$fin = $fin ? $fin->setTime( 23, 59 ) : ( $debut ? $debut->modify( '+1 month' ) : null );
	return $fin && $fin >= $now ? 'en_cours' : 'passee';
}

/**
 * Les dates d'une exposition, à la française : « Du 2 juin au 28 septembre 2021 » ; sans date de fin, « À partir du
 * 10 janvier 2027 », ou, passée, « Inaugurée le 9 novembre 2023 ».
 *
 * @param int $id L'exposition.
 */
function er_expo_quand( $id ) {
	$debut = function_exists( 'er_evt_date' ) ? er_evt_date( $id ) : null;
	$fin   = function_exists( 'er_evt_date' ) ? er_evt_date( $id, 'er_fin' ) : null;
	if ( ! $debut ) {
		return '';
	}
	$tz = function_exists( 'er_evt_fuseau' ) ? er_evt_fuseau() : wp_timezone();
	if ( $fin ) {
		$meme = $fin->format( 'Y' ) === $debut->format( 'Y' );
		/* translators: 1: le premier jour, 2: le dernier */
		return sprintf( __( 'Du %1$s au %2$s', 'er-librairie' ), wp_date( $meme ? __( 'j F', 'er-librairie' ) : __( 'j F Y', 'er-librairie' ), $debut->getTimestamp(), $tz ), wp_date( __( 'j F Y', 'er-librairie' ), $fin->getTimestamp(), $tz ) );
	}
	$jour = wp_date( __( 'j F Y', 'er-librairie' ), $debut->getTimestamp(), $tz );
	/* translators: %s: le premier jour */
	return 'passee' === er_expo_etat( $id ) ? sprintf( __( 'Inaugurée le %s', 'er-librairie' ), $jour ) : sprintf( __( 'À partir du %s', 'er-librairie' ), $jour );
}

/**
 * La ligne des artistes : le champ er_artistes s'il est rempli, sinon les auteurs de l'exposition.
 *
 * @param int $id L'exposition.
 */
function er_expo_artistes( $id ) {
	$ligne = trim( (string) get_post_meta( $id, 'er_artistes', true ) );
	if ( $ligne ) {
		return $ligne;
	}
	$noms = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
	return is_wp_error( $noms ) ? '' : implode( ', ', $noms );
}

/* La saisie -------------------------------------------------------------------------------------------------------- */

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'er_expo', __( 'L’exposition', 'er-librairie' ), 'er_expo_boite', ER_EXPO, 'normal', 'high' );
		add_meta_box( 'er_expo_liens', __( 'Ce qui s’y rattache', 'er-librairie' ), 'er_expo_boite_liens', ER_EXPO, 'normal', 'default' );
		foreach ( array_keys( er_expo_types() ) as $type ) {
			add_meta_box( 'er_expo_de', __( 'Expositions', 'er-librairie' ), 'er_expo_boite_autre', $type, 'side', 'default' );
		}
	}
);

/**
 * La boîte de l'exposition : ses dates, son lieu, son entrée, ses artistes, ses œuvres.
 *
 * @param WP_Post $post L'exposition.
 */
function er_expo_boite( $post ) {
	wp_nonce_field( 'er_expo', 'er_expo_jeton' );
	$v      = fn( $cle ) => (string) get_post_meta( $post->ID, $cle, true );
	$ligne  = function ( $cle, $libelle, $type, $aide = '' ) use ( $v ) {
		$valeur = $v( $cle );
		if ( 'date' === $type ) {
			$valeur = substr( $valeur, 0, 10 );
		}
		echo '<p><label for="' . esc_attr( $cle ) . '"><strong>' . esc_html( $libelle ) . '</strong></label><br><input type="' . esc_attr( $type ) . '" id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '" value="' . esc_attr( $valeur ) . '" class="' . ( 'date' === $type ? '' : 'large-text' ) . '">'
			. ( $aide ? '<br><span class="description">' . esc_html( $aide ) . '</span>' : '' ) . '</p>';
	};
	echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0 24px">';
	$ligne( 'er_debut', __( 'Du', 'er-librairie' ), 'date' );
	$ligne( 'er_fin', __( 'Au', 'er-librairie' ), 'date', __( 'Vide si on ne le sait pas.', 'er-librairie' ) );
	echo '</div>';
	$ligne( 'er_artistes', __( 'La ligne des artistes', 'er-librairie' ), 'text', __( 'Vide : les auteurs de l’exposition (boîte « Auteurs »). Sinon, par exemple « D’après leur correspondance ».', 'er-librairie' ) );
	$ligne( 'er_lieu', __( 'Lieu', 'er-librairie' ), 'text', __( 'Vide : le centre, 11, rue de la Montagne-Sainte-Geneviève.', 'er-librairie' ) );
	$ligne( 'er_entree', _x( 'Entrée', 'l’accès à une exposition', 'er-librairie' ), 'text', __( 'Par exemple « Libre, aux heures de la librairie ».', 'er-librairie' ) );
	$ids = array_filter( array_map( 'absint', explode( ',', $v( 'er_oeuvres' ) ) ) );
	echo '<p><strong>' . esc_html__( 'Les œuvres', 'er-librairie' ) . '</strong><br><span class="description">' . esc_html__( 'Leur cartel est leur légende dans la médiathèque (le titre, l’artiste, la date, la technique).', 'er-librairie' ) . '</span></p>'
		. '<div class="er-medias-choix" data-multiple="1"><input type="hidden" name="er_oeuvres" value="' . esc_attr( implode( ',', $ids ) ) . '"><div class="er-medias-apercu" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px">';
	foreach ( $ids as $i ) {
		echo wp_get_attachment_image( $i, 'thumbnail', false, array( 'style' => 'width:80px;height:80px;object-fit:cover' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div><button type="button" class="button er-medias-choisir">' . esc_html__( 'Choisir les œuvres', 'er-librairie' ) . '</button> <button type="button" class="button-link er-medias-oter">' . esc_html__( 'Ôter', 'er-librairie' ) . '</button></div>';
}

/**
 * La boîte des rattachements : chercher un livre, un article, une vidéo, une rencontre ; les ôter.
 *
 * @param WP_Post $post L'exposition.
 */
function er_expo_boite_liens( $post ) {
	$types = er_expo_types();
	$ids   = er_expo_rattaches( $post->ID );
	echo '<div class="er-rattaches"><input type="hidden" name="er_rattaches" value="' . esc_attr( implode( ',', $ids ) ) . '"><ul class="er-rattaches-liste" style="margin:0 0 12px">';
	foreach ( $ids as $id ) {
		$type = get_post_type( $id );
		if ( ! $type ) {
			continue;
		}
		echo '<li data-id="' . (int) $id . '" style="display:flex;gap:8px;align-items:baseline;padding:6px 0;border-bottom:1px solid #eee"><span style="color:#666;min-width:110px">' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : $type ) . '</span><a href="' . esc_url( (string) get_edit_post_link( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a> <button type="button" class="button-link er-rattache-oter" style="margin-left:auto;color:#b32d2e">' . esc_html__( 'Ôter', 'er-librairie' ) . '</button></li>';
	}
	echo '</ul><input type="search" class="er-rattache-cherche large-text" placeholder="' . esc_attr__( 'Chercher un livre, un article, une vidéo, une rencontre…', 'er-librairie' ) . '"><ul class="er-rattache-resultats" style="margin:6px 0 0"></ul></div>';
}

/**
 * Sur un livre, un article, une rencontre, une vidéo : les expositions auxquelles il est rattaché.
 *
 * @param WP_Post $post Le contenu.
 */
function er_expo_boite_autre( $post ) {
	$expos = get_posts( array( 'post_type' => ER_EXPO, 'post_status' => array( 'publish', 'draft', 'future' ), 'numberposts' => 100, 'orderby' => 'meta_value', 'meta_key' => 'er_debut', 'order' => 'DESC', 'lang' => '' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( ! $expos ) {
		echo '<p class="description">' . esc_html__( 'Aucune exposition pour l’instant.', 'er-librairie' ) . '</p>';
		return;
	}
	wp_nonce_field( 'er_expo_de', 'er_expo_de_jeton' );
	echo '<input type="hidden" name="er_expo_de_present" value="1"><div style="max-height:220px;overflow:auto">';
	foreach ( $expos as $e ) {
		$coche = in_array( (int) $post->ID, er_expo_rattaches( $e->ID ), true );
		echo '<label style="display:block;margin:4px 0"><input type="checkbox" name="er_expo_de[]" value="' . (int) $e->ID . '"' . checked( $coche, true, false ) . '> ' . esc_html( get_the_title( $e ) ) . '</label>';
	}
	echo '</div>';
}

add_action(
	'save_post',
	function ( $id, $post ) {
		if ( wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		// L'exposition elle-même.
		if ( ER_EXPO === $post->post_type && isset( $_POST['er_expo_jeton'] ) && wp_verify_nonce( sanitize_key( $_POST['er_expo_jeton'] ), 'er_expo' ) ) {
			foreach ( array( 'er_debut', 'er_fin' ) as $cle ) {
				$d = isset( $_POST[ $cle ] ) ? sanitize_text_field( wp_unslash( $_POST[ $cle ] ) ) : '';
				update_post_meta( $id, $cle, preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ? $d : '' );
			}
			foreach ( array( 'er_artistes', 'er_lieu', 'er_entree' ) as $cle ) {
				update_post_meta( $id, $cle, isset( $_POST[ $cle ] ) ? sanitize_text_field( wp_unslash( $_POST[ $cle ] ) ) : '' );
			}
			update_post_meta( $id, 'er_oeuvres', isset( $_POST['er_oeuvres'] ) ? implode( ',', array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['er_oeuvres'] ) ) ) ) ) ) : '' );
			if ( isset( $_POST['er_rattaches'] ) ) {
				delete_post_meta( $id, 'er_rattache' );
				foreach ( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['er_rattaches'] ) ) ) ) ) as $r ) {
					er_expo_rattacher( $id, $r );
				}
			}
			return;
		}
		// Un livre, un article… : les expositions cochées.
		if ( isset( $_POST['er_expo_de_present'], $_POST['er_expo_de_jeton'] ) && wp_verify_nonce( sanitize_key( $_POST['er_expo_de_jeton'] ), 'er_expo_de' ) ) {
			$cochees = isset( $_POST['er_expo_de'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['er_expo_de'] ) ) : array();
			foreach ( get_posts( array( 'post_type' => ER_EXPO, 'post_status' => 'any', 'numberposts' => 100, 'fields' => 'ids', 'lang' => '' ) ) as $expo ) {
				if ( in_array( $expo, $cochees, true ) ) {
					er_expo_rattacher( $expo, $id );
				} else {
					delete_post_meta( $expo, 'er_rattache', (string) $id );
				}
			}
		}
	},
	10,
	2
);

// Chercher de quoi rattacher (la boîte des rattachements).
add_action(
	'wp_ajax_er_expo_chercher',
	function () {
		check_ajax_referer( 'er_expo_chercher' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json( array() );
		}
		$q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$types  = er_expo_types();
		$sortie = array();
		foreach ( get_posts( array( 'post_type' => array_keys( $types ), 'post_status' => 'publish', 's' => $q, 'numberposts' => 20, 'lang' => '' ) ) as $p ) {
			$sortie[] = array(
				'id'    => $p->ID,
				'titre' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
				'type'  => $types[ $p->post_type ],
			);
		}
		wp_send_json( $sortie );
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $ecran ) {
		global $post;
		if ( ! in_array( $ecran, array( 'post.php', 'post-new.php' ), true ) || ! $post || ER_EXPO !== $post->post_type ) {
			return;
		}
		wp_enqueue_media();
		$jeton = wp_create_nonce( 'er_expo_chercher' );
		$oter  = esc_js( __( 'Ôter', 'er-librairie' ) );
		wp_add_inline_script(
			'media-editor',
			"jQuery( function ( $ ) {
				$( '.er-medias-choix' ).each( function () {
					var bloc = $( this ), champ = bloc.find( 'input' ), apercu = bloc.find( '.er-medias-apercu' );
					bloc.on( 'click', '.er-medias-choisir', function () {
						var cadre = wp.media( { multiple: 'add', library: { type: 'image' } } );
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
				var boite = $( '.er-rattaches' ), champ = boite.find( 'input[name=er_rattaches]' ), liste = boite.find( '.er-rattaches-liste' ), res = boite.find( '.er-rattache-resultats' ), minuteur;
				function majChamp() { champ.val( liste.children().map( function () { return $( this ).data( 'id' ); } ).get().join( ',' ) ); }
				liste.on( 'click', '.er-rattache-oter', function () { $( this ).closest( 'li' ).remove(); majChamp(); } );
				boite.find( '.er-rattache-cherche' ).on( 'input', function () {
					var q = this.value; clearTimeout( minuteur );
					if ( q.length < 2 ) { res.empty(); return; }
					minuteur = setTimeout( function () {
						$.getJSON( ajaxurl, { action: 'er_expo_chercher', _ajax_nonce: '$jeton', q: q }, function ( items ) {
							res.empty();
							items.forEach( function ( it ) {
								$( '<li style=\"padding:4px 0;cursor:pointer\">' ).text( it.type + ' · ' + it.titre ).on( 'click', function () {
									if ( ! liste.find( '[data-id=' + it.id + ']' ).length ) {
										var li = $( '<li style=\"display:flex;gap:8px;align-items:baseline;padding:6px 0;border-bottom:1px solid #eee\">' ).attr( 'data-id', it.id );
										li.append( $( '<span style=\"color:#666;min-width:110px\">' ).text( it.type ), $( '<span>' ).text( it.titre ), $( '<button type=\"button\" class=\"button-link er-rattache-oter\" style=\"margin-left:auto;color:#b32d2e\">' ).text( '$oter' ) );
										liste.append( li ); majChamp();
									}
									res.empty();
								} ).appendTo( res );
							} );
						} );
					}, 250 );
				} );
			} );"
		);
	}
);

/* L'affichage : les morceaux ---------------------------------------------------------------------------------------- */

/**
 * L'image d'une exposition (ou d'une œuvre), ou l'aplat d'attente.
 *
 * @param int    $attachement L'image (0 : aucune).
 * @param string $taille      La taille de WordPress.
 */
function er_expo_image( $attachement, $taille = 'large' ) {
	$image = $attachement ? wp_get_attachment_image( $attachement, $taille, false, array( 'alt' => '', 'loading' => 'lazy' ) ) : '';
	return '<span class="er-x-image">' . ( $image ? $image : '<span class="er-j-attente"></span>' ) . '</span>';
}

/**
 * Le surtitre d'une exposition : « 2021 · Peinture ».
 *
 * @param int $id L'exposition.
 */
function er_expo_techniques( $id ) {
	$t = wp_get_post_terms( $id, 'er_technique', array( 'fields' => 'names' ) );
	return is_wp_error( $t ) ? '' : implode( ', ', $t );
}

/**
 * Ce qui est rattaché, rangé : les rencontres, le journal, la librairie, écouter et regarder, les pages.
 *
 * @param int $id L'exposition.
 */
function er_expo_ranges( $id ) {
	$ranges = array(
		'er_evenement' => array(),
		'post'         => array(),
		'product'      => array(),
		'er_media'     => array(),
		'page'         => array(),
	);
	foreach ( er_expo_rattaches( $id ) as $r ) {
		$type = get_post_type( $r );
		if ( ! isset( $ranges[ $type ] ) || 'publish' !== get_post_status( $r ) ) {
			continue;
		}
		// Une rencontre qui dure des semaines, c'est l'exposition telle que l'ancien site l'annonçait : elle reste
		// rattachée (sa page montre l'exposition), mais on ne la redit pas ici.
		if ( 'er_evenement' === $type && function_exists( 'er_evt_dure' ) && er_evt_dure( $r ) ) {
			continue;
		}
		$ranges[ $type ][] = $r;
	}
	return $ranges;
}

/**
 * Un contenu rattaché, dans la marge de la fiche : où il est (« Dans le journal · Critique »), son titre, une ligne
 * (le chapô d'un article), ses faits (la date d'une rencontre, l'auteur et le prix d'un livre…).
 *
 * @param int $r Le contenu rattaché.
 */
function er_expo_rattache( $r ) {
	$type  = get_post_type( $r );
	$titre = esc_html( html_entity_decode( get_the_title( $r ), ENT_QUOTES, 'UTF-8' ) );
	$ligne = '';
	$faits = '';
	switch ( $type ) {
		case 'post':
			$genre = function_exists( 'er_journal_genre' ) ? er_journal_genre( $r ) : null;
			$sur   = __( 'Dans le journal', 'er-librairie' ) . ( $genre ? ' · ' . $genre->name : '' );
			$ligne = has_excerpt( $r ) ? wp_trim_words( get_the_excerpt( $r ), 24, '…' ) : '';
			$faits = function_exists( 'er_journal_auteur' ) ? preg_replace( '#^<p class="er-j-auteur">(.*)</p>$#s', '$1', er_journal_auteur( $r ) ) : esc_html( get_the_date( '', $r ) );
			break;
		case 'er_evenement':
			$sur   = __( 'Au centre', 'er-librairie' );
			$faits = function_exists( 'er_evt_quand' ) ? esc_html( er_evt_quand( $r ) ) : '';
			break;
		case 'product':
			$sur    = __( 'À la librairie', 'er-librairie' );
			$titre  = '<em>' . $titre . '</em>';
			$livre  = function_exists( 'wc_get_product' ) ? wc_get_product( $r ) : null;
			$noms   = wp_get_post_terms( $r, 'auteur', array( 'fields' => 'names' ) );
			$parts  = is_wp_error( $noms ) || ! $noms ? array() : array( esc_html( implode( ', ', $noms ) ) );
			if ( $livre ) {
				$parts[] = $livre->is_in_stock() && $livre->get_price() ? wp_strip_all_tags( wc_price( (float) $livre->get_price() ) ) : esc_html__( 'Épuisé pour l’instant', 'er-librairie' );
			}
			$faits = implode( ' · ', $parts );
			break;
		case 'er_media':
			$sur   = 'podcast' === get_post_meta( $r, 'er_genre', true ) ? __( 'Écouter', 'er-librairie' ) : __( 'Regarder', 'er-librairie' );
			$serie = wp_get_post_terms( $r, 'er_serie', array( 'fields' => 'names' ) );
			$faits = is_wp_error( $serie ) || ! $serie ? '' : esc_html( $serie[0] );
			break;
		default:
			$sur = __( 'À lire sur le site', 'er-librairie' );
	}
	return '<a class="er-x-rattache" href="' . esc_url( get_permalink( $r ) ) . '"><span class="er-j-sur">' . esc_html( $sur ) . '</span>'
		. '<span class="er-x-rattache-titre">' . $titre . '</span>'
		. ( $ligne ? '<span class="er-x-rattache-ligne">' . esc_html( $ligne ) . '</span>' : '' )
		. ( $faits ? '<span class="er-x-rattache-faits">' . $faits . '</span>' : '' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* La fiche d'une exposition ---------------------------------------------------------------------------------------- */

/**
 * L'en-tête de la fiche, 5 | 7 : l'image, puis le cartel (surtitre, titre, artistes, chapô, faits).
 *
 * @param int $id L'exposition.
 */
function er_expo_bloc_entete( $id ) {
	$etat   = er_expo_etat( $id );
	$debut  = function_exists( 'er_evt_date' ) ? er_evt_date( $id ) : null;
	$sur    = array();
	if ( 'en_cours' === $etat ) {
		$sur[] = __( 'En ce moment', 'er-librairie' );
	} elseif ( 'a_venir' === $etat ) {
		$sur[] = __( 'Prochainement', 'er-librairie' );
	}
	$page  = get_page_by_path( 'centre-culturel/expositions' );
	$lien  = $page ? get_permalink( function_exists( 'er_traduction' ) ? er_traduction( $page->ID ) : $page->ID ) : '';
	$debut_sur = $lien ? '<a href="' . esc_url( $lien ) . '">' . esc_html__( 'Nos expositions', 'er-librairie' ) . '</a>' : esc_html__( 'Nos expositions', 'er-librairie' );
	foreach ( array_filter( array( er_expo_techniques( $id ), $debut ? $debut->format( 'Y' ) : '' ) ) as $s ) {
		$sur[] = $s;
	}
	$faits  = array();
	// Les rencontres de l'exposition, une ligne par sorte : « Inauguration : samedi 3 octobre 2020, 13 h, 15 h et
	// 17 h » quand il y en a eu plusieurs le même jour (des créneaux, en 2020).
	$sortes = array();
	foreach ( er_expo_ranges( $id )['er_evenement'] as $r ) {
		$sortes[ er_expo_nom_rencontre( $r, true ) ][] = $r;
	}
	// Quand : les dates ; sans date de fin, le vernissage ou l'inauguration le disent déjà (« Inaugurée le… »).
	$quand = er_expo_quand( $id );
	if ( $quand && ( get_post_meta( $id, 'er_fin', true ) || ! $sortes || 'passee' !== $etat ) ) {
		$faits[] = array( __( 'Quand', 'er-librairie' ), $quand );
	}
	foreach ( $sortes as $nom => $rs ) {
		if ( ! function_exists( 'er_evt_quand' ) ) {
			break;
		}
		$dates = array_filter( array_map( 'er_evt_date', $rs ) );
		$jours = array_unique( array_map( fn( $d ) => $d->format( 'Y-m-d' ), $dates ) );
		if ( count( $rs ) > 1 && 1 === count( $jours ) && count( $dates ) === count( $rs ) ) {
			$heures  = array_map( 'er_evt_heure', $dates );
			$dernier = array_pop( $heures );
			/* translators: 1: des heures, séparées par des virgules, 2: la dernière heure */
			$faits[] = array( $nom, wp_date( __( 'l j F Y', 'er-librairie' ), reset( $dates )->getTimestamp(), er_evt_fuseau() ) . ', ' . sprintf( __( '%1$s et %2$s', 'er-librairie' ), implode( ', ', $heures ), $dernier ) );
		} else {
			$faits[] = array( $nom, implode( ' ; ', array_map( 'er_evt_quand', $rs ) ) );
		}
	}
	$lieu    = trim( (string) get_post_meta( $id, 'er_lieu', true ) );
	$faits[] = array( __( 'Où', 'er-librairie' ), $lieu ? $lieu : __( '11, rue de la Montagne-Sainte-Geneviève, Paris 5e', 'er-librairie' ) );
	$entree  = trim( (string) get_post_meta( $id, 'er_entree', true ) );
	if ( $entree ) {
		$faits[] = array( _x( 'Entrée', 'l’accès à une exposition', 'er-librairie' ), $entree );
	}
	$oeuvres = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $id, 'er_oeuvres', true ) ) ) );
	if ( $oeuvres ) {
		/* translators: %d: un nombre d'œuvres */
		$faits[] = array( __( 'Les œuvres', 'er-librairie' ), sprintf( _n( '%d œuvre', '%d œuvres', count( $oeuvres ), 'er-librairie' ), count( $oeuvres ) ) );
	}
	$dl = '';
	foreach ( $faits as list( $nom, $valeur ) ) {
		$dl .= '<div class="er-x-fait"><dt>' . esc_html( $nom ) . '</dt><dd>' . esc_html( $valeur ) . '</dd></div>';
	}
	$artistes = er_expo_artistes( $id );
	return '<section class="er-x-entete alignfull"><div class="er-j-g">'
		. er_expo_image( (int) get_post_thumbnail_id( $id ), 'large' )
		. '<div class="er-x-cartel">'
		. '<p class="er-j-sur">' . $debut_sur . ( $sur ? ' · ' . esc_html( implode( ' · ', $sur ) ) : '' ) . '</p>'
		. '<h1 class="er-x-titre">' . esc_html( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) . '</h1>'
		. ( $artistes ? '<p class="er-x-artistes">' . esc_html( $artistes ) . '</p>' : '' )
		. ( has_excerpt( $id ) ? '<p class="er-x-chapo">' . esc_html( get_the_excerpt( $id ) ) . '</p>' : '' )
		. '<dl class="er-x-faits">' . $dl . '</dl>'
		. '</div></div></section>';
}

/**
 * Le texte et sa marge, 7 | 4 : ce qui s'y rattache, le journal d'abord, puis le centre, la librairie, les vidéos et
 * les podcasts, les pages.
 *
 * @param int $id L'exposition.
 */
function er_expo_bloc_corps( $id ) {
	$texte  = apply_filters( 'the_content', get_post_field( 'post_content', $id ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	$ranges = er_expo_ranges( $id );
	$marge  = '';
	foreach ( array( 'post', 'er_evenement', 'product', 'er_media', 'page' ) as $type ) {
		$marge .= implode( '', array_map( 'er_expo_rattache', $ranges[ $type ] ) );
	}
	$texte = ( trim( wp_strip_all_tags( $texte ) ) ? $texte : '' ) . ( function_exists( 'er_bloc_etiquettes' ) ? er_bloc_etiquettes( $id ) : '' );
	if ( ! $texte && ! $marge ) {
		return '';
	}
	return '<section class="er-x-corps alignfull"><div class="er-j-g">' . ( $texte ? '<div class="er-x-texte">' . $texte . '</div>' : '' ) . ( $marge ? '<aside class="er-x-marge" aria-label="' . esc_attr__( 'Autour de l’exposition', 'er-librairie' ) . '">' . $marge . '</aside>' : '' ) . '</div></section>';
}

/**
 * L'accrochage : les œuvres, chacune avec son cartel (sa légende dans la médiathèque).
 *
 * @param int $id L'exposition.
 */
function er_expo_bloc_oeuvres( $id ) {
	$oeuvres = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $id, 'er_oeuvres', true ) ) ) );
	if ( ! $oeuvres ) {
		return '';
	}
	$html = '';
	foreach ( $oeuvres as $o ) {
		$cartel = wp_get_attachment_caption( $o );
		$grande = wp_get_attachment_image_url( $o, 'full' );
		$html  .= '<li><a href="' . esc_url( $grande ) . '">' . er_expo_image( $o, 'medium_large' ) . ( $cartel ? '<span class="er-x-cartel-oeuvre">' . esc_html( $cartel ) . '</span>' : '' ) . '</a></li>';
	}
	/* translators: %s: un nombre d'œuvres, en lettres (« huit ») */
	$titre = 1 === count( $oeuvres ) ? __( 'L’œuvre', 'er-librairie' ) : sprintf( __( 'Les %s œuvres', 'er-librairie' ), er_expo_en_lettres( count( $oeuvres ) ) );
	return '<section class="er-x-oeuvres alignfull"><div class="er-j-g"><div class="er-j-titre-sec er-j-plein"><h2>' . esc_html( $titre ) . '</h2></div><ul class="er-j-plein">' . $html . '</ul></div></section>';
}

/**
 * Les autres expositions, en tiers.
 *
 * @param int $id L'exposition.
 */
function er_expo_bloc_autres( $id ) {
	$autres = get_posts( array( 'post_type' => ER_EXPO, 'post_status' => 'publish', 'numberposts' => 3, 'post__not_in' => array( $id ), 'meta_key' => 'er_debut', 'orderby' => 'meta_value', 'order' => 'DESC' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( ! $autres ) {
		return '';
	}
	$html = '';
	foreach ( $autres as $a ) {
		$html .= er_expo_carte( $a->ID );
	}
	return '<section class="er-x-autres er-j-doux alignfull"><div class="er-j-g"><div class="er-j-titre-sec er-j-plein"><h2>' . esc_html__( 'Les autres expositions', 'er-librairie' ) . '</h2></div><ul class="er-j-plein">' . $html . '</ul></div></section>';
}

/**
 * Une exposition en carte : l'image, l'année et la technique, le titre, les artistes.
 *
 * @param int $id L'exposition.
 */
function er_expo_carte( $id ) {
	$debut = function_exists( 'er_evt_date' ) ? er_evt_date( $id ) : null;
	$sur   = implode( ' · ', array_filter( array( $debut ? $debut->format( 'Y' ) : '', er_expo_techniques( $id ) ) ) );
	return '<li><a class="er-x-carte" href="' . esc_url( get_permalink( $id ) ) . '">' . er_expo_image( (int) get_post_thumbnail_id( $id ), 'medium_large' )
		. ( $sur ? '<span class="er-j-sur">' . esc_html( $sur ) . '</span>' : '' )
		. '<span class="er-x-carte-titre">' . esc_html( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) . '</span>'
		. '<span class="er-x-carte-artistes">' . esc_html( er_expo_artistes( $id ) ) . '</span></a></li>';
}

/**
 * Sur un livre, un article, une rencontre, une vidéo : l'exposition à laquelle il est rattaché.
 *
 * @param int $id Le contenu.
 */
function er_expo_bloc_liee( $id ) {
	if ( ! $id || ER_EXPO === get_post_type( $id ) ) {
		return '';
	}
	$expos = er_expos_de( $id );
	if ( ! $expos ) {
		return '';
	}
	$html = '';
	foreach ( $expos as $e ) {
		$html .= '<li><a href="' . esc_url( get_permalink( $e ) ) . '">' . er_expo_image( (int) get_post_thumbnail_id( $e ), 'thumbnail' ) . '<span><span class="er-j-sur">' . esc_html( implode( ' · ', array_filter( array( __( 'Exposition', 'er-librairie' ), er_expo_quand( $e->ID ) ) ) ) ) . '</span><span class="er-x-carte-titre">' . esc_html( html_entity_decode( get_the_title( $e ), ENT_QUOTES, 'UTF-8' ) ) . '</span><span class="er-x-carte-artistes">' . esc_html( er_expo_artistes( $e->ID ) ) . '</span></span></a></li>';
	}
	return '<section class="er-x-liee"><h2 class="er-evt-section">' . esc_html( _n( 'L’exposition', 'Les expositions', count( $expos ), 'er-librairie' ) ) . '</h2><ul>' . $html . '</ul></section>';
}

// Sur la fiche d'un livre : l'exposition, dans la bande « Autour de ce livre », en tête de ses rencontres et de ses
// articles (la fiche des livres est à la boutique, inc/recommandations.php : on n'y touche pas, on ajoute).
add_filter(
	'render_block_er/fiche-autour',
	function ( $html, $bloc, $instance = null ) {
		$id    = $instance && isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : (int) get_queried_object_id();
		$expos = $id ? er_expos_de( $id ) : array();
		if ( ! $expos ) {
			return $html;
		}
		$lis = '';
		foreach ( $expos as $e ) {
			$suite = 'passee' === er_expo_etat( $e->ID ) ? __( 'Revoir l’exposition', 'er-librairie' ) : __( 'Voir l’exposition', 'er-librairie' );
			$lis  .= '<li class="er-reco-rencontre er-reco-exposition"><a href="' . esc_url( get_permalink( $e ) ) . '"><span class="er-reco-genre"><span class="er-reco-point" aria-hidden="true"></span>' . esc_html__( 'Exposition au centre', 'er-librairie' ) . '</span>'
				. '<span class="er-reco-contenu-titre">' . esc_html( html_entity_decode( get_the_title( $e ), ENT_QUOTES, 'UTF-8' ) ) . '</span><span class="er-reco-meta">' . esc_html( er_expo_quand( $e->ID ) ) . ' · ' . esc_html( $suite ) . ' →</span></a></li>';
		}
		if ( false !== strpos( $html, '<ul class="er-reco-contenus">' ) ) {
			return str_replace( '<ul class="er-reco-contenus">', '<ul class="er-reco-contenus">' . $lis, $html );
		}
		if ( false !== strpos( $html, '</div></div></section>' ) ) {
			$i = strrpos( $html, '</div></div></section>' );
			return substr( $html, 0, $i ) . '<ul class="er-reco-contenus">' . $lis . '</ul>' . substr( $html, $i );
		}
		// La bande n'avait rien à dire : elle ne porte que l'exposition.
		return '<section class="er-reco-autour alignfull" aria-labelledby="er-reco-autour-titre"><div class="er-reco-autour-g">'
			. '<div class="er-reco-autour-tete"><p class="er-petit-titre">' . esc_html__( 'Autour de ce livre', 'er-librairie' ) . '</p>'
			. '<h2 id="er-reco-autour-titre">' . esc_html( _n( 'L’exposition', 'Les expositions', count( $expos ), 'er-librairie' ) ) . '</h2></div>'
			. '<div class="er-reco-autour-corps er-reco-autour-corps--sans-livres"><ul class="er-reco-contenus">' . $lis . '</ul></div></div></section>';
	},
	10,
	3
);

/* La page « Nos expositions » ------------------------------------------------------------------------------------- */

/**
 * La page : la ligne de repères, ce qui est accroché (ou le sera), puis les expositions passées, une par une, comme
 * des cartels. Filtrée par année (?annee=2023) ou par technique (?technique=peinture).
 */
function er_expo_bloc_liste() {
	$toutes = get_posts( array( 'post_type' => ER_EXPO, 'post_status' => 'publish', 'numberposts' => 200, 'meta_key' => 'er_debut', 'orderby' => 'meta_value', 'order' => 'DESC' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	$annee  = isset( $_GET['annee'] ) ? (int) $_GET['annee'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$tech   = isset( $_GET['technique'] ) ? sanitize_title( wp_unslash( $_GET['technique'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$ici    = get_permalink();

	// La ligne de repères : en ce moment, les années, les techniques.
	$en_cours = array_values( array_filter( $toutes, fn( $e ) => 'en_cours' === er_expo_etat( $e->ID ) ) );
	$a_venir  = array_values( array_filter( $toutes, fn( $e ) => 'a_venir' === er_expo_etat( $e->ID ) ) );
	$annees   = array();
	$techs    = array();
	foreach ( $toutes as $e ) {
		$d = function_exists( 'er_evt_date' ) ? er_evt_date( $e->ID ) : null;
		if ( $d ) {
			$annees[ $d->format( 'Y' ) ] = isset( $annees[ $d->format( 'Y' ) ] ) ? $annees[ $d->format( 'Y' ) ] + 1 : 1;
		}
		foreach ( wp_get_post_terms( $e->ID, 'er_technique' ) as $t ) {
			$techs[ $t->slug ] = $t->name;
		}
	}
	krsort( $annees );
	// La bande de la page (la bande de page du thème) : en ce moment, à venir ; les expositions passées, toutes, par
	// année (les quatre dernières dépliées), par technique (repliées) ; à droite, les horaires.
	$entrees = array();
	if ( $en_cours ) {
		$entrees[] = array(
			'texte' => __( 'En ce moment', 'er-librairie' ),
			'lien'  => '#en-ce-moment',
		);
	}
	if ( $a_venir ) {
		$entrees[] = array(
			'texte' => __( 'À venir', 'er-librairie' ),
			'lien'  => '#a-venir',
		);
	}
	if ( $annees ) {
		if ( $entrees ) {
			$entrees[] = array( 'separateur' => true );
		}
		$entrees[] = array( 'groupe' => __( 'Passées', 'er-librairie' ) );
		$entrees[] = array(
			'texte'   => __( 'Toutes', 'er-librairie' ),
			'lien'    => $ici . '#passees',
			'courant' => ! $annee && ! $tech,
		);
		$entrees = array_merge( $entrees, ! function_exists( 'er_bande_annees' ) ? array() : er_bande_annees( array_keys( $annees ), fn( $an ) => add_query_arg( 'annee', $an, $ici ) . '#passees', $annee ? (string) $annee : '', __( 'Plus anciennes', 'er-librairie' ) ) );
	}
	if ( $techs ) {
		$entrees[] = array(
			'menu'    => __( 'Les techniques', 'er-librairie' ),
			'entrees' => array_map(
				fn( $slug, $nom ) => array(
					'texte'   => $nom,
					'lien'    => add_query_arg( 'technique', $slug, $ici ) . '#passees',
					'courant' => $slug === $tech,
				),
				array_keys( $techs ),
				$techs
			),
		);
	}
	$trouver = get_page_by_path( 'nous-trouver' );
	// La tête de page (er_tete_page(), dans le thème) : le titre, sa phrase (le résumé de la page, sinon ce qu'on
	// accroche), et en face du fil, combien d'expositions depuis quand.
	$page   = get_post();
	$phrase = $page && 'page' === $page->post_type ? trim( $page->post_excerpt ) : '';
	if ( '' === $phrase ) {
		$phrase = __( 'Peintures, gravures, images et archives, accrochées au 11, rue de la Montagne-Sainte-Geneviève.', 'er-librairie' );
	}
	$premiere = $annees ? (int) min( array_keys( $annees ) ) : 0;
	/* translators: 1: un nombre d'expositions, 2: une année */
	$faits = count( $toutes ) > 1 && $premiere ? sprintf( __( '%1$d expositions depuis %2$d', 'er-librairie' ), count( $toutes ), $premiere ) : '';
	$titre = $page ? html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ) : __( 'Nos expositions', 'er-librairie' );
	$html  = '<div class="er-x-liste alignfull">'
		. ( function_exists( 'er_tete_page' )
			? er_tete_page(
				array(
					'maison' => 'centre',
					'faits'  => $faits,
					'titre'  => $titre,
					'chapo'  => $phrase,
				)
			)
			: '<h1>' . esc_html( $titre ) . '</h1>' ) // Sans le thème : le titre seul.
		. ( function_exists( 'er_bande_page' )
			? er_bande_page(
				array(
					'maison'  => 'centre',
					'nom'     => $page ? html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ) : __( 'Nos expositions', 'er-librairie' ),
					'entrees' => $entrees,
					'action'  => $trouver ? array( 'texte' => __( 'Les horaires →', 'er-librairie' ), 'lien' => get_permalink( $trouver ) ) : null,
					'libelle' => __( 'Les expositions du centre', 'er-librairie' ),
				)
			)
			: '' );

	// Ce qui est accroché, et ce qui le sera : sur l'aplat du centre.
	foreach ( array( 'en-ce-moment' => $en_cours, 'a-venir' => $a_venir ) as $ancre => $liste ) {
		foreach ( $liste as $i => $e ) {
			$html .= ( 0 === $i ? '<div id="' . $ancre . '" class="er-x-ancre"></div>' : '' ) . er_expo_affiche( $e->ID );
		}
	}

	// Les expositions passées, filtrées.
	$passees = array_filter(
		$toutes,
		function ( $e ) use ( $annee, $tech ) {
			if ( 'passee' !== er_expo_etat( $e->ID ) ) {
				return false;
			}
			$d = function_exists( 'er_evt_date' ) ? er_evt_date( $e->ID ) : null;
			if ( $annee && ( ! $d || (int) $d->format( 'Y' ) !== $annee ) ) {
				return false;
			}
			return ! $tech || has_term( $tech, 'er_technique', $e );
		}
	);
	if ( $passees ) {
		$html .= '<section class="er-x-passees" id="passees"><div class="er-j-g"><div class="er-j-titre-sec er-j-plein"><h2>' . esc_html__( 'Les expositions passées', 'er-librairie' ) . '</h2><span>' . esc_html__( 'De la plus récente à la plus ancienne', 'er-librairie' ) . '</span></div>';
		foreach ( $passees as $e ) {
			$html .= er_expo_cartel( $e->ID );
		}
		$html .= '</div></section>';
	} elseif ( ! $en_cours && ! $a_venir ) {
		$html .= '<div class="er-j-g"><p class="er-j-plein er-x-rien">' . esc_html__( 'Rien pour l’instant.', 'er-librairie' ) . '</p></div>';
	}
	return $html . er_expo_entrees() . '</div>';
}

/**
 * Pour finir la page, trois entrées sur le fond doux : la prochaine rencontre, le journal, venir au centre.
 */
function er_expo_entrees() {
	if ( ! function_exists( 'er_lien' ) ) {
		return '';
	}
	$evt = __( 'Les rencontres, les lectures et les vernissages du centre.', 'er-librairie' );
	if ( function_exists( 'er_evt_requete' ) ) {
		foreach ( er_evt_requete( true ) as $e ) {
			if ( 'annule' === get_post_meta( $e->ID, 'er_etat', true ) || er_evt_dure( $e->ID ) ) {
				continue;
			}
			$quand = er_evt_quand( $e->ID );
			$evt   = ( $quand ? mb_strtoupper( mb_substr( $quand, 0, 1 ) ) . mb_substr( $quand, 1 ) . ' : ' : '' ) . html_entity_decode( get_the_title( $e ), ENT_QUOTES, 'UTF-8' ) . '.';
			break;
		}
	}
	$entrees = array(
		array( __( 'Au centre', 'er-librairie' ), __( 'Nos événements', 'er-librairie' ), $evt, 'centre-culturel/evenements/' ),
		array( __( 'À lire', 'er-librairie' ), __( 'Le journal du centre', 'er-librairie' ), __( 'Chroniques, critiques, essais et archives.', 'er-librairie' ), 'journal/' ),
		array( __( 'Venir', 'er-librairie' ), __( 'Nous trouver', 'er-librairie' ), __( '11, rue de la Montagne-Sainte-Geneviève, Paris 5e.', 'er-librairie' ), 'nous-trouver/' ),
	);
	$html = '';
	foreach ( $entrees as list( $sur, $titre, $texte, $chemin ) ) {
		$html .= '<a class="er-x-entree" href="' . er_lien( $chemin ) . '"><span class="er-j-sur">' . esc_html( $sur ) . '</span><span class="er-x-entree-titre">' . esc_html( $titre ) . '</span><span class="er-x-entree-texte">' . esc_html( $texte ) . '</span></a>';
	}
	return '<section class="er-x-entrees er-j-doux"><div class="er-j-g">' . $html . '</div></section>';
}

/**
 * Un petit nombre en lettres (« huit »), au-delà en chiffres.
 *
 * @param int  $n         Le nombre.
 * @param bool $majuscule Avec une capitale, en début de phrase.
 */
function er_expo_en_lettres( $n, $majuscule = false ) {
	$mots = array( '', __( 'une', 'er-librairie' ), __( 'deux', 'er-librairie' ), __( 'trois', 'er-librairie' ), __( 'quatre', 'er-librairie' ), __( 'cinq', 'er-librairie' ), __( 'six', 'er-librairie' ), __( 'sept', 'er-librairie' ), __( 'huit', 'er-librairie' ), __( 'neuf', 'er-librairie' ), __( 'dix', 'er-librairie' ), __( 'onze', 'er-librairie' ), __( 'douze', 'er-librairie' ) );
	$mot  = isset( $mots[ $n ] ) && $mots[ $n ] ? $mots[ $n ] : (string) $n;
	return $majuscule ? mb_strtoupper( mb_substr( $mot, 0, 1 ) ) . mb_substr( $mot, 1 ) : $mot;
}

/**
 * Le nom d'une rencontre de l'exposition : dans une pastille, « Le vernissage », « L'inauguration », « La soirée de
 * clôture », sinon son titre, raccourci ; dans les faits de la fiche, « Vernissage », « Clôture », sinon « Rencontre ».
 *
 * @param int  $r     La rencontre.
 * @param bool $court Le nom court, sans article (les faits de la fiche).
 */
function er_expo_nom_rencontre( $r, $court = false ) {
	$titre = html_entity_decode( get_the_title( $r ), ENT_QUOTES, 'UTF-8' );
	foreach (
		array(
			'/vernissage/iu'               => array( __( 'Le vernissage', 'er-librairie' ), __( 'Vernissage', 'er-librairie' ) ),
			'/cl[ôo]ture/iu'               => array( __( 'La soirée de clôture', 'er-librairie' ), __( 'Clôture', 'er-librairie' ) ),
			'/rencontre d.inauguration/iu' => array( __( 'La rencontre d’inauguration', 'er-librairie' ), __( 'Inauguration', 'er-librairie' ) ),
			'/inaugur/iu'                  => array( __( 'L’inauguration', 'er-librairie' ), __( 'Inauguration', 'er-librairie' ) ),
			'/finissage/iu'                => array( __( 'Le finissage', 'er-librairie' ), __( 'Finissage', 'er-librairie' ) ),
		) as $motif => $noms
	) {
		if ( preg_match( $motif, $titre ) ) {
			return $noms[ $court ? 1 : 0 ];
		}
	}
	return $court ? __( 'Rencontre', 'er-librairie' ) : wp_trim_words( $titre, 6, '…' );
}

/**
 * Une exposition passée, en cartel : l'image (5 colonnes), puis l'année, la technique, le titre, les artistes, les
 * dates, deux lignes, et ce qui s'y rattache en pastilles.
 *
 * @param int $id L'exposition.
 */
function er_expo_cartel( $id ) {
	$debut  = function_exists( 'er_evt_date' ) ? er_evt_date( $id ) : null;
	$lien   = get_permalink( $id );
	$texte  = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 32, '…' );
	$pastilles = '';
	$vues      = array();
	$ranges    = er_expo_ranges( $id );
	foreach ( array_merge( $ranges['er_evenement'], $ranges['post'], $ranges['product'], $ranges['er_media'] ) as $r ) {
		$type   = get_post_type( $r );
		$prefix = array(
			'er_evenement' => '',
			'post'         => __( 'Dans le journal : ', 'er-librairie' ),
			'product'      => __( 'À la librairie : ', 'er-librairie' ),
			'er_media'     => '',
		);
		$titre      = 'er_evenement' === $type ? er_expo_nom_rencontre( $r ) : wp_trim_words( html_entity_decode( get_the_title( $r ), ENT_QUOTES, 'UTF-8' ), 7, '…' );
		if ( isset( $vues[ $prefix[ $type ] . $titre ] ) ) {
			continue; // Trois créneaux d'inauguration : une seule pastille.
		}
		$vues[ $prefix[ $type ] . $titre ] = true;
		$pastilles .= '<a class="er-x-pastille" href="' . esc_url( get_permalink( $r ) ) . '">' . esc_html( $prefix[ $type ] . $titre ) . '</a>';
	}
	return '<article class="er-x-cartel-ligne er-j-plein">'
		. '<a class="er-x-cartel-image" href="' . esc_url( $lien ) . '">' . er_expo_image( (int) get_post_thumbnail_id( $id ), 'large' ) . '</a>'
		. '<div class="er-x-cartel-texte">'
		. '<p class="er-x-annee">' . ( $debut ? '<span>' . esc_html( $debut->format( 'Y' ) ) . '</span>' : '' ) . '<span class="er-j-sur">' . esc_html( er_expo_techniques( $id ) ) . '</span></p>'
		. '<h3 class="er-x-cartel-titre"><a href="' . esc_url( $lien ) . '">' . esc_html( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) . '</a></h3>'
		. '<p class="er-x-artistes">' . esc_html( er_expo_artistes( $id ) ) . '</p>'
		. '<p class="er-x-dates">' . esc_html( er_expo_quand( $id ) ) . '</p>'
		. ( $texte ? '<p class="er-x-cartel-chapo">' . esc_html( $texte ) . '</p>' : '' )
		. ( $pastilles ? '<p class="er-x-pastilles">' . $pastilles . '</p>' : '' )
		. '</div></article>';
}

/**
 * Une exposition accrochée (ou annoncée) : l'aplat du centre, 5 | 7, « Выставка » en décor.
 *
 * @param int $id L'exposition.
 */
function er_expo_affiche( $id ) {
	$etat  = er_expo_etat( $id );
	$fin   = function_exists( 'er_evt_date' ) ? er_evt_date( $id, 'er_fin' ) : null;
	$debut = function_exists( 'er_evt_date' ) ? er_evt_date( $id ) : null;
	$tz    = function_exists( 'er_evt_fuseau' ) ? er_evt_fuseau() : wp_timezone();
	if ( 'en_cours' === $etat ) {
		/* translators: %s: le dernier jour */
		$sur = $fin ? sprintf( __( 'En ce moment · jusqu’au %s', 'er-librairie' ), wp_date( __( 'j F', 'er-librairie' ), $fin->getTimestamp(), $tz ) ) : __( 'En ce moment', 'er-librairie' );
	} else {
		/* translators: %s: le premier jour */
		$sur = $debut ? sprintf( __( 'Prochainement · à partir du %s', 'er-librairie' ), wp_date( __( 'j F', 'er-librairie' ), $debut->getTimestamp(), $tz ) ) : __( 'Prochainement', 'er-librairie' );
	}
	$entree = trim( (string) get_post_meta( $id, 'er_entree', true ) );
	$lieu   = trim( (string) get_post_meta( $id, 'er_lieu', true ) );
	return '<section class="er-x-affiche alignfull"><span class="er-x-lettre" lang="ru" aria-hidden="true">Выставка</span><div class="er-j-g">'
		. '<div class="er-x-affiche-texte">'
		. '<p class="er-j-faits"><span class="er-j-point" aria-hidden="true"></span>' . esc_html( $sur ) . '</p>'
		. '<h2 class="er-x-affiche-titre"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) . '</a></h2>'
		. '<p class="er-x-artistes">' . esc_html( er_expo_artistes( $id ) ) . '</p>'
		. ( has_excerpt( $id ) ? '<p class="er-x-chapo">' . esc_html( get_the_excerpt( $id ) ) . '</p>' : '' )
		. '<dl class="er-x-faits"><div class="er-x-fait"><dt>' . esc_html__( 'Quand', 'er-librairie' ) . '</dt><dd>' . esc_html( er_expo_quand( $id ) ) . '</dd></div>'
		. '<div class="er-x-fait"><dt>' . esc_html__( 'Où', 'er-librairie' ) . '</dt><dd>' . esc_html( $lieu ? $lieu : __( '11, rue de la Montagne-Sainte-Geneviève, Paris 5e', 'er-librairie' ) ) . '</dd></div>'
		. ( $entree ? '<div class="er-x-fait"><dt>' . esc_html_x( 'Entrée', 'l’accès à une exposition', 'er-librairie' ) . '</dt><dd>' . esc_html( $entree ) . '</dd></div>' : '' ) . '</dl>'
		. '<p class="er-x-boutons"><a class="er-x-bouton er-x-bouton-plein" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'Voir l’exposition', 'er-librairie' ) . '</a></p>'
		. '</div>' . er_expo_image( (int) get_post_thumbnail_id( $id ), 'large' ) . '</div></section>';
}
