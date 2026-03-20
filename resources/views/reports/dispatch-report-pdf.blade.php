<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Relación de Despacho</title>
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
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 11pt;
        }
        .report-info {
            text-align: right;
            font-size: 8pt;
        }
        .report-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 15px;
            text-decoration: underline;
        }
        
        /* Table Style matching Daily Sales */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f2f2f2;
            border: 1px solid #999;
            padding: 2px 3px;
            text-align: left;
            font-size: 7.5pt;
            white-space: nowrap;
        }
        .table td {
            padding: 2px 3px;
            border-bottom: 1px solid #ddd;
            font-size: 7.2pt;
            vertical-align: middle;
            height: 14px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .driver-header {
            background-color: #e9e9e9;
            font-weight: bold;
            font-size: 9pt;
            padding: 4px;
            border: 1px solid #999;
            margin-top: 10px;
        }
        .seller-header {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 3px;
            border: 1px solid #ccc;
        }
        
        .summary-box {
            width: 35%;
            margin-left: auto;
            border-top: 1.5pt solid #000;
            margin-top: 10px;
            padding-top: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-label {
            font-weight: bold;
            font-size: 7.5pt;
        }
        .summary-value {
            text-align: right;
            font-size: 7.5pt;
        }

        .footer-signatures {
            width: 100%;
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto 5px auto;
        }
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
                Fecha : {{ \Carbon\Carbon::now()->format('d/m/Y') }}<br>
                Hora : {{ \Carbon\Carbon::now()->format('h:i:s a') }}<br>
                Pág : 1
            </td>
        </tr>
    </table>

    <div class="report-title">RELACIÓN DE DESPACHO</div>
    
    <div style="margin-bottom: 10px; font-size: 8pt;">
        Periodo : {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}<br>
        Moneda de Referencia : Dólares<br>
        Generado por : {{ strtoupper($user->name ?? 'N/A') }}
    </div>

    @foreach ($data as $driverId => $driverGroup)
        <div class="driver-header">
            CHOFER: {{ $driverGroup['name'] }}
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 70px;">Factura</th>
                    <th style="width: 100px;">Entrega</th>
                    <th>Cliente</th>
                    <th style="width: 80px;" class="text-right">Importe Base</th>
                    <th style="width: 60px;" class="text-center">% Aplicado</th>
                    <th style="width: 80px;" class="text-right">Monto Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($driverGroup['sellers'] as $sellerId => $sellerGroup)
                    <tr>
                        <td colspan="6" class="seller-header">
                            VENDEDOR: {{ $sellerGroup['name'] }}
                        </td>
                    </tr>
                    @foreach ($sellerGroup['sales'] as $sale)
                        <tr>
                            <td>{{ $sale->invoice_number }}</td>
                            <td>{{ strtoupper($sale->destination) }}</td>
                            <td>{{ strtoupper($sale->customer_name) }}</td>
                            <td class="text-right">{{ number_format($sale->base, 2) }}</td>
                            <td class="text-center">{{ number_format($sale->inc_percent, 2) }}%</td>
                            <td class="text-right">{{ number_format($sale->total, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="background-color: #fafafa; font-weight: bold;">
                        <td colspan="3" class="text-right">Subtotal Vendedor:</td>
                        <td class="text-right">{{ number_format($sellerGroup['total_base'], 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($sellerGroup['total_final'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold; border-top: 1.5pt solid #999;">
                    <td colspan="3" class="text-right">TOTAL {{ $driverGroup['name'] }}:</td>
                    <td class="text-right">{{ number_format($driverGroup['total_base'], 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($driverGroup['total_final'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endforeach

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="summary-label">TOTAL BASE:</td>
                <td class="summary-value">{{ number_format($overall['base'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">TOTAL FLETE:</td>
                <td class="summary-value">{{ number_format($overall['freight'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">TOTAL COMISIÓN:</td>
                <td class="summary-value">{{ number_format($overall['commission'], 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">TOTAL DIFERENCIA:</td>
                <td class="summary-value">{{ number_format($overall['diff'], 2) }}</td>
            </tr>
            <tr style="border-top: 1pt solid #000;">
                <td class="summary-label" style="font-size: 8.5pt;">TOTAL GENERAL:</td>
                <td class="summary-value" style="font-size: 8.5pt;">{{ number_format($overall['total'], 2) }}</td>
            </tr>
        </table>
    </div>

    <table class="footer-signatures">
        <tr>
            <td style="width: 33%; text-align: center;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">ENTREGADO POR</div>
                <div style="font-size: 7pt;">(DESPACHO)</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">CHOFER</div>
                <div style="font-size: 7pt;">(FIRMA)</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">RECIBIDO POR</div>
                <div style="font-size: 7pt;">(ALMACÉN / CAJA)</div>
            </td>
        </tr>
    </table>

</body>
</html>
