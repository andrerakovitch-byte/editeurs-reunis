<?php
/**
 * Les époques d'un livre, et ses langues.
 *
 * Deux classements, avec les mêmes quatorze périodes de l'histoire russe :
 * - er_ecriture, l'époque où le livre a été écrit (les Récits de la Kolyma : le Dégel et la stagnation) ;
 * - er_periode, l'époque dont il parle (les Récits de la Kolyma : les années 1930, la guerre, l'après-guerre).
 * Un livre peut en porter plusieurs. Les périodes sont fermées : on n'en crée pas d'autres, on les choisit.
 *
 * Les langues d'un livre (le classement « langue ») : français, russe, bilingue russe-français, anglais, et les
 * quelques autres du catalogue (slavon d'Église, ukrainien, italien, allemand). Une édition bilingue porte
 * « Bilingue russe-français », pas « français » et « russe » à la fois.
 *
 * `wp er epoques semer` crée les périodes et les langues qui manquent (l'import des étiquettes le fait aussi).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les périodes, dans l'ordre : code → [ slug, nom, années ].
 */
function er_epoques() {
	return array(
		'pc'   => array( 'premiers-siecles-et-byzance', 'Les premiers siècles chrétiens et Byzance', 'jusqu’au XVe siècle' ),
		'ra'   => array( 'russie-ancienne', 'La Russie ancienne', 'jusqu’en 1700' ),
		'x18'  => array( 'xviiie-siecle', 'Le XVIIIe siècle', '1700-1800' ),
		'or'   => array( 'age-d-or', 'L’Âge d’or', '1800-1855' ),
		'gr'   => array( 'grand-roman-et-reformes', 'Le grand roman et les réformes', '1855-1890' ),
		'ar'   => array( 'age-d-argent', 'L’Âge d’argent', '1890-1917' ),
		'rev'  => array( 'revolution-et-guerre-civile', 'La révolution et la guerre civile', '1917-1922' ),
		'2030' => array( 'annees-1920-et-1930', 'Les années 1920 et 1930', '1922-1941' ),
		'ww2'  => array( 'la-guerre', 'La guerre', '1941-1945' ),
		'deg'  => array( 'apres-guerre-et-degel', 'L’après-guerre et le Dégel', '1945-1964' ),
		'sta'  => array( 'stagnation', 'La stagnation', '1964-1985' ),
		'per'  => array( 'perestroika', 'La perestroïka et la fin de l’URSS', '1985-1991' ),
		'90'   => array( 'annees-1990', 'Les années 1990', '1991-1999' ),
		'pou'  => array( 'russie-de-poutine', 'La Russie de Poutine', 'depuis 2000' ),
	);
}

/**
 * Les langues des livres : code → [ slug, nom ].
 */
function er_langues_livres() {
	return array(
		'fr' => array( 'francais', 'Français' ),
		'ru' => array( 'russe', 'Russe' ),
		'bi' => array( 'bilingue-russe-francais', 'Bilingue russe-français' ),
		'en' => array( 'anglais', 'Anglais' ),
		'cu' => array( 'slavon', 'Slavon d’Église' ),
		'uk' => array( 'ukrainien', 'Ukrainien' ),
		'it' => array( 'italien', 'Italien' ),
		'de' => array( 'allemand', 'Allemand' ),
	);
}

add_action(
	'init',
	function () {
		$classements = array(
			'er_ecriture' => array( __( 'Époques d’écriture', 'er-librairie' ), __( 'Époque d’écriture', 'er-librairie' ), 'ecrit' ),
			'er_periode'  => array( __( 'Époques traitées', 'er-librairie' ), __( 'Époque traitée', 'er-librairie' ), 'epoque' ),
		);
		foreach ( $classements as $taxonomie => list( $nom, $un, $slug ) ) {
			register_taxonomy(
				$taxonomie,
				array( 'product' ),
				array(
					'labels'            => array(
						'name'          => $nom,
						'singular_name' => $un,
						'all_items'     => $nom,
						'menu_name'     => $nom,
					),
					'public'            => true,
					'show_in_rest'      => true,
					'show_admin_column' => false,
					'hierarchical'      => true, // Des cases à cocher dans l'administration : on choisit, on ne crée pas.
					'rewrite'           => array( 'slug' => $slug ),
				)
			);
		}
	},
	8
);

/**
 * Les époques d'un livre, dans l'ordre de l'histoire.
 *
 * @param int    $id        Le livre.
 * @param string $taxonomie er_ecriture ou er_periode.
 * @return WP_Term[]
 */
function er_epoques_du_livre( $id, $taxonomie ) {
	$termes = wp_get_post_terms( $id, $taxonomie );
	if ( ! $termes || is_wp_error( $termes ) ) {
		return array();
	}
	$rang = array_flip( array_column( er_epoques(), 0 ) );
	usort( $termes, fn( $a, $b ) => ( $rang[ $a->slug ] ?? 99 ) <=> ( $rang[ $b->slug ] ?? 99 ) );
	return $termes;
}

/**
 * Crée un terme s'il manque, en français pour Polylang.
 *
 * @param string $taxonomie   Le classement.
 * @param string $slug        Son slug.
 * @param string $nom         Son nom.
 * @param string $description Sa description.
 * @return int|null L'identifiant du terme.
 */
function er_terme_assurer( $taxonomie, $slug, $nom, $description = '' ) {
	$t = get_term_by( 'slug', $slug, $taxonomie );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$cree = wp_insert_term( $nom, $taxonomie, array( 'slug' => $slug, 'description' => $description ) );
	if ( is_wp_error( $cree ) ) {
		return null;
	}
	if ( function_exists( 'pll_set_term_language' ) && function_exists( 'pll_is_translated_taxonomy' ) && pll_is_translated_taxonomy( $taxonomie ) ) {
		pll_set_term_language( $cree['term_id'], 'fr' );
	}
	return (int) $cree['term_id'];
}

/**
 * Crée les périodes et les langues qui manquent.
 *
 * @return int Le nombre de termes créés.
 */
function er_epoques_semer() {
	$crees = 0;
	foreach ( array( 'er_ecriture', 'er_periode' ) as $taxonomie ) {
		foreach ( er_epoques() as list( $slug, $nom, $annees ) ) {
			if ( ! get_term_by( 'slug', $slug, $taxonomie ) && er_terme_assurer( $taxonomie, $slug, $nom, $annees ) ) {
				++$crees;
			}
		}
	}
	foreach ( er_langues_livres() as list( $slug, $nom ) ) {
		if ( ! get_term_by( 'slug', $slug, 'langue' ) && er_terme_assurer( 'langue', $slug, $nom ) ) {
			++$crees;
		}
	}
	return $crees;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'er epoques semer',
		function () {
			WP_CLI::success( sprintf( '%d termes créés.', er_epoques_semer() ) );
		}
	);
}
