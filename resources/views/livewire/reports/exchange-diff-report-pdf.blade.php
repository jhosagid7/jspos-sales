<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría de Diferencial Cambiario</title>
    <style>
        @page {
            margin: 0.5cm;
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
            margin-bottom: 5px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 11pt;
            color: #2c3e50;
        }
        .report-info {
            text-align: right;
            font-size: 8pt;
        }
        .report-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 10px;
            text-decoration: underline;
            text-transform: uppercase;
            color: #2c3e50;
        }
        .filter-info {
            margin-bottom: 10px;
            font-size: 8pt;
            background-color: #f8f9fa;
            padding: 5px;
            border: 1px solid #e9ecef;
            border-radius: 3px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .kpi-table th {
            background-color: #1a252f;
            color: white;
            padding: 4px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .kpi-table td {
            padding: 5px 6px;
            border: 1px solid #ddd;
            font-size: 7.2pt;
            text-align: center;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #34495e;
            color: white;
            border: 1px solid #34495e;
            padding: 4px 5px;
            text-align: center;
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table td {
            padding: 4px 5px;
            border: 1px solid #dee2e6;
            font-size: 7.2pt;
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 15px;
            margin-bottom: 5px;
            border-bottom: 1px solid #2c3e50;
            padding-bottom: 2px;
            text-transform: uppercase;
        }

        .footer-signatures {
            width: 100%;
            margin-top: 30px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto 5px auto;
        }
        .text-success { color: green; }
        .text-danger { color: red; }
        .text-warning { color: #fd7e14; }
        .text-primary { color: #1b55e2; }
        .badge-success { color: green; font-weight: bold; }
        .badge-warning { color: #fd7e14; font-weight: bold; }
        .badge-danger { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td class="business-info" width="60%">
                <h2>{{ $config->business_name }}</h2>
                <div style="font-size: 8pt;">
                    {{ $config->address }}<br>
                    TELÉFONOS: {{ $config->phone }}<br>
                    RIF: {{ $config->taxpayer_id }}
                </div>
            </td>
            <td class="report-info">
                Fecha Emisión: {{ $date }}<br>
                Generado por: {{ strtoupper($user->name ?? 'N/A') }}
            </td>
        </tr>
    </table>

    <div class="report-title">Auditoría de Pérdidas y Ganancias por Diferencial Cambiario</div>
    
    <div class="filter-info">
        <strong>Parámetros de Consulta:</strong><br>
        • Rango de Fechas (Pagos): {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Inicio' }} al {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'Fin' }}<br>
        • Moneda Filtrada: Bolívares (VES/VED) | Tipo de Cambio de Mercado Base: Binance Real
    </div>

    <!-- KPIs de Resumen -->
    <div class="section-title">Indicadores Consolidados del Periodo</div>
    <table class="kpi-table">
        <thead>
            <tr>
                <th>Ventas Facturadas (USD)</th>
                <th>Cobrado Teórico (USD)</th>
                <th>Cobrado Real (USD)</th>
                <th>Diferencial Neto (USD)</th>
                <th>Cojín de Diferencial (USD)</th>
                <th>Resultado Neto Cambiario (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">${{ number_format($kpis['totalInvoicedUSD'], 2) }}</td>
                <td class="font-bold text-primary">${{ number_format($kpis['totalCreditedUSD'], 2) }}</td>
                <td class="font-bold">${{ number_format($kpis['totalRealUSD'], 2) }}</td>
                <td class="font-bold @if($kpis['netExchangeDifferenceUSD'] < 0) text-danger @else text-success @endif">${{ number_format($kpis['netExchangeDifferenceUSD'], 2) }}</td>
                <td class="font-bold text-warning">${{ number_format($kpis['totalSurchargesBilledUSD'], 2) }}</td>
                <td class="font-bold @if($kpis['netCambiaryResultUSD'] < 0) text-danger @else text-success @endif">${{ number_format($kpis['netCambiaryResultUSD'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Tabla de Desglose de Pagos Auditados -->
    <div class="section-title">Desglose de Cobros y Auditoría de Tasa</div>
    <table class="table">
        <thead>
            <tr>
                <th>Factura</th>
                <th>Fecha Pago</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Acuerdo</th>
                <th>Monto VED</th>
                <th>Tasa Pago</th>
                <th>Tasa Binance</th>
                <th>USD Abonado</th>
                <th>USD Real</th>
                <th>Diferencial USD</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
                <tr>
                    <td class="text-center font-bold">
                        @if($p['invoice_number'])
                            F-{{ str_pad($p['invoice_number'], 6, '0', STR_PAD_LEFT) }}
                        @else
                            #{{ $p['sale_id'] }}
                        @endif
                    </td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($p['payment_date'])->format('d/m/Y') }}</td>
                    <td>{{ $p['customer_name'] }}</td>
                    <td>{{ $p['seller_name'] ?: 'OFICINA' }}</td>
                    <td class="text-center font-bold">{{ $p['agreement'] }}</td>
                    <td class="text-right font-bold" style="color: #666;">{{ number_format($p['amount'], 2) }} Bs.</td>
                    <td class="text-center text-primary font-bold">{{ number_format($p['pay_rate'], 2) }}</td>
                    <td class="text-center font-bold" style="color: #17a2b8;">{{ number_format($p['binance_rate'], 2) }}</td>
                    <td class="text-right font-bold">${{ number_format($p['usd_credited'], 2) }}</td>
                    <td class="text-right font-bold">${{ number_format($p['usd_real'], 2) }}</td>
                    <td class="text-right font-bold @if($p['diff'] < 0) text-danger @else text-success @endif">
                        ${{ number_format($p['diff'], 2) }}
                    </td>
                    <td class="text-center">
                        @if($p['status'] === 'green')
                            <span class="badge-success">Cumple</span>
                        @elseif($p['status'] === 'orange')
                            <span class="badge-warning">Desviación</span>
                        @else
                            <span class="badge-danger">Fuga</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center text-muted">No se registraron cobros en Bolívares que requieran auditoría en este período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Firmas -->
    <table class="footer-signatures" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">AUDITOR DE CAJA</div>
            </td>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">GERENCIA DE FINANZAS</div>
            </td>
            <td style="width: 34%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">DIRECCIÓN GENERAL</div>
            </td>
        </tr>
    </table>

</body>
</html>
