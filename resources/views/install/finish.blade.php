@extends('install.layout')

@section('content')
    <div class="text-center py-5">
        <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
        <h2 class="text-success">¡Instalación Completada!</h2>
        <p class="lead mt-3">El sistema ha sido configurado correctamente.</p>
        
        <div class="mt-5">
            <a href="{{ route('install.downloadShortcut') }}" class="btn btn-outline-dark btn-lg px-4 me-3">
                <i class="fab fa-windows me-2"></i> Crear Acceso Directo
            </a>

            <a href="{{ url('/') }}" class="btn btn-primary btn-lg px-5">
                Ir al Sistema <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
@endsection
