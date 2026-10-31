# Migration com_breezingformsng → Joomla 6 pur

> Document de suivi destiné aux agents. Cocher chaque tâche à la complétion.  
> Le suivi détaillé de l’architecture est maintenu dans
> [`docs/maintenance/php-architecture-migration-plan.md`](docs/maintenance/php-architecture-migration-plan.md).

## Qualité et fiabilité des écrans — plan du 2026-10-31

Fait : `FormOptionsModel::saveForm()` préserve les colonnes absentes lors de la mise à jour
d'un formulaire existant. « Notification administrateur » et « Confirmation utilisateur »
exposent les mêmes réglages dans l'interface ; l'envoi réel des e-mails reste à tester.
Des parcours de bout en bout sur un Joomla installé sont configurés dans le workflow CI
(`scripts/joomla-e2e.sh`, `tests/E2E`) ; leur réussite en CI reste à constater.

Reste à faire, par priorité :

### 1. Fiabiliser les paiements — blocage avant usage en production

- Déterminer côté serveur un prix autorisé pour Stripe **et** PayPal, y compris les choix
  `bfPaymentSelect` et les montants issus d'un champ : contrôler le choix contre les
  tarifs configurés, sans faire confiance au montant, à la taxe ou à la devise envoyés
  par le navigateur.
- Conserver avec chaque enregistrement le montant attendu en unités minimales et la devise.
  Au retour Stripe et dans le webhook, comparer ces valeurs à la session ou au paiement
  confirmé avant d'enregistrer la transaction, d'envoyer des notifications ou d'ouvrir
  les téléchargements. Appliquer la même vérification au retour PayPal, notamment quand
  un élément de sélection désactive aujourd'hui le contrôle montant/devise.
- Garder les clés secrètes Stripe et les secrets de webhook côté serveur, hors du JSON
  transmis à l'éditeur et des champs HTML. Migrer les formulaires existants sans perdre
  leurs clés ; adapter sauvegarde et import/export des packages. Définir le retour arrière
  et vérifier installation neuve, mise à niveau et réexécution de la migration sur des
  copies jetables avant de retirer les anciennes valeurs.
- Valider des paiements sandbox Stripe et PayPal, y compris retour sur le site, webhook
  Stripe, paiement différé, échec, rejeu et montant/devise altérés.

### 2. Élargir les parcours de bout en bout

- Formulaire réellement ancien, importé d'une installation existante, en plus de l'élément
  dont les parcours retirent les clés ; vérifier la conservation des formulaires,
  enregistrements et réglages après mise à niveau et réinstallation.
- Scripts, pièces, packages, enregistrements, intégrateur et écran public en mode paiement.
- Exécution sur la dernière version de Joomla prise en charge, et avec TinyMCE et CodeMirror.
- Confirmer une exécution CI réussie et couvrir l'envoi réel des notifications administrateur
  et des confirmations utilisateur.

### 3. Rendre les pannes visibles

- `quickmode.doAjaxSave` répond en JSON ; le JS borne les tentatives et affiche un message
  exploitable si la sauvegarde échoue, y compris celle de l'onglet Options.
- Un test vérifie que chaque asset de `joomla.asset.json` pointe vers un fichier existant :
  sans `media/`, Joomla n'écrit aucune balise `<script>` et rien ne le signale.
- Les erreurs Stripe affichent leur cause aux administrateurs.
- Supprimer la source des avertissements PHP des anciens éléments dans `QuickmodeModel::save()`
  (`password`, `mailback`, `mailbackAsSender`, `logging`), aujourd'hui seulement journalisés.

### 4. Unifier les écrans

- Réduire `custom.css` et `admin.css`, sources de règles anciennes et de `!important`.
- Un seul gabarit de formulaire, sur le modèle des sections de l'élément Stripe.
- Liste de contrôle par écran : mode sombre, largeur 800 px, clavier, droits refusés, langues.
- Choisir l'aide des champs longs : l'attribut `title` natif ne s'ouvre ni au clavier ni sur
  écran tactile.
