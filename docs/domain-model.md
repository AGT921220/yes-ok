# Domain Model - yes-ok

## Entidades principales

### Cliente

Representa a la persona que agenda y recibe servicios.

Campos funcionales relevantes:
- nombre(s) y apellidos
- telefono/whatsapp
- email (opcional)
- consentimiento de notificaciones

Nota tecnica:
- En implementacion actual, la entidad sigue mapeada internamente sobre `Patient/patients` por herencia tecnica.

### Cita

Reserva en agenda con fecha, hora inicio, hora fin y estado.

Estados comunes:
- PENDIENTE
- CONFIRMADA
- CANCELADA
- NO_SHOW
- COMPLETADA
- REQUIERE_REAGENDAR

La cita puede contener uno o varios servicios.

### Servicio

Elemento del catalogo seleccionable en una cita.

Campos actualmente soportados:
- `name`
- `duration_minutes`
- `is_active`
- `sort_order`

Campos de negocio deseables (deuda funcional):
- `price`
- `description`

### Disponibilidad / Bloqueo

Reglas de agenda para determinar si un horario puede reservarse.
- bloqueos de dia completo o por rango horario
- validacion de traslapes con citas existentes

### Usuario administrativo

Actores con acceso al panel:
- ADMIN
- ASISTENTE

### Notificacion

Eventos de confirmacion, recordatorio y cancelacion.
- push
- WhatsApp (segun configuracion actual)

### Producto

Articulo vendible del negocio (pomadas, ceras, shampoos, accesorios, etc.).

Campos base:
- `name`
- `description` (opcional)
- `sku` (opcional)
- `sale_price`
- `cost_price` (opcional)
- `stock`
- `min_stock` (opcional)
- `category` (opcional)
- `is_active`
- `image_path` (opcional, ruta preparada)

### Venta

Registro comercial de productos vendidos.

Campos base:
- `folio`
- `patient_id` (cliente opcional)
- `appointment_id` (cita opcional)
- `sale_date`
- `subtotal`
- `total`
- `payment_method` (opcional)
- `notes` (opcional)
- `status`
- `created_by`

### Detalle de venta

Lineas de productos dentro de una venta.

Campos base:
- `sale_id`
- `product_id`
- `quantity`
- `unit_price` (precio historico)
- `subtotal`

### Movimiento de inventario

Trazabilidad basica de entradas/salidas de stock.
- `type` (`in`, `out`, `adjust`)
- `quantity`
- `reference_type` y `reference_id` (ej. `sale`)
- `created_by`

## Relaciones

- Un cliente tiene muchas citas.
- Una cita pertenece a un cliente.
- Una cita tiene muchos servicios.
- Un servicio puede pertenecer a muchas citas.
- Bloqueos afectan disponibilidad de las citas.
- Un producto puede aparecer en muchas lineas de venta.
- Una venta tiene muchas lineas de venta.
- Una venta puede pertenecer opcionalmente a un cliente.
- Una venta puede relacionarse opcionalmente con una cita.

## Reglas de integridad

- No se permiten traslapes de citas activas.
- Servicios inactivos no deben asociarse a nuevas citas.
- La duracion total de cita debe ser mayor a 0 si se agenda por servicios.
- Horarios y bloqueos se validan antes de confirmar cita.
