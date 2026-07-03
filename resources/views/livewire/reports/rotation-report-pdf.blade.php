<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Rotación e Inversión de Inventario</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 9px;
            margin: 20px;
            color: #333;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 5px; text-align: center; border: 1px solid #e0e0e0; vertical-align: middle; }
        th { background-color: #3b3f5c; font-weight: bold; color: white; font-size: 9px; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .header-table { margin-bottom: 15px; border: none; }
        .header-table td { border: none; padding: 0; }
        
        .invoice-title { color: #1b55e2; font-weight: bold; font-size: 16px; margin: 0; }
        .report-title { color: #1b55e2; font-size: 13px; font-weight: bold; margin: 0; }
        
        .box-details {
            border: 1px solid #d3d3d3;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 15px;
            background-color: #fcfcfc;
        }
        
        /* Grid de KPIs */
        .kpi-container {
            width: 100%;
            margin-bottom: 15px;
            border: none;
        }
        .kpi-container td {
            border: none;
            padding: 0 5px;
        }
        .kpi-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 8px;
            text-align: left;
            background-color: #fff;
        }
        .kpi-title {
            font-size: 8px;
            text-transform: uppercase;
            color: #777;
            font-weight: bold;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: bold;
            margin-top: 3px;
        }
        
        /* Badges */
        .badge { padding: 2px 5px; border-radius: 4px; color: white; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .badge-success { background-color: #2ec4b6; }
        .badge-warning { background-color: #ff9f1c; }
        .badge-danger { background-color: #e71d36; }
        .badge-info { background-color: #17a2b8; }
        .badge-secondary { background-color: #6c757d; }

        .text-success { color: #2ec4b6; font-weight: bold; }
        .text-danger { color: #e71d36; font-weight: bold; }
        .text-primary { color: #1b55e2; font-weight: bold; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table class="header-table">
        <tbody>
            <tr>
                <td class="text-left" width="30%" style="vertical-align: middle;">
                   @if(isset($config) && $config->logo)
                        <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="40">
                    @endif
                </td>
                <td class="text-center" width="40%" style="vertical-align: middle;">
                    <h4 class="text-uppercase invoice-title">
                        {{ isset($config) ? $config->business_name : 'JSPOS' }}
                    </h4>
                </td>
                <td class="text-right" width="30%" style="vertical-align: middle;">
                    <h4 class="text-uppercase report-title">
                        MATRIZ DE ROTACIÓN Y RENTABILIDAD
                    </h4>
                    <span style="font-size: 8px; font-weight: bold; color: #777;">REPORTE DE INVENTARIO</span>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Info Box --}}
    <div class="box-details">
        <table class="header-table" style="margin: 0;">
            <tbody>
                <tr>
                    {{-- Business Info --}}
                    <td class="text-left" width="60%" style="vertical-align: top; font-size: 8px; line-height: 1.2;">
                        @if(isset($config))
                            <strong>{{ $config->business_name }}</strong> | NIT: {{ $config->taxpayer_id }}<br>
                            Dirección: {{ $config->address }}<br>
                            Contacto: {{ $config->phone }} | {{ $config->email }}
                        @endif
                    </td>

                    {{-- Report Details --}}
                    <td class="text-right" width="40%" style="vertical-align: top; font-size: 8px; line-height: 1.2;">
                        Fecha Reporte: <strong>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</strong><br>
                        Generado por: <strong>{{ auth()->user()->name ?? 'Sistema' }}</strong><br>
                        Rango: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</strong> | Proyección: <strong>{{ $coverageDays }} días</strong><br>
                        Etiqueta: <strong>{{ $tagName }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Grid de KPIs --}}
    @if(isset($selectedKpis) && count($selectedKpis) > 0)
        @php
            $kpiWidth = (100 / count($selectedKpis)) . '%';
        @endphp
        <table class="kpi-container">
            <tr>
                @if(in_array('totalCapital', $selectedKpis))
                    <td width="{{ $kpiWidth }}">
                        <div class="kpi-card" style="border-left: 3px solid #1b55e2;">
                            <div class="kpi-title">Capital en Inventario</div>
                            <div class="kpi-value" style="color: #333;">${{ number_format($totalCapital, 2) }}</div>
                        </div>
                    </td>
                @endif
                @if(in_array('idleCapital', $selectedKpis))
                    <td width="{{ $kpiWidth }}">
                        <div class="kpi-card" style="border-left: 3px solid #e71d36;">
                            <div class="kpi-title">Capital Ocioso (Sin Mov)</div>
                            <div class="kpi-value" style="color: #e71d36;">${{ number_format($idleCapital, 2) }}</div>
                        </div>
                    </td>
                @endif
                @if(in_array('totalMargin', $selectedKpis))
                    <td width="{{ $kpiWidth }}">
                        <div class="kpi-card" style="border-left: 3px solid #2ec4b6;">
                            <div class="kpi-title">Ganancia Bruta Ventas</div>
                            <div class="kpi-value" style="color: #2ec4b6;">${{ number_format($totalMargin, 2) }}</div>
                        </div>
                    </td>
                @endif
                @if(in_array('avgMarginPercent', $selectedKpis))
                    <td width="{{ $kpiWidth }}">
                        <div class="kpi-card" style="border-left: 3px solid #17a2b8;">
                            <div class="kpi-title">Margen Promedio (%)</div>
                            <div class="kpi-value" style="color: #17a2b8;">{{ number_format($avgMarginPercent, 2) }}%</div>
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    {{-- Main Table --}}
    <table>
        <thead>
            <tr>
                @if(in_array('product', $selectedPdfColumns)) <th class="text-left" style="width: 180px;">Producto</th> @endif
                @if(in_array('abc_class', $selectedPdfColumns)) <th style="width: 40px;">Clase</th> @endif
                @if(in_array('stock_qty', $selectedPdfColumns)) <th style="width: 50px;">Stock</th> @endif
                @if(in_array('stock_value', $selectedPdfColumns)) <th style="width: 65px;">Valor Stock</th> @endif
                @if(in_array('total_sold', $selectedPdfColumns)) <th style="width: 50px;">Vendido</th> @endif
                @if(in_array('sales_usd', $selectedPdfColumns)) <th style="width: 65px;">Ventas USD</th> @endif
                @if(in_array('margin_usd', $selectedPdfColumns)) <th style="width: 65px;">Margen USD</th> @endif
                @if(in_array('margin_percent', $selectedPdfColumns)) <th style="width: 50px;">Margen %</th> @endif
                @if(in_array('velocity', $selectedPdfColumns)) <th style="width: 50px;">Velocidad</th> @endif
                @if(in_array('suggested_order', $selectedPdfColumns)) <th style="width: 60px;">Sugerencia</th> @endif
                @if(in_array('coverage_days', $selectedPdfColumns)) <th style="width: 55px;">Cobertura</th> @endif
                @if(in_array('rotation_status', $selectedPdfColumns)) <th style="width: 70px;">Estado</th> @endif
            </tr>
        </thead>
        <tbody>
            @foreach($data as $product)
                <tr>
                    @if(in_array('product', $selectedPdfColumns)) <td class="text-left" style="font-weight: bold; color: #111;">{{ $product->name }}</td> @endif
                    @if(in_array('abc_class', $selectedPdfColumns))
                        <td>
                            @if($product->abc_class === 'A')
                                <span class="badge badge-success" style="background-color: #2ec4b6;">A</span>
                            @elseif($product->abc_class === 'B')
                                <span class="badge badge-warning" style="background-color: #ff9f1c; color: white;">B</span>
                            @else
                                <span class="badge badge-danger" style="background-color: #e71d36;">C</span>
                            @endif
                        </td>
                    @endif
                    @if(in_array('stock_qty', $selectedPdfColumns)) <td style="font-weight: bold;">{{ $product->stock_qty }}</td> @endif
                    @if(in_array('stock_value', $selectedPdfColumns)) <td style="color: #666;">${{ number_format($product->stock_value, 2) }}</td> @endif
                    @if(in_array('total_sold', $selectedPdfColumns)) <td style="font-weight: bold;">{{ $product->total_sold }}</td> @endif
                    @if(in_array('sales_usd', $selectedPdfColumns)) <td>${{ number_format($product->sales_usd, 2) }}</td> @endif
                    @if(in_array('margin_usd', $selectedPdfColumns))
                        <td>
                            @if($product->margin_usd > 0)
                                <span class="text-success">+${{ number_format($product->margin_usd, 2) }}</span>
                            @elseif($product->margin_usd < 0)
                                <span class="text-danger">-${{ number_format(abs($product->margin_usd), 2) }}</span>
                            @else
                                $0.00
                            @endif
                        </td>
                    @endif
                    @if(in_array('margin_percent', $selectedPdfColumns))
                        <td style="font-weight: bold;">
                            @if($product->margin_percent > 0)
                                <span class="text-success">{{ $product->margin_percent }}%</span>
                            @elseif($product->margin_percent < 0)
                                <span class="text-danger">{{ $product->margin_percent }}%</span>
                            @else
                                0%
                            @endif
                        </td>
                    @endif
                    @if(in_array('velocity', $selectedPdfColumns)) <td style="color: #666;">{{ $product->velocity }}</td> @endif
                    @if(in_array('suggested_order', $selectedPdfColumns)) <td class="text-primary" style="font-weight: bold;">{{ $product->suggested_order }}</td> @endif
                    @if(in_array('coverage_days', $selectedPdfColumns))
                        <td>
                            @if($product->coverage_days > 365)
                                <span>&gt; 1 Año</span>
                            @else
                                {{ $product->coverage_days }} días
                            @endif
                        </td>
                    @endif
                    @if(in_array('rotation_status', $selectedPdfColumns))
                        <td>
                            @if($product->rotation_status == 'Alta Rotacion')
                                <span class="badge badge-success" style="background-color: #2ec4b6;">Alta</span>
                            @elseif($product->rotation_status == 'Baja Rotacion')
                                <span class="badge badge-warning" style="background-color: #ff9f1c; color: white;">Baja</span>
                            @else
                                <span class="badge badge-danger" style="background-color: #e71d36;">Sin Mov.</span>
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 20px; text-align: center; font-size: 8px; color: #888; border-top: 1px solid #eee; padding-top: 10px;">
        <p>Este reporte fue generado de forma automatizada por el Centro de Reportes Avanzados de JSPOS.</p>
    </div>
</body>
</html>
