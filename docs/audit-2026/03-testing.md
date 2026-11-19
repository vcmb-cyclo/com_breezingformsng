# Audit 03 — Stratégie et qualité des tests (BFNG 6.1.0-RC22)

Date : 2026-11-19. Audit en lecture seule. Branche `main`, commit `1faa98d2`.
Sources lues : `AGENTS.md`, `docs/best-practices/testing-quality.md`, `docs/maintenance/tests/*` (état du 2026-11-18), `.github/workflows/*`, `phpunit.xml`, `playwright*.mjs`, `scripts/*`, `tests/`.

## 1. Inventaire chiffré

| Répertoire | Fichiers | Classes `*Test.php` | Remarques |
|---|---|---|---|
| `tests/Administrator` | 103 | 95 | dont `Mcp/` (11 fichiers) ; 63 classes appellent `file_get_contents` |
| `tests/Site` | 253 | 165 | rendu QuickMode et snapshots HTML ; 32 classes lisent le source |
| `tests/Security` | 1 | 1 | `MutationRequestRegressionTest` |
| `tests/Integration` | 1 | 1 | 6 tests, exigent `BFNG_JOOMLA_LIBRARIES` |
| `tests/Compatibility` | 1 | 1 | 1 test, contrôle du manifeste |
| `tests/Performance` | 1 | 1 | aucune mesure (voir TST-004) |
| `tests/Browser` (Playwright sans Joomla) | 12 | — | 12 specs, 34 `test()` |
| `tests/E2E` (Joomla jetable) | 12 | — | 9 specs, 80 `test()` comptés par regex `^\s*test(` (hors `test.describe`/boucles) |
| `tests/JavaScript` (node:test) | 9 | — | 9 fichiers `.test.mjs` |
| `tests/Packaging` | 1 | — | `package-assets.sh` |
| Racine `tests/` | 2 | — | `Installer*Regression.php`, hors PHPUnit, lancés seulement par le job `php-compatibility` |

Total PHP sous `tests/` : environ 29 800 lignes. Code source mesuré : 339 fichiers PHP sous `*/src`, environ 57 200 lignes.

Indicateurs de qualité comptés par grep : 1543 occurrences `assertStringContainsString/NotContainsString`, 99 fichiers de test avec `file_get_contents`, 14 classes `*Architecture*Test`, 26 fichiers utilisant la réflexion, 15 `createMock/createStub`, 1 `assertTrue(true)`, 0 `waitForTimeout`, 0 `test.skip/fixme/only`, 0 `data-testid`, 300 `locator(` contre quelques `getByRole/getByLabel` (login uniquement).

## 2. Ce qui a été exécuté

| Contrôle | Environnement | Résultat |
|---|---|---|
| `vendor/bin/phpunit --no-coverage` (3 suites) | **PHP 8.5.4 hôte**, PHPUnit 12.5.42, sans plugin de compatibilité | 1451 tests, 9125 assertions, **OK avec 1 warning et 6 skipped**, 2,7 s |
| Warning | idem | `InstallerLegacyMigrationTest.php:112` eval : `Undefined property: class@anonymous::$currentInstalledVersion` (voir TST-005) |
| 6 skipped | idem | tous `Integration/ContentPluginTest` (« Set BFNG_JOOMLA_LIBRARIES »). Sans la variable, la suite d'intégration ne garantit rien |
| Couverture | non mesurée par moi | rapport existant `coverage/clover.xml` du **2026-11-18 19:33** (PHP 8.3 + pcov, conteneur) : 7705 / 25298 instructions = **30,46 %**, méthodes 562 / 1625. Plus ancien que les 123 tests ajoutés depuis (1328 -> 1451) : valeur à rafraîchir |
| Node, Playwright Browser, E2E, `joomla-install-smoke.sh`, PHPCS, PHPStan, Psalm | non exécutés | « non exécuté » (E2E/smoke exigent Docker/Joomla, que la consigne interdit de toucher) |
| Stabilité sur N exécutions | non testée | 1 seule exécution PHPUnit par moi |

