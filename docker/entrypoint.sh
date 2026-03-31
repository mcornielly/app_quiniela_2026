#!/bin/sh
set -eu

cd /var/www/html

ROLE="${APP_RUNTIME_ROLE:-}"
if [ -z "$ROLE" ]; then
  ROLE="${1:-web}"
fi

if [ "${DB_CONNECTION:-}" = "sqlite" ] && [ -n "${DB_DATABASE:-}" ]; then
  DB_DIR=$(dirname "$DB_DATABASE")
  mkdir -p "$DB_DIR"
  [ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ] && [ "$ROLE" = "web" ]; then
  php artisan storage:link --force || true
  php artisan migrate --force
fi

case "$ROLE" in
  web)
    if [ "${ENABLE_VITE_DEV_SERVER:-true}" = "true" ]; then
      if [ ! -x node_modules/.bin/vite ]; then
        npm ci
      fi

      npm run dev -- --host=0.0.0.0 --port="${VITE_PORT:-5173}" &
      VITE_PID=$!
    else
      VITE_PID=""
    fi

    php artisan serve --host=0.0.0.0 --port="${PORT:-8080}" &
    WEB_PID=$!

    cleanup() {
      [ -n "${VITE_PID:-}" ] && kill "$VITE_PID" 2>/dev/null || true
      kill "$WEB_PID" 2>/dev/null || true
    }

    trap cleanup INT TERM
    wait "$WEB_PID"
    ;;
  queue)
    exec php artisan queue:work --tries="${QUEUE_TRIES:-3}" --timeout="${QUEUE_TIMEOUT:-120}" --sleep="${QUEUE_SLEEP:-1}"
    ;;
  scheduler)
    while true; do
      php artisan schedule:run --no-interaction --verbose
      sleep "${SCHEDULER_SLEEP:-60}"
    done
    ;;
  reverb)
    exec php artisan reverb:start --host=0.0.0.0 --port="${PORT:-8080}"
    ;;
  *)
    echo "Unknown APP_RUNTIME_ROLE: $ROLE"
    echo "Allowed values: web | queue | scheduler | reverb"
    exit 1
    ;;
esac
