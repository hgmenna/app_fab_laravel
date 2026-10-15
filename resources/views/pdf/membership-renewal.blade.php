<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24mm 14mm 27mm; }
        body { font-family: DejaVu Sans, sans-serif; color:#26334a; font-size:10px; }
        .header { width:100%; border-bottom:2px solid #173f8f; padding-bottom:10px; margin-bottom:18px; }
        .header td { vertical-align:middle; }
        .logo { width:58px; max-height:68px; }
        h1 { margin:0; color:#173f8f; font-size:20px; text-align:center; }
        h2 { margin:5px 0 0; font-size:12px; text-align:center; font-weight:normal; }
        .meta { width:100%; margin-bottom:16px; border-collapse:collapse; }
        .meta td { padding:5px 7px; border:1px solid #d6deea; }
        .meta .label { width:20%; font-weight:bold; background:#edf3fb; }
        table.detail { width:100%; border-collapse:collapse; }
        .detail th { padding:7px 5px; background:#173f8f; color:#fff; border:1px solid #31599d; font-size:9px; }
        .detail td { padding:7px 5px; border:1px solid #cfd8e5; }
        .detail tr:nth-child(even) td { background:#f5f8fc; }
        .center { text-align:center; }
        .right { text-align:right; }
        .footer { position:fixed; left:0; right:0; bottom:-19mm; text-align:center; border-top:1px solid #173f8f; padding-top:5px; }
        .footer img { width:70%; max-height:38px; object-fit:contain; }
        .footer-text { margin-top:3px; font-size:7px; color:#526178; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td style="width:15%">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td>
        <td style="width:70%"><h1>COMPROBANTE DE MEMBRESÍAS</h1><h2>{{ $membership->discipline?->name }} · Año {{ $membership->year }}</h2></td>
        <td style="width:15%;text-align:right">{{ $generatedAt->format('d/m/Y') }}</td>
    </tr>
</table>

<table class="meta">
    <tr><td class="label">Generado por</td><td>{{ $generatedBy->name }} · {{ $generatedBy->email }}</td><td class="label">Fecha y hora</td><td>{{ $generatedAt->format('d/m/Y H:i') }}</td></tr>
    <tr><td class="label">Disciplina</td><td>{{ $membership->discipline?->name }}</td><td class="label">Cierre</td><td>{{ $membership->due_date?->format('d/m/Y') }}</td></tr>
    <tr><td class="label">Importe individual</td><td>$ {{ number_format((float)$membership->amount, 2, ',', '.') }}</td><td class="label">Total</td><td>$ {{ number_format((float)$membership->amount * $renewals->count(), 2, ',', '.') }}</td></tr>
</table>

<table class="detail">
    <thead><tr><th style="width:5%">N°</th><th style="width:26%">APELLIDO Y NOMBRE</th><th style="width:25%">CLUB / SALA</th><th style="width:14%">OPERACIÓN</th><th style="width:14%">ESTADO</th><th style="width:16%">IMPORTE</th></tr></thead>
    <tbody>
    @foreach($renewals as $renewal)
        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td>{{ $renewal->player?->full_name }}</td>
            <td>{{ $renewal->club?->name ?? 'INDEPENDIENTE' }}</td>
            <td class="center">{{ $renewal->operation_label ?? 'Renovación' }}</td>
            <td class="center">HABILITADO</td>
            <td class="right">$ {{ number_format((float)$renewal->amount_paid, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    @if($footerImage)<img src="{{ $footerImage }}">@endif
    <div class="footer-text">{{ $systemName }} · Versión {{ $systemVersion }}</div>
</div>
</body>
</html>
