<?php
/**
 * L'index de l'accueil : le premier module de la page d'accueil (patterns/une-index.php).
 *
 * Retenu le 25 novembre 2026 (canevas « Une nouvelle page d'accueil, aérée », planche D1 : « code ce module à la
 * place du premier module de l'accueil », André). Deux colonnes : à gauche, les titres de ce que publient les trois
 * maisons, l'un sous l'autre, sobres ; à droite, le livre ou la photo de la ligne en cours, qui reste en place pendant
 * qu'on fait défiler la page (assets/js/une-index.js), avec en bas une seule ligne : ce que la gauche ne dit pas (un
 * éditeur et une langue, une entrée, une durée, une signature), le prix s'il y en a un, un bouton.
 *
 * Tout se lit dans le site à chaque affichage, rien n'est recopié : la prochaine rencontre et les deux
 * suivantes, la dernière parution d'YMCA-Press (de l'année ou de la précédente), le dernier podcast, les sept
 * dernières nouveautés de la librairie (la mention « nouveauté ») ; puis, mois par mois, les rencontres passées, les
 * vidéos et les articles du journal, les dix plus récents, les plus anciens sous « Plus tôt ».
 *
 * Au-dessus de chaque titre, seulement la date, ou « Nouveauté », « Parution », « Podcast » (25 nov. : « enlève
 * rencontre, remplace arrivage par nouveauté ») ; le genre se comprend au bouton (« Réserver ma place », « Lire »…).
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le jour, en court : « 16 nov. » (en français) ; ailleurs, le format court de la langue.
 *
 * @param int $ts Le moment.
 */
