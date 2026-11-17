<!doctype html><html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Resultados · FAB</title><link rel="stylesheet" href="{{ asset('fab-mobile/app.css').'?v='.filemtime(base_path('public/fab-mobile/app.css')) }}">
<script type="module" src="{{ asset('fab-mobile/results.js').'?v='.filemtime(base_path('public/fab-mobile/results.js')) }}"></script></head>
<body>@include('partials.mobile-header')<main class="wrap ranking" id="mobile-results" data-source="{{ route('five-quillas-competition.public', ['slug' => $slug ?? request()->route('slug')]) }}">
    <a class="back" href="{{ route('mobile-app') }}">‹ Calendario</a>
    <h1 id="results-title">Resultados</h1>
    <p id="results-status" role="status">Cargando resultados…</p>
    <label>Sección<select id="results-phase" disabled></select></label>
    <div id="results-filters"></div><div id="results-rows" class="tournament-development"></div>
</main>@include('partials.mobile-footer')
</body></html>
