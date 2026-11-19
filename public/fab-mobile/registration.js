import { startLiveRefresh } from './live-refresh.js';

/** La misma pantalla conserva las elecciones y actualiza modalidades, jugadores y cupos desde FAB. */
function initializeRegistration() {
    const form = document.querySelector('#registration');
    if (!form || form.dataset.liveReady) return;
    form.dataset.liveReady = 'yes';
    const modality = form.elements.tournament_modality_id;
    const participants = document.querySelector('#participants');
    const slot = form.elements.tournament_slot_id;
    const status = document.querySelector('#registration-status');
    const submit = form.querySelector('[type=submit]');
    let current, loading = false, ready = false, confirming = false, approved = false;
    let required = 0, requiresSlot = false, players = [], optionsFingerprint = '', message = '';
    const oldPlayers = JSON.parse(participants.dataset.selected || '[]').map(String);
    const selections = () => [...participants.querySelectorAll('select')].map(field => field.value);
    const signature = () => JSON.stringify([modality.value, selections(), slot.value]);
    function confirmState() {
        const chosen = selections();
        const distinct = new Set(chosen.filter(Boolean)).size === chosen.length;
        submit.disabled = confirming || !ready || !required || chosen.length !== required || chosen.some(value => !value) || !distinct || (requiresSlot && !slot.value);
        status.textContent = message || (!distinct ? 'Elegí integrantes distintos.' : '');
    }
    function fill(select, search, selected) {
        const term = search.value.trim().toLocaleLowerCase('es-AR');
        const visible = players.filter(player => String(player.id) === selected || player.name.toLocaleLowerCase('es-AR').includes(term));
        select.replaceChildren(new Option('Elegí un jugador', ''), ...visible.map(player => new Option(player.name, player.id)));
        select.value = visible.some(player => String(player.id) === selected) ? selected : '';
    }
    async function loadOptions({restore = false, force = false} = {}) {
        if (!form.isConnected || (loading && !force)) return false;
        current?.abort();
        const request = new AbortController();
        current = request;
        loading = true;
        if (!ready || force) submit.disabled = true;
        const timeout = setTimeout(() => request.abort(), 12000);
        const selected = restore ? oldPlayers : selections();
        const selectedSlot = restore ? slot.dataset.selected : slot.value;
        const selectedModality = modality.value;
        try {
            const url = new URL(form.dataset.options);
            if (selectedModality) url.searchParams.set('modality', selectedModality);
            const response = await fetch(url, {cache: 'no-store', headers: {Accept: 'application/json'}, signal: request.signal});
            if (!response.ok) throw Error(response.status === 409 ? 'La inscripción está cerrada.' : 'No se pudo verificar la disponibilidad. Se reintentará automáticamente.');
            const payload = await response.json();
            if (current !== request || modality.value !== selectedModality) return false;
            if (payload.modalities) {
                const labels = payload.modalities.map(item => [String(item.id), item.name]);
                if (JSON.stringify([...modality.options].filter(item => item.value).map(item => [item.value, item.text])) !== JSON.stringify(labels)) {
                    modality.replaceChildren(new Option('Elegí la modalidad', ''), ...labels.map(([id, name]) => new Option(name, id)));
                    modality.value = labels.some(([id]) => id === selectedModality) ? selectedModality : '';
                    modality.parentElement.hidden = labels.length === 1;
                    if (!modality.value && labels.length === 1) {
                        modality.value = labels[0][0];
                        return await loadOptions({force: true});
                    }
                    if (modality.value !== selectedModality) { ready = false; participants.replaceChildren(); message = 'La modalidad cambió. Elegí una opción vigente.'; return false; }
                }
            }
            const availableSlots = (payload.slots || []).filter(item => item.remaining === null || Number(item.remaining) > 0);
            const nextFingerprint = JSON.stringify([selectedModality, payload.required, payload.players, availableSlots, payload.requires_slot]);
            required = Number(payload.required);
            requiresSlot = payload.requires_slot ?? Boolean((payload.slots || []).length);
            if (nextFingerprint !== optionsFingerprint || restore) {
                players = payload.players || [];
                if (participants.querySelectorAll('select').length !== required) {
                    participants.replaceChildren();
                    for (let index = 0; index < required; index++) {
                        const label = document.createElement('label');
                        label.textContent = required === 1 ? 'Jugador' : 'Integrante ' + (index + 1);
                        const search = document.createElement('input');
                        search.type = 'search'; search.placeholder = 'Buscar por nombre'; search.setAttribute('aria-label', 'Buscar ' + label.textContent.toLowerCase());
                        const select = document.createElement('select');
                        select.name = 'player_ids[]'; select.required = true; select.setAttribute('aria-label', label.textContent);
                        search.addEventListener('input', () => fill(select, search, select.value));
                        select.addEventListener('change', () => {message = ''; confirmState();});
                        label.append(search, select); participants.append(label);
                    }
                }
                [...participants.querySelectorAll('label')].forEach((label, index) => fill(label.querySelector('select'), label.querySelector('input'), String(selected[index] || '')));
                slot.replaceChildren(new Option(requiresSlot ? 'Elegí un horario disponible' : 'Sin preferencia', ''), ...availableSlots.map(item => new Option(item.name + (item.starts_at ? ' · ' + item.starts_at : '') + (item.remaining === null ? '' : ' · ' + item.remaining + ' de ' + item.capacity + ' cupos libres'), item.id)));
                slot.value = availableSlots.some(item => String(item.id) === String(selectedSlot)) ? String(selectedSlot) : '';
                slot.required = requiresSlot;
                slot.parentElement.hidden = !requiresSlot;
                optionsFingerprint = nextFingerprint;
            }
            ready = true;
            message = requiresSlot && !availableSlots.length ? 'No hay horarios con cupos disponibles. La disponibilidad se actualiza automáticamente.'
                : selectedSlot && !slot.value ? 'El horario elegido ya no está disponible. Elegí otro horario.'
                : selected.some((value, index) => value && selections()[index] !== value) ? 'Un jugador seleccionado ya no está disponible. Revisá los integrantes.' : '';
            return true;
        } catch (error) {
            if (current === request) {ready = false; message = error.message || 'No se pudo verificar la disponibilidad.';}
            return false;
        } finally {
            clearTimeout(timeout);
            if (current === request) { loading = false; confirmState(); }
        }
    }
    modality.addEventListener('change', () => {ready = false; optionsFingerprint = ''; participants.replaceChildren(); slot.replaceChildren(); message = 'Actualizando modalidad…'; void loadOptions({force: true});});
    slot.addEventListener('change', () => {message = ''; confirmState();});
    form.addEventListener('submit', async event => {
        if (approved) {approved = false; submit.disabled = true; status.textContent = 'Registrando inscripción…'; return;}
        event.preventDefault();
        if (confirming || submit.disabled) return;
        const before = signature();
        confirming = true; message = 'Verificando cupos e integrantes…'; confirmState();
        const verified = await loadOptions({force: true});
        confirming = false; confirmState();
        if (!verified || submit.disabled || before !== signature()) return;
        if (form.dataset.preview === 'yes') {status.textContent = 'Formulario comprobado. La vista previa no registra inscripciones.'; return;}
        approved = true;
        form.requestSubmit();
    });
    const stop = startLiveRefresh(() => {if (!form.isConnected) stop(); else if (!confirming) void loadOptions();}, () => form.isConnected, {every: 15000});
    void loadOptions({restore: true});
}
initializeRegistration();
document.addEventListener('mobile-content-updated', initializeRegistration);
