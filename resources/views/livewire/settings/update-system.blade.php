<div>
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
            <div class="widget-content-area br-4">
                <div class="widget-header">
                    <div class="row">
                        <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                            <h4>Sistema de Actualizaciones</h4>
                        </div>
                    </div>
                </div>

                <div class="widget-content widget-content-area">
                    <div class="text-center mt-4 mb-4">
                        <h5 class="mb-3">Versión Actual: <span class="badge badge-info">{{ $currentVersion }}</span></h5>

                        @if($status === 'checking')
                            <div class="alert alert-info">
                                <i class="fas fa-spinner fa-spin me-2"></i> Buscando actualizaciones...
                            </div>
                        @elseif($status === 'up_to_date')
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> El sistema está actualizado.
                            </div>
                        @elseif($status === 'available')
                            <div class="alert alert-warning">
                                <h4><i class="fas fa-gift me-2"></i> ¡Nueva Versión Disponible!</h4>
                                <p class="mt-2">Versión: <strong>{{ $newVersion }}</strong></p>
                                <hr>
                                <div class="text-start" style="max-height: 200px; overflow-y: auto;">
                                    {!! nl2br(e($releaseBody)) !!}
                                </div>
                                <hr>
                                <button wire:click="update" class="btn btn-success btn-lg mt-3" wire:loading.attr="disabled">
                                    <i class="fas fa-cloud-download-alt me-2"></i> Actualizar Ahora
                                </button>
                            </div>
                        @elseif(in_array($status, ['backing_up', 'downloading', 'updating']))
                            <div class="alert alert-primary">
                                <h4 class="alert-heading"><i class="fas fa-cog fa-spin me-2"></i> Actualizando Sistema...</h4>
                                <p class="mb-0">Por favor espere, este proceso puede tardar unos minutos.</p>
                                <hr>
                                <p class="mb-0">
                                    @if($status === 'backing_up') Creando copia de seguridad...
                                    @elseif($status === 'downloading') Descargando archivos...
                                    @elseif($status === 'updating') Instalando actualización...
                                    @endif
                                </p>
                            </div>
                        @elseif($status === 'done')
                            <div class="alert alert-success">
                                <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i> ¡Actualización Exitosa!</h4>
                                <p>El sistema se ha actualizado correctamente a la versión {{ $newVersion }}.</p>
                            </div>
                        @elseif($status === 'error')
                            <div class="alert alert-danger">
                                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Error</h4>
                                <p>{{ $errors->first('update') }}</p>
                            </div>
                        @endif

                        @if(!in_array($status, ['backing_up', 'downloading', 'updating', 'available']))
                            <button wire:click="checkUpdate" class="btn btn-primary mt-3" wire:loading.attr="disabled">
                                <i class="fas fa-sync-alt me-2"></i> Buscar Actualizaciones
                            </button>
                        @endif
                    </div>

                    @if($currentReleaseNotes)
                    <div class="card mt-4 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title m-0"><i class="fas fa-list-alt me-2 text-primary"></i> Novedades de la Versión {{ $currentVersion }}</h5>
                        </div>
                        <div class="card-body text-start" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                            <div class="markdown-body">
                                {!! \Illuminate\Support\Str::markdown($currentReleaseNotes) !!}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
