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

        .label {
            font-weight: bold;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
        }

        .value {
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 8px;
            font-weight: bold;
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

        .receipt-img {
            max-width: 100%;
            max-height: 300px;
            display: block;
            margin: 0 auto;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            width: 100%;
        }

        .footer img {
            width: 70%;
            height: auto;
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

            {{-- TABLA HORIZONTAL: DATOS IZQUIERDA / COMPROBANTE DERECHA --}}
            <table width="100%" style="margin-top: 10px;">
                <tr>

                    {{-- IZQUIERDA: DATOS --}}
                    <td width="55%" valign="top" style="padding-right: 10px;">

                        
                        <div class="label">Jugador</div>
                        <div class="value">
                            {{ $record->player->last_name }}, {{ $record->player->first_name }}
                        </div>
                        
                        <div class="label">Club</div>
                        <div class="value">
                            {{ $record->player->club->name }}
                        </div>
                        
                        <div class="label">Categoría</div>
                        <div class="value">
                            {{ $record->player->category->name }}
                        </div>
                        
                        <div class="label">Horario</div>
                        <div class="value">
                            {{ $record->slot->name }}
                        </div>
                        
                        <div class="label">Estado</div>
                        <div class="value">
                            <span class="status-badge {{ $record->status }}">
                                {{ strtoupper($record->status) }}
                            </span>
                        </div>
                    </td>
                    
                    {{-- DERECHA: COMPROBANTE --}}
                    <td width="45%" valign="top" style="text-align: center;">

                        @php
                            $relative = ltrim($record->payment_file, '/');
                            $absolutePath = "/home/u812683595/domains/sistem.federacionargentinadebillar.org/public_html/{$relative}";
                            $isPdf = str_ends_with(strtolower($relative), '.pdf');
                            $pdfSrc = "file://{$absolutePath}";
                        @endphp

                        @if(!$isPdf && file_exists($absolutePath))
                            <img src="{{ $pdfSrc }}" class="receipt-img">
                        @else
                            <p style="font-size: 12px; color: #991b1b;">
                                Comprobante no disponible
                            </p>
                        @endif

                    </td>

                </tr>
            </table>

        </div>
    </div>

    {{-- PIE --}}
    @php
        $footerPath = config('fab.paths.footer');
    @endphp

    @if(file_exists($footerPath))
        <div class="footer">
            <img src="file://{{ $footerPath }}">
        </div>
    @endif

</body>
</html>
