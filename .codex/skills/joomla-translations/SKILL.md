---

name: joomla-translations
description: >
  Use this skill whenever creating, editing, or reviewing any user-facing
  string in a Joomla 6 extension: component, module, plugin or template.
  This includes administrator field labels and descriptions, form and XML
  labels, toolbar buttons, error and success messages, tooltips, strings
  exposed to JavaScript through Text::script(), and email templates.
  Trigger this skill whenever editing a language .ini file, adding or changing
  a Text::_(), Text::sprintf() or Text::plural() call, modifying an XML field
  label, description or hint in an XML manifest or form, or handling any
  translation request. Always update en-GB, fr-FR, de-DE, es-ES, hu-HU,
  it-IT, nl-NL and tr-TR together. Keep the semantic content strictly aligned
  across all eight languages and apply the linguistic, grammatical and
  typographical conventions of each locale.
---

# Traductions Joomla 6

Langues prises en charge :

* `en-GB`
* `fr-FR`
* `de-DE`
* `es-ES`
* `hu-HU`
* `it-IT`
* `nl-NL`
* `tr-TR`

## Quand s’applique cette skill

Cette skill s’applique dès qu’une chaîne destinée à l’utilisateur est créée,
modifiée ou examinée :

* libellés de champs ;
* descriptions ;
* infobulles ;
* textes d’aide ;
* messages d’erreur ou de succès ;
* boutons de barre d’outils ;
* libellés XML ;
* placeholders ;
* notifications ;
* modèles d’e-mails ;
* chaînes exposées au JavaScript avec `Text::script()`.

Elle s’applique notamment à toute modification concernant :

```php
Text::_('COM_EXAMPLE_KEY');
Text::sprintf('COM_EXAMPLE_KEY', $value);
Text::plural('COM_EXAMPLE_N_ITEMS', $count);
Text::script('COM_EXAMPLE_KEY');
```

Elle s’applique également aux attributs XML suivants :

```xml
<field
    name="example"
    label="COM_EXAMPLE_FIELD_EXAMPLE_LABEL"
    description="COM_EXAMPLE_FIELD_EXAMPLE_DESC"
    hint="COM_EXAMPLE_FIELD_EXAMPLE_HINT"
/>
```

## Règle d’or

**Une clé de langue ne doit jamais être ajoutée, modifiée ou supprimée dans une
seule langue.**

Les huit variantes linguistiques doivent être traitées dans le même tour de
modification :

```text
en-GB
fr-FR
de-DE
es-ES
hu-HU
it-IT
nl-NL
tr-TR
```

Les huit traductions doivent exprimer un sens strictement équivalent.

Une modification est incomplète si une clé :

* manque dans une langue ;
* conserve une ancienne signification dans une langue ;
* est traduite par une simple copie de la valeur anglaise ;
* contient des paramètres différents ;
* utilise une terminologie incohérente avec les autres chaînes de l’extension.

## 1. Emplacement des fichiers

### Administration

```text
admin/language/en-GB/en-GB.com_xxx.ini
admin/language/en-GB/en-GB.com_xxx.sys.ini

admin/language/fr-FR/fr-FR.com_xxx.ini
admin/language/fr-FR/fr-FR.com_xxx.sys.ini

admin/language/de-DE/de-DE.com_xxx.ini
admin/language/de-DE/de-DE.com_xxx.sys.ini

admin/language/es-ES/es-ES.com_xxx.ini
admin/language/es-ES/es-ES.com_xxx.sys.ini

admin/language/hu-HU/hu-HU.com_xxx.ini
admin/language/hu-HU/hu-HU.com_xxx.sys.ini

admin/language/it-IT/it-IT.com_xxx.ini
admin/language/it-IT/it-IT.com_xxx.sys.ini

admin/language/nl-NL/nl-NL.com_xxx.ini
admin/language/nl-NL/nl-NL.com_xxx.sys.ini

admin/language/tr-TR/tr-TR.com_xxx.ini
admin/language/tr-TR/tr-TR.com_xxx.sys.ini
```

### Site

```text
site/language/en-GB/en-GB.com_xxx.ini
site/language/fr-FR/fr-FR.com_xxx.ini
site/language/de-DE/de-DE.com_xxx.ini
site/language/es-ES/es-ES.com_xxx.ini
site/language/hu-HU/hu-HU.com_xxx.ini
site/language/it-IT/it-IT.com_xxx.ini
site/language/nl-NL/nl-NL.com_xxx.ini
site/language/tr-TR/tr-TR.com_xxx.ini
```

### Rôle des fichiers

Les fichiers `*.sys.ini` contiennent uniquement les chaînes nécessaires avant
le chargement normal de l’extension, notamment :

* le nom de l’extension ;
* sa description ;
* les noms de menus d’administration ;
* certaines chaînes du manifeste d’installation.

