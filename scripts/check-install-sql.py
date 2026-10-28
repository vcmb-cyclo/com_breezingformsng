#!/usr/bin/env python3
"""Contrôle CI des fichiers SQL du composant.

Refuse tout ``INSERT … VALUES`` sans liste de colonnes : ajouter une colonne à
la table décale alors les valeurs et fait échouer l'installation.

Usage: python3 scripts/check-install-sql.py
"""

import re
import subprocess
import sys

INSERT_WITHOUT_COLUMNS = re.compile(
    r'\bINSERT\s+(?:IGNORE\s+)?INTO\s+`?[\w#]+`?\s+VALUES\b',
    re.IGNORECASE,
)


def main():
    files = subprocess.run(
        ['git', 'ls-files', '*.sql'], check=True, capture_output=True, text=True
    ).stdout.split()
    errors = []

    for path in files:
        if '/vendor/' in path or '/libraries/' in path:
            continue
        with open(path, encoding='utf-8') as handle:
            source = handle.read()
        for match in INSERT_WITHOUT_COLUMNS.finditer(source):
            line = source.count('\n', 0, match.start()) + 1
            errors.append(f'{path}:{line}: INSERT sans liste de colonnes')

    for error in errors:
        print(error, file=sys.stderr)
    if errors:
        print(f'{len(errors)} INSERT sans liste de colonnes.', file=sys.stderr)
        return 1
    print('SQL OK.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
