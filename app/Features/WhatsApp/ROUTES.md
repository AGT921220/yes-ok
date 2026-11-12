# Rutas de WhatsApp - Documentación

## Rutas de Webhooks (Públicas)

### 1. Mensajes Entrantes
- **URL**: `POST /api/v1/webhooks/twilio/whatsapp`
- **Autenticación**: No requerida (pero se recomienda validar firma de Twilio)
- **Descripción**: Recibe mensajes entrantes de WhatsApp vía Twilio
- **Payload esperado**:
  ```json
  {
    "From": "whatsapp:+5216144950659",
    "To": "whatsapp:+14155238886",
    "Body": "Mensaje del usuario",
    "MessageSid": "SM1234567890abcdef",
    "AccountSid": "AC1234567890abcdef",
    "NumMedia": "0"
  }
  ```

### 2. Status Callbacks
- **URL**: `POST /api/v1/webhooks/twilio/status`
- **Autenticación**: No requerida (pero se recomienda validar firma de Twilio)
- **Descripción**: Recibe actualizaciones de estado de mensajes enviados
- **Payload esperado**:
  ```json
  {
    "MessageSid": "SM1234567890abcdef",
    "MessageStatus": "delivered",
    "To": "whatsapp:+5216144950659",
    "From": "whatsapp:+14155238886",
    "ErrorCode": null,
    "ErrorMessage": null
  }
  ```

## Seguridad

### Validación de Firma (Recomendado)

Para habilitar la validación de firma de Twilio:

1. Asegúrate de que `TWILIO_AUTH_TOKEN` esté configurado en `.env`
2. Descomenta la llamada a `validateTwilioSignature()` en `TwilioWebhookController`

**Nota**: La validación está implementada pero deshabilitada por defecto para facilitar pruebas locales.

### Rate Limiting

Considera agregar rate limiting a estas rutas si es necesario:

```php
Route::post('/whatsapp', [...])->middleware('throttle:60,1');
Route::post('/status', [...])->middleware('throttle:60,1');
```

## Configuración en Twilio Console

1. **Mensajes Entrantes**:
   - Ve a "Messaging" > "Settings" > "WhatsApp Sandbox" o "WhatsApp Business"
   - Configura la URL: `https://tu-dominio.com/api/v1/webhooks/twilio/whatsapp`

2. **Status Callbacks**:
   - Puedes configurarlo globalmente en la cuenta o por mensaje
   - URL: `https://tu-dominio.com/api/v1/webhooks/twilio/status`
   - O usa la variable `TWILIO_STATUS_CALLBACK_URL` en `.env`

## Pruebas Locales

Usa [ngrok](https://ngrok.com/) para exponer tu servidor local:

```bash
ngrok http 8000
```

Luego configura las URLs en Twilio Console usando la URL de ngrok.