Les fichiers `*.ini` sans suffixe `.sys` contiennent toutes les chaînes utilisées
pendant l’exécution normale de l’extension.

Ne pas placer arbitrairement une même clé dans les deux fichiers.

## 2. Convention de nommage des clés

Format général :

```text
COM_<EXTENSION>_<CONTEXTE>_<ÉLÉMENT>
```

Les clés doivent utiliser :

* uniquement des lettres latines non accentuées ;
* des majuscules ;
* des chiffres si nécessaire ;
* des underscores ;
* aucun espace ;
* aucun tiret ;
* aucun point.

Exemples :

```ini
COM_CONTENTBUILDERNG_FIELD_API_KEY_LABEL="API key"
COM_CONTENTBUILDERNG_FIELD_API_KEY_DESC="Your Anthropic API key, used for completions."
COM_CONTENTBUILDERNG_ERROR_INVALID_KEY="The provided API key is invalid."
COM_CONTENTBUILDERNG_TOOLBAR_REINDEX="Reindex"
COM_CONTENTBUILDERNG_N_ITEMS_INDEXED_1="%d item indexed"
COM_CONTENTBUILDERNG_N_ITEMS_INDEXED_MORE="%d items indexed"
```

Suffixes recommandés :

* `_LABEL` : libellé d’un champ ;
* `_DESC` : description ou infobulle ;
* `_HINT` : placeholder ou exemple de saisie ;
* `_TITLE` : titre de vue ou de panneau ;
* `_HEADING` : en-tête de tableau ;
* `_OPTION` : valeur affichée dans une liste ;
* `_BUTTON` : bouton hors barre d’outils ;
* `_TOOLBAR` : action de barre d’outils ;
* `_SUCCESS` : confirmation de réussite ;
* `_ERROR` : message d’erreur ;
* `_WARNING` : avertissement ;
* `_INFO` : information ;
* `_N_ITEMS_1` et `_N_ITEMS_MORE` : formes utilisées par le mécanisme de
  pluriel de Joomla.

Ne pas créer deux clés différentes pour une même notion uniquement parce que la
traduction diffère d’une langue à l’autre.

## 3. Alignement sémantique entre les langues

Pour chaque clé, les huit traductions doivent :

* exprimer exactement la même information ;
* conserver le même niveau de précision ;
* conserver le même niveau de certitude ;
* conserver le même registre ;
* conserver la même distinction entre obligation, conseil et possibilité ;
* conserver les mêmes informations techniques ;
* conserver les mêmes noms de produits, de formats et de protocoles ;
* préserver tous les placeholders.

Ne pas ajouter dans une langue une précision absente des autres.

Ne pas supprimer une restriction, une condition ou une conséquence dans une
traduction.

### Placeholders

Tous les placeholders doivent être présents dans les huit langues :

```text
%s
%d
%1$s
%2$d
```

Utiliser des placeholders positionnels dès qu’une chaîne comporte plusieurs
arguments :

```ini
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s items reindexed in %2$s seconds"
```

Exemples alignés :

```ini
; en-GB
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s items reindexed in %2$s seconds"

; fr-FR
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s éléments réindexés en %2$s secondes"

; de-DE
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s Elemente wurden in %2$s Sekunden neu indiziert"

; es-ES
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s elementos reindexados en %2$s segundos"

; hu-HU
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s elem újraindexelése %2$s másodperc alatt befejeződött"

; it-IT
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s elementi reindicizzati in %2$s secondi"

; nl-NL
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s items opnieuw geïndexeerd in %2$s seconden"

; tr-TR
COM_CONTENTBUILDERNG_MSG_REINDEXED="%1$s öğe %2$s saniyede yeniden dizine eklendi"
```

Même lorsque l’ordre naturel des mots change, les index `%1$s`, `%2$s` et
suivants doivent continuer à désigner les mêmes données.

### Terminologie

Utiliser un vocabulaire cohérent dans toute l’extension.

Une notion technique doit recevoir une traduction stable. Par exemple, ne pas
alterner sans raison entre :

* « enregistrement », « élément » et « entrée » ;
* « indexer » et « référencer » ;
* « supprimer » et « effacer » ;
* « save », « apply » et « submit » pour une même action ;
* « record », « item » et « entry » pour un même objet métier.

Lorsque Joomla fournit déjà une clé générique adaptée, privilégier cette clé
plutôt que d’ajouter une traduction propre à l’extension :

```php
Text::_('JGLOBAL_SAVE');
Text::_('JCANCEL');
Text::_('JDELETE');
Text::_('JSEARCH_FILTER');
```

Ne réutiliser une clé du cœur que si son sens correspond exactement à l’action.

## 4. Style général des interfaces

Toutes les langues doivent respecter les principes suivants :

