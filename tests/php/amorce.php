<?php
/**
 * L'amorce des tests : de quoi charger le thème et l'extension sans WordPress. Les fonctions de WordPress dont le
 * code testé a besoin sont remplacées ici par des doublures simples ; les crochets (add_action, add_filter) sont
 * retenus, pour qu'un test puisse appeler un filtre. Les articles, leurs métas et les termes viennent de tableaux que
 * chaque test remplit (er_test_article, er_test_termes).
 *
 * Seuls les fichiers dont les fonctions sont testées sont chargés (en bas de ce fichier). Une fonction de WordPress
 * qui manque se voit tout de suite : « Call to undefined function ». On l'ajoute alors ici, au plus simple.
 *
 * @package editeurs-reunis
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'ER_RACINE', dirname( __DIR__, 2 ) );
defined( 'YEAR_IN_SECONDS' ) || define( 'YEAR_IN_SECONDS', 365 * 24 * 3600 );

/* Les crochets ------------------------------------------------------------------------------------------------- */

$GLOBALS['er_test_crochets'] = array();

function add_filter( $crochet, $rappel, $priorite = 10, $arguments = 1 ) {
	$GLOBALS['er_test_crochets'][ $crochet ][] = array( $rappel, $arguments );
	return true;
}

function add_action( $crochet, $rappel, $priorite = 10, $arguments = 1 ) {
	return add_filter( $crochet, $rappel, $priorite, $arguments );
}

function apply_filters( $crochet, $valeur, ...$autres ) {
	foreach ( $GLOBALS['er_test_crochets'][ $crochet ] ?? array() as list( $rappel, $n ) ) {
		$valeur = $rappel( ...array_slice( array_merge( array( $valeur ), $autres ), 0, $n ) );
	}
	return $valeur;
}

function do_action( ...$a ) {}

/**
 * Lance les rappels d'une action retenue, comme do_action().
 *
 * @param string $crochet L'action.
 * @param mixed  ...$args Ses arguments.
 */
function er_test_action( $crochet, ...$args ) {
	foreach ( $GLOBALS['er_test_crochets'][ $crochet ] ?? array() as list( $rappel, $n ) ) {
		$rappel( ...array_slice( $args, 0, $n ) );
	}
}
function register_block_type( ...$a ) {}
function add_post_type_support( ...$a ) {}
function add_shortcode( ...$a ) {}

/**
 * Le filtre retenu pour un crochet, pour l'appeler dans un test.
 *
 * @param string $crochet Le crochet.
 * @param int    $rang    Le rang parmi les rappels de ce crochet.
 */
function er_test_filtre( $crochet, $rang = 0 ) {
	return $GLOBALS['er_test_crochets'][ $crochet ][ $rang ][0];
}

/* Les textes ----------------------------------------------------------------------------------------------------- */

function __( $t, $d = '' ) {
	return $t;
}
function _x( $t, $c, $d = '' ) {
	return $t;
}
function _n( $un, $plusieurs, $n, $d = '' ) {
	return 1 === (int) $n ? $un : $plusieurs;
}
function esc_html__( $t, $d = '' ) {
	return esc_html( $t );
}
function esc_html( $t ) {
	return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $t ) {
	return esc_html( $t );
}
function esc_url( $u ) {
	return esc_html( preg_replace( '/[^\w\-.~:\/?#\[\]@!$&\'()*+,;=%\x{80}-\x{10FFFF}]/u', '', (string) $u ) );
}
function wp_strip_all_tags( $t ) {
	return trim( strip_tags( preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $t ) ) );
}
function sanitize_key( $k ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) );
}
function sanitize_text_field( $t ) {
	return trim( wp_strip_all_tags( $t ) );
}
function sanitize_textarea_field( $t ) {
	return trim( strip_tags( (string) $t ) );
}
function sanitize_email( $t ) {
	return preg_replace( '/[^a-zA-Z0-9.@_+\-]/', '', trim( (string) $t ) );
}
function is_email( $t ) {
	return (bool) filter_var( $t, FILTER_VALIDATE_EMAIL );
}
function wp_hash( $t ) {
	return hash_hmac( 'md5', (string) $t, 'sel-des-tests' );
}
function wp_unslash( $v ) {
	return is_string( $v ) ? stripslashes( $v ) : $v;
}

