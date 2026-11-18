import { mobileRanking } from './ranking.js';

const form = document.querySelector('#filters');
const list = document.querySelector('#tournaments');
const status = document.querySelector('#calendar-status');
let current;

function node(tag, text, className) {
    const element = document.createElement(tag);
    if (text) element.textContent = text;
    if (className) element.className = className;
    return element;
}

const actionIcons = {
    Anotarse: 'register', 'Organizar torneo': 'organization', 'Ver resultados': 'result',
    'Ubicación': 'location', Flyer: 'flyer',
};

function icon(name) {
    const namespace = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(namespace, 'svg');
    const use = document.createElementNS(namespace, 'use');
    svg.setAttribute('class', 'fab-icon');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('aria-hidden', 'true');
    use.setAttribute('href', new URL('icons.svg#' + name, import.meta.url).pathname + '#' + name);
    svg.append(use);
    return svg;
}

function link(parent, label, raw, primary = false) {
    const target = raw?.split('||')[0];
    if (!target) return;
    let url;
    try { url = new URL(target, location.href); } catch { return; }
    if (!['https:', 'http:'].includes(url.protocol)) return;
    const anchor = node('a', '', primary ? 'button icon-button' : 'button secondary icon-button');
    anchor.setAttribute('aria-label', label);
    anchor.title = label;
    if (actionIcons[label]) anchor.append(icon(actionIcons[label]));
    else anchor.textContent = label;
    anchor.href = url.href;
    parent.append(anchor);
}

const dateFormatter = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC',
});

function dateLabel(tournament) {
    const start = new Date(tournament.fecha_inicio ?? '');
    const end = new Date(tournament.fecha_fin ?? tournament.fecha_inicio ?? '');
    if (!Number.isFinite(start.getTime())) return [tournament.fecha, tournament.mes].filter(Boolean).join(' · ');
    if (!Number.isFinite(end.getTime()) || start.getTime() === end.getTime()) return dateFormatter.format(start);
    return dateFormatter.formatRange ? dateFormatter.formatRange(start, end) : dateFormatter.format(start) + ' – ' + dateFormatter.format(end);
}

function whatsappNumber(phone) {
    const raw = phone.trim();
    let digits = raw.replace(/\D/g, '');
    if (raw.startsWith('+') || raw.startsWith('00')) {
        if (raw.startsWith('00')) digits = digits.slice(2);
        if (digits.startsWith('54') && digits.length === 12) digits = '549' + digits.slice(2);
        return /^[1-9]\d{7,14}$/.test(digits) ? digits : '';
    }
    if (/^549\d{10}$/.test(digits)) return digits;
    if (/^54\d{10}$/.test(digits)) return '549' + digits.slice(2);
    return /^[1-9]\d{9}$/.test(digits) ? '549' + digits : '';
}

function addContacts(details, tournament) {
    for (const contact of tournament.contactos?.split('|') ?? []) {
        const separator = contact.indexOf(':');
        const name = contact.slice(0, separator).trim();
        const phone = separator >= 0 ? contact.slice(separator + 1).trim() : '';
        const number = whatsappNumber(phone);
        if (!number) {
            details.append(node('p', contact.trim()));
            continue;
        }
        const message = 'Hola, quisiera consultar por el torneo ' + tournament.torneo + '.';
        const anchor = node('a', name || 'Abrir WhatsApp', 'contact-link');
        anchor.href = 'https://wa.me/' + number + '?text=' + encodeURIComponent(message);
        anchor.prepend(icon('contact'));
        details.append(anchor);
    }
}

