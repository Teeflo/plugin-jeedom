<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    if (!isConnect('user')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init(); // Initialize AJAX system

    $action = ajax::getArg('action', false);
    $eqLogicId = ajax::getArg('eqLogic_id', false);

    if (!$action) {
        ajax::error(__('Aucune action spécifiée', __FILE__));
    }

    $eqLogic = null;
    if ($eqLogicId) {
        $eqLogic = eqLogic::byId($eqLogicId);
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'googleNews') {
             ajax::error(__('Equipement non trouvé ou type incorrect: ', __FILE__) . $eqLogicId);
        }
    }

    switch ($action) {
        case 'refreshArticles':
            if (!$eqLogic) {
                ajax::error(__('ID d'équipement manquant pour refreshArticles', __FILE__));
            }
            try {
                $eqLogic->refresh();
                ajax::success(__('Rafraîchissement manuel effectué avec succès.', __FILE__));
            } catch (Exception $e) {
                ajax::error(__('Erreur lors du rafraîchissement: ', __FILE__) . $e->getMessage());
            }
            break;

        case 'getWidgetHTML':
            if (!$eqLogic) {
                ajax::error(__('ID d'équipement manquant pour getWidgetHTML', __FILE__));
            }

            // Determine if it's for widget display or dashboard based on context if needed
            // $isWidget = ajax::getArg('isWidget', 0); // 1 for actual widget, 0 for preview
            // $isMobile = ajax::getArg('mobile', 0); // 1 for mobile display

            // For now, using a generic approach. The widget template itself will handle variations.
            // This part generates the HTML for the widget.
            // It should use the methods from googleNews.class.php to get data
            // and then format it using a template file (e.g., core/template/dashboard/googleNews.html)

            $maxDisplay = $eqLogic->getConfiguration('maxDisplayArticles', 10);
            $sortOrder = $eqLogic->getConfiguration('sortOrder', 'DESC');
            $articles = $eqLogic->getArticles($maxDisplay, $sortOrder);

            // We need to build the HTML for the widget here or include a template file.
            // Let's assume a simple structure for now, which will be refined when the widget template is built.
            // This is a placeholder for where the widget HTML generation will go.
            // The actual widget display logic will be in core/template/dashboard/googleNews.html (next step)
            // This AJAX function will now render that template.

            $widgetContent = '';
            try {
                // Define variables to pass to the template
                $_widgetController =ްeqLogic::byId($eqLogicId); // Make sure it's the correct object
                $_widgetArticles = $articles; // Articles fetched
                $_widgetMaxDisplay = $maxDisplay;
                $_widgetSortOrder = $sortOrder;
                // Add any other variables the widget template might need

                // Capture output of the template
                ob_start();
                // The path to widget template will be core/template/dashboard/googleNews.html
                // This will be created in the next step (modifying desktop/php/googleNews.php)
                // but we anticipate its usage here.
                // Note: The plan step for widget template is part of "Modify desktop/php/googleNews.php"
                // which is technically step 3. This AJAX file is step 6.
                // Let's assume the widget template file will be `core/template/dashboard/googleNews.widget.html`
                // to avoid confusion with the main `googleNews.html` if that was intended for something else.
                // The plan says: "Develop the widget template (core/template/dashboard/googleNews.html ...)"

                $templatePath = dirname(__FILE__) . '/../../core/template/dashboard/googleNews.html';
                if (file_exists($templatePath)) {
                    include $templatePath;
                } else {
                    // Fallback or error if template not found
                    $widgetContent = '<div class="widget-googleNews"><p>Erreur: Template de widget introuvable.</p></div>';
                    echo $widgetContent; // Echo directly as ob_start is active
                }
                $htmlToReturn = ob_get_clean();
                ajax::success($htmlToReturn);

            } catch (Exception $e) {
                ajax::error(__('Erreur lors de la génération de l'aperçu du widget: ', __FILE__) . $e->getMessage());
            }
            break;

        // Example for a future action:
        // case 'updateWidgetOption':
        //     if (!$eqLogic) {
        //         ajax::error(__('ID d'équipement manquant pour updateWidgetOption', __FILE__));
        //     }
        //     $optionName = ajax::getArg('optionName', false);
        //     $optionValue = ajax::getArg('optionValue', false);
        //     if($optionName) {
        //         $eqLogic->setConfiguration($optionName, $optionValue);
        //         $eqLogic->save();
        //         ajax::success(__('Option sauvegardée', __FILE__));
        //     } else {
        //         ajax::error(__('Nom de l'option manquant', __FILE__));
        //     }
        //     break;

        default:
            ajax::error(__('Action non reconnue: ', __FILE__) . $action);
            break;
    }

} catch (Exception $e) {
    ajax::error(basename(__FILE__) . ' ' . $e->getMessage());
}
?>
