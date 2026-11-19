# Audit de performance — BreezingFormsNG Joomla 6

Date : 2026-09-01  
Périmètre : Joomla 6, PHP 8.3+, code source de l’extension uniquement.

## Méthode et limites

L’audit a couvert les modèles, contrôleurs, services SQL, rendu frontend, assets, intégration, import CSV et exports PDF/CSV/XML. La base de production et une requête HTTP instrumentée n’étaient pas disponibles ; les niveaux d’impact sont donc fondés sur le code et les complexités observables, et non sur des temps de production.

Les corrections implémentées sont limitées à des changements locaux à faible risque. Aucun index n’est ajouté sans `EXPLAIN` sur une base représentative.

## PERF-001 — Navigation d’enregistrement en O(n) côté PHP

**Sévérité :** HIGH

**Localisation :**

`administrator/components/com_breezingformsng/src/Model/RecordsModel.php:L76-L103`

**Code concerné :**

Statut : cette méthode a été remplacée par `RecordsModel::getNavigation()`, qui n'exécute plus que des requêtes bornées (valeur de tri courante, comptes et deux voisins `LIMIT 1`). Description initiale : `getAdjacentRecordId()` sélectionne tous les identifiants du jeu filtré, les convertit en tableau PHP, puis utilise `array_search()`. La vue d’édition l’appelle deux fois, pour le précédent et le suivant (`administrator/components/com_breezingformsng/src/View/Records/HtmlView.php:L196-L211`).

**Problème :**

La navigation charge le résultat complet alors qu’un seul identifiant voisin est nécessaire.

**Pourquoi c’est coûteux :**

Le coût PHP et mémoire est O(n) par appel, avec deux appels par page d’édition. La base doit aussi produire et trier l’ensemble des identifiants avant leur transfert au processus PHP.

**Conditions d’apparition :**

Visible à partir de 1 000 enregistrements filtrés ; potentiellement très coûteux à 10 000 et plus, notamment avec une recherche ou un tri non indexé.

**Impact :**

* SQL : deux requêtes retournant n identifiants au lieu d’un résultat borné ; tri possible sur un grand ensemble ;
* CPU : recherche linéaire PHP et conversion de tous les identifiants ;
* mémoire : O(n) par tableau, avec deux appels successifs ;
* temps de réponse : augmentation proportionnelle au volume filtré à chaque édition ;
* scalabilité : mauvaise.

**Correction proposée :**

Remplacer la lecture complète par une requête de voisinage côté SQL, en conservant le même prédicat de filtre et le même ordre stable (`colonne de tri`, puis `records.id`). Une implémentation doit traiter explicitement les valeurs `NULL` de `records.modified` et rester compatible avec les moteurs DB supportés avant d’être activée.

**Gain attendu :** important à très important sur les listes volumineuses.

**Risque de régression :** MEDIUM

**Validation :**

Comparer le nombre de lignes transférées, le temps SQL et la mémoire avec 100, 1 000, 10 000 et 100 000 lignes, pour chaque tri autorisé et pour les cas début/milieu/fin de liste. Vérifier que le voisin retourné est identique à la liste affichée.

## PERF-002 — Exports complets construits en mémoire

**Sévérité :** CRITICAL

**Localisation :**

`administrator/components/com_breezingformsng/src/Controller/RecordsController.php:L531-L546`, `L286-L380`, `L397-L479`

**Code concerné :**

`fetchRecords()` utilise `SELECT *` et `loadObjectList()` sans limite lorsque l’export porte sur un formulaire ou sur tous les enregistrements. CSV et XML concatènent ensuite le résultat complet dans `$body` ou `$xml`. Le PDF capture également le template complet avec `ob_start()` avant de le transmettre au générateur PDF (`L216-L252`).

**Problème :**

Le volume exporté n’est pas borné en mémoire et n’est pas streamé vers un fichier ou la réponse HTTP.

**Pourquoi c’est coûteux :**

Les enregistrements, les chaînes de sortie, les valeurs de sous-enregistrements et les structures intermédiaires coexistent. Les concaténations répétées peuvent provoquer plusieurs allocations de chaînes. Le PDF ajoute le coût du moteur PDF et du buffer HTML.

