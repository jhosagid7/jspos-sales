<div>
    <div wire:ignore.self class="modal fade" id="modalSaleDetail" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="p-1 modal-header bg-info">
                    <h5 class="modal-title">Detalles de la venta #{{ $sale_id }}</h5>
                    <button class="py-0 btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if (count($details) > 0)
                        @php
                            $currencySymbol = '$';
                            if(isset($salesObt) && $salesObt->primary_currency_code) {
                                $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($salesObt->primary_currency_code);
                            }
                        @endphp
                        <div class="table-responsive">
                            <table class="table table-responsive-md table-hover" id="tblPermissions">
                                <thead class="thead-primary">
                                    <tr class="text-center">
                                        <th>Folio</th>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Importe</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($details as $detail)
                                        <tr class="text-center">
                                            <td>{{ $detail->id }}</td>
                                            <td>{{ $detail->product->name }}</td>
                                            <td>{{ $detail->quantity }}</td>
                                            <td>{{ $currencySymbol }}{{ $detail->sale_price }}</td>
                                            <td>{{ $currencySymbol }}{{ round($detail->sale_price * $detail->quantity, 2) }}</td>

                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Sin detalles</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2"><b>Totales</b></td>
                                        <td class="text-center">{{ $details->sum('quantity') }}</td>
                                        <td></td>
                                        <td class="text-center">
                                            @php
                                                $sumTotalDetail = $details->sum(function ($item) {
                                                    return $item->quantity * $item->sale_price;
                                                });
                                            @endphp
                                            {{ $currencySymbol }}{{ round($sumTotalDetail, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        {{-- Detalles de Pagos en Múltiples Monedas (Incluyendo Abonos) --}}
                        @php
                            $allPayments = collect();
                            $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();

                            // 1. Agregar pagos iniciales (SalePaymentDetail)
                            if($salesObt && $salesObt->paymentDetails) {
                                foreach($salesObt->paymentDetails as $detail) {
                                    $allPayments->push((object)[
                                        'type' => 'initial',
                                        'method' => $detail->payment_method, // cash, bank, nequi
                                        'bank_name' => $detail->bank_name,
                                        'currency' => $detail->currency_code,
                                        'amount' => $detail->amount,
                                        'rate' => $detail->exchange_rate,
                                        'amount_primary' => $detail->amount_in_primary_currency
                                    ]);
                                }
                            }

                            // 2. Agregar abonos (Payment)
                            if($salesObt && $salesObt->payments) {
                                foreach($salesObt->payments as $pay) {
                                    // Determinar si el pago está en la moneda principal
                                    $isPaymentInPrimaryCurrency = ($pay->currency == $primaryCurrency->code);
                                    
                                    // Si el pago está en la moneda principal, usar el monto directamente
                                    if ($isPaymentInPrimaryCurrency) {
                                        $amountInPrimary = $pay->amount;
                                        // Para pagos en moneda principal, la tasa a mostrar es la tasa de la moneda principal
                                        // que estaba vigente (cuántos VED vale 1 USD)
                                        $displayRate = $pay->primary_exchange_rate;
                                    } else {
                                        // Si no, convertir a USD y luego a moneda principal
                                        $rate = $pay->exchange_rate > 0 ? $pay->exchange_rate : 1;
                                        $primaryRate = ($pay->primary_exchange_rate && $pay->primary_exchange_rate > 1) 
                                            ? $pay->primary_exchange_rate 
                                            : $primaryCurrency->exchange_rate;
                                        
                                        $amountInUSD = $pay->amount / $rate;
                                        $amountInPrimary = $amountInUSD * $primaryRate;
                                        
                                        // La tasa a mostrar es la de conversión a moneda principal
                                        // (ej: cuántos VED vale 1 USD = primary_exchange_rate)
                                        $displayRate = $primaryRate;
                                    }

                                    // Normalizar método
                                    $method = match($pay->pay_way) {
                                        'deposit' => 'bank',

                                        default => 'cash'
                                    };

                                    $allPayments->push((object)[
                                        'type' => 'abono',
                                        'method' => $method,
                                        'bank_name' => $pay->bank,
                                        'currency' => $pay->currency,
                                        'amount' => $pay->amount,
                                        'rate' => $displayRate,
                                        'amount_primary' => $amountInPrimary
                                    ]);
                                }
                            }
                        @endphp

                        @if($allPayments->count() > 0)
                            <div class="mt-4">
                                <h6 class="text-info"><i class="fa fa-money"></i> Pagos Recibidos</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr class="text-center">
                                                <th>Tipo</th>
                                                <th>Método</th>
                                                <th>Moneda</th>
                                                <th>Monto</th>
                                                <th>Tasa de Cambio</th>
                                                <th>Equivalente ({{ $primaryCurrency->code }})</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allPayments as $payment)
                                                @php
                                                    // Determinar el nombre del método
                                                    if ($payment->method == 'bank' && $payment->bank_name) {
                                                        $methodName = $payment->bank_name;

                                                    } else {
                                                        $methodName = 'Efectivo';
                                                    }
                                                    
                                                    $currencyName = match($payment->currency) {
                                                        'USD' => 'Dólar',
                                                        'COP' => 'Pesos',
                                                        'VES' => 'Bolívares',
                                                        'VED' => 'Bolívares',
                                                        default => $payment->currency
                                                    };
                                                    
                                                    $badgeColor = match($payment->method) {
                                                        'bank' => 'info',

                                                        default => 'success'
                                                    };
                                                @endphp
                                                <tr class="text-center">
                                                    <td>
                                                        <span class="badge badge-light-secondary">
                                                            {{ $payment->type == 'initial' ? 'Inicial' : 'Abono' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $badgeColor }}">
                                                            {{ $methodName }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">
                                                            {{ $currencyName }} ({{ $payment->currency }})
                                                        </span>
                                                    </td>
                                                    <td>{{ number_format($payment->amount, 2) }}</td>
                                                    <td>{{ number_format($payment->rate, 4) }}</td>
                                                    <td>{{ number_format($payment->amount_primary, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="5" class="text-end"><b>Total Pagado:</b></td>
                                                <td class="text-center"><b>{{ number_format($allPayments->sum('amount_primary'), 2) }}</b></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Detalles de Vueltos en Múltiples Monedas --}}
                        @if($salesObt && $salesObt->changeDetails && $salesObt->changeDetails->count() > 0)
                            <div class="mt-4">
                                <h6 class="text-warning"><i class="fa fa-exchange"></i> Vueltos Entregados</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr class="text-center">
                                                <th>Moneda</th>
                                                <th>Monto</th>
                                                <th>Tasa de Cambio</th>
                                                <th>Equivalente (Moneda Principal)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($salesObt->changeDetails as $change)
                                                <tr class="text-center">
                                                    <td><span class="badge badge-light-warning">{{ $change->currency_code }}</span></td>
                                                    <td>{{ number_format($change->amount, 2) }}</td>
                                                    <td>{{ number_format($change->exchange_rate, 4) }}</td>
                                                    <td>{{ number_format($change->amount_in_primary_currency, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="3" class="text-end"><b>Total Vuelto:</b></td>
                                                <td class="text-center"><b>{{ number_format($salesObt->changeDetails->sum('amount_in_primary_currency'), 2) }}</b></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Información de Tasa de Cambio Histórica --}}
                        @if($salesObt && $salesObt->primary_currency_code)
                            <div class="mt-4 alert alert-light">
                                <small class="text-muted">
                                    <i class="fa fa-info-circle"></i> 
                                    <b>Tasa de cambio al momento de la venta:</b> 
                                    1 USD = {{ number_format($salesObt->primary_exchange_rate, 4) }} {{ $salesObt->primary_currency_code }}
                                </small>
                            </div>
                        @endif
                    @endif

                </div>

                <div class="modal-footer">
                    @if (!is_null($sale_id))
                        <a class="btn btn-sm btn-outline-dark {{ $sale_status == 'returned' ? 'disabled' : '' }}"
                            href="{{ route('pos.sales.generatePdfInvoice', $sale_id) }}" target="_blank">
                            Imprimir Factura
                            <i class="text-danger icofont icofont-file-pdf fa-2x"></i>
                        </a>
                    @endif
                    <button class="btn btn-dark " type="button" data-bs-dismiss="modal">Cerrar</button>


                </div>

            </div>
        </div>
    </div>
</div>