* employer une formulation concise ;
* préférer une phrase directe ;
* éviter les formulations commerciales ou emphatiques ;
* ne pas terminer les libellés, boutons ou en-têtes par un point ;
* terminer les descriptions et messages complets par une ponctuation adaptée ;
* éviter les majuscules intégrales, sauf pour les acronymes ;
* ne pas ajouter de deux-points à un libellé si Joomla ou le template les ajoute
  automatiquement ;
* employer le même terme pour une même action ;
* ne pas traduire les noms de produits, bibliothèques, protocoles ou formats ;
* ne pas traduire les extensions de fichiers ;
* ne pas modifier les valeurs techniques affichées à l’utilisateur.

Exemples de termes généralement conservés :

```text
Joomla
PHP
JavaScript
API
JSON
XML
CSV
GeoJSON
GeoPackage
OAuth
OpenID Connect
URL
HTML
CSS
SQL
```

Les acronymes peuvent recevoir une explication traduite, mais leur forme
technique doit rester stable.

## 5. Anglais britannique — en-GB

`en-GB` constitue la source de vérité sémantique.

### Orthographe

Employer l’anglais britannique :

```text
authorise
behaviour
colour
customise
initialise
licence
organisation
optimise
synchronise
```

Ne pas mélanger avec l’orthographe américaine :

```text
authorize
behavior
color
customize
initialize
license
organization
optimize
synchronize
```

Exceptions :

* conserver l’orthographe officielle d’un nom de produit ;
* conserver les noms exacts d’API, de classes, de méthodes et de paramètres ;
* ne pas modifier une commande ou une valeur de configuration.

### Capitalisation

Utiliser le style phrase, et non le style titre :

```ini
COM_EXAMPLE_FIELD_API_KEY_LABEL="API key"
COM_EXAMPLE_CONFIG_ADVANCED_OPTIONS="Advanced options"
```

Éviter :

```ini
COM_EXAMPLE_FIELD_API_KEY_LABEL="API Key"
COM_EXAMPLE_CONFIG_ADVANCED_OPTIONS="Advanced Options"
```

Les noms propres et acronymes conservent leurs majuscules.

### Formulations

Préférer :

```text
Save the changes.
Select an item.
The file could not be uploaded.
No matching records were found.
```

Éviter les formulations inutilement longues :

```text
Please click here in order to save all of the changes that you have made.
```

Ne pas employer systématiquement `please` dans les instructions d’interface.

### Guillemets et ponctuation

Utiliser les guillemets anglais typographiques lorsque le texte l’exige :

```text
“Example”
```

Les apostrophes typographiques peuvent être utilisées :

```text
The item’s identifier
```

Toutefois, ne pas remplacer les délimiteurs INI :

```ini
COM_EXAMPLE_MESSAGE="The item’s identifier is invalid."
```

## 6. Français — fr-FR

### Registre

Employer le vouvoiement dans les messages adressés directement à
l’utilisateur.

Préférer une formulation impersonnelle lorsque cela rend le message plus
concis :

```text
Sélectionnez un élément.
Le fichier n’a pas pu être importé.
Aucun résultat correspondant n’a été trouvé.
```

Éviter le tutoiement dans l’administration :

```text
Choisis un élément.
Entre ton mot de passe.
```

### Typographie

Utiliser une espace fine insécable avant :

```text
:
;
?
!
```

Dans un fichier INI, employer `&#8239;` lorsque la chaîne est rendue comme du
HTML et que l’entité est correctement interprétée :

```ini
COM_EXAMPLE_CONFIRM_DELETE="Voulez-vous supprimer cet élément&#8239;?"
```

Lorsque le contexte ne permet pas de garantir l’interprétation HTML, employer
une espace insécable normale ou un caractère Unicode approprié.

Ne pas introduire littéralement `&nbsp;` ou `&#8239;` dans un contexte où la
chaîne sera échappée et affichée telle quelle.

### Guillemets

Utiliser les guillemets français avec espaces insécables internes :

```text
« Exemple »
```

Éviter les guillemets droits dans le texte affiché :

```text
"Exemple"
```

Les guillemets droits restent les délimiteurs syntaxiques du fichier INI :

```ini
COM_EXAMPLE_MESSAGE="Le mode « lecture seule » est activé."
```

### Capitalisation

Employer le style phrase :

```text
Clé API
Options avancées
Date de création
Adresse e-mail
```

Éviter :

```text
Clé Api
Options Avancées
Date De Création
Adresse E-Mail
```

Les majuscules accentuées sont obligatoires :

```text
Élément
À propos
Échec
```

### Ponctuation et espaces

Ne pas placer d’espace avant :

```text
,
.
)
]
```

Placer une espace après une virgule ou un point lorsque la phrase continue.

Employer les points de suspension typographiques :

```text
…
```

plutôt que :

```text
...
```

sauf contrainte technique explicite.

### Terminologie

Préférer les termes français usuels :

```text
téléverser ou envoyer un fichier
télécharger
enregistrement
paramètres
identifiant
mot de passe
adresse e-mail
case à cocher
liste déroulante
```

