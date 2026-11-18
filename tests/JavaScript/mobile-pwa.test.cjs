const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const installSource = fs.readFileSync(path.join(__dirname, '../../public/fab-mobile/install.js'), 'utf8');
const workerSource = fs.readFileSync(path.join(__dirname, '../../public/fab-mobile-worker.js'), 'utf8');

function installation({ios = false, standalone = false, clipboard = true} = {}) {
    const events = {};
    const element = () => ({hidden: false, disabled: false, textContent: '', dataset: {}, handlers: {}, addEventListener(name, fn) {this.handlers[name] = fn;}, focus() {this.focused = true;}, select() {this.selected = true;}});
    const names = ['fab-pwa-install', 'pwa-install-button', 'pwa-install-status', 'pwa-install-guides', 'pwa-external-browser', 'pwa-share-status', 'pwa-install-url', 'pwa-copy-button', 'pwa-share-button'];
    const elements = Object.fromEntries(names.map(name => [name, element()]));
    elements['fab-pwa-install'].dataset.installUrl = 'https://fab.test/app/instalar';
    const guides = ['android', 'ios', 'desktop'].map(platform => ({dataset: {pwaPlatform: platform}, open: false}));
    elements['fab-pwa-install'].querySelectorAll = () => guides;
    const banners = [element(), element()];
    let registration;
    vm.runInNewContext(installSource, {
        document: {getElementById: name => elements[name], querySelectorAll: () => banners},
        window: {isSecureContext: true, matchMedia: () => ({matches: standalone, addEventListener() {}}), addEventListener: (name, fn) => {events[name] = fn;}},
        navigator: {userAgent: ios ? 'iPad' : 'Chrome Android', platform: ios ? 'MacIntel' : 'Linux', maxTouchPoints: ios ? 5 : 0,
            ...(clipboard ? {clipboard: {writeText: async () => {}}} : {}),
            serviceWorker: {register: (url, options) => {registration = {url, options}; return Promise.resolve();}}},
    });
    return {events, elements, guides, banners, registration};
}

test('installation consumes one browser prompt and waits for appinstalled to confirm success', async () => {
    const ui = installation();
    let prompts = 0;
    assert.equal(ui.elements['pwa-install-button'].hidden, true);
    ui.events.beforeinstallprompt({preventDefault() {}, prompt: async () => {prompts++;}, userChoice: Promise.resolve({outcome: 'accepted'})});
    assert.equal(ui.elements['pwa-install-button'].hidden, false);
    await ui.elements['pwa-install-button'].handlers.click();
    await ui.elements['pwa-install-button'].handlers.click();
    assert.equal(prompts, 1);
    assert.match(ui.elements['pwa-install-status'].textContent, /completando/);
    ui.events.appinstalled();
    assert.match(ui.elements['pwa-install-status'].textContent, /confirmada/);
    assert.equal(ui.elements['pwa-install-button'].hidden, true);
    assert.ok(ui.banners.every(banner => banner.hidden));
    assert.equal(ui.registration.url, '/fab-mobile-worker.js');
    assert.equal(ui.registration.options.scope, '/app');
    assert.equal(ui.registration.options.updateViaCache, 'none');
});

test('iPad uses manual iOS instructions; installed apps hide installation promotion', () => {
    const ios = installation({ios: true});
    assert.equal(ios.guides.find(guide => guide.open).dataset.pwaPlatform, 'ios');
    assert.equal(ios.elements['pwa-install-button'].hidden, true);
    const app = installation({standalone: true});
    assert.ok(app.banners.every(banner => banner.hidden));
    assert.equal(app.elements['pwa-install-guides'].hidden, true);
});

test('copy fallback selects the URL without claiming it has been copied', async () => {
    const ui = installation({clipboard: false});
    await ui.elements['pwa-copy-button'].handlers.click();
    assert.equal(ui.elements['pwa-install-url'].selected, true);
    assert.match(ui.elements['pwa-share-status'].textContent, /seleccionado/);
});

function worker(fetch) {
    const events = {};
    vm.runInNewContext(workerSource, {URL, Response, fetch, self: {location: {origin: 'https://fab.test'}, addEventListener: (name, fn) => {events[name] = fn;}}});
    return events;
}

test('offline fallback covers all app pages but never handles submissions, APIs or other websites', async () => {
    const events = worker(async () => {throw Error('offline');});
    for (const pathname of ['/app', '/app/instalar', '/app/cuenta', '/app/resultados/torneo', '/app/torneos/40/organizacion']) {
        let response;
        events.fetch({request: {url: 'https://fab.test' + pathname, method: 'GET', mode: 'navigate'}, respondWith: value => {response = value;}});
        const result = await response;
        assert.equal(result.status, 503);
        assert.equal(result.headers.get('Cache-Control'), 'no-store');
        assert.match(await result.text(), /Sin conexión/);
    }
    for (const request of [
        {url: 'https://fab.test/app/cuenta', method: 'POST', mode: 'navigate'},
        {url: 'https://fab.test/app/calendario.json', method: 'GET', mode: 'cors'},
        {url: 'https://fab.test/admin', method: 'GET', mode: 'navigate'},
        {url: 'https://fab.test/application', method: 'GET', mode: 'navigate'},
        {url: 'https://other.test/app', method: 'GET', mode: 'navigate'},
    ]) {
        events.fetch({request, respondWith: () => assert.fail('Request must remain untouched')});
    }
});

test('online navigation returns the server response and never a saved score or page', async () => {
    const fresh = new Response('resultado actualizado');
    let response;
    worker(async () => fresh).fetch({request: {url: 'https://fab.test/app', method: 'GET', mode: 'navigate'}, respondWith: value => {response = value;}});
    assert.equal(await response, fresh);
});
