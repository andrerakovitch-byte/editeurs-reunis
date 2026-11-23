<?php
/**
 * Polylang : la mise en place des trois langues, `wp er langues installer`.
 *
 * Polylang Pro et Polylang pour WooCommerce sont téléversés à la main dans l'administration (ils sont payants, leurs
 * archives ne vont pas dans ce dépôt public) ; le workflow « Déployer chez Kinsta » les active dès qu'ils sont là.
 * Cette commande règle ensuite le reste, et se relance sans dommage :
 * - les langues : le français (fr_FR, par défaut), le russe (ru_RU) et l'anglais (en_GB) ;
 * - les adresses : la langue dans le chemin, /ru/ et /en/, le français sans préfixe, l'accueil de chaque langue à
 *   /ru/ et /en/, aucune redirection d'après la langue du navigateur ;
 * - ce qui se traduit : wpml-config.xml en décide pour les rencontres, les auteurs, les langues des livres, les
 *   cycles, les intervenants et les sujets ; Polylang pour WooCommerce pour les livres, les rayons et les mentions ;
 * - ce qui se recopie d'une traduction à l'autre : les classements, les champs, l'image mise en avant, la date,
 *   l'ordre, la page parente et le gabarit ;
 * - tout le contenu qui n'a pas encore de langue passe en français.
 *
 * Les réglages restent modifiables dans Langues → Réglages. La clé DeepL (traduction automatique) et la licence se
 * saisissent là aussi, à la main.
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les langues du site pour Polylang, dans l'ordre du sélecteur.
 */
