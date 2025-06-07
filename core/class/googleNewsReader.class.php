<?php

require_once __DIR__ . '/../../../../core/class/DB.class.php';
require_once __DIR__ . '/../../../../core/class/cache.class.php';
require_once __DIR__ . '/../../../../core/class/eqLogic.class.php';
require_once __DIR__ . '/../../../../core/class/eqReal.class.php';
require_once __DIR__ . '/../../../../core/class/cmd.class.php';
require_once __DIR__ . '/../../../../core/class/config.class.php';
require_once __DIR__ . '/../../../../core/class/cron.class.php';
require_once __DIR__ . '/../../../../core/class/ajax.class.php';
require_once __DIR__ . '/../../../../core/class/log.class.php';

class googleNewsReader extends eqLogic {

    /**
     * Constructor
     * @param null $scenario
     * @param string $plugin
     */
    public function __construct($scenario = null, $plugin = 'googleNewsReader') {
        parent::__construct($scenario, $plugin);
        $this->setCache('eqLogic', array());
    }

    /**
     * Dependency information
     * @return array
     */
    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'googleNewsReader_dependancy';
        $return['state'] = 'ok';

        // Check for php-xml
        if (!extension_loaded('xml')) {
            $return['state'] = 'nok';
            log::add('googleNewsReader', 'error', 'PHP extension php-xml is not loaded.');
        }
        // Check for php-curl
        if (!extension_loaded('curl')) {
            $return['state'] = 'nok';
            log::add('googleNewsReader', 'error', 'PHP extension php-curl is not loaded.');
        }
        return $return;
    }

    /**
     * Dependency installation
     * Apt dependencies are handled by info.json
     * @return bool
     */
    public static function dependancy_install() {
        log::add('googleNewsReader', 'info', 'Starting dependency installation check (apt handled by info.json).');
        // You can add checks here if needed, for now, assume info.json handles it.
        $dep_info = self::dependancy_info();
        if ($dep_info['state'] == 'ok') {
            log::add('googleNewsReader', 'info', 'Dependencies are correctly installed.');
        } else {
            log::add('googleNewsReader', 'error', 'One or more dependencies are missing. Please check Jeedom logs and system configuration.');
        }
        return true; // Returning true as apt is managed by Jeedom core based on info.json
    }

    /**
     * Cron job for hourly RSS feed retrieval
     * @param null $_eqLogic_id
     */
    public static function cronHourly($_eqLogic_id = null) {
        log::add('googleNewsReader', 'info', 'Executing cronHourly');
        $eqLogics = ($_eqLogic_id) ? array(eqLogic::byId($_eqLogic_id)) : eqLogic::byType('googleNewsReader', true);

        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic && $eqLogic->getIsEnable()) {
                log::add('googleNewsReader', 'info', 'Processing eqLogic: ' . $eqLogic->getHumanName());
                $googleNewsUrl = $eqLogic->getConfiguration('googleNewsUrl');
                if (empty($googleNewsUrl)) {
                    log::add('googleNewsReader', 'warning', 'Google News URL not configured for eqLogic: ' . $eqLogic->getHumanName());
                    continue;
                }

                $rssUrl = $eqLogic->convertGoogleNewsUrlToRss($googleNewsUrl);
                if (empty($rssUrl)) {
                    log::add('googleNewsReader', 'error', 'Failed to convert Google News URL to RSS for eqLogic: ' . $eqLogic->getHumanName());
                    continue;
                }

                $articles = $eqLogic->fetchAndParseRss($rssUrl);
                if (empty($articles)) {
                    log::add('googleNewsReader', 'warning', 'No articles fetched or parsed for eqLogic: ' . $eqLogic->getHumanName() . ' from URL: ' . $rssUrl);
                    // Optionally clear old articles if none are fetched
                    // $eqLogic->updateCommandsWithArticles(array());
                } else {
                    log::add('googleNewsReader', 'info', count($articles) . ' articles fetched for eqLogic: ' . $eqLogic->getHumanName());
                }
                $eqLogic->updateCommandsWithArticles($articles);
            }
        }
        log::add('googleNewsReader', 'info', 'cronHourly execution finished.');
    }

    /**
     * Called after saving the equipment
     */
    public function postSave() {
        log::add('googleNewsReader', 'info', 'Executing postSave for eqLogic: ' . $this->getHumanName());
        $nbArticles = intval($this->getConfiguration('nb_articles_to_display', 5));
        if ($nbArticles <= 0) {
            $nbArticles = 5; // Default to 5 if configuration is invalid
            log::add('googleNewsReader', 'warning', 'Invalid nb_articles_to_display, defaulting to 5 for ' . $this->getHumanName());
        }

        $existingCmds = $this->getCmd();
        $cmdLogicalIds = [];
        foreach ($existingCmds as $cmd) {
            $cmdLogicalIds[$cmd->getLogicalId()] = $cmd;
        }

        for ($i = 1; $i <= $nbArticles; $i++) {
            $logicalIdTitle = 'Titre_Article_' . $i;
            $logicalIdLink = 'Lien_Article_' . $i;
            $logicalIdDesc = 'Description_Article_' . $i;
            $logicalIdDate = 'Date_Article_' . $i;

            // Title Command
            if (!isset($cmdLogicalIds[$logicalIdTitle])) {
                $cmd = $this->createCommand($logicalIdTitle, 'info', 'string', $i, 'Titre Article ' . $i);
            } else {
                $cmd = $cmdLogicalIds[$logicalIdTitle];
                $cmd->setName('Titre Article ' . $i);
                $cmd->setOrder($i);
                $cmd->save();
            }
            unset($cmdLogicalIds[$logicalIdTitle]);


            // Link Command
            if (!isset($cmdLogicalIds[$logicalIdLink])) {
                $cmd = $this->createCommand($logicalIdLink, 'info', 'string', $i + $nbArticles, 'Lien Article ' . $i);
            } else {
                $cmd = $cmdLogicalIds[$logicalIdLink];
                $cmd->setName('Lien Article ' . $i);
                $cmd->setOrder($i + $nbArticles);
                $cmd->save();
            }
            unset($cmdLogicalIds[$logicalIdLink]);

            // Description Command
            if (!isset($cmdLogicalIds[$logicalIdDesc])) {
                $cmd = $this->createCommand($logicalIdDesc, 'info', 'string', $i + (2 * $nbArticles), 'Description Article ' . $i);
            } else {
                $cmd = $cmdLogicalIds[$logicalIdDesc];
                $cmd->setName('Description Article ' . $i);
                $cmd->setOrder($i + (2 * $nbArticles));
                $cmd->save();
            }
            unset($cmdLogicalIds[$logicalIdDesc]);

            // Date Command
            if (!isset($cmdLogicalIds[$logicalIdDate])) {
                $cmd = $this->createCommand($logicalIdDate, 'info', 'string', $i + (3 * $nbArticles), 'Date Article ' . $i);
            } else {
                $cmd = $cmdLogicalIds[$logicalIdDate];
                $cmd->setName('Date Article ' . $i);
                $cmd->setOrder($i + (3 * $nbArticles));
                $cmd->save();
            }
            unset($cmdLogicalIds[$logicalIdDate]);
        }

        // Remove surplus commands
        foreach ($cmdLogicalIds as $logicalId => $cmdToRemove) {
            if (strpos($logicalId, 'Titre_Article_') === 0 ||
                strpos($logicalId, 'Lien_Article_') === 0 ||
                strpos($logicalId, 'Description_Article_') === 0 ||
                strpos($logicalId, 'Date_Article_') === 0) {
                log::add('googleNewsReader', 'info', 'Removing surplus command: ' . $logicalId . ' for ' . $this->getHumanName());
                $cmdToRemove->remove();
            }
        }

        // Add/Update cron job
        $cron = cron::byClassAndFunction('googleNewsReader', 'cronHourly', array('eqLogic_id' => $this->getId()));
        if (!is_object($cron)) {
            $cron = new cron();
            $cron->setClass('googleNewsReader');
            $cron->setFunction('cronHourly');
            $cron->setOption(array('eqLogic_id' => $this->getId()));
            $cron->setEnable(1);
            $cron->setDeamon(0);
            $cron->setSchedule('* * * * *'); // Default, will be managed by Jeedom's cronHourly system
        }
        // Ensure the schedule is appropriate for cronHourly (Jeedom handles the actual hourly trigger)
        // For specific equipment, we might want a different schedule if not using global cronHourly
        // For now, this cron entry primarily links the eqLogic to the global cronHourly call.
        $cron->save();

        log::add('googleNewsReader', 'info', 'postSave executed for ' . $this->getHumanName());
        // Optionally trigger a refresh
        // $this->refreshData();
    }

    /**
     * Helper to create commands
     */
    private function createCommand($logicalId, $type, $subType, $order, $name) {
        $cmd = new cmd();
        $cmd->setEqLogic_id($this->getId());
        $cmd->setLogicalId($logicalId);
        $cmd->setType($type);
        $cmd->setSubType($subType);
        $cmd->setOrder($order);
        $cmd->setName($name);
        $cmd->setIsVisible(1);
        $cmd->save();
        return $cmd;
    }


    /**
     * Called before removing the equipment
     */
    public function preRemove() {
        log::add('googleNewsReader', 'info', 'Executing preRemove for eqLogic: ' . $this->getHumanName());
        $cron = cron::byClassAndFunction('googleNewsReader', 'cronHourly', array('eqLogic_id' => $this->getId()));
        if (is_object($cron)) {
            $cron->remove();
            log::add('googleNewsReader', 'info', 'Cron job removed for eqLogic: ' . $this->getHumanName());
        }
    }

    /**
     * Converts a Google News URL to its corresponding RSS feed URL.
     * Example Google News URL: https://news.google.com/search?q=jeedom&hl=fr&gl=FR&ceid=FR:fr
     * Corresponding RSS URL: https://news.google.com/rss/search?q=jeedom&hl=fr&gl=FR&ceid=FR:fr
     * @param string $googleNewsUrl The Google News URL (e.g., search, topic)
     * @return string|null The RSS feed URL or null on failure
     */
    private function convertGoogleNewsUrlToRss(string $googleNewsUrl): ?string {
        log::add('googleNewsReader', 'debug', 'Attempting to convert URL: ' . $googleNewsUrl);
        if (empty($googleNewsUrl) || !filter_var($googleNewsUrl, FILTER_VALIDATE_URL)) {
            log::add('googleNewsReader', 'error', 'Invalid Google News URL provided: ' . $googleNewsUrl);
            return null;
        }

        $parsedUrl = parse_url($googleNewsUrl);
        if (!isset($parsedUrl['host']) || $parsedUrl['host'] !== 'news.google.com') {
            log::add('googleNewsReader', 'error', 'URL is not a valid Google News URL: ' . $googleNewsUrl);
            return null;
        }

        // Default language and country
        $defaultLang = $this->getConfiguration('rss_lang', 'fr');
        $defaultCountry = $this->getConfiguration('rss_country', 'FR');

        $hl = $defaultLang;
        $gl = $defaultCountry;
        $ceid = strtoupper($defaultCountry) . ':' . strtolower($defaultLang);

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            if (isset($queryParams['hl'])) $hl = $queryParams['hl'];
            if (isset($queryParams['gl'])) $gl = $queryParams['gl'];
            if (isset($queryParams['ceid'])) $ceid = $queryParams['ceid'];
            else $ceid = strtoupper($gl) . ':' . strtolower($hl); // Construct ceid if not present
        }

        $rssPath = '';
        // Determine RSS path based on Google News URL structure
        // Search: /search?q=... -> /rss/search?q=...
        // Topic: /topics/... -> /rss/topics/...
        // Section (e.g. World): /headlines/section/topic/WORLD -> /rss/headlines/section/topic/WORLD
        if (isset($parsedUrl['path'])) {
            if (strpos($parsedUrl['path'], '/search') === 0) {
                $rssPath = '/rss' . $parsedUrl['path'];
            } elseif (strpos($parsedUrl['path'], '/topics/') === 0) {
                 $rssPath = '/rss' . $parsedUrl['path'];
            } elseif (strpos($parsedUrl['path'], '/headlines/section/topic/') === 0) {
                 $rssPath = '/rss' . $parsedUrl['path'];
            } else {
                // Fallback for base URL or unrecognized paths - try to make it a general search
                // This might need adjustment based on observed Google News URL patterns
                $rssPath = '/rss';
                if(isset($parsedUrl['path']) && $parsedUrl['path'] != '/'){
                     $rssPath = '/rss' . $parsedUrl['path']; // If path is like /foryou, etc.
                }
            }
        } else {
             $rssPath = '/rss'; // Default if no path
        }


        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
        // Ensure hl, gl, ceid are present in the final query
        parse_str($query, $finalQueryParams);
        $finalQueryParams['hl'] = $hl;
        $finalQueryParams['gl'] = $gl;
        $finalQueryParams['ceid'] = $ceid;

        $finalQueryString = http_build_query($finalQueryParams);

        $rssUrl = 'https://news.google.com' . $rssPath . '?' . $finalQueryString;

        log::add('googleNewsReader', 'info', 'Converted ' . $googleNewsUrl . ' to RSS URL: ' . $rssUrl);
        return $rssUrl;
    }

    /**
     * Fetches and parses an RSS feed.
     * @param string $rssUrl The URL of the RSS feed.
     * @return array An array of articles, sorted by date, or an empty array on failure.
     */
    private function fetchAndParseRss(string $rssUrl): array {
        log::add('googleNewsReader', 'debug', 'Fetching RSS feed: ' . $rssUrl . ' for eqLogic: ' . $this->getHumanName());
        $articles = [];
        $nbArticlesToDisplay = intval($this->getConfiguration('nb_articles_to_display', 5));

        try {
            $ajax = new ajax();
            $ajax->seturl($rssUrl);
            $ajax->settimeout(10); // 10 seconds timeout
            // Google sometimes blocks default user agents or requires a more common one
            $ajax->setOption(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $xmlContent = $ajax->exec();

            if ($xmlContent === false || $ajax->getHttpStatusCode() != 200) {
                log::add('googleNewsReader', 'error', 'Failed to fetch RSS feed. HTTP Status: ' . $ajax->getHttpStatusCode() . ' URL: ' . $rssUrl);
                return [];
            }

            // Suppress errors for invalid XML, then check
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                $xml_errors = libxml_get_errors();
                $error_message = "Failed to parse XML from $rssUrl. Errors: ";
                foreach ($xml_errors as $error) {
                    $error_message .= trim($error->message) . " (Line: {$error->line}, Column: {$error->column}); ";
                }
                libxml_clear_errors();
                log::add('googleNewsReader', 'error', $error_message);
                return [];
            }

            if (!isset($xml->channel->item)) {
                 log::add('googleNewsReader', 'warning', 'No <item> found in RSS feed: ' . $rssUrl);
                 return [];
            }

            foreach ($xml->channel->item as $item) {
                $title = (string) $item->title;
                $link = (string) $item->link;
                // Description can sometimes be HTML, strip tags for plain text
                $description = strip_tags((string) $item->description);
                $pubDateStr = (string) $item->pubDate;

                // Convert pubDate to ISO 8601 format or timestamp
                // $timestamp = strtotime($pubDateStr);
                // $formattedDate = date('Y-m-d H:i:s', $timestamp);
                $dateTime = new DateTime($pubDateStr);
                $formattedDate = $dateTime->format('Y-m-d H:i:s'); // ISO 8601 like
                $timestamp = $dateTime->getTimestamp();


                $articles[] = [
                    'title' => $title,
                    'link' => $link,
                    'description' => $description,
                    'date' => $formattedDate,
                    'timestamp' => $timestamp,
                ];
            }

            // Sort articles by date descending
            usort($articles, function ($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            // Limit number of articles
            if ($nbArticlesToDisplay > 0) {
                $articles = array_slice($articles, 0, $nbArticlesToDisplay);
            }

        } catch (Exception $e) {
            log::add('googleNewsReader', 'error', 'Error fetching or parsing RSS feed: ' . $e->getMessage() . ' for URL: ' . $rssUrl);
            return [];
        }

        log::add('googleNewsReader', 'debug', 'Successfully fetched and parsed ' . count($articles) . ' articles from ' . $rssUrl);
        return $articles;
    }

    /**
     * Updates the info commands with the fetched articles.
     * @param array $articles Array of articles.
     */
    private function updateCommandsWithArticles(array $articles) {
        log::add('googleNewsReader', 'info', 'Updating commands with ' . count($articles) . ' articles for eqLogic: ' . $this->getHumanName());
        $nbArticlesConfigured = intval($this->getConfiguration('nb_articles_to_display', 5));

        for ($i = 1; $i <= $nbArticlesConfigured; $i++) {
            $article = $articles[$i - 1] ?? null; // Get article or null if not enough articles

            $this->checkAndUpdateCmd('Titre_Article_' . $i, $article ? $article['title'] : '');
            $this->checkAndUpdateCmd('Lien_Article_' . $i, $article ? $article['link'] : '');
            $this->checkAndUpdateCmd('Description_Article_' . $i, $article ? $article['description'] : '');
            $this->checkAndUpdateCmd('Date_Article_' . $i, $article ? $article['date'] : '');
        }
        // Refresh widget display
        $this->refreshWidget();
    }

    /**
     * Refresh data for the current equipment.
     * Typically called via AJAX by a button on the equipment.
     */
    public function refreshData() {
        log::add('googleNewsReader', 'info', 'Manual refresh triggered for eqLogic: ' . $this->getHumanName());
        // We can directly call the logic from cronHourly for this specific equipment
        self::cronHourly($this->getId());
        log::add('googleNewsReader', 'info', 'Manual refresh finished for eqLogic: ' . $this->getHumanName());
        // The cronHourly method should handle updating commands and the widget.
    }

    /**
     * Returns the list of commands, usually for the plugin page
     * @return array
     */
    public function getCmdArray() {
        $cmds = array();
        // This function can be customized if needed for display purposes.
        // By default, it might not be strictly necessary if postSave handles command creation.
        return $cmds;
    }

    /**
     * (Optional) preUpdate method
     */
    public function preUpdate() {
        // Logic before updating the equipment
    }

    /**
     * (Optional) postUpdate method
     */
    public function postUpdate() {
        // Logic after updating the equipment, similar to postSave if settings affecting commands change.
        // Consider calling parts of postSave logic if configuration related to commands is updated.
        // For example, if nb_articles_to_display changes, commands need to be adjusted.
        // A common pattern is to have a setupCommands() method called by both postSave and postUpdate.
        log::add('googleNewsReader', 'debug', 'Executing postUpdate for eqLogic: ' . $this->getHumanName() . '. Calling postSave logic to ensure commands are up-to-date.');
        $this->postSave(); // Re-run postSave to ensure commands match configuration
    }

    /**
     * (Optional) preRemoveCmd method
     * @param cmd $_cmd
     */
    public function preRemoveCmd(cmd $_cmd) {
        // Logic before removing a command
    }

    /**
     * (Optional) postRemoveCmd method
     * @param cmd $_cmd
     */
    public function postRemoveCmd(cmd $_cmd) {
        // Logic after removing a command
    }

    /**
     * Generates the HTML for the widget.
     * @return string The HTML content of the widget.
     */
    public function toHtml($_version = 'dashboard') {
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }

        $version = jeedom::versionAlias($_version);
        $cmdColor = ($this->getIsEnable()) ? $this->getCmdColor() : '#767676'; // Color for icon/text if needed

        // Start widget container
        $html = '<div class="eqLogic eqLogic-widget googleNewsReader-widget" style="width: ' . $this->getDisplay('width', '300px') . ';height: ' . $this->getDisplay('height', '300px') . ';" data-eqLogic_id="' . $this->getId() . '" data-eqLogic_uid="' . $this->getUid() . '" data-version="' . $version . '">';

        // Header / Title
        if ($this->getDisplay('showObjectName', 1) == 1) {
            $html .= '<div class="widget-name" style="text-align:center;font-weight:bold;font-size:1.1em;margin-bottom:5px;">' . jeeObject::pathName($this->getObject_id()) . '</div>';
        }
        $html .= '<div class="widget-name" style="text-align:center;font-weight:bold;font-size:1.1em;margin-bottom:10px;">' . $this->getHumanName(true, true) . '</div>';

        // Refresh button
        $html .= '<div style="text-align:center; margin-bottom:10px;">';
        $html .= '<a class="btn btn-xs btn-default tooltips cmdAction googleNewsReader-refresh-widget" data-action="refresh" data-eqLogic_id="' . $this->getId() . '" title="{{Rafraîchir le flux}}"><i class="fas fa-sync"></i></a>';
        $html .= '</div>';

        // Articles container
        $html .= '<div class="googleNewsReader-articles-container" style="overflow-y: auto; height: calc(100% - 60px);">'; // Adjust height based on header/button

        $nbArticles = intval($this->getConfiguration('nb_articles_to_display', 5));
        $articlesFound = false;

        for ($i = 1; $i <= $nbArticles; $i++) {
            $cmdTitle = $this->getCmd(null, 'Titre_Article_' . $i);
            $cmdLink = $this->getCmd(null, 'Lien_Article_' . $i);
            $cmdDescription = $this->getCmd(null, 'Description_Article_' . $i);
            $cmdDate = $this->getCmd(null, 'Date_Article_' . $i);

            if (is_object($cmdTitle) && is_object($cmdLink) && is_object($cmdDescription) && is_object($cmdDate)) {
                $title = $cmdTitle->execCmd();
                $link = $cmdLink->execCmd();
                $description = $cmdDescription->execCmd();
                $date = $cmdDate->execCmd();

                if (!empty($title) && !empty($link)) { // Only display if title and link are present
                    $articlesFound = true;
                    $html .= '<div class="article googleNewsReader-article" style="margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #eee;">';
                    $html .= '  <h4 style="font-size: 1em; margin-top:0; margin-bottom:3px;"><a href="' . htmlspecialchars($link) . '" target="_blank" class="article-title" style="color:'.$cmdColor.';">' . htmlspecialchars($title) . '</a></h4>';
                    if (!empty($description) && $this->getDisplay('showDescription', 1) == 1) {
                         $html .= '  <p class="description article-description" style="font-size: 0.85em; margin-bottom:3px;">' . nl2br(htmlspecialchars($description)) . '</p>';
                    }
                    if (!empty($date) && $this->getDisplay('showDate', 1) == 1) {
                        $html .= '  <small class="date article-date" style="font-size: 0.75em; color: #777;">Publié le : ' . htmlspecialchars($date) . '</small>';
                    }
                    $html .= '</div>';
                }
            }
        }

        if (!$articlesFound) {
            $html .= '<div class="no-articles-message" style="text-align:center;margin-top:20px;">{{Aucun article à afficher. Vérifiez la configuration ou rafraîchissez.}}</div>';
        }

        $html .= '</div>'; // End articles-container
        $html .= '</div>'; // End widget container

        // Include the JavaScript part for refresh button interaction if not handled globally
        // This was also put in desktop/php/googleNewsReader.php, ensure it's loaded once.
        // For widget context, it's better to have it self-contained or ensure global handler.
        // The JS in desktop/php/googleNewsReader.php is more for the config page.
        // For widget, if not using data-action="refresh" that Jeedom handles, custom JS is needed.
        // The current data-action="refresh" with class cmdAction might not work as expected
        // if it's not a standard Jeedom command.
        // Let's ensure the refresh button has a unique class for JS targeting if needed.
        // The JS in desktop/php/googleNewsReader.php for googleNewsReader_widget_toHtml
        // attempts to handle this.
        // The class `googleNewsReader-refresh-widget` is used by that JS.

        return $this->postToHtml($_version, $html);
    }
}
?>
