# Checklist — Notificaciones push (verificación manual)

## Backend

- [ ] `php artisan migrate` (incluye migración `add_device_name_app_version_to_device_tokens`)
- [ ] `.env`: `FIREBASE_PROJECT_ID`, `FIREBASE_CREDENTIALS` (si usas tokens FCM nativos); opcional `EXPO_PUSH_URL`
- [ ] `php artisan send:test-push 1` (reemplaza 1 por un user_id con al menos un token en `device_tokens`): ejecuta sin error
- [ ] Tests: `php artisan test tests/Feature/PushTokenTest.php`

## Mobile

- [ ] `yarn` o `npm install` (expo-constants instalado)
- [ ] En dispositivo físico: abrir app, login, comprobar que aparece el modal de notificaciones (si permiso no concedido y no “Ahora no” reciente)
- [ ] “Permitir notificaciones”: comprobar que no hay error y que en backend el token queda en `device_tokens` (mismo user_id)
- [ ] “Ahora no”: comprobar que el modal no vuelve a salir en la misma sesión; opcionalmente esperar o limpiar AsyncStorage `push_permission_prompt_last_skipped_at` para probar de nuevo
- [ ] Más > Notificaciones (staff) o Mi Cuenta > Notificaciones push (paciente): pantalla muestra estado; “Activar” pide permiso y registra; “Desactivar” revoca en backend y limpia token local
- [ ] Cerrar sesión: comprobar que el token se revoca en backend (DELETE) y que al volver a iniciar sesión se puede registrar de nuevo
- [ ] Recibir notificación: `php artisan send:test-push <user_id>` y comprobar que llega al dispositivo; tocar y comprobar navegación si aplica (p. ej. detalle de cita)

## Expo / EAS

- [ ] Si usas Expo Go o EAS: en `app.json` o `app.config.js` configurar `expo.extra.eas.projectId` con el UUID del proyecto en expo.dev
- [ ] Si usas solo FCM nativo: `google-services.json` en `mobile/` y development build (`npx expo run:android` o EAS build)

## Opcional

- [ ] Probar 409: registrar el mismo token con otro usuario (p. ej. desde otra cuenta en otro dispositivo o Postman) y comprobar que el segundo POST devuelve 409
- [ ] Probar DELETE con token inexistente: respuesta 404
