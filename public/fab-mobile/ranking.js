export function mobileRanking() {
    const panel = document.getElementById('ranking');
    const title = document.getElementById('ranking-title');
    const search = document.getElementById('ranking-search');
    const category = document.getElementById('ranking-category');
    const status = document.getElementById('ranking-status');
    const list = document.getElementById('ranking-results');
    const more = document.getElementById('ranking-more');
    const number = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 3 });
    let request;
    let rows = [];
    let limit = 30;
    let season;
    let selected;

    function append(parent, tag, value, className) {
        const item = document.createElement(tag);
        item.textContent = value;
        if (className) item.className = className;
        parent.append(item);
        return item;
    }

    function normalize(value) {
        return String(value ?? '').normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
    }

    function render() {
        const term = normalize(search.value.trim());
        const filtered = rows.filter(row => (!category.value || row.category === category.value)
            && normalize(row.name).includes(term));
        list.replaceChildren();
        for (const row of filtered.slice(0, limit)) {
            const item = append(list, 'tr', '');
            const positionLabel = position => Number.isFinite(position) && position > 0 ? String(position) : '—';
            append(item, 'td', positionLabel(row.position), 'ranking-position');
            append(item, 'td', positionLabel(row.categoryPosition), 'ranking-position');
            const player = append(item, 'td', '', 'ranking-player');
            append(player, 'strong', row.name);
            append(player, 'small', [row.club, row.category && 'Cat. ' + row.category].filter(Boolean).join(' · '));
            append(item, 'td', Number.isFinite(row.points) ? number.format(row.points) : '—', 'ranking-points');
        }
        more.hidden = filtered.length <= limit;
        status.textContent = [season && 'Temporada ' + season, filtered.length
            ? filtered.length + (filtered.length === 1 ? ' jugador' : ' jugadores')
            : 'No hay jugadores para mostrar.'].filter(Boolean).join(' · ');
    }

    async function open(key) {
        if (selected === key) return;
        selected = key;
        request?.abort();
        const current = new AbortController();
        request = current;
        title.textContent = key === 'five' ? 'Ranking de 5 Quillas' : 'Ranking de 3 Bandas';
        rows = [];
        season = null;
        search.value = '';
        category.replaceChildren(new Option('Todas', ''));
        list.replaceChildren();
        more.hidden = true;
        status.textContent = 'Cargando ranking…';
        try {
            const response = await fetch(panel.dataset[key], { headers: { Accept: 'application/json' }, signal: current.signal });
            if (!response.ok) throw Error('No se pudo cargar el ranking. Volvé a intentarlo.');
            const result = await response.json();
            if (request !== current) return;
            season = result.season ?? result.meta?.season ?? null;
            rows = result.data.filter(row => row.jugador ?? row.player).map(row => ({
                name: row.jugador ?? row.player,
                club: row.club,
                category: row.categoria ?? row.category,
                position: row.ranking_general == null ? null : Number(row.ranking_general),
                categoryPosition: Number(row.ranking_categoria ?? row.position),
                points: Number(row.puntos_total ?? row.ranking_points),
            }));
            const categories = [...new Set(rows.map(row => row.category).filter(Boolean))];
            category.append(...categories.sort((a, b) => String(a).localeCompare(String(b), 'es')).map(value => new Option(value, value)));
            limit = 30;
            render();
        } catch (error) {
            if (request === current && !current.signal.aborted) {
                selected = null;
                status.textContent = error.message;
            }
        }
    }

    search.addEventListener('input', () => { limit = 30; render(); });
    category.addEventListener('change', () => { limit = 30; render(); });
    more.addEventListener('click', () => { limit += 30; render(); });

    return { open };
}
