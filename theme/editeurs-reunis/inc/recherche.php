<?php
/**
 * Le panneau de recherche, qu'ouvre la loupe de l'en-tête (retenu le 21 novembre 2026, redessiné le 27 : AB1a).
 *
 * Le panneau est écrit ici, caché, au bas de chaque page ; assets/js/recherche.js l'ouvre et le remplit en lisant
 * l'API de l'extension de la librairie (/er/v1/chercher, /er/v1/livres). Il s'ouvre sous la barre comme un menu, sur
 * le voile blanc des menus, et se cale sur les trois colonnes du triptyque. Trois états :
 * - avant de taper, des pistes : les dernières recherches (gardées dans le navigateur ; à défaut, les rayons), les
 *   recherches fréquentes, le dernier livre arrivé ;
 * - en tapant, les genres et leurs nombres à la place des maisons ; les résultats en trois colonnes ;
 * - la recherche détaillée (27 nov.) : le panneau s'agrandit jusqu'au bas de l'écran, sous l'en-tête ; des champs qui
 *   se combinent, sur les trois colonnes du triptyque, avec le nombre de livres qui répondent. Le même bouton, au même
 *   endroit, l'ouvre et la referme (« Recherche détaillée → », puis « ← Recherche simple »).
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
 * Le dernier livre arrivé, ou le livre visé dans les résultats : la couverture au centre d'un cadre beige (AA1),
 * le titre, l'auteur et le prix dessous. Le script fabrique le même pour les résultats.
 *
 * @param WC_Product $livre Le livre.
 */
function er_recherche_vue( $livre ) {
	$id     = $livre->get_id();
	$titre  = html_entity_decode( $livre->get_name(), ENT_QUOTES, 'UTF-8' );
	$auteur = function_exists( 'er_em_auteurs' ) ? er_em_auteurs( $id ) : '';
	$prix   = trim( wp_strip_all_tags( html_entity_decode( $livre->get_price_html(), ENT_QUOTES, 'UTF-8' ) ) );
	$sous   = implode( ' · ', array_filter( array( $auteur, $prix ) ) );
	$image  = $livre->get_image_id() ? wp_get_attachment_image_url( $livre->get_image_id(), 'medium' ) : '';
	$lang   = preg_match( '/\p{Cyrillic}/u', $titre ) ? ' lang="ru"' : '';
	return '<a class="er-r-vue" href="' . esc_url( get_permalink( $id ) ) . '"><span class="er-r-vue-cadre">'
		. ( $image ? '<img src="' . esc_url( $image ) . '" alt="" loading="lazy">' : '<span class="er-r-couverture-vide" aria-hidden="true"><span>' . esc_html( $titre ) . '</span></span>' )
		. '</span><span class="er-r-vue-texte"><span class="er-r-vue-titre"' . $lang . '>' . esc_html( $titre ) . '</span>'
		. ( $sous ? '<span class="er-r-vue-sous">' . esc_html( $sous ) . '</span>' : '' ) . '</span></a>';
}

/**
 * Le panneau, au bas de chaque page (AB1a, retenu le 27 novembre 2026) : au-dessus du champ, les maisons en capitales
 * (en tapant, les genres et leurs nombres) ; sous le champ, trois colonnes calées sur les trois photos du triptyque
 * de l'accueil. Avant de taper : vos dernières recherches (ou les rayons), ce qu'on cherche souvent, le dernier livre
 * arrivé. En tapant : l'auteur et les livres ; le reste du site ; la couverture du livre survolé.
 */
