#!/usr/bin/env bash

set -euo pipefail

archive="${1:?Usage: scripts/joomla-install-smoke.sh path/to/package.zip}"
contentbuilder_archive="${CONTENTBUILDER_ARCHIVE:-}"
joomla_image="${JOOMLA_IMAGE:-joomla:6.1.2-php8.3-apache}"
mysql_image="${MYSQL_IMAGE:-mysql:8.4}"
run_id="${GITHUB_RUN_ID:-local}-$$"
network="bfng-smoke-${run_id}"
db_container="bfng-smoke-db-${run_id}"
web_container="bfng-smoke-web-${run_id}"
container_archive="/tmp/com_breezingformsng.zip"

cleanup() {
    local exit_status=$?

    if [[ "${exit_status}" -ne 0 ]]; then
        echo "Smoke test failed - dumping container state for diagnosis:" >&2
        docker ps -a --filter "name=${web_container}" --filter "name=${db_container}" >&2 || true
        docker logs "${web_container}" >&2 || true
        docker logs "${db_container}" >&2 || true
    fi

    if [[ "${KEEP_SMOKE_CONTAINERS:-0}" == "1" ]]; then
        echo "Smoke containers kept: ${web_container}, ${db_container}; network: ${network}" >&2
        return "${exit_status}"
    fi

    docker rm -f "${web_container}" "${db_container}" >/dev/null 2>&1 || true
    docker network rm "${network}" >/dev/null 2>&1 || true
    return "${exit_status}"
}
trap cleanup EXIT

docker network create "${network}" >/dev/null

docker run -d \
    --name "${db_container}" \
    --network "${network}" \
    -e MYSQL_DATABASE=joomla \
    -e MYSQL_USER=joomla \
    -e MYSQL_PASSWORD=joomla \
    -e MYSQL_ROOT_PASSWORD=root \
    "${mysql_image}" >/dev/null

mysql_ready=0
for _ in $(seq 1 60); do
    if docker exec -e MYSQL_PWD=root "${db_container}" mysqladmin ping -uroot --silent >/dev/null 2>&1; then
        mysql_ready=1
        break
    fi
    sleep 2
done

if [[ "${mysql_ready}" -ne 1 ]]; then
    echo "MySQL did not become ready within 120s - dumping container state for diagnosis:" >&2
    docker ps -a --filter "name=${db_container}" >&2 || true
    docker logs "${db_container}" >&2 || true
    exit 1
fi

docker run -d \
    --name "${web_container}" \
    --network "${network}" \
    -e JOOMLA_DB_HOST="${db_container}" \
    -e JOOMLA_DB_USER=joomla \
    -e JOOMLA_DB_PASSWORD=joomla \
    -e JOOMLA_DB_NAME=joomla \
    -e JOOMLA_SITE_NAME="BreezingForms NG Smoke Test" \
    -e JOOMLA_ADMIN_USER="Smoke Administrator" \
    -e JOOMLA_ADMIN_USERNAME=smokeadmin \
    -e JOOMLA_ADMIN_PASSWORD='Smoke-Test-123!' \
    -e JOOMLA_ADMIN_EMAIL=smoke@example.invalid \
    -e JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1 \
    "${joomla_image}" >/dev/null

joomla_ready=0
for _ in $(seq 1 90); do
    if docker exec "${web_container}" test -f /var/www/html/configuration.php; then
        joomla_ready=1
        break
    fi
    sleep 2
done

if [[ "${joomla_ready}" -ne 1 ]]; then
    echo "Joomla did not finish installing within 180s - dumping container state for diagnosis:" >&2
    docker ps -a --filter "name=${web_container}" >&2 || true
    docker logs "${web_container}" >&2 || true
    exit 1
fi

for _ in $(seq 1 60); do
    if docker exec "${web_container}" php -r '
        exit(@file_get_contents("http://127.0.0.1/index.php") === false ? 1 : 0);
    '; then
        break
    fi
    sleep 2
done

docker exec "${web_container}" php -r '
    exit(@file_get_contents("http://127.0.0.1/index.php") === false ? 1 : 0);
'

docker cp "${archive}" "${web_container}:${container_archive}" >/dev/null

if [[ -n "${contentbuilder_archive}" ]]; then
    contentbuilder_container_archive="/tmp/com_contentbuilderng.zip"
    docker cp "${contentbuilder_archive}" "${web_container}:${contentbuilder_container_archive}" >/dev/null
    docker exec -e HTTP_HOST=localhost "${web_container}" php /var/www/html/cli/joomla.php extension:install \
        --path="${contentbuilder_container_archive}" \
        --live-site=http://localhost \
        --quiet \
        --no-interaction
