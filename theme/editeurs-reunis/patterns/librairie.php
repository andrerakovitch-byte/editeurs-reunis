<?php
/**
 * Title: La librairie
 * Slug: editeurs-reunis/librairie
 * Categories: editeurs-reunis
 * Inserter: no
 *
 * Toute la librairie, un rayon, un auteur, une langue ou une recherche : la tête de page (inc/tete.php : le fil, le
 * titre, le chapô ; pour une personne, son portrait, ses graphies et ses dates), la bande de page (les sous-rayons
 * d'un rayon, le sommaire d'une personne), puis la colonne « Filtrer par » (le bloc er/facettes de l'extension) et les
 * livres à côté, les filtres en cours au-dessus d'eux. Pour une personne, sa
 * biographie, ses livres, ses rôles et ce qui l'entoure (er/autour).
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
			__( 'Traductions revues', 'editeurs-reunis' ) => isset( $er_livres['revision'] ) ? $er_livres['revision'] : array(),
			__( 'Ses contributions', 'editeurs-reunis' )  => isset( $er_livres['contribution'] ) ? $er_livres['contribution'] : array(),
		)
	);
	$er_ecrivain = ! empty( $er_livres['auteur'] ) || ! $er_roles;
}
?>
<!-- wp:group {"tagName":"main","className":"er-librairie","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group er-librairie" style="padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:editeurs-reunis/tete /-->
<?php
// La bande de page, sous la tête : le sommaire d'une personne (sa biographie, ses livres, ses traductions…, ce qui
// l'entoure au centre et au journal), ou les sous-rayons d'un rayon (inc/bande.php).
$er_bande = '';
if ( is_tax( 'auteur' ) && function_exists( 'er_bande_page' ) ) {
	$er_personne = get_queried_object();
	$er_entrees  = array();
	if ( '' !== trim( (string) get_term_meta( $er_personne->term_id, 'er_biographie', true ) ) ) {
		$er_entrees[] = array(
			'texte' => __( 'Biographie', 'editeurs-reunis' ),
			'lien'  => '#biographie',
		);
	}
	if ( $er_ecrivain ) {
		$er_entrees[] = array(
			'texte' => __( 'Ses livres', 'editeurs-reunis' ),
			'lien'  => '#ses-livres',
		);
	}
	foreach ( array_keys( $er_roles ) as $er_titre ) {
		$er_entrees[] = array(
			'texte' => $er_titre,
			'lien'  => '#' . sanitize_title( remove_accents( $er_titre ) ),
		);
	}
	if ( function_exists( 'er_autour_sommaire' ) ) {
		$er_entrees = array_merge( $er_entrees, er_autour_sommaire( $er_personne ) );
	}
	if ( $er_entrees ) {
		$er_entrees[0]['courant'] = true;
	}
	$er_bande = er_bande_page(
		array(
			'maison'   => 'librairie',
			'nom'      => $er_personne->name,
			'sommaire' => true,
			'entrees'  => $er_entrees,
		)
	);
} elseif ( is_tax( 'product_cat' ) && function_exists( 'er_bande_rayon' ) ) {
	$er_bande = er_bande_rayon( get_queried_object() );
}
if ( $er_bande ) :
	?>
	<!-- wp:html -->
	<?php echo $er_bande; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<!-- /wp:html -->
<?php endif; ?>
<?php if ( is_tax( 'auteur' ) ) : ?>

	<!-- wp:group {"align":"wide","className":"er-auteur-suite","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide er-auteur-suite">
		<!-- wp:er/auteur-fiche {"partie":"suite"} /-->
	</div>
	<!-- /wp:group -->
	<?php if ( $er_ecrivain ) : ?>

	<!-- wp:heading {"align":"wide","className":"er-auteur-livres"} -->
	<h2 class="wp-block-heading alignwide er-auteur-livres" id="ses-livres"><?php esc_html_e( 'Ses livres', 'editeurs-reunis' ); ?></h2>
	<!-- /wp:heading -->
	<?php endif; ?>
<?php endif; ?>
<?php if ( $er_ecrivain ) : ?>

	<!-- wp:group {"align":"wide","className":"er-rayonnage","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide er-rayonnage">
	<!-- wp:er/facettes /-->

	<!-- wp:group {"className":"er-rayonnage__livres","layout":{"type":"default"}} -->
	<div class="wp-block-group er-rayonnage__livres">
	<!-- wp:woocommerce/store-notices /-->

	<!-- wp:group {"className":"er-tri","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group er-tri">
		<!-- wp:woocommerce/product-results-count /-->

		<!-- wp:woocommerce/catalog-sorting /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:er/facettes {"partie":"puces"} /-->

	<!-- wp:woocommerce/product-collection {"queryId":31,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":24,"pages":0,"offset":0,"postType":"product","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","dimensions":{"widthType":"fill"},"displayLayout":{"type":"flex","columns":5,"shrinkColumns":true},"queryContextIncludes":["collection"]} -->
	<div class="wp-block-woocommerce-product-collection">
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
	</div>
	<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
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
	<h2 class="wp-block-heading alignwide er-auteur-livres er-auteur-livres-role" id="<?php echo esc_attr( sanitize_title( remove_accents( $er_titre ) ) ); ?>"><?php echo esc_html( $er_titre ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/product-collection <?php echo wp_json_encode( $er_bloc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?> -->
	<div class="wp-block-woocommerce-product-collection alignwide er-auteur-role">
		<?php echo er_fiche_livre(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<!-- /wp:woocommerce/product-collection -->
<?php endforeach; ?>
<?php if ( is_tax( 'auteur' ) ) : // Ce qui l'entoure, dans la page : la bande s'accroche jusqu'au bout. ?>

	<!-- wp:group {"align":"wide","className":"er-autour-auteur","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide er-autour-auteur">
		<!-- wp:er/autour /-->
	</div>
	<!-- /wp:group -->
<?php endif; ?>
</main>
<!-- /wp:group -->
