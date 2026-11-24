<?php
/**
 * Les étiquettes de tout le site : les personnes (le classement des auteurs) et les sujets.
 *
 * Une personne n'a qu'une étiquette, son nom d'auteur, et une seule page, /auteur/<slug>/ ; un sujet n'est jamais une
 * personne (un sujet qui porte le nom d'un auteur est refusé : on choisit l'auteur).
 *
 * - Les auteurs : sur un livre, celui qui l'a écrit ; sur un article, une rencontre, une vidéo ou un podcast, une page,
 *   les personnes qui y sont liées (on en parle, elles l'ont écrit ou y interviennent). Un livre qui parle d'une
 *   personne la porte dans « Autour de » (er_autour, un classement caché, tenu avec les mêmes noms que les auteurs :
 *   on y choisit un auteur, rien d'autre). Le compte d'un auteur reste son nombre de livres.
 * - Les sujets (er_sujet) : l'émigration, la poésie, le Goulag…, sur les livres, les articles, les rencontres, les
 *   vidéos et les podcasts, les pages ; avec leur page, /sujet/<slug>/.
 *
 * Ils relient le site : la page de Soljenitsyne donne ses livres, les livres autour de lui, les rencontres, les vidéos
 * et les podcasts, les articles du journal et les pages qui le concernent ; la page d'un sujet, tout ce qui le porte ;
 * un article, une rencontre, une vidéo, ses étiquettes et ce qui lui est proche (« À lire, à revoir »). Les étiquettes
 * WordPress des articles (post_tag) laissent la place aux sujets.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_taxonomy(
			'er_sujet',
			er_types_etiquetes(),
			array(
				'labels'            => array(
					'name'                       => __( 'Sujets', 'er-librairie' ),
					'singular_name'              => __( 'Sujet', 'er-librairie' ),
					'search_items'               => __( 'Chercher un sujet', 'er-librairie' ),
					'all_items'                  => __( 'Tous les sujets', 'er-librairie' ),
					'edit_item'                  => __( 'Modifier le sujet', 'er-librairie' ),
					'add_new_item'               => __( 'Ajouter un sujet', 'er-librairie' ),
					'separate_items_with_commas' => __( 'Séparer les sujets par des virgules', 'er-librairie' ),
					'choose_from_most_used'      => __( 'Choisir parmi les plus utilisés', 'er-librairie' ),
					'not_found'                  => __( 'Aucun sujet', 'er-librairie' ),
					'menu_name'                  => __( 'Sujets', 'er-librairie' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => false,
				'rewrite'           => array( 'slug' => 'sujet' ),
			)
		);
		foreach ( er_types_etiquetes() as $type ) {
			if ( 'product' !== $type ) {
				register_taxonomy_for_object_type( 'auteur', $type );
			}
		}
		unregister_taxonomy_for_object_type( 'post_tag', 'post' );
		register_taxonomy(
			'er_autour',
			array( 'product' ),
			array(
				'labels'            => array(
					'name'          => __( 'Autour de', 'er-librairie' ),
					'singular_name' => __( 'Autour de', 'er-librairie' ),
				),
				'public'            => false,
				'show_ui'           => false,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => false,
			)
		);
	},
	7
);

/**
 * Les contenus qui portent des étiquettes : les livres, les articles, les rencontres, les vidéos et les podcasts, les
 * pages, les expositions.
 */
function er_types_etiquetes() {
	return array( 'product', 'post', ER_EVT, 'er_media', 'page', 'er_exposition' );
}

// Un sujet n'est jamais une personne : celui qui porte le nom d'un auteur est refusé.
add_filter(
	'pre_insert_term',
	function ( $terme, $taxonomie ) {
		if ( 'er_sujet' !== $taxonomie || ! is_string( $terme ) || '' === trim( $terme ) ) {
			return $terme;
		}
		$cle = er_cle_recherche( $terme );
		foreach ( er_evt_auteurs_connus() as $nom => list( $t ) ) {
			if ( $nom === $cle ) {
				/* translators: %s: le nom de l'auteur */
				return new WP_Error( 'er_sujet_auteur', sprintf( __( '%s est un auteur : choisissez-le dans « Auteurs » plutôt qu’en sujet.', 'er-librairie' ), $t->name ) );
			}
		}
		return $terme;
	},
	10,
	2
);

/**
 * Les personnes dont parle un livre (« Autour de »), par leurs termes d'auteur.
 *
 * @param int $id Le livre.
 * @return WP_Term[]
 */
