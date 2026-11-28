<?php
/**
 * Title: La frise : sept photos d’ambiance
 * Slug: editeurs-reunis/pt-frise
 * Categories: er-modules
 * Description: Toutes les photos d’ambiance sur une ligne, de marge à marge ; au téléphone, elles défilent.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-photos er-pt-frise","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-photos er-pt-frise">
<?php echo er_pt_photo( 'bleu-nuit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'ciel-pale' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'creme' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'fenetre' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'or-brun' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'orange' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<?php echo er_pt_photo( 'visage' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
</div>
<!-- /wp:group -->
