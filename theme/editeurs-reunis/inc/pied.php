<?php
/**
 * Le pied de page : très espacé, l'inscription à la lettre, l'arborescence du site.
 *
 * Retenu le 26 novembre 2026, planche « P · Le blanc » du canevas « Une nouvelle page d'accueil, aérée », avec les
 * corrections d'André : le titre « S’inscrire à la newsletter », sans phrase dessous ; le bouton réduit à sa flèche ;
 * les horaires sans la ligne des jours de fermeture. Trois étages, séparés par beaucoup d'air :
 * 1. l'inscription, en grand, et son champ ;
 * 2. l'arborescence en cinq colonnes, un point de couleur par maison : la librairie (ses rayons, tirés des catégories de
 *    WooCommerce, comme le menu), YMCA-Press et le centre culturel (er_pages_des_maisons(), comme l'en-tête), nous
 *    rendre visite, les informations ;
 * 3. tout en bas, en petit : le nom, les bénévoles et les partenaires, les langues, le ©.
 * L'inscription est reçue par l'extension de la librairie (inc/lettre.php).
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les rayons de la librairie, dans l'ordre du menu.
 */
function er_pied_rayons() {
	$termes = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $termes ) ) {
		return array();
	}
	$termes = array_filter(
		$termes,
		function ( $t ) {
			return ! in_array( $t->slug, array( 'uncategorized', 'sans-rayon' ), true );
		}
	);
	usort(
		$termes,
		function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true );
		}
	);
	$liens = array();
	foreach ( $termes as $t ) {
		$traduit = function_exists( 'er_traduction_terme' ) ? er_traduction_terme( $t ) : $t;
		$liens[] = array( er_rayon( $t->slug ), $traduit->name );
	}
	return $liens;
}

/**
 * Une colonne de l'arborescence.
 *
 * @param string      $titre  Son titre.
 * @param string|null $maison librairie, ymca, centre, ou rien (sans point de couleur).
 * @param array       $liens  [ url, texte ].
 */
function er_pied_colonne( $titre, $maison, $liens ) {
	$lis = '';
	foreach ( $liens as $l ) {
		$lis .= '<li><a href="' . esc_url( $l[0] ) . '">' . esc_html( $l[1] ) . '</a></li>';
	}
	return '<div class="er-pd-colonne"><h2 class="er-pd-tete">' . ( $maison ? '<span class="er-pd-point" data-maison="' . esc_attr( $maison ) . '" aria-hidden="true"></span>' : '' ) . esc_html( $titre ) . '</h2>'
		. '<ul class="er-pd-liste">' . $lis . '</ul></div>';
}

/**
 * Le pied.
 */
