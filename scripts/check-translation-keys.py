#!/usr/bin/env python3
"""Contrôle CI des clefs de langue utilisées dans le code.

Chaque clef du composant, du module ou des plugins citée dans le PHP, le
JavaScript ou le XML doit exister dans un fichier en-GB ; sinon Joomla affiche
la clef brute. Une base de ``Text::plural()`` est valide quand une variante
existe (``_1``, ``_MORE``…). Une clef construite par concaténation (préfixe
terminé par ``_``) n'est pas contrôlée.

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

    def defined(key):
        return key in keys or f'{key}_1' in keys or f'{key}_MORE' in keys

    errors = []
    for path in tracked('*.php', '*.js', '*.xml'):
        with open(path, encoding='utf-8', errors='replace') as handle:
            for number, line in enumerate(handle, 1):
                for key in KEY.findall(line):
                    if not defined(key):
                        errors.append(f'{path}:{number}: clef absente de en-GB : {key}')

    for error in errors:
        print(error, file=sys.stderr)
    if errors:
        print(f'{len(errors)} clef(s) de langue absente(s).', file=sys.stderr)
        return 1
    print('Clefs de langue OK.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
