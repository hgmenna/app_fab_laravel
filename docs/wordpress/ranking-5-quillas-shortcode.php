<?php

/**
 * Shortcode: [ranking_5_quillas]
 * Consume el ranking en tiempo real desde la aplicación administrativa.
 */
add_shortcode('ranking_5_quillas', function (): string {
    $id = wp_unique_id('fab-ranking-5q-');
    $endpoint = 'https://sistem.federacionargentinadebillar.org/ranking-5-quillas.json';

    ob_start();
    ?>
    <section id="<?php echo esc_attr($id); ?>" class="fab-ranking-5q" aria-live="polite">
        <div class="fab-ranking-5q__header">
            <div>
                <p class="fab-ranking-5q__eyebrow">FEDERACIÓN ARGENTINA DE BILLAR</p>
                <h2>Ranking Circuito Argentino de 5 Quillas</h2>
                <p class="fab-ranking-5q__season"></p>
            </div>
            <label class="fab-ranking-5q__search">
                <span>Buscar jugador, club o federación</span>
                <input type="search" placeholder="Escriba para filtrar…" autocomplete="off">
            </label>
        </div>

        <div class="fab-ranking-5q__status">Cargando ranking…</div>
        <div class="fab-ranking-5q__table-wrap" hidden>
            <table>
                <thead>
                    <tr>
                        <th class="fab-ranking-5q__toggle" aria-label="Detalle"></th>
                        <th>RG</th>
                        <th>RC</th>
                        <th>Apellido y nombre</th>
                        <th>Club</th>
                        <th>Federación</th>
                        <th>Penalizaciones</th>
                        <th>Total puntos</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>

    <style>
        #<?php echo esc_attr($id); ?> {
            --fab-blue: #173f93;
            --fab-blue-dark: #0d2d70;
            --fab-gold: #f5b400;
            --fab-border: #d8dfeb;
            --fab-row: #f7f9fc;
            color: #13213a;
            font-family: inherit;
            width: 100%;
        }
        #<?php echo esc_attr($id); ?> * { box-sizing: border-box; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__header {
            align-items: end;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        #<?php echo esc_attr($id); ?> h2 { color: var(--fab-blue-dark); margin: 0; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__eyebrow {
            color: var(--fab-blue);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            margin: 0 0 4px;
        }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__season { color: #667085; margin: 4px 0 0; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__search { min-width: 280px; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__search span {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__search input {
            border: 1px solid var(--fab-border);
            border-radius: 9px;
            font: inherit;
            padding: 10px 12px;
            width: 100%;
        }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__status {
            background: #f3f6fb;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
        }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__table-wrap {
            border: 1px solid var(--fab-border);
            border-radius: 12px;
            overflow-x: auto;
        }
        #<?php echo esc_attr($id); ?> table { border-collapse: collapse; margin: 0; min-width: 920px; width: 100%; }
        #<?php echo esc_attr($id); ?> th {
            background: var(--fab-blue);
            color: #fff;
            font-size: 12px;
            letter-spacing: .025em;
            padding: 12px 10px;
            text-align: center;
            text-transform: uppercase;
        }
        #<?php echo esc_attr($id); ?> td {
            border-bottom: 1px solid var(--fab-border);
            padding: 11px 10px;
            text-align: center;
            vertical-align: middle;
        }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__player-row { cursor: pointer; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__player-row:nth-of-type(4n+1) { background: var(--fab-row); }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__player-row:hover { background: #edf3ff; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__name,
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__club { text-align: left; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__name { font-weight: 750; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__points { color: var(--fab-blue-dark); font-weight: 800; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__toggle { width: 42px; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__expand {
            align-items: center;
            background: var(--fab-blue);
            border: 0;
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-size: 18px;
            height: 26px;
            justify-content: center;
            line-height: 1;
            padding: 0;
            width: 26px;
        }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__detail > td { background: #eef3fb; padding: 14px; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__detail table {
            background: #fff;
            border: 1px solid #ccd6e7;
            border-radius: 8px;
            min-width: 520px;
            overflow: hidden;
        }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__detail th { background: var(--fab-blue-dark); padding: 8px; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__detail td { padding: 8px; }
        #<?php echo esc_attr($id); ?> .fab-ranking-5q__empty { color: #667085; font-style: italic; }
        @media (max-width: 720px) {
            #<?php echo esc_attr($id); ?> .fab-ranking-5q__header { align-items: stretch; flex-direction: column; }
            #<?php echo esc_attr($id); ?> .fab-ranking-5q__search { min-width: 0; }
            #<?php echo esc_attr($id); ?> table { min-width: 820px; }
            #<?php echo esc_attr($id); ?> th,
            #<?php echo esc_attr($id); ?> td { font-size: 12px; padding: 9px 7px; }
        }
    </style>

    <script>
    (() => {
        const root = document.getElementById(<?php echo wp_json_encode($id); ?>);
        if (!root) return;

        const endpoint = <?php echo wp_json_encode($endpoint); ?>;
        const status = root.querySelector('.fab-ranking-5q__status');
        const wrapper = root.querySelector('.fab-ranking-5q__table-wrap');
        const tbody = root.querySelector('tbody');
        const search = root.querySelector('input[type="search"]');
        const season = root.querySelector('.fab-ranking-5q__season');
        let rows = [];

        const escapeHtml = value => String(value ?? '')
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;').replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
        const displayNumber = value => value === null || value === '' || Number(value) === 0
            ? (value === null || value === '' ? '—' : '0')
            : new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 }).format(Number(value));

        const detailRows = stages => (stages || []).map(stage => `
            <tr>
                <td>Etapa ${escapeHtml(stage.etapa)}</td>
                <td>${displayNumber(stage.penalizacion)}</td>
                <td>${escapeHtml(stage.posicion || '—')}</td>
                <td>${displayNumber(stage.puntos)}</td>
            </tr>`).join('');

        const render = () => {
            const term = search.value.trim().toLocaleLowerCase('es');
            const filtered = rows.filter(row => !term || [row.jugador, row.club, row.federacion]
                .some(value => String(value || '').toLocaleLowerCase('es').includes(term)));

            tbody.innerHTML = filtered.map((row, index) => `
                <tr class="fab-ranking-5q__player-row" data-index="${index}" tabindex="0" aria-expanded="false">
                    <td><button class="fab-ranking-5q__expand" type="button" aria-label="Ver detalle de ${escapeHtml(row.jugador)}">+</button></td>
                    <td><strong>${escapeHtml(row.ranking_general)}</strong></td>
                    <td>${escapeHtml(row.ranking_categoria)}</td>
                    <td class="fab-ranking-5q__name">${escapeHtml(row.jugador)}</td>
                    <td class="fab-ranking-5q__club">${escapeHtml(row.club || 'SIN CLUB')}</td>
                    <td>${escapeHtml(row.federacion || 'SIN FEDERACIÓN')}</td>
                    <td>${displayNumber(row.penalizaciones)}</td>
                    <td class="fab-ranking-5q__points">${displayNumber(row.puntos_total)}</td>
                </tr>
                <tr class="fab-ranking-5q__detail" hidden>
                    <td colspan="8">
                        <table aria-label="Detalle por etapa de ${escapeHtml(row.jugador)}">
                            <thead><tr><th>Etapa</th><th>Penalización</th><th>Posición</th><th>Puntos</th></tr></thead>
                            <tbody>${detailRows(row.etapas)}</tbody>
                        </table>
                    </td>
                </tr>`).join('');

            status.hidden = filtered.length > 0;
            status.textContent = filtered.length ? '' : 'No se encontraron jugadores con ese criterio.';
            wrapper.hidden = filtered.length === 0;
        };

        const toggle = row => {
            const detail = row.nextElementSibling;
            const button = row.querySelector('.fab-ranking-5q__expand');
            const open = row.getAttribute('aria-expanded') === 'true';
            row.setAttribute('aria-expanded', String(!open));
            detail.hidden = open;
            button.textContent = open ? '+' : '−';
        };

        tbody.addEventListener('click', event => {
            const row = event.target.closest('.fab-ranking-5q__player-row');
            if (row) toggle(row);
        });
        tbody.addEventListener('keydown', event => {
            if ((event.key === 'Enter' || event.key === ' ') && event.target.closest('.fab-ranking-5q__player-row')) {
                event.preventDefault();
                toggle(event.target.closest('.fab-ranking-5q__player-row'));
            }
        });
        search.addEventListener('input', render);

        fetch(endpoint, { cache: 'no-store' })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(payload => {
                rows = Array.isArray(payload.data) ? payload.data : [];
                season.textContent = `Temporada ${payload.season || ''} · ${rows.length} jugadores`;
                render();
            })
            .catch(() => {
                status.textContent = 'No fue posible cargar el ranking. Intente nuevamente en unos minutos.';
                wrapper.hidden = true;
            });
    })();
    </script>
    <?php
    return (string) ob_get_clean();
});
