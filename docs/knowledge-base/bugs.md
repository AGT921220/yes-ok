# Bugs Conocidos y Fixes - yes-ok

Este documento registra bugs encontrados, su causa raíz, el fix aplicado, y cómo probar que está resuelto.

---

## BUG-001: TypeError en babel-metadata-plugin.js

**Fecha Detectado**: 2026-02-05  
**Fecha Resuelto**: 2026-02-05  
**Estado**: ✅ Resuelto

### Descripción
Error durante build del frontend:
```
TypeError: Cannot read properties of null (reading 'traverse')
```

### Causa Raíz
El plugin de Babel intentaba acceder a `importPath.parentPath.parentPath.traverse` sin validar que `parentPath` no fuera null. Esto ocurría cuando se recorría un AST cacheado donde la estructura de `NodePath` no tenía el `parentPath` esperado.

### Fix Aplicado
Refactorización de `lazyEvaluatePropSource` en `frontend/plugins/visual-edits/babel-metadata-plugin.js`:

**Antes**:
```javascript
importPath.parentPath.parentPath.traverse(...)
```

**Después**:
Estrategia de dos traverses separados:
1. Primer traverse: Identifica el `localName` del componente importado
2. Segundo traverse: Recorre todo el AST buscando usos de ese `localName` en `JSXOpeningElement`

Esto desacopla la búsqueda de usos del componente de la estructura del `NodePath` del import.

### Cómo Probar
```bash
cd backend/resources/frontend
npm run build
# Debe completar sin errores
```

### Referencias
- `frontend/plugins/visual-edits/babel-metadata-plugin.js`
- Build logs del frontend

---

## BUG-002: ESLint Warnings - Dependencias Faltantes en useEffect

**Fecha Detectado**: 2026-02-05  
**Fecha Resuelto**: 2026-02-05  
**Estado**: ✅ Resuelto

### Descripción
Múltiples advertencias de ESLint sobre dependencias faltantes en hooks `useEffect`:
```
React Hook useEffect has a missing dependency: 'fetchData'
```

### Causa Raíz
Funciones usadas dentro de `useEffect` no estaban en el array de dependencias, causando que ESLint advierta sobre posibles bugs de stale closures.

### Fix Aplicado
Envolver funciones con `useCallback` y agregarlas a los arrays de dependencias:

**Archivos corregidos**:
- `frontend/src/pages/ClinicalRecord.js`
- `frontend/src/pages/PatientPortal.js`
- `frontend/src/pages/Patients.js`
- `frontend/src/pages/PublicBooking.js`
- `frontend/src/pages/Reports.js`

**Ejemplo**:
```javascript
// Antes
useEffect(() => {
  fetchData();
}, []);

// Después
const fetchData = useCallback(() => {
  // ... lógica
}, [dependencies]);

useEffect(() => {
  fetchData();
}, [fetchData]);
```

### Cómo Probar
```bash
cd backend/resources/frontend
npm run build
# No debe haber warnings de ESLint
```

### Referencias
- Archivos mencionados arriba
- ESLint rules: `react-hooks/exhaustive-deps`

---

## BUG-003: Error de Sintaxis YAML en docker-compose.yml

**Fecha Detectado**: 2026-02-05  
**Fecha Resuelto**: 2026-02-05  
**Estado**: ✅ Resuelto

### Descripción
Error al ejecutar `docker compose up -d`:
```
yaml: line 104: mapping values are not allowed in this context
```

### Causa Raíz
Sintaxis incorrecta en `depends_on` con `condition`. La indentación y estructura YAML no era válida.

### Fix Aplicado
Corregir sintaxis de `depends_on` en `docker-compose.yml` y `docker-compose-staging.yml`:

**Antes** (incorrecto):
```yaml
depends_on:
  php:
    condition: service_started
  redis:
    condition: service_healthy
```

**Después** (correcto):
```yaml
depends_on:
  php:
    condition: service_started
  redis:
    condition: service_healthy
```

