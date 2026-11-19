# 06 — Confrontation des cinq rapports

Date : 2026-11-19 · Référence : `main` au commit `1faa98d2` (RC22) · Coordinateur : session principale.
Rapports lus : 01-security (17 constats), 02-architecture (10), 03-testing (15), 04-ux (15), 05-product (9 propositions).
Aucun constat n'est supprimé : chaque ligne garde son statut et son historique.

## 1. Vérifications faites par le coordinateur

Seules ces vérifications ont été refaites ; le reste des preuves est repris des rapports tels que remis et n'a **pas** été revérifié.

| Constat | Vérification | Résultat |
|---|---|---|
| SEC-003 (IPN PayPal sans contrôle du destinataire) | `grep receiver_email\|receiver_id` dans `components/` et `administrator/` (PHP) | **Confirmé** : aucune occurrence. Reproduction sandbox non faite. |
| SEC-001 (`core.edit` suffit à écrire du code exécuté) | Lecture de `FormsController.php:258-330` et `FormOptionsModel.php:143-215` ; recherche de `AreaAccess::CODE` | **Confirmé statiquement** : `piece1-4code` et `script1-2code` sont lus en `raw` et enregistrés après un simple `core.edit` ; `AreaAccess::CODE` n'est jamais appelé sur ce chemin (il l'est pour les vues Script/Piece/Integrator, l'import et le MCP `include_code`). Le commentaire de `saveForm` (l. 168) montre que la séparation des droits est voulue pour `core.edit.state` ; la même intention pour `breezingformsng.code` semble ne pas avoir été appliquée. Exécution effective non reproduite. |
| SEC-002 (import de package) | Lecture de `PackageTransferModel.php:355-385` et `PackagesController.php:225-235` | **Confirmé en partie** : seuls `integrator` et `templates` sont retirés sans droit. Les bibliothèques `scripts` et `pieces` sont importées sans condition `CODE` (l. 380-381). Les colonnes de code au niveau des formulaires ne sont pas revérifiées. |
| SEC-004 (extension vérifiée sur `name[0]` seulement) | Lecture de `SubmissionEngine.php:288-300` | **Confirmé en partie** : `name[0]` est utilisé ; la boucle complète n'a pas été relue. |

Aucune vulnérabilité n'est **démontrée exploitable** à ce stade : toutes sont « confirmées statiquement » au mieux. La première tâche de chacune est une reproduction sur instance isolée.

## 2. Doublons et recoupements

| Groupe | Constats | Traitement |
|---|---|---|
| Frontière du droit « code » | SEC-001, SEC-002 | Même cause : `breezingformsng.code` n'est pas appliqué partout où du code est écrit. Un seul chantier de garde centralisée. |
| Tests lisant le source | ARC-007, TST-003, TST-010 | Même constat vu sous deux angles ; traité une fois, côté tests. |
| Couverture du cœur métier | ARC-001, TST-002, TST-013 | Les tests doivent précéder le découpage des moteurs. |
| Migration non atomique et peu testée | ARC-006, TST-005 | Même zone (`script.php`) ; traité ensemble. |
| MCP écriture | TST-001, PRD-001, PRD-007 | Tests avant extension (voir § 4). |
| Chaînes non traduites | UX-007, TST-014 | Une correction + un contrôle de rendu localisé. |
| Téléversements / opt-in | SEC-004, SEC-005, SEC-008, TST-012 | Les tests E2E de TST-012 sont les tests de validation de ces correctifs. |
| Ordre captcha / fichiers | SEC-006, PRD-005 | Même point d'entrée ; le durcissement anti-spam vient après la correction de l'ordre. |

## 3. Contradictions et écarts

