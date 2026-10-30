# Joomla 6 — Sécurité

- MUST appliquer ACL et vérification des autorisations **côté serveur** sur chaque opération sensible (lecture incluse lorsque privée).
- MUST contrôler le jeton anti-CSRF sur les requêtes web à effets de bord. Un jeton CSRF ne remplace ni authentification ni autorisation; les API sans session demandent une défense adaptée.
- MUST valider et normaliser tout input et limiter les valeurs acceptées (types, longueur, énumérations, chemins, identifiants).
- MUST échapper la sortie selon le contexte HTML, attribut, URL ou JavaScript; la validation à l'entrée ne suffit pas.
- MUST utiliser des paramètres liés pour les valeurs SQL; noms de colonnes, ordre et tables dynamiques doivent provenir d'une liste autorisée.
- MUST vérifier les extensions, types, tailles et destination des fichiers uploadés; empêcher les traversées de chemin et l'exécution de fichiers déposés.
- MUST protéger les exports, liens de prévisualisation et endpoints Ajax contre l'accès non autorisé et la fuite de données.
- MUST ne jamais logger les secrets, tokens, données personnelles ou contenus sensibles par défaut.
- SHOULD ajouter tests négatifs : utilisateur anonyme, droits refusés, jeton absent, XSS, SQLi, IDOR, upload interdit.

Référence : https://manual.joomla.org/docs/6.0/
