<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Instalar FAB Deportes</title>
    <meta name="description" content="Instalá FAB Deportes en Android, iPhone, iPad o Windows desde este enlace. Calendario, inscripciones, rankings y resultados de torneos.">
    @include('partials.mobile-pwa-head')
    <link rel="stylesheet" href="{{ asset('fab-mobile/app.css').'?v='.filemtime(base_path('public/fab-mobile/app.css')) }}">
</head>
<body>
<header class="masthead"><div class="wrap header-inner">
    <img src="{{ asset('fab-mobile/icon-192.png') }}" alt="" width="42" height="42">
    <div><strong>FAB Deportes</strong><small>Federación Argentina de Billar</small></div>
</div></header>
<main class="wrap pwa-install-page" id="fab-pwa-install" data-install-url="{{ route('mobile-app.install') }}">
    <section class="pwa-install-hero">
        <img src="{{ asset('fab-mobile/icon-192.png') }}" alt="Escudo de FAB" width="88" height="88">
        <h1>FAB Deportes, siempre a mano</h1>
        <p>Calendario, inscripciones, resultados en vivo y rankings. Ingresá con tu cuenta para acceder a las funciones que tengas habilitadas.</p>
        <div class="pwa-install-actions">
            <button type="button" id="pwa-install-button" hidden>Instalar FAB Deportes</button>
            <a class="button secondary" href="{{ route('mobile-app') }}">Abrir calendario</a>
        </div>
        <p id="pwa-install-status" class="muted" role="status" aria-live="polite">Elegí tu dispositivo y seguí los pasos de instalación.</p>
        <p id="pwa-external-browser" class="pwa-notice" hidden>Si abriste el enlace desde WhatsApp u otra aplicación, usá su menú para abrirlo en Chrome (Android) o Safari (iPhone/iPad).</p>
        <noscript><p>Podés instalarla desde el menú de tu navegador siguiendo los pasos de abajo.</p></noscript>
    </section>
    <section id="pwa-install-guides" aria-label="Cómo instalar la aplicación">
        <details class="pwa-guide" data-pwa-platform="android" open><summary>Android</summary>
            <ol><li>Abrí este enlace en <strong>Chrome</strong>.</li><li>Tocá <strong>Instalar FAB Deportes</strong> cuando aparezca. Si no aparece, abrí el menú ⋮ y elegí <strong>Instalar aplicación</strong> o <strong>Agregar a pantalla principal → Instalar</strong>.</li><li>Confirmá y abrí <strong>FAB</strong> desde su icono.</li></ol>
        </details>
        <details class="pwa-guide" data-pwa-platform="ios"><summary>iPhone y iPad</summary>
            <ol><li>Abrí este enlace en <strong>Safari</strong>.</li><li>Abrí <strong>Compartir</strong> y elegí <strong>Agregar a pantalla de inicio</strong>.</li><li>Si aparece <strong>Abrir como app web</strong>, dejalo activado. Tocá <strong>Agregar</strong>.</li><li>Abrí <strong>FAB</strong> desde la pantalla de inicio.</li></ol>
        </details>
        <details class="pwa-guide" data-pwa-platform="desktop"><summary>Windows y computadoras</summary>
            <ol><li>Abrí este enlace en <strong>Chrome o Microsoft Edge</strong>.</li><li>Tocá <strong>Instalar FAB Deportes</strong> o el icono de instalación de la barra de direcciones. En Edge también podés usar <strong>Menú → Aplicaciones → Instalar este sitio como una aplicación</strong>.</li><li>Confirmá la instalación y abrí FAB desde su acceso directo.</li></ol>
        </details>
    </section>
    <section class="pwa-share" aria-labelledby="pwa-share-title">
        <h2 id="pwa-share-title">Compartí la app</h2>
        <p>Enviá el enlace o mostrale este código QR a otra persona.</p>
        <div class="pwa-share-actions">
            <button type="button" id="pwa-share-button">Compartir enlace</button>
            <button type="button" id="pwa-copy-button" class="secondary">Copiar enlace</button>
        </div>
        <label class="pwa-share-address">Enlace de instalación<input type="url" id="pwa-install-url" readonly value="{{ route('mobile-app.install') }}"></label>
        <p id="pwa-share-status" class="muted" role="status" aria-live="polite"></p>
        <figure class="pwa-qr"><img src="{{ asset('fab-mobile/install-qr.png') }}" alt="Código QR para instalar FAB Deportes" width="328" height="328"><figcaption>Escaneá el QR con la cámara del celular.</figcaption></figure>
        <a class="button secondary" href="{{ asset('fab-mobile/install-qr.png') }}" download="FAB-Deportes-instalar.png">Descargar QR</a>
    </section>
    <p class="pwa-install-note">Se instala desde el navegador: no tenés que descargar un archivo APK. Necesitás conexión a Internet para consultar información actualizada y guardar cambios. Las mejoras se reciben desde el servidor al volver a abrir la app.</p>
</main>
@include('partials.mobile-footer')
</body>
</html>
