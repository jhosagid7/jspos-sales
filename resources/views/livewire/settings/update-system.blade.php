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
                            <div class="alert alert-info border-0 shadow-sm">
                                <i class="fas fa-spinner fa-spin me-2 text-info"></i> Buscando actualizaciones...
                            </div>
                        @elseif($status === 'up_to_date')
                            <div class="alert alert-success border-0 shadow-sm">
                                <i class="fas fa-check-circle me-2 text-success"></i> El sistema está actualizado a la última versión.
                            </div>
                        @elseif($status === 'available')
                            <div class="alert alert-warning border-0 shadow-sm text-start p-4">
                                <h4 class="alert-heading text-warning font-weight-bold"><i class="fas fa-gift me-2"></i> ¡Nueva Versión Disponible!</h4>
                                <p class="mt-2">Versión: <strong class="text-dark">{{ $newVersion }}</strong></p>
                                <hr>
                                <div class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto; border: 1px solid #e0e0e0;">
                                    {!! nl2br(e($releaseBody)) !!}
                                </div>
                                <hr class="my-3">
                                <div class="text-center">
                                    <button wire:click="startUpdate" class="btn btn-success btn-lg px-4" wire:loading.attr="disabled">
                                        <i class="fas fa-cloud-download-alt me-2"></i> Actualizar Ahora
                                    </button>
                                </div>
                            </div>
                        @elseif($status === 'updating')
                            <div class="alert alert-primary border-0 shadow-sm p-4">
                                <h4 class="alert-heading text-primary font-weight-bold"><i class="fas fa-cog fa-spin me-2"></i> Procesando...</h4>
                                <p class="mb-0">Por favor, no cierre esta ventana ni interrumpa el servidor.</p>
                                <hr class="my-3">
                                <div class="progress br-30 mb-3 bg-white" style="height: 20px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                                    <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                        <span class="text-dark font-weight-bold">{{ $progress }}%</span>
                                    </div>
                                </div>
                                <p class="mb-0 text-center font-weight-bold text-dark">
                                    {{ $progressStatus }}
                                </p>
                            </div>
                        @elseif($status === 'done')
                            <div class="alert alert-success border-0 shadow-sm p-4">
                                <h4 class="alert-heading text-success font-weight-bold"><i class="fas fa-check-circle me-2"></i> ¡Acción Completada!</h4>
                                <p class="mb-0">La operación se completó exitosamente. El sistema se recargará en unos segundos.</p>
                            </div>
                        @elseif($status === 'error')
                            <div class="alert alert-danger border-0 shadow-sm p-4">
                                <h4 class="alert-heading text-danger font-weight-bold"><i class="fas fa-exclamation-triangle me-2"></i> Error</h4>
                                <p class="mb-0">{{ $errors->first('update') }}</p>
                            </div>
                        @endif

                        @if(!in_array($status, ['backing_up', 'downloading', 'updating', 'available']))
                            <button wire:click="checkUpdate" class="btn btn-primary px-4" wire:loading.attr="disabled">
                                <i class="fas fa-sync-alt me-2"></i> Buscar Actualizaciones
                            </button>
                        @endif
                    </div>

                    <!-- Manual ZIP Upload Section -->
                    @if(!in_array($status, ['updating']))
                    <div class="card mt-3 shadow-sm border-0">
                        <div class="card-header bg-light border-0 py-3">
                            <h5 class="card-title m-0 text-dark font-weight-bold">
                                <i class="fas fa-file-archive me-2 text-secondary"></i> Actualización Manual mediante Archivo (.ZIP)
                            </h5>
                        </div>
                        <div class="card-body text-start">
                            <p class="text-muted small mb-3">
                                Útil cuando el internet del cliente es muy lento o no puede conectar a GitHub: Selecciona el archivo de actualización <strong>.zip</strong> (desde USB o disco local) para instalarlo de forma instantánea.
                            </p>
                            <form wire:submit.prevent="processManualZip">
                                <div class="row align-items-center">
                                    <div class="col-md-8 mb-2">
                                        <input type="file" wire:model="manualZip" class="form-control" accept=".zip">
                                        @error('manualZip') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button type="submit" class="btn btn-secondary px-4 w-100" wire:loading.attr="disabled" @if(!$manualZip) disabled @endif>
                                            <i class="fas fa-upload me-2"></i> Instalar ZIP Manual
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if($currentReleaseNotes && !in_array($status, ['updating']))
                    <div class="card mt-4 shadow-sm border-0">
                        <div class="card-header bg-light border-0 py-3">
                            <h5 class="card-title m-0 text-dark font-weight-bold"><i class="fas fa-list-alt me-2 text-primary"></i> Novedades de la Versión v{{ $currentVersion }}</h5>
                        </div>
                        <div class="card-body text-start" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                            <div class="markdown-body">
                                {!! \Illuminate\Support\Str::markdown($currentReleaseNotes) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if(is_array($rollbacks) && count($rollbacks) > 0 && !in_array($status, ['updating']))
                    <div class="card mt-4 shadow-sm border-0">
                        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title m-0 text-dark font-weight-bold">
                                <i class="fas fa-history me-2 text-warning"></i> Puntos de Restauración (Rollback)
                            </h5>
                            <span class="badge badge-warning font-weight-bold">Máx. 3 copias</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 text-start align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0 px-4 py-3">Versión de Origen</th>
                                            <th class="border-0 px-4 py-3">Fecha de Respaldo</th>
                                            <th class="border-0 px-4 py-3">Tamaño Total</th>
                                            <th class="border-0 px-4 py-3 text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rollbacks as $rollback)
                                        <tr>
                                            <td class="px-4 py-3 font-weight-bold text-primary">
                                                v{{ $rollback['version'] }}
                                            </td>
                                            <td class="px-4 py-3 text-muted">
                                                {{ \Carbon\Carbon::parse($rollback['date'])->format('d/m/Y h:i A') }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="badge badge-outline-secondary">{{ $rollback['size'] }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-end">
                                                <button 
                                                    onclick="confirmRollback('{{ $rollback['folder'] }}', '{{ $rollback['version'] }}')"
                                                    class="btn btn-warning btn-sm me-2" 
                                                    wire:loading.attr="disabled"
                                                    title="Restaurar a esta versión"
                                                >
                                                    <i class="fas fa-undo-alt me-1"></i> Restaurar
                                                </button>
                                                <button 
                                                    onclick="confirmDeleteRollback('{{ $rollback['folder'] }}', '{{ $rollback['version'] }}')"
                                                    class="btn btn-danger btn-sm" 
                                                    wire:loading.attr="disabled"
                                                    title="Eliminar punto de restauración"
                                                >
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if(!in_array($status, ['updating']))
                    <div class="card mt-4 shadow-sm border-0">
                        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title m-0 text-dark font-weight-bold">
                                <i class="fas fa-terminal me-2 text-danger"></i> Bitácora de Errores (Logs)
                            </h5>
                            <div>
                                <button wire:click="loadLogs" class="btn btn-outline-primary btn-sm me-2" wire:loading.attr="disabled">
                                    <i class="fas fa-sync-alt me-1"></i> Cargar/Actualizar
                                </button>
                                <a href="{{ route('system.logs.download') }}" target="_blank" class="btn btn-outline-success btn-sm me-2">
                                    <i class="fas fa-download me-1"></i> Descargar Completo
                                </a>
                                <button onclick="confirmClearLogs()" class="btn btn-outline-danger btn-sm" wire:loading.attr="disabled">
                                    <i class="far fa-trash-alt me-1"></i> Limpiar Historial
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            @if(empty($logLines))
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-2"></i> Los registros de errores no se cargan por defecto para mayor velocidad. Haz clic en "Cargar/Actualizar" para verlos.
                                </div>
                            @else
                                <textarea readonly class="form-control text-white bg-dark p-3 rounded" style="font-size: 13px; line-height: 1.5; font-family: monospace; min-height: 300px; max-height: 500px; overflow-y: auto;">{{ $logLines }}</textarea>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <script>
                    document.addEventListener('livewire:initialized', () => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');

                        @this.on('run-backup', () => {
                            @this.call('runBackup');
                        });
                        @this.on('run-download', () => {
                            @this.call('download');
                        });
                        @this.on('run-install', () => {
                            @this.call('install');
                        });
                        @this.on('run-migrate', () => {
                            @this.call('migrate');
                        });
                        @this.on('run-cleanup', () => {
                            @this.call('cleanup');
                        });
                        @this.on('run-rollback', () => {
                            @this.call('runRollback');
                        });
                        @this.on('reload-page', () => {
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        });
                    });

                    // Confirmation Dialogs using SweetAlert (v1 - the version loaded in this project)
                    function confirmRollback(folder, version) {
                        swal({
                            title: '¿Restaurar sistema?',
                            text: 'El sistema revertirá el código y la base de datos a la versión v' + version + '. Se perderán las transacciones generadas después de este respaldo. Esta acción es IRREVERSIBLE.',
                            icon: 'warning',
                            buttons: {
                                cancel: {
                                    text: 'Cancelar',
                                    value: null,
                                    visible: true,
                                },
                                confirm: {
                                    text: 'Sí, restaurar',
                                    value: true,
                                    className: 'swal-button--danger',
                                }
                            },
                            dangerMode: true,
                        }).then(function(value) {
                            if (value) {
                                @this.call('rollbackToVersion', folder);
                            }
                        });
                    }

                    function confirmDeleteRollback(folder, version) {
                        swal({
                            title: '¿Eliminar punto de restauración?',
                            text: 'Se eliminarán permanentemente los archivos y base de datos respaldados para la versión v' + version + '.',
                            icon: 'warning',
                            buttons: {
                                cancel: {
                                    text: 'Cancelar',
                                    value: null,
                                    visible: true,
                                },
                                confirm: {
                                    text: 'Sí, eliminar',
                                    value: true,
                                    className: 'swal-button--danger',
                                }
                            },
                            dangerMode: true,
                        }).then(function(value) {
                            if (value) {
                                @this.call('deleteRollback', folder);
                            }
                        });
                    }

                    function confirmClearLogs() {
                        swal({
                            title: '¿Limpiar historial de errores?',
                            text: 'Se vaciará por completo el archivo laravel.log de este servidor. Esta acción liberará espacio en disco y no se puede deshacer.',
                            icon: 'warning',
                            buttons: {
                                cancel: {
                                    text: 'Cancelar',
                                    value: null,
                                    visible: true,
                                },
                                confirm: {
                                    text: 'Sí, limpiar',
                                    value: true,
                                    className: 'swal-button--danger',
                                }
                            },
                            dangerMode: true,
                        }).then(function(value) {
                            if (value) {
                                @this.call('clearLogs');
                            }
                        });
                    }

                    document.addEventListener('DOMContentLoaded', () => {
                        setTimeout(() => {
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open');
                        }, 500);
                    });
                </script>
            </div>
        </div>
    </div>
</div>
