# BreezingForms NG Form Mode

This Joomla 6 / PHP 8.3+ codebase keeps only one form creation mode:
QuickMode.

## Supported Mode

| Mode | Status | Storage marker | Main admin entry points |
| --- | --- | --- | --- |
| QuickMode | Supported | `template_code_processed = 'QuickMode'` | `task=quickmode.display` |

Forms whose persisted marker is not `QuickMode` are not supported by this port.
Frontend rendering now stops with a translated warning for those legacy form
formats.

The forms list offers an explicit conversion for a limited subset of legacy
forms. It creates an unpublished QuickMode copy in `QuickModeForms`, with new
element IDs; the original form and its submissions remain separate. Text,
textarea, checkbox, hidden input, simple select-list elements and unambiguous
Classic Mode radio groups are supported. Easy Mode radio groups require manual
rebuilding because their captions may be stored separately in HTML.
Unsupported elements, scripts, integrations and form-specific mail/PDF files
block conversion. Layout and static content require manual review. Conversion
uses transactions and requires InnoDB form and element tables.

## Current Admin Routing

Known QuickMode URLs:

```text
/administrator/index.php?option=com_breezingformsng&task=quickmode.display&form=FORM_ID
```

QuickMode inline editor:

```text
/administrator/index.php?option=com_breezingformsng&tmpl=component&task=quickmode.editor
```

The forms list and post-save redirects should point to QuickMode.

## Important Files

Administration, under `administrator/components/com_breezingformsng/`:

- `src/Controller/QuickmodeController.php`: the display, save and inline editor tasks.
- `src/Model/QuickmodeModel.php`: turns the editor tree into the form and its elements.
- `src/View/Quickmode/HtmlView.php`, `src/Helper/QuickmodeHtml.php`,
  `tmpl/quickmode/default.php` and `tmpl/quickmode/editor.php`: the screen and its inline editor.
- `layouts/quickmode/`: the form, page, section and element property panels.
- `libraries/crosstec/classes/BFQuickMode.php`, `BFQuickModeBootstrap.php` and
  `BFQuickModeOnePage.php`: the QuickMode classes of the original library.

Editor scripts, under `media/com_breezingformsng/js/admin/`:
`quickmode-app.js`, `quickmode-app-properties.js`, `quickmode-elements.js`,
`quickmode-editor.js` and `quickmode-tree-model.js`.

Site rendering, under `components/com_breezingformsng/`:

- `src/Service/Rendering/QuickMode/`: `ClassicRenderer.php`, `BootstrapRenderer.php`,
  `OnePageRenderer.php` and the builders they share.
- `src/Service/Rendering/QuickModeRendererFactory.php`: picks the renderer of a form.
- `src/Support/processor_facade.php`: the form processor.
- `breezingformsng.php`: the entry point of the site.

## Maintenance Rules

- New or edited forms must use QuickMode.
- Do not change `template_code_processed` without migrating the matching
  `template_code`, `template_areas`, elements, scripts, and generated IDs.
- Preserve the QuickMode-specific copy/import ID reset path.
- If unsupported persisted form data must be migrated, create an explicit
  migration into QuickMode.
- Keep new maintenance work aligned with Joomla 6 APIs and PHP 8.3+ only.

## Useful Searches

```sh
rg -n "template_code_processed|QuickMode|QuickModeForms|task=quickmode" administrator components
```
