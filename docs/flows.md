# yes-ok - Flujos Principales

## Flujo de Autenticación

### Login

```
1. Usuario ingresa email/password en frontend
   ↓
2. Frontend → POST /api/v1/auth/login
   {
     "email": "admin@yes-ok.com",
     "password": "admin123"
   }
   ↓
3. Laravel AuthController@login
   ├─ Valida credenciales
   ├─ Verifica usuario activo
   ├─ Genera JWT token
   └─ Retorna token + datos usuario
   ↓
4. Frontend almacena token en localStorage
   ↓
5. Frontend redirige a Dashboard
```

### Request Autenticado

```
1. Frontend hace request con token
   Headers: Authorization: Bearer {token}
   ↓
2. JWT Middleware valida token
   ├─ Decodifica token
   ├─ Obtiene user_id del payload
   ├─ Busca usuario en DB
   ├─ Verifica usuario activo
   └─ Inyecta $user en request
   ↓
3. Controller procesa request con $user disponible
```

## Flujo de Gestión de Citas

### Crear Cita

```
1. Usuario selecciona paciente, fecha, hora
   ↓
2. Frontend → POST /api/v1/appointments
   {
     "patient_id": 1,
     "date": "2026-02-10",
     "start_time": "10:00",
     "end_time": "10:45",
     "reason": "Consulta de seguimiento"
   }
   ↓
3. AppointmentController@store
   ↓
4. CreateAppointmentRequest valida datos
   ↓
5. AppointmentService@create
   ├─ PatientRepository@find → Verifica paciente existe
   ├─ AppointmentRepository@checkOverlap → Valida no empalme
   ├─ AvailabilityBlockRepository@checkBlock → Valida no bloqueo
   ├─ Valida no en el pasado
   ├─ AppointmentRepository@create → Crea cita
   └─ NotificationService@log → Registra notificación
   ↓
6. AppointmentResource transforma respuesta
   ↓
7. Frontend actualiza calendario
```

### Validación de Empalme

```
check_appointment_overlap(date, start_time, end_time, exclude_id):
  1. Buscar citas en la misma fecha
  2. Excluir citas CANCELADAS
  3. Excluir cita actual (si es update)
  4. Para cada cita existente:
     - Si (start_time < existing.end_time AND end_time > existing.start_time)
     - → HAY EMPALME
  5. Retornar true/false
```

### Validación de Bloqueo

```
check_availability_block(date, start_time, end_time):
  1. Buscar bloqueos que incluyan la fecha
  2. Para cada bloqueo:
     - Si is_full_day = true → BLOQUEADO
     - Si tiene start_time/end_time:
       - Si (start_time < block.end_time AND end_time > block.start_time)
       - → BLOQUEADO
  3. Retornar true/false
```

### Listar Citas (Calendario)

```
1. Frontend → GET /api/v1/appointments?start_date=2026-02-01&end_date=2026-02-28
   ↓
2. AppointmentController@index
   ↓
3. AppointmentService@list
   ├─ AppointmentRepository@findByDateRange
   └─ Carga relaciones (patient)
   ↓
4. AppointmentResource[] transforma respuestas
   ↓
5. Frontend renderiza calendario
```

## Flujo de Bloqueos de Disponibilidad

### Crear Bloqueo

```
1. Usuario crea bloqueo (fecha/hora, razón)
   ↓
2. Frontend → POST /api/v1/availability-blocks
   {
     "start_date": "2026-02-15",
     "end_date": "2026-02-15",
     "is_full_day": true,
     "reason": "Día festivo"
   }
   ↓
3. AvailabilityBlockController@store
   ↓
4. AvailabilityBlockService@create
   ├─ AvailabilityBlockRepository@create → Crea bloqueo
   ├─ AppointmentRepository@findAffected → Busca citas afectadas
   │   - Filtra por rango de fechas
   │   - Excluye CANCELADAS y COMPLETADAS
   │   - Si no es full_day: filtra por horario
   ├─ Para cada cita afectada:
   │   ├─ Cambiar status a REQUIERE_REAGENDAR
   │   └─ NotificationService@log → Registra notificación
   └─ Guardar cambios
   ↓
5. Frontend actualiza calendario (muestra bloqueo y citas afectadas)
```

## Flujo de Historia Clínica

### Cerrar Historia Clínica

```
1. Usuario (ADMIN) cierra historia clínica
   ↓
2. Frontend → POST /api/v1/clients/{id}/clinical-history/close
   ↓
3. ClinicalHistoryController@close
   ↓
4. ClinicalHistoryService@close
   ├─ Verifica historia existe y está ABIERTA
   ├─ Actualiza status = CERRADA
   ├─ Registra closed_at, closed_by
   └─ AuditLogService@log → Registra acción
   ↓
5. Frontend muestra historia como "Cerrada" (solo lectura)
```

### Desbloquear Historia Clínica

```
1. Usuario (ADMIN) desbloquea con motivo
   ↓
2. Frontend → POST /api/v1/clients/{id}/clinical-history/unlock
   {
     "reason": "Necesito agregar información adicional"
   }
   ↓
3. ClinicalHistoryController@unlock
   ↓
4. ClinicalHistoryService@unlock
   ├─ Verifica usuario es ADMIN
   ├─ Verifica historia está CERRADA
   ├─ Valida motivo (mínimo 10 caracteres)
   ├─ Actualiza status = ABIERTA
   ├─ Registra unlock_reason, unlocked_at, unlocked_by
   └─ AuditLogService@log → Registra acción
   ↓
5. Frontend permite edición nuevamente
```

## Flujo de Notas SOAP

### Crear Nota SOAP

