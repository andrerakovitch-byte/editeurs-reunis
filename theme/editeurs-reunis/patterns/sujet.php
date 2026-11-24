<?php
/**
 * Title: La page d'un sujet
 * Slug: editeurs-reunis/sujet
 * Inserter: no
 *
 * La tête de page (le nom du sujet, sa description, ce qui le porte), la bande de page (le sommaire), puis tout ce
 * qui le porte (le bloc er/autour de l'extension) : les livres, les rencontres, les vidéos et les podcasts, les
 * articles du journal, les pages.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"main","className":"er-page er-page-large er-sujet","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group er-page er-page-large er-sujet" style="padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:editeurs-reunis/tete /-->
<?php
// La bande de page : le sommaire de ce qui porte le sujet. Un sujet n'a pas de maison : la bande est sur le fond doux,
// avec les trois points, et s'accroche seule en haut de l'écran.
$er_sujet = get_queried_object();
if ( $er_sujet instanceof WP_Term && function_exists( 'er_autour_sommaire' ) && function_exists( 'er_bande_page' ) ) :
	$er_entrees = er_autour_sommaire( $er_sujet );
	if ( $er_entrees ) {
		$er_entrees[0]['courant'] = true;
	}
	$er_bande = er_bande_page(
		array(
			'maison'   => '',
			'nom'      => $er_sujet->name,
			'sommaire' => true,
			'entrees'  => $er_entrees,
		)
	);
	if ( $er_bande ) :
		?>
	<!-- wp:html -->
	<?php echo $er_bande; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<!-- /wp:html -->
		<?php
	endif;
endif;
?>
	<!-- wp:er/autour /-->
</main>
<!-- /wp:group -->
