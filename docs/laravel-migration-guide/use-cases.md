# Casos de Uso y Queries - yes-ok

## Autenticación

### Login
- **Nombre**: `login`
- **Propósito**: Autenticar usuario y generar token JWT
- **Inputs**: `{ email: string, password: string }`
- **Outputs**: `{ access_token: string, token_type: "bearer", user: UserResponse }`
- **Validaciones**:
  - Email debe ser válido
  - Usuario debe existir
  - Password debe coincidir
  - Usuario debe estar activo
- **Errores**:
  - 401: "Credenciales inválidas"
  - 401: "Usuario inactivo"

### GetCurrentUser
- **Nombre**: `getMe`
- **Propósito**: Obtener datos del usuario autenticado
- **Inputs**: Token JWT en header
- **Outputs**: `UserResponse`

---

## Gestión de Usuarios

### ListUsers
- **Nombre**: `listUsers`
- **Propósito**: Listar todos los usuarios del sistema
- **Permisos**: Solo ADMIN
- **Inputs**: Token JWT
- **Outputs**: `UserResponse[]`

### CreateUser
- **Nombre**: `createUser`
- **Propósito**: Crear nuevo usuario
- **Permisos**: Solo ADMIN
- **Inputs**: `{ email, name, password, role }`
- **Validaciones**:
  - Email único
  - Password requerido
- **Errores**:
  - 400: "El email ya está registrado"

### UpdateUser
- **Nombre**: `updateUser`
- **Propósito**: Actualizar datos de usuario
- **Permisos**: Solo ADMIN
- **Inputs**: `{ email?, name?, role?, is_active? }`
- **Errores**:
  - 404: "Usuario no encontrado"

### DeleteUser
- **Nombre**: `deleteUser`
- **Propósito**: Eliminar usuario
- **Permisos**: Solo ADMIN
- **Validaciones**:
  - No puede eliminarse a sí mismo
- **Errores**:
  - 400: "No puedes eliminarte a ti mismo"

---

## Gestión de Pacientes

### ListPatients
- **Nombre**: `listPatients`
- **Propósito**: Listar pacientes con búsqueda opcional
- **Inputs**: `{ search?: string }`
- **Outputs**: `PatientResponse[]`
- **Reglas**: Búsqueda por nombre o WhatsApp (ILIKE)

### CreatePatient
- **Nombre**: `createPatient`
- **Propósito**: Registrar nuevo paciente
- **Inputs**: `{ name, whatsapp, email?, consent_notifications }`
- **Validaciones**:
  - Nombre requerido
  - WhatsApp requerido
- **Outputs**: `PatientResponse`

### GetPatient
- **Nombre**: `getPatient`
- **Propósito**: Obtener paciente por ID
- **Inputs**: `patient_id`
- **Errores**: 404: "Paciente no encontrado"

### UpdatePatient
- **Nombre**: `updatePatient`
- **Propósito**: Actualizar datos de paciente
- **Inputs**: `{ name?, whatsapp?, email?, consent_notifications? }`

### DeletePatient
- **Nombre**: `deletePatient`
- **Propósito**: Eliminar paciente
- **Nota**: Elimina en cascada citas y expediente

---

## Gestión de Citas

### ListAppointments
- **Nombre**: `listAppointments`
- **Propósito**: Listar citas con filtros
- **Inputs**: `{ start_date?, end_date?, status?, patient_id? }`
- **Outputs**: `AppointmentResponse[]` con paciente incluido

### CreateAppointment
- **Nombre**: `createAppointment`
- **Propósito**: Crear nueva cita
- **Inputs**: `{ patient_id, date, start_time, end_time, reason?, notes? }`
- **Validaciones**:
  1. Paciente debe existir
  2. No permitir citas en el pasado
  3. No debe haber empalme con otra cita
  4. No debe haber bloqueo de disponibilidad
