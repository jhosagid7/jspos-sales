<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Fiscal {{ $sale->fiscal_number ?? $sale->invoice_number }}</title>
    <style>
        /* Dimensiones Carta estándar: 215.9mm x 279.4mm */
        @page {
            size: letter portrait;
            margin: 0mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            background: transparent;
        }

        /* Contenedor relativo a página completa */
        .page-container {
            position: relative;
            width: 215.9mm;
            height: 279.4mm;
            box-sizing: border-box;
        }

        /* 1. Bloque de Datos del Cliente y Factura (Y: ~53mm para estar debajo del membrete y RIF) */
        .header-info {
            position: absolute;
            top: 53mm;
            left: 12mm;
            width: 192mm;
        }

        .customer-col {
            position: absolute;
            top: 0;
            left: 0;
            width: 120mm;
            line-height: 1.45;
        }

        .invoice-col {
            position: absolute;
            top: 0;
            left: 128mm;
            width: 64mm;
            line-height: 1.45;
        }

        .info-row {
            margin-bottom: 2px;
            white-space: nowrap;
        }

        .info-label {
            display: inline-block;
            font-size: 10px;
        }

        .info-val {
            font-weight: bold;
            font-size: 11px;
        }

        /* 2. Bloque de Tabla de Ítems (Y: ~76mm hasta ~182mm) */
        .items-container {
            position: absolute;
            top: 76mm;
            left: 10mm;
            width: 195mm;
            height: 106mm;
        }

        .table-items {
            width: 100%;
            border-collapse: collapse;
        }

        .table-items th {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            padding: 4px 2px;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
        }

        .table-items td {
            padding: 3.5px 2px;
            font-size: 10px;
            vertical-align: top;
        }

        .col-cant { width: 12mm; text-align: center; }
        .col-codigo { width: 24mm; text-align: left; }
        .col-desc { width: 85mm; text-align: left; }
        .col-punit { width: 24mm; text-align: center; }
        .col-dscto { width: 14mm; text-align: center; }
        .col-pneto { width: 22mm; text-align: center; }
        .col-iva { width: 14mm; text-align: center; }

        .table-items th.col-cant,
        .table-items td.col-cant,
        .table-items th.col-punit,
        .table-items td.col-punit,
        .table-items th.col-dscto,
        .table-items td.col-dscto,
        .table-items th.col-pneto,
        .table-items td.col-pneto,
        .table-items th.col-iva,
        .table-items td.col-iva {
            text-align: center;
        }

        /* 3. Bloque Inferior: Totales y Pagos (Y: ~184mm hasta ~215mm antes de litografía) */
        .footer-container {
            position: absolute;
            top: 184mm;
            left: 10mm;
            width: 195mm;
        }

        .payments-summary {
            position: absolute;
            top: 0;
            left: 0;
            width: 90mm;
            line-height: 1.4;
            font-size: 10px;
        }

        .tax-summary {
            position: absolute;
            top: 0;
            left: 92mm;
            width: 48mm;
            line-height: 1.4;
            font-size: 10px;
        }

        .totals-summary {
            position: absolute;
            top: 0;
            left: 142mm;
            width: 53mm;
            line-height: 1.4;
            font-size: 10.5px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 1.5px 0;
        }

        /* Frase bancaria sobre la franja litográfica */
        .bank-note {
            position: absolute;
            top: 24mm;
            left: 0;
            width: 140mm;
            font-size: 8.5px;
            color: #222;
        }

        .signature-box {
            position: absolute;
            top: 24mm;
            left: 142mm;
            width: 53mm;
            text-align: center;
            font-size: 9.5px;
        }

        /* Garantía absoluta: Si el usuario presiona imprimir desde la vista previa, NUNCA imprime la imagen */
        @media print {
            .forma-libre-bg-preview {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        {{-- Imagen de fondo membretada (SOLO para visualización en pantalla, NUNCA para imprimir en talonario) --}}
        @if(!empty($showBackground) && !empty($backgroundImage))
            <div class="forma-libre-bg-preview" style="position: absolute; top: 0; left: 0; width: 215.9mm; height: 279.4mm; z-index: -100; overflow: hidden;">
                <img src="{{ $backgroundImage }}" style="width: 215.9mm; height: 279.4mm;" />
            </div>
        @endif
        
        {{-- 1. Encabezado de Datos (calzado con los renglones) --}}
        <div class="header-info">
            {{-- Columna Cliente --}}
            <div class="customer-col">
                <div class="info-row">
                    <span class="info-label" style="width: 48mm;">Nombre/Apellido o Razón Social:</span>
                    <span class="info-val">{{ strtoupper($sale->customer?->name ?? 'CLIENTE GENERAL') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label" style="width: 14mm;">R.I.F.:</span>
                    <span class="info-val" style="margin-right: 15mm;">{{ $sale->customer?->taxpayer_id ?? 'V-00000000-0' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label" style="width: 25mm;">Domicilio Fiscal:</span>
                    <span class="info-val">{{ strtoupper($sale->customer?->address ?? 'EL VIGÍA, EDO. MÉRIDA') }}</span>
                </div>
                <div class="info-row" style="margin-top: 3px;">
                    <span class="info-label" style="width: 18mm;">Teléfonos:</span>
                    <span class="info-val" style="width: 45mm; display: inline-block;">{{ $sale->customer?->phone ?? 'N/D' }}</span>
                    <span class="info-label" style="width: 14mm;">Código:</span>
                    <span class="info-val">{{ $sale->customer?->taxpayer_id ?? $sale->customer_id }}</span>
                </div>
            </div>

            {{-- Columna Factura / Emisión --}}
            <div class="invoice-col">
                <div class="info-row">
                    <span class="info-label" style="width: 24mm;">FACTURA</span>
                    <span class="info-val" style="font-size: 13px; color: #000;">
                        {{ $sale->fiscal_number ? str_pad($sale->fiscal_number, 6, '0', STR_PAD_LEFT) : $sale->invoice_number }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label" style="width: 24mm;">Condición</span>
                    <span class="info-val">{{ strtoupper($sale->type === 'credit' ? 'CRÉDITO' : 'CONTADO') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label" style="width: 24mm;">Emisión:</span>
                    <span class="info-val">{{ $sale->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label" style="width: 24mm;">Vencimiento:</span>
                    <span class="info-val">{{ $sale->credit_days ? $sale->created_at->addDays($sale->credit_days)->format('d/m/Y') : $sale->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label" style="width: 24mm;">Vendedor:</span>
                    <span class="info-val">{{ strtoupper($sale->seller?->name ?? ($sale->user?->name ?? 'JUAN PARRA')) }}</span>
                </div>
            </div>
        </div>

        {{-- 2. Tabla de Ítems --}}
        <div class="items-container">
            <table class="table-items">
                <thead>
                    <tr>
                        <th class="col-cant">Cant.</th>
                        <th class="col-codigo">Código</th>
                        <th class="col-desc">Descripción</th>
                        <th class="col-punit">Precio/Unit</th>
                        <th class="col-dscto">Dscto %Des</th>
                        <th class="col-pneto">Precio/Nto</th>
                        <th class="col-iva">Total %IVA</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subTotalCalculated = 0;
                        $vatPercent = (float)($config?->vat ?? \App\Services\ConfigurationService::getVat() ?? 16);
                        $vatLabel = ((int)$vatPercent == $vatPercent) ? (int)$vatPercent : number_format($vatPercent, 2);
                    @endphp
                    @foreach($sale->details as $item)
                        @php
                            $qty = (float)$item->quantity;
                            $unitPrice = (float)$item->sale_price;
                            $lineTotal = $qty * $unitPrice;
                            $subTotalCalculated += $lineTotal;
                            $skuCode = $item->product?->sku ?? ($item->product?->id ? 'PRD-'.$item->product->id : '');
                            $description = $item->custom_name ?: ($item->product?->name ?? 'REPUESTO / SERVICIO');
                        @endphp
                        <tr>
                            <td class="col-cant">{{ number_format($qty, 0, ',', '.') }}</td>
                            <td class="col-codigo">{{ $skuCode }}</td>
                            <td class="col-desc">
                                {{ strtoupper($description) }}
                                @if($loop->first && $sale->workOrder && $sale->workOrder->vehicle)
                                    <div style="font-size: 8px; color: #444;">
                                        [VEHÍCULO: {{ $sale->workOrder->vehicle->plate }} {{ $sale->workOrder->vehicle->brand }} {{ $sale->workOrder->vehicle->model }} | KM: {{ number_format($sale->workOrder->entry_mileage) }}]
                                    </div>
                                @endif
                            </td>
                            <td class="col-punit">{{ number_format($unitPrice, 2, ',', '.') }}</td>
                            <td class="col-dscto">0,00 &nbsp; 0,00</td>
                            <td class="col-pneto">{{ number_format($unitPrice, 2, ',', '.') }}</td>
                            <td class="col-iva">{{ number_format($lineTotal, 2, ',', '.') }} &nbsp; {{ $vatLabel }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 3. Totales y Pie de Factura --}}
        <div class="footer-container">
            @php
                $vatRate = $vatPercent / 100;
                $baseTaxable = $subTotalCalculated;
                $ivaAmount = round($baseTaxable * $vatRate, 2);
                $totalNeto = $baseTaxable + $ivaAmount;
                $isPaid = ($sale->status === 'paid' || $sale->type === 'cash');
                $cancelado = $isPaid ? $totalNeto : (float)($sale->cash ?? 0);
                $pendiente = max(0, $totalNeto - $cancelado);
            @endphp

            {{-- Bloque Cancelado / Pagos --}}
            <div class="payments-summary">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 25mm;">Cancelado:</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($cancelado, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Pendiente:</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($pendiente, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Efe: {{ number_format($cancelado, 2, ',', '.') }}</td>
                        <td>Cheq: 0,00</td>
                    </tr>
                    @if($sale->notes)
                        <tr>
                            <td colspan="2" style="font-size: 8px; color: #555;">Notas: {{ $sale->notes }}</td>
                        </tr>
                    @endif
                </table>
            </div>

            {{-- Bloque Base e IVA --}}
            <div class="tax-summary">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 18mm;">Exento:</td>
                        <td style="text-align: right;">0,00</td>
                    </tr>
                    <tr>
                        <td>Base {{ $vatLabel }} %</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($baseTaxable, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            {{-- Bloque Totales --}}
            <div class="totals-summary">
                <table class="totals-table">
                    <tr>
                        <td style="text-align: left;">Sub-Total:</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($baseTaxable, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Descuentos: 0%</td>
                        <td style="text-align: right;">0,00</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">IVA {{ $vatLabel }} %</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($ivaAmount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-size: 11px; font-weight: bold; border-top: 1px solid #000; padding-top: 2px;">Total Neto:</td>
                        <td style="text-align: right; font-size: 11px; font-weight: bold; border-top: 1px solid #000; padding-top: 2px;">{{ number_format($totalNeto, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            {{-- Texto de cuenta bancaria y Recibí Conforme --}}
            <div class="bank-note">
                Favor transferir a: CTA CTE. 01080392610100131342 Bco.Provincial a nombre de JUAN BAUTISTA PARRA MARQUEZ V11218958
            </div>
            <div class="signature-box">
                Recibí conforme: ____________________
            </div>
        </div>

    </div>
</body>
</html>
