# yes-ok – App Móvil (React Native + Expo)

App móvil que replica el frontend web de yes-ok: gestión de clientes, reservas y portal del cliente.

---

## 1. Project Audit (Frontend Web)

### 1.1 Rutas / pantallas (Web)

| Ruta Web | Descripción | Rol |
|----------|-------------|-----|
| `/login` | Login (email o teléfono + contraseña) | Público |
| `/agendar` | Reservar cita sin registro (público) | Público |
| `/portal` | Portal paciente por token (magic link) | Público |
| `/` | Agenda (calendario de citas) | Staff (ADMIN/ASISTENTE) |
| `/pacientes` | Lista de pacientes | Staff |
| `/expedientes` | Lista de expedientes | Staff |
| `/expedientes/:id` | Expediente clínico (historia + SOAP) | Staff |
| `/reagendar` | Cola por reagendar | Staff |
| `/bloqueos` | Bloqueos de disponibilidad | Staff |
| `/reportes` | Reportes y estadísticas | Staff |
| `/usuarios` | Gestión de usuarios (asistentes) | Solo ADMIN |
| `/configuracion` | Configuración | Solo ADMIN |
| `/mi-perfil` | Inicio del paciente (resumen) | Solo PACIENTE |
| `/mis-citas` | Mis citas (lista) | Solo PACIENTE |
| `/mis-citas/:id` | Detalle de una cita | Solo PACIENTE |
| `/mi-cuenta` | Mi cuenta (perfil) | Solo PACIENTE |

### 1.2 Componentes clave (Web)

- **Layout**: sidebar (Agenda, Pacientes, Expedientes, Reagendar, Bloqueos, Reportes) + admin (Usuarios, Configuración).
- **PatientLayout**: Inicio, Mis citas, Mi cuenta, Cerrar sesión.
- **UI**: Button, Input, Label, Card, Sheet, Dialog, Select, Tabs (Radix/shadcn).
- **Login**: Tabs email/teléfono, campos y submit.

### 1.3 Estado y auth (Web)

- **Auth**: `AuthContext` (user, login, logout, loading, isAdmin, isPatient, isAssistant).
- **Token**: `localStorage`: `yes-ok_token`, `yes-ok_user`.
- **AuthManager**: singleton; limpia tokens, callbacks de logout; en 401 llama a logout y redirige a `/login`.
- **Guards**: PrivateRoute (redirige a login o, si PACIENTE, a `/mi-perfil`); AdminRoute; PatientLayoutRoute.

### 1.4 API (Web)

