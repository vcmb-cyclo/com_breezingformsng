---
name: joomla-events
description: Reviews or changes Joomla plugin events and event argument access in BreezingForms NG. Use for event classes, dispatchers, subscribers, add-on integrations, or event regressions.
---

# Joomla events

Use `docs/best-practices/joomla6-plugins-events.md` and verify behavior against the installed Joomla source.

## Event argument access

- Treat `getArgument()` as executable dispatch: Joomla may resolve an argument by invoking a matching `get<Name>()` or `onGet<Name>()` method.
- Do not define an event accessor whose name matches an argument when it delegates to `getArgument()` for that same argument; this recurses. Expose custom payloads through Joomla's argument map and native `getArgument()` API.
- When adding or changing event payloads, test every argument through the real Joomla event class. Assert that no matching custom accessor shadows argument resolution.
- If an add-on is enabled on the path, exercise that add-on in integration or end-to-end tests; source assertions alone do not cover event dispatch behavior.

## Regression response

For a confirmed event regression, identify the Joomla dispatch behavior that caused it, add a runtime regression test, then use `skill-improvement` to decide whether this guidance should change.
