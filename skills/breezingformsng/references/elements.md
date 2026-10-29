# QuickMode element types

The type stored in the form (`bfType`) is shown next to the editor label.

| Editor label | `bfType` | Notes |
|---|---|---|
| Textfield | `bfTextfield` | Single-line text. Can be used as mailback address. |
| Textarea | `bfTextarea` | Multi-line text, optional HTML editor and max-length counter. |
| Number input | `bfNumberInput` | Numeric input. |
| Slider | `bfNumberInput` with `range=true` | Number input rendered as a range slider. |
| Select | `bfSelect` | Dropdown, single or multiple. |
| Checkbox | `bfCheckbox` | Single checkbox. |
| Checkbox group | `bfCheckboxGroup` | Several checkboxes. |
| Radio group | `bfRadioGroup` | Radio buttons. |
| Calendar | `bfCalendar` | Date picker. |
| Responsive Calendar | `bfCalendarResponsive` | Mobile-friendly date picker. |
| File | `bfFile` | Upload; HTML5 mode adds progress, multiple and large files. |
| Hidden input | `bfHidden` | Hidden value. |
| Submit Button | `bfSubmitButton` | Submits the form. |
| Summarize | `bfSummarize` | Shows a value computed from other fields (JS). |
| Captcha | `bfCaptcha` | Built-in image captcha. |
| ReCaptcha | `bfReCaptcha` | Google reCAPTCHA, classic or invisible; verified server-side. |
| Signature | `bfSignature` | Drawn signature pad. |
| PayPal | `bfPayPal` | PayPal payment. |
| Stripe | `bfStripe` | Stripe payment, optional paid download with a tries limit. |

Only one captcha (Captcha or ReCaptcha) is allowed per form.

## Structure nodes
- **Form** (root): name, title, email notification, mailback, theme, scripts and pieces.
- **Page**: one step of a multi-page form; previous/next buttons validate the current page.
- **Section**: groups elements; can be a fieldset.

## Rendering
- Theme engine **Bootstrap**: Bootstrap 5 markup; "Enable one-page Layout" switches to the
  one-page renderer (forces HTML5 uploads). Options: label on top, progress bar, hero unit, well.
- Theme engine **BreezingForms**: classic skins from `media/breezingforms/themes/`
  (`default`, `aqua`, `breeze`, `glossy_blue`, `glossy_gray`, …).

## Visibility rules
Fields and sections can be shown or hidden depending on other answers ("Visibility rules" in
the form properties). One rule per line:

```
turn <on|off> <element|section> <targetName> if <sourceElementName> <is|isnot> <value>
```

- `targetName` is an element name, or a section id for `section`.
- A `value` starting with `!` is a regular expression (text fields), e.g. `!^[0-9]+$`.

Example:

```
turn on element company if customer_type is business
turn off section shipping if same_address is yes
```
