<article class="mobile-match" wire:key="mobile-match-{{ $match->id }}">
    @php
        $matchSets = collect(data_get($match->set_scores, 'sets', []));
        $pointsPerSet = (int) $match->phase?->points_per_set;
    @endphp
    <strong class="mobile-match-label">{{ $match->label }}</strong>
    @foreach([[$match->playerOne, $match->player_one_entry_id], [$match->playerTwo, $match->player_two_entry_id]] as $side => [$entry, $entryId])
        <div class="fq-match-player {{ $entryId && $match->winner_entry_id === $entryId ? 'is-winner' : '' }}">
            <span>{{ $entry?->player?->full_name ?? 'A designar' }}</span>
            @include('filament.resources.tournaments.pages.partials.marker-live-score', ['side' => $side])
        </div>
    @endforeach
    <p class="muted">{{ $match->scheduled_at?->format('d/m H:i') }} {{ $match->table_number ? '· Mesa '.$match->table_number : '' }}</p>
    <div class="mobile-match-actions">
        @if($match->player_one_entry_id && $match->player_two_entry_id && !$match->is_bye)
            @if($match->status !== 'completed'){{ ($this->callMatchAction)(['matchId' => $match->id]) }}@endif
            @if($match->status === 'called')
                @if($this->markerLink && !in_array((int)$match->id, $this->sentMarkerMatchIds, true)){{ ($this->sendMarkerMatchAction)(['matchId' => $match->id]) }}@endif
                {{ ($this->cancelMatchCallAction)(['matchId' => $match->id]) }}
            @endif
            {{ ($this->recordResultAction)(['matchId' => $match->id]) }}
            @if(in_array($match->status, ['in_progress', 'completed'], true)){{ ($this->resetResultAction)(['matchId' => $match->id]) }}@endif
        @endif
    </div>
</article>
