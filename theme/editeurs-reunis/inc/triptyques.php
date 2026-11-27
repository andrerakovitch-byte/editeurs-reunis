<?php
/**
 * Les triptyques de la tête de page (26 nov. 2026, la planche « Un triptyque par page, pour la photographe » du
 * canevas « Les pages intérieures, dans la direction ») : trois images sans texte, à droite du grand titre, qui
 * illustrent la page ouverte ; ce ne sont pas des liens (André, 26 nov.). Ce sont des triptyques d'essai, en attendant
 * les séries de la photographe.
 *
 * - Six pages ont le leur (Nos expositions, Nos événements, la librairie, la maison d'édition, Un siècle d'histoire,
 *   Nous trouver) ; les autres pages prennent celui de leur maison (er_triptyque_de_la_page()).
 * - Le cadrage de chaque image est celui qu'André a réglé sur la planche : le zoom, et le point (horizontal, vertical,
 *   en %) vers lequel on zoome. Le CSS le rend tel quel (object-position, transform-origin, scale).
 * - Nos photos sont dans le thème (assets/images/triptyques/). Les filés de la Comédie-Française, qui servent de
 *   références sur la bêta, ne vont pas dans le dépôt, qui est public : on les dépose dans la médiathèque de WordPress
 *   sous leur nom (er-cf-bleu-nuit.jpg…), et le thème les y trouve ; tant qu'ils n'y sont pas, leur place est un aplat
 *   de leur teinte.
 *
 * @package editeurs-reunis
 */

/**
 * Les images : la source (le thème, ou la médiathèque pour un filé), la teinte de repli, le cadrage d'André.
 */
function er_triptyque_images() {
	return array(
		// Nos photos.
		'photographe'   => array( 'theme' => 'triptyques/photographe.jpg', 'cadre' => array( 2.05, 61, 44 ) ),
		'signature'     => array( 'theme' => 'triptyques/signature.jpg', 'cadre' => array( 1.75, 42, 4 ) ),
		'lecteur'       => array( 'theme' => 'triptyques/lecteur.jpg', 'cadre' => array( 1.65, 47, 6 ) ),
		'deux-lecteurs' => array( 'theme' => 'triptyques/deux-lecteurs.jpg', 'cadre' => array( 1.82, 65, 67 ) ),
		'vermont'       => array( 'theme' => 'triptyques/vermont.jpg', 'cadre' => array( 1.03, 42, 0 ) ),
		'congres'       => array( 'theme' => 'une-congres.jpg', 'cadre' => array( 2.95, 46, 36 ) ),
		'imprimerie'    => array( 'theme' => 'une-imprimerie.jpg', 'cadre' => array( 1.85, 71, 39 ) ),
		'groupe'        => array( 'theme' => 'triptyques/groupe.jpg', 'cadre' => array( 1.45, 35, 0 ) ),
		'facade'        => array( 'theme' => 'triptyques/facade.jpg', 'cadre' => array( 1, 100, 50 ) ),
		'peintre'       => array( 'theme' => 'triptyques/peintre.jpg', 'cadre' => array( 1.91, 15, 0 ) ),
		'khokhloma'     => array( 'theme' => 'triptyques/khokhloma.jpg', 'cadre' => array( 1.5, 0, 0 ) ),
		// Les filés de la Comédie-Française, des références : dans la médiathèque seulement.
		'cf-bleu-nuit'  => array( 'media' => 'er-cf-bleu-nuit', 'teinte' => '#21303F', 'cadre' => array( 1, 50, 42 ) ),
		'cf-ciel-pale'  => array( 'media' => 'er-cf-ciel-pale', 'teinte' => '#848BA3', 'cadre' => array( 1, 50, 50 ) ),
		'cf-orange'     => array( 'media' => 'er-cf-orange', 'teinte' => '#503828', 'cadre' => array( 1, 50, 56 ) ),
		'cf-or-brun'    => array( 'media' => 'er-cf-or-brun', 'teinte' => '#754B1D', 'cadre' => array( 1, 50, 50 ) ),
		'cf-creme'      => array( 'media' => 'er-cf-creme', 'teinte' => '#8B7969', 'cadre' => array( 1, 50, 50 ) ),
		'cf-fenetre'    => array( 'media' => 'er-cf-fenetre', 'teinte' => '#5D4F44', 'cadre' => array( 1, 50, 50 ) ),
		'cf-visage'     => array( 'media' => 'er-cf-visage', 'teinte' => '#462E24', 'cadre' => array( 1, 50, 50 ) ),
	);
}

/**
 * Les triptyques : trois images chacun.
 */
