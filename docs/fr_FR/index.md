# Plugin Google News Reader pour Jeedom

## Description

Le plugin **Google News Reader** permet de récupérer et d'afficher des flux d'actualités personnalisés depuis Google News directement sur votre dashboard Jeedom. Vous pouvez suivre des sujets spécifiques, des recherches ou des catégories de Google News.

## Installation

1.  **Depuis le Market Jeedom :**
    *   Accédez au Market Jeedom depuis votre interface Jeedom.
    *   Recherchez le plugin "Google News Reader".
    *   Cliquez sur "Installer" et suivez les instructions.

2.  **Depuis GitHub (méthode alternative pour les développeurs) :**
    *   Téléchargez les fichiers du plugin depuis le dépôt GitHub (si disponible).
    *   Placez les fichiers dans le répertoire `plugins/googleNewsReader` de votre installation Jeedom.
    *   Depuis la page de gestion des plugins dans Jeedom, activez le plugin.

### Dépendances

Le plugin nécessite les extensions PHP suivantes :
*   `php-xml` (pour le parsing des flux RSS)
*   `php-curl` (pour la récupération des flux RSS)

Ces dépendances sont normalement installées automatiquement par Jeedom lors de l'installation du plugin si elles sont manquantes et si votre système le permet. Si vous rencontrez des problèmes, assurez-vous que ces packages sont bien installés sur votre système (`sudo apt-get install php-xml php-curl`).

## Configuration du Plugin

Après l'installation, le plugin lui-même n'a pas de configuration globale. Vous devez ajouter et configurer des équipements pour chaque flux Google News que vous souhaitez suivre.

### Configuration d'un équipement

1.  Accédez à la page du plugin Google News Reader dans Jeedom (Plugins → Organisation → Google News Reader).
2.  Cliquez sur le bouton "Ajouter" pour créer un nouvel équipement (un nouveau flux à suivre).
3.  Donnez un nom à votre équipement (ex: "Actualités Tech", "Infos Locales").
4.  Configurez l'objet parent si vous le souhaitez.
5.  Activez et rendez visible l'équipement.

6.  **Paramètres Spécifiques :**
    *   **URL de la page Google News :** C'est le paramètre le plus important.
        *   Ouvrez votre navigateur et allez sur [Google News](https://news.google.com/).
        *   Naviguez jusqu'à la section, le sujet ou effectuez la recherche que vous souhaitez suivre.
        *   Copiez l'URL complète depuis la barre d'adresse de votre navigateur.
        *   Exemples d'URLs valides :
            *   Rubrique "Technologie" : `https://news.google.com/topics/CAAqJggKJisLoadingAnD4Kz8gEApLACKpLACMmpLAZ0hOd8O0zaA8?hl=fr&gl=FR&ceid=FR%3Afr` (l'ID peut varier)
            *   Recherche "Jeedom" : `https://news.google.com/search?q=jeedom&hl=fr&gl=FR&ceid=FR%3Afr`
        *   Collez cette URL dans le champ "URL de la page Google News". Le plugin la convertira automatiquement en URL de flux RSS.
    *   **Nombre d'articles à afficher :** Définissez combien d'articles (les plus récents) doivent être récupérés et affichés dans le widget (par défaut 5, maximum 50).
    *   **Auto-actualisation (cron) :** Configurez la fréquence à laquelle le flux doit être rafraîchi automatiquement. Utilisez l'assistant cron (bouton `?`) pour choisir une fréquence (ex: toutes les heures `0 * * * *`, toutes les 15 minutes `*/15 * * * *`).

7.  Sauvegardez votre équipement.

## Utilisation du Widget

Une fois configuré, l'équipement apparaîtra sur votre Dashboard Jeedom (si vous l'avez rendu visible).

Le widget affiche :
*   Un bouton **"Rafraîchir"** en haut à droite pour mettre à jour manuellement le flux.
*   La liste des articles récupérés, avec :
    *   Le **titre** de l'article (cliquable, ouvre l'article original dans un nouvel onglet).
    *   La **date de publication**.
    *   Un court **extrait** de la description.

Si aucun article n'est affiché, vérifiez la configuration de l'URL Google News ou essayez de rafraîchir manuellement. Un message peut indiquer la dernière tentative de mise à jour.

### Commandes de l'équipement

Dans l'onglet "Commandes" de la configuration de l'équipement, vous trouverez :
*   **Informations (non visibles sur le widget par défaut) :**
    *   `Titre du dernier article`
    *   `Lien du dernier article`
    *   `Description du dernier article`
    *   `Date de publication du dernier article`
    *   `Dernière mise à jour du flux` (indique la date et l'heure du dernier rafraîchissement réussi ou de l'erreur)
*   **Actions :**
    *   `Rafraîchir le flux` : Permet de déclencher un rafraîchissement manuel (identique au bouton sur le widget).

Ces commandes peuvent être utilisées dans des scénarios, des designs, ou d'autres interactions avec Jeedom.

## FAQ / Dépannage

*   **Le widget reste vide ou affiche "Aucun article".**
    *   Vérifiez que l'URL Google News configurée est correcte et fonctionne dans votre navigateur.
    *   Essayez de rafraîchir manuellement le flux via le bouton sur le widget ou la commande "Rafraîchir le flux".
    *   Consultez les logs du plugin (`googleNewsReader`) dans Jeedom (Analyse → Logs) pour des messages d'erreur (ex: "Impossible de récupérer le flux RSS", "Erreur parsing XML", "URL Google News non reconnue").
    *   Assurez-vous que les dépendances `php-xml` et `php-curl` sont bien installées et actives sur votre serveur Jeedom.
    *   Vérifiez votre connexion internet.

*   **Le flux ne se met pas à jour automatiquement.**
    *   Vérifiez la configuration du cron "Auto-actualisation" sur la page de l'équipement. Assurez-vous qu'elle est valide et activée.
    *   Vérifiez que le moteur de tâches de Jeedom fonctionne correctement.

*   **L'URL Google News que je veux utiliser ne fonctionne pas.**
    *   Le plugin essaie de convertir les URLs Google News standards. Certains formats d'URL très spécifiques ou anciens pourraient ne pas être compatibles. Privilégiez les URLs obtenues en naviguant sur le site Google News (sections "Topics" ou résultats de "Search").
    *   Si vous suspectez un problème de conversion d'URL, vous pouvez chercher l'URL du flux RSS directement (souvent en ajoutant `/rss/` dans l'URL Google News ou en cherchant une icône RSS sur la page) et essayer de la tester avec un lecteur RSS externe pour valider le flux. Cependant, le plugin est conçu pour prendre l'URL Google News standard.

*   **J'ai une erreur "L'extension PHP SimpleXML est manquante" ou "L'extension PHP cURL est manquante" à l'installation.**
    *   Cela signifie que les dépendances PHP n'ont pas pu être installées ou activées. Vous devrez peut-être les installer manuellement sur votre système (ex: `sudo apt-get update && sudo apt-get install php-xml php-curl`) puis redémarrer votre serveur web.

## Changelog

Voir le fichier `changelog.md` (disponible dans le plugin ou sur le Market).

```
