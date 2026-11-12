# yes-ok Backend - Laravel

Backend Laravel para el sistema yes-ok de gestión de reservas y disponibilidad.

## Requisitos

- Docker y Docker Compose
- Git

## Inicio Rápido

### 1. Levantar servicios Docker

```bash
docker compose up -d
```

Esto levantará:
- **nginx**: Reverse proxy (puerto 80)
- **php**: PHP-FPM 8.3
- **mysql**: MySQL 8.0 (puerto 3306)
- **redis**: Redis 7 (puerto 6379)
- **horizon**: Laravel Horizon para colas
- **scheduler**: Laravel Scheduler

### 2. Configurar entorno

```bash
# Copiar archivo de entorno
cp .env.example .env

# Generar clave de aplicación
docker compose exec php php artisan key:generate
```

### 3. Ejecutar migraciones y seeders

```bash
# Migrar base de datos
docker compose exec php php artisan migrate

# Poblar con datos iniciales (incluye usuario admin)
docker compose exec php php artisan db:seed
```

### 4. Verificar servicios

```bash
# Ver logs
docker compose logs -f

# Ver estado de servicios
docker compose ps

# Verificar Horizon
docker compose logs horizon
```

## Credenciales por Defecto

**Usuario Admin:**
- Email: `admin@yes-ok.com`
- Password: `admin123`

**Base de Datos MySQL:**
- Host: `mysql-yes-ok` (desde contenedores) o `localhost:3306` (desde host)
- Database: `yes-ok`
- Username: `yes-ok`
- Password: `yes-ok123`
- Root Password: `root123`

## Comandos Útiles

### Artisan

```bash
# Ejecutar cualquier comando artisan
docker compose exec php php artisan <comando>

# Ejemplos:
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed
docker compose exec php php artisan route:list
docker compose exec php php artisan tinker
```

### Acceder a contenedores

```bash
# Entrar al contenedor PHP
docker compose exec php bash

# Entrar a MySQL
docker compose exec mysql mysql -u yes-ok -pyes-ok123 yes-ok

# Entrar a Redis
docker compose exec redis redis-cli
```

### Reiniciar servicios

```bash
# Reiniciar todos los servicios
docker compose restart

# Reiniciar un servicio específico
docker compose restart php
docker compose restart horizon
```

### Detener servicios

```bash
# Detener servicios (mantiene volúmenes)
docker compose down

# Detener y eliminar volúmenes (¡CUIDADO! Elimina datos)
docker compose down -v
```

## Estructura del Proyecto

```
backend/
├── app/
│   ├── Features/          # Arquitectura por Features
│   │   ├── Auth/
│   │   ├── Users/
│   │   └── ...
│   ├── Http/
│   │   └── Controllers/
│   └── Models/
├── database/
│   ├── factories/         # Factories para datos de prueba
│   ├── migrations/        # Migraciones de base de datos
│   └── seeders/          # Seeders
├── routes/
│   └── api.php           # Rutas API
└── storage/              # Archivos de almacenamiento
```

## Desarrollo

### Agregar nuevas migraciones

```bash
docker compose exec php php artisan make:migration create_table_name
docker compose exec php php artisan migrate
```

### Agregar nuevos seeders

```bash
docker compose exec php php artisan make:seeder SeederName
docker compose exec php php artisan db:seed --class=SeederName
```

### Ver logs en tiempo real

```bash
# Todos los servicios
docker compose logs -f

# Servicio específico
docker compose logs -f php
docker compose logs -f horizon
```

## Troubleshooting

### Error de permisos en storage

```bash
docker compose exec php chmod -R 775 storage bootstrap/cache
docker compose exec php chown -R www-data:www-data storage bootstrap/cache
```

### MySQL no conecta

```bash
# Verificar que MySQL esté saludable
docker compose ps mysql

# Ver logs de MySQL
docker compose logs mysql

# Verificar variables de entorno
docker compose exec php env | grep DB_
```

### Horizon no inicia

```bash
# Ver logs
docker compose logs horizon

# Reiniciar Horizon
docker compose restart horizon

# Verificar estado manualmente
docker compose exec php php artisan horizon:status
```

### Limpiar cache

```bash
docker compose exec php php artisan config:clear
docker compose exec php php artisan cache:clear
docker compose exec php php artisan route:clear
docker compose exec php php artisan view:clear
```

## Producción

Para producción, asegúrate de:

1. Cambiar `APP_ENV=production` y `APP_DEBUG=false` en `.env`
2. Configurar variables de entorno seguras
3. Configurar SSL/TLS en Nginx
4. Revisar permisos de archivos
5. Configurar backups de base de datos
