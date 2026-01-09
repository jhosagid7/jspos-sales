<!DOCTYPE html>
<html lang="es">
<head>
    <title>Reporte de Ventas Diarias</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style type="text/css" media="screen">
        html {
            font-family: sans-serif;
            line-height: 1.15;
            margin: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            text-align: left;
            background-color: #fff;
            font-size: 10px;
            margin: 36pt;
        }

        h4 {
            margin-top: 0;
            margin-bottom: 0.5rem;
        }

        p {
            margin-top: 0;
            margin-bottom: 1rem;
        }

        strong {
            font-weight: bold;
        }

        img {
            vertical-align: middle;
            border-style: none;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            text-align: inherit;
        }

        h4, .h4 {
            margin-bottom: 0.5rem;
            font-weight: 500;
            line-height: 1.2;
            font-size: 1.5rem;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }

        .table th,
        .table td {
            padding: 0.5rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
            text-align: center;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-uppercase {
            text-transform: uppercase !important;
        }

        .text-green {
            color: #77b632;
        }

        .text-red {
            color: #ea5340;
        }

        .bg-light {
            background-color: #f8f9fa;
        }
        
        .customer-header {
            background-color: #e9ecef;
            font-weight: bold;
            padding: 5px;
        }

        .total-row {
            font-weight: bold;
            background-color: #dee2e6;
        }

        .grand-total {
            font-size: 1.2rem;
            font-weight: bold;
            background-color: #0380b2;
            color: white;
            padding: 10px;
        }
        
        .grand-total-row td {
            font-weight: bold;
            background-color: #0380b2;
            color: white;
            border-top: 2px solid #000;
        }

        .header-info {
            margin-bottom: 20px;
        }
        
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
        @php
            $config = \App\Models\Configuration::first();
        @endphp

        {{-- Header --}}
        <table class="table mt-1" style="margin-bottom: 0;">
            <tbody>
                <tr>
                    <td class="pl-0 border-0" width="25%" style="vertical-align: middle;">
                       @if($config && $config->logo)
                            <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="60">
                        @endif
                    </td>
                    <td class="border-0 text-center" width="50%" style="vertical-align: middle;">
                        <h4 class="text-uppercase invoice-title">
                            {{ $config->business_name ?? 'SISTEMA POS' }}
                        </h4>
                    </td>
                    <td class="border-0 text-right" width="25%" style="vertical-align: middle;">
                        <h4 class="text-uppercase report-title">
                            VENTAS DIARIAS
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
                            <strong class="text-uppercase" style="font-size: 14px;">{{ $config->business_name ?? '' }}</strong><br>
                            NIT: {{ $config->taxpayer_id ?? '' }}<br>
                            {{ $config->address ?? '' }}<br>
                            Tel: {{ $config->phone ?? '' }}
                        </td>

                        {{-- Report Details (Right) --}}
                        <td class="border-0 text-right pr-0" width="40%" style="vertical-align: top;">
                            Fecha Reporte: <strong>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</strong><br>
                            Generado por: <strong>{{ auth()->user()->name }}</strong><br>
                            @if($dateFrom && $dateTo)
                                Periodo: <strong>{{ $dateFrom }} - {{ $dateTo }}</strong>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    <table class="table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Cliente</th>
                <th>Total Neto</th>
                @foreach($currencies as $currency)
                    <th>Pagado {{ $currency->code }}</th>
                @endforeach
                
                @if($reportFormat == 'detailed')
                    @foreach($banks as $bank)
                        <th>{{ $bank->name }}</th>
                    @endforeach
                @endif

                <th>Crédito (USD)</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $key => $groupData)
                @if($groupBy != 'none')
                    <tr class="customer-header">
                        <td colspan="{{ 5 + count($currencies) + ($reportFormat == 'detailed' ? count($banks) : 0) }}">
                            @if($groupBy == 'customer_id') CLIENTE: 
                            @elseif($groupBy == 'user_id') USUARIO: 
                            @elseif($groupBy == 'seller_id') VENDEDOR: 
                            @elseif($groupBy == 'date') FECHA: 
                            @endif
                            {{ strtoupper($groupData['name']) }}
                        </td>
                    </tr>
                @endif

                @foreach ($groupData['sales'] as $sale)
                    @php
                        $paidPerCurrency = [];
                        $paidPerBank = [];
                        $totalPaidUSD = 0;
                        
                        foreach($currencies as $currency) {
                            $paidPerCurrency[$currency->code] = 0;
                        }
                        foreach($banks as $bank) {
                            $paidPerBank[$bank->id] = 0;
                        }

                        foreach($sale->paymentDetails as $payment) {
                            if($payment->method == 'bank' && $payment->bank_id) {
                                if(isset($paidPerBank[$payment->bank_id])) {
                                    $paidPerBank[$payment->bank_id] += $payment->amount;
                                }
                            } elseif(isset($paidPerCurrency[$payment->currency_code])) {
                                $paidPerCurrency[$payment->currency_code] += $payment->amount;
                            }
                            
                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                            $totalPaidUSD += ($payment->amount / $rate);
                        }
                        
                        if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                            $code = $sale->primary_currency_code ?? 'VED';
                            if(isset($paidPerCurrency[$code])) {
                                $paidPerCurrency[$code] += $sale->cash;
                            }
                            $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                            $totalPaidUSD += ($sale->cash / $rate);
                        }

                        $creditUSD = 0;
                        if($sale->status != 'paid' && $sale->status != 'returned') {
                            $creditUSD = max(0, $sale->total_usd - $totalPaidUSD);
                        }
                    @endphp
                    <tr class="text-center">
                        <td>{{ $sale->invoice_number ?? $sale->id }}</td>
                        <td class="text-uppercase">{{ $sale->customer->name }}</td>
                        <td>${{ number_format($sale->total, 2) }}</td>
                        
                        @foreach($currencies as $currency)
                            <td>
                                @php
                                    $amount = $paidPerCurrency[$currency->code] ?? 0;
                                    // If summarized, add bank payments of this currency to the total
                                    if($reportFormat == 'summarized') {
                                        foreach($sale->paymentDetails as $pd) {
                                            if($pd->method == 'bank' && $pd->currency_code == $currency->code) {
                                                $amount += $pd->amount;
                                            }
                                        }
                                    }
                                @endphp

                                @if($amount > 0)
                                    {{ number_format($amount, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach

                        @if($reportFormat == 'detailed')
                            @foreach($banks as $bank)
                                <td>
                                    @if($paidPerBank[$bank->id] > 0)
                                        {{ number_format($paidPerBank[$bank->id], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            @endforeach
                        @endif
                        
                        <td>
                            @if($creditUSD > 0.01)
                                <span class="text-red">${{ number_format($creditUSD, 2) }}</span>
                            @else
                                -
                            @endif
                        </td>
                        
                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                    </tr>
                    
                    @if($includeDetails)
                        <tr>
                            <td colspan="{{ 5 + count($currencies) + ($reportFormat == 'detailed' ? count($banks) : 0) }}" style="padding: 0 10px 10px 10px; font-size: 9px; color: #666; border-top: none;">
                                <strong>Detalles:</strong>
                                @foreach($sale->paymentDetails as $pd)
                                    @if($pd->reference || $pd->notes)
                                        <span style="margin-right: 15px;">
                                            [{{ $pd->method == 'bank' ? ($pd->bank->name ?? 'Banco') : 'Efectivo' }}]: 
                                            {{ $pd->reference ? 'Ref: ' . $pd->reference : '' }} 
                                            {{ $pd->notes ? '(' . $pd->notes . ')' : '' }}
                                        </span>
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                    @endif
                @endforeach
                
                @if($groupBy != 'none')
                    <tr class="total-row">
                        <td colspan="2" class="text-right">TOTAL:</td>
                        <td class="text-center">${{ number_format($groupData['total_usd'], 2) }}</td>
                        <td colspan="{{ 2 + count($currencies) + count($banks) }}"></td>
                    </tr>
                @endif

            @empty
                <tr>
                    <td colspan="{{ 5 + count($currencies) + count($banks) }}" class="text-center">Sin ventas registradas</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ 5 + count($currencies) + count($banks) }}">&nbsp;</td>
            </tr>
            <tr class="grand-total-row">
                <td colspan="2" class="text-right">TOTAL GENERAL:</td>
                <td class="text-center">${{ number_format($totalNeto, 2) }}</td>
                @foreach($currencies as $currency)
                    <td class="text-center">
                        @php
                            $total = $reportFormat == 'summarized' 
                                ? ($totalPaidPerCurrencySummarized[$currency->code] ?? 0)
                                : ($totalPaidPerCurrency[$currency->code] ?? 0);
                        @endphp
                        
                        @if($total > 0)
                            {{ number_format($total, 2) }}
                        @else
                            -
                        @endif
                    </td>
                @endforeach
                
                @if($reportFormat == 'detailed')
                    @foreach($banks as $bank)
                         <td class="text-center">
                            @if(isset($totalPaidPerBank[$bank->id]) && $totalPaidPerBank[$bank->id] > 0)
                                {{ number_format($totalPaidPerBank[$bank->id], 2) }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                @endif

                <td class="text-center">${{ number_format($totalCredit, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 50px; text-align: center; font-size: 10px; color: #666;">
        <p>Este reporte fue generado automáticamente por el sistema.</p>
    </div>
</body>
</html>
