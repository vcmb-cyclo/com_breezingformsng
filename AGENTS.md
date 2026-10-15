# AGENTS.md

## Scope
- Joomla 6 only.
- PHP 8.3+ only.

## Core rules
- Use native Joomla 6 APIs and modern conventions only.
- No backward compatibility for older Joomla or PHP versions.
- No legacy/deprecated APIs.
- No fallbacks, polyfills, shims, runtime version checks, or compatibility workarounds.
- Prefer clean, strict, minimal, production-ready implementations.

## Efficiency
- Do only what is explicitly requested.
- Do not assume missing requirements.
- Only inspect and modify files strictly necessary for the task.
- Keep changes minimal and targeted.
- Stop after completing the requested task.

## Agent workflow
- Default to a multi-agent workflow: when a task splits into independent, non-overlapping units, parallelize it across subagents — at least 4 running concurrently whenever the task has that many independent units to give them.
- Good candidates: auditing or reading multiple unrelated files/areas, applying the same well-defined fix across several independent modules, one worker per language batch on a translation pass, researching several separate questions, running independent verification passes (tests, linters, screenshots) once edits land.
- Keep subagents scoped to read-only investigation, or to edits touching disjoint files with no shared state. Never split work that needs one coherent, evolving decision across files — a single git commit, a design choice that must stay consistent as it's made (e.g. this project's `--bfng-*` design-token rollout), a refactor with cross-file dependencies, or anything that depends on the outcome of the immediately preceding step.
- When a task is inherently ordered or single-file, stay sequential — parallelizing it adds coordination overhead with no benefit, and risks inconsistent decisions across the split.
- After subagents return, merge their output yourself, then verify (tests, PHPStan, PHPCS) and commit as one coherent change — don't let subagents commit independently.

## Joomla
- Prefer native Joomla 6 admin patterns before custom markup, CSS, or JavaScript.
- Keep custom CSS and JavaScript minimal.
- Respect MVC separation strictly.
- Route all user-facing strings through translation keys.
- New admin UI should follow native Joomla behavior when applicable.
- Preserve a non-AJAX fallback when practical.

## Translations
- Update `en-GB`, `fr-FR`, `de-DE`, `it-IT`, `es-ES`, `hu-HU`, `nl-NL`, and `tr-TR` together for every translation change.
- Keep wording aligned across languages.
- French must use correct spelling, grammar, typography, and accents.
- Italian must use correct spelling, grammar, typography, and accents.
- Spanish, Hungarian, Dutch, and Turkish must use correct spelling, grammar, typography, and accents.
- In translation conflicts, apply explicit instructions from Gilles and this
  repository's `AGENTS.md` before the translation skill, Joomla conventions and
  upstream wording. Preserve keys and placeholders.

## Output
- Return final code directly when coding is requested.
- Keep explanations concise.
- No legacy alternatives.
- No pseudocode unless requested.
