<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de Inscripción</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 14px;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 65px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .date {
            text-align: right;
            font-size: 13px;
            color: #555;
        }

        /* CONTENEDOR PRINCIPAL – DOMPDF FRIENDLY */
        .container {
            width: 100%;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            padding: 0;
            border-radius: 0; /* DOMPDF no soporta border-radius */
            page-break-inside: avoid;
        }

        .header {
            background-color: #1e293b;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .content {
            padding: 20px;
        }

        .field-group {
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px dotted #cbd5e1;
        }

        .label {
            font-weight: bold;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
        }

        .value {
            font-size: 15px;
            color: #1e293b;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .pendiente { background-color: #fef3c7; color: #92400e; }
        .aprobado { background-color: #d1fae5; color: #065f46; }
        .rechazado { background-color: #fee2e2; color: #991b1b; }

        /* BLOQUE DEL COMPROBANTE */
        .receipt-box {
            margin-top: 15px;
            text-align: center;
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 0;
            page-break-inside: avoid;
        }

        .receipt-img {
            max-width: 180px;
            max-height: 260px;
            display: block;
            margin: 10px auto;
            page-break-inside: avoid;
        }

        /* FOOTER */
        .footer {
            width: 100%;
            text-align: center;
            margin-top: 15px;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- ENCABEZADO INSTITUCIONAL --}}
    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                @php
                    $logoPath = public_path('images/logo.png');
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

    <div class="container">

        <div class="header">
            <h2 style="margin: 0;">Comprobante de Inscripción</h2>
            <p style="margin: 5px 0 0 0; font-size: 15px;">
                {{ $record->tournament->name }}
            </p>
        </div>

        <div class="content">

            <div class="field-group">
                <div class="label">Estado de Inscripción</div>
                <div class="value">
                    <span class="status-badge {{ $record->status }}">
                        {{ strtoupper($record->status) }}
                    </span>
                </div>
            </div>

            <div class="field-group">
                <div class="label">Jugador</div>
                <div class="value">
                    {{ $record->player->last_name }}, {{ $record->player->first_name }}
                </div>
            </div>

            <div class="field-group">
                <div class="label">Club / Categoría</div>
                <div class="value">
                    {{ $record->player->club->name }} - {{ $record->player->category->name }}
                </div>
            </div>

            <div class="field-group">
                <div class="label">Horario Seleccionado</div>
                <div class="value">
                    {{ $record->slot->name }}
                </div>
            </div>

            {{-- COMPROBANTE --}}
            @if($record->payment_file)
                <div class="receipt-box">

                    @php
                        $relative = ltrim($record->payment_file, '/');
                        $absolutePath = "/home/u812683595/domains/sistem.federacionargentinadebillar.org/public_html/{$relative}";
                        $isPdf = str_ends_with(strtolower($relative), '.pdf');
                        $pdfSrc = "file://{$absolutePath}";
                    @endphp

                    @if(!$isPdf)
                        @if(file_exists($absolutePath))
                            <img src="{{ $pdfSrc }}" class="receipt-img">
                        @else
                            <p style="color: #991b1b;">No se encontró la imagen del comprobante.</p>
                        @endif
                    @else
                        <p><strong>Comprobante en PDF:</strong> {{ basename($relative) }}</p>
                    @endif

                </div>
            @endif

        </div>
    </div>

    {{-- FOOTER --}}
    @php
        $footerPath = public_path('images/pie-pagina.png');
    @endphp

    @if(file_exists($footerPath))
        <div class="footer">
            <img src="{{ $footerPath }}" style="width: 70%; height: auto;">
        </div>
    @endif

</body>
</html>