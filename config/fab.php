<?php

return [

    'paths' => [

        // Raíz pública absoluta del hosting
        'public_root' => config('cliente.raiz_publica'),

        // Rutas relativas institucionales
        'logo'   => config('cliente.logo'),
        'footer' => config('cliente.pie_documentos'),

        // Carpetas reales
        'pagos'         => 'pagos',
        'inscripciones' => 'inscripciones',
    ],

];