function er_triptyques() {
	return array(
		'expositions' => array( 'cf-bleu-nuit', 'photographe', 'cf-ciel-pale' ),
		'evenements'  => array( 'signature', 'lecteur', 'deux-lecteurs' ),
		'librairie'   => array( 'cf-creme', 'cf-fenetre', 'cf-visage' ),
		'edition'     => array( 'cf-orange', 'cf-or-brun', 'vermont' ),
		'histoire'    => array( 'congres', 'imprimerie', 'groupe' ),
		'trouver'     => array( 'facade', 'peintre', 'khokhloma' ),
		// L'accueil, sous la phrase, bord à bord (Y3b, 26 nov.) : les filés de la librairie, en attendant les siens.
		'accueil'     => array( 'cf-creme', 'cf-fenetre', 'cf-visage' ),
	);
}

/**
 * Le triptyque de la page qu'on regarde : le sien (six pages), sinon celui de sa maison.
 *
 * @param string $maison La maison de la page (librairie, ymca, centre, ou rien).
 */
function er_triptyque_de_la_page( $maison ) {
	$chemin = trim( (string) ( function_exists( 'er_chemin_reference' ) ? er_chemin_reference() : '' ), '/' );
	$pages  = array(
		'centre-culturel/expositions' => 'expositions',
		'centre-culturel/evenements'  => 'evenements',
		'ymca-press'                  => 'edition',
		'ymca-press/histoire'         => 'histoire',
		'nous-trouver'                => 'trouver',
	);
	if ( isset( $pages[ $chemin ] ) ) {
		return $pages[ $chemin ];
	}
	if ( 0 === strpos( $chemin, 'centre-culturel/evenements' ) || 0 === strpos( $chemin, 'centre-culturel/essai' ) ) {
		return 'evenements';
	}
	$maisons = array(
		'librairie' => 'librairie',
		'ymca'      => 'edition',
		'centre'    => 'evenements',
	);
	return isset( $maisons[ $maison ] ) ? $maisons[ $maison ] : 'trouver';
}

/**
 * L'adresse d'un filé déposé dans la médiathèque, d'après son nom ; '' s'il n'y est pas encore.
 *
 * @param string $nom Le nom du fichier, sans extension (er-cf-bleu-nuit).
 */
function er_triptyque_media( $nom ) {
	static $trouves = array();
	if ( ! isset( $trouves[ $nom ] ) ) {
		$ids             = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'name'           => $nom,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'lang'           => '',
			)
		);
		$trouves[ $nom ] = $ids ? (string) wp_get_attachment_image_url( $ids[0], 'large' ) : '';
	}
	return $trouves[ $nom ];
}

/**
 * Un triptyque, en HTML : trois images cadrées (ou l'aplat d'un filé absent), pour l'œil seulement.
 *
 * @param string $cle expositions, evenements, librairie, edition, histoire, trouver.
 */
function er_triptyque_html( $cle ) {
	$tous   = er_triptyques();
	$images = er_triptyque_images();
	if ( ! isset( $tous[ $cle ] ) ) {
		return '';
	}
	$html    = '';
	$dernier = count( $tous[ $cle ] ) - 1;
	foreach ( array_values( $tous[ $cle ] ) as $rang => $nom ) {
		$i             = $images[ $nom ];
		list( $z, $x, $y ) = $i['cadre'];
		$src           = isset( $i['theme'] ) ? er_image( $i['theme'] ) : er_triptyque_media( $i['media'] );
		$style         = sprintf( '--er-tpq-x:%1$s%%;--er-tpq-y:%2$s%%;--er-tpq-z:%3$s', $x, $y, $z );
		// L'accueil (31-accueil-phrase.css) : au téléphone, seule la photo de droite se voit, et c'est la plus grande
		// image de l'écran. Les deux autres n'y téléchargent rien (une source vide), et elle passe en premier
		// (audit du 27 nov. 2026 : 1 s de gagnée sur l'image principale, en 4G).
		$img = '<img src="' . esc_url( $src ) . '" alt="" decoding="async"' . ( 'accueil' === $cle && $rang === $dernier ? ' fetchpriority="high"' : '' ) . ' style="' . esc_attr( $style ) . '">';
		if ( $src && 'accueil' === $cle && $rang !== $dernier ) {
			$img = '<picture><source media="(max-width: 781px)" srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">' . $img . '</picture>';
		}
		$contenu = $src ? $img : '<span class="er-tpq-aplat" style="background-color:' . esc_attr( $i['teinte'] ) . '"></span>';
		$html   .= '<span class="er-tpq-image">' . $contenu . '</span>';
	}
	return '<div class="er-tpq" data-triptyque="' . esc_attr( $cle ) . '" aria-hidden="true">' . $html . '</div>';
}
