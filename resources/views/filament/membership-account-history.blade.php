<div class="space-y-4">
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-800"><div class="text-xs text-gray-500">Membresía</div><strong>$ {{ number_format((float) $record->amount_due, 2, ',', '.') }}</strong></div>
        <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-800"><div class="text-xs text-gray-500">Abonado</div><strong>$ {{ number_format((float) $record->amount_paid, 2, ',', '.') }}</strong></div>
        <div class="rounded-lg bg-gray-100 p-3 dark:bg-gray-800"><div class="text-xs text-gray-500">Saldo</div><strong>$ {{ number_format($record->balance, 2, ',', '.') }}</strong></div>
    </div>
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800"><tr><th class="p-2 text-left">Fecha</th><th class="p-2 text-left">Pagador</th><th class="p-2 text-left">Medio</th><th class="p-2 text-left">Referencia</th><th class="p-2 text-left">Registrado por</th><th class="p-2 text-right">Importe</th></tr></thead>
            <tbody>
            @forelse($record->payments->sortByDesc('created_at') as $payment)
                <tr class="border-t border-gray-200 dark:border-gray-700">
                    <td class="p-2">{{ ($payment->paid_at ?? $payment->created_at)?->format('d/m/Y H:i') }}</td>
                    <td class="p-2">{{ $payment->payer?->name ?? $payment->payer?->full_name ?? 'Jugador' }}</td>
                    <td class="p-2">{{ match($payment->manual_method) { 'cash' => 'Efectivo', 'bank_transfer' => 'Transferencia', 'other' => 'Otro medio', default => ucfirst($payment->method) } }}</td>
                    <td class="p-2">{{ $payment->external_reference ?: '—' }}</td>
                    <td class="p-2">{{ $payment->recorder?->name ?: 'Sistema' }}</td>
                    <td class="p-2 text-right font-semibold">$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                </tr>
                @if($payment->notes)<tr><td colspan="6" class="px-2 pb-2 text-xs text-gray-500">{{ $payment->notes }}</td></tr>@endif
            @empty
                <tr><td colspan="6" class="p-4 text-center text-gray-500">Todavía no hay pagos registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
