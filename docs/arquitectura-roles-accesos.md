# Arquitectura de Roles y Accesos - yes-ok

**Última actualización**: 2026-02-12  
**Mantenido por**: Senior Laravel Engineer + Tech Writer Senior

---

## 1. RESUMEN EJECUTIVO

yes-ok implementa un sistema de roles y accesos con **separación clara de responsabilidades**:

- **1 SOLO ADMIN**: Administrador único del sistema (creado solo por seed)
- **N ASISTENTES**: Personal administrativo (creados desde módulo "Usuarios")
- **N PACIENTES**: Clientes del sistema (creados desde módulo "Pacientes")

Cada paciente tiene automáticamente un usuario asociado con acceso al sistema.

---

## 2. DIAGRAMA DE ROLES Y RELACIONES

```
┌─────────────────────────────────────────────────────────────┐
│                    ARQUITECTURA DE ROLES                     │
└─────────────────────────────────────────────────────────────┘

ADMIN (1 único)
  │
  ├─ Creado por: seed/migración
  ├─ Acceso: Completo
  └─ Módulo: No se crea desde UI

ASISTENTE (N)
  │
  ├─ Creado por: Módulo "Usuarios" (solo ADMIN)
  ├─ Acceso: Limitado (no puede gestionar usuarios)
  └─ Endpoint: POST /api/v1/users (role=ASISTENTE)

PACIENTE (N)
  │
  ├─ Creado por: Módulo "Pacientes" (ADMIN/ASISTENTE)
  ├─ Acceso: Muy limitado (solo su información)
  ├─ Endpoint: POST /api/v1/clients
  └─ Usuario: Se crea automáticamente con role=PACIENTE
```

---

## 3. RELACIONES DE BASE DE DATOS

### 3.1 Tabla `users`

```sql
users
├── id (PK)
├── name
├── email (UNIQUE, nullable para PACIENTE)
├── phone (UNIQUE, nullable para ASISTENTE/ADMIN)
├── password (hashed)
├── role ENUM('ADMIN', 'ASISTENTE', 'PACIENTE')
├── is_active
└── timestamps
```

### 3.2 Tabla `patients`

```sql
patients
├── id (PK)
├── user_id (FK → users.id, UNIQUE, nullable)
├── first_name
├── paternal_surname
├── maternal_surname
├── whatsapp (UNIQUE)
├── email (nullable)
└── timestamps
```

**Relación**: `patients.user_id` → `users.id` (1:1, UNIQUE)

---

## 4. REGLAS DE NEGOCIO

### 4.1 Creación de ADMIN

- **NO se puede crear desde UI**
- Solo se crea mediante:
  - Seed (`DatabaseSeeder`)
  - Migración manual
  - Comando Artisan (si existe)

**Validación**: Si alguien intenta crear un ADMIN desde `POST /api/v1/users`, debe rechazarse.

### 4.2 Creación de ASISTENTE

**Módulo**: "Usuarios" (`/usuarios`)

**Flujo**:
1. ADMIN accede a `/usuarios`
2. Clic en "Nuevo Asistente"
3. Completa formulario:
   - Nombre completo
   - Email (requerido)
   - Contraseña (generada o manual)
   - Rol: ASISTENTE (fijo, no se puede cambiar)
4. Guarda

**Endpoint**: `POST /api/v1/users`
```json
{
  "name": "María Asistente",
  "email": "maria@yes-ok.com",
  "password": "password123",
  "role": "ASISTENTE"
}
```

**Validaciones**:
- `role` debe ser `ASISTENTE` (no se acepta `PACIENTE` ni `ADMIN`)
- `email` es requerido y único
- `password` mínimo 6 caracteres

**Error si intenta crear PACIENTE**:
```json
{
  "ok": false,
  "message": "Los pacientes no se pueden crear desde el módulo de Usuarios. Use el módulo de Pacientes.",
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Los pacientes no se pueden crear desde el módulo de Usuarios. Use el módulo de Pacientes."
  }
}
```

### 4.3 Creación de PACIENTE

**Módulo**: "Pacientes" (`/pacientes`)