/**
 * Les accents ôtés, comme remove_accents() de WordPress pour les lettres latines (les autres écritures restent).
 *
 * @param string $t Le texte.
 */
function remove_accents( $t ) {
	$t = strtr( (string) $t, array( 'œ' => 'oe', 'Œ' => 'OE', 'æ' => 'ae', 'Æ' => 'AE', 'ß' => 'ss', 'ø' => 'o', 'Ø' => 'O', 'ł' => 'l', 'Ł' => 'L', 'đ' => 'd', 'Đ' => 'D' ) );
	$d = Normalizer::normalize( $t, Normalizer::FORM_D );
	// Les accents des lettres latines seulement : le « й » et le « ё » restent tels quels.
	$d = preg_replace( '/(?<=[A-Za-z])\p{Mn}+/u', '', $d );
	return Normalizer::normalize( $d, Normalizer::FORM_C );
}

function sanitize_title( $t ) {
	$t = strtolower( remove_accents( wp_strip_all_tags( $t ) ) );
	$t = preg_replace( '/[^a-z0-9\x{80}-\x{10FFFF}]+/u', '-', $t );
	return trim( $t, '-' );
}

function wp_parse_args( $a, $defauts = array() ) {
	return array_merge( $defauts, (array) $a );
}
function wp_json_encode( $v, $options = 0 ) {
	return json_encode( $v, $options );
}
function absint( $n ) {
	return abs( (int) $n );
}

/* Les erreurs, les termes, les articles ------------------------------------------------------------------------ */

class WP_Error {}
class WP_Term {
	public $term_id = 0;
	public $name = '';
	public $slug = '';
	public $taxonomy = '';
	public $description = '';
	public $parent = 0;
	public function __construct( $champs = array() ) {
		foreach ( $champs as $k => $v ) {
			$this->$k = $v;
		}
	}
}
class WP_Post {
	public $ID = 0;
	public $post_title = '';
	public $post_excerpt = '';
	public $post_content = '';
	public function __construct( $champs = array() ) {
		foreach ( $champs as $k => $v ) {
			$this->$k = $v;
		}
	}
}

function is_wp_error( $v ) {
	return $v instanceof WP_Error;
}

$GLOBALS['er_test_articles'] = array();
$GLOBALS['er_test_termes']   = array();
$GLOBALS['er_test_page']     = array();

/**
 * Un article pour le test, avec ses métas.
 *
 * @param int   $id     L'identifiant.
 * @param array $champs Les champs (post_title, post_excerpt, post_content) et 'metas'.
 */
function er_test_article( $id, $champs ) {
	$metas = $champs['metas'] ?? array();
	unset( $champs['metas'] );
	$GLOBALS['er_test_articles'][ $id ] = array(
		'post'  => new WP_Post( array_merge( array( 'ID' => $id ), $champs ) ),
		'metas' => $metas,
	);
}

/**
 * Les termes d'une taxonomie pour le test.
 *
 * @param string $taxonomie La taxonomie.
 * @param array  $noms      Les noms.
 */
function er_test_termes( $taxonomie, $noms ) {
	$GLOBALS['er_test_termes'][ $taxonomie ] = $noms;
}