```
1. Usuario crea nota SOAP para paciente
   ↓
2. Frontend → POST /api/v1/clients/{id}/soap-notes
   {
     "appointment_id": 5,
     "subjective": "Paciente refiere...",
     "objective": "Examen mental...",
     "assessment": "Diagnóstico...",
     "plan": "Tratamiento..."
   }
   ↓
3. SoapNoteController@store
   ↓
4. SoapNoteService@create
   ├─ Crea nota con status = BORRADOR
   └─ Registra created_by
   ↓
5. Frontend muestra nota como "Borrador" (editable)
```

### Firmar Nota SOAP

```
1. Usuario firma nota (hace inmutable)
   ↓
2. Frontend → POST /api/v1/soap-notes/{id}/sign
   ↓
3. SoapNoteController@sign
   ↓
4. SoapNoteService@sign
   ├─ Verifica status = BORRADOR
   ├─ Actualiza status = FIRMADA
   ├─ Registra signed_at, signed_by
   └─ AuditLogService@log → Registra acción
   ↓
5. Frontend muestra nota como "Firmada" (no editable, solo addendums)
```

### Agregar Addendum

```
1. Usuario agrega addendum a nota firmada
   ↓
2. Frontend → POST /api/v1/soap-notes/{id}/addendum
   {
     "content": "Corrección: El paciente mencionó..."
   }
   ↓
3. SoapNoteController@addAddendum
   ↓
4. SoapNoteService@addAddendum
   ├─ Verifica nota está FIRMADA
   ├─ Crea SOAPNoteAddendum
   └─ AuditLogService@log → Registra acción
   ↓
5. Frontend muestra addendum en historial
```

## Flujo de Portal Público (Paciente)

### Solicitar Acceso al Portal

```
1. Paciente ingresa WhatsApp
   ↓
2. Frontend → POST /api/v1/public/patient-access
   {
     "whatsapp": "+521234567890"
   }
   ↓
3. PublicPatientPortalController@requestAccess
   ↓
4. PatientPortalService@requestAccess
   ├─ Busca paciente por WhatsApp
   ├─ Si no existe: retorna error
   ├─ Genera token seguro (secrets.token_urlsafe)
   ├─ Establece expiración (24h)
   ├─ Guarda access_token y access_token_expires
   └─ Retorna token (en producción: enviar por WhatsApp/SMS)
   ↓
5. Frontend redirige a /portal?token={token}
```

### Ver Portal del Paciente

```
1. Paciente accede con token
   ↓
2. Frontend → GET /api/v1/public/patient-portal?token={token}
   ↓
3. PublicPatientPortalController@getPortal
   ↓
4. PatientPortalService@getPortal
   ├─ Valida token (existe y no expirado)
   ├─ Obtiene paciente
   ├─ Carga citas futuras y pasadas
   └─ Retorna datos del portal
   ↓
5. Frontend muestra citas del paciente
```

### Cancelar Cita (Paciente)

```
1. Paciente cancela cita desde portal
   ↓
2. Frontend → POST /api/v1/public/patient-portal/cancel/{id}?token={token}
   ↓
3. PublicPatientPortalController@cancel
   ↓
4. PatientPortalService@cancel
   ├─ Valida token
   ├─ Verifica cita pertenece al paciente
   ├─ Verifica cita no está en el pasado
   ├─ Actualiza status = CANCELADA
   └─ NotificationService@log → Registra notificación
   ↓
5. Frontend actualiza lista de citas
```

## Flujo de Reportes

### Estadísticas de Citas

```
1. Usuario solicita estadísticas
   ↓
2. Frontend → GET /api/v1/reports/appointments-stats?start_date=2026-01-01&end_date=2026-01-31
   ↓
3. ReportController@appointmentStats
   ↓
4. ReportService@getAppointmentStats
   ├─ AppointmentRepository@findByDateRange
   ├─ Agrupa por status
   ├─ Calcula totales
   ├─ Calcula tasas (cancelación, no-show)
   └─ Retorna estadísticas
   ↓
5. Frontend muestra gráficas/dashboard
```

### Pacientes Pendientes de SOAP

```
1. Usuario solicita reporte
   ↓
2. Frontend → GET /api/v1/reports/pending-soap
   ↓
3. ReportController@pendingSoap
   ↓
4. ReportService@getPendingSoapPatients
   ├─ Obtiene SOAP_NOTES_INTERVAL de settings (default: 3)
   ├─ Para cada paciente:
   │   ├─ Cuenta citas COMPLETADAS
   │   ├─ Cuenta notas SOAP existentes
   │   ├─ Calcula: expected_soaps = completed / interval
   │   └─ Si soap_count < expected_soaps → PENDIENTE
   └─ Retorna lista de pendientes
   ↓
5. Frontend muestra lista de pacientes pendientes
```

## Flujo de Roles y Permisos

### Control de Acceso

```
Request con JWT token
   ↓
JWT Middleware valida token
   ↓
Obtiene usuario y rol
   ↓
Si endpoint requiere ADMIN:
   ├─ Verifica role === 'ADMIN'
   ├─ Si no: 403 Forbidden
   └─ Si sí: Continúa
   ↓
Si endpoint requiere autenticación:
   ├─ Verifica usuario activo
   ├─ Si no: 401 Unauthorized
   └─ Si sí: Continúa
   ↓
Controller procesa request
```

### Endpoints por Rol

- **Público (sin auth)**: `/api/v1/public/*`
- **Autenticado (cualquier rol)**: `/api/v1/clients/*`, `/api/v1/appointments/*`, etc.
- **Solo ADMIN**: `/api/v1/users/*`, `/api/v1/settings/*`, `/api/v1/clients/{id}/clinical-history/unlock`
