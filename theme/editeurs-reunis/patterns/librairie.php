<?php
/**
 * Title: La librairie
 * Slug: editeurs-reunis/librairie
 * Categories: editeurs-reunis
 * Inserter: no
 *
 * Toute la librairie, un rayon, un auteur, une langue ou une recherche : le titre, les rayons et les langues en
 * pilules, puis les livres en grand, sur toute la largeur. Pour un auteur, sa fiche (er/auteur-fiche : portrait,
 * dates, présentation, biographie, photos) remplace le titre, et ses livres viennent dessous.
 *
 * @package editeurs-reunis
 */


// La page d'une personne : sous les livres qu'elle a écrits, ceux qu'elle a traduits, dirigés, préfacés, établis ou
// illustrés (er_personne_livres(),
// traducteurs-editeurs.php, dans l'extension). Qui n'a rien écrit qui soit en vente (Georges Nivat, Sophie Benech) n'a
// pas de « Ses livres » vide : ses traductions viennent tout de suite (24 nov. 2026).
$er_roles    = array();
$er_ecrivain = true;
if ( is_tax( 'auteur' ) && function_exists( 'er_personne_livres' ) ) {
	$er_livres   = er_personne_livres( get_queried_object() );
	$er_roles    = array_filter(
		array(
			__( 'Ses traductions', 'editeurs-reunis' )    => $er_livres['traduction'],
			__( 'Sous sa direction', 'editeurs-reunis' )  => $er_livres['direction'],
			__( 'Ses préfaces', 'editeurs-reunis' )       => isset( $er_livres['preface'] ) ? $er_livres['preface'] : array(),
			__( 'Éditions établies', 'editeurs-reunis' )  => isset( $er_livres['edition'] ) ? $er_livres['edition'] : array(),
			__( 'Ses illustrations', 'editeurs-reunis' )  => isset( $er_livres['illustration'] ) ? $er_livres['illustration'] : array(),
		)
	);
	$er_ecrivain = ! empty( $er_livres['auteur'] ) || ! $er_roles;
}
?>
<!-- wp:group {"tagName":"main","className":"er-librairie","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group er-librairie" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"align":"wide","className":"er-librairie-tete","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide er-librairie-tete">
		<!-- wp:woocommerce/breadcrumbs {"fontSize":"x-small"} /-->

<?php if ( is_tax( 'auteur' ) ) : ?>
		<!-- wp:er/auteur-fiche /-->
	<?php if ( $er_ecrivain ) : ?>
		<!-- wp:heading {"className":"er-auteur-livres"} -->
		<h2 class="wp-block-heading er-auteur-livres"><?php esc_html_e( 'Ses livres', 'editeurs-reunis' ); ?></h2>
		<!-- /wp:heading -->
	<?php endif; ?>
<?php elseif ( is_search() ) : ?>
		<!-- wp:query-title {"type":"search"} /-->
<?php else : ?>
		<!-- wp:query-title {"type":"archive","showPrefix":false} /-->
<?php endif; ?>
<?php if ( function_exists( 'is_shop' ) && is_shop() && ! is_search() ) : ?>
		<!-- wp:paragraph {"className":"er-devise"} -->
		<p class="er-devise"><?php esc_html_e( 'Les Éditeurs réunis, librairie de référence depuis 1925', 'editeurs-reunis' ); ?></p>
		<!-- /wp:paragraph -->
<?php endif; ?>

<?php if ( ! is_tax( 'auteur' ) ) : // La présentation d'un auteur est dans sa fiche. ?>
		<!-- wp:term-description /-->
<?php endif; ?>
	</div>
	<!-- /wp:group -->
<?php if ( $er_ecrivain ) : ?>

	<!-- wp:group {"align":"wide","className":"er-filtres","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide er-filtres">
		<!-- wp:categories {"taxonomy":"langue","showPostCounts":false,"showEmpty":false,"className":"er-pilules er-pilules-langues"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:woocommerce/store-notices /-->

	<!-- wp:group {"align":"wide","className":"er-tri","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide er-tri">
		<!-- wp:woocommerce/product-results-count /-->

		<!-- wp:woocommerce/catalog-sorting /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:woocommerce/product-collection {"queryId":31,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":24,"pages":0,"offset":0,"postType":"product","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","dimensions":{"widthType":"fill"},"displayLayout":{"type":"flex","columns":5,"shrinkColumns":true},"queryContextIncludes":["collection"],"align":"wide"} -->
	<div class="wp-block-woocommerce-product-collection alignwide">
		<?php echo er_fiche_livre(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
			<!-- wp:query-pagination-previous {"label":"<?php echo er_attr( _x( 'Précédents', 'pagination des livres', 'editeurs-reunis' ) ); ?>"} /-->
			<!-- wp:query-pagination-numbers /-->
			<!-- wp:query-pagination-next {"label":"<?php echo er_attr( _x( 'Suivants', 'pagination des livres', 'editeurs-reunis' ) ); ?>"} /-->
		<!-- /wp:query-pagination -->

		<!-- wp:woocommerce/product-collection-no-results -->
			<!-- wp:paragraph -->
			<p><?php
			printf(
				wp_kses_post( /* translators: %s: l'adresse de la librairie. */ __( 'Aucun livre ne correspond. <a href="%s">Voir toute la librairie</a>, ou écrivez-nous : nous avons bien plus de livres en rayon qu’en ligne.', 'editeurs-reunis' ) ),
				er_lien( 'librairie/' ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
			?></p>
			<!-- /wp:paragraph -->
		<!-- /wp:woocommerce/product-collection-no-results -->
	</div>
	<!-- /wp:woocommerce/product-collection -->
<?php endif; ?>
<?php
$er_rang = 0;
foreach ( $er_roles as $er_titre => $er_ids ) :
	$er_requete = array(
		'perPage'                      => min( 100, count( $er_ids ) ),
		'pages'                        => 1,
		'offset'                       => 0,
		'postType'                     => 'product',
		'order'                        => 'desc',
		'orderBy'                      => 'date',
		'search'                       => '',
		'exclude'                      => array(),
		'inherit'                      => false,
		'taxQuery'                     => (object) array(),
		'isProductCollectionBlock'     => true,
		'woocommerceStockStatus'       => array( 'instock', 'outofstock', 'onbackorder' ),
		'woocommerceAttributes'        => array(),
		'woocommerceHandPickedProducts' => array_map( 'intval', $er_ids ),
		'filterable'                   => false,
	);
	$er_bloc    = array(
		'queryId'              => 32 + $er_rang++,
		'query'                => $er_requete,
		'tagName'              => 'div',
		'displayLayout'        => array(
			'type'          => 'flex',
			'columns'       => 5,
			'shrinkColumns' => true,
		),
		'dimensions'           => array( 'widthType' => 'fill' ),
		'queryContextIncludes' => array( 'collection' ),
		'align'                => 'wide',
		'className'            => 'er-auteur-role',
	);
	?>
	<!-- wp:heading {"align":"wide","className":"er-auteur-livres er-auteur-livres-role"} -->
	<h2 class="wp-block-heading alignwide er-auteur-livres er-auteur-livres-role"><?php echo esc_html( $er_titre ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/product-collection <?php echo wp_json_encode( $er_bloc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?> -->
	<div class="wp-block-woocommerce-product-collection alignwide er-auteur-role">
		<?php echo er_fiche_livre(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<!-- /wp:woocommerce/product-collection -->
<?php endforeach; ?>
</main>
<!-- /wp:group -->
