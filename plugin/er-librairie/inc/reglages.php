<?php
/**
 * Les réglages de la boutique, vus de la ligne de commande : `wp er extensions`.
 *
 * Le relevé des extensions installées et de l'état du paiement, de la livraison et des langues. Il passe par le
 * rapport du workflow « Déployer chez Kinsta », qui se lit dans un dépôt public : aucune clé, aucun secret, aucun
 * identifiant de compte n'y paraît, seulement « posée » ou « vide ».
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Une valeur de réglage, dite sans rien révéler : une clé ou un secret devient « posée » ou « vide ».
 *
 * @param string $nom    Le nom du réglage.
 * @param mixed  $valeur Sa valeur.
 */
function er_reglage_lisible( $nom, $valeur ) {
	if ( preg_match( '/key|secret|token|webhook|account|password|signature|id$/i', $nom ) ) {
		return ( is_array( $valeur ) ? (bool) array_filter( $valeur ) : '' !== trim( (string) $valeur ) ) ? 'posée' : 'vide';
	}
	if ( is_array( $valeur ) ) {
		return $valeur ? implode( ', ', array_map( 'strval', array_filter( $valeur, 'is_scalar' ) ) ) : '—';
	}
	if ( 'yes' === $valeur || true === $valeur ) {
		return 'oui';
	}
	if ( 'no' === $valeur || false === $valeur || '' === $valeur ) {
		return 'non';
	}
	return null; // Le reste (textes, couleurs…) ne s'affiche pas.
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Les extensions installées, puis l'état du paiement (Stripe), de la livraison et des langues.
	 */
	WP_CLI::add_command(
		'er extensions',
		function () {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$ligne = function ( $libelle, $valeur ) {
				WP_CLI::log( '  ' . $libelle . str_repeat( ' ', max( 1, 40 - mb_strlen( $libelle ) ) ) . $valeur );
			};

			WP_CLI::log( 'Le site' );
			$ligne( 'WordPress', get_bloginfo( 'version' ) );
			$ligne( 'PHP', PHP_VERSION );
			$ligne( 'Langue du site', get_locale() );
			$ligne( 'Ouvert aux moteurs de recherche', get_option( 'blog_public' ) ? 'oui' : 'non' );

			WP_CLI::log( 'Les extensions' );
			$mises_a_jour = get_site_transient( 'update_plugins' );
			foreach ( get_plugins() as $fichier => $ext ) {
				$maj = isset( $mises_a_jour->response[ $fichier ] ) ? ' (mise à jour : ' . $mises_a_jour->response[ $fichier ]->new_version . ')' : '';
				$ligne( $ext['Name'], $ext['Version'] . ( is_plugin_active( $fichier ) ? ', active' : ', inactive' ) . $maj );
			}

			WP_CLI::log( 'Le paiement : Stripe' );
			$stripe = get_option( 'woocommerce_stripe_settings', array() );
			if ( ! $stripe ) {
				$ligne( 'Réglages', 'aucun (pas encore relié)' );
			} else {
				foreach ( array( 'enabled', 'testmode', 'test_publishable_key', 'test_secret_key', 'test_webhook_secret', 'publishable_key', 'secret_key', 'webhook_secret', 'test_connection_type', 'connection_type', 'capture', 'express_checkout', 'express_checkout_button_locations', 'upe_checkout_experience_accepted_payments', 'saved_cards', 'logging' ) as $nom ) {
					if ( array_key_exists( $nom, $stripe ) ) {
						$valeur = er_reglage_lisible( $nom, $stripe[ $nom ] );
						if ( null === $valeur && in_array( $nom, array( 'test_connection_type', 'connection_type' ), true ) ) {
							$valeur = sanitize_key( $stripe[ $nom ] ); // oauth, app ou manual : pas un secret.
						}
						if ( null !== $valeur ) {
							$ligne( $nom, $valeur );
						}
					}
				}
			}

			WP_CLI::log( 'La commande' );
			$ligne( 'Achat sans compte', 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ? 'oui' : 'non' );
			$ligne( 'Taxes calculées', 'yes' === get_option( 'woocommerce_calc_taxes' ) ? 'oui' : 'non' );
			$ligne( 'Prix saisis TTC', 'yes' === get_option( 'woocommerce_prices_include_tax' ) ? 'oui' : 'non' );
			if ( class_exists( 'WC_Shipping_Zones' ) ) {
				$zones   = WC_Shipping_Zones::get_zones();
				$zones[] = array(
					'zone_name'        => 'Le reste du monde',
					'shipping_methods' => ( new WC_Shipping_Zone( 0 ) )->get_shipping_methods(),
				);
				foreach ( $zones as $zone ) {
					$methodes = array();
					foreach ( $zone['shipping_methods'] as $m ) {
						$methodes[] = $m->get_title() . ( 'yes' === $m->enabled ? '' : ' (éteinte)' );
					}
					$ligne( 'Zone « ' . $zone['zone_name'] . ' »', $methodes ? implode( ', ', $methodes ) : '—' );
				}
			}

			WP_CLI::log( 'Les langues' );
			if ( function_exists( 'pll_languages_list' ) ) {
				$defaut = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
				foreach ( pll_languages_list( array( 'fields' => '' ) ) as $l ) {
					$ligne( $l->name, $l->locale . ( $l->slug === $defaut ? ', par défaut' : '' ) );
				}
				$pll = get_option( 'polylang', array() );
				foreach ( array( 'force_lang', 'hide_default', 'rewrite', 'browser', 'redirect_lang', 'media_support' ) as $nom ) {
					if ( isset( $pll[ $nom ] ) ) {
						$ligne( $nom, is_scalar( $pll[ $nom ] ) ? (string) $pll[ $nom ] : '…' );
					}
				}
				foreach ( array( 'post_types', 'taxonomies', 'sync' ) as $nom ) {
					$ligne( $nom, ! empty( $pll[ $nom ] ) ? implode( ', ', (array) $pll[ $nom ] ) : '—' );
				}
			} else {
				$ligne( 'Polylang', 'absent' );
			}
		}
	);
}
