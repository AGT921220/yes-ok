# WhatsApp Cloud API Integration

Integración con Meta WhatsApp Cloud API usando arquitectura hexagonal simplificada.

## Estructura

```
Features/WhatsApp/
├── Domain/
│   ├── Ports/
│   │   └── WhatsAppSender.php          # Puerto (interface)
│   └── ValueObjects/
│       └── WhatsAppPhone.php           # Value Object para teléfonos
├── Application/
│   ├── UseCases/
│   │   └── SendTextMessage.php         # Caso de uso
│   └── DTO/
│       ├── SendTextMessageInput.php    # DTO de entrada
│       └── SendTextMessageResult.php   # DTO de resultado
└── Infrastructure/
    └── Meta/
        ├── MetaWhatsAppCloudSender.php  # Adapter (implementa puerto)
        ├── MetaWhatsAppCloudConfig.php  # Configuración desde env
        ├── MetaWhatsAppCloudClient.php  # Cliente HTTP
        └── MetaWhatsAppCloudException.php # Excepciones específicas
```

## Configuración

Agregar a `.env`:

```env
WHATSAPP_CLOUD_ACCESS_TOKEN=tu_token_aqui
WHATSAPP_CLOUD_PHONE_NUMBER_ID=tu_phone_number_id
WHATSAPP_CLOUD_API_VERSION=v20.0
WHATSAPP_CLOUD_FROM_PHONE_NUMBER=5216144950659  # Opcional
WHATSAPP_CLOUD_DEFAULT_TEST_TO=5216144950659
```

## Uso

### Comando de prueba

```bash
# Usar valores por defecto
php artisan test:whatsapp

# Especificar destino
php artisan test:whatsapp --to=5216144950659

# Especificar destino y mensaje
php artisan test:whatsapp --to=5216144950659 --message="Hola desde Laravel"
```

### Uso programático

```php
use App\Features\WhatsApp\Application\UseCases\SendTextMessage;
use App\Features\WhatsApp\Application\DTO\SendTextMessageInput;

// El caso de uso se inyecta automáticamente por DI
$useCase = app(SendTextMessage::class);

$input = SendTextMessageInput::create(
    to: '5216144950659',
    message: 'Mensaje de prueba'
);

$result = $useCase->execute($input);
echo "Message ID: {$result->messageId}";
```

## Notas

- Los números de teléfono se normalizan automáticamente (solo dígitos, sin +)
- El formato E.164 se maneja internamente
- Los errores se loggean automáticamente
- Timeout de 30 segundos para requests HTTP
- La integración está registrada en `AppServiceProvider` mediante binding del puerto
