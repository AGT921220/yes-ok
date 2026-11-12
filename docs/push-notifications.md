# Notificaciones Push — yes-ok

Implementación de notificaciones push para la app móvil (Expo) y el backend Laravel. Soporta **Expo Push Token** (Expo Go / EAS) y **FCM/APNs** nativo en development builds.

## Resumen

- **App móvil**: Modal de permiso al entrar (con "Permitir" / "Ahora no"); si "Ahora no", no se vuelve a mostrar en 7 días. Token (Expo o nativo) se envía al backend con `platform`, `device_name`, `app_version`, `last_seen_at`. Pantalla **Ajustes > Notificaciones** para activar/desactivar. Listeners para notificaciones recibidas y al tocar (p. ej. abrir detalle de cita).
- **Backend**: Tabla `device_tokens` con `device_name`, `app_version`; POST upsert idempotente; 409 si el token pertenece a otro usuario. Envío vía **Expo Push API** para tokens `ExponentPushToken[...]` y vía **FCM HTTP v1** para tokens nativos.
- **Probar**: `php artisan send:test-push {userId}` (sin hardcodear tokens; usa los registrados en BD).

---

## 1. Setup Firebase (Google Console)

1. Entra en [Firebase Console](https://console.firebase.google.com/) y crea un proyecto (o usa uno existente).
2. Añade una app **Android**:
   - Nombre del paquete: debe coincidir con el de tu build (p. ej. con Expo: revisa el `applicationId` en `android/app/build.gradle` tras `expo prebuild`, o el que indique tu `app.json`).
   - Descarga **google-services.json** y colócalo en la raíz del proyecto móvil (ver sección 2).
3. En el proyecto Firebase, ve a **Configuración del proyecto** (engranaje) → **Cuentas de servicio** → **Generar nueva clave privada**. Descarga el JSON de la cuenta de servicio.
4. Coloca ese JSON en el backend (ver sección 3). **No** lo subas al repositorio; usa variables de entorno para la ruta o el contenido.

---

## 2. App móvil (Expo)

### Dependencias

- `expo-notifications`, `expo-device`, `expo-constants` (ya en el proyecto).

### Expo projectId (para Expo Push Token)

Para que la app obtenga un **Expo Push Token** (válido en Expo Go y en EAS builds):

- En **app.json** o **app.config.js** configura el EAS project ID, por ejemplo en `expo.extra.eas.projectId` con el UUID de tu proyecto en [expo.dev](https://expo.dev) (Settings → Project ID).
- Si no hay `projectId`, la app usa `getDevicePushTokenAsync()` (token FCM/APNs), que solo funciona en **development/build nativo** con `google-services.json` (Android) o credenciales APNs (iOS).

### Dónde poner `google-services.json` (FCM nativo / dev build)

- **Ruta**: `mobile/google-services.json`.
- En `app.json`: `"googleServicesFile": "./google-services.json"` en `expo.android`.
- Para **development build**: `npx expo prebuild` y luego `eas build` o `npx expo run:android`. En **Expo Go** sin projectId no se obtendrá token nativo útil.

### Permisos Android

- Android 13+ (API 33+): `POST_NOTIFICATIONS` en `app.json`; se solicita en tiempo de ejecución (modal de la app o al permitir en Ajustes > Notificaciones).

### Variables de entorno

- `EXPO_PUBLIC_API_URL`: URL base del API. La app envía el token a `POST /api/v1/push-tokens` con `Authorization: Bearer <token>`.

### UX en la app

1. **Al entrar** (tras login): si el permiso no está concedido y el usuario no eligió "Ahora no" en los últimos 7 días, se muestra un **modal** (Permitir notificaciones / Ahora no).
2. **Permitir**: se pide permiso al sistema, se obtiene Expo o token nativo, se envía al backend (`token`, `platform`, `device_name`, `app_version`).
3. **Ahora no**: se guarda la fecha y no se vuelve a mostrar el modal hasta pasados 7 días; desde **Ajustes > Notificaciones** se puede activar después.
4. **Más > Notificaciones** (staff) o **Mi Cuenta > Notificaciones push** (paciente): pantalla con estado (Activadas/Desactivadas), botón Activar (o abrir ajustes del sistema si está denegado) y botón Desactivar (DELETE en backend y borrado local del token).

### Sonido custom en Android (canal y sound1)

- **Ruta del archivo de sonido:** `mobile/android/app/src/main/res/raw/sound1.mp3`  
  (el archivo debe existir tras `expo prebuild`; si usas prebuild, colócalo en la ruta indicada antes de `./gradlew assembleRelease` o configúralo vía plugin si tu versión de Expo lo permite).
- **Canal usado para citas:** `appointments_channel`  
  - Nombre mostrado: **Citas**
  - Importance: MAX
  - Sonido: `sound1` (nombre del recurso sin extensión; corresponde a `sound1.mp3` en `res/raw/`)
  - Vibración: activa (patrón `[0, 250, 250, 250]`)
- La app crea el canal al iniciar (`ensureAppointmentsChannel` en `App.js`) y al registrar permisos (`pushNotifications/index.js`, `usePushNotificationsRegistration.js`). El backend debe enviar `channelId: "appointments_channel"` y `sound: "sound1"` en el payload de notificaciones Android (Expo Push API o FCM según corresponda).
- **Android 8+:** El sonido y la vibración los define el **canal**. Si el canal se creó antes sin sonido, el sistema puede ignorar cambios posteriores. Para aplicar el sonido custom:
  - **Opción recomendada:** Desinstalar la app e instalar de nuevo (el canal se vuelve a crear con sound1).
  - **Alternativa:** Usar un canal nuevo (p. ej. `appointments_channel_v2`) en app y backend; actualizar backend para enviar ese `channel_id`.

### Cómo probar (app)

1. Configura `projectId` (Expo Go) o coloca `google-services.json` y haz un dev build.
2. Inicia sesión en la app (paciente o staff).
3. Acepta el modal de notificaciones o ve a Ajustes > Notificaciones y pulsa Activar.
4. En el backend: `php artisan send:test-push <user_id>` (ver sección 4).
5. Toca la notificación: debe abrirse la pantalla de detalle de la cita (si el payload tiene `type: APPOINTMENT_REMINDER` y `appointment_id`).
6. **Probar sonido Android:** Envía una push de prueba; en el dispositivo debe sonar `sound1`. Si no suena, comprueba que el canal sea `appointments_channel`, que exista `res/raw/sound1.mp3` y, si el canal ya existía, desinstala/reinstala la app.

**Payload de ejemplo (Expo Push API) para Android con sonido:**

```json
{
  "to": "ExponentPushToken[xxx]",
  "title": "Recordatorio de cita",
  "body": "Tienes una cita mañana a las 10:00",
  "sound": "sound1",
  "channelId": "appointments_channel",
  "data": { "type": "APPOINTMENT_REMINDER", "appointment_id": "1" }
}
```

### Checklist troubleshooting (push)

- [ ] El token está registrado en el backend para el usuario correcto (`device_tokens`).
- [ ] En Android: el canal `appointments_channel` existe (la app lo crea al iniciar); si cambiaste el sonido del canal, desinstala/reinstala.
- [ ] El archivo `android/app/src/main/res/raw/sound1.mp3` existe en el build (prebuild incluye `res/raw/`).
- [ ] Backend envía `channelId: "appointments_channel"` y `sound: "sound1"` en notificaciones Android.
- [ ] Si usas FCM: el payload debe incluir `android.notification.channel_id` y `sound` según la API de FCM/Expo.

### Login: texto blanco en APK (fix)

En builds de release (APK), el texto de la pantalla de login ("Iniciar sesión", campos y labels) podía verse blanco por el tema/estilos. **Qué se cambió:** En `mobile/src/screens/LoginScreen.js` se definieron colores explícitos para no depender del tema en release: título y texto de campos en negro (`#000000`), placeholders en gris (`#666666`), labels secundarios en gris oscuro (`#6B7280`). Los `TextInput` usan `color` y `placeholderTextColor` fijos. El texto del botón "Iniciar sesión" se mantiene blanco sobre el botón primary. Con esto el login es legible en APK sin tocar la lógica de tema/dark mode del resto de la app.

---

## 3. Backend (Laravel)

### Variables de entorno

Añade en `.env`:

```env
FIREBASE_PROJECT_ID=tu-project-id
FIREBASE_CREDENTIALS=/ruta/absoluta/a/firebase-credentials.json
FIREBASE_ANDROID_CHANNEL_ID=default
```

- **FIREBASE_PROJECT_ID**: ID del proyecto en Firebase (p. ej. `yes-ok-xxxxx`).
- **FIREBASE_CREDENTIALS**: Ruta absoluta al JSON de la cuenta de servicio de Firebase. Por defecto el código usa `storage_path('app/firebase-credentials.json')` si no se define; puedes poner el archivo en `storage/app/firebase-credentials.json` y no definir la variable, o apuntar a otra ruta segura.
- **FIREBASE_ANDROID_CHANNEL_ID**: Canal de notificación Android (opcional; por defecto `default`).

### Migración

Ejecuta la migración de tokens de dispositivo (si no está aplicada):

```bash
cd backend
php artisan migrate
```

Tabla: `device_tokens` (`user_id`, `token`, `platform`, `device_id`, `device_name`, `app_version`, `last_seen_at`, `revoked_at`, `created_at`, `updated_at`). Índice único `(user_id, token)`.

### Endpoints (protegidos por `auth:api`)

- **POST /api/v1/push-tokens**  
  Body: `{ "token": "<expo_or_fcm_token>", "platform": "ios|android", "device_name": null, "app_version": null }`  
  Registra o actualiza el token para el usuario autenticado (idempotente). Si el mismo token está registrado para **otro** usuario, responde **409** (token ya asignado).

- **DELETE /api/v1/push-tokens**  
  Body: `{ "token": "<token>" }`  
  Marca el token como revocado (`revoked_at`) para el usuario actual.

### Envío: Expo vs FCM

- Si el token empieza por `ExponentPushToken[`, el backend envía vía **Expo Push API** (`https://exp.host/--/api/v2/push/send`).
- En caso contrario, envía vía **FCM HTTP v1** (requiere `FIREBASE_CREDENTIALS` y `FIREBASE_PROJECT_ID`).

### Cómo probar envío desde el backend

**Comando (recomendado, sin hardcodear tokens):**

```bash
php artisan send:test-push 1
php artisan send:test-push 1 --title="Hola" --body="Mensaje de prueba"
```

El comando usa los tokens ya registrados en `device_tokens` para ese `user_id`.

**Desde código:**

```php
use App\Services\PushNotificationService;
$push = app(PushNotificationService::class);
$push->sendToUser($userId, 'Prueba', 'Mensaje de prueba', ['type' => 'TEST']);
```

**Ejemplo curl (registrar token a mano):**

```bash
# Primero obtén un access_token (login) y el Expo push token desde la app.
curl -X POST "https://tu-api/api/v1/push-tokens" \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{"token":"ExponentPushToken[xxx]","platform":"android","device_name":"Pixel 6","app_version":"1.0.0"}'
```

---

## 4. Comportamiento del MVP

- **Al crear una cita** (desde staff): se despacha `SendAppointmentCreatedPushJob`, que envía una push al usuario del paciente con tipo `APPOINTMENT_REMINDER` y `appointment_id`.
- **Job de recordatorios** (`RemindUpcomingAppointmentsViaWhatsAppJob`): para citas del día siguiente, envía push a cada paciente (usuario asociado) con el mismo tipo y `appointment_id`.
- En la app, al **tocar** la notificación, se navega a la pantalla de detalle de esa cita (paciente).

---

## 5. Archivos modificados / creados

### Backend

- `database/migrations/2026_02_13_100000_create_device_tokens_table.php`
- `database/migrations/2026_02_13_110000_add_device_name_app_version_to_device_tokens.php` (device_name, app_version)
- `app/Models/DeviceToken.php` (fillable: device_name, app_version)
- `app/Http/Requests/PushToken/StorePushTokenRequest.php` (validación token, platform, device_name, app_version)
- `app/Http/Controllers/Api/V1/PushTokenController.php` (store con 409 si token de otro user, destroy)
- `app/Services/PushNotificationService.php` (sendToTokensViaExpo, sendToTokensViaFcm, detección por prefijo ExponentPushToken)
- `config/services.php` (expo_push.url)
- `app/Console/Commands/SendTestPushCommand.php` (send:test-push {userId})
- `routes/api.php` (POST/DELETE push-tokens)
- `tests/Feature/PushTokenTest.php` (store, upsert, 409, destroy, 401)

### Mobile

- `package.json`: expo-constants
- `src/features/notifications/`: PermissionPromptModal.js, usePushNotificationsRegistration.js, notifications.api.js, notifications.storage.js
- `src/screens/SettingsNotificationsScreen.js`
- `src/services/api/pushTokens.js` (register con device_name, app_version)
- `src/services/pushNotifications/index.js` (Expo token si projectId, device_name/app_version, notificationsStorage)
- `src/app/MainStack.js` (PushPermissionPromptGate, pantalla SettingsNotifications, ítem Más > Notificaciones)
- `src/screens/MoreMenuScreen.js` (ítem Notificaciones)
- `src/screens/PatientProfileScreen.js` (enlace Notificaciones push → SettingsNotifications)
- `src/app/RootNavigator.js` (addNotificationReceivedListener + log en dev)
- `src/screens/LoginScreen.js` (colores fijos para título, inputs y placeholders; fix texto blanco en APK release)

### Documentación

- `docs/push-notifications.md` (este archivo)
- `mobile/README.md` (sección Notificaciones push y projectId)

---

## 6. Notas de compatibilidad

- **Android 13+**: Permiso `POST_NOTIFICATIONS` en tiempo de ejecución; declarado en `app.json`.
- **Expo Go**: Con **projectId** configurado la app obtiene **Expo Push Token** y el backend envía vía Expo Push API (no requiere FCM en el dispositivo). Sin projectId, en Expo Go no se obtiene token útil.
- **Development build**: Con `google-services.json` (Android) o credenciales APNs (iOS), `getDevicePushTokenAsync()` devuelve token nativo y el backend puede usar FCM/APNs.
- **iOS**: Mismo flujo; `platform`: `ios`. Expo Push API envía a APNs cuando el token es Expo; FCM también puede enviar a iOS si está configurado.
- **Tokens inválidos**: Expo push receipts o respuestas FCM con error llevan a marcar el token como `revoked_at` en el backend.

---

## 7. Qué se cambió (implementación end-to-end)

- **UX**: Modal de permiso al entrar (Permitir / Ahora no); cooldown 7 días si "Ahora no"; pantalla Ajustes > Notificaciones para activar/desactivar sin molestar.
- **Token**: Prioridad Expo Push Token (`getExpoPushTokenAsync({ projectId })`) si hay projectId; fallback a token nativo (`getDevicePushTokenAsync()`).
- **Backend**: Campos `device_name`, `app_version`; validación con Form Request; 409 cuando el token ya pertenece a otro usuario (no reasignar por seguridad).
- **Envío**: Backend detecta tipo de token (ExponentPushToken vs nativo) y envía vía Expo API o FCM; sin hardcodear tokens; comando `send:test-push {userId}`.
- **Tests**: Feature tests para POST (upsert, 409), DELETE y 401.
- **Logging**: Solo en __DEV__ en la app; backend con Log::warning/info según nivel.
