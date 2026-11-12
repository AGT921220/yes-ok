# Features Implementadas - yes-ok

Este documento registra las features implementadas, qué hacen, endpoints relacionados, y flags/env vars necesarios.

> Nota de dominio: en yes-ok la terminologia funcional es **cliente/cita/servicio**.  
> Si aparece `patient/patients`, `SOAP` o `clinical-history`, corresponde a herencia tecnica del modelo previo y debe leerse como `client/notes/registro`.

---

## FEAT-001: Autenticación JWT

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Sistema de autenticación basado en JWT (JSON Web Tokens). Los usuarios se autentican con email/password y reciben un token JWT que se usa en requests subsecuentes.

### Endpoints
- `POST /api/v1/auth/login` - Login con email/password
- `GET /api/v1/auth/me` - Obtener usuario actual

### Implementación Python
- **Archivo**: `python-backend/auth.py`
- **Librería**: `python-jose`
- **Expiración**: Configurable vía `JWT_EXPIRATION_HOURS` (default: 24h)

### Implementación Laravel (Pendiente)
- **Librería**: `tymon/jwt-auth` (ya en composer.json)
- **Configuración**: Pendiente publicar config y generar secret
- **Middleware**: Pendiente crear `JwtMiddleware`

### Variables de Entorno
```env
JWT_SECRET=  # Generar con: php artisan jwt:secret
JWT_TTL=1440  # 24 horas en minutos
```

### Flags/Config
- Token se almacena en `localStorage` del frontend como `yes-ok_token`
- Header: `Authorization: Bearer {token}`

### Referencias
- `python-backend/auth.py`
- `backend/composer.json` - `tymon/jwt-auth`
- `/docs/flows.md` - Flujo de autenticación

---

## FEAT-002: CRUD de Clientes

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Gestion completa de clientes. WhatsApp es el identificador unico. Los clientes tienen nombre separado en 3 campos (first_name, paternal_surname, maternal_surname).

### Endpoints
- `GET /api/v1/clients` - Listar (con búsqueda opcional)
- `POST /api/v1/clients` - Crear cliente
- `GET /api/v1/clients/{id}` - Obtener cliente
- `PUT /api/v1/clients/{id}` - Actualizar cliente
- `DELETE /api/v1/clients/{id}` - Eliminar cliente

### Validaciones
- WhatsApp debe ser único
- WhatsApp debe cumplir formato E.164 (regex: `^\+?[1-9]\d{9,14}$`)
- Nombre requerido (first_name y paternal_surname)

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 206-303
- **Modelo**: `models.Patient`
- **Schema**: `schemas.PatientCreate`, `schemas.PatientUpdate`, `schemas.PatientResponse`

### Implementación Laravel (Pendiente)
- **Model**: Pendiente crear `app/Models/Patient.php`
- **Controller**: Pendiente crear `app/Http/Controllers/Api/V1/PatientController.php`
- **Request**: Pendiente crear `app/Http/Requests/Patient/StorePatientRequest.php`
- **Resource**: Pendiente crear `app/Http/Resources/PatientResource.php`
- **Migration**: Pendiente crear `database/migrations/create_patients_table.php`

### Referencias
- `python-backend/models.py` - Modelo Patient
- `python-backend/schemas.py` - Schemas de Patient
- `/docs/database.md` - Tabla patients

---

## FEAT-003: CRUD de Citas con Validaciones

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Gestion de citas de servicios con validaciones complejas:
- No permitir citas en el pasado
- Validar empalmes (overlap) con otras citas
- Validar bloqueos de disponibilidad
- Estados: PENDIENTE, CONFIRMADA, CANCELADA, NO_SHOW, COMPLETADA, REQUIERE_REAGENDAR

### Endpoints
- `GET /api/v1/appointments` - Listar (filtros: fecha, status, paciente)
- `POST /api/v1/appointments` - Crear cita
- `GET /api/v1/appointments/reschedule-queue` - Citas que requieren reagendarse
- `GET /api/v1/appointments/{id}` - Obtener cita
- `PUT /api/v1/appointments/{id}` - Actualizar cita
- `DELETE /api/v1/appointments/{id}` - Eliminar cita

