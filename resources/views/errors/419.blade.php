@extends('layouts.theme.app-simple')

@section('title', __('Sesión Expirada'))

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-warning text-dark text-center">
                    <h3 class="font-weight-light my-2"><i class="fas fa-clock me-2"></i> Sesión Expirada</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-hourglass-end fa-5x text-warning"></i>
                    </div>
                    <h4 class="mb-3">Tu sesión ha finalizado</h4>
                    <p class="lead">
                        Por seguridad, tu sesión ha expirado debido a inactividad.
                    </p>
                    <p>
                        Serás redirigido al inicio de sesión en unos segundos...
                    </p>
                    
                    <div class="mt-4">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión Ahora
                        </a>
                    </div>
                </div>
                <div class="card-footer text-center py-3">
                    <div class="small text-muted">JSPOS Sales System</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto redirect after 3 seconds
    setTimeout(function(){
       window.location.href = "{{ route('login') }}";
    }, 3000);
</script>
@endsection