function er_autour_de( $id ) {
	$autour = array();
	foreach ( wp_get_post_terms( $id, 'er_autour' ) as $miroir ) {
		$auteur = get_term_by( 'slug', $miroir->slug, 'auteur' );
		if ( $auteur ) {
			$autour[] = $auteur;
		}
	}
	return $autour;
}

/**
 * Pose « Autour de » sur un livre : des auteurs (par leur identifiant ou leur nom, un nom inconnu devient un auteur).
 *
 * @param int   $id      Le livre.
 * @param array $auteurs Les auteurs.
 */
function er_poser_autour_de( $id, $auteurs ) {
	$slugs = array();
	foreach ( $auteurs as $a ) {
		$t = is_numeric( $a ) ? get_term( (int) $a, 'auteur' ) : er_personne_par_nom( (string) $a, true );
		if ( $t && ! is_wp_error( $t ) ) {
			if ( ! term_exists( $t->slug, 'er_autour' ) ) {
				wp_insert_term( $t->name, 'er_autour', array( 'slug' => $t->slug ) );
			}
			$slugs[] = $t->slug;
		}
	}
	wp_set_object_terms( $id, $slugs, 'er_autour' );
}

// La fiche d'un livre, dans l'administration : « Autour de », les personnes dont il parle (des auteurs).
add_action(
	'add_meta_boxes_product',
	function () {
		add_meta_box(
			'er-autour',
			__( 'Autour de', 'er-librairie' ),
			function ( $post ) {
				wp_nonce_field( 'er_autour', 'er_autour_jeton' );
				$noms = implode( ', ', wp_list_pluck( er_autour_de( $post->ID ), 'name' ) );
				echo '<p><input type="text" class="widefat" name="er_autour" list="er-auteurs" value="' . esc_attr( $noms ) . '"></p>';
				echo '<p class="description">' . esc_html__( 'Les personnes dont parle le livre, séparées par des virgules : des auteurs (un nom nouveau devient un auteur). Le livre paraît sur leur page, dans « Autour de ».', 'er-librairie' ) . '</p>';
				echo '<datalist id="er-auteurs">';
				foreach ( get_terms( array( 'taxonomy' => 'auteur', 'hide_empty' => false, 'fields' => 'names', 'number' => 3000 ) ) as $nom ) {
					echo '<option value="' . esc_attr( $nom ) . '">';
				}
				echo '</datalist>';
			},
			'product',
			'side'
		);
	}
);
add_action(
	'save_post_product',
	function ( $id ) {
		if ( ! isset( $_POST['er_autour_jeton'], $_POST['er_autour'] ) || ! wp_verify_nonce( sanitize_key( $_POST['er_autour_jeton'] ), 'er_autour' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		er_poser_autour_de( $id, array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['er_autour'] ) ) ) ) ) );
	}
);

/**
 * Le compte d'un auteur : ses livres seulement, pas les articles ni les rencontres qui le concernent.
 *
 * @param array       $termes    Les term_taxonomy_id.
 * @param WP_Taxonomy $taxonomie Le classement.
 */
function er_compter_livres( $termes, $taxonomie ) {
	global $wpdb;
	foreach ( (array) $termes as $tt ) {
		$n = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr JOIN {$wpdb->posts} p ON p.ID = tr.object_id
				WHERE tr.term_taxonomy_id = %d AND p.post_type = 'product' AND p.post_status = 'publish'",
				$tt
			)
		);
		do_action( 'edit_term_taxonomy', $tt, $taxonomie->name );
		$wpdb->update( $wpdb->term_taxonomy, array( 'count' => $n ), array( 'term_taxonomy_id' => $tt ) ); // phpcs:ignore WordPress.DB
		do_action( 'edited_term_taxonomy', $tt, $taxonomie->name );
	}
}

// La page d'un auteur reste celle de ses livres : les articles et les rencontres viennent dessous (er/autour).
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_tax( 'auteur' ) ) {
			$q->set( 'post_type', 'product' );
		}
	},
	5
);

/* Les sujets devinés --------------------------------------------------------------------------------------------
 *
 * Pour l'import (et `wp er sujets deviner`) : des sujets de départ, reconnus par des mots du titre et du chapô, en
 * français et en russe. Une liste à reprendre à la main (Articles → Sujets) : on renomme, on fusionne, on ajoute ;
 * deviner n'ôte jamais un sujet posé à la main.
 */

