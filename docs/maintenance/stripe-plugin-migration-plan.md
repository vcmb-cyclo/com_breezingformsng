# Plan de migration — Stripe vers un plugin Joomla

> Statut : proposition d’architecture, sans modification de code.
>
> Objectif : sortir l’implémentation Stripe du cœur de
> `com_breezingformsng` tout en conservant les formulaires, les éléments
> `bfStripe`, les paiements existants, les téléchargements protégés et les
> URLs de callback.

## Décision générale

La migration doit être progressive. Stripe n’est pas seulement un traitement
« après soumission » comme Salesforce : il intervient dans le rendu de
l’élément, la validation du montant, la création de la session Checkout, le
retour navigateur, le webhook et le téléchargement après paiement.

Le plugin ne doit donc pas être créé en déplaçant simplement
`StripeCallback.php`. Le cœur doit d’abord exposer un contrat de fournisseur de
paiement et déléguer Stripe à ce contrat.

Le périmètre exclut pour l’instant :

- le changement de format de `template_code` ;
- le changement du type historique `bfStripe` ou de la valeur DB `Stripe` ;
- la centralisation des secrets Stripe dans une nouvelle table ;
- la suppression des anciennes routes `confirmStripe`, `stripeWebhook` et
  `stripeDownload` ;
- toute suppression de données ou de code avant la phase de contraction.

## État actuel

### Configuration et interface administrateur

- Le type `bfStripe` est créé par
  `media/com_breezingformsng/js/admin/quickmode-elements.js`.
- Son interface est rendue dans
  `administrator/components/com_breezingformsng/layouts/quickmode/properties_element.php`
  et `advanced_element.php`.
- Les clés, le montant, la devise, les méthodes de paiement, le retour et les
  options de téléchargement sont enregistrés dans les options de l’élément
  par `QuickmodeModel`.
- Les options sont donc attachées à chaque élément Stripe, contrairement aux
  paramètres Salesforce qui sont au niveau du formulaire.

### Exécution site

- `SubmissionEngine` valide l’élément et prépare le paiement.
- `StripeCallback` traite le retour navigateur et le téléchargement.
- `StripeCheckoutWebhook` vérifie les notifications Stripe.
- `EngineDispatcher` sélectionne les callbacks selon les paramètres de
  requête historiques.
- `PaymentRecordService` enregistre et déduplique les paiements.
- `StripeAmount` et `StripePaymentMethods` portent déjà une partie de la
  logique métier isolable.
- La dépendance `stripe/stripe-php` est actuellement déclarée dans le
  `composer.json` du composant administrateur.

### Contrats à préserver

Les éléments existants doivent continuer à fonctionner sans réédition :

- `bfStripe` dans `template_code` ;
- `Stripe` dans les lignes d’éléments historiques ;
- `confirmStripe`, `stripeWebhook` et `stripeDownload` ;
- les identifiants de paiement stockés dans les enregistrements ;
- les chemins de téléchargement et les règles de nombre d’essais ;
- les formulaires utilisant plusieurs éléments de paiement.

## Architecture cible

### Contrats dans le cœur

Introduire dans le composant, sans dépendance Stripe directe :

1. `PaymentProviderInterface` pour les opérations de paiement ;
2. `PaymentProviderRegistry` pour retrouver un fournisseur par type
   (`Stripe`, puis éventuellement `PayPal`) ;
3. un contrat de contexte contenant l’élément, le formulaire, le montant,
   l’identité de session et les données validées ;
4. des résultats typés pour distinguer : paiement créé, paiement confirmé,
   paiement refusé, webhook ignoré et erreur technique ;
5. un point d’entrée commun pour les retours, webhooks et téléchargements.

Le cœur reste responsable de l’ACL, de la session de formulaire, de la
protection CSRF des actions administratives, de la persistance générique et de
la réponse HTTP. Le fournisseur reste responsable de l’API de paiement et de
la validation propre à Stripe.

### Responsabilités du plugin Stripe

Le plugin `breezingforms_addons/stripe` devra fournir :

