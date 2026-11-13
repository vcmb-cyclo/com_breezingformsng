# Tests, qualité et critères de livraison

## Avant modification
- MUST inventorier scénario existant, risques de régression et tests disponibles.
- SHOULD identifier une reproduction du bogue avant correction.

## Commandes conditionnelles
- `php -l chemin/vers/fichier.php` sur fichiers PHP modifiés.
- `vendor/bin/phpcs` et `vendor/bin/phpstan analyse` si configurés.
- `vendor/bin/phpunit` si configuré.
- Tests fonctionnels via navigateur/Playwright si infrastructure présente.

Ne jamais prétendre avoir exécuté une commande non exécutée. Ne pas introduire de dépendances pour faire passer un contrôle sans accord.

## Matrice minimale
- Admin/site; anonyme, autorisé, refusé.
- Installation vierge / mise à jour ancienne version / installation rejouée.
- CRUD et exports; CSRF absent; ressources d'un autre utilisateur.
- Routes SEF et non SEF, multilingue, responsive selon périmètre.
- Cas extrêmes : dates invalides, valeurs nulles, enregistrements volumineux, doublons.

## Livraison
- MUST lister fichiers changés, comportement, effet DB, tests passés/échoués/non lancés, risques ouverts.

## Tests de bout en bout et sécurité

- MUST désactiver la visite guidée Joomla avant toute navigation des tests navigateur : bloquer `**/plg_system_guidedtours/**` pour chaque nouvelle page, ou désactiver le plugin dans l'environnement de test jetable.
- `tests/E2E/security.spec.mjs` vérifie sur un Joomla jetable (`scripts/joomla-e2e.sh`) les droits, les jetons CSRF et l'import de paquets ; chaque protection doit être cassée une fois pour prouver que le test échoue pour la bonne raison.
- `tests/E2E/container-log.mjs` échoue si un parcours ajoute un avertissement ou une erreur PHP au journal du conteneur ; il est couvert par `npm run test:container-log`.
