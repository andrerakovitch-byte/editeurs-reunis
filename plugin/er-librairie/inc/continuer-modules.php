<?php
/**
 * « Pour aller plus loin » : le choix des modules d'un livre et leurs mots, sans WordPress (continuer.php leur donne
 * les données et montre le résultat ; tests/php/continuer-test.php les essaie).
 *
 * Les textes portent deux marques, que l'affichage change en HTML après avoir échappé le reste : {{…}} un titre
 * d'œuvre (en italique), [[…]] une citation de la notice (entre guillemets, en italique).
 *
 * @package er-librairie
 */

defined( 'ABSPATH' ) || exit;

/* Les mots ------------------------------------------------------------------------------------------------------ */

/**
 * Un petit nombre en lettres : un, une, deux… dix ; au-delà, en chiffres.
 *
 * @param int  $n       Le nombre.
 * @param bool $feminin Au féminin (une).
 */
function er_continuer_nombre( $n, $feminin = false ) {
	$mots = array( 1 => $feminin ? 'une' : 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre', 5 => 'cinq', 6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf', 10 => 'dix' );
	return isset( $mots[ $n ] ) ? $mots[ $n ] : (string) $n;
}

/**
 * Une énumération : « a », « a et b », « a, b et c ».
 *
 * @param string[] $elements Les éléments.
 */
function er_continuer_liste( $elements ) {
	$elements = array_values( array_filter( array_map( 'strval', $elements ), 'strlen' ) );
	if ( count( $elements ) < 2 ) {
		return $elements ? $elements[0] : '';
	}
	$dernier = array_pop( $elements );
	return implode( ', ', $elements ) . ' et ' . $dernier;
}

/**
 * Un texte sans ses marques ({{ }}, [[ ]]).
 *
 * @param string $texte Le texte.
 */
function er_continuer_nu( $texte ) {
	return str_replace( array( '{{', '}}', '[[', ']]' ), '', (string) $texte );
}

/**
 * Le texte commence-t-il, à l'oreille, par une voyelle (« d'Anna », « d'Ivan », « d'Yves », mais « de Iouri »,
 * « de Hugo ») ?
 *
 * @param string $texte Le texte.
 */
function er_continuer_voyelle( $texte ) {
	$t = ltrim( er_continuer_nu( $texte ), " \t«\"“(" );
	if ( '' === $t ) {
		return false;
	}
	$debut = mb_strtolower( mb_substr( $t, 0, 2 ), 'UTF-8' );
	$un    = mb_substr( $debut, 0, 1 );
	$deux  = mb_substr( $debut, 1, 1 );
	// Le cyrillique : а, о, у, э, и, ы ; е, ё, ю, я se disent avec un « i » devant (« de Ёлкин », « de Юрий »).
	if ( in_array( $un, array( 'а', 'о', 'у', 'э', 'и', 'ы' ), true ) ) {
		return true;
	}
	$voyelles = array( 'a', 'à', 'â', 'e', 'é', 'è', 'ê', 'ë', 'i', 'î', 'ï', 'o', 'ô', 'ö', 'u', 'û', 'ü', 'œ', 'æ', 'y' );
	if ( ! in_array( $un, $voyelles, true ) ) {
		return false;
	}
	// I ou Y devant une voyelle se dit comme une consonne : Iouri, Ioulia, Iakov, Yourcenar.
	if ( in_array( $un, array( 'i', 'y' ), true ) && in_array( $deux, $voyelles, true ) ) {
		return false;
	}
	return true;
}

/**
 * « de » devant un nom ou un titre : « de Grossman », « d'Akhmatova », « du {{Docteur Jivago}} », « des {{Frères
 * Karamazov}} », « de {{La Garde blanche}} », « d'{{En mémoire de la mémoire}} ».
 *
 * @param string $texte Le nom ou le titre (avec ses marques).
 */
