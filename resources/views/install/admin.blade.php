@extends('install.layout')

@section('content')
    <h4 class="mb-4">Paso 5: Crear Administrador del Negocio</h4>
    <p class="text-muted">Cree la cuenta principal para administrar este negocio.</p>

    <form action="{{ route('install.createAdmin') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nombre del Dueño/Administrador</label>
            <input type="text" name="name" class="form-control" required placeholder="Ej: Juan Pérez">
        </div>
        <div class="mb-3">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" name="email" class="form-control" required placeholder="admin@negocio.com">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required minlength="8">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="8">
            </div>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-check-circle"></i> Finalizar Instalación
            </button>
        </div>
    </form>
@endsection
