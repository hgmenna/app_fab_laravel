/** Actualiza la sección visible cada 30 segundos y al recuperar la app o la conexión. */
export function startLiveRefresh(refresh, isSectionVisible = () => true, {
    page = document,
    browser = window,
    network = navigator,
    every = 30000,
} = {}) {
    const update = () => {
        if (!page.hidden && network.onLine !== false && isSectionVisible()) refresh();
    };
    const restored = event => { if (event.persisted) update(); };
    const timer = browser.setInterval(update, every);
    page.addEventListener('visibilitychange', update);
    browser.addEventListener('focus', update);
    browser.addEventListener('online', update);
    browser.addEventListener('pageshow', restored);

    // También permite retirar los listeners si esta pantalla se monta en otro contenedor.
    return () => {
        browser.clearInterval(timer);
        page.removeEventListener('visibilitychange', update);
        browser.removeEventListener('focus', update);
        browser.removeEventListener('online', update);
        browser.removeEventListener('pageshow', restored);
    };
}
