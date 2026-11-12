# Guía de Migración a Laravel

## Introducción

Esta guía proporciona instrucciones detalladas para replicar el backend de yes-ok (Python/FastAPI) a Laravel, manteniendo todas las reglas de negocio, endpoints y estructura.

## Mapeo de Estructura

### Estructura Python → Laravel

```
Python (actual)                    Laravel (objetivo)
─────────────────                  ────────────────────
/backend/                          /app/
  server.py                          Http/Controllers/Api/V1/
  models.py                          Models/
  schemas.py                         Http/Resources/
                                     Http/Requests/
  auth.py                            Http/Middleware/
                                     Services/Auth/
  database.py                        config/database.php
```

### Mapeo de Módulos

| Módulo Python | Laravel Equivalente |
|---------------|---------------------|
| auth endpoints | AuthController |
| users endpoints | UserController |
| patients endpoints | PatientController |
| appointments endpoints | AppointmentController |
| availability-blocks endpoints | AvailabilityBlockController |
| clinical-history endpoints | ClinicalHistoryController |
| soap-notes endpoints | SoapNoteController |
| reports endpoints | ReportController |
| settings endpoints | SettingController |

## Estructura Laravel Recomendada

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── AuthController.php
│   │           ├── UserController.php
│   │           ├── PatientController.php
│   │           ├── AppointmentController.php
│   │           ├── AvailabilityBlockController.php
│   │           ├── ClinicalHistoryController.php
│   │           ├── SoapNoteController.php
│   │           ├── ReportController.php
│   │           └── SettingController.php
│   ├── Middleware/
│   │   └── JwtMiddleware.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   └── LoginRequest.php
│   │   ├── Patient/
│   │   │   ├── StorePatientRequest.php
│   │   │   └── UpdatePatientRequest.php
│   │   ├── Appointment/
│   │   │   ├── StoreAppointmentRequest.php
│   │   │   └── UpdateAppointmentRequest.php
│   │   └── ...
│   └── Resources/
│       ├── UserResource.php
│       ├── PatientResource.php
│       ├── AppointmentResource.php
│       └── ...
├── Models/
│   ├── User.php
│   ├── Patient.php
│   ├── Appointment.php
│   ├── AvailabilityBlock.php
│   ├── ClinicalHistory.php
│   ├── SoapNote.php
│   ├── SoapNoteAddendum.php
│   ├── NotificationLog.php
│   ├── Setting.php
│   └── AuditLog.php
├── Services/
│   ├── AppointmentService.php
│   ├── ClinicalRecordService.php
│   ├── NotificationService.php
│   └── ReportService.php
├── Enums/
│   ├── UserRole.php
│   ├── AppointmentStatus.php
│   ├── SoapNoteStatus.php
│   └── ClinicalHistoryStatus.php
└── Repositories/
    ├── Interfaces/
    │   ├── PatientRepositoryInterface.php
    │   └── AppointmentRepositoryInterface.php
    └── Eloquent/
        ├── PatientRepository.php
        └── AppointmentRepository.php
