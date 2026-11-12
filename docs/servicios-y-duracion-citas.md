# Servicios y duración variable de citas (yes-ok)

## Entidad `services`

Catálogo de servicios que se pueden reservar.

Campos principales:
- `name`: nombre del servicio
- `duration_minutes`: duración por servicio (solo `15`, `30`, `45`, `60`)
- `is_active`: habilita/deshabilita el servicio
- `sort_order`: orden de visualización

## Pivot `appointment_service`

Relación N:N entre `appointments` y `services`.

- Una cita puede tener uno o varios servicios.
- `sort_order` en la pivot conserva el orden de selección.

## Regla de duración total

La duración total de una cita es:

`duración_total = SUM(duration_minutes de cada service seleccionado)`

Ejemplos:
- Corte (30) + Barba (15) = 45 min
- Peinado (60) + Uñas (45) = 105 min

## Cálculo de `end_time`

Cuando se envían `service_ids[]` en:
- `POST /api/v1/appointments`
- `PUT /api/v1/appointments/{id}`
- `POST /api/v1/public/book`

El backend calcula:
- `end_time = start_time + duración_total`

Cuando NO se envían servicios:
- se usa `end_time` recibido (comportamiento legado).

## Disponibilidad pública de slots

`GET /api/v1/public/available-slots`

Parámetros:
- `target_date` (requerido): `YYYY-MM-DD`
- `duration_minutes` (opcional): **duración total** para el slot
  - debe ser múltiplo de 15 (porque proviene de sumatorias de servicios)
  - rango esperado: `15` a `240`
  - default: `60` (compatibilidad)

La disponibilidad:
- genera slots desde `12:00` hasta `20:00`
- usa pasos de `15` minutos
- valida traslapes contra citas existentes y bloqueos

## Validaciones

1. `service_ids[]`
   - deben existir y estar activos
   - sus duraciones deben estar en `[15, 30, 45, 60]`
2. traslapes (overlap)
   - se valida con `[start_time, end_time]` real de la cita
3. bloqueos
   - se respetan `availability_blocks` con o sin horas
4. horario permitido (si `forced=false`)
   - día: lunes a sábado
   - `start_time >= 12:00`
   - `end_time <= 20:00`

## Tests sugeridos (para duración variable)

1. Crear cita con 1 servicio:
   - `start_time=14:00`, servicio 30 -> `end_time=14:30`
2. Crear cita con varios servicios:
   - 30 + 45 = 75 -> `end_time=start + 75`
3. Traslape:
   - cita A 14:00–14:30 y cita B 14:15–14:45 -> rechazar
4. Slots:
   - `available-slots?duration_minutes=90` devuelve slots de 90 min y no choca con citas