function er_continuer_de( $texte ) {
	$texte = (string) $texte;
	$titre = 0 === strpos( $texte, '{{' );
	$corps = $titre ? substr( $texte, 2 ) : $texte;
	// L'article d'un titre se contracte et sort de l'italique : du {{Docteur Jivago}}, des {{Frères Karamazov}}.
	if ( preg_match( '/^(Le|Les) (.+)$/su', $corps, $m ) && $titre ) {
		return ( 'Le' === $m[1] ? 'du ' : 'des ' ) . '{{' . mb_strtoupper( mb_substr( $m[2], 0, 1 ) ) . mb_substr( $m[2], 1 );
	}
	// Un nom commun avec son article : « de le XVIIIe siècle » → « du XVIIIe siècle ».
	if ( preg_match( '/^(le|les) (.+)$/su', $corps, $m ) && ! $titre ) {
		return ( 'le' === $m[1] ? 'du ' : 'des ' ) . $m[2];
	}
	return ( er_continuer_voyelle( $texte ) ? 'd’' : 'de ' ) . $texte;
}

/**
 * La première lettre en capitale, marques comprises.
 *
 * @param string $texte Le texte.
 */
function er_continuer_maj( $texte ) {
	if ( preg_match( '/^((?:\{\{|\[\[|« )*)(.)(.*)$/su', (string) $texte, $m ) ) {
		return $m[1] . mb_strtoupper( $m[2], 'UTF-8' ) . $m[3];
	}
	return (string) $texte;
}

/**
 * Une phrase : la capitale au début, un point à la fin (pas après un point d'interrogation ou des points de
 * suspension, ni après les guillemets d'une citation qui finit sur un point).
 *
 * @param string $texte Le texte.
 */
function er_continuer_phrase( $texte ) {
	$texte = trim( (string) $texte );
	if ( '' === $texte ) {
		return '';
	}
	$texte = er_continuer_maj( $texte );
	return preg_match( '/[.!?…]( ?\]\]| ?»)*$/u', $texte ) ? $texte : $texte . '.';
}

/**
 * Un titre d'œuvre, marqué pour l'italique.
 *
 * @param string $titre Le titre.
 */
function er_continuer_titre( $titre ) {
	return '{{' . trim( (string) $titre ) . '}}';
}

/**
 * Le nom court d'une personne : le nom de famille (« Pouchkine », « Akhmatova », « Coldefy-Faucard ») ; le nom
 * entier quand il porte une particule (« Maurice de Gandillac ») ou n'a qu'un mot ; un saint garde tout son nom
 * (« saint Séraphin de Sarov ») ; un titre (« P. », « Mgr », « протоиерей ») tombe.
 *
 * @param string $nom Le nom.
 */
function er_continuer_nom_court( $nom ) {
	$nom = trim( preg_replace( '/\s+/u', ' ', (string) $nom ) );
	if ( preg_match( '/^(saint|sainte|святой|святая|святитель|преподобный|преподобная)\s/iu', $nom ) ) {
		return preg_replace( '/^Saint(e?)\s/u', 'saint$1 ', $nom );
	}
	$nom = preg_replace( '/^(?:(?:p\.|père|mère|mgr|métropolite|archimandrite|archevêque|évêque|hiéromoine|higoumène|protopresbytre|archiprêtre|abbé|frère|sœur|soeur|прот\.|протоиерей|протопресвитер|священник|свящ\.|митрополит|архимандрит|архиепископ|епископ|игумен|иеромонах|монахиня|о\.)\s+)+/iu', '', $nom );
	if ( preg_match( '/\s(de|d’|d\'|du|des|von|van|der|den|di|da|del|della|le|la|ibn|ben|al)\s/iu', ' ' . $nom . ' ' ) && substr_count( $nom, ' ' ) >= 1 ) {
		return $nom;
	}
	$mots = explode( ' ', $nom );
	return (string) end( $mots );
}

/* Les livres ---------------------------------------------------------------------------------------------------- */

