@extends('layouts.theme.app-simple')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-danger text-white text-center">
                    <h3 class="font-weight-light my-2"><i class="fas fa-lock me-2"></i> Acceso Restringido</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-shield-alt fa-5x text-danger"></i>
                    </div>
                    <h4 class="mb-3">Este dispositivo no está autorizado</h4>
                    <p class="lead">
                        El administrador ha restringido el acceso al sistema solo a dispositivos aprobados.
                    </p>
                    <div class="alert alert-warning d-inline-block text-left">
                        <strong><i class="fas fa-info-circle"></i> Información del Dispositivo:</strong><br>
                        <small>ID: {{ request()->device_uuid }}</small><br>
                        <small>IP: {{ request()->ip() }}</small>
                    </div>
                    <p class="mt-4">
                        Por favor, contacte al administrador y proporcione el ID de arriba para que su dispositivo sea aprobado.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i> Intentar Nuevamente
                        </a>
                    </div>
                </div>
                <div class="card-footer text-center py-3">
                    <div class="small text-muted">Sistema de Seguridad JSPOS</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
