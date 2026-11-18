/** Solo ofrece una pantalla de recuperación sin red; no almacena páginas ni información personal. */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    const appPage = url.pathname === '/app' || url.pathname.startsWith('/app/');
    if (event.request.method !== 'GET' || event.request.mode !== 'navigate' || url.origin !== self.location.origin || !appPage) return;
    event.respondWith(fetch(event.request).catch(() => new Response(
        '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#111e31"><title>FAB Deportes · Sin conexión</title><style>body{margin:0;background:#0b1422;color:#f0f5fd;font:1.1rem/1.6 system-ui;padding:8vh 6vw}main{max-width:32rem;margin:auto}a{display:inline-block;margin-top:1rem;padding:.8rem 1.2rem;background:#ffca70;color:#182132;border-radius:.6rem;text-decoration:none;font-weight:700}</style><main><strong>FAB Deportes</strong><h1>Sin conexión a Internet</h1><p>Conectate para consultar el calendario y los resultados, inscribirte o administrar torneos.</p><p>No se guardaron cambios desde esta pantalla.</p><a href="">Reintentar</a></main></html>',
        {status: 503, headers: {'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store'}},
    )));
});
