<?php
/**
 * La fiche d'un livre : les renseignements que l'extension garde (traduction, éditeur, collection, parution, pages,
 * format, ISBN), en lignes comme le rayon et la langue. Un bloc dynamique, « er/fiche-champs », que la composition
 * fiche-livre du thème place sous l'auteur ; une ligne vide ne paraît pas.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les lignes de la fiche, dans l'ordre : libellé → valeur, en HTML (déjà échappé). La traduction, la direction et
 * l'éditeur sont des liens vers leur page quand le livre les porte (traducteurs-editeurs.php), sinon le texte repris
 * de Wix.
 *
 * @param WC_Product $produit Le livre.
 */
function er_fiche_champs( $produit ) {
	$pages = (int) $produit->get_meta( 'er_pages' );
	$id    = $produit->get_id();
	$liens = function ( $termes ) {
		$html = array();
		foreach ( $termes as $t ) {
			$lien   = get_term_link( $t );
			$html[] = '<a href="' . esc_url( is_wp_error( $lien ) ? '' : $lien ) . '">' . esc_html( $t->name ) . '</a>';
		}
		return implode( ', ', $html );
	};
	$traduction = function_exists( 'er_personnes_du_livre' ) ? er_personnes_du_livre( $id, 'er_traduction' ) : array();
	$direction  = function_exists( 'er_personnes_du_livre' ) ? er_personnes_du_livre( $id, 'er_direction' ) : array();
	$editeur    = function_exists( 'er_editeur_du_livre' ) ? er_editeur_du_livre( $id ) : array( 'termes' => array(), 'texte' => (string) $produit->get_meta( 'er_editeur' ) );
	$collection = function_exists( 'er_collection_du_livre' ) ? er_collection_du_livre( $id ) : array( 'termes' => array(), 'texte' => (string) $produit->get_meta( 'er_collection' ) );
	$autres     = er_autres_editions( $id );
	$editions   = function ( $ids ) use ( $id ) {
		$html  = array();
		$langue = wp_get_post_terms( $id, 'langue', array( 'fields' => 'ids' ) );
		foreach ( $ids as $autre ) {
			$details = array();
			$sa      = wp_get_post_terms( $autre, 'langue' );
			if ( $sa && ! is_wp_error( $sa ) && ! in_array( (int) $sa[0]->term_id, array_map( 'intval', is_wp_error( $langue ) ? array() : $langue ), true ) ) {
				$details[] = mb_strtolower( $sa[0]->name, 'UTF-8' );
			}
			$ed = function_exists( 'er_editeur_du_livre' ) ? er_editeur_du_livre( $autre ) : array( 'termes' => array(), 'texte' => '' );
			$ed = $ed['termes'] ? $ed['termes'][0]->name : trim( $ed['texte'] );
			if ( '' !== $ed ) {
				$details[] = $ed;
			}
			$annee = trim( (string) get_post_meta( $autre, 'er_annee', true ) );
			if ( '' !== $annee ) {
				$details[] = $annee;
			}
			$html[] = '<a href="' . esc_url( get_permalink( $autre ) ) . '">' . esc_html( html_entity_decode( get_the_title( $autre ), ENT_QUOTES, 'UTF-8' ) ) . '</a>' . ( $details ? ' (' . esc_html( implode( ', ', $details ) ) . ')' : '' );
		}
		return implode( ' · ', $html );
	};
	$texte      = function ( $valeur ) {
		return esc_html( (string) $valeur );
	};
	// Les autres rôles (préface, édition établie, illustrations) : des personnes, comme la traduction.
	$autres_roles = array();
	foreach ( function_exists( 'er_roles_personnes' ) ? er_roles_personnes() : array() as $taxonomie => $role ) {
		if ( ! in_array( $taxonomie, array( 'er_traduction', 'er_direction' ), true ) ) {
			$autres_roles[ $role['fiche'] ] = $liens( er_personnes_du_livre( $id, $taxonomie ) );
		}
	}
	$glose = trim( (string) $produit->get_meta( 'er_titre_fr' ), ' «»"' );
	return array(
		_x( 'Le titre en français', 'fiche d’un livre', 'er-librairie' ) => '' !== $glose ? '« ' . $texte( $glose ) . ' »' : '',
		_x( 'Traduction', 'fiche d’un livre', 'er-librairie' )           => $traduction ? $liens( $traduction ) : $texte( $produit->get_meta( 'er_traducteur' ) ),
		_x( 'Sous la direction de', 'fiche d’un livre', 'er-librairie' ) => $liens( $direction ),
	) + $autres_roles + array(
		_x( 'Éditeur', 'fiche d’un livre', 'er-librairie' )              => $editeur['termes'] ? $liens( $editeur['termes'] ) : $texte( $editeur['texte'] ),
		_x( 'Collection', 'fiche d’un livre', 'er-librairie' )           => $collection['termes'] ? $liens( $collection['termes'] ) : $texte( $collection['texte'] ),
		_x( 'Parution', 'fiche d’un livre', 'er-librairie' )             => $texte( $produit->get_meta( 'er_annee' ) ),
		_x( 'Pages', 'fiche d’un livre', 'er-librairie' )                => $pages ? $texte( number_format_i18n( $pages ) ) : '',
		_x( 'Format', 'fiche d’un livre', 'er-librairie' )               => $texte( $produit->get_meta( 'er_format' ) ),
		_x( 'ISBN', 'fiche d’un livre', 'er-librairie' )                 => $texte( er_isbn( $produit ) ),
		_x( 'Autres éditions', 'fiche d’un livre', 'er-librairie' )      => $editions( $autres['editions'] ),
		_x( 'Les autres volumes', 'fiche d’un livre', 'er-librairie' )   => $editions( $autres['volumes'] ),
	);
}

