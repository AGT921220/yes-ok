# Deploy y Docker (Horizon & Log Viewer)

Este proyecto usa un **solo Dockerfile** multi-stage para PHP (FPM + CLI) y docker-compose con servicios separados para:

- `php` (php-fpm, sirve Laravel para Nginx)
- `horizon` (cola con Laravel Horizon)
- `scheduler` (`php artisan schedule:work`)

Todos montan el backend en `/var/www/html`.

---

## 1. Rebuild / Restart de contenedores

```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```

Staging:

```bash
docker compose -f docker-compose-staging.yml down
docker compose -f docker-compose-staging.yml build --no-cache
docker compose -f docker-compose-staging.yml up -d
```

---

## 2. Servicios clave en docker-compose

### PHP (FPM)

Usa el target `fpm` del Dockerfile:

```yaml
php:
  build:
    context: .
    dockerfile: Dockerfile
    target: fpm
  volumes:
    - ./backend:/var/www/html
    - ./docker/php/php.ini:/usr/local/etc/php/php.ini
    - ./backend/.env:/var/www/html/.env
```

### Horizon

Usa el target `cli` y corre `php artisan horizon` como **proceso principal**:

```yaml
horizon:
  build:
    context: .
    dockerfile: Dockerfile
    target: cli
  container_name: horizon-yes-ok
  restart: always
  working_dir: /var/www/html
  command: php artisan horizon
  volumes:
    - ./backend:/var/www/html
    - ./backend/.env:/var/www/html/.env
  depends_on:
    redis:
      condition: service_healthy
```

### Scheduler

También usa el target `cli`:

```yaml
scheduler:
  build:
    context: .
    dockerfile: Dockerfile
    target: cli
  container_name: scheduler-yes-ok
  restart: always
  working_dir: /var/www/html
  command: php artisan schedule:work
  volumes:
    - ./backend:/var/www/html
    - ./backend/.env:/var/www/html/.env
  depends_on:
    redis:
      condition: service_healthy
    mysql:
      condition: service_healthy
```

> Importante: dentro de estos contenedores **no hay supervisor ni php-fpm**; el proceso principal es directamente `php artisan horizon` o `php artisan schedule:work`, lo que evita estados FATAL/flapping cuando un proceso hijo muere.

---

## 3. Diagnóstico de Horizon en Docker

Logs:

```bash
docker compose logs --tail=200 horizon
```

Estado del contenedor:

```bash
docker inspect -f 'Status={{.State.Status}} ExitCode={{.State.ExitCode}} RestartCount={{.RestartCount}}' horizon-yes-ok
```

Verificar que `pcntl` está habilitado:

```bash
docker exec -it horizon-yes-ok php -m | grep pcntl
```

Comandos útiles dentro del contenedor PHP/Horizon:

```bash
docker exec -it php-yes-ok php artisan horizon:status
docker exec -it php-yes-ok php artisan horizon:supervisors
docker exec -it php-yes-ok php artisan horizon:workload
```

---

## 4. Notas de deploy de backend

Dentro del contenedor `php-yes-ok`:

```bash
composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reinicio limpio de Horizon para que tome la nueva config/código
php artisan horizon:terminate
```

### Log Viewer (opcodesio/log-viewer)

- **Dashboard** `/log-viewer`: middleware `['web', 'auth:admin']` — requiere login admin por sesión.
- **API** `/log-viewer/api/*`: middleware `['logviewer.token', 'throttle:60,1']` — sin sesión; protegida por header `X-Log-Viewer-Token` (valor en `LOG_VIEWER_TOKEN`). El frontend recibe el token al cargar la página (solo si el admin está autenticado) y lo envía en cada petición API.

Variable de entorno obligatoria en producción:

```env
LOG_VIEWER_TOKEN=generar_un_token_largo_y_seguro
```

Generar un token seguro (ejemplo):

```bash
php -r "echo bin2hex(random_bytes(32));"
```

- Publicar **solo** assets:

```bash
php artisan vendor:publish --tag=log-viewer-assets --force
```

- **No** usar en producción:

```bash
php artisan vendor:publish --tag=log-viewer-config --force
```

para no pisar `config/log-viewer.php` personalizado.

Acceso:

- `/admin/login` → login admin (guard `admin`).
- `/horizon` y `/log-viewer` → requieren sesión admin en producción.

### Limpiar caches tras cambios de config/rutas

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
```

