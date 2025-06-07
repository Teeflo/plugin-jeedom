<?php
// Make sure this file is not directly accessible
if (!defined('JEEDOM_CORE_PLUGIN')) {
    die();
}

function googleNews_install() {
    // Check for cURL extension
    if (!extension_loaded('curl')) {
        log::add('googleNews', 'error', "L'extension PHP cURL est requise mais n'est pas installée.");
        throw new Exception("L'extension PHP cURL est requise mais n'est pas installée.");
    }

    // Check for XML extension
    if (!extension_loaded('xml') || !function_exists('simplexml_load_string')) {
        log::add('googleNews', 'error', "L'extension PHP XML (SimpleXML) est requise mais n'est pas installée.");
        throw new Exception("L'extension PHP XML (SimpleXML) est requise mais n'est pas installée.");
    }

    // SQL to create the table for Google News articles
    $sql = "CREATE TABLE IF NOT EXISTS `google_news_articles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `eqLogic_id` INT NOT NULL,
        `guid` VARCHAR(255) NOT NULL,
        `title` TEXT,
        `description` TEXT,
        `pubDate` DATETIME NULL,
        `link` VARCHAR(2048),
        `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_eqLogic_id` (`eqLogic_id`),
        UNIQUE KEY `uniq_guid_eqlogic` (`eqLogic_id`, `guid`(191)) COMMENT 'Using 191 for utf8mb4 compatibility'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    try {
        DB::Prepare($sql, [], DB::FETCH_TYPE_ROW);
        log::add('googleNews', 'info', 'Table google_news_articles vérifiée/créée avec succès.');
    } catch (Exception $e) {
        log::add('googleNews', 'error', 'Erreur lors de la création de la table google_news_articles: ' . $e->getMessage());
        throw $e; // Re-throw exception to halt installation if table creation fails
    }
}

function googleNews_update() {
    log::add('googleNews', 'info', 'Début de la mise à jour de googleNews.');
    // Re-run install logic to ensure table/columns exist and are correct
    googleNews_install();

    // Example of adding a new column if it doesn't exist during an update:
    // if (!DB::columnExist('google_news_articles', 'new_column_example')) {
    //    log::add('googleNews', 'info', 'Ajout de la colonne new_column_example à google_news_articles.');
    //    $sqlAlter = "ALTER TABLE `google_news_articles` ADD COLUMN `new_column_example` VARCHAR(255) NULL AFTER `link`;";
    //    try {
    //        DB::Prepare($sqlAlter, [], DB::FETCH_TYPE_ROW);
    //        log::add('googleNews', 'info', 'Colonne new_column_example ajoutée avec succès.');
    //    } catch (Exception $e) {
    //        log::add('googleNews', 'error', 'Erreur lors de l'ajout de la colonne new_column_example: ' . $e->getMessage());
    //    }
    // }
    log::add('googleNews', 'info', 'Mise à jour de googleNews terminée.');
}

function googleNews_remove() {
    // For now, we will not automatically remove the table to prevent data loss.
    // log::add('googleNews', 'info', 'Désinstallation de googleNews. La table google_news_articles n'est pas supprimée automatiquement.');

    // If you want to remove the table upon plugin removal, uncomment below:
    /*
    log::add('googleNews', 'info', 'Suppression de la table google_news_articles.');
    $sqlDrop = "DROP TABLE IF EXISTS `google_news_articles`;";
    try {
        DB::Prepare($sqlDrop, [], DB::FETCH_TYPE_ROW);
        log::add('googleNews', 'info', 'Table google_news_articles supprimée avec succès.');
    } catch (Exception $e) {
        log::add('googleNews', 'error', 'Erreur lors de la suppression de la table google_news_articles: ' . $e->getMessage());
    }
    */
}
?>
