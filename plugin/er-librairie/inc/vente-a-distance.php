<?php
/**
 * Deux obligations de la vente à distance, que WooCommerce ne remplit pas seul.
 *
 * Le bouton de commande (Code de la consommation, L. 221-14) : il dit qu'on s'engage à payer, « Commander et payer »,
 * au paiement en blocs comme au paiement classique.
 *
 * La rétractation en ligne (L. 221-21 et D. 221-5, depuis le 19 juin 2026) :
 * - un lien « Renoncer au contrat ici », toujours visible : le pied de page (inc/pied.php du thème), les commandes du
 *   compte, le détail d'une commande, les courriels de commande ;
 * - une déclaration : nom et prénom, la commande, l'adresse où recevoir l'accusé (et, si l'on ne renonce qu'à une
 *   partie, laquelle) ; puis une étape « Confirmer la rétractation » ;
 * - l'accusé de réception, par courriel, sans délai : la déclaration, sa date et son heure.
 * Ni compte exigé, ni motif demandé ; une commande introuvable (numéro perdu, commande de Wix) n'empêche rien : la
 * déclaration est gardée et transmise, la librairie fait le rapprochement. Les déclarations sont gardées (type
 * er_retractation, sous WooCommerce dans l'administration) et notées sur la commande quand on la retrouve.
 *
 * La page : [er_retractation], créée une fois (er_retractation_page). Comme la lettre (lettre.php), pas de jeton
 * (nonce) sur le formulaire : la page est en cache. La confirmation porte une empreinte de la déclaration
 * (er_retractation_empreinte), qui oblige à passer par la première étape ; cinq déclarations par heure et par adresse
 * IP, et un champ piège pour les robots.
 *
 * Reste aux conditions générales de vente : dire que la fonction existe, et où (L. 221-5, I, 7°).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/* Le bouton de commande ---------------------------------------------------------------------------------------- */

/**
 * Le libellé du bouton qui passe la commande.
 */
function er_vente_libelle_commande() {
	return __( 'Commander et payer', 'er-librairie' );
}
add_filter( 'woocommerce_order_button_text', 'er_vente_libelle_commande', 20 );

/**
 * Pose le libellé sur le bloc des actions du paiement en blocs : le script de WooCommerce lit ses réglages dans les
 * attributs data-* du HTML enregistré, que render_block_data ne touche pas.
 *
 * @param string $html    Le HTML du bloc woocommerce/checkout-actions-block.
 * @param string $libelle Le libellé.
 */
function er_vente_poser_libelle( $html, $libelle ) {
	$attribut = 'data-place-order-button-label="' . esc_attr( $libelle ) . '"';
	if ( preg_match( '/data-place-order-button-label="[^"]*"/', $html ) ) {
		return (string) preg_replace_callback( '/data-place-order-button-label="[^"]*"/', fn() => $attribut, $html, 1 );
	}
	return (string) preg_replace_callback( '/<div\b/', fn() => '<div ' . $attribut, $html, 1 );
}
add_filter(
	'render_block',
	function ( $html, $bloc ) {
		return 'woocommerce/checkout-actions-block' === ( $bloc['blockName'] ?? '' ) ? er_vente_poser_libelle( (string) $html, er_vente_libelle_commande() ) : $html;
	},
	10,
	2
);

/* La rétractation : les données ---------------------------------------------------------------------------------- */

/**
 * Lit et vérifie une déclaration.
 *
 * @param array $brut Les champs envoyés (nom, commande, courriel, precision), déjà sans barres obliques.
 * @return array{0: array, 1: string[]} La déclaration nettoyée, et les erreurs (champ => message).
 */
