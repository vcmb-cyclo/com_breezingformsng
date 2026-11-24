# Joomla security baseline

Use `AGENTS.md` and `docs/best-practices/joomla6-security.md` as the current
security instructions. Apply server-side ACL and CSRF checks, validate input,
escape output, bind SQL values, protect uploads and paths, and never log
secrets. Add PHPUnit and Playwright coverage for sensitive runtime paths.
