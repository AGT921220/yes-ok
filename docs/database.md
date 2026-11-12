# yes-ok - Base de Datos

## Modelo Mental de Datos

yes-ok gestiona la relación entre **Usuarios** (staff), **Clientes**, **Citas/Reservas**, y el catálogo de **Servicios** con disponibilidad y notificaciones.

## Diagrama de Relaciones

```
users (usuarios internos)
  │
  ├──< appointments (created_by)
  ├──< availability_blocks (created_by)
  ├──< clinical_histories (closed_by, unlocked_by)
  ├──< soap_notes (created_by, signed_by)
  ├──< soap_note_addendums (created_by)
  ├──< settings (updated_by)
  └──< audit_logs (user_id)

patients (pacientes)
  │
  ├──< appointments (patient_id)
  ├──1 clinical_histories (patient_id, UNIQUE)
  ├──< soap_notes (patient_id)
  └──< notification_logs (patient_id)

appointments (citas)
  │
  ├──< soap_notes (appointment_id)
  └──< notification_logs (appointment_id)
```

## Tablas Principales

### users

Usuarios internos del sistema (médicos y asistentes).

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| name | VARCHAR(255) | NOT NULL | Nombre completo |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Email (login) |
| password | VARCHAR(255) | NOT NULL | Hash bcrypt |
| role | ENUM('ADMIN', 'ASISTENTE') | DEFAULT 'ASISTENTE' | Rol del usuario |
| is_active | BOOLEAN | DEFAULT TRUE | Usuario activo |
| created_at | TIMESTAMP | NULL | Fecha creación |
| updated_at | TIMESTAMP | NULL | Fecha actualización |

**Índices**: `email` (UNIQUE)

### patients

Pacientes del sistema. WhatsApp es el identificador único.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| first_name | VARCHAR(255) | NOT NULL | Nombre(s) |
| paternal_surname | VARCHAR(255) | NOT NULL | Apellido Paterno |
| maternal_surname | VARCHAR(255) | NULLABLE | Apellido Materno |
| whatsapp | VARCHAR(20) | UNIQUE, NOT NULL | WhatsApp (E.164) |
| email | VARCHAR(255) | NULLABLE | Email opcional |
| consent_notifications | BOOLEAN | DEFAULT TRUE | Consentimiento notificaciones |
| otp_verified | BOOLEAN | DEFAULT FALSE | OTP verificado |
| access_token | VARCHAR(255) | NULLABLE | Token para portal (magic link) |
| access_token_expires | TIMESTAMP | NULLABLE | Expiración del token |
| created_at | TIMESTAMP | NULL | Fecha creación |
| updated_at | TIMESTAMP | NULL | Fecha actualización |

**Índices**: `whatsapp` (UNIQUE), `access_token` (para búsquedas)

**Nota**: El nombre completo se calcula como `first_name + paternal_surname + maternal_surname` (si existe).

### appointments

Citas médicas. Relaciona pacientes con usuarios (creadores).

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| patient_id | BIGINT UNSIGNED | FK patients.id, NOT NULL | Paciente |
| date | DATE | NOT NULL | Fecha de la cita |
| start_time | TIME | NOT NULL | Hora inicio |
| end_time | TIME | NOT NULL | Hora fin |
| status | ENUM | DEFAULT 'PENDIENTE' | Estado de la cita |
| notes | TEXT | NULLABLE | Notas adicionales |
| reason | VARCHAR(500) | NULLABLE | Motivo de la cita |
| created_by | BIGINT UNSIGNED | FK users.id, NULLABLE | Usuario que creó |
| booked_by_patient | BOOLEAN | DEFAULT FALSE | Agendada por paciente |
| created_at | TIMESTAMP | NULL | Fecha creación |
| updated_at | TIMESTAMP | NULL | Fecha actualización |

**Estados**: `PENDIENTE`, `CONFIRMADA`, `CANCELADA`, `NO_SHOW`, `COMPLETADA`, `REQUIERE_REAGENDAR`

**Índices**: `date` (para búsquedas por fecha), `patient_id` (FK), `status` (para filtros)

### availability_blocks

Bloqueos de disponibilidad (vacaciones, días libres, etc.).

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| start_date | DATE | NOT NULL | Fecha inicio |
| end_date | DATE | NOT NULL | Fecha fin |
| start_time | TIME | NULLABLE | Hora inicio (si parcial) |
| end_time | TIME | NULLABLE | Hora fin (si parcial) |
| is_full_day | BOOLEAN | DEFAULT TRUE | Bloqueo día completo |
| reason | VARCHAR(500) | NULLABLE | Razón del bloqueo |
| is_recurring | BOOLEAN | DEFAULT FALSE | Es recurrente |
| recurrence_pattern | VARCHAR(50) | NULLABLE | Patrón de recurrencia |
| created_by | BIGINT UNSIGNED | FK users.id, NOT NULL | Usuario que creó |
| created_at | TIMESTAMP | NULL | Fecha creación |

