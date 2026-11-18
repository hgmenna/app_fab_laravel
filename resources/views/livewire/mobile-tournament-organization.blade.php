<div class="mobile-competition" wire:key="mobile-competition-{{ $this->record->id }}">
    <h2>{{ $this->record->name }}</h2>
    <details class="mobile-operation-menu"><summary>Configuración y publicación</summary>
        <div class="mobile-operation-actions">
            @foreach($this->getCachedHeaderActions() as $action){{ $action }}@endforeach
        </div>
    </details>
    @if($markerLink = $this->markerLink)
        <p class="mobile-link-status">Marcadores vinculados · {{ $markerLink->club_name ?: $markerLink->club_code }}</p>
        <livewire:tournament-marker-live :tournament-id="(int) $this->record->getKey()" />
    @endif
    @if($competition)
        @if($this->mountedActions === [])
            <livewire:competition-organization-updates :tournament-id="(int) $this->record->getKey()" :revision="$competitionViewRevision" :view-version="$competitionLayoutVersion" />
        @endif
        <div class="mobile-phase-picker">
            <label>Fase<select wire:model.live="mobilePhase">
                @foreach($mobilePhases as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                <option value="positions">Posiciones finales</option>
            </select></label>
            <x-filament::icon-button icon="heroicon-o-arrow-path" label="Actualizar resultados" wire:click="refreshOrganizationForEveryone" />
        </div>
        @if($mobilePhase === 'positions')
            <div class="mobile-operation-actions">{{ $this->saveFinalScoringAction }}</div>
            <div class="mobile-table-scroll"><table class="mobile-standings"><thead><tr><th>Pos.</th><th>Jugador</th><th>Puntos</th><th>Editar</th></tr></thead><tbody>
            @foreach($competition->entries->whereNotNull('final_position')->sortBy('final_position') as $entry)
                <tr><td>{{ $entry->final_position }}</td><td>{{ $entry->player?->full_name }}</td><td>{{ $this->proposedFinalPoints($entry) }}</td><td>{{ ($this->editFinalScoreAction)(['entryId' => $entry->id]) }}</td></tr>
            @endforeach
            </tbody></table></div>
        @elseif($selectedPhase)
            <div wire:key="mobile-phase-{{ $selectedPhase->id }}">
                <p class="muted">{{ match($selectedPhase->displayStatus()){'ready'=>'Preparada','in_progress'=>'En curso','completed'=>'Finalizada',default=>'Pendiente'} }}</p>
                <div class="mobile-operation-actions">
                    @if($selectedPhase->phase_type === 'zones')
                        {{ ($this->createZonesAction)(['phaseId' => $selectedPhase->id]) }}
                    @else
                        @if($selectedPhase->code === 'master-final' && data_get($competition->settings, 'format') === 'circuit_argentino')
                            {{ ($this->loadRegisteredMastersAction)(['phaseId' => $selectedPhase->id]) }}
                        @endif
                        {{ ($this->preparePhaseAction)(['phaseId' => $selectedPhase->id]) }}
                        {{ ($this->schedulePhaseAction)(['phaseId' => $selectedPhase->id]) }}
                        @if($selectedPhase->phase_type === 'slot_elimination'){{ ($this->configureSlotCrossingsAction)(['phaseId' => $selectedPhase->id]) }}@endif
                    @endif
                    @include('filament.resources.tournaments.pages.partials.competition-qualified-action', ['phase' => $selectedPhase])
                </div>
                @if($selectedPhase->phase_type === 'zones')
                    <label>Zona<select wire:model.live="mobileZone"><option value="">Todas las zonas</option>
                        @foreach($selectedPhase->groups as $group)<option value="{{ $group->id }}">{{ $group->name }} · {{ $group->slot?->name }}</option>@endforeach
                    </select></label>
                    @forelse($selectedPhase->groups->filter(fn($group) => !$mobileZone || (string)$group->id === $mobileZone) as $group)
                        <section class="mobile-zone" wire:key="mobile-zone-{{ $group->id }}">
                            <h3>{{ $group->name }} · {{ $group->slot?->name }}</h3>
                            @php
                                $drawPhase = $mobilePhases->where('sort_order', '>', $selectedPhase->sort_order)->first();
                                $drawPhase = $drawPhase?->phase_type === 'bracket' && $drawPhase->seeding_method === 'number_draw' ? $drawPhase : null;
                            @endphp
                            <p class="muted">Deslizá para seguir el desarrollo de la zona →</p>
                            <div class="mobile-zone-scroll"><div class="mobile-zone-rounds">
                                @foreach($group->matches->groupBy('round_number')->sortKeys() as $round => $matches)
                                    <section><h4>Ronda {{ $round }}</h4>
                                        @foreach($matches->sortBy('bracket_order') as $match)
                                            @include('mobile.organization-match', ['match' => $match])
                                        @endforeach
                                    </section>
                                @endforeach
                            </div></div>
                            @if($drawPhase)
                                @foreach($group->matches->whereIn('label', ['Definición del primer puesto', 'Definición del segundo puesto'])->where('status', 'completed')->whereNotNull('winner_entry_id') as $qualifiedMatch)
                                    <p>{{ $qualifiedMatch->winner?->player?->full_name }} · Sorteo: {{ data_get($drawPhase->settings, 'draw_seed_assignments.'.$qualifiedMatch->winner_entry_id) ?: 'Pendiente' }}
                                        {{ ($this->zoneDrawNumberAction)(['phaseId' => $drawPhase->id, 'entryId' => $qualifiedMatch->winner_entry_id]) }}
                                    </p>
                                @endforeach
                            @endif
                            <div class="mobile-table-scroll"><table class="mobile-standings"><thead><tr><th>Pos.</th><th>Jugador</th><th>PG</th><th>DS</th><th>PF</th><th>PC</th><th>Prom.</th></tr></thead><tbody>
                                @foreach($group->groupEntries->sortBy(fn($row) => $row->final_place ?: 99) as $row)
                                    <tr><td>{{ $row->final_place ?? '—' }}</td><td>{{ $row->entry?->player?->full_name }}</td><td>{{ $row->match_wins }}</td><td>{{ $row->sets_difference }}</td><td>{{ $row->points_for }}</td><td>{{ $row->points_against }}</td><td>{{ number_format((float)$row->points_average, 3, ',', '.') }}</td></tr>
                                @endforeach
                            </tbody></table></div>
                        </section>
                    @empty<p>Todavía no hay zonas. Usá Configurar zonas para prepararlas.</p>@endforelse
                @else
                    @if($selectedPhase->matches->contains(fn($match) => (int)$match->round_number === 0))
                        <label>Sección<select wire:model.live="mobileSection"><option value="bracket">Grilla final</option><option value="pairings">Emparejamientos</option></select></label>
                    @endif
                    <p class="muted">Deslizá para seguir los cruces. Los iconos de cada partido permiten organizarlo.</p>
                    @include('filament.resources.tournaments.pages.partials.competition-bracket', ['matches' => $selectedPhase->matches, 'pairingsOnly' => $mobileSection === 'pairings'])
                @endif
            </div>
        @endif
        @if($entriesByPhase->isNotEmpty())
            <details class="mobile-operation-menu"><summary>Jugadores pendientes de asignación ({{ $entriesByPhase->sum(fn($entries) => $entries->count()) }})</summary>
                @foreach($entriesByPhase as $name => $entries)<h3>{{ $name }}</h3><ul>@foreach($entries as $entry)<li>{{ $entry->player?->full_name }}</li>@endforeach</ul>@endforeach
            </details>
        @endif
    @else
        <p>Usá «Iniciar organización» en Configuración y publicación para preparar las fases con los inscriptos aprobados.</p>
    @endif
    <x-filament-actions::modals />
</div>
