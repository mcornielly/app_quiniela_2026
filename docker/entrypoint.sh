#!/usr/bin/env sh
set -eu

cd /var/www/html

ROLE="${APP_RUNTIME_ROLE:-${1:-web}}"

if [ "${RUN_MIGRATIONS:-false}" = "true" ] && [ "$ROLE" = "web" ]; then
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

