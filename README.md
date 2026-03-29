# Quiniela 2026 - Guía de Proyecto

Aplicación Laravel + Inertia/Vue para quiniela del Mundial 2026.

## Estructura del repositorio

- `src/`: código Laravel completo (app, rutas, recursos, tests, etc.)
- `docker/`: configuración de contenedores (`entrypoint.sh`, `php.ini`, `mysql/init.sql`)
- `Dockerfile`: imagen única para local y Railway
- `docker-compose.yml`: orquestación local
- `railway.json`: build/deploy para Railway

## Servicios locales automáticos

Con un solo `docker compose up`, se levantan automáticamente:

1. `app` (web Laravel) en `http://astrocopa.app.test`
2. `queue` (trabajos de cola)
3. `scheduler` (cron de Laravel cada 60s)
4. `reverb` (WebSocket para notificaciones push) en `ws://localhost:8081`
5. `redis` (cache/colas)

MySQL es opcional y se levanta con profile.

## Inicialización rápida (primera vez)

1. Crear archivo de entorno Docker:
```powershell
Copy-Item .env.docker.example .env.docker
```

2. Levantar todo el stack local:
```powershell
docker compose up --build -d
```

3. Ver logs en vivo:
```powershell
docker compose logs -f app queue scheduler reverb
```

4. (Opcional) correr seeders:
```powershell
docker compose exec app php artisan db:seed
```

Notas:

- `RUN_MIGRATIONS=true` viene activo en `.env.docker.example`, así que las migraciones se ejecutan automáticamente al iniciar `app`.
- Si usas SQLite, el `entrypoint` crea automáticamente el archivo de base de datos local.

## Doble modo (sin Docker y con Docker)

- Modo sin Docker usa `src/.env` (tu entorno local normal, por ejemplo MySQL en `127.0.0.1`).
- Modo Docker usa `.env.docker`.
- En Docker, `.env.docker` se monta como `/var/www/html/.env` dentro del contenedor para evitar conflictos con `src/.env`.

- php artisan serve --host=0.0.0.0 --port=8000
- npm run dev
- php artisan reverb:start --host=0.0.0.0 --port=8080
- php artisan queue:work
- php artisan schedule:work

## Domanins
-- App: http://astrocopa.app.test:8000
-- Reverb WS: ws://astrocopa.app.test:8080

## Activar MySQL local (opcional)

1. En `.env.docker`, configurar:

- `DB_CONNECTION=mysql`
- `DB_HOST=mysql`
- `DB_PORT=3306`
- `DB_DATABASE=quiniela`
- `DB_USERNAME=quiniela`
- `DB_PASSWORD=quiniela123`

2. Levantar con profile MySQL:
```powershell
docker compose --profile mysql up --build -d
```

## Laravel Reverb (push notifications)

Configuración local recomendada (ya incluida en `.env.docker.example`):

- Backend publica a Reverb interno:
  - `REVERB_HOST=reverb`
  - `REVERB_PORT=8080`
  - `REVERB_SCHEME=http`
- Frontend cliente conecta por navegador:
  - `VITE_REVERB_HOST=astrocopa.app.test`
  - `VITE_REVERB_PORT=8081`
  - `VITE_REVERB_SCHEME=http`

Comprobar que Reverb está arriba:
```powershell
docker compose logs -f reverb
```

## Comandos útiles

```powershell
# Subir stack local

docker compose up --build -d

# Bajar stack

docker compose down

# Reiniciar solo web

docker compose restart app

# Ejecutar comandos artisan

docker compose exec app php artisan route:list

# Ver estado de servicios

docker compose ps
```

## Deploy en Railway

Usa la misma imagen (`Dockerfile`) y crea servicios separados por rol:

1. `app-web` con `APP_RUNTIME_ROLE=web`
2. `app-queue` con `APP_RUNTIME_ROLE=queue`
3. `app-scheduler` con `APP_RUNTIME_ROLE=scheduler`
4. `app-reverb` con `APP_RUNTIME_ROLE=reverb` (si usarás push en producción)

Recomendado en Railway:

- DB y Redis gestionados por Railway (no contenedores propios en producción)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `RUN_MIGRATIONS=true` solo en la ventana de deploy del servicio web
