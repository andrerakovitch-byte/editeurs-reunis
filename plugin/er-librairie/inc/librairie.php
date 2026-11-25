<?php
/**
 * Le relevé de la librairie, pour l'audit de son organisation (24 nov. 2026) : ce que chaque livre porte (rayons,
 * sujets, langues, mentions, époques) et ce que dit sa fiche.
 *
 * - wp er librairie arbre : les rayons et sous-rayons avec leurs comptes, puis les langues, les sujets, les mentions,
 *   les décennies d'édition, et ce qui manque (ISBN, pages, année, rayon).
 * - wp er librairie fiches [<début>] [<nombre>] : une ligne par livre, ce que montre sa page publique (titre, auteurs,
 *   étiquettes, année, pages, le début de la présentation). Ni prix d'achat, ni quantité, ni vente.
 * - wp er librairie exporter : les mêmes fiches en JSON, pour le workflow des notices (il ne les écrit nulle part dans
 *   le dépôt).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les livres de la librairie (pas les billets), publiés ou en brouillon, du plus ancien au plus récent.
 *
 * @return int[]
 */
function er_librairie_ids() {
	$ids = get_posts(
		array(
			'post_type'        => 'product',
			'post_status'      => array( 'publish', 'draft', 'private' ),
			'numberposts'      => -1,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'lang'             => '',
			'suppress_filters' => false,
		)
	);
	update_object_term_cache( $ids, 'product' );
	update_postmeta_cache( $ids );
	return array_values( array_filter( $ids, fn( $id ) => ! get_post_meta( $id, 'er_evenement', true ) ) );
}

/**
 * Les termes d'un livre dans un classement : leurs slugs.
 *
 * @param int    $id        Le livre.
 * @param string $taxonomie Le classement.
 */
function er_librairie_slugs( $id, $taxonomie ) {
	if ( ! taxonomy_exists( $taxonomie ) ) {
		return array();
	}
	$t = wp_get_post_terms( $id, $taxonomie, array( 'fields' => 'slugs' ) );
	return is_wp_error( $t ) ? array() : $t;
}

/**
 * La fiche d'un livre, telle que sa page la montre.
 *
 * @param int $id Le livre.
 */