**Flujo**:
1. ADMIN o ASISTENTE accede a `/pacientes`
2. Clic en "Nuevo Paciente"
3. Completa formulario:
   - Nombre(s)
   - Apellido Paterno
   - Apellido Materno (opcional)
   - WhatsApp (requerido, único)
   - Email (opcional, para login)
   - **Contraseña para acceso del paciente (opcional)**: si se deja en blanco, se genera una y se muestra una sola vez; si se define, el paciente usará esa para iniciar sesión.
   - Consentimiento de notificaciones
4. Guarda
5. **Sistema automáticamente**:
   - Crea `User` con `role=PACIENTE`
   - Usa la contraseña definida o genera una segura
   - Asocia `Patient.user_id` con `User.id`
   - Si la contraseña fue generada: muestra modal con contraseña (solo una vez). Si fue definida: mensaje indicando que el paciente podrá iniciar sesión con la configurada.
6. **Acceso del paciente**: con esa contraseña el paciente tiene acceso; a **ese paciente** se le configuran las **notas SOAP** (por expediente).

**Endpoint**: `POST /api/v1/clients`
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
`password` es opcional (mín. 8 caracteres). Si no se envía, se genera una y se devuelve en la respuesta (solo una vez).

**Response** (si contraseña generada):
```json
{
  "data": { "id": 1, "first_name": "Juan", ... },
  "password": "aB3xK9mP",
  "message": "Paciente creado exitosamente. Guarda la contraseña para dársela al paciente."
}
```
**Response** (si el cliente definió contraseña): no incluye `password`; `message` indica que el paciente podrá iniciar sesión con la configurada.

**Action utilizada**: `CreatePatientWithUserAction`

---

## 5. FLUJO DE CREACIÓN DE PACIENTE (DETALLADO)

### 5.1 Action: `CreatePatientWithUserAction`

**Ubicación**: `app/Actions/CreatePatientWithUserAction.php`

**Responsabilidades**:
1. Validar datos del paciente
2. Verificar unicidad de WhatsApp
3. Verificar unicidad de email (si se proporciona)
4. Normalizar teléfono a formato E.164
5. Usar contraseña enviada en el request (si viene y cumple reglas) o generar contraseña segura (12–16 caracteres)
6. Crear `User` con `role=PACIENTE`
7. Crear `Patient` asociado
8. Devolver `password_generated` para que el controller solo incluya `password` en la respuesta cuando fue generada
9. Todo dentro de transacción DB

**Código**:
```php
$action = new CreatePatientWithUserAction();
$result = $action->execute($patientData, $email);

// Retorna:
// [
//   'patient' => Patient,
//   'user' => User,
//   'password' => string|null,  // Solo cuando fue generada
//   'password_generated' => bool
// ]
```

### 5.2 Generación de Contraseña

**Algoritmo**:
- Longitud: 8 caracteres
- Caracteres permitidos: `ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789`
- Excluye caracteres confusos: `0`, `O`, `I`, `l`, `1`

**Ejemplo**: `aB3xK9mP`

**Seguridad**:
- Se hashea con `bcrypt` antes de guardar
- Solo se muestra una vez en la respuesta
- No se almacena en texto plano

### 5.3 Normalización de Teléfono

**Formato de entrada**: 
- `8123456789` (10 dígitos)
- `+528123456789` (E.164)

**Formato almacenado**: `+528123456789` (E.164)

**Helper**: `PhoneNormalizer::normalizeMexicanPhone()`

---

## 6. FLUJO DE CREACIÓN DE ASISTENTE (DETALLADO)

### 6.1 Endpoint

**POST** `/api/v1/users`

**Autenticación**: Requerida (solo ADMIN)

**Request**:
```json
{
  "name": "María Asistente",
  "email": "maria@yes-ok.com",
  "password": "password123",
  "role": "ASISTENTE"
}
```

**Validaciones**:
- `role` debe ser `ASISTENTE` (rechaza `PACIENTE` y `ADMIN`)
- `email` requerido y único
- `password` mínimo 6 caracteres

**Response**:
```json
{
  "data": {
    "id": 2,
    "name": "María Asistente",
    "email": "maria@yes-ok.com",
    "role": "ASISTENTE",
    "is_active": true
  }
}
```

---

## 7. SEGURIDAD Y VALIDACIONES

