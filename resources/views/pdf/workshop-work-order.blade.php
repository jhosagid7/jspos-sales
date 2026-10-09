<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Trabajo {{ $order->order_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #222; margin: 15px; }
        .header { border-bottom: 2px solid #2d3748; padding-bottom: 10px; margin-bottom: 15px; }
        .company-name { font-size: 18px; font-weight: bold; color: #1a202c; text-transform: uppercase; }
        .doc-title { font-size: 16px; font-weight: bold; color: #4f46e5; text-align: right; }
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table-info td { padding: 4px 6px; vertical-align: top; }
        .section-title { background: #edf2f7; font-weight: bold; padding: 5px 8px; font-size: 12px; margin-top: 10px; margin-bottom: 6px; border-left: 3px solid #4f46e5; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .items-table th { background: #2d3748; color: white; padding: 6px; text-align: left; font-size: 10px; }
        .items-table td { border-bottom: 1px solid #e2e8f0; padding: 6px; font-size: 10px; }
        .totals-table { width: 40%; margin-left: auto; border-collapse: collapse; margin-top: 10px; }
        .totals-table td { padding: 4px 6px; text-align: right; }
        .signatures { margin-top: 45px; width: 100%; text-align: center; }
        .signature-line { border-top: 1px solid #718096; width: 200px; display: inline-block; margin: 0 40px; padding-top: 5px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%;">
                    <div class="company-name">{{ $config?->business_name ?? 'TALLER AUTOMOTRIZ' }}</div>
                    <div>RIF: {{ $config?->taxpayer_id ?? 'J-00000000' }}</div>
                    <div>Telf: {{ $config?->phone ?? 'N/A' }} | {{ $config?->address ?? '' }}</div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="doc-title">{{ $type === 'reception' ? 'FICHA DE RECEPCIÓN' : 'ORDEN DE LIQUIDACIÓN' }}</div>
                    <div style="font-size: 14px; font-weight: bold; margin-top: 4px;">{{ $order->order_number }}</div>
                    <div style="color: #718096; font-size: 10px;">Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">DATOS DEL CLIENTE Y VEHÍCULO</div>
    <table class="table-info">
        <tr>
            <td style="width: 15%; font-weight: bold;">Cliente:</td>
            <td style="width: 35%;">{{ $order->customer?->name }}</td>
            <td style="width: 15%; font-weight: bold;">Placa:</td>
            <td style="width: 35%; font-weight: bold; font-size: 13px;">{{ $order->vehicle?->plate }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">CI / RIF:</td>
            <td>{{ $order->customer?->taxpayer_id }}</td>
            <td style="font-weight: bold;">Vehículo:</td>
            <td>{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }} ({{ $order->vehicle?->year }})</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Teléfono:</td>
            <td>{{ $order->customer?->phone }}</td>
            <td style="font-weight: bold;">Km Entrada:</td>
            <td>{{ number_format($order->entry_mileage) }} Km</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Asesor:</td>
            <td>{{ $order->user?->name }}</td>
            <td style="font-weight: bold;">Combustible:</td>
            <td>{{ $order->fuel_level }}</td>
        </tr>
    </table>

    <div class="section-title">MOTIVO DE INGRESO / DIAGNÓSTICO PRELIMINAR</div>
    <div style="padding: 6px 8px; background: #f7fafc; border: 1px solid #edf2f7; min-height: 40px; margin-bottom: 10px;">
        {{ $order->initial_diagnosis }}
    </div>

    @if($order->items->isNotEmpty())
        <div class="section-title">DETALLE DE SERVICIOS Y REPUESTOS</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Tipo</th>
                    <th style="width: 50%;">Descripción</th>
                    <th style="width: 10%; text-align: center;">Cant.</th>
                    <th style="width: 15%; text-align: right;">P. Unit ($)</th>
                    <th style="width: 15%; text-align: right;">Subtotal ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->type === 'part' ? 'Repuesto' : 'Mano Obra' }}</td>
                        <td>{{ $item->description }}</td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align: right;">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td style="color: #4a5568;">Subtotal Repuestos:</td>
                <td style="font-weight: bold;">${{ number_format($order->subtotal_parts, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #4a5568;">Subtotal Mano de Obra:</td>
                <td style="font-weight: bold;">${{ number_format($order->subtotal_labor, 2) }}</td>
            </tr>
            <tr style="border-top: 1px solid #cbd5e0; font-size: 13px;">
                <td style="font-weight: bold;">TOTAL:</td>
                <td style="font-weight: bold; color: #4f46e5;">${{ number_format($order->total, 2) }}</td>
            </tr>
        </table>
    @endif

    <div class="signatures">
        <div class="signature-line">
            Firma Asesor / Mecánico
        </div>
        <div class="signature-line">
            Firma Cliente Conforme
        </div>
    </div>
</body>
</html>
