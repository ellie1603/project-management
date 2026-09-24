#!/bin/sh
set -e

cd /var/www/html

artisan() {
    su-exec www-data php artisan "$@"
}

# The storage volume starts empty on first deploy: recreate Laravel's layout
# and make sure PHP (www-data) can write to it.
mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] APP_KEY is not set. Generate one locally with: php artisan key:generate --show" >&2
    exit 1
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    attempt=1
    until artisan migrate --force; do
        if [ "$attempt" -ge 30 ]; then
            echo "[entrypoint] Database still unreachable after $attempt attempts; giving up." >&2
            exit 1
        fi
        echo "[entrypoint] Waiting for the database (attempt $attempt)..."
        attempt=$((attempt + 1))
        sleep 2
    done
fi

# Load the development data set only into an empty database, so a redeploy
# never resets accounts or passwords.
if [ "$SEED_ON_FIRST_DEPLOY" = "true" ]; then
    users=$(artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n 1)
    if [ "$users" = "0" ]; then
        echo "[entrypoint] Empty database detected; running seeders."
        artisan db:seed --force
    else
        echo "[entrypoint] Database already has users; skipping seeders."
    fi
fi

# Cache config, routes, events, and views using the runtime environment.
artisan optimize

exec "$@"
