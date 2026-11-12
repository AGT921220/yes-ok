# Citas, Pacientes y Configuración (Actualizado)

Documentación de las reglas de negocio, configuraciones descartadas y endpoints actualizados del módulo de Citas y Pacientes.

## Configuraciones eliminadas

- **Duración y tiempos de citas**: La pantalla de configuración de duración de cita (minutos) y buffer entre citas fue **descartada**. Las consultas son **horarios cerrados de 1 hora**: solo se seleccionan horas en punto (12:00, 13:00, …, 19:00) y la **hora final se asigna en automático** (p. ej. 12:00 → 13:00). No se elige hora de fin manualmente.
- **Recordatorios (24h / 2h)**: La configuración de recordatorios por correo/WhatsApp fue **descartada**. No hay toggles ni jobs activos de recordatorio desde la configuración.
- **Intervalo SOAP global**: La configuración "cada N citas una nota SOAP" fue **descartada**. Las notas SOAP son **por paciente** (ver más abajo).

La ruta **/configuracion** sigue existiendo (solo ADMIN) y muestra un mensaje informativo sobre la configuración simplificada.

---

## Notas SOAP por paciente

- Cada nota SOAP pertenece a un **paciente** (`patient_id` obligatorio).
- La **numeración es por paciente**: al crear una nota se asigna `number = max(number) del paciente + 1`. Cada paciente tiene su propia secuencia (Nota #1, #2, #3…).
- Al editar una nota no se renumeran las demás.

### Endpoints SOAP

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/v1/clients/{id}/soap-notes` | Listado de notas del cliente (orden por fecha) |
| POST | `/api/v1/clients/{id}/soap-notes` | Crear nota (se asigna `number` automático) |
| GET | `/api/v1/soap-notes/{id}` | Ver una nota |
| PUT | `/api/v1/soap-notes/{id}` | Editar (solo si está en BORRADOR) |
| DELETE | `/api/v1/soap-notes/{id}` | Eliminar (solo si está en BORRADOR) |
| POST | `/api/v1/soap-notes/{id}/sign` | Firmar nota |
| POST | `/api/v1/soap-notes/{id}/addendum` | Agregar addendum (solo si está FIRMADA) |

En la vista de expediente (paciente) la sección "Notas SOAP" muestra "Nota #X" usando el campo `number` del backend.

---

## Teléfono del paciente (México)

- El campo teléfono/WhatsApp **solo acepta 10 dígitos** (sin LADA).
- **Frontend**: input `type="tel"`, solo dígitos, máximo 10; mensaje "El número debe tener 10 dígitos" si no cumple.
- **Backend**: validación `regex:/^\d{10}$/` y mensaje "El número debe tener exactamente 10 dígitos (México)."
- Al guardar se envían solo dígitos (el backend normaliza en `prepareForValidation` si llega con más caracteres).

---

## Creación de paciente y contraseña de acceso

- Al crear un paciente desde el módulo Pacientes se genera automáticamente un **usuario** con rol PACIENTE y credenciales para iniciar sesión.
- **Contraseña**:
  - **Opcional al crear**: puedes definir una contraseña en el formulario ("Contraseña para acceso del paciente"). Si la dejas en blanco, el sistema genera una **contraseña segura** (12–16 caracteres, sin caracteres confusos) y la muestra **una sola vez** en un **modal** con botón **Copiar**. Si defines contraseña, no se muestra modal; se muestra un mensaje indicando que el paciente podrá iniciar sesión con la que configuraste.
  - La contraseña se guarda hasheada. El paciente puede iniciar sesión con teléfono (o email si se proporcionó) y esa contraseña.
- **Acceso del paciente**: con esa contraseña el paciente tiene acceso al sistema; a **ese paciente** se le configuran las **notas SOAP** (por expediente del paciente).
- Si ya existe un paciente con ese teléfono no se duplica; el backend responde con error de conflicto.

---

## Horario disponible de citas y "Forzar cita"

- **Consultas cerradas (1 hora)**:
  - Solo se ofrecen **horarios en punto**: 12:00, 13:00, 14:00, …, 19:00. Cada consulta ocupa **1 hora** (p. ej. 12:00–13:00, 13:00–14:00).
  - La **hora final no se elige**: se asigna en automático a partir de la hora de inicio (+1 h). En agenda, agendar público y por reagendar se selecciona solo la hora de inicio.
- **Modo normal (por defecto)**:
  - Solo se permiten citas **lunes a sábado** entre **12:00 y 20:00**.
  - En el calendario se ocultan los domingos (día deshabilitado) y los horarios fuera de 12:00–20:00. La vista de agenda puede mostrar un rango horario algo más amplio para scroll/consulta, pero **la validación de creación/edición sigue siendo 12:00–20:00**.
  - Si se intenta agendar fuera de rango (p. ej. por API), el backend rechaza con: *"Horario no permitido. Disponible L-S 12:00–20:00. Use 'Forzar Cita' si aplica."*
- **Forzar cita** (solo ADMIN):
  - Solo el **ADMIN** puede usar esta función. El botón **"Forzar cita"** en la agenda se muestra únicamente para usuarios con rol ADMIN.
  - Al hacer clic se activa un modo especial que permite agendar en **cualquier día y cualquier hora** (sin restricción L-S 12:00–20:00).
  - El calendario muestra entonces todos los días y todo el horario; se muestra el indicador "Modo: Cita forzada".
  - La cita se guarda con `forced = true`. Los asistentes no pueden crear ni activar citas forzadas; si un asistente intenta enviar `forced: true` por API, el backend lo ignora (crear) o conserva el valor actual de la cita (editar).
- En listado y detalle de citas se muestra un badge **"Forzada"** y borde distintivo cuando `forced === true`.

### Validación backend (obligatoria)

- Aunque el frontend restrinja opciones, el backend **rechaza** cualquier cita normal fuera de reglas.
- Al crear/actualizar cita:
  - Si `forced === false`: se valida día (1–6, lunes–sábado), `start_time >= 12:00` y `end_time <= 20:00`.
  - Si `forced === true`: no se aplica validación de horario; se permite cualquier fecha/hora.

### Ejemplo de error (400)

```json
{
  "ok": false,
  "message": "Horario no permitido. Disponible L-S 12:00–20:00. Use \"Forzar Cita\" si aplica.",
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "..."
  }
}
```

---

## Asistencia y estado de pago

### Asistencia

- Campo en citas: `attendance_status` con valores:
  - `pendiente` (default)
  - `asistio`
  - `no_asistio`

### Pago

- Campo en citas: `payment_status` con valores:
  - `sin_pagar` (default)
  - `pagado`
  - (opcional en futuro: `parcial`)

- Tabla `appointment_payments`: cada registro es un pago (monto, método, notas, fecha).
- Al agregar al menos un pago, la cita se actualiza a `payment_status = pagado`.

### Endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| PATCH | `/api/v1/appointments/{id}/attendance` | Actualizar asistencia. Body: `{ "status": "pendiente" \| "asistio" \| "no_asistio" }` |
| GET | `/api/v1/appointments/{id}/payments` | Listar pagos de la cita |
| POST | `/api/v1/appointments/{id}/payments` | Registrar un pago. Body: `{ "amount", "method?", "notes?", "paid_at?" }` |

### Ejemplo PATCH attendance

```json
// Request
PATCH /api/v1/appointments/5/attendance
{ "status": "asistio" }

