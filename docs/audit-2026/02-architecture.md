# Audit 02 - Architecture et qualité de code

Périmètre : BreezingForms NG 6.1.0-RC22 (branche `main`, commit `1faa98d2`). Audit en lecture seule : aucun code applicatif modifié, aucune migration, aucune écriture Git.

Environnement de mesure : PHP hôte 8.5.4 (CLI), PHPStan et PHPUnit du dépôt (`vendor/bin`), conteneur `joomla6-joomla-1` consulté en lecture seule (Joomla 6.2.0, PHP 8.3) pour vérifier la source installée du CMS.

Principe suivi : aucune preuve, aucun constat. Les plans `docs/maintenance/php-architecture-migration-plan.md` et `performance-audit-2026-09.md` ont été vérifiés contre le code, pas recopiés.

## 1. Synthèse

L'architecture de base est saine : namespaces PSR-4, fournisseur de services Joomla 6 (`services/provider.php`), contrôleurs/modèles/vues MVC conventionnels côté administration, requêtes liées via `createQuery()`/`bind()`, aucune API Joomla dépréciée détectée, PHPStan niveau 8 sans erreur et baseline vide, 1 451 tests PHPUnit verts.

Les coûts réels se concentrent dans un seul endroit : les cinq « moteurs » du front (`Submission`, `Notification`, `Export`, `Rendering`, `Scripting`) sont du code procédural historique déplacé dans des classes, adossé à un objet processeur global (`HTML_facileFormsProcessor`, 58 propriétés publiques, 876 accès `$this->processor->`). Ils contiennent des méthodes de 500 à 1 078 lignes, une duplication massive démontrée (~80 % entre deux renderers, ~75 % entre deux méthodes de mails), et ne sont couverts que par des tests qui lisent le source en texte plutôt que par des tests de comportement.

Le reste (service provider, MVC admin, API Joomla, PHP 8.4/8.5, index SQL, intégration ContentBuilder NG par détection) ne présente rien de significatif.

## 2. Cartographie des composants et dépendances

| Zone | Contenu | PHP (hors vendor) |
|---|---|---|
| `administrator/components/com_breezingformsng` | `src` (Controller, Model, View, Service, Mcp, Helper, Field, Table, Extension), `services/provider.php`, `tmpl`, `layouts`, `libraries/crosstec` (helpers + 3 façades de 23 lignes), `plugins` (bfcompat, breezingforms, privacy, webservices), `modules/mod_breezingforms`, `mailtpl`, `pdftpl` | 203 fichiers |
| `components/com_breezingformsng` | `breezingformsng.php` (front controller), `src` (Service, Support, Table, Controller, Configuration, Event, View) | 219 fichiers |
| `api/components/com_breezingformsng` | `AuditController`, `McpController` | 2 fichiers |
| `media/` | JS/CSS/images uniquement (aucun PHP) ; plus gros JS : `quickmode-app-properties.js` 2 364 lignes | 0 |
| `script.php` | script d'installation, une seule classe | 4 466 lignes, 96 méthodes |
| `vendor` (non versionné) | 186 Mo sur disque (dont `tc-lib-pdf-font` 160 Mo), ZIP RC21 : 10,1 Mo | - |

Flux de dépendance principal (front) :

```
breezingformsng.php (require_once helpers/BFQuickMode*, globals $ff_*)
  -> EngineDispatcher (injecté par le provider, closures de fabrique)
     -> FormRenderer -> HTML_facileFormsProcessor (processor_facade.php, objet global)
          -> RenderingEngine / ScriptingEngine / SubmissionEngine / NotificationEngine / ExportEngine
             (chacun reçoit le processeur et lit/écrit son état)
          -> ContentBuilder NG (use CB\Component\Contentbuilderng\... dans 5 moteurs + ContentBuilderFormSync)
  -> Callbacks (PayPal, Stripe, Captcha, FlashUpload, Opt) : services injectés par le provider, propres
```

Couplage inter-extensions : les callbacks et services récents (`Callback/*`, `Upload/*`, `Security/*`, `Integration/*`) sont découplés et injectés ; les cinq moteurs et le processeur restent le noyau couplé. L'administration ne dépend du site que via les namespaces `Site\...` déclarés dans le manifeste.

## 3. Métriques

