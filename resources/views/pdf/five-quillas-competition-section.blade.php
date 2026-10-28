<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:118px 28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#17253a;font-size:8px;margin:0}header{position:fixed;top:-98px;left:0;right:0;height:88px;border-bottom:3px solid #173d7c;padding-bottom:5px}.head{width:100%;border-collapse:collapse}.logo{width:52px;height:52px;object-fit:contain}.title{text-align:center;font-size:18px;font-weight:bold;color:#111}.subtitle{text-align:center;font-size:11px;margin-top:3px}.meta{width:100%;margin-top:5px;border-collapse:collapse}.meta td{padding:3px 6px;border:1px solid #c9d3df}.meta b{color:#173d7c}.section-title{font-size:14px;margin:0 0 8px;color:#173d7c}.zone{page-break-inside:avoid;border:1px solid #9eabbc;margin-bottom:10px}.zone-title{background:#173d7c;color:#fff;padding:6px 8px;font-weight:bold}.columns{width:100%;border-collapse:collapse}.columns>tbody>tr>td{width:50%;vertical-align:top;padding:7px}.matches,.standings{width:100%;border-collapse:collapse}.matches td,.standings th,.standings td{border:1px solid #c9d3df;padding:4px}.matches td{height:40px}.standings th{background:#07844d;color:#fff}.match-label{font-weight:bold;color:#173d7c}.winner{font-weight:bold;color:#07844d}.bracket{width:100%;height:620px;border-collapse:separate;border-spacing:5px 0;table-layout:fixed;page-break-inside:avoid}.round-title{text-align:center;background:#173d7c;color:#fff;font-weight:bold;padding:4px}.round-slot{vertical-align:middle;padding:1px 0}.match{box-sizing:border-box;height:34px;min-height:34px;max-height:34px;overflow:hidden;border:1px solid #b8c3d0;border-left:3px solid #2464ce;padding:3px 4px;page-break-inside:avoid;background:#f5f8fc;font-size:7px;line-height:1.15}.player{height:9px;padding:0;white-space:nowrap;overflow:hidden}.sets{float:right;background:#e8eef7;padding:0 2px;margin-left:2px}.match-meta{height:8px;margin-top:2px;color:#65758b;font-size:6px;line-height:8px;white-space:nowrap;overflow:hidden}.match-meta span+span{margin-left:5px;padding-left:5px;border-left:1px solid #9eabbc}.positions{width:100%;border-collapse:collapse}.positions thead{display:table-header-group}.positions th,.positions td{border:1px solid #aab5c2;padding:5px;text-align:center}.positions th{background:#07844d;color:#fff}.positions td:nth-child(2),.positions td:nth-child(3){text-align:left}footer{position:fixed;bottom:-22px;left:0;right:0;border-top:1px solid #173d7c;padding-top:5px;color:#65758b;text-align:center}
        .zone-players{width:100%;border-collapse:collapse;table-layout:fixed}.matches .zone-players td{border:0;height:auto;padding:0;vertical-align:middle;line-height:12px}.matches .zone-players td.zone-score{width:85px;text-align:right;white-space:nowrap}.zone-score span{background:#e8eef7;padding:0 2px}
        .zone .matches>tbody>tr>td{height:auto;padding:3px 4px;line-height:10px}.zone .zone-players td{line-height:10px}.zone .zone-title{padding:4px 8px}.zone .columns>tbody>tr>td{padding:5px}
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
    @forelse($phase->groups as $group)
        <section class="zone">
            <div class="zone-title">{{ $group->name }} · {{ $group->slot?->name ?? 'Sin horario' }}</div>
            <table class="columns"><tr><td>
                <table class="matches"><tbody>
                @foreach($group->matches->sortBy(fn($match) => [$match->round_number, $match->bracket_order]) as $match)
                    @php $sets = collect(data_get($match->set_scores, 'sets', [])); @endphp
                    <tr><td><span class="match-label">{{ $match->label === 'Partido de recuperación' ? 'Perdedores' : $match->label }}</span><br>
                        <table class="zone-players">
                            <tr><td class="{{ $match->winner_entry_id === $match->player_one_entry_id ? 'winner' : '' }}">{{ $match->playerOne?->player?->full_name ?? 'LIBRE' }}</td><td class="zone-score"><span>{{ $sets->pluck('player_one_points')->implode(' · ') ?: '—' }}</span></td></tr>
                            <tr><td class="{{ $match->winner_entry_id === $match->player_two_entry_id ? 'winner' : '' }}">{{ $match->playerTwo?->player?->full_name ?? 'LIBRE' }}</td><td class="zone-score"><span>{{ $sets->pluck('player_two_points')->implode(' · ') ?: '—' }}</span></td></tr>
                        </table>
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
    @php
        $roundColumns = $rounds->map(fn($matches) => $matches->sortBy('bracket_order')->values());
    @endphp
    <table class="bracket">
        <thead><tr>
            @foreach($roundColumns as $round => $matches)
                @php $roundTitle = preg_replace('/\s+\d+$/', '', (string) optional($matches->first())->label) ?: 'Ronda '.$round; @endphp
                <th class="round-title">{{ $roundTitle }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @for($gridRow = 0; $gridRow < $maxRoundMatches; $gridRow++)
            <tr style="height:{{ $bracketSlotHeight }}px">
            @foreach($roundColumns as $round => $matches)
                @php
                    $rowSpan = max(1, (int) ($maxRoundMatches / max(1, $matches->count())));
                    $renderMatch = $gridRow % $rowSpan === 0;
                    $match = $renderMatch ? $matches->get((int) ($gridRow / $rowSpan)) : null;
                @endphp
                @if($renderMatch && $match)
                    @php
                        $pair = $match->round_number === 1 ? data_get($phase->settings, 'bracket_pairs.'.($match->bracket_order - 1), []) : [];
                        $sets = collect(data_get($match->set_scores, 'sets', []));
                    @endphp
                    <td class="round-slot" rowspan="{{ $rowSpan }}"><div class="match">
                        <div class="player {{ $match->winner_entry_id === $match->player_one_entry_id ? 'winner' : '' }}"><b>{{ $match->round_number === 1 ? ($pair[0] ?? '—') : ($match->playerOne?->seed ?? '—') }}</b> · {{ $match->playerOne?->player?->full_name ?? 'LIBRE' }}<span class="sets">{{ $sets->pluck('player_one_points')->implode(' · ') ?: '—' }}</span></div>
                        <div class="player {{ $match->winner_entry_id === $match->player_two_entry_id ? 'winner' : '' }}"><b>{{ $match->round_number === 1 ? ($pair[1] ?? '—') : ($match->playerTwo?->seed ?? '—') }}</b> · {{ $match->playerTwo?->player?->full_name ?? 'LIBRE' }}<span class="sets">{{ $sets->pluck('player_two_points')->implode(' · ') ?: '—' }}</span></div>
                        <div class="match-meta">
                            @if($match->scheduled_at)<span>{{ $match->scheduled_at->format('d/m/y H:i') }}</span>@endif
                            @if($match->table_number)<span>Mesa {{ $match->table_number }}</span>@endif
                            @if(!$match->scheduled_at && !$match->table_number)&nbsp;@endif
                        </div>
                    </div></td>
                @endif
            @endforeach
            </tr>
        @endfor
        </tbody>
    </table>
    @endif
@endif
<footer>Federación Argentina de Billar · Información oficial del torneo</footer>
</body></html>
