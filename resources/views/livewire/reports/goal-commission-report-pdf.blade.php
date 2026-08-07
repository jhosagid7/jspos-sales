<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Comisiones por Metas</title>
    <style>
        @page {
            margin: 0.8cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 5px;
        }
        .header td {
            vertical-align: middle;
        }
        .logo {
            max-height: 50px;
            max-width: 150px;
        }
        .business-info h2 {
            margin: 0;
            font-size: 12pt;
            color: #2c3e50;
            text-transform: uppercase;
        }
        .business-info p {
            margin: 1px 0;
            font-size: 7.5pt;
            color: #555;
        }
        .report-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 10px;
            text-transform: uppercase;
            color: #2c3e50;
            background-color: #eaedd0;
            padding: 6px;
            border-radius: 4px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .kpi-table th {
            background-color: #1a252f;
            color: white;
            padding: 5px;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .kpi-table td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
            background-color: #f8f9fa;
        }
        .seller-header {
            background-color: #2c3e50;
            color: #ffffff;
            padding: 5px 8px;
            font-size: 9pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 0;
            border-radius: 3px 3px 0 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th {
            background-color: #34495e;
            color: white;
            border: 1px solid #34495e;
            padding: 5px;
            text-align: center;
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table td {
            padding: 5px;
            border: 1px solid #dee2e6;
            font-size: 7.5pt;
            vertical-align: middle;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .badge-achieved {
            background-color: #27ae60;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7pt;
        }
        .badge-pending {
            background-color: #7f8c8d;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7pt;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 7pt;
            text-align: center;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Header Corporativo -->
    <table class="header">
        <tr>
            <td style="width: 60%;" class="business-info">
                <h2>{{ $config->business_name ?? 'JSPOS SALES' }}</h2>
                <p><strong>RUC/NIT:</strong> {{ $config->tax_id ?? 'N/A' }} | <strong>Teléfono:</strong> {{ $config->phone ?? 'N/A' }}</p>
                <p><strong>Dirección:</strong> {{ $config->address ?? 'N/A' }}</p>
            </td>
            <td style="width: 40%; text-align: right;" class="business-info">
                <p><strong>Fecha de Emisión:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}</p>
                <p><strong>Generado Por:</strong> {{ $user->name ?? 'Sistema' }}</p>
                <p><strong>Fecha Evaluada:</strong> {{ \Carbon\Carbon::parse($referenceDate)->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>

    <!-- Título del Reporte -->
    <div class="report-title">
        Reporte de Comisiones por Metas de Ventas
    </div>

    <!-- Métricas KPI -->
    <table class="kpi-table">
        <thead>
            <tr>
                <th style="width: 33%;">Metas Evaluadas</th>
                <th style="width: 33%;">Metas Alcanzadas</th>
                <th style="width: 34%;">Total Comisiones / Premios ($)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $totalGoalsEvaluated }}</td>
                <td style="color: #27ae60;">{{ $totalGoalsAchieved }}</td>
                <td style="color: #d35400;">$ {{ number_format($totalCommissionEarned, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Detalle por Vendedor -->
    @foreach($evaluations as $eval)
        <div class="seller-header">
            VENDEDOR: {{ mb_strtoupper($eval['user_name']) }} 
            <span style="float: right;">TOTAL PREMIOS: $ {{ number_format($eval['total_earned'], 2) }}</span>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 20%;">Meta</th>
                    <th style="width: 12%;">Frecuencia</th>
                    <th style="width: 22%;">Período Evaluado</th>
                    <th style="width: 11%;" class="text-right">Meta ($)</th>
                    <th style="width: 11%;" class="text-right">Ventas ($)</th>
                    <th style="width: 12%;" class="text-center">Estatus</th>
                    <th style="width: 12%;" class="text-right">Premio ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($eval['goals'] as $g)
                    <tr>
                        <td class="font-bold">{{ $g['goal_name'] }}</td>
                        <td class="text-center" style="text-transform: uppercase;">{{ $g['periodicity'] }}</td>
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($g['period_start'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($g['period_end'])->format('d/m/Y') }}
                        </td>
                        <td class="text-right font-bold">$ {{ number_format($g['target_amount'], 2) }}</td>
                        <td class="text-right font-bold" style="color: #2980b9;">$ {{ number_format($g['total_sales'], 2) }}</td>
                        <td class="text-center">
                            @if($g['achieved'])
                                <span class="badge-achieved">ALCANZADA</span>
                            @else
                                <span class="badge-pending">EN PROGRESO</span>
                            @endif
                        </td>
                        <td class="text-right font-bold" style="color: {{ $g['achieved'] ? '#27ae60' : '#7f8c8d' }};">
                            $ {{ number_format($g['earned_reward'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        Documento generado automáticamente por JSPOS Sales - Sistema de Gestión de Ventas y Comisiones
    </div>

</body>
</html>
