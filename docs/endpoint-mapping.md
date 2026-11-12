# Mapeo de Endpoints: Python (FastAPI) -> Laravel

## Análisis del Backend Python Actual

### Stack Tecnológico
- **Framework**: FastAPI (Python)
- **Base de Datos**: PostgreSQL
- **ORM**: SQLAlchemy
- **Autenticación**: JWT (python-jose)
- **Validación**: Pydantic
- **Exportación**: ReportLab (PDF), openpyxl (Excel)

### Estructura de Archivos
```
backend/
├── server.py      # Todos los endpoints (1434 líneas)
├── models.py      # Modelos SQLAlchemy
├── schemas.py     # DTOs Pydantic
├── auth.py        # JWT y middleware
└── database.py   # Configuración DB
```

### Base de Datos
- **PostgreSQL** (confirmado en database.py)
- 11 tablas principales: users, patients, appointments, availability_blocks, clinical_histories, soap_notes, soap_note_addendums, notification_logs, settings, audit_logs

---

## Mapeo Completo de Endpoints

### 1. AUTENTICACIÓN (`/api/v1/auth`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| POST | `/api/v1/auth/login` | `POST /api/v1/auth/login` | No | Login con email/password, retorna JWT |
| GET | `/api/v1/auth/me` | `GET /api/v1/auth/me` | Sí | Obtener usuario actual |

**Request/Response:**
- Login: `{email, password}` -> `{access_token, token_type, user}`
- Me: Headers `Authorization: Bearer {token}` -> `UserResponse`

**Reglas de Negocio:**
- Verificar password con bcrypt
- Usuario debe estar activo
- Token JWT con expiración configurable (default 24h)

---

### 2. USUARIOS (`/api/v1/users`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Rol Requerido |
|--------|----------------|------------------|------|---------------|
| GET | `/api/v1/users` | `GET /api/v1/users` | Sí | ADMIN |
| POST | `/api/v1/users` | `POST /api/v1/users` | Sí | ADMIN |
| PUT | `/api/v1/users/{id}` | `PUT /api/v1/users/{id}` | Sí | ADMIN |
| DELETE | `/api/v1/users/{id}` | `DELETE /api/v1/users/{id}` | Sí | ADMIN |

**Reglas de Negocio:**
- Email único
- No se puede eliminar a uno mismo
- Roles: ADMIN, ASISTENTE

---

### 3. CLIENTES (`/api/v1/clients`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| GET | `/api/v1/clients?search=` | `GET /api/v1/clients` | Sí | Listar con búsqueda opcional |
| POST | `/api/v1/clients` | `POST /api/v1/clients` | Sí | Crear cliente |
| GET | `/api/v1/clients/{id}` | `GET /api/v1/clients/{id}` | Sí | Obtener cliente |
| PUT | `/api/v1/clients/{id}` | `PUT /api/v1/clients/{id}` | Sí | Actualizar cliente |
| DELETE | `/api/v1/clients/{id}` | `DELETE /api/v1/clients/{id}` | Sí | Eliminar cliente |

**Campos Importantes:**
- `first_name`, `paternal_surname`, `maternal_surname` (opcional)
- `whatsapp` (único, formato E.164)
- `email` (opcional)
- `consent_notifications` (boolean)
- `otp_verified` (boolean)
- `access_token`, `access_token_expires` (para portal paciente)

**Reglas de Negocio:**
- WhatsApp es único (identificador principal)
- Validación E.164 para WhatsApp
- Búsqueda por nombre o WhatsApp

---

### 4. CITAS (`/api/v1/appointments`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| GET | `/api/v1/appointments?start_date=&end_date=&status=&patient_id=` | `GET /api/v1/appointments` | Sí | Listar con filtros |
| GET | `/api/v1/appointments/reschedule-queue` | `GET /api/v1/appointments/reschedule-queue` | Sí | Cola de reagendamiento |
| POST | `/api/v1/appointments` | `POST /api/v1/appointments` | Sí | Crear cita |
| GET | `/api/v1/appointments/{id}` | `GET /api/v1/appointments/{id}` | Sí | Obtener cita |
| PUT | `/api/v1/appointments/{id}` | `PUT /api/v1/appointments/{id}` | Sí | Actualizar cita |
| DELETE | `/api/v1/appointments/{id}` | `DELETE /api/v1/appointments/{id}` | Sí | Eliminar cita |

**Estados:** PENDIENTE, CONFIRMADA, CANCELADA, NO_SHOW, COMPLETADA, REQUIERE_REAGENDAR

**Reglas de Negocio Críticas:**
- No crear citas en el pasado
- Validar empalmes (overlap) con otras citas (excluyendo canceladas)
- Validar contra bloques de disponibilidad
- Al crear bloqueo, marcar citas afectadas como REQUIERE_REAGENDAR
- Log de notificaciones (sandbox) al crear