// Response 200
{ "data": { ... AppointmentResource con attendance_status actualizado } }
```

### Ejemplo POST payment

```json
// Request
POST /api/v1/appointments/5/payments
{
  "amount": 500.00,
  "method": "efectivo",
  "notes": "Consulta general",
  "paid_at": "2026-02-12"
}

// Response 201
{ "data": { "id": 1, "appointment_id": 5, "amount": 500, "method": "efectivo", ... } }
```

---

## Tasa de inasistencia (copys)

- En UI y reportes se usa **"Tasa de inasistencia"** (en lugar de "tasa de no-show").
- Descripción mostrada: *"Porcentaje de citas marcadas como 'No asistió' respecto al total en el periodo seleccionado."*
- El endpoint `GET /api/v1/reports/appointments-stats` sigue devolviendo `no_show_rate` (por compatibilidad) y opcionalmente `inasistencia_rate` con el mismo valor.

---

## Ejemplos de payloads

### Crear cliente (POST /api/v1/clients)

**Request:** teléfono exactamente 10 dígitos (solo números). `password` es opcional (mínimo 8 caracteres); si no se envía, se genera una y se devuelve en la respuesta.

```json
{
  "first_name": "Juan",
  "paternal_surname": "Pérez",
  "maternal_surname": "García",
  "whatsapp": "5512345678",
  "email": "juan@ejemplo.com",
  "password": "opcional_min8",
  "consent_notifications": true
}
```

**Response 201:** si la contraseña fue **generada** por el sistema, incluye `password` (solo en esta respuesta). Si el cliente envió `password`, la respuesta no incluye `password` y el mensaje indica que el paciente podrá iniciar sesión con la configurada.

```json
{
  "data": { "id": 1, "first_name": "Juan", "whatsapp": "5512345678", ... },
  "password": "Abc12XyZ34Pq",
  "message": "Paciente creado exitosamente. Guarda la contraseña para dársela al paciente."
}
```

Cuando el cliente definió contraseña:

```json
{
  "data": { "id": 1, "first_name": "Juan", ... },
  "message": "Paciente creado exitosamente. El paciente podrá iniciar sesión con la contraseña que configuraste."
}
```

**Errores:** 400 si `whatsapp` no tiene 10 dígitos; 409 si ya existe paciente con ese teléfono.

### Cancelación de citas y Google Calendar

- **Cancelación interna:** la cancelación de una cita se realiza vía `AppointmentService::cancel`, que:
  - Cambia el `status` a `CANCELADA` y guarda `cancellation_reason`.
  - Dispara el evento `AppointmentCancelled` (usado por `SendCancellationNotifications`).
  - Libera el horario para validaciones de empalme/disponibilidad.
- **Visibilidad en la agenda:** las citas canceladas (`status = CANCELADA`) **no se incluyen** en `GET /api/v1/appointments` cuando no se especifica `status`, por lo que **no aparecen en el calendario/agenda** administrativo.
- **Google Calendar:** la cancelación en Google se hace en segundo plano:
  - Controlador staff (`AppointmentController@cancel` y `destroy`) y cancelación desde portal (`PublicController@cancelAppointment`) despachan `CancelGoogleCalendarAppointmentJob` cuando la cita tiene `google_calendar_id`.
  - El job `CancelGoogleCalendarAppointmentJob` corre en la cola `calendar` y usa `GoogleCalendarClient::deleteAppointment()` (idempotente: si el evento ya no existe, no rompe el flujo) con logging de éxito/errores.

### Crear/actualizar cita con forced (POST/PUT /api/v1/appointments)

**Request (cita normal):** `forced` false u omitido; fecha debe ser L-S y hora 12:00–20:00.

```json
{
  "patient_id": 1,
  "date": "2026-02-17",
  "start_time": "14:00",
  "end_time": "14:30",
  "reason": "Seguimiento",
  "forced": false
}
```

**Request (cita forzada):** `forced: true` permite cualquier fecha/hora.

```json
{
  "patient_id": 1,
  "date": "2026-02-16",
  "start_time": "09:00",
  "end_time": "09:30",
  "forced": true
}
```

---

## Resumen de migraciones

- `add_number_to_soap_notes_table`: campo `number` en `soap_notes`, único por `(patient_id, number)`.
- `add_forced_and_attendance_payment_to_appointments_table`: campos `forced`, `attendance_status`, `payment_status` en `appointments`.
- `create_appointment_payments_table`: tabla `appointment_payments` (appointment_id, amount, method, notes, paid_at, created_by).

---

## Tests sugeridos

- **SOAP por paciente**: crear 2 pacientes, crear 2 notas en cada uno; verificar que las numeraciones son 1 y 2 por paciente de forma independiente.
- **Horario**: crear cita con `forced: false` un domingo o a las 10:00 → 400 con mensaje de horario no permitido; con `forced: true` → 201.
- **Asistencia**: PATCH attendance y verificar que la cita devuelta tiene el nuevo `attendance_status`.
- **Pago**: POST payment y verificar que la cita pasa a `payment_status: pagado` y que GET payments devuelve el nuevo registro.
