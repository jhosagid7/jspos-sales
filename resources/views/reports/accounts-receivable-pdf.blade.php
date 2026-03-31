<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuentas Por Cobrar</title>
    <style>
        @page {
            margin: 30px 40px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1f2937; /* Tailwind gray-800 */
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #374151;
            padding-bottom: 10px;
        }
        .header-left {
            width: 70%;
            vertical-align: top;
        }
        .header-right {
            width: 30%;
            vertical-align: top;
            text-align: right;
            font-size: 10px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 15px;
            font-weight: bold;
            margin-top: 10px;
            color: #2563eb; /* Primary blue */
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .filter-info {
            margin-top: 5px;
            font-size: 10px;
            color: #4b5563;
        }
        .customer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
        }
        .customer-header-row td {
            background-color: #f3f4f6; /* Very Light Gray */
            color: #374151;
            font-weight: bold;
            padding: 4px 6px;
            border-bottom: 1px solid #d1d5db;
            text-transform: uppercase;
            font-size: 9px;
        }
        .customer-data-row td {
            padding: 6px;
            font-size: 11px;
        }
        .tx-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 30px;
        }
        .tx-header td {
            background-color: #1f2937; /* Dark Gray */
            color: #ffffff;
            font-weight: bold;
            padding: 5px;
            text-transform: uppercase;
            font-size: 9px;
        }
        .tx-data td {
            padding: 5px;
            border-bottom: 0.5px solid #e5e7eb;
        }
        .tx-amount {
            text-align: right;
            font-weight: bold;
        }
        .tx-footer td {
            padding-top: 8px;
            font-weight: bold;
        }
        .total-line {
            border-top: 2px solid #1f2937;
            text-align: right;
            padding-top: 5px;
            font-size: 12px;
        }
        .grand-total-row {
            margin-top: 30px;
            width: 100%;
            border-top: 1px solid #374151;
            padding-top: 10px;
        }
        .page-number:before {
            content: counter(page);
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="company-name">{{ $config->business_name }}</div>
                <div style="color: #4b5563;">{{ $config->address ?? '...' }}</div>
                <div style="color: #4b5563;">{{ $config->phone ?? '...' }}</div>
                <div class="report-title">CUENTAS POR COBRAR</div>
                <div class="filter-info">
                    @if($seller_name)
                        Vendedor: {{ $seller_name }}<br>
                    @endif
                    @if(isset($overdue_filter) && $overdue_filter != 'all')
                        Vencimiento: {{ $overdue_filter == 'overdue' ? 'VENCIDAS (ROJO)' : 'AL DÍA (VERDE)' }}<br>
                    @endif
                    <div>Moneda: {{ $config->currency->name ?? 'Dólares' }} (USD)</div>
                </div>
            </td>
            <td class="header-right">
                <table style="width:100%; border-collapse: collapse;">
                    <tr><td style="text-align:left; color: #6b7280;">Emisión:</td><td style="text-align:right; font-weight: bold;">{{ $date }}</td></tr>
                    <tr><td style="text-align:left; color: #6b7280;">Hora:</td><td style="text-align:right; font-weight: bold;">{{ $time ?? '' }}</td></tr>
                    <tr><td style="text-align:left; color: #6b7280;">Página:</td><td style="text-align:right; font-weight: bold;"><span class="page-number"></span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <main>
        @foreach($data as $key => $groupData)
            @php 
                $customer = $groupData['customer'] ?? null; 
                $txCount = 0;
                $groupTotal = 0;
                $showDetailedCustomer = ($groupBy == 'customer_id');
            @endphp
            
            @if($showDetailedCustomer)
                <table class="customer-table">
                    <tr class="customer-header-row">
                        <td style="width: 8%;">Código</td>
                        <td style="width: 30%;">Nombre / Razón Social</td>
                        <td style="width: 12%;">RIF / ID</td>
                        <td style="width: 15%;">Teléfono</td>
                        <td style="width: 35%;">Dirección</td>
                    </tr>
                    <tr class="customer-data-row">
                        <td style="font-weight: bold;">{{ $customer ? $customer->id : 'N/A' }}</td>
                        <td style="text-transform: uppercase; font-weight: bold;">{{ $groupData['name'] }}</td>
                        <td>{{ $customer ? $customer->taxpayer_id : '' }}</td>
                        <td>{{ $customer ? $customer->phone : '' }}</td>
                        <td style="text-transform: uppercase; font-size: 9px;">{{ $customer ? $customer->address : '' }}</td>
                    </tr>
                </table>
            @else
                <div style="background-color: #f3f4f6; padding: 6px; border: 1px solid #d1d5db; margin-bottom: 5px; font-weight: bold; text-transform: uppercase;">
                    {{ str_replace('_id', '', $groupBy) }}: {{ $groupData['name'] }}
                </div>
            @endif

            <table class="tx-table">
                <tr class="tx-header">
                    <td style="width:8%;">Tipo</td>
                    <td style="width:10%;">Emisión</td>
                    <td style="width:10%;">Vence</td>
                    @if(!$showDetailedCustomer)
                        <td style="width:18%;">Cliente</td>
                    @endif
                    <td style="width:5%; text-align:center;">Días</td>
                    <td style="width:12%;">Documento</td>
                    <td style="width:{{ $showDetailedCustomer ? '31%' : '17%' }};">Detalle / Descripción</td>
                    <td style="width:12%; text-align:right;">Saldo USD</td>
                </tr>
                
                @foreach($groupData['invoices'] as $invoice)
                    @php
                        $txCount++;
                        $groupTotal += $invoice['balance'];
                        // For non-customer grouping, we might want the customer name from the sale object
                        // But $invoice is a pre-formatted array from the controller. 
                        // The controller already puts the invoice description which includes the ID.
                    @endphp
                    <tr class="tx-data">
                        <td>{{ $invoice['operation'] }}</td>
                        <td>{{ $invoice['date'] }}</td>
                        <td>{{ $invoice['due_date'] }}</td>
                        @if(!$showDetailedCustomer)
                            <td style="font-size: 9px; text-transform: uppercase;">{{ $invoice['customer_name'] ?? 'N/A' }}</td>
                        @endif
                        <td style="text-align:center; color: {{ $invoice['days'] > 0 ? '#ef4444' : '#10b981' }};">
                            {{ $invoice['days'] }}
                        </td>
                        <td style="font-weight: bold;">{{ $invoice['doc_no'] }}</td>
                        <td style="font-size: 9px;">{{ $invoice['description'] }}</td>
                        <td class="tx-amount">{{ number_format($invoice['balance'], 4) }}</td>
                    </tr>

                    @if(isset($invoice['credit_notes']))
                        @foreach($invoice['credit_notes'] as $nc)
                            @php
                                $txCount++;
                            @endphp
                            <tr class="tx-data" style="color: #6b7280; font-style: italic;">
                                <td>{{ $nc['operation'] }}</td>
                                <td>{{ $nc['date'] }}</td>
                                <td>{{ $nc['due_date'] }}</td>
                                @if(!$showDetailedCustomer)
                                    <td></td>
                                @endif
                                <td style="text-align:center;">{{ $nc['days'] }}</td>
                                <td>{{ $nc['doc_no'] }}</td>
                                <td style="font-size: 9px;">{{ $nc['description'] }}</td>
                                <td class="tx-amount" style="color: #ef4444;">-{{ number_format(abs($nc['amount']), 4) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach

                <tr class="tx-footer">
                    <td colspan="{{ $showDetailedCustomer ? 5 : 6 }}" style="color: #6b7280; font-weight: normal; font-size: 10px;">
                        Documentos: {{ $txCount }}
                    </td>
                    <td style="text-align: right; padding-right: 10px;">TOTAL GRUPO:</td>
                    <td class="total-line">{{ number_format($groupData['total_debt'], 4) }}</td>
                </tr>
            </table>
        @endforeach
        
        <table class="grand-total-row">
            <tr>
                <td style="width: 70%; text-align: right; padding-right: 20px; font-weight: bold; font-size: 11px;">Total General por Cobrar :</td>
                <td style="width: 30%; text-align: right; font-weight: bold; font-size: 11px; border-bottom: 3px double #1f2937;">{{ number_format($grandTotalDebt, 4) }}</td>
            </tr>
            <tr>
                <td style="width: 70%; text-align: right; padding-right: 20px;">Total Registros :</td>
                <td style="width: 30%; text-align: right;">{{ count($data) }}</td>
            </tr>
        </table>
    </main>
</body>
</html>
