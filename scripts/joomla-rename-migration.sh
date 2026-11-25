#!/usr/bin/env bash
# Updates a site from a package that still names the site module mod_breezingforms and the content plugin
# content/breezingforms (6.1.0) to this one (mod_breezingformsng, content/breezingformsng) on a throwaway
# Joomla, and checks that nothing the administrator set is lost.

set -euo pipefail

old_archive="${1:?Usage: scripts/joomla-rename-migration.sh old-package.zip new-package.zip}"
new_archive="${2:?Usage: scripts/joomla-rename-migration.sh old-package.zip new-package.zip}"
joomla_image="${JOOMLA_IMAGE:-joomla:6.2.0-php8.4-apache}"
mysql_image="${MYSQL_IMAGE:-mysql:8.4}"
run_id="${GITHUB_RUN_ID:-local}-$$"
network="bfng-rename-${run_id}"
db_container="bfng-rename-db-${run_id}"
web_container="bfng-rename-web-${run_id}"

cleanup() {
    local exit_status=$?

    if [[ "${exit_status}" -ne 0 ]]; then
        docker logs --tail 40 "${web_container}" >&2 || true
        docker exec "${web_container}" sh -c 'for log in /var/www/html/administrator/logs/*.php; do [ -f "${log}" ] && tail -n 40 "${log}"; done' >&2 || true
    fi

    if [[ "${KEEP_RENAME_CONTAINERS:-0}" == "1" ]]; then
        echo "Containers kept: ${web_container}, ${db_container}" >&2
        return "${exit_status}"
    fi

    docker rm -f "${web_container}" "${db_container}" >/dev/null 2>&1 || true
    docker network rm "${network}" >/dev/null 2>&1 || true
    return "${exit_status}"
}
trap cleanup EXIT

sql() {
    docker exec -e MYSQL_PWD=joomla "${db_container}" mysql -N -ujoomla joomla -e "$1"
}

install_package() {
    docker cp "$1" "${web_container}:/tmp/package.zip" >/dev/null
    docker exec -e HTTP_HOST=localhost "${web_container}" php /var/www/html/cli/joomla.php extension:install \
        --path=/tmp/package.zip --live-site="http://localhost" --no-interaction
}

docker network create "${network}" >/dev/null
docker run -d --name "${db_container}" --network "${network}" \
    -e MYSQL_DATABASE=joomla -e MYSQL_USER=joomla -e MYSQL_PASSWORD=joomla -e MYSQL_ROOT_PASSWORD=root \
    "${mysql_image}" >/dev/null
for _ in $(seq 1 60); do
    docker exec -e MYSQL_PWD=root "${db_container}" mysqladmin ping -uroot --silent >/dev/null 2>&1 && break
    sleep 2
done
docker run -d --name "${web_container}" --network "${network}" \
    -e JOOMLA_DB_HOST="${db_container}" -e JOOMLA_DB_USER=joomla -e JOOMLA_DB_PASSWORD=joomla -e JOOMLA_DB_NAME=joomla \
    -e JOOMLA_SITE_NAME="BreezingForms NG rename" -e JOOMLA_ADMIN_USER="Admin" -e JOOMLA_ADMIN_USERNAME=renameadmin \
    -e JOOMLA_ADMIN_PASSWORD='Rename-Test-123!' -e JOOMLA_ADMIN_EMAIL=rename@example.invalid \
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

prefix="$(docker exec "${web_container}" php -r 'require "/var/www/html/configuration.php"; echo (new JConfig())->dbprefix;')"
[[ "${prefix}" =~ ^[a-zA-Z0-9_]+$ ]]

