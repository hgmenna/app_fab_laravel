<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 3px; }
        .meta { color: #555; margin-bottom: 14px; }
        .player { margin: 14px 0; }
        .player h2 { font-size: 11px; background: #eee; padding: 7px; margin: 0; }
        .player h2 span { font-size: 8px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background: #f5f5f5; }
        .number { text-align: right; }
        .empty { padding: 7px; }
    </style>
</head>
<body>
    <h1>Desempeño de jugadores</h1>
    <div class="meta">
        {{ $totals['players'] }} jugadores · {{ $totals['tournaments'] }} participaciones ·
        {{ $totals['results'] }} resultados · {{ number_format((float) $totals['points'], 2, ',', '.') }} puntos
        · Emitido el {{ $generatedAt }}
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
                    <thead><tr>
                        <th>Torneo</th><th>Tipo</th><th>Fecha</th>
                        <th>Inscriptos</th><th>Posición / resultado</th><th>Puntos</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($player['rows'] as $row)
                            <tr>
                                <td>{{ $row->tournament?->name ?? '-' }}</td>
                                <td>{{ $row->tournament?->type?->name ?? '-' }}</td>
                                <td>{{ $row->tournament?->end_date?->format('d/m/Y') }}</td>
                                <td class="number">{{ $row->participant_count ?? 0 }}</td>
                                <td>{{ $row->result_description ?? $row->tournamentInstance?->description ?? 'Sin resultado' }}</td>
                                <td class="number">{{ number_format((float) $row->points, 2, ',', '.') }}</td>
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
