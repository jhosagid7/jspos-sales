@extends('install.layout')

@section('content')
    <h4 class="mb-4">Paso 1: Requisitos del Servidor</h4>

    <div class="list-group mb-4">
        @foreach($requirements as $label => $met)
            <div class="list-group-item d-flex justify-content-between align-items-center">
                {{ $label }}
                @if($met)
                    <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i> OK</span>
                @else
                    <span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i> Error</span>
                @endif
            </div>
        @endforeach
    </div>

    <div class="text-end">
        @if($allMet)
            <a href="{{ route('install.step2') }}" class="btn btn-primary">
                Siguiente <i class="fas fa-arrow-right"></i>
            </a>
        @else
            <button class="btn btn-secondary" disabled>
                Corrija los errores para continuar
            </button>
            <a href="{{ route('install.step1') }}" class="btn btn-outline-primary ms-2">
                <i class="fas fa-sync"></i> Reintentar
            </a>
        @endif
    </div>
@endsection
