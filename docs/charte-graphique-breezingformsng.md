# BreezingFormsNG — Charte graphique & guide d'implémentation

*V1.0 — Joomla 6 / Atum*

Un système visuel unique pour les six écrans du composant : jetons de conception, classes CSS, règles de composition et modes clair / sombre. Le composant s'aligne sur le back-office Joomla (template Atum, Bootstrap 5) au lieu de lui superposer son propre habillage.

| | |
|---|---|
| **Constat** | Trois bleus concurrents (`#0088CC`, `#2d89ef`, `#92c1ff`), un vert et un orange (`#faa732`) hérités de Bootstrap 2, des couleurs en dur, du Verdana 11 px, des flottants et des largeurs en pourcentage. |
| **Parti pris** | Une seule teinte d'accent, héritée du template. Le gris porte la structure, la couleur ne porte que l'état. Densité d'outil professionnel : contrôles de 30 px, lignes d'arbre de 26 px. |
| **Contrat technique** | Un préfixe `bfng-`, une classe racine, zéro `!important`, zéro couleur en dur hors du fichier de jetons, `data-bs-theme` respecté, surcharges de template préservées. |

## 01 — Principes directeurs

| # | Principe | Détail |
|---|---|---|
| 01 | **Hériter, ne pas repeindre** | Chaque jeton de surface, de bordure et de texte pointe d'abord sur une variable Bootstrap 5 / Atum, avec une valeur de repli. Le composant suit donc automatiquement le thème du back-office, y compris les templates admin tiers. |
| 02 | **La couleur est un état, pas une décoration** | L'accent est réservé à la sélection, au focus, aux liens et à l'action primaire. Aucun aplat coloré d'entête, aucun onglet bleu plein, aucun bouton orange. |
| 03 | **Dense mais respirant** | Échelle d'espacement de 2 px de base. On gagne de la densité en réduisant les marges, jamais la taille du texte : plancher à 12 px, corps d'interface à 13 px. |
| 04 | **Une seule couche de mise en page** | Grille CSS et flex avec `gap` partout. Les `float`, les `width: 50%` et les `padding-left: 310px` de l'éditeur disparaissent au profit d'une grille à deux volets redimensionnable. |
| 05 | **Un seul thème sombre** | Le mode sombre n'est pas une seconde feuille de style : c'est la même feuille, avec un bloc de jetons redéfini sous `[data-bs-theme="dark"]`. Aucune règle de composant n'est dupliquée. |

## 02 — Couleurs

Les valeurs ci-dessous sont les *replis* : elles s'appliquent quand le template hôte n'expose pas la variable correspondante. Elles sont calibrées sur Atum et respectent un contraste de 4,5:1 pour le texte, 3:1 pour les bordures actives et les icônes.

### Mode clair

| Jeton | Valeur | Usage |
|---|---|---|
| `--bfng-bg` | `#f4f6f8` | Fond de page. Hérite de `--bs-body-bg`. |
| `--bfng-surface` | `#ffffff` | Cartes, volets, champs, lignes de table. |
| `--bfng-surface-2` | `#eef1f5` | Entêtes de volet, barres d'outils, `thead`. |
| `--bfng-surface-3` | `#e4e8ee` | Survol, poignées de redimensionnement, rails. |
| `--bfng-border` | `#dfe3e7` | Filet standard, 1 px. Déjà en usage dans `forms-edit.css`. |
| `--bfng-border-strong` | `#c3cad3` | Bordure de champ, séparateur de volets. |
| `--bfng-text` | `#1f2933` | Texte principal, titres, valeurs. |
| `--bfng-text-muted` | `#7b8794` | Libellés secondaires, indices, compteurs. |

### Accent — une seule teinte, six pas

| Pas | Valeur | Rôle |
|---|---|---|
| 50 | — | Fonds de sélection et de survol |
| 100 | — | Fonds de sélection et de survol |
| 300 | — | Bordures actives et anneau de focus |
| 500 | `#2f70b9` | Liens, icônes, bordure de l'action primaire |
| 600 | — | Survol |
| 700 | — | État enfoncé et texte d'accent sur fond clair |

Le pas 500 reprend `#2f70b9`, déjà présent comme repli de `--template-link-color` : la migration ne change pas la teinte, elle la discipline.

### États sémantiques

