#!/bin/sh
set -e

php artisan config:cache
php artisan migrate --force
php artisan db:seed --force

exec frankenphp php-server --root public --listen :8000 --access-log
