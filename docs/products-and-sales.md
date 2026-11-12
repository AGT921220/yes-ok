# Products and Sales - yes-ok

Este módulo se retiró. No hay modelos, migraciones ni pantallas de productos o ventas. El documento de abajo es histórico.



## Objetivo del modulo

Agregar venta de productos al sistema sin afectar el core de agenda/citas.

Incluye:
- catalogo de productos
- inventario basico
- registro de ventas
- asociacion opcional con cliente y cita

## Catalogo de productos

Campos soportados:
- `name` (requerido)
- `description` (opcional)
- `sku` (opcional, unico)
- `sale_price` (requerido)
- `cost_price` (opcional)
- `stock` (requerido)
- `min_stock` (opcional)
- `category` (opcional)
- `is_active` (activo/inactivo)
- `image_path` (opcional; ruta preparada)

Reglas:
- productos inactivos no se listan por defecto para venta
- productos inactivos no pueden venderse

## Inventario basico

Version inicial:
- stock almacenado en `products.stock`
- al registrar venta confirmada se descuenta stock
- no se permite vender cantidad mayor al stock disponible

Trazabilidad:
- tabla `inventory_movements`
- cada salida por venta registra:
  - `type = out`
  - `reference_type = sale`
  - `reference_id = sale.id`

## Ventas

Entidad `sales`:
- `folio` unico
- `patient_id` nullable (cliente opcional)
- `appointment_id` nullable (cita opcional)
- `sale_date`
- `subtotal`
- `total`
- `payment_method` nullable
- `notes` nullable
- `status`
- `created_by`

Entidad `sale_items`:
- `sale_id`
- `product_id`
- `quantity`
- `unit_price` (precio historico)
- `subtotal`

Reglas:
1. una venta tiene una o mas lineas
2. cada linea guarda el precio al momento de venta
3. subtotal/total se calculan en backend
4. stock se descuenta en transaccion atomica

## Relacion con citas

`appointment_id` es opcional.

Escenarios:
- venta independiente (sin cita)
- venta vinculada a cita (trazabilidad de consumo posterior al servicio)

## Endpoints agregados (v1)

Productos:
- `GET /api/v1/products`
- `GET /api/v1/products/{id}`
- `POST /api/v1/products`
- `PUT /api/v1/products/{id}`
- `DELETE /api/v1/products/{id}` (desactiva)

Ventas:
- `GET /api/v1/sales`
- `GET /api/v1/sales/{id}`
- `POST /api/v1/sales`

## Panel administrativo

Secciones nuevas:
- `Productos` (`/productos`)
- `Ventas` (`/ventas`)
- `Detalle de venta` (`/ventas/{id}`)

## Pendientes recomendados

- modulo de ajustes manuales de inventario (entradas/salidas)
- alertas visuales de stock bajo (`stock <= min_stock`)
- devoluciones/cancelacion de venta con reversa de stock
- carga real de imagen de producto (storage + uploader)
