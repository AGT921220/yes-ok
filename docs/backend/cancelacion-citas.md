# Flujo de cancelación de citas (yes-ok)

## Mapa del flujo

```
request (POST /appointments/{id}/cancel con cancellation_reason)
  → AppointmentController::cancel (authorize + CancelAppointmentRequest)
  → AppointmentService::cancel (transacción)
      1. UPDATE atómico: WHERE id = ? AND status NOT IN (CANCELADA, COMPLETADA)
      2. Si affected === 0 → return null (409)
      3. Log::info('Appointment cancelled')
      4. event(AppointmentCancelled)
  → response: 200 { success, message, data: AppointmentResource } o 409
  → Listener SendCancellationNotifications (en cola): escribe notification_logs
```

- **Portal paciente** `POST /api/v1/public/patient-portal/cancel/{id}?token=...`: mismo servicio con `byPatient=true`, motivo fijo "Cancelado por el paciente desde el portal". Respuesta 409 si ya cancelada/completada.

## Estados

- Se mantiene el enum existente: `PENDIENTE`, `CONFIRMADA`, `CANCELADA`, `NO_SHOW`, `COMPLETADA`, `REQUIERE_REAGENDAR`.
- No cancelables: `CANCELADA`, `COMPLETADA` (protección en UPDATE).

## Liberación de horario

- No hay tabla `schedules`/`time_slots`. La disponibilidad se calcula con citas activas (`status != CANCELADA`) y bloques. Al poner `status = CANCELADA`, el slot queda libre automáticamente en `availableSlots` y `checkOverlap`.

## Respuesta API

- **200**: `{ "success": true, "message": "Cita cancelada correctamente", "data": { ...appointment } }`
- **409**: Cita ya cancelada o completada.
- **403**: Sin permisos (policy).
- **404**: Cita no encontrada (ej. portal: no es del paciente).

## Cómo probar manualmente

1. **Cancelar desde staff (Dashboard o Por Reagendar)**  
   - Login staff → Agenda → abrir una cita → "Cancelar cita" → motivo → confirmar.  
   - Verificar: calendario actualizado, cita con status CANCELADA; en BD `status = CANCELADA` y `cancellation_reason` guardado.  
   - Segunda cancelación sobre la misma cita: debe devolver 409 y no cambiar la BD.

2. **Portal paciente**  
   - Obtener token (patient-access) → `POST /api/v1/public/patient-portal/cancel/{id}?token=...`.  
   - Verificar 200 y que la cita quede CANCELADA; sin token o cita de otro paciente: 401/404.

3. **Horario liberado**  
   - Crear cita en un slot → cancelarla → llamar `GET /api/v1/public/available-slots?target_date=YYYY-MM-DD`.  
   - Ese slot debe aparecer de nuevo disponible.

4. **Notificación**  
   - Tras cancelar, revisar tabla `notification_logs`: debe existir una fila con `template_name = 'cancellation'`.