- Rétablir le marquage « Vu » à l'ouverture d'un enregistrement et un filtre Vu / Non vu.
- Corriger les libellés français des e-mails (« Soumis par », « Envoyer le nom d'utilisateur »)
  et le libellé « Journal seulement », qui envoie bien l'e-mail.
- Maquette de l'élément Stripe encore à faire : deux jeux de clés, « Tester la connexion »,
  moyens de paiement lus sur le compte.

### 5. Sécuriser le déploiement

- Installer le paquet sur les conteneurs de développement plutôt que copier des fichiers.
- Faire relire les langues autres que le français par des locuteurs natifs.
- Garder l'affichage des erreurs PHP actif en développement.

## État de la migration — 2026-10-15

La migration du code principal vers Joomla 6 et PHP 8.3+ est réalisée : les
écrans administrateur, le routeur, le rendu et le moteur de soumission utilisent
les services et API du composant modernisé. Les façades publiques encore
nécessaires au PHP stocké dans les formulaires restent conservées volontairement.

### Prérequis techniques de Joomla 6.x

| Logiciel | Recommandé | Pris en charge | Minimum |
| --- | --- | --- | --- |
| PHP | 8.4 | 8.3.0 | 8.3.0 |
| **Bases de données** | | | |
| MySQL | 8.4 | 8.0.13 | 8.0.13 |
| MariaDB | 12.0 | 10.6 | 10.4 |
| PostgreSQL | 17.6 | 14.0 | 12.0 |

Source : [exigences techniques officielles de Joomla 6.x](https://manual.joomla.org/docs/next/get-started/technical-requirements/).
Le composant conserve PHP 8.3+ comme minimum ; PHP 8.4 est recommandé par
Joomla, sans être obligatoire pour cette migration.

### Travaux restants

La clôture du chantier dépend des vérifications et corrections suivantes :

- [ ] Tester l’installation et les parcours principaux sur Joomla 6 avec le
  plugin **Joomla Backward Compatibility désactivé**. Le README ne revendique
  actuellement qu’un essai avec ce plugin activé ; le plugin `bfcompat` fourni
  par BreezingForms NG est distinct.
  Depuis le 2026-10-29, `scripts/joomla-install-smoke.sh` désactive
  `plg_behaviour_compat6` avant l’installation : installation, mise à jour,
  migration du schéma, maintenance de la base, plugin de contenu, modules et
  rendu du site passent sans lui. Restent les écrans d’administration.
- [ ] Tester une mise à niveau depuis une version antérieure avec des formulaires
  et enregistrements existants. `scripts/joomla-install-smoke.sh` réinstalle
  actuellement la même archive et vérifie la conservation d’un enregistrement.
- [ ] Exécuter la recette ContentBuilder complète : matrice ACL et export d’un
  enregistrement. Le parcours ContentBuilder du smoke test est optionnel et
  n’est pas activé par le workflow CI courant.
- [ ] Valider Stripe (carte, Klarna, iDEAL | Wero, webhook) et PayPal avec des
  comptes sandbox dédiés. Le webhook Stripe signé enregistre le paiement même si
  le client ne revient pas sur le site ; signature, événements ignorés et erreur
  d’API ont été vérifiés sur une installation de test, pas encore un vrai paiement.
- [x] Sofort : Klarna a arrêté Sofort comme moyen de paiement autonome en 2024.
  L’élément Stripe propose désormais l’option « Proposer aussi Klarna »
  (`payment_method_types` de la session Stripe Checkout).
- [x] L’élément `bfSofortueberweisung` est retiré : rendu, callback, modèles de
  téléchargement, propriétés QuickMode et chaînes de langue. Un élément encore
  enregistré dans un formulaire n’est plus rendu et ne déclenche plus de
  paiement ; QuickMode le retire de l’arbre à l’ouverture, avec un
  avertissement, et l’enregistrement du formulaire le supprime. Un formulaire
  dont Sofort était le seul paiement refuse les soumissions sur le site, avec un
  message et une entrée de journal, tant qu’un autre paiement n’est pas configuré.
- [ ] Migrer PayPal Payments Standard (`_xclick`, PDT, IPN), déprécié par
  PayPal, vers l’API Orders v2 et les webhooks.
- [x] Les appels administrateur à `getError()` et `setError()` dans
  `RecordModel`, `MenuModel` et `PackageItemModel` ont été retirés. Les
  contrôleurs de records gèrent désormais les exceptions afin de conserver les
  messages, redirections et annulations de transaction.
- [x] Les 219 créations de requêtes `$db->getQuery(true)` du code actif ont été
  remplacées par `$db->createQuery()`, méthode recommandée par
  [Joomla](https://manual.joomla.org/docs/5.4/general-concepts/database/insert-data/).
  Les exemples importables et le script de recette ont aussi été convertis.
  Les doubles de base de données et l’assertion dépendant de l’ancienne syntaxe
  ont été adaptés ; PHPUnit, PHPStan et PHPCS passent.
- [ ] Après la fusion de la [PR #101](https://github.com/vcmb-cyclo/com_breezingformsng/pull/101),
  vérifier en navigateur un véritable upload HTML5, les formulaires OnePage
  multipages et la purge des anciens fichiers lors d’une mise à niveau. Ses
  tests unitaires et navigateur passent en CI, mais ces parcours réels restent
  à valider.

Décision du 2026-10-15 : conserver PHP 8.3+ comme exigence minimale. Le
relèvement à PHP 8.4 initialement proposé dans la
[PR #102](https://github.com/vcmb-cyclo/com_breezingformsng/pull/102) a été
annulé ; son diff ne contient plus que les corrections d’en-têtes de copyright.

Le remplacement de la treeview QuickMode reste suivi dans
[`docs/maintenance/quickmode-treeview-migration-plan.md`](docs/maintenance/quickmode-treeview-migration-plan.md).
Il s’agit d’une modernisation de l’éditeur, distincte des validations Joomla 6
ci-dessus.

## Mise à jour du plan — 2026-09-01

Cette section résume l'état du chantier de modernisation. Les détails des lots
techniques sont conservés dans le plan d'architecture associé.

### État courant

- [x] Outillage PHPCS ajouté à Composer (`squizlabs/php_codesniffer`, scripts
  `lint:php` et `lint:php:fix`) avec un périmètre initial ciblé sur les services
  modernisés.
- [x] Tests renforcés pour callbacks, exports, uploads, permissions, signatures
  de scripts, parsing des tests de pièces et état PDF.
- [x] Validation Flash Upload extraite dans `FlashUploadSizeValidator`, injecté
  dans `FlashUploadCallback`.
- [x] Filets minimaux sur `bfTextfield` pour les trois renderers QuickMode :
  Classic, Bootstrap et OnePage.
- [x] `RenderingEngine::view()` couvert par les filets de caractérisation et les
  builders dédiés du plan d’architecture.
- [x] `ClassicRenderer` : les 21 types de champs couverts par filet de
  caractérisation et extraits de `process()` en méthodes privées dédiées
  (`renderTextfieldField`, `renderFileField`, etc.) ; `process()` a été réduit
  d'environ 1250 à 510 lignes.
- [x] Filets des trois renderers sur les familles de champs à risque
  (textarea, groupes, select, upload, CAPTCHA, calendrier et submit) couverts ;
  les différences propres à OnePage sont conservées et documentées dans les tests.
  Les types restants ont également été couverts sur Bootstrap et OnePage : chaque
  type de champ actif dispose désormais d’un test figeant sa sortie.
- [x] Mutualiser les stratégies par type de champ lorsque les sorties
  correspondantes sont couvertes par des tests. Le détail des stratégies
  conservées figure dans le plan d’architecture.
  `HiddenFieldTrait` est partagé par les 3 renderers (`bfHidden`).
  `BootstrapStyleFieldTrait` partagé par Bootstrap et OnePage seulement
  (même convention Bootstrap 5 via `$this->bsClass()`, absente de Classic) : `bfSummarize`, `bfCalendar`,
  `bfCalendarResponsive`, `bfCheckbox`, `bfSelect`, `bfSubmitButton`,
  `bfPayPal`, `bfSofortueberweisung`, `bfSignature`, `bfRadioGroup`,
  `bfCheckboxGroup`, `bfStripe`, `bfTextfield`, `bfNumberInput` mutualisés.
  Tous les types de champ des deux renderers ont été examinés ; les 4
  restants sont des exceptions documentées, pas des oublis :
  - `bfTextarea` : diffère d'un espace cosmétique entre Bootstrap et
    OnePage — sans impact, délibérément non mutualisé.
  - `bfFile` : différences réelles — chez OnePage la condition
    flashUploader/html5 est entièrement commentée en prod (branche morte),
    et le markup du bouton diffère (`<label><div class="btn">` chez
    Bootstrap vs `<span><button type="button">` chez OnePage).
  - `bfCaptcha` et `bfReCaptcha` : les deux renderers utilisent désormais un
    bouton CAPTCHA correctement fermé et les mêmes paramètres de construction
    du widget reCAPTCHA visible et invisible.

---

## État actuel

### ✅ Déjà migré
- [x] Manifest + namespace PSR-4 (`breezingformsng.xml`)
- [x] DI container / services (`services/provider.php`, `src/Extension/`)
- [x] Pipeline d'assets (`joomla.asset.json`)
- [x] Fichiers de langue structure Joomla 6 (`language/{lang}/`)
- [x] Admin — Pieces : Controller, Model, View, templates, help
- [x] Admin — Scripts : Controller, Model, View, templates, help
- [x] Admin — About : Controller, View, template, help
- [x] Admin — Help : View générique (sections par `?section=`)
- [x] Admin — Records : toolbar Joomla 6 native (dropdown Export, bouton Help)
- [x] `script.php` : migration liens menu `act=about` → `task=about.display&view=about`

### ✅ Migration réalisée
- [x] Routeur admin central (`admin.breezingforms.php` — supprimé Phase 6)
- [x] Gestion des enregistrements (`admin/recordmanagement.class.php` — migré Phase 1)
- [x] Configuration (`admin/config.class.php` + `config.html.php` — migré Phase 2 vers `com_config`)
- [x] Intégrateur (`admin/integrator.class.php` + `.html.php` — migré Phase 3)
- [x] Gestion des menus (`admin/menu.class.php` + `menu.html.php` — migré Phase 4)
- [x] Gestionnaire de formulaires (`admin/form.class.php` + `form.html.php` — migré Phase 5)
- [x] QuickMode (`admin/quickmode.class.php` + `.html.php` + `.js` — migré Phase 7)
- [x] Import paquets (`admin/import.class.php` → `src/Model/ImportModel.php`, SimpleXML + transactions ; bibliothèques scripts/pièces uniquement, les paquets contenant formulaires/menus sont refusés ; export de paquets abandonné avec l'UI legacy). *Mise à jour 2026-09-29 (commit `7f713b20`) : l'export/import de paquets est rétabli au format JSON (`breezingformsng-<paquet>.json`, formulaires + scripts + pièces + menus) via `PackagesController` et `PackageTransferModel`, depuis l'onglet « Import/export » des Options (permission `breezingformsng.configuration`). `ImportModel` reste réservé à l'import XML de `packages/stdlib.english.xml` par `script.php`.*
- [x] Frontend moteur formulaires — dispatcher, config, routeur SEF et `HTML_facileFormsProcessor` (8 907 lignes) décomposés en services/traits Joomla 6 (Phase 8)
- [x] `BFRequest` → `Input` Joomla natif (Phase 9a, 326 appels convertis) ; la façade
  de compatibilité reste disponible dans le plugin `bfcompat` pour le PHP stocké
- [x] Rendus `BFQuickMode*` — migration `BFRequest` (Phase 9c, 33 appels convertis ; la réécriture native complète du rendu reste un chantier séparé, hors périmètre `BFRequest`)
- [x] `BFIntegrate` → requêtes préparées (Phase 9b, 2026-07-12 — SQL par concaténation remplacé par `quoteName()`/`bind()`, vérifié en conditions réelles insert/update/repli)
- [x] SDK PHP externes historiques → services Joomla natifs typés (2026-07-12 — `RecaptchaVerifier`,
  `DropboxUploader`, `MailchimpClient` et `SalesforceClient`, chacun avec client HTTP injectable ; ancien
  `RemoteApiClient` conservé dans l'historique source et SDK embarqués supprimés)
- [x] Stripe PHP `17.6.0` → `20.3.1` (2026-07-12 — classes utilisées et package Joomla 6 vérifiés)
- [x] PDF : `tc-lib-pdf 8.x` utilisé directement via Composer ; PHP 8.3 minimum. Les polices personnalisées doivent être fournies en TTF : les anciennes définitions TCPDF en `.php` ne sont plus chargées. Le code PDF personnalisé qui appelle les anciennes méthodes TCPDF doit être adapté à l'API native, y compris le code enregistré qui utilise `BFPDF` au-delà de `AddPage()`, `SetFont()`, `writeHTML()`, `writeHTMLCell()`, `lastPage()` et `Output()`, que cette classe conserve, et les modèles d'en-tête ou de pied de page qui appellent `$this->SetY()` ou `getAliasNbPages()` (les numéros de page sont fournis dans `$pdfPageNumber` et `$pdfPageCount`). Les modèles génériques `export_{header,footer}_pdf.php` et `pdf_attachment_{header,footer}.php` de `media/breezingforms/pdftpl/` ne sont plus lus : la mise à jour supprime ceux qui n'ont pas été modifiés depuis leur installation et signale les autres, dont les personnalisations doivent passer dans un modèle propre au formulaire ou dans `export_custom_{header,footer}_pdf.php`. L'installation signale les modèles, polices et codes enregistrés concernés.
- [x] CAPTCHA : Securimage 4.0.2 géré par Composer (`bgli100/securimage`), chargement via `VendorHelper`
- [x] Audit About enrichi depuis les concepts CBNG (2026-07-12) : rapport persistant sur un affichage, inventaire des
  14 tables BFNG, volumes et tailles, tables manquantes, collations, index dupliqués et références orphelines ;
  aucune réparation destructive automatique.
- [x] API Joomla dépréciées (2026-07-12) : `Application::getCfg()` remplacé par `get()`, `Table::getInstance()`
  remplacé par la `MVCFactory` native de `com_content`, et `LegacyErrorHandlingTrait::getError()` remplacé par
  des exceptions ou des messages traduits. Les six classes Crosstec protégées restent inchangées.

---

## Règles pour les agents

- Joomla 6 uniquement. PHP 8.3+. Aucune compatibilité ascendante.
- Chaque écran migré = un triplet `Controller / Model / View` dans `src/`.
- Supprimer les fichiers `admin/` correspondants **après** que la route MVC fonctionne.
- Toute route legacy supprimée du bridge `DisplayController` doit avoir sa route MVC opérationnelle avant.
- Tester chaque phase manuellement avant de cocher.
- Mettre à jour ce fichier (cases à cocher) à chaque tâche complétée.

---

## Phase 1 — Enregistrements `act=managerecs` → `view=records`

**Priorité : haute. La toolbar est déjà Joomla 6 native.**

### Fichiers à créer
- [x] `src/Controller/RecordsController.php`  
  Tasks : `display`, `edit`, `save`, `delete`, `exportPdf`, `exportCsv`, `exportXml`, `csvImport`, `setFlag`, `setViewed`, `setExported`, `setArchived`
- [x] `src/Model/RecordModel.php` — CRUD enregistrement unique  
  Table : `#__facileforms_records` + `#__facileforms_subrecords`
- [x] `src/Model/RecordsModel.php` — liste filtrée, paginée  
  Filtres : formulaire, état (viewed/exported/archived), recherche texte
- [x] `src/View/Records/HtmlView.php`
- [x] `tmpl/records/default.php` — liste avec filtres, pagination, toggle flags inline
- [x] `tmpl/records/edit.php` — formulaire édition enregistrement
- [x] `tmpl/records/csvimport.php` — upload CSV

### Fichiers à modifier
- [x] `src/Controller/DisplayController.php` — routing `view=records` + intercept `act=managerecs/recordmanagement`

### Fichiers à supprimer (après validation)
- [x] `administrator/components/com_breezingformsng/admin/recordmanagement.class.php` *(git mv → RecordsController)*
- [x] `administrator/components/com_breezingformsng/admin/recordmanagement.php` *(git mv → RecordsModel)*

### Vérification
- [x] Liste des enregistrements s'affiche avec filtres et pagination *(vérifié HTTP le 2026-07-11)*
- [x] Export PDF, CSV, XML fonctionnels *(vérifié : PDF 45 Ko, CSV et XML corrects — le PDF nécessitait les correctifs `getSubrecords()` + repli template)*
- [x] Import CSV fonctionnel *(vérifié de bout en bout le 2026-07-11 : upload authentifié, création de l'enregistrement et de ses sous-enregistrements, puis nettoyage des données de test)*
- [x] Édition et sauvegarde d'un enregistrement *(vérifié HTTP et en base le 2026-07-11 : valeur modifiée puis restaurée ; requête sans jeton rejetée sans mutation)*
- [x] Flags (viewed / exported / archived) fonctionnels en unitaire *(setFlag vérifié ; bascule en masse à confirmer à la main)*
- [x] Bouton Help ouvre la modale *(vérifié dans Chrome le 2026-07-11 : popup Joomla iframe, URL d'aide Records correcte, aucune nouvelle fenêtre)*
- [x] Toolbar obtenue depuis le document Joomla 6 ; suppression du singleton déprécié `Toolbar::getInstance()` *(2026-07-20)*

> **Durcissement (2026-07-11)** : contrôle CSRF ajouté aux sauvegardes, suppressions, flags unitaires/en masse,
> imports CSV et exports (ces derniers marquent les enregistrements comme exportés). `RecordModel::saveRecord()` ne dépend
> plus des colonnes `modified*` ajoutées par ContentBuilderNG mais absentes du schéma BreezingFormsNG autonome.
> L'import CSV utilise désormais `fgetcsv()` avec le séparateur et le caractère de citation configurés, convertit l'encodage
> demandé, reconnaît le format produit par l'export BFNG et insère en transaction l'enregistrement et ses sous-enregistrements.

---

## Phase 2 — Configuration `act=configuration` → `com_config`

**Priorité : haute. Effort faible : `config.xml` existe déjà.**

### Fichiers à modifier
- [x] `config.xml` — fieldsets `general` (disable_ip, emailadr, uploads) + `csv` (csvdelimiter, csvquote, cellnewline) + `permissions`
- [x] `breezingformsng.xml` — entrée submenu Configuration → `com_config`
- [x] `src/View/BreezingformsNG/HtmlView.php` — lien sidebar Configuration → `com_config`
- [x] `src/Model/RecordModel::getExportConfig()` — lit depuis `ComponentHelper::getParams()` au lieu de `#__facileforms_config`

### Fichiers à supprimer (après validation)
- [x] `admin/config.class.php` *(git mv → src/Model/ConfigModel.php)*
- [x] `admin/config.html.php` *(git rm — remplacé par com_config)*
- [x] `admin/config.php` *(git rm — dispatcher inutile)*

> **Fait (2026-07-11)** : `facileFormsConf::load()` lit désormais `ComponentHelper::getParams()` (6 clés gérées par `config.xml`) ;
> `ConfigModel::migrateFromLegacy()` copie une seule fois les valeurs de `#__facileforms_config` vers les params
> (appelée par `script.php::migrateLegacyConfig()` en postflight, idempotente — vérifiée sur le conteneur joomla6).
> `store()` et `bindRequest()` (morts) supprimés. La table `#__facileforms_config` sera supprimée après une version de battement.

### Vérification
- [x] Composants → BreezingForms NG → Options ouvre l'écran natif Joomla *(vérifié)*
- [x] Sauvegarde des paramètres → persistance en base (`#__extensions` params) *(vérifié dans Chrome le 2026-07-11 : `disable_ip` modifié, relu après `component.apply`, puis restauré)*
- [x] Permissions ACL visibles et fonctionnelles.

---

## Phase 3 — Intégrateur `act=integrate`

**Priorité : moyenne.**

### Fichiers à créer
- [x] `src/Controller/IntegratorController.php`  
  Tasks : `display`, `edit`, `save`, `remove`, `publish`, `unpublish`, `addItem`, `removeItem`, `saveCode`, `saveFinalizeCode`, `addCriteria*`, `removeCriteria*`, `publishItem`, `unpublishItem`
- [x] `src/Model/IntegratorModel.php`
- [x] `src/View/Integrator/HtmlView.php`
- [x] `tmpl/integrator/default.php`
- [x] `tmpl/integrator/edit.php`

### Fichiers à modifier
- [x] `src/Controller/DisplayController.php` — ajouter `view=integrator` + intercept `act=integrate`
- [x] `src/View/BreezingformsNG/HtmlView.php` — sidebar Integrator → `view=integrator`

### Fichiers à supprimer
- [x] `admin/integrator.class.php` *(git mv → src/Model/IntegratorModel.php)*
- [x] `admin/integrator.html.php` *(git mv → src/View/Integrator/HtmlView.php)*
- [x] `admin/integrator.php` *(git rm — dispatcher)*

### Vérification
- [x] Liste des règles d'intégration s'affiche *(vérifié)*
- [x] Création d'une nouvelle règle (insert/update) *(deux types vérifiés en HTTP et en base le 2026-07-11, puis règles de test supprimées)*
- [x] Ajout/suppression d'items et critères *(item et critères formulaire/Joomla/valeur fixe vérifiés, puis supprimés)*
- [x] Éditeur de code (CodeMirror) fonctionnel *(assets présents, code d'item et code de finalisation sauvegardés et vérifiés en base)*
- [x] Publish/unpublish règle et item *(aller-retour 1 → 0 → 1 vérifié pour les deux)*

> **Durcissement (2026-07-11)** : toutes les suppressions d'items et de critères vérifient maintenant le jeton CSRF.
> Les actions supprimer et publier/dépublier utilisent des formulaires POST avec jeton au lieu de mutations par URL GET.

---

## Phase 4 — Gestion des menus `act=managemenus`

**Priorité : faible. Fonctionnalité réduite.**

### Fichiers à créer
- [x] `src/Controller/MenusController.php`
- [x] `src/Model/MenuModel.php`
- [x] `src/View/Menus/HtmlView.php`
- [x] `tmpl/menus/default.php`
- [x] `tmpl/menus/edit.php`

### Fichiers à modifier
- [x] `src/Controller/DisplayController.php` — intercept `act=managemenus` → `view=menus`
- [x] `src/View/BreezingformsNG/HtmlView.php` — sidebar Menus activé → `view=menus`

### Fichiers à supprimer
- [x] `administrator/components/com_breezingformsng/admin/menu.class.php` *(git mv → MenuModel)*
- [x] `administrator/components/com_breezingformsng/admin/menu.html.php` *(git mv → Menus/HtmlView)*
- [x] `administrator/components/com_breezingformsng/admin/menu.php` *(git rm)*

### Vérification
- [x] Liste des éléments de menu *(vérifiée après correctif `quoteName` dans `MenuModel::getItems()` ; création/suppression à confirmer à la main)*

---

## Phase 5 — Gestionnaire de formulaires `act=manageforms`

**Priorité : critique. Effort très élevé (cœur du produit).**

### Fichiers créés
- [x] `src/Controller/FormsController.php`  
  Tasks : `display`, `edit`, `save`, `cancel`, `copy`, `remove`, `publish`, `unpublish`, `orderup`, `orderdown`, `run`
- [x] `src/Model/FormModel.php` — CRUD formulaire unique + copy + publish + ordering
- [x] `src/Model/FormsModel.php` — liste filtrée + paginée (git mv depuis `form.class.php`)
- [x] `src/Model/ImportModel.php` — stub (git mv depuis `import.class.php`)
- [x] `src/View/Forms/HtmlView.php` — layouts `default` (liste) et `edit` (propriétés)
- [x] `tmpl/forms/default.php` — liste Bootstrap avec filtres, tri, pagination, publish toggle
- [x] `tmpl/forms/edit.php` — 3 onglets : Général, Email, Scripts & Pièces (CodeMirror)

### Fichiers modifiés
- [x] `src/Controller/DisplayController.php` — intercept `act=manageforms` → `view=forms`
- [x] `src/View/BreezingformsNG/HtmlView.php` — sidebar Forms → `view=forms`
### Fichiers supprimés
- [x] `administrator/components/com_breezingformsng/admin/form.php` (git rm)
- [x] `administrator/components/com_breezingformsng/admin/run.php` (git rm, remplacé par `forms.run`)
- [x] `administrator/components/com_breezingformsng/admin/form.class.php` (git mv → FormsModel)
- [x] `administrator/components/com_breezingformsng/admin/form.html.php` (git mv → Forms/HtmlView)
- [x] `administrator/components/com_breezingformsng/admin/import.class.php` (git mv → ImportModel)

### Périmètre différé
- [x] Import de paquets — `ImportModel` réécrit (SimpleXML, requêtes paramétrées, transaction) ; consommé par `script.php::importStandardLibrary()`. Limité aux bibliothèques de scripts/pièces (seul cas réel : `packages/stdlib.english.xml`) ; les paquets avec formulaires/menus sont refusés avec un message clair (le transfert complet de formulaires/menus passe désormais par `PackageTransferModel`, format JSON — voir la ligne « Import paquets » de la section « Migration réalisée »). Vérifié de bout en bout le 2026-07-10 : installation du paquet 6.1.0 dans le conteneur `joomla6-joomla-1`, import stdlib OK (71 scripts inchangés ignorés, 3 pièces mises à jour, métadonnées paquet actualisées).

### Migré depuis cette phase
- QuickMode — déplacé en Phase 7 vers `QuickmodeController`, `QuickmodeModel`, `QuickmodeHtml` et routes `task=quickmode.*`
- QuickMode JS AJAX endpoint — migré vers `task=quickmode.doAjaxSave`

### Vérification
- [x] Lister les formulaires (filtres package, état, recherche ; tri ; pagination) *(liste vérifiée HTTP)*
- [x] Créer / éditer / sauvegarder un formulaire (3 onglets de propriétés) *(vérifié HTTP et en base le 2026-07-12 : Général, Email et Scripts/Pièces, codes inline inclus)*
- [x] Publier / dépublier un formulaire *(aller-retour vérifié ; copie, changement d'ordre et suppression également validés puis nettoyés)*
- [x] Ouvrir QuickMode depuis la liste *(quickmode.display rendu complet, 1,6 Mo)*

> **Durcissement (2026-07-12)** : les actions rapides publier/dépublier et monter/descendre utilisent désormais le POST
> avec jeton du formulaire de liste. Les champs fantômes `mb_emailadr` et `script3code`, absents du schéma BFNG, ont été
> retirés du contrôleur, du modèle et du gabarit de création. Les références invalides à `LanguageText` ont été corrigées.

---

## Phase 6 — Nettoyage du bridge legacy (terminé)

**Phase partielle — le bridge admin principal est supprimé.**

### Effectué
- [x] `DisplayController` : les tâches `quickmode.display`/`quickmode.editor`/`quickmode.doAjaxSave` passent par le contrôleur MVC
- [x] `admin.breezingforms.php` : suppression des `case` morts (`installation`, `configuration`, `managemenus`, `integrate`, `recordmanagement`, `run`) — tous ces actes sont interceptés par MVC avant d'atteindre le bridge
- [x] `admin.breezingforms.php` : `default` ne tente plus d'inclure des fichiers supprimés
- [x] EasyMode et ClassicMode supprimés côté fichiers admin (par l'utilisateur)

### Complété
- [x] `PiecesController::bootstrapLegacyRuntime()` — `toolbar.facileforms.php` remplacé : require direct de `facileforms.class.php` + stdClass `$ff_config`
- [x] `ScriptsController::bootstrapLegacyRuntime()` — même correction
- [x] `DisplayController::display()` vidé de son `include admin.breezingforms.php`
- [x] Supprimé `administrator/components/com_breezingformsng/admin.breezingforms.php`
- [x] Supprimé `toolbar.facileforms.php` et `toolbar.facileforms.html.php`
- [x] Supprimé `admin/download.php`
- [x] Routes sortantes QuickMode migrées vers `task=quickmode.display` / `task=quickmode.editor`
- [x] Alias legacy QuickMode retirés de `DisplayController`

### Nettoyage final
- [x] Supprimer `src/Helper/LegacyClassLoader.php` *(les classes du moteur sont chargées explicitement par son bootstrap ; les derniers usages admin de `BFRequest` ont été migrés vers l'Input Joomla. Vérifié sans le fichier le 2026-07-12 : Scripts, Pièces, éditeur QuickMode, QuickMode et formulaire frontend répondent en HTTP 200)*
- [x] Retirer l'enregistrement de `LegacyClassLoader` dans `services/provider.php`
- [x] Supprimer le répertoire `admin/` — assets déplacés vers `media/com_breezingformsng/js/admin/` et `css/admin.css`, `joomla.asset.json` et manifeste mis à jour, `bluestork.fix.css` supprimé (obsolète)

### Vérification finale
- [x] Naviguer dans **tous** les écrans admin sans erreur *(balayage HTTP du 2026-07-11 : records, forms, integrator, menus, pieces, scripts, about, quickmode, éditeur inline — tous 200 sans erreur fatale)*
- [x] Aucun `include` ou `require` vers `admin/` ne subsiste dans le call stack (répertoire supprimé, grep sans résultat, paquet reconstruit et validé)
- [x] `php -l` sur tous les fichiers `src/` : aucune erreur de syntaxe

---

## Phase 7 — QuickMode `task=quickmode.display` / `task=quickmode.editor`

**Priorité : critique. QuickMode était cassé (toolbar.facileforms.php manquait config.class.php).**

### Effectué
- [x] `src/Model/QuickmodeModel.php` — migration complète de `QuickMode` : namespace, PHP 8.3, `json_encode/decode` et `base64_encode/decode` natifs (suppression dépendances `Zend_Json` et `bf_b64*`)
- [x] `src/Helper/QuickmodeHtml.php` — renderer migré sous namespace Joomla et retiré de `LegacyClassLoader`
- [x] `src/Controller/QuickmodeController.php` — tasks : `display`, `doAjaxSave`, `editor`
- [x] `src/View/Quickmode/HtmlView.php` — vue Joomla 6 native, configure toolbar
- [x] `tmpl/quickmode/default.php` — appelle le renderer namespacé `QuickmodeHtml::showApplication()`
- [x] `tmpl/quickmode/editor.php` — éditeur inline (git mv depuis `quickmode-editor.php`), task mise à jour vers `quickmode.editor`
- [x] `DisplayController` — alias legacy QuickMode (`act=quickmode*`, `act=manageforms&task=quickmode*`) retirés après migration des liens internes
- [x] `admin.breezingforms.php` — bridge entièrement vidé (switch ne contient plus que `default: break`)
- [x] `admin/quickmode.php`, `admin/quickmode.class.php`, `admin/quickmode.html.php`, `admin/quickmode-editor.php` — supprimés
- [x] Liens internes migrés vers routes MVC `task=quickmode.display` / `task=quickmode.editor`
- [x] Alias `act=quickmode*` supprimés de `DisplayController`
- [x] Save AJAX QuickMode : `quickmode-app.js` poste sur `task=quickmode.doAjaxSave`, puis redirige vers `quickmode.display`
- [x] `QuickmodeController::doAjaxSave()` crée `media/breezingforms/ajax_cache` si absent, valide les bornes de chunks et refuse les payloads JSON invalides
- [x] `QuickmodeController::doAjaxSave()` contrôle le jeton CSRF Joomla avant l'écriture du premier chunk

### Vérification
- [x] Ouvrir QuickMode depuis la liste des formulaires *(quickmode.display rendu complet, 1,6 Mo)*
- [x] Sauvegarder un formulaire (AJAX chunked save → `quickmode.doAjaxSave`) *(vérifié HTTP authentifié le 2026-07-11 : formulaire 8, réponse `8`, hash `template_code` inchangé)*
- [x] Ajouter / modifier / supprimer des éléments *(vérifié dans Chrome le 2026-07-12 sur le formulaire de test 28
  « Test eddy elements » : ajout et suppression dans l'arbre avant enregistrement ; puis cycle serveur validé via
  `quickmode.doAjaxSave` sur un formulaire temporaire : ajout en base, modification du titre, suppression avec zéro
  élément restant. Formulaire et éléments temporaires nettoyés)*
- [x] Éditeur inline (`task=quickmode.editor`, `tmpl=component`) *(vérifié après correctif du layout `editor_editor`)*
- [x] Prévisualisation frontend depuis QuickMode *(vérifié HTTP le 2026-07-11 : preview component + site, formulaire 8 rendu sans erreur fatale)*

---

## Phase 8a — SQL versionné (Lot B du plan — fait le 2026-07-11)

- [x] `sql/create_sql.php` (code mort — plus exécuté par personne) converti en `sql/install.mysql.utf8.sql`
  (14 tables, `CREATE TABLE IF NOT EXISTS` non destructif, index/PK/AUTO_INCREMENT fusionnés, `INSERT IGNORE` du paquet FF)
- [x] Manifeste : sections `<install><sql>` + `<update><schemas><schemapath>` ; baseline `sql/updates/mysql/6.1.0.sql`
- [x] Vérifié sur le conteneur joomla6 : exécution du SQL deux fois sans erreur (idempotent) sur préfixe de test,
  schéma identique à la base réelle (hors colonnes ajoutées par ContentBuilderNG), version `6.1.0` enregistrée dans `#__schemas`,
  données préservées après mise à jour (58 formulaires)
- [x] `validate-package.sh` mis à jour

> Toute évolution de schéma future = un fichier `sql/updates/mysql/<version>.sql`, plus d'ALTER dans `script.php::update()` pour les nouvelles versions.

---

## Phase 8 — Frontend

- [x] Décomposer le `facileforms.process.php` monolithique en services et traits fonctionnels :
  `FormRenderer`, `FormProcessor`, `SubmissionHandler`, intégrations (Stripe, Mailchimp, Dropbox…)
- [x] Squelette MVC site (2026-07-11) : `DisplayController` (vue par défaut `form`, **contrôle CSRF sur `ff_task=submit`**),
  `View/Form/HtmlView`, `tmpl/form/default.php` (délègue encore au moteur legacy), métadonnées de menu modernisées
  (`tmpl/form/default.xml`, blocs 1.5/1.6 supprimés), jeton `form.token` injecté dans les 3 branches d'émission du moteur.
  Vérifié sur le conteneur : rendu 200, soumission sans jeton → 403 (index.php et URL SEF), soumission avec jeton → enregistrement créé puis nettoyé.
- [x] Décomposer `breezingformsng.php` en services fonctionnels *(fait le 2026-07-12 : dispatcher de 157 lignes ;
  rendu/soumission dans `Site\Service\FormRenderer` ; rappels par famille dans `Site\Service\Callback\{PayPal,Stripe,Sofort,Captcha,FlashUpload,Opt}Callback` ;
  streaming de téléchargement dédupliqué dans `Support\DownloadHelper`. Vérifié sur le conteneur : rendu formulaire 8 + rappel opt-out sans erreur)*
- [x] Découper `facileforms.class.php` par domaine *(fait le 2026-07-12 : chargeur de 43 lignes → `legacy/functions.php` (helpers runtime),
  `legacy/Conf.php` (config adossée aux params), `legacy/tables.php` (classes Table globales) ; `facileforms.xml.php` orphelin supprimé
  du paquet et purgé des sites par `removeObsoleteComponentFiles()`)*
- [x] Découper `HTML_facileFormsProcessor` (8 907 lignes) en traits fonctionnels *(fait le 2026-07-12 :
  `facileforms.process.php` ramené à 706 lignes (bootstrap, traces, constructeur) ; méthodes réparties dans
  `legacy/processor/bfProcessor{CodeTools,Scripting,Rendering,Exports,Notifications,Uploads,Submission}.php`.
  Vérifié sur le conteneur : rendu normal + `tmpl=component`, soumission SEF du formulaire 4 → enregistrement créé puis nettoyé)*
- [x] Éliminer les wrappers crosstec triviaux *(fait le 2026-07-12 : `BFText` → `Text` natif (68 fichiers, langue chargée
  au bootstrap du moteur) ; `BFFile::read` → `file_get_contents` et suppression de `BFFactory`/`BFDbo` (sans appelant — **erroné, `BFFactory` rétabli, voir l'encadré ci-dessous**) ;
  `BFRedirect()` → `Site\Service\Support\RedirectHelper`. Fichiers purgés des sites installés via `removeObsoleteComponentFiles()`)*

  > ⚠️ **Régression découverte et corrigée le 2026-07-12** (en creusant un incident similaire sur `BFRequest`,
  > cf. Phase 9a) : `BFFactory` était en réalité utilisé par du **code PHP personnalisé stocké en base**, en
  > **production** (confirmé par l'utilisateur) — 4 Pièces admin (`ff_databaseToSelect` id 3, `ff_query` id 18,
  > `ff_select` id 25, `ff_selectValue` id 26) et la colonne `piece1code` du formulaire `hash_password` (id 11)
  > appellent toutes `BFFactory::getDbo()`/`getDBO()`. « Sans appelant » n'était vrai qu'au sens grep-sur-le-code-
  > source — ces pièces généraient `Class "BFFactory" not found` à chaque exécution (visible dans
  > `administrator/logs/everything.php` en DEBUG depuis le début de cette série de sessions, à tort classé comme
  > « bruit préexistant sans rapport »).
  >
  > **Corrigé** : le fichier original a été restauré tel quel depuis l'historique git (commit `4d3c0813^`) plutôt
  > qu'une réimplémentation « minimale » réécrite — le code des pièces a été écrit contre le comportement précis
  > de `BFDbo` (avale silencieusement les exceptions de `setQuery()`/`execute()` et renvoie `false`/tableau vide
  > au lieu de les relancer), reproduire exactement ce comportement est plus sûr que deviner ce qui est
  > « suffisamment minimal ». Un commentaire d'avertissement a été ajouté en tête du fichier pour qu'un futur
  > agent ne le supprime plus jamais sur la seule foi d'un grep. `require_once` restauré dans `breezingformsng.php`
  > (même emplacement qu'avant suppression) ; `BFFactory.php` retiré de la liste `removeObsoleteComponentFiles()`
  > de `script.php` (sinon la prochaine mise à jour du composant l'aurait supprimé à nouveau).
  >
  > Vérifié : `php -l` propre sur les 3 fichiers touchés ; les 4 formulaires de test rechargés après déploiement
  > — l'entrée `DEBUG` « data1 of Record_ID[1] : Class BFFactory not found » n'apparaît plus (confirmé par
  > l'horodatage du journal : aucune nouvelle entrée après le correctif malgré les rechargements, alors qu'elle
  > apparaissait à chaque chargement avant).
- [x] Remplacer les accesseurs `Factory` dépréciés *(fait le 2026-07-12 : derniers `Factory::getUser()` du moteur,
  des notifications, des exports, des uploads et de l'intégrateur remplacés par `Factory::getApplication()->getIdentity()` ;
  `Factory::getMailer()` remplacé par `MailerFactoryInterface`, `Factory::getCache()` par
  `CacheControllerFactoryInterface`, `Factory::getConfig()` par la configuration de l'application et les accès directs
  `$app->input` par `getInput()`. Aucun accesseur Factory déprécié restant dans le composant. Rendu, callback opt-out et
  soumission invitée vérifiés ; données de test nettoyées)*
- [x] Remplacer les 41 `Factory::getDate()` par `Joomla\CMS\Date\Date` *(l'implémentation Joomla 6.0.4 de l'ancien
  accesseur appelle encore `Factory::getLanguage()` déprécié ; 15 fichiers lintés, puis About, Records, QuickMode et
  formulaire frontend vérifiés en HTTP 200)*
- [x] Empêcher l'accès déprécié à la base pendant la construction de `PackageModel` *(injection explicite après
  construction conservée, fallback `Factory::getDbo()` neutralisé avec `dbo => null` ; listes Scripts et Pièces en HTTP 200)*
- [x] Retirer le helper mort `bf_ToolTip()` fondé sur l'ancien service `HTMLHelper::_('tooltip')` et supprimer
  l'initialisation Bootstrap répétée dans les 293 appels à `bf_tooltipText()` *(tooltip initialisé une fois par QuickMode)*
- [x] Réécriture native du moteur (remplacer les traits legacy par de vrais services typés) — terminée le 2026-07-19.
  Le stockage physique, le traitement d'images, la résolution des chemins/masques et la recherche d'éléments QuickMode du trait
  `bfProcessorUploads` sont extraits dans les services typés `Site\Service\Upload\ImageResizer`,
  `UploadPathResolver`, `UploadStorage` (résultat et erreurs typés) et `Site\Service\QuickMode\ElementFinder`
  (2026-07-13) ; les méthodes publiques historiques délèguent aux services pour préserver les appels issus de code
  personnalisé. La sérialisation des valeurs JavaScript et la résolution des classes CSS de `bfProcessorCodeTools`
  sont également extraites dans `Site\Service\Rendering\JavascriptValueExporter` et `ClassNameResolver`, avec une
  sortie vérifiée octet pour octet contre l'algorithme historique. Les opérations de chaînes et le formatage des
  modes de trace passent par `Site\Service\Runtime\CodeStringTools` et `TraceModeFormatter` (parité vérifiée sur les
  4 096 combinaisons de bits). La construction des répertoires avec jetons (`cbCreatePathByTokens`) est déplacée de
  `bfProcessorRendering` vers `Site\Service\Upload\TokenizedDirectoryResolver`, avec création récursive via l'API
  Joomla `Folder`. Le bloc JavaScript d'état `ff_processor` est rendu par
  `Site\Service\Rendering\ProcessorHeaderRenderer`, avec le compresseur historique injecté uniquement lorsqu'il est
  activé. La collecte des métadonnées de requête (IP, agent, plateforme et fournisseur) quitte également le
  constructeur monolithique pour `Site\Service\Runtime\RequestMetadataResolver` ; le chargement conditionnel de la
  classe Joomla historique a été supprimé puisque `Browser` est nativement autoloadée par Joomla 6. L'horodatage
  des soumissions et la résolution des variables de chemins `{ff_*}`/`{cbsite}` sont maintenant assurés par
  `SubmissionTimestampFactory` et `FormPathResolver`. Le calcul du contexte frontend/backend/prévisualisation
  (URL d'action, identifiant HTML, template, grille et autorisation d'exécution) est isolé dans
  `FormDisplayContextResolver`. La recherche récursive des traductions de titres et champs QuickMode quitte le
  trait Notifications pour `Site\Service\QuickMode\TranslationResolver`, les méthodes historiques restant des
  façades publiques. L'envoi des notifications quitte le helper global `bf_createMail()` pour
  `Site\Service\Notification\MailSender`, fondé sur `MailerFactoryInterface` Joomla 6 ; `sendMail()` reste la
  façade publique du processeur. La conversion d'horodatage dupliquée dans les exports PDF/CSV/XML est centralisée
  dans `Site\Service\Runtime\SubmissionTimestampFormatter` sans modifier les formats ni le double passage du masque
  PDF. Les six conversions identiques des notifications administrateur et mailback utilisent le même service.
  Le traitement des uploads Flash réutilise également ce formateur pour les masques `Y_m_d_H_i_s` et `Y_m_d`,
  supprimant deux blocs de conversion supplémentaires. Le nettoyage HTML du trait Submission est déplacé dans
  `Site\Service\Security\HtmlSanitizer` ; la façade publique historique est conservée et la suppression répétée de
  balises interdites ne dépend plus d'une `DOMNodeList` modifiée pendant une boucle indexée. Restent l'orchestration
  de `facileforms.process.php`. La compression JavaScript quitte le trait Scripting pour
  `Site\Service\Rendering\JavascriptCompressor` ; la longueur de coupure et la fin de ligne sont désormais des
  dépendances explicites, tandis que `compressJavascript()` reste la façade publique. Les lectures des pièces et
  scripts publiés passent par `Site\Service\Scripting\Repository`, avec Query Builder Joomla, paramètres liés et
  résultats `StoredCode` typés ; les méthodes historiques restent des façades. **Premier trait supprimé le
  2026-07-19** : `bfProcessorUploads` est remplacé par
  `Site\Service\Upload\UploadRuntime`, qui compose `UploadPathResolver`, `UploadStorage`,
  `ImageResizer` et `QuickMode\ElementFinder`. Les méthodes publiques historiques restent sur
  `HTML_facileFormsProcessor` comme façades pour le PHP personnalisé stocké en base. Son `require_once` et
  son `use` sont retirés, le fichier supprimé du paquet et ajouté au nettoyage des mises à jour.
  **Deuxième trait supprimé le 2026-07-19** : `bfProcessorCodeTools` devient
  `Site\Service\Runtime\CodeToolsRuntime`. Le service compose les quatre helpers typés déjà extraits
  (`ClassNameResolver`, `JavascriptValueExporter`, `CodeStringTools`, `TraceModeFormatter`) et reçoit
  explicitement le processeur dont il fait évoluer l'état de trace. Toutes les signatures historiques, y
  compris les paramètres passés par référence, restent des façades publiques sur
  `HTML_facileFormsProcessor`. L'évaluation demeure dans le trait Scripting : le `$this` visible par le PHP
  personnalisé reste donc le processeur, jamais le nouveau runtime. Restent cinq traits `legacy/processor` :
  `Scripting`, `Rendering`, `Exports`, `Notifications` et `Submission`. Les six classes Crosstec protégées
  **Troisième trait supprimé le 2026-07-19** : `bfProcessorScripting` devient
  `Site\Service\Scripting\ScriptingEngine`, appuyé sur `ScriptingRuntime` pour le dépôt et la compression.
  Les trois chemins d'évaluation PHP sont centralisés dans `StoredPhpExecutor` ; chaque closure est liée
  explicitement à `HTML_facileFormsProcessor` avec `Closure::call()`, de sorte que le `$this` et les
  variables locales historiques restent disponibles au code Super User stocké en base. Toutes les méthodes
  historiques restent des façades publiques sur le processeur, y compris leurs paramètres par référence.
  L'ancien trait est retiré du bootstrap, supprimé du paquet et purgé lors des mises à jour. Restent quatre
  traits : `Rendering`, `Exports`, `Notifications` et `Submission`.
  **Quatrième trait supprimé le 2026-07-19** : `bfProcessorExports` devient
  `Site\Service\Export\ExportEngine`. La journalisation des enregistrements, les primitives de mail et les
  exports PDF/CSV/XML reçoivent explicitement `HTML_facileFormsProcessor` comme contexte ; `MailSender` et
  `SubmissionTimestampFormatter` restent des dépendances internes typées. Les sept méthodes publiques
  historiques sont conservées comme façades. L'ancien fichier est retiré du bootstrap et ajouté au nettoyage
  de mise à jour. Restent trois traits : `Rendering`, `Notifications` et `Submission`.
  **Cinquième trait supprimé le 2026-07-19** : `bfProcessorNotifications` devient
  `Site\Service\Notification\NotificationEngine`. Les notifications administrateur/mailback,
  Salesforce et Mailchimp ainsi que la résolution des traductions reçoivent explicitement le processeur comme
  contexte. `TranslationResolver` et `SubmissionTimestampFormatter` restent des dépendances privées typées ;
  les six méthodes historiques restent des façades publiques. L'ancien fichier est retiré du bootstrap et
  purgé lors des mises à jour. Restent deux traits : `Rendering` et `Submission`.
  **Sixième trait supprimé le 2026-07-19** : `bfProcessorSubmission` devient
  `Site\Service\Submission\SubmissionEngine`. Le pipeline de collecte,
  validation, stockage, notifications, paiements, Dropbox et nettoyage HTML reçoit explicitement le processeur
  comme contexte ; `HtmlSanitizer` et `SubmissionTimestampFormatter` restent des dépendances privées typées.
  Les façades `collectSubmitdata()`, `submit()` et `removeDangerousHtml()` préservent l'API publique.
  Les deux anciens fichiers sont retirés du bootstrap et purgés lors des mises à jour. Reste un seul trait :
  `Rendering`.
  **Septième et dernier trait supprimé le 2026-07-19** : `bfProcessorRendering` devient
  `Site\Service\Rendering\RenderingEngine`. Le rendu de l'en-tête, les chemins ContentBuilder, les contrôles
  d'autorisation et la vue QuickMode reçoivent explicitement le processeur comme contexte. Les services
  `TokenizedDirectoryResolver` et `ProcessorHeaderRenderer` restent des dépendances privées typées ; les cinq
  méthodes historiques restent des façades publiques. Les instances `BFQuickMode*` reçoivent toujours le
  processeur public afin de préserver leur contrat. L'ancien fichier est retiré du bootstrap et purgé lors des
  mises à jour. Aucun trait `legacy/processor/bfProcessor*` ne reste désormais chargé par le moteur.
  **Dispatcher natif extrait le 2026-07-19** : la sélection procédurale entre rendu, soumission et callbacks
  quitte `breezingformsng.php` pour `Site\Service\EngineDispatcher`. Le service reçoit explicitement l'objet
  `Joomla\Input\Input`, centralise la détection des callbacks et conserve exactement leurs gardes historiques.
  Le contrôleur frontal ne garde plus que l'initialisation du contexte d'exécution, nécessaire aux formulaires
  et scripts stockés, puis délègue le traitement au service.
  `FormRenderer` reçoit maintenant explicitement `CMSApplication` et `DatabaseInterface` depuis ce dispatcher :
  ses 60 résolutions statiques de l'application et son accès à la base globale sont supprimés. La classe est
  finale, en typage strict, et ne conserve en globals que l'état d'exécution public partagé avec les scripts de
  formulaires stockés.
  La façade `HTML_facileFormsProcessor` reçoit à son tour ces deux dépendances dans son constructeur au lieu de
  les retrouver via `Factory`/le conteneur. Le chargement initial des éléments utilise désormais le query builder
  Joomla avec paramètre entier lié, sans concaténation SQL. Ses 56 propriétés déclarées avec le mot-clé PHP 4
  `var` sont converties en propriétés `public` explicites, sans changer le contrat exposé aux scripts stockés.
  Les six services de callback (`Captcha`, `FlashUpload`, `Opt`, `PayPal`, `Sofort`, `Stripe`) reçoivent ensuite
  `CMSApplication` et `DatabaseInterface` depuis `EngineDispatcher`. Leurs 36 appels statiques à
  `Factory::getApplication()` et leurs 13 lectures du global `$database` sont supprimés ; les garde-fous et
  protocoles externes restent inchangés. `RedirectHelper` devient lui aussi un service injecté sans appel
  statique à `Factory`, et la fabrique de mail Joomla utilisée par Sofort est fournie par le dispatcher.
  Les moteurs `Export`, `Notification`, `Rendering` et `Submission` cessent ensuite de résoudre l'application
  179 fois via `Factory` : ils utilisent l'application déjà portée par le processeur. Leurs cinq résolutions de
  la base passent également par `DatabaseInterface` injecté. Les fabriques Joomla de cache et de mail sont
  propagées explicitement du bootstrap jusqu'aux moteurs qui les consomment ; ces quatre moteurs n'appellent
  plus ni `Factory::getApplication()` ni `Factory::getContainer()`.
  Les quatre renderers QuickMode suivent enfin la même règle : leurs 215 résolutions statiques de l'application
  sont remplacées par l'application du processeur déjà injecté, et Mobile utilise son `DatabaseInterface` pour
  la date nulle. `ClassicRenderer`, `BootstrapRenderer`, `MobileRenderer` et `OnePageRenderer` ne dépendent plus
  du tout de `Factory`, tandis que les façades globales `BFQuickMode*` restent intactes pour les scripts stockés.
  Les deux dernières requêtes SQL concaténées du gestionnaire de traces (`pieces` et `scripts`) utilisent aussi
  le query builder Joomla et un identifiant entier lié.
  **Tables du moteur modernisées** : les sept classes de `legacy/tables.php` utilisent désormais exclusivement
  le `DatabaseInterface` reçu par leur constructeur. Leurs résolutions du conteneur, écritures dans le global
  `$database` et sept implémentations manuelles de `load()` sont supprimées au profit de
  `Joomla\CMS\Table\Table::load()`.
  **Configuration native** : `legacy/Conf.php` devient la classe finale et stricte
  `Site\Configuration\FormConfiguration`, autoloadée par Joomla. Son faux accès à la base et son chargement
  manuel disparaissent ; l'ancien fichier est purgé lors des mises à jour.
  **Initialisation runtime extraite** : `RuntimeContextInitializer` construit les URL du moteur et collecte les
  paramètres Joomla à préserver à partir de `CMSApplication`; `RequestParameterParser` alimente explicitement
  le tableau de requête du `FormRenderer`. Les fonctions globales `initFacileForms()`, `saveOtherParam()` et
  `addRequestParams()` sont supprimées de `legacy/functions.php`, qui ne résout plus l'application statiquement.
  Le helper de route des intégrations de tags ne charge plus `BFRequest.php`, inutilisé depuis la conversion
  complète des lectures de requête vers Joomla Input.
  **Tables sorties de `legacy/`** : les huit classes globales sont renommées `MenuTable`, `FormTable`,
  `ElementTable`, `ScriptTable`, `PieceTable`, `RecordTable`, `SubrecordTable` et `QueryColumn`, toutes finales
  et sous le namespace du site. Chaque classe possède désormais son propre fichier PSR-4 ; le bootstrap manuel
  de `RuntimeTables.php` a disparu. Les consommateurs frontend et administrateur utilisent leurs noms qualifiés ;
  l'ancien fichier est purgé lors des mises à jour.
  Le dernier fichier du dossier `legacy/`, désormais limité aux helpers globaux publics requis par les scripts
  stockés, est déplacé vers `src/Support/runtime_functions.php`. Le dossier `legacy/` disparaît du paquet ; les
  noms des fonctions restent inchangés afin de préserver le contrat d'exécution des formulaires.
  L'entrée `<folder>legacy</folder>` est simultanément retirée du manifeste afin que Joomla n'attende pas un
  dossier désormais absent du paquet.
  Les six callbacks ne déclarent plus les 72 globals hérités qu'ils n'utilisaient jamais. Le callback captcha
  perd aussi sa dépendance à la base, et le dernier alias `$mainframe` de l'écran PayPal est remplacé par
  l'application injectée.
  Le bootstrap global `facileforms.class.php` est déplacé vers `src/Support/runtime_bootstrap.php`. Les constantes
  de runmode et symboles publics restent inchangés pour les scripts stockés, mais le fichier historique quitte la
  racine, disparaît du manifeste et est purgé lors des mises à jour.
  La façade globale `HTML_facileFormsProcessor` et ses hooks de trace quittent ensuite
  `facileforms.process.php` pour `src/Support/processor_facade.php`. Le contrat PHP public reste chargé à la
  demande par `FormRenderer`, tandis que l'ancien fichier racine est retiré du manifeste et du site installé.
  (`BFRequest`, `BFIntegrate` et les trois rendus `BFQuickMode*`) restent volontairement disponibles comme API externe.
  `BFJoomlaConfig` a été remplacé par `Factory::getConfig()` ; `BFPDF` a été migré vers le service namespacé
  `Administrator\Service\PdfDocument`. Les deux classes globales ont été supprimées du composant ; elles restent fournies comme façades de compatibilité par le plugin système `bfcompat` (`plugins/bfcompat/src/Compat/BFJoomlaConfig.php`, `BFPDF.php`) pour le code stocké en base. Export administrateur vérifié
  dans Joomla 6 le 2026-07-12 : PDF 1.7 valide généré avec les en-têtes de téléchargement attendus.*
- [x] Migrer `router.php` vers `RouterInterface` Joomla 6 *(fait le 2026-07-11 : `Site\Service\Router extends RouterBase`, `RouterFactory` au provider, `RouterServiceInterface` sur l'extension ; `router.php` supprimé du paquet et nettoyé des sites installés par `script.php::removeObsoleteComponentFiles()` ; pages de formulaires SEF vérifiées en front)*

---

## Phase 9 — Réécriture native du moteur de soumission (terminée)

La réécriture du moteur de soumission est terminée. Les appels internes à
`BFRequest` ont été remplacés par l’API `Input` de Joomla, `BFIntegrate` utilise
des requêtes préparées et les trois renderers QuickMode actifs ont été migrés
vers des services namespacés. Les façades publiques encore nécessaires aux
scripts PHP stockés sont conservées par le plugin `bfcompat` ou dans le dossier
`libraries/crosstec/classes/`.

Les détails des extractions, des tests de caractérisation et de la suppression
du renderer mobile historique sont maintenus dans le plan d’architecture.

## Validations et points externes

- [x] Les permissions ACL ont été vérifiées en conditions réelles.
- [ ] Les paiements Stripe (carte et Klarna) et PayPal restent à valider avec des comptes sandbox dédiés. Cette recette externe ne bloque pas la migration du code.

### Rappels permanents

- **Façade gelée** : ne pas toucher à la façade publique ni à son contrat (HTML_facileFormsProcessor et façades historiques exposées). Du code PHP est stocké en base dans les formulaires, pièces, scripts et intégrations, et des appelants peuvent se trouver à l’extérieur du dépôt ; un grep du code source ne permet donc pas d’en dresser l’inventaire complet. Toute nouvelle évolution doit rester derrière la façade, dans les services délégués.

- Ne pas supprimer les façades encore appelées par du PHP stocké en base. Les classes de compatibilité vivent dans `administrator/components/com_breezingformsng/plugins/bfcompat/src/Compat/`; les façades Classic, Bootstrap et OnePage restent dans `libraries/crosstec/classes/`.
- Avant de retirer une classe, vérifier ses chargements explicites et son éventuelle présence dans `script.php::removeObsoleteComponentFiles()`.
- Déployer les fichiers de langue aux **deux** emplacements (`administrator/components/…/language/` et `administrator/language/`) puis vider `administrator/cache/language/`.
- Toute chaîne utilisateur doit être mise à jour simultanément dans les huit langues : en-GB, fr-FR, de-DE, it-IT, es-ES, hu-HU, nl-NL et tr-TR.
