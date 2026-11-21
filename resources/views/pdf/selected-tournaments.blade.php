<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de torneos</title>
    <style>
        @page { margin: 110px 30px 90px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #172d40; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        .meta { color: #5a6975; font-size: 9px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #ced8df; padding: 7px; vertical-align: top; word-wrap: break-word; }
        th { background: #154a70; color: white; text-align: left; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        tbody tr:nth-child(even) { background: #f3f6f8; }
        .date { width: 8%; }
        .name { width: 20%; }
        .venue { width: 20%; }
        .discipline { width: 10%; }
        .type { width: 17%; }
        .categories { width: 17%; }
        .header { position: fixed; top: -88px; left: 0; right: 0; height: 76px; border-bottom: 2px solid #154a70; }
        .header table { border: 0; }
        .header td { border: 0; padding: 0; vertical-align: middle; }
        .header .logo { width: 58px; max-height: 64px; }
        .institution { font-size: 16px; font-weight: bold; text-align: center; }
        .header-date { font-size: 9px; text-align: right; }
        .footer { position: fixed; bottom: -74px; left: 0; right: 0; height: 62px; border-top: 1px solid #154a70; padding-top: 5px; text-align: center; font-size: 8px; color: #667786; }
        .footer img { max-width: 70%; max-height: 38px; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
@php
    // Usa las mismas imágenes institucionales configuradas para los demás documentos del sistema.
    $logo = \App\Helpers\FabPath::logo();
    $footerImage = \App\Helpers\FabPath::footer();
    // Incrusta las imágenes para que DomPDF no dependa de rutas del sistema operativo ni de descargas HTTP.
    $embeddedImage = fn ($path) => is_file($path)
        ? 'data:'.(getimagesize($path)['mime'] ?? 'image/png').';base64,'.base64_encode(file_get_contents($path))
        : null;
    $logoSource = $embeddedImage($logo);
    $footerSource = $embeddedImage($footerImage);
@endphp
<div class="header">
    <table><tr>
        <td style="width: 12%">@if($logoSource)<img src="{{ $logoSource }}" class="logo" alt="Logo institucional">@endif</td>
        <td class="institution">{{ config('cliente.nombre') }}</td>
        <td class="header-date" style="width: 18%">{{ $generatedAt->format('d/m/Y H:i') }}</td>
    </tr></table>
</div>
<div class="footer">
    @if($footerSource)<div><img src="{{ $footerSource }}" alt="Pie institucional"></div>@endif
    <div>{{ config('cliente.sistema') }} · Listado de torneos · Página <span class="page-number"></span></div>
</div>
<h1>Listado de torneos</h1>
<p class="meta">{{ $tournaments->count() }} torneos · Emitido {{ $generatedAt->format('d/m/Y H:i') }}</p>
<table>
    <thead><tr>
        <th class="name">Torneo</th><th class="date">Fecha inicio</th><th class="date">Fecha fin</th>
        <th class="venue">Sede</th><th class="discipline">Disciplina</th><th class="type">Tipo</th><th class="categories">Categorías</th>
    </tr></thead>
    <tbody>
    @foreach ($tournaments as $tournament)
        @php
            // Conserva todas las categorías del torneo y sus modalidades sin repetir nombres.
            $ids = collect($tournament->categories ?? [])
                ->merge($tournament->tournamentModalities->flatMap(fn ($modality) => $modality->categories ?? []))
                ->filter()->unique();
            $categoryNames = $ids->map(fn ($id) => $categories[$id] ?? 'Categoría '.$id)->implode(', ');
        @endphp
        <tr>
            <td>{{ $tournament->name }}</td>
            <td>{{ $tournament->start_date?->format('d/m/Y') ?? 'Sin definir' }}</td>
            <td>{{ $tournament->end_date?->format('d/m/Y') ?? 'Sin definir' }}</td>
            <td>{{ $tournament->venueName() }}</td>
            <td>{{ $tournament->discipline?->name ?? 'Sin definir' }}</td>
            <td>{{ $tournament->type?->name ?? 'Sin definir' }}</td>
            <td>{{ $categoryNames ?: ($tournament->category?->name ?? 'Sin definir') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
