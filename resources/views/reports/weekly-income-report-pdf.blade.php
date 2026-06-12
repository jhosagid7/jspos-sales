<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Semanal de Ingresos - {{ $weekLabel }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10px 15px;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 8px;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        /* Header Styling */
        .header-container {
            width: 100%;
            border-bottom: 2px solid #2F5597;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-logo-title {
            width: 50%;
            vertical-align: middle;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #1F4E79;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 15px;
            font-weight: bold;
            color: #2F5597;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .week-range {
            font-size: 11px;
            font-weight: bold;
            color: #555555;
            margin-top: 2px;
        }
        .header-meta {
            width: 50%;
            vertical-align: middle;
            text-align: right;
        }
        .meta-table {
            width: auto;
            margin-left: auto;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 5px;
            font-size: 8px;
            color: #555555;
        }
        .meta-label {
            font-weight: bold;
            color: #333333;
            text-align: right;
        }
        .meta-value {
            text-align: left;
            padding-left: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 3px;
            text-align: center;
        }
        .status-preliminar {
            background-color: #FFF2CC;
            color: #B25E00;
            border: 1px solid #FFE599;
        }
        .status-consolidado {
            background-color: #E2EFDA;
            color: #375623;
            border: 1px solid #C6E0B4;
        }

        /* Layout Grid */
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0px;
            table-layout: fixed;
        }
        .grid-col {
            width: 25%;
            vertical-align: top;
        }

        /* Day Card / Table Styling */
        .card-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background-color: #FFFFFF;
        }
        .card-header {
            background-color: #5B9BD5;
            color: #FFFFFF;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            padding: 5px;
            border: 1px solid #2F5597;
            text-transform: uppercase;
        }
        .card-th {
            background-color: #F2F2F2;
            color: #333333;
            font-weight: bold;
            font-size: 7.5px;
            text-align: center;
            padding: 3px;
            border: 0.5px solid #D9D9D9;
        }
        .card-td-label {
            font-weight: bold;
            color: #444444;
            padding: 3.5px 5px;
            border: 0.5px solid #D9D9D9;
            text-align: left;
        }
        .card-td-amount {
            padding: 3.5px 5px;
            border: 0.5px solid #D9D9D9;
            text-align: right;
        }
        .card-td-empty {
            color: #BBBBBB;
        }
        
        /* Row Styles */
        .row-subtotal {
            background-color: #2F5597;
            color: #FFFFFF;
            font-weight: bold;
        }
        .row-subtotal td {
            border: 0.5px solid #1F4E79;
        }
        .row-credito {
            background-color: #D9E1F2;
            color: #1F4E79;
            font-weight: bold;
        }
        .row-ventas-creditos {
            background-color: #1F4E79;
            color: #FFFFFF;
            font-weight: bold;
        }
        .row-ventas-creditos td {
            border: 0.5px solid #1F4E79;
        }
        .row-total-general {
            background-color: #D9D9D9;
            color: #333333;
            font-weight: bold;
        }
        .row-total-recibido {
            background-color: #4472C4;
            color: #FFFFFF;
            font-weight: bold;
        }
        .row-total-recibido td {
            border: 0.5px solid #2F5597;
        }

        /* Highlighted Total Card Header */
        .card-header-total {
            background-color: #2F5597;
            border: 1px solid #1F4E79;
        }

        /* Watermark styling */
        .watermark {
            position: fixed;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 60px;
            color: rgba(220, 220, 220, 0.22);
            font-weight: bold;
            z-index: -1000;
            white-space: nowrap;
            text-transform: uppercase;
            pointer-events: none;
            letter-spacing: 4px;
        }
    </style>
