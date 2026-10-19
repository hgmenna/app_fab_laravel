<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:118px 28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#17253a;font-size:8px;margin:0}header{position:fixed;top:-98px;left:0;right:0;height:88px;border-bottom:3px solid #173d7c;padding-bottom:5px}.head{width:100%;border-collapse:collapse}.logo{width:52px;height:52px;object-fit:contain}.title{text-align:center;font-size:18px;font-weight:bold;color:#111}.subtitle{text-align:center;font-size:11px;margin-top:3px}.meta{width:100%;margin-top:5px;border-collapse:collapse}.meta td{padding:3px 6px;border:1px solid #c9d3df}.meta b{color:#173d7c}.section-title{font-size:14px;margin:0 0 8px;color:#173d7c}.zone{page-break-inside:avoid;border:1px solid #9eabbc;margin-bottom:10px}.zone-title{background:#173d7c;color:#fff;padding:6px 8px;font-weight:bold}.columns{width:100%;border-collapse:collapse}.columns>tbody>tr>td{width:50%;vertical-align:top;padding:7px}.matches,.standings{width:100%;border-collapse:collapse}.matches td,.standings th,.standings td{border:1px solid #c9d3df;padding:4px}.standings th{background:#07844d;color:#fff}.match-label{font-weight:bold;color:#173d7c}.winner{font-weight:bold;color:#07844d}.bracket{width:100%;border-collapse:separate;border-spacing:7px 0;table-layout:fixed}.round{vertical-align:top}.round-title{text-align:center;background:#173d7c;color:#fff;font-weight:bold;padding:5px}.round-table{width:100%;border-collapse:collapse;table-layout:fixed}.round-slot{vertical-align:middle;padding:3px 0}.match{border:1px solid #b8c3d0;border-left:4px solid #2464ce;padding:5px;page-break-inside:avoid;background:#f5f8fc}.player{padding:2px 0}.sets{float:right}.positions{width:100%;border-collapse:collapse}.positions thead{display:table-header-group}.positions th,.positions td{border:1px solid #aab5c2;padding:5px;text-align:center}.positions th{background:#07844d;color:#fff}.positions td:nth-child(2),.positions td:nth-child(3){text-align:left}footer{position:fixed;bottom:-22px;left:0;right:0;border-top:1px solid #173d7c;padding-top:5px;color:#65758b;text-align:center}
    </style>
</head>
<body>
<header>
    <table class="head"><tr>
        <td style="width:80px">@if($logoFile)<img class="logo" src="{{ $logoFile }}" alt="Logo">@endif</td>
        <td><div class="title">{{ $tournamentInfo['name'] }}</div><div class="subtitle">{{ $phase?->name ?? 'Posiciones finales' }}</div></td>
        <td style="width:80px"></td>
    </tr></table>
    <table class="meta"><tr>
        <td><b>Sede:</b> {{ $tournamentInfo['venue'] }}</td>
        <td><b>Fecha:</b> {{ $tournamentInfo['date'] }}</td>
        <td><b>Tipo:</b> {{ $tournamentInfo['type'] }} · {{ $tournamentInfo['official'] }}</td>
        <td><b>Modalidad:</b> {{ $tournamentInfo['modality'] }}</td>
    </tr></table>
</header>

@if($section === 'positions')
    <h2 class="section-title">Posiciones finales</h2>
    <table class="positions"><thead><tr><th>Pos</th><th>Jugador</th><th>Club</th><th>Cat</th><th>Instancia</th><th>DS</th><th>Prom</th><th>Pen</th><th>Puntos</th></tr></thead><tbody>
    @forelse($finalRows as $row)
        <tr><td>{{ $row['position'] }}</td><td>{{ $row['player'] }}</td><td>{{ $row['club'] }}</td><td>{{ $row['category'] }}</td><td>{{ $row['instance'] }}</td><td>{{ $row['sets_difference'] }}</td><td>{{ $row['average'] }}</td><td>{{ $row['penalty'] }}</td><td>{{ $row['points'] ?? '—' }}</td></tr>
    @empty<tr><td colspan="9">Las posiciones finales todavía no están disponibles.</td></tr>@endforelse
    </tbody></table>