fi

docker exec -e HTTP_HOST=localhost "${web_container}" php /var/www/html/cli/joomla.php extension:install \
    --path="${container_archive}" \
    --live-site=http://localhost \
    --quiet \
    --no-interaction

for theme_file in themes/default/theme.css themes/aqua/theme.css; do
    if ! docker exec "${web_container}" test -f "/var/www/html/media/breezingforms/${theme_file}"; then
        echo "Installed package is missing /media/breezingforms/${theme_file}." >&2
        exit 1
    fi
done

table_prefix="$(
    docker exec -e HTTP_HOST=localhost "${web_container}" php -r '
        require "/var/www/html/configuration.php";
        $config = new JConfig();
        echo $config->dbprefix;
    '
)"

component_count="$(
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
        -e "SELECT COUNT(*) FROM \`${table_prefix}extensions\` WHERE type = 'component' AND element = 'com_breezingformsng';"
)"

if [[ "${component_count}" -ne 1 ]]; then
    echo "BreezingForms NG component registration was not found." >&2
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
        -e "SELECT extension_id, type, element, name FROM \`${table_prefix}extensions\` WHERE element LIKE '%breezingform%' OR name LIKE '%BreezingForms%';" >&2
    exit 1
fi

if [[ -n "${contentbuilder_archive}" ]]; then
    contentbuilder_count="$(
        docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
            -e "SELECT COUNT(*) FROM \`${table_prefix}extensions\` WHERE type = 'component' AND element = 'com_contentbuilderng';"
    )"

    if [[ "${contentbuilder_count}" -ne 1 ]]; then
        echo "ContentBuilder NG component registration was not found." >&2
        exit 1
    fi

    contentbuilder_forms_table_count="$(
        docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
            -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table_prefix}contentbuilderng_forms';"
    )"

    if [[ "${contentbuilder_forms_table_count}" -ne 1 ]]; then
        echo "ContentBuilder NG forms table was not installed correctly." >&2
        exit 1
    fi

    # Exercise the packaged BFNG ContentBuilder SQL loaders against the real
    # Joomla database API. The temporary row is removed before the smoke test
    # continues, so this does not alter the installed test fixture.
    docker exec "${web_container}" php -r '
        define("_JEXEC", 1);
        require "/var/www/html/includes/defines.php";
        require "/var/www/html/includes/framework.php";
        $container = \Joomla\CMS\Factory::getContainer();
        $container->alias("session.web", "session.web.site")
            ->alias("session", "session.web.site")
            ->alias("JSession", "session.web.site")
            ->alias(\Joomla\CMS\Session\Session::class, "session.web.site")
            ->alias(\Joomla\Session\Session::class, "session.web.site")
            ->alias(\Joomla\Session\SessionInterface::class, "session.web.site");
        $app = $container->get(\Joomla\CMS\Application\SiteApplication::class);
        \Joomla\CMS\Factory::$application = $app;
        require "/var/www/html/administrator/components/com_contentbuilderng/src/Helper/RuntimeContextHelper.php";
        require "/var/www/html/components/com_breezingformsng/src/Service/Rendering/ContentBuilderFormMetadataLoader.php";
        require "/var/www/html/components/com_breezingformsng/src/Service/Rendering/ContentBuilderRecordLoader.php";
        require "/var/www/html/components/com_breezingformsng/src/Service/Rendering/ContentBuilderHydrationScriptBuilder.php";
        require "/var/www/html/components/com_breezingformsng/src/Service/Rendering/ContentBuilderFileSupportBuilder.php";
        require "/var/www/html/components/com_breezingformsng/src/Service/Rendering/ContentBuilderSignatureImageEncoder.php";
        require "/var/www/html/components/com_breezingformsng/src/Support/processor_facade.php";

        $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $referenceId = 987654;
        $query = $db->getQuery(true)
            ->insert($db->quoteName("#__contentbuilderng_forms"))
            ->columns($db->quoteName(["type", "reference_id", "name", "published"]))
            ->values(implode(",", [
                $db->quote("com_breezingformsng"),
                $referenceId,
                $db->quote("BFNG smoke association"),
                1,
            ]));
        $db->setQuery($query);
        $db->execute();
        $formId = (int) $db->insertid();

        $sourceQuery = $db->getQuery(true)
            ->insert($db->quoteName("#__facileforms_forms"))
            ->columns($db->quoteName(["name", "title", "published", "template_code"]))
            ->values(implode(",", [
                $db->quote("BFNG smoke source"),
                $db->quote("BFNG smoke source"),
                1,
                $db->quote(""),
            ]));
        $db->setQuery($sourceQuery);
        $db->execute();
        $sourceId = (int) $db->insertid();

        try {
            $metadataLoader = new \Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderFormMetadataLoader($db);
            if (!in_array($formId, $metadataLoader->loadAssociatedFormIds($referenceId), true)) {
                throw new \RuntimeException("ContentBuilder association loader returned no inserted form");
            }
            $data = $metadataLoader->loadForm($formId);
            if (!is_array($data) || (int) ($data["reference_id"] ?? 0) !== $referenceId) {
                throw new \RuntimeException("ContentBuilder form data loader returned unexpected data");
            }

            $source = \CB\Component\Contentbuilderng\Administrator\Helper\FormSourceFactory::getForm(
                "com_breezingformsng",
                $sourceId
            );
            if (!is_object($source)) {
                throw new \RuntimeException("ContentBuilder source factory returned no BFNG form");
            }
            $recordLoader = new \Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderRecordLoader(
                static function (string $sourceReferenceId, int $recordId, bool $publishedOnly, int $ownerId, bool $allLanguages) use ($source): array {
                    return (array) $source->getRecord($recordId, $publishedOnly, $ownerId, $allLanguages);
                }
            );
            if ($recordLoader->load(
                [
                    "reference_id" => $sourceId,
                    "published_only" => 0,
                    "own_only_fe" => 0,
                    "show_all_languages_fe" => 1,
                ],
                0,
                true,
                0,
                true,
                "record not found"
            ) !== []) {
                throw new \RuntimeException("ContentBuilder record loader returned an unexpected new-record payload");
            }

            $signatureDirectory = "/var/www/html/media/breezingforms/signatures";
            if (!is_dir($signatureDirectory) && !mkdir($signatureDirectory, 0775, true) && !is_dir($signatureDirectory)) {
                throw new \RuntimeException("Unable to create the ContentBuilder signature directory");
            }
            $signatureFileName = "bfng-smoke-" . getmypid() . ".png";
            $signaturePath = $signatureDirectory . "/" . $signatureFileName;
            file_put_contents($signaturePath, "BFNG smoke signature");

            try {
                $fileRecord = (object) [
                    "recElementId" => 701,
                    "recName" => "documents",
                    "recType" => "File Upload",
                    "recValue" => "existing.pdf\r\nsecond.jpg",
                ];
                $signatureRecord = (object) [
                    "recElementId" => 702,
                    "recName" => "signature",
                    "recType" => "Signature",
                    "recValue" => $signatureFileName,
                ];
                $hydrationBuilder = new \Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ContentBuilderHydrationScriptBuilder(
                    static fn (string $value): string => $value,
                    static fn (string $value, int $width, string $break, bool $cut): string => wordwrap($value, $width, $break, $cut)
                );
                $rendered = $hydrationBuilder->buildEditable(
                    [$fileRecord, $signatureRecord],
                    [],
                    true,
                    7,
                    $signatureDirectory
                );
                if (!str_contains($rendered["contentBuilderScript"], "cbFlashElemCnt[\"ff_elem701\"] = 2;")) {
                    throw new \RuntimeException("ContentBuilder file upload count was not rendered");
                }
                if (!str_contains($rendered["javascript"], "existing.pdf")
                    || !str_contains($rendered["javascript"], "data:image")
                    || !str_contains($rendered["javascript"], base64_encode("BFNG smoke signature"))) {
                    throw new \RuntimeException("ContentBuilder file or signature hydration was not rendered");
                }
            } finally {
                if (is_file($signaturePath)) {
                    unlink($signaturePath);
                }
            }
        } finally {
            $db->setQuery($db->getQuery(true)
                ->delete($db->quoteName("#__contentbuilderng_forms"))
                ->where($db->quoteName("id") . " = " . $formId));
            $db->execute();
            $db->setQuery($db->getQuery(true)
                ->delete($db->quoteName("#__facileforms_forms"))
                ->where($db->quoteName("id") . " = " . $sourceId));
            $db->execute();
        }
    '
