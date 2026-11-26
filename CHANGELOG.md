# Changelog

## 6.1.1-RC4

### Added

- Add an appointment booking sample form that uses every field widget (phone, date, date and time, time, country, address and file drop zone), in the Samples of the Tools screen.
- Add the VCMB J5b QuickMode theme, with light and dark colours, compact fields and buttons, a ring around the active field and the help of each field written under it.
- Add a debug bar above the form on the website, shown to Super Users while the form is in debug mode: try any theme, switch between light and dark colours, show or hide the technical names of the fields and read the form details.
- Repeat each error message under its field in Classic forms, in addition to the list at the top.
- Add a sortable Debug column to the forms list, and an Actions menu to publish, unpublish and switch the debug mode of several forms at once.
- Add the E-mail address and Web address (URL) field types, with the matching phone keyboard and a check by the server.
- Add a GDPR consent field: its box is never checked beforehand, links your privacy policy, and each submission keeps the date and the accepted text.
- Show a number as a rating, in stars or as a scale of buttons, with captions, size and colour.
- Choose the type of a field in a panel with a search, your last choices and one column for each category.

### Changed

- Keep a form in debug mode until you turn it off: opening, saving or closing the editor no longer changes it.
- Show the error message closer to its field in Bootstrap and one-page forms.
- Count the same trashed items in the audit as in the Tools screen.
- Name the CB network in the Stripe payment methods of the editor and recall how to turn it on in Stripe.
- Use a slightly darker blue in the VCMB J5 and J6 themes so that white text keeps a sufficient contrast.
- Order the field types by likely use, and shorten the French name of the number field.

### Fixed

- Restore the country selector of the phone field in the Aqua, Breeze, Glossy Blue and Glossy Gray themes.
- Give a form row its theme colour again after the rollover highlight, including when the colours turn dark.

## 6.1.1-RC3

### Changed

- Manage uninstallation in Joomla's Extensions Manager. About Extensions still lets permitted administrators enable and disable plugins.
- Give extension descriptions more room in About Extensions and keep the full text available on hover.
- Soften the blue accents in the VCMB J5 and J6 form themes while keeping white text readable.

### Fixed

- Exclude Joomla templates from the BreezingForms extension list and audit so site templates are not flagged as legacy or offered for removal.
- Let administrators delete selected values of missing elements from the audit, with an explicit warning when values of existing records are included.
- Put audit repair actions above each orphan results table so they stay accessible without scrolling through long lists.

## 6.1.1-RC2

### Added

- Show the release date of every PHP and JavaScript library on the About screen, and sort both library tables by any column.
- Sort the About Extensions list by any column, and open the changelog of an extension from its version, in a coloured dialog like the one of the Extensions Manager of Joomla.
- Add a version note to forms, scripts and pieces: it is kept with the version that your save creates and shown in the Versions list.
- Add the VCMB J6 QuickMode theme, with light and dark colours, a narrow label column, a soft ring around the active field and the help of each field written under it instead of a hover icon.
- Select several values of missing records or of missing elements in the audit and delete them in one go.
- List the tables of each mixed collation in the audit, and repair the ones that do not use the target collation from there.

### Changed

- Make every section of the audit report collapsible, with its count in the title, and group the collation problems in collapsible sub-sections.
- Show the title, the name and the description of a form once in the editor, under Properties, instead of repeating them in the Options tab.
- Show the reassignment buttons of the audit in the yellow of the other repair buttons, and label the audit date Audited on.
- Give the Extensions list a wider name column, and cut the description after two lines with the full text in a tooltip.
- Improve the contrast of the VCMB J5 theme: readable legends and errors, visible field borders and focus outlines.
- Improve the administration screens in dark mode.
- Round the minimum of a date and time field set to now up to the next time step.
- Show the phone field next to its label when labels are on the left.

### Fixed

- Keep a description edited in the Options tab of the form editor: it was replaced by the older copy of the Properties.
- Show each line of a changelog as one item in the changelog dialog, instead of cutting it at quotes and symbols.
- Show a single external-link icon, instead of two, on the changelog link of the Extensions screen.
- Load the translations of the phone and date widgets on the site.

## 6.1.1-RC1

### Added

- Link record submitters and audit users to their Joomla profiles from the administrator.
- List every BreezingForms extension on the About Extensions screen, the former BreezingForms ones too and whatever their type, with the ID first, the description and the changelog link of each extension, and an uninstall button; "Manage extensions" opens the Extensions Manager of Joomla.
- Clean up from the audit what deleted forms left behind, one form at a time with its trash button or several at once with the selection: their elements are deleted and their records go to the trash of the records, to be deleted from there like any record; the values of records that no longer exist can be deleted.
- Show on each sub-tab of the form email options (administrator notification, user confirmation) how many settings it holds.
- Move several site menu items reported by the audit to the trash at once, with a checkbox on each of them and a button for the selection.

### Changed

- Disable Salesforce and Dropbox plugins by default on new installations while preserving plugin states during updates.
- Show the date of the audit in the time zone of the user instead of UTC.
- The site module and the content plugin are renamed (mod_breezingformsng, content/breezingformsng) so that they no longer share their names with the former BreezingForms; updating moves the module instances placed in the site and the settings of the plugin, and the caches are purged at the end of the installation.

### Fixed

- Stop listing as leftovers of the former BreezingForms the module and the content plugin that BreezingForms NG installs itself, and open the right entry of the Extensions Manager from the audit link.
- Do not send the administrator notification to the placeholder address "Enter your email address here" when no recipient is configured: the submission is stored and the missing recipient is logged instead of ending on a failed mail.

## 6.1.0

### Added

- Add a native Joomla 6 administration experience for forms, records, menus, scripts, pieces and Integrator rules, with search, filters, pagination, sortable columns and native edit screens.
- Add a modern QuickMode editor with an accessible keyboard-operable tree, unsaved-change protection, form and element search, debug details, improved narrow-screen layout and an About-page Piranha animation.
- Add package management and controlled package transfers: create, edit, inspect, import, export and dissolve packages without deleting their items, including selected forms, scripts, pieces, menus, rules and templates.
- Add record tools for CSV, PDF, XML and XLSX export, CSV import, record transfer between forms, and an audit that finds and repairs orphan data, deleted elements, legacy extensions and database issues.
- Add privacy/history controls, a reversible trash for forms and related objects, Tools screens for maintenance, statistics, logs and samples, plus a Bootstrap carte theme.
- Add optional MailChimp, Salesforce and Dropbox integrations, and an opt-in, permission-aware MCP endpoint for AI assistants to read, audit and manage forms.
- Add form widgets for international telephone numbers, date and time, country selection, address lookup and file drop zones, with ready-to-use sample forms.
- Add Stripe Checkout improvements, including configurable payment methods, server-verified webhooks and return destinations.

### Changed

- Complete the transition to Joomla 6 native MVC, dependency injection and web assets; remove obsolete legacy administration code and unsupported runtime dependencies.
- Improve package, form and record workflows: clearer import previews and results, version history, safer library updates, predictable naming, package filters and dedicated package management.
- Improve accessibility and localisation across the administrator and site interfaces, including semantic groups, labelled controls, keyboard interactions, contrast, responsive layouts and completed interface translations.
- Require PHP 8.4 or later. Existing sites running PHP 8.3 remain on their previous release until PHP is upgraded.
- Modernise form rendering, QuickMode trees and bundled libraries; remove discontinued Sofort support while retaining Stripe alternatives, and reduce obsolete package assets without removing the XLSX export or Piranha animation.

