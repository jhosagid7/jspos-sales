<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - {{ $customer->name }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
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
            font-size: 7.5pt;
        }
        .table td {
            padding: 4px;
            border-bottom: 1px solid #eee;
            font-size: 7.5pt;
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
        .summary-box {
            width: 100%;
            margin-top: 20px;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-label {
            font-weight: bold;
            width: 60%;
        }
        .summary-value {
            text-align: right;
        }
        .badge {
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 6pt;
            font-weight: bold;
        }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td class="business-info" width="60%">
                <h2>{{ $config->business_name }}</h2>
                <p>
                    {{ $config->address }}<br>
                    {{ $config->phone }}<br>
                    {{ $config->taxpayer_id }}
                </p>
            </td>
            <td class="report-info">
                <p>
                    Fecha : {{ \Carbon\Carbon::now()->format('d/m/Y') }}<br>
                    Hora : {{ \Carbon\Carbon::now()->format('h:i a') }}<br>
                    Pág : 1
                </p>
            </td>
        </tr>
    </table>

    <div class="report-title">ESTADO DE CUENTA GLOBAL</div>
    
    <div style="margin-bottom: 15px; font-size: 9pt; line-height: 1.4;">
        <strong>Cliente:</strong> {{ $customer->taxpayer_id }} - {{ strtoupper($customer->name) }}<br>
        <strong>Monedas:</strong> Dólares (USD)<br>
        <strong>Periodo:</strong> {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Inicio' }} al {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'Fin' }}<br>
        <strong>Usuario:</strong> {{ $user->name }}
    </div>

    <table class="table">
        <thead>
            <tr>
                <th width="10%">Fecha</th>
                <th width="10%">Ref.</th>
                <th width="45%">Concepto</th>
                <th width="10%" class="text-right">Débito ($)</th>
                <th width="10%" class="text-right">Crédito ($)</th>
                <th width="10%" class="text-right">Saldo ($)</th>
            </tr>
        </thead>
        <tbody>
            @php $runningBalance = 0; @endphp
            @foreach($ledger as $item)
                @php 
                    $runningBalance += ($item->debit_usd - $item->credit_usd);
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->t_date)->format('d/m/Y') }}</td>
                    <td>{{ $item->reference }}</td>
                    <td>
                        {{ $item->concept }}
                    </td>
                    <td class="text-right">
                        {{ $item->debit_usd > 0 ? number_format($item->debit_usd, 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $item->credit_usd > 0 ? number_format($item->credit_usd, 2) : '-' }}
                    </td>
                    <td class="text-right" style="font-weight: bold;">
                        {{ number_format($runningBalance, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="width: 100%;">
        <table style="width: 40%; margin-left: auto; border-collapse: collapse;">
            <tr style="background-color: #f2f2f2;">
                <td style="padding: 8px; font-weight: bold; border: 1px solid #ddd;">Total Ventas:</td>
                <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${{ number_format($totals['totalSales'], 2) }}</td>
            </tr>
            <tr style="background-color: #f2f2f2;">
                <td style="padding: 8px; font-weight: bold; border: 1px solid #ddd;">Total Abonos:</td>
                <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${{ number_format($totals['totalPayments'], 2) }}</td>
            </tr>
            <tr style="background-color: #f2f2f2;">
                <td style="padding: 8px; font-weight: bold; border: 1px solid #ddd;">Total Devoluciones:</td>
                <td style="padding: 8px; text-align: right; border: 1px solid #ddd;">${{ number_format($totals['totalReturns'], 2) }}</td>
            </tr>
            <tr style="background-color: #d9d9d9;">
                <td style="padding: 8px; font-weight: bold; border: 1px solid #ddd; font-size: 10pt;">SALDO ACTUAL:</td>
                <td style="padding: 8px; text-align: right; border: 1px solid #ddd; font-weight: bold; font-size: 10pt;">${{ number_format($totals['balance'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 50px; text-align: center;">
        <div style="border-top: 1px solid #000; width: 200px; margin: 0 auto;"></div>
        <p style="font-size: 8pt;">Firma Autorizada / Sello</p>
    </div>

</body>
</html>
