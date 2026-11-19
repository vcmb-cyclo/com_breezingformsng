# Audit produit et concurrentiel — BreezingForms NG (BFNG)

Date : 2026-11-19. Version auditée : 6.1.0-RC23 (branche `main`, commit 1faa98d2). Audit en lecture seule ; aucun code applicatif modifié.

Légende des classes de preuve : **[O]** observé (code lu, fichier:ligne) · **[D]** documenté (README, docs, changelog, site) · **[S]** supposé.
Les chemins sont relatifs au dépôt ; `ADM` = `administrator/components/com_breezingformsng`, `SITE` = `components/com_breezingformsng`.

Méthode : lecture ciblée du code (échantillonnage, pas de revue exhaustive des ~2900 lignes MCP ni du moteur de rendu), des docs (`README.md`, `CHANGELOG.md`, `docs/maintenance/mcp-read-only.md`, `skills/breezingformsng/references/elements.md`) et recherche web (consultée le 2026-11-19). Aucune donnée du projet n'a été envoyée : les requêtes web ne contenaient que des noms de produits et mots-clés génériques. Aucune exécution applicative n'a été faite : le comportement runtime n'est donc jamais « constaté », seulement lu.

## 1. Matrice fonctionnelle

| Domaine | Fonctionnalité | Classe | Preuve |
|---|---|---|---|
| Champs | 18 types QuickMode (texte, zone de texte, nombre/slider, liste, case, groupe de cases, radio, calendrier, fichier, caché, envoi, summarize, captcha image, reCAPTCHA, signature, PayPal, Stripe) | D | `skills/breezingformsng/references/elements.md:5-24` |
| Champs | Widgets : téléphone international, date/heure, liste de pays, recherche d'adresse | O | `SITE/src/Service/Widget/WidgetOptions.php:24` (`WIDGETS = ['phone','datetime','country','address']`) |
| Champs | Dépôt de fichiers par glisser-déposer, découpage en morceaux, redimensionnement d'image | O (services) / D (comportement) | `SITE/src/Service/Widget/WidgetFieldBuilder.php:43`, `SITE/src/Service/Upload/FlashChunkAssembler.php`, `ImageResizer.php` |
| Champs | Adresses : suggestions désactivées par défaut, tiers explicitement choisi | D | `CHANGELOG.md` RC23, « Added » |
| Logique | Règles de visibilité « turn on/off … if … is/isnot … » (une règle par ligne, regex possible) | O (parser) / D (syntaxe) | `SITE/src/Service/Rendering/QuickMode/QuickModeToggleFieldsParser.php:22` ; elements.md |
| Logique | Pas de groupes ET/OU imbriqués, pas de logique sur les e-mails/intégrations | S | Absent de la syntaxe documentée ; non cherché exhaustivement |
| Calcul | Champ `bfSummarize` (calcul JS) ; estimation dynamique dans les exemples | D | elements.md ; CHANGELOG RC23 (exemples « Event registration », « Quote request ») |
| Multi-pages | Pages avec validation, barre de progression, mode une page | D | README.md « Form building » |
| E-mails | Notification admin + mailback (confirmation) avec pièces jointes, gabarits | O (fichiers) | `ADM/mailtpl/mailtpl.txt.php`, `mailbacktpl.txt.php`, `SITE/src/Service/Notification/NotificationEngine.php` |
| Paiements | Stripe (Checkout, Klarna, Wero), webhook signé, PayPal, téléchargement payant | O | `SITE/src/Service/Callback/StripeCheckoutWebhook.php:20`, `PayPalCallback.php`, `PaymentDownloadService.php` |
| Intégrations | Mailchimp, Salesforce, Dropbox | O | `SITE/src/Service/Integration/MailchimpClient.php:16`, `SalesforceClient.php`, `DropboxUploader.php` |
| Intégrations | Integrator : copie des données soumises vers une table SQL arbitraire, avec critères | O | `SITE/src/Service/Integration/IntegratorRuntime.php:32`, `ADM/src/Model/IntegratorModel.php` |
| Intégrations | Webhook sortant générique / Zapier / Make / n8n | S (non trouvé) | Seul un webhook entrant Stripe est présent (grep « webhook » : 5 fichiers, tous paiement/dispatch) |
| Exports | PDF, CSV, XLSX (plafond 25 000 lignes, 16 384 colonnes), XML ; import CSV | O | `ADM/src/Service/RecordExporter.php:42,49,247` ; `ADM/src/Service/RecordImporter.php` ; `CsvCellSanitizer.php` |
| Enregistrements | Gestionnaire (vus/exportés/archivés), édition, actions par lot, transfert | O (contrôleurs) | `ADM/src/Controller/RecordsController.php`, `RecordstransferController.php` |
| MCP | Neuf outils (lecture + écriture), point d'accès API Joomla | O | `ADM/src/Mcp/Tool/*.php` ; `ADM/src/Mcp/McpServer.php:27` (versions 2025-03-26, 2025-06-18, 2025-11-25) |
| MCP | Désactivé par défaut (`mcp_enabled`) | O | `ADM/config.xml:109` |
| Audit BD | Audit et réparation (index, collations, orphelins) | O | `ADM/src/Service/DatabaseAuditService.php:59`, `DatabaseRepairService.php` |
| Migration | Mise à jour in situ depuis Crosstec BreezingForms (tables `#__facileforms_*` conservées) | D | README.md « Upgrading » ; `script.php` (180 Ko, non relu en entier) |
| Migration | Convertisseur Easy/Classic -> QuickMode (sous-ensemble : texte, zone, case, caché, liste simple, radio non ambigus) | O / D | `ADM/src/Service/LegacyFormConverter.php:16` ; README.md |
| Migration | Plugin `bfcompat` pour les classes `BF*` | O | `ADM/plugins/bfcompat/` |
| i18n | 8 langues (en, fr, de, it, es, hu, nl, tr) | D | README.md ; `AGENTS.md` |
| Anti-spam | Captcha image maison (`CaptchaCodeStore`, bibliothèque maintenue), reCAPTCHA vérifié côté serveur | O | `SITE/src/Service/Security/CaptchaCodeStore.php:24` ; `Integration/RecaptchaVerifier.php` |
| Anti-spam | Honeypot, limitation de débit, Akismet, Turnstile/hCaptcha | S (non trouvé) | grep sans résultat dans `ADM/src`, `SITE`, `ADM/libraries` (hors vendor) |
| RGPD | Plugin privacy Joomla : export, suppression des données et fichiers, copies ContentBuilder ; désactivation du stockage d'IP | O | `ADM/plugins/privacy/src/Extension/Breezingformsng.php:85,148,153,316` ; `ADM/config.xml:33` |
| Script | Hooks JS/PHP par formulaire, droit ACL dédié au code | O / D | `SITE/src/Service/Scripting/*` ; README.md |
| Exemples | Trois formulaires modèles + un exemple technique | O | `ADM/src/Service/SampleFormService.php:73-92` |
| Easter egg | Piranha : cinq clics sur le logo, WebP, fermeture X/Échap | O | `ADM/tmpl/about/default.php:425,578-584` ; `media/com_breezingformsng/js/admin/about.js:33-40` ; `media/com_breezingformsng/images/bfng-easter-egg-2026.webp` (replay et image finale : non relus en détail) |
| IA générative | Génération de formulaires par le produit lui-même, aide à la rédaction, analyse des soumissions | S (absent) | grep `openai|anthropic|llm` : aucun résultat dans `ADM/src`, `SITE`, `media/js` |
| Tests | Suites PHPUnit (Administrator, Site, Security, Integration), E2E Playwright dont `mcp-endpoint.spec.mjs` | O | `tests/` |

