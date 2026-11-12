# Instalación

Requisitos: Docker y Docker Compose.

```bash
cp .env.example .env
docker compose up -d
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
docker compose exec php php artisan admin:create
```

## Acceso local

- Panel: `http://127.0.0.1:8040` si `PORT_NGINX=8040`
- API: `http://127.0.0.1:8040/api/v1`
- phpMyAdmin: puerto `PORT_PHPMYADMIN`

El administrador lo define el `.env`:

```env
ADMIN_NAME=Administrador
ADMIN_EMAIL=admin@admin.com
ADMIN_PASSWORD=admin
```

`php artisan admin:create` crea o actualiza ese usuario con rol `ADMIN`.

## Base de datos dentro de Docker

Compose fuerza `DB_HOST=mysql` y `REDIS_HOST=redis` en PHP, Horizon y el scheduler. El nombre de la base, el usuario y la contraseña salen del `.env` de la raíz.

## Assets del panel

El panel funciona con el CSS de Tabler por CDN. `npm run build` solo hace falta si se quiere el build de Vite.
