@php
    $qualifiedRows = collect(app(\App\Services\FiveQuillasCompetitionService::class)->qualifiedParticipantRows($phase));
    $dialogId = 'fq-qualified-'.$phase->id;
@endphp
@if($qualifiedRows->isNotEmpty())
    <button type="button" class="fq-qualified-button" onclick="document.getElementById('{{ $dialogId }}').showModal()">
        Ver clasificados <span>{{ $qualifiedRows->count() }}</span>
    </button>
    <dialog id="{{ $dialogId }}" class="fq-qualified-dialog">
        <div class="fq-qualified-dialog-head">
            <div><small>Clasificados de {{ $phase->name }}</small><h3>Jugadores que disputan {{ $qualifiedRows->first()['next_phase'] }}</h3></div>
            <button type="button" onclick="this.closest('dialog').close()" aria-label="Cerrar">×</button>
        </div>
        <div class="fq-standings-wrap"><table class="fq-standings">
            <thead><tr><th>Posición</th><th>Jugador</th><th>Club</th><th>Ranking</th></tr></thead>
            <tbody>
            @foreach($qualifiedRows as $row)
                <tr><td>{{ $row['position'] }}</td><td>{{ $row['player'] }}</td><td>{{ $row['club'] ?: '—' }}</td><td>{{ $row['ranking'] ?: 'Sin ranking' }}</td></tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="fq-qualified-dialog-foot"><button type="button" onclick="this.closest('dialog').close()">Cerrar</button></div>
    </dialog>
@endif
