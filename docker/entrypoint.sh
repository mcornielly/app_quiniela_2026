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
    exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
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
