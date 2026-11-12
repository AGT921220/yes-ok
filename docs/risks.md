# yes-ok - Errores Conocidos y Riesgos

## Riesgos Críticos

### 1. Frontend Completamente Roto
**Descripción**: Todos los endpoints que consume el frontend no existen en Laravel.

**Impacto**: El frontend no puede funcionar. Todas las páginas fallan al cargar datos.

**Estado**: ❌ No resuelto

**Solución**: Implementar endpoints P0 (Auth, Patients, Appointments, AvailabilityBlocks) según `/docs/FASE1_ANALISIS.md`.

**Prioridad**: P0 - Crítico

---

### 2. Backend Python No Está Corriendo
**Descripción**: El backend Python existe pero no está configurado en Docker ni corriendo.

**Impacto**: No hay backend funcional disponible. El sistema está completamente inoperativo.

**Estado**: ❌ No resuelto

**Solución**: 
- Opción A: Implementar endpoints en Laravel (recomendado)
- Opción B: Agregar servicio Python a docker-compose.yml (temporal)

**Prioridad**: P0 - Crítico

---

### 3. Migraciones Faltantes
**Descripción**: Solo existe migración de `users`. Faltan 10 tablas más.

**Impacto**: No se puede crear la estructura de base de datos completa.

**Estado**: ❌ No resuelto

**Solución**: Crear migraciones para:
- patients
- appointments
- availability_blocks
- clinical_histories
- soap_notes
- soap_note_addendums
- notification_logs
- settings
- audit_logs

**Prioridad**: P0 - Crítico

---

### 4. Reglas de Negocio Complejas Sin Implementar
**Descripción**: Validaciones críticas (empalmes, bloqueos, estados) no están implementadas en Laravel.

**Impacto**: Riesgo de datos inconsistentes, citas duplicadas, violación de reglas de negocio.

**Estado**: ❌ No resuelto

**Solución**: Implementar Services con lógica de negocio:
- `AppointmentService@checkOverlap`
- `AppointmentService@checkAvailabilityBlock`
- `ClinicalHistoryService@close` / `@unlock`
- `SoapNoteService@sign` / `@addAddendum`

**Prioridad**: P0 - Crítico

---

## Riesgos Altos

### 5. JWT No Configurado
**Descripción**: `tymon/jwt-auth` está en `composer.json` pero no está configurado ni publicado.

**Impacto**: No se puede autenticar usuarios. Sistema de auth no funciona.

**Estado**: ❌ No resuelto

**Solución**:
```bash
docker compose exec php php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
docker compose exec php php artisan jwt:secret
```

**Prioridad**: P1 - Alto

---

### 6. Horizon No Configurado
**Descripción**: `laravel/horizon` está instalado pero falta `config/horizon.php` y publicación de assets.

**Impacto**: Colas no funcionan. Dashboard de Horizon no accesible.

**Estado**: ❌ No resuelto

**Solución**:
```bash
docker compose exec php php artisan vendor:publish --tag=horizon-config
docker compose exec php php artisan horizon:install
```

**Prioridad**: P1 - Alto

---

### 7. Validaciones Complejas
**Descripción**: Validaciones específicas (WhatsApp E.164, overlap de horarios, estados) no implementadas.

**Impacto**: Datos inválidos pueden ingresar al sistema.

**Estado**: ❌ No resuelto

**Solución**: Implementar FormRequest validators:
- `StorePatientRequest` - Validar WhatsApp E.164
- `StoreAppointmentRequest` - Validar empalmes y bloqueos
- `UpdateAppointmentRequest` - Validar cambios de fecha/hora

**Prioridad**: P1 - Alto

---

### 8. Magic Links (Portal Paciente)
**Descripción**: Lógica de tokens para portal de paciente no trivial. Requiere generación segura y expiración.

**Impacto**: Portal público no funciona. Pacientes no pueden acceder.

**Estado**: ❌ No resuelto

**Solución**: Implementar en `PatientPortalService`:
- Generación de token seguro (`Str::random(64)`)
- Validación de expiración
- Actualización de `access_token` y `access_token_expires` en `patients`

**Prioridad**: P1 - Alto

---

## Riesgos Medios

### 9. Exportaciones No Implementadas
**Descripción**: Dependencias (`maatwebsite/excel`, `dompdf/dompdf`) instaladas pero no implementadas.

**Impacto**: Reportes no se pueden exportar.

**Estado**: ❌ No resuelto

**Solución**: Implementar en `ReportController`:
- `exportExcel()` - Usar `Maatwebsite\Excel`
- `exportPdf()` - Usar `DomPDF`

**Prioridad**: P2 - Medio

---

### 10. Timezone No Verificado
**Descripción**: Configuración de `America/Monterrey` no verificada en Laravel.

**Impacto**: Fechas/horas pueden estar en timezone incorrecto.

**Estado**: ⚠️ Parcialmente resuelto

