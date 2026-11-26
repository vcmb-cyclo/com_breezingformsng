# Field types

This guide lists every field type you can pick in the QuickMode editor. For each type it tells you what it is for, which options matter, what is stored in the submission, and what to watch for. It is written for the person who builds forms.

The editor screens themselves are described in [the QuickMode editor guide](03-quickmode-editor.md). Here we only talk about the field types.

You choose a type in the **Type** panel of the element properties. It shows four columns side by side: **Input**, **Choice**, **Security and payment** and **Action and view**. Type in the search box to keep only the matching types: it also knows other words, such as *card* for Stripe. Your last three choices are shown at the top. With the keyboard, the arrows move between types and columns, **Enter** chooses and **Escape** closes.

## How to read this guide

- **Name** is the technical name of the field. It is used in e-mails, exports and scripts.
- **Label** is the text the visitor sees.
- **Required** only shows a red asterisk next to the label. It does not block an empty value by itself. To block an empty value, also set **Validation** to a library validation (for example `ff_valuenotempty`) and fill in the **Error Message**. This is stated in the tooltip of the option.
- **Logging**: when it is off, the value is not stored in the database and does not appear in e-mails.
- **Order (Number)** sets the display order in e-mails and the record manager. `-1` means automatic.
- **Hide label** hides the label in the form.
- Options named **Hint**, **Turn off**, **Title position**, **Tab index**, **Initscript** and **Actionscript** exist on most types. They are not repeated below.

## Summary table

| Type | Use | Stored value | Notes |
|---|---|---|---|
| Textfield | One line of text | The text | Can be a password field and can send a mailback |
| E-mail address | An e-mail address | The address | Checked on the server |
| Web address (URL) | The address of a page | The address | Checked on the server |
| International phone | Phone number with country prefix | `+33612345678` style | Checked on the server |
| Date and time | Date, time, or both | `2026-11-18T09:00`, `2026-11-18` or `09:00` | Optional earliest and latest value |
| Country | Pick a country | Two-letter ISO code, for example `FR` | Optional list of allowed countries |
| Address search | Address with suggestions | The text typed or chosen | Suggestions are off by default |
| Textarea | Several lines of text | The text | Optional editor and character counter |
| Number input | A number | The number | Min, max and step |
| Slider | A number on a slider | The number | A Number input shown as a range |
| Calendar | Date with a popup calendar | Text in the chosen format | Format follows the Joomla calendar |
| Responsive Calendar | Same, for small screens | Text in the chosen format | Same options as Calendar |
| Hidden input | A value the visitor does not see | The value | Can be computed with PHP |
| File | File upload | The server path or URL of each file | Executable files always refused |
| Checkbox | One yes/no box | The box value when checked | Can be preset as checked |
| Checkbox group | Several boxes | The values of the checked boxes | One line per box |
| Radio group | One choice among a few | The value of the chosen button | One line per button |
| Select | Drop-down list | The chosen value or values | Multiple selection possible |
| Captcha | Image code | Nothing stored | Built in, no account needed |
| ReCaptcha | Google robot check | Nothing stored | Needs Google keys |
| Signature | Handwritten signature | The name of a PNG file | Image attached to e-mails |
| PayPal | PayPal payment button | The payment data | Needs a PayPal account |
| Stripe | Card payment through Stripe | The payment data | Needs Stripe keys |
| Submit Button | Send the form | Nothing stored | Label is the button text |
| Summarize | Recap or calculated total | The shown value | Shows other fields |

## Choosing a field type

- **Free text, short**: Textfield. Use a more specific type when one fits (phone, date, country).
- **Free text, long**: Textarea.
- **An e-mail address**: E-mail address. The phone keyboard shows the @ sign.
- **A phone number**: International phone. It gives a clean `+` number.
- **A date**: Date and time in "Date only" mode, or Calendar if you need a specific date format.
- **A country**: Country. The value is always a valid code.
- **A number**: Number input. Use Slider when the exact value matters less than the range.
- **One answer among few**: Radio group. Among many: Select.
- **Several answers**: Checkbox group, or Select with Multiple.
- **Agree to terms**: Checkbox.
- **Attach a document**: File.
- **Proof of agreement**: Signature.
- **Stop bots**: Captcha (no account) or ReCaptcha (Google keys).
- **Take a payment**: Stripe or PayPal.
- **Carry data the visitor must not change**: Hidden input. A visitor can still alter a hidden value in the browser, so never trust it for prices.
- **Show a recap or a total**: Summarize.

