<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Comisiones</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            color: white;
        }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: black; }
        .badge-danger { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Comisiones</h2>
        <p>
            <strong>Fecha:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} <br>
            <strong>Vendedor:</strong> {{ $sellerName }} <br>
            <strong>Periodo:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
        </p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Factura</th>
                <th>Vendedor</th>
                <th>Cliente</th>
                <th>Total Venta</th>
                <th>% Com.</th>
                <th>Monto Com.</th>
                <th>Estado</th>
                <th>Pago</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commissions as $sale)
                <tr>
                    <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                    <td>{{ $sale->invoice_number ?? 'N/A' }}</td>
                    <td>{{ $sale->customer->seller->name ?? 'N/A' }}</td>
                    <td>{{ $sale->customer->name }}</td>
                    <td class="text-right">${{ number_format($sale->total, 2) }}</td>
                    <td class="text-right">{{ number_format($sale->applied_commission_percent, 2) }}%</td>
                    <td class="text-right">${{ number_format($sale->final_commission_amount, 2) }}</td>
                    <td>
                        @if($sale->commission_status == 'paid')
                            <span class="badge badge-success">PAGADA</span>
                        @else
                            <span class="badge badge-warning">PENDIENTE</span>
                        @endif
                    </td>
                    <td>
                        @if($sale->commission_status == 'paid')
                            {{ number_format($sale->commission_payment_amount, 2) }} {{ $sale->commission_payment_currency }}
                            <br>
                            <small>Tasa: {{ number_format($sale->commission_payment_rate, 4) }}</small>
                            <br>
                            <small>
                                <b>{{ $sale->commission_payment_method }}</b>
                                @if($sale->commission_payment_method == 'Bank')
                                    - {{ $sale->commission_payment_bank_name }}
                                    <br>Ref: {{ $sale->commission_payment_reference }}
                                @endif
                            </small>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <strong>Total Comisiones:</strong> ${{ number_format($commissions->sum('final_commission_amount'), 2) }}
    </div>
</body>
</html>
