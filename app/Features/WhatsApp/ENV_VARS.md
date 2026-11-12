# Variables de Entorno - WhatsApp Cloud API

Agregar las siguientes variables a tu archivo `.env`:

```env
# WhatsApp Cloud API (Meta)
WHATSAPP_CLOUD_ACCESS_TOKEN=tu_token_de_acceso_aqui
WHATSAPP_CLOUD_PHONE_NUMBER_ID=tu_phone_number_id_aqui
WHATSAPP_CLOUD_API_VERSION=v20.0
WHATSAPP_CLOUD_FROM_PHONE_NUMBER=5216144950659
WHATSAPP_CLOUD_DEFAULT_TEST_TO=5216144950659
```

## Descripción

- **WHATSAPP_CLOUD_ACCESS_TOKEN**: Token de acceso permanente de Meta (obtenido desde Meta for Developers)
- **WHATSAPP_CLOUD_PHONE_NUMBER_ID**: ID del número de teléfono de WhatsApp Business (formato numérico)
- **WHATSAPP_CLOUD_API_VERSION**: Versión de la API (default: v20.0)
- **WHATSAPP_CLOUD_FROM_PHONE_NUMBER**: Número de teléfono desde el cual se envían los mensajes (opcional, solo referencia)
- **WHATSAPP_CLOUD_DEFAULT_TEST_TO**: Número por defecto para pruebas del comando `test:whatsapp`

## Cómo obtener las credenciales

1. Ve a [Meta for Developers](https://developers.facebook.com/)
2. Crea una app o selecciona una existente
3. Agrega el producto "WhatsApp"
4. Obtén el **Access Token** y el **Phone Number ID** desde el dashboard
