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
                <button type="button" title="Compartir" aria-label="Compartir clasificados" onclick="fqShareQualified('{{ $dialogId }}', @js($shareTitle))">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.6 6.8-4.2M8.6 13.4l6.8 4.2"/></svg>
                </button>
                <button type="button" title="Imprimir" aria-label="Imprimir clasificados" onclick="fqPrintQualified('{{ $dialogId }}', @js($shareTitle))">
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
    <script>
        window.fqShareQualified = window.fqShareQualified || async function (dialogId, title) {
            const dialog = document.getElementById(dialogId);
            const text = `${title}\n\n${dialog?.querySelector('table')?.innerText || ''}`;
            if (navigator.share) {
                await navigator.share({ title, text, url: window.location.href });
                return;
            }
            await navigator.clipboard.writeText(`${text}\n${window.location.href}`);
            alert('La lista de clasificados fue copiada para compartir.');
        };
        window.fqPrintQualified = window.fqPrintQualified || function (dialogId, title) {
            const table = document.getElementById(dialogId)?.querySelector('table');
            if (! table) return;
            const safeTitle = String(title).replace(/[&<>'"]/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
            })[character]);
            const popup = window.open('', '_blank', 'width=1100,height=800');
            popup.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${safeTitle}</title><style>body{font-family:Arial,sans-serif;margin:28px;color:#17253a}h1{font-size:22px;margin:0 0 18px}table{width:100%;border-collapse:collapse;font-size:12px}th,td{padding:8px;border:1px solid #b8c3d1;text-align:left}th{background:#173d7c;color:#fff}@page{size:landscape;margin:14mm}</style></head><body><h1>${safeTitle}</h1>${table.outerHTML}</body></html>`);
            popup.document.close();
            popup.focus();
            setTimeout(() => { popup.print(); popup.close(); }, 250);
        };
    </script>
@endif