function er_retractation_lire( $brut ) {
	$d = array(
		'nom'       => sanitize_text_field( (string) ( $brut['nom'] ?? '' ) ),
		'commande'  => sanitize_text_field( (string) ( $brut['commande'] ?? '' ) ),
		'courriel'  => sanitize_email( (string) ( $brut['courriel'] ?? '' ) ),
		'precision' => sanitize_textarea_field( (string) ( $brut['precision'] ?? '' ) ),
	);
	foreach ( array( 'nom' => 120, 'commande' => 40, 'courriel' => 120, 'precision' => 1000 ) as $champ => $long ) {
		$d[ $champ ] = mb_substr( $d[ $champ ], 0, $long );
	}
	$erreurs = array();
	if ( '' === $d['nom'] ) {
		$erreurs['nom'] = __( 'Indiquez votre nom et votre prénom.', 'er-librairie' );
	}
	if ( '' === $d['courriel'] || ! is_email( $d['courriel'] ) ) {
		$erreurs['courriel'] = __( 'Indiquez une adresse électronique valide : l’accusé de réception y sera envoyé.', 'er-librairie' );
	}
	// Sans numéro, la commande se décrit : une déclaration n'est jamais refusée faute de numéro.
	if ( '' === $d['commande'] && '' === $d['precision'] ) {
		$erreurs['commande'] = __( 'Indiquez le numéro de la commande ; sans lui, décrivez-la plus bas (sa date, les livres).', 'er-librairie' );
	}
	return array( $d, $erreurs );
}

/**
 * L'empreinte d'une déclaration : la confirmation la renvoie, elle prouve qu'on est passé par la première étape et que
 * rien n'a changé depuis.
 *
 * @param array $d La déclaration.
 */
function er_retractation_empreinte( $d ) {
	return wp_hash( 'er_retractation|' . implode( '|', array( $d['nom'], $d['commande'], $d['courriel'], $d['precision'] ) ) );
}

/**
 * Le texte de l'accusé de réception : la déclaration, sa date et son heure (D. 221-5).
 *
 * @param array  $d     La déclaration.
 * @param string $quand La date et l'heure de l'envoi, déjà écrites (« 28 novembre 2026 à 14 h 05 »).
 */
function er_retractation_texte( $d, $quand ) {
	$commande = '' !== $d['commande'] ? $d['commande'] : __( 'non précisée', 'er-librairie' );
	$lignes   = array(
		/* translators: %s: la date et l'heure */
		sprintf( __( 'Nous avons bien reçu votre déclaration de rétractation, envoyée le %s.', 'er-librairie' ), $quand ),
		'',
		/* translators: %s: le numéro de la commande */
		sprintf( __( 'Déclaration : je renonce au contrat de vente de la commande n° %s.', 'er-librairie' ), $commande ),
		/* translators: %s: le nom et le prénom */
		sprintf( __( 'Nom et prénom : %s', 'er-librairie' ), $d['nom'] ),
	);
	if ( '' !== $d['precision'] ) {
		/* translators: %s: ce que la personne a précisé */
		$lignes[] = sprintf( __( 'Précision : %s', 'er-librairie' ), $d['precision'] );
	}
	/* translators: %s: l'adresse électronique */
	$lignes[] = sprintf( __( 'Adresse pour cet accusé : %s', 'er-librairie' ), $d['courriel'] );
	$lignes[] = '';
	$lignes[] = __( 'Nous revenons vers vous pour le retour des livres et le remboursement.', 'er-librairie' );
	return implode( "\n", $lignes );
}

/**
 * La date et l'heure, écrites (à l'heure du site).
 *
 * @param int $t L'instant.
 */
function er_retractation_quand( $t ) {
	/* translators: format de date de PHP pour « 28 novembre 2026 à 14 h 05 » */
	return wp_date( __( 'j F Y à G \h i', 'er-librairie' ), $t );
}

/**
 * La commande d'une déclaration, si on la retrouve : par sa clé (le lien du compte ou du courriel), ou par son numéro
 * et l'adresse de facturation.
 *
 * @param array  $d   La déclaration.
 * @param string $cle La clé de la commande (order_key), s'il y en a une.
 * @return WC_Order|null
 */
