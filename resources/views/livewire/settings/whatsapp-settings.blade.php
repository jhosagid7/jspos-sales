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
                                    <strong>Habilitar envío automático al crear Venta</strong>
                                </label>
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
                                    <strong>Habilitar envío automático al aprobar un Pago</strong>
                                </label>
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
                                    <strong>Habilitar envío automático al registrar un Cargo</strong>
                                </label>
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
                                    <strong>Habilitar envío automático al registrar un Descargo</strong>
                                </label>
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
                </div>
            </div>
        </div>
    </div>
</div>
