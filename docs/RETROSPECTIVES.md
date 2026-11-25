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

## 2026-11-25 (fin) — tickets, PR #257, conflits, flux de mise à jour

### Points forts
- 16 tickets (#241 à #256) dans le projet GitHub ; statuts des tickets de la PR #240 alignés et relus.
- Conflits de la PR #257 diagnostiqués par leur cause (manifestes renommés dans #240), PR `MERGEABLE`/`CLEAN`.
- Le contrôle PHPCS par fichier a attrapé une régression avant le CI.
- Incohérence du flux de mise à jour repérée : `6.1.1-RC1` annoncé `stable` avec le zip et le SHA-256 de la 6.1.0.

### Points faibles
- Deux refus du classificateur (tag de release, commit non demandé) : accord non demandé avant la tentative.
- PHPCS corrigé en cinq tentatives au tâtonnement.
- Flux de mise à jour encore incohérent sur `main` (zip, SHA-256) ; RC1 non publiée.
- CI de la PR #257 et parcours Playwright non vérifiés.

### Actions
1. Action extérieure (tag, release, push non demandé) : demander l'accord avant de la tenter (skill `methode-ia`).
2. PHPCS : comparer les messages par ligne ancien/nouveau dès la première erreur (mémoire).
3. Publier la RC1, puis relire le flux et le corps de la release.

## 2026-11-25 (soir) — smoke test, infobulles, menus en masse

### Points forts
- Smoke test `audit-local-date` diagnostiqué depuis la trace du CI ; aide commune `runAuditAndOpen` pour les 5 parcours d'audit (passent en local, CI non confirmé).
- Contrôles verts avant chaque push : PHPUnit (2051 tests, 9 ignorés), PHPStan, PHPCS par fichier.
- Mise à la corbeille en masse des menus en anomalie : chaque élément revalidé contre un audit récent, 6 clés dans les 8 langues, parcours E2E `audit-menu-bulk-trash` (2 sur 2).
- Écart conteneur/dépôt détecté par comparaison de fichiers.

### Points faibles
- Deux fichiers (audit.php, custom.css) absents du conteneur 9080 jusqu'à la question de xavier.
- Parcours E2E écrit après le commit de la fonction.
- Cause de l'échec du CI toujours une hypothèse ; CI non revérifié après le push.

### Actions
1. Comparer le conteneur au dépôt avant de dire « fait » (mémoire `verify-container-after-delivery`).
2. Écrire le parcours E2E dans la même étape que la fonction.
3. Surveiller le CI après un push correctif, jusqu'au résultat.
4. Demande touchant une release (flux de mise à jour, tag, notes) : reformuler le changement minimal en une ligne avant d'agir (skill `methode-ia`, mémoire `release-ask-restate-minimal-change`). Origine : « RC1 sur main » lue comme « publier la RC ».
