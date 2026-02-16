<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Configuración de márgenes de página para DomPDF */
        @page {
            margin: 120px 50px 100px 50px; /* Espacio para que header y footer no se solapen */
        }

        /* Encabezado fijo: se repite en cada página */
        header {
            position: fixed;
            top: -100px;
            left: 0px;
            right: 0px;
            height: 90px;
            border-bottom: 1px solid #333;
        }

        /* Pie de página fijo: se repite en cada página */
        footer {
            position: fixed;
            bottom: -80px;
            left: 0px;
            right: 0px;
            height: 70px;
            text-align: center;
        }

        /* Tabla del encabezado para distribución Logo / Títulos / Fecha */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        .logo-col { width: 20%; text-align: left; }
        .title-col { width: 60%; text-align: center; }
        .date-col { width: 20%; text-align: right; font-size: 12px; }

        .title { font-size: 20px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .subtitle { font-size: 12px; color: #555; margin: 2px 0 0 0; }

        /* Estilo para la imagen del footer al 70% centrada */
        .footer-image {
            width: 70%;
            height: auto;
            margin: 0 auto;
        }

        /* Estilos del cuerpo del reporte */
        main {
            width: 100%;
        }

        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #333;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .content-table th {
            background-color: green;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }

        .content-table td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        /* Contador de páginas dinámico */
        .pagenum:before {
            content: counter(page);
        }

        .page-counter {
            text-align: center;
            font-size: 8px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <header>
        <table class="header-table">
            <tr>
                <!-- Borde Izquierdo: Logo -->
                <td class="logo-col">
                    @if(isset($logo) && $logo)
                        <img src="{{ $logo }}" style="height: 70px; width: auto;">
                    @endif
                </td>

                <!-- Centro: Título y Subtítulo -->
                <td class="title-col">
                    <div class="title">{{ $title }}</div>
                    @if(isset($subtitle) && $subtitle)
                        <div class="subtitle">{{ $subtitle }}</div>
                    @endif
                </td>

                <!-- Borde Derecho: Fecha -->
                <td class="date-col">
                    Fecha de emisión:<br>
                    <strong>{{ $date }}</strong>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <!-- Imagen de pie de página centrada al 70% -->
        @if(isset($footer_image) && $footer_image)
            <img src="{{ $footer_image }}" class="footer-image">
        @endif
        <div class="page-counter">
            Página <span class="pagenum"></span>
        </div>
    </footer>

    <main>
        <table class="content-table">
            <thead>
                <tr>
                    {{-- Generación dinámica de encabezados de columna [1] --}}
                    @foreach($columns as $column)
                        @php
                            $pxWidth = ($column['chart_width'] ?? 10) * 8;
                        @endphp
                        <th style="width:{{ $pxWidth }}px;">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Iteración dinámica de registros y sus campos procesados [1, 2] --}}
                @foreach($records as $row)
                    <tr>
                        @foreach($columns as $column)
                            <td>
                                {{ $row->{$column['field']} }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
</body>
</html>