@extends('install.layout')

@section('content')
    <h4 class="mb-3"><i class="fas fa-user-shield me-2 text-primary"></i>Paso 5: Crear Administrador del Negocio</h4>
    <p class="text-muted">Cree la cuenta principal para administrar este negocio.</p>

    <form action="{{ route('install.createAdmin') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-bold">Nombre del Dueño / Administrador</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name') }}" placeholder="Ej: Juan Pérez">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Correo Electrónico</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" required value="{{ old('email') }}" placeholder="admin@negocio.com">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Contraseña</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" placeholder="Mínimo 8 caracteres">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Repita la contraseña">
            </div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-check-circle me-1"></i> Finalizar Instalación
            </button>
        </div>
    </form>
@endsection
