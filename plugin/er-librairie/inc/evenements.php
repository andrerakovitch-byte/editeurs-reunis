<?php
/**
 * Les rencontres du centre culturel : le type « Événement », sa billetterie dans WooCommerce, ses vidéos et ses
 * enregistrements, la liste de la page « Nos événements », l'import de l'ancien site.
 *
 * Une rencontre est un contenu à part (er_evenement), à l'adresse /centre-culturel/evenements/<slug>/. Ce qu'elle
 * garde, en champs (méta) : le début et la fin (heure de Paris, « AAAA-MM-JJ HH:MM »), le lieu, l'état (annulée,
 * complète), l'inscription (billetterie, gratuite sur inscription, entrée libre), le prix et le nombre de places, la
 * langue, la forme (rencontre, conférence…), les vidéos et les enregistrements (un lien par ligne), les livres liés.
 * Le cycle et les intervenants sont des classements. Une rencontre payante ou sur inscription a son billet : un
 * produit virtuel de WooCommerce, caché de la librairie, tenu à jour à chaque enregistrement (le prix, les places).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

const ER_EVT     = 'er_evenement';
const ER_ADRESSE = '11, rue de la Montagne-Sainte-Geneviève, 75005 Paris';

/* Le type et ses classements -------------------------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		register_post_type(
			ER_EVT,
			array(
				'labels'        => array(
					'name'               => __( 'Événements', 'er-librairie' ),
					'singular_name'      => __( 'Événement', 'er-librairie' ),
					'add_new'            => __( 'Ajouter', 'er-librairie' ),
					'add_new_item'       => __( 'Ajouter un événement', 'er-librairie' ),
					'edit_item'          => __( 'Modifier l’événement', 'er-librairie' ),
					'view_item'          => __( 'Voir l’événement', 'er-librairie' ),
					'all_items'          => __( 'Tous les événements', 'er-librairie' ),
					'search_items'       => __( 'Chercher un événement', 'er-librairie' ),
					'not_found'          => __( 'Aucun événement', 'er-librairie' ),
					'not_found_in_trash' => __( 'Aucun événement dans la corbeille', 'er-librairie' ),
					'menu_name'          => __( 'Événements', 'er-librairie' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-calendar-alt',
				'menu_position' => 26,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
				'has_archive'   => false, // La liste est la page « Nos événements » (le bloc er/evenements).
				'rewrite'       => array(
					'slug'       => 'centre-culturel/evenements',
					'with_front' => false,
				),
			)
		);
		register_taxonomy(
			'er_cycle',
			ER_EVT,
			array(
				'labels'             => array(
					'name'          => __( 'Cycles', 'er-librairie' ),
					'singular_name' => __( 'Cycle', 'er-librairie' ),
					'all_items'     => __( 'Tous les cycles', 'er-librairie' ),
					'edit_item'     => __( 'Modifier le cycle', 'er-librairie' ),
					'add_new_item'  => __( 'Ajouter un cycle', 'er-librairie' ),
					'menu_name'     => __( 'Cycles', 'er-librairie' ),
				),
				'public'             => true,
				'publicly_queryable' => false, // Un cycle se choisit dans la liste de « Nos événements » (?cycle=).
				'rewrite'            => false,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'hierarchical'       => false,
			)
		);
		register_taxonomy(
			'er_intervenant',
			ER_EVT,
			array(
				'labels'             => array(
					'name'          => __( 'Intervenants', 'er-librairie' ),
					'singular_name' => __( 'Intervenant', 'er-librairie' ),
					'all_items'     => __( 'Tous les intervenants', 'er-librairie' ),
					'edit_item'     => __( 'Modifier l’intervenant', 'er-librairie' ),
					'add_new_item'  => __( 'Ajouter un intervenant', 'er-librairie' ),
					'separate_items_with_commas' => __( 'Séparer les noms par des virgules', 'er-librairie' ),
					'menu_name'     => __( 'Intervenants', 'er-librairie' ),
				),
				'public'             => true,
				'publicly_queryable' => false, // Un intervenant qui est aussi un auteur renvoie à sa page d'auteur.
				'rewrite'            => false,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'hierarchical'       => false,
			)
		);
		foreach ( array_keys( er_evt_champs() ) as $cle ) {
			register_post_meta(
				ER_EVT,
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
 * Les champs d'une rencontre : clé → libellé, genre de saisie, aide.
 */
function er_evt_champs() {
	return array(
		'er_debut'       => array( __( 'Début', 'er-librairie' ), 'datetime', '' ),
		'er_fin'         => array( __( 'Fin', 'er-librairie' ), 'datetime', __( 'Facultatif.', 'er-librairie' ) ),
		'er_lieu'        => array( __( 'Lieu', 'er-librairie' ), 'texte', __( 'Vide : au centre, 11, rue de la Montagne-Sainte-Geneviève.', 'er-librairie' ) ),
		'er_forme'       => array( __( 'Forme', 'er-librairie' ), 'texte', __( 'Rencontre, conférence, présentation, soirée, exposition…', 'er-librairie' ) ),
		'er_langue'      => array( __( 'Langue', 'er-librairie' ), 'langue', '' ),
		'er_inscription' => array( __( 'Entrée', 'er-librairie' ), 'inscription', '' ),
		'er_prix'        => array( __( 'Prix de l’entrée (€)', 'er-librairie' ), 'nombre', __( 'Pour la billetterie.', 'er-librairie' ) ),
		'er_places'      => array( __( 'Places', 'er-librairie' ), 'nombre', __( 'Vide : sans limite.', 'er-librairie' ) ),
		'er_etat'        => array( __( 'État', 'er-librairie' ), 'etat', '' ),
		'er_videos'      => array( __( 'Vidéos et enregistrements', 'er-librairie' ), 'liens', __( 'Un lien par ligne : YouTube, Vimeo, SoundCloud, Dailymotion, Spotify, Facebook, ou un fichier de la médiathèque. Pour un titre : « Titre | lien ».', 'er-librairie' ) ),
		'er_livres'      => array( __( 'Livres liés', 'er-librairie' ), 'texte', __( 'Leurs références (ou identifiants), séparées par des virgules. Les livres des intervenants qui sont aussi des auteurs s’y ajoutent.', 'er-librairie' ) ),
		'er_produit'     => array( __( 'Billet', 'er-librairie' ), 'interne', '' ),
		'er_wix'         => array( __( 'Ancien site', 'er-librairie' ), 'interne', '' ),
	);
}

/* Ce qu'on lit d'une rencontre ----------------------------------------------------------------------------------- */

/**
 * Le fuseau du centre.
 */
function er_evt_fuseau() {
	return new DateTimeZone( 'Europe/Paris' );
}

/**
 * Une date des champs (« AAAA-MM-JJ HH:MM », heure de Paris), ou null.
 *
 * @param int    $id   La rencontre.
 * @param string $cle  er_debut ou er_fin.
 */
function er_evt_date( $id, $cle = 'er_debut' ) {
	$valeur = (string) get_post_meta( $id, $cle, true );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2})?$/', $valeur ) ) {
		return null;
	}
	return new DateTimeImmutable( $valeur, er_evt_fuseau() );
}

