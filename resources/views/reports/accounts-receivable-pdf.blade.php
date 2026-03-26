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
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-left {
            width: 70%;
            vertical-align: top;
        }
        .header-right {
            width: 30%;
            vertical-align: top;
            text-align: right;
            font-weight: bold;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
        }
        .filter-info {
            margin-top: 10px;
            font-size: 11px;
        }
        .customer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .customer-header-row td {
            background-color: #d1d5db; /* Gray */
            font-weight: bold;
            padding: 3px 5px;
        }
        .customer-data-row td {
            padding: 3px 5px;
        }
        .customer-details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .tx-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .tx-header td {
            background-color: #e5e7eb; /* Light Gray */
            font-weight: bold;
            padding: 4px;
            border-bottom: 1px solid #9ca3af;
        }
        .tx-data td {
            padding: 4px;
        }
        .tx-amount {
            text-align: right;
        }
        .tx-footer td {
            padding-top: 10px;
        }
        .total-line {
            border-top: 1px solid #000;
            text-align: right;
            padding-top: 3px;
        }
        .grand-total-row {
            margin-top: 40px;
            width: 100%;
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
                <div>{{ $config->address ?? '...' }}</div>
                <div>{{ $config->phone ?? '...' }}</div>
                <div class="report-title">CUENTAS POR COBRAR</div>
                <div class="filter-info">
                    <div>Activo : Todos</div>
                    <div>Monedas : {{ $config->currency->name ?? 'Dólares' }}</div>
                </div>
            </td>
            <td class="header-right">
                <table style="width:100%; border-collapse: collapse;">
                    <tr><td style="text-align:left; width:50%;">Fecha :</td><td style="text-align:right;">{{ $date }}</td></tr>
                    <tr><td style="text-align:left;">Hora :</td><td style="text-align:right;">{{ $time ?? '' }}</td></tr>
                    <tr><td style="text-align:left;">Pág :</td><td style="text-align:right;"><span class="page-number"></span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <main>
        @if($groupBy == 'customer_id')
            @foreach($data as $key => $groupData)
                @php 
                    $customer = $groupData['customer'] ?? null; 
                    $txCount = 0;
                    $groupTotal = 0;
                @endphp
                <table class="customer-table">
                    <!-- Primera Fila Gris -->
                    <tr class="customer-header-row">
                        <td style="width: 15%;">Código</td>
                        <td style="width: 40%;">Descripción</td>
                        <td style="width: 10%;">Activo</td>
                        <td style="width: 35%;">Dirección</td>
                    </tr>
                    <!-- Segunda Fila Gris -->
                    <tr class="customer-header-row">
                        <td></td>
                        <td>Teléfono</td>
                        <td>RIF</td>
                        <td>E-Mail</td>
                    </tr>
                    <!-- Primera Fila Info -->
                    <tr class="customer-data-row">
                        <td>{{ $customer ? $customer->id : '' }}</td>
                        <td style="text-transform: uppercase;">{{ $groupData['name'] }}</td>
                        <td>S</td>
                        <td style="text-transform: uppercase;">{{ $customer ? $customer->address : '' }}</td>
                    </tr>
                    <!-- Segunda Fila Info -->
                    <tr class="customer-data-row">
                        <td></td>
                        <td style="text-transform: uppercase;">{{ $customer ? $customer->phone : '' }}</td>
                        <td style="text-transform: uppercase;">{{ $customer ? $customer->taxpayer_id : '' }}</td>
                        <td style="text-transform: uppercase;">{{ $customer ? $customer->email : '' }}</td>
                    </tr>
                </table>

                <table class="tx-table">
                    <tr class="tx-header">
                        <td style="width:10%;">Operación</td>
                        <td style="width:12%;">Emisión</td>
                        <td style="width:12%;">Vencimiento</td>
                        <td style="width:8%; text-align:center;">Días</td>
                        <td style="width:15%;">No. Documento</td>
                        <td style="width:28%;">Descripción</td>
                        <td style="width:15%; text-align:right;">Monto</td>
                    </tr>
                    
                    @foreach($groupData['invoices'] as $invoice)
                        @php
                            $txCount++;
                            $groupTotal += $invoice['balance'];
                        @endphp
                        <tr class="tx-data">
                            <td>{{ $invoice['operation'] }}</td>
                            <td>{{ $invoice['date'] }}</td>
                            <td>{{ $invoice['due_date'] }}</td>
                            <td style="text-align:center;">{{ $invoice['days'] }}</td>
                            <td>{{ $invoice['doc_no'] }}</td>
                            <td>{{ $invoice['description'] }}</td>
                            <td class="tx-amount">{{ number_format($invoice['balance'], 4) }}</td>
                        </tr>

                        <!-- N/C applied to this invoice -->
                        @if(isset($invoice['credit_notes']))
                            @foreach($invoice['credit_notes'] as $nc)
                                @php
                                    $txCount++;
                                @endphp
                                <tr class="tx-data">
                                    <td>{{ $nc['operation'] }}</td>
                                    <td>{{ $nc['date'] }}</td>
                                    <td>{{ $nc['due_date'] }}</td>
                                    <td style="text-align:center;">{{ $nc['days'] }}</td>
                                    <td>{{ $nc['doc_no'] }}</td>
                                    <td>{{ $nc['description'] }}</td>
                                    <td class="tx-amount">{{ number_format($nc['amount'], 4) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach

                    <tr class="tx-footer">
                        <td colspan="5">Total Transacciones &nbsp;&nbsp;&nbsp; {{ $txCount }}</td>
                        <td></td>
                        <td class="total-line">{{ number_format($groupData['total_debt'], 4) }}</td>
                    </tr>
                </table>
            @endforeach
        @endif
        
        <table class="grand-total-row">
            <tr>
                <td style="width: 70%; text-align: right; padding-right: 20px; font-weight: bold; font-size: 11px;">Total General por Cobrar :</td>
                <td style="width: 30%; text-align: right; font-weight: bold; font-size: 11px;">{{ number_format($grandTotalDebt, 4) }}</td>
            </tr>
            <tr>
                <td style="width: 70%; text-align: right; padding-right: 20px;">Total Registros :</td>
                <td style="width: 30%; text-align: right;">{{ count($data) }}</td>
            </tr>
        </table>
    </main>
</body>
</html>
