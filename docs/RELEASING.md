# Releasing BreezingForms NG

This is the procedure for publishing a release candidate (`6.1.0-RC23`) or a stable version (`6.1.0`). Most of it is automated by [`.github/workflows/build-package.yml`](../.github/workflows/build-package.yml); the steps below say what a person must do and check, and what the workflow does by itself.

A version is written the same way everywhere: `6.1.0-RC23` in the manifest, the changelogs and the ZIP name, and `v6.1.0-RC23` for the Git tag.

## 1. Before you start

Work on a branch and merge it into `main` through a pull request. A release is always tagged on a commit that is already on `main`.

Check each point and fix what fails before tagging:

| Check | How |
|---|---|
| The code builds and passes | The latest `main` run of the workflow is green: PHP 8.4 and 8.5 compatibility, lint, front-end checks, PHPStan, PHPUnit, then the package job (build, validation, installation/update/migration smoke test on Joomla, browser journeys). |
| The manifest version is the release version | `<version>` in `breezingformsng.xml`. The release job fails if the tag does not match it. |
| The changelog has an entry for this exact version | A non-empty `## <version>` section in `CHANGELOG.md` **and** an entry with `<version>` in `com_breezingformsng_changelog.xml`. `scripts/build-package.sh` refuses to build without both. |
| The entries describe what users see | Plain English under Added, Changed, Fixed and Security. No build logs, no release mechanics, no development-only dependency changes. For a stable release, gather everything since the previous stable tag, including every RC. |
| The two changelogs say the same thing | Same items, same order. |
| Translations are complete | `python3 scripts/check-translation-keys.py` and `python3 scripts/check-translations.py` pass for the eight languages. |
| Protected features are intact | The Piranha easter egg (five-click trigger, replay, final frame, close with X or Escape) and the XLSX export, including the libraries they need. The package check looks for the WebP and PhpSpreadsheet files. |
| Dependencies are clean | `composer audit` and `npm audit --omit=dev` report nothing new, and the open Dependabot alerts are understood. |

Optional but recommended: build and try the package yourself first (the same steps the workflow runs).

```bash
scripts/build-package.sh                        # writes build/com_breezingformsng-<version>.zip
scripts/validate-package.sh build/com_breezingformsng-<version>.zip
JOOMLA_IMAGE=joomla:6.2.0-php8.4-apache \
  scripts/joomla-e2e.sh build/com_breezingformsng-<version>.zip   # throwaway Joomla in Docker
```

Local builds go in `build/`. Never try the package, an upgrade or an import on a production site.

## 2. Tag

On an up-to-date `main`:

```bash
git switch main && git pull --ff-only
git tag v6.1.0-RC23
git push origin v6.1.0-RC23
```

Pushing a tag that starts with `v` is what starts the publication. Do not move or delete a tag once it is published.

## 3. What the workflow does

1. Runs every job again on the tagged commit, then builds the production ZIP and validates it.
2. Installs it on a Joomla, runs the update and migration smoke test and the browser journeys.
3. The `release` job reads the version from `breezingformsng.xml` and stops if the tag is different.
4. It takes the release notes from the `## <version>` section of `CHANGELOG.md` and appends the SHA-256 of the ZIP.
5. It creates the GitHub release with the ZIP attached:
   - a version ending in `-RC<n>` is a **pre-release** and never the "latest" release;
   - a stable version becomes "latest" only if it is at least as recent as the version already in the update stream.
6. If the version is current, a bot commits `com_breezingformsng_update.xml` to `main` (version, download URL, SHA-256, minimum PHP version, and the `rc` or `stable` tag). That file is what Joomla sites read to be offered the update.

Only one publication runs at a time. Re-running an older tag refreshes its release without touching the update stream.

## 4. After the workflow: verify what is public

Do not say that a release is published or that its notes are up to date before you have checked it.

- The release page shows the right title, the right pre-release or latest status, and the ZIP.
- Read the release body back and compare it with the changelog section: same items, plus the SHA-256 line.
- `com_breezingformsng_update.xml` on `main` shows the new version, the download URL and the SHA-256 that match the release.
- Download the ZIP from the release, check its SHA-256, and install it on a test Joomla (fresh install, then an update from the previous version).
- Pull `main` again before your next commit: the bot has added a commit.

## 5. If a regression is found after publication

The workflow has no automatic rollback, so the response is manual.

1. **Do not delete the release or move the tag.** Sites and the update stream may already point to them.
2. **Judge the impact**: data loss or a security problem is urgent; a visual defect is not. For a vulnerability, follow [SECURITY.md](../SECURITY.md) and prepare an advisory.
3. **Warn people quickly**: edit the release notes on GitHub to add a "Known issue" line with the workaround, and comment on the related issue.
4. **Fix on a branch**, with a test that fails without the fix, and merge it through a pull request.
5. **Publish a new version** (the next RC, or the next patch for a stable release) with its own changelog entry that says what was fixed. Versions only go forward.
6. **Stop offering a bad version only if you must.** The update stream never moves backwards by itself. To make sites stop being offered a faulty version before the fix is ready, edit `com_breezingformsng_update.xml` on `main` by hand through a pull request so that it points to the last good release. Sites that already updated keep what they have until the fixed version is out.

## 6. Reference

- Workflow: [`.github/workflows/build-package.yml`](../.github/workflows/build-package.yml)
- Build and checks: `scripts/build-package.sh`, `scripts/validate-package.sh`, `scripts/joomla-install-smoke.sh`, `scripts/joomla-e2e.sh`
- Update stream: `com_breezingformsng_update.xml`
- Changelogs: `CHANGELOG.md`, `com_breezingformsng_changelog.xml`
- Contribution rules: [CONTRIBUTING.md](../CONTRIBUTING.md)
