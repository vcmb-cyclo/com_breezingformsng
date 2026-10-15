# Migration de la treeview QuickMode

Date : 2026-09-05. Statut : plan de réalisation, non implémenté.

Écran concerné : `index.php?option=com_breezingformsng&task=quickmode.display`.

## 1. Objectif et périmètre

Remplacer la treeview jTree par un composant JavaScript moderne, accessible et
intégré à l'administration Joomla, en préservant les formulaires existants et
les interactions avec leurs propriétés.

- Cible exclusive : Joomla 6 et PHP 8.3+.
- Conserver le format de données et le parcours de sauvegarde existants.
- Réutiliser les variables CSS `--bfng-*` et les conventions natives de Joomla.
- Ne pas ajouter de framework frontend, de shim, de polyfill ou de moteur de secours.
- Ne pas étendre ce chantier au rendu frontend des formulaires ni à la refonte
  complète des onglets Propriétés, Avancé et Options.
- La recherche et les commandes globales de dépliage constituent un lot distinct,
  après validation de la migration fonctionnelle.

Ce document repose sur une lecture du code. Les constats visuels et les mesures
de performance devront être confirmés dans un navigateur avant réalisation.

## 2. Cartographie du code actuel

Les chemins ci-dessous sont relatifs à la racine du dépôt.

| Fichier | Rôle et attention requise |
| --- | --- |
| `administrator/components/com_breezingformsng/src/Helper/QuickmodeHtml.php` | Markup du panneau, barre d'actions, conteneur `#bfElementExplorer`, chargement des assets. |
| `administrator/components/com_breezingformsng/src/View/Quickmode/HtmlView.php` | Autre point de chargement des assets QuickMode. |
| `administrator/components/com_breezingformsng/tmpl/quickmode/default.php` | Préparation du rendu, notamment de la racine. |
| `media/com_breezingformsng/js/admin/quickmode-app.js` | Initialisation jTree, callbacks, copie, suppression, déplacement, sélection et sauvegarde. |
| `media/com_breezingformsng/js/admin/quickmode-app-properties.js` | Lecture/écriture des propriétés, création des nœuds et accès directs aux éléments DOM sélectionnés. |
| `media/com_breezingformsng/js/admin/quickmode-editor.js` | Accès aux propriétés depuis les éditeurs, notamment via `parent.app.selectedTreeElement`. |
| `media/com_breezingformsng/js/admin/quickmode-form-dirty.js` | Détection des modifications par sérialisation et interrogation toutes les 500 ms. |
| `media/com_breezingformsng/js/admin/quickmode-compat.js` | Shims jQuery et helper String existants : examiner les consommateurs avant retrait. |
| `media/com_breezingformsng/joomla.asset.json` | Graphe de dépendances jTree, QuickMode et éditeurs. |
| `media/com_breezingformsng/css/bfng-admin.css` | Habillage de l'arbre déjà fondé sur les tokens. |
| `media/com_breezingformsng/css/bfng-tokens.css` | Hauteur et indentation de l'arbre, entre autres variables partagées. |
| `media/com_breezingformsng/css/custom.css` | Anciennes surcharges des thèmes et sprites. |
| `administrator/components/com_breezingformsng/libraries/jquery/jtree/` | Moteur, styles, thèmes et dépendances à retirer après contrôle des usages. |
| `administrator/components/com_breezingformsng/tmpl/about/default.php` | Références documentaires aux assets jTree à actualiser lors du retrait. |

### Comportements observés à préserver

- Les données résident dans `app.dataObject` ; jTree en affiche la structure JSON.
- Avant un changement de sélection, le callback pousse les propriétés du nœud
  précédent dans les données. Omettre cette étape entraîne une perte de saisie.
- Le déplacement actuel lit l'ordre du DOM, modifie les données puis rafraîchit
  l'arbre. La nouvelle implémentation devra faire des données la source de vérité.
