<?php
/**
 * Title: À la une — une affiche (L’Archipel du Goulag)
 * Slug: editeurs-reunis/une-archipel
 * Categories: editeurs-reunis
 * Description: Une affiche sur l’aplat d’une maison : à gauche ce qui situe (la rubrique, le titre, les faits, les boutons, pour aller plus loin), à droite le texte et sa photo.
 *
 * Écrite d'après la maquette « L'accueil, à la une » du canevas (proposition/blocs_une.py).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"er-une er-une-affiche er-aplat-ymca","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull er-une er-une-affiche er-aplat-ymca">
	<!-- wp:html -->
	<p class="er-une-glyphe" aria-hidden="true" lang="ru">Архипелаг</p>
	<!-- /wp:html -->

	<!-- wp:columns {"align":"wide","className":"er-une-colonnes"} -->
	<div class="wp-block-columns alignwide er-une-colonnes">
		<!-- wp:column {"width":"45%","className":"er-une-affiche-gauche"} -->
		<div class="wp-block-column er-une-affiche-gauche" style="flex-basis:45%">
			<!-- wp:paragraph {"className":"er-une-rubrique er-points-tous er-une-rubrique-claire"} -->
			<p class="er-une-rubrique er-points-tous er-une-rubrique-claire"><?php esc_html_e( 'À la une · YMCA-Press, la librairie, le centre', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-une-affiche-titre"} -->
			<h2 class="wp-block-heading er-une-affiche-titre"><?php esc_html_e( 'L’Archipel du Goulag', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"er-une-faits","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-faits">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-couverture"} -->
				<figure class="wp-block-image size-full er-une-couverture"><img src="<?php echo er_image( 'couv-archipel.jpg' ); ?>" alt="<?php esc_attr_e( 'Couverture de L’Archipel du Goulag, cinquante ans après', 'editeurs-reunis' ); ?>"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"er-une-faits-grille","layout":{"type":"grid","columnCount":2}} -->
				<div class="wp-block-group er-une-faits-grille">
					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Paru', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( '28 décembre 1973, en russe', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Éditeur', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( 'YMCA-Press, Paris', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Auteur', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( 'Alexandre Soljenitsyne', 'editeurs-reunis' ); ?></p>
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
						<p class="er-une-fait-valeur"><?php esc_html_e( 'le recueil de 2023', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait er-une-fait-petit","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait er-une-fait-petit">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Prix', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php echo esc_html( er_prix( 'larchipel-du-goulag-cinquante-ans-apres' ) ); ?></p>
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
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_livre( 'larchipel-du-goulag-cinquante-ans-apres' ); ?>"><?php esc_html_e( 'Me prévenir de son retour', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_lien( 'centre-culturel/podcasts/' ); ?>"><?php esc_html_e( 'Écouter le podcast', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_lien( 'ymca-press/histoire/' ); ?>"><?php esc_html_e( 'Les archives de 1973', 'editeurs-reunis' ); ?></a></div>
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
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>En librairie</strong> <a href="%1$s"><em>Voix et visages du Goulag</em></a>', 'editeurs-reunis' ) ), er_livre( 'voix-et-visages-du-goulag' ) ); ?> <span class="er-une-prix">· <?php echo esc_html( er_prix( 'voix-et-visages-du-goulag' ) ); ?></span></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( 'Un autre livre du rayon, pour entendre d’autres voix des camps que celle de Soljenitsyne.', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-plus">
					<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>L’encyclopédie</strong> <a href="%1$s">Le Goulag</a>', 'editeurs-reunis' ) ), er_lien( 'librairie/encyclopedie/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( 'Les camps et leurs témoins&nbsp;: Chalamov, Guinzbourg, Soljenitsyne, et pour chacun les livres qu’on trouve ici.', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-plus">
					<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>Podcast</strong> <a href="%1$s"><em>Les secrets de L’Archipel du Goulag</em></a>', 'editeurs-reunis' ) ), er_lien( 'centre-culturel/podcasts/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( 'L’histoire d’un manuscrit caché, recopié, sorti d’URSS, épisode par épisode.', 'editeurs-reunis' ); ?></p>
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
			<p class="er-une-chapo"><?php esc_html_e( 'Le livre de Soljenitsyne sur les camps soviétiques paraît d’abord en russe, à Paris, chez YMCA-Press, avant de faire le tour du monde.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-photo er-une-gris"} -->
			<figure class="wp-block-image size-full er-une-photo er-une-gris"><img src="<?php echo er_image( 'une-imprimerie.jpg' ); ?>" alt="<?php esc_attr_e( 'Soljenitsyne et Nikita Struve à l’imprimerie', 'editeurs-reunis' ); ?>"/><figcaption class="wp-element-caption"><?php esc_html_e( 'Soljenitsyne, Nikita Struve, Léonide Lifar et Serge Béresniak. Photo Hélène Lifar.', 'editeurs-reunis' ); ?></figcaption></figure>
			<!-- /wp:image -->

			<!-- wp:group {"className":"er-une-deux-colonnes","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-deux-colonnes">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Soljenitsyne l’écrit en secret, entre 1958 et 1968, à partir de ses années de camp et de plus de deux cents témoignages d’anciens détenus. Il le sous-titre «&nbsp;essai d’investigation littéraire&nbsp;»&nbsp;: ni roman ni livre d’histoire, mais les deux à la fois.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'En 1973, le KGB saisit l’un des exemplaires du manuscrit&nbsp;; l’auteur décide de le faire publier sans attendre. Le premier tome paraît à Paris le 28 décembre. En février 1974, Soljenitsyne est arrêté, déchu de sa citoyenneté et expulsé d’URSS.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php echo wp_kses_post( __( 'Cinquante ans après, le recueil dirigé par Georges Nivat, <em>L’Archipel du Goulag, cinquante ans après</em>, en reprend les pages essentielles, avec les lectures qu’on en a faites depuis. Au centre culturel, un podcast en plusieurs épisodes, <em>Les secrets de L’Archipel du Goulag</em>, raconte les mystères d’un livre hors du commun.', 'editeurs-reunis' ) ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
