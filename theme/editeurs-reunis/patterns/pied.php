<?php
/**
 * Title: Pied de page
 * Slug: editeurs-reunis/pied
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * Le pied noir : qui nous sommes, l'adresse et les horaires, les pages légales, puis les partenaires.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"className":"er-pied","backgroundColor":"noir","textColor":"base","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group er-pied has-base-color has-noir-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"42%"} -->
		<div class="wp-block-column" style="flex-basis:42%">
			<!-- wp:paragraph {"className":"er-logo"} -->
			<p class="er-logo"><a href="<?php echo er_lien(); ?>"><span>Centre culturel</span> <span>Alexandre Soljenitsyne</span></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"small","className":"er-pied-discret"} -->
			<p class="has-small-font-size er-pied-discret">Implantée au cœur du quartier latin depuis un demi-siècle, la librairie propose un vaste choix de livres neufs et d’occasion en russe et en français : les grands auteurs de la littérature russe classique et moderne, l’histoire et la civilisation russes, la pensée philosophique et la théologie orthodoxe, des manuels, des dictionnaires et des guides pour vos voyages.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"30%"} -->
		<div class="wp-block-column" style="flex-basis:30%">
			<!-- wp:heading {"level":2,"className":"er-pied-titre"} -->
			<h2 class="wp-block-heading er-pied-titre">La librairie</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"small"} -->
			<p class="has-small-font-size">11, rue de la Montagne-Sainte-Geneviève<br>75005 Paris</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"small"} -->
			<p class="has-small-font-size">Du mardi au samedi, de 10&nbsp;h&nbsp;30 à 18&nbsp;h&nbsp;30<br><span class="er-pied-discret">Fermée le dimanche et le lundi</span></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"small"} -->
			<p class="has-small-font-size"><a href="mailto:les-editeurs-reunis@orange.fr">les-editeurs-reunis@orange.fr</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"28%"} -->
		<div class="wp-block-column" style="flex-basis:28%">
			<!-- wp:heading {"level":2,"className":"er-pied-titre"} -->
			<h2 class="wp-block-heading er-pied-titre">Informations</h2>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"er-pied-liens"} -->
			<ul class="wp-block-list er-pied-liens">
				<!-- wp:list-item --><li><a href="<?php echo er_lien( 'conditions-generales-de-vente/' ); ?>">Conditions générales de vente</a></li><!-- /wp:list-item -->
				<!-- wp:list-item --><li><a href="<?php echo er_lien( 'livraison-et-retours/' ); ?>">Livraison et retours</a></li><!-- /wp:list-item -->
				<!-- wp:list-item --><li><a href="<?php echo er_lien( 'faq/' ); ?>">Questions fréquentes</a></li><!-- /wp:list-item -->
				<!-- wp:list-item --><li><a href="<?php echo er_lien( 'politique-de-confidentialite/' ); ?>">Politique de confidentialité</a></li><!-- /wp:list-item -->
				<!-- wp:list-item --><li><a href="<?php echo er_lien( 'mentions-legales/' ); ?>">Mentions légales</a></li><!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:group {"className":"alignwide er-pied-bas","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide er-pied-bas">
		<!-- wp:paragraph {"fontSize":"x-small","className":"er-pied-discret"} -->
		<p class="has-x-small-font-size er-pied-discret">Avec nos partenaires <strong>ACER-MJO</strong>, 91, rue Olivier-de-Serres, Paris&nbsp;15<sup>e</sup>, et la <strong>Fondation Michalski</strong>, Montricher (Suisse).</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"fontSize":"x-small","className":"er-pied-discret"} -->
		<p class="has-x-small-font-size er-pied-discret">© <?php echo esc_html( gmdate( 'Y' ) ); ?> Les Éditeurs réunis · Une équipe de bénévoles · <a href="<?php echo er_lien( 'ymca-press/amis-ymca-press/' ); ?>">Nous rejoindre</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
