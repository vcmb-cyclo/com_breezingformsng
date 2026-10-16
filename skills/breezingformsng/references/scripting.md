# BreezingForms NG scripting reference

## Hooks

Every hook is set to *none*, a *library* entry (Scripts or Pieces screen) or *custom* code.
Custom JS code is inserted as written: it must declare the function with the exact name below,
built from the form or element name. PHP piece code is a plain statement body.

| Hook | Lang | Function to declare | Runs |
|---|---|---|---|
| Form Init | JS | `ff_<form>_init()` | When the form (or a page) is entered |
| Form Submitted | JS | `ff_<form>_submitted(status, message)` | On the page shown after a submit |
| Element Init | JS | `ff_<element>_init(element, condition)` | On form entry and/or page entry (`condition` = `'formentry'` or `'pageentry'`) |
| Element Action | JS | `ff_<element>_action(element, action)` | On click, blur, change, focus or select, as ticked |
| Element Validation | JS | `ff_<element>_validation(element, message)` | During validation; return `''` if valid, else the message |
| Before Form | PHP | – | Before the form HTML; echoed output is shown |
| After Form | PHP | – | After the form HTML |
| Begin Submit | PHP | – | After submitted data is collected, before the record is saved and mails are sent |
| End Submit | PHP | – | After the record is saved and mails are sent |

In QuickMode, element init/action/validation code is edited in the element's advanced panel;
form scripts and pieces in the form's advanced options.

## PHP pieces: scope

Piece code runs with `$this` bound to the form processor (`HTML_facileFormsProcessor`):

- `$this->form` (form id), `$this->formrow` (form row), `$this->page`, `$this->rows` (elements)
- `$this->submitdata` (submitted values), `$this->savedata`, `$this->maildata`
- `$this->record_id` (set after the record is saved: use it in End Submit)
- `$this->status`, `$this->message`, `$this->submitted`
- `$this->user_id`, `$this->username`, `$this->user_full_name`, `$this->ip`, `$this->agent`
- `$this->app` (Joomla application), `$this->database`, `$this->editable`, `$this->mailbackRecipients`
- Methods: `$this->execPieceByName('<piece>')`, `$this->execPieceById($id)`, `$this->sendMail(...)`
- `ff_trace($msg)` writes to the debug trace when the form's Debug toggle is on.

Load the standard library first:

```php
$this->execPieceByName('ff_InitLib');
```

## PHP standard library (loaded by `ff_InitLib`)

| Function | Purpose |
|---|---|
| `ff_getSubmit($name, $default = null)` | Submitted value of a field. `$default` may be `FF_ARRAY`, `FF_LIST`, `FF_SLIST`, `FF_DLIST` for multi-values |
| `ff_getParam($name, $default = null, $mask = 0)` | Request parameter; `ff_param_*` names come from the form URL |
| `ff_setValue($name, $value)` | Preset a field before the form renders (Before Form) |
| `ff_setChecked($name, $value)` / `ff_setSelected($name, $value)` | Preset checkboxes, radios, selects |
| `ff_query($sql, $insert = false)` | Run SQL; with `$insert = true` returns the new id |
| `ff_select($sql)` / `ff_selectValue($sql, $default = null)` | Rows / single value |
| `ff_redirect($url, $target = 'self', $method = 'post')` | Redirect after submit |
| `ff_redirectForm($name, $params = '')`, `ff_redirectPage($params = '')`, `ff_redirectSelf($params = '')`, `ff_redirectParent($url)` | Redirect variants |
| `ff_makeFormUrl($name, $params = '')`, `ff_makePageUrl(...)`, `ff_makeSelfUrl(...)` | Build URLs |
| `ff_die($message = '', ...)` / `ff_dying()` | Stop processing with a message / test whether stopping |
| `ff_securityImage()` | Captcha image |
| `ff_expString($text)` / `ff_impString($text)` | Escape / unescape C-style strings |

`FF_*` constants come from the `ff_Constants` piece (loaded by `ff_InitLib`).
Some pieces are named differently from the function they define: load `ff_setValueX`,
`ff_setCheckedX`, `ff_setSelectedX`, `ff_getPageByNameX`, `ff_redirectParentX` when loading one by one.

`ff_query()`, `ff_select()` and `ff_selectValue()` call `BFFactory::getDbo()`, so they need the
`bfcompat` system plugin enabled. They take raw SQL. Quote every external value:

```php
$this->execPieceByName('ff_InitLib');
$db = $this->database;
$email = ff_getSubmit('email');
$id = ff_selectValue('SELECT id FROM #__users WHERE email = ' . $db->quote($email));
```

For new code, prefer the Joomla database API with bound parameters:

```php
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

$db = Factory::getContainer()->get(DatabaseInterface::class);
$query = $db->createQuery()
    ->insert($db->quoteName('#__my_leads'))
    ->columns($db->quoteName(['record_id', 'email']))
    ->values(':rid, :email')
    ->bind(':rid', $this->record_id, ParameterType::INTEGER)
    ->bind(':email', $email);
$db->setQuery($query)->execute();
```

## JavaScript built-ins (generated with the form)

- `ff_getElementByName(name)`, `ff_getElementByIndex(i)`, `ff_getDivByName(name)`,
  `ff_getPageByName(name)`, `ff_getIdByName(name)`, `ff_getForm()`
- `ff_validation(page)` (0 = all pages; returns the error text), `ff_validationFocus(name)`
- `ff_submitForm()`, `ff_switchpage(page)`, `ff_initialize(condition)`
- Variables: `ff_elements`, `ff_currentpage`, `ff_lastpage`, `ff_homepage`, `ff_param` (from `ff_param_*`),
  status constants `FF_STATUS_OK`, `FF_STATUS_UNPUBLISHED`, `FF_STATUS_SAVERECORD_FAILED`, …
- Static helpers: `ff_trim(s)`, `ff_redirect(url, target, method)`, `ff_redirectParent(url)`, `ff_redirectTop(url)`

## JavaScript standard library (Scripts screen)

- Validation `(element, message)`: `ff_valuenotempty`, `ff_validemail`, `ff_integer`, `ff_real`,
  `ff_checked`, `ff_anychecked`, `ff_url_validation`, `ff_validate_date`, `ff_securitycode_ok`
- Action `(element, action)`: `ff_validate_submit`, `ff_validate_submit_ajax`, `ff_validate_nextpage`,
  `ff_validate_prevpage`, `ff_nextpage`, `ff_previouspage`, `ff_resetForm`
- Init: `ff_getfocus`, `ff_showelementinit`, `ff_showforminit`
- Submitted `(status, message)`: `ff_showsubmitted`, `ff_submittedhome`
- Helpers: `ff_checkedValue(name)`, `ff_selectedValues(name)`, `ff_selectedValuesCheckboxes(name)`,
  `ff_setChecked(name, value, checked)`, `ff_setSelected(name, values, checked)`

Example element validation (custom code):

```js
function ff_zipcode_validation(element, message)
{
    return /^[0-9]{5}$/.test(element.value) ? '' : message;
}
```
