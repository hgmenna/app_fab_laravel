@php
    $matchesByRound = collect($matches)->groupBy('round_number')->sortKeys();
@endphp
@if($matchesByRound->isEmpty())
    <div class="fq-empty">Todavía no hay partidos preparados para esta sección.</div>
@else
    <div class="fq-bracket-scroll">
        <div class="fq-bracket" style="grid-template-columns:repeat({{ max($matchesByRound->count(), 1) }},minmax(12rem,1fr))">
            @foreach($matchesByRound as $roundMatches)
                <section class="fq-round">
                    @foreach($roundMatches->sortBy('bracket_order') as $match)
                        @php
                            $pair = $match->round_number === 1
                                ? data_get($match->phase?->settings, 'bracket_pairs.'.($match->bracket_order - 1), [])
                                : [];
                            // En la primera ronda el número pertenece al casillero
                            // fijo de la grilla. Corregir el jugador cambia solamente
                            // el nombre y nunca renumera las posiciones del partido.
                            $numberOne = $match->round_number === 1
                                ? ($pair[0] ?? '—')
                                : ($match->playerOne?->seed ?? '—');
                            $numberTwo = $match->round_number === 1
                                ? ($pair[1] ?? '—')
                                : ($match->playerTwo?->seed ?? '—');
                        @endphp
                        <div class="fq-match-shell">
                            <article class="fq-match">
                                <div class="fq-match-player {{ $match->winner_entry_id === $match->player_one_entry_id ? 'is-winner' : '' }}">
                                    <span><b>{{ $numberOne }}</b> · {{ $match->playerOne?->player?->full_name ?? 'LIBRE' }}</span>
                                    <span>{{ $match->status === 'completed' ? $match->player_one_computed_sets : '—' }}</span>
                                </div>
                                <div class="fq-match-player {{ $match->winner_entry_id === $match->player_two_entry_id ? 'is-winner' : '' }}">
                                    <span><b>{{ $numberTwo }}</b> · {{ $match->playerTwo?->player?->full_name ?? 'LIBRE' }}</span>
                                    <span>{{ $match->status === 'completed' ? $match->player_two_computed_sets : '—' }}</span>
                                </div>
                                @if($match->phase?->phase_type === 'slot_elimination' && $match->status === 'completed' && $match->winner)
                                    <div class="fq-crossing-winner">
                                        <span aria-hidden="true">🏆</span>
                                        <strong>{{ $match->winner?->player?->full_name }}</strong>
                                        <small>Clasificado a la siguiente fase</small>
                                    </div>
                                @endif
                                @if($match->scheduled_at)<small class="fq-match-time">{{ $match->scheduled_at->format('d/m/y · H:i') }}</small>@endif
                                @if($match->table_number)<small class="fq-match-time">Mesa {{ $match->table_number }}</small>@endif
                                @if($match->phase?->phase_type === 'bracket' && (int)$match->round_number === 1 && (in_array($match->status, ['pending', 'scheduled'], true) || $match->is_bye))
                                    <div class="fq-match-actions">
                                        {{ ($this->correctMatchParticipantsAction)(['matchId' => $match->id]) }}
                                        @if($match->player_one_entry_id && $match->player_two_entry_id && !$match->is_bye)
                                            @if($match->status !== 'completed'){{ ($this->callMatchAction)(['matchId' => $match->id]) }}@endif
                                            {{ ($this->recordResultAction)(['matchId' => $match->id]) }}
                                        @endif
                                    </div>
                                @elseif($match->player_one_entry_id && $match->player_two_entry_id && !$match->is_bye)
                                    <div class="fq-match-actions">
                                        @if($match->status !== 'completed'){{ ($this->callMatchAction)(['matchId' => $match->id]) }}@endif
                                        {{ ($this->recordResultAction)(['matchId' => $match->id]) }}
                                        @if(in_array($match->status, ['in_progress', 'completed'], true)){{ ($this->resetResultAction)(['matchId' => $match->id]) }}@endif
                                    </div>
                                @endif
                            </article>
                        </div>
                    @endforeach
                </section>
            @endforeach
        </div>
    </div>
@endif
