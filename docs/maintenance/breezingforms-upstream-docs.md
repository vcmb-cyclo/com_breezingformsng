# BreezingForms Upstream Documentation Notes

This file records useful information extracted from archived Crosstec
BreezingForms documentation. It is maintenance context only; the implementation
in this repository must remain Joomla 6 / PHP 8.1+ only.

Primary archived source:
https://web.archive.org/web/20251013050040/https://crosstec.org/en/support/online-documentation/breezingforms.html

## Documentation Map

The archived Crosstec index organizes BreezingForms documentation into these
areas:

- Video Tutorials
- Installation
- Getting Started
- Elements
- Email Configuration
- Advanced Settings
- Examples & Scripts
- Styling
- Common Problems
- More

Useful linked articles from that index:

- `Breezingforms - Online Documentation`
- `Installing BreezingForms for Joomla`
- `Creating a simple multiple-page form Joomla`
- `Mobile Joomla Forms`
- `Form Theme Styling Joomla`
- `Turn fields and sections on or off conditionally`
- `How to Display the Form in Your Site`
- `Embed Form in Article`
- `Mailback Emails`
- `The Breezingforms Package`
- `Classicmode Tutorial By Rheinwerk <openbook>`

The index also links to feature matrices for Joomla and WordPress. Treat those
matrices as historical product documentation, not as current behavior for this
Joomla 6 port.

## QuickMode Concepts

The Crosstec getting-started material is centered on QuickMode for ordinary form
creation. The documented workflow includes:

- creating a form in QuickMode;
- adding multiple pages;
- adding elements and sections;
- adding submit/navigation behavior;
- configuring validation;
- creating a final thank-you page;
- publishing the form through a Joomla menu item;
- changing the frontend appearance through a QuickMode theme.

Maintenance mapping in this codebase:

- QuickMode admin entry: `administrator/components/com_breezingformsng/admin/quickmode.php`
- QuickMode UI assets: `administrator/components/com_breezingformsng/admin/quickmode-app.js`
- Runtime renderers: `administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickMode*.php`
- Persisted marker: `#__facileforms_forms.template_code_processed = 'QuickMode'`

## Themes And Styling

The upstream styling documentation focuses on QuickMode themes and CSS selectors
such as:

- `.bfQuickMode`
- `.bfQuickMode p.bfElemWrap`
- `.bfQuickMode span.bfElemWrap`
- `.bfQuickMode input[type=submit]`
- `.bfQuickMode input[type=reset]`
- `.bfQuickMode .bfErrorMessage`

It also documents backend-adjustable element attributes for common element
types:

| Element | Historically adjustable attributes |
| --- | --- |
| Textfield | value, width/size, maximum length |
| Textarea | value, width, height, maximum length |
| Select list | width, height |
| Submit button | value |
| Calendar | value, width/size |

When maintaining styling, remember that width calculations may be affected by
padding and borders. Do not assume a configured element width is identical to
its rendered CSS box width.

Relevant local areas:

- `administrator/components/com_breezingformsng/admin/quickmode.html.php`
- `administrator/components/com_breezingformsng/admin/quickmode-app.js`
- `administrator/components/com_breezingformsng/libraries/jquery/themes/quickmode/`
- `components/com_breezingformsng/themes/quickmode/`
- `media/com_breezingformsng/css/custom.css`

## Mobile Forms

The archived Crosstec mobile documentation describes a separate mobile behavior
for forms, historically based on jQuery Mobile. Useful maintenance points:

- mobile rendering can differ from desktop rendering;
- mobile mode may use mobile-specific styles;
- reCAPTCHA in the desktop form historically used a native captcha fallback on
  mobile;
- HTML5 upload behavior was treated separately from regular upload behavior;
- custom mobile theme files were historically placed under
  `/media/breezingforms/themes/` with exact jQuery Mobile version names.

This port has renamed component/media paths. Do not reintroduce old
`/media/breezingforms/` assumptions without checking migration code and current
asset registration.

Relevant local areas:

- `components/com_breezingformsng/facileforms.process.php`
- `administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickModeMobile.php`
- `components/com_breezingformsng/themes/quickmode/`
- `script.php` quickmode icon/path migration logic

## Frontend Publication

The upstream docs describe three historically important ways to expose forms:

- Joomla menu item pointing to a BreezingForms form name;
- plugin/embed syntax inside an article;
- module display.

For this Joomla 6 codebase, verify the actual installed plugin/module/menu
integration before changing frontend routing. The historical docs explain the
product concept, not necessarily the current route implementation.

Relevant local searches:

```sh
rg -n "Form Name|formname|ff_form|FacileForms|BreezingForms" administrator components plugins modules
```

```sh
rg -n "plugin|module|menu|Itemid|runmode" components/com_breezingformsng administrator/components/com_breezingformsng
```

## Scripts And Conditional Behavior

The Crosstec documentation has a large `Examples & Scripts` area and highlights
conditional fields/sections as a common topic. Maintenance implications:

- form behavior may depend on stored script snippets and library functions;
- validation and submit/navigation behavior may be configured per element or per
  form;
- changes to sanitization, request handling, or script loading can affect saved
  forms even if editor UI still loads.

Relevant local areas:

- `administrator/components/com_breezingformsng/src/Service/ScriptManager.php`
- `administrator/components/com_breezingformsng/src/Service/PieceManager.php`
- `components/com_breezingformsng/facileforms.process.php`

Script and validation maintenance should target QuickMode and the shared runtime
paths only.

## Maintenance Use

Use this document when triaging legacy behavior, but verify every behavior in
the local code before changing implementation. Crosstec documentation spans
multiple old Joomla/BreezingForms versions and includes WordPress material that
does not apply here.