Fichiers les plus volumineux (hors tmpl/layouts) et méthodes les plus longues (mesure par tokenisation PHP) :

| Fichier | Lignes | Méthodes | Plus longues méthodes |
|---|---|---|---|
| `components/.../Service/Submission/SubmissionEngine.php` | 2 326 | 21 | `submit` 1 078 l. (L1078), `collectSubmitdata` 860 l. (L207) |
| `administrator/.../Model/PackageTransferModel.php` | 2 288 | 64 | `importForms` 127 l. |
| `administrator/.../Mcp/McpFormWriter.php` | 1 494 | 23 | `manageFields` 539 l. (L198) |
| `components/.../QuickMode/ClassicRenderer.php` | 1 470 | 42 | `process` 446 l. (L351) |
| `components/.../Notification/NotificationEngine.php` | 1 405 | 11 | `sendMailbackNotification` 560 l. (L711), `sendEmailNotification` 554 l. (L65) |
| `components/.../Export/ExportEngine.php` | 1 173 | 11 | `logToDatabase` 584 l. (L78) |
| `components/.../QuickMode/OnePageRenderer.php` | 1 228 | 27 | `process` 689 l. (L429) |
| `components/.../QuickMode/BootstrapRenderer.php` | 1 157 | 27 | `process` 679 l. (L362) |
| `components/.../Support/processor_facade.php` | 1 164 | 66 | façade (délégation) |
| `components/.../Rendering/RenderingEngine.php` | 1 022 | 57 | `view` 309 l. (L490) |
| `components/.../Scripting/ScriptingEngine.php` | 967 | 17 | `loadBuiltins` 433 l. (L397) |
| `script.php` | 4 466 | 96 | `migrateDatabase` 326 l., `removeObsoleteComponentFiles` 221 l., `deduplicateBreezingFormsComponentRows` 171 l. |

Autres mesures :

- Complexité grossière de `SubmissionEngine::submit` : environ 148 points de décision (`if/foreach/while/case/catch`), dont 54 lignes de HTML/JS inline.
- Qualité statique : PHPStan niveau 8, `phpVersion` 8.3-8.5, 0 erreur, baseline vide (exécuté, PHP 8.5.4). `php -l` sur tout le code livré (admin, site, api, script.php) : 0 erreur ni dépréciation de compilation (PHP 8.5.4).
- Tests : PHPUnit 12.5.42, 1 451 tests, 9 125 assertions, 6 ignorés, 1 avertissement non détaillé (exécuté sur PHP 8.5.4, sans plugin de compatibilité). 264 fichiers de test, dont 97 lisent le source en texte (1 607 assertions `assertStringContains*`/`assertMatchesRegularExpression`).
- API dépréciées : 0 occurrence de `Factory::getDbo/getUser/getSession/getLanguage/getDocument/getConfig/getMailer`, de `JFactory/JText/JHtml/JRoute/JUri/JInput`, de `Table::getInstance`, de `strftime`, `FILTER_SANITIZE_STRING`, `utf8_encode`. `Factory::getApplication()` : 63 occurrences dans 41 fichiers (non déprécié en 6.2).
- `strict_types` absent de 148 fichiers sur 342 (`src`, `api`, `script.php`).

## 4. Constats

### ARC-001 - `SubmissionEngine::submit()` et `collectSubmitdata()` : méthodes monolithiques non testables unitairement

