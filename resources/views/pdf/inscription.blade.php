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
            margin: 20px;
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
            width: 65px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .date {
            text-align: right;
            font-size: 12px;
            color: #555;
        }

        .container {
            width: 100%;
            border: 1px solid #e2e8f0;
            padding: 0;
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .header {
            background-color: #1e293b;
            color: white;
            padding: 12px;
            text-align: center;
        }

        .content {
            padding: 15px;
        }

        /* NUEVO: 2 COLUMNAS */
        .row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 10px;
        }

        .col {
            width: 48%;
        }

        .label {
            font-weight: bold;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
        }

        .value {
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 6px;
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
            margin-top: 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            padding: 8px;
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .receipt-img {
            max-width: 180px;
            max-height: 240px;
            display: block;
            margin: 8px auto;
        }

        .footer {
            width: 100%;
            text-align: center;
            margin-top: 12px;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- ENCABEZADO --}}
    @php
        $logoPath = config('fab.paths.logo');
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 80px;">
                @if(file_exists($logoPath))
                    <img src="file://{{ $logoPath }}" class="logo">
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
            <p style="margin: 4px 0 0 0; font-size: 14px;">
                {{ $record->tournament->name }}
            </p>
        </div>

        <div class="content">

            {{-- FILA 1 --}}
            <div class="row">
                <div class="col">
                    <div class="label">Estado</div>
                    <div class="value">
                        <span class="status-badge {{ $record->status }}">
                            {{ strtoupper($record->status) }}
                        </span>
                    </div>
                </div>

                <div class="col">
                    <div class="label">Horario</div>
                    <div class="value">{{ $record->slot->name }}</div>
                </div>
            </div>

            {{-- FILA 2 --}}
            <div class="row">
                <div class="col">
                    <div class="label">Jugador</div>
                    <div class="value">
                        {{ $record->player->last_name }}, {{ $record->player->first_name }}
                    </div>
                </div>

                <div class="col">
                    <div class="label">Club / Categoría</div>
                    <div class="value">
                        {{ $record->player->club->name }} - {{ $record->player->category->name }}
                    </div>
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

    {{-- PIE --}}
    @php
        $footerPath = config('fab.paths.footer');
    @endphp

    @if(file_exists($footerPath))
        <div class="footer">
            <img src="file://{{ $footerPath }}" style="width: 70%; height: auto;">
        </div>
    @endif

</body>
</html>

