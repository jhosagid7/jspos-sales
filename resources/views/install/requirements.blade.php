@extends('install.layout')

@section('content')
    <h4 class="mb-4">Paso 1: Requisitos y Detección de Entorno</h4>

    <div class="card mb-4 bg-light border-0 shadow-sm">
        <div class="card-body">
            <h6 class="text-muted mb-3"><i class="fas fa-microchip"></i> Entorno de Instalación Detectado</h6>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>Método detectado:</span>
                <span class="badge bg-info text-white">{{ $installationMethod }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span>Dependencias (vendor):</span>
                @if($hasVendor)
                    <span class="badge bg-success"><i class="fas fa-check"></i> Presentes</span>
                @else
                    <span class="badge bg-danger"><i class="fas fa-times"></i> Faltantes</span>
                @endif
            </div>
        </div>
    </div>

    <h6 class="mb-3 text-muted"><i class="fas fa-list-check"></i> Requisitos del Servidor</h6>
    <div class="list-group mb-4 shadow-sm">
        @foreach($requirements as $label => $met)
            <div class="list-group-item d-flex justify-content-between align-items-center border-start-0 border-end-0">
                {{ $label }}
                @if($met)
                    <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i> OK</span>
                @else
                    <span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i> Error</span>
                @endif
            </div>
        @endforeach
    </div>

    @if(!$hasVendor)
        <div class="alert alert-warning shadow-sm border-0">
            <i class="fas fa-exclamation-triangle"></i> <strong>Atención:</strong> Las dependencias de Composer no han sido detectadas.
            Si instaló vía Git, asegúrese de ejecutar <code>composer install</code> antes de continuar.
        </div>
    @endif

    <div class="text-end mt-4">
        @if($allMet)
            <a href="{{ route('install.step2') }}" class="btn btn-primary px-4 py-2">
                Siguiente Paso <i class="fas fa-arrow-right ms-2"></i>
            </a>
        @else
            <button class="btn btn-secondary px-4 py-2" disabled>
                Incompatible
            </button>
            <a href="{{ route('install.step1') }}" class="btn btn-outline-primary ms-2 px-4 py-2">
                <i class="fas fa-sync me-2"></i> Reintentar
            </a>
        @endif
    </div>
@endsection