- son manifeste Joomla et son provider DI ;
- le namespace PSR-4 et ses fichiers de langue ;
- l’adaptateur `stripe-php` ;
- la création de session Checkout ;
- la vérification de signature webhook ;
- la confirmation du paiement ;
- la gestion des erreurs Stripe sans divulgation de secrets ;
- le traitement des téléchargements après confirmation ;
- les tests de contrat du fournisseur.

Le plugin ne doit pas accéder directement à des variables globales du
composant ni écrire directement dans les templates. Il reçoit des contextes
validés et retourne des résultats au cœur.

## Étapes de migration

### Phase 0 — Inventaire et caractérisation

- Capturer le comportement actuel pour un formulaire avec un élément Stripe,
  plusieurs éléments Stripe, un montant fixe et un montant provenant d’un
  champ.
- Couvrir les modes test et production sans effectuer de paiement réel.
- Documenter les schémas réels de `template_code`, des options d’élément et
  des enregistrements de paiement.
- Ajouter ou compléter des tests de caractérisation avant toute extraction.
- Vérifier les signatures Joomla installées et les versions PHP 8.4/8.5.

**Sortie :** aucun comportement non documenté et aucun contrat implicite
restant sur le chemin critique.

### Phase 1 — Contrat et registre, sans changement de comportement

- Ajouter l’interface du fournisseur et le registre dans le cœur.
- Ajouter un adaptateur interne Stripe qui appelle encore les classes
  existantes.
- Injecter le registre via le provider Joomla.
- Faire passer les tests existants par le registre, sans plugin externe.
- Ne pas modifier le format des formulaires ni déplacer les secrets.

**Rollback :** supprimer l’appel au registre et revenir aux services actuels ;
aucune donnée n’est modifiée.

### Phase 2 — Squelette du plugin Stripe

- Créer le manifeste, le provider, les namespaces et les langues du plugin.
- Déclarer la dépendance Stripe dans le périmètre du plugin ou dans une
  dépendance commune explicitement approuvée.
- Implémenter le fournisseur avec les tests de contrat, mais conserver
  l’adaptateur interne activable.
- Vérifier installation vierge, mise à jour et réinstallation idempotente.

**Critère :** le plugin est installable et désactivable sans modifier les
formulaires existants.

### Phase 3 — Double lecture et délégation contrôlée

- Le plugin lit les options historiques du nœud `bfStripe`.
- Le cœur valide la forme runtime avant de transmettre les données au plugin.
- Le registre délègue les nouvelles exécutions au plugin, avec un indicateur
  de diagnostic non sensible.
- Les routes historiques sont conservées et redirigent vers le registre.
- Aucun secret, contenu de formulaire ou payload Stripe complet ne doit être
  écrit dans les logs.

La lecture de l’ancien format est un adaptateur de compatibilité de données,
pas une preuve que `array<string, mixed>` est valide : chaque montant, clé,
devise, URL, identifiant et option doit être validé à l’exécution.

### Phase 4 — Extraction de l’interface QuickMode

- Définir un descripteur d’élément de paiement fourni par le plugin ou par le
  registre.
- Remplacer les branches Stripe codées en dur dans l’éditeur par un point
  d’extension contrôlé.
- Conserver les identifiants DOM historiques et le type `bfStripe` pendant la
  période de migration.
- Charger les assets du plugin avec le Web Asset Manager Joomla.
- Vérifier que les champs secrets ne sont jamais préremplis dans une réponse
  destinée au navigateur si le contrat ne l’exige pas.

Cette phase doit être séparée de l’extraction du backend : l’interface peut
rester dans le cœur temporairement si le point d’extension n’est pas encore
stable.

### Phase 5 — Bascule complète

- Supprimer les appels directs Stripe de `SubmissionEngine`,
  `EngineDispatcher` et des callbacks du cœur uniquement lorsque les tests de
  contrat du plugin couvrent tous les chemins.
- Faire de `PaymentProviderRegistry` l’unique point de sélection.
- Conserver un lecteur de compatibilité pour les anciens `bfStripe`.
- Vérifier qu’un plugin désactivé refuse proprement une nouvelle tentative de
  paiement sans charger de code Stripe ni provoquer de paiement partiel.

