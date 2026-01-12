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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .license-card {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: white;
        }
        .client-id-box {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 1.2rem;
            text-align: center;
            margin: 1rem 0;
            user-select: all;
        }
    </style>
</head>
<body>

    <div class="license-card text-center">
        <div class="mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#dc3545" class="bi bi-lock-fill" viewBox="0 0 16 16">
                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
            </svg>
        </div>
        
        <h2 class="text-danger mb-3">Sistema Bloqueado</h2>
        <p class="text-muted">Su licencia ha expirado o no es válida. Por favor, contacte al administrador para renovar su suscripción.</p>

        <div class="alert alert-info">
            <small>Envíe este ID a su proveedor:</small>
            <div class="client-id-box">{{ $clientId }}</div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('license.activate') }}" method="POST">
            @csrf
            <div class="mb-3 text-start">
                <label for="license_key" class="form-label">Ingrese su Código de Activación</label>
                <textarea class="form-control" id="license_key" name="license_key" rows="4" required placeholder="Pegue aquí su licencia..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Activar Licencia</button>
        </form>
    </div>

</body>
</html>
