<?php
/**
 * La page type d'une page de texte qui informe (28 nov. 2026, d'après « La Fondation » de la Fondation Cartier et les
 * pages thématiques de la National Academies) : la tête du gabarit « Page de texte » (le titre de la page en grand,
 * calé en bas d'un blanc de la taille d'un menu ouvert), le triptyque, un chapô en grand, puis des
 * modules qui racontent dans l'ordre où on les lit,
 * chacun réarrangeable dans l'éditeur (patterns/pt-*.php, catégorie « Les modules d'une page ») : le texte en colonne,
 * une section sur deux colonnes, les photos (triptyque, diptyque, photo en marge,
 * frise), le rendez-vous en aplat (la seule rupture de couleur), les livres, le journal, « Pour aller plus loin ».
 *
 * Les photos d'ambiance sont celles de la Comédie-Française, dans la médiathèque de la bêta (jamais dans le dépôt) :
 * elles donnent un climat, pas une illustration. Le texte de remplissage est du faux texte, en attendant le vrai.
 *
 * Ici : les photos (er_pt_photo), les trois modules qui lisent le site ([er_pt_rendez_vous], [er_pt_livres],
 * [er_pt_journal]) et la page « Page type » elle-même, créée et tenue à jour tant qu'on la travaille
 * (ER_PAGE_TYPE_VERSION). Le dessin : assets/css/32-page-type.css.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * La version du contenu de la page « Page type » : on l'augmente pour la récrire d'après les patterns, tant que la page
 * est un essai. Le jour où l'on y travaille dans l'éditeur, on cesse de l'augmenter.
 */
const ER_PAGE_TYPE_VERSION = 7;

/**
 * Une photo de la Comédie-Française, en bloc image (dans la médiathèque : er-cf-<nom>.jpg).
 *
 * @param string $nom     bleu-nuit, ciel-pale, creme, fenetre, or-brun, orange ou visage.
 * @param string $legende La légende, s'il y en a une.
 */
function er_pt_photo( $nom, $legende = '' ) {
	$url = content_url( 'uploads/2026/11/er-cf-' . sanitize_key( $nom ) . '.jpg' );
	$id  = function_exists( 'attachment_url_to_postid' ) ? (int) attachment_url_to_postid( $url ) : 0;
	$att = array(
		'sizeSlug'        => 'full',
		'linkDestination' => 'none',
	);
	if ( $id ) {
		$att = array( 'id' => $id ) + $att;
	}
	return '<!-- wp:image ' . wp_json_encode( $att ) . " -->\n"
		. '<figure class="wp-block-image size-full"><img src="' . esc_url( $url ) . '" alt=""' . ( $id ? ' class="wp-image-' . $id . '"' : '' ) . '/>'
		. ( '' !== $legende ? '<figcaption class="wp-element-caption">' . esc_html( $legende ) . '</figcaption>' : '' )
		. "</figure>\n<!-- /wp:image -->\n";
}

/**
 * Le rendez-vous en aplat : la prochaine rencontre, à défaut l'exposition du moment. Rien s'il n'y a ni l'une ni
 * l'autre (le module disparaît, 32-page-type.css).
 */
