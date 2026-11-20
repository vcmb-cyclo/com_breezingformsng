# BreezingForms NG

[![Test and Build Package](https://github.com/vcmb-cyclo/com_breezingformsng/actions/workflows/build-package.yml/badge.svg)](https://github.com/vcmb-cyclo/com_breezingformsng/actions/workflows/build-package.yml)
[![PHPStan level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen?logo=php)](phpstan.neon.dist)

**Form builder for Joomla 6.** BreezingForms NG ("new generation") is the community-maintained
continuation of BreezingForms by Crosstec, rebuilt on the native Joomla 6 MVC for PHP 8.4+.
It lets you build multi-page forms visually, store and export submissions, send notification
emails, take payments, and extend each form with PHP and JavaScript.

- Website: <https://breezingforms-ng.vcmb.fr>
- Code quality: the component's own PHP code is analysed with PHPStan 2.x at level 8 with an empty baseline (bundled libraries and `vendor/` excluded), by the CI workflow above, together with PHPUnit and Playwright tests
- Releases: <https://github.com/vcmb-cyclo/com_breezingformsng/releases>
- Upgrading from Crosstec BreezingForms: see [Upgrading from BreezingForms](#upgrading-from-breezingforms-crosstec)
- AI agents: [`llms.txt`](llms.txt) and the agent skill in [`skills/breezingformsng`](skills/breezingformsng/SKILL.md)

## Historical lineage

BreezingForms NG perpetuates a form-builder lineage that predates the NG project:

| Period | Product | Attribution |
|---|---|---|
| 2004–2006 | FacileForms | Peter Koch, Switzerland |
| From around 2008 | BreezingForms | Markus Bopp / Crosstec |
| Since 2024 | BreezingForms NG | Community-led modernization |

The 2004 date is attested by historical copyright headers, but it is not proof of the
first public distribution. A Joomla forum discussion from August 2005 credits Peter Koch
with FacileForms, and a 2 September 2005 discussion refers to `com_facileforms` and forms
already in operation. The exact date of the first public release has not been established.

Sources: the [BreezingForms 1.8 header](https://github.com/wp-plugins/breezing-forms/blob/master/platform/components/com_breezingforms/breezingforms.php#L4-L7),
its [FacileForms copyright notice](https://github.com/wp-plugins/breezing-forms/blob/master/platform/components/com_breezingforms/breezingforms.php#L2726-L2733),
the [August 2005 Joomla forum discussion](https://forum.joomla.org/viewtopic.php?p=19927), and
[2 September 2005 Joomla forum discussion](https://forum.joomla.org/viewtopic.php?p=908867).

---

## Features

### Form building
- **QuickMode visual editor**: a tree of pages, sections and elements, with drag and drop and AJAX save.
- **20 element types**: text field, textarea (optional HTML editor), number input, slider,
  select, checkbox, checkbox group, radio group, calendar, responsive calendar, file upload,
  hidden field, submit button, summarize (calculated field), image captcha, Google reCAPTCHA
  (classic or invisible), signature pad, PayPal and Stripe (card, Klarna, Wero).
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
- **Stripe and PayPal**, Stripe with Klarna and Wero as options, with notifications sent only once the payment succeeds, and paid file downloads.
- Dropbox upload, Mailchimp, Salesforce.
- [ContentBuilder NG](https://github.com/vcmb-cyclo/com_contentbuilderng) integration, to turn submissions into Joomla articles and lists.
- **AI assistants (MCP)**: let Claude Code, Codex and others read, check and create forms, with Joomla permissions ([details](#ai-assistants-mcp)).

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
- PHP 8.4 or later
- MySQL 8.0.13+ or MariaDB 10.4+

## Installation

1. Download the component zip of the latest release from the [Releases](https://github.com/vcmb-cyclo/com_breezingformsng/releases) page.
2. Install it through **System → Install → Extensions**.
3. Open **Components → BreezingForms NG → Forms** and create a form in QuickMode.
4. Publish it with a menu item, the module or the content plugin.

Updates are then offered by the Joomla update manager.

### Payment webhook

A Stripe element needs a webhook on its form, so that a payment is recorded and its
notifications sent even when the customer does not come back to the site. Without it, the
payment is only recorded on the return, and the component log warns about it.

1. In QuickMode, select the Stripe element and copy its **Webhook endpoint URL**
   (save a new form first: the URL contains its number).
2. In the Stripe Dashboard, under **Developers → Webhooks**, add an endpoint with this URL
   and the events `checkout.session.completed` and `checkout.session.async_payment_succeeded`.
3. Copy the endpoint's signing secret (`whsec_…`) into the element's **Webhook signing
   secret**, then save the form.

If `bf_stripe_success.php` performs external actions, make them idempotent by the Stripe
PaymentIntent ID: a failed request can run the script again when the webhook retries.
Paid Checkout sessions opened before this version must be reconciled manually in Stripe.

### AI assistants (MCP)

BreezingForms NG exposes an [MCP](https://modelcontextprotocol.io) endpoint in the Joomla API,
so that assistants such as Claude Code or Codex can read, check and build your forms. It is
**off by default**: turn on **Enable MCP** in the component options, then authenticate with a
Joomla API token. Every action follows the permissions of the account that owns the token.

- Endpoint: `POST <your-site>/api/index.php/v1/breezingformsng/mcp`
- Tools: list and read forms, check a form for inconsistencies, audit the database
  (read-only); create a form, update its settings, add, change, move or remove fields and
  pages, and trash or restore a form.
- PHP code stays out of reach without the `breezingformsng.code` permission.
- **System → Tools** shows the status and the address. See [`llms.txt`](llms.txt) for agents.

## Upgrading from BreezingForms (Crosstec)

BreezingForms NG installs over a legacy `com_breezingforms` site and keeps your data:

- forms, records, scripts and pieces stay in the same `#__facileforms_*` tables;
- the installer renames the component, migrates menu links, assets and configuration, and
  rewrites `option=com_breezingforms` in stored URLs and code;
- the `bfcompat` system plugin keeps the legacy `BF*` classes available to PHP code stored in your forms.

Before upgrading, back up the database and work on a copy of the site. Only Joomla 6 is supported.

### Converting Easy Mode and Classic Mode forms

In **Components → BreezingForms NG → Forms**, select one legacy form and choose
**Convert to QuickMode**. The converter creates a separate, unpublished QuickMode form;
it does not replace the original or move its submissions. The copy has a new form name,
ID and element IDs, so existing links and integrations still refer to the original.

The converter supports text fields, text areas, checkboxes, hidden inputs, simple select
lists and unambiguous Classic Mode radio groups. Unsupported element types, scripts,
integrations, form-specific mail or PDF templates, and ambiguous settings stop conversion
with a warning identifying the affected element when available. Those cases need manual
rebuilding. Conversion requires transactional InnoDB form and element tables, and permission
to create and edit forms.

Review the copy's content, layout, fields, notifications and behaviour before publishing it:
legacy positioning and static content are not automatically reconstructed. Update links only
after testing the copy. Retest captchas, uploads, payments and exports after migration.
Easy Mode radio groups and ambiguous Classic Mode radio groups require manual rebuilding.
Developers can read [`MIGRATION.md`](MIGRATION.md) for the refactoring details.

---

## Project status

This project is maintained by volunteers on a best-effort basis. Only
[GitHub Releases](https://github.com/vcmb-cyclo/com_breezingformsng/releases) are meant for production.
Issues and pull requests are welcome.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, coding guidelines, checks, and pull request expectations.

⚠️ This is **not** an official Crosstec project. All trademarks and original copyrights remain
the property of their respective owners.

## License

GNU General Public License version 2 or later, see [LICENSE](LICENSE).

This software is provided "as is", without warranty of any kind. The maintainers cannot be
held liable for any damage or data loss resulting from its use.
