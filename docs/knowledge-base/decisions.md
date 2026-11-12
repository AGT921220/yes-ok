# Decisiones Arquitectónicas - yes-ok

Este documento registra las decisiones arquitectónicas importantes del proyecto, el contexto en que se tomaron, y las alternativas consideradas.

---

## DEC-001: Migración de PostgreSQL a MySQL

**Fecha**: 2026-02-05  
**Decisor**: Usuario  
**Estado**: ✅ Aceptada

### Contexto
El backend Python original usa PostgreSQL, pero el usuario solicitó usar MySQL 8.0 para el backend Laravel.

### Decisión
Usar MySQL 8.0 en lugar de PostgreSQL para el backend Laravel.

### Alternativas Consideradas
1. **Mantener PostgreSQL**: Más consistencia con backend Python, pero usuario prefiere MySQL
2. **Usar SQLite**: Más simple pero no adecuado para producción

### Consecuencias
- ✅ MySQL es más común en entornos Laravel
- ✅ Compatibilidad con herramientas estándar
- ⚠️ Requiere migración de datos si hay datos existentes en PostgreSQL
- ⚠️ Algunas funciones específicas de PostgreSQL no disponibles

### Referencias
- `docker-compose.yml` - Configuración MySQL
- `backend/config/database.php` - Configuración Laravel

---

## DEC-002: Arquitectura Hexagonal Simplificada

**Fecha**: 2026-02-05  
**Decisor**: AI Lead Engineer  
**Estado**: ✅ Aceptada

### Contexto
Necesitamos una arquitectura limpia pero no sobre-ingeniería. El proyecto debe ser mantenible y escalable sin complejidad innecesaria.

### Decisión
Usar arquitectura hexagonal simplificada:
- **Controllers**: Delgados, solo validan y delegan
- **Services/UseCases**: Lógica de negocio
- **Repositories**: Acceso a datos (interfaces + implementaciones)
- **Resources**: Transformación de respuestas API
- **Models**: Mínimos, solo relaciones y casts

### Alternativas Consideradas
1. **Full DDD**: Demasiado complejo para el tamaño del proyecto
2. **MVC Tradicional**: No suficiente separación de responsabilidades
3. **Arquitectura por Features**: Considerada para futuro, pero estructura plana por ahora

### Consecuencias
- ✅ Separación clara de responsabilidades
- ✅ Fácil de testear (Services y Repositories)
- ✅ Mantenible y escalable
- ⚠️ Más archivos que MVC tradicional

### Referencias
- `/docs/architecture.md`
- `/docs/structure.md`

---

## DEC-003: Frontend Integrado en Backend

**Fecha**: 2026-02-05  
**Decisor**: AI Lead Engineer (previo)  
**Estado**: ✅ Aceptada

### Contexto
El frontend React está integrado en `backend/resources/frontend/` y se compila a `backend/public/app/`.

### Decisión
Mantener frontend integrado en el backend Laravel, servido por Nginx.

### Alternativas Consideradas
1. **Frontend Separado**: Más flexible pero requiere CORS y configuración adicional
2. **SSR con Laravel**: Más complejo, no necesario para SPA

### Consecuencias
- ✅ Un solo dominio, sin problemas de CORS
- ✅ Despliegue simplificado
- ⚠️ Build manual del frontend necesario
- ⚠️ Frontend y backend acoplados en el mismo repo

### Referencias
- `INTEGRATION_SUMMARY.md`
- `docker/nginx/default.conf`

---

## DEC-004: Laravel Horizon Obligatorio

**Fecha**: 2026-02-05  
**Decisor**: Usuario (requisito inicial)  
**Estado**: ✅ Aceptada

### Contexto
El usuario especificó que Laravel Horizon es obligatorio para gestión de colas y dashboard.

### Decisión
Usar Laravel Horizon para todas las colas, con dashboard accesible.

### Alternativas Consideradas
1. **Queue Workers Tradicionales**: No tiene dashboard
2. **Redis Queue sin Horizon**: Menos funcionalidad

### Consecuencias
- ✅ Dashboard visual de colas
- ✅ Mejor monitoreo y debugging
- ✅ Configuración avanzada de colas
- ⚠️ Requiere Redis (ya estaba en requisitos)

### Referencias
- `docker-compose.yml` - Servicio horizon
- `docker/horizon.conf` - Configuración Supervisor

---

## DEC-005: Sin Postfix en Docker

**Fecha**: 2026-02-05  
**Decisor**: Usuario (requisito inicial)  
**Estado**: ✅ Aceptada

### Contexto
El usuario especificó explícitamente NO incluir postfix en Docker.

### Decisión
No incluir servicio de email en Docker Compose. El envío de emails se manejará externamente o vía servicios terceros (SMTP).

### Alternativas Consideradas
1. **Incluir Postfix**: Más simple pero usuario no lo quiere
2. **Mailhog para desarrollo**: Considerado pero no implementado aún

### Consecuencias
- ✅ Menos servicios en Docker
- ⚠️ Requiere configuración externa de SMTP para producción
- ⚠️ Desarrollo local puede requerir servicio de email mock

### Referencias
- `docker-compose.yml` - No incluye postfix
- Requisitos iniciales del usuario

---

## DEC-006: Prohibición de else/elseif

**Fecha**: 2026-02-05  
**Decisor**: Usuario (requisito inicial)  
**Estado**: ✅ Aceptada

### Contexto
El usuario especificó que no se deben usar `else` ni `elseif` en el código.