```

## Endpoints API

### Autenticación
```
POST /api/v1/auth/login          → AuthController@login
GET  /api/v1/auth/me             → AuthController@me
```

### Usuarios (Admin)
```
GET    /api/v1/users             → UserController@index
POST   /api/v1/users             → UserController@store
PUT    /api/v1/users/{id}        → UserController@update
DELETE /api/v1/users/{id}        → UserController@destroy
```

### Pacientes
```
GET    /api/v1/clients           → PatientController@index
POST   /api/v1/clients           → PatientController@store
GET    /api/v1/clients/{id}      → PatientController@show
PUT    /api/v1/clients/{id}      → PatientController@update
DELETE /api/v1/clients/{id}      → PatientController@destroy
```

### Citas
```
GET    /api/v1/appointments                    → AppointmentController@index
POST   /api/v1/appointments                    → AppointmentController@store
GET    /api/v1/appointments/reschedule-queue   → AppointmentController@rescheduleQueue
GET    /api/v1/appointments/{id}               → AppointmentController@show
PUT    /api/v1/appointments/{id}               → AppointmentController@update
DELETE /api/v1/appointments/{id}               → AppointmentController@destroy
```

### Bloqueos de Disponibilidad
```
GET    /api/v1/availability-blocks         → AvailabilityBlockController@index
POST   /api/v1/availability-blocks         → AvailabilityBlockController@store
DELETE /api/v1/availability-blocks/{id}    → AvailabilityBlockController@destroy
```

### Historia Clínica
```
GET  /api/v1/clients/{id}/clinical-history          → ClinicalHistoryController@show
POST /api/v1/clients/{id}/clinical-history          → ClinicalHistoryController@store
PUT  /api/v1/clients/{id}/clinical-history          → ClinicalHistoryController@update
POST /api/v1/clients/{id}/clinical-history/close    → ClinicalHistoryController@close
POST /api/v1/clients/{id}/clinical-history/unlock   → ClinicalHistoryController@unlock
```

### Notas SOAP
```
GET  /api/v1/clients/{id}/soap-notes       → SoapNoteController@index
POST /api/v1/clients/{id}/soap-notes       → SoapNoteController@store
GET  /api/v1/soap-notes/{id}               → SoapNoteController@show
PUT  /api/v1/soap-notes/{id}               → SoapNoteController@update
POST /api/v1/soap-notes/{id}/sign          → SoapNoteController@sign
POST /api/v1/soap-notes/{id}/addendum      → SoapNoteController@addAddendum
```

### Reportes
```
GET /api/v1/reports/appointments-stats     → ReportController@appointmentStats
GET /api/v1/reports/pending-soap           → ReportController@pendingSoap
GET /api/v1/reports/export/excel           → ReportController@exportExcel
GET /api/v1/reports/export/pdf             → ReportController@exportPdf
```

### Configuración (Admin)
```
GET /api/v1/settings                → SettingController@index
PUT /api/v1/settings/{key}          → SettingController@update
```

### Logs de Notificaciones
```
GET /api/v1/notification-logs       → NotificationLogController@index
```

## Migraciones Laravel

### Ejemplo: appointments
```php
Schema::create('appointments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id')->constrained()->onDelete('cascade');
    $table->date('date')->index();
    $table->time('start_time');
    $table->time('end_time');
    $table->enum('status', [
        'PENDIENTE', 'CONFIRMADA', 'CANCELADA', 
        'NO_SHOW', 'COMPLETADA', 'REQUIERE_REAGENDAR'
    ])->default('PENDIENTE');
    $table->text('notes')->nullable();
    $table->string('reason', 500)->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    
    $table->index(['date', 'status']);
    $table->index('patient_id');
});
```

## Reglas de Negocio Críticas

### 1. Empalmes de Citas
**Ubicación Python**: `server.py` → `check_appointment_overlap()`

```python
def check_appointment_overlap(db, date, start_time, end_time, exclude_id=None):
    # Buscar citas no canceladas en la misma fecha
    # Verificar si hay solapamiento de horarios
    # (start_time < apt.end_time AND end_time > apt.start_time)
```

**Laravel equivalente**:
```php
// app/Services/AppointmentService.php
public function hasOverlap(Carbon $date, $startTime, $endTime, $excludeId = null): bool
{
    return Appointment::where('date', $date)
        ->whereNotIn('status', ['CANCELADA'])
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->where(function ($query) use ($startTime, $endTime) {
            $query->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
        })
        ->exists();
}
```

### 2. Bloqueos de Disponibilidad
**Ubicación Python**: `server.py` → `check_availability_block()`

Al crear un bloqueo:
1. Buscar citas afectadas en el rango de fechas/horas
2. Cambiar estado a `REQUIERE_REAGENDAR`
3. Registrar log de notificación

```php
// Laravel Service
public function createBlock(array $data): AvailabilityBlock
{
    $block = AvailabilityBlock::create($data);
    
    // Marcar citas afectadas
    $affected = Appointment::whereBetween('date', [$block->start_date, $block->end_date])
        ->whereNotIn('status', ['CANCELADA', 'COMPLETADA'])
        ->when(!$block->is_full_day, function ($q) use ($block) {
            $q->where('start_time', '<', $block->end_time)
              ->where('end_time', '>', $block->start_time);
        })
        ->get();
    
    foreach ($affected as $apt) {
        $apt->update(['status' => 'REQUIERE_REAGENDAR']);
        $this->notificationService->log($apt->patient_id, $apt->id, 'reschedule_needed');
    }
    
    return $block;
}
```

### 3. Historia Clínica - Cierre y Desbloqueo

**Reglas**:
- Una vez cerrada, es solo lectura
- Solo ADMIN puede desbloquear
- El desbloqueo requiere motivo (min 10 caracteres)
- Todas las acciones se registran en audit_log

```php
// ClinicalHistoryService
public function close(ClinicalHistory $history, User $user): void
{
    $history->update([
        'status' => 'CERRADA',
        'closed_at' => now(),
        'closed_by' => $user->id,
    ]);
    
    AuditLog::create([
        'user_id' => $user->id,
        'action' => 'CLOSE_CLINICAL_HISTORY',
        'entity_type' => 'ClinicalHistory',
        'entity_id' => $history->id,
        'details' => "Historia cerrada para paciente {$history->patient_id}",
    ]);
}