Choisir entre « téléverser » et « envoyer un fichier » selon la terminologie
déjà utilisée dans la traduction française de Joomla.

Éviter les anglicismes inutiles :

```text
uploader
downloader
checker
resetter
customiser
```

Conserver toutefois les termes techniques sans équivalent établi lorsque leur
traduction nuirait à la compréhension.

### Infinitif et impératif

Les boutons utilisent généralement un infinitif ou un nom d’action :

```text
Enregistrer
Supprimer
Réindexer
Fermer
Aperçu
```

Les instructions utilisent l’impératif à la deuxième personne du pluriel :

```text
Sélectionnez un fichier.
Saisissez une adresse e-mail.
```

## 7. Allemand — de-DE

### Registre

Employer un registre formel.

Lorsque l’utilisateur est directement interpellé, utiliser `Sie` et les formes
associées :

```text
Wählen Sie eine Datei aus.
Geben Sie Ihre E-Mail-Adresse ein.
```

Ne pas utiliser `du`, `dein` ou `deine` dans une interface d’administration.

Lorsque c’est possible, employer une formulation impersonnelle et concise :

```text
Die Datei konnte nicht importiert werden.
Keine passenden Einträge gefunden.
```

### Capitalisation

Tous les noms communs allemands commencent par une majuscule :

```text
Datei
Einstellung
Benutzer
Beschreibung
Fehlermeldung
```

Les verbes, adjectifs et adverbes restent normalement en minuscules.

Les libellés suivent le style phrase :

```text
Erweiterte Einstellungen
Datum der letzten Änderung
```

Éviter de capitaliser chaque mot sur le modèle anglais.

### Orthographe

Employer l’orthographe allemande standard :

```text
E-Mail-Adresse
Benutzeroberfläche
Dateigröße
ausgewählt
gelöscht
```

Utiliser correctement :

```text
ä
ö
ü
ß
```

Ne pas les remplacer par `ae`, `oe`, `ue` ou `ss`, sauf contrainte technique
portant sur une valeur non affichée.

### Guillemets

Employer de préférence les guillemets allemands :

```text
„Beispiel“
```

Les guillemets français peuvent apparaître dans certains usages éditoriaux,
mais une seule convention doit être conservée dans l’ensemble de l’extension.

### Mots composés

Respecter les mots composés allemands :

```text
Dateiformat
Benutzerkonto
Spracheinstellung
Zugriffsberechtigung
```

Ne pas séparer artificiellement les composants sur le modèle anglais.

### Formulations

Préférer des verbes précis :

```text
speichern
löschen
auswählen
hochladen
herunterladen
aktualisieren
zurücksetzen
```

Les boutons utilisent généralement l’infinitif :

```text
Speichern
Löschen
Schließen
Erneut indizieren
```

### Ordre des paramètres

L’allemand peut exiger un ordre syntaxique différent. Utiliser systématiquement
des placeholders positionnels lorsqu’une chaîne contient plusieurs arguments.

## 8. Espagnol — es-ES

### Variante linguistique

Employer l’espagnol d’Espagne.

Éviter de mélanger arbitrairement les variantes d’Espagne et d’Amérique latine.

Préférer notamment la terminologie cohérente avec les traductions espagnoles de
Joomla.

### Registre

Employer un registre neutre et professionnel.

Dans les instructions, utiliser généralement l’impératif formel :

```text
Seleccione un archivo.
Introduzca su dirección de correo electrónico.
```

Éviter le tutoiement :

```text
Selecciona un archivo.
Introduce tu contraseña.
```

Une formulation impersonnelle peut être préférable :

```text
No se ha podido importar el archivo.
No se han encontrado registros coincidentes.
```

### Accents

Respecter tous les accents et signes diacritiques :

```text
configuración
descripción
información
índice
válido
última modificación
```

Ne pas omettre les accents sur les majuscules :

```text
Índice
Última modificación
```

### Ponctuation interrogative et exclamative

Les questions et exclamations complètes doivent utiliser les signes ouvrants et
fermants :

```text
¿Desea eliminar este elemento?
¡La operación se ha completado correctamente!
```

Ne pas écrire :

```text
Desea eliminar este elemento?
```

### Capitalisation

Employer le style phrase :

```text
Clave de API
Opciones avanzadas
Fecha de creación
```

Éviter :

```text
Clave De API
Opciones Avanzadas
Fecha De Creación
```

### Guillemets

Employer de préférence les guillemets espagnols :

```text
«Ejemplo»
```

Les guillemets anglais typographiques peuvent être utilisés au second niveau :

```text
«El modo “solo lectura” está activado»
```

### Terminologie

Préférer les termes établis :

```text
guardar
eliminar
archivo
subir
descargar
ajustes
registro
correo electrónico
contraseña
casilla de verificación
lista desplegable
```

Éviter les anglicismes lorsqu’un équivalent espagnol courant existe.

