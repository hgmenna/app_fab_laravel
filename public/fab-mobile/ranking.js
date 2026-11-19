import { startLiveRefresh } from './live-refresh.js';

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
    let loading = false;
    let fingerprint = '';

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

    async function open(key, {background = false} = {}) {
        if (background && loading) return;
        const changedSection = selected !== key;
        selected = key;
        request?.abort();
        const current = new AbortController();
        request = current;
        loading = true;
        const timeout = setTimeout(() => current.abort(), 15000);
        title.textContent = key === 'five' ? 'Ranking de 5 Quillas' : 'Ranking de 3 Bandas';
        if (changedSection) {
            rows = []; season = null; fingerprint = ''; limit = 30;
            search.value = ''; category.replaceChildren(new Option('Todas', ''));
            list.replaceChildren(); more.hidden = true;
        }
        if (!background || changedSection) status.textContent = 'Cargando ranking…';
        try {
            const response = await fetch(panel.dataset[key], { cache: 'no-store', headers: { Accept: 'application/json' }, signal: current.signal });
            if (!response.ok) throw Error('No se pudo cargar el ranking. Volvé a intentarlo.');
            const result = await response.json();
            if (request !== current) return;
            const nextFingerprint = JSON.stringify([result.season, result.meta?.season, result.data]);
            if (fingerprint === nextFingerprint) { renderStatus(); return; }
            fingerprint = nextFingerprint;
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
            const previousCategory = category.value;
            category.replaceChildren(new Option('Todas', ''), ...categories.sort((a, b) => String(a).localeCompare(String(b), 'es')).map(value => new Option(value, value)));
            if (categories.includes(previousCategory)) category.value = previousCategory;
            render();
        } catch (error) {
            if (request === current && !current.signal.aborted) {
                status.textContent = rows.length ? 'No se pudo actualizar el ranking. Se conserva la última información y se reintentará automáticamente.' : 'No se pudo cargar el ranking. Se reintentará automáticamente.';
            }
        } finally {
            clearTimeout(timeout);
            if (request === current) loading = false;
        }
    }

    search.addEventListener('input', () => { limit = 30; render(); });
    category.addEventListener('change', () => { limit = 30; render(); });
    more.addEventListener('click', () => { limit += 30; render(); });
    function renderStatus() {
        const term = normalize(search.value.trim());
        const count = rows.filter(row => (!category.value || row.category === category.value) && normalize(row.name).includes(term)).length;
        status.textContent = [season && 'Temporada ' + season, count ? count + (count === 1 ? ' jugador' : ' jugadores') : 'No hay jugadores para mostrar.'].filter(Boolean).join(' · ');
    }
    startLiveRefresh(() => { if (selected) void open(selected, {background: true}); }, () => !panel.hidden);

    return { open };
}