function er_index_jour( $ts ) {
	$fuseau = new DateTimeZone( 'Europe/Paris' );
	if ( 'fr' !== substr( determine_locale(), 0, 2 ) ) {
		return wp_date( _x( 'j M', 'date d’une ligne de l’index de l’accueil', 'editeurs-reunis' ), $ts, $fuseau );
	}
	$mois = array( 1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.' );
	$d    = ( new DateTimeImmutable( '@' . $ts ) )->setTimezone( $fuseau );
	$j    = (int) $d->format( 'j' );
	return ( 1 === $j ? '1er' : $j ) . "\u{00A0}" . $mois[ (int) $d->format( 'n' ) ];
}

/**
 * Comment montrer une image : « livre » (posée au centre, avec une ombre) pour ce qui est plus haut que large, une
 * couverture ou une affiche ; « photo » (de bord à bord) sinon.
 *
 * @param int $image L'image (pièce jointe).
 */
function er_index_vue( $image ) {
	$src = $image ? wp_get_attachment_image_src( $image, 'medium' ) : false;
	if ( ! $src ) {
		return 'aucune';
	}
	return (int) $src[2] > 1.15 * (int) $src[1] ? 'livre' : 'photo';
}

/**
 * Une ligne de l'index, avec ce qu'il faut par défaut.
 *
 * @param array $ligne Les champs.
 */
function er_index_ligne( $ligne ) {
	return $ligne + array(
		'maison'      => 'centre',
		'quand'       => '',
		'ts'          => 0,
		'titre'       => '',
		'lien'        => '',
		'image'       => 0,
		'vue'         => 'aucune',
		'produit'     => null, // Pour la couverture typographique.
		'legende'     => array(),
		'prix'        => '',
		'action'      => '',
		'action_lien' => '',
		'grand'       => false,
	);
}

/**
 * Un livre de la librairie : son éditeur et sa langue, « Voir le livre » (ni prix ni panier sur l'accueil, 28 nov. 2026 :
 * plus sérieux, moins commerce).
 *
 * @param WC_Product $produit Le livre.
 * @param string     $quand   « Nouveauté » ou « Parution ».
 */
function er_index_livre( $produit, $quand ) {
	$id      = $produit->get_id();
	$ed      = function_exists( 'er_editeur_du_livre' ) ? er_editeur_du_livre( $id ) : array( 'termes' => array(), 'texte' => (string) $produit->get_meta( 'er_editeur' ) );
	$editeur = $ed['termes'] ? $ed['termes'][0]->name : trim( wp_strip_all_tags( $ed['texte'] ) );
	$ymca    = (bool) preg_match( '/ymca|имка/iu', $editeur );
	$fr      = 'fr' === substr( determine_locale(), 0, 2 );
	$langues = wp_get_post_terms( $id, 'langue', array( 'fields' => 'names' ) );
	$langue  = '';
	if ( $langues && ! is_wp_error( $langues ) ) {
		$langue = mb_strtolower( $langues[0], 'UTF-8' );
		$langue = $fr && false === strpos( $langue, ' ' ) && false === strpos( $langue, 'bilingue' ) ? 'en ' . $langue : $langue;
	}
	$legende = array_values( array_filter( array( esc_html( $editeur ), esc_html( $langue ) ) ) );
	if ( $legende && ! $editeur ) {
		$legende[0] = esc_html( mb_strtoupper( mb_substr( $langue, 0, 1 ) ) . mb_substr( $langue, 1 ) );
	}
	$titre   = wptexturize( $produit->get_name() );
	$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
	$auteurs = is_array( $auteurs ) ? array_slice( $auteurs, 0, 2 ) : array();
	$ru      = preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '';
	$prix    = ''; // L'accueil n'est pas la librairie : pas de prix, et la fiche plutôt que le panier (28 nov. 2026).
	$image = (int) $produit->get_image_id();
	$typo  = function_exists( 'er_couverture_a_remplacer' ) && er_couverture_a_remplacer( $produit );
	return er_index_ligne(
		array(
			'maison'      => $ymca ? 'ymca' : 'librairie',
			'quand'       => $quand,
			'ts'          => (int) get_post_time( 'U', true, $id ),
			'titre'       => '<em' . $ru . '>' . esc_html( $titre ) . '</em>' . ( $auteurs ? ', ' . esc_html( implode( ', ', $auteurs ) ) : '' ),
			'lien'        => get_permalink( $id ),
			'image'       => $typo ? 0 : $image,
			'vue'         => $typo || ! $image ? 'typo' : 'livre',
			'produit'     => $produit,
			'legende'     => $legende,
			'prix'        => $prix,
			'action'      => __( 'Voir le livre', 'editeurs-reunis' ),
			'action_lien' => get_permalink( $id ),
		)
	);
}

/**
 * Une rencontre : à venir, son entrée et sa langue, « Réserver ma place » ; passée, sa langue, « Revoir » si elle a
 * un enregistrement. Sans affiche, la couverture du premier livre lié, dite en bas.
 *
 * @param WP_Post $p       La rencontre.
 * @param bool    $a_venir À venir.
 */
function er_index_rencontre( $p, $a_venir ) {
	$id    = $p->ID;
	$debut = function_exists( 'er_evt_date' ) ? er_evt_date( $id ) : null;
	$etat  = (string) get_post_meta( $id, 'er_etat', true );
	$image = (int) get_post_thumbnail_id( $id );
	$parts = array();
	if ( ! $image && function_exists( 'wc_get_product' ) ) {
		foreach ( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'er_livres', true ) ) ) ) as $ref ) {
			$livre = wc_get_product( ctype_digit( $ref ) ? (int) $ref : wc_get_product_id_by_sku( $ref ) );
			if ( $livre && 'publish' === $livre->get_status() && $livre->get_image_id() ) {
				$image = (int) $livre->get_image_id();
				$ed    = function_exists( 'er_editeur_du_livre' ) ? er_editeur_du_livre( $livre->get_id() ) : array( 'termes' => array(), 'texte' => '' );
				$ed    = $ed['termes'] ? $ed['termes'][0]->name : trim( wp_strip_all_tags( $ed['texte'] ) );
				/* translators: %s: le titre du livre lié à la rencontre (en italique). */
				$parts[] = sprintf( __( 'Le livre : %s', 'editeurs-reunis' ), '<em>' . esc_html( wptexturize( $livre->get_name() ) ) . '</em>' . ( $ed ? ' (' . esc_html( $ed ) . ')' : '' ) );
				break;
			}
		}
	}
	$prix   = '';
	$action = __( 'La rencontre', 'editeurs-reunis' );
	$lien   = get_permalink( $id );
	if ( 'annule' === $etat ) {
		$parts[] = esc_html__( 'Annulée', 'editeurs-reunis' );
	} elseif ( $a_venir ) {
		$inscription = (string) get_post_meta( $id, 'er_inscription', true );
		$billet      = function_exists( 'er_evt_billet' ) ? er_evt_billet( $id ) : null;
		if ( 'complet' === $etat || ( $billet && ! $billet->is_in_stock() ) ) {
			$parts[] = esc_html__( 'Complet', 'editeurs-reunis' );
		} elseif ( 'billetterie' === $inscription ) {
			$tarif = (string) get_post_meta( $id, 'er_prix', true );
			$prix  = '' !== $tarif && function_exists( 'er_evt_prix' ) ? er_evt_prix( $tarif ) : '';
		} elseif ( 'gratuite' === $inscription ) {
			$parts[] = esc_html__( 'Entrée libre, sur inscription', 'editeurs-reunis' );
		} elseif ( 'libre' === $inscription ) {
			$parts[] = esc_html__( 'Entrée libre', 'editeurs-reunis' );
		}
		if ( 'complet' !== $etat && $billet && $billet->is_purchasable() && $billet->is_in_stock() ) {
			$action = 'gratuite' === $inscription ? __( 'M’inscrire', 'editeurs-reunis' ) : __( 'Réserver ma place', 'editeurs-reunis' );
		}
	} elseif ( function_exists( 'er_evt_videos' ) && er_evt_videos( $id ) ) {
		$action = __( 'Revoir', 'editeurs-reunis' );
		$lien  .= '#revoir';
	}
	$langue = function_exists( 'er_evt_langue' ) ? er_evt_langue( $id ) : '';
	if ( $langue ) {
		$parts[] = esc_html( $parts ? mb_strtolower( mb_substr( $langue, 0, 1 ) ) . mb_substr( $langue, 1 ) : $langue );
	}
	$titre = wptexturize( get_the_title( $id ) );
	return er_index_ligne(
		array(
			'maison'      => 'centre',
			'quand'       => $a_venir && function_exists( 'er_info_quand_court' ) ? er_info_quand_court( $id ) : ( $debut ? er_index_jour( $debut->getTimestamp() ) : '' ),
			'ts'          => $debut ? $debut->getTimestamp() : (int) get_post_time( 'U', true, $id ),
			'titre'       => '<span' . ( preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '' ) . '>' . esc_html( $titre ) . '</span>',
			'lien'        => get_permalink( $id ),
			'image'       => $image,
			'vue'         => er_index_vue( $image ),
			'legende'     => $parts,
			'prix'        => $prix,
			'action'      => $action,
			'action_lien' => $lien,
		)
	);
}