/**
 * Ce qu'est un livre, en mots de libraire : sorte → [ singulier, pluriel, féminin, pour un seul livre, possessif ].
 * « pour un seul » : ce qu'on dit d'un seul livre de cette sorte (« des poèmes » plutôt que « un recueil de
 * poèmes ») ; « possessif » : ce qu'on dit des livres d'une personne (« ses poèmes », « son théâtre »).
 */
function er_continuer_sortes() {
	return array(
		'roman'          => array( 'roman', 'romans', false, '', '' ),
		'poemes'         => array( 'recueil de poèmes', 'recueils de poèmes', false, 'des poèmes', 'ses poèmes' ),
		'nouvelles'      => array( 'recueil de nouvelles', 'recueils de nouvelles', false, 'des nouvelles', 'ses nouvelles' ),
		'recit'          => array( 'récit', 'récits', false, '', 'ses récits' ),
		'contes'         => array( 'recueil de contes', 'recueils de contes', false, 'des contes', 'ses contes' ),
		'theatre'        => array( 'livre de théâtre', 'livres de théâtre', false, 'du théâtre', 'son théâtre' ),
		'essai'          => array( 'essai', 'essais', false, '', 'ses essais' ),
		'etude'          => array( 'étude', 'études', true, '', 'ses études' ),
		'temoignage'     => array( 'témoignage', 'témoignages', false, '', '' ),
		'memoires'       => array( 'livre de souvenirs', 'livres de souvenirs', false, 'des souvenirs', 'ses souvenirs' ),
		'journal'        => array( 'journal', 'journaux', false, '', 'son journal' ),
		'carnets'        => array( 'carnet', 'carnets', false, 'des carnets', 'ses carnets' ),
		'lettres'        => array( 'recueil de lettres', 'recueils de lettres', false, 'des lettres', 'ses lettres' ),
		'correspondance' => array( 'correspondance', 'correspondances', true, '', 'sa correspondance' ),
		'entretiens'     => array( 'livre d’entretiens', 'livres d’entretiens', false, 'des entretiens', 'ses entretiens' ),
		'biographie'     => array( 'biographie', 'biographies', true, '', '' ),
		'dictionnaire'   => array( 'dictionnaire', 'dictionnaires', false, '', '' ),
		'manuel'         => array( 'manuel', 'manuels', false, '', '' ),
		'guide'          => array( 'guide', 'guides', false, '', '' ),
		'album'          => array( 'album', 'albums', false, '', 'ses albums' ),
		'jeunesse'       => array( 'livre pour enfants', 'livres pour enfants', false, '', 'ses livres pour enfants' ),
		'adolescents'    => array( 'roman pour adolescents', 'romans pour adolescents', false, '', '' ),
		'vie-de-saint'   => array( 'vie de saint', 'vies de saints', true, '', '' ),
		'priere'         => array( 'livre de prières', 'livres de prières', false, '', '' ),
		'spiritualite'   => array( 'livre de spiritualité', 'livres de spiritualité', false, '', '' ),
		'theologie'      => array( 'ouvrage de théologie', 'ouvrages de théologie', false, '', 'sa théologie' ),
		'philosophie'    => array( 'ouvrage de philosophie', 'ouvrages de philosophie', false, '', 'sa philosophie' ),
		'liturgie'       => array( 'livre liturgique', 'livres liturgiques', false, '', '' ),
		'icones'         => array( 'livre sur les icônes', 'livres sur les icônes', false, '', '' ),
		'art'            => array( 'livre d’art', 'livres d’art', false, '', '' ),
		'photographie'   => array( 'livre de photographies', 'livres de photographies', false, '', '' ),
		'musique'        => array( 'livre sur la musique', 'livres sur la musique', false, '', '' ),
		'cinema'         => array( 'livre sur le cinéma', 'livres sur le cinéma', false, '', '' ),
		'voyage'         => array( 'récit de voyage', 'récits de voyage', false, '', 'ses récits de voyage' ),
		'histoire'       => array( 'livre d’histoire', 'livres d’histoire', false, '', '' ),
		'bilingue'       => array( 'édition bilingue', 'éditions bilingues', true, '', '' ),
		'audio'          => array( 'livre audio', 'livres audio', false, '', '' ),
		'revue'          => array( 'numéro de revue', 'numéros de revue', false, '', '' ),
		'livre'          => array( 'livre', 'livres', false, '', 'ses livres' ),
	);
}