**Conditions d’apparition :**

Risque sérieux à 10 000 lignes avec des valeurs longues ; risque de dépassement mémoire, timeout ou paquet HTTP trop important à 100 000 lignes.

**Impact :**

* SQL : lecture complète de la sélection ;
* CPU : formatage et concaténations proportionnels au volume ;
* mémoire : O(n + taille des valeurs + taille du fichier), avec plusieurs copies possibles ;
* temps de réponse : réponse retardée jusqu’à la construction complète ;
* scalabilité : insuffisante pour les gros exports.

**Correction proposée :**

Introduire un export par flux : lecture par lots avec un ordre stable, écriture progressive dans un fichier temporaire ou un writer adapté, puis envoi de ce fichier. Pour CSV, utiliser `fputcsv()` ; pour XML, un writer XML ou des écritures échappées par fragment. Le PDF doit rester borné ou être explicitement limité à une taille documentée. Le marquage `exported` doit rester transactionnellement cohérent avec les lignes effectivement exportées.

**Gain attendu :** très important sur les gros volumes ; réduction du pic mémoire et meilleure tolérance aux exports longs.

**Risque de régression :** HIGH

**Validation :**

Comparer mémoire courante/maximale, durée, taille et checksum logique de sortie sur 100, 1 000, 10 000 et 100 000 lignes. Vérifier les templates PDF personnalisés, l’ordre, l’encodage CSV/XML et le nombre de lignes marquées exportées.

## PERF-003 — N+1 des sous-enregistrements dans CSV/XML

**Sévérité :** HIGH

**Localisation :**

`administrator/components/com_breezingformsng/src/Model/RecordModel.php:L503-L548`, `administrator/components/com_breezingformsng/src/Controller/RecordsController.php:L324-L368`, `L422-L474`

**Code concerné :**

Avant correction, chaque ligne CSV/XML appelait `getSubrecords($recordId)`, soit une requête SQL par enregistrement. Le modèle expose désormais `getSubrecordsByRecordIds()` et les contrôleurs chargent les sous-enregistrements par lots de 200.

**Problème :**

Le parcours export exécutait un accès SQL répétitif dans la boucle principale.

**Pourquoi c’est coûteux :**

Pour n enregistrements, le nombre de requêtes passait de quelques requêtes fixes à n requêtes de sous-enregistrements, avec latence réseau et préparation SQL répétées.

**Conditions d’apparition :**

Dégradation nette dès 100–1 000 enregistrements ; très visible à 10 000.

**Impact :**

* SQL : avant environ n requêtes de sous-enregistrements ; après `ceil(n / 200)` ;
* CPU : moins de préparation/exécution répétée ;
* mémoire : cache borné à 200 enregistrements pour les sous-enregistrements ;
* temps de réponse : gain important lorsque la latence DB est non négligeable ;
* scalabilité : améliorée, mais limitée par le chargement complet des records et du fichier décrit dans PERF-002.

**Correction proposée :**

Correction implémentée : JOIN explicite vers les éléments, `WHERE IN` borné à 200 identifiants, regroupement indexé par `record`, et ordre conservé par élément puis identifiant de sous-enregistrement.

**Gain attendu :** important.

**Risque de régression :** LOW

**Validation :**

Avec le plugin Debug System, comparer le nombre de requêtes pour 100, 1 000 et 10 000 lignes. Vérifier que les sorties CSV/XML sont identiques et que les sous-enregistrements sans élément restent exclus comme auparavant.

## PERF-004 — Requête d’intégration répétée pour chaque champ soumis

**Sévérité :** HIGH

**Localisation :**

`components/com_breezingformsng/src/Service/Integration/IntegratorRuntime.php:L84-L115`, `L162-L177`

**Code concerné :**

`field()` parcourait chaque règle et appelait `getItems()` à chaque champ soumis. `getItems()` rechargeait alors les mêmes items de règle.

**Problème :**

Le nombre de requêtes dépendait du produit nombre de règles × nombre de champs soumis, alors que les items d’une règle sont invariants pendant la requête.

**Pourquoi c’est coûteux :**

