<!--
Thank you for contributing. Keep the change focused: one fix or one feature per pull request.
Do not include passwords, tokens, personal data, real submissions or payment details.
A security vulnerability must NOT be described here: follow SECURITY.md.
Open the pull request from a branch, not from `main`.
-->

## What and why

<!-- What does this change, and why? Link the issue: Fixes #123 -->

## How it was checked

<!-- Tick what you ran, and give the result. Say "not run" with the reason for anything you skipped. -->

- [ ] `php -l` on every changed PHP file
- [ ] `vendor/bin/phpunit` (state the PHP version used)
- [ ] `vendor/bin/phpstan analyse` (level 8, no new baseline entry)
- [ ] `vendor/bin/phpcs --standard=phpcs.xml.dist` on the changed PHP files
- [ ] `npm run test:js` and `npm run lint` when JavaScript or CSS changed
- [ ] Tests added or updated for the observable behaviour (not only a search in the source text)
- [ ] Tried on a disposable Joomla 6 site (`scripts/joomla-e2e.sh` for the browser journeys), for screens, installer or database changes

## Compatibility and safety

- [ ] Existing forms, stored records, packages and URLs keep working, or the breaking change is explained below
- [ ] Server-side permission checks (ACL) and CSRF protection are in place for any state-changing action
- [ ] Input, uploads, callbacks and imported data are validated; output is escaped for its context
- [ ] No secret or submitted data in logs, fixtures or messages
- [ ] Installer or schema changes are idempotent, with a way back; no destructive change without approval
- [ ] The Piranha easter egg and the XLSX export are untouched

## Translations and release notes

- [ ] New or changed interface text uses translation keys, in all eight languages (en-GB, fr-FR, de-DE, it-IT, es-ES, hu-HU, nl-NL, tr-TR); `python3 scripts/check-translation-keys.py` and `python3 scripts/check-translations.py` pass
- [ ] A user-visible change has an entry under the next version in `CHANGELOG.md` and `com_breezingformsng_changelog.xml`, describing what the user sees

## Not checked / risks

<!-- Anything you could not verify, and what could go wrong. -->
