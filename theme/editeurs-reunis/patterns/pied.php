<?php
/**
 * Title: Pied de page
 * Slug: editeurs-reunis/pied
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * Le pied noir, avec la colonne pervenche des sections de l'accueil : le nom dans la colonne ; à côté, l'adresse et
 * les horaires, les pages légales, la librairie en quelques lignes et les partenaires.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"className":"er-pied er-panneau","backgroundColor":"noir","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group er-pied er-panneau has-base-color has-noir-background-color has-text-color has-background">
	<!-- wp:columns {"align":"wide","className":"er-panneau-colonnes","style":{"spacing":{"blockGap":{"left":"0"}}}} -->
	<div class="wp-block-columns alignwide er-panneau-colonnes">
		<!-- wp:column {"width":"22%","className":"er-colonne"} -->
		<div class="wp-block-column er-colonne" style="flex-basis:22%">
			<!-- wp:paragraph {"className":"er-logo"} -->
			<p class="er-logo"><a href="<?php echo er_lien(); ?>"><span><?php echo esc_html_x( 'Centre culturel', 'logo, première ligne', 'editeurs-reunis' ); ?></span> <span><?php echo esc_html_x( 'Alexandre Soljenitsyne', 'logo, seconde ligne', 'editeurs-reunis' ); ?></span></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"small"} -->
			<p class="has-small-font-size"><?php esc_html_e( 'Notre équipe est uniquement composée de bénévoles.', 'editeurs-reunis' ); ?> <a href="<?php echo er_lien( 'ymca-press/amis-ymca-press/' ); ?>"><?php esc_html_e( 'Rejoindre l’Association des Amis d’YMCA-Press', 'editeurs-reunis' ); ?></a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"x-small","className":"er-pied-copyright"} -->
			<p class="has-x-small-font-size er-pied-copyright">© <?php echo esc_html( gmdate( 'Y' ) ); ?> Les Éditeurs réunis</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"78%","className":"er-champ"} -->
		<div class="wp-block-column er-champ" style="flex-basis:78%">
			<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
			<div class="wp-block-columns">
				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:heading {"level":2,"className":"er-pied-titre"} -->
					<h2 class="wp-block-heading er-pied-titre"><?php echo esc_html_x( 'La librairie', 'titre du pied de page', 'editeurs-reunis' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"small"} -->
					<p class="has-small-font-size">11, rue de la Montagne-Sainte-Geneviève<br>75005 Paris</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"fontSize":"small"} -->
					<p class="has-small-font-size"><?php echo wp_kses_post( __( 'Du mardi au samedi, de 10&nbsp;h&nbsp;30 à 18&nbsp;h&nbsp;30', 'editeurs-reunis' ) ); ?><br><span class="er-pied-discret"><?php esc_html_e( 'Fermée le dimanche et le lundi', 'editeurs-reunis' ); ?></span></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"fontSize":"small"} -->
					<p class="has-small-font-size"><a href="mailto:les-editeurs-reunis@orange.fr">les-editeurs-reunis@orange.fr</a></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:heading {"level":2,"className":"er-pied-titre"} -->
					<h2 class="wp-block-heading er-pied-titre"><?php echo esc_html_x( 'Informations', 'titre du pied de page', 'editeurs-reunis' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:list {"className":"er-pied-liens"} -->
					<ul class="wp-block-list er-pied-liens">
						<!-- wp:list-item --><li><a href="<?php echo er_lien( 'conditions-generales-de-vente/' ); ?>"><?php esc_html_e( 'Conditions générales de vente', 'editeurs-reunis' ); ?></a></li><!-- /wp:list-item -->
						<!-- wp:list-item --><li><a href="<?php echo er_lien( 'livraison-et-retours/' ); ?>"><?php esc_html_e( 'Livraison et retours', 'editeurs-reunis' ); ?></a></li><!-- /wp:list-item -->
						<!-- wp:list-item --><li><a href="<?php echo er_lien( 'faq/' ); ?>"><?php esc_html_e( 'Questions fréquentes', 'editeurs-reunis' ); ?></a></li><!-- /wp:list-item -->
						<!-- wp:list-item --><li><a href="<?php echo er_lien( 'politique-de-confidentialite/' ); ?>"><?php esc_html_e( 'Politique de confidentialité', 'editeurs-reunis' ); ?></a></li><!-- /wp:list-item -->
						<!-- wp:list-item --><li><a href="<?php echo er_lien( 'mentions-legales/' ); ?>"><?php esc_html_e( 'Mentions légales', 'editeurs-reunis' ); ?></a></li><!-- /wp:list-item -->
					</ul>
					<!-- /wp:list -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"38%"} -->
				<div class="wp-block-column" style="flex-basis:38%">
					<!-- wp:heading {"level":2,"className":"er-pied-titre"} -->
					<h2 class="wp-block-heading er-pied-titre"><?php echo esc_html_x( 'Depuis un demi-siècle', 'titre du pied de page', 'editeurs-reunis' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"small","className":"er-pied-discret"} -->
					<p class="has-small-font-size er-pied-discret"><?php esc_html_e( 'Au cœur du quartier latin, un vaste choix de livres neufs et d’occasion en russe et en français : la littérature russe classique et moderne, l’histoire et la civilisation russes, la pensée philosophique et la théologie orthodoxe, des manuels, des dictionnaires et des guides pour vos voyages.', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"fontSize":"x-small","className":"er-pied-discret"} -->
					<p class="has-x-small-font-size er-pied-discret"><?php echo wp_kses_post( __( 'Avec nos partenaires <strong>ACER-MJO</strong>, 91, rue Olivier-de-Serres, Paris&nbsp;15<sup>e</sup>, et la <strong>Fondation Michalski</strong>, Montricher (Suisse).', 'editeurs-reunis' ) ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
