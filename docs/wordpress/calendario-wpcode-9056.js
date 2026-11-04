(function () {
    'use strict';
    const tablaSelector = 'table.wpDataTableID-64';
    const endpoint =
        'https://sistem.federacionargentinadebillar.org/torneos.json?estado=todos';
    let torneos = new Map(),
        estado = 'proximos',
        desde = '',
        hasta = '',
        buscar = '';
    const norm = (v) =>
        String(v || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toUpperCase();
    function css() {
        if (document.getElementById('fab-cal-css')) return;
        const s = document.createElement('style');
        s.id = 'fab-cal-css';
        s.textContent =
            '.fab-cal-filtros{display:grid;grid-template-columns:minmax(210px,1.2fr) repeat(2,minmax(145px,.7fr)) auto;gap:10px;align-items:end;margin:0 0 16px;padding:15px;border:1px solid #d7e0ed;border-radius:12px;background:#f7f9fc;box-shadow:0 6px 18px rgba(16,42,78,.08)}.fab-cal-campo{display:flex;flex-direction:column;gap:5px}.fab-cal-campo label{font-size:12px;font-weight:800;color:#17365d;text-transform:uppercase;letter-spacing:.04em}.fab-cal-campo input{height:40px;border:1px solid #b8c5d6;border-radius:8px;padding:0 11px;background:#fff;color:#172033}.fab-cal-estados{grid-column:1/-1;display:flex;gap:8px;flex-wrap:wrap}.fab-cal-btn{border:1px solid #17468a;border-radius:8px;padding:9px 15px;background:#fff;color:#17468a;font-weight:800;cursor:pointer}.fab-cal-btn.activo,.fab-cal-btn:hover{background:#17468a;color:#fff}.fab-cal-limpiar{height:40px;border:0;border-radius:8px;padding:0 15px;background:#e9eef6;color:#17365d;font-weight:800;cursor:pointer}.fab-torneo-titulo{display:block}.fab-resultados-publicos{display:inline-flex;align-items:center;justify-content:center;gap:6px;margin-top:8px;padding:7px 12px;border-radius:7px;background:#075aa8!important;color:#fff!important;border:1px solid #075aa8!important;font-size:12px;font-weight:700;line-height:1.15;text-decoration:none!important;box-shadow:0 2px 5px rgba(0,0,0,.18);white-space:nowrap}.fab-resultados-publicos:hover{background:#063f77!important}.fab-resultados-publicos svg{width:15px;height:15px;fill:currentColor}@media(max-width:767px){.fab-cal-filtros{grid-template-columns:1fr 1fr;padding:12px}.fab-cal-campo:first-of-type{grid-column:1/-1}.fab-cal-estados{gap:6px}.fab-cal-btn{flex:1;padding:9px 7px;font-size:12px}.fab-cal-limpiar{grid-column:1/-1}.fab-resultados-publicos{width:100%;max-width:155px}}#table_1 .column-id,#table_1 .column-fecha_inicio,#table_1 .column-fecha_fin,#table_1 .column-estado_torneo,#table_1 .column-disciplina_id,#table_1 .column-tipo_id,#table_1 .column-contactos,#table_1 .column-desarrollo_disponible,#table_1 .column-desarrollo_slug,#table_1 .column-desarrollo_estado,#table_1 .column-desarrollo_texto,#table_1 .column-desarrollo_mensaje,#table_1 .column-desarrollo_json_url,#table_1 .column-desarrollo_url{display:none!important}#table_1 thead tr:nth-child(2){display:none!important}#table_1{width:100%!important;table-layout:auto!important}#table_1 th,#table_1 td{height:auto!important;vertical-align:middle!important}#table_1 tbody td{padding:8px 7px!important;line-height:1.25!important}#table_1 .column-torneo,#table_1 .column-club{min-width:150px}#table_1 .column-fecha{min-width:82px}#table_1 .column-flyer img,#table_1 .column-logo_publicacion img{max-width:46px!important;max-height:46px!important;object-fit:contain!important}';
        s.textContent +=
            '#table_1 .responsiveExpander{display:none!important}#table_1 .fab-calendar-toggle{display:none;border:1px solid #17468a;border-radius:6px;background:#fff;color:#17468a;font-size:20px;line-height:28px;width:32px;min-height:32px;padding:0;margin:0 8px 4px 0;cursor:pointer}#table_1 .fab-calendar-detail{display:table-row!important}#table_1 .fab-calendar-detail>td{width:auto!important}#table_1 .fab-calendar-detail ul{list-style:none;margin:0;padding:8px}#table_1 .fab-calendar-detail li{display:grid;grid-template-columns:minmax(90px,30%) minmax(0,1fr);gap:8px;padding:7px 0;border-bottom:1px solid #dce3ee}#table_1 .fab-calendar-detail .columnTitle{font-weight:700;color:#17427c}#table_1 .fab-calendar-detail .columnValue{overflow-wrap:anywhere}@media(max-width:1024px){#table_1 .fab-calendar-toggle{display:inline-block!important}#table_1 .fab-calendar-detail span{font-size:13px!important;line-height:1.4!important}}';
        document.head.appendChild(s);
    }
    function control() {
        const t = document.querySelector(tablaSelector);
        if (!t || document.getElementById('fab-cal-filtros')) return;
        const w = t.closest('.wpDataTablesWrapper') || t.parentElement;
        const d = document.createElement('div');
        d.id = 'fab-cal-filtros';
        d.className = 'fab-cal-filtros';
        d.innerHTML =
            '<div class="fab-cal-estados"><button type="button" class="fab-cal-btn activo" data-e="proximos">Próximos</button><button type="button" class="fab-cal-btn" data-e="encurso">En curso</button><button type="button" class="fab-cal-btn" data-e="finalizados">Finalizados</button><button type="button" class="fab-cal-btn" data-e="todos">Todos</button></div><div class="fab-cal-campo"><label for="fab-cal-buscar">Buscar torneo o sede</label><input id="fab-cal-buscar" type="search" placeholder="Nombre del torneo o sede"></div><div class="fab-cal-campo"><label for="fab-cal-desde">Desde</label><input id="fab-cal-desde" type="date"></div><div class="fab-cal-campo"><label for="fab-cal-hasta">Hasta</label><input id="fab-cal-hasta" type="date"></div><button type="button" class="fab-cal-limpiar">Limpiar filtros</button>';
        w.parentNode.insertBefore(d, w);
        d.querySelectorAll('[data-e]').forEach(
            (b) =>
                (b.onclick = () => {
                    estado = b.dataset.e;
                    d.querySelectorAll('[data-e]').forEach((x) =>
                        x.classList.toggle('activo', x === b),
                    );
                    dibujar();
                }),
        );
        d.querySelector('#fab-cal-buscar').oninput = (e) => {
            buscar = norm(e.target.value);
            clearTimeout(espera);
            espera = setTimeout(dibujar, 120);
        };
        d.querySelector('#fab-cal-desde').onchange = (e) => {
            desde = e.target.value;
            dibujar();
        };
        d.querySelector('#fab-cal-hasta').onchange = (e) => {
            hasta = e.target.value;
            dibujar();
        };
        d.querySelector('.fab-cal-limpiar').onclick = () => {
            estado = 'proximos';
            desde = hasta = buscar = '';
            d.querySelectorAll('input').forEach((x) => (x.value = ''));
            d.querySelectorAll('[data-e]').forEach((x) =>
                x.classList.toggle('activo', x.dataset.e === 'proximos'),
            );
            dibujar();
        };
    }
    function dato(id) {
        return torneos.get(
            String(id || '')
                .replace(/<[^>]*>/g, '')
                .trim(),
        );
    }
    function indiceTorneo(t) {
        const c = [...t.querySelectorAll('thead tr:first-child th')].findIndex(
            (x) => norm(x.textContent) === 'TORNEO',
        );
        return c >= 0 ? c : 5;
    }
    function admite(r) {
        if (!r) return false;
        const e = norm(r.estado_torneo);
        if (estado === 'proximos' && e !== 'PROXIMO') return false;
        if (estado === 'encurso' && e !== 'EN CURSO') return false;
        if (estado === 'finalizados' && e !== 'FINALIZADO') return false;
        if (desde && r.fecha_fin < desde) return false;
        if (desde && hasta && desde > hasta) return false;
        if (hasta && r.fecha_inicio > hasta) return false;
        if (
            buscar &&
            !buscar
                .split(' ')
                .filter(Boolean)
                .every((p) =>
                    norm(
                        [
                            r.torneo,
                            r.club,
                            r.disciplina,
                            r.tipo,
                            r.categorias,
                        ].join(' '),
                    ).includes(p),
                )
        )
            return false;
        return true;
    }
    let tablaApi, espera;
    function aplicarEstado() {
        const t = document.querySelector(tablaSelector),
            $ = window.jQuery;
        if (!t || !$?.fn?.dataTable?.isDataTable(t)) return false;
        if (!tablaApi) {
            tablaApi = $(t).DataTable();
            $.fn.dataTable.ext.search.push(
                (settings, data) =>
                    settings.nTable !== t || admite(dato(data[0])),
            );
            tablaApi.search('');
            tablaApi.columns().search('');
            $(t).on('draw.dt.fabCalendario', () => {
                botones();
                filtrarDOM();
            });
        }
        return true;
    }
    function dibujar() {
        clearTimeout(espera);
        if (!aplicarEstado()) return;
        const t = tablaApi.table().node(),
            col = (n) =>
                [...t.querySelectorAll('thead tr:first-child th')].findIndex(
                    (h) => h.classList.contains('column-' + n),
                ),
            desc = estado === 'finalizados';
        const settings = tablaApi.settings()[0],
            index = col(desc ? 'fecha_fin' : 'fecha_inicio');
        settings.oFeatures.bSort = true;
        window.jQuery.fn.dataTable.ext.order['fab-calendar-date'] = function (
            s,
        ) {
            return new window.jQuery.fn.dataTable.Api(s)
                .rows({ order: 'index' })
                .data()
                .toArray()
                .map((r) => {
                    const id = Array.isArray(r) ? r[0] : r.id;
                    const d = dato(id);
                    return d ? d[desc ? 'fecha_fin' : 'fecha_inicio'] : '';
                });
        };
        settings.aoColumns[index].sSortDataType = 'fab-calendar-date';
        settings.aoColumns[index].sType = 'string';
        settings.aoColumns[index].aDataSort = [index];
        tablaApi.order.fixed([]);
        tablaApi
            .order([
                [
                    col(desc ? 'fecha_fin' : 'fecha_inicio'),
                    desc ? 'desc' : 'asc',
                ],
                [col('id'), desc ? 'desc' : 'asc'],
            ])
            .draw();
        botones();
        filtrarDOM();
    }
    function filtrarDOM() {
        const t = document.querySelector(tablaSelector);
        if (!t) return;
        let grupo,
            hay = false;
        const cerrar = () => {
            if (grupo) grupo.style.display = hay ? '' : 'none';
        };
        t.querySelectorAll('tbody tr').forEach((f) => {
            if (f.matches('.row-detail, .child')) return;
            if (f.cells.length === 1 && !f.querySelector('.dataTables_empty')) {
                cerrar();
                grupo = f;
                hay = false;
                return;
            }
            const r = dato(f.cells[0]?.textContent);
            if (r && admite(r)) hay = true;
        });
        cerrar();
        let s = document.getElementById('fab-cal-resultado');
        if (!s) {
            s = document.createElement('p');
            s.id = 'fab-cal-resultado';
            s.setAttribute('role', 'status');
            document.getElementById('fab-cal-filtros')?.after(s);
        }
        if (s && tablaApi) {
            const n = tablaApi.page.info().recordsDisplay;
            s.textContent =
                desde && hasta && desde > hasta
                    ? 'La fecha Desde debe ser anterior o igual a Hasta.'
                    : n
                      ? n +
                        ' torneo' +
                        (n === 1 ? ' encontrado' : 's encontrados')
                      : 'No hay torneos que coincidan con los filtros.';
        }
    }
    // Keep the mobile detail attached to its tournament, with one open at a time.
    function inlineDetails(table, api) {
        if (!api) return;
        const close = () => {
            table
                .querySelectorAll('.fab-calendar-detail')
                .forEach((row) => row.remove());
            table.querySelectorAll('.fab-calendar-toggle').forEach((button) => {
                button.setAttribute('aria-expanded', 'false');
                button.textContent = '+';
            });
        };
        close();
        table.querySelectorAll('tbody tr').forEach((row) => {
            const cell = row.querySelector('td.column-torneo');
            if (!cell || cell.querySelector('.fab-calendar-toggle')) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'fab-calendar-toggle';
            button.textContent = '+';
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute(
                'aria-label',
                'Ver detalles de ' + cell.textContent.trim(),
            );
            cell.prepend(button);
        });
        if (table.dataset.fabInlineDetails) return;
        table.dataset.fabInlineDetails = 'true';
        table.addEventListener(
            'click',
            (event) => {
                const button = event.target.closest('.fab-calendar-toggle');
                if (!button) return;
                event.preventDefault();
                event.stopImmediatePropagation();
                const expanded =
                    button.getAttribute('aria-expanded') === 'true';
                close();
                if (expanded) return;
                const row = button.closest('tr');
                const data = api.row(row).data();
                if (!data) return;
                const detail = document.createElement('tr');
                detail.className = 'row-detail fab-calendar-detail';
                const cell = document.createElement('td');
                cell.colSpan = Array.from(row.cells).filter(
                    (td) => getComputedStyle(td).display !== 'none',
                ).length;
                const list = document.createElement('ul');
                api.columns()
                    .header()
                    .toArray()
                    .forEach((header, index) => {
                        const name = Array.from(header.classList)
                            .find((value) => value.startsWith('column-'))
                            ?.slice(7);
                        if (
                            ![
                                'disciplina',
                                'categorias',
                                'club',
                                'tipo',
                                'oficial',
                                'provincia',
                                'modalidad',
                                'handicap',
                                'inscriptos',
                                'estado_inscripcion',
                                'apertura_inscripcion',
                                'cierre_inscripcion',
                                'inscripcion',
                                'ubicacion',
                                'flyer',
                                'logo_publicacion',
                            ].includes(name)
                        )
                            return;
                        const html = Array.isArray(data)
                            ? data[index]
                            : data[name];
                        if (html === undefined || html === null || html === '')
                            return;
                        const item = document.createElement('li');
                        item.className = 'column-' + name;
                        const title = document.createElement('span');
                        title.className = 'columnTitle';
                        title.textContent = header.textContent.trim();
                        const value = document.createElement('span');
                        value.className = 'columnValue';
                        value.innerHTML = String(html);
                        item.append(title, value);
                        list.appendChild(item);
                    });
                cell.appendChild(list);
                detail.appendChild(cell);
                row.after(detail);
                button.textContent = '−';
                button.setAttribute('aria-expanded', 'true');
            },
            true,
        );
    }

    function botones() {
        const t = document.querySelector(tablaSelector);
        if (!t) return;
        inlineDetails(t, tablaApi);
        t.querySelectorAll('tbody tr').forEach((f) => {
            const c =
                f.querySelector('td.fab-torneo') || f.cells[indiceTorneo(t)];
            if (!c || c.querySelector('.fab-resultados-publicos')) return;
            const r = dato(f.cells[0]?.textContent);
            if (!r || !r.desarrollo_url) return;
            const sp = document.createElement('span');
            sp.className = 'fab-torneo-titulo';
            while (c.firstChild) sp.appendChild(c.firstChild);
            c.appendChild(sp);
            const a = document.createElement('a');
            a.className = 'fab-resultados-publicos';
            a.href = r.desarrollo_url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.innerHTML =
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 3h-1V1h-2v2H8V1H6v2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Zm1 16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9h16v9Zm0-11H4V6a1 1 0 0 1 1-1h1v2h2V5h8v2h2V5h1a1 1 0 0 1 1 1v2Zm-9 9.4 5.7-5.7 1.4 1.4-7.1 7.1-3.6-3.6 1.4-1.4 2.2 2.2Z"/></svg><span>Ver resultados</span>';
            c.appendChild(a);
        });
    }
    async function cargar() {
        try {
            const r = await window.fetch(
                endpoint + '&actualizacion=' + Date.now(),
                { cache: 'no-store', credentials: 'omit' },
            );
            const j = await r.json();
            torneos = new Map((j.data || []).map((x) => [String(x.id), x]));
            botones();
            dibujar();
        } catch (e) {
            console.warn('No se pudo actualizar el calendario.', e);
        }
    }
    function iniciar() {
        css();
        control();
        cargar();
        const obs = new MutationObserver(() => {
            control();
            if (aplicarEstado()) {
                dibujar();
                obs.disconnect();
            }
        });
        if (!aplicarEstado()) {
            obs.observe(document.body, { childList: true, subtree: true });
            setTimeout(() => obs.disconnect(), 30000);
        }
    }
    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', iniciar, { once: true })
        : iniciar();
})();
