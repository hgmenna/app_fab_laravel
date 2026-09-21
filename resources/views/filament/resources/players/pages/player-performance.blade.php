<x-filament-panels::page>
    <style>
        .performance-summary { padding: 1rem 1.25rem; border: 1px solid rgba(127,127,127,.3); border-radius: .75rem; background: rgba(127,127,127,.08); line-height: 1.6; }
    </style>
    {{ $this->table }}

    @php($totals = $this->totals())
    <div class="performance-summary">
        <strong>Totales del jugador según los filtros:</strong>
        {{ $totals['tournaments'] }} torneos ·
        {{ $totals['results'] }} resultados cargados ·
        {{ number_format((float) $totals['points'], 2, ',', '.') }} puntos
    </div>
</x-filament-panels::page>
