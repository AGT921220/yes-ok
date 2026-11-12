<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - yes-ok</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; margin: 0; }
        .container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .card { background: #ffffff; padding: 24px 28px; border-radius: 8px; box-shadow: 0 10px 25px rgba(15,23,42,0.12); width: 100%; max-width: 360px; }
        h1 { margin: 0 0 4px; font-size: 22px; text-align: center; color: #111827; }
        p { margin: 0 0 16px; font-size: 13px; text-align: center; color: #6b7280; }
        label { display: block; font-size: 13px; margin-bottom: 4px; color: #374151; }
        input { width: 100%; padding: 8px 10px; font-size: 14px; border-radius: 6px; border: 1px solid #d1d5db; box-sizing: border-box; }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 1px rgba(37,99,235,0.2); }
        .field { margin-bottom: 14px; }
        .btn { width: 100%; padding: 9px 0; border-radius: 999px; border: none; background: #2563eb; color: #ffffff; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn:disabled { opacity: .7; cursor: not-allowed; }
        .error { margin-bottom: 10px; font-size: 13px; color: #b91c1c; }
        .footer { margin-top: 12px; font-size: 11px; text-align: center; color: #9ca3af; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Acceso administrador</h1>
        <p>Ingresa para ver Horizon y Log Viewer</p>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="field">
                <label for="email">Correo</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn">
                Entrar
            </button>
        </form>

        <div class="footer">
            &copy; {{ date('Y') }} yes-ok
        </div>
    </div>
</div>
</body>
</html>

