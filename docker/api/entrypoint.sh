#!/usr/bin/env bash
set -e

cd /app

if [ ! -f composer.json ]; then
    echo "ERROR: /app/composer.json not found — is the api/ directory bind-mounted?"
    exit 1
fi

echo "==> Installing Composer dependencies (slow on first boot, fast after)"
composer install --no-interaction --no-progress --prefer-dist

echo "==> Running database migrations"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "==> Warming Symfony cache (eliminates cold-cache penalty on first request)"
php bin/console cache:warmup

echo "==> Starting nginx (background)"
nginx

echo "==> Starting crunz scheduler loop (background)"
(
    while true; do
        vendor/bin/crunz schedule:run || echo "[crunz] non-zero exit; continuing"
        sleep 60
    done
) &

echo "==> Starting php-fpm (foreground)"
exec php-fpm -F
