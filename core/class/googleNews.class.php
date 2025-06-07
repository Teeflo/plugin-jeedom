<?php

if (!class_exists('eqLogic')) {
    require_once dirname(__FILE__) . '/../../../../core/class/eqLogic.class.php';
}

class googleNews extends eqLogic {

    public static $table_name = 'googleNews_articles'; // Nom de la table pour les articles

    // Méthodes standard de gestion d'équipement
    public function preSave() {
        // Logique à exécuter avant la sauvegarde de l'équipement
        // Par exemple, validation de l'URL
        $this->validateGoogleNewsUrl();
    }

    public function postSave() {
        // Logique à exécuter après la sauvegarde de l'équipement
        // Création/Mise à jour des commandes
        $this->createPluginCommands();
    }

    public function preRemove() {
        // Logique à exécuter avant la suppression de l'équipement
        // Par exemple, supprimer les articles liés de la base de données
        $this->removeArticlesForEquipment();
    }

    public function postRemove() {
        // Logique à exécuter après la suppression de l'équipement
    }

    // Validation de l'URL Google News (appelée dans preSave)
    private function validateGoogleNewsUrl() {
        $url = $this->getConfiguration('googleNewsUrl');
        if (empty($url)) {
            // Permettre une URL vide si l'utilisateur ne veut pas encore la configurer
            return;
        }

        if (!preg_match('/^https:\/\/news\.google\.com\/(topics|publications)\/[a-zA-Z0-9_-]+(\?.*)?$/', $url)) {
            throw new Exception(__('L\'URL Google News fournie n\'est pas valide. Elle doit commencer par https://news.google.com/topics/ ou https://news.google.com/publications/', __FILE__));
        }
    }

    // Suppression des articles pour cet équipement (appelée dans preRemove)
    private function removeArticlesForEquipment() {
        if (empty($this->getId())) {
            return;
        }
        $sql = 'DELETE FROM ' . self::$table_name . ' WHERE eqLogic_id = :eqLogic_id';
        $params = array(':eqLogic_id' => $this->getId());
        try {
            DB::Prepare($sql, $params, DB::FETCH_TYPE_ROW);
            log::add('googleNews', 'info', 'Suppression des articles pour l\'équipement ID ' . $this->getId());
        } catch (Exception $e) {
            log::add('googleNews', 'error', 'Erreur lors de la suppression des articles pour l\'équipement ID ' . $this->getId() . ': ' . $e->getMessage());
        }
    }

    // Méthode pour créer/mettre à jour les commandes
    public function createPluginCommands() {
        // Commande Action pour rafraîchir
        $cmd = $this->getCmd(null, 'refresh');
        if (!is_object($cmd)) {
            $cmd = new cmd();
            $cmd->setName(__('Rafraîchir', __FILE__));
            $cmd->setLogicalId('refresh');
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
        }

        // Commande Info pour les derniers articles (stockés en JSON)
        $cmdArticles = $this->getCmd(null, 'last_articles_json');
        if (!is_object($cmdArticles)) {
            $cmdArticles = new cmd();
            $cmdArticles->setName(__('Derniers Articles JSON', __FILE__));
            $cmdArticles->setLogicalId('last_articles_json');
            $cmdArticles->setType('info');
            $cmdArticles->setSubType('string');
            $cmdArticles->setEqLogic_id($this->getId());
            $cmdArticles->setIsVisible(0);
            $cmdArticles->save();
        }

        // Commande Info pour le nombre d'articles
        $cmdNbArticles = $this->getCmd(null, 'nb_articles');
        if (!is_object($cmdNbArticles)) {
            $cmdNbArticles = new cmd();
            $cmdNbArticles->setName(__('Nombre d\'articles', __FILE__));
            $cmdNbArticles->setLogicalId('nb_articles');
            $cmdNbArticles->setType('info');
            $cmdNbArticles->setSubType('numeric');
            $cmdNbArticles->setEqLogic_id($this->getId());
            $cmdNbArticles->setIsVisible(1);
            $cmdNbArticles->save();
        }
        $this->checkAndUpdateCmd('nb_articles', 0);


        // Commande Info pour le statut du rafraîchissement
        $cmdStatus = $this->getCmd(null, 'status');
        if (!is_object($cmdStatus)) {
            $cmdStatus = new cmd();
            $cmdStatus->setName(__('Statut Rafraîchissement', __FILE__));
            $cmdStatus->setLogicalId('status');
            $cmdStatus->setType('info');
            $cmdStatus->setSubType('string');
            $cmdStatus->setEqLogic_id($this->getId());
            $cmdStatus->setIsVisible(1);
            $cmdStatus->save();
        }
        $this->checkAndUpdateCmd('status', __('N/A', __FILE__));
    }

