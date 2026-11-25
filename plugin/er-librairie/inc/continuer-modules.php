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
	// Un élément qui porte déjà son « et » (« sur la Russie et sur le pouvoir », « d'Ilf et Pétrov ») : seul, il passe
	// en dernier ; à plusieurs, une virgule avant le dernier, pour qu'on ne confonde pas les « et ».
	$a_et = fn( $e ) => false !== strpos( (string) preg_replace( '/\{\{.*?\}\}/u', '', $e ), ' et ' );
	$avec = array_keys( array_filter( $elements, $a_et ) );
	if ( 1 === count( $avec ) && count( $elements ) - 1 !== $avec[0] ) {
		$e = $elements[ $avec[0] ];
		unset( $elements[ $avec[0] ] );
		$elements[] = $e;
		$elements   = array_values( $elements );
	}
	$dernier = array_pop( $elements );
	$ambigu  = (bool) array_filter( $elements, $a_et );
	return implode( ', ', $elements ) . ( $ambigu ? ', et ' : ' et ' ) . $dernier;
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
	// Les prénoms à h muet : « d'Hélène Carrère d'Encausse », « d'Henri Troyat » (les noms de famille gardent « de »).
	if ( preg_match( '/^(Hélène|Héléna|Henri|Henriette|Henry|Hervé|Hubert|Hugues|Honoré|Hortense|Hippolyte|Héloïse)\b/u', $t ) ) {
		return true;
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
 * Un nom de religieux au fil d'une phrase : le titre en minuscule, avec l'article que le français lui donne (« le père
 * Serge Boulgakov », « l'abbé Pierre », « mère Marie Skobtsova ») ; « de » le contracte ensuite (« du père »). Les
 * autres noms ne changent pas.
 *
 * @param string $nom Le nom.
 */
function er_continuer_nom_dans_phrase( $nom ) {
	$titres = array(
		'père'          => 'le père',
		'p.'            => 'le père',
		'frère'         => 'le frère',
		'mère'          => 'mère',
		'sœur'          => 'sœur',
		'soeur'         => 'sœur',
		'abbé'          => 'l’abbé',
		'métropolite'   => 'le métropolite',
		'archimandrite' => 'l’archimandrite',
		'archevêque'    => 'l’archevêque',
		'évêque'        => 'l’évêque',
		'hiéromoine'    => 'le hiéromoine',
		'higoumène'     => 'l’higoumène',
		'archiprêtre'   => 'l’archiprêtre',
	);
	if ( preg_match( '/^(\S+)\s+(.+)$/u', trim( (string) $nom ), $m ) ) {
		$t = mb_strtolower( $m[1], 'UTF-8' );
		if ( isset( $titres[ $t ] ) ) {
			return $titres[ $t ] . ' ' . $m[2];
		}
	}
	return (string) $nom;
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
	if ( ! $titre ) {
		$texte = er_continuer_nom_dans_phrase( $texte );
	}
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
	$exceptions = array(
		'Dante Alighieri' => 'Dante',
	);
	if ( isset( $exceptions[ $nom ] ) ) {
		return $exceptions[ $nom ];
	}
	// « Ilf et Pétrov » : un nom de duo reste entier.
	if ( false !== strpos( $nom, ' et ' ) ) {
		return $nom;
	}
	$nom = preg_replace( '/^(?:(?:p\.|père|mère|mgr|métropolite|archimandrite|archevêque|évêque|hiéromoine|higoumène|protopresbytre|archiprêtre|abbé|frère|sœur|soeur|прот\.|протоиерей|протопресвитер|священник|свящ\.|митрополит|архимандрит|архиепископ|епископ|игумен|иеромонах|монахиня|о\.)\s+)+/iu', '', $nom );
	if ( preg_match( '/\s(de|d’|d\'|du|des|von|van|der|den|di|da|del|della|le|la|ibn|ben|al)\s/iu', ' ' . $nom . ' ' ) && substr_count( $nom, ' ' ) >= 1 ) {
		return $nom;
	}
	$mots = explode( ' ', $nom );
	// Un souverain se nomme avec son numéro : « Catherine II », pas « II ».
	if ( count( $mots ) >= 2 && preg_match( '/^(?:[IVX]+|Ier|Ire|1er|1re)$/u', (string) end( $mots ) ) ) {
		return implode( ' ', array_slice( $mots, -2 ) );
	}
	return (string) end( $mots );
}

/* Les livres ---------------------------------------------------------------------------------------------------- */

/**
 * Ce qu'est un livre, en mots de libraire : sorte → [ singulier, pluriel, féminin, pour un seul livre, possessif ].
 * « pour un seul » : ce qu'on dit d'un seul livre de cette sorte (« des poèmes » plutôt que « un recueil de
 * poèmes ») ; « possessif » : ce qu'on dit des livres d'une personne (« ses poèmes », « son théâtre »). Un nom suivi
 * de « sur … » garde ce complément après l'auteur : « un livre de Sylvie Mamy sur la musique ».
 */
function er_continuer_sortes() {
	return array(
		'roman'          => array( 'roman', 'romans', false, '', '' ),
		'poemes'         => array( 'recueil de poèmes', 'recueils de poèmes', false, 'des poèmes', 'ses poèmes' ),
		'nouvelles'      => array( 'recueil de nouvelles', 'recueils de nouvelles', false, 'des nouvelles', 'ses nouvelles' ),
		'recit'          => array( 'récit', 'récits', false, '', 'ses récits' ),
		'contes'         => array( 'recueil de contes', 'recueils de contes', false, 'des contes', 'ses contes' ),
		'theatre'        => array( 'livre de théâtre', 'livres de théâtre', false, 'du théâtre', 'son théâtre' ),
		'oeuvres'        => array( 'volume d’œuvres', 'volumes d’œuvres', false, 'des œuvres', 'un volume de ses œuvres' ),
		'anthologie'     => array( 'anthologie', 'anthologies', true, '', '' ),
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
		'histoire'       => array( 'livre d’histoire', 'livres d’histoire', false, '', '' ),
		'actualite'      => array( 'livre sur la Russie d’aujourd’hui', 'livres sur la Russie d’aujourd’hui', false, '', '' ),
		'dictionnaire'   => array( 'dictionnaire', 'dictionnaires', false, '', '' ),
		'manuel'         => array( 'manuel', 'manuels', false, '', '' ),
		'guide'          => array( 'guide', 'guides', false, '', '' ),
		'album'          => array( 'album', 'albums', false, '', 'ses albums' ),
		'jeunesse'       => array( 'livre pour enfants', 'livres pour enfants', false, '', 'ses livres pour enfants' ),
		'adolescents'    => array( 'roman pour adolescents', 'romans pour adolescents', false, '', '' ),
		'vie-de-saint'   => array( 'vie de saint', 'vies de saints', true, '', '' ),
		'spiritualite'   => array( 'livre de spiritualité', 'livres de spiritualité', false, '', '' ),
		'theologie'      => array( 'ouvrage de théologie', 'ouvrages de théologie', false, '', '' ),
		'philosophie'    => array( 'ouvrage de philosophie', 'ouvrages de philosophie', false, '', '' ),
		'liturgie'       => array( 'livre liturgique', 'livres liturgiques', false, '', '' ),
		'icones'         => array( 'livre sur les icônes', 'livres sur les icônes', false, '', '' ),
		'art'            => array( 'livre d’art', 'livres d’art', false, '', '' ),
		'photographie'   => array( 'livre de photographies', 'livres de photographies', false, '', '' ),
		'musique'        => array( 'livre sur la musique', 'livres sur la musique', false, '', '' ),
		'cinema'         => array( 'livre sur le cinéma', 'livres sur le cinéma', false, '', '' ),
		'scene'          => array( 'livre sur le théâtre et la danse', 'livres sur le théâtre et la danse', false, '', '' ),
		'voyage'         => array( 'récit de voyage', 'récits de voyage', false, '', 'ses récits de voyage' ),
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
		'adolescents'               => 'adolescents',
		'romans-jeunesse'           => 'jeunesse',
		'des-3-ans'                 => 'jeunesse',
		'des-6-ans'                 => 'jeunesse',
		'des-9-ans'                 => 'jeunesse',
		'albums'                    => 'album',
		'livres-bilingues'          => 'bilingue',
		'romans'                    => 'roman',
		'nouvelles-et-recits'       => 'recit',
		'recit-de-voyage'           => 'voyage',
		'dictionnaires'             => 'dictionnaire',
		'grammaire-et-exercices'    => 'manuel',
		'ecriture-et-prononciation' => 'manuel',
		'debutant'                  => 'manuel',
		'intermediaire'             => 'manuel',
		'avance'                    => 'manuel',
		'lectures-bilingues'        => 'bilingue',
		'livres-audio'              => 'audio',
		'guides-de-voyage'          => 'guide',
		'liturgie'                  => 'liturgie',
		'icones'                    => 'icones',
		'spiritualite-et-priere'    => 'spiritualite',
		'theologie-orthodoxe'       => 'theologie',
		'philosophie-russe'         => 'philosophie',
		'photographie'              => 'photographie',
		'peinture'                  => 'art',
		'architecture'              => 'art',
		'musique'                   => 'musique',
		'cinema'                    => 'cinema',
		'theatre-et-ballet'         => 'scene',
		'histoire-des-idees'        => 'essai',
		'essais-politiques'         => 'essai',
		'essais'                    => 'essai',
		'russie-aujourdhui'         => 'actualite',
		'goulag'                    => 'histoire',
		'emigration-russe'          => 'histoire',
		'empire'                    => 'histoire',
		'revolution-1917'           => 'histoire',
		'urss'                      => 'histoire',
		'russie-ancienne'           => 'histoire',
		'civilisation'              => 'histoire',
		'histoire'                  => 'histoire',
		'arts'                      => 'art',
		'jeunesse'                  => 'jeunesse',
		'apprendre-le-russe'        => 'manuel',
	);
}

/**
 * Ce que dit le titre d'un livre de sa sorte : regex → sorte. Les revues et les anthologies d'abord, quel que soit
 * le rayon ; le reste ne précise que les livres qui ne sont pas de fiction (un « Journal d'un fou » rangé dans les
 * nouvelles reste un recueil de nouvelles).
 */
function er_continuer_sortes_des_titres() {
	return array(
		'/^\s*(le messager orthodoxe|вестник|православная мысль|новый журнал|грани|континент)\b|\bcahiers de l’émigration\b/iu' => 'revue',
		'/\banthologie\b|антология/iu' => 'anthologie',
		'/(^|, )(le |mon |un )?journal\b|^дневник|, дневник/iu' => 'journal',
		'/(^|, )carnets?\b|записные книжки|записная книжка/iu' => 'carnets',
		'/\bcorrespondance\b|переписка/iu' => 'correspondance',
		'/^(les )?lettres\b|(^|, )lettres (à|de|d’|d\')|^письма/iu' => 'lettres',
		'/\bentretiens?\b|\bconversations? avec\b|беседы|разговоры с/iu' => 'entretiens',
		'/\bm[ée]moires\b|\bsouvenirs\b|воспоминания|мемуары/iu' => 'memoires',
		'/\bdictionnaire\b|словарь|\bvocabulaire\b/iu' => 'dictionnaire',
		'/\bpo[èe]mes\b|\bpo[ée]sies\b|œuvres poétiques|стихи|стихотворения|поэмы|\bsonnets\b|élégies/iu' => 'poemes',
		'/^(les |des )?contes\b|\bcontes (de|d’|d\'|russes|populaires)|сказки/iu' => 'contes',
		'/^(les |des )?nouvelles\b|рассказы/iu' => 'nouvelles',
		'/\bth[ée][âa]tr|\bpièces\b|пьесы|драматурги/iu' => 'theatre',
		'/\bœuvres\b|\boeuvres\b|сочинени|собрание/iu' => 'oeuvres',
		'/\bbiographie\b|биография/iu' => 'biographie',
		'/\bessais?\b|очерки|этюды|статьи/iu' => 'essai',
	);
}

/**
 * La sorte d'un livre : une revue ou une anthologie d'après son titre ; un livre de fiction d'après son rayon ; les
 * autres d'après leur titre, sinon leur rayon le plus précis, sinon « livre ».
 *
 * @param array $livre  Le livre (l'index).
 * @param array $rayons Les rayons de l'index.
 */
function er_continuer_sorte( $livre, $rayons ) {
	$titre = isset( $livre['t'] ) ? (string) $livre['t'] : '';
	if ( isset( $livre['na'] ) && 'revue' === $livre['na'] ) {
		return 'revue';
	}
	$par_titre = '';
	foreach ( er_continuer_sortes_des_titres() as $motif => $sorte ) {
		if ( preg_match( $motif, $titre ) ) {
			$par_titre = $sorte;
			break;
		}
	}
	if ( in_array( $par_titre, array( 'revue', 'anthologie' ), true ) ) {
		return $par_titre;
	}
	$slugs = array();
	foreach ( isset( $livre['c'] ) ? (array) $livre['c'] : array() as $c ) {
		if ( isset( $rayons[ $c ]['sl'] ) ) {
			$slugs[] = $rayons[ $c ]['sl'];
		}
	}
	$par_rayon = '';
	foreach ( er_continuer_sortes_des_rayons() as $slug => $sorte ) {
		if ( in_array( $slug, $slugs, true ) ) {
			$par_rayon = $sorte;
			break;
		}
	}
	$fiction = array( 'roman', 'recit', 'theatre', 'contes', 'jeunesse', 'album', 'adolescents', 'poemes', 'bilingue' );
	if ( in_array( $par_rayon, $fiction, true ) ) {
		// Le titre précise seulement la poésie, les contes, les nouvelles, les œuvres : « Nouvelles » rangées en récits.
		return in_array( $par_titre, array( 'poemes', 'contes', 'nouvelles', 'oeuvres', 'theatre' ), true ) && 'jeunesse' !== $par_rayon && 'album' !== $par_rayon ? $par_titre : $par_rayon;
	}
	if ( '' !== $par_titre ) {
		return $par_titre;
	}
	return '' !== $par_rayon ? $par_rayon : 'livre';
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
	$nom = str_replace( "'", '’', trim( (string) $ix['personnes'][ $p ]['n'] ) );
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

/**
 * Les noms de plusieurs personnes dans une même énumération, de même forme : tous courts quand toutes sont connues,
 * sinon tous entiers (« de Léonid Andréïev et de Marina Tsvétaïéva », pas « de Léonid Andréïev et de Tsvétaïéva »).
 *
 * @param int[]  $personnes Les personnes.
 * @param array  $ix        L'index.
 * @param string $langue    La langue de la page.
 * @return string[]
 */
function er_continuer_noms_ligne( $personnes, $ix, $langue = 'fr' ) {
	$courts = true;
	foreach ( $personnes as $p ) {
		if ( ( isset( $ix['personnes'][ $p ]['nb'] ) ? (int) $ix['personnes'][ $p ]['nb'] : 0 ) < 5 ) {
			$courts = false;
		}
	}
	return array_map( fn( $p ) => $courts ? er_continuer_nom_court( er_continuer_nom( $p, $ix, $langue ) ) : er_continuer_nom( $p, $ix, $langue ), array_values( $personnes ) );
}

/* Les époques --------------------------------------------------------------------------------------------------- */

/**
 * Ce qu'on dit des époques (er_epoques) : slug → [ le titre d'un module, « sur … », « pendant … », longue, « de … » ]
 * (« des poèmes de l'Âge d'argent », mais « des témoignages sur la guerre »).
 * « Longue » : une époque de plus d'un siècle, dont les livres ne sont pas « des mêmes années ».
 */
function er_continuer_epoques_mots() {
	return array(
		'premiers-siecles-et-byzance' => array( 'Les premiers siècles chrétiens et Byzance', 'sur les premiers siècles chrétiens et Byzance', 'aux premiers siècles chrétiens', true, 'des premiers siècles chrétiens' ),
		'russie-ancienne'             => array( 'La Russie ancienne', 'sur la Russie ancienne', 'dans la Russie ancienne', true, 'de la Russie ancienne' ),
		'xviiie-siecle'               => array( 'Le XVIIIe siècle', 'sur le XVIIIe siècle', 'au XVIIIe siècle', true, 'du XVIIIe siècle' ),
		'age-d-or'                    => array( 'L’Âge d’or, 1800-1855', 'sur l’Âge d’or', 'à l’Âge d’or', false, 'de l’Âge d’or' ),
		'grand-roman-et-reformes'     => array( 'La Russie des grandes réformes, 1855-1890', 'sur la Russie des grandes réformes', 'au temps du grand roman', false, 'du temps du grand roman' ),
		'age-d-argent'                => array( 'L’Âge d’argent, 1890-1917', 'sur l’Âge d’argent', 'à l’Âge d’argent', false, 'de l’Âge d’argent' ),
		'revolution-et-guerre-civile' => array( 'La révolution et la guerre civile, 1917-1922', 'sur la révolution et la guerre civile', 'pendant la révolution et la guerre civile', false, 'de la révolution et de la guerre civile' ),
		'annees-1920-et-1930'         => array( 'Les années 1920 et 1930', 'sur les années 1920 et 1930', 'dans les années 1920 et 1930', false, 'des années 1920 et 1930' ),
		'la-guerre'                   => array( 'La guerre, 1941-1945', 'sur la guerre', 'pendant la guerre', false, 'de la guerre' ),
		'apres-guerre-et-degel'       => array( 'L’après-guerre et le Dégel, 1945-1964', 'sur l’après-guerre et le Dégel', 'après la guerre et pendant le Dégel', false, 'de l’après-guerre et du Dégel' ),
		'stagnation'                  => array( 'La stagnation, 1964-1985', 'sur les années de stagnation', 'pendant la stagnation', false, 'des années de stagnation' ),
		'perestroika'                 => array( 'La perestroïka et la fin de l’URSS, 1985-1991', 'sur la perestroïka', 'pendant la perestroïka', false, 'de la perestroïka' ),
		'annees-1990'                 => array( 'Les années 1990', 'sur les années 1990', 'dans les années 1990', false, 'des années 1990' ),
		'russie-de-poutine'           => array( 'La Russie de Poutine, depuis 2000', 'sur la Russie de Poutine', 'depuis 2000', false, 'de la Russie de Poutine' ),
	);
}

/* Le classement des livres d'un module -------------------------------------------------------------------------- */

/**
 * Des livres, les plus proches d'abord : la proximité donnée par le module (bonus), puis en stock, avec une phrase
 * et une couverture, dans la langue du livre, les nouveautés ; à égalité, dans un ordre propre à la fiche.
 *
 * @param int[] $ids     Les livres.
 * @param array $ix      L'index.
 * @param int   $courant Le livre de la fiche.
 * @param array $bonus   livre → proximité.
 * @param int   $stock   Le poids d'un livre en stock (un module qui cherche loin préfère nettement ce qu'on peut
 *                       acheter).
 * @return int[]
 */
function er_continuer_ranger( $ids, $ix, $courant, $bonus = array(), $stock = 6 ) {
	$langue = er_continuer_langue_du_livre( $ix['livres'][ $courant ] );
	$scores = array();
	foreach ( array_unique( $ids ) as $i ) {
		if ( ! isset( $ix['livres'][ $i ] ) ) {
			continue;
		}
		$l            = $ix['livres'][ $i ];
		$s            = ( isset( $bonus[ $i ] ) ? $bonus[ $i ] : 0 ) * 10
			+ ( ! empty( $l['st'] ) ? $stock : 0 )
			+ ( ! empty( $l['ph'] ) ? 2 : 0 )
			+ ( ! empty( $l['img'] ) ? 2 : 0 )
			+ ( er_continuer_langue_du_livre( $l ) === $langue ? 1 : 0 )
			+ ( in_array( 'nouveaute', er_continuer_champ( $l, 'm' ), true ) ? 1 : 0 );
		// À égalité, un ordre propre à chaque fiche : un même livre ne revient pas en tête de toutes les fiches qui
		// pourraient le proposer, et la même fiche garde le même ordre d'une visite à l'autre.
		$scores[ $i ] = array( $s, crc32( $courant . ':' . $i ), $i );
	}
	uasort( $scores, fn( $a, $b ) => array( $b[0], $b[1], $a[2] ) <=> array( $a[0], $a[1], $b[2] ) );
	return array_keys( $scores );
}

/**
 * Des livres variés parmi des livres rangés : pas deux du même auteur, pas plus de trois de la même sorte, un seul
 * numéro de revue, tant que d'autres peuvent prendre la place.
 *
 * @param int[] $ranges Les livres, les plus proches d'abord.
 * @param array $ix     L'index.
 * @param int   $n      Combien.
 * @return int[]
 */
function er_continuer_divers( $ranges, $ix, $n ) {
	$choisis = array();
	$auteurs = array();
	$sortes  = array();
	$reste   = array();
	foreach ( $ranges as $i ) {
		if ( count( $choisis ) >= $n ) {
			break;
		}
		$a  = er_continuer_champ( $ix['livres'][ $i ], 'a' );
		$a0 = $a ? (int) $a[0] : 0;
		$so = er_continuer_sorte( $ix['livres'][ $i ], $ix['rayons'] );
		// Deux éditions d'une même œuvre (« Crime et châtiment », « Crime et châtiment suivi de… ») : une seule.
		foreach ( $choisis as $c ) {
			if ( er_continuer_meme_oeuvre( $i, $c, $ix ) ) {
				continue 2;
			}
		}
		if ( ( $a0 && in_array( $a0, $auteurs, true ) ) || ( isset( $sortes[ $so ] ) && $sortes[ $so ] >= ( 'revue' === $so ? 1 : 3 ) ) ) {
			$reste[] = $i;
			continue;
		}
		$choisis[] = $i;
		if ( $a0 ) {
			$auteurs[] = $a0;
		}
		$sortes[ $so ] = ( isset( $sortes[ $so ] ) ? $sortes[ $so ] : 0 ) + 1;
	}
	foreach ( $reste as $i ) {
		if ( count( $choisis ) >= $n ) {
			break;
		}
		if ( 'revue' === er_continuer_sorte( $ix['livres'][ $i ], $ix['rayons'] ) && ! empty( $sortes['revue'] ) ) {
			continue;
		}
		foreach ( $choisis as $c ) {
			if ( er_continuer_meme_oeuvre( $i, $c, $ix ) ) {
				continue 2;
			}
		}
		$choisis[] = $i;
	}
	return $choisis;
}

/**
 * Deux livres sont-ils deux éditions d'une même œuvre ? Le même titre, ou l'un qui commence par l'autre (« Crime et
 * châtiment suivi de… »). Les numéros d'une revue et les tomes d'une suite restent distincts.
 *
 * @param int   $a  Un livre.
 * @param int   $b  L'autre.
 * @param array $ix L'index.
 */
function er_continuer_meme_oeuvre( $a, $b, $ix ) {
	$ta = isset( $ix['livres'][ $a ]['t'] ) ? (string) $ix['livres'][ $a ]['t'] : '';
	$tb = isset( $ix['livres'][ $b ]['t'] ) ? (string) $ix['livres'][ $b ]['t'] : '';
	$numerote = fn( $t ) => er_continuer_numero( $t )[0] || er_continuer_revue( $t ) !== trim( $t );
	if ( $numerote( $ta ) || $numerote( $tb ) ) {
		return false;
	}
	$k  = ' ' . ( isset( $ix['livres'][ $a ]['k'] ) ? $ix['livres'][ $a ]['k'] : '' ) . ' ';
	$kc = ' ' . ( isset( $ix['livres'][ $b ]['k'] ) ? $ix['livres'][ $b ]['k'] : '' ) . ' ';
	return strlen( trim( $k ) ) >= 4 && strlen( trim( $kc ) ) >= 4 && ( 0 === strpos( $k, $kc ) || 0 === strpos( $kc, $k ) );
}

/**
 * Des livres rangés, une seule édition de chaque œuvre (la mieux rangée).
 *
 * @param int[] $ranges Les livres, les plus proches d'abord.
 * @param array $ix     L'index.
 * @return int[]
 */
function er_continuer_une_edition( $ranges, $ix ) {
	$gardes = array();
	foreach ( $ranges as $i ) {
		foreach ( $gardes as $g ) {
			if ( er_continuer_meme_oeuvre( $i, $g, $ix ) ) {
				continue 2;
			}
		}
		$gardes[] = $i;
	}
	return $gardes;
}

/**
 * Le numéro d'un volume dans son titre : « tome 2 », « vol. III », « том 1 », « книга 2 », « часть 3 » ; 0 sinon.
 *
 * @param string $titre Le titre.
 * @return array [ numéro, mot ] (le mot : tome, volume, partie, livre, numéro).
 */
function er_continuer_numero( $titre ) {
	$mots = array(
		'tome'   => 'tome',
		't.'     => 'tome',
		'том'    => 'tome',
		'т.'     => 'tome',
		'vol.'   => 'volume',
		'vol'    => 'volume',
		'volume' => 'volume',
		'livre'  => 'livre',
		'книга'  => 'livre',
		'кн.'    => 'livre',
		'partie' => 'partie',
		'часть'  => 'partie',
		'cahier' => 'cahier',
		'выпуск' => 'numéro',
		'вып.'   => 'numéro',
		'n°'     => 'numéro',
		'no'     => 'numéro',
		'№'      => 'numéro',
	);
	if ( preg_match( '/(?:^|[\s,.(–—-])(tome|t\.|том|т\.|vol\.|vol|volume|livre|книга|кн\.|partie|часть|cahier|выпуск|вып\.|n°|no|№)\s*([0-9]+|[IVXLC]+)\b/iu', (string) $titre, $m ) ) {
		$n = ctype_digit( $m[2] ) ? (int) $m[2] : er_continuer_romain( $m[2] );
		return array( $n, $mots[ mb_strtolower( $m[1], 'UTF-8' ) ] ?? 'volume' );
	}
	return array( 0, '' );
}

/**
 * Un nombre romain en chiffres (III → 3).
 *
 * @param string $r Le nombre romain.
 */
function er_continuer_romain( $r ) {
	$valeurs = array( 'I' => 1, 'V' => 5, 'X' => 10, 'L' => 50, 'C' => 100 );
	$r       = strtoupper( $r );
	$n       = 0;
	for ( $i = 0; $i < strlen( $r ); $i++ ) {
		$v  = $valeurs[ $r[ $i ] ] ?? 0;
		$n += ( $i + 1 < strlen( $r ) && ( $valeurs[ $r[ $i + 1 ] ] ?? 0 ) > $v ) ? -$v : $v;
	}
	return $n;
}

/**
 * Un ordinal en lettres, accordé : premier, première, deuxième… dixième.
 *
 * @param int  $n       Le rang.
 * @param bool $feminin Au féminin.
 */
function er_continuer_ordinal( $n, $feminin = false ) {
	$mots = array( 1 => $feminin ? 'première' : 'premier', 2 => 'deuxième', 3 => 'troisième', 4 => 'quatrième', 5 => 'cinquième', 6 => 'sixième', 7 => 'septième', 8 => 'huitième', 9 => 'neuvième', 10 => 'dixième' );
	return isset( $mots[ $n ] ) ? $mots[ $n ] : $n . 'e';
}

/**
 * Les autres fiches d'un même livre (comme er_autres_editions, sur l'index) : mêmes auteurs et même titre avant le
 * sous-titre ; le même titre en entier est une autre édition, un titre qui ne diffère qu'après est un autre volume.
 * S'y ajoute l'édition dans l'autre langue : un livre russe dont le titre en français (er_titre_fr) a la clé du titre
 * d'un livre français d'un même auteur, ou l'inverse.
 *
 * @param int   $id Le livre.
 * @param array $ix L'index.
 * @return array editions, volumes.
 */
function er_continuer_editions( $id, $ix ) {
	$trouves = array(
		'editions' => array(),
		'volumes'  => array(),
	);
	$l       = $ix['livres'][ $id ];
	$auteurs = er_continuer_champ( $l, 'a' );
	if ( ! $auteurs ) {
		return $trouves;
	}
	sort( $auteurs );
	$carte   = er_continuer_carte( $ix );
	$proches = array();
	foreach ( $auteurs as $a ) {
		$proches = array_merge( $proches, isset( $carte['auteur'][ $a ] ) ? $carte['auteur'][ $a ] : array() );
	}
	$k  = isset( $l['k'] ) ? (string) $l['k'] : '';
	$kf = isset( $l['kf'] ) ? (string) $l['kf'] : '';
	foreach ( array_unique( $proches ) as $o ) {
		if ( $o === $id ) {
			continue;
		}
		$lo    = $ix['livres'][ $o ];
		$siens = er_continuer_champ( $lo, 'a' );
		sort( $siens );
		$ko  = isset( $lo['k'] ) ? (string) $lo['k'] : '';
		$kfo = isset( $lo['kf'] ) ? (string) $lo['kf'] : '';
		if ( $siens === $auteurs && strlen( $k ) >= 3 && $ko === $k ) {
			$trouves[ ( isset( $lo['kt'], $l['kt'] ) && $lo['kt'] === $l['kt'] ) ? 'editions' : 'volumes' ][] = $o;
			continue;
		}
		// L'autre langue : la traduction du titre de l'un est le titre de l'autre.
		if ( er_continuer_langue_du_livre( $lo ) !== er_continuer_langue_du_livre( $l ) && ( ( '' !== $kf && strlen( $kf ) >= 4 && $kf === $ko ) || ( '' !== $kfo && strlen( $kfo ) >= 4 && $kfo === $k ) ) ) {
			$trouves['editions'][] = $o;
		}
	}
	foreach ( array_keys( $trouves ) as $sorte ) {
		$trouves[ $sorte ] = array_values( array_unique( $trouves[ $sorte ] ) );
	}
	return $trouves;
}

/* L'inventaire : ce que contient un module, en une phrase -------------------------------------------------------- */

/**
 * Un contenu du centre en quelques mots : « un article du journal du centre », « une rencontre à revoir en vidéo »,
 * « une rencontre le 12 décembre », « un podcast ».
 *
 * @param array $co         Le contenu (l'index).
 * @param int   $maintenant L'heure.
 */
function er_continuer_mots_contenu( $co, $maintenant ) {
	$type = isset( $co['ty'] ) ? $co['ty'] : 'post';
	if ( 'er_evenement' === $type ) {
		$debut = isset( $co['deb'] ) ? strtotime( (string) $co['deb'] ) : 0;
		if ( $debut && $debut > $maintenant ) {
			return 'une rencontre au centre le ' . er_continuer_date( $debut, $maintenant );
		}
		return ! empty( $co['vi'] ) ? 'une rencontre au centre, à revoir en vidéo' : 'une rencontre au centre';
	}
	if ( 'er_media' === $type ) {
		return isset( $co['ge'] ) && 'podcast' === $co['ge'] ? 'un podcast du centre' : 'une vidéo du centre';
	}
	return 'un article du journal du centre';
}

/**
 * Une date à la française, sans l'année quand c'est cette année : « 12 décembre », « 1er mars 2027 ».
 *
 * @param int $t          La date.
 * @param int $maintenant L'heure.
 */
function er_continuer_date( $t, $maintenant ) {
	$mois = array( 1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
	$j    = (int) gmdate( 'j', $t );
	$s    = ( 1 === $j ? '1er' : (string) $j ) . ' ' . $mois[ (int) gmdate( 'n', $t ) ];
	return gmdate( 'Y', $t ) === gmdate( 'Y', $maintenant ) ? $s : $s . ' ' . gmdate( 'Y', $t );
}

/**
 * Le nom d'une personne peut-il se dire dans une phrase de la page ? Pas un nom resté en cyrillique sur une page en
 * français (« d'Александр Гладков »), ni des initiales seules.
 *
 * @param int    $p    La personne.
 * @param array  $ix   L'index.
 * @param string $page La langue de la page.
 */
function er_continuer_nom_dicible( $p, $ix, $page = 'fr' ) {
	$nom = er_continuer_nom( $p, $ix, $page );
	if ( '' === $nom || ( 'ru' !== $page && preg_match( '/\p{Cyrillic}/u', $nom ) ) ) {
		return false;
	}
	return mb_strlen( preg_replace( '/[\p{Lu}]\.\s*/u', '', $nom ) ) >= 3;
}

/**
 * Ce que contient un module, en une phrase de libraire : « Trois romans, de Vadim Andreïev, d'Andreï Guelassimov et
 * de Sacha Filipenko, et un récit d'Ivo Andrić. », « Ses contes, son théâtre et ses poèmes. », « Un essai d'Anne-Marie
 * Pelletier et un article du journal du centre. »
 *
 * Les livres se groupent par sorte, dans l'ordre du module ; au possessif, les sortes qui ont le leur d'abord (« ses
 * essais, un roman ») ; les œuvres réunies et les livres sans sorte précise à la fin (« et deux autres livres »). Un
 * livre seul dit son auteur et ce dont il parle (« un essai de Stefan Zweig sur Dostoïevski ») ; un groupe dit son
 * auteur quand il est le même pour tous, nomme chacun quand ils sont peu nombreux, ou cite le plus connu (« trois
 * recueils de poèmes, dont {{Requiem}} d'Akhmatova »), tant que la phrase reste courte (vingt mots) ; au-delà, on
 * retire des précisions. Un livre en russe se dit quand le livre de la fiche ne l'est pas. Une phrase qui ne dirait
 * qu'un groupe nu (« Deux romans. ») ne dit rien de plus que les vignettes : elle reste vide.
 *
 * @param int[] $ids     Les livres.
 * @param array $ix      L'index.
 * @param array $options personne (la personne du module : ses livres disent « ses… », ou ne la nomment pas),
 *                       possessif (ses livres au possessif), sur (les livres sur elle le disent), langue (la langue du
 *                       livre de la fiche : fr, ru, bi), sans_langues, contenus (identifiants), maintenant, page,
 *                       toujours (même une phrase nue).
 * @return string
 */
function er_continuer_inventaire( $ids, $ix, $options = array() ) {
	$o       = array_merge(
		array(
			'personne'     => 0,
			'possessif'    => false,
			'sur'          => false,
			'langue'       => 'fr',
			'sans_langues' => false,
			'contenus'     => array(),
			'maintenant'   => time(),
			'page'         => 'fr',
			'toujours'     => false,
		),
		$options
	);
	$moi     = (int) $o['personne'];
	$sortes  = er_continuer_sortes();
	$groupes = array();
	foreach ( $ids as $i ) {
		if ( ! isset( $ix['livres'][ $i ] ) ) {
			continue;
		}
		$l     = $ix['livres'][ $i ];
		$sorte = er_continuer_sorte( $l, $ix['rayons'] );
		$a     = array_map( 'intval', er_continuer_champ( $l, 'a' ) );
		$ab    = array_map( 'intval', er_continuer_champ( $l, 'ab' ) );
		$cle   = 'revue' === $sorte ? 'revue:' . er_continuer_revue( $l['t'] ) : $sorte;
		if ( ! isset( $groupes[ $cle ] ) ) {
			$groupes[ $cle ] = array(
				'sorte'   => $sorte,
				'livres'  => array(),
				'auteurs' => array(),
				'langues' => array(),
				'sur'     => array(),
				'sujets'  => array(),
			);
		}
		$groupes[ $cle ]['livres'][]  = $i;
		$groupes[ $cle ]['auteurs'][] = $a ? $a[0] : 0;
		$groupes[ $cle ]['langues'][] = er_continuer_langue_du_livre( $l );
		$groupes[ $cle ]['sur'][]     = $moi && in_array( $moi, $ab, true ) && ! in_array( $moi, $a, true );
		$groupes[ $cle ]['sujets'][]  = $ab ? $ab[0] : 0;
	}
	if ( ! $groupes && ! $o['contenus'] ) {
		return '';
	}
	// L'ordre : au possessif, ses sortes à lui d'abord ; les œuvres réunies, puis les livres sans sorte, à la fin.
	if ( $o['possessif'] ) {
		$a_lui = array();
		$reste = array();
		foreach ( $groupes as $cle => $g ) {
			$siens = $moi && array( $moi ) === array_values( array_unique( array_filter( $g['auteurs'] ) ) ) && ! in_array( 0, $g['auteurs'], true );
			if ( $siens && '' !== $sortes[ $g['sorte'] ][4] && 'oeuvres' !== $g['sorte'] && 'livre' !== $g['sorte'] ) {
				$a_lui[ $cle ] = $g;
			} else {
				$reste[ $cle ] = $g;
			}
		}
		$groupes = $a_lui + $reste;
	}
	foreach ( array( 'oeuvres', 'livre' ) as $fin ) {
		if ( isset( $groupes[ $fin ] ) && count( $groupes ) > 1 ) {
			$g = $groupes[ $fin ];
			unset( $groupes[ $fin ] );
			$groupes[ $fin ] = $g;
		}
	}
	// Les livres sur la personne du module disent « sur elle » une fois, après le dernier groupe qui en parle (« un
	// dictionnaire et deux essais sur Poutine ») ; une biographie, une vie de saint le disent autrement (« trois
	// récits de sa vie »).
	$dernier_sur = '';
	foreach ( $groupes as $cle => $g ) {
		if ( $o['sur'] && $moi && ! in_array( false, $g['sur'], true ) && ! in_array( $g['sorte'], array( 'biographie', 'vie-de-saint' ), true ) ) {
			$dernier_sur = $cle;
		}
	}
	$tous_sur = false;
	$langue_fiche = 'ru' === $o['langue'] ? 'ru' : 'fr';
	$connu        = fn( $p ) => ( isset( $ix['personnes'][ $p ]['nb'] ) ? (int) $ix['personnes'][ $p ]['nb'] : 0 ) >= 5;
	$dicible      = fn( $p ) => $p && er_continuer_nom_dicible( $p, $ix, $o['page'] );
	$court        = fn( $p ) => er_continuer_nom_court( er_continuer_nom( $p, $ix, $o['page'] ) );
	// Le sujet d'un livre : court s'il est connu (« sur Dostoïevski »), entier sinon (« une biographie de Larissa
	// Andersen », qu'on ne prenne pas pour celui des contes). Connu comme sujet : auteur de cinq livres, ou sujet de
	// trois (Poutine, Koltchak).
	$carte_s      = er_continuer_carte( $ix );
	$sujet_nom    = fn( $p ) => ( $connu( $p ) || count( isset( $carte_s['autour'][ $p ] ) ? $carte_s['autour'][ $p ] : array() ) >= 3 ) ? $court( $p ) : er_continuer_nom( $p, $ix, $o['page'] );
	$nom_de       = function ( $personnes ) use ( $ix, $o, $connu, $court ) {
		// Une même personne enregistrée deux fois (« Chalamov », « Varlam Chalamov ») ne se nomme qu'une fois.
		$vus        = array();
		$personnes  = array_values(
			array_filter(
				$personnes,
				function ( $p ) use ( &$vus, $court ) {
					$k = mb_strtolower( $court( $p ) );
					if ( isset( $vus[ $k ] ) ) {
						return false;
					}
					$vus[ $k ] = true;
					return true;
				}
			)
		);
		// Des noms de même forme : tous courts quand tous sont connus, sinon tous entiers.
		$courts = ! array_filter( $personnes, fn( $p ) => ! $connu( $p ) );
		return er_continuer_liste( array_map( fn( $p ) => er_continuer_de( $courts ? $court( $p ) : er_continuer_nom( $p, $ix, $o['page'] ) ), $personnes ) );
	};
	$avec_auteur = function ( $texte, $de ) {
		// « un livre sur la musique » + « de Sylvie Mamy » → « un livre de Sylvie Mamy sur la musique ».
		if ( preg_match( '/^(.*?)( sur .+)$/u', $texte, $m ) ) {
			return $m[1] . ' ' . $de . $m[2];
		}
		return $texte . ' ' . $de;
	};
	// Les niveaux de détail, du plus précis au plus sobre : [ les auteurs des groupes, les auteurs des livres seuls
	// (oui, ou seulement les connus), ce dont parlent les livres seuls, le plus connu d'un groupe ].
	$niveaux = array(
		array( true, 'oui', true, true ),
		array( true, 'oui', false, true ),
		array( false, 'oui', true, true ),
		array( false, 'oui', false, true ),
		array( false, 'connus', false, false ),
		array( false, 'non', false, false ),
	);
	$rendre = function ( $niveau ) use ( $groupes, $sortes, $o, $ix, $moi, $connu, $dicible, $court, $sujet_nom, $nom_de, $avec_auteur, $langue_fiche, $tous_sur, $dernier_sur ) {
		$sujets_dits = array(); // « sur Dostoïevski » se dit une fois par phrase.
		list( $auteurs_groupes, $auteurs_seuls, $de_quoi, $dont_ok ) = $niveau;
		$morceaux = array();
		$autres   = count( $groupes ) > 1;
		$precis   = false;
		foreach ( $groupes as $cle_g => $g ) {
			$sorte = $g['sorte'];
			list( $un, $plusieurs, $feminin, $seul, $possessif ) = $sortes[ $sorte ];
			$n       = count( $g['livres'] );
			// Une même personne enregistrée deux fois (« Chalamov », « Varlam Chalamov ») compte pour une.
			$auteurs = array();
			foreach ( array_unique( array_filter( $g['auteurs'] ) ) as $p ) {
				$auteurs[ mb_strtolower( $court( $p ) ) ] = $p;
			}
			$auteurs = array_values( $auteurs );
			$siens   = $moi && array( $moi ) === $auteurs && ! in_array( 0, $g['auteurs'], true );
			$sur_lui = $o['sur'] && ! in_array( false, $g['sur'], true );
			if ( 'revue' === $sorte ) {
				// Sur une page en français, le titre français d'une revue russe (« le Messager du Mouvement chrétien
				// russe » pour le Вестник).
				$lr      = $ix['livres'][ $g['livres'][0] ];
				$nom_r   = 'ru' !== $o['page'] && ! empty( $lr['fr'] ) ? er_continuer_revue( $lr['fr'] ) : er_continuer_revue( $lr['t'] );
				$texte   = ( 1 === $n ? 'un numéro ' : er_continuer_nombre( $n ) . ' numéros ' ) . er_continuer_de( er_continuer_titre( $nom_r ) );
				$auteurs = array();
				$precis  = true;
			} elseif ( $o['possessif'] && $siens ) {
				if ( 'oeuvres' === $sorte ) {
					$texte = 1 === $n ? 'un volume de ses œuvres' : 'des volumes de ses œuvres';
				} elseif ( 'livre' === $sorte ) {
					$texte = 1 === $n ? ( $autres ? 'un autre de ses livres' : 'un de ses livres' ) : ( $autres ? 'd’autres de ses livres' : 'deux de ses livres' );
				} elseif ( '' !== $possessif && ( $n > 1 || '' !== $seul || in_array( $sorte, array( 'theatre', 'journal', 'correspondance' ), true ) ) ) {
					$texte = $possessif;
				} elseif ( $n > 1 ) {
					$texte = er_continuer_nombre( $n, $feminin ) . ' ' . $plusieurs;
				} else {
					$texte = '' !== $seul ? $seul : er_continuer_nombre( 1, $feminin ) . ' ' . $un;
				}
				$auteurs = array();
				$precis  = true;
			} elseif ( 'livre' === $sorte && $autres ) {
				$texte = 1 === $n ? 'un autre livre' : er_continuer_nombre( $n ) . ' autres livres';
				if ( 1 !== $n ) {
					$auteurs = array();
				}
			} else {
				$texte = 1 === $n ? ( '' !== $seul ? $seul : er_continuer_nombre( 1, $feminin ) . ' ' . $un ) : er_continuer_nombre( $n, $feminin ) . ' ' . $plusieurs;
				if ( $siens ) {
					$auteurs = array(); // La personne du module : on ne la redit pas.
				}
			}
			// Une biographie se dit par son sujet (« une biographie de Joukovski »), et son auteur vient après « par ».
			$par    = false;
			$sujets = array_values( array_unique( $g['sujets'] ) );
			if ( in_array( $sorte, array( 'biographie', 'vie-de-saint' ), true ) && ! $sur_lui ) {
				if ( 1 === count( $sujets ) && $dicible( $sujets[0] ) && $moi !== $sujets[0] ) {
					$texte .= ' ' . er_continuer_de( $sujet_nom( $sujets[0] ) );
					$par    = true;
					$precis = true;
				} else {
					$auteurs = array();
				}
			}
			$complets = ! in_array( 0, $g['auteurs'], true );
			$nommes   = array_values( array_filter( $auteurs, $dicible ) );
			$noms     = array();
			$dont     = '';
			if ( $nommes && $complets && count( $nommes ) === count( $auteurs ) ) {
				if ( 1 === count( $nommes ) && ( 1 === $n ? ( 'oui' === $auteurs_seuls || ( 'connus' === $auteurs_seuls && $connu( $nommes[0] ) ) ) : 'non' !== $auteurs_seuls ) ) {
					$noms = $nommes;
				} elseif ( $auteurs_groupes && count( $nommes ) <= 3 ) {
					$noms = $nommes;
				}
			}
			if ( ! $noms && $n > 1 && $dont_ok && 'revue' !== $sorte && ! $siens ) {
				// Un groupe qu'on ne peut pas nommer en entier : on cite le livre de l'auteur le plus connu.
				foreach ( $g['livres'] as $k => $i ) {
					$p = $g['auteurs'][ $k ];
					if ( $p && $connu( $p ) && $dicible( $p ) && $moi !== $p ) {
						$titre = er_continuer_titre_court( $ix['livres'][ $i ]['t'], 30 );
						if ( '' !== $titre ) {
							$dont = ', dont ' . er_continuer_titre( $titre ) . ' ' . er_continuer_de( $court( $p ) );
						}
						break;
					}
				}
			}
			$coupe = '' !== $dont;
			if ( $noms && $par ) {
				$texte .= ' par ' . er_continuer_liste( array_map( fn( $p ) => er_continuer_nom_dans_phrase( er_continuer_nom( $p, $ix, $o['page'] ) ), $noms ) );
				$coupe  = count( $noms ) > 1;
			} elseif ( $noms && count( $noms ) > 1 ) {
				// Plusieurs auteurs : entre virgules, pour que le « et » de leur liste ne se confonde pas avec celui
				// de la phrase (« trois romans, de A, de B et de C, et un récit de D »).
				$texte .= ', ' . $nom_de( $noms );
				$coupe  = true;
			} elseif ( $noms ) {
				$texte = $avec_auteur( $texte, $nom_de( $noms ) );
			}
			// Ce dont parle un livre seul : « un essai de Stefan Zweig sur Dostoïevski ».
			if ( $de_quoi && 1 === $n && ! $par && ! $sur_lui && in_array( $sorte, array( 'essai', 'etude', 'temoignage', 'memoires', 'entretiens', 'histoire', 'livre', 'dictionnaire', 'art', 'photographie', 'theologie', 'philosophie', 'spiritualite', 'actualite' ), true ) && false === strpos( $texte, ' sur ' ) && $sujets[0] && $dicible( $sujets[0] ) && $moi !== $sujets[0] && ! in_array( $sujets[0], $g['auteurs'], true ) && ! in_array( $sujets[0], $sujets_dits, true ) ) {
				// « un témoignage de Véra Nabokov sur Vladimir Nabokov », pas « … sur Nabokov » : le nom court se
				// confondrait avec celui de l'auteur.
				$sujet = $sujet_nom( $sujets[0] );
				if ( $noms && false !== mb_strpos( er_continuer_nu( $texte ), $sujet ) ) {
					$sujet = er_continuer_nom( $sujets[0], $ix, $o['page'] );
				}
				$texte        .= ' sur ' . $sujet;
				$sujets_dits[] = $sujets[0];
			}
			if ( $sur_lui && 'vie-de-saint' === $sorte ) {
				$texte   = 1 === $n ? 'un récit de sa vie' : er_continuer_nombre( $n ) . ' récits de sa vie';
				$auteurs = array();
			} elseif ( $sur_lui && 'biographie' === $sorte ) {
				$texte .= count( $groupes ) > 1 ? '' : ' ' . er_continuer_de( $court( $moi ) );
			} elseif ( $sur_lui && $cle_g === $dernier_sur ) {
				$texte .= ' sur ' . $court( $moi );
			}
			$sa_langue = array_values( array_unique( $g['langues'] ) );
			$russe     = ! $o['sans_langues'] && 'fr' === $langue_fiche && array( 'ru' ) === $sa_langue;
			$precis     = $precis || $noms || $dont || $par || $russe || false !== strpos( $texte, ' sur ' ) || ( 'livre' === $sorte && $autres );
			$morceaux[] = array( $texte . $dont, $coupe, $russe );
		}
		// Les livres en russe : un seul, il le dit (« un témoignage en russe ») ; plusieurs, ils viennent à la fin, après
		// « et, en russe, » (« un journal et, en russe, un témoignage et une biographie de Struve »).
		$contenus = array();
		foreach ( $o['contenus'] as $cid ) {
			if ( isset( $ix['contenus'][ $cid ] ) ) {
				$contenus[] = array( er_continuer_mots_contenu( $ix['contenus'][ $cid ], $o['maintenant'] ), false, false );
			}
		}
		$russes = array_values( array_filter( $morceaux, fn( $m ) => $m[2] ) );
		$queue  = array();
		if ( count( $russes ) >= 2 && count( $russes ) < count( $morceaux ) ) {
			$morceaux = array_merge( array_values( array_filter( $morceaux, fn( $m ) => ! $m[2] ) ), $contenus );
			$queue    = $russes;
		} else {
			foreach ( $morceaux as $k => $m ) {
				if ( $m[2] ) {
					$morceaux[ $k ][0] .= ' en russe';
				}
			}
			$morceaux = array_merge( $morceaux, $contenus );
		}
		// Un morceau qui porte sa propre liste se ferme par une virgule avant la suite.
		$joindre = function ( $liste ) {
			$phrase = '';
			foreach ( $liste as $k => list( $texte ) ) {
				if ( $k > 0 ) {
					$phrase .= ( count( $liste ) - 1 === $k ) ? ( $liste[ $k - 1 ][1] ? ', et ' : ' et ' ) : ', ';
				}
				$phrase .= $texte;
			}
			return $phrase;
		};
		$phrase = $joindre( $morceaux ) . ( $queue ? ( end( $morceaux )[1] ? ', et, en russe, ' : ' et, en russe, ' ) . $joindre( $queue ) : '' );
		if ( $tous_sur ) {
			$phrase .= ' sur ' . $court( $moi );
			$precis  = true;
		}
		return array( $phrase, $precis || count( $morceaux ) > 1 );
	};
	$phrase = '';
	$precis = false;
	foreach ( $niveaux as $niveau ) {
		list( $phrase, $precis ) = $rendre( $niveau );
		if ( count( preg_split( '/\s+/u', trim( er_continuer_nu( $phrase ) ) ) ) <= 20 ) {
			break;
		}
	}
	if ( ! $precis && ! $o['toujours'] ) {
		return '';
	}
	// Tous en russe, plusieurs groupes, sur la fiche d'un livre français : on le dit une fois, à la fin.
	if ( ! $o['sans_langues'] && 'fr' === $langue_fiche && count( $groupes ) > 1 && ! array_diff( array_merge( ...array_column( $groupes, 'langues' ) ), array( 'ru' ) ) ) {
		$phrase = str_replace( ' en russe', '', $phrase ) . ', en russe';
	}
	return er_continuer_phrase( $phrase );
}

/* Les thèmes ---------------------------------------------------------------------------------------------------- */

/**
 * Ce qu'on dit des sujets du site : nom du sujet → [ le titre d'un module, ce qu'on dit de ses livres (« sur
 * l'orthodoxie », « de poésie »), lieu ]. Un sujet absent d'ici garde son nom, et ses livres sont « sur le même
 * thème ».
 */
function er_continuer_sujets_mots() {
	return array(
		'Orthodoxie'                      => array( 'L’orthodoxie', 'sur l’orthodoxie', false ),
		'Émigration'                      => array( 'L’émigration russe', 'sur l’émigration', false ),
		'Pouvoir et politique'            => array( 'Le pouvoir et la politique', 'sur le pouvoir', false ),
		'Poésie'                          => array( 'La poésie', 'de poésie', false ),
		'Guerre'                          => array( 'La guerre', 'sur la guerre', false ),
		'Goulag et dissidence'            => array( 'Le Goulag et la dissidence', 'sur le Goulag et la dissidence', false ),
		'Philosophie'                     => array( 'La philosophie', 'de philosophie', false ),
		'Femmes'                          => array( 'Des destins de femmes', 'sur des destins de femmes', false ),
		'Russie et Europe'                => array( 'La Russie et l’Europe', 'sur la Russie et l’Europe', false ),
		'Critique et histoire littéraire' => array( 'L’histoire littéraire', 'd’histoire littéraire', false ),
		'Enfance et famille'              => array( 'L’enfance et la famille', 'sur l’enfance et la famille', false ),
		'Saints et monastères'            => array( 'Les saints et les monastères', 'sur les saints et les monastères', false ),
		'Humour et satire'                => array( 'L’humour et la satire', 'd’humour et de satire', false ),
		'Moscou'                          => array( 'Moscou', 'sur Moscou', true ),
		'Ukraine'                         => array( 'L’Ukraine', 'sur l’Ukraine', true ),
		'Amour'                           => array( 'L’amour', 'sur l’amour', false ),
		'Saint-Pétersbourg'               => array( 'Saint-Pétersbourg', 'sur Saint-Pétersbourg', true ),
		'Fantastique et anticipation'     => array( 'Le fantastique et l’anticipation', 'de fantastique et d’anticipation', false ),
		'Arts'                            => array( 'Les arts', 'sur les arts', false ),
		'Langue russe'                    => array( 'La langue russe', 'sur la langue russe', false ),
		'Paysans et campagne'             => array( 'La campagne russe', 'sur la campagne russe', false ),
		'Caucase et Asie centrale'        => array( 'Le Caucase et l’Asie centrale', 'sur le Caucase et l’Asie centrale', true ),
		'Théâtre'                         => array( 'Le théâtre', 'sur le théâtre', false ),
		'Sibérie et Grand Nord'           => array( 'La Sibérie et le Grand Nord', 'sur la Sibérie et le Grand Nord', true ),
		'Contes et légendes'              => array( 'Les contes et les légendes', 'de contes et de légendes', false ),
		'Musique'                         => array( 'La musique', 'sur la musique', false ),
		'Cinéma'                          => array( 'Le cinéma', 'sur le cinéma', false ),
		'Traduction'                      => array( 'La traduction', 'sur la traduction', false ),
		'Juifs de Russie'                 => array( 'Les Juifs de Russie', 'sur les Juifs de Russie', false ),
		'Histoire de la maison'           => array( 'L’histoire de la maison', 'sur l’histoire de la maison', false ),
	);
}

/**
 * Le titre de revue d'un numéro, sans son numéro : « Le Messager orthodoxe », « Вестник РХД ».
 *
 * @param string $titre Le titre du numéro.
 */
function er_continuer_revue( $titre ) {
	return trim( (string) preg_replace( '/[\s,.–—-]*(?:n°|no|№|выпуск|вып\.|tome|том|t\.)\s*[0-9IVXLC]+.*$/iu', '', (string) $titre ) );
}

/* Le choix des modules ------------------------------------------------------------------------------------------ */

/**
 * Un titre de revue ou d'ouvrage générique, qu'une notice peut nommer sans parler de ce livre-là (« Poèmes »,
 * « Œuvres », « Стихотворения »).
 *
 * @param string $titre Le titre.
 */
function er_continuer_titre_generique( $titre ) {
	$base = trim( preg_split( '/\s[:—–-]\s|[:(.]/u', (string) $titre )[0] );
	return (bool) preg_match( '/^(les |des )?(po[èe]mes( choisis)?|po[ée]sies?( choisies)?|œuvres( choisies| complètes)?|oeuvres|nouvelles|récits|contes|théâtre|journal|lettres|mémoires|souvenirs|carnets|correspondance|essais|textes|écrits|choix de textes|anthologie|стихи|стихотворения|избранное|рассказы|повести|сказки|сочинения|собрание сочинений|пьесы|письма|дневник|дневники|воспоминания)$/iu', $base );
}

/**
 * Le titre court d'un livre, pour une phrase : avant le sous-titre (« : », « – », « , », « suivi de », « / ») ; vide
 * quand il reste trop long.
 *
 * @param string $titre Le titre.
 * @param int    $max   La longueur au plus.
 */
function er_continuer_titre_court( $titre, $max = 48 ) {
	$t = trim( (string) $titre );
	// « Les songes de Tchang. Poèmes » : un mot générique après un point tombe.
	$t = (string) preg_replace( '/\.\s+(po[èe]mes|poésies|nouvelles|récits|roman|essais?|contes|стихи|стихотворения|рассказы|повесть|роман)\s*$/iu', '', $t );
	foreach ( array( '/\s[:—–-]\s/u', '/\s-(?=\p{Lu})/u', '/\s*\/\s*/u', '/\ssuivi(e|s|es)? (de\s|d’\s?)/iu', '/,\s/u', '/:\s/u', '/\s\(/u' ) as $coupe ) {
		$morceaux = preg_split( $coupe, $t, 2 );
		// Avant une virgule ou une parenthèse, au moins trois mots, deux : « Anna Akhmatova, requiem pour l'Europe »
		// garde son titre entier, « Poèmes (1961-1987) » aussi.
		$mots_avant = substr_count( trim( $morceaux[0] ), ' ' ) + 1;
		if ( count( $morceaux ) > 1 && mb_strlen( trim( $morceaux[0] ) ) >= 3 && ( '/,\s/u' !== $coupe || $mots_avant >= 3 ) && ( '/\s\(/u' !== $coupe || $mots_avant >= 2 ) ) {
			$t = trim( $morceaux[0] );
		}
	}
	return mb_strlen( $t ) <= $max ? $t : '';
}

/**
 * Ce qu'on dit, dans la ligne d'ouverture, d'une série de livres : « des romans », « des poèmes », « des livres ».
 *
 * @param int[] $ids Les livres.
 * @param array $ix  L'index.
 * @return array [ le groupe nominal, féminin, la sorte ]
 */
function er_continuer_des( $ids, $ix ) {
	$compte = array_count_values( array_map( fn( $i ) => er_continuer_sorte( $ix['livres'][ $i ], $ix['rayons'] ), $ids ) );
	arsort( $compte );
	$sorte = (string) key( $compte );
	// Une sorte qui fait tout le module, ou tout sauf un livre sur trois ou quatre.
	// « des livres sur la musique sur Moscou » ne se dit pas : une sorte qui porte déjà son « sur » laisse « des livres ».
	if ( $compte && ! in_array( $sorte, array( 'livre', 'revue', 'theatre', 'oeuvres' ), true ) && false === strpos( er_continuer_sortes()[ $sorte ][1], ' sur ' ) && ( 1 === count( $compte ) || ( count( $ids ) >= 3 && reset( $compte ) >= count( $ids ) - 1 ) ) ) {
		list( , $plusieurs, $feminin, $seul ) = er_continuer_sortes()[ $sorte ];
		return array( 0 === strpos( $seul, 'des ' ) ? $seul : 'des ' . $plusieurs, $feminin, $sorte );
	}
	return array( 'des livres', false, 'livre' );
}

/**
 * Les modules d'un livre, et la ligne qui les annonce.
 *
 * Un module : type, sur (le surtitre : ce qui le relie au livre), titre, ligne (ce qu'il contient), fragment (ce
 * qu'en dit la ligne d'ouverture), livres, contenus, forme (vedette, vignettes, ligne, deux-langues, contenus),
 * sans_titre (la vedette ne redit pas un titre que le module porte déjà), secondaire, fond, lien (la page où aller
 * plus loin encore : [ classement, terme ], une personne, une époque, un sujet, un rayon).
 *
 * Au plus trois modules principaux, dans cet ordre : l'autre langue, les autres volumes (ou les autres numéros d'une
 * revue), le livre que cite la notice, ce qu'on a écrit sur l'auteur, la personne dont parle le livre puis son
 * œuvre, les mêmes années, la personne que nomme la notice, le même thème, le même rayon (à défaut d'autre chose) ;
 * et des secondaires, plus discrets : les autres éditions dans la même langue, le traducteur (ou le préfacier, un
 * contributeur), une rencontre ou un article qui cite le livre. Un livre ne paraît qu'une fois.
 *
 * @param int   $id  Le livre.
 * @param array $ix  L'index (er_continuer_index).
 * @param array $ctx notice ([ [ phrase, clé ] ]), cites ([ [ personne, intervient ] ]), langue (de la page),
 *                   maintenant.
 * @return array ouverture, modules.
 */
function er_continuer_choisir( $id, $ix, $ctx = array() ) {
	$vide = array(
		'ouverture' => '',
		'modules'   => array(),
	);
	if ( ! isset( $ix['livres'][ $id ] ) ) {
		return $vide;
	}
	$ctx     = array_merge(
		array(
			'notice'     => array(),
			'cites'      => array(),
			'langue'     => 'fr',
			'maintenant' => time(),
		),
		$ctx
	);
	$l       = $ix['livres'][ $id ];
	$carte   = er_continuer_carte( $ix );
	$page    = $ctx['langue'];
	$auteurs = array_map( 'intval', er_continuer_champ( $l, 'a' ) );
	$autour  = array_values( array_diff( array_map( 'intval', er_continuer_champ( $l, 'ab' ) ), $auteurs ) );
	$roles   = isset( $l['r'] ) ? (array) $l['r'] : array();
	$gens    = array_merge( $auteurs, $autour );
	foreach ( $roles as $personnes ) {
		$gens = array_merge( $gens, array_map( 'intval', $personnes ) );
	}
	$langue  = er_continuer_langue_du_livre( $l );
	$sorte   = er_continuer_sorte( $l, $ix['rayons'] );
	$revue   = 'revue' === $sorte;
	$pris    = array( $id );
	$c_pris  = array();
	$du_meme = array();
	foreach ( $auteurs as $a ) {
		$du_meme = array_merge( $du_meme, isset( $carte['auteur'][ $a ] ) ? $carte['auteur'][ $a ] : array() );
	}
	$du_meme  = array_values( array_unique( $du_meme ) );
	$nom      = fn( $p ) => er_continuer_nom( $p, $ix, $page );
	$court    = fn( $p ) => er_continuer_nom_court( er_continuer_nom( $p, $ix, $page ) );
	$titre_l  = fn( $i ) => er_continuer_titre( $ix['livres'][ $i ]['t'] );
	$de_carte = fn( $cle, $k ) => isset( $carte[ $cle ][ $k ] ) ? $carte[ $cle ][ $k ] : array();
	$restants = function ( $ids ) use ( &$pris ) {
		return array_values( array_diff( array_unique( array_map( 'intval', $ids ) ), $pris ) );
	};
	$inv      = function ( $ids, $o = array() ) use ( $ix, $langue, $ctx, $page ) {
		return er_continuer_inventaire(
			$ids,
			$ix,
			array_merge(
				array(
					'langue'     => $langue,
					'maintenant' => $ctx['maintenant'],
					'page'       => $page,
				),
				$o
			)
		);
	};
	// Les articles, rencontres, vidéos qui portent ces personnes, dans la langue de la page : ceux qui citent le livre
	// d'abord, puis les rencontres à revoir, puis les plus récents.
	$contenus_de = function ( $personnes, $n = 1 ) use ( $ix, $carte, $ctx, &$c_pris, $id ) {
		$scores = array();
		foreach ( $personnes as $p ) {
			foreach ( isset( $carte['c_pers'][ $p ] ) ? $carte['c_pers'][ $p ] : array() as $cid ) {
				$co = $ix['contenus'][ $cid ];
				if ( in_array( $cid, $c_pris, true ) || ( isset( $co['lg'] ) && '' !== $co['lg'] && $co['lg'] !== $ctx['langue'] ) ) {
					continue;
				}
				$avant          = isset( $scores[ $cid ] ) ? $scores[ $cid ][0] : 0;
				$scores[ $cid ] = array(
					$avant + 3 + ( in_array( $id, er_continuer_champ( $co, 'lv' ), true ) ? 8 : 0 ) + ( ! empty( $co['vi'] ) ? 1 : 0 ),
					isset( $co['deb'] ) ? (int) strtotime( (string) $co['deb'] ) : (int) $co['d'],
				);
			}
		}
		uasort( $scores, fn( $a, $b ) => $b <=> $a );
		return array_slice( array_keys( $scores ), 0, $n );
	};
	$principaux  = array();
	$secondaires = array();
	$ajouter     = function ( $m, $secondaire = false ) use ( &$principaux, &$secondaires, &$pris, &$c_pris ) {
		if ( $secondaire ) {
			$secondaires[] = $m;
		} else {
			$principaux[] = $m;
		}
		$pris   = array_merge( $pris, isset( $m['livres'] ) ? $m['livres'] : array() );
		$c_pris = array_merge( $c_pris, isset( $m['contenus'] ) ? $m['contenus'] : array() );
	};

	// 1. Le même livre dans l'autre langue, et ses autres éditions.
	$ed     = er_continuer_editions( $id, $ix );
	$autres = array();
	$memes  = array();
	foreach ( $ed['editions'] as $o ) {
		if ( er_continuer_langue_du_livre( $ix['livres'][ $o ] ) !== $langue ) {
			$autres[] = $o;
		} else {
			$memes[] = $o;
		}
	}
	$parution = function ( $o ) use ( $ix, $page ) {
		$lo = $ix['livres'][ $o ];
		$ed = isset( $lo['ed'] ) ? str_replace( "'", '’', trim( (string) $lo['ed'] ) ) : '';
		$an = isset( $lo['an'] ) && preg_match( '/\b(1[5-9]\d\d|20\d\d)\b/', (string) $lo['an'], $m ) ? $m[1] : '';
		// Un éditeur resté en cyrillique (« Русский путь ») ne se dit pas dans une phrase française : l'année seule.
		if ( 'ru' !== $page && preg_match( '/\p{Cyrillic}/u', $ed ) ) {
			$ed = '';
		}
		// « aux éditions des Syrtes », « chez Gallimard ».
		$chez = preg_match( '/^(les )?éditions\s+(.+)$/iu', $ed, $me ) ? 'aux éditions ' . $me[2] : ( '' !== $ed ? 'chez ' . $ed : '' );
		return trim( $chez . ( '' !== $an ? ' en ' . $an : '' ) );
	};
	if ( $autres ) {
		$o    = er_continuer_ranger( $autres, $ix, $id )[0];
		$lo   = er_continuer_langue_du_livre( $ix['livres'][ $o ] );
		$trad = isset( $ix['livres'][ $o ]['r']['traduction'] ) ? array_map( 'intval', $ix['livres'][ $o ]['r']['traduction'] ) : array();
		$m    = array(
			'type'   => 'langue',
			'sur'    => 'Le même livre',
			'livres' => array( $o ),
			'forme'  => 'vedette',
			'ligne'  => '',
		);
		if ( 'bi' === $lo ) {
			$m['titre']    = 'En édition bilingue';
			$m['fragment'] = 'l’édition bilingue';
			$m['ligne']    = 'Le texte russe et sa traduction en regard.';
		} elseif ( 'ru' === $lo && ! empty( $roles['traduction'] ) ) {
			$m['sur']      = 'En version originale';
			$m['titre']    = 'Le texte russe';
			$m['fragment'] = 'le texte russe';
		} elseif ( 'fr' === $lo && $trad ) {
			$m['sur']      = 'En traduction';
			$m['titre']    = 'La traduction française';
			$m['fragment'] = 'sa traduction française';
			$m['ligne']    = er_continuer_phrase( 'dans la traduction ' . er_continuer_liste( array_map( fn( $p ) => er_continuer_de( $nom( $p ) ), $trad ) ) );
		} elseif ( 'ru' === $lo ) {
			$m['titre']    = 'En russe';
			$m['fragment'] = 'l’édition russe';
		} else {
			$m['titre']    = 'En français';
			$m['fragment'] = 'l’édition française';
		}
		if ( '' === $m['ligne'] && '' !== $parution( $o ) ) {
			$m['ligne'] = er_continuer_phrase( 'paru ' . $parution( $o ) );
		}
		$ajouter( $m );
	}

	// 2. Les autres volumes ; pour une revue, les numéros voisins.
	if ( $revue ) {
		$nom_revue = er_continuer_revue( $l['t'] );
		list( $n_courant ) = er_continuer_numero( $l['t'] );
		$numeros   = array();
		foreach ( $ix['livres'] as $o => $lo ) {
			if ( $o !== $id && 'revue' === er_continuer_sorte( $lo, $ix['rayons'] ) && er_continuer_revue( $lo['t'] ) === $nom_revue ) {
				$numeros[ $o ] = er_continuer_numero( $lo['t'] )[0];
			}
		}
		$numeros = array_intersect_key( $numeros, array_flip( $restants( array_keys( $numeros ) ) ) );
		if ( $numeros ) {
			// Les plus proches de celui-ci, en stock d'abord.
			uksort(
				$numeros,
				function ( $a, $b ) use ( $numeros, $n_courant, $ix ) {
					$da = array( empty( $ix['livres'][ $a ]['st'] ) ? 1 : 0, $n_courant && $numeros[ $a ] ? abs( $numeros[ $a ] - $n_courant ) : 999, -$numeros[ $a ] );
					$db = array( empty( $ix['livres'][ $b ]['st'] ) ? 1 : 0, $n_courant && $numeros[ $b ] ? abs( $numeros[ $b ] - $n_courant ) : 999, -$numeros[ $b ] );
					return $da <=> $db;
				}
			);
			$choix = array_slice( $numeros, 0, 4, true );
			ksort( $choix );
			uksort( $choix, fn( $a, $b ) => $choix[ $a ] <=> $choix[ $b ] );
			$noms_n = array();
			foreach ( array_keys( $choix ) as $o ) {
				if ( preg_match( '/(?:n°|no|№|выпуск)\s*([0-9]+(?:\s*[-–]\s*[0-9]+)?)/iu', $ix['livres'][ $o ]['t'], $mn ) ) {
					$noms_n[] = preg_replace( '/\s*[-–]\s*/u', '-', $mn[1] );
				}
			}
			$ajouter(
				array(
					'type'     => 'numeros',
					'sur'      => 'La même revue',
					'titre'    => 1 === count( $choix ) ? 'Un autre numéro' : 'D’autres numéros',
					'ligne'    => count( $noms_n ) === count( $choix ) ? er_continuer_phrase( ( 1 === count( $noms_n ) ? 'le numéro ' : 'les numéros ' ) . er_continuer_liste( $noms_n ) ) : '',
					'fragment' => 'd’autres numéros de la revue',
					'livres'   => array_keys( $choix ),
					'forme'    => 1 === count( $choix ) ? 'vedette' : 'vignettes',
				)
			);
		}
	} elseif ( $ed['volumes'] ) {
		list( $n_courant, $mot ) = er_continuer_numero( $l['t'] );
		$volumes                 = array();
		foreach ( $restants( $ed['volumes'] ) as $o ) {
			$volumes[ $o ] = er_continuer_numero( $ix['livres'][ $o ]['t'] );
			if ( '' === $mot ) {
				$mot = $volumes[ $o ][1];
			}
		}
		// Les volumes de la même partie d'abord (« Красное Колесо – Апрель Семнадцатого », tomes 1 et 2), puis dans
		// l'ordre des numéros.
		$partie = fn( $t ) => trim( (string) preg_replace( '/(?:tome|t\.|том|т\.|vol\.?|volume|livre|книга|кн\.|partie|часть)\s*[0-9IVXLC]+\b.*$/iu', '', (string) $t ) );
		$la_mienne = $partie( $l['t'] );
		uksort(
			$volumes,
			function ( $a, $b ) use ( $volumes, $partie, $la_mienne, $ix ) {
				$pa = $partie( $ix['livres'][ $a ]['t'] ) === $la_mienne ? 0 : 1;
				$pb = $partie( $ix['livres'][ $b ]['t'] ) === $la_mienne ? 0 : 1;
				return array( $pa, 0 === $volumes[ $a ][0] ? 999 : $volumes[ $a ][0], $a ) <=> array( $pb, 0 === $volumes[ $b ][0] ? 999 : $volumes[ $b ][0], $b );
			}
		);
		$volumes = array_slice( $volumes, 0, 4, true );
		if ( $volumes ) {
			$mot     = '' !== $mot && 'numéro' !== $mot ? $mot : 'volume';
			$feminin = 'partie' === $mot;
			$numeros = array_column( $volumes, 0 );
			$m       = array(
				'type'   => 'volumes',
				'sur'    => 'Le même ouvrage',
				'livres' => array_keys( $volumes ),
				'forme'  => 1 === count( $volumes ) ? 'vedette' : 'vignettes',
				'ligne'  => '',
			);
			if ( 1 === count( $volumes ) && $numeros[0] > 0 && $numeros[0] <= 10 ) {
				$m['fragment'] = ( $feminin ? 'la ' : 'le ' ) . er_continuer_ordinal( $numeros[0], $feminin ) . ' ' . $mot;
				$m['titre']    = er_continuer_maj( $m['fragment'] );
				if ( $n_courant && $numeros[0] === $n_courant - 1 ) {
					$m['ligne'] = $feminin ? 'Celle qui précède celle-ci.' : 'Celui qui précède celui-ci.';
				} elseif ( $n_courant && $numeros[0] === $n_courant + 1 ) {
					$m['ligne']    = 'La suite.';
					$m['fragment'] = 'la suite';
				}
			} elseif ( 1 === count( $volumes ) ) {
				$m['titre']    = ( $feminin ? 'L’autre ' : 'L’autre ' ) . $mot;
				$m['fragment'] = 'l’autre ' . $mot;
			} else {
				$m['titre']    = 'Les autres ' . $mot . 's';
				$m['fragment'] = 'les autres ' . $mot . 's';
				if ( ! in_array( 0, $numeros, true ) && count( array_unique( $numeros ) ) === count( $numeros ) ) {
					sort( $numeros );
					$m['ligne'] = er_continuer_phrase( 'les ' . $mot . 's ' . er_continuer_liste( $numeros ) );
				}
			}
			$ajouter( $m );
		}
	}

	// 3. Les livres du même auteur que nomme la notice.
	$cle_notice = ' ' . implode( ' ', array_column( $ctx['notice'], 1 ) ) . ' ';
	$k_courant  = ' ' . ( isset( $l['k'] ) ? $l['k'] : '' ) . ' ';
	$trouves    = array();
	foreach ( $restants( $du_meme ) as $o ) {
		$lo = $ix['livres'][ $o ];
		if ( er_continuer_titre_generique( $lo['t'] ) || 'revue' === er_continuer_sorte( $lo, $ix['rayons'] ) ) {
			continue;
		}
		foreach ( array( 'k', 'kf' ) as $champ ) {
			$k = isset( $lo[ $champ ] ) ? trim( (string) $lo[ $champ ] ) : '';
			if ( strlen( $k ) < 6 || ( false === strpos( $k, ' ' ) && strlen( $k ) < 8 ) || false !== strpos( $k_courant, ' ' . $k . ' ' ) ) {
				continue;
			}
			$position = strpos( $cle_notice, ' ' . $k . ' ' );
			if ( false !== $position ) {
				$trouves[ $o ] = array( $position, $k );
				break;
			}
		}
	}
	// Un titre contenu dans un autre trouvé (« Kolyma » dans « Récits de la Kolyma ») n'est pas cité pour lui-même.
	foreach ( $trouves as $o => list( $pos, $k ) ) {
		foreach ( $trouves as $o2 => list( $pos2, $k2 ) ) {
			if ( $o !== $o2 && strlen( $k2 ) > strlen( $k ) && false !== strpos( ' ' . $k2 . ' ', ' ' . $k . ' ' ) ) {
				unset( $trouves[ $o ] );
				break;
			}
		}
	}
	uasort( $trouves, fn( $a, $b ) => $a[0] <=> $b[0] );
	$trouves = array_slice( array_keys( $trouves ), 0, 3 );
	if ( $trouves ) {
		$texte    = mb_strtolower( implode( ' ', array_column( $ctx['notice'], 0 ) ), 'UTF-8' );
		$ensemble = '';
		foreach ( array( 'diptyque', 'trilogie', 'tétralogie' ) as $mot ) {
			if ( false !== mb_strpos( $texte, $mot ) ) {
				$ensemble = $mot;
				break;
			}
		}
		$second  = (bool) preg_match( '/\b(deuxième|seconde?|dernière|dernier) (partie|volume|tome|volet)|\bfait suite\b|\bla suite (de|du|des)\b/u', $texte );
		$premier = ! $second && preg_match( '/\bpremi(er|ère) (partie|volume|tome|volet)\b/u', $texte );
		$un      = 1 === count( $trouves );
		// Les titres, chacun une fois : trois éditions du même livre ne font pas « A, A et A ».
		$titres = array();
		foreach ( $trouves as $o ) {
			$titres[ mb_strtolower( er_continuer_nu( $titre_l( $o ) ), 'UTF-8' ) ] = $titre_l( $o );
		}
		$titres = array_values( $titres );
		$m       = array(
			'type'       => 'cite',
			'livres'     => $trouves,
			'forme'      => $un ? 'vedette' : 'vignettes',
			'ligne'      => '',
			'sans_titre' => false,
		);
		if ( '' !== $ensemble ) {
			$m['sur'] = er_continuer_maj( ( 'diptyque' === $ensemble ? 'le ' : 'la ' ) . $ensemble );
			if ( $un && $second && 'diptyque' === $ensemble ) {
				$m['titre']    = 'Commencer par le premier volume';
				$m['ligne']    = er_continuer_phrase( $titre_l( $trouves[0] ) . ' ouvre le diptyque ; ' . $titre_l( $id ) . ' en est la suite' );
				$m['fragment'] = 'le premier volume du diptyque';
			} elseif ( $un && $premier && 'diptyque' === $ensemble ) {
				$m['titre']    = 'Et la suite';
				$m['ligne']    = er_continuer_phrase( 'le diptyque se poursuit avec ' . $titre_l( $trouves[0] ) );
				$m['fragment'] = 'la suite du diptyque';
			} else {
				$tout            = ( 'diptyque' === $ensemble ? 'du ' : 'de la ' ) . $ensemble;
				$m['titre']      = er_continuer_liste( $titres );
				$m['sans_titre'] = $un;
				$m['ligne']      = er_continuer_phrase( ( $un ? 'l’autre volet ' : 'les autres volets ' ) . $tout );
				$m['fragment']   = ( $un ? 'l’autre volet ' : 'les autres volets ' ) . $tout;
			}
		} elseif ( ! $un && 1 === count( $titres ) ) {
			// Un seul livre cité, que la librairie a en plusieurs éditions : le titre une fois, les éditions dans la ligne.
			$chez            = array_values( array_filter( array_map( $parution, $trouves ) ) );
			$n               = er_continuer_nombre( count( $trouves ), true );
			$m['sur']        = 'Cité dans la notice';
			$m['titre']      = $titres[0];
			$m['ligne']      = er_continuer_phrase( 'en ' . $n . ' éditions' . ( count( $chez ) === count( $trouves ) ? ' : ' . er_continuer_liste( $chez ) : '' ) );
			$m['fragment']   = $n . ' éditions ' . er_continuer_de( $titres[0] );
		} else {
			$m['sur']        = $un ? 'Cité dans la notice' : 'Cités dans la notice';
			$m['titre']      = er_continuer_liste( $titres );
			$m['sans_titre'] = $un;
			$m['fragment']   = $un ? $titre_l( $trouves[0] ) : 'les livres que cite la notice';
		}
		$ajouter( $m );
	}

	// 4. Ce qu'on a écrit sur l'auteur.
	foreach ( $auteurs as $a ) {
		$sur = array_values( array_diff( $restants( $de_carte( 'autour', $a ) ), $du_meme ) );
		if ( ! $sur ) {
			continue;
		}
		$cs     = $contenus_de( array( $a ) );
		$sur    = array_slice( er_continuer_une_edition( er_continuer_ranger( $sur, $ix, $id ), $ix ), 0, $cs ? 3 : 4 );
		$sortes = array_values( array_unique( array_map( fn( $i ) => er_continuer_sorte( $ix['livres'][ $i ], $ix['rayons'] ), $sur ) ) );
		// Un nom resté en cyrillique ne se met pas dans une phrase française : on dit « l'auteur ».
		$dit = er_continuer_nom_dicible( $a, $ix, $page );
		$qui = $dit ? $court( $a ) : '';
		if ( array( 'biographie' ) === $sortes ) {
			$titre = $dit ? 'La vie ' . er_continuer_de( $qui ) : 'Sa vie';
		} elseif ( ! array_diff( $sortes, array( 'temoignage', 'memoires', 'lettres', 'correspondance' ) ) ) {
			$titre = $dit ? 'Ceux qui ont connu ' . $qui : 'Ceux qui l’ont connu';
		} elseif ( ! array_diff( $sortes, array( 'essai', 'etude' ) ) ) {
			$titre = $dit ? 'Pour mieux lire ' . $qui : 'Pour mieux lire son œuvre';
		} else {
			$titre = $dit ? 'Ce qu’on a écrit sur ' . $qui : 'Ce qu’on en a écrit';
		}
		$de_qui  = $dit ? er_continuer_de( $qui ) : 'de l’auteur';
		$sur_qui = $dit ? 'sur ' . $qui : 'sur l’auteur';
		if ( 1 === count( $sur ) ) {
			$fragment = 'biographie' === $sortes[0] ? 'une biographie ' . $de_qui : er_continuer_inventaire_court( $sur, $ix ) . ' ' . $sur_qui;
		} else {
			list( $des ) = er_continuer_des( $sur, $ix );
			$fragment    = $des . ( 'des biographies' === $des ? ' ' . $de_qui : ' ' . $sur_qui );
		}
		$ajouter(
			array(
				'type'     => 'sur-auteur',
				'sur'      => $dit ? 'Sur ' . er_continuer_nom_dans_phrase( $nom( $a ) ) : 'Sur l’auteur',
				'titre'    => $titre,
				'ligne'    => $inv( $sur, array( 'personne' => $a, 'contenus' => $cs ) ),
				'fragment' => $fragment,
				'livres'   => $sur,
				'contenus' => $cs,
				'forme'    => count( $sur ) + count( $cs ) > 1 ? 'vignettes' : 'vedette',
				'personne' => $a,
				'lien'     => array( 'auteur', $a ),
			)
		);
		break;
	}

	// 5. La personne dont parle le livre : d'autres livres sur elle, puis son œuvre.
	usort( $autour, fn( $p, $q ) => count( $de_carte( 'autour', $q ) ) <=> count( $de_carte( 'autour', $p ) ) );
	foreach ( array_slice( $autour, 0, 2 ) as $rang => $p ) {
		if ( count( $principaux ) >= 3 ) {
			break;
		}
		$sur         = $restants( $de_carte( 'autour', $p ) );
		$cs          = $contenus_de( array( $p ) );
		$fait_autour = false;
		if ( count( $sur ) >= 2 || ( $sur && $cs ) ) {
			$bonus = array();
			foreach ( $sur as $i ) {
				$bonus[ $i ] = 2 * count( array_intersect( $autour, array_map( 'intval', er_continuer_champ( $ix['livres'][ $i ], 'ab' ) ) ) );
			}
			$ranges = er_continuer_une_edition( er_continuer_ranger( $sur, $ix, $id, $bonus ), $ix );
			$fr     = array_values( array_filter( $ranges, fn( $i ) => 'ru' !== er_continuer_langue_du_livre( $ix['livres'][ $i ] ) ) );
			$ru     = array_values( array_filter( $ranges, fn( $i ) => 'ru' === er_continuer_langue_du_livre( $ix['livres'][ $i ] ) ) );
			$forme  = 'vignettes';
			if ( $fr && $ru && count( $ranges ) >= 3 ) {
				$forme  = 'deux-langues';
				$livres = array_merge( array_slice( $fr, 0, 4 ), array_slice( $ru, 0, count( $fr ) >= 4 ? 1 : 2 ) );
			} else {
				$livres = array_slice( $ranges, 0, $cs ? 3 : 4 );
			}
			$ajouter(
				array(
					'type'     => 'autour',
					'sur'      => 'Autour ' . er_continuer_de( $nom( $p ) ),
					'titre'    => 'D’autres regards sur ' . $court( $p ),
					'ligne'    => $inv( $livres, array( 'personne' => $p, 'contenus' => $cs, 'sans_langues' => 'deux-langues' === $forme ) ),
					'fragment' => 'd’autres regards sur ' . $court( $p ),
					'livres'   => $livres,
					'contenus' => $cs,
					'forme'    => 1 === count( $livres ) + count( $cs ) ? 'vedette' : $forme,
					'personne' => $p,
					'lien'     => array( 'auteur', $p ),
				)
			);
			$fait_autour = true;
		}
		$oeuvre    = $restants( $de_carte( 'auteur', $p ) );
		$dicible_p = er_continuer_nom_dicible( $p, $ix, $page );
		if ( $oeuvre && 0 === $rang && count( $principaux ) < 3 ) {
			$oeuvre = er_continuer_divers( er_continuer_ranger( $oeuvre, $ix, $id ), $ix, 4 );
			$sortes = array_values( array_unique( array_map( fn( $i ) => er_continuer_sorte( $ix['livres'][ $i ], $ix['rayons'] ), $oeuvre ) ) );
			$poss   = 1 === count( $sortes ) && '' !== er_continuer_sortes()[ $sortes[0] ][4] ? er_continuer_sortes()[ $sortes[0] ][4] : 'ses propres livres';
			$ajouter(
				array(
					'type'     => 'oeuvre',
					'sur'      => $fait_autour ? 'Et l’œuvre elle-même' : ( $dicible_p ? 'L’œuvre ' . er_continuer_de( $nom( $p ) ) : 'Son œuvre' ),
					'titre'    => $dicible_p ? 'Lire ' . $court( $p ) : $nom( $p ),
					'ligne'    => $inv( $oeuvre, array( 'personne' => $p, 'possessif' => true ) ),
					'fragment' => $fait_autour ? $poss : ( $dicible_p ? 'l’œuvre ' . er_continuer_de( $court( $p ) ) : '' ),
					'livres'   => $oeuvre,
					'forme'    => 1 === count( $oeuvre ) ? 'vedette' : 'vignettes',
					'personne' => $p,
					'lien'     => $fait_autour ? array() : array( 'auteur', $p ),
				)
			);
		}
	}

	// Les modules qui cherchent plus loin laissent de côté les livres de l'auteur (la colonne « Du même auteur » les
	// montre) et, pour une revue, ses autres numéros.
	$loin = function ( $ids ) use ( $restants, $du_meme, $revue, $ix, $l ) {
		$ids = array_values( array_diff( $restants( $ids ), $du_meme ) );
		if ( $revue ) {
			$nom_revue = er_continuer_revue( $l['t'] );
			$ids       = array_values( array_filter( $ids, fn( $i ) => er_continuer_revue( $ix['livres'][ $i ]['t'] ) !== $nom_revue ) );
		}
		return $ids;
	};
	// La proximité d'un livre avec celui de la fiche, au-delà de ce qui le fait entrer dans un module : la même sorte,
	// les mêmes sujets, les mêmes personnes, les mêmes époques ; un livre qui couvre tous les siècles compte moins.
	$sujets    = array_map( 'intval', er_continuer_champ( $l, 's' ) );
	$periodes  = er_continuer_champ( $l, 'p' );
	$ecrits    = er_continuer_champ( $l, 'e' );
	$proximite = function ( $i ) use ( $ix, $sorte, $sujets, $autour, $periodes, $ecrits ) {
		$li = $ix['livres'][ $i ];
		$b  = ( er_continuer_sorte( $li, $ix['rayons'] ) === $sorte ? 3 : 0 )
			+ 2 * count( array_intersect( $sujets, array_map( 'intval', er_continuer_champ( $li, 's' ) ) ) )
			+ 2 * count( array_intersect( $autour, array_map( 'intval', er_continuer_champ( $li, 'ab' ) ) ) )
			+ count( array_intersect( $periodes, er_continuer_champ( $li, 'p' ) ) )
			+ count( array_intersect( $ecrits, er_continuer_champ( $li, 'e' ) ) );
		$np = count( er_continuer_champ( $li, 'p' ) );
		return $b + ( $np <= 1 ? 1 : ( $np >= 4 ? -2 : 0 ) );
	};
	$meilleurs = function ( $cands ) use ( $proximite, $ix, $id ) {
		$bonus = array();
		foreach ( $cands as $i ) {
			$bonus[ $i ] = $proximite( $i );
		}
		$livres = er_continuer_divers( er_continuer_ranger( $cands, $ix, $id, $bonus, 25 ), $ix, 4 );
		return array( $livres, array_sum( array_map( fn( $i ) => $bonus[ $i ], $livres ) ) );
	};

	// Un livre pour enfants : son rayon (« Dès 3 ans ») d'abord, l'âge compte plus que l'époque.
	$enfance = in_array( $sorte, array( 'jeunesse', 'album', 'contes', 'adolescents' ), true ) && in_array( 'jeunesse', array_map( fn( $c ) => isset( $ix['rayons'][ $c ] ) ? $ix['rayons'][ $ix['rayons'][ $c ]['pa'] ]['sl'] ?? $ix['rayons'][ $c ]['sl'] : '', er_continuer_champ( $l, 'c' ) ), true );
	if ( $enfance && count( $principaux ) < 3 ) {
		$m = er_continuer_module_rayon( $id, $ix, $loin, $meilleurs, $inv );
		if ( $m ) {
			$ajouter( $m );
		}
	}

	// 6. Les mêmes années : l'époque dont parle le livre, à défaut celle où il a été écrit (sauf les dernières
	// décennies, qui sont celles de presque tout le catalogue).
	$mots_ep  = er_continuer_epoques_mots();
	$ecrit    = ! $periodes;
	$base     = $ecrit ? array_diff( $ecrits, array( 'russie-de-poutine', 'annees-1990' ) ) : $periodes;
	$epoque_m = null;
	if ( count( $principaux ) < 3 ) {
		foreach ( $base as $e ) {
			if ( ! isset( $mots_ep[ $e ] ) ) {
				continue;
			}
			// Une histoire de la Russie qui traverse tous les siècles n'est pas « des mêmes années ».
			$cands = array_values( array_filter( $loin( $de_carte( $ecrit ? 'ecriture' : 'periode', $e ) ), fn( $i ) => count( er_continuer_champ( $ix['livres'][ $i ], $ecrit ? 'e' : 'p' ) ) <= 4 ) );
			if ( count( $cands ) < 2 ) {
				continue;
			}
			list( $livres, $valeur ) = $meilleurs( $cands );
			if ( null === $epoque_m || $valeur > $epoque_m[0] ) {
				$epoque_m = array( $valeur, $e, $livres );
			}
		}
	}
	if ( $epoque_m ) {
		list( , $e, $livres )   = $epoque_m;
		list( $des, $fem, $sa ) = er_continuer_des( $livres, $ix );
		if ( in_array( $sa, array( 'biographie', 'vie-de-saint' ), true ) ) {
			list( $des, $fem, $sa ) = array( 'des livres', false, 'livre' );
		}
		// La fiction et la poésie sont « de » leur époque ; le reste en parle.
		$de_l = in_array( $sa, array( 'roman', 'poemes', 'nouvelles', 'recit', 'contes', 'voyage', 'adolescents', 'jeunesse' ), true );
		$ajouter(
			array(
				'type'     => 'epoque',
				'sur'      => $ecrit ? 'Écrits à la même époque' : ( $mots_ep[ $e ][3] ? 'La même époque' : 'Les mêmes années' ),
				'titre'    => $mots_ep[ $e ][0],
				'ligne'    => $inv( $livres ),
				'fragment' => $des . ' ' . ( $ecrit ? ( $fem ? 'écrites ' : 'écrits ' ) . $mots_ep[ $e ][2] : ( $de_l ? $mots_ep[ $e ][4] : $mots_ep[ $e ][1] ) ),
				'livres'   => $livres,
				'forme'    => 'vignettes',
				'lien'     => array( $ecrit ? 'er_ecriture' : 'er_periode', $e ),
			)
		);
	}

	// 7. La personne que nomme la notice (pas un traducteur ni un préfacier) : ses livres, ceux qui parlent d'elle.
	if ( count( $principaux ) < 3 ) {
		$choix = null;
		foreach ( $ctx['cites'] as list( $p, $intervient ) ) {
			$p = (int) $p;
			if ( $intervient || in_array( $p, $gens, true ) || ! er_continuer_nom_dicible( $p, $ix, $page ) ) {
				continue;
			}
			$siens = $loin( $de_carte( 'auteur', $p ) );
			$sur   = array_values( array_diff( $loin( $de_carte( 'autour', $p ) ), $siens ) );
			$n     = count( $siens ) + count( $sur );
			if ( $n >= 2 && ( null === $choix || $n > $choix[1] ) ) {
				$choix = array( $p, $n, $siens, $sur );
			}
		}
		if ( $choix ) {
			list( $p, , $siens, $sur ) = $choix;
			$livres                    = array_slice( array_merge( er_continuer_divers( er_continuer_ranger( $siens, $ix, $id, array(), 25 ), $ix, 4 ), er_continuer_ranger( $sur, $ix, $id, array(), 25 ) ), 0, 4 );
			$a_lui                     = array_values( array_intersect( $livres, $siens ) );
			$ajouter(
				array(
					'type'     => 'notice',
					'sur'      => 'Au détour de la notice',
					'titre'    => $nom( $p ),
					'ligne'    => $inv( $livres, array( 'personne' => $p, 'possessif' => true, 'sur' => true ) ),
					'fragment' => count( $a_lui ) === count( $livres ) ? 'des livres ' . er_continuer_de( $court( $p ) ) : ( $a_lui ? $nom( $p ) : 'des livres sur ' . $court( $p ) ),
					'livres'   => $livres,
					'forme'    => 1 === count( $livres ) ? 'vedette' : 'vignettes',
					'personne' => $p,
					'lien'     => array( 'auteur', $p ),
				)
			);
		}
	}

	// 8. Le même thème : le sujet du site dont les livres sont les plus proches de celui-ci ; un sujet précis plutôt
	// qu'un sujet qui couvre la moitié du catalogue, et pas celui qui redit l'époque.
	if ( count( $principaux ) < 3 ) {
		$mots_s   = er_continuer_sujets_mots();
		$deja     = array_column( $principaux, 'fragment' );
		$meilleur = null;
		// Un sujet qui ne fait que redire la sorte du livre (la poésie pour un recueil de poèmes, l'orthodoxie pour un
		// livre de théologie) : le rayon le dit mieux.
		$redites = array(
			'Poésie'               => array( 'poemes', 'anthologie' ),
			'Philosophie'          => array( 'philosophie' ),
			'Théâtre'              => array( 'theatre' ),
			'Contes et légendes'   => array( 'contes', 'jeunesse', 'album' ),
			'Musique'              => array( 'musique' ),
			'Cinéma'               => array( 'cinema' ),
			'Arts'                 => array( 'art', 'icones', 'photographie', 'scene' ),
			'Orthodoxie'           => array( 'theologie', 'liturgie', 'spiritualite', 'vie-de-saint', 'icones' ),
			'Saints et monastères' => array( 'vie-de-saint' ),
			'Langue russe'         => array( 'manuel', 'dictionnaire', 'bilingue', 'audio' ),
			'Enfance et famille'   => array( 'jeunesse', 'album', 'contes', 'adolescents' ),
		);
		foreach ( $sujets as $s ) {
			$nom_s = isset( $ix['sujets'][ $s ]['n'] ) ? $ix['sujets'][ $s ]['n'] : '';
			$mots  = isset( $mots_s[ $nom_s ] ) ? $mots_s[ $nom_s ] : array( $nom_s, 'sur le même thème', false );
			if ( isset( $redites[ $nom_s ] ) && in_array( $sorte, $redites[ $nom_s ], true ) ) {
				continue;
			}
			$redit = false;
			foreach ( $principaux as $m ) {
				// « La guerre » redit « La guerre, 1941-1945 ».
				if ( 'epoque' === $m['type'] && ( false !== mb_strpos( $m['fragment'], $mots[1] ) || 0 === mb_strpos( $m['titre'], $mots[0] ) ) ) {
					$redit = true;
				}
			}
			$cands = $loin( $de_carte( 'sujet', $s ) );
			if ( $redit || count( $cands ) < 2 ) {
				continue;
			}
			list( $livres, $valeur ) = $meilleurs( $cands );
			$valeur                 -= count( $de_carte( 'sujet', $s ) ) / 40;
			if ( null === $meilleur || $valeur > $meilleur[0] ) {
				$meilleur = array( $valeur, $mots, $livres, $s );
			}
		}
		unset( $deja );
		if ( $meilleur ) {
			list( , $mots, $livres, $s ) = $meilleur;
			list( $des, , $sa )      = er_continuer_des( $livres, $ix );
			// « d'autres romans sur Moscou » quand le livre de la fiche en est un aussi ; « de poésie » garde « livres ».
			if ( preg_match( '/^d(e |’)/u', $mots[1] ) || in_array( $sa, array( 'livre', 'biographie', 'vie-de-saint' ), true ) || false !== strpos( $mots[1], 'destins' ) ) {
				$des = 'livres';
			} else {
				$des = preg_replace( '/^des /u', '', $des );
			}
			$ajouter(
				array(
					'type'     => 'sujet',
					'sur'      => $mots[2] ? 'Les mêmes lieux' : 'Sur le même thème',
					'titre'    => $mots[0],
					'ligne'    => $inv( $livres ),
					'fragment' => ( $sa === $sorte || 'livres' === $des ? 'd’autres ' : 'des ' ) . $des . ' ' . $mots[1],
					'livres'   => $livres,
					'forme'    => 'vignettes',
					'lien'     => array( 'er_sujet', $s ),
				)
			);
		}
	}

	// Secondaires : les autres éditions dans la même langue.
	$memes = array_slice( er_continuer_ranger( $restants( $memes ), $ix, $id ), 0, 2 );
	if ( $memes ) {
		$chez = array_values( array_filter( array_map( $parution, $memes ) ) );
		$ajouter(
			array(
				'type'     => 'editions',
				'sur'      => 'Le même livre',
				'titre'    => 1 === count( $memes ) ? 'Dans une autre édition' : 'Dans d’autres éditions',
				'ligne'    => $chez ? er_continuer_phrase( ( 1 === count( $memes ) ? 'paru ' : '' ) . er_continuer_liste( $chez ) ) : '',
				'fragment' => 1 === count( $memes ) ? 'une autre édition' : 'd’autres éditions',
				'livres'   => $memes,
				'forme'    => 'ligne',
			),
			true
		);
	}

	// Secondaires : le traducteur ; à défaut le préfacier, celui qui a établi l'édition ou dirigé l'ouvrage ; pour une
	// revue, l'un de ceux qui y écrivent.
	$ordre = $revue ? array( 'contribution', 'direction', 'traduction' ) : array( 'traduction', 'preface', 'edition', 'direction', 'contribution' );
	foreach ( $ordre as $role ) {
		$choix = null;
		foreach ( isset( $roles[ $role ] ) ? array_map( 'intval', $roles[ $role ] ) : array() as $t ) {
			if ( in_array( $t, $auteurs, true ) || ! er_continuer_nom_dicible( $t, $ix, $page ) ) {
				continue;
			}
			$siens = 'traduction' === $role ? $restants( $de_carte( 'role', 'traduction' )[ $t ] ?? array() ) : $restants( $de_carte( 'auteur', $t ) );
			$siens = array_values( array_diff( $siens, $role !== 'traduction' ? array() : array() ) );
			$enst  = count( array_filter( $siens, fn( $i ) => ! empty( $ix['livres'][ $i ]['st'] ) ) );
			if ( $siens && ( null === $choix || array( $enst, count( $siens ) ) > array( $choix[2], count( $choix[1] ) ) ) ) {
				$choix = array( $t, $siens, $enst );
			}
		}
		if ( ! $choix ) {
			continue;
		}
		list( $t, $siens ) = $choix;
		$siens             = array_slice( er_continuer_ranger( $siens, $ix, $id ), 0, 2 );
		$cs                = $contenus_de( array( $t ) );
		$nom_t             = er_continuer_nom_ligne( $t, $ix, $page );
		// La rencontre, l'article ou la vidéo montrés avec ses livres : la ligne les dit aussi.
		$au_centre = $cs ? ' ' . er_continuer_phrase( 'à retrouver aussi : ' . er_continuer_liste( array_map( fn( $c ) => er_continuer_mots_contenu( $ix['contenus'][ $c ], $ctx['maintenant'] ), $cs ) ) ) : '';
		if ( 'traduction' === $role ) {
			$co    = array_values( array_filter( array_diff( array_map( 'intval', $roles['traduction'] ), array( $t ) ), fn( $p ) => er_continuer_nom_dicible( $p, $ix, $page ) ) );
			$leurs = array();
			foreach ( $siens as $i ) {
				$leurs = array_merge( $leurs, array_map( 'intval', array_slice( er_continuer_champ( $ix['livres'][ $i ], 'a' ), 0, 1 ) ) );
			}
			$leurs = array_values( array_unique( $leurs ) );
			$dits  = array_values( array_filter( $leurs, fn( $p ) => er_continuer_nom_dicible( $p, $ix, $page ) ) );
			if ( 1 === count( $siens ) ) {
				$a_i   = $leurs ? $leurs[0] : 0;
				$suite = '';
				if ( $a_i && in_array( $a_i, $auteurs, true ) ) {
					$suite = ', du même auteur';
				} elseif ( $a_i && er_continuer_nom_dicible( $a_i, $ix, $page ) ) {
					$suite = ', ' . er_continuer_de( er_continuer_nom_ligne( $a_i, $ix, $page ) );
				}
				$objet = 'la traduction ' . er_continuer_de( $titre_l( $siens[0] ) ) . $suite;
			} elseif ( $leurs && ! array_diff( $leurs, $auteurs ) ) {
				$objet = 'd’autres traductions ' . er_continuer_de( er_continuer_nom_ligne( $leurs[0], $ix, $page ) );
			} elseif ( $dits && count( $dits ) === count( $leurs ) ) {
				$objet = 'des traductions ' . er_continuer_liste( array_map( 'er_continuer_de', er_continuer_noms_ligne( $dits, $ix, $page ) ) );
			} else {
				$objet = 'd’autres traductions';
			}
			// « Traduit avec X » laissait deviner qui avait traduit quoi : le livre de la fiche, nommé, et ses mains.
			$a_plusieurs = '';
			if ( $co ) {
				$a_plusieurs = count( $co ) <= 3
					? 'une traduction à ' . er_continuer_nombre( 2 * ( count( $co ) + 1 ) ) . ' mains, avec ' . er_continuer_liste( array_map( fn( $p ) => er_continuer_nom_dans_phrase( $nom( $p ) ), $co ) )
					: 'une traduction collective, avec ' . er_continuer_nombre( count( $co ), true ) . ' autres plumes';
				$a_plusieurs = er_continuer_phrase( $titre_l( $id ) . ' : ' . $a_plusieurs ) . ' ';
			}
			$ajouter(
				array(
					'type'     => 'traduction',
					'sur'      => 'De la même main',
					'titre'    => $nom( $t ),
					'ligne'    => $a_plusieurs . er_continuer_phrase( 'on lui doit aussi ' . $objet ) . $au_centre,
					'fragment' => ( 1 === count( $siens ) ? 'une autre traduction ' : 'd’autres traductions ' ) . er_continuer_de( $nom_t ),
					'livres'   => $siens,
					'contenus' => $cs,
					'forme'    => 'ligne',
					'personne' => $t,
					'lien'     => array( 'auteur', $t ),
				),
				true
			);
		} else {
			$sur = array(
				'preface'      => 'Préfacé par',
				'edition'      => 'Édition établie par',
				'direction'    => $revue ? 'Sous la direction de' : 'Sous la direction de',
				'contribution' => $revue ? 'Dans ce numéro' : 'Avec la contribution de',
			)[ $role ];
			$quoi = $inv( $siens, array( 'personne' => $t, 'toujours' => true ) );
			$ajouter(
				array(
					'type'     => $role,
					'sur'      => $sur,
					'titre'    => $nom( $t ),
					'ligne'    => ( '' !== $quoi ? er_continuer_phrase( 'on lui doit aussi ' . mb_strtolower( mb_substr( $quoi, 0, 1 ) ) . mb_substr( $quoi, 1 ) ) : '' ) . $au_centre,
					'fragment' => 'les livres ' . er_continuer_de( $nom_t ),
					'livres'   => $siens,
					'contenus' => $cs,
					'forme'    => 'ligne',
					'personne' => $t,
					'lien'     => array( 'auteur', $t ),
				),
				true
			);
		}
		break;
	}

	// Secondaires : une rencontre ou un article qui cite le livre, que rien n'a encore pris.
	$citants = array();
	foreach ( $de_carte( 'c_livre', $id ) as $cid ) {
		$co = $ix['contenus'][ $cid ];
		if ( ! in_array( $cid, $c_pris, true ) && ( ! isset( $co['lg'] ) || '' === $co['lg'] || $co['lg'] === $ctx['langue'] ) ) {
			$citants[] = $cid;
		}
	}
	if ( $citants ) {
		$citants = array_slice( $citants, 0, 2 );
		$un      = $ix['contenus'][ $citants[0] ];
		$titre   = 'er_evenement' === $un['ty'] ? 'Une rencontre autour de ce livre' : ( 'er_media' === $un['ty'] ? 'En vidéo, autour de ce livre' : 'Dans le journal du centre' );
		$ajouter(
			array(
				'type'     => 'centre',
				'sur'      => 'Au centre culturel',
				'titre'    => count( $citants ) > 1 ? 'Au centre, autour de ce livre' : $titre,
				'ligne'    => '',
				'fragment' => er_continuer_mots_contenu( $un, $ctx['maintenant'] ),
				'livres'   => array(),
				'contenus' => $citants,
				'forme'    => 'contenus',
			),
			true
		);
	}

	// À défaut d'autre chose : le même rayon (sans son époque si un module la montre déjà : pas de « Poésie de l'Âge
	// d'argent » sous « L'Âge d'argent »).
	if ( count( $principaux ) < 2 && ! $enfance ) {
		$m = er_continuer_module_rayon( $id, $ix, $loin, $meilleurs, $inv, (bool) array_filter( $principaux, fn( $p ) => 'epoque' === $p['type'] ) );
		if ( $m ) {
			$ajouter( $m );
		}
	}

	// L'ordre de la page : les principaux ; les autres éditions après l'autre langue ou les volumes ; le traducteur
	// avant le dernier principal quand il y en a trois ; une rencontre à la fin.
	$modules = $principaux;
	foreach ( $secondaires as $s ) {
		$s['secondaire'] = true;
		if ( 'editions' === $s['type'] ) {
			$apres = 0;
			foreach ( $modules as $k => $m ) {
				if ( in_array( $m['type'], array( 'langue', 'volumes' ), true ) ) {
					$apres = $k + 1;
				}
			}
			array_splice( $modules, $apres, 0, array( $s ) );
		} elseif ( 'centre' !== $s['type'] && count( $principaux ) >= 3 ) {
			array_splice( $modules, count( $modules ) - 1, 0, array( $s ) );
		} else {
			$modules[] = $s;
		}
	}
	if ( ! $modules ) {
		return $vide;
	}
	foreach ( $modules as $k => $m ) {
		$modules[ $k ] = array_merge(
			array(
				'ligne'      => '',
				'contenus'   => array(),
				'sans_titre' => false,
				'secondaire' => false,
				'personne'   => 0,
				'lien'       => array(),
			),
			$m,
			array( 'fond' => 0 === $k )
		);
	}
	$fragments = er_continuer_fusionner( array_values( array_filter( array_column( $modules, 'fragment' ), 'strlen' ) ) );
	$court_l   = $revue ? '' : er_continuer_titre_court( $l['t'] );
	return array(
		'ouverture' => er_continuer_phrase( 'après ' . ( '' !== $court_l ? er_continuer_titre( $court_l ) : ( $revue ? 'ce numéro' : 'ce livre' ) ) . ' : ' . er_continuer_liste( $fragments ) ),
		'modules'   => $modules,
	);
}

/**
 * Deux fragments voisins de la ligne d'ouverture qui commencent pareil se fondent : « des livres sur les années 1990 »
 * et « des livres sur Poutine » → « des livres sur les années 1990 et sur Poutine ».
 *
 * @param string[] $fragments Les fragments.
 * @return string[]
 */
function er_continuer_fusionner( $fragments ) {
	$sortie = array();
	foreach ( $fragments as $f ) {
		$k = count( $sortie ) - 1;
		// « de » ne réunit que deux époques (« de la révolution et de la guerre civile ») : devant un nom sans article,
		// c'est la sorte qui continue (« des ouvrages de philosophie »), et rien ne se réunit.
		$motif = '/^((?:des|d’autres) [^ ]+(?: de poèmes)?) (sur|de (?=la |le |l’)|d’|écrits|écrites) (.+)$/u';
		if ( $k >= 0 && preg_match( $motif, $sortie[ $k ], $a ) && preg_match( $motif, $f, $b ) && preg_replace( '/^d’autres /u', 'des ', $a[1] ) === preg_replace( '/^d’autres /u', 'des ', $b[1] ) && preg_replace( '/^d’$/u', 'de', $a[2] ) === preg_replace( '/^d’$/u', 'de', $b[2] ) && false === strpos( $sortie[ $k ], ' et ' ) ) {
			$sortie[ $k ] .= ' et ' . $b[2] . ( 'd’' === $b[2] ? '' : ' ' ) . $b[3];
			continue;
		}
		$sortie[] = $f;
	}
	return $sortie;
}

/**
 * Ce qu'est un seul livre, en deux ou trois mots, pour la ligne d'ouverture : « un essai », « une biographie ».
 *
 * @param int[] $ids Les livres (le premier compte).
 * @param array $ix  L'index.
 */
function er_continuer_inventaire_court( $ids, $ix ) {
	$sorte = er_continuer_sorte( $ix['livres'][ $ids[0] ], $ix['rayons'] );
	if ( 'revue' === $sorte ) {
		return 'un numéro ' . er_continuer_de( er_continuer_titre( er_continuer_revue( $ix['livres'][ $ids[0] ]['t'] ) ) );
	}
	list( $un, , $feminin, $seul ) = er_continuer_sortes()[ $sorte ];
	return '' !== $seul ? $seul : er_continuer_nombre( 1, $feminin ) . ' ' . $un;
}

/**
 * Le module « Dans le même rayon », à défaut d'autre chose : le genre et l'époque du livre ensemble, à défaut le genre.
 *
 * @param int      $id        Le livre.
 * @param array    $ix        L'index.
 * @param callable $loin      Les livres pas encore pris, hors de l'auteur.
 * @param callable $meilleurs Les meilleurs livres parmi des candidats, et leur proximité.
 * @param callable $inv       L'inventaire.
 * @param bool     $sans_epoque Le genre seul, l'époque ayant déjà son module.
 * @return array|null
 */
function er_continuer_module_rayon( $id, $ix, $loin, $meilleurs, $inv, $sans_epoque = false ) {
	$carte   = er_continuer_carte( $ix );
	$genres  = array();
	$epoques = array();
	foreach ( er_continuer_champ( $ix['livres'][ $id ], 'c' ) as $c ) {
		$r = isset( $ix['rayons'][ $c ] ) ? $ix['rayons'][ $c ] : null;
		if ( ! $r || 'sans-rayon' === $r['sl'] || 0 === (int) $r['pa'] ) {
			continue;
		}
		if ( in_array( $r['sl'], array( 'classiques', 'age-d-argent', 'periode-sovietique', 'emigration', 'contemporains' ), true ) || preg_match( '/époque|période/iu', (string) $r['gr'] ) ) {
			$epoques[] = (int) $c;
		} elseif ( isset( er_continuer_sortes_des_rayons()[ $r['sl'] ] ) ) {
			$genres[] = (int) $c;
		}
	}
	if ( ! $genres ) {
		return null;
	}
	if ( $sans_epoque ) {
		$epoques = array();
	}
	$dans  = fn( $c ) => isset( $carte['rayon'][ $c ] ) ? $carte['rayon'][ $c ] : array();
	$cands = $epoques ? $loin( array_intersect( $dans( $genres[0] ), $dans( $epoques[0] ) ) ) : array();
	$avec  = count( $cands ) >= 2;
	if ( ! $avec ) {
		$cands = $loin( $dans( $genres[0] ) );
	}
	if ( count( $cands ) < 2 ) {
		return null;
	}
	list( $livres ) = $meilleurs( $cands );
	$genre          = $ix['rayons'][ $genres[0] ]['n'];
	$titre          = $genre;
	$suite          = '';
	if ( $avec ) {
		$epoque = $ix['rayons'][ $epoques[0] ]['n'];
		$de     = preg_replace( array( '/^La /u', '/^Le /u', '/^Les /u', '/^L[’\']/u' ), array( 'de la ', 'du ', 'des ', 'de l’' ), $epoque, 1, $fait );
		$suite  = $fait ? $de : er_continuer_de( $epoque );
		$titre  = $genre . ' ' . $suite;
	}
	$sorte = er_continuer_sortes_des_rayons()[ $ix['rayons'][ $genres[0] ]['sl'] ];
	return array(
		'type'     => 'rayon',
		'sur'      => 'Dans le même rayon',
		'titre'    => $titre,
		'ligne'    => $inv( $livres ),
		'fragment' => 'd’autres ' . er_continuer_sortes()[ $sorte ][1] . ( '' !== $suite ? ' ' . $suite : '' ),
		'livres'   => $livres,
		'forme'    => 'vignettes',
		'lien'     => array( 'product_cat', $genres[0] ),
	);
}
