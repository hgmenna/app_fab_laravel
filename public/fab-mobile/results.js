const screen = document.getElementById('mobile-results');
const title = document.getElementById('results-title');
const selector = document.getElementById('results-phase');
const status = document.getElementById('results-status');
const rows = document.getElementById('results-rows');
const filters = document.getElementById('results-filters');
let data, fingerprint, selectedZone = '', subsection = 'bracket', loading = false;

// All names and labels come from the public API and are inserted as text, never HTML.
function text(parent, tag, value = '', className = '') {
    const element = document.createElement(tag);
    element.textContent = String(value ?? '');
    element.className = className;
    parent.append(element);
    return element;
}

// Preserve actual set scores, including the current points of an unfinished match.
function matchBox(parent, match) {
    const box = text(parent, 'article', '', 'development-match');
    box.dataset.match = match.id;
    text(box, 'small', match.etiqueta || 'Partido', 'match-label');
    const live = match.marcador_en_vivo;
    if (live) text(box, 'small', '● En juego', 'live-label');
    for (const index of [0, 1]) {
        const side = index + 1;
        const name = match['jugador_' + side] || match['placeholder_' + side] || 'A designar';
        const line = text(box, 'div', '', 'development-player' + (match.ganador === name ? ' winner' : ''));
        text(line, 'span', [match['numero_' + side], name].filter(value => value != null && value !== '').join(' · '));
        const score = live?.sets?.[index] ?? (match.ganador ? match.sets_reales?.[index] : null);
        text(line, 'strong', score ?? '—', 'set-score');
    }
    const sets = Array.isArray(match.sets) ? match.sets : match.sets?.sets || [];
    const detail = sets.map(set => Array.isArray(set) ? set.join('–') : `${set.player_one_points}–${set.player_two_points}`);
    if (live?.points) detail.push('En juego: ' + live.points.join('–'));
    if (detail.length) text(box, 'small', detail.join(' / '), 'match-detail');
    text(box, 'small', [match.horario, match.mesa && 'Mesa ' + match.mesa, match.pase_automatico && 'Pase automático'].filter(Boolean).join(' · '), 'match-detail');
    return box;
}

// Draw the same progression using explicit next-match relationships. Horizontal scroll
// retains readable names on a phone instead of shrinking an entire bracket to fit.
function bracket(parent, matches, zone = false) {
    if (!matches.length) return text(parent, 'p', 'La grilla todavía no fue publicada.', 'muted');
    text(parent, 'p', 'Deslizá horizontalmente para seguir los cruces →', 'development-hint');
    const viewport = text(parent, 'div', '', 'development-scroll');
    viewport.tabIndex = 0;
    viewport.setAttribute('aria-label', zone ? 'Desarrollo de la zona' : 'Grilla de eliminación');
    const canvas = text(viewport, 'div', '', 'development-canvas');
    const rounds = [...new Set(matches.map(match => Number(match.ronda)))].sort((a, b) => a - b);
    const points = new Map();
    const width = 300, gap = 36, height = 166, rowGap = 22;
    const firstCount = Math.max(...rounds.map(round => matches.filter(match => Number(match.ronda) === round).length));
    const fullHeight = firstCount * (height + rowGap) + 40;
    canvas.style.width = (rounds.length * (width + gap) - gap) + 'px';
    canvas.style.height = fullHeight + 'px';
    rounds.forEach((round, column) => {
        const group = matches.filter(match => Number(match.ronda) === round).sort((a, b) => a.orden - b.orden);
        const heading = text(canvas, 'h3', zone ? 'Ronda ' + round : ({1: 'Final', 2: 'Semifinales', 4: 'Cuartos de final', 8: 'Octavos de final', 16: 'Dieciseisavos'}[group.length] || 'Ronda ' + round), 'round-heading');
        heading.style.left = column * (width + gap) + 'px';
        group.forEach((match, index) => {
            const incoming = matches.filter(source => source.siguiente_partido_id === match.id).map(source => points.get(source.id)).filter(Boolean);
            const y = !zone && incoming.length ? incoming.reduce((sum, point) => sum + point.y, 0) / incoming.length : 40 + (index + .5) * (fullHeight - 40) / group.length;
            const x = column * (width + gap);
            const box = matchBox(canvas, match);
            box.style.left = x + 'px';
            box.style.top = (y - height / 2) + 'px';
            points.set(match.id, {x, y});
        });
    });
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('class', 'development-connectors');
    svg.setAttribute('width', canvas.style.width.replace('px', ''));
    svg.setAttribute('height', fullHeight);
    svg.setAttribute('aria-hidden', 'true');
    for (const match of matches) {
        const from = points.get(match.id), to = points.get(match.siguiente_partido_id);
        if (!from || !to || to.x <= from.x) continue;
        const path = document.createElementNS(svg.namespaceURI, 'path');
        const mid = to.x - gap / 2;
        path.setAttribute('d', `M${from.x + width},${from.y} H${mid} V${to.y} H${to.x}`);
        svg.append(path);
    }
    canvas.prepend(svg);
}