function get_post( $id ) {
	return $GLOBALS['er_test_articles'][ $id ]['post'] ?? null;
}
function get_the_title( $id ) {
	$post = get_post( $id );
	return $post ? $post->post_title : '';
}
function get_post_meta( $id, $cle = '', $seul = false ) {
	return $GLOBALS['er_test_articles'][ $id ]['metas'][ $cle ] ?? '';
}
function get_term_meta( $id, $cle = '', $seul = false ) {
	return $GLOBALS['er_test_metas_termes'][ $id ][ $cle ] ?? '';
}
function update_term_meta( $id, $cle, $valeur ) {
	$GLOBALS['er_test_metas_termes'][ $id ][ $cle ] = $valeur;
	return true;
}
function get_terms( $a ) {
	return $GLOBALS['er_test_termes'][ $a['taxonomy'] ] ?? array();
}

/* Une petite base de termes, pour les personnes et leurs miroirs (er_test_terme). */

$GLOBALS['er_test_base'] = array();

/**
 * Un terme dans la base du test.
 *
 * @param int    $id        L'identifiant.
 * @param string $taxonomie Le classement.
 * @param string $slug      L'identifiant texte.
 * @param string $nom       Le nom.
 */
function er_test_terme( $id, $taxonomie, $slug, $nom ) {
	$GLOBALS['er_test_base'][ $taxonomie ][ $id ] = new WP_Term(
		array(
			'term_id'  => $id,
			'taxonomy' => $taxonomie,
			'slug'     => $slug,
			'name'     => $nom,
		)
	);
	return $GLOBALS['er_test_base'][ $taxonomie ][ $id ];
}
function get_term( $id, $taxonomie = '' ) {
	return $GLOBALS['er_test_base'][ $taxonomie ][ $id ] ?? null;
}
function get_term_by( $champ, $valeur, $taxonomie ) {
	foreach ( $GLOBALS['er_test_base'][ $taxonomie ] ?? array() as $t ) {
		if ( ( 'slug' === $champ ? $t->slug : $t->name ) === $valeur ) {
			return $t;
		}
	}
	return false;
}
function wp_update_term( $id, $taxonomie, $champs ) {
	foreach ( $champs as $k => $v ) {
		$GLOBALS['er_test_base'][ $taxonomie ][ $id ]->$k = $v;
	}
	return array( 'term_id' => $id );
}
function wp_delete_term( $id, $taxonomie ) {
	unset( $GLOBALS['er_test_base'][ $taxonomie ][ $id ] );
	return true;
}
function wp_cache_delete( ...$a ) {
	return true;
}
function delete_transient( $cle ) {
	return true;
}

/* La page qu'on regarde ---------------------------------------------------------------------------------------- */

function er_test_page( $champs ) {
	$GLOBALS['er_test_page'] = $champs;
}
function is_page() {
	return ! empty( $GLOBALS['er_test_page']['page'] );
}
function is_front_page() {
	return ! empty( $GLOBALS['er_test_page']['accueil'] );
}
function get_queried_object_id() {
	return $GLOBALS['er_test_page']['id'] ?? 0;
}
function get_bloginfo( $quoi = '' ) {
	return 'Les Éditeurs réunis';
}

/* WooCommerce, au plus simple ---------------------------------------------------------------------------------- */

function wp_date( $format, $quand = null ) {
	return $GLOBALS['er_test_aujourdhui'] ?? gmdate( $format );
}
function taxonomy_exists( $t ) {
	return true;
}
function wp_get_post_terms( $id, $taxonomie, $a = array() ) {
	return $GLOBALS['er_test_termes_de'][ $id ][ $taxonomie ] ?? array();
}
function has_term( $terme, $taxonomie, $id ) {
	return in_array( $terme, $GLOBALS['er_test_termes_de'][ $id ][ $taxonomie ] ?? array(), true );
}

/**
 * Un produit : ses métas, son prix, ses termes (er_test_termes_de).
 */
