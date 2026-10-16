---
name: breezingformsng
description: Build, script, embed and troubleshoot forms with BreezingForms NG (com_breezingformsng), the Joomla 6 form builder that continues Crosstec BreezingForms. Use when a Joomla site needs a contact, registration, survey, booking or payment form; when writing BreezingForms form scripts or pieces (ff_* functions, $this->submitdata, before/after form or submit hooks, element init/action/validation scripts); when exporting or processing form submissions (#__facileforms_records); or when upgrading a site from legacy com_breezingforms / BreezingForms Crosstec to Joomla 6.
---

# BreezingForms NG

BreezingForms NG is a Joomla 6 component (PHP 8.3+, MySQL/MariaDB) for building forms visually,
storing submissions, sending emails, taking payments and running custom PHP/JS per form.
Source and releases: https://github.com/vcmb-cyclo/com_breezingformsng

## Key facts

- Component `com_breezingformsng`, namespace `Vcmb\Component\BreezingformsNG`.
- Data lives in `#__facileforms_*` tables (same names as legacy BreezingForms):
  `forms`, `elements`, `records`, `subrecords` (one row per submitted value), `scripts` (JS library),
  `pieces` (PHP library), `integrator_*`.
- **QuickMode** is the only form editor. Easy Mode and Classic Mode forms must be rebuilt in QuickMode.
- Renderers: Bootstrap 5, one-page Bootstrap, or classic BreezingForms skins (chosen in the form's advanced settings).
- Admin menu: Records, Forms, Scripts, Pieces, Integrator, Configuration, About.

## Typical tasks

### Create a form
1. Administrator → Components → BreezingForms NG → Forms → New (QuickMode).
2. Build the tree: form → pages → sections → elements. Element types are listed in
   [references/elements.md](references/elements.md).
3. Form properties: title, name (used in URLs and the content plugin), email notification,
   mailback, theme engine (Bootstrap or BreezingForms).
4. Use the "Run" (preview) action and the per-form Debug toggle to test.

### Embed a form
- Menu item type "Add Form" (enter the form name, start page, iframe and alignment options), or
- site module `mod_breezingforms`, or
- content plugin in an article: `{BreezingForms:FormName}`.
- Pass initial values with URL parameters `ff_param_<name>=value`; in PHP read them with `ff_getParam('ff_param_<name>')`.

### Add logic with scripts and pieces
- JavaScript hooks: form init, form submitted, element init/action/validation.
- PHP hooks ("pieces"): Before Form, After Form, Begin Submit, End Submit.
- Each hook is either *none*, a *library* entry (Scripts/Pieces screens) or *custom* code.
- Full API, hook signatures and variables in scope: [references/scripting.md](references/scripting.md).
- Writing PHP requires the `breezingformsng.code` ACL permission.

### Work with submissions
- Records screen: filter, flag Viewed/Exported/Archived, export PDF/CSV/XLSX/XML, import CSV.
- Database: one row per submission in `#__facileforms_records` (`form`, `submitted`, `ip`, `user_id`,
  `username`, `viewed`, `exported`, payment columns `paypal_*`, double opt-in columns `opt_*`);
  each field value in `#__facileforms_subrecords` (`record`, `element`, `name`, `title`, `type`, `value`).
- Integrator screen: rules that copy submitted values into another table without code.
- GDPR: enable the "Privacy - BreezingForms NG" plugin; it covers submissions of logged-in users only.

### Move forms between sites
Configuration → Import/export: forms, scripts, pieces and menus as one JSON package
(`breezingformsng-<package>.json`). Requires the `breezingformsng.configuration` permission.

### Upgrade from legacy BreezingForms (Crosstec)
See [references/migration.md](references/migration.md).

## Rules when writing code for BreezingForms NG

- Target Joomla 6 APIs only: `Factory::getApplication()->getIdentity()`, `getInput()`,
  `Factory::getContainer()->get(DatabaseInterface::class)`, `$db->createQuery()`, exceptions instead of `getError()`.
  Do not use `Factory::getUser()`, `$app->input`, `getQuery(true)` or `JFactory`/`J*` aliases.
- Always use bound parameters (`$query->bind()`) or `$db->quote()` for values coming from `ff_getSubmit()`/`ff_getParam()`.
  The stdlib `ff_query()`/`ff_select()` take raw SQL: never concatenate user input into them unescaped.
- Custom submit code must post the Joomla form token: a request with `ff_task=submit` and no valid
  token is rejected with HTTP 403.
- Legacy `BF*` classes (`BFFactory`, `BFRequest`, `BFText`, …) only exist through the `bfcompat`
  system plugin; prefer native Joomla classes in new code.
- User-facing strings in extensions go through language keys (`Text::_()`); the component ships
  en-GB, fr-FR, de-DE, it-IT, es-ES, hu-HU, nl-NL and tr-TR.