function er_pt_rendez_vous() {
	$maintenant = ( new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Paris' ) ) )->modify( '-2 hours' )->format( 'Y-m-d H:i' );
	$q          = new WP_Query(
		array(
			'post_type'      => 'er_evenement',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_key'       => 'er_debut', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $maintenant, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_compare'   => '>=',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		)
	);
	$id     = $q->posts ? (int) $q->posts[0]->ID : 0;
	$bouton = $id && function_exists( 'er_evt_billet' ) && er_evt_billet( $id ) ? __( 'Réserver ma place', 'editeurs-reunis' ) : __( 'Voir la rencontre', 'editeurs-reunis' );
	if ( ! $id && function_exists( 'er_expo_du_moment' ) ) {
		$id     = er_expo_du_moment();
		$bouton = __( 'Voir l’exposition', 'editeurs-reunis' );
	}
	if ( ! $id ) {
		return '';
	}
	$quand = function_exists( 'er_evt_quand' ) ? er_evt_quand( $id ) : '';
	$titre = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	$image = get_post_thumbnail_id( $id ) ? wp_get_attachment_image( get_post_thumbnail_id( $id ), 'large', false, array( 'loading' => 'lazy', 'sizes' => '(max-width: 781px) 100vw, 40vw' ) ) : '';
	return '<div class="er-pt-rdv-corps"><div class="er-pt-rdv-texte">'
		. ( $quand ? '<p class="er-pt-rdv-quand">' . esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $quand, 0, 1 ) ) . mb_substr( $quand, 1 ) : $quand ) . '</p>' : '' )
		. '<h3 class="er-pt-rdv-titre"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( $titre ) . '</a></h3>'
		. '<p class="er-pt-rdv-action"><a class="er-pt-pilule" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( $bouton ) . '</a></p></div>'
		. ( $image ? '<figure class="er-pt-rdv-image">' . $image . '</figure>' : '' ) . '</div>';
}
add_shortcode( 'er_pt_rendez_vous', 'er_pt_rendez_vous' );

/**
 * Les livres, dans le dessin de la recherche (principe 13) : six cadres, deux par colonne du triptyque.
 *
 * @param array $att ids (des identifiants), auteur ou rayon (un identifiant de terme), nombre (6).
 */
function er_pt_livres( $att = array() ) {
	if ( ! function_exists( 'wc_get_products' ) || ! function_exists( 'er_recherche_vue' ) ) {
		return '';
	}
	$att  = shortcode_atts(
		array(
			'ids'    => '',
			'auteur' => '',
			'rayon'  => '',
			'nombre' => 6,
		),
		$att,
		'er_pt_livres'
	);
	$args = array(
		'status'     => 'publish',
		'limit'      => max( 1, min( 12, (int) $att['nombre'] ) ),
		'visibility' => 'catalog',
		'orderby'    => 'date',
		'order'      => 'DESC',
	);
	if ( '' !== $att['ids'] ) {
		$args['include'] = array_map( 'absint', explode( ',', $att['ids'] ) );
		$args['orderby'] = 'post__in';
	} elseif ( '' !== $att['auteur'] || '' !== $att['rayon'] ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => '' !== $att['auteur'] ? 'auteur' : 'product_cat',
				'field'    => is_numeric( '' !== $att['auteur'] ? $att['auteur'] : $att['rayon'] ) ? 'term_id' : 'slug',
				'terms'    => '' !== $att['auteur'] ? $att['auteur'] : $att['rayon'],
			),
		);
	} else {
		$args['stock_status'] = 'instock';
	}
	$html = '';
	foreach ( wc_get_products( $args ) as $livre ) {
		$html .= '<div class="er-pt-livre">' . er_recherche_vue( $livre ) . '</div>'; // er_recherche_vue() échappe.
	}
	return $html ? '<div class="er-pt-livres-grille">' . $html . '</div>' : '';
}
add_shortcode( 'er_pt_livres', 'er_pt_livres' );

/**
 * Les derniers articles du journal, en lignes : la date, le genre, le titre (la liste des pages de la National
 * Academies).
 *
 * @param array $att nombre (3), categorie (un identifiant ou un slug).
 */
