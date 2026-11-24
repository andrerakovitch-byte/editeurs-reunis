<?php
/**
 * Title: À la une — écran par écran
 * Slug: editeurs-reunis/une-ecrans
 * Categories: editeurs-reunis
 * Description: Le premier module de l'accueil : les sujets de la une, un par écran, sur l'aplat de leur maison ; la photo ou le dessin à droite, d'un bord à l'autre ; le sommaire des sujets au pied de chaque écran.
 *
 * Retenu le 24 novembre 2026 (« garde le module d'accueil de B pour le premier module de l'accueil », canevas « Les
 * Éditeurs réunis — plein écran »). Les trois affiches de l'accueil (L'Archipel du Goulag, Le Docteur Jivago,
 * Tchevengour) y passent chacune sur un écran ; le détail (les faits, « Pour aller plus loin ») est sur la fiche et
 * dans l'encyclopédie. Les prix et « Ajouter au panier » sont lus dans la librairie (er_prix(), er_ajout_panier()).
 *
 * Chaque écran tient la hauteur de la fenêtre sous la ligne de la maison (le premier, sous tout l'en-tête) ; au
 * téléphone, l'image passe au-dessus et l'écran prend la hauteur de son contenu.
 *
 * @package editeurs-reunis
 */

$er_ecrans = array(
	array(
		'id'       => 'une-archipel',
		'aplat'    => 'er-aplat-ymca',
		'glyphe'   => 'Архипелаг',
		'points'   => 'er-points-tous',
		'rubrique' => __( 'À la une · YMCA-Press, la librairie, le centre', 'editeurs-reunis' ),
		'titre'    => __( 'L’Archipel du Goulag', 'editeurs-reunis' ),
		'lien'     => er_livre( 'larchipel-du-goulag-cinquante-ans-apres' ),
		'phrase'   => __( 'Le livre de Soljenitsyne sur les camps soviétiques paraît d’abord en russe, à Paris, chez YMCA-Press, avant de faire le tour du monde.', 'editeurs-reunis' ),
		'boutons'  => array(
			array( er_prix( 'larchipel-du-goulag-cinquante-ans-apres' ) === __( 'épuisé pour l’instant', 'editeurs-reunis' ) ? __( 'Me prévenir de son retour', 'editeurs-reunis' ) : __( 'Ajouter au panier', 'editeurs-reunis' ), er_ajout_panier( 'larchipel-du-goulag-cinquante-ans-apres' ) ),
			array( __( 'Écouter le podcast', 'editeurs-reunis' ), er_lien( 'centre-culturel/podcasts/' ) ),
		),
		'image'    => er_image( 'une-imprimerie.jpg' ),
		'dessin'   => false,
		'alt'      => __( 'Soljenitsyne et Nikita Struve à l’imprimerie', 'editeurs-reunis' ),
		'legende'  => __( 'Soljenitsyne, Nikita Struve, Léonide Lifar et Serge Béresniak. Photo Hélène Lifar.', 'editeurs-reunis' ),
	),
	array(
		'id'       => 'une-jivago',
		'aplat'    => 'er-aplat-vert',
		'glyphe'   => 'Живаго',
		'points'   => 'er-points-librairie',
		'rubrique' => __( 'Retraduire · la librairie', 'editeurs-reunis' ),
		'titre'    => __( 'Le Docteur Jivago', 'editeurs-reunis' ),
		'lien'     => er_livre( 'le-docteur-jivago' ),
		'phrase'   => __( 'Le roman de Pasternak a paru d’abord en italien, à Milan, en 1957, avant toute édition en russe ; une nouvelle traduction le fait relire.', 'editeurs-reunis' ),
		'prix'     => er_prix( 'le-docteur-jivago' ),
		'boutons'  => array(
			array( __( 'Ajouter au panier', 'editeurs-reunis' ), er_ajout_panier( 'le-docteur-jivago' ) ),
			array( __( 'Feuilleter', 'editeurs-reunis' ), er_livre( 'le-docteur-jivago' ) ),
		),
		'image'    => er_image( 'une-bougie.svg' ),
		'dessin'   => true,
		'alt'      => __( 'Une bougie derrière une vitre givrée', 'editeurs-reunis' ),
		'legende'  => __( 'Une bougie derrière une vitre givrée : l’image que le roman a rendue célèbre.', 'editeurs-reunis' ),
	),
	array(
		'id'       => 'une-tchevengour',
		'aplat'    => 'er-aplat-noir',
		'glyphe'   => 'Чевенгур',
		'points'   => 'er-points-librairie',
		'rubrique' => __( 'Un livre, une vie · la librairie', 'editeurs-reunis' ),
		'titre'    => __( 'Tchevengour', 'editeurs-reunis' ),
		'lien'     => er_livre( 'tchevengour' ),
		'phrase'   => __( 'Le roman d’une utopie communiste dans un bourg de la steppe, que son auteur n’a jamais vu paraître en entier.', 'editeurs-reunis' ),
		'prix'     => er_prix( 'tchevengour' ),
		'boutons'  => array(
			array( __( 'Ajouter au panier', 'editeurs-reunis' ), er_ajout_panier( 'tchevengour' ) ),
			array( __( 'Platonov, dans l’encyclopédie', 'editeurs-reunis' ), er_lien( 'librairie/encyclopedie/platonov/' ) ),
		),
		'image'    => er_image( 'une-steppe.svg' ),
		'dessin'   => true,
		'alt'      => __( 'Le soleil sur la steppe, un petit bourg', 'editeurs-reunis' ),
		'legende'  => __( 'Le bourg de Tchevengour : on a cessé de travailler, le soleil travaille pour tous.', 'editeurs-reunis' ),
	),
);

