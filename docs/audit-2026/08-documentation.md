# Audit de la documentation anglaise — BreezingForms NG (BFNG)

Date : 2026-11-19. Version : 6.1.0-RC23 dans le manifeste (RC23 en cours, branche `audit-2026-rc23`). Audit en lecture seule ; seul ce fichier a été créé.

Légende des preuves : **[O]** observé dans le code (chemin), **[D]** lu dans un document. Les points que je n'ai pas pu vérifier sont marqués **[À VÉRIFIER]**. Aucune fonctionnalité n'est décrite si elle n'a pas été trouvée dans le code. Les chemins relatifs sont à la racine du dépôt ; `ADM` = `administrator/components/com_breezingformsng`.

Limites : lecture ciblée (listings, titres, extraits, fichiers de langue en-GB), aucune exécution, aucun écran ouvert. Les libellés cités viennent de `ADM/language/en-GB/*.ini`.

## 1. Cinq constats principaux

1. **Il n'existe aucune documentation utilisateur en anglais** hors README (150 lignes) : pas de guide de premier formulaire, d'éditeur QuickMode, de notifications, d'exports, de paiements ni d'intégrations. Le seul contenu pédagogique est le skill agent (`skills/breezingformsng/`, 4,6 Ko + 3 références), écrit pour des IA. Les guides `docs/guide/01..04` comblent le P1 ; tout le P2 reste à écrire.
2. **Le README gonfle le nombre de types de champs** : « 20 element types », en comptant Klarna et Wero, qui sont des options de Stripe et non des éléments. Le code (`media/com_breezingformsng/js/admin/quickmode-elements.js`) définit 18 `bfType` (+ la variante curseur de `bfNumberInput`) ; `skills/.../elements.md` en liste 19 lignes. Les **widgets** (téléphone international, date/heure, liste de pays, recherche d'adresse, zone de dépôt de fichiers) [O `components/.../Service/Widget/WidgetOptions.php:24`, `WIDGETS = ['phone','datetime','country','address']`] ne figurent ni dans le README, ni dans `elements.md`, ni dans `llms.txt` (seul le CHANGELOG les cite) : lacune pour `04-field-types.md`.
3. **Incohérence sur les versions minimales** : le README, les pages du site et le JSON-LD annoncent « PHP 8.3+ / Joomla 6.x » ; l'installateur impose PHP 8.3 et Joomla 6.0 [O `script.php:51-52`] ; `AGENTS.md` cible Joomla 6.2 / PHP 8.4 (PHP 8.5 compatible). Les guides doivent dire « minimum imposé : 6.0 / 8.3 », et ne parler de 6.2/8.4 que comme cible testée, après décision du propriétaire **[À VÉRIFIER]**.
4. **Des documents publics ou semi-publics sont en français ou obsolètes** : `docs/maintenance/mcp-read-only.md` est en français et son nom est trompeur (il décrit aujourd'hui les 9 outils, dont 5 d'écriture : `bfng_create_form`, `bfng_update_form`, `bfng_manage_form_fields`, `bfng_trash_form`, `bfng_restore_form` [O `ADM/src/Mcp/Tool/`]) ; `MIGRATION.md` (736 lignes, public à la racine) est un journal de suivi pour agents, en français, qui n'est pas un guide de migration utilisateur (le vrai guide est la section README « Upgrading » et les pages `docs/website/`). `docs/charte-graphique-breezingformsng.md` est en français. Aucun de ces fichiers n'est lié depuis un index.
5. **Fonctions réelles non documentées** : API d'audit REST `v1/breezingformsng/audit` (GET + 3 DELETE de réparation) [O `ADM/plugins/webservices/src/Extension/Breezingformsng.php:35`] — absente de tout document sauf le CHANGELOG ; écran Tools (Packages, Sample data, Maintenance : audit, statistiques, corbeille, inventaire des enregistrements) ; transfert d'enregistrements entre formulaires (`Recordstransfer`) et import CSV (`Recordsimport`) ; double opt-in ; versionnement des formulaires (option `save_history`/`history_limit`) ; gabarits e-mail et PDF personnalisables (`ADM/mailtpl`, `ADM/pdftpl`, surcharge `media/breezingforms/pdftpl/<form>_export_pdf.php`) ; les 5 formulaires d'exemple (`ADM/packages/samples/bfng-sample-{contact,event,quote,survey,widgets}.json`). Le README n'a pas non plus de section sur la configuration (onglets General, e-mail, uploads, privacy, CSV, MCP).

## 2. Inventaire de la documentation existante

Audience : EU = utilisateur final ; ADMIN = administrateur du site ; FB = concepteur de formulaires ; DEV = développeur ; MAINT = mainteneur ; IA = agent IA.

| Document | Langue | Audience | Objet | Statut | Preuve / remarque |
|---|---|---|---|---|---|
| `README.md` | EN | ADMIN, FB | Vitrine, fonctionnalités, prérequis, installation, webhook Stripe, migration depuis Crosstec | **Courant, à corriger** | « 20 element types » inexact ; ne mentionne ni widgets, ni transfert d'enregistrements, ni samples, ni MCP, ni API ; webhook Stripe détaillé (bon) ; lien CONTRIBUTING OK |
| `docs/guide/01-installation.md` | EN | ADMIN | Installer, mettre à jour, désinstaller | **Nouveau (agent en cours)** | Lu partiellement (prérequis 6.0 / PHP 8.3 cohérents avec `script.php`) ; relecture finale par le propriétaire |
| `docs/guide/02..04` | EN | FB | Premier formulaire, éditeur QuickMode, types de champs | **En cours d'écriture** | Voir §4 et §6 |
| `MIGRATION.md` | FR | MAINT, IA | Plan de migration code vers Joomla 6 pur, tâches à cocher | **Interne** (daté 2026-10-31 en tête, phases historiques en dessous) | Pas un guide utilisateur ; nom trompeur à la racine ; mal séparé du README « Upgrading » |
| `CONTRIBUTING.md` | EN | DEV | Environnement, normes, contrôles, PR, traductions | **Courant** | Cite les scripts `scripts/check-translations*.py` **[À VÉRIFIER existence]** |
| `SECURITY.md` | EN | ADMIN, DEV | Politique de divulgation | **Courant** | 55 lignes |
| `CODE_OF_CONDUCT.md` | EN | tous | Conduite | Courant | Non relu en détail |
| `CHANGELOG.md` | EN | ADMIN | Journal par version (RC23 en tête) | **Courant** | Source de vérité des nouveautés (widgets, samples, audit) ; doit rester aligné avec `com_breezingformsng_changelog.xml` et la release GitHub (`AGENTS.md`) |
| `com_breezingformsng_changelog.xml` | EN | ADMIN | Changelog lu par Joomla | Courant | Doublon obligatoire du MD |
| `llms.txt` (racine) et `docs/website/llms.txt` | EN | IA | Index pour IA | **Courant, léger** | Liste « PDF/CSV/XLSX/XML » exact ; liens vers le skill ; pas de lien vers les futurs guides |
| `skills/breezingformsng/SKILL.md` + `references/{elements,scripting,migration}.md` | EN | IA | Compétence agent : créer/embarquer/scripter | **Courant, partiel** | `elements.md` : 19 lignes OK mais sans widgets ni options Stripe/Klarna/Wero détaillées |
| `docs/website/README.md` | EN | MAINT | Fichiers à publier sur le site | Courant | |
| `docs/website/migrating-breezingforms-to-joomla-6.en.html` / `.fr.html` | EN/FR | ADMIN | Article de migration publié | **Courant** (dit « PHP 8.3+ ») | À aligner sur la décision de §1.3 |
| `docs/website/jsonld.html` | — | SEO | Métadonnées | Courant | `softwareVersion` à mettre à jour à chaque release |
| `docs/RELEASING.md` | EN | MAINT | Procédure RC/stable (RC23 cité) | **Courant** | Référence de la règle de maintenance |
| `docs/best-practices/*` (11 fichiers, `README.md` index) | FR/EN mixte **[À VÉRIFIER]** | IA, DEV | Guides Joomla 6 / PHP | **Interne** | Imposés par `AGENTS.md` aux agents ; titre « Guides Joomla 6 / PHP 8.4 pour agents IA » ; `php83-best-practices.md` à renommer si la cible est 8.4 |
| `docs/maintenance/mcp-read-only.md` | FR | ADMIN, DEV | Serveur MCP : activation, outils, sécurité | **Obsolète de nom, à jour de contenu** | 9 outils cités = 9 classes `Tool/*.php` ; ne mentionne pas l'API d'audit ; à traduire |
| `docs/maintenance/form-modes.md` | EN | MAINT | Seul QuickMode est supporté | Courant | 80 lignes |
| `docs/maintenance/breezingforms-upstream-docs.md` | EN | MAINT | Notes sur la doc amont Crosstec | Interne/ historique | Utile comme source de concepts (QuickMode, thèmes) |
| `docs/maintenance/quickmode-options-tab-plan.md`, `quickmode-treeview-migration-plan.md`, `js-libraries-migration-plan.md`, `php-architecture-migration-plan.md` (2835 l.), `performance-audit-2026-09.md` | FR/EN | MAINT | Plans et audits clos ou en cours | **Interne / historique** | Plan options clos le 2026-08-31 ; à archiver |
| `docs/maintenance/tests/*` (4 fichiers) | EN/FR | MAINT | Audit/plan/matrice de tests | Interne | |
| `docs/security/00..05` | FR | MAINT | Inventaire, menaces, constats, backlog | Interne | |
| `docs/audit-2026/*` | FR | MAINT | Audits 2026 + ROADMAP | Interne | |
| `BFNG_PLAN_SECURITE_CODEX.md` | FR | MAINT, IA | Prompt/plan d'audit pour Codex | **Interne, à ne pas publier** | À la racine du dépôt public |
| `AGENTS.md`, `CLAUDE.md` | EN | IA | Règles agents | Interne, courant | |
| `docs/charte-graphique-breezingformsng.md` + PDF | FR | MAINT (design) | Charte graphique | Interne | Utile pour la mise en forme des captures |
| `.github/pull_request_template.md`, issue templates | EN | DEV | Modèles GitHub | Courant | |
| `phpstan-stubs/contentbuilderng/README.md` | EN | DEV | Notes de stubs | Interne | |
| `media/com_breezingformsng/images/site/captcha/README.txt` | EN | — | README de la bibliothèque Securimage 2.0 BETA (tierce) | **Tiers, non documentation** | À ne pas toucher |
| Plugins `ADM/plugins/{bfcompat,breezingforms,privacy,webservices}` | — | — | **Aucun README** | Manquant | Seules leurs chaînes de langue existent |
| Aide intégrée (`COM_BREEZINGFORMSNG_HELP_*`, vue `help`) | EN (+7 langues) | ADMIN | Aide courte (3 points) pour **Records** et **Configuration** seulement | **Incomplet** | `ADM/tmpl/help/default.php` ne gère que `records` et `configuration` ; `scripts/help.php`, `pieces/help.php`, `integrator/help.php` existent mais non audités **[À VÉRIFIER]** |
| Infobulles `*_TIP` dans `ADM/language/en-GB/com_breezingformsng.ini` (2405 lignes) | EN | FB | Aide contextuelle par option | **Courant, riche** | Source principale pour les libellés des guides |

## 3. Ce que fait réellement le produit (vérifié dans le code)

Menu d'administration [O `breezingformsng.xml`] : **Records**, **Forms**, **Scripts (JS)**, **Pieces (PHP)**, **Integrator**, **BreezingForms Tools** (vue `packages`), **About**. Options Joomla : onglets General (e-mail par défaut, dossier d'uploads), privacy (désactiver le stockage de l'IP), historique (`save_history`, `history_limit`), CSV Export (séparateur, guillemets, retours à la ligne), **AI assistants (MCP)** (`mcp_enabled`, désactivé par défaut), Permissions [O `ADM/config.xml`].

| Domaine | Constaté | Preuve |
|---|---|---|
| Formulaires | Liste (nouveau, copier, **Convert to QuickMode**, publier, corbeille, check-in) ; édition avec onglets General / Email Notifications / Scripts | `ADM/src/View/Forms/HtmlView.php`, `tmpl/forms/edit.php` |
| QuickMode | Arbre Pages / Sections / Éléments, boutons « Page », « Section », « Element » ; propriétés form/page/section/élément ; onglet Options/Avancé ; thèmes « Bootstrap Themes » et « BreezingForms Themes » | `ADM/tmpl/quickmode/`, `ADM/layouts/quickmode/*`, `quickmode-elements.js` |
| Types de champs | 18 : `bfTextfield`, `bfTextarea`, `bfNumberInput` (+ curseur), `bfSelect`, `bfCheckbox`, `bfCheckboxGroup`, `bfRadioGroup`, `bfCalendar`, `bfCalendarResponsive`, `bfFile`, `bfHidden`, `bfSubmitButton`, `bfSummarize`, `bfCaptcha`, `bfReCaptcha`, `bfSignature`, `bfPayPal`, `bfStripe` ; widgets phone/datetime/country/address et zone de dépôt | `quickmode-elements.js`, `Service/Widget/*` |
| Notifications | Admin (« Administrator Notification », « Admin Email Address(es) ») et utilisateur (« User Confirmation », mailback) ; modèle par défaut ou personnalisé, HTML, pièce jointe (CSV/XML/PDF **[À VÉRIFIER formats exacts]**), expéditeur/sujet alternatifs ; « Log Only » ; double opt-in | `forms/edit.php`, `NotificationEngine.php`, `ADM/mailtpl` |
| Enregistrements | Liste, états Viewed/Exported/Archived, édition, import CSV, transfert entre formulaires | `ADM/src/Controller/Record*Controller.php`, `tmpl/records*` |
| Exports | **CSV, XLSX (méthode `spreadsheet`), PDF, XML** | `ADM/src/Service/RecordExporter.php` (`csv`, `spreadsheet`, `pdf`, `xml`) |
| Paiements | **PayPal et Stripe uniquement** (callbacks, webhook Stripe `checkout.session.completed`, téléchargement payant avec limite d'essais). Klarna/Wero = options Stripe, ce que je n'ai vérifié que par grep **[À VÉRIFIER]** | `Service/Callback/{PayPal,Stripe,StripeCheckoutWebhook,PaymentDownload*}.php` |
| Intégrations | Mailchimp (clé API, double opt-in), Salesforce, Dropbox (jeton, dossier, formats), Integrator (règles et critères vers tables SQL), ContentBuilder NG | `Service/Integration/*`, `ADM/src/Controller/IntegratorController.php` |
| Scripting | Scripts JS et Pieces PHP, bibliothèque standard `ADM/packages/stdlib.english.xml`, pages de test, droit `breezingformsng.code` | `tmpl/script*`, `tmpl/piece*`, `access.xml` |
| Packages et exemples | Écran Tools : Packages (Install / Create-edit / Dissolve), Sample data (5 exemples, jamais écrasés, ajoutés au menu « Tests ») | `PackagesController.php`, `SampleFormService.php` |
| Maintenance | Audit base (tables, collations, menus cassés, extensions héritées), statistiques, corbeille, inventaire d'enregistrements, journal d'installation, About (Piranha Easter Egg, protégé) | `ADM/tmpl/about/*`, `DatabaseAuditService.php` |
| Permissions | `core.admin/options/manage/create/edit/delete/edit.state`, `breezingformsng.code`, `breezingformsng.configuration` | `ADM/access.xml` |
| RGPD | Plugin privacy (export/suppression) ; option sans IP | `ADM/plugins/privacy`, `config.xml` |
| API / MCP | `POST /api/index.php/v1/breezingformsng/mcp` (9 outils, jeton API Joomla, option désactivée par défaut) ; `v1/breezingformsng/audit` | `Mcp/`, plugin webservices |
| Intégration front | Type de menu « Add Form », module `mod_breezingforms`, plugin de contenu `{BreezingForms:Nom}` | `ADM/modules`, `ADM/plugins/breezingforms` |
| Langues | 8 (en-GB, fr-FR, de-DE, it-IT, es-ES, hu-HU, nl-NL, tr-TR) | `breezingformsng.xml` |

Non trouvé / à ne pas documenter : export vers Google Sheets, autres passerelles (Mollie…), OAuth MCP (le doc MCP dit explicitement « non implémenté »).

## 4. Matrice des lacunes

Priorité selon l'ordre du propriétaire. Statut : missing / outdated / incomplete / ok.

| P | Sujet | Audience | Document existant | Statut | Action |
|---|---|---|---|---|---|
| P1 | Installation, mise à jour, désinstallation | ADMIN | `docs/guide/01-installation.md`, README | incomplete → ok après relecture | Relire ; corriger README |
| P1 | Premier formulaire (de bout en bout, publier) | FB | `docs/guide/02-first-form.md` (en cours), README (4 lignes) | missing (en cours) | Attendre l'agent |
| P1 | Éditeur QuickMode (arbre, propriétés, options, enregistrement) | FB | `docs/guide/03-quickmode-editor.md` (en cours) | missing (en cours) | idem |
| P1 | Types de champs (18 + widgets) | FB | `docs/guide/04-field-types.md` (en cours), `skills/.../elements.md` | incomplete | Inclure widgets ; corriger README « 20 » |
| P1 | Exemples (« Sample data ») comme point de départ | FB | CHANGELOG seul | missing | Section dans 02 |
| P2 | Conditions de visibilité, validation, multi-pages | FB | `elements.md`, `scripting.md` (agent) | incomplete | `docs/guide/05-advanced-forms.md` |
| P2 | Notifications admin et mailback, gabarits | FB, ADMIN | Infobulles uniquement | missing | `06-notifications.md` |
| P2 | Enregistrements, exports CSV/XLSX/PDF/XML, import CSV, transfert | ADMIN | README (1 ligne) | missing | `07-records-and-exports.md` |
| P2 | Paiements Stripe/PayPal | ADMIN | README (webhook Stripe) | incomplete | `08-payments.md` (reprendre le README) |
| P2 | Intégrations Mailchimp, Salesforce, Dropbox, Integrator | ADMIN, FB | Infobulles | missing | `09-integrations.md` |
| P2 | Permissions (ACL), RGPD, configuration | ADMIN | Aide intégrée (3 points) | incomplete | Guide administrateur |
| P2 | Packages et déplacement entre sites | ADMIN | skill `SKILL.md` | incomplete | `admin/packages.md` |
| P2 | Maintenance : audit, statistiques, corbeille | ADMIN | CHANGELOG | missing | `admin/maintenance.md` |
| P2 | Migration depuis Crosstec | ADMIN | README, pages site, `references/migration.md` | ok (3 versions à garder alignées) | Un seul texte source |
| P2 | Dépannage / FAQ | ADMIN | aucun | missing | À écrire plus tard |
| P3 | Scripting : scripts JS, pieces PHP, fonctions `ff_*` | DEV, FB | `skills/.../scripting.md` | incomplete (public agent) | `developer/scripting.md` |
| P3 | Serveur MCP | ADMIN, DEV | `docs/maintenance/mcp-read-only.md` | outdated (nom, langue FR) | Traduire et renommer |
| P3 | API REST (audit) | DEV | aucun | missing | Dans `reference/webservices-api.md` |
| P3 | Développement de l'extension | DEV | CONTRIBUTING, `docs/best-practices` | ok / interne | Index |
| P3 | Sécurité (politique) | tous | SECURITY.md | ok | — |
| P3 | Glossaire / langues | tous | aucun | missing | `reference/glossary.md` |

## 5. Organisation proposée

Principe : rien n'est supprimé ni déplacé tant que le propriétaire n'a pas validé. Les chemins ci-dessous sont des cibles ; les fichiers existants restent en place.

```
docs/
  README.md                         Documentation index (TOC; links everywhere below)
  guide/                            USER GUIDE (form builders)
    01-installation.md              [exists]  Installing and updating BreezingForms NG
    02-first-form.md                [in progress]  Your first form
    03-quickmode-editor.md          [in progress]  The QuickMode editor
    04-field-types.md               [in progress]  Field types and widgets
    05-advanced-forms.md            Conditions, validation and multi-page forms
    06-notifications.md             Email notifications and confirmations
    07-records-and-exports.md       Records, CSV/XLSX/PDF/XML export, import, transfer
    08-payments.md                  Payments with Stripe and PayPal
    09-integrations.md              Mailchimp, Salesforce, Dropbox, Integrator
  admin/                            ADMINISTRATOR GUIDE
    configuration-and-permissions.md  Options, ACL, privacy (GDPR)
    packages-and-samples.md         Packages and sample forms (Tools screen)
    maintenance.md                  Audit, statistics, trash
    upgrading-from-crosstec.md      Upgrading from Crosstec BreezingForms
    troubleshooting.md              Troubleshooting
  developer/                        DEVELOPER GUIDE
    scripting.md                    Scripts (JS) and pieces (PHP)
    mcp-server.md                   MCP server (from docs/maintenance/mcp-read-only.md)
    contributing.md                 -> CONTRIBUTING.md (link, not copy)
  reference/
    glossary.md                     Glossary (product labels)
    webservices-api.md              REST endpoints (mcp, audit)
    permissions.md                  ACL actions table
    languages.md                    Supported languages
  maintainers/                      MAINTAINERS (internal)
    RELEASING.md                    -> keeps docs/RELEASING.md
    best-practices/ , maintenance/ , security/ , audit-2026/  (existing, unchanged)
```

Sort des documents existants :

| Document | Décision |
|---|---|
| `README.md`, `CHANGELOG.md`, `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `llms.txt`, `docs/RELEASING.md`, `docs/website/*` | **Conservés tels quels** (corrections ponctuelles : README « 20 element types », PHP/Joomla, liens vers `docs/guide`) |
| `docs/guide/01..04` | Conservés ; ajouter l'index `docs/README.md` |
| `docs/maintenance/mcp-read-only.md` | **Fusionné/traduit** plus tard vers `docs/developer/mcp-server.md` (EN) ; garder l'original jusqu'à validation |
| `skills/breezingformsng/references/{elements,scripting,migration}.md` | Conservés (public agent) ; devenir des résumés renvoyant aux guides |
| `MIGRATION.md` | **À déplacer plus tard** (ex. `docs/maintenance/`) ou renommer (ex. `JOOMLA6-REFACTORING-LOG.md`) ; un lien README `MIGRATION.md` existe : ne pas casser |
| `BFNG_PLAN_SECURITE_CODEX.md` | **À archiver** (hors racine, ex. `docs/security/`) |
| `docs/maintenance/*plan*.md`, `performance-audit-2026-09.md`, `docs/maintenance/tests/*` | **Archivés** dans `docs/maintenance/archive/` après validation |
| `docs/best-practices/*`, `docs/security/*`, `docs/audit-2026/*`, charte graphique | Conservés, signalés « internal » dans l'index |
| `media/.../captcha/README.txt` | Intact (tiers) |

Nommage : minuscules, tirets, préfixe numérique d'ordre uniquement dans `docs/guide/` ; un titre `#` anglais par fichier ; chemins relatifs entre guides. Index `docs/README.md` avec table des matières par audience.

## 6. Captures d'écran à fournir

Toutes à la même résolution, thème clair Joomla 6 (Atum), site de test avec les exemples installés ; une version sombre uniquement pour `03`. Les noms sont ceux des libellés en-GB.

| Guide | Capture (écran + état) |
|---|---|
| 01 | System → Install → Extensions, onglet Upload Package File, ZIP déposé ; message de succès de l'installation ; menu Components → **BreezingForms NG** déployé (7 entrées) ; About avec « Latest Install Log » |
| 01 | Systems → Update : mise à jour proposée (si disponible) |
| 02 | **Forms** : liste vide puis avec un exemple ; bouton New ; écran de choix de création **[À VÉRIFIER : assistant de création existe ? `charte` le mentionne ; non confirmé dans `tmpl/`]** ; onglet General du formulaire ; formulaire de contact enregistré dans QuickMode ; menu Joomla de type « Add Form » ; formulaire rendu côté site ; **Records** avec un enregistrement |
| 02 | **Tools → Sample data** : tuiles des 5 exemples, un coché |
| 03 | QuickMode complet : arbre à gauche (Form > Page > Section > éléments), panneau de propriétés ; menu d'ajout d'élément ouvert ; propriétés d'un élément (onglets) ; onglet Options/Avancé avec thèmes « Bootstrap Themes » / « BreezingForms Themes » ; message « Options saved! … » ; mode sombre |
| 04 | Un rendu par type (Textfield, Textarea, Number input, Slider, Select, Checkbox, Checkbox group, Radio group, Calendar, Responsive Calendar, File avec progression, Hidden, Submit Button, Summarize, Captcha, ReCaptcha, Signature, PayPal, Stripe) ; formulaire d'exemple « Field widgets » : téléphone, date/heure, pays, adresse, zone de dépôt |
| 05 | Règle de visibilité d'un élément (propriété) ; formulaire multi-pages avec barre de progression ; message de validation d'erreur |
| 06 | Forms → onglet **Email Notifications** : sections « Administrator Notification » et « User Confirmation » ; éditeur de gabarit (« Click a field to insert it ») ; e-mail reçu (client de test) |
| 07 | **Records** liste avec filtres, colonnes et indicateurs ; édition d'un enregistrement ; menu d'export (4 formats) ; import CSV (`Recordsimport`) ; transfert (étapes select/mapping/confirm/report) ; exemple de PDF |
| 08 | Élément Stripe avec « Webhook endpoint URL » et « Webhook signing secret » ; Stripe Dashboard → Webhooks (capture externe, à confirmer côté propriétaire) ; élément PayPal ; page de retour |
| 09 | Propriétés Mailchimp (clé API, double opt-in), Dropbox (jeton, dossier), Integrator liste et édition |
| admin | Options : onglets General, uploads, privacy, CSV, **AI assistants (MCP)**, Permissions ; Tools → Packages (Install / Create-edit / Dissolve) ; Maintenance : rapport d'audit, statistiques, corbeille |
| dev | Options MCP activées ; (pas de capture pour les scripts : copier-coller de code) |

## 7. Guide de style

**Voix** : anglais britannique cohérent avec en-GB (« colour », « licence » comme nom, mais « license » dans les textes de licence existants : suivre en-GB du produit) ; deuxième personne, impératif, présent ; phrases courtes ; pas de marketing ; pas d'emojis.

**Structure d'une page pratique** : titre `#` orienté tâche (« Create a contact form ») ; une phrase d'objectif ; *Before you start* (prérequis, droits) ; étapes numérotées, une action par étape, chemin de menu en gras (**Components → BreezingForms NG → Forms**) ; résultat attendu ; capture avec légende ; *Troubleshooting* (facultatif) ; *Related* (liens relatifs). Ne rien promettre qui ne soit trouvé dans le code ; marquer « [À VÉRIFIER] » dans les brouillons seulement.

**Terminologie** (libellés en-GB du produit, à reprendre tels quels) :

| Terme à employer | À éviter | Source |
|---|---|---|
| BreezingForms NG | BFNG (hors contexte technique), BF | `COM_BREEZINGFORMSNG` |
| Forms, Records, Scripts (JS), Pieces (PHP), Integrator, BreezingForms Tools, About | « submissions » pour l'écran Records (acceptable dans le texte courant, après « records ») | `.sys.ini`, `.menu.ini` |
| QuickMode | quick mode, Easy Mode (disparu), Classic Mode (convertible seulement) | `form-modes.md` |
| Page, Section, Element (arbre) ; « Form properties » | step, row | `NEW_PAGE`, `NEW_SECTION`, `NEW_ELEMENT` |
| Textfield, Textarea, Number input, Slider, Select, Checkbox, Checkbox group, Radio group, Calendar, Responsive Calendar, File, Hidden input, Submit Button, Summarize, Captcha, ReCaptcha, Signature, PayPal, Stripe | « text box », « dropdown » | `elements.md` |
| Administrator Notification, User Confirmation (mailback) | « autoresponder » | `FORMS_EMAIL_ADMIN`, `FORMS_EMAIL_USER` |
| Double Opt-In | double-optin | `DOUBLE_OPTIN` |
| Bootstrap Themes, BreezingForms Themes | | `ADVANCED_THEME_*` |
| Sample data, Packages | templates | `TOOLS_*` |
| Convert to QuickMode | migrate form | `FORMS_CONVERT_LEGACY` |
| Enable MCP, AI assistants (MCP) | | `CONFIG_MCP_*` |

Règle : toute chaîne d'interface citée est copiée du fichier en-GB ; si un libellé change, le guide est modifié dans la même PR. Les traductions des guides ne sont pas prévues (décision du propriétaire **[À VÉRIFIER]**).

## 8. Règle de maintenance

- Chaque PR qui change un écran, un libellé en-GB, une option ou un type de champ met à jour le guide concerné dans la même PR (case à cocher à ajouter à `.github/pull_request_template.md`).
- À chaque release (RC et stable) : suivre `docs/RELEASING.md` ; les entrées `CHANGELOG.md` et `com_breezingformsng_changelog.xml` sont obligatoires ; le mainteneur de la release relit le README (versions, nombre de types, prérequis), `llms.txt`, `docs/website/jsonld.html` (`softwareVersion`) et les captures dont l'écran a changé.
- Un propriétaire par document (indiqué dans l'en-tête de `docs/README.md`) ; revue des guides à chaque version stable (contrôle de liens et des libellés par rapport à `ADM/language/en-GB/*.ini`).
- Les documents internes (audits, plans, sécurité) portent la mention « internal » dans l'index et ne sont pas publiés sur le site.

## 9. Points à valider par le propriétaire

1. Cible annoncée : « Joomla 6.0 / PHP 8.3 » (installateur) ou « 6.2 / 8.4 » (AGENTS.md).
2. Renommer/déplacer `MIGRATION.md` et `BFNG_PLAN_SECURITE_CODEX.md` ; traduire `mcp-read-only.md`.
3. Corriger le README (« 20 element types », section configuration, lien vers les guides).
4. Existence d'un assistant de création de formulaire (la charte le cite ; non confirmé dans `tmpl/`).
5. Formats exacts des pièces jointes aux e-mails et rôle précis de Klarna/Wero dans Stripe.
