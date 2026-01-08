<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header bg-primary p-1">
                    <h5 class="txt-light text-center">Corte de Caja</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-3">
                            <div class="card border-l-primary border-3">

                                <div class="card-body">

                                    <span class="f-14"><b>Usuario</b></span>
                                    <select wire:model="user_id" class="form-select form-control-sm">
                                        <option value="0">Todos los usuarios</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>


                                    <div class="mt-3">
                                        <span class="f-14"><b>Fecha desde</b></span>
                                        <div class="input-group datepicker">
                                            <input class="form-control flatpickr-input active" id="dateFrom"
                                                type="text" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <span class="f-14"><b>Hasta</b></span>
                                        <div class="input-group datepicker">
                                            <input class="form-control flatpickr-input active" id="dateTo"
                                                type="text" autocomplete="off">
                                        </div>
                                    </div>



                                    <div class="mt-5">
                                        <button wire:click.prevent="getSalesBetweenDates"
                                            class="btn btn-outline-primary w-100" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="getSalesBetweenDates">
                                                Ventas por Fecha
                                            </span>
                                            <span wire:loading wire:target="getSalesBetweenDates">
                                                Consultando...
                                            </span>
                                        </button>
                                        <button wire:click.prevent="getDailySales"
                                            class="btn btn-outline-primary w-100 mt-3" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="getDailySales">
                                                Ventas del Día
                                            </span>
                                            <span wire:loading wire:target="getDailySales">
                                                Consultando...
                                            </span>

                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-12 col-md-9">
                            @php
                                $primaryCurrency = collect($currencies)->firstWhere('is_primary', 1);
                                $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                            @endphp

                            {{-- VENTAS DEL DÍA SECTION --}}
                            <div class="card border-info border-2 mb-3">
                                <div class="card-header bg-info p-2">
                                    <h5 class="m-0 text-white">
                                        <i class="icofont icofont-money"></i> VENTAS DEL DÍA
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        {{-- Efectivo --}}
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 border-primary">
                                                <div class="card-header bg-light-primary p-2">
                                                    <h6 class="mb-0 text-primary">
                                                        <i class="icofont icofont-bill"></i> Efectivo
                                                    </h6>
                                                </div>
                                                <div class="card-body p-2">
                                                    @if (!empty($salesByCurrency['cash']))
                                                        <table class="table table-sm table-borderless mb-0">
                                                            @foreach ($salesByCurrency['cash'] as $currencyCode => $amount)
                                                                @php
                                                                    $curr = collect($currencies)->firstWhere('code', $currencyCode);
                                                                    $currSymbol = $curr ? $curr->symbol : $currencyCode;
                                                                    $label = $curr ? $curr->label . ' (' . $currencyCode . ')' : $currencyCode;
                                                                @endphp
                                                                <tr>
                                                                    <td class="text-muted">{{ $label }}:</td>
                                                                    <td class="text-end fw-bold">{{ $currSymbol }}{{ number_format($amount, 2) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </table>
                                                    @else
                                                        <p class="text-muted mb-0 text-center">Sin movimientos</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>



                                        {{-- Banco --}}
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 border-primary">
                                                <div class="card-header bg-light-primary p-2">
                                                    <h6 class="mb-0 text-primary">
                                                        <i class="icofont icofont-bank-alt"></i> Banco
                                                    </h6>
                                                </div>
                                                <div class="card-body p-2">
                                                    @if (!empty($salesByCurrency['deposit']))
                                                        @foreach ($salesByCurrency['deposit'] as $key => $value)
                                                            @if(is_array($value))
                                                                {{-- Breakdown by Bank Name --}}
                                                                <div class="mb-2 border-bottom pb-1">
                                                                    <small class="fw-bold text-dark">{{ $key }}</small>
                                                                    <table class="table table-sm table-borderless mb-0">
                                                                        @foreach ($value as $currencyCode => $amount)
                                                                            @php
                                                                                $curr = collect($currencies)->firstWhere('code', $currencyCode);
                                                                                $currSymbol = $curr ? $curr->symbol : $currencyCode;
                                                                                $label = $curr ? $curr->label . ' (' . $currencyCode . ')' : $currencyCode;
                                                                            @endphp
                                                                            <tr>
                                                                                <td class="text-muted ps-2">{{ $label }}:</td>
                                                                                <td class="text-end fw-bold">{{ $currSymbol }}{{ number_format($amount, 2) }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </table>
                                                                </div>
                                                            @else
                                                                {{-- Fallback for old data without bank name --}}
                                                                @php
                                                                    $currencyCode = $key;
                                                                    $amount = $value;
                                                                    $curr = collect($currencies)->firstWhere('code', $currencyCode);
                                                                    $currSymbol = $curr ? $curr->symbol : $currencyCode;
                                                                    $label = $curr ? $curr->label . ' (' . $currencyCode . ')' : $currencyCode;
                                                                @endphp
                                                                <table class="table table-sm table-borderless mb-0">
                                                                    <tr>
                                                                        <td class="text-muted">{{ $label }}:</td>
                                                                        <td class="text-end fw-bold">{{ $currSymbol }}{{ number_format($amount, 2) }}</td>
                                                                    </tr>
                                                                </table>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <p class="text-muted mb-0 text-center">Sin movimientos</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Sales Totals --}}
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <div class="alert alert-info mb-0 py-2">
                                                <strong>Total Ventas:</strong>
                                                <span class="float-end">{{ $symbol }}{{ number_format($totalSales, 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="alert alert-secondary mb-0 py-2">
                                                <strong>Ventas a Crédito:</strong>
                                                <span class="float-end">{{ $symbol }}{{ number_format($totalCreditSales, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- PAGOS DE CRÉDITOS SECTION --}}
                            <div class="card border-warning border-2 mb-3">
                                <div class="card-header bg-warning p-2">
                                    <h5 class="m-0 text-dark">
                                        <i class="icofont icofont-money-bag"></i> PAGOS DE CRÉDITOS RECIBIDOS
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        {{-- Efectivo --}}
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 border-warning">
                                                <div class="card-header bg-light-warning p-2">
                                                    <h6 class="mb-0 text-warning">
                                                        <i class="icofont icofont-bill"></i> Efectivo
                                                    </h6>
                                                </div>
                                                <div class="card-body p-2">
                                                    @if (!empty($paymentsByCurrency['cash']))
                                                        @foreach ($paymentsByCurrency['cash'] as $currency => $amount)
                                                            @php
                                                                $currObj = collect($currencies)->firstWhere('code', $currency);
                                                                $label = $currObj ? $currObj->label . ' (' . $currency . ')' : $currency;
                                                            @endphp
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <span class="f-12">{{ $label }}</span>
                                                                </div>
                                                                <div class="col-6 text-end">
                                                                    <span class="f-12">${{ number_format($amount, 2) }}</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <p class="text-muted mb-0 text-center">Sin movimientos</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Banco --}}
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 border-warning">
                                                <div class="card-header bg-light-warning p-2">
                                                    <h6 class="mb-0 text-warning">
                                                        <i class="icofont icofont-bank-alt"></i> Banco
                                                    </h6>
                                                </div>
                                                <div class="card-body p-2">
                                                    @if (isset($paymentsByCurrency['deposit']) && count($paymentsByCurrency['deposit']) > 0)
                                                        @foreach ($paymentsByCurrency['deposit'] as $bankName => $currenciesInBank)
                                                            <div class="mb-1 ms-2">
                                                                <span class="f-w-600 f-12">{{ $bankName }}</span>
                                                                @foreach ($currenciesInBank as $currency => $amount)
                                                                    @php
                                                                        $currObj = collect($currencies)->firstWhere('code', $currency);
                                                                        $label = $currObj ? $currObj->label . ' (' . $currency . ')' : $currency;
                                                                    @endphp
                                                                    <div class="row ms-2">
                                                                        <div class="col-6">
                                                                            <span class="f-12">{{ $label }}</span>
                                                                        </div>
                                                                        <div class="col-6 text-end">
                                                                            <span
                                                                                class="f-12">${{ number_format($amount, 2) }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <p class="text-muted mb-0 text-center">Sin movimientos</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    {{-- Payment Total --}}
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <div class="alert alert-warning mb-0 py-2">
                                                <strong>Total Pagos Recibidos:</strong>
                                                <span class="float-end">{{ $symbol }}{{ number_format($totalPayments, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            {{-- RESUMEN GENERAL --}}
                            <div class="card border-success border-2 mb-3">
                                <div class="card-header bg-success p-2">
                                    <h5 class="m-0 text-white">
                                        <i class="icofont icofont-calculator-alt-2"></i> RESUMEN TOTAL
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="text-center p-3 bg-light rounded h-100">
                                                <h6 class="text-muted mb-2">Total en Efectivo</h6>
                                                @if (!empty($totalCashDetails))
                                                    @foreach ($totalCashDetails as $currency => $amount)
                                                        @php
                                                            $curr = collect($currencies)->firstWhere('code', $currency);
                                                            $currSymbol = $curr ? $curr->symbol : $currency;
                                                            $label = $curr ? $curr->label : $currency;
                                                        @endphp
                                                        <h4 class="text-success mb-0">{{ $currSymbol }}{{ number_format($amount, 2) }}</h4>
                                                        <small class="text-muted">{{ $label }}</small><br>
                                                    @endforeach
                                                @else
                                                     <h4 class="text-success mb-0">{{ $symbol }}0.00</h4>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center p-3 bg-light rounded h-100">
                                                <h6 class="text-muted mb-2">Total en Banco</h6>
                                                @if (!empty($totalBankDetails))
                                                    @foreach ($totalBankDetails as $bankName => $currenciesInBank)
                                                        <div class="mb-2 border-bottom pb-1">
                                                            <strong class="text-dark f-14">{{ $bankName }}</strong>
                                                            @foreach ($currenciesInBank as $currency => $amount)
                                                                @php
                                                                    $curr = collect($currencies)->firstWhere('code', $currency);
                                                                    $currSymbol = $curr ? $curr->symbol : $currency;
                                                                @endphp
                                                                <div class="d-flex justify-content-between px-4">
                                                                    <span class="text-muted f-12">{{ $currency }}</span>
                                                                    <span class="text-success fw-bold f-12">{{ $currSymbol }}{{ number_format($amount, 2) }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <h4 class="text-success mb-0">{{ $symbol }}0.00</h4>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-success mb-0 py-3">
                                <h5 class="mb-0">
                                    <strong>TOTAL GENERAL:</strong>
                                    <span class="float-end">{{ $symbol }}{{ number_format($totalCash + $totalDeposit + $totalPayments, 2) }}</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <button title="Imprimir corte de caja" wire:click.prevent="printCC"
                        class="btn btn-outline-dark btn-lg {{ $totalSales > 0 ? '' : 'd-none' }}">
                        <i class="icofont icofont-printer"></i> Imprimir Corte
                    </button>
                </div>                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>



        </div>
    </div>

    <script>
        document.onkeydown = function(e) {

            // f3
            if (e.keyCode == '113') {
                e.preventDefault()
                var input = document.getElementById('inputCustomer');
                var tomselect = input.tomselect
                tomselect.clear()
                tomselect.focus()
            }
        }

        document.addEventListener('livewire:init', () => {
            flatpickr("#dateFrom", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",
                onChange: function(selectedDates, dateStr, instance) {
                    console.log(dateStr);
                    @this.set('dateFrom', dateStr)
                }
            })
            flatpickr("#dateTo", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",
                onChange: function(selectedDates, dateStr, instance) {
                    @this.set('dateTo', dateStr)
                }
            })







            Livewire.on('show-modal-payment', event => {
                $('#modalPartialPay').modal('show')
            })

            Livewire.on('close-modal', event => {
                $('#modalPartialPay').modal('hide')
            })

            Livewire.on('show-payhistory', event => {
                $('#modalPayHistory').modal('show')
            })

        })
    </script>
</div>