**Índices**: `start_date`, `end_date` (para búsquedas por rango)

**Regla de Negocio**: Al crear un bloqueo, todas las citas afectadas se marcan como `REQUIERE_REAGENDAR`.

### clinical_histories

Historia clínica del paciente. Una por paciente (UNIQUE constraint).

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| patient_id | BIGINT UNSIGNED | FK patients.id, UNIQUE, NOT NULL | Paciente |
| status | ENUM('ABIERTA', 'CERRADA') | DEFAULT 'ABIERTA' | Estado |
| chief_complaint | TEXT | NULLABLE | Motivo de consulta |
| history_present_illness | TEXT | NULLABLE | Historia enfermedad actual |
| past_psychiatric_history | TEXT | NULLABLE | Antecedentes psiquiátricos |
| past_medical_history | TEXT | NULLABLE | Antecedentes médicos |
| family_history | TEXT | NULLABLE | Antecedentes familiares |
| social_history | TEXT | NULLABLE | Antecedentes sociales |
| medications | TEXT | NULLABLE | Medicamentos |
| allergies | TEXT | NULLABLE | Alergias |
| mental_status_exam | TEXT | NULLABLE | Examen estado mental |
| diagnosis | TEXT | NULLABLE | Diagnóstico |
| treatment_plan | TEXT | NULLABLE | Plan de tratamiento |
| closed_at | TIMESTAMP | NULLABLE | Fecha cierre |
| closed_by | BIGINT UNSIGNED | FK users.id, NULLABLE | Usuario que cerró |
| unlock_reason | TEXT | NULLABLE | Motivo desbloqueo |
| unlocked_at | TIMESTAMP | NULLABLE | Fecha desbloqueo |
| unlocked_by | BIGINT UNSIGNED | FK users.id, NULLABLE | Usuario que desbloqueó |
| created_at | TIMESTAMP | NULL | Fecha creación |
| updated_at | TIMESTAMP | NULL | Fecha actualización |

**Regla de Negocio**: Si `status = 'CERRADA'`, la historia es solo lectura. Solo ADMIN puede desbloquear.

### soap_notes

Notas SOAP (Subjetivo, Objetivo, Evaluación, Plan).

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| patient_id | BIGINT UNSIGNED | FK patients.id, NOT NULL | Paciente |
| appointment_id | BIGINT UNSIGNED | FK appointments.id, NULLABLE | Cita asociada |
| subjective | TEXT | NULLABLE | Subjetivo |
| objective | TEXT | NULLABLE | Objetivo |
| assessment | TEXT | NULLABLE | Evaluación |
| plan | TEXT | NULLABLE | Plan |
| status | ENUM('BORRADOR', 'FIRMADA') | DEFAULT 'BORRADOR' | Estado |
| signed_at | TIMESTAMP | NULLABLE | Fecha firma |
| signed_by | BIGINT UNSIGNED | FK users.id, NULLABLE | Usuario que firmó |
| created_by | BIGINT UNSIGNED | FK users.id, NOT NULL | Usuario que creó |
| created_at | TIMESTAMP | NULL | Fecha creación |
| updated_at | TIMESTAMP | NULL | Fecha actualización |

**Regla de Negocio**: Solo se pueden editar si `status = 'BORRADOR'`. Una vez `FIRMADA`, solo se pueden agregar addendums.

### soap_note_addendums

Addendums (correcciones) a notas SOAP firmadas.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| soap_note_id | BIGINT UNSIGNED | FK soap_notes.id, NOT NULL | Nota SOAP |
| content | TEXT | NOT NULL | Contenido del addendum |
| created_by | BIGINT UNSIGNED | FK users.id, NOT NULL | Usuario que creó |
| created_at | TIMESTAMP | NULL | Fecha creación |

### notification_logs

Logs de notificaciones (modo sandbox actualmente).

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| patient_id | BIGINT UNSIGNED | FK patients.id, NOT NULL | Paciente |
| appointment_id | BIGINT UNSIGNED | FK appointments.id, NULLABLE | Cita asociada |
| channel | VARCHAR(20) | NOT NULL | Canal (WHATSAPP, SMS) |
| template_name | VARCHAR(100) | NULLABLE | Nombre del template |
| message | TEXT | NOT NULL | Mensaje |
| status | VARCHAR(20) | DEFAULT 'SANDBOX' | Estado (SANDBOX, SENT, FAILED) |
| sent_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha envío |
| error_message | TEXT | NULLABLE | Mensaje de error |

