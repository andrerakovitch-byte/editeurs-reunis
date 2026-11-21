<?php
/**
 * Title: À la une — les vidéos du centre
 * Slug: editeurs-reunis/une-videos
 * Categories: editeurs-reunis
 * Description: Une vidéo à voir, avec son histoire ; à côté, les séries des bénévoles, chacune en une phrase.
 *
 * Écrite d'après la maquette « L'accueil, à la une » du canevas (proposition/blocs_une.py).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"er-une er-une-videos er-aplat-nuit","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull er-une er-une-videos er-aplat-nuit">
	<!-- wp:html -->
	<p class="er-une-glyphe" aria-hidden="true" lang="ru">Видео</p>
	<!-- /wp:html -->

	<!-- wp:columns {"align":"wide","className":"er-une-colonnes"} -->
	<div class="wp-block-columns alignwide er-une-colonnes">
		<!-- wp:column {"width":"57%","className":"er-une-videos-une"} -->
		<div class="wp-block-column er-une-videos-une" style="flex-basis:57%">
			<!-- wp:paragraph {"className":"er-une-surtitre er-points-centre"} -->
			<p class="er-une-surtitre er-points-centre"><?php esc_html_e( 'Les vidéos du centre · Le Paris de l’émigration', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-video"} -->
			<figure class="wp-block-image size-full er-une-video"><a href="<?php echo er_lien( 'centre-culturel/videos/une-video/' ); ?>"><img src="<?php echo er_image( 'une-lourmel.jpg' ); ?>" alt="<?php esc_attr_e( 'Mère Marie et ses compagnons au foyer de la rue de Lourmel&nbsp;; voir la vidéo', 'editeurs-reunis' ); ?>"/></a><figcaption class="wp-element-caption"><?php esc_html_e( 'Sainte Marie de Paris, deuxième à gauche, au foyer de la rue de Lourmel.', 'editeurs-reunis' ); ?></figcaption></figure>
			<!-- /wp:image -->

			<!-- wp:heading {"className":"er-une-videos-titre"} -->
			<h2 class="wp-block-heading er-une-videos-titre"><a href="<?php echo er_lien( 'centre-culturel/videos/une-video/' ); ?>"><?php esc_html_e( 'La rue de Lourmel et mère Marie', 'editeurs-reunis' ); ?></a></h2>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"er-une-deux-colonnes","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-deux-colonnes">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Au 77, rue de Lourmel, dans le quinzième arrondissement, mère Marie Skobtsova ouvre en 1935 un foyer pour les plus pauvres de l’émigration&nbsp;: on y loge, on y mange, on y prie.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Sous l’Occupation, la maison cache des Juifs et leur fournit de faux papiers. Arrêtée en 1943, mère Marie meurt à Ravensbrück en mars 1945. L’Église orthodoxe l’a canonisée en 2004.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( '[Une phrase&nbsp;: ce que la vidéo montre de la rue aujourd’hui.]', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"er-une-meta"} -->
			<p class="er-une-meta"><?php esc_html_e( '[14 min] · Réalisée par [Prénom Nom], bénévole · [date]', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"43%","className":"er-une-videos-series"} -->
		<div class="wp-block-column er-une-videos-series" style="flex-basis:43%">
			<!-- wp:paragraph {"className":"er-une-surtitre er-points-centre"} -->
			<p class="er-une-surtitre er-points-centre"><?php esc_html_e( 'Six séries, des rendez-vous', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"er-une-videos-chapo"} -->
			<p class="er-une-videos-chapo"><?php esc_html_e( 'Plutôt que des vidéos isolées, des séries&nbsp;: on y revient, comme à une émission.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"er-une-series","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-series">
				<!-- wp:group {"className":"er-une-serie","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-serie">
					<!-- wp:html -->
					<span class="er-lettrine er-lettrine-petite er-aplat-nuit" aria-hidden="true" lang="ru">К</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-serie-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-serie-texte">
						<!-- wp:group {"className":"er-une-serie-tete","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-serie-tete">
							<!-- wp:paragraph {"className":"er-une-serie-titre"} -->
							<p class="er-une-serie-titre"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><?php esc_html_e( 'Un livre, cinq minutes', 'editeurs-reunis' ); ?></a></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":"er-une-serie-nombre"} -->
							<p class="er-une-serie-nombre"><?php esc_html_e( '[8] vidéos', 'editeurs-reunis' ); ?></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:paragraph {"className":"er-une-serie-desc"} -->
						<p class="er-une-serie-desc"><?php esc_html_e( 'Un lecteur, un livre de la librairie, cinq minutes pour donner envie.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-serie","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-serie">
					<!-- wp:html -->
					<span class="er-lettrine er-lettrine-petite er-aplat-vert" aria-hidden="true" lang="ru">П</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-serie-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-serie-texte">
						<!-- wp:group {"className":"er-une-serie-tete","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-serie-tete">
							<!-- wp:paragraph {"className":"er-une-serie-titre"} -->
							<p class="er-une-serie-titre"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><?php esc_html_e( 'Le Paris de l’émigration', 'editeurs-reunis' ); ?></a></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":"er-une-serie-nombre"} -->
							<p class="er-une-serie-nombre"><?php esc_html_e( '[4] vidéos', 'editeurs-reunis' ); ?></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:paragraph {"className":"er-une-serie-desc"} -->
						<p class="er-une-serie-desc"><?php esc_html_e( 'Un lieu par épisode&nbsp;: la rue Daru, la rue de Lourmel, Montparnasse, Sainte-Geneviève-des-Bois.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-serie","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-serie">
					<!-- wp:html -->
					<span class="er-lettrine er-lettrine-petite er-aplat-ymca" aria-hidden="true" lang="ru">Г</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-serie-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-serie-texte">
						<!-- wp:group {"className":"er-une-serie-tete","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-serie-tete">
							<!-- wp:paragraph {"className":"er-une-serie-titre"} -->
							<p class="er-une-serie-titre"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><?php esc_html_e( 'À voix haute', 'editeurs-reunis' ); ?></a></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":"er-une-serie-nombre"} -->
							<p class="er-une-serie-nombre"><?php esc_html_e( '[12] vidéos', 'editeurs-reunis' ); ?></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:paragraph {"className":"er-une-serie-desc"} -->
						<p class="er-une-serie-desc"><?php esc_html_e( 'Des poèmes et des pages lus en russe et en français, de Pouchkine à Blok.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-serie","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-serie">
					<!-- wp:html -->
					<span class="er-lettrine er-lettrine-petite er-aplat-noir" aria-hidden="true" lang="ru">А</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-serie-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-serie-texte">
						<!-- wp:group {"className":"er-une-serie-tete","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-serie-tete">
							<!-- wp:paragraph {"className":"er-une-serie-titre"} -->
							<p class="er-une-serie-titre"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><?php esc_html_e( 'Dans les archives', 'editeurs-reunis' ); ?></a></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":"er-une-serie-nombre"} -->
							<p class="er-une-serie-nombre"><?php esc_html_e( '[3] vidéos', 'editeurs-reunis' ); ?></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:paragraph {"className":"er-une-serie-desc"} -->
						<p class="er-une-serie-desc"><?php esc_html_e( 'On ouvre une boîte des archives d’YMCA-Press, et on raconte ce qu’on y trouve.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-serie","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-serie">
					<!-- wp:html -->
					<span class="er-lettrine er-lettrine-petite er-aplat-nuit" aria-hidden="true" lang="ru">Б</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-serie-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-serie-texte">
						<!-- wp:group {"className":"er-une-serie-tete","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-serie-tete">
							<!-- wp:paragraph {"className":"er-une-serie-titre"} -->
							<p class="er-une-serie-titre"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><?php esc_html_e( 'Les entretiens', 'editeurs-reunis' ); ?></a></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":"er-une-serie-nombre"} -->
							<p class="er-une-serie-nombre"><?php esc_html_e( '[6] vidéos', 'editeurs-reunis' ); ?></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:paragraph {"className":"er-une-serie-desc"} -->
						<p class="er-une-serie-desc"><?php esc_html_e( 'Des traducteurs, des auteurs, des chercheurs, invités au centre.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-serie","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-serie">
					<!-- wp:html -->
					<span class="er-lettrine er-lettrine-petite er-aplat-pervenche" aria-hidden="true" lang="ru">А</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-serie-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-serie-texte">
						<!-- wp:group {"className":"er-une-serie-tete","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-serie-tete">
							<!-- wp:paragraph {"className":"er-une-serie-titre"} -->
							<p class="er-une-serie-titre"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><?php esc_html_e( 'Premiers pas en russe', 'editeurs-reunis' ); ?></a></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":"er-une-serie-nombre"} -->
							<p class="er-une-serie-nombre"><?php esc_html_e( '[10] vidéos', 'editeurs-reunis' ); ?></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:paragraph {"className":"er-une-serie-desc"} -->
						<p class="er-une-serie-desc"><?php esc_html_e( 'Des leçons courtes, pour accompagner les manuels de la librairie.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"er-une-lien"} -->
			<p class="er-une-lien"><a href="<?php echo er_lien( 'centre-culturel/videos/' ); ?>"><?php esc_html_e( 'Toutes les vidéos', 'editeurs-reunis' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
