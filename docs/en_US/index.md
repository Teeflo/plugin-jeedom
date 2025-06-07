# Google News Plugin

This plugin allows you to integrate personalized Google News RSS feeds into your Jeedom dashboard. You can track specific topics, keywords, or news searches and display the latest articles in a widget.

## Features

-   Convert standard Google News URLs (topics/searches) into their RSS equivalents automatically.
-   Fetch and parse RSS feeds to extract articles (title, description, publication date, link).
-   Store articles in the Jeedom database.
-   Display articles in a configurable dashboard widget.
-   Manual refresh option.
-   Automatic refresh via cron.
-   Configurable number of articles to display and store.
-   Choice of sort order for articles (newest or oldest first).
-   Error handling for invalid URLs or unavailable feeds.

## 1. Installation

1.  **Dependencies**: The plugin requires the PHP extensions `cURL` and `XML (SimpleXML)`. These are usually enabled by default in most PHP installations. If not, you may need to install them (e.g., `sudo apt-get install php-curl php-xml` on Debian/Ubuntu and restart your web server). The plugin will check for these during installation.
2.  **Plugin Upload**:
    *   If you have the `.zip` file: Go to "Plugins" -> "Plugin Management" in Jeedom.
    *   Click on "Add Plugin" (the "+" button).
    *   Select the "Upload a file" tab, choose the plugin's zip file, and click "Upload".
    *   Alternatively, if using the Market, find the plugin and click "Install Stable".
3.  **Activation**: After installation, find "Google News" in your list of plugins (usually under "News" or "Other" category) and click "Activate".

## 2. Configuration

Once the plugin is installed and activated, you need to configure it by adding Google News feeds as "Equipment".

1.  **Go to Plugin Page**: Navigate to "Plugins" -> "Communication" -> "Google News" (the category might vary based on your `info.json`).
2.  **Add a News Feed**:
    *   Click the "+" button ("Add") to create a new news feed equipment.
    *   Give your equipment a **Name** (e.g., "Tech News", "Local Weather Search").
    *   Assign it to an **Object parent** if desired.
    *   Enable and make it visible.
3.  **Configure the Feed**:
    *   Click on the "Configuration" tab for the newly added equipment.
    *   **Google News URL**: This is the most important field.
        *   Go to [Google News](https://news.google.com/).
        *   Search for a topic (e.g., "renewable energy") or find a specific section (e.g., a technology topic).
        *   Copy the URL from your browser's address bar.
            *   Example Search URL: `https://news.google.com/search?q=jeedom&hl=en-US&gl=US&ceid=US%3Aen`
            *   Example Topic URL: `https://news.google.com/topics/CAAqJggKIiBDQkFTRWdvSUwyMHZNRGx1YlY4U0FtVnVHZ0pWVXlnQVAB?hl=en-US&gl=US&ceid=US%3Aen`
        *   Paste this URL into the "Google News URL" field. The plugin will convert it to an RSS feed URL automatically.
    *   **Number of articles to display**: Set how many articles appear in the widget (e.g., 5, 10). Default is 10.
    *   **Maximum number of articles to keep in database**: To save space, the plugin will prune older articles. Set a limit (e.g., 50, 100). Default is 50.
    *   **Sort Order**: Choose "Most recent first" (DESC) or "Oldest first" (ASC). Default is DESC.
    *   **Refresh rate**: Define a CRON expression for automatic updates (e.g., `*/30 * * * *` for every 30 minutes). Leave empty to use the global plugin cron (typically every 15 or 30 minutes, depending on plugin settings).
    *   Click "Save".
4.  **Manual Refresh**: After saving, you can click the "Refresh articles manually" button on the configuration page to fetch articles immediately. Check the logs if needed.

## 3. Usage (Widget)

Once an equipment is configured and has fetched articles:

1.  **Add to Dashboard**: Go to your Dashboard, enter edit mode, and add the Google News equipment as a widget.
2.  **View Articles**: The widget will display the articles according to your configuration. Each article typically shows:
    *   Title (clickable link to the original article)
    *   A snippet of the description
    *   Publication date
3.  **Widget Refresh**:
    *   The widget will update automatically based on the cron schedule.
    *   Some widgets might offer a manual refresh icon directly on them (if implemented in the template).

## 4. Troubleshooting

-   **No articles displayed**:
    *   Check the "Google News URL" for typos or ensure it's a valid Google News search/topic URL.
    *   Try the "Refresh articles manually" button and check the `googleNews` log in Jeedom ("Analysis" -> "Logs").
    *   Ensure cURL is working and can access external URLs from your Jeedom server.
-   **Errors in log**: Look for messages related to "cURL Error", "HTTP Error", or "Failed to parse XML". These can indicate network issues, changes in Google News structure, or invalid feed URLs.

Need help? Check the [Jeedom Community Forum](https://community.jeedom.com/) and search for the plugin.