// Le sommaire, au pied de chaque écran : les trois sujets, celui de l'écran marqué.
$er_sommaire = function ( $ici ) use ( $er_ecrans ) {
	$html = '<nav class="er-ecran-sommaire" aria-label="' . esc_attr__( 'Les sujets de la une', 'editeurs-reunis' ) . '">';
	foreach ( $er_ecrans as $rang => $e ) {
		$html .= '<a href="#' . esc_attr( $e['id'] ) . '"' . ( $e['id'] === $ici ? ' aria-current="true"' : '' ) . '><span>' . ( $rang + 1 ) . '</span> ' . esc_html( $e['titre'] ) . '</a>';
	}
	return $html . '<span class="er-ecran-defiler" aria-hidden="true">' . esc_html__( 'Faire défiler', 'editeurs-reunis' ) . ' <svg viewBox="0 0 24 24" width="16" height="16" focusable="false"><path d="M12 5v14M6 13l6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span></nav>';
};
?>
<!-- wp:group {"align":"full","className":"er-ecrans","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-ecrans">
<?php foreach ( $er_ecrans as $er_e ) : ?>
	<!-- wp:group {"tagName":"section","align":"full","anchor":"<?php echo esc_attr( $er_e['id'] ); ?>","className":"er-ecran <?php echo esc_attr( $er_e['aplat'] ); ?>","layout":{"type":"default"}} -->
	<section id="<?php echo esc_attr( $er_e['id'] ); ?>" class="wp-block-group alignfull er-ecran <?php echo esc_attr( $er_e['aplat'] ); ?>">
		<!-- wp:html -->
		<p class="er-ecran-glyphe" aria-hidden="true" lang="ru"><?php echo esc_html( $er_e['glyphe'] ); ?></p>
		<!-- /wp:html -->

		<!-- wp:group {"className":"er-ecran-texte","layout":{"type":"default"}} -->
		<div class="wp-block-group er-ecran-texte">
			<!-- wp:paragraph {"className":"er-ecran-rubrique <?php echo esc_attr( $er_e['points'] ); ?>"} -->
			<p class="er-ecran-rubrique <?php echo esc_attr( $er_e['points'] ); ?>"><?php echo esc_html( $er_e['rubrique'] ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"er-ecran-titre"} -->
			<h2 class="wp-block-heading er-ecran-titre"><a href="<?php echo $er_e['lien']; // phpcs:ignore WordPress.Security.EscapeOutput -- déjà échappé (er_livre). ?>"><?php echo esc_html( $er_e['titre'] ); ?></a></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"er-ecran-phrase"} -->
			<p class="er-ecran-phrase"><?php echo esc_html( $er_e['phrase'] ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"className":"er-ecran-boutons"} -->
			<div class="wp-block-buttons er-ecran-boutons">
				<!-- wp:button {"className":"is-style-pilule-blanche"} -->
				<div class="wp-block-button is-style-pilule-blanche"><a class="wp-block-button__link wp-element-button" href="<?php echo $er_e['boutons'][0][1]; // phpcs:ignore WordPress.Security.EscapeOutput -- déjà échappé. ?>"><?php echo esc_html( $er_e['boutons'][0][0] ); ?></a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo $er_e['boutons'][1][1]; // phpcs:ignore WordPress.Security.EscapeOutput -- déjà échappé. ?>"><?php echo esc_html( $er_e['boutons'][1][0] ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
			<?php if ( ! empty( $er_e['prix'] ) ) : ?>

			<!-- wp:paragraph {"className":"er-ecran-prix"} -->
			<p class="er-ecran-prix"><?php echo esc_html( $er_e['prix'] ); ?></p>
			<!-- /wp:paragraph -->
			<?php endif; ?>
		</div>
		<!-- /wp:group -->

		<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"er-ecran-image<?php echo $er_e['dessin'] ? ' est-dessin' : ''; ?>"} -->
		<figure class="wp-block-image size-full er-ecran-image<?php echo $er_e['dessin'] ? ' est-dessin' : ''; ?>"><img src="<?php echo $er_e['image']; // phpcs:ignore WordPress.Security.EscapeOutput -- déjà échappé (er_image). ?>" alt="<?php echo esc_attr( $er_e['alt'] ); ?>"/><figcaption class="wp-element-caption"><?php echo esc_html( $er_e['legende'] ); ?></figcaption></figure>
		<!-- /wp:image -->

		<!-- wp:html -->
		<?php echo $er_sommaire( $er_e['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé pièce à pièce. ?>
		<!-- /wp:html -->
	</section>
	<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group -->
