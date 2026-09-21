<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @include('pdf.partials.fab-report-header-styles')
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        .meta { color: #555; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 7px; text-align: left; }
        th { background: #eee; }
        .number { text-align: right; }
        tfoot { font-weight: bold; background: #eee; }
    </style>
</head>
<body>
    @include('pdf.partials.fab-report-header', [
        'reportTitle' => 'Desempeño de jugador',
        'reportSubtitle' => $player->full_name,
    ])
    <div class="meta">Torneos finalizados</div>
    <table>
        <thead>
            <tr>
                <th>Torneo</th><th>Tipo</th><th>Fecha</th><th>Inscriptos</th><th>Posición / resultado</th><th>Puntos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->tournament?->name }}</td>
                    <td>{{ $row->tournament?->type?->name ?? '-' }}</td>
                    <td>{{ $row->tournament?->end_date?->format('d/m/Y') }}</td>
                    <td class="number">{{ $row->participant_count ?? 0 }}</td>
                    <td>{{ $row->result_description ?? $row->tournamentInstance?->description ?? 'Sin resultado' }}</td>
                    <td class="number">{{ number_format((float) $row->points, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No se encontraron torneos para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Totales del jugador</td>
                <td class="number">{{ $rows->count() }} torneos</td>
                <td>{{ $rows->filter(fn ($row) => $row->result_code !== null || $row->tournament_instance_id !== null)->count() }} resultados</td>
                <td class="number">{{ number_format($points, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
