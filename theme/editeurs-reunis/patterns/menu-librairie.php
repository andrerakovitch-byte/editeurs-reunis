<?php
/**
 * Title: Le menu de la librairie
 * Slug: editeurs-reunis/menu-librairie
 * Categories: editeurs-reunis
 * Inserter: no
 *
 * D'après la maquette Wix : la devise de la librairie, puis ses rayons (Accueil, les grands rayons avec leurs
 * sous-rayons, Nos meilleures ventes, Nouveautés, Promos). Un grand rayon ouvre un tiroir sur toute la largeur :
 * ses sous-rayons à gauche, deux tuiles à droite (un petit diaporama, et les anthologies).
 *
 * Les rayons viennent des catégories de produits de WooCommerce (Produits → Catégories) : un rayon ajouté, renommé
 * ou déplacé y apparaît de lui-même, dans l'ordre des catégories.
 *
 * @package editeurs-reunis
 */

/**
 * Les catégories de produits sous un parent, dans l'ordre de WooCommerce.
 *
 * @param int $parent Le parent.
 */
$er_rayons = function ( $parent ) {
	$termes = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent,
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $termes ) ) {
		return array();
	}
	$termes = array_filter(
		$termes,
		function ( $t ) {
			return ! in_array( $t->slug, array( 'uncategorized', 'sans-rayon' ), true );
		}
	);
	usort(
		$termes,
		function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true );
		}
	);
	return $termes;
};

// Où l'on est : le rayon ouvert (ou son parent), ou l'une des entrées fixes.
$actif = '';
if ( function_exists( 'is_shop' ) && is_shop() && empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	$actif = 'accueil';
} elseif ( isset( $_GET['orderby'] ) && 'popularity' === $_GET['orderby'] ) { // phpcs:ignore WordPress.Security.NonceVerification
	$actif = 'ventes';
} elseif ( is_tax( 'product_tag', 'nouveaute' ) ) {
	$actif = 'nouveautes';
} elseif ( is_page( 'promos' ) ) {
	$actif = 'promos';
} elseif ( is_tax( 'product_cat' ) ) {
	$terme    = get_queried_object();
	$ancetres = get_ancestors( $terme->term_id, 'product_cat' );
	$haut     = $ancetres ? get_term( end( $ancetres ), 'product_cat' ) : $terme;
	$actif    = 'rayon-' . $haut->slug;
}

$tuile_diapos = array(
	array(
		'sur'   => 'Classiques',
		'nom'   => 'Pouchkine',
		'image' => er_image( 'branche-librairie.jpg' ),
		'pos'   => '50% 28%',
		'lien'  => er_rayon( 'litterature' ),
	),
	array(
		'sur'   => 'Le livre du moment',
		'nom'   => 'Notes d’un médecin',
		'image' => er_image( 'tuile-medecin.jpg' ),
		'pos'   => '50% 40%',
		'lien'  => er_livre( 'notes-dun-medecin' ),
	),
);

$chevron = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

$entree = function ( $cle, $libelle, $lien ) use ( $actif ) {
	$courant = $cle === $actif ? ' aria-current="page"' : '';
	return '<li class="er-rayon"><a class="er-rayon-lien" href="' . esc_url( $lien ) . '"' . $courant . '>' . esc_html( $libelle ) . '</a></li>';
};
?>
<!-- wp:group {"align":"full","className":"er-librairie-entete","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull er-librairie-entete">
	<!-- wp:html -->
	<p class="er-devise">Les Éditeurs réunis, librairie de référence depuis 1925</p>
	<nav class="er-rayons-nav" aria-label="Les rayons de la librairie">
		<ul class="er-rayons-liste">
			<?php echo $entree( 'accueil', 'Accueil', er_lien( 'librairie/' ) ); // phpcs:ignore ?>
			<?php
			foreach ( $er_rayons( 0 ) as $rayon ) :
				$sous    = $er_rayons( $rayon->term_id );
				$id      = 'er-tiroir-' . $rayon->slug;
				$courant = 'rayon-' . $rayon->slug === $actif ? ' aria-current="page"' : '';
				?>
			<li class="er-rayon er-a-tiroir">
				<a class="er-rayon-lien" href="<?php echo esc_url( get_term_link( $rayon ) ); ?>"<?php echo $courant; // phpcs:ignore ?>><?php echo esc_html( $rayon->name ); ?></a>
				<button type="button" class="er-rayon-ouvrir" aria-expanded="false" aria-controls="<?php echo esc_attr( $id ); ?>" aria-label="<?php echo esc_attr( 'Les rayons de ' . $rayon->name ); ?>"><?php echo $chevron; // phpcs:ignore ?></button>
				<div class="er-tiroir" id="<?php echo esc_attr( $id ); ?>">
					<div class="er-tiroir-dedans">
						<ul class="er-tiroir-liens">
							<?php foreach ( $sous as $s ) : ?>
							<li><a href="<?php echo esc_url( get_term_link( $s ) ); ?>"><?php echo esc_html( $s->name ); ?></a></li>
							<?php endforeach; ?>
							<li class="er-tiroir-tout"><a href="<?php echo esc_url( get_term_link( $rayon ) ); ?>"><?php echo esc_html( 'Tout le rayon ' . $rayon->name ); ?></a></li>
						</ul>

						<div class="er-tuile er-diaporama er-diaporama-mini" data-libelle="<?php echo esc_attr( 'À découvrir, ' . $rayon->name ); ?>">
							<div class="er-diapos">
								<?php foreach ( $tuile_diapos as $d ) : ?>
								<div class="er-diapo er-tuile-diapo" style="background-image:url('<?php echo $d['image']; // phpcs:ignore ?>');background-position:<?php echo esc_attr( $d['pos'] ); ?>">
									<p class="er-tuile-sur"><?php echo esc_html( $d['sur'] ); ?></p>
									<p class="er-tuile-nom er-diapo-titre"><?php echo esc_html( $d['nom'] ); ?></p>
									<p class="er-tuile-bouton"><a href="<?php echo $d['lien']; // phpcs:ignore ?>">Découvrir</a></p>
								</div>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="er-tuile er-tuile-anthologies">
							<img src="<?php echo er_image( 'anthologies-escalier.png' ); ?>" alt="" loading="lazy">
							<p class="er-tuile-phrase">Découvrez nos anthologies bilingues de la poésie russe</p>
							<p class="er-tuile-bouton"><a href="<?php echo er_rayon( 'poesie' ); ?>">Découvrir</a></p>
						</div>
					</div>
				</div>
			</li>
			<?php endforeach; ?>
			<?php echo $entree( 'ventes', 'Nos meilleures ventes', add_query_arg( 'orderby', 'popularity', er_lien( 'librairie/' ) ) ); // phpcs:ignore ?>
			<?php echo $entree( 'nouveautes', 'Nouveautés', er_lien( 'mention/nouveaute/' ) ); // phpcs:ignore ?>
			<?php echo $entree( 'promos', 'Promos', er_lien( 'promos/' ) ); // phpcs:ignore ?>
		</ul>
	</nav>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
