<?php

// Una instalación completa por cliente. Las credenciales continúan en DB_* y MAIL_*.
return [
    'codigo' => env('CLIENTE_CODIGO', 'fab'),
    'nombre' => env('CLIENTE_NOMBRE', 'Federación Argentina de Billar'),
    'nombre_legal' => env('CLIENTE_NOMBRE_LEGAL', env('CLIENTE_NOMBRE', 'Federación Argentina de Billar')),
    'sigla' => env('CLIENTE_SIGLA', 'FAB'),
    'sistema' => env('CLIENTE_SISTEMA', 'Sistema Administrativo '.env('CLIENTE_NOMBRE', 'Federación Argentina de Billar')),
    'pais' => env('CLIENTE_PAIS', 'AR'),
    'pais_nombre' => env('CLIENTE_PAIS_NOMBRE', 'Argentina'),
    'direccion' => env('CLIENTE_DIRECCION', ''),
    'email' => env('CLIENTE_EMAIL', ''),
    'telefono' => env('CLIENTE_TELEFONO', ''),
    'whatsapp' => env('CLIENTE_WHATSAPP', ''),
    'web_url' => env('CLIENTE_WEB_URL', 'https://federacionargentinadebillar.org'),
    'sistema_url' => env('APP_URL', 'http://localhost'),
    'zona_horaria' => env('CLIENTE_ZONA_HORARIA', 'America/Argentina/Buenos_Aires'),
    'idioma' => env('CLIENTE_IDIOMA', env('APP_LOCALE', 'es')),
    'moneda' => env('CLIENTE_MONEDA', 'ARS'),
    'simbolo_moneda' => env('CLIENTE_SIMBOLO_MONEDA', '$'),
    'decimales' => (int) env('CLIENTE_DECIMALES', 2),
    'separador_decimal' => env('CLIENTE_SEPARADOR_DECIMAL', ','),
    'separador_miles' => env('CLIENTE_SEPARADOR_MILES', '.'),
    'formato_fecha' => env('CLIENTE_FORMATO_FECHA', 'd/m/Y'),
    'logo' => env('CLIENTE_LOGO', 'images/logo.png'),
    'pie_documentos' => env('CLIENTE_PIE_DOCUMENTOS', 'images/pie-pagina.png'),
    'favicon' => env('CLIENTE_FAVICON', env('CLIENTE_LOGO', 'images/logo.png')),
    'color_marca' => env('CLIENTE_COLOR_MARCA', '#d97706'),
    'raiz_publica' => env('CLIENTE_RAIZ_PUBLICA', is_dir(base_path('../public_html')) ? base_path('../public_html') : public_path()),
    // Identidad del proveedor: independiente de la federación contratante.
    'proveedor' => [
        'nombre' => env('PROVEEDOR_NOMBRE', 'Hernán Gabriel Menna'),
        'actividad' => env('PROVEEDOR_ACTIVIDAD', 'Desarrollo y Arquitectura de Software'),
        'email' => env('PROVEEDOR_EMAIL', 'hgmenna@hotmail.com'),
        'telefono' => env('PROVEEDOR_TELEFONO', '+54 9 341 598 8191'),
        'ubicacion' => env('PROVEEDOR_UBICACION', 'Rosario - Santa Fe - Argentina'),
    ],
];