## 2. Tableau concurrentiel (sources consultées le 2026-11-19)

Les affirmations concurrentes proviennent de pages marketing ou d'annuaires ; elles ne sont pas vérifiées en conditions réelles.

| Produit | Points constatés dans la source | Compatibilité / remarque | Source |
|---|---|---|---|
| RSForm! Pro (Joomla) | Multi-pages, conditions, calculs, édition front des soumissions, paiements via plugins (PayPal, virement), Mailchimp, Salesforce, Akismet, reCAPTCHA, PDF | Support Joomla 6 : sources contradictoires, **non confirmé** ; aucune note de version 2026 officielle trouvée | <https://extensions.joomla.org/extension/rsform-pro/> ; <https://www.rsjoomla.com/joomla-extensions/joomla-form.html> |
| Convert Forms (Joomla) | Joomla 6 natif depuis 5.1.0 ; conditions ET/OU (Pro) avec comparaison champ à champ ; conditions sur les tâches d'intégration ; MailChimp, HubSpot, Salesforce, Zapier/Make/n8n | v5.2.7, 04/11/2026 | <https://www.tassos.gr/docs/convert-forms/features/conditional-fields> ; <https://www.tassos.gr/joomla-extensions/convert-forms/changelog> |
| ChronoForms / Fabrik / core Forms-Custom Fields (Joomla) | Une page comparative ChronoForms 8 / RSForm est apparue dans les résultats, rien d'autre lu | **Non vérifié** | <https://www.chronoengine.com/blog/joomla-forms-builders-rsforms-pro-vs.-chronoforms-8-which-one-is-better> (non lue) |
| Gravity Forms (WP) | Serveur MCP intégré à partir de la 3.1, toutes licences : génération de formulaire en langage naturel, inspection de la logique conditionnelle, gestion des flux/notifications, recherche et mise à jour d'entrées, entrées de test passant par le pipeline normal ; skills d'agent téléchargeables | Dates des billets incohérentes (août à novembre 2026) | <https://www.gravityforms.com/feature/gravity-forms-mcp/> ; <https://www.gravityforms.com/blog/introducing-gravity-forms-mcp/> |
| Fluent Forms (WP) | MCP depuis 6.2.12 (10/08/2026), désactivé par défaut, exécuté avec les droits de l'utilisateur WP ; outils lecture/écriture ; 6.2.16 passe par l'Abilities API ; 6.3.0 annonce des formulaires de chat IA | Nombre d'outils non confirmé | <https://docs.fluentforms.com/mcp-for-ai-agents> ; <https://fluentforms.com/fluent-forms-6-3-0-sneakpeek/> |
| WPForms (WP) | Constructeur IA ; MCP via Abilities API de WordPress 6.9 et serveur WPVibe ; écriture désactivée tant qu'elle n'est pas activée | Pages comparatives partisanes | <https://wpforms.com/features/wpforms-ai/> ; <https://wpforms.com/wpforms-vs-fluent-forms/> |
| Formidable, Forminator, Ninja Forms | Non recherchés | **Non vérifié** | n/a |