Chaque appel répétait JOIN, GROUP BY, hydratation d’objets et création de tableaux, avant le simple test `element_id`.

**Conditions d’apparition :**

Visible lorsqu’une intégration est active avec plusieurs règles et un formulaire de nombreux champs ; par exemple 5 règles × 30 champs produisait jusqu’à 150 lectures identiques.

**Impact :**

* SQL : avant O(r × e) lectures d’items ; après au plus une lecture par règle ;
* CPU : réduction des hydratations et parcours répétés ;
* mémoire : petit cache local des items par règle ;
* temps de réponse : gain modéré à important sur les soumissions intégrées ;
* scalabilité : améliorée linéairement par rapport au nombre de champs.

**Correction proposée :**

Correction implémentée : cache privé à la durée de `IntegratorRuntime`, indexé par identifiant de règle. Les données, le code configuré et les permissions ne sont pas modifiés.

**Gain attendu :** important pour les formulaires intégrés ; nul lorsque l’intégrateur n’est pas utilisé.

**Risque de régression :** LOW

**Validation :**

Compter les appels SQL d’items avec 1, 5 et 20 règles et 10, 30 et 100 champs. Vérifier que `field()` associe exactement les mêmes items et que les scripts d’intégration sont exécutés une seule fois comme avant.

## PERF-005 — Colonnes lourdes inutiles dans les listes administrateur

**Sévérité :** MEDIUM

**Localisation :**

`administrator/components/com_breezingformsng/src/Model/FormsModel.php:L59-L74`, `administrator/components/com_breezingformsng/src/Model/PackageModel.php:L239-L260`

**Code concerné :**

Les listes de formulaires, scripts et pièces utilisaient `SELECT *`. Les listes de scripts/pièces transféraient notamment `code` et `unit_tests` ; la liste de formulaires transférait aussi `template_code`, `template_areas` et d’autres champs longs non affichés.

**Problème :**

La pagination limitait le nombre de lignes mais pas la largeur inutile de chaque ligne.

**Pourquoi c’est coûteux :**

Les champs `TEXT`/`LONGTEXT` sont lus depuis la DB, hydratés en objets et conservés pendant le rendu, même lorsqu’ils ne sont pas utilisés par la vue de liste.

**Conditions d’apparition :**

Mesurable avec des scripts/pièces contenant du code volumineux ou plusieurs dizaines de formulaires.

**Impact :**

* SQL : mêmes lignes, mais moins d’octets lus et transférés ;
* CPU : hydratation d’objets plus légère ;
* mémoire : baisse proportionnelle aux colonnes exclues ;
* temps de réponse : gain faible à modéré, parfois important avec de gros codes ;
* scalabilité : meilleure largeur de page, pagination déjà présente.

**Correction proposée :**

Correction implémentée : projection limitée aux colonnes utilisées par les listes (`id`, métadonnées d’affichage, description, dates et publication). Les écrans d’édition continuent d’utiliser leurs chargements complets dédiés.

**Gain attendu :** modéré, dépendant de la taille des contenus.

**Risque de régression :** LOW

**Validation :**

Vérifier les listes et tris avec 10, 100 et 1 000 éléments, puis ouvrir un élément depuis chaque liste. Contrôler l’absence de colonne manquante dans les vues et mesurer la taille des résultats DB.

## PERF-006 — Import CSV avec écritures et lectures répétées par champ

**Sévérité :** HIGH

**Localisation :**

`administrator/components/com_breezingformsng/src/Model/RecordModel.php:L338-L485`, `L137-L202`

**Code concerné :**

L’import lit correctement les lignes avec `fgetcsv()` et utilise une transaction, mais insère chaque record séparément. Pour chaque champ, `saveElementValue()` effectue une recherche de sous-records puis des UPDATE/INSERT individuels.

**Problème :**

Le coût DB est proportionnel au nombre de lignes multiplié par le nombre de champs et de valeurs répétées.

**Pourquoi c’est coûteux :**

Une transaction ne supprime pas les allers-retours, les préparations de requêtes ni l’hydratation répétée. En encodage non UTF-8, `stream_get_contents()` du fichier complet ajoute un pic mémoire.

**Conditions d’apparition :**