function er_retractation_commande( $d, $cle = '' ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return null;
	}
	$id       = '' !== $cle ? (int) wc_get_order_id_by_order_key( $cle ) : 0;
	$commande = $id ? wc_get_order( $id ) : null;
	if ( $commande && hash_equals( (string) $commande->get_order_key(), $cle ) ) {
		return $commande;
	}
	$numero   = (int) preg_replace( '/\D/', '', $d['commande'] );
	$commande = $numero ? wc_get_order( $numero ) : null;
	if ( $commande instanceof WC_Order && 0 === strcasecmp( (string) $commande->get_billing_email(), $d['courriel'] ) ) {
		return $commande;
	}
	return null;
}

/**
 * Envoie un courriel, aux couleurs de WooCommerce quand il est là.
 *
 * @param string $a      Le destinataire.
 * @param string $sujet  Le sujet.
 * @param string $titre  Le titre dans le courriel.
 * @param string $texte  Le texte (lignes simples).
 * @param string $repondre L'adresse de réponse, s'il y en a une.
 */
function er_retractation_envoyer( $a, $sujet, $titre, $texte, $repondre = '' ) {
	$entetes = $repondre ? array( 'Reply-To: ' . $repondre ) : array();
	if ( function_exists( 'WC' ) && WC()->mailer() ) {
		$courrier = WC()->mailer();
		$html     = $courrier->wrap_message( $titre, wpautop( esc_html( $texte ) ) );
		$entetes[] = 'Content-Type: text/html; charset=UTF-8';
		return $courrier->send( $a, $sujet, $html, implode( "\r\n", $entetes ) . "\r\n" );
	}
	return wp_mail( $a, $sujet, $texte, $entetes );
}

/**
 * Enregistre une déclaration confirmée : la garde, la note sur la commande, envoie l'accusé et prévient la librairie.
 *
 * @param array  $d   La déclaration.
 * @param string $cle La clé de la commande, s'il y en a une.
 * @return string La date et l'heure de l'envoi, écrites.
 */
function er_retractation_enregistrer( $d, $cle = '' ) {
	$t        = time();
	$quand    = er_retractation_quand( $t );
	$commande = er_retractation_commande( $d, $cle );
	if ( $commande ) {
		$d['commande'] = (string) $commande->get_order_number();
	}
	$texte = er_retractation_texte( $d, $quand );
	wp_insert_post(
		array(
			'post_type'    => 'er_retractation',
			'post_status'  => 'publish',
			'post_title'   => sprintf( '%s · %s', '' !== $d['commande'] ? $d['commande'] : __( 'commande non précisée', 'er-librairie' ), $d['nom'] ),
			'post_content' => $texte,
			'meta_input'   => array(
				'er_courriel' => $d['courriel'],
				'er_commande' => $commande ? $commande->get_id() : 0,
				'er_quand'    => gmdate( 'c', $t ),
			),
		)
	);
	if ( $commande ) {
		/* translators: %s: la date et l'heure */
		$commande->add_order_note( sprintf( __( 'Rétractation déclarée en ligne le %s (Rétractations, dans WooCommerce).', 'er-librairie' ), $quand ) );
		$commande->update_meta_data( 'er_retractation', gmdate( 'c', $t ) );
		$commande->save();
	}

	er_retractation_envoyer( $d['courriel'], __( 'Accusé de réception de votre rétractation', 'er-librairie' ), __( 'Votre rétractation', 'er-librairie' ), $texte );
	$librairie = (string) apply_filters( 'er_retractation_destinataire', get_option( 'woocommerce_email_from_address' ) ? get_option( 'woocommerce_email_from_address' ) : get_option( 'admin_email' ) );
	$note      = $commande ? '' : "\n\n" . __( 'Commande non retrouvée par son numéro et son adresse : à rapprocher à la main.', 'er-librairie' );
	/* translators: %s: le numéro de la commande */
	er_retractation_envoyer( $librairie, sprintf( __( 'Rétractation : commande %s', 'er-librairie' ), '' !== $d['commande'] ? $d['commande'] : '?' ), __( 'Une rétractation', 'er-librairie' ), $texte . $note, $d['courriel'] );
	return $quand;
}

/* La rétractation : la page ------------------------------------------------------------------------------------- */