/**
 * L'heure à la française (19 h, 18 h 30), ailleurs sur 24 heures.
 *
 * @param DateTimeImmutable $d La date.
 */
function er_evt_heure( $d ) {
	if ( 'fr' !== substr( determine_locale(), 0, 2 ) ) {
		return $d->format( 'H:i' );
	}
	return $d->format( 'G' ) . ' h' . ( '00' === $d->format( 'i' ) ? '' : ' ' . $d->format( 'i' ) );
}

/**
 * Quand : « lundi 30 novembre 2026, 19 h » ; pour ce qui dure (une exposition), « du 2 juin au 28 septembre 2021 ».
 * L'heure de fin ne se dit pas : celle de l'ancien site était souvent fausse (le lendemain).
 *
 * @param int  $id   La rencontre.
 * @param bool $jour Avec le jour de la semaine et l'heure.
 */
function er_evt_quand( $id, $jour = true ) {
	$debut = er_evt_date( $id );
	if ( ! $debut ) {
		return __( 'Date à venir', 'er-librairie' );
	}
	$fin = er_evt_date( $id, 'er_fin' );
	if ( er_evt_dure( $id ) ) {
		$meme_annee = $fin->format( 'Y' ) === $debut->format( 'Y' );
		return sprintf(
			/* translators: 1: le premier jour, 2: le dernier */
			__( 'du %1$s au %2$s', 'er-librairie' ),
			wp_date( $meme_annee ? __( 'j F', 'er-librairie' ) : __( 'j F Y', 'er-librairie' ), $debut->getTimestamp(), er_evt_fuseau() ),
			wp_date( __( 'j F Y', 'er-librairie' ), $fin->getTimestamp(), er_evt_fuseau() )
		);
	}
	$texte = wp_date( $jour ? __( 'l j F Y', 'er-librairie' ) : __( 'j F Y', 'er-librairie' ), $debut->getTimestamp(), er_evt_fuseau() );
	if ( $jour && '00:00' !== $debut->format( 'H:i' ) ) {
		$texte .= ', ' . er_evt_heure( $debut );
	}
	return $texte;
}

/**
 * Dure-t-elle plusieurs jours (une exposition) ?
 *
 * @param int $id La rencontre.
 */
function er_evt_dure( $id ) {
	$debut = er_evt_date( $id );
	$fin   = er_evt_date( $id, 'er_fin' );
	return $debut && $fin && $fin->getTimestamp() - $debut->getTimestamp() >= 3 * DAY_IN_SECONDS;
}

/**
 * La rencontre est-elle passée ? (sa fin, sinon son début et deux heures)
 *
 * @param int $id La rencontre.
 */
function er_evt_passee( $id ) {
	$fin = er_evt_date( $id, 'er_fin' );
	if ( ! $fin ) {
		$debut = er_evt_date( $id );
		$fin   = $debut ? $debut->modify( '+2 hours' ) : null;
	}
	return $fin && $fin < new DateTimeImmutable( 'now', er_evt_fuseau() );
}

/**
 * Les vidéos et les enregistrements : [ titre, lien ] par ligne.
 *
 * @param int $id La rencontre.
 */
function er_evt_videos( $id ) {
	$liens = array();
	foreach ( preg_split( '/\R/', (string) get_post_meta( $id, 'er_videos', true ) ) as $ligne ) {
		$ligne = trim( $ligne );
		if ( '' === $ligne ) {
			continue;
		}
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
 * Le lieu, tel qu'on le dit : « au centre » quand c'est à la librairie.
 *
 * @param int $id La rencontre.
 */
function er_evt_lieu( $id ) {
	$lieu = trim( (string) get_post_meta( $id, 'er_lieu', true ) );
	if ( '' === $lieu || false !== stripos( remove_accents( $lieu ), 'montagne-sainte-genevieve' ) || false !== stripos( remove_accents( $lieu ), 'montagne sainte genevieve' ) ) {
		return '';
	}
	return $lieu;
}

/**
 * La langue : en français, en russe…
 *
 * @param int $id La rencontre.
 */
function er_evt_langue( $id ) {
	$langues = array(
		'francais' => __( 'En français', 'er-librairie' ),
		'russe'    => __( 'En russe', 'er-librairie' ),
		'bilingue' => __( 'En français et en russe', 'er-librairie' ),
		'anglais'  => __( 'En anglais', 'er-librairie' ),
	);
	$l = (string) get_post_meta( $id, 'er_langue', true );
	return isset( $langues[ $l ] ) ? $langues[ $l ] : '';
}

/**
 * Le billet de la rencontre (un produit de WooCommerce), s'il y en a un.
 *
 * @param int $id La rencontre.
 */
function er_evt_billet( $id ) {
	$produit = (int) get_post_meta( $id, 'er_produit', true );
	return $produit && function_exists( 'wc_get_product' ) ? wc_get_product( $produit ) : null;
}

/**
 * L'adresse de la page « Nos événements », avec des critères.
 *
 * @param array $criteres annee, cycle, video.
 */
function er_evt_page_liste( $criteres = array() ) {
	$page = get_page_by_path( 'centre-culturel/evenements' );
	if ( $page && function_exists( 'pll_get_post' ) ) {
		$page = get_post( pll_get_post( $page->ID ) ?: $page->ID );
	}
	$url = $page ? get_permalink( $page ) : home_url( '/centre-culturel/evenements/' );
	return add_query_arg( array_map( 'rawurlencode', $criteres ), $url );
}

/**
 * Un prix, en texte : « 10 € », « 7,50 € ».
 *
 * @param float $prix Le prix.
 */
function er_evt_prix( $prix ) {
	$prix = (float) $prix;
	return html_entity_decode( wp_strip_all_tags( wc_price( $prix, array( 'decimals' => floor( $prix ) === $prix ? 0 : 2 ) ) ), ENT_QUOTES, 'UTF-8' );
}

/* La billetterie --------------------------------------------------------------------------------------------------
 *
 * Le billet est un produit virtuel, caché du catalogue et de la recherche : on le prend depuis la page de la
 * rencontre (« Réserver », « S'inscrire »), jamais depuis la librairie. Le stock est le nombre de places : quand on
 * change le nombre de places, le stock bouge d'autant (les places déjà prises restent prises). Une rencontre passée,
 * annulée ou complète ne se réserve plus.
 */

/**
 * Crée ou met à jour le billet d'une rencontre.
 *
 * @param int $id La rencontre.
 */
function er_evt_billet_synchroniser( $id ) {
	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return;
	}
	$inscription = (string) get_post_meta( $id, 'er_inscription', true );
	$produit     = er_evt_billet( $id );
	$prix        = 'gratuite' === $inscription ? '0' : wc_format_decimal( (string) get_post_meta( $id, 'er_prix', true ) );
	$veut        = in_array( $inscription, array( 'billetterie', 'gratuite' ), true ) && '' !== $prix && ! er_evt_passee( $id );
	if ( ! $veut ) {
		if ( $produit && 'publish' === $produit->get_status() && ! in_array( $inscription, array( 'billetterie', 'gratuite' ), true ) ) {
			$produit->set_status( 'private' ); // Plus de billetterie : le billet se retire, les commandes restent.
			$produit->save();
		}
		return;
	}
	$nouveau = ! $produit;
	if ( $nouveau ) {
		$produit = new WC_Product_Simple();
	}
	$produit->set_name( sprintf( /* translators: %s: le titre de la rencontre */ __( 'Entrée — %s', 'er-librairie' ), get_the_title( $id ) ) );
	$produit->set_short_description( er_evt_quand( $id ) . ( er_evt_lieu( $id ) ? ' · ' . er_evt_lieu( $id ) : '' ) );
	$produit->set_virtual( true );
	$produit->set_catalog_visibility( 'hidden' );
	$produit->set_status( 'publish' === get_post_status( $id ) ? 'publish' : 'private' );
	$produit->set_regular_price( $prix );
	$produit->set_image_id( (int) get_post_thumbnail_id( $id ) );
	$places    = (int) get_post_meta( $id, 'er_places', true );
	$anciennes = (int) $produit->get_meta( 'er_places' );
	if ( $places > 0 ) {
		$produit->set_manage_stock( true );
		$stock = $nouveau || ! $produit->get_manage_stock() ? $places : (int) $produit->get_stock_quantity() + ( $places - $anciennes );
		$produit->set_stock_quantity( max( 0, $stock ) );
	} else {
		$produit->set_manage_stock( false );
		$produit->set_stock_status( 'instock' );
	}
	$produit->update_meta_data( 'er_places', $places );
	$produit->update_meta_data( 'er_evenement', $id );
	$produit->save();
	if ( $nouveau ) {
		update_post_meta( $id, 'er_produit', $produit->get_id() );
	}
}

add_action(
	'save_post_' . ER_EVT,
	function ( $id ) {
		if ( wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) ) {
			return;
		}
		er_evt_billet_synchroniser( $id );
		delete_transient( 'er_evt_annees' );
	},
	20
);

