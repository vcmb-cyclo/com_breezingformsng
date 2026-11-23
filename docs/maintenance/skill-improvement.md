# Continuous skill improvement

The prototype currently evaluates `.agents/skills/joomla-security` and
`.agents/skills/joomla-events`. Each target has an independent trusted
manifest, criteria checksum, public fixtures and baseline. It converts
confirmed evidence into a small, reviewable candidate; it never lets an agent
publish an instruction change by itself.

## Evidence and decisions

Use `data/skill-improvement/evidence.json`. An evidence id is unique and has a
root-cause fingerprint, source and fixing commit. A candidate may move only its
selected record from `eligible` to `proposed`; that consumed state blocks a
second evaluation of the same evidence. A reviewer may later record an
`accepted` decision in a separately documented PR. The evaluator rejects every
other transition and every previously proposed or accepted record.

For each record, decide in this order:

1. Can Semgrep, PHPStan, PHPUnit or Playwright express the missing guard?
2. Does an existing canonical rule already cover the cause?
3. If guidance remains necessary, can an existing sentence be generalised or
   replaced rather than adding another rule?

The PR description records the cause, selected evidence id, baseline/candidate
report, test level and residual risk. Security reports stay private under
`SECURITY.md`; the registry contains no exploit payload, secrets or submissions.
Every record points to the committed Semgrep, PHPUnit or Playwright control
that proves its assertion; the evaluator fails if that control disappears.

## Independence and promotion

`skill-improvement.yml` is manual and checks out its evaluator from `main`.
Select a target and eligible evidence id. It accepts only an open PR whose
body names the selected evidence id. The job runs only when manually
dispatched from `main`; dispatching a changed workflow from a candidate branch
produces no evaluation. The target manifest on `main` defines valid evidence,
skill paths and candidate files.
The candidate checkout is data only: it cannot change the evaluator, fixtures,
thresholds or workflow used for the run. The workflow has read-only permissions
and emits an artifact; it cannot write, approve, merge or create a PR.

Repository branch protection must require a reviewer other than the PR author
for `.agents/skills/**`, `skills-evaluation/**`, `scripts/evaluate-skill-candidate.mjs`
and `.github/workflows/skill-improvement.yml`. Administrators must configure
this GitHub setting; no repository file can prevent an administrator override.

Public fixtures are versioned for repeatability. Hidden cases must live in a
separate private evaluator repository or protected runner. Its ref is pinned
and its output is limited to pass/fail and aggregate metrics: candidate agents
must never receive hidden inputs or logs. Until that evaluator is configured,
a public evaluation is a prototype report, not hidden-case approval.

## Loop prevention

The workflow is `workflow_dispatch` only, uses a per-target concurrency group,
rejects bot actors and accepts only targets declared in the workflow inputs.
Target manifests on `main` allowlist evidence and candidate paths. The
evaluator rejects duplicate evidence and caps each run at one record. It is
never triggered by its reports, artifacts or a skill PR.

## Joomla events target

Evidence `pr-235-event-accessor-recursion` records the confirmed Joomla event
argument-resolution recursion and points to
`tests/Integration/SubmissionEventsTest.php`. The candidate skill explains
the dispatch behavior, avoids same-argument custom accessors, and requires
runtime checks with enabled add-ons where applicable. Public fixtures are
repeatable checks, not hidden-case approval.
