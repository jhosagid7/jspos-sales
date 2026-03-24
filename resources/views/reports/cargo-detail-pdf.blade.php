<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Cargo #{{ $cargo->id }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .business-info h2 {
            margin: 0;
            color: #2c3e50;
        }
        .report-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
            color: #34495e;
        }
        .info-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-box {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        .table td {
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            color: white;
            font-weight: bold;
        }
        .bg-pending { background-color: #f1c40f; color: #000; }
        .bg-approved { background-color: #27ae60; }
        .bg-rejected { background-color: #e74c3c; }
        .bg-voided { background-color: #7f8c8d; }
        
        .footer {
            margin-top: 50px;
            width: 100%;
        }
        .signature-box {
            width: 45%;
            display: inline-block;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto 5px auto;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td width="60%">
                <div class="business-info">
                    <h2>{{ $config->business_name ?? 'SISTEMA POS' }}</h2>
                    <p>{{ $config->address ?? '' }}<br>
                    TEL: {{ $config->phone ?? '' }} | RIF: {{ $config->taxpayer_id ?? '' }}</p>
                </div>
            </td>
            <td width="40%" class="text-right">
                <div style="font-size: 10pt; font-weight: bold;">CARGO #{{ str_pad($cargo->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div>Fecha Registro: {{ $cargo->date->format('d/m/Y') }}</div>
                <div>Emisor: {{ strtoupper($cargo->user->name) }}</div>
            </td>
        </tr>
    </table>

    <div class="report-title">Comprobante de Ajuste de Inventario (Cargo)</div>

    <div class="info-section">
        <div class="info-box">
            <strong>MOTIVO:</strong> {{ strtoupper($cargo->motive) }}<br>
            <strong>ALMACÉN:</strong> {{ strtoupper($cargo->warehouse->name) }}<br>
            <strong>AUTORIZADO POR:</strong> {{ strtoupper($cargo->authorized_by ?? 'N/A') }}
        </div>
        <div class="info-box text-right">
            <strong>ESTADO ACTUAL:</strong> 
            <span class="status-badge bg-{{ $cargo->status }}">
                {{ strtoupper($cargo->status == 'pending' ? 'Pendiente' : ($cargo->status == 'approved' ? 'Aprobado' : ($cargo->status == 'rejected' ? 'Rechazado' : 'Anulado'))) }}
            </span>
        </div>
    </div>

    @if($cargo->comments)
        <div style="margin-bottom: 20px; padding: 10px; border: 1px solid #eee; background: #fff;">
            <strong>OBSERVACIONES:</strong><br>
            {{ $cargo->comments }}
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th width="10%">ID</th>
                <th>Producto / Descripción</th>
                <th width="15%" class="text-center">Cant.</th>
                @if(auth()->user()->can('inventory.view_costs'))
                <th width="15%" class="text-right">Costo Unit.</th>
                <th width="15%" class="text-right">Subtotal</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($cargo->details as $item)
                @php $subtotal = $item->quantity * $item->cost; $total += $subtotal; @endphp
                <tr>
                    <td class="text-center">{{ $item->product_id }}</td>
                    <td>
                        {{ $item->product->name }}
                        @if($item->items_json)
                            <br><small style="color: #666;">(Incluye detalles de productos variables)</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    @if(auth()->user()->can('inventory.view_costs'))
                    <td class="text-right">${{ number_format($item->cost, 2) }}</td>
                    <td class="text-right">${{ number_format($subtotal, 2) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        @if(auth()->user()->can('inventory.view_costs'))
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>TOTAL COSTO VALORADO:</strong></td>
                <td class="text-right"><strong>${{ number_format($total, 2) }}</strong></td>
            </tr>
        </tfoot>
        @endif
    </table>

    @if($cargo->status == 'rejected' && $cargo->rejection_reason)
        <div style="border: 1px solid #e74c3c; padding: 10px; margin-bottom: 20px;">
            <strong style="color: #e74c3c;">MOTIVO DE RECHAZO:</strong><br>
            {{ $cargo->rejection_reason }}
            <br><small>Rechazado por: {{ $cargo->rejecter->name ?? 'N/A' }} el {{ $cargo->rejection_date ? $cargo->rejection_date->format('d/m/Y H:i') : '' }}</small>
        </div>
    @endif

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line"></div>
            SOLICITANTE<br>
            {{ strtoupper($cargo->user->name) }}
        </div>
        <div class="signature-box" style="margin-left: 10%;">
            <div class="signature-line"></div>
            AUTORIZADO / SUPERVISIÓN<br>
            {{ strtoupper($cargo->authorized_by ?? 'FIRMA') }}
        </div>
    </div>

    <div style="margin-top: 30px; font-size: 7pt; color: #999; text-align: center;">
        Este documento es un comprobante interno de movimiento de inventario. Generado automáticamente por {{ $config->business_name }}.
    </div>
</body>
</html>
