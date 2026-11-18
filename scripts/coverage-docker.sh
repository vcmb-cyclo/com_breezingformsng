#!/usr/bin/env bash
# Local line coverage with pcov in a throwaway PHP 8.3 image (GD + pcov); the host PHP has no
# coverage driver. Usage: scripts/coverage-docker.sh [phpunit args]

set -euo pipefail

cd "$(dirname "$0")/.."
mkdir -p coverage

docker build -q -t bfng-php-cov:8.3 - >/dev/null <<'DOCKERFILE'
FROM php:8.3-cli
RUN apt-get update -qq && apt-get install -y -qq libpng-dev libjpeg-dev libfreetype-dev >/dev/null \
 && docker-php-ext-configure gd --with-jpeg --with-freetype >/dev/null && docker-php-ext-install -j4 gd >/dev/null \
 && pecl install pcov >/dev/null && docker-php-ext-enable pcov
DOCKERFILE

docker run --rm --user "$(id -u):$(id -g)" -v "$PWD":/app -w /app bfng-php-cov:8.3 \
    php vendor/bin/phpunit --no-progress --coverage-clover coverage/clover.xml --coverage-text "$@"

php scripts/check-coverage.php coverage/clover.xml 30