### Validaciones Críticas
1. **Empalme**: `check_appointment_overlap()` - Verifica que no haya solapamiento de horarios
2. **Bloqueo**: `check_availability_block()` - Verifica que el horario no esté bloqueado
3. **Pasado**: No permitir citas en fechas/horas pasadas

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 51-77 (helpers), 629-768 (endpoints)
- **Modelo**: `models.Appointment`
- **Estados**: `models.AppointmentStatus` enum

### Implementación Laravel (Pendiente)
- **Service**: Pendiente crear `app/Services/AppointmentService.php` con lógica de validación
- **Repository**: Pendiente crear `app/Repositories/AppointmentRepository.php`
- **Controller**: Pendiente crear `app/Http/Controllers/Api/V1/AppointmentController.php`
- **Migration**: Pendiente crear `database/migrations/create_appointments_table.php`

### Referencias
- `python-backend/server.py` - Lógica de validación
- `/docs/flows.md` - Flujo de creación de citas
- `/docs/database.md` - Tabla appointments

---

## FEAT-004: Bloqueos de Disponibilidad

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Permite bloquear disponibilidad (días completos o horarios específicos). Al crear un bloqueo, automáticamente marca las citas afectadas como `REQUIERE_REAGENDAR`.

### Endpoints
- `GET /api/v1/availability-blocks` - Listar (filtros: fechas)
- `POST /api/v1/availability-blocks` - Crear bloqueo
- `DELETE /api/v1/availability-blocks/{id}` - Eliminar bloqueo

### Regla de Negocio
Al crear un bloqueo:
1. Se crea el bloqueo
2. Se buscan citas afectadas (en el rango de fechas, no canceladas/completadas)
3. Si es día completo: todas las citas del rango
4. Si es horario: citas que se solapan con el horario
5. Se marcan como `REQUIERE_REAGENDAR`
6. Se registra log de notificación para cada una

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 65-77 (helper), 771-833 (endpoints)
- **Modelo**: `models.AvailabilityBlock`

### Implementación Laravel (Pendiente)
- **Service**: Pendiente crear `app/Services/AvailabilityBlockService.php`
- **Controller**: Pendiente crear `app/Http/Controllers/Api/V1/AvailabilityBlockController.php`
- **Migration**: Pendiente crear `database/migrations/create_availability_blocks_table.php`

### Referencias
- `python-backend/server.py` - Lógica de marcar citas afectadas
- `/docs/flows.md` - Flujo de bloqueos

---

## FEAT-005: Historia Clínica con Cierre/Desbloqueo

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Historia clínica única por paciente. Puede cerrarse (solo lectura) y desbloquearse (solo ADMIN). Una vez cerrada, no se puede editar hasta que un ADMIN la desbloquee con motivo.

### Endpoints
- `GET /api/v1/clients/{id}/clinical-history` - Obtener historia
- `POST /api/v1/clients/{id}/clinical-history` - Crear historia
- `PUT /api/v1/clients/{id}/clinical-history` - Actualizar (solo si ABIERTA)
- `POST /api/v1/clients/{id}/clinical-history/close` - Cerrar historia
- `POST /api/v1/clients/{id}/clinical-history/unlock` - Desbloquear (solo ADMIN)

### Reglas de Negocio
- Solo una historia por paciente (UNIQUE constraint)
- Si `status = CERRADA`: solo lectura
- Solo ADMIN puede desbloquear
- Desbloqueo requiere motivo (mínimo 10 caracteres)
- Todas las acciones se registran en `audit_logs`

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 842-967
- **Modelo**: `models.ClinicalHistory`
- **Estados**: `models.ClinicalHistoryStatus` enum (ABIERTA, CERRADA)

