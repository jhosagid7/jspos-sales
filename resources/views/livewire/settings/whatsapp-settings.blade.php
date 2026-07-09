<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card height-equal">
                <div class="card-header border-l-success border-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            <h4 class="text-success"><i class="fab fa-whatsapp"></i> Configuración de WhatsApp</h4>
                            <p class="text-muted">Plantillas automáticas de mensajes para clientes</p>
                        </div>
                        <div class="col-sm-12 col-md-4 text-end">
                             <button wire:click="save" class="btn btn-success"><i class="fas fa-save"></i> Guardar Configuración</button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- SECCIÓN ESTADO Y QR -->
                    <div class="row mb-4" x-data="whatsappConnector()">
                        <div class="col-12 text-center p-3 border rounded bg-light">
                            <h5 class="text-dark mb-3"><i class="fab fa-whatsapp"></i> Estado de Conexión de tu Tienda</h5>
                            
                            <div x-show="loading" class="text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2">Verificando estado de WhatsApp...</p>
                            </div>

                            <div x-show="!loading && isReady" style="display: none;">
                                <div class="alert alert-success d-inline-block px-5">
                                    <h4 class="mb-0"><i class="fas fa-check-circle"></i> WhatsApp Conectado</h4>
                                </div>
                                <p class="text-muted mt-2">Tu sistema JSPOS está listo para enviar notificaciones automáticamente.</p>
                                <button type="button" class="btn btn-outline-danger btn-sm mt-3" wire:click="disconnectWhatsapp" @click="setTimeout(() => init(), 1500)">
                                    <i class="fas fa-unlink"></i> Desconectar Cuenta Actual
                                </button>
                            </div>

                            <div x-show="!loading && !isReady" style="display: none;">
                                <div class="alert alert-warning d-inline-block px-5">
                                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> WhatsApp Desconectado</h5>
                                </div>
                                <p class="text-muted mt-2">Abre WhatsApp en tu teléfono inteligente > <strong>Dispositivos Vinculados</strong> > <strong>Vincular un dispositivo</strong> y escanea el código a continuación:</p>
                                
                                <div class="mt-3 p-3 bg-white d-inline-block border rounded shadow-sm">
                                    <template x-if="qrImage">
                                        <img :src="qrImage" alt="WhatsApp QR Code" class="img-fluid" style="max-height: 250px;">
                                    </template>
                                    <template x-if="!qrImage">
                                        <div class="text-center p-4">
                                            <i class="fas fa-sync fa-spin fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">Generando nuevo Código QR...</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        function whatsappConnector() {
                            return {
                                loading: true,
                                isReady: false,
                                qrImage: null,
                                connectionStatus: 'UNKNOWN',
                                pollInterval: null,

                                init() {
                                    this.checkStatus();
                                    
                                    // Set up polling every 3 seconds
                                    this.pollInterval = setInterval(() => {
                                        this.checkStatus();
                                    }, 3000);
                                },

                                async checkStatus() {
                                    try {
                                        // 1. Primero checamos si ya está listo rápido
                                        const statusRes = await fetch('http://localhost:3000/status');
                                        const statusData = await statusRes.json();
                                        
                                        this.isReady = statusData.isReady;
                                        this.connectionStatus = statusData.status;

                                        // 2. Si NO está listo, pedimos el QR code explicitamente
                                        if (!this.isReady) {
                                            const qrRes = await fetch('http://localhost:3000/qr');
                                            const qrData = await qrRes.json();
                                            
                                            // Si la API generó un QR nuevo, lo pintamos
                                            if (qrData.qr) {
                                                this.qrImage = qrData.qr;
                                            }
                                        } else {
                                            // Si ya se conectó, dejamos de pedir el QR
                                            this.qrImage = null;
                                        }

                                    } catch (error) {
                                        console.error('Error conectando con la API de WhatsApp local', error);
                                        this.isReady = false;
                                        this.connectionStatus = 'SERVER_OFFLINE';
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            }
                        }
                    </script>

                    <div class="row">
                        <!-- VENTA NUEVA -->
                        <div class="col-md-6 border-end">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-shopping-cart"></i> Plantilla: Venta Nueva
                            </h5>
                            
                            <div class="form-check form-switch form-switch-lg mb-3">
                                <input wire:model="sale_active" class="form-check-input form-check-input-success" type="checkbox" id="saleActiveSwitch">
                                <label class="form-check-label" for="saleActiveSwitch">
                                    <strong>Habilitar envío al crear Venta</strong>
                                </label>
                            </div>

                            <div class="form-group mb-3" x-data="{ active: @entangle('sale_active') }" x-show="active">
                                <label class="text-success fw-bold">Modo de Envío Global</label>
                                <select wire:model="sale_dispatch_mode" class="form-select border-success">
                                    <option value="auto">Automático (Enviar y despachar al instante)</option>
                                    <option value="manual">Manual (Dejar en Bandeja de Salida para revisión)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Asunto (Uso Interno)</label>
                                <input type="text" wire:model="sale_subject" class="form-control" placeholder="Ej: Ticket de Compra">
                            </div>

                            <div class="form-group mb-3">
                                <label>Cuerpo del Mensaje (Texto Normal, Usa *asteriscos* para negrita)</label>
                                <textarea wire:model="sale_body" class="form-control" rows="5" placeholder="Escribe tu mensaje usando las variables..."></textarea>
                            </div>

                            <div class="alert alert-light border">
                                <h6>Variables Disponibles:</h6>
                                <p class="mb-1"><code>[CLIENTE]</code> : Nombre del Cliente</p>
                                <p class="mb-1"><code>[FACTURA]</code> : Número de Folio o Factura</p>
                                <p class="mb-1"><code>[TOTAL]</code> : Total de la venta</p>
                                <p class="mb-1"><code>[FECHA]</code> : Fecha de la venta</p>
                                <p class="mb-1"><code>[EMPRESA]</code> : Nombre de tu negocio</p>
                                <hr>
                                <small class="text-muted"><i class="fas fa-paperclip"></i> El PDF del recibo se enviará según esté configurado tu motor NodeJS.</small>
                            </div>
                        </div>

                        <!-- ABONO RECIBIDO -->
                        <div class="col-md-6 p-x-3">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-hand-holding-usd"></i> Plantilla: Abono o Pago Recibido
                            </h5>
                            
                            <div class="form-check form-switch form-switch-lg mb-3">
                                <input wire:model="payment_active" class="form-check-input form-check-input-success" type="checkbox" id="paymentActiveSwitch">
                                <label class="form-check-label" for="paymentActiveSwitch">
                                    <strong>Habilitar envío al aprobar un Pago</strong>
                                </label>
                            </div>

                            <div class="form-group mb-3" x-data="{ active: @entangle('payment_active') }" x-show="active">
                                <label class="text-success fw-bold">Modo de Envío Global</label>
                                <select wire:model="payment_dispatch_mode" class="form-select border-success">
                                    <option value="auto">Automático (Enviar y despachar al instante)</option>
                                    <option value="manual">Manual (Dejar en Bandeja de Salida para revisión)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Asunto (Uso Interno)</label>
                                <input type="text" wire:model="payment_subject" class="form-control" placeholder="Ej: Comprobante de Abono">
                            </div>

                            <div class="form-group mb-3">
                                <label>Cuerpo del Mensaje (Texto Normal, Usa *asteriscos* para negrita)</label>
                                <textarea wire:model="payment_body" class="form-control" rows="5" placeholder="Escribe tu mensaje usando las variables..."></textarea>
                            </div>

                            <div class="alert alert-light border">
                                <h6>Variables Disponibles:</h6>
                                <p class="mb-1"><code>[CLIENTE]</code> : Nombre del Cliente</p>
                                <p class="mb-1"><code>[FACTURA_PAGADA]</code> : Número de la Factura afectada / Pedido</p>
                                <p class="mb-1"><code>[MONTO_PAGADO]</code> : Monto que el cliente abonó</p>
                                <p class="mb-1"><code>[SALDO_RESTANTE]</code> : Deuda actual de la factura</p>
                                <p class="mb-1"><code>[FECHA]</code> : Fecha del pago</p>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4 pt-4 border-top">
                        <!-- CARGO NUEVO -->
                        <div class="col-md-6 border-end">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-boxes"></i> Plantilla: Nuevo Cargo / Ajuste Creado
                            </h5>
                            
                            <div class="form-check form-switch form-switch-lg mb-3">
                                <input wire:model="cargo_active" class="form-check-input form-check-input-success" type="checkbox" id="cargoActiveSwitch">
                                <label class="form-check-label" for="cargoActiveSwitch">
                                    <strong>Habilitar envío al registrar un Cargo</strong>
                                </label>
                            </div>

                            <div class="form-group mb-3" x-data="{ active: @entangle('cargo_active') }" x-show="active">
                                <label class="text-success fw-bold">Modo de Envío Global</label>
                                <select wire:model="cargo_dispatch_mode" class="form-select border-success">
                                    <option value="auto">Automático (Enviar y despachar al instante)</option>
                                    <option value="manual">Manual (Dejar en Bandeja de Salida para revisión)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Asunto (Uso Interno)</label>
                                <input type="text" wire:model="cargo_subject" class="form-control" placeholder="Ej: Nuevo Cargo Pendiente">
                            </div>

                            <div class="form-group mb-3">
                                <label>Cuerpo del Mensaje (Texto Normal, Usa *asteriscos* para negrita)</label>
                                <textarea wire:model="cargo_body" class="form-control" rows="5" placeholder="Escribe tu mensaje usando las variables..."></textarea>
                            </div>

                            <div class="alert alert-light border">
                                <h6>Variables Disponibles:</h6>
                                <p class="mb-1"><code>[CARGO_ID]</code> : ID del Ajuste</p>
                                <p class="mb-1"><code>[MOTIVO]</code> : Motivo del ajuste</p>
                                <p class="mb-1"><code>[USUARIO]</code> : Quien registró el cargo</p>
                                <p class="mb-1"><code>[FECHA]</code> : Fecha del registro</p>
                                <p class="mb-1"><code>[EMPRESA]</code> : Nombre de tu negocio</p>
                            </div>
                        </div>

                        <!-- DESCARGO NUEVO -->
                        <div class="col-md-6 p-x-3">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-truck-loading"></i> Plantilla: Nuevo Descargo / Salida Creado
                            </h5>
                            
                            <div class="form-check form-switch form-switch-lg mb-3">
                                <input wire:model="descargo_active" class="form-check-input form-check-input-success" type="checkbox" id="descargoActiveSwitch">
                                <label class="form-check-label" for="descargoActiveSwitch">
                                    <strong>Habilitar envío al registrar un Descargo</strong>
                                </label>
                            </div>

                            <div class="form-group mb-3" x-data="{ active: @entangle('descargo_active') }" x-show="active">
                                <label class="text-success fw-bold">Modo de Envío Global</label>
                                <select wire:model="descargo_dispatch_mode" class="form-select border-success">
                                    <option value="auto">Automático (Enviar y despachar al instante)</option>
                                    <option value="manual">Manual (Dejar en Bandeja de Salida para revisión)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Asunto (Uso Interno)</label>
                                <input type="text" wire:model="descargo_subject" class="form-control" placeholder="Ej: Nueva Salida Pendiente">
                            </div>

                            <div class="form-group mb-3">
                                <label>Cuerpo del Mensaje (Texto Normal, Usa *asteriscos* para negrita)</label>
                                <textarea wire:model="descargo_body" class="form-control" rows="5" placeholder="Escribe tu mensaje usando las variables..."></textarea>
                            </div>

                            <div class="alert alert-light border">
                                <h6>Variables Disponibles:</h6>
                                <p class="mb-1"><code>[DESCARGO_ID]</code> : ID de la Salida</p>
                                <p class="mb-1"><code>[MOTIVO]</code> : Motivo de la salida</p>
                                <p class="mb-1"><code>[USUARIO]</code> : Quien registró el descargo</p>
                                <p class="mb-1"><code>[FECHA]</code> : Fecha del registro</p>
                                <p class="mb-1"><code>[EMPRESA]</code> : Nombre de tu negocio</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN: GRUPOS DE WHATSAPP DISPONIBLES -->
                    <div class="row mt-4 pt-4 border-top">
                        <div class="col-12">
                            <h5 class="text-success mb-3">
                                <i class="fas fa-users"></i> Grupos de WhatsApp Disponibles
                            </h5>
                            <p class="text-muted">Marca las casillas de los grupos a los que deseas enviar de forma automática la notificación diaria al guardar las tasas de cambio. Si no seleccionas ninguno, se enviará por defecto al grupo llamado <strong>Diferencial</strong>.</p>
                            
                            <div class="mb-3">
                                <button type="button" wire:click="loadGroups" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-sync"></i> Actualizar Lista de Grupos
                                </button>
                            </div>

                            @if(count($groups) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nombre del Grupo</th>
                                                <th class="text-center" style="width: 120px;">Tasa de Cambio</th>
                                                <th class="text-center" style="width: 120px;">Cierre Diario</th>
                                                <th class="text-center" style="width: 120px;">Reporte Semanal PDF</th>
                                                <th class="text-center" style="width: 120px;">Soplados (Turno)</th>
                                                <th class="text-center" style="width: 120px;">Soplados (Semanal)</th>
                                                <th>Identificador (JID)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($groups as $group)
                                                @php
                                                    $isRate = in_array($group['id'], $selectedRateGroups);
                                                    $isClosure = in_array($group['id'], $selectedClosureGroups);
                                                    $isWeekly = in_array($group['id'], $selectedWeeklyReportGroups);
                                                    $isSopladosShift = in_array($group['id'], $selectedSopladosShiftGroups);
                                                    $isSopladosWeekly = in_array($group['id'], $selectedSopladosWeeklyGroups);
                                                    $anySelected = $isRate || $isClosure || $isWeekly || $isSopladosShift || $isSopladosWeekly;
                                                @endphp
                                                <tr class="{{ $anySelected ? 'table-success' : '' }}">
                                                    <td class="{{ $anySelected ? 'font-weight-bold' : '' }}">
                                                        <i class="fas fa-users-cog text-muted me-2"></i>
                                                        {{ $group['name'] }}
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input"
                                                               wire:click="toggleGroup('{{ $group['id'] }}', 'rate')"
                                                               {{ $isRate ? 'checked' : '' }}>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input"
                                                               wire:click="toggleGroup('{{ $group['id'] }}', 'closure')"
                                                               {{ $isClosure ? 'checked' : '' }}>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input"
                                                               wire:click="toggleGroup('{{ $group['id'] }}', 'weekly_report')"
                                                               {{ $isWeekly ? 'checked' : '' }}>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input"
                                                               wire:click="toggleGroup('{{ $group['id'] }}', 'soplados_shift')"
                                                               {{ $isSopladosShift ? 'checked' : '' }}>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input"
                                                               wire:click="toggleGroup('{{ $group['id'] }}', 'soplados_weekly')"
                                                               {{ $isSopladosWeekly ? 'checked' : '' }}>
                                                    </td>
                                                    <td><code>{{ $group['id'] }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No se encontraron grupos activos o WhatsApp está desconectado. Si acabas de vincular tu cuenta, presiona "Actualizar Lista de Grupos".
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- SECCIÓN: NOTIFICACIONES ADICIONALES (CORREOS Y USUARIOS WHATSAPP) -->
                    <div class="row mt-4 pt-4 border-top">
                        <div class="col-12">
                            <h5 class="text-success mb-3">
                                <i class="fas fa-envelope-open-text"></i> Notificaciones Adicionales (Correos y Contactos de WhatsApp)
                            </h5>
                            <p class="text-muted">Introduce correos electrónicos o selecciona usuarios del sistema para enviarles notificaciones de tasa de cambio, cierre de caja diario o reportes semanales.</p>
                            
                            <div class="row">
                                <!-- TASA DE CAMBIO -->
                                <div class="col-md-4 border-end">
                                    <h6 class="text-primary mb-3"><i class="fas fa-dollar-sign text-success me-1"></i> Tasa de Cambio</h6>
                                    
                                    <div class="form-group mb-3">
                                        <label class="fw-bold text-dark"><i class="fas fa-envelope text-success me-1"></i> Correos Electrónicos</label>
                                        <textarea wire:model="emailRateRecipients" class="form-control border-success" rows="3" placeholder="ejemplo1@correo.com, ejemplo2@correo.com"></textarea>
                                        <small class="text-muted">Separados por comas.</small>
                                    </div>

                                    <div class="form-group mb-3 position-relative" x-data="{ open: false }">
                                        <label class="fw-bold text-dark"><i class="fab fa-whatsapp text-success me-1"></i> Usuarios de WhatsApp</label>
                                        <input type="text" 
                                               wire:model.live="searchRateQuery" 
                                               class="form-control border-success" 
                                               placeholder="Buscar usuario..."
                                               @focus="open = true"
                                               @click.away="open = false">
                                        
                                        @if(!empty($rateUsersResults))
                                            <ul class="list-group position-absolute w-100 shadow" style="z-index: 1000; max-height: 200px; overflow-y: auto;" x-show="open">
                                                @foreach($rateUsersResults as $result)
                                                    <li class="list-group-item list-group-item-action py-2" style="cursor: pointer;" wire:click="selectUser({{ $result['id'] }}, 'rate')" @click="open = false">
                                                        <strong>{{ $result['name'] }}</strong> <span class="text-muted">({{ $result['phone'] }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                            @foreach($selectedRateUsersList as $user)
                                                <span class="badge bg-success p-2 d-inline-flex align-items-center text-white">
                                                    {{ $user->name }}
                                                    <button type="button" wire:click="removeUser({{ $user->id }}, 'rate')" class="btn-close btn-close-white ms-2" style="font-size: 0.65rem;" aria-label="Remove"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- CIERRE DIARIO -->
                                <div class="col-md-4 border-end">
                                    <h6 class="text-primary mb-3"><i class="fas fa-calculator text-success me-1"></i> Cierre Diario</h6>
                                    
                                    <div class="form-group mb-3">
                                        <label class="fw-bold text-dark"><i class="fas fa-envelope text-success me-1"></i> Correos Electrónicos</label>
                                        <textarea wire:model="emailClosureRecipients" class="form-control border-success" rows="3" placeholder="ejemplo1@correo.com, ejemplo2@correo.com"></textarea>
                                        <small class="text-muted">Separados por comas.</small>
                                    </div>

                                    <div class="form-group mb-3 position-relative" x-data="{ open: false }">
                                        <label class="fw-bold text-dark"><i class="fab fa-whatsapp text-success me-1"></i> Usuarios de WhatsApp</label>
                                        <input type="text" 
                                               wire:model.live="searchClosureQuery" 
                                               class="form-control border-success" 
                                               placeholder="Buscar usuario..."
                                               @focus="open = true"
                                               @click.away="open = false">
                                        
                                        @if(!empty($closureUsersResults))
                                            <ul class="list-group position-absolute w-100 shadow" style="z-index: 1000; max-height: 200px; overflow-y: auto;" x-show="open">
                                                @foreach($closureUsersResults as $result)
                                                    <li class="list-group-item list-group-item-action py-2" style="cursor: pointer;" wire:click="selectUser({{ $result['id'] }}, 'closure')" @click="open = false">
                                                        <strong>{{ $result['name'] }}</strong> <span class="text-muted">({{ $result['phone'] }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                            @foreach($selectedClosureUsersList as $user)
                                                <span class="badge bg-success p-2 d-inline-flex align-items-center text-white">
                                                    {{ $user->name }}
                                                    <button type="button" wire:click="removeUser({{ $user->id }}, 'closure')" class="btn-close btn-close-white ms-2" style="font-size: 0.65rem;" aria-label="Remove"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- REPORTE SEMANAL PDF -->
                                <div class="col-md-4">
                                    <h6 class="text-primary mb-3"><i class="fas fa-file-pdf text-success me-1"></i> Reporte Semanal PDF</h6>
                                    
                                    <div class="form-group mb-3">
                                        <label class="fw-bold text-dark"><i class="fas fa-envelope text-success me-1"></i> Correos Electrónicos</label>
                                        <textarea wire:model="emailWeeklyReportRecipients" class="form-control border-success" rows="3" placeholder="ejemplo1@correo.com, ejemplo2@correo.com"></textarea>
                                        <small class="text-muted">Separados por comas.</small>
                                    </div>

                                    <div class="form-group mb-3 position-relative" x-data="{ open: false }">
                                        <label class="fw-bold text-dark"><i class="fab fa-whatsapp text-success me-1"></i> Logins de WhatsApp</label>
                                        <input type="text" 
                                               wire:model.live="searchWeeklyReportQuery" 
                                               class="form-control border-success" 
                                               placeholder="Buscar usuario..."
                                               @focus="open = true"
                                               @click.away="open = false">
                                        
                                        @if(!empty($weeklyReportUsersResults))
                                            <ul class="list-group position-absolute w-100 shadow" style="z-index: 1000; max-height: 200px; overflow-y: auto;" x-show="open">
                                                @foreach($weeklyReportUsersResults as $result)
                                                    <li class="list-group-item list-group-item-action py-2" style="cursor: pointer;" wire:click="selectUser({{ $result['id'] }}, 'weekly_report')" @click="open = false">
                                                        <strong>{{ $result['name'] }}</strong> <span class="text-muted">({{ $result['phone'] }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                            @foreach($selectedWeeklyUsersList as $user)
                                                <span class="badge bg-success p-2 d-inline-flex align-items-center text-white">
                                                    {{ $user->name }}
                                                    <button type="button" wire:click="removeUser({{ $user->id }}, 'weekly_report')" class="btn-close btn-close-white ms-2" style="font-size: 0.65rem;" aria-label="Remove"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4 pt-4 border-top">
                                <!-- SOPLADOS (TURNO) -->
                                <div class="col-md-4 border-end">
                                    <h6 class="text-primary mb-3"><i class="fas fa-sync text-success me-1"></i> Soplados (Cierre de Turno)</h6>
                                    
                                    <div class="form-group mb-3">
                                        <label class="fw-bold text-dark"><i class="fas fa-envelope text-success me-1"></i> Correos Electrónicos</label>
                                        <textarea wire:model="sopladosEmailRecipients" class="form-control border-success" rows="3" placeholder="ejemplo1@correo.com, ejemplo2@correo.com"></textarea>
                                        <small class="text-muted">Separados por comas (se sincroniza con Ajustes > Email).</small>
                                    </div>

                                    <div class="form-group mb-3 position-relative" x-data="{ open: false }">
                                        <label class="fw-bold text-dark"><i class="fab fa-whatsapp text-success me-1"></i> Usuarios de WhatsApp</label>
                                        <input type="text" 
                                               wire:model.live="searchSopladosShiftQuery" 
                                               class="form-control border-success" 
                                               placeholder="Buscar usuario..."
                                               @focus="open = true"
                                               @click.away="open = false">
                                        
                                        @if(!empty($sopladosShiftUsersResults))
                                            <ul class="list-group position-absolute w-100 shadow" style="z-index: 1000; max-height: 200px; overflow-y: auto;" x-show="open">
                                                @foreach($sopladosShiftUsersResults as $result)
                                                    <li class="list-group-item list-group-item-action py-2" style="cursor: pointer;" wire:click="selectUser({{ $result['id'] }}, 'soplados_shift')" @click="open = false">
                                                        <strong>{{ $result['name'] }}</strong> <span class="text-muted">({{ $result['phone'] }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                            @foreach($selectedSopladosShiftUsersList as $user)
                                                <span class="badge bg-success p-2 d-inline-flex align-items-center text-white">
                                                    {{ $user->name }}
                                                    <button type="button" wire:click="removeUser({{ $user->id }}, 'soplados_shift')" class="btn-close btn-close-white ms-2" style="font-size: 0.65rem;" aria-label="Remove"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- SOPLADOS (SEMANAL) -->
                                <div class="col-md-4 border-end">
                                    <h6 class="text-primary mb-3"><i class="fas fa-file-invoice text-success me-1"></i> Soplados (Semanal Consolidado)</h6>
                                    
                                    <div class="form-group mb-3">
                                        <label class="fw-bold text-dark"><i class="fas fa-envelope text-success me-1"></i> Correos Electrónicos</label>
                                        <textarea wire:model="emailSopladosWeeklyRecipients" class="form-control border-success" rows="3" placeholder="ejemplo1@correo.com, ejemplo2@correo.com"></textarea>
                                        <small class="text-muted">Separados por comas.</small>
                                    </div>

                                    <div class="form-group mb-3 position-relative" x-data="{ open: false }">
                                        <label class="fw-bold text-dark"><i class="fab fa-whatsapp text-success me-1"></i> Usuarios de WhatsApp</label>
                                        <input type="text" 
                                               wire:model.live="searchSopladosWeeklyQuery" 
                                               class="form-control border-success" 
                                               placeholder="Buscar usuario..."
                                               @focus="open = true"
                                               @click.away="open = false">
                                        
                                        @if(!empty($sopladosWeeklyUsersResults))
                                            <ul class="list-group position-absolute w-100 shadow" style="z-index: 1000; max-height: 200px; overflow-y: auto;" x-show="open">
                                                @foreach($sopladosWeeklyUsersResults as $result)
                                                    <li class="list-group-item list-group-item-action py-2" style="cursor: pointer;" wire:click="selectUser({{ $result['id'] }}, 'soplados_weekly')" @click="open = false">
                                                        <strong>{{ $result['name'] }}</strong> <span class="text-muted">({{ $result['phone'] }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                            @foreach($selectedSopladosWeeklyUsersList as $user)
                                                <span class="badge bg-success p-2 d-inline-flex align-items-center text-white">
                                                    {{ $user->name }}
                                                    <button type="button" wire:click="removeUser({{ $user->id }}, 'soplados_weekly')" class="btn-close btn-close-white ms-2" style="font-size: 0.65rem;" aria-label="Remove"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- PLANIFICACIÓN SEMANAL -->
                                <div class="col-md-4">
                                    <h6 class="text-primary mb-3"><i class="fas fa-calendar-alt text-success me-1"></i> Programación de Reportes Semanales</h6>
                                    
                                    <div class="form-group mb-3">
                                        <label class="fw-bold text-dark">Día de la Semana para Envío</label>
                                        <select wire:model="weeklyReportSendDay" class="form-control border-success">
                                            <option value="1">Lunes</option>
                                            <option value="2">Martes</option>
                                            <option value="3">Miércoles</option>
                                            <option value="4">Jueves</option>
                                            <option value="5">Viernes</option>
                                            <option value="6">Sábado</option>
                                            <option value="0">Domingo</option>
                                        </select>
                                        <small class="text-muted">Día programado para enviar los consolidados semanales.</small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="fw-bold text-dark">Hora de Envío</label>
                                        <input type="time" wire:model="weeklyReportSendHour" class="form-control border-success">
                                        <small class="text-muted">Hora del día en formato 24h.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