### Fixed

- Fix form, payment, upload, QuickMode, package, import/export, installer, record and audit regressions found throughout the release-candidate cycle.
- Keep form data, code, templates, package selections and editor changes intact when an operation is refused or interrupted, and make legacy updates and migrations resumable.
- Fix the infinite-recursion error after submitting a form when the MailChimp, Salesforce or Dropbox add-on is enabled.

### Security

- Harden permissions, CSRF checks and server-side validation across form editing, packages, uploads, payments, record transfers, audits and MCP actions.
- Protect submitted files, signatures, downloads, payment notifications, templates, logs and stored code against traversal, unsafe file types, unauthorised access, formula injection and sensitive-data disclosure.
- Strengthen Stripe, PayPal, double opt-in, CAPTCHA and upload handling, including replay protection, integrity checks and safer generated identifiers.

## 6.1.0-RC25

### Fixed

- Fix the error page "Maximum call stack size ... Infinite recursion?" shown after sending a form on a site where the MailChimp, Salesforce or Dropbox plugin is turned on (#236).

## 6.1.0-RC24

### Added

- Add a Package column to the forms list, hidden by default and sortable, that you can show from the Columns menu.
- Add a "No package" option to the package filter of the forms, menus, scripts and pieces lists, to find the items that belong to no package.
- Manage the content of a package from the Manage packages screen: create a new package, edit its details, add the items that have no package yet, take items out, or remove the package. The items themselves are never deleted.

### Changed

- Replace the Dissolve screen of the packages by Manage packages, which also lets you attach and detach forms, scripts, pieces and menu entries.
- Exporting no longer creates or saves a package on your site: it only downloads the package file. The scripts and pieces called by the exported forms and their elements are now included, so the file works on another site.
- Require the name of a form, script or piece to be made of letters, digits and underscores, and never empty. This applies to new names and to names you change; existing names are left as they are, and a form saved without a name gets one made from its title.
- Rename the site menu entry that groups the installed sample forms from "Tests" to "Samples", and update the texts of the Tools screen and the guides accordingly.
- Replace the standard scripts and pieces of the library by the new standard version when updating, after keeping the version of the site in the history of each item (Versions) with a note that says so; the installation summary lists what was replaced.
- Require PHP 8.4 or later: the installer refuses to install on older PHP versions, and the update manifest, the About screen and the documentation now announce PHP 8.4+. Sites still running PHP 8.3 stay on the previous release until PHP is upgraded.

### Fixed

- Keep what you typed and selected when saving or exporting a package is refused, instead of showing the screen empty again.
- Install the sample forms with their code (for example the live price of the event form) when your account may write code; they were installed without it and did not work.

## 6.1.0-RC23

### Added

- Reorder the elements of the QuickMode tree from the keyboard with Alt+Up and Alt+Down, and open its context menu with Shift+F10 or the Menu key. The new position is announced to screen readers.
- Offer the MailChimp, Salesforce and Dropbox integrations as plugins that you can turn on or off. Their tabs in the form options appear only while their plugin is enabled, and the settings already saved are kept.
- Turn the BreezingForms NG plugins on or off from the Extensions tab of the About screen.

### Changed

- Show the errors of the QuickMode editor as Joomla messages instead of browser pop-ups, all together, with the focus on the first field to correct.
- Turn the language selector of the editor into real links, and the publish toggles of the lists into buttons with an accessible name.
- Show the title of a form as plain text: a title that contained HTML tags now displays them as typed.
- Refuse files that a web server runs as scripts, such as .php files, in every upload field, even when the field accepts any type of file. The list of allowed extensions now applies to every form.
- Ask for the permission to create when copying a form, a piece or a script.
- Show the form sections and the groups of choices as fieldsets with a legend, so that screen readers announce the group name.
- Give the fields of the carte theme a visible border and a darker muted text, to reach the contrast that accessibility guidelines ask for.
- Make the Reset button an explicit button.
- Translate the default captions and the untitled element label of the QuickMode editor, and the remaining untranslated character counter.
- Give the content, privacy and MCP plugins clearer names, in all languages.
- Make the sample forms of the Tools screen easier to pick: a Select all box for the ones still to install, a highlighted row when one is ticked, an Install selection button that shows how many are ticked, and an installed form that no longer looks like a selection.

### Fixed

- Announce required fields to screen readers, with a hidden Required text next to the asterisk and the matching ARIA attribute.
- Describe the security code image in words, name its refresh button, and fit the image to narrow screens.
- Check every file of an upload field against the allowed extensions, not only the first one.
- Translate the Copy, Paste and Delete items of the QuickMode tree menu in all languages.
- Keep an interrupted update from leaving the modification dates of records half converted: the conversion is done all at once and resumes safely.
- Stop a double opt-in link without a valid token from confirming or cancelling anything, and say so instead of thanking the visitor.
- Load the previous and next record of a submission with bounded queries instead of reading every record.
- Stop warning an editor without the Manage Code permission when a save does not touch the code of the form.
- Compare the token of a double opt-in link with its exact letter case.
- Apply the same extension check to files sent in chunks as to other uploads.
- Show the names of the add-on plugins on the About screen instead of their language keys.
- Stop the Forms screen from reporting a failed check-in when the form was not locked.
- Remove markup from the status lines of the installation log.

### Security

- Keep the PHP pieces and scripts of a form out of reach of editors who do not hold the Manage Code permission: their changes to code are not saved and a warning says so. This also applies to package imports and to AI assistants (MCP).
- Accept a PayPal payment notification only when the payment went to the account set on the form.
- Check the security code before any uploaded file or signature is written, and accept only PNG images of at most 1 MB as signatures.
- Keep uploaded files inside their folder: a value typed in a field can no longer use .. to leave it.
- Never serve configuration or script files as a paid download, tie each download to its own form, and count the attempts so that simultaneous requests cannot exceed the limit.
- Check the size of a file sent in chunks before writing each chunk, in order, with a limit of 256 MB per file when the field sets none.
- Protect the upload folders and the folder of the payment emails waiting to be sent with access rules and an empty index page, and stop reading objects from that queue.
- Escape the title of a form wherever it is displayed.
- Check the server name of a Mailchimp API key before contacting Mailchimp.
- Ask for one click on a confirmation page before a double opt-in link confirms or withdraws a consent, so that mail scanners that open links cannot do it.
- Keep the queue of payment emails waiting to be sent as JSON in the temporary folder of Joomla, outside the web root; the entries of the previous version are still sent.
- Keep the code of the QuickMode elements and the file path of a paid download out of reach of editors who do not hold the Manage Code permission, also when a version is restored, a legacy form is converted or a package is imported.
- Refuse a theme name or a form name that points outside its folder when loading themes and the custom mail and PDF templates.
- Stop a QuickMode save from taking over an element that belongs to another form.
- Record a PayPal payment once, and notify once, even when PayPal sends the same notification twice at the same moment.
- Protect the folder of the signature images with an access rule.
- Make the image security code harder to guess: a code is dropped after five wrong entries, and a visit can only be given a limited number of new images.
- Refuse more risky files in upload fields: scripts and server configuration files, active content such as HTML, SVG and JavaScript unless the form lists them, and names that hide an extension with trailing dots, colons or invisible characters.
- Refuse as a paid download any file without an extension and any file in the folders of Joomla itself, such as administrator, libraries, plugins, configuration, temporary files and logs, or in system folders.
- Match the percent and underscore signs typed by a visitor literally in the contains, starts-with and ends-with rules of the Integrator.
- Keep a Dropbox error from stopping the sending of a form.
- Name the signature image files with a SHA-256 hash of their content instead of MD5.

## 6.1.0-RC22

### Added

- Let AI assistants (MCP) create and configure forms: create an unpublished form from a short list of fields, change its title, description, publication state, size and administrator email notifications, add, change, move or remove text, text area, choice and check box fields and pages with their introductions, and move a form to the trash or restore it. Every action follows the Joomla permissions of the account that calls it, and reading a form now also returns the help, size, default and length settings of its fields and the introduction of its pages.
- Add four field types to the QuickMode editor, chosen like the Slider: an International phone field with a country flag, the dial code and a number check, stored as +33612345678, a Date and time field with an earliest and latest value and a step, a Country list with the country names in the language of the visitor, and an Address search field that can suggest addresses and fill the street, postcode, city and country fields. The address suggestions are off by default: nothing is sent to a third-party service unless one is chosen in the field settings, and the editor says so.
- Add a Drop zone option to the File field, so that visitors can drop their files on the field or browse for them, with the list of the chosen files.
- Add three ready-to-use sample forms, installed from the Tools screen: an Event registration with a live price, a reduced rate and an optional lunch, a four-step Quote request with questions that depend on the project, an estimate that follows the choices, a specification file and a summary, and a Satisfaction survey with rating scales, an average score and a follow-up question for low ratings. A technical example, Field widgets, shows the new fields.
- Show on the Tools screen the sample forms by category, Business templates and Technical examples, with one check box per form and one Install button. An installed form is ticked and has a Manage link, and a form whose name is already used by another form cannot be ticked.
- Style the confirmation page that replaces a form after it is sent in the carte theme.

### Changed

- Show a short summary at the end of an installation or an update, with the versions, the plugins and the changes made, instead of every internal step. The whole detail stays in the installation log.
- Enrich the sample contact form with required fields and hints, a consent check box, a security code, an optional international phone, an email to the site with a copy to the sender, and a thank-you page.
- Draw the security code with a maintained library, as a clearer image of dark characters on a light background, without the characters that look alike.

### Fixed

- Accept the security code of a form: the image is displayed again and the code that is typed is checked when the form is sent, which refused every code before.
- Let a form with a file field be sent: it no longer stops with a technical error.
- Send a form whose confirmation email field is left out without an error.
- Install a sample form again when another form already has its name: the Install check box is disabled with an explanation, the Manage link opens the existing form, and the message is a notice instead of an error.
- Prevent QuickMode saves from overwriting a form changed since the editor was opened.
- Exclude forms, scripts and pieces in the trash from package exports.
- Show validation messages without a field link alongside field errors in Bootstrap forms.
- Keep restored Integrator rules out of permanent deletion when restoration and emptying the trash overlap.

### Security

- Check on the server the phone numbers, the dates and times and the country codes of the new fields, and refuse a value that is not valid without writing it in a log.
- Refuse a protocol-relative address in the report of the MCP database audit.

## 6.1.0-RC21

### Added

- Replace the Configuration screen by Tools, laid out like the Joomla control panel: Maintenance tiles (Audit with the number of issues counted in the background, Statistics, Trash with the number of items it holds, Log), Package tiles (Install, Create / edit, Dissolve), the AI assistants (MCP) status with its address, and the sample data card.
- Give the Audit, the Statistics, the Trash and the Log their own screens under Tools, with a Back button. The Statistics list the records of each form by state (published, archived, trashed) with links that open the form and its records in a new tab, and the tables with their rows and size, with a hint on the tables whose columns use another collation than the table.
- Add a trash to the lists of forms, scripts, pieces and Integrator rules: Trash instead of Delete, restore with Publish or Unpublish from the Trashed filter, and Empty trash to delete for good after a confirmation. A trashed form is no longer shown on the site, copied or exported, a trashed script or piece no longer runs and a trashed rule no longer fires.
- Show on the Trash screen one table per kind of object, with the id, title and name of each trashed form, script, piece and rule, its own Empty trash button and a link to its trashed list. The number of trashed records of a form opens them.
- Install a sample contact form from the Sample data card of the Tools screen, after a confirmation. It is also added to the site menu under Tests, is never replaced if it is already installed, and needs administrator rights.
- Add the carte theme for the forms in Bootstrap mode, chosen from the Bootstrap theme list of the form properties: a white card, soft fields, pill-shaped choices and an outlined main button, as one stylesheet. The sample contact form uses it.
- Add a Privacy and history tab to the options and describe each tab.

### Changed

- Move the audit report and the installation log out of the About screen: the Actions menu and the Show log button are gone, and the Extensions screen gets its Back and Manage plugins buttons in the toolbar.
- Keep the open or closed sections of the Record screen when going from one record to the next.
- Tell at once that a configuration export is not a package when it is chosen in the package import, instead of asking to confirm a package of 0 forms.
- Remove the Export and Import configuration actions: the import never worked and the packages cover the transfers.
- Redesign the Version information block of the About screen: four framed tiles with the version and its build, the creation date written out in the language of the administrator, the author with the copyright years and the license with a link to its details. The PHP and JavaScript library lists open and close like the other screens.
- Show the GitHub link of the About screen with its star count in a single link, and flag the library loaded from an external service (reCAPTCHA) with an amber badge in the JavaScript libraries list.

### Security

- Count the issues of the Audit tile with a read-only request that needs a valid token and the right to manage the component.
- Open a form in QuickMode without locking it: the lock is taken when the form is saved, with a token, and saving a form needs the right to edit or create it and refuses a form that is in the trash.

## 6.1.0-RC20

### Added

- Move the values of a deleted element to another element from the About audit with one icon per element. A dropdown lists the compatible elements of the form alphabetically, the one with the same name already selected, and the icon opens a preview of each value with the value the target already holds: only the values you check are moved, a record that already holds another value is marked "Already assigned" and left alone, and a value identical to the one the target already holds is removed as a duplicate. Text, hidden, text area, calendar and number elements are interchangeable, so a value can go to a hidden element such as StripeId; every other type only matches itself. Long lists are previewed 500 values at a time.
- Delete the values of a deleted element for good, including those of records that still exist, from the About audit, after a confirmation that warns it cannot be undone.
- Open the records that hold the values of a deleted element from the audit: the Records list is filtered on them and says so.
- Show the number of the form and of its elements in the QuickMode editor when the debug mode is on, in the tree ("125 - Name") and in a badge of the properties. They follow the debug switch without reloading the page.
- Send a site menu item reported by the audit to the Joomla trash from the audit screen, with a message saying whether it worked and why not. The title of the item opens it in a new tab.

### Changed

- Rework the table of the values without an element in the audit: one Deleted element column, a link to the form editor, the number of records, the target element with its code, results shown right above the list, warning colours and correct singular and plural messages in every language.
- Show the code of each element under its label on the Record screen, and attach a value whose element was deleted to the current element of the same name only when that name is unique in the form and the type is the same, as the audit does, on display and on saving. A note under the label says so. The other values stay in the values without an element and are not changed when the record is saved.
- List every deleted element of the audit instead of the first ten, and open its sections by default.

### Fixed

- Make the trash button of the menu items of the audit work. It did nothing and closed the audit, because it sat in a form nested in the page form.

## 6.1.0-RC19

### Added

- Uninstall leftover pre-NG extensions from the audit of the About screen, one at a time or selected, with a link to the matching Joomla screen, and read a help tooltip on every audit title.
- Reassign stored values whose field was deleted to the current field of the same name, per field or all at once, or move them to a field you choose from the same-type fields of the form. Records that already hold a value for that field are skipped.
- Show on the record edit screen the stored values that no current field displays.

### Changed

- Move every row of a multi-valued field when values are reassigned, not only one per record.

### Fixed

- Restore the titles of the audit sections of the About screen and use proper non-breaking spaces in the French audit texts.

### Security

- Check the rights before saving a QuickMode form and refuse updates of a form that does not exist.
- Keep Salesforce responses and record IDs out of the public logs.
- Encode request parameters placed in inline JavaScript and display query list values as text.
- Restrict the files a theme can write and sanitize the names of uploaded files; the upload folders keep their access rules.
- Ignore invalid PayPal payment notifications.

## 6.1.0-RC18

### Added

- Transfer records to another form from the Records toolbar. Choose the records (any can be unchecked) and the target form, match each target field to a source field (fields with the same name are already matched, and a default value fills a field that has no source field), then confirm with an example of what the first record gives each field. A target field that is required in its form is marked and must be filled. The records of a deleted form can be transferred. Records are copied, not moved, and the originals can be sent to the trash. Payment information is copied only if you ask for it and confirm it.
- Convert a legacy form to QuickMode from the Forms list. It creates an unpublished copy and leaves the original form and its records untouched; settings that cannot be converted safely are refused and the unsupported element is named. Classic radio buttons that form an unambiguous group are converted to a radio group. The Convert to QuickMode button only shows when the list holds a form that is not in QuickMode format.
- Let AI assistants check a form for inconsistencies (missing names, duplicate element names, missing scripts, suggested validations) and audit the database, through two more read-only tools of the MCP endpoint.
- Dissolve a package selectively, and see packages and their audit in clearer views.
- Show an Audit information section in the script, piece, Integrator rule and QuickMode editors, and keep the version history of Integrator rules, with restore. Existing rules show their audit data as unknown.
- List and filter the records of deleted forms, with the form title they stored and a warning icon. The audit links each deleted form to its records.
- Delete the orphan elements and the values of missing elements from the audit of the About screen, one at a time or all at once, with a confirmation and without reloading the page. The audit shows the form each missing element belonged to.
- Read the database audit and delete the orphan elements and values through the Joomla API (/api/index.php/v1/breezingformsng/audit), with an API token and the same rights as the About screen.

### Changed

- Show the search, the filters, Clear and the page size on one line in the Records, Forms, Integrator, Menus, Pieces and Scripts lists, without the Filter Options button.
- Say courriel instead of email in French, use the typographic apostrophe and non-breaking spaces before punctuation, and translate the Integrator, ContentBuilder and PayPal tooltip labels that were still in English.
- Use one generic turn-off label for sections and elements, translated in Hungarian and Turkish.
- Name the Advanced options of an element clearly in every language.

### Fixed

- Stop logging six PHP warnings each time a form saved by an earlier version is saved in QuickMode.
- Let the date elements be left out of the log and given an order number, which the page never saved.
- Show a failed collation repair in the audit section of the About screen, next to the report it applies to, and fix the deleted-form records link and the orphan column label.

### Security

- Escape the page shown after a Stripe payment, which reflected the request token, and accept upload tickets only when the form issued them to the session, with element names that hold no path.
- Decide the PayPal price on the server and check what PayPal reports. A payment is accepted only once it is completed, and an invalid notification no longer overwrites a record.
- Neutralize spreadsheet formulas in CSV exports, so a spreadsheet application does not evaluate submitted text.
- Accept only known operators and connectors in Integrator criteria, including in imported packages, and no longer show the error message of a failed criteria query.
- Require the create right to copy a menu.
- Mask link parameter values, hosts, credentials, absolute paths and repair tokens in what the MCP audit tool returns.

## 6.1.0-RC17

### Added

- Let AI assistants such as Claude Code or Codex read your forms through a read-only MCP endpoint of the Joomla API, with a user's API token and that user's permissions. It is off by default: turn it on with "Enable MCP" in the options. The Configuration page shows whether it is enabled.
- Choose what to import from a package file: its items are listed by kind with their status (new, replaced or identical), identical items are left untouched, and the confirmation says what will be written.
- See what an import wrote: the forms, scripts, pieces, menu items, Integrator rules and template files, each with a link. Items about to be replaced link to the existing one.
- Import packages exported as XML by BreezingForms or FacileForms. The accepted formats are shown on the import screen, and the Import button stays disabled until a file is chosen.
- Export and import the custom email and PDF templates of forms, and their Integrator rules, in a package. Templates need the Configure permission and rules the code permission; without it they are left out with a warning and the rest of the package is imported.
- Record imports, copies and restores in a form's history, with a version note and the version of origin.

### Changed

- Package files hold the QuickMode template code of forms as readable text. Files exported by earlier versions are still imported.
- Show clearer import errors naming the file, the upload size and JSON problems, tooltips on the export fields, and the package name in the package list of a form.
- The standard database pieces (ff_query, ff_select, ff_selectValue and ff_databaseToSelect) use the Joomla database directly and no longer need the BreezingForms compatibility plugin. Updates keep any piece you customised.
- Use the word element in the Stripe amount help and capitalize the QuickMode add buttons in all eight languages.
- Update the bundled Stripe and PDF libraries.
- Remove the last Adobe Flash file from the package. Updates delete it from existing sites.
- Remove an unused copy of Font Awesome 4.7.0 (1.1 MB) from the package. Updates delete it from existing sites.
- Write the icons of QuickMode forms with the Font Awesome 6 prefix fa-solid instead of fas. Custom CSS that targets .fas on a form must target .fa-solid.

### Fixed

- Fix the standard piece ff_databaseToSelect, whose code had a syntax error.
- Export every form column, including the admin and user email settings, and refuse forms, scripts and pieces without a name or sharing one instead of silently keeping only one of them.
- Export a package without having to check both the template and the Integrator switches.
- Keep the elements of an imported form in their original order and keep the selection made in the import tables.
- Keep the Integrator rules of a replaced form on its new elements, and report the rules left without their element.
- Do not let a package overwrite the template files of a form of another package. The import says which forms were left out.
- Refuse a package whose forms have a missing, shared or invalid id, so that templates and Integrator rules cannot reach another form.
- Copy a form whose elements hold empty (NULL) columns.
- Translate the captcha refresh button and the double opt-in email subject where they were wrong or still in English.
- Remove PHP 8.5 deprecation notices raised when resizing uploaded images.
- Report form errors and upload errors together when importing records, with clearer upload size and extension messages.
- Load Joomla's own Font Awesome with QuickMode forms, so that field icons also appear on site templates that do not load it.
- Fix two icons whose Font Awesome names did not exist: the image icon of QuickMode forms and the rotated fullscreen button of the administrator.
- Fix the help text of the element icon field in Hungarian and Turkish, which translated the example icon names (fa-pencil, fa-user) into names that do not exist.

### Security

- Generate upload tickets, stored upload names, payment cache files and export file names from a cryptographically secure random source. They were predictable, and payment cache files sit in the public media folder.
- Answer the upload callback in plain text, so that browsers never interpret an uploaded file name as HTML.
- Require the code permission to export or import Integrator rules, and show names from an imported file as plain text in the confirmation.

## 6.1.0-RC16

### Added

- Search form scripts and form or submit pieces from QuickMode Options while Debug is enabled, including saved custom code and edits in the code editor.

### Changed

- Turn off a form's Debug mode when opening or closing QuickMode, while keeping it available through a Save reload.
- Show active Debug with the same red badge and white bug icon used by ContentBuilder.

## 6.1.0-RC15

### Added

- Search form elements by text or property value in the QuickMode editor while Debug is enabled.
- Choose a BreezingForms page number or an external URL as the Stripe return destination.

### Changed

- New Stripe elements use an amount from a form element by default.
- Improve contrast on selected administrator tabs and spacing around element script controls.

### Fixed

- Show active Stripe and email notification choices clearly, keep them readable on hover, and show the element label in form search results.
- Avoid an unsaved changes warning when browsing QuickMode elements and tabs without editing.
- Increase spacing between validation choices in the element editor.
- Warn before leaving QuickMode with a newly added element or other unsaved changes.
- Detect edits to element properties and Stripe settings in the unsaved changes warning.
- Keep the unsaved changes warning active after direct edits to any QuickMode element field.
- Keep the email editor toolbar visible when BreezingForms enters full page mode.
- Restore the None, Library and Custom selectors for scripts and pieces in QuickMode Options.

## 6.1.0-RC14

### Changed

- Reduce the installer download size by excluding unused CAPTCHA background-noise recordings.
  Installation and updates also remove these recordings left by earlier versions.
  Image CAPTCHA, XLSX exports and the About Piranha animation remain available.

### Fixed

- The Stripe and PayPal buttons start the payment without an action script, in both Fixed amount
  and From a field modes. With ActionScript set to None, a click on them did nothing.
- The Stripe button of the classic layout reads Stripe instead of PayPal when it has no image.

## 6.1.0-RC13

### Added

- The script editor shows an *Unsaved changes* badge and warns before you leave the page with
  unsaved changes.

### Changed

- In QuickMode, the number of pages of a form always comes from its tree. A value typed in the
  *Pages* field of the *Options* tab is ignored, and a wrong number is corrected at the next save.

### Fixed

- Show the messages of a QuickMode save on the page. They appeared in a hidden frame, so a failed
  save of the options looked like a success: the page now shows the error, stays open and lets
  you save again.
- Keep the notification recipient and the thank-you page script changed in the QuickMode tree.
  The save of the options put back their old values.

## 6.1.0-RC12

### Added

- Click the Piranha logo five times on the About page to play its animation. The final
  frame stays visible until you close it with X or Escape; opening it again restarts playback.

### Changed

- Remove the unavailable administrator form display, its Run Mode option and the Sync to
  Joomla Menu button. The update removes administrator menu entries that opened empty pages.
- Forms previously saved as Backend only are unpublished during updates and package imports
  to keep them off the site. Publish them explicitly to make them available. Imports warn about
  each affected form, show its previous publication state and record the change in the log.

### Fixed

- Display forms saved as Frontend and Backend through modules and article plugins instead of
  reporting that the form was not found.
- Keep a form's display mode unchanged when restoring a saved version.
- Remove obsolete administrator links from form error reports.
- Preserve component settings and correctly register the installed version when upgrading
  a legacy BreezingForms installation.

## 6.1.0-RC11

- Saving form options only writes the settings the screen sends, so a setting missing from a
  screen keeps its value.
- The *Administrator Notification* tab shows the report, attachment, email body, HTML sending
  and custom template settings, like *User Confirmation*. Each save reset its email type and
  custom template; a template lost this way cannot be restored.
- A QuickMode save no longer stops silently on a form with old elements on a site that displays
  PHP notices. It stopped after the tree was stored, without the options, the version or the
  reload. The notices now go to the component log.
- Each QuickMode save reloads the page, so the title shows the new version number. A second save
  in a row used to leave the old number on screen.
- Saving the QuickMode *Options* tab no longer breaks the rich text editors of the page.
- The save message reads « Enregistrement en cours… (1/2) », with its icon. It called a chunk of
  the save a package, without spaces, and showed a box instead of the icon.
- The amount of the Stripe element is fixed or comes from a form field. An empty or invalid amount,
  one outside what Stripe charges, or one a list or radio field does not offer refuses the form
  before any record or notification. A Begin Submit piece runs first and can compute the amount.
  The value of a free field comes from the browser, and the visitor can change it.
- Stripe amounts follow the currency: zero-decimal currencies such as JPY are no longer charged
  100 times too much, and three-decimal ones such as KWD are correct. A selected `bfPaymentSelect`
  list value must be one the list offers.
- QuickMode refuses to save a Stripe element set to an amount field without an existing field.
- A QuickMode save that gets an unexpected answer now says so and can be run again.
- The Stripe element groups its settings in sections, masks its keys on screen, shows the test or
  production mode from the secret key, and warns about a local webhook address. The keys are still
  sent to the administrator's browser with the form data.
- The payment methods of the Stripe element can be left to Stripe, which new elements do, or
  selected by hand. Existing elements keep their selection. EUR is the default currency of new
  elements, and of an element whose currency is empty.
- *iDEAL | Wero* is now called *Wero*.
- The administration uses the browser tooltips of the `title` attributes, as the Joomla article
  list does, instead of Bootstrap tooltips. They do not open with the keyboard or on touch screens.
- QuickMode no longer fails with `getElementTypeOption is not a function` when a browser kept an
  old copy of a script after an update: the script is now loaded with its version.

## 6.1.0-RC10

- The Stripe payment page opens again: Stripe now refuses `payment_method_types` when a
  Checkout Session is created. The payment methods now come from the Stripe Dashboard, so
  card must be enabled there, and other methods enabled there can appear on the payment
  page. A Klarna or iDEAL | Wero option left off in the element excludes that method; one
  that is on is offered only if it is enabled in the Dashboard.
- The *User Confirmation* tab of the form options shows the report, attachment, email body,
  HTML sending and custom template settings again. They were missing, and each save reset
  them, including the custom template. A template lost this way cannot be restored: the
  form shows *default* until it is entered again.
- The *Email body* setting always shows the body: a preview of the default email, or the
  template editor with the list of field variables. The *Report* setting applies to the
  attachment; the fields of the default email follow *Attach Submission Data* of the
  *Administrator Notification* tab.
- The *Edit* links of the QuickMode page and section texts open with their content and can
  save it again, translations included. Their modal stayed empty.
- On the classic form screen, saving keeps the code edited in scripts and pieces. It was
  lost because the editor content was not posted.
- Deleting a package moves to the Configuration screen and is now called *Dissolve package*:
  choose a package, see what is attached to it, then confirm. Its items are kept and
  detached. Importing the package again later creates duplicates, and the screen warns
  about it. *Create Package* becomes *Create or edit package*, and its download button
  *Export package*.
- A form chooses its package from the list of existing packages. A package is created with
  *Create or edit package*; a value not in the list is kept. Scripts and pieces keep their
  free text field.
- The QuickMode tree and panel share the width on narrow screens and the margins shrink
  first. A long form title no longer widens the page.
- *Keep panel docked* and *Scroll element list* of the QuickMode tree settings work again,
  and the browser remembers them.
- The QuickMode *Options* tab shows on the form root only, and every label of the form
  options has a tooltip.
- The field types are grouped by family in a keyboard accessible menu, with one Lucide icon
  and one color per type, in the menu and in the tree, replacing the emoji.
- *Viewed* and *Exported* start hidden in the records list and can be shown again from the
  *Columns* menu. A column choice already saved on this screen is reset once.
- The back to top button shows its arrow again.
- The French interface says « paquet » instead of « package ».

Known issue: the *Administrator Notification* tab still has no email body setting, and each
save resets its email type and custom template.

## 6.1.0-RC09

- The Stripe element can also offer Klarna and iDEAL | Wero on the Stripe Checkout page
  (options *Also offer Klarna* and *Also offer iDEAL | Wero*). The payment method must be
  enabled in the Stripe Dashboard and accept the form currency (EUR for iDEAL | Wero).
- Stripe payments can be confirmed by a signed webhook, so a successful payment is recorded,
  and its notifications sent, even when the customer does not come back to the site. Add an
  endpoint in the Stripe Dashboard with the URL the Stripe element shows, listening to
  `checkout.session.completed` and `checkout.session.async_payment_succeeded`, and enter its
  signing secret in the element. The return to the site and the webhook record a payment
  once, whichever comes first. The return now also checks that the Stripe session belongs to
  the record it confirms, and tells a customer whose payment is still being processed that it
  is pending instead of declined. A payment opened without a signing secret is logged as a
  warning. The README describes the setup.
- The component writes its warnings and errors to `administrator/logs/com_breezingformsng.php`;
  they were dropped before.
- A Stripe error while opening the payment page (payment method not enabled, currency not
  accepted, invalid key) is logged and shown as a payment error instead of a fatal error.
- The Sofort element is removed: Klarna discontinued Sofort in 2024. A Sofort element still
  stored in a form is no longer rendered; QuickMode drops it when the form is opened and
  warns about it, and saving the form removes it. Use the Klarna option of the Stripe
  element instead. The installer removes the Sofort files on update. A form whose only
  payment element was Sofort is not accepted on the site until another payment is set up:
  it shows a message and the refusal is logged, instead of taking submissions unpaid.
  Saving such a form in QuickMode without adding Stripe or PayPal unpublishes it, so it
  does not become free once Sofort is gone; QuickMode warns about it when it opens.
- The "code framework" buttons of the QuickMode custom init, validation and action scripts
  work again; they did nothing since the editor script became a module (RC06). Saving an
  element no longer also runs the page save, and forms set to show errors as alerts show
  them as alerts on the next-page check too.
- No more `ob_end_clean(): Failed to delete buffer` notice after a form submission on sites
  that display notices.
- A form page reached without an active menu item no longer ends in a fatal error.
- The update no longer risks saving a QuickMode form empty when renaming its old
  `com_breezingforms` links fails: the stored form is left as it is.

## 6.1.0-RC08

- Record dates are shown in the time zone of the user, which the screens now name: the
  records list states it above the table, and the record screen gives it with the UTC
  offset next to the submission date. The submission date was shown in UTC without saying
  so. The list and the record screen use the same date format (`DATE_FORMAT_LC6`).
- The modification date of a record is stored in UTC, like its submission date, instead of
  the site time zone. The update converts the stored values once.
- The record screen shows its position in the filtered list between Previous and Next
  (« 304 sur 412 »), ends the page title with the record title instead of its id, and no
  longer repeats the id and the submission date.
- The `ff_makeFormUrl` standard library piece no longer builds the Joomla 1.x
  `administrator/index2.php?act=run` URL when a form runs in backend or preview mode: it
  always builds the site URL. `ff_makeSelfUrl`, `ff_redirectForm` and `ff_redirectSelf`
  use it. The piece is updated on existing sites when the component is updated (#117).
- The installer now removes, on install and on update, the files and folders earlier
  releases shipped and later ones dropped (78 files and 10 folders: classes replaced
  by the native MVC, removed JavaScript libraries, Ladda among them, mobile and
  Internet Explorer theme files). Joomla leaves such files in place because their
  parent folder is still in the manifest. The six classic skins formerly under
  `themes/quickmode` are left alone, in case a site customised them.
- Fixed QuickMode forms in one-page mode: two generated scripts held literal `\t`
  sequences, a JavaScript syntax error that disabled the whole page script, so the
  form could not be submitted.
- Replaced the vendored Ladda 1.0.6 library (submit button spinner of the one-page
  mode) with the Bootstrap 5 spinner, driven by a small first-party script.
- The CSV import of records has its own screen with a native Joomla form
  (`forms/recordsimport.xml`): the target form is now chosen on the screen and is
  required, and the form is validated before the file is read. The import code moves
  from `RecordModel` to the `RecordImporter` service. Importing requires the
  `core.create` permission.
- The record exports (PDF, CSV, Excel, XML) move out of `RecordsController`: the
  queries go to `RecordsExportModel` and the file formats to the `RecordExporter`
  service. The controller only checks the token, asks for the export and sends it.
  The exported files are unchanged.
- The integrator rules list now uses a native Joomla list model
  (`IntegratorRulesModel`): search tools with search and status filters, native
  pagination and column sorting.
- The pieces screen now uses the native Joomla MVC, on the same shared bases as the
  scripts screen (`PackageListModel`, `PackageItemModel`, `PackageListController`,
  `PackageItemController`): native list, edit form defined in `forms/piece.xml`, and
  the test page as a layout of the view. The PHP test runner moves unchanged to the
  `PieceTestRunner` service and still requires the code permission. The legacy
  `PieceManager`, `PackageModel`, the HTML renderer and the list script are removed.
- The scripts screen now uses the native Joomla MVC: a list with the search tools and
  pagination, native publish, unpublish and delete actions, and an edit form defined
  in `forms/script.xml` with the native code editor field, Save and Save & Close. The
  script test page is a layout of the same view. The legacy `ScriptManager` service,
  the HTML renderer and the list script are removed.
- The forms list now uses the native Joomla list model: search tools (search,
  package and status filters), native pagination and column sorting, with the list
  state kept in the session instead of the links.
- The administration menus screen now uses the native Joomla MVC: a list with the
  search tools (search, package and status filters) and pagination, native publish,
  unpublish, reorder and delete actions, and an edit form defined in `forms/menu.xml`
  with Save and Save & Close. Creating an item requires the new `core.create`
  permission.
- A record is now edited through the native Joomla form MVC: `RecordController`
  (`FormController`), a `Record` view and a form whose fields are built from the
  elements of the record's form. The toolbar has the native Save and Save & Close
  buttons, and editing requires the new `core.edit` permission.
- Fixed the About screen's database/menu audit tables using a non-existent
  `JGRID_HEADING_ACTIONS` language key (rendered untranslated); added a real
  `COM_BREEZINGFORMSNG_ABOUT_AUDIT_ACTIONS` key, and added an "Edit menu item" link
  on the site-menu-issues table pointing to Joomla's native menu item editor.
- Fixed the record edit screen's "unsaved changes" detector being wrongly triggered
  by a PDF/CSV/XLSX/XML export: `Joomla.submitbutton()` mutates the hidden `task`
  field before every submit, and an export leaves the edit page open (file download,
  no navigation), so the mutated value never reset.
- Added quick Vu/Exporté/Archivé toggles next to the record title on the record edit
  screen, kept in sync with the existing read-only Oui/Non detail table, and made the
  detail table's sections collapsible.
- Added tooltips to the QuickMode "New page/section/element" buttons, and fixed the
  tree-toolbar settings dropdown's misaligned switch labels.
- Replaced the generic "Élément enregistré." message on form save with a specific
  "Formulaire « X » enregistré." message.
- Relabelled the records edit screen's Cancel button to "Fermer" (native
  `JTOOLBAR_CLOSE`), matching the Forms edit screen.
- Enabled the native column-visibility filter on the Forms list, matching
  Records/Scripts/Pieces.
- Added a result message after CSV record import (previously silent on success or
  failure).
- Fixed the "Import/export" tab of the component's Options screen (the closest
  equivalent to the legacy `act=configuration` page's "Créer Package" button) silently
  falling back to a plain empty text box instead of its actual button: `config.xml`'s
  `type="packagetransfer"` didn't match the field's real class name
  (`PackageTransferField`) once Joomla applies its own class-name derivation, so the
  custom field type was never found.
- Fixed the tree always showing an element's/the form's label one edit behind: it was
  only synced from the field on populate (i.e. the *next* time the node opens), never
  on save. The underlying saved data was always correct - only the tree's own label
  rendering lagged a cycle.
- Fixed the "unsaved changes" badge appearing on every tree click: `open_node`/
  `close_node` handlers wrote a node's `.state` unconditionally on any tree replay
  (not just the very first render), permanently adding a `"state":"open"` key to nodes
  that never had one. Now only write on a genuine open/close transition.
- Fixed the form's title/name/description/notification fields no longer being synced
  onto the root node at load, and a renamed element becoming unreachable in the tree
  until an unrelated refresh - both regressions from the `ready.jstree` selection fix
  above, since the metadata sync and the auto-save-on-switch path each depended on
  `selectedTreeElement` timing that no longer held.
- Fixed the QuickMode properties panel/save button silently doing nothing right after
  a form loads: the initial root selection raced jsTree 3's asynchronous data parsing
  (`core.worker`), so `select_node()` could run before the node existed in jsTree's
  model. The same race affected creating a page/section/element and changing an
  element's type. Selection now always waits for `ready.jstree`/`refresh.jstree`.
- Fixed a renamed element becoming unselectable and unreachable by its own context-menu
  actions: the tree model's id index wasn't rebuilt after a rename, so subsequent
  lookups by the new id returned nothing.
- Extended the `option=com_breezingforms` migration to also run on a fresh
  install/discover_install (not just update), and to cover `facileforms_forms`'s
  script/piece code columns and `facileforms_scripts`/`facileforms_pieces.code`,
  alongside the columns already covered.
- Fixed a false-positive "unsaved changes" badge appearing on every QuickMode page
  load, caused by jsTree 3 replaying `open_node`/`close_node` events for already-open
  nodes during its own initial render, which the handlers mistook for real edits.
- Fixed a second, unrelated cause of the same false-positive badge: the Options tab's
  `jf_piece1code`-`jf_piece4code` CodeMirror editors aren't registered yet on the very
  first check after load, so their arrival one poll cycle later was read as an edit.
  The baseline now waits for several consecutive stable polls (~3s of genuine quiet,
  capped at ~10s) before freezing, since two absences in a row can also look "stable".
- Extended the `option=com_breezingforms` migration below to also cover
  `facileforms_forms.template_areas` and `template_code_processed` - the compiled
  columns site-side payment callbacks actually read from at runtime, distinct from the
  `template_code` column the admin editor displays.
- Added a one-time update migration rewriting `option=com_breezingforms` (the pre-NG
  component name, no longer installed) to `option=com_breezingformsng` wherever it's
  hardcoded in free-text form/element content — Stripe/PayPal/Sofortueberweisung
  "thank you" redirect URLs, custom init/action/validation code — so those stored links
  don't 404 with "Composant introuvable" after migrating to NG.
- Fixed a pre-existing crash on every QuickMode admin page load
  (`jQuery(...).offset() is undefined`) caused by a stale `#menutab .t` selector left
  over from an earlier Bootstrap 5 tabs migration; falls back to the window height
  instead of throwing.
- Fixed the QuickMode properties panel (Type/Libellé/Nom, section, page and form
  fields) staying empty after selecting any tree node, a regression from the jsTree 3
  migration below: several save/populate functions read the selected node's id via a
  jQuery DOM call that silently failed against the new node representation.
- Migrated the admin QuickMode tree editor from jsTree 0.9.8 (2010, bundling its own
  private jQuery 1.3.2 clone that overwrote the shared `window.JQuery`/`$` globals) to
  jsTree 3.3.17, vendored under `libraries/jquery/jstree3/`. The persisted form JSON
  format and `QuickmodeTreeModel` are unchanged; only the tree widget and its
  init/context-menu/event wiring were ported. The obsolete `jtree/` bundle is removed
  from the package and cleaned up on update.
- Fixed a crash when selecting a node in the admin QuickMode tree editor
  (`TypeError: ...getNodeClass(...).split is not a function`), caused by a DOM element
  being mistaken for a tree-model node because both expose an `.attributes` property.
  Also removed a dead 2015-era Firefox workaround using two more removed jQuery APIs
  (`.live()`, `.browser`) and replaced the remaining admin `.size()` calls with `.length`.
- Fixed jQuery 3 compatibility bugs on the frontend: the form iframe autoheight script
  referenced an undefined `JQuery` alias (`ReferenceError: JQuery is not defined`), and
  QuickMode's toggle fields, Flash upload queue counting, AJAX multi-page submission, and
  progress bar all called the jQuery `.size()` method removed in jQuery 3
  (`TypeError: JQuery(...).size is not a function`), now replaced with `.length`.
- Fixed the utf8mb4 install/update conversion failing on legacy MyISAM tables or InnoDB
  tables using the old Antelope row format (`Specified key was too long; max key length
  is 1000 bytes`). Affected tables are now moved to `InnoDB`/`DYNAMIC` and their oversized
  single-column indexes shrunk to a 191-character prefix before the charset conversion runs.
- Fixed a fatal error (`Call to undefined method BreezingFormsNGComponent::getContainer()`)
  when rendering a form from a module or a menu item, present in the 6.1.0-RC05 package.
  The frontend bootstrap now resolves the `EngineDispatcher` through the component's own
  `getEngineDispatcher()` accessor instead of a non-existent `getContainer()` call (#77).
- Added XLSX export for records, with review-round fixes to the exported columns and formatting.
- Fixed several record-management UI regressions: header navigation, record detail actions,
  compact record detail header, record ID badge contrast, form title link in metadata, and
  added system field tooltips.
- Fixed bundled TCPDF core fonts not loading (fonts are now configured before the Composer
  autoload runs), which affected generated PDF output.
- Fixed component provider bootstrapping and namespace registration in the packaged build.
- Fixed the missing web asset registry declaration on the Scripts and Pieces admin views,
  and isolated the piece test runner's global processor context so it no longer leaks
  between test executions.
- Fixed QuickMode dirty-state initialization and switched it to Joomla's native editor API.
- Normalized the rendering page context type and added tooltips to record table columns.
- Ported the audit repair controls and completed audit coverage for BreezingFormsNG.
- Performance audit fixes: batched subrecord loading to remove an N+1 query pattern in
  the records list/export, narrowed the Forms list query to its used columns, and
  optimized the Package model and Records controller listing queries.

- Switched BreezingForms integration from legacy `com_contentbuilder` to `com_contentbuilderng`.
- Updated BF site/admin flows to use ContentBuilder NG services for permissions, form resolution, record sync, article creation, and redirects.
- Changed BF direct access behavior so linked CBNG views are validated against the new CBNG ACL flow.
- Added the optional BFCompat system plugin for historical `BFFactory`, `BFIntegrate`, `BFJoomlaConfig`,
  `BFPDF`, `BFRequest`, and `BFText` APIs used by third-party plugins and PHP stored in the database.
- Kept the required BFQuickMode public classes in the component while switching the component runtime to
  its Joomla 6 namespaced renderers and integration service.
- Removed the obsolete sysbreezingforms plugin; its disabled licence check and Joomla 3 menu-markup cleanup
  are no longer used by the Joomla 6 component manifest.
- Switched administrator controllers to the application supplied by Joomla's MVC base controller and moved
  database-backed administrator models to `BaseDatabaseModel`/`getDatabase()`.
- Switched database models to Joomla's injected current-user and event-dispatcher services.
- Removed the unused legacy Dropbox configuration containing obsolete embedded application credentials.
- Migrated reCAPTCHA, Dropbox, Mailchimp, and Salesforce HTTP clients from the Joomla CMS compatibility
  wrapper to the native `Joomla\\Http` package required by Joomla 6.
- Removed direct mutation of Joomla document internals, replaced legacy Bootstrap/calendar file loading with
  Joomla 6 web assets, and migrated remaining local runtime script tags to WebAssetManager.
- Removed all embedded jQuery copies and migrated frontend and administrator rendering to Joomla 6's native
  jQuery web asset.
- Migrated script and piece source submissions from direct request-body and superglobal access to Joomla 6
  Input with explicit raw filtering, and standardized their state-changing actions on POST CSRF validation.
- Removed the unused legacy form-route helper and renamed the shared script/piece list model to its Joomla 6
  package-model role.
- Migrated record exports from direct PHP response headers and global database lookup to the Joomla application
  response API and the database connection supplied by the MVC model.
- Migrated QuickMode chunk-save status and completion responses from direct PHP response handling to Joomla's
  application response API.
- Added the sortable form ID as the first data column in the Joomla administrator forms list.
- Unified the records-list modified-date heading with the forms-list wording.
- Migrated CAPTCHA status, Stripe checkout redirects, and paid-file download headers to Joomla 6's application
  response API while retaining streamed file delivery.
- Normalized integer and nullable relation fields when copying forms so strict Joomla 6 database configurations
  no longer receive empty strings for integer columns.
- Aligned every sortable Forms, Records, Scripts, and Pieces heading with ContentBuilder NG by using Joomla 6
  SearchTools icons and accessible list-view sorting behavior; non-sortable columns remain icon-free.
- Removed the Pieces “show internal functions” control, session state, and underscore-prefix query filtering;
  all pieces are now listed consistently.
- Added contextual Integrator help and Joomla 6 SearchTools sorting for every sortable rule-list column.
- Restored the compact, panelled presentation of the advanced form settings while retaining the Joomla 6
  tabs and form controls.
- Removed the obsolete standalone QuickMode mobile document reconstruction and routed its assets through the
  Joomla 6 document normally; migrated adjacent request and session state access to Joomla Input and Session.
- Migrated PayPal request data, Flash uploader files, and the remaining VirtueMart bridge state from PHP
  superglobals to Joomla Input and Session; made Flash upload-size validation an encapsulated service method.
- Replaced PayPal IPN's direct cURL/socket transport and disabled TLS verification with an injectable
  `Joomla\\Http` client using the platform's secure transport configuration; the PayPal waiting callback no
  longer emits a second standalone XHTML document inside Joomla's response.
- Routed regular form upload metadata through Joomla's files Input, removing the final runtime dependency on
  PHP's request, session, GET, POST, and FILES superglobals.
- Converted the administrator Script manager from a static Factory-based utility to an instance receiving the
  Joomla application and database from its MVC controller.
- Converted the administrator Piece manager and its interactive test paths to injected Joomla application and
  database services instead of static Factory lookups.
- Switched the Scripts and Pieces views to the models assigned by Joomla's MVC dispatcher and removed their
  unused legacy `table.columns` behavior asset.
- Replaced direct process termination in payment and Flash-upload callbacks with Joomla application response
  closure, keeping response lifecycle control inside the CMS.
- Replaced the remaining assembled SQL in QuickMode ContentBuilder synchronization and Piece test execution
  with Joomla database queries and bound parameters.
- Moved QuickMode's chunked-save workspace from public media into Joomla's temporary directory, restricted
  chunk identifiers to alphanumeric input, and stopped suppressing filesystem and Base64 errors.
- Removed the administrator display controller's final direct container lookup; its temporary legacy runtime
  globals now receive the database owned by the active Joomla MVC model.
- Injected the form engine's database into the frontend Integrator runtime instead of resolving Joomla's global
  container during submission export.
- Added a native About MVC model for extension discovery and database access, removing SQL from the view and
  the final About controller/view container lookups.
- Routed Piece test-runner validation and failure messages through Joomla translations in all eight supported
  administrator languages.
- Fixed the invalid menu-title translation class and migrated Menu model CRUD, ordering, publication, and copy
  queries to Joomla bound parameters and `whereIn()` lists.
- Routed administrator AJAX payloads through Joomla's application body and headers with exception-safe JSON
  encoding instead of writing encoded strings directly to PHP output.
- Replaced direct writes to Joomla's `#__menu` nested-set columns with the native `com_menus` MenuTable API,
  including transactional synchronization and Joomla-managed tree placement.
- Kept administrator session state in the Forms view instead of coupling its database model to the application.
- Decoupled the Scripts and Pieces list models from the global application by passing Joomla input and session
  services from their views.
- Replaced manual associated-article and asset deletion with Joomla's native Content article model, and removed
  the Records model's global application dependency.
- Injected Joomla's configured temporary path into the database audit service instead of reading the global
  application from the service layer.
- Replaced the bundled Securimage CAPTCHA library with the Composer-managed bgli100/securimage 4.0.2 fork,
  moved its PHP runtime out of public media, and added Google reCAPTCHA to About.
