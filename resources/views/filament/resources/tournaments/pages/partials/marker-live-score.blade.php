@if($this->markerLink && in_array($match->status, ['called', 'in_progress'], true))
    <small class="fq-table-active" title="En vivo · Puntos del set y sets ganados"
        x-data="{ score: null }"
        x-on:marker-live.window="score = $event.detail.scores[@js((string) $match->id)] ?? null"
        x-show="score && score.status !== 'completed'" x-cloak
        x-text="score?.status === 'ready' ? 'En tablet' : (score ? score.points.join('–') + ' · S ' + score.setsWon.join('–') : '')"
    ></small>
@endif
