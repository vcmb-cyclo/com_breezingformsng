# Plan de migration de l'architecture PHP

> Feuille de route de la modernisation Joomla 6 / PHP 8.3+. Les anciennes
> versions de Joomla et PHP ne font pas partie du périmètre.

## Objectifs

- Réduire progressivement les façades historiques sans réécriture globale.
- Isoler le rendu, l'accès aux données et la génération JavaScript dans des
  services testables.
- Supprimer les duplications entre les quatre renderers QuickMode.
- Étendre PHPCS et réduire la baseline PHPStan à mesure que les blocs sont
  extraits.
- Préserver les sorties HTML et JavaScript tant qu'un changement de
  comportement n'est pas explicitement demandé et caractérisé.

## Principes de migration

- **Geler la façade publique** (HTML_facileFormsProcessor et les façades historiques exposées) : ne pas modifier, renommer ou supprimer ses classes, méthodes, propriétés publiques, signatures ni son point de chargement. Du code PHP est stocké en base dans les formulaires, pièces, scripts et règles d’intégration, et des intégrations peuvent aussi l’appeler depuis l’extérieur du dépôt ; l’absence d’appelant dans le code source ne constitue donc pas une preuve d’absence d’usage. Toute évolution future doit se faire derrière la façade, dans les services qu’elle délègue, avec le contrat public inchangé.

- Extraire une seule responsabilité par lot.
- Ajouter les tests de caractérisation avant l'extraction, ou dans le même
  commit lorsque le déplacement est strictement mécanique.
- Comparer les sorties HTML et JavaScript avec des snapshots lorsque leur
  format exact constitue le contrat.
- Préserver les contrats publics encore appelés par le PHP stocké dans les
  formulaires jusqu'à leur migration explicite.
- Utiliser les API Joomla 6 natives et des types PHP stricts pour tout nouveau
  service.
- Ne pas introduire de fallback, de polyfill, de contrôle de version ou de
  compatibilité avec les anciennes versions de Joomla ou PHP.
- Éviter les reformatages massifs : un bloc historique n'est normalisé qu'une
  fois isolé et couvert.

## État actuel

| Domaine | État | Référence |
|---|---|---|
| Uploads, callbacks, exports et permissions | Modernisés et couverts par lots | Tests de services Site/Admin |
| Renderers QuickMode | Snapshots disponibles pour Classic, Bootstrap, Mobile et OnePage | `tests/Site/Service/Rendering/QuickMode/` |
| Champs cachés QuickMode | Mutualisés entre les quatre renderers | `HiddenFieldTrait` |
| Champs Bootstrap/OnePage | Mutualisation partielle | `BootstrapStyleFieldTrait` |
| `RenderingEngine::header()` | Extrait et couvert | `ProcessorHeaderRenderer` |
| `RenderingEngine::view()` — entrée QuickMode | Gardes, métadonnées, session, mode mobile et choix du renderer couverts | `RenderingEngineViewCharacterizationTest` |
| `RenderingEngine::view()` — enveloppe | Initialisation, fermeture et wrappers extraits et couverts | `RenderingEngine` |
| `RenderingEngine::view()` — pièces | Pièces Before/After, code personnalisé et sorties `bury()` couverts | `RenderingEngine` |
| `RenderingEngine::view()` — scripts | Bibliothèques, callbacks formulaire et `onload` extraits et couverts | `RenderingEngine` |
| QuickMode — règles de bascule | Parsing `toggleFields` mutualisé entre les quatre renderers, avec API publique conservée et valeurs multi-mots couvertes | Commit `37c554755` |
| QuickMode — options calendrier | Normalisation des booléens, formats Pickadate, premier jour et années mutualisée entre les quatre renderers | Commit `a2e025609` |
| QuickMode — adaptateurs calendrier | Cinq adaptateurs privés de calendrier mutualisés dans un trait commun aux quatre renderers | Commit `05090635f` |
| QuickMode OnePage — validation précédente | Callback `bf_validate_prevpage` extrait dans un builder pur, avec branches d’erreur et commentaire historique couverts | Commit `f19eb5f89` |
| QuickMode OnePage — validation suivante | Callback `bf_validate_nextpage` extrait dans un builder pur, avec erreur, focus, Ladda et activation de la page couverts | Commit `114dd4d17` |
| QuickMode OnePage — validation soumission | Callback `bf_validate_submit` extrait dans un builder pur, avec erreurs, focus, restauration du bouton et soumission couverts | Commit `280fccd6e` |
| QuickMode OnePage — restauration soumission | Callback `bf_restore_submitbutton` extrait dans un builder pur, avec clonage, remplacement et rebinding Ladda couverts | Commit `bfe93521e` |
| QuickMode — markup d’erreur Bootstrap | Conteneur `bfErrorMessage` partagé entre Bootstrap et OnePage, avec classes de thème injectées et sortie couverte | Commit `9d5a288c2` |
| QuickMode — widget de progression | Markup et script `bfUpdateProgress()` partagés entre Bootstrap et OnePage, avec variante page Thank You couverte | Commit `5cb2da294` |
| QuickMode — configuration runtime des erreurs | Déclaration `bfUseErrorAlerts`/`bfShowDefaultErrors`/`bfErrorPageScoped` partagée, avec portée de page explicite | Commit `45fc45988` |
| QuickMode OnePage — fermeture Thank You | Redirection `bf_remodal_close()` extraite dans un builder pur, avec panier et URL formulaire conservés | Commit `ca2b8f42d` |
| QuickMode OnePage — soumission AJAX | Callback `bf_ajax_submit()` extrait dans un builder pur, avec échec, succès direct et page Thank You couverts | Commit `01ab2d4bd` |
| QuickMode — miniature upload | Callback `bfUploadImageThumb()` partagé entre les quatre renderers, avec chemin Moxie et repli FileReader couverts | Commit `704ccbea2` |
| QuickMode — entrée queue upload | Première boucle `FilesAdded` partagée entre les quatre renderers, avec callback externe et libellé nettoyé couverts | Commit `e6956c7d5` |
| QuickMode — ligne queue upload | Markup de ligne `bfFileQueueItem` mutualisé avec variantes de nom et d’attribut image couvertes | Commit `e081b2f44` |
| QuickMode — progression upload | Callback plupload `UploadProgress` partagé entre les quatre renderers, avec mise à jour de la barre et du pourcentage couverte | Commit `1e28a79b2` |
| QuickMode — fin upload | Callback plupload `FileUploaded` partagé entre les quatre renderers, avec message serveur et nettoyage de ligne couverts | Commit `6d3bfbb27` |
| QuickMode — validation upload | Validation client de taille/extensions et nettoyage des lignes invalides partagé entre les quatre renderers | Commit `fea7df7b6` |
| QuickMode — annulation upload | Annulation, arrêt/redémarrage des uploaders et réactivation du bouton partagés, avec variantes historiques couvertes | Commit `6cf0473ad` |
| QuickMode — expression éditeur | Construction de l’expression JavaScript de lecture des éditeurs mutualisée entre Bootstrap et OnePage, API publique conservée | Commit `43592c8bd` |
| QuickMode — mapping Bootstrap | Mapping Bootstrap 5 des classes mutualisé entre Bootstrap et OnePage, résolution publique `bsClass()` conservée | Commit `658078588` |
| Finalisation — champs de soumission | Champs cachés communs frontend/backend/preview extraits avec conservation des différences `act`/`ff_frame` | Commit `be602b94f` |
| Finalisation — caractérisation des modes | Sorties exactes des champs de soumission frontend, backend et preview verrouillées par tests | Commit `dc73ec0fc` |
| Finalisation — choix mobile | Construction du script et du markup de choix mobile extraite, calcul de l’URL conservé dans l’orchestrateur | Commit `39a4c1da0` |
| Finalisation — wrapper ReCaptcha | Markup du wrapper ReCaptcha historique extrait, activé uniquement pour l’enveloppe legacy et couvert | Commit `092a2a9fa` |
| QuickMode — URLs ReCaptcha | URLs Google visible/invisible mutualisées dans `QuickModeReCaptchaInitScriptBuilder` et réutilisées par Classic, Bootstrap et OnePage, avec snapshots conservés | Commit `4952cfb94` |
| QuickMode — configuration ReCaptcha | Normalisation des configurations visible/invisible mutualisée dans `QuickModeReCaptchaInitScriptBuilder`, sans modifier les paramètres propres aux trois renderers | Commit `56879a38f` |
| QuickMode — rendu ReCaptcha | Markup, configuration et chargement des assets ReCaptcha mutualisés dans `QuickModeReCaptchaFieldBuilder`, avec enveloppes de thème conservées dans les trois renderers | Commit `49a6a5ca7` |
| Finalisation — balise form QuickMode | Assemblage de la balise `<form>` extrait, calcul d’URL conservé dans l’orchestrateur et classe historique préservée | Commit `4f5b8559d` |
| ContentBuilder — wrapper readonly | Enveloppe du script des champs non éditables extraite et couverte, avec marqueurs historiques conservés | Commit `3e1723d15` |
| Finalisation — wrapper enregistrement éditable | Chargement JavaScript de l’enregistrement éditable extrait dans un builder, avec nettoyage historique de `seccode` couvert | Commit `f3b6f8d97` |
| Validation — enveloppe JavaScript | Ouverture/fermeture du script global de validation extraite, contrôles extensions/CAPTCHA conservés dans l’orchestrateur | Commit `9b92acb55` |
| Validation — extensions de fichiers | Génération du validateur JavaScript des uploads extraite dans un builder pur, avec variantes template configuré/non configuré couvertes | Commit `30dd41889` |
| Finalisation — ouverture caractérisée | Initialisation du rendu testée avec remise à zéro des états Query List et wrapper ReCaptcha legacy | Commit `0d33f0253` |
| `RenderingEngine::view()` — validation | Extensions de fichiers, valeurs par défaut et scripts CAPTCHA extraits et couverts | Builders de validation dédiés |
| `RenderingEngine::view()` — CAPTCHA | Sélection `Captcha` / `ReCaptcha`, endpoints site/admin et générateurs JavaScript isolés, ordre historique préservé | Commits `4a070774`, `8e3e9a7a`, `4563ae11`, `d328c4f8`, `75c0ca2b`, `1cea0c84` |
| `RenderingEngine::view()` — Query List | Préparation extraite et variantes par défaut/checkbox/résultat vide couvertes | Commits `4070ec0f`, `b358e7e9` |
| `RenderingEngine::view()` — Query List sélection globale | Callback JavaScript paginé `ff_selectAllQueryRows` extrait et couvert, avec conservation des sélections de page et de la ligne globale | Commit `0b1229d37` |
| `RenderingEngine::view()` — Query List navigation | Markup JavaScript des liens première/précédente/suivante/dernière page extrait et couvert avec libellés injectés | Commit `c93d06eb7` |
| `RenderingEngine::view()` — Query List lignes | Calcul de page et rafraîchissement JavaScript des lignes visibles/masquées extrait et couvert | Commit `7ee50bccc` |
| `RenderingEngine::view()` — Query List fin de pagination | Resynchronisation des checkbox, redimensionnement et défilement extraits et couverts par variantes | Commit `c942cfe90` |
| `RenderingEngine::view()` — Query List état JavaScript | Initialisation ordonnée des tableaux d’état Query List extraite et injection de `qcode` couverte | Commit `70c6aa58b` |
| `RenderingEngine::view()` — hydratation d'un enregistrement éditable | Nettoyage conservé dans l'orchestrateur, génération JavaScript extraite et couverte par famille de contrôle | Commit `a8325a7c` |
| Rendu HTML classique — texte statique, rectangle, image, infobulle et icône | Markup `Static Text/HTML`, `Rectangle`, `Image`, `Tooltip` et `Icon` extrait et couvert sans modifier le HTML produit | Commits `43bc8b9d`, `ec310eabb`, `08f6bc4ee`, `1e23287f8`, `3acfe4d66` |
| Rendu HTML classique — champ caché | Markup `Hidden Input` extrait et couvert avec conservation du nom et de la valeur historiques | Commit `0bacb6fc7` |
| Rendu HTML classique — champ texte | Markup `Text` extrait et couvert pour texte/mot de passe, dimensions, maxlength, événements et états readonly/disabled | Commit `24776d379` |
| Rendu HTML classique — textarea | Markup `Textarea` extrait et couvert pour états, dimensions, événements et ajustement Mozilla des lignes | Commit `593e018b5` |
| Rendu HTML classique — choix élémentaire | Contrôle commun `Checkbox`/`Radio Button` extrait et couvert pour états, classes, labels et événements | Commit `fcc72f2aa` |
| Rendu HTML classique — liste de sélection | Parsing et markup `Select List` extraits et couverts pour options, échappement, sélection, taille et états | Commit `771f3d014` |
| Rendu HTML classique — bouton régulier | Markup `Regular Button` extrait et couvert pour états et événements | Commit `974fcf0db` |
| Rendu HTML classique — bouton graphique | Markup `Graphic Button` extrait et couvert pour les dispositions d’image, états, dimensions et événements | Commit `cf09129e0` |
| Rendu HTML classique — upload | Markup `File Upload` extrait et couvert pour taille, longueur, accept, état disabled et événements | Commit `205127c81` |
| Rendu HTML classique — CAPTCHA | Markup image/saisie/rechargement extrait et couvert pour endpoint, dimensions et mode compressé | Commit `c260c2eb9` |
| Rendu HTML classique — Query List | Interprétation des paramètres de présentation extraite et couverte pour attributs, classes et pagination | Commit `ff720c4dc` |
| Rendu HTML classique — Query List header | Markup des en-têtes, spans, styles et sélecteur de lignes extrait et couvert | Commit `a940525b7` |
| Rendu HTML classique — Query List cellules | Construction d’une cellule extraite et couverte pour alignements, spans, styles et contrôles de sélection | Commit `c047a0779` |
| Rendu HTML classique — Query List lignes | Boucle de ligne extraite, réutilisant le builder de cellule et conservant l’arrêt `dying` | Commit `58dec7178` |
| Rendu HTML classique — Query List pied et pagination | Markup du pied de tableau et liens de pagination extrait, avec variantes de navigation et mode compact couverts | Commit `76351beae` |
| Rendu HTML classique — Query List enveloppe | Ouverture/fermeture du conteneur et du tableau extraite et couverte avec styles, attributs et formatage historiques | Commit `bd59533be` |
| Rendu HTML classique — orchestration | Les 21 types de nœuds et Query List sont orchestrés par `ClassicFormRenderer`; `RenderingEngine::view()` ne conserve que le branchement et l'arrêt `bury()` | Commit de la Phase 18, `ClassicFormRendererTest` |
| `RenderingEngine::view()` — scripts d'icônes | Extraction committée et trois chemins `bury()` couverts | Commit `0a908143` |
| `RenderingEngine::view()` — callbacks d'éléments | Extraction committée ; ordre `init` / `action` / `validate` et trois arrêts `bury()` couverts | Commit `413cb1cb` |
| `RenderingEngine::view()` — métadonnées classiques | Comptage icônes/infobulles et scan `Static Text/HTML` extraits et couverts | Commit `51e86824` |
| `RenderingEngine::view()` — première boucle classique | Intégration callbacks, scan statique et identifiants draggable couverte | Commit `a254e895` |
| ContentBuilder — valeurs éditables | Générateurs d’hydratation par famille créés, couverts et branchés dans `view()` | Commits `f685ff5e`, `c2ae9a76`, `084fc749`, `e8d531cd`, `152def4c` |
| ContentBuilder — champs non éditables | Générateur indépendant créé, couvert et branché dans `view()` | Commits `8bfd520e`, `21a0a812` |
| ContentBuilder — résolution des signatures | Résolution, lecture et encodage des fichiers isolés et testés | Commits `b31b5c47`, `e9386794` |
| ContentBuilder — orchestration des permissions | Création, édition/nouveau et accès `fullarticle` délégués à `ContentBuilderPermissionChecker`, avec adaptateur et contrat local testés | Commits `c367b9505`, `f3b75cd74` |
| ContentBuilder — chargement d’enregistrement | Calcul des scopes propriétaire/langue et contrôle 404 délégués à `ContentBuilderRecordLoader`, avec variantes frontend/admin et nouveau testées | Commit `c6e5855ec` |
| ContentBuilder — loaders runtime | Le smoke Joomla insère puis relit une association publiée avec les loaders BFNG via l’API Database réelle, avant nettoyage de la fixture | Commit `3e22ac75d` |
| ContentBuilder — source/enregistrement runtime | Le smoke résout une source BreezingForms réelle via `FormSourceFactory` et exerce `ContentBuilderRecordLoader` sur le parcours nouveau, avec nettoyage de la fixture | Validé par le smoke Joomla |
| Notifications — exports et envoi de mails | `NotificationEngine` reçoit directement `ExportEngine`; exports, mails et traductions ne repassent plus par la façade | Phase 22, `NotificationEngineArchitectureTest` |
| Soumission — opérations internes et uploads | Le pipeline utilise directement ses opérations de collecte/enregistrement et `UploadRuntime`, sans round-trip par la façade | Phase 23, `SubmissionEngineArchitectureTest` |
| Soumission — orchestration des moteurs | Le pipeline reçoit directement `ScriptingEngine`, `ExportEngine` et `NotificationEngine` pour les pièces, exports et notifications | Phase 24, `SubmissionEngineArchitectureTest` |
| QuickMode — hydratation des valeurs enregistrées | La recherche de lignes, l’exécution de `data1/data2`, les traductions et l’état checkbox sont mutualisés pour les trois renderers actifs | Phase 25, `QuickModeSubmittedValueHydratorTest` |
| Bootstrap Joomla — dispatcher et autoload Composer | `EngineDispatcher` est enregistré dans le provider Joomla 6, le front controller le résout par conteneur, et le smoke/package validator vérifient l’autoload installé de Securimage/TCPDF | Phase 26, `EngineDispatcherContainerTest` et `JoomlaInstallSmokeScriptTest` |
| Bootstrap Joomla — graphe interne du dispatcher | Le renderer, les callbacks et leurs services partagés sont enregistrés dans le provider puis injectés dans `EngineDispatcher`, sans constructions manuelles dans le dispatcher | Phase 27, `EngineDispatcherContainerTest` |
| Paiements — téléchargement des fichiers payants | `PaymentDownloadService` et sa politique sont enregistrés dans le conteneur puis partagés par les callbacks PayPal, Stripe et Sofort, sans construction locale | Phase 28, `PaymentCallbackRegressionTest` |
| Nettoyage — fichiers temporaires et cache de paiement | Le parcours commun de purge est regroupé dans `UploadFileCleaner`, injecté dans `FormRenderer`, avec les deux règles de nommage historiques conservées | Phase 29, `UploadFileCleanerTest` |
| QuickMode — consolidation des builders mono-appelant | Les attributs d’image paiement, le markup checkbox et les deux fragments internes `FilesAdded` sont repliés dans leurs appelants ; le composeur upload partagé est conservé | Phase 30, tests QuickMode fusionnés |
| Admin — préparation commune des bibliothèques de packages | `PieceModel` et `ScriptModel` délèguent leur préparation de liste à `PackageModel`, avec uniquement leur table et leur préfixe de session spécifiques | Phase 31, `PackageLibraryUiRegressionTest` |
| Rendu — cycle de formulaire et champs techniques | L’enveloppe des trois modes est regroupée dans `FormEnvelopeMarkupBuilder` et les champs optionnels/ContentBuilder rejoignent `HiddenFormFieldsBuilder` | Phase 32, tests de rendu fusionnés |
| Rendu — pagination Query List | Le rafraîchissement des lignes, la navigation et la fin de pagination sont regroupés dans `QueryListPageScriptBuilder`, seul assembleur de la callback complète | Phase 33, `QueryListPageScriptBuilderTest` |
| ContentBuilder — métadonnées de formulaire | Les deux chargeurs de métadonnées sont regroupés dans `ContentBuilderFormMetadataLoader`, avec leurs requêtes association/définition conservées | Phase 34, `ContentBuilderFormMetadataLoaderTest` |
| ContentBuilder — hydratation des enregistrements | Les scripts d’édition, de fichiers, de signature, de valeurs, de choix, de listes et de lecture seule sont regroupés dans `ContentBuilderHydrationScriptBuilder` | Phase 35, `ContentBuilderHydrationScriptBuilderTest` |
| PHPCS | Actif sur les services modernes, les builders ContentBuilder, Classic et `HiddenFieldTrait` | `phpcs.xml.dist`, 155 fichiers configurés |
| PHPStan | Niveau 4 validé sur le composant, sans diagnostic résiduel | `phpstan.neon.dist`, baseline vide |
| Navigation des enregistrements admin | Les liens précédent/suivant réutilisent le formulaire, la recherche et le tri de la liste courante ; l'état est conservé pendant l'édition | `RecordsModel::getAdjacentRecordId()`, `tests/Administrator/RecordsNavigationTest.php` |

## Phase 1 — Terminer la préparation des éléments classiques

### 1.1 Stabiliser l'extraction Query List

État : extraction et couverture committées dans `4070ec0f` et `b358e7e9`.

Déjà couvert :

- colonnes visibles et masquées ;
- pagination personnalisée et taille de page ;
- export de lignes non vides.

