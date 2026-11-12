# Base de Conocimiento - yes-ok Backend Laravel

**Última actualización**: 2026-02-05  
**Mantenido por**: AI Lead Engineer + Tech Writer Senior

---

## 1. ESQUEMA DE BASE DE DATOS

### 1.1 Tabla: `patients`

**Columnas reales** (verificado en DB):
- `id` (bigint unsigned, PK, auto_increment)
- `first_name` (varchar(255), NOT NULL)
- `paternal_surname` (varchar(255), NOT NULL) ⚠️ **COLUMNA REAL**
- `maternal_surname` (varchar(255), NULLABLE)
- `whatsapp` (varchar(20), UNIQUE, NOT NULL)
- `email` (varchar(255), NULLABLE)
- `consent_notifications` (tinyint(1), DEFAULT 1)
- `otp_verified` (tinyint(1), DEFAULT 0)
- `access_token` (varchar(255), NULLABLE, INDEX)
- `access_token_expires` (timestamp, NULLABLE)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Índices**:
- `whatsapp` (UNIQUE - creado automáticamente por `->unique()`)
- `access_token` (INDEX - creado por `->index()`)

**Accessor calculado**:
- `full_name`: `first_name + paternal_surname + maternal_surname` (si existe)

**Reglas de negocio**:
- WhatsApp es el identificador único del paciente
- `paternal_surname` es obligatorio
- `maternal_surname` es opcional

**Ordenamiento por defecto**:
- Por `paternal_surname` ASC, luego `first_name` ASC

---

### 1.2 Tabla: `appointments`

**Columnas reales** (verificado en DB):
- `id` (bigint unsigned, PK, auto_increment)
- `patient_id` (bigint unsigned, FK patients.id, NOT NULL, INDEX)
- `date` (date, NOT NULL, INDEX)
- `start_time` (time, NOT NULL)
- `end_time` (time, NOT NULL)
- `status` (enum, DEFAULT 'PENDIENTE', INDEX)
- `notes` (text, NULLABLE)
- `reason` (varchar(500), NULLABLE)
- `created_by` (bigint unsigned, FK users.id, NULLABLE, INDEX)
- `booked_by_patient` (tinyint(1), DEFAULT 0)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Estados válidos**:
- `PENDIENTE`
- `CONFIRMADA`
- `CANCELADA`
- `NO_SHOW`
- `COMPLETADA`
- `REQUIERE_REAGENDAR`

**Índices**:
- `patient_id` (creado automáticamente por `foreignId()`)
- `date` (INDEX)
- `status` (INDEX)
- `date, status` (INDEX compuesto)
- `created_by` (creado automáticamente por `foreignId()`)

**Ordenamiento por defecto**:
- Por `date` ASC, luego `start_time` ASC

**Reglas de negocio**:
- No se pueden crear citas en el pasado
- No puede haber empalmes con otras citas (excepto CANCELADA)
- No se puede agendar en horarios bloqueados (availability_blocks)
- Al crear/actualizar, se valida solapamiento con `AppointmentService`

---

### 1.3 Tabla: `availability_blocks`

**Columnas reales** (verificado en DB):
- `id` (bigint unsigned, PK, auto_increment)
- `start_date` (date, NOT NULL, INDEX)
- `end_date` (date, NOT NULL)
- `start_time` (time, NULLABLE)
- `end_time` (time, NULLABLE)
- `is_full_day` (tinyint(1), DEFAULT 1)
- `reason` (varchar(500), NULLABLE)
- `is_recurring` (tinyint(1), DEFAULT 0)
- `recurrence_pattern` (varchar(50), NULLABLE)
- `created_by` (bigint unsigned, FK users.id, NOT NULL, INDEX)
- `created_at` (timestamp, DEFAULT CURRENT_TIMESTAMP)

**Nota**: Esta tabla NO tiene `updated_at` (solo `created_at`).

