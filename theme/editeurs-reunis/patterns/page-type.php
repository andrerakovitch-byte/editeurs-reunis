<?php
/**
 * Title: Page type : une page de texte qui informe
 * Slug: editeurs-reunis/page-type
 * Categories: editeurs-reunis
 * Block Types: core/post-content
 * Post Types: page
 * Description: Le triptyque, le chapô, le texte, ses sections, les photos, le rendez-vous, les livres, le journal, « Pour aller plus loin ».
 *
 * La page type d'une page de texte qui informe (inc/page-type.php) : ses modules, dans l'ordre où ils racontent. Chacun
 * est aussi un pattern à part (« Les modules d'une page ») : on les réarrange, on en retire, on en ajoute. La tête (le
 * titre de la page, calé en bas d'un blanc de la taille d'un menu ouvert) vient du gabarit « Page de texte »
 * (templates/page-texte.html) : la page ne la porte pas.
 *
 * @package editeurs-reunis
 */

include __DIR__ . '/pt-triptyque.php';
include __DIR__ . '/pt-chapo.php';
include __DIR__ . '/pt-texte.php';
include __DIR__ . '/pt-deux-colonnes.php';
include __DIR__ . '/pt-diptyque.php';
include __DIR__ . '/pt-photo-marge.php';
include __DIR__ . '/pt-rendez-vous.php';
include __DIR__ . '/pt-livres.php';
$er_pt_intertitre = 'La mémoire de la dissidence';
include __DIR__ . '/pt-deux-colonnes.php';
unset( $er_pt_intertitre );
include __DIR__ . '/pt-journal.php';
include __DIR__ . '/pt-frise.php';
include __DIR__ . '/pt-plus-loin.php';
