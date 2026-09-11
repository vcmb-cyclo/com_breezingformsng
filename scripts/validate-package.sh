#!/usr/bin/env bash

set -euo pipefail

archive="${1:?Usage: scripts/validate-package.sh path/to/package.zip}"

if [[ ! -f "${archive}" ]]; then
    echo "Package not found: ${archive}" >&2
    exit 1
fi

entries="$(unzip -Z1 "${archive}")"

required=(
    "com_breezingformsng.xml"
    "script.php"
    "administrator/components/com_breezingformsng/services/provider.php"
    "administrator/components/com_breezingformsng/src/Extension/BreezingFormsNGComponent.php"
    "administrator/components/com_breezingformsng/sql/install.mysql.utf8.sql"
    "administrator/components/com_breezingformsng/plugins/bfcompat/bfcompat.xml"
    "administrator/components/com_breezingformsng/vendor/bgli100/securimage/securimage.php"
    "administrator/components/com_breezingformsng/vendor/bgli100/securimage/CaptchaObject.php"
    "administrator/components/com_breezingformsng/vendor/bgli100/securimage/StorageAdapter/Session.php"
    "administrator/components/com_breezingformsng/vendor/autoload.php"
    "administrator/components/com_breezingformsng/vendor/tecnickcom/tcpdf/tcpdf.php"
    "administrator/components/com_breezingformsng/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/core/helvetica.json"
    "administrator/components/com_breezingformsng/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php"
    "components/com_breezingformsng/breezingformsng.php"
    "components/com_breezingformsng/src/Service/Rendering/QuickMode/QuickModeSubmittedValueHydrator.php"
    "media/com_breezingformsng/css/custom.css"
)

for path in "${required[@]}"; do
    if ! grep -Fxq "${path}" <<<"${entries}"; then
        echo "Required package entry is missing: ${path}" >&2
        exit 1
    fi
done

forbidden_patterns=(
    '^\.git/'
    '^\.github/'
    '^scripts/'
    '^build/'
    '/cache/'
    '/logs/'
    '^media/com_breezingformsng/images/site/captcha/.*\.php$'
)

for pattern in "${forbidden_patterns[@]}"; do
    if grep -Eq "${pattern}" <<<"${entries}"; then
        echo "Forbidden development artifact found in package: ${pattern}" >&2
        exit 1
    fi
done

obsolete_entries=(
    "administrator/components/com_breezingformsng/libraries/jquery/jq.js"
    "components/com_breezingformsng/libraries/jquery/jq.min.legacy.js"
    "components/com_breezingformsng/libraries/jquery/jq.min.js"
    "components/com_breezingformsng/libraries/jquery/plupload/Moxie.swf"
    "components/com_breezingformsng/libraries/js/overlib_mini.js"
    "administrator/components/com_breezingformsng/libraries/wz_dragdrop/wz_dragdrop.js"
    "components/com_breezingformsng/libraries/js/sweetalert.min.js"
    "components/com_breezingformsng/libraries/jquery/jtable/jq.jtable.js"
    "administrator/components/com_breezingformsng/libraries/jquery/jq-ui.min.js"
    "components/com_breezingformsng/libraries/jquery/jq-ui.min.js"
    "administrator/components/com_breezingformsng/libraries/jquery/themes/easymode/easymode.tabs.css"
    "components/com_breezingformsng/libraries/jquery/jquery.validationEngine.js"
    "administrator/components/com_breezingformsng/libraries/jquery/jquery.validationEngine.js"
    "components/com_breezingformsng/libraries/jquery/jq.mobile.min.js"
    "components/com_breezingformsng/themes/quickmode/jq.mobile.1.4.5.min.css"
    "components/com_breezingformsng/themes/quickmode/jq.mobile.1.4.5.icons.min.css"
    "components/com_breezingformsng/themes/quickmode/jq.mobile.min.css"
    "administrator/components/com_breezingformsng/libraries/jquery/tooltip.js"
    "components/com_breezingformsng/libraries/jquery/tooltip.js"
    "components/com_breezingformsng/libraries/jquery/tooltip.css"
    "administrator/components/com_breezingformsng/libraries/jquery/plugins/md5.js"
    "administrator/components/com_breezingformsng/libraries/jquery/plugins/json.js"
)

for path in "${obsolete_entries[@]}"; do
    if grep -Fxq "${path}" <<<"${entries}"; then
        echo "Obsolete package entry is still present: ${path}" >&2
        exit 1
    fi
done

if ! unzip -p "${archive}" com_breezingformsng.xml \
    | grep -Fq '<extension type="component" version="6.0" method="upgrade">'; then
    echo "Invalid Joomla component manifest." >&2
    exit 1
fi

if ! unzip -p "${archive}" com_breezingformsng.xml \
    | grep -Fq '<media folder="media">'; then
    echo "BreezingForms themes media directory is not declared in the manifest." >&2
    exit 1
fi

if ! unzip -p "${archive}" com_breezingformsng.xml \
    | grep -Fq '<folder>breezingforms/themes</folder>'; then
    echo "BreezingForms themes folder is not declared in the manifest." >&2
    exit 1
fi

echo "Package validation passed: ${archive}"