Lecture : en 2026 le MCP est devenu un standard chez les deux acteurs WordPress majeurs ; BFNG est le seul constructeur Joomla trouvé avec un MCP, mais aucune recherche spécifique « RSForm MCP » n'a été faite, donc cette exclusivité est **non vérifiée**.

## 3. Différenciateurs de BFNG

- Continuité de données BreezingForms/Crosstec : installation par-dessus, mêmes tables, plugin de compatibilité, convertisseur QuickMode [D/O]. Aucun concurrent trouvé ne revendique cela.
- Intégration native avec ContentBuilder NG (même éditeur) : synchronisation des vues, nettoyage RGPD [O].
- MCP avec garde-fous sérieux : désactivé par défaut, droits Joomla par outil, pas de lecture des soumissions, pas de suppression définitive, version/verrou/historique, validation `Origin`, adresses de notification masquées [O/D] (`ADM/src/Mcp/OriginValidator.php:17`, doc MCP).
- Audit/réparation de base de données et plugin privacy complet [O] : rare dans les constructeurs de formulaires.
- Scripting PHP/JS par formulaire avec ACL dédiée : puissant pour les intégrateurs [O/D].
- Stripe avec webhook signé et idempotence documentée, Klarna/Wero [O/D].
- Zéro licence payante : GPL, tout inclus [D].

## 4. Lacunes constatées

Voir PRD-002 à PRD-006 : pas d'IA dans le produit, webhook sortant générique absent, anti-spam limité, logique conditionnelle limitée aux règles de visibilité, MCP restreint à 6 types de champs et sans OAuth.

