<?php
/**
 * Title: Les articles du journal
 * Slug: editeurs-reunis/journal-liste
 * Inserter: no
 *
 * La grille des articles (le journal, une rubrique, des résultats), sa pagination, et la phrase quand il n’y a rien.
 * La date « j F Y » vaut pour les trois langues : 21 novembre 2026, 21 ноября 2026, 21 November 2026.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:query {"queryId":51,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":true},"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide">
	<!-- wp:post-template {"className":"er-articles","layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
		<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2"} /-->
		<!-- wp:post-terms {"term":"category","className":"er-surtitre"} /-->
		<!-- wp:post-title {"level":2,"isLink":true,"fontSize":"large"} /-->
		<!-- wp:post-date {"format":"j F Y","className":"er-article-date"} /-->
		<!-- wp:post-excerpt {"excerptLength":30,"moreText":""} /-->
	<!-- /wp:post-template -->
	<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
		<!-- wp:query-pagination-previous {"label":"<?php echo er_attr( _x( 'Plus récents', 'pagination du journal', 'editeurs-reunis' ) ); ?>"} /-->
		<!-- wp:query-pagination-numbers /-->
		<!-- wp:query-pagination-next {"label":"<?php echo er_attr( _x( 'Plus anciens', 'pagination du journal', 'editeurs-reunis' ) ); ?>"} /-->
	<!-- /wp:query-pagination -->
	<!-- wp:query-no-results -->
		<!-- wp:paragraph --><p><?php esc_html_e( 'Rien pour l’instant.', 'editeurs-reunis' ); ?></p><!-- /wp:paragraph -->
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
