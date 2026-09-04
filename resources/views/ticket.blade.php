<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $prod->qr_code }}</title>
    <style>
        @page { margin: 0; size: 80mm auto; }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 72mm;
            margin: 0 auto;
            padding: 10px;
            color: #000;
            background: #fff;
            text-align: center;
        }
        .header { font-size: 16px; font-weight: bold; }
        .subheader { font-size: 11px; margin-bottom: 8px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .info-table { width: 100%; font-size: 12px; text-align: left; }
        .info-table td { padding: 2px 0; }
        .bold { font-weight: bold; }
        .qr-placeholder { display: inline-block; padding: 6px; border: 2px solid #000; font-size: 14px; font-weight: bold; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">JSBOLSAS PRO</div>
    <div class="subheader">PLÁSTICOS M&F STEEL</div>
    <div class="divider"></div>

    <div style="margin: 10px 0;">
        <div class="qr-placeholder">[ QR: {{ $prod->qr_code }} ]</div>
    </div>

    <table class="info-table">
        <tr><td class="bold">CÓDIGO:</td><td style="text-align: right;">{{ $prod->qr_code }}</td></tr>
        <tr><td class="bold">PRODUCTO:</td><td style="text-align: right;">{{ $prod->product->name ?? 'Bolsa' }}</td></tr>
        <tr><td class="bold">OPERARIO:</td><td style="text-align: right;">{{ $prod->user->name ?? 'Operario' }}</td></tr>
        <tr><td class="bold">TURNO:</td><td style="text-align: right;">{{ strtoupper($prod->shift->shift_type ?? 'DIURNO') }}</td></tr>
        <tr><td class="bold">BULTOS:</td><td style="text-align: right;">{{ number_format($prod->quantity, 0) }}</td></tr>
        <tr><td class="bold">PESO:</td><td style="text-align: right; font-weight: bold; font-size: 14px;">{{ number_format($prod->weight, 2) }} Kg</td></tr>
        <tr><td class="bold">FECHA:</td><td style="text-align: right;">{{ $prod->recorded_at ? $prod->recorded_at->format('d/m/Y h:i A') : '-' }}</td></tr>
        <tr><td class="bold">AUDITADO:</td><td style="text-align: right;">{{ $prod->reviewer->name ?? 'Supervisor' }}</td></tr>
    </table>

    <div class="divider"></div>
    <div style="font-size: 10px;">STOCK PRE-LEVANTAMIENTO JSBOLSAS</div>

    <div class="no-print" style="margin-top: 15px;">
        <button onclick="window.print();" style="padding: 8px 16px; background: #000; color: #fff; border: none; border-radius: 6px; cursor: pointer;">🖨️ IMPRIMIR</button>
    </div>
</body>
</html>