- La suppression renumérote les pages et ajuste le traitement de la dernière
  page lorsque celle-ci sert de page de remerciement.
- La copie concerne les sections et éléments ; le collage vise une page ou une
  section et recrée les identifiants des descendants.
- L'ouverture et la fermeture écrivent actuellement `state` dans les données.
- Les libellés du menu contextuel `Copy`, `Paste` et `Delete` sont codés en dur.
- Les tokens modernisent déjà une partie du rendu : ne pas repartir d'une feuille
  blanche ni confondre les valeurs CSS héritées avec les valeurs effectivement appliquées.

## 3. Architecture cible

Créer trois modules de responsabilité limitée sous
`media/com_breezingformsng/js/admin/` :

| Module proposé | Responsabilité |
| --- | --- |
| `quickmode-tree-model.js` | Recherche/index des nœuds, règles de parenté et opérations sur les données. Aucun accès DOM. |
| `quickmode-tree-view.js` | Construction DOM, focus, sélection visuelle, dépliage et indicateurs de déplacement. Aucune sauvegarde métier. |
| `quickmode-tree-controller.js` | Coordination des actions, synchronisation des propriétés et émission des événements. |

Ces noms sont une proposition d'organisation, pas des fichiers déjà présents.
Éviter une abstraction générique destinée à d'autres écrans.

### Contrat de données et d'identité

1. Garder `app.dataObject` comme unique arbre métier ; ne pas maintenir une seconde
   copie mutable pour le rendu.
2. Conserver les propriétés JSON attendues par la sauvegarde et le moteur de rendu.
   Aucun changement de schéma SQL ou conversion globale des formulaires n'est prévu.
3. Référencer la sélection par identifiant, sans conserver un `<li>` comme identité
   métier. Adapter tous les consommateurs, y compris les éditeurs imbriqués.
4. Distinguer les identifiants DOM, les noms techniques et les numéros de pages.
   Examiner leurs usages avant toute modification : les identifiants des pages
   sont actuellement liés à leur position et certains renommages changent les IDs.
5. Pour la duplication, allouer des identifiants et noms uniques dans l'arbre,
   remettre les identifiants de base de données concernés à zéro et traiter tous
   les descendants. Ne pas modifier implicitement les références contenues dans
   des scripts utilisateurs ; conserver et documenter la sémantique existante.
6. Conserver les états de navigation hors des mutations de contenu. Initialiser
   l'ouverture depuis les états existants et préciser leur projection lors de la
   sauvegarde, sans marquer le formulaire modifié au simple dépliage.

### Opérations du contrôleur

Prévoir des opérations explicites : sélection, création, copie, collage,
suppression et déplacement avec destination et position avant/dedans/après.

Pour chaque opération : valider les préconditions, synchroniser les propriétés
concernées, modifier les données une seule fois, mettre à jour la vue, rétablir
focus/sélection puis annoncer le résultat. Une opération refusée ne doit laisser
aucune mutation partielle. Si la synchronisation des propriétés échoue, conserver
la sélection et présenter l'erreur.

Les règles sont communes aux boutons, au clavier et au glisser-déposer :

- La racine contient des pages ; elle ne peut être ni déplacée ni supprimée.
- Les pages se réordonnent entre elles ; elles ne s'imbriquent pas.
- Pages et sections peuvent contenir sections et éléments.
- Un élément ne contient aucun enfant.
- Un nœud ne peut être déplacé dans lui-même ou dans un descendant.
- Vérifier les métadonnées existantes de suppression avant de reproduire les
  autorisations ; ne pas déduire celles-ci du seul type de nœud.

## 4. Lots de réalisation

### Lot 1 — Caractériser le comportement avant remplacement

- [ ] Recenser les appels à `tree_reference`, `.tree(`, `selectedTreeElement`,
  `getNodeClass`, `createTreeItem` et les mutations de `dataObject`.