## Textfield

**Use.** One line of free text: name, e-mail, reference number.

**Options.** Label, Name, Value (default), Placeholder, Icon (a Font Awesome class such as `fa-user`), Size (px or %, for example `200px`), Max. length, Hint. Under advanced options: Password, Readonly, Mailback?, Mailback as sender? (Reply-To), Mailbackfile.

**Specific options.** If the field holds an e-mail address, **Mailback?** sends a copy of the submission to that address. The default value can be plain text or PHP in the form `<?php return '...'; ?>`.

**Stored.** The text typed. It appears as is in e-mails and exports.

**Accessibility.** If Required is on, the asterisk is hidden from screen readers and replaced by a hidden "Required" text. The field also gets `aria-required="true"`. Validation errors are announced in a message box with `role="alert"`.

**Example.** Name `lastname`, Label "Last name", Required on, Validation Library `ff_valuenotempty`, Error Message "Please enter your last name".

## E-mail address

**Use.** An e-mail address.

**Options.** The options of a Textfield, mailback included, plus **Check the entry** (on by default).

**In the browser.** The field offers the e-mail keyboard on phones and the address the browser remembers. For a message while the visitor is still on the form, also set **Validation** to `ff_validemail`.

**On the server.** When **Check the entry** is on, an address that is not valid is refused.

**Stored.** The address as typed.

## Web address (URL)

**Use.** The address of a web page.

**Options.** The options of a Textfield, plus **Check the entry** (on by default).

**In the browser.** The field offers the web keyboard on phones.

**On the server.** When **Check the entry** is on, the address must start with `http://` or `https://` and name a domain. Nothing checks that the site exists.

**Stored.** The address as typed.

## International phone

**Use.** A phone number that works across countries. It is a Textfield with a phone widget.

**Options.** Default country (two-letter code, default `fr`), Allowed countries (comma-separated codes, empty means all), Show flags, Show dial code, Validate the number.

**Stored.** The number in international format: a `+`, a non-zero digit, then 7 to 14 more digits, with no spaces. For example `+33612345678`. An empty value is accepted. A malformed value is refused on the server with "Please enter a valid international phone number, for example +33 6 12 34 56 78." The server check applies only when **Validate the number** is on.

**Accessibility.** Rendered as `type="tel"`. Required marker and error messages work like the Textfield.

**Example.** Name `phone`, Default country `fr`, Allowed countries `fr,be,ch`. A visitor enters 06 12 34 56 78 and `+33612345678` is stored.

## Date and time

**Use.** Pick a date and time, a date only, or a time only with the native browser control.

**Options.** Mode (Date and time, Date only, Time only), Earliest value, Latest value, Step (minutes; `0` uses the browser default; default 15).

**Stored.** `2026-11-18T09:00` for Date and time, `2026-11-18` for Date only, `09:00` for Time only. The server refuses a value that has the wrong shape, is not a real date, or is outside the limits, with "Please enter a valid date and time."

**Limits.** Earliest and Latest take an ISO value in the shape of the mode, or the word `now`. For Time only, `now` is ignored. The server widens a `now` limit by 14 hours so that a visitor is never refused because of time zones.

**Accessibility.** Uses the browser's own `datetime-local`, `date` or `time` input. Required marker as for the Textfield.

**Example.** Mode Date only, Earliest value `now`: appointments cannot be set in the past.

## Country

**Use.** Choose a country from a list. It is a select box with a country widget.

**Options.** Default country, Allowed countries (comma-separated two-letter codes, empty means all), Show flags.

**Stored.** The two-letter ISO 3166-1 code in capitals, for example `FR`. The server refuses any code that is not in its list, or not in your allowed list, with "Please choose a valid country."

**Example.** Allowed countries `fr,de,it`: the visitor can only pick these three.

## Address search

**Use.** An address field that proposes suggestions while the visitor types.

**Options.** Suggestion service (None (plain text), Base Adresse Nationale (France), Photon (worldwide)), Minimum characters (1 to 10, default 3), Allowed countries, Fill other fields (one `role=FieldName` per line, roles: `street`, `postcode`, `city`, `country`), Max. length.