- Catégorie : responsabilités / testabilité
- Impact : élevé. Confiance : élevée.
- Preuves : `components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php:1078` (`submit`, 1 078 lignes, ~148 points de décision) et `:207` (`collectSubmitdata`, 860 lignes). `submit` mêle : bloc ContentBuilder (`// CONTENTBUILDER BEGIN`), pièces Begin/End Submit, validation de montant, envoi de mails, notifications, et actions de paiement PayPal/Stripe avec HTML/JS inline (54 lignes de balisage) et blocs de code commenté (~79 lignes de commentaires ressemblant à du code, mesure heuristique). Le seul test dédié est `tests/Site/Service/Submission/SubmissionEngineArchitectureTest.php`, qui fait des `assertStringContainsString` sur le texte du fichier (lignes 24-60 par exemple) ; aucun test ne construit `SubmissionEngine` ni n'exécute `submit()`.
- Conséquences : tout changement dans le parcours de soumission (cœur métier et paiement) ne peut être vérifié que par E2E manuel ou Playwright ; le coût de régression est porté par la relecture. Les assertions textuelles cassent sur un simple renommage et ne détectent pas une régression de comportement.
- Recommandation : extraire par étapes la partie paiement de `submit()` (bloc « built in PayPal action », ~L1540 et suivants) vers un service dédié recevant le processeur, en gardant `submit()` comme orchestrateur. Écrire d'abord des tests de caractérisation (sortie HTML des branches Stripe/PayPal, statuts de retour) avant déplacement mécanique, comme le plan de migration le prescrit déjà pour `RenderingEngine::view()`. Ne pas toucher à la façade publique `HTML_facileFormsProcessor` (contrainte du plan : PHP stocké en base).
- Effort : élevé (lot par lot). Risque de régression : moyen à élevé sans caractérisation préalable, faible avec.
- Tests nécessaires : caractérisation de la sortie par méthode de paiement ; statuts `_FF_STATUS_*` ; cas montant refusé (aucun enregistrement créé).

### ARC-002 - Duplication démontrée entre `BootstrapRenderer::process()` et `OnePageRenderer::process()`

- Catégorie : duplication
- Impact : moyen. Confiance : élevée.
- Preuves : `components/.../QuickMode/BootstrapRenderer.php:362-1040` (679 lignes) et `OnePageRenderer.php:429-1117` (689 lignes). Comparaison ligne à ligne par `diff` : 140 lignes sur 679 diffèrent (environ 79 % identiques) ; par lignes uniques, 354 sur 378 sont communes (94 %). `ClassicRenderer::process` (L351, 446 lignes) partage 160 lignes uniques sur 244 avec OnePage. Le plan de migration (`docs/maintenance/php-architecture-migration-plan.md`, « Objectifs ») cite pourtant la suppression de cette duplication ; les phases 25 et 30 n'ont mutualisé que l'hydratation et de petits builders.
- Conséquences : toute correction de rendu (accessibilité, échappement, option de champ) doit être répliquée à la main dans 2 à 3 fichiers de 1 100 à 1 500 lignes ; le risque de divergence silencieuse entre modes est mesurable (les 140 lignes de différence ne sont pas documentées comme intentionnelles).
- Recommandation : factoriser le squelette commun de `process()` (parcours des nœuds, validation, hydratation) dans une classe ou un trait, en laissant à chaque renderer uniquement les fabriques de balisage divergentes. Prérequis : les snapshots existants `tests/Site/Service/Rendering/QuickMode/` (Classic, Bootstrap, Mobile, OnePage) doivent rester inchangés octet pour octet.
- Effort : moyen à élevé. Risque de régression : moyen (mitigé par les snapshots). 
- Tests nécessaires : snapshots existants sur tous les modes avant/après ; ajout de cas pour les 140 lignes divergentes afin de documenter ce qui est voulu.

### ARC-003 - `NotificationEngine` : deux méthodes de ~555 lignes à 75 % identiques

- Catégorie : duplication / responsabilités
- Impact : moyen. Confiance : élevée.
- Preuves : `components/.../Notification/NotificationEngine.php:65` (`sendEmailNotification`, 554 lignes) et `:711` (`sendMailbackNotification`, 560 lignes). Sur les lignes uniques non vides, 208 sur 275 (méthode 1) apparaissent aussi dans la méthode 2. Le même fichier contient 26 lignes de commentaires ressemblant à du code mort (bloc `$customSender` aux alentours de L100).
- Conséquences : une correction de sécurité ou de format de mail (échappement, pièces jointes, destinataires `sender:`) doit être appliquée deux fois ; les tests n'exercent que l'architecture textuelle (`NotificationEngineArchitectureTest`) et un cas Salesforce, pas la construction des mails.
- Recommandation : extraire la construction du corps/des pièces jointes/des destinataires dans un service pur (entrées : lignes de formulaire et données soumises ; sortie : structure de message), consommé par les deux méthodes. Tests de caractérisation par `Mail` factice (le projet utilise déjà `MailerFactoryInterface`).
- Effort : moyen. Risque de régression : moyen (formats de mails historiques). 
- Tests nécessaires : snapshots du corps HTML/texte, des destinataires et des pièces jointes pour les deux notifications.

