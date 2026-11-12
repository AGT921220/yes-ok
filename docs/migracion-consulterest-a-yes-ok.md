# Migración a yes-ok

## Objetivo
Consolidar la transición del sistema previo (agenda y expediente para consultas psiquiátricas) hacia **yes-ok** (reservas de servicios para estética, barbería y salón), reutilizando la lógica base de:
- agenda de citas
- validaciones de traslapes (overlap) y bloqueos de disponibilidad
- notificaciones (WhatsApp, push, logs)

## Cambios funcionales principales

1. **Branding / dominio**
   - Nombre y marca visibles: `yes-ok`
   - `pacientes` → `clientes` (UI y mensajes; internamente la tabla puede seguir llamándose `patients` por compatibilidad)

2. **Catálogo de servicios**
   - Nueva entidad `Service` con campo `duration_minutes` (15, 30, 45, 60) y bandera `is_active`.

3. **Citas con uno o varios servicios**
   - La cita puede incluir `service_ids[]`.
   - La duración total de la cita se calcula como la suma de `duration_minutes` de los servicios seleccionados.
   - `end_time` se calcula en backend a partir de `start_time` + duración total (cuando se envían `service_ids`).

4. **Disponibilidad con duración real**
   - El endpoint público de slots `GET /api/v1/public/available-slots` usa la duración solicitada para generar slots y validar traslapes.
   - `duration_minutes` representa la **duración total** (sumatoria), y debe ser múltiplo de 15.

## Cambios técnicos (backend)

- Migraciones:
  - `services`
  - `appointment_service` (pivot cita ↔ servicios)
- Backend:
  - Modelo `Service`
  - Relación `Appointment::services()`
  - `AppointmentService` calcula `end_time` cuando hay `service_ids` y sincroniza la pivot.
- Requests:
  - `StoreAppointmentRequest` acepta `service_ids[]` y hace `end_time` opcional cuando se envían servicios.
  - `UpdateAppointmentRequest` acepta `service_ids[]`.
- API:
  - CRUD `/api/v1/services`
  - Public slots:
    - `GET /api/v1/public/available-slots?target_date=YYYY-MM-DD&duration_minutes=...`
  - Booking público:
    - `POST /api/v1/public/book` acepta `service_ids[]` (y opcionalmente `end_time`).

## Cambios en frontend

- UI de agenda:
  - Selector de servicios (múltiple) en el drawer de creación/edición
  - `end_time` se recalcula en base a la duración total
- UI pública:
  - Selector de servicios (múltiple) antes de elegir fecha/hora
  - El cálculo de slots usa la duración total

## Compatibilidad con citas anteriores

- Si una cita antigua no tiene servicios asociados, la duración se deriva de `start_time`/`end_time` guardados.
- Si una cita nueva tiene servicios, la duración se deriva de la sumatoria.

## Checklist de revisión

1. QA de creación/edición:
   - 30 min
   - 45 + 15 = 60 min
   - 60 + 30 = 90 min (duración total > 60)
2. Disponibilidad:
   - slots retornados respetan la duración real (no el fijo de 60)
3. Traslapes:
   - citas de distinta duración no se empalman
4. Notificaciones:
   - confirmaciones/cancelaciones disparan los jobs esperados (sandbox/logs)