function er_langues_polylang() {
	return array(
		array(
			'name'       => 'Français',
			'slug'       => 'fr',
			'locale'     => 'fr_FR',
			'rtl'        => false,
			'flag'       => 'fr',
			'term_group' => 0,
		),
		array(
			'name'       => 'Русский',
			'slug'       => 'ru',
			'locale'     => 'ru_RU',
			'rtl'        => false,
			'flag'       => 'ru',
			'term_group' => 1,
		),
		array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_GB',
			'rtl'        => false,
			'flag'       => 'gb',
			'term_group' => 2,
		),
	);
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Met en place les trois langues (installer), passe le contenu sans langue en français (contenu), ou dit où elles
	 * en sont (sans argument).
	 *
	 * @param array $args installer, contenu, ou rien.
	 */
	WP_CLI::add_command(
		'er langues',
		function ( $args ) {
			if ( ! function_exists( 'PLL' ) || ! PLL() || empty( PLL()->model ) ) {
				WP_CLI::error( 'Polylang n’est pas actif : téléverser Polylang Pro et Polylang pour WooCommerce, puis relancer le workflow.' );
			}
			$installer = isset( $args[0] ) && 'installer' === $args[0];
			$modele    = PLL()->model;
			$pfwc      = defined( 'PLLWC_VERSION' ) || class_exists( 'Polylang_Woocommerce' );
			WP_CLI::log( 'Polylang ' . ( defined( 'POLYLANG_VERSION' ) ? POLYLANG_VERSION : '?' ) . ( defined( 'POLYLANG_PRO' ) ? ' (Pro)' : '' ) . ', Polylang pour WooCommerce : ' . ( $pfwc ? 'actif' : 'absent' ) );

			if ( $installer ) {
				// Les langues.
				$existe = function ( $slug ) use ( $modele ) {
					return isset( $modele->languages ) && method_exists( $modele->languages, 'get' ) ? $modele->languages->get( $slug ) : $modele->get_language( $slug );
				};
				foreach ( er_langues_polylang() as $langue ) {
					$deja = $existe( $langue['slug'] );
					if ( $deja ) {
						// Une langue créée à la main (l'assistant de Polylang) : on lui rend sa place dans le sélecteur.
						if ( (int) $deja->term_group !== $langue['term_group'] && isset( $modele->languages ) && method_exists( $modele->languages, 'update' ) ) {
							$modele->languages->update(
								array(
									'lang_id'    => $deja->term_id,
									'term_group' => $langue['term_group'],
								)
							);
							WP_CLI::log( 'Ordre rétabli : ' . $deja->name );
						}
						continue;
					}
					$ajouter  = function ( $l ) use ( $modele ) {
						return isset( $modele->languages ) && method_exists( $modele->languages, 'add' ) ? $modele->languages->add( $l ) : $modele->add_language( $l );
					};
					$resultat = $ajouter( $langue );
					if ( is_wp_error( $resultat ) && $resultat->get_error_code() && false !== strpos( $resultat->get_error_code(), 'flag' ) ) {
						unset( $langue['flag'] ); // Sans drapeau plutôt que sans langue.
						$resultat = $ajouter( $langue );
					}
					if ( is_wp_error( $resultat ) && $resultat->has_errors() ) {
						WP_CLI::warning( $langue['name'] . ' : ' . $resultat->get_error_message() );
					} else {
						WP_CLI::log( 'Langue ajoutée : ' . $langue['name'] );
					}
				}
				if ( isset( $modele->languages ) && method_exists( $modele->languages, 'update_default' ) ) {
					$modele->languages->update_default( 'fr' );
				}

				// Les adresses, et ce qui se recopie d'une traduction à l'autre.
				$reglages = array(
					'force_lang'    => 1,
					'hide_default'  => true,
					'rewrite'       => true,
					'redirect_lang' => true,
					'browser'       => false,
					'media_support' => false,
					'sync'          => array( 'taxonomies', 'post_meta', '_thumbnail_id', 'post_date', 'menu_order', 'post_parent', '_wp_page_template' ),
				);
				$options  = PLL()->options;
				foreach ( $reglages as $nom => $valeur ) {
					if ( is_object( $options ) && method_exists( $options, 'set' ) ) {
						$erreur = $options->set( $nom, $valeur );
						if ( $erreur->has_errors() ) {
							WP_CLI::warning( $nom . ' : ' . $erreur->get_error_message() );
						}
					} else {
						$options[ $nom ] = $valeur; // Polylang d'avant 3.7 : un tableau.
					}
				}
				if ( is_object( $options ) && method_exists( $options, 'save' ) ) {
					$options->save();
				} else {
					update_option( 'polylang', $options );
				}

				flush_rewrite_rules();
				WP_CLI::success( 'Les trois langues sont en place.' );
				// Le contenu sans langue passe en français : dans un nouveau processus, où Polylang, qui connaît
				// maintenant les langues, a lu wpml-config.xml et sait que les rencontres et les auteurs se traduisent.
				WP_CLI::runcommand(
					'er langues contenu',
					array(
						'launch'     => true,
						'exit_error' => false,
					)
				);
				return;
			}

			if ( isset( $args[0] ) && 'contenu' === $args[0] ) {
				$modele->set_language_in_mass(); // Par lots de mille, jusqu'au bout.
				WP_CLI::log( 'Le contenu sans langue est passé en français.' );
			}

			// Où on en est.
			$defaut = pll_default_language();
			foreach ( pll_languages_list( array( 'fields' => '' ) ) as $l ) {
				WP_CLI::log( '  ' . $l->name . str_repeat( ' ', max( 1, 12 - mb_strlen( $l->name ) ) ) . $l->locale . ( $l->slug === $defaut ? ', par défaut' : '' ) );
			}
			WP_CLI::log( '  Contenus traduisibles : ' . implode( ', ', $modele->get_translated_post_types() ) );
			WP_CLI::log( '  Classements traduisibles : ' . implode( ', ', $modele->get_translated_taxonomies() ) );
			$sans = $modele->get_objects_with_no_lang( 1000 );
			$n    = 0;
			foreach ( (array) $sans as $type => $ids ) {
				$n += is_array( $ids ) ? count( $ids ) : 0;
			}
			WP_CLI::log( '  Encore sans langue : ' . $n . ( $pfwc ? '' : ' (les livres ne comptent qu’avec Polylang pour WooCommerce)' ) );
		}
	);
}
