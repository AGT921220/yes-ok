# TODO y Deuda Técnica - yes-ok

Este documento registra tareas pendientes, deuda técnica, y mejoras futuras priorizadas.

> Nota de dominio: usar lenguaje funcional de yes-ok (`clientes`, `citas`, `servicios`).  
> Referencias a `patients`, `clinical-history` o `SOAP` son tecnicas y quedan como deuda de renombre interno.

---

## P0 - CRÍTICO (Hacer Primero)

### TODO-001: Implementar Autenticación JWT en Laravel
**Prioridad**: P0  
**Impacto**: Frontend no puede autenticarse  
**Estimación**: 2-3 horas

**Tareas**:
- [ ] Publicar configuración JWT: `php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"`
- [ ] Generar secret: `php artisan jwt:secret`
- [ ] Crear middleware `JwtMiddleware`
- [ ] Crear `AuthController@login` (POST `/api/v1/auth/login`)
- [ ] Crear `AuthController@me` (GET `/api/v1/auth/me`)
- [ ] Configurar rutas en `routes/api.php`
- [ ] Probar con frontend

**Referencias**:
- `python-backend/auth.py`
- `python-backend/server.py` líneas 115-137
- `/docs/knowledge-base/features.md` - FEAT-001

---

### TODO-002: Crear Migraciones Faltantes
**Prioridad**: P0  
**Impacto**: No se puede crear estructura de base de datos  
**Estimación**: 3-4 horas

**Tareas**:
- [ ] `create_patients_table.php`
- [ ] `create_appointments_table.php`
- [ ] `create_availability_blocks_table.php`
- [ ] `create_clinical_histories_table.php`
- [ ] `create_soap_notes_table.php`
- [ ] `create_soap_note_addendums_table.php`
- [ ] `create_notification_logs_table.php`
- [ ] `create_settings_table.php`
- [ ] `create_audit_logs_table.php`
- [ ] Ejecutar todas: `php artisan migrate`

**Referencias**:
- `/docs/database.md` - Esquema completo
- `/docs/database/schema.md` - Detalles de tablas
- `python-backend/models.py` - Modelos de referencia

---

### TODO-003: Implementar CRUD de Clientes
**Prioridad**: P0  
**Impacto**: Frontend no puede gestionar clientes  
**Estimación**: 4-5 horas

**Tareas**:
- [ ] Crear `app/Models/Patient.php`
- [ ] Crear `app/Enums/UserRole.php` (si no existe)
- [ ] Crear `app/Http/Controllers/Api/V1/PatientController.php`
- [ ] Crear `app/Http/Requests/Patient/StorePatientRequest.php` (validar WhatsApp E.164)
- [ ] Crear `app/Http/Requests/Patient/UpdatePatientRequest.php`
- [ ] Crear `app/Http/Resources/PatientResource.php`
- [ ] Crear `app/Repositories/PatientRepository.php` (opcional, puede usar Model directamente)
- [ ] Agregar rutas en `routes/api.php`
- [ ] Probar endpoints con Postman/Insomnia

**Referencias**:
- `python-backend/server.py` líneas 206-303
- `/docs/knowledge-base/features.md` - FEAT-002

---

### TODO-004: Implementar CRUD de Citas con Validaciones
**Prioridad**: P0  
**Impacto**: Core del sistema no funciona  
**Estimación**: 6-8 horas

**Tareas**:
- [ ] Crear `app/Models/Appointment.php`
- [ ] Crear `app/Enums/AppointmentStatus.php`
- [ ] Crear `app/Services/AppointmentService.php` con:
  - [ ] `checkOverlap()` - Validar empalmes
  - [ ] `checkAvailabilityBlock()` - Validar bloqueos
  - [ ] `create()` - Crear cita con validaciones
  - [ ] `update()` - Actualizar con validaciones