**Privacy.** With a suggestion service, the text typed is sent to that third party. It is off by default. Mention it in your privacy policy. With None, the field is a plain text field and nothing is sent.

**Stored.** The text of the field. Other fields filled by a suggestion store their own values.

**Accessibility.** The suggestion list is labelled "Address suggestions" and shows "No suggestion" when empty.

**Example.** Service Base Adresse Nationale, Fill other fields `postcode=zip` and `city=town`: choosing a suggestion fills the fields named `zip` and `town`.

## Textarea

**Use.** Several lines of text: a message, a comment.

**Options.** Value, Placeholder, Icon, Width, Height (px or %), Max. length (empty or 0 means unlimited), Show chars left, an HTML option that renders the Joomla editor instead of a plain box, Hint.

**Stored.** The text. Line breaks are kept in e-mails.

**Accessibility.** Required marker and `aria-required` as for the Textfield.

**Example.** Name `message`, Max. length 500, Show chars left on.

## Number input

**Use.** A number, with browser arrows.

**Options.** Value, Placeholder, Icon, Size, Max. length, Hint. Advanced: **Minimal value**, **Maximum value**, **Step**.

**Stored.** The number typed. The browser enforces the limits. Do not rely on them alone for sensitive data.

**Accessibility.** Rendered as `type="number"`. Required marker and `aria-required` apply.

**Example.** Name `guests`, Minimal value 1, Maximum value 10, Step 1.

## Slider

**Rating.** The option **Rating** of a Number input or a Slider shows **Stars** (3 to 10, from **Maximum**, 5 by default) or a **Scale** of buttons (from **Minimum** to **Maximum**, 0 to 10 by default, 21 choices at most), with an optional caption on each side, a large size and a colour. Nothing is chosen at first, so a **Required** rating with the `ff_valuenotempty` validation must be answered; a second click on the chosen note clears it. The stored value is the number, and the server refuses a number outside the scale.

**Use.** A number chosen on a slider with its value displayed.

**Options.** Same as Number input: Minimal value, Maximum value, Step. In the editor, Slider is a Number input with the **Slider** option on. The value is stored the same way.

**Stored.** The number chosen. Rendered as `type="range"`.

**Example.** Name `satisfaction`, Minimal value 0, Maximum value 10, Step 1.

## Calendar

**Use.** A date chosen in a popup calendar.

**Options.** **Format** (follows the Joomla calendar API), Value (default; PHP allowed; `12-12-2012: :CLICK ME` also sets the button label), Icon, Size, Hint.

**Stored.** The date as text, in the chosen format.

**Accessibility.** The calendar icon is hidden from screen readers. Required marker and `aria-required` apply.

**Example.** Name `birthdate`, Format `%d-%m-%Y`.

## Responsive Calendar

**Use.** The same job as Calendar, with a layout that adapts to small screens.

**Options and storage.** Same as Calendar.

## Hidden input

**Use.** Carry a value through the form without showing it: a campaign code, a computed value.

**Options.** Value (plain text or PHP). Advanced: Order (Number), Logging.

**Stored.** The value as posted. A visitor can change it with browser tools, so do not use it for prices or rights.

**Example.** Name `campaign`, Value `autumn-2026`.

## File

**Use.** Let the visitor send one or more files.

**Options.** Readonly, **Drop zone**, Hint. Advanced: Use HTML5 upload, Use Flash upload (legacy), Allow multi HTML5/Flash uploads, HTML5/Flash max. filesize in bytes (0 = no limit), HTML5/Flash width and height, **Upload Directory**, Image Resize Width and Height (0 for disabled), Image Resize Type (empty, `crop` or `simple`), Image Resize BG-Color, Timestamp, **Allowed file extensions**, Attach to mailback address(es), Attach to admin mail(s), Use Url, Web Download Directory.

**Drop zone.** Lets visitors drag files onto the field or browse for them, with the prompt "Drop files here or browse". It is meant for the standard upload mode.

**Allowed file extensions.** Comma-separated, for example `pdf,jpg,png`. Case and spaces do not matter. Every file of the field is checked, not only the first. If the list is empty, any file is allowed except the ones below. If the list is set, the last extension of the file name must be in it.

**Files always refused**, whatever the list says:

