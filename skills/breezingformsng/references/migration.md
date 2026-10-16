# Upgrading from legacy BreezingForms (Crosstec) to BreezingForms NG

## Requirements
- Joomla 6 and PHP 8.3+. There is no Joomla 4/5 build.
- Back up files and database first.

## What the installer does
Install the BreezingForms NG package over the existing site. It:
- renames the extension `com_breezingforms` to `com_breezingformsng` (extension row, assets, menu links);
- keeps all data in place: the tables keep their `#__facileforms_*` names;
- rewrites `option=com_breezingforms` to `option=com_breezingformsng` in stored form and element
  content (redirect URLs, PayPal/Stripe/Sofort return URLs, init/action/validation code);
- copies the legacy configuration (`#__facileforms_config`) into the component options once
  (IP storage, notification address, uploads folder, CSV settings);
- removes the old `com_breezingforms` folders and the files dropped by earlier releases.

## What you must check
1. **Form modes**: only QuickMode remains. Easy Mode and Classic Mode forms must be rebuilt in QuickMode.
2. **Stored PHP code** (pieces, Before/After Form, Begin/End Submit, integrator code): it may use
   legacy classes. `BFFactory`, `BFRequest`, `BFText`, `BFJoomlaConfig`, `BFPDF` and `BFIntegrate`
   are provided by the **bfcompat** system plugin: keep it enabled until that code is rewritten.
   Search the code columns of `#__facileforms_forms`, `_elements`, `_pieces`, `_scripts` and
   `_integrator_items` for `BF`, `JFactory`, `JRequest`, `Factory::getUser`, `getQuery(true)`.
3. **Custom submit code** must post the Joomla form token (`ff_task=submit` without it returns 403).
4. **Legacy admin URLs** (`act=managerecs`, `act=manageforms`, `act=quickmode`, …) are gone:
   use `view=records`, `view=forms`, `task=quickmode.display`.
5. **Mobile renderer** was removed; use the Bootstrap or one-page layout.
6. Retest captcha/reCAPTCHA, file uploads, payments (sandbox), email notifications and exports.
7. Watch for "Class … not found" in `administrator/logs/everything.php` with Joomla debug on:
   it usually points to stored code calling a removed legacy API.

## Rewriting legacy code (Joomla 6 equivalents)

| Legacy | Joomla 6 |
|---|---|
| `BFFactory::getDbo()`, `JFactory::getDbo()` | `Factory::getContainer()->get(DatabaseInterface::class)` |
| `$db->getQuery(true)` | `$db->createQuery()` |
| `BFRequest::getVar()`, `JRequest`, `$app->input` | `Factory::getApplication()->getInput()` |
| `BFText::_()`, `JText::_()` | `Text::_()` |
| `JFactory::getUser()`, `Factory::getUser()` | `Factory::getApplication()->getIdentity()` |
| `JFactory::getMailer()` | `Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer()` |
| `$model->getError()` | catch exceptions |
