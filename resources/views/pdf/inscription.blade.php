<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de Inscripción</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            line-height: 1.3;
            font-size: 13px;
            margin: 20px 25px; /* margen controlado para A4 */
            padding: 0;
        }

        .header-table {
            width: 100%;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 60px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
        }

        .date {
            text-align: right;
            font-size: 11px;
            color: #555;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            padding: 0;
            border-radius: 0;
            page-break-inside: avoid;
        }

        .header {
            background-color: #1e293b;
            color: white;
            padding: 12px;
            text-align: center;
        }

        .content {
            padding: 15px 18px;
        }

        .field-group {
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px dotted #cbd5e1;
        }

        .label {
            font-weight: bold;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
        }

        .value {
            font-size: 13px;
            color: #1e293b;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }

        .pendiente { background-color: #fef3c7; color: #92400e; }
        .aprobado { background-color: #d1fae5; color: #065f46; }
        .rechazado { background-color: #fee2e2; color: #991b1b; }

        .receipt-box {
            margin-top: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
            padding: 8px;
            border-radius: 0;
            page-break-inside: avoid;
        }

        .receipt-img {
            max-width: 170px;
            max-height: 220px; /* clave para que todo entre */
            display: block;
            margin: 8px auto 0 auto;
            page-break-inside: avoid;
        }

        .pdf-link {
            font-size: 12px;
            color: #2563eb;
            text-decoration: underline;
        }

        .footer {
            width: 100%;
            text-align: center;
            margin-top: 10px;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- ENCABEZADO INSTITUCIONAL --}}
    <table class="header-table">
        <tr>
            <td style="width: 70px;">
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
            <h2 style="margin: 0; font-size: 16px;">Comprobante de Inscripción</h2>
            <p style="margin: 4px 0 0 0; font-size: 13px;">
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
                            <img 
                                src="{{ $pdfSrc }}" 
                                class="receipt-img"
                            >
                        @else
                            <p style="color: #991b1b; font-size: 12px;">No se encontró la imagen del comprobante.</p>
                        @endif
                    @else
                        <p class="pdf-link">
                            <strong>Comprobante en PDF:</strong> {{ basename($relative) }}
                        </p>
                    @endif
                </div>
            @endif

        </div>
    </div>

    {{-- FOOTER INSTITUCIONAL --}}
    @php
        $footerPath = public_path('images/pie-pagina.png');
    @endphp

    @if(file_exists($footerPath))
        <div class="footer">
            <img src="{{ $footerPath }}" style="width: 65%; height: auto;">
        </div>
    @endif

</body>
</html>

