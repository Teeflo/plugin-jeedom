<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'googleNews');
$eqLogics = eqLogic::byType('googleNews');
?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoPrimary" data-action="add">
                <i class="fas fa-plus-circle"></i>
                <br>
                <span>{{Ajouter}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br>
                <span>{{Configuration}}</span>
            </div>
        </div>
        <legend><i class="fas fa-rss"></i> {{Mes Flux Google News}}</legend>
        <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<img src="plugin_info/template_icon.png"/>'; // Using existing template icon
                echo "<br>";
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            </span>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="showEqLogic"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation" class="active"><a href="#" class="eqLogicAction" aria-controls="profile" role="tab" data-toggle="tab" data-action="showConfiguration"><i class="fas fa-cogs"></i> {{Configuration}}</a></li>
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="widget" role="tab" data-toggle="tab" data-action="showWidget"><i class="fas fa-eye"></i> {{Widget Aperçu}}</a></li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'équipement Google News}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement Google News}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (jeeObject::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Catégorie}}</label>
                            <div class="col-sm-8">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            <div role="tabpanel" class="tab-pane" id="configurationtab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{URL Google News}}</label>
                            <div class="col-sm-6">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="googleNewsUrl" placeholder="https://news.google.com/search?q=jeedom... ou /topics/..."/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nombre d'articles à afficher}}</label>
                            <div class="col-sm-1">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="maxDisplayArticles" placeholder="10" min="1" max="100"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nombre maximal d'articles à garder en base}}</label>
                            <div class="col-sm-1">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="maxArticles" placeholder="50" min="10" max="500"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Ordre de tri}}</label>
                            <div class="col-sm-3">
                                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="sortOrder">
                                    <option value="DESC">{{Plus récent en premier}}</option>
                                    <option value="ASC">{{Plus ancien en premier}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Fréquence de rafraîchissement}}</label>
                             <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="cronRefresh" placeholder="*/30 * * * *"/>
                                    <span class="input-group-btn">
                                        <a class="btn btn-default cursor listCmdAction" title="Valeur de la commande" id="bt_cronGenerator"><i class="fas fa-question-circle"></i></a>
                                    </span>
                                </div>
                                <small>{{Format CRON. Ex: "*/30 * * * *" pour toutes les 30 mins. Laisser vide pour désactiver le cron spécifique et utiliser le cron global du plugin.}}</small>
                            </div>
                        </div>
                         <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-3">
                                <a class="btn btn-primary" id="btn_manualRefresh"><i class="fas fa-sync"></i> {{Rafraîchir manuellement les articles}}</a>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            <div role="tabpanel" class="tab-pane" id="widgettab">
                <br/>
                <p>{{L'aperçu du widget sera affiché ici une fois configuré et sauvegardé.}}</p>
                <p>{{Assurez-vous d'avoir sauvegardé les modifications pour voir l'aperçu correct.}}</p>
                <div class="widgetPreview">
                    <!-- Preview will be loaded here by JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="plugins/googleNews/assets/js/googleNews.js"></script>

<script>
$(document).ready(function() {
    // Handler for manual refresh button
    $('#btn_manualRefresh').on('click', function() {
        var eqLogic_id = $('.eqLogicAttr[data-l1key=id]').value();
        if (!eqLogic_id) {
            $('#div_alert').showAlert({message: '{{Veuillez sauvegarder l'équipement avant de rafraîchir.}}', level: 'warning'});
            return;
        }
        $.ajax({
            type: 'POST',
            url: 'plugins/googleNews/core/ajax/googleNews.ajax.php',
            data: {
                action: 'refreshArticles',
                eqLogic_id: eqLogic_id
            },
            dataType: 'json',
            success: function(data) {
                if (data.state == 'ok') {
                    $('#div_alert').showAlert({message: '{{Rafraîchissement manuel effectué avec succès.}}', level: 'success'});
                    // Optionally, trigger widget preview update here
                } else {
                    $('#div_alert').showAlert({message: data.result, level: 'danger'});
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $('#div_alert').showAlert({message: '{{Erreur lors du rafraîchissement manuel:}} ' + errorThrown, level: 'danger'});
            }
        });
    });

    // Show widget preview tab content
    $('.eqLogicAction[data-action=showWidget]').on('click', function () {
        var eqLogic_id = $('.eqLogicAttr[data-l1key=id]').value();
        if (!eqLogic_id) {
             $('#widgettab .widgetPreview').html('<p>{{Veuillez d'abord sauvegarder l'équipement.}}</p>');
            return;
        }
        // Ajax call to get widget content
        $.ajax({
            type: 'POST',
            url: 'plugins/googleNews/core/ajax/googleNews.ajax.php', // We'll create this ajax handler
            data: {
                action: 'getWidgetHTML',
                eqLogic_id: eqLogic_id,
                desktop: 1 // Indicate it's for desktop preview
            },
            dataType: 'html',
            success: function(html) {
                $('#widgettab .widgetPreview').html(html);
            },
            error: function(xhr, textStatus, errorThrown) {
                 $('#widgettab .widgetPreview').html('<p>{{Erreur lors du chargement de l'aperçu du widget:}} '+ errorThrown +'</p>');
            }
        });
    });

    // Cron generator helper
    $('#bt_cronGenerator').on('click', function() {
        $('#md_modal').dialog({
            title: "{{Générateur de CRON}}",
            resizable: true,
            width: 800,
            height: 500,
            modal: true,
            buttons: {
                "Fermer": function() {
                    $(this).dialog("close");
                }
            }
        });
        $('#md_modal').load('index.php?v=d&modal=cron.generator&rand=' + Math.floor((Math.random() * 1000000) + 1)).dialog('open');
    });

});
</script>
