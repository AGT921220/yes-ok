# Manejo Centralizado de Errores 401/419 - Documentación Técnica

## 📋 Resumen Ejecutivo

Se ha implementado un sistema centralizado y robusto para manejar errores de autenticación (401 Unauthenticated y 419 CSRF Token Mismatch) que automáticamente desloguea usuarios, limpia tokens y redirige a la página de login cuando se detecta que el usuario no está autenticado.

## 🎯 Objetivos Cumplidos

✅ **Deslogueo automático** cuando cualquier request devuelve 401 o 419  
✅ **Limpieza de tokens** (localStorage)  
✅ **Redirección automática** a `/login`  
✅ **Código centralizado** (sin lógica duplicada en componentes)  
✅ **Principios SOLID** aplicados  
✅ **Prevención de loops infinitos**  
✅ **Manejo de rutas públicas** (no redirige desde `/agendar`, `/portal`)  

---

## 🏗️ Arquitectura de la Solución

### Patrón: Singleton + Observer Pattern

La solución utiliza dos patrones de diseño principales:

1. **Singleton**: `AuthManager` es una instancia única que gestiona el estado de autenticación
2. **Observer Pattern**: Los componentes React se registran como observadores mediante callbacks

### Separación de Responsabilidades

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND (React)                      │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────┐      ┌──────────────┐                │
│  │  Axios       │──────▶│ AuthManager  │                │
│  │ Interceptor  │      │  (Singleton) │                │
│  └──────────────┘      └──────┬───────┘                │
│                                │                         │
│                                │ callbacks              │
│                                ▼                         │
│                        ┌──────────────┐                │
│                        │ AuthContext   │                │
│                        │  (React)      │                │
│                        └──────────────┘                │
│                                                          │
└─────────────────────────────────────────────────────────┘
                              │
                              │ HTTP 401/419
                              ▼
┌─────────────────────────────────────────────────────────┐
│                    BACKEND (Laravel)                     │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────────────────────────┐              │
│  │  bootstrap/app.php                     │              │
│  │  AuthenticationException Handler       │              │
│  │  → JSON Response con formato estándar  │              │
│  └──────────────────────────────────────┘              │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

---

## 📁 Estructura de Archivos

### Frontend Principal (`frontend/src/`)

```
frontend/src/
├── utils/
│   └── authManager.js          # ⭐ Gestor centralizado de autenticación
├── api.js                       # Interceptor de Axios actualizado
└── context/
    └── AuthContext.js          # Context actualizado con registro de callbacks
```

### Frontend Integrado (`backend/resources/frontend/src/`)

```
backend/resources/frontend/src/
├── utils/
│   └── authManager.js          # Mismo gestor (copia para SPA integrada)
├── api.js                       # Interceptor de Axios actualizado
└── context/
    └── AuthContext.js          # Context actualizado
```

### Backend

```
backend/
└── bootstrap/
    └── app.php                 # Handler de AuthenticationException
```

---

## 🔧 Implementación Detallada

### 1. AuthManager (`utils/authManager.js`)

**Responsabilidades:**
- Gestionar el estado de logout (evitar loops)
- Limpiar tokens del localStorage
- Ejecutar callbacks registrados (limpiar estado de React)
- Redirigir a login cuando sea necesario
- Detectar rutas públicas y página de login

**Características clave:**

```javascript
// Singleton pattern
const authManager = new AuthManager();

// Registro de callbacks (Observer pattern)
authManager.registerLogoutCallback(() => {
  setUser(null); // Limpiar estado de React
});

// Manejo centralizado de errores
authManager.handleAuthError(error); // Detecta 401/419 automáticamente
```

**Protecciones implementadas:**
- ✅ Flag `isLoggingOut` previene loops infinitos
- ✅ Verificación de rutas públicas antes de redirigir
- ✅ Verificación de página de login para evitar redirecciones innecesarias
- ✅ Try-catch en callbacks para evitar fallos en cascada

### 2. Interceptor de Axios (`api.js`)