La couverture vérifie les modes avec et sans checkbox, la pagination par
défaut ou personnalisée et les résultats vides ou non vides. L'appel à
`bury()` reste dans le flux de `view()` au même endroit qu'avant l'extraction.

Critère de sortie : `view()` délègue toute la préparation d'une ligne
`Query List`, et le JavaScript produit reste identique.

### 1.2 Extraire la préparation des scripts des éléments

État : extraction des scripts d'icônes committée dans `0a908143`, des callbacks
dans `413cb1cb` et des métadonnées/du scan statique dans `51e86824`. Les trois
chemins `bury()` des callbacks et du registre d'icônes sont couverts.

- Ajouter les tests d'intégration des variantes Query List et `bury()` de la
  première boucle.

Critère de sortie : la première boucle sur les éléments ne contient plus de
construction de script directement dans `view()`.

## Phase 2 — Extraire l'édition et l'intégration ContentBuilder

Cette phase doit rester distincte du rendu HTML classique. Elle manipule des
données, construit du JavaScript d'hydratation et dépend des permissions
ContentBuilder.

### 2.1 Chargement d'un enregistrement BreezingForms éditable

État : `EditableRecord` et `EditableRecordLoader` sont extraits et branchés
dans `RenderingEngine::view()` par `89165de7`. Le loader est couvert pour
l'absence de résultat, le chargement des sous-enregistrements et les filtres
SQL critiques via `dc68404bb` et `fd27c67dc` ; le contrat SQL est désormais
isolé derrière un service, y compris la liaison du sous-enregistrement et le
cas utilisateur invité. La génération du JavaScript `bfLoadEditable()` est maintenant
extraite dans `EditableRecordHydrationScriptBuilder` (`a8325a7c`) et couverte
pour les champs simples, checkbox/radio, listes, valeurs vides et types
inconnus. Son enveloppe de chargement et le nettoyage historique de
`ff_nm_seccode[]` sont maintenant isolés dans
`EditableRecordScriptWrapperBuilder` (`f3b6f8d97`) ; le nettoyage
`InputFilter` est injecté dans le builder sous forme de closure, sans mutation
des entrées. Les valeurs réinjectées dans les contrôles checkbox/radio et les
listes utilisent en outre un encodage JavaScript avec protection des balises
HTML ; les séquences `</script>` sont couvertes par un test de régression.
Le nom complet des champs checkbox/radio est encodé par la même voie, avec un
test dédié contre les guillemets et séparateurs JavaScript.

- La recherche du dernier enregistrement, les requêtes
  `#__facileforms_records`/`#__facileforms_subrecords` et le résultat typé sont
  désormais portés par `EditableRecordLoader`.
- Caractériser encore l'enregistrement archivé, l'utilisateur invité et le
  parcours complet dans `view()` lorsque le runtime Joomla est disponible.

Critère de sortie : `RenderingEngine` ne construit plus directement les
requêtes de chargement d'un enregistrement éditable.

### 2.2 Génération des valeurs éditables BreezingForms

État : générateur indépendant committé dans `f685ff5e` et branché dans
`RenderingEngine::view()` par `c2ae9a76`, avec l'enveloppe
`bfLoadEditable()` isolée dans `f3b6f8d97`.

- Extraire le JavaScript de remplissage des champs simples : texte, textarea,
  nombre, champ caché et calendrier.
- Extraire les stratégies checkbox, groupe de checkbox, radio et select.
- Conserver le nettoyage `InputFilter` et le déclenchement des événements
  `change`.
- Caractériser les valeurs vides, Unicode, HTML nettoyé et valeurs multiples.

Critère de sortie : le bloc `bfLoadEditable()` est produit par un service pur à
partir d'une collection d'entrées.

### 2.3 Génération des valeurs ContentBuilder

État : générateurs indépendants pour valeurs simples/non éditables, committés
dans `f685ff5e`, `8bfd520e`, `c2ae9a76` et `21a0a812`. Les générateurs de
signatures et de contrôles de fichiers sont maintenant extraits et branchés
dans `view()` par `4f475561` et `2c72231e`, avec couverture dédiée.

- Séparer les stratégies par type de champ :
  - valeurs simples et calendriers ;
  - checkbox et groupes de checkbox ;
  - radio et groupes radio ;
  - listes de sélection ;
  - fichiers ;
  - signatures.
- La résolution du fichier existant est isolée dans
  `ContentBuilderSignatureFileResolver` via `b31b5c47`, et l'encodage est
  désormais confié à `ContentBuilderSignatureImageEncoder` via `e9386794`.
  Le service de génération JavaScript reçoit une image déjà encodée.
- L'hydratation JavaScript des valeurs simples et des calendriers est
  désormais isolée dans `ContentBuilderValueHydrationScriptBuilder` via
  `084fc749`, avec conservation du fallback JQuery/native et du délai des
  calendriers.
- L'hydratation des checkbox/radio est isolée dans
  `ContentBuilderChoiceHydrationScriptBuilder` via `e8d531cd`, et celle des
  listes dans `ContentBuilderSelectHydrationScriptBuilder` via `152def4c` ;
  les valeurs multiples, l'échappement JSON et les déclenchements historiques
  sont couverts par des tests unitaires.
- L'enveloppe `bfLoadContentBuilderEditable`, le compteur Flash et le nettoyage
  legacy du champ seccode sont désormais assemblés par
  `ContentBuilderEditableScriptWrapperBuilder` via `ec113799`, avec les scripts
  de validation et d'hydratation injectés sans changement de contrat.
- Isoler la présentation des fichiers existants et les cases de suppression.
- Vérifier explicitement les chemins, noms de fichiers et valeurs absentes.
- Caractériser `bfLoadContentBuilderEditable()` avant toute normalisation du
  JavaScript historique.

Critère de sortie : l'hydratation ContentBuilder n'est plus implémentée dans
`view()` et chaque famille de champs possède un test ciblé.

Le générateur de contrôles de fichiers est traité par `2c72231e`. La lecture
et la normalisation des fins de ligne de `recValue` sont désormais isolées
dans `ContentBuilderFileValueParser` via `b426525c`, avec conservation du
comptage historique testé. La résolution du nom affiché est désormais isolée
dans `ContentBuilderFileDisplayNameBuilder` via `40c7fd89`; l'échappement des
balises inattendues et la restauration des `<br>` sont couverts. La
validation QuickMode est désormais centralisée dans
`ContentBuilderFlashUploadValidationBuilder` via `6d4afd92`, avec émission
unique du callback `ff_flashupload_not_empty`. Les parcours runtime complets
des fichiers sont désormais exercés par le smoke Joomla/ContentBuilder
(`fe1d09532`), qui vérifie le comptage et le markup des fichiers existants. La
phase de restauration JavaScript des contrôles de fichiers est désormais
isolée dans `ContentBuilderFileHydrationScriptBuilder` via `757f27446`.

La résolution et l'encodage des fichiers de signature couvrent explicitement
les valeurs vides, les fichiers absents et les fichiers présents ; la lecture
et l'encodage sont isolés dans des services unitaires et branchés dans
`RenderingEngine` via `e9386794`. Le parseur de valeurs de fichiers couvre
également les fins de ligne CRLF, les lignes vides et les noms Unicode via
`e8582b59`. Le smoke Joomla/ContentBuilder (`fe1d09532`) restaure désormais
une signature depuis un fichier réel, encode son contenu et supprime la
fixture dans un bloc `finally`.

Pour le CAPTCHA, la construction des endpoints image, validation legacy et
ReCaptcha est désormais isolée dans `CaptchaEndpointBuilder` via `8e3e9a7a`.
L’URL de base du champ CAPTCHA réutilise également ce service via `4563ae11`,
ce qui supprime la dernière construction directe de cet endpoint dans
`RenderingEngine`.
La sélection du nœud de validation est isolée dans
`CaptchaValidationRowSelector` (`d328c4f8`) : le premier `Captcha` est
prioritaire et le dernier `ReCaptcha` est conservé, comme dans le flux
historique. Les valeurs par défaut sont produites par
`CaptchaValidationDefaultsBuilder` (`41612fa2`). Les deux générateurs de
JavaScript sont désormais extraits dans `CaptchaLegacyValidationScriptBuilder`
et `CaptchaReCaptchaValidationScriptBuilder` (`75c0ca2b`, `1cea0c84`) ; leurs
appels sont orchestrés par `CaptchaValidationScriptBuilder`, avec tests
du callback par défaut, du CAPTCHA legacy et du ReCaptcha ; les
variantes et leurs interpolations sont couvertes par les tests unitaires et le
test de caractérisation de `RenderingEngineViewCharacterizationTest`.
Un test d’architecture vérifie en outre que le JavaScript de validation ne
réintègre pas `RenderingEngine`.
La phase 2.3 est donc couverte pour les scripts de signature et de contrôles
de fichiers, y compris leurs dépendances runtime Joomla/ContentBuilder.

Les requêtes d'association et de définition des formulaires ContentBuilder
sont désormais regroupées dans `ContentBuilderFormMetadataLoader`, avec
vérification des filtres `type`, `reference_id` et `published`, des bindings
entiers et du cas `null`. Les étapes de permission et de chargement
d'enregistrement sont validées par le runtime ContentBuilder ; la matrice ACL complète reste suivie comme recette d'intégration dédiée.

### 2.4 Champs ContentBuilder non éditables

État : générateur indépendant committé dans `8bfd520e` et branché dans
`RenderingEngine::view()` par `21a0a812`, en respectant le cycle de création de
`bfDeactivateField`. Le critère de sortie est atteint ; la récupération
runtime des identifiants reste dans `view()` et est couverte par le smoke
Joomla/ContentBuilder.

La récupération est désormais encapsulée par
`ContentBuilderNonEditableFieldsResolver`, testée avec un loader injectable et
utilisée par les deux parcours de `view()`. Sa validation runtime est désormais
couverte par le smoke Joomla ; seule la recette ACL complète reste ouverte.

- [x] Extraire le script de désactivation et de masquage des contrôles.
- [x] Couvrir les champs avec contrôle visible, sans contrôle visible et les
  groupes de contrôles.
- [x] Préserver les règles de lecture seule et les suffixes frontend/admin des
  permissions.
- [x] Extraire et valider la récupération des identifiants non éditables dans le smoke Joomla/ContentBuilder (`ContentBuilderNonEditableFieldsResolver`).

Critère de sortie : le script `bfDisableContentBuilderFields()` est construit
et testé indépendamment de `view()`.

## Phase 3 — Découper le rendu HTML classique

Le rendu classique doit être migré après les scripts d'édition afin de réduire
la quantité d'état implicite traversant la grande boucle actuelle.

### 3.1 Créer le contexte de rendu

- Regrouper les dépendances de lecture nécessaires au rendu d'un nœud :
  processeur, ligne, page, valeurs courantes et options d'affichage.
- Éviter un tableau générique non documenté : utiliser un objet typé ou un
  ensemble réduit de paramètres explicites.
- Ne pas déplacer les écritures globales ou les sorties `echo` sans test de
  caractérisation.

### 3.2 Extraire les familles de nœuds

Ordre conseillé, du plus simple au plus risqué :

1. Texte statique, rectangle, image, infobulle et icône
   (`ClassicStaticTextBuilder` extraits par `43bc8b9d`, `ec310eabb`,
   `08f6bc4ee`, `1e23287f8` et `3acfe4d66`) ; le champ caché est isolé par
   `ClassicHiddenInputBuilder` (`0bacb6fc7`). Les champs techniques viennent
   ensuite.
2. Champ texte (`ClassicTextInputBuilder`, `24776d379`) et textarea
   (`ClassicTextareaBuilder`, `593e018b5`), puis nombre et champ caché.
3. Contrôle commun checkbox/radio (`ClassicChoiceBuilder`, `fcc72f2aa`), puis
   liste de sélection (`ClassicSelectBuilder`, `771f3d014`). Les groupes
   restent représentés par les contrôles d’options élémentaires.
4. Bouton régulier (`ClassicRegularButtonBuilder`, `974fcf0db`) et bouton
   graphique (`ClassicGraphicButtonBuilder`, `cf09129e0`), puis navigation
   entre pages.
5. Upload classique (`ClassicFileUploadBuilder`, `205127c81`) et CAPTCHA
   (`ClassicCaptchaBuilder`, `c260c2eb9`), puis paramètres Query List
   (`ClassicQueryListSettingsBuilder`, `ff720c4dc`), en-tête
   (`ClassicQueryListHeaderBuilder`, `a940525b7`), cellules
   (`ClassicQueryListCellBuilder`, `c047a0779`), puis boucle des lignes
   (`ClassicQueryListRowBuilder`, `58dec7178`) et pied/pagination
   (`ClassicQueryListFooterBuilder`, `76351beae`), puis enveloppe du tableau
   (`ClassicQueryListMarkupBuilder`, `bd59533be`).
6. Query List et ses tableaux paginés.

Chaque famille doit disposer d'un snapshot ou d'assertions structurelles avant
son extraction.

### 3.3 Extraire la sélection du renderer QuickMode

- Conserver dans `RenderingEngine` uniquement le choix Classic, Bootstrap,
  Mobile ou OnePage.
- Déplacer la préparation du contexte dans une factory ou un service dédié.
- Conserver le choix de version mobile et le comportement de soumission.

Critère de sortie : `view()` orchestre le rendu mais ne construit plus le HTML
d'un champ ou d'un nœud.

## Phase 4 — Finaliser le formulaire et le cycle de rendu

### 4.1 Scripts post-rendu

La garde d'entrée de `RenderingEngine::view()` impose désormais le mode
QuickMode avant le chargement des métadonnées ; la condition interne
redondante a été supprimée afin que l'orchestrateur n'entretienne pas un
chemin impossible.

État : `PostRenderScriptBuilder` extrait et branché dans `view()` par
`ad9dd75f`, avec couverture des trois fonctions différées et de leur garde
JQuery/`bfToggleFieldsLoaded`.

- Extraire l'appel différé à `bfLoadEditable()`.
- Extraire l'appel différé à `bfLoadContentBuilderEditable()`.
- Extraire l'appel à `bfDisableContentBuilderFields()`.
- Caractériser les variantes avec et sans jQuery et ToggleFields.

### 4.2 Champs techniques et paiement

État : le builder pur du champ `ff_payment_method` est extrait et branché par
`3d45e1e2`. La détection des types de paiement est désormais isolée dans
`PaymentProviderDetector` et branchée dans `view()` par `b5d1a55e`, avec les
trois providers couverts. Les champs de contexte ContentBuilder
(`cb_form_id`, `cb_record_id`, `cbIsNew`) sont maintenant produits par
`ContentBuilderTechnicalFieldsBuilder`, branché dans les trois modes par
`c53a457e`.

- Détection PayPal, Sofort et Stripe extraite dans `PaymentProviderDetector`
  (`b5d1a55e`).
- Champ caché `ff_payment_method` extrait dans `PaymentMethodFieldBuilder`
  (`3d45e1e2`).
- Paramètres ContentBuilder transmis au formulaire extraits dans
  `ContentBuilderTechnicalFieldsBuilder` (`c53a457e`).
- Paramètres additionnels et jeton CSRF Joomla extraits respectivement dans
  `AdditionalHiddenFieldsBuilder` (`bb469b88`) et `FormTokenFieldBuilder`
  (`462b2984`).

### 4.3 Variantes frontend, backend et preview

État : les champs cachés communs sont mutualisés par
`FormSubmissionFieldsBuilder` (`be602b94f`) et les sorties des trois variantes
sont verrouillées par `dc73ec0fc`. Les différences de route, iframe, cible,
bordure et template restent dans les branches d'orchestration. Les parcours
frontend, backend, preview et Query List sont désormais caractérisés jusqu'à
la fermeture du formulaire dans `RenderingEngineViewCharacterizationTest`.
La validation runtime est effectuée sur Joomla/ContentBuilder ; la matrice ACL complète reste suivie comme recette dédiée.

- [x] Caractériser et valider la finalisation par mode d'exécution après la
  caractérisation runtime Joomla.
- [x] Conserver les différences de route, iframe, cible, bordure et template.
- [x] Étendre les tests de sortie au parcours ContentBuilder.

### 4.4 Fermeture et traçage

État : les sorties anticipées nettoient désormais les tampons et restaurent le
gestionnaire d'erreurs via `abortViewRendering()` (`2d4f75ef`). La finalisation
normale et le vidage du trace buffer sont regroupés dans
`finishViewRendering()` (`ced03d7a`), avec tests de caractérisation des deux
ordres de traçage.

- [x] Couvrir les tests des chemins ContentBuilder et Query List.
- Surveiller les variantes où Joomla interrompt le rendu pendant un callback.

Critère de sortie de la phase : `RenderingEngine::view()` devient une méthode
d'orchestration courte, composée d'étapes nommées et testées.

## Phase 5 — Mutualiser les quatre renderers QuickMode

Les snapshots des quatre renderers constituent les filets de sécurité. La
mutualisation peut donc avancer par type de champ, sans modifier les wrappers
propres à chaque thème.

### État de la mutualisation

- `HiddenFieldTrait` : partagé par les quatre renderers.
- `BootstrapStyleFieldTrait` : partagé par Bootstrap et OnePage.
- Classic : wrappers et logique d'enveloppe encore largement spécifiques ;
  Bootstrap et OnePage partagent déjà leur trait.
- Les trois classes restent volumineuses, pour un total d'environ 4 200
  lignes avec leurs traits.

### Stratégie cible

- Introduire une stratégie par type ou famille de champs.
- Séparer le HTML du contrôle de son enveloppe de thème.
- Fournir à la stratégie un contexte typé : nom, identifiant, valeur, état
  readonly, événements, tabindex et options spécifiques.
- Laisser chaque renderer construire ses wrappers, classes CSS et disposition.
- Ne pas créer une classe de base massive remplaçant une duplication par une
  nouvelle façade.

### Ordre de migration

1. `bfTextfield` et `bfNumberInput` — partagé et branché via
   `QuickModeInputBuilder` (`065cef94`, `f3d04e55`).
2. `bfTextarea` et compteur de longueur — le contrôle textarea est partagé et
   branché via `QuickModeTextareaBuilder` (`620b7efe`); le compteur est
   désormais partagé via `QuickModeMaxLengthCounterBuilder` (`bdb7d910`).
3. `bfCheckbox` — partagé et branché via `QuickModeCheckboxBuilder`
   (`18a3e7ea`).
4. `bfSelect` — partagé et branché via `QuickModeSelectBuilder`
   (`8a891fb5`).
5. Groupes checkbox et radio — contrôle d'option partagé via
   `QuickModeGroupOptionBuilder` (`a85bed34`); les enveloppes, labels et règles
   `wrap` restent propres aux renderers.
6. Boutons et champs cachés restants — `bfHidden` était déjà partagé par
   `HiddenFieldTrait`; le bouton de champ `bfSubmitButton` est désormais
   partagé via `QuickModeSubmitButtonBuilder` (`92b4af6f`). Les boutons de
   navigation générale utilisent désormais les actions partagées de
   `QuickModePagingActionBuilder` (`10d7dc6a`, `04e09cf6`), y compris
   l’annulation. Les variantes de pages intermédiaires et finales des quatre
   renderers sont maintenant caractérisées par `063c39e7`; les enveloppes de
   thème restent propres à chaque renderer.
7. Calendriers et calendriers responsives — le bouton de déclenchement est
   partagé via `QuickModeCalendarButtonBuilder` (`7911701f`), et l'input texte
   est partagé via `QuickModeCalendarInputBuilder` (`1d623384`). Les
   initialisations Pickadate et responsive sont désormais centralisées via
   `QuickModeCalendarInitScriptBuilder` (`1de4b129`).
8. Uploads, CAPTCHA, signatures et paiements — l'URL de l'image CAPTCHA est
   désormais partagée via `QuickModeCaptchaUrlBuilder` (`21afa208`) et le
   markup image/réponse via `QuickModeCaptchaMarkupBuilder` (`176af451`) et le
   script de rechargement via `QuickModeCaptchaReloadScriptBuilder`
   (`4d706e95`). Les actions de navigation et de soumission sont maintenant
   mutualisées via `QuickModePagingActionBuilder` (`10d7dc6a`, `04e09cf6`) et
   `QuickModeSubmitActionBuilder` (`4dd3e3dd`). La miniature image est
   maintenant partagée via `QuickModeUploadThumbnailScriptBuilder`
   (`704ccbea2`) ; le markup et les callbacks de configuration complète de
   l'uploader sont désormais extraits par sous-lots et couverts par tests.
   Les enveloppes visuelles restent dans les renderers ; le callback AJAX
   OnePage est maintenant isolé dans `QuickModeAjaxSubmitScriptBuilder`, avec
   ses branches succès/échec caractérisées.

Pour chaque type :

1. Vérifier les snapshots des quatre renderers.
2. Ajouter les variantes manquantes : valeur préremplie, readonly, événements
   et attributs optionnels.
3. Extraire la stratégie partagée.
4. Relancer tous les snapshots.
5. Supprimer uniquement la duplication devenue réellement inaccessible.

Critère de sortie : chaque type de champ possède une implémentation commune du
contrôle et uniquement des adaptations d'enveloppe dans les renderers.

## Phase 6 — PHPCS et PHPStan

### 6.1 Étendre PHPCS progressivement

