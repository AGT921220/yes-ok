# Inventario de Endpoints - yes-ok API

**Fecha**: 2026-02-10  
**Estado**: Análisis completo de endpoints pendientes

---

## 📊 RESUMEN EJECUTIVO

- **Total de endpoints**: 40+
- **Endpoints implementados**: 18
- **Endpoints pendientes**: 22
- **Endpoints con placeholders**: 22

---

## ✅ ENDPOINTS IMPLEMENTADOS (18)

### Autenticación (2/2)
- ✅ `POST /api/v1/auth/login`
- ✅ `GET /api/v1/auth/me`

### Clientes (5/5)
- ✅ `GET /api/v1/clients`
- ✅ `POST /api/v1/clients`
- ✅ `GET /api/v1/clients/{id}`
- ✅ `PUT /api/v1/clients/{id}`
- ✅ `DELETE /api/v1/clients/{id}`

### Citas (6/6)
- ✅ `GET /api/v1/appointments`
- ✅ `GET /api/v1/appointments/reschedule-queue`
- ✅ `POST /api/v1/appointments`
- ✅ `GET /api/v1/appointments/{id}`
- ✅ `PUT /api/v1/appointments/{id}`
- ✅ `DELETE /api/v1/appointments/{id}`

### Bloqueos de Disponibilidad (3/3)
- ✅ `GET /api/v1/availability-blocks`
- ✅ `POST /api/v1/availability-blocks`
- ✅ `DELETE /api/v1/availability-blocks/{id}`

### Endpoints Públicos (2/6)
- ✅ `GET /api/v1/public/available-slots`
- ✅ `POST /api/v1/public/book`
- ❌ `POST /api/v1/public/client-access` - **PENDIENTE (naming objetivo)**
- ❌ `GET /api/v1/public/client-portal` - **PENDIENTE (naming objetivo)**
- ❌ `POST /api/v1/public/client-portal/cancel/{id}` - **PENDIENTE (naming objetivo)**
- ❌ `POST /api/v1/public/client-portal/reschedule/{id}` - **PENDIENTE (naming objetivo)**

---

## ❌ ENDPOINTS PENDIENTES (22)

### 1. Usuarios (3 endpoints)

| Método | Endpoint | Estado | Archivo | Línea |
|--------|----------|--------|---------|-------|
| POST | `/api/v1/users` | ❌ Placeholder | `UserController.php` | 22-23 |
| PUT | `/api/v1/users/{id}` | ❌ Placeholder | `UserController.php` | 33-34 |
| DELETE | `/api/v1/users/{id}` | ❌ Placeholder | `UserController.php` | 39-40 |

**Inputs esperados (según docs):**
- POST: `{email, name, role, password}`
- PUT: `{email?, name?, role?, is_active?}`
- DELETE: Solo ID en URL

**Reglas de negocio:**
- Email único
- No se puede eliminar a uno mismo
- Solo ADMIN puede crear/editar/eliminar
- Roles: ADMIN, ASISTENTE

---

### 2. Historia Clínica (4 endpoints)

| Método | Endpoint | Estado | Archivo | Línea |
|--------|----------|--------|---------|-------|
| POST | `/api/v1/clients/{id}/clinical-history` | ❌ Placeholder | `ClinicalHistoryController.php` | 29-30 |
| PUT | `/api/v1/clients/{id}/clinical-history` | ❌ Placeholder | `ClinicalHistoryController.php` | 35-36 |
| POST | `/api/v1/clients/{id}/clinical-history/close` | ❌ Placeholder | `ClinicalHistoryController.php` | 41-42 |
| POST | `/api/v1/clients/{id}/clinical-history/unlock` | ❌ Placeholder | `ClinicalHistoryController.php` | 47-48 |

**Inputs esperados:**
- POST: `{chief_complaint?, history_present_illness?, past_psychiatric_history?, past_medical_history?, family_history?, social_history?, medications?, allergies?, mental_status_exam?, diagnosis?, treatment_plan?}`
- PUT: Mismos campos (solo si ABIERTA)
- POST close: Sin body
- POST unlock: `{reason}` (mínimo 10 caracteres)

**Reglas de negocio:**
- Un registro por cliente (unique constraint tecnico sobre `patient_id`)
- No se puede editar si está CERRADA
- Solo ADMIN puede desbloquear
- Requiere razón para desbloquear (mínimo 10 caracteres)
- Audit log al cerrar/desbloquear

---

### 3. Notas SOAP (4 endpoints)

| Método | Endpoint | Estado | Archivo | Línea |
|--------|----------|--------|---------|-------|
| POST | `/api/v1/clients/{id}/soap-notes` | ❌ Placeholder | `SOAPNoteController.php` | 26-27 |
| PUT | `/api/v1/soap-notes/{id}` | ❌ Placeholder | `SOAPNoteController.php` | 38-39 |
| POST | `/api/v1/soap-notes/{id}/sign` | ❌ Placeholder | `SOAPNoteController.php` | 44-45 |
| POST | `/api/v1/soap-notes/{id}/addendum` | ❌ Placeholder | `SOAPNoteController.php` | 50-51 |

**Inputs esperados:**
- POST: `{subjective?, objective?, assessment?, plan?, appointment_id?}`
- PUT: `{subjective?, objective?, assessment?, plan?}` (solo si BORRADOR)
- POST sign: Sin body
- POST addendum: `{content}` (solo si FIRMADA)

**Reglas de negocio:**
- No se puede editar si está FIRMADA
- Solo se pueden agregar addendums a notas FIRMADAS
- Audit log al firmar y agregar addendum
- Estados: BORRADOR, FIRMADA

