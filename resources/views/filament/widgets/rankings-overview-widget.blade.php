<x-filament-widgets::widget>
    <section>
        <div style="display:flex; align-items:end; justify-content:space-between; margin-bottom:.65rem;">
            <div>
                <p style="margin:0; font-size:.72rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; color:#64748b;">Competencia</p>
                <h2 style="margin:.15rem 0 0; font-size:1.05rem; font-weight:800;">Rankings por disciplina</h2>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem;">
            <article style="padding:.9rem; border-radius:1rem; border:1px solid rgba(59,130,246,.25); background:linear-gradient(145deg,rgba(37,99,235,.10),rgba(255,255,255,.02));">
                <div style="display:flex; justify-content:space-between; gap:.5rem;">
                    <div><small style="color:#2563eb; font-weight:800;">5 QUILLAS</small><h3 style="margin:.15rem 0 .45rem; font-size:.95rem;">Circuito Argentino</h3></div>
                    <a href="{{ $fiveQuillasUrl }}" style="font-size:.75rem; font-weight:800; color:#2563eb;">Ver completo →</a>
                </div>
                @forelse ($fiveQuillasTop as $row)
                    <div style="display:flex; justify-content:space-between; gap:.5rem; padding:.18rem 0; font-size:.78rem;"><span><strong>{{ $row->RG }}.</strong> {{ $row->last_name }}, {{ $row->first_name }}</span><strong>{{ number_format((float) $row->total_puntos, 0, ',', '.') }} pts</strong></div>
                @empty
                    <span style="font-size:.78rem; color:#64748b;">Sin posiciones publicadas.</span>
                @endforelse
            </article>

            <article style="padding:.9rem; border-radius:1rem; border:1px solid rgba(16,185,129,.28); background:linear-gradient(145deg,rgba(5,150,105,.11),rgba(255,255,255,.02));">
                <div style="display:flex; justify-content:space-between; gap:.5rem;"><div><small style="color:#059669; font-weight:800;">CARAMBOLA</small><h3 style="margin:.15rem 0 .45rem; font-size:.95rem;">3 Bandas</h3></div><a href="{{ $threeCushionUrl }}" style="font-size:.75rem; font-weight:800; color:#059669;">Elegir categoría →</a></div>
                <div style="display:flex; gap:1.4rem; margin-top:.35rem;"><div><strong style="display:block; font-size:1.35rem;">{{ $threeCushionPlayers }}</strong><small style="color:#64748b;">jugadores</small></div><div><strong style="display:block; font-size:1.35rem;">{{ $threeCushionCategories }}</strong><small style="color:#64748b;">categorías</small></div><div><strong style="display:block; font-size:1.35rem;">{{ $threeCushionSeason ?: '—' }}</strong><small style="color:#64748b;">temporada</small></div></div>
            </article>

            <article style="padding:.9rem; border-radius:1rem; border:1px solid rgba(245,158,11,.30); background:linear-gradient(145deg,rgba(245,158,11,.11),rgba(255,255,255,.02));">
                <small style="color:#d97706; font-weight:800;">POOL</small>
                <h3 style="margin:.15rem 0 .45rem; font-size:.95rem;">Ranking por modalidad</h3>
                <p style="margin:0; font-size:.78rem; line-height:1.45; color:#64748b;">Espacio reservado para Bola 8, Bola 9, Bola 10 y Heyball.</p>
                <span style="display:inline-block; margin-top:.55rem; padding:.25rem .5rem; border-radius:9999px; background:rgba(245,158,11,.14); color:#b45309; font-size:.7rem; font-weight:800;">Próxima implementación</span>
            </article>
        </div>
    </section>
</x-filament-widgets::widget>
