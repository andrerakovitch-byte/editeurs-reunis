<?php
/**
 * Title: Accueil — le diaporama à la une
 * Slug: editeurs-reunis/accueil-une
 * Categories: editeurs-reunis
 * Description: Le grand bandeau d'ouverture, qui défile : chaque diapositive reprend la première de la maquette (la bande pervenche à gauche, une photo ou un aplat, le titre en blanc, un bouton cerné, une image à droite).
 *
 * Sans JavaScript, les diapositives se font glisser de côté ; diaporama.js ajoute les repères, les flèches et le
 * défilement tout seul (arrêté au survol, au toucher, et quand l'appareil demande moins d'animations).
 *
 * @package editeurs-reunis
 */

$diapos = array(
	array(
		'classe'  => 'er-diapo-mer',
		'fond'    => er_image( 'diapo-mer.jpg' ),
		'titre'   => 'Les secrets de l’Archipel du Goulag',
		'texte'   => 'Un podcast inédit pour comprendre les mystères d’un livre hors du commun.',
		'bouton'  => 'Écouter ici',
		'lien'    => er_lien( 'centre-culturel/podcasts/' ),
		'image'   => er_image( 'podcast-archipel.jpg' ),
		'alt'     => 'Pochette du podcast « Les secrets de L’Archipel du Goulag »',
		'forme'   => 'carree',
	),
	array(
		'classe'  => 'er-diapo-samarcande',
		'fond'    => '',
		'titre'   => 'Convoi pour Samarcande',
		'texte'   => 'Le nouveau roman de Gouzel Iakhina, l’auteure de <em>Zouleikha ouvre les yeux</em>, est arrivé à la librairie.',
		'bouton'  => 'Voir le livre',
		'lien'    => er_livre( 'convoi-pour-samarcande' ),
		'image'   => er_image( 'diapo-convoi.jpg' ),
		'alt'     => 'Couverture de Convoi pour Samarcande, de Gouzel Iakhina',
		'forme'   => 'livre',
	),
	array(
		'classe'  => 'er-diapo-anthologies',
		'fond'    => '',
		'titre'   => 'Anthologies de la poésie russe',
		'texte'   => 'Nos meilleures ventes YMCA-Press : quatre volumes bilingues russe-français, du XVIII<sup>e</sup>&nbsp;siècle à nos jours.',
		'bouton'  => 'Le rayon poésie',
		'lien'    => er_rayon( 'poesie' ),
		'image'   => er_image( 'anthologies-escalier.png' ),
		'alt'     => 'Les quatre volumes de l’Anthologie de la poésie russe',
		'forme'   => 'libre',
	),
);
?>
<!-- wp:group {"align":"full","className":"er-diaporama","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-diaporama">
	<!-- wp:group {"className":"er-diapos","layout":{"type":"default"}} -->
	<div class="wp-block-group er-diapos">
		<?php foreach ( $diapos as $rang => $diapo ) : ?>
			<?php
			$fond_bloc = $diapo['fond'] ? ',"style":{"background":{"backgroundImage":{"url":"' . $diapo['fond'] . '","source":"file"},"backgroundSize":"cover","backgroundPosition":"50% 50%"}}' : '';
			$fond_html = $diapo['fond'] ? ' style="background-image:url(\'' . $diapo['fond'] . '\');background-size:cover;background-position:50% 50%"' : '';
			$niveau    = 0 === $rang ? 1 : 2;
			?>
		<!-- wp:group {"className":"er-diapo <?php echo esc_attr( $diapo['classe'] ); ?>"<?php echo $fond_bloc; // phpcs:ignore ?>,"layout":{"type":"constrained"}} -->
		<div class="wp-block-group er-diapo <?php echo esc_attr( $diapo['classe'] ); ?>"<?php echo $fond_html; // phpcs:ignore ?>>
			<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"er-diapo-contenu"} -->
			<div class="wp-block-columns alignwide are-vertically-aligned-center er-diapo-contenu">
				<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
				<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
					<!-- wp:heading {"level":<?php echo (int) $niveau; ?>,"className":"er-diapo-titre"} -->
					<h<?php echo (int) $niveau; ?> class="wp-block-heading er-diapo-titre"><?php echo esc_html( $diapo['titre'] ); ?></h<?php echo (int) $niveau; ?>>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"className":"er-diapo-texte"} -->
					<p class="er-diapo-texte"><?php echo wp_kses( $diapo['texte'], array( 'sup' => array(), 'em' => array() ) ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:buttons -->
					<div class="wp-block-buttons">
						<!-- wp:button {"className":"is-style-fleche"} -->
						<div class="wp-block-button is-style-fleche"><a class="wp-block-button__link wp-element-button" href="<?php echo $diapo['lien']; // phpcs:ignore ?>"><?php echo esc_html( $diapo['bouton'] ); ?></a></div>
						<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
				<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"er-diapo-image er-diapo-image-<?php echo esc_attr( $diapo['forme'] ); ?>"} -->
					<figure class="wp-block-image size-full er-diapo-image er-diapo-image-<?php echo esc_attr( $diapo['forme'] ); ?>"><a href="<?php echo $diapo['lien']; // phpcs:ignore ?>" tabindex="-1"><img src="<?php echo $diapo['image']; // phpcs:ignore ?>" alt="<?php echo esc_attr( $diapo['alt'] ); ?>"/></a></figure>
					<!-- /wp:image -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
