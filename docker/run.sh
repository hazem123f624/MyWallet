#!/bin/sh

cd /var/www
nginx -t || exit 1

php artisan cache:clear
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force
php artisan migrate --seed --force
php artisan schedule:work &


exec /usr/bin/supervisord -n -c /etc/supervisord.conf

