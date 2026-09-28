# Changelog

## Unreleased

- Fixed a false-positive "unsaved changes" badge appearing on every QuickMode page
  load, caused by jsTree 3 replaying `open_node`/`close_node` events for already-open
  nodes during its own initial render, which the handlers mistook for real edits.
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
