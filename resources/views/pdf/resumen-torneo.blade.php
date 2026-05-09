<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen del Torneo</title>

    <style>
        /* ------------------------------
           ESTILOS GENERALES
        ------------------------------ */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #222;
        }

        /* ------------------------------
           ENCABEZADO
        ------------------------------ */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .header-table .logo {
            width: 70px;
        }

        .header-table .title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }

        .header-table .date {
            text-align: right;
            font-size: 12px;
            color: #555;
        }

        /* ------------------------------
           TABLA PRINCIPAL
        ------------------------------ */
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.report-table th {
            background: #003366;
            color: white;
            padding: 8px;
            text-align: center;
            font-size: 12px;
        }

        table.report-table td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: center;
        }

        .federation {
            text-align: left;
            font-weight: bold;
        }

        .row-total {
            background: #f2f2f2;
            font-weight: bold;
        }

        /* ------------------------------
           RESPONSIVE (para móviles)
           DomPDF no soporta media queries,
           pero sí navegadores cuando se visualiza online.
        ------------------------------ */
        @media screen and (max-width: 768px) {
            body {
                font-size: 14px;
            }

            .header-table .title {
                font-size: 18px;
            }

            table.report-table th,
            table.report-table td {
                font-size: 11px;
                padding: 4px;
            }
        }

        /* ------------------------------
           FOOTER
        ------------------------------ */
        .footer {
            width: 100%;
            text-align: center;
            margin-top: 30px;
        }

        .footer img {
            width: 70%;
            height: auto;
        }
    </style>
</head>

<body>

    {{-- ENCABEZADO INSTITUCIONAL --}}
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                @php
                    use App\Helpers\FabPath;
                    $logoPath = FabPath::logo();
                @endphp

                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" class="logo">
                @endif
            </td>

            <td class="title">
                {{ config('app.name') }}
            </td>

            <td class="date">
                {{ now()->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    {{-- TÍTULO DEL REPORTE --}}
    <h2 style="text-align: center; margin-bottom: 5px;">
        {{ $tournament->name }}
    </h2>

    <p style="text-align: center; color: #555; margin-top: 0;">
        Resumen de Participantes
    </p>

    {{-- TABLA DE RESUMEN --}}
    <table class="report-table">
        <thead>
            <tr>
                <th>FEDERACION</th>

                @foreach ($categorias as $cat)
                    <th>{{ $cat->name }}</th>
                @endforeach

                <th>Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($tabla as $fed => $fila)
                <tr>
                    <td class="federation">{{ $fed }}</td>

                    @foreach ($categorias as $cat)
                        <td>{{ $fila[$cat->name] }}</td>
                    @endforeach

                    <td class="row-total">{{ $fila['total'] }}</td>
                </tr>
            @endforeach

            {{-- Fila de totales --}}
            <tr class="row-total">
                <td>Total General</td>

                @foreach ($categorias as $cat)
                    <td>{{ $totales[$cat->name] }}</td>
                @endforeach

                <td>{{ $totales['total'] }}</td>
            </tr>
        </tbody>
    </table>

    {{-- FOOTER INSTITUCIONAL --}}
    @php
        $footerPath = FabPath::footer();
    @endphp

    @if(file_exists($footerPath))
        <div class="footer">
            <img src="{{ $footerPath }}">
        </div>
    @endif

</body>
</html>