Nota: El formato era correcto, el problema era en otro lugar. Se corrigió la indentación completa del archivo.

### Cómo Probar
```bash
docker compose config
# Debe validar sin errores

docker compose up -d
# Debe levantar servicios correctamente
```

### Referencias
- `docker-compose.yml`
- `docker-compose-staging.yml`
- Docker Compose v2 syntax

---

## BUG-004: MySQL Faltante en docker-compose-staging.yml

**Fecha Detectado**: 2026-02-05  
**Fecha Resuelto**: 2026-02-05  
**Estado**: ✅ Resuelto

### Descripción
El archivo `docker-compose-staging.yml` no tenía servicio MySQL, causando que el backend Laravel no pudiera conectarse a la base de datos.

### Causa Raíz
Servicio MySQL no fue agregado inicialmente al archivo de staging.

### Fix Aplicado
Agregar servicio `mysql` a `docker-compose-staging.yml` con credenciales especificadas por el usuario:
- User: `user`
- Password: `password`
- Database: `db`
- Root password: `1234`

### Cómo Probar
```bash
docker compose -f docker-compose-staging.yml up -d
docker compose -f docker-compose-staging.yml exec mysql mysql -u user -ppassword -e "SHOW DATABASES;"
# Debe mostrar la base de datos 'db'
```

### Referencias
- `docker-compose-staging.yml`
- Requisitos del usuario para staging

---

## BUG-005: User Model Falta Cast de Role

**Fecha Detectado**: 2026-02-05  
**Estado**: ❌ Pendiente

### Descripción
El modelo `User` no tiene `role` en el array `$casts`, por lo que se retorna como string en lugar de Enum.

### Causa Raíz
Falta crear el Enum `UserRole` y agregarlo a los casts del modelo.

### Fix Propuesto
1. Crear `app/Enums/UserRole.php`:
```php
enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case ASISTENTE = 'ASISTENTE';
}
```

2. Agregar a `User.php`:
```php
protected $casts = [
    'role' => UserRole::class,
    // ...
];
```

### Cómo Probar
```bash
docker compose exec php php artisan tinker
>>> $user = User::first();
>>> $user->role; // Debe ser UserRole::ADMIN, no string
```

### Prioridad
P2 - Medio

### Referencias
- `backend/app/Models/User.php`
- `/docs/risks.md` - Bug 2

---

## BUG-006: Red Externa No Existe en Staging

**Fecha Detectado**: 2026-02-05  
**Estado**: ⚠️ Requiere Verificación

### Descripción
`docker-compose-staging.yml` marca la red `appnetwork` como `external: true`, pero puede no existir.

### Causa Raíz
La red debe crearse manualmente o Docker debe crearla automáticamente.

### Fix Propuesto
**Opción A**: Crear red manualmente:
```bash
docker network create appnetwork
```

**Opción B**: Remover `external: true` para que Docker la cree automáticamente.

### Cómo Probar
```bash
docker network ls | grep appnetwork
# Debe existir

docker compose -f docker-compose-staging.yml up -d
# No debe dar error de red
```

### Prioridad
P2 - Medio

### Referencias
- `docker-compose-staging.yml` línea 105
- `/docs/risks.md` - Bug 3

---

## BUG-007: Error SQL "Column not found: 1054 Unknown column 'paternal_surname' in 'order clause'"

**Fecha Detectado**: 2026-02-05  
**Fecha Resuelto**: 2026-02-05  
**Estado**: ✅ Resuelto

