<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Relación de Cobro Detallado {{ $sheet->sheet_number }}</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

        <style type="text/css" media="screen">
            html { font-family: sans-serif; line-height: 1.15; margin: 0; }
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif; font-weight: 400; line-height: 1.5; color: #212529; text-align: left; background-color: #fff; font-size: 10px; margin: 10pt; }
            table { border-collapse: collapse; width: 100%; }
            th { text-align: inherit; }
            .table { width: 100%; margin-bottom: 1rem; color: #212529; }
            .table th, .table td { padding: 0.3rem; vertical-align: top; border: 1px solid #dee2e6; }
            .text-right { text-align: right !important; }
            .text-center { text-align: center !important; }
            .header-info { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
            .logo-container { text-align: center; margin-bottom: 10px; }
            .badge { padding: 2px 5px; border-radius: 3px; color: white; font-weight: bold; }
            .badge-success { background-color: #28a745; }
            .badge-danger { background-color: #dc3545; }
            .details-row td { background-color: #f8f9fa; font-size: 9px; color: #555; }
            @page { size: landscape; }
        </style>
    </head>
    <body>
        <div class="logo-container">
             @if($config->logo)
                <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="50">
            @else
                <h2>{{ strtoupper($config->business_name) }}</h2>
            @endif
        </div>

        <div class="header-info">
            <table class="table" style="border: none;">
                <tr style="border: none;">
                    <td style="border: none;">
                        <strong>Relación de Cobro Detallado</strong><br>
                        <strong>Fecha Reporte:</strong> {{ $date }}<br>
                        <strong>Generado por:</strong> {{ $user->name }}
                    </td>
                    <td class="text-right" style="border: none;">
                        <strong>{{ $config->business_name }}</strong><br>
                        {{ $config->address }}<br>
                        NIT: {{ $config->taxpayer_id }}<br>
                        <strong>Planilla #{{ $sheet->sheet_number }}</strong>
                    </td>
                </tr>
            </table>
            
            @if(count($filters) > 0)
                <div style="margin-top: 10px; font-size: 10px; background: #eee; padding: 5px;">
                    <strong>Filtros Aplicados:</strong>
                    @foreach($filters as $key => $val)
                        <span style="margin-right: 10px;"><strong>{{ $key }}:</strong> {{ $val }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <table class="table table-bordered">
            <thead style="background-color: #f8f9fa;">
                <tr>
                    <th>FECHA</th>
                    <th>OPERADOR</th>
                    <th>CLIENTE</th>
                    <th>FACTURA</th>
                    
                    <!-- Dynamic Cash Columns -->
                    @foreach($currencies as $currency)
                        <th class="text-right">EFECTIVO {{ $currency->code }}</th>
                    @endforeach
                    <!-- Dynamic Bank Columns -->
                    @foreach($banks as $bank)
                        <th class="text-right">{{ strtoupper($bank->name) }}</th>
                    @endforeach
                    <th class="text-right">OTROS BANCOS</th>
                    
                    <th class="text-center">ESTADO</th>
                    <th class="text-right">TOTAL (USD)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $groupedPayments = $payments->groupBy('sale_id');
                    $colSpan = 6 + count($currencies) + count($banks); 
                @endphp
                @foreach($groupedPayments as $saleId => $salePayments)
                    @php
                        $firstPayment = $salePayments->first();
                        $sale = $firstPayment->sale;
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
                            <td class="text-right">{{ number_format($cashTotal, 2) }}</td>
                        @endforeach

                        <!-- Bank Totals per Bank -->
                        @foreach($banks as $bank)
                            @php
                                $bankTotal = $salePayments->whereIn('pay_way', ['bank', 'deposit'])->where('bank', $bank->name)->sum('amount');
                                if (stripos($bank->name, 'zelle') !== false) {
                                    $bankTotal += $salePayments->where('pay_way', 'zelle')->sum('amount');
                                }
                            @endphp
                            <td class="text-right">{{ number_format($bankTotal, 2) }}</td>
                        @endforeach

                        <!-- Other Banks -->
                        @php
                            $knownBanks = $banks->pluck('name')->toArray();
                            $otherBanksTotal = $salePayments
                                ->whereIn('pay_way', ['bank', 'deposit'])
                                ->whereNotIn('bank', $knownBanks)
                                ->sum('amount');
                        @endphp
                        <td class="text-right">{{ number_format($otherBanksTotal, 2) }}</td>

                        <td class="text-center">
                            @if(!$isLate)
                                <span class="badge badge-success">A Tiempo</span>
                            @else
                                <span class="badge badge-danger">Mora</span>
                            @endif
                        </td>
                        <td class="text-right">${{ number_format($totalSaleUsd, 2) }}</td>
                    </tr>
                    
                    <!-- Details Row -->
                    <tr class="details-row">
                        <td colspan="{{ $colSpan }}">
                            <strong>Detalles de Pagos:</strong><br>
                            @foreach($salePayments as $p)
                                @if($p->pay_way == 'zelle')
                                    - Zelle: {{ optional($p->zelleRecord)->sender_name ?? 'N/A' }} | Ref: {{ optional($p->zelleRecord)->reference ?? 'N/A' }} | Fecha: {{ optional($p->zelleRecord)->zelle_date ?? 'N/A' }} | Monto: ${{ number_format($p->amount, 2) }}<br>
                                @elseif($p->pay_way == 'bank' || $p->pay_way == 'deposit')
                                    - Banco: {{ $p->bank }} | Cta: {{ $p->account_number }} | Ref: {{ $p->deposit_number }} | Fecha: {{ $p->payment_date }} | Monto: {{ number_format($p->amount, 2) }}<br>
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                    
                    <!-- Totals for Cash Columns -->
                    @foreach($currencies as $currency)
                         <td class="text-right"><strong>{{ number_format($payments->where('pay_way', 'cash')->where('currency', $currency->code)->sum('amount'), 2) }}</strong></td>
                    @endforeach

                    <!-- Totals for Bank Columns -->
                    @foreach($banks as $bank)
                        <td class="text-right"><strong>{{ number_format($payments->filter(function($p) use ($bank) {
                            $match = ($p->pay_way == 'bank' || $p->pay_way == 'deposit') && $p->bank == $bank->name;
                            if (stripos($bank->name, 'zelle') !== false && $p->pay_way == 'zelle') $match = true;
                            return $match;
                        })->sum('amount'), 2) }}</strong></td>
                    @endforeach
                    
                    <!-- Total Other Banks -->
                    <td class="text-right"><strong>{{ number_format($payments->filter(function($p) use ($knownBanks) {
                        return ($p->pay_way == 'bank' || $p->pay_way == 'deposit') && !in_array($p->bank, $knownBanks);
                    })->sum('amount'), 2) }}</strong></td>

                    <td></td>
                    <td class="text-right"><strong>${{ number_format($payments->sum(function($p) { return $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1); }), 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 20px; width: 50%;">
            <table class="table table-bordered">
                <thead style="background-color: #e9ecef;">
                    <tr>
                        <th colspan="2">RESUMEN POR MÉTODO DE PAGO (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($methods as $method => $amount)
                        <tr>
                            <td style="text-transform: capitalize;">
                                @if($method == 'cash') Efectivo
                                @elseif($method == 'deposit') Banco
                                @else {{ $method }}
                                @endif
                            </td>
                            <td class="text-right">${{ number_format($amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </body>
</html>