Le périmètre actuel couvre principalement quelques services modernes. Il ne
doit pas être étendu d'un seul coup aux grandes classes historiques.

Ordre conseillé :

1. Nouveaux services extraits de `RenderingEngine`.
2. Services ContentBuilder extraits.
3. Stratégies de champs QuickMode.
4. Traits QuickMode déjà mutualisés, en commençant par `HiddenFieldTrait`.
5. Renderers, un par un après réduction de leur taille.
6. `RenderingEngine` lorsque les blocs historiques principaux ont disparu.

Chaque ajout au périmètre PHPCS doit être accompagné des corrections du seul
groupe concerné, sans reformatage transversal.

Le périmètre PHPCS couvre désormais aussi les services extraits de CAPTCHA,
ContentBuilder et les actions QuickMode (`CaptchaEndpointBuilder`, builders de
fichiers/champs cachés, `ContentBuilderSignatureFileResolver`,
`QuickModePagingActionBuilder` et `QuickModeSubmitActionBuilder`). Ces huit
fichiers passent PHPCS sans erreur ni warning. Le ruleset complet passe
désormais également sans erreur ni warning (`61/61`), après le nettoyage ciblé
des traits et builders QuickMode ; aucun reformatage global n'a été appliqué.

Les stratégies de contrôles déjà mutualisées (`QuickModeTextFieldStrategy`,
`QuickModeTextareaStrategy` et `QuickModeCheckboxStrategy`) sont désormais
incluses explicitement dans le périmètre PHPCS. Elles disposent chacune d'un
test direct et passent le contrôle sans correction de sortie nécessaire ; ce
lot étend uniquement le filet de qualité à du code déjà stabilisé.

Le parseur pur `QuickModeToggleFieldsParser`, déjà couvert par son test direct,
rejoint également ce périmètre PHPCS afin que la mutualisation des règles de
bascule soit contrôlée au même niveau que les stratégies de champs.

Les autres builders QuickMode purs déjà couverts par des tests directs sont
également ajoutés au ruleset : soumission, calendrier, messages, progression
et callbacks d'upload. `QuickModePaymentImageBuilder` a reçu uniquement une
mise en forme de son attribut optionnel ; sa sortie reste inchangée.

Le périmètre inclut maintenant aussi les services de rendu extraits et propres
déjà couverts (`Captcha*`, `ClassNameResolver`, les hydratateurs ContentBuilder,
les services Query List, `PaymentProviderDetector` et les builders de formulaire).
Les builders Classic historiques qui nécessitent encore des corrections de
style restent volontairement hors périmètre jusqu'à leur nettoyage ciblé.

Les deux builders Classic déjà conformes (`ClassicHiddenInputBuilder` et
`ClassicQueryListMarkupBuilder`) rejoignent également le ruleset ; les autres
builders Classic attendent un nettoyage par famille accompagné de leurs
caractérisations.

La famille Classic Query List est désormais entièrement suivie par PHPCS :
settings, header, cellule, ligne, footer et enveloppe passent le contrôle
après un découpage de quatre lignes trop longues, avec leurs tests directs
conservés comme filet de sortie.

Le double `RenderingEngineProcessorDouble` du harnais de caractérisation est
désormais isolé dans son propre fichier de test. Le fichier principal respecte
ainsi la règle PHPCS d'une classe par fichier ; ses avertissements de longueur
de ligne ont également été supprimés sans modifier les sorties caractérisées.

Les contrôles Classic de saisie (`TextInput`, `Textarea` et `Select`) sont
également passés sous PHPCS après le découpage neutre d'une ligne trop longue
dans chaque builder ; leurs tests directs couvrent toujours leurs sorties.

Les contrôles Classic de choix, bouton régulier, upload et bouton graphique
sont maintenant suivis par PHPCS après découpage syntaxique neutre de leurs
chaînes longues ; leurs tests directs restent le filet de caractérisation.

Le builder Classic CAPTCHA rejoint également le périmètre après découpage
neutre de ses chaînes d'attributs et de script de rechargement ; son test
direct conserve la couverture des dimensions et de l'endpoint historique.

Le builder Classic de texte statique, d'icône, rectangle et infobulle est
également passé sous PHPCS après découpage neutre de ses chaînes de rendu ;
ses dix tests directs verrouillent les variantes de layout et d'échappement.

Les builders de cycle du formulaire (`FormOpeningMarkupBuilder`,
`FormClosingMarkupBuilder`, `FormModeFinalizationBuilder` et
`FormOptionalContextFieldsBuilder`) sont désormais inclus dans le périmètre
PHPCS ; leurs tests unitaires dédiés fournissent déjà la caractérisation des
variantes de mode et de contexte.

Les builders mutualisés de champs cachés et d’habillage des scripts historiques
(`HiddenFormFieldsBuilder` et `LegacyScriptTagWrapperBuilder`) rejoignent aussi
PHPCS après un nettoyage syntaxique limité ; leurs tests unitaires existants
conservent la caractérisation des fragments générés.

`FileExtensionsCheckBuilder` est désormais inclus dans PHPCS après découpage
aux seuls points de concaténation PHP ; son test direct conserve la couverture
du comptage des uploads et des variantes de validation JavaScript.

`EditableRecordHydrationScriptBuilder` rejoint également le périmètre après
factorisation locale des sélecteurs d’options JavaScript et découpage des
concaténations longues ; ses cinq tests couvrent les familles de contrôles,
les valeurs vides et l’encodage des valeurs réinjectées.

Le resolver isolé `MailTemplateResolver`, déjà couvert par son test unitaire,
rejoint également le périmètre PHPCS ; cette extension contrôle une dépendance
de notification extraite sans ouvrir la façade historique complète.

Le premier filet unitaire direct des callbacks de paiement est en place pour
`PayPalCallback::requestVerification()` : la requête cible l’endpoint IPN avec
les en-têtes attendus, la réponse est normalisée et une panne réseau produit
une chaîne vide sans accès réseau réel (`PayPalCallbackTest`). Les traitements
complets PayPal, Sofort et Stripe ont ensuite été caractérisés dans la
Phase 21 ; leur logique fournisseur reste dans les callbacks.

La règle commune de limite des téléchargements payants est maintenant portée
par `PaymentDownloadPolicy`, injectée dans les trois callbacks PayPal, Sofort
et Stripe. Ses frontières sont testées et un test de régression vérifie que
les callbacks délèguent bien cette décision au service partagé.

Le premier nettoyage ciblé de cette baseline PHPCS est réalisé dans
`QuickModeSelectBuilder`, `QuickModeCaptchaMarkupBuilder` et
`QuickModeUploadOptionsBuilder` (`eaf8b2d1`) : leurs sorties sont inchangées et
leurs warnings de longueur sont supprimés. Le nettoyage de
`BootstrapStyleFieldTrait` est désormais terminé dans les lots
`d1638610`, `9a93d10c` et `aaabc40c`.

Le nettoyage du trait Bootstrap a couvert les méthodes de résumé, calendrier,
champs, confirmation, paiement, signature et groupes (`750dd432`, `6b0a8532`,
`82b42a90`, `d1638610`, `9a93d10c`, `aaabc40c`) ; leurs sorties restent
couvertes par les snapshots. Le compteur est passé de 64 à 0 warnings de
longueur pour ce trait. Les quatre renderers passent toujours la
caractérisation PHPUnit et le sous-périmètre QuickMode passe PHPStan.

Les fixtures de caractérisation Bootstrap et OnePage fournissent désormais
toutes les classes de thème demandées par les chemins de navigation. La suite
PHPUnit ne produit plus les 11 warnings de clés absentes et termine sans
warning ; PHPCS et PHPStan restent également verts.

### 6.2 Réduire la baseline PHPStan

- Classer les 11 entrées actuelles par fichier et par catégorie.
- Corriger d'abord les erreurs dans les services extraits et les nouveaux DTO.
- Distinguer les défauts des stubs Joomla des erreurs réelles du composant.
- Ne jamais ajouter une entrée de baseline pour un nouveau code.
- Supprimer les entrées devenues obsolètes après chaque lot.
- Passer progressivement du niveau 2 aux niveaux supérieurs une fois les
  groupes principaux nettoyés.

Critère de sortie : aucun nouveau service n'est couvert par la baseline et le
nombre d'entrées diminue à chaque phase de migration.

Les trois diagnostics `Variable $mdata might not be defined` des renderers
QuickMode ont été supprimés par une initialisation explicite du parent à
`null`, sans changer le parcours récursif. La baseline passe ainsi de 137 à
134 entrées ; PHPStan est validé sans baseline sur ces trois diagnostics et
avec la baseline complète sur l’ensemble du composant.

Dans la boucle de rendu classique, les paramètres `data1`, `data2` et `data3`
sont maintenant réinitialisés pour chaque ligne avant le traitement des
éléments. Les 23 occurrences PHPStan correspondantes sont supprimées de la
baseline, sans réutilisation accidentelle des valeurs d’un élément précédent.

Les deux chemins d'erreur de `ScriptingEngine` utilisent maintenant le nom de
l'élément de requête (`$elem->name`) pour leur message et leur trace, au lieu
d'une variable `$name` inexistante. Les quatre diagnostics correspondants sont
retirés de la baseline ; le niveau 2 complet reste vert.

Les méthodes `del()` de `PieceManager` et `ScriptManager` retournent désormais
immédiatement après leur redirection d'erreur. Elles ne testent plus `$total`
après un échec de suppression ; les deux diagnostics PHPStan correspondants
sont supprimés de la baseline.

La branche ContentBuilder de `ExportEngine` initialise désormais `created_up`
avec la date courante lorsque aucun délai de publication n'est configuré, puis
applique le décalage demandé lorsqu'il existe. Le diagnostic PHPStan associé
est supprimé ; la couverture d'intégration complète de cette branche reste
réservée au harnais Joomla/ContentBuilder.

`PdfDocument::Header()` et `PdfDocument::Footer()` initialisent désormais leur
état de police avant le scan du répertoire, afin qu'un marqueur `_active` placé
avant un fichier de police ne lise pas de variables indéfinies. Les quatre
occurrences PHPStan correspondantes sont supprimées de la baseline ; les tests
de normalisation et de scan des polices restent verts.

`IntegratorRuntime::getCriteria()` retourne maintenant une liste vide lorsqu'une
lecture SQL échoue, au lieu de retourner une variable non initialisée. Ce
contrat est verrouillé par un test avec double de base de données ; une entrée
PHPStan est ainsi supprimée.

Le parcours Stripe de `SubmissionEngine` itère désormais explicitement sur
toutes les zones du template avant de rechercher son élément de paiement,
comme les branches PayPal et Sofort. Le diagnostic PHPStan lié à l'utilisation
d'un `$area` non initialisé est supprimé et un test de caractérisation verrouille
cette boucle. L'adresse e-mail Stripe est également initialisée par le même
parcours ; sa garde isset() redondante est supprimée et couverte par le test
de régression du callback.

Dans FormRenderer, les sept gardes isset(ff_applic) ont été supprimées :
ff_applic est initialisé au début de render() avant toutes les branches qui
l'utilisent. Le comportement des sélections module/plugin et du titre de page
reste inchangé ; le contrat d'initialisation est verrouillé par
FormRendererArchitectureTest.

Dans la façade historique, measureTime() convertit maintenant explicitement
les deux composantes textuelles de microtime() en float avant l'addition. La
formule et son unité restent inchangées ; le diagnostic PHPStan correspondant
est supprimé et le contrat de conversion est couvert par CodeToolsRuntimeTest.

Le script d'installation documente désormais correctement le contrat booléen de
preflight(), qui est vérifié par InstallerScriptArchitectureTest. Le diagnostic
PHPStan de PHPDoc incompatible est supprimé sans modifier le cycle Joomla.

Les deux appels de désinstallation du plugin historique utilisent désormais la
signature Joomla 6 à deux arguments de Installer::uninstall(). Le troisième
argument d'application, obsolète, est supprimé et les deux diagnostics PHPStan
associés sont éliminés ; le test du script vérifie l'absence de cette forme.

Les fichiers uploadés de `SubmissionEngine` alimentent désormais le buffer
`sfdata`, consommé par les notifications Salesforce, au lieu d'une propriété
`sfadata` inexistante (`33b7fb4d5`). Le diagnostic PHPStan et sa régression
structurelle sont supprimés de la baseline. Celle-ci compte maintenant
11 entrées. Le double opt-in de `SubmissionEngine` documente désormais le
contrat concret `Joomla\\CMS\\Mail\\Mail` déjà utilisé par `MailSender` pour
`isHtml()`, ce qui supprime le dernier diagnostic de type mailer de la
baseline. Le parcours des textarea HTML de `ClassicRenderer` utilise
désormais l'identifiant d'instance Joomla (`ff_elem...`) et
`QuickModeEditorValueBuilder`, comme les parcours Bootstrap et OnePage. L'appel
à `Editor::getContent()` n'étant pas disponible dans l'API Joomla 6, il a été
supprimé et sa régression est couverte par
`ClassicRendererEditorValueTest`.

Le compteur de colonnes fusionnées de `ClassicQueryListCellBuilder` convertit
désormais explicitement `thspan` en entier avant d'alimenter son paramètre par
référence `skip`. Cette normalisation supprime les diagnostics PHPStan du
niveau 3 sans modifier le markup Query List ; la valeur dynamique est couverte
par `ClassicQueryListCellBuilderTest`.

Les contrats déjà typés de l'administration Joomla 6 sont maintenant utilisés
directement dans `QuickmodeHtml`, `ImportModel`, `QuickmodeModel` et les
templates About : les tests de type redondants, accès nullsafe impossibles et
catch d'exception jamais levée sont supprimés. Les chemins JSON QuickMode
valides et invalides sont couverts par `QuickmodeHtmlTest`. Le niveau 4 passe
À ce stade intermédiaire, la baseline niveau 2 comptait encore 11 entrées ;
les lots suivants ont depuis conduit à une baseline vide. Le même audit a ensuite supprimé l'état `formId` jamais relu de
`IntegratorRuntime` et le factory privé `SubmissionEngine::getEvent()` jamais
appelé ; le niveau 4 est ainsi ramené à 50 diagnostics sans ajouter de
suppression artificielle dans la baseline.

Dans `ExportEngine`, la branche inconditionnelle de reconstruction des données
de fichiers est supprimée et la détection du marqueur PDF utilise uniquement
les offsets éventuellement absents. Le parcours d'export conserve ses sorties
et le test de régression vérifie que cette branche morte ne revient pas.
Cette correction supprime les trois diagnostics PHPStan niveau 4 du service ;
le total restant est de 47 diagnostics.

Les renderers Classic, Bootstrap et OnePage simplifient ensuite la sélection
des erreurs par défaut et suppriment les tests de présence de feuille de thème
dupliqués à l'intérieur de leur garde déjà validée. `FormRenderer` supprime
également une condition `pagetitle` redondante dans sa branche englobante ; les
chemins de rendu restent couverts par les caractérisations QuickMode et le
niveau 4 est ramené à 41 diagnostics.

La façade `HTML_facileFormsProcessor` documente maintenant explicitement les
délégués qui mutent son état ou leurs paramètres avec `@phpstan-impure`
(`bury`, callbacks, traitement des scripts et fonctions de normalisation).
PHPStan ne mémorise plus à tort un état antérieur à travers ces appels ; le
contrat est verrouillé par `PublicFacadeApiTest`. Cette correction retire dix
faux diagnostics d'analyse et porte le niveau 4 à 31 diagnostics, sans
modifier la façade publique.

Les cinq accès nullsafe suivis d'une coalescence dans `ScriptingEngine` sont
également simplifiés : l'opérateur `??` protège déjà l'accès à la propriété
dans ce contexte. Les chemins de génération des callbacks restent couverts
par les caractérisations `RenderingEngine::view()` ; le niveau 4 descend à 26
diagnostics. Dans `SubmissionEngine`, les listes de valeurs et le marqueur de
chemin relatif sont ensuite utilisés selon leurs contrats déjà établis, sans
tests de type ou de booléen redondants ; le niveau 4 descend à 21 diagnostics.

La méthode privée `RenderingEngine::createCaptchaDefaults()` est ensuite
supprimée : elle n'avait plus d'appelant depuis l'extraction de
`CaptchaSupportBuilder`. Le contrat de valeurs par défaut reste couvert par
`CaptchaSupportBuilderTest`, tandis que `RenderingEngine::view()` conserve ses
caractérisations d'intégration ; le niveau 4 descend à 20 diagnostics.

Dans `CodeToolsRuntime::findRealToken()`, l'état des commentaires de bloc est
corrigé : l'ouverture active désormais `blockcmt` et le token `*/` le clôture.
Le parseur ne traite plus les accolades et mots-clés situés dans un commentaire
comme du code ; ce comportement est couvert par `CodeToolsRuntimeTest`.

Dans la finalisation ContentBuilder de `SubmissionEngine`, la condition
interne sur `cbRecordId` est supprimée puisque la garde englobante le rend déjà
vrai. La redirection `return` et les contrôles `force_login`/`force_url` restent
inchangés ; le niveau 4 descend à 19 diagnostics.

Les branches de diagnostic `_FF_DEBUG` ont été supprimées de `CodeToolsRuntime`
et de la façade processeur : le drapeau était fixé à zéro et ces blocs ne
pouvaient donc jamais produire de sortie. Les deux anciens blocs modaux
`SqueezeBox` de PayPal/Sofort ont également été retirés, car `$j15` était
initialisé à `false` sans jamais être activé ; les formulaires et la soumission
automatique restent inchangés. Le traitement des erreurs d'upload s'appuie
désormais directement sur l'erreur nullable du résultat, et les instructions
inatteignables de `_ff_errorHandler` sont supprimées. Le filet architectural
verrouille ces invariants ; le niveau 4 descend à 5 diagnostics.

Les trois journaux d'erreur de `ScriptingEngine` utilisent désormais la
configuration `debug` de l'application Joomla au lieu de la constante globale
`JDEBUG`. Le comportement de journalisation est conservé lorsque le mode debug
de Joomla est actif, tout en supprimant une dépendance globale que l'analyse
statique ne pouvait pas représenter ; le niveau 4 ne conserve plus que les deux
gardes de soumission interrompues par `exit`.

La vérification serveur ReCaptcha est maintenant injectée dans
`SubmissionEngine` via `RecaptchaVerifier`. L'orchestrateur ne connaît plus
que le contrat booléen du service ; l'URL Google, le transport HTTP et le
parsing JSON restent confinés au fichier dédié et à `RecaptchaVerifierTest`.
Le service vérifie désormais le statut HTTP avant le décodage et transforme
également une réponse JSON invalide en échec booléen ; le contrat ne laisse
plus remonter une erreur de parsing provenant du endpoint externe.

La logique historique de `saveUpload()` est maintenant portée par
`SubmissionEngine`, qui utilise directement `UploadRuntime` pour stocker le
fichier et traduire les erreurs. `HTML_facileFormsProcessor` conserve la même
signature publique comme délégation, sans garder la logique d'upload ni le
mapping des statuts.

Le calcul de temporisation utilisé pendant la collecte des données est
également porté par `SubmissionEngine`. La façade conserve `measureTime()`
pour les scripts PHP stockés, mais ne contient plus l’implémentation de
`microtime()` ; le calcul et ses appelants internes sont regroupés dans le
pipeline de soumission.

Le gestionnaire d'erreurs global `_ff_errorHandler()` est maintenant réduit à
son rôle d'adaptateur pour `set_error_handler()`. La construction du diagnostic,
la résolution des liens vers le formulaire ou le script concerné et la mise à
jour de la trace sont regroupées dans `ErrorHandlerRuntime`. Les niveaux de
dépréciation ignorés, le court-circuit pendant l'arrêt du processeur et le
format du message sont couverts par `ErrorHandlerRuntimeTest`.

Le style inline explicitement marqué « fixing J3 css » a été retiré de la
branche Classic de `RenderingEngine::view()`. BFNG cible Joomla 6 ; cette
correction de présentation propre à Joomla 3 n'a donc plus sa place dans le
pipeline moderne. Un test d'architecture verrouille l'absence de ce bloc sans
modifier les snapshots de rendu.

Le chargement SQL des éléments publiés est désormais isolé dans
`FormElementLoader`. Le constructeur de `HTML_facileFormsProcessor` conserve
la même initialisation de `rows` et `rowcount`, mais la requête, ses filtres
`published`, son ordre d'affichage et sa liaison typée sont testés directement
dans `FormElementLoaderTest`.

Les fonctions globales de traçage utilisées par le code PHP instrumenté sont
maintenant de simples adaptateurs vers `TraceRuntime`. Les messages, lignes,
entrées de pièces/fonctions, sorties de pile et avertissements de pile vide
restent identiques ; `TraceRuntimeTest` couvre aussi le formatage des arguments
null, booléens, numériques, chaînes, tableaux et objets.

Les opérations de sortie et d'arrêt de trace (`dumpTrace`, `traceEval`,
`suicide` et `bury`) sont maintenant regroupées dans le même service. La façade
publique et `CodeToolsRuntime` conservent leurs méthodes historiques comme
délégations, tandis que les sorties script, l'échappement de l'expression `eval`
et l'idempotence de l'arrêt sont couvertes par `TraceRuntimeTest`.