**Solución**: Verificar en `config/app.php`:
```php
'timezone' => env('APP_TIMEZONE', 'America/Monterrey'),
```

**Prioridad**: P2 - Medio

---

### 11. Permisos y Roles
**Descripción**: Middleware de roles (ADMIN vs ASISTENTE) no implementado.

**Impacto**: Usuarios pueden acceder a endpoints que no deberían.

**Estado**: ❌ No resuelto

**Solución**: Crear middleware `RequireAdmin`:
```php
// app/Http/Middleware/RequireAdmin.php
if (auth()->user()->role !== 'ADMIN') {
    abort(403, 'Se requieren permisos de administrador');
}
```

**Prioridad**: P2 - Medio

---

### 12. Audit Logs No Implementados
**Descripción**: Tabla y lógica de auditoría no implementada.

**Impacto**: No hay trazabilidad de acciones importantes.

**Estado**: ❌ No resuelto

**Solución**: 
- Crear migración `create_audit_logs_table.php`
- Crear modelo `AuditLog`
- Crear servicio `AuditLogService@log`
- Integrar en acciones críticas

**Prioridad**: P2 - Medio

---

## Bugs Conocidos

### Bug 1: TODO en routes/api.php
**Ubicación**: `backend/routes/api.php` línea 26

**Descripción**: Comentario `// TODO: Agregar rutas API aquí según el mapeo de endpoints`

**Estado**: ⚠️ Esperado (no es bug, es pendiente)

**Solución**: Implementar rutas según `/docs/endpoint-mapping.md`

---

### Bug 2: User Model Falta Cast de Role
**Ubicación**: `backend/app/Models/User.php`

**Descripción**: `role` no está en `$casts` como Enum.

**Estado**: ❌ No resuelto

**Solución**: Agregar a `$casts`:
```php
protected $casts = [
    'role' => UserRole::class,  // Crear enum UserRole primero
    // ...
];
```

**Prioridad**: P2 - Medio

---

### Bug 3: Red Externa en Staging
**Ubicación**: `docker-compose-staging.yml` línea 105

**Descripción**: Red `appnetwork` está marcada como `external: true` pero puede no existir.

**Estado**: ⚠️ Requiere verificación

**Solución**: 
- Opción A: Crear red manualmente: `docker network create appnetwork`
- Opción B: Remover `external: true` para que Docker la cree

**Prioridad**: P2 - Medio

---

### Bug 4: No Hay Tests
**Descripción**: No hay tests unitarios ni de integración.

**Impacto**: No hay garantía de que la lógica funcione correctamente.

**Estado**: ❌ No resuelto

**Solución**: Crear tests para:
- Services (lógica de negocio)
- Controllers (endpoints)
- Validaciones críticas

**Prioridad**: P3 - Bajo (pero importante a largo plazo)

---

## Deuda Técnica

### 1. Código Duplicado Potencial
**Riesgo**: Al replicar lógica de Python a Laravel, puede haber duplicación.

**Solución**: Usar Services reutilizables, evitar código duplicado.

---

### 2. Falta de Documentación de Código
**Riesgo**: Código sin comentarios o documentación inline.

**Solución**: Agregar PHPDoc a métodos complejos, especialmente en Services.

---

### 3. Sin Estrategia de Migración de Datos
**Riesgo**: Si hay datos en PostgreSQL (Python), no hay plan para migrarlos a MySQL (Laravel).

**Solución**: Crear script de migración de datos si es necesario.

---

## Errores de Configuración

### 1. Permisos de Storage
**Error**: `Permission denied` en `storage/` o `bootstrap/cache/`

**Solución**: Ya resuelto en `docker/entrypoint.sh` con `chmod` y `chown`.

---

### 2. APP_KEY No Generado
**Error**: `RuntimeException: No application encryption key has been specified`

**Solución**: `docker compose exec php php artisan key:generate`

---

### 3. JWT Secret No Generado
**Error**: JWT no funciona sin secret.

**Solución**: `docker compose exec php php artisan jwt:secret`

---

## Checklist de Resolución

### P0 - Crítico (Hacer primero)
- [ ] Implementar endpoints P0 (Auth, Patients, Appointments, Blocks)
- [ ] Crear migraciones faltantes (10 tablas)
- [ ] Implementar reglas de negocio críticas

### P1 - Alto (Hacer después)
- [ ] Configurar JWT
- [ ] Configurar Horizon
- [ ] Implementar validaciones complejas
- [ ] Implementar magic links

### P2 - Medio (Hacer cuando sea posible)
- [ ] Implementar exportaciones
- [ ] Verificar timezone
- [ ] Implementar middleware de roles
- [ ] Implementar audit logs

### P3 - Bajo (Mejoras)
- [ ] Crear tests
- [ ] Documentar código
- [ ] Optimizar queries

---

## Monitoreo Continuo

Estos riesgos deben monitorearse y actualizarse en `/docs/knowledge-base/bugs.md` y `/docs/knowledge-base/TODO.md` según se resuelvan.
