<?php
/**
 * Les données structurées (schema.org, en JSON-LD) : ce que les moteurs de recherche lisent pour montrer un livre
 * avec son prix et sa disponibilité, une rencontre avec sa date et son lieu, une personne avec ses dates.
 *
 * - Un livre : Book et Product à la fois, avec l'offre (le prix que la règle du livre laisse payer, la disponibilité,
 *   neuf ou d'occasion), l'ISBN, les auteurs et les traducteurs, l'éditeur, la langue, l'année, les pages, et son fil
 *   d'Ariane. Celles de WooCommerce sont éteintes : une seule description du livre.
 * - Une rencontre : Event ; une exposition : ExhibitionEvent. La date à l'heure de Paris, le lieu, l'état (annulée),
 *   les intervenants, l'entrée (libre ou le billet).
 * - Une personne (la page d'un auteur) : Person, avec son nom russe, ses dates et lieux de naissance et de mort, son
 *   portrait, ses pages sur Wikipédia ou à la BnF.
 * - L'accueil : la librairie (BookStore) et le site.
 * - Le fil d'Ariane des autres pages vient de leur tête (le thème, inc/tete.php).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/* Les morceaux ----------------------------------------------------------------------------------------------- */

/**
 * Le JSON-LD, prêt pour la page. JSON_HEX_TAG : un « </script> » dans un texte ne ferme rien.
 *
 * @param array $donnees Les données.
 */
function er_ld_script( $donnees ) {
	return '<script type="application/ld+json">' . wp_json_encode( $donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>' . "\n";
}

/**
 * L'adresse de la maison.
 */
function er_ld_adresse() {
	return array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => '11, rue de la Montagne-Sainte-Geneviève',
		'postalCode'      => '75005',
		'addressLocality' => 'Paris',
		'addressCountry'  => 'FR',
	);
}

/**
 * La librairie.
 */
function er_ld_librairie() {
	return array(
		'@type'                     => 'BookStore',
		'name'                      => 'Les Éditeurs réunis',
		'url'                       => home_url( '/' ),
		'address'                   => er_ld_adresse(),
		'openingHoursSpecification' => array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => array( 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ),
			'opens'     => '10:30',
			'closes'    => '18:30',
		),
	);
}

/**
 * Le centre culturel, qui organise les rencontres.
 */
function er_ld_centre() {
	return array(
		'@type'   => 'Organization',
		'name'    => 'Centre culturel Alexandre Soljenitsyne',
		'url'     => home_url( '/centre-culturel/' ),
		'address' => er_ld_adresse(),
	);
}

/**
 * Une personne, par son terme (auteur ou intervenant) : son nom et sa page.
 *
 * @param WP_Term $terme Le terme.
 */
function er_ld_nom( $terme ) {
	$lien = get_term_link( $terme );
	return array_filter(
		array(
			'@type' => 'Person',
			'name'  => html_entity_decode( $terme->name, ENT_QUOTES, 'UTF-8' ),
			'url'   => is_wp_error( $lien ) ? '' : $lien,
		)
	);
}

/**
 * Le code d'une langue des livres (le slug du terme « langue » : russe, francais…).
 *
 * @param string $slug Le slug.
 * @return string|string[]
 */
function er_ld_langue( $slug ) {
	$codes = array(
		'russe'    => 'ru',
		'francais' => 'fr',
		'anglais'  => 'en',
		'allemand' => 'de',
		'italien'  => 'it',
		'ukrainien' => 'uk',
		'bilingue' => array( 'ru', 'fr' ),
	);
	foreach ( $codes as $debut => $code ) {
		if ( 0 === strpos( $slug, $debut ) ) {
			return $code;
		}
	}
	return '';
}

/**
 * Une date et un lieu écrits en français (« Kislovodsk, 11 décembre 1918 », « 1918 », « Moscou, août 2008 ») : la date
 * au format ISO (aussi précise que le texte) et le lieu.
 *
 * @param string $texte Le texte.
 * @return array{date: string, lieu: string}
 */
