<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Torneos seleccionados</title>
    <style>
        @page { margin: 28px 30px 42px; }
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
        .footer { position: fixed; bottom: -25px; font-size: 8px; color: #667786; }
    </style>
</head>
<body>
<div class="footer">{{ config('app.name') }} · Gestión de torneos · {{ $generatedAt->format('d/m/Y H:i') }}</div>
<h1>Torneos seleccionados</h1>
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
