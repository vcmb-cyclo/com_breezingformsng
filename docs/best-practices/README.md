# Guides Joomla 6 / PHP 8.3 pour agents IA

Pack de recommandations **proposé pour BFNG/CBNG**, non officiel Joomla. Date: 2026-10-30.

## Installation
Copier `AGENTS.md` et `CLAUDE.md` à la racine du dépôt et le répertoire `docs/best-practices/` dans `docs/`. Fusionner plutôt qu'écraser des instructions existantes. Adapter les noms des composants, version exacte Joomla et scripts de test.

## Ordre de priorité
1. Consignes de sécurité, demandes explicites et spécifications métier.
2. Instructions spécifiques au dépôt et tests existants.
3. `AGENTS.md`, puis guides thématiques ci-dessous.
4. Références officielles **de la version Joomla installée**, pas `next`.

Les règles utilisent MUST / SHOULD / MAY. Elles ne remplacent pas les vérifications des signatures dans le code installé.

## Liens de référence
- https://manual.joomla.org/docs/6.0/
- https://manual.joomla.org/about/versioning/
- https://github.com/joomla/Manual/tree/main/versioned_docs/version-6.0
- https://api.joomla.org/
- https://www.php.net/manual/fr/
- https://www.php-fig.org/psr/psr-4/
- https://www.php-fig.org/psr/psr-12/
- https://www.php.net/supported-versions.php

## Guides
- `joomla6-architecture.md` : services, DI, découpage.
- `joomla6-mvc.md` : contrôleurs, modèles, vues, frontend/backend.
- `joomla6-plugins-events.md` : événements et plugins.
- `joomla6-security.md` : autorisations, CSRF, XSS, uploads.
- `joomla6-database.md` : requêtes, migrations, cache.
- `joomla6-installer.md` : installation/mise à jour.
- `joomla6-webassets-routing.md` : assets, routage, i18n.
- `php83-best-practices.md` : PHP et analyse statique.
- `testing-quality.md` : validation, régression et livraison.
- `bfng-cbng-migration.md` : contraintes métier à confirmer pour BFNG/CBNG.
