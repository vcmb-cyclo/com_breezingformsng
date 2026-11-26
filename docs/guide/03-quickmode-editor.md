# The QuickMode editor, screen by screen

QuickMode is the editor where you build a form in BreezingForms NG. You see the structure of the form as a tree on the left. You edit the selected item on the right. This guide walks through each part of that screen.

This guide covers the screen itself: the tree, the panels, saving and previewing. It does not describe every field type. For that, see [Field types](04-field-types.md).

## Before you start

- You need the Joomla permission to edit forms (`core.edit`) to open an existing form. You need the permission to create forms (`core.create`) to start a new one. Without it, the editor sends you back to the forms list with an error message.
- A form in the trash cannot be opened in the editor.
- To open the editor, go to **Components > BreezingForms NG > Forms** and open a form, or create a new one. The address looks like `index.php?option=com_breezingformsng&task=quickmode.display&form=FORM_ID`.
- A new form has a temporary random name such as `QuickForm123456`. Change it before you save (see "Form properties").
- Some tabs and buttons only appear once the form has been saved once. They are noted below.

## 1. The layout of the screen

The editor has three areas.

1. **The toolbar** at the top. Buttons: **Save**, **Versions** (only if form history is turned on, see "Version history"), **Preview**, **Site Preview** and **Close**. **Preview** and **Site Preview** only appear for a form that has been saved.
2. **The structure tree** on the left, titled "Form structure" for screen readers. Above it are three buttons: **Page**, **Section**, **Element**, and a gear button named **Settings**.
3. **The properties panel** on the right. It has three tabs: **Properties**, **Advanced** and **Options**.

Above the properties panel, for a saved form, you also see:

- a **Published** switch and a **Debug** switch. They change the form state at once, without a save;
- a language switch, if more than one language is installed (see "Several languages");
- an **Unsaved changes** badge. It appears as soon as you change something and goes away after a save.

The **Settings** (gear) menu has two switches:

- **Keep panel docked**: keeps the properties panel in view while you scroll a long tree.
- **Scroll element list**: makes the tree scroll on its own inside its area.

In debug mode two more tools appear: a **Search form** box ("Search a word or value") that lists matching items, and number badges (form number and element number).

## 2. The structure tree

The tree always has the same four levels.

| Level | What it is | What it can hold |
| --- | --- | --- |
| Form (top) | The whole form | Pages only |
| Page | One screen of the form, shown with a Next/Back step | Sections and elements |
| Section | A group of elements | Sections and elements |
| Element | One field or button | Nothing |

These rules are enforced. You cannot drop a page inside a page, or an element directly in the form.

Pages are named automatically "Page1", "Page2" and so on. When you add, move or delete a page, the pages are renumbered for you.

### Select an item

Click an item in the tree. The right panel switches to the matching properties: Form properties, Page Properties, Section Properties or Element Properties. Your edits on the item you leave are kept automatically when you select another one.

The **Options** tab is only visible when the form (the top item) is selected. If you select anything else while **Options** is open, the editor goes back to **Properties**.

### Add items

New items go inside the item that is selected.

1. Select where the new item should go.
2. Click **Page**, **Section** or **Element**.

Rules:

- **Page**: select the form at the top of the tree.
- **Section**: select a page or a section.
- **Element**: select a page or a section. A new element starts as a text field named "untitled element". Choose its real type in the **Type** list of Element Properties (it starts on "Choose one").

If you pick the wrong place, the editor shows one of these messages:

- "A page can only be added to the form. Select the form at the top of the tree, then try again."
- "The form can only contain pages. Select a page or a section in the tree, then add this item again."
- "An element cannot contain anything. Select a page or a section in the tree, then try again."
- "You cannot create this here!"

### Move items

**With the mouse.** Drag an item and drop it where you want it. The drop is only accepted if the target can hold it (see the table). Otherwise nothing moves.

**With the keyboard.**

1. Use the arrow keys to move between tree items.
2. Press **Alt+Up** or **Alt+Down** to move the focused item one step. It swaps with its neighbour. At the edge of its container, it moves into the previous or next page or section, if that one may hold it.
3. A hidden live message tells screen reader users where the item went: "%1$s moved to position %2$d of %3$d" (with the real name and numbers).

These keyboard shortcuts exist in the code of version 6.1.0-RC23 and later (`quickmode-tree-model.js` and `quickmode-app.js`).

### Context menu: Copy, Paste, Delete