- [ ] Lire les métadonnées de création et suppression ainsi que les points de
  sauvegarde ; documenter les règles qui complètent celles listées ci-dessus.
- [ ] Préparer un formulaire de test avec plusieurs pages, sections imbriquées,
  éléments de types différents, scripts et page de remerciement.
- [ ] Capturer son JSON et son comportement avant modification, avec des données
  fictives, pour comparer sauvegarde et rechargement après migration.
- [ ] Ajouter des tests comportementaux sur les invariants sensibles ; réutiliser
  les tests QuickMode existants lorsque leur périmètre convient.

Critère de sortie : les effets attendus des opérations sont écrits et vérifiables,
en particulier pour la saisie non enregistrée et les pages.

### Lot 2 — Extraire les opérations métier

- [ ] Extraire les manipulations d'arbre du code d'interface vers le modèle dédié.
- [ ] Centraliser validation de destination, insertion, suppression, duplication
  récursive, unicité et renumérotation.
- [ ] Construire un index par identifiant et le maintenir lors des mutations,
  y compris après renommage ou renumérotation.
- [ ] Séparer la synchronisation du panneau de propriétés de sa présentation.
- [ ] Vérifier que les opérations retournent le résultat nécessaire à la vue
  sans sélectionner ou chercher un nœud dans le DOM.

Critère de sortie : tests du modèle exécutables sans jTree ni navigateur.
L'extraction peut être réalisée avant la bascule, mais ne doit pas introduire
d'adaptateur imitant l'ancienne API ni de double moteur livré.

### Lot 3 — Construire la vue et basculer les consommateurs

- [ ] Rendre l'arbre avec les API DOM ; affecter les titres avec `textContent`.
  Ne pas injecter de libellé utilisateur via `innerHTML`.
- [ ] Installer une délégation d'événements sur le conteneur, avec destruction
  explicite des écouteurs si le composant est réinitialisé.
- [ ] Implémenter sélection, dépliage et navigation clavier avant le déplacement.
- [ ] Brancher les boutons existants sur le contrôleur unique.
- [ ] Migrer ensemble les accès de `quickmode-app.js`,
  `quickmode-app-properties.js` et `quickmode-editor.js` à la nouvelle sélection.
- [ ] Conserver `bfqm:ready` avec une émission après initialisation effective,
  et vérifier les consommateurs de `window.BFQMApp` et `parent.app`.
- [ ] Mettre à jour seulement les branches affectées ; préserver ouverture,
  sélection, focus et défilement des branches intactes.
- [ ] Enregistrer les modules dans le Web Asset Manager et vérifier leur ordre
  d'initialisation avec `editor-api` et les autres modules QuickMode.

Critère de sortie : toutes les opérations fonctionnent sans appel à jTree dans
le parcours QuickMode, y compris les éditeurs de propriétés.

### Lot 4 — Ergonomie, accessibilité et déplacements

- [ ] Définir une arborescence avec rôles `tree`, `treeitem` et `group`, sélection
  explicite et `aria-expanded` uniquement pour les nœuds développables.
- [ ] Gérer un seul point d'entrée Tab dans l'arbre et un focus mobile entre nœuds.
  Flèches haut/bas : nœuds visibles ; droite : ouvrir/descendre ; gauche :
  fermer/remonter ; Début/Fin : premier/dernier nœud visible.
- [ ] Distinguer focus et sélection : Entrée ou Espace sélectionne et synchronise
  le panneau ; le déplacement du focus seul ne doit pas effacer de saisie.
- [ ] Proposer le menu d'actions par bouton et clavier, sans imposer le clic droit.
  Échap ferme le menu et rend le focus à son déclencheur.
- [ ] Ajouter un déplacement accessible permettant de choisir destination et
  position, utilisant exactement les règles du glisser-déposer.
- [ ] Pour le glisser-déposer : afficher avant/dedans/après, refuser clairement les
  cibles invalides, permettre l'annulation et gérer le défilement en bord de panneau.
