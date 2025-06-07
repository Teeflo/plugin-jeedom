<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

    // Check for user connection. Admin rights might be more appropriate for triggering data refreshes.
    // Using isConnect() for basic logged-in user, adjust if admin-only execution is needed.
    if (!isConnect()) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init(); // Initialize AJAX response environment

    $action = init('action');
    $eqLogic_id = init('eqLogic_id');

    if (empty($action)) {
        throw new Exception(__('Aucune action spécifiée', __FILE__));
    }

    $eqLogic = null;
    // If an eqLogic_id is provided, attempt to load and validate it.
    // Some actions might not require an eqLogic_id.
    if (!empty($eqLogic_id)) {
        $eqLogic = eqLogic::byId($eqLogic_id);
        if (!is_object($eqLogic)) {
            // If eqLogic not found, send error and exit. Avoids further processing.
            ajax::error(__('Équipement non trouvé : ', __FILE__) . $eqLogic_id);
            // exit(); // or return, depending on ajax::error behavior
        }
        // Ensure it's the correct plugin's equipment
        if ($eqLogic->getEqType_name() != 'googleNewsReader') {
            ajax::error(__('Type d\'équipement incorrect. Attendu "googleNewsReader", obtenu "', __FILE__) . $eqLogic->getEqType_name() . '"');
            // exit();
        }
    }

    switch ($action) {
        case 'refreshData': // This action is called by the JavaScript refresh buttons
            if (!$eqLogic) { // Ensure eqLogic was loaded successfully if required by this action
                ajax::error(__('ID équipement manquant ou invalide pour l\'action refreshData', __FILE__));
                // exit();
            }

            try {
                log::add('googleNewsReader', 'info', '[AJAX] Début du rafraîchissement pour : ' . $eqLogic->getHumanName() . ' (ID: ' . $eqLogic->getId() . ')');
                $eqLogic->refreshData(); // Call the refreshData method defined in googleNewsReader.class.php
                log::add('googleNewsReader', 'info', '[AJAX] Fin du rafraîchissement pour : ' . $eqLogic->getHumanName());
                ajax::success(__('Données rafraîchies avec succès pour ', __FILE__) . $eqLogic->getHumanName());
            } catch (Exception $e) {
                log::add('googleNewsReader', 'error', '[AJAX] Erreur lors du rafraîchissement pour ' . ($eqLogic ? $eqLogic->getHumanName() : 'ID inconnu') . ': ' . $e->getMessage());
                ajax::error(displayExeption($e, true)); // Display the specific exception
            }
            break;

        // Example for a future action:
        // case 'anotherAction':
        //    // ... logic for anotherAction ...
        //    ajax::success("Another action completed.");
        //    break;

        default:
            throw new Exception(__('Action AJAX non valide ou non reconnue : ', __FILE__) . $action);
    }

} catch (Exception $e) {
    // Catch any other exceptions from the main try block (e.g., action not specified, permission denied)
    ajax::error(displayExeption($e, true)); // Use 'true' to ensure it's formatted for AJAX
}
?>
