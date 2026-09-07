#!/usr/bin/env bash
#
# ParishHub API — cPanel update script.
# Run from the app root (e.g. ~/parishhub-api) after the first-time setup
# in DEPLOYMENT.md. Usage:  bash scripts/deploy.sh
set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"

echo "==> Entering maintenance mode"
$PHP_BIN artisan down || true

echo "==> Pulling latest code"
git pull --ff-only

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running migrations"
$PHP_BIN artisan migrate --force

echo "==> Rebuilding caches"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

echo "==> Leaving maintenance mode"
$PHP_BIN artisan up

echo "==> Done. $($PHP_BIN artisan --version)"
