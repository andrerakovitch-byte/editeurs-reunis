<?php
/**
 * Les données structurées (plugin/er-librairie/inc/donnees-structurees.php, et le fil de la tête du thème).
 *
 * @package editeurs-reunis
 */

function test_ld_dates_et_lieux_ecrits_en_francais() {
	egal( array( 'date' => '1918-12-11', 'lieu' => 'Kislovodsk' ), er_ld_date_lieu( 'Kislovodsk, 11 décembre 1918' ) );
	egal( array( 'date' => '2008-08', 'lieu' => 'Moscou' ), er_ld_date_lieu( 'Moscou, août 2008' ) );
	egal( array( 'date' => '1881-02-01', 'lieu' => 'Saint-Pétersbourg' ), er_ld_date_lieu( 'Saint-Pétersbourg, 1er février 1881' ) );
	egal( array( 'date' => '1937', 'lieu' => '' ), er_ld_date_lieu( '1937' ) );
	egal( array( 'date' => '', 'lieu' => 'Paris' ), er_ld_date_lieu( 'Paris' ) );
}

function test_ld_langues_des_livres() {
	egal( 'ru', er_ld_langue( 'russe' ) );
	egal( 'fr', er_ld_langue( 'francais' ) );
	egal( array( 'ru', 'fr' ), er_ld_langue( 'bilingue-russe-francais' ) );
	egal( '', er_ld_langue( 'klingon' ) );
}

function test_ld_un_texte_ne_ferme_pas_la_balise_script() {
	$html = er_ld_script( array( 'name' => 'Livre </script><script>alert(1)</script>' ) );
	egal( 1, substr_count( $html, '</script>' ), 'un seul </script>, le vrai' );
	vrai( false !== strpos( $html, '</script>' ), 'les balises échappées' );
}

function test_ld_fil_d_ariane() {
	$fil = er_ld_fil( array( array( 'La librairie', 'https://exemple.fr/librairie/' ), array( 'Poésie', 'https://exemple.fr/poesie/' ), array( 'Requiem', 'https://exemple.fr/requiem/' ) ) );
	egal( 'BreadcrumbList', $fil['@type'] );
	egal( 3, $fil['itemListElement'][2]['position'] );
	egal( 'Poésie', $fil['itemListElement'][1]['name'] );
}

function test_ld_fil_de_la_tete() {
	$html = er_tete_fil_ld( array( array( 'texte' => 'Le centre culturel', 'lien' => 'https://exemple.fr/centre-culturel/' ) ), 'Nos événements' );
	$ld   = json_decode( preg_replace( '#^<script[^>]*>|</script>$#', '', $html ), true );
	egal( 'Nos événements', $ld['itemListElement'][1]['name'] );
	vrai( ! isset( $ld['itemListElement'][1]['item'] ), 'la page elle-même, sans lien' );
	egal( '', er_tete_fil_ld( array(), 'Seule' ), 'pas de fil d’une seule étape' );
}
