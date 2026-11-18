<!doctype html><html lang="es" class="dark">
<head>@include('partials.mobile-pwa-head')<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>Organización · FAB</title>
@livewireStyles
@filamentStyles
{{ filament()->getTheme()->getHtml() }}
<link rel="stylesheet" href="{{ asset('fab-mobile/app.css').'?v='.filemtime(base_path('public/fab-mobile/app.css')) }}">
@include('mobile.organization-style')
</head>
<body class="mobile-organization-shell">@include('partials.mobile-header')
<main class="wrap mobile-organization">
<a class="back" href="{{ route('mobile-app') }}">‹ Calendario</a>
<h1>Organización del torneo</h1>
<livewire:mobile-tournament-organization :record="$tournament->getKey()" />
</main>
@include('partials.mobile-footer')
@livewire('notifications')
@filamentScripts(withCore: true)
@livewireScripts
<script>@include('partials.competition-pdf-script')</script>
</body></html>