Right-click an item. From the keyboard, press **Shift+F10** or the **Menu** key on the focused item.

| Item | Menu entries |
| --- | --- |
| Form (top) | none |
| Page | **Paste** (if something was copied), **Delete** |
| Section | **Copy**, **Paste** (if something was copied), **Delete** |
| Element | **Copy**, **Delete** |

To duplicate a field:

1. Right-click the element, choose **Copy**.
2. Right-click the page or section where the copy should go, choose **Paste**.

A pasted item gets new internal names, so there are no name clashes. A copied section brings all its elements. You can only copy sections and elements, not pages.

**Delete** removes the item and everything inside it, with no confirmation. The change only becomes permanent when you save. If you delete by mistake, reload the editor without saving, or use the version history if it is on.

## 3. The properties panel

### Form properties (the top item selected)

Tab **Properties**, group "Form properties":

| Label | What it does |
| --- | --- |
| Title | The form title (required). |
| Name | The technical name (required). Letters and numbers only, no underscores. |
| Description | Free text description of the form. |
| Last page is thank you page | When you have more than one page, the last page is shown only after a successful submit. |
| Mail notification | Turns on the e-mail sent to the administrator. |
| Mail recipient(s) | The address or addresses that receive it. |
| Include submit button | Yes or No. |
| Submit button label | Text of the submit button. |
| Paging include | Yes or No. Shows the Next and Back buttons. |
| Next page label / Previous page label | Texts of the paging buttons. |
| Include cancel button | Yes or No. |
| Cancel button label | Text of the cancel button. |

Hover a label to get a tooltip with a longer explanation.

Tab **Advanced**, for the form:

- Group "Form Themes": see "Themes and layout" below.
- Group "Other Options":
  - **Use Joomla tooltips for hints**.
  - **Use Sweetalerts on errors**.
  - **If no alert on errors**: choose **Use default errors** or **Use balloon errors**. The default errors are listed at the top of the form and each message is repeated under its field.
  - **Fading effect on form entry** and **Rollover** / **Rollover color** (only with the BreezingForms theme engine).
  - A text box for the **Form toggle fields** rules (visibility rules).

Tab **Options**: see section 5.

### Page properties

Tab **Properties**, group "Page Properties": one field, **Intro**. Click **Edit** to open a rich-text editor in a window. Click its **Save** button, then close the window. The intro is shown at the top of that page of the form. The **Advanced** tab has nothing for a page.

### Section properties

Tab **Properties**, group "Section Properties":

| Label | What it does |
| --- | --- |
| Type | **Normal** (no frame) or **Fieldset** (a framed group). |
| Display type | **All in a row** (elements side by side) or **Wrap after each element** (one per line). |
| Title | Section title. |
| Name | Section name (required). |
| Description | Click **Edit** to write rich text for the section. |

Tab **Advanced**: **Turn off**. The section starts disabled on the form.

### Element properties

Tab **Properties**:

- **Type**: the field type, in groups (input, choice, security and payment, action and view). Changing the type replaces the element and starts it with that type's defaults. A text field and its presets (phone, date and time, country, address) are the same element: switching between them keeps your settings.
- "Element Properties": **Label** (required) and **Name** (required, letters and numbers only, must be unique in the form), then the options of the chosen type.
- Group "Validation": choose **None**, **Library** or **Custom** validation script.

Tab **Advanced**, group "Advanced Element Options": options of the chosen type, then, for most types, **Hide In Mailback**, **Tab index**, **Turn off** and **Title position** (left, top, right, bottom; hidden with the Bootstrap engine). Below them are two script groups, **Initscript** and **Actionscript**. Each takes **None**, **Library** or **Custom**. With **Custom** you can press the code framework button to generate a starting function.

All the options of each field type are in [Field types](04-field-types.md).

## 4. Themes and layout

On the form (top item), tab **Advanced**, group "Form Themes", use **Choose a theme engine**.

**BreezingForms engine** ("BreezingForms Themes"):

- Pick a **Theme** from the list. The list reads the folders in `media/breezingforms/themes/`. In the package these are: aqua, breeze, default, glossy_blue, glossy_gray, qmtheme, vcmb_j5, vcmb_j5b, vcmb_j6.
- This is the Classic renderer (the original BreezingForms look, independent of Bootstrap).

**Bootstrap engine** ("Bootstrap Themes"):

