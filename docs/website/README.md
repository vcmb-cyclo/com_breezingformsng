# Website assets for breezingforms-ng.vcmb.fr

Files to publish on the project website. They are not part of the component package.

| File | Where it goes |
|---|---|
| `llms.txt` | Site root: `https://breezingforms-ng.vcmb.fr/llms.txt` |
| `jsonld.html` | `<head>` of the home page (template custom code or a custom HTML module); update `softwareVersion` at each release |
| `migrating-breezingforms-to-joomla-6.en.html` | Article body, alias `migrating-breezingforms-to-joomla-6`; title and meta description are in the comment at the top |
| `migrer-breezingforms-vers-joomla-6.fr.html` | Same article in French, alias `migrer-breezingforms-vers-joomla-6` |

Also on the website:
- Enable a sitemap (`/sitemap.xml`) and declare it in `robots.txt` with `Sitemap: https://breezingforms-ng.vcmb.fr/sitemap.xml`.
- Give every page a meta description.
- Make sure AI crawlers (GPTBot, ClaudeBot, PerplexityBot, Google-Extended) are not blocked by a firewall or CDN rule.
