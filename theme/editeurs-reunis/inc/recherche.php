<?php
/**
 * Le panneau de recherche, qu'ouvre la loupe de la barre noire (retenu le 21 novembre 2026).
 *
 * Le panneau est écrit ici, caché, au bas de chaque page ; assets/js/recherche.js l'ouvre et le remplit en lisant
 * l'API de l'extension de la librairie (/er/v1/chercher, /er/v1/livres). Trois états :
 * - avant de taper, des pistes : les dernières recherches (gardées dans le navigateur), les recherches fréquentes,
 *   les rayons, les langues, ailleurs sur le site, les derniers livres arrivés ;
 * - en tapant, les résultats rangés par genre, chaque genre avec le point de sa maison ;
 * - la recherche détaillée, des champs qui se combinent, avec le nombre de livres qui répondent.
 * Sans JavaScript, la loupe mène à la recherche des livres, et la recherche détaillée reste un formulaire.
 *
 * @package editeurs-reunis
 */

defined( 'ABSPATH' ) || exit;

/**
 * L'adresse de la recherche des livres pour un texte.
 *
 * @param string $texte Ce qu'on cherche.
 */
function er_lien_recherche( $texte = '' ) {
	return add_query_arg(
		array(
			's'         => rawurlencode( $texte ),
			'post_type' => 'product',
		),
		function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' )
	);
}

/**
 * Les recherches fréquentes, proposées avant qu'on tape. Le filtre « er_recherches_frequentes » permet d'en changer.
 */
function er_recherches_frequentes() {
	return apply_filters(
		'er_recherches_frequentes',
		array(
			__( 'Soljenitsyne', 'editeurs-reunis' ),
			__( 'L’Archipel du Goulag', 'editeurs-reunis' ),
			__( 'Akhmatova', 'editeurs-reunis' ),
			__( 'Gouzel Iakhina', 'editeurs-reunis' ),
			__( 'Poésie', 'editeurs-reunis' ),
			__( 'Dictionnaire', 'editeurs-reunis' ),
		)
	);
}

/**
 * Un point de couleur de maison.
 *
 * @param string $maison librairie, ymca ou centre.
 */
function er_point_maison( $maison ) {
	return '<span class="er-point er-point-' . esc_attr( $maison ) . '" aria-hidden="true"></span>';
}

/**
 * Les valeurs d'un critère dans l'adresse (pour préremplir la recherche détaillée sur une page de résultats).
 *
 * @param string $champ Le nom du champ, sans « er_ ».
 */