public function unlock(ClinicalHistory $history, User $user, string $reason): void
{
    throw_if($user->role !== 'ADMIN', new UnauthorizedException());
    throw_if(strlen($reason) < 10, new ValidationException('Motivo muy corto'));
    
    $history->update([
        'status' => 'ABIERTA',
        'unlock_reason' => $reason,
        'unlocked_at' => now(),
        'unlocked_by' => $user->id,
    ]);
    
    AuditLog::create([/* ... */]);
}
```

### 4. Notas SOAP - Firma y Addendum

**Reglas**:
- Las notas en BORRADOR pueden editarse
- Una vez FIRMADA, no se puede editar
- Solo se pueden agregar addendums a notas firmadas
- La firma registra usuario y timestamp

```php
// SoapNoteService
public function sign(SoapNote $note, User $user): void
{
    throw_if($note->status === 'FIRMADA', new InvalidStateException());
    
    $note->update([
        'status' => 'FIRMADA',
        'signed_at' => now(),
        'signed_by' => $user->id,
    ]);
    
    AuditLog::create([/* ... */]);
}

public function addAddendum(SoapNote $note, string $content, User $user): SoapNoteAddendum
{
    throw_if($note->status !== 'FIRMADA', new InvalidStateException());
    
    return SoapNoteAddendum::create([
        'soap_note_id' => $note->id,
        'content' => $content,
        'created_by' => $user->id,
    ]);
}
```

### 5. Regla de Nota SOAP cada N Citas

**Ubicación Python**: `server.py` → `/api/v1/reports/pending-soap`

```php
// ReportService
public function getPendingSoapPatients(): Collection
{
    $interval = Setting::where('key', 'soap_interval')->value('value') ?? 3;
    
    return Patient::withCount([
        'appointments' => fn($q) => $q->where('status', 'COMPLETADA'),
        'soapNotes'
    ])
    ->get()
    ->filter(function ($patient) use ($interval) {
        $expectedNotes = intdiv($patient->appointments_count, $interval);
        return $patient->soap_notes_count < $expectedNotes;
    });
}
```

## Checklist de Migración

### Fase 1: Setup
- [ ] Crear proyecto Laravel
- [ ] Configurar base de datos PostgreSQL
- [ ] Instalar dependencias (JWT, Excel, PDF)
- [ ] Configurar timezone a America/Monterrey

### Fase 2: Modelos y Migraciones
- [ ] Crear migraciones para todas las tablas
- [ ] Crear modelos con relaciones
- [ ] Crear Enums para estados
- [ ] Ejecutar seeders

### Fase 3: Autenticación
- [ ] Implementar JwtMiddleware
- [ ] Crear AuthController
- [ ] Configurar rutas protegidas
- [ ] Implementar control de roles

### Fase 4: CRUD Básico
- [ ] UserController + Request + Resource
- [ ] PatientController + Request + Resource
- [ ] AppointmentController + validaciones
- [ ] AvailabilityBlockController

### Fase 5: Lógica de Negocio
- [ ] AppointmentService (empalmes, bloqueos)
- [ ] ClinicalHistoryService (cierre/desbloqueo)
- [ ] SoapNoteService (firma/addendum)
- [ ] NotificationService (sandbox logs)

### Fase 6: Reportes
- [ ] ReportController
- [ ] Exportación Excel (PhpSpreadsheet)
- [ ] Exportación PDF (DomPDF o similar)

### Fase 7: Testing
- [ ] Tests unitarios para Services
- [ ] Tests de integración para endpoints
- [ ] Verificar todas las reglas de negocio

## Paquetes Laravel Recomendados

```json
{
    "require": {
        "tymon/jwt-auth": "^2.0",
        "maatwebsite/excel": "^3.1",
        "barryvdh/laravel-dompdf": "^2.0",
        "spatie/laravel-permission": "^6.0"
    }
}
```

## Variables de Entorno

```env
APP_TIMEZONE=America/Monterrey
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=yes-ok
DB_USERNAME=postgres
DB_PASSWORD=postgres

JWT_SECRET=your-secret-key
JWT_TTL=1440

SOAP_NOTES_INTERVAL=3
```