| Jeton | Clair | Emploi |
|---|---|---|
| `--bfng-success` | `#1f7a52` | Publié, enregistré, validé |
| `--bfng-warning` | `#96650f` | Non publié, obsolète, non enregistré |
| `--bfng-danger` | `#a8332c` | Erreur de validation, suppression |
| `--bfng-info` | = accent | Information, aide contextuelle |

### Familles d'éléments

| Jeton | Clair | Éléments |
|---|---|---|
| `--bfng-kind-struct` | `#2f70b9` | Formulaire, page, section |
| `--bfng-kind-input` | `#0d9488` | Saisie, choix, fichier, date |
| `--bfng-kind-logic` | `#7c3aed` | Script, calcul, pièce, condition |
| `--bfng-kind-static` | `#c2610c` | Texte, image, séparateur, bouton |

Quatre familles remplacent la mosaïque d'icônes PNG de `media/com_breezingformsng/images/quickmode/`. Les icônes deviennent des glyphes Lucide monochromes teintés par la famille : un seul jeu vectoriel, lisible en sombre, sans image à recharger.

### Icônes des types de champ

Le menu « Choisir un type de champ » (`#bfElementType`) utilise **une icône Lucide unique par type** (trait 2 px, 16 px, **une couleur propre à chaque type**, jeton `--bfng-type-*`), jamais d'emoji, et ses options sont **regroupées par famille** (`<optgroup>`) au lieu de l'ordre alphabétique plat. **La liste des types reste telle quelle** : ne pas fusionner ni scinder d'entrées.

| Famille | Type | Icône Lucide |
|---|---|---|
| Saisie (`input`) | Champ de texte | `Type` |
| | Zone de texte | `AlignLeft` |
| | Champ numérique / Slider | `Hash` |
| | Calendrier | `Calendar` |
| | Calendrier responsive | `CalendarDays` |
| | Entrée cachée | `EyeOff` |
| | Envoi de fichiers | `Paperclip` |
| Choix (`struct`/accent) | Case à cocher | `SquareCheck` |
| | Groupe de cases à cocher | `ListChecks` |
| | Groupe de boutons radio | `CircleDot` |
| | Sélection dans une liste | `SquareChevronDown` |
| | Slider | `SlidersHorizontal` |
| Sécurité et paiement (`logic`) | Captcha | `Lock` |
| | ReCaptcha | `ShieldCheck` |
| | Signature | `PenLine` |
| | Bouton PayPal | `Wallet` |
| | Stripe | `CreditCard` |
| Action et vue (`static`) | Bouton Envoyer | `Send` |
| | Vue d'ensemble | `ClipboardList` |

Les familles de ce tableau organisent le menu ; elles ne donnent pas la couleur. Chaque type a sa teinte, en clair et en sombre, avec un contraste d'au moins 3:1 sur le fond du menu.

**Ordre** : familles dans l'ordre du tableau (Saisie → Choix → Sécurité et paiement → Action et vue) ; à l'intérieur d'une famille, ordre du tableau (du plus courant au plus spécialisé), **pas alphabétique**. Le premier élément du menu reste « Choisir un type de champ » (désactivé, sans icône). Chaque intitulé de famille est un `<optgroup label>` non sélectionnable.

**Intégration** : les `<option>` natives n'acceptent pas d'icône dans tous les navigateurs (Firefox notamment). Le `<select>` natif avec `<optgroup>` reste la source de la valeur et de l'événement `change`, mais il est masqué ; `quickmode-type-menu.js` le double par un menu personnalisé (`role="combobox"` + `role="listbox"`) avec navigation clavier complète : flèches, Début/Fin, Entrée/Espace, Échap, Tab et recherche par saisie. Chaque icône est un SVG en `mask-image` (data-URI) défini une seule fois par une classe `.bfng-type-icon--<valeur de l'option>`, dans `bfng-admin.css`. Les mêmes icônes servent dans l'arbre (`.bfng-tree__icon`) et la palette.

## 03 — Mode sombre

Le sombre n'inverse pas le clair : il remonte les surfaces par paliers et éclaircit l'accent pour tenir le contraste. Les seuls sélecteurs autorisés sont `[data-bs-theme="dark"] .bfng` et `.bfng[data-bs-theme="dark"]`.

| Jeton | Valeur sombre |
|---|---|
| `--bfng-bg` | `#131720` |
| `--bfng-surface` | `#1a1f29` |
| `--bfng-surface-2` | `#212834` |
| `--bfng-surface-3` | `#2a323f` |
| `--bfng-border` | `#333c4a` |
| `--bfng-text` | `#e7ebf0` |
| `--bfng-accent-500` | `#6ea8e2` |
| `--bfng-accent-600` | `#8bbdea` |