function er_pt_journal( $att = array() ) {
	$att      = shortcode_atts(
		array(
			'nombre'    => 3,
			'categorie' => '',
		),
		$att,
		'er_pt_journal'
	);
	$articles = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'numberposts'      => max( 1, min( 10, (int) $att['nombre'] ) ),
			'category_name'    => is_numeric( $att['categorie'] ) ? '' : sanitize_title( $att['categorie'] ),
			'cat'              => is_numeric( $att['categorie'] ) ? (int) $att['categorie'] : 0,
			'suppress_filters' => false,
		)
	);
	$html     = '';
	foreach ( $articles as $p ) {
		$genre  = function_exists( 'er_journal_genre' ) ? er_journal_genre( $p->ID ) : null;
		$genre  = $genre instanceof WP_Term ? $genre->name : '';
		$titre  = html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' );
		$html  .= '<li><a class="er-pt-ligne" href="' . esc_url( get_permalink( $p ) ) . '">'
			. '<span class="er-pt-ligne-date">' . esc_html( er_pt_date_courte( $p->ID ) ) . '</span>'
			. '<span class="er-pt-ligne-genre">' . esc_html( $genre ) . '</span>'
			. '<span class="er-pt-ligne-titre"' . ( preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '' ) . '>' . esc_html( $titre ) . '</span>'
			. '<span class="er-pt-ligne-fleche" aria-hidden="true">→</span></a></li>';
	}
	return $html ? '<ul class="er-pt-lignes">' . $html . '</ul>' : '';
}
add_shortcode( 'er_pt_journal', 'er_pt_journal' );

/**
 * La date courte d'un article, comme sur l'accueil : « 21 oct. 2026 ».
 *
 * @param int $id L'article.
 */
function er_pt_date_courte( $id ) {
	$ts     = (int) get_post_timestamp( $id );
	$fuseau = new DateTimeZone( 'Europe/Paris' );
	return ( function_exists( 'er_index_jour' ) ? er_index_jour( $ts ) : wp_date( 'j M', $ts, $fuseau ) ) . "\u{00A0}" . wp_date( 'Y', $ts, $fuseau );
}

/**
 * Un lien de la marge (28 nov. 2026) : une ligne entre deux filets, ce que c'est et quand en petit, le titre, la flèche ;
 * un livre, dans son cadre beige (principe 13).
 *
 * [er_pt_lien type="evenement"] : la rencontre (id), à défaut la prochaine ;
 * [er_pt_lien type="exposition"] : l'exposition (id), à défaut celle du moment ;
 * [er_pt_lien type="article"] : l'article du journal (id), à défaut le dernier ;
 * [er_pt_lien type="livre" id="123"] : le livre, à défaut le dernier arrivé en stock ;
 * [er_pt_lien type="page" lien="/nous-trouver/" titre="Nous trouver" sur="Le centre"] : une page.
 *
 * @param array $att type, id, lien, titre, sur.
 */
