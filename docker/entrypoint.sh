#!/bin/sh
# =============================================================================
# entrypoint.sh
# Arranque del contenedor Docker para Laravel + Vue
# =============================================================================

# Falla si hay errores o variables no definidas
set -eu

# Ir a la raíz del proyecto
cd /var/www/html

# Detectar rol del contenedor
# Prioridad:
# 1) APP_RUNTIME_ROLE
# 2) Primer argumento
# 3) "web" por defecto
ROLE="${APP_RUNTIME_ROLE:-}"
if [ -z "$ROLE" ]; then
  ROLE="${1:-web}"
fi

# Crear archivo SQLite si aplica
if [ "${DB_CONNECTION:-}" = "sqlite" ] && [ -n "${DB_DATABASE:-}" ]; then
  DB_DIR=$(dirname "$DB_DATABASE")
  mkdir -p "$DB_DIR"
  [ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"
fi

# Ejecutar setup inicial solo en rol web si está habilitado
if [ "${RUN_MIGRATIONS:-false}" = "true" ] && [ "$ROLE" = "web" ]; then
  php artisan storage:link --force || true
  php artisan migrate --force
fi

# ✅ Limpia caché de config, rutas, vistas y eventos — corre para TODOS los roles
echo ">>> [entrypoint] Limpiando caché de Laravel..."
php artisan optimize:clear
echo ">>> [entrypoint] Caché limpiada correctamente."

# Ejecutar proceso según el rol
case "$ROLE" in

  # Servidor web + Vite opcional
  web)
    if [ "${ENABLE_VITE_DEV_SERVER:-true}" = "true" ]; then
      # Instalar dependencias si Vite no existe
      if [ ! -x node_modules/.bin/vite ]; then
        npm ci
      fi

      # Iniciar Vite en background
      npm run dev -- --host=0.0.0.0 --port="${VITE_PORT:-5173}" &
      VITE_PID=$!
    else
      VITE_PID=""
    fi

    # Iniciar servidor Laravel
    php artisan serve --host=0.0.0.0 --port="${PORT:-8080}" &
    WEB_PID=$!

    # Cerrar procesos al detener el contenedor
    cleanup() {
      [ -n "${VITE_PID:-}" ] && kill "$VITE_PID" 2>/dev/null || true
      kill "$WEB_PID" 2>/dev/null || true
    }

    trap cleanup INT TERM

    # Mantener vivo el contenedor mientras Laravel siga corriendo
    wait "$WEB_PID"
    ;;

  # Worker de cola
  queue)
    exec php artisan queue:work \
      --tries="${QUEUE_TRIES:-3}" \
      --timeout="${QUEUE_TIMEOUT:-120}" \
      --sleep="${QUEUE_SLEEP:-1}"
    ;;

  # Scheduler en loop
  scheduler)
    while true; do
      php artisan schedule:run --no-interaction --verbose
      sleep "${SCHEDULER_SLEEP:-60}"
    done
    ;;

  # Servidor Reverb
  reverb)
    exec php artisan reverb:start --host=0.0.0.0 --port="${PORT:-8080}"
    ;;

  # Rol no válido
  *)
    echo "Unknown APP_RUNTIME_ROLE: $ROLE"
    echo "Allowed values: web | queue | scheduler | reverb"
    exit 1
    ;;
esac
