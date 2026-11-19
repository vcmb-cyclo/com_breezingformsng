# Audit sécurité BreezingForms NG : rapport 01

Audit statique en lecture seule du dépôt, branche `main` (commit `1faa98d2`, RC22), réalisé le 2026-11-19. Cible : Joomla 6.2 / PHP 8.4, compatibilité PHP 8.5. Aucun fichier applicatif n'a été modifié ; ce document est le seul livrable.

## 1. Résumé

Le code courant est nettement plus durci que l'état décrit par les 42 fiches F-xx de `docs/security/` : la majorité des contrôleurs d'administration vérifient le jeton CSRF et l'ACL, les requêtes SQL sont liées ou passent par `quoteName()`, l'endpoint MCP/API filtre les secrets et exige les droits Joomla, le plugin de confidentialité est soigné. Aucun point d'injection SQL ni de XSS réfléchi n'a été démontré.

Les constats retenus se concentrent sur trois thèmes :

1. **Le droit `breezingformsng.code` est contournable.** Il est censé protéger tout PHP stocké puis exécuté par `eval` sur le site (`AreaAccess.php:19-20`). Deux chemins l'ignorent : l'enregistrement des options d'un formulaire (SEC-001) et l'import de package (SEC-002). Un compte qui n'a que `core.edit` ou `breezingformsng.configuration` peut donc obtenir une exécution de code PHP.
2. **Le paiement PayPal par IPN n'est pas lié au compte marchand** (SEC-003). Un acheteur peut payer son propre compte PayPal et faire enregistrer la transaction comme payée.
3. **Les téléversements sont insuffisamment contraints côté serveur** : contrôle d'extension limité au premier fichier (SEC-004), valeurs de champs insérées dans le chemin de destination sans filtrage des `..` (SEC-005), écriture avant validation du CAPTCHA (SEC-006), dossier par défaut sous la racine web sans protection (SEC-013).

Décompte : 17 constats, dont 5 de gravité élevée (SEC-005 est de gravité moyenne à élevée), 6 de gravité moyenne et 6 de gravité faible. Statut des 42 fiches antérieures : 10 corrigées, 2 infirmées, 1 atténuée, 18 toujours présentes (reprises dans les constats ci-dessus) et 11 non revérifiées (section 4).

## 2. Méthode et limites

Lectures effectuées : `AGENTS.md`, `docs/best-practices/README.md`, `docs/security/03-findings.md` (titres et statuts), tous les contrôleurs d'administration (matrice jeton/ACL), les points d'entrée publics du site (`EngineDispatcher`, callbacks PayPal/Stripe/captcha/upload/opt, `SubmissionEngine`, `NotificationEngine`, services `Upload/*`), les contrôleurs et outils MCP/API, le plugin de confidentialité, `PackageTransferModel`, `FormOptionsModel`, les clients d'intégration et les parties destructrices de `script.php`.

Commandes exécutées : lecture de fichiers, `grep`, `php -v` (PHP 8.5.4 hôte), `composer audit --locked` sur `administrator/components/com_breezingformsng` (résultat : aucun avis de sécurité).

**Non exécuté** : aucun test HTTP, aucun scénario ACL/CSRF en conditions réelles, aucun paiement sandbox, aucun test d'upload de bout en bout, aucune migration, ni PHPUnit, PHPStan, PHPCS ou Psalm. Le conteneur `joomla6-joomla-1` (PHP 8.3) n'a pas été utilisé. Le code source de Joomla 6.2 installé n'a pas été relu : les comportements de `InputFilter`, `Path::clean` et `File::makeSafe` sont indiqués comme à confirmer là où le constat en dépend. Les bibliothèques JavaScript embarquées (plupload, pickadate, sweetalert2, remodal) n'ont pas été auditées.

Échelle de confiance : **confirmé** = chemin complet démontré dans le code avec conditions explicites ; **probable** = chemin observé mais dépendant d'un comportement externe ou d'une configuration non vérifiée ; **hypothétique** = possible sans démonstration.

## 3. Constats par gravité

### Gravité élevée

#### SEC-001 · Exécution de PHP stocké possible avec le seul droit `core.edit`
- **Catégorie** : ACL, élévation de privilèges, exécution de code (CWE-862, CWE-94).
- **Gravité** : élevée. **Confiance** : confirmé (statique).
- **Preuves** :
  - `administrator/components/com_breezingformsng/src/Controller/FormsController.php:258-261` lit en brut `piece1code` à `piece4code`, `script1code`, `script2code` dans la requête POST.
  - `administrator/components/com_breezingformsng/src/Controller/FormsController.php:275-279` et `:313-330` : `storeOptions()` ne vérifie que `core.edit` ou `core.create` via `assertFormEditPermission()`.
  - `administrator/components/com_breezingformsng/src/Model/FormOptionsModel.php:152` et `:204-209` : `saveForm()` n'exige que `core.edit`/`core.create` puis écrit ces colonnes sans condition.
  - Exécution publique : `components/com_breezingformsng/src/Service/Rendering/FormPieceExecutionService.php:34-36` et `:68` (`piece1cond`/`piece2cond` = 2), `components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php:1238-1246` et `:1489` (`piece3code`/`piece4code`), puis `components/com_breezingformsng/src/Service/Scripting/StoredPhpExecutor.php:34-93` (`eval`).
  - Contraste : les pièces, scripts et règles Integrator sont protégés par `AreaAccess::CODE` (`PackageItemController.php:87-99`, `PackageListController.php:60`, `IntegratorController.php:30`), et l'outil MCP `get_form` refuse le code sans ce droit (`Mcp/Tool/GetFormTool.php:88`).