### Decisión
Usar early returns, guard clauses y `match` en lugar de `else/elseif`.

### Alternativas Consideradas
1. **Permitir else/elseif**: Más flexible pero usuario prefiere código más limpio
2. **Usar ternarios**: No siempre aplicable

### Consecuencias
- ✅ Código más legible y menos anidado
- ✅ Fuerza pensamiento en casos base primero
- ⚠️ Requiere disciplina del equipo
- ⚠️ Algunos casos pueden ser menos intuitivos

### Ejemplo
```php
// ❌ No permitido
if ($condition) {
    return $a;
} else {
    return $b;
}

// ✅ Permitido
if ($condition) {
    return $a;
}
return $b;

// ✅ También permitido
return match($condition) {
    true => $a,
    false => $b,
};
```

### Referencias
- Requisitos iniciales del usuario
- Clean Code principles

---

## DEC-007: JWT en lugar de Sanctum

**Fecha**: 2026-02-05  
**Decisor**: AI Lead Engineer  
**Estado**: ✅ Aceptada

### Contexto
El backend Python usa JWT, y necesitamos mantener compatibilidad. Laravel Sanctum es más común pero JWT es más estándar para APIs.

### Decisión
Usar `tymon/jwt-auth` para autenticación JWT en lugar de Laravel Sanctum.

### Alternativas Consideradas
1. **Laravel Sanctum**: Más integrado con Laravel pero diferente a Python
2. **Laravel Passport**: OAuth, demasiado complejo

### Consecuencias
- ✅ Compatible con backend Python (mismo formato JWT)
- ✅ Estándar de la industria
- ✅ Stateless (no requiere sesiones)
- ⚠️ Requiere configuración adicional
- ⚠️ No tan integrado como Sanctum

### Referencias
- `backend/composer.json` - `tymon/jwt-auth`
- `python-backend/auth.py` - Implementación Python

---

## DEC-008: Mantener Python Backend como Referencia

**Fecha**: 2026-02-05  
**Decisor**: AI Lead Engineer  
**Estado**: ✅ Aceptada

### Contexto
El backend Python es la fuente de verdad funcional. Necesitamos mantenerlo como referencia durante la migración.

### Decisión
Mantener `python-backend/` en el repositorio como referencia. No eliminar hasta que la migración esté completa y verificada.

### Alternativas Consideradas
1. **Eliminar Python Backend**: Perderíamos referencia funcional
2. **Mover a branch separado**: Más complejo, no necesario

### Consecuencias
- ✅ Fuente de verdad disponible durante migración
- ✅ Fácil comparar implementaciones
- ✅ Puede servir como documentación viva
- ⚠️ Ocupa espacio en repo (pero es pequeño)

### Referencias
- `python-backend/` - Código fuente Python
- `/docs/FASE1_ANALISIS.md` - Análisis completo

---

## DEC-009: Estructura de Rutas API Versionada

**Fecha**: 2026-02-05  
**Decisor**: AI Lead Engineer  
**Estado**: ✅ Aceptada

### Contexto
Necesitamos versionar la API para permitir cambios futuros sin romper el frontend.

### Decisión
Usar prefijo `/api/v1` para todas las rutas API. El frontend consume `/api/v1/*`.

### Alternativas Consideradas
1. **Sin versionado**: Más simple pero no escalable
2. **Versionado en headers**: Menos visible en URLs

### Consecuencias
- ✅ Permite versiones futuras (`/api/v2`)
- ✅ Compatibilidad con frontend actual
- ✅ Estándar de la industria
- ⚠️ URLs más largas

### Referencias
- `backend/routes/api.php` - Prefijo `v1`
- `frontend/src/api.js` - Cliente API

---

## DEC-010: Notificaciones en Modo Sandbox

**Fecha**: 2026-02-05  
**Decisor**: AI Lead Engineer (basado en PRD)  
**Estado**: ✅ Aceptada

### Contexto
El sistema necesita notificaciones (WhatsApp/SMS) pero aún no hay integración real configurada.

### Decisión
Implementar sistema de logs de notificaciones en modo sandbox. Las notificaciones se registran pero no se envían realmente.

### Alternativas Consideradas
1. **Integración real desde inicio**: Más complejo, puede fallar
2. **No implementar notificaciones**: Perder funcionalidad

### Consecuencias
- ✅ Sistema preparado para integración futura
- ✅ Logs disponibles para debugging
- ✅ No depende de servicios externos
- ⚠️ No envía notificaciones reales (por diseño)

### Referencias
- `python-backend/server.py` - Función `log_notification()`
- `docs/database/schema.md` - Tabla `notification_logs`

---

## Cómo Agregar Nuevas Decisiones

Cuando tomes una decisión arquitectónica importante:

1. Agregar entrada con formato:
   - **DEC-XXX**: Número secuencial
   - **Fecha**: Fecha de la decisión
   - **Decisor**: Quién tomó la decisión
   - **Estado**: ✅ Aceptada / ⏳ Pendiente / ❌ Rechazada
   - **Contexto**: Por qué se necesita la decisión
   - **Decisión**: Qué se decidió
   - **Alternativas**: Qué otras opciones se consideraron
   - **Consecuencias**: Impacto positivo y negativo
   - **Referencias**: Archivos/documentos relacionados

2. Actualizar este documento inmediatamente después de la decisión.

3. Si la decisión cambia, agregar nueva entrada DEC-YYY que revierte o modifica DEC-XXX, manteniendo historial.
