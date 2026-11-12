# Autenticación y Gestión de Usuarios Clientes (PACIENTE)

**Última actualización**: 2026-02-12  
**Mantenido por**: Senior Fullstack Engineer

---

## 1. RESUMEN EJECUTIVO

Este documento describe el sistema de autenticación y gestión de usuarios con rol **PACIENTE** (también referidos como "clientes") en yes-ok. Los usuarios PACIENTE pueden acceder al sistema mediante login con email o teléfono, y tienen acceso limitado según su rol. Tras el login, el frontend redirige al **portal del paciente** (vistas propias: inicio, mis citas, mi cuenta); no ven la agenda ni administración. Ver [Patient Portal](patient-portal.md) para rutas API, middleware y vistas.

---

## 2. ARQUITECTURA Y ROLES

### 2.1 Roles del Sistema

El sistema utiliza un enum `UserRole` con tres valores:

- **ADMIN**: Administrador del sistema (acceso completo)
- **ASISTENTE**: Personal administrativo (acceso limitado)
- **PACIENTE**: Cliente/Paciente (acceso muy limitado)

### 2.2 Diferencias entre `Patient` y `User` con rol PACIENTE

Es importante distinguir:

- **`Patient`** (tabla `patients`): Entidad de negocio que representa un paciente en el sistema médico. Se crea cuando alguien agenda una cita.
- **`User` con rol PACIENTE** (tabla `users`): Usuario autenticado del sistema que puede hacer login y acceder a funcionalidades limitadas.

Un `Patient` puede existir sin un `User` correspondiente, pero un `User` con rol PACIENTE puede estar asociado a un `Patient` (aunque esta relación no está implementada actualmente).

---

## 3. CREACIÓN DE USUARIOS PACIENTE

⚠️ **IMPORTANTE**: Los pacientes **NO se crean desde el módulo de Usuarios**. Se crean únicamente desde el módulo de **Pacientes**.

### 3.1 Endpoint

**POST** `/api/v1/clients`

**Autenticación**: Requerida (ADMIN o ASISTENTE)

**Request Body**:
```json
{
  "first_name": "Juan",
  "paternal_surname": "Pérez",
  "maternal_surname": "García",
  "whatsapp": "+528111234567",
  "email": "juan@example.com",
  "password": "opcional_min8",
  "consent_notifications": true
}
```
- `email`: Opcional, para login.
- `password`: Opcional (mín. 8 caracteres). Si no se envía, el sistema genera una contraseña segura y la devuelve una sola vez en la respuesta.

**Nota**: El sistema crea un `User` con `role=PACIENTE`. Con esa contraseña el paciente tiene acceso al sistema; a **ese paciente** se le configuran las **notas SOAP** (por expediente del paciente).

**Validaciones**:
- `first_name`: Requerido, mínimo 1 carácter
- `paternal_surname`: Requerido, mínimo 1 carácter
- `maternal_surname`: Opcional
- `whatsapp`: Requerido, único, formato E.164
- `email`: Opcional, válido, único si se proporciona
- `password`: Opcional, mínimo 8 caracteres (si se envía)
- `consent_notifications`: Opcional, booleano (default: `true`)

**Response** (201 Created):

Si la contraseña fue **generada** por el sistema, la respuesta incluye `password` (solo en esta respuesta):
```json
{
  "data": { "id": 1, "first_name": "Juan", ... },
  "password": "aB3xK9mP",
  "message": "Paciente creado exitosamente. Guarda la contraseña para dársela al paciente."
}
```

Si el cliente **definió** contraseña en el request, la respuesta no incluye `password`:
```json
{
  "data": { "id": 1, "first_name": "Juan", ... },
  "message": "Paciente creado exitosamente. El paciente podrá iniciar sesión con la contraseña que configuraste."
}
```

⚠️ **IMPORTANTE**: Cuando la contraseña es generada, solo se muestra **una vez**. Cópiala inmediatamente.

### 3.2 Código de Ejemplo (Laravel)

```php
use App\Actions\CreatePatientWithUserAction;

$action = new CreatePatientWithUserAction();

$result = $action->execute([
    'first_name' => 'Juan',
    'paternal_surname' => 'Pérez',
    'maternal_surname' => 'García',
    'whatsapp' => '+528111234567',
    'consent_notifications' => true,
], 'juan@example.com'); // Email opcional

// $result contiene:
// - 'patient': Patient model
// - 'user': User model
// - 'password': string (solo se muestra una vez)
```

### 3.3 Factory (Testing)

```php
use Database\Factories\UserFactory;

// Crear usuario PACIENTE
$patientUser = User::factory()->patient()->create([
    'phone' => '+528123456789',
    'password' => Hash::make('password123'),
]);
```