**Índices**:
- `start_date` (INDEX)
- `start_date, end_date` (INDEX compuesto)
- `created_by` (creado automáticamente por `foreignId()`)

**Ordenamiento por defecto**:
- Por `start_date` ASC

**Reglas de negocio**:
- Al crear un bloqueo, todas las citas afectadas se marcan como `REQUIERE_REAGENDAR`
- Si `is_full_day = true`, se bloquea todo el día (ignora `start_time`/`end_time`)
- Si `is_full_day = false`, se valida el rango horario

---

## 2. CONTRATOS DE ENDPOINTS

### 2.1 GET `/api/v1/clients`

**Request**:
- Query params:
  - `search` (opcional): Búsqueda en `first_name`, `paternal_surname`, `maternal_surname`, `whatsapp`

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "first_name": "Juan",
      "paternal_surname": "Pérez",
      "maternal_surname": "García",
      "full_name": "Juan Pérez García",
      "whatsapp": "+521234567890",
      "email": "juan@test.com",
      "consent_notifications": true,
      "otp_verified": false,
      "created_at": "2026-02-05T10:00:00.000000Z",
      "updated_at": "2026-02-05T10:00:00.000000Z"
    }
  ]
}
```

**Ordenamiento**: `paternal_surname ASC, first_name ASC`

**Errores conocidos corregidos**:
- ✅ `orderBy('paternal_surname')` - Columna existe y funciona correctamente

---

### 2.2 POST `/api/v1/clients`

**Request**:
```json
{
  "first_name": "Juan",
  "paternal_surname": "Pérez",
  "maternal_surname": "García",
  "whatsapp": "+521234567890",
  "email": "juan@test.com",
  "consent_notifications": true
}
```

**Validaciones**:
- `first_name`: required, string, min:1
- `paternal_surname`: required, string, min:1
- `maternal_surname`: nullable, string
- `whatsapp`: required, string, min:10, regex: `/^\+?[1-9]\d{9,14}$/`, unique:patients,whatsapp
- `email`: nullable, email
- `consent_notifications`: boolean

**Response** (201 Created):
```json
{
  "data": {
    "id": 1,
    "first_name": "Juan",
    "paternal_surname": "Pérez",
    "maternal_surname": "García",
    "full_name": "Juan Pérez García",
    "whatsapp": "+521234567890",
    "email": "juan@test.com",
    "consent_notifications": true,
    "otp_verified": false,
    "created_at": "2026-02-05T10:00:00.000000Z",
    "updated_at": "2026-02-05T10:00:00.000000Z"
  }
}
```

**Errores** (400 Bad Request):
- WhatsApp duplicado: `{"message": "Ya existe un paciente con este WhatsApp: {nombre}"}`

---

### 2.3 GET `/api/v1/appointments`

**Request**:
- Query params:
  - `start_date` (opcional): Fecha inicio (Y-m-d)
  - `end_date` (opcional): Fecha fin (Y-m-d)
  - `status` (opcional): Estado de la cita
  - `patient_id` (opcional): ID del paciente

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "patient_id": 1,
      "date": "2026-02-10",
      "start_time": "10:00",
      "end_time": "11:00",
      "status": "PENDIENTE",
      "notes": null,
      "reason": null,
      "created_by": 1,
      "booked_by_patient": false,
      "created_at": "2026-02-05T10:00:00.000000Z",
      "updated_at": "2026-02-05T10:00:00.000000Z",
      "patient": {
        "id": 1,
        "first_name": "Juan",
        "paternal_surname": "Pérez",
        "maternal_surname": "García",
        "full_name": "Juan Pérez García",
        "whatsapp": "+521234567890",
        "email": "juan@test.com",
        "consent_notifications": true,
        "otp_verified": false,
        "created_at": "2026-02-05T10:00:00.000000Z",
        "updated_at": "2026-02-05T10:00:00.000000Z"
      }
    }
  ]
}
```

