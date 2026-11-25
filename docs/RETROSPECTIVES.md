# Rétrospectives

## 2026-11-25 — cycle 6.1.1-RC1 (récursion RC23/RC24, audit, renommage, mode sombre)

### Points forts
- Cause racine de la récursion prouvée par la pile d'appels ; corrigée (PR #235) avec un test qui interdit les accesseurs d'événement.
- Règle durable dans `AGENTS.md` : tout chemin de code propre à une extension optionnelle est testé avec elle activée ; E2E avec les add-ons activés.
- Migration vérifiée depuis le vrai paquet 6.1.0 (CI, second passage, cache activé) ; elle a attrapé deux de mes erreurs.
- Revue publiée sur la PR #240, suivie de corrections (`core.edit.state`, brouillons protégés, test E2E de la corbeille).
- Mode sombre corrigé en inspectant les styles calculés dans un vrai navigateur.

### Points faibles
- Hypothèses avant diagnostic (gestionnaire d'erreurs, erreur de mail) : plusieurs tours perdus.
- Une modification en cours de l'utilisateur a été écrasée, puis restaurée.
- Mode sombre corrigé en trois tours (variable Atum inexistante, sélecteur incomplet, ombre interne de Bootstrap).
- Échecs PHPCS répétés en CI alors que le contrôle local existe.
- Tickets #241 à #255 absents du tableau Projects (permission manquante).

### Actions
1. Diagnostic « local OK, ailleurs KO » : comparer les extensions activées, puis la pile d'appels (skill `methode-ia`).
2. PHPCS sur les fichiers modifiés avant chaque push (`AGENTS.md`).
3. CSS/mode sombre : inspecter les styles calculés avant de corriger (mémoire).
4. Format des réponses : 10 lignes maximum (`preferences.md`).
5. À faire par Xavier : `gh auth refresh -s project`, puis ajout des tickets au tableau.

## 2026-11-25 (suite) — mode sombre et icônes de l'intégrateur

### Points forts
- Règle « inspecter les styles calculés d'abord » appliquée : cause des icônes sans style trouvée en un tour (`sync()` écrasait `className`).
- Menu à icônes de l'éditeur réutilisé (pas dupliqué) ; sélecteur de l'éditeur revérifié.
- Vérification dans un navigateur en modes sombre et clair, utilisateur temporaire supprimé.

### Points faibles
- Défauts de mode sombre signalés écran par écran, sans balayage global.
- Trois sujets non commités mélangés dans l'arbre de travail.
- Demande « notes de versions » ambiguë : réponse donnée sans question.

### Actions
1. Balayer tous les écrans d'administration en mode sombre avec un script listant les fonds clairs (mémoire du projet).
2. Commiter par sujet : CSS sombre, icônes de l'intégrateur, docs.
3. Lever en une question l'ambiguïté d'une demande courte avant de répondre.