La requête de chargement du formulaire ne conserve plus dans la façade que la
lecture de `FormTable` ; le chargement des éléments publiés est délégué à
`FormElementLoader`, ce qui laisse une frontière SQL testable sans modifier les
propriétés historiques `rows` et `rowcount` exposées aux callbacks.

Le contenu des infobulles QuickMode est désormais résolu par
`QuickModeHintContentResolver` dans les trois renderers. La syntaxe historique
`<<<style` est conservée ; le resolver encode même la différence observée
entre Classic et Bootstrap/OnePage lorsque plusieurs marqueurs sont présents.
Les tests dédiés verrouillent ces cas avant toute évolution future du markup.

Le harnais de caractérisation de `RenderingEngine::view()` couvre maintenant
également la finalisation preview hors iframe, en plus des parcours frontend,
backend et preview dans iframe. Les assertions vérifient la présence et
l'absence des champs `ff_runmode` et `ff_frame` selon le mode, sans changer la
sortie produite.

Les deux gardes locales `if (!$halt)` de `SubmissionEngine` ont ensuite été
retirées : `$halt` était initialisé à `false` et ses seules affectations à
`true` précédaient immédiatement `exit`. Le contrôle CAPTCHA, les pièces de
soumission et les sorties d'erreur conservent donc leur ordre et leur
comportement, sans état local inopérant ; le niveau 4 ne signale plus ces
conditions.

## Phase 7 — Réduire les façades historiques

Cette phase commence seulement lorsque leurs responsabilités ont été extraites
et que les appels publics nécessaires sont identifiés.

- Maintenir `HTML_facileFormsProcessor` comme façade mince tant que le PHP
  stocké en base l'appelle encore.
- Déplacer les implémentations vers des services injectables.
- Inventorier les méthodes publiques réellement appelées par les formulaires,
  plugins et callbacks.
- Déprécier en interne les méthodes sans appel avant de les supprimer dans un
  lot explicite.
- Ne pas conserver de chemin alternatif pour les anciennes versions de Joomla
  ou PHP.

## Phase 8 — Consolider les builders triviaux

Ajoutée le 2026-08-31, en retour sur les phases 1 à 5 : l'extraction
systématique de chaque bloc de `RenderingEngine::view()` en sa propre classe
a, dans une minorité de cas, produit des classes disproportionnées par
rapport à ce qu'elles font — un fichier, une propriété et un getter
lazy-init dans `RenderingEngine`, et un fichier de test, pour une seule
chaîne statique sans branchement ni réutilisation. `PaymentMethodFieldBuilder`
en est l'exemple type :

```php
public function build(string $indentation): string
{
    return $indentation . '<input type="hidden" name="ff_payment_method" id="bfPaymentMethod" value=""/>' . "\n";
}
```

### Critère de consolidation

Une classe extraite est candidate à un regroupement quand elle cumule :

1. **Aucune branche conditionnelle** (ou une seule, triviale) — une pure
   fonction de mise en forme, sans logique à isoler.
2. **Un seul appelant** — aucun bénéfice de réutilisation qui justifierait
   un fichier séparé (à l'inverse des builders `QuickMode*Builder` partagés
   entre les 4 renderers, dont l'extraction élimine une vraie duplication et
   reste hors périmètre de cette phase).
3. **Un thème réel commun** avec d'autres classes du même profil — sans
   thème partagé, forcer un regroupement ne ferait que déplacer le problème
   vers une classe fourre-tout, ce qui n'est pas non plus souhaitable.

Analyse quantitative (branches conditionnelles + nombre de fichiers
appelants, sur l'ensemble des classes `*Builder` de
`components/com_breezingformsng/src/Service/Rendering`) : cinq
regroupements cohérents sont désormais réalisés. Les classes restantes sans
partenaire honnête (`ClassNameResolver`, `QueryListStateLibraryBuilder`,
`ProcessorHeaderRenderer`) restent isolées.

### 8.1 `HiddenFormFieldsBuilder` — champs `<input type="hidden">`

Consolide 6 classes qui construisaient chacune des champs cachés du
formulaire rendu, sans logique substantielle :
`PaymentMethodFieldBuilder`, `FormTokenFieldBuilder`,
`AdditionalHiddenFieldsBuilder`, `FormRoutingFieldsBuilder`,
`FormSubmissionFieldsBuilder`, `FormContextFieldsBuilder` → méthodes
`paymentMethod()`, `token()`, `additional()`, `routing()`, `submission()`,
`context()`.

### 8.2 `LegacyScriptTagWrapperBuilder` — habillage `<script>` historique

Consolide 4 classes qui enveloppaient chacune un fragment JavaScript dans le
tag `<script><!-- ... //--></script>` hérité du rendu historique :
`ContentBuilderReadonlyScriptWrapperBuilder`,
`FormValidationScriptWrapperBuilder`, `EditableRecordScriptWrapperBuilder`,
`ContentBuilderEditableScriptWrapperBuilder` → méthodes
`contentBuilderReadonly()`, `formValidationOpen()`/`formValidationClose()`,
`editableRecord()`, `contentBuilderEditable()`.

### 8.3 `ContentBuilderFileSupportBuilder`

Consolide `ContentBuilderFileDisplayNameBuilder`,
`ContentBuilderFileValueParser` et `ContentBuilderSignatureFileResolver` dans
`ContentBuilderFileSupportBuilder` (`9db625e10`). Les sorties d'échappement,
de parsing des lignes et de résolution des signatures sont couvertes dans un
test commun ; les trois anciens fichiers et leurs tests ont été supprimés.

### 8.4 `QuickModeOnePageCallbackScriptBuilder`

Consolide `QuickModeSubmitButtonRestoreBuilder` et
`QuickModeRemodalCloseScriptBuilder` dans
`QuickModeOnePageCallbackScriptBuilder` (`ea627bbdf`). Les callbacks de
restauration Ladda et de fermeture du modal conservent leur sortie historique
et sont testés ensemble.

### 8.5 `CaptchaSupportBuilder`

Consolide `CaptchaEndpointBuilder` et `CaptchaValidationDefaultsBuilder` dans
`CaptchaSupportBuilder` (`5bf333ab8`). Le wrapper `legacy_wrap` avait déjà été
supprimé en phase 10 ; il n'a donc pas été recréé dans ce regroupement. Les
endpoints frontend/administrateur et les valeurs de validation sont testés.

### 8.6 Nettoyage des adaptateurs orphelins

L'audit PHPStan niveau 4 a confirmé que plusieurs propriétés et getters privés
étaient devenus inutiles après les extractions précédentes. Le lot supprime
dans les trois renderers QuickMode les écritures mortes de `fadingCall`, les
propriétés `uploadImagePath` non lues de Bootstrap/OnePage, ainsi que les
adaptateurs privés non appelés pour les builders d'input, textarea, checkbox
et options de calendrier. `ClassicRenderer` conserve son
`uploadImagePath`, qui reste lu lors de la construction du bouton d'upload.

Les getters et propriétés devenus orphelins de `RenderingEngine` pour les
builders de scripts, d'hydratation, de fichiers et de signature sont également
supprimés ; les fragments d'hydratation restent alors regroupés dans
`ContentBuilderHydrationScriptBuilder`. Les tests de caractérisation des trois
renderers et de `RenderingEngine::view()` passent toujours (119 tests,
339 assertions). L'audit PHPStan niveau 4 passe de 89 à 63 diagnostics, les
autres diagnostics étant hors de ce nettoyage ciblé.

### Méthode de vérification

Chaque corps de méthode a été comparé octet pour octet à la classe d'origine
avant toute suppression (extraction automatisée des deux corps, comparaison
stricte) — aucune des 10 méthodes déplacées n'a changé de comportement. Les
propriétés/getters lazy-init et points d'appel de `RenderingEngine.php` ont
été mis à jour vers les nouvelles classes ; les anciens fichiers concernés et
leurs tests ont été supprimés après fusion des cas de test dans trois fichiers
de test consolidés. Suite complète verte, PHPStan propre et PHPCS vert sur les
nouveaux services.

## Phase 9 — Suppression du rendu mobile historique

Ajoutée le 2026-08-31. La détection de terminal mobile (`bf_is_mobile()`) et
le renderer QuickMode dédié qu'elle déclenchait dataient de l'époque
pré-responsive du projet (~2010-2014) : la regex de détection ciblait des
plateformes toutes abandonnées depuis 3 à plus de 15 ans (BlackBerry OS,
Windows Phone, Symbian, WebOS, Firefox Mobile/Fennec, et un bloc de repli au
format `detectmobilebrowsers.com` reconnaissant des modèles de téléphones
WAP des années 2000). Le projet utilisant Bootstrap 5 (responsive) sur
l'ensemble de ses thèmes, cette voie de rendu séparée n'a plus de raison
d'être — décision : suppression complète plutôt que refactorisation.

### Périmètre supprimé

- `bf_is_mobile()` (détection User-Agent, `helpers.php`).
- `libraries/crosstec/constants.php` et son chargement : les constantes Mambo
  (`_ISO`, `_VALID_MOS`, `_MOS_*`) étaient orphelines dans le code Joomla 6.
- `MobileRenderer` (renderer QuickMode dédié, 1463 lignes) et sa façade de
  compatibilité `BFQuickModeMobile` (`class BFQuickModeMobile extends
  MobileRenderer {}`), ainsi que son chargement dans
  `breezingformsng.php`.
- `MobileChoiceMarkupBuilder` (lien « voir la version mobile ») et son seul
  appelant, `RenderingEngine::renderMobileChoice()`.
- Les méthodes orphelines de `RenderingEngine` :
  `createMobileRenderer()`, `syncMobileSessionPreference()`,
  `applyMobileMode()`, `mobileChoiceType()`.
- Le bloc de détection mort dans `SubmissionEngine` (`$dataObject`,
  `$rootMdata`, `$is_device` n'étaient lus nulle part ailleurs).
- Les propriétés `isMobile` du `processor_facade` (deux déclarations
  distinctes) et la classe `bfMobile` inutilisée du même fichier.
- Les 3 champs d'administration (`mobileEnabled`, `forceMobile`,
  `forceMobileUrl`) dans le formulaire QuickMode par défaut, le panneau
  « Avancé » (`advanced_form.php`) et leur synchronisation JS
  (`quickmode-app-properties.js`).
- 5 fichiers JS site dédiés au thème mobile
  (`quickmode-*-mobile.js`), devenus orphelins après la suppression de
  `MobileRenderer`.
- Les clés de traduction `COM_BREEZINGFORMSNG_MOBILE_ENABLED`,
  `COM_BREEZINGFORMSNG_FORCE_MOBILE`, `COM_BREEZINGFORMSNG_FORCE_MOBILE_URL`,
  `COM_BREEZINGFORMSNG_QM_MOBILE_FORMS`,
  `COM_BREEZINGFORMSNG_QM_MOBILE_FORMS_FORCE`,
  `COM_BREEZINGFORMSNG_QM_MOBILE_FORMS_URL` et
  `COM_BREEZINGFORMSNG_MOBILE_VERSION` (devenue orpheline avec
  `renderMobileChoice()`), dans les 8 langues.
- Les tests de caractérisation dédiés
  (`MobileRendererCharacterizationTest`, `MobileChoiceMarkupBuilderTest`) et
  les 4 méthodes de test de `RenderingEngineViewCharacterizationTest`
  couvrant les branches mobiles désormais supprimées ; mise à jour de
  `PublicFacadeApiTest` (retrait des attentes sur `BFQuickModeMobile`).

### Effet de bord conservé : `legacy_wrap`

`applyMobileMode()` portait un effet de bord sans rapport avec la détection
mobile : `$processor->legacy_wrap = false` pour les thèmes Bootstrap
(bascule entre le balisage historique en `<table>` et le balisage en `<div>`
moderne, lu par `RenderingEngine` et `FormClosingMarkupBuilder`). Cet effet
n'était appliqué que dans la branche *sans* détection mobile — un
oubli probable de l'implémentation d'origine. Il est conservé, extrait en
bloc autonome dans `view()`, et s'applique désormais **sans condition** pour
tout thème Bootstrap, ce qui corrige implicitement ce défaut latent.

### Risque connu — façade `BFQuickModeMobile`

`BFQuickModeMobile` était documentée comme façade de compatibilité
ascendante pour du PHP potentiellement stocké en base
(`facileforms_pieces.code`, `forms.piece*code`) référençant la classe par
son nom. Sa suppression ne peut pas être validée par grep sur le dépôt — un
tel code vivrait uniquement en base de données. Risque accepté sur demande
explicite de l'utilisateur (« supprimer tout cela »), à surveiller si un
formulaire existant en production référence cette classe.

### Vérification

Suite complète verte (403 tests, 0 échec), PHPStan niveau 2 propre, `php -l`
sur tous les fichiers modifiés, vérification live du panneau QuickMode
« Avancé » (absence des 3 champs mobiles, `joomlaHint` intact,
zéro erreur console/réseau), build + validation du package.

## Phase 10 — Suppression de `legacy_wrap`

Ajoutée le 2026-08-31, à la suite de la phase 9. `legacy_wrap` pilotait deux
variantes d'habillage du formulaire QuickMode : le thème Bootstrap recevait
un simple `<div>`, tandis que le thème Classic (`ClassicRenderer`) recevait
un balisage à 9 `<div>` imbriqués (`bfPage-tl/tr/t/l/r/m/bl/br/b`) — un
bricolage CSS pré-CSS3 destiné à simuler des coins arrondis via des images
de fond, avant l'existence de `border-radius` — plus un `<table
style="display:none">` caché pour le widget reCAPTCHA (`bfReCaptchaWrap`).

Constat : la feuille de style réellement chargée par `ClassicRenderer`
(`themes/quickmode/system.css`) ne stylait déjà plus aucune de ces classes
`bfPage-*` — le fichier qui les stylait (`themes/quickmode/aqua/theme.css`)
n'était référencé par aucun chemin de code. Le balisage à 9 divs et le
`<table>` caché étaient donc déjà visuellement inertes avant cette phase :
leur suppression ne change aucun rendu visible actuel. `#bfReCaptchaWrap`/
`#bfReCaptchaDiv` n'étaient lus par aucun JS ni CSS du dépôt.

### Périmètre supprimé

- La propriété `$legacy_wrap` du `processor_facade`.
- La branche legacy de `FormOpeningMarkupBuilder` et
  `FormClosingMarkupBuilder` (signatures simplifiées : plus de paramètre
  booléen ; `FormOpeningMarkupBuilder` perd aussi son paramètre `$newline`,
  devenu inutilisé une fois le balisage réduit à un seul `<div>`).
- `CaptchaWrapperMarkupBuilder` entièrement (son seul appelant a disparu) et
  son test.
- Le calcul de `$legacyWrap` dans `RenderingEngine::view()` (les deux
  méthodes privées `initializeFormRendering()`/`closeFormRendering()` ne
  prennent plus de paramètre).
- Les tests de caractérisation dédiés aux deux branches (fusionnés en tests
  à comportement unique).

### Remplacement CSS

Un `border-radius: 8px` a été ajouté sur `.bfFormDiv` dans
`themes/quickmode/system.css`, pour porter l'intention visuelle d'origine
(coins arrondis) via la propriété CSS moderne plutôt que via le balisage à
9 divs.

### Vérification

Suite complète verte (399 tests, 0 échec — les 4 tests de caractérisation
legacy/moderne fusionnés en 2), PHPStan niveau 2 propre, `php -l` sur tous
les fichiers modifiés, build + validation du package. Les assertions des
tests `FormOpeningMarkupBuilderTest`/`FormClosingMarkupBuilderTest`/
`RenderingEngineViewCharacterizationTest` couvrent le contenu exact,
octet pour octet, du nouveau balisage.

## Phase 11 — Nettoyage des assets QuickMode orphelins

Ajoutée le 2026-08-31, à la suite de la phase 10. En investiguant l'origine
visuelle du balisage à 9 divs supprimé en phase 10, découverte que
`components/com_breezingformsng/themes/quickmode/` contenait un ensemble
d'assets totalement hors d'atteinte du code actuel :

- 6 dossiers de skins d'origine BreezingForms (2010-2013) : `aqua/`,
  `breeze/`, `default/`, `glossy_blue/`, `glossy_gray/`, `qmtheme/`.
- `system.ie6.css`, `system.ie7.css`, `system.ie.css` — feuilles
  conditionnelles pour Internet Explorer 6/7.
- `mobile-system.css` — résidu de la phase 9 (suppression du rendu mobile).
- `images/` (icônes, spinner ajax-loader) et les doublons
  `ajax-loader.gif/png`, `cancel.png`, `upload.png`, `img/icon_info_old.png`
  à la racine (`ClassicRenderer` charge en réalité ses icônes cancel/upload
  depuis `media/breezingforms/themes/`, pas ce dossier).

Vérification de la logique de sélection réelle
(`QuickmodeModel::scanThemeDir()`) : le menu déroulant « Thème » de l'admin
scanne dynamiquement `media/breezingforms/themes/` (Classic, un seul skin
réel aujourd'hui : `vcmb_j5`, le thème maison) et
`media/breezingforms/themes-bootstrap5/` (Bootstrap/OnePage, dossier
inexistant). Aucun de ces deux chemins ne pointe vers
`components/com_breezingformsng/themes/quickmode/<skin>/` — les 6 dossiers
supprimés n'étaient donc sélectionnables par aucune voie du code actuel.

### Conservé

`system.css` (seule feuille chargée par `ClassicRenderer`, ligne 184) et
`img/icon_info.png` (seule image qu'il référence, via une unique règle
`url()` dans `system.css`).

### Vérification

252 fichiers supprimés (uniquement des assets CSS/images/HTML, aucun PHP).
Suite complète verte (399 tests), PHPStan niveau 2 propre, build +
validation du package (assets absents de l'archive, `system.css` présent).

## Phase 12 — Restauration des 6 skins Classic dans `media/breezingforms/themes/`

Ajoutée le 2026-08-31, correction de la phase 11. Les 6 skins supprimés en
phase 11 (`aqua`, `breeze`, `default`, `glossy_blue`, `glossy_gray`,
`qmtheme`) n'étaient inatteignables qu'à cause de leur emplacement — pas
parce qu'ils étaient sans valeur. Sur demande explicite, restaurés depuis
l'historique git (`git checkout d88a6ea91 --`) puis déplacés vers
`media/breezingforms/themes/<skin>/`, aux côtés de `vcmb_j5`, le seul
chemin réellement scanné par `QuickmodeModel::scanThemeDir()` pour peupler
le sélecteur « Thème » de l'admin (voir phase 11 pour le détail du
mécanisme de scan).

Point notable retrouvé en creusant l'historique : `media/breezingforms/
themes/<skin>/` était en fait leur **emplacement d'origine** — le commit
`d6655c526` (« Legacy files moves ») les avait déplacés vers
`components/com_breezingforms(ng)/themes/quickmode/` bien avant le
renommage `com_breezingforms → com_breezingformsng`. Cette restauration
les ramène donc à leur chemin historique initial ; `git log --follow`
sur le nouveau chemin remonte sans interruption jusqu'au commit initial.

### Réserve connue

Le skin `aqua` (seul des 6 à référencer les classes `bfPage-tl/tr/t/l/r/
m/bl/br/b`, supprimées du balisage en phase 10) a environ 10 règles CSS
qui ne trouveront plus d'élément à cibler — sans effet visuel néfaste,
juste inertes. Les 5 autres skins ne référençaient pas ce balisage.

### Vérification

Vérification live : les 7 thèmes (`vcmb_j5` + les 6 restaurés) apparaissent
dans le sélecteur `#bfTheme` de l'admin, zéro erreur console/réseau. Suite
complète verte (399 tests, aucun fichier PHP touché), PHPStan niveau 2
propre, build + validation du package (skins présents dans l'archive sous
`media/breezingforms/themes/`, absents de l'ancien chemin).

## Phase 13 — Nettoyage des règles `bfPage-*` mortes dans `aqua/theme.css`

Ajoutée le 2026-08-31, à la suite de la phase 12. Comme signalé dans la
réserve de la phase 12, `aqua/theme.css` était le seul des 6 skins
restaurés à référencer les classes `bfPage-tl/tr/t/l/r/m/bl/br/b`,
supprimées du balisage en phase 10 : ~10 règles CSS sans effet, ciblant
des éléments qui ne sont plus émis. Supprimées, avec les 8 images
`bg_page-*.png` qu'elles étaient les seules à référencer (`img/`
conserve les images encore utilisées : boutons, légendes, fieldsets,
rollover).

### Vérification

Vérification live : `media/breezingforms/themes/aqua/theme.css` répond
200, plus aucune règle `bfPage-*` dans le contenu servi. Build +
validation du package (images supprimées absentes de l'archive,
`theme.css` présent). Suite complète verte (399 tests, aucun fichier PHP
touché).

## Phase 14 — Correction du chevauchement des radios natives dans l'onglet Options

Ajoutée le 2026-08-31. Régression signalée par l'utilisateur : dans l'onglet
QuickMode « Options » → « Général », le champ « Publié(s) » (Oui/Non,
markup Bootstrap 5 natif de `forms/edit.php`) affichait le cercle radio
chevauchant le texte du label.

Cause : `media/com_breezingformsng/css/custom.css` contient un skin CSS
personnalisé pour les radios/checkboxes de l'éditeur QuickMode (technique
`opacity:0` sur l'input natif + `<span>` dessiné à la place, un procédé du
même esprit pré-Bootstrap que le balisage `bfPage-*` supprimé en phase 10).
Une exemption `:not(#bfOptionsFieldsWrap *)` avait déjà été ajoutée sur la
toute première règle du skin (`opacity`/`width`) pour laisser les radios
Bootstrap natives de l'onglet Options intactes — mais **pas** sur les 7
autres règles du même bloc (`+ label`, `+ label > span`, `:checked + label
> span`, etc.), documentée par erreur comme complète (« so its normal
Bootstrap sizing applies too, not just visibility »). La règle
`main input[...]:not(old) + label { margin-left: -1.5em; }`, non exemptée,
s'appliquait quand même aux labels Bootstrap natifs de `#bfOptionsFieldsWrap`
et les tirait par-dessus le cercle du radio.

