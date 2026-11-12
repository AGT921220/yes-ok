# Cumplimiento y Seguridad — yes-ok

**Alcance del documento:** Evaluación de cumplimiento normativo (LFPDPPP, NOM-004-SSA3-2012) y buenas prácticas de seguridad para uso en auditoría interna. No constituye asesoría legal definitiva.

**Última actualización:** 2026-02-13

---

## 1. Alcance real del sistema

### 1.1 Según documentación y código

El sistema **no es solo agenda**. Incluye:

- **Agenda:** Citas, bloqueos de disponibilidad, estados (Pendiente, Confirmada, Cancelada, No Show, Completada, Requiere Reagendar).  
  *Evidencia:* `docs/overview.md` (gestión de agenda, bloqueos); tablas `appointments`, `availability_blocks` en `docs/database/schema.md` y migraciones en `backend/database/migrations/`.

- **Expediente clínico digital:** Historia clínica única por paciente (cerrable/desbloqueable), notas SOAP (Subjetivo, Objetivo, Evaluación, Plan) con firma digital y addendums.  
  *Evidencia:* `docs/overview.md` ("Expediente Clínico Digital", "Historia clínica", "Notas SOAP"); tablas `clinical_histories`, `soap_notes`, `soap_note_addendums` en `docs/database/schema.md` y en `backend/database/migrations/` (`create_clinical_histories_table.php`, `create_soap_notes_table.php`, `create_soap_note_addendums_table.php`).

- **Datos de pacientes:** Identificación, contacto (WhatsApp, email), consentimiento de notificaciones, token de acceso al portal.  
  *Evidencia:* `docs/arquitectura-roles-accesos.md` (tabla `patients`); migración `create_patients_table.php` (first_name, paternal_surname, maternal_surname, whatsapp, email, consent_notifications, access_token, etc.).

- **Usuarios y roles:** ADMIN, ASISTENTE, PACIENTE; autenticación JWT; portal del paciente con acceso limitado a sus datos.  
  *Evidencia:* `docs/arquitectura-roles-accesos.md`, `docs/auth-clientes.md`, `docs/patient-portal.md`; middleware `role.staff`, `role.patient`; rutas `/api/v1/me/*`.

- **Notificaciones:** Logs de notificaciones (sandbox/WhatsApp); push tokens (device_tokens).  
  *Evidencia:* `docs/push-notifications.md`; tablas `notification_logs`, `device_tokens`.

### 1.2 Obligaciones por tipo de datos

- **Aunque solo fuera agenda:** Se tratan datos personales (nombre, teléfono, email, fechas de citas) y, en México, aplican la **Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP)** y su Reglamento (aviso de privacidad, consentimiento cuando aplique, medidas de seguridad, derechos ARCO).

- **Al existir expediente clínico:** Se tratan **datos sensibles** (salud, diagnóstico, tratamiento, antecedentes psiquiátricos/médicos). La LFPDPPP exige consentimiento expreso y por escrito para datos sensibles, y medidas de seguridad reforzadas. Además aplica la **NOM-004-SSA3-2012** (Expediente clínico) en lo que respecta a contenido, integridad, conservación y acceso al expediente.

**Conclusión:** El sistema entra en el ámbito de la LFPDPPP (y Reglamento) y, por el módulo de historia clínica y notas SOAP, en el de la NOM-004-SSA3-2012 para expediente clínico.

---

## 2. Inventario de datos

### 2.1 Datos personales (identificación y contacto)

| Dato | Dónde se almacena | Evidencia |
|------|-------------------|-----------|
| Nombre(s), apellidos | `patients.first_name`, `paternal_surname`, `maternal_surname`; `users.name` | Migraciones `create_patients_table.php`, `create_users_table.php`; `docs/arquitectura-roles-accesos.md` |
| Email | `users.email`, `patients.email` | Idem |
| Teléfono / WhatsApp | `users.phone`, `patients.whatsapp` | Migración; `add_phone_to_users_table.php` |
| Contraseña (hash) | `users.password` | Migración users; `docs/arquitectura-roles-accesos.md` (bcrypt) |

### 2.2 Datos sensibles (salud)

