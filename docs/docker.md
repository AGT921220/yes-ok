# Docker

Compose local (`docker-compose.yml`):

- `nginx-yes-ok` publica `PORT_NGINX` (80 dentro del contenedor) y sirve `public/`
- `php-yes-ok` PHP 8.3 FPM
- `mysql-yes-ok` MySQL 8
- `redis-yes-ok`
- `horizon-yes-ok`
- `scheduler-yes-ok`
- `phpmyadmin-yes-ok` en `PORT_PHPMYADMIN`

El código y el `.env` se montan desde la raíz del repo (`.:/var/www/html`). Nginx usa `docker/nginx/default.conf` y `fastcgi_pass php-yes-ok:9000`.

```bash
docker compose up -d
docker compose exec php php artisan migrate
docker compose exec php php artisan config:clear
```

Staging: `docker-compose-staging.yml`. Ahí el PHP se llama `php-yes-ok` o `php-yes-ok-staging` según el archivo, y Nginx apunta a ese nombre.
