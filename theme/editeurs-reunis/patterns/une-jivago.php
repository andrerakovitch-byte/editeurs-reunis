<?php
/**
 * Title: À la une — une affiche (Le Docteur Jivago)
 * Slug: editeurs-reunis/une-jivago
 * Categories: editeurs-reunis
 * Description: Une affiche sur le vert de la librairie : un livre, ses faits, ses boutons, pour aller plus loin, et le texte avec son dessin.
 *
 * Écrite d'après la maquette « L'accueil, à la une » du canevas (proposition/blocs_une.py).
 *
 * @package editeurs-reunis
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"er-une er-une-affiche er-aplat-vert","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull er-une er-une-affiche er-aplat-vert">
	<!-- wp:html -->
	<p class="er-une-glyphe" aria-hidden="true" lang="ru">Живаго</p>
	<!-- /wp:html -->

	<!-- wp:columns {"align":"wide","className":"er-une-colonnes"} -->
	<div class="wp-block-columns alignwide er-une-colonnes">
		<!-- wp:column {"width":"45%","className":"er-une-affiche-gauche"} -->
		<div class="wp-block-column er-une-affiche-gauche" style="flex-basis:45%">
			<!-- wp:paragraph {"className":"er-une-rubrique er-points-librairie er-une-rubrique-claire"} -->
			<p class="er-une-rubrique er-points-librairie er-une-rubrique-claire"><?php esc_html_e( 'Retraduire · la librairie', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-une-affiche-titre"} -->
			<h2 class="wp-block-heading er-une-affiche-titre"><?php esc_html_e( 'Le Docteur Jivago', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"er-une-faits","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-faits">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-couverture"} -->
				<figure class="wp-block-image size-full er-une-couverture"><img src="<?php echo er_image( 'couv-jivago.jpg' ); ?>" alt="<?php esc_attr_e( 'Couverture du Docteur Jivago', 'editeurs-reunis' ); ?>"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"er-une-faits-grille","layout":{"type":"grid","columnCount":2}} -->
				<div class="wp-block-group er-une-faits-grille">
					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Première édition', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( 'Milan, 1957, en italien', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Éditeur', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( '[éditeur de la nouvelle traduction]', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Auteur', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php esc_html_e( 'Boris Pasternak', 'editeurs-reunis' ); ?></p>
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
						<p class="er-une-fait-valeur"><?php esc_html_e( 'nouvelle traduction', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"er-une-fait er-une-fait-petit","layout":{"type":"default"}} -->
					<div class="wp-block-group er-une-fait er-une-fait-petit">
						<!-- wp:paragraph {"className":"er-une-fait-cle"} -->
						<p class="er-une-fait-cle"><?php esc_html_e( 'Prix', 'editeurs-reunis' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"er-une-fait-valeur"} -->
						<p class="er-une-fait-valeur"><?php echo esc_html( er_prix( 'le-docteur-jivago' ) ); ?></p>
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
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo er_ajout_panier( 'le-docteur-jivago' ); ?>"><?php esc_html_e( 'Ajouter au panier', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_livre( 'le-docteur-jivago' ); ?>"><?php esc_html_e( 'Feuilleter', 'editeurs-reunis' ); ?></a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo er_lien( 'librairie/encyclopedie/' ); ?>"><?php esc_html_e( 'Pasternak, dans l’encyclopédie', 'editeurs-reunis' ); ?></a></div>
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
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>En librairie</strong> <a href="%1$s">Platonov, <em>Tchevengour</em></a>', 'editeurs-reunis' ) ), er_livre( 'tchevengour' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( 'Lui aussi écrit sous Staline et refusé de son vivant&nbsp;: paru en entier en URSS en 1988, la même année que Jivago.', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-plus">
					<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>Journal du centre</strong> <a href="%1$s"><em>Le Docteur Jivago</em>, par Georges Nivat</a>', 'editeurs-reunis' ) ), er_lien( 'journal/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( '[Deux lignes&nbsp;: de quoi parle l’article.]', 'editeurs-reunis' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"er-une-plus","layout":{"type":"default"}} -->
				<div class="wp-block-group er-une-plus">
					<!-- wp:paragraph {"className":"er-une-plus-quoi"} -->
					<p class="er-une-plus-quoi"><?php printf( wp_kses_post( /* translators: %1$s : l’adresse d’une page du site. */ __( '<strong>Le fonds d’archives</strong> <a href="%1$s">Interdits là-bas, publiés ailleurs</a>', 'editeurs-reunis' ) ), er_lien( 'ymca-press/catalogue/' ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"er-une-plus-pourquoi"} -->
					<p class="er-une-plus-pourquoi"><?php esc_html_e( 'Les livres qui ont paru d’abord à l’étranger, ou des décennies après avoir été écrits.', 'editeurs-reunis' ); ?></p>
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
			<p class="er-une-chapo"><?php esc_html_e( 'Le roman de Pasternak a paru d’abord en italien, à Milan, en 1957, avant toute édition en russe&nbsp;; une nouvelle traduction le fait relire.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-une-dessin"} -->
			<figure class="wp-block-image size-full er-une-dessin"><img src="<?php echo er_image( 'une-bougie.svg' ); ?>" alt="<?php esc_attr_e( 'Une bougie derrière une vitre givrée', 'editeurs-reunis' ); ?>"/><figcaption class="wp-element-caption"><?php esc_html_e( 'Une bougie derrière une vitre givrée&nbsp;: l’image que le roman a rendue célèbre.', 'editeurs-reunis' ); ?></figcaption></figure>
			<!-- /wp:image -->

			<!-- wp:group {"className":"er-une-deux-colonnes","layout":{"type":"default"}} -->
			<div class="wp-block-group er-une-deux-colonnes">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Pasternak l’écrit pendant dix ans, de la fin de la guerre au milieu des années 1950&nbsp;: la vie d’un médecin et poète, Iouri Jivago, dans la Russie de la révolution et de la guerre civile, et son amour pour Lara.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Refusé par les revues soviétiques, le manuscrit passe à l’Ouest&nbsp;; l’éditeur Giangiacomo Feltrinelli le publie à Milan en novembre 1957. L’année suivante, Pasternak reçoit le prix Nobel de littérature&nbsp;; sous la pression du pouvoir, il le refuse.', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php echo wp_kses_post( __( 'En URSS, le roman ne paraît qu’en 1988, dans la revue <em>Novy Mir</em>, trente ans après le Nobel.', 'editeurs-reunis' ) ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( '[Une phrase du libraire&nbsp;: ce que la nouvelle traduction change à la lecture.]', 'editeurs-reunis' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
