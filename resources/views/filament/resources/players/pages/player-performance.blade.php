<x-filament-panels::page>
    {{ $this->table }}

    @php($totals = $this->totals())
    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <strong>Totales del jugador según los filtros:</strong>
        {{ $totals['tournaments'] }} torneos ·
        {{ $totals['results'] }} resultados cargados ·
        {{ number_format((float) $totals['points'], 2, ',', '.') }} puntos
    </div>
</x-filament-panels::page>
