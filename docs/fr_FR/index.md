# Plugin Google News

## Description

Ce plugin permet d'intégrer des flux d'actualités personnalisés depuis Google News dans Jeedom. Configurez simplement une URL de recherche ou de rubrique Google News, et le plugin affichera les derniers articles directement sur votre dashboard.
Il permet de suivre des sujets spécifiques, des résultats de recherche ou des catégories d'actualités Google News en les transformant en un flux RSS lisible par Jeedom.

## Installation

L'installation se fait classiquement via le Market Jeedom.

Le plugin nécessite les extensions PHP `php-xml` et `php-curl` pour fonctionner. Celles-ci sont déclarées comme dépendances `apt` dans la configuration du plugin et sont normalement installées automatiquement par Jeedom lors de l'installation du plugin.

Si, pour une raison quelconque, ces dépendances ne s'installaient pas correctement :
1.  Vérifiez la page **Santé** de Jeedom pour des messages d'erreur concernant les dépendances.
2.  Assurez-vous que votre système peut installer des paquets `apt`.
3.  En cas de problème persistant, vous pourriez avoir besoin de les installer manuellement via SSH sur votre machine Jeedom :
    ```bash
    sudo apt-get update
    sudo apt-get install -y php-xml php-curl
    ```
4.  Après une installation manuelle, un redémarrage du service web (Apache, Nginx, ou PHP-FPM) est souvent nécessaire pour que PHP prenne en compte les nouvelles extensions.
    ```bash
    sudo systemctl restart apache2 # Pour Apache
    # ou
    sudo systemctl restart nginx # Pour Nginx
    sudo systemctl restart phpX.Y-fpm # Remplacez X.Y par votre version de PHP (ex: php7.3-fpm)
    ```

## Configuration des Équipements

Une fois le plugin installé et les dépendances satisfaites, vous pouvez ajouter des équipements de flux Google News.

1.  Allez dans le menu `Plugins` -> `Communication` -> `Google News`.
2.  Cliquez sur le bouton `Ajouter` pour créer un nouvel équipement.
3.  **Nom de l'équipement** : Donnez un nom descriptif à votre flux (par exemple, "Actualités Tech", "Infos Locales", "Jeedom Nouveautés").
4.  **Activer** : Cochez cette case pour que l'équipement soit actif et que les données soient récupérées.
5.  **Visible** : Cochez cette case pour que le widget de cet équipement soit affiché sur votre Dashboard.
6.  Configurez l'objet parent si vous le souhaitez.

### Paramètres Spécifiques de Configuration

Dans l'onglet "Équipement" (ou "Configuration" selon la version de Jeedom) de la page de configuration de votre équipement, vous trouverez les paramètres suivants :

*   **URL Google News** :
    *   **Description** : C'est l'URL de la page Google News que vous souhaitez suivre. Le plugin la convertira en une URL de flux RSS.
    *   **Comment l'obtenir** : Naviguez sur [Google News](https://news.google.com/). Effectuez une recherche, sélectionnez une rubrique (ex: Technologie, Monde) ou un sujet spécifique. Une fois sur la page désirée, copiez l'URL complète depuis la barre d'adresse de votre navigateur.
    *   **Exemple de recherche** : `https://news.google.com/search?q=jeedom&hl=fr&gl=FR&ceid=FR:fr`
    *   **Exemple de rubrique** : `https://news.google.com/topics/CAAqJggKIiBDQkFTRWdvSUwyMHZNRGRqTVhZU0JXVnVMVWRDR2dKSlRpZ0FQAQ?hl=fr&gl=FR&ceid=FR:fr` (Rubrique "Science et technologie")

*   **Nombre d'articles à afficher** :
    *   **Description** : Détermine combien d'articles seront récupérés et affichés dans le widget.
    *   **Valeurs** : Un nombre entre 1 et 20. La valeur par défaut est généralement 5.

*   **Langue du flux (hl)** :
    *   **Description** : Spécifie la langue préférée pour les articles retournés par Google News. Cela correspond au paramètre `hl` dans l'URL de Google News.
    *   **Exemple** : `fr` (Français), `en` (English).