- [ ] Crear `app/Repositories/AppointmentRepository.php`
- [ ] Crear `app/Http/Controllers/Api/V1/AppointmentController.php`
- [ ] Crear `app/Http/Requests/Appointment/StoreAppointmentRequest.php`
- [ ] Crear `app/Http/Requests/Appointment/UpdateAppointmentRequest.php`
- [ ] Crear `app/Http/Resources/AppointmentResource.php`
- [ ] Agregar rutas (incluye `reschedule-queue`)
- [ ] Probar validaciones críticas

**Referencias**:
- `python-backend/server.py` líneas 51-77 (helpers), 629-768 (endpoints)
- `/docs/knowledge-base/features.md` - FEAT-003
- `/docs/flows.md` - Flujo de creación

---

### TODO-005: Implementar Bloqueos de Disponibilidad
**Prioridad**: P0  
**Impacto**: No se pueden bloquear horarios  
**Estimación**: 4-5 horas

**Tareas**:
- [ ] Crear `app/Models/AvailabilityBlock.php`
- [ ] Crear `app/Services/AvailabilityBlockService.php` con:
  - [ ] `create()` - Crear bloqueo y marcar citas afectadas
  - [ ] Lógica de marcar citas como `REQUIERE_REAGENDAR`
- [ ] Crear `app/Http/Controllers/Api/V1/AvailabilityBlockController.php`
- [ ] Crear `app/Http/Resources/AvailabilityBlockResource.php`
- [ ] Agregar rutas
- [ ] Probar que las citas se marcan correctamente

**Referencias**:
- `python-backend/server.py` líneas 785-833
- `/docs/knowledge-base/features.md` - FEAT-004
- `/docs/flows.md` - Flujo de bloqueos

---

## P1 - ALTO (Hacer Después de P0)

### TODO-006: Configurar Laravel Horizon
**Prioridad**: P1  
**Impacto**: Colas no funcionan, dashboard no accesible  
**Estimación**: 1 hora

**Tareas**:
- [ ] Publicar configuración: `php artisan vendor:publish --tag=horizon-config`
- [ ] Instalar assets: `php artisan horizon:install`
- [ ] Verificar que Horizon corre: `docker compose logs horizon`
- [ ] Acceder a dashboard: `http://localhost/horizon` (si está configurado)

**Referencias**:
- `/docs/risks.md` - Riesgo 6
- `docker-compose.yml` - Servicio horizon

---

### TODO-007: Implementar Historia Clínica
**Prioridad**: P1  
**Impacto**: Expediente clínico no funciona  
**Estimación**: 5-6 horas

**Tareas**:
- [ ] Crear `app/Models/ClinicalHistory.php`
- [ ] Crear `app/Enums/ClinicalHistoryStatus.php`
- [ ] Crear `app/Services/ClinicalHistoryService.php` con:
  - [ ] `close()` - Cerrar historia
  - [ ] `unlock()` - Desbloquear (solo ADMIN)
- [ ] Crear `app/Http/Controllers/Api/V1/ClinicalHistoryController.php`
- [ ] Crear middleware `RequireAdmin` para unlock
- [ ] Agregar rutas
- [ ] Probar cierre y desbloqueo

**Referencias**:
- `python-backend/server.py` líneas 842-967
- `/docs/knowledge-base/features.md` - FEAT-005

---

### TODO-008: Implementar Notas SOAP
**Prioridad**: P1  
**Impacto**: Notas médicas no funcionan  
**Estimación**: 6-7 horas

**Tareas**:
- [ ] Crear `app/Models/SoapNote.php`
- [ ] Crear `app/Models/SoapNoteAddendum.php`
- [ ] Crear `app/Enums/SOAPNoteStatus.php`
- [ ] Crear `app/Services/SoapNoteService.php` con:
  - [ ] `sign()` - Firmar nota
  - [ ] `addAddendum()` - Agregar addendum
- [ ] Crear `app/Http/Controllers/Api/V1/SoapNoteController.php`
- [ ] Agregar rutas
- [ ] Probar firma y addendums

**Referencias**:
- `python-backend/server.py` líneas 969-1116
- `/docs/knowledge-base/features.md` - FEAT-006

---

