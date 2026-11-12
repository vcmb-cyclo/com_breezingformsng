# BFNG MCP : lecture seule

Le point d’accès API `POST /api/index.php/v1/breezingformsng/mcp` expose les
outils `bfng_list_forms`, `bfng_get_form`, `bfng_check_form` et
`bfng_audit_database`. Il agit avec les droits du compte Joomla dont le jeton
API est fourni. Il ne lit pas les soumissions et n’effectue aucune
modification : les quatre outils sont en lecture seule.

## Activation et accès

1. Activer l’option « Activer le MCP » de BFNG (Options, onglet « Assistants IA
   (MCP) »). Elle est désactivée par défaut : tant qu’elle l’est, le point
   d’accès n’existe pas. Le plugin de services web BreezingForms NG est activé à
   l’installation et le plugin Joomla de jeton API l’est par défaut ; s’ils ont
   été désactivés, l’état affiché sur la page Configuration de BFNG l’indique.
2. Utiliser un compte Joomla autorisé à se connecter à l’API et à gérer BFNG.
   La lecture du code nécessite en plus le droit BFNG correspondant.
3. Utiliser HTTPS et transmettre le jeton dans `Authorization: Bearer …`.
   Ne pas inclure le jeton dans une URL, un formulaire ou un journal.
4. Configurer le serveur web pour accepter uniquement les noms d’hôte prévus et
   ne faire confiance aux en-têtes de proxy qu’aux proxys maîtrisés. Configurer
   l’URL publique Joomla si nécessaire. La comparaison de `Origin` avec l’URL
   publique ne remplace pas cette validation du serveur web.

Le serveur utilise le jeton API Joomla : la prise en charge de la découverte et
du parcours OAuth MCP n’est pas implémentée. La compatibilité avec un client
précis, notamment une application ChatGPT ou Claude, doit être vérifiée avant
de la promettre. Un client doit pouvoir envoyer le jeton Joomla avec ses requêtes.

## Outils

Tous demandent le droit de gérer BFNG, et la lecture du code des formulaires
le droit de code.

- `bfng_list_forms` : liste paginée des formulaires (recherche, état,
  `limit`, `offset`).
- `bfng_get_form` : un formulaire par ID ou par nom, avec ses réglages et ses
  éléments. `include_code` ajoute les scripts et pièces, et demande le droit
  de code.
- `bfng_check_form` : contrôle d’un formulaire par ID ou par nom, sans rien
  modifier.
  - Erreurs : un champ de saisie sans nom, des noms en double, un script de
    bibliothèque d’initialisation, d’action ou de validation qui n’existe plus,
    est dépublié ou n’est pas du bon type. Les boutons radio d’un même groupe
    d’un formulaire classique partagent un nom volontairement et ne sont pas des
    doublons ; un autre élément qui porte ce nom en est un.
  - Avertissements : un champ de saisie sans libellé, un élément placé sur une
    page que le formulaire n’a pas, un script personnalisé dont le code est vide.
  - Propositions : une validation de la bibliothèque (e-mail, URL, date, nombre
    entier, montant) pour un champ texte qui n’en a aucune, d’après son nom ou
    son libellé. Ce sont des propositions à confirmer dans l’éditeur : rien
    n’est appliqué.
  - Le code des scripts n’est jamais lu, seulement le fait qu’un script
    personnalisé soit vide. Au plus 200 problèmes sont renvoyés ; `summary`
    garde les totaux.
- `bfng_audit_database` : le rapport de l’audit de l’écran À propos, sans
  argument et sans réparation (elles restent sur cet écran). Le rapport ne
  donne que des identifiants et des codes : les valeurs des paramètres d’un lien
  de menu sont masquées (sauf `option`, `view`, `layout`, `task` et `Itemid`),
  les chemins sont relatifs au site, le détail d’un échec de l’audit est omis et
  les jetons de réparation ne sont jamais renvoyés. Chaque liste, y compris
  celles qui sont dans les éléments d’une autre, est limitée à 50 éléments :
  `truncated` nomme les listes coupées par leur chemin (par exemple
  `duplicate_forms[].drop`), une liste imbriquée coupée reçoit un champ
  `<nom>_total`, et `summary` garde les totaux. Les noms de formulaires, de
  tables et d’extensions restent dans le rapport.

## Comportement

- `initialize`, `ping`, `tools/list` et `tools/call` sont disponibles ; les
  réponses sont en JSON. Les notifications ne reçoivent pas de corps de réponse.
- Les versions MCP 2025-03-26, 2025-06-18 et 2025-11-25 sont reconnues. Les
  lots JSON-RPC sont limités à 32 messages et réservés à 2025-03-26 ; en
  l’absence d’en-tête de version, cette version est utilisée pour le transport.
- `bfng_list_forms` est paginé. `bfng_get_form` accepte un ID ou un nom ; si
  plusieurs formulaires portent ce nom, utiliser leur ID.
- Les colonnes connues de secrets et les adresses de notification ne sont pas
  renvoyées, mais le contenu des champs et le code éventuellement demandé
  peuvent contenir des données sensibles. Vérifier le fournisseur et les
  données partagées avant d’utiliser un assistant externe.
- Les champs des éléments (`data1` à `data3`) peuvent contenir des blocs
  `<?php … ?>`, que le moteur exécute à l’affichage du formulaire : ils sont
  renvoyés tels quels à tout compte autorisé à gérer BFNG, comme l’éditeur
  QuickMode de l’interface les lui montre. Seuls les scripts et pièces des
  formulaires demandent le droit de code.
- Les textes du site sont des données non fiables ; les outils et leurs
  résultats ne confèrent aucune autorisation supplémentaire au client.
