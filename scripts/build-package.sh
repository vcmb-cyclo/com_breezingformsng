#!/usr/bin/env bash

set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_dir="${1:-${root_dir}/build}"

if [[ "${output_dir}" != /* ]]; then
    output_dir="${root_dir}/${output_dir}"
fi

package_dir="${output_dir}/package"
manifest="${root_dir}/com_breezingformsng.xml"
version="$(sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' "${manifest}" | head -n 1)"

if [[ -z "${version}" ]]; then
    echo "Unable to resolve the component version." >&2
    exit 1
fi

rm -rf "${package_dir}"
mkdir -p "${package_dir}" "${output_dir}"

while IFS= read -r -d '' path; do
    mkdir -p "${package_dir}/$(dirname "${path}")"
    cp "${root_dir}/${path}" "${package_dir}/${path}"
done < <(
    git -C "${root_dir}" ls-files -z \
        administrator \
        components \
        media \
        com_breezingformsng.xml \
        script.php
)

build_timestamp="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
sed -i \
    -e 's|<buildType>development</buildType>|<buildType>production</buildType>|' \
    -e "s|<buildTimestamp></buildTimestamp>|<buildTimestamp>${build_timestamp}</buildTimestamp>|" \
    "${package_dir}/com_breezingformsng.xml"

# Install PHP dependencies (managed by Composer) into the package.
# Some dependencies declare hard PHP extension requirements: tecnickcom/
# tc-lib-barcode (via TCPDF v7's tc-lib-pdf) needs ext-bcmath, and
# phpoffice/phpspreadsheet needs ext-gd, ext-zip, ext-xml and friends.
# Those only have to be present on the deployment target - not on the
# machine running this build script - so skip the extension checks only
# (ext-*), while still enforcing the PHP version requirement.
composer install --no-dev --no-interaction --quiet \
    --ignore-platform-req='ext-*' \
    --working-dir="${package_dir}/administrator/components/com_breezingformsng"

# TCPDF v7 ships its font engine (tecnickcom/tc-lib-pdf-font) without the
# compiled font resource files (target/fonts/*.json) that TCPDF needs at
# runtime - "composer install" alone leaves the package unusable for PDF
# generation. Compile them from the bundled AFM/TTF sources.
pdf_font_dir="${package_dir}/administrator/components/com_breezingformsng/vendor/tecnickcom/tc-lib-pdf-font"
if [[ -d "${pdf_font_dir}" ]]; then
    # Redirected to stderr: this script's stdout is the archive path callers
    # capture via `$(scripts/build-package.sh)` (see build-package.yml), and
    # "make fonts" is chatty - left on stdout it corrupts that capture (and,
    # in CI, breaks $GITHUB_OUTPUT parsing since none of its lines are
    # "key=value").
    (cd "${pdf_font_dir}" && make fonts >&2)

    # Prune TCPDF font families the component does not use: it relies on
    # the helvetica core fonts plus a runtime TTF conversion of
    # media/.../verdana.ttf (imported into the same target/fonts directory
    # on first use - see PdfDocument::importTtfFont()).
    find "${pdf_font_dir}/target/fonts" -mindepth 1 -maxdepth 1 -type d \
        ! -name 'core' \
        -exec rm -rf {} +

    # "make fonts" pulls its own build-only Composer dependency
    # (tecnickcom/tc-font-mirror, the full upstream font set) into util/ -
    # over 100MB of source fonts that are only needed to produce the
    # target/fonts/*.json files above. Not needed at runtime.
    rm -rf "${pdf_font_dir}/util"
fi

# TCPDF v7's dependency tree is 15 separate tecnickcom/* packages (vs. one
# self-contained package for v6). Each ships its own test suite, CI config
# and examples, none of which are needed at runtime - prune them.
tecnickcom_dir="${package_dir}/administrator/components/com_breezingformsng/vendor/tecnickcom"
if [[ -d "${tecnickcom_dir}" ]]; then
    find "${tecnickcom_dir}" -mindepth 2 -maxdepth 2 -type d \
        \( -iname 'test' -o -iname 'tests' -o -iname '.github' -o -iname 'examples' \) \
        -exec rm -rf {} +
fi

archive="${output_dir}/com_breezingformsng-${version}.zip"
rm -f "${archive}"

(
    cd "${package_dir}"
    zip -qr "${archive}" .
)

printf '%s\n' "${archive}"