- Pick a **Theme**: "Default", "carte" (shipped with the component), plus any folder you place in `media/breezingforms/themes-bootstrap5/`.
- **Enable one-page Layout**: Yes uses the **OnePage** renderer instead of the regular Bootstrap one. With it, the whole form is shown on one screen, and file uploads are forced to the HTML5 type.
- **Labels Top**: all labels above their fields.
- **Use page intro accentuation**: highlights the page intro.
- **Use page description accentuation**: highlights section intro text.
- **Display form progress**: shows a progress meter.
- **Edit theme variables**: appears when the chosen theme folder has a `vars.txt` file. It lets you edit that file.

How the site chooses the renderer (`QuickModeRendererFactory.php`): Bootstrap engine plus one-page Layout = OnePage; Bootstrap engine alone = Bootstrap 5; anything else = Classic.

### Two columns (worked example below)

QuickMode has no "columns" setting. Use a section with **Display type** set to **All in a row**. With the Bootstrap engine, the elements of that section are placed in one Bootstrap row, in equal-width columns. Two elements give two columns. Check the result with **Site Preview**, because the exact look depends on the theme and the engine.

### Debug mode

Turn on the **Debug** switch at the top of the editor. While it is on (it stays on until you turn it off, here or in the forms list), Super Users logged in to the site see a bar above the form with the form name, the account, the render time, the form details and its fields with their validation (never the submitted values). The bar also switches between automatic, light and dark colours, and tries any installed theme (`*` marks the configured one). On a Bootstrap or one-page form, the **Classic** group of the list shows the form with the Classic engine and the chosen theme; some fields may then look or behave differently: these choices are carried by the page address and nothing is saved. The technical name of each field is shown next to its label; untick **Field names** in the bar to hide them. Other visitors see no change.

## 5. The Options tab (e-mail, scripts and more)

The **Options** tab shows only for the form item, and only for a form that has been saved once. A new form shows: "Save the form first to access these options."

It contains its own tabs:

- **General**: Title, Name, Package, Description, Pages, CSS Class, Width, Height, **Log submissions to database**.
- **Email Notifications**, with two sub-tabs:
  - **Administrator Notification**: Admin Email Notification, Admin Email Address(es), Custom Mail Subject, Alt. Mail From, Alt. From Name, Report, Attachment, and an e-mail body with a preview of the default mail. Tick **Send as HTML** to write an HTML body. Click a field name to insert it.
  - **User Confirmation**: User Email Notification, User Mail Subject, Alt. Mail From (User), Alt. From Name (User), and its own e-mail body.
- **Scripts**: **Form Init Script** and **Form Submitted Script**. Each can use a library script or custom code.
- **Form pieces** and **Submit pieces**: code pieces run at fixed points of the form. A warning icon on the tab appears if a library piece is missing.
- **MailChimp®**, **Salesforce®**, **Dropbox®**: connection settings.

A number badge on a tab shows how many entries are set in it.

The Options tab is saved together with the form when you click **Save**. You need the "Manage Code" permission to change scripts and pieces. Without it, your other changes are saved and you see: "The changes to the form's code (pieces and scripts) were not saved: you need the "Manage Code" permission to change them. Your other changes were saved."

## 6. Previewing

- **Preview**: opens the form in a window inside the editor, with the Joomla template removed.
- **Site Preview**: opens the form as the site shows it, with your template.

Both show the **saved** version. Save first, then preview. They are not available for a form that has never been saved.

## 7. Saving, concurrent edits and history

### Save

1. Click **Save** in the toolbar. There is a single save button. It saves and keeps you in the editor.
2. The editor checks the item you are on. It reports errors (see below) without saving.
3. A long form is sent in several parts. You may see "Saving… (1/3)".
4. The page reloads on the same tab, and the **Unsaved changes** badge disappears.

On the first save of a new form, it receives its number, and the **Options** tab becomes usable.

### Concurrent edit protection

When you open a form, the editor remembers its state. On save, the server compares this with the current state of the form, inside a database transaction. If the form changed since you opened it (another administrator saved, or a change was made elsewhere), the save is **refused** and nothing is overwritten. The same happens when another user has the form checked out. You see:

"The form has changed or another user has locked it. Reload the editor before trying to save again."

Then reload the page. You lose your unsaved edits, so copy anything important first.

The **Close** button checks the form in (releases the lock) and returns to the forms list. Use it, not the browser back button, so the form is not left locked.

### Version history

If **Save history** is enabled in the component options (the default is Yes, with a history limit of 10), a **Versions** button appears in the toolbar for saved forms. Each save adds a version. The page title shows "(version N)".

