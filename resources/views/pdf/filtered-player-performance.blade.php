<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @include('pdf.partials.fab-report-header-styles')
    <style>
        body { font-family: Helvetica, sans-serif; font-size: 8px; color: #222; }
        .meta { color: #555; margin-bottom: 14px; }
        .player { margin: 14px 0; }
        .player h2 { font-size: 11px; background: #eee; padding: 7px; margin: 0; }
        .player h2 span { font-size: 8px; font-weight: normal; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background: #f5f5f5; }
        .number { text-align: right; }
        .empty { padding: 7px; }
    </style>
</head>
<body>
    @include('pdf.partials.fab-report-header', [
        'reportTitle' => 'Desempeño de jugadores',
        'reportSubtitle' => 'Informe de jugadores filtrados',
    ])
    <div class="meta">
        {{ $totals['players'] }} jugadores · {{ $totals['tournaments'] }} participaciones ·
        {{ $totals['results'] }} resultados · {{ number_format((float) $totals['points'], 2, ',', '.') }} puntos
    </div>

    @forelse ($players as $player)
        <section class="player">
            <h2>{{ $player['name'] }}
                <span>· Torneos: {{ $player['tournaments'] }} · Resultados: {{ $player['results'] }}
                    · Puntos: {{ number_format((float) $player['points'], 2, ',', '.') }}</span>
            </h2>
            @if ($player['rows']->isEmpty())
                <p class="empty">Sin participaciones para los filtros seleccionados.</p>
            @else
                <table>
                    <colgroup><col style="width:30%"><col style="width:23%"><col style="width:10%"><col style="width:8%"><col style="width:21%"><col style="width:8%"></colgroup>
                    <thead><tr>
                        <th>Torneo</th><th>Tipo</th><th>Fecha</th>
                        <th>Inscriptos</th><th>Posición / resultado</th><th>Puntos</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($player['rows'] as $row)
                            <tr>
                                <td>{{ $row->tournament?->name ?? '-' }}</td>
                                <td>{{ $row->tournament?->type?->name ?? '-' }}</td>
                                <td>{{ $row->tournament?->end_date?->format(config('cliente.formato_fecha')) }}</td>
                                <td class="number">{{ $row->participant_count ?? 0 }}</td>
                                <td>{{ $row->performance_score['description'] }}</td>
                                <td class="number">{{ $row->performance_score['points'] === null ? '—' : number_format((float) $row->performance_score['points'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @empty
        <p>No hay jugadores para los filtros seleccionados.</p>
    @endforelse
</body>
</html>