</head>
<body>

    @if($isPreliminar)
        <div class="watermark">PRELIMINAR - BORRADOR</div>
    @endif

    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="header-logo-title">
                    <div class="company-name">{{ $config->business_name }}</div>
                    <div class="report-title">Reporte de Ingresos Semanal</div>
                    <div class="week-range">{{ $weekLabel }}</div>
                </td>
                <td class="header-meta">
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Estado:</td>
                            <td class="meta-value">
                                @if($isPreliminar)
                                    <span class="status-badge status-preliminar">Preliminar</span>
                                @else
                                    <span class="status-badge status-consolidado">Consolidado</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="meta-label">Generado por:</td>
                            <td class="meta-value" style="font-weight: bold;">{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Fecha Emisión:</td>
                            <td class="meta-value">{{ $dateGenerated }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="grid-table">
        <tr>
            <!-- Col 1: Lunes & Jueves -->
            <td class="grid-col">
                @foreach(['LUNES', 'JUEVES'] as $dName)
                    @php $day = $report[$dName]; @endphp
                    <table class="card-table">
                        <thead>
                            <tr>
                                <th colspan="3" class="card-header">{{ $dName }} ({{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }})</th>
                            </tr>
                            <tr>
                                <th class="card-th" style="width: 44%;">MÉTODO / BANCO</th>
                                <th class="card-th" style="width: 28%;">CONTADO</th>
                                <th class="card-th" style="width: 28%;">COBRANZA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($day['data'] as $catName => $amounts)
                                <tr>
                                    <td class="card-td-label">{{ $catName }}</td>
                                    <td class="card-td-amount {{ $amounts['contado'] > 0 ? '' : 'card-td-empty' }}">
                                        {{ $amounts['contado'] > 0 ? number_format($amounts['contado'], 2, ',', '.') : '0,00' }}
                                    </td>
                                    <td class="card-td-amount {{ $amounts['cobranza'] > 0 ? '' : 'card-td-empty' }}">
                                        {{ $amounts['cobranza'] > 0 ? number_format($amounts['cobranza'], 2, ',', '.') : '0,00' }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="row-subtotal">
                                <td class="card-td-label" style="color: #FFFFFF;">SUBTOTAL RECIBIDO</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_contado'], 2, ',', '.') }}</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_cobranza'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-credito">
                                <td class="card-td-label" style="color: #1F4E79;">VENTAS A CRÉDITO</td>
                                <td class="card-td-amount">{{ number_format($day['ventas_credito'], 2, ',', '.') }}</td>
                                <td class="card-td-amount card-td-empty">0,00</td>
                            </tr>
                            <tr class="row-ventas-creditos">
                                <td class="card-td-label" style="color: #FFFFFF;">VENTAS + CRÉDITOS</td>
                                <td class="card-td-amount">{{ number_format($day['ventas_mas_credito'], 2, ',', '.') }}</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_cobranza'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-total-general">
                                <td class="card-td-label">TOTAL GENERAL</td>
                                <td colspan="2" class="card-td-amount" style="text-align: center;">{{ number_format($day['total_general'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-total-recibido">
                                <td class="card-td-label" style="color: #FFFFFF;">TOTAL RECIBIDO</td>
                                <td colspan="2" class="card-td-amount" style="text-align: center;">{{ number_format($day['total_recibido'], 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endforeach
            </td>

            <!-- Col 2: Martes & Viernes -->
            <td class="grid-col">
                @foreach(['MARTES', 'VIERNES'] as $dName)
                    @php $day = $report[$dName]; @endphp
                    <table class="card-table">
                        <thead>
                            <tr>
                                <th colspan="3" class="card-header">{{ $dName }} ({{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }})</th>
                            </tr>
                            <tr>
                                <th class="card-th" style="width: 44%;">MÉTODO / BANCO</th>
                                <th class="card-th" style="width: 28%;">CONTADO</th>
                                <th class="card-th" style="width: 28%;">COBRANZA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($day['data'] as $catName => $amounts)
                                <tr>
                                    <td class="card-td-label">{{ $catName }}</td>
                                    <td class="card-td-amount {{ $amounts['contado'] > 0 ? '' : 'card-td-empty' }}">
                                        {{ $amounts['contado'] > 0 ? number_format($amounts['contado'], 2, ',', '.') : '0,00' }}
                                    </td>
                                    <td class="card-td-amount {{ $amounts['cobranza'] > 0 ? '' : 'card-td-empty' }}">
                                        {{ $amounts['cobranza'] > 0 ? number_format($amounts['cobranza'], 2, ',', '.') : '0,00' }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="row-subtotal">
                                <td class="card-td-label" style="color: #FFFFFF;">SUBTOTAL RECIBIDO</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_contado'], 2, ',', '.') }}</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_cobranza'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-credito">
                                <td class="card-td-label" style="color: #1F4E79;">VENTAS A CRÉDITO</td>
                                <td class="card-td-amount">{{ number_format($day['ventas_credito'], 2, ',', '.') }}</td>
                                <td class="card-td-amount card-td-empty">0,00</td>
                            </tr>
                            <tr class="row-ventas-creditos">
                                <td class="card-td-label" style="color: #FFFFFF;">VENTAS + CRÉDITOS</td>
                                <td class="card-td-amount">{{ number_format($day['ventas_mas_credito'], 2, ',', '.') }}</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_cobranza'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-total-general">
                                <td class="card-td-label">TOTAL GENERAL</td>
                                <td colspan="2" class="card-td-amount" style="text-align: center;">{{ number_format($day['total_general'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-total-recibido">
                                <td class="card-td-label" style="color: #FFFFFF;">TOTAL RECIBIDO</td>
                                <td colspan="2" class="card-td-amount" style="text-align: center;">{{ number_format($day['total_recibido'], 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endforeach
            </td>

            <!-- Col 3: Miércoles & Sábado -->
            <td class="grid-col">
                @foreach(['MIERCOLES', 'SABADO'] as $dName)
                    @php $day = $report[$dName]; @endphp
                    <table class="card-table">
                        <thead>
                            <tr>
                                <th colspan="3" class="card-header">{{ $dName }} ({{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }})</th>
                            </tr>
                            <tr>
                                <th class="card-th" style="width: 44%;">MÉTODO / BANCO</th>
                                <th class="card-th" style="width: 28%;">CONTADO</th>
                                <th class="card-th" style="width: 28%;">COBRANZA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($day['data'] as $catName => $amounts)
                                <tr>
                                    <td class="card-td-label">{{ $catName }}</td>
                                    <td class="card-td-amount {{ $amounts['contado'] > 0 ? '' : 'card-td-empty' }}">
                                        {{ $amounts['contado'] > 0 ? number_format($amounts['contado'], 2, ',', '.') : '0,00' }}
                                    </td>
                                    <td class="card-td-amount {{ $amounts['cobranza'] > 0 ? '' : 'card-td-empty' }}">
                                        {{ $amounts['cobranza'] > 0 ? number_format($amounts['cobranza'], 2, ',', '.') : '0,00' }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="row-subtotal">
                                <td class="card-td-label" style="color: #FFFFFF;">SUBTOTAL RECIBIDO</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_contado'], 2, ',', '.') }}</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_cobranza'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-credito">
                                <td class="card-td-label" style="color: #1F4E79;">VENTAS A CRÉDITO</td>
                                <td class="card-td-amount">{{ number_format($day['ventas_credito'], 2, ',', '.') }}</td>
                                <td class="card-td-amount card-td-empty">0,00</td>
                            </tr>
                            <tr class="row-ventas-creditos">
                                <td class="card-td-label" style="color: #FFFFFF;">VENTAS + CRÉDITOS</td>
                                <td class="card-td-amount">{{ number_format($day['ventas_mas_credito'], 2, ',', '.') }}</td>
                                <td class="card-td-amount">{{ number_format($day['subtotal_cobranza'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-total-general">
                                <td class="card-td-label">TOTAL GENERAL</td>
                                <td colspan="2" class="card-td-amount" style="text-align: center;">{{ number_format($day['total_general'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="row-total-recibido">
                                <td class="card-td-label" style="color: #FFFFFF;">TOTAL RECIBIDO</td>
                                <td colspan="2" class="card-td-amount" style="text-align: center;">{{ number_format($day['total_recibido'], 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endforeach
            </td>

            <!-- Col 4: TOTAL SEMANA -->
            <td class="grid-col">
                <table class="card-table" style="border: 1px solid #1F4E79;">
                    <thead>
                        <tr>
                            <th colspan="3" class="card-header card-header-total">TOTAL SEMANA</th>
                        </tr>
                        <tr style="background-color: #D9E1F2;">
                            <th class="card-th" style="width: 44%; color: #1F4E79;">MÉTODO / BANCO</th>
                            <th class="card-th" style="width: 28%; color: #1F4E79;">CONTADO</th>
                            <th class="card-th" style="width: 28%; color: #1F4E79;">COBRANZA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($weeklyTotals as $catName => $amounts)
                            <tr style="background-color: #FAFBFD;">
                                <td class="card-td-label" style="color: #2F5597;">{{ $catName }}</td>
                                <td class="card-td-amount {{ $amounts['contado'] > 0 ? '' : 'card-td-empty' }}" style="font-weight: bold;">
                                    {{ $amounts['contado'] > 0 ? number_format($amounts['contado'], 2, ',', '.') : '0,00' }}
                                </td>
                                <td class="card-td-amount {{ $amounts['cobranza'] > 0 ? '' : 'card-td-empty' }}" style="font-weight: bold;">
                                    {{ $amounts['cobranza'] > 0 ? number_format($amounts['cobranza'], 2, ',', '.') : '0,00' }}
                                </td>
                            </tr>
                        @endforeach
                        
                        <tr class="row-subtotal">
                            <td class="card-td-label" style="color: #FFFFFF;">SUBTOTAL RECIBIDO</td>
                            <td class="card-td-amount">{{ number_format($weeklySubtotalContado, 2, ',', '.') }}</td>
                            <td class="card-td-amount">{{ number_format($weeklySubtotalCobranza, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="row-credito">
                            <td class="card-td-label" style="color: #1F4E79;">VENTAS A CRÉDITO</td>
                            <td class="card-td-amount">{{ number_format($weeklyCreditTotal, 2, ',', '.') }}</td>
                            <td class="card-td-amount card-td-empty">0,00</td>
                        </tr>
                        <tr class="row-ventas-creditos">
                            <td class="card-td-label" style="color: #FFFFFF;">VENTAS + CRÉDITOS</td>
                            <td class="card-td-amount">{{ number_format($weeklyVentasMasCredito, 2, ',', '.') }}</td>
                            <td class="card-td-amount">{{ number_format($weeklySubtotalCobranza, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="row-total-general" style="font-size: 9px;">
                            <td class="card-td-label">TOTAL GENERAL</td>
                            <td colspan="2" class="card-td-amount" style="text-align: center;">{{ number_format($weeklyTotalGeneral, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="row-total-recibido" style="font-size: 10px;">
                            <td class="card-td-label" style="color: #FFFFFF;">TOTAL RECIBIDO</td>
                            <td colspan="2" class="card-td-amount" style="text-align: center; font-weight: 900;">{{ number_format($weeklyTotalRecibido, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
