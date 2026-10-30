# Joomla 6 — Plugins et événements

- MUST identifier la signature exacte et le type d'événement dans le Joomla installé; ne pas supposer qu'un exemple Joomla 5/`next` est compatible.
- SHOULD utiliser les interfaces d'abonnement aux événements et les événements typés lorsqu'ils sont adaptés à la version du projet.
- MUST considérer chaque événement comme une frontière de confiance : vérifier contexte, identité, ACL, validation et effets de bord.
- SHOULD rendre les traitements idempotents lorsque l'événement peut être déclenché plusieurs fois.
- MUST éviter les boucles de dispatch, traitements récursifs et écritures implicites incontrôlées.
- SHOULD journaliser sans informations confidentielles de formulaire ou identifiants d'authentification.
- MUST maintenir le contrat historique des plugins BFNG/CBNG si des tiers en dépendent.

Référence : https://manual.joomla.org/docs/6.0/