En sombre, l'accent s'*éclaircit* au survol (600 > 500) alors qu'il s'assombrit en clair : c'est la seule règle inversée. Les fonds teintés passent par `color-mix(in oklab, var(--bfng-accent-500) 16%, transparent)` plutôt que par un pas opaque, pour rester lisibles sur les trois surfaces.

Sémantiques en sombre : succès `#4fb888`, alerte `#dba449`, erreur `#e0736a` ; familles : structure `#7fb2e6`, saisie `#4bb5ae`, logique `#a58ede`, statique `#9aa5b4`.

## 04 — Typographie, densité, élévation

Aucune police n'est chargée par le composant : on hérite de la pile du template admin. Le monospace n'est utilisé que là où les caractères sont des données — noms de champs, jetons `{gn_...}`, code, identifiants.

### Typographie

| Rôle | Jeton | Valeur |
|---|---|---|
| Interface | `--bfng-font-ui` | `var(--bs-body-font-family)` |
| Données / code | `--bfng-font-mono` | `ui-monospace, SFMono-Regular, Menlo, Consolas, monospace` |
| Micro-libellé | `--bfng-fs-xs` | 11 px / 1.3 |
| Secondaire | `--bfng-fs-sm` | 12 px / 1.4 |
| Corps d'interface | `--bfng-fs-base` | 13 px / 1.45 |
| Prose, frontend | `--bfng-fs-md` | 14 px / 1.55 |
| Titre de volet | `--bfng-fs-lg` | 16 px / 1.3 — 600 |
| Titre d'écran | `--bfng-fs-xl` | 20 px / 1.25 — 600 |

### Mesures

| Mesure | Jeton | Valeur |
|---|---|---|
| Échelle d'espacement | `--bfng-space-1…8` | 2 · 4 · 6 · 8 · 12 · 16 · 24 · 32 px |
| Hauteur de contrôle | `--bfng-control-h` | 30 px (26 px en `-sm`) |
| Ligne de table | `--bfng-row-h` | 34 px |
| Ligne d'arbre | `--bfng-tree-row-h` | 26 px |
| Retrait d'arbre | `--bfng-tree-indent` | 16 px par niveau |
| Rayon | `--bfng-radius` | 4 px (3 px `-sm`, 6 px `-lg`) |
| Élévation 1 | `--bfng-shadow-1` | `0 1px 2px rgb(16 24 40 / 6%)` |
| Élévation 2 — menus | `--bfng-shadow-2` | `0 6px 16px rgb(16 24 40 / 10%)` |
| Élévation 3 — dialogues | `--bfng-shadow-3` | `0 16px 40px rgb(16 24 40 / 18%)` |
| Focus | `--bfng-focus-ring` | `0 0 0 3px rgb(47 112 185 / 32%)` |
| Transition | `--bfng-t` | `120ms ease` |