Corrigé : ajout de `:not(#bfOptionsFieldsWrap *)` aux 7 règles restantes du
bloc, pour que l'exemption corresponde enfin à ce que le commentaire du
bloc décrivait déjà.

### Vérification

Vérification live avant/après (capture de la ligne « Publié(s) ») :
chevauchement confirmé avant correctif, cercle et texte correctement
séparés après déploiement du CSS corrigé. Suite complète verte (399 tests,
aucun fichier PHP touché), build + validation du package.

## Phase 15 — Nettoyage CSS mort et audit des clés Joomla obsolètes

Ajoutée le 2026-08-31, à la suite de la phase 14.

### Skin radio/checkbox mort

En corrigeant le chevauchement de la phase 14, constat que le skin CSS
personnalisé pour radios/checkboxes de `custom.css` attend un balisage
`<label><span></span>Texte</label>` que **plus aucun fichier PHP ni JS du
dépôt ne génère** (vérifié : recherche exhaustive, zéro correspondance).
Laissé en place, il ne servait plus qu'à casser des cases/radios natives
n'ayant jamais suivi cette convention — 3ᵉ bug de ce type identifié dans la
session (chevauchement Options, radios du sélecteur de moteur de thème,
case « Scroll element list »). Le bloc entier (~100 lignes) est supprimé
plutôt que patché une exemption de plus ; chaque case/radio s'affiche
désormais comme un contrôle natif (`9e990f442`).

### Audit des clés de langue Joomla core

Recherche systématique de tout appel `Text::_()/sprintf()/plural()` vers
une clé `JXXX` (24 clés uniques référencées dans tout le composant),
confrontées au fichier `en-GB.ini` core réel de Joomla 6 dans le
conteneur. 3 obsolètes trouvées et corrigées :

- `JORDER` → `JFIELD_ORDERING_LABEL`
- `JPAGINATION` → `JLIB_HTML_PAGINATION`
- `JLIB_APPLICATION_DELETE_SUCCESS` (n'existe plus comme clé générique
  core) → nouvelle clé projet `COM_BREEZINGFORMSNG_DELETE_SUCCESS`,
  traduite dans les 8 langues

Les 21 autres clés `JXXX` du composant résolvent correctement — plus
aucune référence obsolète (`01d17251a`).

### Vérification

Suite complète verte, PHPStan niveau 2 propre, build + validation du
package. Correctifs CSS vérifiés en direct (avant/après). Baseline PHPStan à
11 entrées (contre 251 au dernier relevé historique de ce document), après
suppression des entrées résiduelles liées au rendu mobile, aux trois renderers
QuickMode et au mailer (`f57d08761`, `ed2abded1` et `9aa2c6409`).

## Phase 16 — Déclaration manifeste des thèmes Classic

Ajoutée le 2026-08-31 après l'audit du paquet d'installation. Les thèmes
Classic restaurés sous `media/breezingforms/themes/` étaient bien suivis par
Git et présents dans l'archive construite, mais aucun élément `<media>` du
manifeste ne déclarait ce répertoire : une installation Joomla neuve ne les
copie donc pas sur le site. Le renderer et `QuickmodeModel` cherchant ces
fichiers à cet emplacement, le défaut pouvait rester invisible sur une
instance déjà migrée.

Le manifeste regroupe maintenant les deux arborescences dans un seul bloc
`<media folder="media">` : `com_breezingformsng` pour les assets du composant
et `breezingforms/themes` pour les thèmes Classic. Cette forme est nécessaire
car l'installateur Joomla traite le nœud `<media>` du manifeste comme un bloc
unique. Le validateur de paquet vérifie cette déclaration et la présence des
thèmes `default` et `aqua`; le smoke-test Joomla 6 vérifie également les deux
fichiers `theme.css` après installation réelle.

### Vérification

Le test de manifeste passe, la suite complète passe (633 tests, 1 963
assertions), PHPCS et le XML du manifeste sont valides, le paquet est validé
et le smoke-test Joomla 6 installation/update/frontend confirme la présence
effective de `media/breezingforms/themes/default/theme.css` et
`media/breezingforms/themes/aqua/theme.css`.

## Phase 17 — Navigation des enregistrements selon la liste courante

Ajoutée le 2026-08-31 après constat que l'écran d'édition calculait le
précédent/suivant uniquement par identifiant dans le même formulaire. Cette
logique ignorait donc le tri choisi et pouvait sortir du résultat affiché
lorsqu'une recherche ou un formulaire était sélectionné.

### Périmètre

- `RecordsModel` construit maintenant la séquence de navigation à partir de
  la même requête filtrée et ordonnée que `getItems()` : formulaire,
  recherche, colonne de tri et direction sont communs aux deux parcours.
- Les enregistrements « tous formulaires » peuvent à nouveau être parcourus
  entre formulaires, tandis qu'un enregistrement absent du résultat filtré ne
  reçoit aucun voisin.
- Les liens de la liste et de l'écran d'édition transmettent le formulaire,
  la recherche, le tri, la direction et l'état de pagination. Ces champs sont
  aussi renvoyés par `save()` et `cancel()` afin de rester dans la liste de
  départ.
- La navigation par identifiant historique de `RecordModel` est supprimée
  pour éviter deux définitions divergentes de l'ordre des enregistrements.

### Vérification

Le test ciblé couvre le tri descendant avec formulaire et recherche, le tri
ascendant sans filtre, l'absence d'un voisin hors résultat et la propagation
de l'état dans les templates. La suite complète passe avec 638 tests et 1 989
assertions ; PHPCS (150 fichiers configurés), PHPStan niveau 2, `php -l` sur
les fichiers modifiés et `git diff --check` sont verts.

## Phase 18 — Orchestration du rendu HTML Classic

Ajoutée le 2026-09-01 pour terminer la frontière de la branche Classic sans
continuer le micro-découpage des builders. Les builders de types de champs et
de Query List étaient déjà testés individuellement, mais leur boucle de
coordination restait dans `RenderingEngine::view()` et maintenait 16
propriétés/getters lazy-init dans l'orchestrateur.

### Périmètre

- `ClassicFormRenderer` prend en charge la boucle complète des 21 types de
  nœuds historiques : remplacement de `data1`/`data2`/`data3`, géométrie du
  wrapper, classes CSS, dispatch vers les builders et composition Query List.
- La frontière Query List reste dans le même service : réglages, en-tête,
  cellules, lignes, pied et pagination sont coordonnés sans créer un service
  par étape.
- `RenderingEngine::view()` conserve le choix du chemin Classic/QuickMode et
  la gestion commune de l'arrêt `bury()`, mais ne construit plus le HTML d'un
  nœud et ne possède plus les builders Classic.
- Les opérateurs, l'ordre des remplacements, les accès aux tableaux préparés
  et les sorties HTML sont conservés ; le service retourne `null` uniquement
  lorsque le processeur demande l'arrêt historique.

### Filet de sécurité

`ClassicFormRendererTest` couvre plusieurs familles de contrôles dans un seul
parcours, une table Query List préparée et l'arrêt pendant le remplacement
des valeurs. `RenderingEngineViewCharacterizationTest` reste le test de
caractérisation du cycle complet, tandis que les tests existants des builders
verrouillent leurs sorties exactes. `RenderingEngineArchitectureTest` vérifie
que la boucle ne revient pas dans `RenderingEngine`.

### Vérification

La suite complète passe avec 643 tests et 2 012 assertions. PHPCS passe sur
151 fichiers configurés, PHPStan niveau 4 ne signale aucune erreur, les deux
fichiers de production passent `php -l` et `git diff --check` est vert.

## Phase 19 — Initialisation du contexte runtime du processeur

Ajoutée le 2026-09-01 après l'audit de la façade publique
`HTML_facileFormsProcessor`. Le constructeur concentrait encore la collecte
des métadonnées HTTP, l'horodatage de soumission, la résolution du contexte
d'affichage et l'expansion des chemins de formulaire, alors que ces opérations
étaient déjà portées individuellement par des services runtime testés.

### Périmètre

- `ProcessorRuntimeContextInitializer` orchestre ces quatre responsabilités
  transversales sans créer un service par propriété de la façade.
- `ProcessorRuntimeContext` regroupe leur résultat typé : métadonnées de
  requête, date de soumission, contexte d'affichage et chemins/tokens.
- Le chargement SQL de `FormTable` et des éléments publiés reste dans la
  façade et dans `FormElementLoader`, leurs frontières étant déjà établies et
  testées.
- La façade conserve ses propriétés publiques historiques et ne change pas
  les valeurs exposées au reste du composant ou aux callbacks.

### Filet de sécurité et vérification

`ProcessorRuntimeContextInitializerTest` couvre l'assemblage frontend avec
expansion des chemins, ainsi que l'aperçu avec grille et IP masquée. Le test
vérifie aussi que la façade délègue cette composition et ne conserve plus les
conditions de configuration ni les appels directs aux résolveurs.

La suite complète passe avec 646 tests et 2 030 assertions. PHPCS passe sur
153 fichiers configurés, PHPStan niveau 4 ne signale aucune erreur, les
fichiers ajoutés et modifiés passent `php -l` et `git diff --check` est vert.

## Phase 20 — Fermer les appels récursifs du moteur scripting

Ajoutée le 2026-09-01 pendant l'audit des appelants de la façade. Les
opérations de `ScriptingEngine` repassaient encore par
`HTML_facileFormsProcessor` pour invoquer une autre opération du même moteur,
ce qui entretenait une boucle inutile dans la façade historique.

### Périmètre

- Les résolutions et exécutions de pièces utilisent directement les méthodes
  du moteur (`getPieceById`, `getPieceByName`, `execPiece`).
- Le remplacement de code et les valeurs de Query List utilisent directement
  `execPiece` et `execQueryValue`.
- La liaison des bibliothèques et l'enregistrement des fonctions utilisent
  directement `linkcode`, tandis que l'émission de scripts appelle les
  opérations locales `replaceCode` et `compressJavascript`.
- Les appels externes continuent d'emprunter la façade publique ; seul le
  round-trip interne est supprimé. Les annotations `@phpstan-impure` rendent
  explicites les effets de `execPiece` et `linkcode`.

### Filet de sécurité et vérification

`ScriptingEngineArchitectureTest` vérifie la présence des chemins internes et
l'absence des appels récursifs correspondants vers le processeur. La suite
complète passe avec 647 tests et 2 042 assertions. PHPStan niveau 4, PHPCS,
`php -l` et `git diff --check` sont verts.

## Phase 21 — Mutualiser le téléchargement des fichiers payants

Ajoutée le 2026-09-01 après caractérisation des trois callbacks de paiement.
`StripeCallback`, `PayPalCallback` et `SofortCallback` reproduisaient chacun
le même parcours de téléchargement sécurisé, avec plusieurs dizaines de
lignes identiques et seules les données fournisseur variant.

### Périmètre

- `PaymentDownloadService` centralise le chargement du formulaire, la recherche
  de l'élément de paiement, la vérification de la transaction, le quota, la
  mise à jour atomique du compteur et le streaming du fichier.
- Le type d'élément, la clé de message, le nom du paramètre transactionnel, le
  préfixe de transaction et l'alternative PayPal `(VALID)` sont des paramètres
  explicites du parcours partagé.
- Les callbacks ne conservent que leur configuration fournisseur et leurs
  autres parcours (`confirm`, `success`, IPN) ; aucune logique de téléchargement
  n'est dupliquée.
- Les contrôles Joomla Query Builder, les paramètres liés et
  `PaymentDownloadPolicy` restent dans le service commun.

### Filet de sécurité et vérification

`PaymentDownloadServiceTest` couvre le formulaire absent, la transaction
inconnue, le quota atteint, les bindings des variantes PayPal et l'ignorance
des éléments non concernés. `PaymentCallbackRegressionTest` vérifie la
délégation des trois callbacks au service commun.

La suite complète passe avec 651 tests et 2 058 assertions. PHPCS passe sur
154 fichiers configurés, PHPStan niveau 4 ne signale aucune erreur, les
callbacks passent `php -l` et `git diff --check` est vert.

## Phase 22 — Raccorder directement notifications et exports

Ajoutée le 2026-09-01 après caractérisation des appelants restants de la
façade. `NotificationEngine` repassait encore par
`HTML_facileFormsProcessor` pour exporter les données, envoyer les mails et
résoudre ses propres traductions.

### Périmètre

- `ExportEngine` est injecté explicitement dans `NotificationEngine` par la
  façade, qui partage l'instance déjà construite pour le parcours d'export.
- Les exports XML, CSV et PDF ainsi que l'envoi des notifications utilisent
  directement `ExportEngine`, sans détour par les méthodes publiques de la
  façade.
- Les traductions de titre de formulaire et de libellé de champ restent dans
  `NotificationEngine`, qui appelle ses propres méthodes au lieu de
  réentrer dans le processeur historique.
- Aucun changement n'est apporté aux méthodes publiques de la façade : les
  appelants externes conservent leur contrat Joomla/PHP 8.3.

### Filet de sécurité et vérification

`NotificationEngineArchitectureTest` vérifie l'injection d'`ExportEngine`,
les délégations d'export et de mail, l'absence des appels croisés concernés
et le branchement de l'instance partagée dans la façade.

La suite complète passe avec 654 tests et 2 075 assertions. PHPCS passe sur
154 fichiers configurés, PHPStan niveau 4 ne signale aucune erreur, les
fichiers ajoutés et modifiés passent `php -l` et `git diff --check` est vert.

## Phase 23 — Fermer les appels récursifs du pipeline de soumission

Ajoutée le 2026-09-01 après l'audit des appelants de la façade. Le pipeline
de `SubmissionEngine` repassait par `HTML_facileFormsProcessor` pour appeler
ses propres opérations de collecte et d'enregistrement d'upload, ainsi que
deux opérations déjà disponibles dans `UploadRuntime`.

### Périmètre

- La collecte des données utilise directement `SubmissionEngine::collectSubmitdata()`.
- L'enregistrement des fichiers utilise directement
  `SubmissionEngine::saveUpload()` et conserve le même traitement des
  erreurs et des chemins.
- Le redimensionnement des images et la recherche d'élément QuickMode
  utilisent directement l'instance `UploadRuntime` du moteur.
- Les appels publics de la façade restent inchangés pour les parcours
  externes ; seul le round-trip interne du pipeline est supprimé.

### Filet de sécurité et vérification

`SubmissionEngineArchitectureTest` vérifie l'utilisation des opérations
locales et des méthodes directes d'`UploadRuntime`, ainsi que l'absence des
anciens appels croisés concernés.

La suite complète passe avec 656 tests et 2 085 assertions. PHPCS passe sur
154 fichiers configurés, PHPStan niveau 4 ne signale aucune erreur, les
fichiers ajoutés et modifiés passent `php -l` et `git diff --check` est vert.

## Phase 24 — Injecter les moteurs dans l'orchestration de soumission

Ajoutée le 2026-09-01 après la fermeture des appels récursifs internes du
pipeline. `SubmissionEngine::submit()` utilisait encore la façade pour
exécuter les pièces, journaliser l'enregistrement, produire les exports et
déclencher les notifications.

### Périmètre

- `ScriptingEngine` est injecté et exécute directement les pièces de début et
  de fin de soumission.
- `ExportEngine` est injecté pour la journalisation, les exports Dropbox et
  la génération du jeton de double opt-in.
- `NotificationEngine` est injecté pour les notifications administrateur,
  mailback, MailChimp et Salesforce.
- La façade assemble les instances partagées déjà utilisées par les autres
  parcours ; les méthodes publiques historiques restent disponibles pour les
  appelants externes.

### Filet de sécurité et vérification

Les tests d'architecture de `SubmissionEngine` vérifient les dépendances
injectées, les délégations de chaque groupe d'opérations et l'absence des
anciens appels directs au processeur pour ces méthodes.

La suite complète passe avec 658 tests et 2 113 assertions. PHPCS passe sur
154 fichiers configurés et PHPStan niveau 4 ne signale aucune erreur.

## Phase 25 — Mutualiser l’hydratation des valeurs QuickMode

Ajoutée le 2026-09-01 après comparaison des trois renderers QuickMode actifs.
`ClassicRenderer`, `BootstrapRenderer` et `OnePageRenderer` reproduisaient
le même parcours de recherche d’une ligne enregistrée et d’hydratation de la
métadonnée du champ.

### Périmètre

- `QuickModeSubmittedValueHydrator` centralise la recherche par nom de champ,
  l’exécution des expressions `data1`/`data2`, les traductions de valeur,
  liste et groupe, ainsi que la synchronisation de l’état d’une checkbox.
- Les trois renderers délèguent ce bloc commun et gardent leurs traductions
  et leur markup propres au thème ; la traduction de valeur préparée
  spécifiquement par OnePage est conservée à son emplacement historique.
- Les comparaisons et appels à `replaceCode()` conservent les règles de
  comparaison historiques afin de ne pas modifier les valeurs produites.

### Filet de sécurité et vérification

`QuickModeSubmittedValueHydratorTest` couvre les valeurs scalaires, les
listes/groupes, les traductions, l’état checkbox et l’absence de ligne
correspondante. Il vérifie aussi que chacun des trois renderers délègue au
service partagé. Les caractérisations Classic, Bootstrap et OnePage restent
vertes.

La suite complète passe avec 662 tests et 2 129 assertions. PHPCS passe sur
155 fichiers configurés, PHPStan niveau 4 ne signale aucune erreur, les
fichiers ajoutés et modifiés passent `php -l` et `git diff --check` est vert.

## Phase 26 — Raccorder le dispatcher au conteneur Joomla 6

Ajoutée le 2026-09-01 après l'audit du bootstrap Joomla, du provider de
l'extension et du chargeur Composer. Le dispatcher était encore construit
manuellement dans le front controller alors que ses cinq dépendances existent
déjà dans le conteneur Joomla.

### Périmètre

- `EngineDispatcher` est enregistré dans
  `administrator/components/com_breezingformsng/services/provider.php` avec
  l'application, l'entrée, la base, le mailer et le contrôleur de cache natifs.
- `BreezingFormsNGComponent::boot()` reçoit le conteneur enfant, conserve le
  dispatcher et l'expose au front controller par `getEngineDispatcher()`.
- `components/com_breezingformsng/breezingformsng.php` démarre le composant et
  utilise désormais ce service injecté ; la construction racine n'est plus
  dupliquée dans le point d'entrée.
- Le smoke Joomla charge `VendorHelper`, puis vérifie après installation que
  Securimage, TCPDF et `PdfDocument` sont réellement autoloadables. Le
  validateur de package exige aussi l'autoloader Composer et le fichier TCPDF.

### Filet de sécurité et vérification

`EngineDispatcherContainerTest` vérifie l'enregistrement du service et la
résolution depuis le front controller. `JoomlaInstallSmokeScriptTest` verrouille
le chemin d'autoload du smoke. Le smoke Docker reste le test d'installation
réelle lorsqu'un moteur Docker est disponible.

Le graphe interne construit par `EngineDispatcher` (callbacks, renderer et
services spécialisés) reste le prochain périmètre d'injection ; cette phase
ne change pas ses contrats ni son ordre d'initialisation.

## Phase 27 — Injecter le graphe interne du dispatcher

Ajoutée le 2026-09-01 après la Phase 26. Le provider enregistrait le point
d'entrée `EngineDispatcher`, mais celui-ci construisait encore lui-même le
renderer et chaque callback, ainsi que leurs dépendances partagées.

### Périmètre

- `FormRenderer`, les sept callbacks, `RequestParameterParser`,
  `PaymentDownloadPolicy`, `RedirectHelper` et `FlashUploadSizeValidator` sont
  enregistrés dans `services/provider.php`.
- `EngineDispatcher` reçoit ces services par constructeur et conserve l'ordre
  historique des branches de rendu et de callback.
- Aucune méthode publique de la façade ni aucun contrat de callback n'est
  modifié.

### Filet de sécurité et vérification

`EngineDispatcherContainerTest` vérifie le graphe enregistré et l'absence des
constructions manuelles dans le dispatcher. Les tests de callbacks existants
restent indépendants et continuent de couvrir leurs contrats propres.

## Phase 28 — Injecter le parcours partagé de téléchargement des paiements

Ajoutée le 2026-09-01 après la Phase 27. Les callbacks PayPal, Stripe et
Sofort déléguaient encore chacun la construction de `PaymentDownloadService`,
ce qui recréait le service partagé à chaque appel.

