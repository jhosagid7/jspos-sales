<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual de Ingresos - {{ $monthLabel }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10px 15px;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 7.5px;
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
            margin-bottom: 8px;
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
            padding: 1px 5px;
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
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 2px;
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

        /* Matrix Table Styling */
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #FFFFFF;
            margin-top: 5px;
        }
        .matrix-table th {
            border: 0.5px solid #2F5597;
            padding: 4px;
            text-align: center;
            font-weight: bold;
        }
        .matrix-table td {
            border: 0.5px solid #D9D9D9;
            padding: 4px 5px;
            text-align: right;
        }
        .matrix-th-main {
            background-color: #5B9BD5;
            color: #FFFFFF;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .matrix-th-category {
            background-color: #2F5597;
            color: #FFFFFF;
            font-size: 8.5px;
            text-align: left !important;
            padding-left: 8px !important;
        }
        .matrix-th-total {
            background-color: #1F4E79;
            color: #FFFFFF;
            font-size: 8.5px;
        }
        .matrix-th-sub {
            background-color: #F2F2F2;
            color: #333333;
            font-size: 7.5px;
            border-bottom: 1.5px solid #2F5597 !important;
        }
        .matrix-td-label {
            font-weight: bold;
            color: #2F5597;
            text-align: left !important;
            padding-left: 8px !important;
            background-color: #FAFBFD;
        }
        .matrix-td-empty {
            color: #BBBBBB;
        }

        /* Monthly Total Columns Highlight */
        .matrix-td-total-contado {
            background-color: #F2F9EE;
            font-weight: bold;
            border-left: 1px solid #1F4E79 !important;
        }
        .matrix-td-total-cobranza {
            background-color: #FFF2EB;
            font-weight: bold;
            border-right: 1px solid #1F4E79 !important;
        }
        
        /* Row Styles */
        .row-subtotal {
            background-color: #2F5597;
            color: #FFFFFF;
            font-weight: bold;
        }
        .row-subtotal td {
            border: 0.5px solid #1F4E79;
            color: #FFFFFF;
        }
        .row-subtotal-month-contado {
            background-color: #1F4E79 !important;
            color: #FFFFFF !important;
        }
        .row-subtotal-month-cobranza {
            background-color: #1F4E79 !important;
            color: #FFFFFF !important;
        }

        .row-credito {
            background-color: #D9E1F2;
            color: #1F4E79;
            font-weight: bold;
        }
        .row-credito-month {
            background-color: #B4C6E7 !important;
            color: #1F4E79 !important;
        }

        .row-ventas-creditos {
            background-color: #1F4E79;
            color: #FFFFFF;
            font-weight: bold;
        }
        .row-ventas-creditos td {
            border: 0.5px solid #1F4E79;
            color: #FFFFFF;
        }
        .row-ventas-creditos-month {
            background-color: #16365C !important;
            color: #FFFFFF !important;
        }

        .row-total-general {
            background-color: #D9D9D9;
            color: #333333;
            font-weight: bold;
        }
        .row-total-general-month {
            background-color: #AEAAAA !important;
            font-size: 8.5px;
        }

        .row-total-recibido {
            background-color: #4472C4;
            color: #FFFFFF;
            font-weight: bold;
        }
        .row-total-recibido td {
            border: 0.5px solid #2F5597;
            color: #FFFFFF;
        }
        .row-total-recibido-month {
            background-color: #2F5597 !important;
            font-size: 9px;
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
                    <div class="report-title">Consolidado Mensual de Ingresos</div>
                    <div class="week-range">{{ $monthLabel }}</div>
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

    <table class="matrix-table">
        <thead>
            <tr>
                <th rowspan="2" class="matrix-th-category">MÉTODO / BANCO</th>
                @foreach($weeks as $wKey => $week)
                    <th colspan="2" class="matrix-th-main">{{ $week['label'] }}</th>
                @endforeach
                <th colspan="2" class="matrix-th-total">TOTAL MES</th>
            </tr>
            <tr>
                @foreach($weeks as $wKey => $week)
                    <th class="matrix-th-sub" style="width: 65px;">CONTADO</th>
                    <th class="matrix-th-sub" style="width: 65px;">COBRANZA</th>
                @endforeach
                <th class="matrix-th-sub" style="width: 80px; background-color: #E2EFDA; color: #375623;">CONTADO</th>
                <th class="matrix-th-sub" style="width: 80px; background-color: #FCE4D6; color: #c65911;">COBRANZA</th>
            </tr>
        </thead>
        <tbody>
            <!-- Categories Rows -->
            @foreach($report as $catName => $weekData)
                <tr>
                    <td class="matrix-td-label">{{ $catName }}</td>
                    @foreach($weeks as $wKey => $week)
                        @php $amt = $weekData[$wKey]; @endphp
                        <td class="{{ $amt['contado'] > 0 ? '' : 'matrix-td-empty' }}">
                            {{ $amt['contado'] > 0 ? number_format($amt['contado'], 2, ',', '.') : '0,00' }}
                        </td>
                        <td class="{{ $amt['cobranza'] > 0 ? '' : 'matrix-td-empty' }}">
                            {{ $amt['cobranza'] > 0 ? number_format($amt['cobranza'], 2, ',', '.') : '0,00' }}
                        </td>
                    @endforeach
                    <!-- Monthly totals for category -->
                    <td class="matrix-td-total-contado {{ $monthlyTotals[$catName]['contado'] > 0 ? '' : 'matrix-td-empty' }}">
                        {{ $monthlyTotals[$catName]['contado'] > 0 ? number_format($monthlyTotals[$catName]['contado'], 2, ',', '.') : '0,00' }}
                    </td>
                    <td class="matrix-td-total-cobranza {{ $monthlyTotals[$catName]['cobranza'] > 0 ? '' : 'matrix-td-empty' }}">
                        {{ $monthlyTotals[$catName]['cobranza'] > 0 ? number_format($monthlyTotals[$catName]['cobranza'], 2, ',', '.') : '0,00' }}
                    </td>
                </tr>
            @endforeach

            <!-- Subtotal Recibido Row -->
            <tr class="row-subtotal">
                <td style="text-align: left !important; padding-left: 8px !important; font-weight: bold;">SUBTOTAL RECIBIDO</td>
                @foreach($weeks as $wKey => $week)
                    <td>{{ number_format($weeklyMetrics[$wKey]['subtotal_contado'], 2, ',', '.') }}</td>
                    <td>{{ number_format($weeklyMetrics[$wKey]['subtotal_cobranza'], 2, ',', '.') }}</td>
                @endforeach
                <td class="row-subtotal-month-contado">{{ number_format($monthlySubtotalContado, 2, ',', '.') }}</td>
                <td class="row-subtotal-month-cobranza">{{ number_format($monthlySubtotalCobranza, 2, ',', '.') }}</td>
            </tr>

            <!-- Ventas a Crédito Row -->
            <tr class="row-credito">
                <td style="text-align: left !important; padding-left: 8px !important; font-weight: bold;">VENTAS A CRÉDITO</td>
                @foreach($weeks as $wKey => $week)
                    <td>{{ number_format($weeklyMetrics[$wKey]['ventas_credito'], 2, ',', '.') }}</td>
                    <td class="matrix-td-empty">0,00</td>
                @endforeach
                <td class="row-credito-month">{{ number_format($monthlyCreditTotal, 2, ',', '.') }}</td>
                <td class="matrix-td-empty row-credito-month">0,00</td>
            </tr>

            <!-- Ventas + Créditos Row -->
            <tr class="row-ventas-creditos">
                <td style="text-align: left !important; padding-left: 8px !important; font-weight: bold;">VENTAS + CRÉDITOS</td>
                @foreach($weeks as $wKey => $week)
                    <td>{{ number_format($weeklyMetrics[$wKey]['ventas_mas_credito'], 2, ',', '.') }}</td>
                    <td>{{ number_format($weeklyMetrics[$wKey]['subtotal_cobranza'], 2, ',', '.') }}</td>
                @endforeach
                <td class="row-ventas-creditos-month">{{ number_format($monthlyVentasMasCredito, 2, ',', '.') }}</td>
                <td class="row-ventas-creditos-month">{{ number_format($monthlySubtotalCobranza, 2, ',', '.') }}</td>
            </tr>

            <!-- Total General Row -->
            <tr class="row-total-general">
                <td style="text-align: left !important; padding-left: 8px !important; font-weight: bold;">TOTAL GENERAL</td>
                @foreach($weeks as $wKey => $week)
                    <td colspan="2" style="text-align: center;">{{ number_format($weeklyMetrics[$wKey]['total_general'], 2, ',', '.') }}</td>
                @endforeach
                <td colspan="2" class="row-total-general-month" style="text-align: center;">{{ number_format($monthlyTotalGeneral, 2, ',', '.') }}</td>
            </tr>

            <!-- Total Recibido Row -->
            <tr class="row-total-recibido">
                <td style="text-align: left !important; padding-left: 8px !important; font-weight: bold;">TOTAL RECIBIDO</td>
                @foreach($weeks as $wKey => $week)
                    <td colspan="2" style="text-align: center;">{{ number_format($weeklyMetrics[$wKey]['total_recibido'], 2, ',', '.') }}</td>
                @endforeach
                <td colspan="2" class="row-total-recibido-month" style="text-align: center; font-weight: 900;">{{ number_format($monthlyTotalRecibido, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