/**
 * Les autres fiches d'un même livre (24 nov. 2026, l'audit : onze livres avaient plusieurs fiches que rien ne reliait) :
 * mêmes auteurs, même titre avant le sous-titre. Le même titre en entier (à la transcription près : « Патриот » et
 * « Patriote ») est une autre édition ; un titre qui ne diffère qu'après (« Красное Колесо – Март Семнадцатого том 1 »
 * et « … том 2 ») est un autre volume. Les billets des rencontres n'en sont pas.
 *
 * @param int $id Le livre.
 * @return array{editions: int[], volumes: int[]}
 */
function er_autres_editions( $id ) {
	static $cache = array();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}
	$vide    = array( 'editions' => array(), 'volumes' => array() );
	$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'ids' ) );
	if ( ! $auteurs || is_wp_error( $auteurs ) || ! function_exists( 'er_cle_recherche' ) ) {
		return $cache[ $id ] = $vide;
	}
	$auteurs = array_map( 'intval', $auteurs );
	sort( $auteurs );
	$cles = function ( $titre ) {
		$titre = html_entity_decode( (string) $titre, ENT_QUOTES, 'UTF-8' );
		return array( er_cle_recherche( preg_split( '/\s[:—–-]\s|[:(]/u', $titre )[0] ), er_cle_recherche( $titre ) );
	};
	list( $base, $entier ) = $cles( get_the_title( $id ) );
	if ( strlen( $base ) < 3 ) {
		return $cache[ $id ] = $vide;
	}
	$proches = get_posts(
		array(
			'post_type'    => 'product',
			'post_status'  => 'publish',
			'numberposts'  => 60,
			'fields'       => 'ids',
			'post__not_in' => array( $id ),
			'lang'         => '',
			'tax_query'    => array( array( 'taxonomy' => 'auteur', 'terms' => $auteurs ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$trouves = $vide;
	foreach ( $proches as $autre ) {
		if ( get_post_meta( $autre, 'er_evenement', true ) ) {
			continue;
		}
		$siens = array_map( 'intval', wp_get_post_terms( $autre, 'auteur', array( 'fields' => 'ids' ) ) );
		sort( $siens );
		if ( $siens !== $auteurs ) {
			continue;
		}
		list( $sa_base, $son_entier ) = $cles( get_the_title( $autre ) );
		if ( $sa_base !== $base ) {
			continue;
		}
		$trouves[ $son_entier === $entier ? 'editions' : 'volumes' ][] = (int) $autre;
	}
	foreach ( array_keys( $trouves ) as $sorte ) {
		usort( $trouves[ $sorte ], fn( $a, $b ) => strnatcasecmp( get_the_title( $a ), get_the_title( $b ) ) );
	}
	$cache[ $id ] = $trouves;
	return $trouves;
}

/**
 * L'ISBN d'un livre : le champ « GTIN, UPC, EAN ou ISBN » de WooCommerce, sinon la méta `er_isbn`. WooCommerce
 * refuse qu'un même GTIN serve à deux produits ; quand la librairie a deux fiches pour un même livre (neuf et
 * d'occasion), la seconde le garde dans `er_isbn` (outils/catalogue/isbn.py).
 *
 * @param WC_Product $produit Le livre.
 */
function er_isbn( $produit ) {
	$isbn = method_exists( $produit, 'get_global_unique_id' ) ? (string) $produit->get_global_unique_id() : '';
	return '' !== $isbn ? $isbn : (string) $produit->get_meta( 'er_isbn' );
}

add_action(
	'init',
	function () {
		register_block_type(
			'er/fiche-champs',
			array(
				'api_version'     => 3,
				'uses_context'    => array( 'postId' ),
				'render_callback' => function ( $attributs, $contenu, $bloc ) {
					$id      = isset( $bloc->context['postId'] ) ? (int) $bloc->context['postId'] : get_the_ID();
					$produit = $id ? wc_get_product( $id ) : null;
					if ( ! $produit ) {
						return '';
					}
					$lignes = '';
					foreach ( er_fiche_champs( $produit ) as $libelle => $valeur ) {
						if ( '' === trim( (string) $valeur ) ) {
							continue;
						}
						$lignes .= '<p class="er-fiche-champ"><span class="er-fiche-champ__libelle">' . esc_html( $libelle ) . '</span>' . wp_kses_post( $valeur ) . '</p>';
					}
					// Les personnes dont parle le livre, ses sujets et ses époques : des liens vers leur page.
					$liees = array(
						_x( 'Autour de', 'fiche d’un livre', 'er-librairie' )         => function_exists( 'er_autour_de' ) ? er_autour_de( $id ) : array(),
						_x( 'Sujets', 'fiche d’un livre', 'er-librairie' )            => wp_get_post_terms( $id, 'er_sujet' ),
						_x( 'Époque d’écriture', 'fiche d’un livre', 'er-librairie' ) => er_epoques_du_livre( $id, 'er_ecriture' ),
						_x( 'Époque traitée', 'fiche d’un livre', 'er-librairie' )    => er_epoques_du_livre( $id, 'er_periode' ),
					);
					foreach ( $liees as $libelle => $termes ) {
						if ( ! $termes || is_wp_error( $termes ) ) {
							continue;
						}
						$liens = array();
						foreach ( $termes as $t ) {
							$lien    = get_term_link( $t );
							$liens[] = '<a href="' . esc_url( is_wp_error( $lien ) ? '' : $lien ) . '">' . esc_html( $t->name ) . '</a>';
						}
						$lignes .= '<p class="er-fiche-champ"><span class="er-fiche-champ__libelle">' . esc_html( $libelle ) . '</span>' . implode( ', ', $liens ) . '</p>';
					}
					// Les prix, puis la presse (er_presse, la revue de presse des livres récents).
					$presse = get_post_meta( $id, 'er_presse', true );
					foreach ( array( 'prix' => _x( 'Prix', 'fiche d’un livre', 'er-librairie' ), 'presse' => _x( 'Ils en parlent', 'fiche d’un livre', 'er-librairie' ) ) as $genre => $libelle ) {
						$liens = array();
						foreach ( is_array( $presse ) ? $presse : array() as $e ) {
							if ( ( $e['genre'] ?? '' ) !== $genre ) {
								continue;
							}
							$texte   = esc_html( $e['nom'] ) . ( '' !== ( $e['date'] ?? '' ) ? ' (' . esc_html( substr( $e['date'], 0, 4 ) ) . ')' : '' );
							$liens[] = '' !== ( $e['lien'] ?? '' ) ? '<a href="' . esc_url( $e['lien'] ) . '" rel="noopener">' . $texte . '</a>' : $texte;
						}
						if ( $liens ) {
							$lignes .= '<p class="er-fiche-champ"><span class="er-fiche-champ__libelle">' . esc_html( $libelle ) . '</span>' . implode( ', ', $liens ) . '</p>';
						}
					}
					return $lignes ? '<div class="wp-block-er-fiche-champs">' . $lignes . '</div>' : '';
				},
			)
		);
	}
);

/**
 * Les corrections faites une fois dans le contenu déjà en place (les pages semées avant la correction du texte).
 */
add_action(
	'init',
	function () {
		$corrections = array(
			1 => array( 'Taganstsev', 'Tagantsev' ),
		);
		$faite = (int) get_option( 'er_corrections', 0 );
		if ( $faite >= max( array_keys( $corrections ) ) ) {
			return;
		}
		global $wpdb;
		foreach ( $corrections as $numero => $paire ) {
			if ( $numero <= $faite ) {
				continue;
			}
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s", $paire[0], $paire[1], '%' . $wpdb->esc_like( $paire[0] ) . '%' ) ); // phpcs:ignore WordPress.DB
			$faite = $numero;
		}
		update_option( 'er_corrections', $faite );
	},
	20
);
