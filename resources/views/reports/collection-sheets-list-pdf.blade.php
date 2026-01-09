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
            .header-info { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
            .logo-container { text-align: center; margin-bottom: 10px; }
            .badge { padding: 2px 5px; border-radius: 3px; color: white; font-weight: bold; }
            .badge-success { background-color: #28a745; }
            .badge-dark { background-color: #343a40; }
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
                        <strong>Relación de Cobro General</strong><br>
                        <strong>Fecha Reporte:</strong> {{ $date }}<br>
                        <strong>Generado por:</strong> {{ $user->name }}
                    </td>
                    <td class="text-right" style="border: none;">
                        <strong>{{ $config->business_name }}</strong><br>
                        {{ $config->address }}<br>
                        NIT: {{ $config->taxpayer_id }}
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
