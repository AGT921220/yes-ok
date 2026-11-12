# CORS en producción (yes-ok)

## URLs

- **Frontend:** https://yes-ok.agsoftweb.com.mx/
- **API:** https://api.yes-ok.agsoftweb.com.mx/api/v1/

## Configuración en el backend (Laravel)

1. **Archivo `config/cors.php`**  
   Ya está creado y usa la variable de entorno `CORS_ORIGINS`. Orígenes por defecto (si no se define la variable):  
   `https://yes-ok.agsoftweb.com.mx`, `http://localhost:3000`, `http://localhost`.

2. **En el servidor de producción**, en el `.env` del backend, define:

   ```env
   CORS_ORIGINS=https://yes-ok.agsoftweb.com.mx
   ```

   Sin barra final, sin espacios. Si necesitas varios orígenes, sepáralos por coma.

3. **Limpiar caché de configuración** después de tocar `.env` o `config/cors.php`:

   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

## Si usas Nginx delante de Laravel

El navegador envía una petición **OPTIONS** (preflight) antes del POST a `/api/v1/auth/login`. Nginx debe **dejar pasar** OPTIONS y el resto de métodos a Laravel; no debe devolver 400/405 por su cuenta.

Ejemplo de location para la API:

```nginx
location /api {
    try_files $uri $uri/ /index.php?$query_string;
    if ($request_method = OPTIONS) {
        add_header Access-Control-Allow-Origin $http_origin;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS";
        add_header Access-Control-Allow-Headers "Authorization, Content-Type, Accept";
        add_header Access-Control-Allow-Credentials "true";
        add_header Access-Control-Max-Age 86400;
        return 204;
    }
}
```

O, más simple, **no** manejar OPTIONS en Nginx y dejar que **Laravel** responda (recomendado): no pongas reglas especiales para OPTIONS y asegúrate de que `try_files` lleve las peticiones a `index.php`. Así el middleware CORS de Laravel se encarga de todo.

## Frontend (React)

En producción el frontend debe llamar a la API correcta. Define en el build:

```env
REACT_APP_BACKEND_URL=https://api.yes-ok.agsoftweb.com.mx
```

(Sin barra final. En Create React App se usa `.env.production` o variables de entorno en el servidor de build.)

## Comprobar CORS

1. En el navegador (F12 → pestaña Red), al hacer login deberías ver:
   - Una petición **OPTIONS** a `https://api.yes-ok.agsoftweb.com.mx/api/v1/auth/login` con respuesta **200** o **204** y cabeceras `Access-Control-Allow-Origin: https://yes-ok.agsoftweb.com.mx`.
   - Luego la petición **POST** al mismo URL, también con `Access-Control-Allow-Origin` en la respuesta.

2. Si OPTIONS devuelve **400** o **404**, el fallo suele estar en Nginx (bloqueando OPTIONS) o en que la petición no llega a Laravel.

3. Si OPTIONS está bien pero el POST falla con 400, el problema ya no es CORS sino la petición (body, validación, etc.). Revisa el cuerpo del POST y la respuesta JSON del 400.