function er_ld_date_lieu( $texte ) {
	$texte = trim( wp_strip_all_tags( (string) $texte ) );
	$mois  = array( 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
	$date  = '';
	$lieu  = $texte;
	if ( preg_match( '/(?:(\d{1,2})(?:er)?\s+)?(?:(' . implode( '|', $mois ) . ')\s+)?(\d{4})\s*$/iu', $texte, $m ) ) {
		$date = $m[3];
		if ( ! empty( $m[2] ) ) {
			$date .= '-' . sprintf( '%02d', array_search( mb_strtolower( $m[2], 'UTF-8' ), $mois, true ) + 1 );
			if ( ! empty( $m[1] ) ) {
				$date .= '-' . sprintf( '%02d', (int) $m[1] );
			}
		}
		$lieu = trim( (string) substr( $texte, 0, -strlen( $m[0] ) ), " ,\t" );
	}
	return array(
		'date' => $date,
		'lieu' => $lieu,
	);
}

/* Un livre --------------------------------------------------------------------------------------------------- */

/**
 * Les données d'un livre.
 *
 * @param WC_Product $produit Le livre.
 */
function er_ld_livre( $produit ) {
	$id       = $produit->get_id();
	$auteurs  = wp_get_post_terms( $id, 'auteur' );
	$langues  = wp_get_post_terms( $id, 'langue', array( 'fields' => 'slugs' ) );
	$langue   = $langues && ! is_wp_error( $langues ) ? er_ld_langue( $langues[0] ) : '';
	$annee    = preg_match( '/\b(1[4-9]\d\d|20\d\d)\b/', (string) $produit->get_meta( 'er_annee' ), $m ) ? $m[1] : '';
	$editeur  = function_exists( 'er_editeur_du_livre' ) ? er_editeur_du_livre( $id ) : array( 'termes' => array(), 'texte' => '' );
	$editeur  = $editeur['termes'] ? $editeur['termes'][0]->name : trim( (string) $editeur['texte'] );
	$occasion = has_term( 'occasion', 'product_tag', $id );
	$image    = wp_get_attachment_image_url( $produit->get_image_id(), 'large' );
	$isbn     = er_isbn( $produit );
	$texte    = $produit->get_short_description() ? $produit->get_short_description() : $produit->get_description();
	$roles    = array(
		'translator'  => 'er_traduction',
		'editor'      => 'er_direction',
		'illustrator' => 'er_illustration',
	);
	$livre    = array(
		'@type'         => array( 'Book', 'Product' ),
		'@id'           => get_permalink( $id ) . '#livre',
		'name'          => html_entity_decode( $produit->get_name(), ENT_QUOTES, 'UTF-8' ),
		'url'           => get_permalink( $id ),
		'image'         => $image ? $image : '',
		'description'   => wp_html_excerpt( html_entity_decode( wp_strip_all_tags( $texte ), ENT_QUOTES, 'UTF-8' ), 300, '…' ),
		'sku'           => $produit->get_sku(),
		'isbn'          => $isbn,
		'gtin13'        => preg_match( '/^\d{13}$/', $isbn ) ? $isbn : '',
		'author'        => is_wp_error( $auteurs ) ? array() : array_map( 'er_ld_nom', $auteurs ),
		'publisher'     => '' !== $editeur ? array( '@type' => 'Organization', 'name' => $editeur ) : '',
		'inLanguage'    => $langue,
		'datePublished' => $annee,
		'numberOfPages' => (int) $produit->get_meta( 'er_pages' ),
		'offers'        => array(
			'@type'         => 'Offer',
			'url'           => get_permalink( $id ),
			'price'         => wc_format_decimal( $produit->get_price(), 2 ),
			'priceCurrency' => get_woocommerce_currency(),
			'availability'  => $produit->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			'itemCondition' => $occasion ? 'https://schema.org/UsedCondition' : 'https://schema.org/NewCondition',
			'seller'        => array(
				'@type' => 'BookStore',
				'name'  => 'Les Éditeurs réunis',
			),
		),
	);
	foreach ( $roles as $propriete => $taxonomie ) {
		if ( function_exists( 'er_personnes_du_livre' ) ) {
			$livre[ $propriete ] = array_map( 'er_ld_nom', er_personnes_du_livre( $id, $taxonomie ) );
		}
	}
	return array_filter( $livre, fn( $v ) => '' !== $v && array() !== $v && 0 !== $v );
}

/**
 * Le fil d'Ariane d'un livre : la librairie, son rayon, le livre.
 *
 * @param WC_Product $produit Le livre.
 */
function er_ld_fil_livre( $produit ) {
	$fil  = array( array( __( 'La librairie', 'er-librairie' ), function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/librairie/' ) ) );
	$cats = wp_get_post_terms( $produit->get_id(), 'product_cat' );
	if ( $cats && ! is_wp_error( $cats ) ) {
		$rayon = $cats[0];
		if ( $rayon->parent ) {
			$parent = get_term( $rayon->parent, 'product_cat' );
			if ( $parent instanceof WP_Term ) {
				$fil[] = array( $parent->name, get_term_link( $parent ) );
			}
		}
		$fil[] = array( $rayon->name, get_term_link( $rayon ) );
	}
	$fil[] = array( $produit->get_name(), get_permalink( $produit->get_id() ) );
	return er_ld_fil( $fil );
}

/**
 * Un fil d'Ariane (BreadcrumbList) : des paires nom, adresse.
 *
 * @param array $fil Les étapes : array( nom, adresse ).
 */
function er_ld_fil( $fil ) {
	$elements = array();
	foreach ( array_values( $fil ) as $i => list( $nom, $lien ) ) {
		$elements[] = array_filter(
			array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => html_entity_decode( wp_strip_all_tags( (string) $nom ), ENT_QUOTES, 'UTF-8' ),
				'item'     => is_string( $lien ) ? $lien : '',
			)
		);
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $elements,
	);
}

/* Une rencontre, une exposition ------------------------------------------------------------------------------ */

/**
 * Une date des champs d'une rencontre, au format ISO : la date seule si l'heure n'est pas dite.
 *
 * @param int    $id  La rencontre.
 * @param string $cle er_debut ou er_fin.
 */
function er_ld_date( $id, $cle ) {
	$d = function_exists( 'er_evt_date' ) ? er_evt_date( $id, $cle ) : null;
	if ( ! $d ) {
		return '';
	}
	return '00:00' === $d->format( 'H:i' ) ? $d->format( 'Y-m-d' ) : $d->format( 'c' );
}

/**
 * Les données d'une rencontre ou d'une exposition.
 *
 * @param WP_Post $post La rencontre ou l'exposition.
 */
function er_ld_rencontre( $post ) {
	$id     = $post->ID;
	$expo   = 'er_exposition' === $post->post_type;
	$lieu   = trim( (string) get_post_meta( $id, 'er_lieu', true ) );
	$au_centre = '' === $lieu || false !== stripos( remove_accents( $lieu ), 'montagne' );
	$en_ligne  = ! $au_centre && preg_match( '/en ligne|zoom|youtube|visio/iu', $lieu );
	$langues   = array(
		'francais' => 'fr',
		'russe'    => 'ru',
		'anglais'  => 'en',
		'bilingue' => array( 'fr', 'ru' ),
	);
	$langue      = (string) get_post_meta( $id, 'er_langue', true );
	$inscription = (string) get_post_meta( $id, 'er_inscription', true );
	$etat        = (string) get_post_meta( $id, 'er_etat', true );
	$image       = get_the_post_thumbnail_url( $id, 'large' );
	$intervenants = array();
	$termes       = wp_get_post_terms( $id, 'er_intervenant' );
	foreach ( is_wp_error( $termes ) ? array() : $termes as $t ) {
		// Lié à sa page d'auteur quand il en a une.
		$auteur         = function_exists( 'er_evt_personne' ) ? er_evt_personne( $t ) : null;
		$intervenants[] = array_merge(
			$auteur ? er_ld_nom( $auteur ) : array( '@type' => 'Person' ),
			array( 'name' => html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) )
		);
	}
	$donnees     = array(
		'@type'               => $expo ? 'ExhibitionEvent' : 'Event',
		'name'                => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
		'url'                 => get_permalink( $id ),
		'description'         => has_excerpt( $id ) ? html_entity_decode( wp_strip_all_tags( get_the_excerpt( $id ) ), ENT_QUOTES, 'UTF-8' ) : '',
		'image'               => $image ? $image : '',
		'startDate'           => er_ld_date( $id, 'er_debut' ),
		'endDate'             => er_ld_date( $id, 'er_fin' ),
		'eventStatus'         => 'annule' === $etat ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => $en_ligne ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode',
		'location'            => $en_ligne
			? array(
				'@type' => 'VirtualLocation',
				'url'   => get_permalink( $id ),
			)
			: array(
				'@type'   => 'Place',
				'name'    => $au_centre ? 'Centre culturel Alexandre Soljenitsyne' : $lieu,
				'address' => $au_centre ? er_ld_adresse() : $lieu,
			),
		'inLanguage'          => $langues[ $langue ] ?? '',
		'organizer'           => er_ld_centre(),
		'performer'           => $intervenants,
		'isAccessibleForFree' => in_array( $inscription, array( 'gratuite', 'libre' ), true ) ? true : '',
	);
	$billet = function_exists( 'er_evt_billet' ) ? er_evt_billet( $id ) : null;
	if ( $billet ) {
		$donnees['offers'] = array(
			'@type'         => 'Offer',
			'url'           => get_permalink( $id ),
			'price'         => wc_format_decimal( $billet->get_price(), 2 ),
			'priceCurrency' => get_woocommerce_currency(),
			'availability'  => 'complet' === $etat || ! $billet->is_in_stock() ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
		);
	}
	return array_filter( $donnees, fn( $v ) => '' !== $v && array() !== $v );
}

/* Une personne ----------------------------------------------------------------------------------------------- */

/**
 * Les données d'une personne (la page d'un auteur).
 *
 * @param WP_Term $terme L'auteur.
 */
function er_ld_personne( $terme ) {
	$naissance = er_ld_date_lieu( get_term_meta( $terme->term_id, 'er_naissance', true ) );
	$deces     = er_ld_date_lieu( get_term_meta( $terme->term_id, 'er_deces', true ) );
	$portrait  = wp_get_attachment_image_url( (int) get_term_meta( $terme->term_id, 'er_portrait', true ), 'large' );
	$russe     = (string) get_term_meta( $terme->term_id, 'er_nom_russe', true );
	if ( '' === $russe && function_exists( 'er_autres_graphies' ) ) {
		foreach ( (array) er_autres_graphies( $terme->term_id ) as $g ) {
			if ( preg_match( '/\p{Cyrillic}/u', $g ) ) {
				$russe = $g;
				break;
			}
		}
	}
	$memes = array();
	foreach ( preg_split( '/\R/', (string) get_term_meta( $terme->term_id, 'er_liens', true ) ) as $ligne ) {
		$lien = trim( (string) preg_replace( '/^.*\|/', '', $ligne ) );
		if ( preg_match( '#^https?://([a-z]+\.)?(wikipedia\.org|wikidata\.org|bnf\.fr|viaf\.org|isni\.org|idref\.fr)/#i', $lien ) ) {
			$memes[] = $lien;
		}
	}
	$lien    = get_term_link( $terme );
	$premier = preg_split( '/\R\s*\R|<\/p>/u', trim( (string) $terme->description ) )[0];
	return array_filter(
		array(
			'@type'         => 'Person',
			'name'          => html_entity_decode( $terme->name, ENT_QUOTES, 'UTF-8' ),
			'alternateName' => $russe,
			'url'           => is_wp_error( $lien ) ? '' : $lien,
			'description'   => html_entity_decode( wp_strip_all_tags( $premier ), ENT_QUOTES, 'UTF-8' ),
			'image'         => $portrait ? $portrait : '',
			'birthDate'     => $naissance['date'],
			'birthPlace'    => '' !== $naissance['lieu'] ? array( '@type' => 'Place', 'name' => $naissance['lieu'] ) : '',
			'deathDate'     => $deces['date'],
			'deathPlace'    => '' !== $deces['lieu'] ? array( '@type' => 'Place', 'name' => $deces['lieu'] ) : '',
			'sameAs'        => $memes,
		),
		fn( $v ) => '' !== $v && array() !== $v
	);
}

/* Dans la page ----------------------------------------------------------------------------------------------- */

// Une seule description d'un livre : la nôtre.
add_filter( 'woocommerce_structured_data_product', '__return_empty_array' );

add_action(
	'wp_head',
	function () {
		$donnees = array();
		if ( function_exists( 'is_product' ) && is_product() ) {
			$produit = wc_get_product( get_queried_object_id() );
			if ( $produit && ! $produit->get_meta( 'er_evenement' ) ) {
				$donnees[] = er_ld_livre( $produit );
				$donnees[] = er_ld_fil_livre( $produit );
			}
		} elseif ( is_singular( array( 'er_evenement', 'er_exposition' ) ) ) {
			$donnees[] = er_ld_rencontre( get_queried_object() );
		} elseif ( is_tax( 'auteur' ) ) {
			$donnees[] = er_ld_personne( get_queried_object() );
		} elseif ( is_front_page() ) {
			$donnees[] = er_ld_librairie();
			$donnees[] = array(
				'@type'           => 'WebSite',
				'name'            => 'Les Éditeurs réunis',
				'url'             => home_url( '/' ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => home_url( '/?s={search_term_string}&post_type=product' ),
					'query-input' => 'required name=search_term_string',
				),
			);
		}
		if ( $donnees ) {
			echo er_ld_script( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encodé, balises échappées.
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $donnees,
				)
			);
		}
	}
);
