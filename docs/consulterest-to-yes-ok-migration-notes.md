# ConsulteRest to yes-ok - Migration Notes

## Que se conserva del core

- Motor de agenda y citas
- Validacion de traslapes y bloqueos
- Panel administrativo
- Flujo de reserva y notificaciones
- Estructura tecnica general (backend/frontend/mobile)

## Que cambia en negocio

- paciente -> cliente
- consulta -> cita
- agenda medica -> agenda de servicios
- foco clinico -> foco operativo de negocio de servicios

## Adaptaciones de dominio esperadas

1. Catalogo de servicios administrable
2. Citas con multiples servicios
3. Duracion de cita calculada por suma de servicios
4. Disponibilidad dependiente de duracion total

## Discrepancias detectadas (documentacion vs implementacion)

- Persisten componentes clinicos en codigo/documentos heredados:
  - referencias a expediente, SOAP, historia clinica
  - nombres internos `Patient/patients`
- En negocio, esos nombres deben considerarse herencia tecnica y no lenguaje funcional recomendado.

## Renombres pendientes / deuda tecnica

- Renombrar entidades internas `Patient/patients` a `Client/clients` en capa de dominio interna (requiere plan de migracion de DB y compatibilidad).
- Revisar y depurar modulos clinicos no aplicables si el producto final deja de requerirlos.
- Unificar nombres de rutas y servicios para evitar mezcla de vocabulario medico.
- Definir estrategia de versionado de API para retirar endpoints legacy `/patients` en el futuro.

## Recomendacion de rollout

1. Mantener compatibilidad temporal (`/patients` y `/clients`).
2. Migrar frontends y clientes API a `/clients`.
3. Anunciar deprecacion de rutas legacy.
4. Ejecutar refactor interno de entidades en una version mayor.