## 8. Several languages

If more than one site language is installed, a language switch appears above the properties for a saved form. The first entry is the default site language. Choose another language to see extra fields, marked with the language code, under the label of each translatable text (titles, button labels, hints, intros). Fill them in to translate the form. Choose the default language to go back. Switching language reloads the editor, so save first.

## 9. Error messages in the editor

Errors appear at the top as Joomla messages, and the faulty field gets the focus.

| Message | Cause |
| --- | --- |
| Please enter a title | Form title or element label is empty. |
| Please enter a name | Form, section or element name is empty. |
| Only use alphanumerical characters for the name (and no underscores!) | The form or element name has other characters. |
| The element name already exists, please choose a different one:  | Another element already uses this name. |
| The Stripe element %s takes its amount from an element: choose an element of the form. | A Stripe element points to a field that no longer exists. |
| The save did not finish: the options and the version may be missing. See the log of the component, then save again. | The form was saved but the Options tab or the version failed. Save again. |
| The form has changed or another user has locked it. Reload the editor before trying to save again. | See "Concurrent edit protection". |

On a connection error while saving, the editor retries after 120 seconds.

An old form that contained Sofort payment elements shows a warning. Sofort elements are removed in the editor. Saving applies the removal. If Sofort was the only payment, saving unpublishes the form.

## Worked example 1: a two-page form

Goal: page 1 asks for a name and e-mail, page 2 asks for a message and has the submit button.

1. Open the **Forms** list and create a new form. The tree shows the form at the top.
2. Select the form. In Form properties set **Title** to "Contact", **Name** to "contact" and **Mail recipient(s)** to your address, then tick **Mail notification**.
3. With the form selected, click **Page**. "Page1" appears and is selected.
4. Click **Section**. A section is added to Page1. Set its **Name** to "who".
5. With the section selected, click **Element**. Set **Type** to Text field, **Label** to "Your name", **Name** to "yourname".
6. Select the section again, click **Element**. Set **Type** to Text field, **Label** to "E-mail", **Name** to "email".
7. Select the form again and click **Page**. "Page2" appears.
8. With Page2 selected, click **Element**. Set **Type** to Textarea, **Label** to "Message", **Name** to "message".
9. Click **Element** again for a submit button: set **Type** to Submit button, **Label** to "Send", **Name** to "send".
10. Click **Save**. The form gets its number and the **Options** tab becomes available.
11. Click **Site Preview**. Check the Next and Back buttons.

To change a label such as "Next page label", select the form and edit **Next page label** and **Previous page label**.

## Worked example 2: a section with two columns

Goal: put "First name" and "Last name" side by side.

1. Select a page.
2. Click **Section**. Select it and set **Display type** to **All in a row**. Set **Name** to "names".
3. With the section selected, click **Element**. Make a Text field: **Label** "First name", **Name** "firstname".
4. Select the section again, click **Element**. Make a Text field: **Label** "Last name", **Name** "lastname".
5. Make sure the form uses the Bootstrap engine (form, **Advanced** tab, **Choose a theme engine**).
6. Click **Save**, then **Site Preview**.

Both fields sit in one row, in two equal columns. To go back to one field per line, set **Display type** to **Wrap after each element**.

## Tips

- Save often. Check the **Unsaved changes** badge before you leave.
- Pick a page or a section before you click **Element**.
- Use **Copy** and **Paste** to build repeated blocks.
- Keep element names simple. They show up in saved data and e-mails.
- Turn on **Debug** to see form and element numbers and to search inside the form.
- Use **Close** when you finish, so the form is released for others.

## Troubleshooting

- **"You cannot create this here!" or a similar message when adding an item.** You selected the wrong level. See "Add items".
- **The Options tab is missing.** Select the form at the top of the tree. If it says "Save the form first", save once.
- **Preview shows the old form.** Preview shows the saved version. Save, then preview.
- **Save refused with "The form has changed or another user has locked it".** Reload the editor. See "Concurrent edit protection".
- **Script and piece changes are not kept.** You lack the "Manage Code" permission.
- **A theme is not in the list.** For Bootstrap themes, check that the folder is in `media/breezingforms/themes-bootstrap5/`. For the BreezingForms engine, use `media/breezingforms/themes/`.
- **Versions button missing.** Turn on "Save history" in the component options (Yes by default).

## See also

- [Installation](01-installation.md)
- [Field types](04-field-types.md)
- [Form modes (technical notes)](../maintenance/form-modes.md)