/**
 * Une vidéo ou un podcast du centre : sa série et sa durée, « Regarder » ou « Écouter ».
 *
 * @param WP_Post $p     Le contenu.
 * @param string  $quand « Podcast », ou vide (la date).
 */
function er_index_media( $p, $quand = '' ) {
	$id      = $p->ID;
	$podcast = 'podcast' === get_post_meta( $id, 'er_genre', true );
	$parts   = array();
	$series  = wp_get_post_terms( $id, 'er_serie', array( 'fields' => 'names' ) );
	if ( $series && ! is_wp_error( $series ) ) {
		$parts[] = esc_html( $series[0] );
	}
	$duree = (int) get_post_meta( $id, 'er_duree', true );
	if ( $duree ) {
		/* translators: %d: une durée, en minutes */
		$parts[] = esc_html( sprintf( __( '%d min', 'editeurs-reunis' ), $duree ) );
	}
	$image = (int) get_post_thumbnail_id( $id );
	$titre = wptexturize( get_the_title( $id ) );
	$ts    = (int) get_post_time( 'U', true, $id );
	return er_index_ligne(
		array(
			'quand'       => '' !== $quand ? $quand : er_index_jour( $ts ),
			'ts'          => $ts,
			'titre'       => '<span' . ( preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '' ) . '>' . esc_html( $titre ) . '</span>',
			'lien'        => get_permalink( $id ),
			'image'       => $image,
			'vue'         => er_index_vue( $image ),
			'legende'     => $parts,
			'action'      => $podcast ? __( 'Écouter', 'editeurs-reunis' ) : __( 'Regarder', 'editeurs-reunis' ),
			'action_lien' => get_permalink( $id ),
		)
	);
}

/**
 * Un article du journal : son genre, sa signature, son temps de lecture, « Lire ».
 *
 * @param WP_Post $p L'article.
 */
