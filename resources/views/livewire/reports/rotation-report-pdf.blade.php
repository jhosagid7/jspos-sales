<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Rotación</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            font-size: 10px;
            margin: 36pt;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 5px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; color: #333; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .header-table { margin-bottom: 20px; border: none; }
        .header-table td { border: none; }
        .badge { padding: 3px 6px; border-radius: 4px; color: white; font-weight: bold; font-size: 9px; }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: black; }
        .badge-danger { background-color: #dc3545; }
        .badge-info { background-color: #17a2b8; }
        
        .invoice-title { color: #0380b2; font-weight: bold; font-size: 18px; margin: 0; }
        .report-title { color: #0380b2; font-size: 14px; font-weight: bold; margin: 0; }
        
        .box-details {
            border: 1px solid #6B7280;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .text-green { color: #28a745; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table class="header-table">
        <tbody>
            <tr>
                <td class="text-left" width="25%" style="vertical-align: middle;">
                   @if(isset($config) && $config->logo)
                        <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="50">
                    @endif
                </td>
                <td class="text-center" width="50%" style="vertical-align: middle;">
                    <h4 class="text-uppercase invoice-title">
                        {{ isset($config) ? $config->business_name : 'JSPOS' }}
                    </h4>
                </td>
                <td class="text-right" width="25%" style="vertical-align: middle;">
                    <h4 class="text-uppercase report-title">
                        ANÁLISIS DE ROTACIÓN
                    </h4>
                    <span style="font-size: 10px; font-weight: bold;">REPORTE</span>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Info Box --}}
    <div class="box-details">
        <table class="header-table" style="margin: 0;">
            <tbody>
                <tr>
                    {{-- Business Info (Left) --}}
                    <td class="text-left" width="60%" style="vertical-align: top; padding: 0;">
                        @if(isset($config))
                            <strong class="text-uppercase" style="font-size: 12px;">{{ $config->business_name }}</strong><br>
                            NIT: {{ $config->taxpayer_id }}<br>
                            {{ $config->address }}<br>
                            {{ $config->email }}<br>
                            {{ $config->phone }}
                        @endif
                    </td>

                    {{-- Report Details (Right) --}}
                    <td class="text-right" width="40%" style="vertical-align: top; padding: 0;">
                        Fecha Reporte: <strong>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</strong><br>
                        Generado por: <strong>{{ auth()->user()->name ?? 'Sistema' }}</strong><br>
                        Rango: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</strong><br>
                        Proyección: <strong>{{ $coverageDays }} días</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">Producto</th>
                <th>Stock</th>
                <th>Vendido</th>
                <th>Velocidad (u/día)</th>
                <th>Demanda ({{ $coverageDays }}d)</th>
                <th>Sugerencia</th>
                <th>Cobertura</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $product)
                <tr>
                    <td class="text-left">{{ $product->name }}</td>
                    <td>{{ $product->stock_qty }}</td>
                    <td>{{ $product->total_sold }}</td>
                    <td>{{ $product->velocity }}</td>
                    <td>{{ $product->monthly_demand }}</td>
                    <td>
                        @if($product->suggested_order > 0)
                            <span style="color: #0380b2; font-weight: bold;">{{ $product->suggested_order }}</span>
                        @else
                            {{ $product->suggested_order }}
                        @endif
                    </td>
                    <td>
                        @if($product->coverage_days > 365)
                            <span class="badge badge-info">> 1 Año</span>
                        @else
                            {{ $product->coverage_days }} días
                        @endif
                    </td>
                    <td>
                        @if($product->rotation_status == 'Alta Rotacion')
                            <span class="badge badge-success">Alta</span>
                        @elseif($product->rotation_status == 'Baja Rotacion')
                            <span class="badge badge-warning">Baja</span>
                        @else
                            <span class="badge badge-danger">Sin Mov.</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #666;">
        <p>Este reporte fue generado automáticamente por el sistema.</p>
    </div>
</body>
</html>
