#!/usr/bin/env bash
# One-command update for WC2026 — run in the cPanel Terminal:  bash deploy.sh
set -e
cd "$(dirname "$0")"
export PATH="/opt/cpanel/composer/bin:/usr/local/bin:$PATH"   # make composer/php visible

echo "==> git pull";   git pull origin main
echo "==> composer";   composer install --no-dev --optimize-autoloader --no-interaction
echo "==> migrate";    php artisan migrate --force
echo "==> clear";      php artisan view:clear && php artisan config:clear && php artisan route:clear
echo "==> DONE ✅"
