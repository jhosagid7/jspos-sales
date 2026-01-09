<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Reporte de Planillas de Cobranza</title>
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
                            RELACIÓN DE COBRO GENERAL
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

        <div style="margin-bottom: 20px; width: 50%;">
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
        </div>

        <table class="table table-bordered">
            <thead style="background-color: #f8f9fa;">
                <tr>
                    <th>FOLIO</th>
                    <th>FECHA APERTURA</th>
                    <th>ESTADO</th>
                    <!-- Dynamic Cash Columns -->
                    @foreach($currencies as $currency)
                        <th class="text-right">EFECTIVO {{ $currency->code }}</th>
                    @endforeach
                    <!-- Dynamic Bank Columns -->
                    @foreach($banks as $bank)
                        <th class="text-right">{{ strtoupper($bank->name) }}</th>
                    @endforeach
                    <th class="text-right">OTROS BANCOS</th>
                    <th class="text-right">TOTAL RECAUDADO</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sheets as $sheet)
                    <tr>
                        <td>{{ $sheet->sheet_number }}</td>
                        <td>{{ $sheet->opened_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge {{ $sheet->status == 'open' ? 'badge-success' : 'badge-dark' }}">
                                {{ strtoupper($sheet->status) }}
                            </span>
                        </td>
                        
                        <!-- Cash Totals per Currency -->
                        @foreach($currencies as $currency)
                            @php
                                $cashTotal = $sheet->payments->where('pay_way', 'cash')->where('currency', $currency->code)->sum('amount');
                            @endphp
                            <td class="text-right">{{ number_format($cashTotal, 2) }}</td>
                        @endforeach

                        <!-- Bank Totals per Bank -->
                        @foreach($banks as $bank)
                            @php
                                // Base: Payments via bank/deposit for this specific bank
                                $bankTotal = $sheet->payments->whereIn('pay_way', ['bank', 'deposit'])->where('bank', $bank->name)->sum('amount');
                                
                                // Special Case: If this bank is "Zelle", also include payments with pay_way = 'zelle'
                                if (stripos($bank->name, 'zelle') !== false) {
                                    $zellePayments = $sheet->payments->where('pay_way', 'zelle')->sum('amount');
                                    $bankTotal += $zellePayments;
                                }
                            @endphp
                            <td class="text-right">{{ number_format($bankTotal, 2) }}</td>
                        @endforeach

                        <!-- Other Banks (not in list) -->
                        @php
                            $knownBanks = $banks->pluck('name')->toArray();
                            $otherBanksTotal = $sheet->payments
                                ->whereIn('pay_way', ['bank', 'deposit'])
                                ->whereNotIn('bank', $knownBanks)
                                ->sum('amount');
                        @endphp
                        <td class="text-right">{{ number_format($otherBanksTotal, 2) }}</td>

                        <td class="text-right"><strong>${{ number_format($sheet->total_amount, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                    
                    <!-- Totals for Cash Columns -->
                    @foreach($currencies as $currency)
                         <td class="text-right"><strong>{{ number_format($sheets->sum(function($s) use ($currency) { return $s->payments->where('pay_way', 'cash')->where('currency', $currency->code)->sum('amount'); }), 2) }}</strong></td>
                    @endforeach

                    <!-- Totals for Bank Columns -->
                    @foreach($banks as $bank)
                        <td class="text-right"><strong>{{ number_format($sheets->sum(function($s) use ($bank) { 
                            $total = $s->payments->whereIn('pay_way', ['bank', 'deposit'])->where('bank', $bank->name)->sum('amount');
                            if (stripos($bank->name, 'zelle') !== false) {
                                $total += $s->payments->where('pay_way', 'zelle')->sum('amount');
                            }
                            return $total;
                        }), 2) }}</strong></td>
                    @endforeach
                    
                    <!-- Total Other Banks -->
                    <td class="text-right"><strong>{{ number_format($sheets->sum(function($s) use ($knownBanks) { 
                        return $s->payments->whereIn('pay_way', ['bank', 'deposit'])->whereNotIn('bank', $knownBanks)->sum('amount'); 
                    }), 2) }}</strong></td>

                    <td class="text-right"><strong>${{ number_format($sheets->sum('total_amount'), 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>


    </body>
</html>
