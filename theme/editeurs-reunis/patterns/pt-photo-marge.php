<?php
/**
 * Title: La photo dans le texte, sa légende en marge
 * Slug: editeurs-reunis/pt-photo-marge
 * Categories: er-modules
 * Description: Une photo dans la colonne du texte, la légende dans la marge, puis le texte.
 *
 * Un module de la page type d'une page de texte qui informe (inc/page-type.php, assets/css/32-page-type.css).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-pt er-pt-texte er-pt-photo-marge","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-pt er-pt-texte er-pt-photo-marge">
<?php echo er_pt_photo( 'visage', '[Légende : qui, où, quand] · Photo [crédit]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- er_pt_photo() échappe. ?>
<!-- wp:paragraph -->
<p>Etiam porta sem malesuada magna mollis euismod. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Sed posuere consectetur est at lobortis.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus, posuere velit aliquet. Curabitur blandit tempus porttitor, nullam quis risus eget urna mollis ornare vel eu leo.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
