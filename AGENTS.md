# AGENTS.md

## Scope and sources

- Target Joomla 6.2 and PHP 8.4 in production; code must also be compatible with PHP 8.5. Use native, non-deprecated APIs and modern conventions. No compatibility code for older versions: no fallbacks, polyfills, shims or runtime version checks.
- Before editing, read `docs/best-practices/README.md` and only the relevant thematic guides it lists. Verify API signatures against the installed CMS source or versioned official documentation; do not assume external documentation matches the installed patch release.
- Follow higher-priority instructions when they conflict with this file, and flag repository-level conflicts.

## Changes

- Inspect the affected code, extension manifest, project conventions, tests and configured Joomla/PHP versions. The baseline is Joomla 6.2 / PHP 8.4, with PHP 8.5 compatibility required.
- Identify the task boundaries, existing design, persistence effects, security threats and test plan. Change only what the task requires; avoid unrelated formatting, refactoring, dependency upgrades and translation changes.
- Preserve data, forms, URLs, ACL behavior, translations, backward-facing interfaces and migration paths unless the task explicitly changes them.
- Use namespaced MVC, the extension service provider / DI and official APIs for new Joomla code. Follow native Joomla 6 administrator patterns; keep custom CSS and JavaScript minimal, separate MVC responsibilities, and preserve a non-AJAX fallback when practical.
- Validate authorization server-side; protect state-changing web actions against CSRF; validate input; escape output by context; bind untrusted SQL values and allowlist SQL identifiers. Never leak secrets or log sensitive submitted form data.
- Do not run destructive schema changes, data deletion, mass renames or installer cleanup without explicit authorization and a migration/rollback plan. Keep installers idempotent and handle existing installations, upgrades and reinstalls conservatively.

## Translations

- Route user-facing strings through translation keys. Update `en-GB`, `fr-FR`, `de-DE`, `it-IT`, `es-ES`, `hu-HU`, `nl-NL` and `tr-TR` together for every translation change. Keep wording aligned, with correct spelling, grammar, typography and accents in each language. Preserve keys and placeholders.
- In translation conflicts, follow explicit instructions from Gilles and this file before the translation skill, Joomla conventions and upstream wording.

## Verification and report

- Add or update relevant tests. Run `php -l` on changed PHP files and configured PHPCS, PHPStan, PHPUnit and functional checks when installed and feasible.
- Run all tests without the Joomla 6.2 compatibility plugin and without the BreezingForms compatibility plugin for old classes.
- Put every locally built release ZIP directly in the repository's `build/` directory.
- Use the local Docker environment `breezingforms-61` for BF tests; its Joomla container is named `bf61_joomla`.
- After copying files into `bf61_joomla`, restore the installed files to `www-data:www-data` with normal file permissions so Joomla updates remain writable.
- For installer/DB changes, verify fresh install, upgrade from the previous schema and rerun idempotence against disposable database copies. For view/form changes, verify relevant frontend and administrator paths, ACL deny/allow, CSRF, translations and dark/mobile layout.
- Report changed files, functional and migration impact, security considerations, checks run with pass/fail/not-run results, and remaining risks. Never claim a check passed unless executed.

## Agent workflow

- By default, delegate independent, non-overlapping work to as many subagents as the task and available capacity warrant. Give them read-only investigations or ownership of disjoint files; keep ordered or cross-file decisions with one agent.
- Integrate subagent results and verify the complete change. If a commit is requested, make one coherent commit; do not let subagents commit independently.

## Output

- Complete the requested task, then stop. Return final code directly when coding is requested; keep explanations concise. Do not add legacy alternatives or pseudocode unless requested.

## Protected features and release notes

- Never remove, disable or omit the About Piranha Easter Egg or its WebP animation. This also applies to package-size optimizations. Preserve its five-click logo trigger, replay from the beginning, final-frame display and user-controlled X/Escape closure.
- Preserve XLSX spreadsheet exports and their required runtime dependencies when reducing package size.
- For every release, including every RC and stable version, create a non-empty entry for the exact release version in both `CHANGELOG.md` and `com_breezingformsng_changelog.xml` before building, tagging or publishing it. Review the complete diff and commit history from the previous release; for a stable release, aggregate the full cycle since the previous stable tag, including all RC changes.
- Keep the Markdown changelog, XML changelog and GitHub release body semantically aligned. Write concise plain-English entries under public categories such as Added, Changed, Fixed and Security, describing observable behavior or administrator benefit. Exclude internal build logs, release mechanics and development-only dependency changes.
- Do not claim the public changelog is updated until the change is merged into `main` and the published GitHub release body has been read back and verified.
- When excluding files shipped by earlier versions, add idempotent installer cleanup for the exact obsolete paths and test that protected assets and user data remain untouched. Report deletion failures; never sweep entire runtime dependency directories.
