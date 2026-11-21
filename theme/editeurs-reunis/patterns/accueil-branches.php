<?php
/**
 * Title: Accueil — une entité, trois branches
 * Slug: editeurs-reunis/accueil-branches
 * Categories: editeurs-reunis
 * Description: Sur fond blanc : le titre dans la colonne pervenche ; à côté, la librairie, la maison d'édition et le centre culturel, chacun son image, deux lignes et ses portes (les liens de l'ancienne « navigation rapide »).
 *
 * @package editeurs-reunis
 */

$branches = array(
	array(
		'titre' => __( 'Librairie des Éditeurs réunis', 'editeurs-reunis' ),
		'texte' => __( 'Au cœur du quartier latin depuis un demi-siècle, des livres neufs et d’occasion, en russe et en français.', 'editeurs-reunis' ),
		'image' => 'branche-librairie.jpg',
		'alt'   => __( 'Portrait de Pouchkine par Vassili Tropinine', 'editeurs-reunis' ),
		'lien'  => er_lien( 'librairie/' ),
		'liens' => array(
			__( 'La librairie en ligne', 'editeurs-reunis' ) => er_lien( 'librairie/' ),
			__( 'Les livres en russe', 'editeurs-reunis' )   => er_lien( 'langue/russe/' ),
			__( 'Nous trouver', 'editeurs-reunis' )          => er_lien( 'nous-trouver/' ),
		),
	),
	array(
		'titre' => __( 'Maison d’édition YMCA-Press', 'editeurs-reunis' ),
		'texte' => __( 'Un siècle d’édition russe en exil, de Bounine à Soljenitsyne : l’histoire et le catalogue de la maison.', 'editeurs-reunis' ),
		'image' => 'branche-ymca-press.jpg',
		'alt'   => __( 'Photographie en noir et blanc : un groupe d’hommes en manteau dans une rue', 'editeurs-reunis' ),
		'lien'  => er_lien( 'ymca-press/' ),
		'liens' => array(
			__( 'Les publications', 'editeurs-reunis' )      => er_lien( 'ymca-press/catalogue/' ),
			__( 'Un siècle d’histoire', 'editeurs-reunis' )  => er_lien( 'ymca-press/histoire/' ),
			__( 'Les Amis d’YMCA-Press', 'editeurs-reunis' ) => er_lien( 'ymca-press/amis-ymca-press/' ),
		),
	),
	array(
		'titre' => __( 'Centre culturel Soljenitsyne', 'editeurs-reunis' ),
		'texte' => __( 'Rencontres, expositions, podcasts et vidéos autour de la littérature et de l’histoire russes.', 'editeurs-reunis' ),
		'image' => 'branche-centre-culturel.jpg',
		'alt'   => __( 'Portrait d’Anna Akhmatova par Nathan Altman', 'editeurs-reunis' ),
		'lien'  => er_lien( 'centre-culturel/' ),
		'liens' => array(
			__( 'Nos événements', 'editeurs-reunis' )       => er_lien( 'centre-culturel/evenements/' ),
			__( 'Nos expositions', 'editeurs-reunis' )      => er_lien( 'centre-culturel/expositions/' ),
			__( 'Nos podcasts', 'editeurs-reunis' )         => er_lien( 'centre-culturel/podcasts/' ),
			__( 'Nos vidéos', 'editeurs-reunis' )           => er_lien( 'centre-culturel/videos/' ),
			__( 'Le journal du centre', 'editeurs-reunis' ) => er_lien( 'journal/' ),
		),
	),
);
?>
<!-- wp:group {"align":"full","className":"er-panneau er-panneau-clair er-branches","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull er-panneau er-panneau-clair er-branches">
	<!-- wp:columns {"align":"wide","className":"er-panneau-colonnes","style":{"spacing":{"blockGap":{"left":"0"}}}} -->
	<div class="wp-block-columns alignwide er-panneau-colonnes">
		<!-- wp:column {"width":"22%","className":"er-colonne"} -->
		<div class="wp-block-column er-colonne" style="flex-basis:22%">
			<!-- wp:paragraph {"className":"er-surtitre"} -->
			<p class="er-surtitre"><?php esc_html_e( 'Qui nous sommes', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-panneau-titre"} -->
			<h2 class="wp-block-heading er-panneau-titre"><?php esc_html_e( 'Une entité, trois branches', 'editeurs-reunis' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-colonne-texte"} -->
			<p class="er-colonne-texte"><?php esc_html_e( 'La librairie, la maison d’édition YMCA-Press et le Centre culturel Alexandre Soljenitsyne.', 'editeurs-reunis' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"78%","className":"er-champ"} -->
		<div class="wp-block-column er-champ" style="flex-basis:78%">
			<!-- wp:columns {"className":"er-trois","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-columns er-trois">
				<?php foreach ( $branches as $branche ) : ?>
				<!-- wp:column {"className":"er-branche"} -->
				<div class="wp-block-column er-branche">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-branche-image"} -->
					<figure class="wp-block-image size-full er-branche-image"><a href="<?php echo $branche['lien']; // phpcs:ignore ?>" tabindex="-1"><img src="<?php echo er_image( $branche['image'] ); ?>" alt="<?php echo esc_attr( $branche['alt'] ); ?>"/></a></figure>
					<!-- /wp:image -->

					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="<?php echo $branche['lien']; // phpcs:ignore ?>"><?php echo esc_html( $branche['titre'] ); ?></a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html( $branche['texte'] ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:list {"className":"er-portes"} -->
					<ul class="wp-block-list er-portes">
						<?php foreach ( $branche['liens'] as $titre => $lien ) : ?>
						<!-- wp:list-item --><li><a href="<?php echo $lien; // phpcs:ignore ?>"><?php echo esc_html( $titre ); ?></a></li><!-- /wp:list-item -->
						<?php endforeach; ?>
					</ul>
					<!-- /wp:list -->
				</div>
				<!-- /wp:column -->
				<?php endforeach; ?>
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
