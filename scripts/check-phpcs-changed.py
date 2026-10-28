#!/usr/bin/env python3
"""Contrôle CI de PHPCS sur les fichiers PHP modifiés.

phpcs.xml.dist ne liste qu'une partie du code. Pour le reste, une PR ne doit
pas ajouter d'erreur : chaque fichier PHP modifié est comparé à sa version de
la branche de base, et un nouveau fichier doit être sans erreur.

Usage: python3 scripts/check-phpcs-changed.py <ref de base>
"""

import json
import os
import re
import subprocess
import sys
import tempfile

SKIPPED = re.compile(r'/(vendor|libraries|node_modules)/|^(joomla-stubs|build|coverage)/')


def git(*args):
    return subprocess.run(['git', *args], check=True, capture_output=True, text=True).stdout


# PHPCS exit code when every given file is excluded by phpcs.xml.dist.
NO_FILES_CHECKED = 16


def phpcs_errors(path):
    """Errors of one file, warnings left aside; None when the ruleset excludes it."""
    result = subprocess.run(
        ['vendor/bin/phpcs', '--standard=phpcs.xml.dist', '--report=json', '-q', path],
        capture_output=True, text=True,
    )
    if result.returncode == NO_FILES_CHECKED:
        return None
    # The exit code is a bit mask of what was found: only a missing report fails.
    try:
        report = json.loads(result.stdout)
    except json.JSONDecodeError as error:
        raise RuntimeError(f'phpcs a échoué sur {path} :\n{result.stderr}{result.stdout}') from error
    return report['totals']['errors']


def main():
    if len(sys.argv) != 2:
        print(__doc__, file=sys.stderr)
        return 2

    base = git('merge-base', sys.argv[1], 'HEAD').strip()
    changed = [
        path for path in git('diff', '--name-only', '--diff-filter=AMR', base, 'HEAD', '--', '*.php').split('\n')
        if path and not SKIPPED.search(path)
    ]
    root = os.getcwd()
    errors = []

    with tempfile.TemporaryDirectory() as directory:
        # The base versions are checked with the same tools and ruleset.
        for name in ('vendor', 'phpcs.xml.dist'):
            os.symlink(os.path.join(root, name), os.path.join(directory, name))

        for path in changed:
            after = phpcs_errors(path)
            if after is None:
                print(f'IGNORÉ {path}: exclu par phpcs.xml.dist')
                continue
            try:
                previous = git('show', f'{base}:{path}')
            except subprocess.CalledProcessError:
                previous = None

            before = 0
            if previous is not None:
                # Same relative path, so path-based rules behave the same.
                copy = os.path.join(directory, path)
                os.makedirs(os.path.dirname(copy), exist_ok=True)
                with open(copy, 'w', encoding='utf-8') as handle:
                    handle.write(previous)
                os.chdir(directory)
                try:
                    before = phpcs_errors(path) or 0
                finally:
                    os.chdir(root)

            status = 'OK' if after <= before else 'ERREUR'
            print(f'{status} {path}: {before} -> {after} erreur(s) PHPCS')
            if after > before:
                errors.append(path)

    if errors:
        print(f'{len(errors)} fichier(s) avec de nouvelles erreurs PHPCS. '
              f'Détail : vendor/bin/phpcs --standard=phpcs.xml.dist <fichier>', file=sys.stderr)
        return 1
    print(f'PHPCS : aucune nouvelle erreur sur {len(changed)} fichier(s) PHP modifié(s).')
    return 0


if __name__ == '__main__':
    sys.exit(main())
