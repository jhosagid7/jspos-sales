<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title">
                        <b>{{ $componentName ?? 'Relación de Cobro Básico' }}</b>
                    </h4>
                    <div>
                        <div class="btn-group mr-2">
                            <button wire:click="generatePdf('basic')" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> PDF Básico
                            </button>
                            <button wire:click="generatePdf('detailed')" class="btn btn-dark">
                                <i class="fas fa-file-alt"></i> PDF Detallado
                            </button>
                        </div>
                        <button wire:click="closeDetails" class="btn btn-outline-dark">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="badge badge-info">Fecha: {{ $sheet->opened_at->format('d/m/Y') }}</span>
                    <span class="badge badge-success">Total: ${{ number_format($sheet->total_amount, 2) }}</span>
                </div>
            </div>

            <div class="widget-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white">FECHA</th>
                                <th class="table-th text-white">OPERADOR</th>
                                <th class="table-th text-white">CLIENTE</th>
                                <th class="table-th text-white">FACTURA</th>
                                
                                <!-- Dynamic Cash Columns -->
                                @foreach($currencies as $currency)
                                    <th class="table-th text-white">EFECTIVO {{ $currency->code }}</th>
                                @endforeach
                                <!-- Dynamic Bank Columns -->
                                @foreach($banks as $bank)
                                    <th class="table-th text-white">{{ strtoupper($bank->name) }}</th>
                                @endforeach
                                <th class="table-th text-white">OTROS BANCOS</th>
                                
                                <th class="table-th text-white">ESTADO</th>
                                <th class="table-th text-white">TOTAL (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $groupedPayments = $payments->groupBy('sale_id');
                            @endphp
                            @foreach($groupedPayments as $saleId => $salePayments)
                                @php
                                    $firstPayment = $salePayments->first();
                                    $sale = $firstPayment->sale;
                                    // Safety check for sale
                                    if (!$sale) continue; 
                                    
                                    $totalSaleUsd = $salePayments->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); });
                                    
                                    $isLate = $salePayments->contains(function ($p) { return !$p->is_on_time; });
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($firstPayment->created_at)->format('H:i') }}</td>
                                    <td>{{ optional($firstPayment->user)->name ?? 'N/A' }}</td>
                                    <td>{{ optional($sale->customer)->name ?? 'N/A' }}</td>
                                    <td>{{ $sale->invoice_number ?? $sale->id }}</td>
                                    
                                    <!-- Cash Totals per Currency -->
                                    @foreach($currencies as $currency)
                                        @php
                                            $cashTotal = $salePayments->where('pay_way', 'cash')->where('currency', $currency->code)->sum('amount');
                                        @endphp
                                        <td>{{ number_format($cashTotal, 2) }}</td>
                                    @endforeach

                                    <!-- Bank Totals per Bank -->
                                    @foreach($banks as $bank)
                                        @php
                                            $bankTotal = $salePayments->whereIn('pay_way', ['bank', 'deposit'])->where('bank', $bank->name)->sum('amount');
                                            if (stripos($bank->name, 'zelle') !== false) {
                                                $bankTotal += $salePayments->where('pay_way', 'zelle')->sum('amount');
                                            }
                                        @endphp
                                        <td>{{ number_format($bankTotal, 2) }}</td>
                                    @endforeach

                                    <!-- Other Banks -->
                                    @php
                                        $knownBanks = $banks->pluck('name')->toArray();
                                        $otherBanksTotal = $salePayments
                                            ->whereIn('pay_way', ['bank', 'deposit'])
                                            ->whereNotIn('bank', $knownBanks)
                                            ->sum('amount');
                                    @endphp
                                    <td>{{ number_format($otherBanksTotal, 2) }}</td>

                                    <td>
                                        @if(!$isLate)
                                            <span class="badge badge-success">A Tiempo</span>
                                        @else
                                            <span class="badge badge-danger">Mora</span>
                                        @endif
                                    </td>
                                    <td>${{ number_format($totalSaleUsd, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right font-weight-bold">TOTAL FILTRADO:</td>
                                
                                <!-- Totals for Cash Columns -->
                                @foreach($currencies as $currency)
                                     <td class="font-weight-bold">{{ number_format($payments->where('pay_way', 'cash')->where('currency', $currency->code)->sum('amount'), 2) }}</td>
                                @endforeach

                                <!-- Totals for Bank Columns -->
                                @foreach($banks as $bank)
                                    <td class="font-weight-bold">{{ number_format($payments->filter(function($p) use ($bank) {
                                        $match = ($p->pay_way == 'bank' || $p->pay_way == 'deposit') && $p->bank == $bank->name;
                                        if (stripos($bank->name, 'zelle') !== false && $p->pay_way == 'zelle') $match = true;
                                        return $match;
                                    })->sum('amount'), 2) }}</td>
                                @endforeach
                                
                                <!-- Total Other Banks -->
                                <td class="font-weight-bold">{{ number_format($payments->filter(function($p) use ($knownBanks) {
                                    return ($p->pay_way == 'bank' || $p->pay_way == 'deposit') && !in_array($p->bank, $knownBanks);
                                })->sum('amount'), 2) }}</td>

                                <td></td>
                                <td class="font-weight-bold">
                                    ${{ number_format($payments->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); }), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