---

## 4. AUTENTICACIÓN (LOGIN)

### 4.1 Endpoint

**POST** `/api/v1/auth/login`

**Autenticación**: No requerida (público)

### 4.2 Login por Email

**Request Body**:
```json
{
  "email": "juan@example.com",
  "password": "password123"
}
```

### 4.3 Login por Teléfono

**Request Body**:
```json
{
  "phone": "8123456789",
  "password": "password123"
}
```

**Nota**: El teléfono se normaliza automáticamente al formato mexicano (`+52` + 10 dígitos).

### 4.4 Response (200 OK)

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "user": {
    "id": 1,
    "name": "Juan Pérez García",
    "email": "juan@example.com",
    "phone": "+528123456789",
    "role": "PACIENTE",
    "is_active": true,
    "created_at": "2026-02-11T10:00:00.000000Z"
  }
}
```

### 4.5 Errores Comunes

**400 Bad Request** - Credenciales inválidas:
```json
{
  "ok": false,
  "message": "Credenciales inválidas",
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Credenciales inválidas",
    "details": {
      "email": ["Credenciales inválidas"]
    }
  }
}
```

**400 Bad Request** - Usuario inactivo:
```json
{
  "ok": false,
  "message": "Usuario inactivo",
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Usuario inactivo"
  }
}
```

---

## 5. RUTAS Y PERMISOS

### 5.1 Rutas Disponibles para PACIENTE

Los usuarios con rol `PACIENTE` tienen acceso limitado. Actualmente, todas las rutas protegidas con `auth:api` están disponibles, pero el frontend redirige a usuarios PACIENTE a rutas específicas.

**Rutas del Frontend**:
- `/mi-perfil`: Dashboard personal para pacientes (redirección automática desde rutas de ADMIN/ASISTENTE)

**Rutas del Backend** (API):
- `GET /api/v1/auth/me`: Obtener perfil del usuario autenticado
- Todas las demás rutas protegidas están disponibles, pero el frontend restringe el acceso según el rol

### 5.2 Restricciones por Rol

| Ruta | ADMIN | ASISTENTE | PACIENTE |
|------|-------|-----------|----------|
| `/api/v1/users` | ✅ | ❌ | ❌ |
| `/api/v1/clients` | ✅ | ✅ | ⚠️ (limitado) |
| `/api/v1/appointments` | ✅ | ✅ | ⚠️ (limitado) |
| `/api/v1/settings` | ✅ | ❌ | ❌ |
| `/api/v1/reports` | ✅ | ✅ | ❌ |

**Leyenda**:
- ✅ Acceso completo
- ❌ Sin acceso
- ⚠️ Acceso limitado (implementación pendiente de políticas específicas)

---

## 6. FLUJO DE ACCESO COMPLETO

### 6.1 Crear Usuario PACIENTE (Admin)

1. Admin hace login
2. Admin accede a `/usuarios`
3. Admin crea nuevo usuario con rol `PACIENTE`
4. Se proporciona credenciales al paciente (email/teléfono + password)

### 6.2 Login de Usuario PACIENTE

1. Usuario accede a `/login`
2. Selecciona "Teléfono" o "Correo"
3. Ingresa credenciales
4. Sistema valida y emite token JWT
5. Frontend redirige a `/mi-perfil` (o `/` si es otro rol)

### 6.3 Acceso a Rutas Protegidas

1. Usuario intenta acceder a ruta protegida
2. Frontend verifica rol en `AuthContext`
3. Si es `PACIENTE`, redirige a `/mi-perfil`
4. Si es `ADMIN`/`ASISTENTE`, permite acceso según permisos

---

## 7. BASE DE DATOS

### 7.1 Migración

La migración `2026_02_11_202205_add_paciente_role_to_users_table.php` actualiza el enum de la columna `role` para incluir `PACIENTE`:

```php
DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'ASISTENTE', 'PACIENTE') DEFAULT 'ASISTENTE'");
```

### 7.2 Estructura de la Tabla `users`

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NULL,
    phone VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'ASISTENTE', 'PACIENTE') DEFAULT 'ASISTENTE',
    is_active BOOLEAN DEFAULT TRUE,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (phone)
);
```

---

## 8. FRONTEND

### 8.1 AuthContext

El `AuthContext` expone las siguientes propiedades:

```javascript
const { user, login, logout, loading, isAdmin, isPatient, isAssistant } = useAuth();
```

- `user`: Objeto del usuario autenticado
- `login(identifier, password, type)`: Función de login (`type` puede ser `'email'` o `'phone'`)
- `logout()`: Función de logout
- `loading`: Estado de carga
- `isAdmin`: Boolean, true si el usuario es ADMIN
- `isPatient`: Boolean, true si el usuario es PACIENTE
- `isAssistant`: Boolean, true si el usuario es ASISTENTE

