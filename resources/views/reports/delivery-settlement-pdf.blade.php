<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Liquidación de Ruta y Despacho</title>
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
            margin-bottom: 10px;
            text-decoration: underline;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th {
            background-color: #f2f2f2;
            border: 1px solid #999;
            padding: 3px;
            text-align: left;
            font-size: 7.5pt;
        }
        .table td {
            padding: 3px;
            border: 1px solid #ddd;
            font-size: 7.2pt;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        
        .driver-info-box {
            border: 1px solid #999;
            padding: 8px;
            background-color: #f9f9f9;
            margin-bottom: 10px;
        }

        .summary-container {
            width: 100%;
            margin-top: 15px;
        }
        .summary-box {
            width: 45%;
            float: left;
            border: 1px solid #999;
            padding: 5px;
        }
        .summary-title {
            background-color: #eee;
            font-weight: bold;
            text-align: center;
            padding: 2px;
            border-bottom: 1px solid #999;
            margin-bottom: 5px;
        }
        .clear { clear: both; }

        .footer-signatures {
            width: 100%;
            margin-top: 50px;
        }
        .signature-item {
            width: 33%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto 5px auto;
        }

        .badge {
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5pt;
            color: white;
            text-transform: uppercase;
        }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #000; }
        .bg-danger { background-color: #dc3545; }
        .bg-info { background-color: #17a2b8; }
        .bg-secondary { background-color: #6c757d; }
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
                Fecha emisión: {{ \Carbon\Carbon::now()->format('d/m/Y h:i a') }}<br>
                Generado por: {{ strtoupper($user->name) }}
            </td>
        </tr>
    </table>

    <div class="report-title">HOJA DE LIQUIDACIÓN Y CIERRE DE RUTA (INFORMATIVA)</div>
    
    <div class="driver-info-box">
        <table width="100%">
            <tr>
                <td width="50%"><strong>CHOFER:</strong> {{ strtoupper($sales->first()->driver->name ?? 'N/A') }}</td>
                <td width="50%" class="text-right"><strong>PERIODO:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} @if($dateFrom != $dateTo) a {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }} @endif</td>
            </tr>
        </table>
    </div>

    @php
        $totalsByCurrency = [];
        $driverGroups = $sales->groupBy('driver_id');
    @endphp

    @foreach($driverGroups as $driverId => $groupSales)
        @if($driverGroups->count() > 1)
            <div style="background-color: #eee; padding: 4px; border: 1px solid #999; margin-bottom: 5px; font-weight: bold;">
                CHOFER: {{ strtoupper($groupSales->first()->driver->name ?? 'N/A') }}
            </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th width="50">Factura</th>
                    <th width="150">Cliente</th>
                    <th width="65" class="text-right">Total Fact.</th>
                    <th width="100">Cobros Declarados</th>
                    <th width="60" class="text-center">Estado Ruta</th>
                    <th>Novedades / Observaciones del Chofer</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupSales as $sale)
                    @php
                        $collections = $sale->deliveryCollections;
                        $statusClass = '';
                        switch($sale->delivery_status) {
                            case 'delivered': $statusClass = 'bg-success'; break;
                            case 'in_transit': $statusClass = 'bg-info'; break;
                            case 'pending': $statusClass = 'bg-warning'; break;
                            case 'cancelled': $statusClass = 'bg-danger'; break;
                            default: $statusClass = 'bg-secondary'; break;
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $sale->invoice_number ?? $sale->id }}</td>
                        <td>{{ strtoupper($sale->customer->name) }}</td>
                        <td class="text-right fw-bold">${{ number_format($sale->total_usd, 2) }}</td>
                        <td>
                            @forelse($collections as $col)
                                @foreach($col->payments as $pay)
                                    <div style="font-size: 6.5pt; border-bottom: 1px dotted #ccc; margin-bottom: 1px;">
                                        <strong>{{ $pay->currency->code }}:</strong> {{ number_format($pay->amount, 2) }}
                                    </div>
                                    @php
                                        $code = $pay->currency->code;
                                        $totalsByCurrency[$code] = ($totalsByCurrency[$code] ?? 0) + $pay->amount;
                                    @endphp
                                @endforeach
                            @empty
                                <span style="color: #999;">Sin cobros informados</span>
                            @endforelse
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $statusClass }}">
                                {{ $sale->delivery_status == 'delivered' ? 'Entregado' : ($sale->delivery_status == 'in_transit' ? 'En Ruta' : ($sale->delivery_status == 'pending' ? 'Pendiente' : 'Cancelado')) }}
                            </span>
                        </td>
                        <td>
                            @foreach($collections as $col)
                                @if($col->note)
                                    <div style="margin-bottom: 3px; border-bottom: 1px solid #eee; padding-bottom: 2px;">
                                        - {{ $col->note }}
                                    </div>
                                @endif
                            @endforeach
                            @if($collections->whereNotNull('note')->count() == 0)
                                <em style="color: #999;">Sin notas</em>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="summary-container">
        <div class="summary-box">
            <div class="summary-title">TOTALES POR MONEDA (RECAUDACIÓN DECLARADA)</div>
            <table width="100%">
                @forelse($totalsByCurrency as $code => $total)
                    <tr>
                        <td class="fw-bold" style="padding: 2px 5px;">{{ $code }} Total:</td>
                        <td class="text-right" style="padding: 2px 5px;">{{ number_format($total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center" style="padding: 10px; color: #999;">No se reportaron pagos en esta ruta</td>
                    </tr>
                @endforelse
            </table>
        </div>
        
        <div class="summary-box" style="margin-left: 5%; border: none;">
             <p style="font-size: 7.5pt; font-style: italic; color: #666; margin-top: 0;">
                <strong>Aviso:</strong> Este reporte es de carácter informativo basado en la declaración del chofer. Los montos aquí expresados deben ser validados físicamente y procesados en el sistema de caja para que afecten el stock y las cuentas de los clientes.
             </p>
        </div>
    </div>

    <div class="clear"></div>

    <table class="footer-signatures">
        <tr>
            <td class="signature-item">
                <div class="signature-line"></div>
                <strong>ENTREGADO POR (CHOFER)</strong><br>
                Firma y C.I.
            </td>
            <td class="signature-item">
                <div class="signature-line"></div>
                <strong>RECIBIDO POR (CAJA)</strong><br>
                Validación de Efectivo
            </td>
            <td class="signature-item">
                <div class="signature-line"></div>
                <strong>AUDITORÍA (ALMACÉN)</strong><br>
                Validación de Devoluciones
            </td>
        </tr>
    </table>

</body>
</html>