fi

plugin_count="$(
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
        -e "SELECT COUNT(*) FROM \`${table_prefix}extensions\` WHERE type = 'plugin' AND element = 'bfcompat' AND folder = 'system';"
)"

if [[ "${plugin_count}" -ne 1 ]]; then
    echo "The BreezingForms NG compatibility plugin was not installed correctly." >&2
    exit 1
fi

# Component tables are prefixed facileforms_ (carried over from the
# original FacileForms/BreezingForms naming), not breezingformsng_.
table_count="$(
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
        -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE '${table_prefix}facileforms_%';"
)"

if [[ "${table_count}" -lt 14 ]]; then
    echo "BreezingForms NG tables were not installed correctly: ${table_count} found (expected >= 14)." >&2
    exit 1
fi

# A real submission must survive a package update, not only the schema.
fixture_form_id="$(
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
        -e "INSERT INTO \`${table_prefix}facileforms_forms\` (name, title, published) VALUES ('bfng-smoke-update-form', 'BFNG smoke update form', 1); SELECT LAST_INSERT_ID();"
)"
fixture_record_id="$(
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
        -e "INSERT INTO \`${table_prefix}facileforms_records\` (submitted, form, title, name, browser) VALUES (UTC_TIMESTAMP(), ${fixture_form_id}, 'BFNG smoke update record', 'bfng-smoke-update-record', 'smoke'); SELECT LAST_INSERT_ID();"
)"