### Boutons

Les boutons utilisent généralement l’infinitif :

```text
Guardar
Eliminar
Cerrar
Reindexar
```

## 9. Hongrois — hu-HU

### Registre

Employer un registre neutre, professionnel et respectueux.

Le hongrois permet souvent d’éviter l’interpellation directe. Préférer les
formulations impersonnelles dans les messages :

```text
A fájl nem importálható.
Nem található megfelelő rekord.
```

Dans les instructions, utiliser une formulation polie et cohérente :

```text
Válasszon ki egy fájlt.
Adja meg az e-mail-címét.
```

Ne pas alterner sans raison entre une forme impersonnelle et une interpellation
directe.

### Accents

Respecter strictement les voyelles hongroises :

```text
á
é
í
ó
ö
ő
ú
ü
ű
```

La distinction entre `ö` et `ő`, ainsi qu’entre `ü` et `ű`, est obligatoire.

Exemples :

```text
beállítás
művelet
érvényes
felhasználó
törlés
```

### Ordre des mots

Ne pas calquer l’ordre des mots anglais.

L’ordre hongrois dépend fortement de la focalisation et du contexte. La
traduction doit rester naturelle tout en conservant exactement le même sens.

Utiliser des placeholders positionnels dès qu’une phrase contient plusieurs
arguments.

### Suffixes et harmonie vocalique

Les suffixes hongrois doivent respecter l’harmonie vocalique.

Éviter de construire des fragments traduits en concaténant dynamiquement un nom
et un suffixe dans le code.

Préférer une phrase complète dans le fichier de langue.

À éviter :

```php
Text::_('COM_EXAMPLE_FILE') . $count . Text::_('COM_EXAMPLE_SUFFIX');
```

À préférer :

```php
Text::plural('COM_EXAMPLE_N_FILES', $count);
```

### Capitalisation

Employer le style phrase.

En hongrois, seuls le premier mot et les noms propres prennent généralement une
majuscule dans les titres et libellés :

```text
Speciális beállítások
Létrehozás dátuma
API-kulcs
```

Éviter de capitaliser chaque mot.

### Termes composés

Respecter l’orthographe hongroise des mots composés et l’usage des traits
d’union :

```text
e-mail-cím
API-kulcs
felhasználónév
jelölőnégyzet
legördülő lista
```

### Guillemets

Employer les guillemets hongrois :

```text
„Példa”
```

### Boutons

Employer une forme d’action concise et cohérente :

```text
Mentés
Törlés
Bezárás
Újraindexelés
```

## 10. Italien — it-IT

### Registre

Employer un registre professionnel et cohérent.

Préférer les formulations impersonnelles dans les messages :

```text
Non è stato possibile importare il file.
Non è stato trovato alcun elemento corrispondente.
```

Dans les instructions adressées directement à l’utilisateur, employer la forme
de politesse :

```text
Selezioni un file.
Inserisca il suo indirizzo e-mail.
```

Toutefois, si les traductions Joomla italiennes du contexte utilisent
systématiquement l’infinitif d’instruction, conserver cette convention.

### Accents et apostrophes

Respecter les accents :

```text
è
perché
più
validità
attività
```

Ne pas écrire :

```text
e'
perche'
piu'
```

Employer l’apostrophe typographique dans le texte affiché lorsque cela est
possible :

```text
L’elemento
L’impostazione
Un’opzione
```

### Capitalisation

Employer le style phrase :

```text
Chiave API
Opzioni avanzate
Data di creazione
```

Éviter :

```text
Chiave API
Opzioni Avanzate
Data Di Creazione
```

### Guillemets

Employer les guillemets italiens :

```text
«Esempio»
```

Les guillemets anglais typographiques peuvent être utilisés au second niveau.

### Articles et prépositions

Respecter les articles contractés :

```text
del
della
dell’
nel
nella
all’
```

Ne pas produire de constructions artificielles issues d’une concaténation de
fragments.

### Terminologie

Préférer les termes italiens établis :

```text
salvare
eliminare
file
caricare
scaricare
impostazioni
record
indirizzo e-mail
password
casella di controllo
elenco a discesa
```

Le mot `file` est invariable en italien :

```text
un file
due file
```

Ne pas écrire `files`.

### Boutons

Les boutons utilisent généralement l’infinitif :

```text
Salvare
Eliminare
Chiudere
Reindicizzare
```

## 11. Néerlandais — nl-NL

### Variante linguistique

Employer le néerlandais des Pays-Bas.

Éviter de mélanger sans nécessité les variantes néerlandaises et belges.

### Registre

Employer un registre professionnel et neutre.

Dans les instructions, utiliser une formulation directe :

```text
Selecteer een bestand.
Voer uw e-mailadres in.
```

Utiliser `u` et `uw` lorsque l’utilisateur est directement interpellé.