/**
 * Les sujets de départ : nom → motif.
 */
function er_sujets_de_depart() {
	return array(
		'Poésie'              => '/po[ée]sie|po[èe]te|po[ée]tesse|po[ée]tique|поэ[тз]|стих/iu',
		'Émigration'          => '/[ée]migr|exil|диаспор|эмигра|изгнан/iu',
		'Goulag et dissidence' => '/goulag|gulag|гулаг|dissiden|диссидент|r[ée]pression|репресс|archipel du|архипелаг|samizdat|самиздат|terreur|kolyma|колым/iu',
		'Orthodoxie'          => '/(?i:orthodox|[ée]glise|th[ée]olog|liturg|православ|христиан|церк|богослов|ic[ôo]ne|икон|святы)|\b[Pp][èe]re [A-ZÀ-Ý]|[Оо]т(?:ец|цом) [А-Я]/u',
		'Histoire'            => '/histoire|historien|истори|r[ée]volution|революц|guerre|войн|m[ée]moire|память/iu',
		'Musique'             => '/musique|musical|musicien|compositeur|op[ée]ra|concert|chostakovitch|tcha[ïi]kovski|rachmaninov|moussorgski|stravinsk|prokofiev|glinka|музык|композитор|опер[аы]|шостакович|чайковск|рахманинов|\bchants?\b/iu',
		'Cinéma'              => '/cin[ée]ma|\bfilms?\b|r[ée]alisateur|кино|фильм|режисс/iu',
		'Arts'                => '/exposition|peint|gravure|\bart\b|artiste|sculpt|photograph|выставк|художник|живопис|график|фотограф/iu',
		'Traduction'          => '/traduction|traduct|traduire|перевод/iu',
		'Philosophie'         => '/philosoph|философ|pens[ée]e russe/iu',
		'Ukraine'             => '/ukrain|украин/iu',
		'Théâtre'             => '/th[ée][âa]tre|lecture[- ]spectacle|театр|спектакл|пьес/iu',
		// Les époques, comme les rayons de la librairie : d'après les écrivains nommés.
		'Classiques russes'   => '/pouchkine|lermontov|gogol|tourgu[ée]niev|dosto[ïi]evski|tolsto[ïi]|tchekhov|gontcharov|leskov|tiouttchev|пушкин|лермонтов|гогол|тургенев|достоевск|толсто|чехов/iu',
		'Âge d’argent'        => '/\bblok|akhmatova|mandelstam|tsv[ée]ta[ïi]eva|goumilev|bi[ée]ly|ess[ée]nine|ma[ïi]akovski|khlebnikov|volochine|kouzmine|блок|ахматов|мандельштам|цветаев|гумил[её]в|есенин|маяковск/iu',
		'Période soviétique'  => '/boulgakov|platonov|pasternak|chalamov|grossman|zamiatine|kharms|brodsk|dovlatov|булгаков|платонов|пастернак|шаламов|гроссман|бродск|довлатов/iu',
		'Russie d’aujourd’hui' => '/poutine|russie d.aujourd.hui|путин/iu',
		// La maison elle-même, pas un livre qu'elle publie : son histoire, son siècle, Nikita Struve.
		'Histoire de la maison' => '/nikita struve|никит\w* струве|(histoire|centenaire|anniversaire)\s+d(e|u|es|\W)\s*(la maison|l.ymca|ymca|[ée]diteurs r[ée]unis)|(ymca[- ]press|[ée]diteurs r[ée]unis|имка)[^.]{0,50}(un si[èe]cle|centenaire|anniversaire|histoire|столет|юбиле|истори)/iu',
	);
}

/**
 * Les sujets qu'un texte évoque.
 *
 * @param string $texte Le titre et le chapô.
 */
function er_sujets_deviner( $texte ) {
	$sujets = array();
	foreach ( er_sujets_de_depart() as $nom => $motif ) {
		if ( preg_match( $motif, $texte ) ) {
			$sujets[] = $nom;
		}
	}
	return $sujets;
}

/**
 * Les auteurs de la librairie qu'un texte nomme, et s'ils y interviennent (« avec Georges Nivat », « par Hélène
 * Henry ») ou si l'on en parle (« le Docteur Jivago de Boris Pasternak »). Le nom du centre ne compte pas.
 *
 * @param string $texte   Le titre et le chapô.
 * @param array  $auteurs er_evt_auteurs_connus().
 * @return array [ [ le terme, ses noms, intervient ] ].
 */