/**
 * La page de la rétractation : son identifiant, la page créée une fois (sous un verrou) si elle manque.
 */
function er_retractation_page() {
	$id = (int) get_option( 'er_retractation_page' );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return $id;
	}
	$page = get_page_by_path( 'retractation' );
	if ( $page ) {
		update_option( 'er_retractation_page', $page->ID );
		return (int) $page->ID;
	}
	if ( (int) get_option( 'er_retractation_verrou' ) < time() - 60 ) {
		delete_option( 'er_retractation_verrou' ); // Un verrou resté d'une requête morte.
	}
	if ( ! add_option( 'er_retractation_verrou', time(), '', false ) ) {
		return 0;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => 'retractation',
			'post_title'   => 'Rétractation',
			'post_content' => "<!-- wp:shortcode -->\n[er_retractation]\n<!-- /wp:shortcode -->",
		)
	);
	delete_option( 'er_retractation_verrou' );
	if ( $id && ! is_wp_error( $id ) ) {
		// Polylang ne montre pas une page sans langue : celle du site.
		if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_default_language' ) ) {
			pll_set_post_language( $id, pll_default_language() );
		}
		update_option( 'er_retractation_page', (int) $id );
		return (int) $id;
	}
	return 0;
}

/**
 * L'adresse de la page ; avec une commande, la déclaration arrive déjà remplie.
 *
 * @param WC_Order|null $commande La commande.
 */
function er_retractation_url( $commande = null ) {
	$id = (int) get_option( 'er_retractation_page' );
	// La page dans la langue de la page qu'on regarde, si elle est traduite.
	if ( $id && function_exists( 'pll_get_post' ) && pll_get_post( $id ) ) {
		$id = (int) pll_get_post( $id );
	}
	$url = $id ? get_permalink( $id ) : home_url( '/retractation/' );
	if ( $commande instanceof WC_Order ) {
		$url = add_query_arg(
			array(
				'commande' => rawurlencode( (string) $commande->get_order_number() ),
				'cle'      => rawurlencode( (string) $commande->get_order_key() ),
			),
			$url
		);
	}
	return $url;
}

/**
 * Le libellé du lien, celui que le décret donne en exemple.
 */
function er_retractation_libelle() {
	return __( 'Renoncer au contrat ici', 'er-librairie' );
}

/**
 * Un champ du formulaire.
 *
 * @param string $nom     Son nom.
 * @param string $libelle Son libellé.
 * @param string $valeur  Sa valeur.
 * @param array  $erreurs Les erreurs.
 * @param string $type    text, email ou textarea.
 * @param string $aide    Une ligne d'aide, s'il y en a une.
 */
function er_retractation_champ( $nom, $libelle, $valeur, $erreurs, $type = 'text', $aide = '' ) {
	$id    = 'er-retr-' . $nom;
	$aides = array();
	$html  = '<p class="er-retr-ligne"><label class="er-retr-libelle" for="' . esc_attr( $id ) . '">' . esc_html( $libelle ) . '</label>';
	if ( $aide ) {
		$aides[] = $id . '-aide';
	}
	if ( isset( $erreurs[ $nom ] ) ) {
		$aides[] = $id . '-erreur';
	}
	$attr = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" class="er-retr-champ"' . ( $aides ? ' aria-describedby="' . esc_attr( implode( ' ', $aides ) ) . '"' : '' ) . ( isset( $erreurs[ $nom ] ) ? ' aria-invalid="true"' : '' );
	if ( 'textarea' === $type ) {
		$html .= '<textarea' . $attr . ' rows="3">' . esc_textarea( $valeur ) . '</textarea>';
	} else {
		$auto  = array(
			'nom'      => 'name',
			'courriel' => 'email',
		);
		$html .= '<input type="' . esc_attr( $type ) . '"' . $attr . ' value="' . esc_attr( $valeur ) . '"' . ( isset( $auto[ $nom ] ) ? ' autocomplete="' . esc_attr( $auto[ $nom ] ) . '"' : '' ) . '>';
	}
	if ( $aide ) {
		$html .= '<span class="er-retr-aide" id="' . esc_attr( $id ) . '-aide">' . esc_html( $aide ) . '</span>';
	}
	if ( isset( $erreurs[ $nom ] ) ) {
		$html .= '<span class="er-retr-erreur" id="' . esc_attr( $id ) . '-erreur">' . esc_html( $erreurs[ $nom ] ) . '</span>';
	}
	return $html . '</p>';
}

