<?php
/**
 * Title: Page introuvable
 * Slug: editeurs-reunis/page-introuvable
 * Inserter: no
 *
 * Le contenu de la page 404, dans la langue de la page.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:heading {"level":1,"className":"er-page-titre"} -->
<h1 class="wp-block-heading er-page-titre"><?php esc_html_e( 'Cette page n’existe pas', 'editeurs-reunis' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php esc_html_e( 'Elle a peut-être changé d’adresse. Cherchez un livre, ou repartez de la librairie.', 'editeurs-reunis' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:search {"label":"<?php echo er_attr( __( 'Chercher dans la librairie', 'editeurs-reunis' ) ); ?>","showLabel":false,"placeholder":"<?php echo er_attr( __( 'Chercher un titre, un auteur…', 'editeurs-reunis' ) ); ?>","buttonText":"<?php echo er_attr( __( 'Chercher', 'editeurs-reunis' ) ); ?>","query":{"post_type":"product"}} /-->
