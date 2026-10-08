# plg_breezingforms
Joomla Plugin for Breezingforms (J5)

## Content plugin (Joomla 6)

The bundled Content – BreezingForms NG plugin is installed and enabled with the component. Embed a published form in an article with `{BreezingForms:Contact}` (case-sensitive form name).

Syntax: `{BreezingForms:formname,page,border,urlparams,suffix,editable,editable_override}`. Defaults: page 1, border 1, empty parameters/suffix, editing disabled. Example: `{BreezingForms:Contact,1,0,&ff_param_source=article}`. Parameters must use the `ff_param_` prefix. Keep the tag on one line in the editor. Enable “Use an iframe” in the plugin settings for an isolated rendering with automatic height when configured on the form.