### TODO-009: Implementar Portal Publico de Clientes
**Prioridad**: P1  
**Impacto**: Clientes no pueden agendar por su cuenta  
**Estimación**: 6-8 horas

**Tareas**:
- [ ] Crear `app/Services/PatientPortalService.php` con:
  - [ ] `requestAccess()` - Generar magic link
  - [ ] `getPortal()` - Obtener datos del portal
  - [ ] `cancel()` - Cancelar cita
  - [ ] `reschedule()` - Reagendar cita
- [ ] Crear `app/Http/Controllers/Api/V1/PublicBookingController.php`
- [ ] Crear `app/Http/Controllers/Api/V1/PublicPatientPortalController.php`
- [ ] Agregar rutas públicas (sin middleware auth)
- [ ] Probar magic links y expiración

**Referencias**:
- `python-backend/server.py` líneas 306-627
- `/docs/knowledge-base/features.md` - FEAT-007

---

## P2 - MEDIO (Hacer Cuando Sea Posible)

### TODO-010: Implementar Reportes y Exportaciones
**Prioridad**: P2  
**Impacto**: Reportes no disponibles  
**Estimación**: 5-6 horas

**Tareas**:
- [ ] Crear `app/Services/ReportService.php` con:
  - [ ] `getAppointmentStats()` - Estadísticas
  - [ ] `getPendingSoapPatients()` - Pacientes pendientes
  - [ ] `exportExcel()` - Exportar Excel
  - [ ] `exportPdf()` - Exportar PDF
- [ ] Crear `app/Http/Controllers/Api/V1/ReportController.php`
- [ ] Probar exportaciones

**Referencias**:
- `python-backend/server.py` líneas 1158-1333
- `/docs/knowledge-base/features.md` - FEAT-008

---

### TODO-011: Implementar Sistema de Configuración
**Prioridad**: P2  
**Impacto**: No se pueden configurar parámetros del sistema  
**Estimación**: 2-3 horas

**Tareas**:
- [ ] Crear `app/Models/Setting.php`
- [ ] Crear `app/Http/Controllers/Api/V1/SettingController.php`
- [ ] Crear middleware `RequireAdmin` (si no existe)
- [ ] Agregar rutas
- [ ] Crear seeder con settings iniciales

**Referencias**:
- `python-backend/server.py` líneas 1131-1156
- `/docs/knowledge-base/features.md` - FEAT-009

---

### TODO-012: Implementar Gestión de Usuarios
**Prioridad**: P2  
**Impacto**: No se pueden gestionar usuarios  
**Estimación**: 3-4 horas

**Tareas**:
- [ ] Crear `app/Enums/UserRole.php` (si no existe)
- [ ] Actualizar `app/Models/User.php` (agregar cast de role)
- [ ] Crear `app/Http/Controllers/Api/V1/UserController.php`
- [ ] Crear middleware `RequireAdmin`
- [ ] Agregar rutas
- [ ] Probar CRUD de usuarios

**Referencias**:
- `python-backend/server.py` líneas 140-203
- `/docs/knowledge-base/bugs.md` - BUG-005

---

### TODO-013: Implementar Logs de Notificaciones
**Prioridad**: P2  
**Impacto**: No se pueden ver logs  
**Estimación**: 2-3 horas

**Tareas**:
- [ ] Crear `app/Models/NotificationLog.php`
- [ ] Crear `app/Services/NotificationService.php` con `log()`
- [ ] Crear `app/Http/Controllers/Api/V1/NotificationLogController.php`
- [ ] Integrar `NotificationService@log()` en otros servicios
- [ ] Agregar rutas

**Referencias**:
- `python-backend/server.py` líneas 79-91, 1118-1129
- `/docs/knowledge-base/features.md` - FEAT-010

---

### TODO-014: Implementar Audit Logs
**Prioridad**: P2  
**Impacto**: No hay trazabilidad  
**Estimación**: 3-4 horas

