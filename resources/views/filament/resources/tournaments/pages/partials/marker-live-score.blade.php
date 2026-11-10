@php
    $pointsKey = $side === 0 ? 'player_one_points' : 'player_two_points';
    $receivesLiveScore = $this->markerLink && in_array($match->status, ['called', 'in_progress'], true);
@endphp
<span class="fq-set-points"
    @if($receivesLiveScore)
        x-data="{ score: null }"
        x-on:marker-live.window="score = $event.detail.scores[@js((string) $match->id)] ?? null"
    @endif
>
    @forelse($matchSets as $set)
        <span @class(['fq-set-point', 'is-set-winner' => isset($set[$pointsKey]) && (int) $set[$pointsKey] === $pointsPerSet])
            @if($receivesLiveScore) x-show="score?.status !== 'in_progress'" @endif
        >{{ $set[$pointsKey] ?? '—' }}</span>
    @empty
        <span class="fq-set-point" @if($receivesLiveScore) x-show="score?.status !== 'in_progress'" @endif>—</span>
    @endforelse
    @if($receivesLiveScore)
        <template x-for="(set, index) in (score?.status === 'in_progress' ? score.sets : [])" x-bind:key="index">
            <span class="fq-set-point" x-bind:class="{ 'is-set-winner': set[{{ $side }}] === {{ $pointsPerSet }} }"
                x-text="set[{{ $side }}]"></span>
        </template>
        <span class="fq-set-point fq-set-point-live" title="Puntos del set en juego"
            x-show="score?.status === 'in_progress'" x-cloak x-text="score?.points[{{ $side }}] ?? ''"></span>
    @endif
</span>
