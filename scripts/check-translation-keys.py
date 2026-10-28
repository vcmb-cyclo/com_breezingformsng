#!/usr/bin/env python3
"""Contrôle CI des clefs de langue utilisées dans le code.

Chaque clef du composant, du module ou des plugins citée dans le PHP, le
JavaScript ou le XML doit exister dans un fichier en-GB ; sinon Joomla affiche
la clef brute.

- Fichier XML (``label``, ``description``, options…) : Joomla y traduit la
  clef telle quelle, qui doit donc exister.
- Premier argument d'un appel ``_(``, ``sprintf(`` ou ``script(`` : la clef doit
  exister telle quelle.
- Premier argument d'un appel ``plural(`` : une variante suffit (``_1``,
  ``_MORE``…).
- Clef citée ailleurs (variable, tableau, valeur de retour) : la clef ou une
  variante de pluriel, car sa valeur peut finir dans ``plural()`` sans que le
  contrôle puisse le suivre.

Une clef construite par concaténation (préfixe terminé par ``_``) n'est pas
contrôlée.

Usage: python3 scripts/check-translation-keys.py
"""

import re
import subprocess
import sys

KEY = re.compile(
    r'\b((?:COM_BREEZINGFORMSNG|MOD_BREEZINGFORMS|PLG_[A-Z]+_BREEZINGFORMS[A-Z]*|PLG_SYSTEM_BFCOMPAT)'
    r'_[A-Z0-9_]*[A-Z0-9])\b'
)
INI_KEY = re.compile(r'^([A-Z][A-Z0-9_]*)\s*=')
# First argument of a Text:: / Joomla.Text. call, possibly on the next line.
CALL_ARGUMENT = re.compile(r'\b(_|sprintf|script|plural)\(\s*([\'"])([A-Z][A-Z0-9_]*)\2')
SKIPPED = re.compile(r'/(vendor|libraries|node_modules)/|^(tests|scripts|joomla-stubs)/|^\.')


def tracked(*patterns):
    output = subprocess.run(
        ['git', 'ls-files', *patterns], check=True, capture_output=True, text=True
    ).stdout
    return [path for path in output.split('\n') if path and not SKIPPED.search(path)]


def main():
    keys = set()
    for path in tracked('*en-GB*.ini'):
        with open(path, encoding='utf-8') as handle:
            keys.update(m.group(1) for m in map(INI_KEY.match, handle) if m)

    errors = []
    for path in tracked('*.php', '*.js', '*.xml'):
        with open(path, encoding='utf-8', errors='replace') as handle:
            source = handle.read()
        # Translation call each key is the direct first argument of, by offset.
        calls = {match.start(3): match.group(1) for match in CALL_ARGUMENT.finditer(source)}
        # XML has no plural: Joomla translates the exact key.
        exact_only = path.endswith('.xml')

        for match in KEY.finditer(source):
            key = match.group(1)
            has_plural = not exact_only and (f'{key}_1' in keys or f'{key}_MORE' in keys)
            accepted = key in keys or (has_plural and calls.get(match.start(1), 'plural') == 'plural')
            if not accepted:
                line = source.count('\n', 0, match.start()) + 1
                errors.append(f'{path}:{line}: clef absente de en-GB : {key}')

    for error in errors:
        print(error, file=sys.stderr)
    if errors:
        print(f'{len(errors)} clef(s) de langue absente(s).', file=sys.stderr)
        return 1
    print('Clefs de langue OK.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