### ARC-004 - Responsabilité mal placée : `ExportEngine::logToDatabase()` enregistre les soumissions et pilote ContentBuilder

- Catégorie : responsabilités / nommage
- Impact : moyen. Confiance : élevée.
- Preuves : `components/.../Export/ExportEngine.php:78` (584 lignes). La méthode est nommée « export » mais : supprime les enregistrements existants d'un utilisateur en mode éditable (L96-L125, boucle de `DELETE` une requête par enregistrement), crée/met à jour `#__contentbuilderng_records` (L158-L200), stocke l'enregistrement via `RecordTable`, enregistre les sous-enregistrements (`SubrecordTable::store()` une fois par champ, L329-L340), crée des articles ContentBuilder via `ArticleService` (L618-L630), purge les caches `com_content`/`com_contentbuilderng` et distribue `onAfterSubmit`. Elle contient aussi des requêtes `SQL_CALC_FOUND_ROWS *` (L226, L414) dont le `FOUND_ROWS()` n'est jamais lu (aucune occurrence de `FOUND_ROWS` ailleurs) ; ce mécanisme est déprécié depuis MySQL 8.0.17.
- Conséquences : la persistance d'une soumission n'est pas atteignable sans charger le moteur d'export ; le nom induit en erreur lors d'un audit ou d'une évolution ; la persistance et l'intégration CB sont testées uniquement par smoke Joomla.
- Recommandation : déplacer `logToDatabase` dans un service de persistance de soumission (par exemple `SubmissionStore`), conserver `HTML_facileFormsProcessor::logToDatabase` (façade gelée) comme délégation, isoler le bloc ContentBuilder derrière `ContentBuilderInstallation::isInstalled()` dans une classe dédiée (le motif existe déjà : `ContentBuilderRecordCleanup`). Supprimer `SQL_CALC_FOUND_ROWS` des deux requêtes s'il est confirmé que `FOUND_ROWS()` n'est pas lu par du PHP stocké (cas improbable car la requête est interne).
- Effort : moyen. Risque de régression : moyen (ordre des opérations et événements). 
- Tests nécessaires : test d'intégration Joomla (insertion record + sous-records), cas éditable (suppression préalable), cas CB installé/absent.

### ARC-005 - Les moteurs dépendent d'un objet processeur global et d'états globaux

- Catégorie : couplage / testabilité
- Impact : moyen. Confiance : élevée.
- Preuves : 876 accès `$this->processor->` dans les cinq moteurs (Submission 332, Notification 188, Export 145, Rendering 129, Scripting 82) ; `HTML_facileFormsProcessor` expose 58 propriétés publiques (`components/.../Support/processor_facade.php:225-356`) ; 26 occurrences de `global $ff_...` dans 9 fichiers (dont `$ff_config`, `$ff_request`) ; `breezingformsng.php:~70-85` déclare 11 variables globales. Les méthodes des moteurs n'ont pas de visibilité ni de types (21 méthodes `function foo()` sans modificateur dans `Service/*/*Engine.php`).
- Conséquences : un moteur ne peut pas être instancié ni testé sans fabriquer le processeur entier ; l'ordre d'initialisation est implicite. C'est la cause racine de ARC-001, ARC-003 et du recours aux tests textuels (ARC-007). Le plan de migration gèle délibérément la façade publique, ce qui est justifié (PHP stocké en base).
- Recommandation : ne pas réécrire. Continuer le schéma déjà appliqué aux callbacks (services à dépendances explicites, injectés par le provider) en y faisant glisser les méthodes extraites, la façade restant une simple délégation. Aucune réécriture globale du processeur.
- Effort : continu. Risque de régression : faible par lot extrait et caractérisé.
- Tests nécessaires : tests unitaires des services extraits avec dépendances factices.

### ARC-006 - `script.php` : installateur monolithique de 4 466 lignes, dont une migration de données non atomique

