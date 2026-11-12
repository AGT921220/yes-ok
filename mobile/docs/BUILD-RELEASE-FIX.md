# Fix: Android release build (Metro + NODE_ENV + expo-constants)

## Qué se cambió

### 1. Mismatch expo-constants vs Expo SDK (causa de `:expo-constants:compileReleaseKotlin`)

- **Problema:** El proyecto usa **Expo SDK 54** (`expo@~54.0.33`, `sdkVersion: 54.0.0`), pero se había instalado **expo-constants@55.0.7** (SDK 55). En compilación Android release, el código Kotlin de expo-constants 55 usa APIs de **expo-modules-core** de SDK 55 (`service`, `services`, `ServiceInterface`), que no existen en la versión de expo-modules-core que trae SDK 54 → "Unresolved reference" y "overrides nothing".
- **Evidencia:** `npx expo-doctor` reportaba: "expo-constants expected ~18.0.13, found 55.0.7" y "duplicate native module dependencies" (expo-constants 55 en root, 18.0.13 anidado bajo expo / expo-notifications).
- **Solución:** Alinear con el SDK usando el método oficial: `npx expo install expo-constants`. Eso fijó **expo-constants@~18.0.13** en `package.json` y deduplicó en el lockfile. Tras el cambio, `npx expo-doctor` pasa 17/17 checks.
- **Regla:** Para paquetes Expo no usar `yarn add expo-constants@x` a secas; usar `npx expo install expo-constants` para que quede la versión compatible con el SDK instalado.

### 2. Import roto en `src/services/pushNotifications/index.js`

- **Problema:** Metro no resolvía `../features/notifications/notifications.storage` porque desde `src/services/pushNotifications/` un solo `..` llega a `src/services/`, donde no existe `features/`.
- **Solución:** Ruta corregida a `../../features/notifications/notifications.storage` (dos niveles arriba hasta `src/`, luego `features/notifications/notifications.storage`).
- **Archivo:** El módulo `src/features/notifications/notifications.storage.js` ya existía con la API usada (`getStoredPushToken`, `setStoredPushToken`, etc.) y la key `yes-ok_push_token`; no se creó ni se renombró nada.

### 3. NODE_ENV en build release Android

- **Problema:** Warning `NODE_ENV environment variable is required but was not specified` durante `expo-constants:createExpoConfig` en builds release.
- **Solución:** Script en `package.json` que fija `NODE_ENV=production` antes del comando de release (Linux nativo; sin cross-env).
- **Script:** `"android:release": "NODE_ENV=production npx expo run:android --variant release"`

## Comandos exactos: instalación limpia y build release

**Recomendado tras cambiar versiones Expo (evitar cachés y native desactualizado):**

```bash
cd mobile

# 1. Limpieza
rm -rf node_modules
rm -rf android/.gradle android/build android/app/build   # si existe android/

# 2. Reinstalar dependencias (yarn porque hay yarn.lock)
yarn install

# 3. Verificar alineación Expo (debe pasar 17/17)
npx expo-doctor

# 4. Si el proyecto usa prebuild (expo run genera android/), regenerar native:
npx expo prebuild --platform android --clean

# 5. Build release (NODE_ENV=production)
yarn android:release
```

**Alternativa sin script (misma línea):**

```bash
NODE_ENV=production npx expo run:android --variant release
```

**Solo APK con Gradle (después de prebuild):**

```bash
cd mobile/android
./gradlew clean
./gradlew assembleRelease
```

## Verificación del import

La cadena de imports queda así:

- `index.js` → `App.js` → `AuthProvider` + `RootNavigator`
- `RootNavigator.js` usa `syncTokenWithBackend` de `src/services/pushNotifications/index.js`
- `pushNotifications/index.js` importa `notificationsStorage` desde `../../features/notifications/notifications.storage` (ruta corregida)

No se modificó el flujo de `.env` ni `EXPO_PUBLIC_*`.
