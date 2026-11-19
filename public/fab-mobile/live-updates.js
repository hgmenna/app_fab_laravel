import { startLiveRefresh } from './live-refresh.js';

/** Cada sección usa su refresco propio; las vistas de lectura se actualizan sin reemplazar formularios. */
const snapshots = new WeakMap();
let statePending = false, regionsPending = false;
const stateUrl = document.querySelector('meta[name="mobile-app-state"]')?.content;
async function get(url) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 12000);
    try {return await fetch(url, {cache: 'no-store', signal: controller.signal});}
    finally {clearTimeout(timeout);}
}
function syncOptions(select, records) {
    const selected = [...select.selectedOptions].map(option => option.value);
    const old = [...select.options];
    const next = records.map(record => {
        const option = new Option(record.name, String(record.id));
        if (record.discipline_id != null) option.dataset.discipline = String(record.discipline_id);
        option.selected = selected.includes(String(record.id));
        return option;
    });
    if (!select.multiple) next.unshift(new Option(old.find(option => option.value === '')?.text || 'Todos', ''));
    for (const option of old) if (option.value && option.selected && !next.some(item => item.value === option.value)) next.push(option.cloneNode(true));
    const fingerprint = JSON.stringify(next.map(option => [option.value, option.text, option.dataset.discipline]));
    if (snapshots.get(select) === fingerprint) return;
    snapshots.set(select, fingerprint);
    select.replaceChildren(...next);
    if (!select.multiple) select.value = selected[0] || '';
    const discipline = document.querySelector('#filters [name=disciplina]')?.value;
    if (select.name === 'categoria' && discipline) for (const option of select.options) {
        option.disabled = Boolean(option.value && option.dataset.discipline !== discipline);
        option.hidden = option.disabled;
    }
}
async function refreshState() {
    if (!stateUrl || statePending) return;
    statePending = true;
    try {
        const response = await get(stateUrl);
        if (!response.ok) return;
        const state = await response.json();
        const incoming = new DOMParser().parseFromString(state.header, 'text/html').querySelector('.app-menu');
        const menu = document.querySelector('.app-menu');
        if (menu && incoming && snapshots.get(menu) !== incoming.innerHTML) {
            const fingerprint = incoming.innerHTML;
            const open = [...menu.querySelectorAll('details')].map(element => element.open);
            menu.replaceChildren(...incoming.childNodes);
            menu.querySelectorAll('details').forEach((element, index) => {element.open = open[index] || false;});
            snapshots.set(menu, fingerprint);
            const active = location.hash.slice(1) || 'calendario';
            for (const link of menu.querySelectorAll('[data-section]')) {
                if (link.dataset.section === active && location.pathname === '/app') link.setAttribute('aria-current', 'page');
            }
        }
        for (const [name, records] of Object.entries(state.filters || {})) {
            for (const select of document.querySelectorAll(`#filters [name="${name}"], .directory-filters [name="${name}[]"]`)) syncOptions(select, records);
        }
    } catch { /* Una interrupción de red no borra menús ni filtros ya cargados. */ }
    finally {statePending = false;}
}
function editing(region) {
    return Boolean(region.querySelector('#registration, [data-live-final]')) || region.hasAttribute('data-live-final') || [...region.querySelectorAll('input:not([type=hidden]), textarea')].some(input => input === document.activeElement || input.value !== input.defaultValue);
}
function updateNotice(failed) {
    let notice = document.querySelector('[data-live-note]');
    if (!notice && failed) {
        notice = document.createElement('p'); notice.dataset.liveNote = ''; notice.className = 'muted'; notice.setAttribute('role', 'status');
        document.querySelector('main')?.append(notice);
    }
    if (notice) notice.textContent = failed ? 'No se pudo actualizar. Se conserva la última información y se reintentará automáticamente.' : '';
}
function replaceRegion(region, incoming) {
    const details = [...region.querySelectorAll('[data-live-key]')].map(element => [element.dataset.liveKey, [...element.querySelectorAll('details')].map(item => item.open)]);
    const anchor = [...region.querySelectorAll('[data-live-key]')].find(element => element.getBoundingClientRect().bottom > 0);
    const anchorTop = anchor?.getBoundingClientRect().top;
    region.replaceChildren(...incoming.childNodes);
    region.toggleAttribute('data-live-final', incoming.hasAttribute('data-live-final'));
    for (const [key, open] of details) {
        const element = [...region.querySelectorAll('[data-live-key]')].find(item => item.dataset.liveKey === key);
        element?.querySelectorAll('details').forEach((item, index) => {item.open = open[index] || false;});
    }
    const restored = anchor && [...region.querySelectorAll('[data-live-key]')].find(element => element.dataset.liveKey === anchor.dataset.liveKey);
    if (restored && anchorTop != null) window.scrollBy(0, restored.getBoundingClientRect().top - anchorTop);
    document.dispatchEvent(new Event('mobile-content-updated'));
}
async function refreshRegions() {
    const regions = [...document.querySelectorAll('[data-live-region]')].filter(region => !editing(region));
    if (!regions.length || regionsPending) return;
    regionsPending = true;
    try {
        const response = await get(location.href);
        if ([401, 403].includes(response.status)) {
            for (const region of regions) {region.textContent = 'Tu sesión o tus permisos cambiaron. Ingresá nuevamente para continuar.'; snapshots.delete(region);}
            return;
        }
        if (!response.ok) {updateNotice(true); return;}
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        for (const region of regions) {
            const incoming = [...page.querySelectorAll('[data-live-region]')].find(item => item.dataset.liveRegion === region.dataset.liveRegion);
            if (!incoming || editing(region)) continue;
            const fingerprint = incoming.innerHTML;
            if (snapshots.get(region) !== fingerprint) {replaceRegion(region, incoming); snapshots.set(region, fingerprint);}
        }
        updateNotice(false);
    } catch { updateNotice(true); }
    finally {regionsPending = false;}
}
for (const region of document.querySelectorAll('[data-live-region]')) snapshots.set(region, region.innerHTML);
const menu = document.querySelector('.app-menu');
if (menu) snapshots.set(menu, menu.innerHTML);
startLiveRefresh(() => {void Promise.allSettled([refreshState(), refreshRegions()]);});
