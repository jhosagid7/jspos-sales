<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas General</title>
    <style>
        @page {
            margin: 0.3cm;
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
        }
        .header td {
            vertical-align: top;
        }
        .business-info h2 {
            margin: 0;
            font-size: 12pt;
        }
        .report-info {
            text-align: right;
            font-size: 8pt;
        }
        .report-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }

        /* Summary Table Style */
        .summary-block {
            width: 100%;
            margin-bottom: 15px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 1px 5px;
            font-size: 7.5pt;
        }
        .summary-label {
            font-weight: normal;
        }
        .summary-value {
            text-align: right;
            font-weight: bold;
        }

        /* Main Table Style */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f2f2f2;
            border: 1px solid #999;
            padding: 3px;
            text-align: left;
            font-size: 7.5pt;
            white-space: nowrap;
        }
        .table td {
            padding: 2px 3px;
            border-bottom: 1px solid #ddd;
            font-size: 7.2pt;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-red { color: #dc3545; }
        .text-green { color: #28a745; }
        .text-blue { color: #17a2b8; }
        .text-orange { color: #fd7e14; }
        .text-bold { font-weight: bold; }
        .text-muted { color: #999; font-size: 6.5pt; }

        .badge-paid {
            background-color: #28a745;
            color: #fff;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5pt;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #333;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5pt;
        }
        .badge-returned {
            background-color: #dc3545;
            color: #fff;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5pt;
        }

        .totals-row td {
            font-weight: bold;
            border-top: 2px solid #333;
            padding: 4px 3px;
            font-size: 8pt;
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
                Fecha : {{ \Carbon\Carbon::now()->format('d/m/Y') }}<br>
                Hora : {{ \Carbon\Carbon::now()->format('h:i:s a') }}<br>
                Pág : 1
            </td>
        </tr>
    </table>

    <div class="report-title">REPORTE DE VENTAS GENERAL</div>

    <div style="margin-bottom: 10px; font-size: 8pt;">
        @if($dateFrom && $dateTo)
            Periodo : {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
        @else
            Fecha : {{ \Carbon\Carbon::now()->format('d/m/Y') }}
        @endif
        <br>
        Moneda de Referencia : Dólares<br>
        Operador : {{ strtoupper($user->name ?? 'N/A') }}
        @if($filterInfo)
            <br>Filtros : {{ $filterInfo }}
        @endif
    </div>

    {{-- Summary Block --}}
    <div class="summary-block">
        <table class="summary-table">
            <tr>
                <td width="50%" style="vertical-align: top; border-right: 1px solid #ccc;">
                    <table width="100%">
                        <tr>
                            <td class="summary-label">Total Facturas :</td>
                            <td class="summary-value">{{ number_format($summary['total_count'], 0) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Artículos :</td>
                            <td class="summary-value">{{ number_format($summary['total_items'], 0) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Base (USD) :</td>
                            <td class="summary-value">${{ number_format($summary['total_base'], 2) }}</td>
                        </tr>
                    </table>
                </td>
                <td width="50%" style="vertical-align: top; padding-left: 10px;">
                    <table width="100%">
                        <tr>
                            <td class="summary-label">Total Ventas (USD) :</td>
                            <td class="summary-value">${{ number_format($summary['total_usd'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Total Crédito (USD) :</td>
                            <td class="summary-value" style="color: #dc3545;">${{ number_format($summary['total_credit'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Ventas Contado :</td>
                            <td class="summary-value">{{ $summary['count_cash'] }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Ventas Crédito :</td>
                            <td class="summary-value">{{ $summary['count_credit'] }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- Transactions Table --}}
    <div style="font-weight: bold; font-size: 8pt; margin-bottom: 5px; text-decoration: underline;">Transacciones</div>

    <table class="table">
        <thead>
            <tr>
                @if($columns['folio']) <th>Documento</th> @endif
                @if($columns['cliente']) <th>Cliente</th> @endif
                @if($columns['operador']) <th>Operador</th> @endif
                @if($columns['vendedor']) <th>Vendedor</th> @endif
                @if($columns['base']) <th class="text-right">Base</th> @endif
                @if($columns['porcentaje']) <th class="text-center">%</th> @endif
                @if($columns['comision']) <th class="text-right">Comisión</th> @endif
                @if($columns['flete']) <th class="text-right">Flete</th> @endif
                @if($columns['recargo']) <th class="text-right">Recargo</th> @endif
                @if($columns['diferencial']) <th class="text-right">Dif.</th> @endif
                @if($columns['total']) <th class="text-right">Total</th> @endif
                @if($columns['credito']) <th class="text-right">Crédito</th> @endif
                @if($columns['acuerdo']) <th class="text-center">Acuerdo</th> @endif
                @if($columns['articulos']) <th class="text-center">Art.</th> @endif
                @if($columns['estatus']) <th class="text-center">Estatus</th> @endif
                @if($columns['tipo']) <th class="text-center">Tipo</th> @endif
                @if($columns['fecha']) <th class="text-center">Fecha</th> @endif
            </tr>
        </thead>
        <tbody>
            @php
                $totBase = 0; $totComm = 0; $totFreight = 0; $totMarkup = 0; $totDiff = 0; $totTotal = 0; $totCredit = 0; $totItems = 0;
                $cutOffDate = \App\Services\ConfigurationService::getSequentialCutOffDate();
                
                $loopData = $isGrouped ? $groupedSales : [['name' => '', 'sales' => $sales]];
            @endphp
            @foreach($loopData as $groupKey => $groupData)
                @if($isGrouped)
                    <tr class="group-header" style="background-color: #f1f1f1; font-weight: bold;">
                        <td colspan="15" style="padding: 5px;">
                            <span style="font-size: 10pt;">{{ strtoupper($groupData['name']) }}</span>
                            <span style="float: right;">Subtotal: ${{ number_format($groupData['total_usd'] ?? 0, 2) }}</span>
                        </td>
                    </tr>
                @endif
                @foreach($groupData['sales'] as $sale)
                @php
                    // Calculate surcharges
                    $base = $sale->base_amount > 0 ? floatval($sale->base_amount) : 0;
                    $commPercent = $sale->resolved_commission_percent;
                    $freightPercent = $sale->resolved_freight_percent;
                    $diffPercent = $sale->resolved_exchange_diff_percent;
                    $markupPercent = $sale->resolved_base_markup_percent;
                    $isSequential = $sale->created_at >= $cutOffDate;

                    if ($isSequential) {
                        $surchargePercent = (((1 + ($commPercent + $freightPercent + $markupPercent) / 100) * (1 + $diffPercent / 100)) - 1) * 100;
                    } else {
                        $surchargePercent = $commPercent + $freightPercent + $markupPercent + $diffPercent;
                    }

                    if ($base == 0 && $sale->total_usd > 0) {
                        if (!$isSequential) {
                            $base = $surchargePercent > 0 ? $sale->total_usd / (1 + ($surchargePercent / 100)) : $sale->total_usd;
                        } else {
                            $base = ($sale->total_usd / (1 + ($diffPercent / 100))) / (1 + (($commPercent + $freightPercent + $markupPercent) / 100));
                        }
                    }

                    $commAmt = $sale->commission_amount > 0 ? floatval($sale->commission_amount) : ($base * $commPercent / 100);
                    $freightAmt = $sale->freight_amount > 0 ? floatval($sale->freight_amount) : ($base * $freightPercent / 100);
                    $markupAmt = $sale->base_markup_amount > 0 ? floatval($sale->base_markup_amount) : ($base * $markupPercent / 100);

                    if ($isSequential) {
                        $diffAmt = ($base + $commAmt + $freightAmt + $markupAmt) * ($diffPercent / 100);
                    } else {
                        $diffAmt = $sale->exchange_diff_amount > 0 ? floatval($sale->exchange_diff_amount) : ($base * $diffPercent / 100);
                    }

                    // Guard: fix if base stored in local currency
                    if ($base > ($sale->total_usd * 1.5) && $sale->primary_exchange_rate > 1) {
                        $base = $base / $sale->primary_exchange_rate;
                        $commAmt = $commAmt / $sale->primary_exchange_rate;
                        $freightAmt = $freightAmt / $sale->primary_exchange_rate;
                        $markupAmt = $markupAmt / $sale->primary_exchange_rate;
                        $diffAmt = $diffAmt / $sale->primary_exchange_rate;
                    }

                    // Calculate credit
                    $totalPaidUSD = 0;
                    foreach($sale->paymentDetails as $payment) {
                        $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                        $totalPaidUSD += ($payment->amount / $rate);
                    }
                    if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                        $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                        $totalPaidUSD += ($sale->cash / $rate);
                    }
                    $creditUSD = 0;
                    if($sale->status != 'paid' && $sale->status != 'returned') {
                        $creditUSD = max(0, $sale->total_usd - $totalPaidUSD);
                    }

                    $totBase += $base;
                    $totComm += $commAmt;
                    $totFreight += $freightAmt;
                    $totMarkup += $markupAmt;
                    $totDiff += $diffAmt;
                    $totTotal += $sale->total_usd;
                    $totCredit += $creditUSD;
                    $totItems += $sale->items;

                    $folio = $sale->invoice_number ?? ('F' . str_pad($sale->id, 8, '0', STR_PAD_LEFT));
                @endphp
                <tr>
                    @if($columns['folio']) <td>{{ $folio }}</td> @endif
                    @if($columns['cliente']) <td>{{ \Illuminate\Support\Str::limit($sale->customer->name ?? 'N/A', 20) }}</td> @endif
                    @if($columns['operador']) <td>{{ optional($sale->user)->name ?? 'N/A' }}</td> @endif
                    @if($columns['vendedor']) <td>{{ optional(optional($sale->customer)->seller)->name ?? 'N/A' }}</td> @endif
                    @if($columns['base']) <td class="text-right">${{ number_format($base, 2) }}</td> @endif
                    @if($columns['porcentaje']) <td class="text-center">{{ number_format($surchargePercent, 1) }}%</td> @endif
                    @if($columns['comision'])
                    <td class="text-right text-green">
                        ${{ number_format($commAmt, 2) }}
                        @if($commPercent > 0)
                            <br><span class="text-muted">({{ number_format($commPercent, 1) }}%)</span>
                        @endif
                    </td>
                    @endif
                    @if($columns['flete'])
                    <td class="text-right text-blue">
                        ${{ number_format($freightAmt, 2) }}
                        @if($freightPercent > 0)
                            <br><span class="text-muted">({{ number_format($freightPercent, 1) }}%)</span>
                        @endif
                    </td>
                    @endif
                    @if($columns['recargo'])
                    <td class="text-right text-green">
                        ${{ number_format($markupAmt, 2) }}
                        @if($markupPercent > 0)
                            <br><span class="text-muted">({{ number_format($markupPercent, 1) }}%)</span>
                        @endif
                    </td>
                    @endif
                    @if($columns['diferencial'])
                    <td class="text-right text-orange">
                        ${{ number_format($diffAmt, 2) }}
                        @if($diffPercent > 0)
                            <br><span class="text-muted">({{ number_format($diffPercent, 1) }}%)</span>
                        @endif
                    </td>
                    @endif
                    @if($columns['total']) <td class="text-right text-bold">${{ number_format($sale->total_usd, 2) }}</td> @endif
                    @if($columns['credito'])
                    <td class="text-right">
                        @if($creditUSD > 0.01)
                            <span class="text-red">${{ number_format($creditUSD, 2) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    @endif
                    @if($columns['acuerdo']) <td class="text-center">{{ $sale->payment_agreement }}</td> @endif
                    @if($columns['articulos']) <td class="text-center">{{ $sale->items }}</td> @endif
                    @if($columns['estatus'])
                    <td class="text-center">
                        @if($sale->status == 'paid')
                            <span class="badge-paid">paid</span>
                        @elseif($sale->status == 'returned')
                            <span class="badge-returned">returned</span>
                        @else
                            <span class="badge-pending">{{ $sale->status }}</span>
                        @endif
                    </td>
                    @endif
                    @if($columns['tipo']) <td class="text-center">{{ $sale->type }}</td> @endif
                    @if($columns['fecha']) <td class="text-center">{{ $sale->created_at->format('d/m/Y') }}</td> @endif
                </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                @if($columns['folio']) <td></td> @endif
                @if($columns['cliente']) <td class="text-right">TOTALES:</td> @else <td class="text-right">TOTALES:</td> @endif
                @if($columns['operador']) <td></td> @endif
                @if($columns['vendedor']) <td></td> @endif
                @if($columns['base']) <td class="text-right">${{ number_format($totBase, 2) }}</td> @endif
                @if($columns['porcentaje']) <td></td> @endif
                @if($columns['comision']) <td class="text-right text-green">${{ number_format($totComm, 2) }}</td> @endif
                @if($columns['flete']) <td class="text-right text-blue">${{ number_format($totFreight, 2) }}</td> @endif
                @if($columns['recargo']) <td class="text-right text-green">${{ number_format($totMarkup, 2) }}</td> @endif
                @if($columns['diferencial']) <td class="text-right text-orange">${{ number_format($totDiff, 2) }}</td> @endif
                @if($columns['total']) <td class="text-right">${{ number_format($totTotal, 2) }}</td> @endif
                @if($columns['credito']) <td class="text-right text-red">${{ number_format($totCredit, 2) }}</td> @endif
                @if($columns['acuerdo']) <td></td> @endif
                @if($columns['articulos']) <td class="text-center">{{ $totItems }}</td> @endif
                @if($columns['estatus']) <td></td> @endif
                @if($columns['tipo']) <td></td> @endif
                @if($columns['fecha']) <td></td> @endif
            </tr>
        </tfoot>
    </table>

</body>
</html>
