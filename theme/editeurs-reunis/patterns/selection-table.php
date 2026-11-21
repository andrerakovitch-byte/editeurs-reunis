<?php
/**
 * Title: Une sélection — la table, sur un aplat
 * Slug: editeurs-reunis/selection-table
 * Categories: editeurs-reunis
 * Description: Plusieurs livres sous un même titre : le titre, un texte comme un article, une vidéo ou un podcast ; à droite, les livres en liste, chacun avec ce qui le relie aux autres, le prix en petit et « Ajouter au panier ».
 *
 * Écrite d'après la maquette « L'accueil, à la une » du canevas (proposition/blocs_une.py).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"er-une er-une-selection er-une-table er-aplat-vert","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull er-une er-une-selection er-une-table er-aplat-vert">
	<!-- wp:html -->
	<p class="er-une-glyphe" aria-hidden="true" lang="ru">Гроза</p>
	<!-- /wp:html -->

	<!-- wp:columns {"align":"wide","className":"er-une-colonnes"} -->
	<div class="wp-block-columns alignwide er-une-colonnes">
		<!-- wp:column {"width":"58%","className":"er-une-selection-texte"} -->
		<div class="wp-block-column er-une-selection-texte" style="flex-basis:58%">
			<!-- wp:paragraph {"className":"er-une-rubrique er-points-librairie er-une-rubrique-claire"} -->
			<p class="er-une-rubrique er-points-librairie er-une-rubrique-claire"><?php esc_html_e( 'Une sélection · la librairie', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-une-affiche-titre er-une-titre-long"} -->
			<h2 class="wp-block-heading er-une-affiche-titre er-une-titre-long"><?php esc_html_e( 'Avant l’orage, 1901&nbsp;–&nbsp;1918', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-une-chapo"} -->
			<p class="er-une-chapo"><?php esc_html_e( 'Entre un médecin qui dit ses erreurs et douze gardes rouges dans la neige, les dernières années d’un monde, et l’une des époques les plus inventives de la littérature russe.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"er-une-deux-colonnes","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-deux-colonnes">
				<!-- wp:paragraph -->
				<p><?php echo wp_kses_post( __( 'En 1901, un jeune médecin, Vikenti Veressaïev, raconte sans fard ses débuts à l’hôpital, ses doutes et ses erreurs&nbsp;: ses <em>Notes d’un médecin</em> font scandale. C’est le début d’un temps qu’on appellera après coup l’Âge d’argent, où la poésie, le théâtre et la prose russes se cherchent et se répondent.', 'editeurs-reunis' ) ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php echo wp_kses_post( __( 'Au théâtre, Léonid Andréïev met en scène des âmes troublées (<em>Ekatérina Ivanovna</em>, 1912). En poésie, Nikolaï Goumilev, avec Anna Akhmatova et Ossip Mandelstam, veut des mots clairs et des choses précises, contre le flou du symbolisme&nbsp;: c’est l’acméisme.', 'editeurs-reunis' ) ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php echo wp_kses_post( __( 'En janvier 1918, Alexandre Blok salue la révolution dans <em>Les Douze</em>&nbsp;: douze gardes rouges marchent dans Petrograd, sous la neige, et le Christ marche devant eux. Trois ans plus tard, en août 1921, Blok meurt, et Goumilev est fusillé.', 'editeurs-reunis' ) ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Ces cinq livres, tous à la librairie, en racontent les années, du cabinet du médecin aux rues de Petrograd. Le plus rare est une anthologie en russe, dont nous n’avons qu’un exemplaire&nbsp;: on peut venir la feuilleter.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"er-une-a-voir","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-a-voir">
				<!-- wp:paragraph {"className":"er-une-plus-titre"} -->
				<p class="er-une-plus-titre"><?php esc_html_e( 'À voir, à écouter', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:group {"className":"er-une-media","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
				<div class="wp-block-group er-une-media">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-video"} -->
					<figure class="wp-block-image size-full er-une-video"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><img src="<?php echo er_image( 'une-video-blok.svg' ); ?>" alt="<?php esc_attr_e( 'Voir la vidéo&nbsp;: Blok, lu en russe et en français', 'editeurs-reunis' ); ?>"/></a></figure>
					<!-- /wp:image -->

					<!-- wp:group {"className":"er-une-media-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-media-texte">
						<!-- wp:paragraph {"className":"er-une-media-genre"} -->
						<p class="er-une-media-genre"><?php esc_html_e( 'Vidéo · À voix haute', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-media-titre"} -->
						<p class="er-une-media-titre"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><?php esc_html_e( 'Blok, lu en russe et en français', 'editeurs-reunis' ); ?></a></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-meta"} -->
						<p class="er-une-meta"><?php esc_html_e( '[6 min] · [Prénom Nom], bénévole', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-media-liens","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-media-liens">
					<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-plus">
						<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
						<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>L’encyclopédie</strong> <a href="%1$s">L’Âge d’argent, l’article du jour</a>', 'editeurs-reunis' ) ), er_lien( 'librairie/encyclopedie/age-d-argent/' ) ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-plus">
						<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
						<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>Podcast</strong> <a href="%1$s">[Un épisode sur Blok et Les Douze]</a>', 'editeurs-reunis' ) ), er_lien( 'centre-culturel/podcasts/' ) ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"42%","className":"er-une-selection-livres"} -->
		<div class="wp-block-column er-une-selection-livres" style="flex-basis:42%">
			<!-- wp:paragraph {"className":"er-une-plus-titre"} -->
			<p class="er-une-plus-titre"><?php esc_html_e( 'Les cinq livres', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"er-une-livres","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-livres">
				<!-- wp:group {"className":"er-une-livre er-une-selection-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre er-une-selection-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'notes-dun-medecin' ); ?>"><img src="<?php echo er_image( 'chef-doeuvre-veressaiev.jpg' ); ?>" alt="<?php esc_attr_e( 'Couverture&nbsp;: Notes d’un médecin', 'editeurs-reunis' ); ?>"/></a></figure>
					<!-- /wp:image -->

					<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-livre-texte">
						<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
						<p class="er-une-livre-titre"><a href="<?php echo er_livre( 'notes-dun-medecin' ); ?>"><em><?php esc_html_e( 'Notes d’un médecin', 'editeurs-reunis' ); ?></em></a></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
						<p class="er-une-livre-auteur"><?php esc_html_e( 'Vikenti Veressaïev · 1901', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-pourquoi"} -->
						<p class="er-une-livre-pourquoi"><?php esc_html_e( 'Le livre qui fit scandale&nbsp;: un médecin dit ses doutes et ses erreurs.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:group {"className":"er-une-achat","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
						<div class="wp-block-group er-une-achat">
							<!-- wp:paragraph {"className":"er-une-prix"} -->
							<p class="er-une-prix"><?php echo esc_html( er_prix( 'notes-dun-medecin' ) ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons {"className":"er-une-outils er-une-outils-petits"} -->
							<div class="wp-block-buttons er-une-outils er-une-outils-petits">
								<!-- wp:button -->
								<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_ajout_panier( 'notes-dun-medecin' ); ?>"><?php esc_html_e( 'Ajouter au panier', 'editeurs-reunis' ); ?></a></div>
								<!-- /wp:button -->
							</div>
							<!-- /wp:buttons -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-livre er-une-selection-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre er-une-selection-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'ekaterina-ivanovna' ); ?>"><img src="<?php echo er_image( 'couv-ekaterina.jpg' ); ?>" alt="<?php esc_attr_e( 'Couverture&nbsp;: Ekatérina Ivanovna, suivi de Requiem', 'editeurs-reunis' ); ?>"/></a></figure>
					<!-- /wp:image -->

					<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-livre-texte">
						<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
						<p class="er-une-livre-titre"><a href="<?php echo er_livre( 'ekaterina-ivanovna' ); ?>"><em><?php esc_html_e( 'Ekatérina Ivanovna, suivi de Requiem', 'editeurs-reunis' ); ?></em></a></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
						<p class="er-une-livre-auteur"><?php esc_html_e( 'Léonid Andréïev · 1912', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-pourquoi"} -->
						<p class="er-une-livre-pourquoi"><?php esc_html_e( 'Le théâtre d’Andréïev&nbsp;: deux pièces, des âmes troublées.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:group {"className":"er-une-achat","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
						<div class="wp-block-group er-une-achat">
							<!-- wp:paragraph {"className":"er-une-prix"} -->
							<p class="er-une-prix"><?php echo esc_html( er_prix( 'ekaterina-ivanovna' ) ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons {"className":"er-une-outils er-une-outils-petits"} -->
							<div class="wp-block-buttons er-une-outils er-une-outils-petits">
								<!-- wp:button -->
								<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_ajout_panier( 'ekaterina-ivanovna' ); ?>"><?php esc_html_e( 'Ajouter au panier', 'editeurs-reunis' ); ?></a></div>
								<!-- /wp:button -->
							</div>
							<!-- /wp:buttons -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-livre er-une-selection-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre er-une-selection-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'poesies-choisies-goumilev' ); ?>"><img src="<?php echo er_image( 'couv-goumilev.jpg' ); ?>" alt="<?php esc_attr_e( 'Couverture&nbsp;: Poésies choisies', 'editeurs-reunis' ); ?>"/></a></figure>
					<!-- /wp:image -->

					<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-livre-texte">
						<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
						<p class="er-une-livre-titre"><a href="<?php echo er_livre( 'poesies-choisies-goumilev' ); ?>"><em><?php esc_html_e( 'Poésies choisies', 'editeurs-reunis' ); ?></em></a></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
						<p class="er-une-livre-auteur"><?php esc_html_e( 'Nikolaï Goumilev', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-pourquoi"} -->
						<p class="er-une-livre-pourquoi"><?php esc_html_e( 'L’acméisme&nbsp;: le mot exact, contre le flou du symbolisme.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:group {"className":"er-une-achat","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
						<div class="wp-block-group er-une-achat">
							<!-- wp:paragraph {"className":"er-une-prix"} -->
							<p class="er-une-prix"><?php echo esc_html( er_prix( 'poesies-choisies-goumilev' ) ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons {"className":"er-une-outils er-une-outils-petits"} -->
							<div class="wp-block-buttons er-une-outils er-une-outils-petits">
								<!-- wp:button -->
								<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_ajout_panier( 'poesies-choisies-goumilev' ); ?>"><?php esc_html_e( 'Ajouter au panier', 'editeurs-reunis' ); ?></a></div>
								<!-- /wp:button -->
							</div>
							<!-- /wp:buttons -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-livre er-une-selection-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre er-une-selection-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'les-douze' ); ?>"><img src="<?php echo er_image( 'couv-douze.jpg' ); ?>" alt="<?php esc_attr_e( 'Couverture&nbsp;: Les Douze', 'editeurs-reunis' ); ?>"/></a></figure>
					<!-- /wp:image -->

					<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-livre-texte">
						<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
						<p class="er-une-livre-titre"><a href="<?php echo er_livre( 'les-douze' ); ?>"><em><?php esc_html_e( 'Les Douze', 'editeurs-reunis' ); ?></em></a></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
						<p class="er-une-livre-auteur"><?php esc_html_e( 'Alexandre Blok · 1918', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-pourquoi"} -->
						<p class="er-une-livre-pourquoi"><?php esc_html_e( 'La révolution en un poème&nbsp;: douze gardes rouges dans la neige.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:group {"className":"er-une-achat","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
						<div class="wp-block-group er-une-achat">
							<!-- wp:paragraph {"className":"er-une-prix"} -->
							<p class="er-une-prix"><?php echo esc_html( er_prix( 'les-douze' ) ); ?></p>
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
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-livre er-une-selection-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre er-une-selection-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'russkaia-poezia' ); ?>"><img src="<?php echo er_image( 'couv-russkaia.jpg' ); ?>" alt=""/></a></figure>
					<!-- /wp:image -->

					<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-livre-texte">
						<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
						<p class="er-une-livre-titre"><a href="<?php echo er_livre( 'russkaia-poezia' ); ?>"><em><span lang="ru">Русская поэзия</span></em></a></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
						<p class="er-une-livre-auteur"><?php esc_html_e( 'Anthologie · très rare', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-livre-pourquoi"} -->
						<p class="er-une-livre-pourquoi"><?php esc_html_e( 'Un seul exemplaire, à venir feuilleter à la librairie.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:group {"className":"er-une-achat","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
						<div class="wp-block-group er-une-achat">
							<!-- wp:paragraph {"className":"er-une-prix"} -->
							<p class="er-une-prix"><?php echo esc_html( er_prix( 'russkaia-poezia' ) ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons {"className":"er-une-outils er-une-outils-petits"} -->
							<div class="wp-block-buttons er-une-outils er-une-outils-petits">
								<!-- wp:button -->
								<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_ajout_panier( 'russkaia-poezia' ); ?>"><?php esc_html_e( 'Ajouter au panier', 'editeurs-reunis' ); ?></a></div>
								<!-- /wp:button -->
							</div>
							<!-- /wp:buttons -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"er-une-lien"} -->
			<p class="er-une-lien"><a href="<?php echo er_lien( 'librairie/' ); ?>"><?php esc_html_e( 'Toute la sélection, en librairie', 'editeurs-reunis' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