| Dato | Dónde se almacena | Evidencia |
|------|-------------------|-----------|
| Motivo de consulta, historia de la enfermedad actual | `clinical_histories.chief_complaint`, `history_present_illness` | `create_clinical_histories_table.php` |
| Antecedentes psiquiátricos/médicos/familiares/sociales | `clinical_histories.past_psychiatric_history`, `past_medical_history`, `family_history`, `social_history` | Idem |
| Medicamentos, alergias | `clinical_histories.medications`, `allergies` | Idem |
| Examen de estado mental | `clinical_histories.mental_status_exam` | Idem |
| Diagnóstico y plan de tratamiento | `clinical_histories.diagnosis`, `treatment_plan` | Idem |
| Notas SOAP (Subjetivo, Objetivo, Evaluación, Plan) | `soap_notes.subjective`, `objective`, `assessment`, `plan` | `create_soap_notes_table.php` |
| Addendums a notas firmadas | `soap_note_addendums.content` | `create_soap_note_addendums_table.php` |
| Notas de cita / motivo | `appointments.notes`, `appointments.reason` | `create_appointments_table.php` |

### 2.3 Otros datos tratados

| Dato | Dónde | Evidencia |
|------|--------|-----------|
| Consentimiento notificaciones | `patients.consent_notifications` | Migración patients; `StorePatientRequest` |
| Token de acceso portal (magic link) | `patients.access_token`, `access_token_expires` | Migración patients; `docs/patient-portal.md` |
| Tokens push | `device_tokens` (user_id, token, platform, device_name, app_version, last_seen_at, revoked_at) | `docs/push-notifications.md`; migración `create_device_tokens_table.php` |
| Logs de notificaciones | `notification_logs` (patient_id, appointment_id, channel, message, status) | Migración `create_notification_logs_table.php` |

---

## 3. Checklist de cumplimiento

| Requisito (legal/técnico) | Evidencia encontrada | Estado | Acción recomendada |
|---------------------------|----------------------|--------|---------------------|
| Aviso de privacidad disponible para el titular | No se encontró referencia en código ni en docs a aviso de privacidad ni URL/pantalla donde se muestre | **No evidenciado** | Redactar aviso de privacidad (con asesoría legal) y publicarlo; en registro/alta de paciente y en portal mostrar enlace y obtener aceptación. |
| Consentimiento expreso y por escrito para datos sensibles (LFPDPPP) | `patients.consent_notifications` existe; no hay campo ni flujo documentado para "consentimiento tratamiento datos de salud" | **Parcial** | Añadir consentimiento específico para expediente/datos de salud (checkbox + texto + registro de fecha/ip si aplica). Documentar en flujo de alta y en expediente. |
| Medidas de seguridad técnicas (control de acceso) | Roles y middleware: `role.staff`, `role.patient`; `AppointmentPolicy` (view/update/delete); MeController filtra por `auth()->user()->patient` | **Cumple** | Revisar que todas las entidades con datos sensibles (clinical_histories, soap_notes) estén protegidas por políticas equivalentes; documentar en este doc. |
| Contraseñas no almacenadas en texto plano | Documentación y código: bcrypt para contraseñas; contraseña generada se devuelve una sola vez | **Cumple** | Mantener; no almacenar contraseñas en logs. |
| Cifrado en tránsito (HTTPS) | `docs/setup.md` y `backend/README.md`: "Configurar HTTPS en Nginx" en checklist pre-producción | **Parcial** | Asegurar HTTPS en todos los entornos donde se acceda a datos personales; documentar en procedimiento de despliegue. |
| Cifrado de datos sensibles en reposo | Laravel `APP_KEY` para cifrado de valores; no se encontró cifrado a nivel de campo para historia clínica / SOAP en BD | **Parcial** | Valorar (con riesgo y normativa) cifrado de columnas de datos sensibles o de BD; documentar decisión. |
| Registro de accesos / auditoría (trazabilidad) | Tabla `audit_logs` existe; controladores tienen "TODO: Agregar audit log cuando exista el modelo AuditLog" (SOAPNoteController, ClinicalHistoryController) | **Parcial** | Implementar modelo/servicio de auditoría y registrar accesos y cambios en expediente, citas y datos sensibles; conservar logs según requisitos legales. |
| Retención y eliminación de datos | No se encontró política documentada de retención ni procedimiento de eliminación/anonimización | **No evidenciado** | Definir política de retención (LFPDPPP, NOM-004) y procedimiento para baja y eliminación/anonimización; documentar aquí. |
| Respuesta a derechos ARCO | No se encontró flujo ni endpoint para solicitudes de acceso, rectificación, cancelación u oposición | **No evidenciado** | Definir proceso (plazos, responsable) y, si aplica, implementar endpoints o formularios para ejercicio de derechos ARCO; enlazar en aviso de privacidad. |
| Backups de base de datos | Mencionado en `docs/setup.md` ("Configurar backups de MySQL") y `backend/README.md` ("Configurar backups de base de datos") como pendiente | **Parcial** | Implementar backups automáticos, cifrados y restauración probada; documentar en DEPLOYMENT o en este doc. |
| NOM-004: Expediente clínico (identificación, contenido, integridad) | Contenido de historia clínica y notas SOAP alineado con práctica de expediente; firma digital en SOAP (signed_by, signed_at); no se verificó NOM-004 artículo por artículo | **Parcial** | Revisar NOM-004 completa (identificación del paciente, requisitos de contenido, conservación, bloqueo) y ajustar flujos/registros si hace falta; dejar constancia en este doc. |
| Control de acceso por rol (principio de mínimo privilegio) | Staff vs paciente; PACIENTE solo accede a `/api/v1/me/*` y a sus propias citas/perfil; ADMIN/ASISTENTE restricciones documentadas | **Cumple** | Mantener; extender políticas a todas las entidades con datos personales/sensibles. |