- **Conséquences** : un utilisateur Joomla (Manager par défaut dans la plupart des installations) autorisé à éditer les formulaires mais non à gérer le code peut écrire du PHP exécuté à chaque affichage ou soumission du formulaire par le serveur. C'est une exécution de code arbitraire sur le site, avec les droits de PHP.
- **Recommandation** : exiger `AreaAccess::CODE` dans `FormOptionsModel::saveForm()` (couche modèle, pour couvrir aussi tout futur appelant) dès qu'une des colonnes `piece*cond`, `piece*code`, `piece*id`, `script*code` est modifiée par rapport à la valeur stockée ; ignorer silencieusement ou refuser ces champs sinon, et masquer les zones de saisie correspondantes dans l'interface pour les utilisateurs sans ce droit. Appliquer la même règle à la restauration d'une version de formulaire (voir F-19) et aux champs de code des éléments QuickMode (`validationCode`, `initCode`, `actionCode`, `submittedScriptCode`, qui sont du JavaScript servi aux visiteurs).
- **Effort** : M. **Risque de régression** : moyen (des gestionnaires de formulaires existants perdraient la modification de code ; prévoir un message clair et une note de version).
- **Tests nécessaires** : test fonctionnel ACL (utilisateur `core.edit` sans `breezingformsng.code` : POST des options avec `piece3code` refusé, colonne inchangée ; avec le droit : accepté) ; test sur la restauration de version ; test unitaire du filtrage dans le modèle.

#### SEC-002 · Import de package : code PHP/JS écrit avec le seul droit `breezingformsng.configuration`
- **Catégorie** : ACL, élévation de privilèges (CWE-862). Reprend et confirme F-13.
- **Gravité** : élevée. **Confiance** : confirmé (statique).
- **Preuves** :
  - `administrator/components/com_breezingformsng/src/Controller/PackagesController.php:205-233` : l'import exige `AreaAccess::CONFIGURATION` (`assertAuthorised()`, `:451-455`), puis transmet `AreaAccess::isAllowed($user, AreaAccess::CODE)` comme seul argument `allowIntegrator`.
  - `administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php:359-369` : seuls `integrator` et `templates` sont retirés quand les droits manquent.
  - Le même fichier écrit sans condition les bibliothèques `scripts` et `pieces` (`:381-382`, `importLibraries()` `:1296-1347`, colonnes `code` et `unit_tests` à `:31`) et les formulaires avec toutes leurs colonnes de table, y compris `piece1code` à `piece4code` (`prepareFormData()` `:1516-1534`, qui ne filtre que `id`, `created*`, `modified*`, `version`).
  - Le formulaire importé référence ces pièces ou code (`remapReferences()` `:1541-1551`) et les exécute via la chaîne décrite en SEC-001.
- **Conséquences** : un compte « configuration » sans droit « code » peut importer un package contenant du PHP exécuté ensuite par le site.
- **Recommandation** : quand `AreaAccess::CODE` manque, retirer du payload les `scripts`, les `pieces` et les colonnes `script*code`/`piece*code`/`piece*cond` des formulaires et des éléments, et le signaler dans le résumé comme pour les modèles. Le même contrôle vaut pour `compareFile()` (`:504-512`) afin que l'aperçu reflète le résultat.
- **Effort** : M. **Risque de régression** : moyen (imports de packages existants qui embarquent du code par un compte sans droit).
- **Tests nécessaires** : import d'un package synthétique contenant pièce, script, `piece3code`, avec et sans `breezingformsng.code` ; vérifier que seules les données hors code sont importées et que les références orphelines sont signalées.

#### SEC-003 · IPN PayPal : paiement non lié au compte marchand du formulaire
- **Catégorie** : paiement, authenticité de la notification (CWE-345). Reprend F-05, toujours présent.
- **Gravité** : élevée. **Confiance** : confirmé (statique) ; la reproduction sandbox n'a pas été faite.
- **Preuves** :
  - Le formulaire PayPal est posté par le navigateur avec `business` et `notify_url` en champs cachés modifiables (`components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php:1833-1838`, `:1882`, `:1896-1910`).
  - `components/com_breezingformsng/src/Service/Callback/PayPalCallback.php:37-135` : l'IPN est validé auprès de PayPal (`VERIFIED`), puis comparé à `custom` signé (HMAC du secret du site, `Submission/PayPalPayment.php:106-129`), au montant, à la devise et à `payment_status = Completed`. Aucun contrôle de `receiver_email`, `receiver_id` ni de `business` n'existe (recherche dans tout `components/` et `src/`). Le point d'entrée IPN ne vérifie pas non plus que `useIpn` est activé pour l'élément.
