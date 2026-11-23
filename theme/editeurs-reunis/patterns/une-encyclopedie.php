<?php
/**
 * Title: À la une — l’encyclopédie du jour
 * Slug: editeurs-reunis/une-encyclopedie
 * Categories: editeurs-reunis
 * Description: L’article du jour, avec sa fiche, son portrait et les livres qu’il cite ; à côté, « Le saviez-vous ? » et les dates du mois.
 *
 * Écrite d'après la maquette « L'accueil, à la une » du canevas (proposition/blocs_une.py).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"er-une er-une-encyclo","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull er-une er-une-encyclo">
	<!-- wp:columns {"align":"wide","className":"er-une-colonnes"} -->
	<div class="wp-block-columns alignwide er-une-colonnes">
		<!-- wp:column {"width":"66%","className":"er-une-encyclo-gauche"} -->
		<div class="wp-block-column er-une-encyclo-gauche" style="flex-basis:66%">
			<!-- wp:columns {"className":"er-une-encyclo-article"} -->
			<div class="wp-block-columns er-une-encyclo-article">
				<!-- wp:column {"className":"er-une-encyclo-texte"} -->
				<div class="wp-block-column er-une-encyclo-texte">
					<!-- wp:paragraph {"className":"er-une-surtitre"} -->
					<p class="er-une-surtitre"><?php esc_html_e( 'L’article du jour', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:heading {"className":"er-une-encyclo-titre"} -->
					<h2 class="wp-block-heading er-une-encyclo-titre"><a href="<?php echo er_lien( 'librairie/encyclopedie/age-d-argent/' ); ?>"><?php esc_html_e( 'L’Âge d’argent', 'editeurs-reunis' ); ?></a></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"className":"er-une-texte"} -->
					<p class="er-une-texte"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( 'On appelle <strong>Âge d’argent</strong> (<em lang="ru">Серебряный век</em>) la période de la poésie russe qui va des années 1890 à 1921, par opposition à l’«&nbsp;<em>Âge d’or</em>&nbsp;» de Pouchkine<sup>1</sup>. Trois courants s’y succèdent et s’y opposent&nbsp;: le <a href="%1$s">symbolisme</a> d’Alexandre Blok et d’Andreï Biély, l’<a href="%1$s">acméisme</a> de Nikolaï Goumilev, d’Anna Akhmatova et d’Ossip Mandelstam, qui veulent des mots clairs et des choses précises, et le <a href="%1$s">futurisme</a> de Khlebnikov et de Maïakovski.', 'editeurs-reunis' ) ), er_lien( 'librairie/encyclopedie/age-d-argent/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-texte"} -->
					<p class="er-une-texte"><?php printf( wp_kses_post( /* translators: %1$s, %2$s : l’adresse d’une page du site. */ __( 'La révolution en fait éclater la communauté. Blok salue Octobre dans <a href="%1$s"><em>Les Douze</em></a>, en janvier 1918&nbsp;; en août 1921, il meurt à Petrograd, et Goumilev est fusillé, accusé de complot<sup>2</sup>. Beaucoup de ceux qui restent sont réduits au silence&nbsp;; d’autres partent, et l’Âge d’argent se prolonge dans l’<a href="%2$s">émigration</a>, à Berlin, à Prague et à Paris.', 'editeurs-reunis' ) ), er_livre( 'les-douze' ), er_lien( 'centre-culturel/paris-emigration/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-photo"} -->
					<figure class="wp-block-image size-full er-une-photo"><img src="<?php echo er_image( 'une-akhmatova.jpg' ); ?>" alt="<?php esc_attr_e( 'Portrait d’Anna Akhmatova par Natan Altman', 'editeurs-reunis' ); ?>"/><figcaption class="wp-element-caption"><?php esc_html_e( 'Anna Akhmatova par Natan Altman, 1914&nbsp;: l’Âge d’argent, en un portrait.', 'editeurs-reunis' ); ?></figcaption></figure>
					<!-- /wp:image -->

					<!-- wp:paragraph {"className":"er-une-meta"} -->
					<p class="er-une-meta"><?php printf( wp_kses_post( /* translators: %1$s, %2$s, %3$s : l’adresse d’une page du site. */ __( 'Article détaillé&nbsp;: <a href="%1$s">L’Âge d’argent</a> · Voir aussi&nbsp;: <a href="%2$s">Alexandre Soljenitsyne</a>, <a href="%3$s">Andreï Platonov</a>', 'editeurs-reunis' ) ), er_lien( 'librairie/encyclopedie/age-d-argent/' ), er_lien( 'librairie/encyclopedie/soljenitsyne/' ), er_lien( 'librairie/encyclopedie/platonov/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:list {"ordered":true,"className":"er-une-notes"} -->
					<ol class="wp-block-list er-une-notes"><!-- wp:list-item -->
					<li><?php esc_html_e( 'Le terme s’impose après coup&nbsp;; ses limites, surtout la date de départ, varient selon les historiens.', 'editeurs-reunis' ); ?></li>
					<!-- /wp:list-item -->
					<!-- wp:list-item -->
					<li><?php echo wp_kses_post( __( 'L’«&nbsp;<em>affaire Tagantsev</em>&nbsp;»&nbsp;: Goumilev est réhabilité en 1992.', 'editeurs-reunis' ) ); ?></li>
					<!-- /wp:list-item --></ol>
					<!-- /wp:list -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"300px","className":"er-une-encyclo-cote"} -->
				<div class="wp-block-column er-une-encyclo-cote" style="flex-basis:300px">
					<!-- wp:group {"className":"er-une-fiche","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fiche">
						<!-- wp:group {"className":"er-une-fiche-tete er-aplat-nuit","layout":{"type":"default"}} -->
						<div class="wp-block-group er-une-fiche-tete er-aplat-nuit">
							<!-- wp:html -->
							<p class="er-une-fiche-mot" aria-hidden="true" lang="ru">Серебро</p>
							<!-- /wp:html -->

							<!-- wp:paragraph {"className":"er-une-fiche-titre"} -->
							<p class="er-une-fiche-titre"><?php esc_html_e( 'Âge d’argent', 'editeurs-reunis' ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":"er-une-fiche-ru"} -->
							<p class="er-une-fiche-ru"><span lang="ru">Серебряный век</span></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:table {"className":"er-une-fiche-rangs"} -->
						<figure class="wp-block-table er-une-fiche-rangs"><table class="has-fixed-layout"><tbody><tr><td><?php esc_html_e( 'Période', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'vers 1890 – 1921', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( 'Lieux', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'Saint-Pétersbourg, Moscou', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( 'Courants', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'symbolisme, acméisme, futurisme', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( 'Figures', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'Blok, Biély, Akhmatova, Goumilev, Mandelstam, Tsvetaïeva, Maïakovski', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( 'Fin', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( '1921&nbsp;: mort de Blok, exécution de Goumilev', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( 'À la librairie', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( '[34] livres · [12] auteurs', 'editeurs-reunis' ); ?></td></tr></tbody></table></figure>
						<!-- /wp:table -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-livres","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-livres">
						<!-- wp:paragraph {"className":"er-une-surtitre"} -->
						<p class="er-une-surtitre"><?php esc_html_e( 'Les livres cités, en librairie', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-livre">
							<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
							<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'les-douze' ); ?>"><img src="<?php echo er_image( 'couv-douze.jpg' ); ?>" alt=""/></a></figure>
							<!-- /wp:image -->

							<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
							<div class="wp-block-group er-une-livre-texte">
								<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
								<p class="er-une-livre-titre"><a href="<?php echo er_livre( 'les-douze' ); ?>"><?php esc_html_e( 'Les Douze', 'editeurs-reunis' ); ?></a></p>
								<!-- /wp:paragraph -->

								<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
								<p class="er-une-livre-auteur"><?php esc_html_e( 'Alexandre Blok', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:group -->

						<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-livre">
							<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
							<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'poesies-choisies-goumilev' ); ?>"><img src="<?php echo er_image( 'couv-goumilev.jpg' ); ?>" alt=""/></a></figure>
							<!-- /wp:image -->

							<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
							<div class="wp-block-group er-une-livre-texte">
								<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
								<p class="er-une-livre-titre"><a href="<?php echo er_livre( 'poesies-choisies-goumilev' ); ?>"><?php esc_html_e( 'Poésies choisies', 'editeurs-reunis' ); ?></a></p>
								<!-- /wp:paragraph -->

								<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
								<p class="er-une-livre-auteur"><?php esc_html_e( 'Nikolaï Goumilev', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:group -->

						<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-livre">
							<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
							<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'ekaterina-ivanovna' ); ?>"><img src="<?php echo er_image( 'couv-ekaterina.jpg' ); ?>" alt=""/></a></figure>
							<!-- /wp:image -->

							<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
							<div class="wp-block-group er-une-livre-texte">
								<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
								<p class="er-une-livre-titre"><a href="<?php echo er_livre( 'ekaterina-ivanovna' ); ?>"><?php esc_html_e( 'Ekatérina Ivanovna, suivi de Requiem', 'editeurs-reunis' ); ?></a></p>
								<!-- /wp:paragraph -->

								<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
								<p class="er-une-livre-auteur"><?php esc_html_e( 'Léonid Andréïev', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:group -->

						<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-livre">
							<!-- wp:html -->
							<span class="er-lettrine er-lettrine-livre er-aplat-ymca" aria-hidden="true" lang="ru">А</span>
							<!-- /wp:html -->

							<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
							<div class="wp-block-group er-une-livre-texte">
								<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
								<p class="er-une-livre-titre"><?php esc_html_e( '[Un livre d’Anna Akhmatova]', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->

								<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
								<p class="er-une-livre-auteur"><?php esc_html_e( 'à choisir dans le catalogue', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:group -->

						<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-livre">
							<!-- wp:html -->
							<span class="er-lettrine er-lettrine-livre er-aplat-nuit" aria-hidden="true" lang="ru">М</span>
							<!-- /wp:html -->

							<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
							<div class="wp-block-group er-une-livre-texte">
								<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
								<p class="er-une-livre-titre"><?php esc_html_e( '[Un livre d’Ossip Mandelstam]', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->

								<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
								<p class="er-une-livre-auteur"><?php esc_html_e( 'à choisir dans le catalogue', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:group -->

						<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
						<div class="wp-block-group er-une-livre">
							<!-- wp:html -->
							<span class="er-lettrine er-lettrine-livre er-aplat-vert" aria-hidden="true" lang="ru">Б</span>
							<!-- /wp:html -->

							<!-- wp:group {"className":"er-une-livre-texte","layout":{"type":"default"}} -->
							<div class="wp-block-group er-une-livre-texte">
								<!-- wp:paragraph {"className":"er-une-livre-titre"} -->
								<p class="er-une-livre-titre"><?php esc_html_e( '[Un livre d’Andreï Biély]', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->

								<!-- wp:paragraph {"className":"er-une-livre-auteur"} -->
								<p class="er-une-livre-auteur"><?php esc_html_e( 'à choisir dans le catalogue', 'editeurs-reunis' ); ?></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"34%","className":"er-une-encyclo-droite"} -->
		<div class="wp-block-column er-une-encyclo-droite" style="flex-basis:34%">
			<!-- wp:heading {"level":3,"className":"er-une-petit-titre"} -->
			<h3 class="wp-block-heading er-une-petit-titre"><?php esc_html_e( 'Le saviez-vous&nbsp;?', 'editeurs-reunis' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"er-une-saviez","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-saviez">
				<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'le-docteur-jivago' ); ?>"><img src="<?php echo er_image( 'couv-jivago.jpg' ); ?>" alt=""/></a></figure>
					<!-- /wp:image -->

					<!-- wp:paragraph {"className":"er-une-saviez-texte"} -->
					<p class="er-une-saviez-texte"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '… que <a href="%1$s"><em>Le Docteur Jivago</em></a> de Pasternak a paru pour la première fois en italien, à Milan, en 1957, avant toute édition en russe&nbsp;?', 'editeurs-reunis' ) ), er_livre( 'le-docteur-jivago' ) ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'tchevengour' ); ?>"><img src="<?php echo er_image( 'couv-tchevengour.jpg' ); ?>" alt=""/></a></figure>
					<!-- /wp:image -->

					<!-- wp:paragraph {"className":"er-une-saviez-texte"} -->
					<p class="er-une-saviez-texte"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '… que <a href="%1$s"><em>Tchevengour</em></a>, écrit par Platonov à la fin des années 1920, n’a paru en entier en URSS qu’en 1988&nbsp;?', 'editeurs-reunis' ) ), er_livre( 'tchevengour' ) ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'larchipel-du-goulag-cinquante-ans-apres' ); ?>"><img src="<?php echo er_image( 'couv-archipel.jpg' ); ?>" alt=""/></a></figure>
					<!-- /wp:image -->

					<!-- wp:paragraph {"className":"er-une-saviez-texte"} -->
					<p class="er-une-saviez-texte"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '… que <a href="%1$s"><em>L’Archipel du Goulag</em></a> a paru d’abord en russe, à Paris, chez YMCA-Press, le 28 décembre 1973&nbsp;?', 'editeurs-reunis' ) ), er_livre( 'larchipel-du-goulag-cinquante-ans-apres' ) ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-livre","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group er-une-livre">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-une-couverture"} -->
					<figure class="wp-block-image size-full er-une-couverture"><a href="<?php echo er_livre( 'notes-dun-medecin' ); ?>"><img src="<?php echo er_image( 'chef-doeuvre-veressaiev.jpg' ); ?>" alt=""/></a></figure>
					<!-- /wp:image -->

					<!-- wp:paragraph {"className":"er-une-saviez-texte"} -->
					<p class="er-une-saviez-texte"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '… que les <a href="%1$s"><em>Notes d’un médecin</em></a> de Veressaïev, sur les débuts d’un jeune médecin, firent scandale à leur parution, en 1901&nbsp;?', 'editeurs-reunis' ) ), er_livre( 'notes-dun-medecin' ) ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:heading {"level":3,"className":"er-une-petit-titre"} -->
			<h3 class="wp-block-heading er-une-petit-titre"><?php esc_html_e( 'En novembre', 'editeurs-reunis' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:table {"className":"er-une-mois"} -->
			<figure class="wp-block-table er-une-mois"><table class="has-fixed-layout"><tbody><tr><td><?php esc_html_e( '7 novembre 1917', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'La révolution d’Octobre (25 octobre dans le calendrier julien).', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( '9 novembre 1818', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'Naissance d’Ivan Tourguéniev, à Orel.', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( '9 novembre 1933', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'Ivan Bounine reçoit le prix Nobel de littérature, le premier écrivain russe à l’obtenir.', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( '11 novembre 1821', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'Naissance de Fiodor Dostoïevski, à Moscou.', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( '20 novembre 1910', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'Mort de Léon Tolstoï, à la gare d’Astapovo.', 'editeurs-reunis' ); ?></td></tr><tr><td><?php esc_html_e( '28 novembre 1880', 'editeurs-reunis' ); ?></td><td><?php esc_html_e( 'Naissance d’Alexandre Blok, à Saint-Pétersbourg.', 'editeurs-reunis' ); ?></td></tr></tbody></table></figure>
			<!-- /wp:table -->

			<!-- wp:paragraph {"className":"er-une-meta"} -->
			<p class="er-une-meta"><?php esc_html_e( 'Dates du calendrier grégorien.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
