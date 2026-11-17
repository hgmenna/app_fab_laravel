<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#111e31">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>FAB · Calendario deportivo</title>
    <link rel="manifest" href="{{ asset('fab-mobile/manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('fab-mobile/icon-192.png') }}">
    <link rel="stylesheet" href="{{ asset('fab-mobile/app.css') . '?v=' . filemtime(public_path('fab-mobile/app.css')) }}">
    <script type="module" src="{{ asset('fab-mobile/app.js') . '?v=' . filemtime(public_path('fab-mobile/app.js')) }}"></script>
</head>
<body>
<a class="skip" href="#calendario">Ir al calendario</a>
<header class="masthead">
    <div class="wrap header-inner">
        <img src="{{ asset('fab-mobile/icon-192.png') }}" alt="" width="42" height="42">
        <div><strong aria-label="Federación Argentina de Billar">FAB</strong><small>Federación Argentina de Billar</small></div>
        <details class="app-navigation">
            <summary>☰ Menú</summary>
            <nav class="app-menu" aria-label="Navegación principal">
                <a href="#calendario" data-section="calendario" aria-current="page">Calendario deportivo</a>
                <details class="ranking-submenu">
                    <summary>Rankings</summary>
                    <a href="#ranking-5-quillas" data-section="ranking-5-quillas">5 Quillas</a>
                    <a href="#ranking-3-bandas" data-section="ranking-3-bandas">3 Bandas</a>
                </details>
                @if ($playersUrl)<a href="{{ $playersUrl }}">Padrón y desempeño</a>@endif
                <a href="{{ $manageUrl }}">{{ $canManage ? 'Mi cuenta' : 'Ingresar a FAB' }}</a>
            </nav>
        </details>
    </div>
</header>
<main class="wrap">
<section id="calendario" aria-labelledby="calendar-title">
    <h1 id="calendar-title">Calendario deportivo</h1>
    <form id="filters" data-endpoint="{{ route('mobile-app.calendar') }}" data-results="{{ route('mobile-results.show', ['slug' => 'result-slug']) }}" data-registration="{{ route('mobile-registration.show', ['tournament' => '__TOURNAMENT__']) }}">
        <label class="search">Buscar torneo o sede<input type="search" name="buscar" maxlength="120" placeholder="Nombre del torneo o del club"></label>
        <details class="calendar-filter">
            <summary>Filtrar</summary>
            <div class="advanced">
        <label>Estado<select name="estado">
            <option value="proximos">Próximos</option><option value="en-curso">En curso</option>
            <option value="finalizados">Finalizados</option><option value="todos">Todos</option>
        </select></label>
        <label>Disciplina<select name="disciplina"><option value="">Todas</option>
            @foreach ($disciplines as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
        </select></label>

                <label>Año<select name="anio"><option value="">Todos</option></select></label>
                <label>Desde<input type="date" name="desde"></label>
                <label>Hasta<input type="date" name="hasta"></label>
                <label>Tipo<select name="tipo"><option value="">Todos</option>
                    @foreach ($types as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                </select></label>
                <label>Categoría<select name="categoria"><option value="">Todas</option>
                    @foreach ($categories as $item)<option data-discipline="{{ $item->discipline_id }}" value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                </select></label>
            </div>
            <div class="actions"><button type="submit" class="icon-button" aria-label="Aplicar filtros" title="Aplicar filtros"><x-fab-icon name="check" /></button><button type="reset" class="icon-button secondary" aria-label="Limpiar" title="Limpiar"><x-fab-icon name="reset" /></button></div>
        </details>
    </form>
    <p id="calendar-status" class="muted" role="status"></p>
    <section id="tournaments" aria-label="Torneos"></section>
    <noscript>Activá JavaScript para consultar el calendario.</noscript>
</section>
    <section id="ranking" class="ranking" hidden
        data-five="{{ url('/ranking-5-quillas.json') }}"
        data-three="{{ url('/ranking-carambola-3-bandas.json') }}" aria-labelledby="ranking-title">
        <a class="back" href="#calendario">‹ Calendario</a>
        <h1 id="ranking-title">Ranking</h1>
        <div class="ranking-filters">
            <label>Buscar jugador<input id="ranking-search" type="search" placeholder="Nombre o apellido"></label>
            <label>Categoría<select id="ranking-category"><option value="">Todas</option></select></label>
        </div>
        <p id="ranking-status" class="muted" role="status"></p>
        <table class="ranking-table">
            <thead><tr><th scope="col">Pos. general</th><th scope="col">Pos. categoría</th><th scope="col">Jugador</th><th scope="col">Puntos</th></tr></thead>
            <tbody id="ranking-results"></tbody>
        </table>
        <button id="ranking-more" type="button" class="icon-button secondary" hidden aria-label="Ver más jugadores" title="Ver más jugadores"><x-fab-icon name="more" /></button>
    </section>
    <details class="install">
        <summary>Instalar en el celular</summary>
        <p><strong>Android</strong> · En Chrome, abrí el menú y elegí <em>Instalar aplicación</em> o <em>Agregar a pantalla principal</em>.</p>
        <p><strong>iPhone</strong> · En Safari, tocá <em>Compartir</em> y elegí <em>Agregar a pantalla de inicio</em>.</p>
    </details>
</main>

@include('partials.mobile-footer')
</body>
</html>
