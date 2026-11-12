# yes-ok - Arquitectura del Sistema

## Visión General

yes-ok utiliza una **arquitectura hexagonal simplificada** con separación clara de responsabilidades. El sistema está en proceso de migración a Laravel, manteniendo la misma estructura funcional y reglas de negocio.

## Stack Tecnológico

### Backend (Laravel - Objetivo)
- **Framework**: Laravel 12
- **PHP**: 8.3-FPM
- **Base de Datos**: MySQL 8.0
- **ORM**: Eloquent
- **Autenticación**: JWT (tymon/jwt-auth)
- **Colas**: Laravel Horizon + Redis
- **Cache**: Redis
- **Exportación**: Maatwebsite/Excel, DomPDF

### Frontend
- **Framework**: React 19
- **Estilos**: Tailwind CSS
- **Componentes**: Shadcn/UI
- **Build Tool**: Create React App (CRA) con Craco
- **API Client**: Axios

### Infraestructura
- **Contenedores**: Docker Compose
- **Web Server**: Nginx (reverse proxy)
- **PHP-FPM**: PHP 8.3
- **Base de Datos**: MySQL 8.0
- **Cache/Colas**: Redis 7
- **Scheduler**: Laravel Scheduler (cron)

## Arquitectura de Capas

### Estructura Laravel (Objetivo)

```
backend/app/
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/          # Controladores API (thin controllers)
│   ├── Middleware/          # JWT, roles, etc.
│   ├── Requests/            # FormRequest validators
│   └── Resources/           # API Resources (transformación)
├── Models/                  # Eloquent models (mínimos)
├── Enums/                   # Enumeraciones (UserRole, AppointmentStatus, etc.)
├── Services/                # Lógica de negocio (UseCases)
├── Repositories/            # Acceso a datos (interfaces + implementaciones)
└── DTOs/                    # Data Transfer Objects (opcional, usar Resources)
```

### Principios Arquitectónicos

1. **Controllers Delgados (Thin Controllers)**
   - Solo validan request y delegan a Services/UseCases
   - No contienen lógica de negocio
   - Retornan Resources o JSON

2. **Services/UseCases para Lógica de Negocio**
   - Toda la lógica compleja va aquí
   - Validaciones de negocio (empalmes, bloqueos, estados)
   - Orquestación de operaciones

3. **Repositories para Acceso a Datos**
   - Abstracción de Eloquent
   - Queries complejas
   - Facilita testing y mantenimiento

4. **Resources para Transformación**
   - Serialización de respuestas API
   - Consistencia en formato JSON
   - Transformación de datos

5. **Models Mínimos**
   - Solo relaciones y casts
   - Sin lógica de negocio
   - Acceso a datos vía Repositories

## Flujo de Datos

### Request → Response

```
1. Request HTTP
   ↓
2. Nginx (reverse proxy)
   ↓
3. Laravel Routes (routes/api.php)
   ↓
4. Middleware (JWT, CORS, etc.)
   ↓
5. Controller (validación con FormRequest)
   ↓
6. Service/UseCase (lógica de negocio)
   ↓
7. Repository (acceso a datos)
   ↓
8. Model (Eloquent)
   ↓
9. Database (MySQL)
   ↓
10. Response (Resource o JSON)
```

### Ejemplo: Crear Cita

```
POST /api/v1/appointments
  ↓
AuthMiddleware → Valida JWT token
  ↓
AppointmentController@store
  ↓
CreateAppointmentRequest → Valida datos de entrada
  ↓
AppointmentService@create
  ├─ AppointmentRepository@checkOverlap → Valida empalme
  ├─ AvailabilityBlockRepository@checkBlock → Valida bloqueo
  ├─ AppointmentRepository@create → Crea cita
  └─ NotificationService@log → Registra notificación
  ↓
AppointmentResource → Transforma respuesta
  ↓
JSON Response
```

## Servicios Docker

### Arquitectura de Contenedores