### Phase 6 — Contraction différée

Après au moins un cycle de release validé :

- retirer l’implémentation Stripe interne devenue inutilisée ;
- retirer la dépendance Stripe du composant seulement si aucun autre chemin ne
  l’utilise ;
- conserver les routes historiques et leur contrat de réponse ;
- ajouter, si nécessaire, une migration d’installation idempotente pour les
  anciens fichiers devenus obsolètes ;
- ne supprimer aucune colonne ni donnée sans décision séparée et plan de
  rollback.

## Sécurité

- Vérifier ACL et contexte du formulaire pour chaque lecture ou modification
  de configuration.
- Conserver les protections CSRF administrateur.
- Valider côté serveur montant, devise, URL de retour, identifiant d’élément,
  nombre d’essais et chemin de téléchargement.
- Vérifier la signature et le type des webhooks avant toute écriture.
- Rendre la confirmation idempotente : retour navigateur et webhook peuvent
  arriver dans n’importe quel ordre ou plusieurs fois.
- Ne jamais journaliser clé secrète, webhook secret, token, numéro de carte ou
  données complètes de soumission.
- Empêcher qu’un téléchargement soit autorisé par un simple paramètre URL sans
  paiement confirmé et sans vérification de propriété/session.
- Ajouter des tests négatifs pour ACL refusée, CSRF absent, webhook falsifié,
  montant modifié, élément d’un autre formulaire et téléchargement IDOR.

## Validation par phase

Chaque phase doit passer les contrôles correspondant à son périmètre :

- `php -l` sur les fichiers PHP modifiés ;
- PHPCS configuré ;
- PHPStan au niveau courant du projet, sans nouvelle baseline ni
  `@phpstan-ignore` ;
- PHPUnit des renderers, de la soumission, des callbacks, du webhook et des
  téléchargements ;
- tests d’installation vierge, mise à jour et réinstallation ;
- tests fonctionnels avec Joomla 6.2 sans plugins de compatibilité historiques ;
- vérification PHP 8.4 et PHP 8.5 sur l’hôte ;
- tests navigateur QuickMode avec un formulaire existant, un formulaire vide,
  un montant fixe, un montant issu d’un champ, un retour et un webhook simulés.

## Critères d’arrêt et de rollback

La migration s’arrête avant la phase suivante si :

- un ancien formulaire ne peut plus être chargé ou sauvegardé ;
- un paiement peut être confirmé deux fois ;
- un webhook non signé entraîne une écriture ;
- un plugin désactivé provoque une erreur fatale ou une perte de données ;
- un test de sécurité ou de compatibilité échoue ;
- le package installe le composant mais pas le plugin, ou inversement.

Le rollback doit pouvoir restaurer la version précédente du composant et du
plugin sans modification destructive de `template_code`, des éléments ou des
enregistrements de paiement.

## Questions à trancher avant le code

1. Les clés Stripe restent-elles propres à chaque élément ou doivent-elles
   devenir des paramètres communs au formulaire ?
2. Le plugin Stripe doit-il fournir l’UI QuickMode dès la première version,
   ou uniquement le backend dans une première étape ?
3. Le plugin doit-il rester désactivable avec une erreur explicite, ou le
   composant doit-il conserver un adaptateur interne pendant une release ?
4. Les téléchargements Stripe doivent-ils rester dans le composant ou suivre
   le plugin ?
5. Quelle politique de version et de support doit accompagner le nouveau
   package plugin ?

## Critère de sortie final

Un formulaire existant contenant `bfStripe` fonctionne sans réédition avec le
plugin installé : configuration, validation, Checkout, retour, webhook,
enregistrement du paiement et téléchargement sont équivalents au comportement
précédent. Le composant ne contient plus de dépendance Stripe directe ni de
branche métier Stripe après la phase de contraction, et l’installation,
la mise à jour, la désactivation contrôlée et le rollback sont vérifiés.