- [ ] Après suppression, sélectionner un nœud survivant déterministe : voisin
  suivant, précédent, puis parent. Pour une branche non vide, confirmer la portée
  de la suppression dans une interface conforme aux conventions Joomla.
- [ ] Annoncer les résultats et refus via une zone de statut accessible traduite.
- [ ] Consolider les styles sous une classe propre au composant : ligne entière
  sélectionnée, focus visible, indentation, icônes et couleurs issues des tokens.
- [ ] Remplacer les sprites raster de l'arbre par les icônes du système existant.
  Garantir l'accès au libellé complet lorsqu'il est tronqué.
- [ ] Traduire les actions, erreurs, descriptions accessibles et confirmations
  dans `en-GB`, `fr-FR`, `de-DE`, `es-ES`, `hu-HU`, `it-IT`, `nl-NL`, `tr-TR`.
  Appliquer le skill `joomla-translations` lors de ces changements.

Ne pas ajouter le renommage en ligne dans ce lot : les propriétés restent le
point d'édition du titre et du nom technique.

Critère de sortie : créer, sélectionner, copier/coller, déplacer et supprimer
sont réalisables sans souris, avec focus visible et messages compréhensibles.

### Lot 5 — Notifications de changement et retrait de jTree

- [ ] Émettre un événement de contenu après chaque mutation réussie, avec type
  d'opération et identifiants concernés ; utiliser un événement distinct pour
  sélection et dépliage. Documenter les noms et charges utiles retenus.
- [ ] Brancher le badge de modifications sur ces événements et sur ceux des champs.
  Ne retirer l'interrogation périodique qu'après couverture des éditeurs riches,
  de leur initialisation et de toutes les mutations externes à la treeview.
- [ ] Préserver la référence initiale du badge après `bfqm:ready`, son comportement
  après sauvegarde réussie et après échec ; une opération sans effet reste neutre.
- [ ] Rechercher les autres consommateurs de jTree et de `_lib.js` avant suppression.
  Contrôler notamment la dépendance actuelle de `quickmode-compat` à `jtree-lib`.
- [ ] Migrer les consommateurs des helpers concernés vers les API modernes avant
  de retirer les shims devenus inutiles ; ne pas ajouter de remplacement compatible.
- [ ] Supprimer les déclarations et chargements devenus inutiles dans les deux
  points PHP, le manifeste d'assets, les styles et la page À propos.
- [ ] Retirer les fichiers jTree seulement si aucun autre écran ne les utilise.
  Si un consommateur subsiste, le documenter sans étendre implicitement le chantier.

Critère de sortie : QuickMode ne charge plus jTree, aucun asset absent n'est
référencé et le badge ne dépend plus des changements d'état de navigation.

### Lot 6 — Recherche et commandes globales, après migration

- [ ] Ajouter une recherche par titre et nom technique avec ancêtres des résultats
  visibles et indication du nombre de résultats.
- [ ] Conserver la sélection même lorsqu'elle est hors filtre ; préciser son état
  dans l'interface et restaurer l'ouverture précédente lorsque le filtre est effacé.
- [ ] Désactiver le réordonnancement pendant le filtrage pour éviter une destination
  ambiguë parmi les nœuds masqués.
- [ ] Ajouter Tout déplier / Tout replier ; ne modifier ni les données métier ni
  le badge de modifications.

Ce lot ne conditionne pas l'acceptation du remplacement du moteur.

## 5. Matrice de validation

