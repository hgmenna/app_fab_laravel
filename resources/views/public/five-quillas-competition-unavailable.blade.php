<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Información no disponible · {{ $competition->tournament?->name }}</title>
    <style>
        :root{color-scheme:light dark;--navy:#173d7c;--ink:#17253a;--muted:#65758b;--line:#d8e0ea;--surface:#fff;--page:#edf2f7}
        *{box-sizing:border-box}
        body{display:grid;min-height:100vh;margin:0;place-items:center;background:var(--page);color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
        main{width:min(620px,calc(100% - 32px));padding:38px 30px;border:1px solid var(--line);border-radius:18px;background:var(--surface);box-shadow:0 18px 55px rgba(23,61,124,.12);text-align:center}
        .icon{display:grid;width:66px;height:66px;margin:0 auto 20px;place-items:center;border-radius:50%;background:#eaf2ff;color:var(--navy)}
        .icon svg{width:34px;height:34px}
        h1{margin:0;font-size:clamp(24px,5vw,34px)}
        h2{margin:10px 0 0;color:var(--navy);font-size:clamp(17px,3vw,22px)}
        p{max-width:470px;margin:18px auto 0;color:var(--muted);font-size:16px;line-height:1.55}
        small{display:block;margin-top:28px;color:var(--muted)}
        @media(prefers-color-scheme:dark){:root{--ink:#eef4ff;--muted:#9dadc2;--line:#2b3950;--surface:#111a28;--page:#09111d}.icon{background:#182c48}}
    </style>
</head>
<body>
<main>
    <div class="icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6Z"/><circle cx="12" cy="12" r="2.5"/><path d="m4 4 16 16"/></svg>
    </div>
    <h1>Información no disponible</h1>
    <h2>{{ $competition->tournament?->name }}</h2>
    <p>No tenés permiso para visualizar esta información en este momento. La organización del torneo todavía no habilitó esta sección para el público.</p>
    <small>Federación Argentina de Billar</small>
</main>
</body>
</html>