---

### 5. BLOQUEOS DE DISPONIBILIDAD (`/api/v1/availability-blocks`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| GET | `/api/v1/availability-blocks?start_date=&end_date=` | `GET /api/v1/availability-blocks` | Sí | Listar con filtros |
| POST | `/api/v1/availability-blocks` | `POST /api/v1/availability-blocks` | Sí | Crear bloqueo |
| DELETE | `/api/v1/availability-blocks/{id}` | `DELETE /api/v1/availability-blocks/{id}` | Sí | Eliminar bloqueo |

**Reglas de Negocio:**
- `is_full_day`: bloquea todo el día
- Si no es full_day, requiere `start_time` y `end_time`
- Al crear, marca citas afectadas como REQUIERE_REAGENDAR
- Log de notificaciones a pacientes afectados

---

### 6. HISTORIA CLÍNICA (`/api/v1/clients/{id}/clinical-history`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| GET | `/api/v1/clients/{id}/clinical-history` | `GET /api/v1/clients/{id}/clinical-history` | Sí | Obtener historia |
| POST | `/api/v1/clients/{id}/clinical-history` | `POST /api/v1/clients/{id}/clinical-history` | Sí | Crear historia |
| PUT | `/api/v1/clients/{id}/clinical-history` | `PUT /api/v1/clients/{id}/clinical-history` | Sí | Actualizar (solo si ABIERTA) |
| POST | `/api/v1/clients/{id}/clinical-history/close` | `POST /api/v1/clients/{id}/clinical-history/close` | Sí | Cerrar historia |
| POST | `/api/v1/clients/{id}/clinical-history/unlock?reason=` | `POST /api/v1/clients/{id}/clinical-history/unlock` | Sí (ADMIN) | Desbloquear historia |

**Estados:** ABIERTA, CERRADA

**Reglas de Negocio:**
- Una historia por paciente (unique constraint)
- No se puede editar si está CERRADA
- Solo ADMIN puede desbloquear
- Requiere razón para desbloquear (mínimo 10 caracteres)
- Audit log al cerrar/desbloquear

**Campos:** chief_complaint, history_present_illness, past_psychiatric_history, past_medical_history, family_history, social_history, medications, allergies, mental_status_exam, diagnosis, treatment_plan

---

### 7. NOTAS SOAP (`/api/v1/clients/{id}/soap-notes` y `/api/v1/soap-notes/{id}`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| GET | `/api/v1/clients/{id}/soap-notes` | `GET /api/v1/clients/{id}/soap-notes` | Sí | Listar notas del cliente |
| POST | `/api/v1/clients/{id}/soap-notes?appointment_id=` | `POST /api/v1/clients/{id}/soap-notes` | Sí | Crear nota |
| GET | `/api/v1/soap-notes/{id}` | `GET /api/v1/soap-notes/{id}` | Sí | Obtener nota con addendums |
| PUT | `/api/v1/soap-notes/{id}` | `PUT /api/v1/soap-notes/{id}` | Sí | Actualizar (solo si BORRADOR) |
| POST | `/api/v1/soap-notes/{id}/sign` | `POST /api/v1/soap-notes/{id}/sign` | Sí | Firmar nota |
| POST | `/api/v1/soap-notes/{id}/addendum` | `POST /api/v1/soap-notes/{id}/addendum` | Sí | Agregar addendum (solo si FIRMADA) |

**Estados:** BORRADOR, FIRMADA

**Reglas de Negocio:**
- No se puede editar si está FIRMADA
- Solo se pueden agregar addendums a notas FIRMADAS
- Audit log al firmar y agregar addendum
- Campos: subjective, objective, assessment, plan

---

### 8. ENDPOINTS PÚBLICOS (`/api/v1/public`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| GET | `/api/v1/public/available-slots?target_date=` | `GET /api/v1/public/available-slots` | No | Obtener slots disponibles |
| POST | `/api/v1/public/book` | `POST /api/v1/public/book` | No | Reservar cita (auto-booking) |
| POST | `/api/v1/public/patient-access` | `POST /api/v1/public/patient-access` | No | Solicitar acceso al portal |
| GET | `/api/v1/public/patient-portal?token=` | `GET /api/v1/public/patient-portal` | No | Obtener datos del portal |
| POST | `/api/v1/public/patient-portal/cancel/{id}?token=` | `POST /api/v1/public/patient-portal/cancel/{id}` | No | Cancelar cita desde portal |
| POST | `/api/v1/public/patient-portal/reschedule/{id}?token=&new_date=&new_start_time=&new_end_time=` | `POST /api/v1/public/patient-portal/reschedule/{id}` | No | Reagendar desde portal |

