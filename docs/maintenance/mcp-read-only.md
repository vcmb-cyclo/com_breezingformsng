# BFNG MCP : lecture seule

Le point d’accès API `POST /api/index.php/v1/breezingformsng/mcp` expose les
outils `bfng_list_forms` et `bfng_get_form`. Il agit avec les droits du compte
Joomla dont le jeton API est fourni. Il ne lit pas les soumissions et n’effectue
aucune modification de formulaire.

## Activation et accès

1. Activer le plugin de services web BreezingForms NG, le plugin Joomla de jeton
   API et l’option « Activer MCP » de BFNG. L’option est désactivée par défaut.
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
- Les textes du site sont des données non fiables ; les outils et leurs
  résultats ne confèrent aucune autorisation supplémentaire au client.
