#!/usr/bin/env bash
# Installs the package on a throwaway Joomla and runs the browser journeys on it.

set -euo pipefail

archive="${1:?Usage: scripts/joomla-e2e.sh path/to/package.zip}"
joomla_image="${JOOMLA_IMAGE:-joomla:6.2.0-php8.3-apache}"
mysql_image="${MYSQL_IMAGE:-mysql:8.4}"
port="${BFNG_E2E_PORT:-18080}"
run_id="${GITHUB_RUN_ID:-local}-$$"
network="bfng-e2e-${run_id}"
db_container="bfng-e2e-db-${run_id}"
web_container="bfng-e2e-web-${run_id}"
admin_password='E2e-Test-123!'

cleanup() {
    local exit_status=$?

    if [[ "${exit_status}" -ne 0 ]]; then
        docker logs --tail 40 "${web_container}" >&2 || true
        docker exec "${web_container}" sh -c '
            for log in /var/www/html/administrator/logs/*.php; do
                [ -f "${log}" ] || continue
                echo "===== ${log} (last 40 lines)"
                tail -n 40 "${log}"
            done
        ' >&2 || true
    fi

    if [[ "${KEEP_E2E_CONTAINERS:-0}" == "1" ]]; then
        echo "E2E containers kept: ${web_container}, ${db_container}; network: ${network}" >&2
        return "${exit_status}"
    fi

    docker rm -f "${web_container}" "${db_container}" >/dev/null 2>&1 || true
    docker network rm "${network}" >/dev/null 2>&1 || true
    return "${exit_status}"
}
trap cleanup EXIT

docker network create "${network}" >/dev/null

docker run -d --name "${db_container}" --network "${network}" \
    -e MYSQL_DATABASE=joomla -e MYSQL_USER=joomla -e MYSQL_PASSWORD=joomla -e MYSQL_ROOT_PASSWORD=root \
    "${mysql_image}" >/dev/null

for _ in $(seq 1 60); do
    docker exec -e MYSQL_PWD=root "${db_container}" mysqladmin ping -uroot --silent >/dev/null 2>&1 && break
    sleep 2
done

docker run -d --name "${web_container}" --network "${network}" -p "127.0.0.1:${port}:80" \
    -e JOOMLA_DB_HOST="${db_container}" -e JOOMLA_DB_USER=joomla -e JOOMLA_DB_PASSWORD=joomla -e JOOMLA_DB_NAME=joomla \
    -e JOOMLA_SITE_NAME="BreezingForms NG E2E" -e JOOMLA_ADMIN_USER="E2E Administrator" -e JOOMLA_ADMIN_USERNAME=journeyadmin \
    -e JOOMLA_ADMIN_PASSWORD="${admin_password}" -e JOOMLA_ADMIN_EMAIL=e2e@example.invalid \
    -e JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1 \
    "${joomla_image}" >/dev/null

for _ in $(seq 1 90); do
    docker exec "${web_container}" test -f /var/www/html/configuration.php && break
    sleep 2
done

docker exec "${web_container}" test -f /var/www/html/configuration.php

for _ in $(seq 1 60); do
    docker exec "${web_container}" php -r 'exit(@file_get_contents("http://127.0.0.1/index.php") === false ? 1 : 0);' && break
    sleep 2
done

docker cp "${archive}" "${web_container}:/tmp/com_breezingformsng.zip" >/dev/null
docker exec -e HTTP_HOST=localhost "${web_container}" php /var/www/html/cli/joomla.php extension:install \
    --path=/tmp/com_breezingformsng.zip --live-site="http://localhost:${port}" --no-interaction

# PHP notices shown on the page, like on a site in development: a save must survive them.
docker exec "${web_container}" sed -i \
    -e "s/public \$error_reporting = '[^']*'/public \$error_reporting = 'maximum'/" \
    -e "s/public \$mailonline = [^;]*;/public \$mailonline = false;/" \
    /var/www/html/configuration.php
docker exec "${web_container}" chown www-data:www-data /var/www/html/configuration.php

if [[ "${E2E_SETUP_ONLY:-0}" == "1" ]]; then
    echo "E2E site ready: http://localhost:${port}/, container ${web_container}"
    exit 0
fi

BFNG_E2E_URL="http://localhost:${port}/" BFNG_E2E_CONTAINER="${web_container}" \
BFNG_E2E_USER=journeyadmin BFNG_E2E_PASSWORD="${admin_password}" \
    npx playwright test --config=playwright.e2e.config.mjs