**Ordenamiento**: `date ASC, start_time ASC`

**Errores conocidos corregidos**:
- ✅ `orderBy('date')` y `orderBy('start_time')` - Columnas existen y funcionan correctamente

---

### 2.4 POST `/api/v1/appointments`

**Request**:
```json
{
  "patient_id": 1,
  "date": "2026-02-10",
  "start_time": "10:00",
  "end_time": "11:00",
  "status": "PENDIENTE",
  "notes": "Primera consulta",
  "reason": "Consulta general"
}
```

**Validaciones**:
- `patient_id`: required, integer, exists:patients,id
- `date`: required, date, after_or_equal:today
- `start_time`: required, date_format:H:i
- `end_time`: required, date_format:H:i, after:start_time
- `status`: nullable, enum(AppointmentStatus)
- `notes`: nullable, string
- `reason`: nullable, string, max:500

**Response** (201 Created):
```json
{
  "data": {
    "id": 1,
    "patient_id": 1,
    "date": "2026-02-10",
    "start_time": "10:00",
    "end_time": "11:00",
    "status": "PENDIENTE",
    "notes": "Primera consulta",
    "reason": "Consulta general",
    "created_by": 1,
    "booked_by_patient": false,
    "created_at": "2026-02-05T10:00:00.000000Z",
    "updated_at": "2026-02-05T10:00:00.000000Z",
    "patient": {
      "id": 1,
      "first_name": "Juan",
      "paternal_surname": "Pérez",
      "maternal_surname": "García",
      "full_name": "Juan Pérez García",
      "whatsapp": "+521234567890",
      "email": "juan@test.com",
      "consent_notifications": true,
      "otp_verified": false,
      "created_at": "2026-02-05T10:00:00.000000Z",
      "updated_at": "2026-02-05T10:00:00.000000Z"
    }
  }
}
```

**Errores** (400 Bad Request):
- Empalme: `{"message": "Existe un empalme con otra cita"}`
- Horario bloqueado: `{"message": "El horario está bloqueado"}`
- Fecha pasada: `{"message": "No se pueden crear citas en el pasado"}`

---

### 2.5 GET `/api/v1/availability-blocks`

