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
        }

        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 70px;
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

        .container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0;
        }

        .header {
            background-color: #1e293b;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .content {
            padding: 25px;
        }

        .field-group {
            margin-bottom: 12px;
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

        .receipt-box {
            margin-top: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 5px;
        }

        .receipt-img {
            max-width: 220px;
            height: auto;
            border-radius: 4px;
            margin-top: 10px;
        }

        .pdf-link {
            font-size: 13px;
            color: #2563eb;
            text-decoration: underline;
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
            <h2 style="margin: 0; padding: 0;">Comprobante de Inscripción</h2>
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

            @if($record->payment_file)
                <div class="receipt-box">

                    @php
                        // Ruta relativa guardada en la BD, por ejemplo: "pagos/archivo.jpg"
                        $relative = $record->payment_file;

                        // URL pública accesible desde el navegador
                        $publicUrl = url($relative);

                        // Ruta absoluta en el servidor (solo para verificar existencia)
                         $absolutePath = '/domains/sistem.federacionargentinadebillar.org/public_html/' . $relative;

                        // Detectar si es PDF
                        $isPdf = str_ends_with($relative, '.pdf');
                    @endphp

                    @if(!$isPdf)
                        @if(file_exists($absolutePath))
                            <img src="{{ $publicUrl }}" class="receipt-img">
                        @else
                            <p style="color: #991b1b;">No se encontró la imagen del comprobante.</p>
                        @endif
                    @else
                        <p style="margin: 0; font-size: 14px;">📄 <strong>Comprobante en PDF</strong></p>
                        <p class="pdf-link">
                            Archivo: {{ basename($relative) }}
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
        <div style="width: 100%; text-align: center; margin-top: 30px;">
            <img src="{{ $footerPath }}" style="width: 70%; height: auto;">
        </div>
    @endif

</body>
</html>