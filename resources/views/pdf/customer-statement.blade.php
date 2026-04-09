<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estado de Cuenta - {{ $customer->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company-name { font-size: 20px; font-bold; }
        .report-title { font-size: 16px; color: #555; margin-top: 5px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .ledger-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .ledger-table th { background-color: #f2f2f2; border: 1px solid #ddd; padding: 8px; text-align: center; }
        .ledger-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #777; }
        .summary-box { float: right; width: 250px; margin-top: 20px; border: 1px solid #ddd; padding: 10px; background: #fafafa; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .balance-row { font-size: 14px; font-weight: bold; border-top: 1px solid #333; padding-top: 5px; margin-top: 5px; }
        .text-danger { color: #d9534f; }
        .text-success { color: #5cb85c; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name text-capitalize">{{ $config->business_name ?? 'JSPOS' }}</div>
        <div class="report-title">ESTADO DE CUENTA GLOBAL</div>
        <div style="font-size: 10px;">Fecha de Impresión: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Cliente:</strong></td>
            <td width="35%">{{ $customer->name }}</td>
            <td width="15%"><strong>Vendedor:</strong></td>
            <td width="35%">{{ $customer->seller->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>R.I.F / C.I:</strong></td>
            <td>{{ $customer->taxpayer_id ?? 'N/A' }}</td>
            <td><strong>Periodo:</strong></td>
            <td>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th width="15%">Fecha</th>
                <th width="35%" class="text-left">Concepto</th>
                <th width="15%">Ref.</th>
                <th width="10%">Debe (+)</th>
                <th width="10%">Haber (-)</th>
                <th width="15%">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @php $running = 0; @endphp
            @foreach($ledger as $row)
            @php $running += ($row->debit_usd - $row->credit_usd); @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->t_date)->format('d/m/Y') }}</td>
                <td class="text-left text-uppercase" style="font-size: 10px;">{{ $row->concept }}</td>
                <td>{{ $row->reference }}</td>
                <td class="text-right">{{ $row->debit_usd > 0 ? number_format($row->debit_usd, 2) : '-' }}</td>
                <td class="text-right">{{ $row->credit_usd > 0 ? number_format($row->credit_usd, 2) : '-' }}</td>
                <td class="text-right" style="background-color: #f9f9f9;"><strong>{{ number_format($running, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <div style="width: 100%;">
            <span style="display: inline-block; width: 120px;">Total Ventas:</span>
            <span style="display: inline-block; width: 100px;" class="text-right">${{ number_format($totalSales, 2) }}</span>
        </div>
        <div style="width: 100%;">
            <span style="display: inline-block; width: 120px;">Total Pagos:</span>
            <span style="display: inline-block; width: 100px;" class="text-right text-success">-${{ number_format($totalPayments, 2) }}</span>
        </div>
        <div style="width: 100%;">
            <span style="display: inline-block; width: 120px;">Total Devoluciones:</span>
            <span style="display: inline-block; width: 100px;" class="text-right text-warning">-${{ number_format($totalReturns, 2) }}</span>
        </div>
        <div style="width: 100%; border-top: 1px solid #333; margin-top: 5px; padding-top: 5px; font-weight: bold;">
            <span style="display: inline-block; width: 120px;">Saldo Pendiente:</span>
            <span style="display: inline-block; width: 100px;" class="text-right {{ $currentBalance > 0 ? 'text-danger' : 'text-success' }}">${{ number_format($currentBalance, 2) }}</span>
        </div>
    </div>

    <div class="footer">
        Este documento es un resumen informativo de su cuenta. Por favor reporte cualquier discrepancia a la brevedad.
    </div>
</body>
</html>
