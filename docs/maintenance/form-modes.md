# BreezingForms NG Form Mode

This Joomla 6 / PHP 8.1+ codebase keeps only one form creation mode:
QuickMode.

## Supported Mode

| Mode | Status | Storage marker | Main admin entry points |
| --- | --- | --- | --- |
| QuickMode | Supported | `template_code_processed = 'QuickMode'` | `task=quickmode.display` |

Forms whose persisted marker is not `QuickMode` are not supported by this port.
Frontend rendering now stops with a translated warning for those legacy form
formats.

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

- `administrator/components/com_breezingformsng/admin/quickmode.php`
- `administrator/components/com_breezingformsng/admin/quickmode.html.php`
- `administrator/components/com_breezingformsng/admin/quickmode.class.php`
- `administrator/components/com_breezingformsng/admin/quickmode-app.js`
- `administrator/components/com_breezingformsng/admin/quickmode-elements.js`
- `administrator/components/com_breezingformsng/admin/quickmode-editor.php`
- `administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickMode.php`
- `administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickModeBootstrap.php`
- `administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickModeOnePage.php`
- `administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickModeMobile.php`
- `components/com_breezingformsng/facileforms.process.php`
- `components/com_breezingformsng/breezingformsng.php`

## Maintenance Rules

- New or edited forms must use QuickMode.
- Do not change `template_code_processed` without migrating the matching
  `template_code`, `template_areas`, elements, scripts, and generated IDs.
- Preserve the QuickMode-specific copy/import ID reset path.
- If unsupported persisted form data must be migrated, create an explicit
  migration into QuickMode.
- Keep new maintenance work aligned with Joomla 6 APIs and PHP 8.1+ only.

## Useful Searches

```sh
rg -n "template_code_processed|QuickMode|QuickModeForms|task=quickmode" administrator components
```