**Tareas**:
- [ ] Crear `app/Models/AuditLog.php`
- [ ] Crear `app/Services/AuditLogService.php` con `log()`
- [ ] Integrar en acciones críticas:
  - [ ] Cierre/desbloqueo de historia clínica
  - [ ] Firma de notas SOAP
  - [ ] Creación/actualización de citas importantes
- [ ] Crear endpoint para ver logs (opcional)

**Referencias**:
- `/docs/database.md` - Tabla audit_logs
- `/docs/risks.md` - Riesgo 12

---

### TODO-015: Crear Middleware de Roles
**Prioridad**: P2  
**Impacto**: Control de acceso no funciona  
**Estimación**: 1-2 horas

**Tareas**:
- [ ] Crear `app/Http/Middleware/RequireAdmin.php`
- [ ] Registrar en `bootstrap/app.php` o `app/Http/Kernel.php`
- [ ] Aplicar a rutas que requieren ADMIN:
  - [ ] `/api/v1/users/*`
  - [ ] `/api/v1/settings/*`
  - [ ] `/api/v1/clients/{id}/clinical-history/unlock`

**Referencias**:
- `/docs/risks.md` - Riesgo 11
- `python-backend/auth.py` - Función `require_admin()`

---

## P3 - BAJO (Mejoras y Optimizaciones)

### TODO-016: Crear Tests Unitarios
**Prioridad**: P3  
**Impacto**: No hay garantía de calidad  
**Estimación**: 10+ horas

**Tareas**:
- [ ] Configurar PHPUnit
- [ ] Crear tests para Services:
  - [ ] `AppointmentServiceTest` - Validaciones de empalme/bloqueo
  - [ ] `ClinicalHistoryServiceTest` - Cierre/desbloqueo
  - [ ] `SoapNoteServiceTest` - Firma/addendums
- [ ] Crear tests para Controllers:
  - [ ] Tests de integración de endpoints
- [ ] Configurar CI/CD para ejecutar tests

**Referencias**:
- `backend/tests/` - Estructura existente
- `/docs/risks.md` - Bug 4

---

### TODO-017: Documentar Código
**Prioridad**: P3  
**Impacto**: Código menos mantenible  
**Estimación**: 5+ horas

**Tareas**:
- [ ] Agregar PHPDoc a métodos complejos
- [ ] Documentar reglas de negocio en Services
- [ ] Agregar comentarios en validaciones complejas
- [ ] Documentar decisiones de implementación

**Referencias**:
- `/docs/risks.md` - Deuda técnica 2

---

### TODO-018: Optimizar Queries
**Prioridad**: P3  
**Impacto**: Performance  
**Estimación**: 3-4 horas

**Tareas**:
- [ ] Revisar queries N+1
- [ ] Agregar eager loading donde sea necesario
- [ ] Revisar índices de base de datos
- [ ] Optimizar queries de reportes

**Referencias**:
- `/docs/database.md` - Índices recomendados

---

### TODO-019: Crear Comando Artisan para Seed Demo
**Prioridad**: P3  
**Impacto**: Desarrollo más fácil  
**Estimación**: 2-3 horas

**Tareas**:
- [ ] Crear `app/Console/Commands/SeedDemoCommand.php`
- [ ] Generar datos de prueba:
  - [ ] Usuarios (admin + asistentes)
  - [ ] Pacientes
  - [ ] Citas
  - [ ] Bloqueos
  - [ ] Historias clínicas
  - [ ] Notas SOAP
- [ ] Registrar comando: `php artisan app:seed-demo`

**Referencias**:
- `python-backend/server.py` líneas 1336-1417 - Endpoint `/seed`

---

## Cómo Gestionar TODOs

1. **Agregar nuevos TODOs**: Usar formato con número secuencial, prioridad, impacto, estimación
2. **Marcar como completado**: Cambiar `[ ]` a `[x]` y agregar fecha de completado
3. **Actualizar estimación**: Si la estimación cambia, actualizar
4. **Mover entre prioridades**: Si la prioridad cambia, mover a sección correspondiente
5. **Archivar completados**: Mover TODOs completados a sección "Completados" al final del documento

---

## Completados

(Ninguno aún)
