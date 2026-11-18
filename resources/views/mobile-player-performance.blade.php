<!doctype html><html lang="es">
<head>@include('partials.mobile-pwa-head')<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Desempeño de jugador · FAB Movil</title><link rel="stylesheet" href="{{ asset('fab-mobile/app.css') }}"></head>
<body>@include('partials.mobile-header')<main class="wrap ranking">
    <a class="back" href="{{ route('mobile-players.index') }}">‹ Padrón de jugadores</a>
    <h1>Desempeño en torneos</h1>
    @if (!empty($livePublicPerformance))<p class="muted">Historial público de desempeño de FAB</p>@endif
    <h2>{{ $record->full_name }}</h2>
    <ul class="ranking-results">
        @forelse ($rows as $row)
            @php($score = $row->performance_score)
            <li class="ranking-row player-directory-row">
                <div class="ranking-player">
                    <strong>{{ $row->tournament->name }}</strong>
                    <small>{{ collect([$row->dateLabel ?? $row->tournament->end_date?->format('d/m/Y'), $row->tournament->type?->name, $row->tournament->discipline?->name])->filter()->join(' · ') }}</small>
                    <small>{{ $score['description'] }}</small>
                    @if (filled($row->participantsLabel ?? $row->tournament->registrations_count ?? null))
                        <small>Inscriptos: {{ $row->participantsLabel ?? $row->tournament->registrations_count }}</small>
                    @endif
                </div>
                <span class="ranking-points">{{ $row->pointsLabel ?? number_format((float) $score['points'], 2, ',', '.') }} pts</span>
            </li>
        @empty
            <li>No hay resultados para este jugador.</li>
        @endforelse
    </ul>
    <nav class="actions" aria-label="Páginas del desempeño">
        @if ($rows->previousPageUrl())<a class="button secondary icon-button" href="{{ $rows->previousPageUrl() }}" aria-label="Anterior" title="Anterior"><x-fab-icon name="previous" /></a>@endif
        @if ($rows->nextPageUrl())<a class="button secondary icon-button" href="{{ $rows->nextPageUrl() }}" aria-label="Siguiente" title="Siguiente"><x-fab-icon name="next" /></a>@endif
    </nav>
</main>@include('partials.mobile-footer')
</body></html>
