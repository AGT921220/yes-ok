# Panel Blade

El panel se renderiza con Blade y Tabler, en `resources/views`.

Si no hay `public/build/manifest.json`, el layout carga Tabler desde CDN. Para compilar los assets locales:

```bash
npm install
npm run build
```

Vite toma `resources/css/app.css` y `resources/js/app.js` (`@tabler/core` y los iconos).

## Vistas

- `layouts/app.blade.php` y `layouts/guest.blade.php`
- `partials/dashboard/header.blade.php`
- `agenda/`, `clients/`, `services/`, `blocks/`, `reschedule/`, `reports/`, `users/`, `settings/`
- `booking/create.blade.php` (reserva pública)
- `portal/login.blade.php` y `portal/index.blade.php`
- `auth/login.blade.php`

Los controladores están en `app/Http/Controllers/Web/`. La agenda llama a `AppointmentService`. La reserva pública reutiliza `PublicController::bookAppointment`.
