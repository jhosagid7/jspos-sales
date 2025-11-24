<div>
    <div wire:ignore.self class="modal fade" id="modalCash" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header {{ $payType == 1 ? 'bg-dark' : 'bg-info' }}">
                    <h5 class="modal-title">{{ $payTypeName }}</h5>
                    <button class="py-0 btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Obtener el símbolo de la moneda principal -->
                    @php
                        $primaryCurrency = collect($currencies)->firstWhere('is_primary', 1);
                        $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                    @endphp

                    <!-- Resumen del carrito -->
                    <div class="mb-1 light-card balance-card align-items-center">
                        <h6 class="mb-0 f-w-400 f-18">Artículos:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">{{ $itemsCart }}</span>
                        </div>
                    </div>
                    <div class="mb-1 light-card balance-card align-items-center">
                        <h6 class="mb-0 f-w-400 f-18">Subtotal:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">{{ $symbol }}{{ number_format($subtotalCart, 2) }}</span>
                        </div>
                    </div>
                    <div class="light-card balance-card align-items-center border-bottom">
                        <h6 class="mb-0 f-w-400 f-18">I.V.A.:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">{{ $symbol }}{{ number_format($ivaCart, 2) }}</span>
                        </div>
                    </div>
                    <div class="light-card balance-card align-items-center">
                        <h6 class="f-w-700 f-18 mb-0 {{ $payType == 1 ? 'txt-dark' : 'txt-info' }}">TOTAL:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">{{ $symbol }}{{ number_format($totalCart, 2) }}</span>
                        </div>
                    </div>

                    @if ($payType == 1)
                        <!-- Campo para ingresar el monto y selección de moneda -->
                        <div class="mt-4 row">
                            <!-- Campo para ingresar el monto -->
                            <div class="col-md-6">
                                <input class="form-control" oninput="validarInputNumber(this)"
                                    wire:model.live.debounce.750ms="paymentAmount"
                                    wire:keydown.enter.prevent='addPayment' type="number" placeholder="Monto"
                                    id="inputCash">
                            </div>

                            <!-- Selección de moneda cargada dinámicamente -->
                            <div class="col-md-6">
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
                        </div>

                        <!-- Botón para agregar el pago -->
                        <div class="mt-3">
                            <button class="btn btn-primary" wire:click="addPayment" type="button">
                                Agregar Pago
                            </button>
                        </div>

                        <!-- Tabla para mostrar los pagos realizados -->
                        @if (count($payments) > 0)
                            <div class="mt-4">
                                <h6 class="mb-2 f-w-400 f-16">Pagos Realizados:</h6>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Moneda</th>
                                            <th>Monto</th>
                                            <th>Conversión ({{ $primaryCurrency->label ?? 'Moneda Principal' }})</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($payments as $index => $payment)
                                            <tr>
                                                <td>{{ $payment['currency'] }}</td>
                                                <td>{{ $payment['symbol'] }}{{ number_format($payment['amount'], 2) }}
                                                </td>
                                                <td>{{ $symbol }}{{ number_format($payment['amount_in_primary_currency'], 2) }}
                                                </td>
                                                <td>
                                                    <button class="btn btn-danger btn-sm"
                                                        wire:click="removePayment({{ $index }})">
                                                        Eliminar
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <!-- Mostrar monto total en Moneda Principal -->
                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 f-w-400 f-16">Total en Moneda Principal:</h6>
                            <div class="ms-auto text-end">
                                <span
                                    class="f-20 txt-primary">{{ $symbol }}{{ number_format($totalInPrimaryCurrency, 2) }}</span>
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 f-w-400 f-16">Monto Restante:</h6>
                            <div class="ms-auto text-end">
                                <span
                                    class="f-16 txt-info">{{ $symbol }}{{ number_format($remainingAmount, 2) }}</span>
                            </div>
                        </div>

                        <!-- Mostrar cambio disponible y distribución en múltiples monedas -->
                        @if ($change > 0 || count($changeDistribution) > 0)
                            <div class="mt-4">
                                <h6 class="mb-2 f-w-400 f-16">Vuelto Disponible:</h6>
                                <div class="light-card balance-card align-items-center">
                                    <span class="f-16 txt-warning">{{ $symbol }}{{ number_format($change, 2) }}</span>
                                </div>

                                <!-- Sección para distribuir el vuelto en múltiples monedas -->
                                <div class="mt-3">
                                    <h6 class="mb-2 f-w-400 f-14">Distribuir Vuelto:</h6>
                                    
                                    <div class="row">
                                        <!-- Selector de moneda -->
                                        <div class="col-md-5">
                                            <select class="form-control form-control-sm" wire:model.live="selectedChangeCurrency">
                                                @foreach ($currencies as $currency)
                                                    <option value="{{ $currency->code }}">
                                                        {{ $currency->label }} ({{ $currency->symbol }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <!-- Monto del vuelto -->
                                        <div class="col-md-4">
                                            <input type="number" class="form-control form-control-sm" 
                                                   wire:model.live="selectedChangeAmount" 
                                                   placeholder="Monto"
                                                   step="0.01">
                                        </div>
                                        
                                        <!-- Botón agregar -->
                                        <div class="col-md-3">
                                            <button class="btn btn-success btn-sm w-100" 
                                                    wire:click="addChangeInCurrency">
                                                <i class="fa fa-plus"></i> Agregar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tabla de vueltos distribuidos -->
                                    @if (count($changeDistribution) > 0)
                                        <div class="mt-3">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Moneda</th>
                                                        <th>Monto</th>
                                                        <th>Equiv. {{ $primaryCurrency->label ?? 'Principal' }}</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($changeDistribution as $index => $changeItem)
                                                        <tr>
                                                            <td>{{ $changeItem['currency'] }}</td>
                                                            <td>{{ $changeItem['symbol'] }}{{ number_format($changeItem['amount'], 2) }}</td>
                                                            <td>{{ $symbol }}{{ number_format($changeItem['amount_in_primary_currency'], 2) }}</td>
                                                            <td>
                                                                <button class="btn btn-danger btn-sm" 
                                                                        wire:click="removeChangeDistribution({{ $index }})">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    <!-- Vuelto restante por asignar -->
                                    @php
                                        $remainingChange = $this->getRemainingChangeToAssign();
                                    @endphp
                                    @if ($remainingChange > 0)
                                        <div class="mt-2 alert alert-warning py-2">
                                            <small>
                                                <i class="fa fa-exclamation-triangle"></i>
                                                Vuelto pendiente de asignar: {{ $symbol }}{{ number_format($remainingChange, 2) }}
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Footer del modal -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" wire:click.prevent='Store' type="button"
                        wire:loading.attr="disabled" {{ floatval($totalCart) == 0 ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="Store">Registrar</span>
                        <span wire:loading wire:target="Store">Registrando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
