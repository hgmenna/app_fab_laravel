<div class="space-y-4">
    @php
        $histories = $record->categoryHistories()
            ->with(['previousCategory', 'category'])
            ->orderByDesc('created_at')
            ->get();
    @endphp

    @if ($histories->isEmpty())
        <div class="text-sm text-gray-500">
            No se encontraron registros de cambios de categoría.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="px-3 py-2 text-left">Fecha carga</th>
                        <th class="px-3 py-2 text-left">Fecha efectiva</th>
                        <th class="px-3 py-2 text-left">Anterior</th>
                        <th class="px-3 py-2 text-left">Nueva</th>
                        <th class="px-3 py-2 text-left">Tipo</th>
                        <th class="px-3 py-2 text-left">Estado</th>
                        <th class="px-3 py-2 text-left">Motivo</th>
                        <th class="px-3 py-2 text-left">Observaciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($histories as $history)
                        @php
                            $status = $history->source === 'manual'
                                ? ($history->applied_at ? 'Aplicado' : 'Pendiente')
                                : 'Registrado';

                            $type = match ($history->change_type) {
                                'affiliation' => 'Afiliación',
                                'ranking' => 'Ranking',
                                'tournament' => 'Torneo',
                                default => $history->change_type ?? '-',
                            };
                        @endphp

                        <tr class="border-b">
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ $history->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>

                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ $history->effective_date?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $history->previousCategory?->name ?? '-' }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $history->category?->name ?? '-' }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $type }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $status }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $history->reason ?? '-' }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $history->notes ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>