function er_pt_lien( $att = array() ) {
	$att = shortcode_atts(
		array(
			'type'  => 'page',
			'id'    => 0,
			'lien'  => '',
			'titre' => '',
			'sur'   => '',
		),
		$att,
		'er_pt_lien'
	);
	$id  = absint( $att['id'] );
	$maj = fn( $t ) => function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $t, 0, 1 ) ) . mb_substr( $t, 1 ) : ucfirst( $t );
	$sur = '';
	switch ( $att['type'] ) {
		case 'livre':
			if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'er_recherche_vue' ) ) {
				return '';
			}
			$livre = $id ? wc_get_product( $id ) : null;
			if ( ! $livre && function_exists( 'wc_get_products' ) ) {
				$livres = wc_get_products(
					array(
						'status'       => 'publish',
						'limit'        => 1,
						'visibility'   => 'catalog',
						'stock_status' => 'instock',
						'orderby'      => 'date',
						'order'        => 'DESC',
					)
				);
				$livre  = $livres ? $livres[0] : null;
			}
			return $livre ? '<div class="er-pt-lien er-pt-lien-livre">' . er_recherche_vue( $livre ) . '</div>' : ''; // er_recherche_vue() échappe.
		case 'evenement':
			if ( ! $id ) {
				$maintenant = ( new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Paris' ) ) )->modify( '-2 hours' )->format( 'Y-m-d H:i' );
				$prochains  = get_posts(
					array(
						'post_type'      => 'er_evenement',
						'post_status'    => 'publish',
						'posts_per_page' => 1,
						'meta_key'       => 'er_debut', // phpcs:ignore WordPress.DB.SlowDBQuery
						'meta_value'     => $maintenant, // phpcs:ignore WordPress.DB.SlowDBQuery
						'meta_compare'   => '>=',
						'orderby'        => 'meta_value',
						'order'          => 'ASC',
						'fields'         => 'ids',
					)
				);
				$id         = $prochains ? (int) $prochains[0] : 0;
			}
			$sur = __( 'Rencontre', 'editeurs-reunis' ) . ( $id && function_exists( 'er_evt_quand' ) ? ' · ' . er_evt_quand( $id ) : '' );
			break;
		case 'exposition':
			if ( ! $id && function_exists( 'er_expo_du_moment' ) ) {
				$id = (int) er_expo_du_moment();
			}
			$fin = $id && function_exists( 'er_info_iso' ) ? er_info_iso( $id, 'er_fin' ) : '';
			/* translators: %s: le dernier jour d'une exposition (« 30 novembre »). */
			$sur = __( 'Exposition', 'editeurs-reunis' ) . ( $fin && function_exists( 'er_jour_fin_expo' ) ? ' · ' . sprintf( __( 'jusqu’au %s', 'editeurs-reunis' ), er_jour_fin_expo( $fin ) ) : '' );
			break;
		case 'article':
			if ( ! $id ) {
				$derniers = get_posts(
					array(
						'post_type'        => 'post',
						'post_status'      => 'publish',
						'numberposts'      => 1,
						'fields'           => 'ids',
						'suppress_filters' => false,
					)
				);
				$id       = $derniers ? (int) $derniers[0] : 0;
			}
			$genre = $id && function_exists( 'er_journal_genre' ) ? er_journal_genre( $id ) : null;
			$genre = $genre instanceof WP_Term ? $genre->name : '';
			$sur   = ( '' !== $genre ? $genre : __( 'Le journal', 'editeurs-reunis' ) ) . ( $id ? ' · ' . er_pt_date_courte( $id ) : '' );
			break;
		default:
			if ( '' === $att['lien'] || '' === $att['titre'] ) {
				return '';
			}
			return '<a class="er-pt-lien er-pt-lien-page" href="' . esc_url( 0 === strpos( $att['lien'], '/' ) ? home_url( $att['lien'] ) : $att['lien'] ) . '"><span class="er-pt-lien-texte">'
				. ( '' !== $att['sur'] ? '<span class="er-pt-lien-sur">' . esc_html( $att['sur'] ) . '</span>' : '' )
				. '<span class="er-pt-lien-titre">' . esc_html( $att['titre'] ) . '</span></span><span class="er-pt-lien-fleche" aria-hidden="true">→</span></a>';
	}
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return '';
	}
	$titre = '' !== $att['titre'] ? $att['titre'] : html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	return '<a class="er-pt-lien er-pt-lien-' . esc_attr( $att['type'] ) . '" href="' . esc_url( get_permalink( $id ) ) . '"><span class="er-pt-lien-texte">'
		. '<span class="er-pt-lien-sur">' . esc_html( $maj( $sur ) ) . '</span>'
		. '<span class="er-pt-lien-titre"' . ( preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '' ) . '>' . esc_html( $titre ) . '</span></span>'
		. '<span class="er-pt-lien-fleche" aria-hidden="true">→</span></a>';
}
add_shortcode( 'er_pt_lien', 'er_pt_lien' );

/**
 * Le contenu de la page type : le pattern patterns/page-type.php, rendu.
 */
function er_page_type_contenu() {
	ob_start();
	include get_template_directory() . '/patterns/page-type.php';
	return (string) ob_get_clean();
}

