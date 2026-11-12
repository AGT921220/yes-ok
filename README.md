# yes-ok

Agenda de citas para negocios de servicios (barbería, estética, salón). Laravel en la raíz del repositorio, pantallas Blade con Tabler y API JSON en `/api/v1`.

## Qué hace

- Agenda de citas para personal y administración.
- Clientes, servicios, bloqueos y cola de reagendar.
- Reserva pública en `/agendar` y portal del cliente en `/portal`.
- Reportes, usuarios del personal y ajustes.
- La app móvil sigue usando la API.

La lógica de citas (traslapes, duración por servicios, horarios y cancelación) sigue en `AppointmentService`.

## Cómo levantarlo

```bash
cp .env.example .env
docker compose up -d
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
docker compose exec php php artisan admin:create
```

El panel queda en el puerto `PORT_NGINX` del `.env` (en local, `http://127.0.0.1:8040`). El usuario administrador sale de `ADMIN_EMAIL`, `ADMIN_PASSWORD` y `ADMIN_NAME`.

Hay un solo `.env` en la raíz. No existe carpeta `frontend/`.

## Documentación

- `docs/overview.md`
- `docs/structure.md`
- `docs/setup.md`
- `docs/frontend.md`
- `docs/docker.md`
- `docs/booking-flow.md`
