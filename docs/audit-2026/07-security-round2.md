# Audit sécurité BreezingForms NG : rapport 07 (deuxième passe)

Revue statique en lecture seule, branche `audit-2026-rc23` (HEAD `66658c0f`), réalisée le 2026-11-19. Cible : Joomla 6.2 / PHP 8.4, compatibilité PHP 8.5. Seul livrable : ce fichier. Aucun fichier applicatif n'a été modifié.

## 1. Résumé

Cette passe a deux objectifs : (1) revérifier sur le code courant les 11 fiches de `docs/security/03-findings.md` laissées « non revérifiées » par le rapport 01, (2) couvrir les zones peu couvertes (SQL, XSS par contexte, intégrations, PII/journaux, MCP, captcha, installateur, bibliothèques embarquées) et relire les correctifs du jour pour y chercher des contournements.

Résultats principaux :

1. **Régression confirmée dans un correctif du jour : `OptLinkPolicy` refuse tous les vrais liens de double opt-in et d'opt-out** (SEC-R2-001). Le jeton réellement émis est du base64 de 20 caractères (28 caractères terminés par `=`), alors que la politique n'accepte que `[A-Za-z0-9]{16,64}`. Le test unitaire utilise un jeton de 20 caractères alphanumériques qui n'est pas celui que le code génère. Conséquence : le lien de retrait du consentement (opt-out) ne fonctionne plus, ce qui est aussi un sujet RGPD.
2. **Écrasement d'éléments d'un autre formulaire par QuickMode (F-10) toujours présent** (SEC-R2-002) : l'`UPDATE` d'un élément est borné par `id` seul.
3. **Inclusion de fichiers PHP pilotée par des valeurs stockées dans le formulaire**, sans validation à l'exécution : nom de thème (`script.php`, SEC-R2-003) et nom de formulaire (gabarits `mailtpl`/`pdftpl`, SEC-R2-004). Chemin exploitable par un éditeur `core.edit` sans droit « code » ; l'exécution de code exige en plus un fichier de nom adapté déjà présent sur le disque (non démontré).
4. **La restauration d'une version de formulaire réécrit les colonnes de code avec le seul droit `core.edit`** (F-19, SEC-R2-005) : `FormCodeGuard` n'est pas appliqué sur ce chemin.
5. Aucune injection SQL ni XSS réfléchi/stocké par un visiteur n'a été démontrés ; `composer audit --locked` ne signale aucun avis. Pour les bibliothèques JavaScript, les versions sont relevées mais les avis ne sont pas vérifiables hors ligne (voir 8).

Statut des 11 fiches : 3 corrigées (F-25, F-28, F-38), 8 toujours présentes (F-10, F-18, F-27, F-29 non confirmée, F-31, F-32, F-35, F-36), la plupart de gravité faible. Détail en section 4. Nouveaux constats : 15 (SEC-R2-001 à SEC-R2-015).

Important : l'arbre de travail contient des modifications non commitées en cours (`FormCodeGuard::applyToElement`, `QuickmodeModel`, `PackageTransferModel`, `OptCallback`, `NotificationEngine`, `PaymentNotificationCache`, `helpers.php`, `McpFormWriter`, `QuickmodeController`) pendant cette revue. Les numéros de ligne cités sont ceux lus au moment de la lecture ; certains constats (code JavaScript des éléments QuickMode, `filepath` du téléchargement payant, cache de paiement) peuvent déjà être en cours de traitement.

## 2. Méthode et limites

Lectures : `docs/audit-2026/01-security.md`, `ROADMAP.md` (tableau de statut), `docs/security/03-findings.md`, puis code source (`administrator/.../src`, `components/.../src`, `libraries/crosstec`, plugins, `api/`, `script.php`) par `grep` ciblés et lecture des fonctions concernées : correctifs du jour (`FormCodeGuard`, `UploadExtensionPolicy`, `PayPalPayment::isReceiver`, `OptLinkPolicy`/`OptCallback`, `PaymentDownloadFilePolicy`, `SignatureImagePolicy`, `TokenizedDirectoryResolver`, `UploadDirectoryProtection`, `FlashUploadCallback`), clients d'intégration (Mailchimp, Salesforce, Dropbox, reCAPTCHA, Stripe, PayPal), MCP (`McpController`, `McpServer`, `OriginValidator`, outils), captcha (`CaptchaCodeStore`, `CaptchaCallback`, `CaptchaImage`), `script.php` (suppressions, uninstall), gabarits d'administration.

