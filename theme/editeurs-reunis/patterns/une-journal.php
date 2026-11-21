<?php
/**
 * Title: À la une — le journal du centre
 * Slug: editeurs-reunis/une-journal
 * Categories: editeurs-reunis
 * Description: L’article du mois en grand, avec sa photo et deux colonnes de texte ; à côté, les derniers billets et ceux qui écrivent dans le journal.
 *
 * Écrite d'après la maquette « L'accueil, à la une » du canevas (proposition/blocs_une.py).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"er-une er-une-journal","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull er-une er-une-journal">
	<!-- wp:columns {"align":"wide","className":"er-une-colonnes"} -->
	<div class="wp-block-columns alignwide er-une-colonnes">
		<!-- wp:column {"width":"59%","className":"er-une-journal-article"} -->
		<div class="wp-block-column er-une-journal-article" style="flex-basis:59%">
			<!-- wp:paragraph {"className":"er-une-surtitre er-points-centre"} -->
			<p class="er-une-surtitre er-points-centre"><?php esc_html_e( 'Le journal du centre · L’article du mois', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-une-journal-titre"} -->
			<h2 class="wp-block-heading er-une-journal-titre"><a href="<?php echo er_lien( 'centre-culturel/paris-emigration/' ); ?>"><?php esc_html_e( 'Le Paris de l’émigration russe', 'editeurs-reunis' ); ?></a></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-une-journal-chapo"} -->
			<p class="er-une-journal-chapo"><?php esc_html_e( 'Une cathédrale, un institut de théologie, des cafés, une cité d’artistes, des usines, un cimetière&nbsp;: dix lieux pour raconter la ville où s’est installée, après 1917, la Russie en exil.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-photo er-une-gris er-une-photo-large"} -->
			<figure class="wp-block-image size-full er-une-photo er-une-gris er-une-photo-large"><img src="<?php echo er_image( 'une-congres.jpg' ); ?>" alt="<?php esc_attr_e( 'Un groupe d’étudiants russes devant une maison, à la fin des années 1920', 'editeurs-reunis' ); ?>"/><figcaption class="wp-element-caption"><?php esc_html_e( 'Congrès de l’Action chrétienne des étudiants russes, à la fin des années 1920&nbsp;: Berdiaev au centre.', 'editeurs-reunis' ); ?></figcaption></figure>
			<!-- /wp:image -->

			<!-- wp:group {"className":"er-une-deux-colonnes","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-deux-colonnes">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Après la révolution et la guerre civile, des dizaines de milliers de Russes s’installent à Paris, qui devient, avec Berlin et Prague, l’une des capitales de l’émigration.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'La cathédrale Saint-Alexandre-Nevski, rue Daru, en est le cœur&nbsp;; l’institut de théologie Saint-Serge ouvre en 1925, rue de Crimée. Beaucoup travaillent aux usines Renault de Billancourt&nbsp;; beaucoup reposent au cimetière de Sainte-Geneviève-des-Bois.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'L’article suit ces lieux un à un, sur une carte, avec trois promenades à faire à pied. La librairie, rue de la Montagne-Sainte-Geneviève, en est l’une des adresses.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"er-une-pied","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
			<div class="wp-block-group er-une-pied">
				<!-- wp:paragraph {"className":"er-une-meta"} -->
				<p class="er-une-meta"><?php esc_html_e( 'Par [l’auteur] · [date] · dix lieux, trois promenades, [4] vidéos', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"er-une-lien"} -->
				<p class="er-une-lien"><a href="<?php echo er_lien( 'centre-culturel/paris-emigration/' ); ?>"><?php esc_html_e( 'Lire l’article', 'editeurs-reunis' ); ?></a></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"41%","className":"er-une-journal-billets"} -->
		<div class="wp-block-column er-une-journal-billets" style="flex-basis:41%">
			<!-- wp:paragraph {"className":"er-une-surtitre er-points-centre"} -->
			<p class="er-une-surtitre er-points-centre"><?php esc_html_e( 'Les billets', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"er-une-billets","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-billets">
				<!-- wp:group {"className":"er-une-billet","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-billet">
					<!-- wp:html -->
					<span class="er-lettrine er-aplat-nuit" aria-hidden="true" lang="ru">Г</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-billet-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-billet-texte">
						<!-- wp:paragraph {"className":"er-une-billet-serie"} -->
						<p class="er-une-billet-serie"><?php esc_html_e( 'Les billets d’Yves Hamant', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:heading {"level":3,"className":"er-une-billet-titre"} -->
						<h3 class="wp-block-heading er-une-billet-titre"><a href="<?php echo er_lien( 'journal/' ); ?>"><?php esc_html_e( 'Anne-Marie Pelletier, Résister avec Vassily Grossman', 'editeurs-reunis' ); ?></a></h3>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"className":"er-une-billet-chapo"} -->
						<p class="er-une-billet-chapo"><?php echo wp_kses_post( __( 'Yves Hamant lit le livre qu’Anne-Marie Pelletier consacre à Vassily Grossman, l’auteur de <em>Vie et destin</em>, et à ce que résister veut dire chez lui.', 'editeurs-reunis' ) ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-billet-meta"} -->
						<p class="er-une-billet-meta"><?php esc_html_e( 'Yves Hamant · 27 janvier · 10 min', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-billet","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-billet">
					<!-- wp:html -->
					<span class="er-lettrine er-aplat-ymca" aria-hidden="true" lang="ru">Л</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-billet-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-billet-texte">
						<!-- wp:paragraph {"className":"er-une-billet-serie"} -->
						<p class="er-une-billet-serie"><?php esc_html_e( 'Les billets de Dominique Samson', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:heading {"level":3,"className":"er-une-billet-titre"} -->
						<h3 class="wp-block-heading er-une-billet-titre"><a href="<?php echo er_lien( 'journal/' ); ?>"><?php esc_html_e( 'Du jardin secret des Lykov, vieux-croyants de Sibérie', 'editeurs-reunis' ); ?></a></h3>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"className":"er-une-billet-chapo"} -->
						<p class="er-une-billet-chapo"><?php esc_html_e( 'En 1978, des géologues découvrent dans la taïga une famille de vieux-croyants, les Lykov, coupée du monde depuis plus de quarante ans. Dominique Samson raconte leur jardin.', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-billet-meta"} -->
						<p class="er-une-billet-meta"><?php esc_html_e( 'Dominique Samson · 13 avril 2023 · 28 min', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-billet","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-billet">
					<!-- wp:html -->
					<span class="er-lettrine er-aplat-pervenche" aria-hidden="true" lang="ru">Р</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-billet-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-billet-texte">
						<!-- wp:paragraph {"className":"er-une-billet-serie"} -->
						<p class="er-une-billet-serie"><?php esc_html_e( 'Le journal, en russe', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:heading {"level":3,"className":"er-une-billet-titre"} -->
						<h3 class="wp-block-heading er-une-billet-titre"><a href="<?php echo er_lien( 'journal/' ); ?>"><span lang="ru">Наталия Роскина, «&nbsp;Четыре главы&nbsp;»</span></a></h3>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"className":"er-une-billet-chapo"} -->
						<p class="er-une-billet-chapo"><?php echo wp_kses_post( __( 'Irina Roskina revient sur <em lang="ru">Четыре главы</em>, le livre de souvenirs littéraires de Natalia Roskina, paru chez YMCA-Press en 1980.', 'editeurs-reunis' ) ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-billet-meta"} -->
						<p class="er-une-billet-meta"><span lang="ru">Ирина Роскина</span> · <?php esc_html_e( '28 septembre · 6 min', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-billet","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-billet">
					<!-- wp:html -->
					<span class="er-lettrine er-aplat-vert" aria-hidden="true" lang="ru">Щ</span>
					<!-- /wp:html -->

					<!-- wp:group {"className":"er-une-billet-texte","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-billet-texte">
						<!-- wp:paragraph {"className":"er-une-billet-serie"} -->
						<p class="er-une-billet-serie"><?php esc_html_e( 'Les billets de Georges Nivat', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:heading {"level":3,"className":"er-une-billet-titre"} -->
						<h3 class="wp-block-heading er-une-billet-titre"><a href="<?php echo er_lien( 'journal/' ); ?>"><?php esc_html_e( 'Quand un chardonneret s’échappe dans le métro', 'editeurs-reunis' ); ?></a></h3>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"className":"er-une-billet-chapo"} -->
						<p class="er-une-billet-chapo"><?php esc_html_e( '[Deux lignes&nbsp;: de quoi parle le billet.]', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-billet-meta"} -->
						<p class="er-une-billet-meta"><?php esc_html_e( 'Georges Nivat · 28 décembre 2024 · 3 min', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"er-une-contributeurs"} -->
			<p class="er-une-contributeurs"><?php echo wp_kses_post( __( '<strong>Ils écrivent dans le journal&nbsp;:</strong> Georges Nivat, Yves Hamant, Anne Hogenhuis, Françoise Lesourd, Cécile Vaissié, Anita Davidenkoff, Anne Misslin, Nikolaï Tikhonchuk, Véronique Jobert, François de Saint-Chéron, Marguerite Léna, Nikita Struve, Dominique Samson et Margarita Sokolova.', 'editeurs-reunis' ) ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"er-une-lien"} -->
			<p class="er-une-lien"><a href="<?php echo er_lien( 'journal/' ); ?>"><?php esc_html_e( 'Tout le journal', 'editeurs-reunis' ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
