#!/bin/sh
set -e

mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/bootstrap/cache

chmod -R a+rwX /var/www/html/storage /var/www/html/bootstrap/cache

exec docker-php-entrypoint "$@"
