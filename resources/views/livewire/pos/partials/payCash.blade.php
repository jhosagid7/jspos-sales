<div>
    <div wire:ignore.self class="modal fade" id="modalCash" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                {{-- Header --}}
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-cash-register me-2"></i>
                        PAGO DE VENTA
                    </h5>
                    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Obtener el símbolo de la moneda principal --}}
                    @php
                        $primaryCurrency = collect($currencies)->firstWhere('is_primary', 1);
                        $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                    @endphp

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
                                        <span class="fw-bold">{{ $symbol }}{{ number_format($subtotalCart, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                        <span class="text-muted">I.V.A.:</span>
                                        <span class="fw-bold">{{ $symbol }}{{ number_format($ivaCart, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fs-5 fw-bold text-primary">TOTAL:</span>
                                        <span class="fs-5 fw-bold text-primary">{{ $symbol }}{{ number_format($totalCart, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Selector de Método de Pago --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-credit-card me-2"></i>Método de Pago</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        {{-- Efectivo --}}
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

                                        {{-- Banco --}}
                                        <div class="col-4">
                                            <button 
                                                type="button"
                                                wire:click="$set('selectedPaymentMethod', 'bank')"
                                                class="btn w-100 {{ $selectedPaymentMethod === 'bank' ? 'btn-primary' : 'btn-outline-primary' }}"
                                                style="padding: 15px 10px;">
                                                <i class="fa fa-university fa-2x d-block mb-2"></i>
                                                <small class="d-block">Banco</small>
                                            </button>
                                        </div>


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

                                    {{-- BANCO --}}
                                    @if($selectedPaymentMethod === 'bank')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Banco</label>
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
                                                <label class="form-label">N°. Depósito/Referencia</label>
                                                <input 
                                                    class="form-control" 
                                                    oninput="validarInputNumber(this)"
                                                    wire:model.live="bankDepositNumber" 
                                                    type="text"
                                                    placeholder="Número de depósito o referencia">
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary w-100" wire:click="addBankPayment" type="button">
                                                    <i class="fa fa-plus-circle me-2"></i>Agregar Pago con Banco
                                                </button>
                                            </div>
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
                                                        <tr>
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
                                                                @endif

                                                            </td>
                                                            <td>
                                                                <strong>{{ $payment['symbol'] }}{{ number_format($payment['amount'], 2) }}</strong>
                                                                <br><small class="text-muted">{{ $payment['currency'] }}</small>
                                                            </td>
                                                            <td>
                                                                <strong>{{ $symbol }}{{ number_format($payment['amount_in_primary_currency'], 2) }}</strong>
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
                                        <span class="fs-5 fw-bold text-success">{{ $symbol }}{{ number_format($totalInPrimaryCurrency, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Monto Restante:</span>
                                        <span class="fs-5 fw-bold {{ $remainingAmount > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $symbol }}{{ number_format($remainingAmount, 2) }}
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
                                            <strong>Vuelto Total: {{ $symbol }}{{ number_format($change, 2) }}</strong>
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
                                                                <td>{{ $changeItem['symbol'] }}{{ number_format($changeItem['amount'], 2) }}</td>
                                                                <td>{{ $symbol }}{{ number_format($changeItem['amount_in_primary_currency'], 2) }}</td>
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
                                                    Vuelto pendiente: {{ $symbol }}{{ number_format($remainingChange, 2) }}
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
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times me-2"></i>Cerrar
                    </button>
                    <button class="btn btn-primary" wire:click.prevent='Store' type="button"
                        wire:loading.attr="disabled" {{ floatval($totalCart) == 0 ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="Store">
                            <i class="fa fa-check me-2"></i>Registrar Venta
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
