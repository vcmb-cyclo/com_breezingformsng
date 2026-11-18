# TEST_COVERAGE_MATRIX

Légende : U = PHPUnit, I = intégration Joomla, E = Playwright E2E (Joomla jetable), B = Playwright Browser. Colonne « Tests existants » = fichiers présents ; le **contenu des E2E n'a pas été exécuté** : « à confirmer » signale une lacune probable à vérifier par lecture/exécution.

| Fonctionnalité | Risque | Niveau recommandé | Tests existants | Lacune |
|---|---|---|---|---|
| Création/édition de formulaire QuickMode | Métier élevé | U + E | `forms.spec` (15), `Browser/quickmode-*` (22), `Administrator/*` | Sauvegarde ACL OK (`QuickmodeSaveAuthorizationTest`) |
| Duplication / suppression / corbeille | Moyen | U + E | `forms.spec`, `RecordState`, commit « align trash permissions » | Duplication : à confirmer |
| Permissions et élévation de privilèges | Critique | U + E | `security.spec` (23), `Security/MutationRequestRegressionTest` | Garder les deux niveaux (risques distincts) |
| CSRF sur actions d'état | Critique | U + E | idem | — |
| Rendu frontend, échappement HTML | Critique | U (caractérisation + assertions) | 97 classes de rendu, 80 snapshots | Assertions explicites d'échappement par champ |
| Validation + soumission frontend | Critique | I + E | `Site/Service/Submission` (5), `Runtime` (15) | **E2E soumission réelle + message de confirmation : à confirmer** |
| Téléversement de fichiers | Critique sécurité | U + E | `Site/Service/Upload` (8), `Browser/quickmode-upload-messages` | **E2E frontend type/extension/taille absent (à confirmer)** |
| Paiements (PayPal, Stripe) | Critique financier | U (+ mutation) | `PayPalPaymentTest` 36, `StripeAmountTest` 30, `PayPalInvalidIpn…` | Mutation testing |
| Notifications (mail, Salesforce) | Moyen | U | `Notification` (3) | Échec SMTP : à confirmer |
| Export CSV/XLSX | Élevé | U + E | `CsvCellSanitizerTest` 23, `RecordTransferTest` 40, `records-transfer` | Injection de formules couverte par CsvCellSanitizer |
| Import/export de paquets | Élevé | U + E + B | `package-upload`, `PackageTransfer*`, `Browser/package-*` | — |
| MCP (endpoint, origine) | Élevé | U + E | `Mcp` (11), `mcp-endpoint.spec` | — |
| Données personnelles (suppression/anonymisation) | Élevé RGPD | U + E | `RecordState`, `records-*` | **Pas de test explicite d'anonymisation/purge (à confirmer)** |
| Installation / mise à jour / rejeu | Critique | I + CI | `script.php` : `InstallerScriptArchitectureTest`, 2 scripts `*Regression.php`, `joomla-install-smoke.sh` | Migration réelle uniquement en CI ; régressions non dans PHPUnit |
| Compatibilité PHP 8.3-8.5 | Élevé | CI | `php -l` ×3, PHPUnit sur 8.5 local | PHPUnit en CI sur 8.3, 8.4 et 8.5 ; couverture (pcov) seulement sur 8.3 |
| Mobile / sombre | Moyen | B | `quickmode-calendar-responsive`, commit dark scheme | Couverture limitée |
| Performance | Faible | U | `Performance/PerformanceRegressionTest` | — |
