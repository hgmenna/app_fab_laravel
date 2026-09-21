<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 3px; }
        h2 { font-size: 12px; margin-top: 18px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th, tfoot { background: #eee; font-weight: bold; }
        .number { text-align: right; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>Desempeño de jugadores</h1>
    <div class="meta">{{ $totals['players'] }} jugadores filtrados · Emitido el {{ $generatedAt }}</div>

    <h2>Resumen por jugador</h2>
    <table>
        <thead><tr><th>Jugador</th><th>Torneos</th><th>Resultados</th><th>Puntos</th></tr></thead>
        <tbody>
            @foreach ($players as $player)
                <tr>
                    <td>{{ $player['name'] }}</td>
                    <td class="number">{{ $player['tournaments'] }}</td>
                    <td class="number">{{ $player['results'] }}</td>
                    <td class="number">{{ number_format($player['points'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot><tr>
            <td>Total</td><td class="number">{{ $totals['tournaments'] }}</td>
            <td class="number">{{ $totals['results'] }}</td>
            <td class="number">{{ number_format((float) $totals['points'], 2, ',', '.') }}</td>
        </tr></tfoot>
    </table>

    <h2 class="page-break">Detalle de participaciones</h2>
    <table>
        <thead><tr>
            <th>Jugador</th><th>Torneo</th><th>Tipo</th><th>Fecha</th>
            <th>Inscriptos</th><th>Posición / resultado</th><th>Puntos</th>
        </tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->player?->full_name ?? '-' }}</td>
                    <td>{{ $row->tournament?->name ?? '-' }}</td>
                    <td>{{ $row->tournament?->type?->name ?? '-' }}</td>
                    <td>{{ $row->tournament?->end_date?->format('d/m/Y') }}</td>
                    <td class="number">{{ $row->participant_count ?? 0 }}</td>
                    <td>{{ $row->result_description ?? $row->tournamentInstance?->description ?? 'Sin resultado' }}</td>
                    <td class="number">{{ number_format((float) $row->points, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Sin participaciones para los filtros elegidos.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