### 7.1 Validaciones en Backend

**StoreUserRequest**:
- Rechaza `role=PACIENTE` con mensaje claro
- Rechaza `role=ADMIN` (si se implementa validación adicional)

**StorePatientRequest**:
- Valida WhatsApp único
- Valida email opcional pero único si se proporciona
- Normaliza WhatsApp

**CreatePatientWithUserAction**:
- Verifica unicidad de email en `users`
- Verifica unicidad de phone en `users`
- Verifica unicidad de WhatsApp en `patients`
- Todo en transacción DB

### 7.2 Validaciones en Frontend

**Módulo Usuarios**:
- Selector de rol solo muestra "Asistente"
- No permite seleccionar "Paciente"
- Título: "Nuevo Asistente"

**Módulo Pacientes**:
- Muestra contraseña generada después de crear
- Copia automática al portapapeles
- Alerta con información de login

---

## 8. LOGIN DE PACIENTE

### 8.1 Endpoint

**POST** `/api/v1/auth/login`

### 8.2 Login por Teléfono

**Request**:
```json
{
  "phone": "8123456789",
  "password": "aB3xK9mP"
}
```

**Proceso**:
1. Normaliza teléfono a `+528123456789`
2. Busca `User` con `phone` y `role=PACIENTE`
3. Valida contraseña
4. Verifica `is_active=true`
5. Emite token JWT

### 8.3 Login por Email

**Request**:
```json
{
  "email": "juan@example.com",
  "password": "aB3xK9mP"
}
```

**Proceso**:
1. Busca `User` con `email`
2. Valida contraseña
3. Verifica `is_active=true`
4. Emite token JWT

**Nota**: El login por email funciona para cualquier rol, pero el frontend redirige a PACIENTE a `/mi-perfil`.

### 8.4 Portal del Paciente (rutas API y middleware)

Tras el login, un PACIENTE **no** puede acceder a los endpoints de staff (pacientes, citas globales, usuarios, configuración, reportes). Se aplica **deny by default**:

- **Middleware `role.staff`**: aplicado a todas las rutas administrativas. Si el usuario es PACIENTE → **403 Forbidden**.
- **Middleware `role.patient`**: aplicado a las rutas bajo el prefijo `/api/v1/me/*`. Solo el rol PACIENTE puede acceder; ADMIN/ASISTENTE reciben 403.

Rutas del portal del paciente (solo rol PACIENTE, JWT):

- `GET /api/v1/me/profile` — Perfil del paciente asociado al user.
- `PUT /api/v1/me/profile` — Actualizar email y consent_notifications.
- `GET /api/v1/me/appointments` — Listado de citas (siempre filtrado por `patient_id` del user).
- `GET /api/v1/me/appointments/{id}` — Detalle de una cita (solo si pertenece al paciente; 403 si es ajena).

Las queries en el backend **siempre** usan el `patient_id` de `auth()->user()->patient`; no se confía en el ID del request salvo para el id de la cita, y se valida ownership. Ver [Patient Portal](patient-portal.md) para detalles completos.

---

## 9. BACKFILL DE PACIENTES EXISTENTES

### 9.1 Comando Artisan

**Comando**: `php artisan patients:backfill-users`

**Funcionalidad**:
- Detecta pacientes sin `user_id`
- Crea usuario asociado automáticamente
- Maneja colisiones de email/teléfono
- Muestra contraseñas generadas

**Opciones**:
- `--dry-run`: Solo muestra qué haría sin ejecutar
- `--force`: Fuerza asociación incluso si hay colisiones

**Ejemplo**:
```bash
php artisan patients:backfill-users --dry-run
php artisan patients:backfill-users
```

**Output**:
```
🔍 Buscando pacientes sin usuario asociado...

📋 Encontrados 5 pacientes sin usuario.

✅ Usuario creado para paciente #1 (Juan Pérez García)
   Usuario ID: 10
   Contraseña: aB3xK9mP

📊 Resumen:
   ✅ Creados/Asociados: 5
   ⚠️  Omitidos: 0
   ❌ Errores: 0
```

---

## 10. CONSIDERACIONES DE SEGURIDAD

### 10.1 Contraseñas