- **Cliente**: axios, `baseURL = REACT_APP_BACKEND_URL || 'http://localhost'` + `/api/v1`, timeout 10s.
- **Auth**: header `Authorization: Bearer <token>`; interceptor 401 → AuthManager.handleAuthError.
- **Endpoints**: auth (login, getMe), users, patients, appointments, availability-blocks, clinical-history, soap-notes, settings, reports, me/* (paciente), public (available-slots, book, patient-portal).
- **Público**: otra instancia axios con `/api/v1/public`.

### 1.5 Documentación de referencia

- `docs/overview.md`, `docs/patient-portal.md`, `docs/citas-pacientes-config.md`, `docs/auth-clientes.md`.
- Roles: ADMIN (1), ASISTENTE (N), PACIENTE (N). Pacientes se crean desde módulo Pacientes; login por email o teléfono + contraseña.

---

## 2. Mapa de pantallas (Web → Mobile) y navegación

### 2.1 Equivalencia de pantallas

| Web | Mobile (Screen) | Navegación |
|-----|------------------|------------|
| Intro / Splash | IntroScreen (GIF + animación) | Stack (inicial) |
| `/login` | LoginScreen | Stack Auth |
| `/` (Agenda) | DashboardScreen | Stack Main (Staff) |
| `/pacientes` | PatientsScreen | Stack Main |
| `/expedientes` | RecordsScreen | Stack Main |
| `/expedientes/:id` | ClinicalRecordScreen | Stack Main |
| `/reagendar` | RescheduleQueueScreen | Stack Main |
| `/bloqueos` | AvailabilityBlocksScreen | Stack Main |
| `/reportes` | ReportsScreen | Stack Main |
| `/usuarios` | UsersScreen | Stack Main (Admin) |
| `/configuracion` | SettingsScreen | Stack Main (Admin) |
| `/mi-perfil` | PatientHomeScreen | Tabs Patient |
| `/mis-citas` | PatientAppointmentsScreen | Tabs Patient |
| `/mis-citas/:id` | PatientAppointmentDetailScreen | Stack Patient |
| `/mi-cuenta` | PatientProfileScreen | Tabs Patient |
| `/agendar` | PublicBookingScreen | Stack (público) |
| `/portal` | PatientPortalScreen (token) | Stack (público) |

### 2.2 Navegación propuesta

- **Stack raíz**: Intro (solo primera vez o siempre 1.5s) → si no hay token → AuthStack; si hay token → Main (Staff o Patient según rol).
- **AuthStack**: Login. Opcional: enlace “Reservar sin registro” (PublicBooking).
- **Staff (ADMIN/ASISTENTE)**: Bottom Tabs (Agenda, Pacientes, Expedientes, Más) + Stack para detalle (expediente, reagendar, bloqueos, reportes, usuarios, configuración).
- **Patient (PACIENTE)**: Bottom Tabs (Inicio, Mis Citas, Mi Cuenta) + Stack para detalle de cita (PatientAppointmentDetail).
- **Público**: (pendiente) PublicBooking, PatientPortal por deep link.

**Implementado**: Stack raíz → Intro → Auth (Login) o Main. Staff: Tabs Agenda, Pacientes, Expedientes, Más; desde Más se accede a Por Reagendar, Bloqueos, Reportes, Usuarios (admin), Configuración (admin); expediente desde lista de Expedientes. Patient: Tabs Inicio, Mis Citas, Mi Cuenta; detalle de cita desde Mis Citas.

---

## 3. Estructura de carpetas (mobile)

```
mobile/
  App.js                 # Entry: AuthProvider + NavigationContainer + RootNavigator
  app.json               # Expo config, splash
  index.js
  package.json
  .env.example
  src/
    app/
      RootNavigator.js   # Lógica: Intro → Auth | Main (Staff | Patient)
      AuthStack.js
      MainStack.js      # Staff: Tabs + stacks; Patient: Tabs + stack
    screens/
      IntroScreen.js
      LoginScreen.js
      DashboardScreen.js
      PatientsScreen.js, RecordsScreen.js, MoreMenuScreen.js
      ClinicalRecordScreen.js, RescheduleQueueScreen.js, AvailabilityBlocksScreen.js
      ReportsScreen.js, UsersScreen.js, SettingsScreen.js
      PatientHomeScreen.js, PatientAppointmentsScreen.js, PatientAppointmentDetailScreen.js
      PatientProfileScreen.js
      SettingsNotificationsScreen.js   # Ajustes > Notificaciones push
    components/
      ui/               # Button, Input, Card, Loader
    hooks/
      useAuth.js
    context/
      AuthContext.js
    services/
      api/
        client.js
        auth.js, pushTokens.js
        patients.js, appointments.js, availabilityBlocks.js,
        clinicalHistory.js, soapNotes.js, settings.js, reports.js,
        users.js, me.js (paciente), index.js
      pushNotifications/
        index.js        # registro token, sync con backend, revoke
    features/
      notifications/
        PermissionPromptModal.js
        usePushNotificationsRegistration.js
        notifications.api.js
        notifications.storage.js
    storage/
      storage.js        # AsyncStorage wrapper (token, user)
    theme/
      colors.js
      index.js
    utils/
      apiError.js
      authManager.js    # RN: sin window.location, usa navigation ref
    assets/
      intro.gif         # Placeholder; ver "Intro GIF" más abajo
  README.md             # Este archivo
```

---

## 4. Cómo correr

### Requisitos

- Node 18+
- npm o yarn
- Expo Go (en dispositivo o emulador) o Android Studio / Xcode para builds nativos.

### Instalación

```bash
cd mobile
npm install
```

### Variables de entorno

Copia `.env.example` a `.env` y ajusta:

```bash
cp .env.example .env
```

- **EXPO_PUBLIC_API_URL**: URL base del backend (sin `/api/v1`). Ejemplos:
  - Local: `http://localhost` o `http://192.168.1.x` (reemplaza por tu IP para dispositivo físico).
  - Staging: `https://api-staging.yes-ok.com`
  - Producción: `https://api.yes-ok.agsoftweb.com.mx`

En Expo, solo las variables con prefijo `EXPO_PUBLIC_` están disponibles en el cliente. La app usa `EXPO_PUBLIC_API_URL` para construir `baseURL` del api client.

### Notificaciones push (Expo)

- **Dependencias**: `expo-notifications`, `expo-device`, `expo-constants` (incluidas en el proyecto).
- **Expo projectId**: Para obtener un **Expo Push Token** (válido en Expo Go y en development builds), el proyecto debe tener configurado el EAS project ID. Opciones:
  1. **EAS**: Si usas EAS Build, en `app.json` añade en `expo.extra.eas.projectId` el UUID de tu proyecto (desde [expo.dev](https://expo.dev) → tu proyecto → Settings → Project ID). O usa `eas.json` y deja que `expo-constants` lo resuelva.
  2. **app.config.js**: Si usas `app.config.js`, exporta `extra: { eas: { projectId: 'tu-uuid' } }`.
- Sin `projectId`, la app intenta obtener el token nativo (FCM/APNs) con `getDevicePushTokenAsync()`, que solo funciona en **development/build nativo** con `google-services.json` (Android) o credenciales APNs (iOS).
- **Probar en dispositivo real**: Las push no funcionan en emulador/simulador. Usa un dispositivo físico y, para Expo Go, asegúrate de tener `projectId` configurado.
- **Ajustes > Notificaciones**: Desde **Más > Notificaciones** (staff) o **Mi Cuenta > Notificaciones push** (paciente) se puede activar/desactivar el registro del token en el backend.

### Iniciar

```bash
npm start
```

Luego:

- Escanear QR con Expo Go (Android/iOS).
- `a` para abrir en Android.
- `i` para abrir en iOS (solo en Mac).

### Builds (opcional)

- **Android**: `npx expo run:android` (requiere Android Studio y SDK).
- **iOS**: `npx expo run:ios` (solo en Mac con Xcode).

---

## 5. Staging / Producción

- **Staging**: en `.env` pon `EXPO_PUBLIC_API_URL=https://api-staging.yes-ok.com` y ejecuta `npm start` o genera un build de preview.
- **Producción**: `EXPO_PUBLIC_API_URL=https://api.yes-ok.agsoftweb.com.mx`. Para EAS Build: configura en `eas.json` y usa `eas build --profile production`.

No inventamos endpoints: los mismos que el frontend web (ver `frontend/src/api.js` y `docs/`).

---

## 6. Intro animada + GIF

- **Splash**: configurado en `app.json` (`expo.splash`) con color de marca y logo si se añade.
- **Pantalla Intro**: se muestra al abrir la app; incluye un GIF (`src/assets/intro.gif`) con animación de entrada (fade/scale, ~1.2–2 s) y luego redirección:
  - Si hay sesión (token válido) → Main (Home/Dashboard o Patient Home).
  - Si no hay sesión → Login.

Si no existe `intro.gif`, en el código se usa un **placeholder** (View con texto o imagen estática) y se documenta en la propia pantalla cómo reemplazarlo por `assets/intro.gif`.

---

## 7. Decisiones técnicas

- **JS solamente**: sin TypeScript.
- **Estado global**: Context (AuthContext) como en web; sin Redux/Zustand por ahora.
- **HTTP**: axios, un solo apiClient con interceptores (token, 401 → authManager + navegación a Login).
- **Persistencia**: AsyncStorage para token y usuario (equivalente a localStorage).
- **Estilos**: StyleSheet + theme central (colores, espaciado) para mantener consistencia y evitar dependencias extra.
- **Navegación**: React Navigation (Stack + Bottom Tabs); rutas protegidas según rol (Staff vs Patient).
- **Accesibilidad**: labels, roles y listas con FlatList cuando corresponda.

---

## 8. Estado de la migración

- **Hecho**: Intro, Login, Auth; Staff (Agenda por día con navegación fechas, Pacientes, Expedientes, Más → Reagendar, Bloqueos, Reportes, Usuarios, Configuración, Expediente clínico); Patient (Inicio con próximas citas desde API, Mis Citas, detalle cita, Mi Cuenta). Servicios API: auth, patients, appointments, availabilityBlocks, clinicalHistory, soapNotes, settings, reports, users, me.
- **Pendiente**: Crear/editar citas desde móvil (crear/editar sigue en web); Agendar público y Portal por token; formularios crear/editar (pacientes, usuarios, bloqueos) en móvil; toast/alert unificado; pruebas en dispositivo.
