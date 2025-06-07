<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('user')) { // 'user' suffit généralement pour une action sur un équipement
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init(); // En V4, on peut spécifier les actions GET autorisées ici si besoin

    if (init('action') == 'refreshFeed') {
        $eqLogicId = init('eqLogic_id');
        if (!$eqLogicId) {
            ajax::error(__('ID de l'équipement manquant.', __FILE__));
        }
        $eqLogic = eqLogic::byId($eqLogicId);
        if (!is_object($eqLogic)) {
            ajax::error(__('Equipement non trouvé : ', __FILE__) . $eqLogicId);
        }
        if ($eqLogic->getPluginName() != 'googleNewsReader') {
             ajax::error(__('L'équipement n'appartient pas au plugin googleNewsReader.', __FILE__));
        }

        try {
            // On cherche la commande 'refresh' de cet équipement
            $cmd = $eqLogic->getCmd(null, 'refresh');
            if (!is_object($cmd)) {
                 ajax::error(__('Commande refresh non trouvée pour l'équipement.', __FILE__));
            }
            $cmd->execute(); // Exécute la commande action
            // La méthode execute de la commande devrait déjà logguer et utiliser message::add
            ajax::success("{{Flux RSS rafraîchi pour}} " . $eqLogic->getHumanName());
        } catch (Exception $e) {
            log::add('googleNewsReader', 'error', 'Erreur AJAX lors du rafraîchissement pour ' . $eqLogic->getHumanName() . ': ' . $e->getMessage());
            ajax::error($e->getMessage());
        }
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
