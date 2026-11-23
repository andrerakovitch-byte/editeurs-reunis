<?php
/**
 * Le point sur le catalogue : `wp er catalogue`.
 *
 * Des comptes, jamais une liste de livres ni une quantité en stock : le rapport du workflow « Déployer chez Kinsta »
 * se lit dans un dépôt public. Rien sur les ventes : ni les commandes ni le compte des ventes de WooCommerce ne sont
 * lus ici. Les billets des rencontres (les produits qui portent `er_evenement`) sont comptés à part : ce ne sont pas
 * des livres.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le poids d'un produit en grammes, quelle que soit l'unité de la boutique ; null sans poids.
 *
 * @param WC_Product $produit Le produit.
 */
function er_catalogue_grammes( $produit ) {
	$poids = $produit->get_weight();
	if ( '' === $poids || null === $poids || (float) $poids <= 0 ) {
		return null;
	}
	return (int) round( wc_get_weight( (float) $poids, 'g' ) );
}

/**
 * La médiane d'une liste de nombres.
 *
 * @param array $nombres Les nombres.
 */
function er_catalogue_mediane( $nombres ) {
	if ( ! $nombres ) {
		return 0;
	}
	sort( $nombres );
	$n = count( $nombres );
	return $n % 2 ? $nombres[ intdiv( $n, 2 ) ] : ( $nombres[ $n / 2 - 1 ] + $nombres[ $n / 2 ] ) / 2;
}

/**
 * Les comptes du catalogue.
 */