/**
 * Les rayons de la librairie qui disent ce qu'est un livre : slug du rayon → sorte, du plus précis au plus large
 * (un livre rangé à la fois en « Biographies » et en « Histoire des idées » est une biographie).
 */
function er_continuer_sortes_des_rayons() {
	return array(
		'vies-de-saints'            => 'vie-de-saint',
		'biographies'               => 'biographie',
		'memoires'                  => 'temoignage',
		'poesie'                    => 'poemes',
		'theatre'                   => 'theatre',
		'contes-russes'             => 'contes',
		'nouvelles-et-recits'       => 'recit',
		'recit-de-voyage'           => 'voyage',
		'adolescents'               => 'adolescents',
		'des-3-ans'                 => 'jeunesse',
		'des-6-ans'                 => 'jeunesse',
		'des-9-ans'                 => 'jeunesse',
		'albums'                    => 'album',
		'romans'                    => 'roman',
		'dictionnaires'             => 'dictionnaire',
		'grammaire-et-exercices'    => 'manuel',
		'ecriture-et-prononciation' => 'manuel',
		'lectures-bilingues'        => 'bilingue',
		'livres-bilingues'          => 'bilingue',
		'livres-audio'              => 'audio',
		'guides-de-voyage'          => 'guide',
		'liturgie'                  => 'liturgie',
		'spiritualite-et-priere'    => 'spiritualite',
		'theologie-orthodoxe'       => 'theologie',
		'philosophie-russe'         => 'philosophie',
		'icones'                    => 'icones',
		'photographie'              => 'photographie',
		'peinture'                  => 'art',
		'architecture'              => 'art',
		'musique'                   => 'musique',
		'cinema'                    => 'cinema',
		'theatre-et-ballet'         => 'art',
		'histoire-des-idees'        => 'essai',
		'essais-politiques'         => 'essai',
		'essais'                    => 'essai',
		'civilisation'              => 'livre',
	);
}

/**
 * Ce que dit le titre d'un livre de sa sorte, avant le rayon : « Journal… », « Carnets… », « Lettres… »,
 * « Стихотворения », « Сказки »… : regex → sorte.
 */
function er_continuer_sortes_des_titres() {
	return array(
		'/\b(revue|le messager|cahiers? de l’émigration)\b|вестник|^\s*(новый журнал|грани|континент)\b|№\s*\d+/iu' => 'revue',
		'/^(le |mon )?journal\b|\bjournal (de|d’|d\'|intime|d’un|d\'un)|дневник/iu' => 'journal',
		'/\bcarnets?\b|записные книжки|записная книжка/iu' => 'carnets',
		'/\bcorrespondance\b|переписка/iu' => 'correspondance',
		'/^(les )?lettres\b|\blettres (à|de|d’|d\')|письма/iu' => 'lettres',
		'/\bentretiens?\b|\bconversations? avec\b|беседы|разговоры с/iu' => 'entretiens',
		'/\bm[ée]moires\b|\bsouvenirs\b|воспоминания|мемуары/iu' => 'memoires',
		'/\bdictionnaire\b|словарь/iu' => 'dictionnaire',
		'/\bpo[èe]mes\b|\bpo[ée]sies\b|\bstihi\b|стихи|стихотворения|поэмы|\bsonnets\b|\belegies\b|\bélégies\b/iu' => 'poemes',
		'/^(les |des )?contes\b|\bcontes (de|d’|d\'|russes|populaires)|сказки/iu' => 'contes',
		'/^(les |des )?nouvelles\b|рассказы|повести и рассказы/iu' => 'nouvelles',
		'/\bthéâtre\b|\bpièces\b|пьесы|драматургия/iu' => 'theatre',
		'/\bbiographie\b|биография|\bune vie\b|\bla vie de\b|жизнь и/iu' => 'biographie',
	);
}

