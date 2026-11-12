# Setup Rápido - yes-ok

## Inicio Rápido (4 comandos)

```bash
# 1. Levantar servicios Docker
docker compose up -d

# 2. Configurar backend (desde la raíz del proyecto)
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed

# 3. Construir frontend (opcional - solo si quieres servir el frontend integrado)
docker compose exec php bash -c "cd resources/frontend && npm install && npm run build"
```

¡Listo! 
- Backend API: `http://localhost/api/v1`
- Frontend: `http://localhost` (si construiste el frontend)

## Credenciales

**Usuario Admin:**
- Email: `admin@yes-ok.com`
- Password: `admin123`

**MySQL:**
- Host: `mysql-yes-ok` (desde contenedores) o `localhost:3306` (desde host)
- Database: `yes-ok`
- Username: `yes-ok`
- Password: `yes-ok123`

## Verificar que todo funciona

```bash
# Ver estado de servicios
docker compose ps

# Ver logs
docker compose logs -f

# Verificar Horizon
docker compose logs horizon
```

## Documentación Completa

- `backend/README.md` - Documentación del backend Laravel
- `docs/docker-setup.md` - Guía completa de Docker
- `docs/frontend.md` - Guía del frontend integrado

## Troubleshooting Rápido

### Error de permisos
```bash
docker compose exec php chmod -R 775 storage bootstrap/cache
```

### MySQL no conecta
```bash
docker compose logs mysql
docker compose ps mysql
```

### Reiniciar todo
```bash
docker compose restart
```