---

## 4. Brechas detectadas (Gap Analysis)

| Brecha | Prioridad | Impacto | Evidencia |
|--------|-----------|---------|-----------|
| Ausencia de aviso de privacidad y de flujo de aceptación | **Crítico** | Legal (LFPDPPP): obligación de informar y, para datos sensibles, consentimiento expreso | Búsqueda en repo: no hay "aviso de privacidad" ni "privacidad" en docs funcionales |
| Consentimiento específico para datos de salud no implementado ni documentado | **Crítico** | Legal: datos sensibles requieren consentimiento expreso por escrito | Solo existe `consent_notifications`; no "consentimiento datos de salud" |
| Auditoría (audit_log) no implementada en controladores | **Alto** | Seguridad y cumplimiento: sin trazabilidad de accesos/cambios a expediente | `SOAPNoteController.php`, `ClinicalHistoryController.php`: TODO audit log; tabla existe pero no modelo/servicio usado |
| Derechos ARCO sin proceso ni canal documentado | **Alto** | Legal: obligación de atender acceso, rectificación, cancelación y oposición | No encontrado en código ni docs |
| Backups no implementados | **Alto** | Operativo y legal: pérdida de datos y posible incumplimiento de conservación | setup.md, backend/README: "Configurar backups" como pendiente |
| Cifrado en reposo de datos sensibles no verificado | **Medio** | Seguridad: riesgo si hay acceso no autorizado a BD o copias | No hay evidencia de cifrado a nivel de campo para clinical_histories/soap_notes |
| Política de retención y eliminación no documentada | **Medio** | Legal/operativo: LFPDPPP y NOM-004 exigen criterios de conservación y eliminación | No encontrado |
| Revisión explícita de NOM-004 (expediente clínico) | **Medio** | Cumplimiento normativo sector salud | Doc schema y overview confirman expediente; no hay checklist NOM-004 en repo |

---

## 5. Plan de remediación

| # | Acción | Cambios (archivos/áreas) |
|---|--------|---------------------------|
| 1 | Redactar y publicar aviso de privacidad (con asesoría legal). Incluir en registro de paciente y en portal (enlace visible). | Nuevo: texto en `/docs/aviso-de-privacidad-plantilla.md` o similar; frontend: enlace en formulario de paciente y en "Mi cuenta"; opcional: tabla `privacy_acceptances` (user_id/patient_id, version_aviso, ip, fecha). |
| 2 | Implementar consentimiento expreso para tratamiento de datos de salud. | Backend: migración para `patients.consent_health_data` (boolean) y `consent_health_data_at` (timestamp); actualizar `StorePatientRequest`/`UpdatePatientRequest` y flujo de alta; frontend: checkbox con texto claro + registro de aceptación. |
| 3 | Implementar auditoría (uso de `audit_logs`). | Backend: crear `App\Models\AuditLog` y servicio `AuditLogService::log($userId, $action, $entityType, $entityId, $details)`; llamar desde ClinicalHistoryController (create/update/close/unlock), SOAPNoteController (store/update/sign/addendum), PatientController (store/update), AppointmentController (store/update/destroy) y otros que modifiquen datos sensibles; incluir ip_address si está disponible. |
| 4 | Documentar e implementar proceso de derechos ARCO. | Docs: procedimiento (plazos, responsable, formato de solicitud); opcional: endpoint o formulario para solicitudes; actualizar aviso de privacidad con contacto y procedimiento. |
| 5 | Configurar backups de base de datos. | Scripts o cron para dump MySQL; almacenamiento seguro y cifrado; prueba de restauración; documentar en `docs/DEPLOYMENT.md` o en este doc. |
| 6 | Asegurar HTTPS en producción y documentar. | Nginx/entorno: certificados y redirección HTTP→HTTPS; `docs/DEPLOYMENT.md`: requisito HTTPS y cómo verificarlo. |
| 7 | Revisar NOM-004-SSA3-2012 y ajustar expediente si aplica. | Revisión manual de la NOM (identificación, contenido mínimo, conservación, bloqueo); actualizar este doc con "Cumple NOM-004" o ítems pendientes; cambios en flujos/UI si hace falta. |
| 8 | Definir política de retención y eliminación. | Doc interno: plazos de conservación (ej. expediente según NOM-004); procedimiento de baja (anonimización o eliminación) y dónde se registra; actualizar aviso de privacidad si corresponde. |