function er_index_article( $p ) {
	$id    = $p->ID;
	$parts = array();
	$genre = function_exists( 'er_journal_genre' ) ? er_journal_genre( $id ) : null;
	if ( $genre ) {
		$parts[] = esc_html( $genre->name );
	}
	$signature = trim( (string) get_post_meta( $id, 'er_signature', true ) );
	if ( $signature ) {
		/* translators: %s: le nom de qui signe l'article */
		$parts[] = esc_html( sprintf( $parts ? __( 'par %s', 'editeurs-reunis' ) : __( 'Par %s', 'editeurs-reunis' ), $signature ) );
	}
	if ( function_exists( 'er_journal_lecture' ) ) {
		$min = er_journal_lecture( $id );
		/* translators: %d: des minutes */
		$parts[] = esc_html( sprintf( _n( '%d min de lecture', '%d min de lecture', $min, 'editeurs-reunis' ), $min ) );
	}
	$image = (int) get_post_thumbnail_id( $id );
	$titre = wptexturize( get_the_title( $id ) );
	$ts    = (int) get_post_time( 'U', true, $id );
	return er_index_ligne(
		array(
			'quand'       => er_index_jour( $ts ),
			'ts'          => $ts,
			'titre'       => '<span' . ( preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '' ) . '>' . esc_html( $titre ) . '</span>',
			'lien'        => get_permalink( $id ),
			'image'       => $image,
			'vue'         => er_index_vue( $image ),
			'legende'     => $parts,
			'action'      => __( 'Lire', 'editeurs-reunis' ),
			'action_lien' => get_permalink( $id ),
		)
	);
}

/**
 * Les livres de la librairie montrés en vitrine (ni billets de rencontre, ni livres cachés), selon d'autres critères.
 *
 * @param array $args Ce qui s'ajoute à la requête.
 * @return WC_Product[]
 */
function er_index_produits( $args ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$tax = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
	unset( $args['tax_query'] );
	$q = new WP_Query(
		$args + array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'tax_query'           => array_merge( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'slug',
						'terms'    => array( 'exclude-from-catalog' ),
						'operator' => 'NOT IN',
					),
				),
				$tax
			),
		)
	);
	return array_values( array_filter( array_map( 'wc_get_product', $q->posts ) ) );
}

/**
 * Les rubriques de l'index : [ titre, lignes ]. La première, « À la une » : ce qui vient, ce qui vient d'arriver.
 */
