# Booking Flow - yes-ok

## Flujo principal de agendamiento

1. Cliente ingresa al flujo de reserva.
2. Selecciona uno o varios servicios.
3. Sistema calcula duracion total estimada.
4. Cliente selecciona fecha y hora.
5. Sistema valida:
   - traslapes con citas activas
   - bloqueos de disponibilidad
   - reglas de horario habilitado
6. Se registra la cita con resumen de servicios.
7. Se muestra confirmacion al cliente.
8. Se dispara flujo de notificacion (segun configuracion).

## Calculo de duracion

- Cada servicio tiene `duration_minutes`.
- Duracion total cita:
  - `total = SUM(duration_minutes de servicios seleccionados)`
- `end_time` se deriva de:
  - `start_time + total`

## Escenarios ejemplo

- corte (30) + barba (20) = 50 minutos
- tinte (60) + corte (30) + peinado (45) = 135 minutos

## Reglas de disponibilidad

- Un slot es valido solo si cabe la duracion total completa.
- No se permite ocupar un rango que colisione con:
  - otra cita activa
  - bloqueo de agenda

## Relacion opcional con ventas

- Una vez registrada la cita, el staff/admin puede registrar una venta de productos:
  - independiente
  - o vinculada a la cita para trazabilidad
- Si la venta se vincula, queda referencia en `sales.appointment_id`.
- El flujo de agenda no cambia ni se bloquea por ventas.

## Datos minimos de confirmacion

- cliente
- fecha
- hora inicio / hora fin
- lista de servicios
- duracion total
- estado inicial de cita
