<x-filament-panels::page>
    <style>
        .performance-summary, .performance-card { padding: 1rem 1.25rem; border: 1px solid rgba(127,127,127,.3); border-radius: .75rem; background: rgba(127,127,127,.08); line-height: 1.6; }
        .performance-card { overflow-x: auto; }
        .performance-card h2 { font-size: 1rem; font-weight: 600; margin-bottom: .75rem; }
        .performance-card table { width: 100%; border-collapse: collapse; min-width: 450px; }
        .performance-card th, .performance-card td { padding: .6rem .75rem; border-bottom: 1px solid rgba(127,127,127,.25); text-align: left; }
        .performance-card th:last-child, .performance-card td:last-child { text-align: right; }
    </style>
    @php($totals = $this->totals())

    <div class="performance-summary">
        <strong>Jugadores seleccionados desde el listado: {{ $totals['players'] }}</strong>
        · {{ $totals['tournaments'] }} participaciones
        · {{ $totals['results'] }} resultados cargados
        · {{ number_format((float) $totals['points'], 2, ',', '.') }} puntos
    </div>

    {{ $this->table }}

    <div class="performance-card">
        <h2>Resumen por jugador según los filtros</h2>
        <table>
            <thead>
                <tr>
                    <th>Jugador</th>
                    <th>Torneos</th>
                    <th>Resultados</th>
                    <th>Puntos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->playerSummaries() as $player)
                    <tr>
                        <td>{{ $player['name'] }}</td>
                        <td>{{ $player['tournaments'] }}</td>
                        <td>{{ $player['results'] }}</td>
                        <td>{{ number_format($player['points'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
