function competitionViewStateKey() {
    return 'five-quillas-view:' + window.location.pathname;
}

function restoreCompetitionViewState() {
    try {
        const key = competitionViewStateKey();
        const saved = JSON.parse(sessionStorage.getItem(key) || 'null');
        sessionStorage.removeItem(key);

        return saved?.version === loadedCompetitionViewVersion ? saved.state : null;
    } catch {
        return null;
    }
}

function reloadCompetitionViewIfChanged(version, state = null) {
    if (!version || version === loadedCompetitionViewVersion) return false;

    const url = new URL(window.location.href);
    if (url.searchParams.get('_vista') === version) return false;

    if (state) {
        try {
            sessionStorage.setItem(competitionViewStateKey(), JSON.stringify({ version, state }));
        } catch {
            // La vista también debe actualizarse si el navegador bloquea el almacenamiento.
        }
    }

    url.searchParams.set('_vista', version);
    window.location.replace(url.toString());
    return true;
}
