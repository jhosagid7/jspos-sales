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

                            // Calculate Charges
                            $commPercent = $salesObt->applied_commission_percent ?? 0;
                            // For display, we use the stored percent, but for amount we sum details
                            $freightPercent = $salesObt->applied_freight_percent ?? 0;
                            $diffPercent = $salesObt->applied_exchange_diff_percent ?? 0;
                            
                            $totalFreightAmount = $details->sum('freight_amount');

                            // Split Freight Logic
                            // Config Freight: Products with 'global' or 'none' (using seller config)
                            $configFreightTotal = $details->filter(function($d) {
                                return in_array($d->product->freight_type, ['global', 'none']);
                            })->sum('freight_amount');

                            // Product Freight: Products with 'personalized', 'fixed', 'percentage'
                            $productFreightTotal = $details->filter(function($d) {
                                return !in_array($d->product->freight_type, ['global', 'none']);
                            })->sum('freight_amount');
                            
                            // Calculate True Base Amount
                            $baseAmount = $details->sum(function($d) {
                                return ($d->regular_price ?? $d->sale_price) * $d->quantity;
                            });
                            
                            // Calculate Comm/Diff amounts
                            $commAmount = 0;
                            $diffAmount = 0;
                            
                            // Only calculate if we have percentages enabled
                            if ($salesObt->is_foreign_sale) { 
                                $commAmount = $baseAmount * ($commPercent / 100);
                                $diffAmount = $baseAmount * ($diffPercent / 100);
                            }
                            
                            $hasExtraCharges = ($commPercent > 0 || $diffPercent > 0 || $totalFreightAmount > 0);
                        @endphp

                        {{-- Header Information --}}
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <div><b>Cliente:</b> {{ $salesObt->customer->name ?? 'N/A' }}</div>
                                <div><b>Folio:</b> {{ $salesObt->invoice_number ?? 'N/A' }}</div>
                                <div><b>Fecha:</b> {{ $salesObt->created_at->format('d/m/Y h:i A') }}</div>
                            </div>
                            <div class="col-sm-6 text-end">
                                <div><b>Vendedor:</b> {{ $salesObt->customer->seller->name ?? 'N/A' }}</div>
                                <div><b>Operador:</b> {{ $salesObt->user->name ?? 'N/A' }}</div>
                            </div>
                        </div>

                        {{-- Additional Charges Breakdown --}}
                        @if ($hasExtraCharges && $salesObt->is_foreign_sale)
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="alert alert-light border">
                                        <h6 class="text-info"><i class="fa fa-calculator"></i> Desglose de Cargos Adicionales</h6>
                                        <div class="row">
                                            @if($commPercent > 0)
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">Comisión ({{ number_format($commPercent, 2) }}%)</small>
                                                    <strong class="text-dark">{{ $currencySymbol }}{{ number_format($commAmount, 2) }}</strong>
                                                </div>
                                            @endif

                                            @if($configFreightTotal > 0)
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">Flete (Config: {{ number_format($freightPercent, 2) }}%)</small>
                                                    <strong class="text-dark">{{ $currencySymbol }}{{ number_format($configFreightTotal, 2) }}</strong>
                                                </div>
                                            @endif

                                            @if($productFreightTotal > 0)
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">Flete (Productos)</small>
                                                    <strong class="text-dark">{{ $currencySymbol }}{{ number_format($productFreightTotal, 2) }}</strong>
                                                </div>
                                            @endif

                                            @if($diffPercent > 0)
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">Dif. Cambiaria ({{ number_format($diffPercent, 2) }}%)</small>
                                                    <strong class="text-dark">{{ $currencySymbol }}{{ number_format($diffAmount, 2) }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-responsive-md table-hover" id="tblPermissions">
                                <thead class="thead-primary">
                                    <tr class="text-center">
                                        <th>Folio</th>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Subtotal</th>
                                        <th>Cargos Flete</th>
                                        <th>Cargos Adic.</th>
                                        <th>Importe Total</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($details as $detail)
                                        @php
                                            // Data from DB
                                            $qty = $detail->quantity;
                                            $finalUnitSalePrice = $detail->sale_price;
                                            $totalFreight = $detail->freight_amount;
                                            $finalImporte = $finalUnitSalePrice * $qty; // Total Final (with everything)
                                            
                                            // 1. Calculate Base Total from regular_price
                                            $baseUnit = $detail->regular_price ?? $finalUnitSalePrice;
                                            $baseTotal = $baseUnit * $qty;
                                            
                                            // Percentages stored on Sale
                                            $commPct = $salesObt->applied_commission_percent ?? 0;
                                            $diffPct = $salesObt->applied_exchange_diff_percent ?? 0;
                                            $combinedPct = ($commPct + $diffPct) / 100;
                                            
                                            // Detect Additive Freight Check
                                            $rawItemsSum = $details->sum(function($d) { return $d->quantity * $d->sale_price; });
                                            $isAdditive = ($salesObt->total - $rawItemsSum) > 0.01;
                                            
                                            if ($isAdditive) {
                                                $additionalCharges = 0; // Info is hidden for additive
                                            } else {
                                                // 3. Calculate Additional Charges Amount based on Pure Base Total
                                                $additionalCharges = $baseTotal * $combinedPct;
                                            }
                                        @endphp
                                        <tr class="text-center">
                                            <td>{{ $detail->id }}</td>
                                            <td>
                                                {{ $detail->product->name }}

                                            </td>
                                            <td>{{ $qty }}</td>
                                            <td>{{ $currencySymbol }}{{ number_format($baseUnit, 2) }}</td>
                                            <td>{{ $currencySymbol }}{{ number_format($baseTotal, 2) }}</td>
                                            <td>{{ $currencySymbol }}{{ number_format($totalFreight, 2) }}</td>
                                            <td>{{ $currencySymbol }}{{ number_format($additionalCharges, 2) }}</td>
                                            
                                            <td>{{ $currencySymbol }}{{ number_format($baseTotal + $totalFreight + $additionalCharges, 2) }}</td>

                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">Sin detalles</td>
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
                                                $sumBaseTotal = $details->sum(function ($item) {
                                                    $baseUnit = $item->regular_price ?? $item->sale_price;
                                                    return $baseUnit * $item->quantity;
                                                });
                                            @endphp
                                            {{ $currencySymbol }}{{ number_format($sumBaseTotal, 2) }}
                                        </td>
                                        <td class="text-center">
                                            {{ $currencySymbol }}{{ number_format($details->sum('freight_amount'), 2) }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $commPct = $salesObt->applied_commission_percent ?? 0;
                                                $diffPct = $salesObt->applied_exchange_diff_percent ?? 0;
                                                $combinedPct = ($commPct + $diffPct) / 100;
                                                
                                                $rawItemsSum = $details->sum(function($d) { return $d->quantity * $d->sale_price; });
                                                $isAdditive = ($salesObt->total - $rawItemsSum) > 0.01;

                                                $sumAdditional = $details->sum(function ($item) use ($combinedPct, $isAdditive) {
                                                    if ($isAdditive) {
                                                        return 0;
                                                    } else {
                                                        $baseUnit = $item->regular_price ?? $item->sale_price;
                                                        $baseTotal = $baseUnit * $item->quantity;
                                                        return $baseTotal * $combinedPct;
                                                    }
                                                });
                                            @endphp
                                            {{ $currencySymbol }}{{ number_format($sumAdditional, 2) }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                // Calculate Total Row Sum
                                                // Base + Freight + Additional
                                                $sumTotalDetail = $sumBaseTotal + $details->sum('freight_amount') + $sumAdditional;
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
                                        'method' => $detail->payment_method, // cash, bank, nequi, zelle
                                        'bank_name' => $detail->bank_name,
                                        'currency' => $detail->currency_code,
                                        'amount' => $detail->amount,
                                        'rate' => $detail->exchange_rate,
                                        'amount_primary' => $detail->amount_in_primary_currency,
                                        'reference' => $detail->reference_number ?? ($detail->bankRecord ? $detail->bankRecord->reference : null),
                                        'account' => $detail->account_number,
                                        'zelle_record' => $detail->zelleRecord,
                                        'bank_record' => $detail->bankRecord // Link BankRecord
                                    ]);
                                }
                            }

                            // 2. Agregar abonos (Payment)
                            $totalPaidUSD = 0; // Initialize USD Total
                            $pendingPayments = collect(); // Separated collection for pending

                            if($salesObt && $salesObt->payments) {
                                foreach($salesObt->payments as $pay) {
                                    
                                    // Determinar si el pago está en la moneda principal
                                    $isPaymentInPrimaryCurrency = ($pay->currency == $primaryCurrency->code);
                                    
                                    // Calculos comunes de conversión
                                    if ($isPaymentInPrimaryCurrency) {
                                        $amountInPrimary = $pay->amount;
                                        $displayRate = $pay->primary_exchange_rate;
                                        
                                        $rateForUSD = ($pay->primary_exchange_rate > 0) ? $pay->primary_exchange_rate : 1;
                                        $thisAmountUSD = $pay->amount / $rateForUSD;

                                    } else {
                                        $rate = $pay->exchange_rate > 0 ? $pay->exchange_rate : 1;
                                        $primaryRate = ($pay->primary_exchange_rate && $pay->primary_exchange_rate > 1) 
                                            ? $pay->primary_exchange_rate 
                                            : $primaryCurrency->exchange_rate;
                                        
                                        $amountInUSD = $pay->amount / $rate;
                                        $amountInPrimary = $amountInUSD * $primaryRate;
                                        $displayRate = $primaryRate;
                                        $thisAmountUSD = $amountInUSD;
                                    }
                                    
                                    // Normalizar método
                                    $method = match($pay->pay_way) {
                                        'deposit' => 'bank',
                                        'zelle' => 'zelle',
                                        default => 'cash'
                                    };

                                    $paymentObj = (object)[
                                        'id' => $pay->id,
                                        'status' => $pay->status, // Add status
                                        'type' => 'abono',
                                        'method' => $method,
                                        'bank_name' => $pay->bank,
                                        'currency' => $pay->currency,
                                        'amount' => $pay->amount,
                                        'rate' => $displayRate,
                                        'amount_primary' => $amountInPrimary,
                                        'amount_usd' => $thisAmountUSD, // Store USD Amount
                                        'reference' => $pay->deposit_number ?? $pay->reference ?? ($pay->bankRecord ? $pay->bankRecord->reference : null),
                                        'account' => $pay->account_number,
                                        'zelle_record' => $pay->zelleRecord,
                                        'bank_record' => $pay->bankRecord, // Link BankRecord
                                        // Discount info
                                        'discount_amount' => $pay->discount_applied,
                                        'discount_percentage' => $pay->discount_percentage,
                                        'discount_reason' => $pay->discount_reason,
                                        'created_at' => $pay->created_at,
                                    ];

                                    // Logic Separation: Pending vs Approved
                                    if($pay->status == 'pending') {
                                        $pendingPayments->push($paymentObj);
                                    } else {
                                        // Only add to Total Paid if NOT pending
                                        $totalPaidUSD += $thisAmountUSD;
                                        $allPayments->push($paymentObj);
                                    }
                                }
                            }
                        @endphp

                        {{-- Section: Pending Payments (New) --}}
                        @if($pendingPayments->count() > 0)
                            <div class="mt-4">
                                <h6 class="text-danger">
                                    <i class="fa fa-clock-o"></i> Pagos Pendientes de Aprobación 
                                    <span class="badge bg-danger">{{ $pendingPayments->count() }}</span>
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered border-danger">
                                        <thead class="bg-light-danger text-danger">
                                            <tr class="text-center">
                                                <th>Fecha</th>
                                                <th>Método</th>
                                                <th>Moneda</th>
                                                <th>Monto</th>
                                                <th>Ref. / Detalles</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendingPayments as $pending)
                                                @php
                                                    $currencyName = match($pending->currency) {
                                                        'USD' => 'Dólar',
                                                        'COP' => 'Pesos',
                                                        'VES' => 'Bolívares',
                                                        'VED' => 'Bolívares',
                                                        default => $pending->currency
                                                    };
                                                @endphp
                                                <tr class="text-center">
                                                    <td>{{ $pending->created_at->format('d/m/Y h:i A') }}</td>
                                                    <td>{{ ucfirst($pending->method) }}</td>
                                                    <td>
                                                        <span class="badge badge-light-primary">
                                                            {{ $currencyName }} ({{ $pending->currency }})
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold">{{ number_format($pending->amount, 2) }}</td>
                                                    <td class="text-start">
                                                        @if($pending->reference) 
                                                            <div><b>Ref:</b> {{ $pending->reference }}</div> 
                                                        @endif
                                                        @if($pending->bank_name)
                                                            <div>{{ $pending->bank_name }}</div>
                                                        @endif
                                                        @if($pending->bank_record && $pending->bank_record->image_path)
                                                             <a href="{{ asset('storage/' . $pending->bank_record->image_path) }}" target="_blank" class="text-danger small"><i class="fa fa-image"></i> Ver Comprobante</a>
                                                        @elseif($pending->zelle_record && $pending->zelle_record->image_path)
                                                             <a href="{{ asset('storage/' . $pending->zelle_record->image_path) }}" target="_blank" class="text-danger small"><i class="fa fa-image"></i> Ver Comprobante</a>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-light-danger">
                                                <td colspan="6" class="text-start text-danger small">
                                                    <i class="fa fa-info-circle"></i> Estos pagos <b>NO</b> se han descontado de la deuda todavía. Deben ser aprobados en la sección de Pagos.
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Section: Approved Payments (Existing) --}}
                        @if($allPayments->count() > 0)
                            <div class="mt-4">
                                <h6 class="text-info"><i class="fa fa-money"></i> Pagos Recibidos (Verificados)</h6>
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
                                                <th>Detalles</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allPayments as $payment)
                                                @php
                                                    // Determinar el nombre del método
                                                    if ($payment->method == 'bank' && $payment->bank_name) {
                                                        $methodName = $payment->bank_name;
                                                    } elseif ($payment->method == 'zelle') {
                                                        $methodName = 'Zelle';
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
                                                        'zelle' => 'dark',
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
                                                    <td class="text-start">
                                                        @if ($payment->method == 'bank' || $payment->method == 'deposit')
                                                            <small>
                                                                @if($payment->account) <div><b>Cta:</b> {{ $payment->account }}</div> @endif
                                                                @if($payment->reference) <div><b>Ref:</b> {{ $payment->reference }}</div> @endif
                                                                
                                                                {{-- Bank Record Details (Date, Note, Image) --}}
                                                                @if($payment->bank_record)
                                                                    <div><b>Fecha:</b> {{ \Carbon\Carbon::parse($payment->bank_record->payment_date)->format('d/m/Y') }}</div>
                                                                    <div><b>Monto:</b> {{ number_format($payment->bank_record->amount, 2) }}</div>
                                                                    @if(!empty($payment->bank_record->note))
                                                                       <div><small class="text-muted">{{ $payment->bank_record->note }}</small></div>
                                                                    @endif
                                                                    
                                                                    @if(!empty($payment->bank_record->image_path))
                                                                        <div class="mt-1">
                                                                            <a href="{{ asset('storage/' . $payment->bank_record->image_path) }}" target="_blank" class="text-info">
                                                                                <i class="fas fa-image"></i> Ver Comprobante
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </small>
                                                        @elseif ($payment->method == 'zelle' && $payment->zelle_record)
                                                            <div class="small">
                                                                <div><b>Emisor:</b> {{ $payment->zelle_record->sender_name }}</div>
                                                                <div><b>Fecha:</b> {{ \Carbon\Carbon::parse($payment->zelle_record->zelle_date)->format('d/m/Y') }}</div>
                                                                @if($payment->zelle_record->reference)
                                                                    <div><b>Ref:</b> {{ $payment->zelle_record->reference }}</div>
                                                                @endif
                                                                @if(!empty($payment->zelle_record->image_path))
                                                                    <div class="mt-1">
                                                                        <a href="{{ asset('storage/' . $payment->zelle_record->image_path) }}" target="_blank" class="text-primary">
                                                                            <i class="fas fa-image"></i> Ver Comprobante
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($payment->discount_amount) && $payment->discount_amount > 0)
                                                            <div class="mt-1 text-success">
                                                                <i class="fas fa-arrow-down"></i> 
                                                                <b>{{ $payment->discount_reason ?? 'Descuento' }} ({{ $payment->discount_percentage }}%):</b> 
                                                                {{ $currencySymbol }}{{ number_format($payment->discount_amount, 2) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Unified Summary Section --}}
                                @php
                                    $allPaymentsPrimary = isset($allPayments) ? $allPayments->sum('amount_primary') : 0;
                                    $exchangeRate = $salesObt->primary_exchange_rate > 0 ? $salesObt->primary_exchange_rate : 1;
                                    $allPaymentsUSD = $allPaymentsPrimary / $exchangeRate;
                                    
                                    $totalReturned = $salesObt->returns ? $salesObt->returns->sum('total_returned') : 0;
                                    $totalReturnedUSD = $totalReturned / $exchangeRate;
                                    
                                    $debtReductionReturns = $salesObt->returns ? $salesObt->returns->where('refund_method', 'debt_reduction')->sum('total_returned') : 0;
                                    $debtReductionReturnsUSD = $debtReductionReturns / $exchangeRate;
                                    
                                    $montoNeto = $salesObt->total - $totalReturned;
                                    $montoNetoUSD = $salesObt->total_usd - $totalReturnedUSD;
                                    
                                    $pendingBalance = $salesObt->total - $allPaymentsPrimary - $debtReductionReturns;
                                    $pendingBalanceUSD = $salesObt->total_usd - $allPaymentsUSD - $debtReductionReturnsUSD;
                                    
                                    if ($pendingBalance < 0) $pendingBalance = 0;
                                    if ($pendingBalanceUSD < 0) $pendingBalanceUSD = 0;
                                @endphp

                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="row justify-content-end">
                                            <div class="col-md-8">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr class="bg-light text-center">
                                                            <th>Concepto</th>
                                                            <th>Moneda Factura ({{ $currencySymbol }})</th>
                                                            <th>Equivalente (USD)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-end bg-light"><b>Monto Original (Bruto):</b></td>
                                                            <td class="text-end text-muted">{{ $currencySymbol }}{{ number_format($salesObt->total, 2) }}</td>
                                                            <td class="text-end text-muted">${{ number_format($salesObt->total_usd, 2) }}</td>
                                                        </tr>
                                                        @if($totalReturned > 0)
                                                            <tr>
                                                                <td class="text-end bg-light"><b>(-) Devoluciones:</b></td>
                                                                <td class="text-end text-danger">{{ $currencySymbol }}{{ number_format($totalReturned, 2) }}</td>
                                                                <td class="text-end text-danger">${{ number_format($totalReturnedUSD, 2) }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-end bg-light"><b>Monto Neto (Actual):</b></td>
                                                                <td class="text-end fw-bold">{{ $currencySymbol }}{{ number_format($montoNeto, 2) }}</td>
                                                                <td class="text-end fw-bold">${{ number_format($montoNetoUSD, 2) }}</td>
                                                            </tr>
                                                        @endif
                                                        <tr>
                                                            <td class="text-end bg-light"><b>(-) Total Abonado:</b></td>
                                                            <td class="text-end text-success fw-bold">{{ $currencySymbol }}{{ number_format($allPaymentsPrimary, 2) }}</td>
                                                            <td class="text-end text-success fw-bold">${{ number_format($allPaymentsUSD, 2) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end bg-light"><b>Saldo Pendiente:</b></td>
                                                            <td class="text-end text-danger fw-bold h5">
                                                                {{ $currencySymbol }}{{ number_format($pendingBalance, 2) }}
                                                            </td>
                                                            <td class="text-end text-danger fw-bold h5">
                                                                ${{ number_format($pendingBalanceUSD, 2) }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- End of payments check. We just unified the summary, so nothing else needed here. --}}
                        @endif
                        
                        {{-- Detalles de Devoluciones --}}
                        @if($salesObt && $salesObt->returns && $salesObt->returns->count() > 0)
                            <div class="mt-4">
                                <h6 class="text-danger"><i class="fa fa-undo"></i> Devoluciones de Productos Registradas</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr class="text-center">
                                                <th>Fecha</th>
                                                <th>Motivo</th>
                                                <th>Método de Reembolso</th>
                                                <th>Total Devuelto</th>
                                                <th>Detalles de Productos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($salesObt->returns as $return)
                                                <tr class="text-center">
                                                    <td>
                                                        {{ $return->created_at->format('d/m/Y h:i A') }}<br>
                                                        <a href="{{ route('pos.returns.generateCreditNotePdf', $return->id) }}" target="_blank"
                                                           class="btn btn-xs btn-outline-danger mt-1" title="Imprimir Nota de Crédito">
                                                            <i class="fas fa-file-pdf"></i> Nota
                                                        </a>
                                                    </td>
                                                    <td>{{ $return->reason ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($return->refund_method == 'cash')
                                                            <span class="badge badge-light-success">Efectivo / Caja</span>
                                                        @elseif($return->refund_method == 'wallet')
                                                            <span class="badge badge-light-info">Saldo a Favor</span>
                                                        @elseif($return->refund_method == 'debt_reduction')
                                                            <span class="badge badge-light-warning">Redondeo a Deuda</span>
                                                        @elseif($return->refund_method == 'bank')
                                                            <span class="badge badge-light-primary">Banco / Transferencia</span>
                                                        @else
                                                            <span class="badge badge-light-secondary">{{ ucfirst($return->refund_method) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="fw-bold text-danger">{{ $currencySymbol }}{{ number_format($return->total_returned, 2) }}</td>
                                                    <td class="text-start">
                                                        <ul class="mb-0 pl-3">
                                                            @foreach($return->details as $retDetail)
                                                                <li>
                                                                    {{ (int)$retDetail->quantity }}x {{ $retDetail->saleDetail->product->name ?? 'Producto Eliminado' }} 
                                                                    <small class="text-muted">({{ $currencySymbol }}{{ number_format($retDetail->unit_price, 2) }} c/u)</small>
                                                                </li>
                                                            @endforeach
                                                            @if($return->details->count() === 0)
                                                                <li><i class="fa fa-info-circle me-1"></i> Ajuste Manual de Saldo</li>
                                                            @endif
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="3" class="text-end"><b>Total Devoluciones:</b></td>
                                                <td class="text-center text-danger"><b>{{ $currencySymbol }}{{ number_format($salesObt->returns->sum('total_returned'), 2) }}</b></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Detalles de Cobranza del Chofer --}}
                        @if($salesObt && $salesObt->deliveryCollections && $salesObt->deliveryCollections->count() > 0)
                            <div class="mt-4">
                                <h6 class="text-primary"><i class="fa fa-truck"></i> Reportes de Chofer / Cobranza</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr class="text-center">
                                                <th>Fecha</th>
                                                <th>Chofer</th>
                                                <th>Nota</th>
                                                <th>Pagos Reportados</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($salesObt->deliveryCollections as $collection)
                                                <tr class="text-center">
                                                    <td>{{ $collection->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $collection->driver->name ?? 'N/A' }}</td>
                                                    <td class="text-start fst-italic">{{ $collection->note ?? '-' }}</td>
                                                    <td class="text-start">
                                                        @if($collection->payments->count() > 0)
                                                            @foreach($collection->payments as $payment)
                                                                <div class="small">
                                                                    <span class="fw-bold">{{ $payment->currency->code }}:</span> 
                                                                    {{ number_format($payment->amount, 2) }}
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted small">Solo nota</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
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

                <div class="modal-footer d-block">
                    @if (!is_null($sale_id))
                        {{-- Row 1: Acciones Generales --}}
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                            <small class="text-muted fw-bold me-1">GENERAL:</small>

                            <button class="btn btn-sm btn-outline-dark" wire:click="printSale({{ $sale_id }})" title="Imprimir Ticket Cliente">
                                <i class="text-info icofont icofont-ticket"></i> Ticket Venta
                            </button>

                            <button class="btn btn-sm btn-outline-dark" wire:click="printInternalTicket({{ $sale_id }})" title="Imprimir Ticket Interno Contable">
                                <i class="text-warning icofont icofont-ticket"></i> Ticket Interno
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-danger ms-auto"
                                onclick="Livewire.dispatch('openReturnModal', { id: {{ $sale_id }} })">
                                <i class="fa fa-undo"></i> Devolver Productos
                            </button>

                            <button class="btn btn-sm btn-dark" type="button" data-bs-dismiss="modal">Cerrar</button>
                        </div>

                        {{-- Row 2: Facturas Cliente --}}
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                            <small class="text-muted fw-bold me-1">FACTURAS CLIENTE:</small>

                            <a class="btn btn-sm btn-outline-info"
                               href="{{ route('pos.sales.generatePdfInvoiceOriginal', $sale_id) }}" target="_blank">
                                <i class="icofont icofont-file-pdf"></i> Original (Bruta)
                            </a>

                            <a class="btn btn-sm btn-outline-success {{ $sale_status == 'returned' ? 'disabled' : '' }}"
                               href="{{ route('pos.sales.generatePdfInvoice', $sale_id) }}" target="_blank">
                                <i class="icofont icofont-file-pdf"></i> Actualizada (Neta)
                            </a>

                            <span class="vr mx-1"></span>
                            <small class="text-muted fw-bold me-1">COMPROBANTES INTERNOS:</small>

                            <a class="btn btn-sm btn-outline-danger"
                               href="{{ route('pos.sales.generatePdfInternalOriginal', $sale_id) }}" target="_blank">
                                <i class="icofont icofont-file-pdf"></i> Interno Original
                            </a>

                            <a class="btn btn-sm btn-outline-warning {{ $sale_status == 'returned' ? 'disabled' : '' }}"
                               href="{{ route('pos.sales.generatePdfInternal', $sale_id) }}" target="_blank">
                                <i class="icofont icofont-file-pdf"></i> Interno Actualizado
                            </a>
                        </div>
                    @else
                        <button class="btn btn-sm btn-dark" type="button" data-bs-dismiss="modal">Cerrar</button>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
