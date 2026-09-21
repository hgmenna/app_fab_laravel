<x-filament-panels::page>
    <style>
        .performance-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem; }
        .performance-filters label { display: block; font-size: .8rem; font-weight: 600; margin-bottom: .35rem; }
        .performance-filters input, .performance-filters select { width: 100%; background: rgba(127,127,127,.08); color: inherit; border: 1px solid rgba(127,127,127,.35); border-radius: .5rem; padding: .55rem; }
        .performance-filters select option { color: #111; }
        .performance-type-picker { position: relative; }
        .performance-type-picker > button { display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; min-height: 2.75rem; padding: .55rem .75rem; border: 1px solid rgba(127,127,127,.35); border-radius: .5rem; background: rgba(127,127,127,.08); color: inherit; text-align: left; cursor: pointer; }
        .performance-type-options { position: absolute; z-index: 30; top: calc(100% + .3rem); left: 0; width: max(100%, 18rem); max-width: min(24rem, calc(100vw - 2rem)); max-height: 16rem; overflow-y: auto; padding: .35rem; border: 1px solid rgba(127,127,127,.35); border-radius: .5rem; background: #242427; color: #fff; box-shadow: 0 12px 24px rgba(0,0,0,.35); }
        .performance-type-options label { display: flex; align-items: center; gap: .6rem; margin: 0; padding: .5rem; border-radius: .35rem; cursor: pointer; }
        .performance-type-options label:hover { background: rgba(255,255,255,.12); }
        .performance-type-options input { width: 1rem; height: 1rem; flex: none; margin: 0; }
        .performance-type-options p { padding: .5rem; margin: 0; }
        .performance-filters .performance-checkbox { display: flex; align-items: center; gap: .55rem; padding: .7rem 0; }
        .performance-filters .performance-checkbox input { width: 1.1rem; height: 1.1rem; margin: 0; }
        .performance-filters .performance-checkbox label { margin: 0; cursor: pointer; }
        .performance-totals { padding: 1rem 1.25rem; border: 1px solid rgba(127,127,127,.3); border-radius: .75rem; background: rgba(127,127,127,.08); }
        .performance-players { display: grid; gap: .75rem; }
        .performance-player { border: 1px solid rgba(127,127,127,.3); border-radius: .75rem; overflow: hidden; }
        .performance-player summary { cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; background: rgba(127,127,127,.08); }
        .performance-player summary::-webkit-details-marker { display: none; }
        .performance-player summary::after { content: '▾'; font-size: 1.25rem; }
        .performance-player[open] summary::after { content: '▴'; }
        .performance-player strong { font-size: 1rem; }
        .performance-player .metrics { display: flex; flex-wrap: wrap; gap: .75rem; font-size: .85rem; }
        .performance-detail { overflow-x: auto; }
        .performance-detail table { width: 100%; border-collapse: collapse; min-width: 720px; }
        .performance-detail th, .performance-detail td { text-align: left; padding: .7rem .85rem; border-top: 1px solid rgba(127,127,127,.25); }
        .performance-detail th:last-child, .performance-detail td:last-child { text-align: right; }
        .performance-empty { padding: 1rem 1.25rem; }
    </style>

    <div class="performance-filters">
        <div>
            <label for="performance-player-search">Jugador</label>
            <input id="performance-player-search" type="search" wire:model.live.debounce.350ms="searchPlayer" placeholder="Buscar jugador">
        </div>
        <div>
            <label for="performance-discipline">Disciplina</label>
            <select id="performance-discipline" wire:model.live="disciplineId">
                <option value="">Todas</option>
                @foreach ($this->disciplineOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <span class="block text-sm font-semibold mb-1">Tipo de torneo</span>
            <div class="performance-type-picker" x-data="{ open: false }" x-on:click.outside="open = false">
                <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-label="Seleccionar tipos de torneo">
                    <span>{{ count($this->typeIds) ? count($this->typeIds) . ' tipos seleccionados' : 'Todos los tipos' }}</span>
                    <span aria-hidden="true">⌄</span>
                </button>
                <div class="performance-type-options" x-show="open" x-cloak>
                    @forelse ($this->typeOptions() as $id => $name)
                        <label for="performance-type-{{ $id }}" wire:key="performance-type-{{ $id }}">
                            <input id="performance-type-{{ $id }}" type="checkbox" value="{{ $id }}" wire:model.live="typeIds">
                            <span>{{ $name }}</span>
                        </label>
                    @empty
                        <p>Sin tipos de torneo para esta disciplina.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div>
            <label for="performance-from">Desde</label>
            <input id="performance-from" type="date" wire:model.live="fromDate">
        </div>
        <div>
            <label for="performance-until">Hasta</label>
            <input id="performance-until" type="date" wire:model.live="untilDate">
        </div>
        <div>
            <label for="performance-min-points">Puntos mínimos por participación</label>
            <input id="performance-min-points" type="number" step="0.01" wire:model.live.debounce.350ms="minPoints" placeholder="Sin mínimo">
        </div>
        <div>
            <label for="performance-max-points">Puntos máximos por participación</label>
            <input id="performance-max-points" type="number" step="0.01" wire:model.live.debounce.350ms="maxPoints" placeholder="Sin máximo">
        </div>
        <div class="performance-checkbox">
            <input id="performance-only-participants" type="checkbox" wire:model.live="onlyParticipants">
            <label for="performance-only-participants">Solo jugadores con participaciones</label>
        </div>
    </div>

    @php($report = $this->reportData())
    <div class="performance-totals">
        <strong>{{ $report['totals']['players'] }} jugadores</strong>
        · {{ $report['totals']['tournaments'] }} participaciones
        · {{ $report['totals']['results'] }} resultados cargados
        · {{ number_format((float) $report['totals']['points'], 2, ',', '.') }} puntos
    </div>

    <div class="performance-players">
        @forelse ($report['players'] as $player)
            <details class="performance-player" wire:key="performance-player-{{ $player['id'] }}">
                <summary>
                    <strong>{{ $player['name'] }}</strong>
                    <span class="metrics">
                        <span>Torneos: {{ $player['tournaments'] }}</span>
                        <span>Resultados: {{ $player['results'] }}</span>
                        <span>Puntos: {{ number_format((float) $player['points'], 2, ',', '.') }}</span>
                    </span>
                </summary>
                @if ($player['rows']->isEmpty())
                    <p class="performance-empty">Sin participaciones para los filtros seleccionados.</p>
                @else
                    <div class="performance-detail">
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
                                        <td>{{ $row->participant_count ?? 0 }}</td>
                                        <td>{{ $row->result_description ?? $row->tournamentInstance?->description ?? 'Sin resultado' }}</td>
                                        <td>{{ number_format((float) $row->points, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </details>
        @empty
            <p class="performance-empty">No hay jugadores para los filtros seleccionados.</p>
        @endforelse
    </div>
</x-filament-panels::page>
