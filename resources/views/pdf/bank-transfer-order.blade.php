<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172554; font-size: 11px; }
        h1 { margin: 0; font-size: 22px; color: #1e3a8a; }
        .header { border-bottom: 3px solid #f59e0b; padding-bottom: 12px; margin-bottom: 18px; }
        .code { float: right; font-size: 15px; font-weight: bold; color: #111827; }
        .box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px; margin-bottom: 14px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { width: 50%; padding: 4px 8px 4px 0; vertical-align: top; }
        .label { color: #64748b; font-size: 9px; text-transform: uppercase; }
        .value { color: #111827; font-size: 12px; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .items th { background: #1e3a8a; color: white; text-align: left; padding: 7px; }
        .items td { border: 1px solid #cbd5e1; padding: 7px; }
        .right { text-align: right; }
        .total { font-size: 16px; font-weight: bold; color: #15803d; }
        .notice { padding: 10px; background: #fff7ed; border-left: 4px solid #f59e0b; color: #7c2d12; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #cbd5e1; padding-top: 6px; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <span class="code">{{ $order->code }}</span>
        <h1>Orden de transferencia bancaria</h1>
        <div>Federación Argentina de Billar</div>
    </div>

    <div class="box">
        <table class="grid">
            <tr>
                <td><div class="label">Titular</div><div class="value">{{ $setting?->account_holder }}</div></td>
                <td><div class="label">CUIT</div><div class="value">{{ $setting?->tax_id }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Banco</div><div class="value">{{ $setting?->bank_name }}</div></td>
                <td><div class="label">Tipo y número de cuenta</div><div class="value">{{ trim(($setting?->account_type ?? '').' '.($setting?->account_number ?? '')) ?: '—' }}</div></td>
            </tr>
            <tr>
                <td><div class="label">CBU</div><div class="value">{{ $setting?->cbu }}</div></td>
                <td><div class="label">Alias</div><div class="value">{{ $setting?->alias ?: '—' }}</div></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <table class="grid">
            <tr>
                <td><div class="label">Responsable</div><div class="value">{{ $order->user?->name ?? '—' }}</div></td>
                <td><div class="label">Club</div><div class="value">{{ $order->club?->name ?? 'INDEPENDIENTE' }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Disciplina</div><div class="value">{{ $order->discipline?->name ?? '—' }}</div></td>
                <td><div class="label">Vencimiento</div><div class="value">{{ $order->expires_at?->format('d/m/Y') ?? '—' }}</div></td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead><tr><th>Concepto</th><th class="right">Importe</th></tr></thead>
        <tbody>
        @foreach($order->items as $item)
            <tr><td>{{ $item->description }}</td><td class="right">$ {{ number_format((float) $item->amount, 2, ',', '.') }}</td></tr>
        @endforeach
        <tr><td class="right"><strong>Total a transferir</strong></td><td class="right total">$ {{ number_format((float) $order->amount, 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <p class="notice">Transferí el importe exacto y utilizá <strong>{{ $order->code }}</strong> como referencia. La presentación del comprobante no acredita el pago: Tesorería debe verificar el ingreso en Banco Macro.</p>
    @if($setting?->instructions)<p>{{ $setting->instructions }}</p>@endif

    <div class="footer">Sistema Administrativo Federación Argentina de Billar · Orden emitida el {{ $order->created_at?->format('d/m/Y H:i') }}</div>
</body>
</html>