**Antes:**
```javascript
if (error.response?.status === 401) {
  localStorage.removeItem('yes-ok_token');
  localStorage.removeItem('yes-ok_user');
  window.location.href = '/login';
}
```

**Después:**
```javascript
import authManager from './utils/authManager';

// En el interceptor:
const status = error?.response?.status;
if (status === 401 || status === 419) {
  authManager.handleAuthError(error);
}
```

**Ventajas:**
- ✅ Maneja tanto 401 como 419
- ✅ Lógica centralizada (no duplicada)
- ✅ Integración con AuthContext automática
- ✅ Prevención de loops

### 3. AuthContext (`context/AuthContext.js`)

**Cambios principales:**

```javascript
// Registro automático del callback al montar
useEffect(() => {
  const handleLogout = () => {
    setUser(null);
  };
  
  authManager.registerLogoutCallback(handleLogout);
  
  // Cleanup al desmontar
  return () => {
    authManager.unregisterLogoutCallback(handleLogout);
  };
}, []);

// Logout ahora usa AuthManager
const logout = () => {
  authManager.logout({
    redirect: true,
    redirectTo: '/login',
  });
};
```

**Beneficios:**
- ✅ El interceptor puede limpiar el estado de React sin dependencias directas
- ✅ Cleanup automático previene memory leaks
- ✅ Consistencia: logout manual y automático usan la misma lógica

### 4. Backend Handler (`bootstrap/app.php`)

**Implementación:**

```php
use Illuminate\Auth\AuthenticationException;

// Manejar AuthenticationException (401 Unauthenticated)
$exceptions->render(function (AuthenticationException $e, $request) {
    if ($request->is('api/*')) {
        return response()->json([
            'ok' => false,
            'message' => 'No autenticado. Por favor, inicia sesión nuevamente.',
            'error' => [
                'code' => 'UNAUTHENTICATED',
                'message' => 'No autenticado. Por favor, inicia sesión nuevamente.',
            ],
        ], 401);
    }
});
```

**Garantías:**
- ✅ Todas las rutas API devuelven JSON (no HTML) en caso de 401
- ✅ Formato consistente con el resto de errores de la API
- ✅ Mensaje en español

---

## 🔄 Flujo de Ejecución

### Escenario: Usuario con token expirado hace una petición

```
1. Usuario hace petición → Axios interceptor agrega token
   ↓
2. Backend valida token → Token inválido/expirado
   ↓
3. Laravel lanza AuthenticationException
   ↓
4. Handler en bootstrap/app.php → Responde JSON 401
   ↓
5. Frontend recibe 401 → Interceptor de Axios detecta
   ↓
6. Interceptor llama → authManager.handleAuthError(error)
   ↓
7. AuthManager ejecuta:
   a. Limpia tokens (localStorage)
   b. Ejecuta callbacks registrados (setUser(null))
   c. Verifica ruta actual
   d. Redirige a /login (si no está en ruta pública)
   ↓
8. Usuario es redirigido a /login
   ↓
9. AuthContext detecta user === null → Muestra formulario de login
```

---

## 🛡️ Protecciones Implementadas

### 1. Prevención de Loops Infinitos

```javascript
if (this.isLoggingOut && !force) {
  console.warn('Logout ya está en proceso, ignorando solicitud duplicada');
  return;
}
```

### 2. Rutas Públicas

```javascript
const publicPaths = ['/agendar', '/portal', '/login'];

if (!this.isPublicPath(currentPath)) {
  window.location.href = '/login';
}
```

### 3. Página de Login

```javascript
if (!this.isLoginPage(currentPath)) {
  window.location.href = '/login';
}
```

### 4. Manejo de Errores en Callbacks

```javascript
this.logoutCallbacks.forEach(callback => {
  try {
    callback();
  } catch (error) {
    console.error('Error ejecutando callback de logout:', error);
  }
});
```

---

## ✅ Principios SOLID Aplicados

### Single Responsibility Principle (SRP)
- **AuthManager**: Solo gestiona autenticación
- **Interceptor**: Solo intercepta requests/responses
- **AuthContext**: Solo gestiona estado de usuario en React