- Catégorie : installateur / migrations
- Impact : moyen. Confiance : moyenne (risque démontré sur lecture, non reproduit).
- Preuves : `script.php:48` (`com_breezingformsngInstallerScript`, 96 méthodes) ; `migrateDatabase` 326 lignes (L3698). `convertRecordModifiedDatesToUtc()` (`script.php:3661-3696`) charge toutes les lignes `#__facileforms_records.modified` puis exécute un `UPDATE` par ligne, sans transaction, et ne s'exécute que si `schemaVersionBeforeUpdate < 6.1.2-2026-10-23` (valeur lue en préflight, L4315). Joomla enregistre la nouvelle version de schéma avant le post-flight : si le script échoue en cours de boucle, une relance voit déjà un schéma >= 6.1.2 et saute la conversion, laissant une partie des dates convertie et l'autre non ; une relance ne peut pas non plus distinguer une ligne déjà convertie. Le chemin n'est pas vérifié par les tests : aucun test ne référence `convertRecordModifiedDatesToUtc` (grep dans `tests/`). Par ailleurs `script.php:156-175` vérifie `version_compare(PHP_VERSION, '8.3')` et `JVERSION`, alors que `AGENTS.md` interdit les contrôles de version d'exécution (doublon de `php_minimum` du manifeste de mise à jour).
- Conséquences : en cas d'échec partiel sur une grosse table (mise à jour depuis une version <= RC07), dates de modification incohérentes sans moyen de reprise automatique ; la méthode illustre que l'installateur accumule des migrations ponctuelles dans une seule classe.
- Recommandation : entourer la conversion d'une transaction (`transactionStart/Commit/Rollback`, comme `PackageTransferModel` le fait déjà) ou d'un `UPDATE ... SET modified = CONVERT_TZ(...)` unique ; ajouter un test sur base jetable (idempotence : double passage, échec simulé). Séparer à terme les migrations de données (classe dédiée par version) du script d'installation, sans en changer le comportement. Valider la présence du contrôle de version avec Gilles (conflit `AGENTS.md`).
- Effort : faible (transaction + test) ; moyen (découpage). Risque de régression : faible. 
- Tests nécessaires : mise à jour depuis schéma < 6.1.2 sur copie de base ; relance après échec simulé ; fraîche installation.

### ARC-007 - Les tests « d'architecture » lisent le code source en texte

- Catégorie : testabilité
- Impact : moyen. Confiance : élevée.
- Preuves : 97 des 264 fichiers de test appellent `file_get_contents` ; 1 607 assertions textuelles contre 1 095 `assertSame/assertEquals` (grep sur `tests/`). Exemples : `SubmissionEngineArchitectureTest.php:24-60` (vérifie la présence de `$this->processor->sfdata[] = array(` et `$mailer->isHtml(true);`), `NotificationEngineArchitectureTest.php`. Ces tests passent tant que la chaîne existe, même si le comportement est cassé, et échouent sur un refactoring neutre.
- Conséquences : couverture fonctionnelle trompeuse des moteurs ; le refactoring de ARC-001/003/004 déclenchera des échecs sans signal utile.
- Recommandation : pour chaque service extrait, remplacer l'assertion textuelle par un test de comportement avec dépendances factices (les tests QuickMode et `Callback/*` montrent déjà le modèle). Ne pas supprimer les tests textuels existants avant qu'un test de comportement équivalent existe.
- Effort : continu. Risque : faible.
- Tests nécessaires : voir ARC-001 à ARC-004.

### ARC-008 - Navigation d'enregistrement : `getNavigation()` charge encore tous les identifiants (PERF-001 non corrigé, documentation obsolète)

- Catégorie : performance / documentation
- Impact : faible à moyen (non mesuré). Confiance : élevée sur le comportement, faible sur l'ampleur.
- Preuves : `administrator/components/com_breezingformsng/src/Model/RecordsModel.php:94-113` : `loadColumn()` de tous les identifiants du jeu filtré puis `array_search()`, appelé à chaque affichage de la vue d'enregistrement (`View/Record/HtmlView.php:72`). `performance-audit-2026-09.md` (PERF-001) et le plan de migration (ligne 135) citent `getAdjacentRecordId()`, qui n'existe plus : le plan indique ainsi un état « modernisé » qui est en fait la même complexité O(n). La base de production n'étant pas disponible, aucun temps n'est mesuré ; au-delà de quelques dizaines de milliers d'enregistrements, le coût est mémoire PHP O(n) à chaque page.
- Recommandation : ne rien changer tant qu'une mesure sur une base représentative ne confirme pas le coût ; à ce moment-là, calculer précédent/suivant/position par trois requêtes avec `LIMIT 1` sur le même `ORDER BY` (la méthode `getIdAt()` voisine utilise déjà ce motif). Mettre à jour les deux documents pour citer `getNavigation()`.
- Effort : faible. Risque de régression : moyen (tris avec `NULL`, égalités, filtres). 
- Tests nécessaires : tous les tris autorisés (`SELF::SORTS`), direction, filtres, première/dernière position.

