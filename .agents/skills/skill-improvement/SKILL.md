---
name: skill-improvement
description: Improves project agent skills from confirmed defects, regressions, security audits and code review findings. Use when deciding whether an incident justifies a concise skill change and independent evaluation.
---

# Objective

Improve agent effectiveness without unnecessarily
increasing instruction size.

# Workflow

1. Accept only confirmed, non-sensitive evidence: a merged fix, reproduced regression, accepted audit finding or resolved review finding.
2. Identify the root cause rather than its symptoms.
3. Identify the relevant existing skill.
4. Determine why the defect was not prevented.
5. Prefer generalising or replacing an existing rule
   over adding a new one.
6. Prefer deterministic checks over LLM instructions
   whenever technically possible.
7. Propose the smallest effective modification.
8. Record the evidence fingerprint and do not analyse it again.
9. Submit the candidate for independent evaluation against the baseline.

# Constraints

- Never duplicate existing instructions.
- Never weaken security requirements.
- Never modify evaluation criteria.
- Never access hidden evaluation cases.
- Never approve your own modifications.
- Preserve existing validated capabilities.
- Minimise token consumption.
- Make no changes when no improvement is justified.
- Never modify evaluator code, fixtures, thresholds, workflow policy or an evidence record other than the selected record's permitted lifecycle transition.
- Never create, merge, approve or label a pull request. A human reviewer owns promotion.

## Supported evaluation targets

Use `data/skill-improvement/evidence.json` and the manual
`skill-improvement.yml` workflow. Each target has a trusted manifest, immutable
criteria and public fixtures under `skills-evaluation/<target>/`. The
independent evaluator reports coverage, regressions, false-positive signals,
instruction bytes, words and estimated tokens. A candidate may change only the
target skill and its selected evidence record.

The initial targets are `joomla-security` and `joomla-events`. For event
argument regressions, use the real Joomla event classes and exercise enabled
add-ons when the affected path depends on them.
