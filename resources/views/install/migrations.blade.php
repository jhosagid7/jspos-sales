@extends('install.layout')

@section('content')
    <h4 class="mb-4">Paso 3: Instalación de Base de Datos</h4>

    <div class="text-center py-5">
        <i class="fas fa-database fa-4x text-primary mb-3"></i>
        <p class="lead">El sistema está listo para instalar las tablas y datos iniciales.</p>
        <p class="text-muted">Esto puede tardar unos segundos.</p>

        <form action="{{ route('install.runMigrations') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-lg btn-success mt-3">
                <i class="fas fa-play"></i> Instalar Base de Datos
            </button>
        </form>
    </div>
@endsection