Aucune ombre portée ne remplace un filet : l'élévation ne sert qu'aux couches flottantes (menu des champs disponibles, dialogue d'enregistrement, aperçu). Les volets fixes sont délimités par une bordure de 1 px.

## 05 — Composants

Onze composants couvrent les six écrans. Chacun est une classe unique, avec des modificateurs suffixés, jamais une combinaison d'utilitaires ad hoc.

| Classe | Modificateurs / états | Règle |
|---|---|---|
| `.bfng-btn` | `--primary --ghost --danger --icon --sm · :hover :active :focus-visible [disabled]` | Hauteur 30 px, rayon 4 px, contour 1 px. La primaire est un contour d'accent sur fond de surface — aucun aplat plein. Enfoncé : fond `accent-50`, bordure `accent-700`. |
| `.bfng-field` | `__label __control __hint __error --wide --stacked --mono` | Grille `190px 1fr`, repli empilé sous 900 px. Libellé 12 px, contrôle 13 px. L'erreur vit sous le contrôle, jamais en infobulle. |
| `.bfng-tabs` | `__tab __tab.is-active __actions` | Onglets sur `surface-2`, l'actif reprend la surface du panneau et coupe le filet. Barre d'actions poussée à droite dans la même ligne. |
| `.bfng-tree` | `__row __row.is-selected __row.is-drop-target __toggle __icon __badge` | Ligne de 26 px, retrait de 16 px. Sélection = fond `accent-50` + liseré interne de 2 px. Cible de dépôt = filet `accent-500` de 2 px sur l'arête concernée. |
| `.bfng-panel` | `__header __body __footer · --scroll` | Surface bordée, entête de 34 px sur `surface-2`. Le corps est la seule zone défilante ; l'entête et le pied restent collés. |
| `.bfng-split` | `__aside __main __handle · --docked` | Grille à deux colonnes, largeur d'aside redimensionnable (280–480 px) et mémorisée. Remplace `#bfQuickModeLeft/Right` et leurs flottants. |
| `.bfng-table` | `--dense __num __actions · tr.is-selected` | Sur `table table-hover` de Joomla. Lignes de 34 px, filets horizontaux seuls, pas de zébrage. Colonnes numériques en chiffres tabulaires alignés à droite. |
| `.bfng-badge` | `--published --unpublished --error --type --count` | Contour teinté, hauteur 18 px, 11 px de texte. Jamais un aplat saturé. L'état publié se lit aussi par l'icône, pas seulement par la couleur. |
| `.bfng-toolbar` | `__group __spacer __search · --sticky` | Barre de 44 px collée sous la barre Joomla, filet bas de 1 px, groupes séparés par un `gap` de 12 px et un filet vertical. |
| `.bfng-dialog` | `__backdrop __head __body __actions` | Élévation 3, largeur maximale 640 px (960 px pour l'aperçu de soumission), fermeture au clavier. Remplace les boîtes jQuery UI. |
| `.bfng-message` | `--info --success --warning --danger` | Filet gauche de 3 px + fond teinté à 8 %, icône Lucide 16 px. Remplace `.bfFadingMessage` ; ne disparaît jamais tout seul pour une erreur. |

## 06 — Règles par écran

Chaque vue reçoit une classe racine unique, portée par le conteneur de `tmpl/<vue>/*.php`. Toute règle CSS du composant est préfixée par elle : aucune fuite vers le reste du back-office.

### Éditeur de formulaire
`.bfng .bfng-editor` — `tmpl/quickmode/default.php`

Deux volets : arbre à gauche (`.bfng-split__aside`, redimensionnable, entête collée avec la recherche et les boutons d'ajout), inspecteur à droite. La barre de langues (`fr-FR / en-GB`) devient un `.bfng-seg` discret dans la barre d'outils, plus deux pastilles vertes et rouges. « Keep panel docked » et « Scroll element list » passent en interrupteurs dans un menu ⚙ de l'entête d'arbre, hors du flux de contenu. Le bouton d'enregistrement quitte le corps du panneau pour la barre d'outils Joomla ; la mention « modifications non enregistrées » s'affiche en `.bfng-badge--unpublished` à côté du titre.

### Liste des formulaires
`.bfng .bfng-list` — `tmpl/forms/default.php`

Filtres regroupés dans `.bfng-toolbar` avec la recherche à gauche et les sélecteurs à droite ; le bouton « Rechercher » disparaît au profit d'une soumission différée. Colonne titre dominante (lien accent, nom technique en monospace 11 px en dessous), `id` et compteurs en chiffres tabulaires, état publié en icône + badge de contour. Le tri conserve les `searchtools.sort` de Joomla, simplement restylés.

### Soumissions
`.bfng .bfng-records` — `tmpl/records/*.php`

Le panneau de recherche flottant (`#bfSearchWrapper`, position absolue) devient un tiroir `.bfng-panel` replié sous la barre d'outils, en grille de champs. Table dense à colonnes gelées (case, date, actions) et défilement horizontal du reste. Le détail d'un enregistrement s'ouvre en `.bfng-dialog` : libellés à 20 % en `text-muted`, valeurs en `surface`, pas d'entête bleu ni de zébrage. Les fichiers joints se lisent comme des liens, jamais comme des chemins bruts.

### Configuration
`.bfng .bfng-config` — `tmpl/forms/edit.php`, `integrator`, `menus`

Mesure fixe de 1180 px maximum (règle déjà présente dans `forms-edit.css`, à conserver), champs limités à 32 rem, zones de code en pleine largeur. Les `card-header` bleus pleins deviennent des entêtes `surface-2` à texte foncé. Sections séparées par un filet et un titre de 16 px, pas par une carte imbriquée. Une seule colonne : la lecture prime sur la densité dans les écrans de réglages.

### Assistant de création
`.bfng .bfng-wizard`

Trois étapes maximum, une question par écran, largeur de 720 px centrée. Fil d'étapes en chiffres tabulaires reliés par un filet ; l'étape courante en accent, les précédentes cochées, les suivantes en `text-muted`. Les modèles de départ sont des `.bfng-panel` sélectionnables (bordure d'accent + liseré, pas de fond coloré). Pied fixe : « Retour » en ghost à gauche, « Continuer » en primaire à droite.

### Formulaire public
`.bfng-form` — `components/…/themes/`

Le frontend n'impose ni couleur ni police : il hérite du template du site via `--bs-*` et ne fixe que la géométrie — hauteur de contrôle 40 px (cible tactile ≥ 44 px avec le libellé), libellés au-dessus, champs en pleine largeur, une colonne sous 640 px. Erreurs sous le champ, `aria-describedby` obligatoire, jamais d'infobulle « balloon ». Pagination et bouton d'envoi dans un pied à filet haut. Le thème par défaut fournit uniquement des variables, pas des couleurs en dur, pour rester surchargeable.

Les vues `about`, `help`, `scripts` et `pieces` réutilisent `.bfng-config` sans classe supplémentaire ; `about.css` peut alors être supprimé.

## 07 — Table de migration

Les identifiants historiques restent dans le HTML tant que le JavaScript s'y accroche ; on leur ajoute la classe nouvelle et on déplace le style. Aucun sélecteur d'`#id` ne subsiste dans le CSS final.

| Existant | Remplacé par | Note |
|---|---|---|
| `#bfQuickModeWrapper` / `Left` / `Right` | `.bfng-split` + `__aside` / `__main` | Supprime les `float` et le `padding-left: 310px`. |
| `.bfPropertyWrap` / `.bfPropertyLabel` | `.bfng-field` + `__label` | Le liseré de survol `#0088CC` devient `accent-500` et n'apparaît qu'au focus interne. |
| `#menutab .nav-link` (aplat `#0088CC`) | `.bfng-tabs__tab` | Retire les cinq `!important` et la hauteur figée de 37 px. |
| `.bfLanguageButton` (bleu / rouge) | `.bfng-seg__opt` | Le rouge d'état actif n'est plus un signal d'erreur. |
| `#bfAvailableFieldsOpen` / `#bfSearchOpen` (`#faa732`) | `.bfng-btn` + `.bfng-panel` | L'orange disparaît ; les panneaux en position absolue deviennent des tiroirs en flux. |
| `.bfAvailableField` (pavés `#2d89ef`) | `.bfng-palette__item` | Grille de cartes bordées avec icône de famille et libellé, au lieu de tuiles bleues pleines. |
| `.bfDetailsTable*` (entête `#92c1ff`) | `.bfng-table` + `.bfng-dialog` | Le zébrage `#F5F5F5` et le `strong` gris clair partent au profit des jetons de texte. |
| `#bfSaveQueue` (Verdana 11 px) | `.bfng-message--info` | Largeur fixe de 350 px supprimée ; le message vit dans la barre d'outils. |
| `.bfFadingMessage` | `.bfng-message` | Les erreurs ne s'effacent plus automatiquement. |
| `images/quickmode/*.png` | jeu Lucide + `--bfng-kind-*` | Icônes vectorielles teintées par famille, compatibles mode sombre. |

## 08 — Implémentation

Trois fichiers seulement, chargés comme des ressources Joomla déclarées dans `media/com_breezingformsng/joomla.asset.json`. `bfng-tokens` est la dépendance de tout le reste.

```
media/com_breezingformsng/css/
├── bfng-tokens.css   · jetons clairs + sombres, aucune règle de composant
├── bfng-admin.css    · composants + les 5 écrans admin
└── bfng-site.css     · .bfng-form (frontend)

// joomla.asset.json
{ "name": "com_breezingformsng.tokens", "type": "style",
  "uri": "media/com_breezingformsng/css/bfng-tokens.css", "version": "auto" },
{ "name": "com_breezingformsng.admin-style", "type": "style",
  "uri": "media/com_breezingformsng/css/bfng-admin.css",
  "dependencies": ["com_breezingformsng.tokens"], "version": "auto" }
```

### Socle de jetons, à copier tel quel

```css
.bfng {
  /* surfaces — héritées du template, replis calibrés Atum */
  --bfng-bg:              var(--bs-body-bg, #f4f6f8);
  --bfng-surface:         var(--card-bg, #fff);
  --bfng-surface-2:       var(--bs-tertiary-bg, #eef1f5);
  --bfng-surface-3:       #e4e8ee;
  --bfng-border:          var(--border-color, #dfe3e7);
  --bfng-border-strong:   #c3cad3;

  /* texte */
  --bfng-text:            var(--bs-body-color, #1f2933);
  --bfng-text-2:          #4b5763;
  --bfng-text-muted:      var(--bs-secondary-color, #7b8794);

  /* accent — une seule teinte */
  --bfng-accent-500:      var(--template-link-color, #2f70b9);
  --bfng-accent-600:      #275f9e;
  --bfng-accent-700:      #1d4a7c;
  --bfng-accent-100:      color-mix(in oklab, var(--bfng-accent-500) 14%, #fff);
  --bfng-accent-50:       color-mix(in oklab, var(--bfng-accent-500) 7%,  #fff);

  /* états */
  --bfng-success: #1f7a52;  --bfng-warning: #96650f;  --bfng-danger: #a8332c;

  /* familles d'éléments */
  --bfng-kind-struct: var(--bfng-accent-500);  --bfng-kind-input: #0d9488;
  --bfng-kind-logic:  #7c3aed;                 --bfng-kind-static: #c2610c;

  /* géométrie */
  --bfng-space-1: 2px;   --bfng-space-2: 4px;   --bfng-space-3: 6px;   --bfng-space-4: 8px;
  --bfng-space-5: 12px;  --bfng-space-6: 16px;  --bfng-space-7: 24px;  --bfng-space-8: 32px;
  --bfng-control-h: 30px;   --bfng-control-h-sm: 26px;
  --bfng-row-h: 34px;       --bfng-tree-row-h: 26px;   --bfng-tree-indent: 16px;
  --bfng-radius: 4px;       --bfng-radius-sm: 3px;     --bfng-radius-lg: 6px;

  /* type */
  --bfng-font-ui:   var(--bs-body-font-family, system-ui, sans-serif);
  --bfng-font-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  --bfng-fs-xs: 11px; --bfng-fs-sm: 12px; --bfng-fs-base: 13px;
  --bfng-fs-md: 14px; --bfng-fs-lg: 16px; --bfng-fs-xl: 20px;

  /* élévation, focus, temps */
  --bfng-shadow-1: 0 1px 2px rgb(16 24 40 / 6%);
  --bfng-shadow-2: 0 6px 16px rgb(16 24 40 / 10%);
  --bfng-shadow-3: 0 16px 40px rgb(16 24 40 / 18%);
  --bfng-focus-ring: 0 0 0 3px color-mix(in oklab, var(--bfng-accent-500) 32%, transparent);
  --bfng-t: 120ms ease;

  color: var(--bfng-text);
  font-family: var(--bfng-font-ui);
  font-size: var(--bfng-fs-base);
}

[data-bs-theme="dark"] .bfng,
.bfng[data-bs-theme="dark"] {
  --bfng-bg: #131720;              --bfng-surface: #1a1f29;
  --bfng-surface-2: #212834;       --bfng-surface-3: #2a323f;
  --bfng-border: #333c4a;          --bfng-border-strong: #46515f;
  --bfng-text: #e7ebf0;            --bfng-text-2: #b6c0cc;   --bfng-text-muted: #8892a0;
  --bfng-accent-500: #6ea8e2;      --bfng-accent-600: #8bbdea;  --bfng-accent-700: #a9cff2;
  --bfng-accent-100: color-mix(in oklab, var(--bfng-accent-500) 20%, transparent);
  --bfng-accent-50:  color-mix(in oklab, var(--bfng-accent-500) 12%, transparent);
  --bfng-success: #4fb888;         --bfng-warning: #dba449;   --bfng-danger: #e0736a;
  --bfng-kind-input: #2dd4bf;      --bfng-kind-logic: #a78bfa;  --bfng-kind-static: #fb923c;
  --bfng-shadow-1: 0 1px 2px rgb(0 0 0 / 40%);
  --bfng-shadow-2: 0 6px 16px rgb(0 0 0 / 48%);
  --bfng-shadow-3: 0 16px 40px rgb(0 0 0 / 56%);
}

/* focus unique, jamais l'anneau bleu par défaut */
.bfng :is(a, button, input, select, textarea, [tabindex]):focus-visible {
  outline: 2px solid var(--bfng-accent-500);
  outline-offset: 1px;
  box-shadow: var(--bfng-focus-ring);
}
```

### Deux composants de référence — les autres suivent la même forme

```css
.bfng-btn {
  display: inline-flex; align-items: center; gap: var(--bfng-space-3);
  height: var(--bfng-control-h); padding: 0 var(--bfng-space-5);
  border: 1px solid var(--bfng-border-strong); border-radius: var(--bfng-radius);
  background: var(--bfng-surface); color: var(--bfng-text);
  font: 500 var(--bfng-fs-sm)/1 var(--bfng-font-ui);
  cursor: pointer; transition: background var(--bfng-t), border-color var(--bfng-t);
}
.bfng-btn:hover         { background: var(--bfng-surface-2); border-color: var(--bfng-accent-500); }
.bfng-btn:active         { background: var(--bfng-accent-50); border-color: var(--bfng-accent-700); }
.bfng-btn[disabled]      { opacity: .45; pointer-events: none; }
.bfng-btn--primary       { border-color: var(--bfng-accent-500); color: var(--bfng-accent-700); font-weight: 600; }
.bfng-btn--primary:hover { background: var(--bfng-accent-50); border-color: var(--bfng-accent-600); }
.bfng-btn--ghost         { border-color: transparent; background: transparent; color: var(--bfng-text-2); }
.bfng-btn--danger        { border-color: var(--bfng-danger); color: var(--bfng-danger); }
.bfng-btn--icon          { width: var(--bfng-control-h); padding: 0; justify-content: center; }

.bfng-tree__row {
  display: flex; align-items: center; gap: var(--bfng-space-3);
  height: var(--bfng-tree-row-h);
  padding-inline: var(--bfng-space-5) var(--bfng-space-4);
  padding-inline-start: calc(var(--bfng-space-5) + var(--level, 0) * var(--bfng-tree-indent));
  font-size: var(--bfng-fs-base); color: var(--bfng-text);
  border-inline-start: 2px solid transparent; cursor: default; user-select: none;
}
.bfng-tree__row:hover                    { background: var(--bfng-surface-3); }
.bfng-tree__row.is-selected              { background: var(--bfng-accent-50); border-inline-start-color: var(--bfng-accent-500); font-weight: 600; }
.bfng-tree__row.is-drop-target           { box-shadow: inset 0 -2px 0 var(--bfng-accent-500); }
.bfng-tree__icon                         { color: var(--bfng-kind-static); flex: none; }
.bfng-tree__row[data-kind="struct"] .bfng-tree__icon { color: var(--bfng-kind-struct); }
.bfng-tree__row[data-kind="input"]  .bfng-tree__icon { color: var(--bfng-kind-input); }
.bfng-tree__row[data-kind="logic"]  .bfng-tree__icon { color: var(--bfng-kind-logic); }
```

## 09 — Contrôle avant fusion

- ✔ Aucun `!important` ajouté ; ceux d'`admin.css` retirés.
- ✔ Aucun code couleur hors de `bfng-tokens.css`.
- ✔ Chaque règle préfixée par `.bfng` ou une classe d'écran.
- ✔ Aucun sélecteur d'`#id` dans le CSS.
- ✔ Les six écrans relus en clair puis en `data-bs-theme="dark"`.
- ✔ Contraste ≥ 4,5:1 pour le texte, ≥ 3:1 pour les bordures actives.
- ✔ Aucune information portée par la seule couleur (icône ou texte en doublon).
- ✔ Parcours clavier complet dans l'arbre et l'inspecteur, anneau de focus visible.
- ✔ Pas de débordement horizontal à 1280 px ; volets réduits sous 1024 px.
- ✔ Cibles tactiles frontend ≥ 44 px.
- ✔ Surcharges de template Joomla toujours effectives.
- ✔ `about.css`, `quickmode.all.css` et `tree_component.css` retirés du chargement admin.

---

*BreezingFormsNG — charte graphique v1.0 · `vcmb-cyclo/com_breezingformsng` · `main`*
