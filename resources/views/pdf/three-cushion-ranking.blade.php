<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 92px 24px 72px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 8px; }
        header { position: fixed; top: -72px; left: 0; right: 0; height: 58px; border-bottom: 2px solid #1d4ed8; }
        footer { position: fixed; bottom: -58px; left: 0; right: 0; height: 42px; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 5px; }
        footer img { width: 70%; max-height: 32px; object-fit: contain; }
        .head { width: 100%; border-collapse: collapse; }
        .head td { border: 0; vertical-align: middle; }
        .logo { width: 58px; max-height: 52px; object-fit: contain; }
        h1 { margin: 0; font-size: 16px; color: #172554; text-transform: uppercase; }
        .subtitle { margin-top: 4px; font-size: 10px; color: #475569; }
        .date { text-align: right; color: #64748b; }
        table.ranking { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ranking th { padding: 6px 4px; background: #1e3a8a; color: #fff; border: 1px solid #94a3b8; font-size: 7px; text-transform: uppercase; }
        .ranking td { padding: 5px 4px; border: 1px solid #cbd5e1; text-align: center; }
        .ranking tbody tr:nth-child(even) { background: #f8fafc; }
        .ranking .person, .ranking .club { text-align: left; }
        .pos { width: 4%; font-weight: bold; color: #1d4ed8; }
        .person { width: 18%; font-weight: bold; }
        .club { width: 18%; }
        .small { width: 7%; }
        .average { width: 9%; }
        .points { width: 8%; font-weight: bold; }
        .summary { margin-top: 8px; text-align: right; font-weight: bold; color: #475569; }
    </style>
</head>
<body>
<header>
    <table class="head">
        <tr>
            <td style="width:15%;"><img class="logo" src="{{ $logo }}" alt="FAB"></td>
            <td style="width:65%; text-align:center;">
                <h1>Ranking Carambola 3 Bandas</h1>
                <div class="subtitle">Categoría: {{ $category }} · Temporada {{ $season }}</div>
            </td>
            <td class="date" style="width:20%;">Emisión<br><strong>{{ $generatedAt->format('d/m/Y H:i') }}</strong></td>
        </tr>
    </table>
</header>

<footer>
    @if (is_file($footer))
        <img src="{{ $footer }}" alt="Federación Argentina de Billar">
    @else
        Federación Argentina de Billar
    @endif
</footer>

<main>
    <table class="ranking">
        <thead>
            <tr>
                <th class="pos">Pos.</th>
                <th class="person">Apellido y Nombre</th>
                <th class="club">Club</th>
                <th class="small">Carambolas</th>
                <th class="small">Entradas</th>
                <th class="small">Serie mayor</th>
                <th class="average">Prom. general</th>
                <th class="average">Mejor prom. particular</th>
                <th class="points">Puntos</th>
                <th class="small">Etapas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td class="pos">{{ $record->position }}</td>
                    <td class="person">{{ $record->player?->full_name }}</td>
                    <td class="club">{{ $record->player?->club?->name ?: 'Sin club' }}</td>
                    <td>{{ $record->total_caroms }}</td>
                    <td>{{ $record->total_innings }}</td>
                    <td>{{ $record->high_run }}</td>
                    <td>{{ number_format((float) $record->general_average, 3, ',', '.') }}</td>
                    <td>{{ number_format((float) $record->best_match_average, 3, ',', '.') }}</td>
                    <td class="points">{{ number_format((float) $record->ranking_points, 2, ',', '.') }}</td>
                    <td>{{ $record->stages_played }} / {{ $record->total_stages }}</td>
                </tr>
            @empty
                <tr><td colspan="10">No hay posiciones para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="summary">Jugadores clasificados: {{ $records->count() }}</div>
</main>
</body>
</html>
