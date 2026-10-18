<x-filament-panels::page>
    <style>
        .fq-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:1rem}.fq-card,.fq-slot{border:1px solid rgba(127,127,127,.25);background:rgba(127,127,127,.06);border-radius:.8rem;padding:1rem}.fq-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#718096}.fq-value{font-size:1.35rem;font-weight:800;margin-top:.2rem}.fq-phases{display:flex;gap:.55rem;flex-wrap:wrap;margin:1rem 0}.fq-phase{border:1px solid rgba(59,130,246,.35);border-radius:.7rem;padding:.7rem .9rem;min-width:10rem}.fq-phase strong{display:block}.fq-slots{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.fq-zones{display:grid;grid-template-columns:1fr;gap:.9rem}.fq-player{display:grid;grid-template-columns:1fr auto;gap:.5rem;padding:.45rem 0;border-bottom:1px solid rgba(127,127,127,.18)}.fq-player:last-child{border-bottom:0}.fq-rank{font-size:.75rem;color:#718096}.fq-empty{padding:2rem;text-align:center;border:1px dashed rgba(127,127,127,.4);border-radius:.8rem}.fq-slot-tabs{display:flex;gap:.4rem;overflow:auto;margin-bottom:.8rem}.fq-slot-tab{border:1px solid rgba(127,127,127,.3);border-radius:999px;padding:.45rem .75rem;font-size:.78rem;font-weight:800;white-space:nowrap}.fq-slot-tab.is-active{color:#fff;background:#173d7c;border-color:#173d7c}.fq-zone{overflow:hidden;border:1px solid #d8e0ea;border-radius:.75rem;background:#fff;color:#17253a}.dark .fq-zone{border-color:#2b3950;background:#111a28;color:#eef4ff}.fq-zone-head{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.55rem .75rem;background:#173d7c;color:#fff}.fq-zone-body{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(23rem,.8fr);gap:.75rem;padding:.75rem}.fq-zone-matches{display:grid;grid-template-columns:repeat(3,minmax(10rem,1fr));gap:.5rem;align-content:start}.fq-bracket-scroll{overflow:auto}.fq-bracket{display:grid;gap:.8rem;min-width:58rem}.fq-round{display:flex;flex-direction:column;justify-content:space-around;gap:.55rem}.fq-match-shell{min-width:0}.fq-match{border:1px solid #d8e0ea;border-left:4px solid #2464ce;border-radius:.5rem;background:#f4f7fb;padding:.45rem;font-size:.69rem;color:#17253a}.dark .fq-match{border-color:#2b3950;border-left-color:#2464ce;background:#182334;color:#eef4ff}.fq-match-title{display:block;margin-bottom:.3rem;color:#173d7c;font-weight:800}.dark .fq-match-title{color:#8db7ff}.fq-match-player{display:flex;justify-content:space-between;gap:.5rem;padding:.18rem 0}.fq-match-player.is-winner{color:#07844d;font-weight:900}.fq-match-time{display:block;margin-top:.2rem;color:#65758b}.fq-match-actions{display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.35rem}.fq-standings-wrap{overflow:auto}.fq-standings{width:100%;border-collapse:collapse;font-size:.75rem}.fq-standings th,.fq-standings td{padding:.5rem;border:1px solid #d8e0ea;text-align:center}.dark .fq-standings th,.dark .fq-standings td{border-color:#2b3950}.fq-standings th{background:#07844d;color:#fff;font-weight:800}.fq-standings td:nth-child(2){text-align:left;font-weight:700;min-width:10rem}@media(max-width:1050px){.fq-zone-body{grid-template-columns:1fr}.fq-zone-matches{grid-template-columns:repeat(2,minmax(10rem,1fr))}}@media(max-width:900px){.fq-summary{grid-template-columns:repeat(2,1fr)}.fq-slots{grid-template-columns:1fr}.fq-zone-matches{grid-template-columns:1fr}.fq-match:nth-child(n){grid-column:auto}}
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
                @php($previousPhase = $competition->phases->where('is_enabled', true)->where('sort_order', '<', $phase->sort_order)->sortByDesc('sort_order')->first())
                <div class="fq-phase" style="{{ $phase->is_enabled ? '' : 'opacity:.48' }}">
                    <span class="fq-label">Fase {{ $phase->sort_order }}{{ $phase->is_enabled ? '' : ' · Deshabilitada' }}</span>
                    <strong>{{ $phase->name }}</strong>
                    <small>{{ match($phase->status){'ready'=>'Preparada','in_progress'=>'En curso','completed'=>'Finalizada',default=>'Pendiente'} }}</small>
                    @if($phase->is_enabled)
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.6rem">
                            @if($phase->phase_type === 'zones' && $phase->status !== 'completed')
                                {{ ($this->createZonesAction)(['phaseId' => $phase->id]) }}
                            @elseif($phase->phase_type !== 'zones' && $phase->status !== 'completed' && $phase->matches->isEmpty() && (!$previousPhase || $previousPhase->status === 'completed'))
                                {{ ($this->preparePhaseAction)(['phaseId' => $phase->id]) }}
                            @elseif($phase->phase_type !== 'zones' && $phase->status !== 'completed' && $phase->matches->isNotEmpty())
                                @foreach(collect(data_get($phase->settings, 'schedules', [])) as $scheduleIndex => $schedule)
                                    <span style="display:flex;align-items:center;gap:.25rem"><small>{{ $schedule['name'] ?? 'Horario' }}</small>{{ ($this->schedulePhaseAction)(['phaseId' => $phase->id, 'scheduleIndex' => $scheduleIndex]) }}</span>
                                @endforeach
                            @elseif($phase->phase_type !== 'zones' && $phase->status !== 'completed' && $phase->matches->isEmpty())
                                <small>Pendiente de la fase anterior</small>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <h2 style="font-size:1.1rem;font-weight:800;margin:1rem 0 .65rem">Jugadores pendientes de asignación</h2>
        <div class="fq-slots">
            @forelse($entriesByPhase as $phaseName => $entries)
                <section class="fq-slot">
                    <div style="display:flex;justify-content:space-between;gap:.5rem;margin-bottom:.5rem"><strong>{{ $phaseName }}</strong><span>{{ $entries->count() }} jugadores</span></div>
                    @foreach($entries->sortBy(fn($entry) => $entry->player?->last_name) as $entry)
                        <div class="fq-player"><span>{{ $entry->player?->full_name }}<br><small>{{ $entry->player?->category?->name ?? 'SIN CATEGORÍA' }} · {{ $entry->sourceSlot?->name ?? 'SIN HORARIO' }}</small></span><span class="fq-rank">RG {{ $entry->national_ranking_snapshot ?? '—' }}</span></div>
                    @endforeach
                </section>
            @empty
                <div class="fq-empty">Todos los jugadores disponibles ya fueron asignados.</div>
            @endforelse
        </div>

        @php($zonePhase = $competition->phases->first(fn($phase) => $phase->phase_type === 'zones' && $phase->is_enabled))
        @if($zonePhase?->groups?->isNotEmpty())
            @php($groupsBySlot = $zonePhase->groups->groupBy(fn($group) => (string)($group->slot_id ?? 0)))
            @php($firstSlotKey = (string)$groupsBySlot->keys()->first())
            <h2 style="font-size:1.1rem;font-weight:800;margin:1.2rem 0 .65rem">Zonas preparadas</h2>
            <div x-data="{ activeSlot: @js($firstSlotKey) }">
                <div class="fq-slot-tabs">
                    @foreach($groupsBySlot as $slotKey => $slotGroups)
                        <button type="button" class="fq-slot-tab" :class="activeSlot === @js((string)$slotKey) && 'is-active'" @click="activeSlot = @js((string)$slotKey)">
                            {{ $slotGroups->first()?->slot?->name ?? 'SIN HORARIO' }}
                        </button>
                    @endforeach
                </div>
                @foreach($groupsBySlot as $slotKey => $slotGroups)
                    <div class="fq-zones" x-show="activeSlot === @js((string)$slotKey)" x-cloak>
                        @foreach($slotGroups as $group)
                            <section class="fq-zone">
                                <header class="fq-zone-head"><strong>{{ $group->name }}</strong><span>{{ $group->slot?->name ?? 'SIN HORARIO' }}</span></header>
                                <div class="fq-zone-body">
                                    <div class="fq-zone-matches">
                                        @foreach($group->matches->sortBy(fn($match) => [$match->round_number, $match->bracket_order]) as $match)
                                            <div class="fq-match-shell">
                                                <article class="fq-match">
                                                    <b class="fq-match-title">{{ $match->label === 'Partido de recuperación' ? 'Perdedores' : $match->label }}</b>
                                                    <div class="fq-match-player {{ $match->winner_entry_id === $match->player_one_entry_id ? 'is-winner' : '' }}">
                                                        <span>{{ $match->playerOne?->player?->full_name ?? ($match->is_bye ? 'LIBRE' : 'A definir') }}</span>
                                                        <span>{{ $match->status === 'completed' ? $match->player_one_computed_sets : '—' }}</span>
                                                    </div>
                                                    <div class="fq-match-player {{ $match->winner_entry_id === $match->player_two_entry_id ? 'is-winner' : '' }}">
                                                        <span>{{ $match->playerTwo?->player?->full_name ?? ($match->is_bye ? 'LIBRE' : 'A definir') }}</span>
                                                        <span>{{ $match->status === 'completed' ? $match->player_two_computed_sets : '—' }}</span>
                                                    </div>
                                                    @if($match->scheduled_at)<small class="fq-match-time">{{ $match->scheduled_at->format('H:i') }}</small>@endif
                                                    @if($match->table_number)<small class="fq-match-time">Mesa {{ $match->table_number }}</small>@endif
                                                </article>
                                                @if($match->player_one_entry_id && $match->player_two_entry_id && !$match->is_bye)
                                                    <div class="fq-match-actions">
                                                        @if($match->status !== 'completed')
                                                            {{ ($this->callMatchAction)(['matchId' => $match->id]) }}
                                                        @endif
                                                        {{ ($this->recordResultAction)(['matchId' => $match->id]) }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="fq-standings-wrap">
                                        <table class="fq-standings">
                                            <thead><tr><th>Pos</th><th>Jugador</th><th>PG</th><th>DS</th><th>PF</th><th>PC</th><th>Prom</th></tr></thead>
                                            <tbody>
                                            @foreach($group->groupEntries->sortBy(fn($row) => $row->final_place ?: 99) as $row)
                                                <tr>
                                                    <td>{{ $row->final_place ?? '—' }}</td><td>{{ $row->entry?->player?->full_name }}</td>
                                                    <td>{{ $row->match_wins }}</td><td>{{ $row->sets_difference }}</td>
                                                    <td>{{ $row->points_for }}</td><td>{{ $row->points_against }}</td>
                                                    <td>{{ number_format((float)$row->points_average, 3, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        @foreach($competition->phases->where('phase_type', '!=', 'zones')->where('is_enabled', true) as $phase)
            @if($phase->matches->isNotEmpty())
                <h2 style="font-size:1.1rem;font-weight:800;margin:1.2rem 0 .65rem">{{ $phase->name }}</h2>
                @php($matchesByRound = $phase->matches->groupBy('round_number')->sortKeys())
                <div class="fq-bracket-scroll">
                    <div class="fq-bracket" style="grid-template-columns:repeat({{ max($matchesByRound->count(), 1) }},minmax(12rem,1fr))">
                        @foreach($matchesByRound as $roundMatches)
                            <section class="fq-round">
                                @foreach($roundMatches->sortBy('bracket_order') as $match)
                                    <div class="fq-match-shell">
                                        <article class="fq-match">
                                            <b class="fq-match-title">{{ $match->label === 'Partido de recuperación' ? 'Perdedores' : $match->label }}</b>
                                            <div class="fq-match-player {{ $match->winner_entry_id === $match->player_one_entry_id ? 'is-winner' : '' }}"><span>{{ $match->playerOne?->player?->full_name ?? 'A definir' }}</span><span>{{ $match->status === 'completed' ? $match->player_one_computed_sets : '—' }}</span></div>
                                            <div class="fq-match-player {{ $match->winner_entry_id === $match->player_two_entry_id ? 'is-winner' : '' }}"><span>{{ $match->playerTwo?->player?->full_name ?? 'A definir' }}</span><span>{{ $match->status === 'completed' ? $match->player_two_computed_sets : '—' }}</span></div>
                                            @if($match->scheduled_at)<small class="fq-match-time">{{ $match->scheduled_at->format('H:i') }}</small>@endif
                                            @if($match->table_number)<small class="fq-match-time">Mesa {{ $match->table_number }}</small>@endif
                                        </article>
                                        @if($match->player_one_entry_id && $match->player_two_entry_id && !$match->is_bye)
                                            <div class="fq-match-actions">
                                                @if($match->status !== 'completed'){{ ($this->callMatchAction)(['matchId' => $match->id]) }}@endif
                                                {{ ($this->recordResultAction)(['matchId' => $match->id]) }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        @if($publicJsonUrl)
            <div class="fq-card" style="margin-top:1rem">
                <div class="fq-label">Resultados para el público</div>
                <a href="{{ $publicPageUrl }}" target="_blank" style="color:#3b82f6;font-weight:800">Ver resultados públicos</a>
                <br><small><a href="{{ $publicJsonUrl }}" target="_blank" style="color:#718096;word-break:break-all">Servicio JSON para WordPress</a></small>
            </div>
        @endif
    @endif
</x-filament-panels::page>