function er_auteurs_cites( $texte, $auteurs ) {
	$cle    = ' ' . er_cle_recherche( $texte ) . ' ';
	$cle    = str_replace( ' ' . er_cle_recherche( 'Centre culturel Alexandre Soljenitsyne' ) . ' ', ' ', $cle );
	$mots   = array_values( array_filter( explode( ' ', $cle ) ) );
	$cites  = array();
	foreach ( $auteurs as $nom => list( $t, $noms ) ) {
		if ( isset( $cites[ $t->term_id ] ) ) {
			continue;
		}
		$position = er_nom_dans( explode( ' ', $nom ), $mots );
		if ( false === $position ) {
			continue;
		}
		$avant                = array_slice( $mots, max( 0, $position - 3 ), min( 3, $position ) );
		$cites[ $t->term_id ] = array( $t, $noms, (bool) array_intersect( $avant, array( 'avec', 'par', 'presenc', 'recevons', 'invit', 'invite', 's' ) ) );
	}
	return array_values( $cites );
}

/**
 * Où un nom (ses mots) paraît dans un texte (ses mots), mots qui se suivent ; un mot peut changer de fin, comme en
 * russe (« с Марией Степановой » pour Мария Степанова) : les deux dernières lettres d'un mot de cinq lettres et plus
 * peuvent varier.
 *
 * @param array $nom   Les mots du nom.
 * @param array $mots  Les mots du texte.
 * @return int|false La position du premier mot.
 */
function er_nom_dans( $nom, $mots ) {
	$n = count( $nom );
	for ( $i = 0; $i + $n <= count( $mots ); $i++ ) {
		$ok = true;
		foreach ( $nom as $k => $m ) {
			$t = $mots[ $i + $k ];
			if ( $t === $m ) {
				continue;
			}
			$racine = strlen( $m ) >= 5 ? substr( $m, 0, strlen( $m ) - 2 ) : null;
			if ( null === $racine || 0 !== strpos( $t, $racine ) || abs( strlen( $t ) - strlen( $m ) ) > 3 ) {
				$ok = false;
				break;
			}
		}
		if ( $ok ) {
			return $i;
		}
	}
	return false;
}

/**
 * Pose sur un article ou une rencontre les sujets et les auteurs devinés (sans rien ôter).
 *
 * @param int    $id      Le contenu.
 * @param string $texte   Le titre et le chapô.
 * @param array  $auteurs er_evt_auteurs_connus().
 * @return array Les sujets et les auteurs posés.
 */
function er_etiqueter( $id, $texte, $auteurs ) {
	if ( get_post_meta( $id, 'er_etiquettes_relues', true ) ) {
		return array(); // Des étiquettes posées ou relues à la main : on ne devine rien de plus.
	}
	$sujets = er_sujets_deviner( $texte );
	if ( $sujets ) {
		wp_set_object_terms( $id, $sujets, 'er_sujet', true );
	}
	$poses = array();
	foreach ( er_auteurs_cites( $texte, $auteurs ) as list( $t, , $intervient ) ) {
		if ( $intervient && ER_EVT === get_post_type( $id ) ) {
			continue; // Un intervenant a sa ligne « Avec » (er_intervenant), posée par l'import des rencontres.
		}
		wp_set_object_terms( $id, $t->term_id, 'auteur', true );
		$poses[] = $t->name;
	}
	return array_merge( $sujets, $poses );
}

/* L'affichage ------------------------------------------------------------------------------------------------------
 *
 * Trois blocs dynamiques : er/etiquettes (les auteurs et les sujets d'un article ou d'une rencontre, en pilules),
 * er/proches (« À lire, à revoir » : ce qui partage le plus d'étiquettes) et er/autour (sur la page d'un auteur ou
 * d'un sujet : ses rencontres et ses articles).
 */

add_action(
	'init',
	function () {
		$blocs = array(
			'er/etiquettes' => 'er_bloc_etiquettes',
			'er/proches'    => 'er_bloc_proches',
			'er/autour'     => 'er_bloc_autour',
		);
		foreach ( $blocs as $nom => $rendu ) {
			register_block_type(
				$nom,
				array(
					'api_version'     => 3,
					'uses_context'    => array( 'postId' ),
					'render_callback' => function ( $attributs, $contenu, $bloc ) use ( $rendu ) {
						$id = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : (int) get_the_ID();
						return call_user_func( $rendu, $id );
					},
				)
			);
		}
	},
	8
);