---

### 4. Endpoints Públicos - Portal Cliente (4 endpoints)

| Método | Endpoint | Estado | Archivo | Línea |
|--------|----------|--------|---------|-------|
| POST | `/api/v1/public/client-access` | ❌ Placeholder | `PublicController.php` | 257-258 |
| GET | `/api/v1/public/client-portal` | ❌ Placeholder | `PublicController.php` | 263-264 |
| POST | `/api/v1/public/client-portal/cancel/{id}` | ❌ Placeholder | `PublicController.php` | 269-270 |
| POST | `/api/v1/public/client-portal/reschedule/{id}` | ❌ Placeholder | `PublicController.php` | 275-276 |

**Inputs esperados:**
- POST client-access: `{whatsapp}`
- GET client-portal: Query param `token`
- POST cancel: Query param `token`
- POST reschedule: Query params `token`, `new_date`, `new_start_time`, `new_end_time`

**Reglas de negocio:**
- `client-access`: Genera token seguro (24h), log en sandbox
- `client-portal`: Token valido y no expirado, NO incluye datos clinicos
- Validaciones de overlap y bloques igual que citas internas
- Solo puede cancelar/reagendar sus propias citas

---

### 5. Reportes (4 endpoints)

| Método | Endpoint | Estado | Archivo | Línea |
|--------|----------|--------|---------|-------|
| GET | `/api/v1/reports/appointments-stats` | ❌ Placeholder | `ReportController.php` | 13-14 |
| GET | `/api/v1/reports/pending-soap` | ❌ Placeholder | `ReportController.php` | 19-20 |
| GET | `/api/v1/reports/export/excel` | ❌ Placeholder | `ReportController.php` | 25-26 |
| GET | `/api/v1/reports/export/pdf` | ❌ Placeholder | `ReportController.php` | 31-32 |

**Inputs esperados:**
- appointments-stats: Query params `start_date?`, `end_date?`
- pending-soap: Sin params
- export/excel: Query params `start_date?`, `end_date?`, `status?`
- export/pdf: Query params `start_date?`, `end_date?`, `status?`

**Reglas de negocio:**
- `appointments-stats`: Total, por estado, tasa cancelación, tasa no-show
- `pending-soap`: Basado en `soap_interval` (setting), compara citas completadas vs notas SOAP
- Exportaciones: Filtros opcionales, formato específico (Excel/PDF)

---

### 6. Configuración (2 endpoints)

| Método | Endpoint | Estado | Archivo | Línea |
|--------|----------|--------|---------|-------|
| GET | `/api/v1/settings` | ❌ Placeholder | `SettingController.php` | 13-14 |
| PUT | `/api/v1/settings/{key}` | ❌ Placeholder | `SettingController.php` | 19-20 |

**Inputs esperados:**
- GET: Sin params
- PUT: `{value}`

**Reglas de negocio:**
- Solo ADMIN puede actualizar
- Settings clave: `appointment_duration`, `buffer_minutes`, `soap_interval`, `reminder_24h`, `reminder_2h`

---

### 7. Logs de Notificaciones (1 endpoint)

| Método | Endpoint | Estado | Archivo | Línea |
|--------|----------|--------|---------|-------|
| GET | `/api/v1/notification-logs` | ❌ Placeholder | `NotificationLogController.php` | 13-14 |

**Inputs esperados:**
- Query params: `patient_id?`, `limit?` (default: 100)

**Reglas de negocio:**
- Solo lectura (sandbox mode)
- Ordenado por `sent_at DESC`

---

## 📋 PRIORIZACIÓN

### P0 (Crítico - Frontend depende)
1. ✅ Clientes - COMPLETO
2. ✅ Citas - COMPLETO
3. ✅ Bloqueos - COMPLETO
4. ❌ Historia Clínica - **4 endpoints pendientes**
5. ❌ Notas SOAP - **4 endpoints pendientes**
6. ❌ Settings - **2 endpoints pendientes**
7. ❌ Reportes - **4 endpoints pendientes**

### P1 (Importante - Funcionalidad pública)
8. ❌ Portal Cliente - **4 endpoints pendientes**

### P2 (Menor prioridad)
9. ❌ Usuarios - **3 endpoints pendientes** (solo admin)
10. ❌ Notification Logs - **1 endpoint pendiente** (solo lectura)

---

## 🔍 ANÁLISIS DE MODELOS

### Modelos Existentes
- ✅ User
- ✅ Patient
- ✅ Appointment
- ✅ AvailabilityBlock
- ✅ ClinicalHistory
- ✅ SOAPNote
- ✅ SOAPNoteAddendum

### Modelos a Verificar/Crear
- ❓ Settings (tabla existe según migraciones, modelo puede faltar)
- ❓ NotificationLog (tabla existe según migraciones, modelo puede faltar)

---

## 📝 FORMATO DE RESPUESTA ACTUAL

### Éxito
```json
{
  "data": {...}  // o [...]
}
```

### Error
```json
{
  "message": "..."  // o "detail": "..."
}
```

### Status Codes
- 200: OK
- 201: Created
- 400: Bad Request (validación/reglas negocio)
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 500: Internal Server Error
- 501: Not Implemented (placeholders actuales)

---

## 🎯 PRÓXIMOS PASOS

1. ✅ Crear inventario completo
2. ⏳ Implementar sistema de errores consistente
3. ⏳ Verificar/crear modelos Settings y NotificationLog
4. ⏳ Implementar endpoints pendientes por prioridad
5. ⏳ Crear tests mínimos
6. ⏳ Actualizar documentación