// La page « Page type » (/page-type/), dans le gabarit « Page de texte » : créée au premier passage,
// récrite quand ER_PAGE_TYPE_VERSION augmente, sauf si quelqu'un l'a retouchée dans l'éditeur depuis (son contenu ne
// répond plus à l'empreinte de la dernière écriture) : la page de l'équipe passe avant. Hors des moteurs, comme toute
// la bêta.
add_action(
	'init',
	function () {
		if ( (int) get_option( 'er_page_type_version' ) >= ER_PAGE_TYPE_VERSION || wp_installing() ) {
			return;
		}
		if ( (int) get_option( 'er_page_type_verrou' ) < time() - 60 ) {
			delete_option( 'er_page_type_verrou' );
		}
		if ( ! add_option( 'er_page_type_verrou', time(), '', false ) ) {
			return;
		}
		$page      = get_page_by_path( 'page-type' );
		$empreinte = (string) get_option( 'er_page_type_empreinte' );
		if ( $page && '' !== $empreinte && md5( $page->post_content ) !== $empreinte ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-texte' );
			update_option( 'er_page_type_version', ER_PAGE_TYPE_VERSION, false );
			delete_option( 'er_page_type_verrou' );
			return;
		}
		$donnees = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => 'page-type',
			'post_title'   => 'Page type',
			'post_content' => er_page_type_contenu(),
		);
		$id      = $page ? wp_update_post( array( 'ID' => $page->ID ) + $donnees, true ) : wp_insert_post( $donnees, true );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', 'page-texte' );
			if ( ! $page && function_exists( 'pll_set_post_language' ) && function_exists( 'pll_default_language' ) ) {
				pll_set_post_language( $id, pll_default_language() );
			}
			update_option( 'er_page_type_version', ER_PAGE_TYPE_VERSION, false );
			update_option( 'er_page_type_empreinte', md5( (string) get_post_field( 'post_content', $id, 'raw' ) ), false );
		}
		delete_option( 'er_page_type_verrou' );
	},
	40
);

/**
 * Un essai de dessins sur la page type (à l'essai) : « ?<clé>=b » pose un dessin (html[data-er-<clé>]) et ouvre un petit
 * sélecteur en bas à gauche ; le navigateur le retient, « × » referme l'essai. Les dessins sont dans
 * assets/css/32-page-type.css. Rien ne change pour les autres visiteurs.
 *
 * @param string $cle     La clé (texte, chapo…).
 * @param string $libelle Le nom du sélecteur, pour les lecteurs d'écran.
 * @param array  $dessins Les dessins : lettre => nom.
 */
function er_pt_essai( $cle, $libelle, $dessins ) {
	?>
<script>
(function () {
	var D = <?php echo wp_json_encode( $dessins ); ?>, P = <?php echo wp_json_encode( $cle ); ?>, K = 'er-essai-' + P, A = 'data-er-' + P, r = document.documentElement;
	function poser(c) { if (c && D[c]) { r.setAttribute(A, c); } else { r.removeAttribute(A); } }
	try {
		var q = new URLSearchParams(location.search).get(P);
		if (null !== q) { q = q.toLowerCase(); if (D[q]) { localStorage.setItem(K, q); } else { localStorage.removeItem(K); } }
		var c = localStorage.getItem(K);
		if (!c || !D[c]) { return; }
		poser(c);
		document.addEventListener('DOMContentLoaded', function () {
			var s = document.createElement('div'), h = '';
			s.setAttribute('role', 'group');
			s.setAttribute('aria-label', <?php echo wp_json_encode( $libelle ); ?>);
			s.style.cssText = 'position:fixed;left:16px;bottom:16px;z-index:99999;display:flex;align-items:center;gap:2px;padding:6px;border-radius:999px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.08),0 6px 18px rgba(0,0,0,.14);font:500 12px/1 Montserrat,system-ui,sans-serif;color:#1C1C1C';
			Object.keys(D).forEach(function (k) {
				h += '<button type="button" data-dessin="' + k + '" style="height:30px;padding:0 12px;border:0;border-radius:999px;background:transparent;font:inherit;color:inherit;cursor:pointer">' + k.toUpperCase() + ' · ' + D[k] + '</button>';
			});
			h += '<button type="button" data-fermer aria-label="Refermer l’essai" style="width:30px;height:30px;border:0;border-radius:50%;background:transparent;font:inherit;font-size:16px;color:#6B6B66;cursor:pointer">×</button>';
			s.innerHTML = h;
			function marquer(k) {
				s.querySelectorAll('[data-dessin]').forEach(function (b) {
					var oui = b.getAttribute('data-dessin') === k;
					b.style.background = oui ? '#EEECE6' : 'transparent';
					b.style.fontWeight = oui ? '700' : '500';
					b.setAttribute('aria-pressed', oui ? 'true' : 'false');
				});
			}
			s.addEventListener('click', function (e) {
				var b = e.target.closest('button');
				if (!b) { return; }
				if (b.hasAttribute('data-fermer')) { localStorage.removeItem(K); poser(null); s.remove(); return; }
				var k = b.getAttribute('data-dessin');
				localStorage.setItem(K, k);
				poser(k);
				marquer(k);
			});
			marquer(c);
			document.body.appendChild(s);
		});
	} catch (e) {}
})();
</script>
	<?php
}

