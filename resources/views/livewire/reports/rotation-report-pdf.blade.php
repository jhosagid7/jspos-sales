<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Rotación</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: center; }
        th { background-color: #f2f2f2; }
        .text-left { text-align: left; }
        .header { text-align: center; margin-bottom: 20px; }
        .badge { padding: 2px 5px; border-radius: 3px; color: white; }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: black; }
        .badge-danger { background-color: #dc3545; }
        .badge-info { background-color: #17a2b8; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Análisis de Rotación</h2>
        <p>Desde: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - Hasta: {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
        <p>Proyección para: {{ $coverageDays }} días</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
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
                    <td>{{ $product->suggested_order }}</td>
                    <td>
                        @if($product->coverage_days > 365)
                            > 1 Año
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
</body>
</html>