/**
 * Les auteurs et les sujets d'un article ou d'une rencontre, en pilules qui mènent à leur page.
 *
 * @param int $id Le contenu.
 */
function er_bloc_etiquettes( $id ) {
	$lignes = array(
		'auteur'   => __( 'Auteurs', 'er-librairie' ),
		'er_sujet' => __( 'Sujets', 'er-librairie' ),
	);
	$html   = '';
	foreach ( $lignes as $taxonomie => $libelle ) {
		$termes = wp_get_post_terms( $id, $taxonomie );
		if ( ! $termes || is_wp_error( $termes ) ) {
			continue;
		}
		$pilules = '';
		foreach ( $termes as $t ) {
			$lien     = get_term_link( $t );
			$pilules .= '<li><a href="' . esc_url( is_wp_error( $lien ) ? '' : $lien ) . '">' . esc_html( $t->name ) . '</a></li>';
		}
		$html .= '<div class="er-etiquettes-ligne"><span class="er-etiquettes-libelle">' . esc_html( $libelle ) . '</span><ul class="er-pilules">' . $pilules . '</ul></div>';
	}
	return $html ? '<div class="er-etiquettes">' . $html . '</div>' : '';
}

/**
 * Un article ou une rencontre, en carte : l'image, le genre et la date, le titre.
 *
 * @param WP_Post $p Le contenu.
 */
function er_carte_contenu( $p ) {
	if ( ER_EVT === $p->post_type ) {
		$genre = __( 'Rencontre', 'er-librairie' );
		$date  = er_evt_quand( $p->ID, false );
	} elseif ( 'er_media' === $p->post_type ) {
		$genre = 'podcast' === get_post_meta( $p->ID, 'er_genre', true ) ? __( 'Podcast', 'er-librairie' ) : __( 'Vidéo', 'er-librairie' );
		$date  = get_the_date( __( 'j F Y', 'er-librairie' ), $p );
	} elseif ( 'er_exposition' === $p->post_type ) {
		$genre = __( 'Exposition', 'er-librairie' );
		$date  = function_exists( 'er_expo_quand' ) ? er_expo_quand( $p->ID ) : '';
	} else {
		$genre = __( 'Journal', 'er-librairie' );
		$date  = get_the_date( __( 'j F Y', 'er-librairie' ), $p );
	}
	$image = get_the_post_thumbnail( $p, 'medium_large', array( 'alt' => '', 'loading' => 'lazy' ) );
	if ( ! $image && 'er_media' === $p->post_type && function_exists( 'er_media_vignette' ) ) {
		$image = er_media_vignette( $p->ID ); // L'image de la vidéo, chez YouTube.
	}
	$classes = array( ER_EVT => 'rencontre', 'er_media' => 'media', 'post' => 'article', 'er_exposition' => 'exposition' );
	return '<li class="er-carte-contenu er-carte-' . ( isset( $classes[ $p->post_type ] ) ? $classes[ $p->post_type ] : 'page' ) . '"><a href="' . esc_url( get_permalink( $p ) ) . '">'
		. '<span class="er-carte-image">' . $image . '</span>'
		. '<span class="er-carte-genre">' . esc_html( $genre ) . ' · ' . esc_html( $date ) . '</span>'
		. '<span class="er-carte-titre">' . esc_html( get_the_title( $p ) ) . '</span>'
		. ( ER_EVT === $p->post_type && er_evt_videos( $p->ID ) ? '<span class="er-evt-marques"><span class="er-evt-marque er-evt-marque-video">' . esc_html__( 'Vidéo', 'er-librairie' ) . '</span></span>' : '' )
		. '</a></li>';
}

/**
 * « À lire, à revoir » : jusqu'à quatre articles ou rencontres qui partagent le plus d'auteurs et de sujets.
 *
 * @param int $id Le contenu.
 */
