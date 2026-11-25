---
name: joomla-security
description: Reviews or changes Joomla 6 security-sensitive code in BreezingForms NG. Use for ACL, CSRF, input validation, SQL, XSS, uploads, callbacks, exports, logs, or security regressions.
---

# Joomla security

Use `docs/best-practices/joomla6-security.md` and `AGENTS.md`; do not restate or weaken them.

## Workflow

1. Name the trust boundary: actor and ACL, request/token, untrusted input, stored data, file, payment callback, export or log.
2. Find the server-side enforcement point. UI checks, client validation and CSRF alone do not prove authorisation.
3. Prefer a deterministic control before adding prose:
   - Semgrep fixture/rule for a stable source pattern;
   - PHPUnit for policy, validation and service behaviour;
   - Playwright for Joomla ACL, CSRF, enabled add-ons and real request flow.
4. Add a negative test that proves the unsafe request changes nothing. Test the real optional extension when the path depends on one.
5. Report the boundary, root cause, control, test level and remaining risk. Never put secrets or submitted data in fixtures or logs.

## Review prompts

- State change: does the server verify the Joomla permission and CSRF token before mutation?
- Input/output: are values normalised, SQL values bind parameters, identifiers allowlisted and output escaped for its exact context?
- Files/callbacks: are origin, ownership, size, type, upload path and replay constraints checked before use?
- Data exposure: are exports, previews, AJAX/API responses and logs limited to authorised, non-sensitive data?

## Regression rule

For a confirmed defect, invoke `skill-improvement` before changing this skill. Consolidate an existing rule when possible; do not add a rule when a mechanical control closes the gap.
