# Resumen de Integración Frontend-Backend

## ✅ Cambios Realizados

### 1. Estructura Reorganizada

**Antes:**
```
/frontend          ← React independiente
/backend           ← Laravel
```

**Después:**
```
/backend
├── resources/
│   └── frontend/  ← Código fuente React
└── public/
    └── app/       ← Build final del frontend
```

### 2. Configuración del Build

- **craco.config.js** actualizado:
  - Output path: `../../public/app`
  - Public path: `/app/`
  - Los assets se generan en `backend/public/app/static/`

### 3. API Configuration

- **api.js** actualizado:
  - Usa URL relativa cuando `REACT_APP_BACKEND_URL` no está definido
  - API base: `/api/v1` (relativo)
  - Compatible con desarrollo y producción

### 4. Nginx Configuration

- **docker/nginx/default.conf** actualizado:
  - `/api/*` → Laravel (PHP-FPM)
  - `/app/static/*` → Assets del frontend (con cache)
  - `/*` → Frontend SPA (fallback a `app/index.html`)

### 5. Laravel Routes

- **routes/web.php**: Sirve `app/index.html` para rutas del frontend
- **routes/api.php**: Creado para rutas API (prefijo `/api/v1`)
- **bootstrap/app.php**: Configurado para cargar rutas API

## 🚀 Cómo Usar

### Desarrollo del Frontend

```bash
# Opción 1: Servidor de desarrollo independiente (recomendado)
cd backend/resources/frontend
npm install
npm start
# Accede a http://localhost:3000

# Opción 2: Build e integrar
cd backend/resources/frontend
npm run build
# Accede a http://localhost (servido por Nginx)
```

### Producción

```bash
# 1. Construir frontend
cd backend/resources/frontend
npm run build

# 2. Levantar Docker
docker compose up -d

# 3. Configurar Laravel
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

## 📁 Estructura Final

```
backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── app/              ← Build del frontend (generado)
│   │   ├── index.html
│   │   └── static/
│   ├── index.php        ← Laravel entry point
│   └── ...
├── resources/
│   └── frontend/         ← Código fuente React
│       ├── src/
│       ├── public/
│       ├── package.json
│       └── ...
├── routes/
│   ├── api.php          ← Rutas API (/api/v1/*)
│   └── web.php          ← Rutas web (sirve frontend)
└── ...
```

## 🔗 URLs

- **Frontend**: `http://localhost/` (cualquier ruta que no sea `/api/*`)
- **API**: `http://localhost/api/v1/*`
- **Assets**: `http://localhost/app/static/*`

## ⚠️ Notas Importantes

1. **Build Manual**: El build del frontend se hace manualmente con `npm run build`
2. **Desarrollo**: Para desarrollo, usar `npm start` en `resources/frontend`
3. **API Relativa**: El frontend usa URLs relativas, no necesita `REACT_APP_BACKEND_URL` en producción
4. **SPA Routing**: Nginx está configurado para servir `app/index.html` como fallback

## 📝 Próximos Pasos

1. Implementar rutas API en `routes/api.php` según `docs/endpoint-mapping.md`
2. Agregar middleware de autenticación JWT
3. Configurar CORS si es necesario
4. Probar flujo completo: login → dashboard → funcionalidades
