<?php
/**
 * Plugin Name: Les Éditeurs réunis — bascule
 * Description: Ce qui sert à passer de l'ancien site Wix au nouveau, et rien d'autre : le contenu de l'ébauche (donnees/semer.php, les pages d'exemple et leurs images), les imports des rencontres, du journal, des vidéos et des expositions de Wix. À désactiver puis supprimer après la bascule ; les anciennes adresses de Wix, elles, restent dans l'extension de la librairie.
 * Version: 1.0.0
 * Requires Plugins: er-librairie
 * Text Domain: er-librairie
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/inc/import-wix.php';
require __DIR__ . '/inc/exemples.php';
require __DIR__ . '/inc/etiquettes.php';
require __DIR__ . '/inc/titres.php';
require __DIR__ . '/inc/phrases.php';