**Reglas de Negocio Críticas:**
- `available-slots`: Calcula slots basado en duración, buffer, horarios laborales, citas existentes y bloques
- `book`: Crea o encuentra paciente por WhatsApp, valida overlap y bloques
- `patient-access`: Genera token seguro (24h), log en sandbox
- `patient-portal`: Token válido y no expirado, NO incluye datos clínicos
- Validaciones de overlap y bloques igual que citas internas

---

### 9. REPORTES (`/api/v1/reports`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| GET | `/api/v1/reports/appointments-stats?start_date=&end_date=` | `GET /api/v1/reports/appointments-stats` | Sí | Estadísticas de citas |
| GET | `/api/v1/reports/pending-soap` | `GET /api/v1/reports/pending-soap` | Sí | Pacientes pendientes de SOAP |
| GET | `/api/v1/reports/export/excel?start_date=&end_date=&status=` | `GET /api/v1/reports/export/excel` | Sí | Exportar Excel |
| GET | `/api/v1/reports/export/pdf?start_date=&end_date=&status=` | `GET /api/v1/reports/export/pdf` | Sí | Exportar PDF |

**Reglas de Negocio:**
- `appointments-stats`: Total, por estado, tasa cancelación, tasa no-show
- `pending-soap`: Basado en `soap_interval` (setting), compara citas completadas vs notas SOAP
- Exportaciones: Filtros opcionales, formato específico

---

### 10. CONFIGURACIÓN (`/api/v1/settings`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Rol Requerido |
|--------|----------------|------------------|------|---------------|
| GET | `/api/v1/settings` | `GET /api/v1/settings` | Sí | - |
| PUT | `/api/v1/settings/{key}` | `PUT /api/v1/settings/{key}` | Sí | ADMIN |

**Settings Clave:**
- `appointment_duration` (default: 45)
- `buffer_minutes` (default: 15)
- `soap_interval` (default: 3)
- `reminder_24h`, `reminder_2h` (boolean)

---

### 11. LOGS DE NOTIFICACIONES (`/api/v1/notification-logs`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| GET | `/api/v1/notification-logs?patient_id=&limit=` | `GET /api/v1/notification-logs` | Sí | Listar logs (sandbox) |

**Nota:** Modo sandbox - solo logs, no envía realmente

---

### 12. SEED (`/api/v1/seed`)

| Método | Endpoint Python | Endpoint Laravel | Auth | Descripción |
|--------|----------------|------------------|------|-------------|
| POST | `/api/v1/seed` | `POST /api/v1/seed` | No | Poblar BD con datos demo |

---

## Dependencias y Configuración

### Variables de Entorno Python
- `DATABASE_URL`: PostgreSQL connection string
- `JWT_SECRET`: Secret para JWT
- `JWT_ALGORITHM`: HS256
- `JWT_EXPIRATION_HOURS`: 24
- `CORS_ORIGINS`: Orígenes permitidos

### Base de Datos
- **PostgreSQL** (confirmado)
- Timezone: UTC (almacenamiento), America/Monterrey (presentación)

---

## Priorización para Implementación Laravel

### P0 (Crítico - Frontend depende de estos)
1. ✅ Auth (login, me)
2. ✅ Patients (CRUD completo)
3. ✅ Appointments (CRUD + reschedule-queue)
4. ✅ AvailabilityBlocks (CRUD)
5. ✅ ClinicalHistory (CRUD + close/unlock)
6. ✅ SOAPNotes (CRUD + sign + addendum)
7. ✅ Settings (list, update)
8. ✅ Reports (stats, pending-soap, export)

### P1 (Importante - Funcionalidad pública)
9. ✅ Public endpoints (available-slots, book, patient-portal)

### P2 (Menor prioridad)
10. ✅ Users (solo admin)
11. ✅ Notification logs (solo lectura)
12. ✅ Seed (desarrollo)

---

## Notas de Implementación Laravel

### Autenticación
- Usar Laravel Sanctum o JWT (replicar comportamiento actual)
- Middleware para roles (ADMIN, ASISTENTE)
- Token en header: `Authorization: Bearer {token}`

### Validación
- FormRequest para cada endpoint
- Validación de WhatsApp (E.164)
- Validación de overlap de citas
- Validación de bloques de disponibilidad

### Respuestas
- Usar Resources de Laravel para formatear respuestas
- Mantener compatibilidad de nombres de campos con Python
- Status codes HTTP iguales

### Reglas de Negocio
- UseCases para lógica compleja (overlap, bloques, etc.)
- Repositories para acceso a DB
- Events/Listeners para notificaciones (sandbox = log)

### Exportaciones
- Excel: Laravel Excel (Maatwebsite)
- PDF: DomPDF o similar

---

## Compatibilidad Frontend

El frontend espera:
- Base URL: `/api/v1`
- Autenticación: `Authorization: Bearer {token}`
- Respuestas JSON con estructura específica
- Status codes HTTP estándar

**IMPORTANTE:** Mantener compatibilidad total con las respuestas actuales del backend Python para no romper el frontend.
