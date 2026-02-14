<div class="row layout-top-spacing">
    <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
        <div class="widget widget-content-area br-4">
            <div class="widget-one">
                <div class="widget-header">
                    <h3>Generador de Lista de Precios</h3>
                </div>
                
                <div class="widget-content mt-4">
                    
                    @can('sales.configure_price_list')
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white"><i class="fas fa-cog"></i> Configuración de Columnas (Admin)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($availableColumns as $key => $label)
                                <div class="col-md-3 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="col_{{ $key }}" wire:model="selectedColumns" value="{{ $key }}">
                                        <label class="custom-control-label" for="col_{{ $key }}">{{ $label }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <button wire:click="saveConfig" class="btn btn-primary">Guardar Configuración Predeterminada</button>
                            </div>
                        </div>
                    </div>
                    @endcan

                    <div class="card">
                        <div class="card-body">
                            
                            {{-- Admin Seller Selection --}}
                            @if($sellers && count($sellers) > 0)
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Seleccionar Vendedor (Admin Only)</label>
                                        <select wire:model.live="selectedSellerId" class="form-control">
                                            <option value="">-- Usar mi configuración / Vendedor del Cliente --</option>
                                            @foreach($sellers as $seller)
                                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Al seleccionar un vendedor, se cargarán sus clientes y su configuración de precios.</small>
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- Custom Config "On-the-Fly" --}}
                            <div class="row mb-4 p-3 bg-light rounded">
                                <div class="col-12">
                                    <h6 class="font-weight-bold text-primary"><i class="fas fa-magic"></i> Configuración "Al Vuelo" (Opcional)</h6>
                                    <p class="text-muted small">Ingrese valores aquí para generar una lista con condiciones personalizadas, ignorando la configuración guardada.</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Comisión (%)</label>
                                        <input type="number" step="0.01" wire:model="customCommission" class="form-control" placeholder="Ej: 10">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Flete (%)</label>
                                        <input type="number" step="0.01" wire:model="customFreight" class="form-control" placeholder="Ej: 5">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Interés Mora % (IM)</label>
                                        <input type="number" step="0.01" class="form-control" wire:model.live="customMora" placeholder="E.g. 1">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Dif. Cambiario (%)</label>
                                        <input type="number" step="0.01" wire:model="customExchangeDiff" class="form-control" placeholder="Ej: 2">
                                    </div>
                                </div>
                            </div>

                            {{-- Custom Commercial Conditions --}}
                            <div class="row mb-4 p-3 bg-white border rounded">
                                <div class="col-12">
                                    <h6 class="font-weight-bold text-success"><i class="fas fa-file-invoice-dollar"></i> Condiciones Comerciales (Al Vuelo)</h6>
                                    <p class="text-muted small">Defina condiciones específicas para el encabezado del PDF (ideal para prospectos).</p>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Días Crédito</label>
                                        <input type="number" wire:model="customCreditDays" class="form-control" placeholder="Ej: 30">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Desc. Divisa (%)</label>
                                        <input type="number" step="0.01" wire:model="customUsdDiscount" class="form-control" placeholder="Ej: 5">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label>Reglas de Pronto Pago</label>
                                    @foreach($customRules as $index => $rule)
                                    <div class="input-group mb-2">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">0 a:</span>
                                        </div>
                                        <input type="number" wire:model="customRules.{{ $index }}.days" class="form-control" placeholder="Días">
                                        <input type="number" step="0.01" wire:model="customRules.{{ $index }}.percent" class="form-control" placeholder="%">
                                        <select wire:model="customRules.{{ $index }}.type" class="form-control">
                                            <option value="discount">Descuento</option>
                                            <option value="surcharge">Recargo</option>
                                        </select>
                                        <div class="input-group-append">
                                            <button class="btn btn-danger" wire:click="removeCustomRule({{ $index }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                    <button class="btn btn-sm btn-outline-primary mt-1" wire:click="addCustomRule">
                                        <i class="fas fa-plus"></i> Agregar Regla
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Seleccionar Cliente (Opcional)</label>
                                        <select wire:model.live="customerId" class="form-control">
                                            <option value="">-- Cliente General / Prospecto --</option>
                                            @foreach($customers as $c)
                                            <option value="{{ $c->id }}" wire:key="customer-{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Si selecciona un cliente, se aplicarán sus condiciones específicas (si existen).</small>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button wire:click="generate" wire:loading.attr="disabled" class="btn btn-success btn-lg btn-block mb-3">
                                        <span wire:loading.remove wire:target="generate">
                                            <i class="fas fa-file-pdf"></i> Generar Lista de Precios
                                        </span>
                                        <span wire:loading wire:target="generate">
                                            <i class="fas fa-spinner fa-spin"></i> Generando PDF...
                                        </span>
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