function er_catalogue_comptes() {
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$c   = array(
		'livres'        => 0,
		'statuts'       => array(),
		'wix'           => 0,
		'exemples'      => 0,
		'autres_refs'   => 0,
		'avec_prix'     => 0,
		'promotion'     => 0,
		'stock'         => array( 'instock' => 0, 'outofstock' => 0, 'onbackorder' => 0 ),
		'stock_suivi'   => 0,
		'stock_sans_q'  => 0,
		'commandables'  => 0,
		'prix'          => array(),
		'sous_35'       => 0,
		'poids'         => array(),
		'sans_poids'    => 0,
		'sans_poids_c'  => 0,
		'tranches'      => array( 250 => 0, 500 => 0, 1000 => 0, 2000 => 0, 'plus' => 0 ),
		'virtuels'      => 0,
		'caches'        => 0,
		'isbn'          => 0,
		'isbn_c'        => 0,
		'sans_rayon'    => 0,
		'sans_auteur'   => 0,
		'sans_langue'   => 0,
		'couvertures'   => array_fill_keys( array_keys( er_couverture_qualites() ), array( 0, 0 ) ),
		'billets'       => 0,
		'billets_st'    => array(),
		'billets_virt'  => 0,
		'billets_cache' => 0,
	);
	$par_defaut = (int) get_option( 'default_product_cat' );
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			continue;
		}
		$statut = $p->get_status();
		if ( $p->get_meta( 'er_evenement' ) ) {
			++$c['billets'];
			$c['billets_st'][ $statut ] = ( isset( $c['billets_st'][ $statut ] ) ? $c['billets_st'][ $statut ] : 0 ) + 1;
			$c['billets_virt']         += $p->is_virtual() ? 1 : 0;
			$c['billets_cache']        += 'hidden' === $p->get_catalog_visibility() ? 1 : 0;
			continue;
		}
		++$c['livres'];
		$c['statuts'][ $statut ] = ( isset( $c['statuts'][ $statut ] ) ? $c['statuts'][ $statut ] : 0 ) + 1;
		$ref                     = (string) $p->get_sku();
		if ( 0 === strpos( $ref, 'WIX-' ) ) {
			++$c['wix'];
		} elseif ( '' === $ref ) {
			++$c['exemples'];
		} else {
			++$c['autres_refs'];
		}
		$prix = $p->get_price();
		if ( '' !== $prix && null !== $prix ) {
			++$c['avec_prix'];
		}
		if ( $p->is_on_sale() ) {
			++$c['promotion'];
		}
		$etat = $p->get_stock_status();
		if ( isset( $c['stock'][ $etat ] ) ) {
			++$c['stock'][ $etat ];
		}
		if ( $p->managing_stock() ) {
			++$c['stock_suivi'];
		} elseif ( 'instock' === $etat ) {
			++$c['stock_sans_q'];
		}
		$commandable = 'publish' === $statut && $p->is_purchasable() && $p->is_in_stock();
		if ( $commandable ) {
			++$c['commandables'];
			$c['prix'][] = (float) $prix;
			if ( (float) $prix < 35 ) {
				++$c['sous_35'];
			}
		}
		$g = er_catalogue_grammes( $p );
		if ( null === $g ) {
			++$c['sans_poids'];
			$c['sans_poids_c'] += $commandable ? 1 : 0;
		} else {
			$c['poids'][] = $g;
			if ( $commandable ) {
				$tranche = 'plus';
				foreach ( array( 250, 500, 1000, 2000 ) as $seuil ) {
					if ( $g <= $seuil ) {
						$tranche = $seuil;
						break;
					}
				}
				++$c['tranches'][ $tranche ];
			}
		}
		$c['virtuels'] += $p->is_virtual() ? 1 : 0;
		$c['caches']   += in_array( $p->get_catalog_visibility(), array( 'hidden', 'search' ), true ) ? 1 : 0;
		if ( '' !== er_isbn( $p ) ) {
			++$c['isbn'];
			$c['isbn_c'] += $commandable ? 1 : 0;
		}
		$rayons = array_diff( $p->get_category_ids(), array( $par_defaut ) );
		$c['sans_rayon']  += $rayons ? 0 : 1;
		$c['sans_auteur'] += wp_get_object_terms( $id, 'auteur', array( 'fields' => 'ids' ) ) ? 0 : 1;
		$c['sans_langue'] += wp_get_object_terms( $id, 'langue', array( 'fields' => 'ids' ) ) ? 0 : 1;
		$q                 = er_couverture_qualite( $p );
		++$c['couvertures'][ $q ][0];
		$c['couvertures'][ $q ][1] += $commandable ? 1 : 0;
	}
	return $c;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Le point sur le catalogue : les livres, leur prix, leur stock, leur poids, leurs fiches et leurs couvertures,
	 * puis les billets des rencontres. Des comptes seulement.
	 */
	WP_CLI::add_command(
		'er catalogue',
		function () {
			$c     = er_catalogue_comptes();
			// Les colonnes s'alignent sur le nombre de lettres, pas d'octets (les accents en prennent deux).
			$ligne   = function ( $libelle, $nombre, $suite = '' ) {
				$nombre = is_numeric( $nombre ) ? number_format_i18n( $nombre ) : $nombre;
				WP_CLI::log( '  ' . $libelle . str_repeat( ' ', max( 1, 42 - mb_strlen( $libelle ) - mb_strlen( $nombre ) ) ) . $nombre . ( '' !== $suite ? '   ' . $suite : '' ) );
			};
			$statuts = array(
				'publish' => array( 'publié', 'publiés' ),
				'draft'   => array( 'brouillon', 'brouillons' ),
				'pending' => array( 'en attente', 'en attente' ),
				'private' => array( 'privé', 'privés' ),
			);
			$detail  = function ( $comptes ) use ( $statuts ) {
				$morceaux = array();
				foreach ( $comptes as $s => $n ) {
					$morceaux[] = $n . ' ' . ( isset( $statuts[ $s ] ) ? $statuts[ $s ][ $n > 1 ? 1 : 0 ] : $s );
				}
				return $morceaux ? '(' . implode( ', ', $morceaux ) . ')' : '';
			};

			WP_CLI::log( 'Le catalogue, hors billets des rencontres' );
			$ligne( 'Livres', $c['livres'], $detail( $c['statuts'] ) );
			$ligne( 'Venus de Wix (référence WIX-…)', $c['wix'] );
			$ligne( 'Sans référence (exemples, saisis)', $c['exemples'] );
			$ligne( 'Avec une autre référence', $c['autres_refs'] );

			WP_CLI::log( 'Le prix' );
			$ligne( 'Avec un prix', $c['avec_prix'] );
			$ligne( 'Sans prix (ne se commandent pas)', $c['livres'] - $c['avec_prix'] );
			$ligne( 'En promotion', $c['promotion'] );

			WP_CLI::log( 'Le stock' );
			$ligne( 'En stock', $c['stock']['instock'] );
			$ligne( 'Épuisés', $c['stock']['outofstock'] );
			$ligne( 'En réassort', $c['stock']['onbackorder'] );
			$ligne( 'Stock suivi en quantité', $c['stock_suivi'] );
			$ligne( 'En stock sans quantité', $c['stock_sans_q'] );
			$ligne( 'Commandables', $c['commandables'], '(publiés, avec un prix, en stock)' );
			if ( $c['prix'] ) {
				$ligne( 'Prix médian des commandables', number_format_i18n( er_catalogue_mediane( $c['prix'] ), 2 ) . ' €' );
				$ligne( 'Commandables à moins de 35 €', $c['sous_35'] );
			}

			WP_CLI::log( 'Pour la livraison' );
			$ligne( 'Avec un poids', count( $c['poids'] ) );
			$ligne( 'Sans poids', $c['sans_poids'], sprintf( '(dont %d commandables)', $c['sans_poids_c'] ) );
			if ( $c['poids'] ) {
				$ligne( 'Poids médian', number_format_i18n( er_catalogue_mediane( $c['poids'] ) ) . ' g', sprintf( '(de %s à %s g)', number_format_i18n( min( $c['poids'] ) ), number_format_i18n( max( $c['poids'] ) ) ) );
				$ligne( 'Commandables jusqu’à 250 g', $c['tranches'][250] );
				$ligne( 'de 251 à 500 g', $c['tranches'][500] );
				$ligne( 'de 501 g à 1 kg', $c['tranches'][1000] );
				$ligne( 'de 1 à 2 kg', $c['tranches'][2000] );
				$ligne( 'plus de 2 kg', $c['tranches']['plus'] );
			}
			$ligne( 'Livres virtuels (sans livraison)', $c['virtuels'] );
			$ligne( 'Livres cachés du catalogue', $c['caches'] );

			WP_CLI::log( 'Les fiches' );
			$ligne( 'Avec un ISBN', $c['isbn'], sprintf( '(dont %d commandables)', $c['isbn_c'] ) );
			$ligne( 'Sans rayon', $c['sans_rayon'] );
			$ligne( 'Sans auteur', $c['sans_auteur'] );
			$ligne( 'Sans langue', $c['sans_langue'] );

			WP_CLI::log( 'Les couvertures' );
			foreach ( er_couverture_qualites() as $q => $nom ) {
				$ligne( $nom, $c['couvertures'][ $q ][0], sprintf( '(dont %d commandables)', $c['couvertures'][ $q ][1] ) );
			}

			WP_CLI::log( 'Les billets des rencontres' );
			$ligne( 'Billets', $c['billets'], $detail( $c['billets_st'] ) );
			$ligne( 'Virtuels', $c['billets_virt'] );
			$ligne( 'Cachés du catalogue', $c['billets_cache'] );
		}
	);
}