| Scénario | Résultat attendu |
| --- | --- |
| Ouvrir un formulaire existant ou nouveau | Arbre correct, bonne sélection initiale, aucun faux état modifié. |
| Modifier une propriété puis sélectionner un autre nœud | Valeur conservée en mémoire et après sauvegarde/rechargement. |
| Modifier un script dans un éditeur puis changer de nœud | Contenu conservé, panneau affichant les bonnes données. |
| Renommer titre et nom technique | Arbre, index, sélection et éditeurs restent cohérents. |
| Déplacer un élément entre pages ou sections | Position exacte, propriétés et identité conservées. |
| Déplacer une section dans un descendant | Refus sans modification partielle. |
| Réordonner ou supprimer une page | Numérotation et page de remerciement cohérentes après rechargement. |
| Copier/coller une section imbriquée | Descendants complets, IDs et noms uniques, original inchangé. |
| Supprimer le nœud sélectionné | Sélection survivante valide, propriétés et focus cohérents. |
| Déplier, replier ou déplacer seulement le focus | Aucun faux changement de contenu. |
| Sauvegarde refusée ou échouée | Données locales préservées, état modifié maintenu. |
| Titres longs, accents, guillemets, chevrons | Texte lisible, aucun HTML exécuté, sélection fiable. |
| Parcours clavier et lecteur d'écran | Hiérarchie, ouverture, sélection et actions compréhensibles. |
| Thèmes clair/sombre, zoom et panneau étroit | Focus et sélection lisibles, actions accessibles, aucun contenu essentiel coupé. |
| Répétition de créations/déplacements/suppressions | Pas d'écouteurs multiples ni de références à des nœuds supprimés. |

Pour les performances, utiliser des fixtures de 100, 500 et 1 000 nœuds avec
plusieurs profondeurs. Mesurer initialisation, sélection, déplacement et recherche
dans le même navigateur et sur la même machine avant/après. Établir le budget à
partir de ces mesures ; ne pas introduire de virtualisation sans besoin mesuré.

## 6. Vérifications techniques et livraison

Tests existants à examiner et prolonger selon les changements :

- `tests/Administrator/QuickmodeHtmlTest.php`
- `tests/Administrator/QuickmodeTemplateCodeTest.php`
- `tests/Administrator/QuickmodeModelTest.php`
- `tests/Administrator/QuickmodeFormDirtyInitializationTest.php`
- `tests/Administrator/QuickmodeOptionsEditorSyncTest.php`

Ces tests ne remplacent pas les tests d'interactions dans un vrai navigateur.
Utiliser le skill `playwright-cli` pour les parcours de l'éditeur. Ne pas écrire
de tests vérifiant uniquement la présence d'un nom de fonction ou d'une classe CSS.

Avant livraison :

- [ ] Exécuter les tests ciblés, puis les contrôles PHP applicables aux fichiers
  modifiés : syntaxe, PHPUnit, PHPStan et PHPCS selon la configuration du dépôt.
- [ ] Vérifier le chargement des modules, l'absence d'erreurs console et de 404.
- [ ] Construire et valider le paquet avec `scripts/build-package.sh` et
  `scripts/validate-package.sh`, après lecture de leurs arguments requis.
- [ ] Vérifier le parcours dans Joomla 6 avec le paquet produit.
- [ ] Comparer les données sauvegardées avant/après, en tenant compte uniquement
  des modifications fonctionnelles effectuées et des états de navigation prévus.
- [ ] Consigner les résultats, limites et captures utiles dans le compte rendu.
- [ ] Livrer une bascule cohérente : aucun mélange de consommateurs de l'ancienne
  et de la nouvelle API, aucune implémentation de secours.

La réalisation des modules et la bascule des consommateurs sont dépendantes et
doivent rester coordonnées. Les audits CSS, les traductions dans des fichiers
distincts et les vérifications indépendantes peuvent être délégués. Le responsable
de la migration intègre les résultats et vérifie l'ensemble avant le commit.

## 7. Définition de terminé

La migration principale est terminée lorsque les lots 1 à 5 sont validés, que les
formulaires existants restent éditables et enregistrables sans conversion, que les
actions sont accessibles au clavier, que QuickMode ne dépend plus de jTree et que
la matrice de non-régression est renseignée. Le lot 6 reste une amélioration séparée.
