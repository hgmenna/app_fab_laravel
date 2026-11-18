/** Installation is independent of the calendar so a failed data request cannot block it. */
const standalone = window.matchMedia('(display-mode: standalone)');
const androidWebView = /Android/i.test(navigator.userAgent) && /; wv\)|\bwv\b/i.test(navigator.userAgent);
const installed = () => standalone.matches || navigator.standalone === true;
const page = document.getElementById('fab-pwa-install');
const button = document.getElementById('pwa-install-button');
const status = document.getElementById('pwa-install-status');
let promptEvent = null;
let installationInProgress = false;

// The browser decides whether installation is available; never simulate a successful install.
function displayInstalledState(isInstalled = installed()) {
    for (const element of document.querySelectorAll('[data-pwa-browser-only]')) element.hidden = isInstalled || androidWebView;
    if (button) button.hidden = isInstalled || !promptEvent;
    if (isInstalled && status) {
        status.textContent = 'FAB Deportes ya está abierta como aplicación.';
        const guides = document.getElementById('pwa-install-guides');
        if (guides) guides.hidden = true;
    }
}
window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    promptEvent = event;
    displayInstalledState();
    if (status && !installed()) status.textContent = 'Todo listo. Tocá Instalar FAB Deportes y confirmá.';
});
window.addEventListener('appinstalled', () => {
    promptEvent = null;
    displayInstalledState(true);
    if (status) status.textContent = 'Instalación confirmada. Buscá el icono FAB en tu dispositivo.';
});
standalone.addEventListener?.('change', () => displayInstalledState());
button?.addEventListener('click', async () => {
    if (!promptEvent || installationInProgress) return;
    const event = promptEvent;
    promptEvent = null;
    installationInProgress = true;
    button.disabled = true;
    try {
        await event.prompt();
        const choice = await event.userChoice;
        if (status) status.textContent = choice.outcome === 'accepted'
            ? 'El navegador está completando la instalación. Buscá el icono FAB.'
            : 'Podés instalarla más adelante desde el menú del navegador.';
    } catch {
        if (status) status.textContent = 'Usá el menú del navegador para instalarla siguiendo los pasos de abajo.';
    } finally {
        installationInProgress = false;
        button.disabled = false;
        displayInstalledState();
    }
});

if (page) {
    const ios = /iPad|iPhone|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const platform = ios ? 'ios' : /Android/i.test(navigator.userAgent) ? 'android' : 'desktop';
    for (const guide of page.querySelectorAll('[data-pwa-platform]')) guide.open = guide.dataset.pwaPlatform === platform;
    const embedded = /WhatsApp|FBAN|FBAV|Instagram|; wv\)/i.test(navigator.userAgent);
    const browserNotice = document.getElementById('pwa-external-browser');
    if (browserNotice) browserNotice.hidden = !embedded;
    const shareStatus = document.getElementById('pwa-share-status');
    const address = document.getElementById('pwa-install-url');
    async function copyLink() {
        try {
            if (!navigator.clipboard?.writeText) throw Error('Clipboard unavailable');
            await navigator.clipboard.writeText(page.dataset.installUrl);
            shareStatus.textContent = 'Enlace copiado. Pegalo en WhatsApp o donde quieras compartirlo.';
        } catch {
            address.focus();
            address.select();
            shareStatus.textContent = 'El enlace quedó seleccionado: usá Copiar en el menú del dispositivo.';
        }
    }
    document.getElementById('pwa-copy-button')?.addEventListener('click', copyLink);
    document.getElementById('pwa-share-button')?.addEventListener('click', async () => {
        if (!navigator.share) return copyLink();
        try {
            await navigator.share({title: 'FAB Deportes', text: 'Instalá FAB Deportes: calendario, inscripciones, rankings y resultados de torneos.', url: page.dataset.installUrl});
            shareStatus.textContent = 'Enlace compartido.';
        } catch (error) {
            if (error.name !== 'AbortError') await copyLink();
        }
    });
}
displayInstalledState();

// Never cache accounts, private pages or scores. Worker updates are fetched directly.
if ('serviceWorker' in navigator && window.isSecureContext) {
    void navigator.serviceWorker.register('/fab-mobile-worker.js', {scope: '/app', updateViaCache: 'none'}).catch(() => {
        if (status) status.textContent = 'Si no aparece la instalación, abrí el enlace en Chrome o Safari y seguí los pasos de abajo.';
    });
}
