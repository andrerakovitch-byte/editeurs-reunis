<?php
/**
 * La bande de page et la tête de page (theme/editeurs-reunis/inc/bande.php et tete.php).
 *
 * @package editeurs-reunis
 */

function test_bande_quatre_annees_puis_les_autres_repliees() {
	$e = er_bande_annees( array( 2026, 2025, 2024, 2023, 2022, 2021 ), fn( $an ) => '?an=' . $an, '2025', 'Plus anciens' );
	egal( 5, count( $e ) );
	egal( array( '2026', '2025', '2024', '2023' ), array_column( array_slice( $e, 0, 4 ), 'texte' ) );
	egal( true, $e[1]['courant'] );
	egal( '?an=2024', $e[2]['lien'] );
	egal( 'Plus anciens', $e[4]['menu'] );
	egal( array( '2022', '2021' ), array_column( $e[4]['entrees'], 'texte' ) );
	egal( 3, count( er_bande_annees( array( 2026, 2025, 2024 ), fn( $an ) => '', '', 'Plus anciens' ) ), 'rien à replier' );
}

function test_bande_ancres_uniques() {
	egal( 'les-annees-1920', er_bande_ancre( 'Les années 1920' ) );
	egal( 'les-annees-1920-2', er_bande_ancre( 'Les années 1920' ) );
	egal( 'partie', er_bande_ancre( '<em></em>' ) );
}

function test_bande_vide_sous_deux_liens() {
	egal( '', er_bande_page( array( 'entrees' => array( array( 'texte' => 'Seule', 'lien' => '#a' ) ) ) ) );
}

function test_bande_entree_courante_et_menu() {
	$html = er_bande_page(
		array(
			'maison'  => 'centre',
			'nom'     => 'Nos événements',
			'entrees' => array(
				array( 'texte' => 'À venir', 'lien' => '#a-venir', 'courant' => true ),
				array( 'texte' => '2025', 'lien' => '?an=2025' ),
				array(
					'menu'    => 'Plus anciens',
					'entrees' => array( array( 'texte' => '2019', 'lien' => '?an=2019', 'courant' => true ) ),
				),
			),
		)
	);
	vrai( false !== strpos( $html, 'class="er-bande er-bande-centre"' ), 'la classe de la maison' );
	vrai( false !== strpos( $html, 'href="#a-venir" aria-current="page"' ), 'l’entrée courante' );
	vrai( false !== strpos( $html, 'Plus anciens : 2019' ), 'un menu replié dit l’entrée choisie' );
	vrai( false !== strpos( $html, 'aria-expanded="false"' ), 'le bouton du menu' );
}

function test_plus_de_sommaire_automatique_sur_une_page_de_texte() {
	// Retiré le 28 nov. 2026 : aucun filtre ne glisse plus de bande dans le contenu d'une page.
	vrai( empty( $GLOBALS['er_test_crochets']['render_block_core/post-content'] ), 'pas de sommaire glissé dans le contenu' );
}

function test_chapo_d_un_terme_son_premier_paragraphe() {
	egal( 'Premier paragraphe.', er_tete_chapo_terme( new WP_Term( array( 'description' => "Premier paragraphe.\n\nSecond." ) ) ) );
	egal( 'Un & deux', er_tete_chapo_terme( new WP_Term( array( 'description' => '<p>Un &amp; deux</p><p>Trois</p>' ) ) ) );
	egal( '', er_tete_chapo_terme( new WP_Term( array( 'description' => '  ' ) ) ) );
}
