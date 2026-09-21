<x-filament-panels::page>
    @php($totals = $this->totals())

    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <strong>Jugadores seleccionados desde el listado: {{ $totals['players'] }}</strong>
        · {{ $totals['tournaments'] }} participaciones
        · {{ $totals['results'] }} resultados cargados
        · {{ number_format((float) $totals['points'], 2, ',', '.') }} puntos
    </div>

    {{ $this->table }}

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <h2 class="mb-3 font-semibold">Resumen por jugador según los filtros</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left dark:border-gray-700">
                    <th class="p-2">Jugador</th>
                    <th class="p-2">Torneos</th>
                    <th class="p-2">Resultados</th>
                    <th class="p-2">Puntos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->playerSummaries() as $player)
                    <tr class="border-b dark:border-gray-700">
                        <td class="p-2">{{ $player['name'] }}</td>
                        <td class="p-2">{{ $player['tournaments'] }}</td>
                        <td class="p-2">{{ $player['results'] }}</td>
                        <td class="p-2">{{ number_format($player['points'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