function er_pied_html() {
	// L'inscription : le formulaire, ou ce qu'il en est après l'envoi (?lettre=merci ou ?lettre=erreur).
	$etat = isset( $_GET['lettre'] ) ? sanitize_key( wp_unslash( $_GET['lettre'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- un simple retour d'état, rien n'est enregistré.
	if ( 'merci' === $etat ) {
		$formulaire = '<p class="er-pd-reponse" role="status">' . esc_html__( 'Merci, c’est noté : vous recevrez la prochaine lettre.', 'editeurs-reunis' ) . '</p>';
	} else {
		$formulaire = '<form class="er-pd-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. '<input type="hidden" name="action" value="er_lettre">'
			. wp_referer_field( false )
			// Un champ que seuls les robots remplissent.
			. '<p class="er-pd-piege" aria-hidden="true"><label>' . esc_html__( 'Ne pas remplir', 'editeurs-reunis' ) . ' <input type="text" name="site" tabindex="-1" autocomplete="off"></label></p>'
			. '<label class="screen-reader-text" for="er-pd-courriel">' . esc_html__( 'Votre adresse électronique', 'editeurs-reunis' ) . '</label>'
			. '<input id="er-pd-courriel" class="er-pd-champ" type="email" name="courriel" required placeholder="' . esc_attr__( 'Votre adresse électronique', 'editeurs-reunis' ) . '" autocomplete="email"' . ( 'erreur' === $etat ? ' aria-invalid="true" aria-describedby="er-pd-erreur"' : '' ) . '>'
			. '<button class="er-pd-bouton" type="submit"><span class="screen-reader-text">' . esc_html__( 'S’inscrire', 'editeurs-reunis' ) . '</span>'
			. '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>'
			. '</form>'
			. ( 'erreur' === $etat ? '<p id="er-pd-erreur" class="er-pd-reponse est-erreur" role="alert">' . esc_html__( 'Cette adresse ne semble pas valide : pouvez-vous la vérifier ?', 'editeurs-reunis' ) . '</p>' : '' );
	}

	// L'arborescence.
	$librairie = er_pied_rayons();
	$librairie[] = array( er_mention( 'nouveaute' ), _x( 'Nouveautés', 'menu de la librairie', 'editeurs-reunis' ) );
	$librairie[] = array( add_query_arg( 'orderby', 'popularity', er_lien( 'librairie/' ) ), _x( 'Nos meilleures ventes', 'menu de la librairie', 'editeurs-reunis' ) );
	$librairie[] = array( er_lien( 'promos/' ), _x( 'Promos', 'menu de la librairie', 'editeurs-reunis' ) );
	$librairie[] = array( er_lien( 'librairie/encyclopedie/' ), __( 'L’encyclopédie', 'editeurs-reunis' ) );

	$maisons = er_pages_des_maisons();
	$pages   = function ( $maison ) use ( $maisons ) {
		$liens = array();
		foreach ( $maisons[ $maison ] as $chemin => $nom ) {
			$liens[] = array( er_lien( $chemin ), $nom );
		}
		return $liens;
	};

	$compte = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : er_lien( 'mon-compte/' );
	$infos  = array(
		array( $compte, __( 'Mon compte', 'editeurs-reunis' ) ),
		array( er_lien( 'conditions-generales-de-vente/' ), __( 'Conditions générales de vente', 'editeurs-reunis' ) ),
		array( er_lien( 'livraison-et-retours/' ), __( 'Livraison et retours', 'editeurs-reunis' ) ),
		array( er_lien( 'faq/' ), __( 'Questions fréquentes', 'editeurs-reunis' ) ),
		array( er_lien( 'politique-de-confidentialite/' ), __( 'Politique de confidentialité', 'editeurs-reunis' ) ),
		array( er_lien( 'mentions-legales/' ), __( 'Mentions légales', 'editeurs-reunis' ) ),
	);

	$visite = '<div class="er-pd-colonne"><h2 class="er-pd-tete">' . esc_html__( 'Nous rendre visite', 'editeurs-reunis' ) . '</h2>'
		. '<p class="er-pd-texte">11, rue de la Montagne-Sainte-Geneviève<br>75005 Paris</p>'
		. '<p class="er-pd-texte">' . esc_html__( 'Du mardi au samedi', 'editeurs-reunis' ) . '<br>' . wp_kses_post( __( 'de 10&nbsp;h&nbsp;30 à 18&nbsp;h&nbsp;30', 'editeurs-reunis' ) ) . '</p>'
		// Des traits d'union insécables : l'adresse ne se coupe qu'avant l'arobase.
		. '<p class="er-pd-texte"><a href="mailto:les-editeurs-reunis@orange.fr">' . "les\u{2011}editeurs\u{2011}reunis<wbr>@orange.fr" . '</a></p>'
		. '<p class="er-pd-texte"><a class="er-pd-souligne" href="' . er_lien( 'nous-trouver/' ) . '">' . esc_html_x( 'Nous trouver', 'menu du centre culturel', 'editeurs-reunis' ) . '</a></p>'
		. '</div>';

	$colonnes = er_pied_colonne( __( 'La librairie', 'editeurs-reunis' ), 'librairie', $librairie )
		. er_pied_colonne( 'YMCA-Press', 'ymca', $pages( 'ymca' ) )
		. er_pied_colonne( __( 'Le centre culturel', 'editeurs-reunis' ), 'centre', $pages( 'centre' ) )
		. $visite
		. er_pied_colonne( __( 'Informations', 'editeurs-reunis' ), null, $infos );

	// Les langues : « Français · Русский · English », la langue de la page en noir.
	$langues = array();
	foreach ( function_exists( 'er_liens_langues' ) ? er_liens_langues() : array() as $l ) {
		$langues[] = '<a href="' . esc_url( $l['url'] ) . '" hreflang="' . esc_attr( $l['code'] ) . '" lang="' . esc_attr( $l['code'] ) . '"' . ( $l['actuelle'] ? ' class="est-actuelle" aria-current="true"' : '' ) . '>' . esc_html( $l['nom'] ) . '</a>';
	}

	return '<div class="er-pd">'
		. '<div class="er-pd-lettre" id="er-lettre">'
		. '<h2 class="er-pd-titre">' . esc_html__( 'S’inscrire à la newsletter', 'editeurs-reunis' ) . '</h2>'
		. '<div class="er-pd-inscription">' . $formulaire . '</div>'
		. '</div>'
		. '<nav class="er-pd-plan" aria-label="' . esc_attr__( 'Plan du site', 'editeurs-reunis' ) . '">' . $colonnes . '</nav>'
		. '<div class="er-pd-bas">'
		// Le même nom que dans l'en-tête (patterns/en-tete.php), aux mêmes mesures (assets/css/26-pied.css).
		. '<p class="er-logo"><a href="' . er_lien() . '" rel="home"><span>' . esc_html_x( 'Centre culturel', 'logo, première ligne', 'editeurs-reunis' ) . '</span> <span>' . esc_html_x( 'Alexandre Soljenitsyne', 'logo, seconde ligne', 'editeurs-reunis' ) . '</span></a></p>'
		. '<p class="er-pd-petit">' . esc_html__( 'Notre équipe est uniquement composée de bénévoles.', 'editeurs-reunis' ) . '<br>'
		. '<a class="er-pd-souligne" href="' . er_lien( 'ymca-press/amis-ymca-press/' ) . '">' . esc_html__( 'Rejoindre l’Association des Amis d’YMCA-Press', 'editeurs-reunis' ) . '</a><br>'
		. wp_kses_post( __( 'Avec nos partenaires l’ACER-MJO, Paris 15<sup>e</sup>, et la Fondation Michalski, Montricher (Suisse).', 'editeurs-reunis' ) ) . '</p>'
		. '<p class="er-pd-petit er-pd-fin">' . ( $langues ? '<span class="er-pd-langues">' . implode( ' · ', $langues ) . '</span><br>' : '' ) . '© ' . esc_html( gmdate( 'Y' ) ) . ' Les Éditeurs réunis</p>'
		. '</div>'
		. '</div>';
}

// En code court : la partie « footer » du thème l'appelle dans un bloc HTML (déplié par le filtre de
// render_block_core/html, functions.php).
add_shortcode( 'er_pied', 'er_pied_html' );
