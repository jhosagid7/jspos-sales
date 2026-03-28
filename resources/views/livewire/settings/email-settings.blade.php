<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card height-equal">
                <div class="card-header border-l-primary border-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            <h4 class="text-primary"><i class="fas fa-envelope"></i> Configuración de Correo Electrónico</h4>
                            <p class="text-muted">Plantillas automáticas de correos para clientes</p>
                        </div>
                        <div class="col-sm-12 col-md-4 text-end">
                             <button wire:click="save" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Configuración</button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">


                    <div class="row">
                        <!-- VENTA NUEVA -->
                        <div class="col-md-6 border-end">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-shopping-cart"></i> Plantilla: Venta Nueva
                            </h5>
                            
                            <div class="form-check form-switch form-switch-lg mb-3">
                                <input wire:model="sale_active" class="form-check-input" type="checkbox" id="saleActiveSwitch">
                                <label class="form-check-label" for="saleActiveSwitch">
                                    <strong>Habilitar envío al crear Venta</strong>
                                </label>
                            </div>

                            <div class="form-group mb-3" x-data="{ active: @entangle('sale_active') }" x-show="active">
                                <label class="text-primary fw-bold">Modo de Envío Global</label>
                                <select wire:model="sale_dispatch_mode" class="form-select border-primary">
                                    <option value="auto">Automático (Enviar y despachar al instante)</option>
                                    <option value="manual">Manual (Dejar en Bandeja de Salida para revisión)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Asunto del Correo</label>
                                <input type="text" wire:model="sale_subject" class="form-control" placeholder="Ej: Ticket de Compra">
                            </div>

                            <div class="form-group mb-3">
                                <label>Cuerpo del Correo</label>
                                <textarea wire:model="sale_body" class="form-control" rows="5" placeholder="Escribe el cuerpo del correo usando las variables..."></textarea>
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
                                <input wire:model="payment_active" class="form-check-input" type="checkbox" id="paymentActiveSwitch">
                                <label class="form-check-label" for="paymentActiveSwitch">
                                    <strong>Habilitar envío al aprobar un Pago</strong>
                                </label>
                            </div>

                            <div class="form-group mb-3" x-data="{ active: @entangle('payment_active') }" x-show="active">
                                <label class="text-primary fw-bold">Modo de Envío Global</label>
                                <select wire:model="payment_dispatch_mode" class="form-select border-primary">
                                    <option value="auto">Automático (Enviar y despachar al instante)</option>
                                    <option value="manual">Manual (Dejar en Bandeja de Salida para revisión)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Asunto del Correo</label>
                                <input type="text" wire:model="payment_subject" class="form-control" placeholder="Ej: Comprobante de Abono">
                            </div>

                            <div class="form-group mb-3">
                                <label>Cuerpo del Correo</label>
                                <textarea wire:model="payment_body" class="form-control" rows="5" placeholder="Escribe el cuerpo del correo usando las variables..."></textarea>
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
                                <input wire:model="cargo_active" class="form-check-input" type="checkbox" id="cargoActiveSwitch">
                                <label class="form-check-label" for="cargoActiveSwitch">
                                    <strong>Habilitar envío al registrar un Cargo</strong>
                                </label>
                            </div>

                            <div class="form-group mb-3" x-data="{ active: @entangle('cargo_active') }" x-show="active">
                                <label class="text-primary fw-bold">Modo de Envío Global</label>
                                <select wire:model="cargo_dispatch_mode" class="form-select border-primary">
                                    <option value="auto">Automático (Enviar y despachar al instante)</option>
                                    <option value="manual">Manual (Dejar en Bandeja de Salida para revisión)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Asunto del Correo</label>
                                <input type="text" wire:model="cargo_subject" class="form-control" placeholder="Ej: Nuevo Cargo Pendiente">
                            </div>

                            <div class="form-group mb-3">
                                <label>Cuerpo del Correo</label>
                                <textarea wire:model="cargo_body" class="form-control" rows="5" placeholder="Escribe el cuerpo del correo usando las variables..."></textarea>
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
                                <input wire:model="descargo_active" class="form-check-input" type="checkbox" id="descargoActiveSwitch">
                                <label class="form-check-label" for="descargoActiveSwitch">
                                    <strong>Habilitar envío al registrar un Descargo</strong>
                                </label>
                            </div>

                            <div class="form-group mb-3" x-data="{ active: @entangle('descargo_active') }" x-show="active">
                                <label class="text-primary fw-bold">Modo de Envío Global</label>
                                <select wire:model="descargo_dispatch_mode" class="form-select border-primary">
                                    <option value="auto">Automático (Enviar y despachar al instante)</option>
                                    <option value="manual">Manual (Dejar en Bandeja de Salida para revisión)</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Asunto del Correo</label>
                                <input type="text" wire:model="descargo_subject" class="form-control" placeholder="Ej: Nueva Salida Pendiente">
                            </div>

                            <div class="form-group mb-3">
                                <label>Cuerpo del Correo</label>
                                <textarea wire:model="descargo_body" class="form-control" rows="5" placeholder="Escribe el cuerpo del correo usando las variables..."></textarea>
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
