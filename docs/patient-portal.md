# Portal del Paciente (Patient Portal)

**Última actualización**: 2026-02-12

---

## 1. Resumen

Los usuarios con rol **PACIENTE** que inician sesión con teléfono/email y contraseña (JWT) tienen un **portal propio** en la aplicación React. No ven las vistas ni menús del personal (ADMIN/ASISTENTE) y solo pueden acceder a sus propios datos.

- **Autenticación**: misma API de login (`POST /api/v1/auth/login`). El frontend detecta el rol y redirige a PACIENTE a `/mi-perfil`.
- **Backend**: rutas bajo el prefijo `/api/v1/me/*` protegidas con `auth:api` + middleware `role.patient`. Siempre filtran por el `patient_id` asociado al usuario autenticado.
- **Frontend**: layout `PatientLayout` con menú reducido (Inicio, Mis citas, Mi cuenta). Rutas bajo `/mi-perfil`, `/mis-citas`, `/mi-cuenta`.

---

## 2. Rutas API: Staff vs Paciente

### 2.1 Rutas solo para staff (ADMIN y ASISTENTE)

Middleware: `auth:api` + `role.staff`. Si un PACIENTE intenta acceder, recibe **403 Forbidden**.

- `GET/POST/PUT/DELETE /api/v1/clients`
- `GET/POST/PUT /api/v1/clients/{id}/clinical-history`, close, unlock
- `GET/POST /api/v1/clients/{id}/soap-notes`, `GET/PUT/DELETE /api/v1/soap-notes/{id}`, sign, addendum
- `GET/POST/PUT/DELETE /api/v1/appointments`, reschedule-queue, attendance, payments
- `GET/POST/DELETE /api/v1/availability-blocks`
- `GET/PUT /api/v1/users` (solo ADMIN en lógica del controlador)
- `GET/PUT /api/v1/settings`
- `GET /api/v1/reports/*`, `GET /api/v1/notification-logs`, `POST /api/v1/seed`

### 2.2 Rutas solo para PACIENTE (portal con login JWT)

Middleware: `auth:api` + `role.patient`. Si ADMIN o ASISTENTE intentan acceder, reciben **403**.

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/v1/me/profile` | Perfil del paciente (datos asociados al user) |
| PUT | `/api/v1/me/profile` | Actualizar email y consent_notifications |
| GET | `/api/v1/me/appointments` | Listado de citas del paciente (filtrado por patient_id) |
| GET | `/api/v1/me/appointments/{id}` | Detalle de una cita (solo si es del paciente; 403 si es ajena) |

En todas las respuestas se usa **siempre** el `patient_id` derivado de `auth()->user()->patient`. No se confía en IDs enviados en la URL más allá del id de la cita, y se valida ownership en `GET /me/appointments/{id}`.

### 2.3 Rutas compartidas (cualquier rol autenticado)

- `GET /api/v1/auth/me` — Devuelve el usuario autenticado (incluye rol).

---

## 3. Middleware (Backend)

- **`role.staff`** (`EnsureStaffRole`): permite solo `UserRole::ADMIN` y `UserRole::ASISTENTE`. PACIENTE → 403.
- **`role.patient`** (`EnsurePatientRole`): permite solo `UserRole::PACIENTE`. ADMIN/ASISTENTE → 403.

Registro en `bootstrap/app.php`:

```php
$middleware->alias([
    'role.staff' => \App\Http\Middleware\EnsureStaffRole::class,
    'role.patient' => \App\Http\Middleware\EnsurePatientRole::class,
]);
```

---

## 4. Políticas y autorización

- **AppointmentPolicy**: `view` — staff siempre; paciente solo si `appointment.patient_id === auth()->user()->patient->id`. `update` y `delete` solo staff. Se usa en el controlador de staff (AppointmentController) para show, update, destroy, payments, etc.
- **MeController**: no usa Policy; obtiene el paciente con `auth()->user()->patient` y en `showAppointment` comprueba que `appointment.patient_id === patient->id` antes de devolver la cita.

---

## 5. Vistas y rutas Frontend (React)

### 5.1 Layout del paciente

- **PatientLayout**: sidebar con “Inicio”, “Mis citas”, “Mi cuenta” y “Cerrar sesión”. Sin acceso a Agenda, Pacientes, Expedientes, Reportes, Usuarios ni Configuración.

### 5.2 Rutas (todas con `PatientLayoutRoute`: auth + rol PACIENTE)

| Ruta | Componente | Descripción |
|------|------------|-------------|
| `/mi-perfil` | PatientDashboard | Resumen: próxima cita, enlaces a Mis citas y Mi cuenta |
| `/mis-citas` | PatientAppointmentsList | Listado de sus citas (historial y próximas) |
| `/mis-citas/:id` | PatientAppointmentShow | Detalle de una cita (solo suya) |
| `/mi-cuenta` | PatientProfile | Ver/editar perfil (email, consent_notifications) |

### 5.3 Comportamiento de rutas privadas

- **PrivateRoute** (agenda, pacientes, expedientes, etc.): si el usuario es PACIENTE, redirige a `/mi-perfil`. Solo ADMIN/ASISTENTE ven el `Layout` administrativo.
- **PatientLayoutRoute**: solo usuarios con `role === 'PACIENTE'`; si no, redirige a `/`.
- Un paciente no puede acceder por URL directa a `/pacientes`, `/usuarios`, etc.: el backend devuelve 403 en esas APIs y el frontend no muestra esas pantallas.

---

## 6. Cómo crear un usuario paciente y asignación de rol

- Los pacientes **no** se crean desde el módulo Usuarios. Se crean desde **Pacientes** (`/pacientes`).
- Al crear un cliente (POST `/api/v1/clients`), se ejecuta `CreatePatientWithUserAction`, que crea un `User` con `role=PACIENTE` y un `Patient` con `user_id` apuntando a ese usuario. El rol se asigna automáticamente; no hay pasos adicionales.
- Ese usuario puede iniciar sesión en `/login` con teléfono (o email si se configuró) y la contraseña definida o generada al crear el paciente. Tras el login, el frontend redirige a `/mi-perfil`.

---

## 7. Tests

En `tests/Feature/PatientPortalAccessTest.php`:

- Paciente recibe 403 en rutas de staff (`/api/v1/clients`, `/api/v1/appointments`, `/api/v1/users`, `/api/v1/settings`).
- Paciente puede acceder a `GET /api/v1/me/profile` y recibe solo sus datos.
- Paciente solo ve sus propias citas en `GET /api/v1/me/appointments`.
- Paciente no puede ver una cita ajena con `GET /api/v1/me/appointments/{id}` (403).
- Paciente puede ver su propia cita con `GET /api/v1/me/appointments/{id}`.
- Admin puede acceder a rutas de staff (200).
- Staff (admin) recibe 403 en `GET /api/v1/me/profile`.

Ejecutar:

```bash
cd backend
php artisan test tests/Feature/PatientPortalAccessTest.php
```

---

## 8. Referencias

- [Arquitectura de roles y accesos](./arquitectura-roles-accesos.md)
- [Autenticación clientes (PACIENTE)](./auth-clientes.md)
- [Citas y pacientes](./citas-pacientes-config.md)
