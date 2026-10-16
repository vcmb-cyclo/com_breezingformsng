# BreezingForms NG

**Form builder for Joomla 6.** BreezingForms NG ("new generation") is the community-maintained
continuation of BreezingForms by Crosstec, rebuilt on the native Joomla 6 MVC for PHP 8.3+.
It lets you build multi-page forms visually, store and export submissions, send notification
emails, take payments, and extend each form with PHP and JavaScript.

- Website: <https://breezingforms-ng.vcmb.fr>
- Releases: <https://github.com/vcmb-cyclo/com_breezingformsng/releases>
- Upgrading from Crosstec BreezingForms: see [Upgrading from BreezingForms](#upgrading-from-breezingforms-crosstec)
- AI agents: [`llms.txt`](llms.txt) and the agent skill in [`skills/breezingformsng`](skills/breezingformsng/SKILL.md)

---

## Features

### Form building
- **QuickMode visual editor**: a tree of pages, sections and elements, with drag and drop and AJAX save.
- **20 element types**: text field, textarea (optional HTML editor), number input, slider,
  select, checkbox, checkbox group, radio group, calendar, responsive calendar, file upload,
  hidden field, submit button, summarize (calculated field), image captcha, Google reCAPTCHA
  (classic or invisible), signature pad, PayPal, Stripe and Sofort.
- **Multi-page forms** with per-page validation and an optional progress bar, or a **one-page layout**.
- **Visibility rules** to show and hide fields depending on other answers.
- **File uploads** in HTML5 with chunking, multiple files, size and extension checks, and image resizing.
- **Themes**: a Bootstrap 5 renderer, plus the classic BreezingForms skins.

### Submissions
- Records manager with Viewed, Exported and Archived flags, editing of a single record, and batch actions.
- Export to **PDF, CSV, Excel (XLSX) and XML**; CSV import of records.
- **Email notifications** to administrators and **mailback** confirmations to the person who
  submitted the form, with attachments.
- **Integrator** rules that copy submitted data into any database table.

### Payments and integrations
- **Stripe, PayPal and Sofort**, with notifications sent only once the payment succeeds, and paid file downloads.
- Dropbox upload, Mailchimp, Salesforce.
- [ContentBuilder NG](https://github.com/vcmb-cyclo/com_contentbuilderng) integration, to turn submissions into Joomla articles and lists.

### Scripting
- Per-form and per-element **JavaScript** hooks (init, action, validation, after submit) and
  **PHP** hooks (before form, after form, begin submit, end submit).
- A standard library of reusable scripts and pieces (`ff_getElementByName`, `ff_setValue`,
  `ff_query`, `ff_redirect`, …), and a test page for each script and piece.
- Writing PHP code requires a dedicated ACL permission (`breezingformsng.code`).

### Embedding a form
- Menu item type "Add Form".
- Site module `mod_breezingforms`.
- Content plugin: `{BreezingForms:FormName}` in any article.

### Administration, privacy and maintenance
- Native Joomla ACL, action logs and help screens.
- **GDPR**: a Joomla privacy plugin handles export and removal requests; storing IP addresses can be turned off.
- Packages: export and import forms, scripts, pieces and menus as JSON.
- Test and debug tools: per-form debug trace, form preview, database audit and repair (indexes, collations), log viewer.
- Available in 8 languages: English, French, German, Italian, Spanish, Hungarian, Dutch and Turkish.

---

## Requirements

- Joomla 6.x
- PHP 8.3 or later
- MySQL 8.0.13+ or MariaDB 10.4+

## Installation

1. Download the component zip of the latest release from the [Releases](https://github.com/vcmb-cyclo/com_breezingformsng/releases) page.
2. Install it through **System → Install → Extensions**.
3. Open **Components → BreezingForms NG → Forms** and create a form in QuickMode.
4. Publish it with a menu item, the module or the content plugin.

Updates are then offered by the Joomla update manager.

## Upgrading from BreezingForms (Crosstec)

BreezingForms NG installs over a legacy `com_breezingforms` site and keeps your data:

- forms, records, scripts and pieces stay in the same `#__facileforms_*` tables;
- the installer renames the component, migrates menu links, assets and configuration, and
  rewrites `option=com_breezingforms` in stored URLs and code;
- the `bfcompat` system plugin keeps the legacy `BF*` classes available to PHP code stored in your forms.

Before upgrading, back up the database. Only Joomla 6 is supported. Easy Mode and Classic Mode
forms must be rebuilt in QuickMode. Afterwards, retest captchas, uploads, payments and exports.
Developers can read [`MIGRATION.md`](MIGRATION.md) for the refactoring details.

---

## Project status

This project is maintained by volunteers on a best-effort basis. Only
[GitHub Releases](https://github.com/vcmb-cyclo/com_breezingformsng/releases) are meant for production.
Issues and pull requests are welcome.

⚠️ This is **not** an official Crosstec project. All trademarks and original copyrights remain
the property of their respective owners.

## License

GNU General Public License version 2 or later, see [LICENSE](LICENSE).

This software is provided "as is", without warranty of any kind. The maintainers cannot be
held liable for any damage or data loss resulting from its use.
