const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../public/fab-mobile/live-refresh.js'), 'utf8');
const modulePromise = import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

test('every live section refreshes on schedule, focus, restored pages and recovered connection', async () => {
    const {startLiveRefresh} = await modulePromise;
    const events = {}, pageEvents = {};
    let tick, interval, calls = 0, visible = true, cleared = false;
    const page = {hidden: false, addEventListener: (name, fn) => {pageEvents[name] = fn;}, removeEventListener: name => {delete pageEvents[name];}};
    const network = {onLine: true};
    const browser = {setInterval(fn, ms) {tick = fn; interval = ms; return 1;}, clearInterval() {cleared = true;}, addEventListener: (name, fn) => {events[name] = fn;}, removeEventListener: name => {delete events[name];}};
    const stop = startLiveRefresh(() => calls++, () => visible, {page, browser, network});
    assert.equal(interval, 30000);
    tick(); events.focus(); events.online(); events.pageshow({persisted: true}); pageEvents.visibilitychange();
    assert.equal(calls, 5);
    page.hidden = true; tick(); events.focus(); assert.equal(calls, 5);
    page.hidden = false; visible = false; tick(); assert.equal(calls, 5);
    visible = true; network.onLine = false; tick(); assert.equal(calls, 5);
    network.onLine = true; events.online(); assert.equal(calls, 6);
    events.pageshow({persisted: false}); assert.equal(calls, 6);
    stop(); assert.equal(cleared, true); assert.equal(Object.keys(events).length, 0); assert.equal(Object.keys(pageEvents).length, 0);
});

test('registration and result sections can use a shorter polling interval', async () => {
    const {startLiveRefresh} = await modulePromise;
    let interval;
    startLiveRefresh(() => {}, () => true, {every: 15000, page: {addEventListener() {}}, network: {}, browser: {setInterval(fn, value) {interval = value;}, addEventListener() {}}});
    assert.equal(interval, 15000);
});