- Programs and scripts: `exe`, `dll`, `bat`, `cmd`, `com`, `msi`, `scr`, `jar`, `war`, `vbs`, `vbe`, `wsf`, `ps1`, `reg`, `lnk`.
- Server-side scripts anywhere in the name, for example `shell.php.jpg`: PHP (`php`, `php5`, `phtml`, `phar` and similar), `inc`, `cgi`, `pl`, `py`, `rb`, `asp`, `aspx`, `jsp`, `sh` and other server types.
- Server configuration names: `.htaccess`, `.htpasswd`, `.user.ini`, `php.ini`, `web.config`, `.env`.
- Names with control, invisible or bidirectional characters, or a colon.
- A name without an extension, when a list of allowed extensions is set.

**Active content is refused unless you list it.** `html`, `htm`, `xhtml`, `svg`, `svgz`, `xml`, `xsl`, `js`, `mjs` and `swf` are accepted only when you name that extension in the list. An empty list does not allow them.

When a file is refused, the visitor sees: "The file '...' has an invalid extension. Allowed extensions: ...". If the list is empty, the message says executable files are not allowed.

**Size limits.** The field's own limit is **HTML5/Flash max. filesize in bytes**. For chunked uploads, when the field sets 0, a ceiling of 256 MiB (268,435,456 bytes) applies to one file. Other limits come from your PHP and server settings.

**Upload directory.** Use the `{ff_uploads}` substitution or an absolute server path, not a web path. The `{ff_uploads}` value can be set in the BreezingForms configuration. Uploads kept inside the Joomla site folder can be protected with `.htaccess` and `index.html` guard files. An existing file of the same name is replaced, or kept with a random prefix, depending on the upload override setting. Underscores in the name of an upload field are discouraged by the editor tooltip.

**Stored.** The server path of each file, or its web address when **Use Url** is on. Images can be resized when both width and height are above 0. With **Attach to admin mail(s)** or **Attach to mailback address(es)**, the files are attached to the e-mails.

**Accessibility.** Required marker and `aria-required` apply. The drop zone also offers a "Browse files" button and a "Selected files" list.

**Example.** Name `cv`, Allowed file extensions `pdf,doc,docx`, Upload Directory `{ff_uploads}`, Attach to admin mail(s) on.

## Checkbox

**Use.** One box: accept the terms, subscribe.

**Options.** Value (what is sent when checked), Checked (preset), Readonly, Hint.

**Stored.** The value when the box is checked. When unchecked, nothing is posted.

**Accessibility.** Required marker and `aria-required` apply.

**Example.** Name `terms`, Label "I accept the terms", Required on, Validation Library `ff_valuenotempty`.

## GDPR consent

**Use.** The agreement of the visitor to the use of their information.

**Options.** The **Label** is the short name of the field; **Text to accept** is the sentence next to the box. Then the options of a Checkbox, plus **Privacy policy address** (a web address or `index.php?…`), linked next to the box. The box is never checked beforehand. A new one is required, with the `ff_checked` validation and a default text that you should adapt.

**Stored.** The proof written by the server, for example `Accepted on 2026-11-26 19:05:09 UTC: “I agree …” — https://example.org/privacy`: the date, the text of the box and the linked address. An unchecked box stores nothing. The IP address is not part of the proof.

**Watch out.** Like other required fields, the box is required by the form in the browser; a submission without it is stored without any consent. A script that compares the value of this box with `yes` must now look for a non-empty value.

## Checkbox group

**Use.** Several boxes where the visitor may tick any number.

**Options.** **Group** (one line per box), Readonly, **Wrap each element** (one per line instead of inline), Hint.

**Group format.** Each line has three parts: `0` or `1` to say whether the box is ticked at first, the label and the value to submit. Example: `0;Click Me;clicked`.

**Stored.** The values of the ticked boxes.

**Accessibility.** The group has `role="group"` and is labelled by the field label. When Required is on, a hidden "Required" text is added instead of `aria-required`.

**Example.**

```
0;Newsletter;newsletter
0;Events;events
1;Offers;offers
```

## Radio group

**Use.** Exactly one choice among a few.

**Options.** Group, Readonly, Wrap each element, Hint. The line format is the same as the Checkbox group.

**Stored.** The value of the chosen button.

**Accessibility.** Same as the Checkbox group.

**Example.**

```
1;Individual;individual
0;Company;company
```

