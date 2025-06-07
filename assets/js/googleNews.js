// assets/js/googleNews.js
// Standard Jeedom plugin JS for the equipment page might go here.
// For now, specific interactions are mostly handled in desktop/php/googleNews.php

// This function is a placeholder if needed by Jeedom's core for specific cmd interactions.
function googleNewsEqLogicCmd(cmd_id, value) {
    jeedom.cmd.execute({
        id: cmd_id,
        value: value,
        success: function(result) {
            // console.log('Command success:', result);
        },
        error: function(error) {
            // console.error('Command error:', error);
        }
    });
}

$(document).ready(function() {
    // console.log("googleNews.js loaded for equipment page.");
    // Additional JS for the equipment configuration page can be added here if necessary.
    // The manual refresh and widget preview JS is currently inlined in desktop/php/googleNews.php.
});

// Widget specific JS, if any, that needs to be globally available or called from widget.
// For example, a function to manually trigger widget content refresh via AJAX.
// This would be called from an element within the widget's HTML.
function googleNews_refreshWidgetContent(eqLogicId) {
    var widgetDiv = $('.eqLogicDisplay[data-eqLogic_id=' + eqLogicId + ']');
    if (!widgetDiv.length) {
        // Try finding by data-l1key if it's a dashboard widget context
        widgetDiv = $('.eqLogic[data-eqLogic_id=' + eqLogicId + ']');
    }

    // Show some loading indicator in the widget
    var contentDiv = widgetDiv.find('.widget-content'); // Assuming a div with class 'widget-content'
    if(contentDiv.length === 0) { // Fallback for different structures
        contentDiv = widgetDiv.find('div[data-template=core\\template\\dashboard\\googleNews]');
    }


    if(contentDiv.length > 0) {
        contentDiv.html('<i class="fas fa-sync fa-spin"></i> {{Chargement...}}');
    } else {
         // If specific content div is not found, can't show loading specific there.
         // console.log("Widget content area not found for loading indicator.");
    }


    $.ajax({
        type: 'POST',
        url: 'plugins/googleNews/core/ajax/googleNews.ajax.php',
        data: {
            action: 'getWidgetHTML', // Action to get the full widget HTML
            eqLogic_id: eqLogicId,
            isWidget: 1 // Indicate it's for actual widget display
        },
        dataType: 'html',
        success: function(data) {
            // Replace the content of the specific widget
            // The widget's root div should have a unique ID or class
            // Example: id="googleNewsWidget_<eqLogic_id>"
            // For now, replacing the content of eqLogicDisplay
            widgetDiv.empty().html($(data).find('.widget-googleNews').parent().html());
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(contentDiv.length > 0) {
                contentDiv.html('<i class="fas fa-exclamation-triangle"></i> {{Erreur de chargement du widget.}}');
            }
            console.error("AJAX error refreshing widget: " + textStatus, errorThrown);
        }
    });
}
