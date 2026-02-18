<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* 1. Márgenes de página para evitar solapamiento (Fuentes 1, 3, 5) */
        @page { 
            margin: 160px 30px 100px 30px; 
        }

        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }

        /* 2. Encabezado Fijo (Se repite en todas las páginas) */
        header { 
            position: fixed; 
            top: -130px; 
            left: 0px; 
            right: 0px; 
            height: 110px; 
        }

        /* 3. Pie de Página Fijo */
        footer { 
            position: fixed; 
            bottom: -70px; 
            left: 0px; el
            right: 0px; 
            height: 80px; 
            text-align: center;
        }

        /* Tabla de encabezado: Logo izq, Título centro, Fecha der */
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { border: none; vertical-align: middle; }
        .logo-cell { width: 20%; text-align: left; }
        .title-cell { width: 60%; text-align: center; }
        .date-cell { width: 20%; text-align: right; font-size: 12px; }

        .logo { max-height: 80px; width: auto; }
        .main-title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .sub-title { font-size: 12px; color: #555; margin-top: 5px; }

        /* TABLA DE DATOS: Layout FIXED es obligatorio para respetar los PX */
        table.data-table { 
            width: auto;
            table-layout: fixed !important; 
            border-collapse: collapse; 
            margin-bottom: 20px;
        }

        thead {
            display: table-header-group !important;
        }

        tr {page-break-inside: avoid !important;}
        thead tr {page-break-after: avoid !important;}

        th { 
            background-color: #f2f2f2; 
            border: 1px solid #ccc; 
            padding: 4px; 
            font-size: 11px; 
            text-transform: uppercase;
            font-weight: bold;
            height: 1.2rem;
        }

        td { 
            border: 1px solid #ccc; 
            padding: 4px; 
            vertical-align: middle;
            word-wrap: break-word; /* Evita que el texto rompa el diseño */
        }

        .text-center { text-align: center; }
        
        /* Títulos de Horarios (Fuentes 1, 4, 6) */
        .group-header { 
            background-color: #444; color: #fff; padding: 8px; 
            font-size: 11px; font-weight: bold; margin-bottom: 0px;
        }

        .group-wrapper {
            page-break-inside: avoid;
            margin-bottom: 15px;
        }

        .summary-row {background-color: #f3f3f3; font-weight: bold; font-size: 12px;}

        .total-general-section 
        {
            margin-top: 10px; 
            padding: 10px; 
            background-color: #eee; 
            font-weight: bold;
            text-align: right;
            font-size: 14px;
        }

        .footer-image { width: 70%; height: auto; }
    </style>
</head>
<body>

    <header>
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(isset($logo)) <img src="{{ $logo }}" class="logo"> @endif
                </td>
                <td class="title-cell">
                    <div class="main-title">{{ $title }}</div>
                    @if(isset($subtitle)) <div class="sub-title">{{ $subtitle }}</div> @endif
                </td>
                <td class="date-cell">
                    Emisión:<br><strong>{{ $date }}</strong>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        @if(isset($footer_image)) <img src="{{ $footer_image }}" class="footer-image"> @endif
    </footer>

    <main>
        @if(isset($groups))
            @foreach($groups as $slotName => $rows)
                <div class="group-wrapper;">
                    <div class="group-header">{{ $slotName }}</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach($columns as $column)
                                    <th style="width: {{ $column['width'] }}px !important;" 
                                        class="{{ in_array($column['field'], ['ranking_category', 'ranking_rg']) ? 'text-center' : '' }}">
                                        {{ $column['label'] }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    @foreach($columns as $column)
                                        <td class="{{ in_array($column['field'], ['ranking_category', 'ranking_rg']) ? 'text-center' : '' }}">
                                            {{ $row->{$column['field']} }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            {{-- Subtotales --}}
                            <tr class="summary-row">
                                <td colspan="{{ count($columns) -1 }}" style="text-align: right;">
                                    Total: 
                                </td>
                                <td style="text-align: center;">
                                    {{ count($rows) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach
            @isset($totalGeneral)
                <div class="total-general-section">
                    Total {{ $labelTotalGeneral }}: {{ $totalGeneral }}
                </div>
                
            @endisset
        @endif
    </main>

</body>
</html>