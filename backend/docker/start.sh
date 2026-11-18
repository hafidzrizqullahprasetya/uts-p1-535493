#!/bin/sh
set -e

php artisan config:cache
php artisan migrate --force
php artisan db:seed --force

exec frankenphp run --config docker/Caddyfile
