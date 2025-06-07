/* global $, jeedom, ajax, showAlert, log, jeeObject, eqLogic */

/**
 * Adds a command to the command table in the equipment configuration page.
 * @param {object} _cmd The command object.
 */
function addCmdToTable(_cmd) {
    if (!(_cmd instanceof Object)) {
        console.error('addCmdToTable: _cmd is not an object', _cmd);
        return;
    }

    var tr = $('<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">');
    tr.append($('<td>')
        .append($('<span class="cmdAttr" data-l1key="id" style="display:none;"></span>'))
        .append($('<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom de la commande}}">'))
    );
    tr.append($('<td>')
        .append($('<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableTypeNames[init(_cmd.type)] + '</span>'))
        .append($('<span class="subType" subType="' + init(_cmd.subType) + '" style="display:none;"></span>'))
    );
    tr.append($('<td>')
        .append($('<span><input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="isVisible" data-size="mini" data-label-text="{{Visible}}" checked/></span>'))
        .append($(' <input class="cmdAttr form-control input-sm" data-l1key="logicalId" disabled style="width : 180px;margin-top:5px;" placeholder="{{Logical ID}}">'))
        .append($(' <input class="cmdAttr form-control input-sm" data-l1key="generic_type" style="width : 120px; margin-top: 5px;" placeholder="{{Type Générique}}" title="{{Type générique Jeedom (optionnel)}}">'))
    );

    var $actionButtons = $('<td>');
    if (is_numeric(_cmd.id)) { // Check if the command is already saved
        $actionButtons.append($('<a class="btn btn-default btn-xs cmdAction" data-action="configure" title="{{Configuration avancée}}"><i class="fas fa-cogs"></i></a> '));
        $actionButtons.append($('<a class="btn btn-default btn-xs cmdAction" data-action="test" title="{{Tester la commande}}"><i class="fas fa-rss"></i> {{Tester}}</a>'));
    }
    // Commands are auto-managed by postSave, so removal here is generally not advised.
    // $actionButtons.append($('<a class="btn btn-danger btn-xs cmdAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i></a>'));
    tr.append($actionButtons);

    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType)); // Initialize type-specific display
    initBootstrapSwitch(); // Ensure new switches are initialized
}


/**
 * Called before displaying the equipment configuration page.
 * @param {object} _eqLogic The equipment object.
 */
function prePrintEqLogic(_eqLogic) {
    // Can be used to prepare data or UI elements before printEqLogic renders everything.
}

/**
 * Called after displaying the equipment configuration page.
 * Populates the command table and initializes UI elements.
 * @param {object} _eqLogic The equipment object.
 */
function postPrintEqLogic(_eqLogic) {
    $('#table_cmd tbody').empty(); // Clear existing commands
    if (_eqLogic.cmd) {
        for (var i in _eqLogic.cmd) {
            if (typeof _eqLogic.cmd[i] === 'object') {
                addCmdToTable(_eqLogic.cmd[i]);
            }
        }
    }
    // Any other specific initializations after the eqLogic is fully displayed.
}


// -- Event Listeners --

