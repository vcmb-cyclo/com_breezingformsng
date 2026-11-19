# ROADMAP — BreezingForms NG, audit 2026

Date : 2026-11-19 · Base : `main` au commit `1faa98d2` (6.1.0-RC22) · Sources : `01` à `06` dans ce dossier.

## 1. Résumé exécutif

Le code est syntaxiquement sain et bien outillé : `php -l` propre, PHPStan niveau 8 sans erreur, 1 451 tests PHPUnit verts (PHP 8.5.4 hôte), aucune dépréciation Joomla 6 relevée, `composer audit` sans avis. Les points faibles se concentrent à trois endroits.

1. **Frontières de privilège.** L'audit a trouvé quatre constats de gravité élevée, confirmés par lecture du code, aucun reproduit : le droit `core.edit` permet d'écrire du code PHP exécuté côté site (SEC-001) ; l'import de package écrit des scripts et pièces sans droit « code » (SEC-002) ; l'IPN PayPal ne vérifie pas le destinataire du paiement (SEC-003) ; le téléversement standard ne vérifie que l'extension du premier fichier (SEC-004).
2. **Un cœur métier peu testé.** Couverture mesurée à 30,46 % (rapport du 2026-11-18, PHP 8.3), proche de zéro sur contrôleurs, soumission, notifications, exports. Une centaine de tests vérifient la forme du source plus que le comportement.
3. **Accessibilité du formulaire public** : champs obligatoires signalés par une icône seule, captcha sans alternative.

Côté produit, les différenciateurs sont réels (migration depuis BreezingForms, ContentBuilder NG, RGPD, audit de base, MCP). Les lacunes mesurées : pas de webhook sortant générique, conditions en texte libre, anti-spam limité, MCP d'écriture limité à 6 types de champs.

**Aucune vulnérabilité n'est démontrée exploitable.** Les priorités P0 sont donc d'abord des reproductions, puis des correctifs.

## 2. Principaux risques

| Risque | Constats | Confiance |
|---|---|---|
| Exécution de PHP par un compte sans droit « code » | SEC-001, SEC-002 | Confirmé statiquement (vérifié) |
| Transaction PayPal marquée payée sans paiement au marchand | SEC-003 | Confirmé statiquement (vérifié) ; sandbox non testé |
| Dépôt de fichier à extension non autorisée | SEC-004, SEC-005 | Confirmé / probable |
| Régression non détectée dans le cœur métier | TST-001, TST-002, TST-013 | Confirmé (mesures) |
| Dates d'enregistrements à moitié converties à la mise à jour | ARC-006 | Probable (lecture, non reproduit) |
| Formulaire public inutilisable au lecteur d'écran | UX-001, UX-003 | Observé |

## 3. Méthode de priorisation

**Formule** : `Score = (Impact × Probabilité × Confiance) / (Effort + Régression)`, chaque terme de 1 à 5. Étendue : de 0,1 à 62,5.

**Limites** : (a) les notes sont des jugements du coordinateur, non des mesures ; (b) la formule ne tient pas compte des dépendances (un score faible peut être un prérequis) ; (c) elle compare mal un correctif de sécurité à une innovation produit ; (d) la probabilité est estimée sans données d'usage ni de déploiement.

**Règle d'exception** : un constat de gravité élevée confirmé n'est jamais rétrogradé par son score. Les P0 ci-dessous sont classés par cette règle, le score est donné à titre indicatif.

