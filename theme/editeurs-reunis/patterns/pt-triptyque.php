<?php
/**
 * Title: Le triptyque : trois photos d’ambiance
 * Slug: editeurs-reunis/pt-triptyque
 * Categories: er-modules
 * Description: Trois photos côte à côte, de marge à marge, et une légende.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-photos er-pt-triptyque","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-photos er-pt-triptyque">
<?php echo er_pt_photo( 'fenetre' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'bleu-nuit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'creme' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<!-- wp:paragraph {"className":"er-pt-legende"} -->
<p class="er-pt-legende">[Légende : qui, où, quand] · Photo [crédit]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