## 5. Intégration ContentBuilder NG

- **Observé** : `ADM/src/Service/ContentBuilderFormSync.php:33` synchronise les vues CB NG construites sur un formulaire BFNG après modification (utilisé par `bfng_manage_form_fields`, doc MCP). `SITE/src/Service/Integration/ContentBuilderInstallation.php` et `ContentBuilderRecordCleanup.php` gèrent détection et nettoyage RGPD ; `SITE/src/Service/Rendering/ContentBuilderFormMetadataLoader.php` charge les métadonnées. Le dépôt voisin existe (`../com_contentbuilderng`, non lu en profondeur ; son README annonce un point d'accès API et un mode aperçu **[D]**).
- **Lacune** : aucun outil MCP ne crée ou n'expose une vue CB NG ; l'enchaînement « formulaire -> liste/article » reste manuel **[S]** (aucun outil `cb_*` dans `ADM/src/Mcp/Tool`).

## 6. IA générative

Constat : aucun appel de modèle de langage dans le produit [O, absence par grep]. L'IA passe uniquement par des assistants externes pilotant le MCP, ce que le projet documente (`llms.txt`, `skills/breezingformsng/`).

Pistes (toutes rattachées à l'existant) :
- Génération de formulaire : déjà possible côté client via `bfng_create_form` (max 30 champs, 6 types, validation `required` uniquement). La limite est le plafond fonctionnel de l'outil, pas l'IA.
- Aide à la rédaction : libellés/aides/messages dans l'éditeur QuickMode ; nécessite un connecteur à un fournisseur, donc consentement explicite et option désactivée par défaut, sur le modèle des suggestions d'adresse (CHANGELOG RC23).
- Analyse des soumissions : à ne pas offrir via MCP aujourd'hui (le MCP ne lit volontairement pas les soumissions, `docs/maintenance/mcp-read-only.md`). Si ajoutée, exiger agrégats ou pseudonymisation, jamais les champs bruts d'un formulaire contenant du RGPD sensible.

## 7. MCP

État [O/D] : 9 outils (`bfng_list_forms`, `get_form`, `check_form`, `audit_database`, `create_form`, `update_form`, `manage_form_fields`, `trash_form`, `restore_form`). Authentification par jeton API Joomla ; **OAuth/découverte MCP non implémentée** (doc MCP), compatibilité avec un client précis à vérifier avant de la promettre.

Écarts vs concurrents : Gravity Forms expose les flux, notifications, entrées et entrées de test ; Fluent Forms expose styling, confirmations, notifications. BFNG n'expose pas les e-mails (modèles), l'Integrator, les règles de visibilité, ni la lecture des soumissions. Le périmètre actuel est cohérent avec une posture prudente, mais limite l'utilité d'un assistant à la création de formulaires simples.

## 8. Simplification du parcours utilisateur

- Les exemples installables depuis Tools (3 modèles + 1 technique) sont un bon point d'entrée [O]. Il n'existe pas d'assistant de premier lancement ni de galerie de modèles dans l'écran « Forms » **[S]** (non cherché dans les vues).
- Les règles de visibilité sont saisies en texte libre (une règle par ligne) [D] : source d'erreurs et inaccessible à un non-technicien. Un générateur visuel de règles est le gain de simplicité le plus net (voir PRD-004).
- La conversion de formulaires legacy est limitée : tous les autres cas exigent une reconstruction manuelle [D].

## 9. Constats et propositions priorisées

### PRD-001 — MCP : étendre l'écriture aux e-mails, à la visibilité et aux types de champs supplémentaires
- Catégorie : MCP / différenciation · Impact : élevé · Confiance : moyenne
- Preuves : `ADM/src/Mcp/Tool/CreateFormTool.php`, `ManageFormFieldsTool.php` ; doc MCP (6 types de champs, « scripts personnalisés, autres validations, intégrations, téléversements et paiements ne sont pas configurables ») ; concurrents : Gravity Forms MCP (notifications, logique), Fluent Forms (confirmations, notifications).
- Conséquences : un assistant ne peut pas produire un formulaire réellement utilisable (e-mail de confirmation, champs conditionnels, téléphone/date).
- Recommandation : ajouter progressivement `bfng_manage_form_notifications` (mailback, texte brut, mêmes garde-fous que l'adresse masquée) et un champ `visibility` pour la syntaxe de `QuickModeToggleFieldsParser`, puis les widgets de `WidgetOptions::WIDGETS`.
- Effort : moyen à élevé · Risque de régression : moyen (écritures versionnées, mêmes verrous que `McpFormWriter`)
- Tests : extension de `tests/Administrator/Mcp`, `mcp-endpoint.spec.mjs`, vérification ACL deny/allow, rejet des modèles d'e-mail contenant `<?php`.

### PRD-002 — Aucune IA native : proposer une aide à la rédaction optionnelle et consentie
- Catégorie : IA · Impact : moyen · Confiance : faible
- Preuves : absence de grep ; WPForms et Fluent Forms annoncent IA native (sources §2).
- Conséquences : retard perçu face aux acteurs WordPress, mais risque RGPD réel si la fonction envoie des contenus.
- Recommandation : n'envoyer à un fournisseur que le brouillon de libellé saisi par l'administrateur, jamais de soumission ; option globale désactivée par défaut dans `ADM/config.xml` et message explicite dans l'éditeur, comme pour les suggestions d'adresse (CHANGELOG RC23) ; clé fournisseur dans les paramètres avec ACL.
- Effort : moyen · Risque : faible (fonction additive) · Tests : option désactivée = aucun appel sortant ; absence de fuite de clé dans les journaux ; ACL.

### PRD-003 — Webhook sortant générique
- Catégorie : intégrations · Impact : élevé · Confiance : moyenne
- Preuves : seuls Mailchimp/Salesforce/Dropbox sont présents (`SITE/src/Service/Integration/`) ; webhook présent uniquement en entrée pour Stripe ; Convert Forms annonce Zapier/Make/n8n (§2).
- Conséquences : tout service autre que ces trois exige du PHP personnalisé ou l'Integrator vers une table SQL.
- Recommandation : un type d'Integrator « HTTP POST signé » (HMAC), file d'attente avec nouvelle tentative, sur le modèle de l'idempotence déjà imposée par le webhook Stripe ; liste d'hôtes autorisés pour limiter le SSRF.
- Effort : moyen · Risque : moyen (soumission synchrone ; échec distant ne doit pas bloquer l'envoi) · Tests : signature, timeout, SSRF, secret absent des journaux.

### PRD-004 — Éditeur visuel pour les règles de visibilité et conditions plus riches
- Catégorie : UX / logique · Impact : élevé · Confiance : moyenne
- Preuves : syntaxe texte `turn on element X if Y is Z` (elements.md), `QuickModeToggleFieldsParser.php:22` ; Convert Forms propose ET/OU et comparaisons champ à champ (§2).
- Conséquences : erreurs de saisie, pas de combinaisons ET/OU ; l'usage de la logique est réservé aux utilisateurs avancés.
- Recommandation : construire une interface dans l'éditeur QuickMode qui génère la même syntaxe (compatibilité arrière des formulaires existants), puis étendre le parseur aux conditions multiples.
- Effort : moyen · Risque : moyen (rendu côté client et validation côté serveur doivent rester cohérents) · Tests : JS (`tests/JavaScript`), parseur, formulaires existants inchangés.

### PRD-005 — Renforcer l'anti-spam
- Catégorie : sécurité produit · Impact : moyen · Confiance : moyenne
- Preuves : captcha image et reCAPTCHA seulement (`CaptchaCodeStore.php:24`, `RecaptchaVerifier.php`) ; honeypot, limitation de débit et alternatives respectueuses de la vie privée non trouvés ; RSForm! Pro propose Akismet (§2).
- Conséquences : dépendance à Google ou à une image, friction d'accessibilité ; pas de défense contre les envois répétés depuis une même IP (note : le stockage d'IP est optionnel, `config.xml:33`).
- Recommandation : honeypot et délai minimal de remplissage configurables par formulaire ; limitation de débit par session ; Turnstile/hCaptcha en option en réutilisant la structure de `RecaptchaVerifier`.
- Effort : faible à moyen · Risque : faible (opt-in) · Tests : refus des bots simples, pas de faux positifs sur les formulaires multi-pages.

### PRD-006 — Parcours de démarrage et conversion legacy
- Catégorie : UX / adoption · Impact : moyen · Confiance : moyenne
- Preuves : `LegacyFormConverter.php` ne couvre qu'un sous-ensemble (README) ; les modèles sont installés depuis Tools (`SampleFormService.php:73-92`).
- Conséquences : les sites Crosstec avec formulaires complexes doivent tout reconstruire à la main.
- Recommandation : rapport de convertibilité avant conversion, listant les éléments bloquants par formulaire (la conversion avertit déjà par élément) ; étendre le convertisseur aux types fréquents, et proposer un accès aux modèles depuis l'écran Forms.
- Effort : moyen · Risque : faible (conversion crée une copie dépubliée, aucun changement de l'original) · Tests : jeux de formulaires legacy dans `tests/Administrator/Fixtures`.

### PRD-007 — MCP : authentification OAuth et validation par client
- Catégorie : MCP · Impact : moyen · Confiance : moyenne
- Preuves : doc MCP (« la prise en charge de la découverte et du parcours OAuth MCP n'est pas implémentée ») ; `McpServer.php:27`.
- Conséquences : la configuration du client exige de copier un jeton Joomla à longue durée de vie, sans portée limitée à BFNG.
- Recommandation : au minimum documenter un compte Joomla dédié à permissions minimales ; ensuite, jeton à portée MCP uniquement et expiration. OAuth peut attendre le standard du client visé.
- Effort : élevé · Risque : moyen · Tests : jeton hors portée refusé ; `Origin` ; ACL.

### PRD-008 — Pont MCP vers ContentBuilder NG
- Catégorie : écosystème · Impact : moyen · Confiance : faible
- Preuves : `ContentBuilderFormSync.php:33` existe, mais aucun outil CB dans `ADM/src/Mcp/Tool`.
- Recommandation : n'exposer que la lecture de l'état de synchronisation et la liste des vues liées à un formulaire ; créer ou modifier les vues reste dans ContentBuilder NG (autre dépôt).
- Effort : faible · Risque : faible · Tests : ContentBuilder NG absent = outil masqué ; ACL des deux composants.

### PRD-009 — Préserver les protections de produit
- Catégorie : conformité interne · Impact : élevé · Confiance : élevée
- Preuves : Easter egg et XLSX observés (§1) ; `AGENTS.md`.
- Recommandation : toute réduction de paquet ou évolution de l'export conserve le WebP de l'Easter egg, ses cinq clics, le replay, l'image finale, la fermeture X/Échap, et les dépendances XLSX. Un test statique du WebP et du hotspot dans `tests/Packaging` protégerait ce comportement (existence d'un tel test : non vérifiée).
- Effort : faible · Risque : nul · Tests : empaquetage contient `bfng-easter-egg-2026.webp` et la bibliothèque XLSX.

## 10. Limites de l'audit

- Pas d'exécution du composant ; aucun comportement n'est « constaté », seulement lu.
- Lecture partielle : `script.php`, `PackageTransferModel.php` (98 Ko), le moteur de rendu, le dépôt ContentBuilder NG et `docs/website` n'ont pas été relus.
- Les fonctionnalités « absentes » sont déduites de greps ciblés ; un nom différent ou un fichier non cherché peut les cacher. À confirmer avant décision.
- Concurrents : sept sources, pour l'essentiel marketing ; ChronoForms, Fabrik, Joomla core, Formidable, Forminator, Ninja Forms **non vérifiés** ; support Joomla 6 de RSForm! Pro non confirmé. Les tarifs, parts de marché et performances ne sont pas traités.
- Le recensement « 18 types de champs » repose sur la doc, qui compte aussi les widgets (téléphone, date/heure, pays, adresse) comme types distincts dans le changelog, mais pas dans `elements.md` (écart de documentation à corriger).
