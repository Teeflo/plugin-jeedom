<?php
function googleNews_install() {
    // Vérification des dépendances système et PHP
    // Bien que info.json le déclare, une vérification ici peut donner un feedback immédiat.
    // Cependant, Jeedom gère normalement cela en amont via info.json.
    // Pour l'instant, nous allons nous concentrer sur la création de la table,
    // car les dépendances sont déjà déclarées dans info.json.

    // Création de la table SQL pour les articles
    if (!DB::isTableExists('googleNews_articles')) {
        $sql = "CREATE TABLE `googleNews_articles` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `eqLogic_id` INT(11) NULL DEFAULT NULL,
            `guid` VARCHAR(255) NULL DEFAULT NULL,
            `title` TEXT NULL DEFAULT NULL,
            `link` TEXT NULL DEFAULT NULL,
            `pubDate` DATETIME NULL DEFAULT NULL,
            `description` TEXT NULL DEFAULT NULL,
            `timestamp_insert` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_guid_per_eq` (`eqLogic_id`, `guid`(191)),
            KEY `eqLogic_id` (`eqLogic_id`),
            KEY `pubDate` (`pubDate`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
            log::add('googleNews', 'info', 'Table googleNews_articles créée avec succès.');
        } catch (Exception $e) {
            log::add('googleNews', 'error', 'Erreur lors de la création de la table googleNews_articles: ' . $e->getMessage());
            // Il serait bien de remonter cette erreur à l'utilisateur via l'interface d'installation de Jeedom.
            // Pour l'instant, on logue l'erreur.
        }
    } else {
        log::add('googleNews', 'info', 'La table googleNews_articles existe déjà.');
        // Potentiellement, vérifier ici si des mises à jour de la table sont nécessaires (alter table)
        // pour des versions futures du plugin. Pour une première installation, ce n'est pas nécessaire.
    }
}

function googleNews_update() {
    // Logique de mise à jour du plugin si nécessaire
    // Par exemple, si la structure de la table change.
    // Pour l'instant, on peut vérifier si la table existe, au cas où.
    if (!DB::isTableExists('googleNews_articles')) {
        googleNews_install(); // Tenter de l'installer si elle n'existe pas
    } else {
        // Exemple: Ajouter une colonne si elle n'existe pas
        // if (!DB::isFieldExists('googleNews_articles', 'new_column')) {
        //     log::add('googleNews', 'info', 'Ajout de la colonne new_column à googleNews_articles.');
        //     DB::Prepare("ALTER TABLE `googleNews_articles` ADD COLUMN `new_column` VARCHAR(255) NULL AFTER `description`;", array(), DB::FETCH_TYPE_ROW);
        // }
    }
}

function googleNews_remove() {
    // Logique de suppression du plugin
    // Optionnel: supprimer la table lors de la désinstallation.
    // Attention, cela supprime toutes les données utilisateur.
    // Il est souvent préférable de laisser la table, ou de donner une option.
    // Pour cet exercice, nous n'allons PAS supprimer la table automatiquement.
    // log::add('googleNews', 'info', 'Désinstallation de Google News. La table googleNews_articles n'est pas supprimée.');

    // Si on voulait la supprimer:
    // if (DB::isTableExists('googleNews_articles')) {
    //     DB::Prepare("DROP TABLE `googleNews_articles`;", array(), DB::FETCH_TYPE_ROW);
    // }
}

// Appel de la fonction d'installation au chargement du fichier
// (Jeedom appelle ces fonctions spécifiques, pas besoin de les appeler directement ici)
?>