Dégradation importante à 1 000 lignes ; risque de timeout et de mémoire à 10 000+ lignes ou avec beaucoup de colonnes.

**Impact :**

* SQL : au minimum une insertion par ligne, plus les lectures/écritures de sous-records par champ ;
* CPU : parsing et préparation répétée ;
* mémoire : fichier complet en mémoire pour une conversion d’encodage ;
* temps de réponse : potentiellement très long ;
* scalabilité : faible.

**Correction proposée :**

Précharger la définition des éléments, construire les inserts de records et sous-records par lots bornés, éviter le SELECT de sous-records pour les nouvelles lignes, et utiliser un filtre de conversion de flux pour les encodages non UTF-8. Conserver des transactions par lot et le rollback global attendu comme choix fonctionnel explicite.

**Gain attendu :** important à très important.

**Risque de régression :** HIGH

**Validation :**

Mesurer requêtes, temps et mémoire sur 100, 1 000 et 10 000 lignes, avec champs simples, multi-valeurs et encodage non UTF-8. Comparer les identifiants, valeurs, dates et comportement de rollback sur erreur.

## PERF-007 — Sauvegarde d’un record : requête par élément et valeur

**Sévérité :** MEDIUM

**Localisation :**

`administrator/components/com_breezingformsng/src/Model/RecordModel.php:L97-L202`

**Code concerné :**

`saveRecord()` boucle sur les éléments éditables. `saveElementValue()` recharge les sous-records de chaque élément, puis exécute un UPDATE par valeur existante et un INSERT par nouvelle valeur.

**Problème :**

Une édition de record avec beaucoup d’éléments ou de groupes multi-valeurs déclenche un nombre élevé de requêtes.

**Pourquoi c’est coûteux :**

Les mêmes sous-records sont lus séparément par élément et les opérations ne sont pas regroupées.

**Conditions d’apparition :**

Formulaires d’administration de 30–100 éléments, cases à cocher multiples ou listes multi-valeurs.

**Impact :**

* SQL : O(e + v) requêtes environ, avec un SELECT par élément ;
* CPU : construction répétée des QueryBuilder ;
* mémoire : faible ;
* temps de réponse : modéré, surtout sur DB distante ;
* scalabilité : moyenne pour les formulaires complexes.

**Correction proposée :**

Précharger une fois les sous-records du record, indexer par élément et nom, puis regrouper les écritures compatibles. Préserver les identifiants existants, les multi-valeurs, les champs vides et l’ordre métier.

**Gain attendu :** modéré.

**Risque de régression :** MEDIUM

**Validation :**

Comparer le nombre de requêtes et le contenu final avec 10, 50 et 100 éléments, en couvrant les valeurs vides, les multi-valeurs et le fallback `Formulaire`.

## PERF-008 — Query List entièrement chargé et paginé côté navigateur

**Sévérité :** HIGH

**Localisation :**

`components/com_breezingformsng/src/Service/Rendering/QueryListRowPreparationService.php:L59-L78`, `components/com_breezingformsng/src/Service/Scripting/ScriptingEngine.php:L228-L283`

**Code concerné :**

`execQuery()` exécute la requête configurée et transforme toutes les lignes en `$valrows`. Le résultat est ensuite sérialisé dans le JavaScript ; la pagination (`QueryListPageScriptBuilder`) masque les lignes côté client mais ne réduit ni la requête ni le payload initial.

**Problème :**

La pagination visuelle n’est pas une pagination SQL.

**Pourquoi c’est coûteux :**

Toutes les lignes et colonnes sont hydratées en PHP, transformées cellule par cellule, sérialisées dans la page HTML/JS et conservées dans le navigateur.

**Conditions d’apparition :**

Visible à 1 000 lignes ; risque important à 10 000 et critique à 100 000 selon la largeur et le code de transformation de chaque cellule.

**Impact :**

* SQL : absence de limite sur la requête configurée ;
* CPU : O(lignes × colonnes × transformations) en PHP et JS ;
* mémoire : PHP, HTML et navigateur conservent de gros tableaux ;
* temps de réponse : HTML initial lourd et parsing navigateur lent ;
* scalabilité : mauvaise.

