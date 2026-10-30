# Joomla 6 — Base de données

- MUST utiliser `DatabaseInterface`, les query builders et bind values disponibles dans la version installée pour les nouvelles requêtes.
- MUST préfixer les noms des tables Joomla via `#__` dans les requêtes compatibles avec le préfixage de Joomla.
- MUST interdire la concaténation de valeurs non fiables dans SQL; valeurs liées et identifiants validés sont des problèmes différents.
- MUST définir une stratégie explicite pour `NULL`, dates invalides, charset, JSON, clés uniques et valeurs par défaut lors d'une migration.
- SHOULD documenter le grain, les relations et les index; éviter N+1 et requêtes surdimensionnées; mesurer avant d'optimiser.
- MUST préserver les données préexistantes lors des changements de schéma sauf autorisation expresse.
- SHOULD utiliser transactions lorsque les opérations et moteurs concernés les supportent; prévoir compensation pour DDL non transactionnel.
- MUST invalider les caches concernés après une mutation; éviter les caches statiques indexés insuffisamment.
- SHOULD tester bases vides, schémas anciens et nouveaux, migration répétée et préfixes différents.

Référence : https://manual.joomla.org/docs/6.0/