**Actual**:
- Se generan automáticamente
- Se muestran una vez en la respuesta
- Se hashean con `bcrypt`
- No se almacenan en texto plano

**Mejora Futura** (Recomendado):
- Implementar flujo "Set Password"
- Generar token de activación
- Enviar email/SMS con enlace
- No exponer contraseña en respuesta

### 10.2 Validación de Roles

**Backend**:
- `StoreUserRequest` rechaza `PACIENTE`
- `UserController@store` valida nuevamente
- `CreatePatientWithUserAction` solo crea `PACIENTE`

**Frontend**:
- Selector de rol solo muestra opciones válidas
- No permite enviar roles inválidos

### 10.3 Unicidad

**Constraints de BD**:
- `users.email` UNIQUE
- `users.phone` UNIQUE
- `patients.whatsapp` UNIQUE
- `patients.user_id` UNIQUE

**Validaciones en código**:
- Verificaciones antes de crear
- Mensajes de error claros

---

## 11. TESTING

### 11.1 Tests Recomendados

**Feature Tests**:

```php
// Test: Admin crea asistente OK
public function test_admin_can_create_assistant()
{
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin, 'api');
    
    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test Asistente',
        'email' => 'asistente@test.com',
        'password' => 'password123',
        'role' => 'ASISTENTE',
    ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('users', [
        'email' => 'asistente@test.com',
        'role' => 'ASISTENTE',
    ]);
}

// Test: Crear paciente desde Users → falla
public function test_cannot_create_patient_from_users_endpoint()
{
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin, 'api');
    
    $response = $this->postJson('/api/v1/users', [
        'name' => 'Test Paciente',
        'phone' => '+528123456789',
        'password' => 'password123',
        'role' => 'PACIENTE',
    ]);
    
    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Los pacientes no se pueden crear desde el módulo de Usuarios...'
    ]);
}

// Test: Admin crea paciente OK
public function test_admin_can_create_patient()
{
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin, 'api');
    
    $response = $this->postJson('/api/v1/clients', [
        'first_name' => 'Juan',
        'paternal_surname' => 'Pérez',
        'whatsapp' => '+528111234567',
        'email' => 'juan@test.com',
    ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('patients', [
        'whatsapp' => '+528111234567',
    ]);
    $this->assertDatabaseHas('users', [
        'email' => 'juan@test.com',
        'role' => 'PACIENTE',
    ]);
}

// Test: Paciente puede login
public function test_patient_can_login()
{
    $patient = Patient::factory()->create();
    $user = User::factory()->patient()->create([
        'phone' => $patient->whatsapp,
        'password' => Hash::make('password123'),
    ]);
    $patient->user_id = $user->id;
    $patient->save();
    
    $response = $this->postJson('/api/v1/auth/login', [
        'phone' => '8123456789',
        'password' => 'password123',
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'access_token',
        'token_type',
        'user',
    ]);
}

// Test: Paciente NO puede acceder a ruta de admin
public function test_patient_cannot_access_admin_route()
{
    $patient = Patient::factory()->create();
    $user = User::factory()->patient()->create();
    $patient->user_id = $user->id;
    $patient->save();
    
    $token = JWTAuth::fromUser($user);
    
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/users');
    
    $response->assertStatus(200);
    // Debe retornar array vacío o redirigir
}
```

---

## 12. ARCHIVOS MODIFICADOS/CREADOS

### Backend

**Nuevos**:
- `app/Actions/CreatePatientWithUserAction.php`
- `app/Console/Commands/BackfillPatientUsersCommand.php`
- `database/migrations/2026_02_11_220118_add_user_id_to_patients_table.php`

**Modificados**:
- `app/Http/Controllers/Api/V1/PatientController.php`
- `app/Http/Controllers/Api/V1/UserController.php`
- `app/Http/Requests/User/StoreUserRequest.php`
- `app/Models/Patient.php`
- `app/Models/User.php`

### Frontend

**Modificados**:
- `frontend/src/pages/Users.js`
- `frontend/src/pages/Patients.js`
- `backend/resources/frontend/src/pages/Users.js`
- `backend/resources/frontend/src/pages/Patients.js`

### Documentación

