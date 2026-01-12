@extends('install.layout')

@section('content')
    <h4 class="mb-4">Paso 2: Configuración de Base de Datos</h4>

    <form action="{{ route('install.saveDatabase') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Host</label>
                <input type="text" name="db_host" class="form-control" value="127.0.0.1" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Puerto</label>
                <input type="text" name="db_port" class="form-control" value="3306" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Base de Datos</label>
                <input type="text" name="db_database" class="form-control" placeholder="nombre_bd" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="db_username" class="form-control" value="root" required>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="db_password" class="form-control">
            </div>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary">
                Guardar y Continuar <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>
@endsection
