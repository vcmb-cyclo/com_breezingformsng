# Security Policy

BreezingForms NG is a community-maintained form builder for Joomla 6. This policy covers the
component and the module and plugins installed with its package.

## Supported Versions

Security fixes are published in a new release of the current version line. Earlier versions do not
receive them, so upgrade to the latest release.

| Version                                       | Supported          |
| --------------------------------------------- | ------------------ |
| 6.1.x, including its latest release candidate | :white_check_mark: |
| < 6.1                                         | :x:                |

The supported versions require Joomla 6 and PHP 8.3 or later.

## Reporting a Vulnerability

Do not report a vulnerability in a public issue, pull request or discussion.

Report it privately through GitHub: on the repository, open the **Security** tab and choose
**Report a vulnerability**. Include:

- the affected version, and your Joomla and PHP versions;
- the affected screen, URL or file;
- the steps to reproduce it, and what an attacker can achieve;
- the access it needs: anonymous visitor, registered user or administrator.

Do not send real submitted form data, payment details, passwords or API keys. Test only on a site
you own or are allowed to test.

## What to Expect

The project is maintained on a best-effort basis, without a guaranteed response time. After your
report:

- we confirm that we received it and check whether we can reproduce it;
- if we accept it, we prepare a fix and publish it in a new release, then describe the issue in the
  changelog or a security advisory;
- if we decline it, we explain why.

Please keep the report private until the fix is released. We credit you in the changelog or the
advisory if you wish.

## Scope

These are outside this policy:

- **Joomla itself:** report it to the Joomla Security Strike Team.
- **Stripe, PayPal, other services and third-party extensions:** report it to their maintainers.
- **Scripts added to a form by its administrators:** these PHP and JavaScript scripts run on the
  site by design, so a flaw in one belongs to whoever wrote it.
- **Development tools:** the npm packages used to test this repository are not part of the installed
  package. Report them only if they affect the released package or its build.
