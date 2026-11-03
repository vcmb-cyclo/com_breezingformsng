# CLAUDE.md

@AGENTS.md

## Protected features and release notes

- Follow the protected-feature and release-note rules in `AGENTS.md`: never remove or disable the Piranha Easter Egg or omit its WebP, including when shrinking an installer; retain replay, the final frame and X/Escape closure. Keep XLSX exports and their required runtime dependencies.
- Every RC and stable release requires matching, non-empty entries for its exact version in `CHANGELOG.md` and `com_breezingformsng_changelog.xml` before any build, tag or publication. Align the GitHub release body with those entries, describe user benefits rather than internal build logs, and verify publication after the changes reach `main` before claiming the public notes are updated.