function er_index_rubriques() {
	$haut       = array();
	$maintenant = ( new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Paris' ) ) )->modify( '-2 hours' )->format( 'Y-m-d H:i' );
	$rencontres = function ( $a_venir, $nombre ) use ( $maintenant ) {
		$q = new WP_Query(
			array(
				'post_type'      => 'er_evenement',
				'post_status'    => 'publish',
				'posts_per_page' => $nombre,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'debut' => array(
						'key'     => 'er_debut',
						'value'   => $maintenant,
						'compare' => $a_venir ? '>=' : '<',
					),
					array(
						'key'     => 'er_debut',
						'value'   => '',
						'compare' => '!=',
					),
				),
				'orderby'        => array( 'debut' => $a_venir ? 'ASC' : 'DESC' ),
			)
		);
		return $q->posts;
	};

	// La prochaine rencontre et les deux suivantes.
	foreach ( $rencontres( true, 3 ) as $rang => $p ) {
		$haut[] = array( 'grand' => 0 === $rang ) + er_index_rencontre( $p, true );
	}

	// La dernière parution d'YMCA-Press, de l'année ou de la précédente.
	$annee  = (int) wp_date( 'Y' );
	$ymca   = er_index_produits(
		array(
			'posts_per_page' => 1,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'er_editeur',
					'field'    => 'slug',
					'terms'    => array( 'ymca-press' ),
				),
			),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'annee' => array(
					'key'     => 'er_annee',
					'value'   => array( (string) ( $annee - 1 ), (string) $annee ),
					'compare' => 'IN',
				),
			),
			'orderby'        => array(
				'annee' => 'DESC',
				'date'  => 'DESC',
			),
		)
	);
	if ( ! $ymca ) { // L'éditeur écrit en clair, pour une fiche qui n'a pas encore son classement.
		$ymca = er_index_produits(
			array(
				'posts_per_page' => 1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'AND',
					'annee'    => array(
						'key'     => 'er_annee',
						'value'   => array( (string) ( $annee - 1 ), (string) $annee ),
						'compare' => 'IN',
					),
					array(
						'key'     => 'er_editeur',
						'value'   => 'YMCA',
						'compare' => 'LIKE',
					),
				),
				'orderby'        => array(
					'annee' => 'DESC',
					'date'  => 'DESC',
				),
			)
		);
	}
	$vus    = array();
	foreach ( $ymca as $produit ) {
		$haut[] = er_index_livre( $produit, __( 'Parution', 'editeurs-reunis' ) );
		$vus[]  = $produit->get_id();
	}

	// Le dernier podcast.
	$podcasts = new WP_Query(
		array(
			'post_type'      => 'er_media',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => 'er_genre', 'value' => 'podcast' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $podcasts->posts as $p ) {
		$haut[] = er_index_media( $p, __( 'Podcast', 'editeurs-reunis' ) );
	}

	// Les sept dernières nouveautés de la librairie.
	foreach (
		er_index_produits(
			array(
				'posts_per_page' => 7,
				'post__not_in'   => $vus, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
				'orderby'        => 'date',
				'order'          => 'DESC',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => 'product_tag',
						'field'    => 'slug',
						'terms'    => array( 'nouveaute' ),
					),
				),
			)
		) as $produit
	) {
		$haut[] = er_index_livre( $produit, __( 'Nouveauté', 'editeurs-reunis' ) );
	}

	// Ce qui s'est passé : les rencontres, les vidéos, les articles, les dix plus récents, mois par mois.
	$passe = array();
	foreach ( $rencontres( false, 8 ) as $p ) {
		$passe[] = er_index_rencontre( $p, false );
	}
	$videos = new WP_Query(
		array(
			'post_type'      => 'er_media',
			'post_status'    => 'publish',
			'posts_per_page' => 4,
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => 'er_genre', 'value' => 'video' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $videos->posts as $p ) {
		$passe[] = er_index_media( $p );
	}
	$articles = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	foreach ( $articles->posts as $p ) {
		$passe[] = er_index_article( $p );
	}
	usort( $passe, fn( $a, $b ) => $b['ts'] <=> $a['ts'] );
	$passe = array_slice( $passe, 0, 10 );

	// La première rubrique a son titre, « À la une », sur un trait noir comme les mois qui suivent (Y3b, 26 nov.).
	$rubriques = $haut ? array( array( __( 'À la une', 'editeurs-reunis' ), $haut ) ) : array();
	$fuseau    = new DateTimeZone( 'Europe/Paris' );
	$ce_mois   = new DateTimeImmutable( 'first day of this month 00:00', $fuseau );
	$limite    = $ce_mois->modify( '-2 months' )->getTimestamp();
	$plus_tot  = __( 'Plus tôt', 'editeurs-reunis' );
	foreach ( $passe as $ligne ) {
		if ( $ligne['ts'] >= $limite ) {
			$titre = wp_date( 'F', $ligne['ts'], $fuseau );
			$titre = mb_strtoupper( mb_substr( $titre, 0, 1 ) ) . mb_substr( $titre, 1 );
			if ( wp_date( 'Y', $ligne['ts'], $fuseau ) !== $ce_mois->format( 'Y' ) ) {
				$titre .= ' ' . wp_date( 'Y', $ligne['ts'], $fuseau );
			}
		} else {
			$titre = $plus_tot;
		}
		$dernier = count( $rubriques ) - 1;
		if ( $dernier >= 0 && $rubriques[ $dernier ][0] === $titre ) {
			$rubriques[ $dernier ][1][] = $ligne;
		} else {
			$rubriques[] = array( $titre, array( $ligne ) );
		}
	}
	return $rubriques;
}

/**
 * L'image d'une ligne, à la taille voulue ; la couverture typographique pour un livre sans bonne photo.
 *
 * @param array  $ligne  La ligne.
 * @param string $taille La taille de WordPress.
 * @param string $sizes  L'attribut sizes.
 * @param bool   $paresseuse Chargée quand elle approche.
 */
function er_index_image( $ligne, $taille, $sizes, $paresseuse = true ) {
	if ( 'typo' === $ligne['vue'] ) {
		return $ligne['produit'] && function_exists( 'er_couverture_typo' ) ? er_couverture_typo( $ligne['produit'] ) : '';
	}
	if ( ! $ligne['image'] ) {
		return '';
	}
	return wp_get_attachment_image(
		$ligne['image'],
		$taille,
		false,
		array(
			'alt'      => '',
			'sizes'    => $sizes,
			'loading'  => $paresseuse ? 'lazy' : 'eager',
			'decoding' => 'async',
		)
	);
}

/**
 * Le module : la liste à gauche, l'image à droite.
 */
