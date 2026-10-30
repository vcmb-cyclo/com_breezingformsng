# Joomla 6 — Installation et mises à jour

- MUST conserver manifest XML, namespaces, fichiers langue, assets et script d'installation cohérents.
- MUST rendre les mises à jour **idempotentes** : rejouer une migration ne doit pas créer colonnes, menus ou états en double.
- MUST identifier explicitement les scénarios fresh install / update / reinstall / uninstall; ne jamais supprimer des données sur uninstall par défaut sans décision produit.
- MUST détecter l'état réel du schéma avant `ALTER TABLE` et journaliser les erreurs actionnables.
- MUST ne pas désactiver ni supprimer les anciennes extensions/plugins sans plan de migration explicite.
- MUST contrôler les liens de menu et paramètres `option=com_...` pendant les renommages.
- SHOULD préparer backup, rollback, tests sur copies jetables et checksum/contrôle post-installation.
- SHOULD tester MySQL/MariaDB et différentes préfixations si le projet les supporte.

Référence : https://manual.joomla.org/docs/6.0/