### 8.2 Rutas Protegidas

- **`PrivateRoute`**: Para ADMIN y ASISTENTE. Redirige a PACIENTE a `/mi-perfil`
- **`AdminRoute`**: Solo para ADMIN
- **`PatientRoute`**: Solo para PACIENTE

### 8.3 Interceptor 401

El interceptor de Axios maneja automáticamente respuestas 401:
- Limpia tokens de `localStorage`
- Redirige a `/login`
- Muestra mensaje de error

---

## 9. PRUEBAS

### 9.1 Crear Usuario PACIENTE

```bash
# Usando Tinker
php artisan tinker

$user = \App\Models\User::create([
    'name' => 'Test Paciente',
    'phone' => '+528123456789',
    'role' => \App\Enums\UserRole::PACIENTE,
    'password' => \Illuminate\Support\Facades\Hash::make('test123'),
    'is_active' => true,
]);
```

### 9.2 Probar Login

**Con cURL**:
```bash
# Login por email
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "password123"
  }'

# Login por teléfono
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "8123456789",
    "password": "password123"
  }'
```

**Con Postman**:
1. Método: POST
2. URL: `http://localhost/api/v1/auth/login`
3. Headers: `Content-Type: application/json`
4. Body (raw JSON):
```json
{
  "phone": "8123456789",
  "password": "password123"
}
```

### 9.3 Probar Acceso a Rutas Protegidas

```bash
# Obtener token del login anterior
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

# Acceder a /me
curl -X GET http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

---

## 10. ARCHIVOS MODIFICADOS

### Backend

1. **Migración**: `database/migrations/2026_02_11_202205_add_paciente_role_to_users_table.php`
   - Agrega `PACIENTE` al enum de roles

2. **Factory**: `database/factories/UserFactory.php`
   - Agrega método `patient()` para crear usuarios PACIENTE

3. **Form Request**: `app/Http/Requests/User/StoreUserRequest.php`
   - Valida `phone` como requerido para PACIENTE
   - Valida `email` como opcional para PACIENTE

4. **Form Request**: `app/Http/Requests/User/UpdateUserRequest.php`
   - Permite actualizar `phone`

5. **Controller**: `app/Http/Controllers/Api/V1/UserController.php`
   - Maneja creación de usuarios con `phone`

6. **Resource**: `app/Http/Resources/UserResource.php`
   - Incluye `phone` en la respuesta

7. **AuthController**: `app/Http/Controllers/Api/V1/AuthController.php`
   - Ya soportaba login por teléfono para PACIENTE (sin cambios)

### Frontend

1. **App.js**: `frontend/src/App.js` y `backend/resources/frontend/src/App.js`
   - Agrega `PatientRoute` para rutas específicas de PACIENTE
   - Actualiza `PrivateRoute` para redirigir PACIENTE

2. **AuthContext**: `frontend/src/context/AuthContext.js` y `backend/resources/frontend/src/context/AuthContext.js`
   - Agrega `isPatient` e `isAssistant` al contexto

---

## 11. PRÓXIMOS PASOS (MEJORAS FUTURAS)

1. **Políticas de Autorización**: Implementar políticas de Laravel para restringir acceso de PACIENTE a recursos específicos (solo sus propias citas, etc.)

2. **Relación User-Patient**: Crear relación entre `User` (rol PACIENTE) y `Patient` para vincular cuentas

3. **Dashboard de Paciente**: Crear vista específica para pacientes con sus citas y datos personales

4. **Tests**: Agregar tests automatizados para:
   - Creación de usuario PACIENTE
   - Login de usuario PACIENTE
   - Acceso a rutas según rol
   - Restricciones de permisos

5. **Validación de Teléfono**: Mejorar normalización y validación de teléfonos mexicanos

---

## 12. SOLUCIÓN DE PROBLEMAS

### Problema: No se puede crear usuario PACIENTE

**Causa**: La migración no se ha ejecutado.

**Solución**:
```bash
php artisan migrate
```

### Problema: Login por teléfono falla

**Causa**: El teléfono no está normalizado correctamente.

**Solución**: Asegúrate de que el teléfono esté en formato mexicano (10 dígitos) o con código de país.

### Problema: Usuario PACIENTE no puede acceder a rutas

**Causa**: El frontend está redirigiendo incorrectamente.

**Solución**: Verifica que `isPatient` esté correctamente calculado en `AuthContext`.

---

## 13. REFERENCIAS

- [Documentación de Autenticación 401](./AUTH_401_HANDLING.md)
- [Base de Conocimiento](./knowledge-base.md)
- [Enum UserRole](../backend/app/Enums/UserRole.php)