function er_index_html() {
	$rubriques = er_index_rubriques();
	if ( ! $rubriques ) {
		return '';
	}
	$liste     = '';
	$panneaux  = '';
	$rang      = 0;
	foreach ( $rubriques as list( $titre, $lignes ) ) {
		$liste .= '<div class="er-index-rubrique">' . ( '' !== $titre ? '<h3 class="er-index-rubrique-titre">' . esc_html( $titre ) . '</h3>' : '' ) . '<ol class="er-index-lignes">';
		foreach ( $lignes as $l ) {
			$actif   = 0 === $rang ? ' est-actif' : '';
			$vignette = er_index_image( $l, 'woocommerce_thumbnail', '72px' );
			// Tous les titres à la même taille (26 nov.) : la prochaine rencontre n'est plus en grand, elle dépassait la phrase.
			$liste  .= '<li class="er-index-ligne' . $actif . '" data-maison="' . esc_attr( $l['maison'] ) . '">'
				. '<p class="er-index-quand"><span class="er-index-point" aria-hidden="true"></span>' . esc_html( $l['quand'] ) . '</p>'
				. '<p class="er-index-titre"><a href="' . esc_url( $l['lien'] ) . '">' . $l['titre'] . '</a></p>' // Le titre est échappé pièce à pièce.
				. ( $vignette ? '<span class="er-index-vignette est-' . esc_attr( $l['vue'] ) . '" aria-hidden="true">' . $vignette . '</span>' : '' )
				. '</li>';
			// Toujours paresseuse : l'index vient après la phrase et le triptyque, hors de l'écran au chargement (la première
			// image partait d'emblée, et WordPress lui donnait la priorité de la page ; audit du 27 nov. 2026).
			$image     = er_index_image( $l, 'photo' === $l['vue'] ? 'large' : 'medium_large', '(max-width: 781px) 1px, 45vw' );
			$panneaux .= '<div class="er-index-panneau' . $actif . '" data-maison="' . esc_attr( $l['maison'] ) . '">'
				. '<div class="er-index-vue est-' . esc_attr( $image ? $l['vue'] : 'aucune' ) . '">'
				. ( $image ? $image : '<p class="er-index-vue-titre">' . $l['titre'] . '</p>' )
				. '</div>'
				. '<div class="er-index-bande">'
				. '<p class="er-index-bande-texte">' . implode( ' · ', $l['legende'] ) . '</p>' // Échappé pièce à pièce.
				. ( '' !== $l['prix'] ? '<p class="er-index-prix">' . esc_html( $l['prix'] ) . '</p>' : '' )
				// Un ajout au panier ne se suit pas (les robots créaient des paniers et des doublons de l'accueil).
				. '<a class="er-index-bouton" href="' . esc_url( $l['action_lien'] ) . '" tabindex="-1"' . ( false !== strpos( (string) $l['action_lien'], 'add-to-cart=' ) ? ' rel="nofollow"' : '' ) . '>' . esc_html( $l['action'] ) . '</a>'
				. '</div></div>';
			++$rang;
		}
		$liste .= '</ol></div>';
	}

	// Au bout : « Le fil continue : » et où tout trouver, sans le compte des nouveautés, des rencontres et des textes
	// (André, 26 nov.) ; sur la même ligne que le bas de la colonne de l'image (22-index.css).
	$liens = array(
		array( __( 'Les nouveautés', 'editeurs-reunis' ), function_exists( 'er_mention' ) ? er_mention( 'nouveaute' ) : er_lien( 'librairie/' ) ),
		array( __( 'Les rencontres', 'editeurs-reunis' ), function_exists( 'er_evt_page_liste' ) ? esc_url( er_evt_page_liste() ) : er_lien( 'centre-culturel/evenements/' ) ),
		array( __( 'Le journal', 'editeurs-reunis' ), er_lien( 'journal/' ) ),
	);
	$fin = '<div class="er-index-fin">'
		. '<p class="er-index-liens"><span class="er-index-fil">' . esc_html__( "Le fil continue\u{00A0}:", 'editeurs-reunis' ) . '</span>' . implode( '', array_map( fn( $l ) => '<a href="' . $l[1] . '">' . esc_html( $l[0] ) . '</a>', $liens ) ) . '</p>'
		. '</div>';

	return '<section class="er-index" aria-labelledby="er-index-titre">'
		. '<h2 id="er-index-titre" class="screen-reader-text">' . esc_html__( 'L’actualité des trois maisons', 'editeurs-reunis' ) . '</h2>'
		. '<div class="er-index-deux">'
		. '<div class="er-index-liste">' . $liste . $fin . '</div>'
		. '<div class="er-index-image" aria-hidden="true">' . $panneaux . '</div>'
		. '</div></section>';
}

// Le module, en code court : le gabarit de l'accueil l'appelle dans un bloc HTML (déplié par le filtre de
// render_block_core/html, functions.php), sans attendre que la composition soit connue de WordPress.
add_shortcode( 'er_index', 'er_index_html' );
