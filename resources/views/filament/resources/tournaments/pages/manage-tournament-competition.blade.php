<x-filament-panels::page>
    <style>
        .fq-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:1rem}.fq-card,.fq-slot{border:1px solid rgba(127,127,127,.25);background:rgba(127,127,127,.06);border-radius:.8rem;padding:1rem}.fq-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#718096}.fq-value{font-size:1.35rem;font-weight:800;margin-top:.2rem}.fq-phases{display:flex;gap:.55rem;flex-wrap:wrap;margin:1rem 0}.fq-phase{border:1px solid rgba(59,130,246,.35);border-radius:.7rem;padding:.7rem .9rem;min-width:10rem}.fq-phase strong{display:block}.fq-pending{margin:.8rem 0 1rem;border:1px solid rgba(127,127,127,.28);border-radius:.75rem;background:rgba(127,127,127,.05);overflow:hidden}.fq-pending summary{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.7rem .9rem;cursor:pointer;list-style:none;font-weight:800}.fq-pending summary::-webkit-details-marker{display:none}.fq-pending summary:after{content:'Ver lista';padding:.3rem .6rem;border-radius:999px;background:#173d7c;color:#fff;font-size:.7rem}.fq-pending[open] summary{border-bottom:1px solid rgba(127,127,127,.22)}.fq-pending[open] summary:after{content:'Ocultar'}.fq-pending-content{max-height:18rem;overflow:auto;padding:.65rem}.fq-slots{display:grid;grid-template-columns:repeat(auto-fit,minmax(18rem,1fr));gap:.6rem}.fq-pending .fq-slot{padding:.65rem}.fq-zones{display:grid;grid-template-columns:1fr;gap:.9rem}.fq-player{display:grid;grid-template-columns:1fr auto;gap:.5rem;padding:.3rem 0;border-bottom:1px solid rgba(127,127,127,.18);font-size:.76rem;line-height:1.2}.fq-player:last-child{border-bottom:0}.fq-player small{font-size:.64rem;color:#718096}.fq-rank{font-size:.68rem;color:#718096}.fq-empty{padding:2rem;text-align:center;border:1px dashed rgba(127,127,127,.4);border-radius:.8rem}.fq-slot-tabs{display:flex;gap:.4rem;overflow:auto;margin-bottom:.8rem}.fq-slot-tab{border:1px solid rgba(127,127,127,.3);border-radius:999px;padding:.45rem .75rem;font-size:.78rem;font-weight:800;white-space:nowrap}.fq-slot-tab.is-active{color:#fff;background:#173d7c;border-color:#173d7c}.fq-zone{overflow:hidden;border:1px solid #d8e0ea;border-radius:.75rem;background:#fff;color:#17253a}.dark .fq-zone{border-color:#2b3950;background:#111a28;color:#eef4ff}.fq-zone-head{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.55rem .75rem;background:#173d7c;color:#fff}.fq-zone-body{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(23rem,.8fr);gap:.75rem;padding:.75rem}.fq-zone-matches{display:grid;grid-template-columns:repeat(3,minmax(10rem,1fr));gap:.5rem;align-content:start}.fq-bracket-scroll{overflow:auto}.fq-bracket{display:grid;gap:.8rem;min-width:58rem}.fq-round{display:flex;flex-direction:column;justify-content:space-around;gap:.55rem}.fq-match-shell{min-width:0}.fq-match{position:relative;border:1px solid #d8e0ea;border-left:4px solid #2464ce;border-radius:.5rem;background:#f4f7fb;padding:.45rem;font-size:.69rem;color:#17253a}.dark .fq-match{border-color:#2b3950;border-left-color:#2464ce;background:#182334;color:#eef4ff}.fq-match-title{display:block;margin-bottom:.3rem;color:#173d7c;font-weight:800}.dark .fq-match-title{color:#8db7ff}.fq-match-player{display:flex;justify-content:space-between;gap:.5rem;padding:.18rem 0}.fq-match-player.is-winner{color:#07844d;font-weight:900}.fq-match-time{display:block;margin-top:.2rem;color:#65758b}.fq-match-actions{position:absolute;right:.25rem;bottom:.2rem;display:flex;gap:.15rem;align-items:center;padding:.12rem;border:1px solid rgba(127,127,127,.2);border-radius:.45rem;background:rgba(255,255,255,.96);box-shadow:0 2px 8px rgba(15,23,42,.15);opacity:0;visibility:hidden;transform:translateY(3px);transition:.15s ease;z-index:5}.dark .fq-match-actions{background:rgba(17,26,40,.96)}.fq-match:hover .fq-match-actions,.fq-match:focus-within .fq-match-actions{opacity:1;visibility:visible;transform:none}.fq-standings-wrap{overflow:auto}.fq-standings{width:100%;border-collapse:collapse;font-size:.75rem}.fq-standings th,.fq-standings td{padding:.5rem;border:1px solid #d8e0ea;text-align:center}.dark .fq-standings th,.dark .fq-standings td{border-color:#2b3950}.fq-standings th{background:#07844d;color:#fff;font-weight:800}.fq-standings td:nth-child(2){text-align:left;font-weight:700;min-width:10rem}@media(hover:none){.fq-match-actions{position:static;justify-content:flex-end;margin-top:.2rem;opacity:1;visibility:visible;transform:none;box-shadow:none;background:transparent;border:0}}@media(max-width:1050px){.fq-zone-body{grid-template-columns:1fr}.fq-zone-matches{grid-template-columns:repeat(2,minmax(10rem,1fr))}}@media(max-width:900px){.fq-summary{grid-template-columns:repeat(2,1fr)}.fq-slots{grid-template-columns:1fr}.fq-pending-content{max-height:15rem}.fq-zone-matches{grid-template-columns:1fr}.fq-match:nth-child(n){grid-column:auto}}
        .fq-zone-bracket{display:grid;grid-template-columns:repeat(3,minmax(9.5rem,1fr));grid-template-rows:repeat(3,minmax(5.3rem,auto));gap:.45rem 1rem;min-width:36rem;align-items:center}.fq-zone-bracket .fq-match-shell{position:relative}.fq-zone-bracket .fq-match{font-size:.66rem;padding:.32rem}.fq-zone-bracket .fq-match-player{padding:.1rem 0;line-height:1.15}.fq-zone-bracket .fq-match-title{margin-bottom:.18rem}.fq-zone-bracket .fq-match-actions{margin-top:.2rem;gap:.2rem}.fq-zone-bracket .z-m1{grid-column:1;grid-row:1}.fq-zone-bracket .z-m2{grid-column:1;grid-row:2}.fq-zone-bracket .z-loss{grid-column:1;grid-row:3}.fq-zone-bracket .z-win{grid-column:2;grid-row:1}.fq-zone-bracket .z-second{grid-column:2;grid-row:3}.fq-zone-bracket .z-first{grid-column:3;grid-row:1}.fq-zone-bracket .z-runner{grid-column:3;grid-row:3}.fq-place{border:2px solid #173d7c;border-radius:.4rem;background:#fff;padding:.35rem;text-align:center;color:#17253a;font-size:.68rem}.dark .fq-place{background:#111a28;color:#eef4ff}.fq-place b{display:block;color:#173d7c;text-transform:uppercase;font-size:.62rem}.dark .fq-place b{color:#8db7ff}.fq-place span{display:block;margin-top:.18rem;font-weight:900}.fq-set-points{display:flex;gap:.12rem;margin-left:auto}.fq-set-point{display:inline-grid;place-items:center;min-width:1.35rem;height:1.2rem;padding:0 .12rem;border:1px solid #9aa8ba;background:#fff;color:#17253a;font-size:.62rem;font-weight:800}.dark .fq-set-point{border-color:#53627a;background:#111a28;color:#eef4ff}.fq-zone-bracket .z-win:after,.fq-zone-bracket .z-second:after{content:"";position:absolute;top:50%;right:-1rem;width:1rem;border-top:1px solid #9aa8ba}.fq-zone-bracket-wrap{overflow:auto}.fq-phase-tab{cursor:pointer;text-align:left;transition:.15s ease}.fq-phase-tab.is-active{color:#fff;background:#173d7c;border-color:#173d7c}.fq-phase-tab.is-active .fq-label,.fq-phase-tab.is-active small{color:#dbeafe}.fq-phase-panel{margin-top:.8rem}.fq-phase-heading{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.7rem}.fq-phase-heading h2{font-size:1.1rem;font-weight:800}.fq-phase-actions{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}@media(max-width:1050px){.fq-zone-bracket{min-width:36rem}.fq-zone-body{grid-template-columns:1fr}}@media(max-width:700px){.fq-zone-bracket{min-width:34rem}}
        .fq-tournament-info{display:grid;grid-template-columns:auto repeat(5,minmax(0,1fr));gap:.7rem;align-items:center;margin-bottom:1rem;padding:.85rem 1rem;border:1px solid rgba(127,127,127,.25);border-radius:.85rem;background:rgba(127,127,127,.05)}.fq-tournament-logo{width:58px;height:58px;object-fit:contain}.fq-info-item small{display:block;color:#718096;font-size:.66rem;text-transform:uppercase;letter-spacing:.06em}.fq-info-item strong{display:block;margin-top:.15rem;font-size:.82rem}.fq-section-tools{display:flex;gap:.35rem}.fq-icon-button{display:inline-grid;place-items:center;width:2rem;height:2rem;border:1px solid rgba(127,127,127,.35);border-radius:.5rem;background:rgba(127,127,127,.08);cursor:pointer}.fq-icon-button:hover{color:#fff;background:#173d7c;border-color:#173d7c}.fq-icon-button svg{width:1rem;height:1rem}@media(max-width:950px){.fq-tournament-info{grid-template-columns:auto repeat(2,minmax(0,1fr))}}@media(max-width:560px){.fq-tournament-info{grid-template-columns:1fr 1fr}.fq-tournament-logo{grid-column:1/-1}}
    </style>

    @if(!$competition)
        <div class="fq-empty">
            <h2 style="font-size:1.15rem;font-weight:800">La competencia todavía no fue preparada</h2>
            <p>Usá <strong>Iniciar organización</strong> para crear las fases y tomar los inscriptos aprobados de cada horario.</p>
        </div>
    @else
        <section class="fq-tournament-info" id="fq-tournament-info">
            @if($tournamentInfo['logo'])<img class="fq-tournament-logo" src="{{ $tournamentInfo['logo'] }}" alt="Logo del torneo">@endif
            <div class="fq-info-item"><small>Torneo</small><strong>{{ $tournamentInfo['name'] }}</strong></div>
            <div class="fq-info-item"><small>Sede</small><strong>{{ $tournamentInfo['venue'] }}</strong></div>
            <div class="fq-info-item"><small>Fecha</small><strong>{{ $tournamentInfo['date'] }}</strong></div>
            <div class="fq-info-item"><small>Tipo</small><strong>{{ $tournamentInfo['type'] }} · {{ $tournamentInfo['official'] }}</strong></div>
            <div class="fq-info-item"><small>Modalidad</small><strong>{{ $tournamentInfo['modality'] }}</strong></div>
        </section>
        <div class="fq-summary">
            <div class="fq-card"><div class="fq-label">Estado</div><div class="fq-value">{{ match($competition->status){'published'=>'Publicada','in_progress'=>'En curso','completed'=>'Finalizada',default=>'Borrador'} }}</div></div>
            <div class="fq-card"><div class="fq-label">Jugadores disponibles</div><div class="fq-value">{{ $competition->entries->where('status','!=','withdrawn')->count() }}</div></div>
            <div class="fq-card"><div class="fq-label">Horarios</div><div class="fq-value">{{ $entriesBySlot->count() }}</div></div>
            <div class="fq-card"><div class="fq-label">Fases</div><div class="fq-value">{{ $competition->phases->count() }}</div></div>
        </div>

        @php
            $enabledPhases = $competition->phases->where('is_enabled', true)->values();
        @endphp
        <div x-data="{ activePhase: @js((string) optional($enabledPhases->first())->id) }">
        <div class="fq-phases" role="tablist" aria-label="Fases del torneo">
            @foreach($competition->phases as $phase)
                <button type="button" class="fq-phase fq-phase-tab" :class="activePhase === @js((string)$phase->id) && 'is-active'" @click="activePhase = @js((string)$phase->id)" style="{{ $phase->is_enabled ? '' : 'opacity:.48;pointer-events:none' }}">
                    <span class="fq-label">Fase {{ $phase->sort_order }}{{ $phase->is_enabled ? '' : ' · Deshabilitada' }}</span>
                    <strong>{{ $phase->name }}</strong>
                    <small>{{ match($phase->status){'ready'=>'Preparada','in_progress'=>'En curso','completed'=>'Finalizada',default=>'Pendiente'} }}</small>
                </button>
            @endforeach
            <button type="button" class="fq-phase fq-phase-tab" :class="activePhase === 'positions' && 'is-active'" @click="activePhase = 'positions'">
                <span class="fq-label">Clasificación</span><strong>Posiciones finales</strong><small>Puntuación del torneo</small>
            </button>
        </div>

        @php
            $pendingCount = $entriesByPhase->sum(fn($entries) => $entries->count());
        @endphp
        <details class="fq-pending">
            <summary>
                <span>Jugadores pendientes de asignación</span>
                <span style="margin-left:auto;color:#718096;font-size:.78rem;font-weight:700">{{ $pendingCount }} jugadores</span>
            </summary>
            <div class="fq-pending-content">
                <div class="fq-slots">
                    @forelse($entriesByPhase as $phaseName => $entries)
                        <section class="fq-slot">
                            <div style="display:flex;justify-content:space-between;gap:.5rem;margin-bottom:.35rem"><strong>{{ $phaseName }}</strong><span>{{ $entries->count() }}</span></div>
                            @foreach($entries->sortBy(fn($entry) => $entry->player?->last_name) as $entry)
                                <div class="fq-player"><span>{{ $entry->player?->full_name }}<br><small>{{ $entry->player?->category?->name ?? 'SIN CATEGORÍA' }} · {{ $entry->sourceSlot?->name ?? 'SIN HORARIO' }}</small></span><span class="fq-rank">RG {{ $entry->national_ranking_snapshot ?? '—' }}</span></div>
                            @endforeach
                        </section>
                    @empty
                        <div class="fq-empty">Todos los jugadores disponibles ya fueron asignados.</div>
                    @endforelse
                </div>
            </div>
        </details>

        @php
            $zonePhase = $competition->phases->first(fn($phase) => $phase->phase_type === 'zones' && $phase->is_enabled);
        @endphp
        @if($zonePhase)
            <section class="fq-phase-panel" id="fq-section-{{ $zonePhase->id }}" x-show="activePhase === @js((string)$zonePhase->id)" x-cloak>
            <div class="fq-phase-heading">
                <h2>{{ $zonePhase->name }}</h2>
                <div class="fq-phase-actions">
                    @if($zonePhase->status !== 'completed'){{ ($this->createZonesAction)(['phaseId' => $zonePhase->id]) }}@endif
                    @include('filament.resources.tournaments.pages.partials.competition-section-tools', ['sectionId' => 'fq-section-'.$zonePhase->id, 'sectionTitle' => $zonePhase->name])
                </div>
            </div>
        @if($zonePhase->groups->isNotEmpty())
            @php
                $groupsBySlot = $zonePhase->groups->groupBy(fn($group) => (string)($group->slot_id ?? 0));
                $firstSlotKey = (string) $groupsBySlot->keys()->first();
            @endphp
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
                                    <div class="fq-zone-bracket-wrap">
                                    <div class="fq-zone-bracket">
                                        @php
                                            $firstPlace = $group->groupEntries->firstWhere('final_place', 1)?->entry?->player?->full_name;
                                            $secondPlace = $group->groupEntries->firstWhere('final_place', 2)?->entry?->player?->full_name;
                                        @endphp
                                        @foreach($group->matches->sortBy(fn($match) => [$match->round_number, $match->bracket_order]) as $match)
                                            @php
                                                $zoneMatchClass = match($match->label) {
                                                'Partido 1' => 'z-m1',
                                                'Partido 2' => 'z-m2',
                                                'Definición del primer puesto' => 'z-win',
                                                'Partido de recuperación', 'Perdedores' => 'z-loss',
                                                'Definición del segundo puesto' => 'z-second',
                                                default => '',
                                                };
                                                $matchSets = collect(data_get($match->set_scores, 'sets', []));
                                            @endphp
                                            <div class="fq-match-shell {{ $zoneMatchClass }}">
                                                <article class="fq-match">
                                                    <b class="fq-match-title">{{ $match->label === 'Partido de recuperación' ? 'Perdedores' : $match->label }}</b>
                                                    <div class="fq-match-player {{ $match->winner_entry_id === $match->player_one_entry_id ? 'is-winner' : '' }}">
                                                        <span>{{ $match->playerOne?->player?->full_name ?? ($match->is_bye ? 'LIBRE' : 'A definir') }}</span>
                                                        <span class="fq-set-points">
                                                            @forelse($matchSets as $set)<span class="fq-set-point">{{ $set['player_one_points'] ?? '—' }}</span>@empty<span class="fq-set-point">—</span>@endforelse
                                                        </span>
                                                    </div>
                                                    <div class="fq-match-player {{ $match->winner_entry_id === $match->player_two_entry_id ? 'is-winner' : '' }}">
                                                        <span>{{ $match->playerTwo?->player?->full_name ?? ($match->is_bye ? 'LIBRE' : 'A definir') }}</span>
                                                        <span class="fq-set-points">
                                                            @forelse($matchSets as $set)<span class="fq-set-point">{{ $set['player_two_points'] ?? '—' }}</span>@empty<span class="fq-set-point">—</span>@endforelse
                                                        </span>
                                                    </div>
                                                    @if($match->scheduled_at)<small class="fq-match-time">{{ $match->scheduled_at->format('d/m/y · H:i') }}</small>@endif
                                                    @if($match->table_number)<small class="fq-match-time">Mesa {{ $match->table_number }}</small>@endif
                                                    @if($match->player_one_entry_id && $match->player_two_entry_id && !$match->is_bye)
                                                        <div class="fq-match-actions">
                                                            @if($match->status !== 'completed')
                                                                {{ ($this->callMatchAction)(['matchId' => $match->id]) }}
                                                            @endif
                                                            {{ ($this->recordResultAction)(['matchId' => $match->id]) }}
                                                            @if(in_array($match->status, ['in_progress', 'completed'], true))
                                                                {{ ($this->resetResultAction)(['matchId' => $match->id]) }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                </article>
                                            </div>
                                        @endforeach
                                        <div class="fq-place z-first"><b>1.er puesto</b><span>{{ $firstPlace ?? 'A definir' }}</span></div>
                                        <div class="fq-place z-runner"><b>2.º puesto</b><span>{{ $secondPlace ?? 'A definir' }}</span></div>
                                    </div>
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
        @else
            <div class="fq-empty">Todavía no hay zonas cargadas en esta fase.</div>
        @endif
            </section>
        @endif

        @foreach($competition->phases->where('phase_type', '!=', 'zones')->where('is_enabled', true) as $phase)
            @php
                $previousPhase = $competition->phases->where('is_enabled', true)->where('sort_order', '<', $phase->sort_order)->sortByDesc('sort_order')->first();
                $phaseSchedules = collect(data_get($phase->settings, 'schedules', []))->values();
            @endphp
            <section class="fq-phase-panel" id="fq-section-{{ $phase->id }}" x-show="activePhase === @js((string)$phase->id)" x-cloak>
                <div class="fq-phase-heading">
                    <h2>{{ $phase->name }}</h2>
                    @if($phase->status !== 'completed')
                        <div class="fq-phase-actions">
                            @if($phase->matches->isNotEmpty() || !$previousPhase || $previousPhase->status === 'completed')
                                {{ ($this->preparePhaseAction)(['phaseId' => $phase->id]) }}
                            @else
                                <small>Pendiente de finalizar {{ $previousPhase->name }}</small>
                            @endif
                            @if($phase->matches->isNotEmpty())
                                @foreach($phaseSchedules as $scheduleIndex => $schedule)
                                    {{ ($this->schedulePhaseAction)(['phaseId' => $phase->id, 'scheduleIndex' => $scheduleIndex]) }}
                                @endforeach
                            @endif
                            @include('filament.resources.tournaments.pages.partials.competition-section-tools', ['sectionId' => 'fq-section-'.$phase->id, 'sectionTitle' => $phase->name])
                        </div>
                    @else
                        @include('filament.resources.tournaments.pages.partials.competition-section-tools', ['sectionId' => 'fq-section-'.$phase->id, 'sectionTitle' => $phase->name])
                    @endif
                </div>
                @include('filament.resources.tournaments.pages.partials.competition-bracket', ['matches' => $phase->matches])
            </section>
        @endforeach
        <section class="fq-phase-panel" id="fq-section-positions" x-show="activePhase === 'positions'" x-cloak>
            <div class="fq-phase-heading"><h2>Posiciones finales</h2>@include('filament.resources.tournaments.pages.partials.competition-section-tools', ['sectionId' => 'fq-section-positions', 'sectionTitle' => 'Posiciones finales'])</div>
            @php $finalEntries = $competition->entries->whereNotNull('final_position')->sortBy('final_position'); @endphp
            @if($finalEntries->isEmpty())
                <div class="fq-empty">Las posiciones se completan a medida que finaliza la participación de cada jugador.</div>
            @else
                <div class="fq-standings-wrap"><table class="fq-standings">
                    <thead><tr><th>Pos</th><th>Jugador</th><th>Club</th><th>Cat</th><th>Instancia</th><th>DS</th><th>Prom</th><th>Puntos</th><th>Acción</th></tr></thead>
                    <tbody>@foreach($finalEntries as $entry)
                    @php
                        $performance = $this->finalPerformance($entry);
                    @endphp
                    <tr>
                        <td>{{ $entry->final_position }}</td><td>{{ $entry->player?->full_name }}</td>
                        <td>{{ $entry->player?->club?->short_name ?: $entry->player?->club?->name }}</td>
                        <td>{{ $entry->player?->category?->code ?: $entry->player?->category?->name }}</td>
                        <td>{{ $this->finalInstanceLabel($entry) }}</td>
                        <td>{{ $performance['sets_difference'] }}</td><td>{{ $performance['average'] }}</td>
                        <td>{{ $entry->registration?->points ?? '—' }}</td>
                        <td>{{ ($this->editFinalScoreAction)(['entryId' => $entry->id]) }}</td>
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
        </section>
        </div>

        @if($publicJsonUrl)
            <div class="fq-card" style="margin-top:1rem">
                <div class="fq-label">Resultados para el público</div>
                <a href="{{ $publicPageUrl }}" target="_blank" style="color:#3b82f6;font-weight:800">Ver resultados públicos</a>
                <br><small><a href="{{ $publicJsonUrl }}" target="_blank" style="color:#718096;word-break:break-all">Servicio JSON para WordPress</a></small>
            </div>
        @endif
    @endif
    <script>
        window.fqTournamentInfo = @json($tournamentInfo ?? []);
        window.fqSectionText = title => `${window.fqTournamentInfo.name}\n${title}\nSede: ${window.fqTournamentInfo.venue}\nFecha: ${window.fqTournamentInfo.date}\nTipo: ${window.fqTournamentInfo.type} · ${window.fqTournamentInfo.official}\nModalidad: ${window.fqTournamentInfo.modality}`;
        window.fqShareSection = async title => {
            const payload = {title: `${window.fqTournamentInfo.name} · ${title}`, text: window.fqSectionText(title), url: @json($publicPageUrl ?? request()->fullUrl())};
            if (navigator.share) return navigator.share(payload);
            await navigator.clipboard.writeText(`${payload.text}\n${payload.url}`);
            alert('La información fue copiada para compartir.');
        };
        window.fqPrintSection = (id, title) => {
            const section = document.getElementById(id); if (!section) return;
            const info = window.fqTournamentInfo, win = window.open('', '_blank', 'width=1200,height=800');
            const logo = info.logo ? `<img src="${info.logo}" alt="Logo">` : '';
            win.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${info.name} · ${title}</title><style>@@page{size:landscape;margin:10mm}body{font:12px Arial;color:#17253a}header{display:grid;grid-template-columns:80px 1fr;gap:18px;align-items:center;border-bottom:3px solid #173d7c;padding-bottom:10px;margin-bottom:16px}header img{max-width:72px;max-height:72px}h1{margin:0 0 7px}.meta{display:flex;gap:18px;flex-wrap:wrap}.meta span{display:block}.fq-phase-actions,.fq-match-actions,button{display:none!important}.fq-bracket-scroll,.fq-zone-bracket-wrap,.fq-standings-wrap{overflow:visible!important}.fq-bracket{min-width:0!important}.fq-zone{break-inside:avoid;margin-bottom:10px}.fq-zone-body{display:grid;grid-template-columns:1.2fr .8fr;gap:10px}.fq-zone-bracket{min-width:0!important}.fq-standings{width:100%;border-collapse:collapse}.fq-standings th,.fq-standings td{border:1px solid #aaa;padding:4px}.fq-match{border:1px solid #aaa;border-left:3px solid #2464ce;padding:5px}.fq-round{display:flex;flex-direction:column;justify-content:space-around;gap:6px}</style></head><body><header>${logo}<div><h1>${info.name}</h1><div class="meta"><span><b>Sección:</b> ${title}</span><span><b>Sede:</b> ${info.venue}</span><span><b>Fecha:</b> ${info.date}</span><span><b>Tipo:</b> ${info.type} · ${info.official}</span><span><b>Modalidad:</b> ${info.modality}</span></div></div></header>${section.innerHTML}</body></html>`);
            win.document.close(); win.onload = () => { win.focus(); win.print(); };
        };
    </script>
</x-filament-panels::page>