### Open/Closed Principle (OCP)
- **AuthManager** es extensible mediante callbacks sin modificar su código interno
- Nuevos componentes pueden registrarse sin cambiar la implementación base

### Dependency Inversion Principle (DIP)
- Los componentes dependen de la abstracción `authManager` (no de implementaciones concretas)
- El interceptor no depende directamente de React Context

---

## 🧪 Casos de Uso Cubiertos

| Caso | Comportamiento |
|------|---------------|
| Token expirado en petición protegida | ✅ Logout automático + Redirección |
| Token inválido | ✅ Logout automático + Redirección |
| Usuario ya en `/login` | ✅ No redirige (evita loop) |
| Usuario en `/agendar` (público) | ✅ No redirige |
| Usuario en `/portal` (público) | ✅ No redirige |
| Múltiples 401 simultáneos | ✅ Solo un logout (flag de protección) |
| Error 419 (CSRF) | ✅ Mismo tratamiento que 401 |
| Logout manual desde componente | ✅ Usa misma lógica centralizada |

---

## 📝 Uso en Componentes

### ❌ ANTES (Lógica duplicada)

```javascript
// En cada componente que maneja errores
catch (error) {
  if (error.response?.status === 401) {
    localStorage.removeItem('yes-ok_token');
    localStorage.removeItem('yes-ok_user');
    window.location.href = '/login';
  }
}
```

### ✅ DESPUÉS (Automático)

```javascript
// No necesitas hacer nada especial
// El interceptor maneja todo automáticamente
catch (error) {
  // El error se propaga normalmente
  // Si es 401, el interceptor ya manejó el logout
  toast.error(getApiErrorMessage(error, 'Error al guardar'));
}
```

---

## 🔍 Debugging

### Logs en Desarrollo

El `AuthManager` incluye logs útiles en modo desarrollo:

```javascript
if (process.env.NODE_ENV === 'development') {
  console.warn('Error de autenticación detectado:', {
    status,
    message,
    path: window.location.pathname,
  });
}
```

### Verificar Registro de Callbacks

```javascript
// En consola del navegador
console.log(authManager.logoutCallbacks.length); // Debe ser > 0 si AuthContext está montado
```

---

## 🚀 Ventajas de esta Implementación

1. **Mantenibilidad**: Cambios en un solo lugar afectan toda la aplicación
2. **Testabilidad**: `AuthManager` puede testearse independientemente
3. **Escalabilidad**: Fácil agregar nuevos callbacks o lógica adicional
4. **Consistencia**: Mismo comportamiento en toda la aplicación
5. **Robustez**: Múltiples protecciones contra edge cases
6. **Producción-ready**: Manejo de errores completo y logging apropiado

---

## 📚 Referencias

- **Patrón Singleton**: [MDN - Singleton Pattern](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Working_with_Objects#Using_a_constructor_function)
- **Observer Pattern**: [Refactoring Guru - Observer](https://refactoring.guru/design-patterns/observer)
- **Axios Interceptors**: [Axios Docs - Interceptors](https://axios-http.com/docs/interceptors)
- **Laravel Exception Handling**: [Laravel Docs - Exception Handling](https://laravel.com/docs/11.x/errors)

---

## ✨ Conclusión

Esta implementación proporciona un sistema robusto, centralizado y mantenible para manejar errores de autenticación en toda la aplicación. Sigue principios SOLID, previene bugs comunes (loops infinitos, redirecciones innecesarias) y está lista para producción.

**Archivos modificados/creados:**
- ✅ `frontend/src/utils/authManager.js` (nuevo)
- ✅ `frontend/src/api.js` (actualizado)
- ✅ `frontend/src/context/AuthContext.js` (actualizado)
- ✅ `backend/resources/frontend/src/utils/authManager.js` (nuevo)
- ✅ `backend/resources/frontend/src/api.js` (actualizado)
- ✅ `backend/resources/frontend/src/context/AuthContext.js` (actualizado)
- ✅ `backend/bootstrap/app.php` (actualizado)

**Total: 7 archivos** (3 nuevos, 4 actualizados)
