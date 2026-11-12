# Estructura

Laravel está en la raíz. Un solo `.env`. El panel es Blade. La carpeta `frontend/` ya no existe. `mobile/` sigue aparte y consume `/api/v1`.

```
yes-ok/
├── app/                  # Modelos, controladores API y Web, servicios
├── bootstrap/
├── config/
├── database/
├── docker/               # nginx, php, entrypoint
├── docs/
├── mobile/               # App Expo (API)
├── public/               # index.php y assets
├── resources/
│   ├── css/
│   ├── js/
│   └── views/            # Blade (layouts, agenda, clientes, portal)
├── routes/
│   ├── api.php           # /api/v1
│   └── web.php           # Panel, reserva pública, portal
├── storage/
├── tests/
├── docker-compose.yml
├── Dockerfile
├── artisan
├── composer.json
├── package.json          # Vite + Tabler
├── vite.config.js
└── .env
```

Docker monta el repositorio en `/var/www/html`. Nginx sirve `public/`.

## Pantallas

| Ruta | Quién |
| --- | --- |
| `/login` | Personal y administración |
| `/agenda` | Personal y administración |
| `/clientes`, `/bloqueos`, `/reagendar`, `/reportes` | Personal y administración |
| `/servicios`, `/personal`, `/configuracion` | Solo administración |
| `/agendar` | Público |
| `/portal`, `/mi-cuenta` | Cliente |

El login de Horizon sigue en `/admin/login`.
