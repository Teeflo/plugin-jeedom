<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('googleNewsReader');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
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
        <legend><i class="fas fa-newspaper"></i> {{Mes Flux Google News}}</legend>
        <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
        <div class="eqLogicThumbnailContainer">
            <?php
            // This section displays thumbnails for existing equipment on the plugin setup page.
            // The actual dashboard widget display is handled by Jeedom calling $eqLogic->toHtml()
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
                echo '<br>';
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
                <a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
                <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
                <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            </span>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="eqlogictab" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Équipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="commandtab" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow-y:auto;overflow-x:hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br />
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement Google News}}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (jeeObject::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . htmlentities($object->getName()) . '</option>';
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
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
                            </div>
                        </div>

                        <legend>{{Configuration Spécifique}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{URL Google News}}</label>
                            <div class="col-sm-7">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="googleNewsUrl" placeholder="https://news.google.com/search?q=jeedom&hl=fr&gl=FR&ceid=FR:fr"/>
                            </div>
                            <div class="col-sm-1">
                                <span class="tooltips cursor" title="{{Pour obtenir cette URL, naviguez sur Google News (recherche, rubrique), puis copiez l'URL depuis la barre d'adresse de votre navigateur. Exemples: Recherche: 'https://news.google.com/search?q=jeedom&hl=fr&gl=FR&ceid=FR%3Afr', Sujet: 'https://news.google.com/topics/CAAqIQgKIhtDQkFTRGdvSUwyMHZNR1J4ZFpJU0VnSmxiaWdBUAE?hl=fr&gl=FR&ceid=FR%3Afr'}}"><i class="fas fa-question-circle"></i></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nombre d'articles à afficher}}</label>
                            <div class="col-sm-2">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="nb_articles_to_display" min="1" max="20" placeholder="5"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Langue du flux}}</label>
                            <div class="col-sm-3">
                                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="rss_lang">
                                    <option value="fr">{{Français}}</option>
                                    <option value="en">{{English}}</option>
                                    <option value="de">{{Deutsch}}</option>
                                    <option value="es">{{Español}}</option>
                                    <option value="it">{{Italiano}}</option>
                                    <option value="pt">{{Português}}</option>
                                    <option value="nl">{{Nederlands}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Pays du flux}}</label>
                            <div class="col-sm-3">
                                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="rss_country">
                                    <option value="FR">{{France}}</option>
                                    <option value="US">{{United States}}</option>
                                    <option value="GB">{{United Kingdom}}</option>
                                    <option value="DE">{{Germany}}</option>
                                    <option value="ES">{{España}}</option>
                                    <option value="IT">{{Italia}}</option>
                                    <option value="BE">{{Belgique}}</option>
                                    <option value="CA">{{Canada}}</option>
                                    <option value="CH">{{Suisse}}</option>
                                    <option value="PT">{{Portugal}}</option>
                                    <option value="NL">{{Netherlands}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Informations de rafraîchissement}}</label>
                            <div class="col-sm-7">
                                <span class="help-block">{{Le plugin utilise le cron horaire global de Jeedom pour mettre à jour les flux.}}</span>
                                <span class="help-block">{{Prochain lancement global du cron horaire :}} <?php echo cron::previsuCron('cronHourly'); ?></span>
                                <?php
                                    // Attempt to get specific cron for this eqLogic
                                    // Note: $eqLogic might not be defined here in the initial page load (before an eqLogic is selected)
                                    // This part might be better handled by JS after eqLogic load, or needs to be robust for $eqLogic being null.
                                    $eqLogic_id =鋳造('id'); // Placeholder, real ID comes from JS context
                                    if ($eqLogic_id && $eqLogic_id != '') {
                                        $cron = cron::byClassAndFunction('googleNewsReader', 'cronHourly', array('eqLogic_id' => $eqLogic_id));
                                        if (is_object($cron) && $cron->getEnable()) {
                                            echo '<span class="help-block">{{Dernière exécution pour cet équipement : }} ' . $cron->lastRunFormated() . '</span>';
                                        }
                                    }
                                ?>
                                 <a class="btn btn-info btn-xs" id="btn_manualRefresh_cfgpage"><i class="fas fa-sync"></i> {{Tester le rafraîchissement}}</a>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            <div role="tabpanel" class="tab-pane" id="commandtab">
                <br />
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>{{ID}}</th>
                            <th>{{Nom}}</th>
                            <th>{{Type}}</th>
                            <th>{{Sous-Type}}</th>
                            <th>{{Paramètres}}</th>
                            <th>{{Action}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'googleNewsReader', 'js', 'googleNewsReader'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
