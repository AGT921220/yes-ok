# Panel

Entra en `/login` un usuario `ADMIN` o `ASISTENTE`.

- Agenda: listado del día, alta de cita, detalle, asistencia, pago y cancelación.
- Clientes, bloqueos, cola por reagendar y reportes: personal y administración.
- Servicios, personal y configuración: solo administración.
- Reserva pública: `/agendar`, sin sesión.
- Portal del cliente: `/portal`.

La duración, los traslapes y la reserva pública no cambian: siguen en `AppointmentService` y `PublicController`.
