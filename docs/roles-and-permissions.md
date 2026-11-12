# Roles and Permissions - yes-ok

## Roles del sistema

### ADMIN

Permisos esperados:
- gestionar usuarios internos
- gestionar clientes
- gestionar citas y bloqueos
- gestionar catalogo de servicios
- configurar parametros generales

### ASISTENTE

Permisos esperados:
- gestionar clientes
- gestionar citas y agenda
- consultar reportes operativos

Restricciones:
- no gestiona usuarios administrativos
- no modifica configuraciones sensibles (segun politica del proyecto)

### CLIENTE

Permisos esperados:
- reservar cita desde flujo publico
- seleccionar servicios disponibles
- consultar sus citas en portal
- cancelar/reagendar segun reglas

## Matriz resumida

- `clientes`: ADMIN, ASISTENTE
- `citas`: ADMIN, ASISTENTE (cliente solo sobre sus propias citas)
- `servicios`: ADMIN (y opcionalmente ASISTENTE segun configuracion)
- `usuarios internos`: ADMIN
- `configuracion`: ADMIN

## Notas de implementacion

- Existen nombres tecnicos heredados (`Patient`, `role=PACIENTE`) que en negocio se reinterpretan como cliente.
- La API principal de dominio para front se estandariza en `/api/v1/clients`, manteniendo rutas legacy de compatibilidad donde aplique.
