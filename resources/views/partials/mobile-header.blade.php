@php($mobileUser = auth()->user())
<header class="masthead mobile-shell-header">
    <div class="wrap header-inner">
        <a href="{{ route('mobile-app') }}" aria-label="Calendario de FAB"><img src="{{ asset('fab-mobile/icon-192.png') }}" alt="FAB" width="36" height="36"></a>
        <div><strong>FAB Movil</strong><small>Federación Argentina de Billar</small></div>
        <details class="app-navigation">
            <summary aria-label="Abrir menú">☰ Menú</summary>
            <nav class="app-menu" aria-label="Navegación principal">
                <a href="{{ route('mobile-app') }}" data-section="calendario">Calendario deportivo</a>
                <details class="ranking-submenu"><summary>Rankings</summary>
                    <a href="{{ route('mobile-app') }}#ranking-5-quillas" data-section="ranking-5-quillas">5 Quillas</a>
                    <a href="{{ route('mobile-app') }}#ranking-3-bandas" data-section="ranking-3-bandas">3 Bandas</a>
                </details>
                @if (!empty($localPreview) || !empty($livePublicDirectory) || !empty($livePublicPerformance) || \Illuminate\Support\Facades\Gate::forUser($mobileUser ?? new \App\Auth\GuestUser)->allows('viewAny', \App\Models\Player::class) || !empty($livePublicDirectory) || !empty($livePublicPerformance))
                    <a href="{{ route('mobile-players.index') }}">Padrón y desempeño</a>
                @endif
                <a href="{{ route('mobile-account.show') }}">{{ $mobileUser ? 'Mi cuenta' : 'Ingresar a FAB' }}</a>
            </nav>
        </details>
    </div>
</header>