/**
 * La sorte d'un livre : d'après son titre, sinon son rayon le plus précis, sinon « livre ».
 *
 * @param array $livre  Le livre (l'index).
 * @param array $rayons Les rayons de l'index.
 */
function er_continuer_sorte( $livre, $rayons ) {
	$titre = isset( $livre['t'] ) ? (string) $livre['t'] : '';
	foreach ( er_continuer_sortes_des_titres() as $motif => $sorte ) {
		if ( preg_match( $motif, $titre ) ) {
			return $sorte;
		}
	}
	$slugs = array();
	foreach ( isset( $livre['c'] ) ? (array) $livre['c'] : array() as $c ) {
		if ( isset( $rayons[ $c ]['sl'] ) ) {
			$slugs[] = $rayons[ $c ]['sl'];
		}
	}
	foreach ( er_continuer_sortes_des_rayons() as $slug => $sorte ) {
		if ( in_array( $slug, $slugs, true ) ) {
			return $sorte;
		}
	}
	return 'livre';
}

/**
 * La langue d'un livre, pour les mots : fr, ru, bi (bilingue russe-français) ou autre.
 *
 * @param array $livre Le livre (l'index).
 */
function er_continuer_langue_du_livre( $livre ) {
	$l = isset( $livre['l'] ) ? (array) $livre['l'] : array();
	if ( in_array( 'bilingue-russe-francais', $l, true ) ) {
		return 'bi';
	}
	if ( in_array( 'russe', $l, true ) ) {
		return 'ru';
	}
	if ( in_array( 'francais', $l, true ) ) {
		return 'fr';
	}
	if ( $l ) {
		return 'autre';
	}
	// Sans langue posée : le titre le dit.
	return preg_match( '/\p{Cyrillic}/u', isset( $livre['t'] ) ? (string) $livre['t'] : '' ) ? 'ru' : 'fr';
}

/**
 * Les phrases d'un texte : coupé aux fins de ligne, et après un point, un point d'exclamation ou d'interrogation,
 * des points de suspension, quand une majuscule suit.
 *
 * @param string $texte Le texte.
 * @return string[]
 */
function er_continuer_phrases_du_texte( $texte ) {
	$phrases = array();
	foreach ( preg_split( '/\R+/u', (string) $texte ) as $ligne ) {
		foreach ( preg_split( '/(?<=[.!?…])\s+(?=[«"“(\p{Lu}0-9])/u', trim( $ligne ) ) as $p ) {
			$p = trim( $p );
			if ( '' !== $p ) {
				$phrases[] = $p;
			}
		}
	}
	return $phrases;
}

/* Les cartes de l'index ----------------------------------------------------------------------------------------- */

/**
 * Les renvois de l'index, faits une fois : personne → ses livres (auteur), les livres sur elle (Autour de), ceux
 * où elle a un rôle ; époque, sujet, rayon → leurs livres ; personne, sujet, livre → les contenus qui les portent.
 *
 * @param array $ix L'index.
 */
