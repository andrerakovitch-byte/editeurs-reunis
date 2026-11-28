<?php
/**
 * Title: Le diptyque : deux photos et leurs légendes
 * Slug: editeurs-reunis/pt-diptyque
 * Categories: er-modules
 * Description: Deux photos côte à côte, chacune avec sa légende.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-photos er-pt-diptyque","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-photos er-pt-diptyque">
<?php echo er_pt_photo( 'orange', '[Légende : qui, où, quand] · Photo [crédit]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'ciel-pale', '[Légende : qui, où, quand] · Photo [crédit]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
</div>
<!-- /wp:group -->