Les chiffres de `TEST_AUDIT.md`/`TEST_OPTIMIZATION_RESULTS.md` (1328 tests, 83 E2E OK sur 2 exécutions) sont repris comme déclarés par ces documents, pas revérifiés ; ma suite PHPUnit confirme la progression à 1451 tests. Plusieurs points de `TEST_AUDIT.md` sont déjà corrigés dans l'arbre (0 `waitForTimeout`, `trace`/`screenshot` Browser, 3 suites), ce que j'ai vérifié dans `playwright.config.mjs`, `phpunit.xml` et par grep.

## 3. Constats

### TST-001 — Aucun test sur les outils MCP d'écriture (création, mise à jour, corbeille de formulaires)
- Catégorie : couverture fonctionnelle / sécurité. Impact : **élevé**. Confiance : élevée.
- Preuves : `McpFormWriter.php` (1494 lignes), `ManageFormFieldsTool.php` (173), `CreateFormTool.php` (122), `UpdateFormTool.php` (85), `TrashFormTool.php` (73), `RestoreFormTool.php` (71) : aucun fichier de `tests/` ne référence ces classes (`grep -rw`). Les tests MCP couvrent la lecture (`McpReadToolsTest`, `GetFormToolTest`, `FormCheckerTest`, `McpServerTest`, `OriginValidatorTest`). Couverture clover : `Mcp/Tool` 5,8 % (16/276). L'E2E `mcp-endpoint.spec.mjs` (7 tests) vérifie la liste des outils et leurs annotations (`readOnlyHint`, `destructiveHint`), pas leur exécution d'écriture (non vérifié ligne par ligne sur toute la spec ; lecture ciblée uniquement). `TEST_OPTIMIZATION_RESULTS.md` note que ces deux outils ont été modifiés pendant la session sans test.
- Conséquences : surface d'écriture exposée à un jeton d'API, sans filet pour la validation, l'ACL, la perte de données (`destructiveHint`).
- Recommandation : tests unitaires de `McpFormWriter` (validation d'arguments, refus ACL, idempotence corbeille/restauration) puis un E2E d'écriture avec jeton non autorisé.
- Effort : M-L. Risque de régression : faible (ajout de tests). Tests nécessaires : appels valides/invalides par outil, jeton sans droit, formulaire verrouillé, rejeu.

### TST-002 — Couverture quasi nulle du cœur métier : contrôleurs, soumission, notifications, exports, scripting
- Catégorie : couverture. Impact : **élevé**. Confiance : élevée (mesure du 2026-11-18, partiellement périmée).
- Preuves (clover, instructions couvertes / total) : `Controller` 0 / 2173 ; `Service/Submission` 97 / 1530 (6,3 %) ; `Service/Notification` 11 / 840 (1,3 %) ; `Service/Export` 6 / 768 (0,8 %) ; `Service/Scripting` 16 / 693 (2,3 %) ; `Model` 483 / 3718 (13 %) ; `Helper` 32 / 469 ; toutes les `View/*` à 0 %. À l'inverse `Service/Rendering` 79 % (4718/5944) : l'effort est concentré sur le rendu.
- Par nom de classe, 52 classes de `src` (≈ 5700 lignes) ne sont référencées par aucun test, dont `RecordstransferController` (417), `LegacyConversionModel` (199), `MailSender` (83), `HtmlSanitizer` (77), `MailchimpClient`, `DropboxUploader`, `ScriptingRuntime` (101). Une référence par nom n'est pas une couverture ; ceci est un indicateur, pas une mesure.
- Conséquences : le chemin « affichage -> validation serveur -> enregistrement -> envoi de mail » n'est protégé que par un E2E (`forms.spec` « a submission on the site makes a record ») et par des tests de structure (voir TST-003).
- Recommandation : tests de comportement sur `SubmissionEngine`, `MailSender`, `HtmlSanitizer`, exports (`RecordExporter`), avec fakes de base de données ; viser des seuils par répertoire critique.
- Effort : L. Risque : faible. Tests : validations, champs requis, échappement HTML/CSV, pièces jointes, échec d'envoi.

### TST-003 — Beaucoup de tests vérifient la forme du source, pas le comportement
- Catégorie : tests artificiellement positifs. Impact : **élevé** (faux sentiment de sécurité). Confiance : élevée.
- Preuves : 99 classes avec `file_get_contents` sur le code du composant ; 1543 `assertStringContainsString` ; 14 `*ArchitectureTest`. Exemples : `tests/Security/MutationRequestRegressionTest.php:27` valide la protection CSRF par `assertStringContainsString("Session::checkToken('post')", $source)` ; `SubmissionEngineArchitectureTest` (54 assertions, `file_get_contents` aux lignes 23, 34, 46) ; `InstallerScriptArchitectureTest` (19) ; `PackageTransferArchitectureTest` (41).
- Conséquences : ces tests passent si la chaîne est présente mais inopérante (code mort, mauvaise branche) et cassent au moindre refactoring. Un contrôle CSRF peut être supprimé de la logique réelle en laissant la chaîne dans un commentaire.
- Compensation : l'E2E `security.spec.mjs` (24 tests) teste réellement droits, CSRF et import sur un Joomla jetable, ce qui limite le risque pour les chemins qu'il couvre. Je ne l'ai pas exécuté.
- Recommandation : garder ces tests comme garde-fous bon marché, mais exiger pour chaque garde critique un test de comportement (contrôleur appelé sans jeton -> refus) ; les étiqueter par groupe (`#[Group('source-shape')]`) pour mesurer leur part.
- Effort : L. Risque : faible. Tests : tests de contrôleurs avec requête simulée.

### TST-004 — Le répertoire « Performance » ne mesure aucune performance
- Catégorie : tests artificiellement positifs. Impact : faible-moyen. Confiance : élevée.
- Preuves : `tests/Performance/PerformanceRegressionTest.php` n'utilise que `assertStringContainsString`/`assertSame(2, substr_count(...))` sur le texte du code (ex. `private array $itemsByRule = [];`, `array_chunk($ids, self::BULK_ID_BATCH_SIZE)`).
- Conséquences : le nom promet une garantie de performance qu'il n'apporte pas ; une réécriture équivalente fait échouer le test, une régression réelle passe.
- Recommandation : renommer (`SourceShapeTest`) ou remplacer par des tests de nombre de requêtes (compteur sur un faux `DatabaseDriver`) pour les exports volumineux.
- Effort : S-M. Risque : faible. Tests : lot de 1000 enregistrements, nombre de requêtes borné.

### TST-005 — Le test de migration de l'installateur exécute du source découpé par `eval`
- Catégorie : migrations / fiabilité. Impact : moyen. Confiance : élevée.
- Preuves : `tests/Administrator/InstallerLegacyMigrationTest.php:95-112` extrait `update()` et `postflight()` du `script.php` (180 Ko) par `strpos`/`substr` puis les exécute via `eval` dans une classe anonyme ; la propriété `$currentInstalledVersion` (`script.php:92`) n'existe pas dans cette classe, d'où le warning PHPUnit réel de mon exécution. Les bornes sont des chaînes (`"\n    private "`, `'        $durationSeconds = '`) : toute réorganisation de `script.php` casse le test ou, pire, le fait tester un fragment différent. Les deux scripts `tests/Installer*Regression.php` ne sont pas dans PHPUnit.
- Conséquences : le test peut devenir non représentatif sans échouer ; le warning masque d'éventuels vrais warnings (le CI n'a pas `--fail-on-warning`).
- Compensation : `scripts/joomla-install-smoke.sh` (lignes ~396-503) fait une vraie mise à jour : ligne de formulaire et d'enregistrement conservées, schéma remis à la première version SQL, colonnes migrées, bibliothèque TCPDF obsolète supprimée, `schema_version` finale. Cela est bon, mais exécuté seulement dans le job `package` et seulement depuis un état « pré-NG » fabriqué, pas depuis chaque version publiée précédente.
- Recommandation : extraire la logique migratoire dans des classes testables (le test d'architecture le suggère déjà) ; ajouter `failOnWarning="true"` ; intégrer les deux `*Regression.php` à PHPUnit.
- Effort : M. Risque : moyen (touche l'installateur). Tests : installation vierge, mise à jour depuis N-1, rejeu.

### TST-006 — Deux fichiers de tests JavaScript ne sont jamais exécutés en CI
- Catégorie : CI/CD. Impact : moyen. Confiance : élevée.
- Preuves : `tests/JavaScript` contient 9 fichiers ; l'étape « Run JavaScript tests » de `build-package.yml` en liste 7 (`quickmode-tree-model`, `vendor-sync`, `quickmode-form-dirty`, `quickmode-debug-lifecycle`, `quickmode-options-toggle`, `container-log`, `about-easter-egg`). `bfng-widgets.test.mjs` et `query-list-pagination.test.mjs` n'apparaissent ni dans le workflow ni dans `package.json` ni dans `scripts/` (grep). Je ne les ai pas exécutés.
- Conséquences : tests écrits mais jamais bloquants.
- Recommandation : remplacer la liste par `node --test tests/JavaScript/*.test.mjs`.
- Effort : S. Risque : très faible. Tests : le fichier lui-même.

### TST-007 — Seuil de couverture CI trop bas et rapport périmé
- Catégorie : CI/CD. Impact : moyen. Confiance : élevée.
- Preuves : `build-package.yml` : `php scripts/check-coverage.php coverage/clover.xml 30` ; dernière mesure 30,46 %, soit 0,46 point de marge ; couverture seulement sur PHP 8.3, 0 conditionnelle (pcov). Les modules critiques à ~0 % (TST-002) passent sans alerte ; seul le total est contrôlé.
- Recommandation : seuils par répertoire critique (Controller, Submission, Notification, Export, Mcp), augmentés progressivement ; ne pas dépasser ce que mesure réellement la CI.
- Effort : S. Risque : faible (peut faire échouer le CI). Tests : le script de contrôle accepte déjà un fichier clover.

### TST-008 — La suite d'intégration dépend d'une variable d'environnement et d'un téléchargement externe
- Catégorie : CI/flakiness. Impact : moyen. Confiance : moyenne (étape non exécutée par moi).
- Preuves : 6 skipped reproduits localement ; le CI télécharge `Joomla_6.2.0-Stable-Full_Package.zip` de GitHub avec contrôle SHA-256 et `--fail-on-skipped` (bonne pratique). Mais `CaptchaImageTest.php:34` contient `markTestSkipped` si GD/Composer manque : `--fail-on-skipped` rend ce cas bloquant, donc cohérent. Risque restant : une indisponibilité GitHub fait échouer les trois lignes de la matrice.
- Recommandation : mettre l'archive en cache (`actions/cache`) ; documenter la variable dans `README`/`AGENTS.md` pour le poste local.
- Effort : S. Risque : faible.

### TST-009 — Matrice PHP : PHP 8.4 (cible) et 8.5 sans couverture ni PHPStan ; 8.3 seule validée en analyse
- Catégorie : CI/CD. Impact : moyen. Confiance : élevée.
- Preuves : `phpstan` et `lint` n'utilisent que PHP 8.3 (`setup-php` avec `php-version: '8.3'`) alors que `AGENTS.md` fixe 8.4 en production et 8.5 en compatibilité ; PHPUnit tourne sur 8.3/8.4/8.5 (bon) ; les `Installer*Regression.php` et `php -l` aussi. Le projet exige aussi l'absence de code de compatibilité ancienne ; tester 8.3 contredit la cible déclarée.
- Conséquences : dépréciations 8.4/8.5 non détectées par l'analyse statique ; l'étiquette « PHP 8.3 » du CI ne reflète pas la production.
- Recommandation : lancer PHPStan sur 8.4 (et 8.5 en matrice) ; décider si 8.3 doit rester pour la couverture (conteneur de dev en 8.3).
- Effort : S. Risque : faible.

### TST-010 — Tests de caractérisation (snapshots) sans assertion métier explicite
- Catégorie : qualité des assertions. Impact : moyen. Confiance : moyenne.
- Preuves : suite `characterization` (`tests/Site/Service/Rendering/QuickMode`), 89 tests déclarés dans `TEST_OPTIMIZATION_RESULTS.md`, 80 snapshots HTML (`TEST_AUDIT.md`, non recomptés par moi). Les snapshots régénérés sans lecture masquent une régression. Rendu couvert à 79 %, c'est la zone la mieux protégée mais en sortie brute.
- Recommandation : exiger une revue des diffs de snapshots en PR ; compléter par des assertions ciblées sur l'échappement et les attributs ARIA.
- Effort : S. Risque : faible.

### TST-011 — Sélecteurs CSS fragiles dans Playwright, aucun `data-testid`
- Catégorie : flakiness/maintenance. Impact : faible-moyen. Confiance : élevée.
- Preuves : 300 `locator(` dans `tests/Browser` et `tests/E2E` ; 0 `data-testid`/`getByTestId` ; `getByRole/getByLabel` limités à `login.mjs`, `legacy-conversion` et `mcp-endpoint`. E2E en `workers: 1` et mode série (forms, security, mcp) : un échec en cascade masque les suivants ; `TEST_OPTIMIZATION_RESULTS.md` a abandonné `storageState` pour raison d'isolation (argument recevable).
- Recommandation : ajouter des `data-testid` ciblés sur l'éditeur QuickMode (décision produit requise, AGENTS.md demande de garder le CSS/JS minimal).
- Effort : M. Risque : faible.

### TST-012 — Sécurité des téléversements et RGPD : lacunes E2E ou fonctionnelles
- Catégorie : couverture sécurité. Impact : moyen-élevé. Confiance : moyenne.
- Preuves : `Site/Service/Upload` couvert à 63 % (243/383), bonne base unitaire ; `grep` ne trouve aucun E2E de téléversement côté site (les E2E `package-upload` concernent l'import de paquets d'administration) ; `TEST_AUDIT.md` signale l'absence de test explicite de suppression/anonymisation des enregistrements (je n'ai pas relu le code RGPD).
- Recommandation : un E2E site « fichier avec double extension / MIME falsifié refusé » et un test sur la suppression d'enregistrements et de fichiers liés.
- Effort : M. Risque : faible.

### TST-013 — Pas de test d'ACL au niveau des contrôleurs administrateur
- Catégorie : sécurité/ACL. Impact : élevé. Confiance : moyenne.
- Preuves : `tests/Administrator/Controller` ne contient que `RawCodeInputTest.php` ; couverture `Controller` 0 %. Les refus ACL reposent sur `QuickmodeSaveAuthorizationTest`, des tests de structure (`AreaAccessArchitectureTest`, `CheckOutArchitectureTest`) et `E2E/security.spec.mjs` (non exécuté).
- Recommandation : matrice paramétrée (anonyme / sans droit / autorisé) par tâche d'écriture de contrôleur, avec jeton valide et absent.
- Effort : M. Risque : faible.

### TST-014 — Multilingue : cohérence des clés vérifiée, pas le rendu localisé
- Catégorie : multilingue. Impact : faible. Confiance : élevée.
- Preuves : `scripts/check-translations.py` couvre les 8 langues exigées (`LANGS` ligne 17) et `check-translation-keys.py` les clés utilisées, tous deux dans le job `lint`. Aucun test PHPUnit ou E2E ne rend une page dans une langue autre que en-GB ; seuls quelques tests citent `fr-FR`/`de-DE`.
- Recommandation : un E2E de smoke en `fr-FR` (et une langue à accents longs comme `hu-HU`) pour détecter les chaînes tronquées en mise en page.
- Effort : S. Risque : faible.

### TST-015 — Détails mineurs de qualité
- `LegacyFormConverterTest.php:200` : `self::assertTrue(true)` dans un `catch` après `self::fail(...)` ; fonctionne mais `expectException` serait plus clair. Impact faible.
- `phpunit.xml` : un seul `cacheDirectory`, pas de `failOnWarning`/`failOnRisky`/`failOnDeprecation` ; le warning TST-005 passe donc le CI.
- Aucun test mutationnel : Infection a été tenté et retiré (cause non identifiée, d'après `TEST_OPTIMIZATION_RESULTS.md`).
- Le job `release` dépend de `package`, qui dépend de tous les autres jobs, y compris les E2E : bonne chaîne de blocage. Les workflows `codeql`, `semgrep`, `trivy`, `psalm` sont séparés et leur caractère obligatoire dépend des règles de branche (non vérifiables ici).

## 4. Matrice risques × couverture

| Domaine | Risque | PHPUnit | E2E / Browser | Évaluation |
|---|---|---|---|---|
| Soumission frontend et validation | Critique | 6 % `Submission` ; tests de structure | 1 E2E + Stripe (2) | Insuffisante (TST-002) |
| Notifications / mails | Élevé | 1,3 % | indirecte | Insuffisante |
| Exports (CSV/XLSX) | Élevé | 0,8 % ; sanitiseur CSV testé | `records-transfer` (11) | Moyenne |
| ACL / CSRF administrateur | Critique | structure + 1 contrôleur | `security.spec` (24) | Moyenne, dépend de l'E2E (TST-003, 013) |
| MCP lecture | Moyen | bonne | 7 | Bonne |
| MCP écriture | Élevé | **0** | liste des outils | Insuffisante (TST-001) |
| Téléversement de fichiers | Élevé | 63 % | aucun côté site | Moyenne (TST-012) |
| Installation / mise à jour | Élevé | eval du source | smoke CI | Moyenne (TST-005) |
| Rendu QuickMode | Moyen | 79 %, snapshots | 34 specs Browser | Bonne |
| Multilingue | Faible | script de clés | aucun rendu | Moyenne (TST-014) |
| Scripting utilisateur | Élevé | 2,3 % | indirecte | Insuffisante |
| Intégrations externes (Stripe, PayPal, Mailchimp, Dropbox) | Moyen | partielle (Stripe, PayPal) | 2 Stripe | Moyenne |

## 5. Stratégie proposée, fondée sur les risques

1. Rafraîchir la couverture (conteneur PHP 8.3 via `scripts/coverage-docker.sh`) et publier les pourcentages par répertoire dans le CI.
2. Combler d'abord les trous critiques : MCP écriture (TST-001), ACL/CSRF de contrôleurs (TST-013), moteur de soumission et mails (TST-002).
3. Corriger les points de CI à effort faible : TST-006 (glob node), TST-009 (PHPStan 8.4/8.5), `failOnWarning`, mise en cache de l'archive Joomla (TST-008).
4. Réduire la dette des tests de forme : groupe PHPUnit pour les 99 classes, règle « une garde de sécurité = un test de comportement » (TST-003).
5. Seuils de couverture par répertoire, montés par paliers ; mutation testing seulement quand Infection fonctionne.
6. Un E2E par risque non couvert : téléversement site, MCP écriture, smoke fr-FR.

## 6. Domaines sans problème constaté

- Rapidité et stabilité apparente de PHPUnit : 1451 tests en 2,7 s, sans test lent notable (une seule exécution).
- Absence de `waitForTimeout`, `test.skip/fixme/only`, retries masquant les échecs (`retries: 0`), `forbidOnly` en CI, traces conservées en échec.
- Cohérence des traductions : 8 langues contrôlées automatiquement en CI.
- Chaîne de publication : `release` bloqué par lint, PHPStan, PHPUnit et le job `package` (build, validation du contenu, smoke d'installation/mise à jour, E2E).
- Rendu QuickMode bien couvert (79 %).
- Tests d'installation : le smoke vérifie la conservation de données réelles, la migration de schéma et la suppression d'une bibliothèque obsolète.
- Aucune assertion triviale systématique : un seul `assertTrue(true)`, aucun `doesNotPerformAssertions`.

## Limites de cet audit

Je n'ai pas exécuté Playwright, node:test, E2E, smoke d'installation ni PHPCS/PHPStan/Psalm. Les pourcentages de couverture proviennent d'un rapport du 2026-11-18, non régénéré. Les références par nom de classe (TST-002) sont un indicateur. Les doublons ou faux positifs possibles sont signalés en « confiance moyenne ».
