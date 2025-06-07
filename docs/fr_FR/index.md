# Plugin Google News pour Jeedom

## Description

Le plugin Google News vous permet d'intégrer et d'afficher des flux d'actualités personnalisés provenant de Google News directement dans votre interface Jeedom. Configurez simplement un ou plusieurs flux Google News et affichez les derniers titres dans un widget dédié.

## Installation

L'installation est standard et s'effectue depuis le Market Jeedom.
Le plugin nécessite `curl` et l'extension PHP `xml` pour fonctionner. Ces dépendances sont normalement vérifiées par Jeedom lors de l'installation.

## Configuration des Équipements

Après l'installation du plugin, vous devez configurer au moins un équipement pour récupérer un flux d'actualités.

1.  Allez dans `Plugins` -> `Organisation` -> `Google News`.
2.  Cliquez sur le bouton `Ajouter` pour créer un nouvel équipement (un nouveau flux).
3.  Donnez un nom à votre équipement (ex: "Actualités Tech", "Infos Locales").
4.  Assurez-vous que l'équipement est `Activer` et `Visible`.
5.  Dans l'onglet `Equipement` (ou la section de configuration principale de l'équipement) :
    *   **URL Google News** : Saisissez ici l'URL complète de la page Google News que vous souhaitez suivre.
        *   Exemple pour un sujet : `https://news.google.com/topics/CAAqJggKIiBDQkFTRWdvSUwyMHZNRGx1YlY4U0FtVnVHZ0pWVXlnQVAB` (Sujet Technologie)
        *   Exemple pour une publication : `https://news.google.com/publications/CAAqBwgKMMOutgsw--PBAw` (Publication spécifique)
        Le plugin transformera automatiquement cette URL en l'URL du flux RSS correspondant.
    *   **Rétention des articles (jours)** : Indiquez combien de jours les articles doivent être conservés en base de données. Passé ce délai, les anciens articles seront purgés pour éviter une accumulation excessive. (Défaut : 7 jours).
    *   **Max articles pour widget** : Définissez le nombre maximum d'articles qui seront récupérés et affichés dans le widget. (Défaut : 5 articles).
    *   **Fréquence de rafraîchissement (cron)** : Vous pouvez définir une planification cron pour que le flux soit rafraîchi automatiquement (ex: `*/30 * * * *` pour toutes les 30 minutes). Laissez vide si vous préférez déclencher les rafraîchissements manuellement via la commande `Rafraîchir`.

6.  Sauvegardez votre équipement.

## Commandes de l'équipement

Chaque équipement Google News disposera des commandes suivantes :

*   **Rafraîchir** (action) : Déclenche manuellement la récupération des derniers articles pour ce flux.
*   **Derniers Articles JSON** (info/chaîne) : Contient les derniers articles au format JSON. C'est cette commande qui est utilisée par le widget. (Normalement non visible sur le dashboard).
*   **Nombre d'articles** (info/numérique) : Affiche le nombre total d'articles actuellement stockés en base pour ce flux, après application de la rétention.
*   **Statut Rafraîchissement** (info/chaîne) : Indique l'état de la dernière tentative de rafraîchissement (OK, En cours, Erreur URL, etc.).

## Affichage (Widget)

Si un équipement est configuré et visible, sa commande `Derniers Articles JSON` (qui est configurée pour utiliser le template de widget du plugin) affichera les titres des derniers articles.
*   Chaque titre est un lien cliquable vers l'article complet sur Google News.
*   La date de publication est affichée à côté de chaque titre.
*   Le nombre d'articles affichés dépend du paramètre "Max articles pour widget" de l'équipement.

## Dépannage

*   **Vérifiez les logs** : En cas de problème, consultez les logs du plugin (`googleNews`) dans `Analyse` -> `Logs` pour des messages d'erreur détaillés.
*   **URL invalide** : Assurez-vous que l'URL copiée depuis Google News est correcte et correspond bien à un sujet (`/topics/`) ou une publication (`/publications/`).
*   **Dépendances** : Vérifiez que `curl` et l'extension `php-xml` sont bien installés et activés sur votre système Jeedom.

## Limitations

*   Le plugin se base sur la structure des flux RSS de Google News. Si Google modifie cette structure de manière significative, le plugin pourrait nécessiter une mise à jour.