- **Errores**:
  - 404: "Paciente no encontrado"
  - 400: "No se pueden crear citas en el pasado"
  - 400: "Existe un empalme con otra cita"
  - 400: "El horario está bloqueado"
- **Regla de negocio - Empalme**:
  ```
  Hay empalme si:
  new_start_time < existing_end_time AND new_end_time > existing_start_time
  ```

### UpdateAppointment
- **Nombre**: `updateAppointment`
- **Propósito**: Actualizar cita existente
- **Inputs**: `{ date?, start_time?, end_time?, status?, notes?, reason? }`
- **Validaciones**: Las mismas de creación si cambia fecha/hora

### GetRescheduleQueue
- **Nombre**: `getRescheduleQueue`
- **Propósito**: Obtener citas que requieren reagendarse
- **Outputs**: Citas con status=REQUIERE_REAGENDAR

---

## Bloqueos de Disponibilidad

### ListAvailabilityBlocks
- **Nombre**: `listAvailabilityBlocks`
- **Propósito**: Listar bloqueos en rango de fechas
- **Inputs**: `{ start_date?, end_date? }`

### CreateAvailabilityBlock
- **Nombre**: `createAvailabilityBlock`
- **Propósito**: Crear bloqueo y marcar citas afectadas
- **Inputs**: `{ start_date, end_date, start_time?, end_time?, is_full_day, reason? }`
- **Regla de negocio**:
  1. Crear el bloqueo
  2. Buscar citas en el rango que no estén canceladas/completadas
  3. Si es día completo: todas las citas del rango
  4. Si es horario: citas que se solapan con el horario
  5. Marcar afectadas como REQUIERE_REAGENDAR
  6. Crear log de notificación para cada una

### DeleteAvailabilityBlock
- **Nombre**: `deleteAvailabilityBlock`
- **Propósito**: Eliminar bloqueo
- **Nota**: NO revierte automáticamente el estado de citas

---

## Historia Clínica

### GetClinicalHistory
- **Nombre**: `getClinicalHistory`
- **Propósito**: Obtener historia clínica del paciente
- **Inputs**: `patient_id`
- **Errores**: 404: "Historia clínica no encontrada"

### CreateClinicalHistory
- **Nombre**: `createClinicalHistory`
- **Propósito**: Crear historia clínica inicial
- **Validaciones**:
  - Solo una historia por paciente
- **Errores**: 400: "Ya existe historia clínica para este paciente"

### UpdateClinicalHistory
- **Nombre**: `updateClinicalHistory`
- **Propósito**: Actualizar campos de historia
- **Validaciones**:
  - Historia debe estar ABIERTA
- **Errores**: 400: "La historia clínica está cerrada"

### CloseClinicalHistory
- **Nombre**: `closeClinicalHistory`
- **Propósito**: Cerrar historia (solo lectura)
- **Regla de negocio**:
  1. Cambiar status a CERRADA
  2. Registrar closed_at y closed_by
  3. Crear audit_log

### UnlockClinicalHistory
- **Nombre**: `unlockClinicalHistory`
- **Propósito**: Desbloquear historia cerrada
- **Permisos**: Solo ADMIN
- **Inputs**: `reason` (mínimo 10 caracteres)
- **Regla de negocio**:
  1. Validar que esté cerrada
  2. Cambiar status a ABIERTA
  3. Registrar unlock_reason, unlocked_at, unlocked_by
  4. Crear audit_log

---

## Notas SOAP

### ListSOAPNotes
- **Nombre**: `listSOAPNotes`
- **Propósito**: Listar notas SOAP del paciente
- **Inputs**: `patient_id`
- **Outputs**: Notas ordenadas por fecha DESC, con addendums

### CreateSOAPNote
- **Nombre**: `createSOAPNote`
- **Propósito**: Crear nueva nota SOAP
- **Inputs**: `{ patient_id, appointment_id?, subjective?, objective?, assessment?, plan? }`
- **Estado inicial**: BORRADOR