### settings

Configuraciones del sistema (key-value).

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| key | VARCHAR(100) | UNIQUE, NOT NULL | Clave |
| value | TEXT | NOT NULL | Valor |
| updated_at | TIMESTAMP | NULL | Fecha actualización |
| updated_by | BIGINT UNSIGNED | FK users.id, NULLABLE | Usuario que actualizó |

**Settings comunes**:
- `appointment_duration`: Duración default de citas (minutos)
- `buffer_minutes`: Buffer entre citas
- `soap_interval`: Citas entre notas SOAP (default: 3)
- `reminder_24h`: Recordatorio 24h antes (true/false)
- `reminder_2h`: Recordatorio 2h antes (true/false)

### audit_logs

Logs de auditoría (acciones importantes).

| Campo | Tipo | Restricciones | Descripción |
|-------|------|--------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | ID único |
| user_id | BIGINT UNSIGNED | FK users.id, NULLABLE | Usuario (null si acción de paciente) |
| action | VARCHAR(100) | NOT NULL | Acción realizada |
| entity_type | VARCHAR(50) | NOT NULL | Tipo de entidad |
| entity_id | BIGINT UNSIGNED | NOT NULL | ID de la entidad |
| details | TEXT | NULLABLE | Detalles adicionales |
| ip_address | VARCHAR(50) | NULLABLE | IP del request |
| created_at | TIMESTAMP | NULL | Fecha creación |

**Acciones comunes**:
- `CREATE_APPOINTMENT`
- `UPDATE_APPOINTMENT`
- `CANCEL_APPOINTMENT`
- `CLOSE_CLINICAL_HISTORY`
- `UNLOCK_CLINICAL_HISTORY`
- `SIGN_SOAP_NOTE`
- `ADD_SOAP_ADDENDUM`

## Migraciones

### Estado Actual

- ✅ `0001_01_01_000000_create_users_table.php` - Creada
- ✅ `0001_01_01_000001_create_cache_table.php` - Creada (Laravel)
- ✅ `0001_01_01_000002_create_jobs_table.php` - Creada (Laravel)
- ❌ `create_patients_table.php` - **Falta**
- ❌ `create_appointments_table.php` - **Falta**
- ❌ `create_availability_blocks_table.php` - **Falta**
- ❌ `create_clinical_histories_table.php` - **Falta**
- ❌ `create_soap_notes_table.php` - **Falta**
- ❌ `create_soap_note_addendums_table.php` - **Falta**
- ❌ `create_notification_logs_table.php` - **Falta**
- ❌ `create_settings_table.php` - **Falta**
- ❌ `create_audit_logs_table.php` - **Falta**

**Total**: 3/12 migraciones creadas (25%)

## Relaciones Importantes

### One-to-Many

- `User` → `Appointment` (created_by)
- `User` → `AvailabilityBlock` (created_by)
- `Patient` → `Appointment` (patient_id)
- `Patient` → `SOAPNote` (patient_id)
- `SOAPNote` → `SOAPNoteAddendum` (soap_note_id)

### One-to-One

- `Patient` → `ClinicalHistory` (patient_id, UNIQUE)

### Many-to-Many

No hay relaciones many-to-many directas.

## Índices Recomendados

```sql
-- Appointments
CREATE INDEX idx_appointments_date ON appointments(date);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_appointments_date_status ON appointments(date, status);

-- Patients
CREATE INDEX idx_patients_whatsapp ON patients(whatsapp);
CREATE INDEX idx_patients_access_token ON patients(access_token);

-- Availability Blocks
CREATE INDEX idx_availability_blocks_dates ON availability_blocks(start_date, end_date);

-- SOAP Notes
CREATE INDEX idx_soap_notes_patient ON soap_notes(patient_id);
CREATE INDEX idx_soap_notes_appointment ON soap_notes(appointment_id);

-- Audit Logs
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);
```

## Configuraciones Importantes

- **Timezone**: `America/Monterrey` (GMT-6)
- **Formato fechas**: `dd/mm/yyyy` 24h
- **SOAP interval**: Configurable (default: cada 3 citas)
- **JWT TTL**: 1440 minutos (24 horas)

## Referencia Completa

Para el esquema completo con todos los campos, ver `/docs/database/schema.md`.