function er_critere_courant( $champ ) {
	$v = isset( $_GET[ 'er_' . $champ ] ) ? wp_unslash( $_GET[ 'er_' . $champ ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	return is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : sanitize_text_field( $v );
}

/**
 * Le panneau, au bas de chaque page.
 */
function er_panneau_recherche() {
	$loupe = '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M15.5 15.5L20 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';
	$croix = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
	$chev  = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	$accueil = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
	$boutique = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : $accueil;

	// Les rayons (les grands), les langues des livres, les derniers livres arrivés.
	$rayons  = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
		)
	);
	$rayons  = is_wp_error( $rayons ) ? array() : array_values( array_filter( $rayons, fn( $t ) => ! in_array( $t->slug, array( 'uncategorized', 'sans-rayon' ), true ) ) );
	usort( $rayons, fn( $a, $b ) => (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true ) );
	$langues = get_terms(
		array(
			'taxonomy'   => 'langue',
			'hide_empty' => false,
		)
	);
	$langues = is_wp_error( $langues ) ? array() : $langues;
	$livres  = function_exists( 'wc_get_products' ) ? wc_get_products(
		array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'limit'      => 3,
			'orderby'    => 'date',
			'order'      => 'DESC',
		)
	) : array();

	$etats = array(
		'neuf'             => __( 'Neuf', 'editeurs-reunis' ),
		'occasion'         => __( 'Occasion', 'editeurs-reunis' ),
		'edition-ancienne' => __( 'Édition ancienne', 'editeurs-reunis' ),
		'tres-rare'        => __( 'Très rare', 'editeurs-reunis' ),
	);
	$courant_langues = (array) er_critere_courant( 'langue' );
	$courant_etats   = (array) er_critere_courant( 'etat' );
	$courant_rayon   = (array) er_critere_courant( 'rayon' );

	// Où chercher : les trois maisons ; puis, pour les livres, le rayon, la langue, l'état. Des boutons à presser (un
	// seul pour « Où chercher », plusieurs ailleurs), jamais des liens : ils filtrent ce qu'on tape.
	$maisons = array(
		'tout'      => __( 'Partout', 'editeurs-reunis' ),
		'librairie' => __( 'La librairie', 'editeurs-reunis' ),
		'ymca'      => __( 'YMCA-Press', 'editeurs-reunis' ),
		'centre'    => __( 'Le centre culturel', 'editeurs-reunis' ),
	);
	$filtre = function ( $nom, $libelle, $options, $un_seul = false ) {
		$html = '<fieldset class="er-filtre" data-er-filtre="' . esc_attr( $nom ) . '"' . ( $un_seul ? ' data-er-un-seul' : '' ) . '><legend class="er-filtre-titre">' . esc_html( $libelle ) . '</legend><div class="er-filtre-choix">';
		foreach ( $options as $valeur => $texte ) {
			$presse = $un_seul && 'tout' === $valeur;
			$point  = 'maison' === $nom && 'tout' !== $valeur ? er_point_maison( $valeur ) : '';
			$html  .= '<button type="button" class="er-choix' . ( $presse ? ' est-active' : '' ) . '" aria-pressed="' . ( $presse ? 'true' : 'false' ) . '" data-er-valeur="' . esc_attr( $valeur ) . '">' . $point . '<span>' . esc_html( $texte ) . '</span></button>';
		}
		return $html . '</div></fieldset>';
	};
	$champ_texte = function ( $nom, $libelle, $aide, $liste = '' ) {
		return '<label class="er-rd-champ"><span class="er-rd-libelle">' . esc_html( $libelle ) . '</span>'
			. '<input type="text" name="er_' . esc_attr( $nom ) . '" value="' . esc_attr( er_critere_courant( $nom ) ) . '" placeholder="' . esc_attr( $aide ) . '" autocomplete="off"' . ( $liste ? ' list="' . esc_attr( $liste ) . '"' : '' ) . '></label>';
	};
	$bornes = function ( $nom, $libelle, $unite = '' ) {
		return '<div class="er-rd-champ"><span class="er-rd-libelle">' . esc_html( $libelle ) . '</span><span class="er-rd-bornes">'
			. '<input type="number" inputmode="numeric" name="er_' . esc_attr( $nom ) . '_de" value="' . esc_attr( er_critere_courant( $nom . '_de' ) ) . '" placeholder="' . esc_attr__( 'de', 'editeurs-reunis' ) . '" aria-label="' . esc_attr( $libelle . ', ' . __( 'de', 'editeurs-reunis' ) ) . '">'
			. '<span aria-hidden="true">–</span>'
			. '<input type="number" inputmode="numeric" name="er_' . esc_attr( $nom ) . '_a" value="' . esc_attr( er_critere_courant( $nom . '_a' ) ) . '" placeholder="' . esc_attr__( 'à', 'editeurs-reunis' ) . '" aria-label="' . esc_attr( $libelle . ', ' . __( 'à', 'editeurs-reunis' ) ) . '">'
			. ( $unite ? '<span class="er-rd-unite">' . esc_html( $unite ) . '</span>' : '' ) . '</span></div>';
	};
	$cases = function ( $nom, $libelle, $options, $coches ) {
		$html = '<fieldset class="er-rd-champ"><legend class="er-rd-libelle">' . esc_html( $libelle ) . '</legend><span class="er-rd-pilules">';
		foreach ( $options as $valeur => $texte ) {
			$html .= '<label class="er-rd-pilule"><input type="checkbox" name="er_' . esc_attr( $nom ) . '[]" value="' . esc_attr( $valeur ) . '"' . checked( in_array( $valeur, $coches, true ), true, false ) . '><span>' . esc_html( $texte ) . '</span></label>';
		}
		return $html . '</span></fieldset>';
	};
	?>
	<div class="er-recherche" id="er-recherche" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Rechercher', 'editeurs-reunis' ); ?>" hidden>
		<div class="er-recherche-voile" data-er-fermer></div>
		<div class="er-recherche-panneau">
			<div class="er-recherche-dedans">
				<div class="er-recherche-tete">
					<form class="er-recherche-champ" role="search" method="get" action="<?php echo esc_url( $accueil ); ?>">
						<?php echo $loupe; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<label class="screen-reader-text" for="er-recherche-texte"><?php esc_html_e( 'Chercher un titre, un auteur, un sujet', 'editeurs-reunis' ); ?></label>
						<input type="search" id="er-recherche-texte" name="s" autocomplete="off" spellcheck="false" placeholder="<?php esc_attr_e( 'Un titre, un auteur, un sujet…', 'editeurs-reunis' ); ?>" aria-controls="er-recherche-resultats">
						<input type="hidden" name="post_type" value="product">
						<button type="button" class="er-recherche-effacer" hidden><?php echo $croix; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Effacer', 'editeurs-reunis' ); ?></button>
					</form>
					<button type="button" class="er-recherche-bascule" aria-expanded="false" aria-controls="er-recherche-detaillee"><?php esc_html_e( 'Recherche détaillée', 'editeurs-reunis' ); ?><?php echo $chev; // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<button type="button" class="er-recherche-fermer" data-er-fermer><?php echo $croix; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Fermer', 'editeurs-reunis' ); ?></button>
				</div>
				<div class="er-recherche-corps">
					<aside class="er-recherche-filtres" aria-label="<?php esc_attr_e( 'Filtrer', 'editeurs-reunis' ); ?>">
						<button type="button" class="er-recherche-filtrer" aria-expanded="false" aria-controls="er-recherche-groupes"><?php esc_html_e( 'Filtrer', 'editeurs-reunis' ); ?><span class="er-recherche-nombre" hidden></span><?php echo $chev; // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
						<div class="er-recherche-groupes" id="er-recherche-groupes">
							<div class="er-filtres-tete">
								<p class="er-recherche-surtitre"><?php esc_html_e( 'Filtrer', 'editeurs-reunis' ); ?></p>
								<button type="button" class="er-filtres-effacer" hidden><?php esc_html_e( 'Tout effacer', 'editeurs-reunis' ); ?></button>
							</div>
							<?php
							// phpcs:disable WordPress.Security.EscapeOutput -- les filtres sont échappés pièce à pièce.
							echo $filtre( 'maison', __( 'Où chercher', 'editeurs-reunis' ), $maisons, true );
							?>
							<div class="er-filtres-livres">
								<?php
								echo $filtre( 'rayon', __( 'Rayon', 'editeurs-reunis' ), wp_list_pluck( $rayons, 'name', 'slug' ) );
								echo $filtre( 'langue', __( 'Langue', 'editeurs-reunis' ), wp_list_pluck( $langues, 'name', 'slug' ) );
								echo $filtre( 'etat', __( 'État', 'editeurs-reunis' ), $etats );
								// phpcs:enable
								?>
							</div>
						</div>
					</aside>

					<div class="er-recherche-principal">
						<p class="er-recherche-pour" hidden></p>
						<div class="er-recherche-portees" hidden></div>

						<div class="er-recherche-pistes">
							<div class="er-recherche-colonne">
								<div class="er-recherche-recentes" hidden>
									<p class="er-recherche-surtitre"><?php esc_html_e( 'Vos dernières recherches', 'editeurs-reunis' ); ?></p>
									<ul class="er-recherche-lignes"></ul>
								</div>
								<p class="er-recherche-surtitre"><?php esc_html_e( 'On cherche souvent', 'editeurs-reunis' ); ?></p>
								<ul class="er-recherche-lignes">
									<?php foreach ( er_recherches_frequentes() as $frequente ) : ?>
										<li><a class="er-r-lien er-recherche-proposition" href="<?php echo esc_url( er_lien_recherche( $frequente ) ); ?>" data-er-texte="<?php echo esc_attr( $frequente ); ?>"><?php echo esc_html( $frequente ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
							<?php if ( $livres ) : ?>
								<div class="er-recherche-colonne">
									<p class="er-recherche-surtitre"><?php esc_html_e( 'Les derniers arrivés', 'editeurs-reunis' ); ?></p>
									<ul class="er-recherche-derniers">
										<?php foreach ( $livres as $livre ) : ?>
											<?php $auteurs = wp_get_post_terms( $livre->get_id(), 'auteur', array( 'fields' => 'names' ) ); ?>
											<li><a class="er-r-lien" href="<?php echo esc_url( get_permalink( $livre->get_id() ) ); ?>">
												<?php if ( $livre->get_image_id() ) : ?>
													<img src="<?php echo esc_url( wp_get_attachment_image_url( $livre->get_image_id(), 'woocommerce_thumbnail' ) ); ?>" alt="" loading="lazy">
												<?php else : ?>
													<span class="er-r-couverture-vide" aria-hidden="true"></span>
												<?php endif; ?>
												<span class="er-r-titre"><?php echo esc_html( $livre->get_name() ); ?></span>
												<?php if ( $auteurs && ! is_wp_error( $auteurs ) ) : ?>
													<span class="er-r-sous"><?php echo esc_html( implode( ', ', $auteurs ) ); ?></span>
												<?php endif; ?>
											</a></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>

						<div class="er-recherche-resultats" id="er-recherche-resultats" aria-live="polite" hidden></div>
					</div>
				</div>

				<form class="er-recherche-detaillee" id="er-recherche-detaillee" method="get" action="<?php echo esc_url( $boutique ); ?>" hidden>
					<div class="er-rd-grille">
						<?php
						// phpcs:disable WordPress.Security.EscapeOutput -- les champs sont échappés pièce à pièce.
						echo $champ_texte( 'titre', __( 'Titre', 'editeurs-reunis' ), __( 'Un mot du titre', 'editeurs-reunis' ) );
						echo $champ_texte( 'auteur', __( 'Auteur', 'editeurs-reunis' ), __( 'Dans n’importe quelle écriture', 'editeurs-reunis' ) );
						echo $champ_texte( 'traducteur', __( 'Traducteur, préfacier', 'editeurs-reunis' ), __( 'Par exemple Georges Nivat', 'editeurs-reunis' ) );
						echo $champ_texte( 'isbn', __( 'ISBN', 'editeurs-reunis' ), '978…' );
						?>
						<label class="er-rd-champ"><span class="er-rd-libelle"><?php esc_html_e( 'Rayon', 'editeurs-reunis' ); ?></span>
							<select name="er_rayon[]">
								<option value=""><?php esc_html_e( 'Tous les rayons', 'editeurs-reunis' ); ?></option>
								<?php foreach ( $rayons as $rayon ) : ?>
									<option value="<?php echo esc_attr( $rayon->slug ); ?>"<?php selected( in_array( $rayon->slug, $courant_rayon, true ) ); ?>><?php echo esc_html( $rayon->name ); ?></option>
									<?php
									$sous = get_terms(
										array(
											'taxonomy'   => 'product_cat',
											'parent'     => $rayon->term_id,
											'hide_empty' => false,
										)
									);
									foreach ( is_wp_error( $sous ) ? array() : $sous as $s ) :
										?>
										<option value="<?php echo esc_attr( $s->slug ); ?>"<?php selected( in_array( $s->slug, $courant_rayon, true ) ); ?>>&nbsp;&nbsp;&nbsp;<?php echo esc_html( $s->name ); ?></option>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</select>
						</label>
						<?php
						echo $cases( 'langue', __( 'Langue', 'editeurs-reunis' ), wp_list_pluck( $langues, 'name', 'slug' ), $courant_langues );
						echo $champ_texte( 'editeur', __( 'Éditeur', 'editeurs-reunis' ), __( 'Par exemple YMCA-Press', 'editeurs-reunis' ), 'er-rd-editeurs' );
						echo $champ_texte( 'collection', __( 'Collection', 'editeurs-reunis' ), __( 'Toutes', 'editeurs-reunis' ), 'er-rd-collections' );
						echo $bornes( 'annee', __( 'Année d’édition', 'editeurs-reunis' ) );
						echo $bornes( 'prix', __( 'Prix', 'editeurs-reunis' ), '€' );
						echo $cases( 'etat', __( 'État', 'editeurs-reunis' ), $etats, $courant_etats );
						// phpcs:enable
						?>
						<div class="er-rd-champ"><span class="er-rd-libelle"><?php esc_html_e( 'Disponibilité', 'editeurs-reunis' ); ?></span>
							<label class="er-rd-bascule"><input type="checkbox" name="er_stock" value="1"<?php checked( '' !== er_critere_courant( 'stock' ) ); ?>><span class="er-rd-interrupteur" aria-hidden="true"></span><?php esc_html_e( 'En stock seulement', 'editeurs-reunis' ); ?></label>
						</div>
					</div>
					<datalist id="er-rd-editeurs"><?php foreach ( function_exists( 'er_valeurs_champ' ) ? er_valeurs_champ( 'er_editeur' ) : array() as $v ) : ?><option value="<?php echo esc_attr( $v ); ?>"><?php endforeach; ?></datalist>
					<datalist id="er-rd-collections"><?php foreach ( function_exists( 'er_valeurs_champ' ) ? er_valeurs_champ( 'er_collection' ) : array() as $v ) : ?><option value="<?php echo esc_attr( $v ); ?>"><?php endforeach; ?></datalist>
					<div class="er-rd-pied">
						<div class="er-rd-criteres" aria-live="polite"></div>
						<div class="er-rd-actions">
							<button type="reset" class="er-rd-effacer"><?php esc_html_e( 'Tout effacer', 'editeurs-reunis' ); ?></button>
							<button type="submit" class="er-rd-voir"><?php esc_html_e( 'Voir les livres', 'editeurs-reunis' ); ?></button>
						</div>
					</div>
					<p class="er-rd-note"><?php esc_html_e( 'Les champs se combinent, on peut n’en remplir qu’un ; le nombre de livres change à chaque choix.', 'editeurs-reunis' ); ?></p>
				</form>

				<div class="er-recherche-aide">
					<p><?php echo wp_kses_post( __( 'On cherche dans les trois langues : <strong>Dostoïevski</strong>, <strong lang="ru">Достоевский</strong> ou <strong>Dostoevsky</strong> mènent au même auteur.', 'editeurs-reunis' ) ); ?></p>
					<p class="er-recherche-touches"><kbd>↑</kbd><kbd>↓</kbd> <?php esc_html_e( 'choisir', 'editeurs-reunis' ); ?> <kbd><?php esc_html_e( 'Entrée', 'editeurs-reunis' ); ?></kbd> <?php esc_html_e( 'ouvrir', 'editeurs-reunis' ); ?> <kbd><?php esc_html_e( 'Échap', 'editeurs-reunis' ); ?></kbd> <?php esc_html_e( 'fermer', 'editeurs-reunis' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'er_panneau_recherche', 5 );

/**
 * Le script du panneau et ses mots, dans la langue de la page.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$version = wp_get_theme()->get( 'Version' );
		wp_enqueue_script(
			'editeurs-reunis-recherche',
			get_theme_file_uri( 'assets/js/recherche.js' ),
			array(),
			$version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		$langue = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
		wp_add_inline_script(
			'editeurs-reunis-recherche',
			'window.erRecherche = ' . wp_json_encode(
				array(
					'api'    => esc_url_raw( rest_url( 'er/v1/' ) ),
					'langue' => $langue,
					'mots'   => array(
						'tout'          => __( 'Tout', 'editeurs-reunis' ),
						'livres'        => __( 'Livres', 'editeurs-reunis' ),
						'auteurs'       => __( 'Auteurs', 'editeurs-reunis' ),
						'rayons'        => __( 'Rayons', 'editeurs-reunis' ),
						'encyclopedie'  => __( 'Encyclopédie', 'editeurs-reunis' ),
						'ymca'          => __( 'YMCA-Press', 'editeurs-reunis' ),
						'centre'        => __( 'Centre culturel', 'editeurs-reunis' ),
						'journal'       => __( 'Journal', 'editeurs-reunis' ),
						'pages'         => __( 'Pages', 'editeurs-reunis' ),
						'g_auteurs'     => __( 'Auteur', 'editeurs-reunis' ),
						'g_livres'      => __( 'Livres', 'editeurs-reunis' ),
						'g_rayons'      => __( 'Rayons', 'editeurs-reunis' ),
						'g_encyclopedie' => __( 'Dans l’encyclopédie', 'editeurs-reunis' ),
						'g_ymca'        => __( 'Chez YMCA-Press', 'editeurs-reunis' ),
						'g_centre'      => __( 'Au centre culturel', 'editeurs-reunis' ),
						'g_journal'     => __( 'Dans le journal', 'editeurs-reunis' ),
						'g_pages'       => __( 'Pages', 'editeurs-reunis' ),
						/* translators: %s: le nom reconnu (un auteur). */
						'pour'          => __( 'Résultats pour %s.', 'editeurs-reunis' ),
						/* translators: %s: les autres noms de l'auteur. */
						'aussi'         => __( 'Cherché aussi : %s.', 'editeurs-reunis' ),
						/* translators: %s: ce qu'on a tapé. */
						'aucun'         => __( 'Rien pour « %s ». Essayez un autre mot, une autre écriture, ou la recherche détaillée.', 'editeurs-reunis' ),
						/* translators: %s: un nombre de livres. */
						'voir_livres'   => __( 'Voir les %s livres', 'editeurs-reunis' ),
						'voir_livre'    => __( 'Voir le livre', 'editeurs-reunis' ),
						'voir_aucun'    => __( 'Aucun livre', 'editeurs-reunis' ),
						/* translators: %s: un nombre de livres. */
						'tous_livres'   => __( 'Les %s livres', 'editeurs-reunis' ),
						'page_auteur'   => __( 'La page de l’auteur', 'editeurs-reunis' ),
						/* translators: %s: un nombre de livres. */
						'n_livres'      => __( '%s livres en rayon', 'editeurs-reunis' ),
						'un_livre'      => __( 'Un livre en rayon', 'editeurs-reunis' ),
						'en_stock'      => __( 'en stock', 'editeurs-reunis' ),
						'rupture'       => __( 'épuisé pour l’instant', 'editeurs-reunis' ),
						'entree'        => __( 'Les résultats changent à chaque lettre ; Entrée ouvre la page des livres trouvés.', 'editeurs-reunis' ),
						'criteres'      => __( 'Vos critères :', 'editeurs-reunis' ),
						/* translators: %s: le critère qu'on retire. */
						'retirer'       => __( 'Retirer : %s', 'editeurs-reunis' ),
						'fermer'        => __( 'Fermer', 'editeurs-reunis' ),
						'rechercher'    => __( 'Rechercher', 'editeurs-reunis' ),
						'effacer_recentes' => __( 'Effacer', 'editeurs-reunis' ),
						'aucun_filtre'  => __( 'Aucun livre ne répond à ces filtres. Retirez-en un, ou essayez la recherche détaillée.', 'editeurs-reunis' ),
						/* translators: %s: ce qu'on a tapé. */
						'aucun_texte_filtre' => __( 'Rien pour « %s » avec ces filtres. Retirez-en un, ou essayez un autre mot.', 'editeurs-reunis' ),
						'champ_tout'    => __( 'Un titre, un auteur, un sujet…', 'editeurs-reunis' ),
						'champ_librairie' => __( 'Un titre, un auteur, un rayon…', 'editeurs-reunis' ),
						'champ_ymca'    => __( 'Dans le catalogue d’YMCA-Press…', 'editeurs-reunis' ),
						'champ_centre'  => __( 'Dans les événements et le journal du centre…', 'editeurs-reunis' ),
					),
				)
			) . ';',
			'before'
		);
	}
);
