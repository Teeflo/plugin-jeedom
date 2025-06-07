<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class googleNewsReader extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*     * ***********************Methode static*************************** */

  public static function convertGoogleNewsUrlToRss(string $googleNewsUrl): ?string {
    if (strpos($googleNewsUrl, 'news.google.com/topics/') !== false) {
        // Example: https://news.google.com/topics/CAAqJggKJ BURNAnD4Kz8gEApLACKpLACMmpLAZ0hOd8O0zaA8?hl=fr&gl=FR&ceid=FR%3Afr
        // RSS: https://news.google.com/rss/topics/CAAqJggKJ BURNAnD4Kz8gEApLACKpLACMmpLAZ0hOd8O0zaA8?hl=fr&gl=FR&ceid=FR%3Afr
        return str_replace('/topics/', '/rss/topics/', $googleNewsUrl);
    } elseif (strpos($googleNewsUrl, 'news.google.com/search?q=') !== false) {
        // Example: https://news.google.com/search?q=jeedom&hl=fr&gl=FR&ceid=FR%3Afr
        // RSS: https://news.google.com/rss/search?q=jeedom&hl=fr&gl=FR&ceid=FR%3Afr
        return str_replace('/search?q=', '/rss/search?q=', $googleNewsUrl);
    } elseif (strpos($googleNewsUrl, 'news.google.com/gn/news/headlines/section/topic/') !== false) {
        // Example: https://news.google.com/gn/news/headlines/section/topic/TECHNOLOGY?ned=fr&hl=fr&gl=FR
        // RSS: https://news.google.com/news?cf=rss&ned=fr&hl=fr&gl=FR&topic=TECHNOLOGY
        // This format seems older or less common, prioritize topic and search.
        // A more robust solution might involve parsing the URL parameters.
        // For now, let's assume this specific transformation or focus on the /topics/ and /search?q=
        // For simplicity, we'll return null if this complex case is met, favoring the other two.
         parse_str(parse_url($googleNewsUrl, PHP_URL_QUERY), $queryParams);
         if (isset($queryParams['topic'])) {
             return 'https://news.google.com/rss/headlines/section/topic/' . $queryParams['topic'] . '?ned=' . ($queryParams['ned'] ?? 'us') . '&hl=' . ($queryParams['hl'] ?? 'en') . '&gl=' . ($queryParams['gl'] ?? 'US');
         }
         return null;
    }
    log::add('googleNewsReader', 'error', 'URL Google News non reconnue: ' . $googleNewsUrl);
    return null;
  }

  /*     * *********************Méthodes d'instance************************* */

  private function createCmdInfo($logicalId, $name, $subType = 'string', $generic_type = null, $visible = 1) {
    $cmd = $this->getCmd(null, $logicalId);
    if (!is_object($cmd)) {
        $cmd = new cmd();
        $cmd->setEqLogic_id($this->getId());
        $cmd->setLogicalId($logicalId);
        $cmd->setType('info');
        $cmd->setSubType($subType);
        $cmd->setName($name);
        $cmd->setIsVisible($visible);
        if ($generic_type !== null) {
            $cmd->setGeneric_type($generic_type);
        }
        $cmd->setTemplate('dashboard', 'default');
        $cmd->setTemplate('mobile', 'default');
    }
    // Pour s'assurer que le nom est à jour si on le change
    $cmd->setName($name);
    $cmd->save();
    return $cmd;
  }

  public function pull() {
    log::add('googleNewsReader', 'info', 'Début du rafraîchissement pour : ' . $this->getHumanName());
    $rssUrl = $this->getConfiguration('rssUrl');
    $nbArticlesToDisplay = (int)$this->getConfiguration('nbArticles', 5); // Valeur par défaut 5 si non configuré

    if (empty($rssUrl)) {
        log::add('googleNewsReader', 'warning', 'URL RSS non configurée pour : ' . $this->getHumanName());
        $this->checkAndUpdateCmd('lastUpdate', date('Y-m-d H:i:s') . ' - Erreur: URL RSS non configurée');
        return;
    }

    $articles = $this->fetchAndParseRss($rssUrl);

    if (empty($articles)) {
        log::add('googleNewsReader', 'warning', 'Aucun article récupéré depuis : ' . $rssUrl . ' pour ' . $this->getHumanName());
        $this->checkAndUpdateCmd('title', 'Aucun article trouvé');
        $this->checkAndUpdateCmd('link', '');
        $this->checkAndUpdateCmd('description', 'Vérifiez la configuration ou le flux RSS.');
        $this->checkAndUpdateCmd('pubDate', '');
        $this->checkAndUpdateCmd('lastUpdate', date('Y-m-d H:i:s') . ' - Aucun article');
        // On ne vide pas les articles stockés ici, on les gérera via le widget ou une autre logique.
        // On met juste à jour les commandes "dernier article".
        return;
    }

    // Mettre à jour les commandes avec le premier article (le plus récent si trié)
    // Le widget s'occupera d'afficher la liste complète.
    // Ces commandes sont pour une info rapide ou scénario.
    $latestArticle = $articles[0];
    $this->checkAndUpdateCmd('title', $latestArticle['title']);
    $this->checkAndUpdateCmd('link', $latestArticle['link']);

    // Tronquer la description pour la commande info, le widget affichera plus
    $descriptionShort = mb_substr($latestArticle['description'], 0, 250);
    if (mb_strlen($latestArticle['description']) > 250) {
        $descriptionShort .= '...';
    }
    $this->checkAndUpdateCmd('description', $descriptionShort);
    $this->checkAndUpdateCmd('pubDate', $latestArticle['pubDate']);

    // Stocker les articles récupérés dans une configuration de l'équipement (ou une table dédiée plus tard)
    // Pour l'instant, on stocke un nombre limité d'articles directement dans la config.
    // On trie par date de publication décroissante avant de stocker.
    usort($articles, function($a, $b) {
        return strtotime($b['pubDate']) - strtotime($a['pubDate']);
    });
    $articlesToStore = array_slice($articles, 0, $nbArticlesToDisplay);
    $this->setConfiguration('storedArticles', json_encode($articlesToStore));

    log::add('googleNewsReader', 'info', count($articlesToStore) . ' articles stockés pour ' . $this->getHumanName());
    $this->checkAndUpdateCmd('lastUpdate', date('Y-m-d H:i:s'));
    log::add('googleNewsReader', 'info', 'Rafraîchissement terminé pour : ' . $this->getHumanName());
  }

  public function fetchAndParseRss(string $rssUrl): array {
    $articles = [];
    $context = stream_context_create([
        'http' => [
            'timeout' => 10, // 10 seconds timeout
            'header' => "User-Agent: JeedomPlugin/googleNewsReader\r\n"
        ]
    ]);

    $xmlString = @file_get_contents($rssUrl, false, $context);

    if ($xmlString === false) {
        log::add('googleNewsReader', 'error', 'Impossible de récupérer le flux RSS depuis : ' . $rssUrl);
        return $articles;
    }

    // Replace non-breaking space with regular space
    $xmlString = str_replace('&nbsp;', ' ', $xmlString);

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlString);

    if ($xml === false) {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            log::add('googleNewsReader', 'error', 'Erreur parsing XML: ' . $error->message . ' (ligne: ' . $error->line . ')');
        }
        libxml_clear_errors();
        log::add('googleNewsReader', 'error', 'Échec du parsing du flux RSS pour : ' . $rssUrl . '. Contenu reçu: ' . substr($xmlString, 0, 500));
        return $articles;
    }

    if (isset($xml->channel->item)) {
        foreach ($xml->channel->item as $item) {
            $title = (string)$item->title;
            $link = (string)$item->link;
            $pubDate = (string)$item->pubDate;
            $description = (string)$item->description; // Often contains HTML

            // Clean up description (basic HTML removal)
            $description = preg_replace('/<br\s*\/?>/i', "\n", $description); // Convert <br> to newlines
            $description = strip_tags($description); // Remove all other HTML tags
            $description = html_entity_decode($description, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $description = trim($description);

            // Format date
            try {
                $date = new DateTime($pubDate);
                $formattedPubDate = $date->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                log::add('googleNewsReader', 'warning', 'Date de publication invalide pour l\'article "' . $title . '": ' . $pubDate . '. Erreur: ' . $e->getMessage());
                $formattedPubDate = date('Y-m-d H:i:s'); // Fallback to current date
            }

            $articles[] = [
                'title' => $title,
                'link' => $link,
                'pubDate' => $formattedPubDate,
                'description' => $description,
            ];
        }
    } elseif (isset($xml->entry)) { // Atom feed format (sometimes used by Google News)
         foreach ($xml->entry as $entry) {
            $title = (string)$entry->title;
            $link = '';
            if (isset($entry->link) && isset($entry->link['href'])) {
                $link = (string)$entry->link['href'];
            }
            $pubDate = (string)$entry->updated; // Atom uses <updated>
            $description = '';
            if (isset($entry->summary)) { // Atom uses <summary>
                $description = (string)$entry->summary;
            } elseif (isset($entry->content)) { // Or <content>
                $description = (string)$entry->content;
            }

            // Clean up description (basic HTML removal)
            $description = preg_replace('/<br\s*\/?>/i', "\n", $description); // Convert <br> to newlines
            $description = strip_tags($description); // Remove all other HTML tags
            $description = html_entity_decode($description, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $description = trim($description);

            // Format date
            try {
                $date = new DateTime($pubDate);
                $formattedPubDate = $date->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                log::add('googleNewsReader', 'warning', 'Date de publication invalide pour l\'article "' . $title . '": ' . $pubDate . '. Erreur: ' . $e->getMessage());
                $formattedPubDate = date('Y-m-d H:i:s'); // Fallback to current date
            }

            $articles[] = [
                'title' => $title,
                'link' => $link,
                'pubDate' => $formattedPubDate,
                'description' => $description,
            ];
        }
    } else {
        log::add('googleNewsReader', 'warning', 'Aucun article trouvé dans le flux RSS: ' . $rssUrl . '. Vérifiez la structure du flux.');
    }
    return $articles;
  }

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    // Récupérer l'URL Google News depuis la configuration
    $googleNewsUrl = $this->getConfiguration('googleNewsUrl');
    $rssUrl = null;

    if (!empty($googleNewsUrl)) {
        $rssUrl = self::convertGoogleNewsUrlToRss($googleNewsUrl);
        if ($rssUrl) {
            $this->setConfiguration('rssUrl', $rssUrl);
            log::add('googleNewsReader', 'info', 'URL RSS sauvegardée pour ' . $this->getHumanName() . ': ' . $rssUrl);
        } else {
            $this->setConfiguration('rssUrl', ''); // Effacer si la conversion échoue
            log::add('googleNewsReader', 'error', 'Impossible de convertir l\'URL Google News en RSS pour ' . $this->getHumanName() . ': ' . $googleNewsUrl);
        }
    } else {
        $this->setConfiguration('rssUrl', ''); // Effacer si l'URL Google News est vide
    }

    // Création/Mise à jour des commandes pour afficher les informations du flux
    // Pour l'instant, un jeu de commandes générique.
    // Le widget affichera une liste, mais ces commandes peuvent servir pour des scénarios ou autres.

    $this->createCmdInfo('title', 'Titre du dernier article', 'string');
    $this->createCmdInfo('link', 'Lien du dernier article', 'string');
    $this->createCmdInfo('description', 'Description du dernier article', 'string');
    $this->createCmdInfo('pubDate', 'Date de publication du dernier article', 'string');
    $this->createCmdInfo('lastUpdate', 'Dernière mise à jour du flux', 'string'); // Pour savoir quand le flux a été vérifié

    // Configuration du cron pour le rafraîchissement automatique via la méthode pull()
    // La fréquence est définie dans la configuration de l'équipement (champ 'autorefresh')
    // Jeedom s'occupe d'appeler la méthode pull() selon cette fréquence.
    // Pas besoin de code spécifique ici si 'autorefresh' est bien géré par le core Jeedom et la page de config.
    // On s'assure juste que la méthode pull existe.

    // Ajout de la commande action "Rafraîchir"
    $cmdRefresh = $this->getCmd(null, 'refresh');
    if (!is_object($cmdRefresh)) {
        $cmdRefresh = new cmd();
        $cmdRefresh->setEqLogic_id($this->getId());
        $cmdRefresh->setLogicalId('refresh');
        $cmdRefresh->setType('action');
        $cmdRefresh->setSubType('other');
        $cmdRefresh->setName('Rafraîchir le flux');
        $cmdRefresh->setIsVisible(1); // Visible dans l'onglet Commandes
        $cmdRefresh->setTemplate('dashboard', 'default'); // Peut être masqué du widget si on préfère le bouton JS
        $cmdRefresh->setTemplate('mobile', 'default');
    }
    $cmdRefresh->save();
  }

  public function toHtml($_version = 'dashboard') {
    $eqLogic_id = $this->getId();
    $widgetId = 'googleNewsReader_widget_' . $eqLogic_id;
    // $refreshButtonId = 'googleNewsReader_refresh_' . $eqLogic_id; // Bouton JS direct, mais on utilise la commande action

    // Récupérer les articles stockés
    $articlesJson = $this->getConfiguration('storedArticles', '[]');
    $articles = json_decode($articlesJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $articles = [];
        log::add('googleNewsReader', 'error', 'Erreur JSON lors de la récupération des articles stockés pour ' . $this->getHumanName() . ': ' . json_last_error_msg());
    }

    $nbArticlesConfigured = (int)$this->getConfiguration('nbArticles', 5);
    // S'assurer qu'on n'affiche pas plus que configuré, même si plus sont stockés
    $articles = array_slice($articles, 0, $nbArticlesConfigured);

    $html = '<div class="eqLogic-widget googleNewsReader" data-eqLogic_id="' . $eqLogic_id . '" data-widget_id="' . $widgetId . '" style="width: 100%;">';

    // Bouton de rafraîchissement manuel pour le widget (appellera la commande action 'refresh')
    // Le script JS pour cela sera dans googleNewsReader.js
    $cmdRefresh = $this->getCmd(null,'refresh');
    if (is_object($cmdRefresh)) {
        $html .= '<div style="text-align: right; margin-bottom: 5px;">';
        // data-cmd_id pour le JS, ou data-action="execute" si on utilise le core JS pour les commandes.
        // Pour l'instant, on met un ID/classe pour le cibler en JS.
        $html .= '<a class="btn btn-xs btn-success googleNewsReaderWidgetRefresh" data-cmd_id="' . $cmdRefresh->getId() . '"><i class="fas fa-sync"></i> Rafraîchir</a>';
        $html .= '</div>';
    }


    if (empty($articles)) {
        $html .= '<div class="text-center" style="padding-top:10px;padding-bottom:10px;">';
        $html .= '<span>{{Aucun article à afficher. Vérifiez la configuration ou cliquez sur Rafraîchir.}}</span><br/>';
        $rssUrl = $this->getConfiguration('rssUrl');
        if(empty($rssUrl)){
            $html .= '<span>{{URL RSS non configurée. Veuillez vérifier la configuration de l'équipement.}}</span>';
        } else {
            $lastUpdateCmd = $this->getCmd(null, 'lastUpdate');
            if (is_object($lastUpdateCmd)) {
                 $html .= '<span style="font-size: 0.8em;">{{Dernière tentative de mise à jour}}: ' . $lastUpdateCmd->execCmd() . '</span>';
            }
        }
        $html .= '</div>';
    } else {
        $html .= '<ul style="list-style: none; padding-left: 0;">';
        foreach ($articles as $article) {
            $html .= '<li style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">';
            $html .= '  <strong style="font-size: 1.1em;"><a href="' . htmlspecialchars($article['link']) . '" target="_blank">' . htmlspecialchars($article['title']) . '</a></strong><br/>';
            $html .= '  <em style="font-size: 0.8em; color: #777;">' . htmlspecialchars($article['pubDate']) . '</em><br/>';
            // Limiter la description à N caractères pour le widget
            $descriptionDisplay = htmlspecialchars($article['description']);
            if (mb_strlen($descriptionDisplay) > 150) {
                $descriptionDisplay = mb_substr($descriptionDisplay, 0, 147) . '...';
            }
            $html .= '  <p style="font-size: 0.9em; margin-top: 5px;">' . $descriptionDisplay . '</p>';
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</div>'; // close eqLogic-widget

    return $html;
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*     * **********************Getteur Setteur*************************** */
}

class googleNewsReaderCmd extends cmd {
  public function execute($_options = array()) {
      $eqLogic = $this->getEqLogic();
      $logicalId = $this->getLogicalId();

      log::add('googleNewsReader', 'debug', 'Commande action exécutée: ' . $logicalId . ' sur ' . $eqLogic->getHumanName());

      if ($logicalId == 'refresh') {
          try {
              $eqLogic->pull(); // Appelle la méthode pull de l'équipement parent
              message::add("googleNewsReader", "{{Flux RSS rafraîchi pour}} " . $eqLogic->getHumanName());
          } catch (Exception $e) {
              log::add('googleNewsReader', 'error', 'Erreur lors du rafraîchissement manuel pour ' . $eqLogic->getHumanName() . ': ' . $e->getMessage());
              message::add("googleNewsReader", "{{Erreur lors du rafraîchissement pour}} " . $eqLogic->getHumanName() . ": " . $e->getMessage());
              return false; // Indique un échec
          }
          return true; // Indique que la commande a été exécutée
      }
  }
}