*   **Pays du flux (gl)** :
    *   **Description** : Spécifie le pays pour lequel les actualités doivent être ciblées. Cela correspond au paramètre `gl` dans l'URL de Google News.
    *   **Exemple** : `FR` (France), `US` (United States).
    *   **Note** : La combinaison de la langue et du pays est également utilisée pour former le paramètre `ceid` (ex: `FR:fr`).

Une fois tous les paramètres configurés, cliquez sur `Sauvegarder`. Le plugin créera automatiquement les commandes d'information nécessaires (Titre, Lien, Description, Date pour chaque article). Vous pouvez les voir dans l'onglet "Commandes".

## Utilisation du Widget

Si votre équipement est activé et visible, son widget apparaîtra sur le Dashboard. Le widget affiche :

*   Le nom de l'équipement.
*   Une liste des derniers articles récupérés :
    *   **Titre de l'article** : Le titre est un lien cliquable qui ouvre l'article original dans un nouvel onglet.
    *   **Description** : Un bref résumé de l'article (si disponible dans le flux).
    *   **Date de publication** : La date et l'heure de publication de l'article.
*   Un bouton de rafraîchissement manuel (<i class='fas fa-sync'></i>) en haut du widget (ou selon la configuration de l'affichage) permet de forcer la mise à jour du flux immédiatement.

Les flux sont rafraîchis automatiquement grâce à une tâche cron horaire configurée par le plugin. Vous n'avez normalement pas besoin d'intervenir pour que les actualités se mettent à jour.

## FAQ / Dépannage

*   **Mon flux ne se met pas à jour automatiquement.**
    *   Vérifiez que l'équipement est bien activé.
    *   Vérifiez que l'URL Google News configurée est toujours valide et retourne des résultats sur le site de Google News.
    *   Consultez les logs du plugin (`googleNewsReader` et `cron_execution`) dans Jeedom (`Analyse` -> `Logs`) pour des messages d'erreur.
    *   Assurez-vous que le système de cron de Jeedom fonctionne correctement (voir la page Santé de Jeedom).

*   **L'URL Google News que j'ai copiée n'est pas valide ou ne donne rien.**
    *   Assurez-vous de copier l'URL *complète* depuis la barre d'adresse de votre navigateur une fois que vous êtes sur la page de résultats ou la rubrique souhaitée sur Google News. Évitez les URL trop courtes ou celles pointant vers la page d'accueil générique de Google News sans recherche ou rubrique spécifiée.
    *   Le plugin essaie de convertir l'URL en flux RSS. Toutes les URL Google News ne sont pas forcément convertibles, mais les recherches et les rubriques principales le sont généralement.

*   **J'ai une erreur de dépendance (`php-xml` ou `php-curl`).**
    *   Comme mentionné dans la section Installation, ces extensions sont cruciales. Si l'installation automatique a échoué :
        1.  Connectez-vous à votre Jeedom en SSH.
        2.  Exécutez : `sudo apt-get update && sudo apt-get install -y php-xml php-curl`
        3.  Redémarrez votre serveur web :
            *   Pour Apache : `sudo systemctl restart apache2`
            *   Pour Nginx : `sudo systemctl restart nginx`
            *   Et aussi PHP-FPM si vous l'utilisez : `sudo systemctl restart phpX.Y-fpm` (remplacez `X.Y` par votre version de PHP, ex: `php7.3-fpm`).
        4.  Vérifiez à nouveau la page Santé de Jeedom.

*   **Comment puis-je styliser davantage le widget ?**
    *   Le plugin utilise des classes CSS standards (ex: `.googleNewsReader-article`, `.article-title`). Vous pouvez utiliser l'outil de personnalisation CSS de Jeedom (`Réglages` -> `Système` -> `Personnalisation avancée` -> `CSS personnalisé`) pour ajouter vos propres styles.

Pour tout autre problème, n'hésitez pas à consulter le forum Jeedom ou à ouvrir un ticket si vous pensez avoir trouvé un bug.
