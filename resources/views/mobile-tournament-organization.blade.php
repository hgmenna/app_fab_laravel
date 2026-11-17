<!doctype html><html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Organización · FAB</title><link rel="stylesheet" href="{{ asset('fab-mobile/app.css') }}"></head>
<body>@include('partials.mobile-header')<main class="wrap ranking">
    <a class="back" href="{{ route('mobile-app', !empty($localPreview) ? ['perfil' => 'organizador'] : []) }}">‹ Calendario</a>
    <h1>Organización del torneo</h1>
    <h2>{{ $tournament->name }}</h2>
    @if (!empty($localPreview))<p class="muted">Perfil de demostración · Consulta de datos públicos</p>@endif
    @if ($phase)
        <form method="get" class="actions">
            @if (!empty($localPreview))<input type="hidden" name="perfil" value="organizador">@endif
            <label>Fase<select name="fase">
                @foreach ($phases as $item)<option value="{{ $item->id }}" @selected($item->id === $phase->id)>{{ $item->name }}</option>@endforeach
            </select></label>
            <button type="submit" class="icon-button" aria-label="Ver partidos" title="Ver partidos"><x-fab-icon name="search" /></button>
        </form>
        <ul class="ranking-results">
            @forelse ($matches as $match)
                <li class="tournament">
                    <strong>{{ $match->playerOne?->player?->full_name ?? 'A designar' }} · {{ $match->playerTwo?->player?->full_name ?? 'A designar' }}</strong>
                    <p class="muted">{{ $match->scheduled_at?->format('d/m/Y H:i') ?? $match->scheduleLabel ?? 'Sin horario' }} · {{ $match->table_number ? 'Mesa '.$match->table_number : 'Sin mesa asignada' }}</p>
                </li>
            @empty
                <li>Esta fase todavía no tiene partidos.</li>
            @endforelse
        </ul>
        <nav class="actions" aria-label="Páginas de partidos">
            @if ($matches->previousPageUrl())<a class="button secondary icon-button" href="{{ $matches->previousPageUrl() }}" aria-label="Anterior" title="Anterior"><x-fab-icon name="previous" /></a>@endif
            @if ($matches->nextPageUrl())<a class="button secondary icon-button" href="{{ $matches->nextPageUrl() }}" aria-label="Siguiente" title="Siguiente"><x-fab-icon name="next" /></a>@endif
        </nav>
    @else
        <p>El torneo todavía no tiene fases configuradas.</p>
    @endif
</main>@include('partials.mobile-footer')
</body></html>
