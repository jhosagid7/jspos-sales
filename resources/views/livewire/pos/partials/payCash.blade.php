<div>
    <div wire:ignore.self class="modal fade" id="modalCash" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                {{-- Header --}}
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-cash-register me-2"></i>
                        {{ $payType == 2 ? 'GESTIÓN DE CRÉDITO' : 'PAGO DE VENTA' }}
                    </h5>
                    <button class="btn-close btn-close-white" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalCash').modal('hide')"></button>
                </div>

                <div class="modal-body">
                    {{-- Obtener el símbolo de la moneda principal --}}
                    @php
                        // Use displayCurrency if available (for Invoice Currency view), otherwise Primary
                        $targetCurrency = $displayCurrency ?? collect($currencies)->firstWhere('is_primary', 1);
                        $symbol = $targetCurrency ? $targetCurrency->symbol : '$';
                    @endphp

                    @style
                        .btn-outline-wallet {
                            color: #f39c12;
                            border-color: #f39c12;
                            background-color: transparent;
                        }
                        .btn-outline-wallet:hover {
                            color: #fff;
                            background-color: #e67e22;
                            border-color: #d35400;
                        }
                        .btn-active-wallet {
                            color: #fff !important;
                            background-color: #f39c12 !important;
                            border-color: #e67e22 !important;
                            box-shadow: 0 4px 8px rgba(243, 156, 18, 0.3);
                        }
                    @endstyle

                    <div class="row">
                        {{-- Columna Izquierda: Resumen y Método de Pago --}}
                        <div class="col-md-6">
                            {{-- Resumen del carrito --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-shopping-cart me-2"></i>Resumen de la Venta</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Artículos:</span>
                                        <span class="fw-bold">{{ $itemsCart }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Subtotal:</span>
                                        <span class="fw-bold">{{ $symbol }}{{ formatMoney($this->displaySubtotalCart) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                        <span class="text-muted">I.V.A.:</span>
                                        <span class="fw-bold">{{ $symbol }}{{ formatMoney($this->displayIvaCart) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fs-5 fw-bold text-primary">TOTAL:</span>
                                        <span class="fs-5 fw-bold text-primary">{{ $symbol }}{{ formatMoney($this->displayTotalCart) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Selector de Método de Pago --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-credit-card me-2"></i>Método de Pago</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 justify-content-center">
                                        {{-- Efectivo (siempre activo) --}}
                                        <div class="col-4">
                                            <button 
                                                type="button"
                                                wire:click="$set('selectedPaymentMethod', 'cash')"
                                                class="btn w-100 {{ $selectedPaymentMethod === 'cash' ? 'btn-success' : 'btn-outline-success' }}"
                                                style="padding: 15px 10px;">
                                                <i class="fa fa-money-bill-wave fa-2x d-block mb-2"></i>
                                                <small class="d-block">Efectivo</small>
                                            </button>
                                        </div>

                                        {{-- Banco / Zelle --}}
                                        @module('module_advanced_payments')
                                        <div class="col-4">
                                            <button 
                                                type="button"
                                                wire:click="$set('selectedPaymentMethod', 'bank')"
                                                class="btn w-100 {{ $selectedPaymentMethod === 'bank' ? 'btn-primary' : 'btn-outline-primary' }}"
                                                style="padding: 15px 10px;">
                                                <i class="fa fa-university fa-2x d-block mb-2"></i>
                                                <small class="d-block">Banco / Zelle</small>
                                            </button>
                                        </div>
                                        @endmodule

                                        {{-- Billetera Virtual (En lugar de Crédito si el cliente tiene saldo) --}}
                                        @if(!empty($customer['id']) && ($customer['wallet_balance'] ?? 0) > 0)
                                        <div class="col-4">
                                            <button 
                                                type="button"
                                                wire:click="$set('selectedPaymentMethod', 'wallet')"
                                                class="btn w-100 {{ $selectedPaymentMethod === 'wallet' ? 'btn-active-wallet' : 'btn-outline-wallet' }}"
                                                style="padding: 15px 10px; border-radius: 8px;">
                                                <i class="fa fa-wallet fa-2x d-block mb-2"></i>
                                                <small class="d-block text-truncate fw-bold">Billetera ({{ $primaryCurrency->symbol ?? '$' }}{{ formatMoney($customer['wallet_balance'] ?? 0) }})</small>
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Formulario según método seleccionado --}}
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-edit me-2"></i>Detalles del Pago</h6>
                                </div>
                                <div class="card-body">
                                    {{-- EFECTIVO --}}
                                    @if($selectedPaymentMethod === 'cash')
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Monto</label>
                                                <input 
                                                    class="form-control" 
                                                    oninput="validarInputNumber(this)"
                                                    wire:model.live.debounce.750ms="paymentAmount"
                                                    wire:keydown.enter.prevent='addPayment' 
                                                    type="number" 
                                                    placeholder="0.00"
                                                    id="inputCash">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Moneda</label>
                                                <select class="form-control" wire:model.live="paymentCurrency">
                                                    @if (!empty($currencies) && $currencies->count() > 0)
                                                        @foreach ($currencies as $currency)
                                                            <option value="{{ $currency->code }}"
                                                                @if ($currency->is_primary) selected @endif>
                                                                {{ $currency->label }}
                                                            </option>
                                                        @endforeach
                                                    @else
                                                        <option disabled>No hay monedas disponibles</option>
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-success w-100" wire:click="addPayment" type="button">
                                                    <i class="fa fa-plus-circle me-2"></i>Agregar Pago en Efectivo
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- CRÉDITO --}}
                                    @if($selectedPaymentMethod === 'credit')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="alert alert-info mb-3">
                                                    <h6 class="mb-2"><i class="fa fa-info-circle"></i> Información de Crédito</h6>
                                                    <ul class="mb-0 small">
                                                        <li><strong>Días de crédito:</strong> {{ $creditConfig['credit_days'] ?? 'N/A' }} días</li>
                                                        <li><strong>Límite de crédito:</strong> {{ $symbol }}{{ formatMoney($creditConfig['credit_limit'] ?? 0) }}</li>
                                                        <li><strong>Origen configuración:</strong> {{ ucfirst($creditConfig['source'] ?? 'N/A') }}</li>
                                                        @if(isset($creditConfig['source_name']))
                                                            <li><strong>Configurado por:</strong> {{ $creditConfig['source_name'] }}</li>
                                                        @endif
                                                        @php
                                                            $activeComm2 = ($customerConfig && $customerConfig->commission_percent > 0)
                                                                ? $customerConfig->commission_percent
                                                                : ($sellerConfig->commission_percent ?? 0);
                                                            $activeFreight2 = ($customerConfig && $customerConfig->freight_percent > 0)
                                                                ? $customerConfig->freight_percent
                                                                : ($sellerConfig->freight_percent ?? 0);
                                                            $activeDiff2 = ($customerConfig && $customerConfig->exchange_diff_percent > 0)
                                                                ? $customerConfig->exchange_diff_percent
                                                                : ($sellerConfig->exchange_diff_percent ?? 0);
                                                            $commSrc2 = ($customerConfig && $customerConfig->commission_percent > 0) ? 'Cliente' : 'Vendedor';
                                                            $freightSrc2 = ($customerConfig && $customerConfig->freight_percent > 0) ? 'Cliente' : 'Vendedor';
                                                            $diffSrc2 = ($customerConfig && $customerConfig->exchange_diff_percent > 0) ? 'Cliente' : 'Vendedor';
                                                        @endphp
                                                        <li><strong>Comisión:</strong> {{ $activeComm2 }}% <em>({{ $commSrc2 }})</em></li>
                                                        @if(!auth()->user()->can('system.is_foreign_seller') || auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Super Admin'))
                                                            <li><strong>Flete:</strong> {{ $activeFreight2 }}% <em>({{ $freightSrc2 }})</em></li>
                                                            <li><strong>Diferencial:</strong> {{ $activeDiff2 }}% <em>({{ $diffSrc2 }})</em></li>
                                                        @endif
                                                        @if(isset($creditConfig['usd_payment_discount']) && $creditConfig['usd_payment_discount'] > 0)
                                                            <li><strong>Desc. Pago Divisa:</strong> {{ $creditConfig['usd_payment_discount'] }}%</li>
                                                        @endif
                                                    </ul>

                                                    {{-- Recargos / Pronto Pago - Vendedor --}}
                                                    @if(isset($creditConfig['seller_discount_rules']) && count($creditConfig['seller_discount_rules']) > 0)
                                                        <hr class="my-2">
                                                        <strong class="small">Recargos/Desc. Vendedor:</strong>
                                                        <ul class="mb-0 small">
                                                            @foreach($creditConfig['seller_discount_rules'] as $rule)
                                                                @php $pct = $rule['discount_percentage']; @endphp
                                                                <li>{{ $rule['days_from'] }}-{{ $rule['days_to'] }} días: <span class="{{ $pct >= 0 ? 'text-success' : 'text-danger' }} fw-bold">{{ $pct > 0 ? '+' : '' }}{{ $pct }}%</span></li>
                                                            @endforeach
                                                        </ul>
                                                    @endif

                                                    {{-- Recargos / Pronto Pago - Cliente --}}
                                                    @if(isset($creditConfig['discount_rules']) && count($creditConfig['discount_rules']) > 0)
                                                        <hr class="my-2">
                                                        <strong class="small">Recargos/Desc. Cliente:</strong>
                                                        <ul class="mb-0 small">
                                                            @foreach($creditConfig['discount_rules'] as $rule)
                                                                @php $pct = $rule['discount_percentage']; @endphp
                                                                <li>{{ $rule['days_from'] }}-{{ $rule['days_to'] }} días: <span class="{{ $pct >= 0 ? 'text-success' : 'text-danger' }} fw-bold">{{ $pct > 0 ? '+' : '' }}{{ $pct }}%</span></li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="alert alert-warning">
                                                    <i class="fa fa-exclamation-triangle"></i> 
                                                    <strong>Nota:</strong> Al registrar esta venta a crédito, el estado será PENDIENTE hasta que se realice el pago completo.
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <button class="btn btn-info w-100" wire:click="addPayment" type="button">
                                                    <i class="fa fa-credit-card me-2"></i>REGISTRAR CRÉDITO
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- BILLETERA --}}
                                    @if($selectedPaymentMethod === 'wallet')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="alert alert-warning mb-3 shadow-sm border-warning">
                                                    <h6 class="mb-2"><i class="fa fa-info-circle"></i> Saldo en Billetera Virtual</h6>
                                                    <p class="mb-0">El cliente dispone de <strong>{{ $primaryCurrency->symbol ?? '$' }}{{ formatMoney($customer['wallet_balance'] ?? 0) }}</strong> en su billetera virtual.</p>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Monto a Usar</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text bg-warning text-white border-warning">{{ $primaryCurrency->symbol ?? '$' }}</span>
                                                    <input 
                                                        class="form-control border-warning" 
                                                        oninput="validarInputNumber(this)"
                                                        wire:model.live="walletAmount"
                                                        wire:keydown.enter.prevent='addPayment' 
                                                        type="number" 
                                                        placeholder="0.00">
                                                    <button class="btn btn-warning" type="button" wire:click="addPayment">
                                                        <i class="fa fa-plus-circle me-1"></i> Usar Saldo
                                                    </button>
                                                </div>
                                                @error('walletAmount') <span class="text-danger small d-block mt-n2 mb-2">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    @endif

                                    {{-- BANCO / ZELLE --}}
                                    @if($selectedPaymentMethod === 'bank')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Banco / Plataforma</label>
                                                <select class="form-control" wire:model.live="bankId">
                                                    <option value="">Seleccione un banco</option>
                                                    @forelse($banks as $bank)
                                                        <option value="{{$bank->id}}">
                                                            {{$bank->name}}
                                                            @if($bank->currency_code)
                                                                ({{ $bank->currency_code }})
                                                            @endif
                                                        </option>
                                                    @empty
                                                        <option value="-1" disabled>No hay bancos registrados</option>
                                                    @endforelse
                                                </select>
                                            </div>

                                            @if($isZelleSelected)
                                                {{-- ZELLE FIELDS --}}
                                                <div class="col-12">
                                                    @if($zelleStatusMessage)
                                                        <div class="alert alert-{{ $zelleStatusType == 'danger' ? 'danger' : ($zelleStatusType == 'warning' ? 'warning' : 'success') }} py-2 mb-3">
                                                            <i class="fa fa-{{ $zelleStatusType == 'danger' ? 'times-circle' : ($zelleStatusType == 'warning' ? 'exclamation-triangle' : 'check-circle') }} me-1"></i>
                                                            {{ $zelleStatusMessage }}
                                                        </div>
                                                    @endif
                                                
                                                    <label class="form-label">Referencia (Opcional)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-barcode"></i>
                                                        </span>
                                                        <input type="text" wire:model="zelleReference" class="form-control" placeholder="Referencia">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Nombre del Emisor <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-user"></i>
                                                        </span>
                                                        <input type="text" wire:model.live="zelleSender" class="form-control" placeholder="Nombre del titular">
                                                    </div>
                                                    @error('zelleSender') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Fecha <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-calendar"></i>
                                                        </span>
                                                        <input type="date" wire:model.live="zelleDate" class="form-control">
                                                    </div>
                                                    @error('zelleDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Monto (USD) <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-dollar-sign"></i>
                                                        </span>
                                                        <input type="number" wire:model.live="zelleAmount" class="form-control" placeholder="0.00">
                                                    </div>
                                                    @error('zelleAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Comprobante (Foto) <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-camera"></i>
                                                        </span>
                                                        <input type="file" wire:model="zelleImage" class="form-control" accept="image/*">
                                                    </div>
                                                    @error('zelleImage') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    @if ($zelleImage)
                                                        <div class="mt-2">
                                                            <img src="{{ $zelleImage->temporaryUrl() }}" class="img-thumbnail" style="max-height: 100px;">
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <div class="col-12 mt-3">
                                                    <div class="alert alert-info py-2">
                                                        <small><i class="fa fa-info-circle me-1"></i> El monto a usar en esta venta se calculará automáticamente.</small>
                                                    </div>
                                                    <label class="form-label">Monto a usar en esta venta</label>
                                                    <input class="form-control" type="number" wire:model="paymentAmount" placeholder="Monto a aplicar">
                                                    @error('paymentAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
            
                                                <div class="col-12">
                                                    <button class="btn btn-purple w-100" style="background-color: #6f42c1; color: white;" wire:click="addZellePayment">Agregar Pago Zelle</button>
                                                </div>
                                            @else
                                                @if($isVedBankSelected)
                                                    {{-- VED BANK FIELDS (Detailed) --}}
                                                    <div class="col-12">
                                                        <div class="alert alert-info py-2 mb-0">
                                                            <small><i class="fa fa-info-circle me-1"></i> Se requieren detalles para pagos en Bolívares.</small>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 mt-2">
                                                        @if($bankStatusMessage)
                                                            <div class="alert alert-{{ $bankStatusType }} py-2 mb-3">
                                                                <i class="fa fa-{{ $bankStatusType == 'danger' ? 'times-circle' : ($bankStatusType == 'warning' ? 'exclamation-triangle' : 'check-circle') }} me-1"></i>
                                                                {{ $bankStatusMessage }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Monto del Depósito (Total)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                                            <input 
                                                                class="form-control" 
                                                                oninput="validarInputNumber(this)"
                                                                wire:model.live.debounce.500ms="bankGlobalAmount" 
                                                                type="number"
                                                                placeholder="Monto total">
                                                        </div>
                                                        @error('bankGlobalAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Monto a Usar</label>
                                                        <input 
                                                            class="form-control" 
                                                            oninput="validarInputNumber(this)"
                                                            wire:model.live="bankAmount" 
                                                            type="number"
                                                            placeholder="0.00">
                                                        @error('bankAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Referencia (Últimos 5 caracteres)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                            <input 
                                                                class="form-control" 
                                                                wire:model.live="bankReference" 
                                                                type="text"
                                                                maxlength="5"
                                                                minlength="5"
                                                                placeholder="Ej: 12345">
                                                        </div>
                                                        @error('bankReference') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Fecha de Pago</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                            <input type="date" wire:model.live="bankDate" class="form-control">
                                                        </div>
                                                        @error('bankDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Comprobante (Foto) <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-camera"></i></span>
                                                            <input type="file" wire:model="bankImage" class="form-control" accept="image/*">
                                                        </div>
                                                        @error('bankImage') <span class="text-danger small">{{ $message }}</span> @enderror
                                                        @if ($bankImage)
                                                            <div class="mt-2">
                                                                <img src="{{ $bankImage->temporaryUrl() }}" class="img-thumbnail" style="max-height: 100px;">
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label">Nota (Opcional)</label>
                                                        <input class="form-control" wire:model.live="bankNote" type="text" placeholder="Observaciones...">
                                                    </div>

                                                    <div class="col-12">
                                                        <button class="btn btn-primary w-100" wire:click="addBankPayment" type="button">
                                                            <i class="fa fa-plus-circle me-2"></i>Agregar Pago Detallado
                                                        </button>
                                                    </div>
                                                @else
                                                    {{-- STANDARD BANK FIELDS --}}
                                                    <div class="col-md-6">
                                                        <label class="form-label">Monto</label>
                                                        <input 
                                                            class="form-control" 
                                                            oninput="validarInputNumber(this)"
                                                            wire:model.live="bankAmount" 
                                                            type="number"
                                                            placeholder="0.00">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">N°. Cuenta</label>
                                                        <input 
                                                            class="form-control" 
                                                            oninput="validarInputNumber(this)"
                                                            wire:model.live="bankAccountNumber" 
                                                            type="text"
                                                            placeholder="Número de cuenta">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">N°. Depósito/Referencia (Últimos 5 caracteres)</label>
                                                        <input 
                                                            class="form-control" 
                                                            wire:model.live="bankDepositNumber" 
                                                            type="text"
                                                            maxlength="5"
                                                            minlength="5"
                                                            placeholder="Ej: 12345">
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn btn-primary w-100" wire:click="addBankPayment" type="button">
                                                            <i class="fa fa-plus-circle me-2"></i>Agregar Pago con Banco
                                                        </button>
                                                    </div>
                                                @endif
                                    @endif
                                </div>
                            @endif
                            </div>
                        </div>
                    </div>

                        {{-- Columna Derecha: Pagos Agregados y Totales --}}
                        <div class="col-md-6">
                            {{-- Tabla de Pagos Agregados --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-list me-2"></i>Pagos Agregados</h6>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    @if (count($payments) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Método</th>
                                                        <th>Monto</th>
                                                        <th>Equiv.</th>
                                                        <th width="50"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($payments as $index => $payment)
                                                        <tr wire:key="payment-{{ $index }}">
                                                            <td>
                                                                @if($payment['method'] === 'cash')
                                                                    <span class="badge bg-success">
                                                                        <i class="fa fa-money-bill-wave"></i> Efectivo
                                                                    </span>
                                                                    <br><small class="text-muted">{{ $payment['currency'] }}</small>
                                                                @elseif($payment['method'] === 'bank')
                                                                    <span class="badge bg-primary">
                                                                        <i class="fa fa-university"></i> Banco
                                                                    </span>
                                                                    <br><small class="text-muted">{{ $payment['bank_name'] ?? 'N/A' }}</small>
                                                                    @if(isset($payment['bank_file_url']) && $payment['bank_file_url'])
                                                                        <br><a href="{{ $payment['bank_file_url'] }}" target="_blank"><i class="fa fa-image"></i> Ver</a>
                                                                    @endif
                                                                @elseif($payment['method'] === 'zelle')
                                                                    <span class="badge bg-info text-white" style="background-color: #6f42c1 !important;">
                                                                        <i class="fa fa-mobile-alt"></i> Zelle
                                                                    </span>
                                                                    <br><small class="text-muted">{{ $payment['zelle_sender'] ?? 'N/A' }}</small>
                                                                    @if(isset($payment['zelle_file_url']) && $payment['zelle_file_url'])
                                                                        <br><a href="{{ $payment['zelle_file_url'] }}" target="_blank"><i class="fa fa-image"></i> Ver</a>
                                                                    @endif
                                                                @elseif($payment['method'] === 'wallet')
                                                                    <span class="badge bg-warning text-dark">
                                                                        <i class="fa fa-wallet"></i> Billetera
                                                                    </span>
                                                                @endif

                                                            </td>
                                                            <td>
                                                                <strong>{{ $payment['symbol'] }}{{ formatMoney($payment['amount']) }}</strong>
                                                                <br><small class="text-muted">{{ $payment['currency'] }}</small>
                                                            </td>
                                                            <td>
                                                                <strong>{{ $primaryCurrency->symbol }}{{ formatMoney($payment['amount_in_primary_currency']) }}</strong>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-danger btn-sm" wire:click="removePayment({{ $index }})" title="Eliminar">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-5">
                                            <i class="fa fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                            <p>No hay pagos agregados</p>
                                            <small>Seleccione un método de pago y agregue el monto</small>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Totales --}}
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                        <span class="text-muted">Total Pagado:</span>
                                        <span class="fs-5 fw-bold text-success">{{ $symbol }}{{ formatMoney($this->totalPaidDisplay) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Monto Restante:</span>
                                        <span class="fs-5 fw-bold {{ $remainingAmount > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $symbol }}{{ formatMoney($remainingAmount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Vuelto y Distribución --}}
                            @if (count($payments) > 0 && ($change > 0 || count($changeDistribution) > 0))
                                <div class="card">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="mb-0 text-warning"><i class="fa fa-exchange-alt me-2"></i>Vuelto</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning mb-3">
                                            <strong>Vuelto Total: {{ $symbol }}{{ formatMoney($change) }}</strong>
                                        </div>

                                        {{-- Distribuir Vuelto --}}
                                        <h6 class="mb-2">Distribuir Vuelto:</h6>
                                        <div class="row g-2 mb-3">
                                            <div class="col-5">
                                                <select class="form-control form-control-sm" wire:model.live="selectedChangeCurrency">
                                                    @foreach ($currencies as $currency)
                                                        <option value="{{ $currency->code }}">
                                                            {{ $currency->label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm" 
                                                       wire:model.live="selectedChangeAmount" 
                                                       placeholder="Monto"
                                                       step="0.01">
                                            </div>
                                            <div class="col-3">
                                                <button class="btn btn-success btn-sm w-100" wire:click="addChangeInCurrency">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Tabla de Vueltos Distribuidos --}}
                                        @if (count($changeDistribution) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Moneda</th>
                                                            <th>Monto</th>
                                                            <th>Equiv.</th>
                                                            <th width="40"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($changeDistribution as $index => $changeItem)
                                                            <tr>
                                                                <td>{{ $changeItem['currency'] }}</td>
                                                                <td>{{ $changeItem['symbol'] }}{{ formatMoney($changeItem['amount']) }}</td>
                                                                <td>{{ $symbol }}{{ formatMoney($changeItem['amount_in_primary_currency']) }}</td>
                                                                <td>
                                                                    <button class="btn btn-danger btn-sm" wire:click="removeChangeDistribution({{ $index }})">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif

                                        {{-- Vuelto Restante --}}
                                        @php
                                            $remainingChange = $this->getRemainingChangeToAssign();
                                        @endphp
                                        @if ($remainingChange > 0)
                                            <div class="alert alert-warning py-2 mb-0">
                                                <small>
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    Vuelto pendiente: {{ $symbol }}{{ formatMoney($remainingChange) }}
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer bg-light">
                    <button class="btn btn-secondary fs-6" type="button" data-dismiss="modal" style="background-color: #6c757d; border-color: #6c757d;">
                        <i class="fa fa-times me-2"></i>Cerrar
                    </button>
                    
                    <button class="btn btn-primary fs-6" wire:click.prevent='Store' type="button" 
                        style="background-color: #007bff; border-color: #007bff;"
                        wire:loading.attr="disabled" {{ floatval($totalCart) == 0 ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="Store">
                            <i class="fa fa-check me-2"></i>{{ $payType == 2 ? 'Registrar Crédito' : 'Registrar Venta' }}
                        </span>
                        <span wire:loading wire:target="Store">
                            <i class="fa fa-spinner fa-spin me-2"></i>Registrando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