### UpdateSOAPNote
- **Nombre**: `updateSOAPNote`
- **Propósito**: Actualizar nota en borrador
- **Validaciones**: Solo si status=BORRADOR
- **Errores**: 400: "No se puede editar una nota firmada"

### SignSOAPNote
- **Nombre**: `signSOAPNote`
- **Propósito**: Firmar nota (hacerla inmutable)
- **Validaciones**: Solo si status=BORRADOR
- **Regla de negocio**:
  1. Cambiar status a FIRMADA
  2. Registrar signed_at y signed_by
  3. Crear audit_log
- **Errores**: 400: "La nota ya está firmada"

### AddSOAPAddendum
- **Nombre**: `addSOAPAddendum`
- **Propósito**: Agregar información a nota firmada
- **Validaciones**: Solo si status=FIRMADA
- **Inputs**: `{ content }`
- **Regla de negocio**:
  1. Crear SOAPNoteAddendum
  2. Crear audit_log
- **Errores**: 400: "Solo se pueden agregar addendums a notas firmadas"

---

## Reportes

### GetAppointmentStats
- **Nombre**: `getAppointmentStats`
- **Propósito**: Estadísticas de citas
- **Inputs**: `{ start_date?, end_date? }`
- **Outputs**:
  ```json
  {
    "total": 100,
    "by_status": {
      "PENDIENTE": 20,
      "CONFIRMADA": 30,
      "COMPLETADA": 40,
      "CANCELADA": 8,
      "NO_SHOW": 2
    },
    "cancellation_rate": 8.0,
    "no_show_rate": 2.0
  }
  ```

### GetPendingSOAPPatients
- **Nombre**: `getPendingSOAPPatients`
- **Propósito**: Pacientes que requieren nota SOAP
- **Regla de negocio**:
  1. Obtener SOAP_NOTES_INTERVAL de settings (default: 3)
  2. Para cada paciente: contar citas COMPLETADAS
  3. Calcular notas esperadas: completed_appointments / interval
  4. Si soap_count < expected: marcar como pendiente
- **Outputs**:
  ```json
  [{
    "patient_id": 1,
    "patient_name": "Juan",
    "completed_appointments": 6,
    "last_soap_date": "2024-01-15",
    "soap_required": true
  }]
  ```

### ExportExcel
- **Nombre**: `exportAppointmentsExcel`
- **Propósito**: Exportar citas a Excel
- **Inputs**: `{ start_date?, end_date?, status? }`
- **Columnas**: ID, Paciente, Fecha, Hora Inicio, Hora Fin, Estado, Motivo

### ExportPDF
- **Nombre**: `exportAppointmentsPDF`
- **Propósito**: Exportar citas a PDF
- **Formato**: Tabla con mismas columnas que Excel

---

## Configuración

### ListSettings
- **Nombre**: `listSettings`
- **Propósito**: Obtener todas las configuraciones
- **Settings disponibles**:
  - `appointment_duration`: Duración default (minutos)
  - `buffer_minutes`: Buffer entre citas
  - `soap_interval`: Citas entre notas SOAP
  - `reminder_24h`: Recordatorio 24h (true/false)
  - `reminder_2h`: Recordatorio 2h (true/false)

### UpdateSetting
- **Nombre**: `updateSetting`
- **Propósito**: Actualizar configuración
- **Permisos**: Solo ADMIN
- **Inputs**: `{ key, value }`

---

## Logs de Notificaciones

### ListNotificationLogs
- **Nombre**: `listNotificationLogs`
- **Propósito**: Ver logs de notificaciones enviadas
- **Inputs**: `{ patient_id?, limit? }`
- **Nota**: En modo sandbox, status="SANDBOX"

### LogNotification (interno)
- **Nombre**: `logNotification`
- **Propósito**: Registrar intento de notificación
- **Inputs**: `{ patient_id, appointment_id, channel, template_name, message }`
- **Nota**: Esta función es interna, no es endpoint