### Périmètre

- `PaymentDownloadService` est enregistré dans le provider Joomla 6 avec sa
  `PaymentDownloadPolicy` et ses dépendances communes.
- Les callbacks PayPal, Stripe et Sofort reçoivent le service par
  constructeur et lui délèguent leur parcours de téléchargement.
- Les contrats et paramètres propres à chaque moyen de paiement restent
  inchangés ; aucun changement de sortie ou de règle de quota n'est introduit.

### Filet de sécurité et vérification

`PaymentCallbackRegressionTest` vérifie l'absence de constructions locales,
le partage du service depuis le provider et la conservation de la politique
de quota. Les tests ciblés des téléchargements et du callback PayPal couvrent
les contrats d'exécution concernés.

## Phase 29 — Regrouper le nettoyage des fichiers dans un service unique

Ajoutée le 2026-09-01 après la Phase 28. `PaymentCacheCleaner` et
`TemporaryUploadFileCleaner` parcouraient les répertoires et supprimaient les
fichiers avec le même algorithme, alors qu'ils n'avaient qu'un seul appelant
de production : `FormRenderer`.

### Périmètre

- `UploadFileCleaner` regroupe le parcours commun et expose deux opérations
  explicites pour les uploads temporaires et le cache de paiement.
- Les règles historiques de nommage, les suffixes et l'expiration de 24 heures
  restent distincts et sont conservés.
- Le service unique est enregistré dans le provider Joomla 6 puis injecté
  dans `FormRenderer`; les deux anciennes classes disparaissent.

### Filet de sécurité et vérification

`UploadFileCleanerTest` couvre les noms valides et invalides, les suffixes
Flash/chunked et les limites d'expiration. Le provider et le rendu continuent
à être vérifiés par la suite complète PHPUnit, PHPStan et PHPCS.

## Phase 30 — Consolider les builders QuickMode mono-appelant

Ajoutée le 2026-09-01 après la Phase 29. Plusieurs builders QuickMode ne
possédaient qu'un appelant et n'exprimaient pas une frontière réutilisable.

### Périmètre

- Les attributs d'image de paiement sont une méthode privée de
  `QuickModePaymentButtonBuilder`.
- Le markup checkbox est une méthode privée de
  `QuickModeCheckboxStrategy`.
- Les deux fragments internes de la callback upload sont des méthodes
  privées de `QuickModeUploadEntryCallbacksBuilder`.
- `QuickModeUploadEntryCallbacksBuilder` reste une classe partagée par les
  trois renderers ; aucune duplication n'est recréée.

### Filet de sécurité et vérification

Les tests des builders supprimés sont fusionnés dans les tests des appelants.
Les snapshots de paiement, checkbox et upload restent inchangés. Les fichiers
retirés ne figurent plus dans le périmètre PHPCS.

## Phase 31 — Factoriser la préparation des modèles de packages Admin

Ajoutée le 2026-09-01 après la Phase 30. `PieceModel` et `ScriptModel`
contenaient la même préparation de liste ; leurs seules différences étaient la
table et le préfixe de session.

### Périmètre

- `PackageModel` porte désormais la préparation commune de la liste, des
  filtres, du tri, de la pagination et de l'état de session.
- `PieceModel` et `ScriptModel` conservent uniquement leurs paramètres propres
  via deux hooks protégés.
- Les contrôleurs et les vues restent distincts, car leurs différences ne
  relèvent pas du même contrat MVC.

### Filet de sécurité et vérification

`PackageLibraryUiRegressionTest` vérifie le partage de la préparation et la
conservation des préfixes `pieces` et `scripts`. Les tâches, assets et vues
Admin restent couverts par les tests de régression existants.

## Phase 32 — Regrouper l’enveloppe du formulaire et ses champs techniques

Ajoutée le 2026-09-01 après la Phase 31. L’ouverture, la fermeture et la
finalisation des modes du formulaire étaient réparties dans trois builders,
et deux builders de champs cachés n’avaient qu’un appelant commun.

### Périmètre

- `FormEnvelopeMarkupBuilder` regroupe l’ouverture, la fermeture et les
  assemblages frontend, backend et preview.
- `HiddenFormFieldsBuilder` absorbe les champs de contexte optionnels et les
  marqueurs techniques ContentBuilder.
- `RenderingEngine` conserve les décisions liées au contexte Joomla et
  l’ordre historique des fragments, tout en utilisant ces deux points
  d’entrée fonctionnels.

### Filet de sécurité et vérification

Les tests d’enveloppe et de champs cachés sont regroupés autour des deux
builders conservés. Les sorties exactes des trois modes, l’absence de preview
hors iframe et l’indentation des champs techniques restent couvertes.

## Phase 33 — Regrouper la pagination Query List dans son assembleur

Ajoutée le 2026-09-01 après la Phase 32. Les trois fragments de la callback
JavaScript de pagination n’avaient qu’un appelant immédiat commun :
`QueryListPageScriptBuilder`.

### Périmètre

- `QueryListPageScriptBuilder` porte désormais le rafraîchissement des lignes,
  la navigation et les statements optionnels de fin de pagination.
- L’ordre historique de `ff_dispQueryPage()`, les bornes de page et les
  variantes checkbox/iframe restent inchangés.
- Les services d’état Query List et de préparation des lignes restent séparés,
  car ils ont des responsabilités et des appelants distincts.

### Filet de sécurité et vérification

Les tests des trois fragments sont fusionnés dans
`QueryListPageScriptBuilderTest`, qui vérifie l’ordre d’assemblage, les liens
de navigation, le rafraîchissement des lignes et les options de fin.

## Phase 34 — Regrouper les chargeurs de métadonnées ContentBuilder

Ajoutée le 2026-09-01 après la Phase 33. Les chargeurs d’associations et de
définition ContentBuilder utilisaient la même base de données et la même table,
avec un seul appelant commun dans `cbCheckPermissions()`.

### Périmètre

- `ContentBuilderFormMetadataLoader` regroupe les deux requêtes de métadonnées
  dans des méthodes explicites : association publiée et définition publiée.
- Les filtres, bindings entiers et types de retour restent distincts.
- `ContentBuilderRecordLoader` reste séparé, car il porte le chargement d’un
  enregistrement et non celui des métadonnées de formulaire.

### Filet de sécurité et vérification

`ContentBuilderFormMetadataLoaderTest` couvre les résultats vide/non vide, les
filtres de publication et les bindings des deux requêtes.

## Phase 35 — Regrouper l’hydratation ContentBuilder

Ajoutée le 2026-09-01 après la Phase 34. Les scripts d’hydratation et de
désactivation ContentBuilder étaient répartis dans huit builders, alors que
leurs sorties appartiennent au même parcours de rendu et que les fragments
avaient un appelant commun dans `RenderingEngine` ou dans l’orchestrateur des
enregistrements.

### Périmètre

- `ContentBuilderHydrationScriptBuilder` porte les parcours `buildEditable()`
  et `buildReadonly()`, ainsi que les fragments de validation Flash, contrôles
  de fichiers, hydratation de fichiers, signatures, valeurs, choix et listes.
- `ContentBuilderFileSupportBuilder` et
  `ContentBuilderSignatureImageEncoder` restent séparés : ils sont des
  utilitaires utilisés au-delà d’un fragment unique.
- `RenderingEngine` conserve un seul service lazy et l’ordre historique des
  scripts générés.

### Filet de sécurité et vérification

Les tests des huit anciens builders sont fusionnés dans
`ContentBuilderHydrationScriptBuilderTest`. Ils couvrent les sorties
JavaScript historiques, les valeurs multiples, l’échappement, les signatures,
les files d’upload QuickMode et la désactivation des champs non éditables.

## Travail en parallèle

| Couloir | Fichiers principaux | Peut avancer avec |
|---|---|---|
| A — `RenderingEngine` | `RenderingEngine.php`, tests de caractérisation de `view()` | Couloirs B et C |
| B — Stratégies QuickMode | `QuickMode/`, snapshots des trois renderers actifs | Couloirs A et C |
| C — Qualité | `phpcs.xml.dist`, baseline PHPStan, groupes de services déjà extraits | Couloirs A et B |
| D — ContentBuilder | Nouveaux services et tests ContentBuilder | Couloir B, mais coordination requise avec A pour le branchement dans `view()` |

Règles de coordination :

- Une seule personne modifie `RenderingEngine.php` à la fois.
- Les tests partagés ne sont déplacés qu'après intégration des branches en
  cours.
- Chaque couloir produit des commits autonomes et réversibles.
- Le branchement d'un nouveau service dans `view()` appartient au couloir A,
  même si le service a été préparé dans le couloir D.

## Ordre recommandé des prochains lots

1. ~~Harnais ContentBuilder pour le parcours complet des fichiers et des
   signatures, puis validation de la lecture SQL de l'enregistrement.~~
   Terminé par `72a4d2632` et `fe1d09532`.
2. ~~Poursuivre les stratégies de contrôles restantes dans les wrappers
   Classic et Bootstrap/OnePage, après les branchements déjà réalisés
   (`065cef94`, `f3d04e55`).~~
   Les familles de contrôles prioritaires sont mutualisées et couvertes par
   les lots de la Phase 5.
3. ~~Harnais ContentBuilder pour les parcours runtime fichiers/signatures.~~
   Terminé par `fe1d09532`.
4. ~~Rendu HTML classique par famille de nœuds.~~
   Les builders et l'orchestration complète sont terminés par la Phase 18.
5. ~~Réduction progressive des avertissements PHPCS et PHPStan après chaque
   extraction fonctionnelle.~~
   Le périmètre PHPCS configuré est vert et PHPStan niveau 4 est sans erreur.
6. ~~Auditer la façade publique `HTML_facileFormsProcessor` et ses appelants
   avant toute nouvelle extraction : caractériser les méthodes encore
   appelées par le PHP stocké, puis sélectionner un lot runtime transversal
   qui ne duplique pas les frontières déjà établies.~~
   L'initialisation du contexte runtime est extraite et couverte par la
   Phase 19 ; les méthodes publiques restantes doivent maintenant être
   regroupées par parcours fonctionnel avant toute nouvelle réduction.
7. ~~Caractériser le parcours notifications/export de la façade avec ses
   appelants réels, puis injecter directement le moteur d'export et couvrir
   les délégations.~~
   Terminé par la Phase 22.
8. ~~Fermer les appels récursifs de soumission et injecter les moteurs de
   scripting, export et notification dans son orchestration.~~
   Terminé par les Phases 23 et 24.
9. ~~Comparer les trois renderers QuickMode actifs et mutualiser uniquement
   l'hydratation réellement commune des valeurs enregistrées.~~
   Terminé par la Phase 25.
10. ~~Sécuriser le bootstrap Composer et enregistrer le graphe des services
    Joomla 6 : prouver l'autoload réel après installation, enregistrer les
    services runtime dans le provider, supprimer les constructions manuelles
    du dispatcher et ajouter un test de résolution par le conteneur.~~
    Terminé par la Phase 26, puis complété par l’injection du graphe interne
    dans la Phase 27.
11. ~~Caractériser puis injecter le graphe interne d'`EngineDispatcher` par
    parcours fonctionnel (callbacks, rendu, soumission et runtime), sans
    modifier les contrats publics de la façade ni refactoriser plusieurs
    frontières dans un même lot.~~
    Terminé par la Phase 27. Les frontières internes des callbacks restent
    des lots séparés si une caractérisation supplémentaire est nécessaire.

Le rendu Classic des groupes radio et checkbox est désormais mutualisé dans
`ClassicChoiceGroupBuilder` (`478e1c24a`), avec couverture dédiée des wrappers,
labels, états checked/disabled, lignes invalides et mode sans retour à la ligne.
Les traductions du groupe et les différences d'enveloppe restent dans
`ClassicRenderer`.
Les deux points d'entrée Classic radio/checkbox délèguent désormais à une
méthode paramétrée unique, sans changer leurs sorties caractérisées.

Le smoke Joomla 6 avec ContentBuilderNG 6.1.14 valide maintenant en runtime
les loaders d'association, de données et d'enregistrement ContentBuilder
contre la base Joomla réelle. Le harnais initialise explicitement le contexte
web Joomla 6 et charge le contexte runtime ContentBuilder avant les loaders ;
les branches d'upload de fichiers existants et de restauration de signatures
exercent également les classes de rendu avec de vrais fichiers temporaires,
supprimés dans un bloc `finally`.

Lots récemment terminés : tests d'intégration des variantes Query List et du
point d'arrêt `bury()` dans la première boucle (`7a19ffeb`), contrôles de
fichiers ContentBuilder (`2c72231e`) et loader d'enregistrement éditable
(`89165de7`). Les scripts post-rendu et les champs techniques sont également
extraits et testés (`ad9dd75f`, `3d45e1e2`, `c53a457e`). Les champs de routage
`return` et `tmpl` sont désormais construits par
`FormRoutingFieldsBuilder` dans les trois branches de finalisation via
`f183a4ce`, avec échappement et absence de paramètres couverts. Le formatage
du token CSRF Joomla est désormais isolé dans `FormTokenFieldBuilder` et
réutilisé dans ces trois branches via `462b2984`, avec sa sortie indentée et
ses retours historiques testés. La couverture unitaire de la finalisation par mode est complète ; la recette runtime complète reste suivie ci-dessous. La
finalisation des paramètres de routage et du token est désormais isolée. Les
champs de contexte (`ff_contentid`, `ff_applic`, `ff_record_id`,
`ff_module_id` et `ff_runmode`) sont désormais générés par
`FormContextFieldsBuilder` dans les trois branches via `f7d06454`. Les
séparateur de lignes reste fourni par le renderer afin de préserver les
sorties de test et le comportement historique (`0261acd4`). La fermeture des
wrappers moderne et legacy est désormais construite par
`FormClosingMarkupBuilder`, branché dans `closeFormRendering()` via
`301ba9f1`. Les deux sorties restent couvertes par le test de caractérisation
de `RenderingEngine` et par des tests unitaires dédiés ; la validation runtime couvre les parcours frontend, backend et preview ContentBuilder. Le markup d'ouverture
du formulaire (wrapper moderne/legacy, identifiant et classe personnalisée)
est désormais construit par `FormOpeningMarkupBuilder` via `e77be68a`, avec
ses deux variantes couvertes par des tests unitaires.

La purge des fichiers temporaires Flash et chunked est désormais mutualisée
par `UploadFileCleaner` dans `FormRenderer`. Les suffixes, le critère de
nommage historique et l'expiration sont couverts par un test unitaire commun.

Le scan des thèmes de `QuickmodeModel` utilise désormais `DirectoryIterator`,
avec les répertoires techniques explicitement exclus et les cas de répertoire
absent couverts par un test unitaire.

Le nettoyage du cache de paiement est désormais porté par
`UploadFileCleaner`, en conservant son critère distinct de nommage à quatre
segments et son expiration à 24 heures ; ses cas limites sont testés dans le
même service que la purge des fichiers temporaires.

La lecture du répertoire de polices PDF est mutualisée par
`PdfFontDirectoryScanner` entre le document PDF, l'export et l'export des
enregistrements. La sélection des fichiers réguliers lisibles et le répertoire
absent sont couverts par des tests unitaires.

La résolution des modèles d'e-mail principaux et de mailback est mutualisée
par `MailTemplateResolver`, avec priorité au modèle HTML puis texte du
formulaire avant les modèles par défaut ; ces variantes sont testées.

La résolution des signatures ContentBuilder est désormais limitée à des
fichiers réguliers nommés dans le répertoire prévu; le cas de traversal est
couvert par le test du support fichiers.

L'encodeur d'image de signature vérifie également la lisibilité du fichier et
est couvert par des tests d'encodage et d'absence de fichier.

`QuickModeFormTagBuilder` échappe désormais les valeurs injectées dans les
attributs `action`, `name`, `id` et `class`; le test couvre les guillemets et
les séparateurs HTML.

`QuickModeUploadConfigurationBuilder` échappe désormais les valeurs injectées
dans les chaînes JavaScript de configuration; les champs, tickets, runtimes,
URL et extensions contenant des apostrophes sont testés.

`UploadStorage` vérifie désormais directement le résultat de `chmod`, sans
masquer les erreurs système, et conserve les statuts de stockage testés.

L'assemblage des chunks du callback Flash est désormais isolé dans
`FlashChunkAssembler`; la concaténation des fichiers existants et le rejet
d'un chunk absent sont couverts par des tests unitaires.

La sélection des fichiers Flash terminés par champ et ticket est désormais
isolée dans `FlashUploadFileMatcher`; les suffixes et les fichiers étrangers
sont couverts par des tests unitaires.

`SubmissionEngine` s'appuie désormais directement sur les fichiers filtrés par
le matcher et vérifie explicitement l'accessibilité du répertoire source; les
gardes de matching dupliquées ont été supprimées.

`ImageResizer` dispose désormais d'un filet de tests dédié pour la détection
PNG et les trois géométries de redimensionnement, en complément des tests de
taille mémoire et de capacité déjà présents.

La gestion des buffers de sortie et la lecture de `max_execution_time` dans
`SubmissionEngine` n'utilisent plus de suppression d'erreur; les niveaux de
buffer sont contrôlés avant fermeture.

`ImageResizer` rejette désormais explicitement les chemins absents ou non
lisibles avant d'interroger EXIF/GD, sans suppression d'erreur sur ces lectures.

`IntegratorRuntime` réutilise désormais `StoredPhpExecutor` pour exécuter les
scripts d'intégration dans son scope avec le contexte `$value` explicite; le
retour et le scope d'exécution sont couverts par des tests unitaires.

Les tests d'existence des assets statiques des trois renderers QuickMode
utilisent désormais `is_file()` sans masquage d'erreur; leurs sorties restent
couvertes par les tests de caractérisation des renderers.

Les paramètres additionnels `ff_otherparams` du mode frontend sont désormais
générés par `AdditionalHiddenFieldsBuilder` via `bb469b88`, avec ordre,
encodage URL et échappement HTML couverts. La détection des providers de
paiement est désormais isolée dans `PaymentProviderDetector` via `b5d1a55e`;
les trois providers historiques et l'absence de provider sont couverts par
des tests unitaires. Les champs conditionnels de finalisation sont désormais
caractérisés dans les trois parcours ; les appels de lecture Joomla restent
dans l'orchestrateur car ils dépendent du contexte d'exécution.

Les champs conditionnels `target`, `frame`, `border`, `page`, `align` et `top`
sont désormais générés par `FormOptionalContextFieldsBuilder` via
`fdbf8960`. Les paramètres frontend/backend complets et la variante preview
(page/frame uniquement) sont couverts par des tests unitaires, tandis que les
différences de mode restent exprimées explicitement à l'appel.

Les champs cachés de soumission communs aux trois modes sont désormais
assemblés par `FormSubmissionFieldsBuilder` (`be602b94f`). Frontend, backend et
preview conservent leurs marqueurs spécifiques (`act` ou `ff_frame`), tandis
que le formulaire et la tâche de soumission suivent une construction commune
testée. Les sorties exactes des trois variantes sont désormais verrouillées
par `dc73ec0fc`.

Le markup du choix mobile est désormais construit par
`MobileChoiceMarkupBuilder` (`39a4c1da0`). Le calcul de l'URL et la
normalisation des paramètres restent dans `RenderingEngine`, tandis que la
sortie JavaScript/HTML historique est couverte par un test dédié.

Le callback Query List `ff_selectAllQueryRows` est désormais construit par
`QueryListSelectAllScriptBuilder` (`0b1229d37`). La logique de pagination,
la remise à zéro des lignes hors page et la synchronisation de la case globale
sont couvertes séparément ; le callback de navigation `ff_dispQueryPage` reste
partiellement dans l'orchestrateur, mais son markup de navigation est
désormais construit par `QueryListNavigationBuilder` (`c93d06eb7`). Les
libellés traduits sont injectés par le moteur et les liens de navigation sont
couverts indépendamment ; le cœur de mise à jour des lignes est désormais
fourni par `QueryListRowsRefreshBuilder` et composé dans
`QueryListPageScriptBuilder` (`7ee50bccc`).

Le wrapper caché `bfReCaptchaWrap` est désormais construit par
`CaptchaWrapperMarkupBuilder` (`092a2a9fa`). Il reste limité au mode
`legacy_wrap`, et l'absence de saut de ligne historique est explicitement
préservée.

La vérification serveur ReCaptcha est isolée dans `RecaptchaVerifier`, qui
porte uniquement l'appel HTTP vers Google et son contrat booléen. Ses cas de
secret/réponse absents, de validation réussie, de réponse refusée et de statut
HTTP en erreur sont couverts par `RecaptchaVerifierTest`. La génération du
JavaScript de validation reste séparée dans
`CaptchaReCaptchaValidationScriptBuilder`.

L'assemblage de la balise `<form>` QuickMode est désormais confié à
`QuickModeFormTagBuilder` (`4f5b8559d`). Le calcul de l'action et la
résolution de la classe personnalisée restent dans `RenderingEngine`, tandis
que l'ordre des attributs et la classe `bfQuickMode` sont couverts par des
tests exacts.

