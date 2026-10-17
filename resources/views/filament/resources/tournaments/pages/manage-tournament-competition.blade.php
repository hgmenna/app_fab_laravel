<x-filament-panels::page>
    <style>
        .fq-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:1rem}.fq-card,.fq-slot{border:1px solid rgba(127,127,127,.25);background:rgba(127,127,127,.06);border-radius:.8rem;padding:1rem}.fq-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#718096}.fq-value{font-size:1.35rem;font-weight:800;margin-top:.2rem}.fq-phases{display:flex;gap:.55rem;flex-wrap:wrap;margin:1rem 0}.fq-phase{border:1px solid rgba(59,130,246,.35);border-radius:.7rem;padding:.7rem .9rem;min-width:10rem}.fq-phase strong{display:block}.fq-slots,.fq-zones{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.fq-player{display:grid;grid-template-columns:1fr auto;gap:.5rem;padding:.45rem 0;border-bottom:1px solid rgba(127,127,127,.18)}.fq-player:last-child{border-bottom:0}.fq-rank{font-size:.75rem;color:#718096}.fq-empty{padding:2rem;text-align:center;border:1px dashed rgba(127,127,127,.4);border-radius:.8rem}.fq-zone{overflow:hidden;border:1px solid rgba(37,99,235,.35);border-radius:.8rem;background:rgba(15,23,42,.04)}.fq-zone-head{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.7rem .85rem;background:#1e478e;color:#fff}.fq-zone-body{padding:.8rem}.fq-seed{display:grid;grid-template-columns:1.5rem 1fr auto;gap:.5rem;align-items:center;padding:.4rem 0;border-bottom:1px solid rgba(127,127,127,.18)}.fq-seed:last-child{border-bottom:0}.fq-pos{display:grid;place-items:center;width:1.45rem;height:1.45rem;border-radius:50%;background:#f5b400;color:#111827;font-weight:800;font-size:.75rem}.fq-bracket{display:grid;grid-template-columns:repeat(3,1fr);gap:.35rem;margin-top:.75rem}.fq-match{border-left:3px solid #f5b400;background:rgba(127,127,127,.08);padding:.45rem;font-size:.73rem;min-height:3.4rem}.fq-match strong{display:block;color:#1d4ed8}.fq-match:nth-child(3),.fq-match:nth-child(4){grid-column:2}.fq-match:nth-child(5){grid-column:3}@media(max-width:900px){.fq-summary{grid-template-columns:repeat(2,1fr)}.fq-slots,.fq-zones{grid-template-columns:1fr}.fq-bracket{grid-template-columns:1fr}.fq-match:nth-child(n){grid-column:auto}}
    </style>

    @if(!$competition)
        <div class="fq-empty">
            <h2 style="font-size:1.15rem;font-weight:800">La competencia todavía no fue preparada</h2>
            <p>Usá <strong>Iniciar organización</strong> para crear las fases y tomar los inscriptos aprobados de cada horario.</p>
        </div>
    @else
        <div class="fq-summary">
            <div class="fq-card"><div class="fq-label">Estado</div><div class="fq-value">{{ match($competition->status){'published'=>'Publicada','in_progress'=>'En curso','completed'=>'Finalizada',default=>'Borrador'} }}</div></div>
            <div class="fq-card"><div class="fq-label">Jugadores disponibles</div><div class="fq-value">{{ $competition->entries->where('status','!=','withdrawn')->count() }}</div></div>
            <div class="fq-card"><div class="fq-label">Horarios</div><div class="fq-value">{{ $entriesBySlot->count() }}</div></div>
            <div class="fq-card"><div class="fq-label">Fases</div><div class="fq-value">{{ $competition->phases->count() }}</div></div>
        </div>

        <div class="fq-phases">
            @foreach($competition->phases as $phase)
                <div class="fq-phase" style="{{ $phase->is_enabled ? '' : 'opacity:.48' }}"><span class="fq-label">Fase {{ $phase->sort_order }}{{ $phase->is_enabled ? '' : ' · Deshabilitada' }}</span><strong>{{ $phase->name }}</strong><small>{{ $phase->sets_to_play }} sets · {{ $phase->points_per_set }} puntos · {{ data_get($phase->settings, 'tables') ?: '—' }} mesas · {{ data_get($phase->settings, 'estimated_minutes') ?: '—' }} min</small></div>
            @endforeach
        </div>

        <h2 style="font-size:1.1rem;font-weight:800;margin:1rem 0 .65rem">Inscriptos aprobados disponibles para formar zonas</h2>
        <div class="fq-slots">
            @forelse($entriesBySlot as $slot => $entries)
                <section class="fq-slot">
                    <div style="display:flex;justify-content:space-between;gap:.5rem;margin-bottom:.5rem"><strong>{{ $slot }}</strong><span>{{ $entries->count() }} jugadores</span></div>
                    @foreach($entries->sortBy(fn($entry) => $entry->player?->last_name) as $entry)
                        <div class="fq-player"><span>{{ $entry->player?->full_name }}<br><small>{{ $entry->player?->club?->name ?? 'INDEPENDIENTE' }}</small></span><span class="fq-rank">RG {{ $entry->national_ranking_snapshot ?? '—' }}</span></div>
                    @endforeach
                </section>
            @empty
                <div class="fq-empty">No hay inscripciones aprobadas asignadas a horarios activos.</div>
            @endforelse
        </div>

        @php($zonePhase = $competition->phases->first(fn($phase) => $phase->phase_type === 'zones' && $phase->is_enabled))
        @if($zonePhase?->groups?->isNotEmpty())
            <h2 style="font-size:1.1rem;font-weight:800;margin:1.2rem 0 .65rem">Zonas preparadas</h2>
            <div class="fq-zones">
                @foreach($zonePhase->groups as $group)
                    <section class="fq-zone">
                        <header class="fq-zone-head"><strong>{{ $group->name }}</strong><span>{{ $group->slot?->name ?? 'SIN HORARIO' }}</span></header>
                        <div class="fq-zone-body">
                            @php($rowsByPosition = $group->groupEntries->keyBy('position'))
                            @foreach(range(1, 4) as $position)
                                @php($row = $rowsByPosition->get($position))
                                <div class="fq-seed">
                                    <span class="fq-pos">{{ $position }}</span>
                                    @if($row)
                                        <span><strong>{{ $row->entry?->player?->full_name }}</strong><br><small>{{ $row->entry?->player?->club?->name ?? 'INDEPENDIENTE' }}</small></span>
                                        <small>RG {{ $row->entry?->national_ranking_snapshot ?? '—' }}</small>
                                    @else
                                        <span><strong style="color:#dc2626">LIBRE</strong><br><small>Pase automático del rival</small></span><small>2–0</small>
                                    @endif
                                </div>
                            @endforeach
                            <div class="fq-bracket">
                                @foreach($group->matches->sortBy(fn($match) => [$match->round_number, $match->bracket_order]) as $match)
                                    <div class="fq-match">
                                        <strong>{{ $match->label }}</strong>
                                        {{ $match->playerOne?->player?->full_name ?? ($match->is_bye ? 'LIBRE' : 'A definir') }}<br>
                                        {{ $match->playerTwo?->player?->full_name ?? ($match->is_bye ? 'LIBRE' : 'A definir') }}
                                        @if($match->is_bye)<br><small>Pase automático · 2–0</small>@endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif

        @if($publicJsonUrl)
            <div class="fq-card" style="margin-top:1rem"><div class="fq-label">Servicio público para WordPress</div><a href="{{ $publicJsonUrl }}" target="_blank" style="color:#3b82f6;word-break:break-all">{{ $publicJsonUrl }}</a></div>
        @endif
    @endif
</x-filament-panels::page>
