<x-filament-widgets::widget>
    <style>
        .fab-compact-dashboard .fi-page-content,
        .fab-compact-dashboard .fi-sc,
        .fab-compact-dashboard .fi-sc-grid { gap:.55rem !important; }
        .fab-compact-dashboard .fi-page-content { padding-block:.55rem !important; }
        .fab-compact-dashboard .fi-wi-stats-overview-stat { padding:.65rem .8rem !important; }
        .fab-compact-dashboard .fi-wi-stats-overview-stat-content { gap:.2rem !important; }
        .fab-compact-dashboard .fi-wi-stats-overview-stat-value { font-size:1.3rem !important; line-height:1.15 !important; }

        @media (min-width:1024px) {
            .fab-compact-dashboard .fab-ranking-card {
                min-height:max(10rem, calc(100vh - 29rem));
                display:flex;
                flex-direction:column;
                justify-content:space-between;
                gap:1rem;
            }
        }

        @media (max-width:767px) {
            .fab-compact-dashboard .fi-page-content,
            .fab-compact-dashboard .fi-sc,
            .fab-compact-dashboard .fi-sc-grid { gap:.75rem !important; }
            .fab-dashboard-heading-row { align-items:stretch !important; }
            .fab-dashboard-date { justify-content:center; width:100%; box-sizing:border-box; }
            .fab-upcoming-summary { flex-direction:column; align-items:stretch !important; padding:.8rem !important; }
            .fab-upcoming-summary > div { min-width:0 !important; text-align:center; }
            .fab-upcoming-disciplines { justify-content:center !important; }
            .fab-upcoming-link { display:block; width:100%; box-sizing:border-box; text-align:center; }
            .fab-ranking-grid { grid-template-columns:1fr !important; gap:.75rem !important; }
            .fab-ranking-card { min-height:0 !important; padding:.85rem !important; }
            .fab-ranking-card { gap:.8rem !important; }
            .fab-compact-dashboard .fi-wi-stats-overview-stat { padding:.85rem 1rem !important; }
            .fab-compact-dashboard .fi-wi-stats-overview-stat-value { font-size:1.45rem !important; }
        }
    </style>

    <section style="position:relative; overflow:hidden; border-radius:1rem; padding:.65rem .9rem; background:linear-gradient(135deg,#172554 0%,#1e3a8a 48%,#0f766e 100%); box-shadow:0 10px 26px rgba(15,23,42,.18); color:#fff;">
        <div aria-hidden="true" style="position:absolute; width:18rem; height:18rem; right:-6rem; top:-9rem; border-radius:9999px; background:rgba(255,255,255,.08);"></div>
        <div aria-hidden="true" style="position:absolute; width:12rem; height:12rem; right:9rem; bottom:-9rem; border-radius:9999px; background:rgba(250,204,21,.10);"></div>

        <div class="fab-dashboard-heading-row" style="position:relative; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.6rem;">
            <div>
                <p style="margin:0 0 .25rem; font-size:.68rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:#fde68a;">Federación Argentina de Billar</p>
                <h1 style="margin:0; font-size:clamp(1.08rem,1.7vw,1.4rem); line-height:1.1; font-weight:800;">Panel deportivo institucional</h1>
            </div>

            <div class="fab-dashboard-date" style="display:flex; align-items:center; gap:.45rem; border:1px solid rgba(255,255,255,.18); border-radius:9999px; padding:.4rem .7rem; background:rgba(15,23,42,.25); backdrop-filter:blur(8px);">
                <x-filament::icon icon="heroicon-m-calendar-days" style="width:1rem; height:1rem; color:#fde68a;" />
                <span style="font-size:.76rem; font-weight:700; text-transform:capitalize;">{{ $today }}</span>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