```
┌─────────────────────────────────────────┐
│           Nginx (Puerto 80)             │
│  - Reverse Proxy                        │
│  - Sirve frontend (SPA)                 │
│  - Enruta /api/* → PHP-FPM              │
└──────────────┬──────────────────────────┘
               │
       ┌───────┴────────┐
       │                │
┌──────▼──────┐  ┌──────▼──────┐
│  PHP-FPM    │  │   Horizon   │
│  (Laravel)  │  │  (Colas)    │
└──────┬──────┘  └──────┬──────┘
       │                │
       └───────┬────────┘
               │
    ┌──────────┴──────────┐
    │                     │
┌───▼────┐          ┌─────▼────┐
│ MySQL  │          │  Redis   │
│  (DB)  │          │ (Cache)  │
└────────┘          └──────────┘
```

### Servicios Detallados

1. **nginx** (`nginx-yes-ok`)
   - Puerto: 80 (local), 443 (HTTPS en prod)
   - Volúmenes: `./backend:/var/www/html`, configuración Nginx
   - Depende de: `php`, `redis`

2. **mysql** (`mysql-yes-ok`)
   - Puerto: 3306
   - Database: `yes-ok` (local), `db` (staging)
   - Volumen persistente: `mysql_data`
   - Healthcheck: `mysqladmin ping`

3. **redis** (`redis-yes-ok`)
   - Puerto: 6379
   - Volumen persistente: `redis_data`
   - Healthcheck: `redis-cli ping`

4. **php** (`php-yes-ok`)
   - Puerto interno: 9000
   - Volúmenes: código Laravel, configuración PHP
   - Depende de: `mysql` (healthy), `redis` (healthy)

5. **horizon** (`horizon-yes-ok`)
   - Procesa colas de Laravel
   - Comando: `php artisan horizon`
   - Depende de: `php`, `redis`

6. **scheduler** (`scheduler-yes-ok`)
   - Tareas programadas
   - Comando: `php artisan schedule:work`
   - Depende de: `php`, `mysql`

## Redes y Comunicación

- **Red**: `appnetwork` (Docker network)
- **Comunicación interna**: Por nombre de servicio (ej: `mysql-yes-ok`, `redis-yes-ok`)
- **Puertos expuestos**: Solo Nginx (80) y MySQL (3306) para desarrollo

## Seguridad

### Autenticación
- **JWT Tokens**: Expiración configurable (default 24h)
- **Middleware**: Valida token en cada request protegido
- **Roles**: ADMIN y ASISTENTE (control de acceso)

### Validaciones
- **FormRequest**: Validación de entrada en Laravel
- **Reglas de Negocio**: En Services/UseCases
- **Base de Datos**: Constraints y foreign keys

### CORS
- Configurado para permitir requests del frontend
- Headers de autorización permitidos

## Principios SOLID Aplicados

### Single Responsibility
- Cada clase tiene una responsabilidad única
- Controllers solo enrutan, Services solo lógica, Repositories solo datos

### Open/Closed
- Extensible sin modificar código existente
- Nuevos endpoints no afectan los existentes

### Liskov Substitution
- Interfaces de Repositories permiten intercambiar implementaciones
- Resources mantienen contrato consistente

### Interface Segregation
- Repositories específicos por entidad
- No hay interfaces monolíticas

### Dependency Inversion
- Controllers dependen de Services (interfaces)
- Services dependen de Repositories (interfaces)
- Inyección de dependencias vía constructor

## Módulos Funcionales

1. **Auth**: Autenticación JWT, roles, permisos
2. **Users**: CRUD de usuarios internos (solo ADMIN)
3. **Patients**: CRUD de pacientes, búsqueda
4. **Appointments**: Gestión de citas con validaciones complejas
5. **AvailabilityBlocks**: Bloqueos de disponibilidad
6. **ClinicalHistory**: Historia clínica con cierre/desbloqueo
7. **SOAPNotes**: Notas médicas con firma y addendums
8. **Reports**: Estadísticas y exportaciones
9. **Settings**: Configuración del sistema
10. **Public**: Endpoints públicos (booking, portal paciente)

## Decisiones Arquitectónicas Importantes

1. **MySQL en lugar de PostgreSQL**: Decisión del usuario para staging/producción
2. **Laravel Horizon**: Obligatorio para gestión de colas y dashboard
3. **Sin Postfix**: No se incluye servicio de email en Docker
4. **Frontend Integrado**: React compilado en `backend/public/app/`
5. **Arquitectura Hexagonal Simplificada**: No full DDD, pero con separación clara

Para más detalles sobre decisiones específicas, ver `/docs/knowledge-base/decisions.md`.