**Correction proposée :**

Ajouter une pagination serveur explicite pour les Query Lists, avec endpoint et contrat de tri/filtre documentés. Cela nécessite de distinguer les requêtes configurées sûres pour compter/paginer et de préserver les scripts de transformation, donc aucune modification automatique n’est appliquée dans cet audit.

**Gain attendu :** très important sur les Query Lists volumineuses.

**Risque de régression :** HIGH

**Validation :**

Tester 100, 1 000, 10 000 et 100 000 résultats dans les trois modes de formulaire. Mesurer poids HTML, TTFB, temps de parsing, mémoire navigateur et nombre de requêtes SQL.

## PERF-009 — Index composites à confirmer par EXPLAIN

**Sévérité :** MEDIUM

**Localisation :**

`administrator/components/com_breezingformsng/sql/install.mysql.utf8.sql:L224-L274`, `administrator/components/com_breezingformsng/src/Model/RecordsModel.php:L42-L145`

**Code concerné :**

La liste des records filtre fréquemment sur `records.form`, recherche plusieurs colonnes et trie notamment sur `records.submitted` ou `records.modified`. Le schéma possède un index simple sur `form`, mais pas d’index composite visible pour le filtre + tri.

**Problème :**

Selon la sélectivité et la distribution, la base peut devoir trier un grand ensemble après le filtre.

**Pourquoi c’est coûteux :**

Un index simple ne couvre pas nécessairement à la fois la sélection et l’ordre. Les recherches avec `%terme%` restent non sargables avec un index B-tree ordinaire.

**Conditions d’apparition :**

Formulaires avec 10 000+ records, filtre par formulaire et tri par date ; le gain dépend fortement du moteur et de la distribution.

**Impact :**

* SQL : scans/tri potentiellement plus larges ;
* CPU : tri DB ;
* mémoire : buffers de tri DB ;
* temps de réponse : dépendant du plan ;
* scalabilité : à risque sur grosses tables.

**Correction proposée :**

Mesurer d’abord `EXPLAIN`/`EXPLAIN ANALYZE` sur les requêtes réelles. Tester éventuellement `records(form, submitted, id)` pour le parcours filtré et `records(submitted, id)` pour le parcours global, sans ajouter automatiquement les deux. Évaluer aussi le coût d’écriture et l’espace disque.

**Gain attendu :** modéré à important si le plan actuel trie un gros ensemble ; nul si les index existants suffisent.

**Risque de régression :** MEDIUM

**Validation :**

Comparer plans, lignes examinées, temps SQL et coût d’écriture avant/après sur une copie représentative contenant au moins 100 000 records. Ajouter ensuite une migration idempotente uniquement si le gain est confirmé.

## PERF-010 — Audit de schéma administrateur à coût fixe

**Sévérité :** LOW

**Localisation :**

`administrator/components/com_breezingformsng/src/Service/DatabaseAuditService.php:L45-L113`, `L307-L340`

**Code concerné :**

`run()` exécute plusieurs lectures de métadonnées par table attendue, puis quatre comptages d’orphelins séparés.

**Problème :**

Le service peut exécuter plusieurs dizaines de requêtes pour une page d’audit.

**Pourquoi c’est coûteux :**

Les requêtes inspectent `information_schema`, les index et les relations séparément.

**Conditions d’apparition :**

Uniquement lorsque l’administrateur lance l’audit de base ; ce n’est pas dans le chemin frontend normal.

**Impact :**

* SQL : coût fixe d’environ plusieurs dizaines de requêtes ;
* CPU : faible ;
* mémoire : faible ;
* temps de réponse : modéré seulement sur serveur DB distant ;
* scalabilité : indépendante du trafic frontend, hormis la taille des tables d’orphelins.

**Correction proposée :**

Conserver en l’état. Une mutualisation rendrait le diagnostic plus complexe pour un gain limité ; ne l’envisager que si la page d’audit est mesurée comme lente.

**Gain attendu :** faible.

**Risque de régression :** LOW

**Validation :**

Mesurer le nombre et le temps des requêtes uniquement lors de l’exécution de l’audit. Ne pas optimiser ce chemin avant d’avoir un signal réel.