## Select

**Use.** A drop-down list, good for many choices.

**Options.** **List** (one line per item, same `0;label;value` format), **Multiple**, Width, Height, Readonly, Hint.

**Stored.** The chosen value, or the chosen values when Multiple is on.

**Accessibility.** Required marker and `aria-required` apply.

**Example.**

```
0;France;fr
0;Belgium;be
1;Switzerland;ch
```

## Captcha

**Use.** A built-in image code that stops simple bots. No account needed.

**Options.** Captcha Width (px), Hide label, Hint.

**Stored.** Nothing. The code is checked on the server when the form is sent.

**Accessibility.** The image has an alternative text, and a button reloads a new image. An image code is hard for some visitors, so consider ReCaptcha.

## ReCaptcha

**Use.** Google robot check.

**Options.** NewCaptcha (Google's newer robot recognition), Invisible, Site Key, Secret Key, ReCaptcha Size (`normal` or `compact`), Hide label, Hint.

**Stored.** Nothing. The Google response is verified on the server with your Secret Key.

**Privacy.** The visitor's browser contacts Google. Mention it in your privacy policy.

## Signature

**Use.** A handwritten signature drawn on a canvas, with a **Reset** button.

**Options.** Hint. Advanced: Hide label, Logging, Order (Number).

**Stored.** A PNG image. The image is saved in `media/breezingforms/signatures/` as `fieldname-<hash>.png`, and the submission stores that file name. The server writes it only when it is a real PNG of at most 1 MiB (1,048,576 bytes). Otherwise the value is empty. The folder is protected against direct web access.

**In e-mails and PDF.** The PNG is attached to notification e-mails (unless the e-mail format is set to the XML-only mode, value 4) and shown in PDF exports. In lists and exports, the value is the file name.

**Example.** Name `signature`, Label "Sign here", Required on.

## PayPal

**Use.** A PayPal payment button.

**Options.** Business (your PayPal e-mail), Token, Item name, Item number, Amount (empty lets the payer enter it), Tax, Thankyou page, Cancel URL, Locale, Currency code, Email notification on successful payment only, Hint. Test account and IPN options are available in the editor.

**Notes.** A Token is needed for verified payments and instant payment notifications. "Email notification on successful payment only" needs the token, or the payment can never be verified.

**Example.** Item name "Ticket", Amount `25.00`, Currency code `EUR`.

## Stripe

**Use.** Card and other payments on the Stripe payment page.

**Options.** Publishable Key, Secret Key, Webhook signing secret, Webhook endpoint URL (shown after the form is saved), Amount to charge (Fixed amount or From an element), Amount, Element holding the amount, Currency code, Payment methods (Stripe chooses automatically, or I select the methods myself, with Bank cards always on, Klarna and Wero options), Item name, Thankyou page (BF page, External URL, Page number), Email notification on successful payment only, Hint.

**Amount warning.** An amount taken from a free field passes through the browser, and the visitor can change it. A list or radio field is checked against its values. To impose a price, compute it on the server in a Begin Submit piece. The amount format is a main unit with a decimal separator, for example `12.99` or `12,99`. If it is empty or invalid, the form is not submitted.

**Mode.** The editor shows Test mode or Production mode from the Secret Key. A method only appears if it is enabled in your Stripe Dashboard. Cards (CB, Visa, Mastercard) are always offered. To process French cards on the CB network, turn on **Cartes Bancaires** in the Stripe Dashboard: for a payment in euros with a co-badged card, Stripe Checkout then lets the customer choose the network.

**Example.** Fixed amount `49.00`, Currency code `EUR`, Webhook signing secret filled in so the payment is recorded even if the customer does not come back.

## Submit Button

**Use.** The button that sends the form.

**Options.** Value (the button text), Hint.

**Stored.** Nothing.

## Summarize

**Use.** Show a recap of another field, or a calculated total.

**Options.** Connect with (the field to show), Empty message (default "not available"), Hide row if empty, Use element label, Calculate fields (JavaScript that adds up values, for example `value = Number( value ) + Number( bfField( 'FIELDNAME' ) );`).

**Stored.** The shown value.

## See also

- [Installing and updating BreezingForms NG](01-installation.md)
- [The QuickMode editor](03-quickmode-editor.md)
- [Form modes](../maintenance/form-modes.md)