// Un billet ne se prend plus quand la rencontre est passée, annulée ou complète.
add_filter(
	'woocommerce_is_purchasable',
	function ( $ok, $produit ) {
		$evt = (int) $produit->get_meta( 'er_evenement' );
		if ( ! $evt ) {
			return $ok;
		}
		if ( 'publish' !== get_post_status( $evt ) || er_evt_passee( $evt ) || in_array( get_post_meta( $evt, 'er_etat', true ), array( 'annule', 'complet' ), true ) ) {
			return false;
		}
		return $ok;
	},
	10,
	2
);

// La page d'un billet est celle de sa rencontre ; dans le panier et la commande, le billet y renvoie aussi.
add_action(
	'template_redirect',
	function () {
		if ( function_exists( 'is_product' ) && is_product() ) {
			$evt = (int) get_post_meta( get_queried_object_id(), 'er_evenement', true );
			if ( $evt && 'publish' === get_post_status( $evt ) ) {
				wp_safe_redirect( get_permalink( $evt ), 301 );
				exit;
			}
		}
	}
);
add_filter(
	'woocommerce_cart_item_permalink',
	function ( $lien, $article ) {
		$evt = isset( $article['product_id'] ) ? (int) get_post_meta( $article['product_id'], 'er_evenement', true ) : 0;
		return $evt ? get_permalink( $evt ) : $lien;
	},
	10,
	2
);

/* Les anciennes adresses ------------------------------------------------------------------------------------------
 *
 * Après la bascule, /events-1/<slug> (l'adresse de Wix) mène à la rencontre du même nom, pour les liens qui
 * circulent et pour les moteurs de recherche.
 */
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;
		}
		$chemin = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! preg_match( '#^(?:[a-z]{2}/)?(?:events-1|event-details|event-info)/([^/]+)#', $chemin, $m ) ) {
			return;
		}
		$evts = get_posts(
			array(
				'post_type'   => ER_EVT,
				'name'        => sanitize_title( urldecode( $m[1] ) ),
				'numberposts' => 1,
				'lang'        => '',
			)
		);
		if ( $evts ) {
			wp_safe_redirect( get_permalink( $evts[0] ), 301 );
			exit;
		}
	}
);

/* La saisie -------------------------------------------------------------------------------------------------------- */

add_action(
	'add_meta_boxes_' . ER_EVT,
	function () {
		add_meta_box( 'er-evenement', __( 'La rencontre', 'er-librairie' ), 'er_evt_boite', ER_EVT, 'normal', 'high' );
	}
);

/**
 * Les champs de la rencontre, dans l'écran de modification.
 *
 * @param WP_Post $post La rencontre.
 */
