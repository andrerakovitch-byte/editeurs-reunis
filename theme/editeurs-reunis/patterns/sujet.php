<?php
/**
 * Title: La page d'un sujet
 * Slug: editeurs-reunis/sujet
 * Inserter: no
 *
 * Le nom du sujet, sa description, puis tout ce qui le porte (le bloc er/autour de l'extension) : les livres, les
 * rencontres, les vidéos et les podcasts, les articles du journal, les pages.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"main","className":"er-page er-page-large er-sujet","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group er-page er-page-large er-sujet" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:paragraph {"align":"wide","className":"er-evt-surtitre er-sujet-surtitre"} -->
	<p class="alignwide er-evt-surtitre er-sujet-surtitre"><?php esc_html_e( 'Sujet', 'editeurs-reunis' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:query-title {"type":"archive","showPrefix":false,"align":"wide","className":"er-page-titre"} /-->
	<!-- wp:term-description {"align":"wide","className":"er-sujet-description"} /-->
	<!-- wp:er/autour /-->
</main>
<!-- /wp:group -->