function er_bloc_proches( $id ) {
	$requete = array( 'relation' => 'OR' );
	$miens   = array();
	foreach ( array( 'auteur', 'er_sujet', 'er_intervenant', 'er_cycle' ) as $taxonomie ) {
		$ids = wp_get_post_terms( $id, $taxonomie, array( 'fields' => 'ids' ) );
		if ( $ids && ! is_wp_error( $ids ) ) {
			$requete[]            = array( 'taxonomy' => $taxonomie, 'terms' => $ids );
			$miens[ $taxonomie ] = $ids;
		}
	}
	if ( count( $requete ) < 2 ) {
		return '';
	}
	$candidats = get_posts(
		array(
			'post_type'      => array( 'post', ER_EVT, 'er_media' ),
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'post__not_in'   => array( $id ),
			'tax_query'      => $requete, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	// Les plus proches d'abord : un auteur en commun compte plus qu'un sujet, un sujet plus qu'un cycle.
	$poids  = array(
		'auteur'         => 3,
		'er_intervenant' => 3,
		'er_sujet'       => 2,
		'er_cycle'       => 1,
	);
	$scores = array();
	foreach ( $candidats as $c ) {
		$s = 0;
		foreach ( $miens as $taxonomie => $ids ) {
			$leurs = wp_get_post_terms( $c->ID, $taxonomie, array( 'fields' => 'ids' ) );
			$s    += $poids[ $taxonomie ] * count( array_intersect( $ids, is_wp_error( $leurs ) ? array() : $leurs ) );
		}
		$scores[ $c->ID ] = $s * 100000000 + (int) get_post_timestamp( $c ) / 100;
	}
	arsort( $scores );
	$choisis = array_slice( array_keys( $scores ), 0, 4 );
	if ( ! $choisis ) {
		return '';
	}
	update_post_thumbnail_cache( new WP_Query( array( 'post__in' => $choisis, 'post_type' => array( 'post', ER_EVT, 'er_media' ), 'posts_per_page' => 4 ) ) );
	return '<section class="er-proches"><h2 class="er-evt-section">' . esc_html__( 'À lire, à revoir', 'er-librairie' ) . '</h2><ul class="er-cartes">'
		. implode( '', array_map( 'er_carte_contenu', array_map( 'get_post', $choisis ) ) ) . '</ul></section>';
}

/**
 * Un livre, en carte : la couverture, l'auteur, le titre, le prix.
 *
 * @param int $pid Le livre.
 */
function er_carte_livre( $pid ) {
	$p = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
	if ( ! $p || 'publish' !== $p->get_status() || $p->get_meta( 'er_evenement' ) ) {
		return '';
	}
	$auteurs = wp_get_post_terms( $pid, 'auteur', array( 'fields' => 'names' ) );
	$prix    = $p->is_in_stock() ? html_entity_decode( wp_strip_all_tags( wc_price( wc_get_price_to_display( $p ) ) ), ENT_QUOTES, 'UTF-8' ) : __( 'épuisé pour l’instant', 'er-librairie' );
	return '<li class="er-evt-livre"><a href="' . esc_url( get_permalink( $pid ) ) . '">'
		. '<span class="er-evt-livre-couverture">' . $p->get_image( 'woocommerce_thumbnail' ) . '</span>'
		. '<span class="er-evt-livre-auteur">' . esc_html( implode( ', ', is_wp_error( $auteurs ) ? array() : $auteurs ) ) . '</span>'
		. '<span class="er-evt-livre-titre">' . esc_html( $p->get_name() ) . '</span></a>'
		. '<span class="er-evt-livre-prix">' . esc_html( $prix ) . '</span></li>';
}

/**
 * Les contenus d'un type qui portent une étiquette, les plus récents d'abord.
 *
 * @param string|array $type  Le type.
 * @param array        $tax   La requête des classements.
 * @param int          $nombre Combien.
 * @param array        $plus  D'autres arguments.
 */
function er_contenus_etiquetes( $type, $tax, $nombre, $plus = array() ) {
	return get_posts(
		$plus + array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => $nombre,
			'tax_query'      => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
			'lang'           => function_exists( 'pll_current_language' ) ? pll_current_language() : '',
		)
	);
}

/**
 * Sur la page d'une personne ou d'un sujet, tout ce qui la concerne : les livres autour d'elle (ou les livres du
 * sujet), les rencontres (à venir, puis passées), les vidéos et les podcasts, les articles du journal, les pages.
 * Sur la page d'un auteur, ses propres livres sont au-dessus (la librairie).
 */
function er_bloc_autour() {
	$terme = get_queried_object();
	if ( ! $terme instanceof WP_Term || ! in_array( $terme->taxonomy, array( 'auteur', 'er_sujet' ), true ) ) {
		return '';
	}
	$sections = er_autour_sections( $terme );
	if ( ! $sections ) {
		return 'er_sujet' === $terme->taxonomy ? '<div class="er-autour alignwide"><p class="er-evts-vide">' . esc_html__( 'Rien encore sur ce sujet.', 'er-librairie' ) . '</p></div>' : '';
	}
	$html = '';
	foreach ( $sections as $cle => list( $titre, $contenu ) ) {
		$html .= '<section class="er-autour-section" id="' . esc_attr( 'er-autour-' . $cle ) . '"><h2 class="er-evts-titre">' . esc_html( $titre ) . '</h2>' . $contenu . '</section>';
	}
	return '<div class="er-autour alignwide">' . $html . '</div>';
}

/**
 * Les parties de « tout ce qui la concerne », pour la page d'une personne ou d'un sujet : clé → [titre, contenu], dans
 * l'ordre de la page. Calculées une fois par page : la bande de page en fait son sommaire (er_autour_sommaire()).
 *
 * @param WP_Term $terme La personne ou le sujet.
 * @return array<string,array{0:string,1:string}>
 */
function er_autour_sections( $terme ) {
	static $faites = array();
	if ( isset( $faites[ $terme->term_taxonomy_id ] ) ) {
		return $faites[ $terme->term_taxonomy_id ];
	}
	$sujet  = 'er_sujet' === $terme->taxonomy;
	$sienne = array( array( 'taxonomy' => $terme->taxonomy, 'terms' => $terme->term_id ) );
	$nombre = $sujet ? 48 : 12;

	// Les livres : autour de la personne, ou du sujet.
	if ( $sujet ) {
		$livres = er_contenus_etiquetes( 'product', $sienne, $nombre, array( 'fields' => 'ids' ) );
	} else {
		$miroir = get_term_by( 'slug', $terme->slug, 'er_autour' );
		$livres = $miroir ? er_contenus_etiquetes( 'product', array( array( 'taxonomy' => 'er_autour', 'terms' => $miroir->term_id ) ), $nombre, array( 'fields' => 'ids' ) ) : array();
	}
	// Les rencontres : la personne y est liée ou y intervient.
	$tax_rencontres = $sienne;
	if ( ! $sujet && taxonomy_exists( 'er_intervenant' ) ) {
		// L'intervenant qui est cette personne : même identifiant, ou son nom sous l'une de ses graphies (er_evt_personne).
		$siens = array();
		foreach ( get_terms( array( 'taxonomy' => 'er_intervenant', 'hide_empty' => true ) ) as $it ) {
			$p = function_exists( 'er_evt_personne' ) ? er_evt_personne( $it ) : get_term_by( 'slug', $it->slug, 'auteur' );
			if ( $p && (int) $p->term_id === (int) $terme->term_id ) {
				$siens[] = (int) $it->term_id;
			}
		}
		if ( $siens ) {
			$tax_rencontres = array( 'relation' => 'OR', $sienne[0], array( 'taxonomy' => 'er_intervenant', 'terms' => $siens ) );
		}
	}
	$rencontres = er_contenus_etiquetes( ER_EVT, $tax_rencontres, $sujet ? 60 : 24, array( 'meta_key' => 'er_debut', 'orderby' => 'meta_value', 'order' => 'DESC' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	$a_venir    = array_reverse( array_values( array_filter( $rencontres, fn( $p ) => ! er_evt_passee( $p->ID ) ) ) );
	$passees    = array_values( array_filter( $rencontres, fn( $p ) => er_evt_passee( $p->ID ) ) );
	$medias     = post_type_exists( 'er_media' ) ? er_contenus_etiquetes( 'er_media', $sienne, $sujet ? 48 : 24 ) : array();
	$articles   = er_contenus_etiquetes( 'post', $sienne, $nombre );
	$pages      = er_contenus_etiquetes( 'page', $sienne, 12, array( 'orderby' => 'title', 'order' => 'ASC' ) );
	$expos      = post_type_exists( 'er_exposition' ) ? er_contenus_etiquetes( 'er_exposition', $sienne, 12, array( 'meta_key' => 'er_debut', 'orderby' => 'meta_value', 'order' => 'DESC' ) ) : array(); // phpcs:ignore WordPress.DB.SlowDBQuery
	update_post_thumbnail_cache( new WP_Query( array( 'post__in' => array_merge( array( 0 ), wp_list_pluck( array_merge( $rencontres, $medias, $articles, $expos ), 'ID' ) ), 'post_type' => 'any', 'posts_per_page' => -1 ) ) );

	$sections = array();
	if ( $a_venir ) {
		$sections['a-venir'] = array( __( 'Prochainement au centre', 'er-librairie' ), '<ol class="er-evts-lignes">' . implode( '', array_map( 'er_evt_ligne', $a_venir ) ) . '</ol>' );
	}
	if ( $livres ) {
		/* translators: %s: le nom de la personne */
		$titre      = $sujet ? __( 'À la librairie', 'er-librairie' ) : sprintf( __( 'Autour de %s', 'er-librairie' ), $terme->name );
		$sections['livres'] = array( $titre, '<ul class="er-autour-livres">' . implode( '', array_map( 'er_carte_livre', $livres ) ) . '</ul>' );
	}
	if ( $passees ) {
		$sections['rencontres'] = array( $sujet ? __( 'Les rencontres', 'er-librairie' ) : __( 'Au centre', 'er-librairie' ), '<ul class="er-evts-grille">' . implode( '', array_map( 'er_evt_carte', $passees ) ) . '</ul>' );
	}
	if ( $expos ) {
		$sections['expositions'] = array( _n( 'L’exposition', 'Les expositions', count( $expos ), 'er-librairie' ), '<ul class="er-cartes">' . implode( '', array_map( 'er_carte_contenu', $expos ) ) . '</ul>' );
	}
	if ( $medias ) {
		$sections['medias'] = array( __( 'Écouter, regarder', 'er-librairie' ), '<ul class="er-cartes">' . implode( '', array_map( 'er_carte_contenu', $medias ) ) . '</ul>' );
	}
	if ( $articles ) {
		$sections['journal'] = array( __( 'Dans le journal', 'er-librairie' ), '<ul class="er-cartes">' . implode( '', array_map( 'er_carte_contenu', $articles ) ) . '</ul>' );
	}
	if ( $pages ) {
		$liens = '';
		foreach ( $pages as $p ) {
			$liens .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></li>';
		}
		$sections['pages'] = array( __( 'À lire sur le site', 'er-librairie' ), '<ul class="er-autour-pages er-lien-fleche">' . $liens . '</ul>' );
	}
	$faites[ $terme->term_taxonomy_id ] = $sections;
	return $sections;
}

/**
 * Le sommaire de « tout ce qui la concerne », en entrées de la bande de page.
 *
 * @param WP_Term $terme La personne ou le sujet.
 */
function er_autour_sommaire( $terme ) {
	$entrees = array();
	foreach ( er_autour_sections( $terme ) as $cle => list( $titre ) ) {
		$entrees[] = array(
			'texte' => $titre,
			'lien'  => '#er-autour-' . $cle,
		);
	}
	return $entrees;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Devine les sujets et les auteurs des articles et des rencontres déjà en place, d'après leur titre et leur chapô
	 * (sans rien ôter).
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : post, er_evenement, ou les deux (par défaut).
	 *
	 * [--simulation]
	 * : Dit ce qui serait posé, sans rien poser.
	 *
	 * @param array $args  Rien.
	 * @param array $assoc Les options.
	 */
	WP_CLI::add_command(
		'er sujets deviner',
		function ( $args, $assoc ) {
			$types   = empty( $assoc['type'] ) ? array( 'post', ER_EVT ) : array( $assoc['type'] );
			$auteurs = er_evt_auteurs_connus();
			$n       = 0;
			foreach ( get_posts( array( 'post_type' => $types, 'post_status' => 'publish', 'numberposts' => -1, 'lang' => '' ) ) as $p ) {
				if ( get_post_meta( $p->ID, 'er_etiquettes_relues', true ) ) {
					continue;
				}
				$texte = $p->post_title . ' ' . $p->post_excerpt;
				if ( ! empty( $assoc['simulation'] ) ) {
					$poses = array_merge( er_sujets_deviner( $texte ), array_map( fn( $c ) => $c[0]->name . ( $c[2] ? ' (intervient)' : '' ), er_auteurs_cites( $texte, $auteurs ) ) );
				} else {
					$poses = er_etiqueter( $p->ID, $texte, $auteurs );
				}
				if ( $poses ) {
					++$n;
					WP_CLI::log( $p->post_title . ' → ' . implode( ', ', $poses ) );
				}
			}
			WP_CLI::success( $n . ' contenus étiquetés.' );
		}
	);
}
