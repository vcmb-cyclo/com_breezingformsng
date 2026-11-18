# TEST_OPTIMIZATION_PLAN

Constat : PHPUnit (5,6 s), Node et Browser (< 5 s) sont déjà rapides. **Le gain n'est pas la vitesse locale mais la fiabilité du signal, le diagnostic et les lacunes.** Aucun test n'est supprimé dans ce plan.

| # | Action | Impact | Effort | Risque | Vérification |
|---|---|---|---|---|---|
| 1 | Installer pcov localement et publier la couverture lignes/branches par répertoire (`phpunit --coverage-text`) ; passer le seuil CI de 30 % à la valeur mesurée moins une marge, puis cliquet | Élevé | Faible | Faible | Chiffre baseline réel |
| 2 | Playwright Browser : ajouter `trace: 'on-first-retry'` seulement si retries, sinon `trace: 'retain-on-failure'`, `screenshot: 'only-on-failure'`, `video: 'off'` ; `retries: 0` conservé | Moyen (diagnostic CI) | Très faible | Nul | `npx playwright test` |
| 3 | Remplacer les 11 `waitForTimeout` des E2E (`forms.spec`, `security.spec`) par des assertions auto-attendues (`expect(locator).toBeVisible()`/`toHaveValue`, `waitForResponse`) | Élevé (flakiness) | Faible | Moyen : vérifier sur Joomla jetable | `scripts/joomla-e2e.sh` — **non exécutable sans ZIP + Docker, à faire par lot validé** |
| 4 | Fixture Playwright `storageState` admin partagée (setup project) pour `legacy-conversion`, `security`, `forms` | Moyen (durée E2E) | Moyen | Moyen : isolation des rôles de `security.spec` | Mesurer avant/après la durée E2E |
| 5 | Migrer les sélecteurs CSS des E2E vers `getByRole/getByLabel` en touchant d'abord les specs les plus verbeuses (`forms`, `package-upload`, `records-transfer`) | Moyen | Moyen | Faible | Mêmes scénarios verts |
| 6 | Découper la suite PHPUnit en `unit`, `characterization`, `source-contract`, `integration` (groupes ou `testsuite`), et faire échouer en CI si `Integration` est skippée (variable `BFNG_JOOMLA_LIBRARIES` fournie) | Élevé | Faible | Faible | 1328 tests toujours exécutés, 6 intégration non skippés en CI |
| 7 | Remplacer progressivement les tests `*ArchitectureTest` par des tests comportementaux quand une API publique existe (ex. `PackageTransferModel`) ; garder ceux qui protègent une règle de sécurité sans autre moyen | Élevé (maintenabilité) | Élevé | Moyen | Pour chaque suppression : test comportemental équivalent vert ET cassé à dessein une fois |
| 8 | Politique snapshots : chaque mise à jour de snapshot exige un diff relu ; ajouter dans les tests de rendu 1-2 assertions explicites par champ critique (échappement, `required`, `name`) | Moyen | Moyen | Faible | |
| 9 | Infection (mutation) ciblé : `Service/Submission/StripeAmount`, `PayPalPayment`, `Mcp/OriginValidator`, `CsvCellSanitizer`, `Upload` (classes pures, rapides). Dépendance dev uniquement, hors CI par défaut | Élevé sur cibles | Moyen | Faible (dev-dépendance, accord requis par `testing-quality.md`) | MSI par classe, tests ajoutés pour les mutants survivants |
| 10 | Combler les lacunes de la matrice (frontend submit, upload, données personnelles, migration) | Élevé | Élevé | Faible | Voir matrice |

## Ordre recommandé
1 → 2 → 6 (mesure et signal) ; 3 → 4 → 5 (E2E, par lots mesurés sur Joomla jetable) ; 9 → 10 (mutation puis lacunes) ; 7 et 8 en continu.

## Gains attendus (estimations, non mesurées)
Moins de faux négatifs/positifs E2E, diagnostic CI exploitable, couverture pilotée. La durée PHPUnit ne bouge pas ; la durée E2E peut baisser avec `storageState` (à mesurer).

## Points de vigilance
Ne pas toucher aux scénarios de sécurité de `security.spec.mjs` sans les « casser une fois » ; jamais de retries pour masquer une attente ; aucun changement du code applicatif n'est nécessaire à ce stade.