@elseif($phase->phase_type === 'zones')
    <h2 class="section-title">{{ $phase->name }}</h2>
    @forelse($phase->groups as $group)
        <section class="zone">
            <div class="zone-title">{{ $group->name }} · {{ $group->slot?->name ?? 'Sin horario' }}</div>
            <table class="columns"><tr><td>
                <table class="matches"><tbody>
                @foreach($group->matches->sortBy(fn($match) => [$match->round_number, $match->bracket_order]) as $match)
                    @php $sets = collect(data_get($match->set_scores, 'sets', [])); @endphp
                    <tr><td><span class="match-label">{{ $match->label === 'Partido de recuperación' ? 'Perdedores' : $match->label }}</span><br>
                        <span class="{{ $match->winner_entry_id === $match->player_one_entry_id ? 'winner' : '' }}">{{ $match->playerOne?->player?->full_name ?? 'LIBRE' }}</span><span class="sets">{{ $sets->pluck('player_one_points')->implode(' · ') ?: '—' }}</span><br>
                        <span class="{{ $match->winner_entry_id === $match->player_two_entry_id ? 'winner' : '' }}">{{ $match->playerTwo?->player?->full_name ?? 'LIBRE' }}</span><span class="sets">{{ $sets->pluck('player_two_points')->implode(' · ') ?: '—' }}</span>
                    </td></tr>
                @endforeach
                </tbody></table>
            </td><td>
                <table class="standings"><thead><tr><th>Pos</th><th>Jugador</th><th>PG</th><th>DS</th><th>PF</th><th>PC</th><th>Prom</th></tr></thead><tbody>
                @foreach($group->groupEntries->sortBy(fn($row) => $row->final_place ?: 99) as $row)
                    <tr><td>{{ $row->final_place ?? '—' }}</td><td>{{ $row->entry?->player?->full_name }}</td><td>{{ $row->match_wins }}</td><td>{{ $row->sets_difference }}</td><td>{{ $row->points_for }}</td><td>{{ $row->points_against }}</td><td>{{ number_format((float)$row->points_average,3,',','.') }}</td></tr>
                @endforeach
                </tbody></table>
            </td></tr></table>
        </section>
    @empty<p>Información no disponible en este momento.</p>@endforelse
@else
    <h2 class="section-title">{{ $phase->name }}</h2>
    @php $rounds = $phase->matches->groupBy('round_number')->sortKeys(); @endphp
    @if($rounds->isEmpty())<p>Información no disponible en este momento.</p>@else
    @php $maxMatches = max(1, $rounds->max(fn($roundMatches) => $roundMatches->count())); @endphp
    <table class="bracket"><tr>
        @foreach($rounds as $round => $matches)
            @php $slotHeight = max(48, (int) ceil($maxMatches / max(1, $matches->count())) * 48); @endphp
            <td class="round"><div class="round-title">Ronda {{ $round }}</div><table class="round-table"><tbody>
            @foreach($matches->sortBy('bracket_order') as $match)
                @php
                    $pair = $match->round_number === 1 ? data_get($phase->settings, 'bracket_pairs.'.($match->bracket_order - 1), []) : [];
                    $sets = collect(data_get($match->set_scores, 'sets', []));
                @endphp
                <tr><td class="round-slot" style="height:{{ $slotHeight }}px"><div class="match">
                    <div class="player {{ $match->winner_entry_id === $match->player_one_entry_id ? 'winner' : '' }}"><b>{{ $match->playerOne?->seed ?? ($pair[0] ?? '—') }}</b> · {{ $match->playerOne?->player?->full_name ?? 'LIBRE' }}<span class="sets">{{ $sets->pluck('player_one_points')->implode(' · ') ?: '—' }}</span></div>
                    <div class="player {{ $match->winner_entry_id === $match->player_two_entry_id ? 'winner' : '' }}"><b>{{ $match->playerTwo?->seed ?? ($pair[1] ?? '—') }}</b> · {{ $match->playerTwo?->player?->full_name ?? 'LIBRE' }}<span class="sets">{{ $sets->pluck('player_two_points')->implode(' · ') ?: '—' }}</span></div>
                    @if($match->scheduled_at)<small>{{ $match->scheduled_at->format('d/m/y H:i') }}</small>@endif
                </div></td></tr>
            @endforeach
            </tbody></table></td>
        @endforeach
    </tr></table>
    @endif
@endif
<footer>Federación Argentina de Billar · Información oficial del torneo</footer>
</body></html>
