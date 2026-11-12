# Evolución de Arquitectura - yes-ok

Este documento registra cómo ha evolucionado la arquitectura del proyecto, decisiones de diseño, y cambios importantes.

> Dominio funcional actual: yes-ok (clientes y citas de servicios).  
> Nombres internos heredados como `Patient` se conservan por compatibilidad tecnica.

---

## Estado Inicial (Python Backend)

**Fecha**: Antes de 2026-02-05  
**Stack**: FastAPI + PostgreSQL + SQLAlchemy

### Estructura
```
python-backend/
├── server.py      # 1434 líneas - Todos los endpoints
├── models.py      # Modelos SQLAlchemy
├── schemas.py     # DTOs Pydantic
├── auth.py        # JWT y middleware
└── database.py    # Configuración PostgreSQL
```

### Características
- ✅ Monolítico pero funcional
- ✅ Toda la lógica en `server.py`
- ✅ Validaciones con Pydantic
- ✅ JWT con python-jose
- ✅ PostgreSQL como base de datos

### Decisiones
- Endpoints todos en un solo archivo
- Helpers inline en `server.py`
- Sin separación de capas (controllers/services/repositories)

---

## Migración a Laravel (En Progreso)

**Fecha**: 2026-02-05  
**Stack**: Laravel 12 + MySQL 8.0 + Eloquent

### Estructura Objetivo
```
backend/app/
├── Http/
│   ├── Controllers/Api/V1/    # Controllers delgados
│   ├── Middleware/             # JWT, roles
│   ├── Requests/               # Validadores
│   └── Resources/              # Transformación API
├── Models/                     # Modelos mínimos
├── Enums/                      # Enumeraciones
├── Services/                   # Lógica de negocio
└── Repositories/               # Acceso a datos
```

### Cambios Arquitectónicos

#### 1. Separación de Responsabilidades
**Antes (Python)**: Todo en `server.py`  
**Después (Laravel)**: Separado en Controllers, Services, Repositories

**Razón**: Mejor mantenibilidad, testabilidad, y escalabilidad.

#### 2. Arquitectura Hexagonal Simplificada
**Antes**: Monolítico  
**Después**: Capas claras (Controllers → Services → Repositories → Models)

**Razón**: Clean Code, SOLID principles, facilita testing.

#### 3. Validaciones con FormRequest
**Antes**: Validación inline con Pydantic  
**Después**: FormRequest validators en Laravel

**Razón**: Separación de validación de lógica de negocio.

#### 4. Resources para Respuestas
**Antes**: Schemas Pydantic para serialización  
**Después**: API Resources de Laravel

**Razón**: Consistencia en formato JSON, transformación centralizada.

---

## Decisiones de Diseño Actuales

### Controllers Delgados
**Principio**: Controllers solo validan request y delegan a Services.

**Ejemplo**:
```php
// ❌ No hacer
public function store(Request $request) {
    // Lógica de negocio aquí
    if (checkOverlap(...)) {
        // ...
    }
}

// ✅ Hacer
public function store(StoreAppointmentRequest $request, AppointmentService $service) {
    $appointment = $service->create($request->validated(), auth()->user());
    return new AppointmentResource($appointment);
}
```

### Services para Lógica de Negocio
**Principio**: Toda la lógica compleja va en Services.

**Ejemplo**:
```php
class AppointmentService {
    public function create(array $data, User $user): Appointment {
        // Validar paciente existe
        // Validar no empalme
        // Validar no bloqueo
        // Crear cita
        // Log notificación
        return $appointment;
    }
}
```

### Repositories para Datos
**Principio**: Abstracción de acceso a datos.

**Ejemplo**:
```php
interface AppointmentRepositoryInterface {
    public function checkOverlap(...): bool;
    public function create(...): Appointment;
}

class EloquentAppointmentRepository implements AppointmentRepositoryInterface {
    // Implementación con Eloquent
}
```

### Sin else/elseif
**Principio**: Usar early returns, guard clauses, match.

**Ejemplo**:
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
```

---

## Cambios Futuros Considerados

### 1. Estructura por Features
**Idea**: Organizar por features en lugar de por tipo de archivo.

```
app/Features/
├── Auth/
│   ├── Controllers/
│   ├── Services/
│   └── ...
├── Clients/
└── ...
```

**Estado**: Considerado para futuro, no implementado aún.

**Razón de no implementar ahora**: Estructura plana es más simple para el tamaño actual del proyecto.

### 2. Eventos y Listeners
**Idea**: Usar eventos de Laravel para acciones como "cita creada", "nota firmada", etc.

**Estado**: Considerado, no implementado.

**Razón**: Por ahora, llamadas directas a Services son más simples y explícitas.

### 3. DTOs Explícitos
**Idea**: Crear clases DTO en lugar de usar arrays.

**Estado**: No implementado, usando arrays y Resources.

**Razón**: Resources de Laravel ya proporcionan transformación, DTOs serían redundantes.

---

## Comparación: Python vs Laravel

| Aspecto | Python (FastAPI) | Laravel |
|---------|------------------|---------|
| **Estructura** | Monolítica | Por capas |
| **Validación** | Pydantic schemas | FormRequest |
| **ORM** | SQLAlchemy | Eloquent |
| **Autenticación** | python-jose | tymon/jwt-auth |
| **Colas** | No implementado | Horizon + Redis |
| **Testing** | pytest | PHPUnit |
| **Base de Datos** | PostgreSQL | MySQL 8.0 |

---

## Principios Arquitectónicos Aplicados

### SOLID

1. **Single Responsibility**: Cada clase tiene una responsabilidad única
2. **Open/Closed**: Extensible sin modificar código existente
3. **Liskov Substitution**: Interfaces permiten intercambiar implementaciones
4. **Interface Segregation**: Interfaces específicas, no monolíticas
5. **Dependency Inversion**: Dependencias inyectadas, no hardcodeadas

### Clean Code

- Nombres descriptivos
- Funciones pequeñas y enfocadas
- Sin código duplicado
- Comentarios solo cuando necesario
- Sin else/elseif (early returns)

### Hexagonal Architecture (Simplificada)

- **Adapters de Entrada**: Controllers, Requests
- **Caso de Uso**: Services
- **Adapters de Salida**: Repositories, Models

---

## Métricas de Arquitectura

### Complejidad
- **Python Backend**: 1434 líneas en un archivo (alta complejidad)
- **Laravel Backend**: Objetivo: <200 líneas por archivo

### Acoplamiento
- **Python Backend**: Alto (todo acoplado en server.py)
- **Laravel Backend**: Bajo (separación de capas)

### Testabilidad
- **Python Backend**: Difícil (lógica inline)
- **Laravel Backend**: Fácil (Services y Repositories testables)

---

## Cómo Actualizar Este Documento

1. **Agregar cambios importantes**: Cuando se tome una decisión arquitectónica significativa
2. **Documentar evolución**: Antes/después de cambios grandes
3. **Registrar razones**: Por qué se hizo el cambio
4. **Referenciar decisiones**: Enlazar a `/docs/knowledge-base/decisions.md`

---

## Referencias

- `/docs/architecture.md` - Arquitectura actual detallada
- `/docs/knowledge-base/decisions.md` - Decisiones específicas
- `/docs/structure.md` - Estructura del proyecto
