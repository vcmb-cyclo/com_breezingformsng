# Contributing to BreezingForms NG

Thank you for your interest in improving **BreezingForms NG (BFNG)**, the community-maintained Joomla 6 form builder. Bug reports, documentation improvements, translations, tests, and focused code contributions are welcome.

The project is maintained by volunteers on a **best-effort basis**. Submitting an issue or pull request does not guarantee that it will be accepted or reviewed within a particular timeframe.

## Before you start

- Read the [README](README.md) for supported platforms, installation instructions, and project scope.
- Search [existing issues](https://github.com/vcmb-cyclo/com_breezingformsng/issues) and [pull requests](https://github.com/vcmb-cyclo/com_breezingformsng/pulls) before opening a duplicate.
- For substantial features or architectural changes, open an issue first to discuss the scope, expected behavior, alternatives, and compatibility implications.
- Keep changes focused. Separate unrelated bug fixes, refactoring, and new features into distinct pull requests.
- For security vulnerabilities, **do not open a public issue**. Follow [SECURITY.md](SECURITY.md) and use GitHub's private vulnerability reporting.

## Report a bug or request a feature

Open a [GitHub issue](https://github.com/vcmb-cyclo/com_breezingformsng/issues) and include, when relevant:

- BFNG version (and whether it is a release candidate), Joomla version, PHP version, database and browser versions.
- A clear description of the problem, expected behavior, and actual behavior.
- Minimal reproduction steps, preferably using a new form and sample data.
- Relevant logs or screenshots **with passwords, tokens, personal information, form submissions, and payment details removed**.
- For feature requests, the use case, intended users, and any backward-compatibility impact.

Please do not post exploitable security details publicly.

## Development environment

The currently documented runtime requirements are:

- Joomla 6.x
- PHP 8.3 or later
- MySQL 8.0.13+ or MariaDB 10.4+

For development and automated checks, use the repository's `composer.json`, `package.json`, and GitHub Actions workflows as the source of truth for exact tool versions. PHP 8.3, 8.4, and 8.5 are exercised in the CI configuration for the current release candidate.

Start from a fork (or a branch if you have write access):

```bash
git clone https://github.com/YOUR-ACCOUNT/com_breezingformsng.git
cd com_breezingformsng
git switch -c fix/short-description
composer install
npm ci
```

Some checks require additional Joomla libraries or a running disposable Joomla instance. See `.github/workflows/build-package.yml`, `phpunit.xml`, and the scripts under `scripts/` for the corresponding setup.

**Do not run installation, migration, destructive tests, or package import against a production website.** Back up any test site before exercising upgrade or migration paths.

## Coding and compatibility guidelines

- Follow Joomla 6 APIs, conventions, and permission checks; do not bypass ACL, CSRF, or server-side validation.
- Retain compatibility with the supported Joomla, PHP, and database versions unless the change explicitly updates the documented requirements.
- Keep backward compatibility with existing forms, stored records, packages, and supported migrations in mind. Explain intentional breaking changes.
- Treat user input, uploaded files, API/MCP arguments, payment callbacks, and imported data as untrusted. Validate and authorize operations on the server.
- Never commit credentials, API keys, real personal data, payment details, or production database dumps. Use synthetic fixtures.
- Preserve translatable strings and update the relevant language files when introducing or changing interface text. The project currently provides English, French, German, Spanish, Hungarian, Italian, Dutch, and Turkish translations.
- Add or update tests for observable behavior. Avoid tests that only search for particular strings in source files when a behavioral assertion is feasible.
- Avoid unrelated formatting changes and broad refactoring in a bug-fix PR.

## Checks before submitting a pull request

Install dependencies and run the checks relevant to your changes. The current repository provides these commands:

```bash
# PHP coding standards
composer lint:php

# PHP static analysis (configured at level 8 in phpstan.neon.dist)
vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress

# PHPUnit tests
vendor/bin/phpunit

# Frontend lint and JavaScript tests
npm run lint
npm run test:tree-model
npm run test:vendor
npm run test:container-log

# Browser tests (requires an installed Playwright browser)
npm run test:browser
```

The PHPUnit integration suite needs `BFNG_JOOMLA_LIBRARIES` pointing to a Joomla 6 `libraries` directory; the CI workflow provisions it and runs tests with `--fail-on-skipped`. For a local run with the integration suite enabled:

```bash
BFNG_JOOMLA_LIBRARIES=/path/to/joomla/libraries vendor/bin/phpunit --fail-on-skipped
```

The real-Joomla end-to-end tests use the repository's disposable Docker environment and packaging scripts. Refer to `scripts/joomla-e2e.sh`, `playwright.e2e.config.mjs`, and `.github/workflows/build-package.yml` before running them.

For changes affecting security-sensitive flows, include appropriate negative tests (forbidden roles, invalid input, CSRF failures, or rejected uploads). For UI changes, verify the Joomla administrator experience, keyboard accessibility, mobile layout, and light/dark appearance when applicable.

**Do not weaken assertions, lower analysis levels, add blanket suppressions, or regenerate snapshots without explaining the behavior change.** If a check cannot be run locally, state that explicitly in the pull request.

## Pull request process

1. Create a branch with a descriptive name and make focused changes.
2. Add or adjust tests and documentation, including translations when needed.
3. Run the applicable checks and review the diff for secrets or unintended changes.
4. Open a pull request against `main`, link the related issue, and describe:
   - **Why** the change is needed.
   - **What** changed and any compatibility or data-migration implications.
   - **How** it was tested, with commands and results.
   - Any known limitations, remaining risks, or follow-up work.
5. Respond to review feedback and keep the CI checks passing. Maintainers may ask for changes or decline proposals that do not fit the project scope.

Pull requests are reviewed on a best-effort basis. Contributors should not assume that a passing CI run alone guarantees merge or a release.

## Documentation and translations

Documentation improvements, clearer error messages, reproductions, and language fixes are welcome. When changing a translation key, ensure that other supported language files remain consistent and run the translation checks defined in CI (`scripts/check-translations.py` and `scripts/check-translation-keys.py`).

## Licensing and attribution

BreezingForms NG is distributed under **GNU GPL-2.0-or-later**; see [LICENSE](LICENSE). Only submit code, assets, or documentation you have the right to contribute under the applicable project license. Preserve existing copyright and attribution notices, including notices for the original BreezingForms/Crosstec code and third-party components. Identify any newly introduced third-party dependency and its license in the pull request.

Thank you for helping make BreezingForms NG more reliable, accessible, and secure.
