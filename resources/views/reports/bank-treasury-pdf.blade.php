<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Tesorería - {{ $bankName }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 25px 20px;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .header-container {
            width: 100%;
            border-bottom: 2px solid #1F4E79;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #1F4E79;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #2F5597;
            margin-top: 3px;
            text-transform: uppercase;
        }
        .report-range {
            font-size: 10px;
            color: #555555;
            margin-top: 2px;
        }
        .header-meta {
            text-align: right;
            font-size: 8px;
            color: #666666;
            line-height: 1.2;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 15px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kpi-card {
            background-color: #F2F4F7;
            border: 1px solid #D9E1E8;
            padding: 8px;
            text-align: center;
            border-radius: 4px;
            width: 30%;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: bold;
            color: #1F4E79;
            margin-top: 3px;
        }
        .kpi-label {
            font-size: 8px;
            color: #555555;
            text-transform: uppercase;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1F4E79;
            border-bottom: 1px solid #D9E1E8;
            padding-bottom: 3px;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #1F4E79;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 5px;
            font-size: 8px;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 5px;
            border-bottom: 1px solid #E4E7EB;
            font-size: 8px;
        }
        .data-table tr:nth-child(even) {
            background-color: #F8F9FA;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            color: #ffffff;
            display: inline-block;
        }
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .badge-primary { background-color: #007bff; }
        .badge-warning { background-color: #ffc107; color: #333333; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 7px;
            color: #999999;
            border-top: 1px solid #E4E7EB;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td>
                    <div class="company-name">{{ $config->name ?? 'JSPOS SYSTEM' }}</div>
                    <div class="report-title">
                        @if($type === 'closures')
                            Historial de Cortes Diarios
                        @elseif($type === 'expenses')
                            Reporte de Gastos Bancarios
                        @elseif($type === 'transfers')
                            Reporte de Transferencias Internas
                        @else
                            Reporte de Auditoría y Tesorería
                        @endif
                    </div>
                    <div class="report-range">
                        Cuenta: <strong>{{ $bankName }}</strong> &nbsp;|&nbsp; 
                        Período: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</strong>
                    </div>
                </td>
                <td class="header-meta">
                    Usuario: {{ $user->name ?? 'Admin' }}<br>
                    Generado: {{ $date }} {{ $time }}<br>
                    Moneda: {{ $currencyCode }}
                </td>
            </tr>
        </table>
    </div>

    @if($type !== 'closures')
        <!-- Resumen Financiero por Moneda (Estilo Estado de Cuenta Bancario) -->
        <div class="section-title">Resumen de Flujo Financiero y Estado de Cuenta</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Moneda</th>
                    <th class="text-center">Operaciones</th>
                    <th class="text-right">Total Ingresos / Depósitos (+)</th>
                    <th class="text-right">Total Egresos / Gastos (-)</th>
                    <th class="text-right">Flujo Neto del Período (=)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($totalsByCurrency as $curr => $t)
                    <tr>
                        <td><strong>{{ $curr }}</strong></td>
                        <td class="text-center">
                            <span class="badge badge-success">{{ $t['count_income'] }} Ingr.</span>
                            <span class="badge badge-danger">{{ $t['count_expenses'] }} Egr.</span>
                        </td>
                        <td class="text-right text-success font-weight-bold">
                            +{{ number_format($t['income'], 2) }} {{ $curr }}
                        </td>
                        <td class="text-right text-danger font-weight-bold">
                            -{{ number_format($t['expenses'], 2) }} {{ $curr }}
                        </td>
                        <td class="text-right font-weight-bold @if($t['net'] >= 0) text-success @else text-danger @endif">
                            {{ $t['net'] >= 0 ? '+' : '' }}{{ number_format($t['net'], 2) }} {{ $curr }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No se registran movimientos financieros en el período seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($type === 'dashboard' || $type === 'expenses')
            <!-- Categories Analysis -->
            @if(isset($analysis['categories']) && count($analysis['categories']) > 0)
            <div class="section-title">Distribución por Categoría de Gasto</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th class="text-right">Total Gastado</th>
                        <th class="text-right">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($analysis['categories'] as $cat)
                        <tr>
                            <td><strong>{{ $cat['category_name'] }}</strong></td>
                            <td>
                                @if($cat['is_essential'])
                                    <span class="badge badge-success">ESENCIAL</span>
                                @else
                                    <span class="badge badge-danger">DISCRECIONAL</span>
                                @endif
                            </td>
                            <td class="text-right">${{ number_format($cat['total_amount'], 2) }} {{ $currencyCode }}</td>
                            <td class="text-right">{{ $cat['percentage'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No hay egresos registrados en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @endif
        @endif

        <!-- Detailed Movements -->
        <div class="section-title">
            @if($type === 'expenses')
                Historial Detallado de Gastos
            @elseif($type === 'transfers')
                Historial Detallado de Transferencias
            @else
                Historial Detallado de Movimientos
            @endif
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Fecha</th>
                    <th style="width: 15%;">Banco</th>
                    <th style="width: 15%;">Tipo</th>
                    <th>Detalles / Beneficiario</th>
                    <th style="width: 15%;">Referencia</th>
                    <th class="text-right" style="width: 18%;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($m->date)->format('d/m/Y') }}</td>
                        <td>{{ $m->bank_name }}</td>
                        <td>
                            @if($m->type === 'INGRESO')
                                <span class="badge badge-success">INGRESO</span>
                            @elseif($m->type === 'GASTO')
                                <span class="badge badge-danger">GASTO</span>
                            @elseif($m->type === 'TRANSFER_IN')
                                <span class="badge badge-primary">TRANSF. IN</span>
                            @elseif($m->type === 'TRANSFER_OUT')
                                <span class="badge badge-warning">TRANSF. OUT</span>
                            @endif
                        </td>
                        <td>
                            @if($m->type === 'INGRESO')
                                Pago recibido en sistema
                            @elseif($m->type === 'GASTO')
                                {{ $m->category_name }} @if($m->beneficiary) - Al: {{ $m->beneficiary }} @endif
                            @elseif($m->type === 'TRANSFER_IN')
                                Desde: {{ $m->beneficiary }}
                            @elseif($m->type === 'TRANSFER_OUT')
                                Hacia: {{ $m->beneficiary }}
                            @endif
                        </td>
                        <td>{{ $m->reference ?? '-' }}</td>
                        <td class="text-right @if($m->type === 'GASTO' || $m->type === 'TRANSFER_OUT') text-danger @else text-success @endif">
                            <strong>
                                @if($m->type === 'GASTO' || $m->type === 'TRANSFER_OUT') - @else + @endif
                                ${{ number_format($m->amount, 2) }} {{ $m->currency_code }}
                            </strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay movimientos registrados en el rango de fechas.</td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($movements) > 0)
            <tfoot>
                @foreach($totalsByCurrency as $curr => $t)
                    <tr style="background-color: #EBF3FA; font-weight: bold; border-top: 2px solid #1F4E79;">
                        <td colspan="3" class="text-right" style="color: #1F4E79;">
                            TOTALES ({{ $curr }}):
                        </td>
                        <td class="text-left text-success">
                            Ingresos: +{{ number_format($t['income'], 2) }} {{ $curr }}
                        </td>
                        <td class="text-left text-danger">
                            Egresos: -{{ number_format($t['expenses'], 2) }} {{ $curr }}
                        </td>
                        <td class="text-right @if($t['net'] >= 0) text-success @else text-danger @endif">
                            Neto: {{ $t['net'] >= 0 ? '+' : '' }}{{ number_format($t['net'], 2) }} {{ $curr }}
                        </td>
                    </tr>
                @endforeach
            </tfoot>
            @endif
        </table>
    @else
        <!-- TAB: CORTES DIARIOS -->
        <div class="section-title">Historial de Cortes Diarios Realizados</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha Corte</th>
                    <th>Banco</th>
                    <th class="text-right">Saldo Apertura</th>
                    <th class="text-right">Total Ingresos</th>
                    <th class="text-right">Total Egresos</th>
                    <th class="text-right">Saldo Cierre</th>
                    <th>Cerrado Por</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $cls)
                    <tr>
                        <td><strong>{{ \Carbon\Carbon::parse($cls->closure_date)->format('d/m/Y') }}</strong></td>
                        <td>{{ $cls->bank_name }}</td>
                        <td class="text-right">${{ number_format($cls->opening_balance, 2) }} {{ $cls->currency_code }}</td>
                        <td class="text-right text-success">+${{ number_format($cls->total_income, 2) }} ({{ $cls->total_income_count }})</td>
                        <td class="text-right text-danger">-${{ number_format($cls->total_expenses, 2) }} ({{ $cls->total_expenses_count }})</td>
                        <td class="text-right font-weight-bold text-primary">${{ number_format($cls->closing_balance, 2) }} {{ $cls->currency_code }}</td>
                        <td>{{ $cls->user_name ?? 'Cierre Automático' }}</td>
                        <td>{{ $cls->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No hay cortes diarios registrados en el período.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        JSPOS Sales System &copy; {{ date('Y') }} - Reporte de Auditoría Bancaria y Flujo de Fondos. Página 1 de 1.
    </div>

</body>
</html>
