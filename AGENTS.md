# AGENTS.md

## Scope and sources

- Joomla 6 and PHP 8.3+ only. Use native, non-deprecated APIs and modern conventions. No compatibility code for older versions: no fallbacks, polyfills, shims or runtime version checks.
- Before editing, read `docs/best-practices/README.md` and only the relevant thematic guides it lists. Verify API signatures against the installed CMS source or versioned official documentation; do not assume external documentation matches the installed patch release.
- Follow higher-priority instructions when they conflict with this file, and flag repository-level conflicts.

## Changes

- Inspect the affected code, extension manifest, project conventions, tests and configured Joomla/PHP versions. The baseline is Joomla 6.0.x / PHP 8.3 until the project proves otherwise.
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
- For installer/DB changes, verify fresh install, upgrade from the previous schema and rerun idempotence against disposable database copies. For view/form changes, verify relevant frontend and administrator paths, ACL deny/allow, CSRF, translations and dark/mobile layout.
- Report changed files, functional and migration impact, security considerations, checks run with pass/fail/not-run results, and remaining risks. Never claim a check passed unless executed.

## Agent workflow

- By default, delegate independent, non-overlapping work to as many subagents as the task and available capacity warrant. Give them read-only investigations or ownership of disjoint files; keep ordered or cross-file decisions with one agent.
- Integrate subagent results and verify the complete change. If a commit is requested, make one coherent commit; do not let subagents commit independently.

## Output

- Complete the requested task, then stop. Return final code directly when coding is requested; keep explanations concise. Do not add legacy alternatives or pseudocode unless requested.