**Request**:
- Query params:
  - `start_date` (opcional): Fecha inicio (Y-m-d)
  - `end_date` (opcional): Fecha fin (Y-m-d)

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "start_date": "2026-02-15",
      "end_date": "2026-02-20",
      "start_time": null,
      "end_time": null,
      "is_full_day": true,
      "reason": "Vacaciones",
      "is_recurring": false,
      "recurrence_pattern": null,
      "created_by": 1,
      "created_at": "2026-02-05T10:00:00.000000Z"
    }
  ]
}
```

**Ordenamiento**: `start_date ASC`

**Errores conocidos corregidos**:
- ✅ `orderBy('start_date')` - Columna existe y funciona correctamente

---

### 2.6 POST `/api/v1/availability-blocks`

**Request**:
```json
{
  "start_date": "2026-02-15",
  "end_date": "2026-02-20",
  "start_time": null,
  "end_time": null,
  "is_full_day": true,
  "reason": "Vacaciones",
  "is_recurring": false,
  "recurrence_pattern": null
}
```

**Validaciones**:
- `start_date`: required, date, after_or_equal:today
- `end_date`: required, date, after_or_equal:start_date
- `start_time`: nullable, date_format:H:i
- `end_time`: nullable, date_format:H:i, after:start_time
- `is_full_day`: boolean
- `reason`: nullable, string, max:500
- `is_recurring`: boolean
- `recurrence_pattern`: nullable, string, max:50

**Response** (201 Created):
```json
{
  "data": {
    "id": 1,
    "start_date": "2026-02-15",
    "end_date": "2026-02-20",
    "start_time": null,
    "end_time": null,
    "is_full_day": true,
    "reason": "Vacaciones",
    "is_recurring": false,
    "recurrence_pattern": null,
    "created_by": 1,
    "created_at": "2026-02-05T10:00:00.000000Z"
  }
}
```

**Efectos secundarios**:
- Todas las citas afectadas se marcan como `REQUIERE_REAGENDAR`
- Se crea un log de notificación (sandbox) para cada cita afectada

---

## 3. DECISIONES TÉCNICAS Y CONVENCIONES

### 3.1 Ordenamiento Centralizado

**Convención**: Los ordenamientos por defecto se definen en los Controllers, no en los Models.

**Ejemplos**:
- `PatientController::index()`: `orderBy('paternal_surname')->orderBy('first_name')`
- `AppointmentController::index()`: `orderBy('date')->orderBy('start_time')`
- `AvailabilityBlockController::index()`: `orderBy('start_date')`

**Razón**: Facilita el mantenimiento y evita duplicación. Si se necesita cambiar el ordenamiento, se hace en un solo lugar.

---

### 3.2 Formato de Fechas y Tiempos

**Fechas**:
- DB: `DATE` (Y-m-d)
- API Response: `Y-m-d` (string)
- API Request: `Y-m-d` (string)

**Tiempos**:
- DB: `TIME` (H:i:s)
- API Response: `H:i` (string, sin segundos)
- API Request: `H:i` (string, sin segundos)

**Formateo en Resources**:
- `AppointmentResource`: Usa `Carbon::parse()` para formatear `start_time` y `end_time`
- `AvailabilityBlockResource`: Similar para `start_time` y `end_time`

---

### 3.3 Validación de Solapamientos

**Servicio**: `AppointmentService`

**Métodos**:
- `checkOverlap()`: Verifica si hay empalme con otras citas
- `checkAvailabilityBlock()`: Verifica si el horario está bloqueado

**Reglas**:
- No se consideran citas con status `CANCELADA`
- Se excluye la cita actual si es actualización (parámetro `excludeId`)
- Comparación de tiempos normalizada a formato `H:i:s`

---

### 3.4 Estructura de Respuestas API

**Formato estándar**:
- Listas: `{"data": [...]}` (usando `Resource::collection()`)
- Item único: `{"data": {...}}` (usando `new Resource()`)
- Errores (formato unificado para frontend):
  - Para errores de negocio (`AppError` y derivados):
    ```json
    {
      "ok": false,
      "message": "Mensaje descriptivo en español",
      "error": {
        "code": "ERROR_CODE",
        "message": "Mensaje descriptivo en español",
        "details": {
          "campo_opcional": ["Detalle opcional"]
        }
      },
      "errors": {
        "campo_opcional": ["Detalle opcional"]
      }
    }
    ```
  - Para errores de validación (`ValidationException` de Laravel):
    ```json
    {
      "ok": false,
      "message": "Primer mensaje de validación en español",
      "error": {
        "code": "VALIDATION_ERROR",
        "message": "Primer mensaje de validación en español",
        "details": {
          "campo": ["Mensaje de error"]
        }
      },
      "errors": {
        "campo": ["Mensaje de error"]
      }
    }
    ```

El frontend debe leer principalmente `response.data.message` para mostrar el error al usuario y, si necesita mapear errores por campo, usar `response.data.errors`.

**Resources utilizados**:
- `PatientResource`
- `AppointmentResource`
- `AvailabilityBlockResource`

---

## 4. PROBLEMAS CONOCIDOS Y SOLUCIONES

### 4.1 Error: "Column not found: 1054 Unknown column 'paternal_surname' in 'order clause'"

**Estado**: ✅ **RESUELTO** (2026-02-05)

**Causa raíz**:
- Las migraciones se ejecutaron en orden incorrecto debido a timestamps idénticos
- `appointments` se creó antes que `patients`, causando errores de foreign key
- La tabla `patients` no se creó completamente, por lo que `paternal_surname` no existía

**Solución aplicada**:
1. Renombrado `2026_02_05_214755_create_patients_table.php` → `2026_02_05_214754_create_patients_table.php`
2. Eliminados índices duplicados en migraciones:
   - `patients`: Eliminado `->index('whatsapp')` (ya tiene `->unique()`)
   - `patients`: Eliminado `->index('access_token')` duplicado (ya tiene `->index()` en línea 24)
   - `appointments`: Eliminado `->index('date')` duplicado (ya tiene `->index()` en línea 17)
   - `appointments`: Eliminado `->index('patient_id')` (ya tiene índice por `foreignId()`)

**Verificación**:
- ✅ `patients` tiene columna `paternal_surname`
- ✅ `orderBy('paternal_surname')` funciona correctamente
- ✅ Todos los endpoints devuelven respuestas válidas

---

### 4.2 Error: "Table 'db.availability_blocks' doesn't exist"

**Estado**: ✅ **RESUELTO** (2026-02-05)

**Causa raíz**:
- La migración de `availability_blocks` falló porque dependía de migraciones anteriores que también fallaron

**Solución aplicada**:
- Corregido orden de migraciones
- Eliminadas tablas parcialmente creadas
- Ejecutadas todas las migraciones en orden correcto

**Verificación**:
- ✅ Tabla `availability_blocks` existe con todas las columnas
- ✅ Endpoint `/api/v1/availability-blocks` funciona correctamente

---

## 5. ÚLTIMOS CAMBIOS

### 2026-02-05 - Corrección de Endpoints y Migraciones

**Cambios realizados**:

1. **Migraciones**:
   - Renombrado `create_patients_table.php` para ejecutarse antes que `create_appointments_table.php`
   - Eliminados índices duplicados en `patients` y `appointments`
   - Verificado orden correcto de ejecución

2. **Endpoints corregidos**:
   - ✅ `/api/v1/clients` - `orderBy('paternal_surname')` funciona correctamente
   - ✅ `/api/v1/appointments` - `orderBy('date')` y `orderBy('start_time')` funcionan correctamente
   - ✅ `/api/v1/availability-blocks` - `orderBy('start_date')` funciona correctamente

3. **Documentación**:
   - Creado `docs/knowledge-base.md` con esquema completo de DB
   - Documentados contratos de endpoints
   - Documentadas decisiones técnicas y convenciones

**Archivos modificados**:
- `backend/database/migrations/2026_02_05_214754_create_patients_table.php` (renombrado y corregido)
- `backend/database/migrations/2026_02_05_214755_create_appointments_table.php` (corregido)
- `docs/knowledge-base.md` (nuevo)

---

## 6. MAPA DE COLUMNAS (Field Mapping)

**No hay mapeos necesarios**: Todas las columnas en el código coinciden con las columnas reales en la base de datos.

**Columnas verificadas**:
- ✅ `patients.paternal_surname` - Existe y se usa correctamente
- ✅ `patients.first_name` - Existe y se usa correctamente
- ✅ `appointments.date` - Existe y se usa correctamente
- ✅ `appointments.start_time` - Existe y se usa correctamente
- ✅ `availability_blocks.start_date` - Existe y se usa correctamente

---

## 7. PRÓXIMOS PASOS

1. ✅ Endpoints funcionando sin errores SQL
2. ⏳ Crear pruebas Feature (FASE 5)
3. ⏳ Actualizar documentación API (FASE 6)
4. ⏳ Crear CHANGELOG.md (FASE 6)

---

## 8. REFERENCIAS

- Migraciones: `backend/database/migrations/`
- Models: `backend/app/Models/`
- Controllers: `backend/app/Http/Controllers/Api/V1/`
- Resources: `backend/app/Http/Resources/`
- Services: `backend/app/Services/`
- Documentación DB: `docs/database.md`, `docs/database/schema.md`
