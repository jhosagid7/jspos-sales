<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Descargo #{{ $adjustment->id }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .business-name { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .document-title { font-size: 14px; font-weight: bold; background: #eee; padding: 5px; margin-top: 5px; }
        .info-section { width: 100%; margin-bottom: 20px; }
        .info-col { width: 50%; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th { background: #3b3f5c; color: white; padding: 8px; font-size: 11px; text-transform: uppercase; }
        .table td { border: 1px solid #ddd; padding: 6px; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-badge { padding: 4px 8px; border-radius: 4px; color: white; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .status-pending { background: #e2a03f; }
        .status-approved { background: #8dbf42; }
        .status-rejected { background: #e7515a; }
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 9px; color: #777; }
        .signature-table { width: 100%; margin-top: 50px; }
        .signature-box { border-top: 1px solid #333; width: 200px; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="business-name">{{ $config->business_name }}</div>
        <div>{{ $config->taxpayer_id }} | {{ $config->address }}</div>
        <div>Tel: {{ $config->phone }} | Email: {{ $config->email }}</div>
        <div class="document-title">DESCARGO DE INVENTARIO (AJUSTE DE SALIDA) #{{ str_pad($adjustment->id, 6, '0', STR_PAD_LEFT) }}</div>
    </div>

    <table class="info-section">
        <tr>
            <td class="info-col">
                <strong>DEPÓSITO ORIGEN:</strong> {{ $adjustment->warehouse->name }}<br>
                <strong>FECHA REGISTRO:</strong> {{ $adjustment->date->format('d/m/Y H:i') }}<br>
                <strong>MOTIVO AJUSTE:</strong> {{ $adjustment->motive }}
            </td>
            <td class="info-col text-right">
                <strong>RESPONSABLE:</strong> {{ $adjustment->user->name }}<br>
                <strong>AUTORIZADO POR:</strong> {{ $adjustment->authorized_by }}<br>
                <strong>ESTADO:</strong> 
                <span class="status-badge status-{{ $adjustment->status }}">
                    {{ $adjustment->status == 'pending' ? 'Pendiente' : ($adjustment->status == 'approved' ? 'Aprobado' : 'Rechazado') }}
                </span>
            </td>
        </tr>
    </table>

    @if($adjustment->comments)
    <div style="margin-bottom: 15px; background: #f9f9f9; padding: 8px; border-left: 3px solid #ccc;">
        <strong>Comentarios:</strong> {{ $adjustment->comments }}
    </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th class="text-center">Cantidad</th>
                <th>Detalles (Items)</th>
                <th class="text-right">Costo Unit.</th>
                <th class="text-right">Total Costo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($adjustment->details as $item)
            <tr>
                <td class="text-center">{{ $item->product->sku }}</td>
                <td>{{ $item->product->name }}</td>
                <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                <td>
                    @if($item->items_json)
                        @php $itemsArr = json_decode($item->items_json, true); @endphp
                        @foreach($itemsArr as $idx => $v)
                            <small>{{ $v['weight'] }}kg {{ $v['color'] ? '| '.$v['color'] : '' }}{{ $idx < count($itemsArr)-1 ? ',' : '' }}</small>
                        @endforeach
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">${{ number_format($item->cost, 2) }}</td>
                <td class="text-right">${{ number_format($item->quantity * $item->cost, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f2f2f2; font-weight: bold;">
                <td colspan="5" class="text-right">VALOR TOTAL DEL DESCARGO:</td>
                <td class="text-right">${{ number_format($adjustment->details->sum(function($d){ return $d->quantity * $d->cost; }), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-table">
        <tr>
            <td class="text-center">
                <div class="signature-box" style="margin-left: 50px;">
                    Solicitado / Responsable<br>
                    <small>{{ $adjustment->user->name }}</small>
                </div>
            </td>
            <td class="text-center">
                <div class="signature-box" style="margin-left: auto; margin-right: 50px;">
                    Aprobado / Supervisor<br>
                    <small>{{ $adjustment->approver->name ?? '____________________' }}</small>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer text-center">
        Comprobante interno de ajuste de inventario (Descargo). 
        Generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
