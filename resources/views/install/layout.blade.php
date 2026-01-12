<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-card {
            max-width: 800px;
            width: 100%;
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        .install-header {
            background: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .install-body {
            background: white;
            padding: 30px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }
        .step {
            width: 30px;
            height: 30px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            font-weight: bold;
            color: #6c757d;
        }
        .step.active {
            background: #007bff;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>

    <div class="install-card">
        <div class="install-header">
            <h3><i class="fas fa-cogs"></i> Asistente de Instalación</h3>
        </div>
        <div class="install-body">
            <div class="step-indicator">
                <div class="step {{ request()->routeIs('install.step1') ? 'active' : (request()->routeIs('install.step1') ? '' : 'completed') }}">1</div>
                <div class="step {{ request()->routeIs('install.step2') ? 'active' : (request()->routeIs('install.step1') ? '' : (request()->routeIs('install.step2') ? '' : 'completed')) }}">2</div>
                <div class="step {{ request()->routeIs('install.step3') ? 'active' : (request()->routeIs('install.step1') || request()->routeIs('install.step2') ? '' : (request()->routeIs('install.step3') ? '' : 'completed')) }}">3</div>
                <div class="step {{ request()->routeIs('install.step4') ? 'active' : (request()->routeIs('install.step1') || request()->routeIs('install.step2') || request()->routeIs('install.step3') ? '' : (request()->routeIs('install.step4') ? '' : 'completed')) }}">4</div>
                <div class="step {{ request()->routeIs('install.step5') ? 'active' : 'completed' }}">5</div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>

</body>
</html>
