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

/**
 * Le contenu d'une page, avec des intertitres.
 *
 * @param string[] $titres Les intertitres.
 * @param string   $avant  Ce qui précède chacun.
 */
function er_test_contenu( $titres, $avant = '' ) {
	$html = '<div class="entry-content wp-block-post-content">';
	foreach ( $titres as $i => $t ) {
		$html .= sprintf( $avant, $i + 1 ) . '<h2 class="wp-block-heading">' . $t . '</h2><p>Texte.</p>';
	}
	return $html . '</div>';
}

function test_sommaire_automatique_des_trois_intertitres() {
	er_test_page( array( 'page' => true, 'id' => 7 ) );
	er_test_article( 7, array( 'post_title' => 'Un siècle d’histoire' ) );
	$filtre = er_test_filtre( 'render_block_core/post-content' );
	$html   = $filtre(
		er_test_contenu(
			array( 'Les débuts', 'Le symbolisme : le monde comme signe', 'L’exil' ),
			'<p class="er-surtitre">Chapitre 0%d · 1940 – 1945</p>'
		),
		array()
	);
	vrai( 0 === strpos( $html, '<div class="entry-content wp-block-post-content"><div class="er-bande-repere"' ), 'la bande en tête du contenu, dans son enveloppe' );
	vrai( false !== strpos( $html, 'er-bande-sommaire' ), 'un sommaire' );
	vrai( false !== strpos( $html, '<h2 id="les-debuts" class="wp-block-heading">' ), 'un identifiant posé sur l’intertitre' );
	vrai( false !== strpos( $html, 'href="#les-debuts" aria-current="true"' ), 'la première entrée, courante' );
	vrai( false !== strpos( $html, '<span>Le symbolisme</span>' ), 'coupé aux deux-points' );
	vrai( false !== strpos( $html, '<span class="er-bande-avant">1940</span>' ), 'l’année du chapitre' );
}

function test_sommaire_coupe_aussi_avec_une_espace_insecable() {
	er_test_page( array( 'page' => true, 'id' => 8 ) );
	$html = er_test_filtre( 'render_block_core/post-content' )( er_test_contenu( array( 'Un', "Deux\u{00A0}: la suite", 'Trois' ) ), array() );
	vrai( false !== strpos( $html, '<span>Deux</span>' ), 'les deux-points précédés d’une espace insécable' );
}

function test_pas_de_sommaire_sans_trois_intertitres_ni_sur_une_page_qui_a_sa_bande() {
	er_test_page( array( 'page' => true, 'id' => 9 ) );
	$filtre = er_test_filtre( 'render_block_core/post-content' );
	$deux   = er_test_contenu( array( 'Un', 'Deux' ) );
	egal( $deux, $filtre( $deux, array() ) );
	$avec_bande = str_replace( '<p>Texte.</p>', '<nav class="er-bande"></nav>', er_test_contenu( array( 'Un', 'Deux', 'Trois' ) ) );
	egal( $avec_bande, $filtre( $avec_bande, array() ) );
	er_test_page( array( 'page' => false ) );
	$trois = er_test_contenu( array( 'Un', 'Deux', 'Trois' ) );
	egal( $trois, $filtre( $trois, array() ), 'seulement sur une page' );
}

function test_chapo_d_un_terme_son_premier_paragraphe() {
	egal( 'Premier paragraphe.', er_tete_chapo_terme( new WP_Term( array( 'description' => "Premier paragraphe.\n\nSecond." ) ) ) );
	egal( 'Un & deux', er_tete_chapo_terme( new WP_Term( array( 'description' => '<p>Un &amp; deux</p><p>Trois</p>' ) ) ) );
	egal( '', er_tete_chapo_terme( new WP_Term( array( 'description' => '  ' ) ) ) );
}
