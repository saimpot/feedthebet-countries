#!/bin/sh
set -e

cd /var/www/backend

if [ "${SKIP_CACHE_BOOT:-0}" != "1" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache
    # Best effort: an unreachable upstream must not prevent the app from starting.
    php artisan countries:warm >/dev/null 2>&1 || echo "countries:warm skipped (non-fatal)"
fi

exec "$@"