Commandes exécutées (lecture seule) : `grep`, `sed`, `php -r` (calcul de base64 et d'une expression régulière, PHP 8.5.4 hôte), `composer audit --locked` à la racine et dans `administrator/components/com_breezingformsng` (résultat : « No security vulnerability advisories found », PHP 8.5.4 hôte, réseau requis), `git status/diff/log`.

**Non exécuté** : aucun test HTTP, ACL, CSRF, upload, paiement sandbox, migration ; ni PHPUnit, PHPStan, PHPCS, Psalm ; pas de Docker (`joomla6-joomla-1` non utilisé) ; `npm audit` impossible (binaire `npm` absent) ; les sources installées de Joomla 6.2 n'ont pas été relues (comportement de `Uri::isInternal`, `InputFilter`, `File::makeSafe`, `Folder::delete` sur lien symbolique à confirmer). Les avis de sécurité des bibliothèques JavaScript n'ont pas pu être consultés (pas d'accès à une base d'avis) : je le signale à chaque occurrence.

Échelle de confiance : **confirmé statiquement** = chemin complet lu dans le code, conditions explicites ; **probable** = chemin observé mais dépendant d'une configuration ou d'un comportement externe ; **hypothétique** = possible sans démonstration. Aucune exploitabilité n'est affirmée.

## 3. Statut des 11 fiches « non revérifiées »

| Fiche | Statut | Preuve (code courant) |
|---|---|---|
| F-10 identifiant d'élément étranger (QuickMode) | **présent** (confirmé statiquement) | `administrator/.../src/Model/QuickmodeModel.php:829` : `WHERE id = :dbId` seul, sans borne `form`. Le « fix des identifiants copiés » (`:795-808`) ne remplace `dbId` que si un élément du formulaire cible porte le même nom. Voir SEC-R2-002. |
| F-18 validation générique côté serveur | **présent** (confirmé statiquement, impact limité) | Seule `WidgetValueValidator` est appelée (`components/.../Submission/SubmissionEngine.php:185`). Aucune validation de champ requis ni de liste fermée côté serveur. Intégrité des données plutôt que sécurité ; voir SEC-R2-012. |
| F-25 copie de menus sans `core.create` | **corrigé** | `administrator/.../Controller/MenusController.php:44` : `authorise('core.create')` avant `copy()`, jeton vérifié en `:41`. |
| F-27 fichier référencé par URL omis de l'effacement privacy | **présent** (confirmé statiquement) | `SubmissionFileGuard::locate()` (`administrator/.../Service/SubmissionFileGuard.php:~92-100`) renvoie `null` pour une valeur `scheme://`, et `plan()` ne place alors le fichier ni dans « remove » ni dans « leave » : il n'est pas journalisé. `UploadStorage.php:~78-84` produit ces URL quand `useUrl` est actif. Gravité faible (option `useUrl` rare). |
| F-28 conversion des dates interrompue | **corrigé** | `script.php:3662-3690` + `Service/RecordModifiedDateConverter.php` : marqueur durable `pending`/`done`, transaction atomique. |
| F-29 Query List : valeurs dans le HTML | **présent / non confirmé** | Non retraité ligne à ligne : `ClassicQueryListCellBuilder.php:86` échappe la valeur de l'input (`$escapedValue`) mais `QueryListPageScriptBuilder.php` utilise `innerHTML` ; les valeurs proviennent d'un code PHP administrateur (`execQuery`). Confiance : hypothétique. |
| F-31 historique supprimé à la désinstallation | **présent** (confirmé statiquement) | `script.php:4381` → `unregisterContentTypes()` (`:1819-1840`) : `DELETE FROM #__history WHERE item_id LIKE 'com_breezingformsng.<type>.%'`. Perte de versions à la désinstallation ; à documenter ou à subordonner à une confirmation. |
| F-32 réécriture large à la migration | **présent** (confirmé statiquement) | `script.php:725` et `:778` : `preg_replace('/com_breezingforms(?!ng)/', ...)` sur les champs de code, de contenu libre et les arbres de formulaire. Rejouable (idempotent) mais non restreinte au contexte. |
| F-35 dates de packages non reconnues effacées | **présent** (confirmé statiquement) | `script.php:~3715-3750` : `normaliseSqlDate()` (`:4023`) renvoie `null` pour tout format autre que `Y-m-d H:i:s`/`Y-m-d`, la valeur devient `NULL` sans journal. Perte de métadonnées de date uniquement. |
| F-36 erreur de lecture de configuration = table absente | **présent** (confirmé statiquement) | `administrator/.../Model/ConfigModel.php:55-58` : toute `RuntimeException` donne `[]`. Repli sur les valeurs par défaut sans signalement. Gravité faible. |
| F-38 exception SQL affichée | **corrigé** | `components/.../Integration/IntegratorRuntime.php:~140-146` affiche `Text::_('JERROR_AN_ERROR_HAS_OCCURRED')`, plus le message de la base. Une sortie `echo` en pleine requête subsiste (voir SEC-R2-010). |

Fiche supplémentaire relue : **F-19** (restauration de version avec code) reste présente, voir SEC-R2-005.

## 4. Nouveaux constats

### SEC-R2-001 · Correctif `OptLinkPolicy` : tous les vrais jetons d'opt-in/opt-out sont refusés
- **Catégorie** : régression d'un correctif de sécurité, consentement (RGPD), CWE-1295 / logique.
- **Gravité** : moyenne (fonctionnel + conformité). **Confiance** : confirmé statiquement (jeton calculé avec `php -r`).
- **Preuves** :
  - Génération : `components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php:1425-1426` : `$token = randomString(20)` (alphanumérique) puis `$optToken = bf_b64enc($token)` stocké en base ; liens e-mail `:1436-1439` : `token=' . bf_b64enc($token)`.
  - `bf_b64enc` est `base64_encode` (`components/.../breezingformsng.php:26-31`). Un jeton de 20 octets donne 28 caractères avec un `=` final : `php -r 'echo base64_encode("abcdefghij0123456789");'` produit `YWJjZGVmZ2hpajAxMjM0NTY3ODk=` ; `preg_match('/^[A-Za-z0-9]{16,64}$/', ...)` renvoie `0`.
  - Politique : `components/.../Callback/OptLinkPolicy.php:27` : `'/^[A-Za-z0-9]{16,64}$/'` ; commentaire « 20 letters and digits ».
  - Usage : `OptCallback.php` (`optIn()` et `optOut()`) répond « ressource introuvable » dès que `isUsable()` est faux, avant toute confirmation.
  - Test : `tests/Site/Service/Callback/OptLinkPolicyTest.php:36` utilise `aB3dE6gH9jK2mN5pQ8sT` « as the e-mail carries it » : ce n'est pas la forme émise. Le test E2E `tests/E2E/db.php:314-316` insère un jeton arbitraire sans passer par le générateur.
- **Conséquences** : aucun lien de double opt-in reçu depuis l'installation de ce correctif ne peut être confirmé ; surtout, le lien de retrait de consentement (opt-out) est inopérant. Les enregistrements en attente restent « non confirmés ».
- **Recommandation** : accepter la forme réellement émise (`'/^[A-Za-z0-9+\/]{16,64}={0,2}$/'`, ou mieux, émettre un jeton alphanumérique sans base64 et migrer les jetons existants) ; ajouter un test qui passe par le vrai générateur (`randomString` puis `bf_b64enc`) et un E2E qui suit le lien d'un e-mail réel.
- **Effort** : S. **Risque de régression** : faible. **Tests** : unitaire avec jeton généré ; E2E complet (soumission, lien, confirmation, retrait).

### SEC-R2-002 · QuickMode : mise à jour d'un élément par `id` sans borne de formulaire (F-10)
- **Catégorie** : référence d'objet directe, intégrité (CWE-639). **Gravité** : moyenne. **Confiance** : confirmé statiquement pour le code ; exploitation non démontrée.
- **Preuves** : `administrator/.../Model/QuickmodeModel.php:774` (`dbId == 0` pour l'insertion), `:795-808` (correction par nom dans le seul formulaire courant), `:821-829` : `UPDATE #__facileforms_elements SET ... WHERE id = :dbId`. Les champs écrits incluent `form = <formulaire courant>` (`elementFields()`), donc un `dbId` appartenant à un autre formulaire est déplacé et réécrit. Le contrôleur n'exige que `core.edit` (`QuickmodeController.php`, `doAjaxSave`).
- **Conséquences** : un éditeur autorisé à modifier le formulaire A peut, en connaissant un identifiant, détourner ou écraser un élément du formulaire B (périmètre ACL ou propriétaire différent si des droits par formulaire sont ajoutés un jour).
- **Recommandation** : `AND form = :form` dans l'`UPDATE` et rejet (ou création) quand aucune ligne n'est touchée ; vérifier de même les `script*id`/`piece*id` référencés.
- **Effort** : S. **Risque de régression** : faible à moyen (copies et migrations de formulaires anciens). **Tests** : deux formulaires, arbre portant l'`dbId` de l'autre, élément de B inchangé.

### SEC-R2-003 · Thème QuickMode : nom stocké utilisé pour inclure `script.php` sans validation à l'exécution
- **Catégorie** : inclusion de fichier local / traversée de chemin (CWE-22, CWE-98). **Gravité** : moyenne. **Confiance** : probable (puits confirmé, exploitation non démontrée).
- **Preuves** : `components/.../Rendering/QuickMode/BootstrapRenderer.php:1122-1139` et `OnePageRenderer.php:383-400` : `$themecss_path = JPATH_SITE . '/media/breezingforms/themes-bootstrap5/' . $this->rootMdata['themebootstrap'] . '/theme.css'`, puis, si ce fichier existe, `require_once` du `script.php` voisin. `rootMdata` vient du JSON du formulaire. À l'enregistrement, seul le **chemin d'écriture** de `vars.txt` est protégé (`QuickmodeModel.php:337-345` + `QuickmodeThemePath::resolve`) ; la valeur `themebootstrap` elle-même est stockée sans contrôle. `ClassicRenderer.php:230-231, 341-345` utilise `themes/ . $rootMdata['theme']` de même (lecture de fichiers).
- **Conséquences** : un éditeur `core.edit` sans droit « code » peut fixer `themebootstrap` à `../../x` et provoquer l'inclusion d'un `script.php` situé là où un `theme.css` existe aussi. L'exécution de code suppose qu'un tel couple de fichiers existe déjà hors du dossier des thèmes ; non démontré. Contourne l'intention de `breezingformsng.code`.
- **Recommandation** : au rendu, n'accepter qu'un nom présent dans la liste des thèmes installés (`getThemesBootstrap()`), ou passer par `QuickmodeThemePath::resolve` ; n'inclure `script.php` que pour un thème livré ; ignorer le thème sinon.
- **Effort** : S. **Risque de régression** : faible. **Tests** : unitaire sur le résolveur ; formulaire avec `themebootstrap = '../x'` : thème ignoré, aucune inclusion.

### SEC-R2-004 · Nom de formulaire inséré dans un chemin de gabarit PHP inclus (mailtpl, pdftpl)
- **Catégorie** : traversée de chemin / inclusion (CWE-22, CWE-98). **Gravité** : faible à moyenne. **Confiance** : hypothétique pour l'exploitation ; confirmé statiquement pour l'absence de validation.
- **Preuves** : `components/.../Notification/MailTemplateResolver.php:24-25` : `$formName . $suffix . '.html.php'` ; `NotificationEngine.php:~190-264, ~928-1007` : `include($tplFile)`. `Export/ExportEngine.php:770` : `pdftpl/ . formrow->name . '_pdf_attachment.php'` puis `require($file)` (`:836`). La colonne `name` est écrite telle quelle par `FormOptionsModel::saveForm()` (`administrator/.../Model/FormOptionsModel.php:220-221, ~255`) avec le seul droit `core.edit`.
- **Conséquences** : un nom du type `../../x/y` peut désigner n'importe quel fichier PHP existant de suffixe `.html.php`, `.txt.php` ou `_pdf_attachment.php` ; l'inclusion s'exécute à chaque soumission. Nécessite un tel fichier déjà présent.
- **Recommandation** : imposer `^[A-Za-z0-9_.-]+$` (et refuser `..`) à l'enregistrement du nom et dans les résolveurs ; comparer `realpath` au dossier attendu.
- **Effort** : S. **Risque de régression** : faible (noms atypiques existants à signaler). **Tests** : nom `../a` refusé ; résolveur ne sort pas du dossier.

### SEC-R2-005 · Restauration d'une version : colonnes de code réécrites avec le seul droit `core.edit` (F-19)
- **Catégorie** : ACL, contournement du droit « code » (CWE-862). **Gravité** : moyenne. **Confiance** : confirmé statiquement.
- **Preuves** : `administrator/.../Controller/FormController.php:53-56` (seul `core.edit`), `:83` `restoreOptions($formId, $version['columns'])` ; `Service/FormVersioning.php:42-46` (`KEPT_COLUMNS` n'exclut que `template_*`, `id`, etc.) et `:194-212` écrivent `script1code`, `script2code`, `piece1code` à `piece4code`, `piece*cond`, `piece*id`. `FormCodeGuard` n'est appelé que dans `FormOptionsModel` et `PackageTransferModel` (grep).
- **Conséquences** : un éditeur sans droit « code » peut remettre en place un code ancien (par exemple celui qu'un administrateur a retiré après un incident). Il ne peut pas écrire du code nouveau par ce chemin.
- **Recommandation** : appliquer `FormCodeGuard::apply()` dans `restoreOptions()` (ou ignorer les colonnes de code sans le droit) et signaler les colonnes ignorées.
- **Effort** : S. **Risque de régression** : faible. **Tests** : restauration sans le droit « code » : colonnes de code inchangées, message affiché.

### SEC-R2-006 · Captcha image : espace de codes réduit, ré-émission illimitée, pas de limitation de débit
- **Catégorie** : anti-automatisation (CWE-804 / CWE-307). **Gravité** : faible à moyenne. **Confiance** : probable pour la faiblesse ; contournement non démontré.
- **Preuves** : `Security/CaptchaImage.php:27-28` : 18 caractères, longueur 5 (18^5 ≈ 1,9 million), distorsion légère et 2 lignes (`:38-45`), documenté « lisible d'un coup d'œil ». `Security/CaptchaCodeStore.php:30, 79-83` : 5 essais par code, mais `CaptchaCallback::image()` (`:44-52`) émet un nouveau code et remet le compteur à zéro à chaque appel, sans limite. `check()` (`:30-39`) sert d'oracle non consommant, également illimité. Chaque appel à `image()` génère un JPEG (coût CPU) sans limitation.
- **Conséquences** : un robot peut tenter des codes (5 essais par image) ou résoudre l'image par OCR ; l'endpoint d'image peut servir à consommer du CPU. L'effet réel dépend du modèle de menace (robots simples ou ciblés).
- **Recommandation** : limiter la fréquence des ré-émissions par session/IP ; allonger le code ou activer reCAPTCHA/hCaptcha/Turnstile pour les formulaires sensibles ; documenter la limite.
- **Effort** : M. **Risque de régression** : faible. **Tests** : fonctionnel (N demandes d'image en rafale) et unitaire sur le compteur.

### SEC-R2-007 · IPN PayPal : enregistrement non atomique (course entre deux notifications)
- **Catégorie** : intégrité des paiements (CWE-362). **Gravité** : faible. **Confiance** : confirmé statiquement ; non reproduit.
- **Preuves** : `Callback/PayPalCallback.php:~98-125` : test `$record->paypal_tx_id === ''` puis `storeTransaction()` (`PaymentRecordService.php:52-76`, `UPDATE` sans condition sur `paypal_tx_id`), alors que le chemin Stripe utilise `storeTransactionOnce()` (`:82-101`). Deux notifications simultanées (PayPal réémet) peuvent toutes deux passer le test et lancer `bf_paypalipn_success.php` et les notifications deux fois.
- **Conséquences** : scripts de succès et e-mails dupliqués ; pas de contournement du montant ni du destinataire.
- **Recommandation** : utiliser `storeTransactionOnce()` et ne lancer le script/les e-mails que si elle renvoie vrai.
- **Effort** : S. **Risque de régression** : faible. **Tests** : deux notifications concurrentes, un seul déclenchement.
- Relecture de `PayPalPayment::isReceiver` (`Submission/PayPalPayment.php:169-179`) : comparaison insensible à la casse avec `receiver_email` ou `receiver_id`, ferme sur `business` vide. Aucun contournement trouvé ; la comparaison `mc_gross`/devise/`custom` signée reste en place (`PayPalCallback.php:104-112`).

### SEC-R2-008 · Téléchargement payant : aucun dossier de base ; tout fichier lisible sans extension dangereuse est servable
- **Catégorie** : lecture arbitraire de fichier par un éditeur (CWE-22/73). **Gravité** : faible à moyenne (éditeur `core.edit`). **Confiance** : confirmé statiquement pour la politique ; accès à l'option non retesté.
- **Preuves** : `Callback/PaymentDownloadService.php:171-176` : `realpath($options['filepath'])` puis `PaymentDownloadFilePolicy::isServable()` ; la politique (`PaymentDownloadFilePolicy.php:27-42`) refuse les noms cachés et les extensions exécutables, mais `UploadExtensionPolicy::isAccepted($name, [])` accepte les noms **sans extension** (`/etc/passwd`, journaux), `.sql`, `.zip`, `.ini`, `.log`, `.env.bak`, etc. Aucune racine autorisée. Le champ `filepath` est défini par QuickMode (`QuickmodeModel.php`, options Stripe/PayPal) avec `core.edit`. (Un travail est en cours dans l'arbre de travail sur ce champ.)
- **Conséquences** : un éditeur qui a aussi acheté (ou obtenu) un téléchargement valide peut faire servir un fichier hors site lisible par PHP. Le statut « corrigé en partie » du ROADMAP reste exact.
- **Recommandation** : imposer un dossier de base (par exemple `media/breezingforms/downloads/`), comparer `realpath` à ce dossier, ou réserver le champ au droit « code/configuration ».
- **Effort** : S. **Risque de régression** : moyen (chemins existants). **Tests** : `/etc/hostname` refusé ; fichier du dossier autorisé servi.

### SEC-R2-009 · Signatures stockées dans un dossier public sans protection
- **Catégorie** : exposition de données personnelles (CWE-200/552). **Gravité** : faible à moyenne. **Confiance** : probable (dépend de la configuration du serveur web).
- **Preuves** : `Submission/SubmissionEngine.php:885-902` : dossier `media/breezingforms/signatures/` créé avec un `index.html` vide, sans `.htaccess` ni `UploadDirectoryProtection` ; nom de fichier `<champ>-<md5(valeur base64)>.png`. Les PDF/e-mails référencent ce dossier par chemin disque (`pdftpl/export_pdf.php:173`).
- **Conséquences** : les images de signature sont accessibles par URL directe pour qui connaît ou déduit le nom (pas d'énumération par défaut grâce à `index.html`). L'URL n'est pas secrète au sens fort (md5 du contenu).
- **Recommandation** : appliquer `UploadDirectoryProtection::ensureWithinWebRoot()` à ce dossier et servir les signatures par un endpoint authentifié, ou nommer par jeton aléatoire.
- **Effort** : S. **Risque de régression** : moyen (liens existants dans des e-mails). **Tests** : accès direct refusé, PDF/e-mail toujours générés.

### SEC-R2-010 · Intégrateur : jokers `LIKE` de la valeur visiteur non échappés ; exceptions avalées
- **Catégorie** : injection de motif (CWE-943), gestion d'erreurs. **Gravité** : faible. **Confiance** : hypothétique (dépend d'une règle « mise à jour » configurée avec opérateur `%...%`).
- **Preuves** : `Integration/IntegratorRuntime.php:412-422` : `'%' . $value . '%'` avec `$value` issu du formulaire (`:446`), `%` et `_` non échappés ; la liste blanche des opérateurs (`IntegratorCriteriaPolicy`) est bien appliquée. `:369-382` : `catch (\Throwable $e) {}` vide autour de l'`UPDATE`/`INSERT` ; `:140-145` : `echo` d'un message d'erreur pendant la soumission.
- **Conséquences** : un visiteur peut saisir `%` pour élargir la condition d'une règle `UPDATE` et modifier toutes les lignes de la table de référence si la règle est ainsi conçue ; échecs silencieux sans trace.
- **Recommandation** : échapper `%`, `_` et `\` dans `$value` pour les opérateurs LIKE ; journaliser les échecs sans donnée soumise.
- **Effort** : S. **Risque de régression** : faible. **Tests** : valeur `%` ne correspond qu'à un `%` littéral.

### SEC-R2-011 · Dropbox : erreur non interceptée sur le chemin d'upload ; jeton stocké dans `dropbox_email`
- **Catégorie** : gestion d'erreurs, secrets (CWE-209, CWE-312). **Gravité** : faible. **Confiance** : probable.
- **Preuves** : `Submission/SubmissionEngine.php:642-644` : `uploadFileToDropbox()` sans `try/catch` (contrairement à `:1316-1340`) ; `Integration/DropboxUploader.php:~50` : `RuntimeException('Dropbox API error: ' . $result->getBody())`. Le jeton d'accès Dropbox est lu dans la colonne `dropbox_email` (`SubmissionEngine.php:2306`), en clair (déjà noté SEC-012).
- **Conséquences** : une panne ou un jeton révoqué interrompt la soumission avec un message contenant le corps de la réponse Dropbox ; le détail n'est pas un secret mais n'a pas lieu d'être affiché au visiteur.
- **Recommandation** : intercepter, journaliser un message générique, poursuivre ; renommer la colonne (migration) ou la chiffrer.
- **Effort** : S. **Risque de régression** : faible.

### SEC-R2-012 · Validation serveur absente pour les champs requis (F-18) et extensions : lacunes résiduelles
- **Catégorie** : validation d'entrée (CWE-20). **Gravité** : faible. **Confiance** : confirmé statiquement pour l'absence ; impact limité.
- **Preuves** : voir F-18 ci-dessus ; `Upload/UploadExtensionPolicy.php:61-87` : liste fermée de 23 extensions exécutables ; sans liste blanche de champ, tout autre fichier est accepté (HTML, SVG, XML, JS, `web.config`, `.user.ini`). Le dernier segment n'est pas passé par `trim` : `shell.php ` (espace final) n'est pas reconnu comme `php` (dangereux sous Windows/NTFS qui retire l'espace). `bf_sanitizeFilename` (`libraries/crosstec/functions/helpers.php:26-67`) conserve l'extension brute en minuscules sans la nettoyer.
- **Conséquences** : stockage de fichiers de contenu actif si le dossier est servi (nginx, IIS) ; sur Apache, le `.htaccess` de `UploadDirectoryProtection` interdit l'accès.
- **Recommandation** : refuser par défaut HTML/SVG/JS/XML sans liste blanche explicite, `trim` de l'extension, refuser les extensions contenant des espaces ou points terminaux ; documenter la règle nginx.
- **Effort** : S. **Risque de régression** : moyen (formulaires acceptant tout). **Tests** : `a.php `, `a.svg`, `web.config`.

### SEC-R2-013 · Installateur : suppression récursive de `components/com_breezingformsng/uploads`
- **Catégorie** : perte de données (CWE-459). **Gravité** : faible. **Confiance** : hypothétique.
- **Preuves** : `script.php:3583` (liste) et `:3601` : `Folder::delete()` récursif de `JPATH_SITE/components/com_breezingformsng/uploads` à chaque mise à jour, dans la liste « dossiers obsolètes ». Les versions précédentes y stockaient des fragments (`tests/Site/Service/Upload/TemporaryUploadDirectoryTest.php:30-50`). Un site dont un champ d'upload pointerait ce dossier perdrait les fichiers déposés. Contraire à la règle d'`AGENTS.md` (chemins exacts, protection des données utilisateur) pour ce dossier précis. Les autres suppressions sont des chemins exacts, plus un nettoyage de dossiers du composant historique déjà noté (SEC-017). `removeObsoleteCaptchaLibrary()` gère correctement les liens symboliques.
- **Recommandation** : supprimer seulement les fichiers `*_chunktmp`/`*_flashtmp` de ce dossier, ou ne supprimer que s'il est vide ; journaliser.
- **Effort** : S. **Risque de régression** : faible.

### SEC-R2-014 · Code mort avec sortie JavaScript non échappée
- **Catégorie** : XSS potentielle (CWE-79) dans du code non appelé. **Gravité** : faible. **Confiance** : hypothétique (aucun appelant trouvé).
- **Preuves** : `administrator/.../libraries/crosstec/functions/helpers.php:215-216, 245-246, 263-266` : noms d'éléments (`$row`) concaténés sans échappement dans `href="javascript: ...('{$row:label}')"` (`bf_getFieldSelectorList`, `...Editor`, `...HTML`). Recherche dans `administrator/components` et `components` : aucun appel.
- **Recommandation** : supprimer ces trois fonctions (ou `json_encode` + `htmlspecialchars`). Le fichier est en cours de modification dans l'arbre de travail.
- **Effort** : S. **Risque de régression** : nul si aucun appelant tiers (à vérifier pour le code utilisateur de sites).

### SEC-R2-015 · Bibliothèques embarquées : versions anciennes non vérifiables
- **Catégorie** : composants tiers (CWE-1104). **Gravité** : à déterminer. **Confiance** : hypothétique (avis non consultés).
- **Relevé** : Plupload 3.1.2 (daté 2018-02-20, licence AGPLv3 : à examiner pour la compatibilité de licence avec GPL), `moxie.js` de la même série, pickadate 3.6.4 (2019-05-25, projet non maintenu), Remodal 1.1.1 (2017), SweetAlert2 11.26.25, jsTree 3.3.17 (2024-09-03), intl-tel-input 29.5.11 ; PHP : phpoffice/phpspreadsheet 5.11.0, stripe-php 22.1.0, tc-lib-pdf 8.84.0, gregwar/captcha 2.1.1, symfony/finder 7.4.23. Aucun avis connu de ma mémoire sur Plupload 3.1.2, pickadate 3.6.4 ou Remodal 1.1.1, mais je ne peux pas le garantir sans base d'avis. `composer audit --locked` (PHP) : aucun avis. `npm audit` : non exécutable (npm absent).
- **Recommandation** : remplacer Plupload/pickadate (anciens, non maintenus) ou les isoler ; consulter OSV/GitHub Advisories pour chaque version ; publier l'inventaire dans un SBOM.
- **Effort** : M.

## 5. Zones sans problème significatif observé

- **SQL** : toutes les requêtes parcourues (administrateur, composant, `libraries/crosstec`, plugins, `api`) utilisent `createQuery()`, `bind()`, `quote()` sur des valeurs et `quoteName()` sur les identifiants. Les ordres de tri sont en liste blanche (`RecordsModel.php:221-222`, `self::SORTS`) ; les `$id`/`$formId` concaténés sont des entiers typés (`RecordImporter.php:63`, `PackageTransferModel.php:1440, 1613`). Les colonnes de `FormVersioning::restoreOptions()` sont filtrées par `getTableColumns()` puis `quoteName()` (la question est l'ACL, SEC-R2-005, pas l'injection). Seuls points d'attention : jokers `LIKE` (SEC-R2-010).
- **XSS des gabarits d'administration** : les valeurs système de `tmpl/record/edit.php` passent par l'assistant `$value` ; `tmpl/script/test.php:23-28` échappe `auditCreatedBy` ; `statistics.php` caste en `int`. Aucune sortie brute relevée dans `tmpl/` et `layouts/` (échantillon systématique par `grep`, pas une preuve d'absence).
- **XSS côté visiteur** : les valeurs soumises ne sont pas réaffichées en HTML hors e-mails (SEC-011 ouvert) ; PDF : `htmlentities` sur toutes les valeurs (`pdftpl/export_pdf.php:90-174`, `pdf_attachment.php`) ; le texte de confirmation opt-in est échappé (`OptConfirmationPage.php:37-48`) ; les paramètres d'URL JavaScript passent par `json_encode` (F-21). Le contenu de formulaire écrit par un éditeur (libellés, valeurs par défaut, JavaScript des éléments) est rendu en HTML par conception : risque d'éditeur à éditeur, pas de visiteur.
- **Redirections** : toutes les cibles proviennent de la configuration du formulaire (`thankYouPage`, `force_url`) ou de Joomla ; `return` passe par `Uri::isInternal()` (`SubmissionEngine.php:1965-1970`) après décodage base64 ; je n'ai pas relu les sources de Joomla 6.2 pour cette fonction (comportement à confirmer).
- **SSRF** : hôtes fixes (`api.paypal.com`, `content.dropboxapi.com`, `login.salesforce.com`, `google.com`) ; Mailchimp : suffixe de datacenter validé `^[a-z0-9]+$` (`MailchimpClient.php:34-37`, SEC-016 corrigé) ; Salesforce n'envoie le jeton qu'à l'hôte de `serverUrl` renvoyé par le login sur TLS (`SalesforceClient.php:~125`). Aucun `curl`/`file_get_contents` sur URL fournie par un visiteur.
- **Stripe** : webhook signé (`StripeCallback.php:~245-258`, tolérance de 5 minutes par défaut de `Webhook::constructEvent`), enregistrement idempotent (`storeTransactionOnce`), montant calculé côté serveur et choix `bfPaymentSelect` validé (`SubmissionEngine.php:2280-2297`).
- **PayPal IPN** : validation `VERIFIED`, `custom` signé, `payment_status = Completed`, montant, devise et destinataire (SEC-003 corrigé).
- **MCP/API** : authentification par jeton API Joomla (`McpController.php:48-62`), `core.manage` puis droit par outil (`allows()` de chaque outil), origine comparée schéma, hôte et port (`OriginValidator.php:15-70`), corps limité à 1 Mo, erreurs d'outil génériques (`McpServer.php:~210-218`), journal sans message d'exception (`McpServerFactory.php:75`), annotations « destructive » sur les outils concernés ; les outils n'écrivent pas de PHP (`McpFormWriter` refuse le renommage avec scripts/pièces). Absence de limitation de débit : à traiter au niveau serveur.
- **Journalisation et PII** : les `Log::add` relevés (`SubmissionEngine`, `StripeCallback`, `PayPalCallback`, `IntegratorRuntime`, `NotificationEngine`, `PackageTransferModel`) ne contiennent pas de valeurs soumises (identifiants de formulaire/enregistrement uniquement ; `PackageTransferModel` assainit les caractères de contrôle). `Stripe Checkout: ` consigne le message de l'API Stripe (clé masquée par Stripe). Les journaux `BF Piece` ne s'écrivent qu'en mode debug.
- **Suppression et effacement** : `RecordModel` applique `core.delete` ; le plugin privacy est borné à `user_id` avec `SubmissionFileGuard` ; les fichiers non effaçables sont journalisés. Les exports (PDF, CSV, XLSX, XML) exigent le jeton CSRF et le dispatcher exige `core.manage`, mais il n'existe pas de droit d'export dédié (information).
- **Captcha** : session, durée de vie 900 s, 5 essais par code, `hash_equals`, consommation à la soumission ; la vérification serveur est pilotée par les éléments du formulaire en base (`SubmissionEngine.php:1147-1180`), pas par un champ posté (limites : SEC-R2-006).
- **Téléversement par fragments** : ticket de session de 32 chiffres hexadécimaux, nom d'élément sans chemin, fichiers temporaires suffixés `_flashtmp`/`_chunktmp`, taille cumulée vérifiée, réponses `text/plain` (`FlashUploadCallback.php`).
- **Dossiers d'upload** : `makeSafeFolder()` retire `.` et `..` et filtre le jeu de caractères (`TokenizedDirectoryResolver.php:97-125`) ; `UploadDirectoryProtection` écrit `.htaccess` et `index.html` sans écraser ; `SignatureImagePolicy` exige l'en-tête PNG et 1 Mo maximum.
- **`FormCodeGuard`** : relu, pas de contournement trouvé sur `piece*code` et `script*code` pour `FormOptionsModel` (clé absente = inchangée, nouveau formulaire = vide, normalisation CRLF). Restent hors garde : `piece*cond`/`piece*id`/`script*id` (référence à des pièces de bibliothèque existantes) et SEC-R2-005.
- **`UploadExtensionPolicy`** : contrôle chaque fichier, refuse les doubles extensions exécutables (`shell.php.jpg`), les majuscules et les points finaux ; limites SEC-R2-012.
- **Installateur** : suppressions de fichiers par chemins exacts ; pas de `chmod` large dans `script.php` ; le nettoyage du composant historique et SEC-R2-013 sont les seuls points à surveiller.

## 6. Liste priorisée

1. **SEC-R2-001** (S) : corriger `OptLinkPolicy` avant la publication RC23 ; sinon l'opt-in et l'opt-out sont cassés.
2. **SEC-R2-005** (S) : appliquer `FormCodeGuard` à la restauration de version.
3. **SEC-R2-003** et **SEC-R2-004** (S) : valider le nom de thème et le nom de formulaire avant tout chemin inclus.
4. **SEC-R2-002** (S) : borner l'`UPDATE` d'élément par formulaire.
5. **SEC-R2-008** (S) : imposer un dossier de base aux téléchargements payants.
6. **SEC-R2-007** (S) : `storeTransactionOnce` pour l'IPN PayPal.
7. **SEC-R2-009** (S) : protéger le dossier des signatures.
8. **SEC-R2-006** (M) : limitation de débit et option de captcha plus fort.
9. **SEC-R2-012**, **SEC-R2-010**, **SEC-R2-011**, **SEC-R2-013**, **SEC-R2-014** (S) : durcissements faibles.
10. **SEC-R2-015** (M) : inventaire et mise à niveau des bibliothèques JavaScript.

## 7. Points à valider par test (non exécutés)

- Flux e-mail complet du double opt-in avec le vrai jeton (SEC-R2-001).
- ACL : `core.edit` sans droit « code » sur restauration de version, thème traversé, nom de formulaire traversé (SEC-R2-003/004/005).
- Comportement de `Uri::isInternal()` et de `Folder::delete()` (lien symbolique) dans Joomla 6.2 installé.
- Existence réelle de fichiers `*.html.php` ou `script.php` hors des dossiers prévus sur un site type (SEC-R2-003/004).
- Concurrence d'IPN PayPal en sandbox (SEC-R2-007).