### Implementación Laravel (Pendiente)
- **Service**: Pendiente crear `app/Services/ClinicalHistoryService.php`
- **Controller**: Pendiente crear `app/Http/Controllers/Api/V1/ClinicalHistoryController.php`
- **Migration**: Pendiente crear `database/migrations/create_clinical_histories_table.php`

### Referencias
- `/docs/flows.md` - Flujo de cierre/desbloqueo
- `/docs/database.md` - Tabla clinical_histories

---

## FEAT-006: Notas SOAP con Firma y Addendums

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Notas médicas SOAP (Subjetivo, Objetivo, Evaluación, Plan). Pueden estar en estado BORRADOR (editable) o FIRMADA (inmutable, solo addendums).

### Endpoints
- `GET /api/v1/clients/{id}/soap-notes` - Listar notas del cliente
- `POST /api/v1/clients/{id}/soap-notes` - Crear nota (BORRADOR)
- `GET /api/v1/soap-notes/{id}` - Obtener nota
- `PUT /api/v1/soap-notes/{id}` - Actualizar (solo si BORRADOR)
- `POST /api/v1/soap-notes/{id}/sign` - Firmar nota
- `POST /api/v1/soap-notes/{id}/addendum` - Agregar addendum (solo si FIRMADA)

### Reglas de Negocio
- Estado inicial: BORRADOR
- Solo se pueden editar si BORRADOR
- Una vez FIRMADA: inmutable, solo se pueden agregar addendums
- La firma registra `signed_at` y `signed_by`
- Todas las acciones se registran en `audit_logs`

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 969-1116
- **Modelos**: `models.SOAPNote`, `models.SOAPNoteAddendum`
- **Estados**: `models.SOAPNoteStatus` enum (BORRADOR, FIRMADA)

### Implementación Laravel (Pendiente)
- **Service**: Pendiente crear `app/Services/SoapNoteService.php`
- **Controller**: Pendiente crear `app/Http/Controllers/Api/V1/SoapNoteController.php`
- **Migrations**: Pendiente crear migraciones para `soap_notes` y `soap_note_addendums`

### Referencias
- `/docs/flows.md` - Flujo de notas SOAP
- `/docs/database.md` - Tablas soap_notes y soap_note_addendums

---

## FEAT-007: Portal Publico de Clientes

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Portal publico donde los clientes pueden agendar, ver, cancelar y reagendar sus citas. Acceso mediante magic links (tokens seguros con expiracion).

### Endpoints Públicos (Sin Auth)
- `GET /api/v1/public/available-slots` - Slots disponibles para una fecha
- `POST /api/v1/public/book` - Reservar cita (público)
- `POST /api/v1/public/patient-access` - Solicitar acceso al portal (genera token)
- `GET /api/v1/public/patient-portal` - Ver portal (requiere token)
- `POST /api/v1/public/patient-portal/cancel/{id}` - Cancelar cita (paciente)
- `POST /api/v1/public/patient-portal/reschedule/{id}` - Reagendar cita (paciente)

### Magic Links
- Token generado con `secrets.token_urlsafe(32)` (Python) o `Str::random(64)` (Laravel)
- Expiración: 24 horas
- Almacenado en `patients.access_token` y `patients.access_token_expires`

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 306-627
- **Helper**: `check_token_expiration()`, `generate_patient_access_token()`

### Implementación Laravel (Pendiente)
- **Service**: Pendiente crear `app/Services/PatientPortalService.php`
- **Controllers**: Pendiente crear `PublicBookingController` y `PublicPatientPortalController`

### Referencias
- `/docs/flows.md` - Flujo de portal público
- `/docs/database.md` - Campo `access_token` en patients

---

## FEAT-008: Reportes y Exportaciones

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Sistema de reportes con estadísticas y exportaciones a Excel y PDF.

### Endpoints
- `GET /api/v1/reports/appointments-stats` - Estadísticas de citas
- `GET /api/v1/reports/pending-soap` - Pacientes pendientes de SOAP
- `GET /api/v1/reports/export/excel` - Exportar Excel
- `GET /api/v1/reports/export/pdf` - Exportar PDF

