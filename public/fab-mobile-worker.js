/** El calendario y la sesión se obtienen siempre de FAB. El aviso sin red no guarda datos del usuario. */
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    if (event.request.mode !== 'navigate' || url.origin !== self.location.origin || url.pathname !== '/app') return;
    event.respondWith(fetch(event.request).catch(() => new Response(
        '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>FAB · Sin conexión</title><body style="font:1.1rem system-ui;padding:1.25rem"><h1>Sin conexión</h1><p>Conectate a Internet para consultar el calendario de FAB.</p><a href="/app">Reintentar</a></body></html>',
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' } },
    )));
});
