<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Recuperación de Clientes</title>
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
            color: #c0392b;
        }
        .filter-info {
            margin-bottom: 10px;
            font-size: 8pt;
            background-color: #fce8e6;
            padding: 6px;
            border: 1px solid #f5c6cb;
            border-radius: 3px;
            color: #721c24;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #c0392b;
            color: white;
            border: 1px solid #a93226;
            padding: 5px;
            text-align: center;
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            font-size: 7.2pt;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        .group-header {
            background-color: #f8d7da;
            font-weight: bold;
            padding: 5px;
            border: 1px solid #f5c6cb;
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 8.5pt;
            color: #721c24;
        }

        .risk-badge {
            display: block;
            text-align: center;
            padding: 2px;
            font-weight: bold;
            font-size: 7pt;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .risk-critical { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .risk-high { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .risk-medium { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .risk-low { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .line-write {
            display: inline-block;
            border-bottom: 0.5pt solid #666;
            width: 80px;
            height: 8px;
            vertical-align: bottom;
        }
        .line-write-long {
            border-bottom: 0.5pt dashed #999;
            height: 12px;
            margin-bottom: 4px;
        }

        .summary-box {
            width: 45%;
            margin-left: auto;
            border-top: 1.5pt solid #000;
            margin-top: 10px;
            padding-top: 5px;
            margin-bottom: 20px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-label {
            font-weight: bold;
            font-size: 8pt;
        }
        .summary-value {
            text-align: right;
            font-size: 8pt;
            font-weight: bold;
        }

        .footer-signatures {
            width: 100%;
            margin-top: 40px;
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
                Fecha Emisión: {{ $date }}<br>
                Generado por: {{ strtoupper($user->name ?? 'N/A') }}
            </td>
        </tr>
    </table>

    <div class="report-title">Reporte de Recuperación de Clientes Inactivos</div>
    
    <div class="filter-info">
        <strong>Campaña de Reactivación Comercial:</strong> El listado se encuentra ordenado de mayor a menor según el <strong>Volumen Histórico de Compra (USD)</strong>. Llame con prioridad a los clientes de alto valor ubicados en el tope del listado.
    </div>

    @php
        $totalCustomersCount = 0;
    @endphp

    @foreach ($customersData as $groupName => $items)
        @php
            $totalCustomersCount += $items->count();
        @endphp
        @if($isGrouped)
            <div class="group-header">
                VENDEDOR: {{ $groupName ?: 'SIN VENDEDOR' }} (Clientes: {{ $items->count() }})
            </div>
        @endif
        @php
            $showRiskCol = !isset($columns) || $columns['risk_level'];
            $showHistoryCol = !isset($columns) || $columns['last_purchase'] || $columns['total_purchased'];
            
            // Calculate widths
            if ($showRiskCol && $showHistoryCol) {
                $clientColWidth = '30%';
                $historyColWidth = '20%';
                $riskColWidth = '15%';
                $registryColWidth = '35%';
            } elseif ($showRiskCol && !$showHistoryCol) {
                $clientColWidth = '35%';
                $riskColWidth = '20%';
                $registryColWidth = '45%';
            } elseif (!$showRiskCol && $showHistoryCol) {
                $clientColWidth = '35%';
                $historyColWidth = '25%';
                $registryColWidth = '40%';
            } else {
                $clientColWidth = '45%';
                $registryColWidth = '55%';
            }
        @endphp

        <table class="table">
            <thead>
                <tr>
                    <th style="width: {{ $clientColWidth }};">Cliente y Contacto</th>
                    @if($showHistoryCol)
                        <th style="width: {{ $historyColWidth }};">Historial de Compra</th>
                    @endif
                    @if($showRiskCol)
                        <th style="width: {{ $riskColWidth }};">Nivel de Riesgo</th>
                    @endif
                    <th style="width: {{ $registryColWidth }};">Registro de Contacto / Televenta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $customer)
                    @php
                        $days = null;
                        if ($customer->last_purchase_at) {
                            $days = \Carbon\Carbon::parse($customer->last_purchase_at)->diffInDays(\Carbon\Carbon::now());
                        }
                    @endphp
                    <tr @if($customer->deleted_at) style="background-color: #fce8e6;" @endif>
                        <!-- Cliente y Contacto -->
                        <td>
                            @if(!isset($columns) || $columns['name'])
                                <strong style="font-size: 8.5pt; color: #2c3e50;">{{ $customer->name }}</strong><br>
                            @endif
                            @if(!isset($columns) || $columns['taxpayer_id'])
                                <span style="color: #555;">RIF/Cédula: {{ $customer->taxpayer_id ?: 'N/A' }}</span><br>
                            @endif
                            @if(!isset($columns) || $columns['phone'])
                                <strong>Telf:</strong> {{ $customer->phone ?: 'N/A' }}<br>
                            @endif
                            @if(!isset($columns) || $columns['seller'])
                                <strong>Vendedor:</strong> {{ $customer->seller->name ?? 'N/A' }}
                            @endif
                        </td>
                        <!-- Historial de Compra -->
                        @if($showHistoryCol)
                            <td>
                                @if(!isset($columns) || $columns['last_purchase'])
                                    <strong>Última Compra:</strong><br>
                                    {{ $customer->last_purchase_at ? \Carbon\Carbon::parse($customer->last_purchase_at)->format('d/m/Y') : 'Nunca ha comprado' }}<br>
                                    @if($days !== null)
                                        <strong style="color: #c0392b;">({{ $days }} días inactivo)</strong><br>
                                    @endif
                                @endif
                                @if(!isset($columns) || $columns['total_purchased'])
                                    <strong style="color: #27ae60;">Histórico: ${{ number_format($customer->total_purchased_usd ?? 0, 2) }}</strong>
                                @endif
                            </td>
                        @endif
                        <!-- Nivel de Riesgo -->
                        @if($showRiskCol)
                            <td style="vertical-align: middle;">
                                @if($days === null)
                                    <span class="risk-badge risk-critical">Sin Compras</span>
                                @elseif($days >= 120)
                                    <span class="risk-badge risk-critical">Perdido (&gt;120d)</span>
                                @elseif($days >= 90)
                                    <span class="risk-badge risk-critical">Crítico (&gt;90d)</span>
                                @elseif($days >= 60)
                                    <span class="risk-badge risk-high">Alto (&gt;60d)</span>
                                @elseif($days >= 30)
                                    <span class="risk-badge risk-medium">Medio (&gt;30d)</span>
                                @else
                                    <span class="risk-badge risk-low">Bajo (&lt;30d)</span>
                                @endif
                            </td>
                        @endif
                        <!-- Registro de Contacto -->
                        <td>
                            <strong>Fecha Llamada:</strong> <span class="line-write"></span><br>
                            <strong>Resultado/Compromiso:</strong><br>
                            <div class="line-write-long" style="margin-top: 5px;"></div>
                            <div class="line-write-long"></div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No se encontraron clientes inactivos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="summary-label">TOTAL CLIENTES INACTIVOS PARA RECUPERACIÓN:</td>
                <td class="summary-value">{{ number_format($totalCustomersCount, 0) }}</td>
            </tr>
        </table>
    </div>

    <table class="footer-signatures" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">OPERADOR TELEVENTAS</div>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">SUPERVISOR DE VENTAS</div>
            </td>
        </tr>
    </table>

</body>
</html>
