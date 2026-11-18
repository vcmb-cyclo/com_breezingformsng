# TEST_AUDIT — état initial (2026-11-18)

Branche `feat/mcp-form-management`, arbre propre. Toutes les mesures ci-dessous ont été exécutées localement ; ce qui n'a pas été exécuté est indiqué comme tel.

## 1. Inventaire

| Catégorie | Outil / config | Fichiers | Tests | Durée mesurée |
|---|---|---|---|---|
| PHPUnit (suite `unit`, tout `tests/`) | `phpunit.xml`, PHPUnit 12.5.42, **PHP 8.5.4 hôte** | 255 classes `*Test.php` (+ 2 `*Regression.php` hors suite) | 1328 tests, 8046 assertions, 6 skipped | **5,6 s** (5,9 s mur) |
| PHPUnit « intégration » (`tests/Integration`) | exige `BFNG_JOOMLA_LIBRARIES` | 1 | 6 (skipped par défaut) ; **6 OK / 30 assertions** avec les libs du conteneur `joomla6-joomla-1` | 1,2 s |
| Node (`node --test tests/JavaScript/*.test.mjs`) | node:test | 8 | 23, 23 OK | 0,6 s |
| Playwright « Browser » (`playwright.config.mjs`) | Chromium, sans Joomla (pages statiques/maquettes) | 12 specs | 39, 39 OK, 0 flaky | **3,8 s** (4,8 s mur) |
| Playwright « E2E » (`playwright.e2e.config.mjs`) | Joomla 6.2 jetable via `scripts/joomla-e2e.sh` (Docker) | 9 specs | **83** (mesure ultérieure ; mon comptage initial par regex en donnait 75) | 4,3 min de tests, **5 min 8 s** avec le provisionnement ; avant correction : 78 OK, **1 échec** (test MCP périmé) et 4 non exécutés (mode série) |
| Hors PHPUnit | `tests/Packaging/package-assets.sh`, `scripts/check-*`, `joomla-install-smoke.sh`, lint CSS/JS/PHPCS/PHPStan/Psalm/Semgrep/CodeQL/Trivy | | | non mesuré |

Répartition PHPUnit : `Site/` 159 classes (dont ~97 rendu/QuickMode, 80 snapshots HTML), `Administrator/` 92 (dont Mcp 11), `Compatibility` 1, `Integration` 1, `Performance` 1, `Security` 1.

CI (`.github/workflows/build-package.yml`) : php-compatibility (8.3/8.4/8.5, `php -l`), lint, frontend-checks (lint + `npm run test:browser`), phpstan, unit-tests (pcov seulement sur 8.3, seuil de couverture **30 %**), package, release. Les E2E sont lancés via `joomla-e2e.sh` (workers: 1, trace `retain-on-failure`).

Fixtures : `tests/Administrator/fixtures` (2), `Site/Service/Notification/fixtures`, `Callback/fixtures`, `__snapshots__` (80 HTML), `tests/Support`.

## 2. Mesures de référence

| Indicateur | Valeur |
|---|---|
| Tests PHPUnit | 1328 (6 skipped = suite Integration sans libs) |
| Tests d'intégration | 6 (réels, exigent les libs Joomla) |
| Tests JS Node | 23 |
| Tests Playwright Browser / E2E | 39 / 75 |
| Durée PHPUnit | 5,6 s |
| Durée Node | 0,6 s ; Browser 3,8 s |
| Durée E2E, durée CI totale | **non mesurées** |
| Couverture lignes | **30,46 %** (7705/25298, pcov via `scripts/coverage-docker.sh`) ; branches non disponibles avec pcov. Controller 0 %, Service/Submission 6 %, Notification 1 %, Export 0,8 %, Scripting 2 %, Model 13 %, Rendering 79 % |
| Tests instables | 0 sur 1 exécution PHPUnit et 1 exécution Browser ; **stabilité sur N exécutions non testée** |

Classes PHPUnit les plus lentes : `PdfDocumentTest` 0,63 s, `BfpdfFacadeTest` 0,29 s, `QuickmodeSaveAuthorizationTest` 0,26 s, `SalesforceNotificationSecurityTest` 0,13 s, `FormsOptionsLabelTooltipsTest` 0,11 s. Rien d'autre ne dépasse 0,05 s : **la vitesse PHPUnit n'est pas un problème**.
Specs Browser les plus lentes : form-mail-template (0,77 s), 0,72 s, 0,64 s — aucune n'est préoccupante.

