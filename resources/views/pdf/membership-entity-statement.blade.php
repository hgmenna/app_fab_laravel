<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 12mm 23mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #24344d; font-size: 8px; }
        .header { width: 100%; border-bottom: 3px solid #173f8f; margin-bottom: 10px; padding-bottom: 8px; }
        .logo { width: 52px; max-height: 58px; }
        h1 { margin: 0; text-align: center; color: #173f8f; font-size: 18px; }
        .subtitle { margin-top: 3px; text-align: center; font-size: 10px; color: #475569; }
        .meta, .accounts, .movements { width: 100%; border-collapse: collapse; }
        .meta td { width: 25%; border: 1px solid #d4ddea; padding: 6px; }
        .label { font-size: 7px; color: #64748b; text-transform: uppercase; }
        .value { margin-top: 2px; font-size: 9px; font-weight: bold; }
        .summary { width: 100%; margin: 8px 0; border-collapse: separate; border-spacing: 5px; }
        .summary td { background: #edf3fb; padding: 7px; text-align: center; border-radius: 4px; }
        .summary strong { display: block; margin-top: 2px; font-size: 12px; color: #173f8f; }
        .accounts th { background: #173f8f; color: white; padding: 5px; text-transform: uppercase; }
        .accounts td { border: 1px solid #d4ddea; padding: 5px; vertical-align: top; }
        .accounts tr:nth-child(even) > td { background: #f8fafc; }
        .right { text-align: right; }
        .center { text-align: center; }
        .status { font-weight: bold; }
        .paid { color: #15803d; }
        .partial { color: #b45309; }
        .pending, .expired { color: #b91c1c; }
        .movement-cell { padding: 0 !important; background: #f8fafc !important; }
        .movements { margin: 3px 0 6px; }
        .movements th { background: #dbe7f7; color: #173f8f; padding: 3px 5px; }
        .movements td { border: 1px solid #d4ddea; padding: 3px 5px; background: white !important; }
        .footer { position: fixed; bottom: -17mm; left: 0; right: 0; text-align: center; border-top: 1px solid #173f8f; padding-top: 4px; }
        .footer img { width: 70%; max-height: 34px; object-fit: contain; }
        .small { font-size: 6px; color: #64748b; }
    </style>
</head>
<body>
@php
    $totalDue = $accounts->sum(fn ($account) => (float) $account->amount_due);
    $totalPaid = $accounts->sum(fn ($account) => (float) $account->amount_paid);
    $totalBalance = $accounts->sum(fn ($account) => $account->balance);
@endphp
<table class="header">
    <tr>
        <td style="width: 14%">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td>
        <td>
            <h1>ESTADO DE CUENTA DE MEMBRESÍAS</h1>
            <div class="subtitle">{{ $entity->name }} · {{ $discipline->name }} · {{ $year }}</div>
        </td>
        <td style="width: 14%; text-align: right">{{ now()->format('d/m/Y') }}</td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td><div class="label">Tipo de entidad</div><div class="value">{{ $entityType === 'club' ? 'Club / sala' : 'Federación' }}</div></td>
        <td><div class="label">Entidad</div><div class="value">{{ $entity->name }}</div></td>
        <td><div class="label">Disciplina</div><div class="value">{{ $discipline->name }}</div></td>
        <td><div class="label">Período</div><div class="value">{{ $year }}</div></td>
    </tr>
</table>

<table class="summary">
    <tr>
        <td>Jugadores<strong>{{ $accounts->count() }}</strong></td>
        <td>Membresías<strong>$ {{ number_format($totalDue, 2, ',', '.') }}</strong></td>
        <td>Total abonado<strong>$ {{ number_format($totalPaid, 2, ',', '.') }}</strong></td>
        <td>Saldo pendiente<strong>$ {{ number_format($totalBalance, 2, ',', '.') }}</strong></td>
        <td>Pagadas / parciales / pendientes<strong>{{ $accounts->where('status', 'paid')->count() }} / {{ $accounts->where('status', 'partial')->count() }} / {{ $accounts->whereIn('status', ['pending', 'expired'])->count() }}</strong></td>
    </tr>
</table>

<table class="accounts">
    <thead>
        <tr><th>Jugador</th><th>Club / sala</th><th class="right">Membresía</th><th class="right">Abonado</th><th class="right">Saldo</th><th>Estado</th></tr>
    </thead>
    <tbody>
    @forelse($accounts as $account)
        <tr>
            <td><strong>{{ $account->player?->full_name }}</strong></td>
            <td>{{ $account->club?->name ?? 'INDEPENDIENTE' }}</td>
            <td class="right">$ {{ number_format((float) $account->amount_due, 2, ',', '.') }}</td>
            <td class="right">$ {{ number_format((float) $account->amount_paid, 2, ',', '.') }}</td>
            <td class="right"><strong>$ {{ number_format($account->balance, 2, ',', '.') }}</strong></td>
            <td class="status {{ $account->status }}">{{ match($account->status) {'paid' => 'PAGADA', 'partial' => 'PAGO PARCIAL', 'expired' => 'VENCIDA', default => 'PENDIENTE'} }}</td>
        </tr>
        @if($account->payments->isNotEmpty())
        <tr>
            <td colspan="6" class="movement-cell">
                <table class="movements">
                    <thead><tr><th>Fecha</th><th>Pagador</th><th>Medio</th><th>Referencia</th><th>Observación</th><th class="right">Importe</th></tr></thead>
                    <tbody>
                    @foreach($account->payments->sortBy('created_at') as $payment)
                        <tr>
                            <td>{{ ($payment->paid_at ?? $payment->created_at)?->format('d/m/Y H:i') }}</td>
                            <td>{{ $payment->payer?->name ?? $payment->payer?->full_name ?? 'Jugador' }}</td>
                            <td>{{ match($payment->manual_method) {'cash' => 'Efectivo', 'bank_transfer' => 'Transferencia', 'other' => 'Otro medio', default => ucfirst($payment->method)} }}</td>
                            <td>{{ $payment->external_reference ?: '—' }}</td>
                            <td>{{ $payment->notes ?: '—' }}</td>
                            <td class="right">$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
        @endif
    @empty
        <tr><td colspan="6" class="center">No se encontraron membresías para los filtros seleccionados.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    @if($footerImage)<img src="{{ $footerImage }}">@endif
    <div class="small">Sistema Administrativo Federación Argentina de Billar · Versión {{ config('app.version') }}</div>
</div>
</body>
</html>
