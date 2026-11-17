const form = document.querySelector('#registration');
if (form) {
    const modality = form.elements.tournament_modality_id;
    const participants = document.querySelector('#participants');
    const slot = form.elements.tournament_slot_id;
    const status = document.querySelector('#registration-status');
    const submit = form.querySelector('[type=submit]');
    let current;

    const confirmState = () => {
        const fields = [...participants.querySelectorAll('select')];
        const chosen = fields.map(field => field.value).filter(Boolean);
        submit.disabled = !fields.length || chosen.length !== fields.length || new Set(chosen).size !== chosen.length;
        if (chosen.length !== new Set(chosen).size) status.textContent = 'Elegí integrantes distintos.';
        else if (status.textContent === 'Elegí integrantes distintos.') status.textContent = '';
    };

    async function loadOptions(restore = false) {
        current?.abort();
        const request = new AbortController();
        current = request;
        submit.disabled = true;
        participants.replaceChildren();
        slot.replaceChildren(new Option('Sin preferencia', ''));
        slot.parentElement.hidden = true;
        status.textContent = '';
        if (!modality.value) return;
        status.textContent = 'Cargando jugadores…';
        try {
            const url = new URL(form.dataset.options);
            url.searchParams.set('modality', modality.value);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal });
            if (!response.ok) throw Error(response.status === 409 ? 'La inscripción está cerrada.' : 'No se pudieron cargar las opciones.');
            const { required, players, slots } = await response.json();
            if (current !== request) return;
            const previous = restore ? JSON.parse(participants.dataset.selected || '[]') : [];
            for (let index = 0; index < required; index++) {
                const label = document.createElement('label');
                label.textContent = required === 1 ? 'Jugador' : 'Integrante ' + (index + 1);
                const search = document.createElement('input');
                search.type = 'search';
                search.placeholder = 'Buscar por nombre';
                search.setAttribute('aria-label', 'Buscar ' + label.textContent.toLowerCase());
                const select = document.createElement('select');
                select.setAttribute('aria-label', label.textContent);
                select.name = 'player_ids[]';
                select.required = true;
                const fill = selected => {
                    const term = search.value.trim().toLocaleLowerCase('es-AR');
                    const visible = players.filter(player => String(player.id) === selected || player.name.toLocaleLowerCase('es-AR').includes(term));
                    select.replaceChildren(new Option('Elegí un jugador', ''), ...visible.map(player => new Option(player.name, player.id)));
                    select.value = selected;
                };
                fill(String(previous[index] ?? ''));
                search.addEventListener('input', () => fill(select.value));
                select.addEventListener('change', confirmState);
                label.append(search, select);
                participants.append(label);
            }
            for (const item of slots) {
                const when = item.starts_at ? ' · ' + item.starts_at : '';
                const places = item.remaining === null ? '' : item.remaining === 0
                    ? ' · Completo' : ' · ' + item.remaining + ' de ' + item.capacity + ' cupos libres';
                const option = new Option(item.name + when + places, item.id);
                option.disabled = item.remaining === 0;
                slot.append(option);
            }
            if (restore && [...slot.options].some(option => !option.disabled && option.value === slot.dataset.selected)) slot.value = slot.dataset.selected;
            slot.parentElement.hidden = slots.length === 0;
            status.textContent = players.length >= required ? '' : 'No hay suficientes jugadores habilitados.';
            confirmState();
        } catch (error) {
            if (!request.signal.aborted) status.textContent = error.message;
        }
    }

    modality.addEventListener('change', () => void loadOptions());
    form.addEventListener('submit', event => {
        if (submit.disabled) event.preventDefault();
        else if (form.dataset.preview === 'yes') {
            event.preventDefault();
            status.textContent = 'Formulario comprobado. La vista previa no registra inscripciones.';
        } else {
            submit.disabled = true;
            status.textContent = 'Registrando inscripción…';
        }
    });
    void loadOptions(true);
}
