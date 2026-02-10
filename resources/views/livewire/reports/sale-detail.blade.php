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
                            
                            // Simplified reverse calc for Comm/Diff
                            $commAmount = 0;
                            $diffAmount = 0;
                            
                            // Only calculate if we have percentages enabled
                            if ($salesObt->is_foreign_sale) { 
                                // Re-calculate base excluding TOTAL freight
                                $totalWithoutFreight = $salesObt->total - $totalFreightAmount;
                                $combinedPercent = ($commPercent + $diffPercent) / 100;
                                
                                if ($combinedPercent >= 0) { 
                                     $baseAmount = $totalWithoutFreight / (1 + $combinedPercent);
                                     $commAmount = $baseAmount * ($commPercent / 100);
                                     $diffAmount = $baseAmount * ($diffPercent / 100);
                                }
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
                                            
                                            // Percentages
                                            $commPct = $salesObt->applied_commission_percent ?? 0;
                                            $diffPct = $salesObt->applied_exchange_diff_percent ?? 0;
                                            $combinedPct = ($commPct + $diffPct) / 100;

                                            // Detect Additive Freight (Global Check or Per Item?)
                                            // Ideally we pass this from controller, but here we can check:
                                            // If SaleTotal > Sum(Items), it's additive.
                                            // Only calculate once using the parent object if possible, but inside loop we can use a flag?
                                            // Let's assume consistent behavior for the sale.
                                            // We can check if we haven't already.
                                            
                                            // Actually, let's just do the check here. 
                                            // Need to be careful about scope. 
                                            // $details is a collection.
                                            $rawItemsSum = $details->sum(function($d) { return $d->quantity * $d->sale_price; });
                                            $isAdditive = ($salesObt->total - $rawItemsSum) > 0.01;
                                            
                                            // 1. Calculate Base Total (Importe Base)
                                            if ($isAdditive) {
                                                // If Additive, Price IS Base (User says "Base 10" for $1 item).
                                                // And User says "Base includes comission".
                                                // So we do NOT strip anything.
                                                $baseTotal = $finalImporte;
                                                
                                                // Unit Price is just sale_price
                                                $baseUnit = $finalUnitSalePrice;
                                                
                                                // Additional Charges?
                                                // If Base includes them, do we show them separately?
                                                // View has a column "Cargos Adic.".
                                                // If we show 0 here, it implies no commission?
                                                // But User said "Base includes...".
                                                // Maybe we should calculate what the commission WOULD be?
                                                // If Base $10 includes commission... wait.
                                                // If Commission is 8% ON TOP of Base.
                                                // And Base is $10. Total $10.8.
                                                // If User says "Base includes commission", maybe they mean "The Price I set ($1) includes it".
                                                // If so, $1 is the Base.
                                                
                                                // Let's set Additional Charges to 0 for now if Additive, 
                                                // OR calculate them if they are supposed to be informational?
                                                // "Cargos Adic" column usually adds to the total?
                                                // Row: Unit | Subtotal | Freight | Adic | Total.
                                                // $1 | $10 | $1 | $0 | $11.
                                                // This matches 10+1=11.
                                                // If we put $0.8 in Adic...
                                                // $1 | $10 | $1 | $0.8 | $11.8. 
                                                // Total would be wrong.
                                                // So Adic MUST be 0 if it's included in Base.
                                                
                                                $additionalCharges = 0;

                                            } else {
                                                // Inclusive Logic (Old)
                                                // Formula: (FinalImporte - Freight) / (1 + Combined%)
                                                $cleanTotal = max(0, $finalImporte - $totalFreight);
                                                $baseTotal = $cleanTotal / (1 + $combinedPct);
                                                
                                                // 2. Calculate Base Unit Price
                                                $baseUnit = ($qty > 0) ? ($baseTotal / $qty) : 0;
                                                
                                                // 3. Calculate Additional Charges Amount
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
                                                $commPct = $salesObt->applied_commission_percent ?? 0;
                                                $diffPct = $salesObt->applied_exchange_diff_percent ?? 0;
                                                $combinedPct = ($commPct + $diffPct) / 100;

                                                // Calculate Additive on the fly using the collection
                                                $rawItemsSum = $details->sum(function($d) { return $d->quantity * $d->sale_price; });
                                                // Assuming salesObt is available (it is, from lines above)
                                                // We need to access $salesObt from the outer scope? Yes, it's available in the view.
                                                $isAdditive = ($salesObt->total - $rawItemsSum) > 0.01;

                                                $sumBaseTotal = $details->sum(function ($item) use ($combinedPct, $isAdditive) {
                                                    $totalSale = $item->sale_price * $item->quantity;
                                                    
                                                    if ($isAdditive) {
                                                        // Base is just Price * Qty
                                                        return $totalSale;
                                                    } else {
                                                        $totalFreight = $item->freight_amount;
                                                        $cleanTotal = max(0, $totalSale - $totalFreight);
                                                        return $cleanTotal / (1 + $combinedPct);
                                                    }
                                                });
                                            @endphp
                                            {{ $currencySymbol }}{{ number_format($sumBaseTotal, 2) }}
                                        </td>
                                        <td class="text-center">
                                            {{ $currencySymbol }}{{ number_format($details->sum('freight_amount'), 2) }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $sumAdditional = $details->sum(function ($item) use ($combinedPct, $isAdditive) {
                                                    
                                                    if ($isAdditive) {
                                                        // If Base includes commission, then Additional is 0 (or included).
                                                        // We display 0 to avoid double counting in the total.
                                                        return 0;
                                                    } else {
                                                        $totalSale = $item->sale_price * $item->quantity;
                                                        $totalFreight = $item->freight_amount;
                                                        $cleanTotal = max(0, $totalSale - $totalFreight);
                                                        $baseTotal = $cleanTotal / (1 + $combinedPct);
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
                                        'zelle' => 'zelle',
                                        default => 'cash'
                                    };

                                    $allPayments->push((object)[
                                        'type' => 'abono',
                                        'method' => $method,
                                        'bank_name' => $pay->bank,
                                        'currency' => $pay->currency,
                                        'amount' => $pay->amount,
                                        'rate' => $displayRate,
                                        'amount_primary' => $amountInPrimary,
                                        'reference' => $pay->deposit_number ?? $pay->reference ?? ($pay->bankRecord ? $pay->bankRecord->reference : null),
                                        'account' => $pay->account_number,
                                        'zelle_record' => $pay->zelleRecord,
                                        'bank_record' => $pay->bankRecord, // Link BankRecord
                                        // Discount info
                                        'discount_amount' => $pay->discount_applied,
                                        'discount_percentage' => $pay->discount_percentage,
                                        'discount_reason' => $pay->discount_reason,
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
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="5" class="text-end"><b>Total Pagado:</b></td>
                                                <td class="text-center"><b>{{ number_format($allPayments->sum('amount_primary'), 2) }}</b></td>
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

                <div class="modal-footer">
                    @if (!is_null($sale_id))
                        <button class="btn btn-sm btn-outline-dark" wire:click="printSale({{ $sale_id }})" title="Imprimir Ticket Cliente">
                            Ticket Venta
                            <i class="text-info icofont icofont-ticket fa-2x"></i>
                        </button>

                        <button class="btn btn-sm btn-outline-dark" wire:click="printInternalTicket({{ $sale_id }})" title="Imprimir Ticket Interno Contable">
                            Ticket Interno
                            <i class="text-warning icofont icofont-ticket fa-2x"></i>
                        </button>

                        <a class="btn btn-sm btn-outline-dark" 
                           href="{{ route('pos.sales.generatePdfInternal', $sale_id) }}" target="_blank" title="Imprimir Comprobante Contable">
                            PDF Interno
                            <i class="text-danger icofont icofont-file-pdf fa-2x"></i>
                        </a>

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
