# BFNG / CBNG — Conventions de migration du projet

> Hypothèses initiales à confirmer avec chaque dépôt. Les règles ci-dessous ne constituent pas une spécification métier et ne doivent jamais autoriser des migrations de données destructrices.

- MUST préserver les formulaires, soumissions, identifiants, éléments, réglages, liens et données d'origine.
- MUST conserver le fonctionnement des vues site : liste, détail, édition, export, formulaires publics (si présentes).
- MUST conserver comportement admin et liens de menu du composant.
- MUST traiter la migration des tables `#__contentbuilder_*` vers `#__contentbuilderng_*` uniquement si la migration est explicitement prévue dans la version et l'installeur du dépôt.
- MUST ne pas désactiver les plugins historiques ou convertir les paramètres de thème sans condition de migration vérifiée.
- MUST vérifier les collisions de colonnes, états de listes, relations `form_id` et gestion de dates invalides avant tout changement de schéma.
- MUST documenter les changements de contrats des plugins, et vérifier les dépendances croisées entre BFNG et CBNG.
- SHOULD isoler les adaptateurs legacy dans des chemins identifiables afin de ne pas contaminer le code neuf.
- SHOULD conserver un jeu de données représentatif de migration et des tests de non-régression des éléments de formulaire.
- MUST obtenir une instruction explicite avant fusion structurelle BFNG/CBNG ou changement des schémas de données.