function er_evt_boite( $post ) {
	wp_nonce_field( 'er_evenement', 'er_evenement_jeton' );
	$choix = array(
		'langue'      => array(
			''         => '—',
			'francais' => __( 'En français', 'er-librairie' ),
			'russe'    => __( 'En russe', 'er-librairie' ),
			'bilingue' => __( 'En français et en russe', 'er-librairie' ),
			'anglais'  => __( 'En anglais', 'er-librairie' ),
		),
		'inscription' => array(
			'libre'       => __( 'Entrée libre, sans inscription', 'er-librairie' ),
			'gratuite'    => __( 'Gratuite, sur inscription', 'er-librairie' ),
			'billetterie' => __( 'Payante : billetterie', 'er-librairie' ),
		),
		'etat'        => array(
			''        => __( 'Prévue', 'er-librairie' ),
			'complet' => __( 'Complète', 'er-librairie' ),
			'annule'  => __( 'Annulée', 'er-librairie' ),
		),
	);
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( er_evt_champs() as $cle => list( $libelle, $genre, $aide ) ) {
		$valeur = (string) get_post_meta( $post->ID, $cle, true );
		if ( 'interne' === $genre ) {
			if ( 'er_produit' === $cle && $valeur ) {
				echo '<tr><th scope="row">' . esc_html( $libelle ) . '</th><td><a href="' . esc_url( (string) get_edit_post_link( (int) $valeur ) ) . '">' . esc_html( get_the_title( (int) $valeur ) ) . '</a>';
				$billet = er_evt_billet( $post->ID );
				if ( $billet ) {
					/* translators: %d: le nombre de billets pris */
					echo ' · ' . esc_html( sprintf( _n( '%d place prise', '%d places prises', (int) $billet->get_total_sales(), 'er-librairie' ), (int) $billet->get_total_sales() ) );
				}
				echo '</td></tr>';
			}
			continue;
		}
		echo '<tr><th scope="row"><label for="' . esc_attr( $cle ) . '">' . esc_html( $libelle ) . '</label></th><td>';
		switch ( $genre ) {
			case 'datetime':
				echo '<input type="datetime-local" id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '" value="' . esc_attr( str_replace( ' ', 'T', $valeur ) ) . '">';
				break;
			case 'nombre':
				echo '<input type="number" min="0" step="' . ( 'er_prix' === $cle ? '0.01' : '1' ) . '" class="small-text" id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '" value="' . esc_attr( $valeur ) . '">';
				break;
			case 'liens':
				echo '<textarea class="large-text code" rows="4" id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '">' . esc_textarea( $valeur ) . '</textarea>';
				break;
			case 'langue':
			case 'inscription':
			case 'etat':
				echo '<select id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '">';
				foreach ( $choix[ $genre ] as $v => $nom ) {
					echo '<option value="' . esc_attr( $v ) . '"' . selected( $valeur, $v, false ) . '>' . esc_html( $nom ) . '</option>';
				}
				echo '</select>';
				break;
			default:
				echo '<input type="text" class="regular-text" id="' . esc_attr( $cle ) . '" name="' . esc_attr( $cle ) . '" value="' . esc_attr( $valeur ) . '">';
		}
		if ( $aide ) {
			echo '<p class="description">' . esc_html( $aide ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

add_action(
	'save_post_' . ER_EVT,
	function ( $id ) {
		if ( ! isset( $_POST['er_evenement_jeton'] ) || ! wp_verify_nonce( sanitize_key( $_POST['er_evenement_jeton'] ), 'er_evenement' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		foreach ( er_evt_champs() as $cle => list( , $genre ) ) {
			if ( 'interne' === $genre || ! isset( $_POST[ $cle ] ) ) {
				continue;
			}
			$valeur = wp_unslash( $_POST[ $cle ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			switch ( $genre ) {
				case 'datetime':
					$valeur = preg_match( '/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/', $valeur, $m ) ? $m[1] . ' ' . $m[2] : '';
					break;
				case 'nombre':
					$valeur = '' === trim( $valeur ) ? '' : (string) ( 'er_prix' === $cle ? wc_format_decimal( $valeur ) : absint( $valeur ) );
					break;
				case 'liens':
					$valeur = sanitize_textarea_field( $valeur );
					break;
				default:
					$valeur = sanitize_text_field( $valeur );
			}
			update_post_meta( $id, $cle, $valeur );
		}
	},
	10
);

// La liste des événements de l'administration : la date de la rencontre, la plus récente en tête.
add_filter(
	'manage_' . ER_EVT . '_posts_columns',
	function ( $colonnes ) {
		$avant = array_slice( $colonnes, 0, 2, true );
		return $avant + array( 'er_debut' => __( 'Le', 'er-librairie' ) ) + array_slice( $colonnes, 2, null, true );
	}
);
add_action(
	'manage_' . ER_EVT . '_posts_custom_column',
	function ( $colonne, $id ) {
		if ( 'er_debut' === $colonne ) {
			echo esc_html( er_evt_quand( $id, false ) );
			$etat = get_post_meta( $id, 'er_etat', true );
			if ( 'annule' === $etat ) {
				echo ' · <strong>' . esc_html__( 'annulée', 'er-librairie' ) . '</strong>';
			}
			if ( get_post_meta( $id, 'er_videos', true ) ) {
				echo ' · ' . esc_html__( 'vidéo', 'er-librairie' );
			}
		}
	},
	10,
	2
);
add_filter(
	'manage_edit-' . ER_EVT . '_sortable_columns',
	function ( $colonnes ) {
		$colonnes['er_debut'] = 'er_debut';
		return $colonnes;
	}
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || ER_EVT !== $q->get( 'post_type' ) ) {
			return;
		}
		if ( ! $q->get( 'orderby' ) || 'er_debut' === $q->get( 'orderby' ) ) {
			$q->set( 'meta_key', 'er_debut' );
			$q->set( 'orderby', 'meta_value' );
			$q->set( 'order', $q->get( 'order' ) ? $q->get( 'order' ) : 'DESC' );
		}
	}
);

/* L'affichage : les blocs ------------------------------------------------------------------------------------------
 *
 * Des blocs dynamiques, que le gabarit single-er_evenement du thème et la page « Nos événements » placent :
 * er/evenement-surtitre (la forme et le cycle, au-dessus du titre), er/evenement-infos (le chapô, les lignes Quand,
 * Où, Entrée, Avec, Langue, puis « Réserver » ou l'état), er/evenement-videos (« Revoir, réécouter »),
 * er/evenement-livres (les livres de la rencontre), er/evenement-retour (« Toutes les rencontres ») et er/evenements
 * (la liste : à venir, puis l'archive).
 */

add_action(
	'init',
	function () {
		$blocs = array(
			'er/evenement-surtitre' => 'er_evt_bloc_surtitre',
			'er/evenement-infos'    => 'er_evt_bloc_infos',
			'er/evenement-videos'   => 'er_evt_bloc_videos',
			'er/evenement-livres'   => 'er_evt_bloc_livres',
			'er/evenement-retour'   => 'er_evt_bloc_retour',
			'er/evenements'         => 'er_evt_bloc_liste',
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
	}
);

/**
 * La forme et le cycle : « Conférence · Поэт и время ».
 *
 * @param int $id La rencontre.
 */
function er_evt_bloc_surtitre( $id ) {
	if ( ER_EVT !== get_post_type( $id ) ) {
		return '';
	}
	$parties = array();
	$forme   = (string) get_post_meta( $id, 'er_forme', true );
	if ( $forme ) {
		$parties[] = esc_html( $forme );
	}
	foreach ( wp_get_post_terms( $id, 'er_cycle' ) as $cycle ) {
		$parties[] = '<a href="' . esc_url( er_evt_page_liste( array( 'cycle' => $cycle->slug ) ) . '#archive' ) . '">' . esc_html( $cycle->name ) . '</a>';
	}
	return $parties ? '<p class="er-evt-surtitre er-points-centre">' . implode( ' · ', $parties ) . '</p>' : '';
}

/**
 * La page de personne d'un intervenant : le même identifiant, sinon son nom retrouvé sous toutes ses graphies
 * (er_personne_trouver).
 *
 * @param WP_Term $t L'intervenant.
 * @return WP_Term|null
 */
function er_evt_personne( $t ) {
	$auteur = get_term_by( 'slug', $t->slug, 'auteur' );
	if ( ! $auteur && function_exists( 'er_personne_trouver' ) ) {
		$auteur = er_personne_trouver( $t->name );
	}
	return $auteur ? $auteur : null;
}

/**
 * Les intervenants, liés à leur page d'auteur quand ils en ont une.
 *
 * @param int $id La rencontre.
 */
function er_evt_intervenants( $id ) {
	$noms = array();
	foreach ( wp_get_post_terms( $id, 'er_intervenant' ) as $t ) {
		$auteur = er_evt_personne( $t );
		$lien   = $auteur ? get_term_link( $auteur ) : '';
		$noms[] = $lien && ! is_wp_error( $lien ) ? '<a href="' . esc_url( $lien ) . '">' . esc_html( $t->name ) . '</a>' : esc_html( $t->name );
	}
	return $noms;
}

/**
 * Le chapô, les lignes de la rencontre et ce qu'on peut faire (réserver, s'inscrire), ou son état.
 *
 * @param int $id La rencontre.
 */
function er_evt_bloc_infos( $id ) {
	if ( ER_EVT !== get_post_type( $id ) ) {
		return '';
	}
	$passee = er_evt_passee( $id );
	$etat   = (string) get_post_meta( $id, 'er_etat', true );
	$html   = '';
	if ( function_exists( 'wc_print_notices' ) && function_exists( 'WC' ) && WC()->session ) {
		$html .= wc_print_notices( true ); // « Le billet a été ajouté au panier ».
	}
	if ( has_excerpt( $id ) ) {
		$html .= '<p class="er-evt-chapo">' . esc_html( get_the_excerpt( $id ) ) . '</p>';
	}
	$lignes = array();
	$lignes[ __( 'Quand', 'er-librairie' ) ] = esc_html( er_evt_quand( $id ) );
	$lieu   = er_evt_lieu( $id );
	$trouver = get_page_by_path( 'nous-trouver' );
	$lignes[ __( 'Où', 'er-librairie' ) ] = $lieu
		? esc_html( $lieu )
		: esc_html__( 'Au centre, 11, rue de la Montagne-Sainte-Geneviève, Paris 5e', 'er-librairie' ) . ( $trouver ? ' · <a href="' . esc_url( get_permalink( $trouver ) ) . '">' . esc_html__( 'Nous trouver', 'er-librairie' ) . '</a>' : '' );
	if ( ! $passee && 'annule' !== $etat ) {
		$inscription = (string) get_post_meta( $id, 'er_inscription', true );
		$billet      = er_evt_billet( $id );
		$entree      = '';
		if ( 'billetterie' === $inscription ) {
			$prix   = (string) get_post_meta( $id, 'er_prix', true );
			$entree = '' !== $prix ? er_evt_prix( $prix ) : __( 'Payante', 'er-librairie' );
		} elseif ( 'gratuite' === $inscription ) {
			$entree = __( 'Libre, sur inscription', 'er-librairie' );
		} elseif ( 'libre' === $inscription ) {
			$entree = __( 'Libre, sans inscription', 'er-librairie' );
		}
		if ( $billet && $billet->managing_stock() && $billet->get_stock_quantity() > 0 && $billet->get_stock_quantity() <= 10 ) {
			/* translators: %d: le nombre de places qui restent */
			$entree .= ' · ' . sprintf( _n( 'il reste %d place', 'il reste %d places', $billet->get_stock_quantity(), 'er-librairie' ), $billet->get_stock_quantity() );
		}
		if ( $entree ) {
			$lignes[ __( 'Entrée', 'er-librairie' ) ] = esc_html( $entree );
		}
	}
	$avec = er_evt_intervenants( $id );
	if ( $avec ) {
		$lignes[ __( 'Avec', 'er-librairie' ) ] = implode( ', ', $avec );
	}
	$langue = er_evt_langue( $id );
	if ( $langue ) {
		$lignes[ __( 'Langue', 'er-librairie' ) ] = esc_html( $langue );
	}
	$html .= '<dl class="er-evt-infos">';
	foreach ( $lignes as $libelle => $valeur ) {
		$html .= '<div class="er-evt-info"><dt>' . esc_html( $libelle ) . '</dt><dd>' . $valeur . '</dd></div>';
	}
	$html .= '</dl>';

	// Ce qu'on peut faire, ou l'état.
	if ( 'annule' === $etat ) {
		$html .= '<p class="er-evt-etat er-evt-etat-annule">' . esc_html__( 'Cette rencontre est annulée.', 'er-librairie' ) . '</p>';
	} elseif ( $passee ) {
		$html .= '<p class="er-evt-etat">' . esc_html(
			er_evt_dure( $id )
				? sprintf( /* translators: %s: « du 2 juin au 28 septembre 2021 » */ __( 'C’était %s.', 'er-librairie' ), er_evt_quand( $id, false ) )
				: sprintf( /* translators: %s: la date */ __( 'Cette rencontre a eu lieu le %s.', 'er-librairie' ), er_evt_quand( $id, false ) )
		) . '</p>';
		if ( er_evt_videos( $id ) ) {
			$html .= '<p class="er-evt-actions"><a class="er-evt-bouton" href="#revoir">' . esc_html__( 'Revoir la rencontre', 'er-librairie' ) . '</a></p>';
		}
	} else {
		$inscription = (string) get_post_meta( $id, 'er_inscription', true );
		$billet      = er_evt_billet( $id );
		if ( 'complet' === $etat || ( $billet && ! $billet->is_in_stock() ) ) {
			$html .= '<p class="er-evt-etat">' . esc_html__( 'Complet.', 'er-librairie' ) . '</p>';
		} elseif ( $billet && $billet->is_purchasable() ) {
			$max   = $billet->managing_stock() ? min( 10, (int) $billet->get_stock_quantity() ) : 10;
			$texte = 'gratuite' === $inscription ? __( 'S’inscrire', 'er-librairie' ) : __( 'Réserver', 'er-librairie' );
			$html .= '<form class="er-evt-actions er-evt-reserver" method="post" action="' . esc_url( get_permalink( $id ) ) . '">'
				. '<label class="er-evt-nombre"><span>' . esc_html__( 'Places', 'er-librairie' ) . '</span>'
				. '<input type="number" name="quantity" value="1" min="1" max="' . esc_attr( $max ) . '" inputmode="numeric"></label>'
				. '<button type="submit" class="er-evt-bouton" name="add-to-cart" value="' . esc_attr( $billet->get_id() ) . '">' . esc_html( $texte ) . '</button>'
				. '</form>';
		} elseif ( in_array( $inscription, array( 'billetterie', 'gratuite' ), true ) ) {
			$html .= '<p class="er-evt-etat">' . esc_html__( 'Les réservations ouvrent bientôt.', 'er-librairie' ) . '</p>';
		}
	}
	return '<div class="er-evt-tete">' . $html . '</div>';
}

/**
 * Le lecteur d'une vidéo ou d'un enregistrement (oEmbed, gardé par WordPress) ; un lien quand le site ne se lit pas.
 *
 * @param string $url Le lien.
 */
function er_evt_lecteur( $url ) {
	if ( preg_match( '/\.(mp4|webm|m4v|mov)(\?|$)/i', $url ) ) {
		return wp_video_shortcode( array( 'src' => $url ) );
	}
	if ( preg_match( '/\.(mp3|m4a|ogg|oga|wav)(\?|$)/i', $url ) ) {
		return wp_audio_shortcode( array( 'src' => $url ) );
	}
	$html = isset( $GLOBALS['wp_embed'] ) ? $GLOBALS['wp_embed']->shortcode( array(), $url ) : '';
	if ( $html && false !== strpos( $html, '<iframe' ) ) {
		// YouTube sans témoins tant qu'on ne lance pas la vidéo.
		return '<div class="er-evt-lecteur">' . str_replace( array( 'https://www.youtube.com/embed/', 'https://youtube.com/embed/' ), 'https://www.youtube-nocookie.com/embed/', $html ) . '</div>';
	}
	$hote = preg_replace( '/^www\./', '', (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$noms = array(
		'facebook.com' => 'Facebook',
		'fb.watch'     => 'Facebook',
		'youtube.com'  => 'YouTube',
		'youtu.be'     => 'YouTube',
		'vimeo.com'    => 'Vimeo',
	);
	/* translators: %s: le site (Facebook, YouTube…) */
	$texte = isset( $noms[ $hote ] ) ? sprintf( __( 'Voir sur %s', 'er-librairie' ), $noms[ $hote ] ) : __( 'Voir la vidéo', 'er-librairie' );
	return '<p class="er-evt-lien-video"><a href="' . esc_url( $url ) . '" rel="noopener">' . esc_html( $texte ) . ' ↗</a></p>';
}

/**
 * « Revoir, réécouter » : les vidéos et les enregistrements de la rencontre.
 *
 * @param int $id La rencontre.
 */
function er_evt_bloc_videos( $id ) {
	$videos = ER_EVT === get_post_type( $id ) ? er_evt_videos( $id ) : array();
	if ( ! $videos ) {
		return '';
	}
	$titre = er_evt_passee( $id ) ? __( 'Revoir, réécouter', 'er-librairie' ) : __( 'À voir, à écouter', 'er-librairie' );
	$html  = '<section class="er-evt-revoir" id="revoir"><h2 class="er-evt-section">' . esc_html( $titre ) . '</h2>';
	foreach ( $videos as list( $legende, $url ) ) {
		$html .= '<figure class="er-evt-media">' . er_evt_lecteur( $url ) . ( $legende ? '<figcaption>' . esc_html( $legende ) . '</figcaption>' : '' ) . '</figure>';
	}
	return $html . '</section>';
}

/**
 * Les livres de la rencontre : ceux qu'on a liés, puis ceux des intervenants qui sont aussi des auteurs (en stock).
 *
 * @param int $id La rencontre.
 */
function er_evt_bloc_livres( $id ) {
	if ( ER_EVT !== get_post_type( $id ) || ! function_exists( 'wc_get_product' ) ) {
		return '';
	}
	$ids = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'er_livres', true ) ) ) ) as $ref ) {
		$trouve = ctype_digit( $ref ) ? (int) $ref : wc_get_product_id_by_sku( $ref );
		if ( $trouve ) {
			$ids[] = $trouve;
		}
	}
	$auteurs = array();
	foreach ( wp_get_post_terms( $id, 'er_intervenant' ) as $t ) {
		$auteur = er_evt_personne( $t );
		if ( $auteur ) {
			$auteurs[] = $auteur->term_id;
		}
	}
	if ( $auteurs && count( $ids ) < 6 ) {
		$ids = array_merge(
			$ids,
			get_posts(
				array(
					'post_type'   => 'product',
					'numberposts' => 6,
					'fields'      => 'ids',
					'tax_query'   => array( array( 'taxonomy' => 'auteur', 'terms' => $auteurs ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_query'  => array( array( 'key' => '_stock_status', 'value' => 'instock' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			)
		);
	}
	$html = '';
	foreach ( array_slice( array_unique( $ids ), 0, 6 ) as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p || 'publish' !== $p->get_status() || $p->get_meta( 'er_evenement' ) ) {
			continue;
		}
		$auteurs_du_livre = wp_get_post_terms( $pid, 'auteur', array( 'fields' => 'names' ) );
		$html            .= '<li class="er-evt-livre"><a href="' . esc_url( get_permalink( $pid ) ) . '">'
			. '<span class="er-evt-livre-couverture">' . $p->get_image( 'woocommerce_thumbnail' ) . '</span>'
			. '<span class="er-evt-livre-auteur">' . esc_html( implode( ', ', is_wp_error( $auteurs_du_livre ) ? array() : $auteurs_du_livre ) ) . '</span>'
			. '<span class="er-evt-livre-titre">' . esc_html( $p->get_name() ) . '</span></a>'
			. '<span class="er-evt-livre-prix">' . ( $p->is_in_stock() ? esc_html( html_entity_decode( wp_strip_all_tags( wc_price( wc_get_price_to_display( $p ) ) ), ENT_QUOTES, 'UTF-8' ) ) : esc_html__( 'épuisé pour l’instant', 'er-librairie' ) ) . '</span></li>';
	}
	return $html ? '<section class="er-evt-livres"><h2 class="er-evt-section">' . esc_html__( 'À la librairie', 'er-librairie' ) . '</h2><ul>' . $html . '</ul></section>' : '';
}

/**
 * « Toutes les rencontres », au bas d'une rencontre.
 */
function er_evt_bloc_retour() {
	return '<p class="er-lien-fleche er-evt-retour"><a href="' . esc_url( er_evt_page_liste() ) . '">' . esc_html__( 'Toutes les rencontres', 'er-librairie' ) . '</a></p>';
}

/* La liste de « Nos événements » ---------------------------------------------------------------------------------- */

/**
 * Les années qui ont des rencontres passées, de la plus récente à la plus ancienne.
 */
function er_evt_annees() {
	$annees = get_transient( 'er_evt_annees' );
	if ( false === $annees ) {
		global $wpdb;
		$annees = $wpdb->get_col( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT DISTINCT LEFT(m.meta_value, 4) AS a FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id
				WHERE m.meta_key = 'er_debut' AND m.meta_value <> '' AND p.post_type = %s AND p.post_status = 'publish' AND m.meta_value < %s ORDER BY a DESC",
				ER_EVT,
				( new DateTimeImmutable( 'now', er_evt_fuseau() ) )->format( 'Y-m-d H:i' )
			)
		);
		set_transient( 'er_evt_annees', $annees, DAY_IN_SECONDS );
	}
	return $annees;
}

/**
 * Les rencontres, du plus proche au plus loin (à venir) ou l'inverse (passées).
 *
 * @param bool  $a_venir  À venir, sinon passées.
 * @param array $criteres annee, cycle, video.
 */
function er_evt_requete( $a_venir, $criteres = array() ) {
	// Une rencontre commencée depuis moins de deux heures est encore « à venir » (comme er_evt_passee).
	$maintenant = ( new DateTimeImmutable( 'now', er_evt_fuseau() ) )->modify( '-2 hours' )->format( 'Y-m-d H:i' );
	$meta       = array(
		'relation' => 'AND',
		'debut'    => array(
			'key'     => 'er_debut',
			'value'   => $maintenant,
			'compare' => $a_venir ? '>=' : '<',
		),
		array(
			'key'     => 'er_debut',
			'value'   => '',
			'compare' => '!=', // Une rencontre sans date (annulée avant d'être fixée) ne paraît pas.
		),
	);
	if ( ! empty( $criteres['annee'] ) ) {
		$meta[] = array(
			'key'     => 'er_debut',
			'value'   => $criteres['annee'] . '-',
			'compare' => 'LIKE',
		);
	}
	if ( ! empty( $criteres['video'] ) ) {
		$meta[] = array(
			'key'     => 'er_videos',
			'value'   => 'http',
			'compare' => 'LIKE',
		);
	}
	$args = array(
		'post_type'      => ER_EVT,
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'no_found_rows'  => true,
		'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => array( 'debut' => $a_venir ? 'ASC' : 'DESC' ),
	);
	if ( ! empty( $criteres['cycle'] ) ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'er_cycle', 'field' => 'slug', 'terms' => $criteres['cycle'] ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$q = new WP_Query( $args );
	update_post_thumbnail_cache( $q );
	return $q->posts;
}

/**
 * Une rencontre à venir, en ligne : la date, l'affiche, le titre, l'entrée.
 *
 * @param WP_Post $p La rencontre.
 */
function er_evt_ligne( $p ) {
	$debut = er_evt_date( $p->ID );
	$etat  = (string) get_post_meta( $p->ID, 'er_etat', true );
	$date  = $debut
		? '<span class="er-evt-jour">' . esc_html( $debut->format( 'j' ) ) . '</span><span class="er-evt-mois">' . esc_html( wp_date( 'M', $debut->getTimestamp(), er_evt_fuseau() ) ) . '</span><span class="er-evt-heure">' . esc_html( '00:00' === $debut->format( 'H:i' ) ? '' : wp_date( 'D', $debut->getTimestamp(), er_evt_fuseau() ) . ' ' . er_evt_heure( $debut ) ) . '</span>'
		: '';
	$surtitre = wp_strip_all_tags( er_evt_bloc_surtitre( $p->ID ) );
	$entree   = '';
	if ( 'annule' === $etat ) {
		$entree = '<span class="er-evt-marque er-evt-marque-annule">' . esc_html__( 'Annulée', 'er-librairie' ) . '</span>';
	} elseif ( 'complet' === $etat ) {
		$entree = '<span class="er-evt-marque">' . esc_html__( 'Complet', 'er-librairie' ) . '</span>';
	} else {
		$inscription = (string) get_post_meta( $p->ID, 'er_inscription', true );
		$prix        = (string) get_post_meta( $p->ID, 'er_prix', true );
		if ( 'billetterie' === $inscription && '' !== $prix ) {
			$entree = '<span class="er-evt-prix">' . esc_html( er_evt_prix( $prix ) ) . '</span>';
		} elseif ( in_array( $inscription, array( 'gratuite', 'libre' ), true ) ) {
			$entree = '<span class="er-evt-prix">' . esc_html__( 'Entrée libre', 'er-librairie' ) . '</span>';
		}
		$entree .= '<span class="er-evt-lien">' . esc_html( 'billetterie' === $inscription ? __( 'Réserver', 'er-librairie' ) : __( 'Voir', 'er-librairie' ) ) . '</span>';
	}
	return '<li class="er-evt-ligne' . ( 'annule' === $etat ? ' er-evt-annulee' : '' ) . '"><a href="' . esc_url( get_permalink( $p ) ) . '">'
		. '<span class="er-evt-date">' . $date . '</span>'
		. '<span class="er-evt-vignette">' . get_the_post_thumbnail( $p, 'medium', array( 'alt' => '' ) ) . '</span>'
		. '<span class="er-evt-texte">' . ( $surtitre ? '<span class="er-evt-ligne-surtitre">' . esc_html( $surtitre ) . '</span>' : '' )
		. '<span class="er-evt-ligne-titre">' . esc_html( get_the_title( $p ) ) . '</span>'
		. ( er_evt_lieu( $p->ID ) ? '<span class="er-evt-ligne-lieu">' . esc_html( er_evt_lieu( $p->ID ) ) . '</span>' : '' ) . '</span>'
		. '<span class="er-evt-entree">' . $entree . '</span>'
		. '</a></li>';
}

/**
 * Une rencontre passée, en carte : l'affiche, la date, le titre, et « vidéo » ou « annulée ».
 *
 * @param WP_Post $p La rencontre.
 */
function er_evt_carte( $p ) {
	$annulee = 'annule' === get_post_meta( $p->ID, 'er_etat', true );
	$video   = (bool) er_evt_videos( $p->ID );
	$marques = ( $video ? '<span class="er-evt-marque er-evt-marque-video">' . esc_html__( 'Vidéo', 'er-librairie' ) . '</span>' : '' )
		. ( $annulee ? '<span class="er-evt-marque er-evt-marque-annule">' . esc_html__( 'Annulée', 'er-librairie' ) . '</span>' : '' );
	$image   = get_the_post_thumbnail( $p, 'medium_large', array( 'alt' => '', 'loading' => 'lazy' ) );
	return '<li class="er-evt-carte' . ( $annulee ? ' er-evt-annulee' : '' ) . '"><a href="' . esc_url( get_permalink( $p ) ) . '">'
		. '<span class="er-evt-affiche">' . ( $image ? $image : '<span class="er-evt-affiche-vide" aria-hidden="true" lang="ru">Встреча</span>' ) . '</span>'
		. '<span class="er-evt-carte-date">' . esc_html( er_evt_quand( $p->ID, false ) ) . '</span>'
		. '<span class="er-evt-carte-titre">' . esc_html( get_the_title( $p ) ) . '</span>'
		. ( $marques ? '<span class="er-evt-marques">' . $marques . '</span>' : '' )
		. '</a></li>';
}

/**
 * La bande de la page « Nos événements » (la bande de page du thème, er_bande_page()) : à venir ; les années passées,
 * les quatre dernières dépliées, les autres sous « Plus anciennes » ; avec une vidéo ; les cycles, repliés.
 *
 * @param array $criteres annee, cycle, video : ce que la page montre.
 */
function er_evt_bande( $criteres ) {
	if ( ! function_exists( 'er_bande_page' ) ) {
		return '';
	}
	$choisi  = $criteres['annee'] || $criteres['cycle'] || $criteres['video'];
	$entrees = array(
		array(
			'texte'   => __( 'À venir', 'er-librairie' ),
			'lien'    => er_evt_page_liste() . '#a-venir',
			'courant' => ! $choisi,
		),
	);
	$annees = er_evt_annees();
	if ( $annees ) {
		$entrees[] = array( 'separateur' => true );
		$entrees[] = array( 'groupe' => __( 'Passées', 'er-librairie' ) );
		$entrees   = array_merge(
			$entrees,
			er_bande_annees(
				$annees,
				fn( $a ) => er_evt_page_liste( array( 'annee' => $a ) ) . '#archive',
				$criteres['cycle'] || $criteres['video'] ? '' : $criteres['annee'],
				__( 'Plus anciennes', 'er-librairie' )
			)
		);
		$entrees[] = array( 'separateur' => true );
		$entrees[] = array(
			'texte'   => __( 'Avec une vidéo', 'er-librairie' ),
			'lien'    => er_evt_page_liste( array( 'video' => '1' ) ) . '#archive',
			'courant' => (bool) $criteres['video'],
		);
		$cycles = get_terms(
			array(
				'taxonomy'   => 'er_cycle',
				'hide_empty' => true,
			)
		);
		if ( $cycles && ! is_wp_error( $cycles ) ) {
			$entrees[] = array(
				'menu'    => __( 'Les cycles', 'er-librairie' ),
				'entrees' => array_map(
					fn( $c ) => array(
						'texte'   => $c->name,
						'lien'    => er_evt_page_liste( array( 'cycle' => $c->slug ) ) . '#archive',
						'courant' => $c->slug === $criteres['cycle'],
					),
					$cycles
				),
			);
		}
	}
	$page = get_page_by_path( 'centre-culturel/evenements' );
	return er_bande_page(
		array(
			'maison'  => 'centre',
			'nom'     => $page ? html_entity_decode( get_the_title( $page ), ENT_QUOTES, 'UTF-8' ) : __( 'Nos événements', 'er-librairie' ),
			'entrees' => $entrees,
			'libelle' => __( 'Les rencontres du centre', 'er-librairie' ),
		)
	);
}

/**
 * La liste de la page « Nos événements » : les rencontres à venir (cette semaine, la semaine prochaine, plus tard),
 * puis les rencontres passées, par année, avec un cycle ou les vidéos seulement.
 */
function er_evt_bloc_liste() {
	// phpcs:disable WordPress.Security.NonceVerification
	$criteres = array(
		'annee' => isset( $_GET['annee'] ) ? preg_replace( '/\D/', '', wp_unslash( $_GET['annee'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		'cycle' => isset( $_GET['cycle'] ) ? sanitize_title( wp_unslash( $_GET['cycle'] ) ) : '',
		'video' => ! empty( $_GET['video'] ) ? '1' : '',
	);
	// phpcs:enable
	$html = er_evt_bande( $criteres ) . '<div class="er-evts alignwide">';

	// À venir.
	$a_venir = er_evt_requete( true );
	$html   .= '<section class="er-evts-section er-evts-a-venir" id="a-venir"><h2 class="er-evts-titre">' . esc_html__( 'À venir', 'er-librairie' ) . '</h2>';
	if ( $a_venir ) {
		$maintenant = new DateTimeImmutable( 'now', er_evt_fuseau() );
		$dimanche   = $maintenant->modify( 'sunday this week' )->setTime( 23, 59 );
		$groupes    = array(
			__( 'Cette semaine', 'er-librairie' )          => array(),
			__( 'La semaine prochaine', 'er-librairie' )   => array(),
			__( 'Plus tard', 'er-librairie' )              => array(),
		);
		$noms       = array_keys( $groupes );
		foreach ( $a_venir as $p ) {
			$d = er_evt_date( $p->ID );
			$i = ! $d || $d > $dimanche->modify( '+7 days' ) ? 2 : ( $d > $dimanche ? 1 : 0 );
			$groupes[ $noms[ $i ] ][] = $p;
		}
		foreach ( $groupes as $nom => $liste ) {
			if ( ! $liste ) {
				continue;
			}
			$html .= '<h3 class="er-evts-groupe">' . esc_html( $nom ) . '</h3><ol class="er-evts-lignes">' . implode( '', array_map( 'er_evt_ligne', $liste ) ) . '</ol>';
		}
	} else {
		$html .= '<p class="er-evts-vide">' . esc_html__( 'Pas de rencontre annoncée pour l’instant : les prochaines paraîtront ici.', 'er-librairie' ) . '</p>';
	}
	$html .= '</section>';

	// L'archive.
	$annees = er_evt_annees();
	if ( ! $annees ) {
		return $html . '</div>';
	}
	if ( ! $criteres['annee'] && ! $criteres['cycle'] && ! $criteres['video'] ) {
		$criteres['annee'] = $annees[0];
	}
	$passees = er_evt_requete( false, $criteres );
	// Ce que montre l'archive, choisi dans la bande : une année, un cycle, les vidéos.
	$quoi = $criteres['annee'];
	if ( $criteres['cycle'] ) {
		$cycle = get_term_by( 'slug', $criteres['cycle'], 'er_cycle' );
		$quoi  = $cycle ? $cycle->name : '';
	} elseif ( $criteres['video'] ) {
		$quoi = __( 'avec une vidéo', 'er-librairie' );
	}
	$html .= '<section class="er-evts-section er-evts-archive" id="archive"><h2 class="er-evts-titre">' . esc_html__( 'Les rencontres passées', 'er-librairie' ) . ( $quoi ? ' · ' . esc_html( $quoi ) : '' ) . '</h2>';
	/* translators: %d: le nombre de rencontres */
	$html .= '<p class="er-evts-compte">' . esc_html( sprintf( _n( '%d rencontre', '%d rencontres', count( $passees ), 'er-librairie' ), count( $passees ) ) ) . '</p>';
	$html .= $passees ? '<ul class="er-evts-grille">' . implode( '', array_map( 'er_evt_carte', $passees ) ) . '</ul>' : '';
	return $html . '</section></div>';
}

/* L'import de l'ancien site ---------------------------------------------------------------------------------------
 *
 * wp er evenements importer <fichier.json> : le fichier que fait outils/wix/evenements.py. Une rencontre se
 * reconnaît à son identifiant Wix : relancé, l'import met à jour sans rien créer en double. Un titre, un texte ou un
 * chapô modifiés à la main depuis le dernier import ne sont plus touchés ; les vidéos s'ajoutent à celles qu'on a
 * déjà, sans en retirer. L'affiche est téléchargée une fois.
 */

/**
 * Les auteurs de la librairie, par leur clé de recherche : de quoi reconnaître un intervenant dans un titre.
 */
function er_evt_auteurs_connus() {
	$auteurs = array();
	$termes  = get_terms(
		array(
			'taxonomy'   => 'auteur',
			'hide_empty' => false,
			'lang'       => '',
		)
	);
	foreach ( is_wp_error( $termes ) ? array() : $termes as $t ) {
		$noms = array_merge( array( $t->name ), er_autres_graphies( $t->term_id ) );
		foreach ( $noms as $nom ) {
			$cle = er_cle_recherche( $nom );
			if ( substr_count( $cle, ' ' ) >= 1 && strlen( $cle ) >= 8 ) {
				$auteurs[ $cle ] = array( $t, $noms );
			}
		}
	}
	return $auteurs;
}

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
	foreach ( er_auteurs_cites( $e['titre'] . ' ' . $e['chapo'], $auteurs ) as list( $t, $noms, $intervient ) ) {
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

/**
 * En face du fil de la tête de page « Nos événements » (le thème, er_tete_page()) : combien de rencontres, depuis
 * quand.
 */
add_filter(
	'er_tete_faits',
	function ( $faits, $page ) {
		if ( '' !== $faits || ! $page instanceof WP_Post || ! has_block( 'er/evenements', $page ) ) {
			return $faits;
		}
		$n      = (int) wp_count_posts( ER_EVT )->publish;
		$annees = er_evt_annees();
		if ( ! $n ) {
			return $faits;
		}
		/* translators: %d: un nombre de rencontres */
		$texte = sprintf( _n( '%d rencontre', '%d rencontres', $n, 'er-librairie' ), $n );
		/* translators: 1: un nombre de rencontres (« 169 rencontres »), 2: une année */
		return $annees ? sprintf( __( '%1$s depuis %2$s', 'er-librairie' ), $texte, end( $annees ) ) : $texte;
	},
	10,
	2
);
