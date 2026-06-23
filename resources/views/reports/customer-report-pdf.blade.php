<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Clientes</title>
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
            background-color: #f2f2f2;
            border: 1px solid #999;
            padding: 3px 5px;
            text-align: center;
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table td {
            padding: 3px 5px;
            border-bottom: 1px solid #ddd;
            border-left: 0.5pt solid #eee;
            border-right: 0.5pt solid #eee;
            font-size: 7.2pt;
            vertical-align: middle;
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

        .summary-box {
            width: 40%;
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
            font-size: 8pt;
        }
        .summary-value {
            text-align: right;
            font-size: 8pt;
            font-weight: bold;
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
                Fecha : {{ $date }}<br>
                Generado por : {{ strtoupper($user->name ?? 'N/A') }}
            </td>
        </tr>
    </table>

    <div class="report-title">Reporte de Clientes por Vendedor</div>
    
    <div class="filter-info">
        <strong>Filtros aplicados:</strong><br>
        • Mostrar desactivados/eliminados: {{ $showDeleted ? 'SÍ' : 'NO' }}<br>
        • Agrupación: {{ $isGrouped ? 'Agrupado por Vendedor' : 'Sin Agrupar' }}
    </div>

    @php
        $totalCustomersCount = 0;
        $totalWalletBalance = 0;
    @endphp

    @foreach ($customersData as $groupName => $items)
        @php
            $totalCustomersCount += $items->count();
            $totalWalletBalance += $items->sum('wallet_balance');
        @endphp

        @if($isGrouped)
            <div class="group-header">
                VENDEDOR: {{ $groupName ?: 'SIN VENDEDOR' }} (Clientes: {{ $items->count() }})
            </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    @if($columns['name']) <th>Cliente</th> @endif
                    @if($columns['taxpayer_id']) <th>Identificación</th> @endif
                    @if($columns['address']) <th>Dirección</th> @endif
                    @if($columns['city']) <th>Ciudad</th> @endif
                    @if($columns['phone']) <th>Teléfono</th> @endif
                    @if($columns['seller']) <th>Vendedor</th> @endif
                    @if($columns['wallet_balance']) <th>Billetera</th> @endif
                    @if($columns['zone']) <th>Zona</th> @endif
                    @if($columns['allow_credit']) <th>Crédito</th> @endif
                    @if($columns['credit_limit']) <th>Límite</th> @endif
                    @if($columns['credit_days']) <th>Días</th> @endif
                    @if($columns['notifications']) <th>Notificaciones (WA/Email)</th> @endif
                    @if($columns['status']) <th>Estado</th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse($items as $customer)
                    <tr class="text-center" @if($customer->deleted_at) style="background-color: #fce8e6;" @endif>
                        @if($columns['name']) <td class="text-left font-bold">{{ $customer->name }}</td> @endif
                        @if($columns['taxpayer_id']) <td>{{ $customer->taxpayer_id }}</td> @endif
                        @if($columns['address']) <td class="text-left">{{ $customer->address }}</td> @endif
                        @if($columns['city']) <td>{{ $customer->city }}</td> @endif
                        @if($columns['phone']) <td>{{ $customer->phone }}</td> @endif
                        @if($columns['seller']) <td>{{ $customer->seller->name ?? 'Sin Vendedor' }}</td> @endif
                        @if($columns['wallet_balance']) <td class="text-right">${{ number_format($customer->wallet_balance, 2) }}</td> @endif
                        @if($columns['zone']) <td>{{ $customer->zone }}</td> @endif
                        @if($columns['allow_credit']) <td>{{ $customer->allow_credit ? 'SÍ' : 'NO' }}</td> @endif
                        @if($columns['credit_limit']) <td class="text-right">${{ number_format($customer->credit_limit, 2) }}</td> @endif
                        @if($columns['credit_days']) <td>{{ $customer->credit_days }}</td> @endif
                        @if($columns['notifications'])
                            <td class="text-left" style="font-size: 6.5pt; line-height: 1.1;">
                                WA Ventas: {{ $customer->whatsapp_notify_sales ? 'SÍ' : 'NO' }} | WA Pagos: {{ $customer->whatsapp_notify_payments ? 'SÍ' : 'NO' }}<br>
                                Email Ventas: {{ $customer->email_notify_sales ? 'SÍ' : 'NO' }} | Email Pagos: {{ $customer->email_notify_payments ? 'SÍ' : 'NO' }}
                            </td>
                        @endif
                        @if($columns['status'])
                            <td>
                                @if($customer->deleted_at)
                                    <span style="color: red; font-weight: bold;">Desactivado</span>
                                @else
                                    <span style="color: green;">Activo</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="20" class="text-center text-muted">No se encontraron registros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="summary-label">TOTAL CLIENTES:</td>
                <td class="summary-value">{{ number_format($totalCustomersCount, 0) }}</td>
            </tr>
            @if($columns['wallet_balance'])
            <tr>
                <td class="summary-label">TOTAL SALDO BILLETERAS:</td>
                <td class="summary-value">${{ number_format($totalWalletBalance, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="footer-signatures" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">ELABORADO POR</div>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: bottom;">
                <div class="signature-line"></div>
                <div style="font-size: 7.5pt; font-weight: bold;">AUTORIZADO POR</div>
            </td>
        </tr>
    </table>

</body>
</html>