### ARC-009 - Dérive de la documentation d'architecture par rapport au code

- Catégorie : documentation de maintenance
- Impact : faible. Confiance : élevée.
- Preuves : `php-architecture-migration-plan.md:134` indique « PHPStan niveau 4 » alors que `phpstan.neon.dist` configure le niveau 8 ; la ligne 135 cite une méthode supprimée (voir ARC-008) ; `docs/best-practices/README.md` et `php83-best-practices.md` ciblent PHP 8.3 alors qu'`AGENTS.md` fixe 8.4 avec compatibilité 8.5, et `phpstan.neon.dist` garde `phpVersion.min: 80300`. Le plan décrit `RenderingEngine::view()` comme réduit à « branchement et arrêt `bury()` » alors que la méthode fait encore 309 lignes (`RenderingEngine.php:490`).
- Conséquences : un agent ou un développeur qui s'appuie sur ces documents prend des décisions sur un état erroné.
- Recommandation : corriger les trois points ci-dessus lors de la prochaine mise à jour du plan. Aucune action de code.
- Effort : très faible. Risque : nul.

### ARC-010 - Détails mineurs avec coût démontré faible

- `components/com_breezingformsng/breezingformsng.php:22-35` : `bf_b64enc/bf_b64dec` appellent `call_user_func('base' . '64_encode')` (chaîne coupée). Aucun gain fonctionnel, nuit à la lecture et aux outils d'analyse ; `bf_b64dec` est utilisé par le runtime (ex. `SubmissionEngine.php`, `$head = json_decode(bf_b64dec(...))`). À conserver tant que du PHP stocké peut les appeler (même contrainte que la façade), mais remplaçable par un appel direct à l'intérieur de leur corps sans changer le contrat public.
- `sql/install.mysql.utf8.sql:~330` (table `subrecords`) : l'index `record` est redondant avec `record_2 (record, element)` et `record_3 (record, name)`. Coût : légère surcharge d'écriture et d'espace. Ne rien faire sans mesure (suppression d'index = changement de schéma à migrer).
- `provider.php:14-26` et `script.php:1793-1802` enregistrent des espaces de noms via `JLoader::registerNamespace` « tant que Joomla ne les connaît pas » ; ce comportement est volontairement documenté (installation initiale) mais contredit la règle « pas de fallback » d'`AGENTS.md`. À valider avec Gilles ; supprimer sans test d'installation sur base vierge casserait l'installation (`extension:install` en CLI).

## 5. Refactorings utiles et réécritures à éviter

Refactorings utiles (chacun a un coût mesuré ci-dessus) :

1. Extraire la persistance de soumission et le bloc ContentBuilder hors de `ExportEngine::logToDatabase` (ARC-004), avec caractérisation.
2. Factoriser `process()` des renderers Bootstrap/OnePage/Classic (ARC-002) sous la protection des snapshots existants.
3. Mutualiser la construction des mails (ARC-003).
4. Découper `submit()` en commençant par le bloc paiement (ARC-001).
5. Transaction + test d'idempotence pour `convertRecordModifiedDatesToUtc` (ARC-006).

Réécritures à éviter (justifié) :

