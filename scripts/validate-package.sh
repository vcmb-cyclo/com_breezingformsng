#!/usr/bin/env bash

set -euo pipefail

archive="${1:?Usage: scripts/validate-package.sh path/to/package.zip}"

if [[ ! -f "${archive}" ]]; then
    echo "Package not found: ${archive}" >&2
    exit 1
fi

entries="$(unzip -Z1 "${archive}")"

required=(
    "breezingformsng.xml"
    "script.php"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/mod_breezingformsng.xml"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/services/provider.php"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/src/Dispatcher/Dispatcher.php"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/tmpl/default.php"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/language/en-GB/mod_breezingformsng.ini"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/language/fr-FR/mod_breezingformsng.ini"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/language/de-DE/mod_breezingformsng.ini"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/language/it-IT/mod_breezingformsng.ini"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/language/es-ES/mod_breezingformsng.ini"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/language/hu-HU/mod_breezingformsng.ini"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/language/nl-NL/mod_breezingformsng.ini"
    "administrator/components/com_breezingformsng/modules/mod_breezingformsng/language/tr-TR/mod_breezingformsng.ini"
    "administrator/components/com_breezingformsng/plugins/breezingformsng/breezingformsng.xml"
    "administrator/components/com_breezingformsng/plugins/breezingformsng/services/provider.php"
    "administrator/components/com_breezingformsng/plugins/breezingformsng/src/Extension/Breezingforms.php"
    "administrator/components/com_breezingformsng/plugins/breezingformsng/js/resize.js"
    "administrator/components/com_breezingformsng/services/provider.php"
    "administrator/components/com_breezingformsng/src/Extension/BreezingFormsNGComponent.php"
    "administrator/components/com_breezingformsng/sql/install.mysql.utf8.sql"
    "administrator/components/com_breezingformsng/plugins/bfcompat/bfcompat.xml"
    "administrator/components/com_breezingformsng/plugins/dropbox/dropbox.xml"
    "administrator/components/com_breezingformsng/plugins/dropbox/services/provider.php"
    "administrator/components/com_breezingformsng/plugins/dropbox/src/Extension/Dropbox.php"
    "administrator/components/com_breezingformsng/plugins/dropbox/src/Service/DropboxUploader.php"
    "administrator/components/com_breezingformsng/plugins/dropbox/language/en-GB/plg_breezingforms_addons_dropbox.ini"
    "administrator/components/com_breezingformsng/plugins/privacy/breezingformsng.xml"
    "administrator/components/com_breezingformsng/vendor/gregwar/captcha/src/Gregwar/Captcha/CaptchaBuilder.php"
    "administrator/components/com_breezingformsng/vendor/gregwar/captcha/src/Gregwar/Captcha/Font/captcha0.ttf"
    "components/com_breezingformsng/src/Service/Security/CaptchaImage.php"
    "administrator/components/com_breezingformsng/vendor/autoload.php"
    "administrator/components/com_breezingformsng/vendor/tecnickcom/tc-lib-pdf/src/Tcpdf.php"
    "administrator/components/com_breezingformsng/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/core/helvetica.json"
    "administrator/components/com_breezingformsng/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php"
    "components/com_breezingformsng/breezingformsng.php"
    "components/com_breezingformsng/src/Service/Rendering/QuickMode/QuickModeSubmittedValueHydrator.php"
    "media/com_breezingformsng/css/custom.css"
    "media/com_breezingformsng/images/bfng-easter-egg-2026.webp"
)

for path in "${required[@]}"; do
    if ! grep -Fxq "${path}" <<<"${entries}"; then
        echo "Required package entry is missing: ${path}" >&2
        exit 1
    fi
done

forbidden_patterns=(
    '^administrator/components/com_breezingformsng/vendor/bgli100/securimage/'
    '^administrator/components/com_breezingformsng/vendor/tecnickcom/tcpdf/'
    '^administrator/components/com_breezingformsng/vendor/bin/'
    '^administrator/components/com_breezingformsng/vendor/phpunit/'
    '^administrator/components/com_breezingformsng/vendor/phpstan/'
    '^administrator/components/com_breezingformsng/vendor/squizlabs/'
    '^administrator/components/com_breezingformsng/vendor/sebastian/'
    '^administrator/components/com_breezingformsng/vendor/nikic/'
    '^administrator/components/com_breezingformsng/vendor/myclabs/'
    '^administrator/components/com_breezingformsng/vendor/phar-io/'
    '^administrator/components/com_breezingformsng/vendor/theseer/'
    '^administrator/components/com_breezingformsng/vendor/staabm/'
    '^\.git/'
    '^\.github/'
    '/vendor/(.*/)?(test|tests|PHPStan|\.github|bin)/'
    '/vendor/.*(phpunit[^/]*\.xml|phpstan[^/]*\.neon|phpcs[^/]*\.xml)'
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
    "components/com_breezingformsng/libraries/jquery/expressInstall.swf"
    "components/com_breezingformsng/libraries/jquery/plupload/Moxie.swf"
    "components/com_breezingformsng/libraries/js/overlib_mini.js"
    "administrator/components/com_breezingformsng/libraries/wz_dragdrop/wz_dragdrop.js"
    "media/com_breezingformsng/font-awesome/css/font-awesome.css"
    "media/com_breezingformsng/font-awesome/css/font-awesome.min.css"
    "media/com_breezingformsng/font-awesome/fonts/FontAwesome.otf"
    "media/com_breezingformsng/font-awesome/fonts/fontawesome-webfont.eot"
    "media/com_breezingformsng/font-awesome/fonts/fontawesome-webfont.svg"
    "media/com_breezingformsng/font-awesome/fonts/fontawesome-webfont.ttf"
    "media/com_breezingformsng/font-awesome/fonts/fontawesome-webfont.woff"
    "media/com_breezingformsng/font-awesome/fonts/fontawesome-webfont.woff2"
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

if ! unzip -p "${archive}" breezingformsng.xml \
    | grep -Fq '<extension type="component" version="6.0" method="upgrade">'; then
    echo "Invalid Joomla component manifest." >&2
    exit 1
fi

if ! unzip -p "${archive}" breezingformsng.xml \
    | grep -Fq '<media folder="media">'; then
    echo "BreezingForms themes media directory is not declared in the manifest." >&2
    exit 1
fi

if ! unzip -p "${archive}" breezingformsng.xml \
    | grep -Fq '<folder>breezingforms/themes</folder>'; then
    echo "BreezingForms themes folder is not declared in the manifest." >&2
    exit 1
fi

echo "Package validation passed: ${archive}"