# 1. The package that carries the old names, with what an administrator sets on them.
install_package "${old_archive}"
[[ "$(sql "SELECT COUNT(*) FROM \`${prefix}extensions\` WHERE type='module' AND element='mod_breezingforms'")" == 1 ]]
[[ "$(sql "SELECT COUNT(*) FROM \`${prefix}extensions\` WHERE type='plugin' AND folder='content' AND element='breezingforms'")" == 1 ]]
sql "INSERT INTO \`${prefix}facileforms_forms\` (name, title, published, template_code_processed, template_code) VALUES ('kept-form', 'Kept form', 1, 'QuickMode', 'eyJhdHRyaWJ1dGVzIjogeyJpZCI6ICJiZlF1aWNrTW9kZVJvb3QifSwgInByb3BlcnRpZXMiOiB7InR5cGUiOiAicm9vdCIsICJ0aXRsZSI6ICJTbW9rZSBmb3JtIiwgIm5hbWUiOiAiYmZuZy1zbW9rZS11cGRhdGUtZm9ybSIsICJyb2xsb3ZlciI6IGZhbHNlLCAicm9sbG92ZXJDb2xvciI6ICIjZmZjIiwgInRvZ2dsZUZpZWxkcyI6ICIiLCAiZGVzY3JpcHRpb24iOiAiIiwgIm1haWxOb3RpZmljYXRpb24iOiBmYWxzZSwgIm1haWxSZWNpcGllbnQiOiAiIiwgInN1Ym1pdEluY2x1ZGUiOiB0cnVlLCAic3VibWl0TGFiZWwiOiAiU3VibWl0IiwgImNhbmNlbEluY2x1ZGUiOiBmYWxzZSwgImNhbmNlbExhYmVsIjogIlJlc2V0IiwgInBhZ2luZ0luY2x1ZGUiOiB0cnVlLCAicGFnaW5nTmV4dExhYmVsIjogIk5leHQiLCAicGFnaW5nUHJldkxhYmVsIjogIkJhY2siLCAidGhlbWUiOiAiZGVmYXVsdCIsICJ0aGVtZWJvb3RzdHJhcCI6ICIiLCAidGhlbWVib290c3RyYXBiZWZvcmUiOiAiIiwgInRoZW1lYm9vdHN0cmFwTGFiZWxUb3AiOiBmYWxzZSwgInRoZW1lYm9vdHN0cmFwVGhlbWVFbmdpbmUiOiAiYm9vdHN0cmFwIiwgInRoZW1lYm9vdHN0cmFwVXNlSGVyb1VuaXQiOiBmYWxzZSwgInRoZW1lYm9vdHN0cmFwVXNlV2VsbCI6IGZhbHNlLCAidGhlbWVib290c3RyYXBVc2VQcm9ncmVzcyI6IGZhbHNlLCAiZmFkZUluIjogZmFsc2UsICJsYXN0UGFnZVRoYW5rWW91IjogZmFsc2UsICJzdWJtaXR0ZWRTY3JpcHRDb25kaWR0aW9uIjogMCwgInN1Ym1pdHRlZFNjcmlwdENvZGUiOiAiIiwgInVzZUVycm9yQWxlcnRzIjogZmFsc2UsICJ1c2VEZWZhdWx0RXJyb3JzIjogdHJ1ZSwgInVzZUJhbGxvb25FcnJvcnMiOiBmYWxzZSwgImpvb21sYUhpbnQiOiBmYWxzZX0sICJjaGlsZHJlbiI6IFtdfQ==')"
sql "INSERT INTO \`${prefix}modules\` (title, position, published, module, access, showtitle, params, client_id, language) VALUES ('Rename keeps me', 'sidebar-right', 1, 'mod_breezingforms', 1, 0, '{\"ff_mod_name\":\"kept-form\",\"cache\":0}', 0, '*')"
instance_id="$(sql "SELECT id FROM \`${prefix}modules\` WHERE title='Rename keeps me'")"
sql "INSERT INTO \`${prefix}modules_menu\` (moduleid, menuid) VALUES (${instance_id}, 0)"
# The site cache is on and warm: the module list it holds names the old module.
docker exec "${web_container}" sed -i "s/public \$caching = [0-9];/public \$caching = 1;/" /var/www/html/configuration.php
for _ in 1 2; do
    docker exec "${web_container}" php -r 'file_get_contents("http://127.0.0.1/index.php");'
done
[[ "$(docker exec "${web_container}" sh -c 'grep -c "caching = 1" /var/www/html/configuration.php')" == 1 ]]

# The administrator turned the content plugin off and gave it a position in the order.
sql "UPDATE \`${prefix}extensions\` SET enabled = 0, ordering = 7, access = 2 WHERE type='plugin' AND folder='content' AND element='breezingforms'"

# 2. The package of this version, installed over it (an update), then once more (idempotence).
install_package "${new_archive}"
install_package "${new_archive}"

# The new registrations exist, the old ones are gone, each once.
[[ "$(sql "SELECT COUNT(*) FROM \`${prefix}extensions\` WHERE type='module' AND element='mod_breezingformsng' AND client_id=0")" == 1 ]]
[[ "$(sql "SELECT COUNT(*) FROM \`${prefix}extensions\` WHERE type='plugin' AND folder='content' AND element='breezingformsng'")" == 1 ]]
[[ "$(sql "SELECT COUNT(*) FROM \`${prefix}extensions\` WHERE (type='module' AND element='mod_breezingforms') OR (type='plugin' AND folder='content' AND element='breezingforms')")" == 0 ]]

# The placeholder instance Joomla makes at each module installation is not doubled.
[[ "$(sql "SELECT COUNT(*) FROM \`${prefix}modules\` WHERE module='mod_breezingformsng' AND position='' AND published=0")" == 1 ]]

# The module instance kept its place, its parameters and its menu assignment, and now uses the new module.
[[ "$(sql "SELECT CONCAT(module,'|',position,'|',published,'|',params) FROM \`${prefix}modules\` WHERE id=${instance_id}")" == 'mod_breezingformsng|sidebar-right|1|{"ff_mod_name":"kept-form","cache":0}' ]]
[[ "$(sql "SELECT COUNT(*) FROM \`${prefix}modules_menu\` WHERE moduleid=${instance_id} AND menuid=0")" == 1 ]]

# ... and it still shows its form in the site: the renamed module works, not only its registration.
docker exec "${web_container}" php -r '
    $html = file_get_contents("http://127.0.0.1/index.php", false, stream_context_create(["http" => ["ignore_errors" => true]]));
    if ($html === false || !str_contains($html, "name=\"ff_form\"")) {
        fwrite(STDERR, "The renamed module does not show its form.\n"); exit(1);
    }
'

# The language files of the old paths are no longer in Joomla's language cache.
[[ "$(docker exec "${web_container}" sh -c 'ls /var/www/html/administrator/cache/language 2>/dev/null | grep -c -E "mod_breezingforms[-.]|plugins-content-breezingforms-|plg_content_breezingforms[-.]"')" == 0 ]]

# The plugin stayed off, with its order and access level.
[[ "$(sql "SELECT CONCAT(enabled,'|',ordering,'|',access) FROM \`${prefix}extensions\` WHERE type='plugin' AND folder='content' AND element='breezingformsng'")" == '0|7|2' ]]

# The old files are gone, the new ones are there.
docker exec "${web_container}" sh -c '
    test ! -e /var/www/html/modules/mod_breezingforms &&
    test ! -e /var/www/html/plugins/content/breezingforms &&
    test ! -e /var/www/html/administrator/components/com_breezingformsng/modules/mod_breezingforms &&
    test ! -e /var/www/html/administrator/components/com_breezingformsng/plugins/breezingforms &&
    test -f /var/www/html/modules/mod_breezingformsng/mod_breezingformsng.xml &&
    test -f /var/www/html/plugins/content/breezingformsng/breezingformsng.xml'

echo "The module and the content plugin were renamed by the update without losing their settings."
