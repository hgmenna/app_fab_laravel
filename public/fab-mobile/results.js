const screen = document.getElementById('mobile-results');
const title = document.getElementById('results-title');
const selector = document.getElementById('results-phase');
const status = document.getElementById('results-status');
const rows = document.getElementById('results-rows');
const more = document.getElementById('results-more');
let data;
let limit = 20;

function text(parent, tag, value, className = '') {
    const element = document.createElement(tag);
    element.textContent = String(value ?? '');
    if (className) element.className = className;
    parent.append(element);
    return element;
}

function render() {
    if (!data) return;
    rows.replaceChildren();
    const phase = data.fases.find(item => String(item.id) === selector.value);
    const records = phase ? phase.partidos.filter(match => !match.pase_automatico)
        : selector.value === 'standings' ? data.posiciones_finales : [];
    for (const record of records.slice(0, limit)) {
        const card = text(rows, 'li', '', 'tournament');
        if (!phase) {
            text(card, 'strong', record.posicion + ' · ' + record.jugador);
            text(card, 'p', [record.club, record.instancia].filter(Boolean).join(' · '), 'muted');
            continue;
        }
        const scored = record.marcador_en_vivo;
        const score = scored?.sets ?? (record.ganador ? record.sets_reales : null);
        for (const index of [0, 1]) {
            const side = index + 1;
            const name = record['jugador_' + side] || record['placeholder_' + side] || 'A designar';
            const sets = score?.[index];
            const points = scored?.points?.[index];
            text(card, 'p', name + (sets != null ? ' · ' + sets + ' sets' : '') + (points != null ? ' · ' + points + ' puntos' : ''));
        }
        text(card, 'small', [record.horario, record.mesa && 'Mesa ' + record.mesa].filter(Boolean).join(' · '), 'muted');
    }
    more.hidden = records.length <= limit;
    status.textContent = records.length ? '' : 'No hay resultados publicados en esta sección.';
}

async function load() {
    try {
        const response = await fetch(screen.dataset.source, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw Error('Los resultados no están disponibles.');
        ({ data } = await response.json());
        title.textContent = data.torneo;
        const options = data.fases.map(phase => new Option(phase.nombre, phase.id));
        if (data.posiciones_finales.length) options.push(new Option('Posiciones finales', 'standings', true, true));
        selector.replaceChildren(...options);
        selector.disabled = options.length < 2;
        render();
    } catch {
        status.textContent = 'No se pudieron cargar los resultados.';
    }
}
selector.addEventListener('change', () => { limit = 20; render(); });
more.addEventListener('click', () => { limit += 20; render(); });
void load();