### Descripción
Error al listar pacientes:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'paternal_surname' in 'order clause'
```

### Causa Raíz
1. **Orden incorrecto de migraciones**: `appointments` se creaba antes que `patients` debido a timestamps idénticos (`2026_02_05_214755`), causando que la migración de `patients` fallara silenciosamente.
2. **Índices duplicados**: Las migraciones intentaban crear índices que ya existían (por `unique()`, `foreignId()`, o `index()` duplicado).

### Fix Aplicado
1. **Renombrado migración de patients**: `2026_02_05_214755_create_patients_table.php` → `2026_02_05_214754_create_patients_table.php` para ejecutarse antes que `appointments`.
2. **Eliminados índices duplicados**:
   - `patients`: Eliminado `->index('whatsapp')` (ya tiene `->unique()`)
   - `patients`: Eliminado `->index('access_token')` duplicado (ya tiene `->index()` en línea 24)
   - `appointments`: Eliminado `->index('date')` duplicado (ya tiene `->index()` en línea 17)
   - `appointments`: Eliminado `->index('patient_id')` (ya tiene índice por `foreignId()`)
3. **Recreadas tablas**: Ejecutado `migrate:fresh` para recrear todas las tablas en orden correcto.

### Cómo Probar
```bash
# Verificar que la columna existe
docker compose exec mysql mysql -u user -ppassword db -e "DESCRIBE patients;" | grep paternal_surname
# Debe mostrar: paternal_surname	varchar(255)	NO

# Probar endpoint
TOKEN=$(docker compose exec php php artisan tinker --execute="echo JWTAuth::fromUser(App\Models\User::first());" 2>&1 | grep -v "Restricted Mode" | tail -1)
curl -X GET "http://localhost/api/v1/clients" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
# Debe devolver {"data":[...]} sin errores SQL
```

### Referencias
- `backend/database/migrations/2026_02_05_214754_create_patients_table.php`
- `backend/database/migrations/2026_02_05_214755_create_appointments_table.php`
- `backend/app/Http/Controllers/Api/V1/PatientController.php` línea 33
- `/docs/knowledge-base.md` - Sección 4.1

---

## BUG-008: Tabla 'availability_blocks' no existe

**Fecha Detectado**: 2026-02-05  
**Fecha Resuelto**: 2026-02-05  
**Estado**: ✅ Resuelto

### Descripción
Error al listar bloqueos de disponibilidad:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'db.availability_blocks' doesn't exist
```

### Causa Raíz
La migración de `availability_blocks` falló porque dependía de migraciones anteriores (`patients`, `appointments`) que también fallaron debido a problemas de orden e índices duplicados.

### Fix Aplicado
Corregidas las migraciones anteriores (ver BUG-007), lo que permitió que `availability_blocks` se creara correctamente.

### Cómo Probar
```bash
# Verificar que la tabla existe
docker compose exec mysql mysql -u user -ppassword db -e "DESCRIBE availability_blocks;"
# Debe mostrar todas las columnas

# Probar endpoint
TOKEN=$(docker compose exec php php artisan tinker --execute="echo JWTAuth::fromUser(App\Models\User::first());" 2>&1 | grep -v "Restricted Mode" | tail -1)
curl -X GET "http://localhost/api/v1/availability-blocks" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
# Debe devolver {"data":[]} sin errores SQL
```

### Referencias
- `backend/database/migrations/2026_02_05_214756_create_availability_blocks_table.php`
- `/docs/knowledge-base.md` - Sección 4.2

---

## Cómo Reportar Nuevos Bugs

1. Agregar entrada con formato:
   - **BUG-XXX**: Número secuencial
   - **Fecha Detectado**: Cuándo se encontró
   - **Fecha Resuelto**: Cuándo se arregló (si aplica)
   - **Estado**: ✅ Resuelto / ❌ Pendiente / ⚠️ En Progreso
   - **Descripción**: Qué pasa
   - **Causa Raíz**: Por qué pasa
   - **Fix Aplicado/Propuesto**: Cómo se arregló o cómo arreglarlo
   - **Cómo Probar**: Pasos para verificar el fix
   - **Prioridad**: P0/P1/P2/P3
   - **Referencias**: Archivos/documentos relacionados

2. Actualizar este documento inmediatamente al detectar o resolver un bug.

3. Si el bug está relacionado con un riesgo, actualizar también `/docs/risks.md`.
