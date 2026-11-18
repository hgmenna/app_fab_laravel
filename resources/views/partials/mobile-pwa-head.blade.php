{{-- Metadatos comunes: mantienen calendario, cuenta, organización y resultados dentro de la PWA. --}}
<meta name="theme-color" content="#111e31">
<meta name="application-name" content="FAB Movil">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="FAB Movil">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="manifest" href="{{ asset('fab-mobile/manifest.webmanifest') }}">
<link rel="icon" type="image/png" href="{{ asset('fab-mobile/icon-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('fab-mobile/icon-192.png') }}">
<script type="module" src="{{ asset('fab-mobile/install.js').'?v='.filemtime(base_path('public/fab-mobile/install.js')) }}"></script>
