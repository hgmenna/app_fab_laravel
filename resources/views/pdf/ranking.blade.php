<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        /* 1. Configuración de Página A4 Horizontal y Márgenes */
        @page { 
            margin: 160px 25px 100px 25px; 
        }

        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 8pt; 
            color: #333; 
            margin: 0;
            padding: 0;
        }

        /* 2. Encabezado Fijo (Logo, Título y Fecha) */
        header { 
            position: fixed; 
            top: -140px; 
            left: 0px; 
            right: 0px; 
            height: 60px; 
            border-bottom: 0.5px solid #eee;
        }

        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; border: none; }
        
        .h1-title { font-size: 18pt; margin: 0; text-align: center; text-transform: uppercase; color: #111; }
        .subtitle { font-size: 12pt; font-weight: bold; text-align: center; margin-top: 5px; color: #444; }
        .date-text { text-align: right; font-size: 9pt; color: #666; }

        /* 3. Pie de Página (Reducido al 50% y Centrado) */
        footer { 
            position: fixed; 
            bottom: -80px; 
            left: 0px; 
            right: 0px; 
            height: 60px; 
            text-align: center;
        }
        .footer-img { width: 50%; height: auto; opacity: 0.8; }

        /* 4. Estructura de la Tabla de Datos (Basada en RankingGeneralWidget) */
        table.data-table { 
            width: 100%; 
            border-collapse: collapse; 
            /*table-layout: fixed; Mantiene anchos de columna estrictos */
        }

        .data-table th, .data-table td { 
            border: 0.5px solid #444; 
            padding: 4px 2px; 
            text-align: center; 
            vertical-align: middle;
            overflow: hidden
            height: 30px;
        }

        /* 5. Definición de Anchos y Alineación de Columnas */
        .col-rg-rc { width: 10px !important; }   /* Estrechas: 3 cifras [1] */
        .col-last-name { width: 130px; text-align: left !important; padding-left: 5px !important; } 
        .col-n { width: 8px; }       /* Inicial del nombre [4] */
        .col-club { width: 150px; text-align: left !important; padding-left: 5px !important; }
        .col-cat-tot { width: 30px; }
        .col-pos { width: 45px; font-size: 7pt; } /* Posición etapa [2] */
        .col-pts { width: 10px !important; }     /* Estrecha: 2 dígitos [2, 5] */

        /* 6. Estética de Grupos y Colores (Basado en extraCellAttributes) */
        .group-label { 
            background-color: green; 
            color: #ffffff; 
            font-weight: bold; 
            text-transform: uppercase; 
            font-size: 10pt
            height: 15px; 
        }
        .column-label { 
            background-color: #green; 
            color: #374151;
            font-weight: bold
            height: 15px; 
        }
        
        /* Color de fondo verde para RC=1 y Totales [1, 4] */
        .bg-green { 
            background-color: green !important; 
            color: #ffffff !important; 
            font-weight: bold; 
        }

        table tr td {
            line-heigt: 1.5;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .text-left { text-align: left !important; padding-left: 5px; }
    </style>
</head>
<body>
    <header>
        <table class="header-table">
            <tr>
                <td style="width: 20%;">
                    <img src="{{ public_path('images/logo.png') }}" style="height: 70px;">
                </td>
                <td style="width: 60%;">
                    <h1 class="h1-title">Ranking Nacional 5 Quillas</h1>
                    <div class="subtitle">Calendario {{ now()->year }}</div>
                </td>
                <td style="width: 20%;" class="date-text">
                    Fecha: {{ now()->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <img src="{{ public_path('images/pie-pagina.png') }}" class="footer-img">
    </footer>

    <main>
        <table class="data-table">
            <thead>
                <!-- Grupos de Columnas definidos en el código fuente [1, 2, 5] -->
                <tr class="group-label">
                    <th colspan="2">Ranking</th>
                    <th colspan="5">Datos Personales</th>
                    <th colspan="2">Etapa 1</th>
                    <th colspan="2">Etapa 2</th>
                    <th colspan="2">Etapa 3</th>
                    <th colspan="2">Etapa 4</th>
                </tr>
                <tr class="column-label">
                    <th class="col-rg-rc group-label">RG</th>
                    <th class="col-rg-rc group-label">RC</th>
                    <th class="col-last-name group-label">Apellidoy y Nombre</th>
                    <th class="col-club group-label">Club</th>
                    <th class="col-cat-tot group-label">Cat</th>
                    <th class="col-cat-tot group-label">Fed</th>
                    <th class="col-cat-tot group-label">Tot</th>
                    <!-- Etiquetas de Etapas según RankingService [3] -->
                    <th class="col-pos group-label">Pos</th><th class="col-pts group-label">Pts</th>
                    <th class="col-pos group-label">Pos</th><th class="col-pts group-label">Pts</th>
                    <th class="col-pos group-label">Pos</th><th class="col-pts group-label">Pts</th>
                    <th class="col-pos group-label">Pos</th><th class="col-pts group-label">Pts</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $row)
                <tr>
                    <td class="col-rg-rc">{{ (int)$row->RG }}</td>
                    <td class="col-rg-rc {{ $row->RC == 1 ? 'bg-green' : '' }}">
                        {{ (int)$row->RC }}
                    </td>

                    <td class="col-last-name text-left">{{ $row->last_name }}, {{substr($row->first_name, 0, 1)}}</td>
                    <td class="col-club text-left">{{ $row->club }}</td>
                    <td class="col-cat-tot">{{ $row->category }}</td>
                    <td class="col-cat-tot">{{ $row->fed }}</td>
                    <td class="col-cat-tot bg-green">{{ (int)$row->total_puntos }}</td>

                    {{-- Etapas: Se eliminan decimales mediante casting (int) [2, 5] --}}
                    <td class="col-pos">{{ $row->pos_1 }}</td>
                    <td class="col-pts">{{ (int)$row->ptos_1 }}</td>
                    
                    <td class="col-pos">{{ $row->pos_2 }}</td>
                    <td class="col-pts">{{ (int)$row->ptos_2 }}</td>
                    
                    <td class="col-pos">{{ $row->pos_3 }}</td>
                    <td class="col-pts">{{ (int)$row->ptos_3 }}</td>
                    
                    <td class="col-pos">{{ $row->pos_4 }}</td>
                    <td class="col-pts">{{ (int)$row->ptos_4 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </main>
</body>
</html>