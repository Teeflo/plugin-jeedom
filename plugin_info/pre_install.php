<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../../core/php/core.inc.php';

function googleNewsReader_pre_install() {
    // Check for SimpleXML (provided by php-xml)
    if (!extension_loaded('simplexml')) {
        echo '<div class="alert alert-danger">{{L\'extension PHP SimpleXML est manquante. Veuillez installer php-xml.}}</div>';
        echo '<div class="alert alert-danger">{{The SimpleXML PHP extension is missing. Please install php-xml.}}</div>';
        // It's preferable to throw an exception to halt the process if critical
        // For pre_install, echoing an error is often the practice shown in some plugins,
        // but a more robust approach for future Jeedom versions might involve a specific return or exception.
        // For now, we stick to echoing, as Jeedom's core handling of pre_install failures can vary.
        // However, the dependency check in info.json for apt packages is more for system libraries.
        // This PHP extension check is crucial here.
        throw new Exception("L'extension PHP SimpleXML (php-xml) est requise et n'est pas chargée.");
    }

    // Check for cURL (provided by php-curl)
    if (!extension_loaded('curl')) {
        echo '<div class="alert alert-danger">{{L\'extension PHP cURL est manquante. Veuillez installer php-curl.}}</div>';
        echo '<div class="alert alert-danger">{{The cURL PHP extension is missing. Please install php-curl.}}</div>';
        throw new Exception("L'extension PHP cURL (php-curl) est requise et n'est pas chargée.");
    }

    echo '<div class="alert alert-success">{{Vérification des extensions PHP SimpleXML et cURL réussie.}}</div>';
    echo '<div class="alert alert-success">{{PHP extensions SimpleXML and cURL check successful.}}</div>';
}

// Appel de la fonction de pré-installation
// Cette structure est typique si le fichier est appelé directement ou inclus dans un contexte où la fonction doit être exécutée.
// Dans le cadre de Jeedom, le core appelle cette fonction s'il la trouve.
// Pas besoin d'appeler explicitement googleNewsReader_pre_install() ici.
?>