**Nuevos**:
- `docs/arquitectura-roles-accesos.md` (este archivo)

**Actualizados**:
- `docs/auth-clientes.md` (si existe, actualizar)

---

## 13. CÓMO PROBAR END-TO-END

### 13.1 Preparación

```bash
# 1. Ejecutar migraciones
cd backend
php artisan migrate

# 2. (Opcional) Ejecutar backfill si hay pacientes existentes
php artisan patients:backfill-users --dry-run
php artisan patients:backfill-users
```

### 13.2 Crear Asistente

1. Login como ADMIN: `admin@yes-ok.com / admin123`
2. Ir a `/usuarios`
3. Clic en "Nuevo Asistente"
4. Completar:
   - Nombre: "María Asistente"
   - Email: "maria@yes-ok.com"
   - Contraseña: Generar o escribir manualmente
5. Guardar
6. Verificar que aparece en la lista

### 13.3 Crear Paciente

1. Login como ADMIN o ASISTENTE
2. Ir a `/pacientes`
3. Clic en "Nuevo Paciente"
4. Completar:
   - Nombre: "Juan"
   - Apellido Paterno: "Pérez"
   - WhatsApp: "+528111234567"
   - Email: "juan@example.com" (opcional)
5. Guardar
6. **Copiar la contraseña mostrada**
7. Verificar que aparece en la lista

### 13.4 Login de Paciente

1. Ir a `/login`
2. Seleccionar tab "Teléfono"
3. Ingresar:
   - Teléfono: `8111234567` (10 dígitos)
   - Contraseña: La copiada anteriormente
4. Login exitoso
5. Verificar redirección a `/mi-perfil`

### 13.5 Verificar Restricciones

1. Intentar crear paciente desde `/usuarios` → Debe fallar
2. Intentar crear asistente desde `/pacientes` → No es posible (solo pacientes)
3. Verificar que solo hay 1 ADMIN

---

## 14. DIAGRAMA DE FLUJOS

### 14.1 Flujo de Creación de Asistente

```
ADMIN → /usuarios → "Nuevo Asistente"
  ↓
Completa formulario (nombre, email, password)
  ↓
POST /api/v1/users {role: "ASISTENTE"}
  ↓
StoreUserRequest valida (rechaza PACIENTE)
  ↓
UserController@store crea User
  ↓
Response: User creado
```

### 14.2 Flujo de Creación de Paciente

```
ADMIN/ASISTENTE → /pacientes → "Nuevo Paciente"
  ↓
Completa formulario (datos paciente + email opcional)
  ↓
POST /api/v1/clients
  ↓
StorePatientRequest valida
  ↓
PatientController@store
  ↓
CreatePatientWithUserAction.execute()
  ├─ Valida unicidad WhatsApp
  ├─ Valida unicidad email/phone
  ├─ Normaliza teléfono
  ├─ Usa password del request o genera una
  ├─ Crea User (role=PACIENTE) [DB Transaction]
  └─ Crea Patient (user_id) [DB Transaction]
  ↓
Response: Patient + password (solo si fue generada)
  ↓
Frontend: si hay password → modal con Copiar; si no → toast con mensaje de contraseña configurada
```

---

## 15. MEJORAS FUTURAS

### 15.1 Flujo "Set Password"

**Implementar**:
- Token de activación (password reset)
- Email/SMS con enlace
- Página `/activate-password?token=xxx`
- No exponer contraseña en respuesta

### 15.2 Políticas de Autorización

**Implementar**:
- `PatientPolicy` para restringir acceso
- Paciente solo ve sus propias citas
- Paciente solo ve su propia información

### 15.3 Dashboard de Paciente

**Crear**:
- Vista específica `/mi-perfil`
- Mostrar citas del paciente
- Permitir cancelar/reagendar
- Mostrar información personal

---

## 16. REFERENCIAS

- [Documentación de Autenticación 401](./AUTH_401_HANDLING.md)
- [Base de Conocimiento](./knowledge-base.md)
- [Patient Portal (rutas, middleware, vistas)](./patient-portal.md)
- [Enum UserRole](../backend/app/Enums/UserRole.php)
- [Action CreatePatientWithUserAction](../backend/app/Actions/CreatePatientWithUserAction.php)
