<x-filament-widgets::widget>
    <section style="display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.2rem; border:1px solid rgba(148,163,184,.22); border-radius:1rem; background:rgba(15,23,42,.04);">
        <div style="min-width:10rem;">
            <p style="margin:0; font-size:.72rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; color:#64748b;">Calendario</p>
            <p style="margin:.2rem 0 0; font-size:1.05rem; font-weight:800;">{{ $total }} torneos próximos</p>
        </div>

        <div style="display:flex; flex:1; flex-wrap:wrap; justify-content:center; gap:.5rem;">
            @forelse ($disciplines as $discipline)
                <span style="display:inline-flex; align-items:center; gap:.45rem; padding:.45rem .7rem; border-radius:9999px; background:#eff6ff; color:#1e3a8a; font-size:.78rem; font-weight:700;">
                    {{ $discipline->name }}
                    <strong style="display:grid; place-items:center; min-width:1.45rem; height:1.45rem; border-radius:9999px; background:#1d4ed8; color:#fff;">{{ $discipline->upcoming_count }}</strong>
                </span>
            @empty
                <span style="color:#64748b; font-size:.85rem;">No hay torneos próximos.</span>
            @endforelse
        </div>

        <a href="{{ $tournamentsUrl }}" style="white-space:nowrap; padding:.55rem .85rem; border-radius:.65rem; background:#1d4ed8; color:#fff; font-size:.78rem; font-weight:800; text-decoration:none;">Ver gestión de torneos</a>
    </section>
</x-filament-widgets::widget>