Ne pas utiliser `jij`, `je` ou `jouw` dans une interface d’administration, sauf
si cette convention est explicitement établie dans le projet.

Les messages peuvent être impersonnels :

```text
Het bestand kon niet worden geïmporteerd.
Er zijn geen overeenkomende records gevonden.
```

### Capitalisation

Employer le style phrase :

```text
API-sleutel
Geavanceerde instellingen
Aanmaakdatum
```

Éviter de capitaliser tous les mots.

### Mots composés

Le néerlandais emploie fréquemment des mots composés :

```text
gebruikersaccount
toegangsrechten
bestandsnaam
taalinstelling
foutmelding
```

Ne pas séparer artificiellement les composants sur le modèle anglais.

Employer un trait d’union lorsqu’il est requis avec un acronyme :

```text
API-sleutel
CSV-bestand
Joomla-extensie
```

### Accents et trémas

Respecter les signes diacritiques nécessaires :

```text
geïmporteerd
geïndexeerd
e-mailadres
```

### Guillemets

Employer de préférence les guillemets typographiques :

```text
‘Voorbeeld’
```

ou :

```text
“Voorbeeld”
```

Choisir une convention et la conserver dans toute l’extension.

### Terminologie

Préférer les termes établis :

```text
opslaan
verwijderen
bestand
uploaden
downloaden
instellingen
record
e-mailadres
wachtwoord
selectievakje
vervolgkeuzelijst
```

Certains emprunts anglais comme `uploaden` et `downloaden` sont courants en
néerlandais. Ne pas les remplacer par une construction artificielle si la
terminologie Joomla les emploie déjà.

### Boutons

Employer généralement l’infinitif :

```text
Opslaan
Verwijderen
Sluiten
Opnieuw indexeren
```

## 12. Turc — tr-TR

### Registre

Employer un registre professionnel et respectueux.

Dans les instructions adressées directement à l’utilisateur, employer une forme
polie :

```text
Bir dosya seçin.
E-posta adresinizi girin.
```

Les messages peuvent être impersonnels :

```text
Dosya içe aktarılamadı.
Eşleşen kayıt bulunamadı.
```

### Alphabet turc

Respecter strictement les caractères turcs :

```text
ç
ğ
ı
İ
ö
ş
ü
```

Faire particulièrement attention à la distinction entre :

```text
i
İ
ı
I
```

Ne pas appliquer naïvement une conversion de casse conçue pour l’anglais.

Exemples corrects :

```text
İçe aktar
İndirme
Kullanıcı
Başarılı
Geçersiz
```

### Capitalisation

Employer le style phrase.

Les règles de casse turques doivent être respectées :

```text
API anahtarı
Gelişmiş ayarlar
Oluşturma tarihi
```

Ne pas capitaliser tous les mots sur le modèle anglais.

### Harmonie vocalique et suffixes

Les suffixes turcs varient selon l’harmonie vocalique et la consonne finale.

Ne pas concaténer dans le code une racine traduite et un suffixe supposé
universel.

À éviter :

```php
Text::_('COM_EXAMPLE_ITEM') . $count . Text::_('COM_EXAMPLE_SUFFIX');
```

À préférer :

```php
Text::plural('COM_EXAMPLE_N_ITEMS', $count);
```

Les suffixes ajoutés à un nom propre ou à un acronyme peuvent nécessiter une
apostrophe selon les règles turques. Éviter les assemblages dynamiques qui
empêchent une traduction grammaticale correcte.

### Ordre des mots

Le turc place généralement le verbe à la fin de la phrase.

Ne pas calquer l’ordre des mots anglais.

Utiliser des placeholders positionnels lorsqu’une phrase contient plusieurs
arguments :

```ini
COM_EXAMPLE_PROCESSED="%1$s kayıt %2$s saniyede işlendi"
```

### Guillemets

Employer les guillemets typographiques :

```text
“Örnek”
```

Une convention cohérente doit être conservée dans toute l’extension.

### Terminologie

Préférer les termes turcs établis :

```text
kaydet
sil
dosya
yükle
indir
ayarlar
kayıt
e-posta adresi
parola
onay kutusu
açılır liste
```

### Boutons

Employer une forme impérative concise :

```text
Kaydet
Sil
Kapat
Yeniden dizine ekle
```

## 13. Chaînes JavaScript

Une clé utilisée en JavaScript doit être déclarée côté PHP avant son utilisation.

### PHP

```php
use Joomla\CMS\Language\Text;

Text::script('COM_CONTENTBUILDERNG_ERROR_INVALID_KEY');
```

### JavaScript

```javascript
const message = Joomla.Text._(
    'COM_CONTENTBUILDERNG_ERROR_INVALID_KEY'
);
```

Ne pas coder directement une chaîne utilisateur dans JavaScript :

```javascript
alert('Invalid API key');
```

Employer :

```javascript
alert(
    Joomla.Text._('COM_CONTENTBUILDERNG_ERROR_INVALID_KEY')
);
```

