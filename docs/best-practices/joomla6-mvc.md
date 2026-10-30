# Joomla 6 — MVC

- MUST utiliser des classes de contrôleur/modèle/vue correspondant à la convention de nommage, au namespace et à la zone du composant.
- SHOULD garder contrôleurs minces : orchestration, vérification des jetons et autorisations, puis délégation.
- MUST vérifier l'autorisation pour chaque lecture, modification, export, suppression et action Ajax sensible, y compris l'accès direct via URL.
- SHOULD placer les requêtes dans les modèles/services, éviter les requêtes depuis les templates.
- MUST maintenir la compatibilité des paramètres de menus, `Itemid`, URLs existantes et redirections pendant une migration.
- MUST traiter `getState()`, `getTable()` et MVCFactory comme des contrats à vérifier dans le CMS installé : ne pas déduire les signatures de modèles hérités.
- SHOULD tester les vues site et administration, avec utilisateur anonyme, autorisé et refusé.

Référence : https://manual.joomla.org/docs/6.0/
