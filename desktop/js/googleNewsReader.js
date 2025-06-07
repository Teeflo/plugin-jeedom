// Gestion du rafraîchissement depuis le widget sur le Dashboard
$(document).on('click', '.googleNewsReaderWidgetRefresh', function(event) {
    event.preventDefault(); // Empêcher le comportement par défaut du lien si c'en est un
    var eqLogic_id = $(this).closest('.eqLogic-widget').data('eqlogic_id');

    // Le bouton de rafraîchissement dans toHtml est défini avec la classe googleNewsReaderWidgetRefresh
    // et un attribut data-cmd_id. S'il y a un eqLogic_id directement sur le bouton, on le prend,
    // sinon on remonte au widget parent.
    if (!eqLogic_id) {
        eqLogic_id = $(this).data('eqlogic_id');
    }

    if (!eqLogic_id) {
        console.error('googleNewsReader: eqLogic_id manquant pour le bouton de rafraîchissement.');
        // Peut-être afficher une alerte plus visible à l'utilisateur
        // bootbox.alert("Erreur : ID de l'équipement manquant.");
        return;
    }

    var $refreshButton = $(this);
    var $icon = $refreshButton.find('i');
    var originalIconClass = $icon.attr('class'); // Sauvegarder la classe originale de l'icône
    $icon.removeClass().addClass('fas fa-spinner fa-spin'); // Afficher l'icône de chargement

    jeedom.ajax.prepare({
        type: 'POST',
        url: 'core/ajax/googleNewsReader.ajax.php',
        data: {
            action: 'refreshFeed',
            eqLogic_id: eqLogic_id,
            ajax: 1
        },
        dataType: 'json',
        success: function(data) {
            if (data.state == 'ok') {
                // Demander à Jeedom de mettre à jour le widget.
                // Cette fonction va récupérer le nouveau contenu HTML du widget auprès du serveur (via toHtml())
                // et le mettre à jour dans le DOM.
                jeedom.widget.update({widget: eqLogic_id});

                // data.result contient le message de succès de l'ajax, qui est géré par Jeedom globalement.
                // On pourrait vouloir un retour plus spécifique dans le widget lui-même si nécessaire.
                // Par exemple, mettre à jour un timestamp "dernière synchro" directement dans le widget
                // sans recharger tout le widget, mais update() est plus simple.
            } else {
                // L'erreur est normalement gérée et affichée par le système AJAX global de Jeedom
                // si ajax::error() est utilisé côté PHP.
                // On pourrait ajouter un message d'erreur spécifique au widget ici si nécessaire.
                console.error('googleNewsReader: Erreur lors du rafraîchissement - ', data.result);
                 // Afficher un message d'erreur plus visible à l'utilisateur si nécessaire
                // bootbox.alert("Erreur lors du rafraîchissement du flux :<br/>" + data.result);
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            console.error('googleNewsReader: Erreur AJAX - ', textStatus, errorThrown);
            // Afficher une erreur générique à l'utilisateur
            // bootbox.alert("Une erreur de communication AJAX est survenue.");
        },
        complete: function() {
            // Restaurer l'icône originale
            if (originalIconClass) {
                $icon.removeClass().addClass(originalIconClass);
            } else {
                $icon.removeClass('fa-spinner fa-spin').addClass('fa-sync'); // Fallback
            }
        }
    });
});

// Le reste du fichier (fonctions pour la page de configuration, etc.) peut rester tel quel
// s'il n'y avait rien d'autre dans le template.js original.
// Si template.js contenait des fonctions pour la gestion des commandes sur la page de config,
// comme addCmdToTable, elles sont généralement dépendantes de l'ID du plugin.
// Par exemple : function addCmdToTable(_cmd) { ... if (_cmd.eqType != 'googleNewsReader') return; ... }
// Mais le template de base utilise souvent des fonctions globales de plugin.template.js.