function tournamentCard(tournament) {
    const card = node('article', '', 'tournament');
    const top = node('div', '', 'card-top');
    top.append(node('span', tournament.disciplina), node('span', tournament.estado_torneo, 'badge'));
    card.append(top, node('h2', tournament.torneo));
    const venue = tournament.club?.trim();
    card.append(node('p', dateLabel(tournament), 'date'));
    card.append(node('p', venue && !/^sin asignar$/i.test(venue) ? venue : 'Sede por confirmar', 'venue'));
    const details = node('details', '', 'tournament-details');
    details.append(node('summary', 'Detalles'));
    const facts = node('dl', '', 'tournament-facts');
    const addFact = (label, value, className = '') => {
        if (!value) return;
        const field = node('div', '', className);
        field.append(node('dt', label), node('dd', value));
        facts.append(field);
    };
    addFact('Fecha', dateLabel(tournament), 'date');
    addFact('Sede', venue && !/^sin asignar$/i.test(venue)
        ? [venue, tournament.provincia].filter(Boolean).join(' · ')
        : 'Por confirmar', 'full-width');
    addFact('Disciplina', tournament.disciplina);
    addFact('Tipo', tournament.tipo);
    addFact('Modalidad', tournament.modalidad);
    addFact('Categorías', tournament.categorias, 'full-width');
    if (tournament.inscripciones != null) addFact('Inscripciones', String(tournament.inscripciones));
    if (tournament.estado_torneo === 'Próximo') addFact('Estado de inscripción', tournament.estado_inscripcion);
    details.append(facts);
    const actions = node('div', '', 'actions');
    if (tournament.inscripcion && Number.isSafeInteger(Number(tournament.id)) && Number(tournament.id) > 0) {
        link(actions, 'Anotarse', form.dataset.registration.replace('__TOURNAMENT__', String(tournament.id)), true);
    }
    link(actions, 'Organizar torneo', tournament.organizacion_url);
    if (tournament.desarrollo_disponible && /^[a-z0-9-]+$/.test(tournament.desarrollo_slug ?? '')) {
        link(actions, 'Ver resultados', form.dataset.results.replace('result-slug', tournament.desarrollo_slug), !tournament.inscripcion);
    }
    const secondary = node('div', '', 'actions');
    link(secondary, 'Ubicación', tournament.ubicacion);
    link(secondary, 'Flyer', tournament.flyer_url);
    if (secondary.childElementCount) details.append(secondary);
    if (tournament.apertura_inscripcion) details.append(node('p', 'Apertura: ' + tournament.apertura_inscripcion));
    if (tournament.cierre_inscripcion) details.append(node('p', 'Cierre: ' + tournament.cierre_inscripcion));
    const contacts = node('details', '', 'contacts');
    contacts.append(node('summary', 'Contactos'));
    addContacts(contacts, tournament);
    const more = node('div', '', 'tournament-more');
    more.append(details);
    if (contacts.childElementCount > 1) more.append(contacts);
    if (actions.childElementCount) card.append(actions);
    card.append(more);
    return card;
}

async function loadCalendar() {
    current?.abort();
    const request = new AbortController();
    current = request;
    const url = new URL(form.dataset.endpoint);
    for (const [key, value] of new FormData(form)) if (value) url.searchParams.set(key, value);
    status.textContent = 'Cargando torneos…';
    list.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal });
        if (!response.ok) throw Error(response.status === 422 ? 'Revisá los filtros, en especial el rango de fechas.' : 'No pudimos cargar el calendario.');
        const { data, meta } = await response.json();
        if (current !== request) return;
        const year = form.elements.anio;
        if (year.options.length === 1) {
            for (const value of meta.anios ?? []) {
                const option = node('option', String(value));
                option.value = String(value);
                year.append(option);
            }
        }
        list.replaceChildren(...data.map(tournamentCard));
        status.textContent = data.length ? data.length + (data.length === 1 ? ' torneo' : ' torneos') : 'No hay torneos para los filtros elegidos.';
    } catch (error) {
        if (request.signal.aborted) return;
        status.textContent = error instanceof TypeError ? 'Sin conexión. Revisá la red y volvé a aplicar los filtros.' : error.message;
    } finally {
        if (current === request) list.removeAttribute('aria-busy');
    }
}

function filterCategories() {
    const discipline = form.elements.disciplina.value;
    const category = form.elements.categoria;
    if (!category.options) return;
    for (const option of category.options) {
        option.disabled = Boolean(discipline && option.value && option.dataset.discipline !== discipline);
        option.hidden = option.disabled;
    }
    if (category.selectedOptions[0]?.disabled) category.value = '';
}

let searchTimer;
form.elements.buscar.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadCalendar, 280);
});
form.addEventListener('submit', event => {
    clearTimeout(searchTimer);
    form.querySelector('.calendar-filter').open = false;
    event.preventDefault();
    void loadCalendar();
});
form.elements.estado.addEventListener('change', loadCalendar);
form.elements.disciplina.addEventListener('change', () => {
    filterCategories();
    void loadCalendar();
});
form.addEventListener('reset', () => setTimeout(() => {
    filterCategories();
    void loadCalendar();
}, 0));
window.addEventListener('pageshow', event => { if (event.persisted) void loadCalendar(); });
void loadCalendar();

const navigation = document.querySelector('.app-navigation');
const sections = [...navigation.querySelectorAll('[data-section]')];
const ranking = mobileRanking();

function showSection() {
    const key = ({ '#ranking-5-quillas': 'five', '#ranking-3-bandas': 'three' })[location.hash];
    const active = key ? location.hash.slice(1) : 'calendario';
    document.getElementById('ranking').hidden = !key;
    document.getElementById('calendario').hidden = Boolean(key);
    if (key) void ranking.open(key);
    for (const link of sections) {
        if (link.dataset.section === active) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    }
}

navigation.querySelector('nav').addEventListener('click', event => {
    if (event.target.closest('a')) navigation.open = false;
});
window.addEventListener('hashchange', () => {
    showSection();
    window.scrollTo(0, 0);
});
showSection();
