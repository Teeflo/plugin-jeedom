<?php

if (!defined('JEEDOM_CORE_INSTALL_PHP')) {
    define('JEEDOM_CORE_INSTALL_PHP', true); // Indicate that this script is an install script
}

/**
 * Installation function for the googleNewsReader plugin.
 * This function is called when the plugin is installed.
 */
function googleNewsReader_install() {
    // Log the start of the installation
    log::add('googleNewsReader', 'info', 'Début de l\'installation du plugin Google News Reader...');

    // Dependencies (php-xml, php-curl) are expected to be handled by Jeedom's core
    // based on the "dependencies" section in plugin_info/info.json (apt packages).
    // However, we can check if they are loaded after apt installation might have occurred.
    // This check might be more reliable if run after a potential restart or on first use,
    // as PHP might not pick up newly installed extensions immediately without a web server or FPM restart.

    if (function_exists('extension_loaded')) {
        $xml_loaded = extension_loaded('xml');
        $curl_loaded = extension_loaded('curl');

        if ($xml_loaded && $curl_loaded) {
            log::add('googleNewsReader', 'info', 'Les extensions PHP requises (xml et curl) sont bien chargées.');
        } else {
            $missing_extensions = [];
            if (!$xml_loaded) {
                $missing_extensions[] = 'xml';
            }
            if (!$curl_loaded) {
                $missing_extensions[] = 'curl';
            }
            log::add('googleNewsReader', 'warning', 'Attention : Une ou plusieurs extensions PHP requises ne semblent pas chargées : ' . implode(', ', $missing_extensions) . '.');
            log::add('googleNewsReader', 'warning', 'Si les paquets apt (php-xml, php-curl) viennent d\'être installés, un redémarrage du serveur web (Apache/Nginx) ou de PHP-FPM peut être nécessaire.');
            log::add('googleNewsReader', 'warning', 'Veuillez vérifier la configuration PHP et les logs système si le problème persiste.');
        }
    } else {
        log::add('googleNewsReader', 'warning', 'La fonction extension_loaded() n\'existe pas. Impossible de vérifier les extensions PHP.');
    }

    // Log the end of the installation
    log::add('googleNewsReader', 'info', 'Installation du plugin Google News Reader terminée.');
}

/**
 * Update function for the googleNewsReader plugin.
 * This function is called when the plugin is updated.
 *
 * @param string $version The new version of the plugin.
 */
function googleNewsReader_update($version) {
    // Log the start of the update
    log::add('googleNewsReader', 'info', 'Mise à jour du plugin Google News Reader vers la version ' . $version . '...');

    // Example of version-specific update logic (uncomment and adapt as needed for future versions)
    /*
    if (version_compare(jeedom::versionPhp(), '7.0.0', '<=')) {
        // Specific actions for older PHP versions if necessary
    }

    if (version_compare($version, '1.1.0', '<=')) {
        log::add('googleNewsReader', 'info', 'Application des mises à jour pour la version 1.1.0 ou inférieure...');
        // Example: Rename a configuration key
        // $allEqLogics = eqLogic::byType('googleNewsReader');
        // foreach ($allEqLogics as $eqLogic) {
        //    $oldConfig = $eqLogic->getConfiguration('oldKey');
        //    if ($oldConfig !== null) {
        //        $eqLogic->setConfiguration('newKey', $oldConfig);
        //        $eqLogic->removeConfiguration('oldKey');
        //        $eqLogic->save();
        //        log::add('googleNewsReader', 'debug', 'Configuration mise à jour pour l\'équipement : ' . $eqLogic->getHumanName());
        //    }
        // }
    }

    if (version_compare($version, '1.2.0', '<=')) {
        // Actions for 1.2.0
    }
    */

    // For the initial release, or if no specific migration steps are needed for this version:
    log::add('googleNewsReader', 'info', 'Aucune action de migration de données spécifique n\'est requise pour cette version.');

    // Log the end of the update
    log::add('googleNewsReader', 'info', 'Mise à jour du plugin Google News Reader terminée.');
}

/**
 * Removal function for the googleNewsReader plugin.
 * This function is called when the plugin is uninstalled.
 */
function googleNewsReader_remove() {
    // Log the start of the uninstallation
    log::add('googleNewsReader', 'info', 'Désinstallation du plugin Google News Reader...');

    // Cron jobs are managed per equipment via the addToCron mechanism and
    // should be removed by the preRemove() method in the googleNewsReader.class.php (eqLogic).
    // If there were any global cron jobs specific to the plugin (not per equipment),
    // they would need to be removed here.
    // Example: cron::remove('my_global_plugin_cron_function');

    // For this plugin, preRemove() in the class handles cron removal for each equipment.
    // So, no specific global cleanup actions are typically needed here regarding crons.

    // Other cleanup tasks could include:
    // - Removing specific cache entries if any were created globally by the plugin.
    // - Removing global configuration entries if any (usually handled by Jeedom).

    log::add('googleNewsReader', 'info', 'Aucune action de suppression globale spécifique n\'est nécessaire (les crons des équipements sont gérés par leur suppression individuelle).');

    // Log the end of the uninstallation
    log::add('googleNewsReader', 'info', 'Désinstallation du plugin Google News Reader terminée.');
}

?>
