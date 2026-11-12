# Changelog - yes-ok Backend Laravel

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased]

### Added - 2026-02-05

#### Factories y Seeders
- ✅ **PatientFactory**: Factory para crear pacientes de prueba con nombres mexicanos, WhatsApp en formato E.164, y estados de verificación
- ✅ **AppointmentFactory**: Factory para crear citas con diferentes estados, fechas futuras/pasadas, y duraciones variables
- ✅ **AvailabilityBlockFactory**: Factory para crear bloqueos de disponibilidad (día completo y parciales)
- ✅ **PatientSeeder**: Crea 28 pacientes de prueba (20 normales, 5 verificados, 3 sin consentimiento)
- ✅ **AppointmentSeeder**: Crea 41 citas de prueba (futuras, pasadas, confirmadas, canceladas, que requieren reagendamiento)
- ✅ **AvailabilityBlockSeeder**: Crea 8 bloqueos de disponibilidad (5 días completos, 3 parciales)
- ✅ **DatabaseSeeder mejorado**: Ahora ejecuta todos los seeders en orden y muestra información detallada

#### Migraciones
- ✅ Corregido orden de migración `soap_note_addendums` (renombrado a `2026_02_05_214758` para ejecutarse después de `soap_notes`)

### Fixed - 2026-02-05

#### Endpoints Corregidos
- ✅ **GET /api/v1/patients**: Corregido error SQL "Column not found: paternal_surname" en `orderBy`
- ✅ **GET /api/v1/appointments**: Verificado que `orderBy('date')` y `orderBy('start_time')` funcionan correctamente
- ✅ **GET /api/v1/availability-blocks**: Corregido error "Table 'availability_blocks' doesn't exist"

#### Migraciones
- ✅ Renombrado `2026_02_05_214755_create_patients_table.php` → `2026_02_05_214754_create_patients_table.php` para ejecutarse antes que `appointments`
- ✅ Eliminados índices duplicados en migraciones:
  - `patients`: Eliminado `->index('whatsapp')` (ya tiene `->unique()`)
  - `patients`: Eliminado `->index('access_token')` duplicado
  - `appointments`: Eliminado `->index('date')` duplicado
  - `appointments`: Eliminado `->index('patient_id')` (ya tiene índice por `foreignId()`)

#### Documentación
- ✅ Creado `docs/knowledge-base.md` con esquema completo de DB, contratos de endpoints, y decisiones técnicas
- ✅ Actualizado `docs/knowledge-base/bugs.md` con bugs resueltos (BUG-007, BUG-008)
- ✅ Creado `CHANGELOG.md`

### Changed - 2026-02-05

#### Estructura de Migraciones
- Migración de `patients` ahora se ejecuta antes que `appointments` para evitar errores de foreign key
- Migración de `soap_note_addendums` ahora se ejecuta después de `soap_notes` para evitar errores de foreign key

#### Credenciales de Admin
- Email del admin: `admin@yes-ok.com`
- Contraseña del admin: `admin123`

---

## [0.1.0] - 2026-02-05

### Added
- Estructura base de Laravel 11
- Migraciones para todas las tablas principales:
  - `users`
  - `patients`
  - `appointments`
  - `availability_blocks`
  - `clinical_histories`
  - `soap_notes`
  - `soap_note_addendums`
  - `notification_logs`
  - `settings`
  - `audit_logs`
- Endpoints API v1:
  - `POST /api/v1/auth/login`
  - `GET /api/v1/auth/me`
  - CRUD completo de `patients`
  - CRUD completo de `appointments`
  - CRUD de `availability-blocks` (index, store, destroy)
- Autenticación JWT con `tymon/jwt-auth`
- Docker Compose para local y staging
- Laravel Horizon para gestión de colas
- Seeders con usuario admin por defecto

### Changed
- Migración de PostgreSQL a MySQL 8.0

---

## Tipos de Cambios

- **Added**: Nueva funcionalidad
- **Changed**: Cambios en funcionalidad existente
- **Deprecated**: Funcionalidad que será removida
- **Removed**: Funcionalidad removida
- **Fixed**: Corrección de bugs
- **Security**: Correcciones de seguridad