function er_panneau_recherche() {
	$loupe = '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M15.5 15.5L20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
	$croix = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
	$accueil  = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
	$boutique = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : $accueil;

	// Les rayons (les grands), les langues des livres, le dernier livre arrivé.
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
			'limit'      => 1,
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

	// Où chercher : les trois maisons, en capitales comme les noms de la barre (un seul choix ; des boutons, jamais des
	// liens : ils règlent ce qu'on tape). Le rayon, la langue et l'état des livres sont dans la recherche détaillée.
	$maisons = array(
		'tout'      => __( 'Partout', 'editeurs-reunis' ),
		'librairie' => __( 'La librairie', 'editeurs-reunis' ),
		'ymca'      => __( 'YMCA-Press', 'editeurs-reunis' ),
		'centre'    => __( 'Le centre culturel', 'editeurs-reunis' ),
	);
	$choix_maisons = '';
	foreach ( $maisons as $valeur => $texte ) {
		$presse         = 'tout' === $valeur;
		$choix_maisons .= '<button type="button" class="er-choix' . ( $presse ? ' est-active' : '' ) . '" aria-pressed="' . ( $presse ? 'true' : 'false' ) . '" data-er-valeur="' . esc_attr( $valeur ) . '"><span>' . esc_html( $texte ) . '</span></button>';
	}
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
					<div class="er-recherche-maisons er-filtre" data-er-filtre="maison" data-er-un-seul role="group" aria-label="<?php esc_attr_e( 'Où chercher', 'editeurs-reunis' ); ?>"><?php echo $choix_maisons; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div class="er-recherche-portees" role="group" aria-label="<?php esc_attr_e( 'Les résultats', 'editeurs-reunis' ); ?>" hidden></div>
					<p class="er-recherche-mode" id="er-recherche-mode"><?php esc_html_e( 'Recherche détaillée', 'editeurs-reunis' ); ?></p>
					<button type="button" class="er-recherche-bascule" aria-expanded="false" aria-controls="er-recherche-detaillee"><?php esc_html_e( 'Recherche détaillée', 'editeurs-reunis' ); ?>&nbsp;<span aria-hidden="true">→</span></button>
					<button type="button" class="er-recherche-fermer" data-er-fermer><?php echo $croix; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Fermer', 'editeurs-reunis' ); ?></button>
				</div>
				<form class="er-recherche-champ" role="search" method="get" action="<?php echo esc_url( $accueil ); ?>">
					<?php echo $loupe; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<label class="screen-reader-text" for="er-recherche-texte"><?php esc_html_e( 'Chercher un titre, un auteur, un sujet', 'editeurs-reunis' ); ?></label>
					<input type="search" id="er-recherche-texte" name="s" autocomplete="off" spellcheck="false" placeholder="<?php esc_attr_e( 'Un titre, un auteur, un sujet…', 'editeurs-reunis' ); ?>" aria-controls="er-recherche-resultats">
					<input type="hidden" name="post_type" value="product">
					<button type="button" class="er-recherche-effacer" hidden><?php esc_html_e( 'Effacer', 'editeurs-reunis' ); ?></button>
				</form>
				<div class="er-recherche-corps">
					<div class="er-recherche-pistes er-grille-tpq">
						<div class="er-recherche-colonne">
							<div class="er-recherche-recentes" hidden>
								<p class="er-recherche-libelle"><?php esc_html_e( 'Vos dernières recherches', 'editeurs-reunis' ); ?></p>
								<ul class="er-recherche-lignes"></ul>
							</div>
							<div class="er-recherche-rayons">
								<p class="er-recherche-libelle"><?php esc_html_e( 'Les rayons', 'editeurs-reunis' ); ?></p>
								<ul class="er-recherche-lignes">
									<?php foreach ( $rayons as $rayon ) : ?>
										<?php $lien = get_term_link( $rayon ); ?>
										<li><a class="er-r-lien" href="<?php echo esc_url( is_wp_error( $lien ) ? $boutique : $lien ); ?>"><?php echo esc_html( $rayon->name ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
						<div class="er-recherche-colonne">
							<p class="er-recherche-libelle"><?php esc_html_e( 'On cherche souvent', 'editeurs-reunis' ); ?></p>
							<ul class="er-recherche-lignes">
								<?php foreach ( er_recherches_frequentes() as $frequente ) : ?>
									<li><a class="er-r-lien er-recherche-proposition" href="<?php echo esc_url( er_lien_recherche( $frequente ) ); ?>" data-er-texte="<?php echo esc_attr( $frequente ); ?>"><?php echo esc_html( $frequente ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="er-recherche-colonne">
							<?php if ( $livres ) : ?>
								<p class="er-recherche-libelle"><?php esc_html_e( 'Le dernier arrivé', 'editeurs-reunis' ); ?></p>
								<?php echo er_recherche_vue( $livres[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="er-recherche-resultats" id="er-recherche-resultats" aria-live="polite" hidden></div>
				</div>

				<form class="er-recherche-detaillee" id="er-recherche-detaillee" method="get" action="<?php echo esc_url( $boutique ); ?>" aria-labelledby="er-recherche-mode" hidden>
					<div class="er-rd-grille er-grille-tpq">
						<div class="er-rd-colonne">
							<?php
							// phpcs:disable WordPress.Security.EscapeOutput -- les champs sont échappés pièce à pièce.
							echo $champ_texte( 'titre', __( 'Titre', 'editeurs-reunis' ), __( 'Un mot du titre', 'editeurs-reunis' ) );
							echo $champ_texte( 'auteur', __( 'Auteur', 'editeurs-reunis' ), __( 'Dans n’importe quelle écriture', 'editeurs-reunis' ) );
							echo $champ_texte( 'traducteur', __( 'Traducteur, préfacier', 'editeurs-reunis' ), __( 'Par exemple Georges Nivat', 'editeurs-reunis' ) );
							echo $champ_texte( 'isbn', __( 'ISBN', 'editeurs-reunis' ), '978…' );
							?>
						</div>
						<div class="er-rd-colonne">
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
							echo $champ_texte( 'editeur', __( 'Éditeur', 'editeurs-reunis' ), __( 'Par exemple YMCA-Press', 'editeurs-reunis' ), 'er-rd-editeurs' );
							echo $champ_texte( 'collection', __( 'Collection', 'editeurs-reunis' ), __( 'Toutes', 'editeurs-reunis' ), 'er-rd-collections' );
							echo $bornes( 'annee', __( 'Année d’édition', 'editeurs-reunis' ) );
							echo $bornes( 'prix', __( 'Prix', 'editeurs-reunis' ), '€' );
							?>
						</div>
						<div class="er-rd-colonne">
							<?php
							echo $cases( 'langue', __( 'Langue', 'editeurs-reunis' ), wp_list_pluck( $langues, 'name', 'slug' ), $courant_langues );
							echo $cases( 'etat', __( 'État', 'editeurs-reunis' ), $etats, $courant_etats );
							// phpcs:enable
							?>
							<div class="er-rd-champ"><span class="er-rd-libelle"><?php esc_html_e( 'Disponibilité', 'editeurs-reunis' ); ?></span>
								<label class="er-rd-bascule"><input type="checkbox" name="er_stock" value="1"<?php checked( '' !== er_critere_courant( 'stock' ) ); ?>><span class="er-rd-interrupteur" aria-hidden="true"></span><?php esc_html_e( 'En stock seulement', 'editeurs-reunis' ); ?></label>
							</div>
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
				</form>
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
			// La date du fichier dans l'adresse : sans elle, le navigateur gardait l'ancien script (27 nov.).
			$version . '.' . filemtime( get_theme_file_path( 'assets/js/recherche.js' ) ),
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
						'le_livre'      => __( 'Le livre', 'editeurs-reunis' ),
						'detaillee'     => __( 'Recherche détaillée', 'editeurs-reunis' ),
						'simple'        => __( 'Recherche simple', 'editeurs-reunis' ),
						/* translators: %s: un nombre de livres. */
						'n_livres'      => __( '%s livres en rayon', 'editeurs-reunis' ),
						'un_livre'      => __( 'Un livre en rayon', 'editeurs-reunis' ),
						/* translators: %s: le nombre de livres qu'une personne a dirigés */
						'n_direction'    => __( '%s sous sa direction', 'editeurs-reunis' ),
						'une_direction'  => __( 'un sous sa direction', 'editeurs-reunis' ),
						/* translators: %s: le nombre de livres qu'une personne a traduits */
						'n_traductions'  => __( '%s traductions', 'editeurs-reunis' ),
						'une_traduction' => __( 'une traduction', 'editeurs-reunis' ),
						/* translators: %s: « 2 sous sa direction et une traduction » */
						'dont'           => __( 'dont %s', 'editeurs-reunis' ),
						'et'             => __( 'et', 'editeurs-reunis' ),
						/* translators: %s: le nombre de livres qui parlent d'une personne */
						'n_sujet'        => __( '%s livres à son sujet', 'editeurs-reunis' ),
						'un_sujet'       => __( 'Un livre à son sujet', 'editeurs-reunis' ),
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
