# Joomla 6 — Architecture, composants et DI

**MUST** Vérifier les classes réellement disponibles dans la version installée avant de générer du code.

- MUST conserver les namespaces PSR-4 du manifest et leur cohérence avec `src/`.
- SHOULD déclarer les services du composant dans `administrator/components/com_x/services/provider.php` selon les conventions Joomla 6 et utiliser `MVCComponent`, `MVCFactory` et `ComponentDispatcherFactory` lorsque pertinents.
- SHOULD injecter les dépendances plutôt que multiplier les accès globaux à `Factory` dans le nouveau code; les appels legacy peuvent rester dans un adaptateur identifié.
- MUST limiter l'accès à la persistance aux couches appropriées. Ne pas écrire la logique métier dans les templates.
- SHOULD isoler les dépendances envers BreezingForms/ContentBuilder derrière des services ou interfaces testables lorsque cela réduit réellement le couplage.
- MUST différencier code site et administrator, contrôleurs, routes et identités.

## Revue
- Le provider boote-t-il sans erreur ?
- Les classes autoloadent-elles depuis le namespace manifest ?
- Les erreurs de routeur/dispatcher/Factory sont-elles couvertes par un test de fumée ?

Référence : https://manual.joomla.org/docs/6.0/