Vérifier que la clé est présente dans les huit fichiers de langue concernés.

Si le JavaScript est utilisé à la fois sur le site et dans l’administration,
vérifier que la clé est chargée dans chacun des contextes concernés.

## 14. Pluriels

Utiliser `Text::plural()` lorsque la chaîne dépend d’un nombre.

Exemple PHP :

```php
$message = Text::plural(
    'COM_CONTENTBUILDERNG_N_ITEMS_INDEXED',
    $count,
    $count
);
```

Exemple anglais :

```ini
COM_CONTENTBUILDERNG_N_ITEMS_INDEXED_1="%d item indexed"
COM_CONTENTBUILDERNG_N_ITEMS_INDEXED_MORE="%d items indexed"
```

Les systèmes de pluriel diffèrent selon les langues.

Ne pas supposer que toutes les langues fonctionnent comme l’anglais.

Respecter les suffixes et conventions attendus par le mécanisme de pluralisation
de Joomla pour chaque locale.

Ne pas produire manuellement une forme plurielle en concaténant un `s` :

```php
$count . ' item' . ($count > 1 ? 's' : '');
```

## 15. Valeurs techniques et HTML

### HTML

Limiter le HTML dans les fichiers de langue.

Lorsque du HTML est indispensable :

* conserver exactement les mêmes balises dans les huit langues ;
* vérifier l’échappement au moment du rendu ;
* ne pas introduire de balise uniquement dans une traduction ;
* ne pas inclure d’attribut JavaScript inline ;
* ne pas traduire les noms de balises ou d’attributs.

Exemple :

```ini
COM_EXAMPLE_MESSAGE="<strong>Warning:</strong> The operation cannot be undone."
```

Les traductions doivent conserver la balise `<strong>` autour de l’information
équivalente.

### Valeurs techniques

Ne pas traduire :

```text
UTF-8
application/json
text/csv
EPSG:4326
GET
POST
PUT
PATCH
DELETE
localhost
index.php
configuration.php
```

Ne pas modifier les chemins, noms de fichiers, noms de classes, identifiants,
options de ligne de commande ou extraits de code.

### Entités HTML

N’utiliser des entités HTML que lorsque le contexte de rendu les interprète
réellement.

Ne pas employer une entité HTML pour corriger une typographie dans une chaîne
destinée à être affichée en texte brut, dans un attribut échappé ou dans une
notification JavaScript.

## 16. Workflow de modification

### Étape 1 — Identifier le contexte

Déterminer si la clé appartient :

* à l’administration ;
* au site ;
* au manifeste ;
* à un formulaire XML ;
* au JavaScript ;
* à un e-mail ;
* à plusieurs de ces contextes.

### Étape 2 — Définir le sens en anglais

Créer ou modifier la valeur `en-GB`.

Cette valeur constitue la source de vérité sémantique.

Vérifier :

* l’orthographe britannique ;
* la concision ;
* la terminologie ;
* les placeholders ;
* la ponctuation ;
* la capitalisation.

### Étape 3 — Mettre à jour les huit langues

Mettre à jour dans la même modification :

```text
en-GB
fr-FR
de-DE
es-ES
hu-HU
it-IT
nl-NL
tr-TR
```

Ne pas différer une langue dans un commit ultérieur.

### Étape 4 — Conserver le même ordre

Les huit fichiers doivent conserver le même ordre logique de clés.

Lorsqu’une nouvelle clé est ajoutée, l’insérer au même emplacement dans les huit
fichiers.

### Étape 5 — Vérifier les paramètres

Comparer les placeholders :

```text
%s
%d
%1$s
%2$d
```

Vérifier :

* leur nombre ;
* leur type ;
* leur index ;
* leur signification ;
* leur présence dans les huit langues.

### Étape 6 — Vérifier le contexte JavaScript

Si la clé est consommée par :

```javascript
Joomla.Text._('COM_EXAMPLE_KEY')
```

vérifier qu’elle est déclarée côté PHP :

```php
Text::script('COM_EXAMPLE_KEY');
```

### Étape 7 — Rechercher les chaînes codées en dur

Rechercher dans les fichiers modifiés les textes utilisateur non traduits :

```php
throw new \RuntimeException('Invalid file');
```

```javascript
alert('Save failed');
```

```xml
label="API key"
```

Les remplacer par des clés de langue.

### Étape 8 — Contrôler les fichiers

Vérifier :

* l’encodage UTF-8 ;
* l’absence de BOM si le projet l’exige ;
* la validité syntaxique INI ;
* l’équilibre des guillemets ;
* l’absence de clés dupliquées ;
* l’absence de clés manquantes ;
* l’absence de traduction anglaise copiée dans une autre langue ;
* la cohérence de l’ordre des clés.

## 17. Contrôles obligatoires en revue de code

Lorsqu’une modification touche une chaîne utilisateur, contrôler les points
suivants.

