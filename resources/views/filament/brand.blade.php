<table style="border-collapse: collapse; border: 0; padding: 0; margin: 0; width: 500px !important; table-layout: auto;">
    <tr style="vertical-align: middle;">
        <!-- Margen superior izquierdo para el Logo -->
        <td style="padding: 0; margin: 0; vertical-align: middle; width: 50px;">
            <img src="{{ asset('images/logo.png') }}" 
                 alt="Logo" 
                 style="height: 40px !important; width: auto !important; display: block; max-width: none !important;">
        </td>
        
        <!-- Nombre a la derecha -->
        <td style="padding: 0 0 0 15px; margin: 0; vertical-align: middle; text-align: left;">
            <span style="font-size: 22px !important; 
                         font-weight: 800 !important; 
                         color: #d97706 !important; 
                         white-space: nowrap !important; 
                         display: block !important;
                         visibility: visible !important;
                         line-height: 1;">
                {{ config('app.name', 'Federación Argentina de Billar') }}
            </span>
        </td>
    </tr>
</table>