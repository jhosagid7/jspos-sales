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
            .invoice-title {
                color: #0380b2;
                font-weight: bold;
                font-size: 20px;
                margin: 0;
            }
            .report-title {
                color: #0380b2;
                font-size: 16px;
                font-weight: bold;
                margin: 0;
            }
            .box-details {
                border: 1px solid #6B7280;
                border-radius: 15px;
                padding: 10px;
                margin-bottom: 20px;
            }
            .text-blue {
                color: #0380b2;
            }
        </style>
    </head>
    <body>
        {{-- Header --}}
        <table class="table mt-1" style="margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="pl-0 border-0" width="25%" style="vertical-align: middle;">
                       @if($config->logo)
                            <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="60">
                        @endif
                    </td>
                    <td class="border-0 text-center" width="50%" style="vertical-align: middle;">
                        <h4 class="text-uppercase invoice-title">
                            {{ $config->business_name }}
                        </h4>
                    </td>
                    <td class="border-0 text-right" width="25%" style="vertical-align: middle;">
                        <h4 class="text-uppercase report-title">
                            RELACIÓN DE COBRO DETALLADO
                        </h4>
                        <span style="font-size: 10px; font-weight: bold;">REPORTE</span>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Info Box --}}
        <div class="box-details">
            <table class="table border-0" style="margin: 0;">
                <tbody>
                    <tr>
                        {{-- Business Info (Left) --}}
                        <td class="border-0 pl-0" width="60%" style="vertical-align: top;">
                            <strong class="text-uppercase" style="font-size: 14px;">{{ $config->business_name }}</strong><br>
                            NIT: {{ $config->taxpayer_id }}<br>
                            {{ $config->address }}
                        </td>

                        {{-- Report Details (Right) --}}
                        <td class="border-0 text-right pr-0" width="40%" style="vertical-align: top;">
                            Planilla: <strong>#{{ $sheet->sheet_number }}</strong><br>
                            Fecha Reporte: <strong>{{ $date }}</strong><br>
                            Generado por: <strong>{{ $user->name }}</strong><br>
                            @if(count($filters) > 0)
                                <div style="margin-top: 5px; font-size: 9px;">
                                    <strong>Filtros:</strong><br>
                                    @foreach($filters as $key => $val)
                                        <span style="margin-right: 5px;">{{ $key }}: {{ $val }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <table style="width: 100%; border: none; margin-bottom: 20px;">
            <tr style="border: none;">
                <!-- Summary Table Column -->
                <td style="width: 40%; vertical-align: top; border: none; padding-right: 20px;">
                     <table class="table table-bordered">
                        <thead style="background-color: #e9ecef;">
                            <tr>
                                <th>MÉTODO / BANCO</th>
                                <th class="text-right">MONTO ORIGINAL</th>
                                <th class="text-right">TOTAL (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary as $item)
                                <tr>
                                    <td>{{ $item['name'] }}</td>
                                    <td class="text-right">
                                        ${{ number_format($item['original'], 2) }} {{ $item['currency'] }}
                                    </td>
                                    <td class="text-right">${{ number_format($item['equivalent'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>

                <!-- Commissions Table Column -->
                <td style="width: 60%; vertical-align: top; border: none;">
                    @if(isset($commissions) && count($commissions) > 0)
                        <table class="table table-bordered">
                            <thead style="background-color: #e9ecef;">
                                <tr>
                                    <th colspan="7" class="text-center">COMISIONES POR PAGAR</th>
                                </tr>
                                <tr>
                                    <th>FACTURA</th>
                                    <th>CLIENTE</th>
                                    <th class="text-right">BASE</th>
                                    <th class="text-right">TOTAL</th>
                                    <th class="text-center">%</th>
                                    <th class="text-right">A PAGAR (USD)</th>
                                    <th class="text-center">MONEDA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($commissions as $comm)
                                    <tr>
                                        <td>{{ $comm['invoice'] }}</td>
                                        <td>{{ Str::limit($comm['client'], 15) }}</td>
                                        <td class="text-right">${{ number_format($comm['base'], 2) }}</td>
                                        <td class="text-right">${{ number_format($comm['total_with_surcharges'], 2) }}</td>
                                        <td class="text-center">{{ $comm['percentage'] }}%</td>
                                        <td class="text-right">${{ number_format($comm['commission_usd'], 2) }}</td>
                                        <td class="text-center">
                                            @if($comm['payment_currency'] == 'USD')
                                                <span class="badge badge-success">Dólar</span>
                                            @elseif($comm['payment_currency'] == 'COP')
                                                <span class="badge badge-info">Pesos</span>
                                            @elseif($comm['payment_currency'] == 'VES')
                                                <span class="badge badge-warning">Bolívar</span>
                                            @else
                                                {{ $comm['payment_currency'] }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </td>
            </tr>
        </table>

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


    </body>
</html>