L'enveloppe du script ContentBuilder des champs non éditables est désormais
construite par `ContentBuilderReadonlyScriptWrapperBuilder` (`3e1723d15`).
L'enregistrement de l'asset et la génération du contenu restent séparés, et
les marqueurs HTML historiques sont couverts par un test de sortie exacte.

Le wrapper `bfLoadEditable` est désormais construit par
`EditableRecordScriptWrapperBuilder` (`f3b6f8d97`). Le moteur conserve le
nettoyage des valeurs et la génération du contenu d'hydratation, tandis que
le chargement JavaScript, l'identifiant du formulaire et l'effacement
historique de `ff_nm_seccode[]` sont isolés et testés.

Le calcul de page et le rafraîchissement des lignes Query List sont désormais
construits par `QueryListRowsRefreshBuilder` (`7ee50bccc`). Le déplacement
des valeurs, la gestion des colonnes masquées et le masquage des lignes hors
page sont testés indépendamment ; la fonction de navigation conserve encore
son orchestration finale et ses appels de callbacks.

La fin de `ff_dispQueryPage` est désormais construite par
`QueryListPaginationTailBuilder` (`c942cfe90`). Les comportements optionnels
de resynchronisation des checkbox, de redimensionnement, de défilement dans
une iframe et de défilement général sont testés par combinaisons ; la gestion
des variables d'état et l'appel au callback restent dans le moteur.

L'initialisation des tableaux JavaScript Query List est désormais fournie par
`QueryListStateLibraryBuilder` (`70c6aa58b`). L'ordre des sept entrées
(`ff_queryCurrPage` à `ff_queryRows`) et l'injection du code de données restent
explicitement couverts ; l'orchestration de la liaison des callbacks demeure
dans `RenderingEngine`.

Les affectations d'état propres à chaque ligne Query List sont désormais
construites par `QueryListRowStateBuilder`. La préparation conserve dans
`RenderingEngine` la compilation des colonnes et l'exécution de la requête,
tandis que la génération de `ff_queryCurrPage`, `ff_queryCols` et
`ff_queryRows` est pure et couverte par un test de sortie exacte.

La préparation complète d'une ligne est désormais portée par
`QueryListRowPreparationService` : compilation des définitions de colonnes,
exécution de la requête et production de l'état JavaScript sont regroupées
dans un service testable. `RenderingEngine` ne conserve que le compteur de
checkboxes, le stockage dans l'état du processeur et l'assemblage du script.

L'assemblage final de `ff_dispQueryPage` est maintenant confié à
`QueryListPageScriptBuilder`, qui compose les services de rafraîchissement,
de navigation et de fin de pagination. Le moteur conserve la traduction des
libellés, les options du formulaire et l'appel `linkcode`.

L'enregistrement des callbacks de formulaire et d'élément est maintenant
mutualisé dans `CallbackRegistrationService`. L'ordre `init`/`action`/
`validate`, les points d'arrêt `bury()` et la fermeture historique du buffer
après validation restent inchangés et sont couverts par le harnais de
caractérisation et un test direct du service. Les deux callbacks de bordure
d'icône sont également enregistrés par ce service, avec leurs deux points
d'arrêt historiques conservés. Le callback de scan `#scanonly` des éléments
`Static Text/HTML` y est également centralisé, avec sa garde de type.

L'enveloppe du script global de validation est désormais construite par
`FormValidationScriptWrapperBuilder` (`9b92acb55`). Les contrôles de fichiers
et de CAPTCHA restent générés par leurs services respectifs, tandis que les
marqueurs du bloc JavaScript et leur ordre historique sont testés.

La génération de `checkFileExtensions()` est désormais isolée dans
`FileExtensionsCheckBuilder` (`30dd41889`). Le builder reçoit les lignes du
formulaire, le nombre de lignes, le message déjà traduit et l'indication de
template configuré ; il conserve donc l'orchestration Joomla dans
`RenderingEngine` tout en testant séparément les uploads configurés et la
sortie neutre lorsque le template n'est pas renseigné.

L'initialisation de formulaire est désormais caractérisée de bout en bout au
niveau de sa méthode dédiée par `0d33f0253` : les registres Query List sont
réinitialisés, le wrapper ReCaptcha legacy est présent uniquement dans ce
mode et l'ouverture du formulaire conserve son identifiant.

Les sorties anticipées de `RenderingEngine::view()` après activation du buffer
et du gestionnaire d'erreurs nettoient désormais leur propre état via
`abortViewRendering()` (`2d4f75ef`). Les chemins `bury()` et callbacks sont
couverts par les tests de caractérisation, qui vérifient le niveau de buffer
restitué au code appelant et ne produisent plus de tests PHPUnit risqués. La
finalisation normale est regroupée dans `finishViewRendering()` (`ced03d7a`) :
les ordres `dumpTrace()`/`ob_end_flush()` des modes direct et non direct sont
caractérisés et testés, ainsi que la fermeture `</pre>` du mode direct.

Les champs de contexte et le cycle d'enveloppe de `view()` sont désormais
isolés en builders testés : `FormContextFieldsBuilder` (`f7d06454`),
`FormOpeningMarkupBuilder` (`e77be68a`) et `FormClosingMarkupBuilder`
(`301ba9f1`). La génération du token et des paramètres de routage reste
également partagée (`462b2984`, `f183a4ce`), avec conservation explicite du
séparateur de lignes (`0261acd4`). La préparation du contexte et des champs
techniques est centralisée dans `RenderingEngine` ; seules les lectures
Joomla/ContentBuilder propres au runtime restent dans l'orchestrateur.

L'ordre final des champs cachés et la fermeture du formulaire sont désormais
assemblés par `FormModeFinalizationBuilder` (lot de finalisation courant) pour les modes
frontend, backend et preview. Les décisions Joomla et la génération des
fragments restent dans `RenderingEngine`, tandis que les variantes d'ordre
et l'absence de sortie preview hors iframe sont couvertes par des tests.

La construction répétée des champs techniques ContentBuilder est désormais
réduite à `buildContentBuilderTechnicalFields()` dans `RenderingEngine` ; les
trois modes continuent de choisir leur ordre et leurs marqueurs via
`FormModeFinalizationBuilder`, tandis que le test de caractérisation de
`view()` couvre les sorties frontend, backend et preview, y compris l'émission
des trois champs techniques ContentBuilder lorsque la requête les fournit.
La construction du tableau de contexte commun est également centralisée dans
`buildFormContext()`, avec le seul ajout conditionnel de `ff_runmode` pour le
backend et la preview.

La façade `helpers.php` ne dépend plus des fonctions supprimées ou obsolètes
de PHP 8.3 pour `bf_isUTF8()` et `bf_stripslashes_deep()`. La validation UTF-8
utilise désormais `preg_match('//u', ...)`, la détection de BOM est explicite
pour les tableaux d'octets, et le retrait des magic quotes est un no-op puisque
ce mécanisme n'existe plus. Ces contrats sont couverts par
`LegacyHelpersTest`.

Dans `cbCheckPermissions()`, l'orchestration des droits est désormais confiée
à `ContentBuilderPermissionChecker`, qui réutilise le même service pour
`checkPermissions()` et l'autorisation `fullarticle`. L'objet Input n'est
capturé qu'après la détection de ContentBuilderNG, ce qui conserve le retour
neutre lorsque le runtime externe est absent. Le smoke Joomla couvre désormais
la résolution d'une source BreezingForms et le chargement d'un nouvel
enregistrement ; une configuration ContentBuilder ACL réelle reste nécessaire
pour valider de bout en bout les permissions d'un enregistrement.

Les appels de messagerie restants utilisent désormais l'API Joomla 6
(`addRecipient`, `addAttachment`, `isHtml` et `send`) dans la façade helpers,
le callback Sofort et le double opt-in de `SubmissionEngine`. Une vérification
automatisée empêche le retour des noms de méthodes historiques.

Le déplacement des fichiers uploadés dans `SubmissionEngine` utilise
désormais `Joomla\\Filesystem\\File::copy()` sans suppression d'erreur et
traite explicitement les retours négatifs de copie et de suppression avant de
valider le déplacement.

Les quatre renderers QuickMode ont ensuite rejoint le périmètre PHPCS après
le traitement ciblé de leurs violations de formatage héritées. Le ruleset
complet est désormais vert, sans bloquer les extractions fonctionnelles.

`ClassicRenderer` a reçu les corrections automatiques PHPCBF et ses trois
erreurs structurelles manuelles dans `e25d501f`; les avertissements de
longueur restent explicitement hors de ce lot. `MobileRenderer` a reçu le même
traitement dans `14985d4d`; ses snapshots et PHPStan sont verts, avec les
avertissements de longueur conservés pour un lot ultérieur.
`ClassNameResolver` et l'appel multi-ligne restant de `BootstrapRenderer` ne
présentent désormais plus d'erreur PHPCS via `0fc70f33`; les avertissements de
longueur de Bootstrap restent documentés comme hérités.
Les builders de finalisation et de validation ContentBuilder ajoutés dans ces
lots ne présentent plus d'avertissement de longueur via `4d64cc7c`; les
chaînes produites restent inchangées et couvertes par les tests.
`BootstrapRenderer` a été traité dans `288b42a4`; ses snapshots et PHPStan
sont également verts. `OnePageRenderer` a reçu le même traitement dans
`72204865`; ses snapshots et PHPStan sont verts également. Les quatre
renderers ont désormais une baseline sans erreurs PHPCS, les avertissements de
longueur restant à traiter séparément. Le trait partagé
`BootstrapStyleFieldTrait` est désormais inclus dans le périmètre PHPCS par
`212b6450` et ne présente plus d'erreur, avec ses avertissements de longueur
documentés.

Le contrôle partagé `bfTextfield`/`bfNumberInput` est commité dans
`065cef94` et branché dans les wrappers Classic/Mobile. Le trait Bootstrap
commun aux wrappers Bootstrap/OnePage l'utilise désormais aussi via
`f3d04e55`, sans perte de classes, icônes ni attributs de thème. Les autres
familles de champs ont ensuite été traitées par les lots QuickMode suivants.
La décision de type, les traductions, les longueurs et les bornes sont
désormais regroupées dans `QuickModeTextFieldStrategy` (`059885a45`) et
utilisées par les quatre renderers ; les enveloppes et effets annexes restent
propres à chaque thème.

Le contrôle `bfTextarea` est désormais extrait dans
`QuickModeTextareaBuilder` et branché dans les quatre renderers par
`620b7efe`. Les snapshots QuickMode restent verts (`84 tests, 230
assertions`) et le service est couvert par des tests d'échappement, de
placeholder et d'attributs structurels. Le compteur de longueur est désormais
couvert par `QuickModeMaxLengthCounterBuilder` (`bdb7d910`) ; ses événements
restent volontairement dans les renderers jusqu'à caractérisation complète.
La logique commune de textarea brut (traductions, dimensions et callback de
longueur) est également centralisée dans `QuickModeTextareaStrategy`
(`d57debb07`), tandis que l'intégration de l'éditeur HTML reste dans les
renderers.

Les lots 3 à 5 peuvent être préparés en parallèle du lot 6, à condition que le
branchement dans `RenderingEngine.php` soit coordonné.

Le parsing des règles `toggleFields`, commun aux quatre renderers QuickMode,
est désormais confié à `QuickModeToggleFieldsParser` (`37c554755`). Les
méthodes publiques historiques des renderers délèguent au parser afin de
préserver le contrat appelé par la façade ; les règles invalides, les retours
CRLF et les valeurs contenant des espaces sont couverts.

La normalisation des options de calendrier est désormais confiée à
`QuickModeCalendarOptionsBuilder` (`a2e025609`). Les quatre renderers
réutilisent ce service pour les valeurs booléennes, les formats Pickadate, le
premier jour de la semaine et le nombre d'années sélectionnables ; les
snapshots de calendrier restent inchangés.

La construction de l'expression JavaScript d'accès aux éditeurs est désormais
confiée à `QuickModeEditorValueBuilder` (`43592c8bd`). Bootstrap et OnePage
conservent leurs méthodes publiques `getEditorContent()` comme délégations,
avec la sortie historique couverte par un test unitaire.

Le mapping Bootstrap 5 commun à Bootstrap et OnePage est désormais fourni par
`QuickModeBootstrapClassMapBuilder` (`658078588`). La méthode publique
`bsClass()` reste dans chaque renderer et les 54 associations historiques
restent couvertes par un test de mapping.

Le contrôle `bfCheckbox` est désormais extrait dans
`QuickModeCheckboxBuilder` et branché dans les quatre renderers par
`18a3e7ea`. Les variantes checked/unchecked, valeur échappée, attributs
d'événements et états de lecture seule sont couvertes ; les champs cachés
associés à `mailbackAccept` restent dans chaque renderer car ils appartiennent
au comportement d'enveloppe du champ.
La préparation commune du contrôle (valeur, état checked et désactivation
readonly) est maintenant portée par `QuickModeCheckboxStrategy`
(`40bf6269d`) ; les wrappers et les champs mailback restent propres aux
renderers.

Le contrôle `bfSelect` est désormais extrait dans `QuickModeSelectBuilder` et
branché dans les quatre renderers par `8a891fb5`. La stratégie couvre les
options sélectionnées, l'échappement des libellés et valeurs, `multiple`, les
attributs d'événements et les styles de dimensions. La différence historique
de `data-chosen` entre Mobile et les autres renderers est conservée par une
option explicite du builder.

Les contrôles élémentaires des groupes radio et checkbox sont désormais
partagés via `QuickModeGroupOptionBuilder` et branchés dans les quatre
renderers par `a85bed34`. Les différences d'enveloppe, de label, de saut de
ligne et de classe Bootstrap restent dans les renderers ; les valeurs et
attributs des options sont testés avec leurs variantes checked/unchecked.
Les enveloppes Bootstrap et OnePage de ces groupes sont maintenant mutualisées
dans `QuickModeBootstrapChoiceGroupBuilder`, tandis que les classes de thème
restent injectées par le trait commun.

Le rendu du champ `bfSubmitButton` est désormais partagé via
`QuickModeSubmitButtonBuilder` et branché dans Classic, Mobile et le trait
Bootstrap commun à Bootstrap/OnePage par `92b4af6f`. Les variantes bouton et
image, les attributs de thème et l'ordre HTML historique sont couverts ; les
callbacks JavaScript restent préparés dans chaque renderer.

Le bouton de déclenchement des calendriers est désormais partagé via
`QuickModeCalendarButtonBuilder` et branché dans Classic, Mobile et le trait
Bootstrap commun à Bootstrap/OnePage par `7911701f`. Les ordres d'attributs,
classes, valeurs et contenus d'icône/label propres aux renderers sont
préservés par les paramètres du builder.

L'input texte des calendriers est désormais partagé via
`QuickModeCalendarInputBuilder` et branché dans Classic, Mobile et le trait
Bootstrap commun à Bootstrap/OnePage par `1d623384`. Les classes de thème,
styles de largeur, valeur échappée et espacement historique de l'attribut
`id` sont couverts par les snapshots et le test unitaire du builder.

Les appels d'initialisation des calendriers responsive et Mobile sont désormais
partagés via `QuickModeCalendarInitScriptBuilder` et branchés par `1de4b129`.
Les paramètres JSON, le drapeau `hasYearScroller` et le libellé traduit sont
couverts ; le chargement des assets reste dans les renderers car il dépend du
cycle d'asset Joomla et de l'état local du renderer.

La construction de l'URL du CAPTCHA image est désormais partagée via
`QuickModeCaptchaUrlBuilder` et branchée dans les quatre renderers par
`21afa208`. Les variantes frontend et administrateur sont couvertes ; le
markup d'image, de saisie et de rechargement ainsi que les scripts CAPTCHA
restent séparés en raison de leurs différences de thème et de comportement.

Le markup commun de l'image CAPTCHA et du champ de réponse est désormais
centralisé dans `QuickModeCaptchaMarkupBuilder` et branché dans les quatre
renderers par `176af451`. Les attributs de dimensions, classes de thème,
valeurs échappées et espacements historiques sont couverts par les snapshots
et les tests unitaires ; les contrôles de rechargement restent spécifiques à
chaque renderer.

Le corps JavaScript de rechargement CAPTCHA est désormais centralisé dans
`QuickModeCaptchaReloadScriptBuilder` et branché dans les quatre renderers par
`4d706e95`. Le nettoyage du champ, le focus et l'ajout de
`bfMathRandom` sont couverts par un test unitaire ; les balises et icônes
visuelles restent propres à chaque renderer.

Le rendu ReCaptcha visible et invisible est désormais centralisé dans
`QuickModeReCaptchaFieldBuilder`. Le builder conserve la configuration
spécifique de chaque renderer (réinitialisation, upload Flash et widget
visible), enregistre les scripts via `RuntimeAssetLoader` et laisse uniquement
les enveloppes Bootstrap/OnePage et Classic aux renderers. Les snapshots des
trois parcours et les branches clé absente/visible/invisible sont couvertes.

La normalisation des options d'upload est désormais centralisée dans
`QuickModeUploadOptionsBuilder` et branchée dans les quatre renderers par
`94a1345d`. Les extensions autorisées et la taille maximale sont couvertes
par des tests unitaires et alimentent toujours le même JavaScript historique.
Les options `multi_selection` et `runtimes` sont ensuite partagées par
`eaa777e8`, avec couverture des combinaisons HTML5/Flash/HTML4 et de la
sélection multiple. Les dimensions positives et les valeurs par défaut du
bouton d'upload sont partagées par `ddea69f8` dans les renderers Classic et
Mobile. La valeur numérique utilisée par le contrôle client de taille est
également fournie par le builder et consommée par les quatre renderers via
`9535e3a`, supprimant le dernier recalcul PHP de cette limite. Le markup et
les callbacks de configuration complète de l'uploader sont désormais répartis
dans `QuickModeUploadEntryCallbacksBuilder`,
`QuickModeUploadFileAddedHandlerBuilder`,
`QuickModeUploadProgressScriptBuilder`,
`QuickModeUploadCompletedScriptBuilder`,
`QuickModeUploadValidationScriptBuilder` et
`QuickModeUploadCancelScriptBuilder`; leurs sorties sont testées. Les
enveloppes visuelles et les différences de thème restent propres aux
renderers.

Le callback JavaScript `bfUploadImageThumb()` est désormais généré par
`QuickModeUploadThumbnailScriptBuilder` (`704ccbea2`) pour les quatre
renderers. Le chemin Moxie, la destruction après intégration, la détection des
extensions image et le repli `FileReader` sont couverts par un test dédié ;
les différences de markup et la configuration plupload restent dans chaque
renderer.

La configuration commune de l'objet `plupload.Uploader` est désormais fournie
par `QuickModeUploadConfigurationBuilder` (`218990e11`) et utilisée par les
renderers Classic, Bootstrap et OnePage. Les différences d'URL SEF sont
injectées explicitement ; les callbacks et les enveloppes visuelles restent
propres à chaque renderer.

Le calcul du chemin de base de l'uploader, y compris la suppression du segment
`administrator` dans l'URI backend historique, est désormais centralisé dans
`QuickModeUploadBasePathBuilder`. Les cas frontend, backend et chemin non
concerné sont couverts par un test dédié ; la construction du markup et la
configuration plupload restent propres à chaque renderer (`8745eb8f2`).

Les callbacks contigus d'entrée de file (`FilesAdded` initial et ajout des
fichiers) sont désormais composés par
`QuickModeUploadEntryCallbacksBuilder`; les variantes Bootstrap et Classic sont
couvertes par des tests dédiés.

Le corps commun du callback `FilesAdded` est désormais fourni par
`QuickModeUploadFileAddedHandlerBuilder` (`941f1ed6d`). La boucle de validation,
la création de la file d'attente et l'annulation sont ainsi mutualisées ; les
variantes de markup et de réactivation du bouton restent paramétrées par le
renderer.

Le callback plupload `UploadProgress` est désormais généré par
`QuickModeUploadProgressScriptBuilder` (`1e28a79b2`) pour les quatre renderers.
La mise à jour du pourcentage et de la barre visuelle reste inchangée et est
couverte par un test de sortie ; les callbacks d’ajout, d’erreur et de fin
d’upload ont ensuite été traités par les builders dédiés.

Le callback plupload `FileUploaded` est désormais généré par
`QuickModeUploadCompletedScriptBuilder` (`6d3bfbb27`) pour les quatre
renderers. La restitution éventuelle de la réponse serveur et la suppression
de la ligne de queue sont couvertes par un test dédié ; la validation de la
liste de fichiers et la configuration complète de l’uploader ont ensuite été
extraites par les builders dédiés.

La validation client des fichiers est désormais générée par
`QuickModeUploadValidationScriptBuilder` (`fea7df7b6`) pour les quatre
renderers. La limite de taille, les extensions autorisées, les messages
d’erreur et le nettoyage des fichiers invalides sont couverts par un test dédié ;
les paramètres propres au champ restent injectés par chaque renderer.

La gestion d’annulation est désormais générée par
`QuickModeUploadCancelScriptBuilder` (`6cf0473ad`) pour les quatre renderers.
L’arrêt puis le redémarrage des uploaders, la suppression des lignes, le
compteur global et les deux variantes de réactivation du bouton sont couverts
par des tests dédiés.

