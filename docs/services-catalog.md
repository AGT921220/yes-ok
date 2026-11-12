# Services Catalog - yes-ok

## Objetivo

Definir como se administra el catalogo de servicios y su impacto en agenda.

## Gestion administrativa

El administrador debe poder:
- crear servicios
- editar servicios
- activar/desactivar servicios
- visualizar catalogo vigente

## Campos de servicio

### Implementados

- `name` (nombre)
- `duration_minutes` (duracion en minutos)
- `is_active` (activo/inactivo)
- `sort_order` (orden de despliegue)

### Recomendados para evolucion

- `price` (precio)
- `description` (descripcion opcional)

Nota:
- Si `price` y `description` no existen aun en implementacion, se consideran campos funcionales propuestos.

## Reglas de negocio

1. Solo servicios activos pueden seleccionarse al agendar.
2. Una cita puede incluir multiples servicios.
3. La duracion total de la cita depende de la suma de duraciones del catalogo.
4. Cambios en servicios afectan disponibilidad futura, no deben alterar historico de citas previas sin estrategia explicita.

## Impacto en agenda

- Slots disponibles deben calcularse en funcion de la duracion total.
- Si un servicio aumenta duracion, puede reducir huecos disponibles.
- Si un servicio se desactiva, deja de aparecer para nuevas reservas.