- Réécrire `HTML_facileFormsProcessor` ou supprimer sa façade : du PHP stocké en base (pièces, scripts, règles d'intégration) et des intégrations externes l'appellent ; l'absence d'appelant dans le dépôt n'est pas une preuve (principe déjà posé dans le plan). Extraire derrière la façade uniquement.
- Réécrire les quatre renderers QuickMode « proprement » : la sortie HTML/JS est le contrat ; des snapshots verrouillent l'existant. Factoriser, ne pas réécrire.
- Supprimer `eval` de `StoredPhpExecutor` (`components/.../Scripting/StoredPhpExecutor.php:34,51,70,93`) et `PieceTestRunner` (`administrator/.../Service/PieceTestRunner.php:66,122,248`) : c'est la fonctionnalité du produit (PHP stocké dans les formulaires). Les points d'entrée sont isolés dans deux classes ; leur sécurité relève de l'audit sécurité, pas de l'architecture.
- Retirer `strict_types` manquant en bloc (148 fichiers) : risque d'effet sur le PHP stocké et les appels historiques ; à ne traiter que fichier par fichier lorsqu'un service est extrait et couvert.
- Rendre la couche `plugins/bfcompat` « moderne » : `BFRequest.php` utilise 45 superglobales (`$_GET`, `$_POST`...) mais c'est une couche de compatibilité explicite, hors du chemin du composant et exclue des tests selon `AGENTS.md`.

## 6. Domaines sans problème significatif

- Service provider / DI : `services/provider.php` enregistre le MVC factory, le dispatcher, le routeur et les services de paiement/upload avec dépendances explicites ; `Factory::getApplication()` est résolu paresseusement pour permettre le démarrage en console (commentaire L65-69, motivé).
- MVC administrateur : une vingtaine de contrôleurs et de modèles, vues par dossier, séparation correcte. Les rares accès directs à la base en contrôleur/template (`FormsController.php:446`, `RecordstransferController.php:220`, `tmpl/about/default.php:70`) sont des lectures liées d'une ligne ou d'un jeton, sans coût démontré ; seul le gabarit `about/default.php` (définit une fonction et interroge `#__extensions`) est discutable mais isolé à un écran.
- API Joomla 6 : aucune API dépréciée constatée sur la liste vérifiée (section 3). `Editor::getInstance('codemirror')` (`QuickmodeHtml.php:500`, `View/Quickmode/HtmlView.php:133`) est utilisable en 6.2.0 : la dépréciation « 7.0 » ne se déclenche que pour un éditeur absent de `EditorsRegistry`, ce qui n'est pas le cas de CodeMirror.
- SQL : toutes les requêtes relevées utilisent `createQuery()`, `quoteName()` et `bind()` ; aucune concaténation de valeur utilisateur détectée dans les fichiers lus. `ORDER BY` construit par liste blanche (`RecordsModel::SORTS`).
- PHP 8.4/8.5 : `php -l` propre sur tout le code livré avec PHP 8.5.4, PHPStan niveau 8 propre, tests verts. Aucune interpolation `${}` PHP, `each`, `create_function`.
- Performance des exports : le N+1 des sous-enregistrements est déjà traité par lots (`RecordExporter`, `getSubrecordsByRecordIds`) ; le cache local d'`IntegratorRuntime` est en place. Les insertions de sous-enregistrements par champ à la soumission (`ExportEngine.php:329-340`) sont proportionnelles au nombre de champs du formulaire ; aucun coût mesuré, donc pas de constat.
- Index : `records` et `subrecords` portent des index utiles (`form`, `user_id`, `state`, `record_2`, `record_3`). Aucun index composite proposé sans `EXPLAIN` sur base représentative (cohérent avec PERF-009).
- Intégration ContentBuilder NG : les classes CB sont importées par `use` mais n'entraînent aucun chargement si CB est absent (chargement paresseux) ; les chemins sont gardés par `ContentBuilderInstallation::isInstalled()`. Le couplage est large (6 fichiers, 21 requêtes sur `#__contentbuilderng_*`) mais assumé ; le coût principal est traité par ARC-004.
- Modules/plugins : fournisseurs de services et événements natifs Joomla 6 pour `breezingforms`, `privacy`, `webservices`, `mod_breezingforms`.
- Taille de livraison : `vendor` pèse 186 Mo sur disque mais le ZIP RC21 fait 10,1 Mo ; pas de constat.

## 7. Limites de l'audit

- Pas de mesure sur base de production ni de profilage HTTP : les niveaux d'impact de performance reposent sur la lecture du code.
- Les pourcentages de duplication sont calculés sur les lignes de code normalisées (espaces retirés, commentaires exclus pour les renderers) ; ils indiquent un ordre de grandeur, pas une équivalence exacte.
- Le dossier `administrator/components/com_breezingformsng/vendor` (non versionné) et les bibliothèques tierces n'ont pas été audités.
- Le test PHPUnit n'a pas pu être détaillé pour son avertissement unique (aucun intitulé affiché dans la sortie condensée).
