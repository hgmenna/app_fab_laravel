<!doctype html><html lang="es">
<head>@include('partials.mobile-pwa-head')<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Padrón de jugadores · FAB</title><link rel="stylesheet" href="{{ asset('fab-mobile/app.css') }}"></head>
<body>@include('partials.mobile-header')<main class="wrap ranking">
    <a class="back" href="{{ route('mobile-app') }}">‹ Calendario</a>
    <h1>Padrón de jugadores</h1>
    @if (!empty($livePublicDirectory))<p class="muted">Padrón público de FAB@if (isset($directoryTotal)) · {{ number_format($directoryTotal, 0, ',', '.') }} resultados@endif</p>@endif
    @if (!empty($localPreview))<p class="muted">El simulador muestra una muestra del ranking público. El padrón completo y los filtros de afiliación y estado requieren conexión a la base de FAB.</p>@endif
    <form class="directory-filters" method="get">
        <label>Buscar jugador<input type="search" name="buscar" maxlength="120" value="{{ $search }}" placeholder="Nombre o apellido"></label>
        <details class="calendar-filter" @if (array_filter(array_diff_key($filters, array_flip(['buscar', 'estado', 'afiliacion']))) || ($filters['estado'] ?? 'todos') !== 'todos' || ($filters['afiliacion'] ?? 'todos') !== 'todos') open @endif>
            <summary>Filtrar</summary>
            <div class="advanced">
                @foreach (['disciplina' => 'Disciplina', 'categoria' => 'Categoría', 'federacion' => 'Federación de afiliación'] as $field => $label)
                    <label>{{ $label }}
                        <select name="{{ $field }}[]" multiple>
                            @foreach ($directoryOptions[$field] as $id => $name)
                                <option value="{{ $id }}" @selected(in_array((string)$id, array_map('strval', $filters[$field] ?? []), true))>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
                <label>Estado<select name="estado" @disabled(!empty($localPreview))>
                    @foreach (['todos'=>'Todos', 'activos'=>'Activos', 'inactivos'=>'Inactivos'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['estado'] ?? 'todos') === $value)>{{ $label }}</option>
                    @endforeach
                </select></label>
                <label>Afiliado año actual<select name="afiliacion" @disabled(!empty($localPreview))>
                    @foreach (['todos'=>'Todos', 'habilitados'=>'Habilitados', 'inhabilitados'=>'Inhabilitados'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['afiliacion'] ?? 'todos') === $value)>{{ $label }}</option>
                    @endforeach
                </select></label>
            </div>
        </details>
        <div class="actions"><button type="submit" class="icon-button" aria-label="Buscar" title="Buscar"><x-fab-icon name="search" /></button><a class="icon-button button secondary" href="{{ route('mobile-players.index') }}" aria-label="Limpiar" title="Limpiar"><x-fab-icon name="reset" /></a></div>
    </form>
    <ul class="ranking-results">
        @forelse ($players as $player)
            @php($rank = ($rankings ?? collect())->get($player->id))
            <li class="directory-entry">
                <details>
                    <summary class="directory-summary">
                        <span><strong>{{ $player->full_name }}</strong>
                            <small>{{ collect([$player->club?->name, $player->discipline?->name])->filter()->join(' · ') }}</small>
                        </span>
                    </summary>
                    <dl class="tournament-facts">
                        <div><dt>Club o sala</dt><dd>{{ $player->club?->name ?? 'Independiente' }}</dd></div>
                        <div><dt>Categoría</dt><dd>{{ $player->category?->name ?? 'Sin categoría' }}</dd></div>
                        <div class="full-width"><dt>Disciplina y federación de afiliación</dt>
                            <dd>{{ $player instanceof \App\Models\Player ? $player->affiliationLabel() : ($player->federationLabel ?? $player->discipline?->name) }}</dd></div>
                        <div><dt>Torneos disputados</dt><dd>{{ $player->played_count ?? '—' }}</dd></div>
                        <div><dt>Promedio de puntos</dt><dd>{{ isset($player->average_points) ? number_format((float)$player->average_points, 2, ',', '.') : '—' }}</dd></div>
                        <div><dt>Posición general</dt><dd>{{ $rank?->RG ?? '—' }}</dd></div>
                        <div><dt>Posición en categoría</dt><dd>{{ $rank?->RC ?? '—' }}</dd></div>
                        <div><dt>Categoría del ranking</dt><dd>{{ $rank?->category ?? '—' }}</dd></div>
                        <div><dt>Estado</dt><dd>{{ isset($player->is_active) ? ($player->is_active ? 'Activo' : 'Inactivo') : '—' }}</dd></div>
                        <div><dt>Afiliación actual</dt><dd>{{ isset($player->is_enabled_to_compete) ? ($player->is_enabled_to_compete ? 'Habilitado' : 'Inhabilitado') : '—' }}</dd></div>
                    </dl>
                </details>
                <a class="button secondary icon-button directory-performance" href="{{ route('mobile-players.performance', !empty($livePublicDirectory) ? ['player' => $player->id, 'nombre' => $player->full_name] : $player->id) }}" aria-label="Desempeño de {{ $player->full_name }}" title="Desempeño"><x-fab-icon name="performance" /></a>
            </li>
        @empty
            <li>No hay jugadores para esta búsqueda.</li>
        @endforelse
    </ul>
    <nav class="actions" aria-label="Páginas del padrón">
        @if ($players->previousPageUrl())<a class="button secondary icon-button" href="{{ $players->previousPageUrl() }}" aria-label="Anterior" title="Anterior"><x-fab-icon name="previous" /></a>@endif
        @if ($players->nextPageUrl())<a class="button secondary icon-button" href="{{ $players->nextPageUrl() }}" aria-label="Siguiente" title="Siguiente"><x-fab-icon name="next" /></a>@endif
    </nav>
</main>@include('partials.mobile-footer')
</body></html>
