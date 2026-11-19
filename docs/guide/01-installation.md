# Installing and updating BreezingForms NG

BreezingForms NG is a form builder for Joomla 6. This guide shows how to install it on a site for the first time, how to update it, and how to remove it. It also explains what the installer adds to your site and what to check afterwards. If you come from Crosstec BreezingForms, read the section on upgrading from the old component too.

## Before you start

You need:

- Joomla 6.x. The installer stops on anything older than Joomla 6.0.
- PHP 8.3 or later. The installer stops on an older PHP version.
- MySQL 8.0.13 or later, or MariaDB 10.4 or later.
- A Super User account on the Joomla administrator.
- Files that Joomla can write to. On an update, the installer checks that the files of the existing BreezingForms NG installation are writable. If some are not, it stops before copying anything.

Back up first, especially before an update or before upgrading from Crosstec BreezingForms:

1. Make a full backup of the database. It holds your forms, your submitted records and the component settings.
2. Make a backup of the site files.
3. For an upgrade from Crosstec BreezingForms, work on a copy of the site first.

Where to download:

1. Open the [Releases page](https://github.com/vcmb-cyclo/com_breezingformsng/releases) on GitHub.
2. Download the ZIP of the release you want. The file name looks like `com_breezingformsng-<version>.zip`, for example `com_breezingformsng-6.1.0-RC22.zip`.
3. The release notes show the SHA-256 checksum of the ZIP. If you want to check the download, compare it with the checksum of your file.
4. Versions ending in `-RC<number>` are release candidates. GitHub marks them as pre-releases. The project says that only GitHub Releases are meant for production. Use a stable version on a live site when one is available.

Do not unzip the file. Joomla installs the ZIP as it is.

## Install for the first time

1. Log in to the Joomla administrator.
2. Go to **System**, then **Install**, then **Extensions**.
3. Upload the `com_breezingformsng-<version>.zip` file (drag it into the upload area, or browse for it).
4. Wait for the installation to end. It can take a little while.
5. Read the messages shown at the end (see "Check that it worked").

What the installer adds:

- The component **BreezingForms NG** (`com_breezingformsng`), with its database tables.
- The module **BreezingForms NG** (`mod_breezingforms`). It shows a form in a template position.
- The plugin **Content - BreezingForms NG**. It lets you put `{BreezingForms:FormName}` in an article.
- The plugin **Privacy - BreezingForms NG**. It handles Joomla privacy export and removal requests for submissions.
- The plugin **Web Services - BreezingForms NG**. It adds the MCP endpoint to the Joomla API. The endpoint exists only when MCP is enabled in the component options.
- The plugin **BreezingForms NG - Compatibility** (system plugin `bfcompat`). See "Compatibility plugin" below.
- The standard library of scripts and pieces, imported by the installer.

The installer enables each of these plugins when it is installed for the first time. On a later update it keeps the enabled or disabled state you had.

## Take a first look

1. Go to **Components**, then **BreezingForms NG**.
2. The component menu has these entries: **Records**, **Forms**, **Scripts (JS)**, **Pieces (PHP)**, **Integrator**, **Tools** and **About**.
3. Open **Tools**. The screen is called "BreezingForms Tools". It has these parts:
   - **Maintenance** ("Check and clean up BreezingForms NG"). Tiles: **Audit** (checks the tables and the installation), **Statistics** (number of rows and size of each table), **Trash** (restore or permanently delete) and **Log** ("Latest installation log of BreezingForms NG").
   - **Packages** ("Transfer forms, scripts, pieces and menus from one site to another"). Tiles: **Install**, **Create / edit** and **Dissolve**. Dissolve removes a package and is irreversible.
   - **Sample data** ("Ready-made forms to start from"). See below.
   - An MCP status box, with the title of the MCP option group. It shows whether MCP is enabled or disabled.

### Install sample forms

The Sample data box offers ready-made forms in two groups. The group **Business templates** has: Professional contact form, Event registration, Dynamic quote request and Satisfaction survey. The group **Technical examples** has: Field widgets.

1. Open **Components**, then **BreezingForms NG**, then **Tools**.
2. In **Sample data**, tick the forms you want.
3. Click **Install**. Confirm the question that appears.
4. Each installed form gets an **Installed** badge and a **Manage** link.

A sample form is also added to the site menu, under "Tests". Installing a sample form needs administrator rights. If a sample is already installed, it is left as it is.

To build your own form, continue with the [first form guide](02-first-form.md).

## Update from an earlier BreezingForms NG version

### With the Joomla update manager

The component declares an update server, "BreezingForms-ng". Joomla reads the file `com_breezingformsng_update.xml` from the project repository. It lists the current version, the download address of the ZIP and its SHA-256 checksum. The file is for Joomla 6 and PHP 8.3 or later. Release candidates carry the `rc` tag in this file.

1. Make your backups.
2. Go to **System**, then **Update**, then **Extensions**.
3. Click **Check for Updates** if the list is empty.
4. Tick **BreezingForms NG** and click **Update**.

### With a ZIP file

1. Make your backups.
2. Download the new ZIP from the Releases page.
3. Go to **System**, then **Install**, then **Extensions** and upload it. You do not uninstall the old version first. The manifest uses the "upgrade" method, so Joomla installs over the existing files.

During an update the installer also updates the database structure, refreshes the standard library, and updates the plugins and the module. Then it shows the same summary as for a first installation.

## Upgrade from Crosstec BreezingForms

BreezingForms NG installs over a site that runs the old `com_breezingforms`. The project documents this as follows:

- Forms, records, scripts and pieces stay in the same `#__facileforms_*` tables.
- The installer renames the component, migrates menu links, assets and configuration, and rewrites `option=com_breezingforms` in stored URLs and code.
- It removes the old component rows and folders after the new one is in place.
- The compatibility plugin keeps the old `BF*` classes available to PHP code stored in your forms.
- Only Joomla 6 is supported. Upgrade Joomla itself first if your site is older.

Back up the database and work on a copy of the site. After the upgrade, retest captchas, uploads, payments and exports.

Old Easy Mode and Classic Mode forms can be copied to QuickMode with **Convert to QuickMode** in the **Forms** list. The converter makes an unpublished copy and leaves the original alone. Some elements cannot be converted and need manual work. The [README](../../README.md#upgrading-from-breezingforms-crosstec) lists what is supported.

[`MIGRATION.md`](../../MIGRATION.md) is a technical follow-up document for developers (written in French). You do not need it to install the component. There are also two public pages in [`docs/website`](../website/) about migrating to Joomla 6, in English and in French.

If the installer finds PDF templates, fonts or stored code that use the former PDF library, it shows a warning with the affected items. Adapt them as the message says.

## Compatibility plugin

The system plugin **BreezingForms NG - Compatibility** "provides the legacy BF* public classes required by third-party plugins and PHP code stored in forms". Keep it enabled if your forms or third-party extensions still call the old `BF*` classes. This is all the repository documents about it.

## Check that it worked

1. At the end of the installation, Joomla shows messages from the installer. Look for a line that starts with "[OK] BreezingForms" and ends with "finished successfully". It shows the old version, an arrow, the new version, and the duration. At the start, an "[INFO]" line shows the action, the versions, and your Joomla, PHP and database versions.
2. The installer also announces the plugins it installed or updated, and may report changes to the standard library or the database character set (utf8mb4).
3. Go to **System**, then **Manage**, then **Extensions** and search for "BreezingForms". You should see the component, the module and the four plugins, all enabled.
4. Go to **Components**, then **BreezingForms NG**, then **Tools**. Click the **Audit** tile to check the tables and the installation. Open the **Log** tile to read the latest installation log.
5. Open **Forms** and check that your forms (if any) are still listed.

The installation log is also written to `administrator/logs/breezingforms_install2.log`.

## Uninstall

1. Go to **System**, then **Manage**, then **Extensions**.
2. Search for "BreezingForms NG", select the component and click **Uninstall**.

What the uninstaller does, according to `script.php`:

- It uninstalls the four bundled plugins (system `bfcompat`, content, privacy, web services) and the `mod_breezingforms` site module.
- It removes the component menu entries in the administrator, the action log registration and the content type registration.
- It deletes the old configuration file `media/breezingforms/facileforms.config.php` if it exists, and the component log file `administrator/logs/com_breezingformsng.log`.

The manifest has no uninstall SQL and `script.php` has no step that drops the database tables. So the installer code does not delete your forms and records. But we have not tested an uninstall to see what remains on a live site. Do not rely on this. Make a backup before you uninstall, and export what you need first.

## Troubleshooting

**"PHP x is too old. Requires PHP y or later."** The server runs a PHP version below 8.3. Ask your host to switch to PHP 8.3 or later.

**"Joomla x is too old. Requires Joomla y or later."** The site runs a Joomla version below 6.0. Update Joomla first.

**"BreezingForms NG update cannot copy files because some installed files or directories are not writable by Joomla."** The message lists up to ten example paths. Fix the owner and the permissions of those files so the web server user can write to them, then run the update again. Nothing was copied.

**Installation finished with an error ("finished with critical issue(s)").** The installer reports the first problems in the message. Joomla then shows the installation as failed. Read the installation log (Tools, **Log** tile, or `administrator/logs/breezingforms_install2.log`) and restore your backup if the site is not working.

**"Failed to install BreezingForms plugin: ..." or "Failed to install BreezingForms NG module."** One of the bundled extensions did not install. Check the log, and the permissions of the `plugins` and `modules` folders. Then install the ZIP again.

**Stale temporary folders after an interrupted install.** The **Audit** reports "Stale installer temp folders": temporary folders left in the Joomla tmp folder by an interrupted install or update. The audit says they can be safely removed, and offers a **Trash selected** button.

**Warnings about PDF templates or BFPDF code.** These appear after an update when custom PDF templates or stored code still use the former PDF library. Follow the text of each warning.

For anything else, open an issue on the [GitHub repository](https://github.com/vcmb-cyclo/com_breezingformsng). Report security problems privately, as described in [SECURITY.md](../../SECURITY.md).

## See also

- [README](../../README.md): features, requirements and the upgrade summary.
- [CHANGELOG](../../CHANGELOG.md): what changed in each version.
- [SECURITY.md](../../SECURITY.md): supported versions and how to report a vulnerability.
- [CONTRIBUTING.md](../../CONTRIBUTING.md): for people who want to help with the project.
- [MIGRATION.md](../../MIGRATION.md): developer notes (French).
- [Releasing](../RELEASING.md): how a release is published (ZIP, checksum, update stream).
- Next guides: [02 - Your first form](02-first-form.md), [03 - The QuickMode editor](03-quickmode-editor.md), [04 - Field types](04-field-types.md).