## Vérifications négatives

* Les listes administrateur utilisent déjà une limite/pagination ; aucun chargement complet des records n’est utilisé pour la liste normale.
* Les vérifications ACL directes sont limitées aux contrôleurs et les contrôles ContentBuilder passent par le service de permissions ; aucun `authorise()` massif dans une boucle n’a été retenu.
* `RuntimeAssetLoader` déduplique les assets dynamiques par URI et les vues enregistrent leurs assets spécifiques ; aucun doublon frontend évident n’a justifié une modification.
* `DatabaseAuditService::findOrphans()` utilise quatre requêtes fixes, pas une requête par ligne ; ce n’est pas un N+1 de données.

## Tableau de synthèse

| ID | Sévérité | Zone | Problème | Gain attendu | Complexité | Priorité |
| --- | --- | --- | --- | --- | --- | --- |
| PERF-001 | HIGH | Model/View | Navigation O(n) et tableau complet d’identifiants | Important | Moyenne | P1 |
| PERF-002 | CRITICAL | Export | Records et sortie construits en mémoire | Très important | Élevée | P0 |
| PERF-003 | HIGH | Export/Model | N+1 des sous-records CSV/XML | Important | Faible | P1 |
| PERF-004 | HIGH | Integration | Items rechargés pour chaque champ | Important | Faible | P1 |
| PERF-005 | MEDIUM | Admin lists | `SELECT *` et gros champs inutiles | Modéré | Faible | P2 |
| PERF-006 | HIGH | Import | SQL par ligne et par champ | Important | Élevée | P1 |
| PERF-007 | MEDIUM | Admin model | Sauvegarde élément par élément | Modéré | Moyenne | P2 |
| PERF-008 | HIGH | Frontend Query List | Pagination seulement côté client | Très important | Élevée | P1 |
| PERF-009 | MEDIUM | Database | Index composites non confirmés | Variable | Moyenne | P2 |
| PERF-010 | LOW | Database audit | Requêtes de diagnostic répétées | Faible | Moyenne | P3 |

## Modifications implémentées

* Projection minimale dans `FormsModel` et `PackageModel`.
* Cache local des items dans `IntegratorRuntime`.
* Chargement des sous-records par lots de 200 pour CSV/XML.
* Mise à jour `exported` par lots de 500 identifiants pour éviter les `IN (...)` démesurés.
* Garde-fous dans `tests/Performance/PerformanceRegressionTest.php`.

Ces changements ne modifient ni les routes, ni les permissions, ni les événements Joomla, ni les chaînes utilisateur.

## Plan d’optimisation

### Phase 1 — Quick wins

1. Déployer et mesurer PERF-003, PERF-004 et PERF-005.
2. Mesurer les exports avec Debug System, mémoire PHP et temps SQL.
3. Ajouter les index de PERF-009 uniquement après confirmation par `EXPLAIN`.

### Phase 2 — Optimisations structurelles

1. Repenser la navigation PERF-001 côté SQL avec tests de tous les tris et des `NULL`.
2. Refactoriser l’import PERF-006 avec lots bornés et stratégie d’échec documentée.
3. Concevoir la pagination serveur des Query Lists PERF-008.

### Phase 3 — Optimisations avancées

1. Streamer les exports PERF-002, d’abord CSV/XML puis PDF si le besoin est confirmé.
2. Réduire les traitements et allocations de la génération PDF sur gros lots.
3. Utiliser Xdebug profiler ou Blackfire seulement après les mesures précédentes, sans conserver de profiling dans le produit.

## Validation réalisée

* PHP 8.3.6 : syntaxe des fichiers modifiés validée.
* PHPUnit : `664 tests`, `2 248 assertions`, aucun échec ni avertissement.
* PHPStan : niveau/configuration du dépôt, aucune erreur.
* `git diff --check` : propre.
* PHPCS : des erreurs préexistantes restent dans les fichiers historiques (`RecordsController`, en-têtes et style ancien) ; aucune réécriture de formatage hors périmètre performance n’a été effectuée.

Les mesures de gain HTTP/SQL réelles restent à effectuer sur une instance Joomla 6 reliée à une base représentative.
