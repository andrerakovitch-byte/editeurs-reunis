<?php
/**
 * Title: À la une — une page (un poème)
 * Slug: editeurs-reunis/une-poeme
 * Categories: editeurs-reunis
 * Description: Un poème en entier, en grand, en russe, avec un dessin ; dessous, trois paragraphes pour le lire, et le livre à la librairie.
 *
 * Écrite d'après la maquette « L'accueil, à la une » du canevas (proposition/blocs_une.py).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"er-une er-une-poeme","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull er-une er-une-poeme">
	<!-- wp:html -->
	<p class="er-une-glyphe" aria-hidden="true" lang="ru">Блок</p>
	<!-- /wp:html -->

	<!-- wp:group {"align":"wide","className":"er-une-dedans","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide er-une-dedans">
		<!-- wp:paragraph {"className":"er-une-surtitre er-points-librairie"} -->
		<p class="er-une-surtitre er-points-librairie"><?php esc_html_e( 'Une page · Alexandre Blok, 1912', 'editeurs-reunis' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:columns {"align":"wide","className":"er-une-colonnes er-une-poeme-haut"} -->
		<div class="wp-block-columns alignwide er-une-colonnes er-une-poeme-haut">
			<!-- wp:column {"width":"56%","className":"er-une-strophes"} -->
			<div class="wp-block-column er-une-strophes" style="flex-basis:56%">
				<!-- wp:paragraph {"className":"er-une-strophe"} -->
				<p class="er-une-strophe"><span lang="ru">Ночь, улица, фонарь, аптека,<br>Бессмысленный и тусклый свет.<br>Живи ещё хоть четверть века —<br>Всё будет так. Исхода нет.</span></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"er-une-strophe"} -->
				<p class="er-une-strophe"><span lang="ru">Умрёшь — начнёшь опять сначала<br>И повторится всё, как встарь:<br>Ночь, ледяная рябь канала,<br>Аптека, улица, фонарь.</span></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"44%"} -->
			<div class="wp-block-column" style="flex-basis:44%">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-dessin"} -->
				<figure class="wp-block-image size-full er-une-dessin"><img src="<?php echo er_image( 'une-rue.svg' ); ?>" alt="<?php esc_attr_e( 'Une rue la nuit&nbsp;: un réverbère, une pharmacie, le canal', 'editeurs-reunis' ); ?>"/><figcaption class="wp-element-caption"><?php esc_html_e( 'La nuit, la rue, le réverbère, la pharmacie&nbsp;; et dans l’eau du canal, les mêmes, à l’envers.', 'editeurs-reunis' ); ?></figcaption></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:columns {"align":"wide","className":"er-une-colonnes er-une-poeme-bas"} -->
		<div class="wp-block-columns alignwide er-une-colonnes er-une-poeme-bas">
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Nuit, rue, réverbère, pharmacie&nbsp;: quatre mots, et la ville de Blok est là. Le poème est écrit le 10 octobre 1912&nbsp;; il fait partie du cycle «&nbsp;Danses macabres&nbsp;».', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Les deux strophes se répondent&nbsp;: la seconde reprend la première à rebours, de la nuit au réverbère, comme un reflet dans l’eau glacée du canal. Vivre encore un quart de siècle n’y changera rien&nbsp;: tout recommencera.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:paragraph -->
				<p><?php echo wp_kses_post( __( 'Blok est le grand poète du symbolisme russe. Six ans plus tard, en janvier 1918, il écrit <em>Les Douze</em>, le poème de la révolution&nbsp;: douze gardes rouges marchent dans Petrograd, sous la neige.', 'editeurs-reunis' ) ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"31%","className":"er-une-en-librairie"} -->
			<div class="wp-block-column er-une-en-librairie" style="flex-basis:31%">
				<!-- wp:paragraph {"className":"er-une-plus-titre"} -->
				<p class="er-une-plus-titre"><?php esc_html_e( 'En librairie', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:group {"className":"er-une-ref","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-ref">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'les-douze' ); ?>"><img src="<?php echo er_image( 'couv-douze.jpg' ); ?>" alt="<?php esc_attr_e( 'Couverture des Douze, d’Alexandre Blok', 'editeurs-reunis' ); ?>"/></a></figure>
					<!-- /wp:image -->

					<!-- wp:group {"className":"er-une-ref-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-ref-texte">
						<!-- wp:paragraph {"className":"er-une-ref-titre"} -->
						<p class="er-une-ref-titre"><a href="<?php echo er_livre( 'les-douze' ); ?>"><?php echo wp_kses_post( __( '<em>Les Douze</em>', 'editeurs-reunis' ) ); ?></a></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-ref-auteur"} -->
						<p class="er-une-ref-auteur"><?php esc_html_e( 'Alexandre Blok · le poème de janvier 1918', 'editeurs-reunis' ); ?> · <span class="er-une-prix"><?php echo esc_html( er_prix( 'les-douze' ) ); ?></span></p>
						<!-- /wp:paragraph -->

						<!-- wp:buttons {"className":"er-une-outils er-une-outils-petits"} -->
						<div class="wp-block-buttons er-une-outils er-une-outils-petits">
							<!-- wp:button -->
							<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_ajout_panier( 'les-douze' ); ?>"><?php esc_html_e( 'Ajouter au panier', 'editeurs-reunis' ); ?></a></div>
							<!-- /wp:button -->
						</div>
						<!-- /wp:buttons -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:paragraph {"className":"er-une-note"} -->
				<p class="er-une-note"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( 'Et la traduction du libraire, vers à vers, dans <a href="%1$s">l’encyclopédie</a>.', 'editeurs-reunis' ) ), er_lien( 'librairie/encyclopedie/age-d-argent/' ) ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
