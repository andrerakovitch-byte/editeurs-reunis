<?php
/**
 * Title: À la une — une affiche (Tchevengour)
 * Slug: editeurs-reunis/une-tchevengour
 * Categories: editeurs-reunis
 * Description: Une affiche sur le noir : un livre, ses faits, ses boutons, pour aller plus loin, et le texte avec son dessin.
 *
 * Écrite d'après la maquette « L'accueil, à la une » du canevas (proposition/blocs_une.py).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"er-une er-une-affiche er-aplat-noir","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull er-une er-une-affiche er-aplat-noir">
	<!-- wp:html -->
	<p class="er-une-glyphe" aria-hidden="true" lang="ru">Чевенгур</p>
	<!-- /wp:html -->

	<!-- wp:columns {"align":"wide","className":"er-une-colonnes"} -->
	<div class="wp-block-columns alignwide er-une-colonnes">
		<!-- wp:column {"width":"45%","className":"er-une-affiche-gauche"} -->
		<div class="wp-block-column er-une-affiche-gauche" style="flex-basis:45%">
			<!-- wp:paragraph {"className":"er-une-rubrique er-points-librairie er-une-rubrique-claire"} -->
			<p class="er-une-rubrique er-points-librairie er-une-rubrique-claire"><?php esc_html_e( 'Un livre, une vie · la librairie', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-une-affiche-titre er-une-titre-long"} -->
			<h2 class="wp-block-heading er-une-affiche-titre er-une-titre-long"><?php esc_html_e( 'Tchevengour', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"er-une-faits","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-faits">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-couverture"} -->
				<figure class="wp-block-image size-full er-une-couverture"><img src="<?php echo er_image( 'couv-tchevengour.jpg' ); ?>" alt="<?php esc_attr_e( 'Couverture de Tchevengour, d’Andreï Platonov', 'editeurs-reunis' ); ?>"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"er-une-faits-grille","layout":{"type":"grid","columnCount":2}} -->
				<div class="wp-block-group er-une-faits-grille">
					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Paru en URSS', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( 'en entier, en 1988', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Éditeur', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( '[éditeur de la nouvelle édition]', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Auteur', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( 'Andreï Platonov', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Pages', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( '[nombre]', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'À la librairie', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( 'nouvelle édition', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait er-une-fait-petit","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait er-une-fait-petit">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Prix', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php echo esc_html( er_prix( 'tchevengour' ) ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:buttons {"className":"er-une-outils"} -->
			<div class="wp-block-buttons er-une-outils">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_ajout_panier( 'tchevengour' ); ?>"><?php esc_html_e( 'Ajouter au panier', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_lien( 'librairie/encyclopedie/platonov/' ); ?>"><?php esc_html_e( 'Platonov, dans l’encyclopédie', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

			<!-- wp:group {"className":"er-une-plus-loin","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-plus-loin">
				<!-- wp:paragraph {"className":"er-une-plus-titre"} -->
				<p class="er-une-plus-titre"><?php esc_html_e( 'Pour aller plus loin', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-plus">
					<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>En librairie</strong> <a href="%1$s">Boulgakov, <em>Le Maître et Marguerite</em></a>', 'editeurs-reunis' ) ), er_lien( 'librairie/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( 'Écrit lui aussi dans les années 1930, sans espoir de publication&nbsp;; paru en URSS, censuré, en 1966-1967.', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-plus">
					<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>Vidéo</strong> <a href="%1$s">Zamiatine, <em>Nous</em>, vu par André Markowicz</a>', 'editeurs-reunis' ) ), er_lien( 'centre-culturel/videos/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( 'Une autre utopie, écrite en 1920&nbsp;: refusée en URSS, elle paraît d’abord en anglais, à New York, en 1924.', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-plus">
					<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>Le fonds d’archives</strong> <a href="%1$s">1917, et après</a>', 'editeurs-reunis' ) ), er_lien( 'ymca-press/catalogue/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( 'La révolution vue par ceux qui l’ont vécue, de Blok à Platonov.', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"55%","className":"er-une-affiche-droite"} -->
		<div class="wp-block-column er-une-affiche-droite" style="flex-basis:55%">
			<!-- wp:paragraph {"className":"er-une-chapo"} -->
			<p class="er-une-chapo"><?php esc_html_e( 'Le roman d’une utopie communiste dans un bourg de la steppe, que son auteur n’a jamais vu paraître en entier.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-dessin"} -->
			<figure class="wp-block-image size-full er-une-dessin"><img src="<?php echo er_image( 'une-steppe.svg' ); ?>" alt="<?php esc_attr_e( 'Le soleil sur la steppe, un petit bourg', 'editeurs-reunis' ); ?>"/><figcaption class="wp-element-caption"><?php esc_html_e( 'Le bourg de Tchevengour&nbsp;: on a cessé de travailler, le soleil travaille pour tous.', 'editeurs-reunis' ); ?></figcaption></figure>
			<!-- /wp:image -->

			<!-- wp:group {"className":"er-une-deux-colonnes","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-deux-colonnes">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Platonov écrit Tchevengour à la fin des années 1920. Dans un bourg perdu de la steppe, une poignée de révolutionnaires décide que le communisme est arrivé&nbsp;: ils chassent les «&nbsp;bourgeois&nbsp;», cessent de travailler, et laissent le soleil travailler pour eux.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Sa langue, faite de mots de la propagande pris au pied de la lettre, est l’une des plus singulières de la littérature russe&nbsp;: on y entend à la fois l’espoir de la révolution et son absurdité.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Refusé de son vivant, le livre ne paraît en entier en URSS qu’en 1988, presque quarante ans après la mort de son auteur, en 1951.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