---

## 6. Texto mínimo sugerido (plantilla técnica)

**Uso:** Solo como base técnica. Debe ser revisado y adaptado por un profesional legal antes de usarse.

### 6.1 Aviso de privacidad (fragmento sugerido)

> **Aviso de privacidad**  
> [Nombre del responsable / consultorio], con domicilio en [dirección], es responsable del tratamiento de sus datos personales. Los datos que recabamos (nombre, apellidos, teléfono, correo, datos de salud necesarios para su atención) se utilizan para [agendar citas, llevar su expediente clínico, notificaciones y las finalidades que indiquen en el aviso completo]. Puede acceder al aviso de privacidad integral en [URL]. Usted puede ejercer sus derechos de acceso, rectificación, cancelación y oposición contactando a [correo o medio]. Los datos de salud se tratan con su consentimiento expreso.

### 6.2 Consentimiento expreso (datos de salud)

> He leído el aviso de privacidad y acepto que mis datos personales y los datos relativos a mi salud (historia clínica, notas de evolución, diagnóstico y tratamiento) sean tratados conforme a dicho aviso, exclusivamente para los fines de mi atención médica y las finalidades allí descritas. Entiendo que puedo revocar este consentimiento en los términos que se indican en el aviso de privacidad.

*(Se recomienda checkbox obligatorio + registro de fecha y, si se tiene, IP o identificador de sesión.)*

---

## 7. Suposiciones y preguntas abiertas

- **Responsable del tratamiento:** Se asume que es el titular del consultorio/psiquiatra; no está identificado explícitamente en el repositorio. Debe quedar claro en el aviso de privacidad.
- **Alcance de NOM-004:** La NOM aplica a establecimientos de atención a la salud; se asume que el uso del sistema está en ese contexto. La revisión artículo por artículo de la NOM no se realizó en este documento.
- **Datos en notificaciones y logs:** `notification_logs` y `device_tokens` pueden contener identificadores o mensajes; conviene incluirlos en el inventario de datos y en el aviso de privacidad (finalidad, retención).
- **Portal público (agendar sin login):** Si se capturan datos personales en el flujo de agendar cita sin cuenta, debe mostrarse aviso de privacidad y, según el caso, consentimiento antes de enviar el formulario.

---

## 8. Referencias a documentación existente

| Archivo | Secciones relevantes |
|---------|----------------------|
| `docs/overview.md` | Descripción del sistema (agenda, expediente clínico, SOAP, portal paciente, reportes). |
| `docs/arquitectura-roles-accesos.md` | Roles (ADMIN, ASISTENTE, PACIENTE), tablas users/clients (internamente `patients`), validaciones, contraseñas (bcrypt), consideraciones de seguridad (sección 10). |
| `docs/auth-clientes.md` | Creación de usuarios PACIENTE, login, consent_notifications. |
| `docs/patient-portal.md` | Rutas API `/me/*`, middleware role.patient, políticas (AppointmentPolicy), filtrado por patient_id. |
| `docs/database/schema.md` | Esquema de tablas (users, patients, appointments, clinical_histories, soap_notes, audit_logs, etc.). |
| `docs/risks.md` | Riesgos (audit logs no implementados, permisos, JWT, backups no configurados). |
| `docs/setup.md` | Checklist pre-producción (HTTPS, backups MySQL, logs). |
| `backend/README.md` | Producción: backups de base de datos. |
| `backend/database/migrations/` | Estructura real de tablas (patients, clinical_histories, soap_notes, audit_logs, etc.). |
| `backend/app/Policies/AppointmentPolicy.php` | Control de acceso a citas por rol. |
| `backend/app/Http/Controllers/Api/V1/ClinicalHistoryController.php`, `SOAPNoteController.php` | TODOs de audit log. |

---

*Documento generado para auditoría interna. Para cumplimiento legal definitivo, consulte a un profesional del derecho y, en materia de salud, a un especialista en normativa aplicable.*
