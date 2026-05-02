@extends('install.layout')

@section('content')
    <h4 class="mb-4">Paso 3: Estructura y Datos Maestros</h4>

    <div class="text-center py-5">
        <div class="mb-4">
            <span class="fa-stack fa-3x">
                <i class="fas fa-database fa-stack-1x text-primary"></i>
                <i class="fas fa-cog fa-stack-2x fa-spin text-secondary" style="opacity: 0.3"></i>
            </span>
        </div>
        
        <p class="lead">El sistema desplegará la arquitectura de tablas y los <strong>Datos Maestros</strong> profesionales.</p>
        
        <div class="card bg-light border-0 mb-4 mx-auto" style="max-width: 500px;">
            <div class="card-body text-start small">
                <ul class="mb-0">
                    <li>Creación de esquemas y relaciones.</li>
                    <li>Carga de Permisos y Roles jerárquicos.</li>
                    <li>Configuración base de Monedas y Bancos.</li>
                    <li>Inicialización de Catálogos (Sin datos transaccionales).</li>
                </ul>
            </div>
        </div>

        <form action="{{ route('install.runMigrations') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-lg btn-success mt-2 px-5">
                <i class="fas fa-rocket me-2"></i> Iniciar Despliegue Profesional
            </button>
        </form>
        
        <p class="text-muted mt-3 small"><i class="fas fa-info-circle"></i> Esta acción reseteará cualquier tabla existente en la base de datos seleccionada.</p>
    </div>
@endsection
