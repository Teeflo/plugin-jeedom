<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'googleNews'); // Important pour la gestion JS de Jeedom
$eqLogics = eqLogic::byType('googleNews');
?>

<div class="row row-overflow">
    <!-- Panel de gestion des équipements -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoSecondary" data-action="add">
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

        <legend><i class="fas fa-newspaper"></i> {{Mes Flux Google News}}</legend>
        <?php
        if (count($eqLogics) == 0) {
            echo "<br/><div class='text-center' style='font-size:1.2em;font-weight:bold;'>{{Aucun équipement Google News trouvé, cliquez sur Ajouter pour commencer}}</div>";
        } else {
        ?>
            <div class="eqLogicThumbnailContainer">
                <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : 'disable';
                    echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
                    echo '<img src="' . $eqLogic->getImage() . '"/>'; // Utilise l'icône définie dans info.json par défaut
                    echo "<br>";
                    echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
        <?php } ?>
    </div>

    <!-- Panel de configuration de l'équipement -->
    <div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
                <a class="btn btn-default btn-sm eqLogicAction"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
                <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
                <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            </span>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="showEqLogic"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation" class="active"><a href="#" class="eqLogicAction" aria-controls="profile" role="tab" data-toggle="tab" data-action="showObject"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
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
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" data-l2key="name" placeholder="{{Nom de l'équipement Google News}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id" data-l2key="object_id">
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
                            <div class="col-sm-9">
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
                            <div class="col-sm-9">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" data-l2key="isEnable" checked/>{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" data-l2key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>

                        <legend>{{Configuration du flux}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{URL Google News}}</label>
                            <div class="col-sm-6">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="googleNewsUrl" placeholder="https://news.google.com/topics/CAAqJggK... ou https://news.google.com/publications/CAAq..."/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Rétention des articles (jours)}}</label>
                            <div class="col-sm-2">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="retention_days" placeholder="7" min="1" max="365"/>
                            </div>
                            <div class="col-sm-7">
                                 <small>{{Nombre de jours pendant lesquels les articles sont conservés en base (défaut 7).}}</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Max articles pour widget}}</label>
                            <div class="col-sm-2">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="widget_max_articles" placeholder="5" min="1" max="50"/>
                            </div>
                             <div class="col-sm-7">
                                 <small>{{Nombre maximum d'articles à récupérer pour l'affichage dans le widget (défaut 5).}}</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Fréquence de rafraîchissement (cron)}}</label>
                            <div class="col-sm-3">
                               <div class="input-group">
                                    <input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="cron_schedule" placeholder="{{Optionnel, ex: */30 * * * * pour toutes les 30 min}}"/>
                                    <span class="input-group-btn">
                                        <a class="btn btn-default listCmdAction CronPropagationTest"><i class="fas fa-question-circle"></i></a>
                                    </span>
                                </div>
                                <small>{{Laissez vide si vous ne souhaitez pas de rafraîchissement automatique par cron. Le bouton Rafraîchir sera toujours disponible.}}</small>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            <div role="tabpanel" class="tab-pane" id="commandtab">
                <br/>
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th style="width: 250px;">{{Nom}}</th>
                            <th style="width: 150px;">{{Type}}</th>
                            <th style="width: 200px;">{{Options}}</th>
                            <th style="width: 150px;">{{Action}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'googleNews', 'js', 'googleNews'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
