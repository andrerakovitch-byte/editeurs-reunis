<?php
/**
 * Les œuvres collectives (25 nov. 2026) : une revue (Le Messager orthodoxe, le Вестник РХД, Православная мысль) ou un
 * ouvrage collectif (une anthologie, des actes de colloque, un catalogue d'exposition, un recueil) n'a pas d'auteur
 * seul. Il a une direction, des contributions, une édition établie, une traduction : plusieurs personnes y ont part.
 *
 * - La nature d'un livre, la méta er_nature : « revue » ou « collectif » (rien : un livre d'auteur). Une revue se
 *   reconnaît aussi à son titre, pour les numéros à venir.
 * - Le rôle « Avec des contributions de » (er_contribution, traducteurs-editeurs.php) : les auteurs d'articles ou de
 *   textes, et ceux qui y traduisent ou publient un texte.
 * - Sous le titre, à la place de l'auteur absent : « Revue, sous la direction de Nikita Struve », « Ouvrage collectif,
 *   établi par … » ; la carte d'un livre dit de même ; la fiche ajoute la ligne « Nature ».
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les natures d'une œuvre collective : clé → libellé.
 *
 * @return array<string,string>
 */
function er_natures() {
	return array(
		'revue'     => _x( 'Revue', 'nature d’un livre', 'er-librairie' ),
		'collectif' => _x( 'Ouvrage collectif', 'nature d’un livre', 'er-librairie' ),
	);
}

/**
 * Un titre de revue (les numéros d'une revue que la librairie vend) : « Le Messager orthodoxe, n° 160 »,
 * « Вестник Русского христианского движения, № 219 », « Православная мысль, выпуск 5 ».
 *
 * @param string $titre Le titre.
 */
function er_titre_de_revue( $titre ) {
	return (bool) preg_match( '/^\s*(?:Le\s+Messager\s+orthodoxe|Вестник\s+Русского\s+христианского\s+движения|Православная\s+мысль)\b/iu', html_entity_decode( (string) $titre, ENT_QUOTES, 'UTF-8' ) );
}

/**
 * La nature d'un livre : « revue », « collectif », ou rien (un livre d'auteur).
 *
 * @param int $id Le livre.
 */
function er_nature_du_livre( $id ) {
	$nature = (string) get_post_meta( $id, 'er_nature', true );
	if ( isset( er_natures()[ $nature ] ) ) {
		return $nature;
	}
	return er_titre_de_revue( get_the_title( $id ) ) ? 'revue' : '';
}

/**
 * Ce qui tient lieu d'auteur à une œuvre collective, en HTML (déjà échappé) : sa nature, puis qui la dirige, l'établit
 * ou la traduit, sinon ses premiers contributeurs. Rien pour un livre d'auteur.
 *
 * @param int  $id    Le livre.
 * @param bool $liens Des liens vers les pages des personnes (la fiche), ou du texte (la carte, dont le titre est déjà un
 *                    lien).
 */
function er_collectif_en_tete( $id, $liens = true ) {
	$nature = er_nature_du_livre( $id );
	if ( '' === $nature || ! function_exists( 'er_personnes_du_livre' ) ) {
		return '';
	}
	$noms = function ( $termes, $combien ) use ( $liens ) {
		$html = array();
		foreach ( array_slice( $termes, 0, $combien ) as $t ) {
			$lien   = $liens ? get_term_link( $t ) : '';
			$html[] = $liens && ! is_wp_error( $lien ) ? '<a href="' . esc_url( $lien ) . '">' . esc_html( $t->name ) . '</a>' : esc_html( $t->name );
		}
		return implode( ', ', $html ) . ( count( $termes ) > $combien ? ' …' : '' );
	};
	$suite = '';
	foreach (
		array(
			'er_direction'    => _x( 'sous la direction de', 'œuvre collective', 'er-librairie' ),
			'er_edition'      => _x( 'établi par', 'œuvre collective', 'er-librairie' ),
			'er_traduction'   => _x( 'traduit par', 'œuvre collective', 'er-librairie' ),
			'er_contribution' => _x( 'avec', 'œuvre collective', 'er-librairie' ),
		) as $taxonomie => $mot
	) {
		$termes = taxonomy_exists( $taxonomie ) ? er_personnes_du_livre( $id, $taxonomie ) : array();
		if ( $termes ) {
			$suite = ', ' . esc_html( $mot ) . ' ' . $noms( $termes, 'er_contribution' === $taxonomie ? 3 : 2 );
			break;
		}
	}
	return esc_html( er_natures()[ $nature ] ) . $suite;
}

// Sous le titre de la fiche : l'auteur, ou, pour une œuvre collective qui n'en a pas, sa nature et qui l'a faite.
add_filter(
	'render_block_core/post-terms',
	function ( $html, $bloc, $instance = null ) {
		$attrs = isset( $bloc['attrs'] ) ? $bloc['attrs'] : array();
		if ( 'auteur' !== ( $attrs['term'] ?? '' ) || false === strpos( (string) ( $attrs['className'] ?? '' ), 'er-fiche-auteur' ) || '' !== trim( wp_strip_all_tags( (string) $html ) ) ) {
			return $html;
		}
		$id    = $instance && isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : (int) get_the_ID();
		$ligne = $id ? er_collectif_en_tete( $id ) : '';
		return '' === $ligne ? $html : '<div class="taxonomy-auteur er-fiche-auteur er-fiche-collectif wp-block-post-terms">' . $ligne . '</div>';
	},
	10,
	3
);

// La nature, dans l'administration d'un livre : un menu, à côté des rôles.
add_action(
	'add_meta_boxes_product',
	function () {
		add_meta_box(
			'er-nature',
			__( 'Nature de l’ouvrage', 'er-librairie' ),
			function ( $post ) {
				wp_nonce_field( 'er_nature', 'er_nature_jeton' );
				$choisie = (string) get_post_meta( $post->ID, 'er_nature', true );
				echo '<p><select name="er_nature" class="widefat"><option value="">' . esc_html__( 'Un livre d’auteur', 'er-librairie' ) . '</option>';
				foreach ( er_natures() as $cle => $libelle ) {
					echo '<option value="' . esc_attr( $cle ) . '"' . selected( $choisie, $cle, false ) . '>' . esc_html( $libelle ) . '</option>';
				}
				echo '</select></p><p class="description">' . esc_html__( 'Une revue ou un ouvrage collectif n’a pas d’auteur seul : la fiche montre à la place qui le dirige, l’établit, le traduit ou y contribue.', 'er-librairie' ) . '</p>';
			},
			'product',
			'side'
		);
	}
);
add_action(
	'save_post_product',
	function ( $id ) {
		if ( ! isset( $_POST['er_nature_jeton'], $_POST['er_nature'] ) || ! wp_verify_nonce( sanitize_key( $_POST['er_nature_jeton'] ), 'er_nature' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$nature = sanitize_key( wp_unslash( $_POST['er_nature'] ) );
		if ( isset( er_natures()[ $nature ] ) ) {
			update_post_meta( $id, 'er_nature', $nature );
		} else {
			delete_post_meta( $id, 'er_nature' );
		}
	}
);
