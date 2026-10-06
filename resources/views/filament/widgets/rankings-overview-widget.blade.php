<x-filament-widgets::widget>
    <section>
        <div style="display:flex; align-items:end; justify-content:space-between; margin-bottom:.35rem;">
            <div>
                <p style="margin:0; font-size:.72rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; color:#64748b;">Competencia</p>
                <h2 style="margin:.15rem 0 0; font-size:.95rem; font-weight:800;">Rankings por disciplina</h2>
            </div>
        </div>

        <div class="fab-ranking-grid" style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.55rem;">
            <article class="fab-ranking-card" style="padding:1rem; border-radius:.8rem; border:1px solid rgba(59,130,246,.35); background:linear-gradient(145deg,rgba(37,99,235,.12),rgba(15,23,42,.04));">
                <header style="display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem;">
                    <div><small style="display:block; color:#3b82f6; font-size:.7rem; font-weight:900; letter-spacing:.08em;">5 QUILLAS</small><h3 style="margin:.2rem 0 0; font-size:1rem;">Circuito Argentino</h3></div>
                    <span style="display:grid; place-items:center; width:2.35rem; height:2.35rem; border-radius:.75rem; background:rgba(37,99,235,.16); color:#60a5fa; font-size:1.25rem;">🏆</span>
                </header>

                <div style="display:grid; gap:.42rem; width:100%;">
                    @forelse ($fiveQuillasTop as $row)
                        <div style="display:grid; grid-template-columns:1.75rem minmax(0,1fr) auto; align-items:center; gap:.55rem; padding:.52rem .6rem; border:1px solid rgba(96,165,250,.16); border-radius:.65rem; background:rgba(15,23,42,.22);">
                            <strong style="display:grid; place-items:center; width:1.65rem; height:1.65rem; border-radius:9999px; background:#1d4ed8; color:#fff; font-size:.72rem;">{{ $row->RG }}</strong>
                            <span style="min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.78rem; font-weight:750;">{{ $row->last_name }}, {{ $row->first_name }}</span>
                            <strong style="white-space:nowrap; color:#93c5fd; font-size:.76rem;">{{ number_format((float) $row->total_puntos, 0, ',', '.') }} pts</strong>
                        </div>
                    @empty
                        <div style="padding:1rem; border:1px dashed rgba(96,165,250,.35); border-radius:.65rem; color:#94a3b8; text-align:center; font-size:.78rem;">Sin posiciones publicadas.</div>
                    @endforelse
                </div>

                <a href="{{ $fiveQuillasUrl }}" style="display:flex; align-items:center; justify-content:center; gap:.35rem; width:100%; padding:.52rem .7rem; border-radius:.6rem; background:#1d4ed8; color:#fff; font-size:.75rem; font-weight:850; text-decoration:none; box-sizing:border-box;">Ver ranking completo <span>→</span></a>
            </article>

            <article class="fab-ranking-card" style="padding:1rem; border-radius:.8rem; border:1px solid rgba(16,185,129,.36); background:linear-gradient(145deg,rgba(5,150,105,.13),rgba(15,23,42,.04));">
                <header style="display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem;">
                    <div><small style="display:block; color:#10b981; font-size:.7rem; font-weight:900; letter-spacing:.08em;">CARAMBOLA</small><h3 style="margin:.2rem 0 0; font-size:1rem;">3 Bandas</h3></div>
                    <span style="display:grid; place-items:center; width:2.35rem; height:2.35rem; border-radius:.75rem; background:rgba(5,150,105,.16); color:#34d399; font-size:1.2rem;">◆</span>
                </header>

                <div style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; width:100%;">
                    <div style="padding:.8rem .4rem; border:1px solid rgba(52,211,153,.18); border-radius:.7rem; background:rgba(15,23,42,.20); text-align:center;"><strong style="display:block; font-size:1.45rem; line-height:1; color:#6ee7b7;">{{ $threeCushionPlayers }}</strong><small style="display:block; margin-top:.35rem; color:#94a3b8; font-size:.67rem;">Jugadores</small></div>
                    <div style="padding:.8rem .4rem; border:1px solid rgba(52,211,153,.18); border-radius:.7rem; background:rgba(15,23,42,.20); text-align:center;"><strong style="display:block; font-size:1.45rem; line-height:1; color:#6ee7b7;">{{ $threeCushionCategories }}</strong><small style="display:block; margin-top:.35rem; color:#94a3b8; font-size:.67rem;">Categorías</small></div>
                    <div style="padding:.8rem .4rem; border:1px solid rgba(52,211,153,.18); border-radius:.7rem; background:rgba(15,23,42,.20); text-align:center;"><strong style="display:block; font-size:1.45rem; line-height:1; color:#6ee7b7;">{{ $threeCushionSeason ?: '—' }}</strong><small style="display:block; margin-top:.35rem; color:#94a3b8; font-size:.67rem;">Temporada</small></div>
                </div>

                <div style="padding:.65rem .75rem; border-radius:.65rem; background:rgba(5,150,105,.10); color:#a7f3d0; font-size:.73rem; line-height:1.35;">Consulta independiente por categoría.</div>
                <a href="{{ $threeCushionUrl }}" style="display:flex; align-items:center; justify-content:center; gap:.35rem; width:100%; padding:.52rem .7rem; border-radius:.6rem; background:#047857; color:#fff; font-size:.75rem; font-weight:850; text-decoration:none; box-sizing:border-box;">Seleccionar categoría <span>→</span></a>
            </article>

            <article class="fab-ranking-card" style="padding:1rem; border-radius:.8rem; border:1px solid rgba(245,158,11,.38); background:linear-gradient(145deg,rgba(245,158,11,.13),rgba(15,23,42,.04));">
                <header style="display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem;">
                    <div><small style="display:block; color:#f59e0b; font-size:.7rem; font-weight:900; letter-spacing:.08em;">POOL</small><h3 style="margin:.2rem 0 0; font-size:1rem;">Ranking por modalidad</h3></div>
                    <span style="display:grid; place-items:center; width:2.35rem; height:2.35rem; border-radius:.75rem; background:rgba(245,158,11,.16); color:#fbbf24; font-size:1.2rem;">●</span>
                </header>

                <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.5rem; width:100%;">
                    @foreach (['Bola 8', 'Bola 9', 'Bola 10', 'Heyball'] as $modality)
                        <div style="display:flex; align-items:center; gap:.45rem; padding:.65rem .7rem; border:1px solid rgba(251,191,36,.18); border-radius:.65rem; background:rgba(15,23,42,.20); color:#fde68a; font-size:.74rem; font-weight:750;"><span style="color:#f59e0b;">●</span>{{ $modality }}</div>
                    @endforeach
                </div>

                <div style="padding:.65rem .75rem; border-radius:.65rem; background:rgba(245,158,11,.10); color:#fde68a; font-size:.73rem; line-height:1.35;">Clasificaciones independientes por modalidad.</div>
                <span style="display:flex; align-items:center; justify-content:center; width:100%; padding:.52rem .7rem; border-radius:.6rem; background:rgba(245,158,11,.16); color:#fbbf24; font-size:.75rem; font-weight:850; box-sizing:border-box;">Próxima implementación</span>
            </article>
        </div>
    </section>
</x-filament-widgets::widget>