// Listener for the manual refresh button on the CONFIGURATION PAGE
$('body').off('click', '#btn_manualRefresh_cfgpage').on('click', '#btn_manualRefresh_cfgpage', function () {
    var eqLogicId = $('.eqLogicAttr[data-l1key=id]').value();
    if (!eqLogicId) {
        $('#div_alert').showAlert({message: '{{L\'équipement doit être sauvegardé au moins une fois avant de tester le rafraîchissement.}}', level: 'warning'});
        return;
    }

    var btn = $(this);
    btn.find('i').addClass('fa-spin');
    $('#div_alert').showAlert({message: '{{Demande de rafraîchissement envoyée...}}', level: 'info'});

    $.ajax({
        type: 'POST',
        url: 'plugins/googleNewsReader/core/ajax/googleNewsReader.ajax.php',
        data: {
            action: 'refreshData', // Should match the action in your AJAX file
            eqLogic_id: eqLogicId,
            ajax: 1
        },
        dataType: 'json',
        success: function(data) {
            btn.find('i').removeClass('fa-spin');
            if (data.state == 'ok') {
                $('#div_alert').showAlert({message: data.result || '{{Rafraîchissement effectué avec succès. Les nouvelles données seront visibles sur le widget.}}', level: 'success'});
            } else {
                $('#div_alert').showAlert({message: data.result || '{{Erreur lors du rafraîchissement.}}', level: 'danger'});
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            btn.find('i').removeClass('fa-spin');
            $('#div_alert').showAlert({message: '{{Erreur AJAX : }}' + textStatus + ' - ' + errorThrown, level: 'danger'});
        }
    });
});

// Listener for the manual refresh button on the WIDGET
// Assumes the button has class 'googleNewsReader-refresh-widget' and is within an eqLogicDisplayCard
$('body').off('click', '.eqLogicDisplayCard[data-eqType="googleNewsReader"] .googleNewsReader-refresh-widget').on('click', '.eqLogicDisplayCard[data-eqType="googleNewsReader"] .googleNewsReader-refresh-widget', function (event) {
    event.preventDefault();
    var $this = $(this);
    var eqLogic_id = $this.closest('.eqLogicDisplayCard').attr('data-eqLogic_id');

    if (!eqLogic_id) {
        console.error("Could not find eqLogic_id for widget refresh.");
        return;
    }

    var $icon = $this.find('i');
    $icon.addClass('fa-spin');

    // Using jeedom.ajax.prepare for consistency if preferred, or direct $.ajax
    jeedom.ajax.prepare({
        type: 'POST',
        url: 'plugins/googleNewsReader/core/ajax/googleNewsReader.ajax.php',
        data: {
            action: 'refreshData', // Action in googleNewsReader.ajax.php
            eqLogic_id: eqLogic_id,
            ajax: 1
        },
        dataType: 'json',
        success: function (data) {
            $icon.removeClass('fa-spin');
            if (data.state == 'ok') {
                //$('#div_alert').showAlert({message: '{{Flux Google News rafraîchi avec succès.}}', level: 'success'});
                // Jeedom should update the widget commands automatically.
                // If direct HTML update is needed (less ideal):
                // var widgetContainer = $('.eqLogicDisplayCard[data-eqLogic_id="' + eqLogic_id + '"] .widget-content-container');
                // if (data.resultHtml && widgetContainer.length) {
                //    widgetContainer.html(data.resultHtml);
                // } else {
                //    jeedom.eqLogic.request({ // This will re-render the widget based on new command values
                //        id: eqLogic_id,
                //        action: 'refreshWidget', // Custom action if you want specific widget refresh logic
                //        error: function () {},
                //        success: function (data) {
                //            $('.eqLogicDisplayCard[data-eqLogic_id=' + eqLogic_id + ']').empty().append(data.html);
                //        }
                //    });
                // }
                // For now, rely on Jeedom's automatic update of command values on dashboard.
                // A small visual feedback can be good.
                var originalColor = $this.css('color');
                $this.css('color', 'green');
                setTimeout(function() { $this.css('color', originalColor); }, 1000);

            } else {
                $('#div_alert').showAlert({message: data.result || '{{Erreur lors du rafraîchissement du flux.}}', level: 'danger'});
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $icon.removeClass('fa-spin');
            $('#div_alert').showAlert({message: '{{Erreur AJAX : }}' + textStatus + ' - ' + errorThrown, level: 'danger'});
        }
    });
});

// Standard Jeedom functions (printEqLogic, etc.) are called by core.
// printEqLogic is defined in plugin.template.js but can be overridden here if needed.
// We are using postPrintEqLogic to populate commands, which is good.

// If using the old way for printEqLogic without plugin.template.js overriding:
/*
function printEqLogic(_eqLogic) {
    $('.eqLogicDisplayCard').removeClass('active');
    $('.eqLogicDisplayCard[data-eqLogic_id="' + _eqLogic.id + '"]').addClass('active');
    $('.eqLogic').show(); // Show the configuration panel for the selected eqLogic

    // Populate generic attributes
    $('.eqLogicAttr').setValues(_eqLogic, '.eqLogicAttr');

    // Call pre and post print functions
    prePrintEqLogic(_eqLogic);
    postPrintEqLogic(_eqLogic); // This will call our command table population
}
*/
// Note: Jeedom's `plugin.template.js` now handles `printEqLogic` and calls `postPrintEqLogic`.
// So, defining `printEqLogic` here might be redundant unless for very specific overrides.
// The `postPrintEqLogic` for command table population is the key part.
// `addCmdToTable` is correctly defined.
// Ensure `initBootstrapSwitch()` is called if new switches are added dynamically.
// It's usually handled by Jeedom's core scripts after AJAX loads.
// If not, you might need to call it explicitly in addCmdToTable or postPrintEqLogic.
// jeedom.cmd.changeType also handles some UI initializations.

// Make sure this file is correctly included in your desktop/php/googleNewsReader.php
// e.g., <?php include_file('desktop', 'googleNewsReader', 'js', 'googleNewsReader'); ?>
// This is already done based on previous steps.