| Écart | Analyse | Statut |
|---|---|---|
| Sécurité : « aucun PHPUnit exécuté » ; architecture et tests : 1 451 tests, OK | Pas de contradiction : périmètres d'exécution différents. Résultats (PHP 8.5.4 hôte) cohérents entre 02 et 03. | Résolu |
| `docs/security/00` : PHPStan « avec baseline » ; architecture : « baseline vide » | `phpstan-baseline.neon` fait 30 octets : la baseline est vide aujourd'hui. Le document de sécurité est antérieur. | Résolu (doc obsolète) |
| ARC-009 : plan indique PHPStan niveau 4 ; config niveau 8 | Doc dérivée, sans impact sur le code. | Ouvert (faible) |
| TST-009 : analyse statique seulement sur PHP 8.3 en CI ; ARC : PHPStan 8.5 = 0 erreur | Compatibles : l'exécution locale 8.5 ne remplace pas la CI sur 8.4. | Ouvert |
| UX : l'agent n'a pas chargé `frontend-design` et n'a pas observé l'administration (pas d'identifiants) | Écart de méthode déclaré. Tous les constats d'admin (UX-004 à UX-010) restent « déduits du code ». | Limite acceptée, à lever |
| Produit : « pas de honeypot ni de limitation de débit » (grep) ; sécurité : captcha sans problème observé | Pas de contradiction, mais la sécurité n'a pas évalué le déni de service par soumissions. | Angle mort |
| Produit : non-lecture de `script.php` et du moteur de rendu | Couvert par l'architecture ; aucun conflit. | Résolu |

## 4. Recommandations incompatibles ou sous condition

1. **PRD-001 (étendre l'écriture MCP) contre SEC-001 et TST-001.** Les outils d'écriture n'ont aucun test, et il n'est pas établi qu'ils respectent `breezingformsng.code` pour les colonnes de code. Étendre l'écriture aux e-mails et à la visibilité avant de fermer ce point agrandirait la surface. **Décision de séquencement** : TST-001 puis SEC-001 (y compris le MCP), puis PRD-001.
2. **ARC-001 à ARC-005 (découper les moteurs) contre le gel de la façade `HTML_facileFormsProcessor`.** Le PHP stocké en base dépend de cette façade. Seule l'extraction *derrière* la façade est admise ; aucune réécriture.
3. **SEC-006 (déplacer la vérification du captcha) contre l'ordre d'exécution des pièces « début de soumission ».** Le risque de régression est moyen ; à traiter avec les tests de TST-002, pas avant.
4. **SEC-003 (contrôler `receiver_email`) contre les marchands dont l'adresse du formulaire diffère de celle du compte.** À accompagner d'une note de mise à jour et d'un message clair.
5. **SEC-005 (filtrer `..` dans les chemins) contre les champs qui contiennent légitimement `/`.** À valider avec un jeu de cas avant de durcir.
6. **ARC-010 signale deux conflits avec `AGENTS.md`** (`version_compare` dans `script.php`, `JLoader::registerNamespace` de repli). Ils sont à arbitrer par Gilles ; je ne tranche pas.
7. **PRD-009 (préserver Piranha et XLSX)** est une contrainte, pas une action : tout chantier de réduction de taille ou de nettoyage doit la respecter.

## 5. Indispensable ou facultatif

- **Indispensable avant la version stable** : SEC-001, SEC-002, SEC-003, SEC-004 (+ SEC-005), après reproduction. Si une reproduction échoue, le constat est rétrogradé et l'historique conservé.
- **Hautement recommandé** : SEC-006 à SEC-009, TST-001, TST-002/013, TST-009, UX-001, UX-003, ARC-006.
- **Facultatif** : ARC-002, ARC-003, ARC-008, UX-009 à UX-015, PRD-002, PRD-007, PRD-008.

## 6. Quick wins (effort S, régression faible)

SEC-003, SEC-005, SEC-007, SEC-008 ; TST-006, TST-007, TST-009 ; UX-001 (texte masqué + `required`), UX-007, UX-012, UX-013, UX-015 ; ARC-008 (corriger la doc ou la requête).

## 7. Changements structurels

- Garde centralisée du droit « code » (SEC-001/002).
- Extraction progressive derrière la façade : soumission, notifications, enregistrements (ARC-001/003/004/005).
- Tests de comportement des contrôleurs et des moteurs (TST-002/013), puis remplacement des tests de forme du source (TST-003).
- Éditeur : accessibilité de l'arbre Quick Mode et retrait de `custom.css` (UX-004/009).
- Découpage de `script.php` et migration atomique (ARC-006).

## 8. Dépendances entre travaux

```
Reproduction sandbox/ACL (SEC-001..004)  ─► correctifs SEC ─► tests de non-régression (TST-012/013)
TST-001 (MCP écriture) ─► SEC-001 côté MCP ─► PRD-001 / PRD-007 / PRD-008
TST-002 (moteurs) ─► SEC-006 ─► ARC-001/003/004/005 ─► PRD-003 (webhook), PRD-005 (anti-spam)
TST-005 + ARC-006 ─► toute nouvelle migration de schéma
TST-009 (matrice PHP 8.4) ─► toute décision de compatibilité
UX-007 ─► TST-014 (contrôle de rendu localisé)
```

## 9. Registre des statuts

Les 66 constats et propositions restent dans leurs rapports d'origine avec leur statut. Statuts de la confrontation : SEC-001 *confirmé statiquement (vérifié)* ; SEC-002 *confirmé en partie (vérifié)* ; SEC-003 *confirmé (vérifié)* ; SEC-004 *confirmé en partie (vérifié)* ; SEC-005 *probable, non vérifié* ; SEC-006 à SEC-017, ARC-*, TST-*, UX-*, PRD-* *tels que rapportés, non revérifiés*. Aucun constat contesté à ce jour.