## 3. Problèmes détectés

1. **Tests « d'architecture » lisant le source** : 71 classes appellent `file_get_contents()` sur du PHP/templates du composant, ~1240 `assertStringContainsString` au total. Ils figent la forme du code (noms de méthodes, chaînes) plutôt que le comportement ; ils cassent au refactoring et passent si le code est présent mais inopérant. Les tests nommés `*ArchitectureTest` (`InstallerScriptArchitectureTest` 11 tests, `PackageTransferArchitectureTest` 10…) sont les principaux.
2. **Tests de caractérisation** (`*RendererCharacterizationTest`, 97 classes de rendu, 80 snapshots HTML) : utiles comme filet, mais sans assertion explicite sur le métier ; un snapshot régénéré à l'aveugle masque une régression.
3. **E2E avec `waitForTimeout`** : 11 occurrences (`forms.spec.mjs` ×7, `security.spec.mjs` ×4), attentes fixes 300-500 ms = défaut de synchronisation.
4. **Sélecteurs CSS dominants** : ~275 lignes `locator("css")` (E2E 166, Browser ~109) contre 9 `getByRole/getByLabel` ; fragiles aux changements de markup. Aucun `data-testid`.
5. **E2E sérialisés** (`workers: 1`, `describe.configure({mode:'serial'})` dans forms, security, mcp) : dépendance à l'ordre ; un échec en cascade masque les suivants. `legacy-conversion` et `security` refont `beforeAll` + login. Pas de `storageState` réutilisable.
6. **Pas de `retries`** (bon), mais **pas de `trace: on-first-retry`/screenshot** dans la config Browser (aucun diagnostic en cas d'échec CI) ; la config E2E garde la trace en échec (bon).
7. **Couverture non pilotée** : seuil global 30 % trop bas pour détecter une dérive ; rapport clover seulement sur PHP 8.3.
8. **Suite `unit` = tout `tests/`** : unitaires, snapshots, source-grep et intégration dans une seule suite, sans séparation ni groupe ; l'intégration est silencieusement skippée sans variable d'environnement (6 skips = 0 garantie en local).
9. Doublon probable : les protections CSRF/ACL sont testées en PHPUnit (`Security/MutationRequestRegressionTest`, `QuickmodeSaveAuthorizationTest`…) **et** dans `E2E/security.spec.mjs` (23). C'est légitime tant que l'E2E prouve l'intégration réelle (recommandation du guide : « casser chaque protection une fois ») — à ne pas fusionner.

## 4. Risques / lacunes de couverture critiques (voir matrice)

- Aucun test n'exécute le **flux de soumission frontend de bout en bout** (affichage → validation serveur → enregistrement → message) sur Joomla réel hors de `forms.spec` ; à vérifier en E2E non exécuté ici.
- **Téléversement de fichiers** : 8 classes Site/Upload, 2 specs Browser côté messages ; pas de scénario E2E frontend (type/taille/extension double, chemin).
- **Données personnelles** (suppression/export RGPD des enregistrements) : `RecordTransfer`/`records-transfer` couvrent l'export, pas explicitement l'anonymisation/suppression.
- **Installation/mise à jour** : `script.php` (180 Ko) couvert par tests de structure (`InstallerScriptArchitectureTest`) et deux scripts de régression hors PHPUnit ; la migration réelle (schéma précédent → courant, rejeu) n'est vérifiée qu'en CI par `joomla-install-smoke.sh` (non exécuté ici).
- **Mutation testing** : non installé (Infection absent de `composer.json`).

## 5. Difficultés de maintenance

Snapshots volumineux, tests couplés au code source, suite unique non segmentée, E2E sérialisés avec sélecteurs CSS, absence de mesure de couverture locale.

## Addendum (mesures complémentaires)
- **La CI n'exécutait jamais la suite d'intégration** : `BFNG_JOOMLA_LIBRARIES` n'y était pas défini, les 6 tests étaient ignorés en silence.
- **Le test E2E MCP était périmé** par rapport à la branche (cinq outils d'écriture ajoutés, le test exigeait quatre outils en lecture seule) : seul vrai échec de la baseline.
- La couverture de 30,46 % passe de justesse le seuil CI de 30 % : le seuil n'a aucune marge utile.
