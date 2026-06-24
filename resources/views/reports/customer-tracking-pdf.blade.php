<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla de Seguimiento Comercial</title>
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
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #343a40;
            color: white;
            border: 1px solid #454d55;
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
            background-color: #e9ecef;
            font-weight: bold;
            padding: 5px;
            border: 1px solid #ccc;
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 8.5pt;
            color: #2c3e50;
        }

        .checkbox-box {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #333;
            margin-right: 4px;
            vertical-align: middle;
        }
        .line-write {
            display: inline-block;
            border-bottom: 0.5pt solid #666;
            width: 70px;
            height: 8px;
            vertical-align: bottom;
        }
        .line-write-long {
            display: inline-block;
            border-bottom: 0.5pt solid #666;
            width: 140px;
            height: 8px;
            vertical-align: bottom;
        }
        .line-write-obs {
            border-bottom: 0.5pt dashed #999;
            height: 12px;
            margin-bottom: 4px;
        }

        .summary-box {
            width: 40%;
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

    <div class="report-title">Planilla de Seguimiento y Visitas Comerciales</div>
    
    <div class="filter-info">
        <strong>Instrucciones para el Vendedor:</strong> Rellene físicamente los datos de cada visita en campo. Anote montos cobrados o pedidos agendados y justifique los motivos en caso de no compra.
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
            $showFinancialCol = !isset($columns) || $columns['wallet_balance'] || $columns['allow_credit'] || $columns['credit_limit'] || $columns['credit_days'] || $columns['zone'];
            $clientColWidth = $showFinancialCol ? '35%' : '45%';
            $visitColWidth = $showFinancialCol ? '30%' : '35%';
            $obsColWidth = '20%';
        @endphp

        <table class="table">
            <thead>
                <tr>
                    <th style="width: {{ $clientColWidth }};">Cliente y Datos de Contacto</th>
                    @if($showFinancialCol)
                        <th style="width: 15%;">Estatus Financiero</th>
                    @endif
                    <th style="width: {{ $visitColWidth }};">Registro de la Visita</th>
                    <th style="width: {{ $obsColWidth }};">Observaciones / Novedades</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $customer)
                    <tr @if($customer->deleted_at) style="background-color: #fce8e6;" @endif>
                        <!-- Cliente y Contacto -->
                        <td>
                            @if(!isset($columns) || $columns['name'])
                                <strong style="font-size: 8.5pt; color: #2c3e50;">{{ $customer->name }}</strong><br>
                            @endif
                            @if(!isset($columns) || $columns['taxpayer_id'])
                                <span style="color: #555;">ID: {{ $customer->taxpayer_id ?: 'N/A' }}</span><br>
                            @endif
                            @if(!isset($columns) || $columns['phone'])
                                <span style="color: #555;">Telf: {{ $customer->phone ?: 'N/A' }}</span><br>
                            @endif
                            @if(!isset($columns) || $columns['address'])
                                <strong>Dir:</strong> {{ $customer->address }}<br>
                            @endif
                            @if(!isset($columns) || $columns['seller'])
                                <strong>Vendedor:</strong> {{ $customer->seller->name ?? 'N/A' }}
                            @endif
                        </td>
                        <!-- Estatus Financiero -->
                        @if($showFinancialCol)
                            <td>
                                @if(!isset($columns) || $columns['wallet_balance'])
                                    <strong>Billetera:</strong> ${{ number_format($customer->wallet_balance, 2) }}<br>
                                @endif
                                @if(!isset($columns) || $columns['allow_credit'])
                                    <strong>Permite Crédito:</strong> {{ $customer->allow_credit ? 'SÍ' : 'NO' }}<br>
                                @endif
                                @if($customer->allow_credit)
                                    @if(!isset($columns) || $columns['credit_limit'])
                                        <strong>Límite:</strong> ${{ number_format($customer->credit_limit, 2) }}<br>
                                    @endif
                                    @if(!isset($columns) || $columns['credit_days'])
                                        <strong>Días:</strong> {{ $customer->credit_days }}<br>
                                    @endif
                                @endif
                                @if(!isset($columns) || $columns['zone'])
                                    <strong>Zona:</strong> {{ $customer->zone ?: 'N/A' }}
                                @endif
                            </td>
                        @endif
                        <!-- Registro de Visita -->
                        <td>
                            <strong>Fecha Visita:</strong> <span class="line-write"></span><br><br>
                            
                            <div style="margin-bottom: 4px;">
                                <span class="checkbox-box"></span> Pedido Tomado (USD: <span class="line-write" style="width:50px;"></span>)
                            </div>
                            <div style="margin-bottom: 4px;">
                                <span class="checkbox-box"></span> Cobro Realizado (USD: <span class="line-write" style="width:45px;"></span>)
                            </div>
                            <div style="margin-left: 15px; margin-bottom: 4px;">
                                Recibo / Ref: <span class="line-write" style="width:80px;"></span>
                            </div>
                            <div style="margin-bottom: 4px;">
                                <span class="checkbox-box"></span> Cliente Ausente / Local Cerrado
                            </div>
                            <div style="margin-bottom: 4px;">
                                <span class="checkbox-box"></span> No Compró (Motivo: <span class="line-write" style="width:70px;"></span>)
                            </div>
                            <div style="margin-top: 8px;">
                                <strong>Próxima Visita:</strong> <span class="line-write"></span>
                            </div>
                        </td>
                        <!-- Observaciones / Novedades -->
                        <td>
                            <div class="line-write-obs"></div>
                            <div class="line-write-obs"></div>
                            <div class="line-write-obs"></div>
                            <div class="line-write-obs"></div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No hay clientes asignados a este vendedor.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="summary-label">TOTAL CLIENTES EN SEGUIMIENTO:</td>
                <td class="summary-value">{{ number_format($totalCustomersCount, 0) }}</td>
            </tr>
        </table>
    </div>

    <table class="footer-signatures" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">FIRMA VENDEDOR</div>
            </td>
            <td style="width: 33%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">CONTROL DE RUTAS</div>
            </td>
            <td style="width: 34%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">GERENCIA DE VENTAS</div>
            </td>
        </tr>
    </table>

</body>
</html>
