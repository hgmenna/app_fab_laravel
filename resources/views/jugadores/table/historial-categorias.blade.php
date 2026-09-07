<div>
    @php
        $histories = $record->categoryHistories()
            ->with(['previousCategory', 'category'])
            ->orderByDesc('created_at')
            ->get();
    @endphp

    @if ($histories->isEmpty())
        <p style="color: #9ca3af; margin: 0;">
            No se encontraron registros de cambios de categoría.
        </p>
    @else
        <div style="width: 100%; overflow-x: auto;">
            <table style="
                width: 100%;
                min-width: 1050px;
                border-collapse: collapse;
                font-size: 14px;
            ">
                <thead>
                    <tr>
                        <th style="padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid #4b5563;">
                            Fecha carga
                        </th>
                        <th style="padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid #4b5563;">
                            Fecha efectiva
                        </th>
                        <th style="padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid #4b5563;">
                            Anterior
                        </th>
                        <th style="padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid #4b5563;">
                            Nueva
                        </th>
                        <th style="padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid #4b5563;">
                            Tipo
                        </th>
                        <th style="padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid #4b5563;">
                            Estado
                        </th>
                        <th style="padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid #4b5563;">
                            Motivo
                        </th>
                        <th style="padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 1px solid #4b5563;">
                            Observaciones
                        </th>
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

                        <tr>
                            <td style="padding: 12px; white-space: nowrap; border-bottom: 1px solid #374151;">
                                {{ $history->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>

                            <td style="padding: 12px; white-space: nowrap; border-bottom: 1px solid #374151;">
                                {{ $history->effective_date?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td style="padding: 12px; white-space: nowrap; border-bottom: 1px solid #374151;">
                                {{ $history->previousCategory?->name ?? '-' }}
                            </td>

                            <td style="padding: 12px; white-space: nowrap; border-bottom: 1px solid #374151;">
                                {{ $history->category?->name ?? '-' }}
                            </td>

                            <td style="padding: 12px; white-space: nowrap; border-bottom: 1px solid #374151;">
                                {{ $type }}
                            </td>

                            <td style="padding: 12px; white-space: nowrap; border-bottom: 1px solid #374151;">
                                {{ $status }}
                            </td>

                            <td style="padding: 12px; min-width: 180px; border-bottom: 1px solid #374151;">
                                {{ $history->reason ?? '-' }}
                            </td>

                            <td style="padding: 12px; min-width: 180px; border-bottom: 1px solid #374151;">
                                {{ $history->notes ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>