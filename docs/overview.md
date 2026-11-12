# yes-ok

Plataforma de reservas para negocios de servicios. El panel es Blade (Tabler). La API `/api/v1` sigue disponible para la app móvil.

## Módulos

- Agenda y citas
- Clientes
- Catálogo de servicios
- Bloqueos de disponibilidad
- Cola por reagendar
- Reserva pública (`/agendar`)
- Portal del cliente (`/portal`, `/mi-cuenta`)
- Reportes, personal y configuración
- Notificaciones (push / WhatsApp), según el `.env`

## Reglas de citas

1. Una cita puede incluir varios servicios.
2. La duración es la suma de las duraciones de esos servicios.
3. Los horarios se calculan con esa duración y los bloqueos.
4. Un servicio inactivo no entra en citas nuevas.
5. Solo un administrador puede forzar un horario.
6. La reserva pública crea o actualiza al cliente por teléfono y deja la cita confirmada.

Esas reglas viven en `app/Services/AppointmentService.php` y en `app/Http/Controllers/Api/V1/PublicController.php`. Las pantallas Blade los llaman; no duplican la validación.

## Roles

- `ADMIN` y `ASISTENTE`: panel (`/login`).
- `CLIENTE`: portal (`/portal`).

## Documentos

- `docs/structure.md`
- `docs/booking-flow.md`
- `docs/roles-and-permissions.md`
- `docs/services-catalog.md`
