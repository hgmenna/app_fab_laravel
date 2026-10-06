<x-filament-widgets::widget>
    <section class="fab-upcoming-summary" style="display:flex; align-items:center; justify-content:space-between; gap:.65rem; padding:.6rem .8rem; border:1px solid rgba(148,163,184,.22); border-radius:.85rem; background:rgba(15,23,42,.04);">
        <div style="min-width:10rem;">
            <p style="margin:0; font-size:.72rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; color:#64748b;">Calendario</p>
            <p style="margin:.1rem 0 0; font-size:.92rem; font-weight:800;">{{ $total }} torneos próximos</p>
        </div>

        <div class="fab-upcoming-disciplines" style="display:flex; flex:1; flex-wrap:wrap; justify-content:center; gap:.35rem;">
            @forelse ($disciplines as $discipline)
                <span style="display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .5rem; border-radius:9999px; background:#eff6ff; color:#1e3a8a; font-size:.73rem; font-weight:700;">
                    {{ $discipline->name }}
                    <strong style="display:grid; place-items:center; min-width:1.2rem; height:1.2rem; border-radius:9999px; background:#1d4ed8; color:#fff;">{{ $discipline->upcoming_count }}</strong>
                </span>
            @empty
                <span style="color:#64748b; font-size:.85rem;">No hay torneos próximos.</span>
            @endforelse
        </div>

        <a class="fab-upcoming-link" href="{{ $tournamentsUrl }}" style="white-space:nowrap; padding:.4rem .65rem; border-radius:.55rem; background:#1d4ed8; color:#fff; font-size:.73rem; font-weight:800; text-decoration:none;">Ver gestión de torneos</a>
    </section>
</x-filament-widgets::widget>
