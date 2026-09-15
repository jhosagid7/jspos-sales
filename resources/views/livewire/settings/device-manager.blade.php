<div class="row layout-top-spacing">
    <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
        <div class="widget-content-area br-4">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>Gestión de Dispositivos</h4>
                    </div>
                </div>
            </div>

            <div class="widget-content widget-content-area">
                
                <!-- Control Panel -->
                <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded">
                    <div>
                        <h5 class="mb-1">Modo de Acceso: 
                            <span class="badge badge-{{ $access_mode == 'open' ? 'success' : 'danger' }}">
                                {{ $access_mode == 'open' ? 'ABIERTO' : 'RESTRINGIDO' }}
                            </span>
                        </h5>
                        <small class="text-muted">
                            @if($access_mode == 'open')
                                <i class="fas fa-unlock me-1"></i> Nuevos dispositivos se aprueban automáticamente.
                            @else
                                <i class="fas fa-lock me-1"></i> Nuevos dispositivos requieren aprobación manual.
                            @endif
                        </small>
                    </div>
                    <div>
                        <button wire:click="purgeDuplicates" class="btn btn-outline-warning mr-2" 
                            onclick="confirm('¿Estás seguro de depurar dispositivos duplicados e inactivos?') || event.stopImmediatePropagation()"
                            title="Eliminar dispositivos duplicados e inactivos">
                            <i class="fas fa-broom mr-1"></i> Limpiar Duplicados
                        </button>
                        <button class="btn btn-info mr-2" data-toggle="modal" data-target="#modalHelp">
                            <i class="fas fa-question-circle"></i> Ayuda
                        </button>
                        <button wire:click="toggleAccessMode" class="btn btn-{{ $access_mode == 'open' ? 'danger' : 'success' }}">
                            @if($access_mode == 'open')
                                <i class="fas fa-lock me-2"></i> Cambiar a Restringido
                            @else
                                <i class="fas fa-unlock me-2"></i> Cambiar a Abierto
                            @endif
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input wire:model.live="search" type="text" class="form-control" placeholder="Buscar por nombre o IP...">
                        </div>
                    </div>
                </div>

                <!-- Devices Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white">Nombre / ID</th>
                                <th class="table-th text-white">IP / Navegador</th>
                                <th class="table-th text-white text-center">Estado</th>
                                <th class="table-th text-white">Último Acceso</th>
                                <th class="table-th text-white">Impresora Asignada</th>
                                <th class="table-th text-white text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($devices as $device)
                            <tr @if($device->uuid == $current_token) style="background-color: rgba(26, 188, 156, 0.1);" @endif>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-2 position-relative">
                                            @if($device->user_agent && strpos(strtolower($device->user_agent), 'mobile') !== false)
                                                <i class="fas fa-mobile-alt fa-lg text-primary"></i>
                                            @else
                                                <i class="fas fa-desktop fa-lg text-info"></i>
                                            @endif
                                            
                                            {{-- Status Dot --}}
                                            <span class="position-absolute {{ $device->is_online ? 'pulse-online' : '' }}" style="top: -5px; right: -5px; height: 10px; width: 10px; background-color: {{ $device->is_online ? '#28a745' : '#6c757d' }}; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.2);" 
                                                title="{{ $device->is_online ? 'En línea' : 'Desconectado' }}">
                                            </span>

                                            @if($device->uuid == $current_token)
                                                <span class="badge badge-success d-block mt-1" style="font-size: 0.6rem;">ESTE</span>
                                            @endif
                                        </div>
                                        <div>
                                            <input type="text" 
                                                class="form-control form-control-sm border-0 bg-transparent p-0 font-weight-bold" 
                                                value="{{ $device->name }}"
                                                wire:change="updateName({{ $device->id }}, $event.target.value)"
                                                title="Actividad: {{ $device->last_accessed_at ? $device->last_accessed_at->diffForHumans() : 'Nunca' }} | {{ $device->user_agent }}"
                                            >
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">{{ $device->uuid }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $device->ip_address }}</p>
                                    <p class="text-xs text-secondary mb-0">{{ Str::limit($device->user_agent, 40) }}</p>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    @if ($device->status == 'approved')
                                        <span class="badge badge-sm bg-gradient-success">Aprobado</span>
                                    @elseif($device->status == 'pending')
                                        <span class="badge badge-sm bg-gradient-warning">Pendiente</span>
                                    @elseif($device->status == 'blocked')
                                        <span class="badge badge-sm bg-gradient-danger">Bloqueado</span>
                                    @endif
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $device->last_accessed_at ? \Carbon\Carbon::parse($device->last_accessed_at)->diffForHumans() : 'Nunca' }}</p>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-xs font-weight-bold mb-0 text-truncate" style="max-width: 200px;" title="{{ $device->printer_name ?? 'Predeterminada' }}">
                                            {{ $device->printer_name ?? 'Predeterminada' }}
                                        </span>
                                        <span class="text-xs text-secondary mb-0">
                                            {{ $device->printer_width ?? '80mm' }}
                                        </span>
                                    </div>
                                    <button wire:click="editPrinter({{ $device->id }})" class="btn btn-sm btn-outline-dark mt-1" title="Configurar Impresora">
                                        <i class="fas fa-print me-1"></i> Configurar
                                    </button>
                                </td>
                                <td class="align-middle text-center">
                                    @if ($device->status == 'pending' || $device->status == 'blocked')
                                        <button wire:click="approve({{ $device->id }})" class="btn btn-link text-success text-gradient px-3 mb-0" title="Aprobar">
                                            <i class="fas fa-check me-2"></i>
                                        </button>
                                    @endif

                                    @if ($device->status == 'approved')
                                        <button wire:click="block({{ $device->id }})" class="btn btn-link text-warning text-gradient px-3 mb-0" title="Bloquear">
                                            <i class="fas fa-ban me-2"></i>
                                        </button>
                                    @endif

                                    <button wire:click="delete({{ $device->id }})" class="btn btn-link text-danger text-gradient px-3 mb-0"
                                        onclick="confirm('¿Estás seguro de eliminar este dispositivo?') || event.stopImmediatePropagation()" title="Eliminar">
                                        <i class="far fa-trash-alt me-2"></i>
                                    </button>
                                </td>
                            </tr>

                            @endforeach
                            @if($devices->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">No hay dispositivos registrados</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-top">
                    {{ $devices->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Printer -->
    <div class="modal fade" id="modalPrinter" tabindex="-1" role="dialog" aria-labelledby="modalPrinterLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="modalPrinterLabel">
                        <i class="fas fa-print mr-2 text-primary"></i> Configurar Impresora del Dispositivo
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info text-white mb-3" role="alert">
                        <strong><i class="fas fa-info-circle mr-1"></i> Prioridad de Impresión:</strong> Esta configuración tiene prioridad sobre la impresora del usuario y la global del sistema.
                    </div>

                    <!-- Scanner Box -->
                    <div class="card mb-3 border-primary" style="background-color: #f8faff;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 font-weight-bold text-primary">
                                    <i class="fas fa-satellite-dish mr-1"></i> Detección Automática de Impresoras
                                </h6>
                                <button type="button" wire:click="scanPrinters" wire:loading.attr="disabled" class="btn btn-sm btn-primary">
                                    <span wire:loading wire:target="scanPrinters" class="spinner-border spinner-border-sm mr-1" role="status"></span>
                                    <i wire:loading.remove wire:target="scanPrinters" class="fas fa-sync-alt mr-1"></i>
                                    Escanear Red y Locales
                                </button>
                            </div>

                            @if(!empty($discovered_printers))
                                <div class="form-group mb-0">
                                    <label class="text-xs font-weight-bold text-muted mb-1">Impresoras Detectadas en la Red / PC:</label>
                                    <select wire:change="selectDiscoveredPrinter($event.target.value)" class="form-control form-control-sm">
                                        <option value="">-- Seleccionar impresora detectada para auto-rellenar --</option>
                                        @foreach($discovered_printers as $p)
                                            <option value="{{ $p['unc'] }}">
                                                {{ $p['label'] }} ({{ $p['unc'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-magic text-warning mr-1"></i> Al seleccionar una opción se completarán automáticamente los campos de configuración.
                                    </small>
                                </div>
                            @else
                                <small class="text-muted">
                                    Haga clic en "Escanear Red y Locales" para descubrir impresoras compartidas en la red local y colas locales de Windows.
                                </small>
                            @endif
                        </div>
                    </div>

                    <!-- Form Settings -->
                    <div class="form-group" @if($is_network) style="display:none" @endif>
                        <label for="printerName" class="font-weight-bold">Nombre de la Impresora (Local)</label>
                        <input type="text" class="form-control" id="printerName" wire:model="printer_name" placeholder="Ej: POS-80 o EPSON TM-T20II">
                        @error('printer_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    @if($is_network)
                    <div class="row">
                         <div class="col-md-6">
                            <div class="form-group">
                                <label for="printerHost" class="font-weight-bold">IP o Nombre del Equipo de Red</label>
                                <input type="text" class="form-control" id="printerHost" wire:model="printer_host" placeholder="Ej: 192.168.20.115 o CAJA-PRINCIPAL">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="printerShare" class="font-weight-bold">Nombre Compartido (Share)</label>
                                <input type="text" class="form-control" id="printerShare" wire:model="printer_share" placeholder="Ej: POS-80-Series">
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="printerWidth" class="font-weight-bold">Ancho del Papel</label>
                                <select class="form-control" id="printerWidth" wire:model="printer_width">
                                    <option value="80mm">80mm (Estándar POS)</option>
                                    <option value="58mm">58mm (Pequeña POS)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="isNetwork" wire:model.live="is_network">
                                <label class="custom-control-label font-weight-bold" for="isNetwork">
                                    ¿Es una impresora compartida en red?
                                </label>
                            </div>
                        </div>
                    </div>

                    @if($is_network)
                    <div class="mb-3">
                        <a class="text-xs font-weight-bold text-muted text-decoration-none" data-toggle="collapse" href="#credentialsCollapse" role="button" aria-expanded="{{ (!empty($printer_user) || !empty($printer_password)) ? 'true' : 'false' }}">
                            <i class="fas fa-key text-warning mr-1"></i> ¿El equipo remoto requiere usuario y contraseña de Windows? (Opcional) <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </a>
                        <div class="collapse {{ (!empty($printer_user) || !empty($printer_password)) ? 'show' : '' }} mt-2" id="credentialsCollapse">
                            <div class="card card-body p-2 bg-light border-0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-1">
                                            <label for="printerUser" class="text-xs font-weight-bold">Usuario de Windows</label>
                                            <input type="text" class="form-control form-control-sm" id="printerUser" wire:model="printer_user" placeholder="Ej: Administrador">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-1">
                                            <label for="printerPassword" class="text-xs font-weight-bold">Contraseña</label>
                                            <input type="password" class="form-control form-control-sm" id="printerPassword" wire:model="printer_password" placeholder="********">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Diagnostics & Test Result Area -->
                    @if($connection_test_result)
                        <div class="alert {{ $connection_test_result['success'] ? 'alert-success' : 'alert-danger' }} mb-3 py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas {{ $connection_test_result['success'] ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-2"></i>
                                    <strong>{{ $connection_test_result['success'] ? 'Conexión Exitosa' : 'Fallo de Conexión' }}:</strong>
                                    {{ $connection_test_result['message'] }}
                                </div>
                                @if(isset($connection_test_result['latency_ms']) && $connection_test_result['latency_ms'] > 0)
                                    <span class="badge badge-light text-dark font-weight-bold ml-2">
                                        ⚡ {{ $connection_test_result['latency_ms'] }} ms
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons for Testing -->
                    <div class="d-flex flex-wrap gap-2 justify-content-start pt-2 border-top">
                        <button type="button" wire:click="testPrinterConnection" wire:loading.attr="disabled" class="btn btn-outline-success mr-2">
                            <span wire:loading wire:target="testPrinterConnection" class="spinner-border spinner-border-sm mr-1" role="status"></span>
                            <i wire:loading.remove wire:target="testPrinterConnection" class="fas fa-bolt mr-1"></i>
                            Probar Conexión
                        </button>

                        <button type="button" wire:click="printTestTicket" wire:loading.attr="disabled" class="btn btn-outline-info">
                            <span wire:loading wire:target="printTestTicket" class="spinner-border spinner-border-sm mr-1" role="status"></span>
                            <i wire:loading.remove wire:target="printTestTicket" class="fas fa-receipt mr-1"></i>
                            Imprimir Ticket de Prueba
                        </button>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" wire:click="updatePrinter">
                        <i class="fas fa-save mr-1"></i> Guardar Configuración
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Help -->
    <div class="modal fade" id="modalHelp" tabindex="-1" role="dialog" aria-labelledby="modalHelpLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalHelpLabel">Guía de Configuración de Dispositivos e Impresoras</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6>¿Cómo funciona el Control de Acceso?</h6>
                    <p>El sistema identifica cada navegador/PC único y le asigna un "Token de Dispositivo".</p>
                    <ul>
                        <li><strong>Modo Abierto:</strong> Cualquier dispositivo nuevo entra automáticamente como "Aprobado". Ideal para empezar.</li>
                        <li><strong>Modo Restringido:</strong> Los dispositivos nuevos entran como "Pendientes" y no pueden acceder hasta que un administrador los apruebe aquí. Ideal para mayor seguridad.</li>
                    </ul>
                    
                    <hr>

                    <h6>Configuración de Impresoras en Red</h6>
                    <p>El sistema permite asignar una impresora específica a cada PC (Dispositivo). Esto es útil para entornos con múltiples cajas e impresoras.</p>
                    
                    <div class="alert alert-secondary">
                        <strong>Prioridad de Impresión:</strong><br>
                        1. Impresora del Dispositivo (Si existe)<br>
                        2. Impresora del Usuario (Si existe)<br>
                        3. Impresora Global (Configuración del Sistema)
                    </div>

                    <h6>Escenarios Comunes:</h6>
                    
                    <p><strong>Caso 1: Servidor y Caja (1 Impresora en Caja)</strong></p>
                    <ol>
                        <li>En la PC de Caja, comparta la impresora en red (ej: <code>ImpresoraCaja</code>).</li>
                        <li>En el Servidor, agregue esa impresora de red (<code>\\IP-CAJA\ImpresoraCaja</code>).</li>
                        <li>En esta pantalla, busque el dispositivo "Caja", haga clic en el botón <strong>Configurar</strong> y escriba el nombre de red: <code>\\IP-CAJA\ImpresoraCaja</code>.</li>
                    </ol>

                    <p><strong>Caso 2: Múltiples Cajas con sus propias impresoras</strong></p>
                    <ol>
                        <li>Comparta todas las impresoras en red.</li>
                        <li>Instale todas las impresoras en el Servidor.</li>
                        <li>Asigne a cada Dispositivo (Caja 1, Caja 2) su impresora correspondiente usando el nombre con el que se instaló en el Servidor.</li>
                    </ol>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .pulse-online {
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-modal', (modalId) => {
                $('#' + modalId).modal('show');
            });

            Livewire.on('close-modal', (modalId) => {
                $('#' + modalId).modal('hide');
            });
        });
    </script>
</div>
