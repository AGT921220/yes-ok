# Configuración de Twilio WhatsApp

## Variables de Entorno

Agregar a tu archivo `.env`:

```env
# Provider de mensajería (twilio o meta)
MESSAGING_PROVIDER=twilio

# Twilio Configuration
TWILIO_ACCOUNT_SID=tu_account_sid_aqui
TWILIO_AUTH_TOKEN=tu_auth_token_aqui
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
TWILIO_STATUS_CALLBACK_URL=https://tu-dominio.com/api/v1/webhooks/twilio/status
```

## Descripción de Variables

- **MESSAGING_PROVIDER**: Selecciona el provider (`twilio` o `meta`). Default: `twilio`
- **TWILIO_ACCOUNT_SID**: Account SID de tu cuenta de Twilio
- **TWILIO_AUTH_TOKEN**: Auth Token de tu cuenta de Twilio
- **TWILIO_WHATSAPP_FROM**: Número de WhatsApp desde el cual se envían los mensajes (formato: `whatsapp:+14155238886`)
- **TWILIO_STATUS_CALLBACK_URL**: URL opcional para recibir actualizaciones de estado de mensajes

## Cómo Obtener las Credenciales

1. Ve a [Twilio Console](https://console.twilio.com/)
2. En el dashboard, encontrarás tu **Account SID** y **Auth Token**
3. Para WhatsApp:
   - Ve a "Messaging" > "Try it out" > "Send a WhatsApp message"
   - O configura un número de WhatsApp Business en "Phone Numbers" > "Manage" > "Buy a number" > "WhatsApp"

## Probar Envío de Mensajes

```bash
# Usar valores por defecto
php artisan test:whatsapp

# Especificar destino
php artisan test:whatsapp --to=+5216144950659

# Especificar destino y mensaje
php artisan test:whatsapp --to=+5216144950659 --message="Hola desde Twilio"
```

## Configurar Webhooks

### 1. Mensajes Entrantes

Configura en Twilio Console:
- **URL**: `https://tu-dominio.com/api/v1/webhooks/twilio/whatsapp`
- **Método**: POST

### 2. Status Callbacks

Configura en Twilio Console o en la variable `TWILIO_STATUS_CALLBACK_URL`:
- **URL**: `https://tu-dominio.com/api/v1/webhooks/twilio/status`
- **Método**: POST

## Probar Webhooks Localmente

Usa [ngrok](https://ngrok.com/) o similar para exponer tu servidor local:

```bash
# Instalar ngrok
ngrok http 8000

# Usar la URL generada en Twilio Console
# Ejemplo: https://abc123.ngrok.io/api/v1/webhooks/twilio/whatsapp
```

### Probar con cURL

**Mensaje entrante:**
```bash
curl -X POST https://tu-dominio.com/api/v1/webhooks/twilio/whatsapp \
  -d "From=whatsapp:+5216144950659" \
  -d "To=whatsapp:+14155238886" \
  -d "Body=Hola desde WhatsApp" \
  -d "MessageSid=SM1234567890abcdef" \
  -d "AccountSid=AC1234567890abcdef"
```

**Status callback:**
```bash
curl -X POST https://tu-dominio.com/api/v1/webhooks/twilio/status \
  -d "MessageSid=SM1234567890abcdef" \
  -d "MessageStatus=delivered" \
  -d "To=whatsapp:+5216144950659" \
  -d "From=whatsapp:+14155238886"
```

## Seguridad

### Validación de Firma (Recomendado)

Twilio puede validar que las peticiones vengan realmente de ellos usando firmas. Para habilitarlo:

1. Asegúrate de que `TWILIO_AUTH_TOKEN` esté configurado
2. El método `validateTwilioSignature()` en `TwilioWebhookController` está disponible pero comentado por defecto
3. Descomenta la llamada en los métodos `handleInboundMessage` y `handleStatusCallback` si deseas habilitarlo

## Estados de Mensajes

Twilio retorna los siguientes estados:
- `queued`: Mensaje en cola
- `sent`: Mensaje enviado
- `delivered`: Mensaje entregado
- `read`: Mensaje leído
- `failed`: Mensaje fallido
- `undelivered`: No entregado

Estos se mapean automáticamente a estados internos en el sistema.

## Notas

- Los números deben estar en formato E.164 (ej: `+5216144950659`)
- Twilio agrega automáticamente el prefijo `whatsapp:` cuando es necesario
- El sistema normaliza números automáticamente
- Los errores se loggean automáticamente con contexto completo
