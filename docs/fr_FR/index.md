# Plugin Google News

Ce plugin vous permet d'intégrer des flux RSS personnalisés de Google News dans votre tableau de bord Jeedom. Vous pouvez suivre des sujets spécifiques, des mots-clés ou des recherches d'actualités et afficher les derniers articles dans un widget.

## Fonctionnalités

-   Conversion automatique des URLs Google News standard (sujets/recherches) en leurs équivalents RSS.
-   Récupération et analyse des flux RSS pour extraire les articles (titre, description, date de publication, lien).
-   Stockage des articles dans la base de données Jeedom.
-   Affichage des articles dans un widget de tableau de bord configurable.
-   Option de rafraîchissement manuel.
-   Rafraîchissement automatique via cron.
-   Nombre d'articles à afficher et à stocker configurable.
-   Choix de l'ordre de tri des articles (plus récent ou plus ancien en premier).
-   Gestion des erreurs pour les URL invalides ou les flux indisponibles.

## 1. Installation

1.  **Dépendances**: Le plugin nécessite les extensions PHP `cURL` et `XML (SimpleXML)`. Celles-ci sont généralement activées par défaut dans la plupart des installations PHP. Sinon, vous devrez peut-être les installer (par ex., `sudo apt-get install php-curl php-xml` sur Debian/Ubuntu et redémarrer votre serveur web). Le plugin vérifiera leur présence lors de l'installation.
2.  **Téléversement du Plugin**:
    *   Si vous avez le fichier `.zip` : Allez dans "Plugins" -> "Gestion des plugins" dans Jeedom.
    *   Cliquez sur "Ajouter un plugin" (le bouton "+").
    *   Sélectionnez l'onglet "Envoyer un fichier", choisissez le fichier zip du plugin, et cliquez sur "Envoyer".
    *   Alternativement, si vous utilisez le Market, trouvez le plugin et cliquez sur "Installer Stable".
3.  **Activation**: Après l'installation, trouvez "Google News" dans votre liste de plugins (généralement dans la catégorie "Actualités" ou "Autre") et cliquez sur "Activer".

## 2. Configuration

Une fois le plugin installé et activé, vous devez le configurer en ajoutant des flux Google News en tant qu'"Équipements".

1.  **Aller à la Page du Plugin**: Naviguez vers "Plugins" -> "Communication" -> "Google News" (la catégorie peut varier en fonction de votre `info.json`).
2.  **Ajouter un Flux d'Actualités**:
    *   Cliquez sur le bouton "+" ("Ajouter") pour créer un nouvel équipement de flux d'actualités.
    *   Donnez un **Nom** à votre équipement (par ex., "Actualités Tech", "Recherche Météo Locale").
    *   Assignez-le à un **Objet parent** si désiré.
    *   Activez-le et rendez-le visible.
3.  **Configurer le Flux**:
    *   Cliquez sur l'onglet "Configuration" de l'équipement nouvellement ajouté.
    *   **URL Google News**: C'est le champ le plus important.
        *   Allez sur [Google Actualités](https://news.google.com/).
        *   Recherchez un sujet (par ex., "énergie renouvelable") ou trouvez une section spécifique (par ex., un sujet technologique).
        *   Copiez l'URL depuis la barre d'adresse de votre navigateur.
            *   Exemple URL de Recherche : `https://news.google.com/search?q=jeedom&hl=fr&gl=FR&ceid=FR%3Afr`
            *   Exemple URL de Sujet : `https://news.google.com/topics/CAAqJggKIiBDQkFTRWdvSUwyMHZNRGx1YlY4U0FtVnVHZ0pWVXlnQVAB?hl=fr&gl=FR&ceid=FR%3Afr`
        *   Collez cette URL dans le champ "URL Google News". Le plugin la convertira automatiquement en URL de flux RSS.
    *   **Nombre d'articles à afficher**: Définissez combien d'articles apparaissent dans le widget (par ex., 5, 10). Défaut : 10.
    *   **Nombre maximal d'articles à conserver en base**: Pour économiser de l'espace, le plugin supprimera les anciens articles. Définissez une limite (par ex., 50, 100). Défaut : 50.
    *   **Ordre de tri**: Choisissez "Plus récent en premier" (DESC) ou "Plus ancien en premier" (ASC). Défaut : DESC.
    *   **Fréquence de rafraîchissement**: Définissez une expression CRON pour les mises à jour automatiques (par ex., `*/30 * * * *` pour toutes les 30 minutes). Laissez vide pour utiliser le cron global du plugin (généralement toutes les 15 ou 30 minutes, selon les paramètres du plugin).
    *   Cliquez sur "Sauvegarder".
4.  **Rafraîchissement Manuel**: Après avoir sauvegardé, vous pouvez cliquer sur le bouton "Rafraîchir manuellement les articles" sur la page de configuration pour récupérer immédiatement les articles. Vérifiez les logs si nécessaire.

## 3. Utilisation (Widget)

Une fois qu'un équipement est configuré et a récupéré des articles :

1.  **Ajouter au Tableau de Bord**: Allez sur votre Tableau de bord, activez le mode édition, et ajoutez l'équipement Google News comme widget.
2.  **Voir les Articles**: Le widget affichera les articles selon votre configuration. Chaque article montre typiquement :
    *   Titre (lien cliquable vers l'article original)
    *   Un extrait de la description
    *   Date de publication
3.  **Rafraîchissement du Widget**:
    *   Le widget se mettra à jour automatiquement selon le planning cron.
    *   Certains widgets peuvent offrir une icône de rafraîchissement manuel directement dessus (si implémenté dans le template).

## 4. Dépannage

-   **Aucun article affiché**:
    *   Vérifiez l'"URL Google News" pour des erreurs de frappe ou assurez-vous que c'est une URL de recherche/sujet Google News valide.
    *   Essayez le bouton "Rafraîchir manuellement les articles" et vérifiez le log `googleNews` dans Jeedom ("Analyse" -> "Logs").
    *   Assurez-vous que cURL fonctionne et peut accéder aux URL externes depuis votre serveur Jeedom.
-   **Erreurs dans le log**: Recherchez des messages liés à "cURL Error", "HTTP Error", ou "Failed to parse XML". Ceux-ci peuvent indiquer des problèmes de réseau, des changements dans la structure de Google News, ou des URL de flux invalides.

Besoin d'aide ? Consultez le [Forum Communautaire Jeedom](https://community.jeedom.com/) et recherchez le plugin.
