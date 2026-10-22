@php
    $qualifiedRows = collect(app(\App\Services\FiveQuillasCompetitionService::class)->qualifiedParticipantRows($phase));
    $dialogId = 'fq-qualified-'.$phase->id;
    $classificationMethod = $qualifiedRows->first()['classification_method'] ?? 'manual';
    $shareTitle = 'Clasificados de '.$phase->name.' · '.($phase->competition?->tournament?->name ?? 'Torneo');
@endphp
@if($qualifiedRows->isNotEmpty())
    <button type="button" class="fq-qualified-button" onclick="document.getElementById('{{ $dialogId }}').showModal()">
        Ver clasificados <span>{{ $qualifiedRows->count() }}</span>
    </button>
    <dialog id="{{ $dialogId }}" class="fq-qualified-dialog">
        <div class="fq-qualified-dialog-head">
            <div><small>Clasificados de {{ $phase->name }}</small><h3>Jugadores que disputan {{ $qualifiedRows->first()['next_phase'] }}</h3></div>
            <div class="fq-qualified-dialog-actions">
                <button type="button" title="Compartir" aria-label="Compartir clasificados" data-share-title="{{ $shareTitle }}" onclick="fqShareQualified('{{ $dialogId }}', this.dataset.shareTitle)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.6 6.8-4.2M8.6 13.4l6.8 4.2"/></svg>
                </button>
                <button type="button" title="Imprimir" aria-label="Imprimir clasificados" data-share-title="{{ $shareTitle }}" onclick="fqPrintQualified('{{ $dialogId }}', this.dataset.shareTitle)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 8V3h10v5M7 17h10v4H7z"/><path d="M6 17H4V9h16v8h-2M16 12h1"/></svg>
                </button>
                <button class="fq-dialog-close" type="button" onclick="this.closest('dialog').close()" aria-label="Cerrar">×</button>
            </div>
        </div>
        <div class="fq-standings-wrap"><table class="fq-standings">
            <thead><tr>
                <th>Posición</th><th>Jugador</th><th>Club</th>
                @if($classificationMethod === 'previous_results')
                    <th>Pos. zona</th><th>DS</th><th>Promedio</th>
                @elseif($classificationMethod === 'national_ranking')
                    <th>Ranking</th>
                @elseif(in_array($classificationMethod, ['number_draw', 'automatic_draw'], true))
                    <th>N.º sorteo</th>
                @endif
            </tr></thead>
            <tbody>
            @foreach($qualifiedRows as $row)
                <tr>
                    <td>{{ $row['position'] }}</td><td>{{ $row['player'] }}</td><td>{{ $row['club'] ?: '—' }}</td>
                    @if($classificationMethod === 'previous_results')
                        <td>{{ $row['zone_position'] ?: '—' }}</td>
                        <td>{{ $row['sets_difference'] ?? '—' }}</td>
                        <td>{{ $row['average'] !== null ? number_format((float) $row['average'], 3, ',', '.') : '—' }}</td>
                    @elseif($classificationMethod === 'national_ranking')
                        <td>{{ $row['ranking'] ?: 'Sin ranking' }}</td>
                    @elseif(in_array($classificationMethod, ['number_draw', 'automatic_draw'], true))
                        <td>{{ $row['draw_number'] ?: '—' }}</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="fq-qualified-dialog-foot"><button type="button" onclick="this.closest('dialog').close()">Cerrar</button></div>
    </dialog>
@endif
