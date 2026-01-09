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
    @php
        $config = \App\Models\Configuration::first();
    @endphp
    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="width: 25%; text-align: left;">
                @if($config->logo)
                    <img src="{{ public_path('storage/' . $config->logo) }}" alt="Logo" style="max-width: 100px; max-height: 80px;">
                @else
                    <img src="{{ public_path('assets/images/logo/logo-icon.png') }}" alt="Logo" style="max-width: 100px; max-height: 80px;">
                @endif
            </td>
            <td style="width: 50%; text-align: center;">
                <h2 style="margin: 0; font-size: 20px; font-weight: bold; color: #0380b2;">{{ $config->business_name }}</h2>
            </td>
            <td style="width: 25%; text-align: right;">
                <h3 style="margin: 0; font-size: 16px; font-weight: bold; color: #0380b2;">REPORTE DE COMISIONES</h3>
                <p style="margin: 0; font-size: 10px; font-weight: bold; color: #0380b2;">REPORTE</p>
            </td>
        </tr>
    </table>

    <div style="border: 1px solid #6B7280; border-radius: 15px; padding: 10px; margin-bottom: 20px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%; vertical-align: top; border: none;">
                    <p style="margin: 2px 0;"><strong>Empresa:</strong> {{ $config->business_name }}</p>
                    <p style="margin: 2px 0;"><strong>NIT:</strong> {{ $config->nit }}</p>
                    <p style="margin: 2px 0;"><strong>Dirección:</strong> {{ $config->address }}</p>
                    <p style="margin: 2px 0;"><strong>Teléfono:</strong> {{ $config->phone }}</p>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right; border: none;">
                    <p style="margin: 2px 0;"><strong>Fecha Reporte:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
                    <p style="margin: 2px 0;"><strong>Generado por:</strong> {{ $user->name }}</p>
                    <p style="margin: 2px 0;"><strong>Periodo:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
                    <p style="margin: 2px 0;"><strong>Vendedor:</strong> {{ $sellerName }}</p>
                </td>
            </tr>
        </table>
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
