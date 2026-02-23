<div>
    <div wire:ignore.self class="modal fade" id="modalPayment" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-cash-register me-2"></i>
                        REGISTRAR PAGO
                        @if($customerName)
                            <small class="d-block mt-1" style="font-size: 0.7rem;">Cliente: {{ $customerName }}</small>
                        @endif
                    </h5>
                    <button class="btn-close btn-close-white" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalPayment').modal('hide')"></button>
                </div>

                <div class="modal-body">
                    @php
                        // First try to find currency matching the Debt Currency Code
                        $currentCurrency = collect($currencies)->firstWhere('code', $currencyCode);
                        if (!$currentCurrency) {
                             $primaryCurrency = collect($currencies)->firstWhere('is_primary', 1);
                             $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                        } else {
                             $symbol = $currentCurrency->symbol;
                        }
                    @endphp

                    <div class="row">
                        {{-- Left Column: Summary & Method --}}
                        <div class="col-md-6">
                            {{-- Summary --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-file-invoice-dollar me-2"></i>Resumen</h6>
                                </div>
                                <div class="card-body">
                                    {{-- Adjustment Alert (Early Payment) --}}
                                    {{-- Show if Adjustment exists AND we are NOT eligible for USD Discount (e.g. mixed payments) --}}
                                    @if($adjustment && !empty($payments) && !$usdAdjustment)
                                        @php
                                            $adjType = $adjustment['rule_type'] ?? 'early_payment';
                                            // Dynamic color: Green/Warning if applied, Secondary if unchecked
                                            $adjColor = $applyAdjustment ? ($adjType == 'early_payment' ? 'success' : 'warning') : 'secondary';
                                            $adjTitle = $adjType == 'early_payment' ? 'Descuento' : 'Recargo';
                                            $adjIcon = $adjType == 'early_payment' ? 'arrow-down' : 'arrow-up';
                                        @endphp
                                        <div class="alert alert-{{ $adjColor }} mb-3 p-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-{{ $adjIcon }} fa-2x me-3"></i>
                                                    <div>
                                                        <h6 class="alert-heading fw-bold mb-0" style="font-size: 0.9rem;">
                                                            {{ "¡Aplica $adjTitle!" }}
                                                        </h6>
                                                        <div class="fw-bold">
                                                            {{ $adjustment['percentage'] }}% ({{ $symbol }}{{ number_format($adjustment['amount'], 2) }})
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" wire:model.live="applyAdjustment" wire:click="toggleAdjustment" id="toggleAdjustment">
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- USD Discount Alert --}}
                                    {{-- Show ONLY if Eligible for USD Discount (No VED payments, etc) --}}
                                    @if($allowDiscounts && $usdAdjustment && !$applyAdjustment)
                                        <div class="alert alert-{{ $applyUsdDiscount ? 'info' : 'secondary' }} mb-3 p-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-percentage fa-2x me-3"></i>
                                                    <div>
                                                        <h6 class="alert-heading fw-bold mb-0" style="font-size: 0.9rem;">
                                                            Desc. Pago Divisa (Al Liquidar)
                                                        </h6>
                                                        <div class="fw-bold">
                                                            {{ $usdPaymentDiscountPercent }}% ({{ $symbol }}{{ number_format($usdAdjustment['amount'] ?? $fixedUsdDiscountAmount, 2) }})
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" wire:model.live="applyUsdDiscount" wire:click="toggleUsdDiscount" id="toggleUsdDiscount">
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Calculation Details --}}
                                    <ul class="list-group list-group-flush mb-2">
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                            <span>Deuda Actual:</span>
                                            <span class="fw-bold">{{ $symbol }}{{ number_format($totalToPay, 2) }}</span>
                                        </li>
                                        
                                        {{-- Only show Adjustment if explicitly applied by logic OR if it's the eligible discount (strikethrough if disabled) --}}
                                        @if($adjustment && !$usdAdjustment)
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 {{ $applyAdjustment ? 'text-muted' : 'text-muted text-decoration-line-through' }}">
                                                <span>{{ $adjustment['rule_type'] == 'early_payment' ? 'Descuento' : 'Recargo' }} ({{ $adjustment['percentage'] }}%):</span>
                                                <span class="fw-bold {{ $applyAdjustment ? 'text-danger' : '' }}">
                                                    -{{ $symbol }}{{ number_format($adjustment['amount'], 2) }}
                                                </span>
                                            </li>
                                        @endif
                                        
                                        @if($usdAdjustment)
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 {{ $applyUsdDiscount ? 'text-muted' : 'text-muted text-decoration-line-through' }}">
                                                <span>Desc. Divisa ({{ $usdAdjustment['percentage'] }}%):</span>
                                                <span class="fw-bold {{ $applyUsdDiscount ? 'text-danger' : '' }}">
                                                    -{{ $symbol }}{{ number_format($usdAdjustment['amount'], 2) }}
                                                </span>
                                            </li>
                                        @endif
                                        
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-top">
                                            <span class="fs-5 fw-bold text-primary">TOTAL A PAGAR:</span>
                                            <span class="fs-5 fw-bold text-primary">
                                                @php
                                                    $finalTotal = $totalToPay;
                                                    if ($adjustment && $applyAdjustment) {
                                                        $finalTotal -= $adjustment['amount']; 
                                                    }
                                                    if ($usdAdjustment && $applyUsdDiscount) {
                                                        $finalTotal -= $usdAdjustment['amount'];
                                                    }
                                                @endphp
                                                {{ $symbol }}{{ number_format($finalTotal, 2) }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            {{-- Method Selector --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-credit-card me-2"></i>Método de Pago</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button type="button" wire:click="$set('paymentMethod', 'cash')"
                                                class="btn w-100 {{ $paymentMethod === 'cash' ? 'btn-success' : 'btn-outline-success' }}"
                                                style="padding: 15px 10px;">
                                                <i class="fa fa-money-bill-wave fa-2x d-block mb-2"></i>
                                                <small class="d-block">Efectivo</small>
                                            </button>
                                        </div>
                                        @module('module_advanced_payments')
                                        <div class="col-6">
                                            <button type="button" wire:click="$set('paymentMethod', 'bank')"
                                                class="btn w-100 {{ $paymentMethod === 'bank' ? 'btn-primary' : 'btn-outline-primary' }}"
                                                style="padding: 15px 10px;">
                                                <i class="fa fa-university fa-2x d-block mb-2"></i>
                                                <small class="d-block">Banco / Zelle</small>
                                            </button>
                                        </div>
                                        @endmodule

                                    </div>
                                </div>
                            </div>

                            {{-- Input Form --}}
                            <div class="card">
                                <div class="card-body">
                                    {{-- CASH --}}
                                    @if($paymentMethod === 'cash')
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Monto</label>
                                                <input class="form-control" type="number" wire:model="amount" placeholder="0.00" wire:keydown.enter="addPayment">
                                                @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Moneda</label>
                                                <select class="form-control" wire:model.live="paymentCurrency">
                                                    @foreach($currencies as $curr)
                                                        <option value="{{ $curr->code }}">{{ $curr->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- CASH VED EXTENDED FIELDS --}}
                                            @if(in_array($paymentCurrency, ['VED', 'VES']))
                                                <div class="col-md-6">
                                                    <label class="form-label">Fecha de Pago <small class="text-danger">(Requerido para VED)</small></label>
                                                    <input type="date" class="form-control" wire:model.live="paymentDate">
                                                    @error('paymentDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Tasa (Opcional)</label>
                                                    <input type="number" step="0.000001" class="form-control" wire:model.live="customExchangeRate" placeholder="Usar del sistema">
                                                    @if($customExchangeRate) 
                                                        <small class="text-info">Tasa personalizada aplicada</small> 
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="col-12">
                                                <button class="btn btn-success w-100" wire:click="addPayment">Agregar Pago</button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- BANK / ZELLE --}}
                                    @if($paymentMethod === 'bank')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Banco / Plataforma</label>
                                                <select class="form-control" wire:model.live="bankId">
                                                    <option value="">Seleccione...</option>
                                                    @foreach($banks as $b)
                                                        <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->currency_code }})</option>
                                                    @endforeach
                                                </select>
                                                @error('bankId') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>

                                            @if($isZelleSelected)
                                                {{-- ZELLE FIELDS --}}
                                                
                                                @if($zelleStatusMessage)
                                                    <div class="col-12">
                                                        <div class="alert alert-{{ $zelleStatusType }} mb-0">
                                                            <i class="fa fa-{{ $zelleStatusType == 'danger' ? 'exclamation-circle' : 'info-circle' }} me-1"></i>
                                                            {{ $zelleStatusMessage }}
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="col-12">
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
                                                    <label class="form-label">Monto <span class="text-danger">*</span></label>
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
                                                    <input class="form-control" type="number" wire:model="amount" placeholder="Monto a aplicar">
                                                    @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
            
                                                <div class="col-12">
                                                    <button class="btn btn-purple w-100" style="background-color: #6f42c1; color: white;" wire:click="addPayment">Agregar Pago Zelle</button>
                                                </div>

                                            @else
                                                @if($isVedBankSelected)
                                                    {{-- VED BANK FIELDS (Detailed) --}}
                                                    <div class="col-12">
                                                        <div class="alert alert-info py-2 mb-0">
                                                            <small><i class="fa fa-info-circle me-1"></i> Se requieren detalles para pagos en Bolívares.</small>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Monto a Usar</label>
                                                        <input 
                                                            class="form-control" 
                                                            oninput="validarInputNumber(this)"
                                                            wire:model.live="amount" 
                                                            type="number"
                                                            placeholder="0.00">
                                                        @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>

                                                    <div class="col-12">
                                                        @if($bankStatusMessage)
                                                            <div class="alert alert-{{ $bankStatusType }} mb-2">
                                                                <i class="fa fa-{{ $bankStatusType == 'danger' ? 'exclamation-circle' : 'info-circle' }} me-1"></i>
                                                                {{ $bankStatusMessage }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Monto del Depósito (Total)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                                            <input 
                                                                class="form-control" 
                                                                oninput="validarInputNumber(this)"
                                                                wire:model.live="bankGlobalAmount" 
                                                                type="number"
                                                                placeholder="Monto Original">
                                                        </div>
                                                        @error('bankGlobalAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">Referencia (Últimos 5 dígitos)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                            <input 
                                                                class="form-control" 
                                                                wire:model.live="bankReference" 
                                                                type="text"
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
                                                        <label class="form-label">Tasa (Opcional)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                                            <input type="number" step="0.000001" class="form-control" wire:model.live="customExchangeRate" placeholder="Usar del sistema">
                                                        </div>
                                                        @if($customExchangeRate) 
                                                            <small class="text-info d-block mt-1">Tasa personalizada aplicada</small> 
                                                        @endif
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
                                                        <button class="btn btn-primary w-100" wire:click="addPayment" type="button">
                                                            <i class="fa fa-plus-circle me-2"></i>Agregar Pago Detallado
                                                        </button>
                                                    </div>
                                                @else
                                                    {{-- STANDARD BANK FIELDS --}}
                                                    <div class="col-md-6">
                                                        <label class="form-label">Monto</label>
                                                        <input class="form-control" type="number" wire:model="amount" placeholder="0.00">
                                                        @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">N° Cuenta</label>
                                                        <input class="form-control" type="text" wire:model="accountNumber">
                                                        @error('accountNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Referencia</label>
                                                        <input class="form-control" type="text" wire:model="depositNumber">
                                                        @error('depositNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn btn-primary w-100" wire:click="addPayment">Agregar Pago</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>

                        {{-- Right Column: List & Totals --}}
                        <div class="col-md-6">
                            {{-- Payments List --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-list me-2"></i>Pagos Agregados</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 300px;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Método</th>
                                                    <th>Monto</th>
                                                    <th>Equiv.</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($payments as $index => $p)
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-secondary">{{ strtoupper($p['method']) }}</span>
                                                            @if($p['method'] == 'bank') <br><small>{{ $p['bank_name'] }}</small> @endif
                                                            @if($p['method'] == 'zelle') 
                                                                <br><small>Zelle: {{ $p['zelle_sender'] }}</small>
                                                                @if(isset($p['zelle_file_url']) && $p['zelle_file_url'])
                                                                    <br><a href="{{ $p['zelle_file_url'] }}" target="_blank"><i class="fa fa-image"></i> Ver</a>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td>{{ $p['symbol'] }}{{ number_format($p['amount'], 2) }}</td>
                                                        <td>{{ $symbol }}{{ number_format($p['amount_in_primary'], 2) }}</td>
                                                        <td>
                                                            <button class="btn btn-danger btn-sm" wire:click="removePayment({{ $index }})">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center py-3 text-muted">No hay pagos agregados</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Totals --}}
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Pagado:</span>
                                        <span class="fw-bold text-success">{{ $symbol }}{{ number_format($totalPaid, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Restante:</span>
                                        <span class="fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $symbol }}{{ number_format($remaining, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Change --}}
                            @if($change > 0)
                                <div class="card">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="mb-0 text-dark"><i class="fa fa-exchange-alt me-2"></i>Vuelto: {{ $symbol }}{{ number_format($change, 2) }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-6 col-md-5">
                                                <select class="form-control form-control-sm" wire:model="selectedChangeCurrency">
                                                    <option value="">Moneda...</option>
                                                    @foreach($currencies as $c)
                                                        <option value="{{ $c->code }}">{{ $c->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <input type="number" class="form-control form-control-sm" wire:model="selectedChangeAmount" placeholder="Monto">
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <button class="btn btn-success btn-sm w-100" wire:click="addChangeDistribution"><i class="fa fa-plus"></i></button>
                                            </div>
                                        </div>

                                        <ul class="list-group mt-2">
                                            @foreach($changeDistribution as $idx => $cd)
                                                <li class="list-group-item d-flex justify-content-between align-items-center p-1">
                                                    <small>{{ $cd['symbol'] }}{{ number_format($cd['amount'], 2) }} {{ $cd['currency'] }}</small>
                                                    <button class="btn btn-xs btn-danger" wire:click="removeChangeDistribution({{ $idx }})">&times;</button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#modalPayment').modal('hide')">Cancelar</button>
                    
                    @if($canPay)
                        <button type="button" class="btn btn-primary" wire:click="submit('pay')" {{ ($remaining > 0.01 && !$allowPartialPayment) ? 'disabled' : '' }}>
                            <i class="fa fa-check me-2"></i>Confirmar Pago
                        </button>
                    @endif

                    @if($canUpload)
                        <button type="button" class="btn btn-warning text-dark" wire:click="submit('upload')" {{ ($remaining > 0.01 && !$allowPartialPayment) ? 'disabled' : '' }}>
                            <i class="fa fa-cloud-upload-alt me-2"></i>Subir Pago
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script>
        window.addEventListener('show-payment-modal', event => {
            $('#modalPayment').modal('show');
        });
        
        window.addEventListener('close-payment-modal', event => {
            $('#modalPayment').modal('hide');
        });
    </script>
</div>
