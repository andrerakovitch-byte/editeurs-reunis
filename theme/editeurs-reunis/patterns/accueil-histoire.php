<?php
/**
 * Title: Accueil — notre histoire
 * Slug: editeurs-reunis/accueil-histoire
 * Categories: editeurs-reunis
 * Description: Comme une diapositive : la bande pervenche, un aplat brun, l'histoire de la maison en blanc et, à droite, l'année de sa fondation.
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"align":"full","className":"er-panneau er-panneau-sombre er-histoire","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-panneau er-panneau-sombre er-histoire">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"er-diapo-contenu"} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center er-diapo-contenu">
		<!-- wp:column {"verticalAlignment":"center","width":"64%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:64%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre">YMCA-Press</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-diapo-titre"} -->
			<h2 class="wp-block-heading er-diapo-titre">Découvrez notre histoire centenaire</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-diapo-texte"} -->
			<p class="er-diapo-texte">L’histoire centenaire des éditions YMCA-Press, cœur vibrant de la littérature russe du XX<sup>e</sup>&nbsp;siècle, entre Bounine, Akhmatova, Mandelstam, Boulgakov et Soljenitsyne.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-fleche"} -->
				<div class="wp-block-button is-style-fleche"><a class="wp-block-button__link wp-element-button" href="<?php echo er_lien( 'ymca-press/histoire/' ); ?>">Un siècle d’histoire</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"36%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:36%">
			<!-- wp:paragraph {"className":"er-annee"} -->
			<p class="er-annee"><span>Depuis</span> 1921</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