/**
 * Les champs cachés qui portent une déclaration d'une étape à l'autre.
 *
 * @param array  $d   La déclaration.
 * @param string $cle La clé de la commande.
 */
function er_retractation_caches( $d, $cle ) {
	$html = '';
	foreach ( array( 'nom', 'commande', 'courriel', 'precision' ) as $champ ) {
		$html .= '<input type="hidden" name="' . esc_attr( $champ ) . '" value="' . esc_attr( $d[ $champ ] ) . '">';
	}
	return $html . '<input type="hidden" name="cle" value="' . esc_attr( $cle ) . '">';
}

/**
 * Le formulaire, à ses trois étapes : la déclaration, la confirmation, l'accusé.
 */
function er_retractation_formulaire() {
	// phpcs:disable WordPress.Security.NonceVerification -- voir l'en-tête : pas de jeton sur une page en cache ; l'empreinte tient la confirmation.
	$etape = isset( $_POST['er_retr'] ) ? sanitize_key( wp_unslash( $_POST['er_retr'] ) ) : '';
	$cle   = isset( $_REQUEST['cle'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cle'] ) ) : '';
	$brut  = array();
	foreach ( array( 'nom', 'commande', 'courriel', 'precision' ) as $champ ) {
		$brut[ $champ ] = isset( $_POST[ $champ ] ) ? wp_unslash( $_POST[ $champ ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé par er_retractation_lire.
	}
	$piege     = ! empty( $_POST['site'] );
	$empreinte = isset( $_POST['empreinte'] ) ? sanitize_text_field( wp_unslash( $_POST['empreinte'] ) ) : '';
	$numero    = isset( $_GET['commande'] ) ? sanitize_text_field( wp_unslash( $_GET['commande'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification
	$action = esc_url( remove_query_arg( array( 'commande', 'cle' ) ) );

	list( $d, $erreurs ) = er_retractation_lire( $brut );

	// La confirmation : on enregistre, on montre l'accusé.
	if ( 'confirmer' === $etape && ! $erreurs && hash_equals( er_retractation_empreinte( $d ), $empreinte ) ) {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$garde = 'er_retractation_ip_' . md5( $ip );
		$fois  = (int) get_transient( $garde );
		set_transient( $garde, $fois + 1, HOUR_IN_SECONDS );
		if ( $piege || $fois >= 5 ) {
			return '<div class="er-retr"><p>' . esc_html__( 'Votre déclaration n’a pas pu être enregistrée. Écrivez-nous à les-editeurs-reunis@orange.fr : une déclaration par courriel vaut aussi.', 'er-librairie' ) . '</p></div>';
		}
		$quand = er_retractation_enregistrer( $d, $cle );
		return '<div class="er-retr er-retr-fait" role="status"><h2 class="er-retr-titre">' . esc_html__( 'Votre rétractation est enregistrée', 'er-librairie' ) . '</h2>'
			/* translators: 1: la date et l'heure, 2: l'adresse électronique */
			. '<p>' . esc_html( sprintf( __( 'Nous l’avons reçue le %1$s. Un accusé de réception part à l’instant à %2$s.', 'er-librairie' ), $quand, $d['courriel'] ) ) . '</p>'
			. '<p>' . esc_html__( 'Nous revenons vers vous pour le retour des livres et le remboursement.', 'er-librairie' ) . '</p></div>';
	}

	// La déclaration lue et juste : on la montre, avec « Confirmer la rétractation ».
	if ( 'declarer' === $etape && ! $erreurs && ! $piege ) {
		$commande = '' !== $d['commande'] ? $d['commande'] : __( 'non précisée', 'er-librairie' );
		$html     = '<div class="er-retr"><h2 class="er-retr-titre">' . esc_html__( 'Vérifiez votre déclaration', 'er-librairie' ) . '</h2>'
			/* translators: %s: le numéro de la commande */
			. '<p class="er-retr-declaration">' . esc_html( sprintf( __( 'Je renonce au contrat de vente de la commande n° %s.', 'er-librairie' ), $commande ) ) . '</p>'
			. '<dl class="er-retr-recap"><dt>' . esc_html__( 'Nom et prénom', 'er-librairie' ) . '</dt><dd>' . esc_html( $d['nom'] ) . '</dd>'
			. ( '' !== $d['precision'] ? '<dt>' . esc_html__( 'Précision', 'er-librairie' ) . '</dt><dd>' . nl2br( esc_html( $d['precision'] ) ) . '</dd>' : '' )
			. '<dt>' . esc_html__( 'L’accusé de réception part à', 'er-librairie' ) . '</dt><dd>' . esc_html( $d['courriel'] ) . '</dd></dl>'
			. '<form method="post" action="' . $action . '" class="er-retr-form">' . er_retractation_caches( $d, $cle )
			. '<input type="hidden" name="empreinte" value="' . esc_attr( er_retractation_empreinte( $d ) ) . '">'
			. '<p class="er-retr-boutons"><button type="submit" name="er_retr" value="confirmer" class="er-retr-bouton">' . esc_html__( 'Confirmer la rétractation', 'er-librairie' ) . '</button>'
			. ' <button type="submit" name="er_retr" value="corriger" class="er-retr-lien">' . esc_html__( 'Corriger', 'er-librairie' ) . '</button></p></form></div>';
		return $html;
	}

	// La déclaration : vide, remplie depuis le compte ou le courriel, ou à corriger.
	if ( '' === $etape ) {
		$erreurs  = array();
		$commande = '' !== $cle ? er_retractation_commande( array_merge( $d, array( 'commande' => $numero ) ), $cle ) : null;
		if ( $commande ) {
			$d['commande'] = (string) $commande->get_order_number();
			$d['nom']      = trim( $commande->get_billing_first_name() . ' ' . $commande->get_billing_last_name() );
			$d['courriel'] = (string) $commande->get_billing_email();
		} elseif ( is_user_logged_in() ) {
			$moi           = wp_get_current_user();
			$d['nom']      = trim( $moi->first_name . ' ' . $moi->last_name );
			$d['courriel'] = (string) $moi->user_email;
		}
	} elseif ( 'corriger' === $etape ) {
		$erreurs = array();
	}
	return '<div class="er-retr">'
		. '<p class="er-retr-intro">' . esc_html__( 'Vous avez quatorze jours après avoir reçu votre commande pour y renoncer, sans avoir à vous justifier. Remplissez ce formulaire, puis confirmez : un accusé de réception vous est aussitôt envoyé.', 'er-librairie' ) . '</p>'
		. '<form method="post" action="' . $action . '" class="er-retr-form" novalidate>'
		. er_retractation_champ( 'nom', __( 'Nom et prénom', 'er-librairie' ), $d['nom'], $erreurs )
		. er_retractation_champ( 'commande', __( 'Numéro de la commande', 'er-librairie' ), $d['commande'], $erreurs, 'text', __( 'Il figure sur le courriel de confirmation de la commande.', 'er-librairie' ) )
		. er_retractation_champ( 'courriel', __( 'Adresse électronique où recevoir l’accusé de réception', 'er-librairie' ), $d['courriel'], $erreurs, 'email' )
		. er_retractation_champ( 'precision', __( 'Si vous ne renoncez qu’à une partie de la commande, ou sans numéro de commande : précisez (facultatif)', 'er-librairie' ), $d['precision'], $erreurs, 'textarea' )
		. '<p class="er-retr-piege" aria-hidden="true"><label>Site <input type="text" name="site" tabindex="-1" autocomplete="off"></label></p>'
		. '<input type="hidden" name="cle" value="' . esc_attr( $cle ) . '">'
		. '<p class="er-retr-boutons"><button type="submit" name="er_retr" value="declarer" class="er-retr-bouton">' . esc_html__( 'Continuer', 'er-librairie' ) . '</button></p>'
		. '</form></div>';
}

/* Les crochets ------------------------------------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		register_post_type(
			'er_retractation',
			array(
				'labels'          => array(
					'name'          => __( 'Rétractations', 'er-librairie' ),
					'singular_name' => __( 'Rétractation', 'er-librairie' ),
					'menu_name'     => __( 'Rétractations', 'er-librairie' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'woocommerce',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'shop_order',
				'map_meta_cap'    => true,
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			)
		);
		add_shortcode( 'er_retractation', 'er_retractation_formulaire' );
		if ( ! get_option( 'er_retractation_page' ) ) {
			er_retractation_page();
		}
	},
	20
);

// Une page qui porte des données personnelles (remplie, ou une étape envoyée) ne se met pas en cache ; la page
// elle-même reste hors des moteurs.
/**
 * La page qu'on regarde porte-t-elle le formulaire (en français ou dans une traduction) ?
 */
function er_retractation_est_la_page() {
	return is_page() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'er_retractation' );
}
add_action(
	'template_redirect',
	function () {
		if ( er_retractation_est_la_page() && ( ! empty( $_POST ) || isset( $_GET['cle'] ) || is_user_logged_in() ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- on ne lit rien, on regarde seulement s'il y a quelque chose.
			nocache_headers();
		}
	}
);
add_filter(
	'wp_robots',
	fn( $robots ) => er_retractation_est_la_page() ? array_merge( $robots, array( 'noindex' => true ) ) : $robots
);

// Les commandes du compte : « Renoncer au contrat ici » sur les commandes des soixante derniers jours.
add_filter(
	'woocommerce_my_account_my_orders_actions',
	function ( $actions, $commande ) {
		$creee = $commande->get_date_created();
		if ( $commande->has_status( array( 'processing', 'completed', 'on-hold' ) ) && $creee && $creee->getTimestamp() > time() - 60 * DAY_IN_SECONDS ) {
			$actions['er-retractation'] = array(
				'url'  => er_retractation_url( $commande ),
				'name' => er_retractation_libelle(),
			);
		}
		return $actions;
	},
	10,
	2
);

// Le détail d'une commande (le compte, la page de remerciement).
add_action(
	'woocommerce_order_details_after_order_table',
	function ( $commande ) {
		if ( $commande instanceof WC_Order && ! $commande->has_status( array( 'cancelled', 'refunded', 'failed' ) ) ) {
			echo '<p class="er-retr-rappel">' . esc_html__( 'Vous pouvez renoncer à cette commande pendant quatorze jours après l’avoir reçue :', 'er-librairie' ) . ' <a href="' . esc_url( er_retractation_url( $commande ) ) . '">' . esc_html( er_retractation_libelle() ) . '</a></p>';
		}
	}
);

// Les courriels de commande envoyés au client.
add_action(
	'woocommerce_email_after_order_table',
	function ( $commande, $pour_la_librairie, $texte_brut, $courriel = null ) {
		$ids = array( 'customer_processing_order', 'customer_completed_order', 'customer_on_hold_order' );
		if ( $pour_la_librairie || ! $commande instanceof WC_Order || ( is_object( $courriel ) && ! in_array( $courriel->id, $ids, true ) ) ) {
			return;
		}
		$phrase = __( 'Vous pouvez renoncer à cette commande pendant quatorze jours après l’avoir reçue :', 'er-librairie' );
		if ( $texte_brut ) {
			echo esc_html( $phrase . ' ' . er_retractation_libelle() ) . "\n" . esc_url_raw( er_retractation_url( $commande ) ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- courriel en texte brut : l'adresse nettoyée, sans entités HTML.
		} else {
			echo '<p>' . esc_html( $phrase ) . ' <a href="' . esc_url( er_retractation_url( $commande ) ) . '">' . esc_html( er_retractation_libelle() ) . '</a></p>';
		}
	},
	20,
	4
);