# Exercise the update path: installing the same package again over an
# existing install must succeed without errors and leave the same tables
# and registrations in place.
docker exec -e HTTP_HOST=localhost "${web_container}" php /var/www/html/cli/joomla.php extension:install \
    --path="${container_archive}" \
    --live-site=http://localhost \
    --quiet \
    --no-interaction

table_count_after_update="$(
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
        -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE '${table_prefix}facileforms_%';"
)"

if [[ "${table_count_after_update}" -ne "${table_count}" ]]; then
    echo "Table count changed after re-running the installer as an update: ${table_count} -> ${table_count_after_update}." >&2
    exit 1
fi

fixture_count_after_update="$(
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla \
        -e "SELECT COUNT(*) FROM \`${table_prefix}facileforms_records\` WHERE id = ${fixture_record_id} AND form = ${fixture_form_id} AND name = 'bfng-smoke-update-record';"
)"

if [[ "${fixture_count_after_update}" -ne 1 ]]; then
    echo "Record fixture was not preserved by the package update." >&2
    exit 1
fi

# Frontend sanity check: the site must still render after installation
# (catches a fatal error in the system plugin or a broken menu item).
frontend_status="$(
    docker exec "${web_container}" php -r '
        $context = stream_context_create(["http" => ["ignore_errors" => true]]);
        file_get_contents("http://127.0.0.1/index.php", false, $context);
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match("#^HTTP/\\S+\\s+(\\d+)#", $header, $m)) {
                echo $m[1];
                break;
            }
        }
    '
)"

if [[ "${frontend_status}" != "200" ]]; then
    echo "Frontend did not respond with HTTP 200 after installation (got: ${frontend_status:-<empty>})." >&2
    exit 1
fi

# Exercise the installed Composer autoloader through the component's loader.
# This catches packages that are present in source but missing from a clean
# Joomla installation, including Securimage and TCPDF.
docker exec "${web_container}" php -r '
    define("_JEXEC", 1);
    define("JPATH_ADMINISTRATOR", "/var/www/html/administrator");
    require "/var/www/html/administrator/components/com_breezingformsng/src/Helper/VendorHelper.php";
    \Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper::load();
    if (!class_exists("Securimage") || !class_exists("TCPDF")) {
        exit(1);
    }
    require "/var/www/html/administrator/components/com_breezingformsng/src/Service/PdfDocument.php";
    if (!class_exists("\\Vcmb\\Component\\BreezingformsNG\\Administrator\\Service\\PdfDocument")) {
        exit(1);
    }
    $pdf = new \Vcmb\Component\BreezingformsNG\Administrator\Service\PdfDocument();
    $pdf->AddPage();
    $pdf->Write(0, "BreezingForms NG smoke test");
    $pdfOutput = $pdf->Output("", "S");
    if (!str_starts_with($pdfOutput, "%PDF-")) {
        exit(1);
    }
    $captcha = new Securimage(["no_exit" => true, "send_headers" => false]);
    ob_start();
    $captcha->show();
    $image = ob_get_clean();
    exit(str_starts_with($image, "\x89PNG\r\n\x1a\n") ? 0 : 1);
'

echo "Joomla installation, update and frontend smoke tests passed."