class WC_Product {
	public $id;
	public $metas;
	public $prix;
	public function __construct( $id, $metas = array(), $prix = array() ) {
		$this->id    = $id;
		$this->metas = $metas;
		$this->prix  = array_merge( array( 'regular' => '20', 'sale' => '' ), $prix );
	}
	public function get_id() {
		return $this->id;
	}
	public function get_meta( $cle ) {
		return $this->metas[ $cle ] ?? '';
	}
	public function get_regular_price( $c = 'view' ) {
		return $this->prix['regular'];
	}
	public function get_sale_price( $c = 'view' ) {
		return 'view' === $c ? apply_filters( 'woocommerce_product_get_sale_price', $this->prix['sale'], $this ) : $this->prix['sale'];
	}
	public function get_price( $c = 'view' ) {
		$prix = '' !== $this->prix['sale'] ? $this->prix['sale'] : $this->prix['regular'];
		return 'view' === $c ? apply_filters( 'woocommerce_product_get_price', $prix, $this ) : $prix;
	}
	public function is_on_sale( $c = 'view' ) {
		$en_solde = '' !== $this->prix['sale'] && (float) $this->prix['sale'] < (float) $this->prix['regular'];
		return 'view' === $c ? apply_filters( 'woocommerce_product_is_on_sale', $en_solde, $this ) : $en_solde;
	}
}

/**
 * Un tarif de livraison.
 */
class WC_Shipping_Rate {
	public $methode;
	public $cout;
	public $taxes = array();
	public $libelle;
	public function __construct( $methode, $cout, $libelle = '' ) {
		$this->methode = $methode;
		$this->cout    = $cout;
		$this->libelle = $libelle;
	}
	public function get_method_id() {
		return $this->methode;
	}
	public function get_cost() {
		return $this->cout;
	}
	public function get_taxes() {
		return $this->taxes;
	}
	public function set_cost( $c ) {
		$this->cout = $c;
	}
	public function set_taxes( $t ) {
		$this->taxes = $t;
	}
	public function set_label( $l ) {
		$this->libelle = $l;
	}
}

/**
 * Les taxes : un seul taux, celui de la livraison, posé par le test (er_test_taux_livraison, en %).
 */
class WC_Tax {
	public static function get_shipping_tax_rates() {
		return isset( $GLOBALS['er_test_taux_livraison'] ) ? array( 1 => array( 'rate' => $GLOBALS['er_test_taux_livraison'] ) ) : array();
	}
	public static function calc_tax( $prix, $taux, $ttc = false ) {
		$t = reset( $taux )['rate'] / 100;
		return array( 1 => $ttc ? $prix - $prix / ( 1 + $t ) : $prix * $t );
	}
}
function wc_tax_enabled() {
	return isset( $GLOBALS['er_test_taux_livraison'] );
}

/* Les fichiers testés ------------------------------------------------------------------------------------------ */

foreach ( array(
	'plugin/er-librairie/inc/recherche.php',
	'plugin/er-librairie/inc/collections.php',
	'plugin/er-librairie/inc/traducteurs-editeurs.php',
	'plugin/er-librairie/inc/oeuvres-collectives.php',
	'plugin/er-librairie/inc/prix-du-livre.php',
	'plugin/er-librairie/inc/vente-a-distance.php',
	'plugin/er-librairie/inc/recherches-vides.php',
	'plugin/er-librairie/inc/bascule.php',
	'plugin/er-librairie/inc/donnees-structurees.php',
	'plugin/er-librairie/inc/personnes.php',
	'plugin/er-librairie/inc/epoques.php',
	'plugin/er-librairie/inc/facettes.php',
	'plugin/er-librairie/inc/rayonnage.php',
	'plugin/er-librairie/inc/continuer-modules.php',
	'plugin/er-bascule/inc/etiquettes.php',
	'plugin/er-bascule/inc/phrases.php',
	'plugin/er-bascule/inc/recoupement.php',
	'plugin/er-librairie/inc/evenements.php',
	'theme/editeurs-reunis/inc/bande.php',
	'theme/editeurs-reunis/inc/tete.php',
) as $fichier ) {
	require_once ER_RACINE . '/' . $fichier;
}