- **Conséquences** : un acheteur remplace `business` par son propre compte PayPal, paie le montant signé, et PayPal envoie un IPN authentique. Le site enregistre la transaction (`storeTransaction`), déclenche le script `bf_paypalipn_success.php`, les notifications et ouvre le téléchargement payant, sans que le marchand ait été payé. Le retour navigateur (PDT, `PayPalCallback::confirm`) n'est pas concerné : le jeton d'identité PDT est propre au compte marchand.
- **Recommandation** : comparer `receiver_email` (ou `receiver_id`) en minuscules avec `$options['business']`/`$options['testBusiness']` selon le mode, et refuser sinon ; vérifier aussi `txn_type` attendu (`web_accept`). Journaliser le refus sans donnée personnelle.
- **Effort** : S. **Risque de régression** : faible à moyen (marchands dont l'adresse du formulaire diffère de l'adresse principale du compte : documenter l'usage de `receiver_id`).
- **Tests nécessaires** : tests unitaires de la comparaison (casse, espaces, mode test) ; test sandbox avec un IPN dont le destinataire diffère.

#### SEC-004 · Téléversement standard : extension vérifiée seulement pour le premier fichier
- **Catégorie** : téléversement de fichier (CWE-434). Reprend F-09, toujours présent.
- **Gravité** : élevée. **Confiance** : confirmé (statique) ; l'impact dépend de la capacité du serveur à exécuter ou servir le dossier de destination.
- **Preuves** :
  - `components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php:292-309` : la liste d'extensions autorisées (`data2`) n'est contrôlée que si `template_code != ''` et `data2 != ''`, et uniquement sur `name[0]`.
  - `:322-350` : la boucle enregistre ensuite tous les fichiers du tableau (`saveUpload` → `UploadStorage::store`, `Upload/UploadStorage.php:31-71`) sans nouvelle vérification d'extension. Le champ `ff_nm_<nom>[]` accepte plusieurs fichiers quel que soit l'attribut HTML `multiple` (`Upload/UploadedFilesColumns.php:35-56`).
  - `administrator/components/com_breezingformsng/libraries/crosstec/functions/helpers.php:25-65` : `bf_sanitizeFilename()` conserve l'extension en minuscules, sans liste de refus. Aucune liste d'extensions dangereuses (`php`, `phtml`, `phar`…) n'existe dans le code (recherche vide).
  - Le chemin « Flash » contrôle bien chaque fichier (`SubmissionEngine.php:548-563`), ce qui montre que l'omission est involontaire.
- **Conséquences** : un visiteur envoie un premier fichier conforme puis un second fichier d'extension arbitraire, qui est déposé dans le dossier configuré. Si ce dossier est servi avec exécution PHP, c'est une exécution de code à distance non authentifiée. Sinon, c'est le dépôt de contenu actif (HTML/SVG) sur le domaine du site.
- **Recommandation** : valider l'extension de chaque fichier dans une boucle unique, côté serveur, avant tout `move_uploaded_file` ; appliquer une liste de refus non contournable (extensions exécutables PHP et configuration serveur) même quand `data2` est vide, et refuser le fichier sans extension ; comparer l'extension du nom final (après `bf_sanitizeFilename`), pas celle du nom d'origine.
- **Effort** : M. **Risque de régression** : moyen (formulaires qui acceptent aujourd'hui toute extension).
- **Tests nécessaires** : test unitaire de la validation sur plusieurs fichiers (`[ok.pdf, shell.php]`, `x.PHP`, `x.php.jpg`, sans extension) ; test E2E avec envoi multiple.

#### SEC-005 · Chemin d'upload construit avec des valeurs de champs sans filtrage des `..`
- **Catégorie** : traversée de répertoire (CWE-22). Reprend F-40, toujours présent.
- **Gravité** : moyenne à élevée. **Confiance** : probable (dépend d'une configuration de dossier et du comportement de `Path::clean` de Joomla, non relu).
- **Preuves** :
  - `components/com_breezingformsng/src/Service/Upload/TokenizedDirectoryResolver.php:56-89` : pour un chemin contenant `|`, les jetons `{champ:value}` sont remplacés par la valeur postée (nettoyée par `InputFilter` mode HTML, `:56-66`), puis `makeSafeFolder()` (`:94-97`) conserve `.`, `/` et `-`, et `Folder::create($directory)` crée le dossier (`:89`).
  - `SubmissionEngine.php:327` et `:423` appellent ce résolveur avant `saveUpload`.
- **Conséquences** : une valeur `../../x` d'un champ référencé par le chemin de destination peut déplacer le fichier et créer des dossiers hors de la racine d'upload prévue. Combiné à SEC-004, l'écriture peut atterrir dans un dossier exécutable.
- **Recommandation** : après remplacement, rejeter toute valeur contenant `..`, `/`, `\` ou un octet nul ; normaliser le chemin résultant avec `realpath` du dossier parent et vérifier qu'il reste sous la racine d'upload configurée.
- **Effort** : S. **Risque de régression** : faible (valeurs légitimes avec `/` dans un champ utilisé comme sous-dossier : à documenter).
- **Tests nécessaires** : tests unitaires de `TokenizedDirectoryResolver` avec `..`, `/`, valeur vide ; test d'intégration sur le dossier créé.

### Gravité moyenne

#### SEC-006 · Fichiers et signatures écrits avant la validation du CAPTCHA
- **Catégorie** : abus de ressources, ordre des contrôles (CWE-400, CWE-367). Reprend F-16 et F-37, toujours présents.
- **Gravité** : moyenne. **Confiance** : confirmé.
- **Preuves** : `SubmissionEngine.php:1140` appelle `collectSubmitdata()` (envois de fichiers `:322-350`, signatures écrites dans `media/breezingforms/signatures/` à `:893-894`) ; la vérification du code image n'intervient qu'à `:1143-1175` (`CaptchaCodeStore::matches` à `:1146`). Un échec du captcha ne supprime pas les fichiers déjà déposés. Le contenu d'une signature est décodé et écrit tel quel (`bf_b64dec`, `:892-894`) sans vérifier qu'il s'agit d'un PNG, et sans plafond de taille ou de nombre.
- **Conséquences** : un robot peut remplir le dossier d'upload et le dossier des signatures sans résoudre le captcha, et déposer du contenu arbitraire sous un nom `.png` servi publiquement.
- **Recommandation** : vérifier le captcha (image et reCAPTCHA) avant `collectSubmitdata()` ; valider les signatures (signature PNG, taille maximale) ; supprimer les fichiers écrits si la soumission échoue plus loin.
- **Effort** : M. **Risque de régression** : moyen (ordre d'exécution des scripts de pièce « début de soumission »). **Tests** : soumission avec captcha faux et fichier joint : aucun fichier créé.

#### SEC-007 · Téléchargement payant : chemin de fichier serveur non borné
- **Catégorie** : lecture arbitraire de fichier pour un éditeur (CWE-22, CWE-73). Nouveau constat.
- **Gravité** : moyenne. **Confiance** : confirmé (statique), conditionné à un éditeur `core.edit` qui accomplit le flux de paiement.
- **Preuves** : l'option `filepath` de l'élément PayPal/Stripe est stockée telle quelle (`administrator/components/com_breezingformsng/src/Model/QuickmodeModel.php:391`, `:412`), puis servie par `components/com_breezingformsng/src/Service/Callback/PaymentDownloadService.php:154-164` après un simple `is_file()`, via `Support/DownloadHelper.php:20-52`. Aucun dossier de base n'est imposé.
- **Conséquences** : un utilisateur `core.edit` qui n'a pas d'accès au système de fichiers peut régler un chemin tel que le `configuration.php` de Joomla, effectuer un paiement minimal (ou exploiter SEC-003) et télécharger le fichier, donc obtenir les identifiants de base de données et le secret du site.
- **Recommandation** : imposer un dossier de téléchargement dédié (configurable, hors racine web) ; refuser tout chemin dont le `realpath` n'est pas sous ce dossier ; n'autoriser la modification de ce champ que sous `breezingformsng.code` ou `core.admin`.
- **Effort** : S. **Risque de régression** : moyen (chemins absolus existants). **Tests** : chemin `../../configuration.php`, lien symbolique, chemin valide.

#### SEC-008 · Double opt-in : changement d'état par GET, et jeton vide accepté
- **Catégorie** : CSRF/intégrité du consentement (CWE-352, CWE-841). Reprend F-06, toujours présent.
- **Gravité** : moyenne. **Confiance** : confirmé.
- **Preuves** : `components/com_breezingformsng/src/Service/Callback/OptCallback.php:34-67` (opt-in) et `:69-96` (opt-out) mettent à jour `opted`, `opt_ip`, `opt_date` d'après `id` et `token` lus en GET. La colonne `opt_token` vaut `''` par défaut (`administrator/components/com_breezingformsng/sql/install.mysql.utf8.sql:266`) et n'est renseignée que pour les formulaires en double opt-in (`SubmissionEngine.php:1412-1421`) : la requête `opt_in=true&id=<n>&token=` correspond donc à tout enregistrement sans jeton et `opted = 0`. Le message de remerciement est affiché même quand aucune ligne n'est modifiée. Le jeton généré est correct (`random_int`, 20 caractères, `ExportEngine.php:667-677`).
- **Conséquences** : un tiers peut marquer comme « consentement donné » des enregistrements d'autres formulaires en énumérant les identifiants, et réécrire l'IP et la date du consentement ; un prefetch de client mail confirme ou résilie sans action humaine.
- **Recommandation** : refuser un jeton vide ; ne rien afficher de positif si aucune ligne n'est modifiée ; confirmer par une page intermédiaire avec POST ; comparer le jeton en temps constant ; ne modifier que les enregistrements dont le formulaire a le double opt-in actif.
- **Effort** : S. **Risque de régression** : faible. **Tests** : jeton vide, jeton erroné, rejeu, enregistrement d'un formulaire sans double opt-in.

#### SEC-009 · Cache de notification de paiement : données personnelles sous `media/`, `unserialize` sans classes autorisées
- **Catégorie** : exposition de données, désérialisation (CWE-200, CWE-502). Reprend F-20, toujours présent.
- **Gravité** : moyenne. **Confiance** : confirmé pour le code, probable pour l'exposition (dépend de la configuration du serveur web).
- **Preuves** : `components/com_breezingformsng/src/Service/Notification/NotificationEngine.php:591-615` et `:1219-1265` écrivent dans `media/breezingforms/payment_cache/` un `serialize()` contenant destinataires, sujet, corps du courriel et pièces jointes ; `administrator/components/com_breezingformsng/libraries/crosstec/functions/helpers.php:399-435` le relit avec `unserialize()` sans `allowed_classes`. Les noms sont aléatoires (128 bits, `bin2hex(random_bytes(16))`), ce qui limite la devinette mais pas l'indexation ou le listage de dossier. Aucune protection d'accès du dossier n'est créée (`script.php` et le dépôt ne livrent ni `.htaccess` ni `index.html` pour ce dossier).
- **Conséquences** : les messages en attente de paiement (données saisies par le visiteur) restent jusqu'à 24 h dans un dossier public ; tout moyen d'écrire dans ce dossier devient une surface de désérialisation d'objets.
- **Recommandation** : déplacer le cache hors racine web (dossier temporaire Joomla), le stocker en JSON, et à défaut utiliser `unserialize($data, ['allowed_classes' => false])`.
- **Effort** : M. **Risque de régression** : moyen (cache existant au moment de la mise à jour). **Tests** : écriture/lecture, fichier corrompu, objet sérialisé injecté.

#### SEC-010 · Envoi par morceaux : écriture avant contrôle de taille, pas de plafond global
- **Catégorie** : déni de service par consommation disque (CWE-400, CWE-770). Reprend F-03 et F-26.
- **Gravité** : moyenne à faible. **Confiance** : probable.
- **Preuves** : `components/com_breezingformsng/src/Service/Callback/FlashUploadCallback.php:82-101` : chaque fragment est écrit puis ajouté au fichier final (`FlashChunkAssembler.php:20-46`) avant la vérification de `flashUploaderBytes`, qui ne s'applique que si la limite est strictement positive (`Upload/FlashUploadSizeValidator.php:23-32`) ; le fragment n'est supprimé qu'en cas de succès de l'assemblage. Le point d'entrée exige un ticket de session (`isUploadTarget`, `:122-130`), obtenu en affichant le formulaire, donc accessible à tout visiteur. Le nettoyage des fichiers temporaires a lieu au rendu d'un formulaire après 24 h (`UploadFileCleaner.php:22`, `FormRenderer.php:474-479`).
- **Recommandation** : contrôler la taille cumulée avant l'ajout, appliquer un plafond par ticket et par session, imposer l'ordre et l'unicité des décalages `offset`.
- **Effort** : M. **Risque de régression** : faible. **Tests** : fragments répétés, décalages hors ordre, dépassement.

#### SEC-011 · Courriels HTML personnalisés : contenu HTML du visiteur inséré dans le corps
- **Catégorie** : injection de contenu (CWE-79, CWE-74), usurpation. Nouveau constat.
- **Gravité** : moyenne à faible. **Confiance** : probable (le comportement exact du filtre HTML de Joomla 6.2 n'a pas été relu).
- **Preuves** : chaque valeur de champ est passée à `InputFilter::getInstance([], [], 1, 1)->clean($v, 'html')` (`SubmissionEngine.php:806-815`), c'est-à-dire en mode liste noire avec listes vides, qui ne retire que les balises et attributs dangereux par défaut de Joomla ; les balises `a`, `img`, `form` ne sont donc pas supprimées. Quand `email_custom_html` est activé, la valeur est insérée sans échappement dans le corps HTML (`NotificationEngine.php:388-399`, `:1155-1175`) pour l'administrateur et pour le message de retour envoyé au visiteur.
- **Conséquences** : un visiteur peut insérer des liens ou images dans un courriel envoyé depuis l'adresse du site (hameçonnage). Sans modèle HTML personnalisé, le corps reste en texte brut (`:291`, `:1046`).
- **Recommandation** : échapper les valeurs avec `htmlspecialchars` lorsqu'elles sont insérées dans un modèle HTML, hors champs explicitement marqués HTML (`is_html`), et neutraliser les URL.
- **Effort** : S. **Risque de régression** : moyen (modèles qui comptaient sur le HTML saisi). **Tests** : valeur contenant `<a href>`, `<img>`, retours à la ligne.

### Gravité faible

#### SEC-012 · Secrets conservés en clair et exportés dans les packages
- **Catégorie** : secrets (CWE-312, CWE-200). Reprend F-14 et F-24.
- **Gravité** : faible à moyenne. **Confiance** : confirmé pour le stockage ; probable pour l'étendue de l'export.
- **Preuves** : clés Stripe, jetons PayPal, mots de passe Salesforce/Dropbox et clé Mailchimp sont écrits en clair en base (`QuickmodeModel.php:393-416`, `FormOptionsModel.php:119` et `:210-217`). `PackageTransferModel.php:1009-1017` exporte les lignes de formulaire complètes (aucune occurrence de `secret`, `password` ou `api_key` n'apparaît dans le fichier) pour tout détenteur de `breezingformsng.configuration` (`PackagesController.php:66-95`). L'outil MCP filtre bien ces valeurs (`Mcp/FormCatalog.php:20-23`).
- **Recommandation** : exclure ces colonnes et options de l'export par défaut, ou exiger `core.admin` ; chiffrer au repos avec la clé de Joomla si possible.
- **Effort** : M. **Risque de régression** : moyen (réimport qui perd les secrets : l'indiquer).

#### SEC-013 · Dossier d'upload par défaut sous la racine web, sans protection
- **Catégorie** : exposition de données personnelles (CWE-552). Reprend F-33 en partie.
- **Gravité** : faible à moyenne. **Confiance** : confirmé pour la configuration par défaut, probable pour l'exposition.
- **Preuves** : valeur par défaut `{mospath}/media/breezingforms/uploads` (`administrator/components/com_breezingformsng/config.xml:22`, `Configuration/FormConfiguration.php:43`). `Upload/UploadDirectoryProtection.php:13-19` n'écrit un `.htaccess` que pour l'intégration ContentBuilder, avec la directive Apache 2.2 `deny from all` (ignorée par nginx, dépendante de `mod_access_compat` avec Apache 2.4). Les noms de fichier sont ceux du visiteur après assainissement (`bf_sanitizeFilename`), donc prévisibles.
- **Recommandation** : proposer par défaut un dossier hors racine web ; créer `index.html` et une règle `Require all denied` à la création ; documenter la configuration nginx.
- **Effort** : S. **Risque de régression** : faible.

#### SEC-014 · Libellé de formulaire rendu sans échappement
- **Catégorie** : XSS stocké de niveau administrateur (CWE-79). Reprend F-41, toujours présent.
- **Gravité** : faible. **Confiance** : confirmé.
- **Preuves** : `components/com_breezingformsng/src/Service/FormRenderer.php:390` écrit `$form->title` (ou le titre de menu) dans un `<h1>` sans `htmlspecialchars`. La valeur est saisie par un éditeur `core.edit` (ou créée par l'outil MCP `create_form` avec le droit `core.create` ; la validation de ce titre côté MCP n'a pas été vérifiée).
- **Conséquences** : un éditeur dont le droit se limite aux formulaires peut injecter du script exécuté chez les visiteurs et les administrateurs qui voient la page.
- **Recommandation** : échapper la sortie (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- **Effort** : S. **Risque de régression** : faible (titres contenant du HTML volontaire).

#### SEC-015 · Téléchargement payant non rapproché du formulaire ; décompte non atomique
- **Catégorie** : contrôle d'accès objet (CWE-639). Reprend F-02, toujours présent.
- **Gravité** : faible. **Confiance** : confirmé, impact limité.
- **Preuves** : `PaymentDownloadService.php:83-117` sélectionne l'enregistrement par `id` et `paypal_tx_id` seulement, sans la colonne `form` (alors que `PaymentRecordService::hasTransaction`, `:37-48`, la contrôle ailleurs). Le décompte des essais est lu puis incrémenté en deux requêtes (`:90-152`).
- **Conséquences** : un acheteur qui connaît un identifiant de transaction valide peut l'utiliser avec le formulaire d'un autre produit ; des requêtes simultanées dépassent le nombre d'essais autorisé.
- **Recommandation** : ajouter `form = :formId` et faire l'incrément conditionnel en une requête (`WHERE paypal_download_tries < :max`).
- **Effort** : S. **Risque de régression** : faible.

#### SEC-016 · Mailchimp : suffixe de datacenter non validé (SSRF d'éditeur)
- **Catégorie** : SSRF (CWE-918). Nouveau constat.
- **Gravité** : faible. **Confiance** : hypothétique (conditionnée à un éditeur `core.edit`).
- **Preuves** : `components/com_breezingformsng/src/Service/Integration/MailchimpClient.php:36-43` construit l'hôte `https://<fin de clé>.api.mailchimp.com/...` à partir de la clé saisie, sans valider le suffixe ; l'en-tête `Authorization` contient la clé. Un suffixe contenant `/`, `?` ou `#` change la destination.
- **Recommandation** : exiger `^[a-z0-9]+$` pour le datacenter.
- **Effort** : S. **Risque de régression** : nul.

#### SEC-017 · Installateur : suppression récursive de répertoires du composant historique
- **Catégorie** : perte de données (CWE-459). Reprend F-30 (F-33 atténué).
- **Gravité** : faible. **Confiance** : confirmé (comportement statique).
- **Preuves** : `script.php:2956-2977` supprime récursivement `components/com_breezingforms`, `media/com_breezingforms` et `api/components/com_breezingforms` à l'installation, sans sauvegarde ; les autres suppressions visent des chemins exacts (`:3300-3345`, `:3575-3605`), conformément à `AGENTS.md`.
- **Recommandation** : renommer en `.bak-<date>` ou exiger une confirmation avant la suppression de `media/com_breezingforms`, qui peut contenir des fichiers déposés par des visiteurs.
- **Effort** : S. **Risque de régression** : faible.

## 4. Statut des fiches F-xx antérieures

Légende : **corrigé** = le code décrit n'existe plus ou est neutralisé, avec preuve ; **présent** = toujours exploitable selon la lecture du code ; **non revérifié** = non rouvert dans cet audit, statut de `docs/security/` inchangé.

| Fiche | Statut | Preuve ou renvoi |
|---|---|---|
| F-01 IPN INVALID | corrigé | `PayPalCallback.php:135-143` : journal seulement |
| F-02 téléchargement non corrélé | présent | SEC-015 |
| F-03 upload borné après écriture | présent | SEC-010 |
| F-04 / F-39 formules CSV | corrigé | `CsvCellSanitizer` utilisé dans `ExportEngine.php:1004-1037`, `RecordExporter.php:183` ; export XLSX non revérifié |
| F-05 PayPal non lié au marchand | présent | SEC-003 |
| F-42 retour PayPal non terminé | corrigé | `PayPalCallback.php:100` (IPN) et `:209` (retour) exigent `Completed` |
| F-06 consentement par GET | présent | SEC-008 |
| F-07 montant Stripe | corrigé en grande partie | montant fixé côté serveur à la création de session (`SubmissionEngine.php:1686-1706`), liaison `client_reference_id` (`StripeCallback.php:144`, `StripeCheckoutWebhook.php:24-27`), webhook signé (`StripeCallback.php:248`) ; non testé avec Stripe |
| F-08 sauvegarde QuickMode | infirmé au niveau contrôleur | `QuickmodeController.php:100-108` exige `core.edit`/`core.create` ; voir SEC-001 pour la portée |
| F-09 validation d'extension | présent | SEC-004 |
| F-10 identifiant d'élément étranger | non revérifié | |
| F-11 sélecteur de prix | corrigé | `PayPalPayment::fromSelection` et `SubmissionEngine.php:2216-2285` ; non testé |
| F-12 chemin de thème | corrigé | `Service/QuickmodeThemePath.php:11-38` (liste noire de séparateurs, `realpath`, liens refusés) |
| F-13 import de code | présent | SEC-002 |
| F-14 secrets Stripe | présent | SEC-012 |
| F-15 opérateurs Integrator | corrigé | `IntegratorCriteriaPolicy.php:10-21`, appliqué `IntegratorRuntime.php:401` |
| F-16 upload avant captcha | présent | SEC-006 |
| F-17 suppression de `.htaccess` | infirmé | aucune suppression dans le code ; seule une création existe (`UploadDirectoryProtection.php:13-19`) |
| F-18 validation générique des champs | non revérifié | seule la validation des widgets est vue (`SubmissionEngine.php:166-193`) |
| F-19 restauration de version avec code | présent | conséquence de SEC-001 (non rouvert en détail) |
| F-20 cache de paiement | présent | SEC-009 |
| F-21 paramètres URL dans JavaScript | corrigé | `Scripting/RequestParameterJavascript.php:16-26` (`json_encode` avec drapeaux HEX) |
| F-22 expiration du cache de paiement | présent | purge à 24 h (`UploadFileCleaner.php:22`, `:58-62`), risque d'intégrité (notification perdue pour un paiement tardif) |
| F-23 accès direct aux formulaires publiés | présent | `FormRenderer.php:219`, `:234` : aucun niveau d'accès Joomla ; comportement de conception à décider |
| F-24 export de configuration | présent | SEC-012 |
| F-25 copie de menus | non revérifié | |
| F-26 ordre des fragments | présent | SEC-010 |
| F-27 effacement privacy | non revérifié | le plugin est soigné (`SubmissionFileGuard`, journal des fichiers restants) ; cas des fichiers référencés par URL non rouvert |
| F-28 conversion des dates | non revérifié | |
| F-29 Query List historique | non revérifié | |
| F-30 nettoyage récursif | présent | SEC-017 |
| F-31 historique supprimé à la désinstallation | non revérifié | |
| F-32 réécriture à la migration | non revérifié | |
| F-33 nettoyage de fichiers personnalisés | atténué | chemins exacts uniquement (`script.php:3300-3345`) ; voir SEC-017 |
| F-34 journal Salesforce | corrigé | message générique `NotificationEngine.php:1304`, aucun fichier journal Salesforce trouvé |
| F-35 dates de packages | non revérifié | |
| F-36 erreurs de configuration | non revérifié | |
| F-37 signatures avant captcha | présent | SEC-006 |
| F-38 exception SQL affichée | non revérifié | correctif décrit dans `docs/security/00-etat-actuel.md` |
| F-40 traversée par valeurs de profil | présent | SEC-005 |
| F-41 libellé non échappé | présent | SEC-014 |

## 5. Domaines sans problème significatif observé

- **CSRF des contrôleurs d'administration** : tous les contrôleurs inspectés (`Records`, `Forms`, `Integrator`, `Packages`, `PackageList/Item`, `About`, `Quickmode`, `Recordsimport`, `Recordstransfer`) appellent `checkToken()` avant les actions à effet, y compris pour les réponses AJAX (`RecordsController.php:297-321`, `AboutController.php:734-746`, `FormsController.php:466-478`). La soumission publique est protégée par `Session::checkToken('post')` dans les trois points d'entrée (`Controller/DisplayController.php:27`, `modules/mod_breezingforms/src/Dispatcher/Dispatcher.php:89`, `plugins/breezingforms/src/Extension/Breezingforms.php:157`).
- **ACL des zones sensibles** : pièces, scripts, Integrator, packages et audit exigent `core.manage` puis le droit dédié (`PackageItemController.php:87-99`, `IntegratorController.php:28-33`, `AreaAccess.php`). Les réparations d'orphelins exigent suppression et gestion (`AboutController.php:748-750`).
- **Injection SQL** : les requêtes inspectées utilisent `createQuery()`, `bind()`, `quoteName()` ; les opérateurs de l'Integrator sont en liste blanche. Aucun point d'entrée n'a montré de valeur de requête concaténée.
- **API et MCP** : authentification par jeton Joomla avec droit « API login » (`McpController.php:44-60`), droit `core.manage` puis droit par outil (`McpServer.php:189-196`, `Mcp/Tool/*::allows`), validation d'origine, taille maximale de 1 Mo, secrets et code filtrés (`FormCatalog.php:20-23`), `include_code` soumis à `breezingformsng.code`. Les routes d'audit ne sont pas publiques (`webservices/.../Breezingformsng.php`).
- **Callbacks Stripe** : webhook à signature vérifiée par `Webhook::constructEvent`, enregistrement unique (`storeTransactionOnce`, `PaymentRecordService.php:76-100`), session liée à l'enregistrement.
- **CAPTCHA** : jeton en session, durée de vie 900 s, 5 essais, comparaison par `hash_equals` (`Security/CaptchaCodeStore.php:99-176`).
- **Plugin de confidentialité** : export et effacement bornés à `user_id`, suppression de fichiers protégée par `SubmissionFileGuard`, traces des fichiers restants journalisées.
- **Dépendances PHP** : `composer audit --locked` ne signale aucun avis (exécuté sur l'hôte, PHP 8.5.4).
- **Sorties de l'écran d'enregistrement** : les valeurs système passent par l'assistant `$value` ; aucune sortie brute n'a été relevée dans `tmpl/record/edit.php`.

## 6. Points non vérifiables ou à compléter

- **Reproduction** : SEC-001 à SEC-004 et SEC-007 reposent sur la lecture du code ; une reproduction ACL/HTTP sur instance isolée est à prévoir. SEC-003 demande le bac à sable PayPal.
- **Comportement Joomla 6.2** : `InputFilter` en liste noire (SEC-011), `Path::clean` et `File::makeSafe` (SEC-005), `Folder::delete` sur liens symboliques (SEC-017) sont à confirmer contre les sources installées.
- **Rendu côté client** : le rappel JavaScript qui reçoit `ff_message` après soumission (`FormOnloadScriptBuilder.php:51-57`) est correctement encodé en JSON, mais la manière dont les fonctions de rappel des formulaires insèrent ce message dans le DOM n'a pas été auditée.
- **PDF et XLSX** : la génération PDF (tc-lib-pdf) et l'import/export XLSX (PhpSpreadsheet) n'ont pas été relus ligne à ligne ; l'import XLSX est limité à `core.manage` et `core.create` (`RecordsimportController.php`).
- **Éléments QuickMode** : le champ `data2` d'un élément « Query List » est évalué comme PHP (`Scripting/ScriptingEngine.php:289-300`) ; les chemins qui l'alimentent dans l'éditeur QuickMode n'ont pas été tracés, et `FormCatalog::ELEMENT_COLUMNS` renvoie `data2` sans exiger le droit code (`FormCatalog.php:41`).
- **Conservation** : aucune politique de durée de conservation des enregistrements n'a été trouvée ; les soumissions anonymes (`user_id = 0`) ne sont pas atteignables par les demandes Joomla de confidentialité. Constat fonctionnel à arbitrer.
- **Limitation de débit** : aucune limite de fréquence n'existe sur la soumission publique, le point d'envoi par morceaux ni le contrôle du code image ; à traiter au niveau du serveur ou d'un captcha obligatoire.
- **`HtmlSanitizer`** (`Service/Security/HtmlSanitizer.php:17-40`) est une liste noire de cinq balises ; il n'est appelé que par la façade `removeDangerousHtml` destinée au code personnalisé (`Support/processor_facade.php:423-426`) et ne doit pas être utilisé comme assainisseur.
- **JavaScript tiers embarqué** (plupload, moxie, pickadate, remodal, sweetalert2) : versions et avis non vérifiés.
