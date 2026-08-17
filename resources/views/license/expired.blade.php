<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Bloqueado - Licencia Expirada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 0;
        }
        .license-card {
            max-width: 520px;
            width: 100%;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
        }
        .client-id-box {
            background: #e9ecef;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 1.1rem;
            text-align: center;
            margin: 0.5rem 0;
            user-select: all;
            word-break: break-all;
        }
    </style>
</head>
<body>

    <div class="license-card text-center">
        <div class="mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="#dc3545" class="bi bi-lock-fill" viewBox="0 0 16 16">
                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
            </svg>
        </div>
        
        <h2 class="text-danger mb-2">Sistema Bloqueado</h2>
        <p class="text-muted small mb-3">Su licencia ha expirado o no es válida. Por favor, contacte al administrador para renovar su suscripción.</p>

        <div class="alert alert-info py-2">
            <small>Envíe este ID a su proveedor:</small>
            <div class="client-id-box">{{ $clientId }}</div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger py-2 mb-3">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
        @endif

        <!-- Option 1: Auto Sync via VPN -->
        <div class="card bg-light border mb-4">
            <div class="card-body p-3">
                <h6 class="card-title text-primary mb-2">⚡ Sincronizar por VPN (ZeroTier / Tailscale)</h6>
                <form action="{{ route('license.sync') }}" method="POST">
                    @csrf
                    <div class="input-group mb-2">
                        <input type="text" name="server_ip" class="form-control form-control-sm" value="{{ old('server_ip', $serverIp) }}" placeholder="Ej. 100.115.149.91:8080 o dominio">
                        <button type="submit" class="btn btn-sm btn-primary">Sincronizar Licencia</button>
                    </div>
                    <small class="text-muted d-block text-start" style="font-size: 11px;">
                        Si el administrador ya renovó su licencia en el panel, presione este botón para activarla en 1-clic.
                    </small>
                </form>
            </div>
        </div>

        <!-- Option 2: Manual Code -->
        <form action="{{ route('license.activate') }}" method="POST">
            @csrf
            <div class="mb-3 text-start">
                <label for="license_key" class="form-label font-semibold text-muted small">O ingrese su Código de Activación Manual:</label>
                <textarea class="form-control" id="license_key" name="license_key" rows="3" required placeholder="Pegue aquí su licencia..."></textarea>
            </div>
            <button type="submit" class="btn btn-outline-secondary w-100">Activar Licencia Manualmente</button>
        </form>
    </div>

</body>
</html>
