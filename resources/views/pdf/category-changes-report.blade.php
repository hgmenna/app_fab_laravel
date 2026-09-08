<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <style>
        @page {
            margin: 125px 25px 75px 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* =========================
           ENCABEZADO
        ========================== */

        header {
            position: fixed;
            top: -105px;
            left: 0;
            right: 0;
            height: 90px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        .logo-cell {
            width: 18%;
            text-align: left;
        }

        .title-cell {
            width: 64%;
            text-align: center;
        }

        .date-cell {
            width: 18%;
            text-align: right;
            font-size: 9px;
        }

        .logo {
            max-height: 65px;
            width: auto;
        }

        .main-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .sub-title {
            margin-top: 4px;
            font-size: 9px;
            color: #666;
        }

        /* =========================
           PIE
        ========================== */

        footer {
            position: fixed;
            bottom: -55px;
            left: 0;
            right: 0;
            height: 55px;
            text-align: center;
        }

        .footer-image {
            width: 55%;
            height: auto;
        }

        /* =========================
           TABLA
        ========================== */

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        th {
            background-color: #eeeeee;
            border: 1px solid #bdbdbd;
            padding: 5px 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            vertical-align: middle;
        }

        td {
            border: 1px solid #cccccc;
            padding: 4px 3px;
            vertical-align: top;
            word-wrap: break-word;
        }

        tr {
            page-break-inside: avoid;
        }

        .center {
            text-align: center;
        }

        .player {
            font-weight: bold;
        }

        .club {
            font-weight: bold;
            font-size: 8px;
        }

        .federation {
            margin-top: 2px;
            font-size: 7px;
            color: #666;
        }

        .category-old {
            font-size: 7.5px;
        }

        .category-new {
            margin-top: 2px;
            font-weight: bold;
            font-size: 8px;
        }

        .secondary {
            color: #666;
            font-size: 7px;
        }

        /* =========================
           ESTADOS
        ========================== */

        .status {
            font-weight: bold;
            text-align: center;
        }

        .status-pending {
            color: #9a6700;
        }

        .status-applied {
            color: #18794e;
        }

        .status-registered {
            color: #175cd3;
        }

        /* =========================
           RESUMEN
        ========================== */

        .summary {
            margin-top: 10px;
            padding: 7px;
            background-color: #eeeeee;
            border: 1px solid #cccccc;
            font-size: 9px;
            font-weight: bold;
            text-align: right;
        }

        .no-records {
            padding: 25px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>

<body>

<header>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(isset($logo))
                    <img src="{{ $logo }}" class="logo">
                @endif
            </td>

            <td class="title-cell">
                <div class="main-title">
                    Cambios de categoría
                </div>

                <div class="sub-title">
                    Historial de cambios de categoría de jugadores
                </div>
            </td>

            <td class="date-cell">
                Emisión:<br>
                <strong>{{ $date }}</strong>
            </td>
        </tr>
    </table>
</header>

<footer>
    @if(isset($footer_image))
        <img src="{{ $footer_image }}" class="footer-image">
    @endif
</footer>

<main>

    @if($records->isEmpty())

        <div class="no-records">
            No se encontraron cambios de categoría para los filtros seleccionados.
        </div>

    @else

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">Temp.</th>
                    <th style="width: 11%;">Jugador</th>
                    <th style="width: 18%;">Club / Federación</th>
                    <th style="width: 12%;">Cambio categoría</th>
                    <th style="width: 8%;">Fecha efectiva</th>
                    <th style="width: 8%;">Fecha carga</th>
                    <th style="width: 7%;">Origen</th>
                    <th style="width: 7%;">Estado</th>
                    <th style="width: 13%;">Motivo</th>
                    <th style="width: 11%;">Notas</th>
                </tr>
            </thead>

            <tbody>
                @foreach($records as $record)

                    @php
                        $status = ! in_array(
                            $record->source,
                            ['manual', 'season_promotion'],
                            true
                        )
                            ? 'Registrado'
                            : ($record->applied_at ? 'Aplicado' : 'Pendiente');

                        $statusClass = match ($status) {
                            'Pendiente' => 'status-pending',
                            'Aplicado' => 'status-applied',
                            default => 'status-registered',
                        };

                        $source = match ($record->source) {
                            'manual' => 'Manual',
                            'season_promotion' => 'Promoción',
                            'ranking' => 'Ranking',
                            'tournament' => 'Torneo',
                            'affiliation' => 'Afiliación',
                            default => $record->source ?? '-',
                        };
                    @endphp

                    <tr>
                        <td class="center">
                            {{ $record->season ?? '-' }}
                        </td>

                        <td>
                            <div class="player">
                                {{ $record->player?->last_name ?? '-' }}
                                {{ $record->player?->first_name ?? '' }}
                            </div>
                        </td>

                        <td>
                            <div class="club">
                                {{ $record->player?->club?->name ?? '-' }}
                            </div>

                            <div class="federation">
                                {{ $record->player?->club?->city?->state?->federation?->name ?? '-' }}
                            </div>
                        </td>

                        <td>
                            <div class="category-old">
                                {{ $record->previousCategory?->name ?? '-' }}
                            </div>

                            <div class="category-new">
                                → {{ $record->category?->name ?? '-' }}
                            </div>
                        </td>

                        <td class="center">
                            {{ $record->effective_date?->format('d/m/Y') ?? '-' }}
                        </td>

                        <td class="center">
                            {{ $record->created_at?->format('d/m/Y') ?? '-' }}

                            @if($record->created_at)
                                <div class="secondary">
                                    {{ $record->created_at->format('H:i') }}
                                </div>
                            @endif
                        </td>

                        <td class="center">
                            {{ $source }}
                        </td>

                        <td class="status {{ $statusClass }}">
                            {{ $status }}
                        </td>

                        <td>
                            {{ $record->reason ?? '-' }}
                        </td>

                        <td>
                            {{ $record->notes ?? '-' }}
                        </td>
                    </tr>

                @endforeach
            </tbody>
        </table>

        <div class="summary">
            Total de cambios: {{ $records->count() }}
        </div>

    @endif

</main>

</body>
</html>