/**
 * Les liens vers un livre dans le texte d'une page (28 nov. 2026, à l'essai sur la page type) : chacun prend
 * l'identifiant de son livre (data-apercu-livre ; data-livre est aux liens du menu Librairie) ; leurs cartes (le dessin de la recherche, principe 13) attendent à la fin
 * du contenu, cachées, et assets/js/apercu-livre.js les montre au survol, dans le dessin choisi (« ?survol=a »).
 */
add_filter(
	'render_block_core/post-content',
	function ( $html ) {
		if ( ! is_page( 'page-type' ) || ! function_exists( 'wc_get_product' ) || ! function_exists( 'er_recherche_vue' ) ) {
			return $html;
		}
		$cartes = array();
		$html   = preg_replace_callback(
			'#<a\s([^>]*?)href="([^"]+)"([^>]*)>#i',
			function ( $m ) use ( &$cartes ) {
				$chemin = (string) wp_parse_url( html_entity_decode( $m[2] ), PHP_URL_PATH );
				if ( false !== strpos( $m[0], 'er-r-' ) || ! preg_match( '#/livre/([^/]+)/?$#', $chemin, $s ) ) {
					return $m[0];
				}
				$page  = get_page_by_path( rawurldecode( $s[1] ), OBJECT, 'product' );
				$livre = $page ? wc_get_product( $page->ID ) : null;
				if ( ! $livre ) {
					return $m[0];
				}
				$cartes[ $livre->get_id() ] = '<div class="er-apercu-modele" data-livre="' . (int) $livre->get_id() . '">' . er_recherche_vue( $livre ) . '</div>';
				return '<a ' . $m[1] . 'href="' . $m[2] . '"' . $m[3] . ' data-apercu-livre="' . (int) $livre->get_id() . '">';
			},
			$html
		);
		if ( ! $cartes ) {
			return $html;
		}
		return $html . '<div class="er-apercus" hidden>' . implode( '', $cartes ) . '</div>';
	},
	20
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_page( 'page-type' ) ) {
			return;
		}
		wp_enqueue_script( 'er-apercu-livre', get_theme_file_uri( 'assets/js/apercu-livre.js' ), array(), wp_get_theme()->get( 'Version' ) . '.' . filemtime( get_theme_file_path( 'assets/js/apercu-livre.js' ) ), true );
	}
);

add_action(
	'wp_head',
	function () {
		if ( is_page( 'page-type' ) ) {
			er_pt_essai(
				'survol',
				'Essai des aperçus de livres',
				array(
					'a' => 'La couverture qui suit',
					'b' => 'La carte au-dessus',
					'c' => 'Dans la marge',
				)
			);
		}
	},
	1
);