### Regla de SOAP Pendiente
- Obtiene `SOAP_NOTES_INTERVAL` de settings (default: 3)
- Para cada paciente: `expected_soaps = completed_appointments / interval`
- Si `soap_count < expected_soaps` → PENDIENTE

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 1158-1333
- **Librerías**: `openpyxl` (Excel), `reportlab` (PDF)

### Implementación Laravel (Pendiente)
- **Librerías**: `maatwebsite/excel` (Excel), `dompdf/dompdf` (PDF) - ya en composer.json
- **Service**: Pendiente crear `app/Services/ReportService.php`
- **Controller**: Pendiente crear `app/Http/Controllers/Api/V1/ReportController.php`

### Referencias
- `/docs/flows.md` - Flujo de reportes
- `python-backend/server.py` - Lógica de exportación

---

## FEAT-009: Sistema de Configuración

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Sistema de configuración key-value. Solo ADMIN puede actualizar.

### Endpoints
- `GET /api/v1/settings` - Listar todas las configuraciones
- `PUT /api/v1/settings/{key}` - Actualizar configuración (solo ADMIN)

### Settings Comunes
- `appointment_duration`: Duración default de citas (minutos)
- `buffer_minutes`: Buffer entre citas
- `soap_interval`: Citas entre notas SOAP (default: 3)
- `reminder_24h`: Recordatorio 24h antes (true/false)
- `reminder_2h`: Recordatorio 2h antes (true/false)

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 47-49 (helper), 1131-1156 (endpoints)
- **Modelo**: `models.Settings`

### Implementación Laravel (Pendiente)
- **Model**: Pendiente crear `app/Models/Setting.php`
- **Controller**: Pendiente crear `app/Http/Controllers/Api/V1/SettingController.php`
- **Migration**: Pendiente crear `database/migrations/create_settings_table.php`

### Referencias
- `/docs/database.md` - Tabla settings

---

## FEAT-010: Logs de Notificaciones (Sandbox)

**Fecha Implementada**: Pendiente (Backend Python existe, Laravel no)  
**Estado**: ⏳ En Migración

### Descripción
Sistema de logs de notificaciones. Actualmente en modo sandbox (solo registra, no envía). Preparado para integración futura con WhatsApp/SMS.

### Endpoints
- `GET /api/v1/notification-logs` - Listar logs (filtro: patient_id)

### Función Interna
- `log_notification(patient_id, appointment_id, channel, template, message)` - Registra log sin enviar

### Implementación Python
- **Archivo**: `python-backend/server.py` líneas 79-91 (función), 1118-1129 (endpoint)
- **Modelo**: `models.NotificationLog`
- **Status**: Siempre "SANDBOX" actualmente

### Implementación Laravel (Pendiente)
- **Service**: Pendiente crear `app/Services/NotificationService.php`
- **Controller**: Pendiente crear `app/Http/Controllers/Api/V1/NotificationLogController.php`
- **Migration**: Pendiente crear `database/migrations/create_notification_logs_table.php`

### Referencias
- `/docs/database.md` - Tabla notification_logs
- DEC-010 - Decisión de modo sandbox

---

## Cómo Agregar Nuevas Features

1. Agregar entrada con formato:
   - **FEAT-XXX**: Número secuencial
   - **Fecha Implementada**: Cuándo se implementó
   - **Estado**: ✅ Implementada / ⏳ En Migración / ❌ Pendiente
   - **Descripción**: Qué hace la feature
   - **Endpoints**: Lista de endpoints relacionados
   - **Reglas de Negocio**: Si aplica
   - **Implementación**: Dónde está implementada (Python/Laravel)
   - **Variables/Flags**: Si aplica
   - **Referencias**: Archivos/documentos relacionados

2. Actualizar este documento inmediatamente al implementar una feature.

3. Si la feature está relacionada con una decisión, referenciar en `/docs/knowledge-base/decisions.md`.