La première boucle `FilesAdded`, qui notifie `bfUploadFileAdded()` et ajoute le
libellé nettoyé à `#bfFileQueue`, est désormais générée par
`QuickModeUploadQueueEntryScriptBuilder` (`e6956c7d5`). La variante Bootstrap
conservant sa ligne vide historique est paramétrée explicitement et la sortie
est couverte par un test dédié.

Le markup de ligne `bfFileQueueItem` est désormais généré par
`QuickModeUploadQueueItemMarkupBuilder` (`e081b2f44`). Les variantes de nom
conditionnel des renderers Bootstrap/OnePage, l’attribut `border` de Mobile et
les chemins d’image d’annulation sont paramétrés explicitement et couverts
par des tests dédiés.

Les quatre renderers ne possèdent plus chacun leurs cinq adaptateurs privés de
calendrier : `CalendarOptionsTrait` (`05090635f`) délègue les booléens, le
format Pickadate, le premier jour et la plage d'années au builder partagé. Les
sorties QuickMode restent inchangées et la duplication d'orchestration est
réduite sans créer de classe de base commune.

Le callback OnePage `bf_validate_prevpage` est désormais construit par
`QuickModePreviousPageValidationBuilder` (`f19eb5f89`). Les chemins d'erreur,
la validation de la page courante, le déplacement vers la page précédente et
le commentaire historique conservé sont couverts par un test de sortie.

Le callback `bf_validate_nextpage` est désormais construit par
`QuickModeNextPageValidationBuilder` (`114dd4d17`). La validation de la page
courante, la gestion des erreurs, l'arrêt de Ladda et l'activation de la page
suivante sont couverts par un test dédié.

Le callback `bf_validate_submit` est désormais construit par
`QuickModeSubmitValidationBuilder` (`280fccd6e`). Les chemins d'erreur et de
succès, le focus, la restauration du bouton et l'appel à `ff_submitForm()`
sont couverts par un test dédié ; les responsabilités de restauration du
bouton sont désormais isolées dans `QuickModeSubmitButtonRestoreBuilder`
(`bfe93521e`) ; elles conservent leurs dépendances JavaScript historiques.

Le conteneur d'erreur Bootstrap est désormais construit par
`QuickModeErrorMessageMarkupBuilder` (`9d5a288c2`) et partagé par Bootstrap et
OnePage. Les classes de thème restent fournies par chaque renderer, tandis
que le markup caché commun est testé indépendamment ; les variantes Classic et
Mobile restent distinctes.

Le widget de progression est désormais construit par
`QuickModeProgressMarkupBuilder` (`5cb2da294`) et partagé entre Bootstrap et
OnePage. Le calcul de progression, la remise à zéro et l'exclusion éventuelle
de la page Thank You sont testés sans déplacer la gestion des assets hors des
renderers.

La déclaration runtime des erreurs est désormais construite par
`QuickModeErrorRuntimeConfigBuilder` (`45fc45988`). Bootstrap et OnePage
partagent les trois variables de configuration, tandis que la portée des
erreurs reste explicitement paramétrée (`false` pour Bootstrap, `true` pour
OnePage) et testée.

La fermeture du modal Thank You est désormais construite par
`QuickModeRemodalCloseScriptBuilder` (`ca2b8f42`). La priorité donnée à
`crbc_cart_url`, puis le retour vers l'URL courante du formulaire, restent
inchangés et sont couverts par un test dédié.

La soumission AJAX OnePage est désormais construite par
`QuickModeAjaxSubmitScriptBuilder`. L'envoi sérialisé, le verrouillage visuel
des pages, la gestion d'échec, la redirection panier/URL courante et les deux
branches de succès (page Thank You ou redirection directe) sont couverts par
un test de sortie ; l'orchestrateur conserve le choix de la page Thank You et
l'injection des messages et de l'URL Joomla.

La baseline PHPCS est maintenant sans erreur sur les services modernes, les
quatre renderers QuickMode et leurs traits (`e25d501f`, `14985d4d`, `288b42a4`,
`72204865`, `212b6450`, `aaabc40c`). Le ruleset complet est vert ; la qualité
de formatage n'est donc plus un prérequis bloquant pour les extractions de
comportement suivantes.

## Vérification obligatoire par lot

## Lots récents

`ContentBuilderSignatureScriptBuilder` protège désormais le sélecteur de champ
par un littéral JavaScript JSON strict ; `QuickModeCaptchaMarkupBuilder`
échappe les valeurs d'attribut HTML et `QuickModeCaptchaReloadScriptBuilder`
protège l'URL injectée dans le callback de rechargement. Les tests couvrent
les entrées contenant des guillemets et du code JavaScript ; les snapshots
CAPTCHA des quatre thèmes ont été actualisés pour l'encodage HTML attendu
(`8ed97fb3c`).

`QuickModeUploadQueueItemMarkupBuilder` échappe maintenant le chemin de
l'image d'annulation à la fois pour le fragment HTML et pour la chaîne
JavaScript qui l'embarque. Une régression d'injection dans ce chemin est
couverte par un test dédié (`5aec57c30`).

`QuickModeUploadThumbnailScriptBuilder` protège la base d'URL de `Moxie.swf`
dans son littéral JavaScript (`a5f57ce15`). Les messages de taille et
d'extension ainsi que la liste d'extensions sont désormais encodés au point
de construction par `QuickModeUploadValidationScriptBuilder`, ce qui retire
le pré-encodage divergent des trois renderers (`474408bee`). Les sorties
normales et les chemins d'upload des quatre thèmes sont caractérisés.

Les endpoints injectés dans les callbacks CAPTCHA historiques sont désormais
échappés comme contenu de littéral JavaScript par
`CaptchaLegacyValidationScriptBuilder` et
`CaptchaReCaptchaValidationScriptBuilder`. Les flux AJAX legacy et ReCaptcha
conservent leurs délimiteurs et leur comportement, avec couverture des URL
hostiles (`b80c88d37`).

Le markup d'image des moyens de paiement est désormais mutualisé dans
`QuickModePaymentImageBuilder`, utilisé par Classic et le trait Bootstrap.
Les sources et textes alternatifs sont échappés et testés, tandis que les
callbacks de paiement et les valeurs historiques restent propres à chaque
renderer (`1cd836395`).

Le harnais de `RenderingEngine::view()` couvre désormais explicitement la
sortie anticipée déclenchée par une pièce « before form » qui demande
`bury`, et vérifie que les sections de validation suivantes ne sont pas
émises (`0a2ce63d5`).

Le même harnais couvre désormais l'abandon après les callbacks de formulaire,
en vérifiant l'ordre des callbacks `init` et `submitted` et l'absence des
sections d'éléments et de Query List (`5a50a93fd`).

La branche Query List est également caractérisée au-delà de ses points
d'abandon internes : la fonction `ff_dispQueryPage` est enregistrée dans la
bibliothèque avec son état de page et sa navigation (`a743e9c67`).

Le harnais atteint maintenant la finalisation complète de `view()` sur un
formulaire QuickMode vide : ouverture et fermeture du formulaire, champs de
contexte, validation et nettoyage du buffer sont couverts sans dépendance
Joomla externe (`502998adb`).

La branche de finalisation backend est désormais caractérisée séparément,
notamment l'émission du champ `ff_runmode`, avec la même vérification de
fermeture et de nettoyage (`4f89b4029`).

La branche preview est également couverte sur son parcours in-frame, avec
vérification de `ff_runmode`, `ff_frame` et de la fermeture du formulaire
(`b1a2b5973`).

La sélection du renderer QuickMode par les métadonnées est maintenant
caractérisée pour Classic, Bootstrap et OnePage, avec des métadonnées de
constructeur explicites dans le harnais (`912e7ed47`).

La garde du script post-rendu ne répète plus le test de disponibilité de
`bfToggleFieldsLoaded`; un test vérifie qu'elle ne contient qu'une occurrence
(`dd1eee60b`).

Les builders partagés `QuickModeInputBuilder`, `QuickModeCheckboxBuilder` et
`QuickModeGroupOptionBuilder` échappent désormais toutes leurs valeurs
d'attributs dynamiques, avec couverture des classes, types, noms et
identifiants hostiles (`92f6e3e59`).

`QuickModeTextareaBuilder` et `QuickModeSelectBuilder` appliquent maintenant
la même règle aux classes et noms de champs partagés par les renderers, avec
tests d'injection et snapshots inchangés (`b80711c89`).

Le markup `bfHidden` partagé par les renderers Classic, Bootstrap et OnePage
est maintenant produit par `QuickModeHiddenFieldBuilder`, appelé par le trait
commun. La sortie historique est caractérisée et les attributs nom/id sont
échappés dans le builder pur (`d4756b71c`).

Le contrôle natif utilisé par les branches d'upload QuickMode est maintenant
mutualisé dans `QuickModeFileInputBuilder` pour Classic, Bootstrap et OnePage.
Les variantes `file` et placeholder `hidden`, les attributs d'événements et
les noms de champs sont couverts sans modification des snapshots (`299157a91`).

Les contrôles internes des six boutons de paiement Classic et Bootstrap/OnePage
sont maintenant construits par `QuickModePaymentButtonBuilder`. Les variantes
submit/image, callbacks, fournisseurs et sorties historiques sont couvertes,
tandis que les wrappers propres aux thèmes restent dans les renderers
(`40b282362`).

Le cœur canvas et bouton de réinitialisation des signatures est maintenant
mutualisé dans `QuickModeSignatureMarkupBuilder` entre Classic et
Bootstrap/OnePage. Les marqueurs de champ et classes de bouton propres au
thème restent paramétrables ; les sorties caractérisées et l’échappement du
marqueur sont testés (`5ac9a8786`).

Le parcours de génération des scripts d’un enregistrement ContentBuilder est
maintenant isolé dans `ContentBuilderEditableRecordScriptBuilder`. Il traite
les familles de valeurs, choix, listes, fichiers et signatures, filtre les
champs non éditables et reçoit les opérations dépendantes du runtime via des
closures typées. Le nettoyage des valeurs s'effectue désormais dans une
variable locale afin de ne pas modifier les objets d'enregistrement fournis.
`RenderingEngine::view()` ne conserve plus que l’orchestration et l’enveloppe
historique (`09cf02975`).

Le harnais unitaire couvre également les branches QuickMode d'upload (compteur,
validation, contrôles de suppression et désactivation) ainsi que la résolution
et l'encodage d'une signature existante (`7fe0e95a9`). La validation avec le
runtime ContentBuilder installé reste un contrôle d'intégration séparé. Il
couvre désormais aussi l'absence de signature et le rejet explicite des
chemins qui sortent du répertoire autorisé.

Les branches radio, liste de sélection et calendrier de l’orchestrateur sont
également caractérisées dans le harnais local (`efefb400c`).

Le périmètre PHPCS inclut désormais l’orchestrateur ContentBuilder et les
quatre builders QuickMode ajoutés récemment. Les références vers six builders
supprimés ont été retirées de `phpcs.xml.dist`, et le dernier warning de
longueur de `QuickModeCheckboxBuilder` a été éliminé sans changement de sortie
(`380d7bd7c`). Le ruleset complet passe maintenant sans erreur ni warning.

Le smoke test Joomla 6 du package `6.1.0-RC05` a été exécuté avec succès :
installation, réinstallation de mise à jour, enregistrement des extensions,
création des tables, réponse frontend HTTP 200 et génération d’une image
CAPTCHA Securimage sont validés.

Le package a ensuite été reconstruit depuis les fichiers versionnés ; sa liste
contient les nouveaux services QuickMode et ContentBuilder, et le même smoke
test a de nouveau réussi sur cette archive reconstruite.

Le smoke test accepte désormais `CONTENTBUILDER_ARCHIVE` pour installer et
vérifier ContentBuilderNG avant BFNG. Cette variante a été exécutée avec
`/home/xavier/workspaces/vcmb/com_contentbuilderng/build/com_contentbuilderng-6.1.9.zip` et a réussi sur Joomla
6, levant le blocage de validation d’intégration (`cb1822792`).
Le contrôle vérifie désormais aussi la présence de la table publiée
`contentbuilderng_forms`, utilisée par la lecture des associations et des
métadonnées de `RenderingEngine`.

La sélection Classic, Bootstrap et OnePage est désormais isolée dans
`QuickModeRendererFactory`. `RenderingEngine` ne porte plus la construction
conditionnelle des renderers ; les trois branches sont testées directement et
le test de caractérisation de `view()` utilise la factory (`1c9e497ab`).

Le script de désactivation des sections est désormais partagé par Classic,
Bootstrap et OnePage via `QuickModeDeactivatedSectionScriptBuilder`. Le nom
de section reste encodé en JSON et la sortie historique est verrouillée par
un test dédié. Le même `QuickModeDeactivationScriptBuilder` centralise
également le script `bfRegisterDeactivatedField` des trois renderers, sans
modifier leurs conditions d'activation.

L'enveloppe de registration `bfRegisterHtmlTextarea` est désormais partagée
par Classic, Bootstrap et OnePage via `QuickModeHtmlTextareaScriptBuilder`.
La récupération du contenu de l'éditeur reste propre à chaque renderer ; le
builder accepte donc explicitement l'expression déjà préparée.

L'invocation JavaScript de ReCaptcha et son markup visible/invisible sont
désormais construits par `QuickModeReCaptchaFieldBuilder`, qui réutilise
`QuickModeReCaptchaInitScriptBuilder` dans les trois renderers. Le chargement
des assets est centralisé via `RuntimeAssetLoader`, tandis que les enveloppes
Bootstrap/OnePage et Classic et leurs paramètres de réinitialisation restent
explicitement injectés par les renderers. Les snapshots des trois parcours et
les branches visible/invisible sont conservés.

La factory est également incluse dans le périmètre PHPCS ; le ruleset complet
reste vert après son ajout (`7434564bc`).

Les deux scripts `onload` de formulaire sont maintenant produits par
`FormOnloadScriptBuilder`. Le builder couvre l'initialisation initiale, les
hooks de hauteur/grille, le callback après soumission et l'omission complète
quand aucun hook n'est requis ; `RenderingEngine` conserve la résolution des
callbacks et leur enregistrement via `linkcode` (`FormOnloadScriptBuilder`).

La préparation complète d'une ligne Query List et l'assemblage de
`ff_dispQueryPage` sont respectivement portés par
`QueryListRowPreparationService` et `QueryListPageScriptBuilder`. Les
registrations de callbacks formulaire, élément, icône et scan `#scanonly` sont
mutualisées dans `CallbackRegistrationService`. Les petits blocs restants de
`RenderingEngine` sont conservés lorsqu'ils sont trop courts ; les pièces
`before/after` et la résolution SQL de l'`onload` soumis nécessitent une
frontière runtime Joomla plus large pour éviter le surdécoupage.

Les pièces `before/after` sont désormais exécutées par
`FormPieceExecutionService` : la requête commune des pièces publiées et les
branches custom/library sont isolées, tandis que `RenderingEngine` conserve
les libellés traduits et le nettoyage du rendu interrompu.

La résolution SQL du callback `script2cond` après soumission est désormais
portée par `SubmittedCallbackNameResolver`. `RenderingEngine` ne conserve que
la composition de l'`onload` et son enregistrement.

Le parseur `RequestParameterParser` dispose maintenant d'un filet de sécurité
direct sur les paramètres séparés par `&` ou `&amp;`, les valeurs vides, les
noms ignorés et les valeurs contenant plusieurs signes `=`. Le comportement
historique de la préparation des paramètres runtime est ainsi verrouillé avant
toute évolution de son appelant.

Le service pur `TraceModeFormatter` est également caractérisé directement :
les modes non entiers, la désactivation, les variantes de transport, les
priorités, la portée et les combinaisons de sujets sont couvertes sans
dépendance au processeur historique.

Les services `SubmissionTimestampFactory` et
`SubmissionTimestampFormatter` disposent maintenant de tests directs sur le
format SQL, le nommage de fichier et les formats personnalisés. Leurs doubles
de date restent confinés aux tests purs, sans élargir le bootstrap Joomla.

Les résolveurs `FormPathResolver` et `FormDisplayContextResolver` sont
désormais caractérisés directement. Les tests verrouillent l'expansion
progressive des tokens de chemins, les variantes frontend/backend/preview,
l'identifiant de formulaire, la grille d'aperçu et la règle `canRun`.

Le `RequestMetadataResolver` est maintenant couvert avec un double Browser
local : adresse visible ou masquée, récupération des informations navigateur,
désactivation de la résolution fournisseur et repli pour une adresse invalide.
La résolution DNS n'est pas déclenchée par les tests, ce qui rend le filet
déterministe.

La frontière `RuntimeAssetLoader` est caractérisée avec les doubles Joomla
existants : normalisation d'un chemin sous-répertoire, enregistrement
idempotent des scripts, génération de noms dépendant des attributs et
enregistrement des feuilles de style hors du chemin racine.

Le `RuntimeContextInitializer` est maintenant couvert sur la normalisation
des URLs explicites, la reconstruction d'une URL HTTPS derrière proxy avec
port non standard, et la collecte sélective des paramètres de contenu. Le
double d'application partagé expose désormais uniquement les primitives
Input/Server nécessaires à ce harnais.

La même couverture verrouille désormais la branche `livesite = 0`, la racine
Joomla par défaut et le jeu de paramètres dédié à `com_contacts`.

Le jeu de paramètres `com_weblinks` est également verrouillé : seuls
`option`, `Itemid` et `catid` sont collectés pour ce contexte.

Les délégations modernisées de `CodeToolsRuntime` sont caractérisées
directement : nettoyage et détection de code, export JavaScript, résolution
des classes et formatage du mode de trace. Le parsing/eval historique reste
séparé, avec son propre besoin de caractérisation plus large.

Le harnais couvre maintenant aussi les branches de garde de
`prepareEvalCode`, l'application des tags de remplacement lorsque la trace
est désactivée, et les points d'instrumentation produits par
`patchCode` pour une fonction et son retour. Ces tests précèdent toute
extraction supplémentaire de ce code historique.

Les primitives lexicales `findToken` et `findRealToken` sont également
caractérisées sur l'avancement des offsets, les tokens de structure, les
commentaires de ligne et le suivi des numéros de ligne.

Les directives `+trace` de `prepareEvalCode` sont couvertes sur leurs
parcours normal et `disable`. Le test verrouille aussi le comportement
historique observé : sans `first`, le mot-clé `disable` de la directive
n'empêche pas l'instrumentation du code.

La façade `UploadRuntime` dispose maintenant d'une couverture directe de ses
délégations sans upload réel : parsing des tailles, détection d'une image
absente et recherche récursive d'un élément QuickMode. Le double `Input`
reste limité au test du constructeur.

La branche URL de `UploadStorage` est maintenant couverte en complément des
chemins serveur existants : elle vérifie la séparation entre URL publique et
chemin disque, avec un répertoire contrôlé et nettoyé par le test.

L'échec du déplacement dans `UploadStorage` est également caractérisé :
aucun fichier n'est laissé dans la destination et l'erreur
`MoveFailed` est retournée au runtime.

Le `UploadPathResolver` est caractérisé sur le remplacement de tokens de
destination et sur un masque de fichier alimenté par une valeur de formulaire,
avec nettoyage HTML et sécurisation du nom final. Les doubles filesystem et
Input restent confinés au test.

Le parcours complet de \`TokenizedDirectoryResolver\` est maintenant
caractérisé sur les tokens de champ, le nettoyage de valeur et la création du
répertoire calculé, avec nettoyage du dossier temporaire en fin de test.

Les trois corrections de fuseau horaire du runtime sont maintenant mutualisées
par SubmissionTimestampAdjuster, utilisé par SubmissionTimestampFormatter,
UploadPathResolver et TokenizedDirectoryResolver. Les constructeurs existants
conservent leur appel direct tout en acceptant explicitement cette stratégie,
et le comportement historique est validé par les tests concernés.

1. Test PHPUnit ciblé du service ou du renderer modifié.
2. `php -l` sur chaque fichier PHP modifié.
3. Suite PHPUnit complète.
4. PHPCS sur le périmètre configuré et sur tout nouveau service.
5. PHPStan ciblé sur le code de production modifié.
6. `git diff --check` avant commit.
7. Vérification des snapshots lorsque du HTML ou du JavaScript est déplacé.
8. Smoke test Joomla lorsque le routage, le conteneur de services, les assets
   ou le cycle complet de soumission sont modifiés.

## Définition de « terminé »

Un lot est terminé lorsque :

- sa responsabilité est nommée et isolée ;
- son comportement historique utile est couvert ;
- les sorties HTML et JavaScript restent stables, sauf changement demandé ;
- aucun nouveau fallback ou code de compatibilité n'est introduit ;
- PHPUnit, PHPCS et PHPStan passent sur le périmètre concerné ;
- la documentation et la baseline sont mises à jour si nécessaire ;
- le commit ne contient aucun changement sans rapport avec le lot.

## Hors périmètre immédiat

- Réécriture globale de `HTML_facileFormsProcessor`.
- Suppression des façades encore appelées par le PHP stocké en base.
- Changement volontaire du JavaScript historique sans caractérisation et
  validation fonctionnelle.
- Refonte visuelle des formulaires pendant la migration d'architecture.
- Compatibilité avec Joomla 5 ou antérieur et PHP 8.2 ou antérieur.