| Action | I | P | C | E | R | Score |
|---|---|---|---|---|---|---|
| SEC-003 contrôle du destinataire IPN | 4 | 3 | 4 | 1 | 2 | 16,0 |
| SEC-001 garde du droit « code » | 5 | 3 | 4 | 3 | 3 | 10,0 |
| SEC-002 import sans droit « code » | 5 | 2 | 4 | 3 | 3 | 6,7 |
| SEC-004 extension de chaque fichier | 4 | 3 | 4 | 3 | 3 | 8,0 |
| SEC-005 chemins de dépôt | 4 | 2 | 3 | 1 | 1 | 12,0 |
| SEC-008 opt-in/opt-out | 3 | 3 | 5 | 1 | 1 | 22,5 |
| SEC-006 ordre captcha/fichiers | 3 | 3 | 5 | 3 | 3 | 7,5 |
| SEC-007 `filepath` des téléchargements | 3 | 2 | 5 | 1 | 3 | 7,5 |
| SEC-009 cache `unserialize` | 3 | 2 | 4 | 3 | 3 | 4,0 |
| TST-001 tests MCP écriture | 4 | 3 | 5 | 3 | 1 | 15,0 |
| TST-002/013 tests moteurs et ACL contrôleurs | 4 | 4 | 5 | 4 | 1 | 16,0 |
| TST-009 matrice PHP 8.4 | 3 | 3 | 5 | 1 | 1 | 22,5 |
| TST-006 JS hors CI | 2 | 3 | 5 | 1 | 1 | 15,0 |
| TST-003 tests de forme du source | 3 | 4 | 5 | 4 | 1 | 12,0 |
| UX-001 champs obligatoires | 4 | 5 | 5 | 2 | 2 | 25,0 |
| UX-003 captcha accessible | 4 | 3 | 5 | 3 | 2 | 12,0 |
| UX-007 chaînes non traduites | 2 | 5 | 5 | 1 | 1 | 25,0 |
| UX-004 arbre au clavier | 4 | 3 | 4 | 4 | 3 | 6,9 |
| UX-009 `custom.css` hérité | 3 | 5 | 4 | 4 | 4 | 7,5 |
| ARC-006 migration atomique | 4 | 2 | 3 | 3 | 3 | 4,0 |
| ARC-001 découpage de `submit()` | 3 | 3 | 5 | 5 | 5 | 4,5 |
| ARC-002/003 duplication renderers/notifications | 2 | 3 | 5 | 3 | 3 | 5,0 |
| PRD-003 webhook sortant | 4 | 4 | 3 | 3 | 2 | 9,6 |
| PRD-005 anti-spam | 3 | 4 | 3 | 2 | 2 | 9,0 |
| PRD-004 conditions visuelles | 4 | 4 | 4 | 5 | 4 | 7,1 |
| PRD-006 parcours de démarrage | 3 | 4 | 3 | 3 | 2 | 7,2 |
| PRD-001 MCP étendu | 3 | 3 | 4 | 4 | 3 | 5,1 |
| PRD-002 aide IA optionnelle | 3 | 2 | 3 | 4 | 3 | 2,6 |
| PRD-007 OAuth MCP | 2 | 2 | 3 | 4 | 3 | 1,7 |
| PRD-008 pont MCP ContentBuilder NG | 2 | 2 | 2 | 4 | 3 | 1,1 |

## 4. Feuille de route

### P0 — Bloquant avant la version stable
Chacun commence par une reproduction sur instance isolée. Un échec de reproduction déclasse le constat, avec trace.

| Id | Travail |
|---|---|
| SEC-001 | Exiger `breezingformsng.code` pour toute écriture de `piece*code`, `script*code` (formulaires, y compris via MCP). Les champs de code sont ignorés avec un message si le droit manque. |
| SEC-002 | Retirer des packages importés scripts, pièces et colonnes de code si le droit manque, avec le résumé « ignoré » déjà utilisé pour Integrator et modèles. |
| SEC-003 | Comparer `receiver_email`/`receiver_id` au compte marchand configuré avant d'accepter l'IPN. |
| SEC-004, SEC-005 | Vérifier l'extension de chaque fichier du lot ; refuser `..` dans les valeurs de champs utilisées dans le chemin. |

### P1 — Haute priorité
SEC-006, SEC-007, SEC-008, SEC-009 ; TST-001, TST-002/013, TST-009 ; UX-001, UX-003 ; ARC-006 avec TST-005.

### P2 — Amélioration importante
SEC-010 à SEC-017 (faibles ou probables) ; TST-003, TST-006, TST-007, TST-008 ; UX-002, UX-004 à UX-009 ; ARC-001 à ARC-005 (extraction derrière la façade) ; PRD-003, PRD-004, PRD-005, PRD-006.

### P3 — Évolution facultative
UX-010 à UX-015 ; ARC-008 à ARC-010 ; PRD-001, PRD-002, PRD-007, PRD-008.

## 5. Les dix améliorations prioritaires

1. SEC-003 — destinataire de l'IPN PayPal.
2. SEC-001 — droit « code » sur l'édition des formulaires.
3. SEC-002 — droit « code » sur l'import de packages.
4. SEC-004 + SEC-005 — extension de chaque fichier et chemins de dépôt.
5. SEC-008 — opt-in/opt-out : jeton vide et actions par GET.
6. SEC-006 + SEC-007 + SEC-009 — ordre captcha, `filepath`, `unserialize`.
7. TST-001 + TST-002 + TST-013 — tests MCP écriture, moteurs, ACL contrôleurs.
8. UX-001 + UX-003 — champs obligatoires et captcha accessibles.
9. TST-009 + TST-006 + TST-007 — PHP 8.4 en CI, JS en CI, seuil de couverture.
10. ARC-006 + TST-005 — migration de dates atomique et testée.

## 6. Quick wins
SEC-003, SEC-005, SEC-007, SEC-008 ; TST-006, TST-007, TST-009 ; UX-001, UX-007, UX-012, UX-013, UX-015 ; ARC-008 (requête ou documentation).

## 7. Travaux structurants
Garde centralisée du droit « code » ; extraction progressive des moteurs derrière la façade `HTML_facileFormsProcessor` ; remplacement des tests de forme du source par des tests de comportement ; refonte accessible de l'arbre Quick Mode ; découpage de `script.php`.

