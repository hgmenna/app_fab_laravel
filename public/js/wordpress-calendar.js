(function (root) {
    'use strict';

    const normalize = value => String(value ?? '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim().toUpperCase();

    function matches(row, filters) {
        if (!row) return false;
        const states = { proximos: 'PROXIMO', encurso: 'EN CURSO', finalizados: 'FINALIZADO' };
        if (states[filters.estado] && normalize(row.estado_torneo) !== states[filters.estado]) return false;
        if (filters.desde && filters.hasta && filters.desde > filters.hasta) return false;
        if (filters.desde && row.fecha_fin < filters.desde) return false;
        if (filters.hasta && row.fecha_inicio > filters.hasta) return false;
        const text = normalize([row.torneo, row.club, row.disciplina, row.tipo, row.categorias].join(' '));
        return normalize(filters.buscar).split(' ').filter(Boolean).every(word => text.includes(word));
    }

    function sortOrder(state, column) {
        const descending = state === 'finalizados';
        const direction = descending ? 'desc' : 'asc';
        return [[column(descending ? 'fecha_fin' : 'fecha_inicio'), direction], [column('id'), direction]];
    }

    if (typeof module === 'object' && module.exports) {
        module.exports = { normalize, matches, sortOrder };
        return;
    }
    if (root.fabCalendarInitialized) return;
    root.fabCalendarInitialized = true;

    const calendarScript = document.currentScript;
    const selector = root.fabCalendarConfig?.selector || 'table.wpDataTableID-64';
    // El script externo identifica el sistema que lo sirve, aunque WordPress esté en otro dominio.
    const endpoint = root.fabCalendarConfig?.endpoint || new URL('/torneos.json?estado=todos',
        calendarScript?.dataset.systemUrl || calendarScript?.src || root.location.origin).href;
    const filters = { estado: 'proximos', desde: '', hasta: '', buscar: '' };
    let rows = new Map(), ready = false, api, controls, status, timer;

    // Keep the mobile detail attached to its tournament, with one open at a time.
    function inlineDetails(table, api) {
        if (!api) return;
        const close = () => {
            table.querySelectorAll('.fab-calendar-detail').forEach(row => row.remove());
            table.querySelectorAll('.fab-calendar-toggle').forEach(button => {
                button.setAttribute('aria-expanded', 'false');
                button.textContent = '+';
            });
        };
        close();
        table.querySelectorAll('tbody tr').forEach(row => {
            const cell = row.querySelector('td.column-id');
            if (!cell || cell.querySelector('.fab-calendar-toggle')) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'fab-calendar-toggle';
            button.textContent = '+';
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', 'Ver detalles de ' + row.querySelector('td.column-torneo').textContent.trim());
            cell.prepend(button);
        });
        if (table.dataset.fabInlineDetails) return;
        table.dataset.fabInlineDetails = 'true';
        table.addEventListener('click', event => {
            const button = event.target.closest('.fab-calendar-toggle');
            if (!button) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            const expanded = button.getAttribute('aria-expanded') === 'true';
            close();
            if (expanded) return;
            const row = button.closest('tr');
            const data = api.row(row).data();
            if (!data) return;
            const detail = document.createElement('tr');
            detail.className = 'row-detail fab-calendar-detail';
            const cell = document.createElement('td');
            cell.colSpan = Array.from(row.cells).filter(td => getComputedStyle(td).display !== 'none').length;
            const list = document.createElement('ul');
            api.columns().header().toArray().forEach((header, index) => {
                const name = Array.from(header.classList).find(value => value.startsWith('column-'))?.slice(7);
                if (!['disciplina', 'categorias', 'club', 'tipo', 'oficial', 'provincia', 'modalidad', 'handicap', 'inscriptos', 'estado_inscripcion', 'apertura_inscripcion', 'cierre_inscripcion', 'inscripcion', 'ubicacion', 'flyer', 'logo_publicacion'].includes(name)) return;
                const html = Array.isArray(data) ? data[index] : data[name];
                if (html === undefined || html === null || html === '') return;
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
        }, true);
    }

    function decorate() {
        const table = document.querySelector(selector);
        if (!table) return;
        let header, hasRows = false;
        const finishGroup = () => { if (header) header.hidden = !hasRows; };
        table.querySelectorAll('tbody tr').forEach(tr => {
            if (tr.matches('.row-detail, .child')) return;
            // wpDataTables month headings span the entire table.
            if (tr.cells.length === 1 && !tr.querySelector('.dataTables_empty')) {
                finishGroup(); header = tr; hasRows = false; return;
            }
            const row = rows.get(tr.cells[0]?.textContent.replace(/^[+−]\s*/, '').trim());
            if (!row) return;
            hasRows = true;
            const cell = tr.querySelector('.column-torneo, .fab-torneo') || tr.cells[5];
            if (!cell || !row.desarrollo_url || cell.querySelector('.fab-resultados-publicos')) return;
            const link = document.createElement('a');
            link.className = 'fab-resultados-publicos';
            link.textContent = 'Ver resultados';
            link.href = row.desarrollo_url;
            link.target = '_blank'; link.rel = 'noopener';
            cell.appendChild(link);
        });
        finishGroup();
        inlineDetails(table, api);
        if (ready && api) {
            const count = api.page.info().recordsDisplay;
            status.textContent = filters.desde && filters.hasta && filters.desde > filters.hasta
                ? 'La fecha Desde debe ser anterior o igual a Hasta.'
                : count ? `${count} ${count === 1 ? 'torneo encontrado' : 'torneos encontrados'}`
                    : 'No hay torneos que coincidan con los filtros.';
        }
    }

    function draw() {
        clearTimeout(timer);
        if (!api || !ready) return;
        const table = api.table().node();
        const column = name => Array.from(table.querySelectorAll('thead tr:first-child th'))
            .findIndex(th => th.classList.contains(`column-${name}`));
        const settings = api.settings()[0];
        const dateKey = filters.estado === 'finalizados' ? 'fecha_fin' : 'fecha_inicio';
        const index = column(dateKey);
        // wpDataTables can disable ordering or configure these cells as DD/MM/YYYY.
        // Use the authoritative ISO dates, independent of the visible date format.
        settings.oFeatures.bSort = true;
        root.jQuery.fn.dataTable.ext.order['fab-calendar-date'] = function (tableSettings) {
            return new root.jQuery.fn.dataTable.Api(tableSettings).rows({ order: 'index' }).data().toArray()
                .map(row => rows.get(String(Array.isArray(row) ? row[0] : row.id))?.[dateKey] || '');
        };
        settings.aoColumns[index].sSortDataType = 'fab-calendar-date';
        settings.aoColumns[index].sType = 'string';
        settings.aoColumns[index].aDataSort = [index];
        // Do not let a fixed month order override the selected chronological order.
        api.order.fixed([]);
        api.order(sortOrder(filters.estado, column)).draw();
        decorate();
    }

    function setup() {
        const $ = root.jQuery;
        const table = document.querySelector(selector);
        if (!table || !$?.fn?.dataTable?.isDataTable(table)) return false;
        api = $(table).DataTable();
        const wrapper = table.closest('.wpDataTablesWrapper') || table.parentElement;
        controls = document.createElement('div');
        controls.id = 'fab-cal-filtros'; controls.className = 'fab-cal-filtros';
        controls.innerHTML = `<div class="fab-cal-estados">${[
            ['proximos', 'Próximos'], ['encurso', 'En curso'], ['finalizados', 'Finalizados'], ['todos', 'Todos'],
        ].map(([value, text]) => `<button type="button" class="fab-cal-btn" data-e="${value}" aria-pressed="${value === filters.estado}">${text}</button>`).join('')}</div>
            <label class="fab-cal-campo">Buscar torneo o sede<input type="search" name="buscar" placeholder="Nombre del torneo o sede" autocomplete="off"></label>
            <label class="fab-cal-campo">Desde<input type="date" name="desde"></label>
            <label class="fab-cal-campo">Hasta<input type="date" name="hasta"></label>
            <button type="button" class="fab-cal-limpiar">Limpiar filtros</button>`;
        wrapper.before(controls);
        status = document.createElement('p'); status.setAttribute('role', 'status');
        status.textContent = 'Cargando torneos…'; controls.after(status);
        const buttons = () => controls.querySelectorAll('[data-e]').forEach(button => {
            const active = button.dataset.e === filters.estado;
            button.classList.toggle('activo', active); button.setAttribute('aria-pressed', String(active));
        });
        controls.addEventListener('click', event => {
            const button = event.target.closest('button');
            if (!button) return;
            if (button.dataset.e) filters.estado = button.dataset.e;
            else {
                Object.assign(filters, { estado: 'proximos', desde: '', hasta: '', buscar: '' });
                controls.querySelectorAll('input').forEach(input => { input.value = ''; });
            }
            buttons(); draw();
        });
        controls.addEventListener('input', event => {
            const input = event.target;
            if (!Object.hasOwn(filters, input.name)) return;
            filters[input.name] = input.value;
            clearTimeout(timer);
            if (input.name === 'buscar') timer = setTimeout(draw, 120);
            else draw();
        });
        buttons();
        // Filter the whole dataset before pagination and month headings are built.
        $.fn.dataTable.ext.search.push((settings, data) => settings.nTable !== table
            || (ready && matches(rows.get(String(data[0]).replace(/<[^>]*>/g, '').trim()), filters)));
        api.search(''); api.columns().search('');
        $(table).on('draw.dt.fabCalendar', decorate);
        draw();
        fetch(endpoint + '&actualizacion=' + Date.now(), { cache: 'no-store', credentials: 'omit' })
            .then(response => { if (!response.ok) throw new Error('calendar'); return response.json(); })
            .then(payload => {
                if (!Array.isArray(payload.data)) throw new Error('calendar');
                rows = new Map(payload.data.map(row => [String(row.id), row]));
                ready = true; draw();
            }).catch(() => { status.textContent = 'No se pudo cargar el calendario. Recargá la página para intentar nuevamente.'; });
        return true;
    }

    function start() {
        const style = document.createElement('style');
        style.textContent = `.fab-cal-filtros{display:grid;grid-template-columns:minmax(210px,1.2fr) repeat(2,minmax(145px,.7fr)) auto;gap:10px;align-items:end;margin:0 0 12px;padding:15px;border:1px solid #d7e0ed;border-radius:12px;background:#f7f9fc}.fab-cal-campo{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:#17365d}.fab-cal-campo input{height:40px;border:1px solid #b8c5d6;border-radius:8px;padding:0 11px;background:#fff;color:#172033}.fab-cal-estados{grid-column:1/-1;display:flex;gap:8px;flex-wrap:wrap}.fab-cal-btn,.fab-cal-limpiar{border:1px solid #17468a;border-radius:8px;padding:9px 15px;background:#fff;color:#17468a;font-weight:800;cursor:pointer}.fab-cal-btn.activo,.fab-cal-btn:hover{background:#17468a;color:#fff}.fab-cal-limpiar{height:40px;background:#e9eef6}.fab-resultados-publicos{display:table;margin-top:8px;padding:7px 12px;border-radius:7px;background:#075aa8;color:#fff!important;font-size:12px;font-weight:700;text-decoration:none!important}#table_1 thead tr:nth-child(2){display:none!important}#table_1{width:100%!important}#table_1 td{vertical-align:middle;padding:8px 7px;line-height:1.25}#table_1 [hidden]{display:none!important}#table_1 .column-torneo,#table_1 .column-club{min-width:150px}#table_1 .column-flyer img,#table_1 .column-logo_publicacion img{max-width:46px;max-height:46px;object-fit:contain}@media(max-width:767px){.fab-cal-filtros{grid-template-columns:1fr 1fr}.fab-cal-campo:has(input[type=search]),.fab-cal-limpiar{grid-column:1/-1}.fab-cal-btn{flex:1;padding:9px 7px;font-size:12px}}`;
        const internal = ['id', 'fecha_inicio', 'fecha_fin', 'estado_torneo', 'disciplina_id', 'tipo_id', 'contactos', 'desarrollo_disponible', 'desarrollo_slug', 'desarrollo_estado', 'desarrollo_texto', 'desarrollo_mensaje', 'desarrollo_json_url', 'desarrollo_url'];
        style.textContent += internal.map(name => `#table_1 .column-${name}`).join(',') + '{display:none!important}';
        style.textContent += '#table_1 .responsiveExpander{display:none!important}#table_1 .fab-calendar-toggle{display:none;border:1px solid #17468a;border-radius:6px;background:#fff;color:#17468a;font-size:20px;line-height:28px;width:32px;min-height:32px;padding:0;margin:0 8px 4px 0;cursor:pointer}#table_1 .fab-calendar-detail{display:table-row!important}#table_1 .fab-calendar-detail>td{width:auto!important}#table_1 .fab-calendar-detail ul{list-style:none;margin:0;padding:8px}#table_1 .fab-calendar-detail li{display:grid;grid-template-columns:minmax(90px,30%) minmax(0,1fr);gap:8px;padding:7px 0;border-bottom:1px solid #dce3ee}#table_1 .fab-calendar-detail .columnTitle{font-weight:700;color:#17427c}#table_1 .fab-calendar-detail .columnValue{overflow-wrap:anywhere}@media(max-width:1024px){#table_1 .fab-calendar-toggle{display:inline-block!important}#table_1 .fab-calendar-detail span{font-size:13px!important;line-height:1.4!important}}';
        style.textContent += '@media(max-width:1024px){#table_1>thead>tr:first-child>th.column-id,#table_1>tbody>tr:not(.row-detail)>td.column-id{display:table-cell!important;width:42px!important;min-width:42px!important;max-width:42px!important;font-size:0!important;padding:5px!important;text-align:center!important}#table_1 td.column-id .fab-calendar-toggle{margin:0!important}}';
        document.head.appendChild(style);
        if (setup()) return;
        const observer = new MutationObserver(() => { if (setup()) observer.disconnect(); });
        observer.observe(document.body, { childList: true, subtree: true });
        setTimeout(() => observer.disconnect(), 30000);
    }
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start, { once: true }) : start();
})(typeof window === 'undefined' ? globalThis : window);
