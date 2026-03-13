<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Relación de Cobros - {{ $sheet->sheet_number }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            margin-bottom: 10px;
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 14pt;
        }
        .report-info {
            text-align: right;
        }
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f2f2f2;
            border-bottom: 2px solid #ddd;
            padding: 5px;
            text-align: left;
            font-size: 8pt;
        }
        .table td {
            padding: 5px;
            border-bottom: 1px solid #eee;
            font-size: 8pt;
            vertical-align: top;
        }
        .customer-header {
            background-color: #d9d9d9;
            font-weight: bold;
            padding: 5px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row td {
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .footer-totals {
            width: 100%;
            margin-top: 20px;
        }
        .footer-totals td {
            vertical-align: top;
        }
        .summary-box {
            width: 250px;
            float: left;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 3px;
        }
        .summary-label {
            font-weight: bold;
        }
        .nc-row {
            color: #d9534f;
        }
        .invoice-links {
            font-size: 7pt;
            color: #666;
            margin-top: 2px;
            display: block;
        }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td class="business-info" width="60%">
                <h2>{{ $config->business_name }}</h2>
                @if($config->logo)
                    <!-- <img src="{{ public_path('storage/' . $config->logo) }}" height="40"> -->
                @endif
                <p>
                    {{ $config->address }}<br>
                    {{ $config->phone }}<br>
                    {{ $config->taxpayer_id }}
                </p>
            </td>
            <td class="report-info">
                <p>
                    <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($sheet->opened_at)->format('d/m/Y') }}<br>
                    <strong>Hora:</strong> {{ \Carbon\Carbon::parse($sheet->opened_at)->format('h:i a') }}<br>
                    <strong>Pág:</strong> 1
                </p>
            </td>
        </tr>
    </table>

    <div class="report-title">Relación de Cobros</div>
    
    <p>
        <strong>Planilla:</strong> {{ $sheet->sheet_number }}<br>
        <strong>Usuario:</strong> {{ $user->name }}
    </p>

    <table class="table">
        <thead>
            <tr>
                <th>Operación</th>
                <th>Fecha Pago</th>
                <th>Fecha Emisión</th>
                <th class="text-center">Días</th>
                <th>No. Documento</th>
                <th>Descripción</th>
                <th class="text-right">Monto</th>
                <th class="text-right">Total Ingreso</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Union of Payments and Returns filtered by sheet
                $activity = collect();
                
                foreach($payments as $p) {
                    $description = strtoupper($p->pay_way);
                    if ($p->pay_way == 'zelle' && $p->zelleRecord) {
                        $description .= " (Sender: {$p->zelleRecord->sender_name}, Ref: {$p->zelleRecord->reference})";
                    } elseif (($p->pay_way == 'bank' || $p->pay_way == 'deposit') && $p->bank) {
                        $description .= " ({$p->bank}, Ref: {$p->deposit_number})";
                    } elseif ($p->deposit_number) {
                        $description .= " (Ref: {$p->deposit_number})";
                    }

                    if ($p->discount_applied > 0) {
                        $description .= " [Desc: $" . number_format($p->discount_applied, 2) . "]";
                    }

                    $dateEmit = \Carbon\Carbon::parse($p->sale->created_at);
                    $datePay = \Carbon\Carbon::parse($p->payment_date);
                    $creditDays = $p->sale->credit_days ?? 0;
                    $dueDate = $dateEmit->copy()->addDays($creditDays);
                    
                    // Signed difference: PayDate - DueDate
                    // If payed on 15 and due on 10 -> 5 days late
                    // If payed on 8 and due on 10 -> -2 days late (early)
                    $daysDiff = $dueDate->diffInDays($datePay, false);

                    $activity->push([
                        'type' => 'Pago',
                        'customer_id' => $p->sale->customer_id,
                        'customer_name' => $p->sale->customer->name,
                        'customer_doc' => $p->sale->customer->taxpayer_id,
                        'date_pay' => $datePay,
                        'date_emit' => $dateEmit,
                        'days' => $daysDiff,
                        'doc_number' => $p->sale->invoice_number ?? $p->sale->id,
                        'description' => $description,
                        'monto' => $p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1),
                        'ingreso' => ($p->pay_way == 'advance' || $p->pay_way == 'adelanto') ? 0 : ($p->amount / ($p->exchange_rate > 0 ? $p->exchange_rate : 1)),
                        'sale_id' => $p->sale_id,
                        'raw_amount' => $p->amount,
                        'currency' => $p->currency,
                        'rate' => $p->exchange_rate
                    ]);
                }

                foreach($returns as $r) {
                    $dateEmit = \Carbon\Carbon::parse($r->sale->created_at);
                    $datePay = \Carbon\Carbon::parse($r->created_at);
                    $creditDays = $r->sale->credit_days ?? 0;
                    $dueDate = $dateEmit->copy()->addDays($creditDays);
                    $daysDiff = $dueDate->diffInDays($datePay, false);

                    $activity->push([
                        'type' => 'N/C',
                        'customer_id' => $r->customer_id,
                        'customer_name' => $r->customer->name,
                        'customer_doc' => $r->customer->taxpayer_id,
                        'date_pay' => $datePay,
                        'date_emit' => $dateEmit,
                        'days' => $daysDiff,
                        'doc_number' => $r->return_number,
                        'description' => $r->reason ?? 'Nota de Crédito',
                        'monto' => $r->total_returned / ($r->sale->primary_exchange_rate > 0 ? $r->sale->primary_exchange_rate : 1),
                        'ingreso' => 0,
                        'sale_id' => $r->sale_id,
                        'raw_amount' => $r->total_returned,
                        'currency' => $r->sale->primary_currency_code ?? 'USD',
                        'rate' => $r->sale->primary_exchange_rate
                    ]);
                }

                $grouped = $activity->sortBy('date_pay')->groupBy('customer_id');
                $grandTotalMonto = 0;
                $grandTotalIngreso = 0;
            @endphp

            @foreach($grouped as $customerId => $items)
                @php
                    $first = $items->first();
                    $customerMonto = $items->sum('monto');
                    $customerIngreso = $items->sum('ingreso');
                @endphp
                <tr>
                    <td colspan="8" class="customer-header">
                        {{ $first['customer_doc'] }} - {{ strtoupper($first['customer_name']) }}
                    </td>
                </tr>
                @foreach($items as $item)
                    @php
                        $grandTotalMonto += $item['monto'];
                        $grandTotalIngreso += $item['ingreso'];
                    @endphp
                    <tr class="{{ $item['type'] == 'N/C' ? 'nc-row' : '' }}">
                        <td>{{ $item['type'] }}</td>
                        <td>{{ $item['date_pay']->format('d/m/Y') }}</td>
                        <td>{{ $item['date_emit']->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $item['days'] }}</td>
                        <td>{{ $item['doc_number'] }}</td>
                        <td>
                            {{ $item['description'] }}
                            @if($item['type'] == 'Pago')
                                <br><small>Tasa: {{ number_format($item['rate'], 2) }} | ({{ number_format($item['raw_amount'], 2) }} {{ $item['currency'] }})</small>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item['monto'], 4) }}</td>
                        <td class="text-right">{{ number_format($item['ingreso'], 4) }}</td>
                    </tr>
                @endforeach
                <tr style="border-bottom: 2px solid #ccc;">
                    <td colspan="6" class="text-right"><strong>Subtotal Cliente:</strong></td>
                    <td class="text-right"><strong>{{ number_format($customerMonto, 4) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($customerIngreso, 4) }}</strong></td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="6" class="text-right">TOTAL GENERAL:</td>
                <td class="text-right">{{ number_format($grandTotalMonto, 4) }}</td>
                <td class="text-right">{{ number_format($grandTotalIngreso, 4) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer-totals">
        <div class="summary-box">
            <table class="table-bordered">
                @foreach($totalsByCategory as $label => $total)
                    @php
                        // Show all cash/bank/NC categories even if 0 to match layout, hide others if 0
                        $isConfigured = !str_contains($label, 'OTROS');
                    @endphp
                    @if($total > 0 || $isConfigured)
                        <tr>
                            <td class="summary-label">{{ $label }}:</td>
                            <td class="text-right">{{ number_format($total, 4) }}</td>
                        </tr>
                    @endif
                @endforeach
                <tr style="border-top: 1px solid #000;">
                    <td class="summary-label">Total Ingreso:</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($grandTotalIngreso, 4) }}</td>
                </tr>
            </table>
        </div>
        
        <div class="summary-box" style="margin-left: 20px;">
            <table class="table-bordered">
                <thead>
                    <tr style="background-color: #eee;">
                        <th colspan="2" class="text-center">Detalle por Moneda</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($totalsByCurrency as $curr => $amt)
                        <tr>
                            <td class="summary-label">Total {{ $curr }}:</td>
                            <td class="text-right">{{ number_format($amt, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div style="float: right; width: 250px; text-align: center; margin-top: 50px;">
            <div style="border-top: 1px solid #000; width: 200px; margin: 0 auto;"></div>
            Firma Autorizada
        </div>
    </div>

</body>
</html>