    // Méthode principale de rafraîchissement des news
    public function refreshNews() {
        log::add('googleNews', 'info', 'Début du rafraîchissement pour l\'équipement: ' . $this->getHumanName());
        $this->checkAndUpdateCmd('status', __('En cours...', __FILE__));

        $googleNewsUrl = $this->getConfiguration('googleNewsUrl');
        if (empty($googleNewsUrl)) {
            log::add('googleNews', 'error', 'URL Google News non configurée pour: ' . $this->getHumanName());
            $this->checkAndUpdateCmd('status', __('Erreur: URL non configurée', __FILE__));
            return false;
        }

        if (!preg_match('/^https:\/\/news\.google\.com\/(topics|publications)\/([a-zA-Z0-9_-]+)(\?.*)?$/', $googleNewsUrl, $matches)) {
            log::add('googleNews', 'error', 'URL Google News invalide: ' . $googleNewsUrl . ' pour: ' . $this->getHumanName());
            $this->checkAndUpdateCmd('status', __('Erreur: URL invalide', __FILE__));
            return false;
        }

        $rssUrl = 'https://news.google.com/rss/' . $matches[1] . '/' . $matches[2];
        if (isset($matches[3]) && !empty($matches[3])) {
            $queryParams = ltrim($matches[3], '?');
             $rssUrl .= '?' . $queryParams;
        }

        log::add('googleNews', 'debug', 'URL RSS générée: ' . $rssUrl . ' pour: ' . $this->getHumanName());

        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $rssUrl);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Jeedom_googleNews_Plugin/0.1');
            $xmlContent = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode != 200 || $xmlContent === false) {
                throw new Exception('Erreur de récupération du flux RSS (HTTP code: ' . $httpCode . ')');
            }
        } catch (Exception $e) {
            log::add('googleNews', 'error', 'Erreur de récupération du flux RSS pour ' . $rssUrl . ': ' . $e->getMessage());
            $this->checkAndUpdateCmd('status', __('Erreur: Récupération flux', __FILE__));
            return false;
        }

        try {
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($xmlContent);
            if ($xml === false) {
                $errors = libxml_get_errors();
                $errorMsg = '';
                foreach ($errors as $error) {
                    $errorMsg .= trim($error->message) . ' | ';
                }
                libxml_clear_errors();
                throw new Exception('Erreur de parsing XML: ' . $errorMsg);
            }

            $articles = [];
            foreach ($xml->channel->item as $item) {
                $title = (string)$item->title;
                $link = (string)$item->link;
                $pubDateStr = (string)$item->pubDate;
                $description = (string)$item->description;
                $guid = (string)$item->guid;

                $pubDateTime = new DateTime($pubDateStr);
                $pubDate = $pubDateTime->format('Y-m-d H:i:s');

                $articles[] = [
                    'guid' => $guid,
                    'title' => $title,
                    'link' => $link,
                    'pubDate' => $pubDate,
                    'description' => strip_tags($description)
                ];
            }
        } catch (Exception $e) {
            log::add('googleNews', 'error', 'Erreur de parsing XML pour ' . $rssUrl . ': ' . $e->getMessage());
            $this->checkAndUpdateCmd('status', __('Erreur: Parsing XML', __FILE__));
            return false;
        }

        if (empty($articles)) {
            log::add('googleNews', 'info', 'Aucun article trouvé dans le flux RSS pour: ' . $this->getHumanName());
            $this->checkAndUpdateCmd('status', __('OK (0 article)', __FILE__));
            $this->checkAndUpdateCmd('last_articles_json', json_encode([]));
            $this->checkAndUpdateCmd('nb_articles', 0);
            return true;
        }

        $nbAdded = 0;
        $nbErrors = 0;
        foreach ($articles as $article) {
            try {
                $sql_check = 'SELECT id FROM `' . self::$table_name . '` WHERE eqLogic_id = :eqLogic_id AND guid = :guid';
                $params_check = [':eqLogic_id' => $this->getId(), ':guid' => $article['guid']];
                $existing = DB::Prepare($sql_check, $params_check, DB::FETCH_TYPE_ROW);

                if (!$existing) {
                    $sql = 'INSERT INTO `' . self::$table_name . '` (eqLogic_id, guid, title, link, pubDate, description) VALUES (:eqLogic_id, :guid, :title, :link, :pubDate, :description)';
                    $params = [
                        ':eqLogic_id' => $this->getId(),
                        ':guid' => $article['guid'],
                        ':title' => $article['title'],
                        ':link' => $article['link'],
                        ':pubDate' => $article['pubDate'],
                        ':description' => $article['description']
                    ];
                    DB::Prepare($sql, $params, DB::FETCH_TYPE_ROW);
                    $nbAdded++;
                }
            } catch (Exception $e) {
                log::add('googleNews', 'error', 'Erreur d\'insertion en BDD pour l\'article "' . $article['title'] . '": ' . $e->getMessage());
                $nbErrors++;
            }
        }
        log::add('googleNews', 'info', $nbAdded . ' article(s) ajouté(s), ' . $nbErrors . ' erreur(s) pour: ' . $this->getHumanName());

        $retentionDays = (int)$this->getConfiguration('retention_days', 7);
        if ($retentionDays > 0) {
            try {
                $sql_delete_old = 'DELETE FROM `' . self::$table_name . '` WHERE eqLogic_id = :eqLogic_id AND pubDate < DATE_SUB(NOW(), INTERVAL :retention_days DAY)';
                $params_delete_old = [':eqLogic_id' => $this->getId(), ':retention_days' => $retentionDays];
                DB::Prepare($sql_delete_old, $params_delete_old, DB::FETCH_TYPE_ROW);
                log::add('googleNews', 'debug', 'Suppression des articles de plus de ' . $retentionDays . ' jours pour: ' . $this->getHumanName());
            } catch (Exception $e) {
                log::add('googleNews', 'error', 'Erreur lors de la suppression des anciens articles: ' . $e->getMessage());
            }
        }

        $this->updateLastArticlesJsonCmd();

        $this->checkAndUpdateCmd('status', __('OK', __FILE__));
        return true;
    }

    public function updateLastArticlesJsonCmd() {
        $maxWidgetArticles = (int)$this->getConfiguration('widget_max_articles', 5);
        try {
            $sql_select = 'SELECT title, link, pubDate, description FROM `' . self::$table_name . '` WHERE eqLogic_id = :eqLogic_id ORDER BY pubDate DESC LIMIT :limit';
            $params_select = [':eqLogic_id' => $this->getId(), ':limit' => $maxWidgetArticles];
            $latest_articles = DB::Prepare($sql_select, $params_select, DB::FETCH_TYPE_ALL);

            if ($latest_articles === false) $latest_articles = [];

            $this->checkAndUpdateCmd('last_articles_json', json_encode($latest_articles));

            $sql_count = 'SELECT COUNT(id) as nb FROM `' . self::$table_name . '` WHERE eqLogic_id = :eqLogic_id';
            $params_count = [':eqLogic_id' => $this->getId()];
            $count_result = DB::Prepare($sql_count, $params_count, DB::FETCH_TYPE_ROW);
            $nbTotalArticles = ($count_result && isset($count_result['nb'])) ? $count_result['nb'] : 0;
            $this->checkAndUpdateCmd('nb_articles', $nbTotalArticles);

        } catch (Exception $e) {
            log::add('googleNews', 'error', 'Erreur lors de la mise à jour de last_articles_json ou nb_articles pour eqLogic_id ' . $this->getId() . ': ' . $e->getMessage());
        }
    }

    public function execute($_options = array()) {
        $cmd = cmd::byId(str_replace('#', '', $_options['cmd_id']));
        if (is_object($cmd) && $cmd->getEqLogic_id() == $this->getId()) {
            switch ($cmd->getLogicalId()) {
                case 'refresh':
                    $this->refreshNews();
                    break;
            }
        }
    }

    public function getWidgetData() {
        $articles = [];
        try {
            $cmdJson = $this->getCmd(null, 'last_articles_json');
            if (is_object($cmdJson)) {
                $articles = json_decode($cmdJson->execCmd(), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $articles = [];
                }
            }
        } catch (Exception $e) {
            log::add('googleNews', 'error', 'Erreur récupération données widget pour ' . $this->getHumanName() . ': ' . $e->getMessage());
        }
        return $articles;
    }
}
?>
