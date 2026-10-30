# Joomla 6 — WebAssets, routing, langues

- SHOULD charger JS/CSS via le WebAssetManager et `joomla.asset.json` plutôt que des injections ad hoc dans les templates.
- MUST vérifier les dépendances JavaScript et l'ordre de chargement, éviter les doubles chargements de jQuery.
- MUST maintenir une configuration de routage SEF/non-SEF cohérente avec le LanguageFilter et les paramètres de menu.
- MUST tester menus, pagination, recherche, détail, édition, retour et URLs directes après tout changement de route.
- MUST utiliser clés de traduction cohérentes, paramètres de langue INI valides et structures de dossiers attendues par le manifest.
- SHOULD tester plusieurs langues et fallback sans inventer de traductions manquantes.
- SHOULD vérifier responsive, accessibilité des contrôles et thème sombre si les vues ont été modifiées.

Référence : https://manual.joomla.org/docs/6.0/
