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
                                <th class="table-th text-white text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($devices as $device)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-2">
                                            @if($device->user_agent && strpos(strtolower($device->user_agent), 'mobile') !== false)
                                                <i class="fas fa-mobile-alt fa-lg text-primary"></i>
                                            @else
                                                <i class="fas fa-desktop fa-lg text-info"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <input type="text" 
                                                class="form-control form-control-sm border-0 bg-transparent p-0 font-weight-bold" 
                                                value="{{ $device->name }}"
                                                wire:change="updateName({{ $device->id }}, $event.target.value)"
                                            >
                                            <small class="text-muted d-block">{{ $device->uuid }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $device->ip_address }}</p>
                                    <p class="text-xs text-secondary mb-0">{{ Str::limit($device->user_agent, 40) }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $device->last_accessed_at ? \Carbon\Carbon::parse($device->last_accessed_at)->diffForHumans() : 'Nunca' }}</p>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-xs font-weight-bold mb-0">
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
                                <td class="align-middle text-center text-sm">
                                    @if ($device->status == 'approved')
                                        <span class="badge badge-sm bg-gradient-success">Aprobado</span>
                                    @elseif($device->status == 'pending')
                                        <span class="badge badge-sm bg-gradient-warning">Pendiente</span>
                                    @elseif($device->status == 'blocked')
                                        <span class="badge badge-sm bg-gradient-danger">Bloqueado</span>
                                    @endif
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

                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay dispositivos registrados</td>
                            </tr>
                            @endforelse
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
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPrinterLabel">Configurar Impresora del Dispositivo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info text-white" role="alert">
                        <strong>Nota:</strong> Esta configuración tiene prioridad sobre la impresora del usuario y la global.
                        Use el nombre exacto de la impresora compartida en red (ej: <code>\\SERVIDOR\ImpresoraCaja</code>) o el nombre local.
                    </div>
                    <div class="form-group">
                        <label for="printerName">Nombre de la Impresora</label>
                        <input type="text" class="form-control" id="printerName" wire:model="printer_name" placeholder="Ej: EPSON TM-T20II o \\PC-CAJA\TicketPrinter">
                        @error('printer_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="printerWidth">Ancho del Papel</label>
                        <select class="form-control" id="printerWidth" wire:model="printer_width">
                            <option value="80mm">80mm (Estándar)</option>
                            <option value="58mm">58mm (Pequeña)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="updatePrinter">Guardar Configuración</button>
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