### Couverture linguistique

* La clé existe-t-elle dans les huit langues ?
* A-t-elle été modifiée dans les huit langues ?
* A-t-elle été supprimée dans les huit langues ?
* Se trouve-t-elle dans le bon fichier ?

### Alignement sémantique

* Le sens est-il identique ?
* Une traduction ajoute-t-elle une information ?
* Une traduction supprime-t-elle une restriction ?
* Les niveaux de politesse sont-ils cohérents ?
* Les termes métier sont-ils stables ?

### Paramètres

* Les placeholders sont-ils identiques ?
* Les indexes correspondent-ils aux mêmes données ?
* Les nombres utilisent-ils `Text::plural()` lorsque nécessaire ?

### Typographie

* L’anglais utilise-t-il l’orthographe britannique ?
* Le français respecte-t-il les accents, guillemets et espaces typographiques ?
* L’allemand respecte-t-il les majuscules des noms et le registre formel ?
* L’espagnol utilise-t-il les signes `¿` et `¡` lorsque nécessaire ?
* Le hongrois respecte-t-il les accents doubles `ő` et `ű` ?
* L’italien respecte-t-il les accents et apostrophes ?
* Le néerlandais respecte-t-il les mots composés et les trémas ?
* Le turc respecte-t-il les caractères `İ`, `ı`, `ğ`, `ş`, `ç`, `ö` et `ü` ?

### Joomla

* Une clé du cœur Joomla existe-t-elle déjà ?
* Une chaîne JavaScript est-elle déclarée avec `Text::script()` ?
* Le fichier `.sys.ini` est-il utilisé uniquement pour les chaînes système ?
* Les labels XML utilisent-ils une clé plutôt qu’un texte codé en dur ?

## 18. Interdictions

Ne jamais :

* modifier une seule langue ;
* copier automatiquement la valeur anglaise dans les sept autres langues ;
* laisser une valeur `TODO`;
* utiliser une traduction approximative en la présentant comme définitive ;
* supprimer un placeholder ;
* ajouter un placeholder absent des autres langues ;
* traduire une clé de configuration ou une valeur technique ;
* utiliser du tutoiement dans une interface d’administration ;
* concaténer des fragments de phrase traduits ;
* créer artificiellement un pluriel dans le code ;
* coder une chaîne utilisateur directement en PHP, JavaScript ou XML ;
* employer une typographie anglaise pour toutes les langues ;
* supposer que la casse, les suffixes ou l’ordre des mots sont identiques dans
  toutes les langues.

Si une traduction fiable n’est pas connue, le signaler explicitement dans le
compte rendu de modification. Ne pas remplacer silencieusement la traduction par
le texte anglais.

## 19. Compte rendu attendu

Lorsqu’une tâche ajoute ou modifie des traductions, le compte rendu doit
préciser :

* les clés ajoutées ;
* les clés modifiées ;
* les clés supprimées ;
* les huit langues mises à jour ;
* les fichiers concernés ;
* les éventuelles clés JavaScript enregistrées avec `Text::script()` ;
* les vérifications effectuées sur les placeholders ;
* les éventuelles incertitudes terminologiques.

Exemple :

```text
Traductions mises à jour dans les huit langues :
en-GB, fr-FR, de-DE, es-ES, hu-HU, it-IT, nl-NL et tr-TR.

Clés ajoutées :
- COM_CONTENTBUILDERNG_FIELD_API_KEY_LABEL
- COM_CONTENTBUILDERNG_FIELD_API_KEY_DESC
- COM_CONTENTBUILDERNG_ERROR_INVALID_KEY

Les fichiers conservent le même ordre de clés.
Les placeholders ont été vérifiés.
La chaîne d’erreur utilisée par JavaScript a été déclarée avec Text::script().
```

## 20. Ce que cette skill ne couvre pas

Cette skill ne couvre pas :

* l’ajout de langues autres que `en-GB`, `fr-FR`, `de-DE`, `es-ES`, `hu-HU`,
  `it-IT`, `nl-NL` et `tr-TR` ;
* la logique native de fallback linguistique de Joomla ;
* l’installation des packs de langues Joomla ;
* la traduction du contenu éditorial saisi par les utilisateurs ;
* la traduction automatique de données enregistrées en base ;
* la détection de la langue du navigateur ;
* la création de variantes régionales supplémentaires.

Ne pas implémenter de contournement personnalisé du fallback Joomla dans le
cadre d’une tâche de traduction.

## 21. Priorité en cas de conflit

En cas de divergence entre les instructions de Gilles, `AGENTS.md`, cette
spécification, Joomla et une traduction amont, appliquer cet ordre : instructions
explicites de Gilles et `AGENTS.md`, règles de cette skill, conventions Joomla 6,
puis valeur amont. Conserver les clés et placeholders existants et signaler une
divergence qui nécessiterait de modifier le comportement.
