#!/usr/bin/env bash

set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
temporary_dir="$(mktemp -d)"
trap 'rm -rf "${temporary_dir}"' EXIT

package_dir="${temporary_dir}/package"
mkdir -p "${package_dir}"
validator="${root_dir}/scripts/validate-package.sh"
builder="${root_dir}/scripts/build-package.sh"

# Minimal complete archive for the actual production validator.
while IFS= read -r path; do
    mkdir -p "${package_dir}/$(dirname "${path}")"
    printf 'fixture\n' > "${package_dir}/${path}"
done < <(sed -n '/^required=(/,/^)/s/^    "\(.*\)"$/\1/p' "${validator}")
cp "${root_dir}/breezingformsng.xml" "${package_dir}/breezingformsng.xml"
captcha_dir="${package_dir}/administrator/components/com_breezingformsng/vendor/bgli100/securimage"
mkdir -p "${captcha_dir}/audio/noise" "${captcha_dir}/audio/en"
printf 'noise\n' > "${captcha_dir}/audio/noise/background.wav"
printf 'letter\n' > "${captcha_dir}/audio/en/0.wav"

# Execute the builder's real vendor-cleanup block against the fixture.
sed -n '/^vendor_dir=/,/^archive=/{ /^archive=/d; p; }' "${builder}" > "${temporary_dir}/cleanup.sh"
source "${temporary_dir}/cleanup.sh"
test ! -d "${captcha_dir}/audio/noise"
test -f "${captcha_dir}/audio/en/0.wav"
test -f "${package_dir}/media/com_breezingformsng/images/bfng-easter-egg-2026.webp"

archive="${temporary_dir}/fixture.zip"
(cd "${package_dir}" && zip -qr "${archive}" .)
bash "${validator}" "${archive}" > /dev/null

zip -dq "${archive}" media/com_breezingformsng/images/bfng-easter-egg-2026.webp
if bash "${validator}" "${archive}" > "${temporary_dir}/validation.log" 2>&1; then
    echo 'Validator accepted a package without the Easter Egg.' >&2
    exit 1
fi
grep -Fq 'Required package entry is missing: media/com_breezingformsng/images/bfng-easter-egg-2026.webp' "${temporary_dir}/validation.log"
(cd "${package_dir}" && zip -q "${archive}" media/com_breezingformsng/images/bfng-easter-egg-2026.webp)

mkdir -p "${captcha_dir}/audio/noise"
printf 'noise\n' > "${captcha_dir}/audio/noise/background.wav"
(cd "${package_dir}" && zip -q "${archive}" administrator/components/com_breezingformsng/vendor/bgli100/securimage/audio/noise/background.wav)
if bash "${validator}" "${archive}" > "${temporary_dir}/validation.log" 2>&1; then
    echo 'Validator accepted excluded CAPTCHA background noise.' >&2
    exit 1
fi
grep -Fq 'vendor/bgli100/securimage/audio/noise/' "${temporary_dir}/validation.log"

# Missing release notes must fail before deleting existing staging contents.
guard_dir="${temporary_dir}/release-guard"
mkdir -p "${guard_dir}/scripts" "${guard_dir}/build/package"
cp "${builder}" "${guard_dir}/scripts/build-package.sh"
printf '<version>0.0.0-test</version>\n' > "${guard_dir}/breezingformsng.xml"
printf 'keep\n' > "${guard_dir}/build/package/keep.txt"
printf '## 0.0.0-test\n\n## Previous\n\n- Old entry.\n' > "${guard_dir}/CHANGELOG.md"
printf '<version>0.0.0-test</version>\n' > "${guard_dir}/com_breezingformsng_changelog.xml"
if bash "${guard_dir}/scripts/build-package.sh" > "${temporary_dir}/guard.log" 2>&1; then
    echo 'Builder accepted an empty Markdown release section.' >&2
    exit 1
fi
grep -Fq 'CHANGELOG.md has no non-empty section for 0.0.0-test.' "${temporary_dir}/guard.log"
test -f "${guard_dir}/build/package/keep.txt"

printf '## 0.0.0-test\n\n- Release note.\n' > "${guard_dir}/CHANGELOG.md"
printf '<version>0.0.0-old</version>\n' > "${guard_dir}/com_breezingformsng_changelog.xml"
if bash "${guard_dir}/scripts/build-package.sh" > "${temporary_dir}/guard.log" 2>&1; then
    echo 'Builder accepted missing XML release notes.' >&2
    exit 1
fi
grep -Fq 'com_breezingformsng_changelog.xml has no entry for 0.0.0-test.' "${temporary_dir}/guard.log"
test -f "${guard_dir}/build/package/keep.txt"

echo 'PASS: CAPTCHA noise removed; English audio and Easter Egg kept; invalid ZIPs rejected; release notes required before staging cleanup.'