function table(parent, headers, records) {
    const scroll = text(parent, 'div', '', 'development-scroll');
    const grid = text(scroll, 'table', '', 'development-table');
    const head = text(text(grid, 'thead'), 'tr');
    headers.forEach(label => { const cell = text(head, 'th', label); cell.scope = 'col'; });
    const body = text(grid, 'tbody');
    records.forEach(record => { const row = text(body, 'tr'); record.forEach(value => text(row, 'td', value ?? '—')); });
}

function render() {
    if (!data) return;
    filters.replaceChildren();
    rows.replaceChildren();
    status.textContent = '';
    const phase = (data.fases || []).find(item => String(item.id) === selector.value);
    if (!phase) {
        table(rows, ['Posición', 'Jugador', 'Club', 'Instancia', 'Puntos'], (data.posiciones_finales || []).map(item => [item.posicion, item.jugador, item.club, item.instancia, item.puntos]));
        return;
    }
    const matches = phase.partidos || [];
    if (phase.tipo === 'zones') {
        const label = text(filters, 'label', 'Zona');
        const select = text(label, 'select');
        select.add(new Option('Todas las zonas', ''));
        (phase.zonas || []).forEach(zone => select.add(new Option([zone.nombre, zone.horario].filter(Boolean).join(' · '), zone.id)));
        select.value = selectedZone;
        select.onchange = () => { selectedZone = select.value; render(); };
        for (const zone of phase.zonas || []) {
            if (selectedZone && String(zone.id) !== selectedZone) continue;
            const section = text(rows, 'section', '', 'development-zone');
            text(section, 'h2', zone.nombre);
            text(section, 'p', zone.horario || zone.inicio || '', 'muted');
            bracket(section, matches.filter(match => match.zona_id === zone.id), true);
            text(section, 'h3', 'Posiciones de la zona');
            table(section, ['Pos.', 'Jugador', 'PG', 'DS', 'PF', 'PC', 'Prom.'], [...(zone.jugadores || [])].sort((a,b) => (a.clasificacion || a.posicion) - (b.clasificacion || b.posicion)).map(player => [player.clasificacion || player.posicion, player.nombre, player.partidos_ganados, player.diferencia_sets, player.puntos_favor, player.puntos_contra, player.promedio]));
        }
    } else {
        const preliminary = matches.filter(match => Number(match.ronda) === 0);
        if (preliminary.length) {
            const tabs = text(filters, 'div', '', 'development-tabs');
            for (const [value, caption] of [['bracket', 'Grilla final'], ['pairings', 'Emparejamientos']]) {
                const button = text(tabs, 'button', caption, value === subsection ? 'selected' : 'secondary');
                button.type = 'button';
                button.setAttribute('aria-pressed', String(value === subsection));
                button.onclick = () => { subsection = value; render(); };
            }
        }
        if (subsection === 'pairings' && preliminary.length) {
            for (const match of preliminary.sort((a,b) => a.orden - b.orden)) {
                const section = text(rows, 'section', '', 'development-pairing');
                text(section, 'h2', 'Posición ' + (match.posicion_ingreso || match.orden));
                matchBox(section, match);
                text(section, 'p', 'Ganador: ' + (match.ganador || 'A definir'), 'pairing-winner');
            }
        } else bracket(rows, matches.filter(match => Number(match.ronda) > 0));
    }
    if (!rows.children.length) text(rows, 'p', 'Todavía no hay desarrollo publicado.');
}

// Refresh live scores without resetting the chosen phase or interrupting horizontal scroll.
async function load() {
    if (loading) return;
    loading = true;
    try {
        const response = await fetch(screen.dataset.source, {cache: 'no-store', headers: {Accept: 'application/json'}});
        if (!response.ok) throw Error('Resultados no disponibles');
        const payload = (await response.json()).data;
        const nextFingerprint = JSON.stringify(payload);
        if (nextFingerprint === fingerprint) { status.textContent = ''; return; }
        const previous = selector.value;
        const positions = [...rows.querySelectorAll('.development-scroll')].map(element => element.scrollLeft);
        data = payload;
        fingerprint = nextFingerprint;
        title.textContent = data.torneo;
        const options = (data.fases || []).map(phase => new Option(phase.nombre, phase.id));
        if (data.posiciones_finales?.length) options.push(new Option('Posiciones finales', 'standings'));
        selector.replaceChildren(...options);
        if (options.some(option => option.value === previous)) selector.value = previous;
        selector.disabled = options.length < 2;
        render();
        rows.querySelectorAll('.development-scroll').forEach((element, index) => { element.scrollLeft = positions[index] || 0; });
    } catch {
        status.textContent = data ? 'No se pudo actualizar. Se conservan los últimos resultados; se reintentará automáticamente.' : 'No se pudieron cargar los resultados. Se reintentará automáticamente.';
    } finally { loading = false; }
}
selector.addEventListener('change', () => { selectedZone = ''; subsection = 'bracket'; render(); });
setInterval(() => { if (!document.hidden) void load(); }, 10000);
document.addEventListener('visibilitychange', () => { if (!document.hidden) void load(); });
void load();