## 8. Fonctionnalités innovantes
Sources et comparaison dans `05-product.md` (concurrents consultés le 2026-11-19 ; plusieurs marqués « non vérifié »).
- **PRD-003** webhook sortant générique (Zapier, Make, n8n).
- **PRD-004** éditeur visuel de conditions ET/OU.
- **PRD-001** MCP : e-mails, visibilité, types de champs supplémentaires, après SEC-001 et TST-001.
- **PRD-002** aide à la rédaction par IA, désactivée par défaut, qui n'envoie jamais de soumissions.
- **PRD-005** honeypot et limitation de débit.
- **PRD-006** parcours de démarrage et conversion legacy.
- **PRD-007/008** OAuth MCP ; pont MCP vers ContentBuilder NG (à valider côté ContentBuilder NG, non audité).

## 9. Dépendances techniques
```
Reproduction (SEC-001..004) ─► correctifs ─► TST-012/013
TST-001 ─► SEC-001 côté MCP ─► PRD-001 / PRD-007 / PRD-008
TST-002 ─► SEC-006 ─► ARC-001/003/004/005 ─► PRD-003, PRD-005
TST-005 + ARC-006 ─► toute nouvelle migration
```
Contraintes permanentes : façade `HTML_facileFormsProcessor` gelée (PHP stocké) ; Piranha et XLSX préservés ; huit langues mises à jour ensemble ; cible Joomla 6.2 / PHP 8.4, compatibilité 8.5 (conflits `version_compare` et `JLoader` de `AGENTS.md` à arbitrer par Gilles).

## 10. Critères d'acceptation

| Id | Critère |
|---|---|
| SEC-001 | Un compte avec `core.edit` sans `breezingformsng.code` enregistre un formulaire : colonnes de code inchangées, message affiché. Avec le droit : enregistrées. Même résultat par MCP. |
| SEC-002 | Import par un compte sans droit « code » : scripts, pièces et code de formulaire ignorés et listés dans le résumé ; le reste est importé. |
| SEC-003 | IPN vérifié avec `receiver_email` différent : refusé et journalisé sans donnée personnelle ; identique : accepté. |
| SEC-004/005 | Lot de plusieurs fichiers dont un interdit en 2e position : refusé ; valeur `../x` dans un champ de chemin : refusée. |
| SEC-008 | Jeton vide ou erroné : aucun changement d'état. |
| TST-001/002/013 | Tests de comportement pour les outils d'écriture MCP, une soumission complète, un envoi de notification et, par contrôleur d'admin, un cas ACL refusé et un autorisé. |
| TST-009 | CI exécute PHPStan, lint et PHPUnit sur 8.4 et 8.5. |
| UX-001/003 | Champs obligatoires exposés par `required`/`aria-required` et texte masqué ; captcha avec nom accessible et alternative. |
| ARC-006 | Relance de la migration de dates après interruption : aucune date convertie deux fois ni laissée mixte. |

## 11. Tests de validation
- PHPUnit sur PHP 8.4 puis 8.5 de l'hôte, sans plugins de compatibilité ; PHPStan niveau 8 ; PHPCS.
- Instance isolée `joomla6-joomla-1` (PHP 8.3, à ne pas prendre pour une validation 8.4) : ACL par rôle, CSRF, téléversements, IPN simulé.
- Migration : installation neuve, mise à jour depuis le schéma précédent, relance, sur copies jetables de base.
- E2E Playwright : formulaire public clavier seul, thème sombre, mobile 390 px ; administration observée avec identifiants de test (non fait dans cet audit).
- Contrôle de traduction `scripts/check-translations.py` pour les huit langues.

## 12. Limites et incertitudes de l'audit
- Revue statique : aucune exploitation, aucun paiement sandbox, aucun test HTTP/ACL en conditions réelles. Les constats de sécurité sont au mieux « confirmés statiquement ».
- Seuls SEC-001 à SEC-004 ont été revérifiés par le coordinateur, et SEC-002/004 en partie. Tout le reste est repris des rapports.
- Chiffres de tests et de taille de code mesurés par les agents (PHP 8.5.4 hôte), non rejoués par le coordinateur ; couverture non régénérée (rapport du 2026-11-18).
- Administration non observée dans un navigateur (pas d'identifiants) ; `frontend-design` non utilisé ; seul le front public de test a été capturé.
- Veille concurrentielle partielle : plusieurs concurrents « non vérifié », support Joomla 6 de RSForm! Pro non confirmé ; ContentBuilder NG non audité.
- Rien exécuté : Playwright, node:test, smoke d'installation, PHPCS, Psalm.
- Les 42 fiches de `docs/security/` antérieures : 11 non revérifiées.
- Les agents d'audit ont été lancés comme agents génériques suivant les fichiers `.claude/agents/audit-*.md` , car les types n'étaient pas encore enregistrés au lancement.
- Aucun code applicatif modifié. Rien n'est commité.
