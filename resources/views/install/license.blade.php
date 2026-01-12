@extends('install.layout')

@section('content')
    <h4 class="mb-4">Paso 4: Activación de Licencia</h4>

    <div class="alert alert-info">
        <p class="mb-1">Por favor, envíe el siguiente ID a su proveedor para obtener su licencia:</p>
        <h3 class="text-center my-3 font-monospace user-select-all bg-white p-2 rounded border">{{ $clientId }}</h3>
    </div>

    <form action="{{ route('install.activateLicense') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Pegue su Código de Licencia aquí:</label>
            <textarea name="license_key" class="form-control" rows="5" required placeholder="Pegue el código largo aquí..."></textarea>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary">
                Activar y Continuar <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>
@endsection
