<div>
    <style>
        .update-hero-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            transition: box-shadow 0.2s ease;
        }
        .update-hero-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
        .markdown-body {
            color: #334155;
            font-size: 13.5px;
            line-height: 1.65;
        }
        .markdown-body h1, .markdown-body h2, .markdown-body h3, .markdown-body h4 {
            color: #1e293b;
            font-weight: 700;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            font-size: 1.05rem;
        }
        .markdown-body h1:first-child, .markdown-body h2:first-child, .markdown-body h3:first-child {
            margin-top: 0;
        }
        .markdown-body ul {
            padding-left: 1.4rem;
            margin-bottom: 0.75rem;
        }
        .markdown-body li {
            margin-bottom: 0.35rem;
        }
        .markdown-body code {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 88%;
            border: 1px solid #e2e8f0;
        }
        .markdown-body strong {
            color: #0f172a;
        }
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
            <div class="widget-content-area br-4 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff;">
                <div class="widget-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="mb-0 font-weight-bold text-dark d-flex align-items-center">
                            <i class="fas fa-cloud-upload-alt text-primary me-2" style="font-size: 1.35rem; color: #2b78b8 !important;"></i>
                            Sistema de Actualizaciones
                        </h4>
                        <small class="text-muted">Mantén tu sistema al día con las últimas funciones, seguridad y mejoras de rendimiento.</small>
                    </div>
                    <div>
                        <span class="badge shadow-sm" style="background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; padding: 7px 14px; border-radius: 8px; font-weight: 600; font-size: 13px;">
                            Versión Instalada: <strong class="ms-1" style="color: #2b78b8;">v{{ ltrim($currentVersion, 'v') }}</strong>
                        </span>
                    </div>
                </div>

                <div class="widget-content p-4">
                    <div class="text-center mt-2 mb-4">
                        @if($status === 'checking')
                            <div class="p-4 rounded-3 text-center my-3 shadow-sm" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="spinner-border mb-2" role="status" style="width: 2.2rem; height: 2.2rem; color: #2b78b8;"></div>
                                <div class="text-dark font-weight-bold">Buscando actualizaciones en la nube...</div>
                                <small class="text-muted">Conectando de forma segura con los servidores de versiones.</small>
                            </div>
                        @elseif($status === 'up_to_date')
                            <div class="p-4 rounded-3 text-center my-3 shadow-sm" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; border-radius: 50%; background: #dcfce7; color: #16a34a; font-size: 24px;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h5 class="text-dark font-weight-bold mb-1">¡Tu sistema está completamente actualizado!</h5>
                                <p class="text-muted mb-0 small">Estás disfrutando de la versión más reciente (v{{ ltrim($currentVersion, 'v') }}).</p>
                            </div>
                        @elseif($status === 'available')
                            <!-- Tarjeta Moderna de Actualización Disponible (Estilo SaaS Hero) -->
                            <div class="update-hero-card mb-4 text-start shadow-sm">
                                <div class="p-4" style="background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); border-bottom: 1px solid #e2e8f0;">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 54px; height: 54px; border-radius: 12px; background: #ffffff; color: #2b78b8; font-size: 24px; border: 1px solid #e2e8f0;">
                                                <i class="fas fa-rocket"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge" style="background-color: #2b78b8; color: #ffffff; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; padding: 4px 10px; border-radius: 20px;">
                                                        <i class="fas fa-sparkles me-1"></i> Nueva Versión
                                                    </span>
                                                    <span class="text-muted small">Lista para instalar</span>
                                                </div>
                                                <h3 class="mb-0 font-weight-bold" style="color: #1e293b; font-size: 1.45rem;">
                                                    Versión <span style="color: #2b78b8;">v{{ ltrim($newVersion, 'v') }}</span>
                                                </h3>
                                            </div>
                                        </div>
                                        <div>
                                            <button type="button" id="btnActualizarAhora" data-version="{{ $newVersion }}" class="btn btn-lg px-4 py-2 font-weight-bold shadow-sm" style="background: linear-gradient(135deg, #2b78b8 0%, #1e5c8e 100%); color: #ffffff; border: none; border-radius: 8px; font-size: 0.95rem;">
                                                <i class="fas fa-cloud-download-alt me-2"></i> Actualizar Ahora
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body p-4 text-start">
                                    <h6 class="font-weight-bold text-muted text-uppercase mb-3" style="font-size: 12px; letter-spacing: 0.5px;">
                                        <i class="fas fa-clipboard-list me-1" style="color: #2b78b8;"></i> Cambios y Novedades de esta Versión:
                                    </h6>
                                    <div class="p-3 rounded-3 custom-scroll" style="max-height: 250px; overflow-y: auto; background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                        <div class="markdown-body">
                                            {!! \Illuminate\Support\Str::markdown($releaseBody) !!}
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 pt-3 text-muted small border-top gap-2">
                                        <span><i class="fas fa-shield-alt text-success me-1"></i> Respaldo preventivo automático incluido antes de instalar.</span>
                                        <span><i class="fas fa-history text-primary me-1"></i> Restauración rápida disponible en caso de rollback.</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($status === 'updating')
                            <div class="p-4 rounded-3 text-center my-3 shadow-sm" style="background: #f0f9ff; border: 1px solid #bae6fd;">
                                <div class="spinner-border mb-2" role="status" style="width: 2.2rem; height: 2.2rem; color: #2b78b8;"></div>
                                <h5 class="font-weight-bold mb-1" style="color: #0369a1;">Actualización en curso...</h5>
                                <p class="text-muted mb-0 small">Por favor, no cierres esta ventana ni interrumpas el servidor. La consola de operaciones en vivo está activa en el modal emergente.</p>
                            </div>
                        @elseif($status === 'done')
                            <div class="p-4 rounded-3 text-center my-3 shadow-sm" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; border-radius: 50%; background: #dcfce7; color: #16a34a; font-size: 24px;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h5 class="text-dark font-weight-bold mb-1">¡Actualización Completada!</h5>
                                <p class="mb-3 text-muted small">El sistema se actualizó exitosamente.</p>
                                <button type="button" class="btn px-4 font-weight-bold text-white shadow-sm" style="background-color: #2b78b8; border-radius: 8px;" onclick="window.location.reload()">
                                    <i class="fas fa-sync-alt me-2"></i> Recargar Sistema
                                </button>
                            </div>
                        @elseif($status === 'error')
                            <div class="p-4 rounded-3 text-start my-3 shadow-sm" style="background: #fef2f2; border: 1px solid #fecaca;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-exclamation-circle text-danger me-2" style="font-size: 1.4rem;"></i>
                                    <h5 class="text-danger font-weight-bold mb-0">No se pudo completar la comprobación</h5>
                                </div>
                                <p class="text-muted mb-0 small">{{ $errors->first('update') ?: 'Ocurrió un error al contactar el servidor de actualizaciones.' }}</p>
                            </div>
                        @endif

                        @if(!in_array($status, ['backing_up', 'downloading', 'updating', 'available']))
                            <button wire:click="checkUpdate" class="btn px-4 py-2 font-weight-bold text-white shadow-sm" style="background-color: #2b78b8; border-radius: 8px;" wire:loading.attr="disabled">
                                <i class="fas fa-sync-alt me-2"></i> Buscar Actualizaciones
                            </button>
                        @endif
                    </div>

                    <!-- Manual ZIP Upload Section -->
                    @if(!in_array($status, ['updating']))
                    <div class="card mt-4 shadow-sm border-0" style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                        <div class="card-header bg-light border-bottom py-3 px-4">
                            <h5 class="card-title m-0 text-dark font-weight-bold d-flex align-items-center">
                                <i class="fas fa-file-archive me-2 text-secondary"></i> Actualización Manual mediante Archivo (.ZIP)
                            </h5>
                        </div>
                        <div class="card-body p-4 text-start">
                            <p class="text-muted small mb-3">
                                Útil cuando el internet del cliente es muy lento o no puede conectar a GitHub: Selecciona el archivo de actualización <strong>.zip</strong> (desde USB o disco local) para instalarlo de forma instantánea.
                            </p>
                            <form wire:submit.prevent="processManualZip">
                                <div class="row align-items-center">
                                    <div class="col-md-8 mb-2">
                                        <input type="file" wire:model.live="manualZip" class="form-control" accept=".zip">
                                        <div wire:loading wire:target="manualZip" class="text-primary small mt-1 font-weight-bold">
                                            <i class="fas fa-spinner fa-spin me-1"></i> Cargando paquete ZIP al servidor... por favor espere.
                                        </div>
                                        @error('manualZip') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button type="submit" class="btn btn-secondary px-4 w-100" wire:loading.attr="disabled" wire:target="manualZip, processManualZip" @if(!$manualZip) disabled @endif>
                                            <i class="fas fa-upload me-2"></i> Instalar ZIP Manual
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if($currentReleaseNotes && !in_array($status, ['updating']))
                    <div class="card mt-4 shadow-sm border-0" style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                        <div class="card-header bg-light border-bottom py-3 px-4">
                            <h5 class="card-title m-0 text-dark font-weight-bold d-flex align-items-center">
                                <i class="fas fa-list-alt me-2" style="color: #2b78b8;"></i> Novedades de la Versión v{{ ltrim($currentVersion, 'v') }}
                            </h5>
                        </div>
                        <div class="card-body text-start p-4 custom-scroll" style="max-height: 300px; overflow-y: auto; background-color: #f8fafc;">
                            <div class="markdown-body">
                                {!! \Illuminate\Support\Str::markdown($currentReleaseNotes) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if(is_array($rollbacks) && count($rollbacks) > 0 && !in_array($status, ['updating']))
                    <div class="card mt-4 shadow-sm border-0" style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                        <div class="card-header bg-light border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title m-0 text-dark font-weight-bold d-flex align-items-center">
                                <i class="fas fa-history me-2" style="color: #2b78b8;"></i> Puntos de Restauración (Rollback)
                            </h5>
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 600; padding: 5px 12px; border-radius: 20px;">
                                Máx. 3 copias
                            </span>
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
                                                    class="btn btn-sm me-2 font-weight-bold" 
                                                    style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; border-radius: 6px;"
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
                                                    style="border-radius: 6px;"
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
                    <div class="card mt-4 shadow-sm border-0" style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                        <div class="card-header bg-light border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title m-0 text-dark font-weight-bold d-flex align-items-center">
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

                <!-- Modal de Actualización de Software en Curso (Estilo Villasol) -->
                <div wire:ignore class="modal fade" id="modalActualizar" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 6px; overflow: hidden;">
                            <!-- Header con fondo azul y nube (Imagen 1 y 2) -->
                            <div class="modal-header text-white py-3 px-4" style="background-color: #2b78b8 !important; border-bottom: none;">
                                <h5 class="modal-title font-weight-bold d-flex align-items-center mb-0 text-white" style="font-size: 1.15rem; letter-spacing: 0.2px;">
                                    <i class="fas fa-cloud me-2 text-white" style="font-size: 1.25rem; margin-right: 10px;"></i>
                                    Actualización de Software en Curso
                                </h5>
                            </div>
                            
                            <div class="modal-body p-4" style="background-color: #ffffff;">
                                <!-- 1. Pantalla de Progreso y Consola en Vivo (Imagen 1) -->
                                <div id="modalProgreso">
                                    <div class="d-flex align-items-center mb-3">
                                        <h5 id="txtPasoActual" class="font-weight-bold mb-0 d-flex align-items-center" style="color: #2b78b8 !important; font-size: 1.1rem;">
                                            <i id="iconoPasoSpinner" class="fas fa-circle-notch fa-spin me-2" style="margin-right: 8px; font-size: 1.2rem;"></i>
                                            <span id="textoPasoSpinner">Iniciando actualización...</span>
                                        </h5>
                                    </div>

                                    <!-- Barra de Carga Animada -->
                                    <div class="progress mb-3" style="height: 28px; border-radius: 4px; background-color: #f0f2f5; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); overflow: hidden;">
                                        <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 10%; font-weight: bold; font-size: 13px; line-height: 28px; background-color: #2b78b8 !important; transition: width 0.4s ease;" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                                            10%
                                        </div>
                                    </div>

                                    <!-- Terminal estilo consola -->
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted font-weight-bold small">
                                            &gt;_ Registro de Operaciones:
                                        </span>
                                        <span id="badgeEstadoTerminal" class="badge badge-light border text-muted small px-2 py-1">En ejecución</span>
                                    </div>
                                    <div id="consolaPasos" style="background: #1e1e1e; color: #a9b7c6; font-family: 'Consolas', 'Courier New', monospace; font-size: 13px; padding: 14px; border-radius: 5px; height: 180px; overflow-y: auto; line-height: 1.6; border: 1px solid #333; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);">
                                        <!-- Líneas dinámicas generadas vía JS -->
                                    </div>
                                </div>

                                <!-- 2. Pantalla de Éxito Final (Imagen 2) -->
                                <div id="modalExito" style="display: none; text-align: center; padding: 25px 15px 15px 15px;">
                                    <div class="mb-3">
                                        <span class="d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 50%; background-color: #e8f5e9;">
                                            <i class="fas fa-check-circle text-success" style="font-size: 55px; color: #28a745 !important;"></i>
                                        </span>
                                    </div>
                                    <h3 class="text-dark font-weight-bold mb-2" style="font-size: 1.6rem;">¡Actualización Completada Exitosamente!</h3>
                                    <p id="txtDetalleExito" class="text-muted mb-4" style="font-size: 1.05rem;">
                                        ¡Sistema actualizado con éxito a la versión <strong class="text-dark">{{ $newVersion ?? $currentVersion }}</strong>!
                                    </p>
                                    
                                    <div class="mt-4 pt-2">
                                        <button type="button" id="btnRecargar" class="btn btn-primary btn-block btn-lg font-weight-bold py-2 shadow-sm text-white" style="background-color: #2b78b8; border-color: #2b78b8; font-size: 1.1rem; border-radius: 4px;" onclick="window.location.reload()">
                                            <i class="fas fa-sync-alt me-2" style="margin-right: 8px;"></i> Recargar Sistema
                                        </button>
                                    </div>
                                </div>

                                <!-- 3. Pantalla de Error (en caso de fallo) -->
                                <div id="modalError" style="display: none; text-align: center; padding: 20px 15px;">
                                    <div class="mb-3">
                                        <span class="d-inline-flex align-items-center justify-content-center" style="width: 75px; height: 75px; border-radius: 50%; background-color: #ffebee;">
                                            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 50px;"></i>
                                        </span>
                                    </div>
                                    <h4 class="text-danger font-weight-bold mb-2">Ocurrió un Problema</h4>
                                    <p id="txtDetalleError" class="text-muted small mb-3"></p>
                                    <div class="text-start mb-3">
                                        <span class="text-muted font-weight-bold small">&gt;_ Registro del error:</span>
                                        <div id="consolaError" style="background: #1e1e1e; color: #f44747; font-family: monospace; font-size: 12px; padding: 10px; border-radius: 4px; max-height: 120px; overflow-y: auto;"></div>
                                    </div>
                                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">
                                        <i class="fas fa-times me-1"></i> Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@push('my-scripts')
<script>
    (function() {
        function initUpdateSystem() {
            var $ = window.jQuery || window.$;
            if (!$) {
                setTimeout(initUpdateSystem, 50);
                return;
            }

            // Move modal to body to guarantee clean Bootstrap backdrop behavior
            if ($('#modalActualizar').length && $('#modalActualizar').parent().get(0) !== document.body) {
                $('#modalActualizar').appendTo('body');
            }

            function abrirModalActualizacion(titulo) {
                var $ = window.jQuery || window.$;
                titulo = titulo || 'Iniciando actualización...';
                $('#modalProgreso').show();
                $('#modalExito').hide();
                $('#modalError').hide();
                $('#consolaPasos').empty();
                $('#consolaError').empty();
                $('#badgeEstadoTerminal').removeClass('badge-danger badge-success text-white').addClass('badge-light text-muted').text('En ejecución');
                $('#barraProgreso').removeClass('bg-success bg-danger bg-warning').css({'width': '10%', 'background-color': '#2b78b8'}).text('10%');
                $('#textoPasoSpinner').text(titulo);
                $('#iconoPasoSpinner').show();
                $('#modalActualizar').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });
            }

            function agregarLog(texto, color) {
                var $ = window.jQuery || window.$;
                color = color || '#a9b7c6';
                var now = new Date();
                var hora = now.toTimeString().split(' ')[0]; // HH:MM:SS
                $('#consolaPasos').append('<div style="color: ' + color + '; margin-bottom: 3px;">[' + hora + '] ' + texto + '</div>');
                var d = $('#consolaPasos');
                d.scrollTop(d.prop("scrollHeight"));
            }

            function updateProgreso(percent, textoSpinner, logTexto, logColor, colorBarra) {
                var $ = window.jQuery || window.$;
                if (percent !== null && percent !== undefined) {
                    $('#barraProgreso').css('width', percent + '%').text(percent + '%');
                    if (colorBarra) {
                        $('#barraProgreso').removeClass('bg-primary bg-warning bg-danger bg-success');
                        if (colorBarra === 'bg-success') {
                            $('#barraProgreso').css('background-color', '#28a745');
                        } else if (colorBarra === 'bg-danger') {
                            $('#barraProgreso').css('background-color', '#dc3545');
                        } else {
                            $('#barraProgreso').css('background-color', '#2b78b8');
                        }
                    }
                }
                if (textoSpinner) {
                    $('#textoPasoSpinner').text(textoSpinner);
                }
                if (logTexto) {
                    agregarLog(logTexto, logColor);
                }
            }

            function mostrarErrorActualizacion(errMsg) {
                window.actualizacionEnCurso = false;
                var $ = window.jQuery || window.$;
                updateProgreso(null, 'Error en el proceso', '✗ ERROR: ' + errMsg, '#f44747', 'bg-danger');
                $('#badgeEstadoTerminal').removeClass('badge-light text-muted').addClass('badge-danger text-white').text('Fallido');
                $('#iconoPasoSpinner').hide();
                $('#txtDetalleError').text(errMsg);
                $('#consolaError').text(errMsg);

                setTimeout(function() {
                    $('#modalProgreso').fadeOut(300, function() {
                        $('#modalError').fadeIn(300);
                    });
                }, 1500);
            }

            window.ejecutarActualizacionAJAX = function(version) {
                var $ = window.jQuery || window.$;
                if (!$) {
                    alert("El sistema aún está cargando librerías, por favor intente en unos segundos...");
                    return;
                }
                if (window.actualizacionEnCurso) {
                    return;
                }
                window.actualizacionEnCurso = true;

                abrirModalActualizacion('Iniciando actualización...');
                agregarLog('Iniciando proceso de actualización del sistema...', '#569cd6');
                agregarLog('Paso 1/5: Generando respaldo preventivo de base de datos...');
                updateProgreso(20, 'Generando respaldo preventivo...');

                var timer1 = setTimeout(function() {
                    updateProgreso(45, 'Descargando paquete desde GitHub...', 'Paso 2/5: Descargando archivos actualizados desde GitHub...');
                }, 1800);

                var timer2 = setTimeout(function() {
                    updateProgreso(70, 'Instalando archivos en el servidor...', 'Paso 3/5: Descomprimiendo e instalando archivos en el servidor...');
                }, 3800);

                $.ajax({
                    url: "{{ url('/system/update/apply') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        version: version
                    },
                    dataType: 'json',
                    timeout: 600000,
                    success: function(res) {
                        clearTimeout(timer1);
                        clearTimeout(timer2);

                        if (res.success) {
                            updateProgreso(85, 'Actualizando base de datos...', 'Paso 4/5: Verificando y aplicando migraciones de base de datos...');
                            
                            setTimeout(function() {
                                updateProgreso(95, 'Limpiando cachés...', 'Paso 5/5: Limpiando cachés de Laravel (vistas, rutas, configuración)...');
                                
                                setTimeout(function() {
                                    var verFinal = res.new_version || version || '{{ $newVersion }}';
                                    updateProgreso(100, '¡Actualización completada!', '✓ ' + (res.message || '¡Sistema actualizado con éxito a la versión ' + verFinal + '!'), '#4ec9b0', 'bg-success');
                                    $('#badgeEstadoTerminal').removeClass('badge-light text-muted').addClass('badge-success text-white').text('Completado');
                                    
                                    setTimeout(function() {
                                        $('#modalProgreso').fadeOut(300, function() {
                                            $('#txtDetalleExito').html('¡Sistema actualizado con éxito a la versión <strong class="text-dark">' + verFinal + '</strong>!');
                                            $('#modalExito').fadeIn(300);
                                        });
                                    }, 1200);
                                }, 500);
                            }, 500);
                        } else {
                            mostrarErrorActualizacion(res.message || 'Error desconocido durante la actualización');
                        }
                    },
                    error: function(xhr, status, error) {
                        clearTimeout(timer1);
                        clearTimeout(timer2);
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (error || 'Error de conexión con el servidor');
                        mostrarErrorActualizacion(msg);
                    }
                });
            };

            window.ejecutarRollbackAJAX = function(folder, version) {
                var $ = window.jQuery || window.$;
                abrirModalActualizacion('Iniciando restauración a v' + version + '...');
                agregarLog('Iniciando proceso de restauración del sistema...', '#569cd6');
                agregarLog('Paso 1/2: Restaurando código fuente y base de datos respaldada...');
                updateProgreso(35, 'Restaurando código y base de datos...');

                var timer = setTimeout(function() {
                    updateProgreso(65, 'Extrayendo archivos y aplicando base de datos...');
                }, 1800);

                $.ajax({
                    url: "{{ url('/system/update/rollback') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        folder: folder,
                        version: version
                    },
                    dataType: 'json',
                    timeout: 600000,
                    success: function(res) {
                        clearTimeout(timer);
                        if (res.success) {
                            updateProgreso(90, 'Limpiando cachés...', 'Paso 2/2: Limpiando cachés del sistema...');
                            setTimeout(function() {
                                updateProgreso(100, '¡Restauración completada!', '✓ ' + (res.message || '¡Sistema restaurado correctamente a la versión anterior!'), '#4ec9b0', 'bg-success');
                                $('#badgeEstadoTerminal').removeClass('badge-light text-muted').addClass('badge-success text-white').text('Restaurado');

                                setTimeout(function() {
                                    $('#modalProgreso').fadeOut(300, function() {
                                        $('#txtDetalleExito').html('¡Sistema restaurado con éxito a la versión <strong class="text-dark">v' + version + '</strong>!');
                                        $('#modalExito').fadeIn(300);
                                    });
                                }, 1200);
                            }, 500);
                        } else {
                            mostrarErrorActualizacion(res.message || 'Error durante la restauración');
                        }
                    },
                    error: function(xhr, status, error) {
                        clearTimeout(timer);
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (error || 'Error de conexión con el servidor');
                        mostrarErrorActualizacion(msg);
                    }
                });
            };

            // Escuchar clics directos sobre el botón
            $(document).off('click', '#btnActualizarAhora').on('click', '#btnActualizarAhora', function(e) {
                e.preventDefault();
                var ver = $(this).attr('data-version') || '{{ $newVersion }}';
                window.ejecutarActualizacionAJAX(ver);
            });

            // Escucha evento cuando se sube un ZIP manual
            if (window.Livewire) {
                Livewire.on('start-zip-apply', function(event) {
                    var ver = (event && event.version) ? event.version : 'Manual (ZIP)';
                    window.ejecutarActualizacionAJAX(ver);
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initUpdateSystem);
        } else {
            initUpdateSystem();
        }
        document.addEventListener('livewire:init', initUpdateSystem);
        document.addEventListener('livewire:navigated', initUpdateSystem);
    })();

    // Diálogos de Confirmación SweetAlert v1
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
                window.ejecutarRollbackAJAX(folder, version);
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
</script>
@endpush
