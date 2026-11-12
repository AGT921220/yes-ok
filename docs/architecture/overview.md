# yes-ok - Arquitectura del Sistema

## Visión General

yes-ok es un sistema de reservas de servicios construido con una arquitectura modular y simplificada.

## Stack Tecnológico

- **Frontend**: React 19 + Tailwind CSS + Shadcn/UI
- **Backend**: FastAPI (Python)
- **Base de Datos**: PostgreSQL
- **Autenticación**: JWT

## Estructura del Proyecto

```
/app
├── backend/
│   ├── server.py        # Controladores y endpoints
│   ├── models.py        # Modelos SQLAlchemy
│   ├── schemas.py       # DTOs (Pydantic)
│   ├── auth.py          # Autenticación JWT
│   ├── database.py      # Configuración de DB
│   └── .env             # Variables de entorno
├── frontend/
│   ├── src/
│   │   ├── api.js       # Cliente API
│   │   ├── context/     # AuthContext
│   │   ├── components/  # Componentes UI
│   │   └── pages/       # Páginas
│   └── .env
└── docs/                # Documentación
```

## Responsabilidades por Capa

### Controladores (server.py)
- Validar requests HTTP
- Llamar a la lógica de negocio
- Retornar respuestas formateadas
- NO contienen lógica de negocio compleja

### Modelos (models.py)
- Definición de tablas
- Relaciones entre entidades
- Enums para estados

### Schemas/DTOs (schemas.py)
- Validación de entrada
- Serialización de salida
- Transformación de datos

### Auth (auth.py)
- Generación de tokens JWT
- Validación de tokens
- Middleware de autenticación
- Control de permisos por rol

## Principios SOLID Aplicados

### Single Responsibility
- Cada archivo tiene una responsabilidad clara
- Modelos solo definen estructura
- Schemas solo validan/transforman datos

### Open/Closed
- Los endpoints pueden extenderse sin modificar el core
- Los estados de citas son extensibles vía Enum

### Liskov Substitution
- Los DTOs de respuesta son consistentes
- PatientResponse, AppointmentResponse mantienen contrato

### Interface Segregation
- Endpoints específicos por recurso
- No hay interfaces monolíticas

### Dependency Inversion
- La DB se inyecta vía `get_db()`
- El usuario actual se inyecta vía `get_current_user()`

## Flujo de Datos

```
Request HTTP
    ↓
Controlador (valida request)
    ↓
Auth Middleware (verifica JWT)
    ↓
Lógica de Negocio (inline o funciones helper)
    ↓
Repository (SQLAlchemy queries)
    ↓
Response (Schema/DTO)
```

## Módulos Funcionales

1. **Auth**: Login, validación de roles
2. **Users**: CRUD de usuarios internos
3. **Patients**: CRUD de pacientes
4. **Appointments**: Gestión de citas con validaciones
5. **AvailabilityBlocks**: Bloqueos de disponibilidad
6. **ClinicalRecords**: Historia clínica + Notas SOAP
7. **Notifications**: Logs de recordatorios (sandbox)
8. **Reports**: Estadísticas y exportaciones
9. **Settings**: Configuración del sistema

## Seguridad

- Passwords hasheados con bcrypt
- Tokens JWT con expiración configurable
- Roles: ADMIN y ASISTENTE
- CORS configurado
- Validación de datos con Pydantic