function er_librairie_fiche( $id ) {
	$p       = wc_get_product( $id );
	$auteurs = wp_get_post_terms( $id, 'auteur', array( 'fields' => 'names' ) );
	$editeur = er_editeur_du_livre( $id );
	$texte   = $p->get_description() ? $p->get_description() : $p->get_short_description();
	$roles   = array();
	foreach ( array_keys( er_roles_personnes() ) as $taxonomie ) {
		$noms = wp_get_post_terms( $id, $taxonomie, array( 'fields' => 'names' ) );
		if ( $noms && ! is_wp_error( $noms ) ) {
			$roles[ substr( $taxonomie, 3 ) ] = $noms;
		}
	}
	return array(
		'id'         => $id,
		'ref'        => $p->get_sku(),
		'statut'     => $p->get_status(),
		'titre'      => html_entity_decode( $p->get_name(), ENT_QUOTES, 'UTF-8' ),
		'auteurs'    => is_wp_error( $auteurs ) ? array() : array_map( fn( $n ) => html_entity_decode( $n, ENT_QUOTES, 'UTF-8' ), $auteurs ),
		'roles'      => $roles,
		'editeur'    => $editeur['termes'] ? html_entity_decode( $editeur['termes'][0]->name, ENT_QUOTES, 'UTF-8' ) : trim( wp_strip_all_tags( $editeur['texte'] ) ),
		'collection' => trim( (string) er_collection_du_livre( $id )['texte'] ),
		'annee'      => trim( (string) $p->get_meta( 'er_annee' ) ),
		'pages'      => (int) $p->get_meta( 'er_pages' ),
		'isbn'       => er_isbn( $p ),
		'en_stock'   => $p->is_in_stock(),
		'langues'    => er_librairie_slugs( $id, 'langue' ),
		'rayons'     => er_librairie_slugs( $id, 'product_cat' ),
		'sujets'     => er_librairie_slugs( $id, 'er_sujet' ),
		'mentions'   => er_librairie_slugs( $id, 'product_tag' ),
		'ecriture'   => er_librairie_slugs( $id, 'er_ecriture' ),
		'periode'    => er_librairie_slugs( $id, 'er_periode' ),
		'texte'      => trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $texte ), ENT_QUOTES, 'UTF-8' ) ) ),
		'nature'     => function_exists( 'er_nature_du_livre' ) ? er_nature_du_livre( $id ) : '',
	);
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Une ligne de compte : le libellé, puis le nombre.
	 *
	 * @param string $libelle Le libellé.
	 * @param mixed  $valeur  Le nombre, ou un texte.
	 */
	function er_librairie_ligne( $libelle, $valeur ) {
		WP_CLI::log( '  ' . $libelle . str_repeat( ' ', max( 1, 52 - mb_strlen( $libelle ) ) ) . $valeur );
	}

	/**
	 * Les rayons, les étiquettes et ce qui manque : wp er librairie arbre.
	 */
	WP_CLI::add_command(
		'er librairie arbre',
		function () {
			$ids     = er_librairie_ids();
			$fiches  = array_map( 'er_librairie_fiche', $ids );
			$publies = array_filter( $fiches, fn( $f ) => 'publish' === $f['statut'] );
			WP_CLI::log( sprintf( '%d livres (%d publiés, %d en stock).', count( $fiches ), count( $publies ), count( array_filter( $publies, fn( $f ) => $f['en_stock'] ) ) ) );

			WP_CLI::log( 'Les rayons (livres publiés ; entre parenthèses, ceux qui ne sont que dans le rayon, sans sous-rayon)' );
			$par_rayon = array();
			foreach ( $publies as $f ) {
				foreach ( $f['rayons'] as $r ) {
					$par_rayon[ $r ] = ( $par_rayon[ $r ] ?? 0 ) + 1;
				}
			}
			$dans_l_ordre = function ( $parent ) {
				$t = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $parent, 'hide_empty' => false ) );
				$t = is_wp_error( $t ) ? array() : $t;
				usort( $t, fn( $a, $b ) => (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true ) );
				return $t;
			};
			foreach ( $dans_l_ordre( 0 ) as $rayon ) {
				$enfants = $dans_l_ordre( $rayon->term_id );
				$slugs   = wp_list_pluck( $enfants, 'slug' );
				$seuls   = count( array_filter( $publies, fn( $f ) => in_array( $rayon->slug, $f['rayons'], true ) && ! array_intersect( $slugs, $f['rayons'] ) ) );
				$tous    = count( array_filter( $publies, fn( $f ) => in_array( $rayon->slug, $f['rayons'], true ) || array_intersect( $slugs, $f['rayons'] ) ) );
				er_librairie_ligne( $rayon->name . ' [' . $rayon->slug . ']', $tous . ' (' . $seuls . ')' );
				$groupe = null;
				foreach ( $enfants as $e ) {
					$g = (string) get_term_meta( $e->term_id, 'er_groupe', true );
					if ( $g !== $groupe ) {
						WP_CLI::log( '      ' . ( '' !== $g ? $g : '—' ) );
						$groupe = $g;
					}
					er_librairie_ligne( '        ' . $e->name . ' [' . $e->slug . ']', $par_rayon[ $e->slug ] ?? 0 );
				}
			}
			$sans = count( array_filter( $publies, fn( $f ) => ! array_diff( $f['rayons'], array( 'sans-rayon', 'uncategorized' ) ) ) );
			er_librairie_ligne( 'Sans rayon', $sans );
			$plusieurs = count( array_filter( $publies, fn( $f ) => count( array_diff( $f['rayons'], array( 'sans-rayon' ) ) ) > 2 ) );
			er_librairie_ligne( 'Dans plus d’un rayon et sous-rayon', $plusieurs );

			foreach ( array(
				'langues'  => 'Les langues',
				'sujets'   => 'Les sujets',
				'mentions' => 'Les mentions',
				'ecriture' => 'L’époque d’écriture',
				'periode'  => 'L’époque traitée',
			) as $cle => $titre ) {
				WP_CLI::log( $titre );
				$c = array();
				foreach ( $publies as $f ) {
					foreach ( $f[ $cle ] as $s ) {
						$c[ $s ] = ( $c[ $s ] ?? 0 ) + 1;
					}
					if ( ! $f[ $cle ] ) {
						$c['(aucun)'] = ( $c['(aucun)'] ?? 0 ) + 1;
					}
				}
				arsort( $c );
				foreach ( $c as $s => $n ) {
					er_librairie_ligne( '  ' . $s, $n );
				}
			}

			WP_CLI::log( 'Par livre publié' );
			$moyenne = fn( $cle ) => count( $publies ) ? round( array_sum( array_map( fn( $f ) => count( $f[ $cle ] ), $publies ) ) / count( $publies ), 2 ) : 0;
			er_librairie_ligne( 'Rayons et sous-rayons, en moyenne', $moyenne( 'rayons' ) );
			er_librairie_ligne( 'Sujets, en moyenne', $moyenne( 'sujets' ) );
			er_librairie_ligne( 'Avec un ISBN', count( array_filter( $publies, fn( $f ) => '' !== $f['isbn'] ) ) );
			er_librairie_ligne( 'Avec le nombre de pages', count( array_filter( $publies, fn( $f ) => $f['pages'] > 0 ) ) );
			er_librairie_ligne( 'Avec l’année d’édition', count( array_filter( $publies, fn( $f ) => preg_match( '/\d{4}/', $f['annee'] ) ) ) );
			er_librairie_ligne( 'Avec un éditeur', count( array_filter( $publies, fn( $f ) => '' !== $f['editeur'] ) ) );
			er_librairie_ligne( 'Avec une présentation de plus de 200 signes', count( array_filter( $publies, fn( $f ) => mb_strlen( $f['texte'] ) > 200 ) ) );
			er_librairie_ligne( 'Sans auteur', count( array_filter( $publies, fn( $f ) => ! $f['auteurs'] ) ) );
			er_librairie_ligne( 'Calendriers et objets (ni ISBN ni pages)', count( array_filter( $publies, fn( $f ) => er_nature_hors_livre( $f['nature'] ) ) ) );

			WP_CLI::log( 'Les décennies d’édition' );
			$dec = array();
			foreach ( $publies as $f ) {
				$d         = preg_match( '/(1[89]\d|20[0-2])\d/', $f['annee'], $m ) ? $m[1] . '0' : '(inconnue)';
				$dec[ $d ] = ( $dec[ $d ] ?? 0 ) + 1;
			}
			ksort( $dec );
			foreach ( $dec as $d => $n ) {
				er_librairie_ligne( '  ' . $d, $n );
			}
		}
	);

	/**
	 * Une ligne par livre : wp er librairie fiches [<début>] [<nombre>].
	 *
	 * @param array $args Le rang du premier livre, et combien.
	 */
	WP_CLI::add_command(
		'er librairie fiches',
		function ( $args ) {
			$ids = array_slice( er_librairie_ids(), (int) ( $args[0] ?? 0 ), (int) ( $args[1] ?? 2000 ) );
			foreach ( $ids as $id ) {
				$f = er_librairie_fiche( $id );
				WP_CLI::log(
					sprintf(
						'#%d %s | %s | %s | %s | %sp | %s | L:%s | R:%s | S:%s | M:%s | %s',
						$f['id'],
						$f['titre'],
						implode( ', ', $f['auteurs'] ),
						$f['editeur'],
						$f['annee'],
						$f['pages'] ? $f['pages'] : '?',
						$f['en_stock'] ? 'stock' : 'épuisé',
						implode( ',', $f['langues'] ),
						implode( ',', $f['rayons'] ),
						implode( ',', $f['sujets'] ),
						implode( ',', $f['mentions'] ),
						mb_substr( $f['texte'], 0, 420 )
					)
				);
			}
		}
	);

	/**
	 * Le texte entier de chaque livre, en lignes courtes (« #id.1 », « #id.2 »…) que le journal d'un workflow garde,
	 * avec ses rôles posés : wp er librairie textes [<début>] [<nombre>]. Pour relire les sommaires des revues et des
	 * recueils, que « fiches » coupe à 420 signes et que le JSON d'« exporter », sur une seule ligne, perd.
	 *
	 * @param array $args Le rang du premier livre, et combien.
	 */
	WP_CLI::add_command(
		'er librairie textes',
		function ( $args ) {
			$ids = array_slice( er_librairie_ids(), (int) ( $args[0] ?? 0 ), (int) ( $args[1] ?? 2000 ) );
			foreach ( $ids as $id ) {
				$f     = er_librairie_fiche( $id );
				$roles = array();
				foreach ( $f['roles'] as $role => $noms ) {
					$roles[] = $role . ' : ' . implode( ', ', $noms );
				}
				WP_CLI::log( '#' . $f['id'] . '.0 ' . $f['titre'] . ' | ' . implode( ', ', $f['auteurs'] ) . ' | ' . implode( ' ; ', $roles ) );
				foreach ( mb_str_split( $f['texte'], 900, 'UTF-8' ) as $k => $morceau ) {
					WP_CLI::log( '#' . $f['id'] . '.' . ( $k + 1 ) . ' ' . $morceau );
				}
			}
		}
	);

	/**
	 * Les indices d'édition des fiches à qui manquent l'éditeur, l'année et les pages (le « noyau dur ») : la
	 * référence, le prix, l'ISBN, le nom du fichier de la couverture, la collection et les rôles, de quoi reconnaître
	 * l'édition vendue parmi celles que donnent les catalogues : wp er librairie indices [tous].
	 *
	 * @param array $args « tous » : toutes les fiches, pas seulement le noyau dur.
	 */
	WP_CLI::add_command(
		'er librairie indices',
		function ( $args ) {
			$tous = in_array( 'tous', $args, true );
			foreach ( er_librairie_ids() as $id ) {
				$f = er_librairie_fiche( $id );
				if ( ! $tous && ( '' !== $f['editeur'] || preg_match( '/\d{4}/', $f['annee'] ) || $f['pages'] ) ) {
					continue;
				}
				$p       = wc_get_product( $id );
				$fichier = $p->get_image_id() ? wp_basename( (string) get_post_meta( $p->get_image_id(), '_wp_attached_file', true ) ) : '';
				$roles   = array();
				foreach ( $f['roles'] as $role => $noms ) {
					$roles[] = $role . ':' . implode( ',', $noms );
				}
				WP_CLI::log(
					sprintf(
						'IND #%d | ref:%s | prix:%s | isbn:%s | img:%s | coll:%s | %s',
						$id,
						$f['ref'],
						$p->get_regular_price(),
						$f['isbn'],
						$fichier,
						$f['collection'],
						implode( ' ; ', $roles )
					)
				);
			}
		}
	);

	/**
	 * Les fiches sans ISBN, une par ligne : année, éditeur, pages, nature, stock, langues, prix, titre, auteurs et
	 * traducteurs, de quoi chercher leur ISBN : wp er librairie sans-isbn. Sans les calendriers ni les objets, qui n'en
	 * ont pas.
	 */
	WP_CLI::add_command(
		'er librairie sans-isbn',
		function () {
			foreach ( er_librairie_ids() as $id ) {
				$f = er_librairie_fiche( $id );
				if ( '' !== $f['isbn'] || er_nature_hors_livre( $f['nature'] ) ) {
					continue;
				}
				WP_CLI::log(
					sprintf(
						'SANS #%d | %s | %s | %d | %s | %s | %s | %s | %s | %s | %s',
						$id,
						$f['annee'],
						$f['editeur'],
						$f['pages'],
						$f['nature'],
						$f['en_stock'] ? 'stock' : 'épuisé',
						implode( ',', $f['langues'] ),
						wc_get_product( $id )->get_regular_price(),
						$f['titre'],
						implode( ', ', $f['auteurs'] ),
						implode( ', ', $f['roles']['traduction'] ?? array() )
					)
				);
			}
		}
	);

	/**
	 * Les fiches en JSON, sur la sortie : wp er librairie exporter.
	 */
	WP_CLI::add_command(
		'er librairie exporter',
		function () {
			WP_CLI::line( wp_json_encode( array_map( 'er_librairie_fiche', er_librairie_ids() ), JSON_UNESCAPED_UNICODE ) );
		}
	);
}
