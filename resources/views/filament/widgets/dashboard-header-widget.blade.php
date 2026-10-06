<x-filament-widgets::widget>
    <section style="position:relative; overflow:hidden; border-radius:1.1rem; padding:1rem 1.25rem; background:linear-gradient(135deg,#172554 0%,#1e3a8a 48%,#0f766e 100%); box-shadow:0 14px 35px rgba(15,23,42,.20); color:#fff;">
        <div aria-hidden="true" style="position:absolute; width:18rem; height:18rem; right:-6rem; top:-9rem; border-radius:9999px; background:rgba(255,255,255,.08);"></div>
        <div aria-hidden="true" style="position:absolute; width:12rem; height:12rem; right:9rem; bottom:-9rem; border-radius:9999px; background:rgba(250,204,21,.10);"></div>

        <div style="position:relative; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem;">
            <div>
                <p style="margin:0 0 .25rem; font-size:.68rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:#fde68a;">Federación Argentina de Billar</p>
                <h1 style="margin:0; font-size:clamp(1.2rem,2vw,1.65rem); line-height:1.1; font-weight:800;">Panel deportivo institucional</h1>
            </div>

            <div style="display:flex; align-items:center; gap:.65rem; border:1px solid rgba(255,255,255,.18); border-radius:9999px; padding:.65rem 1rem; background:rgba(15,23,42,.25); backdrop-filter:blur(8px);">
                <x-filament::icon icon="heroicon-m-calendar-days" style="width:1.15rem; height:1.15rem; color:#fde68a;" />
                <span style="font-size:.82rem; font-weight:700; text-transform:capitalize;">{{ $today }}</span>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
