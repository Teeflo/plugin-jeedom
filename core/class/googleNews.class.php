<?php
require_once dirname(__FILE__) . '/../../../../core/class/DB.class.php';

class googleNews extends eqLogic {
    /*     * *************************Attributs****************************** */

    private $_rssUrl = '';

    /*     * ***********************Methode static*************************** */

    public static function cron() {
        $eqLogics = self::byType('googleNews', true);
        foreach ($eqLogics as $eqLogic) {
            $eqLogic->refresh();
        }
    }

    /*     * *********************Methode d'instance************************* */

    /**
     * Converts a Google News URL (search, topic) to its RSS equivalent.
     * Example Search: https://news.google.com/search?q=jeedom&hl=en-US&gl=US&ceid=US%3Aen
     * RSS Search:     https://news.google.com/rss/search?q=jeedom&hl=en-US&gl=US&ceid=US%3Aen
     * Example Topic:  https://news.google.com/topics/CAAqJggKIiBDQkFTRWdvSUwyMHZNRGx1YlY4U0FtVnVHZ0pWVXlnQVAB?hl=en-US&gl=US&ceid=US%3Aen
     * RSS Topic:      https://news.google.com/rss/topics/CAAqJggKIiBDQkFTRWdvSUwyMHZNRGx1YlY4U0FtVnVHZ0pWVXlnQVAB?hl=en-US&gl=US&ceid=US%3Aen
     *
     * @param string $url The Google News URL.
     * @return string The RSS feed URL.
     * @throws Exception If the URL is not a valid Google News URL.
     */
    public function convertUrlToRss(string $url): string {
        if (empty($url)) {
            throw new Exception(__('Google News URL cannot be empty', __FILE__));
        }

        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['host']) || $parsedUrl['host'] !== 'news.google.com') {
            throw new Exception(__('Invalid Google News URL: Host must be news.google.com', __FILE__));
        }

        if (isset($parsedUrl['path'])) {
            if (strpos($parsedUrl['path'], '/search') === 0) {
                $rssUrl = 'https://news.google.com/rss/search';
                if (isset($parsedUrl['query'])) {
                    $rssUrl .= '?' . $parsedUrl['query'];
                }
                return $rssUrl;
            } elseif (strpos($parsedUrl['path'], '/topics/') === 0) {
                $rssUrl = 'https://news.google.com/rss' . $parsedUrl['path'];
                if (isset($parsedUrl['query'])) {
                    $rssUrl .= '?' . $parsedUrl['query'];
                }
                return $rssUrl;
            }
        }
        throw new Exception(__('Invalid Google News URL: Path must be /search or /topics/...', __FILE__));
    }

    public function getRssUrl() {
        if ($this->_rssUrl == '') {
            $configUrl = $this->getConfiguration('googleNewsUrl');
            if ($configUrl != '') {
                try {
                    $this->_rssUrl = $this->convertUrlToRss($configUrl);
                } catch (Exception $e) {
                    log::add('googleNews', 'error', $e->getMessage());
                    $this->_rssUrl = ''; // Ensure it's empty on error
                }
            }
        }
        return $this->_rssUrl;
    }

    public function preUpdate() {
        // Logic before update
    }

    public function postSave() {
        // Logic after save - perhaps fetch initial RSS feed
        $this->refresh();
    }

    public function preRemove() {
        // Logic before removing the equipment
        // For example, remove associated data if any
        $this->removeArticles();
    }

    public function refresh() {
        log::add('googleNews', 'info', __('Refreshing Google News feed for: ', __FILE__) . $this->getHumanName());
        $rssUrl = $this->getRssUrl();
        if (empty($rssUrl)) {
            log::add('googleNews', 'warning', __('RSS URL is not configured or invalid for: ', __FILE__) . $this->getHumanName());
            return;
        }

        $articles = $this->fetchAndParseRss($rssUrl);
        if ($articles === false) { // fetchAndParseRss will return false on error
            log::add('googleNews', 'error', __('Failed to fetch or parse RSS feed for: ', __FILE__) . $this->getHumanName());
            return;
        }

        $this->saveArticles($articles);
        log::add('googleNews', 'info', __('Successfully refreshed and saved articles for: ', __FILE__) . $this->getHumanName());
    }

    /**
     * Fetches and parses the RSS feed.
     *
     * @param string $rssUrl The RSS feed URL.
     * @return array|false An array of articles or false on error.
     */
    public function fetchAndParseRss(string $rssUrl): array {
        if (!function_exists('curl_init')) {
            log::add('googleNews', 'error', __('cURL PHP extension is not installed.', __FILE__));
            throw new Exception(__('cURL PHP extension is not installed.', __FILE__));
        }
        if (!function_exists('simplexml_load_string')) {
            log::add('googleNews', 'error', __('SimpleXML PHP extension is not installed.', __FILE__));
            throw new Exception(__('SimpleXML PHP extension is not installed.', __FILE__));
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rssUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 seconds timeout
        curl_setopt($ch, CURLOPT_USERAGENT, 'Jeedom GoogleNews Plugin'); // Set a user agent
        // Follow redirects if any
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // Disable SSL verification for simplicity in some environments, not recommended for production without understanding implications
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


        $xmlString = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            log::add('googleNews', 'error', __('cURL Error fetching RSS feed: ', __FILE__) . $curlError . ' for URL: ' . $rssUrl);
            return false;
        }

        if ($httpCode != 200) {
            log::add('googleNews', 'error', __('HTTP Error fetching RSS feed: Status ', __FILE__) . $httpCode . ' for URL: ' . $rssUrl);
            return false;
        }

        if (empty($xmlString)) {
            log::add('googleNews', 'error', __('Empty response from RSS feed: ', __FILE__) . $rssUrl);
            return false;
        }

        libxml_use_internal_errors(true); // Enable user error handling for XML
        $xml = simplexml_load_string($xmlString);

        if ($xml === false) {
            $xmlErrors = [];
            foreach(libxml_get_errors() as $error) {
                $xmlErrors[] = $error->message;
            }
            libxml_clear_errors();
            log::add('googleNews', 'error', __('Failed to parse XML from RSS feed: ', __FILE__) . $rssUrl . ' Errors: ' . implode("; ", $xmlErrors) );
            return false;
        }

        $articles = [];
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $articles[] = [
                    'title' => (string)$item->title,
                    'description' => (string)$item->description,
                    'pubDate' => (string)$item->pubDate,
                    'link' => (string)$item->link,
                    'guid' => (string)$item->guid // Use guid as a unique identifier
                ];
            }
        } elseif (isset($xml->item)) { // Atom feeds might have items directly under root
             foreach ($xml->item as $item) {
                // Atom feeds use different tags, this is a simplified mapping
                $title = (string)$item->title;
                $content = (string)$item->content ?: (string)$item->summary; // Atom uses <content> or <summary>
                $published = (string)$item->published ?: (string)$item->updated; // Atom uses <published> or <updated>
                $link = '';
                if (isset($item->link)) {
                    foreach($item->link as $l) {
                        if (isset($l['rel']) && $l['rel'] == 'alternate') {
                            $link = (string)$l['href'];
                            break;
                        }
                        if (empty($link)) { // Fallback if no alternate link
                           $link = (string)$l['href'];
                        }
                    }
                }
                 $guid = (string)$item->id;


                $articles[] = [
                    'title' => $title,
                    'description' => $content,
                    'pubDate' => $published,
                    'link' => $link,
                    'guid' => $guid
                ];
            }
        }else {
             log::add('googleNews', 'warning', __('No <item> found in RSS feed: ', __FILE__) . $rssUrl);
        }
        return $articles;
    }

    /**
     * Saves articles to the database.
     * It ensures that articles are unique based on their GUID.
     *
     * @param array $articles Array of articles to save.
     */
    public function saveArticles(array $articles) {
        if (empty($articles)) {
            return;
        }
        $eqLogicId = $this->getId();
        if (empty($eqLogicId)) {
            log::add('googleNews', 'error', __('Equipment ID is missing, cannot save articles.', __FILE__));
            return;
        }

        $tableName = 'google_news_articles'; // Table name should be defined in install.php

        // Create table if it doesn't exist (defensive check, should be handled by install)
        $sql = "CREATE TABLE IF NOT EXISTS `${tableName}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `eqLogic_id` INT NOT NULL,
            `guid` VARCHAR(255) NOT NULL,
            `title` TEXT,
            `description` TEXT,
            `pubDate` DATETIME,
            `link` VARCHAR(2048),
            `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_guid_eqlogic` (`eqLogic_id`, `guid`(191))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"; // Ensure guid is not too long for unique key
        try {
            DB::Prepare($sql, [], DB::FETCH_TYPE_ROW);
        } catch (Exception $e) {
            // Table creation might fail if already exists with different schema, log and continue
            log::add('googleNews', 'warning', __('Could not ensure table exists (might be normal if already exists): ', __FILE__) . $e->getMessage());
        }


        foreach ($articles as $article) {
            // Check if article with this GUID already exists for this eqLogic
            $existingArticle = DB::Prepare("SELECT `id` FROM `${tableName}` WHERE `eqLogic_id` = :eqLogic_id AND `guid` = :guid",
                                            ['eqLogic_id' => $eqLogicId, 'guid' => $article['guid']],
                                            DB::FETCH_TYPE_ROW);

            if ($existingArticle) {
                // Optionally update existing article, for now, we skip if GUID exists
                // log::add('googleNews', 'debug', 'Article with GUID ' . $article['guid'] . ' already exists. Skipping.');
                continue;
            }

            $pubDateTime = null;
            if (!empty($article['pubDate'])) {
                try {
                    $dt = new DateTime($article['pubDate']);
                    $pubDateTime = $dt->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    log::add('googleNews', 'warning', __('Invalid date format for article: ', __FILE__) . $article['title'] . ' Date: ' . $article['pubDate']);
                    // Keep $pubDateTime as null or set to current time
                    // $pubDateTime = date('Y-m-d H:i:s');
                }
            }

            $sql = "INSERT INTO `${tableName}` (`eqLogic_id`, `guid`, `title`, `description`, `pubDate`, `link`)
                    VALUES (:eqLogic_id, :guid, :title, :description, :pubDate, :link)";
            $params = [
                'eqLogic_id' => $eqLogicId,
                'guid' => $article['guid'],
                'title' => $article['title'],
                'description' => $article['description'],
                'pubDate' => $pubDateTime,
                'link' => $article['link'],
            ];
            try {
                DB::Prepare($sql, $params, DB::FETCH_TYPE_ROW);
            } catch (Exception $e) {
                log::add('googleNews', 'error', __('Error saving article: ', __FILE__) . $e->getMessage() . ' Article: ' . $article['title']);
            }
        }
        // After saving, prune old articles if configured
        $this->pruneOldArticles();
    }

    /**
     * Retrieves articles from the database.
     *
     * @param int $limit Number of articles to retrieve.
     * @param string $sortOrder Sort order ('ASC' or 'DESC' for pubDate).
     * @return array Array of articles.
     */
    public function getArticles(int $limit = 10, string $sortOrder = 'DESC'): array {
        $eqLogicId = $this->getId();
        if (empty($eqLogicId)) {
            return [];
        }
        $tableName = 'google_news_articles';
        $sortOrder = strtoupper($sortOrder) == 'ASC' ? 'ASC' : 'DESC'; // Sanitize sort order
        $limit = max(1, (int)$limit); // Ensure limit is at least 1

        $sql = "SELECT `title`, `description`, `pubDate`, `link`
                FROM `${tableName}`
                WHERE `eqLogic_id` = :eqLogic_id
                ORDER BY `pubDate` ${sortOrder}, `id` ${sortOrder}
                LIMIT :limit";
        try {
            $articles = DB::Prepare($sql, ['eqLogic_id' => $eqLogicId, 'limit' => $limit], DB::FETCH_TYPE_ALL);
            if ($articles === false) return []; // DB::Prepare returns false on error
            return $articles;
        } catch (Exception $e) {
            log::add('googleNews', 'error', __('Error retrieving articles: ', __FILE__) . $e->getMessage());
            return [];
        }
    }

    /**
     * Prunes old articles based on configuration (e.g., max number of articles or max age).
     */
    public function pruneOldArticles() {
        $eqLogicId = $this->getId();
        if (empty($eqLogicId)) {
            return;
        }
        $tableName = 'google_news_articles';
        $maxArticles = $this->getConfiguration('maxArticles', 50); // Default to 50 articles
        $maxArticles = max(10, (int)$maxArticles); // Ensure a minimum

        // Get the GUIDs of the most recent N articles
        $sqlGuidsToKeep = "SELECT `guid` FROM `${tableName}`
                           WHERE `eqLogic_id` = :eqLogic_id
                           ORDER BY `pubDate` DESC, `id` DESC
                           LIMIT :limit";
        try {
            $guidsToKeepResult = DB::Prepare($sqlGuidsToKeep, ['eqLogic_id' => $eqLogicId, 'limit' => $maxArticles], DB::FETCH_TYPE_COL);
            if ($guidsToKeepResult === false || empty($guidsToKeepResult)) {
                return; // Nothing to do or error
            }

            // Ensure guids are properly quoted for the IN clause
            $placeholders = implode(',', array_fill(0, count($guidsToKeepResult), '?'));
            $params = array_merge([$eqLogicId], $guidsToKeepResult);

            $sqlDeleteOld = "DELETE FROM `${tableName}`
                             WHERE `eqLogic_id` = ?
                             AND `guid` NOT IN (${placeholders})";
            DB::Prepare($sqlDeleteOld, $params, DB::FETCH_TYPE_ROW);
        } catch (Exception $e) {
            log::add('googleNews', 'error', __('Error pruning old articles: ', __FILE__) . $e->getMessage());
        }
    }

    /**
     * Removes all articles associated with this equipment.
     */
    public function removeArticles() {
        $eqLogicId = $this->getId();
        if (empty($eqLogicId)) {
            return;
        }
        $tableName = 'google_news_articles';
        $sql = "DELETE FROM `${tableName}` WHERE `eqLogic_id` = :eqLogic_id";
        try {
            DB::Prepare($sql, ['eqLogic_id' => $eqLogicId], DB::FETCH_TYPE_ROW);
        } catch (Exception $e) {
            log::add('googleNews', 'error', __('Error removing articles for eqLogicId : ', __FILE__) . $eqLogicId . ' - ' . $e->getMessage());
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}
?>
