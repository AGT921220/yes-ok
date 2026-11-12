# Docker Setup - yes-ok

Laravel está en la raíz, con un solo `.env`. La guía vigente es `docs/docker.md` y `docs/setup.md`.



## Requisitos Previos

- Docker Engine 20.10+
- Docker Compose 2.0+

## Inicio Rápido

```bash
# 1. Levantar todos los servicios
docker compose up -d

# 2. Configurar entorno (si no existe .env)
cd backend
cp .env.example .env

# 3. Generar clave de aplicación
docker compose exec php php artisan key:generate

# 4. Ejecutar migraciones y seeders
docker compose exec php php artisan migrate --seed
```

¡Listo! El backend está funcionando en `http://localhost`

## Servicios

### Nginx
- **Puerto**: 80 (configurable con `PORT_NGINX`)
- **Container**: `nginx-yes-ok`
- **Función**: Reverse proxy para PHP-FPM

### PHP-FPM
- **Puerto**: 9000 (interno, mapeado a `PORT_PHP_MAPPING`)
- **Container**: `php-yes-ok`
- **Versión**: PHP 8.3
- **Extensiones**: pdo_mysql, mbstring, zip, gd, intl, redis, opcache

### MySQL
- **Puerto**: 3306 (configurable con `PORT_MYSQL`)
- **Container**: `mysql-yes-ok`
- **Versión**: MySQL 8.0
- **Credenciales**:
  - Database: `yes-ok`
  - Username: `yes-ok`
  - Password: `yes-ok123`
  - Root Password: `root123`

### Redis
- **Puerto**: 6379 (configurable con `PORT_REDIS`)
- **Container**: `redis-yes-ok`
- **Versión**: Redis 7
- **Uso**: Cache, Sessions, Queue

### Horizon
- **Container**: `horizon-yes-ok`
- **Función**: Procesador de colas Laravel
- **Comando**: `php artisan horizon`

### Scheduler
- **Container**: `scheduler-yes-ok`
- **Función**: Ejecuta tareas programadas
- **Comando**: `php artisan schedule:work`

## Variables de Entorno

Crea un archivo `.env.docker` en la raíz del proyecto (opcional):

```env
PORT_NGINX=80
PORT_MYSQL=3306
PORT_REDIS=6379
PORT_PHP_MAPPING=9000

APP_ENV=local
XDEBUG_MODE=off

DB_USERNAME=yes-ok
DB_PASSWORD=yes-ok123
DB_DATABASE=yes-ok
DB_ROOT_PASSWORD=root123
```

O configura directamente en `docker-compose.yml`.

## Comandos Comunes

### Levantar servicios
```bash
docker compose up -d
```

### Ver logs
```bash
# Todos
docker compose logs -f

# Específico
docker compose logs -f php
docker compose logs -f mysql
docker compose logs -f horizon
```

### Ejecutar comandos Artisan
```bash
docker compose exec php php artisan <comando>
```

### Acceder a contenedores
```bash
# PHP
docker compose exec php bash

# MySQL
docker compose exec mysql bash
docker compose exec mysql mysql -u yes-ok -pyes-ok123 yes-ok

# Redis
docker compose exec redis redis-cli
```

### Reiniciar servicios
```bash
docker compose restart
docker compose restart php
docker compose restart horizon
```

### Detener servicios
```bash
# Mantener volúmenes
docker compose down

# Eliminar volúmenes (¡CUIDADO!)
docker compose down -v
```

## Migraciones y Seeders

### Primera vez
```bash
docker compose exec php php artisan migrate --seed
```

### Solo migraciones
```bash
docker compose exec php php artisan migrate
```

### Solo seeders
```bash
docker compose exec php php artisan db:seed
```

### Seeders específicos
```bash
docker compose exec php php artisan db:seed --class=DatabaseSeeder
```

## Credenciales por Defecto

### Usuario Admin
- **Email**: `admin@yes-ok.com`
- **Password**: `admin123`

### MySQL
- **Host**: `mysql-yes-ok` (desde contenedores) o `localhost:3306` (desde host)
- **Database**: `yes-ok`
- **Username**: `yes-ok`
- **Password**: `yes-ok123`
- **Root Password**: `root123`

## Troubleshooting

### Error: "Connection refused" a MySQL

1. Verificar que MySQL esté saludable:
   ```bash
   docker compose ps mysql
   ```

2. Verificar logs:
   ```bash
   docker compose logs mysql
   ```

3. Verificar variables de entorno:
   ```bash
   docker compose exec php env | grep DB_
   ```

### Error de permisos en storage

```bash
docker compose exec php chmod -R 775 storage bootstrap/cache
docker compose exec php chown -R www-data:www-data storage bootstrap/cache
```

### Horizon no inicia

1. Ver logs:
   ```bash
   docker compose logs horizon
   ```

2. Verificar Redis:
   ```bash
   docker compose exec redis redis-cli ping
   ```

3. Reiniciar:
   ```bash
   docker compose restart horizon
   ```

### Limpiar todo y empezar de nuevo

```bash
# Detener y eliminar contenedores y volúmenes
docker compose down -v

# Reconstruir imágenes
docker compose build --no-cache

# Levantar de nuevo
docker compose up -d

# Configurar
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

## Healthchecks

Los servicios tienen healthchecks configurados:

- **MySQL**: `mysqladmin ping`
- **Redis**: `redis-cli ping`

Los servicios dependientes esperan a que los servicios base estén saludables antes de iniciar.

## Volúmenes

- `mysql_data`: Datos persistentes de MySQL
- `redis_data`: Datos persistentes de Redis

Los volúmenes persisten entre reinicios de contenedores.

## Redes

Todos los servicios están en la red `appnetwork` para comunicación interna.

## Staging

Para staging, usa:

```bash
docker compose -f docker-compose-staging.yml up -d
```

**Nota**: En staging, MySQL debe estar en un servicio externo (no incluido en compose).