function er_continuer_carte( $ix ) {
	static $cartes = array();
	$cle = ( isset( $ix['quand'] ) ? $ix['quand'] : 0 ) . ':' . count( $ix['livres'] ) . ':' . count( $ix['contenus'] );
	if ( isset( $cartes[ $cle ] ) ) {
		return $cartes[ $cle ];
	}
	$c = array(
		'auteur'   => array(),
		'autour'   => array(),
		'role'     => array(),
		'periode'  => array(),
		'ecriture' => array(),
		'sujet'    => array(),
		'rayon'    => array(),
		'c_pers'   => array(),
		'c_sujet'  => array(),
		'c_livre'  => array(),
	);
	foreach ( $ix['livres'] as $id => $l ) {
		foreach ( isset( $l['a'] ) ? $l['a'] : array() as $p ) {
			$c['auteur'][ $p ][] = $id;
		}
		foreach ( isset( $l['ab'] ) ? $l['ab'] : array() as $p ) {
			$c['autour'][ $p ][] = $id;
		}
		foreach ( isset( $l['r'] ) ? $l['r'] : array() as $role => $gens ) {
			foreach ( $gens as $p ) {
				$c['role'][ $role ][ $p ][] = $id;
			}
		}
		foreach ( isset( $l['p'] ) ? $l['p'] : array() as $e ) {
			$c['periode'][ $e ][] = $id;
		}
		foreach ( isset( $l['e'] ) ? $l['e'] : array() as $e ) {
			$c['ecriture'][ $e ][] = $id;
		}
		foreach ( isset( $l['s'] ) ? $l['s'] : array() as $s ) {
			$c['sujet'][ $s ][] = $id;
		}
		foreach ( isset( $l['c'] ) ? $l['c'] : array() as $r ) {
			$c['rayon'][ $r ][] = $id;
		}
	}
	foreach ( $ix['contenus'] as $cid => $co ) {
		foreach ( isset( $co['a'] ) ? $co['a'] : array() as $p ) {
			$c['c_pers'][ $p ][] = $cid;
		}
		foreach ( isset( $co['s'] ) ? $co['s'] : array() as $s ) {
			$c['c_sujet'][ $s ][] = $cid;
		}
		foreach ( isset( $co['lv'] ) ? $co['lv'] : array() as $b ) {
			$c['c_livre'][ $b ][] = $cid;
		}
	}
	$cartes = array( $cle => $c );
	return $c;
}

/**
 * Un champ d'un livre de l'index, en tableau.
 *
 * @param array  $l     Le livre.
 * @param string $champ Le champ.
 */
function er_continuer_champ( $l, $champ ) {
	return isset( $l[ $champ ] ) ? (array) $l[ $champ ] : array();
}

/**
 * Le nom d'une personne tel que la page le montre : en français, la graphie latine d'un nom écrit en cyrillique
 * quand la personne en a une (« Alexandre Arkhangelski » plutôt que « Ал. Архангельский »).
 *
 * @param int    $p      La personne.
 * @param array  $ix     L'index.
 * @param string $langue La langue de la page.
 */
function er_continuer_nom( $p, $ix, $langue = 'fr' ) {
	if ( ! isset( $ix['personnes'][ $p ] ) ) {
		return '';
	}
	$nom = trim( (string) $ix['personnes'][ $p ]['n'] );
	if ( 'ru' !== $langue && preg_match( '/\p{Cyrillic}/u', $nom ) ) {
		foreach ( isset( $ix['personnes'][ $p ]['g'] ) ? (array) $ix['personnes'][ $p ]['g'] : array() as $g ) {
			if ( '' !== trim( $g ) && ! preg_match( '/\p{Cyrillic}/u', $g ) && substr_count( trim( $g ), ' ' ) >= 1 ) {
				return trim( $g );
			}
		}
	}
	return $nom;
}

/**
 * Le nom à dire dans une ligne : court pour une personne que la librairie connaît bien (cinq livres et plus, ou
 * déjà nommée plus haut), entier sinon (« d'Akhmatova », mais « d'Anne-Marie Pelletier »).
 *
 * @param int    $p      La personne.
 * @param array  $ix     L'index.
 * @param string $langue La langue de la page.
 */
function er_continuer_nom_ligne( $p, $ix, $langue = 'fr' ) {
	$nom = er_continuer_nom( $p, $ix, $langue );
	$nb  = isset( $ix['personnes'][ $p ]['nb'] ) ? (int) $ix['personnes'][ $p ]['nb'] : 0;
	return $nb >= 5 ? er_continuer_nom_court( $nom ) : $nom;
}
