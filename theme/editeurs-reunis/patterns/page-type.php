<?php
/**
 * Title: Page type : une page de texte qui informe
 * Slug: editeurs-reunis/page-type
 * Categories: editeurs-reunis
 * Block Types: core/post-content
 * Post Types: page
 * Description: L’ouverture, les photos, le texte, ses sections, le rendez-vous, les livres, le journal, « Pour aller plus loin ».
 *
 * La page type d'une page de texte qui informe (inc/page-type.php) : ses modules, dans l'ordre où ils racontent. Chacun
 * est aussi un pattern à part (« Les modules d'une page ») : on les réarrange, on en retire, on en ajoute.
 *
 * @package editeurs-reunis
 */

include __DIR__ . '/pt-ouverture.php';
include __DIR__ . '/pt-triptyque.php';
include __DIR__ . '/pt-texte.php';
include __DIR__ . '/pt-section.php';
include __DIR__ . '/pt-diptyque.php';
include __DIR__ . '/pt-photo-marge.php';
include __DIR__ . '/pt-rendez-vous.php';
include __DIR__ . '/pt-livres.php';
$er_pt_intertitre = 'La mémoire de la dissidence';
include __DIR__ . '/pt-section.php';
unset( $er_pt_intertitre );
include __DIR__ . '/pt-journal.php';
include __DIR__ . '/pt-frise.php';
include __DIR__ . '/pt-plus-loin.php';
